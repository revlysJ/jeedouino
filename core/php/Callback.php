<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom and the jeedouino plugin are distributed in the hope that they will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

// TODO : Verifier l'origine de l'appel
jeedouino::log( 'debug', __('CALLBACK - Requête reçue : ? ', __FILE__) . $_SERVER['QUERY_STRING']);
if (isset($_GET['BoardEQ']))
{
	$arduino_id = trim($_GET['BoardEQ']);
	if ($arduino_id == '' or $arduino_id == null or $arduino_id == 0)
	{
		jeedouino::log( 'error', __('CALLBACK - ID de l\'équipement non défini : *', __FILE__) . $arduino_id . '*');
		return;
	}
	$eqLogic = eqLogic::byId($arduino_id);

	if ($eqLogic !== false and $eqLogic !== null)
	{
		// paliatif manque lastCommunication
		config::save('lastCommunication' . $arduino_id, date('Y-m-d H:i:s'), 'jeedouino');

		$ModeleArduino = $eqLogic->getConfiguration('arduino_board');
		if (config::byKey($arduino_id . '_HasDemon', 'jeedouino', 0))
		{
			if (method_exists('jeedouino', 'updateControlCmd')) jeedouino::updateControlCmd($arduino_id, true);
		}
		// Specifique Analog to Digital pins OUT - Etat
		$ard328 = false;
		switch ($ModeleArduino)
		{
			case 'auno':
			case 'a2009':
			case 'anano':
			case 'auno':
				$ard328 = true;
			break;
		}
		config::save('REP_' . $arduino_id, '', 'jeedouino'); 	// Pour Double vérif
		if (isset($_GET['REP'])) config::save('REP_'.$arduino_id, $_GET['REP'], 'jeedouino'); 	// Pour Double vérif

		list($Arduino_pins,$board,$usb) = jeedouino::GetPinsByBoard($arduino_id);

		$BOARDNAME = 'Equipement ' . strtoupper($eqLogic->getName()) . ' (eqID: '.$arduino_id.') ';
		$CALLBACK = 'CALLBACK - ' . $BOARDNAME . '- ';
		$_ProbeNoLog = config::byKey($arduino_id . '_ProbeNoLog', 'jeedouino', 0);

		// Cas de la téléinfo recue, on tente de l'envoyer au plugin teleinfo
		if (isset($_GET['ADCO']))
		{
			jeedouino::log( 'debug', $CALLBACK . __('vient d\'envoyer une trame téléinfo. J\'essaye de la transmettre au plugin adapté.', __FILE__));
			if (method_exists('teleinfo', 'createFromDef'))
			{
				//$ApiKey = config::byKey('api');
				$ApiKey = jeedom::getApiKey('teleinfo');
				if ($ApiKey == '')  jeedouino::log( 'error', $CALLBACK . __('Impossible de trouver la clé API du plugin Téléinfo.', __FILE__));
				else
				{
					// Traitement de la trame
					$message = '';
					$deviceTeleinfo= '';
					$etiquettes = array('ADCO' , 'OPTARIF' , 'ISOUSC' , 'BASE' , 'PTEC' , 'IINST' , 'IMAX' , 'PAPP' , 'MOTDETAT' , 'BBRHCJB' , 'BBRHPJB' , 'BBRHCJW' , 'BBRHPJW' , 'BBRHCJR' , 'BBRHPJR' , 'DEMAIN' , 'HCHC' , 'HCHP' , 'EJPHN' , 'EJPHPM' , 'PEJP' , 'ADPS' , 'HHPHC' );
					$teleinfo = array_unique(explode(';' , $_GET['ADCO']));
					jeedouino::log( 'debug', $CALLBACK . 'Teleinfo :' . json_encode($teleinfo));
					foreach($teleinfo as $value)
					{
						$champs =  explode('_' , $value);
						jeedouino::log( 'debug', $CALLBACK . 'Champ :' . json_encode($champs));
						if (isset($champs[0]) and isset($champs[1]))
						{
							if (in_array($champs[0], $etiquettes))
							{
								$message .= ',"' . $champs[0] . '":"' . $champs[1] . '"';
								if ( $champs[0] == 'ADCO' ) $deviceTeleinfo = $champs[1];
								jeedouino::log( 'debug', $CALLBACK . 'Champ compris :' . $champs[0] . ' = ' . $champs[1]);
							}
							else jeedouino::log( 'debug', $CALLBACK . 'Etiquette non trouvée :' . json_encode($champs));
						}
						else jeedouino::log( 'debug', $CALLBACK . 'Champ non compris :' . json_encode($champs));
					}
					$message = '{"device":{"' . $deviceTeleinfo . '":{"device":"' . $deviceTeleinfo . '"' . $message . '}}}';

					// Envoi le tout au plugin Téléinfo
					$http_ = trim(config::byKey('internalProtocol'));
					if ($http_ == '' ) $http_ = 'http://';
					$JeedomIP = jeedouino::GetJeedomIP();
					$JeedomPort = jeedouino::GetJeedomPort();
					$JeedomCPL =jeedouino::GetJeedomComplement();
					$url = $http_ . $JeedomIP . ':' . $JeedomPort . $JeedomCPL . '/plugins/teleinfo/core/php/jeeTeleinfo.php?apikey=' . $ApiKey;
					jeedouino::log( 'debug', 'Appel de : ' . $url);
					jeedouino::log( 'debug', 'Message POST : ' . $message);

					$options = array('http' => array(	'method'  			=> 'POST',
																						'header'  			=> "Content-Type: application/json",
																						'ignore_errors' => true,
																						'timeout' 			=> 10,
																						'content' 			=> $message,
																					),
													);
					$context  = stream_context_create($options);
					return trim(@file_get_contents($url, false, $context));
				}
			}
			else jeedouino::log( 'error', $CALLBACK . __(' - Impossible de trouver le plugin téléinfo.', __FILE__));
			return;
		}
		// Informations fournies par les démons
		if (isset($_GET['NODEP']))
		{
			if ($eqLogic->getIsEnable() == 0) jeedouino::StopBoardDemon($arduino_id, 0, $ModeleArduino);
			config::save('NODEP_' . $arduino_id, $_GET['NODEP'], 'jeedouino');
			$message = __('Dépendances ', __FILE__) . ucfirst(strtolower($_GET['NODEP'])) . __(' introuvables ou problème de configuration.' , __FILE__);
			if (isset($_GET['errdep'])) $message .= __(' >> Erreur : ', __FILE__) . $_GET['errdep'];
			jeedouino::logAlert('error', 'danger', ':bg-danger: /!\ ' . $message . ' :/bg:');
		}
		if (isset($_GET['NOBMEP']))
		{
			if ($eqLogic->getIsEnable() == 0) jeedouino::StopBoardDemon($arduino_id, 0, $ModeleArduino);
			$message = __('Sonde ', __FILE__) . ucfirst(strtolower($_GET['NOBMEP'])) . __(' introuvable. Veuillez vérifier l\'adresse i2c choisie.' , __FILE__);
			jeedouino::logAlert('error', 'warning', $message);
		}
		if (isset($_GET['PINGME']))
		{
			if ($eqLogic->getIsEnable() == 0) jeedouino::StopBoardDemon($arduino_id, 0, $ModeleArduino);
			jeedouino::log( 'debug', $CALLBACK . __('Le 1er thread du démon demande un test PING ...', __FILE__));
			$result = jeedouino::StatusBoardDemon($arduino_id, 1, $ModeleArduino); // Force le cache
		}
		if (isset($_GET['THREADSDEAD']))
		{
			jeedouino::updateControlCmd($arduino_id, false);
			if ($eqLogic->getIsEnable() == 0) jeedouino::StopBoardDemon($arduino_id, 0, $ModeleArduino);
			jeedouino::log( 'error', $CALLBACK . __('Les threads du démon sont hs. Tentative de redémarrage du démon en cours...', __FILE__));
			jeedouino::ReStartBoardDemon($arduino_id, 0, $ModeleArduino);
		}
		if (isset($_GET['PORTINUSE']))
		{
			jeedouino::log( 'debug', $CALLBACK . __('Le port ', __FILE__) . $_GET['PORTINUSE'] . __(' est probablement utilisé. Nouvel essai en mode auto-découverte dans 7s.', __FILE__));
		}
		if (isset($_GET['PORTISUSED']))
		{
			jeedouino::log( 'debug', $CALLBACK . __('Le port ', __FILE__) . $_GET['PORTISUSED'] . __(' est peut-être utilisé. Nouvel essai dans 11s.', __FILE__));
		}
		if (isset($_GET['NOPORTFOUND']))
		{
			jeedouino::updateControlCmd($arduino_id, false);
			jeedouino::log( 'error', $CALLBACK . __('Impossible de trouver un port de libre automatiquement. Veuillez en choisir un autre.', __FILE__));
		}
		if (isset($_GET['PORTFOUND']))
		{
			jeedouino::log( 'debug', $CALLBACK . __('Un port libre (', __FILE__) . $_GET['PORTFOUND'] . __(') est disponible. Je met à jour l\'équipement.', __FILE__));
			$eqLogic->setConfiguration('ipPort', $_GET['PORTFOUND']);
			$eqLogic->setConfiguration('PortDemon', $_GET['PORTFOUND']);
			$eqLogic->save(true);
			if ($eqLogic->getConfiguration('alone') == '1') jeedouino::SendPRM($eqLogic); // on renvoie la config
		}
		// Informations fournies par les Arduinos/Esp
		if (isset($_GET['ASK']))
		{
			//jeedouino::ConfigureAllPinsValues($arduino_id);

			if (($board=='arduino') or ($board=='esp'))	// Petite verif : seulement Arduinos / ESP (pour l'instant)
			{
				$message='';
				foreach ($Arduino_pins as $pins_id => $pin_datas)
				{
					$cmd = $eqLogic->getCmd(null, 'ID'.$pins_id.'i');	// Uniquement les commandes info (retour d'etat) liées aux commandes action
					if (is_object($cmd))
					{
						$myMode=$cmd->getConfiguration('modePIN');
						//jeedouino::log( 'debug','myMode (pins_id : '.$pins_id.') '.$myMode);
						switch ($myMode)
						{
							case 'output':
							case 'switch':
							case 'output_pulse':
							case 'low_relais':
							case 'high_pulse':
							case 'high_relais':
							case 'low_pulse':
								if ($cmd->getDisplay('invertBinare')) $message.=sprintf("%01s", 1-$cmd->getConfiguration('value'));
								else $message.=sprintf("%01s", $cmd->getConfiguration('value'));
								break;
							default:
								$message.='.';
								break;
						}
					}
					else $message.='.';
				}
				if ($message != '' and config::byKey('SENDING_'.$arduino_id, 'jeedouino', 0) == 0)
				{
					config::save('SENDING_'.$arduino_id, 1, 'jeedouino');
					jeedouino::log( 'debug', __("Pause de 4s pour laisser l'arduino finir sa communication de démarrage.", __FILE__));
					sleep(4);
					$message='S'.$message.'F';
					jeedouino::log( 'debug', __('Envoi les valeurs des pins suite à la demande de la carte (Reboot?) ', __FILE__) . $BOARDNAME . '- Message : ' . $message);
					$reponse=jeedouino::SendToBoard($arduino_id,$message);
					if ($reponse!='SFOK') jeedouino::log( 'debug', __('ERREUR CONFIGURATION ConfigureAllPinsValues  ', __FILE__) . $BOARDNAME . '- Réponse :' . $reponse);
					config::save('SENDING_'.$arduino_id, 0, 'jeedouino');
				}
			}
		}
		if (isset($_GET['ipwifi']))
		{
			$eqLogic->setConfiguration('iparduino',$_GET['ipwifi']);  // On sauve L'IP fournie par l'ESP
			$eqLogic->save(true);
			jeedouino::log( 'debug', $BOARDNAME . __(' vient d\'envoyer son adresse IP : ', __FILE__) . $_GET['ipwifi']);
		}
		if (isset($_GET['PINMODE']))
		{
			//$PinMode = config::byKey($arduino_id . '_PinMode', 'jeedouino', 'none');
			$PinMode = jeedouino::GetPinMode($arduino_id);
			if ($PinMode != '')// and config::byKey($arduino_id . '-ForceStart', 'jeedouino', '0') == '0')
			{
				switch ($ModeleArduino)
				{
					case 'piGPIO26':
						$PinMode .= '..............';
					case 'piGPIO40':
					case 'piface':
					case 'piPlus':
						$PinMode = 'ConfigurePins=' . $PinMode;
						jeedouino::log( 'debug', $CALLBACK . __('Le démon réclame l\'envoi de la configuration des pins.', __FILE__));
						$DemonTypeF = jeedouino::FilterDemon($ModeleArduino);
						if ($DemonTypeF == 'USB' ) $PinMode = 'USB=' . $PinMode;
						$reponse = jeedouino::SendToBoardDemon($arduino_id, $PinMode, $DemonTypeF);
						if ($reponse != 'COK') jeedouino::log( 'debug', __('Erreur d\'envoi de la configuration des pins sur l\'équipement ', __FILE__) . $arduino_id.' ( ' . $eqLogic->getName() . ' ) - Réponse :' . $reponse);
						else
						{
							config::save('NODEP_' . $arduino_id, '', 'jeedouino');
							jeedouino::updateControlCmd($arduino_id, true);
						}
						jeedouino::log( 'debug', __('Envoi de ', __FILE__) . $PinMode . __(' - Réponse : ', __FILE__) . $reponse);
						break;
					default:
						$PinMode = 'C' . $PinMode . 'C';
						jeedouino::log( 'debug', $CALLBACK . __('L\'' . $board . ' réclame l\'envoi de la configuration des pins.', __FILE__));
						$reponse = jeedouino::SendToArduino($arduino_id, $PinMode, 'PinMode', 'COK');
						if ($reponse != 'NOK') $reponse = jeedouino::SendToArduino($arduino_id, 'B' . config::byKey($arduino_id . '_choix_boot', 'jeedouino', '2') . 'M', 'BootMode', 'BMOK');
						else jeedouino::log( 'debug', __('Erreur d\'envoi de la configuration des pins sur l\'équipement ', __FILE__) . $arduino_id.' ( ' . $eqLogic->getName() . ' ) - Réponse :' . $reponse);
						if (!$usb)
						{
							if ($reponse != 'NOK') $reponse = jeedouino::SendToArduino($arduino_id, 'E' . $arduino_id . 'Q', 'BoardEQ', 'EOK');
							if ($reponse != 'NOK') $reponse = jeedouino::SendToArduino($arduino_id, 'I' . jeedouino::GetJeedomIP() . 'P', 'BoardIP', 'IPOK');
						}
				}
				config::save($arduino_id . '_PinMode', $PinMode, 'jeedouino');
			}
		}
		//else // Informations fournies par tous
		if (true)
		{
			foreach ($eqLogic->getCmd('info') as $cmd)
			{
				//jeedouino::log( 'debug','>>> Liste $cmd = '. json_encode(utils::o2a($cmd)));
				if (is_object($cmd))
				{
					$pins_id = $cmd->getConfiguration('pins_id');
					//jeedouino::log( 'debug','$pins_id = '.$pins_id.' - Liste $_GET = '. json_encode($_GET));

					// Specifique Analog to Digital pins OUT - Etat
					if ($ard328 and $cmd->getConfiguration('modePIN')!='analog_input')
					{
						if ($pins_id<100 and $pins_id>53) $pins_id -= 40;
						elseif ($pins_id<1100 and $pins_id>1053) $pins_id -= 40;
						elseif ($pins_id<2100 and $pins_id>2053) $pins_id -= 40;
						elseif ($pins_id<3100 and $pins_id>3053) $pins_id -= 40;
					}

					// Specifique ESPxx entree analogique
					if ( $board == 'esp' and $cmd->getConfiguration('modePIN') == 'analog_input') $pins_id += 40;

					if (array_key_exists($pins_id, $_GET))
					{
						$_board = strtoupper($board);
						$recu = trim($_GET[$pins_id]);
						$MaJ = true;
						$MaJErr = true;
						$MaJLog = $CALLBACK . '** NoOp ** - ' . $BOARDNAME . ' - Pin n° ' . $pins_id . ' = ' . $recu;
						if ($recu == '')
						{
							$MaJ = false;
							$MaJLog = $CALLBACK . __(' a envoyé une valeur vide de la Pin n° ', __FILE__) . $pins_id;
						}
						elseif (substr($cmd->getConfiguration('modePIN'), 0, 3) == 'dht')
						{
							$recu = round($recu / 100, 1);	// 1 chiffre apres la virgule.
							if ($recu > 255 and !$_ProbeNoLog)
							{
								$MaJLog = $CALLBACK . __('La sonde DHT de la Pin n° ', __FILE__) . ($pins_id>=1000?($pins_id-1000).' (Humidité)':$pins_id.' (Température)').__(' a envoyée une valeur erronée : ', __FILE__).$recu . __('. Veuillez vérifier votre sonde et/ou son alimentation. ', __FILE__);
								$MaJ = false;
							}
						}
						elseif (substr($cmd->getConfiguration('modePIN'), 0, 4) == 'ds18')
						{
							$DSkey = 'DS18list_' . $pins_id;
							if (isset($_GET['DS18list'])) $DSkey = 'DS18list'; // palliatif temporaire
							if (isset($_GET[$DSkey]))
							{
								$MaJ = false;
								$MaJErr = false;
								$_GET[$DSkey] = strtoupper(str_replace(',}', '}', $_GET[$DSkey])); // filtre pour arduino
								$DS18list = json_decode($_GET[$DSkey], true);

								foreach($DS18list as $id) $cmd->setConfiguration($id, null); // cleaning
								if ($cmd->getConfiguration('ds18id', '') == '')
								{
									list($firstID) = array_keys($DS18list);
									$cmd->setConfiguration('ds18id', $firstID);
									$cmd->setConfiguration($firstID, 'set');
									$cmd->save();
								}
								else
								{
									$firstID = $cmd->getConfiguration('ds18id', '');
									$cmd->setConfiguration($firstID, 'set');
									$cmd->save();
								}
								$pins_start_id = $pins_id + 2000;
								foreach($DS18list as $id => $temp)
								{
									//$pins_start_id++;
									$temp = round($temp / 100, 2);
									$_cmd = $eqLogic->searchCmdByConfiguration($id);
									if (is_array($_cmd) and !empty($_cmd)) $_cmd = $_cmd[0];
									if (!is_object($_cmd))
									{
										$_cmd = new jeedouinoCmd();
										$_cmd->setName($pins_id . '_' . 'ds18_' . substr($id, -10));
										$_cmd->setLogicalId('ID' . $pins_id . 'a');
										$_cmd->setConfiguration('modePIN', 'ds18b20');
										$_cmd->setConfiguration('pins_id', $pins_start_id);
										$_cmd->setConfiguration($id, 'set');
										$_cmd->setConfiguration('ds18id', $id);
										$_cmd->setUnite('°C');
										$_cmd->setOrder($pins_id);
										$_cmd->setSubType('numeric');
										$_cmd->setType('info');
										$_cmd->setEqLogic_id($eqLogic->getId());
										$_cmd->setGeneric_type('TEMPERATURE');
										$_cmd->save();
										$_cmd->setValue($_cmd->getId());
									}
									if ((($temp < -55) || ($temp > 125)) and !$_ProbeNoLog)
									{
										jeedouino::log( 'error', $CALLBACK . __('La sonde DS18x20 (', __FILE__) . $id . __(') de la Pin n° ', __FILE__) . $pins_id . __(' a envoyée une valeur erronée : ', __FILE__) . $temp . __(' . Veuillez vérifier votre sonde et/ou son alimentation. ', __FILE__));
									}
									else
									{
										$_cmd->setCollectDate(date('Y-m-d H:i:s'));
										$_cmd->event($temp);
										$_cmd->setConfiguration('value', $temp);
										$_cmd->save();
										jeedouino::log('debug', $CALLBACK . __('Lecture Sonde DS18x20 (', __FILE__) . $id . ') Pin n° ' . $pins_id . ' = ' . $temp);
									}
								}
								$eqLogic->refreshWidget();
							}
							else
							{
								if ($recu > 32767) $recu = -(($recu ^ 0xFFFF) + 1);
								$recu = round($recu / 100, 2);
								if ((($temp < -55) || ($temp > 125)) and !$_ProbeNoLog)
								{
									$MaJLog = $CALLBACK . __('La sonde DS18x20 de la Pin n° ', __FILE__) . $pins_id . __(' a envoyée une valeur erronée : ', __FILE__) . $recu . __(' . Veuillez vérifier votre sonde et/ou son alimentation. ', __FILE__);
									$MaJ = false;
								}
							}
						}
						elseif ($cmd->getConfiguration('modePIN') == 'compteur_pullup' or $cmd->getConfiguration('modePIN') == 'compteur_pulldown')
						{
							$value = $cmd->getConfiguration('value');	// En cas de mauvais reboot d'une carte, evite le renvoi d'une valeur de cpt infrieure (souvent 0))
							$RSTvalue = $cmd->getConfiguration('RSTvalue');
							if ($recu < ($RSTvalue / 2) and $recu > 0)
							{
								$message = $CALLBACK . __('La valeur de comptage reçue (', __FILE__) . $recu . __(') est inférieure à la valeur déjà connue (', __FILE__) . $RSTvalue . __('), est-ce voulu ?', __FILE__);
								jeedouino::logAlert('debug', 'warning', $message);
								$recu = $RSTvalue;
							}
							elseif ($recu == 0) $recu = $RSTvalue;
							$cmd->setConfiguration('RSTvalue', $recu);
							jeedouino::log('debug', $CALLBACK .  __('Valeur compteur sur Pin n° ', __FILE__) . $pins_id . ' = ' . $recu . ' ( +' . ($recu - $RSTvalue) . ' )');
						}
						if ($MaJ)
						{
							if ($cmd->getDisplay('invertBinare'))
							{
								$recu = 1 - $recu;
								jeedouino::log( 'debug',$CALLBACK . __(' *** Valeur inversée demandée pour la Pin n° ', __FILE__) . $pins_id . ' = ' . $recu);
							}
							$cmd->setCollectDate(date('Y-m-d H:i:s'));
							$cmd->event($recu);
							$cmd->setConfiguration('value', $recu);
							$cmd->save();
							$eqLogic->refreshWidget();

							$probeMSG = '';
							$modepin = str_replace('80b', '80', $cmd->getConfiguration('modePIN'));
							$i2c = ' (i2c x76) ';
							if ($modepin != $cmd->getConfiguration('modePIN')) $i2c = ' (i2c x77) ';
							$modepin = strtoupper($modepin);
							switch($cmd->getConfiguration('modePIN'))
							{
								case 'dht11':
								case 'dht21':
								case 'dht22':
								case 'bmp180':
									$i2c = '';
								case 'bmp280':
								case 'bmp280b':
								case 'bme280':
								case 'bme280b':
								case 'bme680':
								case 'bme680b':
									$probeMSG = __('Lecture Sonde ', __FILE__) . $modepin . $i2c . __(' (Température) ', __FILE__);
									break;
								case 'dht11_h':
								case 'dht21_h':
								case 'dht22_h':
									$i2c = '';
								case 'bme280_h':
								case 'bme280b_h':
								case 'bme680_h':
								case 'bme680b_h':
									$probeMSG = __('Lecture Sonde ', __FILE__) . $modepin . $i2c . __(' (Humidité) ', __FILE__);
									break;
								case 'bmp180_p':
									$i2c = '';
								case 'bmp280_p':
								case 'bmp280b_p':
								case 'bme280_p':
								case 'bme280b_p':
								case 'bme680_p':
								case 'bme680b_p':
									$probeMSG = __('Lecture Sonde ', __FILE__) . $modepin . $i2c . __(' (Pression) ', __FILE__);
									break;
								case 'bme680_g':
								case 'bme680b_g':
									$probeMSG = __('Lecture Sonde ', __FILE__) . $modepin . $i2c . __(' (Gas) ', __FILE__);
									break;
							}
							jeedouino::log('debug', $CALLBACK . $probeMSG . 'Pin n° ' . $pins_id . ' = ' . $recu);
						}
						else
						{
							if ($MaJErr) jeedouino::log( 'error', $MaJLog);
						}

					}
					elseif (array_key_exists('IN_' . $pins_id, $_GET))
					{
						$recu = $_GET['IN_' . $pins_id];
						if (substr($cmd->getConfiguration('modePIN'), 0, 5) == 'input')
						{
							$cmd->setCollectDate(date('Y-m-d H:i:s'));
							$cmd->event($recu);
							$cmd->setConfiguration('value',$recu);
							//$cmd->save();
							$eqLogic->refreshWidget();
							jeedouino::log( 'debug',$CALLBACK . 'Pin n° '.$pins_id.' = '.$recu);
						}
					}
					elseif (array_key_exists('CPT_' . $pins_id, $_GET))
					{
						if ($cmd->getConfiguration('modePIN') == 'compteur_pullup' or $cmd->getConfiguration('modePIN') == 'compteur_pulldown') // uniquement les valeurs de compteur
						{
							$value=$cmd->getConfiguration('value');
							list(,$board) = jeedouino::GetPinsByBoard($arduino_id);

							jeedouino::log( 'debug',$CALLBACK . __('Compteur sur pin n° ', __FILE__) . $pins_id . __(' - Valeur réclamée : ', __FILE__) . $value);
							$log_txt = __('Pin Compteur - Envoi de la dernière valeur connue suite à la demande de la carte (Reboot?)  ', __FILE__) . $BOARDNAME . '- Message : ';

							switch ($board)
							{
								case 'arduino':
								case 'esp':
									sleep(2);
									$message = 'S' . sprintf("%02s", $pins_id) . $value . 'C';
									jeedouino::log( 'debug', $log_txt . $message);
									$reponse = jeedouino::SendToBoard($arduino_id, $message);
									break;
								case 'piface':
								case 'gpio':
								case 'piplus':
									$message  = 'SetCPT=' . $pins_id;
									$message .= '&ValCPT=' . $value;
									jeedouino::log( 'debug', $log_txt . $message);
									$reponse = jeedouino::SendToBoardDemon($arduino_id, $message, $board);
									break;
							}
							if ($reponse!='SCOK') jeedouino::log( 'error', __('ERREUR ENVOI GetCompteurPinValue ', __FILE__) . $BOARDNAME . '- Réponse :' . $reponse);
						}
					}
				}
			}
		}

	}
	else jeedouino::log( 'error', __('CALLBACK - L\'équipement ID ', __FILE__) . $arduino_id . __(' est introuvable.', __FILE__));
}
else jeedouino::log( 'error', __('CALLBACK - ID de l\'équipement non défini.', __FILE__));
?>

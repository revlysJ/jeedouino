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
		config::save('REP_'.$arduino_id, '', 'jeedouino'); 	// Pour Double vérif
		if (isset($_GET['REP'])) config::save('REP_'.$arduino_id, $_GET['REP'], 'jeedouino'); 	// Pour Double vérif

		list($Arduino_pins,$board,$usb) = jeedouino::GetPinsByBoard($arduino_id);

		$BOARDNAME = 'Equipement ' . strtoupper($eqLogic->getName()) . ' (eqID: '.$arduino_id.') ';
		$CALLBACK = 'CALLBACK - ' . $BOARDNAME . '- ';

		// Cas de la téléinfo recue, on tente de l'envoyer au plugin teleinfo
		if (isset($_GET['ADCO']))
		{
			jeedouino::log( 'debug', $CALLBACK . __('vient d\'envoyer une trame téléinfo. J\'essaye de la transmettre au plugin adapté.', __FILE__));
			if (method_exists('teleinfo', 'createFromDef'))
			{
				$ApiKey = config::byKey('api');
				if ($ApiKey == '')  jeedouino::log( 'error', $CALLBACK . __('Impossible de trouver la clé API de votre Jeedom.', __FILE__));
				else
				{
					// Traitement de la trame
					$message = '';
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
								$message .= '&' . $champs[0] . '=' . $champs[1];
								jeedouino::log( 'debug', $CALLBACK . 'Champ compris :' . $champs[0] . ' = ' . $champs[1]);
							}
							else jeedouino::log( 'debug', $CALLBACK . 'Etiquette non trouvée :' . json_encode($champs));
						}
						else jeedouino::log( 'debug', $CALLBACK . 'Champ non compris :' . json_encode($champs));
					}

					// Envoi le tout au plugin Téléinfo
					$http_ = trim(config::byKey('internalProtocol'));
					if ($http_ == '' ) $http_ = 'http://';
					$JeedomIP = jeedouino::GetJeedomIP();
					$JeedomPort = jeedouino::GetJeedomPort();
					$JeedomCPL =jeedouino::GetJeedomComplement();
					$url = $http_ . $JeedomIP . ':' . $JeedomPort . $JeedomCPL . '/plugins/teleinfo/core/php/jeeTeleinfo.php?api=' . $ApiKey . $message;
					jeedouino::log( 'debug', 'Appel de : ' . $url);
					return trim(@file_get_contents($url));
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
			$message = __('Dépendances ', __FILE__) . ucfirst(strtolower($_GET['NODEP'])) . __(' introuvables. Veuillez les reinstaller.' , __FILE__);
			event::add('jeedom::error', array(
				'level' => 'warning',
				'page' => 'jeedouino',
				'message' => $message
				));
			jeedouino::log('error', $message);
		}
		if (isset($_GET['PINGME']))
		{
			if ($eqLogic->getIsEnable() == 0) jeedouino::StopBoardDemon($arduino_id, 0, $ModeleArduino);
			jeedouino::log( 'debug', $CALLBACK . __('Le 1er thread du démon demande un test PING ...', __FILE__));
			$result = jeedouino::StatusBoardDemon($arduino_id, 1, $ModeleArduino); // Force le cache
		}
		if (isset($_GET['THREADSDEAD']))
		{
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
					jeedouino::log( 'debug', __("Pause de 4s pour laisser l'arduino finir de communiquer avec le démon qui vient de demarrer", __FILE__));
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
			$PinMode = config::byKey($arduino_id . '_PinMode', 'jeedouino', 'none');
			if ($PinMode != 'none')
			{
				jeedouino::log( 'debug', $CALLBACK . __('Le démon réclame l\'envoi du mode des pins.', __FILE__));
				$DemonTypeF = jeedouino::FilterDemon($ModeleArduino);
				if ($DemonTypeF == 'USB' ) $PinMode = 'USB=' . $PinMode;
				$reponse = jeedouino::SendToBoardDemon($arduino_id, $PinMode, $DemonTypeF);
				if ($reponse != 'COK') jeedouino::log( 'debug', __('Erreur d\'envoi de la configuration des pins sur l\'équipement ', __FILE__) . $arduino_id.' ( ' . $eqLogic->getName() . ' ) - Réponse :' . $reponse);
				else
				{
					config::save('NODEP_' . $arduino_id, '', 'jeedouino');
					config::byKey($arduino_id . '_' . $DemonTypeF . 'DaemonState', 'jeedouino', true);
				}
				jeedouino::log( 'debug', __('Envoi de ', __FILE__) . $PinMode . __(' - Réponse : ', __FILE__) . $reponse);
			}
		}
		else // Informations fournies par tous
		{
			foreach ($eqLogic->getCmd('info') as $cmd)
			{
				if (is_object($cmd))
				{
					$pins_id = $cmd->getConfiguration('pins_id');
					//jeedouino::log( 'debug','$pins_id = '.$pins_id.' - Liste $_GET = '. json_encode($_GET));

					// Specifique Analog to Digital pins OUT - Etat
					if ($ard328 and $cmd->getConfiguration('modePIN')!='analog_input')
					{
						if ($pins_id<100 and $pins_id>53) $pins_id -= 40;
						elseif ($pins_id<1100 and $pins_id>1053) $pins_id -= 40;
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
							if ($recu > 255)	// valeur arbitraire
							{
								$MaJLog = $CALLBACK . __('La sonde DHT de la Pin n° ', __FILE__) . ($pins_id>=1000?($pins_id-1000).' (Humidité)':$pins_id.' (Température)').__(' a envoyée une valeur erronée : ', __FILE__).$recu . __('. Veuillez vérifier votre sonde et/ou son alimentation. ', __FILE__);
								$MaJ = false;
							}
						}
						elseif (substr($cmd->getConfiguration('modePIN'), 0, 4) == 'ds18')
						{
							if (isset($_GET['DS18list']))
							{
								$MaJ = false;
								$MaJErr = false;
								$_GET['DS18list'] = strtoupper(str_replace(',}', '}', $_GET['DS18list'])); // filtre pour arduino
								$DS18list = json_decode($_GET['DS18list'], true);
								list($firstID) = array_keys($DS18list);
								if ($cmd->getConfiguration('ds18id', '') == '' or $cmd->getConfiguration($firstID, '') == '')
								{
									$cmd->setConfiguration('ds18id', $firstID);
									$cmd->setConfiguration($firstID, 'set');
									$cmd->save();
								}
								//unset($DS18list[$firstID]);
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
										$_cmd->setName($pins_id . '_' . 'ds18b20_' . substr($id, -7));
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
									if (($temp < -55) || ($temp > 125))
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
								if (($recu < -55) || ($recu > 125))
								{
									$MaJLog = $CALLBACK . __('La sonde DS18x20 de la Pin n° ', __FILE__) . $pins_id . __(' a envoyée une valeur erronée : ', __FILE__) . $recu . __(' . Veuillez vérifier votre sonde et/ou son alimentation. ', __FILE__);
									$MaJ = false;
								}
							}
						}
						elseif ($cmd->getConfiguration('modePIN') == 'compteur_pullup')
						{
							$value = $cmd->getConfiguration('value');	// En cas de mauvais reboot d'une carte, evite le renvoi d'une valeur de cpt infrieure (souvent 0))
							$RSTvalue = $cmd->getConfiguration('RSTvalue');
							if ($recu < $RSTvalue) $recu = $RSTvalue;
							$cmd->setConfiguration('RSTvalue', $recu);
							jeedouino::log('debug', $CALLBACK . 'RSTvalue Pin n° ' . $pins_id . ' = ' . $recu);
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
							jeedouino::log('debug', $CALLBACK . 'Pin n° ' . $pins_id . ' = ' . $recu);
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
						if ($cmd->getConfiguration('modePIN') == 'compteur_pullup') // uniquement les valeurs de compteur
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

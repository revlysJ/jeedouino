<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

// TODO : Verifier l'origine de l'appel
jeedouino::log( 'debug','CALLBACK - Requête reçue : ?'.$_SERVER['QUERY_STRING']);
if (isset($_GET['BoardEQ']))
{
	$arduino_id = trim($_GET['BoardEQ']);
	if ($arduino_id == '' or $arduino_id == null or $arduino_id == 0)
	{
		jeedouino::log( 'error', 'CALLBACK - BoardEQ non défini : *' . $arduino_id . '*');
		return;
	}
	$eqLogic = eqLogic::byId($arduino_id);

	if ($eqLogic!==false and $eqLogic!==null)
	{
		// paliatif manque lastCommunication
		config::save('lastCommunication'.$arduino_id, date('Y-m-d H:i:s'), 'jeedouino');

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

		$BOARDNAME = strtoupper($eqLogic->getName()) . ' eqID ( '.$arduino_id.' ) ';
		$CALLBACK = 'CALLBACK - ' . $BOARDNAME . '- ';

		// Cas de la téléinfo recue, on tente de l'envoyer au plugin teleinfo
		if (isset($_GET['ADCO']))
		{
			jeedouino::log( 'debug', $CALLBACK . 'vient d\'envoyer une trame téléinfo. J\'essaye de la transmette au plugin adapté.');
			if (method_exists('teleinfo', 'createFromDef'))
			{
				$ApiKey = config::byKey('jeeNetwork::master::apikey');
				if ($ApiKey == '') $ApiKey = config::byKey('api');
				if ($ApiKey == '')  jeedouino::log( 'error', $CALLBACK . 'Impossible de trouver la clé API de votre Jeedom.');
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
			else jeedouino::log( 'error', $CALLBACK . ' - Impossible de trouver le plugin téléinfo.');
			return;
		}
		// Informations fournies par les démons
		if (isset($_GET['NODEP']))
		{
			$message = __('Dépendances ' . ucfirst(strtolower($_GET['NODEP'])) . ' introuvables. Veuillez les reinstaller.' , __FILE__);
			event::add('jeedom::error', array(
				'level' => 'warning',
				'page' => 'jeedouino',
				'message' => $message
				));
			jeedouino::log( 'error', $message);
		}
		if (isset($_GET['PINGME']))
		{
			jeedouino::log( 'debug', $CALLBACK . 'Le 1er thread du démon demande un test PING ...');
			$result = jeedouino::StatusBoardDemon($arduino_id, 0, $ModeleArduino);
		}
		if (isset($_GET['THREADSDEAD']))
		{
			jeedouino::log( 'error', $CALLBACK . 'Les threads du démon sont hs. Tentative de redémarrage du démon en cours...');
			jeedouino::ReStartBoardDemon($arduino_id, 0, $ModeleArduino);
		}
		if (isset($_GET['PORTINUSE']))
		{
			jeedouino::log( 'debug', $CALLBACK . 'Le port ' . $_GET['PORTINUSE'] . ' est probablement utilisé. Nouvel essai en mode auto-découverte dans 7s.');
		}
		if (isset($_GET['PORTISUSED']))
		{
			jeedouino::log( 'debug', $CALLBACK . 'Le port ' . $_GET['PORTISUSED'] . ' est peut-être utilisé. Nouvel essai dans 11s.');
		}
		if (isset($_GET['NOPORTFOUND']))
		{
			jeedouino::log( 'error', $CALLBACK . 'Impossible de trouver un port de libre automatiquement. Veuillez en choisir un autre.');
		}
		if (isset($_GET['PORTFOUND']))
		{
			jeedouino::log( 'debug', $CALLBACK . 'Un port libre (' . $_GET['PORTFOUND'] . ') est disponible. Je met à jour l\'équipement.');
			$eqLogic->setConfiguration('ipPort', $_GET['PORTFOUND']);
			$eqLogic->setConfiguration('PortDemon', $_GET['PORTFOUND']);
			$eqLogic->save(true);
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
				if ($message!='')
				{
					$message='S'.$message.'F';
					jeedouino::log( 'debug','Envoi des valeurs des pins suite à la demande de la carte (Reboot?) '.$BOARDNAME.'- Message : '. $message);
					$reponse=jeedouino::SendToBoard($arduino_id,$message);
					if ($reponse!='SFOK') jeedouino::log( 'error','ERREUR CONFIGURATION ConfigureAllPinsValues  '.$BOARDNAME.'- Réponse :'.$reponse);
				}
			}
		}
		if (isset($_GET['ipwifi']))
		{
			$eqLogic->setConfiguration('iparduino',$_GET['ipwifi']);  // On sauve L'IP fournie par l'ESP
			$eqLogic->save(true);
			jeedouino::log( 'debug',$BOARDNAME.' vient d\'envoyer son adresse IP : '.$_GET['ipwifi']);
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
						$MaJLog = $CALLBACK . '** NoOp **-  ' . $BOARDNAME . '- Pin n° ' . $pins_id . ' = ' . $recu;
						if ($recu=='')
						{
							$MaJ = false;
							$MaJLog = $CALLBACK . ' a envoyé une valeur vide de la Pin n° '.$pins_id;
						}

						if (substr($cmd->getConfiguration('modePIN'), 0, 3) == 'dht')
						{
							$recu = round($recu/100,1);	// 1 chiffre apres la virgule.
							if ($recu>255)	// valeur arbitraire
							{
								$MaJLog = $CALLBACK . 'La sonde DHT de la Pin n° '.($pins_id>=1000?($pins_id-1000).' (Humidité)':$pins_id.' (Température)').' a envoyée une valeur erronée : '.$recu . '. Veuillez vérifier votre sonde et/ou son alimentation. ';
								$MaJ = false;
							}
						}
						if (substr($cmd->getConfiguration('modePIN'), 0, 4) == 'ds18')
						{
							if ($recu>32767)
							{
								//jeedouino::log( 'debug',$CALLBACK . 'Pin n° '.$pins_id.' = '.decbin($recu));
								$recu = -(($recu ^ 0xFFFF)+1);
								//jeedouino::log( 'debug',$CALLBACK . 'Pin n° '.$pins_id.' = '.decbin($recu));
							}
							$recu = round($recu/16,1);
							if (($recu<-55) || ($recu>125))
							{
								$MaJLog = $CALLBACK . 'La sonde DS18x20 de la Pin n° '.$pins_id.' a envoyée une valeur erronée : '.$recu. '. Veuillez vérifier votre sonde et/ou son alimentation. ';
								$MaJ = false;
							}
						}
						if ($cmd->getConfiguration('modePIN') == 'compteur_pullup')
						{
							$value=$cmd->getConfiguration('value');	// En cas de mauvais reboot d'une carte, evite le renvoi d'une valeur de cpt infrieure (souvent 0))
							$RSTvalue=$cmd->getConfiguration('RSTvalue');
							if ($recu<$RSTvalue) $recu=$RSTvalue;
							//$cmd->setConfiguration('RSTvalue',$recu);
						}
						if ($MaJ)
						{
							if ($cmd->getDisplay('invertBinare'))
							{
								$recu = 1 - $recu;
								jeedouino::log( 'debug',$CALLBACK . ' *** Valeur inversée demandée pour la Pin n° ' . $pins_id . ' = ' . $recu);
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
							jeedouino::log( 'error',$MaJLog);
						}

					}
					elseif (array_key_exists('IN_'.$pins_id, $_GET))
					{
						$recu=$_GET['IN_'.$pins_id];
						if (substr($cmd->getConfiguration('modePIN'),0,5)=='input')
						{
							$cmd->setCollectDate(date('Y-m-d H:i:s'));
							$cmd->event($recu);
							$cmd->setConfiguration('value',$recu);
							//$cmd->save();
							$eqLogic->refreshWidget();
							jeedouino::log( 'debug',$CALLBACK . 'Pin n° '.$pins_id.' = '.$recu);
						}
					}
					elseif (array_key_exists('CPT_'.$pins_id, $_GET))
					{
						if ($cmd->getConfiguration('modePIN')=='compteur_pullup') // uniquement les valeurs de compteur
						{
							$value=$cmd->getConfiguration('value');
							list(,$board) = jeedouino::GetPinsByBoard($arduino_id);

							jeedouino::log( 'debug',$CALLBACK . 'Compteur sur pin n° '.$pins_id.' - Valeur réclamée : '.$value);
							$log_txt = 'Pin Compteur - Envoi de la dernière valeur connue suite à la demande de la carte (Reboot?)  '.$BOARDNAME.'- Message : ';

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
							if ($reponse!='SCOK') jeedouino::log( 'error','ERREUR ENVOI GetCompteurPinValue '.$BOARDNAME.'- Réponse :'.$reponse);
						}
					}
				}
			}
		}

	}
	else jeedouino::log( 'error','CALLBACK - L\'équipement ID '.$arduino_id.' est introuvable.');
}
else jeedouino::log( 'error','CALLBACK - BoardEQ (EqLogicID) non défini.');
?>

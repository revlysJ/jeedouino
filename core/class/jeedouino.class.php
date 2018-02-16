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

/******************************* Includes *******************************/
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
include_file('core', 'jeedouino', 'config', 'jeedouino');

class jeedouino extends eqLogic {
	/******************************* Attributs *******************************/
	/* Ajouter ici toutes vos variables propre à votre classe */

	/***************************** Methode static ****************************/
	// correctif perte jeeNetwork jeedom beta 2.5.0
	public static function Networkmode()
	{
		if (config::byKey('jeeNetwork::mode') == 'slave') return 'slave';
		return 'master';
	}
	
	// Fonction adaptée depuis https://github.com/jeedom/core/blob/master/core/class/jeedom.class.php
	public static function LsDevTty($search = 'ttyUSB*') 
	{
		$usbMapping = array();
		foreach (ls('/dev/', $search) as $usb) {
			$vendor = '';
			$model = '';
			foreach (explode("\n", shell_exec('/sbin/udevadm info --name=/dev/' . $usb . ' --query=all')) as $line) {
				if (strpos($line, 'E: ID_MODEL_FROM_DATABASE=') !== false) {
					$model = trim(str_replace(array('E: ID_MODEL_FROM_DATABASE=', '"'), '', $line));
				}
				if (strpos($line, 'E: ID_VENDOR_FROM_DATABASE=') !== false) {
					$vendor = trim(str_replace(array('E: ID_VENDOR_FROM_DATABASE=', '"'), '', $line));
				}
			}
			if ($vendor == '' && $model == '') {
				$usbMapping['/dev/' . $usb] = '/dev/' . $usb;
			} else {
				$name = trim($vendor . ' ' . $model);
				$number = 2;
				while (isset($usbMapping[$name])) {
					$name = trim($vendor . ' ' . $model . ' ' . $number);
					$number++;
				}
				$usbMapping[$name] = '/dev/' . $usb;
			}
		}
		return $usbMapping; 
	}
	public static function getUsbMapping($_name = '') 
	{
		$cache = cache::byKey('jeedouino::usbMapping');
		if (!is_json($cache->getValue()) || $_name == '') {
			$usbMapping = array();
			// on cherche par port USB et ACM
			$usbMapping = self::LsDevTty('ttyUSB*');			
			$usbMapping = array_merge($usbMapping , self::LsDevTty('ttyACM*'));
			// on cherche par Serial
			foreach (ls('/dev/serial/by-path/', '*') as $usb) {
				$vendor = '';
				$model = '';
				$devport = '';
				foreach (explode("\n", shell_exec('/sbin/udevadm info --name=/dev/serial/by-path/' . $usb . ' --query=all')) as $line) {
					if (strpos($line, 'E: ID_MODEL_FROM_DATABASE=') !== false) {
						$model = trim(str_replace(array('E: ID_MODEL_FROM_DATABASE=', '"'), '', $line));
					}
					if (strpos($line, 'E: ID_VENDOR_FROM_DATABASE=') !== false) {
						$vendor = trim(str_replace(array('E: ID_VENDOR_FROM_DATABASE=', '"'), '', $line));
					}
					if (strpos($line, 'E: DEVNAME=') !== false) {
						$devport = trim(str_replace(array('E: DEVNAME=', '"'), '', $line));
					}
				}
				
				if ($vendor == '' && $model == '') {
					$usbMapping['/dev/' . $usb] = $devport;
				} else {
					$name = trim($vendor . ' ' . $model);
					if (!isset($usbMapping[$name])) $usbMapping[$name] = $devport;
				}
		}
			if (file_exists('/dev/ttyAMA0')) {
				$usbMapping['Raspberry pi'] = '/dev/ttyAMA0';
			}
			if (file_exists('/dev/ttymxc0')) {
				$usbMapping['Jeedom board'] = '/dev/ttymxc0';
			}
			if (file_exists('/dev/S2')) {
				$usbMapping['Banana PI'] = '/dev/S2';
			}
			if (file_exists('/dev/ttyS2')) {
				$usbMapping['Banana PI (2)'] = '/dev/ttyS2';
			}
			if (file_exists('/dev/ttyS0')) {
				$usbMapping['Cubiboard'] = '/dev/ttyS0';
			}
			if (file_exists('/dev/ttyS3')) {
				$usbMapping['Orange PI'] = '/dev/ttyS3';
			}
			if (file_exists('/dev/ttyS1')) {
				$usbMapping['Odroid C2'] = '/dev/ttyS1';
			}

			cache::set('jeedouino::usbMapping', json_encode($usbMapping));
		} else {
			$usbMapping = json_decode($cache->getValue(), true);
		}
 		if ($_name != '') {
			if (isset($usbMapping[$_name])) {
				return $usbMapping[$_name];
			}
			$usbMapping = self::getUsbMapping('');
			if (isset($usbMapping[$_name])) {
				return $usbMapping[$_name];
			}
			if (file_exists($_name)) {
				return $_name;
			}
			return '';
		} 
		return $usbMapping;
	}

	public function getImage()
	{
		if (file_exists(dirname(__FILE__) . '/../../doc/images/jeedouino_' . $this->getConfiguration('arduino_board') . '.png'))
		{
			return 'plugins/jeedouino/doc/images/jeedouino_' . $this->getConfiguration('arduino_board') . '.png';
		}
		else
		{
			return 'plugins/jeedouino/doc/images/jeedouino_icon.png';
		}
	}
	public static function event()
	{
		$cmd = jeedouino::byId(init('id'));
		if (!is_object($cmd) || $cmd->getEqType() != 'jeedouino')
		{
			throw new Exception(__('Commande ID jeedouino inconnu, ou la commande n\'est pas de type jeedouino : ', __FILE__) . init('id'));
		}
		jeedouino::log( 'debug',' *** >>> $cmd->event(init(value))'.json_encode($cmd));
		$cmd->event(init('value'));
	}
	// Fonction exécutée automatiquement toutes les minutes par Jeedom
	public static function cron()
	{
		$CronStep=config::byKey('CronStep', 'jeedouino', 0);
		if ($CronStep==0) return;
		if ($CronStep>0) $CronStep++;
		if ($CronStep>4)
		{
			$CronStep = 0;
			self::StartAllDemons(config::byKey('CronStepArr', 'jeedouino', ''),true);
		}
		config::save('CronStep', $CronStep, 'jeedouino');
	}
	// Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
	public static function cron5()
	{
		$eqLogics = eqLogic::byType('jeedouino');
		foreach ($eqLogics as $eqLogic)
		{
			if ($eqLogic->getIsEnable() == 0) continue;
			$board_id = $eqLogic->getId();
			//jeedouino::log( 'debug','"' . $eqLogic->getName(true) . '" :: Auto_'. $board_id . ' = ' . config::byKey('Auto_'. $board_id, 'jeedouino', 'none') . ' :: _HasDemon = ' . config::byKey($board_id . '_HasDemon', 'jeedouino', 'none'));
			if (config::byKey($board_id . '_HasDemon', 'jeedouino', 0) and config::byKey('Auto_'. $board_id, 'jeedouino', 0))
			{
				jeedouino::log( 'debug','Vérification du démon pour "' . $eqLogic->getName(true) . '" (' . $board_id . ')');
				$ModeleArduino = $eqLogic->getConfiguration('arduino_board');
				if (!jeedouino::StatusBoardDemon($board_id, 0, $ModeleArduino)) jeedouino::StartBoardDemon($board_id, 0, $ModeleArduino);
			}
		}
	}

	/*
	// Fonction exécutée automatiquement toutes les heures par Jeedom
	public static function cronHourly() {

	}
	*/

	/*
	// Fonction exécutée automatiquement tous les jours par Jeedom
	public static function cronDayly() {

	}
	*/
	// Compatibilité Jeedom V2
	public static function dependancy_info()
	{
		$return = array();
		$return['log'] = 'jeedouino_update';
		$return['last_launch'] = '';
		$return['progress_file'] = '/tmp/dependances_jeedouino_en_cours';
		if (@shell_exec('ls /usr/lib/python2.*/dist-packages/serial/serialposix.py | wc -l') == 0)
		{
			$return['state'] = 'nok';
		}
		else
		{
			$return['state'] = 'ok';
			if (@shell_exec('ls /usr/local/lib/python2.*/dist-packages/Adafruit_DHT*.egg | wc -l') == 0) $return['state'] = 'nok';
		}
		if ($return['state'] == 'nok') $return['advice'] = 'Normal si ce n\'est pas sur un Raspberry PI.';

		// Cas du maitre qui n'est pas un RPI
		if (self::Networkmode() == 'master')
		{
			if (strpos(strtolower(config::byKey('hardware_name')),'rpi') === false)
			{
				$return['state'] = 'ok';
				log::add('jeedouino_update','info','ATTENTION ! Ce n\'est pas un Raspberry PI, les dépendances afférentes ne s(er)ont pas installées.');
			}
		}
		return $return;
	}
	public static function dependancy_install()
	{
		if (file_exists('/tmp/dependances_jeedouino_en_cours')) return;	// Install déja en cours

		log::remove('jeedouino_update');
		//exec('sudo apt-get install python-serial >> '.log::getPathToLog('jeedouino_update') . ' 2>&1 &');
		exec('sudo /bin/bash ' . dirname(__FILE__) . '/../../ressources/Jeedouino.sh >> '.log::getPathToLog('jeedouino_update') . ' 2>&1 &');

		log::add('jeedouino_update','info','Veuillez utiliser les boutons de la page Configuration du plugin pour les dépendances spécifiques. Merci');
	}

	public static function health()
	{
		$return = array();
		$return['test'] = 'Etat(s) démon(s)';
		$return['result'] ='OK';
		$return['advice'] = '';
		$return['state'] = true;

  		$eqLogics = eqLogic::byType('jeedouino');
 		foreach ($eqLogics as $eqLogic)
		{
			if ($eqLogic->getIsEnable() == 0) continue;
			$board_id=$eqLogic->getId();
			//jeedouino::log( 'debug',$board_id.'_HasDemon :  '.config::byKey($board_id.'_HasDemon', 'jeedouino', 0));
			if (config::byKey($board_id.'_HasDemon', 'jeedouino', 0))
			{
				//jeedouino::log( 'debug',$board_id.'_StatusDemon :  '.config::byKey($board_id.'_StatusDemon', 'jeedouino', 0));
 				if (!config::byKey($board_id.'_StatusDemon', 'jeedouino', 0))
				{
					$return['state'] = false;
					$return['result'] = 'NOK';
					$return['advice'] = 'Au moins un démon ne tourne pas. Voir la page de configuration du plugin.';
					break;
				}
			}
		}
		return array($return);
	}

	public static function deamon_info()
	{
		$return = array();
		$return['log'] = 'jeedouino';
		$return['state'] = 'ok';
		$return['launchable'] = 'ok';
		$return['last_launch'] = '';
		$return['auto'] = '0';
  		$eqLogics = eqLogic::byType('jeedouino');
		$n = 0;
 		foreach ($eqLogics as $eqLogic)
		{
			if ($eqLogic->getIsEnable() == 0) continue;
			$board_id = $eqLogic->getId();
			//jeedouino::log( 'debug',$board_id.'_HasDemon :  '.config::byKey($board_id.'_HasDemon', 'jeedouino', 0));
			if (config::byKey($board_id . '_HasDemon', 'jeedouino', 0))
			{
				$n++;
				//jeedouino::log( 'debug',$board_id.'_StatusDemon :  '.config::byKey($board_id.'_StatusDemon', 'jeedouino', 0));
 				if (!config::byKey($board_id . '_StatusDemon', 'jeedouino', 0))
				{
					$return['state'] = 'nok';
					break;
				}
			}
		}
		//if ($n == 0) jeedouino::log( 'debug','-=-= Aucun démon trouvé =-=-');
		return $return;
	}
	public static function deamon_start($_debug = false)
	{
		$eqLogics = eqLogic::byType('jeedouino');
		jeedouino::log( 'debug', '-=-= Suite demande Jeedom, démarrage global des démons =-=-');
		foreach ($eqLogics as $eqLogic)
		{
			if ($eqLogic->getIsEnable() == 0) continue;
			$board_id=$eqLogic->getId();
			if (config::byKey($board_id . '_HasDemon', 'jeedouino', 0))
			{
				jeedouino::log( 'debug','-=-= '.$board_id.' =-=-');
				list(,$board,$usb) = self::GetPinsByBoard($board_id);
				self::StartBoardDemon($board_id,0,$board);
				sleep(2);
			}

		}
		jeedouino::log( 'debug', '-=-= Fin du démarrage des démons =-=-');
	}
	public static function deamon_stop()
	{
 		$eqLogics = eqLogic::byType('jeedouino');
		jeedouino::log('debug', '-=-= Suite demande Jeedom, Arrêt global des démons =-=-');
		foreach ($eqLogics as $eqLogic)
		{
			$board_id = $eqLogic->getId();
			if (config::byKey($board_id . '_HasDemon', 'jeedouino', 0))
			{
				jeedouino::log( 'debug','-=-= ' . $board_id . ' =-=-');
				list(, $board, $usb) = self::GetPinsByBoard($board_id);
				self::StopBoardDemon($board_id, 0, $board);
				sleep(2);
			}
		}
		jeedouino::log( 'debug', '-=-= Fin de l\'arrêt des démons =-=-');
	}

	// fonction lancée au démarrage de Jeedom
	public static function start()
	{
		$EqLogicArr = config::byKey('EqLogicForStart', 'jeedouino', 'none');
		if ($EqLogicArr != 'none')
		{
			//$EqLogicArr = json_decode($EqLogicArr, true);
			if (self::Networkmode() == 'master')  self::StartAllDemons($EqLogicArr);
			elseif (class_exists ('jeeNetwork', false))
			{
				$jsonrpc = jeeNetwork::getJsonRpcMaster();
				if (!$jsonrpc->sendRequest('StartAllDemons', array('plugin'=>'jeedouino' ,'EqLogics' => $EqLogicArr)))
				{
				   throw new Exception($jsonrpc->getError(), $jsonrpc->getErrorCode());
				}
			}
		}
	}

	public static function log($log1,$log2)
	{
		// On nettoie le log de ses caracteres speciaux car il font planter l'affichage des logs dans jeedom
		//$log2 = filter_var($log2, FILTER_SANITIZE_STRING);
		if (config::byKey('ActiveLog', 'jeedouino', false)) log::add('jeedouino',$log1,$log2);
	}

	/*************************** Méthodes d'instance **************************/

	public function StartAllDemons($EqLogics, $StartNow=false)
	{
		if (($EqLogics!='') and is_array($EqLogics))
		{
			$CronStep=config::byKey('CronStep', 'jeedouino', 0);
			$CronStepArr=config::byKey('CronStepArr', 'jeedouino', '');
			if (($CronStepArr!='') and (!$StartNow))
			{
				$say_it = false;
				foreach ($EqLogics as $EqIDs)	// Au cas ou plusieurs esclaves jeedom redémarrent en même temps.
				{
					if (!in_array($EqIDs,$CronStepArr))
					{
						$CronStepArr[] = $EqIDs; // En cas d'envois multiple depuis l'esclave
						$say_it = true;
					}
				}
				$EqLogics = $CronStepArr;
				//$EqLogics=array_merge($CronStepArr,$EqLogics);	// Au cas ou plusieurs esclaves jeedom redémarrent en même temps.
				$CronStep=0;
				if ($say_it) jeedouino::log( 'debug','Un autre Jeedom avec démon(s) a redémarré aussi. Démarrage des démons repoussé., EqID :  '.json_encode($EqLogics));
			}
			if ($CronStep==0)
			{
				jeedouino::log( 'debug','Suite reboot Jeedom, démarrage des démons dans 4 min., EqID :  '.json_encode($EqLogics));
				config::save('CronStepArr',  json_encode($EqLogics), 'jeedouino');
				config::save('CronStep', 1, 'jeedouino');
				return;
			}
			config::save('CronStep', 0, 'jeedouino');
			config::save('CronStepArr', '', 'jeedouino');

			jeedouino::log( 'debug','Suite reboot Jeedom, démarrage des démons, EqID :  '.json_encode($EqLogics));
			config::save('StartDemons', 1, 'jeedouino');
			//sleep(2);
			foreach ($EqLogics as $eqLogic)
			{
				jeedouino::log( 'debug','-=-= '.$eqLogic.' =-=-');
				$my_board = eqLogic::byid($eqLogic);
				if (!is_object($my_board))
				{
					jeedouino::log( 'debug','L\'équipement ID '.$eqLogic.' n\'existe plus.');
					self::RemoveEqLogicForStart($eqLogic);	// On enleve l'eqLogic
					continue;
				}
				if ($my_board->getIsEnable() == 0)
				{
					jeedouino::log( 'debug','L\'équipement ID '.$eqLogic.' est désactivé.');
					continue;
				}
				list(, $board, $usb) = self::GetPinsByBoard($eqLogic);
				self::StartBoardDemon($eqLogic, 0 ,$board);
				sleep(2);
			}
			jeedouino::log( 'debug','-=-= Fin du démarrage des démons =-=-');
			config::save('StartDemons', 0, 'jeedouino');
		}
	}

	public function GetPinsByBoard($arduino_id)	// Renvoi la liste des pins suivant le type de carte
	{
		global $ArduinoMODEpins, $Arduino328pins, $ArduinoMEGApins, $ArduinoESPanalogPins;
		global $PifaceMODEpinsIN, $PifaceMODEpinsOUT, $Pifacepins;
		global $PiGPIOpins, $PiGPIO26pins, $PiGPIO40pins;
		global $ESP8266pins, $ESP01pins, $ESP07pins, $espMCU01pins, $ESP32pins;
		global $SonoffPow, $SonoffPowPins, $Sonoff4ch, $Sonoff4chPins;
		global $ElectroDragonSPDT, $ElectroDragonSPDTPins;
		global $PiPluspins, $PiPlus16pins;
		global $UserModePins;

		$my_arduino = eqLogic::byid($arduino_id);
		$ModeleArduino = $my_arduino->getConfiguration('arduino_board');
		$PortArduino = $my_arduino->getConfiguration('datasource');
		$Arduino_pins = '';
		$usb = false;
		$board = '';
		switch ($ModeleArduino)
		{
			case 'auno':
			case 'a2009':
			case 'anano':
			case 'auno':
				$Arduino_pins = $Arduino328pins;
				if ($PortArduino=='usbarduino') $usb=true;
				$board='arduino';
				break;
			case 'a1280':
			case 'a2560':
				$Arduino_pins = $ArduinoMEGApins;
				if ($PortArduino=='usbarduino') $usb=true;
				$board='arduino';
				break;
			case 'piface':
				$Arduino_pins = $Pifacepins;
				$board='piface';
				break;
			case 'piGPIO26':
				$Arduino_pins = $PiGPIO26pins;
				$board='gpio';
				break;
			case 'piGPIO40':
				$Arduino_pins = $PiGPIO40pins;
				$board='gpio';
				break;
			case 'piPlus':
				$Arduino_pins = $PiPlus16pins;
				$board='piplus';
				break;
			case 'esp01':
				$Arduino_pins = $ESP01pins;
				$board='esp';
				break;
			case 'esp07':
				$Arduino_pins = $ESP07pins;
				$board='esp';
				break;
			case 'espMCU01':
				$Arduino_pins = $espMCU01pins;
				$board='esp';
				break;
			case 'espsonoffpow':
				$Arduino_pins = $SonoffPowPins;
				$board='esp';
				break;
			case 'espsonoff4ch':
				$Arduino_pins = $Sonoff4chPins;
				$board='esp';
				break;
			case 'espElectroDragonSPDT':
				$Arduino_pins = $ElectroDragonSPDTPins;
				$board='esp';
				break;
			case 'esp32dev':
				$Arduino_pins = $ESP32pins;
				$board='esp';
				break;
			default:
				$Arduino_pins = '';
				$usb = false;
				$board = '';
			break;
		}

		// On ajoute les pins utilisateur
		if (($board == 'arduino') or ($board == 'esp'))
		{
			$UserPinsMax = $my_arduino->getConfiguration('UserPinsMax');
			if ($UserPinsMax < 0 or $UserPinsMax>100) $UserPinsMax = 0;
			$Arduino_pins = $Arduino_pins + jeedouino::GiveMeUserPins($UserPinsMax);
		}

		return array($Arduino_pins,$board,$usb);
	}

	public function ConfigurePinMode($ForceStart = true) 	// Paramétrage du mode des pins)
	{
	 	//$arduino_id=$this->getEqLogic_id();	// si appel depuis CMD
		$arduino_id=$this->getId();
		$my_arduino = eqLogic::byid($arduino_id);
		$ModeleArduino = $my_arduino->getConfiguration('arduino_board');
		$PortArduino = $my_arduino->getConfiguration('datasource');	 // usbarduino - rj45arduino
		$LocalArduino = $my_arduino->getConfiguration('arduinoport');   // usblocal - usbdeporté
		$portusbdeporte = $my_arduino->getConfiguration('portusbdeporte');   // portusbdeporté (jeeNetworkID_portUSB)
		$IPArduino = $my_arduino->getConfiguration('iparduino'); 		// IP réseau
		$ipPort = $my_arduino->getConfiguration('ipPort');	  // port réseau
		$PortID = $my_arduino->getConfiguration('PortID');	 // No de carte piface ( si plusieurs sur même RPI)

		//if ((($PortArduino=='rj45arduino') or ($PortArduino=='usbarduino')) and (($LocalArduino=='usblocal') or ($LocalArduino=='usbdeporte')))
		if (($ModeleArduino !='') and (($PortArduino=='rj45arduino') or ($PortArduino=='usbarduino')))
		{
			// ok une carte est définie
			$JeedouinoAlone = $my_arduino->getConfiguration('alone'); // Déja fait si Jeedouino sur un Rpi sans Jeedom.
			if (($PortArduino=='usbarduino') and ($JeedouinoAlone != '1'))// On va essayer de récupérer l'ip du jeedom sur lequel est branchée l'arduino en usb
			{
				$PortDemon = $my_arduino->getConfiguration('PortDemon');
				if ($LocalArduino=='usblocal')  // facile
				{
					$IPArduino = self::GetJeedomIP();
					$my_arduino->setConfiguration('iparduino',$IPArduino);  // On la sauve pour le démon
					$my_arduino->save(true);
					jeedouino::log( 'debug','Démon local - IPArduino ArduinoUsb (eqID '.$arduino_id.') : '.$IPArduino.':'.$PortDemon);
				}
				else	// Un peu plus long en déporté
				{
					if ($portusbdeporte=='none') return;	// pas la peine de continuer, le port usb de connection n'est pas choisi.
					if (!class_exists ('jeeNetwork', false)) return;
					$p=strpos($portusbdeporte,'_');
					if ($p) // Pas de vérification stricte car je ne veux pas de position à 0 non plus
					{
						$SlaveNetworkID=substr($portusbdeporte,0,$p);   // On recupère l'ID du jeeNetwork stocké via le formulaire du port usb
						$jeeNetwork = jeeNetwork::byId($SlaveNetworkID);
						$IPArduino=$jeeNetwork->getIp();
						$my_arduino->setConfiguration('iparduino',$IPArduino);  // On la sauve pour le démon
						$my_arduino->save(true);
						jeedouino::log( 'debug','Démon déporté - IPArduino ArduinoUsb (eqID '.$arduino_id.') : '.$IPArduino.':'.$PortDemon);
					}
					else
					{
						throw new Exception(__('Impossible de trouver l\'IP du démon déporté: ', __FILE__) .$portusbdeporte);
					}
				}
			}

			list($Arduino_pins,$board,$usb) = self::GetPinsByBoard($arduino_id);

			$PinMode='';
 			foreach ($Arduino_pins as $pins_id => $pin_datas)
			{
				if ( $pins_id >= 500 ) continue;
				$myPin = config::byKey($arduino_id . '_' . $pins_id, 'jeedouino', 'not_used');
				switch ($myPin)
				{
					// dispo : y
					case 'servo':
						$PinMode .= 'x';
					break;
					case 'bmp180':
						$PinMode .= 'r';
					break;
					case 'bp_input':
						$PinMode .= 'n';
					break;
					case 'bp_input_pullup':
						$PinMode .= 'q';
					break;
					case 'teleinfoRX':
						$PinMode.='j';
					break;
					case 'teleinfoTX':
						$PinMode.='k';
					break;
					case 'trigger':
						$PinMode.='t';
					break;
					case 'echo':
						$PinMode.='z';
					break;
					case 'input':
						$PinMode.='i';
					break;
					case 'input_pullup':
						$PinMode.='p';
					break;
					case 'dht11':
						$PinMode.='d';
					break;
					case 'dht21':
						$PinMode.='e';
					break;
					case 'dht22':
						$PinMode.='f';
					break;
					case 'ds18b20':
						$PinMode.='b';
					break;
					case 'pwm_input':
						$PinMode.='g';
					break;
					case 'analog_input':
						$PinMode.='a';
					break;
					case 'output':
						$PinMode.='o';
					break;
					case 'switch':
						$PinMode.='s';
					break;
					case 'compteur_pullup':
						$PinMode.='c';
					break;
					case 'low_relais':
						$PinMode.='l';
					break;
					case 'high_relais':
						$PinMode.='h';
					break;
					case 'output_pulse':
						$PinMode.='u';
					break;
					case 'low_pulse':
					case 'low_pulse_slide':
						$PinMode.='v';
					break;
					case 'high_pulse':
					case 'high_pulse_slide':
						$PinMode.='w';
					break;
					case 'pwm_output':
						$PinMode.='m';
					break;
					default:		// case 'not_used':
						$PinMode.='.';
					break;
				}
			}
			if ($PinMode!='')
			{
				switch ($board)
				{
					case 'arduino':
						if (!$usb) break;
					case 'piface':
					case 'piplus':
					case 'gpio':
						if ($ForceStart)
						{
							self::StartBoardDemon($arduino_id, 0, $ModeleArduino);   // on démarre le démon si nécéssaire.
							sleep(2);
						}
				}
				switch ($ModeleArduino)
				{
					case 'piGPIO26':
						$PinMode .= '..............'; // Complement pour le démon piGpio26 (il faut 40 caracteres )
					case 'piGPIO40':
						$BootMode='BootMode='.config::byKey($arduino_id.'_piGPIO_boot', 'jeedouino', '0');
						jeedouino::log( 'debug','Envoi de la configuration BootMode eqID ( '.$arduino_id.' ) '."BootMode : ". $BootMode);
						$reponse = self::SendToBoardDemon($arduino_id, $BootMode, $ModeleArduino);
						if ($reponse!='BMOK') jeedouino::log( 'debug', ucfirst($ModeleArduino) . ' - PB ENVOI CONFIGURATION BootMode eqID ( '.$arduino_id.' ) - Réponse :'.$reponse);
					case 'piface':
					case 'piPlus':
						$PinMode = 'ConfigurePins=' . $PinMode;
						config::save($arduino_id.'_PinMode', $PinMode, 'jeedouino');
						jeedouino::log( 'debug','Envoi de la nouvelle configuration des pins eqID ( ' . $arduino_id . ' ) ' . "PinMode : ". $PinMode);
						$reponse = self::SendToBoardDemon($arduino_id, $PinMode, $ModeleArduino);
						if ($reponse != 'COK') jeedouino::log( 'debug', ucfirst($ModeleArduino) . ' - PB ENVOI CONFIGURATION PinMode eqID ( '.$arduino_id.' ) - Réponse :'.$reponse);

						break;
					default:
						config::save('SENDING_'.$arduino_id, 1, 'jeedouino');
						$PinMode = 'C'.$PinMode.'C';
						config::save($arduino_id.'_PinMode', $PinMode, 'jeedouino');
						self::SendToArduino($arduino_id, $PinMode, 'PinMode', 'COK');
						self::SendToArduino($arduino_id, 'B' . config::byKey($arduino_id . '_choix_boot', 'jeedouino', '2') . 'M', 'BootMode', 'BMOK');
						if ($PortArduino=='rj45arduino')
						{
							self::SendToArduino($arduino_id, 'E' . $arduino_id . 'Q', 'BoardEQ', 'EOK');
							self::SendToArduino($arduino_id, 'I' . self::GetJeedomIP() . 'P', 'BoardIP', 'IPOK');
						}
						config::save('SENDING_'.$arduino_id, 0, 'jeedouino');
				}
			}
		}
		 jeedouino::log( 'debug','Fin de ConfigurePinMode()');
	}
	public function SendToArduino($arduino_id, $message, $keyword, $waitfor)
	{
		$my_arduino = eqLogic::byid($arduino_id);
		$name = $my_arduino->getName();
		$IPArduino = $my_arduino->getConfiguration('iparduino');
		$ipPort = $my_arduino->getConfiguration('ipPort');
		jeedouino::log( 'debug','Envoi de la configuration [ ' . $keyword . ' : ' . $message . ' ] à l\'équipement '.$arduino_id.' ( ' . $name . ' ) sur l\'IP : ' . $IPArduino . ':'. $ipPort );
		$reponse = self::SendToBoard($arduino_id, $message);
		if ($reponse != $waitfor) 
		{
			$waitforArr = array('COK' , 'PINGOK' , 'EOK' , 'IPOK' , 'SOK' , 'SCOK' , 'SFOK' , 'BMOK');
			if (in_array($reponse, $waitforArr)) jeedouino::log( 'debug', 'Réponse différée reçue de l\'équipement '.$arduino_id.' ( ' . $name . ' ) - Réponse :'.$reponse);
			else jeedouino::log( 'debug', 'PB ENVOI CONFIGURATION ' . $keyword . ' équipement '.$arduino_id.' ( ' . $name . ' ) - Réponse :'.$reponse);
		}
	}

	public function ConfigureAllPinsValues($arduino_id) 	// Renvoi l'état de toutes les pins à l'arduino sur demande après reboot.
	{
		$my_arduino = eqLogic::byid($arduino_id);
		$ModeleArduino = $my_arduino->getConfiguration('arduino_board');
		$PortArduino = $my_arduino->getConfiguration('datasource');
		$LocalArduino = $my_arduino->getConfiguration('arduinoport');
		$IPArduino = $my_arduino->getConfiguration('iparduino');

		//if ((($PortArduino=='rj45arduino') or ($PortArduino=='usbarduino')) and (($LocalArduino=='usblocal') or ($LocalArduino=='usbdeporte')))
		if (($ModeleArduino !='') and (($PortArduino=='rj45arduino') or ($PortArduino=='usbarduino')))
		{
			// ok une carte est definie
			list($Arduino_pins,$board,$usb) = self::GetPinsByBoard($arduino_id);

			$message='';
			foreach ($Arduino_pins as $pins_id => $pin_datas)
			{
				$myPin=config::byKey($arduino_id.'_'. $pins_id, 'jeedouino', 'not_used');
				switch ($myPin)
				{
					case 'not_used':
					case 'input':
					case 'input_pullup':
					case 'analog_input':
					case 'compteur_pullup':
					case 'pwm_output':
					case 'switch':
					case 'servo':
						$message.='.';
						break;
					case 'output':
					case 'output_pulse':
						$cmd = $my_arduino->getCmd(null, $pin_datas['Nom_pin']);
						if ($cmd->getDisplay('invertBinare'))  $message.=1-$cmd->getConfiguration('value');
						else $message.=$cmd->getConfiguration('value');
						break;
					case 'low_relais':
					case 'high_pulse':
					case 'high_pulse_slide':
						if ($cmd->getDisplay('invertBinare')) $message.='0';
						else $message.='1';
						break;
					case 'high_relais':
					case 'low_pulse':
					case 'low_pulse_slide':
						if ($cmd->getDisplay('invertBinare')) $message.='1';
						else $message.='0';
						break;
					default:
						$message.='.';
						break;
				}
			}
			if ($message!='')
			{
				jeedouino::log( 'debug','Envoi des valeurs des pins suite à la demande de la carte (Reboot?) eqID ( '.$arduino_id.' ) - Message : '. $message);
				self::SendToArduino($arduino_id, 'S' . $message . 'F', 'AllPinsValues', 'SFOK');
			}
		}

	}

	public function ConfigurePinValue($pins_id, $value, $arduino_id)
	{
		$my_arduino = eqLogic::byid($arduino_id);
		$ModeleArduino = $my_arduino->getConfiguration('arduino_board');
		$reponse='';

		$Altern = false;
		if ($pins_id >= 1000)
		{
			$pins_id -= 1000;
			$Altern = true;
		}

		$PinValue='';
		if ( $pins_id=='990') 		$PinValue='SetAllLOW=1';		// Set To LOW all Output pins
		elseif ( $pins_id=='991') 	$PinValue='SetAllHIGH=1';		// Set To HIGH all Output pins
		elseif ( $pins_id=='992') 	$PinValue='SetAllSWITCH=1';		// Switch/Toggle all Output pins
		elseif ( $pins_id=='993') 	$PinValue='SetAllPulseLOW=1&tempo='. substr($value,-5);		// Pulse To LOW  all Output pins
		elseif ( $pins_id=='994') 	$PinValue='SetAllPulseHIGH=1&tempo='. substr($value,-5);	// Pulse To HIGH all Output pins
		else
		{
			$myPin=config::byKey($arduino_id.'_'. $pins_id, 'jeedouino', 'not_used');
			switch ($myPin)
			{
				case 'switch':
					$PinValue='SwitchPin='. $pins_id;
				break;
				case 'trigger':
					$PinValue='Trigger='. $pins_id;
					$PinValue.='&Echo='. substr($value,-2);
				break;
				case 'low_relais':
					$PinValue='SetPinLOW='. $pins_id;
					$value='0';
					if ($Altern)
					{
						$value='1';
						$PinValue='SetPinHIGH='. $pins_id;
					}
				break;
				case 'high_relais':
					$PinValue='SetPinHIGH='. $pins_id;
					$value='1';
					if ($Altern)
					{
						$value='0';
						$PinValue='SetPinLOW='. $pins_id;
					}
				break;
				case 'low_pulse':
				case 'low_pulse_slide':
					$PinValue ='SetLOWpulse='. $pins_id;
					$PinValue.='&tempo='. substr($value,-5);	 // recupere la tempo (5 derniers chiffres)
				break;
				case 'high_pulse':
				case 'high_pulse_slide':
					$PinValue ='SetHIGHpulse='. $pins_id;
					$PinValue.='&tempo='. substr($value,-5);	 // recupere la tempo (5 derniers chiffres)
				break;
			}
		}

		if ($ModeleArduino == 'piface' or $ModeleArduino == 'piGPIO26' or $ModeleArduino == 'piGPIO40' or $ModeleArduino == 'piPlus')
		{
			jeedouino::log( 'debug','ConfigurePinValue '.$ModeleArduino.' ( '.$arduino_id.' ) '. "PinValue : ".$PinValue);
			if ($PinValue != '')  $reponse = self::SendToBoardDemon($arduino_id, $PinValue, $ModeleArduino);
		}
		else // Arduinos
		{
			if ( $pins_id=='990') 		$PinValue='S2L';	// Set To LOW all Output pins
			elseif ( $pins_id=='991') 	$PinValue='S2H';	// Set To HIGH all Output pins
			elseif ( $pins_id=='992') 	$PinValue='S2A';	// Switch/Toggle all Output pins
			elseif ( $pins_id=='993') 	$PinValue='SP' . sprintf("%05s", substr($value,-5)) . 'L';	// Pulse To LOW  all Output pins
			elseif ( $pins_id=='994') 	$PinValue='SP' . sprintf("%05s", substr($value,-5)) . 'H';	// Pulse To HIGH all Output pins
			elseif ( $pins_id>499 and $pins_id<600 ) 	$PinValue='U' . sprintf("%03s", $pins_id) . $value.'R';// User Pins
			else
			{
				if ($ModeleArduino!='a1280' and  $ModeleArduino!='a2560' and $pins_id>53) $pins_id -= 40; // Analog to Digital pins OUT
				$PinValue='S' . sprintf("%02s", $pins_id) . $value.'S';
				if ($myPin == 'trigger') $PinValue='T' . sprintf("%02s", $pins_id) . sprintf("%02s", substr($value,-2)) . 'E';	// Trigger pin + pin Echo -> HC-SR04 (ex:T0203E)
				if ($myPin == 'Send2LCD') $PinValue='S' . sprintf("%02s", $pins_id) . $value . 'M'; //S17Title|MessageM >>> S 17 Title | Message M	// Title & Message <16chars chacun
				if ($myPin == 'WS2811') 
				{
					$value = strtoupper($value);
					if (!$Altern) $PinValue = 'C' . sprintf("%02s", $pins_id) . 'L' . sprintf("%06s", $value) . 'R'; // C09LFF00FFR >>>Led Strip sur pin 09 valeur FF00FF (color)  
					else $PinValue = 'C' . sprintf("%02s", $pins_id) . 'M' . sprintf("%02s", $value) . 'R'; // C09M12R >>>Led Strip sur pin 09 valeur 12 (effet)
				}
			}
			jeedouino::log( 'debug','ConfigurePinValue '.$ModeleArduino.' ( '.$arduino_id.' ) '. "PinValue : ".$PinValue);
			$reponse=self::SendToBoard($arduino_id,$PinValue);
		}
		if ($reponse != 'SOK' and $reponse != 'SMOK')
		{
			// Si pas de réponse directe, on va essayer de voir, si on l'a recue via un callback (utile en cas de lags)
			//sleep(1);
			if ($reponse=='') $reponse=config::byKey('REP_'.$arduino_id, 'jeedouino', '');	// Double vérif
			config::save('REP_'.$arduino_id, '', 'jeedouino');  // On supprime pour les appels suivants.
			if ($reponse != 'SOK' and $reponse != 'SMOK')
			{
				jeedouino::log('debug', 'ERREUR SETTING PIN VALUE eqID ( '.$arduino_id.' )- Réponse :'.$reponse);
				return false;
			}
		}
		return true;
	}

	public function SendToBoard($arduino_id,$message)
	{
		$my_arduino = eqLogic::byid($arduino_id);
		$PortArduino = $my_arduino->getConfiguration('datasource');
		$IPArduino = $my_arduino->getConfiguration('iparduino');
		$ipPort = $my_arduino->getConfiguration('ipPort');

		if ($PortArduino=='rj45arduino')		// envoi sur reseau local
		{
			$message.="\n";
			$fp = @fsockopen($IPArduino, $ipPort, $errno, $errstr, 3);
			if ($fp===false)
			{
				$oldport = config::byKey($arduino_id . '_OLDPORT', 'jeedouino', '');
				if ($oldport != '') $fp = @fsockopen($IPArduino, $oldport, $errno, $errstr, 3);
				if ($fp === false)
				{
					jeedouino::log('error', 'ERREUR DE CONNECTION  ('.$IPArduino.':'.$ipPort.') : '. $errno.' - '.$errstr);
					return 'NOK';
				}
				event::add('jeedom::alert', array(
					'level' => 'warning',
					'page' => 'jeedouino',
					'message' => __('Attention vous avez changé le port ('.$oldport.'), il faudra reflasher ou le remettre !  (Nouveau :'.$IPArduino.':'.$ipPort.') ' , __FILE__)
					));
				jeedouino::log('info', 'Attention vous avez changé le port ('.$oldport.'), il faudra reflasher ou le remettre !  (Nouveau :'.$IPArduino.':'.$ipPort.') ');
			}

			stream_set_timeout($fp,9);
			fwrite($fp, $message);
			$reponse='';
			$debut = time();
			while (!feof($fp))
			{
				$reponse.=fgets($fp);
				if  ((time() - $debut) > 9) break;
			}
			fclose($fp);

			$reponse=trim($reponse);
			// Si pas de réponse directe, on va essayer de voir, si on l'a recue via un callback (utile en cas de lags)
			if ($reponse == '') $reponse = config::byKey('REP_'.$arduino_id, 'jeedouino', '');	// Double vérif
			config::save('REP_'.$arduino_id, '', 'jeedouino');  // On supprime pour les appels suivants.
			
			if ($reponse == '') $reponse = 'TIMEOUT';

			jeedouino::log( 'debug','REPONSE DE CONNECTION :'. $reponse);
			return $reponse;
		}
		else			// envoi sur usb
		{
			return self::SendToBoardDemon($arduino_id, 'USB=' . $message, 'USB');
		}
		return 'NOK';
	}

	// Permet de proposer un port libre lors d'un nouvel équipement.

	public function GiveMeFreePort($TypePort='')
	{
		$ipPortArr = array();
		$PortDemonArr = array();
		$ipPort = 8000;
		$PortDemon = 8080;
		// On recherche tous les ports déja utilisé (même si ce n'est pas les mêmes Jeedom, c'est plus simple)
		$eqLogics = eqLogic::byType('jeedouino');
		foreach ($eqLogics as $eqLogic)
		{
			$ipPortArr[] = $eqLogic->getConfiguration('ipPort');	  // port réseau
			$PortDemonArr[] = $eqLogic->getConfiguration('PortDemon');	  // port démon
		}
		if ($TypePort == 'ipPort')
		{
			while (in_array($ipPort, $ipPortArr))
			{
				$ipPort++;
			}
			return $ipPort;
		}
		elseif ($TypePort == 'PortDemon')
		{
			while (in_array($PortDemon, $PortDemonArr))
			{
				$PortDemon++;
			}
			return $PortDemon;
		}
		else return 8888;
	}

	// Permet de stocker les EqLogic ayant besoin d'un démon pour les relancer sutie à un reboot.

	public function AddEqLogicForStart($eqLogic = '')
	{
		if ($eqLogic != '')
		{
			$EqLogicArr = config::byKey('EqLogicForStart', 'jeedouino', 'none');
			if ($EqLogicArr != 'none')
			{
				//$EqLogicArr = json_decode($EqLogicArr, true);
				if (!in_array($eqLogic, $EqLogicArr))	$EqLogicArr[] = $eqLogic;
			}
			else
			{
				$EqLogicArr = array($eqLogic);
			}
			config::save('EqLogicForStart', json_encode($EqLogicArr), 'jeedouino');
			jeedouino::log( 'debug', 'EqLogicForStart :' . json_encode($EqLogicArr) . ' ( Ajouté: ' . $eqLogic.' ) ');
		}
	}

	public function RemoveEqLogicForStart($eqLogic = '')
	{
		if ($eqLogic != '')
		{
			$EqLogicArr = config::byKey('EqLogicForStart', 'jeedouino', 'none');
			if ($EqLogicArr != 'none')
			{
				//$EqLogicArr = json_decode($EqLogicArr, true);
				$key = array_search($eqLogic, $EqLogicArr);
				if ($key !== false)
				{
					unset($EqLogicArr[$key]);
					config::save('EqLogicForStart', $EqLogicArr, 'jeedouino');
					jeedouino::log( 'debug', 'EqLogicForStart :' . json_encode($EqLogicArr) . ' ( Enlevé: ' . $eqLogic . ' ) ');
				}
			}
		}
	}

	// Recup ID Esclave et appel cmd sur esclave

	public function GetSlaveNetworkID($SlaveNetworkID = 0, $EqIP)
	{
		$SlaveName = '';
		if ($SlaveNetworkID == 0)
		{
			if (class_exists ('jeeNetwork', false))
			{
				foreach (jeeNetwork::all() as $jeeNetwork)
				{
					if ($jeeNetwork->getIp() == $EqIP)
					{
						$SlaveName = $jeeNetwork->getName();
						$SlaveNetworkID = $jeeNetwork->getId();
					}
				}
			}
		}
		return array($SlaveNetworkID, $SlaveName);
	}

	public function CallSlavejeeNetwork($CallCmd, $params, $SlaveNetworkID=0)
	{
		if ($SlaveNetworkID)
		{
			if (class_exists ('jeeNetwork', false))
			{
				$jeeNetwork = jeeNetwork::byId($SlaveNetworkID);
				if (!is_object($jeeNetwork))
				{
					throw new Exception(__('Impossible de trouver l\'esclave : ', __FILE__) .$SlaveNetworkID);
				}
				if ($params=='') $params=array('plugin' => 'jeedouino');
				//jeedouino::log( 'debug','sendRawRequest CallCmd ' . $CallCmd . ' .');
				//jeedouino::log( 'debug','sendRawRequest params ' . json_encode($params) . ' .');
				$jeeNetwork->sendRawRequest($CallCmd, $params);  // Appel fonction sur esclave
			}
		}
		else
		{
			$board_id = $params['eqLogic'];
			$board = eqLogic::byid($board_id);
			//if (config::byKey($board_id.'remove', 'jeedouino', '0') == '0')	// cas de la suppression d'un équipement.
			//{
				$JeedouinoAlone = $board->getConfiguration('alone');
				if ($JeedouinoAlone == '1')	// Jeedouino sur un Rpi sans Jeedom.
				{
					jeedouino::log( 'debug','>>Envoi cmd '.$CallCmd.' sur Jeedouino déporté eqID ( '.$board_id.' ) - Message :'.json_encode($params));
					$reponse = self::CallJeedouinoExt($board_id, $CallCmd, json_encode($params),80); // EqLogic, Cmd, Array Params
					if ($reponse!='OK') jeedouino::log( 'error','Pb Envoi cmd '.$CallCmd.' sur Jeedouino déporté eqID ( '.$board_id.' ) - Réponse :'.$reponse);
				}
				else
				{
					log('debug',__('Equipement: '.strtoupper($board->getName()).' => Impossible de trouver Jeedouino sur l\'IP fournie - Le plugin Jeedouino n\'est peut-être pas installé dessus ou l\'IP est incorrecte.', __FILE__));
				}
			//}
		}
	}

	public function CallJeedouinoExt($board_id, $CallCmd, $params ,$_port='')
	{
		$board = eqLogic::byid($board_id);
		$IPboard = $board->getConfiguration('iparduino');
		$Port = $board->getConfiguration('ipPort');
		list(,$carte,$usb) = self::GetPinsByBoard($board_id);
		if ($carte == 'arduino' and $usb) $Port = $board->getConfiguration('PortDemon');
		if ($_port != '') $Port = $_port;

		$message  = $CallCmd.'='.$params;
		//$message .= "\n";

		jeedouino::log('debug', 'CallJeedouinoExt ('.$IPboard.':'.$Port.') $message -> '.$message);

		// $_path est envoyé par la page JeedouinoExt de l'ip concernée.
		$_path = trim(config::byKey('path-'.$IPboard, 'jeedouino', ''));
		if ($_path == '') $_path = '/';

		$url = 'http://'.$IPboard.':'.$Port.$_path.'JeedouinoExt.php?'.$message;
		return trim(@file_get_contents($url));
	}
	public function FilterDemon($DemonType)
	{
		$DemonType = trim(strtolower($DemonType));
		switch ($DemonType)
		{
			case 'usb':
			case 'arduinousb':
			case 'arduino':
			case 'auno':
			case 'a2009':
			case 'anano':
			case 'a1280':
			case 'a2560':
				return 'USB';
				break;
			case 'piface':
			case 'face':
				return 'PiFace';
				break;
			case 'pigpio':
			case 'pigpio26':
			case 'pigpio40':
			case 'gpio':
				return 'PiGpio';
				break;
			case 'piplus':
			case 'plus':
			case 'mcp':
				return 'PiPlus';
				break;
		}
		jeedouino::log( 'error', 'Impossible de trouver ce type de démon ( ' . ucfirst ($DemonType) . ' ).');
		return null;
	}

	public function SendToBoardDemon($board_id, $message, $DemonType)	// Envoi de message au Démon
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null) return;

		$my_Board = eqLogic::byid($board_id);
		$name = $my_Board->getName();
		$IPBoard = $my_Board->getConfiguration('iparduino');
		$ipPort = $my_Board->getConfiguration('ipPort');
		if ($DemonTypeF == 'USB' ) $ipPort = $my_Board->getConfiguration('PortDemon');

		$message = "GET ?".$message." HTTP/1.1\r\n";
		$message .= "Host: ".self::GetJeedomIP()."\r\n";
		$message .= "Connection: Close\r\n\r\n";
		
		$fp = false;
		//jeedouino::log( 'debug', ' $IPBoard = ' . $IPBoard);
		//jeedouino::log( 'debug', ' $_IpLocale = ' . config::byKey($board_id . '_IpLocale', 'jeedouino', 1));
		
		if (config::byKey($board_id . '_IpLocale', 'jeedouino', 1)) // Adresse IP locale pour le démon différente de celle de jeedom ?
		{
			$IPJeedom = self::GetJeedomIP();
			if ($IPJeedom != $IPBoard)
			{
				event::add('jeedom::alert', array(
					'level' => 'danger',
					'page' => 'jeedouino',
					'message' => __('Attention l\'IP (' . $IPBoard . ') du démon local ' . $DemonTypeF . ' (' . $name . ' - EqID ' . $board_id . ') et de Jeedom (' . $IPJeedom . ') diffèrent. Veuillez vérifier.' , __FILE__)
					));
				jeedouino::log('error', 'Attention l\'IP (' . $IPBoard . ') du démon local ' . $DemonTypeF . ' (' . $name . ' - EqID ' . $board_id . ') et de Jeedom (' . $IPJeedom . ') diffèrent. Veuillez vérifier. ');
				$IPBoard =  $IPJeedom;
			}
		}
		$fp = @fsockopen($IPBoard, $ipPort, $errno, $errstr, 3);
		if ($fp === false)
		{
			$oldport = config::byKey($board_id . '_OLDPORT', 'jeedouino', '');
			if ($oldport != '') $fp = @fsockopen($IPBoard, $oldport, $errno, $errstr, 3);
			if ($fp === false)
			{
				$reponse = $errno.' - '.$errstr;
				if (!config::byKey('StartDemons', 'jeedouino', 0)) jeedouino::log( 'debug','(Normal si Re/Start/Stop demandé) Erreur de connection au démon ' . $DemonTypeF . ' ( ' . $name . ' - EqID ' . $board_id . ' ) sur '.$IPBoard.':'.$ipPort.' - Réponse : ' . $reponse);
				return $reponse;
			}
		}
		stream_set_timeout($fp,11);
		fwrite($fp, $message);
		$reponse='';
		$debut = time();
		while (!feof($fp))
		{
			$reponse.=fgets($fp);
			if  ((time() - $debut) > 11) break;
		}
		fclose($fp);
		$reponse = trim($reponse);

		// Si pas de réponse directe, on va essayé de voir, si on la recue via un callback (utile en cas de lags)
		if ($reponse == '') $reponse = config::byKey('REP_'.$board_id, 'jeedouino', '');	// Double vérif
		config::save('REP_'.$board_id, '', 'jeedouino');  // On supprime pour les appels suivants.

		if ($reponse == '') $reponse = 'TIMEOUT'; //Aucune, Il n\'est peût-être pas encore démarré.';
		jeedouino::log('debug', 'Réponse du Démon ' . $DemonTypeF . ' :'. $reponse);
		return $reponse;
	}
	public function StartBoardDemon($board_id, $SlaveNetworkID = 0, $DemonType)	// Démarre le Démon
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null) return;

		jeedouino::log( 'debug','Démarrage du démon ' . $DemonTypeF . '.');

		$my_Board = eqLogic::byid($board_id);
		$name = $my_Board->getName();
		$IPBoard = $my_Board->getConfiguration('iparduino');
		$ipPort = $my_Board->getConfiguration('ipPort');
		$PiPlusBoardID = $my_Board->getConfiguration('PortI2C');		// No de carte PiPlus ( si plusieurs sur même RPI)
		$PiFaceBoardID = $my_Board->getConfiguration('PortID');		// No de carte piface ( si plusieurs sur même RPI)
		$PortDemon = $my_Board->getConfiguration('PortDemon'); 	// PortUSB - ArduinoUSB

		$jeedomIP = self::GetJeedomIP();
		$JeedomPort = self::GetJeedomPort();
		$JeedomCPL = self::GetJeedomComplement();

		// Arduino USB
		if ($DemonTypeF == 'USB')
		{
			$LocalArduino = $my_Board->getConfiguration('arduinoport');   // usblocal - usbdeporte
			$portusbdeporte = $my_Board->getConfiguration('portusbdeporte');   // portusbdeporte (jeeNetworkID_portUSB)
			if ($LocalArduino == 'usblocal')
			{
				$portUSB = $my_Board->getConfiguration('portusblocal');
				// MàJ de l'ip de l'arduino usb au cas où celle de Jeedom ai changée depuis la sauvegarde de l'équipement.
				$IPBoard = self::GetJeedomIP();
				$my_Board->setConfiguration('iparduino', $IPBoard);  // On la sauve pour le démon
				$my_Board->save(true);
			}
			else
			{
				$p = strpos($portusbdeporte,'_');
				// Pas de verification stricte car je ne veux pas de position a 0 non plus
				if ($p) $portUSB = substr($portusbdeporte,$p+1);
				else throw new Exception(__('Impossible de trouver le port USB du démon déporté: ', __FILE__) .$portusbdeporte);
			}
		}
		else $portUSB = '';

		// Si déja démarré, on le stoppe car la config du port d"écoute peut avoir changée par ex.
		if (self::StatusBoardDemon($board_id, $SlaveNetworkID, $DemonType)) self::StopBoardDemon($board_id, $SlaveNetworkID, $DemonType);

		if ($jeedomIP == $IPBoard or config::byKey($board_id . '_IpLocale', 'jeedouino', 1)) self::StartBoardDemonCMD($board_id, $DemonType, $ipPort, $jeedomIP, $JeedomPort, $JeedomCPL, $PiPlusBoardID, $PiFaceBoardID, $PortDemon, $portUSB);   // sur local Board (master ou esclave)
		else
		{
			if (self::Networkmode() == 'master')
			{
				list($SlaveNetworkID) = self::GetSlaveNetworkID($SlaveNetworkID, $IPBoard);
				self::CallSlavejeeNetwork('StartBoardDemonCMD', array('plugin' => 'jeedouino', 'eqLogic' => $board_id, 'DemonType' => $DemonType, 'ipPort' => $ipPort, 'jeedomIP' => $jeedomIP, 'JeedomPort' => $JeedomPort, 'JeedomCPL' => $JeedomCPL, 'PiPlusBoardID' => $PiPlusBoardID, 'PiFaceBoardID' => $PiFaceBoardID, 'PortDemon' => $PortDemon, 'portUSB' => $portUSB), $SlaveNetworkID);
			}
			else self::StartBoardDemonCMD($board_id, $DemonType, $ipPort, $jeedomIP, $JeedomPort, $JeedomCPL, $PiPlusBoardID, $PiFaceBoardID, $PortDemon, $portUSB); 	// cas peu probable (jeedouino appellé sur esclave sans Board)
		}
		// Après un démarrage/redémarrage, on renvoi la config des pins
		$PinMode = config::byKey($board_id . '_PinMode', 'jeedouino', 'none');
		if ($PinMode != 'none')
		{
			if (($DemonTypeF == 'PiGpio') or ($DemonTypeF == 'PiPlus'))
			{
				sleep(2);
				$BootMode = 'BootMode=' . config::byKey($board_id . '_' . $DemonTypeF . '_boot', 'jeedouino', '0');
				jeedouino::log( 'debug', 'Envoi de la dernière configuration connue du BootMode eqID ( ' . $board_id . ' ) ' . "BootMode : " . $BootMode);
				$reponse = self::SendToBoardDemon($board_id, $BootMode, $DemonType);
				if ($reponse != 'BMOK') jeedouino::log( 'debug', 'Erreur d\'envoi de la configuration du BootMode sur l\'équipement '.$board_id.' ( ' . $name . ' ) - Réponse :'.$reponse);
			}
			elseif ($DemonTypeF == 'USB' ) $PinMode = 'USB=' . $PinMode;

			//jeedouino::log( 'debug', "Pause de 4s pour laisser l'arduino finir de communiquer avec le démon qui vient de demarrer");
			sleep(4);
			$debut = time();
			while (config::byKey('SENDING_'.$board_id, 'jeedouino', 0) == 1)
			{
				if  ((time() - $debut) > 11) break;	// timeout de securite
				sleep(1);
			}
			config::save('SENDING_'.$board_id, 1, 'jeedouino');
			$_try = 0;
			$reponse = '';
			$waitforArr = array('PINGOK' , 'EOK' , 'IPOK' , 'SOK' , 'SCOK' , 'SFOK' , 'BMOK');
			while ($_try<2 and $reponse != 'COK')
			{
				$_try++;
				
				jeedouino::log( 'debug', 'Essai '.$_try.' - Envoi de la dernière configuration connue des pins eqID ( '.$board_id.' ) '."PinMode : ". $PinMode );
				$reponse = self::SendToBoardDemon($board_id, $PinMode, $DemonType);
				if (in_array($reponse, $waitforArr)) jeedouino::log( 'debug', 'Réponse différée reçue de l\'équipement '.$board_id.' ( ' . $name . ' ) - Réponse :'.$reponse);
				elseif ($reponse != 'COK') jeedouino::log( 'debug', 'Erreur d\'envoi de la configuration des pins sur l\'équipement '.$board_id.' ( ' . $name . ' ) - Réponse :'.$reponse);
				sleep(2);
			 }
			 config::save('SENDING_'.$board_id, 0, 'jeedouino');
		}
	}
	public function StartBoardDemonCMD($board_id = '', $DemonType, $ipPort, $jeedomIP, $JeedomPort, $JeedomCPL, $PiPlusBoardID, $PiFaceBoardID, $PortDemon, $_portUSB)	// Démarre le Démon
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null or $board_id == '') return;

		$jeedouinoPATH = realpath(dirname(__FILE__) . '/../../ressources');
		$jeedouinoFile = '/jeedouino' . $DemonTypeF;

		$filename = $jeedouinoPATH . $jeedouinoFile . '_' . $board_id . '.py';
		if (file_exists($filename)) unlink($filename);

		$DemonFileName = $jeedouinoPATH . $jeedouinoFile . '.py';
		if (!copy($DemonFileName, $filename))
		{
			jeedouino::log( 'error', ' Impossible de créer le fichier pour le démon ( '.$filename.' ).');
			$filename = $DemonFileName;
		}

		self::StopBoardDemonCMD($board_id, $DemonType); // Stoppe le(s) processus du Démon local
		$_ProbeDelay = config::byKey($board_id . '_ProbeDelay', 'jeedouino', '5');
		if ($JeedomCPL == '') $JeedomCPL = '.';
		switch ($DemonTypeF)
		{
			case 'USB':
				$portUSB = trim(jeedom::getUsbMapping($_portUSB));
				if ($portUSB == '')	$portUSB = trim(self::getUsbMapping($_portUSB));
				if ($portUSB == '')
				{
					jeedouino::log( 'error', 'Appel démon ArduinoUsb impossible - port USB vide !.');
					return false;
				}
				$baudrate = 115200;
				if (config::byKey($board_id . '_SomfyRTS', 'jeedouino', 0)) $baudrate /=  2;
				jeedouino::log( 'debug', 'Appel démon ArduinoUsb sur port :' . $_portUSB . ' ( ' . $portUSB . ' ) - Baudrate : ' . $baudrate);
				$cmd = $PortDemon.' '.$portUSB.' '.$board_id.' '.$jeedomIP.' '.$JeedomPort.' '.$JeedomCPL.' '.$baudrate.' '.$_ProbeDelay;
				break;
			case 'PiFace':
				$cmd = $ipPort.' '.$board_id.' '.$jeedomIP.' '.$PiFaceBoardID.' '.$JeedomPort.' '.$JeedomCPL;
				break;
			case 'PiGpio':
				$cmd = $ipPort.' '.$board_id.' '.$jeedomIP.' '.$JeedomPort.' '.$JeedomCPL.' '.$_ProbeDelay;
				break;
			case 'PiPlus':
				$cmd = $ipPort.' '.$board_id.' '.$jeedomIP.' '.$PiPlusBoardID.' '.$JeedomPort.' '.$JeedomCPL;
				break;
		}
		$cmd = "sudo nice -n 19 /usr/bin/python " . $filename . ' ' . $cmd;
		jeedouino::log( 'debug', 'Cmd Appel démon : ' . $cmd);
		$reponse = exec($cmd . ' >> ' . log::getPathToLog('jeedouino') . ' 2>&1 &');

		self::AddEqLogicForStart($board_id);	// On stocke l'eqLogic, pour pouvoir relancer le démon suite à un reboot

		if ((strpos(strtolower($reponse), 'error') !== false) or (strpos(strtolower($reponse), 'traceback') !== false))
		{
			jeedouino::log( 'error', 'Le démon ' . $DemonTypeF . ' ne démarre pas. - Réponse :'.$reponse);
		}
		else  jeedouino::log('debug', 'Le démon ' . $DemonTypeF . ' est en cours de démarrage.  - '.$reponse);
	}
	public function StopBoardDemon($board_id, $SlaveNetworkID=0, $DemonType)	// Stoppe le Démon
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null) return;
		// Arrét soft
		jeedouino::log( 'debug',' Demande d\'arrêt au démon ' . $DemonTypeF . ' eqID ( '.$board_id.' )');
		$reponse = self::SendToBoardDemon($board_id, 'EXIT=1', $DemonType);
		if ($reponse != 'EXITOK') jeedouino::log( 'error','Le démon ' . $DemonTypeF . ' ne réponds pas correctement. - Réponse :'. $reponse);
		else
		{
			usleep(1000000);  // 1s , petite pause pour laisser au script python le temps de stopper
			jeedouino::log( 'debug','Le démon ' . $DemonTypeF . ' est stoppé (SOFT EXIT) - Réponse :'. $reponse);
		}
		config::save($board_id . '_OLDPORT', '', 'jeedouino');
		// est il toujours en marche (processus)? Arrét hard.
		self::ForceStopBoardDemon($board_id, $SlaveNetworkID, $DemonType);
	}
	public function ForceStopBoardDemon($board_id, $SlaveNetworkID=0, $DemonType)	// Stoppe le(s) processus du Démon local ou déporté
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null) return;

		$my_Board = eqLogic::byid($board_id);
		$IPBoard = $my_Board->getConfiguration('iparduino');

		$jeedomIP = self::GetJeedomIP();
		if ($jeedomIP == $IPBoard) self::StopBoardDemonCMD($board_id, $DemonType);   // sur local  (master ou esclave)
		else
		{
			if (self::Networkmode() == 'master')
			{
				list($SlaveNetworkID) = self::GetSlaveNetworkID($SlaveNetworkID, $IPBoard);
				self::CallSlavejeeNetwork('StopBoardDemonCMD',array('plugin' => 'jeedouino', 'eqLogic' => $board_id, 'DemonType' => $DemonType), $SlaveNetworkID);
			}
			else self::StopBoardDemonCMD($board_id, $DemonType);	// cas peu probable (jeedouino appellé sur esclave sans démon)
		}
	}
	public function StopBoardDemonCMD($board_id = '', $DemonType)	// Stoppe le(s) processus du Démon local
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null or $board_id == '') return;

		$DemonFileName = 'jeedouino' . $DemonTypeF . '_' . $board_id . '.py';
		// Si  il y a toujours des processus en cours, on les tues
		exec("pgrep --full ".$DemonFileName, $processus);
		$done = false;
		foreach ($processus as $process)
		{
			jeedouino::log( 'debug','KILL process '.$process);
			exec('sudo kill -9 ' . $process . ' >> ' . log::getPathToLog('jeedouino') . ' 2>&1 &');
			$done = true;
			usleep(500000); // 0.5 secondes
		}
		sleep(3);
		if ($done)
		{
			jeedouino::log( 'debug','StopBoardDemonCMD - Arrêt forcé du démon ' . $DemonTypeF . ' sur  '.self::GetJeedomIP().' - '.$DemonFileName.' : Kill process : '.json_encode($processus));
			self::RemoveEqLogicForStart($board_id);	// On enleve l'eqLogic, pour ne pas relancer le démon suite à un reboot
		}
	}
	public function StatusBoardDemon($_board_id, $SlaveNetworkID=0, $DemonType)	 // Démon en marche ???
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null) return false;

		if ($_board_id == 0) $_board_id = $this->getEqLogic_id();
		jeedouino::log( 'debug','PING ( EqID:'.$_board_id.' ) Démon ' . $DemonTypeF . ' en marche ??? Envoi d\'un PING...');
 		$reponse = self::SendToBoardDemon($_board_id, 'PING=1', $DemonType); // On le PINGue
		
		if (strpos($reponse, '111') !== false) return false; // Connection refused
		
		if ($reponse != 'PINGOK')
		{
			sleep(1);
			jeedouino::log( 'debug','RePING ( EqID:'.$_board_id.' ) Encore un essai...');
			$reponse = self::SendToBoardDemon($_board_id, 'PING=2', $DemonType); // On rePINGue
			if ($reponse == 'PINGOK')
			{
				config::save($_board_id.'_' . $DemonTypeF . 'CountBadPING', 0, 'jeedouino');	// RAZ
				return true;
			}
			if (strpos($reponse, '111') !== false) return false; // Connection refused
			// Combien de PING non répondus ?
			$CountBadPING=config::byKey($_board_id.'_' . $DemonTypeF . 'CountBadPING', 'jeedouino', 0);
			$CountBadPING++;

			if (!config::byKey('StartDemons', 'jeedouino', 0)) jeedouino::log( 'debug','PING EqID:'.$_board_id.' (Essai No '.$CountBadPING.' ): Le démon ' . $DemonTypeF . ' ne réponds pas - Réponse :'.$reponse);   // Le démon ne réponds pas, on va vérifier si il traîne des processus
			if ($CountBadPING>3)	// Après 3 PING NON OK, on force l'arrêt du démon.
			{
				if (!config::byKey('StartDemons', 'jeedouino', 0)) jeedouino::log( 'error','4 PINGs non répondus, je stoppe les processus du démon ' . $DemonTypeF . ' ! ( EqID : '.$_board_id.' )');
				self::ForceStopBoardDemon($_board_id, $SlaveNetworkID, $DemonType);   // Si oui, on les kill pour éviter les problèmes.
				$CountBadPING=0;	//RAZ
				// Si demon usb, on tente de changer le port car il permet un redemarrage du demon plus rapide sur certains systemes
				if ($DemonTypeF == 'USB') jeedouino::ChangePortDemon($_board_id);
			}
			config::save($_board_id.'_' . $DemonTypeF . 'CountBadPING', $CountBadPING, 'jeedouino');	// Sauvegarde
			return false;
		}
		else
		{
			config::save($_board_id.'_' . $DemonTypeF . 'CountBadPING', 0, 'jeedouino');	// RAZ
			return true;
		}
	}
	public function ChangePortDemon($board_id = 0)
	{
		if ($board_id == 0) $board_id = $this->getEqLogic_id();
		
		$my_Board = eqLogic::byid($board_id);
		
		jeedouino::log( 'debug','Changement du port du démon ( EqID:'.$board_id.' ) ...'); 
		$port = jeedouino::GiveMeFreePort('PortDemon');
		$my_Board->setConfiguration('PortDemon' , $port);
		$my_Board->setConfiguration('ipPort' , $port);
		$my_Board->save(true);
		
	}
	public function StatusBoardDemonCMD($board_id = '', $DemonType)	// Processus du Démon en marche ??? (si ping ne réponds pas)
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null or $board_id == '') return;

		$DemonFileName = 'jeedouino' . $DemonTypeF . '_' . $board_id . '.py';
		exec("pgrep --full ".$DemonFileName, $processus);
		return isset($processus[0]);
	}
	public function ReStartBoardDemon($board_id, $SlaveNetworkID=0, $DemonType)	// Redémarre le Démon
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null) return;

		config::save('StartDemons', 1, 'jeedouino');
		if (self::StatusBoardDemon($board_id,$SlaveNetworkID, $DemonType))
		{
			self::StopBoardDemon($board_id,$SlaveNetworkID, $DemonType);
		}
		usleep(1500000); // 1.5 secondes
		self::StartBoardDemon($board_id, $SlaveNetworkID, $DemonType);
		config::save('StartDemons', 0, 'jeedouino');
	}
	public function EraseBoardDemonFile($board_id, $SlaveNetworkID=0, $DemonType)	// Cherche le Démon pour effacer son fichier jeedouinoUSB_*.py
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null) return;

		$my_Board = eqLogic::byid($board_id);
		$IPBoard = $my_Board->getConfiguration('iparduino');

		$jeedomIP = self::GetJeedomIP();
		if ($jeedomIP == $IPBoard) self::EraseBoardDemonFileCMD($board_id, $DemonType);   // sur local ArduinoUsb (master ou esclave)
		else
		{
			if (self::Networkmode() == 'master')
			{
				list($SlaveNetworkID) = self::GetSlaveNetworkID($SlaveNetworkID, $IPBoard);
				self::CallSlavejeeNetwork('EraseBoardDemonFileCMD',array('plugin' => 'jeedouino', 'eqLogic' => $board_id, 'DemonType' => $DemonType),$SlaveNetworkID);
			}
			else self::EraseBoardDemonFileCMD($board_id, $DemonType);	// cas peu probable (jeedouino appellé sur esclave sans ArduinoUsb)
		}
	}
	public function EraseBoardDemonFileCMD($board_id = '', $DemonType)	// Efface le fichier python généré pour le Démon
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null or $board_id == '') return;

		$jeedouinoPATH = realpath(dirname(__FILE__) . '/../../ressources');
		$DemonFileName = $jeedouinoPATH . '/jeedouino' . $DemonTypeF . '_' . $board_id . '.py';

		if (!file_exists($DemonFileName)) jeedouino::log( 'error', 'Le fichier du démon ' . $DemonTypeF . ' (eqID : '.$board_id.') est introuvable ! :'.$DemonFileName);
		else
		{
			unlink($DemonFileName);
			jeedouino::log( 'debug', 'Le fichier du démon ' . $DemonTypeF . '  (eqID : '.$board_id.') est supprimé ! :');
		}
	}

	/************************** Pile de mise à jour **************************/

	/* fonction permettant d'initialiser la pile
	 * plugin: le nom de votre plugin
	 * action: l'action qui sera utilisé dans le fichier ajax du pulgin
	 * callback: fonction appelé coté client(JS) pour mettre à jour l'affichage
	 */
	public function initStackData() {
		nodejs::pushUpdate('jeedouino::initStackDataEqLogic', array('plugin' => 'jeedouino', 'action' => 'saveStack', 'callback' => 'displayEqLogic'));
	}

	/* fonnction permettant d'envoyer un nouvel équipement pour sauvegarde et affichage,
	 * les données sont envoyé au client(JS) pour être traité de manière asynchrone
	 * Entrée:
	 *	  - $params: variable contenant les paramètres eqLogic
	 */
	public function stackData($params) {
		if(is_object($params)) {
			$paramsArray = utils::o2a($params);
		}
		nodejs::pushUpdate('jeedouino::stackDataEqLogic', $paramsArray);
	}

	/* fonction appelé pour la sauvegarde asynchrone
	 * Entrée:
	 *	  - $params: variable contenant les paramètres eqLogic
	 */
	public function saveStack($params) {
		// inserer ici le traitement pour sauvegarde de vos données en asynchrone

	}

	/* fonction appelé avant le début de la séquence de sauvegarde */
	public function preSave()
	{
		jeedouino::log( "debug",' >>>preSave');

		if ($this->getIsEnable() == 0)
		{
			jeedouino::log( 'debug','L\'équipement ID '.$this->getId().' est désactivé. Pas la peine de continuer.');
			return;
		}
		// On va essayer de détecter un changement dans les paramêtres de la carte (réseau/port/etc)
		$arduino_id=$this->getId();
		$BoardEQ = eqLogic::byid($arduino_id);


		config::save($arduino_id.'-ForceStart', '0', 'jeedouino');
		config::save($arduino_id.'-ForceSuppr', '0', 'jeedouino');

		// liste des paramêtres a surveiller
		$config_list=array('ipPort','iparduino','arduino_board','PortI2C','datasource','PortID','arduinoport','portusbdeporte','PortDemon','portusblocal','alone');

		if ($BoardEQ!==false and $BoardEQ!==null)	// Si création équipement, il n'y a pas de eqLogic pour comparer
		{
			foreach ($config_list as $param)
			{
				if (trim($BoardEQ->getConfiguration($param)) != trim($this->getConfiguration($param)))
				{
					config::save($arduino_id.'-ForceStart', '1', 'jeedouino');
					jeedouino::log( 'debug','EqID '.$arduino_id.' Old -  $'.$param.' = '.trim($BoardEQ->getConfiguration($param)));
					jeedouino::log( 'debug','EqID '.$arduino_id.' New -  $'.$param.' = '.trim($this->getConfiguration($param)));
					jeedouino::log( 'debug','EqID '.$arduino_id.' Au moins un paramêtre a changé, il faut forcer le redémarrage du démon si il y en a un.');
					// On sauvegarde l'ancien port pour pouvoir communiquer l'arret du démon par ex.
					if ($param == 'ipPort' or $param == 'PortDemon' )
					{
						config::save($arduino_id . '_OLDPORT', trim($BoardEQ->getConfiguration($param)), 'jeedouino');
					}
					// Changement de modele, on supprime toutes les commandes
					if ($param == 'arduino_board')
					{
						// TODO : ajouter suppression des sketchs du modele precedent.
						config::save($arduino_id.'-ForceSuppr', '1', 'jeedouino');
						jeedouino::log( 'debug','EqID '.$arduino_id.' Le modèle de carte n\'est plus le même, il faut supprimer toutes les commandes.');
					}
					break; // pas la peine de continuer
				}
			}

			$ModeleArduino = trim($this->getConfiguration('arduino_board'));
			if ($ModeleArduino!='')
			{
				$PortArduino = trim($this->getConfiguration('datasource'));

				// On vérifie si l'IP est correctement renseignée.
				if ($PortArduino=='rj45arduino')
				{
					$ip = strtolower(trim($this->getConfiguration('iparduino')));
					if (substr($ip,0,5) == 'local') $ip = '127.0.0.1';

					if (filter_var( $ip , FILTER_VALIDATE_IP) === false)
					{
						throw new Exception(__('Le format de l\'adresse IP n\'est pas valide. Veuillez le vérifier.', __FILE__));
					}
					$ipJeedom = self::GetJeedomIP();
					if ($ip == '127.0.0.1')
					{
						$ip = $ipJeedom;
						$this->setConfiguration('iparduino', $ip);
						self::log( "debug",'Adresse IP 127.0.0.1 de ' . ($this->getName()) . ' (' .$arduino_id. ') remplacée par son adresse locale : ' . $ip);
					}
					if ($ip == $ipJeedom) config::save($arduino_id . '_IpLocale', 1, 'jeedouino');
					else config::save($arduino_id . '_IpLocale', 0, 'jeedouino');	
				}
				elseif ($PortArduino=='usbarduino')
				{
					$LocalArduino = trim($this->getConfiguration('arduinoport'));
					if ($LocalArduino == 'usblocal')
					{
						$portusblocal = trim($this->getConfiguration('portusblocal'));
						if ($portusblocal == '') throw new Exception(__('Vous n\'avez pas choisi le port USB : Local .', __FILE__));
					}
					elseif ($LocalArduino == 'usbdeporte')
					{
						$portusbdeporte = trim($this->getConfiguration('portusbdeporte'));
						if ($portusbdeporte == '') throw new Exception(__('Vous n\'avez pas choisi le port USB : Déporté .', __FILE__));
					}
					else throw new Exception(__('Vous n\'avez pas défini où est le port USB: Local/Déporté)) !.', __FILE__));
				}
				else throw new Exception(__('Vous n\'avez pas défini le type de connection de la carte (Réseau / Usb)) !.', __FILE__));

				if ($ModeleArduino == 'piPlus')
				{
					$PortI2C = trim($this->getConfiguration('PortI2C'));
					if ($PortI2C == '' ) throw new Exception(__('Vous n\'avez pas choisi l\'adresse I2C du MCP23017.', __FILE__));
				}
				if (substr($ModeleArduino,0,3) == 'esp')
				{
					$wifi_ssid = $this->getConfiguration('wifi_ssid');
					$wifi_pass = $this->getConfiguration('wifi_pass');
					if (strpos($wifi_ssid,' ')!==false) jeedouino::log( 'error','EqID '.$arduino_id.' - Votre SSID WIFI contient un/des espaces. Cela peut poser des problèmes de connection.');
					if (strpos($wifi_pass,' ')!==false) jeedouino::log( 'error','EqID '.$arduino_id.' - Votre mot de passe WIFI contient un/des espaces. Cela peut poser des problèmes de connection.');
					$wifi_ssid = trim($wifi_ssid);
					$wifi_pass = trim($wifi_pass);
					if ($wifi_ssid == '' ) throw new Exception(__('Vous n\'avez pas défini le SSID de votre WiFi.', __FILE__));
					if ($wifi_pass == '' ) throw new Exception(__('Vous n\'avez pas défini le Mot de Passe de votre WiFi.', __FILE__));
				}
				// On vérifie que le numéro de piFace est ok
				$PiBoardID = $this->getConfiguration('PortID');
				if ($PiBoardID<0 or $PiBoardID>3 or $PiBoardID=='')
				{
					$this->setConfiguration('PortID',0);  // Carte PiFace par défaut.
				}
				// On vérifie que le port est bien renseigné.
				$ipPort = trim($this->getConfiguration('ipPort'));	  // port réseau
				$PortDemon = trim($this->getConfiguration('PortDemon'));	  // port démon

				if ($PortArduino=='rj45arduino')
				{
					if ($ipPort == '')
					{
						//throw new Exception(__('Le port de communication IP n\'est pas valide. Veuillez le changer.', __FILE__));
						$this->setConfiguration('ipPort' ,  jeedouino::GiveMeFreePort('ipPort'));
					}
					else
					{
						if (substr($ModeleArduino,0,2) == 'pi')
						{
							if ($ipPort<1024) throw new Exception(__('Le port de communication IP du démon doit etre supérieur à 1024. Veuillez le changer.', __FILE__));
						}
						else
						{
							if ($ipPort<80) throw new Exception(__('Le port de communication IP doit etre supérieur à 80. Veuillez le changer.', __FILE__));
						}
					}
				}
				else
				{
					if ($PortDemon == '')
					{
						//throw new Exception(__('Le port de communication du démon n\'est pas valide. Veuillez le changer.', __FILE__));
						$port = jeedouino::GiveMeFreePort('PortDemon');
						$this->setConfiguration('PortDemon' , $port);
						$this->setConfiguration('ipPort' , $port);
					}
					elseif ($PortDemon==self::GetJeedomPort()) throw new Exception(__('Le port de communication du démon doit être différent de celui de Jeedom ('.self::GetJeedomPort().'). Veuillez le changer.', __FILE__));
					elseif ($PortDemon<1024) throw new Exception(__('Le port de communication du démon doit etre supérieur à 1024. Veuillez le changer.', __FILE__));
					// TODO : faire la vérif en déporté.
				}

			}
			else throw new Exception(__('Vous n\'avez pas défini de modèle de carte !.', __FILE__));
		}
	}

	/* fonction appelé pendant la séquence de sauvegarde avant l'insertion
	 * dans la base de données pour une mise à jour d'une entrée */
	public function preUpdate() {
		jeedouino::log( "debug",' >>>preUpdate');
	}

	/* fonction appelé pendant la séquence de sauvegarde après l'insertion
	 * dans la base de données pour une mise à jour d'une entrée */
	public function postUpdate() {
		jeedouino::log( "debug",' >>>postUpdate');
	}

	/* fonction appelé pendant la séquence de sauvegarde avant l'insertion
	 * dans la base de données pour une nouvelle entrée */
	public function preInsert()
	{
		jeedouino::log( "debug",' >>>preInsert');
		$this->setCategory('automatism', 1);
	}

	/* fonction appelé pendant la séquence de sauvegarde après l'insertion
	 * dans la base de données pour une nouvelle entrée */
	public function postInsert()
	{
		jeedouino::log( "debug", ' >>>postInsert');
		$arduino_id = $this->getId();
		config::save($arduino_id . '_EqCfgSaveStep', 1, 'jeedouino');	// A la création de l'équipement, permetra de savoir à quelle étape on est.

		// On verifie si l'equipement resulte d'une duplication
		$Original_ID = $this->getConfiguration('Original_ID');
		if ($Original_ID == '')
		{
			// pas d'id original, donc surement creation d'un nouvel equipement
			$this->setConfiguration('Original_ID' , $arduino_id);
			$this->save(true);
			jeedouino::log( 'debug','Pas de ID original, donc surement création d un nouvel équipement.');
		}
		elseif ($Original_ID != $arduino_id)
		{
			jeedouino::log( 'debug',' arduino_id = ' . $arduino_id);
			jeedouino::log( 'debug',' Original_ID = ' . $Original_ID);
			jeedouino::log( 'debug','ID original différent de celui de l\'ID courant, donc résultat d\'une duplication.');
			$this->setConfiguration('Original_ID' , $arduino_id);
			$this->save(true);
			// id original different de celui de l'id courant, donc resultat d'une duplication.
			//
			// pins de la carte (normalement deja definie)
			list($Arduino_pins , $board , $usb) = self::GetPinsByBoard($arduino_id);

			// copie des datas des pins
			foreach ($Arduino_pins as $pins_id => $pin_datas)
			{
				$myPin = config::byKey($Original_ID . '_' . $pins_id, 'jeedouino', 'not_used');
				$generic_type = config::byKey('GT_' . $Original_ID . '_' . $pins_id, 'jeedouino', '');
				$virtual = config::byKey('GV_' . $Original_ID . '_' . $pins_id, 'jeedouino', '');

				config::save($arduino_id . '_' . $pins_id, $myPin, 'jeedouino');
				config::save('GT_' . $arduino_id . '_' . $pins_id, $generic_type, 'jeedouino');
				config::save('GV_' . $arduino_id . '_' . $pins_id, $virtual, 'jeedouino');
			}
			// copie des options
			$choix_boot = config::byKey($Original_ID . '_choix_boot', 'jeedouino', '2');
			config::save($arduino_id . '_choix_boot', $choix_boot, 'jeedouino');
			$_ProbeDelay = config::byKey($Original_ID . '_ProbeDelay', 'jeedouino', '5');
			config::save($arduino_id . '_ProbeDelay', $_ProbeDelay, 'jeedouino');
		}

	}
	public function GiveMeUserPins($UserPinsMax, $UserPinsBase = 500)
	{
		$user_pins = array();
		if (config::byKey('ActiveUserCmd', 'jeedouino', false))
		{
			//jeedouino::log( 'debug','UserPinsMax : '.$UserPinsMax);
			for ($i = 0; $i < $UserPinsMax; $i++)
			{
				$pins_id = $i + $UserPinsBase;
				$user_pins[$pins_id] = array('Nom_pin' => 'UserPin (' . $pins_id . ')' , 'disable' => 0, 'ethernet' => 0, 'option' => '');
			}
		}
		return $user_pins;
	}
	/* fonction appelé après la fin de la séquence de sauvegarde */
	public function postSave()
	{
		jeedouino::log( "debug" , 'Debut de postSave');

		$arduino_id = $this->getId();

		// petit correctif concernant le delai de renvoi des temp des sondes
		$_ProbeDelay = config::byKey($arduino_id . '_ProbeDelay', 'jeedouino', '5');
		if ($_ProbeDelay < 1 or $_ProbeDelay>1000) $_ProbeDelay = 5;
		config::save($arduino_id . '_ProbeDelay', $_ProbeDelay, 'jeedouino');

		if ($this->getIsEnable() == 0)
		{
			list(, $board, $usb) = self::GetPinsByBoard($arduino_id);
			switch ($board)
			{
				case 'arduino':
					if ($usb)
					{
						self::StopBoardDemon($arduino_id, 0, $board);
					}
					break;
				case 'piface':
				case 'piplus':
				case 'gpio':
					self::StopBoardDemon($arduino_id, 0, $board);
					break;
			}
			// Equipement désactivé, pas la peine de regénérer les commandes et d'envoyer la config des pins aux cartes/démons
			jeedouino::log( 'debug','L\'équipement ID '.$this->getId().' est désactivé. Pas la peine de continuer.');
			return;
		}

		$ModeleArduino = $this->getConfiguration('arduino_board');
		$PortArduino = $this->getConfiguration('datasource');
		$LocalArduino = $this->getConfiguration('arduinoport');

		if ($ModeleArduino == '') throw new Exception(__('Vous n\'avez pas défini de modèle de carte !.', __FILE__));

		//if ((($PortArduino=='rj45arduino') or ($PortArduino=='usbarduino')) and (($LocalArduino=='usblocal') or ($LocalArduino=='usbdeporte')))
		if (($ModeleArduino != '') and (($PortArduino == 'rj45arduino') or ($PortArduino == 'usbarduino')))
		{
			// ok une carte est definie
			list($Arduino_pins, $board, $usb) = self::GetPinsByBoard($arduino_id);

			$CfgStep = config::byKey($arduino_id.'_EqCfgSaveStep', 'jeedouino', 0);
			if ($CfgStep == 1)
			{
				// On génère les sketchs
				if (($board == 'arduino') and (!$usb)) self::GenerateLanArduinoSketchFile($arduino_id);
				if (($board == 'arduino') and ($usb)) self::GenerateUSBArduinoSketchFile($arduino_id);
				if (($board == 'esp') and (!$usb)) self::GenerateESP8266SketchFile($arduino_id);
				config::save($arduino_id.'_EqCfgSaveStep', 2, 'jeedouino');
				return;
			}
			elseif ($CfgStep == 2)
			{
				config::save($arduino_id.'-ForceStart', '1', 'jeedouino');
				config::save($arduino_id.'_EqCfgSaveStep', 3, 'jeedouino');
			}

			// Jeedouino seul sur rpi
			$JeedouinoAlone = $this->getConfiguration('alone');
			if ($JeedouinoAlone == '1')	// Jeedouino sur un Rpi sans Jeedom.
			{
				// cas spécial de l'arduino en usb déporté car faut lui trouver l'ip de son rpi hôte
				if (($PortArduino=='usbarduino') and ($LocalArduino=='usbdeporte'))
				{
					$portusbdeporte = $this->getConfiguration('portusbdeporte');
					$p=strpos($portusbdeporte,'_');
					if ($p) // Pas de vérification stricte car je ne veux pas de position à 0 non plus
					{
						$PortDemon = $this->getConfiguration('PortDemon');
						$IPArduino=substr($portusbdeporte,0,$p);   // On recupère l'IP
						$this->setConfiguration('iparduino',$IPArduino);  // On la sauve pour le démon
						$this->save(true);
						jeedouino::log( 'debug','Démon déporté - IPArduino ArduinoUsb (eqID '.$arduino_id.') : '.$IPArduino.':'.$PortDemon);
					}
					else
					{
						throw new Exception(__('Impossible de trouver l\'IP du démon déporté: ', __FILE__) .$portusbdeporte);
					}
				}
				// fin cas
				$wwwPort = 80;	// port ecoute page php JeedouinoExt.php
				$JeedomIP = self::GetJeedomIP();
				$JeedomPort = self::GetJeedomPort();
				$JeedomCPL= self::GetJeedomComplement();
				$ipPort = $this->getConfiguration('ipPort');
				$prm = json_encode(array('IP' => $JeedomIP, 'Port' => $JeedomPort, 'Cpl' => $JeedomCPL));
				$reponse = self::CallJeedouinoExt($arduino_id, 'SetJeedomCFG', $prm, $wwwPort ); // EqLogic, Cmd, Array Params, default port www
				if ($reponse!='OK') jeedouino::log( 'error','Pb Envoi cmd SetJeedomCFG sur Jeedouino déporté eqID ( '.$arduino_id.' ) - Réponse :'.$reponse);

				$ToSend = false;
				$_ProbeDelay = config::byKey($arduino_id . '_ProbeDelay', 'jeedouino', '5');
				if ($JeedomCPL == '') $JeedomCPL = '.';
				if ($board == 'arduino' and $usb)
				{
					$DemonName = 'USB';
					$PortDemon = $this->getConfiguration('PortDemon');
					if ($LocalArduino=='usblocal') $portUSB=$this->getConfiguration('portusblocal');
					else
					{
						$p=strpos($portusbdeporte,'_');
						if ($p) // Pas de verification stricte car je ne veut pas de position a 0 non plus
						{
							$portUSB=substr($portusbdeporte,$p+1);
						}
						else
						{
							throw new Exception(__('Impossible de trouver le port USB du démon déporté: ', __FILE__) .$portusbdeporte);
						}
					}
					$ip = $this->getConfiguration('iparduino');
					$UsbMap=config::byKey('uMap-'.$ip, 'jeedouino', '');
					if (is_array($UsbMap))  $portUSB = $UsbMap[$portUSB];
					else $portUSB = '"'.$portUSB.'"';
					$baudrate = 115200;
					if (config::byKey($arduino_id . '_SomfyRTS', 'jeedouino', 0)) $baudrate /=  2;
					$setprm = $PortDemon.' '.$portUSB.' '.$arduino_id.' '.$JeedomIP.' '.$JeedomPort.' '.$JeedomCPL.' '.$baudrate.' '.$_ProbeDelay;
					$ToSend = true;
				}
				if ($board == 'piface')
				{
					$DemonName = 'PiFace';
					$PiBoardID = $this->getConfiguration('PortID');		 // No de carte piface ( si plusieurs sur même RPI)
					$setprm = $ipPort.' '.$arduino_id.' '.$JeedomIP.' '.$PiBoardID.' '.$JeedomPort.' '.$JeedomCPL;
					$ToSend = true;
				}
				if ($board == 'gpio')
				{
					$DemonName = 'PiGpio';
					$setprm = $ipPort.' '.$arduino_id.' '.$JeedomIP.' '.$JeedomPort.' '.$JeedomCPL.' '.$_ProbeDelay;
					$ToSend = true;
				}
				if ($board == 'piplus')
				{
					$DemonName = 'PiPlus';
					$PiBoardID = $this->getConfiguration('PortI2C');
					$setprm = $ipPort.' '.$arduino_id.' '.$JeedomIP.' '.$PiBoardID.' '.$JeedomPort.' '.$JeedomCPL;
					$ToSend = true;
				}
				if ($ToSend)
				{
					$prm = json_encode(array('board_id' => $arduino_id, 'DemonName' => $DemonName, 'prm' => $setprm));
					$reponse = self::CallJeedouinoExt($arduino_id, 'SetPRM', $prm, $wwwPort ); // EqLogic, Cmd, Array Params, default port www
					if ($reponse!='OK') jeedouino::log( 'error','Pb Envoi cmd SetPRM sur Jeedouino'.$DemonName.'.py déporté eqID ( '.$arduino_id.' ) - Réponse :'.$reponse);
				}
			}
			//

			// changement de modèle de carte
			if (config::byKey($arduino_id.'-ForceSuppr', 'jeedouino', '0'))
			{
				jeedouino::log( 'debug','EqID '.$arduino_id.' Effacement de toutes les commandes suite au changement de modèle de la carte.');
				foreach ($this->getCmd() as $cmd)
				{
					// nettoyage des pins paramêtrées
					$pins_id=$cmd->getConfiguration('pins_id');
					config::save($arduino_id.'_'. $pins_id, 'not_used', 'jeedouino');
					config::save('GT_'.$arduino_id.'_'. $pins_id, '', 'jeedouino');
					config::save('GV_'.$arduino_id.'_'. $pins_id, '', 'jeedouino');

					// Netttoyage des virtuels
					if (config::byKey('ActiveVirtual', 'jeedouino', false)) jeedouino::DelCmdOfVirtual($cmd, $cmd->getLogicalId());
					// nettoyage des commandes afférentes
					jeedouino::log( 'debug','Suppression de : '. json_encode($cmd->getLogicalId()));
					$cmd->remove();
				}
				config::save($arduino_id.'-ForceSuppr', '0', 'jeedouino');	// pas forcément utile
			}

			$DHTxx = 0;			// Pour génération sketch
			$DS18x20 = 0;		// Pour génération sketch
			$teleinfoRX = 0;		// Pour génération sketch
			$teleinfoTX = 0;		// Pour génération sketch
			$Send2LCD = 0;		// Pour génération sketch
			$UserSketch = 0;	// Pour génération sketch
			$SomfyRTS = 0;		// Pour génération sketch
			$bmp180 = 0;			// Pour génération sketch
			$servo = 0;				// Pour génération sketch
			$WS2811 = false;	// Pour génération sketch (a cause de la pin 0 sur esp...)

			jeedouino::log( 'debug','EqID '.$arduino_id.' Création de la liste des commandes.');
			//sleep(2);	//
			$cmd_list = array();
			$old_list = array();
			$list_order_nb=1;
			foreach ($Arduino_pins as $pins_id => $pin_datas)
			{
				$double_cmd = '';
				if (($PortArduino == 'rj45arduino') and ($pin_datas['ethernet'] == '1'))
				{
					config::save($arduino_id . '_' . $pins_id, 'not_used', 'jeedouino');
					config::save('GT_' . $arduino_id . '_' . $pins_id, '', 'jeedouino');
					config::save('GV_' . $arduino_id . '_' . $pins_id, '', 'jeedouino');
					continue;
				}
				$myPin 			= config::byKey($arduino_id . '_' . $pins_id, 'jeedouino', 'not_used');
				$generic_type 	= config::byKey('GT_' . $arduino_id . '_' . $pins_id, 'jeedouino', '');
				$virtual 		= config::byKey('GV_' . $arduino_id . '_' . $pins_id, 'jeedouino', '');
				//jeedouino::log( 'debug','config::byKey '.$arduino_id.'_'. $pins_id.'  '. "myPin : ".$myPin);
				if ($myPin != 'not_used')
				{
					switch ($myPin)
					{
						case 'SomfyRTS':
							$SomfyRTS = $pins_id;
							$myType='info';
							$mySubType='string';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
						break;
						case 'input_binary':
							$myType='info';
							$mySubType='binary';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
						break;
						case 'input_numeric':
							$myType='info';
							$mySubType='numeric';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
						break;
						case 'input_string':
							$myType='info';
							$mySubType='string';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
						break;
						case 'output_other':
							$myType='action';
							$mySubType='other';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
						break;
						case 'output_slider':
							$myType='action';
							$mySubType='slider';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
						break;
						case 'output_message':
							$myType='action';
							$mySubType='message';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
						break;
						case 'Send2LCD':
							$myType='action';
							$mySubType='message';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
							$Send2LCD = $pins_id;
						break;
						case 'teleinfoRX':
							$myType='info';
							$mySubType='string';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
							$teleinfoRX = $pins_id;
						break;
						case 'teleinfoTX':
							$myType='action';
							$mySubType='message';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
							$teleinfoTX = $pins_id;
						break;
						case 'trigger':
							$myType='action';
							$mySubType='other';
							$myinvertBinary='0';
							$tempo='00000';
							$value='1';
						break;
						case 'echo':
							$myType='info';
							$mySubType='numeric';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
						break;
						case 'input':
							$myType='info';
							$mySubType='binary';
							$myinvertBinary='1';
							$tempo='0';
							$value='0';
						break;
						case 'dht11':
						case 'dht21':
						case 'dht22':
							$myType = 'info';
							$mySubType = 'numeric';
							$myinvertBinary = '0';
							$tempo = '0';
							$value = '0';
							$double_cmd = $myPin . '_h';
							$value2 = '0';
							$DHTxx = 1;			// Pour génération sketch
						break;
						case 'bmp180':
							$myType = 'info';
							$mySubType = 'numeric';
							$myinvertBinary = '0';
							$tempo = '0';
							$value = '0';
							$double_cmd = $myPin . '_p';
							$value2 = '0';
							$bmp180 = 1;			// Pour génération sketch
						break;
						case 'ds18b20':
							$myType='info';
							$mySubType='numeric';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
							$DS18x20=1;	// Pour génération sketch
						break;
						case 'pwm_input':
							$myType='info';
							$mySubType='numeric';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
						break;
						case 'input_pullup':
							$myType='info';
							$mySubType='binary';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
						break;
						case 'bp_input':
						case 'bp_input_pullup':
						case 'analog_input':
							$myType='info';
							$mySubType='numeric';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
						break;
						case 'output':
							$myType='action';
							$mySubType='other';
							$myinvertBinary='0';
							$tempo='0';
							$value='1';
						break;
						case 'switch':
							$myType='action';
							$mySubType='other';
							$myinvertBinary='0';
							$tempo='0';
							$value='1';
						break;
						case 'compteur_pullup':
							$myType='info';
							$mySubType='numeric';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
						break;
						case 'low_relais':
							$myType='action';
							$mySubType='other';
							$myinvertBinary='0';
							$tempo='0';
							$value='0';
							$double_cmd='high_relais';
							$value2='1';
						break;
						case 'high_relais':
							$myType='action';
							$mySubType='other';
							$myinvertBinary='0';
							$tempo='0';
							$value='1';
							$double_cmd='low_relais';
							$value2='0';
						break;
						case 'output_pulse':
							$myType='action';
							$mySubType='other';
							$myinvertBinary='0';
							$tempo='0007';
							$value='1';
						break;
						case 'low_pulse':
							$myType='action';
							$mySubType='other';
							$myinvertBinary='0';
							$tempo='00007';
							$value='0';
							$double_cmd='high_relais';
							$value2='1';
						break;
						case 'low_pulse_slide':
							$myType='action';
							$mySubType='slider';
							$myinvertBinary='0';
							$tempo='00007';
							$value='0';
							$double_cmd='high_relais';
							$value2='1';
						break;
						case 'high_pulse':
							$myType='action';
							$mySubType='other';
							$myinvertBinary='0';
							$tempo='00007';
							$value='1';
							$double_cmd='low_relais';
							$value2='0';
						break;
						case 'high_pulse_slide':
							$myType='action';
							$mySubType='slider';
							$myinvertBinary='0';
							$tempo='00007';
							$value='1';
							$double_cmd='low_relais';
							$value2='0';
						break;
						case 'WS2811':
							$WS2811 = $pins_id;
							$myType='action';
							$mySubType='color';
							$myinvertBinary='0';
							$tempo='00007';
							$value='0';
							$double_cmd='WSmode';
							$value2='0';
						break;
						case 'servo':
							$servo = 1;			// Pour génération sketch
						case 'pwm_output':
							$myType='action';
							$mySubType='slider';
							$myinvertBinary='0';
							$tempo='0';
							$value='127';
						break;
						default:
							continue;
						break;
					}

					$LogicalId = 'ID'.$pins_id; // $pin_datas['Nom_pin']
					$ID_pins = $pins_id;
					//Correctif noms des pins pour NodeMCU
					if ($ModeleArduino == 'espMCU01' and $pins_id < 500) $ID_pins = $pin_datas['Nom_pin'];
					if ($pins_id >= 500) $UserSketch = 1;// Pour génération sketch

					$myPinN = $myPin;
					if ($myPin == 'low_relais') $myPinN = 'low_pin';
					if ($myPin == 'high_relais') $myPinN = 'high_pin';
					$double_cmdN=$double_cmd;
					if ($double_cmd == 'low_relais') $double_cmdN = 'low_pin';
					if ($double_cmd == 'high_relais') $double_cmdN = 'high_pin';

					//Correctif noms des pins HLW8012 pour SONOFF POW
					if ($ModeleArduino == 'espsonoffpow' and $pins_id > 0 and $pins_id < 6) $myPinN = $pin_datas['Nom_pin'];

					//$double_tmp = '';
					//if ($double_cmd != '') $double_tmp=$double_cmd;

					$cmd_list[$LogicalId . 'a'] = array('name' 			=> __($ID_pins . '_' . $myPinN, __FILE__) ,
														'type' 				=> $myType,
														'subtype' 			=> $mySubType,
														'tempo' 			=> $tempo,
														'value' 				=> $value,
														'modePIN' 		=> $myPin,
														'double_cmd' 	=> $double_cmd,
														'double_key' 	=> $LogicalId . 'b',
														'pins_id' 			=> $pins_id,
														'invertBinary'	=> $myinvertBinary,
														'generic_type'	=> $generic_type,
														'virtual'				=> $virtual,
														'order'				=> $list_order_nb
														);
					$old_list[$pin_datas['Nom_pin']] = $LogicalId . 'a';
					if ($double_cmd!='')
					{
						// cas du low_pulse_slide et du high_pulse_slide
						if ($myPin == 'low_pulse_slide' or $myPin == 'high_pulse_slide') $mySubType = 'other';
						// cas du WS2811 (led strip)
						if ($myPin == 'WS2811' ) $mySubType = 'slider';

						// Tentative d'adapter le generic_type pour la double commande
						if (strpos($generic_type, '_ON') !== false) 		$generic_type = str_replace('_ON', '_OFF', $generic_type);
						elseif (strpos($generic_type, '_OFF') !== false) 	$generic_type = str_replace('_OFF', '_ON', $generic_type);
						if (strpos($generic_type, '_UP') !== false) 		$generic_type = str_replace('_UP', '_DOWN', $generic_type);
						elseif (strpos($generic_type,'_DOWN') !== false) 	$generic_type = str_replace('_DOWN', '_UP', $generic_type);
						if (strpos($generic_type, '_OPEN') !== false)	 	$generic_type = str_replace('_OPEN', '_CLOSE', $generic_type);
						elseif (strpos($generic_type, '_CLOSE') !== false) 	$generic_type = str_replace('_CLOSE', '_OPEN', $generic_type);
 						$list_order_nb++;
						$cmd_list[$LogicalId . 'b'] = array('name' 				=> __($ID_pins . '_' . $double_cmdN, __FILE__) ,
															'type' 				=> $myType,
															'subtype' 			=> $mySubType,
															'tempo' 			=> $tempo,
															'value' 				=> $value2,
															'modePIN' 		=> $double_cmd,
															'double_cmd'	=> '',
															'double_key' 	=> $LogicalId . 'a',
															'pins_id' 			=> $pins_id + 1000,
															'invertBinary'	=> $myinvertBinary,
															'generic_type'	=> $generic_type,
															'virtual'				=> $virtual,
															'order'				=> $list_order_nb
															);
						$old_list[$pin_datas['Nom_pin'].'2'] = $LogicalId.'b';
						$double_cmd='';
					}
					if (($myType == 'action') and ($mySubType == 'other' or $mySubType == 'slider') and ($myPin != 'trigger') and ($myPin != 'servo') and ($myPin != 'WS2811'))
					{
						// Tentative d'adapter le generic_type pour le retour d'etat
						$GT = array('_ON', '_OFF', '_UP', '_DOWN', '_TOGGLE', '_OPEN', '_CLOSE', '_SET_STATE');
						$replace_type = str_replace($GT, '_STATE', $generic_type);
						//jeedouino::log( 'debug',$ID_pins .' - replace_type = '.$replace_type.' :: generic_type = '.$generic_type);
						switch ($generic_type)
						{
							case '0':
							case '':
							case 'configGT':
							case 'DONT':	// Ne rien faire.
								break;
							case $replace_type: // G.T info non trouvée donc on met une générique
								$generic_type = 'GENERIC_INFO';
								break;
							default: // G.T info trouvée
								$generic_type = $replace_type;
						}
						//jeedouino::log( 'debug',$ID_pins .' - replace_type = '.$replace_type.' :: generic_type = '.$generic_type);
						$list_order_nb++;
						if ($mySubType == 'other') $mySubType = 'binary';
						if ($mySubType == 'slider') $mySubType = 'numeric';
						$cmd_list[$LogicalId . 'i'] = array(	'name' 			=> __('Etat_Pin_' . $ID_pins, __FILE__) ,
																'type' 				=> 'info',
																'subtype' 			=> $mySubType,
																'tempo' 			=> '0',
																'value' 				=> '0',
																'modePIN' 		=> $myPin,
																'double_cmd' 	=> '',
																'double_key' 	=> '',
																'pins_id' 			=> $pins_id,
																'invertBinary'	=> $myinvertBinary,
																'generic_type'	=> $generic_type,
																'virtual'				=> $virtual,
																'order'				=> $list_order_nb
															);
						$old_list[$pin_datas['Nom_pin'] . 'i'] = $LogicalId . 'i';

					}

				}
				else
				{
					config::save('GT_'.$arduino_id.'_'. $pins_id, '', 'jeedouino');
					config::save('GV_'.$arduino_id.'_'. $pins_id, '', 'jeedouino');
				}
				$list_order_nb++;
			}

			config::save($arduino_id.'_DHTxx', $DHTxx, 'jeedouino');			// Pour génération sketch
			config::save($arduino_id.'_DS18x20', $DS18x20, 'jeedouino');		// Pour génération sketch
			config::save($arduino_id.'_TeleInfoRX', $teleinfoRX, 'jeedouino');	// Pour génération sketch
			config::save($arduino_id.'_TeleInfoTX', $teleinfoTX, 'jeedouino');	// Pour génération sketch
			config::save($arduino_id.'_Send2LCD', $Send2LCD, 'jeedouino');		// Pour génération sketch
			config::save($arduino_id.'_UserSketch', $UserSketch, 'jeedouino');	// Pour génération sketch
			config::save($arduino_id.'_SomfyRTS', $SomfyRTS, 'jeedouino');		// Pour génération sketch
			config::save($arduino_id.'_BMP180', $bmp180, 'jeedouino');			// Pour génération sketch
			config::save($arduino_id.'_SERVO', $servo, 'jeedouino');			// Pour génération sketch
			config::save($arduino_id.'_WS2811', $WS2811, 'jeedouino');			// Pour génération sketch
			

			if ($this->getConfiguration('ActiveCmdAll'))
			{
				$cmd_list['ALLON'] = array(		'name' 				=> __('ALL_LOW', __FILE__) ,
																'type' 				=> 'action',
																'subtype' 			=> 'other',
																'tempo' 			=> '0',
																'value' 				=> '0',
																'modePIN' 		=> 'none',
																'double_cmd' 	=> '',
																'double_key' 	=> '',
																'pins_id' 			=> '990',
																'invertBinary'	=> '0',
																'generic_type'	=> 'GENERIC_ACTION',
																'virtual'				=> '',
																'order'				=> $list_order_nb
																);
				$old_list['ALL_ON'] = 'ALLON';
				$list_order_nb++;
				$cmd_list['ALLOFF'] = array(	'name' 			=> __('ALL_HIGH', __FILE__) ,
																'type' 				=> 'action',
																'subtype' 			=> 'other',
																'tempo' 			=> '0',
																'value' 				=> '1',
																'modePIN' 		=> 'none',
																'double_cmd' 	=> '',
																'double_key' 	=> '',
																'pins_id' 			=> '991',
																'invertBinary'	=> '0',
																'generic_type'	=> 'GENERIC_ACTION',
																'virtual'				=> '',
																'order'				=> $list_order_nb
																);
				$old_list['ALL_OFF'] = 'ALLOFF';
				$list_order_nb++;
				$cmd_list['ALLSWT'] = array(	'name' 			=> __('ALL_SWITCH', __FILE__) ,
																'type' 				=> 'action',
																'subtype' 			=> 'other',
																'tempo' 			=> '0',
																'value' 				=> '1',
																'modePIN' 		=> 'none',
																'double_cmd' 	=> '',
																'double_key' 	=> '',
																'pins_id' 			=> '992',
																'invertBinary'	=> '0',
																'generic_type'	=> 'GENERIC_ACTION',
																'virtual'				=> '',
																'order'				=> $list_order_nb
																);
				$list_order_nb++;
				$cmd_list['ALLPLSELOW'] = array(	'name' 			=> __('ALL_PULSE_LOW', __FILE__) ,
																'type' 				=> 'action',
																'subtype' 			=> 'other',
																'tempo' 			=> '00007',
																'value' 				=> '0',
																'modePIN' 		=> 'low_pulse',
																'double_cmd' 	=> '',
																'double_key' 	=> '',
																'pins_id' 			=> '993',
																'invertBinary'	=> '0',
																'generic_type'	=> 'GENERIC_ACTION',
																'virtual'				=> '',
																'order'				=> $list_order_nb
																);
				$list_order_nb++;
				$cmd_list['ALLPLSEHIGH'] = array(	'name' 			=> __('ALL_PULSE_HIGH', __FILE__) ,
																'type' 				=> 'action',
																'subtype' 			=> 'other',
																'tempo' 			=> '00007',
																'value' 				=> '1',
																'modePIN' 		=> 'high_pulse',
																'double_cmd' 	=> '',
																'double_key' 	=> '',
																'pins_id' 			=> '994',
																'invertBinary'	=> '0',
																'generic_type'	=> 'GENERIC_ACTION',
																'virtual'				=> '',
																'order'				=> $list_order_nb
																);

			}

			$modif_cmd = false; //  // ne renvoi pas la config a la carte
			jeedouino::log( 'debug','EqID '.$arduino_id.' Effacement des commandes obsolètes.');
			foreach ($this->getCmd() as $cmd)
			{
				$Lid = $cmd->getLogicalId();
				if (!isset($cmd_list[$Lid]))
				{
					if (isset($old_list[$Lid]))	// Ancien LogicalId, màj nécéssaire.
					{
						$cmd->setLogicalId($old_list[$Lid]);
						$cmd->save();
						jeedouino::log( 'debug','Màj du LogicalId de : '.$Lid.' vers '.$old_list[$Lid]);
					}
					else
					{
						if (config::byKey('ActiveVirtual', 'jeedouino', false)) jeedouino::DelCmdOfVirtual($cmd, $Lid);
						jeedouino::log( 'debug','Suppression de : '. json_encode($cmd->getLogicalId()));
						$cmd->remove();
						$modif_cmd = true; // Renvoi la config a la carte
					}
				}
			}
/* 			jeedouino::log( 'debug','Debut - Liste des commandes');
			foreach ($this->getCmd() as $cmd)
			{
				jeedouino::log( 'debug','== EqID '.$arduino_id.' $cmd->getLogicalId() = '.$cmd->getLogicalId());
				jeedouino::log( 'debug','== EqID '.$arduino_id.' $cmd->getName() = '.$cmd->getName());
				//jeedouino::log( 'debug','== EqID '.$arduino_id.' $cmd->exportApi() = '.json_encode($cmd->exportApi()));
			}
			jeedouino::log( 'debug','Fin - Liste des commandes');   */

			jeedouino::log( 'debug','EqID '.$arduino_id.' Création des nouvelles commandes, MàJ des autres..');

			foreach ($cmd_list as $key => $cmd_info)
			{
				$cmd = $this->getCmd(null, $key);  // public function getCmd($_type = null, $_logicalId = null, $_visible = null, $_multiple = false)
				$create_cmd = false;
				if (!is_object($cmd))
				{
					$create_cmd = true;
				}
				else
				{
					//jeedouino::log( 'debug','EqID '.$arduino_id.' else $create_cmd=true. - '.$key.' => '.json_encode($cmd_info));
					//jeedouino::log( 'debug','EqID '.$arduino_id.' $cmd_list($key) : '.$key.' => $cmd->getName() : '.$cmd->getName().' ( '.$cmd->getLogicalId().' ) ');

					// on verifie au cas ou le mode d'une pin a changé
					if ($cmd_info['modePIN'] == 'trigger') $modif_cmd = true; // envoi nécéssaire.

					if ($cmd->getConfiguration('modePIN') != $cmd_info['modePIN'])
					{
						$create_cmd = true;
						jeedouino::log(  'debug',"Mode Pin ".$cmd->getConfiguration('modePIN').' changé pour  '.$cmd_info['modePIN']);
					}
					elseif ($cmd->getSubType() != $cmd_info['subtype'])
					{
						$create_cmd = true;
						jeedouino::log(  'debug',"SubType ".$cmd->getSubType().' changé pour  '.$cmd_info['subtype']);
					}
					elseif ($cmd->getType() != $cmd_info['type'])
					{
						$create_cmd = true;
						jeedouino::log(  'debug',"Type ".$cmd->getType().' changé pour  '.$cmd_info['type']);
					}
					if ($create_cmd)
					{
						//jeedouino::log(  'debug','Double_cmd : '.$cmd_info['name']);
						foreach ($this->getCmd() as $cmd_tmp)
						{
							$Lid = $cmd_tmp->getLogicalId();
							// jeedouino::log(  'debug','*** Lid : '.$Lid);

							if ($cmd_info['double_key'] == $Lid or $key == $Lid)
							{
								// jeedouino::log(  'debug','*** key : '.$key);
								// jeedouino::log(  'debug','*** double_key : '.$cmd_info['double_key']);
								if (config::byKey('ActiveVirtual', 'jeedouino', false)) jeedouino::DelCmdOfVirtual($cmd_tmp, $Lid);
								//if (!config::byKey('MultipleCmd', 'jeedouino', false))
								$cmd_tmp->remove();
								//else if (substr($Lid, -1) == 'i') $cmd_tmp->remove();
							}
						}
						unset($cmd);
					}
				}
				if ($create_cmd)
				{
					jeedouino::log( 'debug','Création de : '. $cmd_info['name']);
					$modif_cmd = true; // Renvoi la config a la carte

					$cmd = new jeedouinoCmd();
					$cmd->setName($cmd_info['name']);
					$cmd->setConfiguration('value',$cmd_info['value']);
					$cmd->setConfiguration('tempo',$cmd_info['tempo']);
					$cmd->setConfiguration('modePIN',$cmd_info['modePIN']);
					if ($cmd_info['modePIN'] == 'teleinfoTX' or $cmd_info['modePIN'] == 'teleinfoRX') $cmd->setIsVisible(0);
					else $cmd->setIsVisible(1);
					$cmd->setDisplay('invertBinary',$cmd_info['invertBinary']);	// Affichage
					$cmd->setDisplay('invertBinare',$cmd_info['invertBinary']);	// Valeur interne
					$cmd->setLogicalId($key);
				}
				$order = $cmd_info['order'];
				if ($order > 999) $order -= 1000;
				$cmd->setOrder($order);

				$generic_type = $cmd_info['generic_type'];
				switch ($cmd_info['modePIN'])
				{
					case 'compteur_pullup':
						$cmd->setTemplate('dashboard', 'tile');
						$cmd->setTemplate('mobile', 'tile');
						$generic_type = 'GENERIC_INFO';
						break;
					case 'pwm_output':
					case 'servo':
						$cmd->setTemplate('dashboard', 'default');
						$cmd->setTemplate('mobile', 'default');
						$generic_type = 'LIGHT_SLIDER';
						if ($cmd_info['type'] == 'info') $generic_type = 'LIGHT_STATE';
						break;
					case 'WS2811':
						$cmd->setTemplate('dashboard', 'default');
						$cmd->setTemplate('mobile', 'default');
						$generic_type = 'LIGHT_SET_COLOR';
						if ($cmd_info['type'] == 'info') $generic_type = 'LIGHT_COLOR';
						break;
					case 'WSmode':
						$cmd->setTemplate('dashboard', 'default');
						$cmd->setTemplate('mobile', 'default');
						$generic_type = 'LIGHT_MODE';
						break;
					case 'dht11':
					case 'dht21':
					case 'dht22':
					case 'ds18b20':
					case 'bmp180':
						$cmd->setTemplate('dashboard', 'thermometre');
						$cmd->setTemplate('mobile', 'default');
						$cmd->setUnite('°C');
						$generic_type = 'TEMPERATURE';
						break;
					case 'dht11_h':
					case 'dht21_h':
					case 'dht22_h':
						$cmd->setTemplate('dashboard', 'humidite');
						$cmd->setTemplate('mobile', 'default');
						$cmd->setUnite('%');
						$generic_type = 'HUMIDITY';
						break;
					case 'bmp180_p':
						$generic_type = 'PRESSURE';
						break;
					case 'output_other':
					case 'output_slider':
					case 'output_message':
					case 'Send2LCD':
					case 'trigger':
					case 'output_pulse':
					case 'low_pulse':
					case 'high_pulse':
					case 'low_pulse_slide':
					case 'high_pulse_slide':
					case 'low_relais':
					case 'high_relais':
					case 'output':
						$generic_type = 'GENERIC_ACTION';
						if ($cmd_info['type'] == 'info') $generic_type = 'GENERIC_INFO';
						break;
					case 'teleinfoTX':
						$generic_type = 'DONT';
						break;
					case 'input_binary':
					case 'input_numeric':
					case 'input_string':
					case 'teleinfoRX':
					case 'echo':
					case 'input':
					case 'input_pullup':
					case 'analog_input':
					case 'pwm_input':
						$generic_type = 'GENERIC_INFO';
						break;
					case 'switch':
						$generic_type = 'LIGHT_TOGGLE';
						if ($cmd_info['type'] == 'info') $generic_type = 'LIGHT_STATE';
						break;
				}
				switch ($cmd_info['modePIN'])
				{
					case 'pwm_output':
						$cmd->setConfiguration('minValue',0);
						$cmd->setConfiguration('maxValue',255);
						break;
					case 'servo':
						$cmd->setConfiguration('minValue',0);
						$cmd->setConfiguration('maxValue',180);
						break;
					case 'WSmode':
						$cmd->setConfiguration('minValue',0);
						$cmd->setConfiguration('maxValue',17);
						break;
					case 'compteur_pullup':
						if ($cmd->getConfiguration('RSTvalue')!='') $cmd->setConfiguration('value',$cmd->getConfiguration('RSTvalue'));
						break;
				}
			//	jeedouino::log( 'debug', $cmd_info['name'] . ' - 1 - $generic_type : '. $generic_type);
			//	jeedouino::log( 'debug', $cmd_info['name'] . ' - 1 - $cmd_info[generic_type] : '. $cmd_info['generic_type']);
				switch ($cmd_info['generic_type'])
				{
					case '0': // Auto
					case '':
						if ($generic_type == '0') $generic_type = '';
/* 						$type_generic = $cmd->getDisplay('generic_type');
						if ($type_generic == '' or $type_generic == '0')  $cmd->setDisplay('generic_type', $generic_type);
						else $cmd->setDisplay('generic_type', $type_generic); */
						$cmd->setDisplay('generic_type', $generic_type);

				//		jeedouino::log( 'debug', $cmd_info['name'] . ' - 2 - $generic_type : '. $generic_type);
				//		jeedouino::log( 'debug', $cmd_info['name'] . ' - 2 - $type_generic : '. $type_generic);
						break;
					case 'configGT':
						break;
					case 'DONT': // Ne rien faire.
						//break;
					default: // Choix user
						$cmd->setDisplay('generic_type', $cmd_info['generic_type']);
				//		jeedouino::log( 'debug', $cmd_info['name'] . ' - 2 - $cmd_info[generic_type] : '. $cmd_info['generic_type']);
				}

				$cmd->setConfiguration('pins_id',$cmd_info['pins_id']);
				$cmd->setSubType($cmd_info['subtype']);
				$cmd->setType($cmd_info['type']);
				$cmd->setEqLogic_id($this->getId());
				$cmd->save();

				if ($cmd_info['type']=='info') // après 'save' pour avoir l'id
				{
					//$cmd->setEventOnly(1);
					//$cmd->setValue(null);	//
					$cmd->setValue($cmd->getId());
					$cmd->save();
					// On va indiquer aux actions l'etat parent pour l'app mobile
					$cmd2 = $this->getCmd(null, 'ID'.$cmd_info['pins_id'].'a');
					if (is_object($cmd2))
					{
						$cmd2->setValue($cmd->getId());
						$cmd2->save();
					}
					$cmd2 = $this->getCmd(null, 'ID'.$cmd_info['pins_id'].'b');
					if (is_object($cmd2))
					{
						$cmd2->setValue($cmd->getId());
						$cmd2->save();
					}
				}
				if (config::byKey('ActiveVirtual', 'jeedouino', false)) jeedouino::AddCmdToVirtual($cmd, $cmd_info['virtual'], $key);
			}

			// On envoie la config à l'Arduino/Piface/PiGPIO/ESP
			$_ForceStart = config::byKey($arduino_id.'-ForceStart', 'jeedouino', '0');

			if ($this->getConfiguration('alone') == '1')  $_ForceStart = true;
			if (($board == 'arduino') and (!$usb)) $_ForceStart = true;
			if (($board == 'esp') and (!$usb)) $_ForceStart = true;

			if ($modif_cmd or $_ForceStart) self::ConfigurePinMode($_ForceStart);
			// On génère les sketchs
			if (($board == 'arduino') and (!$usb)) self::GenerateLanArduinoSketchFile($arduino_id);
			if (($board == 'arduino') and ($usb)) self::GenerateUSBArduinoSketchFile($arduino_id);
			if (($board == 'esp') and (!$usb)) self::GenerateESP8266SketchFile($arduino_id);

		}
		else throw new Exception(__('Vous n\'avez pas défini la connection de la carte (Réseau / Usb: Local/Déporté)) !.', __FILE__));

		jeedouino::log( 'debug','Fin de postSave()');
	}
	public function postAjax()
	{
		jeedouino::log( 'debug','Debut de postAjax()');

		if ($this->getConfiguration('AutoOrder') == '1')
		{
			foreach ($this->getCmd() as $cmd)
			{
				$order = $cmd->getConfiguration('pins_id');
				if ($order > 999) $order -= 1000;
				$cmd->setOrder($order);
				$cmd->save();
			}
		}

		jeedouino::log( 'debug','Fin de postAjax()');
	}
	public function DelCmdOfVirtual($cmd_def, $LogicalId)
	{
		if (method_exists('virtual', 'copyFromEqLogic'))
		{
			//$LogicalId = 'JeedouinoEQ' . $cmd_def->getEqLogic_id() . 'CMD' . $cmd_def->getConfiguration('pins_id') . $cmd_def->getType();
			$LogicalId = 'JeedouinoEQ' . $cmd_def->getEqLogic_id() . 'CMD' . $LogicalId;
			//jeedouino::log( 'debug','Virtuel - Suppression de : '. $LogicalId);
			$Vcmd = cmd::byLogicalId($LogicalId);	// Renvoie un array de tous les objets 'cmd' (ici je n'en veux qu'une donc la '0')
			if (isset($Vcmd[0])) $Vcmd = $Vcmd[0];
			if (is_object($Vcmd))
			{
				jeedouino::log( 'debug', 'Commande "' . $Vcmd->getName() . '" du virtuel "' . virtual::byId($Vcmd->getEqLogic_id())->getName() . '" supprimée. ');
				$Vcmd->remove();
			}
		}
		else jeedouino::log( 'error', 'Impossible de trouver le plugin Virtuel !');
	}

	public function AddCmdToVirtual($cmd_def, $eq_id,  $LogicalId)
	{
		if (method_exists('virtual', 'copyFromEqLogic'))
		{
			if ($eq_id == '' or $eq_id == 'Aucun')
			{
				jeedouino::DelCmdOfVirtual($cmd_def, $LogicalId);
				return;
			}
			$eqLogic = virtual::byId($eq_id);
			if (!is_object($eqLogic)) jeedouino::log( 'error', 'Impossible de trouver le virtuel demandé eqID : ' . $eq_id);
			else
			{
				//$LogicalId = 'JeedouinoEQ' . $cmd_def->getEqLogic_id() . 'CMD' . $cmd_def->getConfiguration('pins_id') . $cmd_def->getType();
				$LogicalId = 'JeedouinoEQ' . $cmd_def->getEqLogic_id() . 'CMD' . $LogicalId;
				$cmd = cmd::byLogicalId($LogicalId);	// Renvoie un array de tous les objets 'cmd' (ici je n'en veux qu'une donc la '0')
				if (isset($cmd[0])) $cmd = $cmd[0];
				if (!is_object($cmd)) $cmd = new virtualCmd();
				$cmd->setLogicalId($LogicalId);
				//jeedouino::log( 'debug','Ajout  de : '. $LogicalId);
				$cmd->setName($cmd_def->getName());
				$cmd->setEqLogic_id($eqLogic->getId());
				$cmd->setIsVisible($cmd_def->getIsVisible());
				$cmd->setType($cmd_def->getType());
				$cmd->setUnite($cmd_def->getUnite());
				$cmd->setOrder($cmd_def->getOrder());
				$cmd->setDisplay('icon', $cmd_def->getDisplay('icon'));
				$cmd->setDisplay('invertBinary', $cmd_def->getDisplay('invertBinary'));
				$cmd->setDisplay('generic_type', $cmd_def->getDisplay('generic_type'));
				//foreach ($cmd_def->getTemplate() as $key => $value) $cmd->setTemplate($key, $value);
				$cmd->setSubType($cmd_def->getSubType());
				if ($cmd_def->getType() == 'info')
				{
					$cmd->setConfiguration('calcul', '#' . $cmd_def->getId() . '#');
					$cmd->setValue($cmd_def->getId());
				}
				else
				{
					$cmd->setValue($cmd_def->getValue());
					$cmd->setConfiguration('infoName', '#' . $cmd_def->getId() . '#');
				}
				try
				{
					jeedouino::log( 'debug', 'Commande "' . $cmd->getName() . '" ajoutée/modifiée dans le virtuel "' . $eqLogic->getName() . '". ');
					$cmd->save();
				}
				catch (Exception $e) {}
				if ($cmd_def->getType() == 'info')
				{
					// On met a jour les 'cmd' actions liées a cet info (après le 'save' pour avoir l'id dispo si création))
					$cmd2 = $eqLogic->getCmd(null, substr($LogicalId, 0, -1) . 'a');
					if (is_object($cmd2))
					{
						$cmd2->setValue($cmd->getId());
						$cmd2->save();
					}
					$cmd2 = $eqLogic->getCmd(null, substr($LogicalId, 0, -1) . 'b');
					if (is_object($cmd2))
					{
						$cmd2->setValue($cmd->getId());
						$cmd2->save();
					}
				}
			}
		}
		else jeedouino::log( 'error', 'Impossible de trouver le plugin Virtuel !');
	}

	public function ResetCPT($arduino_id, $RSTvalue=0, $CMDid='')
	{
		list(, $board) = self::GetPinsByBoard($arduino_id);

		//jeedouino::log( 'debug','LOG ResetCompteur pour ' . $board . ' eqID ( ' . $arduino_id . ' )' . ' RSTvalue ( ' . $RSTvalue . ' )' . ' CMDid ( ' . $CMDid . ' )');
		// on envoie une reinit aux compteurs
		if ($CMDid != '')
		{
			jeedouino::log( 'debug', 'Début de ResetCompteur pour ' . $board . ' eqID ( ' . $arduino_id . ' )');

			$cmd = cmd::byid($CMDid);
			$pins_id = $cmd->getConfiguration('pins_id');

			jeedouino::log( 'debug', 'Compteur sur pin n° ' . $pins_id . ' - Valeur de reset envoyée : ' . $RSTvalue);

			$cmd->setCollectDate('');
			$cmd->event($RSTvalue);
			$cmd->setConfiguration('value',$RSTvalue);
			$cmd->setConfiguration('RSTvalue',$RSTvalue);
			$cmd->save();

			switch ($board)
			{
				case 'arduino':
				case 'esp':
					sleep(2);
					self::SendToArduino($arduino_id, 'R' . sprintf("%02s", $pins_id) . $RSTvalue . 'C', 'ResetCompteur', 'SCOK');
					break;
				case 'piface':
				case 'gpio':
				case 'piplus':
					$message  = 'RazCPT=' . $pins_id;
					$message .= '&ValCPT=' . $RSTvalue;
					jeedouino::log( 'debug', $log_txt . $message);
					$reponse = jeedouino::SendToBoardDemon($arduino_id, $message, $board);
					if ($reponse != 'SCOK') jeedouino::log( 'error','ERREUR ENVOI ResetCompteur - Réponse :' . $reponse);
					break;
			}
			jeedouino::log( 'debug', 'Fin de ResetCompteur');
		}
	}

	public function GetJeedomIP() // on recupere l'adresse IP du maitre si dispo
	{
		if (self::Networkmode() == 'master')
		{
			if (config::byKey('ActiveJLAN', 'jeedouino', false))
			{
				$ip = strtolower(trim(config::byKey('IPJLAN', 'jeedouino', '')));

				if ($ip!='' and $ip!='127.0.0.1' and substr($ip,0,5) != 'local' and (filter_var( $ip , FILTER_VALIDATE_IP) !== false)) return $ip;
				// pas d'IP hôte alors erreur.
				jeedouino::log( 'error', __('L\'IP réelle de l hôte/NAS Jeedom Maître doit être renseignée dans la configuration du plugin. Merci. ', __FILE__));
			}
		}

		$ip = strtolower(trim(config::byKey('internalAddr')));
		if ($ip!='' and $ip!='127.0.0.1' and substr($ip,0,5) != 'local' and (filter_var( $ip , FILTER_VALIDATE_IP) !== false))  return $ip;

		$ip = strtolower(@$_SERVER['SERVER_ADDR']);
		if ($ip!='' and $ip!='127.0.0.1' and substr($ip,0,5) != 'local' and (filter_var( $ip , FILTER_VALIDATE_IP) !== false))  return $ip;

		// pas d'IP alors erreur.
		throw new Exception(__('L\'IP réelle du Jeedom Maître doit être renseignée dans Configuration -> Configuration réseaux -> Adresse IP. Merci. ', __FILE__));
	}

	public function GetJeedomComplement() // on récupère  le complément de l'adresse du maître si dispo
	{
		$cmpl = trim(config::byKey('internalComplement'));
		if ($cmpl == '')  return $cmpl;
		if (substr($cmpl, 0, 1) != '/') $cmpl = '/' . $cmpl;	// si il manque le premier /
		// petite verif
		$jeedouinoPATH = realpath(dirname(__FILE__));
		if (strpos($jeedouinoPATH, $cmpl) === false)
		{
			jeedouino::log( 'debug', 'Le complément ' . $cmpl . ' (configuration -> conf.réseaux-> accès interne) est introuvable dans le chemin de votre Jeedom : ' . $jeedouinoPATH);
			$cmpl ='';
		}
		return $cmpl;
	}

	public function GetJeedomPort() // on récupère  le port de l'adresse du maître si dispo
	{
		if (self::Networkmode() == 'master')
		{
			if (config::byKey('ActiveJLAN', 'jeedouino', false))
			{
				$port = trim(config::byKey('PORTJLAN', 'jeedouino', ''));
				if ($port != '') return $port;
				// pas de port hôte alors erreur.
				jeedouino::log( 'error', 'Le port de l hôte/NAS Jeedom Maître doit être renseignée dans la configuration du plugin. Merci. ');
			}
		}
		$port = trim(config::byKey('internalPort'));
		if ($port == '')  return '80';
		return $port;
	}

	public function GenerateLanArduinoSketchFile($board_id = '')	//  Génère le sketch pour l'arduino avec shield ethernet
	{
		$jeedouinoPATH = realpath(dirname(__FILE__) . '/../../sketchs/');
		if ($board_id != '')
		{
			jeedouino::log( 'debug','Génération du sketch Arduino LAN...');
			$SketchMasterFile=$jeedouinoPATH.'/JeedouinoLAN.ino';
			$SketchFileName=$jeedouinoPATH.'/JeedouinoLAN_'.$board_id.'.ino';
			jeedouino::log( 'debug', 'Création du Sketch Arduino Réseau pour l\'équipement eqID : '.$board_id.' - '.$SketchFileName);

			if (file_exists($SketchFileName)) unlink($SketchFileName);

			$MasterFile=file_get_contents($SketchMasterFile);
			if ($MasterFile)
			{
				$JeedomIP = self::GetJeedomIP();
				$JeedomPort = '(IP_JEEDOM, '.self::GetJeedomPort().')';
				$JeedomCPL = '"GET '.self::GetJeedomComplement().'/plugins/';

				$my_arduino = eqLogic::byid($board_id);
				$ModeleArduino = $my_arduino->getConfiguration('arduino_board');
				$IPArduino = $my_arduino->getConfiguration('iparduino');
				$ipPort = 'server('.$my_arduino->getConfiguration('ipPort').');';

				$JeEdUiNoTaG  = '// Généré le ' . date('Y-m-d H:i:s') . ".\r";
				$JeEdUiNoTaG .= '// Pour l\'équipement ' . $my_arduino->getName() . ' (EqID : ' .$board_id . ' ).' . "\r";
				$JeEdUiNoTaG .= '// Modèle de carte : ' . $ModeleArduino . '.';
				$MasterFile = str_replace('// JeEdUiNoTaG' , $JeEdUiNoTaG , $MasterFile);
				
				$DHTxx = config::byKey($board_id.'_DHTxx', 'jeedouino', 0);
				$DS18x20 = config::byKey($board_id.'_DS18x20', 'jeedouino', 0);
				$TeleInfoRX = config::byKey($board_id.'_TeleInfoRX', 'jeedouino', 0);
				$TeleInfoTX = config::byKey($board_id.'_TeleInfoTX', 'jeedouino', 0);
				$Send2LCD = config::byKey($board_id.'_Send2LCD', 'jeedouino', 0);
				$UserSketch = config::byKey($board_id.'_UserSketch', 'jeedouino', 0);
				$_ProbeDelay = config::byKey($board_id . '_ProbeDelay', 'jeedouino', '1');
				$bmp180 = config::byKey($board_id.'_BMP180', 'jeedouino', 0);
				$servo = config::byKey($board_id.'_SERVO', 'jeedouino', 0);
				$WS2811 = config::byKey($board_id.'_WS2811', 'jeedouino', 0);
				

				if ($TeleInfoRX)
				{
					$MasterFile = str_replace('#define UseTeleInfo 0' , '#define UseTeleInfo 1' , $MasterFile);	// Sketch ligne 10
					$MasterFile = str_replace('SoftwareSerial teleinfo(6,7);' , 'SoftwareSerial teleinfo(' . $TeleInfoRX . ',' . $TeleInfoRX . ');' , $MasterFile);	// Sketch ligne 113
				}
				if ($Send2LCD) $MasterFile = str_replace('#define UseLCD16x2 0' , '#define UseLCD16x2 1' , $MasterFile);	// Sketch ligne 11
				if ($UserSketch) $MasterFile = str_replace('#define UserSketch 0' , '#define UserSketch 1' , $MasterFile);	// Sketch ligne 16
				$MasterFile = str_replace('PinNextSend[i]=millis()+60000;' , 'PinNextSend[i]=millis()+' . 60000 * $_ProbeDelay . ';' , $MasterFile);
				if ($bmp180)
				{
				 	$MasterFile = str_replace('#define UseBMP180 0' , '#define UseBMP180 1' , $MasterFile);
				}
				if ($servo)
				{
				 	$MasterFile = str_replace('#define UseServo 0' , '#define UseServo 1' , $MasterFile);
				}
				if ($WS2811)
				{
				 	$MasterFile = str_replace('#define WS2811PIN 6' , '#define WS2811PIN ' . $WS2811 , $MasterFile);
					$MasterFile = str_replace('#define UseWS2811 0' , '#define UseWS2811 1' , $MasterFile);
				}
				// if ($DHTxx==0)
				// {
					// $MasterFile = str_replace('#define UseDHT 1' , '#define UseDHT 0' , $MasterFile);	// Sketch ligne 8
				// }
				// if ($DS18x20==0)
				// {
					// $MasterFile = str_replace('#define UseDS18x20 1' , '#define UseDS18x20 0' , $MasterFile);	// Sketch ligne 9
				// }

				$JeedomIP = str_replace('.',', ',$JeedomIP);	// 192.168.0.44 -> 192, 168, 0, 44
				$IPArduino = str_replace('.',', ',$IPArduino);	// 192.168.0.70 -> 192, 168, 0, 70
				$MasterFile =  str_replace('192, 168, 0, 44',$JeedomIP,$MasterFile);			// Sketch ligne 56
				$MasterFile =  str_replace('192, 168, 0, 70',$IPArduino,$MasterFile);			// Sketch ligne 55
				$MasterFile =  str_replace('server(80);',$ipPort,$MasterFile);							// Sketch ligne 58
				$MasterFile =  str_replace('(IP_JEEDOM, 80)',$JeedomPort,$MasterFile);	// Sketch ligne 646
				$MasterFile =  str_replace('"GET /plugins/',$JeedomCPL,$MasterFile);			// Sketch ligne 649

				$MasterFile =  str_replace('IDeqLogic',$board_id,$MasterFile);						// Sketch ligne 1028
				$MasterFile =  str_replace('eqLogicLength',strlen($board_id)+1,$MasterFile);	// Sketch ligne 1029,1030

				$mac = '';
				$tmac = strtoupper(dechex($board_id));						// On utilise l'EqID pour générer une adresse mac unique pour chaque arduino réseau
				$tmac = str_pad($tmac,12,'DEADBEEFFEED',STR_PAD_LEFT);
				for ($i = 0; $i < 12; $i+=2)
				{
					$mac .= ' 0x'.substr($tmac,$i,2).',';		//  0xDE, 0xAD, 0xBE, 0xEF, 0xFE, 0xED
				}
				$mac = substr($mac,0,-1);	// dernière ','
				$MasterFile =  str_replace(' 0xDE, 0xAD, 0xBE, 0xEF, 0xFE, 0xED',$mac,$MasterFile);						// Sketch ligne 57
				$result = file_put_contents($SketchFileName,$MasterFile);																		// On sauve le sketch modifié
				if ($result===false) jeedouino::log( 'error', 'Impossible de sauver le Sketch Arduino Réseau généré pour l\'équipement eqID : '.$board_id);
			}
			else jeedouino::log( 'error', 'Impossible de charger le fichier maître du Sketch Arduino Réseau pour l\'équipement eqID : '.$board_id);
		}
	}

	public function GenerateESP8266SketchFile($board_id='')	//  Génère le sketch pour l'esp8266 wifi
	{
		$jeedouinoPATH = realpath(dirname(__FILE__) . '/../../sketchs/');
		if ($board_id!='')
		{
			jeedouino::log( 'debug','Génération du sketch ESP...');
			$SketchMasterFile=$jeedouinoPATH.'/JeedouinoESP.ino';
			$SketchFileName=$jeedouinoPATH.'/JeedouinoESP_'.$board_id.'.ino';
			jeedouino::log( 'debug', 'Création du Sketch ESP8266 pour l\'équipement eqID : '.$board_id.' - '.$SketchFileName);

			if (file_exists($SketchFileName)) unlink($SketchFileName);

			$MasterFile=file_get_contents($SketchMasterFile);
			if ($MasterFile)
			{
				$JeedomIP = self::GetJeedomIP();
				$JeedomPort = '(IP_JEEDOM, '.self::GetJeedomPort().')';
				$JeedomCPL = '"GET '.self::GetJeedomComplement().'/plugins/';

				$my_arduino = eqLogic::byid($board_id);
				$ModeleArduino = $my_arduino->getConfiguration('arduino_board');
				$IPArduino = $my_arduino->getConfiguration('iparduino');
				$ipPort = 'server('.$my_arduino->getConfiguration('ipPort').');';

				$JeEdUiNoTaG  = '// Généré le ' . date('Y-m-d H:i:s') . ".\r";
				$JeEdUiNoTaG .= '// Pour l\'équipement ' . $my_arduino->getName() . ' (EqID : ' .$board_id . ' ).' . "\r";
				$JeEdUiNoTaG .= '// Modèle de carte : ' . $ModeleArduino . '.';
				$MasterFile = str_replace('// JeEdUiNoTaG' , $JeEdUiNoTaG , $MasterFile);
				
				$DHTxx = config::byKey($board_id.'_DHTxx', 'jeedouino', 0);
				$DS18x20 = config::byKey($board_id.'_DS18x20', 'jeedouino', 0);
				$TeleInfoRX = config::byKey($board_id.'_TeleInfoRX', 'jeedouino', 0);
				$TeleInfoTX = config::byKey($board_id.'_TeleInfoTX', 'jeedouino', 0);
				$Send2LCD = config::byKey($board_id.'_Send2LCD', 'jeedouino', 0);
				$UserSketch = config::byKey($board_id.'_UserSketch', 'jeedouino', 0);
				$_ProbeDelay = config::byKey($board_id . '_ProbeDelay', 'jeedouino', '1');
				$bmp180 = config::byKey($board_id.'_BMP180', 'jeedouino', 0);
				$servo = config::byKey($board_id.'_SERVO', 'jeedouino', 0);
				$WS2811 = config::byKey($board_id.'_WS2811', 'jeedouino', false);

				if ($ModeleArduino == 'espsonoffpow')
				{
					$MasterFile = str_replace('#define UseHLW8012 0' , '#define UseHLW8012 1' , $MasterFile);
					$MasterFile = str_replace('#define UseDHT 1' , '#define UseDHT 0' , $MasterFile);
					$MasterFile = str_replace('#define UseDS18x20 1' , '#define UseDS18x20 0' , $MasterFile);
				}
				if ($ModeleArduino == 'esp32dev')
				{
					$MasterFile = str_replace('#include <ESP8266WiFi.h>' , '#include <WiFi.h>' , $MasterFile);
					$MasterFile = str_replace('analogWrite' , '//analogWrite' , $MasterFile);
					$MasterFile = str_replace('#define NB_DIGITALPIN 17' , '#define NB_DIGITALPIN 39' , $MasterFile);
				}
				if ($TeleInfoRX)
				{
					$MasterFile = str_replace('#define UseTeleInfo 0' , '#define UseTeleInfo 1' , $MasterFile);	// Sketch ligne 10
					$MasterFile = str_replace('SoftwareSerial teleinfo(6,7);' , 'SoftwareSerial teleinfo(' . $TeleInfoRX . ',' . $TeleInfoRX . ');' , $MasterFile);	// Sketch ligne 113
				}
				if ($Send2LCD) $MasterFile = str_replace('#define UseLCD16x2 0' , '#define UseLCD16x2 1' , $MasterFile);	// Sketch ligne 11
				if ($UserSketch) $MasterFile = str_replace('#define UserSketch 0' , '#define UserSketch 1' , $MasterFile);	// Sketch ligne 16
				$MasterFile = str_replace('PinNextSend[i]=millis()+60000;' , 'PinNextSend[i]=millis()+' . 60000 * $_ProbeDelay . ';' , $MasterFile);
				if ($bmp180)
				{
				 	$MasterFile = str_replace('#define UseBMP180 0' , '#define UseBMP180 1' , $MasterFile);
				}
				if ($servo)
				{
				 	$MasterFile = str_replace('#define UseServo 0' , '#define UseServo 1' , $MasterFile);
				}
				if ($WS2811 !== false)	// a cause la pin 0...
				{
				 	$MasterFile = str_replace('#define WS2811PIN 6' , '#define WS2811PIN ' . $WS2811 , $MasterFile);
					$MasterFile = str_replace('#define UseWS2811 0' , '#define UseWS2811 1' , $MasterFile);
				}
				// if ($DHTxx==0)
				// {
					// $MasterFile = str_replace('#define UseDHT 1' , '#define UseDHT 0' , $MasterFile);	// Sketch ligne 8
				// }
				// if ($DS18x20==0)
				// {
					// $MasterFile = str_replace('#define UseDS18x20 1' , '#define UseDS18x20 0' , $MasterFile);	// Sketch ligne 9
				// }

				$wifi_ssid = $my_arduino->getConfiguration('wifi_ssid');
				$wifi_pass = $my_arduino->getConfiguration('wifi_pass');
				$MasterFile =  str_replace('MonSSID',$wifi_ssid,$MasterFile);				// Sketch ligne 10
				$MasterFile =  str_replace('MonPassword',$wifi_pass,$MasterFile);		// Sketch ligne 11

				$JeedomIP = str_replace('.',', ',$JeedomIP);	// 192.168.0.44 -> 192, 168, 0, 44
				$IPArduino = str_replace('.',', ',$IPArduino);	// 192.168.0.70 -> 192, 168, 0, 70
				$MasterFile =  str_replace('192, 168, 0, 44',$JeedomIP,$MasterFile);			// Sketch ligne 14
				$MasterFile =  str_replace('192, 168, 0, 70',$IPArduino,$MasterFile);			// Sketch ligne 13
				$MasterFile =  str_replace('server(80);',$ipPort,$MasterFile);							// Sketch ligne 16
				$MasterFile =  str_replace('(IP_JEEDOM, 80)',$JeedomPort,$MasterFile);	// Sketch ligne 429
				$MasterFile =  str_replace('"GET /plugins/',$JeedomCPL,$MasterFile);			// Sketch ligne 431

				$MasterFile =  str_replace('IDeqLogic',$board_id,$MasterFile);						// Sketch ligne 798
				$MasterFile =  str_replace('eqLogicLength',strlen($board_id)+1,$MasterFile);	// Sketch ligne 799,800

				$result = file_put_contents($SketchFileName,$MasterFile);																		// On sauve le sketch modifié
				if ($result===false) jeedouino::log( 'error', 'Impossible de sauver le Sketch ESP8266 généré pour l\'équipement eqID : '.$board_id);
			}
			else jeedouino::log( 'error', 'Impossible de charger le fichier maître du Sketch ESP8266 pour l\'équipement eqID : '.$board_id);
		}
	}

	public function GenerateUSBArduinoSketchFile($board_id = '')	//  Génère le sketch pour l'arduino avec shield ethernet
	{
		$jeedouinoPATH = realpath(dirname(__FILE__) . '/../../sketchs/');
		if ($board_id != '')
		{
			jeedouino::log( 'debug','Génération du sketch Arduino USB...');
			$SketchMasterFile = $jeedouinoPATH.'/JeedouinoUSB.ino';
			$SketchFileName = $jeedouinoPATH.'/JeedouinoUSB_'.$board_id.'.ino';
			jeedouino::log( 'debug', 'Création du Sketch Arduino USB pour l\'équipement eqID : ' . $board_id . ' - ' . $SketchFileName);

			if (file_exists($SketchFileName)) unlink($SketchFileName);

			$MasterFile=file_get_contents($SketchMasterFile);
			if ($MasterFile)
			{
				$JeedomIP = self::GetJeedomIP();

				$my_arduino = eqLogic::byid($board_id);
				$ModeleArduino = $my_arduino->getConfiguration('arduino_board');

				$JeEdUiNoTaG  = '// Généré le ' . date('Y-m-d H:i:s') . ".\r";
				$JeEdUiNoTaG .= '// Pour l\'équipement ' . $my_arduino->getName() . ' (EqID : ' .$board_id . ' ).' . "\r";
				$JeEdUiNoTaG .= '// Modèle de carte : ' . $ModeleArduino . '.';
				$MasterFile = str_replace('// JeEdUiNoTaG' , $JeEdUiNoTaG , $MasterFile);
				
				$DHTxx = config::byKey($board_id.'_DHTxx', 'jeedouino', 0);
				$DS18x20 = config::byKey($board_id.'_DS18x20', 'jeedouino', 0);
				$TeleInfoRX = config::byKey($board_id.'_TeleInfoRX', 'jeedouino', 0);
				$TeleInfoTX = config::byKey($board_id.'_TeleInfoTX', 'jeedouino', 0);
				$Send2LCD = config::byKey($board_id.'_Send2LCD', 'jeedouino', 0);
				$UserSketch = config::byKey($board_id.'_UserSketch', 'jeedouino', 0);
				$_ProbeDelay = config::byKey($board_id . '_ProbeDelay', 'jeedouino', '1');
				$bmp180 = config::byKey($board_id.'_BMP180', 'jeedouino', 0);
				$servo = config::byKey($board_id.'_SERVO', 'jeedouino', 0);
				$WS2811 = config::byKey($board_id.'_WS2811', 'jeedouino', 0);

				if ($TeleInfoRX)
				{
					$MasterFile = str_replace('#define UseTeleInfo 0' , '#define UseTeleInfo 1' , $MasterFile);
					$MasterFile = str_replace('SoftwareSerial teleinfo(6,7);' , 'SoftwareSerial teleinfo(' . $TeleInfoRX . ',' . $TeleInfoRX . ');' , $MasterFile);
				}
				if ($Send2LCD) $MasterFile = str_replace('#define UseLCD16x2 0' , '#define UseLCD16x2 1' , $MasterFile);
				if ($UserSketch) $MasterFile = str_replace('#define UserSketch 0' , '#define UserSketch 1' , $MasterFile);

				$MasterFile = str_replace('PinNextSend[i]=millis()+60000;' , 'PinNextSend[i]=millis()+' . 60000 * $_ProbeDelay . ';' , $MasterFile);

				if ($bmp180) $MasterFile = str_replace('#define UseBMP180 0' , '#define UseBMP180 1' , $MasterFile);
				if ($servo) $MasterFile = str_replace('#define UseServo 0' , '#define UseServo 1' , $MasterFile);

				if ($WS2811)
				{
				 	$MasterFile = str_replace('#define WS2811PIN 6' , '#define WS2811PIN ' . $WS2811 , $MasterFile);
					$MasterFile = str_replace('#define UseWS2811 0' , '#define UseWS2811 1' , $MasterFile);
				}

				$JeedomIP = str_replace('.' , ', ' , $JeedomIP);	// 192.168.0.44 -> 192, 168, 0, 44
				$MasterFile =  str_replace('192, 168, 0, 44' , $JeedomIP , $MasterFile);	

				$result = file_put_contents($SketchFileName , $MasterFile);																		// On sauve le sketch modifié
				if ($result === false) jeedouino::log( 'error', 'Impossible de sauver le Sketch Arduino USB généré pour l\'équipement eqID : ' . $board_id);
			}
			else jeedouino::log( 'error' , 'Impossible de charger le fichier maître du Sketch Arduino USB pour l\'équipement eqID : ' . $board_id);
		}
	}

	/* fonction appelé avant l'effacement d'une entrée */
	public function preRemove()
	{
		//  On éfface le fichier python généré pour le Démon
		$arduino_id = $this->getId();
		list($Arduino_pins, $board, $usb) = self::GetPinsByBoard($arduino_id);
		config::save($arduino_id . 'remove', '1', 'jeedouino');
		switch ($board)
		{
			case 'arduino':
				$USBLAN = 'LAN';
				if ($usb)
				{
					self::StopBoardDemon($arduino_id, 0, $board);
					self::EraseBoardDemonFile($arduino_id, 0, $board);
					$USBLAN = 'USB';
				}
				// On efface le sketch

				$jeedouinoPATH = realpath(dirname(__FILE__) . '/../../sketchs/');
				$SketchFileName = $jeedouinoPATH . '/Jeedouino' . $USBLAN . '_' . $arduino_id . '.ino';
				if (file_exists($SketchFileName))
				{
					unlink($SketchFileName);
					jeedouino::log( 'debug', 'Le Sketch Arduino ' . $USBLAN . ' pour l\'équipement eqID : ' . $arduino_id . '  est supprimé ! - ' . $SketchFileName);
				}

				break;
			case 'esp':
				 // On efface le sketch
				$jeedouinoPATH = realpath(dirname(__FILE__) . '/../../sketchs/');
				$SketchFileName = $jeedouinoPATH . '/JeedouinoESP_' . $arduino_id . '.ino';
				if (file_exists($SketchFileName))
				{
					unlink($SketchFileName);
					jeedouino::log( 'debug', 'Le Sketch ESP / NodeMCU / Wemos pour l\'équipement eqID : ' . $arduino_id . '  est supprimé ! - ' . $SketchFileName);
				}
				break;
			case 'piface':
			case 'piplus':
			case 'gpio':
				self::StopBoardDemon($arduino_id, 0, $board);
				self::EraseBoardDemonFile($arduino_id, 0, $board);
				break;
		}
		// On efface les commandes dans les virtuels
		foreach ($this->getCmd() as $cmd)  jeedouino::DelCmdOfVirtual($cmd, $cmd->getLogicalId());

		// On efface les variables dans la table config (pins et autres)
		foreach ($Arduino_pins as $pins_id => $pin_datas)
		{
			config::remove($arduino_id . '_' . $pins_id, 'jeedouino');
			config::remove('GT_' . $arduino_id . '_' . $pins_id, 'jeedouino');
			config::remove('GV_' . $arduino_id . '_' . $pins_id, 'jeedouino');
		}
		config::remove($arduino_id . '_choix_boot', 'jeedouino');
		config::remove($arduino_id . '_DHTxx', 'jeedouino');
		config::remove($arduino_id . '_DS18x20', 'jeedouino');
		config::remove($arduino_id . '_TeleInfoRX', 'jeedouino');
		config::remove($arduino_id . '_TeleInfoTX', 'jeedouino');
		config::remove($arduino_id . '_Send2LCD', 'jeedouino');
		config::remove($arduino_id . '_UserSketch', 'jeedouino');
		config::remove($arduino_id . '_SomfyRTS', 'jeedouino');
		config::remove($arduino_id . '_HasDemon', 'jeedouino');
		config::remove($arduino_id . '_oID', 'jeedouino');
		config::remove($arduino_id . '_PinMode', 'jeedouino');
		config::remove($arduino_id . '-ForceStart', 'jeedouino');
		config::remove($arduino_id . '-ForceSuppr', 'jeedouino');
		config::remove($arduino_id . '_EqCfgSaveStep', 'jeedouino');
		config::remove($arduino_id . '_ProbeDelay', 'jeedouino');
		config::remove($arduino_id . '_BMP180', 'jeedouino');
		config::remove($arduino_id . '_SERVO', 'jeedouino');
		config::remove($arduino_id . '_WS2811', 'jeedouino');
		
		config::remove('REP_' . $arduino_id, 'jeedouino');

		config::remove($arduino_id . 'remove', 'jeedouino');
	}

	/* fonction appelé après l'effacement d'une entrée */
	public function postRemove()
	{
		//config::remove( $this->getId() . 'remove', 'jeedouino');		// arduino_id
	}

	/*	 * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
	  public function toHtml($_version = 'dashboard') {
	  }	 */
}

class jeedouinoCmd extends cmd {
	public function execute($_options = null)
	{
		$pins_id = $this->getConfiguration('pins_id');
		if ($this->getType() == 'action')
		{
			try
			{
				if ($this->getSubType() == 'other')
				{
					$tempo = $this->getConfiguration('tempo');
					if ($tempo == '0') $tempo = '';
					else  $tempo = sprintf("%05s", $tempo);
					if (jeedouino::ConfigurePinValue($pins_id, $this->getConfiguration('value') . $tempo, $this->getEqLogic_id())) return true;
					return false;
				}
				elseif ($this->getSubType() == 'slider')
				{
					$modePIN = $this->getConfiguration('modePIN');
					if ($modePIN == 'low_pulse_slide' or $modePIN == 'high_pulse_slide')
					{
						//jeedouino::log( 'debug','Liste $_options = '. json_encode($_options));
						$tempo = round($_options['slider']);
						$tempo = sprintf("%05s", $tempo);
						$this->setConfiguration('tempo', $tempo);
						$this->setConfiguration('minValue', 0);
						$this->setConfiguration('maxValue', 10000);
						$this->save();
						if (jeedouino::ConfigurePinValue($pins_id, $this->getConfiguration('value') . $tempo, $this->getEqLogic_id())) return true;
					}
					elseif ($modePIN == 'WSmode') // led strip mode effet
					{
						//jeedouino::log( 'debug','Liste $_options = '. json_encode($_options));
						$value = round($_options['slider']);
						$this->setConfiguration('value',$value);
						$this->setConfiguration('minValue', 0);
						$this->setConfiguration('maxValue', 17);
						$this->save();
						//jeedouino::log( 'debug','Liste $sprintf("%02s", $value) = '. sprintf("%02s", $value));
						if (jeedouino::ConfigurePinValue($pins_id, sprintf("%02s", $value), $this->getEqLogic_id())) return true;
					}
					else
					{
						//jeedouino::log( 'debug','Liste $_options = '. json_encode($_options));
						$value = round($_options['slider']);
						$this->setConfiguration('value',$value);
						$this->setConfiguration('minValue', 0);
						$this->setConfiguration('maxValue', 255);
						$this->save();
						if (jeedouino::ConfigurePinValue($pins_id, sprintf("%03s", $value), $this->getEqLogic_id())) return true;
					}
					return false;
				}
				elseif ($this->getSubType() == 'color')
				{
					if ($_options === null) return false;
					//jeedouino::log( 'debug','Liste $_options = '. json_encode($_options));
					if (!isset($_options['color'])) $_options['color'] = '';
					if ($_options['color'] == '') $_options['color'] = '000000';

					if (jeedouino::ConfigurePinValue($pins_id, substr($_options['color'], -6), $this->getEqLogic_id())) return true;
					return false;
				}
				elseif ($this->getSubType() == 'message')
				{
					if ($_options === null) return false;
					//jeedouino::log( 'info','_ - $_options = '. json_encode($_options));
					if (!isset($_options['title'])) $_options['title'] = '';
					if (!isset($_options['message'])) $_options['message'] = '';
					if ($_options['title'] == '') $_options['title'] = 'Jeedouino says:';
					if ($_options['message'] == '') $_options['message'] = 'Nothing yet...';

					if (jeedouino::ConfigurePinValue($pins_id, substr($_options['title'], 0, 16) . '|' . substr($_options['message'], 0, 16), $this->getEqLogic_id())) return true;
					return false;
				}
				else jeedouino::log( 'error','CMD execute $this->getSubType() = ' . $this->getSubType() . ' - $_options = '. json_encode($_options));
			}
			catch (Exception $e)
			{
				jeedouino::log( 'error', 'CMD execute Exception $e = ' . $e . ' - $_options = ' . json_encode($_options));
				return false;
			}
		}
	}
}
?>

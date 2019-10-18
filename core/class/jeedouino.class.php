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

/******************************* Includes *******************************/
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
include_file('core', 'jeedouino', 'config', 'jeedouino');

class jeedouino extends eqLogic {
	/******************************* Attributs *******************************/
	/* Ajouter ici toutes vos variables propre à votre classe */

	/***************************** Methode static ****************************/
	public static function Networkmode()
	{
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
		if (file_exists(dirname(__FILE__) . '/../../icons/jeedouino_' . $this->getConfiguration('arduino_board') . '.png'))
		{
			return 'plugins/jeedouino/icons/jeedouino_' . $this->getConfiguration('arduino_board') . '.png';
		}
		else
		{
			return 'plugins/jeedouino/icons/jeedouino_icon.png';
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

	// fonction lancée au démarrage de Jeedom
	public static function start()
	{
		config::save('CronStep', 0, 'jeedouino');
		$BootTime = config::byKey('BootTime', 'jeedouino', 4);
		jeedouino::log( 'debug', __('Suite (re)boot Jeedom, démarrage des démons dans ', __FILE__) . $BootTime . ' min(s).');
	}
	// Fonction exécutée automatiquement toutes les minutes par Jeedom
	public static function cron()
	{
		$BootTime = config::byKey('BootTime', 'jeedouino', 4);
		$CronStep = config::byKey('CronStep', 'jeedouino', 0);
		//jeedouino::log( 'debug', '$CronStep = ' . $CronStep);
		if ($CronStep > $BootTime + 42) return;
		if ($CronStep == $BootTime) jeedouino::StartAllDemons();
		$CronStep++;
		config::save('CronStep', $CronStep, 'jeedouino');
	}
	// Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
	public static function cron5()
	{
		$BootTime = config::byKey('BootTime', 'jeedouino', 4) + 5;
		$CronStep = config::byKey('CronStep', 'jeedouino', 0);
		if ($CronStep <= $BootTime) return;

		$eqLogics = eqLogic::byType('jeedouino');
		foreach ($eqLogics as $eqLogic)
		{
			if ($eqLogic->getIsEnable() == 0) continue;
			$board_id = $eqLogic->getId();
			if (config::byKey($board_id . '_HasDemon', 'jeedouino', 0) and config::byKey('Auto_'. $board_id, 'jeedouino', 0))
			{
				jeedouino::log( 'debug', __('Vérification automatique du démon (option AutoReStart) toutes les 5 minutes pour ', __FILE__) . $eqLogic->getName(true) . ' (' . $board_id . ')');
				$ModeleArduino = $eqLogic->getConfiguration('arduino_board');
				if (!jeedouino::StatusBoardDemon($board_id, 0, $ModeleArduino)) jeedouino::StartBoardDemon($board_id, 0, $ModeleArduino);
			}
		}
	}
	public static function cron30()
	{
		$BootTime = config::byKey('BootTime', 'jeedouino', 4) + 30;
		$CronStep = config::byKey('CronStep', 'jeedouino', 0);
		if ($CronStep <= $BootTime) return;
		$eqLogics = eqLogic::byType('jeedouino');
		$hasDemons = false;
		foreach ($eqLogics as $eqLogic)
		{
			if ($eqLogic->getIsEnable() == 0) continue;
			if (config::byKey($eqLogic->getId() . '_HasDemon', 'jeedouino', 0)) $hasDemons = true;
		}
		if ($hasDemons)
		{
			jeedouino::log( 'debug', __('JeedouinoControl : Vérification automatique des démons toutes les 30 minutes', __FILE__));
			jeedouino::updateDemons();
		}
	}

	public static function dependancy_info()
	{
		$return = array();
		$return['log'] = 'jeedouino_update';
		$return['last_launch'] = '';
		$return['progress_file'] = '/tmp/dependances_jeedouino_en_cours';
		if (@shell_exec('ls /usr/lib/python3*/dist-packages/serial/serialposix.py | wc -l') == 0)
		{
			$return['state'] = 'nok';
		}
		else
		{
			$return['state'] = 'ok';
			if (@shell_exec('ls /usr/local/lib/python3*/dist-packages/Adafruit_DHT*.egg | wc -l') == 0) $return['state'] = 'nok';
		}
		if ($return['state'] == 'nok') $return['advice'] = __('Normal si ce n\'est pas sur un Raspberry PI.', __FILE__);

		// Cas du maitre qui n'est pas un RPI
		if (strpos(strtolower(config::byKey('hardware_name')), 'rpi') === false)
		{
			$return['state'] = 'ok';
			log::add('jeedouino_update','info', __('ATTENTION ! Ce n\'est pas un Raspberry PI, les dépendances afférentes ne s(er)ont pas installées.', __FILE__));
		}
		return $return;
	}
	public static function dependancy_install()
	{
		if (file_exists('/tmp/dependances_jeedouino_en_cours')) return;	// Install déja en cours
		exec('sudo /bin/bash ' . dirname(__FILE__) . '/../../ressources/Jeedouino.sh >> ' . log::getPathToLog('jeedouino_update') . ' 2>&1 &');

		log::add('jeedouino_update','info', __('Veuillez utiliser les boutons de la page Configuration du plugin pour les dépendances spécifiques. Merci', __FILE__));
	}
	public static function health()
	{
		$return = array();
		$return['test'] = __('Etat(s) démon(s)', __FILE__);
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
					$return['advice'] = __('Au moins un démon ne tourne pas. Voir la page de configuration du plugin.', __FILE__);
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
		//if ($n == 0) jeedouino::log( 'debug',__('-=-= Aucun démon trouvé =-=-', __FILE__));
		return $return;
	}
	public static function deamon_start($_debug = false)
	{
		$eqLogics = eqLogic::byType('jeedouino');
		jeedouino::log( 'debug', __('-=-= Suite demande Jeedom, démarrage global des démons =-=-', __FILE__));
		foreach ($eqLogics as $eqLogic)
		{
			if ($eqLogic->getIsEnable() == 0) continue;
			$board_id=$eqLogic->getId();
			if (config::byKey($board_id . '_HasDemon', 'jeedouino', 0))
			{
				jeedouino::log( 'debug',__('-=-= '.$board_id.' =-=-', __FILE__));
				list(,$board,$usb) = self::GetPinsByBoard($board_id);
				self::StartBoardDemon($board_id,0,$board);
				sleep(2);
			}

		}
		jeedouino::log( 'debug', __('-=-= Fin du démarrage des démons =-=-', __FILE__));
	}
	public static function deamon_stop()
	{
 		$eqLogics = eqLogic::byType('jeedouino');
		jeedouino::log('debug', __('-=-= Suite demande Jeedom, Arrêt global des démons =-=-', __FILE__));
		foreach ($eqLogics as $eqLogic)
		{
			$board_id = $eqLogic->getId();
			if (config::byKey($board_id . '_HasDemon', 'jeedouino', 0))
			{
				jeedouino::log( 'debug',__('-=-= ' . $board_id . ' =-=-', __FILE__));
				list(, $board, $usb) = self::GetPinsByBoard($board_id);
				self::StopBoardDemon($board_id, 0, $board);
				sleep(2);
			}
		}
		jeedouino::log( 'debug', __('-=-= Fin de l\'arrêt des démons =-=-', __FILE__));
	}

	public static function log($log1, $log2)
	{
		// On nettoie le log de ses caracteres speciaux car il font planter l'affichage des logs dans jeedom
		//$log2 = filter_var($log2, FILTER_SANITIZE_STRING);
		if (config::byKey('ActiveLog', 'jeedouino', false)) log::add('jeedouino', $log1, $log2);
	}
	public static function getPathToLog($log)
	{
		if (config::byKey('ActiveDemonLog', 'jeedouino', false)) return log::getPathToLog($log);
		return '/dev/null';
	}

	/*************************** Méthodes d'instance **************************/

	public function StartAllDemons($EqIDarr = '')
	{
		$EqLogics = [];
		if (!is_array($EqIDarr))
		{
			$EqLogics = eqLogic::byType('jeedouino');
			jeedouino::log( 'debug', __('Suite reboot Jeedom, démarrage des démons.', __FILE__));
		}
		else
		{
			$BootTime = config::byKey('BootTime', 'jeedouino', 4);
			$CronStep = config::byKey('CronStep', 'jeedouino', 0);
			if ($CronStep <= $BootTime) return;
			foreach ($EqIDarr as $id) $EqLogics[] = eqLogic::byId($id);
			jeedouino::log( 'debug', __('Suite reboot JeedouinoExt, démarrage des démons.', __FILE__) . ' ID(s) : ' . json_encode($EqIDarr));
		}
		config::save('StartDemons', 1, 'jeedouino');
		foreach ($EqLogics as $eqLogic)
		{
			if ($eqLogic->getLogicalId() == 'JeedouinoControl') continue;
			$arduino_id = $eqLogic->getId();
			if (!is_object($eqLogic))
			{
				jeedouino::log( 'debug', __('L\'équipement est n\'existe plus.', __FILE__) . ' (ID ' . $arduino_id . ') ');
				continue;
			}
			if ($eqLogic->getIsEnable() == 0)
			{
				jeedouino::log( 'debug', __('L\'équipement est désactivé.', __FILE__) . ' (ID ' . $arduino_id . ') ');
				continue;
			}
			list(, $board, $usb) = jeedouino::GetPinsByBoard($arduino_id);
			if (($board == 'arduino' and !$usb) or ($board == 'esp')) continue;

			jeedouino::log( 'debug', '-=-= ' . __('Démarrage de ' , __FILE__) . $eqLogic->getName() . ' ID ' . $arduino_id . ' =-=-');
			jeedouino::StartBoardDemon($arduino_id, 0, $board);
			sleep(2);
		}
		jeedouino::log( 'debug', '-=-= ' . __('Fin du démarrage des démons', __FILE__) . ' =-=-');
		config::save('StartDemons', 0, 'jeedouino');
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

		return array($Arduino_pins, $board, $usb);
	}

	public function ConfigurePinMode($ForceStart = true) 	// Paramétrage du mode des pins)
	{
	 	//$arduino_id = $this->getEqLogic_id();	// si appel depuis CMD
		$arduino_id = $this->getId();
		$my_arduino = eqLogic::byid($arduino_id);
		$ModeleArduino = $my_arduino->getConfiguration('arduino_board');
		$PortArduino = $my_arduino->getConfiguration('datasource');	 // usbarduino - rj45arduino
		$LocalArduino = $my_arduino->getConfiguration('arduinoport');   // usblocal - usbdeporté
		$portusbdeporte = $my_arduino->getConfiguration('portusbdeporte');   // portusbdeporté (ExtID_portUSB)
		$IPArduino = $my_arduino->getConfiguration('iparduino'); 		// IP réseau
		$ipPort = $my_arduino->getConfiguration('ipPort');	  // port réseau
		$PortID = $my_arduino->getConfiguration('PortID');	 // No de carte piface ( si plusieurs sur même RPI)

		//if ((($PortArduino=='rj45arduino') or ($PortArduino=='usbarduino')) and (($LocalArduino=='usblocal') or ($LocalArduino=='usbdeporte')))
		if (($ModeleArduino !='') and (($PortArduino == 'rj45arduino') or ($PortArduino == 'usbarduino')))
		{
			// ok une carte est définie
			$JeedouinoAlone = $my_arduino->getConfiguration('alone'); // Déja fait si Jeedouino sur un Rpi sans Jeedom.
			if (($PortArduino == 'usbarduino') and ($JeedouinoAlone != '1'))// On va essayer de récupérer l'ip du jeedom sur lequel est branchée l'arduino en usb
			{
				$PortDemon = $my_arduino->getConfiguration('PortDemon');
				if ($LocalArduino == 'usblocal')
				{
					$IPArduino = self::GetJeedomIP();
					$my_arduino->setConfiguration('iparduino', $IPArduino);  // On la sauve pour le démon
					$my_arduino->save(true);
					jeedouino::log( 'debug',__('Démon local', __FILE__) . ' - IPArduino ArduinoUsb (eqID ' . $arduino_id . ') : ' . $IPArduino . ':' . $PortDemon);
				}
			}

			list($Arduino_pins, $board, $usb) = self::GetPinsByBoard($arduino_id);

			$PinMode = '';
 			foreach ($Arduino_pins as $pins_id => $pin_datas)
			{
				if ( $pins_id >= 500 ) continue;
				$myPin = config::byKey($arduino_id . '_' . $pins_id, 'jeedouino', 'not_used');
				switch ($myPin)
				{
					// dispo : 0-9 D-Z
					case 'double_pulse_low':
					case 'double_pulse_high':
					case 'double_pulse':
						$PinMode .= 'y';
						break;
					case 'servo':
						$PinMode .= 'x';
						break;
					case 'bmp180':
						$PinMode .= 'r';
						break;
					case 'bmp280':
						$PinMode .= 'C';
						break;
					case 'bmp280b':
						$PinMode .= 'F';
						break;
					case 'bme280':
						$PinMode .= 'A';
						break;
					case 'bme280b':
						$PinMode .= 'D';
						break;
					case 'bme680':
						$PinMode .= 'B';
						break;
					case 'bme680b':
						$PinMode .= 'E';
						break;
					case 'bp_input':
						$PinMode .= 'n';
						break;
					case 'bp_input_pullup':
						$PinMode .= 'q';
						break;
					case 'teleinfoRX':
						$PinMode .= 'j';
						break;
					case 'teleinfoTX':
						$PinMode .= 'k';
						break;
					case 'trigger':
						$PinMode .= 't';
						break;
					case 'echo':
						$PinMode .= 'z';
						break;
					case 'input':
						$PinMode .= 'i';
						break;
					case 'input_pullup':
						$PinMode .= 'p';
						break;
					case 'dht11':
						$PinMode .= 'd';
						break;
					case 'dht21':
						$PinMode .= 'e';
						break;
					case 'dht22':
						$PinMode .= 'f';
						break;
					case 'ds18b20':
						$PinMode .= 'b';
						break;
					case 'pwm_input':
						$PinMode .= 'g';
						break;
					case 'analog_input':
						$PinMode .= 'a';
						break;
					case 'output':
						$PinMode .= 'o';
						break;
					case 'switch':
						$PinMode .= 's';
						break;
					case 'compteur_pullup':
						$PinMode .= 'c';
						break;
					case 'low_relais':
						$PinMode .= 'l';
						break;
					case 'high_relais':
						$PinMode .= 'h';
						break;
					case 'output_pulse':
						$PinMode .= 'u';
						break;
					case 'low_pulse':
					case 'low_pulse_slide':
						$PinMode .= 'v';
						break;
					case 'high_pulse':
					case 'high_pulse_slide':
						$PinMode .= 'w';
						break;
					case 'pwm_output':
						$PinMode .= 'm';
						break;
					default:		// case 'not_used':
						$PinMode .= '.';
						break;
				}
			}
			if ($PinMode != '')
			{
				switch ($board)
				{
					case 'arduino':
						if (!$usb) break;
					case 'piface':
					case 'piplus':
					case 'gpio':
						if ($ForceStart or $ForceStart == '1')
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
						$BootMode = 'BootMode=' . config::byKey($arduino_id . '_piGPIO_boot', 'jeedouino', '0');
						jeedouino::log( 'debug', __('Envoi de la configuration BootMode eqID ( ', __FILE__) . $arduino_id . ' ) ' . "BootMode : " . $BootMode);
						$reponse = self::SendToBoardDemon($arduino_id, $BootMode, $ModeleArduino);
						if ($reponse!='BMOK') jeedouino::log( 'debug', ucfirst($ModeleArduino) . __(' - PB ENVOI CONFIGURATION BootMode eqID ( ', __FILE__) . $arduino_id . ' ) - Réponse :'.$reponse);
					case 'piface':
					case 'piPlus':
						$PinMode = 'ConfigurePins=' . $PinMode;
						config::save($arduino_id.'_PinMode', $PinMode, 'jeedouino');
						jeedouino::log( 'debug', __('Envoi de la nouvelle configuration des pins eqID ( ', __FILE__) . $arduino_id . ' ) ' . "PinMode : ". $PinMode);
						$reponse = self::SendToBoardDemon($arduino_id, $PinMode, $ModeleArduino);
						if ($reponse != 'COK') jeedouino::log( 'debug', ucfirst($ModeleArduino) . __(' - PB ENVOI CONFIGURATION PinMode eqID ( ', __FILE__) . $arduino_id . ' ) - Réponse :'.$reponse);

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
		 jeedouino::log( 'debug', __('Fin de ConfigurePinMode()', __FILE__));
	}
	public function SendToArduino($arduino_id, $message, $keyword, $waitfor)
	{
		$my_arduino = eqLogic::byid($arduino_id);
		$name = $my_arduino->getName();
		$IPArduino = $my_arduino->getConfiguration('iparduino');
		$ipPort = $my_arduino->getConfiguration('ipPort');
		jeedouino::log( 'debug', __('Envoi de la configuration [ ', __FILE__) . $keyword . ' : ' . $message . __(' ] à l\'équipement ', __FILE__) . $arduino_id . ' ( ' . $name . ' ) sur l\'IP : ' . $IPArduino . ':' . $ipPort );
		$reponse = self::SendToBoard($arduino_id, $message);
		if ($reponse != $waitfor)
		{
			$waitforArr = array('COK' , 'PINGOK' , 'EOK' , 'IPOK' , 'SOK' , 'SCOK' , 'SFOK' , 'BMOK');
			if (in_array($reponse, $waitforArr)) jeedouino::log( 'debug', __('Réponse différée reçue de l\'équipement ', __FILE__) . $arduino_id . ' ( ' . $name . ' ) - Réponse :'.$reponse);
			else jeedouino::log( 'debug', __('PB ENVOI CONFIGURATION ', __FILE__) . $keyword . __(' équipement ', __FILE__) . $arduino_id . ' ( ' . $name . ' ) - Réponse :'.$reponse);
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
		if (($ModeleArduino !='') and (($PortArduino == 'rj45arduino') or ($PortArduino == 'usbarduino')))
		{
			// ok une carte est definie
			list($Arduino_pins,$board,$usb) = self::GetPinsByBoard($arduino_id);

			$message='';
			foreach ($Arduino_pins as $pins_id => $pin_datas)
			{
				$myPin = config::byKey($arduino_id . '_' . $pins_id, 'jeedouino', 'not_used');
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
						if ($cmd->getDisplay('invertBinare'))  $message .= 1 - $cmd->getConfiguration('value');
						else $message .= $cmd->getConfiguration('value');
						break;
					case 'low_relais':
					case 'high_pulse':
					case 'double_pulse_high':
					case 'high_pulse_slide':
						if ($cmd->getDisplay('invertBinare')) $message .= '0';
						else $message .= '1';
						break;
					case 'high_relais':
					case 'low_pulse':
					case 'double_pulse_low':
					case 'low_pulse_slide':
						if ($cmd->getDisplay('invertBinare')) $message .= '1';
						else $message .= '0';
						break;
					default:
						$message.='.';
						break;
				}
			}
			if ($message!='')
			{
				jeedouino::log( 'debug', __('Envoi des valeurs des pins suite à la demande de la carte (Reboot?) eqID ( ', __FILE__) . $arduino_id . ' ) - Message : ' . $message);
				self::SendToArduino($arduino_id, 'S' . $message . 'F', 'AllPinsValues', 'SFOK');
			}
		}

	}

	public function ConfigurePinValue($pins_id, $value, $arduino_id)
	{
		$my_arduino = eqLogic::byid($arduino_id);
		$ModeleArduino = $my_arduino->getConfiguration('arduino_board');
		$reponse = '';

		$Altern = false;
		if ($pins_id >= 1000)
		{
			$pins_id -= 1000;
			$Altern = true;
		}

		$PinValue = '';
		if ( $pins_id == '990') 		$PinValue = 'SetAllLOW=1';		// Set To LOW all Output pins
		elseif ( $pins_id == '991') 	$PinValue = 'SetAllHIGH=1';		// Set To HIGH all Output pins
		elseif ( $pins_id == '992') 	$PinValue = 'SetAllSWITCH=1';		// Switch/Toggle all Output pins
		elseif ( $pins_id == '993') 	$PinValue = 'SetAllPulseLOW=1&tempo=' . substr($value, -5);		// Pulse To LOW  all Output pins
		elseif ( $pins_id == '994') 	$PinValue = 'SetAllPulseHIGH=1&tempo=' . substr($value, -5);	// Pulse To HIGH all Output pins
		else
		{
			$myPin = config::byKey($arduino_id . '_' . $pins_id, 'jeedouino', 'not_used');
			switch ($myPin)
			{
				case 'switch':
					$PinValue = 'SwitchPin=' . $pins_id;
				break;
				case 'trigger':
					$PinValue = 'Trigger=' . $pins_id;
					$PinValue .= '&Echo=' . substr($value, -2);
				break;
				case 'low_relais':
					$PinValue = 'SetPinLOW=' . $pins_id;
					$value = '0';
					if ($Altern)
					{
						$value = '1';
						$PinValue = 'SetPinHIGH=' . $pins_id;
					}
				break;
				case 'high_relais':
					$PinValue = 'SetPinHIGH=' . $pins_id;
					$value = '1';
					if ($Altern)
					{
						$value = '0';
						$PinValue = 'SetPinLOW=' . $pins_id;
					}
				break;
				case 'low_pulse':
				case 'low_pulse_slide':
					$PinValue = 'SetLOWpulse=' . $pins_id;
					$PinValue .= '&tempo=' . substr($value, -5);	 // recupere la tempo (5 derniers chiffres)
					if ($Altern) // high_relais
					{
						$value = '1';
						$PinValue = 'SetPinHIGH=' . $pins_id;
					}
				break;
				case 'high_pulse':
				case 'high_pulse_slide':
					$PinValue = 'SetHIGHpulse=' . $pins_id;
					$PinValue .= '&tempo=' . substr($value, -5);	 // recupere la tempo (5 derniers chiffres)
					if ($Altern) // low_relais
					{
						$value = '0';
						$PinValue = 'SetPinLOW=' . $pins_id;
					}
				break;
				case 'double_pulse_low':
					$PinValue = 'SetLOWdoublepulse=' . $pins_id;
					$PinValue .= '&tempclick=' . substr($value, -6, 3);
					$PinValue .= '&temppause=' . substr($value, -3);
					if ($Altern) // low_relais
					{
						$value = '0';
						$PinValue = 'SetPinLOW=' . $pins_id;
					}
				break;
				case 'double_pulse_high':
					$PinValue = 'SetHIGHdoublepulse=' . $pins_id;
					$PinValue .= '&tempclick=' . substr($value, -6, 3);
					$PinValue .= '&temppause=' . substr($value, -3);
					if ($Altern) // high_relais
					{
						$value = '1';
						$PinValue = 'SetPinHIGH=' . $pins_id;
					}
				break;
			}
		}

		if ($ModeleArduino == 'piface' or $ModeleArduino == 'piGPIO26' or $ModeleArduino == 'piGPIO40' or $ModeleArduino == 'piPlus')
		{
			jeedouino::log( 'debug', 'ConfigurePinValue ' . $ModeleArduino . ' ( ' . $arduino_id . ' ) ' . "PinValue : " . $PinValue);
			if ($PinValue != '')  $reponse = self::SendToBoardDemon($arduino_id, $PinValue, $ModeleArduino);
		}
		else // Arduinos
		{
			if ( $pins_id == '990') 	$PinValue = 'S2L';	// Set To LOW all Output pins
			elseif ( $pins_id == '991') $PinValue = 'S2H';	// Set To HIGH all Output pins
			elseif ( $pins_id == '992') $PinValue = 'S2A';	// Switch/Toggle all Output pins
			elseif ( $pins_id == '993') $PinValue = 'SP' . sprintf("%05s", substr($value, -5)) . 'L';	// Pulse To LOW  all Output pins
			elseif ( $pins_id == '994') $PinValue = 'SP' . sprintf("%05s", substr($value, -5)) . 'H';	// Pulse To HIGH all Output pins
			elseif ( $pins_id > 499 and $pins_id < 600 ) 	$PinValue = 'U' . sprintf("%03s", $pins_id) . $value . 'R';// User Pins
			else
			{
				if ($ModeleArduino != 'a1280' and  $ModeleArduino != 'a2560' and $pins_id > 53) $pins_id -= 40; // Analog to Digital pins OUT
				$PinValue = 'S' . sprintf("%02s", $pins_id) . $value . 'S';
				if ($myPin == 'trigger') $PinValue = 'T' . sprintf("%02s", $pins_id) . sprintf("%02s", substr($value,-2)) . 'E';	// Trigger pin + pin Echo -> HC-SR04 (ex:T0203E)
				if ($myPin == 'Send2LCD') $PinValue = 'S' . sprintf("%02s", $pins_id) . $value . 'M'; //S17Title|MessageM >>> S 17 Title | Message M	// Title & Message <16chars chacun
				if ($myPin == 'WS2811')
				{
					$value = strtoupper($value);
					if (!$Altern) $PinValue = 'C' . sprintf("%02s", $pins_id) . 'L' . sprintf("%06s", $value) . 'R'; // C09LFF00FFR >>>Led Strip sur pin 09 valeur FF00FF (color)
					else $PinValue = 'C' . sprintf("%02s", $pins_id) . 'M' . sprintf("%02s", $value) . 'R'; // C09M12R >>>Led Strip sur pin 09 valeur 12 (effet)
				}
			}
			jeedouino::log( 'debug', 'ConfigurePinValue ' . $ModeleArduino . ' ( ' . $arduino_id . ' ) ' . "PinValue : " . $PinValue);
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
				jeedouino::log('debug', __('ERREUR SETTING PIN VALUE eqID ( ', __FILE__) . $arduino_id . ' )- Réponse :' . $reponse);
				return false;
			}
		}
		return true;
	}

	public function SendToBoard($arduino_id, $message)
	{
		$my_arduino = eqLogic::byid($arduino_id);
		$PortArduino = $my_arduino->getConfiguration('datasource');
		$IPArduino = $my_arduino->getConfiguration('iparduino');
		$ipPort = $my_arduino->getConfiguration('ipPort');

		if ($PortArduino == 'rj45arduino')		// envoi sur reseau local
		{
			$message .= "\n";
			$fp = @fsockopen($IPArduino, $ipPort, $errno, $errstr, 3);
			if ($fp === false)
			{
				$oldport = config::byKey($arduino_id . '_OLDPORT', 'jeedouino', '');
				if ($oldport != '') $fp = @fsockopen($IPArduino, $oldport, $errno, $errstr, 3);
				if ($fp === false)
				{
					jeedouino::log('error', __('ERREUR DE CONNECTION  (', __FILE__).$IPArduino.':'.$ipPort.') : '. $errno.' - '.$errstr);
					return 'NOK';
				}
				$tell = __('Attention vous avez changé le port (', __FILE__) . $oldport . __('), il faudra reflasher ou le remettre !  (Nouveau : ', __FILE__) . $IPArduino . ':' . $ipPort . ') ';
				event::add('jeedom::alert', array(
					'level' => 'warning',
					'page' => 'jeedouino',
					'message' => $tell
					));
				jeedouino::log('info', $tell);
			}

			stream_set_timeout($fp, 9);
			fwrite($fp, $message);
			$reponse = '';
			$debut = time();
			while (!feof($fp))
			{
				$reponse .= fgets($fp);
				if  ((time() - $debut) > 9) break;
			}
			fclose($fp);

			$reponse = trim($reponse);
			// Si pas de réponse directe, on va essayer de voir, si on l'a recue via un callback (utile en cas de lags)
			if ($reponse == '') $reponse = config::byKey('REP_'.$arduino_id, 'jeedouino', '');	// Double vérif
			config::save('REP_' . $arduino_id, '', 'jeedouino');  // On supprime pour les appels suivants.

			if ($reponse == '') $reponse = 'TIMEOUT';

			jeedouino::log( 'debug','REPONSE DE CONNECTION :' . $reponse);
			return $reponse;
		}
		else			// envoi sur usb
		{
			return self::SendToBoardDemon($arduino_id, 'USB=' . $message, 'USB');
		}
		return 'NOK';
	}

	// Permet de proposer un port libre lors d'un nouvel équipement.

	public function GiveMeFreePort($TypePort = '')
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

	public function CallSlaveExt($CallCmd, $params)
	{
		$board_id = $params['eqLogic'];
		$board = eqLogic::byid($board_id);
		$JeedouinoAlone = $board->getConfiguration('alone');
		if ($JeedouinoAlone == '1')	// JeedouinoExt Rpi sans Jeedom.
		{
			jeedouino::log('debug', __('CallSlaveExt Envoi de la commande : ', __FILE__) . $CallCmd . __(' sur JeedouinoExt : ', __FILE__) . $board->getName() . ' (eqID ' . $board_id . ') -> ' . json_encode($params));
			$reponse = self::CallJeedouinoExt($board_id, $CallCmd, json_encode($params), 80); // EqLogic, Cmd, Array Params
			if ($reponse != 'OK')
			{
				jeedouino::log( 'error', __('Problème d\'envoi de la commande : ', __FILE__) . $CallCmd . __(' sur JeedouinoExt : ', __FILE__) . $board->getName() . ' (eqID ' . $board_id . ') -> Réponse :' . $reponse);
				return false;
			}
		}
		else
		{
			jeedouino::log('error', __('Equipement: ', __FILE__) . strtoupper($board->getName()) . __(' => Impossible de trouver JeedouinoExt sur l\'IP fournie - JeedouinoExt n\'est peut-être pas installé dessus ou l\'IP est incorrecte.', __FILE__));
			return false;
		}
		return true;
	}

	public function CallJeedouinoExt($board_id, $CallCmd, $params ,$_port='')
	{
		$board = eqLogic::byid($board_id);
		$IPboard = $board->getConfiguration('iparduino');

		$message  = trim($CallCmd . "=" . urlencode($params));
		// $_path et $Port sont fournis par la page JeedouinoExt de l'ip concernée.
		$_path = trim(config::byKey('path-' . $IPboard, 'jeedouino', '/'));
		$Port = trim(config::byKey('PORT-' . $IPboard, 'jeedouino', '80'));
		// nettoyage de path
		$_path = str_replace('/var', '', $_path);
		$_path = str_replace('/www', '', $_path);
		$_path = str_replace('/html', '', $_path);

		jeedouino::log('debug', 'CallJeedouinoExt (' . $IPboard . ':' . $Port . ') $message -> ' . $message);

		$url = 'http://' . $IPboard . ':' . $Port . $_path . 'JeedouinoExt.php?' . $message;

		$reponse = trim(file_get_contents($url));
		jeedouino::log('debug', 'CallJeedouinoExt (' . $IPboard . ':' . $Port . __(') Réponse pour ', __FILE__) . $CallCmd . ' -> ' . $reponse);
		return $reponse;
	}

	//////
	// Gestion Equipement Controle (demons)
	public static function CreateJeedouinoControl()
	{
		$eqLogics = eqLogic::byType('jeedouino');
		$Control = jeedouino::byLogicalId('JeedouinoControl', 'jeedouino');
		if (!is_object($Control))
		{
			$Control = new jeedouino();
			$Control->setName(__('Jeedouino Control', __FILE__));
			$Control->setIsEnable(1);
			$Control->setIsVisible(1);
			jeedouino::log('debug',__('NEW : Added JeedouinoControl', __FILE__));
		}
		$Control->setLogicalId('JeedouinoControl');
		$Control->setConfiguration('LogicalId', 'JeedouinoControl');
		$Control->setEqType_name('jeedouino');
		$Control->save();
		// pour respecter l'ordre des commandes si possible
		// et nettoyage des commandes inexisantes/inutiles(suppression/desactivation d'un equipement)
		$i = 7;
		$cmds = cmd::byEqLogicId($Control->getId());
		foreach($cmds as $cmd)
		{
			$eqLogic = eqLogic::byid($cmd->getConfiguration('boardid')); // eqLogic_id dont depend la cmd
			//jeedouino::log('debug', '$cmd->getConfiguration(boardid)' . $cmd->getConfiguration('boardid'));
			if (!is_object($eqLogic) or $eqLogic->getIsEnable() == 0) $cmd->remove();
			else if ($cmd->getOrder() >= $i) $i = $cmd->getOrder() + 1;
		}

		foreach ($eqLogics as $eqLogic)
		{
			if ($eqLogic->getIsEnable() == 0) continue;
			if (!config::byKey($eqLogic->getId() . '_HasDemon', 'jeedouino', 0)) continue;
			// si pas de object_id (parent) On essai d'en trouver un pour JeedouinoControl
			if ($Control->getObject_id() == '' or $Control->getObject_id() === null)
			{
				$Control->setObject_id($eqLogic->getObject_id());
				$Control->save();
			}
			// Cmds status démons
			$cmd = $Control->getCmd(null, 'StatusDaemon' . $eqLogic->getId());
			if (!is_object($cmd))
			{
				$cmd = new jeedouinoCmd();
				$cmd->setName(__('Etat Démon', __FILE__) . ' ' . $eqLogic->getName());
				$cmd->setTemplate('mobile', 'line');
				$cmd->setTemplate('dashboard', 'line');
				$cmd->setOrder($i++);
			}
			$cmd->setConfiguration('control', 'JeedouinoControl');
			$cmd->setConfiguration('boardid', $eqLogic->getId());
			$cmd->setConfiguration('demontype', $eqLogic->getConfiguration('arduino_board'));
			$cmd->setEqLogic_id($Control->getId());
			$cmd->setLogicalId('StatusDaemon' . $eqLogic->getId());
			$cmd->setType('info');
			$cmd->setSubType('binary');
			$cmd->save();
			$cmd->event(config::byKey($eqLogic->getId() . '_StatusDemon', 'jeedouino', 0));

			// Cmds start démons
			$cmd = $Control->getCmd(null, 'StartDaemon' . $eqLogic->getId());
			if (!is_object($cmd))
			{
				$cmd = new jeedouinoCmd();
				$cmd->setName(__('Start Démon', __FILE__) . ' ' . $eqLogic->getName());
				$cmd->setOrder($i++);
			}
			$cmd->setConfiguration('control', 'JeedouinoControl');
			$cmd->setConfiguration('boardid', $eqLogic->getId());
			$cmd->setConfiguration('demontype', $eqLogic->getConfiguration('arduino_board'));
			$cmd->setEqLogic_id($Control->getId());
			$cmd->setLogicalId('StartDaemon' . $eqLogic->getId());
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->save();

			// Cmds stop démons
			$cmd = $Control->getCmd(null, 'StopDaemon' . $eqLogic->getId());
			if (!is_object($cmd))
			{
				$cmd = new jeedouinoCmd();
				$cmd->setName(__('Stop Démon', __FILE__) . ' ' . $eqLogic->getName());
				$cmd->setOrder($i++);
			}
			$cmd->setConfiguration('control', 'JeedouinoControl');
			$cmd->setConfiguration('boardid', $eqLogic->getId());
			$cmd->setConfiguration('demontype', $eqLogic->getConfiguration('arduino_board'));
			$cmd->setEqLogic_id($Control->getId());
			$cmd->setLogicalId('StopDaemon' . $eqLogic->getId());
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->save();
		}
		// cmd Refresh
		$cmd = $Control->getCmd(null, 'refresh');
		if (!is_object($cmd))
		{
			$cmd = new jeedouinoCmd();
			$cmd->setName(__('Rafraichir', __FILE__));
			$cmd->setOrder(0);
		}
		$cmd->setConfiguration('control', 'JeedouinoControl');
		$cmd->setConfiguration('boardid', $eqLogic->getId());
		$cmd->setConfiguration('demontype', $eqLogic->getConfiguration('arduino_board'));
		$cmd->setEqLogic_id($Control->getId());
		$cmd->setLogicalId('refresh');
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->save();
		$Control->refreshWidget();
	}

	public function updateDemons()
	{
		$eqLogics = eqLogic::byType('jeedouino');
 		jeedouino::CreateJeedouinoControl();
		$Control = jeedouino::byLogicalId('JeedouinoControl', 'jeedouino');
		foreach ($eqLogics as $eqLogic)
		{
			if ($eqLogic->getIsEnable() == 0) continue;
			$ModeleArduino = $eqLogic->getConfiguration('arduino_board');
			$StatusDemon = false;
			if (($eqLogic->getConfiguration('datasource') == 'usbarduino' and substr($ModeleArduino, 0, 1) == 'a') or $ModeleArduino == 'piface' or $ModeleArduino == 'piGPIO26' or $ModeleArduino == 'piGPIO40' or $ModeleArduino == 'piPlus')
			{
				config::save($eqLogic->getId().'_HasDemon', 1, 'jeedouino');
				$StatusDemon = jeedouino::StatusBoardDemon($eqLogic->getId(), 0, $ModeleArduino);
			}
			else config::save($eqLogic->getId().'_HasDemon', 0, 'jeedouino');

			config::save($eqLogic->getId().'_StatusDemon', $StatusDemon, 'jeedouino');

			if (is_object($Control))
			{
				$cmd = $Control->getCmd(null, 'StatusDaemon' . $eqLogic->getId());
				//jeedouino::log('debug','StatusDaemon' . $eqLogic->getId() . ' : ' . json_encode($cmd) );
				if (is_object($cmd))
				{
					//jeedouino::log('debug','StatusDaemon' . $eqLogic->getId() . ' , event :' . $StatusDemon );
					$cmd->event($StatusDemon);
					$cmd->save();
				}
			}
		}
		$Control->refreshWidget();
	}
	//////
	// Gestion JeedouinoExt

	public function AddIPJeedouinoExt($ip)
	{
		// On a reçu une nouvelle ip ? Si oui on l'ajoute à la liste
		$ListExtIP = config::byKey('ListExtIP', 'jeedouino', '');
		if ($ListExtIP == '')
		{
			$ListExtIP = array($ip);
		}
		else
		{
			if (!in_array($ip, $ListExtIP))	$ListExtIP[] = $ip;
		}
		config::save('ListExtIP', $ListExtIP, 'jeedouino');
	}
	public function AddIDJeedouinoExt($ip)
	{
		// On a reçu une nouvelle ip ? Si oui on ajoute un ID à la liste
		$id = trim(config::byKey('ID-' . $ip, 'jeedouino', ''));
		if ($id == '')
		{
			$id = ip2long($ip);
		}
		config::save('ID-' . $ip, $id, 'jeedouino');
		return $id;
	}
	public function IPfromIDJeedouinoExt($id)
	{
		//jeedouino::log('debug', 'long2ip (id : ' . $id . ')');
		if (is_numeric($id)) return long2ip($id);
		jeedouino::log('debug', 'long2ip (id : ' . $id . ') n\'est pas un entier long.');
		return "Undefined";
	}
	public function SaveIPJeedouinoExt($jeedouino_ext)
	{
		$ip = $jeedouino_ext['IP'];
		jeedouino::AddIPJeedouinoExt($ip);
		$id = jeedouino::AddIDJeedouinoExt($ip);
		jeedouino::log('debug', 'IP => ' . $ip);
		jeedouino::log('debug', 'ID => ' . $id);
		config::save('JExtname-'.$ip, $jeedouino_ext['name'], 'jeedouino');
		config::save('JExtSSH-'.$ip, $jeedouino_ext['sshID'], 'jeedouino');
		config::save('JExtPW-'.$ip, $jeedouino_ext['sshPW'], 'jeedouino');
		config::save('JExtPortSSH-'.$ip, $jeedouino_ext['sshPort'], 'jeedouino');
		return $id;
	}
	public function GetJeedouinoExt($ip)
	{
		$id = trim(config::byKey('ID-' . $ip, 'jeedouino', ''));
		if ($id == '') $id = jeedouino::AddIDJeedouinoExt($ip);
		$path = trim(config::byKey('path-' . $ip, 'jeedouino', '/'));
		if ($path == '') $path = '/';
		$port = trim(config::byKey('PORT-' . $ip, 'jeedouino', '80'));
		if ($port == '') $port = '80';
		$UsbMap = config::byKey('uMap-' . $ip, 'jeedouino', '');
		if (!is_array($UsbMap))  $UsbMap = [];
		$UsbMap = json_encode($UsbMap);
		$JExtname = trim(config::byKey('JExtname-' . $ip, 'jeedouino', 'JeedouinoExt'));
		$JExtSSH = trim(config::byKey('JExtSSH-' . $ip, 'jeedouino', 'SSH_' . $ip));
		$JExtPW = trim(config::byKey('JExtPW-' . $ip, 'jeedouino', ''));
		$JExtPortSSH = trim(config::byKey('JExtPortSSH-' . $ip, 'jeedouino', '22'));
		return ['id' 		=> $id,
				'name' 		=> $JExtname,
				'IP' 		=> $ip,
				'sshID' 	=> $JExtSSH,
				'sshPW' 	=> $JExtPW,
				'sshPort' 	=> $JExtPortSSH,
				'URLpath' 	=> $path,
				'URLport' 	=> $port,
				'usbMap' 	=> $UsbMap];
	}

	public function CleanIPJeedouinoExt()
	{
		$ListExtIP = config::byKey('ListExtIP', 'jeedouino', '');
		if ($ListExtIP != '')
		{
			// un petit nettoyage
			$ListExtIPCleaned = array();
			foreach ($ListExtIP as $_ip)
			{
				if (filter_var($_ip, FILTER_VALIDATE_IP) === false) continue;
				$ListExtIPCleaned[] = $_ip;
			}
			$ListExtIP = $ListExtIPCleaned;
			config::save('ListExtIP', $ListExtIP, 'jeedouino');
		}
		return $ListExtIP;
	}

	public function allJeedouinoExt()
	{
		$ListExtIP = jeedouino::CleanIPJeedouinoExt();
		$all = [];
		foreach ($ListExtIP as $ip)
		{
			$all[] = jeedouino::GetJeedouinoExt($ip);
		}
		return $all;
	}

	public function RemoveJeedouinoExt($ip)
	{
		if (filter_var($ip, FILTER_VALIDATE_IP) !== false)
		{
			$ListExtIP = jeedouino::CleanIPJeedouinoExt();
			if (in_array($ip, $ListExtIP))
			{
				config::remove('ID-' . $ip, 'jeedouino');
				config::remove('path-' . $ip, 'jeedouino');
				config::remove('PORT-' . $ip, 'jeedouino');
				config::remove('uMap-' . $ip, 'jeedouino');
				config::remove('JExtname-' . $ip, 'jeedouino');
				config::remove('JExtSSH-' . $ip, 'jeedouino');
				config::remove('JExtPW-' . $ip, 'jeedouino');
				config::remove('JExtPortSSH-' . $ip, 'jeedouino');
				$ListExtIPCleaned = array();
				foreach ($ListExtIP as $_ip)
				{
					if ($ip != $_ip) $ListExtIPCleaned[] = $_ip;
				}
				config::save('ListExtIP', $ListExtIPCleaned, 'jeedouino');
				jeedouino::log('debug', '! Suppression effectuée du JeedouinoExt sur IP : ' . $ip);
				return true;
			}
			else
			{
				jeedouino::log('error', '! Impossible de supprimer ce JeedouinoExt. IP non trouvée : ' . $ip);
			}
		}
		else
		{
			jeedouino::log('error', '! Impossible de supprimer ce JeedouinoExt. IP non valide : ' . $ip);
		}
		return false;
	}

	public function SendJeedouinoExt($jeedouino_ext, $Noinstall = false)
	{
		jeedouino::log( 'info', __('Envoi des fichiers JeedouinoExt sur ', __FILE__) . $jeedouino_ext['IP']);
		$file_path = dirname(__FILE__) . '/../../ressources/JeedouinoExt.zip';
		$to_path = '/tmp/JeedouinoExt.zip';
		$sh_path = '/tmp/JeedouinoExt/JeedouinoExt.sh >> /tmp/InstallJeedouinoExt.log 2>&1 &';
		if ($Noinstall) $sh_path = '/tmp/JeedouinoExt/JeedouinoExt2.sh >> /var/www/html/JeedouinoExt/JeedouinoExt.log 2>&1 &';

		// test
		$jeedouinocfg = '{"IP":"' . jeedouino::GetJeedomIP(). '","Port":"' . jeedouino::GetJeedomPort(). '","Cpl":"' . jeedouino::GetJeedomComplement(). '"}';
		$test = "echo '" . $jeedouinocfg . "' | sudo tee /var/www/html/JeedouinoExt/jeedouino.cfg";

		if (!$connection = ssh2_connect($jeedouino_ext['IP'], $jeedouino_ext['sshPort']))
		{
			jeedouino::log( 'error', __('Connection SSH impossible sur ', __FILE__) . $jeedouino_ext['IP']);
			return false;
		}
		else
		{
			if (!ssh2_auth_password($connection, $jeedouino_ext['sshID'], $jeedouino_ext['sshPW']))
			{
				jeedouino::log( 'error', __('Authentification SSH impossible sur ', __FILE__) . $jeedouino_ext['IP']);
				return false;
			}
			else
			{
				if (!$result = ssh2_scp_send($connection, $file_path, $to_path, 0777))
				{
					jeedouino::log( 'error', __('Envoi du fichier ', __FILE__) . $file_path. __(' impossible sur ', __FILE__) . $jeedouino_ext['IP']);
					return false;
				}
				$preCmd = "echo '" . $jeedouino_ext['sshPW'] . "' | sudo -S ";
				$result = jeedouino::SshCmdJeedouinoExt($connection, $preCmd, 'unzip ' . $to_path . ' -d /tmp');
				$result = jeedouino::SshCmdJeedouinoExt($connection, $preCmd, '/bin/bash ' . $sh_path);
				$result = jeedouino::SshCmdJeedouinoExt($connection, '', $test);
				$result = jeedouino::SshCmdJeedouinoExt($connection, '', 'exit');
			}
		}
		if ($Noinstall)
		{
			foreach (eqLogic::byType('jeedouino') as $eqLogic)
			{
				if ($eqLogic->getConfiguration('iparduino') == $jeedouino_ext['IP'])
				{
					jeedouino::SendPRM($eqLogic); // on renvoie la config
					jeedouino::ReStartBoardDemon($eqLogic->getId(), 0, $eqLogic->getConfiguration('arduino_board'));
				}
			}
		}
		return true;
	}

	public function SshCmdJeedouinoExt($connection, $preCmd, $cmd)
	{
		$stream = ssh2_exec($connection, $preCmd . $cmd);
		$error = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
		stream_set_blocking($error, true);
		stream_set_blocking($stream, true);
		$output = trim(stream_get_contents($stream));
		fclose($error);
		fclose($stream);
		if ($output != '')
		{
			jeedouino::log( 'error', __('Envoi via SSH de la commande : ', __FILE__) . $cmd . __(' impossible. ', __FILE__));
			return false;
		}
		jeedouino::log( 'debug', __('Réponse via SSH de la commande : ', __FILE__) . $cmd . ' : ' . $output);
		return true;
	}
	public function SshGetJeedouinoExt($jeedouino_ext, $local, $distant)
	{
		jeedouino::log( 'info', __('Téléchargement via SSH d\'un fichier de JeedouinoExt depuis l\'IP: ', __FILE__) . $jeedouino_ext['IP']);
		if (!$connection = ssh2_connect($jeedouino_ext['IP'], $jeedouino_ext['sshPort']))
		{
			jeedouino::log( 'error', __('Connection SSH impossible sur ', __FILE__) . $jeedouino_ext['IP']);
			return false;
		}
		else
		{
			if (!ssh2_auth_password($connection, $jeedouino_ext['sshID'], $jeedouino_ext['sshPW']))
			{
				jeedouino::log( 'error', __('Authentification SSH impossible sur ', __FILE__) . $jeedouino_ext['IP']);
				return false;
			}
			else
			{
				$preCmd = "echo '" . $jeedouino_ext['sshPW'] . "' | sudo -S ";
				$result = ssh2_scp_recv($connection, $distant, $local);
				$res= jeedouino::SshCmdJeedouinoExt($connection, $preCmd, 'exit');
				return $result;
			}
		}
		return true;
	}
	public function getExtLog($_log)
	{
		$log = file($_log);
		if ($log !== false) return $log;
		else return [];
	}
	public function SendSSHCmdsJeedouinoExt($jeedouino_ext, $cmds)
	{
		jeedouino::log( 'info', __('Envoi de commandes SSH pour JeedouinoExt sur ', __FILE__) . $jeedouino_ext['IP']);
		if (!$connection = ssh2_connect($jeedouino_ext['IP'], $jeedouino_ext['sshPort']))
		{
			jeedouino::log( 'error', __('Connection SSH impossible sur ', __FILE__) . $jeedouino_ext['IP']);
			return false;
		}
		else
		{
			if (!ssh2_auth_password($connection, $jeedouino_ext['sshID'], $jeedouino_ext['sshPW']))
			{
				jeedouino::log( 'error', __('Authentification SSH impossible sur ', __FILE__) . $jeedouino_ext['IP']);
				return false;
			}
			else
			{
				$preCmd = "echo '" . $jeedouino_ext['sshPW'] . "' | sudo -S ";
				foreach ($cmds as $cmd)
				{
					if (!$result = jeedouino::SshCmdJeedouinoExt($connection, $preCmd, $cmd)) return false;
				}
				$result = jeedouino::SshCmdJeedouinoExt($connection, $preCmd, 'exit');
			}
		}
		return true;
	}
	//
	//////

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
		$IPBoard = trim($my_Board->getConfiguration('iparduino'));
		$ipPort = $my_Board->getConfiguration('ipPort');
		if ($DemonTypeF == 'USB' ) $ipPort = $my_Board->getConfiguration('PortDemon');

		$message = "GET ?".$message." HTTP/1.1\r\n";
		$message .= "Host: ".self::GetJeedomIP()."\r\n";
		$message .= "Connection: Close\r\n\r\n";

		$fp = false;
		$IPJeedom = self::GetJeedomIP();
		if (config::byKey($board_id . '_IpLocale', 'jeedouino', 1)) // Adresse IP locale pour le démon différente de celle de jeedom ?
		{
			if (substr($IPBoard, 0, 5) == 'local') $IPBoard = '127.0.0.1';
			if ($IPJeedom != $IPBoard and $IPBoard != '127.0.0.1')
			{
				if ($IPBoard == '') $IPBoard = __(' non definie ', __FILE__);
				event::add('jeedom::alert', array(
					'level' => 'danger',
					'page' => 'jeedouino',
					'message' => __('Attention l\'IP (', __FILE__) . $IPBoard . __(') du démon local ', __FILE__) . $DemonTypeF . ' (' . $name . ' - EqID ' . $board_id . __(') et de Jeedom (', __FILE__) . $IPJeedom . __(') diffèrent. Veuillez vérifier.' , __FILE__)
					));
				jeedouino::log('error', __('Attention l\'IP (', __FILE__) . $IPBoard . __(') du démon local ', __FILE__) . $DemonTypeF . ' (' . $name . ' - EqID ' . $board_id . __(') et de Jeedom (', __FILE__) . $IPJeedom . __(') diffèrent. Veuillez vérifier. ', __FILE__));
				$IPBoard = $IPJeedom;
			}
		}
		else
		{
			if ($IPBoard == '')
			{
				event::add('jeedom::alert', array(
					'level' => 'danger',
					'page' => 'jeedouino',
					'message' => __('Attention l\'IP du démon ', __FILE__) . $DemonTypeF . ' (' . $name . ' - EqID ' . $board_id . __(') n\'est pas définie. Veuillez vérifier.' , __FILE__)
					));
				jeedouino::log('error', __('Attention l\'IP du démon ', __FILE__) . $DemonTypeF . ' (' . $name . ' - EqID ' . $board_id . __(') n\'est pas définie. Veuillez vérifier. ', __FILE__));
				$IPBoard = $IPJeedom;
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
				if (!config::byKey('StartDemons', 'jeedouino', 0)) jeedouino::log( 'debug', __('(Normal si Re/Start/Stop demandé) Erreur de connection au démon ', __FILE__) . $DemonTypeF . ' ( ' . $name . ' - EqID ' . $board_id . ' ) ' . $IPBoard . ':' . $ipPort. __(' - Réponse : ', __FILE__) . $reponse);
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
	public function StartBoardDemon($board_id, $useless=0, $DemonType)	// Démarre le Démon
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null) return false;

		$my_Board = eqLogic::byid($board_id);
		$name = $my_Board->getName();

		jeedouino::log( 'debug', __('Démarrage du démon ', __FILE__) . $DemonTypeF . __(' de l\'équipement ', __FILE__) . $name);
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
			$portusbdeporte = $my_Board->getConfiguration('portusbdeporte');   // portusbdeporte (ExtID_portUSB)
			if ($LocalArduino == 'usblocal')
			{
				$portUSB = $my_Board->getConfiguration('portusblocal');
				// MàJ de l'ip de l'arduino usb au cas où celle de Jeedom ai changée depuis la sauvegarde de l'équipement.
				if ($IPBoard != '127.0.0.1') $IPBoard = self::GetJeedomIP();
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
		//if (self::StatusBoardDemon($board_id, 0, $DemonType)) self::StopBoardDemon($board_id, 0, $DemonType);

		if ($jeedomIP == $IPBoard or $IPBoard == '127.0.0.1' or config::byKey($board_id . '_IpLocale', 'jeedouino', 1))
			$result = self::StartBoardDemonCMD($board_id, $DemonType, $ipPort, $IPBoard, $JeedomPort, $JeedomCPL, $PiPlusBoardID, $PiFaceBoardID, $PortDemon, $portUSB);
		else
		{
			$result = self::CallSlaveExt('StartBoardDemonCMD', array(	'plugin' => 'jeedouino',
			 												'eqLogic' 		=> $board_id,
															'DemonType' 	=> $DemonType,
															'ipPort' 		=> $ipPort,
															'jeedomIP' 		=> $jeedomIP,
															'JeedomPort' 	=> $JeedomPort,
															'JeedomCPL' 	=> $JeedomCPL,
															'PiPlusBoardID' => $PiPlusBoardID,
															'PiFaceBoardID' => $PiFaceBoardID,
															'PortDemon' 	=> $PortDemon,
															'portUSB' 		=> $portUSB));
		}
		if (!$result) return false;
		if (!jeedouino::IsNoDep($board_id)) return false;
		// Après un démarrage/redémarrage, on renvoi la config des pins
		$PinMode = config::byKey($board_id . '_PinMode', 'jeedouino', 'none');
		if ($PinMode != 'none')
		{
			if (($DemonTypeF == 'PiGpio') or ($DemonTypeF == 'PiPlus'))
			{
				sleep(2);
				$BootMode = 'BootMode=' . config::byKey($board_id . '_' . $DemonTypeF . '_boot', 'jeedouino', '0');
				jeedouino::log( 'debug', __('Envoi de la dernière configuration connue du BootMode eqID ( ', __FILE__) . $board_id . ' ) ' . "BootMode : " . $BootMode);
				$reponse = self::SendToBoardDemon($board_id, $BootMode, $DemonType);
				if ($reponse != 'BMOK')
				{
					jeedouino::log( 'debug', __('Erreur d\'envoi de la configuration du BootMode sur l\'équipement ', __FILE__) . $board_id . ' ( ' . $name . ' ) - Réponse :' . $reponse);
					config::byKey($board_id . '_' . $DemonTypeF . 'DaemonState', 'jeedouino', false);
				}
				else
				{
					config::save('NODEP_' . $board_id, '', 'jeedouino');
					config::byKey($board_id . '_' . $DemonTypeF . 'DaemonState', 'jeedouino', true);
				}
			}
			elseif ($DemonTypeF == 'USB' ) $PinMode = 'USB=' . $PinMode;

			//jeedouino::log( 'debug', "Pause de 4s pour laisser l'arduino finir de communiquer avec le démon qui vient de demarrer");
			sleep(2);
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

				jeedouino::log( 'debug', __('Essai ', __FILE__) . $_try . __(' - Envoi de la dernière configuration connue des pins eqID ( ', __FILE__) . $board_id . ' ) ' . "PinMode : " . $PinMode );
				$reponse = self::SendToBoardDemon($board_id, $PinMode, $DemonType);
				if (in_array($reponse, $waitforArr))
				{
					jeedouino::log( 'debug', __('Réponse différée reçue de l\'équipement ', __FILE__) . $board_id . ' ( ' . $name . ' ) - Réponse :' . $reponse);
					config::byKey($board_id . '_' . $DemonTypeF . 'DaemonState', 'jeedouino', true);
				}
				elseif ($reponse != 'COK')
				{
					config::byKey($board_id . '_' . $DemonTypeF . 'DaemonState', 'jeedouino', false);
					jeedouino::log( 'debug', __('Erreur d\'envoi de la configuration des pins sur l\'équipement ', __FILE__) . $board_id.' ( ' . $name . ' ) - Réponse :' . $reponse);
				}
				sleep(2);
			}
			if ($reponse == 'COK')
			{
				config::save('NODEP_' . $board_id, '', 'jeedouino');
				config::byKey($board_id . '_' . $DemonTypeF . 'DaemonState', 'jeedouino', true);
			}
			config::save('SENDING_'.$board_id, 0, 'jeedouino');
		}
	}
	public function IsNoDep($board_id)
	{
		$NODEP = config::byKey('NODEP_' . $board_id, 'jeedouino', '');
		if ($NODEP != '')
		{
			$message = __('Dépendances ', __FILE__) . ucfirst(strtolower($NODEP)) . __(' introuvables. Imposssible de démarrer le démon.' , __FILE__);
			event::add('jeedom::error', array(
				'level' => 'warning',
				'page' => 'jeedouino',
				'message' => $message
				));
			jeedouino::log('error', $message);
			return false;
		}
		return true;
	}
	public function StartBoardDemonCMD($board_id = '', $DemonType, $ipPort, $jeedomIP, $JeedomPort, $JeedomCPL, $PiPlusBoardID, $PiFaceBoardID, $PortDemon, $_portUSB)	// Démarre le Démon
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null or $board_id == '') return false;

		$jeedouinoPATH = realpath(dirname(__FILE__) . '/../../ressources');
		$jeedouinoFile = '/jeedouino' . $DemonTypeF;

		$filename = $jeedouinoPATH . $jeedouinoFile . '_' . $board_id . '.py';
		if (file_exists($filename)) unlink($filename);

		$DemonFileName = $jeedouinoPATH . $jeedouinoFile . '.py';
		if (!copy($DemonFileName, $filename))
		{
			jeedouino::log( 'error', __(' Impossible de créer le fichier pour le démon ', __FILE__) . $filename . '.');
			$filename = $DemonFileName;
		}

		self::StopBoardDemonCMD($board_id, $DemonType); // Stoppe le(s) processus du Démon local
		config::save($board_id . '_' . $DemonTypeF . 'DaemonState', false, 'jeedouino');

		$_ProbeDelay = config::byKey($board_id . '_ProbeDelay', 'jeedouino', '5');
		if ($JeedomCPL == '') $JeedomCPL = '.';
		switch ($DemonTypeF)
		{
			case 'USB':
				$portUSB = trim(jeedom::getUsbMapping($_portUSB));
				if ($portUSB == '')	$portUSB = trim(self::getUsbMapping($_portUSB));
				if ($portUSB == '')
				{
					jeedouino::log( 'error', __('Appel démon ArduinoUsb impossible - port USB vide !.', __FILE__));
					return false;
				}
				$baudrate = 115200;
				if (config::byKey($board_id . '_SomfyRTS', 'jeedouino', 0)) $baudrate /=  2;
				jeedouino::log( 'debug', __('Appel démon ArduinoUsb sur port :', __FILE__) . $_portUSB . ' ( ' . $portUSB . ' ) - Baudrate : ' . $baudrate);
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
		$cmd = "sudo nice -n 19 /usr/bin/python3 " . $filename . ' ' . $cmd;
		$_log = jeedouino::getPathToLog('jeedouino_' . strtolower($DemonTypeF));
		$cmd .= ' ' . $_log;
		jeedouino::log( 'debug', __('Cmd Appel démon : ', __FILE__) . $cmd);
		$reponse = exec($cmd . ' >> ' . $_log . ' 2>&1 &');

		if ((strpos(strtolower($reponse), 'error') !== false) or (strpos(strtolower($reponse), 'traceback') !== false))
		{
			jeedouino::log( 'error', __('Le démon ', __FILE__) . $DemonTypeF . __(' ne démarre pas. - Réponse :', __FILE__) . $reponse);
			return false;
		}
		else  jeedouino::log('debug', __('Le démon ', __FILE__) . $DemonTypeF . __(' est en cours de démarrage.  - ', __FILE__) . $reponse);
		config::save($board_id . '_' . $DemonTypeF . 'DaemonState', true, 'jeedouino');
		return true;
	}
	public function StopBoardDemon($board_id, $useless=0, $DemonType)	// Stoppe le Démon
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null) return;
		// Arrét soft
		jeedouino::log( 'debug',  __('Demande d\'arrêt au démon ', __FILE__) . $DemonTypeF .  __(' de l\'équipement : ', __FILE__) . eqLogic::byid($board_id)->getName() . ' ( eqID ' . $board_id . ' )');
		$reponse = self::SendToBoardDemon($board_id, 'EXIT=1', $DemonType);
		if ($reponse != 'EXITOK') jeedouino::log( 'error',  __('Le démon ', __FILE__) . $DemonTypeF .  __(' ne réponds pas correctement. - Réponse : ', __FILE__) . $reponse);
		else
		{
			usleep(1000000);  // 1s , petite pause pour laisser au script python le temps de stopper
			jeedouino::log( 'debug',  __('Le démon ', __FILE__) . $DemonTypeF .  __(' est stoppé (SOFT EXIT) - Réponse : ', __FILE__) . $reponse);
		}
		config::save($board_id . '_OLDPORT', '', 'jeedouino');
		// est il toujours en marche (processus)? Arrét hard.
		self::ForceStopBoardDemon($board_id, 0, $DemonType);
		config::save($board_id . '_' . $DemonTypeF . 'DaemonState', false, 'jeedouino');
	}
	public function ForceStopBoardDemon($board_id, $useless=0, $DemonType)	// Stoppe le(s) processus du Démon local ou déporté
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null) return;

		$my_Board = eqLogic::byid($board_id);
		$IPBoard = $my_Board->getConfiguration('iparduino');

		$jeedomIP = self::GetJeedomIP();
		if ($jeedomIP == $IPBoard) self::StopBoardDemonCMD($board_id, $DemonType);   // sur local  (master ou esclave)
		else
		{
			self::CallSlaveExt('StopBoardDemonCMD', array('plugin' => 'jeedouino',
			 											'eqLogic' => $board_id,
														'DemonType' => $DemonType));
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
			exec('sudo kill -9 ' . $process . ' >> ' . jeedouino::getPathToLog('jeedouino_' . strtolower($DemonTypeF)) . ' 2>&1 &');
			$done = true;
			usleep(500000); // 0.5 secondes
		}
		sleep(3);
		if ($done)
		{
			jeedouino::log( 'debug','StopBoardDemonCMD - Arrêt forcé du démon ' . $DemonTypeF . ' sur  '.self::GetJeedomIP().' - '.$DemonFileName.' : Kill process : '.json_encode($processus));
		}
	}
	public function StatusBoardDemon($_board_id, $forceCache = 0, $DemonType)	 // Démon en marche ???
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null) return false;
		if ($_board_id == 0) $_board_id = $this->getEqLogic_id();
		$id_type = '( EqID: ' . $_board_id . ' ) Démon ' . $DemonTypeF;

		$Time = time() + 180; // duree du cache 3 min
		$LastPING = config::byKey($_board_id . '_' . $DemonTypeF . 'LastPING', 'jeedouino', 0);
		if ($LastPING > time() and $forceCache == 0)
		{
			jeedouino::log( 'debug','PING ' . $id_type . __(' déja sollicité il y a moins de 3 minutes. Renvoi de la valeur cache...', __FILE__));
			return config::byKey($_board_id . '_' . $DemonTypeF . 'DaemonState', 'jeedouino', false);
		}
		config::save($_board_id . '_' . $DemonTypeF . 'LastPING', $Time, 'jeedouino');

		jeedouino::log( 'debug','PING ' . $id_type . __(' en marche ??? Envoi d\'un PING...', __FILE__));
 		$reponse = self::SendToBoardDemon($_board_id, 'PING=1', $DemonType); // On le PINGue

		config::save($_board_id . '_' . $DemonTypeF . 'DaemonState', false, 'jeedouino');
		if (strpos($reponse, '111') !== false) return false; // Connection refused
		if ($reponse != 'PINGOK')
		{
			sleep(1);
			jeedouino::log( 'debug','RePING ' . $id_type . __(' Encore un essai...', __FILE__));
			$reponse = self::SendToBoardDemon($_board_id, 'PING=2', $DemonType); // On rePINGue
			if ($reponse == 'PINGOK')
			{
				config::save('NODEP_' . $_board_id, '', 'jeedouino');
				config::save($_board_id . '_' . $DemonTypeF . 'CountBadPING', 0, 'jeedouino');	// RAZ
				config::save($_board_id . '_' . $DemonTypeF . 'DaemonState', true, 'jeedouino');
				return true;
			}
			if (strpos($reponse, '111') !== false) return false; // Connection refused
			// Combien de PING non répondus ?
			$CountBadPING=config::byKey($_board_id.'_' . $DemonTypeF . 'CountBadPING', 'jeedouino', 0);
			$CountBadPING++;

			if (!config::byKey('StartDemons', 'jeedouino', 0)) jeedouino::log( 'debug','PING EqID:'.$_board_id.' (Essai No '.$CountBadPING.' ): Le démon ' . $DemonTypeF . ' ne réponds pas - Réponse :'.$reponse);   // Le démon ne réponds pas, on va vérifier si il traîne des processus
			if ($CountBadPING>3)	// Après 3 PING NON OK, on force l'arrêt du démon.
			{
				if (!config::byKey('StartDemons', 'jeedouino', 0)) jeedouino::log( 'error', $id_type . __(' 4 PINGs non répondus, je stoppe les processus du démon.', __FILE__));
				self::ForceStopBoardDemon($_board_id, 0, $DemonType);   // Si oui, on les kill pour éviter les problèmes.
				$CountBadPING=0;	//RAZ
				// Si demon usb, on tente de changer le port car il permet un redemarrage du demon plus rapide sur certains systemes
				if ($DemonTypeF == 'USB') jeedouino::ChangePortDemon($_board_id);
			}
			config::save($_board_id.'_' . $DemonTypeF . 'CountBadPING', $CountBadPING, 'jeedouino');	// Sauvegarde
			return false;
		}
		else
		{
			config::save('NODEP_' . $_board_id, '', 'jeedouino');
			config::save($_board_id . '_' . $DemonTypeF . 'CountBadPING', 0, 'jeedouino');	// RAZ
			config::save($_board_id . '_' . $DemonTypeF . 'DaemonState', true, 'jeedouino');
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
	public function ReStartBoardDemon($board_id, $useless=0, $DemonType)	// Redémarre le Démon
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null) return;

		config::save('StartDemons', 1, 'jeedouino');
		if (self::StatusBoardDemon($board_id, 1, $DemonType))
		{
			self::StopBoardDemon($board_id, 0, $DemonType);
		}
		usleep(1500000); // 1.5 secondes
		self::StartBoardDemon($board_id, 0, $DemonType);
		config::save('StartDemons', 0, 'jeedouino');
	}
	public function EraseBoardDemonFile($board_id, $useless=0, $DemonType)	// Cherche le Démon pour effacer son fichier jeedouinoUSB_*.py
	{
		$DemonTypeF = self::FilterDemon($DemonType);
		if ($DemonTypeF == null) return;

		$my_Board = eqLogic::byid($board_id);
		$IPBoard = $my_Board->getConfiguration('iparduino');

		$jeedomIP = self::GetJeedomIP();
		if ($jeedomIP == $IPBoard) self::EraseBoardDemonFileCMD($board_id, $DemonType);   // sur local ArduinoUsb (master ou esclave)
		else
		{
			self::CallSlaveExt('EraseBoardDemonFileCMD', array(	'plugin' => 'jeedouino',
			 													'eqLogic' => $board_id,
																'DemonType' => $DemonType));
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

	public function saveStack($params) {
	}

	/* fonction appelé avant le début de la séquence de sauvegarde */
	public function preSave()
	{
		if ($this->getLogicalId() == 'JeedouinoControl') return;
		jeedouino::log( "debug",' >>>preSave');

		if ($this->getIsEnable() == 0)
		{
			if ($this->getId() != '' and $this->getId() > 0)
			{
				$message = __('L\'équipement ', __FILE__) . $this->getName() . ' id '. $this->getId() . __(' est désactivé. Pas la peine de continuer.' , __FILE__);
				jeedouino::log( 'debug', $message);
				event::add('jeedom::alert', array(
					'level' => 'warning',
					'page' => 'jeedouino',
					'message' => $message
					));
			}
			return;
		}
		// Correction IP choisie par Input ou Select
		$ip = strtolower(trim($this->getConfiguration('iparduino')));
		if ($ip == '' or $this->getConfiguration('alone') == '1')
		{
			$ip = strtolower(trim($this->getConfiguration('iparduino2')));
			if ($ip != '' and filter_var($ip , FILTER_VALIDATE_IP)) $this->setConfiguration('iparduino', $ip);
		}

		// On va essayer de détecter un changement dans les paramêtres de la carte (réseau/port/etc)
		$arduino_id=$this->getId();
		$BoardEQ = eqLogic::byid($arduino_id);

		config::save($arduino_id . '-ForceStart', '0', 'jeedouino');
		config::save($arduino_id . '-ForceSuppr', '0', 'jeedouino');

		// liste des paramêtres a surveiller
		$config_list=array('ipPort', 'iparduino', 'arduino_board', 'PortI2C', 'datasource', 'PortID', 'arduinoport', 'portusbdeporte', 'PortDemon', 'portusblocal', 'alone');

		if ($BoardEQ !== false and $BoardEQ !== null)	// Si création équipement, il n'y a pas de eqLogic pour comparer
		{
			foreach ($config_list as $param)
			{
				if (trim($BoardEQ->getConfiguration($param)) != trim($this->getConfiguration($param)))
				{
					config::save($arduino_id . '-ForceStart', '1', 'jeedouino');
					jeedouino::log( 'debug','EqID ' . $arduino_id . ' Old -  $' . $param . ' = ' . trim($BoardEQ->getConfiguration($param)));
					jeedouino::log( 'debug','EqID ' . $arduino_id . ' New -  $' . $param . ' = ' . trim($this->getConfiguration($param)));
					jeedouino::log( 'debug','EqID ' . $arduino_id . __(' Au moins un paramêtre a changé, il faut forcer le redémarrage du démon si il y en a un.', __FILE__));
					// On sauvegarde l'ancien port pour pouvoir communiquer l'arret du démon par ex.
					if ($param == 'ipPort' or $param == 'PortDemon')
					{
						config::save($arduino_id . '_OLDPORT', trim($BoardEQ->getConfiguration($param)), 'jeedouino');
					}
					// Changement de modele, on supprime toutes les commandes
					if ($param == 'arduino_board')
					{
						// TODO : ajouter suppression des sketchs du modele precedent.
						config::save($arduino_id . '-ForceSuppr', '1', 'jeedouino');
						jeedouino::log( 'debug','EqID ' . $arduino_id . __(' Le modèle de carte n\'est plus le même, il faut supprimer toutes les commandes.', __FILE__));
					}
					break; // pas la peine de continuer
				}
			}

			$ModeleArduino = trim($this->getConfiguration('arduino_board'));
			if ($ModeleArduino != '')
			{
				$PortArduino = trim($this->getConfiguration('datasource'));

				// On vérifie si l'IP est correctement renseignée.
				$ipJeedom = self::GetJeedomIP();
				if ($PortArduino=='rj45arduino')
				{
					$ip = strtolower(trim($this->getConfiguration('iparduino')));
					if (substr($ip, 0, 5) == 'local') $ip = '127.0.0.1';

					if (filter_var( $ip , FILTER_VALIDATE_IP) === false)
					{
						throw new Exception(__('Le format de l\'adresse IP n\'est pas valide. Veuillez le vérifier : ', __FILE__) . $ip);
					}

					if ($ip == '127.0.0.2') // En attendant les tests
					{
						$this->setConfiguration('iparduino', $ipJeedom);
						self::log( "debug",'Adresse IP 127.0.0.1 de ' . ($this->getName()) . ' (' .$arduino_id. ') remplacée par son adresse locale : ' . $ipJeedom);
					}
					if ($ip == $ipJeedom or $ip == '127.0.0.1') config::save($arduino_id . '_IpLocale', 1, 'jeedouino');
					else config::save($arduino_id . '_IpLocale', 0, 'jeedouino');
				}
				elseif ($PortArduino == 'usbarduino')
				{
					$ip = strtolower(trim($this->getConfiguration('iparduino')));
					if ($ip == '') $this->setConfiguration('iparduino', $ipJeedom);

					$LocalArduino = trim($this->getConfiguration('arduinoport'));
					if ($LocalArduino == 'usblocal')
					{
						config::save($arduino_id . '_IpLocale', 1, 'jeedouino');
						$portusblocal = trim($this->getConfiguration('portusblocal'));
						if ($portusblocal == '') throw new Exception(__('Vous n\'avez pas choisi le port USB : Local .', __FILE__));
					}
					elseif ($LocalArduino == 'usbdeporte')
					{
						config::save($arduino_id . '_IpLocale', 0, 'jeedouino');
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
	public function preUpdate()
	{
		if ($this->getLogicalId() == 'JeedouinoControl') return;
		jeedouino::log( "debug",' >>>preUpdate');
	}

	/* fonction appelé pendant la séquence de sauvegarde après l'insertion
	 * dans la base de données pour une mise à jour d'une entrée */
	public function postUpdate()
	{
		if ($this->getLogicalId() == 'JeedouinoControl') return;
		jeedouino::log( "debug",' >>>postUpdate');
	}

	/* fonction appelé pendant la séquence de sauvegarde avant l'insertion
	 * dans la base de données pour une nouvelle entrée */
	public function preInsert()
	{
		if ($this->getLogicalId() == 'JeedouinoControl') return;
		jeedouino::log( "debug",' >>>preInsert');
		$this->setCategory('automatism', 1);
	}

	/* fonction appelé pendant la séquence de sauvegarde après l'insertion
	 * dans la base de données pour une nouvelle entrée */
	public function postInsert()
	{
		if ($this->getLogicalId() == 'JeedouinoControl') return;
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
			jeedouino::log( 'debug', __('Pas de ID original, donc surement création d\'un nouvel équipement.', __FILE__));
		}
		elseif ($Original_ID != $arduino_id)
		{
			jeedouino::log( 'debug',' arduino_id = ' . $arduino_id);
			jeedouino::log( 'debug',' Original_ID = ' . $Original_ID);
			jeedouino::log( 'debug', __('ID original différent de celui de l\'ID courant, donc résultat d\'une duplication.', __FILE__));
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

	public function SendPRM($eqLogic)
	{
		$arduino_id = $eqLogic->getId();
		list( , $board, $usb) = jeedouino::GetPinsByBoard($arduino_id);
		// cas spécial de l'arduino en usb déporté car faut lui trouver l'ip de son rpi hôte
		if (($eqLogic->getConfiguration('datasource') == 'usbarduino') and ($eqLogic->getConfiguration('arduinoport') == 'usbdeporte'))
		{
			$portusbdeporte = $eqLogic->getConfiguration('portusbdeporte');
			$p = strpos($portusbdeporte, '_');
			if ($p) // Pas de vérification stricte car je ne veux pas de position à 0 non plus
			{
				$IPArduino = substr($portusbdeporte, 0, $p);   // On recupère l'IP
				$eqLogic->setConfiguration('iparduino', $IPArduino);  // On la sauve pour le démon
				$eqLogic->save(true);
				jeedouino::log( 'debug', __('Démon déporté - IPArduino ArduinoUsb (eqID ', __FILE__) . $arduino_id . ') : ' . $IPArduino . ':' . $eqLogic->getConfiguration('PortDemon'));
			}
			else
			{
				throw new Exception(__('Impossible de trouver l\'IP du démon déporté: ', __FILE__) .$portusbdeporte);
			}
		}
		// fin cas
		$wwwPort = 80;	// port ecoute page php JeedouinoExt.php
		$JeedomIP = jeedouino::GetJeedomIP();
		$JeedomPort = jeedouino::GetJeedomPort();
		$JeedomCPL = jeedouino::GetJeedomComplement();
		$ipPort = $eqLogic->getConfiguration('ipPort');
		$prm = json_encode(array('IP' => $JeedomIP, 'Port' => $JeedomPort, 'Cpl' => $JeedomCPL));
		$reponse = jeedouino::CallJeedouinoExt($arduino_id, 'SetJeedomCFG', $prm, $wwwPort ); // EqLogic, Cmd, Array Params, default port www
		if ($reponse!='OK') jeedouino::log( 'error', __('Pb Envoi cmd SetJeedomCFG sur Jeedouino déporté eqID ( ', __FILE__) . $arduino_id . ' ) - Réponse :' . $reponse);

		$ToSend = false;
		$_ProbeDelay = config::byKey($arduino_id . '_ProbeDelay', 'jeedouino', '5');
		if ($JeedomCPL == '') $JeedomCPL = '.';
		if ($board == 'arduino' and $usb)
		{
			$DemonName = 'USB';
			if ($eqLogic->getConfiguration('arduinoport') == 'usblocal') $portUSB = $eqLogic->getConfiguration('portusblocal');
			else
			{
				$p=strpos($portusbdeporte, '_');
				if ($p) // Pas de verification stricte car je ne veut pas de position a 0 non plus
				{
					$portUSB = substr($portusbdeporte, $p + 1);
				}
				else
				{
					throw new Exception(__('Impossible de trouver le port USB du démon déporté: ', __FILE__) .$portusbdeporte);
				}
			}
			$ip = $eqLogic->getConfiguration('iparduino');
			$UsbMap = config::byKey('uMap-' . $ip, 'jeedouino', '');
			if (is_array($UsbMap))  $portUSB = $UsbMap[$portUSB];
			else $portUSB = '"' . $portUSB . '"';
			$baudrate = 115200;
			if (config::byKey($arduino_id . '_SomfyRTS', 'jeedouino', 0)) $baudrate /=  2;
			$setprm = $eqLogic->getConfiguration('PortDemon').' '.$portUSB.' '.$arduino_id.' '.$JeedomIP.' '.$JeedomPort.' '.$JeedomCPL.' '.$baudrate.' '.$_ProbeDelay;
			$ToSend = true;
		}
		if ($board == 'piface')
		{
			$DemonName = 'PiFace';
			$PiBoardID = $eqLogic->getConfiguration('PortID');		 // No de carte piface ( si plusieurs sur même RPI)
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
			$PiBoardID = $eqLogic->getConfiguration('PortI2C');
			$setprm = $ipPort.' '.$arduino_id.' '.$JeedomIP.' '.$PiBoardID.' '.$JeedomPort.' '.$JeedomCPL;
			$ToSend = true;
		}
		if ($ToSend)
		{
			$prm = json_encode(array('board_id' => $arduino_id, 'DemonName' => $DemonName, 'prm' => $setprm));
			$reponse = jeedouino::CallJeedouinoExt($arduino_id, 'SetPRM', $prm, $wwwPort ); // EqLogic, Cmd, Array Params, default port www
			if ($reponse != 'OK') jeedouino::log( 'error', __('Problème d\'envoi de SetPRM sur JeedouinoExt, démon : ', __FILE__) . ' Jeedouino' . $DemonName . '.py ( eqID ' . $arduino_id . ' ) - Réponse :' . $reponse);
		}
	}
	/* fonction appelé après la fin de la séquence de sauvegarde */
	public function postSave()
	{
		if ($this->getLogicalId() == 'JeedouinoControl') return;
		jeedouino::log( "debug" , 'Debut de postSave');

		$arduino_id = $this->getId();

		// petit correctif concernant le delai de renvoi des temp des sondes
		$_ProbeDelay = config::byKey($arduino_id . '_ProbeDelay', 'jeedouino', '5');
		if ($_ProbeDelay < 1 or $_ProbeDelay>1000) $_ProbeDelay = 5;
		config::save($arduino_id . '_ProbeDelay', $_ProbeDelay, 'jeedouino');

		if ($this->getIsEnable() == 0)
		{
			$IPBoard = trim($this->getConfiguration('iparduino'));
			$AlreadyKilled = config::byKey($arduino_id . '_AlreadyKilled', 'jeedouino', false);

			if ($IPBoard != '' and filter_var( $IPBoard , FILTER_VALIDATE_IP) !== false and $AlreadyKilled == false)
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
				config::save($arduino_id . '_AlreadyKilled', true, 'jeedouino');
			}
			// Equipement désactivé, pas la peine de regénérer les commandes et d'envoyer la config des pins aux cartes/démons
			return;
		}
		config::save($arduino_id . '_AlreadyKilled', false, 'jeedouino');

		$ModeleArduino = $this->getConfiguration('arduino_board');
		$PortArduino = $this->getConfiguration('datasource');
		$LocalArduino = $this->getConfiguration('arduinoport');

		if ($ModeleArduino == '') throw new Exception(__('Vous n\'avez pas défini de modèle de carte !.', __FILE__));

		if (($ModeleArduino != '') and (($PortArduino == 'rj45arduino') or ($PortArduino == 'usbarduino')))
		{
			// ok une carte est definie
			list($Arduino_pins, $board, $usb) = self::GetPinsByBoard($arduino_id);

			$CfgStep = config::byKey($arduino_id . '_EqCfgSaveStep', 'jeedouino', 0);
			if ($CfgStep == 1)
			{
				// On génère les sketchs
				if (($board == 'arduino') and (!$usb)) self::GenerateLanArduinoSketchFile($arduino_id);
				if (($board == 'arduino') and ($usb)) self::GenerateUSBArduinoSketchFile($arduino_id);
				if (($board == 'esp') and (!$usb)) self::GenerateESP8266SketchFile($arduino_id);
				config::save($arduino_id . '_EqCfgSaveStep', 2, 'jeedouino');
				return;
			}
			elseif ($CfgStep == 2)
			{
				config::save($arduino_id . '-ForceStart', '1', 'jeedouino');
				config::save($arduino_id . '_EqCfgSaveStep', 3, 'jeedouino');
			}

			// Jeedouino seul sur rpi ??
			if ($this->getConfiguration('alone') == '1')
			{
				jeedouino::SendPRM($this);
			}
			//

			// changement de modèle de carte
			if (config::byKey($arduino_id . '-ForceSuppr', 'jeedouino', '0'))
			{
				jeedouino::log( 'debug', 'EqID ' . $arduino_id . __(' Effacement de toutes les commandes suite au changement de modèle de la carte.', __FILE__));

				$cmds = $this->getCmd();
				if (is_object($cmds)) $cmds = [$cmds]; // 1 seule commande ???

				foreach ($cmds as $cmd)
				{
					// nettoyage des pins paramêtrées
					$pins_id=$cmd->getConfiguration('pins_id');
					config::save($arduino_id . '_' . $pins_id, 'not_used', 'jeedouino');
					config::save('GT_' . $arduino_id . '_' . $pins_id, '', 'jeedouino');
					config::save('GV_' . $arduino_id . '_' . $pins_id, '', 'jeedouino');

					// Netttoyage des virtuels
					if (config::byKey('ActiveVirtual', 'jeedouino', false)) jeedouino::DelCmdOfVirtual($cmd, $cmd->getLogicalId());
					// nettoyage des commandes afférentes
					jeedouino::log( 'debug', __('Suppression de : ', __FILE__) . json_encode($cmd->getLogicalId()));
					$cmd->remove();
				}
				config::save($arduino_id . '-ForceSuppr', '0', 'jeedouino');
			}

			$DHTxx = 0;			// Pour génération sketch
			$DS18x20 = 0;		// Pour génération sketch
			$teleinfoRX = 0;	// Pour génération sketch
			$teleinfoTX = 0;	// Pour génération sketch
			$Send2LCD = 0;		// Pour génération sketch
			$UserSketch = 0;	// Pour génération sketch
			$SomfyRTS = 0;		// Pour génération sketch
			$bmp180 = 0;		// Pour génération sketch
			$bmp280 = 0;		// Pour génération sketch
			$bme280 = 0;		// Pour génération sketch
			$bme680 = 0;		// Pour génération sketch
			$servo = 0;			// Pour génération sketch
			$WS2811 = false;	// Pour génération sketch (a cause de la pin 0 sur esp...)

			jeedouino::log( 'debug', 'EqID ' . $arduino_id . __(' Création de la liste des commandes.', __FILE__));
			//sleep(2);	//
			$cmd_list = array();
			$old_list = array();
			$list_order_nb=1;
			foreach ($Arduino_pins as $pins_id => $pin_datas)
			{
				$double_cmd = '';
				$triple_cmd = '';
				$quadruple_cmd = '';
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
						case 'bmp280':
							$bmp280 |= 1;			// i2c x76
							$myType = 'info';
							$mySubType = 'numeric';
							$myinvertBinary = '0';
							$tempo = '0';
							$value = '0';
							$double_cmd = $myPin . '_p';
							$value2 = '0';
						break;
						case 'bmp280b':
							$bmp280 |= 2;			// i2c x77
							$myType = 'info';
							$mySubType = 'numeric';
							$myinvertBinary = '0';
							$tempo = '0';
							$value = '0';
							$double_cmd = $myPin . '_p';
							$value2 = '0';
						break;
						case 'bme280':
							$bme280 |= 1;			// i2c x76
							$myType = 'info';
							$mySubType = 'numeric';
							$myinvertBinary = '0';
							$tempo = '0';
							$value = '0';
							$double_cmd = $myPin . '_p';
							$value2 = '0';
							$triple_cmd = $myPin . '_h';
							$value3 = '0';
						break;
						case 'bme280b':
							$bme280 |= 2;			// i2c x77
							$myType = 'info';
							$mySubType = 'numeric';
							$myinvertBinary = '0';
							$tempo = '0';
							$value = '0';
							$double_cmd = $myPin . '_p';
							$value2 = '0';
							$triple_cmd = $myPin . '_h';
							$value3 = '0';
						break;
						case 'bme680':
							$bme680 |= 1;			// i2c x76
							$myType = 'info';
							$mySubType = 'numeric';
							$myinvertBinary = '0';
							$tempo = '0';
							$value = '0';
							$double_cmd = $myPin . '_p';
							$value2 = '0';
							$triple_cmd = $myPin . '_h';
							$value3 = '0';
							$quadruple_cmd = $myPin . '_g';
							$value4 = '0';

						break;
						case 'bme680b':
							$bme680 |= 2;			// i2c x77
							$myType = 'info';
							$mySubType = 'numeric';
							$myinvertBinary = '0';
							$tempo = '0';
							$value = '0';
							$double_cmd = $myPin . '_p';
							$value2 = '0';
							$triple_cmd = $myPin . '_h';
							$value3 = '0';
							$quadruple_cmd = $myPin . '_g';
							$value4 = '0';
						break;
						case 'ds18b20':
							$myType = 'info';
							$mySubType = 'numeric';
							$myinvertBinary = '0';
							$tempo = '0';
							$value = '0';
							$DS18x20 = 1;	// Pour génération sketch
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
							$tempo='00007';
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
						case 'double_pulse_low':
							$myType = 'action';
							$mySubType = 'other';
							$myinvertBinary = '0';
							$tempo = '002002';
							$value = '0';
							$double_cmd = 'low_relais';
							$value2 = '0';
						break;
						case 'double_pulse_high':
							$myType = 'action';
							$mySubType = 'other';
							$myinvertBinary = '0';
							$tempo = '002002';
							$value = '1';
							$double_cmd = 'high_relais';
							$value2 = '1';
						break;
						default:
							continue 2;
						break;
					}

					$LogicalId = 'ID' . $pins_id; // $pin_datas['Nom_pin']
					$ID_pins = $pins_id;
					//Correctif noms des pins pour NodeMCU
					if ($ModeleArduino == 'espMCU01' and $pins_id < 500) $ID_pins = $pin_datas['Nom_pin'];
					if ($pins_id >= 500) $UserSketch = 1;// Pour génération sketch

					$myPinN = $myPin;
					if ($myPin == 'low_relais') $myPinN = 'low_pin';
					if ($myPin == 'high_relais') $myPinN = 'high_pin';
					$double_cmdN = $double_cmd;
					if ($double_cmd == 'low_relais') $double_cmdN = 'low_pin';
					if ($double_cmd == 'high_relais') $double_cmdN = 'high_pin';

					//Correctif noms des pins HLW8012 pour SONOFF POW
					if ($ModeleArduino == 'espsonoffpow' and $pins_id > 0 and $pins_id < 6) $myPinN = $pin_datas['Nom_pin'];

					$cmd_list[$LogicalId . 'a'] = array('name' 			=> $ID_pins . '_' . $myPinN ,
														'type' 			=> $myType,
														'subtype' 		=> $mySubType,
														'tempo' 		=> $tempo,
														'value' 		=> $value,
														'modePIN' 		=> $myPin,
														'double_cmd' 	=> $double_cmd,
														'double_key' 	=> $LogicalId . 'b',
														'pins_id' 		=> $pins_id,
														'invertBinary'	=> $myinvertBinary,
														'generic_type'	=> $generic_type,
														'virtual'		=> $virtual,
														'order'			=> $list_order_nb
														);
					$old_list[$pin_datas['Nom_pin']] = $LogicalId . 'a';
					if ($double_cmd != '')
					{
						if ($myPin == 'double_pulse_low' or $myPin == 'double_pulse_high') $tempo = '999999';
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
						$cmd_list[$LogicalId . 'b'] = array('name' 			=> $ID_pins . '_' . $double_cmdN,
															'type' 			=> $myType,
															'subtype' 		=> $mySubType,
															'tempo' 		=> $tempo,
															'value' 		=> $value2,
															'modePIN' 		=> $double_cmd,
															'double_cmd'	=> '',
															'double_key' 	=> $LogicalId . 'a',
															'pins_id' 		=> $pins_id + 1000,
															'invertBinary'	=> $myinvertBinary,
															'generic_type'	=> $generic_type,
															'virtual'		=> $virtual,
															'order'			=> $list_order_nb
															);
						$old_list[$pin_datas['Nom_pin'].'2'] = $LogicalId.'b';
						$double_cmd='';
					}
					if ($triple_cmd != '')
					{
						$list_order_nb++;
						$cmd_list[$LogicalId . 'c'] = array('name' 			=> $ID_pins . '_' . $triple_cmd,
															'type' 			=> $myType,
															'subtype' 		=> $mySubType,
															'tempo' 		=> $tempo,
															'value' 		=> $value3,
															'modePIN' 		=> $triple_cmd,
															'double_cmd'	=> $quadruple_cmd,
															'double_key' 	=> $LogicalId . 'd',
															'pins_id' 		=> $pins_id + 2000,
															'invertBinary'	=> $myinvertBinary,
															'generic_type'	=> $generic_type,
															'virtual'		=> $virtual,
															'order'			=> $list_order_nb
															);
						$triple_cmd = '';
					}
					if ($quadruple_cmd != '')
					{
						$list_order_nb++;
						$cmd_list[$LogicalId . 'd'] = array('name' 			=> $ID_pins . '_' . $quadruple_cmd,
															'type' 			=> $myType,
															'subtype' 		=> $mySubType,
															'tempo' 		=> $tempo,
															'value' 		=> $value4,
															'modePIN' 		=> $quadruple_cmd,
															'double_cmd'	=> $triple_cmd,
															'double_key' 	=> $LogicalId . 'c',
															'pins_id' 		=> $pins_id + 3000,
															'invertBinary'	=> $myinvertBinary,
															'generic_type'	=> $generic_type,
															'virtual'		=> $virtual,
															'order'			=> $list_order_nb
															);
						$quadruple_cmd = '';
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
						$list_order_nb++;
						if ($mySubType == 'other') $mySubType = 'binary';
						if ($mySubType == 'slider') $mySubType = 'numeric';
						$cmd_list[$LogicalId . 'i'] = array(	'name' 			=> __('Etat_Pin_', __FILE__) . $ID_pins ,
																'type' 			=> 'info',
																'subtype' 		=> $mySubType,
																'tempo' 		=> '0',
																'value' 		=> '0',
																'modePIN' 		=> $myPin,
																'double_cmd' 	=> '',
																'double_key' 	=> '',
																'pins_id' 		=> $pins_id,
																'invertBinary'	=> $myinvertBinary,
																'generic_type'	=> $generic_type,
																'virtual'		=> $virtual,
																'order'			=> $list_order_nb
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
			config::save($arduino_id.'_DHTxx', $DHTxx, 'jeedouino');
			config::save($arduino_id.'_DS18x20', $DS18x20, 'jeedouino');
			config::save($arduino_id.'_TeleInfoRX', $teleinfoRX, 'jeedouino');
			config::save($arduino_id.'_TeleInfoTX', $teleinfoTX, 'jeedouino');
			config::save($arduino_id.'_Send2LCD', $Send2LCD, 'jeedouino');
			config::save($arduino_id.'_UserSketch', $UserSketch, 'jeedouino');
			config::save($arduino_id.'_SomfyRTS', $SomfyRTS, 'jeedouino');
			config::save($arduino_id.'_BMP180', $bmp180, 'jeedouino');
			config::save($arduino_id.'_BMP280', $bmp280, 'jeedouino');
			config::save($arduino_id.'_BME280', $bme280, 'jeedouino');
			config::save($arduino_id.'_BME680', $bme680, 'jeedouino');
			config::save($arduino_id.'_SERVO', $servo, 'jeedouino');
			config::save($arduino_id.'_WS2811', $WS2811, 'jeedouino');

			if ($this->getConfiguration('ActiveCmdAll'))
			{
				$cmd_list['ALLON'] = array(		'name' 			=> __('ALL_LOW', __FILE__) ,
												'type' 			=> 'action',
												'subtype' 		=> 'other',
												'tempo' 		=> '0',
												'value' 		=> '0',
												'modePIN' 		=> 'none',
												'double_cmd' 	=> '',
												'double_key' 	=> '',
												'pins_id' 		=> '990',
												'invertBinary'	=> '0',
												'generic_type'	=> 'GENERIC_ACTION',
												'virtual'		=> '',
												'order'			=> $list_order_nb
												);
				$old_list['ALL_ON'] = 'ALLON';
				$list_order_nb++;
				$cmd_list['ALLOFF'] = array(	'name' 			=> __('ALL_HIGH', __FILE__) ,
												'type' 			=> 'action',
												'subtype' 		=> 'other',
												'tempo' 		=> '0',
												'value' 		=> '1',
												'modePIN' 		=> 'none',
												'double_cmd' 	=> '',
												'double_key' 	=> '',
												'pins_id' 		=> '991',
												'invertBinary'	=> '0',
												'generic_type'	=> 'GENERIC_ACTION',
												'virtual'		=> '',
												'order'			=> $list_order_nb
												);
				$old_list['ALL_OFF'] = 'ALLOFF';
				$list_order_nb++;
				$cmd_list['ALLSWT'] = array(	'name' 			=> __('ALL_SWITCH', __FILE__) ,
												'type' 			=> 'action',
												'subtype' 		=> 'other',
												'tempo' 		=> '0',
												'value' 		=> '1',
												'modePIN' 		=> 'none',
												'double_cmd' 	=> '',
												'double_key' 	=> '',
												'pins_id' 		=> '992',
												'invertBinary'	=> '0',
												'generic_type'	=> 'GENERIC_ACTION',
												'virtual'		=> '',
												'order'			=> $list_order_nb
												);
				$list_order_nb++;
				$cmd_list['ALLPLSELOW'] = array(	'name' 			=> __('ALL_PULSE_LOW', __FILE__) ,
													'type' 			=> 'action',
													'subtype' 		=> 'other',
													'tempo' 		=> '00007',
													'value' 		=> '0',
													'modePIN' 		=> 'low_pulse',
													'double_cmd' 	=> '',
													'double_key' 	=> '',
													'pins_id' 		=> '993',
													'invertBinary'	=> '0',
													'generic_type'	=> 'GENERIC_ACTION',
													'virtual'		=> '',
													'order'			=> $list_order_nb
													);
				$list_order_nb++;
				$cmd_list['ALLPLSEHIGH'] = array(	'name' 			=> __('ALL_PULSE_HIGH', __FILE__) ,
													'type' 			=> 'action',
													'subtype' 		=> 'other',
													'tempo' 		=> '00007',
													'value' 		=> '1',
													'modePIN' 		=> 'high_pulse',
													'double_cmd' 	=> '',
													'double_key' 	=> '',
													'pins_id' 		=> '994',
													'invertBinary'	=> '0',
													'generic_type'	=> 'GENERIC_ACTION',
													'virtual'		=> '',
													'order'			=> $list_order_nb
													);
			}
			$modif_cmd = false; //  // ne renvoi pas la config a la carte
			jeedouino::log( 'debug', 'EqID ' . $arduino_id . __(' Effacement des commandes obsolètes.', __FILE__));
			foreach ($this->getCmd() as $cmd)
			{
				$Lid = $cmd->getLogicalId();
				if (!isset($cmd_list[$Lid]))
				{
					if (isset($old_list[$Lid]))	// Ancien LogicalId, màj nécéssaire.
					{
						$cmd->setLogicalId($old_list[$Lid]);
						$cmd->save();
						jeedouino::log( 'debug', __('Màj du LogicalId de : ', __FILE__) . $Lid . ' vers ' . $old_list[$Lid]);
					}
					else
					{
						if (config::byKey('ActiveVirtual', 'jeedouino', false)) jeedouino::DelCmdOfVirtual($cmd, $Lid);
						jeedouino::log( 'debug', __('Suppression de : ', __FILE__) . json_encode($cmd->getLogicalId()));
						$cmd->remove();
						$modif_cmd = true; // Renvoi la config a la carte
					}
				}
			}

			jeedouino::log( 'debug', 'EqID ' . $arduino_id . __(' Création des nouvelles commandes, MàJ des autres...', __FILE__));

			foreach ($cmd_list as $key => $cmd_info)
			{
				$cmd = $this->getCmd(null, $key);
				$create_cmd = false;
				if (!is_object($cmd))
				{
					$create_cmd = true;
				}
				else
				{
					// on verifie au cas ou le mode d'une pin a changé
					if ($cmd_info['modePIN'] == 'trigger') $modif_cmd = true; // envoi nécéssaire.

					if ($cmd->getConfiguration('modePIN') != $cmd_info['modePIN'])
					{
						$create_cmd = true;
						jeedouino::log(  'debug',"Mode Pin " . $cmd->getConfiguration('modePIN') . __(' changé pour  ', __FILE__) . $cmd_info['modePIN']);
					}
					elseif ($cmd->getSubType() != $cmd_info['subtype'])
					{
						$create_cmd = true;
						jeedouino::log(  'debug',"SubType " . $cmd->getSubType() . __(' changé pour  ', __FILE__) . $cmd_info['subtype']);
					}
					elseif ($cmd->getType() != $cmd_info['type'])
					{
						$create_cmd = true;
						jeedouino::log(  'debug',"Type " . $cmd->getType() . __(' changé pour  ', __FILE__) . $cmd_info['type']);
					}
					if ($create_cmd)
					{
						foreach ($this->getCmd() as $cmd_tmp)
						{
							$Lid = $cmd_tmp->getLogicalId();
							if ($cmd_info['double_key'] == $Lid or $key == $Lid)
							{
								if (config::byKey('ActiveVirtual', 'jeedouino', false)) jeedouino::DelCmdOfVirtual($cmd_tmp, $Lid);
								$cmd_tmp->remove();
							}
						}
						unset($cmd);
					}
					else
					{
						switch ($cmd->getConfiguration('modePIN'))
						{
							case 'double_pulse_low':
							case 'double_pulse_high':
								$cmd->setConfiguration('tempo', substr($cmd->getConfiguration('tempo'), -6));
								break;
							case 'WS2811':
							case 'high_pulse_slide':
							case 'high_pulse':
							case 'low_pulse_slide':
							case 'low_pulse':
							case 'output_pulse':
								$cmd->setConfiguration('tempo', substr($cmd->getConfiguration('tempo'), -5));
								break;
						}
					}
				}
				if ($create_cmd)
				{
					jeedouino::log( 'debug', __('Création de : ', __FILE__) . $cmd_info['name']);
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
				if ($order > 999) $order -= 1000;
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
					case 'bmp280':
					case 'bme280':
					case 'bme680':
					case 'bmp280b':
					case 'bme280b':
					case 'bme680b':
						$cmd->setTemplate('dashboard', 'thermometre');
						$cmd->setTemplate('mobile', 'default');
						$cmd->setUnite('°C');
						$generic_type = 'TEMPERATURE';
						break;
					case 'dht11_h':
					case 'dht21_h':
					case 'dht22_h':
					case 'bme280_h':
					case 'bme680_h':
					case 'bme280b_h':
					case 'bme680b_h':
						$cmd->setTemplate('dashboard', 'humidite');
						$cmd->setTemplate('mobile', 'default');
						$cmd->setUnite('%');
						$generic_type = 'HUMIDITY';
						break;
					case 'bmp180_p':
					case 'bmp280_p':
					case 'bme280_p':
					case 'bme680_p':
					case 'bmp280b_p':
					case 'bme280b_p':
					case 'bme680b_p':
						$generic_type = 'PRESSURE';
						$cmd->setUnite('Pa');
						break;
					case 'bme680_g': // gas cov
					case 'bme680b_g': // gas cov
						$generic_type = 'AIR_QUALITY';
						$cmd->setUnite('Ohms');
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
					case 'double_pulse_low':
					case 'double_pulse_high':
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
				switch ($cmd_info['generic_type'])
				{
					case '0': // Auto
					case '':
						if ($generic_type == '0') $generic_type = '';
						$cmd->setDisplay('generic_type', $generic_type);
						break;
					case 'configGT':
						break;
					case 'DONT': // Ne rien faire.
						//break;
					default: // Choix user
						$cmd->setDisplay('generic_type', $cmd_info['generic_type']);
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
			$_ForceStart = config::byKey($arduino_id . '-ForceStart', 'jeedouino', '0');

			if ($this->getConfiguration('alone') == '1')  $_ForceStart = true;
			if (($board == 'arduino') and (!$usb)) $_ForceStart = true;
			if (($board == 'esp') and (!$usb)) $_ForceStart = true;

			if ($modif_cmd or $_ForceStart == '1') self::ConfigurePinMode($_ForceStart);
			// On génère les sketchs
			if (($board == 'arduino') and (!$usb)) self::GenerateLanArduinoSketchFile($arduino_id);
			if (($board == 'arduino') and ($usb)) self::GenerateUSBArduinoSketchFile($arduino_id);
			if (($board == 'esp') and (!$usb)) self::GenerateESP8266SketchFile($arduino_id);
			jeedouino::CreateJeedouinoControl();
			config::save($arduino_id . '-ForceStart', '0', 'jeedouino');
		}
		else throw new Exception(__('Vous n\'avez pas défini la connection de la carte (Réseau / Usb: Local/Déporté)) !.', __FILE__));

		jeedouino::log( 'debug', 'Fin de postSave()');
	}
	public function postAjax()
	{
		if ($this->getLogicalId() == 'JeedouinoControl') return;
		jeedouino::log( 'debug','Debut de postAjax()');

		if ($this->getConfiguration('AutoOrder') == '1')
		{
			foreach ($this->getCmd() as $cmd)
			{
				$order = $cmd->getConfiguration('pins_id');
				if ($order > 999) $order -= 1000;
				if ($order > 999) $order -= 1000;
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
				jeedouino::log( 'debug', __('Commande "', __FILE__) . $Vcmd->getName() . __('" du virtuel "', __FILE__) . virtual::byId($Vcmd->getEqLogic_id())->getName() . __('" supprimée. ', __FILE__));
				$Vcmd->remove();
			}
		}
		else jeedouino::log( 'error', __('Impossible de trouver le plugin Virtuel !', __FILE__));
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
			if (!is_object($eqLogic)) jeedouino::log( 'error', __('Impossible de trouver le virtuel demandé eqID : ', __FILE__) . $eq_id);
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
					jeedouino::log( 'debug', __('Commande "', __FILE__) . $cmd->getName() . __('" ajoutée/modifiée dans le virtuel "', __FILE__) . $eqLogic->getName() . '". ');
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
		else jeedouino::log( 'error', __('Impossible de trouver le plugin Virtuel !', __FILE__));
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
		if (config::byKey('ActiveJLAN', 'jeedouino', false))
		{
			$ip = strtolower(trim(config::byKey('IPJLAN', 'jeedouino', '')));

			if ($ip!='' and $ip!='127.0.0.1' and substr($ip,0,5) != 'local' and (filter_var( $ip , FILTER_VALIDATE_IP) !== false)) return $ip;
			// pas d'IP hôte alors erreur.
			jeedouino::log( 'error', __('L\'IP réelle de l hôte/NAS Jeedom Maître doit être renseignée dans la configuration du plugin. Merci. ', __FILE__));
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
		if (config::byKey('ActiveJLAN', 'jeedouino', false))
		{
			$port = trim(config::byKey('PORTJLAN', 'jeedouino', ''));
			if ($port != '') return $port;
			// pas de port hôte alors erreur.
			jeedouino::log( 'error', 'Le port de l hôte/NAS Jeedom Maître doit être renseignée dans la configuration du plugin. Merci. ');
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
				if (config::byKey($board_id.'_BMP180', 'jeedouino', 0))
				{
				 	$MasterFile = str_replace('#define UseBMP180 0' , '#define UseBMP180 1' , $MasterFile);
				}

				$MasterFile = str_replace('#define UseBMP280 0' , '#define UseBMP280 ' . config::byKey($board_id.'_BMP280', 'jeedouino', 0) , $MasterFile);
				$MasterFile = str_replace('#define UseBME280 0' , '#define UseBME280 ' . config::byKey($board_id.'_BME280', 'jeedouino', 0) , $MasterFile);
				$MasterFile = str_replace('#define UseBME680 0' , '#define UseBME680 ' . config::byKey($board_id.'_BME680', 'jeedouino', 0) , $MasterFile);

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
				$MasterFile = str_replace('JeedouinoESPTAG' , 'JeedouinoESP_' . $board_id , $MasterFile);

				$DHTxx = config::byKey($board_id.'_DHTxx', 'jeedouino', 0);
				$DS18x20 = config::byKey($board_id.'_DS18x20', 'jeedouino', 0);
				$TeleInfoRX = config::byKey($board_id.'_TeleInfoRX', 'jeedouino', 0);
				$TeleInfoTX = config::byKey($board_id.'_TeleInfoTX', 'jeedouino', 0);
				$Send2LCD = config::byKey($board_id.'_Send2LCD', 'jeedouino', 0);
				$UserSketch = config::byKey($board_id.'_UserSketch', 'jeedouino', 0);
				$_ProbeDelay = config::byKey($board_id . '_ProbeDelay', 'jeedouino', '1');
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
				if (config::byKey($board_id.'_BMP180', 'jeedouino', 0))
				{
				 	$MasterFile = str_replace('#define UseBMP180 0' , '#define UseBMP180 1' , $MasterFile);
				}
				$MasterFile = str_replace('#define UseBMP280 0' , '#define UseBMP280 ' . config::byKey($board_id.'_BMP280', 'jeedouino', 0) , $MasterFile);
				$MasterFile = str_replace('#define UseBME280 0' , '#define UseBME280 ' . config::byKey($board_id.'_BME280', 'jeedouino', 0) , $MasterFile);
				$MasterFile = str_replace('#define UseBME680 0' , '#define UseBME680 ' . config::byKey($board_id.'_BME680', 'jeedouino', 0) , $MasterFile);

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

				if (config::byKey($board_id.'_BMP180', 'jeedouino', 0))
				{
				 	$MasterFile = str_replace('#define UseBMP180 0' , '#define UseBMP180 1' , $MasterFile);
				}
				$MasterFile = str_replace('#define UseBMP280 0' , '#define UseBMP280 ' . config::byKey($board_id.'_BMP280', 'jeedouino', 0) , $MasterFile);
				$MasterFile = str_replace('#define UseBME280 0' , '#define UseBME280 ' . config::byKey($board_id.'_BME280', 'jeedouino', 0) , $MasterFile);
				$MasterFile = str_replace('#define UseBME680 0' , '#define UseBME680 ' . config::byKey($board_id.'_BME680', 'jeedouino', 0) , $MasterFile);

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
		if ($this->getLogicalId() == 'JeedouinoControl') return;
		$arduino_id = $this->getId();

		//  On éfface le fichier python généré pour le Démon

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
					jeedouino::log( 'debug', __('Le Sketch ESP / NodeMCU / Wemos est supprimé !', __FILE__) . ' eqID : ' . $arduino_id . ' - ' . $SketchFileName);
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
		$cmds = $this->getCmd();
		if (is_array($cmds))
		{
			if ($cmds != []) foreach ($cmds as $cmd)  jeedouino::DelCmdOfVirtual($cmd, $cmd->getLogicalId());
		}
		elseif (is_object($cmds)) jeedouino::DelCmdOfVirtual($cmds, $cmds->getLogicalId());

		// On efface les variables dans la table config (pins et autres)
		if (is_array($Arduino_pins))
		{
			foreach ($Arduino_pins as $pins_id => $pin_datas)
			{
				config::remove($arduino_id . '_' . $pins_id, 'jeedouino');
				config::remove('GT_' . $arduino_id . '_' . $pins_id, 'jeedouino');
				config::remove('GV_' . $arduino_id . '_' . $pins_id, 'jeedouino');
			}
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
		config::remove($arduino_id . '_BMP280', 'jeedouino');
		config::remove($arduino_id . '_BME280', 'jeedouino');
		config::remove($arduino_id . '_BME680', 'jeedouino');
		config::remove($arduino_id . '_SERVO', 'jeedouino');
		config::remove($arduino_id . '_WS2811', 'jeedouino');
		config::remove('REP_' . $arduino_id, 'jeedouino');

		config::remove($arduino_id . 'remove', 'jeedouino');
	}

	/* fonction appelé après l'effacement d'une entrée */
	public function postRemove()
	{
		// on efface/màj les commandes mises dans JeedouinoControl
		jeedouino::CreateJeedouinoControl();
		//config::remove( $this->getId() . 'remove', 'jeedouino');		// arduino_id
	}

	/*	 * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
	  public function toHtml($_version = 'dashboard') {
	  }	 */
}

class jeedouinoCmd extends cmd {
	public function execute($_options = null)
	{
		// jeedouino cmd control
		//jeedouino::log( 'debug', 'CMD execute $this->getLogicalId() = ' . $this->getLogicalId() . ' - $_options = ' . json_encode($_options));
		if ($this->getLogicalId() == 'refresh')
		{
			jeedouino::updateDemons();
			return;
		}
		elseif (strpos($this->getLogicalId(), 'StartDaemon') !== false)
		{
			//jeedouino::log( 'debug', 'CMD StartDaemon $this->getConfiguration(demontype) = ' . $this->getConfiguration('demontype'));
			jeedouino::StartBoardDemon($this->getConfiguration('boardid'), 0, $this->getConfiguration('demontype'));
			//sleep(3);
			jeedouino::updateDemons();
			return;
		}
		elseif (strpos($this->getLogicalId(), 'StopDaemon') !== false)
		{
			//jeedouino::log( 'debug', 'CMD StartDaemon $this->getConfiguration(demontype) = ' . $this->getConfiguration('demontype'));
			jeedouino::StopBoardDemon($this->getConfiguration('boardid'), 0, $this->getConfiguration('demontype'));
			//sleep(3);
			jeedouino::updateDemons();
			return;
		}
		// jeedouino cmds classiques
		$pins_id = $this->getConfiguration('pins_id');
		if ($this->getType() == 'action')
		{
			try
			{
				if ($this->getSubType() == 'other')
				{
					$modePIN = $this->getConfiguration('modePIN');
					if ($modePIN == 'double_pulse_low' or $modePIN == 'double_pulse_high')
					{
						$tempo = $this->getConfiguration('tempo');
						if ($tempo == '0') $tempo = '';
						else  $tempo = substr(sprintf("%06s", $tempo), -6); // First click XXX, pause YYY, Second click XXX
						$this->setConfiguration('tempo', $tempo);
						$this->save();
						if (jeedouino::ConfigurePinValue($pins_id, $this->getConfiguration('value') . $tempo, $this->getEqLogic_id())) return true;
					}
					else
					{
						$tempo = $this->getConfiguration('tempo');
						if ($tempo == '0') $tempo = '';
						elseif ($tempo != '999999')  $tempo = substr(sprintf("%05s", $tempo), -5);
						$this->setConfiguration('tempo', $tempo);
						$this->save();
						if (jeedouino::ConfigurePinValue($pins_id, $this->getConfiguration('value') . $tempo, $this->getEqLogic_id())) return true;
					}
					return false;
				}
				elseif ($this->getSubType() == 'slider')
				{
					$modePIN = $this->getConfiguration('modePIN');
					if ($modePIN == 'low_pulse_slide' or $modePIN == 'high_pulse_slide')
					{
						//jeedouino::log( 'debug','Liste $_options = '. json_encode($_options));
						$tempo = round($_options['slider']);
						$tempo = substr(sprintf("%05s", $tempo), -5);
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

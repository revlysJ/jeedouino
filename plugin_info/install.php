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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function jeedouino_install() {

}

function jeedouino_update()
{
    // update JeedouinoExt
    $ListExtIP = jeedouino::CleanIPJeedouinoExt();
    foreach ($ListExtIP as $ip)
    {
        $id = trim(config::byKey('ID-' . $ip, 'jeedouino', ''));
        $JExtname = trim(config::byKey('JExtname-' . $ip, 'jeedouino', ''));
		if ($id == '' and $JExtname == '')
        {
            $id = jeedouino::AddIDJeedouinoExt($ip);
            config::save('JExtname-' . $ip, 'JeedouinoExt', 'jeedouino');
        }
    }
    //
	// correction droits fichier DS18B20Scan
	exec('sudo chmod 755 ' . dirname(__FILE__) . '/../ressources/DS18B20Scan >> '.log::getPathToLog('jeedouino_update') . ' 2>&1 &');
	//
	$eqLogics = eqLogic::byType('jeedouino');
	$IPJeedom = jeedouino::GetJeedomIP();
	jeedouino::log( 'debug','-=-= Suite mise à jour du plugin, démarrage global des démons et re-génération des sketchs =-=-');
	foreach ($eqLogics as $eqLogic)
	{
		$arduino_id = $eqLogic->getId();

		// On verifie si l'equipement est local
		if ($IPJeedom == $eqLogic->getConfiguration('iparduino')) config::save($arduino_id . '_IpLocale', 1, 'jeedouino');
		else config::save($arduino_id . '_IpLocale', 0, 'jeedouino');

		// On verifie si l'equipement a un tag "Original_ID"
		$Original_ID = $eqLogic->getConfiguration('Original_ID');
		if ($Original_ID == '')
		{
			$eqLogic->setConfiguration('Original_ID' , $arduino_id);
			$eqLogic->save(true);
		}
		if ($eqLogic->getIsEnable() == 0) continue;

		list(,$board,$usb) = jeedouino::GetPinsByBoard($arduino_id);
		jeedouino::log( 'debug','-=-= '.$board.'  ( '.$arduino_id.' ) =-=-');
		switch ($board)
		{
			case 'arduino':
				if ($usb)
				{
					jeedouino::GenerateUSBArduinoSketchFile($arduino_id);
					jeedouino::StartBoardDemon($arduino_id, 0, $board);
				}
				else jeedouino::GenerateLanArduinoSketchFile($arduino_id);
				break;
			case 'gpio':
				$oldKey = config::byKey($arduino_id.'_piGPIO_boot', 'jeedouino', 'none');
				if (($oldKey != 'none') and (config::byKey($arduino_id.'_PiGpio_boot', 'jeedouino', 'none') == 'none')) config::save($arduino_id.'_PiGpio_boot', $oldKey, 'jeedouino');
			case 'piplus':
			case 'piface':
				jeedouino::StartBoardDemon($arduino_id, 0, $board);
				break;
			case 'esp':
				if (!$usb) jeedouino::GenerateESP8266SketchFile($arduino_id);
				break;
		}
		sleep(2);
	}
	jeedouino::log( 'debug','-=-= Fin du démarrage des démons et de la re-génération des sketchs =-=-');
}


function jeedouino_remove() {

}

?>

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
    message::add('jeedouino', __('Suite à l\'installation de ce plugin, veuillez en consulter la documentation et les changelogs avant toute utilisation. Merci.', __FILE__));
    message::add('jeedouino', __('Pensez à installer les dépendances générales du plugin, ainsi que les dépendances spécifiques dont vous avez besoin. Merci', __FILE__));
}

function jeedouino_update()
{
    // update JeedouinoExt
    $ListExtIP = jeedouino::CleanIPJeedouinoExt();
    $IPsToNames = [];
    $eqLogics = eqLogic::byType('jeedouino');
    foreach ($eqLogics as $eqLogic)
    {
        $ip = trim($eqLogic->getConfiguration('iparduino'));
        $IPsToNames[$ip] = $eqLogic->getName();
        if ($eqLogic->getConfiguration('alone') == '1')
        {
            if ($ip != '' and filter_var($ip , FILTER_VALIDATE_IP))
            {
                $eqLogic->setConfiguration('iparduino2', $ip);
                $eqLogic->save(true);
            }
        }
    }
    foreach ($ListExtIP as $ip)
    {
        $id = trim(config::byKey('ID-' . $ip, 'jeedouino', ''));
        $JExtname = trim(config::byKey('JExtname-' . $ip, 'jeedouino', ''));
		if ($id == '' and $JExtname == '')
        {
            $id = jeedouino::AddIDJeedouinoExt($ip);
            if (isset($IPsToNames[$ip])) config::save('JExtname-' . $ip, $IPsToNames[$ip], 'jeedouino');
            else config::save('JExtname-' . $ip, 'JeedouinoExt', 'jeedouino');
        }
    }
	//$eqLogics = eqLogic::byType('jeedouino');
	$IPJeedom = jeedouino::GetJeedomIP();
	jeedouino::log( 'debug', __('-=-= Suite mise à jour du plugin, démarrage global des démons et re-génération des sketchs =-=-', __FILE__));
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
  jeedouino::log( 'debug', __('-=-= Fin du démarrage des démons et de la re-génération des sketchs =-=-', __FILE__));
  message::add('jeedouino', __('Suite mise à jour de ce plugin, veuillez en consulter la documentation et les changelogs avant toute utilisation. Merci.', __FILE__));
  message::add('jeedouino', __('Pensez à ré-installer les dépendances générales du plugin, ainsi que les dépendances spécifiques dont vous avez besoin. Merci', __FILE__));
}

function jeedouino_remove()
{
  jeedouino::log( 'info', __('-=-= Suppression du plugin. =-=-', __FILE__));
  jeedouino::log( 'info', __('Bye.', __FILE__));
}
?>

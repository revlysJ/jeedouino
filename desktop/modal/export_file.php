<?php
/* This file is part of Plugin Jeedouino for jeedom.
 *
 * Plugin Jeedouino for jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Plugin Jeedouino for Jeedom and the jeedouino plugin are distributed in the hope that they will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Plugin Jeedouino for jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

if (!isset($_GET['id'])){
	throw new Exception('{{EqLogic ID ne peut être vide}}');
}
$arduino_id = $_GET['id'];
$eqLogic = eqLogic::byId($arduino_id);

if (!is_object($eqLogic)) {
	throw new Exception('{{EqLogic non trouvé}}');
}
header("Content-type: json/force-download");
header("Content-disposition: attachment; filename=Jeedouino_" . $arduino_id . ".json");
$export = $eqLogic->export();

//echo '<br>getcmd : <br>';
//echo json_encode(utils::o2a($eqLogic->getCmd()));

			// pins de la carte (normalement deja definie)
			$myPin = array();
//			$generic_type = array();
//			$virtual = array();
			list($Arduino_pins , $board , $usb) = jeedouino::GetPinsByBoard($arduino_id);

			// copie des datas des pins
			foreach ($Arduino_pins as $pins_id => $pin_datas)
			{
				$myPin['EL_' . $pins_id] = config::byKey($arduino_id . '_' . $pins_id, 'jeedouino', 'not_used');
//				$generic_type['EL_' . $pins_id] = config::byKey('GT_' . $arduino_id . '_' . $pins_id, 'jeedouino', '');
//				$virtual['EL_' . $pins_id] = config::byKey('GV_' . $arduino_id . '_' . $pins_id, 'jeedouino', '');
			}
			// copie des options
			$myPin['EL__choix_boot'] = config::byKey($arduino_id . '_choix_boot', 'jeedouino', '2');
			$myPin['EL__ProbeDelay'] = config::byKey($arduino_id . '_ProbeDelay', 'jeedouino', '5');
//			$MesPins = array(	'myPin' 				=> $myPin ,
//											'generic_type' 		=> $generic_type,
//											'virtual' 				=> $virtual,
//											'choix_boot' 			=> $choix_boot,
//											'_ProbeDelay' 		=> $_ProbeDelay
//										);
$export['MyPins'] = $myPin;
echo json_encode($export);
//echo json_encode($myPin);

?>
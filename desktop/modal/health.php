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

if (!isConnect('admin')) {
	throw new Exception('401 Unauthorized');
}
$eqLogics = jeedouino::byType('jeedouino');
?>

<table class="table table-condensed tablesorter" id="table_healthjeedouino">
	<thead>
		<tr>
			<th>{{Equipement}}</th>
			<th>{{ID}}</th>
			<th>{{Actif}}</th>
			<th>{{IP}}</th>
			<th>{{Port}}</th>
			<th>{{Modèle}}</th>
			<th>{{Dernière communication}}</th>
			<th>{{Date création}}</th>
		</tr>
	</thead>
	<tbody>
	 <?php
foreach ($eqLogics as $eqLogic) 
{
	$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
	$board = strtolower($eqLogic->getConfiguration('arduino_board'));
	$modele = $board;
	$port = $eqLogic->getConfiguration('ipPort');
	if (substr($board,0,1)=='a') 
	{
		$modele = 'Arduino '.ucfirst(substr($board,1));
		if ($eqLogic->getConfiguration('datasource')=='usbarduino') $port = $eqLogic->getConfiguration('PortDemon');
	}
	elseif (substr($board,0,3)=='esp') 
	{
		$modele = 'ESP 8266';
		if (strpos($board,'mcu')!==false ) $modele = 'NodeMCU / Wemos';
	}
	elseif (substr($board,0,2)=='pi') $modele = 'RPI pi'.strtoupper(substr($board,2));
	
	$eqTime = trim($eqLogic->getStatus('lastCommunication'));
	if ($eqTime=='') $eqTime = config::byKey('lastCommunication'.$eqLogic->getId(), 'jeedouino', '');
	
	if (($timestamp = strtotime($eqTime)) !== false) 
	{
		$timestamp = time() - $timestamp;
		if ($timestamp<2*3600) $eqSpan = 'success';
		elseif ($timestamp<24*3600) $eqSpan = 'warning';
		else $eqSpan = 'danger';		
	}
	else 
	{
		//$timestamp = 0;
		$eqSpan = 'info'; 
	}
	$JeedouinoAlone = $eqLogic->getConfiguration('alone');
	if ($JeedouinoAlone == '1')	// Jeedouino sur un Rpi sans Jeedom.
	{
		$ip = trim($eqLogic->getConfiguration('iparduino'));
		$_path = trim(config::byKey('path-'.$ip, 'jeedouino', ''));
		if ($_path == '') $_path = '/';		
		$_port = trim(config::byKey('PORT-'.$ip, 'jeedouino', ''));
		if ($_port == '') $_port = '80';			
		$ip = '<span class="label label-success" style="font-size : 1em; cursor : default;"><a href="http://' . $ip . ':' . $_port . $_path . 'JeedouinoExt.php" target="_blank"><i class="fa fa-home"></i> ' . $eqLogic->getConfiguration('iparduino') . '</a></span>';
	}
	else
	{
		$ip = '<span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('iparduino') . '</span>';
	}
	
	echo '<tr style="' . $opacity . '"><td><a href="' . $eqLogic->getLinkToConfiguration() . '" style="text-decoration: none;">' . $eqLogic->getHumanName(true) . '</a></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getId() . '</span></td>';
	$status = '<span class="label label-success" style="font-size : 1em; cursor : default;">{{Oui}}</span>';
	if ($eqLogic->getIsEnable()==0) 	
	{
		$status = '<span class="label label-warning" style="font-size : 1em; cursor : default;">{{Non}}</span>';
	}
	echo '<td>' . $status . '</td>';
	echo '<td>' . $ip . '</td>';
	//echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('iparduino') . '</span></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $port . '</span></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $modele . '</span></td>';
	echo '<td><span class="label label-'.$eqSpan.'" style="font-size : 1em; cursor : default;">' . $eqTime . '</span></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('createtime') . '</span></td></tr>';
}
?>
	</tbody>
</table>

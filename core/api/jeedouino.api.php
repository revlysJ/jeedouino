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

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

global $jsonrpc;
if (!is_object($jsonrpc)) {
	throw new Exception(__('JSONRPC object not defined', __FILE__), -32699);
}
$params = $jsonrpc->getParams();

//start démons
if ($jsonrpc->getMethod() == 'StartAllDemons') 
{
	$EqLogics = (!isset($params['EqLogics'])) ? '' : $params['EqLogics'];
    jeedouino::StartAllDemons($EqLogics);
	$jsonrpc->makeSuccess();
}
//log (depuis esclaves)
if ($jsonrpc->getMethod() == 'log') 
{
	$log1 = (!isset($params['log1'])) ? 'error' : $params['log1'];
	$log2 = (!isset($params['log2'])) ? 'JeedouinoAPI' : $params['log2'];
    jeedouino::log($log1,$log2);
	$jsonrpc->makeSuccess();
}
// Démons
$eqLogic = (!isset($params['eqLogic'])) ? '' : $params['eqLogic'];
$DemonType = (!isset($params['DemonType'])) ? '' : $params['DemonType'];
	
if ($jsonrpc->getMethod() == 'StartBoardDemonCMD') 
{
	$ipPort = (!isset($params['ipPort'])) ? 8000 : $params['ipPort'];
    $jeedomIP = (!isset($params['jeedomIP'])) ? '' : $params['jeedomIP'];          
	$JeedomPort = (!isset($params['JeedomPort'])) ? 80 : $params['JeedomPort'];    
	$JeedomCPL = (!isset($params['JeedomCPL'])) ? '' : $params['JeedomCPL'];  
  	$PiPlusBoardID = (!isset($params['PiPlusBoardID'])) ? '32' : $params['PiPlusBoardID']; 
	$PiFaceBoardID = (!isset($params['PiFaceBoardID'])) ? '0' : $params['PiFaceBoardID']; 
	$PortDemon = (!isset($params['PortDemon'])) ? 8080 : $params['PortDemon'];
    $portUSB = (!isset($params['portUSB'])) ? '/dev/ttyUSB0' : $params['portUSB'];	
    jeedouino::StartBoardDemonCMD($eqLogic, $DemonType, $ipPort, $jeedomIP, $JeedomPort, $JeedomCPL, $PiPlusBoardID, $PiFaceBoardID, $PortDemon, $portUSB);
	$jsonrpc->makeSuccess();
}
if ($jsonrpc->getMethod() == 'StopBoardDemonCMD') 
{
    jeedouino::StopBoardDemonCMD($eqLogic, $DemonType);
	$jsonrpc->makeSuccess();
}
if ($jsonrpc->getMethod() == 'EraseBoardDemonFileCMD') 
{
    jeedouino::EraseBoardDemonFileCMD($eqLogic, $DemonType);
	$jsonrpc->makeSuccess();
}

throw new Exception(__('Aucune méthode correspondante pour le plugin jeedouino : ' . $jsonrpc->getMethod(), __FILE__));
?>
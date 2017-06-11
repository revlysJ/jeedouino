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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');
    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    // action qui permet d'obtenir l'ensemble des eqLogic
    if (init('action') == 'getAll') {
        $eqLogics = eqLogic::byType('jeedouino'); // ne pas oublier de modifier pour le nom de votre plugin
        // la liste des équipements
        foreach ($eqLogics as $eqLogic) {
            $data['id'] = $eqLogic->getId();
            $data['humanSidebar'] = $eqLogic->getHumanName(true, false);
            $data['humanContainer'] = $eqLogic->getHumanName(true, true);
            $return[] = $data;
        }
        ajax::success($return);
    }
	// Actions pour la gestion du reset compteur
 	if (init('action') == 'ResetCPT') 
    {
        jeedouino::ResetCPT(init('boardid'),init('RSTvalue'),init('CMDid'));
		ajax::success();
	}   
	
    // Actions pour la gestion des démons  / Jeedouino
 	if (init('action') == 'StartBoardDemon') 
    {
        jeedouino::StartBoardDemon(init('boardid'), init('id'), init('DemonType'));
		ajax::success();
	}   
  	if (init('action') == 'ReStartBoardDemon') 
    {
        jeedouino::ReStartBoardDemon(init('boardid'), init('id'), init('DemonType'));
		ajax::success();
	}   
 	if (init('action') == 'StopBoardDemon') 
    {
        jeedouino::StopBoardDemon(init('boardid'), init('id'), init('DemonType'));
		ajax::success();
	} 
 
    // Actions pour l'Installation des dépendances
  	if (init('action') == 'installUpdate') 
    {
        exec('sudo apt-get -y --yes --force-yes update >> '.log::getPathToLog('jeedouino_update') . ' 2>&1 &');
		ajax::success();
	}  
  	if (init('action') == 'installSerial') 
    {
        exec('sudo apt-get -y --yes --force-yes install python-serial >> '.log::getPathToLog('jeedouino_update') . ' 2>&1 &');
		ajax::success();
	}    	
  	if (init('action') == 'installGPIO') 
    {
        exec('sudo pip install RPi.GPIO >> '.log::getPathToLog('jeedouino_update') . ' 2>&1 &');
		ajax::success();
	}   
 	if (init('action') == 'installPIFACE') 
    {
        exec('sudo apt-get -y --yes --force-yes install python-pifacedigitalio >> '.log::getPathToLog('jeedouino_update') . ' 2>&1 &');
		ajax::success();
	}      
 	if (init('action') == 'installPiPlus') 
    {
        exec('sudo apt-get -y --yes --force-yes install python-smbus >> '.log::getPathToLog('jeedouino_update') . ' 2>&1 &');
		ajax::success();
	}    	
    // action qui permet d'effectuer la sauvegarde des données en asynchrone
    if (init('action') == 'saveStack') {
        $params = init('params');
        ajax::success(jeedouino::saveStack($params)); // ne pas oublier de modifier pour le nom de votre plugin
    }
    throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
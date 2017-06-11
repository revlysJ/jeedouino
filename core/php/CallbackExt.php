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
jeedouino::log( 'debug','CALLBACK EXT - Requête reçue : ?'.$_SERVER['QUERY_STRING']);
if (isset($_GET['ip']))
{
	$ip = $_GET['ip'];
	if (filter_var($ip, FILTER_VALIDATE_IP) !== false) 
	{
		jeedouino::log( 'debug','CALLBACK EXT - IP reçue de '.$ip);
		// On a reçu une nouvelle ip ? Si oui on l'ajoute à la liste
		$ListExtIP=config::byKey('ListExtIP', 'jeedouino', '');
		if ($ListExtIP == '') 
		{
			$ListExtIP = array($ip);
		}
		else
		{
			if (!in_array($ip, $ListExtIP))	$ListExtIP[]=$ip;
		}
		config::save('ListExtIP', $ListExtIP, 'jeedouino');
	}
	else jeedouino::log( 'error','CALLBACK EXT - IP non valide reçue de '.$ip);

	
	if (isset($_GET['port']))
	{
		config::save('PORT-'.$ip, $_GET['port'], 'jeedouino');
		jeedouino::log( 'debug','CALLBACK EXT - PORT reçu de '.$ip. ' : '.$_GET['port']);
	}
	else jeedouino::log( 'error','CALLBACK EXT - PORT non reçu.');
	
	if (isset($_GET['usbMapping']))
	{
		config::save('uMap-'.$ip, json_decode($_GET['usbMapping'],true), 'jeedouino');
		jeedouino::log( 'debug','CALLBACK EXT - usbMapping reçu de '.$ip. ' : '.$_GET['usbMapping']);
	}
	else jeedouino::log( 'error','CALLBACK EXT - usbMapping non reçu.');
	
	if (isset($_GET['path']))
	{
		config::save('path-'.$ip, $_GET['path'], 'jeedouino');
		jeedouino::log( 'debug','CALLBACK EXT - path reçu de '.$ip. ' : '.$_GET['path']);
	}
	else jeedouino::log( 'error','CALLBACK EXT - path non reçu.');	
	
	if (isset($_GET['Dstart']))
	{
		jeedouino::log( 'debug','CALLBACK EXT - Dstart reçu de '.$ip. ' : '.$_GET['Dstart']);
		if (config::byKey('jeeNetwork::mode') == 'master') 
		{
			$EqLogicArr=json_decode($_GET['Dstart'],true);
			if (is_array($EqLogicArr))
			{			
				jeedouino::StartAllDemons($EqLogicArr);
			}
		}		
	}

}
else jeedouino::log( 'error','CALLBACK EXT- IP non reçue.');
?>
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
    /////
    // Actions JeedouinoExt
    //
    // 		config::byKey('Auto_'. $board_id, 'jeedouino', 0)
    //
    if (init('action') == 'bt_AutoReStart')
    {
        $board_id = init('boardid');
        if (config::byKey('Auto_' . $board_id, 'jeedouino', 1)) config::save('Auto_' . $board_id, 0, 'jeedouino');
        else config::save('Auto_' . $board_id, 1, 'jeedouino');
        ajax::success(['boardid'    => $board_id,
				       'status'      => config::byKey('Auto_' . $board_id, 'jeedouino', 0)]);
    }

    if (init('action') == 'save_jeedouinoExt')
    {
		$JeedouinoExtSave = jeedom::fromHumanReadable(json_decode(init('jeedouino_ext'), true));
        $ip = trim($JeedouinoExtSave['IP']);
        if ($ip == '')
            throw new Exception(__('/!\ IP non renseignée. /!\ ', __FILE__) . $ip, 9999);
        if (filter_var($ip, FILTER_VALIDATE_IP) === false)
            throw new Exception(__('/!\ IP non valide. /!\ ', __FILE__) . $ip, 9999);
        if ($ip == '127.0.0.1')
            throw new Exception(__('/!\ IP non valide. /!\ ', __FILE__) . $ip, 9999);
//        $ListExtIP = config::byKey('ListExtIP', 'jeedouino', []);
//        if (in_array($ip, $ListExtIP))
//            throw new Exception(__('/!\ IP déja utilisée. /!\ ', __FILE__) . $ip, 9999);
        $id = jeedouino::SaveIPJeedouinoExt($JeedouinoExtSave);
		ajax::success(jeedom::toHumanReadable(jeedouino::GetJeedouinoExt($ip)));
	}
    if (init('action') == 'remove_jeedouinoExt')
    {
        $ip = jeedouino::IPfromIDJeedouinoExt(init('id'));
        if (!jeedouino::RemoveJeedouinoExt($ip))
        {
			throw new Exception(__('JeedouinoExt inconnu : ' . $ip, __FILE__) . init('id'), 9999);
		}
		ajax::success();
	}
    if (init('action') == 'get_jeedouinoExt')
    {
        if (init('id') == '') throw new Exception(__('JeedouinoExt ID inconnu : ', __FILE__) . init('id'), 9999);
        $ip = jeedouino::IPfromIDJeedouinoExt(init('id'));
        $ListExtIP = config::byKey('ListExtIP', 'jeedouino', '');
        if (!in_array($ip, $ListExtIP))
        {
			throw new Exception(__('JeedouinoExt inconnu : ' . $ip, __FILE__) . init('id'), 9999);
		}
		ajax::success(jeedom::toHumanReadable(jeedouino::GetJeedouinoExt($ip)));
        //ajax::success(jeedouino::GetJeedouinoExt($ip));
	}
    if (init('action') == 'send_jeedouinoExt')
    {
        $JeedouinoExtSend = jeedom::fromHumanReadable(json_decode(init('jeedouino_ext'), true));
        if (!jeedouino::SendJeedouinoExt($JeedouinoExtSend))
        {
            ajax::error(__('Erreur, Impossible d envoyer les fichiers pour JedouinoExt. ', __FILE__));
        }
		ajax::success();
	}
    if (init('action') == 'send_jeedouinoExt2')
    {
        $JeedouinoExtSend = jeedom::fromHumanReadable(json_decode(init('jeedouino_ext'), true));
        if (!jeedouino::SendJeedouinoExt($JeedouinoExtSend, true))
        {
            ajax::error(__('Erreur, Impossible d envoyer les fichiers pour JedouinoExt. ', __FILE__));
        }
		ajax::success();
	}
    if (init('action') == 'getExtLog')
    {
        $JeedouinoExtGet = jeedom::fromHumanReadable(json_decode(init('jeedouino_ext'), true));
        if ($JeedouinoExtGet == '') ajax::error('DarkMatterIsUndetectable...');
        $_log = dirname(__FILE__) . '/../../ressources/jeedouino_ext.logg'; //jeedouino::getPathToLog('jeedouino_ext');
        if (!jeedouino::SshGetJeedouinoExt($JeedouinoExtGet, $_log, init('logfile')))
        {
            ajax::error(__('Erreur, Impossible de récupérer le fichier de log de JedouinoExt. ', __FILE__));
        }
		ajax::success(jeedouino::getExtLog($_log));
    }
    ////

    // Actions pour la gestion du reset compteur / RéArm event compteurs
    if (init('action') == 'ResetCPT')
    {
      jeedouino::ResetCPT(init('boardid'), init('RSTvalue'), init('CMDid'));
      ajax::success();
    }
    if (init('action') == 'CptDelay')
    {
      jeedouino::CptDelay(init('boardid'), init('CptDelay'));
      ajax::success();
    }
    if (init('action') == 'bounceDelay')
    {
      jeedouino::bounceDelay(init('boardid'), init('bounceDelay'));
      ajax::success();
    }
    // Action pour mettre à jour le délai de relève des sondes
    if (init('action') == 'ProbeDelay')
    {
      jeedouino::ProbeDelay(init('boardid'), init('ProbeDelay'));
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
    $title = str_replace('install', '', init('action'));
    $sudo = system::getCmdSudo();
    $exec = "echo '======= Start of $title installation ======='\n";
    $xEnd = "echo '======= End of $title installation ======='\n";
    if (init('action') == 'installUpdate')
    {
      $exec .= "$sudo apt-get -y update \n";
      $exec .= "$sudo apt-get -y upgrade \n";
      $exec .= "$sudo apt-get -y dist-upgrade \n";
      jeedouino::execSH($exec . $xEnd, log::getPathToLog('jeedouino_update'));
      ajax::success();
    }
  	if (init('action') == 'installSerial')
    {
      $exec .= "$sudo apt-get -y install python{,3}-pip \n";
      $exec .= "$sudo apt-get -y python-serial \n";
      $exec .= "$sudo pip install --upgrade pip \n";
      $exec .= "$sudo pip3 uninstall serial \n";
      $exec .= "$sudo pip3 install pyserial \n";
      jeedouino::execSH($exec . $xEnd, log::getPathToLog('jeedouino_usb'));
      ajax::success();
    }
  	if (init('action') == 'installGPIO')
    {
      $exec .= "$sudo pip3 install RPi.GPIO \n";
      $exec .= "$sudo pip install RPi.GPIO \n";
      jeedouino::execSH($exec . $xEnd, log::getPathToLog('jeedouino_pigpio'));
      ajax::success();
    }
    if (init('action') == 'installPIFACE')
    {
      $exec .= "$sudo apt-get -y install python{,3}-pip \n";
      $exec .= "$sudo apt-get -y python{,3}-setuptools \n";
      $exec .= "$sudo pip install --upgrade pip \n";
      $exec .= "$sudo pip3 install pifacecommon pifacedigitalio \n";
      $exec .= "$sudo pip install pifacecommon pifacedigitalio \n";
      $exec .= "$sudo echo dtparam=spi=on | sudo tee -a /boot/config.txt \n";
      jeedouino::execSH($exec . $xEnd, log::getPathToLog('jeedouino_piface'));
      ajax::success();
    }
    if (init('action') == 'installPiPlus')
    {
      $exec .= "$sudo apt-get -y install i2c-tools libi2c-dev python-smbus python3-smbus \n";
      $exec .= "$sudo echo dtparam=i2c_arm=on | sudo tee -a /boot/config.txt \n";
      $exec .= "$sudo echo dtparam=i2c1=on | sudo tee -a /boot/config.txt \n";
      $exec .= "$sudo echo i2c-dev | sudo tee -a /etc/modules \n";
      $exec .= "$sudo echo i2c-bcm2708 | sudo tee -a /etc/modules \n";
      jeedouino::execSH($exec . $xEnd, log::getPathToLog('jeedouino_piplus'));
      ajax::success();
    }
 	if (init('action') == 'installDS18B20')
    {
        $exec .= "$sudo rm -Rf /tmp/ds \n";
        $exec .= "$sudo git clone https://github.com/danjperron/BitBangingDS18B20.git /tmp/ds \n";
        $exec .= "cd /tmp/ds/python \n";
        $exec .= "$sudo python3 setup.py install \n";
        $exec .= "$sudo python setup.py install \n";
        jeedouino::execSH($exec . $xEnd, log::getPathToLog('jeedouino_pigpio'));
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

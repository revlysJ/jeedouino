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

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
if (isset($_GET['id']))
{
	$arduino_board = '';
	if (isset($_GET['board'])) $arduino_board = trim($_GET['board']);

	include_file('core', 'jeedouino', 'config', 'jeedouino');
	global $ArduinoMODEpins, $Arduino328pins, $ArduinoMEGApins, $ArduinoESPanalogPins;
	global $PifaceMODEpinsIN, $PifaceMODEpinsOUT, $Pifacepins;
	global $PiGPIOpins, $PiGPIO26pins, $PiGPIO40pins;
	global $ESP8266pins, $ESP01pins, $ESP07pins, $espMCU01pins, $ESP32pins;
	global $SonoffPow, $SonoffPowPins, $Sonoff4ch, $Sonoff4chPins;
	global $ElectroDragonSPDT, $ElectroDragonSPDTPins;
	global $PiPluspins, $PiPlus16pins;
	global $UserModePins;

	$arduino_id = $_GET['id'];
	$my_arduino = eqLogic::byid($arduino_id);
    if ($my_arduino == null)
    {
        jeedouino::log( 'debug','$arduino_id' . json_encode($_GET));
        jeedouino::log( 'debug','$my_arduino' . json_encode($my_arduino));
        echo __(' NON DEFINI ! ', __FILE__);
        die();
    }

	$ModeleArduino = $my_arduino->getConfiguration('arduino_board');
	$PortArduino = $my_arduino->getConfiguration('datasource');
	$LocalArduino = $my_arduino->getConfiguration('arduinoport');
	$IPArduino = trim($my_arduino->getConfiguration('iparduino'));
    if ($IPArduino == '')
    {
        // si équipement désactivé, iparduino pas mis à jour par iparduino2 dans presave()
        $IPArduino = strtolower(trim($my_arduino->getConfiguration('iparduino2')));
        if ($IPArduino == '') $IPArduino = __(' NON DEFINIE ! ', __FILE__);
    }

	// On verifie que l'utilisateur n'a pas changé de modèle de carte après la 1ère sauvegarde sans resauver derrière.
	if ($arduino_board != '')
	{
		if ($arduino_board != $ModeleArduino) $ModeleArduino = $arduino_board;
	}
	$non_defini = true;
	if ($ModeleArduino != '')
	{

		if ($PortArduino == 'rj45arduino') $message_a = __(' réseau sur IP : ', __FILE__) . $IPArduino . '.';
		elseif ($PortArduino == 'usbarduino')
		{
			$message_a = ' USB ';
			if ($LocalArduino == 'usblocal') $message_a .= __(' sur port local. ', __FILE__);
			elseif ($LocalArduino == 'usbdeporte') $message_a .= __(' sur port déporté. ', __FILE__);
			else
			{
				$non_defini = false;
				$message_a .= __(' NON DEFINI ! ', __FILE__);
			}
		}
		else
		{
			$non_defini = false;
			$message_a = __(' NON DEFINI ! ', __FILE__);
		}
	}
	else
	{
		$non_defini = false;
		$message_a = __(' NON DEFINI ! ', __FILE__);
	}


	if ($non_defini)
	{
		// Pins utilisateur
		$UserPinsBase = 500;
		$UserPinsStatus = false;
		$user_pins = array();
		if (config::byKey('ActiveUserCmd', 'jeedouino', false) and substr($ModeleArduino, 0, 2) != 'pi')
		{
			$UserPinsMax = trim($my_arduino->getConfiguration('UserPinsMax'));
			if (!is_numeric($UserPinsMax)) $UserPinsMax = 0;
			if ($UserPinsMax > 0 and $UserPinsMax<101) $UserPinsStatus = true;
			else $UserPinsMax = 0;
			$my_arduino->setConfiguration('UserPinsMax', $UserPinsMax);
			$my_arduino->save(true);

			$user_pins = jeedouino::GiveMeUserPins($UserPinsMax, $UserPinsBase);
		}
?>

<div class="tab-content" id="backup_pins" >
			<div id='div_alertpins' style="display: none;"></div>
			<div class="form-group" >
				<label class="col-sm-6 control-label ">{{Paramétrage des pins de l'arduino/esp/rpi}} <?php echo $message_a; ?></label>
				<div class="col-sm-6">
					<a href="https://revlysj.github.io/jeedouino/fr_FR/" target="_blank" class="btn btn-info eqLogicAction pull-right"  title="{{Lien vers la Documentation du plugin}}"><i class="fas fa-book"></i> </a>
					<a class="btn btn-success pull-right bt_savebackup_pins" id="bt_savebackup_pins1" title="{{Pensez à sauver l'équipement pour envoyer la config à la carte}}">{{* Sauvegarde}}</a>
				</div>
			</div>

		<ul class="nav nav-tabs" role="tablist" >
				<?php// if (substr($ModeleArduino,0,2) != 'pi' or (substr($ModeleArduino,0,6) == 'piGPIO') or ($ModeleArduino == 'piPlus'))
				//{
				?>
			<li role="presentation"><a href="#optionstab" aria-controls="profile" role="tab" data-toggle="tab"  id="bt_conf_Pin"><i class="fas fa-wrench"></i> {{Options}}</a></li>
				<?php
				//}
				?>
			<li role="presentation" class="active"><a href="#boardpinstab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Pins Matérielles}}</a></li>
				<?php if ($UserPinsStatus)
				{
				?>
			<li role="presentation"><a href="#userpinstab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-code"></i> {{Pins Utilisateur}}</a></li>
				<?php
				}
        if (substr($ModeleArduino, 0, 2) == 'pi')
        {
				?>
        <li role="presentation"><a href="https://fr.pinout.xyz/" target="_blank" aria-controls="profile" role="tab" ><img src="plugins/jeedouino/icons/pinout.jpg" style="width:20px; height: 20px;"/></a></li>
        <?php
				}
				?>
  	</ul>

		<div class="tab-content" style="height:calc(100% - 70px);overflow:auto;overflow-x: hidden;">
			<?php //if (substr($ModeleArduino, 0, 2) != 'pi' or (substr($ModeleArduino, 0, 6) == 'piGPIO') or ($ModeleArduino == 'piPlus'))
			//{ // 11
				$_ProbeDelay = '
				<div class="form-group">
					<label class="col-sm-6 control-label hidden-xs">{{Délai de renvoi des valeurs des sondes T°/H en Minutes : }} <i class="fas fa-question-circle tooltips" title="{{Délai sondes en Minutes : 1min à 1000min max.}}"></i></label>
					<div class="col-sm-6">
						<input type="number" class="form-control  configKeyPins" data-l1key="' . $arduino_id . '_ProbeDelay"  placeholder="Délai sondes en Minutes : 1min à 1000min max." min="1" max="1000"/>
            <a class="btn btn-warning btn-xs bt_ProbeDelay"><i class="fas fa-rss"></i> {{MàJ immédiatement le délai entre sondes avec la valeur ci-dessus:}}</a>
            <br><br>
					</div>
				</div>
        <div class="form-group">
					<label class="col-sm-6 control-label hidden-xs">{{Pas de logs pour les erreurs hors-limites des sondes. }} </label>
					<div class="col-sm-6">
						<input type="checkbox" class="form-control configKeyPins" data-l1key="' . $arduino_id . '_ProbeNoLog" />
            <br><br>
					</div>
				</div>';
			?>
			<div role="tabpanel" class="tab-pane" id="optionstab">
				<?php if (substr($ModeleArduino, 0, 2) != 'pi')
				{
          echo '<div class="form-group">
                <label class="col-sm-6 control-label hidden-xs">{{Choix de sauvegarde de l\'état des pins suite a un redémarrage de l\'Arduino/esp (Coupure de courant, reset,etc...)}}</label>
                <div class="col-sm-6">';
					if (config::byKey($arduino_id . '_choix_boot', 'jeedouino', 'none') != 'none') $message_a = '';
					else $message_a = ' selected ';

					echo '<select class="form-control configKeyPins" data-l1key="' . $arduino_id . '_choix_boot">';
					echo '<option value="0">{{Pas de sauvegarde - Toutes les pins sorties non modifiées au démarrage.}}</option>';
					echo '<option value="1">{{Pas de sauvegarde - Toutes les pins sorties mises à LOW au démarrage.}}</option>';
					echo '<option value="2">{{Pas de sauvegarde - Toutes les pins sorties mises à HIGH au démarrage.}}</option>';
					echo '<option value="3" style="color: #3c763d!important;"' . $message_a . '>{{Sauvegarde sur JEEDOM - Toutes les pins sorties mises suivant leur sauvegarde dans Jeedom. Lent, Jeedom requis sinon pins mises à HIGH.}}</option>';
					echo '<option value="5" style="color: #3c763d!important;">{{Sauvegarde sur JEEDOM - Toutes les pins sorties mises suivant leur sauvegarde dans Jeedom. Lent, Jeedom requis sinon pins mises à LOW.}}</option>';
					echo '<option value="4" style="color: #a94442!important;">{{Sauvegarde sur EEPROM- Toutes les pins sorties mises suivant leur sauvegarde dans l\'EEPROM. Autonome, rapide mais durée de vie de l\'eeprom fortement réduite.}}</option>';
					echo '</select><br><br>';
          echo '</div></div>';
          if ($ModeleArduino != 'espsonoffpow') echo $_ProbeDelay;
				}
				elseif (substr($ModeleArduino,0,6) == 'piGPIO')
				{
          echo '<div class="form-group">
                <label class="col-sm-6 control-label hidden-xs">{{Choix de l\'état des pins sorties au démarrage du démon piGPIO : }}</label>
                <div class="col-sm-6">';
					echo '<select class="form-control  configKeyPins" data-l1key="' . $arduino_id . '_PiGpio_boot">';
					echo '<option value="0">{{Toutes les pins sorties mises à LOW au démarrage du démon}}</option>';
					echo '<option value="1">{{Toutes les pins sorties mises à HIGH au démarrage du démon}}</option>';
					echo '</select><br><br>';
          echo '</div></div>';

          echo $_ProbeDelay;

          echo '<div class="form-group">
          			<label class="col-sm-6 control-label hidden-xs">{{Délai RéArm Event compteurs en Secondes (En test) : }} <i class="fas fa-question-circle tooltips" title="{{Mettre ici 3600 pour UNE heure  : 600s à 86400s max.}}"></i></label>
          			<div class="col-sm-6">
          				<input type="number" class="form-control configKeyPins" data-l1key="' . $arduino_id . '_CptDelay"  placeholder="Mettre ici 3600 pour UNE heure  : 600s à 86400s max." min="600" max="86400"/>
                  <a class="btn btn-warning btn-xs bt_CptDelay"><i class="fas fa-rss"></i> {{MàJ immédiatement le délai de RéArm Event des compteurs avec la valeur ci-dessus:}}</a>
                  <br><br>
          			</div>
          		</div>';
          echo '<div class="form-group">
          			<label class="col-sm-6 control-label hidden-xs">{{Délai anti-rebonds compteurs en milli-secondes (En test) : }} <i class="fas fa-question-circle tooltips" title="{{Mettre ici 200 pour 200ms de bounceTime.}}"></i></label>
          			<div class="col-sm-6">
          				<input type="number" class="form-control configKeyPins" data-l1key="' . $arduino_id . '_bounceDelay"  placeholder="Mettre ici 200 pour 200ms de bounceTime." min="50" max="10000"/>
                  <a class="btn btn-warning btn-xs bt_bounceDelay"><i class="fas fa-rss"></i> {{MàJ immédiatement le délai anti-rebonds des compteurs avec la valeur ci-dessus:}}</a>
                  <br><br>
          			</div>
          		</div>';
				}
				elseif ($ModeleArduino == 'piPlus')
				{
          echo '<div class="form-group">
                <label class="col-sm-6 control-label "><p class="hidden-xs"><br/>{{Choix de l\'état des pins sorties au démarrage du démon piPlus. (En tests)}}</p></label>
                <div class="col-sm-6">';
					echo '<select class="form-control  configKeyPins" data-l1key="' . $arduino_id . '_PiPlus_boot">';
					echo '<option value="0">{{Toutes les pins sorties mises à LOW au démarrage du démon}}</option>';
					echo '<option value="1">{{Toutes les pins sorties mises à HIGH au démarrage du démon}}</option>';
					echo '</select><br><br>';
          echo '</div></div>';
          echo '<div class="form-group">
              <label class="col-sm-6 control-label hidden-xs">{{Délai boucle compteurs en milli-secondes (En test) : }} <i class="fas fa-question-circle tooltips" title="{{Mettre ici 200 pour 200ms de délai.}}"></i></label>
              <div class="col-sm-6">
                <input type="number" class="form-control configKeyPins" data-l1key="' . $arduino_id . '_bounceDelay"  placeholder="Mettre ici 200 pour 200ms de délai." min="20" max="300"/>
                <a class="btn btn-warning btn-xs bt_bounceDelay"><i class="fas fa-rss"></i> {{MàJ immédiatement le délai de boucle des compteurs avec la valeur ci-dessus:}}</a>
                <br><br>
              </div>
            </div>';
				}
        elseif ($ModeleArduino == 'piface')
				{
          echo '<div class="form-group">
              <label class="col-sm-6 control-label hidden-xs">{{Délai boucle compteurs en milli-secondes (En test) : }} <i class="fas fa-question-circle tooltips" title="{{Mettre ici 200 pour 200ms de délai.}}"></i></label>
              <div class="col-sm-6">
                <input type="number" class="form-control configKeyPins" data-l1key="' . $arduino_id . '_bounceDelay"  placeholder="Mettre ici 200 pour 200ms de délai." min="20" max="300"/>
                <a class="btn btn-warning btn-xs bt_bounceDelay"><i class="fas fa-rss"></i> {{MàJ immédiatement le délai de boucle des compteurs avec la valeur ci-dessus:}}</a>
                <br><br>
              </div>
            </div>';
				}
				echo '</div>'; // end tabpanel
			//} // 11
				// Html tabs echo
				$UserPinsTab = '';
				$BoardPinsTab = '';

				// Pins materielles
                $Arduino_pins = '';
                switch ($ModeleArduino)
				{
                    case 'auno':
                    case 'a2009':
                    case 'anano':
                        $Arduino_pins = $Arduino328pins + $user_pins;
                        break;
                    case 'a1280':
                    case 'a2560':
                        $Arduino_pins = $ArduinoMEGApins + $user_pins;
                        break;
                    case 'piface':
                        $Arduino_pins = $Pifacepins;
                        break;
                    case 'piGPIO26':
                        $Arduino_pins = $PiGPIO26pins;
                        break;
                    case 'piGPIO40':
                        $Arduino_pins = $PiGPIO40pins;
                        break;
					case 'piPlus':
						$Arduino_pins = $PiPlus16pins;
						break;
					case 'esp01':
						$Arduino_pins = $ESP01pins + $user_pins;
						break;
					case 'esp07':
						$Arduino_pins = $ESP07pins + $user_pins;
						break;
					case 'espMCU01':
						$Arduino_pins = $espMCU01pins + $user_pins;
						break;
                    case 'espsonoffpow':
						$Arduino_pins = $SonoffPowPins + $user_pins;
						break;
                    case 'espsonoff4ch':
						$Arduino_pins = $Sonoff4chPins + $user_pins;
						break;
                    case 'espElectroDragonSPDT':
						$Arduino_pins = $ElectroDragonSPDTPins + $user_pins;
						break;
                    case 'esp32dev':
						$Arduino_pins = $ESP32pins + $user_pins;
						break;
					default:
						$Arduino_pins = '';
					break;
                }
				// On créé la liste des virtuels commune a tous
				if (method_exists('virtual', 'copyFromEqLogic'))
				{
					$Virtuels = '';
					foreach (jeeObject::all() as $object)
					{
						$options = '';
						foreach (eqLogic::byType('virtual', true) as $eqLogic)
						{
							if ($eqLogic->getObject_id() == $object->getId()) $options .= '<option value="' . $eqLogic->getId() . '" >' . $eqLogic->getName() . '</option>';
						}
						if ($options != '')
						{
							$Virtuels .=  '<optgroup label="' . $object->getName() . '">';
							$Virtuels .=  $options;
							$Virtuels .=  '</optgroup>';
						}
					}
					// Orphelins = Virtuels sans parents déclarés
					$options = '';
					$Orphelins = '';
					foreach (eqLogic::byType('virtual', true) as $eqLogic)
					{
						if ($eqLogic->getObject_id() == null) $options .= '<option value="' . $eqLogic->getId() . '" >' . $eqLogic->getName() . '</option>';
					}
					if ($options != '')
					{
						$Orphelins .=  '<optgroup label="_{{Orphelins}}_">';
						$Orphelins .=  $options;
						$Orphelins .=  '</optgroup>';
					}
					$Virtuels = '<option>{{Aucun}}</option>' . $Orphelins . $Virtuels . '</select>';
				}

                // Recup de la liste des generic-types de jeedom
				$InfoPinsFull = [];
				$ActionPinsFull = [];
				$OtherPinsFull = [];
				foreach (jeedom::getConfiguration('cmd::generic_type') as $key => $value)
				{
					$Gvalue = strtolower($value['type']);
					if ($Gvalue == 'info') $InfoPinsFull[$key] = $value['name'];
					elseif ($Gvalue == 'action') $ActionPinsFull[$key] = $value['name'];
					else $OtherPinsFull[$key] = $value['name'];
				}

				// On traite les pins
				foreach ($Arduino_pins as $pins_id => $pin_datas)
				{
					$TmpPins = '<tr class="pinoche" data-logicalId="' . $pins_id . '">';
					if ($pin_datas['option'] != '') $TmpPins .= '<td>' . $pin_datas['Nom_pin'] . ' - ( ' . $pin_datas['option'] . ' ) ';
					else $TmpPins .= '<td>' . $pin_datas['Nom_pin'] . ' ';
          if ($ModeleArduino == 'esp01' or $ModeleArduino == 'esp07' or $ModeleArduino == 'espMCU01' or $ModeleArduino == 'esp32dev') $TmpPins .= '<br><span class="label label-info" >PIN No : ' . $pins_id . '</span></td>';
          else $TmpPins .= '</td>';

					// pins non disponibles
					if ($pin_datas['disable'] == '1')
					{
                        // cas particulier GPIO RPI disabled sur SDA et SCL (i2c)
                        $sda = substr($pin_datas['option'], 0, 3);
                        if ($sda == 'SDA')
                        {
                            $TmpPins .= '<td>';
                            $TmpPins .= '<select class="form-control  configKeyPins" data-l1key="' . $arduino_id . '_' . $pins_id . '">';
                            $TmpPins .= '<option value="not_used">{{Non utilisée}}</option>';
                            $TmpPins .= '<option value="bmp180">{{Capteur BMP085/180 Température/Pression}}</option>';
                            $TmpPins .= '<option value="bmp280">{{Capteur BMP280 (i2c x76) Température/Pression}}</option>';
                            $TmpPins .= '<option value="bme280">{{Capteur BME280 (i2c x76) Température/Humidité/Pression}}</option>';
                            $TmpPins .= '<option value="bme680">{{Capteur BME680 (i2c x76) Température/Humidité/Pression/Gas COV}}</option>';
                            $TmpPins .= '</select>';
                            $TmpPins .= '</td>';
                        }
                        elseif ($sda == 'SCL')
                        {
                            $TmpPins .= '<td>';
                            $TmpPins .= '<select class="form-control  configKeyPins" data-l1key="' . $arduino_id . '_' . $pins_id . '">';
                            $TmpPins .= '<option value="not_used">{{Non utilisée}}</option>';
                            $TmpPins .= '<option value="bmp280b">{{Capteur BMP280 (i2c x77) Température/Pression}}</option>';
                            $TmpPins .= '<option value="bme280b">{{Capteur BME280 (i2c x77) Température/Humidité/Pression}}</option>';
                            $TmpPins .= '<option value="bme680b">{{Capteur BME680 (i2c x77) Température/Humidité/Pression/Gas COV}}</option>';
                            $TmpPins .= '</select>';
                            $TmpPins .= '</td>';
                        }
						else
                        {
                            $TmpPins = str_replace('<tr class="', '<tr class="hide ', $TmpPins );
                            $TmpPins .= '<td><input disabled class="form-control configKeyPins" name="' . $arduino_id . '_' . $pins_id . '" value="{{Pin réservée !}}"></td>';
                        }
						if ($pins_id < $UserPinsBase) $BoardPinsTab .= $TmpPins;
						else $UserPinsTab .= $TmpPins;
						continue;
					}
					// pins reservee pour la carte ethernet sur arduino
					if (($PortArduino == 'rj45arduino') and ($pin_datas['ethernet'] == '1'))
					{
						$TmpPins .= '<td><input disabled class="form-control configKeyPins" name="' . $arduino_id . '_' . $pins_id . '" value="{{Pin réservée pour le shield ethernet !}}"></td>';
						if ($pins_id < $UserPinsBase) $BoardPinsTab .= $TmpPins;
						else $UserPinsTab .= $TmpPins;
						continue;
					}

					$InfoPins = array();
					$ActionPins = array();
					$OtherPins = array();
					$TmpPins .= '<td>';
					$TmpPins .= '<select class="form-control  configKeyPins" data-l1key="'.$arduino_id.'_'.$pins_id.'">';

					if ($pins_id < $UserPinsBase)
					{
						if ($ModeleArduino == 'piface')
						{
							if ($pin_datas['option']!='IN')
							{
								foreach ($PifaceMODEpinsOUT as $mode_value => $mode_name)
								{
									$ActionPins[] = '<option value="'.$mode_value.'">{{'.$mode_name.'}}</option>';
								}
							}
							else
							{
								foreach ($PifaceMODEpinsIN as $mode_value => $mode_name)
								{
									$InfoPins[] = '<option value="'.$mode_value.'">{{'.$mode_name.'}}</option>';
								}
							}
						}
						else if ($ModeleArduino == 'piGPIO26' or $ModeleArduino == 'piGPIO40' )
						{
							foreach ($PiGPIOpins as $mode_value => $mode_name)
							{
								if (substr($mode_name,0,1)=='i') $InfoPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
								elseif (substr($mode_name,0,1)=='o') $ActionPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
								else $OtherPins[] = '<option value="'.$mode_value.'">{{'.$mode_name.'}}</option>';
							}
						}
						else if ($ModeleArduino == 'piPlus')
						{
							foreach ($PiPluspins as $mode_value => $mode_name)
							{
								if (substr($mode_name,0,1)=='i') $InfoPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
								elseif (substr($mode_name,0,1)=='o') $ActionPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
								else $OtherPins[] = '<option value="'.$mode_value.'">{{'.$mode_name.'}}</option>';
							}
						}
						else if ($ModeleArduino == 'esp01' or $ModeleArduino == 'esp07' or $ModeleArduino == 'espMCU01' or $ModeleArduino == 'esp32dev')
						{
							if ($pin_datas['option']!='ANA')
							{
								foreach ($ESP8266pins as $mode_value => $mode_name)
								{
									if ($pin_datas['option'] == 'R/W') 	// Palliatif pin gpio16 (or D0)
									{
										if ($mode_value == 'pwm_output') continue;
										if ($mode_value == 'pwm_input') continue;
										if ($mode_value == 'compteur_pullup') continue;
										if ($mode_value == 'input_pullup') continue;
									}
									if (substr($mode_name,0,1)=='i') $InfoPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
									elseif (substr($mode_name,0,1)=='o') $ActionPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
									else $OtherPins[] = '<option value="'.$mode_value.'">{{'.$mode_name.'}}</option>';
								}
                                if (substr($pin_datas['option'], 0, 3) == 'SDA')
                                {
                                    $InfoPins[] = '<option value="bmp180">{{Capteur BMP085/180 Température/Pression}}</option>';
                                    $InfoPins[] = '<option value="bmp280">{{Capteur BMP280 (i2c x76) Température/Pression}}</option>';
                                    $InfoPins[] = '<option value="bme280">{{Capteur BME280 (i2c x76) Température/Humidité/Pression}}</option>';
                                    $InfoPins[] = '<option value="bme680">{{Capteur BME680 (i2c x76) Température/Humidité/Pression/Gas COV}}</option>';
                                }
                                if (substr($pin_datas['option'], 0, 3) == 'SCL')
                                {
                                    $InfoPins[] = '<option value="bmp280b">{{Capteur BMP280 (i2c x77) Température/Pression}}</option>';
                                    $InfoPins[] = '<option value="bme280b">{{Capteur BME280 (i2c x77) Température/Humidité/Pression}}</option>';
                                    $InfoPins[] = '<option value="bme680b">{{Capteur BME680 (i2c x77) Température/Humidité/Pression/Gas COV}}</option>';
                                }
							}
							else
							{
								// pinoche analogique ADC = A0
								$OtherPins[] = '<option value="not_used">{{Non utilisée}}</option>';
								$OtherPins[] = '<option value="analog_input">{{Entrée Analogique}}</option>';
							}
						}
                        else if ($ModeleArduino == 'espsonoffpow')
						{
							if ($pin_datas['option'] != 'HLW8012')
							{
								foreach ($SonoffPow as $mode_value => $mode_name)
								{
                                    $f = substr($mode_name, 0, 1);
                                    $m = '<option value="' . $mode_value . '">{{' . substr($mode_name, 1) . '}}</option>';
                                    switch ($pin_datas['option'])
                                    {
                                        case 'IN':
                                            if ($f == 'i') $InfoPins[] = $m;
                                            elseif ($f == ' ') $OtherPins[] = $m;
                                            break;
                                        case 'OUT':
                                            if ($f == 'o') $ActionPins[] = $m;
                                            elseif ($f == ' ') $OtherPins[] = $m;
                                            break;
                                        default:
                                            if ($f == 'i') $InfoPins[] = $m;
                                            elseif ($f == 'o') $ActionPins[] = $m;
                                            else $OtherPins[] = $m;
                                            break;
                                    }
								}
							}
							else
							{
								//$OtherPins[] = '<option value="not_used">{{Non utilisée}}</option>';
								$OtherPins[] = '<option value="input_numeric">{{Entrée Numérique}}</option>';
							}
						}
                        else if ($ModeleArduino == 'espsonoff4ch' or $ModeleArduino == 'espElectroDragonSPDT')
						{
							foreach ($Sonoff4ch as $mode_value => $mode_name)
                            {
                                $f = substr($mode_name, 0, 1);
                                $m = '<option value="' . $mode_value . '">{{' . substr($mode_name, 1) . '}}</option>';
                                switch ($pin_datas['option'])
                                {
                                    case 'IN':
                                        if ($f == 'i') $InfoPins[] = $m;
                                        elseif ($f == ' ') $OtherPins[] = $m;
                                        break;
                                    case 'OUT':
                                        if ($f == 'o') $ActionPins[] = $m;
                                        elseif ($f == ' ') $OtherPins[] = $m;
                                        break;
                                    default:
                                        if ($f == 'i') $InfoPins[] = $m;
                                        elseif ($f == 'o') $ActionPins[] = $m;
                                        else $OtherPins[] = $m;
                                        break;
                                }
                            }
						}
						else
						{
                            if (strpos($pin_datas['option'], 'SDA') !== false )
                            {
                                $InfoPins[] = '<option value="bmp180">{{Capteur BMP085/180 Température/Pression}}</option>';
                                $InfoPins[] = '<option value="bmp280">{{Capteur BMP280 (i2c x76) Température/Pression}}</option>';
                                $InfoPins[] = '<option value="bme280">{{Capteur BME280 (i2c x76) Température/Humidité/Pression}}</option>';
                                $InfoPins[] = '<option value="bme680">{{Capteur BME680 (i2c x76) Température/Humidité/Pression/Gas COV}}</option>';
                            }
                            if (strpos($pin_datas['option'], 'SCL') !== false )
                            {
                                $InfoPins[] = '<option value="bmp280b">{{Capteur BMP280 (i2c x77) Température/Pression}}</option>';
                                $InfoPins[] = '<option value="bme280b">{{Capteur BME280 (i2c x77) Température/Humidité/Pression}}</option>';
                                $InfoPins[] = '<option value="bme680b">{{Capteur BME680 (i2c x77) Température/Humidité/Pression/Gas COV}}</option>';
                            }
                            if (strpos($pin_datas['option'], 'ANA') === false )
							{
								foreach ($ArduinoMODEpins as $mode_value => $mode_name)
								{
									if (substr($mode_name, 0, 1) == 'i') $InfoPins[] = '<option value="' . $mode_value . '">{{' . substr($mode_name, 1) . '}}</option>';
									elseif (substr($mode_name, 0, 1) == 'o') $ActionPins[] = '<option value="' . $mode_value . '">{{' . substr($mode_name, 1) . '}}</option>';
									else $OtherPins[] = '<option value="' . $mode_value . '">{{' . $mode_name . '}}</option>';
								}
								if (substr($pin_datas['option'], 0, 3) == 'PWM') $ActionPins[] = '<option value="pwm_output">{{Sortie PWM}}</option>';
							}
							else
							{
								// pinoches analogiques
								foreach ($ArduinoESPanalogPins as $mode_value => $mode_name)
								{
									if (substr($mode_name, 0, 1) == 'i') $InfoPins[] = '<option value="' . $mode_value . '">{{' . substr($mode_name, 1) . '}}</option>';
									elseif (substr($mode_name, 0, 1) == 'o') $ActionPins[] = '<option value="' . $mode_value . '">{{' . substr($mode_name, 1) . '}}</option>';
									else $OtherPins[] = '<option value="' . $mode_value . '">{{' . $mode_name . '}}</option>';
								}
							}
						}
					}
					else
					{
						foreach ($UserModePins as $mode_value => $mode_name)
						{
							if (substr($mode_name, 0, 1) == 'i') $InfoPins[] = '<option value="' . $mode_value . '">{{' . substr($mode_name, 1) . '}}</option>';
							elseif (substr($mode_name, 0, 1) == 'o') $ActionPins[] = '<option value="' . $mode_value . '">{{' . substr($mode_name, 1) . '}}</option>';
							else $OtherPins[] = '<option value="'.$mode_value . '">{{' . $mode_name . '}}</option>';
						}
					}
					foreach ($OtherPins as $pins_option) $TmpPins .= $pins_option;
					$options = '';
					foreach ($ActionPins as $pins_option) $options .= $pins_option;
					if ($options != '')
					{
						$TmpPins .= '<optgroup label="{{Sorties Numériques (action)}}">';
						$TmpPins .= $options;
						$TmpPins .= '</optgroup>';
					}
					$options = '';
					foreach ($InfoPins as $pins_option) $options .= $pins_option;
					if ($options != '')
					{
						$TmpPins .= '<optgroup label="{{Entrées Numériques (info)}}">';
						$TmpPins .= $options;
						$TmpPins .= '</optgroup>';
					}
					$TmpPins .= '</select>';
					$TmpPins .= '</td>';

					// Type Générique pour App Mobile
					$G_T = '';
					$InfoPins = $InfoPinsFull;
					$ActionPins = $ActionPinsFull;
					$OtherPins = $OtherPinsFull;

					// cas particuliers
					switch ($ModeleArduino)
                    {
					    case 'piface':
                            if ($pin_datas['option'] != 'IN') $InfoPins = array();
    						else $ActionPins = array();
					        break;
                        case 'espsonoffpow':
                        case 'espsonoff4ch':
						case 'espElectroDragonSPDT':
                            if ($pin_datas['option'] == 'IN') $ActionPins = array();
                            elseif ($pin_datas['option'] == 'HLW8012') $ActionPins = array();
    						elseif ($pin_datas['option'] == 'OUT') $InfoPins = array();
					        break;
					}

					$G_T .= '<td>';
					$G_T .=  '<select class="form-control  configKeyPins" data-l1key="GT_' . $arduino_id . '_' . $pins_id . '">';
					// On essaye de récup les generic_type déja saisis en config avancée
					$generic_type = '0';
					$cmd = $my_arduino->getCmd(null, 'ID' . $pins_id . 'a');
					if (is_object($cmd)) $generic_type = $cmd->getDisplay('generic_type');
					$generic_type = config::byKey('GT_' . $arduino_id . '_' . $pins_id, 'jeedouino', $generic_type);
					if ($generic_type == '') $generic_type = '0';
					config::save('GT_'.$arduino_id . '_' . $pins_id, $generic_type, 'jeedouino'); // pour les équipements sans GT_....

					$OtherPins['configGT'] = "{{Comme la Configuration avancée.}}";
					asort($OtherPins);
					array_unshift($OtherPins, "{{Auto-paramétrage.}}");
					$options = '';
					foreach ($OtherPins as $key => $value) $options .= '<option value="' . $key . '" ' . ($key ==  $generic_type ? ' selected="selected" ':''). '>' . __($value, 'common') . '</option>';
					if ($options != '') $G_T .=  $options;

					asort($ActionPins);
					$options = '';
					foreach ($ActionPins as $key => $value) $options .= '<option value="' . $key . '" ' . ($key ==  $generic_type ? ' selected="selected" ':''). '>' . __($value, 'common') . ' (Action)</option>';
					if ($options != '')
					{
						$G_T .=  '<optgroup label="{{Types Action}}">';
						$G_T .=  $options;
						$G_T .=  '</optgroup>';
					}

					asort($InfoPins);
					$options = '';
					foreach ($InfoPins as $key => $value) $options .= '<option value="' . $key . '" ' . ($key ==  $generic_type ? ' selected="selected" ':''). '>' . __($value, 'common') . ' (Info)</option>';
					if ($options != '')
					{
						$G_T .=  '<optgroup label="{{Types Info}}">';
						$G_T .=  $options;
						$G_T .=  '</optgroup>';
					}
					$G_T .=  '</select>';
					$G_T .=  '</td>';

					$TmpPins .= $G_T;
					// Groupes Virtuels
					$Virtuals = '';
					if (config::byKey('ActiveVirtual', 'jeedouino', false))
					{
						if (method_exists('virtual', 'copyFromEqLogic'))
						{
							$Virtuals .= '<td>';
							$Virtuals .=  '<select class="form-control  configKeyPins" data-l1key="GV_' . $arduino_id . '_' . $pins_id . '">';
							$Virtuals .=  $Virtuels;
							$Virtuals .= '</td>';
						}
						else $Virtuals .= '<td>{{Plugin Virtual non trouvé !}}</td>';
						$TmpPins .= $Virtuals;
					}
                    $TmpPins .= '</tr>';
					// On affecte la ligne pin au bon onglet
					if ($pins_id < $UserPinsBase) $BoardPinsTab .= $TmpPins;
					else $UserPinsTab .= $TmpPins;
				}
                ?>
			<div role="tabpanel" class="tab-pane active" id="boardpinstab">
				<!-- Affichage de l'onglet des pins matérielles de la carte sélectionnée -->
				<table class="table table-bordered table-condensed tablesorter">
					<thead>
					<tr>
						<th>{{Arduino/ESP/RPI Pins}}</th><th>{{Fonctions}}</th><th>{{Type Générique}}</th><?php if (config::byKey('ActiveVirtual', 'jeedouino', false)) echo '<th>{{Groupes Virtuels}}</th>'; ?>
					</tr>
					</thead>
					<tbody>
					<?php echo $BoardPinsTab; ?>
					</tbody>
				</table>
				<div class="col-sm-12">
					<a class="btn btn-success pull-right bt_savebackup_pins" id="bt_savebackup_pins2" title="{{Pensez à sauver l'équipement pour envoyer la config à la carte}}">{{* Sauvegarde}}</a>
				</div>
			</div>
			<?php if ($UserPinsStatus)
			{
			?>
			<div role="tabpanel" class="tab-pane" id="userpinstab">
				<!-- Affichage de l'onglet des pins utilisateur de la carte sélectionnée -->
				<table class="table table-bordered table-condensed tablesorter">
					<thead>
					<tr>
						<th>{{Arduino/ESP/RPI  Pins}}</th><th>{{Fonctions}}</th><th>{{Type Générique}}</th><?php if (config::byKey('ActiveVirtual', 'jeedouino', false)) echo '<th>{{Groupes Virtuels}}</th>'; ?>
					</tr>
					</thead>
					<tbody>
					<?php echo $UserPinsTab; ?>
					</tbody>
				</table>
				<div class="col-sm-12">
					<a class="btn btn-success pull-right bt_savebackup_pins" id="bt_savebackup_pins2" title="{{Pensez à sauver l'équipement pour envoyer la config à la carte}}">{{* Sauvegarde}}</a>
				</div>
			</div>
			<?php
			}
			?>
		</div>
</div>
<script>
// Onglet Options
$(".bt_CptDelay").on('click', function (event) {
	$.ajax({
		type: "POST",
		url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
		data: {
			action: "CptDelay",
			boardid : <?php echo $arduino_id; ?>,
			CptDelay : $('.configKeyPins[data-l1key=<?php echo $arduino_id; ?>_CptDelay]').value()
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function (data) {
		if (data.state != 'ok') {
			$('#div_alert').showAlert({message: data.result, level: 'danger'});
			return;
		}
		$('#div_alert').showAlert({message: '{{La valeur délai de RéArm Event des compteurs a bien été envoyée.}}', level: 'success'});
	}
});
});
$(".bt_bounceDelay").on('click', function (event) {
	$.ajax({
		type: "POST",
		url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
		data: {
			action: "bounceDelay",
			boardid : <?php echo $arduino_id; ?>,
			bounceDelay : $('.configKeyPins[data-l1key=<?php echo $arduino_id; ?>_bounceDelay]').value()
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function (data) {
		if (data.state != 'ok') {
			$('#div_alert').showAlert({message: data.result, level: 'danger'});
			return;
		}
		$('#div_alert').showAlert({message: '{{La valeur délai anti-rebonds des compteurs a bien été envoyée.}}', level: 'success'});
	}
});
});
$(".bt_ProbeDelay").on('click', function (event) {
	$.ajax({
		type: "POST",
		url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
		data: {
			action: "ProbeDelay",
			boardid : <?php echo $arduino_id; ?>,
			ProbeDelay : $('.configKeyPins[data-l1key=<?php echo $arduino_id; ?>_ProbeDelay]').value()
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function (data) {
		if (data.state != 'ok') {
			$('#div_alert').showAlert({message: data.result, level: 'danger'});
			return;
		}
		$('#div_alert').showAlert({message: '{{La valeur délai de relève des sondes a bien été envoyée.}}', level: 'success'});
	}
});
});

	// jeedom.backup_class.js
	$(".bt_savebackup_pins").on('click', function (event) {
		//$.hideAlert();
		jeedom.config.save({
			configuration: $('#backup_pins').getValues('.configKeyPins')[0],
			error: function (error) {
				$('#div_alertpins').showAlert({message: error.message, level: 'danger'});
			},
			success: function () {
				jeedom.config.load({
					configuration: $('#backup_pins').getValues('.configKeyPins')[0],
					plugin: 'jeedouino',
					error: function (error) {
						$('#div_alertpins').showAlert({message: error.message, level: 'danger'});
					},
					success: function (data) {
						$('#backup_pins').setValues(data, '.configKeyPins');
						modifyWithoutSave = false;
						$('#md_modal').dialog('close');
						$('#div_alert').showAlert({message: '{{Paramétrages réussis. /!\ Pensez à sauver l\'équipement ensuite pour envoyer la config à la carte et (re)générer les commandes).}}', level: 'warning'});
					}
				});
			}
		});
		$.ajax({// fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "core/ajax/config.ajax.php", // url du fichier php
			data: {
				action: 'addKey',
				value: json_encode($('#backup_pins').getValues('.configKeyPins')[0]),
				plugin: 'jeedouino',
			},
			dataType: 'json',
			error: function (request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function (data) { // si l'appel a bien fonctionné
			if (data.state != 'ok') {
				$('#div_alertpins').showAlert({message: data.result, level: 'danger'});
				return;
			}
      //			$('#md_modal').dialog('close');
			$('#jqueryLoadingDiv').hide();		// A surveiller, élimine la "roue" qui tourne mais laisse celle lors de la sauvegarde de équipement. bug ??

			modifyWithoutSave = false;
		}
		});
		//$('#md_modal').dialog('close');
		//$('#jqueryLoadingDiv').hide();
	});

	jeedom.config.load({
		configuration: $('#backup_pins').getValues('.configKeyPins')[0],
		plugin: 'jeedouino',
		error: function (error) {
			$('#div_alertpins').showAlert({message: error.message, level: 'danger'});
		},
		success: function (data) {
			$('#backup_pins').setValues(data, '.configKeyPins');
			modifyWithoutSave = false;
			//$('#div_alertpins').showAlert({message: '{{Sauvegarde réussie}}', level: 'success'});
		}
	});

	$("#bt_adduser_pins").on('click', function (event) {
		var tr = $('#user_cmd tbody tr:first');
	});
	$('body').undelegate('#backup_pins .cmdAction[data-action=remove]', 'click').delegate('#backup_pins .cmdAction[data-action=remove]', 'click', function () {
		$(this).closest('tr').hide();
	});
   initTableSorter();
</script>
<?php
	}
	else
	{
		?>
			<script>$('#jqueryLoadingDiv').hide();$('#md_modal').dialog('close');	</script>
		<div class="alert alert-danger">
			<center><h4>{{Pré-requis}}</h4>
			{{Veuillez finir de configurer l'équipement et le sauvegarder avant de pouvoir configurer les pins de celui-ci}}
			</center>
		</div>
		<?php
	}
}
else echo __(" !!! Il y a eu un problème. Veuillez re-sauvegarder l'équipement puis réessayer.", __FILE__);
?>

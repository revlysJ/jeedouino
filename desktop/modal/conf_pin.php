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
    throw new Exception('{{401 - Accès non autorisé}}');
}
if (isset($_GET['id']))
{
	$arduino_board = '';
	if (isset($_GET['board'])) $arduino_board = trim($_GET['board']);
	
	include_file('core', 'jeedouino', 'config', 'jeedouino');
    global $ArduinoMODEpins,$Arduino328pins,$ArduinoMEGApins,$ArduinoESPanalogPins;
    global $PifaceMODEpinsIN,$PifaceMODEpinsOUT,$Pifacepins;
    global $PiGPIOpins,$PiGPIO26pins,$PiGPIO40pins;
	global $ESP8266pins,$ESP01pins,$ESP07pins,$espMCU01pins;
	global $PiPluspins,$PiPlus16pins;
	global $UserModePins;
    
	$arduino_id = $_GET['id'];
	$my_arduino = eqLogic::byid($arduino_id);		
	$ModeleArduino = $my_arduino->getConfiguration('arduino_board'); 
	$PortArduino = $my_arduino->getConfiguration('datasource'); 
	$LocalArduino = $my_arduino->getConfiguration('arduinoport'); 
	$IPArduino = $my_arduino->getConfiguration('iparduino'); 	
	
	// On verifie que l'utilisateur n'a pas changé de modèle de carte après la 1ère sauvegarde sans resauver derrière.
	if ($arduino_board != '')
	{
		if ($arduino_board != $ModeleArduino) $ModeleArduino = $arduino_board;
	}
	$non_defini=true;
	if ($ModeleArduino != '')
	{

		if ($PortArduino=='rj45arduino') $message_a=' réseau sur IP : '.$IPArduino.'. ';
		elseif ($PortArduino=='usbarduino')
		{
			$message_a=' USB ';
			if ($LocalArduino=='usblocal') $message_a.=' sur port local. ';
			elseif ($LocalArduino=='usbdeporte') $message_a.=' sur port déporté. ';
			else 
			{
				$non_defini=false;
				$message_a.=' NON DEFINI ! ';
			}
		}
		else 
		{
			$non_defini=false;
			$message_a=' NON DEFINI ! ';
		}
	}
	else 
	{
		$non_defini=false;
		$message_a=' NON DEFINI ! ';
	}
	
	
	if ($non_defini)
	{
		// Pins utilisateur
		$UserPinsBase = 500;
		$UserPinsStatus = false;
		$user_pins = array();
		if (config::byKey('ActiveUserCmd', 'jeedouino', false))
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
			<div class="form-group"  style="    ">	
				<label class="col-sm-4 control-label ">Paramétrage des pins de l'arduino/esp/rpi <?php echo $message_a; ?></label>
				<div class="col-sm-8">
					<a href="https://jeedom.github.io/documentation/third_plugin/jeedouino/fr_FR/index.html#_sketchs_personnels_modifiables_et_commandes_utilisateur" target="_blank" class="btn btn-info eqLogicAction pull-right"  title="{{Lien vers la Documentation du plugin}}"><i class="fa fa-book"></i> </a>
					<a class="btn btn-success pull-right bt_savebackup_pins" id="bt_savebackup_pins1" title="Pensez à sauver l'équipement pour envoyer la config à la carte">* Sauvegarde</a>
				</div>
			</div>	
		
		<ul class="nav nav-tabs" role="tablist">
				<?php if (substr($ModeleArduino,0,2)!='pi' or (substr($ModeleArduino,0,6)=='piGPIO') or ($ModeleArduino == 'piPlus'))
				{
				?>	
			<li role="presentation"><a href="#optionstab" aria-controls="profile" role="tab" data-toggle="tab"  id="bt_conf_Pin"><i class="fa fa-wrench"></i> {{Options}}</a></li>
				<?php
				}
				?>
			<li role="presentation" class="active"><a href="#boardpinstab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Pins Matérielles}}</a></li>
				<?php if ($UserPinsStatus)
				{
				?>	
			<li role="presentation"><a href="#userpinstab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-code"></i> {{Pins Utilisateur}}</a></li>
				<?php
				}
				?>
		</ul>

		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<?php if (substr($ModeleArduino,0,2)!='pi' or (substr($ModeleArduino,0,6)=='piGPIO') or ($ModeleArduino == 'piPlus'))
			{
				$_ProbeDelay = '
				<div class="form-group">
					<label class="col-sm-4 control-label "><p class="hidden-xs"><br/>{{Délai de renvoi des valeurs des sondes T°/H en minutes. <br>(En tests)}}</p></label>
					<div class="col-sm-8">
						<input type="number" class="form-control  configKeyPins" data-l1key="' . $arduino_id . '_ProbeDelay"  placeholder="Délai sondes en Minutes : 1 à 1000 max." min="1" max="1000"/> 
					</div>
				</div>';
			?>
			<div role="tabpanel" class="tab-pane" id="optionstab">
				<?php if (substr($ModeleArduino,0,2)!='pi')
				{
				?>
                <div class="form-group">	 
                    <label class="col-sm-4 control-label "><p class="hidden-xs"><br/>{{Choix de sauvegarde de l'état des pins suite a un redémarrage de l'Arduino/esp (Coupure de courant, reset,etc...)}}</p></label>
                    <div class="col-sm-8">
						<?php
							if (config::byKey($arduino_id.'_choix_boot', 'jeedouino', 'none')!='none') $message_a='';
							else $message_a=' selected ';
							
							echo '<br><br>';
							echo '<select class="form-control  configKeyPins" data-l1key="'.$arduino_id.'_choix_boot">';
							echo '<option value="0">{{Pas de sauvegarde - Toutes les pins sorties non modifiées au démarrage.}}</option>';
							echo '<option value="1">{{Pas de sauvegarde - Toutes les pins sorties mises à LOW au démarrage.}}</option>';
							echo '<option value="2">{{Pas de sauvegarde - Toutes les pins sorties mises à HIGH au démarrage.}}</option>';
							echo '<option value="3" class="text-success"'.$message_a.'>{{Sauvegarde sur JEEDOM - Toutes les pins sorties mises suivant leur sauvegarde dans Jeedom. Lent, Jeedom requis sinon pins mises à HIGH.}}</option>';
							echo '<option value="5" class="text-success">{{Sauvegarde sur JEEDOM - Toutes les pins sorties mises suivant leur sauvegarde dans Jeedom. Lent, Jeedom requis sinon pins mises à LOW.}}</option>';
							echo '<option value="4" class="text-danger">{{Sauvegarde sur EEPROM- Toutes les pins sorties mises suivant leur sauvegarde dans l\'EEPROM. Autonome, rapide mais durée de vie de l\'eeprom fortement réduite.}}</option>';

							echo '</select>';
						 ?>
					<br>
                    </div>
                </div>		
				<?php
					if ($PortArduino != 'usbarduino') echo $_ProbeDelay;
				}
				elseif (substr($ModeleArduino,0,6)=='piGPIO')
				{
				?>
                <div class="form-group">	 
                    <label class="col-sm-4 control-label "><p class="hidden-xs"><br/>{{Choix de l'état des pins sorties au démarrage du démon piGPIO. (En tests)}}</p></label>
                    <div class="col-sm-8">
						<?php
							echo '<br><br>';
							echo '<select class="form-control  configKeyPins" data-l1key="'.$arduino_id.'_PiGpio_boot">';
							echo '<option value="0">{{Toutes les pins sorties mises à LOW au démarrage du démon}}</option>';
							echo '<option value="1">{{Toutes les pins sorties mises à HIGH au démarrage du démon}}</option>';
							echo '</select>';
						 ?>
					<br>
                    </div>
                </div>		
				<?php
					echo $_ProbeDelay;
				}
				elseif ($ModeleArduino == 'piPlus')
				{
				?>
                <div class="form-group">	 
                    <label class="col-sm-4 control-label "><p class="hidden-xs"><br/>{{Choix de l'état des pins sorties au démarrage du démon piPlus. (En tests)}}</p></label>
                    <div class="col-sm-8">
						<?php
							echo '<br><br>';
							echo '<select class="form-control  configKeyPins" data-l1key="'.$arduino_id.'_PiPlus_boot">';
							echo '<option value="0">{{Toutes les pins sorties mises à LOW au démarrage du démon}}</option>';
							echo '<option value="1">{{Toutes les pins sorties mises à HIGH au démarrage du démon}}</option>';
							echo '</select>';
						 ?>
					<br>
                    </div>
                </div>		
				<?php
				}
				echo '</div>'; // end tabpanel
			}
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
                    case 'auno':
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
					default:	
						$Arduino_pins = '';
					break;
                }
				// On créer la liste des virtuels communes a tous
				if (method_exists('virtual', 'copyFromEqLogic'))
				{
					$Virtuels = '';
					foreach (object::all() as $object) 
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

				// On traite les pins
				foreach ($Arduino_pins as $pins_id => $pin_datas) 
				{
					$TmpPins = '<tr class="pinoche" data-logicalId="'.$pins_id.'">';
					if ($pin_datas['option']!='') $TmpPins .= '<td>'.$pin_datas['Nom_pin'].' - ( '.$pin_datas['option'].' ) </td>';
					else $TmpPins .= '<td>'.$pin_datas['Nom_pin'].'</td>';

					// pins non disponibles
					if ($pin_datas['disable']=='1')
					{
						$TmpPins .= '<td><input disabled class="form-control configKeyPins" name="'.$arduino_id.'_'.$pins_id.'" value="{{Pin réservée !}}"></td>';
						if ($pins_id < $UserPinsBase) $BoardPinsTab .= $TmpPins;
						else $UserPinsTab .= $TmpPins;
						continue;
					}
					// pins reservee pour la carte ethernet sur arduino
					if (($PortArduino=='rj45arduino') and ($pin_datas['ethernet']=='1')) 
					{
						$TmpPins .= '<td><input disabled class="form-control configKeyPins" name="'.$arduino_id.'_'.$pins_id.'" value="{{Pin réservée pour le shield ethernet !}}"></td>';    
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
						if ($ModeleArduino=='piface')
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
						else if ($ModeleArduino=='piGPIO26' or $ModeleArduino=='piGPIO40' )
						{              
							foreach ($PiGPIOpins as $mode_value => $mode_name) 
							{
								if (substr($mode_name,0,1)=='i') $InfoPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
								elseif (substr($mode_name,0,1)=='o') $ActionPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
								else $OtherPins[] = '<option value="'.$mode_value.'">{{'.$mode_name.'}}</option>';
							}
						}
						else if ($ModeleArduino=='piPlus')
						{
							foreach ($PiPluspins as $mode_value => $mode_name) 
							{
								if (substr($mode_name,0,1)=='i') $InfoPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
								elseif (substr($mode_name,0,1)=='o') $ActionPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
								else $OtherPins[] = '<option value="'.$mode_value.'">{{'.$mode_name.'}}</option>';
							}                      
						}					
						else if ($ModeleArduino=='esp01' or $ModeleArduino=='esp07' or $ModeleArduino=='espMCU01' )
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
							}
							else
							{
								// pinoche analogique ADC = A0
								$OtherPins[] = '<option value="not_used">{{Non utilisée}}</option>';
								$OtherPins[] = '<option value="analog_input">{{Entrée Analogique}}</option>';			
							}							
						}   
						else
						{
							if ($pin_datas['option']!='ANA')
							{
								foreach ($ArduinoMODEpins as $mode_value => $mode_name) 
								{							
									if (substr($mode_name,0,1)=='i') $InfoPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
									elseif (substr($mode_name,0,1)=='o') $ActionPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
									else $OtherPins[] = '<option value="'.$mode_value.'">{{'.$mode_name.'}}</option>';						
								}
								if (substr($pin_datas['option'],0,3)=='PWM') $ActionPins[] ='<option value="pwm_output">{{Sortie PWM}}</option>';
							}
							else
							{
								// pinoches analogiques
	/* 							$OtherPins[] = '<option value="not_used">{{Non utilisée}}</option>';
								$OtherPins[] = '<option value="analog_input">{{Entrée Analogique}}</option>';
								$ActionPins[] = '<option value="output">{{Sortie Numérique}}</option>';		 */
								foreach ($ArduinoESPanalogPins as $mode_value => $mode_name) 
								{							
									if (substr($mode_name,0,1)=='i') $InfoPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
									elseif (substr($mode_name,0,1)=='o') $ActionPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
									else $OtherPins[] = '<option value="'.$mode_value.'">{{'.$mode_name.'}}</option>';						
								}							
							}
						}
					}
					else
					{
						foreach ($UserModePins as $mode_value => $mode_name) 
						{
							if (substr($mode_name,0,1)=='i') $InfoPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
							elseif (substr($mode_name,0,1)=='o') $ActionPins[] = '<option value="'.$mode_value.'">{{'.substr($mode_name,1).'}}</option>';
							else $OtherPins[] = '<option value="'.$mode_value.'">{{'.$mode_name.'}}</option>';
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
					$TmpPins .= '<span class="HC_trigger hide"><i class="fa fa-arrow-right"></i> {{Pensez à selectionner la pin $TmpPins .=.}}</span>';
					$TmpPins .= '</td>';
					
					// Type Générique pour App Mobile
					$G_T = '';
					$InfoPins = array();
					$ActionPins = array();
					$OtherPins = array();		
					
					foreach (jeedom::getConfiguration('cmd::generic_type') as $key => $value)
					{		
						$Gvalue=strtolower($value['type']);
						if ($Gvalue=='info') $InfoPins[$key] = $value['name'];
						elseif ($Gvalue=='action') $ActionPins[$key] = $value['name'];
						else $OtherPins[$key] = $value['name'];
					}
					// cas particulier
					if ($ModeleArduino=='piface')
                    {
                        if ($pin_datas['option']!='IN') $InfoPins = array();
						else $ActionPins = array();
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
						<th>{{Arduino/ESP/RPI  Pins}}</th><th>{{Fonctions}}</th><th>{{Type Générique}}</th><?php if (config::byKey('ActiveVirtual', 'jeedouino', false)) echo '<th>{{Groupes Virtuels}}</th>'; ?>
					</tr>
					</thead>
					<tbody>
					<?php echo $BoardPinsTab; ?>
					</tbody>
				</table>
				<div class="col-sm-12">
					<a class="btn btn-success pull-right bt_savebackup_pins" id="bt_savebackup_pins2" title="Pensez à sauver l'équipement pour envoyer la config à la carte">* Sauvegarde</a>
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
					<a class="btn btn-success pull-right bt_savebackup_pins" id="bt_savebackup_pins2" title="Pensez à sauver l'équipement pour envoyer la config à la carte">* Sauvegarde</a>
				</div>
			</div>
			<?php
			}
			?>
		</div>
</div>
<script>
	$('.configKeyPins').on('change',function(){
		if ($(this).value()=='trigger')
		{
			$('.HC_trigger').show();
		}
		else
		{
			$('.HC_trigger').hide();
		}
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
else echo " !!! Il y a eu un problème. Veuillez re-sauvegarder l'équipement puis réessayer.";
?>
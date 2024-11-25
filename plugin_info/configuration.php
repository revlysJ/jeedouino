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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
	include_file('desktop', '404', 'php');
	die();
}
$eqLogics = eqLogic::byType('jeedouino');

$cpl = jeedouino::GetJeedomComplement();
$ip = jeedouino::GetJeedomIP();
$port =  jeedouino::GetJeedomPort();
?>
<div class="form-group">
	<label class="col-lg-5 control-label">{{Raccourcis}}</label>
	<div class="col-lg-5">

		<a class="btn btn-warning " href="<?php echo $cpl; ?>/index.php?v=d&m=jeedouino&p=jeedouino">
			<img class="img-responsive" style="width : 20px;display:inline-block;" src="plugins/jeedouino/icons/jeedouino_icon.png"> {{Jeedouino Plugin}}
		</a>
		<a class="btn btn-warning " href="<?php echo $cpl; ?>/index.php?v=d&p=log&logfile=jeedouino">
			<img class="img-responsive" style="width : 20px;display:inline-block;" src="plugins/jeedouino/icons/jeedouino_icon.png"> {{Jeedouino Logs}}
		</a>
	</div>

	<br><br>
</div>
<ul class="nav nav-tabs" id="tab_jeedouino">
	<li class="active"><a href="#tab_logs"><i class="fas fa-pencil-square-o"></i> {{Options}}</a></li>
	<li><a href="#tab_dep"><i class="fas fa-certificate"></i> {{Dépendances}}</a></li>
	<li><a href="#tab_demon"><i class="fas fa-university"></i> {{Démons}}</a></li>
	<li><a href="#tab_sketch"><i class="fas fa-download"></i> {{Sketchs}}</a></li>
	<li><a href="#tab_docker"><i class="fas fa-rss"></i> {{Conf. Docker}}</a></li>
</ul>
<div class="tab-content">
	<div class="tab-pane active" id="tab_logs">
		<br />
		<form class="form-horizontal">
			<fieldset>
				<div class="form-group">
					<label class="col-lg-7 control-label">{{Activer les logs}} <i class="fas fa-question-circle tooltips" title="{{Permet de surveiller le fonctionnement du plugin}}"></i></label>
					<div class="col-lg-3">
						<input type="checkbox" class="configKey " data-l1key="ActiveLog" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-7 control-label">{{Activer les logs séparés des démons locaux}} <i class="fas fa-question-circle tooltips" title="{{Nécéssite un redémarrage des démons}}"></i></label>
					<div class="col-lg-3">
						<input type="checkbox" class="configKey " data-l1key="ActiveDemonLog" />
					</div>
				</div>
				<div class="alert alert-info"><a href="<?php echo $cpl; ?>/index.php?v=d&p=administration#logtab"><i class="fas fa-arrow-right"></i> {{ N.B. Pensez aussi a activer les logs de niveau debug dans Jeedom.}} </a></div>
				<div class="form-group">
					<label class="col-lg-7 control-label">{{Activer l'affichage du menu gauche}} <i class="fas fa-question-circle tooltips" title="{{Permet d'afficher le menu de gauche du plugin comme précédemment}}"></i></label>
					<div class="col-lg-3">
						<input type="checkbox" class="configKey " data-l1key="ShowSideBar" />
					</div>
				</div>
				<?php if (method_exists('virtual', 'copyFromEqLogic')) {
				?>
					<div class="form-group">
						<label class="col-lg-7 control-label">{{Activer les groupes virtuels}} <i class="fas fa-question-circle tooltips" title="{{Permet de séparer les commandes d'un même équipement dans plusieurs virtuels}}"></i></label>
						<div class="col-lg-3">
							<input type="checkbox" class="configKey " data-l1key="ActiveVirtual" />
						</div>
					</div>
				<?php } ?>
				<div class="form-group">
					<label class="col-lg-7 control-label">{{Activer les commandes utilisateur (Sketchs persos - Arduinos/Esp8266/NodeMcu/Wemos...)}} <i class="fas fa-question-circle tooltips" title="{{Permet d'ajouter du code/commandes dans le(s) sketch(s) }}"></i></label>
					<div class="col-lg-3">
						<input type="checkbox" class="configKey" data-l1key="ActiveUserCmd" name="ActiveUserCmd" />
					</div>
					</br></br>
				</div>
				<div class="alert alert-warning"><i class="fas fa-code"></i>{{ Uniquement pour utilisateurs avancés. /!\ Option en version BETA pour TESTS seulement. /!\}}
					<div class="form-group">
						<label class="col-lg-7 control-label">{{Activer l'option JeedouinoExt}} <i class="fas fa-question-circle tooltips" title="{{Permet d'utiliser les fonctions du plugin sur un RaspberryPi n'ayant pas Jeedom }}"></i></label>
						<div class="col-lg-3">
							<input type="checkbox" class="configKey " data-l1key="ActiveExt" />
						</div>
					</div>
				</div>
			</fieldset>
		</form>
	</div>

	<div class="tab-pane" id="tab_dep">
		<br />
		<?php
		$dep = '';
		if (@file_exists('/tmp/dependances_jeedouino_en_cours')) $dep = trim(file_get_contents('/tmp/dependances_jeedouino_en_cours'));
		if (count(system::ps('dpkg')) > 0 || count(system::ps('apt')) > 0 || $dep != '') { ?>
			<div class="alert alert-danger"><i class="fas fa-arrow-right"></i> {{ Il y a déjà une installation en cours. Veuillez patienter et la suivre via le log.}} </div>
			<div class="alert alert-info"><i class="fas fa-cogs"></i> {{ Important : <br> L'installation peut prendre pas mal de temps, il faut être patient.<br>
    Cependant, si elle vous semble bloquée, elle nécessite peut-être une intervention manuelle de votre part.<br>
    Dans ce cas, il faudra rebooter puis via ssh procéder aux commandes manuelles suivantes:}}<br>
				<code>sudo dpkg --configure -a --force-confdef</code><br>
				<code>sudo apt -y --fix-broken install</code><br>
			</div>
		<?php } else { ?>
			<div class="alert alert-warning"><i class="fas fa-arrow-right"></i> {{ A n'installer que si nécéssaire.}} </div>
			<form class="form-horizontal">
				<fieldset>
					<table class="table table-bordered table-striped">
						<thead>
							<tr class="info">
								<th>{{Dépendances spécifiques}}</th>
								<th>{{Matériel support}}</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									<div class="form-group">
										<label class="col-lg-5 control-label">{{Télécharger l'Arduino IDE}}</label>
										<div class="col-lg-5">
											<a href="https://www.arduino.cc/en/Main/Software" target="_blank" class="btn btn-success"><i class='fas fa-floppy-o'></i>{{ www.arduino.CC }}</a>
										</div>
									</div>
								</td>
								<td>{{Arduinos}}</td>
							</tr>
							<tr class="info">
								<td colspan=2>{{Dépendances spécifiques Raspberry PI}}</td>
							</tr>
							<tr>
								<td>
									<div class="form-group">
										<label class="col-lg-5 control-label">{{Pifacedigitalio Installation}}</label>
										<div class="col-lg-5">
											<a class="btn btn-info bt_installPIFACE"><i class="fas fa-play"></i> {{sudo install}}</a>
										</div>
									</div>
								</td>
								<td>{{Raspberry PI avec carte(s) PiFace}}</td>
							</tr>
							<tr>
								<td>
									<div class="form-group">
										<label class="col-lg-5 control-label">{{IO.PiPlus smbus Installation}}</label>
										<div class="col-lg-5">
											<a class="btn btn-info bt_installPiPlus"><i class="fas fa-play"></i> {{sudo install}}</a>
										</div>
									</div>
								</td>
								<td>{{Raspberry PI avec carte(s) Pi.Plus ou MCP23017 (I2C)}}</td>
							</tr>
						</tbody>
					</table>



					<div class="form-group">
						<label class="col-lg-5 control-label">{{Dépendance : Mise à Jour Système}}</label>
						<div class="col-lg-5">
							<a class="btn btn-warning bt_installUpdate"><i class="fas fa-play"></i> {{ sudo apt-get update / upgrade / dist-upgrade}}</a>
						</div>
					</div>




				</fieldset>
			</form>
		<?php } ?>
	</div>

	<div class="tab-pane" id="tab_demon">
		<br />
		<?php $html = '
        <div class="alert alert-primary"><i class="fas fa-university"></i> {{ Gestion des équipements avec Démons.}} </div>
		<div class="alert alert-warning"><i class="fas fa-arrow-right"></i> {{ N.B. Suite a un reboot, les démons démarrent automatiquement >4 min après Jeedom. Cf doc.}}
            </br></br>
            <div class="form-group">
                <label class="col-lg-3 control-label">{{Choisir un autre délai (min)}}</label>
                <div class="col-lg-2">
                    <input type="text" class="configKey form-control"  data-l1key="BootTime" placeholder="ex : 4 min"/>
                </div>
            </div>
            </br></br>
        </div>

		<form class="form-horizontal">
			<fieldset>
			<div class="form-group" >

		<table class="table table-bordered table-striped">
		<thead>
			<tr>
				<th>{{Hôte}}</th>
				<th>{{Emplacement}}</th>
				<th>{{Equipement}}</th>
				<th>{{Statut}}</th>
				<th>{{(Re)Démarrer}}</th>
				<th>{{Arrêter}}</th>
				<th>{{AutoReStart}}</th>
				<th>{{Type}}</th>
				<th>{{Sketchs}}</th>
			</tr>
		</thead>
		<tbody>';
		$hasDemons = false;
		$Arduino_reseaux = '';
		$CronStepArr = config::byKey('CronStepArr', 'jeedouino', '');
		foreach ($eqLogics as $eqLogic) {
			if ($eqLogic->getIsEnable() == 0) continue;
			if ($eqLogic->getLogicalId() == 'JeedouinoControl') continue;
			$name = $eqLogic->getName(true);
			$board_id = $eqLogic->getId();
			$ModeleArduino = $eqLogic->getConfiguration('arduino_board');
			$Sketch = 'Sketch pour ';
			$StatusDemon = false;
			$jsButton = 'ArduinoUsb';
			if ($eqLogic->getConfiguration('datasource') == 'usbarduino') $a_usb = true;
			else  $a_usb = false;
			$a_lan = true;
			$esp = false;
			switch ($ModeleArduino) {
				case 'auno':
					$Sketch  .= 'Uno 328';
					if ($a_usb) $a_lan = false;
					break;
				case 'a2009':
					$Sketch  .= 'Duemillanove 328';
					if ($a_usb) $a_lan = false;
					break;
				case 'anano':
					$Sketch .= 'Nano 328';
					if ($a_usb) $a_lan = false;
					break;
				case 'a1280':
					$Sketch  .= 'Mega 1280';
					if ($a_usb) $a_lan = false;
					break;
				case 'a2560':
					$Sketch  .= 'Mega 2560';
					if ($a_usb) $a_lan = false;
					break;
				case 'esp01':
					$Sketch  .= 'ESP8266-01';
					$a_lan = true;
					$esp = true;
					break;
				case 'esp07':
					$Sketch  .= 'ESP8266-07';
					$a_lan = true;
					$esp = true;
					break;
				case 'espMCU01':
					$Sketch  .= 'NodeMCU';
					$a_lan = true;
					$esp = true;
					break;
				case 'espsonoffpow':
					$Sketch  .= 'SONOFF-POW';
					$a_lan = true;
					$esp = true;
					break;
				case 'espsonoff4ch':
					$Sketch  .= 'SONOFF-4CH';
					$a_lan = true;
					$esp = true;
					break;
				case 'espElectroDragonSPDT':
					$Sketch  .= 'ElectroDragon-2CH';
					$a_lan = true;
					$esp = true;
					break;
				case 'esp32dev':
					$Sketch  .= 'ESP32Dev';
					$a_lan = true;
					$esp = true;
					break;
				case 'piface':
					$a_lan = false;
					$Sketch  = '';
					$jsButton = 'PiFace';
					$StatusDemon = jeedouino::StatusBoardDaemon($board_id, 0, $ModeleArduino);
					break;
				case 'piGPIO26':
				case 'piGPIO40':
					$a_lan = false;
					$Sketch  = '';
					$jsButton = 'PiGpio';
					$StatusDemon = jeedouino::StatusBoardDaemon($board_id, 0, $ModeleArduino);
					break;
				case 'piPlus':
					$a_lan = false;
					$Sketch  = '';
					$jsButton = 'PiPlus';
					$StatusDemon = jeedouino::StatusBoardDaemon($board_id, 0, $ModeleArduino);
					break;
				default:
					$a_lan = true;
					$Sketch  = '';
					$jsButton = '';
					break;
			}
			if (!$a_lan) {
				$hasDemons = true;
				$board_ip = $eqLogic->getConfiguration('iparduino');
				$jeedomMasterIP = jeedouino::GetJeedomIP();

				if ($jeedomMasterIP == $board_ip or $board_ip == '127.0.0.1') $localDemon = true;
				else $localDemon = false;

				if (($a_usb) and (substr($ModeleArduino, 0, 1) == 'a')) $StatusDemon = jeedouino::StatusBoardDaemon($board_id, 0, $ModeleArduino);
				config::save($board_id . '_StatusDemon', $StatusDemon, 'jeedouino');
				config::save($board_id . '_HasDemon', 1, 'jeedouino');

				if (config::byKey('Auto_' . $board_id, 'jeedouino', 'none') == 'none') config::save('Auto_' . $board_id, 0, 'jeedouino');

				$html .= '<tr><td>';

				if ($localDemon) $html .= '{{Jeedouino}}';
				else {
					if ($board_ip != '')  $html .= trim(config::byKey('JExtname-' . $board_ip, 'jeedouino', 'JeedouinoExt'));
					else $html .=  '{{Equipement mal configuré}}';
				}
				$html .= '</td><td>';
				if ($localDemon) $html .= '{{Local}}';
				else {
					if ($board_ip != '') {
						$_path = trim(config::byKey('path-' . $board_ip, 'jeedouino', ''));
						if ($_path == '') $_path = '/';
						$_port = trim(config::byKey('PORT-' . $board_ip, 'jeedouino', ''));
						if ($_port == '') $_port = '80';
						$html .= '<a href="http://' . $board_ip . ':' . $_port . $_path . 'JeedouinoExt.php" target="_blank"><i class="fas fa-home"></i> {{ sur ' . $board_ip . '}}</a>';
					} else $html .=  '{{EqID' . $board_id . '}}';
				}
				$html .= '</td><td>';
				$html .= '<div class="col-lg-7"><a class="btn btn-default " title="EqID : ' . $board_id . '" href=" index.php?&v=d&p=jeedouino&m=jeedouino&id=' . $board_id . '" target="_blank"><i class="fas fa-sitemap"></i> ' . $name . '</a></div>';
				$html .= '</td><td class="deamonState">';

				if ($StatusDemon) $html .= '<span class="btn btn-success" >OK</span>';
				else {
					if (($CronStepArr != '') and (in_array($board_id, $CronStepArr))) $html .= '<span class="btn btn-warning " ><i class="fas fa-play"></i> 4min</span>';
					else $html .= '<span class="btn btn-danger " >NOK</span>';
				}
				$html .= '</td><td>';
				if ($StatusDemon) $html .= '<a class="btn btn-success bt_restartDemon" slaveID="0" boardID="' . $board_id . '" DemonType="' . $jsButton . '"><i class="fas fa-sync"></i></a>';
				else $html .= '<a class="btn btn-success bt_StartDemon" slaveID="0" boardID="' . $board_id . '" DemonType="' . $jsButton . '"><i class="fas fa-play"></i></a>';
				$html .= '</td><td>';
				if ($StatusDemon) $html .= '<a class="btn btn-danger bt_stopDemon" slaveID="0" boardID="' . $board_id . '" DemonType="' . $jsButton . '"><i class="fas fa-stop"></i></a>';
				$html .= '</td><td>';
				$html .= '<label class="checkbox-inline"><input type="checkbox" class="configKey " data-l1key="Auto_' . $board_id . '" /><i class="fas fa-redo"></i> {{5min}}</label>';
				$html .= '</td><td>';
				$html .= $jsButton;
				$html .= '</td><td>';
				if ($Sketch != '') {
					$jeedouinoPATH = realpath(dirname(__FILE__) . '/../sketchs/');
					$SketchFileName = $jeedouinoPATH . '/JeedouinoUSB_' . $board_id . '.ino';
					if (file_exists($SketchFileName)) {
						$html .= '<div class="col-lg-5"><a href="plugins/jeedouino/sketchs/JeedouinoUSB_' . $board_id . '.ino" class="btn btn-info" download target="_blank" ><i class="fas fa-download"></i>{{ SketchUSB ( EqID : ' . $board_id . ' ) }}</a></div>';
						$Arduino_reseaux .= '<div class="form-group">
						<label class="col-lg-4 control-label">{{ ' . $Sketch . ' ( USB )' . '}}</label>
						<div class="col-lg-3"><a class="btn btn-default " href=" index.php?&v=d&p=jeedouino&m=jeedouino&id=' . $board_id . '" target="_blank" ><i class="fab fa-usb"></i> ' . $name . '</a></div>
						<div class="col-lg-5">
							<a href="core/php/downloadFile.php?pathfile=plugins/jeedouino/sketchs/JeedouinoUSB_' . $board_id . '.ino" class="btn btn-info" download target="_blank" ><i class="fas fa-download"></i>{{ Télécharger le Sketch a mettre dans l\'arduino ( EqID : ' . $board_id . ' ) }}</a>
						</div></div>';
					} else {
						$html .= '<div class="col-lg-5"><a href="core/php/downloadFile.php?pathfile=plugins/jeedouino/sketchs/JeedouinoUSB.ino" class="btn btn-info"  title="{{ Télécharger le Sketch à mettre dans l\'arduino }}" download target="_blank"><i class="fas fa-download"></i> SketchUSB</a></div>';
					}
				}
				$html .= '</td></tr>';
			} else {
				config::save($board_id . '_HasDemon', 0, 'jeedouino');
				//jeedouino::log( 'debug',$board_id.'_HasDemon ==  '.config::byKey($board_id.'_HasDemon', 'jeedouino', 3));
				//jeedouino::log( 'debug',$board_id.'_StatusDemon ==  '.config::byKey($board_id.'_StatusDemon', 'jeedouino', 3));
				//<div class="col-lg-7"><a class="btn btn-default " href=" index.php?&v=d&p=jeedouino&m=jeedouino&id='.$board_id.'"><i class="fas fa-sitemap"></i> '.$name.'</a></div>
				if ($esp) {
					$Arduino_reseaux .= '<div class="form-group">
					<label class="col-lg-4 control-label">{{ ' . $Sketch . ' ( WIFI )' . '}}</label>
					<div class="col-lg-3"><a class="btn btn-default " href=" index.php?&v=d&p=jeedouino&m=jeedouino&id=' . $board_id . '" target="_blank" ><i class="fas fa-wifi"></i> ' . $name . '</a></div>
					<div class="col-lg-5">
						<a href="core/php/downloadFile.php?pathfile=plugins/jeedouino/sketchs/JeedouinoESP_' . $board_id . '.ino" class="btn btn-info" download target="_blank" ><i class="fas fa-download"></i>{{ Télécharger le Sketch a mettre dans l\'esp ( EqID : ' . $board_id . ' ) }}</a>
					</div></div>';
				} else {
					$Arduino_reseaux .= '<div class="form-group">
					<label class="col-lg-4 control-label">{{ ' . $Sketch . ' ( LAN )' . '}}</label>
					<div class="col-lg-3"><a class="btn btn-default " href=" index.php?&v=d&p=jeedouino&m=jeedouino&id=' . $board_id . '" target="_blank" ><i class="fas fa-sitemap"></i> ' . $name . '</a></div>
					<div class="col-lg-5">
						<a href="core/php/downloadFile.php?pathfile=plugins/jeedouino/sketchs/JeedouinoLAN_' . $board_id . '.ino" class="btn btn-info" download target="_blank" ><i class="fas fa-download"></i>{{ Télécharger le Sketch a mettre dans l\'arduino ( EqID : ' . $board_id . ' ) }}</a>
					</div></div>';
				}
			}
		}
		$html .= '</tbody></table>';
		if ($Arduino_reseaux != '') {
			$Arduino_reseaux .= '<div class="form-group">
							<label class="col-lg-5 control-label">{{ Librairies pour vos Sketchs }}</label>
							<div class="col-lg-5">
								<a href="core/php/downloadFile.php?pathfile=plugins/jeedouino/sketchs/ArduinoLibraries.zip" class="btn btn-warning" target="_blank"  download><i class="fas fa-download"></i>{{ Télécharger les librairies Arduinos/ESP }}</a>
							</div></div>';
		}
		$html .= '
			</div>
			</fieldset>
		</form>';

		if ($hasDemons) echo $html;
		else echo '<div class="alert alert-info"><i class="fas fa-times"></i> {{ Vous n\'avez aucun équipement nécéssitant un démon.}} </div>';

		?>
	</div>

	<div class="tab-pane" id="tab_sketch">
		<br />
		<form class="form-horizontal">
			<fieldset>
				<?php
				if ($Arduino_reseaux != '') echo  '<div class="alert alert-primary"><i class="fas fa-download"></i> {{ Sketchs pour vos équipements Arduino / ESP8266 / NodeMCU / Wemos.}} </div>' . $Arduino_reseaux;
				else echo '<div class="alert alert-info"><i class="fas fa-times"></i> {{ Vous n\'avez aucun équipement nécéssitant un Sketch.}} </div>';
				?>
			</fieldset>
		</form>
	</div>

	<div class="tab-pane" id="tab_docker">
		<br />
		<div class="alert alert-warning"><i class="fas fa-rss"></i>{{ Uniquement pour utilisateurs avertis.}} </div>

		<form class="form-horizontal">
			<fieldset>
				<div class="form-group ">
					<label class="col-lg-8 control-label">{{Utiliser une configuration réseau de Jeedom Perso (Utile sous Docker par ex.)}}</label>
					<div class="col-lg-2">
						<input type="checkbox" class="configKey " data-l1key="ActiveJLAN" />
						<br /><br />
					</div>

					<div class="ipsource">
						<div class="form-group">
							<label class="col-lg-8 control-label">{{IP de l'hôte/NAS support de Jeedom}}</label>
							<div class="col-lg-2">
								<input type="text" class="configKey form-control" data-l1key="IPJLAN" placeholder="ex : 192.168.0.55" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-lg-8 control-label">{{Port (Mappé)}}</label>
							<div class="col-lg-2">
								<input type="text" class="configKey form-control" data-l1key="PORTJLAN" placeholder="ex : 9080" />
							</div>
						</div>
					</div>
				</div>
			</fieldset>
		</form>
	</div>

</div>
<div class=" alert alert-success">
	<span><i class="fas fa-arrow-right"></i> {{Veillez à réactualiser cette page (F5) après l'avoir sauvegardée.}}</span>
</div>
<script>
	$('#tab_jeedouino a').click(function(e) {
		e.preventDefault()
		$(this).tab('show')
	});
	$('.bt_installUpdate').on('click', function() {
		$.ajax({ // fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
			data: {
				action: "installUpdate",
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) { // si l'appel a bien fonctionné
				if (data.state != 'ok') {
					$('#div_alert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click(); // recharge la page config du plugin
				$('#div_alert').showAlert({
					message: '{{Le système est en cours de mise à jour.}}',
					level: 'success'
				});
			}
		});
	});

	$('.bt_installSerial').on('click', function() {
		$.ajax({ // fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
			data: {
				action: "installSerial",
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) { // si l'appel a bien fonctionné
				if (data.state != 'ok') {
					$('#div_alert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click(); // recharge la page config du plugin
				$('#div_alert').showAlert({
					message: '{{Le module Serial pour Python est en cours d\'installation.}}',
					level: 'success'
				});
			}
		});
	});

	$('.bt_installPiPlus').on('click', function() {
		$.ajax({ // fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
			data: {
				action: "installPiPlus",
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) { // si l'appel a bien fonctionné
				if (data.state != 'ok') {
					$('#div_alert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click(); // recharge la page config du plugin
				$('#div_alert').showAlert({
					message: '{{Les dépendances IO.PiPlus sont en cours d\'installation.}}',
					level: 'success'
				});
			}
		});
	});

	$('.bt_installDS18B20').on('click', function() {
		$.ajax({ // fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
			data: {
				action: "installDS18B20",
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) { // si l'appel a bien fonctionné
				if (data.state != 'ok') {
					$('#div_alert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click(); // recharge la page config du plugin
				$('#div_alert').showAlert({
					message: '{{L\'installation de la dépendance danjperron/BitBangingDS18B20 est en cours.}}',
					level: 'success'
				});
			}
		});
	});

	$('.bt_installGPIO').on('click', function() {
		$.ajax({ // fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
			data: {
				action: "installGPIO",
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) { // si l'appel a bien fonctionné
				if (data.state != 'ok') {
					$('#div_alert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click(); // recharge la page config du plugin
				$('#div_alert').showAlert({
					message: '{{Les dépendances RPI.GPIO sont en cours d\'installation}}',
					level: 'success'
				});
			}
		});
	});

	$('.bt_installPIFACE').on('click', function() {
		$.ajax({ // fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
			data: {
				action: "installPIFACE",
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) { // si l'appel a bien fonctionné
				if (data.state != 'ok') {
					$('#div_alert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click(); // recharge la page config du plugin
				$('#div_alert').showAlert({
					message: '{{Les dépendances PIFACEDIGITALIO sont en cours d\'installation}}',
					level: 'success'
				});
			}
		});
	});

	$('.bt_StartDemon').on('click', function() {
		$.ajax({ // fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
			data: {
				action: "StartBoardDemon",
				boardid: $(this).attr('boardID'),
				DemonType: $(this).attr('DemonType'),
				id: $(this).attr('slaveID')
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) { // si l'appel a bien fonctionné
				if (data.state != 'ok') {
					$('#div_alert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click(); // recharge la page config du plugin
				$('#div_alert').showAlert({
					message: '{{Le démon a été correctement démarré}}',
					level: 'success'
				});
			}
		});
	});

	$('.bt_restartDemon').on('click', function() {
		$.ajax({ // fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
			data: {
				action: "ReStartBoardDemon",
				boardid: $(this).attr('boardID'),
				DemonType: $(this).attr('DemonType'),
				id: $(this).attr('slaveID')
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) { // si l'appel a bien fonctionné
				if (data.state != 'ok') {
					$('#div_alert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click(); // recharge la page config du plugin
				$('#div_alert').showAlert({
					message: '{{Le démon a été correctement (re)démarré}}',
					level: 'success'
				});
			}
		});
	});

	$('.bt_stopDemon').on('click', function() {
		$.ajax({ // fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
			data: {
				action: "StopBoardDemon",
				boardid: $(this).attr('boardID'),
				DemonType: $(this).attr('DemonType'),
				id: $(this).attr('slaveID')
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) { // si l'appel a bien fonctionné
				if (data.state != 'ok') {
					$('#div_alert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click(); // recharge la page config du plugin
				$('#div_alert').showAlert({
					message: '{{Le démon a été correctement arreté}}',
					level: 'success'
				});
			}
		});
	});
</script>
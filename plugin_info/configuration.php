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
   <div class="form-group" >
        <label class="col-lg-5 control-label">{{Raccourcis}}</label>
        <div class="col-lg-5">
<?php   if (jeedouino::Networkmode() == 'master')
               {
?>
            <a class="btn btn-warning " href="<?php echo $cpl; ?>/index.php?v=d&m=jeedouino&p=jeedouino">
                <img class="img-responsive" style="width : 20px;display:inline-block;" src="plugins/jeedouino/doc/images/jeedouino_icon.png"> {{Jeedouino Plugin}}
            </a>
<?php   }     ?>
            <a class="btn btn-warning " href="<?php echo $cpl; ?>/index.php?v=d&p=log&logfile=jeedouino">
                <img class="img-responsive" style="width : 20px;display:inline-block;" src="plugins/jeedouino/doc/images/jeedouino_icon.png"> {{Jeedouino Logs}}
            </a>
        </div>

            <br><br>
    </div>
<ul class="nav nav-tabs" id="tab_jeedouino">
	<li class="active"><a href="#tab_logs"><i class="fa fa-pencil-square-o"></i> {{Options}}</a></li>
	<li><a href="#tab_dep"><i class="fa fa-certificate"></i> {{Dépendances}}</a></li>
<?php
    if (jeedouino::Networkmode() == 'master')
	{
	?>
	<li><a href="#tab_demon"><i class="fa fa-university"></i> {{Démons}}</a></li>
	<li><a href="#tab_sketch"><i class="fa fa-download"></i> {{Sketchs Réseau}}</a></li>
	<li><a href="#tab_docker" class="expertModeVisible"><i class="fa fa-rss"></i> {{Conf. Docker}}</a></li>
	<li><a href="#tab_JeedouinoExt" class="expertModeVisible"><i class="fa fa-code"></i> {{JeedouinoExt}}</a></li>

<?php
	}
?>
	</ul>
<div class="tab-content">
	 <div class="tab-pane active" id="tab_logs">
	 <br/>
		<form class="form-horizontal">
			<fieldset>
				<div class="form-group" >
						<label class="col-lg-7 control-label">{{Activer les logs}}</label>
						<div class="col-lg-3">
							<input type="checkbox" class="configKey " data-l1key="ActiveLog" />
						</div>
				</div>
				<?php if (method_exists('virtual', 'copyFromEqLogic'))
						{
				?>
				<div class="form-group" >
						<label class="col-lg-7 control-label">{{Activer les groupes virtuels}}</label>
						<div class="col-lg-3">
							<input type="checkbox" class="configKey " data-l1key="ActiveVirtual" />
						</div>
				</div>
				<?php } ?>
				<div class="form-group" >
					<label class="col-lg-7 control-label expertModeVisible">{{Activer les commandes utilisateur (Sketchs persos - Arduinos/Esp8266/NodeMcu/Wemos...)}}</label>
					<div class="col-lg-3 expertModeVisible">
						<input type="checkbox" class="configKey  expertModeVisible" data-l1key="ActiveUserCmd" name="ActiveUserCmd"/>
					</div>
				</div>
				<!--
				<div class="form-group" >
					<label class="col-lg-7 control-label expertModeVisible text-warning">{{Maintenir les commandes en double lors d'un changement de fonction (Pour TESTS - NON recommandé).}}</label>
					<div class="col-lg-3 expertModeVisible">
						<input type="checkbox" class="configKey  expertModeVisible" data-l1key="MultipleCmd" name="MultipleCmd"/>
					</div>
				</div>
				-->
				<div class="alert alert-info"><a href="<?php echo $cpl; ?>/index.php?v=d&p=administration#configuration_logMessage"><i class="fa fa-arrow-right"></i> {{ N.B. Pensez aussi a activer les logs de niveau debug dans Jeedom.}} </a></div>
			</fieldset>
		</form>
	</div>

	<div class="tab-pane" id="tab_dep">
	<br/>
		<div class="alert alert-warning"><i class="fa fa-arrow-right"></i> {{ A n'installer que si nécéssaire.}} </div>
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
								<div class="form-group" >
										<label class="col-lg-5 control-label">{{Télécharger l'Arduino IDE}}</label>
										<div class="col-lg-5">
											<a href="https://www.arduino.cc/en/Main/Software" target="_blank" class="btn btn-success" ><i class='fa fa-floppy-o'></i>{{ Aller sur le site }}</a>
										</div>
								</div>
							</td>
							<td>{{Arduinos}}</td>
						</tr>
						<tr>
							<td>
								<div class="form-group" >
										<label class="col-lg-5 control-label">{{Install Python-Serial}}</label>
										<div class="col-lg-5">
												<a class="btn btn-info bt_installSerial" ><i class="fa fa-play"></i> {{sudo apt-get install python-serial}}</a>
										</div>
								</div>
							</td>
							<td>{{Arduinos sur port USB d'un Raspberry PI}}</td>
						</tr>
						<tr class="info">
							<td colspan=2>{{Dépendances spécifiques Raspberry PI}}</td>
						</tr>
						<tr>
							<td>
								<div class="form-group" >
										<label class="col-lg-5 control-label">{{RPi.GPIO Installation}}</label>
										<div class="col-lg-5">
												<a class="btn btn-info bt_installGPIO" ><i class="fa fa-play"></i> {{sudo pip install RPi.GPIO}}</a>
										</div>
								</div>
							</td>
							<td>{{Raspberry PI (gpio)}}</td>
						</tr>
						<tr>
							<td>
								<div class="form-group" >
										<label class="col-lg-5 control-label">{{Pifacedigitalio Installation}}</label>
										<div class="col-lg-5">
												<a class="btn btn-info bt_installPIFACE" ><i class="fa fa-play"></i> {{sudo apt-get install python-pifacedigitalio}}</a>
										</div>
								</div>
							</td>
							<td>{{Raspberry PI avec carte(s) PiFace}}</td>
						</tr>
						<tr>
							<td>
								<div class="form-group" >
										<label class="col-lg-5 control-label">{{IO.PiPlus smbus Installation}}</label>
										<div class="col-lg-5">
												<a class="btn btn-info bt_installPiPlus" ><i class="fa fa-play"></i> {{sudo install python-smbus}}</a>
										</div>
								</div>
							</td>
							<td>{{Raspberry PI avec carte(s) Pi.Plus ou MCP23017 (I2C)}}</td>
						</tr>
						<tr>
							<td>
								<div class="form-group" >
										<label class="col-lg-5 control-label">{{Correction droits DS18B20}}</label>
										<div class="col-lg-5">
												<a class="btn btn-info bt_installDS18B20" ><i class="fa fa-play"></i> {{sudo chmod 755 DS18B20Scan}}</a>
										</div>
								</div>
							</td>
							<td>{{Raspberry PI (gpio) avec sonde(s) DS18B20}}</td>
						</tr>
					</tbody>
				</table>

			<?php
			/*
				<div class="form-group" >
						<label class="col-lg-5 control-label">{{Dépendance : MàJ Système}}</label>
						<div class="col-lg-5">
								<a class="btn btn-info bt_installUpdate" ><i class="fa fa-play"></i> {{sudo apt-get update}}</a>
						</div>
				</div>
			*/
			?>


			</fieldset>
		</form>
	</div>

<?php
	if (jeedouino::Networkmode() == 'master')
	{
	?>
	 <div class="tab-pane" id="tab_demon">
        <br/>
        <div class="alert alert-primary"><i class="fa fa-university"></i> {{ Gestion des équipements avec Démons.}} </div>
		<div class="alert alert-warning"><i class="fa fa-arrow-right"></i> {{ N.B. Suite a un reboot, les démons démarrent automatiquement >4 min après Jeedom. Cf doc.}} </div>

		<form class="form-horizontal">
			<fieldset>
			<div class="form-group" >

		<table class="table table-bordered">
		<thead>
			<tr>
				<th>{{Jeedom}}</th>
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
		<tbody>
		<?php
		$Arduino_reseaux = '';
		$CronStepArr=config::byKey('CronStepArr', 'jeedouino', '');
		foreach ($eqLogics as $eqLogic)
		{
			if ($eqLogic->getIsEnable() == 0) continue;
			$name=$eqLogic->getName(true);
			$board_id=$eqLogic->getId();
			$ModeleArduino = $eqLogic->getConfiguration('arduino_board');
			$Sketch = 'Sketch pour ';
			$StatusDemon=false;
			$jsButton='ArduinoUsb';
			if ($eqLogic->getConfiguration('datasource')=='usbarduino') $a_usb=true;
			else  $a_usb=false;
			$a_lan=true;
			$esp=false;
			switch ($ModeleArduino)
			{
				case 'auno':
					$Sketch  .= 'Uno 328';
					if ($a_usb) $a_lan=false;
					break;
				case 'a2009':
					$Sketch  .= 'Duemillanove 328';
					if ($a_usb) $a_lan=false;
					break;
				case 'anano':
					$Sketch .= 'Nano 328';
					if ($a_usb) $a_lan=false;
					break;
				case 'a1280':
					$Sketch  .= 'Mega 1280';
					if ($a_usb) $a_lan=false;
					break;
				case 'a2560':
					$Sketch  .= 'Mega 2560';
					if ($a_usb) $a_lan=false;
					break;
				case 'esp01':
					$Sketch  .= 'ESP8266-01';
					$a_lan=true;
					$esp=true;
					break;
				case 'esp07':
					$Sketch  .= 'ESP8266-07';
					$a_lan=true;
					$esp=true;
					break;
				case 'espMCU01':
					$Sketch  .= 'NodeMCU';
					$a_lan=true;
					$esp=true;
					break;
                case 'espsonoffpow':
					$Sketch  .= 'SONOFF-POW';
					$a_lan=true;
					$esp=true;
					break;
                case 'espsonoff4ch':
					$Sketch  .= 'SONOFF-4CH';
					$a_lan=true;
					$esp=true;
					break;
				case 'piface':
					$a_lan=false;
					$Sketch  = '';
					$jsButton = 'PiFace';
					$StatusDemon = jeedouino::StatusBoardDemon($board_id, 0, $ModeleArduino);
					break;
				case 'piGPIO26':
				case 'piGPIO40':
					$a_lan=false;
					$Sketch  = '';
					$jsButton = 'PiGpio';
					$StatusDemon = jeedouino::StatusBoardDemon($board_id, 0, $ModeleArduino);
					break;
				case 'piPlus':
					$a_lan=false;
					$Sketch  = '';
					$jsButton = 'PiPlus';
					$StatusDemon = jeedouino::StatusBoardDemon($board_id, 0, $ModeleArduino);
					break;
				default:
					$a_lan=true;
					$Sketch  = '';
					$jsButton='';
				break;
			}
			if (!$a_lan)
			{
				$board_ip=$eqLogic->getConfiguration('iparduino');
				$jeedomMasterIP = jeedouino::GetJeedomIP();

				if ($jeedomMasterIP==$board_ip) $localDemon=true;
				else $localDemon=false;

				list($SlaveNetworkID, $SlaveName) = jeedouino::GetSlaveNetworkID(0, $board_ip);

				if (($a_usb) and (substr($ModeleArduino,0,1)=='a')) $StatusDemon = jeedouino::StatusBoardDemon($board_id, $SlaveNetworkID, $ModeleArduino);
				config::save($board_id.'_StatusDemon', $StatusDemon, 'jeedouino');
				config::save($board_id.'_HasDemon', 1, 'jeedouino');

				if (config::byKey('Auto_'. $board_id, 'jeedouino', 'none') == 'none') config::save('Auto_'. $board_id, 0, 'jeedouino');

				?>
				<tr>
					<td>
						<?php
							if ($localDemon) echo '{{Jeedom maître}}';
							elseif ($SlaveName!='') echo $SlaveName;
							else
							{
								if ($board_ip!='')  echo '{{JeedouinoExt}}';
								else echo '{{Equipement mal configuré}}';
							}
						?>
					</td>
					<td>
						<?php
							if ($localDemon) echo'{{Local}}';
							elseif ($SlaveName!='') echo '{{Distant}}';
							else
							{
								if ($board_ip!='') echo '{{ sur '.$board_ip.'}}';
								else echo '{{EqID'.$board_id.'}}';
							}
						?>
					</td>
					<td>
						<?php
							echo '<div class="col-lg-7"><a class="btn btn-default " href=" index.php?&v=d&p=jeedouino&m=jeedouino&id='.$board_id.'"><i class="fa fa-sitemap"></i> '.$name.'</a></div>';
						?>
					</td>
					<td class="deamonState">
						<?php
							if ($StatusDemon) echo '<span class="label label-success" style="font-size : 1em;" >OK</span>';
							else
							{
								if (($CronStepArr!='') and (in_array($board_id,$CronStepArr)))echo '<span class="label label-warning " style="font-size : 1em;" ><i class="fa fa-play"></i> 4min</span>';
								else echo '<span class="label label-danger " style="font-size : 1em;" >NOK</span>';
							}
						?>
					</td>
					<td>
						<?php
							if ($StatusDemon) echo '<a class="btn btn-success bt_restartDemon" slaveID="'.$SlaveNetworkID.'" boardID="'. $board_id.'" DemonType="'.$jsButton.'"><i class="fa fa-play"></i></a>';
							else echo '<a class="btn btn-success bt_StartDemon" slaveID="'.$SlaveNetworkID.'" boardID="'. $board_id.'" DemonType="'.$jsButton.'"><i class="fa fa-play"></i></a>';
						?>
					</td>
					<td>
						<?php
							if ($StatusDemon) echo '<a class="btn btn-danger bt_stopDemon" slaveID="'.$SlaveNetworkID.'" boardID="'. $board_id.'" DemonType="'.$jsButton.'"><i class="fa fa-stop"></i></a>';
						?>
					</td>
					<td>
						<?php
							echo '<label class="checkbox-inline"><input type="checkbox" class="configKey " data-l1key="Auto_'. $board_id.'" /><i class="fa fa-refresh"></i> {{5min}}</label>';
							//echo '<a class="btn btn-danger bt_changeAutoMode" data-mode="1" slaveID="'.$SlaveNetworkID.'" boardID="'. $board_id.'" DemonType="'.$jsButton.'"><i class="fa fa-magic"></i> ON</a>';
						?>
					</td>
					<td><?php echo $jsButton; ?></td>
					<td>
						<?php
							if ($Sketch != '')
							{
								echo '<div class="col-lg-5"><a href="plugins/jeedouino/sketchs/JeedouinoUSB.ino" class="btn btn-info"  title="{{ Télécharger le Sketch à mettre dans l\'arduino }}"><i class="fa fa-download"></i> SketchUSB</a></div>';
							}
						?>
					</td>
				</tr>
			<?php
			}
			else
			{
				config::save($board_id.'_HasDemon', 0, 'jeedouino');
				//jeedouino::log( 'debug',$board_id.'_HasDemon ==  '.config::byKey($board_id.'_HasDemon', 'jeedouino', 3));
				//jeedouino::log( 'debug',$board_id.'_StatusDemon ==  '.config::byKey($board_id.'_StatusDemon', 'jeedouino', 3));
				//<div class="col-lg-7"><a class="btn btn-default " href=" index.php?&v=d&p=jeedouino&m=jeedouino&id='.$board_id.'"><i class="fa fa-sitemap"></i> '.$name.'</a></div>
				if ($esp)
				{
					$Arduino_reseaux .= '<div class="form-group">
					<label class="col-lg-4 control-label">{{ '.$Sketch.'}}</label>
					<div class="col-lg-1"><a class="btn btn-default " href=" index.php?&v=d&p=jeedouino&m=jeedouino&id='.$board_id.'"><i class="fa fa-sitemap"></i></a></div>
					<div class="col-lg-5">
						<a href="plugins/jeedouino/sketchs/JeedouinoESP_'.$board_id.'.ino" class="btn btn-info" ><i class="fa fa-download"></i>{{ Télécharger le Sketch a mettre dans l\'esp ( EqID : '.$board_id.' ) }}</a>
					</div></div>';
				}
				else
				{
					$Arduino_reseaux .= '<div class="form-group">
					<label class="col-lg-4 control-label">{{ '.$Sketch.'}}</label>
					<div class="col-lg-1"><a class="btn btn-default " href=" index.php?&v=d&p=jeedouino&m=jeedouino&id='.$board_id.'"><i class="fa fa-sitemap"></i></a></div>
					<div class="col-lg-5">
						<a href="plugins/jeedouino/sketchs/JeedouinoLAN_'.$board_id.'.ino" class="btn btn-info" ><i class="fa fa-download"></i>{{ Télécharger le Sketch a mettre dans l\'arduino ( EqID : '.$board_id.' ) }}</a>
					</div></div>';
				}
			}

		}
		echo '</tbody></table>';
		if ($Arduino_reseaux!='')
		{
			$Arduino_reseaux .= '<div class="form-group">
							<label class="col-lg-5 control-label">{{ Librairies pour vos Sketchs }}</label>
							<div class="col-lg-5">
								<a href="plugins/jeedouino/sketchs/ArduinoLibraries.zip" class="btn btn-warning" ><i class="fa fa-download"></i>{{ Télécharger les librairies Arduinos/ESP }}</a>
							</div></div>';

		}
		?>
			</div>
			</fieldset>
		</form>
	</div>

	 <div class="tab-pane" id="tab_sketch">
        <br/>
        <div class="alert alert-primary"><i class="fa fa-download"></i> {{ Sketchs pour vos équipements Arduino réseau / ESP8266 / NodeMCU / Wemos.}} </div>

		<form class="form-horizontal">
			<fieldset>
				<?php echo  $Arduino_reseaux; ?>
			</fieldset>
		</form>
	</div>

	 <div class="tab-pane" id="tab_docker">
        <br/>
        <div class="alert alert-warning"><i class="fa fa-rss"></i>{{ Uniquement pour utilisateurs avertis.}} </div>

		<form class="form-horizontal">
			<fieldset>
				<div class="form-group ">
					<label class="col-lg-8 control-label">{{Utiliser une configuration réseau de Jeedom Perso (Utile sous Docker par ex.)}}</label>
					<div class="col-lg-2">
						<input type="checkbox" class="configKey " data-l1key="ActiveJLAN" />
						<br/><br/>
					</div>

					<div class="ipsource">
					   <div class="form-group">
							<label class="col-lg-8 control-label">{{IP de l'hôte/NAS support de Jeedom}}</label>
							<div class="col-lg-2">
								<input type="text" class="configKey form-control"  data-l1key="IPJLAN" placeholder="ex : 192.168.0.55"/>
							</div>
						</div>
						<div class="form-group">
							<label class="col-lg-8 control-label">{{Port (Mappé)}}</label>
							<div class="col-lg-2">
								<input type="text" class="configKey form-control"  data-l1key="PORTJLAN" placeholder="ex : 9080"/>
							</div>
						</div>
					</div>
				</div>
			</fieldset>
		</form>
	</div>

	 <div class="tab-pane" id="tab_JeedouinoExt">
        <br/>
        <div class="alert alert-warning"><i class="fa fa-code"></i>{{ Uniquement pour utilisateurs avancés. /!\ Option en version BETA pour TESTS seulement. /!\}} </div>
		<form class="form-horizontal">
			<fieldset>
				<div class="form-group" >
						<label class="col-lg-5 control-label">{{Activer JeedouinoExt}}</label>
						<div class="col-lg-5">
							<input type="checkbox" class="configKey " data-l1key="ActiveExt" />
						</div>
				</div>
			</fieldset>
		</form>
		<?php if (config::byKey('ActiveExt', 'jeedouino', false))
		{

			// Sur JeedouinoExt (sans Jeedom)
			$ListExtIP=config::byKey('ListExtIP', 'jeedouino', '');
			if ($ListExtIP != '')
			{
				// un petit nettoyage
				$ListExtIPCleaned = array();
				foreach ($ListExtIP as $_ip)
				{
					if (filter_var($_ip, FILTER_VALIDATE_IP) === false) continue;
					$ListExtIPCleaned[]=$_ip;
				}
				$ListExtIP = $ListExtIPCleaned;
				config::save('ListExtIP', $ListExtIP, 'jeedouino');
			}
			if ($ListExtIP != '')
			{
				echo '<form class="form-horizontal">
							<fieldset> ';
				foreach ($ListExtIP as $_ip)
				{
					$_path = trim(config::byKey('path-'.$_ip, 'jeedouino', ''));
					if ($_path == '') $_path = '/';
					$_port = trim(config::byKey('PORT-'.$_ip, 'jeedouino', ''));
					if ($_port == '') $_port = '80';
					echo '
					<div class="form-group" >
						<label class="col-lg-5 control-label">{{Configuration de JeedouinoExt sur }}</label>
						<div class="col-lg-5">
							<span class="label label-success" style="font-size : 1em; cursor : default;"><a href="http://' . $_ip . ':' . $_port . $_path . 'JeedouinoExt.php" target="_blank"><i class="fa fa-home"></i> ' . $_ip . '</a></span>
						</div>
					</div>'	;
				}
				echo '</fieldset>
					</form>';
			}
		?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{{ Extension spécifique pour Raspberry déportés sans Jeedom.}}</h3>
			</div>
			<div class="panel-body">
				<span  class="pull-right">Testé uniquement sur <a class="btn btn-danger btn-xs" href="https://www.raspberrypi.org/downloads/raspbian/" target="_blank"> 2016-03-18-raspbian-jessie-lite.zip</a></span>
				<p><br/><br/>1 - Se connecter au Shell via SSH du RPI déporté.</p>
				<p>2 - Faire un <kbd>sudo su</kbd></p>
				<p>3.1 - Se mettre dans le dossier racine du serveur web si présent:<br/>
					- Nginx ex: <kbd>cd /usr/share/nginx/www</kbd> ou <kbd>cd /var/www/html</kbd><br/>
					- Apache ex: <kbd>cd /var/www/html</kbd><br/>
					A adapter à votre cas.<br/>
					Puis <kbd>cd JeedouinoExt</kbd><br/>
					<kbd>sudo chmod -R 775 $(pwd)</kbd><br/>
					<kbd>sudo chown www-data:www-data $(pwd)</kbd><br/>
					<br/>
					3.2 - Si pas de serveur web de présent, faire les points 4, 5 et 6 dans le dossier courant, puis<br/>
					<kbd>cd JeedouinoExt</kbd><br/>
					<kbd>/bin/bash  JeedouinoExt.sh</kbd><br/>
					Ensuite passer à l'étape 7.
				</p>
				<p>4 - Supprimer le zip JeedouinoExt si déja présent<br/>
					<kbd>rm JeedouinoExt.zip* </kbd></p>
				<p>5 - Récupérer le zip JeedouinoExt<br/>
					<kbd>wget <?php echo $ip.':'.$port.$cpl; ?>/plugins/jeedouino/ressources/JeedouinoExt.zip</kbd></p>
				<p>6 - Le dézipper en créant le dossier JeedouinoExt.<br/>
					<kbd>unzip JeedouinoExt.zip</kbd></p>
				<p>7 - Normalement la page de configuration JeedouinoExt devrait être accessible sur <br/>
					<kbd>IP_du_RPI/JeedouinoExt/JeedouinoExt.php</kbd></p>
				<p>8 - Une fois sur la page de configuration JeedouinoExt, il faut configurer en premier l'IP du Jeedom maître, le port ( et le complément si utile ) puis <span class="btn btn-success btn-xs">valider</span>.<br/>
					Cela permettra à la page JeedouinoExt et au Jeedom maître de communiquer.</p>
			</div>
		</div>
		<?php }  ?>
	</div>
	<?php
	}
?>

</div>
<br/><div class="nav-tabs"></div> <br/>

	<div class=" alert alert-success">
	<span><i class="fa fa-arrow-right"></i> {{Veillez à réactualiser cette page (F5) après l'avoir sauvegardée.}}</span>
	</div>
<script>
 $('#tab_jeedouino a').click(function (e) {
    e.preventDefault()
    $(this).tab('show')
});
   $('.bt_installUpdate').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
            data: {
                action: "installUpdate",
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click();   // recharge la page config du plugin
            $('#div_alert').showAlert({message: '{{Le système est en cours de mise à jour.}}', level: 'success'});
        }
    });
    });

   $('.bt_installSerial').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
            data: {
                action: "installSerial",
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click();   // recharge la page config du plugin
            $('#div_alert').showAlert({message: '{{Le module Serial pour Python est en cours d\'installation.}}', level: 'success'});
        }
    });
    });

   $('.bt_installPiPlus').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
            data: {
                action: "installPiPlus",
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click();   // recharge la page config du plugin
            $('#div_alert').showAlert({message: '{{Les dépendances IO.PiPlus sont en cours d\'installation.}}', level: 'success'});
        }
    });
    });

   $('.bt_installDS18B20').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
            data: {
                action: "installDS18B20",
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click();   // recharge la page config du plugin
            $('#div_alert').showAlert({message: '{{Les corrections de droits de DS18B20Scan sont en cours.}}', level: 'success'});
        }
    });
    });

	$('.bt_installGPIO').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
            data: {
                action: "installGPIO",
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click();   // recharge la page config du plugin
            $('#div_alert').showAlert({message: '{{Les dépendances RPI.GPIO sont en cours d\'installation}}', level: 'success'});
        }
    });
    });

   $('.bt_installPIFACE').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
            data: {
                action: "installPIFACE",
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click();   // recharge la page config du plugin
            $('#div_alert').showAlert({message: '{{Les dépendances PIFACEDIGITALIO sont en cours d\'installation}}', level: 'success'});
        }
    });
    });

   $('.bt_StartDemon').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
            data: {
                action: "StartBoardDemon",
                boardid : $(this).attr('boardID'),
				DemonType : $(this).attr('DemonType'),
                id : $(this).attr('slaveID')
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click();   // recharge la page config du plugin
            $('#div_alert').showAlert({message: '{{Le démon a été correctement démarré}}', level: 'success'});
        }
    });
    });

   $('.bt_restartDemon').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
            data: {
                action: "ReStartBoardDemon",
                boardid : $(this).attr('boardID'),
				DemonType : $(this).attr('DemonType'),
                id : $(this).attr('slaveID')
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click();   // recharge la page config du plugin
            $('#div_alert').showAlert({message: '{{Le démon a été correctement (re)démarré}}', level: 'success'});
        }
    });
    });

   $('.bt_stopDemon').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
            data: {
                action: "StopBoardDemon",
                boardid : $(this).attr('boardID'),
				DemonType : $(this).attr('DemonType'),
                id : $(this).attr('slaveID')
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click();   // recharge la page config du plugin
            $('#div_alert').showAlert({message: '{{Le démon a été correctement arreté}}', level: 'success'});
        }
    });
    });

</script>

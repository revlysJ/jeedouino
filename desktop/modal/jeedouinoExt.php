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

if (!isConnect('admin'))
{
	throw new Exception('{{401 - Accès non autorisé}}');
}

$JeedouinoExts = jeedouino::allJeedouinoExt();
$eqLogics = eqLogic::byType('jeedouino');
$ip = jeedouino::GetJeedomIP();

?>
<div id='div_jeedouinoExtAlert' style="display: none;"></div>
<div id='div_jeedouinoExtblk'class="row row-overflow">
	<div class="col-lg-3 col-md-4 col-sm-5 col-xs-5">
		<div class="bs-sidebar">
			<ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
				<a class="btn btn-warning jeedouinoExtAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fas fa-plus-circle"></i> {{Ajouter un JeedouinoExt}}</a>
				<a class="btn btn-info    jeedouinoExtAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="refresh" data-refresh-id=""><i class="fas fa-sync"></i> {{Rafraichir la page}}</a>
				<li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
				<?php
				foreach ($JeedouinoExts as $JeedouinoExt)
				{
					echo '<li class="cursor li_jeedouinoExt" data-jeedouinoExt_id="' . $JeedouinoExt['id'] . '"><a>' . $JeedouinoExt['name'] . ' <i class="fas fa-server"></i> ' . $JeedouinoExt['IP'] . '</a></li>';
				}
				?>
			</ul>
		</div>
	</div>

	<div class="col-lg-9 col-md-8 col-sm-7 col-xs-7 jeedouinoExtThumbnailDisplay" style="border-left: solid 1px #00979C; padding-left: 25px;">
		<legend><i class="fas fa-table"></i>  {{Mes JeedouinoExt}}</legend>

		<div class="eqLogicThumbnailContainer">
			<div class="cursor jeedouinoExtAction logoPrimary" style="color:#00979C;" data-action="add" style="width:10px">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
			<?php
			foreach ($JeedouinoExts as $JeedouinoExt)
			{
				echo '<div class="eqLogicDisplayCard cursor col-lg-2" data-jeedouinoExt_id="' . $JeedouinoExt['id'] . '" style="width:10px">';
				echo '<img class="lazy" src="plugins/jeedouino/icons/jeedouino_piGPIO40.png"/>';
				echo '<br>';
				echo '<span class="name">' . $JeedouinoExt['name'] . '</span>';
				echo '</div>';
			}
			?>
		</div>
	</div>


	<div class="col-lg-9 col-md-8 col-sm-7 col-xs-7 jeedouinoExt" style="border-left: solid 1px #00979C; padding-left: 25px;display:none;">
		<a class="btn btn-success jeedouinoExtAction pull-right" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
		<a class="btn btn-danger jeedouinoExtAction jeedouinoExtRemove pull-right" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>

		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="" class="jeedouinoExtAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="ExtHideLog active"><a href="#jeedouinoExtConfigtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-cogs"></i> {{Paramètres}}</a></li>
			<li role="presentation" class="ExtHideLog"><a href="#jeedouinoExtEq" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Equipement(s) et Démon(s)}}</a></li>
			<li role="presentation" class="ExtShowLog" style="display:none;"><a href="#jeedouinoExtLogs" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Logs distants}}</a></li>
		</ul>

		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="jeedouinoExtConfigtab">
				<form class="form-horizontal">
					<fieldset>
						<br>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Nom du JeedouinoExt}}</label>
							<div class="col-sm-3">
								<input type="text" class="jeedouinoExtAttr form-control" data-l1key="id" style="display : none;" />
								<input type="text" class="jeedouinoExtAttr form-control" data-l1key="name" placeholder="{{Nom du JeedouinoExt}}"/>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Adresse IP}}</label>
							<div class="col-sm-3">
								<input required pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" class="jeedouinoExtAttr form-control" data-l1key="IP" placeholder="ex: <?php echo $ip; ?>"/>
							</div>
							<div class="col-sm-2 JeedouinoExtPage">
								<span id="GoToJeedouinoExt" class="pull-left"><a class="btn btn-info JeedouinoExtHREF" target="_blank" href="#"><i class="fas fa-sitemap"></i> Ouvrir</a></span>
							</div>
						</div>
					</fieldset>
					<fieldset>
						<legend><i class="fas fa-cogs"></i> {{Accès SSH}}</legend>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Login SSH}}</label>
							<div class="col-sm-3">
								<input type="login" class="jeedouinoExtAttr form-control" data-l1key="sshID" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Mot de passe SSH}}</label>
							<div class="col-sm-3 input-group">
								<input type="text" class="inputPassword jeedouinoExtAttr configuration form-control" data-l1key="sshPW" placeholder="Mot de passe"/>
								<span class="input-group-btn">
									<a class="btn btn-default form-control bt_showPass roundedRight"><i class="fas fa-eye"></i></a>
								</span>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Port SSH}}</label>
							<div class="col-sm-2">
								<input type="number" class="jeedouinoExtAttr form-control" data-l1key="sshPort" placeholder="22"/>
							</div>
						</div>
						<div class="form-group JeedouinoExtNew JeedouinoExtNoLocal">
							<label class="col-sm-3 control-label">{{Fichiers JeedouinoExt}}</label>
							<div class="col-sm-9">
								<a class="btn btn-warning jeedouinoExtAction" data-action="sendFiles"><i class="fas fa-spinner"></i> {{Envoi pour Installation}}</a>
								<a class="btn btn-success jeedouinoExtAction" data-action="getExtLog" log="/tmp/InstallJeedouinoExt.log"><i class="fas fa-file-alt"></i> {{Logs d'installation}}</a>
								<!-- <a class="btn btn-info" target="_blank" download href="/../../plugins/jeedouino/ressources/JeedouinoExt.zip"><i class="fas fa-download"></i> {{Zip}}</a>-->
							</div>
						</div>
						<div class="form-group JeedouinoExtNew JeedouinoExtNoLocal">
							<label class="col-sm-3 control-label">{{Ou}}</label>
							<div class="col-sm-9">
								<a class="btn btn-warning jeedouinoExtAction" data-action="sendFiles2"><i class="fas fa-spinner"></i> {{Envoi pour mise à jour}}</a>
								<a class="btn btn-success jeedouinoExtAction" data-action="getExtLog" log="/var/www/html/JeedouinoExt/JeedouinoExt.log"><i class="fas fa-file-alt"></i> {{Logs JeedouinoExt}}</a>
							</div>
						</div>
						<div class="alert alert-info JeedouinoExtNew JeedouinoExtNoLocal">
							{{La durée d'installation peut être trés (trés) longue selon les systèmes.}}<br>
							{{Il faudra re-sauver le(s) équipement(s) après un envoi sur une installation existante si le redémarrage du/des démon(s) échoue.}}<br>
						</div>
					</fieldset>
					<fieldset class="JeedouinoExtNew">
						<legend><i class="fas fa-cog"></i> {{Paramètres reçus}}</legend>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Chemin URL}}</label>
							<div class="col-sm-3">
								<input type="text" class="jeedouinoExtAttr form-control" data-l1key="URLpath" placeholder="/" disabled="disabled" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Port URL}}</label>
							<div class="col-sm-2">
								<input type="number" class="jeedouinoExtAttr form-control" data-l1key="URLport" placeholder="80" disabled="disabled"/>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Liste USB}}</label>
							<div class="col-sm-3">
								<textarea class="jeedouinoExtAttr form-control" data-l1key="usbMap" disabled="disabled"/></textarea>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
			<div role="tabpanel" class="tab-pane" id="jeedouinoExtEq">
				<br>
				<div class="alert alert-info JeedouinoExtNew">
					{{Si un équipement est NOK ici mais OK sur la page JeedouinoExt, patientez 3min (màj du cache) et ré-actualisez (F5) ici.}}<br>
				</div>
				<form class="form-horizontal">
					<fieldset>
						<div class="form-group" >
							<table class="table table-bordered" style = "margin-left: 15px;width:calc(100% - 50px);overflow:auto;">
								<thead>
									<tr>
										<th>{{Equipement}}</th>
										<th>{{Statut}}</th>
										<th>{{(Re)Démarrer}}</th>
										<th>{{Arrêter}}</th>
										<th>{{AutoReStart}}</th>
										<th>{{Type}}</th>
										<th>{{Logs}}</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$ListExtIP = jeedouino::CleanIPJeedouinoExt();
									$CronStepArr = config::byKey('CronStepArr', 'jeedouino', '');
									if (!is_array($ListExtIP)) $ListExtIP = array();
									if (!is_array($CronStepArr)) $CronStepArr = array();

									foreach ($eqLogics as $eqLogic)
									{
										if ($eqLogic->getIsEnable() == 0) continue;

										$ip = $eqLogic->getConfiguration('iparduino');
										if (!in_array($ip, $ListExtIP)) continue;

										$id = trim(config::byKey('ID-' . $ip, 'jeedouino', ''));
										if ($id == '') $id = jeedouino::AddIDJeedouinoExt($ip);

										echo '<tr class="jeedouinoExtEqTR" data-jextid="' . $id . '">';
										echo '<td><div class="col-lg-7"><a class="btn btn-default " href=" index.php?&v=d&p=jeedouino&m=jeedouino&id=' . $eqLogic->getId() . '" target="_blank"><i class="fas fa-sitemap"></i> ' . $eqLogic->getName(true) . '</a></div></td>';

										$StatusDemon = jeedouino::StatusBoardDemon($eqLogic->getId(), 0, $eqLogic->getConfiguration('arduino_board'));
										if ($StatusDemon) echo '<td><span class="btn btn-success" >OK</span></td>';
										else
										{
											if (($CronStepArr != '') and (in_array($eqLogic->getId(), $CronStepArr))) echo '<td><span class="btn btn-warning " ><i class="fas fa-spinner"></i> 4min</span></td>';
											else echo '<td><span class="btn btn-danger" >NOK</span></td>';
										}
										switch ($eqLogic->getConfiguration('arduino_board'))
										{
											case 'piface':
												$jsButton = 'PiFace';
												break;
												case 'piGPIO26':
												case 'piGPIO40':
												$jsButton = 'PiGpio';
												break;
												case 'piPlus':
												$jsButton = 'PiPlus';
												break;
												default:
												$jsButton = 'USB';
											}
											if ($StatusDemon) echo '<td><a class="btn btn-success bt_restartDemon" slaveID="0" boardID="' . $eqLogic->getId() . '" DemonType="' . $jsButton . '"><i class="fas fa-sync"></i></a></td>';
											else echo '<td><a class="btn btn-success bt_StartDemon" slaveID="0" boardID="' . $eqLogic->getId() . '" DemonType="' . $jsButton . '"><i class="fas fa-play"></i></a></td>';
											echo '<td>';
											if ($StatusDemon) echo '<a class="btn btn-danger bt_stopDemon" slaveID="0" boardID="' . $eqLogic->getId() . '" DemonType="' . $jsButton . '"><i class="fas fa-stop"></i></a>';
											echo '</td>';
											if (config::byKey('Auto_'. $eqLogic->getId(), 'jeedouino', 0)) echo '<td><a class="btn btn-success bt_AutoReStart" slaveID="0" boardID="' . $eqLogic->getId() . '" DemonType="' . $jsButton . '"><i class="fas fa-check"></i>  {{5min}}</a></td>';
											else echo '<td><a class="btn btn-danger bt_AutoReStart" slaveID="0" boardID="' . $eqLogic->getId() . '" DemonType="' . $jsButton . '"><i class="fas fa-times"></i>  {{5min}}</a></td>';

											echo '<td>' . $jsButton . '</td>';
											echo '<td><a class="btn btn-success jeedouinoExtAction" data-action="getExtLog" log="/var/www/html/JeedouinoExt/Jeedouino' . $jsButton . '_' . $eqLogic->getId() . '.log"><i class="fas fa-file-alt"></i> </a></td>';
											echo '</tr>';
										}
										?>
									</tbody>
								</table>
							</div>
						</fieldset>
					</form>
				</div>
				<div role="tabpanel" class="tab-pane" id="jeedouinoExtLogs">
					<br>
					<div class="alert alert-info">
						{{Suivi distant via ssh des logs de JeedouinoExt - RefreshAuto 5s}}
						<a class="btn btn-danger pull-right" id="bt_vider" logfile=""><i class="fas fa-eraser"></i> {{Effacer}}</a>
						<a class="btn btn-warning pull-right" id="bt_pause" logfile=""><i class="fas fa-pause"></i> {{Pause}}</a>
						<a class="btn btn-success pull-right" target="_blank" download href="/../../plugins/jeedouino/data/jeedouino_ext.txt"><i class="fas fa-download"></i> {{Télécharger}}</a>
						<br><br>
					</div>
					<pre id="modal_log" style='overflow: auto; height: 500px; width:100%;'></pre>
				</div>
			</div>
		</div>
	</div>
	<script>
	timeout = null;
	pause = 1;
	voirlogs = 0;
	document.getElementById('div_jeedouinoExtblk').addEventListener('click', function(event) {
	  var _target = null
		if (_target = event.target.closest('.jeedouinoExtAction[data-action=getExtLog]')) {
			if (voirlogs == 0)
			{
				voirlogs = 1;
				pause = 0;
				document.getElementById('modal_log').empty()
				document.querySelector('.nav-tabs a[href="#jeedouinoExtLogs"]')?.click();;
				document.getElementById('bt_pause').setAttribute('logfile', _target.getAttribute('log'));
				document.getElementById('bt_vider').setAttribute('logfile', _target.getAttribute('log'));
				autoupdate({
					logfile : _target.getAttribute('log'),
					display : document.getElementById('modal_log')
				});
				document.querySelector('.jeedouinoExtAction[data-action=remove]').unseen();
				document.querySelector('.jeedouinoExtAction[data-action=save]').unseen();
				document.querySelector('.ExtShowLog').seen();
			}
			else
			{
				hideLog();
			}
		}
		if (_target = event.target.closest('.ExtHideLog')) {
			hideLog();
		}
		if (_target = event.target.closest('.jeedouinoExtAction[data-action=add]')) {
			document.querySelector('.jeedouinoExt').seen();
			document.querySelector('.jeedouinoExtThumbnailDisplay').unseen();
			document.querySelectorAll('.jeedouinoExtAttr').jeeValue('');
			document.querySelector('.jeedouinoExtAttr[data-l1key=IP]').removeAttribute("disabled");
			document.querySelector('.JeedouinoExtPage').unseen();
			document.querySelector('.JeedouinoExtNew').unseen();
			if (document.querySelector('.jeedouinoExtEqTR')) document.querySelector('.jeedouinoExtEqTR').unseen();
			document.querySelector('.jeedouinoExtAction[data-action=refresh]').setAttribute('data-refresh-id', '');
			hideLog();
			document.querySelector('.nav-tabs a[href="#jeedouinoExtConfigtab"]')?.click();
			document.querySelector('.jeedouinoExtRemove').unseen();
			document.querySelector('.nav-tabs a[href="#jeedouinoExtConfigtab"]').addClass('active');
			document.querySelectorAll('.li_jeedouinoExt').removeClass('active');
		}
		if (_target = event.target.closest('.jeedouinoExtAction[data-action=returnToThumbnailDisplay]')) {
			document.querySelector('.jeedouinoExt').unseen();
			document.querySelectorAll('.li_jeedouinoExt').removeClass('active');
			document.querySelector('.jeedouinoExtThumbnailDisplay').seen();
			hideLog();
		}
		if (_target = event.target.closest('.eqLogicDisplayCard')) {
			displayjeedouinoExt(_target.getAttribute('data-jeedouinoExt_id'));
			document.querySelector('.jeedouinoExtAction[data-action=refresh]').setAttribute('data-refresh-id', _target.getAttribute('data-jeedouinoExt_id'));
			hideLog();
			document.querySelector('.nav-tabs a[href="#jeedouinoExtConfigtab"]')?.click();
			document.querySelector('.nav-tabs a[href="#jeedouinoExtConfigtab"]').addClass('active');
		}
		if (_target = event.target.closest('.li_jeedouinoExt')) {
			displayjeedouinoExt(_target.getAttribute('data-jeedouinoExt_id'));
			document.querySelector('.jeedouinoExtAction[data-action=refresh]').setAttribute('data-refresh-id', _target.getAttribute('data-jeedouinoExt_id'));
			hideLog();
			document.querySelector('.nav-tabs a[href="#jeedouinoExtConfigtab"]')?.click();
			document.querySelector('.nav-tabs a[href="#jeedouinoExtConfigtab"]').addClass('active');
		}
		if (_target = event.target.closest('.jeedouinoExtAction[data-action=save]')) {
			var jeedouino_ext = document.querySelector('.jeedouinoExt').getJeeValues('.jeedouinoExtAttr')[0];
			domUtils.ajax({
				type: "POST",
				url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
				data: {
					action: "save_jeedouinoExt",
					jeedouino_ext: json_encode(jeedouino_ext),
				},
				dataType: 'json',
				error: function (request, status, error) {
					domUtils.handleAjaxError(request, status, error);
				},
				success: function (data) {
					if (data.state != 'ok') {
						jeedomUtils.showAlert({message: data.result, level: 'danger'});
						return;
					}
					document.querySelectorAll('div.jeeDialog:not(.jeeDialogNoCloseBackdrop)').forEach(_dialog => {
            if (isset(_dialog._jeeDialog)) _dialog._jeeDialog.close(_dialog)
					})
					jeeDialog.dialog({
						id: "div_jeedouinoExtblk",
						title: "{{Gestion JeedouinoExt}}",
						contentUrl: "index.php?v=d&plugin=jeedouino&modal=jeedouinoExt"
					})

					setTimeout(function() {
						jeedomUtils.showAlert({message: '{{Sauvegarde réussie}} ', level: 'success'});
						displayjeedouinoExt(data.result.id);
						document.querySelector('.jeedouinoExtAction[data-action=refresh]').setAttribute('data-refresh-id', data.result.id);
					}, 199);
				}
			});
		}
		if (_target = event.target.closest('.jeedouinoExtAction[data-action=remove]')) {
			jeeDialog.confirm('{{Êtes-vous sûr de vouloir supprimer ce JeedouinoExt ?}}', function (result) {
				if (result) {
					domUtils.ajax({
						type: "POST",
						url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
						data: {
							action: "remove_jeedouinoExt",
							id: document.querySelector('.li_jeedouinoExt.active').getAttribute('data-jeedouinoExt_id'),
						},
						dataType: 'json',
						error: function (request, status, error) {
							domUtils.handleAjaxError(request, status, error);
						},
						success: function (data) {
							document.querySelector('.li_jeedouinoExt.active').remove();
							document.querySelector('.jeedouinoExt').unseen();
							document.querySelectorAll('div.jeeDialog:not(.jeeDialogNoCloseBackdrop)').forEach(_dialog => {
								if (isset(_dialog._jeeDialog)) _dialog._jeeDialog.close(_dialog)
							})
							jeeDialog.dialog({
								id: "div_jeedouinoExtblk",
								title: "{{Gestion JeedouinoExt}}",
								contentUrl: "index.php?v=d&plugin=jeedouino&modal=jeedouinoExt"
							})
							setTimeout(function() {
								document.querySelector('.jeedouinoExtAction[data-action=refresh]').setAttribute('data-refresh-id', '');
								if (data.state != 'ok') {
									jeedomUtils.showAlert({message: data.result, level: 'danger'});
									return;
								}
							}, 199);
						}
					});
				}
			});
		}
		if (_target = event.target.closest('.jeedouinoExtAction[data-action=sendFiles]')) {
			var jeedouino_ext = document.querySelector('.jeedouinoExt').getJeeValues('.jeedouinoExtAttr')[0];
			domUtils.ajax({
				type: "POST",
				url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
				data: {
					action: "send_jeedouinoExt",
					jeedouino_ext: json_encode(jeedouino_ext),
				},
				dataType: 'json',
				error: function (request, status, error) {
					domUtils.handleAjaxError(request, status, error);
				},
				success: function (data) {
					if (data.state != 'ok') {
						jeedomUtils.showAlert({message: data.result, level: 'danger'});
						return;
					}
					jeedomUtils.showAlert({message: '{{Envoi(s) réussi(s)}}', level: 'success'});
				}
			});
		}
		if (_target = event.target.closest('.jeedouinoExtAction[data-action=sendFiles2]')) {
			var jeedouino_ext = document.querySelector('.jeedouinoExt').getJeeValues('.jeedouinoExtAttr')[0];
			domUtils.ajax({
				type: "POST",
				url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
				data: {
					action: "send_jeedouinoExt2",
					jeedouino_ext: json_encode(jeedouino_ext),
				},
				dataType: 'json',
				error: function (request, status, error) {
					domUtils.handleAjaxError(request, status, error);
				},
				success: function (data) {
					if (data.state != 'ok') {
						jeedomUtils.showAlert({message: data.result, level: 'danger'});
						return;
					}
					jeedomUtils.showAlert({message: '{{Envoi(s) réussi(s)}}', level: 'success'});
				}
			});
		}
		if (_target = event.target.closest('.bt_StartDemon')) {
			var jextid = _target.closest('.jeedouinoExtEqTR').getAttribute('data-jextid');
			domUtils.ajax({
				type: "POST",
				url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
				data: {
					action: "StartBoardDemon",
					boardid : _target.getAttribute('boardID'),
					DemonType : _target.getAttribute('DemonType'),
					id : _target.getAttribute('slaveID')
				},
				dataType: 'json',
				error: function (request, status, error) {
					domUtils.handleAjaxError(request, status, error);
				},
				success: function (data) {
					if (data.state != 'ok') {
						jeedomUtils.showAlert({message: data.result, level: 'danger'});
						return;
					}
					jeedomUtils.showAlert({message: '{{Démarrage du démon demandé.}}', level: 'success'});
					refreshTab(jextid);
				}
			});
		}
		if (_target = event.target.closest('.bt_restartDemon')) {
			var jextid = _target.closest('.jeedouinoExtEqTR').getAttribute('data-jextid');
			domUtils.ajax({
				type: "POST",
				url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
				data: {
					action: "ReStartBoardDemon",
					boardid : _target.getAttribute('boardID'),
					DemonType : _target.getAttribute('DemonType'),
					id : _target.getAttribute('slaveID')
				},
				dataType: 'json',
				error: function (request, status, error) {
					domUtils.handleAjaxError(request, status, error);
				},
				success: function (data) {
					if (data.state != 'ok') {
						jeedomUtils.showAlert({message: data.result, level: 'danger'});
						return;
					}
					jeedomUtils.showAlert({message: '{{Re-démarrage du démon demandé.}}', level: 'success'});
					refreshTab(jextid);
				}
			});
		}
		if (_target = event.target.closest('.bt_stopDemon')) {
			var iid = _target.closest('.jeedouinoExtEqTR').getAttribute('data-jextid');
			domUtils.ajax({
				type: "POST",
				url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
				data: {
					action: "StopBoardDemon",
					boardid : _target.getAttribute('boardID'),
					DemonType : _target.getAttribute('DemonType'),
					id : _target.getAttribute('slaveID')
				},
				dataType: 'json',
				error: function (request, status, error) {
					domUtils.handleAjaxError(request, status, error);
				},
				success: function (data) {
					if (data.state != 'ok') {
						jeedomUtils.showAlert({message: data.result, level: 'danger'});
						return;
					}
					jeedomUtils.showAlert({message: '{{Arret du démon demandé.}}', level: 'success'});
					refreshTab(iid);
				}
			});
		}
		if (_target = event.target.closest('.bt_AutoReStart')) {
			_btars = _target
			domUtils.ajax({
				type: "POST",
				url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
				data: {
					action: "bt_AutoReStart",
					boardid : _target.getAttribute('boardID'),
				},
				dataType: 'json',
				error: function (request, status, error) {
					domUtils.handleAjaxError(request, status, error);
				},
				success: function (data) {
					if (data.state != 'ok') {
						jeedomUtils.showAlert({message: data.result, level: 'danger'});
						return;
					}
					if (data.result.status == 1)
					{
						_btars.removeClass('btn-danger').addClass('btn-success')
						_btars.querySelector('i').removeClass('fas fa-times').addClass('fas fa-check');
						jeedomUtils.showAlert({message: '{{AutoRestart du démon ajouté.}}', level: 'success'});
					}
					else
					{
						_btars.removeClass('btn-success').addClass('btn-danger')
						_btars.querySelector('i').removeClass('fas fa-check').addClass('fas fa-times');
						jeedomUtils.showAlert({message: '{{AutoRestart du démon supprimé.}}', level: 'success'});
					}

				}
			});
		}
		if (_target = event.target.closest('.jeedouinoExtAction[data-action=refresh]')) {
			var iid = _target.getAttribute('data-refresh-id');
			document.querySelectorAll('div.jeeDialog:not(.jeeDialogNoCloseBackdrop)').forEach(_dialog => {
				if (isset(_dialog._jeeDialog)) _dialog._jeeDialog.close(_dialog)
			})
			jeeDialog.dialog({
				id: "div_jeedouinoExtblk",
				title: "{{Gestion JeedouinoExt}}",
				contentUrl: "index.php?v=d&plugin=jeedouino&modal=jeedouinoExt"
			})
			if (iid != '')
				setTimeout(function() {
					displayjeedouinoExt(iid);
					document.querySelector('.jeedouinoExtAction[data-action=refresh]').setAttribute('data-refresh-id', iid);
				}, 199);
		}
	})
	document.getElementById('bt_pause').unRegisterEvent('click').registerEvent('click', function (event) {
		if (pause == 0)
		{
			pause = 1;
			document.getElementById('bt_pause').removeClass('btn-warning').addClass('btn-success');
			document.getElementById('bt_pause').innerHTML = '<i class="fas fa-play"></i> {{Reprendre}}';
		}
		else
		{
			pause = 0;
			document.getElementById('bt_pause').removeClass('btn-success').addClass('btn-warning');
			document.getElementById('bt_pause').innerHTML = '<i class="fa fa-pause"></i> {{Pause}}';
			autoupdate({
				logfile : document.getElementById('bt_pause').getAttribute('logfile'),
				display : document.getElementById('modal_log')
			});
		}
	});
	document.getElementById('bt_vider').unRegisterEvent('click').registerEvent('click', function (event) {
		if ( confirm('Etês-vous sûr de vouloir vider ce fichier distant ?' ) )
		{
			logf = document.getElementById('bt_vider').getAttribute('logfile');
			var jeedouino_ext = document.querySelector('.jeedouinoExt').getJeeValues('.jeedouinoExtAttr')[0];
			jeedomUtils.showAlert({message: '<i class="fa fa-spinner fa-spin fa-fw"></i> Purge du fichier de log ' + logf + ' en cours.', level: 'success'});
			domUtils.ajax({
				type: "POST",
				url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
				data: {
					action: "delExtLog",
					logfile : logf,
					jeedouino_ext: json_encode(jeedouino_ext)
				},
				dataType: 'json',
				error: function (request, status, error) {
					domUtils.handleAjaxError(request, status, error);
				},
				success: function (data) {
					if (data.state != 'ok') {
						jeedomUtils.showAlert({message: data.result, level: 'danger'});
						return;
					}
					//setTimeout(function(){ jeeDialog.hide(); }, 3000);
				}
			});
		};
	});
	hideLog = function ()	{
		voirlogs = 0;
		pause = 1;
		document.querySelector('.jeedouinoExtAction[data-action=remove]').seen();
		document.querySelector('.jeedouinoExtAction[data-action=save]').seen();
		document.querySelector('.ExtShowLog').unseen();
		document.getElementById('modal_log').empty();
		document.getElementById('bt_pause').removeClass('btn-success').addClass('btn-warning');
		document.getElementById('bt_pause').innerHTML = '<i class="fa fa-pause"></i> {{Pause}}';
		if (timeout !== null) { clearTimeout( timeout ); }
	}
	autoupdate = function (prm)	{
		if (document.querySelector('.jeedouinoExt') == null) {
			pause = 1;
			if (timeout !== null) { clearTimeout( timeout ); }
			return;
		}
		if (pause == 1) return;
		var jeedouino_ext = document.querySelector('.jeedouinoExt').getJeeValues('.jeedouinoExtAttr')[0];
		if (jeedouino_ext == null || jeedouino_ext == '')
		{
			if (timeout !== null) { clearTimeout( timeout ); }
			document.querySelector('.ExtShowLog').unseen();
			pause = 1;
			return;
		}
		domUtils.ajax({
			type: "POST",
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
			data: {
				action: 'getExtLog',
				logfile : prm.logfile,
				jeedouino_ext: json_encode(jeedouino_ext)
			},
			dataType: 'json',
			error: function (request, status, error) {
				domUtils.handleAjaxError(request, status, error);
			},
			success : function(data){
				var log = '';
				if (Array.isArray(data.result))
				{
					for (var i in data.result) { log += (data.result[i].trim()) + "\n"; }
				}
				prm.display.innerHTML = log;
				prm.display.scrollTop = prm.display.offsetHeight + 30000;
				if (timeout !== null) { clearTimeout( timeout ); }
				timeout = setTimeout( function() { autoupdate(prm) }, 5000 );
			},
			error : function(){
				document.getElementById('modal_log').empty();
				document.getElementById('modal_log').innerHTML = {{'Erreur de Lecture du fichier distant !'}};
				if (timeout !== null) { clearTimeout( timeout ); }
				timeout = setTimeout( function() { autoupdate(prm) }, 5000 );
			},
		});
	}
	refreshTab = function (iid)	{
		document.querySelectorAll('div.jeeDialog:not(.jeeDialogNoCloseBackdrop)').forEach(_dialog => {
			if (isset(_dialog._jeeDialog)) _dialog._jeeDialog.close(_dialog)
		})
		document.getElementById('div_jeedouinoExtblk').unRegisterEvent('click');
		jeeDialog.dialog({
			id: "div_jeedouinoExtblk",
			title: "{{Gestion JeedouinoExt}}",
			contentUrl: "index.php?v=d&plugin=jeedouino&modal=jeedouinoExt"
		})
		if (timeout !== null) { clearTimeout( timeout ); }
		if (iid != '')
			timeout = setTimeout(function() {
				displayjeedouinoExt(iid);
				document.querySelector('.jeedouinoExtAction[data-action=refresh]').setAttribute('data-refresh-id', iid);
				document.querySelector('.nav-tabs a[href="#jeedouinoExtEq"]')?.click();
			}, 199);
		}
	function displayjeedouinoExt(_id){
		document.querySelectorAll('.li_jeedouinoExt').removeClass('active');
		document.querySelector('.li_jeedouinoExt[data-jeedouinoExt_id="' + _id + '"]').addClass('active');
		domUtils.ajax({
			type: "POST",
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
			data: {
				action: "get_jeedouinoExt",
				id: _id,
			},
			dataType: 'json',
			error: function (request, status, error) {
				domUtils.handleAjaxError(request, status, error);
			},
			success: function (data) {
				if (data.state != 'ok') {
					jeedomUtils.showAlert({message: data.result, level: 'danger'});
					return;
				}
				if (document.querySelector('.jeedouinoExtEqTR')) document.querySelector('.jeedouinoExtEqTR').unseen();
				document.querySelector('.jeedouinoExtThumbnailDisplay').unseen();
				document.querySelector('.jeedouinoExt').seen();
				document.querySelector('.jeedouinoExtAttr').jeeValue('');
				document.querySelector('.jeedouinoExt').setJeeValues(data.result,'.jeedouinoExtAttr');
				document.querySelector('.jeedouinoExtAttr[data-l1key=IP]').setAttribute('disabled','disabled');
				document.querySelector('.JeedouinoExtPage').seen();
				document.querySelector('.JeedouinoExtNew').seen();
				if (data.result.IP == '127.0.0.1') { document.querySelector('.JeedouinoExtNoLocal').unseen(); }
				document.querySelector('.JeedouinoExtHREF').setAttribute('href', 'http://' + data.result.IP + '/JeedouinoExt/JeedouinoExt.php');
				if (document.querySelector('.jeedouinoExtEqTR[data-jextid="' + _id + '"]')) document.querySelector('.jeedouinoExtEqTR[data-jextid="' + _id + '"]').seen();
			}
		});
	}

	</script>

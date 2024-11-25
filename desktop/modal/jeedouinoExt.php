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

$JeedouinoExts = jeedouino::allJeedouinoExt();
$eqLogics = eqLogic::byType('jeedouino');
$ip = jeedouino::GetJeedomIP();

?>
<div id='div_jeedouinoExtAlert' style="display: none;"></div>
<div class="row row-overflow">
	<div class="col-lg-3 col-md-4 col-sm-5 col-xs-5">
		<div class="bs-sidebar">
			<ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
				<a class="btn btn-warning jeedouinoExtAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fas fa-plus-circle"></i> {{Ajouter un JeedouinoExt}}</a>
				<a class="btn btn-info    jeedouinoExtAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="refresh" data-refresh-id=""><i class="fas fa-sync"></i> {{Rafraichir la page}}</a>
				<li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%" /></li>
				<?php
				foreach ($JeedouinoExts as $JeedouinoExt) {
					echo '<li class="cursor li_jeedouinoExt" data-jeedouinoExt_id="' . $JeedouinoExt['id'] . '"><a>' . $JeedouinoExt['name'] . ' <i class="fas fa-server"></i> ' . $JeedouinoExt['IP'] . '</a></li>';
				}
				?>
			</ul>
		</div>
	</div>

	<div class="col-lg-9 col-md-8 col-sm-7 col-xs-7 jeedouinoExtThumbnailDisplay" style="border-left: solid 1px #00979C; padding-left: 25px;">
		<legend><i class="fas fa-table"></i> {{Mes JeedouinoExt}}</legend>

		<div class="eqLogicThumbnailContainer">
			<div class="cursor jeedouinoExtAction logoPrimary" style="color:#00979C;" data-action="add" style="width:10px">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
			<?php
			foreach ($JeedouinoExts as $JeedouinoExt) {
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
								<input type="text" class="jeedouinoExtAttr form-control" data-l1key="name" placeholder="{{Nom du JeedouinoExt}}" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Adresse IP}}</label>
							<div class="col-sm-3">
								<input required pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" class="jeedouinoExtAttr form-control" data-l1key="IP" placeholder="ex: <?php echo $ip; ?>" />
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
							<div class="col-sm-3">
								<input type="password" class="jeedouinoExtAttr form-control" data-l1key="sshPW" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Port SSH}}</label>
							<div class="col-sm-2">
								<input type="number" class="jeedouinoExtAttr form-control" data-l1key="sshPort" placeholder="22" />
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
								<input type="number" class="jeedouinoExtAttr form-control" data-l1key="URLport" placeholder="80" disabled="disabled" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Liste USB}}</label>
							<div class="col-sm-3">
								<textarea class="jeedouinoExtAttr form-control" data-l1key="usbMap" disabled="disabled" /></textarea>
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
						<div class="form-group">
							<table class="table table-bordered" style="margin-left: 15px;width:calc(100% - 50px);overflow:auto;">
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
									$CronStepArr = config::byKey('CronStepArr', 'jeedouino', []);
									foreach ($eqLogics as $eqLogic) {
										if ($eqLogic->getIsEnable() == 0) continue;
										$ip = $eqLogic->getConfiguration('iparduino');
										if (!in_array($ip, $ListExtIP)) continue;
										$id = trim(config::byKey('ID-' . $ip, 'jeedouino', ''));
										//jeedouino::log( 'debug','>>>> IP ' . $ip . " ID $id");
										if ($id == '') $id = jeedouino::AddIDJeedouinoExt($ip);
										//jeedouino::log( 'debug','>>>> IP ' . $ip . " ID $id");
										echo '<tr class="jeedouinoExtEqTR" data-jextid="' . $id . '">';
										echo '<td><div class="col-lg-7"><a class="btn btn-default " href=" index.php?&v=d&p=jeedouino&m=jeedouino&id=' . $eqLogic->getId() . '" target="_blank"><i class="fas fa-sitemap"></i> ' . $eqLogic->getName(true) . '</a></div></td>';
										$StatusDemon = jeedouino::StatusBoardDemon($eqLogic->getId(), 0, $eqLogic->getConfiguration('arduino_board'));
										if ($StatusDemon) echo '<td><span class="btn btn-success" >OK</span></td>';
										else {
											if (is_array($CronStepArr) and (in_array($eqLogic->getId(), $CronStepArr))) echo '<td><span class="btn btn-warning " ><i class="fas fa-spinner"></i> 4min</span></td>';
											else echo '<td><span class="btn btn-danger" >NOK</span></td>';
										}
										switch ($eqLogic->getConfiguration('arduino_board')) {
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
										if (config::byKey('Auto_' . $eqLogic->getId(), 'jeedouino', 0)) echo '<td><a class="btn btn-success bt_AutoReStart" slaveID="0" boardID="' . $eqLogic->getId() . '" DemonType="' . $jsButton . '"><i class="fas fa-check"></i>  {{5min}}</a></td>';
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
					<a class="btn btn-success pull-right" target="_blank" download href="/../../plugins/jeedouino/ressources/jeedouino_ext.logg"><i class="fas fa-download"></i> {{Télécharger}}</a>
					<br><br>
				</div>
				<pre id="modal_log" style='overflow: auto; height: 100%; width:100%;'></pre>
			</div>
		</div>
	</div>
</div>
<script>
	timeout = null;
	pause = 1;
	voirlogs = 0;
	$('.jeedouinoExtAction[data-action=getExtLog]').off('click').on('click', function() {
		if (voirlogs == 0) {
			voirlogs = 1;
			pause = 0;
			$('#modal_log').empty();
			$('.nav-tabs a[href="#jeedouinoExtLogs"]').tab('show');
			$('#bt_pause').attr('logfile', $(this).attr('log'));
			$('#bt_vider').attr('logfile', $(this).attr('log'));
			autoupdate({
				logfile: $(this).attr('log'),
				display: $('#modal_log'),
			});
			$('.ExtShowLog').show();
			$('.jeedouinoExtAction[data-action=remove]').hide();
			$('.jeedouinoExtAction[data-action=save]').hide();
		} else {
			hideLog();
		}
	});
	$('.ExtHideLog').off('click').on('click', function() {
		hideLog();
	});
	hideLog = function() {
		voirlogs = 0;
		pause = 1;
		$('.ExtShowLog').hide();
		$('.jeedouinoExtAction[data-action=remove]').show();
		$('.jeedouinoExtAction[data-action=save]').show();
		$('#modal_log').empty();
		$('#bt_pause').removeClass('btn-success').addClass('btn-warning');
		$('#bt_pause').html('<i class="fas fa-pause"></i> {{Pause}}');
		if (timeout !== null) {
			clearTimeout(timeout);
		}
	}
	$('#bt_pause').off('click').on('click', function() {
		if (pause == 0) {
			pause = 1;
			$(this).removeClass('btn-warning').addClass('btn-success');
			$(this).html('<i class="fas fa-play"></i> {{Reprendre}}');
		} else {
			pause = 0;
			$(this).removeClass('btn-success').addClass('btn-warning');
			$(this).html('<i class="fas fa-pause"></i> {{Pause}}');
			autoupdate({
				logfile: $(this).attr('logfile'),
				display: $('#modal_log'),
			});
		}
	});
	///
	$('#bt_vider').off('click').on('click', function() {
		if (confirm('Etês-vous sûr de vouloir vider ce fichier distant ?')) {
			logf = $(this).attr('logfile');
			var jeedouino_ext = $('.jeedouinoExt').getValues('.jeedouinoExtAttr')[0];
			$('#div_jeedouinoExtAlert').showAlert({
				message: '<i class="fa fa-spinner fa-spin fa-fw"></i> Purge du fichier de log ' + logf + ' en cours.',
				level: 'success'
			});
			$.ajax({
				type: "POST",
				url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
				data: {
					action: "delExtLog",
					logfile: logf,
					jeedouino_ext: json_encode(jeedouino_ext)
				},
				dataType: 'json',
				success: function(data) {
					if (data.state != 'ok') {
						$('#div_jeedouinoExtAlert').showAlert({
							message: data.result,
							level: 'danger'
						});
						return;
					}
					setTimeout(function() {
						$('#div_jeedouinoExtAlert').hide();
					}, 3000);
				}
			});
		};
	});

	///
	autoupdate = function(prm) {
		if (pause == 1) return;
		var jeedouino_ext = $('.jeedouinoExt').getValues('.jeedouinoExtAttr')[0];
		if (jeedouino_ext == null || jeedouino_ext == '') {
			if (timeout !== null) {
				clearTimeout(timeout);
			}
			$('.ExtShowLog').hide();
			pause = 1;
			return;
		}
		$.ajax({
			type: "POST",
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
			data: {
				action: 'getExtLog',
				logfile: prm.logfile,
				jeedouino_ext: json_encode(jeedouino_ext)
			},
			dataType: 'json',
			success: function(data) {
				var log = '';
				if ($.isArray(data.result)) {
					for (var i in data.result) {
						log += $.trim(data.result[i]) + "\n";
					}
				}
				prm.display.text(log);
				prm.display.scrollTop(prm.display.height() + 50000);
				if (timeout !== null) {
					clearTimeout(timeout);
				}
				timeout = setTimeout(function() {
					autoupdate(prm)
				}, 5000);
			},
			error: function() {
				$('#modal_log').empty();
				$('#modal_log').text(__('Erreur de Lecture du fichier distant !', __FILE__));
				if (timeout !== null) {
					clearTimeout(timeout);
				}
				timeout = setTimeout(function() {
					autoupdate(prm)
				}, 5000);
			},
		});
	}

	///
	$('.jeedouinoExtAction[data-action=add]').on('click', function() {
		$('.jeedouinoExt').show();
		$('.jeedouinoExtThumbnailDisplay').hide();
		$('.jeedouinoExtAttr').value('');
		$('.jeedouinoExtAttr[data-l1key=IP]').prop("disabled", false);
		$('.JeedouinoExtPage').hide();
		$('.JeedouinoExtNew').hide();
		$('.jeedouinoExtEqTR').hide();

		$('.jeedouinoExtAction[data-action=refresh]').attr('data-refresh-id', '');
		hideLog();
		$('.nav-tabs a[href="#jeedouinoExtConfigtab"]').tab('show');
		$('.jeedouinoExtRemove').hide();
		$('.nav-tabs a[href="#jeedouinoExtConfigtab"]').addClass('active');
	});

	$('.jeedouinoExtAction[data-action=returnToThumbnailDisplay]').on('click', function() {
		$('.jeedouinoExt').hide();
		$('.li_jeedouinoExt').removeClass('active');
		$('.jeedouinoExtThumbnailDisplay').show();
		hideLog();
	});

	$('.eqLogicDisplayCard').on('click', function() {
		displayjeedouinoExt($(this).attr('data-jeedouinoExt_id'));
		$('.jeedouinoExtAction[data-action=refresh]').attr('data-refresh-id', $(this).attr('data-jeedouinoExt_id'));
		hideLog();
		$('.nav-tabs a[href="#jeedouinoExtConfigtab"]').tab('show');
		$('.nav-tabs a[href="#jeedouinoExtConfigtab"]').addClass('active');
	});
	$('.li_jeedouinoExt').on('click', function() {
		displayjeedouinoExt($(this).attr('data-jeedouinoExt_id'));
		$('.jeedouinoExtAction[data-action=refresh]').attr('data-refresh-id', $(this).attr('data-jeedouinoExt_id'));
		hideLog();
		$('.nav-tabs a[href="#jeedouinoExtConfigtab"]').tab('show');
		$('.nav-tabs a[href="#jeedouinoExtConfigtab"]').addClass('active');
	});

	function displayjeedouinoExt(_id) {
		$('.li_jeedouinoExt').removeClass('active');
		$('.li_jeedouinoExt[data-jeedouinoExt_id=' + _id + ']').addClass('active');
		$.ajax({
			type: "POST",
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
			data: {
				action: "get_jeedouinoExt",
				id: _id,
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error, $('#div_jeedouinoExtAlert'));
			},
			success: function(data) {
				if (data.state != 'ok') {
					$('#div_jeedouinoExtAlert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('.jeedouinoExtEqTR').hide();
				$('.jeedouinoExtThumbnailDisplay').hide();
				$('.jeedouinoExt').show();
				$('.jeedouinoExtAttr').value('');
				$('.jeedouinoExt').setValues(data.result, '.jeedouinoExtAttr');
				$('.jeedouinoExtAttr[data-l1key=IP]').prop("disabled", true);
				$('.JeedouinoExtPage').show();
				$('.JeedouinoExtNew').show();
				if (data.result.IP == '127.0.0.1') {
					$('.JeedouinoExtNoLocal').hide();
				}
				$('.JeedouinoExtHREF').attr('href', 'http://' + data.result.IP + '/JeedouinoExt/JeedouinoExt.php');
				$('.jeedouinoExtEqTR[data-jextid="' + _id + '"]').show();
			}
		});
	}

	$('.jeedouinoExtAction[data-action=save]').on('click', function() {
		var jeedouino_ext = $('.jeedouinoExt').getValues('.jeedouinoExtAttr')[0];
		$.ajax({
			type: "POST",
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
			data: {
				action: "save_jeedouinoExt",
				jeedouino_ext: json_encode(jeedouino_ext),
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error, $('#div_jeedouinoExtAlert'));
			},
			success: function(data) {
				if (data.state != 'ok') {
					$('#div_jeedouinoExtAlert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('#md_modal').dialog('close');
				$('#md_modal').dialog({
					title: "{{Gestion JeedouinoExt}}"
				});
				$('#md_modal').load('index.php?v=d&plugin=jeedouino&modal=jeedouinoExt').dialog('open');

				setTimeout(function() {
					$('#div_jeedouinoExtAlert').showAlert({
						message: '{{Sauvegarde réussie}} ',
						level: 'success'
					});
					displayjeedouinoExt(data.result.id);
					$('.jeedouinoExtAction[data-action=refresh]').attr('data-refresh-id', data.result.id);
				}, 199);
			}
		});
	});

	$('.jeedouinoExtAction[data-action=remove]').on('click', function() {
		bootbox.confirm('{{Etês-vous sûr de vouloir supprimer ce JeedouinoExt ?}}', function(result) {
			if (result) {
				$.ajax({
					type: "POST",
					url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
					data: {
						action: "remove_jeedouinoExt",
						id: $('.li_jeedouinoExt.active').attr('data-jeedouinoExt_id'),
					},
					dataType: 'json',
					error: function(request, status, error) {
						handleAjaxError(request, status, error, $('#div_jeedouinoExtAlert'));
					},
					success: function(data) {
						$('.li_jeedouinoExt.active').remove();
						$('.jeedouinoExt').hide();
						$('#md_modal').dialog('close');
						$('#md_modal').dialog({
							title: "{{Gestion JeedouinoExt}}"
						});
						$('#md_modal').load('index.php?v=d&plugin=jeedouino&modal=jeedouinoExt').dialog('open');
						setTimeout(function() {
							$('.jeedouinoExtAction[data-action=refresh]').attr('data-refresh-id', '');
							if (data.state != 'ok') {
								$('#div_jeedouinoExtAlert').showAlert({
									message: data.result,
									level: 'danger'
								});
								return;
							}
						}, 199);

					}
				});
			}
		});
	});

	$('.jeedouinoExtAction[data-action=sendFiles]').on('click', function() {
		var jeedouino_ext = $('.jeedouinoExt').getValues('.jeedouinoExtAttr')[0];
		$.ajax({
			type: "POST",
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
			data: {
				action: "send_jeedouinoExt",
				jeedouino_ext: json_encode(jeedouino_ext),
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error, $('#div_jeedouinoExtAlert'));
			},
			success: function(data) {
				if (data.state != 'ok') {
					$('#div_jeedouinoExtAlert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('#div_jeedouinoExtAlert').showAlert({
					message: '{{Envois réussis}}',
					level: 'success'
				});
			}
		});
	});

	$('.jeedouinoExtAction[data-action=sendFiles2]').on('click', function() {
		var jeedouino_ext = $('.jeedouinoExt').getValues('.jeedouinoExtAttr')[0];
		$.ajax({
			type: "POST",
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
			data: {
				action: "send_jeedouinoExt2",
				jeedouino_ext: json_encode(jeedouino_ext),
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error, $('#div_jeedouinoExtAlert'));
			},
			success: function(data) {
				if (data.state != 'ok') {
					$('#div_jeedouinoExtAlert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('#div_jeedouinoExtAlert').showAlert({
					message: '{{Envois réussis}}',
					level: 'success'
				});
			}
		});
	});

	$('.bt_StartDemon').on('click', function() {
		var jextid = $(this).attr('data-jextid');
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
					$('#div_jeedouinoExtAlert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('#ul_eqLogic .li_jeedouinoExt[data-jeedouinoext_id=' + jextid + ']').click(); // recharge la page config du plugin
				$('#div_jeedouinoExtAlert').showAlert({
					message: '{{Démarrage du démon demandé.}}',
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
					$('#div_jeedouinoExtAlert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				//$('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click();   // recharge la page config du plugin
				$('#div_jeedouinoExtAlert').showAlert({
					message: '{{Re-démarrage du démon demandé.}}',
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
					$('#div_jeedouinoExtAlert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				//$('#ul_plugin .li_plugin[data-plugin_id=jeedouino]').click();   // recharge la page config du plugin
				$('#div_jeedouinoExtAlert').showAlert({
					message: '{{Arret du démon demandé.}}',
					level: 'success'
				});
			}
		});
	});

	$('.bt_AutoReStart').on('click', function() {
		var ar = $(this);
		$.ajax({ // fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
			data: {
				action: "bt_AutoReStart",
				boardid: $(this).attr('boardID')
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) { // si l'appel a bien fonctionné
				if (data.state != 'ok') {
					$('#div_jeedouinoExtAlert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$('#div_jeedouinoExtAlert').showAlert({
					message: '{{AutoRestart du démon modifié.}}',
					level: 'success'
				});
				if (data.result.status == 1) {
					ar.removeClass('btn-danger');
					ar.addClass('btn-success');
					ar.children('i').removeClass('fas fa-times');
					ar.children('i').addClass('fas fa-check');
				} else {
					ar.removeClass('btn-success');
					ar.addClass('btn-danger');
					ar.children('i').removeClass('fas fa-check');
					ar.children('i').addClass('fas fa-times');
				}

			}
		});
	});

	$('.jeedouinoExtAction[data-action=refresh]').on('click', function() {
		var iid = $(this).attr('data-refresh-id');
		$('#md_modal').dialog('close');
		$('#md_modal').dialog({
			title: "{{Gestion JeedouinoExt}}"
		});
		$('#md_modal').load('index.php?v=d&plugin=jeedouino&modal=jeedouinoExt').dialog('open');
		setTimeout(function() {
			displayjeedouinoExt(iid);
			$('.jeedouinoExtAction[data-action=refresh]').attr('data-refresh-id', iid);
		}, 199);
	});
</script>
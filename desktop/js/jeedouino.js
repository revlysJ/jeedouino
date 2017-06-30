
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



/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.jeedouino
 */
 $('#bt_graphEqLogic').off('click').on('click', function () {
  $('#md_modal').dialog({title: "{{Graphique de lien}}"});
  $("#md_modal").load('index.php?v=d&modal=graph.link&filter_type=eqLogic&filter_id='+$('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});
$('#bt_exportEq').on('click', function() { 
	$('#md_modal').dialog({title: "{{Exportation de l'équipement}}"});
	$('#md_modal').load('index.php?v=d&plugin=jeedouino&modal=export&id=' + $('.eqLogicAttr[data-l1key=id]').value() + '&board=' + $('.eqLogicAttr[data-l1key=configuration][data-l2key=arduino_board]').value()).dialog('open');
});
 $('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').on('change',function(){
	 if ($('.eqLogicAttr[data-l1key=configuration][data-l2key=arduino_board]').value() != '')
	 {
		$('.datasource').hide();
		$('.datasource.'+$(this).value()).show();
		$('.sketchs').hide();
		//$('.esp8266').hide();
		if ($(this).value()=='usbarduino') 
		{
			$('.sketchUSB').show();
			$('.ActiveExt').show();
		}
		else
		{
			if ($('.eqLogicAttr[data-l1key=configuration][data-l2key=arduino_board]').value().substr(0, 1)=='e')
			{
				$('.ActiveExt').hide();
				$('.sketchESP' + $('.li_eqLogic.active').attr('data-eqLogic_id')).show();	
			}
			if ($('.eqLogicAttr[data-l1key=configuration][data-l2key=arduino_board]').value().substr(0, 1)=='a')
			{
				$('.ActiveExt').hide();
				$('.sketchLAN' + $('.li_eqLogic.active').attr('data-eqLogic_id')).show();
			}
		}
	 }
});
 $('.eqLogicAttr[data-l1key=configuration][data-l2key=arduino_board]').on('change',function(){
	if ($(this).value()=='')
	{
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').removeAttr('disabled');
		$('.config_pin').hide();
		$('.datasource').hide();
		$('.piFacePortID').hide();
		$('.piPlusPortI2C').hide();	
		$('.sketchs').hide();
		$('.sketchsLib').hide();
		$('.sketchUSB').hide();
		$('.esp8266').hide();
		$('.sketchstab').hide();
		$('.ActiveExt').hide();
	}
	else if ($(this).value()=='piface' || $(this).value()=='piGPIO26' || $(this).value()=='piGPIO40' || $(this).value()=='piPlus' )
	{
		$('.config_pin').show(); 
		if ($(this).value()=='piface') $('.piFacePortID').show();
		else $('.piFacePortID').hide();
		if ($(this).value()=='piPlus') $('.piPlusPortI2C').show();
		else $('.piPlusPortI2C').hide();		
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').value('rj45arduino');
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').attr('disabled','disabled');
		$('.sketchs').hide();	
		$('.sketchUSB').hide();
		$('.sketchsLib').hide();
		$('.esp8266').hide();
		$('.sketchstab').hide();
		$('.ActiveExt').show();
	}
	else if ($(this).value().substr(0, 1)=='e')
	{
		$('.config_pin').show(); 
		$('.piFacePortID').hide();
		$('.piPlusPortI2C').hide();	
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').value('rj45arduino');
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').attr('disabled','disabled');
		$('.sketchs').hide();	
		$('.sketchUSB').hide();
		$('.sketchLAN' + $('.li_eqLogic.active').attr('data-eqLogic_id')).hide();
		$('.sketchESP' + $('.li_eqLogic.active').attr('data-eqLogic_id')).show();
		$('.sketchsLib').show();
		$('.esp8266').show();
		$('.sketchstab').show();
		$('.ActiveExt').hide();
	}	
	else if ($(this).value().substr(0, 1)=='a')
	{
		$('.sketchs').hide();
		$('.sketchUSB').hide();		
		if ($('.li_eqLogic.active').attr('data-eqLogic_id') != undefined) 
		{
			$('.sketchsLib').show();
			
			$('.sketchESP' + $('.li_eqLogic.active').attr('data-eqLogic_id')).hide();
			 if ($('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').value() != 'usbarduino')
			 {
				$('.sketchLAN' + $('.li_eqLogic.active').attr('data-eqLogic_id')).show();
				$('.ActiveExt').hide();
			 }
			 else
			 {
				$('.sketchUSB').show();
				$('.ActiveExt').show();
			 }
		}		
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').removeAttr('disabled');
		$('.config_pin').show();
		$('.piFacePortID').hide();
		$('.piPlusPortI2C').hide();	
		$('.esp8266').hide();
		$('.datasource.'+$('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').value()).show();
		$('.sketchstab').show();
	} 
	else
	{
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').removeAttr('disabled');
		$('.config_pin').show();
		$('.piFacePortID').hide();
		$('.piPlusPortI2C').hide();	
		$('.datasource '+$('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').value()).show();
		$('.sketchs').hide();
		$('.sketchsLib').hide();
		$('.sketchUSB').hide();
		$('.esp8266').hide();
		$('.sketchstab').hide();
		$('.ActiveExt').hide();
	}
});
 $('.eqLogicAttr[data-l1key=configuration][data-l2key=arduinoport]').on('change',function(){
	$('.arduinoport').hide();
	$('.arduinoport.'+$(this).value()).show();
});

$('#bt_conf_Pin').on('click', function() { 
	$('#md_modal').dialog({title: "{{Paramétrages / affectation des pins}}"});
	$('#md_modal').load('index.php?v=d&plugin=jeedouino&modal=conf_pin&id=' + $('.eqLogicAttr[data-l1key=id]').value() + '&board=' + $('.eqLogicAttr[data-l1key=configuration][data-l2key=arduino_board]').value()).dialog('open');
});
$('.eqLogicAction[data-action=bt_healthSpecific]').on('click', function () {
  $('#md_modal').dialog({title: "{{Santé Jeedouino}}"});
  $('#md_modal').load('index.php?v=d&plugin=jeedouino&modal=health').dialog('open');
});
$('.bt_plugin_view_log').on('click',function(){
 if($('#md_modal').is(':visible')){
   $('#md_modal2').dialog({title: "{{Logs de Jeedouino}}"});
   $("#md_modal2").load('index.php?v=d&modal=log.display&log='+$(this).attr('data-log')+'&slaveId='+$(this).attr('data-slaveId')).dialog('open');
 }else{
   $('#md_modal').dialog({title: "{{Logs de Jeedouino}}"});
   $("#md_modal").load('index.php?v=d&modal=log.display&log='+$(this).attr('data-log')+'&slaveId='+$(this).attr('data-slaveId')).dialog('open');
 }
});
/* Fonction appelé pour mettre l'affichage du tableau des commandes de votre eqLogic
 * _cmd: les détails de votre commande
 */
/* global jeedom */
$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

function addCmdToTable(_cmd) {
	if (!isset(_cmd)) {
		var _cmd = {configuration: {}};
	}
	if (!isset(_cmd.configuration)) {
		_cmd.configuration = {};
	}
	
	var ctype = init(_cmd.type);
	var stype = init(_cmd.subType);
	var mtype = init(_cmd.configuration.modePIN);
	var gtype = init(_cmd.display.generic_type);
	var pins_id = init(_cmd.configuration.pins_id);
	if (pins_id>999) pins_id -= 1000;
/* 	if (pins_id & 1) var tr = '<tr class="cmd table-info" data-cmd_id="' + init(_cmd.id) + '">';
	else */
	var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
	
	tr += '<td>';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom}}">';
/*     tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="Action Value = ID Info">';
    tr += '<option value="">Aucune</option>';
    tr += '</select>';	 */
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="pins_id" disabled style="display : none;">';	
	tr +=  '<span class="label label-info">PIN No : ' + pins_id + '</span>';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="modePIN" disabled style="display : none;">';	
	
	tr += '</td>'; 
	 
	tr += '<td>';
	//tr += jeedom.cmd.availableType();
	//tr += '<span class="type" type="' + ctype + '">' + ctype+ '</span>';
	tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="' + ctype + '" disabled style="display : none;" />';
	tr += '<input class="cmdAttr form-control  input-sm" data-l1key="subType" value="' + stype + '" disabled style="display : none;" />';
	if ( ctype == 'action') {
		tr += '<div class="label label-warning" style="text-transform: uppercase;">' + ctype + ' ( ' + stype + ' ) </div></br>';
		} else {
		tr += '<div class="label label-success" style="text-transform: uppercase;">' + ctype + ' ( ' + stype + ' ) </div></br>';
	}
	tr += '</td>';
	tr += '<td>';
	tr += '<div class="label label-primary" >' + gtype + '</div>';
	tr += '</td>';
	
	tr += '<td>';
	if ( ctype == 'action') {
		switch(mtype) 
		{
			case 'output_other':
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" >';
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" style="display : none;">';
				break;		
			case 'Send2LCD':
				tr += '<span class="label label-info">16 Caractères max.</span>';
			case 'output_message':
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="title" style="display : none;">';
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="message" style="display : none;">';
				break;
			case 'switch':
			case 'none':
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" style="display : none;">';
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" style="display : none;">';
				break;
			case 'high_relais':
			case 'low_relais':
			case 'teleinfoTX':
			case 'output_slider':	
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" disabled>';
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" style="display : none;">';
				break;
			case 'high_pulse':
			case 'low_pulse':
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" disabled>';
				tr += '<span class="label label-warning">Durée en dixième de secondes. 5 Chiffres max.</span>';
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" >';
				break;
			case 'trigger':
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" disabled>';
				tr += '<span class="label label-info">Numéro de la PIN ECHO correspondante.</span>';
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" >';
				break;				
			case 'output':
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" >';
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" style="display : none;">';
				break;			
			case 'pwm_output':
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" >';
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" style="display : none;">';
			//	tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="Min" title="Min"   style="display : none;">';
			//	tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="Max" title="Max"   style="display : none;">';

				break;						
			default:
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" >';
				tr += '<span class="label label-warning">Durée en dixième de secondes. 5 Chiffres max.</span>';
				tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" >';
		}
	}
	else if ( ctype == 'info')
	{
		if ( mtype == 'compteur_pullup')
		{
			tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" disabled>';
			tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="RSTvalue" >';
			tr += '<a class="btn btn-warning btn-xs cmdAction" data-action="ResetCPT"><i class="fa fa-rss"></i> {{Reset}}</a>';
		}
	}	
	tr += '</td>';
	
	tr += '<td>';
	tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr " data-l1key="isVisible" data-size="mini" checked />{{Afficher}}</label></span> ';
	tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr  expertModeVisible" data-l1key="display" data-l2key="invertBinary" data-size="mini" />{{Inverser}}</label></span> ';	
	if ( ctype == 'info') {
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr " data-l1key="isHistorized" data-size="mini" />{{Historiser}}</label></span> ';
	}	
	tr += '</td>';
	
	tr += '<td>';
	if ( ctype == 'info' && stype == 'binary') {
		tr += '<span class="expertModeVisible"><label class="checkbox-inline"><input type="checkbox" class="cmdAttr  expertModeVisible" data-l1key="display" data-l2key="invertBinare" data-size="mini" />{{Inverser}}</label></span> ';
	}	
	tr += '</td>';
	
	tr += '<td>';	
	tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
	tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
//    tr += '<a class="cmdAction btn btn-default btn-xs" data-l1key="chooseIcon"><i class="fa fa-flag"></i> {{Icône}}</a>';
//    tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
	tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
	tr += '</td>';
	
	tr += '</tr>';	
	$('#table_cmd tbody').append(tr);
 /*    var tr = $('#table_cmd tbody tr:last');
    jeedom.eqLogic.builSelectCmd({
        id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
        filter: {type: 'info'},
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (result) {
            tr.find('.cmdAttr[data-l1key=value]').append(result);
            tr.find('.cmdAttr[data-l1key=configuration][data-l2key=updateCmdId]').append(result);
            tr.setValues(_cmd, '.cmdAttr');
            jeedom.cmd.changeType(tr, init(_cmd.subType));
        }
    });	 */
	$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
	if (isset(_cmd.type)) {
		$('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
	}
	jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType)); 
}
/*
	Fonction pour mettre à jour la valeur reset compteur.
*/
$('body').undelegate('.cmd .cmdAction[data-action=ResetCPT]', 'click').delegate('.cmd .cmdAction[data-action=ResetCPT]', 'click', function () {	
	$.ajax({// fonction permettant de faire de l'ajax
		type: "POST", // methode de transmission des données au fichier php
		url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // url du fichier php
		data: {
			action: "ResetCPT",
			boardid : $('.li_eqLogic.active').attr('data-eqLogic_id'),
			RSTvalue : $(this).closest('.cmd').find('.cmdAttr[data-l1key=configuration][data-l2key=RSTvalue]').value(),
			CMDid : $(this).closest('.cmd').attr('data-cmd_id')
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
		$('#div_alert').showAlert({message: '{{La valeur de reset a bien été envoyée.}}', level: 'success'});
	}
});
});    
/* Fonction appelé pour mettre l'affichage à jour pour la sauvegarde en temps réel
 * _data: les détails des informations à sauvegardé
 */
function displayEqLogic(_data) {
	
}

/* Fonction appelé pour mettre l'affichage à jour de la sidebar et du container 
 * en asynchrone, est appelé en début d'affichage de page, au moment de la sauvegarde,
 * de la suppression, de la création
 * _callback: obligatoire, permet d'appeler une fonction en fin de traitement
 */
function updateDisplayPlugin(_callback) {
	$.ajax({
		type: "POST",
		url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php", // ne pas oublier de modifier pour le nom de votre plugin
		data: {
			action: "getAll"
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function (data) {
			//console.log(data);
			if (data.state !== 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}
			var htmlSideBar = '';
			var htmlContainer = '';
			// Le plus Geant - ne pas supprimer
			htmlContainer += '<div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
			htmlContainer += '<center>';
			htmlContainer += '<i class="fa fa-plus-circle" style="font-size : 7em;color:#94ca02;"></i>';
			htmlContainer += '</center>';
			htmlContainer += '<span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>Ajouter</center></span>';
			htmlContainer += '</div>';
			// la liste des équipements
			var eqLogics = data.result;
			for (var i  in eqLogics) {
				htmlSideBar += '<li class="cursor li_eqLogic" data-eqLogic_id="' + eqLogics[i].id + '"><a>' + eqLogics[i].humanSidebar + '</a></li>';
				// Définition du format des icones de la page principale - ne pas modifier
				htmlContainer += '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' + eqLogics[i].id + '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
				htmlContainer += "<center>";
				// lien vers l'image de votre icone
				htmlContainer += '<img src="plugins/jeedouino/doc/images/jeedouino_icon.png" height="105" width="95" />';
				htmlContainer += "</center>";
				// Nom de votre équipement au format human
				htmlContainer += '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' + eqLogics[i].humanContainer + '</center></span>';
				htmlContainer += '</div>';
			}
			$('#ul_eqLogicView').empty();
			$('#ul_eqLogicView').append(htmlSideBar);
			$('.eqLogicThumbnailContainer').remove();
			$('.eqLogicThumbnailDisplay legend').after($('<div class="eqLogicThumbnailContainer">').html(htmlContainer));
			$('.eqLogicThumbnailContainer').packery();
			$("img.lazy").lazyload({
				container: $(".eqLogicThumbnailContainer"),
				event : "sporty",
				skip_invisible : false
			});
			$("img.lazy").trigger("sporty");
			$("img.lazy").each(function () {
				var el = $(this);
				if (el.attr('data-original2') !== undefined) {
					$("<img>", {
						src: el.attr('data-original'),
						error: function () {
							$("<img>", {
								src: el.attr('data-original2'),
								error: function () {
									if (el.attr('data-original3') !== undefined) {
										$("<img>", {
											src: el.attr('data-original3'),
											error: function () {
												el.lazyload({
													event: "sporty"
												});
												el.trigger("sporty");
											},
											load: function () {
												el.attr("data-original", el.attr('data-original3'));
												el.lazyload({
													event: "sporty"
												});
												el.trigger("sporty");
											}
										});
									} else {
										el.lazyload({
											event: "sporty"
										});
										el.trigger("sporty");
									}
								},
								load: function () {
									el.attr("data-original", el.attr('data-original2'));
									el.lazyload({
										event: "sporty"
									});
									el.trigger("sporty");
								}
							});
						},
						load: function () {
							el.lazyload({
								event: "sporty"
							});
							el.trigger("sporty");
						}
					});
				} else {
					el.lazyload({
						event: "sporty"
					});
					el.trigger("sporty");
				}
			});
			if(_callback !== undefined)
				_callback();
			modifyWithoutSave = false;
		}
	});
}
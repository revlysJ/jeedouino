
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
RPIlocal = function()
{
  document.querySelector('.Alone').unseen();
  document.querySelector('.NotAlone').seen();
  document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=arduinoport] option[value="usblocal"]').selected = true;
  document.querySelector('.arduinoport.usbdeporte').unseen();
  document.querySelector('.arduinoport.usblocal').seen();
}
RPIalone = function()
{
  document.querySelector('.NotAlone').unseen();
  document.querySelector('.Alone').seen();
  document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=arduinoport] option[value="usbdeporte"]').selected = true;
  document.querySelector('.arduinoport.usblocal').unseen();
  document.querySelector('.arduinoport.usbdeporte').seen();
}
document.querySelector("#bt_graphEqLogic")?.addEventListener("click", function (event) {
  var eqId = document.querySelector('.eqLogicAttr[data-l1key=id]').jeeValue();
  jeeDialog.dialog({
    id: "graphEqLogic",
    title: "{{Graphique des relations de vos équipements}}",
    contentUrl: "index.php?v=d&modal=graph.link&filter_type=eqLogic&filter_id=" + eqId
  })
})
document.querySelector("#bt_exportEq")?.addEventListener("click", function (event) {
  var eqId = document.querySelector('.eqLogicAttr[data-l1key=id]').jeeValue();
  var board = document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=arduino_board]').jeeValue();
  jeeDialog.dialog({
    id: "exportEq",
    title: "{{Exportation de l'équipement}}",
    contentUrl: "index.php?v=d&plugin=jeedouino&modal=export&id=" + eqId  + '&board=' + board
  })
})
document.querySelector("#bt_conf_Pin")?.addEventListener("click", function (event) {
  var eqId = document.querySelector('.eqLogicAttr[data-l1key=id]').jeeValue();
  var board = document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=arduino_board]').jeeValue();
  jeeDialog.dialog({
    id: "conf_Pin",
    title: "{{Paramétrages / affectation des pins}}",
    contentUrl: "index.php?v=d&plugin=jeedouino&modal=conf_pin&id=" + eqId  + '&board=' + board
  })
})
document.querySelectorAll(".eqLogicAction[data-action=bt_healthSpecific]")?.forEach(el => el.addEventListener("click", function (event) {
  jeeDialog.dialog({
    id: "health",
    title: "{{Santé de vos équipements Jeedouino}}",
    contentUrl: "index.php?v=d&plugin=jeedouino&modal=health"
  })
}))
document.querySelectorAll(".eqLogicAction[data-action=bt_jeedouinoExt]")?.forEach(el => el.addEventListener("click", function (event) {
  jeeDialog.dialog({
    id: "jeedouinoExt",
    title: "{{Gestion de vos équipements distants JeedouinoExt}}",
    contentUrl: "index.php?v=d&plugin=jeedouino&modal=jeedouinoExt"
  })
}))
document.querySelector(".eqLogicAction[data-action=bt_docSpecific]")?.addEventListener("click", function (event) {
  window.open('https://revlysj.github.io/jeedouino/fr_FR/');
})
document.querySelector(".bt_plugin_view_log")?.addEventListener("click", function (event) {
  jeeDialog.dialog({
    id: "bt_plugin_view_log",
    title: "{{Logs de Jeedouino}}",
    contentUrl: "index.php?v=d&modal=log.display&log=" + this.getAttribute('data-log')
  })
})
document.querySelector(".sketchstab")?.addEventListener("click", function (event) {
  var board = document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=arduino_board]').value;
  var etv = document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').value;
  var eqId = document.querySelector('.eqLogic_active').getAttribute('v4_data-eqlogic_id');
  document.querySelectorAll('.sketchs').unseen();
  if (etv == 'usbarduino')
  {
    if (document.querySelector('.sketchUSB' + eqId)) document.querySelector('.sketchUSB' + eqId).seen();
    if (document.querySelector('.sketchLAN' + eqId)) document.querySelector('.sketchLAN' + eqId).unseen();
    if (document.querySelector('.sketchESP' + eqId)) document.querySelector('.sketchESP' + eqId).unseen();
  }
  else
  {
    if (board.substr(0, 1) == 'e')
    {
      if (document.querySelector('.sketchUSB' + eqId)) document.querySelector('.sketchUSB' + eqId).unseen();
      if (document.querySelector('.sketchLAN' + eqId)) document.querySelector('.sketchLAN' + eqId).unseen();
      if (document.querySelector('.sketchESP' + eqId)) document.querySelector('.sketchESP' + eqId).seen();
    }
    if (board.substr(0, 1) == 'a')
    {
      if (document.querySelector('.sketchUSB' + eqId)) document.querySelector('.sketchUSB' + eqId).unseen();
      if (document.querySelector('.sketchLAN' + eqId)) document.querySelector('.sketchLAN' + eqId).seen();
      if (document.querySelector('.sketchESP' + eqId)) document.querySelector('.sketchESP' + eqId).unseen();
    }
  }
})
document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]')?.addEventListener("change", function (event) {
  var board = document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=arduino_board]').value;
  if (board != '')
  {
    var etv = event.target.value;
    var eqId = document.querySelector('.eqLogic_active').getAttribute('v4_data-eqlogic_id');
    document.querySelectorAll('.datasource').unseen();
    document.querySelectorAll('.datasource.' + etv).seen();
    if (etv == 'usbarduino')
    {
      document.querySelector('.ActiveExt').seen();
    }
    else
    {
      document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=alone]').checked = false;
      RPIlocal();
      if (board.substr(0, 1) == 'e')
      {
        document.querySelector('.ActiveExt').unseen();
      }
      if (board.substr(0, 1) == 'a')
      {
        document.querySelector('.ActiveExt').unseen();
      }
    }
  }
});
document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=alone]')?.addEventListener("change", function (event) {
  if (event.target.checked == 0) { RPIlocal(); }
  else { RPIalone(); }
});
document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=arduino_board]')?.addEventListener("change", function (event) {
  var etv = event.target.value;
  var eqId = document.querySelector('.eqLogic_active').getAttribute('v4_data-eqlogic_id');
  if (etv == '')
  {
    document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=alone]').checked = false;
    document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').removeAttribute('disabled');
    document.querySelectorAll('.datasource').unseen();
    document.querySelector('.piFacePortID').unseen();
    document.querySelector('.piPlusPortI2C').unseen();
    document.querySelector('.esp8266').unseen();
    document.querySelector('.sketchstab').unseen();
    document.querySelector('.ActiveExt').unseen();
    document.querySelector('.Alone').unseen();
    document.querySelector('.NotAlone').unseen();
    document.querySelector('.UsbLan').unseen();
  }
  else if (etv.substr(0, 1) == 'p')
  {
    if (document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=alone]').value == 0)
    {
      RPIlocal();
    }
    else
    {
      RPIalone();
    }
    document.querySelector('.control').seen();
    document.querySelector('.UsbLan').seen();

    if (etv == 'piface') document.querySelector('.piFacePortID').seen();
    else document.querySelector('.piFacePortID').unseen();

    if (etv == 'piPlus') document.querySelector('.piPlusPortI2C').seen();
    else document.querySelector('.piPlusPortI2C').unseen();

    document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').jeeValue('rj45arduino');
    document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').setAttribute('disabled','disabled');
    document.querySelectorAll('.sketchs').unseen();
    document.querySelector('.sketchUSB').unseen();
    document.querySelector('.sketchsLib').unseen();
    document.querySelector('.esp8266').unseen();
    document.querySelector('.sketchstab').unseen();
    document.querySelector('.ActiveExt').seen();
  }
  else if (etv.substr(0, 1) == 'e')
  {
    RPIlocal();
    document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=alone]').checked = false;
    document.querySelector('.control').seen();
    document.querySelector('.UsbLan').seen();
    document.querySelector('.piFacePortID').unseen();
    document.querySelector('.piPlusPortI2C').unseen();
    document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').jeeValue('rj45arduino');
    document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').setAttribute('disabled','disabled');
    document.querySelectorAll('.sketchs').unseen();
    document.querySelector('.sketchsLib').seen();
    document.querySelector('.esp8266').seen();
    document.querySelector('.sketchstab').seen();
    document.querySelector('.ActiveExt').unseen();
  }
  else if (etv.substr(0, 1) == 'a')
  {
    if (document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').jeeValue() == 'usbarduino')
    {
      if (document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=alone]').checked)
      {
        RPIalone();
      }
      else
      {
        RPIlocal();
      }
    }
    else
    {
      document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=alone]').checked = false;
      RPIlocal();
    }

    document.querySelector('.control').seen();
    document.querySelector('.UsbLan').seen();
    if (eqId != undefined)
    {
      document.querySelector('.sketchsLib').seen();
      if (document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').jeeValue() != 'usbarduino')
      {
        document.querySelector('.ActiveExt').unseen();
        document.querySelectorAll('.datasource.rj45arduino').seen();
      }
      else
      {
        document.querySelector('.ActiveExt').seen();
        document.querySelectorAll('.datasource.usbarduino').seen();
      }
    }
    document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').removeAttribute('disabled');
    document.querySelector('.piFacePortID').unseen();
    document.querySelector('.piPlusPortI2C').unseen();
    document.querySelector('.esp8266').unseen();
    document.querySelector('.sketchstab').seen();
  }
  else
  {
    document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=alone]').checked = false;
    document.querySelector('.control').seen();
    document.querySelector('.UsbLan').seen();
    document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').removeAttribute('disabled');
    document.querySelector('.piFacePortID').unseen();
    document.querySelector('.piPlusPortI2C').unseen();
    document.querySelectorAll('.datasource ' + document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').jeeValue()).seen();
    document.querySelector('.esp8266').unseen();
    document.querySelector('.sketchstab').unseen();
    document.querySelector('.ActiveExt').unseen();
  }
});
document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=arduinoport]')?.addEventListener("change", function (event) {
  if (event.target.value == 'usblocal')
  {
    document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=alone]').checked = false;
    RPIlocal();
  }
  else
  {
    document.querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=alone]').checked = true;
    RPIalone();
  }
});


//$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

// $(".li_eqLogic2").on('click', function () {
//     $('.eqLogicDisplayCard[data-eqLogic_id="' + $(this).attr('data-eqLogic_id') + '"]').click();
// });

function prePrintEqLogic(id)
{
  document.querySelector('.eqLogic_active').setAttribute('v4_data-eqLogic_id', id);
  // if($('.li_eqLogic2[data-eqLogic_id=' + id + ']').html() != undefined)
  // {
  //     $('.li_eqLogic2').removeClass('active');
  //     $('.li_eqLogic2[data-eqLogic_id=' + id + ']').addClass('active');
  // }
}
function printEqLogic(_data)
{
  var control = init(_data.logicalId);
  if (control == 'JeedouinoControl')
  {
    document.querySelectorAll('.control').unseen();
    document.querySelectorAll('.datasource').unseen();
    document.querySelector('.piFacePortID').unseen();
    document.querySelector('.piPlusPortI2C').unseen();
    document.querySelector('.esp8266').unseen();
    document.querySelector('.sketchstab').unseen();
    document.querySelector('.ActiveExt').unseen();
    document.querySelectorAll(".eqLogicAction[data-action=save]").unseen();
    document.querySelectorAll(".eqLogicAction[data-action=remove]").unseen();
    document.querySelectorAll(".eqLogicAction[data-action=copy]").unseen();
  }
  else
  {
    document.querySelectorAll('.control').seen();
    document.querySelectorAll(".eqLogicAction[data-action=save]").seen();
    document.querySelectorAll(".eqLogicAction[data-action=remove]").seen();
    document.querySelectorAll(".eqLogicAction[data-action=copy]").seen();
  }
}
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = {configuration: {}};
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {};
  }
  var control = init(_cmd.configuration.control);
  if (control == 'JeedouinoControl')
  {
    document.querySelectorAll('.control').unseen();
    document.querySelectorAll('.datasource').unseen();
    document.querySelector('.piFacePortID').unseen();
    document.querySelector('.piPlusPortI2C').unseen();
    document.querySelector('.esp8266').unseen();
    document.querySelector('.sketchstab').unseen();
    document.querySelector('.ActiveExt').unseen();
    document.querySelectorAll(".eqLogicAction[data-action=save]").unseen();
    document.querySelectorAll(".eqLogicAction[data-action=remove]").unseen();
    document.querySelectorAll(".eqLogicAction[data-action=copy]").unseen();
  }
  else
  {
    document.querySelectorAll('.control').seen();
    document.querySelectorAll(".eqLogicAction[data-action=save]").seen();
    document.querySelectorAll(".eqLogicAction[data-action=remove]").seen();
    document.querySelectorAll(".eqLogicAction[data-action=copy]").seen();
  }
  var ctype = init(_cmd.type);
  var stype = init(_cmd.subType);
  var mtype = init(_cmd.configuration.modePIN);
  var gtype = init(_cmd.generic_type);
  var pins_id = init(_cmd.configuration.pins_id);
  if (pins_id>999) pins_id -= 1000;
  if (pins_id>999) pins_id -= 1000;
  if (pins_id>999) pins_id -= 1000;

  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
  tr += '<td>';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
  tr += '<div class="input-group">'
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom}}">';
  tr += '<span class="input-group-btn">'
  tr += '<a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a>'
  tr += '</span>'
  tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
  tr += '</div>'
  if (control == 'JeedouinoControl')
  {
    if (init(_cmd.logicalId) != 'refresh') tr +=  '<span class="label label-info">eqID: ' + init(_cmd.configuration.boardid) + '</span>';
    else tr +=  '<span class="label label-info">' + control + '</span>';
  }
  /*     tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="Action Value = ID Info">';
  tr += '<option value="">Aucune</option>';
  tr += '</select>';	 */
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="pins_id" disabled style="display : none;">';
  if (control != 'JeedouinoControl') tr +=  '<span class="label label-info" title="Pin mode : ' + mtype + '"><i class="fas fa-question-circle tooltips"></i> PIN No : ' + pins_id + '</span>';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="modePIN" disabled style="display : none;">';
  tr += '</td>';
  tr += '<td>';
  tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="' + ctype + '" disabled style="display : none;" />';
  tr += '<input class="cmdAttr form-control  input-sm" data-l1key="subType" value="' + stype + '" disabled style="display : none;" />';
  if (ctype == 'action')
  {
    tr += '<div class="label label-warning" style="text-transform: uppercase;">' + ctype + ' ( ' + stype + ' ) </div></br>';
  } else {
    tr += '<div class="label label-success" style="text-transform: uppercase;">' + ctype + ' ( ' + stype + ' ) </div></br>';
  }
  tr += '<div class="label label-primary" >' + gtype + '</div>';
  tr += '</td>';
  tr += '<td>';
  if (control != 'JeedouinoControl')
  {
    if (ctype == 'action') {
      switch(mtype)
      {
        case 'output_other':
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" >';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" style="display : none;">';
        break;
        case 'Send2LCD':
        tr += '<span class="label label-warning">{{16x2 Caractères max.}}</span>';
        case 'output_message':
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="title" style="display : none;">';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="message" style="display : none;">';
        break;
        case 'high_pulse_slide':
        case 'low_pulse_slide':
        tr += '<div class="label label-warning">{{Durée en dixième de secondes. 5 Chiffres max.}}</div><br>';
        case 'resetcpt':
        tr += '<span class="label label-success">{{Valeur affectée via scénario}}</span>';
        case 'switch':
        case 'none':
        case 'low_pin_all':
        case 'high_pin_all':
        case 'teleinfoTX':
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" style="display : none;">';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" style="display : none;">';
        break;
        case 'high_relais':
        case 'low_relais':
        case 'teleinfoRX':
        case 'output_slider':
        case 'WSmode':
        case 'WS2811':
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" disabled>';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" style="display : none;">';
        break;
        case 'high_pulse':
        case 'low_pulse':
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" disabled>';
        tr += '<span class="label label-warning">{{Durée en dixième de secondes. 5 Chiffres max.}}</span>';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" >';
        break;
        case 'trigger':
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" disabled>';
        tr += '<span class="label label-info">{{Numéro de la PIN ECHO correspondante.}}</span>';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" >';
        break;
        case 'output':
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" >';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" style="display : none;">';
        break;
        case 'pwm_output':
        case 'pwm_outputPI':
        case 'servo':
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" >';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" style="display : none;">';
        break;
        case 'double_pulse_low':
        case 'double_pulse_high':
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" disabled>';
        tr += '<span class="label label-warning">{{Durées clic + pause en dixième de s. 3 + 3 Chiffres.}}</span>';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" >';
        break;
        default:
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" >';
        tr += '<span class="label label-warning">{{Durée en dixième de secondes. 5 Chiffres max.}}</span>';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="tempo" >';
      }
    }
    else if (ctype == 'info')
    {
      if (mtype == 'compteur_pullup' || mtype == 'compteur_pulldown')
      {
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" disabled>';
        tr += '<a class="btn btn-warning btn-xs cmdAction" data-action="ResetCPT"><i class="fas fa-rss"></i> {{MàJ compteur avec valeur ci-dessous:}}</a>';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="RSTvalue" >';
      }
      else if (mtype == 'ds18b20')
      {
        var ds18b20 = init(_cmd.configuration.ds18id);
        if (ds18b20 != '') tr += '<div class="label label-info" ><i class="fas fa-fingerprint"></i> ID: ' + ds18b20 + '</div>';
      }
    }
  }
  tr += '</td>';
  tr += '<td>';
  tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr " data-l1key="isVisible" data-size="mini" checked />{{Afficher}}</label></span><br> ';
  if (ctype == 'info') {
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr " data-l1key="isHistorized" data-size="mini" />{{Historiser}}</label></span><br> ';
  }
  //if ( mtype != 'ds18b20' && mtype.substr(0, 3) != 'dht' && mtype.substr(0, 3) != 'bmp' && mtype.substr(0, 3) != 'bme' )
  if (ctype == 'info' && stype == 'binary' && control != 'JeedouinoControl')
  {
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary" data-size="mini" />{{Inverser}}</label></span><br> ';
  }
  tr += '</td>';
  tr += '<td>';
  if (ctype == 'info' && stype == 'binary' && control != 'JeedouinoControl')
  {
    tr += '<span ><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinare" data-size="mini" />{{Inverser}}</label></span> ';
  }
  if (mtype != 'teleinfoRX')
  {
    tr += '<input class="cmdAttr form-control tooltips input-sm" data-l1key="unite" style="width : 100px;" placeholder="Unité" title="{{Unité}}">';
  }
  // tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'
  tr += '</td>';
  tr += '<td>';
  tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
  if (mtype != 'teleinfoTX' && mtype != 'resetcpt')
  {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
  }
  if (control != 'JeedouinoControl') tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
  tr += '</td>';
  tr += '</tr>';

  let newRow = document.createElement('tr')
  newRow.innerHTML = tr
  newRow.addClass('cmd')
  newRow.setAttribute('data-cmd_id', init(_cmd.id))
  document.getElementById('table_cmd').querySelector('tbody').appendChild(newRow)

  jeedom.eqLogic.buildSelectCmd({
    id: document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue(),
    filter: { type: 'info' },
    error: function(error) {
      jeedomUtils.showAlert({ message: error.message, level: 'danger' })
    },
    success: function(result) {
      //newRow.querySelector('.cmdAttr[data-l1key="value"]').insertAdjacentHTML('beforeend', result)
      newRow.setJeeValues(_cmd, '.cmdAttr')
      jeedom.cmd.changeType(newRow, init(_cmd.subType))
    }
  })
}

document.getElementById('table_cmd').addEventListener('click', function(event) {
  var _target = null
  if (_target = event.target.closest('.cmd .cmdAction[data-action=ResetCPT]')) {
    jeedomUtils.showAlert({message: '{{Envoi en cours...}}', level: 'info'});
    var RSTvalue = _target.closest('.cmd').querySelector('.cmdAttr[data-l1key=configuration][data-l2key=RSTvalue]').jeeValue();
    domUtils.ajax({
      type: "POST",
      url: "plugins/jeedouino/core/ajax/jeedouino.ajax.php",
      data: {
        action: "ResetCPT",
        boardid : _target.closest('.eqLogic_active').getAttribute('v4_data-eqLogic_id'),
        RSTvalue : RSTvalue,
        CMDid : _target.closest('.cmd').getAttribute('data-cmd_id')
      },
      dataType: 'json',
      global: false,
      error: function (request, status, error) {
        domUtils.handleAjaxError(request, status, error);
      },
      success: function (data) { // si l'appel a bien fonctionné
        if (data.state != 'ok') {
          jeedomUtils.showAlert({message: data.result, level: 'danger'});
          return;
        }
        jeedomUtils.showAlert({message: '{{La valeur de reset a bien été envoyée.}} -> ' + RSTvalue, level: 'success'});
      }
    })
    return
  }
})

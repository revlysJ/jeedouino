<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom and the jeedouino plugin are distributed in the hope that they will be useful
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
sendVarToJS('eqType', 'jeedouino');
$eqLogics = eqLogic::byType('jeedouino');

$cpl = jeedouino::GetJeedomComplement();
//include_file('desktop', 'jeedouino', 'css', 'jeedouino');
//
$eqLogicsHTML = "";
$eqLogicsEXT = "";
foreach ($eqLogics as $eqLogic)
{
    $ModeleArduino = $eqLogic->getConfiguration('arduino_board');
    $icon = 'lan';
    switch(substr($ModeleArduino, 0, 1))
    {
        case 'a':
            if ($eqLogic->getConfiguration('datasource') == 'usbarduino') $icon = 'usb';
            break;
        case 'e':
            $icon = 'wifi';
    }
    $style = 'style="background-image: url(plugins/jeedouino/icons/' . $icon . '.png);background-repeat: no-repeat;"';
    $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
    $JExtname = trim(config::byKey('JExtname-' . $eqLogic->getConfiguration('iparduino'), 'jeedouino', ''));
    $HTML = '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="' . $opacity . '" title="' . $JExtname . '">';
    $HTML .= "<center>";
    if (!file_exists(dirname(__FILE__) . '/../../icons/jeedouino_' . $ModeleArduino . '.png'))
    {
        $ModeleArduino = 'icon';
        $style = '';
    }
    $HTML .= '<img src="plugins/jeedouino/icons/jeedouino_' . $ModeleArduino . '.png" ' . $style . '/>';

    $HTML .= "</center>";
    $HTML .= '<span class="name" style="color:#00979C"><br><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
    $HTML .= '</div>';
    if ($eqLogic->getConfiguration('alone') == '1') $eqLogicsEXT .= $HTML;
    else $eqLogicsHTML .= $HTML;
}
if (config::byKey('ShowSideBar', 'jeedouino', false)) $ShowSideBar = "col-lg-10 col-md-9 col-sm-8";
else $ShowSideBar = "col-xs-12";
?>

<div class="row row-overflow">
<?php if (config::byKey('ShowSideBar', 'jeedouino', false))
{
?>
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-warning eqLogicAction pull-left" data-action="gotoPluginConf" title="{{Configuration avancée de l'équipement}}"><i class="fas fa-wrench"></i></a>
                <?php if (config::byKey('ActiveExt', 'jeedouino', false)) { ?>
                      <a class="btn btn-warning eqLogicAction pull-left" data-action="bt_jeedouinoExt"  title="{{Configuration des JeedouinoExt}}"><i class="fas fa-screwdriver"></i></a>
                <?php } ?>
                <a class="btn btn-info eqLogicAction pull-left bt_plugin_view_log" data-slaveid="-1" data-log="jeedouino" title="{{Logs du plugin}}"><i class="fas fa-file"></i></a>
                <a class="btn btn-info eqLogicAction pull-left" data-action="bt_healthSpecific" title="{{Page de Santé du plugin}}"><i class="fas fa-medkit"></i></a>
                <a class="btn btn-default eqLogicAction" style="margin-top : 5px;margin-bottom: 5px;" data-action="add">
                    <i class="fas fa-plus-circle"></i> {{Ajouter un jeedouino}} <!-- changer pour votre type d'équipement -->
                </a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
				foreach ($eqLogics as $eqLogic)
				{
					$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
					echo '<li class="cursor li_eqLogic2" data-eqLogic_id="' . $eqLogic->getId() . '" style="' . $opacity . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
				}
				?>
            </ul>
            <ul id="ul_eqLogicView" class="nav nav-pills nav-stacked"></ul> <!-- la sidebar -->
        </div>
    </div>
<?php } ?>

    <div class="<?php echo $ShowSideBar;?> eqLogicThumbnailDisplay">
        <legend><i class="fas fa-cog"></i> {{Gestion}}</legend> <!-- changer pour votre type d'équipement -->

		<div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicAction" data-action="add" >
    			<center>
    				<i class="fas fa-plus-circle" style="font-size: 38px !important;color:#00979C;"></i>
    			</center>
    			<span style="color:#00979C"><center>{{Ajouter}}</center></span>
			</div>
			<div class="cursor eqLogicAction" data-action="gotoPluginConf" >
				<center>
					<i class="fas fa-wrench" style="font-size: 38px !important;color:#00979C;"></i>
				</center>
				<span style="color:#00979C"><center>{{Configuration}}</center></span>
    		</div>
			<div class="cursor eqLogicAction" data-action="bt_healthSpecific" >
				<center>
					<i class="fas fa-medkit" style="font-size: 38px !important;color:#00979C;"></i>
				</center>
				<span style="color:#00979C"><center>{{Santé}}</center></span>
			</div>
			<div class="cursor eqLogicAction" data-action="bt_docSpecific" >
				<center>
					<i class="fas fa-book" style="font-size: 38px !important;color:#00979C;"></i>
				</center>
				<span style="color:#00979C"><center>{{Documentation}}</center></span>
			</div>
<?php
    if (config::byKey('ActiveExt', 'jeedouino', false))
    {
?>
            <div class="cursor eqLogicAction" data-action="bt_jeedouinoExt" >
				<center>
					<i class="fas fa-screwdriver" style="font-size: 38px !important;color:#00979C;"></i>
				</center>
				<span style="color:#00979C"><center>{{JeedouinoExt}}</center></span>
			</div>
<?php
    }
?>
		</div>
        <input class="form-control" placeholder="{{Rechercher}}" style="margin-bottom:4px;" id="in_searchEqlogic" />
		<legend><i class="fas fa-table"></i> {{Mes équipements Jeedouino}}</legend>
		<div class="eqLogicThumbnailContainer">
		<?php
            echo $eqLogicsHTML;
            echo '</div>';

            if ($eqLogicsEXT != '')
            {
                echo '<legend><i class="fas fa-table"></i> {{Mes équipements sur JeedouinoEXT}}</legend>';
                echo '<div class="eqLogicThumbnailContainer">';
                echo $eqLogicsEXT;
                echo '</div>';
            }
        ?>
	</div>
    <!-- Affichage de l'eqLogic sélectionné -->
    <div class="<?php echo $ShowSideBar;?> eqLogic eqLogic_active" data-eqLogic_id = "" style="display: none;">
		<div style="padding-bottom:40px;">
			<a class="btn btn-success eqLogicAction pull-right" data-action="save"  title="{{Sauver et/ou Générer les commandes automatiquement}}"><i class="fas fa-check-circle"></i> {{Sauver / Générer}}</a>
			<a class="btn btn-danger eqLogicAction pull-right" data-action="remove" title="{{Supprimer l'équipement}}"><i class="fas fa-minus-circle"></i> </a>
			<a class="btn btn-warning eqLogicAction pull-right" data-action="copy" title="{{Dupliquer cet équipement}}"><i class="fas fa-copy"></i> </a>
			<!-- <a class="btn btn-default pull-right" id="bt_exportEq" title="{{Exporter cet équipement}}}"><i class="fas fa-share"></i> </a> -->
			<a class="btn btn-default pull-right" id="bt_graphEqLogic" title="{{Graphique de liens}}"><i class="fas fa-object-group"></i> </a>

			<a class="btn btn-default eqLogicAction pull-right" data-action="configure" title="{{Configuration avancée de l'équipement}}"><i class="fas fa-cogs"></i> </a>
			<a class="btn btn-default eqLogicAction pull-right" data-action="gotoPluginConf"  title="{{Page de Configuration du plugin}}"><i class="fas fa-wrench"></i> </a>
<?php if (config::byKey('ActiveExt', 'jeedouino', false)) { ?>
      <a class="btn btn-warning eqLogicAction pull-right" data-action="bt_jeedouinoExt"  title="{{Configuration des JeedouinoExt}}"><i class="fas fa-screwdriver"></i> </a>
<?php } ?>
			<a class="btn btn-info eqLogicAction pull-right" data-action="bt_healthSpecific" title="{{Page de Santé du plugin}}"><i class="fas fa-medkit"></i> </a>
			<a class="btn btn-info eqLogicAction pull-right bt_plugin_view_log" data-slaveid="-1" data-log="jeedouino" title="{{Logs du plugin}}"><i class="fas fa-file"></i> </a>
			<a href="https://revlysj.github.io/jeedouino/fr_FR/" target="_blank" class="btn btn-success eqLogicAction pull-right"  title="{{Lien vers la Documentation du plugin}}"><i class="fas fa-book"></i> </a>
	      </div>

		<ul class="nav nav-tabs">
			<li><a href="#" class="eqLogicAction" aria-controls="home" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li class="active"><a href="#eqlogictab" aria-controls="home" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li><a href="#commandtab" aria-controls="profile" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
			<li class="control pintab"><a href="#pinstab" aria-controls="profile" data-toggle="tab"><i class="fas fa-wrench"></i> {{Pins / GPIO}}</a></li>
			<li class="sketchstab"><a href="#sketchstab" aria-controls="profile" data-toggle="tab"  ><i class="fas fa-code"></i> {{Sketchs}}</a></li>
		</ul>

		<div class="tab-content" style="height:calc(100% - 80px);overflow:auto;overflow-x: hidden;">
			<div class="tab-pane active" id="eqlogictab">
			<br>

        <form class="form-horizontal">
            <fieldset>
<legend><i class="fas fa-user-cog"></i> {{Paramètres Jeedom}}</legend>
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Nom de l'équipement jeedouino}}</label>
                    <div class="col-sm-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
						<input type="text" class="eqLogicAttr form-control" data-l1key="Original_ID" style="display : none;" />
                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement jeedouino}}"/>
                        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="LogicalId" style="display : none;"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                    <div class="col-sm-3">
                        <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                            <option value="">{{Aucun}}</option>
                            <?php
                            foreach (jeeObject::all() as $object) {
                                echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="control form-group">
                    <label class="col-sm-3 control-label">{{Catégorie}}</label>
                    <div class="col-sm-9">
                        <?php
                        $n = 0;
                        foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                            echo '<label class="checkbox-inline ">';
                            echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                            echo '</label>';
                            $n++;
                            if ($n >= 4)
                            {
                                $n = 0;
                                echo '</div></div><div class="control form-group"><label class="col-sm-3 control-label"> </label><div class="col-sm-9">';
                            }
                        }
                        ?>
                    </div>
                    <br><br>
                </div>
                <div class="form-group">
				  <label class="col-sm-3 control-label"> </label>
				  <div class="col-sm-9">
					<label class="control checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activé?}}</label>
					<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible?}}</label>

					<?php if (config::byKey('ActiveExt', 'jeedouino', false))
					{
					?>
					<label class="checkbox-inline ActiveExt"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="alone"/>{{RPI sans Jeedom*}}</label>
					<?php }  ?>
					</div>
                </div>
<legend class="control"><i class="fas fa-cogs"></i> {{Paramètres Matériel}}</legend>
                <div class="control form-group">
                    <label class="col-sm-3 control-label">Modèle de la carte </label>
                    <div class="col-sm-3">
                        <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="arduino_board">
                            <option value="">Aucune</option>
							<optgroup label="Pour Arduino">
                            <option value="auno">Arduino UNO 328</option>
							<option value="a2009">Arduino DUEMILLANOVE 328</option>
                            <option value="anano">Arduino NANO 328</option>
                            <option value="a1280">Arduino MEGA 1280</option>
                            <option value="a2560">Arduino MEGA 2560</option>
							</optgroup>
							<optgroup label="Pour Raspberry PI">
                            <option value="piface">PiFace Digital</option>
                            <option value="piGPIO26">Raspberry PI A/B GPIO</option>
                            <option value="piGPIO40">Raspberry PI2/3 A+/B+ GPIO</option>
							<option value="piPlus" >AB-Elec. IO Pi plus / MCP23017</option>
							</optgroup>
							<optgroup label="Pour ESP826x">
                            <option value="esp01" >ESP8266-01</option>
							<option value="esp07" >ESP8266-All I/O (Pour tests)</option>
							<option value="espMCU01" >NodeMCU / Wemos</option>
                            <option value="espsonoffpow">SONOFF POW (Pour tests)</option>
                            <option value="espsonoff4ch">SONOFF 4CH (Pour tests)</option>
                            <option value="esp32dev">ESP32 Dev (Pour tests)</option>
							<option value="espElectroDragonSPDT">ElectroDragon 2CH (Pour tests)</option>
							</optgroup>
                        </select>
                    </div>
                </div>
				 <div class="control UsbLan form-group">
					 <label class="col-sm-3 control-label">{{Type de connection de la carte}}</label>
					 <div class="col-sm-3">
						<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="datasource">
							<option value="usbarduino">{{USB}}</option>
							<option value="rj45arduino">{{Réseau}}</option>
						</select>
					</div>
				</div>
				<div class="datasource usbarduino">
					<div class="form-group">
						<label class="col-sm-3 control-label">{{Localisation de la carte}}</label>
						<div class="col-sm-3">
							<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="arduinoport">
								<option value="usblocal">{{Local (sur ce Jeedom)}}</option>
								<option value="usbdeporte">{{Déporté (sur un JeedouinoExt)}}</option>
							</select>
						</div>
					</div>

					<div class="form-group arduinoport usblocal">
						<label class="col-sm-3 control-label">{{Port USB de connection locale}}</label>
						<div class="col-sm-3">
							<select class="eqLogicAttr form-control"  data-l1key="configuration" data-l2key="portusblocal">
								<option value="none">{{Aucun}}</option>
								<optgroup label="Detection par Jeedom">
                                    <?php
                                    foreach (jeedom::getUsbMapping('', true) as $name => $value)
                                    {
                                        echo '<option value="' . $name . '">' . $name . ' (' . $value . ')</option>';
                                    }
                                    ?>
									</optgroup>
								<optgroup label="Detection par Jeedouino">
                                    <?php
                                    foreach (jeedouino::getUsbMapping() as $name => $value)
                                    {
                                        echo '<option value="' . $name . '">' . $name . ' (' . $value . ')</option>';
                                    }
                                    ?>
									</optgroup>
							</select>
						</div>
					</div>
					<div class="form-group arduinoport usbdeporte">
                        <label class="col-lg-3 control-label">{{Port USB de connection déportée}}</label>
                        <div class="col-lg-3">
                            <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="portusbdeporte">
                                <option value="none">{{Aucun}}</option>
                                <?php
                                $plugin_jeedouino_deporte=false;

								// Si JeedouinoExt activé
								if (config::byKey('ActiveExt', 'jeedouino', false))
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
										foreach ($ListExtIP as $ip)
										{
											echo '<optgroup label="JeedouinoExt sur '.$ip.'">';
											$plugin_jeedouino_deporte=true;
											$UsbMap=config::byKey('uMap-'.$ip, 'jeedouino', '');
											if (is_array($UsbMap))
											{
												foreach ($UsbMap as $name => $value)
												{
													echo '<option value="'.$ip.'_' . $name . '">' . $name . ' (' . $value . ')</option>';
												}
											}

											echo '</optgroup>';
										}
									}
								}
                                ?>
                            </select>
                        </div>
				<?php

			if (!$plugin_jeedouino_deporte)
			{
				?>
				<legend class="col-lg-6 btn btn-warning btn-sm">{{Aucun plugin Jeedouino déporté ( Jeedom esclave ) trouvé.}}</legend>
				<?php
			}
	//	}
		?>
					</div>
				</div>
				<div class="datasource rj45arduino">
				   <div class="form-group">
						<label class="col-sm-3 control-label">{{Adresse IP de la carte }}</label>
						<div class="col-sm-3">
							<input type="text" list="jeeReseau" class="NotAlone eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="iparduino" placeholder="ex : 192.168.0.55"/>
                            <datalist id="jeeReseau">
<?php
    echo '<option value="' .$_SERVER["SERVER_ADDR"]. '" >{{Ce Jeedom }}</option>';
?>
                            </datalist>

                            <select class="Alone eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="iparduino2">
<?php
	// Sur JeedouinoExt (sans Jeedom)
	// iparduino2 mis dans iparduino dans la class method presave()
	$ListExtIP = config::byKey('ListExtIP', 'jeedouino', '');
	if ($ListExtIP != '')
	{
        echo '<option value="" >' . __('Veuillez choisir un JeedouinoExt', __FILE__) . '</option>';
		foreach ($ListExtIP as $ip)
		{
            echo '<optgroup label="JeedouinoExt sur ' . $ip . '">';
            $JExtname = trim(config::byKey('JExtname-' . $ip, 'jeedouino', 'JeedouinoExt'));
			echo '<option value="' . $ip . '">' . $JExtname . '</option>';
            echo '</optgroup>';
		}
	}
    else
    {
        echo '<option value="" >' . __('Veuillez créer un JeedouinoExt avant', __FILE__) . '</option>';
    }
?>
                            </select>
						</div>
					</div>
				</div>
				<div class="esp8266">
				   <div class="form-group">
						<label class="col-sm-3 control-label">{{SSID WIFI}}</label>
						<div class="col-sm-2">
							<input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="wifi_ssid" placeholder="Mon wifi"/>
						</div>
						<label class="col-sm-2 control-label">{{Mot de passe WIFI}}</label>
						<div class="col-sm-2">
							<input type="password" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="wifi_pass" placeholder="Mot de passe"/>
						</div>
					</div>
				</div>
                <div class="piFacePortID form-group">
					 <label class="col-sm-3 control-label" >{{Numéro de la carte piFace}}</label>
						<div class="col-sm-3">
							<input type="number" min="0" max="3" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="PortID" placeholder="{{ 0 par défaut, 0 à 3 si PiRack }}"  title="{{ 0 par défaut, 0 à 3 si PiRack }}"/>
						</div>
				</div>
				<div class="piPlusPortI2C form-group">
					<label class="col-sm-3 control-label" >{{Adresse I2C du MCP23017}}</label>
					<div class="col-sm-3">
					   <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="PortI2C">
							<option value="32" id="select_i2c_board">0x20 (32) </option>
							<option value="33" id="select_i2c_board">0x21 (33) </option>
							<option value="34" id="select_i2c_board">0x22 (34) </option>
							<option value="35" id="select_i2c_board">0x23 (35) </option>
							<option value="36" id="select_i2c_board">0x24 (36) </option>
							<option value="37" id="select_i2c_board">0x25 (37) </option>
							<option value="38" id="select_i2c_board">0x26 (38) </option>
							<option value="39" id="select_i2c_board">0x27 (39) </option>
							</optgroup>
						</select>
					</div>
				</div>
<legend><i class="fas fa-cog"></i> {{Paramètres facultatifs}}</legend>
            <div class="datasource rj45arduino">
               <div class="form-group">
                    <label class="col-sm-3 control-label">{{Port réseau libre}}</label>
                    <div class="col-sm-3">
                        <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="ipPort" placeholder="ex : <?php echo jeedouino::GiveMeFreePort('ipPort'); ?>"/>
                    </div>
                </div>
            </div>
            <div class="datasource usbarduino">
                <div class="form-group arduinoport usblocal usbdeporte">
                    <label class="col-sm-3 control-label">{{Port réseau libre pour le démon}}</label>
                    <div class="col-sm-3">
                        <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="PortDemon" placeholder="ex : <?php echo jeedouino::GiveMeFreePort('PortDemon'); ?>"/>
                    </div>
                </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Memento}}</label>
              <div class="col-sm-3">
                <textarea class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="memento" placeholder="Vos notes personnelles"></textarea>
              </div>
            </div>

            </fieldset>
        </form>

			</div>
		<div class="tab-pane" id="commandtab">

        <legend><i class="fas fa-list-alt"></i> {{Commandes générées}}</legend>
			<form class="form-horizontal">
				<div class="control form-group">
				  <label class="col-sm-3 control-label"></label>
				  <div class="col-sm-9">
					<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr configuration" data-l1key="configuration" data-l2key="AutoOrder" checked/> {{Ordonner automatiquement les commandes par numéro de pin.}}</label>
				  </div>
				</div>
			</form>
		<br>
        <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th>{{Nom}}</th><th>{{Types}}</th><th>{{Paramètres}}</th><th>{{Affichage}}</th><th>{{Valeur}}</th><th>{{Plus}}</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
		</div>

		<div class="tab-pane" id="pinstab">
			<br><br>
			<div class="form-group alert alert-warning">
				<center>
                    <h4>
                        {{Note : Il est nécéssaire de configurer l'équipement et de le sauvegarder avant de pouvoir configurer les pins de celui-ci}}
                    </h4>
                    <a class="btn btn-info" href="#pinstab" aria-controls="profile" data-toggle="tab"  id="bt_conf_Pin"><i class="fas fa-wrench"></i> {{Pins / GPIO}}</a>
                </center>
			</div>
            <br><br>

				<?php	if (config::byKey('ActiveUserCmd', 'jeedouino', false))
				{ ?>
				<form class="form-horizontal sketchstab">
					<div class="form-group">
						<label class="col-sm-3 control-label"></label>
						<div class="col-sm-9">
							<label class="checkbox-inline"><input type="number" class="eqLogicAttr configuration" data-l1key="configuration" data-l2key="UserPinsMax" placeholder="0 à 100 max." min="0" max="100"/> {{Nombre de pins utilisateur (Arduinos/Esp...) 0 - 100 max}}</label>
						</div>
					</div>
				</form>
				<table class="table table-striped sketchstab">
					<tr>
						<td>Pins Utilisateur</td>
						<td>{{Ajoute autant de pins utilisateur (que le nombre choisi) à la liste des pins configurables dans l'onglet <i class="fas fa-wrench"></i> Pins/GPIO.}}</td>
					</tr>
				</table>
                <br><br>
		<?php
				} ?>
			<form class="form-horizontal">
				<div class="form-group">
				  <label class="col-sm-3 control-label"></label>
				  <div class="col-sm-9">
					<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr configuration" data-l1key="configuration" data-l2key="ActiveCmdAll" checked/> {{Création automatique des commandes génériques ALL_*}}</label>
				  </div>
				</div>
			</form>
			<table class="table table-striped">
				<tr>
				  <td>ALL_LOW</td>
				  <td>{{Met toutes les pins output à LOW}}</td>
				</tr>
				<tr>
				  <td>ALL_HIGH</td>
				  <td>{{Met toutes les pins output à HIGH}}</td>
				</tr>
				<tr>
				  <td>ALL_SWITCH</td>
				  <td>{{Inverse toutes les pins output}}</td>
				</tr>
				<tr>
				  <td>ALL_PULSE_LOW</td>
				  <td>{{Met toutes les pins output à LOW pendant le temps choisi}}</td>
				</tr>
				<tr>
				  <td>ALL_PULSE_HIGH</td>
				  <td>{{Met toutes les pins output à HIGH pendant le temps choisi}}</td>
				</tr>
			</table>
		</div>

			<div class="tab-pane" id="sketchstab">
			<br><br><br>
					<?php
					$jeedouinoPATH = realpath(dirname(__FILE__) . '/../../sketchs/');
					$ArduinoEspTag = false;
					foreach ($eqLogics as $eqLogic)
					{
						$board_id = $eqLogic->getId();
						$ModeleArduino = $eqLogic->getConfiguration('arduino_board');
						if (substr($ModeleArduino,0,1)=='a')
						{
							$ArduinoEspTag = true;
							$SketchFileName = $jeedouinoPATH . '/JeedouinoLAN_' . $board_id . '.ino';
							$SketchFileNameUSB = $jeedouinoPATH . '/JeedouinoUSB_' . $board_id . '.ino';
							if (file_exists($SketchFileName))
							{
								echo '<div class="form-group sketchs sketchLAN'.$board_id.' " style="display : none;">
								<label class="col-sm-2 control-label">{{ Sketch }}</label>
								<div class="col-sm-10">
									<a href="plugins/jeedouino/sketchs/JeedouinoLAN_'.$board_id.'.ino" class="btn btn-info" target="_blank" download><i class="fas fa-download"></i>{{ Télécharger le Sketch* à mettre dans l\'arduino (Réseau) pour cet équipement.}}</a>
									<br><i>/!\ Le sketch est spécifiquement généré pour cet équipement !</i>
									<br>
									<br><i>Note : Ce sketch est prévu pour les shields réseaux basés sur un chip W5100.</i>
									<br><i> Pour un chip ENC28J60 (ou autre), il faudra modifier/adapter le sketch.</i>
									<br>
								</div></div>';
							}
							elseif (file_exists($SketchFileNameUSB))
							{
								echo '<div class="form-group sketchs sketchUSB' . $board_id . ' " style="display : none;">
								<label class="col-sm-2 control-label">{{ Sketch }}</label>
								<div class="col-sm-10">
									<a href="plugins/jeedouino/sketchs/JeedouinoUSB_' . $board_id . '.ino" class="btn btn-info" target="_blank" download><i class="fas fa-download"></i>{{ Télécharger le Sketch* à mettre dans l\'arduino (Usb) pour cet équipement.}}</a>
									<br><i>/!\ Le sketch est spécifiquement généré pour cet équipement !</i>
									<br>
								</div></div>';
							}
							else
							{
								echo '<div class="form-group sketchs sketchLAN'.$board_id.' " style="display : none;">
								<label class="col-sm-2 control-label">{{ Sketch }}</label>
								<div class="col-sm-6">
									<i>/!\ Merci de réactualiser la page (F5) après la sauvegarde pour avoir le lien du sketch !</i>
								</div></div>';
							}
						}
						elseif (substr($ModeleArduino,0,1)=='e')
						{
							$ArduinoEspTag = true;
							$SketchFileName=$jeedouinoPATH.'/JeedouinoESP_'.$board_id.'.ino';
							if (file_exists($SketchFileName))
							{
								echo '<div class="form-group sketchs sketchESP'.$board_id.' " style="display : none;">
								<label class="col-sm-2 control-label">{{ Sketch }}</label>
								<div class="col-sm-6">
									<a href="plugins/jeedouino/sketchs/JeedouinoESP_'.$board_id.'.ino" class="btn btn-info" target="_blank" download><i class="fas fa-download"></i>{{ Télécharger le Sketch* à mettre dans l\'ESP8266 pour cet équipement.}}</a>
									<br><i>/!\ Le sketch est spécifiquement généré pour cet équipement !</i>
								</div></div>';
							}
							else
							{
								echo '<div class="form-group sketchs sketchESP'.$board_id.' " style="display : none;">
								<label class="col-sm-2 control-label">{{ Sketch }}</label>
								<div class="col-sm-6">
									<i>/!\ Merci de réactualiser la page (F5) après la sauvegarde pour avoir le lien du sketch !</i>
								</div></div>';
							}
						}
					}
					if ($ArduinoEspTag)
					{
						echo '<br><br><div class="form-group sketchs sketchUSB " style="display : none;">
						<label class="col-sm-2 control-label">{{ Sketch }}</label>
						<div class="col-sm-6">
							<a href="plugins/jeedouino/sketchs/JeedouinoUSB.ino" class="btn btn-info" target="_blank" download><i class="fas fa-download"></i>{{ Télécharger le Sketch à mettre dans l\'arduino (USB) pour cet équipement.}}</a>
						</div></div>';
						echo '<br><br><div class="form-group sketchsLib " style="display : none;">
								<label class="col-sm-2 control-label">{{ Librairies pour vos Sketchs }}</label>
								<div class="col-sm-6">
									<a href="plugins/jeedouino/sketchs/ArduinoLibraries.zip" class="btn btn-warning" target="_blank" download><i class="fas fa-download"></i>{{ Télécharger les librairies Arduinos/ESP }}</a>
								</div></div>';
					}

					?>

			</div>
		</div>
	</div>
</div>

<?php
 include_file('desktop', 'jeedouino', 'js', 'jeedouino');
 include_file('core', 'plugin.template', 'js');
?>

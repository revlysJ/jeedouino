<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'jeedouino');
$eqLogics = eqLogic::byType('jeedouino');

$cpl = jeedouino::GetJeedomComplement();
//include_file('desktop', 'jeedouino', 'css', 'jeedouino');
?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-warning " style="width : 100%;margin-top : 5px;margin-bottom: 5px;" href="<?php echo $cpl; ?>/index.php?v=d&p=plugin&id=jeedouino">
                    <i class="fa fa-cogs"></i> {{Config. du plugin}}
                </a>
                <a class="btn btn-warning bt_plugin_view_log " style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-slaveid="-1" data-log="jeedouino">
                    <i class="fa fa-comment"></i> {{Logs du plugin}}
                </a>
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add">
                    <i class="fa fa-plus-circle"></i> {{Ajouter un jeedouino}} <!-- changer pour votre type d'équipement -->
                </a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
				foreach ($eqLogics as $eqLogic)
				{
					$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
					echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '" style="' . $opacity . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
				}
				?>
            </ul>
            <ul id="ul_eqLogicView" class="nav nav-pills nav-stacked"></ul> <!-- la sidebar -->
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
        <legend><i class="fa fa-cog"></i> {{Gestion}}</legend> <!-- changer pour votre type d'équipement -->

		<div class="eqLogicThumbnailContainer">
		   <div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
			 <center>
				<i class="fa fa-plus-circle" style="font-size : 7em;color:#00979C;"></i>
			</center>
			<span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#00979C"><center>Ajouter</center></span>
			</div>
			<div class="cursor eqLogicAction" data-action="gotoPluginConf" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
				<center>
					<i class="fa fa-wrench" style="font-size : 7em;color:#00979C;"></i>
				</center>
				<span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#00979C"><center>{{Configuration}}</center></span>
			</div>
			<div class="cursor eqLogicAction" data-action="bt_healthSpecific" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
				<center>
					<i class="fa fa-medkit" style="font-size : 7em;color:#00979C;"></i>
				</center>
				<span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#00979C"><center>{{Santé}}</center></span>
			</div>
			<div class="cursor eqLogicAction" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
				<a target="_blank" style="text-decoration: none!important;" href="https://revlysj.github.io/jeedouino/fr_FR/">
				<center>
					<i class="fa fa-book" style="font-size : 7em;color:#00979C;"></i>
				</center>
				<span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#00979C"><center>{{Documentation}}</center></span>
				</a>
			</div>
		</div>
		<legend><i class="fa fa-table"></i> {{Mes équipements Jeedouino}}</legend>
		<div class="eqLogicThumbnailContainer">
		<?php
		foreach ($eqLogics as $eqLogic)
		{
			$ModeleArduino = $eqLogic->getConfiguration('arduino_board');
			$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
			echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
			echo "<center>";
			if (file_exists(dirname(__FILE__) . '/../../docs/images/jeedouino_'.$ModeleArduino.'.png'))
			{
				echo '<img class="lazy" src="plugins/jeedouino/docs/images/jeedouino_'.$ModeleArduino.'.png" height="105" width="95" />';
			}
			else
			{
				echo '<img class="lazy" src="plugins/jeedouino/docs/images/jeedouino_icon.png" height="105" width="95" />';
			}
			echo "</center>";
			echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
			echo '</div>';
		}
		?>
		</div>
	</div>
    <!-- Affichage de l'eqLogic sélectionné -->
    <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
		<div style="padding-bottom:40px;">
			<a class="btn btn-success eqLogicAction pull-right" data-action="save"  title="{{Sauver et/ou Générer les commandes automatiquement}}"><i class="fa fa-check-circle"></i> {{Sauver / Générer}}</a>
			<a class="btn btn-danger eqLogicAction pull-right" data-action="remove" title="{{Supprimer l'équipement}}"><i class="fa fa-minus-circle"></i> </a>
			<a class="btn btn-warning eqLogicAction pull-right" data-action="copy" title="{{Dupliquer cet équipement}}"><i class="fa fa-files-o"></i> </a>
			<!-- <a class="btn btn-default pull-right" id="bt_exportEq" title="{{Exporter cet équipement}}}"><i class="fa fa-share"></i> </a> -->
			<?php if (version_compare(jeedom::version(), '3.0.0', '>=')) echo '<a class="btn btn-default pull-right" id="bt_graphEqLogic" title="{{Graphique de liens}}"><i class="fa fa-object-group"></i> </a>'; ?>

			<a class="btn btn-default eqLogicAction pull-right" data-action="configure" title="{{Configuration avancée de l'équipement}}"><i class="fa fa-cogs"></i> </a>
			<a class="btn btn-default eqLogicAction pull-right" data-action="gotoPluginConf"  title="{{Page de Configuration du plugin}}"><i class="fa fa-wrench"></i> </a>
			<a class="btn btn-info eqLogicAction pull-right" data-action="bt_healthSpecific" title="{{Page de Santé du plugin}}"><i class="fa fa-medkit"></i> </a>
			<a class="btn btn-info eqLogicAction pull-right bt_plugin_view_log" data-slaveid="-1" data-log="jeedouino" title="{{Logs du plugin}}"><i class="fa fa-file"></i> </a>
			<a href="https://revlysj.github.io/jeedouino/fr_FR/" target="_blank" class="btn btn-success eqLogicAction pull-right"  title="{{Lien vers la Documentation du plugin}}"><i class="fa fa-book"></i> </a>
	      </div>

		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
			<li role="presentation"><a href="#pinstab" aria-controls="profile" role="tab" data-toggle="tab"  id="bt_conf_Pin"><i class="fa fa-wrench"></i> {{Pins / GPIO}}</a></li>
			<li role="presentation" class="sketchstab"><a href="#sketchstab" aria-controls="profile" role="tab" data-toggle="tab"  ><i class="fa fa-code"></i> {{Sketchs}}</a></li>
		</ul>

		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
			<br>

        <form class="form-horizontal">
            <fieldset>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Nom de l'équipement jeedouino}}</label>
                    <div class="col-sm-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
						<input type="text" class="eqLogicAttr form-control" data-l1key="Original_ID" style="display : none;" />
                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement jeedouino}}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                    <div class="col-sm-3">
                        <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                            <option value="">{{Aucun}}</option>
                            <?php
                            foreach (object::all() as $object) {
                                echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">{{Catégorie}}</label>
                    <div class="col-sm-8">
                        <?php
                        foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                            echo '<label class="checkbox-inline ">';
                            echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                            echo '</label>';
                        }
                        ?>
                    </div>
                </div>
                <div class="form-group">
				  <label class="col-sm-3 control-label"></label>
				  <div class="col-sm-9">
					<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activé?}}</label>
					<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible?}}</label>

					<?php if (config::byKey('ActiveExt', 'jeedouino', false))
					{
					?>
					<label class="checkbox-inline ActiveExt"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="alone"/>{{RPI sans Jeedom*}}</label>
					<?php }  ?>
					</div>
                </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Modèle de la carte </label>
                        <div class="col-sm-3">
                            <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="arduino_board">
                                <option value="" id="select_arduino_board">Aucune</option>
								<optgroup label="Pour Arduino">
                                <option value="auno" id="select_arduino_board">Arduino UNO 328</option>
								<option value="a2009" id="select_arduino_board">Arduino DUEMILLANOVE 328</option>
                                <option value="anano" id="select_arduino_board">Arduino NANO 328</option>
                                <option value="a1280" id="select_arduino_board">Arduino MEGA 1280</option>
                                <option value="a2560" id="select_arduino_board">Arduino MEGA 2560</option>
								</optgroup>
								<optgroup label="Pour Raspberry PI">
                                <option value="piface" id="select_arduino_board">PiFace Digital</option>
                                <option value="piGPIO26" id="select_arduino_board">Raspberry PI A/B GPIO</option>
                                <option value="piGPIO40" id="select_arduino_board">Raspberry PI2/3 A+/B+ GPIO</option>
								<option value="piPlus" id="select_arduino_board" >AB-Elec. IO Pi plus / MCP23017</option>
								</optgroup>
								<optgroup label="Pour ESP826x">
                                <option value="esp01" id="select_arduino_board" >ESP8266-01</option>
								<option value="esp07" id="select_arduino_board" >ESP8266-All I/O (Pour tests)</option>
								<option value="espMCU01" id="select_arduino_board" >NodeMCU / Wemos</option>
                                <option value="espsonoffpow" id="select_arduino_board">SONOFF POW (Pour tests)</option>
                                <option value="espsonoff4ch" id="select_arduino_board">SONOFF 4CH (Pour tests)</option>
                                <option value="esp32dev" id="select_arduino_board">ESP32 Dev (Pour tests)</option>
								<option value="espElectroDragonSPDT" id="select_arduino_board">ElectroDragon 2CH (Pour tests)</option>
								</optgroup>

                            </select>
                        </div>
                    </div>
				<div class="piFacePortID form-group">
					 <label class="col-sm-3 control-label" >{{Numéro de la carte piFace}}</label>
						<div class="col-sm-1">
							<input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="PortID" placeholder="ex : 0"  title="{{ 0 par défaut, 0 à 3 si PiRack }}"/>
						</div>
				</div>
				<div class="piPlusPortI2C form-group">
					<label class="col-sm-3 control-label" >{{Adresse I2C du MCP23017}}</label>
					<div class="col-sm-2">
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
				 <div class="form-group">
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
						<label class="col-sm-3 control-label">{{Port USB}}</label>
						<div class="col-sm-3">
							<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="arduinoport">
								<option value="usblocal">{{Local}}</option>
								<option value="usbdeporte">{{Déporté}}</option>
							</select>
						</div>
					</div>

					<div class="form-group arduinoport usblocal">
						<label class="col-sm-3 control-label">{{Port local USB carte }}</label>
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
                        <label class="col-lg-3 control-label">{{Carte sur port USB déporté }}</label>
                        <div class="col-lg-3">
                            <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="portusbdeporte">
                                <option value="none">{{Aucun}}</option>
                                <?php
                                $plugin_jeedouino_deporte=false;
								// sur Jeedom esclave
								if (class_exists ('jeeNetwork', false))
								{
									foreach (jeeNetwork::byPlugin('jeedouino') as $jeeNetwork)
									{
										echo '<optgroup label="Jeedouino sur '.$jeeNetwork->getName().'">';
										$plugin_jeedouino_deporte=true;
										$UsbMap=$jeeNetwork->sendRawRequest('jeedom::getUsbMapping',array('gpio' => true));
										if (is_array($UsbMap))
										{
											foreach ($UsbMap as $name => $value)
											{
												echo '<option value="' . $jeeNetwork->getId() . '_' . $name . '">' . $name . ' (' . $value . ')</option>';
											}
										}
										echo '</optgroup>';
									}
								}

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
                    <div class="form-group arduinoport usblocal usbdeporte">
						<label class="col-lg-3 control-label">{{Port réseau du démon}}</label>
						<div class="col-lg-3">
							<input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="PortDemon" placeholder="ex : <?php echo jeedouino::GiveMeFreePort('PortDemon'); ?>"/>
						</div>
                    </div>
				</div>
				<div class="datasource rj45arduino">
				   <div class="form-group">
						<label class="col-sm-3 control-label">{{Adresse IP de la carte }}</label>
						<div class="col-sm-3">
							<input type="text" list="jeeReseau" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="iparduino" placeholder="ex : 192.168.0.55"/>
                            <datalist id="jeeReseau">
                <?php
                    echo '<option value="' .$_SERVER["SERVER_ADDR"]. '" >Jeedom Master</option>';
					if (class_exists ('jeeNetwork', false))
					{
						foreach (jeeNetwork::all() as $jeeNetwork)
						{
							echo '<option value="' . $jeeNetwork->getIp() . '">' . $jeeNetwork->getName() . '</option>';
						}
					}
					// Sur JeedouinoExt (sans Jeedom)
					$ListExtIP = config::byKey('ListExtIP', 'jeedouino', '');
					if ($ListExtIP != '')
					{
						foreach ($ListExtIP as $ip)
						{
							echo '<option value="' . $ip . '">JeedouinoExt</option>';
						}
					}
                ?>
                            </datalist>
						</div>
						<label class="col-sm-1 control-label">{{Port}}</label>
						<div class="col-sm-2">
							<input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="ipPort" placeholder="ex : <?php echo jeedouino::GiveMeFreePort('ipPort'); ?>"/>
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
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Memento}}</label>
              <div class="col-sm-3">
                <textarea class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="memento" placeholder="Vos notes personnelles"></textarea>
              </div>
            </div>

            </fieldset>
        </form>

			</div>
		<div role="tabpanel" class="tab-pane" id="commandtab">

        <legend>{{Commandes de la carte}}</legend>
			<form class="form-horizontal">
				<div class="form-group">
				  <label class="col-sm-3 control-label"></label>
				  <div class="col-sm-9">
					<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr configuration" data-l1key="configuration" data-l2key="AutoOrder" checked/> {{Ordonner automatiquement les commandes par numéro de pin.}}</label>
				  </div>
				</div>
			</form>
		<!--
		<a class="btn btn-success btn-sm cmdAction" data-action="add"><i class="fa fa-plus-circle"></i>{{ Ajouter une commande Jeedouino}}</a><br/><br/>
		 -->
		 <!--
        <form class="form-horizontal">
            <fieldset>
                <div class="form-actions">
                    <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer l'équipement}}</a>
                    <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauver et/ou Générer les commandes}}</a>
                </div>
            </fieldset>
        </form>
		-->
		<br>
        <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th>{{Nom}}</th><th>{{Type (Sous-Type)}}</th><th>{{Type Générique}}</th><th>{{Valeur}}</th><th>{{Affichage}}</th><th>{{Valeur}}</th><th>{{Plus}}</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
		</div>

		<div role="tabpanel" class="tab-pane" id="pinstab">
			<br><br><br>
			<div class="form-group alert alert-warning">
				<center><h4>
						{{Note : Il est nécéssaire de configurer l'équipement et de le sauvegarder avant de pouvoir configurer les pins de celui-ci}}
				</h4></center>
				<!--
				<label class="col-sm-3 control-label">{{Configuration des pins de la carte}}</label>
				 <div class="col-sm-6">
					<a class="btn btn-warning btn-sm " id="bt_conf_Pin" >{{Ouvrir le paramétrage}}</a>
				</div>
				-->
			</div>

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
						<td>{{Ajoute autant de pins utilisateur (que le nombre choisi) à la liste des pins configurables dans l'onglet <i class="fa fa-wrench"></i> Pins/GPIO.}}</td>
					</tr>
				</table>

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

			<div role="tabpanel" class="tab-pane" id="sketchstab">
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
									<a href="plugins/jeedouino/sketchs/JeedouinoLAN_'.$board_id.'.ino" class="btn btn-info" target="_blank" download><i class="fa fa-download"></i>{{ Télécharger le Sketch* à mettre dans l\'arduino (Réseau) pour cet équipement.}}</a>
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
									<a href="plugins/jeedouino/sketchs/JeedouinoUSB_' . $board_id . '.ino" class="btn btn-info" target="_blank" download><i class="fa fa-download"></i>{{ Télécharger le Sketch* à mettre dans l\'arduino (Usb) pour cet équipement.}}</a>
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
									<a href="plugins/jeedouino/sketchs/JeedouinoESP_'.$board_id.'.ino" class="btn btn-info" target="_blank" download><i class="fa fa-download"></i>{{ Télécharger le Sketch* à mettre dans l\'ESP8266 pour cet équipement.}}</a>
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
							<a href="plugins/jeedouino/sketchs/JeedouinoUSB.ino" class="btn btn-info" target="_blank" download><i class="fa fa-download"></i>{{ Télécharger le Sketch à mettre dans l\'arduino (USB) pour cet équipement.}}</a>
						</div></div>';
						echo '<br><br><div class="form-group sketchsLib " style="display : none;">
								<label class="col-sm-2 control-label">{{ Librairies pour vos Sketchs }}</label>
								<div class="col-sm-6">
									<a href="plugins/jeedouino/sketchs/ArduinoLibraries.zip" class="btn btn-warning" target="_blank" download><i class="fa fa-download"></i>{{ Télécharger les librairies Arduinos/ESP }}</a>
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

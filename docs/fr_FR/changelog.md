Changelog
=========

Changelog Github : https://github.com/revlysJ/jeedouino/commits/master

13/01/2018 v1.02
---

- Ajout support ELECTRODRAGON Wifi IoT Relay Board Based on ESP8266 (esp).
- Ajout des commandes pour cartes arduino et esp826x:  
   * Envoi valeur au servo par slider, permet de commander un servo avec une valeur entre 0 et 180 par ex.
   * Commande pour RGB LED Strip a base de WS2811 (1 strip max par carte).
       Permet d'envoyer une valeur de couleur, ou de selectionner parmi 17 effets. 
       Attention un effet est bloquant, il vaux mieux dédier un arduino/esp.
- Ajout génération Sketch USB.
- Amélioration du démarrage du démon usb.
- Corrections diverses.
- Amélioration des libéllés des logs.
- /!\ A corriger : piGPIO : Entrée Bouton poussoir multi-clics + clic-long.

02/01/2018 v1.01
---

- Ajout des commandes :  
   * Sortie mise à LOW avec temporisation (minuterie) par slider.
   * Sortie mise à HIGH avec temporisation (minuterie) par slider.
   * Permet de modifier la valeur à la volée dans un scénario par ex.
- Amélioration de la détection des cartes sur port usb.
- Corrections diverses.

30/11/2017 v1.00
---

- Correctif affichage sur piGPIO : Entrée Bouton poussoir multi-clics + clic-long.

29/09/2017 v1.00
---

- Suppression des boutons de dons.
- Ajout support SONOFF POW et 4CH (esp).
- Ajout support Entrée Bouton poussoir multi-clics + clic-long  (Arduino, Esp, piGPIO).
- Ajout retour commande info sur action slider.
- Ajout support BMP085/180 Pression + Température (Commande dispo sur pin SDA) (Arduino, Esp, piGPIO).
- Correctifs Démons.

13/07/2017 v0.99.2
---

- Correctif (test2) démons thread hs.
- Ajout image sur graph link.

07/07/2017 v0.99.1
---

- Correctif detection port usb.
- Correctif (test) démons thread hs.
- Correctif léger JeedouinoExt.

01/07/2017 v0.99
---

- Correctifs divers + compatibilité Jeedom V3.
- Ajout de la fonction "Dupliquer" pour les équipements.
- Ajout d'un paramétrage de délai pour les sondes de température (Onglet "Options" dans "Pins /GPIO") pour Arduinos/Esp réseaux et piGpio.
- Ajout d'un graphique de liens (Jeedom V3 requis).
- Correctifs démons.

13/06/2017 v0.98.5
---

- Compatibilité Jeedom V3 béta ajoutée.

03/12/2016 v0.98
---

- Correction BootMode sur démon PiGPIO.
- Ajout BootMode sur démon PiPlus (pour tests).
- Corrections sketchs.
- Corrections Plugin.
- Améliorations diverses.

15/10/2016 v0.97.2
---

- Suppression des bootstrapswitch (boutons On/Off) pour compatibilité avec Jeedom.
- Mise à jour des visuels de la doc en conséquence.
- Correctifs divers.
- Arduino/ESP : Envoie de la trame téléinfo au plugin éponyme.

11/10/2016 v0.97
---

- Refonte et optimisation de la gestion des démons.
- Ajout du support des Generic Type (pour l'app mobile) en mode automatique ou manuel.
- Ajout de la possibilité de créér des commandes virtuelles automatiquement dans différents virtuels afin de mieux gérer ses équipements multiples.
- Correctifs et améliorations diverses sur plugin et préparation à la disparition du mode maître / esclave de Jeedom.
- Ajout de mise à jour du système (nécéssaire pour les gpio/dht/ds18b20) lors de l'installation des dépendances.
- Correctifs et améliorations sur tous les démons (ajout d'une option de redémarrage automatique par ex).
- Améliorations des sketchs USB, LAN et ESP:
- Arduino/ESP : Ajout du support d'une laison série pour téléinfo.
- Arduino/ESP : Ajout du support d'écran LCD type 16x2 pour envois de messages depuis Jeedom.
- Arduino/ESP : Ajout du support de sketchs perso pour les utilisateurs (détails dans les sketchs).
- Arduino/ESP : Ajout de pins/commandes utilisateur (0 à 100 max) pour vos sketchs perso.
- Arduino/ESP : Ajout de la possibilité dans les sketchs d'activer/désactiver certaines fonctionnalités pour libérer de l'espace par ex..
- Adaptations de l'option JeedouinoExt (RPI déportés sans Jeedom) pour utilisateurs avancés, pour TESTS uniquement.
- Amélioration de la doc
- Ajout de nouvelles commandes globales ALL_SWITCH, ALL_PULSE_LOW, ALL_PULSE_HIGH.

21/06/2016 v0.96
---

- Correctifs divers sur plugin, et sketchs.
- Ajout des dépendances Python-DHT et DS18B20.
- Ajout du support des sondes DHT 11, 22 (AM2302) et DS18B20 sur Raspberry PI (piGPIO).
- Ajout du support du capteur de distance HC-SR04.
- Améliorations diverses.
- Améliorations de l'option JeedouinoExt (RPI déportés sans Jeedom) pour utilisateurs avancés, pour TESTS uniquement.

21/04/2016 v0.95
---

- Correctifs divers sur plugin et démons.
- Ajout de vérifications complémentaires.
- Ajout de la dépendance Python-Serial.
- Améliorations de la page configuration avec ajout d'onglets (tabs) pour plus de clarté.
- Note : Certains onglets et options ne sont visibles qu'en mode expert de Jeedom.
- Ajout de l'option JeedouinoExt (RPI déportés sans Jeedom) pour utilisateurs avancés, pour TESTS uniquement.

04/04/2016 v0.94
---

- Amélioration de la doc.
- Correctifs divers sur plugin.
- Ajout du support de la carte IO PiPlus (et donc des MCP23017) et de son démon.
- Améliorations sur la page santé de Jeedom.

21/03/2016 v0.93
---

- Amélioration de la doc.
- Correctifs divers sur plugin, sketchs et démons.
- Ajout du support de la carte NodeMCU.
- Ajout du support de Docker ( cf. FAQ )

02/03/2016 v0.92
---

- Correctifs divers sur plugin et démons.
- Ajout d'un ResetCompteur pour les cartes arduino/esp.
- Ajout d'une entrée digitale variable (0-255 sur 0-10s) pour les cartes arduino/esp.

03/02/2016 v0.9
---

- Amélioration de la doc.
- Réduction de la charge CPU des démons python, et amélioration de la réactivité.
- Correctifs divers.
- Ajout de vérifications/validations supplémentaires.
- Ajout du support des sondes DHT (11,21,22) et DS18x20 pour les Arduinos (Ethernet/USB) et ESP8266.
   (1 sonde max par pin - peut impacter la réactivité de l'arduino.)
- Ajout d'un slider sur les commandes pwm et changement de valeur à la volée (scenarii).
- Amélioration de la page de configuration du plugin.

12/01/2016 v0.8
---

- Amélioration de la doc.
- Améliorations globale des démons python.
- Correctifs cosmétiques.
- Ajout du retour d'état des commandes 'action'.
- Amélioration des sketchs Arduinos (Ethernet/USB) et ESP8266.

07/01/2016 v0.75
---

- Amélioration de la doc.
- Amélioration de la gestion du redémarrage des démons en cas de reboot de plusieurs Jeedom esclaves en même temps.
- Correctifs mineurs.

06/01/2016 v0.7
---

- Correctifs mineurs sur les commandes.
- Correction d'un bug affectant les piFaces en piRack.
- Amélioration de la récupération des valeurs des compteurs sur les démons.
- Ajout de plus de flexibilité dans la communication entre sketchs/démons et Jeedom.

03/01/2016 v0.6
---

- Correctifs mineurs.
- Amélioration de la doc.
- Ajout du support de la carte ESP8266-01.

01/01/2016
---

- Correction d'un bug sur le démon python ArduinoUSB.
- Correctif mineur du plugin.

31/12/2015
---

- Ajout de screenshots pour le market.

20/12/2015
---

- Création du plugin Jeedouino.

////////
//
// Sketch Arduino pour le Plugin JEEDOUINO v097+ de JEEDOM
// Connection via Ethernet
//
////////
#define DEBUGtoSERIAL 0	// 0, ou 1 pour debug dans la console serie
#define UseWatchdog 0
#define NODHCP 1					// 0 pour IP via DHCP, 1 pour IP fixée dans le sketch.
#define UseDHT 1
#define UseDS18x20 1
#define UseTeleInfo 0
#define UseLCD16x2 0	// 0 = None(Aucun) / 1 = LCD Standard 6 pins / 2 = LCD via I2C
#define UseEthernet 0 // Choix de la lib suivant shield ethernet : 0 = W5100 / 1 = ENC28J60  / 2 = W5500 - Voir note ci-dessous
#define UseHCSR04 0
#define UsePwm_input 0 // Code obsolete (sera supprimé) - Entrée Numérique Variable (0-255 sur 10s) en PULL-UP
#define UseBMP180 0		// pour BMP085/180 Barometric Pressure & Temp Sensor

// Vous permet d'inclure du sketch perso - voir Doc / FAQ.
// Il faut activer l'option dans la configuration du plugin.
// Puis choisir le nombre de variables utilisateur sous l'onglet Pins/GPIO de votre équipement.
#define UserSketch 0
// Tags pour rechercher l'emplacement pour votre code :
//UserVars
//UserSetup
//UserLoop

#if (UseWatchdog == 1)
	#include <avr/wdt.h>
#endif

#include <SPI.h>
// Pour shield avec W5100
#if (UseEthernet == 0)
	#include <Ethernet.h>
#endif
// Pour shield avec W5500
#if (UseEthernet == 2)
	#include <Ethernet2.h>
#endif
// Pour shield avec ENC28J60 - Note : il faut passer NODHCP à 1 ci-dessus.
// Attention, problèmes de mémoire possibles sur arduino nano/uno/328 avec cette lib (v1.59)!
// Pour la récupérer, et l'installer dans l'IDE, voir : https://github.com/ntruchsess/arduino_uip/tree/Arduino_1.5.x
//
// Il faudra modifier dans le fichier \arduino-IDE\libraries\arduino_uip-master\utility\uipethernet-conf
// les lignes suivantes:
//#define UIP_SOCKET_NUMPACKETS		3
//#define UIP_CONF_MAX_CONNECTIONS 2
//#define UIP_CONF_UDP						 0
//
#if (UseEthernet == 1)
	#include <UIPEthernet.h>	// v1.59
#endif
// Traitement spécifique a cette librairie (pb de deconnection):
int UIPEFailCount = 0;
unsigned long UIPEFailTime = millis();

////////
// DHT
// https://github.com/adafruit/DHT-sensor-library
#if (UseDHT == 1)
	#include <DHT.h>
#endif

////////
// DS18x20
// https://github.com/PaulStoffregen/OneWire
#if (UseDS18x20 == 1)
	#include <OneWire.h>
#endif

byte IP_ARDUINO[] = { 192, 168, 0, 70 };
byte IP_JEEDOM[] = { 192, 168, 0, 44 };
byte mac[] = { 0xDE, 0xAD, 0xBE, 0xEF, 0xFE, 0xED };
EthernetServer server(80);

#include <EEPROM.h>

// CONFIGURATION VARIABLES

#if defined(__AVR_ATmega1280__) || defined(__AVR_ATmega2560__)
	#define NB_DIGITALPIN 54
	#define NB_ANALOGPIN 16
#else
	#define NB_DIGITALPIN 14
	#define NB_ANALOGPIN 6
#endif
#define NB_TOTALPIN ( NB_DIGITALPIN + NB_ANALOGPIN)

// Etat des pins de l'arduino ( Mode )
char Status_pins[NB_TOTALPIN];
byte pin_id;
byte echo_pin;

String eqLogic = "";
String inString = "";
String Message = "";
byte BootMode;

// Pour la detection des changements sur pins en entree
byte PinValue;
byte OLDPinValue[NB_TOTALPIN ];
unsigned long AnalogPinValue;
unsigned long OLDAnalogPinValue[NB_TOTALPIN ];
unsigned long CounterPinValue[NB_TOTALPIN ];
unsigned long PinNextSend[NB_TOTALPIN ];
byte swtch[NB_TOTALPIN];
// pour envoi ver jeedom
String jeedom = "\0";
// reception commande
char c[250];
byte n=0;
byte RepByJeedom=0;
// Temporisation sorties
unsigned long TempoPinHIGH[NB_TOTALPIN ]; // pour tempo pins sorties HIGH
unsigned long TempoPinLOW[NB_TOTALPIN ]; // pour tempo pins sorties LOW
unsigned long pinTempo=0;
unsigned long NextRefresh=0;
unsigned long ProbeNextSend=millis();
unsigned long timeout = 0;

#if (UseDHT == 1)
	DHT *myDHT[NB_TOTALPIN];
#endif

#if (UseTeleInfo == 1)
	// TeleInfo / Software serial
	#include <SoftwareSerial.h>
	byte teleinfoRX = 0;
	byte teleinfoTX = 0;
	SoftwareSerial teleinfo(6,7);	// definir vos pins RX , TX
#endif

#if (UseLCD16x2 == 1)
	// LiquidCrystal Standard (not i2c)
	#include <LiquidCrystal.h>
	LiquidCrystal lcd(8, 9, 4, 5, 6, 7);
#endif
#if (UseLCD16x2 == 2)
	// LiquidCrystal  i2c
	#include <Wire.h>
	#include <LiquidCrystal_I2C.h>
	LiquidCrystal_I2C lcd(0x27,16,2);
#endif
#if (UseBMP180 == 1)
	//  BMP085/180 Barometric Pressure & Temp Sensor
	//  https://learn.adafruit.com/bmp085/downloads
	#include <Wire.h>
	#include <Adafruit_BMP085.h>
	Adafruit_BMP085 bmp;
#endif

#if (UserSketch == 1)
	// UserVars
	// Vos declarations de variables / includes etc....
	//#include <your_stuff_here.h>
#endif

// SETUP

void setup()
{
	jeedom.reserve(256);
	Message.reserve(16);
	inString.reserve(4);
	#if (DEBUGtoSERIAL == 1)
		Serial.begin(115200); // Init du Port serie/USB
		Serial.setTimeout(5); // Timeout 5ms
		Serial.println(F("JEEDOUINO IS HERE."));
	#endif
	if (EEPROM.read(13) != 'J')
	{
		Init_EEPROM();
	#if (NODHCP == 0)
		if (Ethernet.begin(mac) == 0) 	// 1er demarrage 1er flash Jeedouino, on essaye via dhcp
		{
			#if (DEBUGtoSERIAL == 1)
				Serial.println(F("Connection via DHCP failed."));
			#endif
			#if (UseWatchdog == 1)
				wdt_enable(WDTO_15MS); // try reboot
			#endif
			while(1){}
		}
		IPAddress IP_ARDUINO = Ethernet.localIP();
		jeedom = F("&ipwifi=");
		jeedom += IP_ARDUINO[0];
		jeedom += '.';
		jeedom += IP_ARDUINO[1];
		jeedom += '.';
		jeedom += IP_ARDUINO[2];
		jeedom += '.';
		jeedom += IP_ARDUINO[3];
		SendToJeedom();
	#else
		Ethernet.begin(mac, IP_ARDUINO);
	#endif
	}
	else Ethernet.begin(mac, IP_ARDUINO);

	#if (DEBUGtoSERIAL == 1)
		Serial.println(F("Connection to LAN."));
	#endif
	server.begin();
	Load_EEPROM(1);

	#if (DEBUGtoSERIAL == 1)
		Serial.print(F("\nEqLogic:"));
		Serial.println(eqLogic);
	#endif

	#if (UseTeleInfo == 1)
		teleinfo.begin(1200);	 // vitesse par EDF
	#endif

	#if (UseLCD16x2 == 1)
		lcd.begin(16, 2);
		lcd.setCursor(0,0);
		lcd.print(F("JEEDOUINO v097+"));
	#endif
	#if (UseLCD16x2 == 2)
		lcd.init();
		lcd.backlight();
		lcd.home();
		lcd.print(F("JEEDOUINO v097+"));
	#endif

	#if (UseBMP180 == 1)
		bmp.begin();
	#endif

	#if (UserSketch == 1)
		UserSetup(); // Appel de votre setup()
	#endif
}
//// User Setup
#if (UserSketch == 1)
	void UserSetup()
	{
		// Votre setup()
	}
#endif

// LOOP

void loop()
{
	// TRAITEMENT DES TEMPO SORTIES SI IL Y EN A
	jeedom="";
	for (int i = 2; i < NB_TOTALPIN; i++)
	{
		if (TempoPinHIGH[i]!=0 && TempoPinHIGH[i]<millis()) // depassement de la temporisation
		{
			TempoPinHIGH[i]=0; // Suppression de la temporisation
			PinWriteHIGH(i);
		}
		else if (TempoPinLOW[i]!=0 && TempoPinLOW[i]<millis()) // depassement de la temporisation
		{
			TempoPinLOW[i]=0; // Suppression de la temporisation
			PinWriteLOW(i);
		}
	}
	// FIN TEMPO

	// On ecoute le reseau
	EthernetClient client = server.available();

	if (client)
	{
		// on regarde si on recois des donnees
		n=0;
		#if (DEBUGtoSERIAL == 1)
			Serial.println(F("\nRECEIVING:"));
		#endif
		timeout = millis()+30000;	// 30s
		while (client.connected() and timeout>millis())
		{
			if (client.available())
			{
				c[n] = client.read();
				if (c[n]=='\r') c[n]='\n';
				if (c[n]=='\n')
				{
					while  (client.available()) c[n+1] = client.read();
					break;
				}
				n++;
			}
		}
		#if (DEBUGtoSERIAL == 1)
			if (timeout<millis()) Serial.println(F("\nTimeOut:"));
			for (int i = 0; i <= n; i++)	Serial.print(c[i]);
		#endif

		if (n && c[n]=='\n')
		{
			n--;
			// on les traites
			if (c[0]=='C' && c[n]=='C')	 // Configuration de l'etat des pins
			{
				// NB_TOTALPIN = NB_DIGITALPIN	+ NB_ANALOGPIN

				if (n==(NB_TOTALPIN+1))				 // Petite securite
				{
					for (int i = 0; i < NB_TOTALPIN; i++)
					{
						EEPROM.update(30+i, c[i+1]);			// Sauvegarde mode des pins
					}
					Load_EEPROM(0);							// On met en place
					client.print(F("COK"));							 // On reponds a JEEDOM
					jeedom+=F("&REP=COK");
					ProbeNextSend=millis()+60000; // Décalage pour laisser le temps aux differents parametrages d'arriver de Jeedom
				}
			}
			else if (c[0]=='E' && c[n]=='Q')	// Recuperation de l' eqLogic de Jeedom concernant cet arduino
			{
				eqLogic = "";
				EEPROM.update(15, n);				 // Sauvegarde de la longueur du eqLogic
				for (int i = 1; i < n; i++)
				{
					EEPROM.update(15+i, c[i]-'0');			// Sauvegarde de l' eqLogic
					eqLogic += (char)c[i];
				}
				client.print(F("EOK"));							 // On reponds a JEEDOM
				jeedom+=F("&REP=EOK");
				ProbeNextSend=millis()+60000; // Décalage pour laisser le temps aux differents parametrages d'arriver de Jeedom
			}
			else if (c[0]=='I' && c[n]=='P')	// Recuperation de l' IP de Jeedom ( I192.168.000.044P )
			{
				if (n<17)			 // Petite securite
				{
					int ip=0;
					inString="";
					for (int i = 1; i <= n; i++)	//jusqu'a n car il faut un caractere non digit pour finir
					{
					if (isDigit(c[i]))
					{
						inString += (char)c[i];
					}
					else
					{
						IP_JEEDOM[ip]=inString.toInt();
						inString="";
						ip++;
					}
					}
					EEPROM.update(26, IP_JEEDOM[0]);					// Sauvegarde de l' IP
					EEPROM.update(27, IP_JEEDOM[1]);
					EEPROM.update(28, IP_JEEDOM[2]);
					EEPROM.update(29, IP_JEEDOM[3]);
					client.print(F("IPOK"));							// On reponds a JEEDOM
					jeedom+=F("&REP=IPOK");
					ProbeNextSend=millis()+60000; // Décalage pour laisser le temps aux differents parametrages d'arriver de Jeedom
				}
			}
			else if (c[0]=='S' && c[n]=='S')	// Modifie la valeur d'une pin sortie
			{
				jeedom+=F("&REP=SOK");
				for (int i = 1; i < n; i++)
				{
					if (isDigit(c[i])) c[i]=c[i]-'0';
				}

				pin_id=10*int(c[1])+int(c[2]);					// recuperation du numero de la pin

				Set_OutputPin(pin_id);
				client.print(F("SOK"));							 // On reponds a JEEDOM
				ProbeNextSend=millis()+10000; // Décalage pour laisser le temps au differents parametrages d'arriver de Jeedom
			}
			else if ((c[0]=='S' || c[0]=='R') && c[n]=='C')		 	// Reçoie la valeur SAUVEE d'une pin compteur (suite reboot)
			{																				// ou RESET suite sauvegarde equipement.
					if (n>3)										// Petite securite
					{
						for (int i = 1; i < n; i++)
						{
							if (isDigit(c[i])) c[i]=c[i]-'0';
						}

						if (c[0]=='R') CounterPinValue[pin_id]=0;	// On reset la valeur si demandé.

						pin_id=10*int(c[1])+int(c[2]);										// récupération du numéro de la pin
						int multiple=1;
						for (int i = n-1; i >= 3; i--)										// récupération de la valeur
						{
							CounterPinValue[pin_id] += int(c[i])*multiple;
							multiple *= 10;
						}
						PinNextSend[pin_id]=millis()+2000;
						NextRefresh=millis()+2000;
						ProbeNextSend=millis()+10000; // Décalage pour laisser le temps au differents parametrages d'arriver de Jeedom

						client.print(F("SCOK"));												// On reponds a JEEDOM
						jeedom+=F("&REP=SCOK");
					}
			}
			else if (c[0]=='S' && c[n]=='F')	// Modifie la valeur de toutes les pins sortie (suite reboot )
			{
				// NB_TOTALPIN = NB_DIGITALPIN	+ NB_ANALOGPIN
				if (n==(NB_TOTALPIN+1))			 // Petite securite
				{
					jeedom+=F("&REP=SFOK");
					for (int i = 2; i < NB_TOTALPIN; i++)
					{
						switch (Status_pins[i])
						{
							case 'o': // output
							case 's': // switch
							case 'l': // low_relais
							case 'h': // high_relais
							case 'u': // output_pulse
							case 'v': // low_pulse
							case 'w': // high_pulse
								if (c[i+1]=='0')
								{
									PinWriteLOW(i);
								}
								else if (c[i+1]=='1')
								{
									PinWriteHIGH(i);
								}
							break;
						}
					}
				RepByJeedom=0; // Demande repondue, pas la peine de redemander a la fin de loop()
					client.print(F("SFOK"));							// On reponds a JEEDOM
					ProbeNextSend=millis()+20000; // Décalage pour laisser le temps au differents parametrages d'arriver de Jeedom
				}
			}
			else if (c[0]=='S' && (c[n]=='L' || c[n]=='H' || c[n]=='A')) // Modifie la valeur de toutes les pins sortie a LOW / HIGH / SWITCH / PULSE
			{
				if (n==2 || n==7)			// Petite securite  : S2L / S2H / S2A / SP00007L /SP00007H
				{
					jeedom+=F("&REP=SOK");
					for (int i = 1; i < n; i++)
					{
						if (isDigit(c[i])) c[i]=c[i]-'0';
					}
					if (c[1]=='P') pinTempo = 10000*int(c[2])+1000*int(c[3])+100*int(c[4])+10*int(c[5])+int(c[6]);
					for (int i = 2; i < NB_TOTALPIN; i++)
					{
						TempoPinHIGH[i] = 0;
						TempoPinLOW[i] = 0;
						switch (Status_pins[i])
						{
							case 'o': // output
							case 's': // switch
							case 'l': // low_relais
							case 'h': // high_relais
							case 'u': // output_pulse
							case 'v': // low_pulse
							case 'w': // high_pulse
								if (c[n]=='L')
								{
									if (c[1] == 'P') TempoPinHIGH[i] = pinTempo;
									PinWriteLOW(i);
								}
								else if (c[n] == 'H')
								{
									if (c[1] == 'P') TempoPinLOW[i] = pinTempo;
									PinWriteHIGH(i);
								}
								else
								{
									if (swtch[i]==1) PinWriteLOW(i);
									else PinWriteHIGH(i);
								}
							break;
						}
					}
					client.print(F("SOK"));							// On reponds a JEEDOM
					ProbeNextSend=millis()+10000; // Décalage pour laisser le temps au differents parametrages d'arriver de Jeedom
				}
			}
			else if (c[0]=='B' && c[n]=='M')	// Choix du BootMode
			{
				BootMode=int(c[1]-'0');
				EEPROM.update(14, BootMode);

				client.print(F("BMOK"));								// On reponds a JEEDOM
				jeedom+=F("&REP=BMOK");
				ProbeNextSend=millis()+3000; // Décalage pour laisser le temps au differents parametrages d'arriver de Jeedom
			}
		#if (UseHCSR04 == 1)
			else if (c[0]=='T' && c[n]=='E') 	// Trigger pin + pin Echo pour le support du HC-SR04 (ex: T0203E)
			{
				if (n==5)				 // Petite securite
				{
					client.print(F("SOK"));								// On reponds a JEEDOM
					jeedom+=F("&REP=SOK");
					ProbeNextSend=millis()+10000; // Décalage pour laisser le temps aux differents parametrages d'arriver de Jeedom

					for (int i = 1; i < n; i++)
					{
						if (isDigit(c[i])) c[i]=c[i]-'0';
					}
					pin_id=10*int(c[1])+int(c[2]);					// recuperation du numero de la pin trigger
					echo_pin=10*int(c[3])+int(c[4]);			// recuperation du numero de la pin echo

					digitalWrite(pin_id, HIGH);							// impulsion de 10us pour demander la mesure au HC-SR04
					delayMicroseconds(10);
					digitalWrite(pin_id, LOW);
					long distance = pulseIn(echo_pin, HIGH); 	// attente du retour de la mesure (en us) - timeout 1s
					distance = distance * 0.034 / 2;					// conversion en distance (cm). NOTE : V=340m/s, fluctue en foncion de la temperature
					// on envoi le resultat a jeedom
					jeedom += '&';
					jeedom += echo_pin;
					jeedom += '=';
					jeedom += distance;
				}
			}
		#endif
		#if (UseLCD16x2 == 1 || UseLCD16x2 == 2)
			else if (c[0]=='S' && c[n]=='M') 	// Send Message to LCD
			{
					client.print(F("SMOK"));								// On reponds a JEEDOM
					jeedom+=F("&REP=SMOK");
					ProbeNextSend=millis()+10000; // Décalage pour laisser le temps aux differents parametrages d'arriver de Jeedom

					//pin_id=10*int(c[1]-'0')+int(c[2]-'0');
					lcd.clear();
					Message = "";
					int i = 3; // Normal, utilise dans les 2x FOR
					for (i; i < n; i++)	//S17Title|MessageM >>> S 17 Title | Message M	// Title & Message <16chars chacun
					{
						if (c[i] == '|') break;
						Message += (char)c[i];
					}
					lcd.setCursor(0,0);	// Title
					lcd.print(Message);
					i++;
					Message = "";
					for (i; i < n; i++)
					{
						Message += (char)c[i];
					}
					lcd.setCursor(0,1);	// Message
					lcd.print(Message);
			}
		#endif
		#if (UserSketch == 1)
			else if (c[0]=='U' && c[n]=='R')	// UseR Action
			{
				client.print(F("SOK"));	// On reponds a JEEDOM
				UserAction();
			}
		#endif
			else
			{
				client.print(F("NOK"));									 // On reponds a JEEDOM
				jeedom+=F("&REP=NOK");
			}
		}
	}
	client.stop();
	// On ecoute les pins en entree
	//jeedom="";
	for (int i = 2; i < NB_TOTALPIN; i++)
	{
		byte BPvalue = 0;
		switch (Status_pins[i])
		{
		case 'i': // input
		case 'p': // input_pullup
			PinValue = digitalRead(i);
			if (PinValue!=OLDPinValue[i] && (PinNextSend[i]<millis() || NextRefresh<millis()))
			{
				OLDPinValue[i]=PinValue;
				jeedom += '&';
				jeedom += i;
				jeedom += '=';
				jeedom += PinValue;
				PinNextSend[i]=millis()+1000; // Delai pour eviter trop d'envois
			}
			break;
		case 'n':		// BP_input_pulldown
			BPvalue = 1;
		case 'q':		// BP_input_pullup
			PinValue = digitalRead(i);
			if (PinValue != OLDPinValue[i])
			{
				PinNextSend[i] = millis() + 50;   // Delai antirebond
				OLDPinValue[i] = PinValue;
				ProbeNextSend = millis() + 5000; // decale la lecture des sondes pour eviter un conflit
			}
			if (PinNextSend[i] < millis() && PinValue != swtch[i])
			{
				if (PinValue == BPvalue) CounterPinValue[i] += 1;
				OLDAnalogPinValue[i] = millis() + 250;   // Delai entre clicks
				swtch[i] = PinValue;
			}
			if (OLDAnalogPinValue[i] < millis() && CounterPinValue[i] != 0)
			{
				if (PinValue == BPvalue) CounterPinValue[i] = 99; // Appui long
				jeedom += '&';
				jeedom += i;
				jeedom += '=';
				jeedom += CounterPinValue[i];
				CounterPinValue[i] = 0;
				OLDAnalogPinValue[i] = millis() + 1000;
			}
			break;
		#if (UsePwm_input == 1)
		case 'g': // input_variable suivant tempo
			PinValue = digitalRead(i);
			// Calcul
			if (PinNextSend[i]>millis()) // bouton laché avant les 10s
			{
				pinTempo=255-((PinNextSend[i]-millis())*255/10000); // pas de 25.5 par seconde
			}
			else pinTempo=255;	// si bouton laché après les 10s, on bloque la valeur a 255

			if (PinValue!=OLDPinValue[i]) // changement état entrée = bouton appuyé ou bouton relaché
			{
				OLDPinValue[i]=PinValue;
				if (swtch[i]==1)	// on vient de lacher le bouton.
				{
					swtch[i]=0; // on enregistre le laché.
					jeedom += '&';
					jeedom += i;
					jeedom += '=';
					jeedom += pinTempo;
					PinNextSend[i]=millis();
				}
				else
				{
					swtch[i]=1; // on vient d'appuyer sur le bouton, on enregistre.
					PinNextSend[i]=millis()+10000; // Delai pour la tempo de maintient du bouton.
					CounterPinValue[i]==millis(); // reutilisation pour economie de ram
					ProbeNextSend=millis()+15000; // decale la lecture des sondes pour eviter un conflit
				}
			}
			else
			{
				if (swtch[i]==1 && CounterPinValue[i]<millis())
				{
					jeedom += '&';
					jeedom += i;
					jeedom += '=';
					jeedom += pinTempo;
					CounterPinValue[i]==millis()+1000; // reactualisation toutes les secondes pour ne pas trop charger Jeedom
				}
			}
			break;
		#endif
		case 'a': // analog_input
				AnalogPinValue = analogRead(i);
				if (AnalogPinValue!=OLDAnalogPinValue[i] && (PinNextSend[i]<millis() || NextRefresh<millis()))
				{
					if (abs(AnalogPinValue-OLDAnalogPinValue[i])>20)		// delta correctif pour eviter les changements negligeables
					{
						int j=i;
						if (i<54) j=i+40;	 // petit correctif car dans Jeedom toutes les pins Analog commencent a l'id 54+
						OLDAnalogPinValue[i]=AnalogPinValue;
						//jeedom += '&' + j + '=' + AnalogPinValue;
						jeedom += '&';
						jeedom += j;
						jeedom += '=';
						jeedom += AnalogPinValue;
						PinNextSend[i]=millis()+5000; // Delai pour eviter trop d'envois
					}
				}
				break;
		case 'c': // compteur_pullup CounterPinValue
			PinValue = digitalRead(i);
			if (PinValue!=OLDPinValue[i])
			{
				OLDPinValue[i]=PinValue;
				CounterPinValue[i]+=PinValue;
			}
			if (NextRefresh<millis() || PinNextSend[i]<millis())
			{
				jeedom += '&';
				jeedom += i;
				jeedom += '=';
				jeedom += CounterPinValue[i];
				PinNextSend[i]=millis()+10000;		// Delai 10s pour eviter trop d'envois
			}
			break;
		#if (UseDHT == 1)
		case 'd': // DHT11
		case 'e': // DHT21
		case 'f':	// DHT22
			if (PinNextSend[i]<millis() and ProbeNextSend<millis())
			{
				jeedom += '&';
				jeedom += i;
				jeedom += '=';
				jeedom += int (myDHT[i]->readTemperature()*100);
				jeedom += '&';
				jeedom += i+1000;
				jeedom += '=';
				jeedom += int (myDHT[i]->readHumidity()*100);
				PinNextSend[i]=millis()+60000;	// Delai 60s entre chaque mesures pour eviter trop d'envois
				ProbeNextSend=millis()+10000; // Permet de decaler la lecture entre chaque sonde DHT sinon ne marche pas cf librairie (3000 mini)
				//jeedom += F("&FREERAM=");
				//jeedom += freeRam();
			}
			break;
		#endif
		#if (UseDS18x20 == 1)
		case 'b': // DS18x20
			if (PinNextSend[i]<millis() and ProbeNextSend<millis())
			{
				jeedom += '&';
				jeedom += i;
				jeedom += '=';
				jeedom += read_DSx(i); // DS18x20
				PinNextSend[i]=millis()+60000;	// Delai 60s entre chaque mesures pour eviter trop d'envois
				ProbeNextSend=millis()+10000; // Permet de laisser du temps pour les commandes 'action', probabilite de blocage moins grande idem^^
			}
			break;
		#endif
		#if (UseTeleInfo == 1)
		case 'j': // teleinfoRX
			if (PinNextSend[i]<millis() || NextRefresh<millis())
			{
				#if (DEBUGtoSERIAL == 1)
					Serial.print(F("\nTeleinfoRX ("));
					Serial.print(i);
					Serial.print(F(") : "));
				#endif
				char recu = 0;
				int cntChar=0;
				timeout = millis()+2000;	// 2s
				while (recu != 0x02 and timeout>millis())
				{
					if (teleinfo.available()) recu = teleinfo.read() & 0x7F;
/* 					#if (DEBUGtoSERIAL == 1)
						Serial.print(recu);
					#endif	 */
				}
				jeedom += F("&ADCO=");
				timeout = millis()+2000;	// 2s
				while (timeout>millis())
				{
					if (teleinfo.available())
					{
						recu = teleinfo.read() & 0x7F;
/* 						#if (DEBUGtoSERIAL == 1)
							Serial.print(recu);
						#endif */
						cntChar++;
						if (cntChar > 280) break;
						if (recu == 0) break;
						if (recu == 0x04) break; // EOT
						if (recu == 0x03) break; // permet d'eviter ce caractere dans la chaine envoyée (economise du code pour le traiter)
						if (recu == 0x0A) continue; 			// Debut de groupe
						if (recu == 0x0D)
						{
							jeedom += ';';	// Fin de groupe
							continue;
						}
						if (recu<33)
						{
							jeedom += '_';
						}
						else jeedom += recu;
					}
				}
				#if (DEBUGtoSERIAL == 1)
					Serial.println(F("/finRX"));
				#endif
				PinNextSend[i]=millis()+30000;	// Delai 30s entre chaque mesures pour eviter trop d'envois
			}
			break;
		#endif
		#if (UseBMP180 == 1)
		case 'r': // BMP085/180
			if (PinNextSend[i]<millis())
			{
				jeedom += '&';
				jeedom += i;
				jeedom += '=';
				jeedom += bmp.readTemperature();
				jeedom += '&';
				jeedom += i + 1000;
				jeedom += '=';
				jeedom += bmp.readPressure();
				PinNextSend[i] = millis() + 60000;	// Delai 60s entre chaque mesures pour eviter trop d'envois
			}
			break;
		#endif

		}
	}
	if (NextRefresh<millis())
	{
		NextRefresh=millis()+60000;	// Refresh auto toutes les 60s
		if (RepByJeedom) // sert a verifier que jeedom a bien repondu a la demande dans Load_eeprom
		{
			jeedom += F("&ASK=1"); // Sinon on redemande
		}
	}

	#if (UserSketch == 1)
		UserLoop(); // Appel de votre loop() permanent
		// if (NextRefresh<millis()) UserLoop(); // Appel de votre loop() toutes les 60s
	#endif

/* 	#if (UseLCD16x2 == 1 || UseLCD16x2 == 2)
		lcd.setCursor(0,1);
		lcd.print(jeedom);
	#endif */

	if (jeedom != "") SendToJeedom();
}
//// User Loop + Action
#if (UserSketch == 1)
	void UserLoop()
	{
		// Votre loop()
		// pour envoyer une valeur a jeedom, il suffit de remplir la variable jeedom comme cela :
		// jeedom += '&';
		// jeedom += u;	// avec u = numero de la pin "info" dans l'equipement jeedom - info pin number
		// jeedom += '=';
		// jeedom += info; // la valeur a envoyer - info value to send
		//
		// Ex:
		// jeedom += '&';
		// jeedom += 500;	// Etat pin 500
		// jeedom += '=';
		// jeedom += '1'; 	// '0' ou '1'
		//
		// jeedom += '&';
		// jeedom += 504;	// pin 504
		// jeedom += '=';
		// jeedom += millis(); 	// valeur numerique
		//
		// jeedom += '&';
		// jeedom += 506;	// pin 506
		// jeedom += '=';
		// jeedom += "Jeedouino%20speaking%20to%20Jeedom...";   // valeur string

		// /!\ attention de ne pas mettre de code bloquant (avec trop de "delays") - max time 2s
	}
	void UserAction()
	{
		// Ens cas d'une reception d'une commande user action depuis jeedom
		// c[0]='U' & c[n]='R')
		//
		// c[1] = c[1]-'0';	==5 (user pin start at 500)
		// c[2] = c[2]-'0';
		// c[3] = c[3]-'0';
		// pin_id = 100 * int(c[1]) + 10 * int(c[2]) + int(c[3]); 	// pin action number
		//
		// c[4] to c[n-1] 	// pin action value
		//
		// Ex:
		// U5000R -> U 500 0 R = binary 0 pin 500
		// U5001R -> U 500 1 R = binary 1 pin 500
		// U502128R -> U 502 128 R = Slider Value 128 pin 502
		// U507[Jeedom] Message|Ceci est un testR -> U 507 [Jeedom] Message | Ceci est un test R = Message pin 507

		// /!\ attention de ne pas mettre de code bloquant (avec trop de "delays") - max time 2s
	}
#endif

// FONCTIONS

void SendToJeedom()
{
	EthernetClient JEEDOMclient = server.available();
	#if (DEBUGtoSERIAL == 1)
		Serial.print(F("\nSending: "));
		Serial.println(jeedom);
		Serial.print(F("To eqLogic: "));
		Serial.println(eqLogic);
	#endif
	int J=JEEDOMclient.connect(IP_JEEDOM, 80);
	if (J)
	{
		JEEDOMclient.print(F("GET /plugins/jeedouino/core/php/Callback.php?BoardEQ="));
		JEEDOMclient.print(eqLogic);
		JEEDOMclient.print(jeedom);
		JEEDOMclient.println(F(" HTTP/1.1"));
		JEEDOMclient.print(F("Host: "));
		JEEDOMclient.print(IP_JEEDOM[0]);
		JEEDOMclient.print('.');
		JEEDOMclient.print(IP_JEEDOM[1]);
		JEEDOMclient.print('.');
		JEEDOMclient.print(IP_JEEDOM[2]);
		JEEDOMclient.print('.');
		JEEDOMclient.println(IP_JEEDOM[3]);
		delay(111);
		JEEDOMclient.println(F("Connection: close"));
		JEEDOMclient.println();
		delay(111);
		JEEDOMclient.stop();
	#if (DEBUGtoSERIAL == 1)
		Serial.print(F("At IP: "));
		Serial.print(IP_JEEDOM[0]);
		Serial.print('.');
		Serial.print(IP_JEEDOM[1]);
		Serial.print('.');
		Serial.print(IP_JEEDOM[2]);
		Serial.print('.');
		Serial.println(IP_JEEDOM[3]);
	#endif

		UIPEFailTime = millis();
		UIPEFailCount = 0;
	}
	else
	{
		JEEDOMclient.stop();
		UIPEFailCount++;
	#if (DEBUGtoSERIAL == 1)
		Serial.print(F("connection failed : "));
		Serial.println(J);
		Serial.print(F("UIPEFailCount : "));
		Serial.println(UIPEFailCount);
	#endif
		if (UIPEFailCount>10 and millis()>UIPEFailTime+60000)
		{
			#if (DEBUGtoSERIAL == 1)
				Serial.println(F("Waiting 10s & reboot if wdg"));
			#endif
			delay(10000); // tentative soft pour laisser le temps a la lib de se resaisir
			#if (UseWatchdog == 1)
				wdt_enable(WDTO_15MS); // try reboot
			#endif
			delay(20000); // tentative soft pour laisser le temps a la lib de se resaisir
			JEEDOMclient.stop();
			Ethernet.begin(mac, IP_ARDUINO);
			server.begin();
			UIPEFailTime = millis()+60000;
			delay(999);
		}
	}
	jeedom="";
	delay(444);
	//JEEDOMclient.stop();
}

void Set_OutputPin(int i)
{
	TempoPinHIGH[i]=0;
	TempoPinLOW[i]=0;

	switch (Status_pins[i])
	{
		case 'o': // output			 // S131S pin 13 set to 1 (ou S130S pin 13 set to 0)
		case 'l': // low_relais	// S13S pin 13 set to 0
		case 'h': // high_relais	// S13S pin 13 set to 1
			if (c[3]==0)
			{
				PinWriteLOW(i);
			}
			else
			{
				PinWriteHIGH(i);
			}
			break;

		case 's': // switch			// S13 pin 13 set to 1 si 0 sinon set to 0 si 1
			if (swtch[i]==1)
			{
				PinWriteLOW(i);
			}
			else
			{
				PinWriteHIGH(i);
			}
			break;

		//
		// ON VERIFIE SI UNE TEMPORISATION EST DEMANDEE SUR UNE DES SORTIES
		// On essai d'etre sur une precision de 0.1s mais ca peut fluctuer en fonction de la charge cpu
		// Testé seulement sur mega2560
		//
		case 'u': // output_pulse // Tempo ON : S1309999S : pin 13 set to 0 during 999.9 seconds then set to 1 (S1319999 : set to 1 then to 0)
			pinTempo=10000*int(c[4])+1000*int(c[5])+100*int(c[6])+10*int(c[7])+int(c[8]);
			// pinTempo est donc en dixieme de seconde
			pinTempo = pinTempo*100+millis(); // temps apres lequel la pin doit retourner dans l'autre etat.

			// Peut buguer quand millis() arrive vers 50jours si une tempo est en cours pendant la remise a zero de millis().
			// Risque faible si les tempo sont de l'ordre de la seconde (impulsions sur relais par ex.).
			if (c[3]==0)
			{
				TempoPinHIGH[i]=pinTempo;
				PinWriteLOW(i);
			}
			else if (c[3]==1)
			{
				TempoPinLOW[i]=pinTempo;
				PinWriteHIGH(i);
			}
			break;

		case 'v': // low_pulse		// Tempo ON : S139999S : pin 13 set to 0 during 999.9 seconds then set to 1
			if (c[3]==0)
			{
				pinTempo=10000*int(c[4])+1000*int(c[5])+100*int(c[6])+10*int(c[7])+int(c[8]);
				// pinTempo est donc en dixieme de seconde
				pinTempo = pinTempo*100+millis();	 // temps apres lequel la pin doit retourner dans l'autre etat.

				TempoPinHIGH[i]=pinTempo;
				PinWriteLOW(i);
			}
			else
			{
				PinWriteHIGH(i);
			}
			break;

		case 'w': // high_pulse	 // Tempo ON : S139999S : pin 13 set to 1 during 999.9 seconds then set to 0
			if (c[3]==0)
			{
				PinWriteLOW(i);
			}
			else
			{
				pinTempo=10000*int(c[4])+1000*int(c[5])+100*int(c[6])+10*int(c[7])+int(c[8]);
				// pinTempo est donc en dixieme de seconde
				pinTempo = pinTempo*100+millis();	 // temps apres lequel la pin doit retourner dans l'autre etat.

				TempoPinLOW[i]=pinTempo;
				PinWriteHIGH(i);
			}
			break;

		case 'm': // pwm_output
			pinTempo=100*int(c[3])+10*int(c[4])+int(c[5]);	// the duty cycle: between 0 (always off) and 255 (always on).
			analogWrite(i, pinTempo);
			break;
	}
}

void Load_EEPROM(int k)
{
	// on recupere le BootMode
	BootMode=EEPROM.read(14);
	// Recuperation de l'eqLogic
	eqLogic = "";
	n=EEPROM.read(15);				// Recuperation de la longueur du eqLogic
	if (n>0)				// bug probable si eqLogic_id<10 dans jeedom
	{
		for (int i = 1; i < n; i++)
		{
			eqLogic += EEPROM.read(15+i);
		}
	}
	// Recuperation de l'IP
	IP_JEEDOM[0]=EEPROM.read(26);
	IP_JEEDOM[1]=EEPROM.read(27);
	IP_JEEDOM[2]=EEPROM.read(28);
	IP_JEEDOM[3]=EEPROM.read(29);

	// on met en place le mode des pins
	jeedom="";
	byte y=1;
	#if (UseTeleInfo == 1)
		teleinfoRX = 0;
		teleinfoTX = 0;
	#endif
	#if (DEBUGtoSERIAL == 1)
		Serial.println(F("Conf. Pins:"));
		for (int i = 0; i < NB_TOTALPIN; i++) Serial.print((char)EEPROM.read(30+i));
		Serial.println();
	#endif
	for (int i = 2; i < NB_TOTALPIN; i++)
	{
		Status_pins[i] = EEPROM.read(30+i); // Etats des pins

		// INITIALISATION DES TABLEAUX DE TEMPO SORTIES
		TempoPinHIGH[i] = 0;
		TempoPinLOW[i] = 0;
		//
		switch (Status_pins[i])
		{
			case 'i': // input
			case 'a': // analog_input
			case 'n':		// BP_input_pulldown
				pinMode(i, INPUT);
				break;
			#if (UseTeleInfo == 1)
			case 'j':		// teleinfoRX pin
				teleinfoRX = i;
				pinMode(i, INPUT);
				break;
			case 'k':		// teleinfoTX pin
				teleinfoTX = i;
				pinMode(i, OUTPUT);
				break;
			#endif
			#if (UseDHT == 1)
			case 'd': // DHT11
				myDHT[i] = new DHT(i, 11);	// DHT11
				PinNextSend[i]=millis()+60000;
				break;
			case 'e': // DHT21
				myDHT[i] = new DHT(i, 21);	// DHT21
				PinNextSend[i]=millis()+60000;
				break;
			case 'f': // DHT 22
				myDHT[i] = new DHT(i, 22);	// DHT22
				PinNextSend[i]=millis()+60000;
				break;
			#endif
			#if (UseDS18x20 == 1)
			case 'b': // DS18x20
				PinNextSend[i]=millis()+60000;
				break;
			#endif
			case 't':		// trigger pin
				pinMode(i, OUTPUT);
				digitalWrite(i, LOW);
				break;
			case 'z':		// echo pin
				pinMode(i, INPUT);
				break;
			case 'p':		 // input_pullup
			case 'g':		 // pwm_input
			case 'q':		// BP_input_pullup
					pinMode(i, INPUT_PULLUP); // pour eviter les parasites en lecture, mais inverse l'etat de l'entree : HIGH = input open, LOW = input closed
					// Arduino Doc : An internal 20K-ohm resistor is pulled to 5V.
				swtch[i]=0; 	// init pour pwm_input
				OLDPinValue[i]=1;
				PinNextSend[i]=millis();
					break;
			case 'c':		 // compteur_pullup
					pinMode(i, INPUT_PULLUP); // pour eviter les parasites en lecture, mais inverse l'etat de l'entree : HIGH = input open, LOW = input closed
					// Arduino Doc : An internal 20K-ohm resistor is pulled to 5V.
					if (k)
					{
						jeedom += F("&CPT_"); // On demande à Jeedom de renvoyer la dernière valeur connue pour la pin i
						jeedom += i;
						jeedom += '=';
						jeedom += i;
					}
					break;
			case 'o': // output
			case 's': // switch
			case 'l': // low_relais
			case 'h': // high_relais
			case 'u': // output_pulse
			case 'v': // low_pulse
			case 'w': // high_pulse
				pinMode(i, OUTPUT);
				// restauration de l'etat des pins DIGITAL OUT au demarrage
			 if (k)
			 {
				switch (BootMode)
				{
					case 0:
						// On laisse tel quel
						break;
					case 1:
						PinWriteLOW(i);
						break;
					case 2:
						PinWriteHIGH(i);
						break;
					case 3:
						PinWriteHIGH(i);
						// On demande a Jeedom d'envoyer la valeur des pins
						if (y)
						{
							jeedom += F("&ASK=1");
							y=0;
							RepByJeedom=1; // sert a verifier que jeedom a bien repondu a la demande
						}
						break;
					case 4:
						if (EEPROM.read(110+i) == 0) PinWriteLOW(i);
						else PinWriteHIGH(i);
						break;
					case 5:
						PinWriteLOW(i);
						// On demande a Jeedom d'envoyer la valeur des pins
						if (y)
						{
							jeedom += F("&ASK=1");
							y=0;
							RepByJeedom=1; // sert a verifier que jeedom a bien repondu a la demande
						}
						break;
					}
			 }
				// fin restauration

				break;

			case 'm': // pwm_output
				pinMode(i, OUTPUT);
				break;
		}
	}
	#if (UseTeleInfo == 1)
		if (teleinfoRX != 0)
		{
				#if (DEBUGtoSERIAL == 1)
			Serial.print(F("\nteleinfoRX:"));
			Serial.println(teleinfoRX);
			Serial.print(F("\nteleinfoTX:"));
			Serial.println(teleinfoTX);
			#endif
			//SoftwareSerial teleinfo(teleinfoRX, teleinfoTX);
		}
	#endif
	if (jeedom!="") SendToJeedom();
}
void PinWriteHIGH(long p)
{
	digitalWrite(p, HIGH);
	swtch[p]=1;
	jeedom += '&';
	jeedom += p;
	jeedom += F("=1");
	// Si bootmode=4 sauvegarde de l'etat de la pin (en sortie) - !!! Dangereux pour l'eeprom à long terme !!!
	if (BootMode==4) EEPROM.update(110+p, 1);
	#if (DEBUGtoSERIAL == 1)
		Serial.print(F("SetPin "));
		Serial.print(p);
		Serial.println(F(" to 1"));
	#endif
}
void PinWriteLOW(long p)
{
	digitalWrite(p, LOW);
	swtch[p]=0;
	jeedom += '&';
	jeedom += p;
	jeedom += F("=0");
	// Si bootmode=4 sauvegarde de l'etat de la pin (en sortie) - !!! Dangereux pour l'eeprom à long terme !!!
	if (BootMode==4) EEPROM.update(110+p, 0);
	#if (DEBUGtoSERIAL == 1)
		Serial.print(F("SetPin "));
		Serial.print(p);
		Serial.println(F(" to 0"));
	#endif
}

void Init_EEPROM()
{
	// Un marqueur
	EEPROM.update(13, 'J');	 // JEEDOUINO

	// BootMode choisi au demarrage de l'arduino
	// 0 = Pas de sauvegarde - Toutes les pins sorties non modifi�es au d�marrage.
	// 1 = Pas de sauvegarde - Toutes les pins sorties mises � LOW au d�marrage.
	// 2 = Pas de sauvegarde - Toutes les pins sorties mises � HIGH au d�marrage.
	// 3 = Sauvegarde sur JEEDOM - Toutes les pins sorties mises suivant leur sauvegarde dans Jeedom. Jeedom requis, sinon pins mises � OFF.
	// 4 = Sauvegarde sur EEPROM- Toutes les pins sorties mises suivant leur sauvegarde dans l\'EEPROM. Autonome, mais dur�e de vie de l\'eeprom fortement r�duite.
	EEPROM.update(14, 2);
	BootMode=2;

	// Initialisation par default
	for (int i = 30; i < 200; i++)
	{
		EEPROM.update(i, 1);	// Valeur des pins OUT au 1er demarrage ( mes relais sont actis a 0, donc je met 1 pour eviter de les actionner au 1er boot)
	}
	EEPROM.update(26, IP_JEEDOM[0]);					// Sauvegarde de l' IP
	EEPROM.update(27, IP_JEEDOM[1]);
	EEPROM.update(28, IP_JEEDOM[2]);
	EEPROM.update(29, IP_JEEDOM[3]);

	eqLogic = F("IDeqLogic");										// Sauvegarde de eqLogic pour 1er boot apres 1er flashage
	EEPROM.update(15, eqLogicLength);					// Sauvegarde de la longueur du eqLogic
	for (int i = 1; i < eqLogicLength; i++)
	{
		EEPROM.update(15+i, eqLogic[i-1]-'0'); 				// Sauvegarde de l' eqLogic
	}

	// fin initialisation
}
//int freeRam ()
//{
//	extern int __heap_start, *__brkval;
//	int v;
//	return (int) &v - (__brkval == 0 ? (int) &__heap_start : (int) __brkval);
//}
#if (UseDS18x20 == 1)
int read_DSx(int pinD)
{
	byte present = 0;
	byte type_s;
	byte data[12];
	byte addr[8];
	OneWire ds(pinD);

	if ( !ds.search(addr))
	{
		ds.reset_search();
	#if (DEBUGtoSERIAL == 1)
		Serial.println(F("ds not found..."));
	#endif
		delay(250);
		return 0;
	}

	if (OneWire::crc8(addr, 7) != addr[7]) //Check if there is no errors on transmission
	{
		#if (DEBUGtoSERIAL == 1)
		Serial.println(F("CRC invalide..."));
		#endif
		return 0;
	}

	// the first ROM byte indicates which chip
	switch (addr[0])
	{
		case 0x10:
	#if (DEBUGtoSERIAL == 1)
		 Serial.println(F(" Chip = DS18S20")); // or old DS1820
	#endif
		 type_s = 1;
		 break;
		case 0x28:
	#if (DEBUGtoSERIAL == 1)
		 Serial.println(F(" Chip = DS18B20"));
	#endif
		 type_s = 0;
		 break;
		case 0x22:
	#if (DEBUGtoSERIAL == 1)
		 Serial.println(F(" Chip = DS1822"));
	#endif
		 type_s = 0;
		 break;
		default:
	#if (DEBUGtoSERIAL == 1)
		 Serial.println(F("Device is not a DS18x20 family device."));
	#endif
		 return 0;
	}

	ds.reset();
	ds.select(addr);
	ds.write(0x44,1);			 // start conversion, with parasite power on at the end
	delay(800);
	present = ds.reset();
	ds.select(addr);
	ds.write(0xBE);			 // Read Scratchpad
	byte ii;
	for ( ii = 0; ii < 9; ii++)
	{				 // we need 9 bytes
		data[ii] = ds.read();
	}

	// convert the data to actual temperature

	unsigned int raw = (data[1] << 8) | data[0];
	if (type_s)
	{
		raw = raw << 3; // 9 bit resolution default
		if (data[7] == 0x10)
		{
			// count remain gives full 12 bit resolution
			raw = (raw & 0xFFF0) + 12 - data[6];
		}
	}
	else
	{
		byte cfg = (data[4] & 0x60);
		if (cfg == 0x00) raw = raw << 3;	// 9 bit resolution, 93.75 ms
		else if (cfg == 0x20) raw = raw << 2; // 10 bit res, 187.5 ms
		else if (cfg == 0x40) raw = raw << 1; // 11 bit res, 375 ms

	}
	#if (DEBUGtoSERIAL == 1)
	Serial.println(raw/16);
	#endif
	return raw;
}
#endif

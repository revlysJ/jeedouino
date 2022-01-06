////////
//
// Sketch Arduino pour le Plugin JEEDOUINO v097+ de JEEDOM
// Connection via USB avec le Démon Python
//
// JeEdUiNoTaG
////////
#define DEBUGtoSERIAL 0	// 0, ou 1 pour debug dans la console serie
#define UseWatchdog 0
#define UseDHT 1
#define UseDS18x20 1
#define UseTeleInfo 0
#define UseLCD16x2 0	// 0 = None(Aucun) / 1 = LCD Standard 6 pins / 2 = LCD via I2C
#define UseHCSR04 0
#define UsePwm_input 0 // Code obsolete (sera supprimé) - Entrée Numérique Variable (0-255 sur 10s) en PULL-UP
#define UseBMP180 0		// pour BMP085/180 Barometric Pressure & Temp Sensor
#define UseBMP280 0		// pour BMP280 temperature, barometric pressure
#define UseBME280 0		// pour BME280 temperature, barometric pressure and humidity
#define UseBME680 0		// pour BME680 temperature, humidity, barometric pressure and VOC gas
#define UseServo 0
#define UseWS2811 0	// Pour gerer les led stips a base de WS2811/2 avec l'excellente lib Adafruit_NeoPixel

// Concernant UseBMP280, UseBME280 et UseBME680
// =1 capteur(x1) sur i2c addr 0x76 (au choix)
// =2 capteur(x1) sur i2c addr 0x77 (au choix)
// =3 capteurs(x2) sur i2c addr 0x76 & 0x77 (identiques)

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

#include <EEPROM.h>

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

// CONFIGURATION VARIABLES
byte IP_JEEDOM[] = { 192, 168, 0, 44 };
#if defined(__AVR_ATmega1280__) || defined(__AVR_ATmega2560__)
#define NB_DIGITALPIN 54
#define NB_ANALOGPIN 16
#else
#define NB_DIGITALPIN 14
#define NB_ANALOGPIN 6
#endif
#define NB_TOTALPIN ( NB_DIGITALPIN  + NB_ANALOGPIN)

// Etat des pins de l'arduino ( Mode )
char Status_pins[NB_TOTALPIN];
int pin_id;
byte echo_pin;

String eqLogic = "";
String eqLogic0 = "";
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
char buf[1];
int buf_etat=0;
byte n=0;
byte RepByJeedom=0;
// Temporisation sorties
unsigned long TempoPinHIGH[NB_TOTALPIN ]; // pour tempo pins sorties HIGH
unsigned long TempoPinLOW[NB_TOTALPIN ]; // pour tempo pins sorties LOW
unsigned long pinTempo = 0;
unsigned long NextRefresh = 0;
unsigned long ProbeNextSend = millis();
unsigned long timeout = 0;
unsigned long ProbePauseDelay = 60000;

#if (DEBUGtoSERIAL == 1)
	#include <SoftwareSerial.h>
	SoftwareSerial DebugSerial(4,5);	// definir vos pins RX , TX
#endif

#if (UseDHT == 1)
	DHT *myDHT[NB_TOTALPIN];
#endif

#if (UseServo == 1)
	#include <Servo.h>
	Servo myServo[NB_TOTALPIN];
#endif

#if (UseWS2811 == 1)
	// More info at https://github.com/adafruit/Adafruit_NeoPixel
	#include <Adafruit_NeoPixel.h>
	// Parameter 1 = number of pixels in strip
	// Parameter 2 = Arduino pin number (most are valid)
	// Parameter 3 = pixel type flags, add together as needed:
	//   NEO_KHZ800  800 KHz bitstream (most NeoPixel products w/WS2812 LEDs)
	//   NEO_KHZ400  400 KHz (classic 'v1' (not v2) FLORA pixels, WS2811 drivers)
	//   NEO_GRB     Pixels are wired for GRB bitstream (most NeoPixel products)
	//   NEO_RGB     Pixels are wired for RGB bitstream (v1 FLORA pixels, not v2)
	//   NEO_RGBW    Pixels are wired for RGBW bitstream (NeoPixel RGBW products)
	#define WS2811PIN 6
	Adafruit_NeoPixel strip = Adafruit_NeoPixel(50, WS2811PIN, NEO_GRB + NEO_KHZ800);
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
#if (UseBME280 >= 1)
	// BME280-barometric-pressure-temperature-humidity-sensor
	// https://learn.adafruit.com/adafruit-bme280-humidity-barometric-pressure-temperature-sensor-breakout/arduino-test
	#include <Adafruit_BME280.h>
	#if (UseBME280 != 2)
		Adafruit_BME280 bme280; // I2C x76
	#endif
	#if (UseBME280 >= 2)
		Adafruit_BME280 bme280b; // I2C x77
	#endif
#endif
#if (UseBMP280 >= 1)
	// BMP280 barometric-pressure-temperature-sensor
	// https://learn.adafruit.com/adafruit-bmp280-barometric-pressure-plus-temperature-sensor-breakout/arduino-test
	#include <Adafruit_BMP280.h>
	#if (UseBMP280 != 2)
		Adafruit_BMP280 bmp280; // I2C x76
	#endif
	#if (UseBMP280 >= 2)
		Adafruit_BMP280 bmp280b; // I2C x77
	#endif
#endif
#if (UseBME680 >= 1)
	//  bme680-humidity-temperature-barometic-pressure-voc-gas
	//  https://learn.adafruit.com/adafruit-bme680-humidity-temperature-barometic-pressure-voc-gas/arduino-wiring-test
	#include "Adafruit_BME680.h"
	#if (UseBME680 != 2)
		Adafruit_BME680 bme680; // I2C x76
	#endif
	#if (UseBME680 >= 2)
		Adafruit_BME680 bme680b; // I2C x77
	#endif
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
	Serial.begin(115200); // Init du Port serie/USB
	Serial.setTimeout(5); // Timeout 5ms
	#if (DEBUGtoSERIAL == 1)
		DebugSerial.begin(115200);
		DebugSerial.println(F("JEEDOUINO IS HERE."));
	#endif
	if (EEPROM.read(13) != 'J') Init_EEPROM();
	Load_EEPROM(1);

	#if (DEBUGtoSERIAL == 1)
		DebugSerial.print(F("\nEqLogic:"));
		DebugSerial.println(eqLogic);
	#endif

	#if (UseLCD16x2 == 1)
		lcd.begin(16, 2);
		lcd.setCursor(0,0);
		lcd.print(F("JEEDOUINO v097+"));
	#endif
	#if (UseLCD16x2 == 2)
		lcd.begin();
		lcd.backlight();
		lcd.home();
		lcd.print(F("JEEDOUINO v097+"));
	#endif

	#if (UseBMP180 == 1)
		bmp.begin();
	#endif
	#if (UseBME280 >= 1)
		#if (UseBME280 != 2)
			bme280.begin(0x76);
		#endif
		#if (UseBME280 >= 2)
			bme280b.begin(0x77);
		#endif
	#endif
	#if (UseBMP280 >= 1)
		#if (UseBMP280 != 2)
			bmp280.begin(0x76);
		#endif
		#if (UseBMP280 >= 2)
			bmp280b.begin(0x77);
		#endif
	#endif
	#if (UseBME680 >= 1)
		#if (UseBME680 != 2)
			bme680.begin(0x76);
		#endif
		#if (UseBME680 >= 2)
			bme680b.begin(0x77);
		#endif
	#endif

	#if (UseWS2811 == 1)
		strip.begin();
		strip.show();
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

	// On ecoute l'usb
	if (Serial.available()>0)
	{
		// on regarde si on recois des donnees
		n=0;
		#if (DEBUGtoSERIAL == 1)
			DebugSerial.println(F("\nRECEIVING:"));
		#endif
		timeout = millis()+1000;
		while (millis()<timeout)
		{
			buf_etat = Serial.readBytes(buf,1);
			if (buf_etat)
			{
				c[n] = buf[0];
				if (c[n]=='\r') c[n]='\n';
				if (c[n]=='\n') break;
				n++;
			}
			else break;
		}
		#if (DEBUGtoSERIAL == 1)
			if (timeout<millis()) DebugSerial.println(F("\nTimeOut:"));
			for (int i = 0; i <= n; i++)	DebugSerial.print(c[i]);
		#endif

		if (n && c[n]=='\n')
		{
			n--;
			// on les traites
			if (c[0]=='C' && c[n]=='C')		 // Configuration de l'etat des pins
			{
				// NB_TOTALPIN = NB_DIGITALPIN  + NB_ANALOGPIN

				if (n==(NB_TOTALPIN+1))						// Petite securite
				{
					for (int i = 0; i < NB_TOTALPIN; i++)
					{
						EEPROM.update(30+i, c[i+1]);			 // Sauvegarde mode des pins
					}
					Load_EEPROM(0);								// On met en place
					Serial.println(F("COK"));							// On reponds a JEEDOM
					ProbeNextSend = millis() + ProbePauseDelay; 			// Décalage pour laisser le temps aux differents parametrages d'arriver de Jeedom
				}
			}
			else if (c[0]=='P' && c[n]=='G')	// On repond au ping
			{
				if (n==3)					// Petite securite
				{
					Serial.println(F("PINGOK"));								// On reponds a JEEDOM
					ProbeNextSend = millis() + 10000; 			// Décalage pour laisser le temps aux differents parametrages d'arriver de Jeedom
				}
			}
			else if (c[0]=='E' && c[n]=='Q')	 // Recuperation de l' eqLogic de Jeedom concernant cet arduino
			{
				eqLogic = "";
				EEPROM.update(15, n);						// Sauvegarde de la longueur du eqLogic
				for (int i = 1; i < n; i++)
				{
					EEPROM.update(15+i, c[i]-'0');			 // Sauvegarde de l' eqLogic
					eqLogic += (char)c[i];
				}
				Serial.println(F("EOK"));							// On reponds a JEEDOM
				ProbeNextSend = millis() + ProbePauseDelay; // Décalage pour laisser le temps aux differents parametrages d'arriver de Jeedom
			}
			else if (c[0]=='I' && c[n]=='P')	 // Recuperation de l' IP de Jeedom ( I192.168.000.044P )
			{
				if (n<17)					// Petite securite
				{
					int ip=0;
					inString="";
					for (int i = 1; i <= n; i++)	 //jusqu'a n car il faut un caractere non digit pour finir
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
					Serial.println(F("IPOK"));								// On reponds a JEEDOM
					ProbeNextSend = millis() + ProbePauseDelay; // Décalage pour laisser le temps aux differents parametrages d'arriver de Jeedom
				}
			}
			else if (c[0]=='S' && c[n]=='S')	 // Modifie la valeur d'une pin sortie
			{
				for (int i = 1; i < n; i++)
				{
					if (isDigit(c[i])) c[i] = c[i] - '0';
				}

				pin_id = 10 * int(c[1]) + int(c[2]);					// recuperation du numero de la pin
				if (Status_pins[pin_id] != 'y')
				{
					Set_OutputPin(pin_id);
				}
				else	// double pulse
				{
					if (n == 10)		// Petite securite
					{
						unsigned long clickTemp = 100 * int(c[4]) + 10 * int(c[5]) + int(c[6]); // milli-secondes
						unsigned long pauseTemp = 100 * int(c[7]) + 10 * int(c[8]) + int(c[9]); // milli-secondes

						clickTemp = 100 * clickTemp;
						pauseTemp = 100 * pauseTemp;
						if (c[3] == 0)
						{
							digitalWrite(pin_id, LOW);	// first click
							delay(clickTemp);			// duree du click

							digitalWrite(pin_id, HIGH);	// pause
							delay(pauseTemp);			// duree de la pause

							digitalWrite(pin_id, LOW);	// second click
							delay(clickTemp);			// duree du click

							digitalWrite(pin_id, HIGH);	// retour
							swtch[pin_id] = 1;
							jeedom += '&';
							jeedom += pin_id;
							jeedom += F("=1");
						}
						else
						{
							digitalWrite(pin_id, HIGH);
							delay(clickTemp);

							digitalWrite(pin_id, LOW);
							delay(pauseTemp);

							digitalWrite(pin_id, HIGH);
							delay(clickTemp);

							digitalWrite(pin_id, LOW);
							swtch[pin_id] = 0;
							jeedom += '&';
							jeedom += pin_id;
							jeedom += F("=0");
						}
					}
					else if (n == 4)
					{
						if (c[3] == 0)
						{
							PinWriteLOW(pin_id);
						}
						else
						{
							PinWriteHIGH(pin_id);
						}
					}
				}
				Serial.println(F("SOK"));								// On reponds a JEEDOM
				ProbeNextSend = millis() + 10000; // Décalage pour laisser le temps au differents parametrages d'arriver de Jeedom
			}
			else if ((c[0]=='S' || c[0]=='R') && c[n]=='C')       	// Reçoie la valeur SAUVEE d'une pin compteur (suite reboot)
			{                                       										// ou RESET suite sauvegarde equipement.
				if (n>3)					// Petite securite
				{

					for (int i = 1; i < n; i++)
					{
						if (isDigit(c[i])) c[i]=c[i]-'0';
					}

					if (c[0]=='R') CounterPinValue[pin_id]=0; // On reset la valeur si demandé.

					pin_id=10*int(c[1])+int(c[2]);					// récupération du numéro de la pin
					int multiple=1;
					for (int i = n-1; i >= 3; i--)					// récupération de la valeur
					{
						CounterPinValue[pin_id] += int(c[i])*multiple;
						multiple *= 10;
					}
					PinNextSend[pin_id]=millis()+2000;
					NextRefresh=millis()+60000;
					ProbeNextSend=millis()+10000; // Décalage pour laisser le temps au differents parametrages d'arriver de Jeedom
					Serial.println(F("SCOK"));							 // On reponds a JEEDOM
				}
			}
			else if (c[0] == 'S' && c[n] == 'P')		 	// Reçoi le délai de relève des sondes
			{
				if (n > 1)										// Petite securite
				{
					for (int i = 1; i < n; i++)
					{
						if (isDigit(c[i])) c[i] = c[i] - '0';
					}

					int multiple = 1;
					pinTempo = 0;
					for (int i = n-1; i > 0; i--)										// récupération de la valeur
					{
						pinTempo += int(c[i]) * multiple;
						multiple *= 10;
					}
					if (pinTempo < 1 || pinTempo > 1000) pinTempo = 5;
					ProbePauseDelay = 60000 * pinTempo;

					Serial.println(F("SOK"));												// On reponds a JEEDOMSerial.println
					jeedom += F("&REP=SOK");
				}
			}
			else if (c[0] == 'S' && c[n] == 'F')	 // Modifie la valeur de toutes les pins sortie (suite reboot )
			{
				// NB_TOTALPIN = NB_DIGITALPIN  + NB_ANALOGPIN
				if (n == (NB_TOTALPIN + 1))					// Petite securite
				{
					for (int i = 2; i < NB_TOTALPIN; i++)
					{
						switch (Status_pins[i])
						{
							case 'o':		//	output
							case 's':		//  switch
							case 'l':		 //	low_relais
							case 'h':		//	high_relais
							case 'u':		//	output_pulse
							case 'v':		//	low_pulse
							case 'w':		 //	high_pulse
							case 'y':		 //	double_pulse
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
					Serial.println(F("SFOK"));								// On reponds a JEEDOM
					ProbeNextSend=millis()+20000; // Décalage pour laisser le temps au differents parametrages d'arriver de Jeedom
				}
			}
			else if (c[0]=='S' && (c[n]=='L' || c[n]=='H' || c[n]=='A')) // Modifie la valeur de toutes les pins sortie a LOW / HIGH / SWITCH / PULSE
			{
				if (n==2 || n==7)			// Petite securite  : S2L / S2H / S2A / SP00007L /SP00007H
				{
					//jeedom+=F("&REP=SOK");
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
							case 'y': // double_pulse
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
					Serial.println(F("SOK"));							// On reponds a JEEDOM
					ProbeNextSend=millis()+10000; // Décalage pour laisser le temps au differents parametrages d'arriver de Jeedom
				}
			}
			else if (c[0]=='B' && c[n]=='M')	 // Choix du BootMode
			{
				BootMode=int(c[1]-'0');
				EEPROM.update(14,  BootMode);
				ProbeNextSend=millis()+3000; // Décalage pour laisser le temps aux differents parametrages d'arriver de Jeedom
				Serial.println(F("BMOK"));									// On reponds a JEEDOM
			}
		#if (UseHCSR04 == 1)
			else if (c[0]=='T' && c[n]=='E') 	// Trigger pin + pin Echo pour le support du HC-SR04 (ex: T0203E)
			{
				if (n==5)				 // Petite securite
				{
					Serial.println(F("SOK"));				 // On reponds a JEEDOM
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
					distance = distance * 0.034 / 2;					// conversion en distance (cm). NOTE : V=340m/s, fluctue en fonction de la temperature
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
					Serial.println(F("SMOK"));								// On reponds a JEEDOM
					//jeedom+=F("&REP=SMOK");
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
		#if (UseWS2811 == 1)
			else if (c[0]=='C' && c[n]=='R')	// COLOR : C09LFF00FFR ou C09M12R pin 09 color L or effect M
			{
				for (int i = 1; i < n; i++)
				{
					if (isDigit(c[i])) c[i]=c[i]-'0';
					if ((c[i] >= 'A') && (c[i] <= 'F')) c[i]=c[i] - 'A' + 10; // For hex
				}
				if (c[3]=='M')
				{
					Serial.println(F("SOK"));	// On reponds a JEEDOM avant le TIMEOUT
					pinTempo = 10 * int(c[4]) + int(c[5]);
					#if (DEBUGtoSERIAL == 1)
						DebugSerial.print(F("\startShow: "));
						DebugSerial.println(pinTempo);
					#endif
					startShow(pinTempo);
				}
				else if (c[3]=='L')
				{
					Serial.println(F("SOK"));		// On reponds a JEEDOM avant le TIMEOUT
					if (n == 10)			 // Petite securite
					{
						uint8_t r = 16 * int(c[4]) + int(c[5]);
						uint8_t g = 16 * int(c[6]) + int(c[7]);
						uint8_t b = 16 * int(c[8]) + int(c[9]);
						#if (DEBUGtoSERIAL == 1)
							DebugSerial.print(F("\R: "));
							DebugSerial.println(r);
							DebugSerial.print(F("\G: "));
							DebugSerial.println(g);
							DebugSerial.print(F("\B: "));
							DebugSerial.println(b);
						#endif
						for(uint16_t z = 0; z < strip.numPixels(); z++)
						{
							strip.setPixelColor(z, r, b, g);
						}
						strip.show();
					}
				}
				else Serial.println(F("NOK"));	// On reponds a JEEDOM
			}
		#endif
		#if (UserSketch == 1)
			else if (c[0]=='U' && c[n]=='R')	// User Action
			{
				Serial.println(F("SOK"));				 // On reponds a JEEDOM
				UserAction();
			}
		#endif
			else
			{
				Serial.println(F("NOK"));										// On reponds a JEEDOM
			}
		}
	}
	// On ecoute les pins en entree
	//jeedom="";
	for (int i = 2; i < NB_TOTALPIN; i++)
	{
		byte BPvalue = 0;
		switch (Status_pins[i])
		{
			case 'i':		// input
			case 'p':		// input_pullup
				PinValue = digitalRead(i);
				if (PinValue != OLDPinValue[i] && (PinNextSend[i] < millis() || NextRefresh < millis()))
				{
					OLDPinValue[i] = PinValue;
					jeedom += '&';
					jeedom += i;
					jeedom += '=';
					jeedom += PinValue;
					PinNextSend[i] = millis() + 1000;		// Delai 1s pour eviter trop d'envois
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
					OLDAnalogPinValue[i] = millis() + 1200;   // Delai Appui long
					swtch[i] = PinValue;
				}
				if ((OLDAnalogPinValue[i] < millis() && CounterPinValue[i] != 0) || (PinNextSend[i] < millis() && PinValue != OLDPinValue[i]))
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
				else pinTempo=255;  // si bouton laché après les 10s, on bloque la valeur a 255

				if (PinValue!=OLDPinValue[i]) // changement état entrée = bouton appuyé ou bouton relaché
				{
					OLDPinValue[i]=PinValue;
					if (swtch[i]==1)  // on vient de lacher le bouton.
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
			case 'a':		// analog_input
				AnalogPinValue = analogRead(i);
				if (AnalogPinValue!=OLDAnalogPinValue[i] && (PinNextSend[i]<millis() || NextRefresh<millis()))
				{
					if (abs(int(AnalogPinValue - OLDAnalogPinValue[i])) > 20)		// delta correctif pour eviter les changements negligeables
					{
						int j=i;
						if (i<54) j=i+40;			// petit correctif car  dans Jeedom toutes les pins Analog commencent a l'id 54+
						OLDAnalogPinValue[i]=AnalogPinValue;
						jeedom += '&';
						jeedom += j;
						jeedom += '=';
						jeedom += AnalogPinValue;
						PinNextSend[i]=millis()+5000;		// Delai 5s pour eviter trop d'envois
					}
				}
				break;
			case 'c':		// compteur_pullup CounterPinValue
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
					PinNextSend[i] = millis() + ProbePauseDelay;	// Delai 60s entre chaque mesures pour eviter trop d'envois
					ProbeNextSend = millis() + 5000; // Permet de decaler la lecture entre chaque sonde DHT sinon ne marche pas cf librairie (3000 mini)
				}
				break;
			#endif
			#if (UseDS18x20 == 1)
			case 'b': // DS18x20
				if (PinNextSend[i] < millis() and ProbeNextSend < millis())
				{
					float reponse = read_DSx(i); // DS18x20
					jeedom += '&';
					jeedom += i;
					jeedom += '=';
					jeedom += reponse;
					PinNextSend[i] = millis() + ProbePauseDelay;	// Delai 60s entre chaque mesures pour eviter trop d'envois
					ProbeNextSend = millis() + 12000; // Permet de laisser du temps pour les commandes 'action', probabilite de blocage moins grande idem^^
				}
				break;
			#endif
			#if (UseTeleInfo == 1)
			case 'j': // teleinfoRX
				if (PinNextSend[i]<millis() || NextRefresh<millis())
				{
					#if (DEBUGtoSERIAL == 1)
						DebugSerial.print(F("\nTeleinfoRX ("));
						DebugSerial.print(i);
						DebugSerial.print(F(") : "));
					#endif
					teleinfo.begin(1200);	 // vitesse par EDF
					char recu = 0;
					int cntChar=0;
					timeout = millis()+1000;
					while (recu != 0x02 and timeout>millis())
					{
						if (teleinfo.available()) recu = teleinfo.read() & 0x7F;
						#if (DEBUGtoSERIAL == 1)
							DebugSerial.print(recu);
						#endif
					}
					jeedom += F("&ADCO=");
					timeout = millis()+1000;
					while (timeout>millis())
					{
						if (teleinfo.available())
						{
							recu = teleinfo.read() & 0x7F;
							#if (DEBUGtoSERIAL == 1)
								DebugSerial.print(recu);
							#endif
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
					teleinfo.end();
					#if (DEBUGtoSERIAL == 1)
						DebugSerial.println(F("/finRX"));
					#endif
					PinNextSend[i]=millis()+120000;	// Delai 120s entre chaque mesures pour eviter trop d'envois
				}
				break;
			#endif
			#if (UseBMP180 == 1)
			case 'r': // BMP085/180
				if (PinNextSend[i] < millis())
				{
					jeedom += '&';
					jeedom += i;
					jeedom += '=';
					jeedom += bmp.readTemperature();
					jeedom += '&';
					jeedom += i + 1000;
					jeedom += '=';
					jeedom += bmp.readPressure();
					PinNextSend[i] = millis() + ProbePauseDelay;	// Delai 60s entre chaque mesures pour eviter trop d'envois
				}
				break;
			#endif
			#if (UseBME280 >= 1)
				#if (UseBME280 != 2)
				case 'A': // BME280
					if (PinNextSend[i] < millis())
					{
						jeedom += '&';
						jeedom += i;
						jeedom += '=';
						jeedom += bme280.readTemperature();
						jeedom += '&';
						jeedom += i + 1000;
						jeedom += '=';
						jeedom += bme280.readPressure();
						jeedom += '&';
						jeedom += i + 2000;
						jeedom += '=';
						jeedom += bme280.readHumidity();
						PinNextSend[i] = millis() + ProbePauseDelay;	// Delai 60s entre chaque mesures pour eviter trop d'envois
					}
					break;
				#endif
				#if (UseBME280 >= 2)
				case 'D': // BME280
					if (PinNextSend[i] < millis())
					{
						jeedom += '&';
						jeedom += i;
						jeedom += '=';
						jeedom += bme280b.readTemperature();
						jeedom += '&';
						jeedom += i + 1000;
						jeedom += '=';
						jeedom += bme280b.readPressure();
						jeedom += '&';
						jeedom += i + 2000;
						jeedom += '=';
						jeedom += bme280b.readHumidity();
						PinNextSend[i] = millis() + ProbePauseDelay;	// Delai 60s entre chaque mesures pour eviter trop d'envois
					}
					break;
				#endif
			#endif
			#if (UseBME680 >= 1)
				#if (UseBME680 != 2)
				case 'B': // BME680
					if (PinNextSend[i] < millis() and bme680.performReading())
					{
						jeedom += '&';
						jeedom += i;
						jeedom += '=';
						jeedom += bme680.temperature;
						jeedom += '&';
						jeedom += i + 1000;
						jeedom += '=';
						jeedom += bme680.pressure;
						jeedom += '&';
						jeedom += i + 2000;
						jeedom += '=';
						jeedom += bme680.humidity;
						jeedom += '&';
						jeedom += i + 3000;
						jeedom += '=';
						jeedom += bme680.gas_resistance;
						PinNextSend[i] = millis() + ProbePauseDelay;	// Delai 60s entre chaque mesures pour eviter trop d'envois
					}
					break;
				#endif
				#if (UseBME680 >= 2)
				case 'E': // BME680
					if (PinNextSend[i] < millis() and bme680b.performReading())
					{
						jeedom += '&';
						jeedom += i;
						jeedom += '=';
						jeedom += bme680b.temperature;
						jeedom += '&';
						jeedom += i + 1000;
						jeedom += '=';
						jeedom += bme680b.pressure;
						jeedom += '&';
						jeedom += i + 2000;
						jeedom += '=';
						jeedom += bme680b.humidity;
						jeedom += '&';
						jeedom += i + 3000;
						jeedom += '=';
						jeedom += bme680b.gas_resistance;
						PinNextSend[i] = millis() + ProbePauseDelay;	// Delai 60s entre chaque mesures pour eviter trop d'envois
					}
					break;
				#endif
			#endif
			#if (UseBMP280 >= 1)
				#if (UseBMP280 != 2)
				case 'C': // BMP280
					if (PinNextSend[i] < millis())
					{
						jeedom += '&';
						jeedom += i;
						jeedom += '=';
						jeedom += bmp280.readTemperature();
						jeedom += '&';
						jeedom += i + 1000;
						jeedom += '=';
						jeedom += bmp280.readPressure();
						PinNextSend[i] = millis() + ProbePauseDelay;	// Delai 60s entre chaque mesures pour eviter trop d'envois
					}
					break;
				#endif
				#if (UseBMP280 >= 2)
				case 'F': // BMP280
					if (PinNextSend[i] < millis())
					{
						jeedom += '&';
						jeedom += i;
						jeedom += '=';
						jeedom += bmp280b.readTemperature();
						jeedom += '&';
						jeedom += i + 1000;
						jeedom += '=';
						jeedom += bmp280b.readPressure();
						PinNextSend[i] = millis() + ProbePauseDelay;	// Delai 60s entre chaque mesures pour eviter trop d'envois
					}
					break;
				#endif
			#endif
		}
	}

	#if (UserSketch == 1)
		//UserLoop(); // Appel de votre loop() permanent
		if (NextRefresh < millis()) UserLoop(); // Appel de votre loop() toutes les 60s
	#endif

	if (NextRefresh < millis())
	{
		NextRefresh = millis() + 60000;	// Refresh auto toutes les 60s
		if (RepByJeedom) // sert a verifier que jeedom a bien repondu a la demande dans Load_eeprom
		{
			jeedom += F("&ASK=1"); // Sinon on redemande
		}
	}

	// #if (UseLCD16x2 == 1 || UseLCD16x2 == 2)
		// lcd.setCursor(0,1);
		// lcd.print(jeedom);
	// #endif

	if (jeedom!="") SendToJeedom();
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
		// En cas d'une reception d'une commande user action depuis jeedom
		// c[0]='U' & c[n]='R')
		//
		// c[1] = c[1] - '0';	==5 (user pin start at 500)
		// c[2] = c[2] - '0';
		// c[3] = c[3] - '0';
		// ou : for (int i = 1; i < n; i++) if (isDigit(c[i])) c[i] = c[i] - '0'; // conversion simple char(ascii) vers int
		// int pin_id = 100 * int(c[1]) + 10 * int(c[2]) + int(c[3]); 	// pin action number
		//
		// c[4] to c[n-1] 	// pin action value
		//
		// Ex1:
		// JEEDOM  : Sortie Numérique (Sous-type Jeedom: défaut)
		// ARDUINO : c[] = U5000R -> U 500 0 R = binary 0 pin 500 -> c[4] = '0'
		// ARDUINO : c[] = U5001R -> U 500 1 R = binary 1 pin 500 -> c[4] = '1'
		// Ex2:
		// JEEDOM  : Sortie Numérique (Sous-type Jeedom: curseur)
		// ARDUINO : c[] = U502128R -> U 502 128 R = Slider, Value 128, pin 502 -> c[4] = '1', c[5] = '2', c[5] = '8'
		// Ex3:
		// JEEDOM  : Sortie Numérique (Sous-type Jeedom: message)
		// ARDUINO : c[] = U507[Jeedom] Message|Ceci est un testR -> U 507 [Jeedom] Message | Ceci est un test R = Message, pin 507

		// /!\ attention de ne pas mettre de code bloquant (avec trop de "delays") - max time 2s
	}
#endif

// FONCTIONS

void SendToJeedom()
{
	Serial.println(jeedom);
	#if (DEBUGtoSERIAL == 1)
		DebugSerial.print(F("\nSending: "));
		DebugSerial.println(jeedom);
		DebugSerial.print(F("\nTo eqLogic: "));
		DebugSerial.println(eqLogic);
	#endif
	delay(333);
	jeedom="";
}

void Set_OutputPin(int i)
{
	TempoPinHIGH[i]=0;
	TempoPinLOW[i]=0;
	switch (Status_pins[i])
	{
		#if (UseServo == 1)
		case 'x':
			pinTempo = 100 * int(c[3]) + 10 * int(c[4]) + int(c[5]);
			myServo[i].write(pinTempo);
			delay(15);
			break;
		#endif
		case 'o':			//  output			// S131S pin 13 set to 1 (ou S130S pin 13 set to 0)
		case 'l':			 //  low_relais	// S13S pin 13 set to 0
		case 'h':			//  high_relais   // S13S pin 13 set to 1
			if (c[3]==0)
			{
				PinWriteLOW(i);
			}
			else
			{
				PinWriteHIGH(i);
			}
			break;

		case 's':   //  switch		// S13 pin 13 set to 1 si 0 sinon set to 0 si 1
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
		case 'u':		//	output_pulse	 // Tempo ON : S1309999S : pin 13 set to 0 during 999.9 seconds then set to 1 (S1319999 : set to 1 then to 0)
			pinTempo=10000*int(c[4])+1000*int(c[5])+100*int(c[6])+10*int(c[7])+int(c[8]);
			// pinTempo est donc en dixieme de seconde
			pinTempo = pinTempo*100+millis();   // temps apres lequel la pin doit retourner dans l'autre etat.

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

		case 'v':		//	low_pulse			// Tempo ON : S139999S : pin 13 set to 0 during 999.9 seconds then set to 1
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

		case 'w':		//	high_pulse		// Tempo ON : S139999S : pin 13 set to 1 during 999.9 seconds then set to 0
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

		case 'm':		//	pwm_output
			pinTempo = 100 * int(c[3]) + 10 * int(c[4]) + int(c[5]); 	// the duty cycle: between 0 (always off) and 255 (always on).
			analogWrite(i, pinTempo);
			jeedom += '&';
			jeedom += i;
			jeedom += '=';
			jeedom += pinTempo;
			break;
	}
}

void Load_EEPROM(int k)
{
	// on recupere le BootMode
	BootMode = EEPROM.read(14);
	// Recuperation de l'eqLogic
	eqLogic = F("IDeqLogic");
	eqLogic0 = "";
	n = EEPROM.read(15);				// Recuperation de la longueur du eqLogic
	if (n > 0)		// bug probable si eqLogic_id<10 dans jeedom
	{
		for (int i = 1; i < n; i++)
		{
			eqLogic0 += EEPROM.read(15 + i);
		}
	}
	if (eqLogic != eqLogic0)
	{
		#if (DEBUGtoSERIAL == 1)
			DebugSerial.println(F("Reinit eqID etc"));
			DebugSerial.println();
		#endif
		Init_EEPROM();
	}
	// Recuperation de l'IP
	IP_JEEDOM[0]=EEPROM.read(26);
	IP_JEEDOM[1]=EEPROM.read(27);
	IP_JEEDOM[2]=EEPROM.read(28);
	IP_JEEDOM[3]=EEPROM.read(29);

	// on met en place le mode des pins
	jeedom = "";
	byte y = 1;
	#if (UseTeleInfo == 1)
		teleinfoRX = 0;
		teleinfoTX = 0;
	#endif
	#if (DEBUGtoSERIAL == 1)
		DebugSerial.println(F("Conf. Pins:"));
		for (int i = 0; i < NB_TOTALPIN; i++) DebugSerial.print((char)EEPROM.read(30 + i));
		DebugSerial.println();
	#endif
	// au cas ou l'arduino n'ai pas encore recu la conf. des pins.
	// for (int i = 2; i < NB_TOTALPIN; i++)
	// {
	// 	byte e = EEPROM.read(30 + i);
	// 	if (e < ' ' || e > 'z')
	// 	{
	// 		jeedom += F("&PINMODE=1");
	// 		#if (DEBUGtoSERIAL == 1)
	// 			DebugSerial.println(F("Demande la Conf. Pins."));
	// 			DebugSerial.println();
	// 		#endif
	// 		break;
	// 	}
	// }
	for (int i = 2; i < NB_TOTALPIN; i++)
	{
		Status_pins[i] = EEPROM.read(30 + i); // Etats des pins

		// INITIALISATION DES TABLEAUX DE TEMPO SORTIES
		TempoPinHIGH[i] = 0;
		TempoPinLOW[i] = 0;
		//
		switch (Status_pins[i])
		{
			case 'i':		// input
				OLDPinValue[i] = 2;				//@cpaillet
				PinNextSend[i] = millis();
				break;
			case 'a':		// analog_input
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
				PinNextSend[i] = millis() + ProbePauseDelay;
				break;
			case 'e': // DHT21
				myDHT[i] = new DHT(i, 21);	// DHT21
				PinNextSend[i] = millis() + ProbePauseDelay;
				break;
			case 'f': // DHT 22
				myDHT[i] = new DHT(i, 22);	// DHT22
				PinNextSend[i] = millis() + ProbePauseDelay;
				break;
			#endif
			#if (UseDS18x20 == 1)
			case 'b': // DS18x20
				PinNextSend[i] = millis() + ProbePauseDelay;
				break;
			#endif
			#if (UseServo == 1)
			case 'x':
				myServo[i].attach(i);
				break;
			#endif
			case 't':		// trigger pin
				pinMode(i, OUTPUT);
				digitalWrite(i, LOW);
				break;
			case 'z':		// echo pin
				pinMode(i, INPUT);
				break;
			case 'p':		// input_pullup
				pinMode(i, INPUT_PULLUP);
				OLDPinValue[i] = 2;				//@cpaillet
				PinNextSend[i] = millis();
				break;
			case 'g':     // pwm_input
			case 'q':		// BP_input_pullup
				pinMode(i, INPUT_PULLUP);   // pour eviter les parasites en lecture, mais inverse l'etat de l'entree : HIGH = input open, LOW = input closed
				// Arduino Doc : An internal 20K-ohm resistor is pulled to 5V.
				swtch[i] = 0; 	// init pour pwm_input
				OLDPinValue[i] = 1;
				PinNextSend[i] = millis();
				break;
			case 'c':		// compteur_pullup
				pinMode(i, INPUT_PULLUP);   // pour eviter les parasites en lecture, mais inverse l'etat de l'entree : HIGH = input open, LOW = input closed
				// Arduino Doc : An internal 20K-ohm resistor is pulled to 5V.
				if (k)
				{
					jeedom += F("&CPT_");  // On demande à Jeedom de renvoyer la dernière valeur connue pour la pin i
					jeedom += i;
					jeedom += '=';
					jeedom += i;
				}
				break;

			case 'o':		//	output
			case 's':		//	switch
			case 'l':		//	low_relais
			case 'h':		//	high_relais
			case 'u':		//	output_pulse
			case 'v':		//	low_pulse
			case 'w':		//	high_pulse
			case 'y':		 //	double_pulse
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
							// On demade a Jeedom d'envoyer la valeur des pins
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
							// On demade a Jeedom d'envoyer la valeur des pins
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

			case 'm':		//	pwm_output
				pinMode(i, OUTPUT);
				break;
		}
	}
	#if (UseTeleInfo == 1)
	if (teleinfoRX != 0)
	{
		#if (DEBUGtoSERIAL == 1)
		DebugSerial.print(F("\nteleinfoRX:"));
		DebugSerial.println(teleinfoRX);
		DebugSerial.print(F("\nteleinfoTX:"));
		DebugSerial.println(teleinfoTX);
		#endif
		//SoftwareSerial teleinfo(teleinfoRX, teleinfoTX);
	}
	#endif
  if (jeedom != "") SendToJeedom();
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
		DebugSerial.print(F("SetPin "));
		DebugSerial.print(p);
		DebugSerial.println(F(" to 1"));
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
		DebugSerial.print(F("SetPin "));
		DebugSerial.print(p);
		DebugSerial.println(F(" to 0"));
	#endif
}

void Init_EEPROM()
{
	// Un marqueur
	EEPROM.update(13,  'J');		 // JEEDOUINO

	// BootMode choisi au demarrage de l'arduino
	// 0 = Pas de sauvegarde - Toutes les pins sorties non modifiï¿½es au dï¿½marrage.
	// 1 = Pas de sauvegarde - Toutes les pins sorties mises ï¿½ LOW au dï¿½marrage.
	// 2 = Pas de sauvegarde - Toutes les pins sorties mises ï¿½ HIGH au dï¿½marrage.
	// 3 = Sauvegarde sur JEEDOM - Toutes les pins sorties mises suivant leur sauvegarde dans Jeedom. Jeedom requis, sinon pins mises ï¿½ OFF.
	// 4 = Sauvegarde sur EEPROM- Toutes les pins sorties mises suivant leur sauvegarde dans l\'EEPROM. Autonome, mais durï¿½e de vie de l\'eeprom fortement rï¿½duite.
	EEPROM.update(14,  2);
	BootMode=2;

	// Initialisation par default
	EEPROM.update(15,	0);
	for (int i = 16; i < 200; i++)
	{
		EEPROM.update(i, 1);  // Valeur des pins OUT au 1er demarrage ( mes relais sont actis a 0, donc je met 1 pour eviter de les actionner au 1er boot)
	}
	// fin initialisation
}

#if (UseDS18x20 == 1)
int read_DSx(int pinD)
{
	byte data[12];
	byte addr[8];
	long first, temp;
	char buffer[3];
	OneWire ds(pinD);
	byte nb_ds18 = 0;

	ds.reset_search();
	while (ds.search(addr))
	{
		if (OneWire::crc8(addr, 7) != addr[7]) //Check if there is no errors on transmission
		{
			#if (DEBUGtoSERIAL == 1)
				DebugSerial.println(F("CRC invalide..."));
			#endif
			return 9999;
		}
		if (addr[0] != 0x28)
		{
			#if (DEBUGtoSERIAL == 1)
				DebugSerial.println(F("Device is not a DS18B20."));
			#endif
			return 9999;
		}
		ds.reset();
		ds.select(addr);
		ds.write(0x44, 1);
		nb_ds18++;
		delay(250);
	}
	if (nb_ds18 == 0)
	{
		ds.reset_search();
		#if (DEBUGtoSERIAL == 1)
			DebugSerial.println(F("ds not found..."));
		#endif
		return 9999;
	}
	nb_ds18 = 0;
	delay(800);
	jeedom = F("&DS18list_");
	jeedom += pinD;
	jeedom += F("={");
	ds.reset_search();
	while (ds.search(addr))
	{
		jeedom += '"';
		jeedom += F("28-");
		for (int ii = 6; ii > 0; ii--)
		{
			if (addr[ii] < 16) jeedom += '0';
			itoa (addr[ii], buffer, 16);
			jeedom += buffer;
		}
		jeedom += '"';
		jeedom += ':';
		jeedom += '"';
		ds.reset();
		ds.select(addr);
		ds.write(0xBE);
		for (int ii = 0; ii < 9; ii++)
		{
			data[ii] = ds.read();
		}
		temp = (int16_t) ((data[1] << 8) | data[0]) * 6.25;
		if (nb_ds18 == 0) first = temp;
		nb_ds18++;
		#if (DEBUGtoSERIAL == 1)
			DebugSerial.println(temp / 100);
		#endif
		jeedom += temp;
		jeedom += '"';
		jeedom += ',';
	}
	jeedom += '}';
	return first;
}
#endif

#if (UseWS2811 == 1)
// Code below is from https://github.com/adafruit/Adafruit_NeoPixel/blob/master/examples/buttoncycler/buttoncycler.ino
// More info at https://github.com/adafruit/Adafruit_NeoPixel
void startShow(int i) {
	switch(i){
		case 0: colorWipe(strip.Color(0, 0, 0), 50);		// Black/off
						break;
		case 1: colorWipe(strip.Color(255, 0, 0), 50);	// Red
						break;
		case 2: colorWipe(strip.Color(0, 255, 0), 50);	// Green
						break;
		case 3: colorWipe(strip.Color(0, 0, 255), 50);	// Blue
						break;
		case 4: colorWipe(strip.Color(255, 255, 255), 50);	// White
						break;
		case 5: colorWipe(strip.Color(255, 255, 0), 50);	// Magenta
						break;
		case 6: colorWipe(strip.Color(255, 0, 255), 50);	// Yellow
						break;
		case 7: colorWipe(strip.Color(0, 255, 255), 50);	// Cyan
						break;

		case 8: theaterChase(strip.Color(127, 0, 0), 50); // Red
						break;
		case 9: theaterChase(strip.Color(0, 127, 0), 50); // Green
						break;
		case 10: theaterChase(strip.Color(0, 0, 127), 50); // Blue
						break;
		case 11: theaterChase(strip.Color(127, 127, 127), 50); // White
						break;
		case 12: theaterChase(strip.Color(127, 127, 0), 50); // Magenta
						break;
		case 13: theaterChase(strip.Color(127, 0, 127), 50); // Yellow
						break;
		case 14: theaterChase(strip.Color(0, 127, 127), 50); // Cyan
						break;

		case 15: rainbow(20);
						break;
		case 16: rainbowCycle(20);
						break;
		case 17: theaterChaseRainbow(50);
						break;
	}
}

// Fill the dots one after the other with a color
void colorWipe(uint32_t c, uint8_t wait) {
	for(uint16_t i=0; i<strip.numPixels(); i++) {
		strip.setPixelColor(i, c);
		strip.show();
		delay(wait);
	}
}

void rainbow(uint8_t wait) {
	uint16_t i, j;

	for(j=0; j<256; j++) {
		for(i=0; i<strip.numPixels(); i++) {
			strip.setPixelColor(i, Wheel((i+j) & 255));
		}
		strip.show();
		delay(wait);
	}
}

// Slightly different, this makes the rainbow equally distributed throughout
void rainbowCycle(uint8_t wait) {
	uint16_t i, j;

	for(j=0; j<256*5; j++) { // 5 cycles of all colors on wheel
		for(i=0; i< strip.numPixels(); i++) {
			strip.setPixelColor(i, Wheel(((i * 256 / strip.numPixels()) + j) & 255));
		}
		strip.show();
		delay(wait);
	}
}

//Theatre-style crawling lights.
void theaterChase(uint32_t c, uint8_t wait) {
	for (int j=0; j<10; j++) {	//do 10 cycles of chasing
		for (int q=0; q < 3; q++) {
			for (int i=0; i < strip.numPixels(); i=i+3) {
				strip.setPixelColor(i+q, c);		//turn every third pixel on
			}
			strip.show();

			delay(wait);

			for (int i=0; i < strip.numPixels(); i=i+3) {
				strip.setPixelColor(i+q, 0);				//turn every third pixel off
			}
		}
	}
}

//Theatre-style crawling lights with rainbow effect
void theaterChaseRainbow(uint8_t wait) {
	for (int j=0; j < 256; j++) {		 // cycle all 256 colors in the wheel
		for (int q=0; q < 3; q++) {
			for (int i=0; i < strip.numPixels(); i=i+3) {
				strip.setPixelColor(i+q, Wheel( (i+j) % 255));		//turn every third pixel on
			}
			strip.show();

			delay(wait);

			for (int i=0; i < strip.numPixels(); i=i+3) {
				strip.setPixelColor(i+q, 0);				//turn every third pixel off
			}
		}
	}
}

// Input a value 0 to 255 to get a color value.
// The colours are a transition r - g - b - back to r.
uint32_t Wheel(byte WheelPos) {
	WheelPos = 255 - WheelPos;
	if(WheelPos < 85) {
		return strip.Color(255 - WheelPos * 3, 0, WheelPos * 3);
	}
	if(WheelPos < 170) {
		WheelPos -= 85;
		return strip.Color(0, WheelPos * 3, 255 - WheelPos * 3);
	}
	WheelPos -= 170;
	return strip.Color(WheelPos * 3, 255 - WheelPos * 3, 0);
}
#endif

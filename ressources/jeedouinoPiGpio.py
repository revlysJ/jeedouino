"""
JEEDOUINO PIGPIO DEMON v0.8 Dec2015 - 2020
Modif de simplewebcontrol.py pour utilisation avec Jeedom
Original : https://github.com/piface/pifacedigitalio/blob/master/examples/simplewebcontrol.py
				https://piface.github.io/pifacedigitalio/example.html#interrupts
et de
				Alex Eames http://RasPi.TV
				http://RasPi.TV/?p=4320
				RPi.GPIO Official Documentation http://sourceforge.net/p/raspberry-gpio-python/wiki/Home/
				http://www.tutorialspoint.com/python/python_multithreading.htm
				https://github.com/adafruit/Adafruit_Python_DHT
				https://github.com/danjperron/BitBangingDS18B20
"""

import socket
import threading
import os, time
import sys

try:
	import http.client as httplib
except:
	import httplib
import RPi.GPIO as GPIO
import Adafruit_DHT
import Adafruit_BMP.BMP085 as BMP085
os.environ['TZ'] = 'Europe/Paris'
time.tzset()
import DS18B20 as DS
try:
	import board, busio, adafruit_bmp280, adafruit_bme280, adafruit_bme680
	nodep = 0
except Exception as e:
	errdep = e
	nodep = 1

bme280 = None
bmp280 = None
bme680 = None
bme280b = None
bmp280b = None
bme680b = None

sensors = {}
sendPINMODE = 0
port = 8001
portusb = ''
JeedomIP = ''
eqLogic = ''
JeedomPort = 80
JeedomCPL = ''
pin2gpio = [0,0,2,0,3,0,4,14,0,15,17,18,27,0,22,23,0,24,10,0,9,25,11,8,0,7,0,0,5,0,6,12,13,0,19,16,26,20,0,21]
gpio2pin = [0,0,3,5,7,29,31,26,24,21,19,23,32,33,8,10,36,11,12,35,38,40,15,16,18,22,37,13,0,0,0,0,0,0,0,0,0,0,0,0]

# compteurs:
ReArmDelay = 3600 # secondes
CptNextReArm = time.time() + ReArmDelay
bounceDelay = 222 # millisecondes


# s-Fallback
BootMode = False
Status_pins = {}
# e-Fallback

ProbeDelay = 5
bmp180 = False
bme280 = False
bme680 = False

gpioSET = False
# Tests Threads alives
thread_1 = 0
thread_2 = 0

logFile = "JeedouinoPiGpio.log"

def log(level,message):
	fifi = open(logFile, "a+")
	try:
		fifi.write('[%s][Demon PiGpio][%s] %s : %s' % (time.strftime('%Y-%m-%d %H:%M:%S', time.localtime()), str(eqLogic), str(level), str(message)))
	except:
		print('[%s][Demon PiGpio][%s] %s : %s' % (time.strftime('%Y-%m-%d %H:%M:%S', time.localtime()), str(eqLogic), str(level), str(message)))
	fifi.write("\r\n")
	fifi.close()

def SimpleParse(m):
	m = m.decode("utf-8")
	m = m.replace('/', '')
	u = m.find('?')
	if u > -1:
		u += 1
		v = m.find(' HTTP', u)
		if v > -1:
			url = m[u:v]
			cmds = url.split("&")
			get = []
			for i in cmds:
				try:
					a,b = i.split("=")
					get.append(a)
					get.append(b)
				except:
					log('erreur', 'Un element est manquant dans :"' + str(i) + '" .')
					get.append(i)
			return get
		else:
			return 0
	else:
		return 0

class myThread1 (threading.Thread):
	def __init__(self, threadID, name):
		threading.Thread.__init__(self)
		self.threadID = threadID
		self.name = name

	def run(self):
		log('info', "Starting " + self.name)
		global eqLogic,JeedomIP,PinNextSend,TempoPinLOW,TempoPinHIGH,exit,Status_pins,swtch,GPIO,SetAllLOW,SetAllHIGH,CounterPinValue,s,BootMode,SetAllSWITCH,SetAllPulseLOW,SetAllPulseHIGH,ProbeDelay,thread_1,thread_2,thread_tries1,bmp180,bmp280,bme280,bme680,bmp280b,bme280b,bme680b,gpioSET,sendPINMODE,busio,CptNextReArm,ReArmDelay,bounceDelay
		s = socket.socket()		 		# Create a socket object
		s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
		#host = socket.gethostname() 	# Get local machine name
		try:
			s.bind(('', port))					# Bind to the port
		except:
			s.close() 							# rate
			log('erreur','Le port est peut-etre utilise. Nouvel essai dans 11s.')
			SimpleSend('&PORTISUSED=' + str(port))
			time.sleep(11)					# on attend un peu
			s = socket.socket()
			s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
			try:
				s.bind(('', port))					# nouvel essai
			except:
				s.close() 							# rate
				log('erreur','Le port est probablement utilise. Nouvel essai en mode auto dans 7s.')
				SimpleSend('&PORTINUSE=' + str(port))
				time.sleep(7)					# on attend encore un peu
				s = socket.socket()
				s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
				portnew = 0
				try:
					s.bind(('', 0))					# on essai en auto decouverte
					addr, portnew = s.getsockname()
					log('debug','Un port libre est disponible : ' + str(portnew))
					SimpleSend('&PORTFOUND=' + str(portnew))
				except:
					log('erreur','Impossible de trouver un port automatiquement. Veuillez en choisir un autre')
					SimpleSend('&NOPORTFOUND=' + str(port))
					s.close()
					exit = 1
					raise e

		s.listen(5)								# Now wait for client connection.
		while exit == 0:
			thread_1 = 1
			c, addr = s.accept()			 # Establish connection with client.
			if exit == 1:
				break
			m = c.recv(1024)
			thread_tries1 = 0
			query = SimpleParse(m)
			if query:
				log ('Requete', str(query))

				reponse = 'NOK'
				exit = 0
				RepStr = ''
				GPIOStr = ''

				if 'BootMode' in query:
					q = query.index("BootMode")
					BootMode = int(query[q + 1])
					reponse = 'BMOK'

				if 'ConfigurePins' in query:
					q = query.index("ConfigurePins")
					Status_pins = query[q + 1]
					sendPINMODE = 1

					for i in range(0, 40):
						j = pin2gpio[i]
						if Status_pins[i] == 'o' or Status_pins[i] == 'y' or Status_pins[i] == 's' or Status_pins[i] == 'l' or Status_pins[i] == 'h' or Status_pins[i] == 'u' or Status_pins[i] == 'v' or Status_pins[i] == 'w':
							GPIO.setup(j, GPIO.OUT)
							GPIO.remove_event_detect(j)
							GPIO.output(j, BootMode)
							swtch[i + 1] = BootMode
							GPIOStr += '&' + str(i + 1) + '=' + str(BootMode)
						elif Status_pins[i] == 'c':
							PinNextSend[i + 1] = time.time() + 30  #30s environ
							k = i % 4
							GPIO.setup(j, GPIO.IN,  pull_up_down = GPIO.PUD_UP)
							GPIO.remove_event_detect(j)
							if k == 1:
								GPIO.add_event_detect(j, GPIO.FALLING, callback = toggle_cpts1, bouncetime = bounceDelay)
							elif k == 2:
								GPIO.add_event_detect(j, GPIO.FALLING, callback = toggle_cpts2, bouncetime = bounceDelay)
							elif k == 3:
								GPIO.add_event_detect(j, GPIO.FALLING, callback = toggle_cpts3, bouncetime = bounceDelay)
							else:
								GPIO.add_event_detect(j, GPIO.FALLING, callback = toggle_cpts0, bouncetime = bounceDelay)
							time.sleep(0.1)
						elif Status_pins[i] == 'G':
							PinNextSend[i + 1] = time.time() + 30  #30s environ
							k = i % 4
							GPIO.setup(j, GPIO.IN,  pull_up_down = GPIO.PUD_DOWN)
							GPIO.remove_event_detect(j)
							if k == 1:
								GPIO.add_event_detect(j, GPIO.RISING, callback = toggle_cpts1, bouncetime = bounceDelay)
							elif k == 2:
								GPIO.add_event_detect(j, GPIO.RISING, callback = toggle_cpts2, bouncetime = bounceDelay)
							elif k == 3:
								GPIO.add_event_detect(j, GPIO.RISING, callback = toggle_cpts3, bouncetime = bounceDelay)
							else:
								GPIO.add_event_detect(j, GPIO.RISING, callback = toggle_cpts0, bouncetime = bounceDelay)
							time.sleep(0.1)
						elif Status_pins[i] == 'n':
							GPIO.setup(j, GPIO.IN,  pull_up_down = GPIO.PUD_DOWN)
							GPIO.remove_event_detect(j)
							GPIO.add_event_detect(j, GPIO.BOTH, callback = toggle_inputs)
						elif Status_pins[i] == 'q':
							GPIO.setup(j, GPIO.IN,  pull_up_down = GPIO.PUD_UP)
							GPIO.remove_event_detect(j)
							GPIO.add_event_detect(j, GPIO.BOTH, callback = toggle_inputs)
						elif Status_pins[i] == 'i':
							GPIO.setup(j, GPIO.IN,  pull_up_down = GPIO.PUD_DOWN)
							GPIO.remove_event_detect(j)
							GPIO.add_event_detect(j, GPIO.BOTH, callback = toggle_inputs)
							GPIOStr +='&IN_' + str(i + 1) + '=' + str(GPIO.input(j))
						elif Status_pins[i] == 'p':
							GPIO.setup(j, GPIO.IN,  pull_up_down = GPIO.PUD_UP)
							GPIO.remove_event_detect(j)
							GPIO.add_event_detect(j, GPIO.BOTH, callback = toggle_inputs)
							GPIOStr += '&IN_' + str(i + 1) + '=' + str(GPIO.input(j))
						elif Status_pins[i] == 'd' or Status_pins[i] == 'f': # Sondes DHT(11,22)
							GPIO.setup(j, GPIO.IN,  pull_up_down = GPIO.PUD_DOWN)
							GPIO.remove_event_detect(j)
						elif Status_pins[i] == 'b': # Sondes DS18b20
							GPIO.setup(j, GPIO.IN,  pull_up_down = GPIO.PUD_DOWN)
							GPIO.remove_event_detect(j)
							sensors[i] = DS.scan(pin2gpio[i])
						elif Status_pins[i] == 't': 					#HC-SR04 Declencheur (Trigger pin)
							GPIO.setup(j, GPIO.OUT)
							GPIO.remove_event_detect(j)
							GPIO.output(j, False)
						elif Status_pins[i] == 'z': 					#HC-SR04 Distance (Echo pin)
							GPIO.setup(j, GPIO.IN,  pull_up_down = GPIO.PUD_DOWN)
							GPIO.remove_event_detect(j)
						elif Status_pins[i] == 'r':
							bmp180 = BMP085.BMP085()
						elif Status_pins[i] == 'A':
							i2c = busio.I2C(board.SCL, board.SDA)
							try:
								bme280 = adafruit_bme280.Adafruit_BME280_I2C(i2c, 118) # hex76 = 118
								bme280.sea_level_pressure = 1013.25
							except Exception as e:
								log('Error' , 'Impossible de trouver la sonde bme280 sur x76. - ' + str(e))
								SimpleSend('&NOBMEP=bme280(x76)')
								exit = 1
								break
						elif Status_pins[i] == 'D':
							i2c = busio.I2C(board.SCL, board.SDA)
							try:
								bme280b = adafruit_bme280.Adafruit_BME280_I2C(i2c) #hex77 default
								bme280b.sea_level_pressure = 1013.25
							except Exception as e:
								log('Error' , 'Impossible de trouver la sonde bme280 sur x77. - ' + str(e))
								SimpleSend('&NOBMEP=bme280(x77)')
								exit = 1
								break
						elif Status_pins[i] == 'B':
							i2c = busio.I2C(board.SCL, board.SDA)
							try:
								bme680 = adafruit_bme680.Adafruit_BME680_I2C(i2c, 118, debug=False)
								bme680.sea_level_pressure = 1013.25
							except Exception as e:
								log('Error' , 'Impossible de trouver la sonde bme680 sur x76. - ' + str(e))
								SimpleSend('&NOBMEP=bme680(x76)')
								exit = 1
								break
						elif Status_pins[i] == 'E':
							i2c = busio.I2C(board.SCL, board.SDA)
							try:
								bme680b = adafruit_bme680.Adafruit_BME680_I2C(i2c, debug=False)
								bme680b.sea_level_pressure = 1013.25
							except Exception as e:
								log('Error' , 'Impossible de trouver la sonde bme680 sur x77. - ' + str(e))
								SimpleSend('&NOBMEP=bme680(x77)')
								exit = 1
								break
						elif Status_pins[i] == 'C':
							i2c = busio.I2C(board.SCL, board.SDA)
							try:
								bmp280 = adafruit_bmp280.Adafruit_BMP280_I2C(i2c, 118)
								bmp280.sea_level_pressure = 1013.25
							except Exception as e:
								log('Error' , 'Impossible de trouver la sonde bmp280 sur x76. - ' + str(e))
								SimpleSend('&NOBMEP=bmp280(x76)')
								exit = 1
								break
						elif Status_pins[i] == 'F':
							i2c = busio.I2C(board.SCL, board.SDA)
							try:
								bmp280b = adafruit_bmp280.Adafruit_BMP280_I2C(i2c)
								bmp280b.sea_level_pressure = 1013.25
							except Exception as e:
								log('Error' , 'Impossible de trouver la sonde bmp280 sur x77. - ' + str(e))
								SimpleSend('&NOBMEP=bmp280(x77)')
								exit = 1
								break
					if exit == 1:
						reponse = 'NOK'
					else:
						reponse = 'COK'
					RepStr = '&REP=' + str(reponse) + GPIOStr
					gpioSET = True

				if 'eqLogic' in query:
					q = query.index("eqLogic")
					eqLogic = query[q + 1]
					reponse = 'EOK'

				if 'JeedomIP' in query:
					q = query.index("JeedomIP")
					JeedomIP = query[q + 1]
					reponse = 'IPOK'

				if 'SetPinLOW' in query:
					q = query.index("SetPinLOW")
					u = int(query[q + 1])
					reponse = 'SOK'
					SetPin(u, 0 ,reponse)

				if 'SetPinHIGH' in query:
					q = query.index("SetPinHIGH")
					u = int(query[q + 1])
					reponse = 'SOK'
					SetPin(u, 1, reponse)

				if 'SetLOWpulse' in query:
					q = query.index("SetLOWpulse")
					u = int(query[q + 1])
					q = query.index("tempo")
					TempoPinLOW[u] = time.time() * 10 + int(query[q + 1])
					reponse = 'SOK'
					SetPin(u, 0, reponse)

				if 'SetHIGHpulse' in query:
					q = query.index("SetHIGHpulse")
					u = int(query[q + 1])
					q = query.index("tempo")
					TempoPinHIGH[u] = time.time() * 10 + int(query[q + 1])
					reponse = 'SOK'
					SetPin(u, 1, reponse)

				if 'SwitchPin' in query:
					q = query.index("SwitchPin")
					u = int(query[q + 1])
					v = 1 - swtch[u]
					reponse = 'SOK'
					SetPin(u, v, reponse)

				if 'SetCPT' in query:
					q = query.index("SetCPT")
					u = int(query[q + 1])
					q = query.index("ValCPT")
					ValCPT = int(query[q + 1])
					CounterPinValue[u] += ValCPT
					reponse = 'SCOK'
					RepStr = '&REP=' + str(reponse)

				if 'RazCPT' in query:
					q = query.index("RazCPT")
					u = int(query[q + 1])
					q = query.index("ValCPT")
					ValCPT = int(query[q + 1])
					CounterPinValue[u] = ValCPT
					reponse = 'SCOK'
					RepStr = '&REP=' + str(reponse)

				if 'SetAllLOW' in query:
					SetAllLOW = 1 # deport dans l'autre thread question de vitesse d'execution
					reponse = 'SOK'

				if 'SetAllHIGH' in query:
					SetAllHIGH = 1 # deport dans l'autre thread question de vitesse d'execution
					reponse = 'SOK'

				if 'SetAllSWITCH' in query:
					SetAllSWITCH = 1 # deport dans l'autre thread question de vitesse d'execution
					reponse = 'SOK'

				if 'SetAllPulseLOW' in query:
					RepStr = '&REP=SOK'
					q = query.index("tempo")
					for i in range(0, 40):
						j = i + 1
						if Status_pins[i] == 'o' or Status_pins[i] == 's' or Status_pins[i] == 'l' or Status_pins[i] == 'h' or Status_pins[i] == 'u' or Status_pins[i] == 'v' or Status_pins[i] == 'w':
							swtch[j] = 0
							GPIO.output(pin2gpio[i], 0)
							TempoPinLOW[j] = time.time() * 10 + int(query[q + 1])
							RepStr += '&' + str(j) + '=0'
					reponse = 'SOK'

				if 'SetAllPulseHIGH' in query:
					RepStr = '&REP=SOK'
					q = query.index("tempo")
					for i in range(0, 40):
						j = i + 1
						if Status_pins[i] == 'o' or Status_pins[i] == 's' or Status_pins[i] == 'l' or Status_pins[i] == 'h' or Status_pins[i] == 'u' or Status_pins[i] == 'v' or Status_pins[i] == 'w':
							swtch[j] = 1
							GPIO.output(pin2gpio[i], 1)
							TempoPinHIGH[j] = time.time() * 10 + int(query[q + 1])
							RepStr += '&' + str(j) + '=1'
					reponse = 'SOK'

				if 'Trigger' in query:
					q = query.index("Trigger")
					u = int(query[q + 1])
					q = query.index("Echo")
					v = int(query[q + 1])
					RepStr = GetDistance(u,v)
					reponse = 'SOK'

				if 'SetLOWdoublepulse' in query:
					q = query.index("SetLOWdoublepulse")
					u = int(query[q + 1])
					r = pin2gpio[u - 1]
					q = query.index("tempclick")
					v = float(query[q + 1]) / 10
					q = query.index("temppause")
					w = float(query[q + 1]) / 10
					GPIO.output(r, 0)
					time.sleep(v)
					GPIO.output(r, 1)
					time.sleep(w)
					GPIO.output(r, 0)
					time.sleep(v)
					reponse = 'SOK'
					SetPin(u, 1, reponse)

				if 'SetHIGHdoublepulse' in query:
					q = query.index("SetHIGHdoublepulse")
					u = int(query[q + 1])
					r = pin2gpio[u - 1]
					q = query.index("tempclick")
					v = float(query[q + 1]) / 10
					q = query.index("temppause")
					w = float(query[q + 1]) / 10
					GPIO.output(r, 1)
					time.sleep(v)
					GPIO.output(r, 0)
					time.sleep(w)
					GPIO.output(r, 1)
					time.sleep(v)
					reponse = 'SOK'
					SetPin(u, 0, reponse)

				if 'CptDelay' in query:
					q = query.index("CptDelay")
					ReArmDelay = int(query[q + 1])
					reponse = 'SCOK'

				if 'bounceDelay' in query:
					q = query.index("bounceDelay")
					bounceDelay = int(query[q + 1])
					reponse = 'SCOK'

				if 'ProbeDelay' in query:
					q = query.index("ProbeDelay")
					ProbeDelay = int(query[q + 1])
					reponse = 'SOK'

				if 'PING' in query:
					if thread_2 == 1:
						reponse = 'PINGOK'
					else:
						reponse = 'PINGKO'
					RepStr = '&REP=' + str(reponse)

				if 'EXIT' in query:
					exit = 1
					reponse = 'EXITOK'

				if reponse != '':
					c.send(reponse.encode('ascii'))
					log ('>> Reponse a la requete' ,str(reponse))
					if RepStr != '':
						SimpleSend(RepStr)

				if exit == 1:
					break

			c.close()
			time.sleep(0.1)
		s.close()
		if exit == 1:
			try:
				GPIO.cleanup()
			except:
				pass
			sys.exit()

def SetPin(u, v, m):
	global swtch
	swtch[u] = v
	GPIO.output(pin2gpio[u - 1], v)
	pinStr = '&' + str(u) + '=' + str(v)
	if m != '':
		pinStr += '&REP=' + str(m)
	SimpleSend(pinStr)

def toggle_cpts0(u):
	global CounterPinValue
	uu = gpio2pin[u]
	CounterPinValue[uu] += 1

def toggle_cpts1(u):
	global CounterPinValue
	uu = gpio2pin[u]
	CounterPinValue[uu] += 1

def toggle_cpts2(u):
	global CounterPinValue
	uu = gpio2pin[u]
	CounterPinValue[uu] += 1

def toggle_cpts3(u):
	global CounterPinValue
	uu = gpio2pin[u]
	CounterPinValue[uu] += 1

def toggle_inputs(u):
	global Status_pins, GPIO, NextRefresh, ProbeDelay, PinNextSend
	#GPIO.remove_event_detect(u)
	uu = gpio2pin[u]
	BPvalue = 1
	if Status_pins[uu - 1] == 'n' or Status_pins[uu - 1] == 'q':
		GPIO.remove_event_detect(u)

		NewNextRefresh = time.time() + (60 * ProbeDelay) 			# Decale la lecture des sondes pour eviter un conflit
		if NextRefresh < NewNextRefresh:
			NextRefresh = NewNextRefresh
		if Status_pins[uu - 1] == 'q':
			BPvalue = 0

		TimeOut = time.time() + 1.2
		count = 0
		etat = 1 - BPvalue
		while time.time() < TimeOut:
			v = GPIO.input(u)
			if v != etat:
				if v != BPvalue:
					count += 1
				etat = 1 - etat
				#TimeOut += 0.05
				time.sleep(0.05)
		if count == 0:
			count = 99
		pinStr = '&' + str(uu) + '=' + str(count)
		SimpleSend(pinStr)
		while GPIO.input(u) == BPvalue:
			time.sleep(0.01)
		GPIO.add_event_detect(u, GPIO.BOTH, callback = toggle_inputs)
	else:
		v = GPIO.input(u)
		pinStr = '&' + str(uu) + '=' + str(v)
		SimpleSend(pinStr)
		PinNextSend[uu] = time.time() + 2 # renvoie la valeur du gpio si non detectée car changement trop rapide
		#GPIO.add_event_detect(u, GPIO.BOTH, callback = toggle_inputs, bouncetime = bounceDelay)

class myThread2 (threading.Thread):
	def __init__(self, threadID, name):
		threading.Thread.__init__(self)
		self.threadID = threadID
		self.name = name

	def run(self):
		log('info', "Starting " + self.name)
		global TempoPinLOW,TempoPinHIGH,exit,swtch,GPIO,SetAllLOW,SetAllHIGH,Status_pins,sendCPT,timeCPT,s,NextRefresh,CounterPinValue,SetAllSWITCH,SetAllPulseLOW,SetAllPulseHIGH,PinNextSend,ProbeDelay,thread_1,thread_2,thread_tries2,bmp180,bmp280,bme280,bme680,bmp280b,bme280b,bme680b,sendPINMODE,CptNextReArm,ReArmDelay,bounceDelay

		while exit==0:
			thread_2 = 1
			pinStr = ''
			for i in range(1, 41):
				if TempoPinHIGH[i]!=0 and TempoPinHIGH[i]<int(time.time()*10):
					TempoPinHIGH[i]=0
					swtch[i] = 0
					GPIO.output(pin2gpio[i - 1], 0)
					pinStr += '&' + str(i) + '=0'
				elif TempoPinLOW[i]!=0 and TempoPinLOW[i]<int(time.time()*10):
					TempoPinLOW[i]=0
					swtch[i] = 1
					GPIO.output(pin2gpio[i - 1], 1)
					pinStr += '&' + str(i) + '=1'
			if pinStr != '':
				SimpleSend(pinStr)

			thread_tries2 = 0
			if SetAllLOW==1:
				pinStr = '&REP=SOK'
				for i in range(0, 40):
					j = i + 1
					if Status_pins[i] == 'o' or Status_pins[i] == 's' or Status_pins[i] == 'l' or Status_pins[i] == 'h' or Status_pins[i] == 'u' or Status_pins[i] == 'v' or Status_pins[i] == 'w':
						swtch[j] = 0
						GPIO.output(pin2gpio[i], 0)
						pinStr += '&' + str(j) + '=0'
				SetAllLOW=0
				SimpleSend(pinStr)

			if SetAllHIGH==1:
				pinStr = '&REP=SOK'
				for i in range(0, 40):
					j = i + 1
					if Status_pins[i] == 'o' or Status_pins[i] == 's' or Status_pins[i] == 'l' or Status_pins[i] == 'h' or Status_pins[i] == 'u' or Status_pins[i] == 'v' or Status_pins[i] == 'w':
						swtch[j] = 1
						GPIO.output(pin2gpio[i], 1)
						pinStr += '&' + str(j) + '=1'
				SetAllHIGH=0
				SimpleSend(pinStr)

			if SetAllSWITCH==1:
				pinStr = '&REP=SOK'
				for i in range(0, 40):
					j = i + 1
					if Status_pins[i] == 'o' or Status_pins[i] == 's' or Status_pins[i] == 'l' or Status_pins[i] == 'h' or Status_pins[i] == 'u' or Status_pins[i] == 'v' or Status_pins[i] == 'w':
						if swtch[j] == 0:
							swtch[j] = 1
							GPIO.output(pin2gpio[i], 1)
							pinStr += '&' + str(j) + '=1'
						else:
							swtch[j] = 0
							GPIO.output(pin2gpio[i], 0)
							pinStr += '&' + str(j) + '=0'
				SetAllSWITCH=0
				SimpleSend(pinStr)

			# On envoi le nombre d'impulsions connu si il n'y en a pas eu dans les 10s depuis le dernier envoi
			pinStr = ''
			for i in range(0, 40):
				j = i + 1
				if (Status_pins[i] == 'c' or Status_pins[i] == 'G') and PinNextSend[j] < time.time():
					pinStr +='&' + str(j) + '=' + str(CounterPinValue[j])
					PinNextSend[j] = time.time() + 30  #30s environ
				elif (Status_pins[i] == 'i' or Status_pins[i] == 'p') and PinNextSend[j] < time.time()  and PinNextSend[j] != 0:
					v = GPIO.input(pin2gpio[i])
					pinStr += '&' + str(j) + '=' + str(v)
					PinNextSend[j] = 0
			if pinStr!='':
				SimpleSend(pinStr)

			# Essai : ReArm compteur si bloqué (toutes les heures)
			if CptNextReArm < time.time():
				CptNextReArm = time.time() + ReArmDelay
				for i in range(0, 40):
					if Status_pins[i] == 'c' or Status_pins[i] == 'G':
						GPIO.remove_event_detect(pin2gpio[i])
				time.sleep(7)
				for i in range(0, 40):
					j = pin2gpio[i]
					if Status_pins[i] == 'c':
						PinNextSend[i + 1] = time.time() + 30  #30s environ
						k = i % 4
						GPIO.setup(j, GPIO.IN,  pull_up_down = GPIO.PUD_UP)
						if k == 1:
							GPIO.add_event_detect(j, GPIO.FALLING, callback = toggle_cpts1, bouncetime = bounceDelay)
						elif k == 2:
							GPIO.add_event_detect(j, GPIO.FALLING, callback = toggle_cpts2, bouncetime = bounceDelay)
						elif k == 3:
							GPIO.add_event_detect(j, GPIO.FALLING, callback = toggle_cpts3, bouncetime = bounceDelay)
						else:
							GPIO.add_event_detect(j, GPIO.FALLING, callback = toggle_cpts0, bouncetime = bounceDelay)
					elif Status_pins[i] == 'G':
						PinNextSend[i + 1] = time.time() + 30  #30s environ
						k = i % 4
						GPIO.setup(j, GPIO.IN,  pull_up_down = GPIO.PUD_DOWN)
						if k == 1:
							GPIO.add_event_detect(j, GPIO.RISING, callback = toggle_cpts1, bouncetime = bounceDelay)
						elif k == 2:
							GPIO.add_event_detect(j, GPIO.RISING, callback = toggle_cpts2, bouncetime = bounceDelay)
						elif k == 3:
							GPIO.add_event_detect(j, GPIO.RISING, callback = toggle_cpts3, bouncetime = bounceDelay)
						else:
							GPIO.add_event_detect(j, GPIO.RISING, callback = toggle_cpts0, bouncetime = bounceDelay)

			# Renvoie des sondes toutes les 300s par defaut
			if NextRefresh < time.time():
				NextRefresh = time.time() + (60 * ProbeDelay)  #300s environ par defaut
				pinStr = ''
				pinDHT = 0
				for i in range(0, 40):
					j = i + 1
					if pinDHT:
						time.sleep(2)	# si une sonde vient d'etre lue on attends un peu pour la suivante.
						pinDHT = 0
					if Status_pins[i] == 'd' or Status_pins[i] == 'f':
						if Status_pins[i] == 'd': #DHT 11
							humidity, temperature = Adafruit_DHT.read(Adafruit_DHT.DHT11, pin2gpio[i])
						elif Status_pins[i] == 'f': #DHT 22
							humidity, temperature = Adafruit_DHT.read(Adafruit_DHT.DHT22, pin2gpio[i])
						if temperature is not None:
							temperature = round(float(temperature), 2)
							pinStr += '&' + str(j) + '=' + str(temperature * 100)
							pinDHT = 1
						if humidity is not None:
							humidity = round(float(humidity), 2)
							pinStr += '&' + str(1000 + j) + '=' + str(humidity * 100)
							pinDHT = 1
					elif Status_pins[i] == 'b': # ds18b20
						sensors[i] = DS.scan(pin2gpio[i])
						DS.pinsStartConversion([pin2gpio[i]])
						time.sleep(1)
						try:
							k = sensors[i][0]
							pinStr += '&' + str(j) + '=' + str(DS.read(False, pin2gpio[i], k) * 100)
							pinStr2 = ''
							for k in sensors[i]:
								pinStr2 += '"' + str(k) + '":"' + str(DS.read(False, pin2gpio[i], k) * 100) + '",'
							if pinStr2 != '':
								pinStr += '&DS18list_' + str(j) + '={' + pinStr2[:-1] + '}'
							pinDHT = 1
						except Exception as e:
							log('Error' , 'Probleme lecture sonde ds18b20 : ' + str(e))
							pass
					elif Status_pins[i] == 'r': # BMP085/180
						if bmp180 is not None:
							temperature = bmp180.read_temperature()
							pressure = bmp180.read_pressure()
							pinStr += '&' + str(j) + '=' + str(temperature)
							pinStr += '&' + str(1000 + j) + '=' + str(pressure)
							pinDHT = 1
					elif Status_pins[i] == 'A': # BME280
						if bme280 is not None:
							pinStr += '&' + str(j) + '=' + str(bme280.temperature)
							pinStr += '&' + str(1000 + j) + '=' + str(bme280.pressure)
							pinStr += '&' + str(2000 + j) + '=' + str(bme280.humidity)
							pinDHT = 1
					elif Status_pins[i] == 'D': # BME280
						if bme280b is not None:
							pinStr += '&' + str(j) + '=' + str(bme280b.temperature)
							pinStr += '&' + str(1000 + j) + '=' + str(bme280b.pressure)
							pinStr += '&' + str(2000 + j) + '=' + str(bme280b.humidity)
							pinDHT = 1
					elif Status_pins[i] == 'B': # BME680
						if bme680 is not None:
							pinStr += '&' + str(j) + '=' + str(bme680.temperature)
							pinStr += '&' + str(1000 + j) + '=' + str(bme680.pressure)
							pinStr += '&' + str(2000 + j) + '=' + str(bme680.humidity)
							pinStr += '&' + str(3000 + j) + '=' + str(bme680.gas)
							pinDHT = 1
					elif Status_pins[i] == 'E': # BME680
						if bme680b is not None:
							pinStr += '&' + str(j) + '=' + str(bme680b.temperature)
							pinStr += '&' + str(1000 + j) + '=' + str(bme680b.pressure)
							pinStr += '&' + str(2000 + j) + '=' + str(bme680b.humidity)
							pinStr += '&' + str(3000 + j) + '=' + str(bme680b.gas)
							pinDHT = 1
					elif Status_pins[i] == 'C': # BMP280
						if bmp280 is not None:
							pinStr += '&' + str(j) + '=' + str(bmp280.temperature)
							pinStr += '&' + str(1000 + j) + '=' + str(bmp280.pressure)
							pinDHT = 1
					elif Status_pins[i] == 'F': # BMP280
						if bmp280b is not None:
							pinStr += '&' + str(j) + '=' + str(bmp280b.temperature)
							pinStr += '&' + str(1000 + j) + '=' + str(bmp280b.pressure)
							pinDHT = 1
				if pinStr != '':
					SimpleSend(pinStr)

			#on reclame la valeur des compteurs
			if sendCPT == 0 and timeCPT < time.time():
				if JeedomIP != '' and eqLogic != '':
					sendCPT = 1
					if sendPINMODE == 0:
						pinStr = '&PINMODE=1'
						sendPINMODE = 1
					else:
						pinStr = ''
					for i in range(1, 41):
						if Status_pins[i - 1] == 'c' or Status_pins[i - 1] == 'G':
							pinStr += '&CPT_' + str(i) + '=' + str(i)
					if pinStr != '':
						SimpleSend(pinStr)
			time.sleep(0.1)
		s.close()
		try:
			GPIO.cleanup()
		except:
			pass
		sys.exit()

def SimpleSend(rep):
	global eqLogic,JeedomIP,JeedomPort,JeedomCPL
	if JeedomIP!='' and eqLogic!='':
		url = str(JeedomCPL) + "/plugins/jeedouino/core/php/Callback.php?BoardEQ=" + str(eqLogic) + str(rep)
		try:
			conn = httplib.HTTPConnection(JeedomIP, JeedomPort)
			conn.request("GET", url)
			conn.close()
		except:
			conn = httplib.HTTPConnection('127.0.0.1', 80)
			conn.request("GET", url)
			conn.close()
		log("GET", url )
	else:
		log('Error', "JeedomIP et/ou eqLogic non fourni(s)")

def GetDistance(u, w):
	u = pin2gpio[u - 1]
	v = pin2gpio[w - 1]
	GPIO.output(u, True)
	time.sleep(0.00001)
	GPIO.output(u, False)

	start = time.time()
	debut = start
	duree = 0
	while GPIO.input(v) == 0 and duree < 0.02:
		start = time.time()
		duree = start-debut

	stop = time.time()
	debut = stop
	duree = 0
	while GPIO.input(v) == 1 and duree < 0.02:
		stop = time.time()
		duree = stop - debut

	duree = stop - start
	distance = round(duree * 34000 / 2) 		#NOTE : 340m/s, fluctue en fonction de la temperature
	return  '&' + str(w) + '=' + str(distance)

# Debut
if __name__ == "__main__":
	# get the arguments
	if len(sys.argv) > 7:
		if sys.argv[7] != '':
			logFile = sys.argv[7]
	if len(sys.argv) > 6:
		ProbeDelay = int(sys.argv[6])
		if ProbeDelay < 1 or ProbeDelay > 1000:
			ProbeDelay = 5
	if len(sys.argv) > 5:
		JeedomCPL = sys.argv[5]
		if JeedomCPL == '.':
			JeedomCPL = ''
	if len(sys.argv) > 4:
		JeedomPort = int(sys.argv[4])
	if len(sys.argv) > 3:
		JeedomIP = sys.argv[3]
	if len(sys.argv) > 2:
		eqLogic = int(sys.argv[2])
	if len(sys.argv) > 1:
		port = int(sys.argv[1])

	log('info', "---------")
	log('info', "Jeedouino - Demarrage du daemon piGPIO (eqID: " + str(eqLogic) + "). ")
	log('info', "---------")
	# On va demander la valeur des compteurs avec un peu de retard expres
	timeCPT = time.time() + 4
	NextRefresh = time.time() + 7
	sendCPT = 0

	if (nodep):
		log('Error' , 'Dependances introuvables. Veuillez les (re)installer. - ' + str(errdep))

	# set up GPIO
	GPIO.setwarnings(False)
	GPIO.setmode(GPIO.BCM)

	# Toutes les entrees en impulsion
	# Inits
	CounterPinValue = {}
	PinNextSend = {}
	TempoPinHIGH = {}
	TempoPinLOW = {}
	PinValue = {}
	TimeValue = {}
	swtch = {}
	etat_pins = list('........................................')
	exit = 0
	SetAllLOW = 0
	SetAllHIGH = 0
	SetAllSWITCH = 0
	SetAllPulseLOW = 0
	SetAllPulseHIGH = 0
	pinStr = ''
	for i in range(1, 41):
		CounterPinValue[i] = 0
		PinNextSend[i] = 0
		TempoPinHIGH[i] = 0
		TempoPinLOW[i] = 0
		PinValue[i] = 0
		TimeValue[i] = 0
		j = pin2gpio[i - 1]
		try:
			if Status_pins[i - 1] == 'o' or Status_pins[i - 1] == 'y' or Status_pins[i - 1] == 's' or Status_pins[i - 1] == 'l' or Status_pins[i - 1] == 'h' or Status_pins[i - 1] == 'u' or Status_pins[i - 1] == 'v' or Status_pins[i - 1] == 'w':
				GPIO.setup(j, GPIO.OUT)
				GPIO.remove_event_detect(j)
				GPIO.output(j, BootMode)
				swtch[i] = BootMode
				pinStr += '&' + str(i) + '=' + str(BootMode)
			elif Status_pins[i - 1] == 'p':
				GPIO.setup(j, GPIO.IN, pull_up_down = GPIO.PUD_UP)
				GPIO.remove_event_detect(j)
				pinStr +='&IN_' + str(i) + '=' + str(GPIO.input(j))
			elif Status_pins[i - 1] == 'i':
				GPIO.setup(j, GPIO.IN, pull_up_down = GPIO.PUD_DOWN)
				GPIO.remove_event_detect(j)
				pinStr +='&IN_' + str(i) + '=' + str(GPIO.input(j))
			etat_pins[i - 1] = Status_pins[i - 1]
		except:
			etat_pins[i - 1] = '.'
			swtch[i] = 0
	Status_pins = '' . join(etat_pins)

	if pinStr != '':
		SimpleSend(pinStr)

	threadLock = threading.Lock()
	threads = []

	# Create new threads
	thread1 = myThread1(1, "First Network thread")
	thread2 = myThread2(2, "Second Network thread")

	# Settings as daemon
	thread1.daemon = True
	thread2.daemon = True

	# Start new Threads
	thread1.start()
	thread2.start()

	# Add threads to thread list
	threads.append(thread1)
	threads.append(thread2)

	thread_delay = 900
	thread_refresh = time.time() + thread_delay
	thread_tries1 = 0
	thread_tries2 = 0

	log('info', "Jeedouino PiGpio daemon (eqID: " + str(eqLogic) + ") running...")
	try:
		while exit == 0:
			#if gpioSET:
				#ReadPushButton()
			if thread_refresh < time.time():
				thread_refresh = time.time() + thread_delay
				if thread_1 == 0:
					if thread_tries1 < 1:
						thread_tries1 += 1
						log('Warning' , '1st Thread maybe dead or waiting for a too long period, trying a re-start of it.')
						time.sleep(5)
						SimpleSend('&PINGME=1')
						if not thread1.is_alive():
							thread1.start()
					elif thread_tries1 < 2:
						thread_tries1 += 1
						log('Warning' , '1st Thread maybe dead or waiting for a too long period, ask Jeedouino for a ping and wait for one more try.')
						time.sleep(2)
						SimpleSend('&PINGME=1')
					else:
						exit = 1
						log('Error' , '1st Thread dead, shutting down daemon server and ask Jeedouino for a restart.')
						time.sleep(2)
						SimpleSend('&THREADSDEAD=1')
						break
				if thread_2 == 0:
					if thread_tries2 < 1:
						thread_tries2 += 1
						log('Warning' , '2nd Thread maybe dead or waiting for a too long period, trying a re-start of it.')
						time.sleep(5)
						SimpleSend('&PINGME=1')
						if not thread2.is_alive():
							thread2.start()
					elif thread_tries2 < 2:
						thread_tries2 += 1
						log('Warning' , '2nd Thread maybe dead or waiting for a too long period, ask Jeedouino for a ping and wait for one more try.')
						time.sleep(2)
						SimpleSend('&PINGME=1')
					else:
						exit = 1
						log('Error' , '2nd Thread dead, shutting down daemon server and ask Jeedouino for a restart.')
						time.sleep(2)
						SimpleSend('&THREADSDEAD=1')
						break
				thread_1 = 0
				thread_2 = 0
			time.sleep(0.1)
	except KeyboardInterrupt:
		log('debug' , '^C received, shutting down daemon server')
		exit = 1  # permet de sortir du thread aussi
		time.sleep(4)

	try:
		GPIO.cleanup()
	except:
		pass
	s.close()
	sys.exit()

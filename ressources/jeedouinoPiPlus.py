"""
JEEDOUINO IO PiPlus DEMON v0.8 Dec2015-2019
Modif de simplewebcontrol.py pour utilisation avec Jeedom
Original : https://github.com/abelectronicsuk/ABElectronics_Python_Libraries
				http://www.tutorialspoint.com/python/python_multithreading.htm
"""

import socket
import threading
import os, time
import sys
try:
	import http.client as httplib
except:
	import httplib
os.environ['TZ'] = 'Europe/Paris'
time.tzset()

try:
	from IOPi import IOPi
	nodep = 0
except Exception as e:
	errdep = e
	nodep = 1

#reload(sys)
#sys.setdefaultencoding('utf8')

sendPINMODE = 0
port = 8000
portusb = ''
JeedomIP=''
eqLogic=''
boardId=32
JeedomPort=80
JeedomCPL=''

# s-Fallback
BootMode = False
Status_pins = {}
# e-Fallback

# Tests Threads alives
thread_1 = 0
thread_2 = 0

logFile = "JeedouinoPiPlus.log"

def log(level,message):
	fifi=open(logFile, "a+")
	try:
		fifi.write('[%s][Demon PiPlus] %s : %s' % (time.strftime('%Y-%m-%d %H:%M:%S', time.localtime()), str(level), str(message)))
	except:
		print('[%s][Demon PiPlus] %s : %s' % (time.strftime('%Y-%m-%d %H:%M:%S', time.localtime()), str(level), str(message)))
	fifi.write("\r\n")
	fifi.close()

def SimpleParse(m):
	m=m.decode('ascii')
	m=m.replace('/', '')
	u = m.find('?')
	if u>-1:
		u+= 1
		v = m.find(' HTTP',u)
		if v>-1:
			url = m[u:v]
			cmds = url.split("&")
			get = []
			for i in cmds:
				try:
					a,b = i.split("=")
					get.append(a)
					get.append(b)
				except:
					log('erreur','Un element est manquant dans :"' + str(i) + '" .')
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
		global eqLogic,JeedomIP,TempoPinLOW,TempoPinHIGH,exit,Status_pins,swtch,SetAllLOW,SetAllHIGH,CounterPinValue,s,bus,SetAllSWITCH,SetAllPulseLOW,SetAllPulseHIGH,BootMode,thread_1,thread_tries,sendPINMODE
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
					exit=1
					raise e

		s.listen(5)								# Now wait for client connection.
		while exit==0:
			thread_1 = 1
			c, addr = s.accept()			 # Establish connection with client.
			if exit==1:
				break
			m = c.recv(1024)
			thread_tries = 0
			query=SimpleParse(m)
			if query:
				log ('Requete :',str(query))

				reponse='NOK'
				exit=0
				RepStr=''
				PiPlusStr=''

				if 'BootMode' in query:
					q = query.index("BootMode")
					BootMode = int(query[q+1])
					reponse='BMOK'

				if 'ConfigurePins' in query:
					q = query.index("ConfigurePins")
					Status_pins = query[q+1]
					sendPINMODE = 1
					for i in range(0,16):
						j = i + 1
						if Status_pins[i]=='o' or Status_pins[i]=='y' or Status_pins[i]=='s' or Status_pins[i]=='l' or Status_pins[i]=='h' or Status_pins[i]=='u' or Status_pins[i]=='v' or Status_pins[i]=='w':
							bus.set_pin_direction(j, 0)				# output
							bus.invert_pin(j, 0)
							bus.write_pin(j, BootMode)
							swtch[i] = BootMode
							PiPlusStr += '&' + str(i) + '=' + str(BootMode)
						elif Status_pins[i] == 'p':
							bus.set_pin_direction(j, 1)				# input
							bus.set_pin_pullup(j, 1)				# pull_up
							bus.invert_pin(j, 0)
							PiPlusStr += '&IN_' + str(i) + '=' + str(bus.read_pin(j))
						elif Status_pins[i] == 'c':
							bus.set_pin_direction(j, 1)
							bus.set_pin_pullup(j, 1)
							bus.invert_pin(j, 0)
						elif Status_pins[i] == 'i':
							bus.set_pin_direction(j, 1)			# input
							bus.set_pin_pullup(j, 1)			# pull_up
							bus.invert_pin(j, 1)				# inverse pin, sorte de pull_down
							PiPlusStr += '&IN_' + str(i) + '=' + str(bus.read_pin(j))
					reponse = 'COK'
					RepStr = '&REP=' + str(reponse) + PiPlusStr

				if 'eqLogic' in query:
					q = query.index("eqLogic")
					eqLogic = query[q+1]
					reponse = 'EOK'

				if 'JeedomIP' in query:
					q = query.index("JeedomIP")
					JeedomIP = query[q+1]
					reponse = 'IPOK'

				if 'SetPinLOW' in query:
					q = query.index("SetPinLOW")
					u = int(query[q+1])
					reponse = 'SOK'
					SetPin(u, 0, reponse)

				if 'SetPinHIGH' in query:
					q = query.index("SetPinHIGH")
					u = int(query[q+1])
					reponse = 'SOK'
					SetPin(u, 1, reponse)

				if 'SetLOWpulse' in query:
					q = query.index("SetLOWpulse")
					u = int(query[q+1])
					q = query.index("tempo")
					TempoPinLOW[u] = time.time() * 10 + int(query[q+1])
					reponse = 'SOK'
					SetPin(u, 0, reponse)

				if 'SetHIGHpulse' in query:
					q = query.index("SetHIGHpulse")
					u = int(query[q+1])
					q = query.index("tempo")
					TempoPinHIGH[u] = time.time() * 10 + int(query[q+1])
					reponse = 'SOK'
					SetPin(u, 1, reponse)

				if 'SwitchPin' in query:
					q = query.index("SwitchPin")
					u = int(query[q+1])
					v = 1 - swtch[u]
					reponse = 'SOK'
					SetPin(u, v, reponse)

				if 'SetCPT' in query:
					q = query.index("SetCPT")
					u = int(query[q+1])
					q = query.index("ValCPT")
					ValCPT = int(query[q+1])
					CounterPinValue[u] += ValCPT
					reponse = 'SCOK'
					RepStr = '&REP=' + str(reponse)

				if 'RazCPT' in query:
					q = query.index("RazCPT")
					u = int(query[q+1])
					q = query.index("ValCPT")
					ValCPT = int(query[q+1])
					CounterPinValue[u] = ValCPT
					reponse = 'SCOK'
					RepStr = '&REP=' + str(reponse)

				if 'SetAllLOW' in query:
					SetAllLOW=1 # deport dans l'autre thread question de vitesse d'execution
					reponse = 'SOK'

				if 'SetAllHIGH' in query:
					SetAllHIGH=1 # deport dans l'autre thread question de vitesse d'execution
					reponse = 'SOK'

				if 'SetAllSWITCH' in query:
					SetAllSWITCH=1 # deport dans l'autre thread question de vitesse d'execution
					reponse = 'SOK'

				if 'SetAllPulseLOW' in query:
					RepStr = '&REP=SOK'
					q = query.index("tempo")
					for i in range(0,16):
						if Status_pins[i]=='o' or Status_pins[i]=='y' or Status_pins[i]=='s' or Status_pins[i]=='l' or Status_pins[i]=='h' or Status_pins[i]=='u' or Status_pins[i]=='v' or Status_pins[i]=='w':
							swtch[i] = 0
							bus.write_pin(i + 1, 0)
							TempoPinLOW[i] = time.time() * 10 + int(query[q+1])
							RepStr += '&' + str(i) + '=0'
					reponse = 'SOK'

				if 'SetAllPulseHIGH' in query:
					RepStr = '&REP=SOK'
					q = query.index("tempo")
					for i in range(0,16):
						if Status_pins[i]=='o' or Status_pins[i]=='y' or Status_pins[i]=='s' or Status_pins[i]=='l' or Status_pins[i]=='h' or Status_pins[i]=='u' or Status_pins[i]=='v' or Status_pins[i]=='w':
							swtch[i] = 1
							bus.write_pin(i + 1, 1)
							TempoPinHIGH[i] = time.time() * 10 + int(query[q+1])
							RepStr += '&' + str(i) + '=1'
					reponse = 'SOK'

				if 'SetLOWdoublepulse' in query:
					q = query.index("SetLOWdoublepulse")
					u = int(query[q+1])
					q = query.index("tempclick")
					v = float(query[q+1]) / 10
					q = query.index("temppause")
					w = float(query[q+1]) / 10
					bus.write_pin(u + 1, 0)
					time.sleep(v)
					bus.write_pin(u + 1, 1)
					time.sleep(w)
					bus.write_pin(u + 1, 0)
					time.sleep(v)
					reponse = 'SOK'
					SetPin(u, 1, reponse)

				if 'SetHIGHdoublepulse' in query:
					q = query.index("SetHIGHdoublepulse")
					u = int(query[q+1])
					q = query.index("tempclick")
					v = float(query[q+1]) / 10
					q = query.index("temppause")
					w = float(query[q+1]) / 10
					bus.write_pin(u + 1, 1)
					time.sleep(v)
					bus.write_pin(u + 1, 0)
					time.sleep(w)
					bus.write_pin(u + 1, 1)
					time.sleep(v)
					reponse = 'SOK'
					SetPin(u, 0, reponse)

				if 'PING' in query:
					reponse = 'PINGOK'
					RepStr = '&REP=' + str(reponse)

				if 'EXIT' in query:
					exit = 1
					reponse = 'EXITOK'

				if reponse != '':
					c.send(reponse.encode('ascii'))
					log ('>>Reponse a la requete :', str(reponse))
					if RepStr != '':
						SimpleSend(RepStr)

				if exit == 1:
					break

			c.close()
			time.sleep(0.1)
		s.close()
		if exit == 1:
			#listener.deactivate()
			sys.exit()

def SetPin(u, v, m):
	global swtch, bus
	swtch[u] = v
	bus.write_pin(u + 1, v)
	pinStr = '&' + str(u) + '=' + str(v)
	if m != '':
		pinStr += '&REP=' + str(m)
	SimpleSend(pinStr)


class myThread2 (threading.Thread):
	def __init__(self, threadID, name):
		threading.Thread.__init__(self)
		self.threadID = threadID
		self.name = name

	def run(self):
		log('info', "Starting " + self.name)
		global TempoPinLOW,TempoPinHIGH,exit,swtch,SetAllLOW,SetAllHIGH,sendCPT,timeCPT,s,NextRefresh,CounterPinValue,bus,SetAllSWITCH,SetAllPulseLOW,SetAllPulseHIGH,PinNextSend,thread_2,sendPINMODE

		while exit==0:
			thread_2 = 1
			pinStr = ''
			for i in range(0,16):	# Gestion des impulsions
				if TempoPinHIGH[i]!=0 and TempoPinHIGH[i]<int(time.time()*10):
					TempoPinHIGH[i]=0
					swtch[i]=0
					bus.write_pin(i+1, 0)
					pinStr += '&' + str(i) + '=0'
				elif TempoPinLOW[i]!=0 and TempoPinLOW[i]<int(time.time()*10):
					TempoPinLOW[i]=0
					swtch[i]=1
					bus.write_pin(i+1, 1)
					pinStr += '&' + str(i) + '=1'
			if pinStr!='':
				SimpleSend(pinStr + '&Tempo=1')

			if SetAllLOW==1:
				pinStr = '&REP=SOK'
				for i in range(0,16):
					j=i+1
					if Status_pins[i]=='o' or Status_pins[i]=='y' or Status_pins[i]=='s' or Status_pins[i]=='l' or Status_pins[i]=='h' or Status_pins[i]=='u' or Status_pins[i]=='v' or Status_pins[i]=='w':
						swtch[j]=0
						bus.write_pin(j, 0)
						pinStr += '&' + str(i) + '=0'
				SetAllLOW=0
				SimpleSend(pinStr)

			if SetAllHIGH==1:
				pinStr = '&REP=SOK'
				for i in range(0,16):
					j=i+1
					if Status_pins[i]=='o' or Status_pins[i]=='y' or Status_pins[i]=='s' or Status_pins[i]=='l' or Status_pins[i]=='h' or Status_pins[i]=='u' or Status_pins[i]=='v' or Status_pins[i]=='w':
						swtch[j]=1
						bus.write_pin(j, 1)
						pinStr += '&' + str(i) + '=1'
				SetAllHIGH=0
				SimpleSend(pinStr)

			if SetAllSWITCH==1:
				pinStr = '&REP=SOK'
				for i in range(0,16):
					j=i+1
					if Status_pins[i]=='o' or Status_pins[i]=='y' or Status_pins[i]=='s' or Status_pins[i]=='l' or Status_pins[i]=='h' or Status_pins[i]=='u' or Status_pins[i]=='v' or Status_pins[i]=='w':
						if swtch[j]==0:
							swtch[j]=1
							bus.write_pin(j, 1)
							pinStr += '&' + str(j) + '=1'
						else:
							swtch[j]=0
							bus.write_pin(j, 0)
							pinStr += '&' + str(j) + '=0'
				SetAllSWITCH=0
				SimpleSend(pinStr)

			# On envoi le nombre d'impulsions connu si il n'y en a pas eu dans les 10s depuis le dernier envoi
			pinStr=''
			for i in range(0,16):
				if Status_pins[i]=='c' and PinNextSend[i]<time.time() and PinNextSend[i]!=0:
					pinStr +='&' + str(i) + '=' + str(CounterPinValue[i])
					PinNextSend[i]=0
			if pinStr!='':
				SimpleSend(pinStr)

			#on reclame la valeur des compteurs
			if sendCPT==0 and timeCPT<time.time():
				if JeedomIP != '' and eqLogic != '':
					sendCPT=1
					if sendPINMODE == 0:
						pinStr = '&PINMODE=1'
						sendPINMODE = 1
					else:
						pinStr = ''
					for i in range(0,16):
						if Status_pins[i] == 'c':
							pinStr +='&CPT_' + str(i) + '=' + str(i)
					if pinStr != '':
						SimpleSend(pinStr)
			time.sleep(0.1)
		s.close()
		sys.exit()

def SimpleSend(rep):
	global eqLogic,JeedomIP,JeedomPort,JeedomCPL
	if JeedomIP!='' and eqLogic!='':
		url = str(JeedomCPL)+"/plugins/jeedouino/core/php/Callback.php?BoardEQ=" + str(eqLogic) + str(rep)
		conn = httplib.HTTPConnection(JeedomIP,JeedomPort)
		conn.request("GET", url)
		#resp = conn.getresponse()
		conn.close()
		log("GET", str(JeedomIP) + ':' + str(JeedomPort) + url )
	else:
		log('Error', "JeedomIP et/ou eqLogic non fourni(s)")

# Debut
if __name__ == "__main__":
	# get the arguments
	if len(sys.argv) > 7:
		if sys.argv[7] != '':
			logFile = sys.argv[7]
	if len(sys.argv) > 6:
		JeedomCPL = sys.argv[6]
		if JeedomCPL == '.':
			JeedomCPL = ''
	if len(sys.argv) > 5:
		JeedomPort = int(sys.argv[5])
	if len(sys.argv) > 4:
		boardId = int(sys.argv[4])
	if len(sys.argv) > 3:
		JeedomIP = sys.argv[3]
	if len(sys.argv) > 2:
		eqLogic = int(sys.argv[2])
	if len(sys.argv) > 1:
		port = int(sys.argv[1])

	# On va demander la valeur des compteurs avec un peu de retard expres
	timeCPT=time.time() +  4
	NextRefresh=time.time() + 40
	sendCPT=0

	if (nodep):
		SimpleSend('&NODEP=smbus')
		log('Error' , 'Dependances SMBUS introuvables. Veuillez les (re)installer. - ' + str(errdep))
		sys.exit('Dependances SMBUS introuvables. - ' + str(errdep))
	try:
		bus = IOPi(boardId)
	except Exception as e:
		SimpleSend('&NODEP=i2cBusNotOpen')
		log('Error' , 'I2C introuvable. Veuillez l activer (sudo raspi-config) ou verifier si la carte i2c est bien connectee/bon port. - ' + str(e))
		sys.exit('i2c Bus Not Open ! try (sudo raspi-config) or verify i2c port/wiring. - ' + str(e))

	# Toutes les entrees en impulsion
	# Init du Compteur  d'Impulsion
	CounterPinValue={}
	PinNextSend={}
	TempoPinHIGH={}
	TempoPinLOW={}
	Status_INPUTS={}
	swtch={}
	exit=0
	SetAllLOW=0
	SetAllHIGH=0
	SetAllSWITCH=0
	SetAllPulseLOW=0
	SetAllPulseHIGH=0
	pinStr = ''
	for i in range(0,16):
		CounterPinValue[i] = 0
		PinNextSend[i] = 0
		TempoPinHIGH[i] = 0
		TempoPinLOW[i] = 0
		j = i + 1
		try:
			if Status_pins[i]=='o' or Status_pins[i]=='y' or Status_pins[i]=='s' or Status_pins[i]=='l' or Status_pins[i]=='h' or Status_pins[i]=='u' or Status_pins[i]=='v' or Status_pins[i]=='w':
				bus.set_pin_direction(j, 0)				# output
				bus.invert_pin(j, 0)
				bus.write_pin(j, BootMode)
				swtch[i] = BootMode
				pinStr += '&' + str(i) + '=' + str(BootMode)
			elif Status_pins[i]=='p' or Status_pins[i]=='i':
				bus.set_pin_direction(j, 1)
				input = bus.read_pin(j)
				Status_INPUTS[i] = input
				pinStr += '&IN_' + str(i) + '=' + str(input)
		except:
			Status_pins[i]='.'
			swtch[i] = 0

	if pinStr!='':
		SimpleSend(pinStr)

	threadLock = threading.Lock()
	threads = []

	# Create new threads
	thread1 = myThread1(1, "First Network thread")
	thread2 = myThread2(2, "Second Network thread")

	# Start new Threads
	thread1.start()
	thread2.start()

	# Add threads to thread list
	threads.append(thread1)
	threads.append(thread2)

	thread_delay = 900
	thread_refresh = time.time() + thread_delay
	thread_tries = 0

	log('info', "Jeedouino PiPlus daemon running...")
	try:
		while exit==0:
			if thread_refresh<time.time():
				thread_refresh = time.time() + thread_delay
				if thread_1 == 0:
					if thread_tries < 2:
						thread_tries += 1
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
					exit = 1
					log('Error' , '2nd Thread dead, shutting down daemon server and ask Jeedouino for a restart.')
					time.sleep(2)
					SimpleSend('&THREADSDEAD=1')
					break
				thread_1 = 0
				thread_2 = 0
			# Boucle qui remplace le listener (qui bug avec plusieurs piPlus)
			pinStr = ''
			for i in range(0,16):
				if Status_pins[i]=='p' or Status_pins[i]=='i' or Status_pins[i]=='c':
					input = bus.read_pin(i + 1)
					if Status_INPUTS[i] != input:
						Status_INPUTS[i] = input
						if Status_pins[i] == 'c':
							# On compte le nombre d'impulsions
							CounterPinValue[i] += input
							# on verifie qu'il y ai suffisamment de temps d'ecoule pour ne pas saturer jeedom et le reseau
							if PinNextSend[i] < time.time():
								PinNextSend[i] = time.time() + 10  #10s environ
								pinStr +='&' + str(i) + '=' + str(CounterPinValue[i])
						else:
							pinStr +='&' + str(i) + '=' + str(input)
			if pinStr!='':
				SimpleSend(pinStr + '&Main=1')
			time.sleep(0.2)
	except KeyboardInterrupt:
		log('debug' , '^C received, shutting down daemon server')
		exit=1  # permet de sortir du thread aussi

	s.close()
	#listener.deactivate()
	sys.exit()

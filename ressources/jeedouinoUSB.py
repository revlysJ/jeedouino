"""
JEEDOUINO ARDUINO USB DEMON v0.8 , Dec 2015 - 2020
Modif de simplewebcontrol.py pour utilisation avec Jeedom
Original :	https://github.com/piface/pifacedigitalio/blob/master/examples/simplewebcontrol.py
				http://www.tutorialspoint.com/python/python_multithreading.htm
"""

import socket			   # Import socket module
import threading
import os, time
import sys
import serial
try:
	import http.client as httplib
except:
	import httplib
os.environ['TZ'] = 'Europe/Paris'
time.tzset()

#reload(sys)
#sys.setdefaultencoding('utf8')

port = 8080
baudrate = 115200
portusb = ''
JeedomIP = ''
eqLogic = ''
JeedomPort = 80
JeedomCPL = ''

# Tests Threads alives
thread_1 = 0
thread_2 = 0

logFile = "JeedouinoUSB.log"

def log(level,message):
	fifi=open(logFile, "a+")
	try:
		fifi.write('[%s][Demon USB] %s : %s' % (time.strftime('%Y-%m-%d %H:%M:%S', time.localtime()), str(level), str(message)))
	except:
		print('[%s][Demon USB] %s : %s' % (time.strftime('%Y-%m-%d %H:%M:%S', time.localtime()), str(level), str(message)))
	fifi.write("\r\n")
	fifi.close()

def SimpleParse(m):
	m=m.decode('ascii')
	m = m.replace('/', '')
	u = m.find('?')
	if u > -1:
		u+= 1
		v = m.find(' HTTP', u)
		if v > -1:
			url = m[u:v]
			cmds = url.split("&")
			get = []
			for i in cmds:
				try:
					a, b = i.split("=")
					get.append(a)
					get.append(b)
				except:
					log('erreur', 'Un element est manquant dans :"' + str(i) + '" .')
					get.append('USB')
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
		global exit, Arduino_reponse, USBArduino, port, s, thread_1, thread_tries
		s = socket.socket()		 		# Create a socket object
		s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
		#host = socket.gethostname() 	# Get local machine name
		try:
			s.bind(('', port))					# Bind to the port
		except:
			s.close() 							# rate
			log('erreur', 'Le port est peut-etre utilise. Nouvel essai dans 11s.')
			SimpleSend('&PORTISUSED = ' + str(port))
			time.sleep(11)					# on attend un peu
			s = socket.socket()
			s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
			try:
				s.bind(('', port))					# nouvel essai
			except:
				s.close() 							# rate
				log('erreur', 'Le port est probablement utilise. Nouvel essai en mode auto dans 7s.')
				SimpleSend('&PORTINUSE = ' + str(port))
				time.sleep(7)					# on attend encore un peu
				s = socket.socket()
				s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
				portnew = 0
				try:
					s.bind(('', 0))					# on essai en auto decouverte
					addr, portnew = s.getsockname()
					log('debug', 'Un port libre est disponible : ' + str(portnew))
					SimpleSend('&PORTFOUND = ' + str(portnew))
				except:
					log('erreur', 'Impossible de trouver un port automatiquement. Veuillez en choisir un autre')
					SimpleSend('&NOPORTFOUND = ' + str(port))
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
			thread_tries = 0
			query = SimpleParse(m)
			if query:
				log ('Requete', str(query))
				reponse = ''
				exit = 0
				Arduino_message = ''

				if 'eqLogic' in query:
					q = query.index("eqLogic")
					Arduino_message = 'E' + query[q+1] + 'Q'

				if 'JeedomIP' in query:
					q = query.index("JeedomIP")
					Arduino_message = 'I' + query[q+1] + 'P'

				if 'USB' in query:
					q = query.index("USB")
					Arduino_message = query[q+1]

				if 'PING' in query:
					Arduino_message = 'PING'

				if 'EXIT' in query:
					exit = 1
					reponse = 'EXITOK'
				else:
					if Arduino_message != '':
						reponse = ''
						Arduino_reponse = ''
						Arduino_message +=  '\n'
						USBArduino.write(Arduino_message.encode('ascii'))	   # fin du message a l'arduino
						log ('Arduino_message', Arduino_message)
						timeout = time.time()*10+35
						while Arduino_reponse == '':
							reponse = Arduino_reponse
							if timeout < time.time()*10:
								Arduino_reponse = ''	#timeout
								break
							if exit == 1:
								Arduino_reponse = 'EXITOK'
								break
						reponse = Arduino_reponse		 # Important !

				if reponse != '':
					c.send(reponse.encode('ascii'))
					log ('>> Reponse a la requete', str(reponse))

				if exit == 1:
					break

			c.close()
			time.sleep(0.1)
		s.close()
		if exit == 1:
			sys.exit()


class myThread2 (threading.Thread):
	def __init__(self, threadID, name):
		threading.Thread.__init__(self)
		self.threadID = threadID
		self.name = name

	def run(self):
		log('info', "Starting " + self.name)
		global exit, Arduino_reponse, USBArduino, cmd_list, s, thread_2
		rep = ''
		while exit == 0:
			while rep == '':
				thread_2 = 1
				if exit == 1:
					break
				try:
					rep = USBArduino.readline().decode('ascii')
				except serial.SerialException as e:
					rep = ''
					exit = 1
					break
				except TypeError as e:
					rep = ''
					exit = 1
					break
				time.sleep(0.1)

			log ('Reponse brute recue', str(rep))

			if rep != '':
				for i in cmd_list:
					if rep.find(i) > -1:
						Arduino_reponse = i
						rep = rep.replace(i, '&REP='+str(i))
						break

			rep = rep.replace('\n', '')
			rep = rep.replace('\r', '')

			time.sleep(0.1)
			log ('Reponse filtree', str(rep))

			if rep != '':
				log ('Envois sur entree', str(rep))
				if rep[0] == '&':
					SimpleSend(rep)
				else:
					log ('Erreur', "rep non traitee :" + str(rep))

			rep = ''
			time.sleep(0.1)

		USBArduino.close()
		s.close()
		sys.exit()

def SimpleSend(rep):
	global eqLogic, JeedomIP, JeedomPort, JeedomCPL
	if JeedomIP != '' and eqLogic != '':
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

# Debut
if __name__  ==  "__main__":
	# get the arguments
	if len(sys.argv) > 9:
		if sys.argv[9] != '':
			logFile = sys.argv[9]
	if len(sys.argv) > 8:
		ProbeDelay = int(sys.argv[8])
		if ProbeDelay<1 or ProbeDelay>1000:
			ProbeDelay = 5
	if len(sys.argv) > 7:
		baudrate = sys.argv[7]
	if len(sys.argv) > 6:
		JeedomCPL = sys.argv[6]
	if JeedomCPL == '.':
		JeedomCPL = ''
	if len(sys.argv) > 5:
		JeedomPort = int(sys.argv[5])
	if len(sys.argv) > 4:
		JeedomIP = sys.argv[4]
	if len(sys.argv) > 3:
		eqLogic = int(sys.argv[3])
	if len(sys.argv) > 2:
		portusb = sys.argv[2]
	if len(sys.argv) > 1:
		port = int(sys.argv[1])

	# set Serial port for Arduino USB if possible
	try:
		USBArduino = serial.Serial(portusb, baudrate, timeout = 1, rtscts = 1)
		time.sleep(0.5)
		USBArduino.flush()
	except Exception as e:
		USBArduino = ''
		SimpleSend('&NODEP = SERIAL')
		log('Error' , 'Dependances Serial introuvables. Veuillez les reinstaller. - ' + str(e))
		sys.exit('Dependances Serial introuvables. - ' + str(e))

	# inits
	exit = 0
	Arduino_message = ''
	timeout = 1
	cmd_list = ['COK', 'PINGOK', 'EOK', 'IPOK', 'SOK', 'SCOK', 'SFOK', 'BMOK', 'NOK']

	threadLock = threading.Lock()
	threads = []

	# Create new threads
	thread1 = myThread1(1, "Network thread")
	thread2 = myThread2(2, "Usb thread")

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
	thread_tries = 0

	log('info', "Jeedouino USB daemon running...")
	try:
		while exit == 0:
			if thread_refresh < time.time():
				thread_refresh = time.time() + thread_delay
				if thread_1  ==  0:
					if thread_tries  <  2:
						thread_tries +=  1
						log('Warning' , '1st Thread maybe dead or waiting for a too long period, ask Jeedouino for a ping and wait for one more try.')
						time.sleep(2)
						SimpleSend('&PINGME=1')
					else:
						exit = 1
						log('Error' , '1st Thread dead, shutting down daemon server and ask Jeedouino for a restart.')
						time.sleep(2)
						SimpleSend('&THREADSDEAD=1')
						break
				if thread_2  ==  0:
					exit = 1
					log('Error' , '2nd Thread dead, shutting down daemon server and ask Jeedouino for a restart.')
					time.sleep(2)
					SimpleSend('&THREADSDEAD=1')
					break
				thread_1 = 0
				thread_2 = 0
			time.sleep(2)
	except KeyboardInterrupt:
		log('debug' , '^C received, shutting down daemon server')
		exit = 1  # permet de sortir du thread aussi
		time.sleep(2)

	USBArduino.close()
	s.close()
	sys.exit()

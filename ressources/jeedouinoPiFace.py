"""
JEEDOUINO PIFACE DEMON v0.6 Dec2015-Novembre 2016
Modif de simplewebcontrol.py pour utilisation avec Jeedom
Original : https://github.com/piface/pifacedigitalio/blob/master/examples/simplewebcontrol.py
				https://piface.github.io/pifacedigitalio/example.html#interrupts
				http://www.tutorialspoint.com/python/python_multithreading.htm
"""

import socket			   # Import socket module
import threading
import time
import sys
import httplib
import pifacedigitalio

port = 8000
portusb = ''
JeedomIP=''
eqLogic=''
boardId=0
JeedomPort=80
JeedomCPL=''

# Tests Threads alives
thread_1 = 0
thread_2 = 0

def log(level,message):
	print('[%s][Demon PiFace] %s : %s' % (time.strftime('%Y-%m-%d %H:%M:%S', time.localtime()), str(level), message.encode('utf8')))
	# print(str(level)+" | " + str(message))

def SimpleParse(m):	
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
		print("Starting " + self.name)
		global eqLogic,JeedomIP,TempoPinLOW,TempoPinHIGH,exit,Status_pins,swtch,GPIO,SetAllLOW,SetAllHIGH,CounterPinValue,s,SetAllSWITCH,SetAllPulseLOW,SetAllPulseHIGH,thread_1
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
				except Exception, e:
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
			query=SimpleParse(m)
			if query:
				log ('Requete :',str(query))
				
				reponse='NOK'
				exit=0
				RepStr=''
				
				if 'ConfigurePins' in query:
					q = query.index("ConfigurePins")
					Status_pins = query[q+1]
					reponse='COK'	
					RepStr='&REP=' + str(reponse)				
			
				if 'eqLogic' in query:
					q = query.index("eqLogic")
					eqLogic = query[q+1]
					reponse='EOK'
					#SimpleSend('&REP=' + str(reponse))
				
				if 'JeedomIP' in query:
					q = query.index("JeedomIP")
					JeedomIP = query[q+1]
					reponse='IPOK'
					#SimpleSend('&REP=' + str(reponse))
					
				if 'SetPinLOW' in query:
					q = query.index("SetPinLOW")
					u = int(query[q+1])-8
					reponse='SOK'	
					SetPin(u,0,reponse)	
					
				if 'SetPinHIGH' in query:
					q = query.index("SetPinHIGH")
					u = int(query[q+1])-8
					reponse='SOK'
					SetPin(u,1,reponse)					

				if 'SetLOWpulse' in query:
					q = query.index("SetLOWpulse")
					u = int(query[q+1])-8
					q = query.index("tempo")		
					TempoPinLOW[u] = time.time()*10+int(query[q+1])					
					reponse='SOK'	
					SetPin(u,0,reponse)					
					
				if 'SetHIGHpulse' in query:
					q = query.index("SetHIGHpulse")
					u = int(query[q+1])-8
					q = query.index("tempo")		
					TempoPinHIGH[u] = time.time()*10+int(query[q+1])		
					reponse='SOK'	
					SetPin(u,1,reponse)
					
				if 'SwitchPin' in query:
					q = query.index("SwitchPin")
					u = int(query[q+1])-8
					if swtch[u]==1:
						v=0
					else:
						v=1
					reponse='SOK'   
					SetPin(u,v,reponse)
					
				if 'SetCPT' in query:
					q = query.index("SetCPT")
					u = int(query[q+1])
					q = query.index("ValCPT")
					ValCPT = int(query[q+1])					
					CounterPinValue[u] += ValCPT
					reponse='SCOK'
					RepStr='&REP=' + str(reponse)
					
				if 'RazCPT' in query:
					q = query.index("RazCPT")
					u = int(query[q+1])
					q = query.index("ValCPT")
					ValCPT = int(query[q+1])					
					CounterPinValue[u] = ValCPT
					reponse='SCOK'
					RepStr='&REP=' + str(reponse)
					
				if 'SetAllLOW' in query:
					SetAllLOW=1 # deport dans l'autre thread question de vitesse d'execution
					reponse='SOK'
				
				if 'SetAllHIGH' in query:
					SetAllHIGH=1 # deport dans l'autre thread question de vitesse d'execution 
					reponse='SOK'					
					
				if 'SetAllSWITCH' in query:
					SetAllSWITCH=1 # deport dans l'autre thread question de vitesse d'execution
					reponse='SOK'

				if 'SetAllPulseLOW' in query:
					RepStr = '&REP=SOK'
					q = query.index("tempo")	
					for i in range(0,8):
						swtch[i]=0
						pifacedigital.output_pins[i].value = 0
						TempoPinLOW[i] = time.time()*10+int(query[q+1])
						RepStr += '&' + str(i) + '=0'
					reponse='SOK'
				 
				if 'SetAllPulseHIGH' in query:
					RepStr = '&REP=SOK'
					q = query.index("tempo")	
					for i in range(0,8):
						swtch[i]=1
						pifacedigital.output_pins[i].value = 1
						TempoPinHIGH[i] = time.time()*10+int(query[q+1])		
						RepStr += '&' + str(i) + '=1'
					reponse='SOK'

				if 'PING' in query:
					reponse='PINGOK'	
					SimpleSend('&REP=' + str(reponse))
					
				if 'EXIT' in query:
					exit=1
					reponse='EXITOK'
				
				if reponse!='':
					c.send(reponse)
					log ('>>Reponse a la requete :',str(reponse))
					if RepStr!='':
						SimpleSend(RepStr)

				if exit==1:
					break
				
			c.close()  
			time.sleep(0.1)
		s.close()  
		if exit==1:
			#listener.deactivate()
			sys.exit
			
def SetPin(u,v,m):				
	global swtch
	swtch[u]=v
	pifacedigital.output_pins[u].value	= v
	pinStr = '&' + str(u+8) + '=' + str(v)
	if m!='':
		pinStr += '&REP=' + str(m)
	SimpleSend(pinStr)
	
def toggle_inputs(event):
	global CounterPinValue,PinNextSend,Status_pins
	
	t=event.timestamp
	u=event.pin_num
	v=event.direction	
	
	pinStr = ''
	if Status_pins[u]=='c':
		# On compte le nombre d'impulsions 
		CounterPinValue[u] += v
		# on verifie qu'il y ai suffisamment de temps d'ecoule pour ne pas saturer jeedom et le reseau
		if PinNextSend[u]<t:
			PinNextSend[u]=t+10  #10s environ	
			pinStr='&' + str(u) + '=' + str(CounterPinValue[u])
	else:
		pinStr='&' + str(u) + '=' + str(v)
		
	if pinStr!='':
		SimpleSend(pinStr + '&Toggle=1')
		
class myThread2 (threading.Thread):
	def __init__(self, threadID, name):
		threading.Thread.__init__(self)
		self.threadID = threadID
		self.name = name

	def run(self):
		print("Starting " + self.name)
		global TempoPinLOW,TempoPinHIGH,exit,swtch,SetAllLOW,SetAllHIGH,sendCPT,timeCPT,s,NextRefresh,CounterPinValue,SetAllSWITCH,SetAllPulseLOW,SetAllPulseHIGH,PinNextSend,thread_2
		
		while exit==0:
			thread_2 = 1
			pinStr = ''
			for i in range(0,8):	# Gestion des impulsions
				if TempoPinHIGH[i]!=0 and TempoPinHIGH[i]<int(time.time()*10):
					TempoPinHIGH[i]=0
					swtch[i]=0
					pifacedigital.output_pins[i].value = 0
					pinStr += '&' + str(i+8) + '=0'
				elif TempoPinLOW[i]!=0 and TempoPinLOW[i]<int(time.time()*10):
					TempoPinLOW[i]=0
					swtch[i]=1
					pifacedigital.output_pins[i].value = 1
					pinStr += '&' + str(i+8) + '=1'
			if pinStr!='':
				SimpleSend(pinStr + '&Tempo=1')

			if SetAllLOW==1:
				pinStr = '&REP=SOK'
				pifacedigital.output_port.all_off()
				for i in range(0,8):
					swtch[i]=0					
					pinStr += '&' + str(i+8) + '=0'
				SetAllLOW=0
				SimpleSend(pinStr)
			 
			if SetAllHIGH==1:
				pinStr = '&REP=SOK'
				pifacedigital.output_port.all_on()
				for i in range(0,8):
					swtch[i]=1
					pinStr += '&' + str(i+8) + '=1'
				SetAllHIGH=0
				SimpleSend(pinStr)

			if SetAllSWITCH==1:
				pinStr = '&REP=SOK'
				for i in range(0,8):
					if swtch[i]==1:
						swtch[i]=0
						pifacedigital.output_pins[i].value = 0
						pinStr += '&' + str(i+8) + '=0'
					else:
						swtch[i]=1
						pifacedigital.output_pins[i].value = 1
						pinStr += '&' + str(i+8) + '=1'
				SetAllSWITCH=0
				SimpleSend(pinStr)

			# On envoi le nombre d'impulsions connu si il n'y en a pas eu dans les 10s depuis le dernier envoi
			pinStr=''
			for i in range(0,8):
				if Status_pins[i]=='c' and PinNextSend[i]<time.time() and PinNextSend[i]!=0:
					pinStr +='&' + str(i) + '=' + str(CounterPinValue[i])
					PinNextSend[i]=0
			if pinStr!='':
				SimpleSend(pinStr)
			
			#on reclame la valeur des compteurs
			if sendCPT==0 and timeCPT<time.time():
				sendCPT=1
				pinStr=''
				for i in range(0,8):
					pinStr +='&CPT_' + str(i) + '=' + str(i)
				SimpleSend(pinStr)
			time.sleep(0.1)
		s.close() 
		#listener.deactivate()
		sys.exit

def SimpleSend(rep):			
	global eqLogic,JeedomIP,JeedomPort,JeedomCPL
	if JeedomIP!='' and eqLogic!='':		
		url = str(JeedomCPL)+"/plugins/jeedouino/core/php/Callback.php?BoardEQ="+str(eqLogic)+str(rep)
		conn = httplib.HTTPConnection(JeedomIP,JeedomPort)
		conn.request("GET", url )
		#resp = conn.getresponse()
		conn.close()	
		log("GET", url )		
	else:
		log ('Probleme',"JeedomIP et/ou eqLogic non fourni(s)")  		
		
# Debut
if __name__ == "__main__":
	# get the arguments
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
	timeCPT=time.time()+11
	NextRefresh=time.time()+40 
	sendCPT=0
	
	# set up PiFace Digital
	pifacedigital = pifacedigitalio.PiFaceDigital(int(boardId))
	#listener = pifacedigitalio.InputEventListener(chip=pifacedigital)
	
	# Toutes les entrees en impulsion
	# Init du Compteur  d'Impulsion
	CounterPinValue={}
	PinNextSend={}
	TempoPinHIGH={}
	TempoPinLOW={}
	Status_pins={}
	Status_INPUTS={}
	swtch={}
	exit=0
	SetAllLOW=0
	SetAllHIGH=0
	SetAllSWITCH=0
	SetAllPulseLOW=0
	SetAllPulseHIGH=0	
	pinStr = ''
	for i in range(0,8):
		#listener.register(i, pifacedigitalio.IODIR_BOTH, toggle_inputs,settle_time=0.3)
		input=pifacedigital.input_pins[i].value
		Status_INPUTS[i]=input
		pinStr += '&IN_' + str(i) + '=' + str(input)	
		CounterPinValue[i] = 0
		PinNextSend[i] = 0
		TempoPinHIGH[i] = 0
		TempoPinLOW[i] = 0
		swtch[i] = 0
		Status_pins[i]='.'
	#listener.activate()
	
	# Envoi de l'etats des inputs au boot
	if pinStr!='':
		SimpleSend(pinStr)
		
	threadLock = threading.Lock()
	threads = []

	# Create new threads
	thread1 = myThread1(1, "Net")
	thread2 = myThread2(2, "Tmp")

	# Start new Threads
	thread1.start()
	thread2.start()

	# Add threads to thread list
	threads.append(thread1)
	threads.append(thread2)
	
	thread_delay = 900
	thread_refresh = time.time() + thread_delay
	thread_tries = 0

	print("Jeedouino PiFace daemon waiting for inputs...")
	try:
		while exit==0:
			if thread_refresh<time.time():
				thread_refresh = time.time() + thread_delay
				if thread_1 == 0 or thread_2 == 0:
					if thread_tries < 2:
						thread_tries += 1
						log('erreur' , 'Threads maybe dead, wait for one more try.')
					else:
						exit = 1
						log('erreur' , 'Threads dead, shutting down daemon server')
						time.sleep(2)
						SimpleSend('&THREADSDEAD=1')
						break
				thread_1 = 0
				thread_2 = 0
			# Boucle qui remplace le listener (qui bug avec plusieurs piFace)
			pinStr = ''
			for i in range(0,8):
				input=pifacedigital.input_pins[i].value
				if Status_INPUTS[i]!=input:
					Status_INPUTS[i]=input
					if Status_pins[i]=='c':
						# On compte le nombre d'impulsions 
						CounterPinValue[i] += input
						# on verifie qu'il y ai suffisamment de temps d'ecoule pour ne pas saturer jeedom et le reseau
						if PinNextSend[i]<time.time():
							PinNextSend[i]=time.time()+10  #10s environ	
							pinStr +='&' + str(i) + '=' + str(CounterPinValue[i])
					else:
						pinStr +='&' + str(i) + '=' + str(input)					
			if pinStr!='':
				SimpleSend(pinStr + '&Main=1')		
			time.sleep(0.2)
	except KeyboardInterrupt:
		print('^C received, shutting down server')
		exit=1  # permet de sortir du thread aussi
		
	s.close()
	#listener.deactivate()
	sys.exit
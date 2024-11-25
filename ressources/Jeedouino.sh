##
# Jeedouino Install dependancies
# v0.4 alpha
##

PROGRESS_FILE=/tmp/dependances_jeedouino_en_cours
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Jeedouino - Debut de l'installation des dependances ..."
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
cd /tmp
BASE_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
VENV_DIR=${BASE_DIR}/venv
# mises a jours
#echo "-"
#echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
#echo "Mises a jour du systeme en cours ..."
#echo "/!\ Peut etre long suivant l'anciennete de votre systeme."
#echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
#sudo apt-get -y update
#sudo apt-get -y upgrade
#sudo apt-get -y dist-upgrade

echo 10 > ${PROGRESS_FILE}
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Auto-Fix et Nettoyage preventif"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
sudo apt -y --fix-broken install
sudo dpkg --configure -a --force-confdef
sudo apt -y autoremove

echo 20 > ${PROGRESS_FILE}
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance python3-pip & python3-venv"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
sudo apt-get -y install python3 python3-pip python3-dev python3-venv python3-setuptools
sudo python3 -m venv --upgrade-deps ${VENV_DIR}
sudo ${VENV_DIR}/bin/python3 -m pip install --upgrade setuptools pip wheel


echo 30 > ${PROGRESS_FILE}
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance  python3-serial"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
sudo apt-get -y install python3-serial
sudo ${VENV_DIR}/bin/python3 -m pip install pyserial

echo 40 > ${PROGRESS_FILE}
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance python3-dev-openssl"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
sudo apt-get -y install build-essential python3-dev python3-openssl

echo 50 > ${PROGRESS_FILE}
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance git"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
sudo apt-get -y install git

if [ -f /usr/bin/raspi-config ]; then
	echo "-"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	echo "  Installation des dependances specifiques au Raspberry PI  "
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"

	echo 60 > ${PROGRESS_FILE}
	echo "-"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	echo "Installation dependance Adafruit_Python_DHT"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	rm -Rf /tmp/Adafruit_Python_DHT
	git clone https://github.com/adafruit/Adafruit_Python_DHT.git
	sudo ${VENV_DIR}/bin/python3 -m pip install /tmp/Adafruit_Python_DHT

	echo 65 > ${PROGRESS_FILE}
	echo "-"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	echo "Installation dependance RPi.GPIO"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	sudo ${VENV_DIR}/bin/python3 -m pip install RPi.GPIO

	echo 70 > ${PROGRESS_FILE}
	echo "-"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	echo "Installation dependance AB Electronics Python Libraries"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	rm -Rf /tmp/ABElectronics_Python_Libraries
	git clone https://github.com/abelectronicsuk/ABElectronics_Python_Libraries.git
	sudo ${VENV_DIR}/bin/python3 -m pip install /tmp/ABElectronics_Python_Libraries

	echo 75 > ${PROGRESS_FILE}
	echo "-"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	echo "Installation dependance Adafruit_Python_BMP085/180"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	rm -Rf /tmp/Adafruit_Python_BMP
	git clone https://github.com/adafruit/Adafruit_Python_BMP.git
	sudo ${VENV_DIR}/bin/python3 -m pip install /tmp/Adafruit_Python_BMP

	echo 80 > ${PROGRESS_FILE}
	echo "-"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	echo "Installation dependance Adafruit_circuitpython"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	sudo ${VENV_DIR}/bin/python3 -m pip install adafruit-circuitpython-lis3dh

	echo 85 > ${PROGRESS_FILE}
	echo "-"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	echo "Installation dependance Adafruit_Python_BME280"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	sudo ${VENV_DIR}/bin/python3 -m pip install adafruit-circuitpython-bme280
	sudo ${VENV_DIR}/bin/python3 -m pip install adafruit-circuitpython-bmp280

	echo 90 > ${PROGRESS_FILE}
	echo "-"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	echo "Installation dependance Adafruit_Python_BME680"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	sudo ${VENV_DIR}/bin/python3 -m pip install adafruit-circuitpython-bme680

	echo 95 > ${PROGRESS_FILE}
	echo "-"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	echo "Installation dependance danjperron/BitBangingDS18B20"
	echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
	rm -Rf /tmp/BitBangingDS18B20
	git clone https://github.com/danjperron/BitBangingDS18B20.git
	sudo ${VENV_DIR}/bin/python3 -m pip install /tmp/BitBangingDS18B20/python
fi

echo 100 > ${PROGRESS_FILE}
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Fin de l'installation des dependances ..."
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
sudo chmod -R 755 ${PROGRESS_FILE}
rm ${PROGRESS_FILE}

##
# Jeedouino Install dependancies
# v0.3 alpha
##

touch /tmp/dependances_jeedouino_en_cours
cd /tmp
echo 0 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Jeedouino - Debut de l'installation des dependances ..."
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"

# mises a jours
#echo "-"
#echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
#echo "Mises a jour du systeme en cours ..."
#echo "/!\ Peut etre long suivant l'anciennete de votre systeme."
#echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
#sudo apt-get -y update
#sudo apt-get -y upgrade
#sudo apt-get -y dist-upgrade

echo 20 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance  python-pip"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
sudo apt-get -y install python{,3}-pip python{,3}-setuptools
sudo pip install wheel
sudo pip3 install wheel

echo 30 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance  python-serial"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
sudo apt-get -y install python-serial
sudo pip3 uninstall serial
sudo pip3 install pyserial

echo 40 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance python-dev-openssl"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
sudo apt-get -y install build-essential python{,3}-dev python{,3}-openssl

echo 50 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance git"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
sudo apt-get -y install git

echo 60 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance Adafruit_Python_DHT"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
rm -Rf Adafruit_Python_DHT
git clone https://github.com/adafruit/Adafruit_Python_DHT.git
cd Adafruit_Python_DHT
sudo python setup.py install
sudo python3 setup.py install
cd ..

echo 70 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance AB Electronics Python Libraries"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
rm -Rf ABElectronics_Python_Libraries
git clone https://github.com/abelectronicsuk/ABElectronics_Python_Libraries.git
cd ABElectronics_Python_Libraries
sudo python setup.py install
sudo python3 setup.py install
cd ..

echo 75 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance Adafruit_Python_BMP085/180"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
rm -Rf Adafruit_Python_BMP
git clone https://github.com/adafruit/Adafruit_Python_BMP.git
cd Adafruit_Python_BMP
sudo python setup.py install
sudo python3 setup.py install
cd ..

echo 80 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance Adafruit_circuitpython"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
pip3 install adafruit-circuitpython-lis3dh

echo 85 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance Adafruit_Python_BME280"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
sudo pip3 install adafruit-circuitpython-bme280
sudo pip3 install adafruit-circuitpython-bmp280

echo 90 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance Adafruit_Python_BME680"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
sudo pip3 install adafruit-circuitpython-bme680

echo 95 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Installation dependance danjperron/BitBangingDS18B20"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
rm -Rf BitBangingDS18B20
git clone https://github.com/danjperron/BitBangingDS18B20.git
cd BitBangingDS18B20/python
sudo python setup.py install
sudo python3 setup.py install
cd ../..

echo 100 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Fin de l'installation des dependances ..."
echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
rm /tmp/dependances_jeedouino_en_cours

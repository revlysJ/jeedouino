##
# Jeedouino Install dependacies
# v0.2 alpha
##

touch /tmp/dependances_jeedouino_en_cours
echo 0 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^"
echo "Debut de l'installation des dependances ..."
echo "^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^"

# mises a jours
echo "-"
echo "Mises a jour du systeme en cours ..."
echo "/!\ Peut etre long suivant l'anciennete de votre systeme."
echo "^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^"
sudo apt-get -y update
sudo apt-get -y upgrade
sudo apt-get -y dist-upgrade

echo 20 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "Installation dependance  python-pip"
echo "^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^"
sudo apt-get -y install python-pip

echo 30 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "Installation dependance  python-serial"
echo "^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^"
sudo apt-get -y install python-serial

echo 40 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "Installation dependance python-dev-openssl"
echo "^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^"
sudo apt-get -y install build-essential python-dev python-openssl

echo 60 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "Installation dependance git"
echo "^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^"
sudo apt-get -y install git

echo 80 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "Installation dependance Adafruit_Python_DHT"
echo "^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^"
git clone https://github.com/adafruit/Adafruit_Python_DHT.git
cd Adafruit_Python_DHT
sudo python setup.py install
cd ..

echo 90 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "Corrections droits"
echo "^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^"
sudo chmod 755 $(pwd)/../../plugins/jeedouino/ressources/DS18B20Scan
sudo adduser jeedom gpio

echo 100 > /tmp/dependances_jeedouino_en_cours
echo "-"
echo "Fin de l'installation des dependances ..."
echo "^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^"
rm /tmp/dependances_jeedouino_en_cours





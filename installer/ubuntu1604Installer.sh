#!/bin/bash

if [ `whoami` == "root" ]
then
	echo This script cannot be run as sudo!
	exit
fi

OS=Linux
grep -qE "(Microsoft|WSL)" /proc/version &> /dev/null && OS=Windows

if [ $OS == "Windows" ]; then
    echo "Running this script in Windows 10 WSL!"
    WORKINGDIR=`pwd`
    if [ "$WORKINGDIR" != "/mnt/c/src" ]; then
        echo "WARNING: It is recommended that you run this script from /mnt/c/src"
    fi

    ISADMIN=0 && echo "hi" > /mnt/c/Windows/amiadmin && ISADMIN=1
    if [ "$ISADMIN" == "0" ]; then
        echo "This script must be run inside an elevated Ubuntu terminal!"
        echo "Re-open this Ubuntu terminal by right-clicking on the icon and 'Run as Administrator'"
        exit
    fi

    rm /mnt/c/Windows/amiadmin

    # We assume that choco is already installed via the windowsSetup.sh script
    cmd.exe /C "choco install -y jre8 selenium selenium-chrome-driver php"

fi

echo "Please enter your sudo password below (necessary for some installation steps)"
sudo echo "Thank you!"

echo "Adding regional Ubuntu mirrors to apt sources.list"
sudo sed -i '1i deb mirror://mirrors.ubuntu.com/mirrors.txt xenial main restricted universe multiverse\n' /etc/apt/sources.list
sudo sed -i '1i deb mirror://mirrors.ubuntu.com/mirrors.txt xenial-updates main restricted universe multiverse\n' /etc/apt/sources.list
sudo sed -i '1i deb mirror://mirrors.ubuntu.com/mirrors.txt xenial-backports main restricted universe multiverse\n' /etc/apt/sources.list

echo Add extra apt repositories
wget -O- http://linux.lsdev.sil.org/downloads/sil-testing.gpg | sudo apt-key add -
sudo add-apt-repository -y 'deb http://linux.lsdev.sil.org/ubuntu xenial main'
sudo add-apt-repository -y 'deb http://linux.lsdev.sil.org/ubuntu xenial-experimental main'
sudo add-apt-repository -y ppa:ansible/ansible

echo Install NodeJS 8.X and latest npm
wget -O- https://deb.nodesource.com/setup_8.x | sudo -E bash -
sudo apt install -y nodejs || exit

echo Install postfix non-interactively
sudo DEBIAN_FRONTEND=noninteractive apt install -y postfix || exit

echo Install and upgrade packages
sudo apt install -y flip chromium-browser git ansible php7.0-cli libapache2-mod-php mongodb-server p7zip-full php7.0-dev php7.0-gd php7.0-intl php7.0-mbstring php-pear php-xdebug postfix unzip lfmerge || exit
sudo apt -y upgrade || exit

if [ ! -d "web-languageforge/deploy" ]
then
	echo Clone web-languageforge repo into the current directory
	git clone --recurse-submodules https://github.com/sillsdev/web-languageforge || exit
fi

cd web-languageforge/deploy

echo "Run xforge web developer ansible scripts"
echo "Please enter your sudo password when prompted (twice)"
ansible-playbook -i hosts playbook_create_config.yml --limit localhost -K || exit
ansible-playbook -i hosts playbook_webdeveloper_bash_windows10.yml --limit localhost -K || exit
cd ..

echo "Please enter your sudo password if necessary"
sudo echo "Thank you!"
sudo adduser $USER fieldworks

if [ $OS == "Windows" ]; then
    HOSTSFILE=/mnt/c/Windows/System32/drivers/etc/hosts
    ALREADYHASHOSTS=`grep "languageforge.local" $HOSTSFILE`
    if [ -f "$HOSTSFILE" -a ! -n "$ALREADYHASHOSTS" ]; then
        echo "Modifying Windows hosts file"
        ADDITIONSFILE=installer/windowsHostFileAdditions.txt
        flip -m $ADDITIONSFILE
        cat $ADDITIONSFILE >> $HOSTSFILE
        flip -u $ADDITIONSFILE
    fi

    BASHRCFILE="/home/$USER/.bashrc"
    ALREADYHASBASHRCMODS=`grep "service apache2 start" $BASHRCFILE`
    if [ -f "$BASHRCFILE" -a ! -n "$ALREADYHASBASHRCMODS" ]; then
        echo "Adding service start lines to $BASHRCFILE"
        cat installer/bashrcFileAdditions.txt >> $BASHRCFILE
    fi

    echo "Note: the Windows Bash window must be open in order for languageforge.local to work"
fi

echo "Refresh xForge dependencies"
./refreshDeps.sh || exit

echo "Factory Reset the database"
cd scripts/tools

sudo php FactoryReset.php run || exit
cd ../..

echo "You should now be able to access Language Forge locally at http://languageforge.local"
echo "username: admin"
echo "password: password"
echo "Installation finished!"

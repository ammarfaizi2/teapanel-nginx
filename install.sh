#!/bin/bash

if readlink /proc/$$/exe | grep -q "dash"; then
	echo "This script needs to be run with bash, not sh"
	exit
fi

if [[ "$EUID" -ne 0 ]]; then
	echo "Sorry, you need to run this script as root"
	exit 1
fi

(git --version || apt-get install git -y ) && \
[ -e /opt ] || mkdir -pv /opt
[ -e /opt/teapanel-nginx ] || git clone https://github.com/ammarfaizi2/teapanel-nginx /opt/teapanel-nginx

cd /opt/teapanel-nginx && \
php install.php

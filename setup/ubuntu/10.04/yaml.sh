#!/bin/bash
echo " "
echo "========================================================================================"
echo "| Installing PHP5 Yaml Mod                                                             |"
echo "========================================================================================"
sudo apt-get install -y libyaml-dev
sudo pecl install yaml-beta
sudo sh -c "echo 'extension=yaml.so' > /etc/php5/conf.d/libyaml.ini"

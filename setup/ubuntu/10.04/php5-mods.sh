#!/bin/bash
echo " "
echo "========================================================================================"
echo "| Installing PHP Modules                                                               |"
echo "========================================================================================"
sudo apt-get install -y php5-mcrypt php5-memcache php5-memcached php5-uuid php5-curl php5-pgsql php-pear php-apc
sudo sh -c "echo 'extension=mcrypt.so' > /etc/php5/cli/conf.d/mcrypt.ini"

#!/bin/bash
echo " "
echo "========================================================================================"
echo "| Installing PHP5 Mongo Mod                                                             |"
echo "========================================================================================"
sudo pecl install mongo
sudo sh -c "echo 'extension=mongo.so' > /etc/php5/conf.d/mongo.ini"

#!/bin/bash
echo " "
echo "========================================================================================"
echo "| Installing PHP5 SQLite3 Mod                                                          |"
echo "========================================================================================"
sudo apt-get install libsqlite3-dev sqlite
cd ~
sudo apt-get source php5
cd php5-5.3.2/ext/sqlite3
sudo mv config0.m4 config.m4
sudo phpize
sudo ./configure
sudo make
sudo make install
sudo sh -c "echo 'extension=sqlite3.so' > /etc/php5/conf.d/sqlite3.ini"

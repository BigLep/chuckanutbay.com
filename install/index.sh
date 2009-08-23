#!/bin/bash

# This script is expected to be executed from loeppky.com:/home/loeppky/www/chuckanutbay

# Download zip file from GitHub
curl -L -O http://github.com/BigLep/chuckanutbay.com/zipball/master
downloadFileZip=master

# Unzip the zip file
unzip $downloadFileZip
rm $downloadFileZip

# Set the directory permissions
downloadDir=`ls | grep 'BigLep-chuckanutbay.com-'`
cd $downloadDir
find . -type d -exec chmod 755 {} \;
find . -type f -name '*.php' -exec chmod 755 {} \;

# Copy the contents of Applications and dynamicPages
rsync -avz Applications dynamicPages ../

# Cleanup
cd ..
rm -rf $downloadDir

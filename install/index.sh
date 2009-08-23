#!/bin/bash

# This script is expected to be executed from loeppky.com:/home/loeppky/www/chuckanutbay
# As dynamicPages/common/databaseConnection.php is not stored on GitHub,
# it's expected that the file already exist or be manually copied.

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

#!/bin/bash

# This script grabs the latest vesion of the dynamic chuckanutbay.com code from GitHub,
# and then installed it in the provded directory.
# As dynamicPages/common/databaseConnection.php is not stored on GitHub,
# it's expected that the file already exist or be manually copied.

scriptDir=`dirname $0`

installDir=$1
echo "Installing latest GitHub to: $installDir"
mkdir $installDir
cd $installDir
echo

# Download zip file from GitHub
echo "Downloading latest zip file from GitHub"
curl -L -O http://github.com/BigLep/chuckanutbay.com/zipball/master
downloadFileZip=master

# Unzip the zip file
unzip $downloadFileZip
rm $downloadFileZip
echo

# Set the directory permissions
echo "Setting directory permissons"
downloadDir=`ls | grep 'BigLep-chuckanutbay.com-'`
cd $downloadDir
find . -type d -exec chmod 755 {} \;
find . -type f -name '*.php' -exec chmod 755 {} \;
echo

# Copy the contents of Applications and dynamicPages
echo "Copying content into to $installDir"
rsync -av \
	--include-from=${scriptDir}/installGitHubToDir-includes.txt \
	. ../
echo

# Cleanup
echo "Cleaning up"
cd ..
rm -rf $downloadDir

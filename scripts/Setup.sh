#!/bin/bash

mampDir="/Applications/MAMP";
tomcatDir="/Applications/Tomcat7";
webserverDir="/Applications/Webserver";
# Use "~" doesn't work correctly, so we use the full path to the user directory.
dropboxDir="/Users/webserver/Dropbox";
apacheDir="$webserverDir/Apache";
backupsDir="$webserverDir/Backups";
imagesDir="$apacheDir/Images";

if [ ! -d $mampDir ]; then
        echo "Could not find MAMP installed at $mapDir";
        exit 1;
fi

if [ ! -d $tomcatDir ]; then
        echo "Could not find Tomcat installed at $tomcatDir";
        exit 1;
fi

# Setup Webserver dir
echo "Setting up $webserverDir";
mkdir $webserverDir;
ln -s $mampDir/htdocs $apacheDir;
ln -s $tomcatDir $webserverDir/Tomcat;

# Setups Application Importer Dropbox dirs
for importerApplicationDir in `ls $apacheDir/Applications/ | grep Importer`; do
	apacheToProcessDir=$apacheDir/Applications/$importerApplicationDir/toProcess;
	dropboxToProcessDir=$dropboxDir/${importerApplicationDir}DropBox;
	echo "Symlinking $dropboxToProcessDir to $apacheToProcessDir";
        ln -s $dropboxToProcessDir $apacheToProcessDir;
done


# Setup Backup dir
echo "Setting up $backupsDir";
ln -s $dropboxDir/Backups $backupsDir;

# Add Database backup to crontab
echo "Adding database backup to crontab.";
webserverCrontab="/tmp/webserver.crontab";
# -e option enables interpretting backslash escapes (per http://www.unix.com/shell-programming-scripting/56666-new-line-echo.html)
echo -e "0\t23\t*\t*\t*\t/usr/bin/open $webserverDir/BackupDatabase.app\n" > $webserverCrontab;
crontab $webserverCrontab;
rm $webserverCrontab;
echo "The current crontab is: "
crontab -l;

# Setup Images dir
echo "Setting up $imagesDir";
ln -s $dropboxDir/Images $imagesDir;


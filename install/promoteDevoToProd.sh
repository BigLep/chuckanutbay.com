#!/bin/bash

# This script will promote the dynamic code from DEVO to PROD.

scriptDir=`dirname $0`

prodDir=$1
devoDir=$prodDir/DEVO

echo "Promoting from $devoDir to $prodDir"
rsync -av \
	--include-from=${scriptDir}/promoteDevoToProd-includes.txt \
	--exclude-from=${scriptDir}/promoteDevoToProd-excludes.txt \
	$devoDir/ $prodDir/
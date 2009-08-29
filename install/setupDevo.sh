#!/bin/bash

prodDir=$1
devoDir=$prodDir/DEVO

mkdir -p \
	$devoDir/Applications/NutritionLabelImporter/toProcess \
	$devoDir/Applications/QuickBooksItemsImporterImporter/toProcess \
	$devoDir/dynamicPages/common
	
rsync -av $prodDir/dynamicPages/common/databaseConnection.php $devoDir/dynamicPages/common/
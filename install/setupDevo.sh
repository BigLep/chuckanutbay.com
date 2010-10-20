#!/bin/bash

prodDir=$1
devoDir=$prodDir/DEVO

echo "Creating directories:"
for dir in "Applications/NutritionLabelImporter/toProcess" "Applications/QuickBooksItemsImporterImporter/toProcess" "dynamicPages/common"; do
	echo "$dir";
	mkdir -p $devoDir/${dir}
done
echo

echo "Creating symlinks:"
for file in Images; do
	echo "$file";
	ln -s ${prodDir}/${file} ${devoDir}/${file} 
done
echo

echo "Copying environment info:"
rsync -av $prodDir/dynamicPages/common/setUpEnvironment.php $devoDir/dynamicPages/common/
echo
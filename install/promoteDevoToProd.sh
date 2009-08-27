#!/bin/bash

# This script will promote the dynamic code from DEVO to PROD.

prodDir=$1
devoDir=$prodDir/DEVO

rsync -avz $devoDir/Applications $devoDir/dynamicPages $prodDir/
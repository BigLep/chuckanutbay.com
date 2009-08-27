#!/bin/bash
# This script will run the provided commands,
# but record all of its output to the provided log directory.

logDir=$1;
scriptToExecute=$2;
shift
shift
scriptToExecuteName=`basename $scriptToExecute`

now=`date "+%Y-%m-%d_%H:%M:%S"`
logFile="$logDir/${scriptToExecuteName}_${now}.log"

$scriptToExecute $@ 2>&1 | tee $logFile
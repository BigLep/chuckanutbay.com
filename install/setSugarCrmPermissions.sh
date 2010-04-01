#!/bin/bash

# This script sets the permissions correctly for Sugar CRM.
# https://www.sugarcrm.com/crm/support/documentation/SugarProfessional/5.5.1/-docs-Application_Guides-Sugar_Professional_Application_Guide_5.5.1GA-Sugar_Install_Upgrade.html#1044910

sugarDir=../sugar

# Set the directory permissions
echo "Setting file/directory permissons of ${sugarDir} to 755"
chmod -v -R 755 ${sugarDir}
echo
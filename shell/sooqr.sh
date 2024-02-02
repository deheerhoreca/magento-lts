#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/sooqr.sh

# Set User Environment
. ${HOME}/.profile

cd ~/httpdocs/deheerhoreca-magento

mphp -c php.cmd.ini shell/sooqr.php --generate 1

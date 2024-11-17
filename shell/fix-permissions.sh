#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/fix-permissions.sh

# Set User Environment
. ${HOME}/.bash_profile

cm

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

cd ./media/catalog/product

find . -type f ! -perm 0644 -print -exec chmod 0644 -- {} +

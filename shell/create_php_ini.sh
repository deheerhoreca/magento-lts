#!/bin/bash

# cm && shell/create_php_ini.sh

# This script takes the system-configured php.ini for the PHP version that is applicable to this app, and adds our modifications into a new php.ini

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

# Set User Environment
. ${HOME}/.profile

cm

TARGET_PHP_INI=./php.cmd.ini
TARGET_USER_INI=_user.ini
PHP_VERSION=$(mphp -r "echo phpversion();")

# Backup php.cmd.ini
PHP_INI_BACKUP="php.cmd.ini.bak-$(date +%F_%H%M%S)"
cp ${TARGET_PHP_INI} ${PHP_INI_BACKUP}
if [ $? -ne 0 ]; then
  echo "Fatal error: Failed to backup ${TARGET_PHP_INI}. Keeping current configuration."
  exit
fi
echo "Existing ${TARGET_PHP_INI} backed up to ${PHP_INI_BACKUP}";

# Backup _user.ini
USER_INI_BACKUP="${TARGET_USER_INI}.bak-$(date +%F_%H%M%S)"
cp ${TARGET_USER_INI} ${USER_INI_BACKUP}
if [ $? -ne 0 ]; then
  echo "Fatal error: Failed to backup ${TARGET_USER_INI}. Keeping current configuration."
  exit
fi
echo "Existing ${TARGET_USER_INI} backed up to ${USER_INI_BACKUP}";

# Create new php.cmd.ini
DEFAULT_PHP_INI=$(mphp -r "echo php_ini_loaded_file();")
SHARED_PHP_INI=$(pwd)/etc/php.cmd-dist.ini
LOCAL_PHP_INI=$(pwd)/etc/php.cmd-local.ini
NOW=`date`

echo > ${TARGET_PHP_INI}

printf "; File: ${TARGET_PHP_INI}\n" >> ${TARGET_PHP_INI}
printf "; This file is created automatically, do not edit\n" >> ${TARGET_PHP_INI}
printf "; PHP version: ${PHP_VERSION}\n" >> ${TARGET_PHP_INI}
printf "; Created: %s\n" "$NOW\n" >> ${TARGET_PHP_INI}

printf "\n; ---------------------- ${DEFAULT_PHP_INI} ----------------------\n\n" >> ${TARGET_PHP_INI}
cat ${DEFAULT_PHP_INI} >> ${TARGET_PHP_INI}

printf "\n; ---------------------- ${SHARED_PHP_INI} ----------------------\n\n" >> ${TARGET_PHP_INI}
cat ${SHARED_PHP_INI} >> ${TARGET_PHP_INI}

printf "\n; ---------------------- ${LOCAL_PHP_INI} ----------------------\n\n" >> ${TARGET_PHP_INI}
cat ${LOCAL_PHP_INI} >> ${TARGET_PHP_INI}

# Sometimes the OS INI has no unix line endings
dos2unix -k ${TARGET_PHP_INI}

# Create new _user.ini
SHARED_USER_INI=$(pwd)/etc/.user-dist.ini
LOCAL_USER_INI=$(pwd)/etc/.user-local.ini
NOW=`date`

echo > ${TARGET_USER_INI}

printf "; File: ${TARGET_USER_INI}\n" >> ${TARGET_USER_INI}
printf "; This should be used as Additional PHP Directives in Plesk\n" >> ${TARGET_USER_INI}
printf "; This file is created automatically, do not edit\n" >> ${TARGET_USER_INI}
printf "; PHP version: ${PHP_VERSION}\n" >> ${TARGET_USER_INI}
printf "; Created: %s\n" "$NOW\n" >> ${TARGET_USER_INI}

printf "\n; ---------------------- ${SHARED_USER_INI} ----------------------\n\n" >> ${TARGET_USER_INI}
cat ${SHARED_USER_INI} >> ${TARGET_USER_INI}

printf "\n; ---------------------- ${LOCAL_USER_INI} ----------------------\n\n" >> ${TARGET_USER_INI}
cat ${LOCAL_USER_INI} >> ${TARGET_USER_INI}

# Sometimes the OS INI has no unix line endings
dos2unix -k ${TARGET_PHP_INI}

echo "Done"

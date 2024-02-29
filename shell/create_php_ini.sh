#!/bin/bash

# cm && shell/create_php_ini.sh

# This script takes the system-configured php.ini for the PHP version that is applicable to this app, and adds our modifications into a new php.ini

# Set User Environment
. ${HOME}/.profile

cm

TARGET_PHP_INI=./php.cmd.ini
TARGET_USER_INI=.user.ini
PHP_VERSION=$(iphp -r "echo phpversion();")

# Backup the current php.ini
PHP_INI_BACKUP="php.cmd.ini.bak-$(date +%F_%H%M%S)"
cp ${TARGET_PHP_INI} ${PHP_INI_BACKUP}
if [ $? -ne 0 ]; then
  echo "Fatal error: Failed to backup php.ini. Keeping current php.ini"
  exit
fi
echo "Old php.ini backed up to ${PHP_INI_BACKUP}";

# Backup the current .user.ini
USER_INI_BACKUP=".user.ini.bak-$(date +%F_%H%M%S)"
cp ${TARGET_USER_INI} ${USER_INI_BACKUP}
if [ $? -ne 0 ]; then
  echo "Fatal error: Failed to backup .user.ini. Keeping current .user.ini"
  exit
fi
echo "Old .user.ini backed up to ${USER_INI_BACKUP}";

# Create new php.ini
DEFAULT_PHP_INI=$(iphp -r "echo php_ini_loaded_file();")
SHARED_PHP_INI=$(pwd)/etc/php.cmd-dist.ini
LOCAL_PHP_INI=$(pwd)/etc/php.cmd-local.ini
NOW=`date`

echo > ${TARGET_PHP_INI}

printf "; This file is created automatically, do not edit\n" >> ${TARGET_PHP_INI}
printf "; PHP version: ${PHP_VERSION}\n" >> ${TARGET_PHP_INI}
printf "; Created: %s\n" "$NOW\n" >> ${TARGET_PHP_INI}

printf "\n# ------------------------------------- ${DEFAULT_PHP_INI}-------------------------------------\n\n" >> ${TARGET_PHP_INI}
cat ${DEFAULT_PHP_INI} >> ${TARGET_PHP_INI}

printf "\n# ------------------------------------- ${SHARED_PHP_INI}-------------------------------------\n\n" >> ${TARGET_PHP_INI}
cat ${SHARED_PHP_INI} >> ${TARGET_PHP_INI}

printf "\n# ------------------------------------- ${LOCAL_PHP_INI}-------------------------------------\n\n" >> ${TARGET_PHP_INI}
cat ${LOCAL_PHP_INI} >> ${TARGET_PHP_INI}

# Create new .user.ini
SHARED_USER_INI=$(pwd)/etc/.user-dist.ini
LOCAL_USER_INI=$(pwd)/etc/.user-local.ini
NOW=`date`

echo > ${TARGET_USER_INI}

printf "; This file is created automatically, do not edit\n" >> ${TARGET_USER_INI}
printf "; PHP version: ${PHP_VERSION}\n" >> ${TARGET_USER_INI}
printf "; Created: %s\n" "$NOW\n" >> ${TARGET_USER_INI}

printf "\n; ------------------------------------- ${SHARED_USER_INI}-------------------------------------\n\n" >> ${TARGET_USER_INI}
cat ${SHARED_USER_INI} >> ${TARGET_USER_INI}

printf "\n; ------------------------------------- ${LOCAL_USER_INI}-------------------------------------\n\n" >> ${TARGET_USER_INI}
cat ${LOCAL_USER_INI} >> ${TARGET_USER_INI}

echo "Done"

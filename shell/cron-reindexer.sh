#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-reindexer.sh

# set -e      # Exit immediately if a command exits with a non-zero status
# set -u      # Treat unset variables as an error when substituting

# Set User Environment
. ${HOME}/.profile

# set -x      # Print commands and their arguments as they are executed

cm

mphp -c php.cmd.ini shell/indexer.php reindexall

# set +x

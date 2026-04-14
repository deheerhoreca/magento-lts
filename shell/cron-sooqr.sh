#!/bin/bash

: '---------------------------------------------------------------------------------------------------------------
${HOME}/workspace/openmage/shell/cron-sooqr.sh                                                # ~5 min
-----------------------------------------------------------------------------------------------------------------'

export PREFER_HOST=prod
export NO_DEV=1

. ${HOME}/.profile || die "Failed to load ~/.profile"
cm || die "Failed to go to the openmage directory"
. ./shell/cron-bootstrap.sh || die "Failed to run ./shell/cron-bootstrap.sh"

# Run the Sooqr XML datafeed generation
#openmage shell/sooqr.php --generate 1

# Convert the generated XML to JSON using dasel, capturing any errors
errors=$(yq -p=xml -o=json ./media/sooqr/sooqr-datafeed-5AjS-1.xml > ./media/sooqr/sooqr-datafeed-5AjS-1.json 2>&1)
if [ -n "$errors" ]; then
  log_line "Errors occurred during XML to JSON conversion: ${errors}" "ERROR"
else
  log_line "XML to JSON conversion completed successfully." "INFO"
fi

# Set permissions on the generated files so they can be accessed by the webserver
chmod +r ./media/sooqr/sooqr-datafeed-5AjS-1.json

. ./shell/cron-wrapup.sh

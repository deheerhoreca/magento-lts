#!/usr/bin/env bash

: "---------------------------------------------------------------------------------------------------------------
${HOME}/workspace/openmage/shell/cron-sooqr.sh                                                # ~5 min
-----------------------------------------------------------------------------------------------------------------"

export PREFER_HOST=prod
export NO_DEV=1

. ${HOME}/.profile || die "Failed to load ~/.profile"
cm || die "Failed to go to the openmage directory"
. ./shell/cron-bootstrap.sh || die "Failed to run ./shell/cron-bootstrap.sh"

# Run the Sooqr XML datafeed generation
errors=$(openmage shell/sooqr.php --generate 1 2>&1)
last=$?
if [ -n "$errors" ] && [ $last -ne 0 ]; then
  log_line "Errors occurred during Sooqr datafeed generation: ${errors}" "ERROR"
else
  log_line "Sooqr datafeed generation completed:${errors}" "INFO"
fi

# Convert the generated XML to JSON using yq, capturing any errors
errors=$(yq -p=xml -o=json ./media/sooqr/sooqr-datafeed-5AjS-1.xml 2>&1 1>./media/sooqr/sooqr-datafeed-5AjS-1.json)
last=$?
if [ -n "$errors" ] && [ $last -ne 0 ]; then
  log_line "Errors occurred during XML to JSON conversion: ${errors}" "ERROR"
else
  log_line "XML to JSON conversion completed:${errors}" "INFO"
fi

# Set permissions on the generated files so they can be accessed by the webserver
chmod +r ./media/sooqr/sooqr-datafeed-5AjS-1.json
last=$?
if [ $last -ne 0 ]; then
  log_line "Failed to set permissions on JSON file, but file was written." "WARNING"
else
  log_line "Permissions set on JSON file successfully." "INFO"
fi

. ./shell/cron-wrapup.sh

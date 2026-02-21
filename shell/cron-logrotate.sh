#!/bin/bash

: '
${HOME}/workspace/openmage/shell/cron-logrotate.sh
'

# set -x      # Print commands and their arguments as they are executed
# set -e      # Exit immediately if a command exits with a non-zero status      <-- Do not enable
set -u      # Treat unset variables as an error when substituting

export NO_DEV=0

. ${HOME}/.profile || die "Failed to load ~/.profile"
cm || die "Failed to go to the openmage directory"
. ./shell/cron-bootstrap.sh || die "Failed to run ./shell/cron-bootstrap.sh"

touch ${HOME}/logs/deheerhoreca-magento/access_log
touch ${HOME}/logs/deheerhoreca-magento/cron_error.log
touch ${HOME}/logs/deheerhoreca-magento/cron_event.log
touch ${HOME}/logs/deheerhoreca-magento/error_log
touch ${HOME}/logs/deheerhoreca-magento/php_error.log

now=$(date)
echo "--------------------------------------------------------------------"
echo "Current date: $now"
echo "--------------------------------------------------------------------"

cm

# ------------------------------------------------------------------------
# Logs indexed in Elasticsearch:
# ------------------------------------------------------------------------

cat > ${HOME}/tmp/logrotate-deheerhoreca-magento.conf << EOF
${HOME}/workspace/openmage/var/log/*log
${HOME}/workspace/openmage/var/log/*jsonl
${HOME}/workspace/openmage/var/log/*ndjson
${HOME}/logs/deheerhoreca-magento/*log
${HOME}/logs/deheerhoreca-magento/*.jsonl
{
  daily
  dateext
  rotate 3
  maxage 3
  missingok
}
EOF

/usr/sbin/logrotate ${HOME}/tmp/logrotate-deheerhoreca-magento.conf -s ${HOME}/tmp/logrotate-deheerhoreca-magento.status
rm ${HOME}/tmp/logrotate-deheerhoreca-magento.conf

# ------------------------------------------------------------------------
# Logs NOT indexed in Elasticsearch:
# ------------------------------------------------------------------------

sleep 1
cat > ${HOME}/tmp/logrotate-deheerhoreca-magento.conf << EOF
${HOME}/workspace/openmage/var/log/*txt
{
  daily
  dateext
  rotate 7
  maxage 7
  missingok
}
EOF

/usr/sbin/logrotate ${HOME}/tmp/logrotate-deheerhoreca-magento.conf -s ${HOME}/tmp/logrotate-deheerhoreca-magento-txt.status
rm ${HOME}/tmp/logrotate-deheerhoreca-magento.conf

# Create some files now, to make sure they get included in the tail command
touch ${HOME}/logs/deheerhoreca-magento/access_log
touch ${HOME}/logs/deheerhoreca-magento/cron_error.log
touch ${HOME}/logs/deheerhoreca-magento/cron_event.log
touch ${HOME}/logs/deheerhoreca-magento/error_log
touch ${HOME}/logs/deheerhoreca-magento/php_error.log

. ./shell/cron-wrapup.sh

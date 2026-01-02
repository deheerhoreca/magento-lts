#!/bin/bash

# ~/workspace/openmage/shell/cron-logrotate.sh

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

. ${HOME}/.bash_profile
cm
. ./shell/cron-bootstrap.sh

touch ~/logs/deheerhoreca-magento/access_log
touch ~/logs/deheerhoreca-magento/cron_error.log
touch ~/logs/deheerhoreca-magento/cron_event.log
touch ~/logs/deheerhoreca-magento/error_log
touch ~/logs/deheerhoreca-magento/php_error.log

now=$(date)
echo "--------------------------------------------------------------------"
echo "Current date: $now"
echo "--------------------------------------------------------------------"

cm

# ------------------------------------------------------------------------
# Logs indexed in Elasticsearch:
# ------------------------------------------------------------------------

cat > ~/tmp/logrotate-deheerhoreca-magento.conf << EOF
~/workspace/openmage/var/log/*.log
~/workspace/openmage/var/log/*.jsonl
~/workspace/openmage/var/log/*.ndjson
~/logs/deheerhoreca-magento/*.log
~/logs/deheerhoreca-magento/*_log
{
  daily
  dateext
  rotate 3
  maxage 3
  missingok
}
EOF

/usr/sbin/logrotate ~/tmp/logrotate-deheerhoreca-magento.conf -s ~/tmp/logrotate-deheerhoreca-magento.status
rm ~/tmp/logrotate-deheerhoreca-magento.conf

# ------------------------------------------------------------------------
# Logs NOT indexed in Elasticsearch:
# ------------------------------------------------------------------------

sleep 1
cat > ~/tmp/logrotate-deheerhoreca-magento.conf << EOF
~/workspace/openmage/var/log/*.txt
{
  daily
  dateext
  rotate 7
  maxage 7
  missingok
}
EOF

/usr/sbin/logrotate ~/tmp/logrotate-deheerhoreca-magento.conf -s ~/tmp/logrotate-deheerhoreca-magento-txt.status
rm ~/tmp/logrotate-deheerhoreca-magento.conf

# Create some files now, to make sure they get included in the tail command
touch ~/logs/deheerhoreca-magento/access_log
touch ~/logs/deheerhoreca-magento/cron_error.log
touch ~/logs/deheerhoreca-magento/cron_event.log
touch ~/logs/deheerhoreca-magento/error_log
touch ~/logs/deheerhoreca-magento/php_error.log

. ./shell/cron-wrapup.sh

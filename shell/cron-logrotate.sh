#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-logrotate.sh

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

touch ~/logs/deheerhoreca-magento/access_log
touch ~/logs/deheerhoreca-magento/error_log
touch ~/logs/deheerhoreca-magento/cron_error.log
touch ~/logs/deheerhoreca-magento/cron_event.log

now=$(date)
echo "--------------------------------------------------------------------"
echo "Current date: $now"
echo "--------------------------------------------------------------------"

# Set User Environment
. ${HOME}/.bash_profile

cm

# ------------------------------------------------------------------------
# Logs indexed in Elasticsearch:
# ------------------------------------------------------------------------

cat > ~/tmp/logrotate-deheerhoreca-magento.conf << EOF
~/httpdocs/deheerhoreca-magento/var/log/*.log
~/httpdocs/deheerhoreca-magento/var/log/*.jsonl
~/httpdocs/deheerhoreca-magento/var/log/*.ndjson
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
~/httpdocs/deheerhoreca-magento/var/log/*.txt
{
  daily
  dateext
  rotate 14
  maxage 14
  missingok
}
EOF

/usr/sbin/logrotate ~/tmp/logrotate-deheerhoreca-magento.conf -s ~/tmp/logrotate-deheerhoreca-magento-txt.status
rm ~/tmp/logrotate-deheerhoreca-magento.conf

# DEHEERHORECA-MAGENTO -- PATHS MUST BE ABSOLUTE
 *    *  *  *  *    ! test -e ~/httpdocs/deheerhoreca-magento/maintenance.flag && cd ~/httpdocs/deheerhoreca-magento && /bin/bash ./scheduler_cron.sh --mode always  1>>${OLOG} 2>>${OERR}
 *    *  *  *  *    ! test -e ~/httpdocs/deheerhoreca-magento/maintenance.flag && cd ~/httpdocs/deheerhoreca-magento && /bin/bash ./scheduler_cron.sh --mode default 1>>${OLOG} 2>>${OERR}
55   21  *  *  *    ~/httpdocs/deheerhoreca-magento/shell/cron-clean-mysql.sh                           1>>${OLOG} 2>>${OERR}
30   02  *  *  *    ~/httpdocs/deheerhoreca-magento/shell/cron-clean-redis.sh                           1>>${OLOG} 2>>${OERR}
00   01  *  *  *    ~/httpdocs/deheerhoreca-magento/shell/cron-download-external-assets.sh              1>>${OLOG} 2>>${OERR}
59   23  *  *  *    ~/httpdocs/deheerhoreca-magento/shell/cron-logrotate.sh                             1>>${OLOG} 2>>${OERR}
29   05  *  *  *    ~/httpdocs/deheerhoreca-magento/shell/cron-rebuild-cache.sh                         1>>${OLOG} 2>>${OERR}
30   05  *  *  *    ~/httpdocs/deheerhoreca-magento/shell/cron-reindexer.sh                             1>>${OLOG} 2>>${OERR}
00   05  *  *  *    ~/httpdocs/deheerhoreca-magento/shell/cron-sooqr.sh                                 1>>${OLOG} 2>>${OERR}
#00   22  *  *  0    cd ~/httpdocs/deheerhoreca-magento; mphp -c php.cmd.ini shell/resave_all_products.php 1>>${OLOG} 2>>${OERR}

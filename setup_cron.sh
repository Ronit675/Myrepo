#!/bin/bash
CRON_CMD="/usr/bin/php /var/www/html/xkcd-Ronit675/src/cron.php"
CRON_JOB="0 0 * * * $CRON_CMD > /dev/null 2>&1"

if ! crontab -l | grep -q "$CRON_CMD"; then
    (crontab -l; echo "$CRON_JOB") | crontab -
    echo "Cron job set up successfully"
else
    echo "Cron job already exists"
fi
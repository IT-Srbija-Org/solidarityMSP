
# Cancelled transactions
0 * * * * php /var/www/solidaritySF/bin/console app:cancelled-transaction >> ./var/log/crontab-cancelled-transaction-`date +\%d-\%m-\%Y`.txt

# Create damaged educator period
0 1 23 * * php /var/www/solidaritySF/bin/console app:create-damaged-educator-period `date -d' -1 month' +\%n` `date -d' -1 month' +\%Y` full >> ./var/log/crontab-cancelled-transaction-`date +\%d-\%m-\%Y`.txt

# Cleaner
0 2 * * * find /var/www/solidaritySF/var/log/crontab* -maxdepth 0 -type f -mtime +30 -exec rm {} \;

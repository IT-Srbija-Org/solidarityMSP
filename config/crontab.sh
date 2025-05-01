
# Create transactions
*/10 7-21 * * * php /var/www/solidaritySF/bin/console app:create-transactions 10000 >> /var/www/solidaritySF/var/log/crontab-create-transactions-`date +\%d-\%m-\%Y`.txt

# Cancelled transactions
0 * * * * php /var/www/solidaritySF/bin/console app:expired-transactions >> /var/www/solidaritySF/var/log/crontab-expired-transaction-`date +\%d-\%m-\%Y`.txt

# Create damaged educator period
0 1 23 * * php /var/www/solidaritySF/bin/console app:create-damaged-educator-period `date -d' -1 month' +\%n` `date -d' -1 month' +\%Y` full >> /var/www/solidaritySF/var/log/crontab-cancelled-transaction-`date +\%d-\%m-\%Y`.txt

# Cleaner
0 2 * * * find /var/www/solidaritySF/var/log/crontab* -maxdepth 0 -type f -mtime +30 -exec rm {} \;

# Create damaged educator period
10 0 * * * php /var/www/solidaritySF/bin/console app:log-numbers >> /var/www/solidaritySF/var/log/crontab-log-number-`date +\%d-\%m-\%Y`.txt

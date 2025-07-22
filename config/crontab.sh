
# Transactions
0 7 * * 1-4 php /var/www/solidarityMSP/bin/console app:transaction:create > /var/www/solidarityMSP/var/log/crontab-transaction-create-`date +\%d-\%m-\%Y`.txt
0 * * * * php /var/www/solidarityMSP/bin/console app:transaction:expired >> /var/www/solidarityMSP/var/log/crontab-transaction-expired-`date +\%d-\%m-\%Y`.txt
10 7 * * * php /var/www/solidarityMSP/bin/console app:transaction:notify-delegates >> /var/www/solidarityMSP/var/log/crontab-transaction-notify-delegates-`date +\%d-\%m-\%Y`.txt
10 7 * * * php /var/www/solidarityMSP/bin/console app:transaction:notify-donors >> /var/www/solidarityMSP/var/log/crontab-transaction-notify-donors-`date +\%d-\%m-\%Y`.txt

# Donors
#0 1 * * * php /var/www/solidarityMSP/bin/console app:inactive-donors >> /var/www/solidarityMSP/var/log/crontab-inactive-donors-`date +\%d-\%m-\%Y`.txt
0 8 * * 1 php /var/www/solidarityMSP/bin/console app:thank-you-donors >> /var/www/solidarityMSP/var/log/crontab-thank-you-donors-`date +\%d-\%m-\%Y`.txt

# Cache
*/10 * * * * php /var/www/solidarityMSP/bin/console app:cache-numbers >> /var/www/solidarityMSP/var/log/crontab-cache-numbers-`date +\%d-\%m-\%Y`.txt

# Cleaner
0 2 * * * find /var/www/solidarityMSP/var/log/crontab* -maxdepth 0 -type f -mtime +30 -exec rm {} \;

# Create damaged educator period
10 0 * * * php /var/www/solidarityMSP/bin/console app:log-numbers >> /var/www/solidarityMSP/var/log/crontab-log-number-`date +\%d-\%m-\%Y`.txt

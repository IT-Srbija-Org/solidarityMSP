
# Update repository
sudo -u www-data git pull;

# Install dependencies
sudo -u www-data php composer.phar install --no-scripts;

# Clear Symfony cache
sudo -u www-data php bin/console cache:clear;

# Update database
sudo -u www-data php bin/console doctrine:schema:update --force;

# Update crontab
crontab -u www-data < config/crontab.sh;

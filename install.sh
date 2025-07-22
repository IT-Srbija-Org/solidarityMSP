#!/bin/bash

# Check if "docker compose" is available
if docker compose &> /dev/null
then
    docker compose down;
    docker compose build;
    docker compose up -d;
else
    echo "Error: docker compose command not found"
    exit 1
fi

# Set git safe.directory
docker exec solidarity-msp-php-container git config --global --add safe.directory /var/www/project

# Install dependencies
docker exec solidarity-msp-php-container php composer.phar install;

# Create database
docker exec solidarity-msp-php-container php bin/console doctrine:database:create --if-not-exists;

# Dump and create tables
docker exec solidarity-msp-php-container php bin/console doctrine:schema:drop --force;
docker exec solidarity-msp-php-container php bin/console doctrine:schema:update --force;

# Load fixtures
docker exec solidarity-msp-php-container php bin/console doctrine:fixtures:load --group=1 --no-interaction;
docker exec solidarity-msp-php-container php bin/console doctrine:fixtures:load --group=2 --append --no-interaction;
docker exec solidarity-msp-php-container php bin/console doctrine:fixtures:load --group=3 --append --no-interaction;
docker exec solidarity-msp-php-container php bin/console doctrine:fixtures:load --group=4 --append --no-interaction;
docker exec solidarity-msp-php-container php bin/console doctrine:fixtures:load --group=5 --append --no-interaction;
docker exec solidarity-msp-php-container php bin/console doctrine:fixtures:load --group=6 --append --no-interaction;

echo "Done!"

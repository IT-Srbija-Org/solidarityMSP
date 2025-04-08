#!/bin/bash

# Check if "docker compose" or "docker-compose" is available
if docker compose &> /dev/null
then
    # Use docker compose
    docker compose up -d;
elif command -v docker-compose &> /dev/null
then
    # Use docker-compose
    docker-compose up -d;
else
    echo "Error: Neither docker compose nor docker-compose command found"
    exit 1
fi

# Install dependencies
docker exec solidarity-php-container php composer.phar install;

# Create database
docker exec solidarity-php-container php bin/console doctrine:database:create --if-not-exists;

# Create tables
docker exec solidarity-php-container php bin/console doctrine:schema:update --force;

# Execute Fixtures
docker exec solidarity-php-container php bin/console doctrine:fixtures:load --group=1 --no-interaction;
docker exec solidarity-php-container php bin/console doctrine:fixtures:load --group=2 --append --no-interaction;
docker exec solidarity-php-container php bin/console doctrine:fixtures:load --group=3 --append --no-interaction;
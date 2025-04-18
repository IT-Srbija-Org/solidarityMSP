#!/bin/bash

# Check if "docker compose" or "docker-compose" is available
if docker compose &> /dev/null
then
    # Use "docker compose"
    docker compose down;
    docker compose build;
    docker compose up -d;
elif command -v docker-compose &> /dev/null
then
    # Use "docker-compose"
    docker-compose down;
    docker-compose build;
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

# Load fixtures in order:
echo "Loading data fixtures..."
echo "1. Base data (cities, schools, users)"
docker exec solidarity-php-container php bin/console doctrine:fixtures:load --group=1 --no-interaction;

echo "2. Delegate requests"
docker exec solidarity-php-container php bin/console doctrine:fixtures:load --group=2 --append --no-interaction;

echo "3. School assignments"
docker exec solidarity-php-container php bin/console doctrine:fixtures:load --group=3 --append --no-interaction;

echo "4. Donors"
docker exec solidarity-php-container php bin/console doctrine:fixtures:load --group=4 --append --no-interaction;

echo "5. Educators"
docker exec solidarity-php-container php bin/console doctrine:fixtures:load --group=5 --append --no-interaction;

echo "6. Transactions"
docker exec solidarity-php-container php bin/console doctrine:fixtures:load --group=6 --append --no-interaction;

echo "✓ Done!"

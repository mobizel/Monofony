#!/bin/bash

cd /var/www/jdj

composer install --optimize-autoloader

php bin/console app:install --no-interaction
php bin/console sylius:fixtures:load --no-interaction
cd web/assets/backend
yarn install
yarn run gulp

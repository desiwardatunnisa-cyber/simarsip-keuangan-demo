#!/usr/bin/env bash

echo "Running composer"
composer install --no-dev --working-dir=/var/www/html --optimize-autoloader

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Linking storage..."
php artisan storage:link || true

echo "Running migrations..."
php artisan migrate --force

echo "Seeding admin user (jika ada)..."
php artisan db:seed --force || true

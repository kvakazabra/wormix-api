#!/bin/sh
set -e

if [ ! -f .env ]; then
  cp .env.example .env
fi

# This is stupid but works as a fix
sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST}|" .env
sed -i "s|^DB_PORT=.*|DB_PORT=${DB_PORT}|" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE}|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME}|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env

if ! grep -q "^APP_KEY=base64" .env; then
  php artisan key:generate
fi

php artisan config:clear
php artisan migrate --force

if [ ! -f storage/.game-initialized ]; then
  php artisan game:init
  touch storage/.game-initialized
fi

exec "$@"
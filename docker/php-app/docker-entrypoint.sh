#!/bin/sh
set -e

composer install

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -d public ]; then
    mkdir public
fi

php-fpm7 -F & nginx -g 'daemon off;';

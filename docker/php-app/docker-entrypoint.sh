#!/bin/sh
set -e

composer install

cp /etc/nginx/fastcgi_params_base /etc/nginx/fastcgi_params
if [ ${OAUTH_CLIENT_ID} ]; then
    echo "fastcgi_param OAUTH_CLIENT_ID \"${OAUTH_CLIENT_ID}\";" >> /etc/nginx/fastcgi_params
fi
if [ ${OAUTH_CLIENT_SECRET} ]; then
    echo "fastcgi_param OAUTH_CLIENT_SECRET \"${OAUTH_CLIENT_SECRET}\";" >> /etc/nginx/fastcgi_params
fi
if [ ${OAUTH_CLIENT_REDIRECT} ]; then
    echo "fastcgi_param OAUTH_CLIENT_REDIRECT \"${OAUTH_CLIENT_REDIRECT}\";" >> /etc/nginx/fastcgi_params
fi
if [ ${ADMINS} ]; then
    echo "fastcgi_param ADMINS \"${ADMINS}\";" >> /etc/nginx/fastcgi_params
fi
if [ ${MANAGERS} ]; then
    echo "fastcgi_param MANAGERS \"${MANAGERS}\";" >> /etc/nginx/fastcgi_params
fi
if [ ${ACCOUNTANTS} ]; then
    echo "fastcgi_param ACCOUNTANTS \"${ACCOUNTANTS}\";" >> /etc/nginx/fastcgi_params
fi
if [ ${DEVELOPERS} ]; then
    echo "fastcgi_param DEVELOPERS \"${DEVELOPERS}\";" >> /etc/nginx/fastcgi_params
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -d public ]; then
    mkdir public
fi

if [ -f ./bin/init.php ]; then
    php ./bin/init.php
fi

if [ -d ./var ]; then
    chmod 0777 ./var -R
fi

php-fpm7 -F & nginx -g 'daemon off;';

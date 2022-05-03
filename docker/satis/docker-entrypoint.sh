#!/bin/sh
set -e

# clear data
if [ ${DEBUG} ]; then
  if [ -d ./public/dist ]; then
    rm -rf ./public/dist
  fi
fi

# build
php /create-satis-config.php > ./satis.json
php ./bin/satis build ./satis.json ./public

#serve
php -S 0.0.0.0:80 -t public/

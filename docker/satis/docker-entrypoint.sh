#!/bin/sh
set -e

# clear data
if [ -d ./public/dist ]; then
  rm -rf ./public/dist
fi

# build
php /create-satis-config.php > ./satis.json
php ./bin/satis build ./satis.json ./public

#serve
php -S 0.0.0.0:80 -t public/

#!/bin/sh
set -e

# build
php /create-satis-config.php > ./satis.json
php ./bin/satis build ./satis.json ./public

#serve
php -S 0.0.0.0:80 -t public/

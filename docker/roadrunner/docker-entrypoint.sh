#!/bin/sh
set -e

# install depends
composer install -q

# init project
if [ -f ./bin/init.php ]; then
    php ./bin/init.php
fi

if [ -d ./var ]; then
    chmod 0777 ./var -R
fi

# create roadrunner config
if [ -f ./rr-worker.php ]; then
  echo "# generated automatically. do not edit" > ./.rr.yaml
  echo "" >> ./.rr.yaml
  echo "http:" >> ./.rr.yaml
  echo "  address: \"0.0.0.0:80\"" >> ./.rr.yaml
  echo "  access_logs: false" >> ./.rr.yaml
  echo "  middleware:" >> ./.rr.yaml
  echo "  - headers" >> ./.rr.yaml

  if [ -d ./public ]; then
    echo "  - static" >> ./.rr.yaml
    echo "  static:" >> ./.rr.yaml
    echo "    dir: ./public" >> ./.rr.yaml
  fi

  echo "pool:" >> ./.rr.yaml
  if [ ${DEBUG} ]; then
    echo "  num_workers: 1" >> ./.rr.yaml
    echo "  max_jobs: 1" >> ./.rr.yaml
  else
    echo "  num_workers: 32" >> ./.rr.yaml
    echo "  max_jobs: 1000" >> ./.rr.yaml
  fi

  echo "logs:" >> ./.rr.yaml
  echo "  level: error" >> ./.rr.yaml
  echo "server:" >> ./.rr.yaml
  echo "  command: \"php rr-worker.php\"" >> ./.rr.yaml
  echo "  env:" >> ./.rr.yaml
  if [ ${DEBUG} ]; then
    echo "    DEBUG: 1" >> ./.rr.yaml
  else
    echo "    DEBUG: 0" >> ./.rr.yaml
  fi
  if [ ${OAUTH_CLIENT_ID} ]; then
    echo "    OAUTH_CLIENT_ID: \"${OAUTH_CLIENT_ID}\"" >> ./.rr.yaml
  fi
  if [ ${OAUTH_CLIENT_SECRET} ]; then
    echo "    OAUTH_CLIENT_SECRET: \"${OAUTH_CLIENT_SECRET}\"" >> ./.rr.yaml
  fi
  if [ ${OAUTH_CLIENT_REDIRECT} ]; then
    echo "    OAUTH_CLIENT_REDIRECT: \"${OAUTH_CLIENT_REDIRECT}\"" >> ./.rr.yaml
  fi
  if [ ${ADMINS} ]; then
    echo "    ADMINS: \"${ADMINS}\"" >> ./.rr.yaml
  fi
  if [ ${MANAGERS} ]; then
    echo "    MANAGERS: \"${MANAGERS}\"" >> ./.rr.yaml
  fi
  if [ ${ACCOUNTANTS} ]; then
    echo "    ACCOUNTANTS: \"${ACCOUNTANTS}\"" >> ./.rr.yaml
  fi
  if [ ${DEVELOPERS} ]; then
    echo "    DEVELOPERS: \"${DEVELOPERS}\"" >> ./.rr.yaml
  fi
  echo "" >> ./.rr.yaml
fi

# create supervisor config
echo "[supervisord]" > /etc/supervisord.conf
echo "nodaemon=true" >> /etc/supervisord.conf
echo "logfile=/var/log/supervisor/supervisord.log" >> /etc/supervisord.conf
echo "pidfile=/var/run/supervisord.pid" >> /etc/supervisord.conf
if [ -f ./rr-worker.php ]; then
  echo "" >> /etc/supervisord.conf
  echo "[program:roadrunner]" >> /etc/supervisord.conf
  echo "command=/usr/local/bin/roadrunner serve -c /var/www/app/.rr.yaml" >> /etc/supervisord.conf
fi

# Add application workers to supervisor
if [ -f ./bin/console ]; then
OLD_IFS=$IFS
IFS=","
list=$WORKERS

num=0
for worker in $list
do
  echo "" >> /etc/supervisord.conf
  echo "[program:worker-$num]" >> /etc/supervisord.conf
  echo "command=php /var/www/app/bin/console $worker" >> /etc/supervisord.conf
  num=$((num+1))
done

IFS=$OLD_IFS
fi

#run, Forest, run!
/usr/bin/supervisord -c /etc/supervisord.conf

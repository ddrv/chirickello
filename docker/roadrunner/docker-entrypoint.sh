#!/bin/sh

set -e

composer install -q

# migrations
if [ -f ./vendor/bin/phinx ]; then
    ./vendor/bin/phinx migrate
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
  echo "  num_workers: 32" >> ./.rr.yaml
  echo "  max_jobs: 1000" >> ./.rr.yaml
  if [ ${DEBUG} ]; then
    echo "reload:" >> ./.rr.yaml
    echo "  interval: 1s" >> ./.rr.yaml
    echo "  patterns: [ \".php\" ]" >> ./.rr.yaml
    echo "  services:" >> ./.rr.yaml
    echo "    http:" >> ./.rr.yaml
    echo "      recursive: true" >> ./.rr.yaml
    echo "      ignore: [ \"vendor\" ]" >> ./.rr.yaml
    echo "      patterns: [ \".php\" ]" >> ./.rr.yaml
    echo "      dirs: [ \".\" ]" >> ./.rr.yaml
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
  if [ ${AUTH_HOST} ]; then
    echo "    AUTH_HOST: \"${AUTH_HOST}\"" >> ./.rr.yaml
  fi
  if [ ${RABBIT_MQ_DSN} ]; then
    echo "    RABBIT_MQ_DSN: \"${RABBIT_MQ_DSN}\"" >> ./.rr.yaml
  fi
  if [ ${KAFKA_DSN} ]; then
    echo "    KAFKA_DSN: \"${KAFKA_DSN}\"" >> ./.rr.yaml
  fi
  if [ ${TIMER_SPEED} ]; then
    echo "    TIMER_SPEED: \"${TIMER_SPEED}\"" >> ./.rr.yaml
  fi
  if [ ${TIMER_BEGIN} ]; then
    echo "    TIMER_BEGIN: \"${TIMER_BEGIN}\"" >> ./.rr.yaml
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
  echo "command=/usr/local/bin/roadrunner serve -c /opt/app/.rr.yaml" >> /etc/supervisord.conf
fi

# Add application workers to supervisor
if [ -f ./workers ]; then
  echo "" >> /etc/supervisord.conf
  cat ./workers | sed -e 's/APP_ROOT/\/opt\/app/g' >> /etc/supervisord.conf
fi

echo "" >> /etc/supervisord.conf

#run, Forest, run!
if [ ${KAFKA_DSN} ]; then
  /wait-for-it.sh ${KAFKA_DSN} -t 0
fi

/usr/bin/supervisord -c /etc/supervisord.conf

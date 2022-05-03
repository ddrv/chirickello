FROM alpine:3.15

VOLUME /opt/app
WORKDIR /opt/app

# install supervisor and php
RUN apk update && \
    apk add --no-cache \
      supervisor \
      php7 \
      php7-mbstring \
      php7-phar \
      php7-json \
      php7-openssl \
      php7-curl \
      php7-sockets \
      php7-pdo \
      php7-pdo_sqlite

# install composer
COPY composer-install.sh /

RUN sh /composer-install.sh

# install roadrunner
RUN mkdir /tmp/rr -p && \
    cd /tmp/rr && \
    composer require -q spiral/roadrunner && \
    ./vendor/bin/rr get-binary -n -q -f 2.* && \
    mv ./rr /usr/local/bin/roadrunner && \
    cd /opt/app && \
    rm -rf /tmp/rr

# create dir for supervisor logs
RUN mkdir /var/log/supervisor -p
EXPOSE 80

COPY docker-entrypoint.sh /

STOPSIGNAL SIGTERM

ENTRYPOINT ["sh", "/docker-entrypoint.sh"]


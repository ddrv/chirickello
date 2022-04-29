FROM alpine:3.15

# install php
RUN apk update && \
    apk add --no-cache \
      zip \
      p7zip \
      unzip \
      php7 \
      php7-mbstring \
      php7-phar \
      php7-json \
      php7-openssl \
      php7-curl \
      php7-tokenizer \
      php7-dom \
      php7-xml \
      php7-xmlwriter \
      php7-zip

# install composer
COPY composer-install.sh /

WORKDIR /opt

RUN sh /composer-install.sh

# init satis

RUN composer create-project composer/satis --stability=dev --keep-vcs

COPY create-satis-config.php /

WORKDIR /opt/satis

VOLUME /opt/satis/packages

EXPOSE 80

COPY docker-entrypoint.sh /

STOPSIGNAL SIGTERM

ENTRYPOINT ["sh", "/docker-entrypoint.sh"]


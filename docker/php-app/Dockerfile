FROM alpine:3.15

# Вообще, так делать нельзя, правильно - nginx отдельно,php-fpm отдельно. Но в данном случае так проще.
# Один образ, который будут использовать большинство сервисов. Работет - и ладно =)

WORKDIR /var/www/app
VOLUME /var/www/app

RUN apk update && \
    apk add php7 \
      php7-mbstring \
      php7-phar \
      php7-json \
      php7-openssl \
      php7-curl \
      php7-fpm \
      nginx && \
    rm /etc/nginx/http.d/default.conf

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('sha384', 'composer-setup.php') === '906a84df04cea2aa72f40b5f787e49f22d4c2f19492ac310e8cba5b96ac8b64115ac402c8cd292b8a03482574915d1a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    php -r "unlink('composer-setup.php');" && \
    chmod +x /usr/local/bin/composer

EXPOSE 80

COPY docker-entrypoint.sh /
COPY nginx_app.conf /etc/nginx/http.d/app.conf

ENTRYPOINT ["sh", "/docker-entrypoint.sh"]

STOPSIGNAL SIGTERM
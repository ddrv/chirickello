FROM alpine:3.15

# install nginx
RUN apk update && \
    apk add --no-cache \
      bash \
      nginx

VOLUME /var/www
WORKDIR /var/www

EXPOSE "8080"

COPY api.conf /etc/nginx/http.d/api.conf
COPY docker-entrypoint.sh /
COPY wait-for-it.sh /
RUN chmod +x /wait-for-it.sh

STOPSIGNAL SIGTERM

ENTRYPOINT ["sh", "/docker-entrypoint.sh"]

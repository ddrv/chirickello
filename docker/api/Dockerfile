FROM alpine:3.15

# install nginx
RUN apk update && \
    apk add --no-cache \
      nginx

VOLUME /var/www
WORKDIR /var/www

EXPOSE "8080"

COPY api.conf /etc/nginx/http.d/api.conf

ENTRYPOINT ["nginx", "-g", "daemon off;"]
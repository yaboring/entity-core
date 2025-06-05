FROM php:8.4-cli

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY --from=ghcr.io/roadrunner-server/roadrunner /usr/bin/rr /usr/local/bin/rr

RUN apt-get update
RUN apt-get install libzip-dev -y
RUN docker-php-ext-install zip sockets mysqli

WORKDIR /usr/local/src

# HTTP server requirements
RUN composer require spiral/roadrunner-http nyholm/psr7
# In memory Key-Value requirements
RUN composer require spiral/roadrunner-kv

COPY .rr.yaml .rr.yaml
COPY ./*.php ./

CMD ["rr", "serve"]
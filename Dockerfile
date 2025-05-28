FROM php:8.4-cli-alpine3.20
RUN apk add --no-cache --virtual .build-deps postgresql-dev
RUN apk add --no-cache postgresql-libs
RUN docker-php-ext-install pdo pdo_pgsql
RUN apk del -f .build-deps && rm -rf /tmp/* /var/cache/apk/*
ENV PATH=./vendor/bin:/composer/vendor/bin:$PATH
ENV COMPOSER_HOME=/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer /usr/bin/composer /usr/bin/composer
WORKDIR /app
ENTRYPOINT ["/usr/bin/composer"]

FROM php:8.4-cli-alpine3.21
RUN apk add --no-cache --virtual .build-deps postgresql-dev
RUN apk add --no-cache postgresql-libs php84-pecl-pcov
RUN docker-php-ext-install pdo pdo_pgsql && \
    mv /usr/lib/php84/modules/pcov.so /usr/local/lib/php/extensions/no-debug-non-zts-20240924/pcov.so && \
    docker-php-ext-enable pcov
RUN apk del -f .build-deps && rm -rf /tmp/* /var/cache/apk/*
ENV PATH=./vendor/bin:/composer/vendor/bin:$PATH
ENV COMPOSER_HOME=/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer /usr/bin/composer /usr/bin/composer
WORKDIR /app
ENTRYPOINT ["/usr/bin/composer"]

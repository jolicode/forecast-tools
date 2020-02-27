FROM debian:stretch-slim

ENV DEBIAN_FRONTEND=noninteractive
ENV APT_KEY_DONT_WARN_ON_DANGEROUS_USAGE=1

RUN apt-get update \
    && apt-get -y dist-upgrade \
    && apt-get install -y --no-install-recommends \
        apt-transport-https \
        apt-utils \
        ca-certificates \
        gnupg \
        netbase \
        wget \
    && wget -q -O - https://packages.sury.org/php/apt.gpg | apt-key add - \
    && echo "deb https://packages.sury.org/php/ stretch main" | tee /etc/apt/sources.list.d/php.list \
    && apt-get update \
    && apt-get -y install --no-install-recommends \
        curl \
        php7.4-apcu \
        php7.4-bcmath \
        php7.4-cli \
        php7.4-common \
        php7.4-curl \
        php7.4-fileinfo \
        php7.4-fpm \
        php7.4-gd \
        php7.4-iconv \
        php7.4-imagick \
        php7.4-intl \
        php7.4-json \
        php7.4-mbstring \
        php7.4-memcached \
        php7.4-mysql \
        php7.4-redis \
        php7.4-sqlite3 \
        php7.4-simplexml \
        php7.4-xml \
        php7.4-xsl \
        php7.4-zip \
        unzip

# Install fake user app
ARG USER_ID

RUN addgroup -gid 1000 app && \
    adduser --system --uid $USER_ID --home /home/app --ingroup app --shell /bin/bash app

# Configuration
COPY *.ini /etc/php/7.4/conf.d/

ARG PROJECT_NAME

FROM ${PROJECT_NAME}_basephp

RUN wget -q -O - https://deb.nodesource.com/gpgkey/nodesource.gpg.key | apt-key add - \
    && wget -q -O - https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - \
    && echo "deb https://deb.nodesource.com/node_13.x buster main" | tee /etc/apt/sources.list.d/nodejs.list \
    && echo "deb-src https://deb.nodesource.com/node_13.x buster main" | tee /etc/apt/sources.list.d/nodejs-src.list \
    && echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list \
    && apt-get update \
    && apt-get -y install --no-install-recommends \
        curl \
        g++ \
        git \
        make \
        mariadb-client \
        nodejs \
        php7.4-phar \
        python-pip \
        python \
        yarn

RUN pip install pycodestyle

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mkdir -p ~/.composer

ENV PATH $PATH:/home/app/.composer/vendor/bin/
ENV TERM xterm

WORKDIR /home/app/app

CMD ["echo"]

ARG PROJECT_NAME

FROM ${PROJECT_NAME}_basephp

RUN apt-get install -y --no-install-recommends \
    nginx \
    php7.4-fpm \
    runit

COPY etc/. /etc/

CMD ["runsvdir", "-P", "/etc/service"]

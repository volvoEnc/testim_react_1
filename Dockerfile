FROM phpdockerio/php80-fpm

WORKDIR /go/src/app

COPY . .

EXPOSE 8080
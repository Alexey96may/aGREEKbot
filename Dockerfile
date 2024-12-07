FROM php:7.4-apache
COPY . /var/www/html/
CMD ["/var/www/html/vendor/bin/phpunit", "tests/"]
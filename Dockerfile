FROM moodlehq/moodle-php-apache:8.1

ARG MOODLE_DBTYPE=mysqli
ARG MOODLE_DBLIB=native
ARG MOODLE_DBPFX=mdl_
ARG MOODLE_DBCOLL=utf8mb4_bin
ARG MOODLE_URL=http://127.0.0.1
ARG MOODLE_DATA=/var/www/moodledata
ARG MOODLE_ADMIN=admin

ARG MOODLE_DBHOST
ARG MOODLE_DBNAME
ARG MOODLE_DBUSER
ARG MOODLE_DBPASS

WORKDIR /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
ENTRYPOINT ["moodle-docker-php-entrypoint"]

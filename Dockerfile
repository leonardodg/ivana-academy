FROM moodlehq/moodle-php-apache:8.1

ARG MOODLE_DBHOST
ARG MOODLE_DBNAME
ARG MOODLE_DBUSER
ARG MOODLE_DBPASS

ENV MOODLE_DBTYPE=mysqli
ENV MOODLE_DBLIB=native
ENV MOODLE_DBPFX=mdl_
ENV MOODLE_DBCOLL=utf8mb4_bin
ENV MOODLE_URL=http://127.0.0.1
ENV MOODLE_DATA=/var/www/moodledata
ENV MOODLE_ADMIN=admin

WORKDIR /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
ENTRYPOINT ["moodle-docker-php-entrypoint"]

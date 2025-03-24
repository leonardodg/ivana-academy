FROM moodlehq/moodle-php-apache:8.1

ARG MOODLE_DBHOST
ARG MOODLE_DBNAME
ARG MOODLE_DBUSER
ARG MOODLE_DBPASS
ARG ENVIRONMENT

ENV MOODLE_DBHOST=$MOODLE_DBHOST
ENV MOODLE_DBNAME=$MOODLE_DBNAME
ENV MOODLE_DBUSER=$MOODLE_DBUSER
ENV MOODLE_DBPASS=$MOODLE_DBPASS
ENV ENVIRONMENT=$ENVIRONMENT

ENV MOODLE_DBTYPE=mysqli
ENV MOODLE_DBLIB=native
ENV MOODLE_DBPFX=mdl_
ENV MOODLE_DBCOLL=utf8mb4_bin
ENV MOODLE_URL=http://127.0.0.1
ENV MOODLE_DATA=/var/www/moodledata
ENV MOODLE_ADMIN=admin

WORKDIR /var/www/html

# SSL Enable + Letsencrypt
COPY .github/workflows/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
RUN if [ $ENVIRONMENT = "develop" ]; then sed -i 's/ivana.academy/dev.ivana.academy/g' /etc/apache2/sites-available/default-ssl.conf; fi
RUN a2enmod ssl && a2ensite default-ssl

# Add Script Entrypoint
COPY my-entrypoint /usr/local/bin/my-entrypoint
RUN chmod +x /usr/local/bin/my-entrypoint

# CRONTAB
RUN apt update && apt install -y cron nano
RUN touch /var/log/moodle-cron.log && chmod 0666 /var/log/moodle-cron.log

# Setting up crontab
COPY my-cron /tmp/crontab
RUN cat /tmp/crontab > /etc/crontabs/root
CMD ["crond", "-f", "-l", "2"]

EXPOSE 80 443
ENTRYPOINT ["my-entrypoint"]

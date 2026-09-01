# Lumen Capital — production image for Railway (or any Docker host)
#
# Apache + mod_php serving the app exactly as it runs locally under
# XAMPP/WAMP/MAMP, with one difference: the port Apache listens on is set
# at container start from Railway's dynamic $PORT variable (docker-entrypoint.sh),
# and the database schema is imported automatically on first boot only.

FROM php:8.4-apache

# --- PHP extensions -----------------------------------------------------
RUN docker-php-ext-install pdo pdo_mysql

# --- Apache modules & config ---------------------------------------------
# mod_rewrite: not strictly required (no rewrite rules today) but harmless
#   and future-proof. headers: used by a couple of security headers below.
# AllowOverride All: the app ships an .htaccess (blocks *.sql, disables
#   directory listing, sets the branded 404 page) — Apache's default is
#   AllowOverride None, which would silently ignore it.
RUN (a2dismod mpm_event mpm_worker 2>/dev/null || true) \
    && a2enmod mpm_prefork rewrite headers \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && { \
        echo '<Directory /var/www/html/>'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
    } > /etc/apache2/conf-available/lumen.conf \
    && a2enconf lumen

# --- MySQL client (used by the entrypoint to seed the schema once) -------
RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# --- App code --------------------------------------------------------------
WORKDIR /var/www/html
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Railway assigns $PORT at runtime; the entrypoint rewrites Apache's
# Listen/VirtualHost directives to match before starting the server.
EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
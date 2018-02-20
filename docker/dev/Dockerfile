FROM webdevops/php-nginx:latest

# Install composer
RUN apt-get update && \
    apt-get install -y --no-install-recommends git zip && \
    curl --silent --show-error https://getcomposer.org/installer | php

# Configure nginx
ENV WEB_DOCUMENT_INDEX=index.html
ENV WEB_DOCUMENT_ROOT=/ssmap/app/build/

# Expose HTTP and HTTPS ports
EXPOSE 80 443

# Add reverse proxy config
COPY docker/dev/files/vhost.conf /opt/docker/etc/nginx/vhost.common.d/01-vhost.conf

# Copy supervisord conf that runs composer
COPY docker/dev/files/composer.conf /opt/docker/etc/supervisor.d/
COPY docker/dev/files/composer.sh /opt/docker/

#!/usr/bin/env sh
until cd /ssmap
do
    echo "Waiting for mount"
    sleep 1
done

export COMPOSER_ALLOW_SUPERUSER=1
composer install
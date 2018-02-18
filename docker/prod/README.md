## Production Docker image

This Dockerfile and run script build the project from a fresh checkout, and run the compiled production files in nginx
with PHP-FPM. Because the built files are copied into the image, it is self contained and suitable for deployment.

Once started using the `run.sh` script, the app is visible at `http://localhost:8000` or `https://localhost:8443`.

The image is based on `webdevops/php-nginx`, and includes a self-signed certificate so you will see a security warning.
For production deployment the certificates can be replaced or traffic routed to the container through a separate reverse
proxy.

To save time, the commands in `run.sh` can be run individually as required.

### Dependencies

The host requires:

* Docker
* PHP & Composer
* NodeJS & NPM

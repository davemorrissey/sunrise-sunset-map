FROM webdevops/php-nginx:latest

EXPOSE 80 443

COPY api /ssmap/api
COPY vendor /ssmap/vendor
COPY app/build /ssmap/app/build

ENV WEB_DOCUMENT_INDEX=index.html
ENV WEB_DOCUMENT_ROOT=/ssmap/app/build/

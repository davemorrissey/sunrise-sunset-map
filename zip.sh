#!/usr/bin/env bash
DIR=sunrise-sunset-map
composer install
cd app
npm run build
cd ..
rm -rf ${DIR}
rm sunrise-sunset-map.zip
mkdir ${DIR}
mkdir ${DIR}/app
cp -r vendor ${DIR}
cp -r api ${DIR}
cp -r app/build ${DIR}/app
zip -r sunrise-sunset-map.zip ${DIR}
rm -rf ${DIR}

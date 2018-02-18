#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Move to project root
cd ${DIR}
cd ../..

# Compile production app
composer install
cd app
npm install
npm run build
cd ..

# Build docker image
docker build -t sunrise-sunset-map -f docker/prod/Dockerfile .

# Run docker image
docker run -p 8000:80 -p 8443:443 --name sunrise-sunset-map --rm sunrise-sunset-map

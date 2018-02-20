#!/usr/bin/env bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

cd ${DIR}
cd ../..
DIR="$( pwd )"

docker build -t ssmap-dev -f docker/dev/Dockerfile .
docker run -p 8000:80 -p 8443:443 --mount type=bind,src=${DIR},dst=/ssmap --name ssmap-dev --rm ssmap-dev

#! /usr/bin/env bash

cp -f ../composer.json ./composer.json

cp -rf ../src ./src

composer install --no-dev

version=$(git describe --tags)

phar-composer build . aliyun-oss-flysystem_$version.phar

rm -rf composer.lock composer.json vendor/ src/
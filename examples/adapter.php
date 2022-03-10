<?php

require __DIR__ . '/../vendor/autoload.php';

use AlphaSnow\Flysystem\Aliyun\AliyunFactory;

Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__))->load();
$config = require __DIR__ . "/config.php";

$adapter = (new AliyunFactory())->createAdapter($config);

$url = $adapter->getUrl("foo/bar.md");
print_r($url);

$tempUrl = $adapter->getTemporaryUrl("foo/bar.md", (new \DateTime())->add(new \DateInterval('P1D')));
print_r($url);

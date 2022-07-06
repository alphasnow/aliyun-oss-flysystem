<?php

require __DIR__ . "/../vendor/autoload.php";

use AlphaSnow\Flysystem\Aliyun\AliyunFactory;

Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__))->load();
$config = require __DIR__ . "/config.php";

$client = (new AliyunFactory())->createClient($config);

$res = $client->getBucketInfo($config["bucket"]);
print_r($res);

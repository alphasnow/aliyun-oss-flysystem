<?php

require __DIR__ . "/../vendor/autoload.php";

use AlphaSnow\Flysystem\Aliyun\AliyunFactory;

Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__))->load();
$config = require __DIR__ . "/config.php";

$adapter = (new AliyunFactory())->createAdapter($config);

$url = $adapter->getUrl("file.md");
print_r($url);

$tempUrl = $adapter->getTemporaryUrl("file.md", (new \DateTime())->add(new \DateInterval("P1D")));
print_r($tempUrl);

$putTempUrl = $adapter->getTemporaryUrl("file.md", (new \DateTime())->add(new \DateInterval("P1D")), ["options" => ["method" => "PUT"]]);
print_r($putTempUrl);

<?php

require __DIR__ . "/../vendor/autoload.php";

use AlphaSnow\Flysystem\Aliyun\AliyunFactory;

Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__))->load();
$config = require __DIR__ . "/config.php";
$flysystem = (new AliyunFactory())->createFilesystem($config);

$flysystem->createDirectory("foo/");
$directoryExists = $flysystem->directoryExists("foo/");
$flysystem->deleteDirectory("foo/");

$flysystem->write("foo/file.md", "contents");
$flysystem->write("foo/bar/file.md", "contents");
$flysystem->deleteDirectory("foo/");

$listContents = $flysystem->listContents("/");
$listPaths = [];
foreach ($listContents as $listContent) {
    $listPaths[] = $listContent->path();
}

$objContents = $flysystem->listContents("/", true);
$objPaths = [];
foreach ($objContents as $objContent) {
    $objPaths[] = $objContent->path();
}

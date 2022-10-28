<?php

require __DIR__ . "/../vendor/autoload.php";

use AlphaSnow\Flysystem\Aliyun\AliyunFactory;
use OSS\OssClient;

Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__))->load();
$config = require __DIR__ . "/config.php";

$flysystem = (new AliyunFactory())->createFilesystem($config);

$flysystem->write("file.md", "contents");
$flysystem->writeStream("foo.md", fopen("file.md", "r"));

$fileExists = $flysystem->fileExists("foo.md");
$flysystem->copy("foo.md", "baz.md");
$flysystem->move("baz.md", "bar.md");
$flysystem->delete("bar.md");
$has = $flysystem->has("bar.md");

$read = $flysystem->read("file.md");
$readStream = $flysystem->readStream("file.md");

$lastModified = $flysystem->lastModified("file.md");
$fileSize = $flysystem->fileSize("file.md");
$mimeType = $flysystem->mimeType("file.md");

$flysystem->setVisibility("file.md", "private");
$visibility = $flysystem->visibility("file.md");

$flysystem->write("file.md", "contents", [
    "options" => [OssClient::OSS_CHECK_MD5 => false]
]);
$flysystem->write("bar.md", "contents", [
    "headers" => ["Content-Disposition" => "attachment; filename=bar.md"]
]);
$flysystem->write("baz.md", "contents", [
    "visibility" => "private"
]);

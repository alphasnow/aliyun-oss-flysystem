<?php

require __DIR__.'/../vendor/autoload.php';

use OSS\OssClient;
use League\Flysystem\Filesystem;
use AlphaSnow\Flysystem\Aliyun\AliyunAdapter;

$config = [
    "access_id" => "LTAI4**************qgcsA",        // Required, AccessKey
    "access_key" => "PkT4F********************Bl9or", // Required, AccessKey Key Secret
    "endpoint" => "oss-cn-shanghai.aliyuncs.com",     // Required, Endpoint
    "bucket" => "my-storage",                         // Required, Bucket
    "prefix" => "",
    "options" => [
        "checkmd5" => false
    ]
];
is_file(__DIR__ . '/config.php') && $config = array_merge($config, require __DIR__ . '/config.php');

$client = new OssClient($config['access_id'], $config['access_key'], $config['endpoint']);
$adapter = new AliyunAdapter($client, $config['bucket'], $config['prefix'], $config['options']);
$flysystem = new Filesystem($adapter, ["disable_asserts" => true,"case_sensitive" => true]);

$flysystem->write('file.md', 'contents');
$flysystem->writeStream('foo.md', fopen('file.md', 'r'));

$fileExists = $flysystem->fileExists('foo.md');
$flysystem->copy('foo.md', 'baz.md');
$flysystem->move('baz.md', 'bar.md');
$flysystem->delete('bar.md');
$has = $flysystem->has('bar.md');

$read = $flysystem->read('file.md');
$readStream = $flysystem->readStream('file.md');

$flysystem->createDirectory('foo/');
$directoryExists =$flysystem->directoryExists('foo/');
$flysystem->deleteDirectory('foo/');
$listContents = $flysystem->listContents('/', true);

$lastModified = $flysystem->lastModified('file.md');
$fileSize = $flysystem->fileSize('file.md');
$mimeType = $flysystem->mimeType('file.md');

$flysystem->setVisibility('file.md', 'private');
$visibility = $flysystem->visibility('file.md');

$flysystem->write('file.md', 'contents', [
    "options" => ["length" => 8]
]);
$flysystem->write('file.md', 'contents', [
    "headers" => ["Content-Disposition" => "attachment; filename=file.md"]
]);
$flysystem->write('file.md', 'contents', [
    "visibility" => "private"
]);

<?php

require __DIR__.'/../vendor/autoload.php';

use OSS\OssClient;
use League\Flysystem\Filesystem;
use AlphaSnow\Flysystem\AliyunOss\AliyunOssAdapter;
use AlphaSnow\Flysystem\AliyunOss\Plugins\AppendContent;
use AlphaSnow\Flysystem\AliyunOss\Plugins\GetTemporaryUrl;

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
$adapter = new AliyunOssAdapter($client, $config['bucket'], $config['prefix'], $config['options']);
$flysystem = new Filesystem($adapter, ["disable_asserts" => true,"case_sensitive" => true]);
$flysystem->addPlugin(new AppendContent());
$flysystem->addPlugin(new GetTemporaryUrl());

$result = $flysystem->write('file.md', 'contents');
$result = $flysystem->writeStream('file.md', fopen('file.md', 'r'));
$result = $flysystem->update('file.md', 'new contents');
$result = $flysystem->updateStream('file.md', fopen('file.md', 'r'));

$result = $flysystem->delete('foo.md');
$result = $flysystem->appendContent('foo.md', 'contents', 0);
$result = $flysystem->getTemporaryUrl('foo.md');

$result = $flysystem->copy('foo.md', 'baz.md');
$result = $flysystem->rename('baz.md', 'bar.md');
$result = $flysystem->delete('bar.md');
$result = $flysystem->has('bar.md');

$result = $flysystem->read('file.md');
$result = $flysystem->readStream('file.md');
// $result = $flysystem->readAndDelete('file.md');

$result = $flysystem->createDir('foo/');
$result = $flysystem->deleteDir('foo/');
$result = $flysystem->write('bar/foo.md', 'contents');
$result = $flysystem->write('bar/foo/file.md', 'contents');
$result = $flysystem->deleteDir('bar/');
$result = $flysystem->listContents('/');
$result = $flysystem->listContents('/', true);

$result = $flysystem->setVisibility('file.md', 'private');
$result = $flysystem->getVisibility('file.md');

$result = $flysystem->getMetadata('file.md');
$result = $flysystem->getSize('file.md');
$result = $flysystem->getMimetype('file.md');
$result = $flysystem->getTimestamp('file.md');

$result = $flysystem->write('file.md', 'contents', [
    "options" => ["length" => 8]
]);
$result = $flysystem->write('file.md', 'contents', [
    "headers" => ["Content-Disposition" => "attachment; filename=file.md"]
]);
$result = $flysystem->write('file.md', 'contents', [
    "visibility" => "private"
]);

//$result = $flysystem->getMetadata('none.md');
//$exception = $flysystem->getAdapter()->getException();

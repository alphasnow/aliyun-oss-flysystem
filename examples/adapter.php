<?php

require __DIR__.'/../vendor/autoload.php';

use OSS\OssClient;
use League\Flysystem\Filesystem;
use AlphaSnow\Flysystem\Aliyun\AliyunAdapter;
use AlphaSnow\Flysystem\Aliyun\Plugins\AppendContent;
use AlphaSnow\Flysystem\Aliyun\Plugins\GetTemporaryUrl;

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
$flysystem->addPlugin(new AppendContent());
$flysystem->addPlugin(new GetTemporaryUrl());

$flysystem->write('file.md', 'contents');
$flysystem->writeStream('file.md', fopen('file.md', 'r'));
$flysystem->update('file.md', 'new contents');
$flysystem->updateStream('file.md', fopen('file.md', 'r'));

$flysystem->delete('foo.md');
$flysystem->appendContent('foo.md', 'contents', 0);
$flysystem->getTemporaryUrl('foo.md');

$flysystem->copy('foo.md', 'baz.md');
$flysystem->rename('baz.md', 'bar.md');
$flysystem->delete('bar.md');
$flysystem->has('bar.md');

$flysystem->read('file.md');
$flysystem->readStream('file.md');
// $flysystem->readAndDelete('file.md');

$flysystem->createDir('foo/');
$flysystem->deleteDir('foo/');
$flysystem->listContents('/');
$flysystem->listContents('/', true);

$flysystem->setVisibility('file.md', 'public');
$flysystem->getVisibility('file.md');

$flysystem->getMetadata('file.md');
$flysystem->getSize('file.md');
$flysystem->getMimetype('file.md');
$flysystem->getTimestamp('file.md');

$flysystem->write('file.md', 'contents', [
    "options" => ["length" => 8]
]);
$flysystem->write('file.md', 'contents', [
    "headers" => ["Content-Disposition" => "attachment; filename=file.md"]
]);
$flysystem->write('file.md', 'contents', [
    "visibility" => "private"
]);

//$flysystem->getMetadata('none.md');
//$exception = $flysystem->getAdapter()->getException();

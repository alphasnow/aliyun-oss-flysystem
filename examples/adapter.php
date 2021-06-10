<?php
require __DIR__.'/../vendor/autoload.php';

use League\Flysystem\Filesystem;
use AlphaSnow\Flysystem\AliyunOss\AliyunOssAdapter;

$accessId = '******';
$accessKey = '******';
$endpoint = 'endpoint.com'; // example: oss-cn-shanghai.aliyuncs.com
$bucket = 'bucket'; // example: static-files

$adapter = AliyunOssAdapter::create($accessKey, $accessKey, $endpoint, $bucket);
$flysystem = new Filesystem($adapter);

$flysystem->write('file.md', 'contents');
$flysystem->writeStream('file.md', fopen('file.md', 'r'));

$flysystem->update('file.md', 'new contents');
$flysystem->updateStream('file.md', fopen('file.md', 'r'));

$flysystem->rename('foo.md', 'bar.md');
$flysystem->copy('foo.md', 'baz.md');
$flysystem->delete('file.md');
$flysystem->has('file.md');
$flysystem->read('file.md');
$flysystem->readAndDelete('file.md');

$flysystem->createDir('foo/');
$flysystem->deleteDir('foo/');
$flysystem->listContents();

$flysystem->setVisibility('foo/bar','public');
$flysystem->getVisibility('foo/bar');

$flysystem->getMetadata('file.md');
$flysystem->getSize('file.md');
$flysystem->getMimetype('file.md');
$flysystem->getTimestamp('file.md');
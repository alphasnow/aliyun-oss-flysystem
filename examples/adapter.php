<?php

require __DIR__.'/../vendor/autoload.php';

use League\Flysystem\Filesystem;
use AlphaSnow\Flysystem\AliyunOss\AliyunOssAdapter;

$env = is_file(__DIR__ . '/.env') ? __DIR__ . '/.env' : __DIR__ . '/.env.example';
$config = parse_ini_file($env);
!isset($config['options']) && $config['options'] = [];

$adapter = AliyunOssAdapter::create($config['access_id'], $config['access_key'], $config['endpoint'], $config['bucket'], $config['prefix'], $config['options']);
$flysystem = new Filesystem($adapter, ["disable_asserts" => true]);

$result = $flysystem->write('file.md', 'contents');
$result = $flysystem->writeStream('file.md', fopen('file.md', 'r'));
$result = $flysystem->update('file.md', 'new contents');
$result = $flysystem->updateStream('file.md', fopen('file.md', 'r'));

$result = $flysystem->copy('file.md', 'baz.md');
$result = $flysystem->rename('baz.md', 'bar.md');
$result = $flysystem->delete('bar.md');
$result = $flysystem->has('file.md');

$result = $flysystem->read('file.md');
$result = $flysystem->readStream('file.md');
// $result = $flysystem->readAndDelete('file.md');

$result = $flysystem->createDir('foo/');
$result = $flysystem->deleteDir('foo/');
$result = $flysystem->listContents('/');
$result = $flysystem->listContents('/',true);

$result = $flysystem->setVisibility('file.md', 'public');
$result = $flysystem->getVisibility('file.md');

$result = $flysystem->getMetadata('file.md');
$result = $flysystem->getSize('file.md');
$result = $flysystem->getMimetype('file.md');
$result = $flysystem->getTimestamp('file.md');

$result = $flysystem->write('file.md', 'contents', [
    "options" => ["length" => 8]
]);
$result = $flysystem->write('file.md', 'contents', [
    "headers" => ["Content-Disposition" => "attachment;filename=file.md"]
]);
$result = $flysystem->write('file.md', 'contents', [
    "visibility" => "private"
]);

//$result = $flysystem->getMetadata('none.md');
//$exception = $flysystem->getAdapter()->getException();

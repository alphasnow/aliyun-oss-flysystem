<?php

require __DIR__.'/../vendor/autoload.php';

use League\Flysystem\Filesystem;
use AlphaSnow\Flysystem\AliyunOss\AliyunOssAdapter;

$env = is_file(__DIR__.'/env.ini') ? __DIR__.'/env.ini' : __DIR__.'/env.example.ini' ;
$config = parse_ini_file($env);

$adapter = AliyunOssAdapter::create($config['access_id'], $config['access_key'], $config['endpoint'], $config['bucket'], $config['prefix'], $config['options']);
$flysystem = new Filesystem($adapter, ["disable_asserts" => true]);

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

$flysystem->setVisibility('foo/bar', 'public');
$flysystem->getVisibility('foo/bar');

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

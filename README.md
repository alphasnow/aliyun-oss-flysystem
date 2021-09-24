# Aliyun OSS Flysystem

💾 Flysystem adapter for the Aliyun storage.

[![Build Status](https://github.com/alphasnow/aliyun-oss-flysystem/workflows/CI/badge.svg)](https://github.com/alphasnow/aliyun-oss-flysystem/actions)
[![Latest Stable Version](https://poser.pugx.org/alphasnow/aliyun-oss-flysystem/v/stable)](https://packagist.org/packages/alphasnow/aliyun-oss-flysystem)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alphasnow/aliyun-oss-flysystem/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/alphasnow/aliyun-oss-flysystem/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/alphasnow/aliyun-oss-flysystem/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/alphasnow/aliyun-oss-flysystem/?branch=master)
[![License](https://poser.pugx.org/alphasnow/aliyun-oss-flysystem/license)](https://packagist.org/packages/alphasnow/aliyun-oss-flysystem)
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Falphasnow%2Faliyun-oss-flysystem.svg?type=shield)](https://app.fossa.com/projects/git%2Bgithub.com%2Falphasnow%2Faliyun-oss-flysystem?ref=badge_shield)

## Requirement

- PHP >= 5.5.9

## Installation

```bash
composer require "alphasnow/aliyun-oss-flysystem" -vvv
```

## Usage

### Initialize
```php
use League\Flysystem\Filesystem;
use AlphaSnow\Flysystem\AliyunOss\AliyunOssAdapter;

$env = is_file(__DIR__.'/env.ini') ? __DIR__.'/env.ini' : __DIR__.'/env.example.ini' ;
$config = parse_ini_file($env);

$adapter = AliyunOssAdapter::create($config['access_id'], $config['access_key'], $config['endpoint'], $config['bucket'], $config['prefix'], $config['options']);
$flysystem = new Filesystem($adapter, ["disable_asserts" => true]);
```

### Methods
```php
$flysystem->write('file.md', 'contents');
$flysystem->writeStream('file.md', fopen('file.md', 'r'));
$flysystem->update('file.md', 'new contents');
$flysystem->updateStream('file.md', fopen('file.md', 'r'));

$flysystem->copy('file.md', 'baz.md');
$flysystem->rename('baz.md', 'bar.md');
$flysystem->delete('bar.md');
$flysystem->has('file.md');

$flysystem->read('file.md');
$flysystem->readStream('file.md');
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
```

### Options
```php
$flysystem->write('file.md', 'contents', [
    "options" => ["length" => 8]
]);
$flysystem->write('file.md', 'contents', [
    "headers" => ["Content-Disposition" => "attachment; filename=file.md"]
]);
$flysystem->write('file.md', 'contents', [
    "visibility" => "private"
]);
```

## License
The MIT License (MIT). Please see [License File](LICENSE) for more information.

[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Falphasnow%2Faliyun-oss-flysystem.svg?type=large)](https://app.fossa.com/projects/git%2Bgithub.com%2Falphasnow%2Faliyun-oss-flysystem?ref=badge_large)

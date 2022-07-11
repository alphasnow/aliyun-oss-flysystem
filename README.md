# Aliyun OSS Flysystem

💾 Flysystem Adapter for [Aliyun Object Storage Service](http://oss.aliyun.com).

[![Latest Stable Version](https://poser.pugx.org/alphasnow/aliyun-oss-flysystem/v/stable)](https://packagist.org/packages/alphasnow/aliyun-oss-flysystem)
[![Total Downloads](https://poser.pugx.org/alphasnow/aliyun-oss-flysystem/downloads)](https://packagist.org/packages/alphasnow/aliyun-oss-flysystem)
[![License](https://poser.pugx.org/alphasnow/aliyun-oss-flysystem/license)](https://packagist.org/packages/alphasnow/aliyun-oss-flysystem)
[![Build Status](https://github.com/alphasnow/aliyun-oss-flysystem/workflows/CI/badge.svg)](https://github.com/alphasnow/aliyun-oss-flysystem/actions)

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

$config = [
    "access_id" => "LTAI4**************qgcsA",        // Required, AccessKey 
    "access_key"=> "PkT4F********************Bl9or",  // Required, AccessKey Key Secret
    "endpoint"  => "oss-cn-shanghai.aliyuncs.com",    // Required, Endpoint
    "bucket"    => "my-storage",                      // Required, Bucket
    "prefix"    => "",
    "options"   => [
        "is_cname"       => false,
        "security_token" => null,
        "request_proxy"  => null,
        "checkmd5"       => false
    ]
];

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
$result = $flysystem->listContents('/');
$result = $flysystem->listContents('/',true);

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
    "headers" => ["Content-Disposition" => "attachment;filename=file.md"]
]);
$flysystem->write('file.md', 'contents', [
    "visibility" => "private"
]);
```

### Exception
```php
$flysystem->getMetadata('none.md');
$exception = $flysystem->getAdapter()->getException();
```

## Reference
[https://github.com/thephpleague/flysystem](https://github.com/thephpleague/flysystem)
[https://github.com/aliyun/aliyun-oss-php-sdk-flysystem](https://github.com/aliyun/aliyun-oss-php-sdk-flysystem)

## License
The MIT License (MIT). Please see [License File](LICENSE) for more information.

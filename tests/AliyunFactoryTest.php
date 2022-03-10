<?php

namespace AlphaSnow\Flysystem\Aliyun\Tests;

use AlphaSnow\Flysystem\Aliyun\AliyunFactory;
use League\Flysystem\Filesystem;
use OSS\OssClient;
use PHPUnit\Framework\TestCase;

class AliyunFactoryTest extends TestCase
{
    public function testCreateClient()
    {
        $config = [];
        $config['access_key_id'] = "access_id";
        $config['access_key_secret'] = "access_secret";
        $config['bucket'] = "bucket";
        $config['endpoint'] = "endpoint.com";

        $factory = new AliyunFactory();
        $client = $factory->createClient($config);
        $this->assertInstanceOf(OssClient::class, $client);
        return $client;
    }

    public function testCreateFilesystem()
    {
        $config = [];
        $config['access_key_id'] = "access_id";
        $config['access_key_secret'] = "access_secret";
        $config['bucket'] = "bucket";
        $config['endpoint'] = "endpoint.com";

        $factory = new AliyunFactory();
        $filesystem = $factory->createFilesystem($config);
        $this->assertInstanceOf(Filesystem::class, $filesystem);
    }
}

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
        $config['access_key_id'] = getenv("OSS_ACCESS_KEY_ID");
        $config['access_key_secret'] = getenv("OSS_ACCESS_KEY_SECRET");
        $config['bucket'] = getenv("OSS_BUCKET");
        $config['endpoint'] = getenv("OSS_ENDPOINT");
        $factory = new AliyunFactory();
        $client = $factory->createClient($config);
        $this->assertInstanceOf(OssClient::class, $client);
        return $client;
    }

    public function testCreateFilesystem()
    {
        $config = [];
        $config['access_key_id'] = getenv("OSS_ACCESS_KEY_ID");
        $config['access_key_secret'] = getenv("OSS_ACCESS_KEY_SECRET");
        $config['bucket'] = getenv("OSS_BUCKET");
        $config['endpoint'] = getenv("OSS_ENDPOINT");

        $factory = new AliyunFactory();
        $filesystem = $factory->createFilesystem($config);
        $this->assertInstanceOf(Filesystem::class, $filesystem);
    }
}

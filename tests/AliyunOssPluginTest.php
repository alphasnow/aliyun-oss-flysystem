<?php

namespace AlphaSnow\Flysystem\AliyunOss\Tests;

use AlphaSnow\Flysystem\AliyunOss\AliyunOssAdapter;
use AlphaSnow\Flysystem\AliyunOss\Plugins\AppendContent;
use League\Flysystem\Filesystem;
use Mockery\MockInterface;
use OSS\OssClient;
use PHPUnit\Framework\TestCase;

class AliyunOssPluginTest extends TestCase
{
    public function aliyunProvider()
    {
        $accessId = getenv("ALIYUN_OSS_ACCESS_ID");
        $accessKey = getenv("ALIYUN_OSS_ACCESS_KEY");
        $bucket = getenv("ALIYUN_OSS_BUCKET");
        $endpoint = getenv("ALIYUN_OSS_ENDPOINT");

        /**
         * @var $client OssClient
         */
        $client = \Mockery::mock(OssClient::class, [$accessId,$accessKey,$endpoint])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $adapter = new AliyunOssAdapter($client, $bucket);
        $flysystem = new Filesystem($adapter, ["disable_asserts" => true,"case_sensitive" => true]);

        return [
            [$flysystem,$adapter,$client]
        ];
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param Filesystem $filesystem
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testAppendContent($filesystem, $adapter, $client)
    {
        $filesystem->addPlugin(new AppendContent());

        $client->shouldReceive("appendObject")
            ->andReturn(7)
            ->once();

        $position = $filesystem->appendContent("foo/bar.md", "content", 0);

        $this->assertSame(7, $position);
    }
}

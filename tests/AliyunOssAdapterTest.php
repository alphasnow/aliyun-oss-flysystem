<?php

namespace AlphaSnow\Flysystem\AliyunOss\Tests;

use AlphaSnow\Flysystem\AliyunOss\AliyunOssAdapter;
use League\Flysystem\Config;
use OSS\OssClient;
use PHPUnit\Framework\TestCase;

class AliyunOssAdapterTest extends TestCase
{
    public function aliyunProvider()
    {
        $accessId = getenv('ALIYUN_OSS_ACCESS_ID');
        $accessKey = getenv('ALIYUN_OSS_ACCESS_KEY');
        $bucket = getenv('ALIYUN_OSS_BUCKET');
        $endpoint = getenv('ALIYUN_OSS_ENDPOINT');
        $client = new OssClient($accessId,$accessKey,$endpoint);
        $adapter = new AliyunOssAdapter($client,$bucket);
        return [
            [$adapter]
        ];
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testWrite(AliyunOssAdapter $adapter)
    {
        $result = $adapter->write('foo/bar.md', 'content', new Config());
        $this->assertSame([
            'type'=>'file',
            'path'=>'foo/bar.md'
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testUpdate(AliyunOssAdapter $adapter)
    {
        $adapter->write('foo/bar.md', 'content', new Config());

        $result = $adapter->update('foo/bar.md', 'update', new Config());
        $this->assertSame([
            'type'=>'file',
            'path'=>'foo/bar.md'
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testRename(AliyunOssAdapter $adapter)
    {
        $adapter->write('foo/bar.md', 'content', new Config());

        $result = $adapter->copy('foo/bar.md', 'foo/baz.md');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testDelete(AliyunOssAdapter $adapter)
    {
        $adapter->write('foo/bar.md', 'content', new Config());

        $result = $adapter->delete('foo/bar.md');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testDeleteDir(AliyunOssAdapter $adapter)
    {
        $adapter->write('foo/bar.md', 'content', new Config());
        $adapter->write('foo/baz.md', 'content', new Config());

        $result = $adapter->deleteDir('foo/');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testCreateDir(AliyunOssAdapter $adapter)
    {
        $result = $adapter->createDir('baz/',new Config());
        $this->assertSame([
            'type'=>'dir',
            'path'=>'baz/'
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testSetVisibility(AliyunOssAdapter $adapter)
    {
        $adapter->write('foo/bar.md', 'content', new Config());

        $result = $adapter->setVisibility('foo/bar.md','public');
        $this->assertSame([
            'visibility'=>'public',
            'path'=>'foo/bar.md'
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testHas(AliyunOssAdapter $adapter)
    {
        $adapter->write('foo/bar.md', 'content', new Config());

        $result = $adapter->has('foo/bar.md');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testRead(AliyunOssAdapter $adapter)
    {
        $adapter->write('foo/bar.md', 'content', new Config());

        $result = $adapter->read('foo/bar.md');
        $this->assertSame([
            'path'=>'foo/bar.md',
            'contents'=>'content'
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testListContents(AliyunOssAdapter $adapter)
    {
        $adapter->deleteDir('foo/');
        $adapter->write('foo/bar.md', 'content', new Config());
        $adapter->createDir('foo/baz/',new Config());

        $result = $adapter->listContents('foo/');
        $this->assertSame([
            [
                'type'=>'file',
                'path'=>'foo/bar.md',
                'size'=>7
            ],
            [
                'type'=>'dir',
                'path'=>'foo/baz/',
            ]
        ],$result);
    }
}

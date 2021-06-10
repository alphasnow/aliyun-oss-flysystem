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
            'path'=>'foo/bar.md',
            'timestamp'=>$result['timestamp'],
            'size'=>7,
            'mimetype'=>'application/octet-stream',
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
            'path'=>'foo/bar.md',
            'timestamp'=>$result['timestamp'],
            'size'=>6,
            'mimetype'=>'application/octet-stream',
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testRename(AliyunOssAdapter $adapter)
    {
        $adapter->write('foo/bar.md', 'content', new Config());

        $result = $adapter->rename('foo/bar.md', 'foo/baz.md');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testCopy(AliyunOssAdapter $adapter)
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
        $this->assertTrue($result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testHas(AliyunOssAdapter $adapter)
    {
        $adapter->write('foo/bar.md', 'content', new Config());

        $this->assertTrue($adapter->has('foo/bar.md'));

        $adapter->delete('foo/bar.md');

        $this->assertFalse($adapter->has('foo/bar.md'));
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testRead(AliyunOssAdapter $adapter)
    {
        $adapter->write('foo/bar.md', 'content', new Config());

        $result = $adapter->read('foo/bar.md');
        $this->assertSame([
            'type'=>'file',
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
        $file = $adapter->write('foo/bar.md', 'content', new Config());
        $adapter->createDir('foo/baz/',new Config());

        $result = $adapter->listContents('foo/');
        $this->assertSame([
            [
                'type'=>'file',
                'path'=>'foo/bar.md',
                'size'=>$file['size'],
                'timestamp'=>$file['timestamp']
            ],
            [
                'type'=>'dir',
                'path'=>'foo/baz/',
                'size'=>0,
                'timestamp'=>0
            ]
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testGetMetadata(AliyunOssAdapter $adapter)
    {
        $file = $adapter->write('foo/bar.md', 'content', new Config());

        $result = $adapter->getMetadata('foo/bar.md');
        $this->assertSame([
            'type'=>'file',
            'path'=>'foo/bar.md',
            'size'=>$file['size'],
            'timestamp'=>$file['timestamp'],
            'mimetype'=>'application/octet-stream'
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testGetSize(AliyunOssAdapter $adapter)
    {
        $file = $adapter->write('foo/bar.md', 'content', new Config());

        $result = $adapter->getSize('foo/bar.md');
        $this->assertSame([
            'size'=>$file['size'],
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testGetMimetype(AliyunOssAdapter $adapter)
    {
        $file = $adapter->write('foo/bar.md', 'content', new Config());

        $result = $adapter->getMimetype('foo/bar.md');
        $this->assertSame([
            'mimetype'=>$file['mimetype'],
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testGetTimestamp(AliyunOssAdapter $adapter)
    {
        $file = $adapter->write('foo/bar.md', 'content', new Config());

        $result = $adapter->getTimestamp('foo/bar.md');
        $this->assertSame([
            'timestamp'=>$file['timestamp'],
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testGetVisibility(AliyunOssAdapter $adapter)
    {
        $adapter->write('foo/bar.md', 'content', new Config(['visibility'=>'public']));

        $result = $adapter->getVisibility('foo/bar.md');
        $this->assertSame([
            'visibility'=>'public',
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testGetClient(AliyunOssAdapter $adapter)
    {
        $client = $adapter->getClient();

        $this->assertTrue($client instanceof OssClient);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testGetBucket(AliyunOssAdapter $adapter)
    {
        $bucket = $adapter->getBucket();

        $this->assertSame(getenv('ALIYUN_OSS_BUCKET'),$bucket);
    }

    /**
     * @dataProvider aliyunProvider
     */
    public function testOptions(AliyunOssAdapter $adapter)
    {
        $options =[
            'Content-Type'=>'application/octet-stream'
        ];
        $adapter->setOptions($options);


        $this->assertSame($options,$adapter->getOptions());
    }
}

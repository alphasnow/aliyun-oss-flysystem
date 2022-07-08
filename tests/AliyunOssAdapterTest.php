<?php

namespace AlphaSnow\Flysystem\AliyunOss\Tests;

use AlphaSnow\Flysystem\AliyunOss\AliyunOssAdapter;
use League\Flysystem\Config;
use Mockery\MockInterface;
use OSS\Core\OssException;
use OSS\Model\ObjectInfo;
use OSS\Model\PrefixInfo;
use OSS\OssClient;
use PHPUnit\Framework\TestCase;

class AliyunOssAdapterTest extends TestCase
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
        $client = \Mockery::mock(OssClient::class, [$accessId, $accessKey, $endpoint])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $adapter = new AliyunOssAdapter($client, $bucket);

        return [
            [$adapter, $client]
        ];
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testWrite($adapter, $client)
    {
        $client->shouldReceive("putObject")
            ->andReturn(["oss-requestheaders" => ["Date" => "Thu, 10 Jun 2021 02:42:20 GMT", "Content-Length" => "7", "Content-Type" => "application/octet-stream"]])
            ->once();

        $result = $adapter->write("foo/bar.md", "content", new Config());
        $this->assertSame([
            "type" => "file",
            "path" => "foo/bar.md",
            "size" => 7,
            "mimetype" => "text/markdown",
        ], $result);

        $client->shouldReceive("putObject")
            ->andThrow(new OssException("error"))
            ->once();

        $result = $adapter->write("foo/bar.md", "content", new Config());
        $this->assertFalse($result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testWriteStream($adapter, $client)
    {
        $client->shouldReceive("uploadStream")
            ->andReturn(["info" => ["upload_content_length" => 7.0], "oss-requestheaders" => ["Date" => "Thu, 10 Jun 2021 02:42:20 GMT", "Content-Type" => "application/octet-stream"]])
            ->once();

        $fp = fopen("php://temp", "w+");
        fwrite($fp, "content");
        $result = $adapter->writeStream("foo/bar.md", $fp, new Config());
        fclose($fp);

        $this->assertSame([
            "type" => "file",
            "path" => "foo/bar.md",
            "size" => 7,
            "mimetype" => "text/markdown",
        ], $result);

        $client->shouldReceive("uploadStream")
            ->andThrow(new OssException("error"))
            ->once();

        $fp = fopen("php://temp", "w+");
        fwrite($fp, "content");
        $result = $adapter->writeStream("foo/bar.md", $fp, new Config());
        fclose($fp);
        $this->assertFalse($result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testUpdate($adapter, $client)
    {
        $client->shouldReceive("putObject")
            ->andReturn(["oss-requestheaders" => ["Date" => "Thu, 10 Jun 2021 02:42:20 GMT", "Content-Length" => "6", "Content-Type" => "application/octet-stream"]])
            ->once();

        $result = $adapter->update("foo/bar.md", "update", new Config());
        $this->assertSame([
            "type" => "file",
            "path" => "foo/bar.md",
            "size" => 6,
            "mimetype" => "text/markdown",
        ], $result);

        $client->shouldReceive("putObject")
            ->andThrow(new OssException("error"))
            ->once();

        $result = $adapter->update("foo/bar.md", "update", new Config());
        $this->assertFalse($result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testUpdateStream($adapter, $client)
    {
        $client->shouldReceive("uploadStream")
            ->andReturn(["info" => ["upload_content_length" => 7.0], "oss-requestheaders" => ["Date" => "Thu, 10 Jun 2021 02:42:20 GMT", "Content-Type" => "application/octet-stream"]])
            ->once();

        $fp = fopen("php://temp", "w+");
        fwrite($fp, "content");
        $result = $adapter->updateStream("foo/bar.md", $fp, new Config());
        fclose($fp);

        $this->assertSame([
            "type" => "file",
            "path" => "foo/bar.md",
            "size" => 7,
            "mimetype" => "text/markdown",
        ], $result);

        $client->shouldReceive("uploadStream")
            ->andThrow(new OssException("error"))
            ->once();

        $fp = fopen("php://temp", "w+");
        fwrite($fp, "content");
        $result = $adapter->writeStream("foo/bar.md", $fp, new Config());
        fclose($fp);
        $this->assertFalse($result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testRename($adapter, $client)
    {
        $client->shouldReceive("copyObject", "deleteObject")
            ->andReturn(null)
            ->once();

        $result = $adapter->rename("foo/bar.md", "foo/baz.md");
        $this->assertTrue($result);

        $client->shouldReceive("copyObject", "deleteObject")
            ->andThrow(new OssException("error"))
            ->once();

        $result = $adapter->rename("foo/bar.md", "foo/baz.md");
        $this->assertFalse($result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testCopy($adapter, $client)
    {
        $client->shouldReceive("copyObject")
            ->andReturn(null)
            ->once();

        $result = $adapter->copy("foo/bar.md", "foo/baz.md");
        $this->assertTrue($result);

        $client->shouldReceive("copyObject")
            ->andThrow(new OssException("error"))
            ->once();

        $result = $adapter->copy("foo/bar.md", "foo/baz.md");
        $this->assertFalse($result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testDelete($adapter, $client)
    {
        $client->shouldReceive("deleteObject")
            ->andReturn(null)
            ->once();

        $result = $adapter->delete("foo/bar.md");
        $this->assertTrue($result);

        $client->shouldReceive("deleteObject")
            ->andThrow(new OssException("error"))
            ->once();

        $result = $adapter->delete("foo/bar.md");
        $this->assertFalse($result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testSetVisibility($adapter, $client)
    {
        $client->shouldReceive("putObjectAcl")
            ->andReturn(null)
            ->once();

        $result = $adapter->setVisibility("foo/bar.md", "public");
        $this->assertSame([
            "visibility" => "public"
        ], $result);

        $client->shouldReceive("putObjectAcl")
            ->andThrow(new OssException("error"))
            ->once();

        $result = $adapter->setVisibility("foo/bar.md", "public");
        $this->assertFalse($result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testHas($adapter, $client)
    {
        $client->shouldReceive("doesObjectExist")
            ->andReturn(true, false)
            ->times(2);

        $this->assertTrue($adapter->has("foo/bar.md"));
        $this->assertFalse($adapter->has("foo/baz.md"));
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testRead($adapter, $client)
    {
        $client->shouldReceive("getObject")
            ->andReturn("content");

        $result = $adapter->read("foo/bar.md");
        $this->assertSame([
            "type" => "file",
            "path" => "foo/bar.md",
            "contents" => "content"
        ], $result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testReadStream($adapter, $client)
    {
        $client->shouldReceive("getObject")
            ->andReturn(null);

        $result = $adapter->readStream("foo/bar.md");

        $this->assertTrue(is_resource($result["stream"]));
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetMetadata($adapter, $client)
    {
        $client->shouldReceive("getObjectMeta")
            ->andReturn(["content-length" => 7, "last-modified" => "Wed, 09 Mar 2022 08:40:58 GMT", "content-type" => "application/octet-stream"]);

        $result = $adapter->getMetadata("foo/bar.md");
        $this->assertSame([
            "type" => "file",
            "path" => "foo/bar.md",
            "size" => 7,
            "mimetype" => "application/octet-stream",
            "timestamp" => 1646815258
        ], $result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetSize($adapter, $client)
    {
        $client->shouldReceive("getObjectMeta")
            ->andReturn(["content-length" => 7, "last-modified" => "Wed, 09 Mar 2022 08:40:58 GMT", "content-type" => "application/octet-stream"]);

        $result = $adapter->getSize("foo/bar.md");
        $this->assertSame([
            "size" => 7,
        ], $result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetMimetype($adapter, $client)
    {
        $client->shouldReceive("getObjectMeta")
            ->andReturn(["content-length" => 7, "last-modified" => "Wed, 09 Mar 2022 08:40:58 GMT", "content-type" => "application/octet-stream"]);

        $result = $adapter->getMimetype("foo/bar.md");
        $this->assertSame([
            "mimetype" => "application/octet-stream",
        ], $result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetTimestamp($adapter, $client)
    {
        $client->shouldReceive("getObjectMeta")
            ->andReturn(["content-length" => 7, "last-modified" => "Wed, 09 Mar 2022 08:40:58 GMT", "content-type" => "application/octet-stream"]);

        $result = $adapter->getTimestamp("foo/bar.md");
        $this->assertSame([
            "timestamp" => 1646815258,
        ], $result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetVisibility($adapter, $client)
    {
        $client->shouldReceive("getObjectAcl")
            ->andReturn("public-read");

        $result = $adapter->getVisibility("foo/bar.md");
        $this->assertSame([
            "visibility" => "public",
        ], $result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetClient($adapter, $client)
    {
        $adapterClient = $adapter->getClient();

        $this->assertSame($client, $adapterClient);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     */
    public function testGetBucket($adapter)
    {
        $bucket = $adapter->getBucket();

        $this->assertSame(getenv("ALIYUN_OSS_BUCKET"), $bucket);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     */
    public function testOptions($adapter)
    {
        $options = [
            "Content-Type" => "application/octet-stream"
        ];
        $adapter->setOptions($options);

        $this->assertSame($options, $adapter->getOptions());
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetException($adapter, $client)
    {
        $errorException = new OssException("error");
        $client->shouldReceive("getObject")
            ->andThrow($errorException);

        $result = $adapter->read("none.md");
        $exception = $adapter->getException();

        $this->assertFalse($result);
        $this->assertSame($errorException, $exception);
    }

    public function testCreate()
    {
        $accessId = getenv("ALIYUN_OSS_ACCESS_ID");
        $accessKey = getenv("ALIYUN_OSS_ACCESS_KEY");
        $bucket = getenv("ALIYUN_OSS_BUCKET");
        $endpoint = getenv("ALIYUN_OSS_ENDPOINT");

        $adapter = AliyunOssAdapter::create($accessId, $accessKey, $bucket, $endpoint);
        $this->assertInstanceOf(AliyunOssAdapter::class, $adapter);
    }
}

<?php

namespace AlphaSnow\Flysystem\Aliyun\Tests;

use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use PHPUnit\Framework\TestCase;
use AlphaSnow\Flysystem\Aliyun\AliyunAdapter;
use League\Flysystem\Config;
use Mockery\MockInterface;
use OSS\Core\OssException;
use OSS\Model\ObjectInfo;
use OSS\Model\PrefixInfo;
use OSS\OssClient;

class AliyunAdapterTest extends TestCase
{
    public function aliyunProvider()
    {
        $accessId = getenv("OSS_ACCESS_KEY_ID");
        $accessKey = getenv("OSS_ACCESS_KEY_SECRET");
        $bucket = getenv("OSS_BUCKET");
        $endpoint = getenv("OSS_ENDPOINT");

        /**
         * @var $client OssClient
         */
        $client = \Mockery::mock(OssClient::class, [$accessId,$accessKey,$endpoint])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $adapter = new AliyunAdapter($client, $bucket);

        return [
            [$adapter,$client]
        ];
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testWrite($adapter, $client)
    {
        $this->expectException(UnableToWriteFile::class);

        $client->shouldReceive("putObject")
            ->andThrow(new OssException("error"))
            ->once();

        $adapter->write("foo/bar.md", "content", new Config());
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testWriteStream($adapter, $client)
    {
        $this->expectException(UnableToWriteFile::class);

        $client->shouldReceive("uploadStream")
            ->andThrow(new OssException("error"))
            ->once();

        $fp = fopen('php://temp', 'w+');
        fwrite($fp, "content");
        $adapter->writeStream("foo/bar.md", $fp, new Config());
        fclose($fp);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testMove($adapter, $client)
    {
        $this->expectException(UnableToCopyFile::class);

        $client->shouldReceive("copyObject", "deleteObject")
            ->andThrow(new OssException("error"))
            ->once();

        $adapter->move("foo/bar.md", "foo/baz.md", new Config());
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testCopy($adapter, $client)
    {
        $this->expectException(UnableToCopyFile::class);

        $client->shouldReceive("copyObject")
            ->andThrow(new OssException("error"))
            ->once();

        $adapter->copy("foo/bar.md", "foo/baz.md", new Config());
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testDelete($adapter, $client)
    {
        $this->expectException(UnableToDeleteFile::class);

        $client->shouldReceive("deleteObject")
            ->andThrow(new OssException("error"))
            ->once();

        $adapter->delete("foo/bar.md");
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testDeleteDirectory($adapter, $client)
    {
        $listObjects = \Mockery::mock("stdClass")->allows([
            "getObjectList" => [new ObjectInfo(
                "foo/bar.md",
                "Thu, 10 Jun 2021 02:42:20 GMT",
                "9A0364B9E99BB480DD25E1F0284C8555",
                "application/octet-stream",
                7,
                "standard"
            ),new ObjectInfo(
                "foo/baz.md",
                "Thu, 10 Jun 2021 02:42:20 GMT",
                "9A0364B9E99BB480DD25E1F0284C8555",
                "application/octet-stream",
                7,
                "standard"
            )],
            "getPrefixList" => [],
            "getNextMarker" => "",
            "getIsTruncated" => "false"
        ]);
        $client->allows([
            "listObjects" => $listObjects,
            "deleteObjects" => null
        ]);

        try {
            $adapter->deleteDirectory("foo/");
        } catch (UnableToDeleteDirectory $exception) {
            $this->assertTrue(false);
        }
        $this->assertTrue(true);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testCreateDirectory($adapter, $client)
    {
        $client->allows([
            "createObjectDir" => null
        ]);

        try {
            $adapter->createDirectory("baz/", new Config());
        } catch (UnableToCreateDirectory $exception) {
            $this->assertTrue(false);
        }
        $this->assertTrue(true);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testSetVisibility($adapter, $client)
    {
        $client->shouldReceive("putObjectAcl")
            ->andReturn(null)
            ->once();

        try {
            $adapter->setVisibility("foo/bar.md", "public");
        } catch (UnableToSetVisibility $e) {
            $this->assertTrue(false);
        }
        $this->assertTrue(true);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testExists($adapter, $client)
    {
        $client->shouldReceive("doesObjectExist")
            ->andReturn(true, false)
            ->times(2);

        $this->assertTrue($adapter->fileExists("foo/bar.md"));
        $this->assertFalse($adapter->directoryExists("foo/"));
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testRead($adapter, $client)
    {
        $client->shouldReceive("getObject")
            ->andReturn("content");

        $result = $adapter->read("foo/bar.md");
        $this->assertSame("content", $result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testReadStream($adapter, $client)
    {
        $client->shouldReceive("getObject")
            ->andReturn(null);

        $result = $adapter->readStream("foo/bar.md");

        $this->assertTrue(is_resource($result));
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testListContents($adapter, $client)
    {
        $listObjects = \Mockery::mock("stdClass")->allows([
            "getObjectList" => [new ObjectInfo(
                "foo/bar.md",
                "Thu, 10 Jun 2021 02:42:20 GMT",
                "9A0364B9E99BB480DD25E1F0284C8555",
                "application/octet-stream",
                7,
                "standard"
            )],
            "getPrefixList" => [new PrefixInfo("foo/baz/")],
            "getNextMarker" => "",
            "getIsTruncated" => "false"
        ]);
        $client->allows([
            "listObjects" => $listObjects
        ]);

        $results = $adapter->listContents("foo/", false);
        $resultPaths = [];
        foreach ($results as $result) {
            $resultPaths[] = $result->path();
        }
        $this->assertSame([
                "foo/bar.md",
            "foo/baz"
        ], $resultPaths);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetSize($adapter, $client)
    {
        $client->shouldReceive("getObjectMeta")
            ->andReturn(["content-length" => "7","last-modified" => "Wed, 09 Mar 2022 08:40:58 GMT","content-type" => "application/octet-stream"]);

        $result = $adapter->fileSize("foo/bar.md");
        $this->assertSame(
            7,
            $result->fileSize()
        );
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testMimetype($adapter, $client)
    {
        $client->shouldReceive("getObjectMeta")
            ->andReturn(["content-length" => "7","last-modified" => "Wed, 09 Mar 2022 08:40:58 GMT","content-type" => "application/octet-stream"]);

        $result = $adapter->mimetype("foo/bar.md");
        $this->assertSame(
            "application/octet-stream",
            $result->mimeType()
        );
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testLastModified($adapter, $client)
    {
        $client->shouldReceive("getObjectMeta")
            ->andReturn(["content-length" => "7","last-modified" => "Wed, 09 Mar 2022 08:40:58 GMT","content-type" => "application/octet-stream"]);

        $result = $adapter->lastModified("foo/bar.md");
        $this->assertSame(
            1646815258,
            $result->lastModified()
        );
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testVisibility($adapter, $client)
    {
        $client->shouldReceive("getObjectAcl")
            ->andReturn(OssClient::OSS_ACL_TYPE_PUBLIC_READ);

        $result = $adapter->visibility("foo/bar.md");
        $this->assertSame(
            "public",
            $result->visibility()
        );
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
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
     * @param AliyunAdapter $adapter
     */
    public function testGetBucket($adapter)
    {
        $bucket = $adapter->getBucket();

        $this->assertSame(getenv("OSS_BUCKET"), $bucket);
    }
}

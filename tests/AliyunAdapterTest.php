<?php

namespace AlphaSnow\Flysystem\Aliyun\Tests;

use AlphaSnow\Flysystem\Aliyun\AliyunException;
use AlphaSnow\Flysystem\Aliyun\OssOptions;
use AlphaSnow\Flysystem\Aliyun\UrlGenerator;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
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
    private $accessId = "access_id";
    private $accessKey = "access_secret";
    private $bucket = "bucket";
    private $endpoint = "endpoint.com";
    private $prefix = "";

    public function aliyunProvider()
    {
        $config = [
            "access_key_id" => $this->accessId,
            "access_key_secret" => $this->accessKey,
            "endpoint" => $this->endpoint,
            "bucket" => $this->bucket,
            "prefix" => $this->prefix,
        ];

        $ossEndpoint = (new UrlGenerator($config))->getOssEndpoint();
        /**
         * @var $client OssClient
         */
        $client = \Mockery::mock(OssClient::class, [$this->accessId,$this->accessKey,$ossEndpoint])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $adapter = new AliyunAdapter($client, $this->bucket, $this->prefix, $config);
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
    public function testFileExistsException($adapter, $client)
    {
        $this->expectException(UnableToCheckExistence::class);
        $client->shouldReceive("doesObjectExist")
            ->andThrow(new OssException("error"))
            ->once();
        $adapter->fileExists("foo/bar.md");
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testDirectoryExistsException($adapter, $client)
    {
        $this->expectException(UnableToCheckExistence::class);
        $client->shouldReceive("doesObjectExist")
            ->andThrow(new OssException("error"))
            ->once();
        $adapter->directoryExists("foo/");
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testWriteException($adapter, $client)
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
    public function testWriteStreamException($adapter, $client)
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
        $client->shouldReceive("copyObject", "deleteObject")
            ->andThrow(new OssException("error"))
            ->once();

        $this->expectException(UnableToCopyFile::class);
        $adapter->move("foo/bar.md", "foo/baz.md", new Config());

        $this->expectException(UnableToDeleteFile::class);
        $adapter->move("foo/bar.md", "foo/baz.md", new Config());
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testCopyException($adapter, $client)
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
    public function testDeleteException($adapter, $client)
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
            "getNextContinuationToken" => "",
            "getIsTruncated" => "false"
        ]);
        $client->allows([
            "listObjectsV2" => $listObjects,
            "deleteObjects" => null,
            "deleteObject"=>null
        ]);
        $adapter->deleteDirectory("foo/");
        $this->assertTrue(true);
    }
    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testDeleteDirectoryException($adapter, $client)
    {
        $this->expectException(AliyunException::class);
        $client->shouldReceive("listObjects")
            ->andThrow(new OssException("error"))
            ->once();
        $adapter->deleteDirectory("bar/");
    }
    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testCreateDirectory($adapter, $client)
    {
        $this->expectException(UnableToCreateDirectory::class);
        $client->shouldReceive("createObjectDir")
            ->andThrow(new OssException("error"))
            ->once();
        $adapter->createDirectory("baz/", new Config());
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testSetVisibility($adapter, $client)
    {
        $this->expectException(UnableToSetVisibility::class);
        $client->shouldReceive("createObjectDir")
            ->andThrow(new OssException("error"))
            ->once();
        $adapter->setVisibility("foo/bar.md", "private");
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
            ->andReturn("content")
            ->once();
        $result = $adapter->read("foo/bar.md");
        $this->assertSame("content", $result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testReadException($adapter, $client)
    {
        $this->expectException(UnableToReadFile::class);
        $client->shouldReceive("createObjectDir")
            ->andThrow(new OssException("error"))
            ->once();
        $adapter->read("foo/bar.md");
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
    public function testReadStreamException($adapter, $client)
    {
        $this->expectException(UnableToReadFile::class);
        $client->shouldReceive("getObject")
            ->andThrow(new OssException("error"))
            ->once();
        $adapter->readStream("foo/bar.md");
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
            "getNextContinuationToken" => "",
            "getIsTruncated" => "false"
        ]);
        $client->allows([
            "listObjectsV2" => $listObjects
        ]);
        $results = $adapter->listContents("foo/", false);
        $resultPaths = [];
        foreach ($results as $result) {
            $resultPaths[] = $result->path();
        }
        $this->assertSame([
            "foo/baz",
            "foo/bar.md"
        ], $resultPaths);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testFileSize($adapter, $client)
    {
        $client->shouldReceive("getObjectMeta")
            ->andReturn(["content-length" => "7","last-modified" => "Wed, 09 Mar 2022 08:40:58 GMT","content-type" => "application/octet-stream"])
            ->once();
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
            ->andReturn(["content-length" => "7","last-modified" => "Wed, 09 Mar 2022 08:40:58 GMT","content-type" => "application/octet-stream"])
            ->once();
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
            ->andReturn(["content-length" => "7","last-modified" => "Wed, 09 Mar 2022 08:40:58 GMT","content-type" => "application/octet-stream"])
            ->once();
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
            ->andReturn(OssClient::OSS_ACL_TYPE_PUBLIC_READ)
            ->once();
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
    public function testVisibilityException($adapter, $client)
    {
        $client->shouldReceive("getObjectAcl")
            ->andThrow(new OssException("error"))
            ->once();
        $this->expectException(UnableToRetrieveMetadata::class);
        $adapter->visibility("foo/bar.md");
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     */
    public function testGetClient($adapter)
    {
        $ossClient = $adapter->getClient();
        $this->assertInstanceOf(OssClient::class, $ossClient);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     */
    public function testGetBucket($adapter)
    {
        $bucket = $adapter->getBucket();
        $this->assertSame($this->bucket, $bucket);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     */
    public function testGetOptions($adapter)
    {
        $options = $adapter->getOptions();
        $this->assertInstanceOf(OssOptions::class, $options);

        $configs = $options->mergeConfig(new Config([
            "headers" => ["Content-Disposition" => "attachment; filename=file.md"],
            "visibility" => "private",
            "options" => ["checkmd5" => false]
        ]));
        $this->assertSame([
            "checkmd5" => false,
            "headers" => [
                "Content-Disposition" => "attachment; filename=file.md",
                "x-oss-object-acl" => "private",
            ],
        ], $configs);

        $options->setOptions(["checkmd5" => false]);
        $this->assertSame(["checkmd5" => false], $options->getOptions());
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     */
    public function testGetPrefix($adapter)
    {
        $prefixer = $adapter->getPrefixer();
        $this->assertInstanceOf(PathPrefixer::class, $prefixer);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     */
    public function testGetUrl($adapter)
    {
        $url = $adapter->getUrl("foo/bar.md");
        $this->assertSame("http://bucket.endpoint.com/foo/bar.md", $url);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetTemporaryUrl($adapter, $client)
    {
        $client->shouldReceive("signUrl")
            ->andReturn("http://bucket.endpoint.com/foo/bar.md?OSSAccessKeyId=********&Expires=1646970000&Signature=***********************")
            ->once();

        $url = $adapter->getTemporaryUrl("foo/bar.md", (new \DateTime())->add(new \DateInterval('P1D')));
        $this->assertSame("http://bucket.endpoint.com/foo/bar.md?OSSAccessKeyId=********&Expires=1646970000&Signature=***********************", $url);
    }
}

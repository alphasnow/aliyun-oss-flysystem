<?php

namespace AlphaSnow\Flysystem\AliyunOss\Tests;

use AlphaSnow\Flysystem\AliyunOss\AliyunOssAdapter;
use League\Flysystem\Config;
use Mockery\MockInterface;
use OSS\Model\ObjectInfo;
use OSS\Model\PrefixInfo;
use OSS\OssClient;
use PHPUnit\Framework\TestCase;

class AliyunOssAdapterTest extends TestCase
{
    protected function isUseMock()
    {
        return !defined("PHPUNIT_RUNNING") || PHPUNIT_RUNNING === true;
    }

    public function aliyunProvider()
    {
        $accessId = getenv("ALIYUN_OSS_ACCESS_ID");
        $accessKey = getenv("ALIYUN_OSS_ACCESS_KEY");
        $bucket = getenv("ALIYUN_OSS_BUCKET");
        $endpoint = getenv("ALIYUN_OSS_ENDPOINT");

        if($this->isUseMock()){
            $client = \Mockery::mock(OssClient::class, [$accessId,$accessKey,$endpoint])
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();
            $adapter = new AliyunOssAdapter($client,$bucket);
        }else{
            $client = new OssClient($accessId,$accessKey,$endpoint);
            $adapter = new AliyunOssAdapter($client,$bucket);
        }

        return [
            [$adapter,$client]
        ];
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testWrite($adapter,$client)
    {
        if($this->isUseMock()){
            $client->allows([
                "putObject"=>["date"=>"Thu, 10 Jun 2021 02:42:20 GMT","oss-requestheaders"=>["Content-Length"=>"7","Content-Type"=>"application/octet-stream"]]
            ]);
        }

        $result = $adapter->write("foo/bar.md", "content", new Config());

        $this->assertSame([
            "type"=>"file",
            "path"=>"foo/bar.md",
            "timestamp"=>$result["timestamp"],
            "size"=>7,
            "mimetype"=>"application/octet-stream",
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testUpdate($adapter,$client)
    {
        if($this->isUseMock()){
            $client->shouldReceive("putObject")
                ->andReturn(
                    ["date"=>"Thu, 10 Jun 2021 02:42:20 GMT","oss-requestheaders"=>["Content-Length"=>"6","Content-Type"=>"application/octet-stream"]]
                );
        }else{
            $adapter->write("foo/bar.md", "content", new Config());
        }

        $result = $adapter->update("foo/bar.md", "update", new Config());
        $this->assertSame([
            "type"=>"file",
            "path"=>"foo/bar.md",
            "timestamp"=>$result["timestamp"],
            "size"=>6,
            "mimetype"=>"application/octet-stream",
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testRename($adapter,$client)
    {
        if($this->isUseMock()){
            $client->allows([
                "copyObject"=>null,
                "deleteObject"=>null
            ]);
        }else{
            $adapter->write("foo/bar.md", "content", new Config());
        }

        $result = $adapter->rename("foo/bar.md", "foo/baz.md");
        $this->assertTrue($result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testCopy($adapter,$client)
    {
        if($this->isUseMock()){
            $client->allows([
                "copyObject"=>null
            ]);
        }else{
            $adapter->write("foo/bar.md", "content", new Config());
        }

        $result = $adapter->copy("foo/bar.md", "foo/baz.md");
        $this->assertTrue($result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testDelete($adapter,$client)
    {
        if($this->isUseMock()){
            $client->allows([
                "deleteObject"=>null
            ]);
        }else{
            $adapter->write("foo/bar.md", "content", new Config());
        }

        $result = $adapter->delete("foo/bar.md");
        $this->assertTrue($result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testDeleteDir($adapter, $client)
    {
        if($this->isUseMock()){
            $listObjects = \Mockery::mock("stdClass")->allows([
                "getObjectList"=>[new ObjectInfo(
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
                "getPrefixList"=>[],
                "getNextMarker"=>""
            ]);
            $client->allows([
                "listObjects"=>$listObjects,
                "deleteObjects"=>null
            ]);
        }else{
            $adapter->write("foo/bar.md", "content", new Config());
            $adapter->write("foo/baz.md", "content", new Config());
        }

        $result = $adapter->deleteDir("foo/");
        $this->assertTrue($result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testCreateDir($adapter,$client)
    {
        if($this->isUseMock()){
            $client->allows([
                "createObjectDir"=>null
            ]);
        }

        $result = $adapter->createDir("baz/",new Config());
        $this->assertSame([
            "type"=>"dir",
            "path"=>"baz/"
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testSetVisibility($adapter,$client)
    {
        if($this->isUseMock()){
            $client->allows([
                "putObjectAcl"=>null
            ]);
        }else{
            $adapter->write("foo/bar.md", "content", new Config());
        }

        $result = $adapter->setVisibility("foo/bar.md","public");
        $this->assertSame([
            "visibility" => "public"
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testHas($adapter,$client)
    {
        if($this->isUseMock()){
            $client->shouldReceive("doesObjectExist")
                ->andReturn(true,false)
                ->times(2);
        }else{
            $adapter->write("foo/bar.md", "content", new Config());
            $adapter->delete("foo/baz.md");
        }

        $this->assertTrue($adapter->has("foo/bar.md"));
        $this->assertFalse($adapter->has("foo/baz.md"));
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testRead($adapter,$client)
    {
        if($this->isUseMock()){
            $client->shouldReceive("getObject")
                ->andReturn("content");
        }else{
            $adapter->write("foo/bar.md", "content", new Config());
        }

        $result = $adapter->read("foo/bar.md");
        $this->assertSame([
            "type"=>"file",
            "path"=>"foo/bar.md",
            "contents"=>"content"
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testListContents($adapter,$client)
    {
        if($this->isUseMock()){
            $listObjects = \Mockery::mock("stdClass")->allows([
                "getObjectList"=>[new ObjectInfo(
                    "foo/bar.md",
                    "Thu, 10 Jun 2021 02:42:20 GMT",
                    "9A0364B9E99BB480DD25E1F0284C8555",
                    "application/octet-stream",
                    7,
                    "standard"
                )],
                "getPrefixList"=>[new PrefixInfo("foo/baz/")],
                "getNextMarker"=>""
            ]);
            $client->allows([
                "listObjects"=>$listObjects
            ]);
            $file = ["timestamp"=>strtotime("Thu, 10 Jun 2021 02:42:20 GMT")];
        }else{
            $adapter->deleteDir("foo/");
            $file = $adapter->write("foo/bar.md", "content", new Config());
            $adapter->createDir("foo/baz/",new Config());
        }

        $result = $adapter->listContents("foo/");
        $this->assertSame([
            [
                "type"=>"file",
                "path"=>"foo/bar.md",
                "size"=>7,
                "timestamp"=>$file["timestamp"]
            ],
            [
                "type"=>"dir",
                "path"=>"foo/baz/",
                "size"=>0,
                "timestamp"=>0
            ]
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetMetadata($adapter,$client)
    {
        if($this->isUseMock()){
            $client->shouldReceive("getObjectMeta")
                ->andReturn(["content-length"=>"7","last-modified"=>"Thu, 10 Jun 2021 02:42:20 GMT","content-type"=>"application/octet-stream"]);
            $file = ["timestamp"=>strtotime("Thu, 10 Jun 2021 02:42:20 GMT")];
        }else{
            $file = $adapter->write("foo/bar.md", "content", new Config());
        }

        $result = $adapter->getMetadata("foo/bar.md");
        $this->assertSame([
            "type"=>"file",
            "path"=>"foo/bar.md",
            "size"=>7,
            "timestamp"=>$file["timestamp"],
            "mimetype"=>"application/octet-stream"
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetSize($adapter,$client)
    {
        if($this->isUseMock()){
            $client->shouldReceive("getObjectMeta")
                ->andReturn(["content-length"=>"7","last-modified"=>"Thu, 10 Jun 2021 02:42:20 GMT","content-type"=>"application/octet-stream"]);
        }else{
            $adapter->write("foo/bar.md", "content", new Config());
        }

        $result = $adapter->getSize("foo/bar.md");
        $this->assertSame([
            "size"=>7,
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetMimetype($adapter,$client)
    {
        if($this->isUseMock()){
            $client->shouldReceive("getObjectMeta")
                ->andReturn(["content-length"=>"7","last-modified"=>"Thu, 10 Jun 2021 02:42:20 GMT","content-type"=>"application/octet-stream"]);
        }else{
            $adapter->write("foo/bar.md", "content", new Config());
        }

        $result = $adapter->getMimetype("foo/bar.md");
        $this->assertSame([
            "mimetype"=>"application/octet-stream",
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetTimestamp($adapter,$client)
    {
        if($this->isUseMock()){
            $client->shouldReceive("getObjectMeta")
                ->andReturn(["content-length"=>"7","last-modified"=>"Thu, 10 Jun 2021 02:42:20 GMT","content-type"=>"application/octet-stream"]);
            $file = ["timestamp"=>strtotime("Thu, 10 Jun 2021 02:42:20 GMT")];
        }else{
            $file = $adapter->write("foo/bar.md", "content", new Config());
        }

        $result = $adapter->getTimestamp("foo/bar.md");
        $this->assertSame([
            "timestamp"=>$file["timestamp"],
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetVisibility($adapter,$client)
    {
        if($this->isUseMock()){
            $client->shouldReceive("getObjectAcl")
                ->andReturn("public-read");
        }else{
            $adapter->write("foo/bar.md", "content", new Config(["visibility"=>"public"]));
        }

        $result = $adapter->getVisibility("foo/bar.md");
        $this->assertSame([
            "visibility"=>"public",
        ],$result);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     * @param OssClient|MockInterface $client
     */
    public function testGetClient($adapter,$client)
    {
        $adapterClient = $adapter->getClient();

        $this->assertSame($client,$adapterClient);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     */
    public function testGetBucket($adapter)
    {
        $bucket = $adapter->getBucket();

        $this->assertSame(getenv("ALIYUN_OSS_BUCKET"),$bucket);
    }

    /**
     * @dataProvider aliyunProvider
     *
     * @param AliyunOssAdapter $adapter
     */
    public function testOptions($adapter)
    {
        $options =[
            "Content-Type"=>"application/octet-stream"
        ];
        $adapter->setOptions($options);

        $this->assertSame($options,$adapter->getOptions());
    }
}

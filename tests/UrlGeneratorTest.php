<?php

namespace AlphaSnow\Flysystem\Aliyun\Tests;

use AlphaSnow\Flysystem\Aliyun\UrlGenerator;
use PHPUnit\Framework\TestCase;

class UrlGeneratorTest extends TestCase
{
    public function testGetDomain()
    {
        $urlGen = new UrlGenerator([
            "access_key_id" => "access_id",
            "access_key_secret" => "access_secret",
            "endpoint" => "endpoint.com",
            "bucket" => "bucket",
            "prefix" => "prefix",
            "domain" => "storage.domain.com",
            "use_ssl" => true,
        ]);

        $got = $urlGen->fullUrl("uploads/file.md");
        $want = "https://storage.domain.com/uploads/file.md";
        $this->assertSame($want, $got);
    }

    public function testCorrectDomain()
    {
        $urlGen = new UrlGenerator([
            "access_key_id" => "access_id",
            "access_key_secret" => "access_secret",
            "endpoint" => "endpoint.com",
            "bucket" => "bucket",
            "prefix" => "prefix",
            "domain" => "storage.domain.com",
            "use_ssl" => true,
            "internal" => "oss-cn-shanghai-internal.aliyuncs.com"
        ]);

        $got = $urlGen->correctDomain("https://bucket.oss-cn-shanghai-internal.aliyuncs.com/uploads/file.md");
        $want = "https://storage.domain.com/uploads/file.md";
        $this->assertSame($want, $got);
    }
}

<?php

namespace AlphaSnow\Flysystem\AliyunOss;

interface AliyunOssFactoryInterface
{
    /**
     * @param string $accessId
     * @param string $accessKey
     * @param string $endpoint
     * @param string $bucket
     * @param string|null $prefix
     * @param array $options
     * @return static
     * @throws \OSS\Core\OssException
     */
    public static function create($accessId, $accessKey, $endpoint, $bucket, $prefix = null, array $options = []);
}

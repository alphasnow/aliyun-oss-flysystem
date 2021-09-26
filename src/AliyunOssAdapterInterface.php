<?php

namespace AlphaSnow\Flysystem\AliyunOss;

use OSS\Core\OssException;
use OSS\OssClient;

interface AliyunOssAdapterInterface
{
    /**
     * @return OssClient
     */
    public function getClient();

    /**
     * @return string
     */
    public function getBucket();

    /**
     * @return array
     */
    public function getOptions();

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options);

    /**
     * @return OssException
     */
    public function getException();
}

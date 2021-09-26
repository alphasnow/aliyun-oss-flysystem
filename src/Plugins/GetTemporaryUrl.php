<?php

namespace AlphaSnow\Flysystem\AliyunOss\Plugins;

use OSS\OssClient;

class GetTemporaryUrl extends AliyunOssAbstractPlugin
{
    /**
     * @return string
     */
    public function getMethod()
    {
        return 'getTemporaryUrl';
    }

    /**
     * @param string $path
     * @param int $timeout
     * @param string $method
     * @return string
     * @throws \OSS\Core\OssException
     */
    public function handle($path, $timeout = 3600, $method = OssClient::OSS_HTTP_GET)
    {
        return $this->adapter->getClient()
            ->signUrl(
                $this->adapter->getBucket(),
                $this->adapter->applyPathPrefix($path),
                $timeout,
                $method,
                $this->adapter->getOptions()
            );
    }
}

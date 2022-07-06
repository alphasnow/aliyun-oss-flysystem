<?php

namespace AlphaSnow\Flysystem\AliyunOss\Plugins;

use OSS\OssClient;

class SignUrl extends AliyunOssAbstractPlugin
{
    /**
     * @return string
     */
    public function getMethod()
    {
        return "signUrl";
    }

    /**
     * @param string $path
     * @param int $timeout
     * @param string $method
     * @param array $config
     * @return string
     * @throws \OSS\Core\OssException
     */
    public function handle($path, $timeout = 3600, $method = OssClient::OSS_HTTP_GET, $config = [])
    {
        return $this->adapter->getClient()
            ->signUrl(
                $this->adapter->getBucket(),
                $this->adapter->applyPathPrefix($path),
                $timeout,
                $method,
                $this->adapter->getOptionsFromConfig($this->prepareConfig($config))
            );
    }
}

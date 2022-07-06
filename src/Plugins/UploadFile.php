<?php

namespace AlphaSnow\Flysystem\AliyunOss\Plugins;

class UploadFile extends AliyunOssAbstractPlugin
{
    /**
     * @return string
     */
    public function getMethod()
    {
        return "uploadFile";
    }

    /**
     * @param string $path
     * @param string $file
     * @param array $config
     */
    public function handle($path, $file, $config = [])
    {
        return $this->adapter->getClient()
            ->uploadFile(
                $this->adapter->getBucket(),
                $this->adapter->applyPathPrefix($path),
                $file,
                $this->adapter->getOptionsFromConfig($this->prepareConfig($config))
            );
    }
}

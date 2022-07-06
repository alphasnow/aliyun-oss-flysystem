<?php

namespace AlphaSnow\Flysystem\AliyunOss\Plugins;

class AppendFile extends AliyunOssAbstractPlugin
{
    /**
     * @return string
     */
    public function getMethod()
    {
        return "appendFile";
    }

    /**
     * @param string $path
     * @param string $file
     * @param int $position
     * @param array $config
     * @return int
     * @throws \OSS\Core\OssException
     */
    public function handle($path, $file, $position = 0, $config = [])
    {
        return $this->adapter->getClient()
            ->appendFile(
                $this->adapter->getBucket(),
                $this->adapter->applyPathPrefix($path),
                $file,
                $position,
                $this->adapter->getOptionsFromConfig($this->prepareConfig($config))
            );
    }
}

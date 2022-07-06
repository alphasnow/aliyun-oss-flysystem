<?php

namespace AlphaSnow\Flysystem\AliyunOss\Plugins;

class AppendObject extends AliyunOssAbstractPlugin
{
    /**
     * @return string
     */
    public function getMethod()
    {
        return "appendObject";
    }

    /**
     * @param string $path
     * @param string $content
     * @param int $position
     * @param array $config
     * @return int
     * @throws \OSS\Core\OssException
     */
    public function handle($path, $content, $position = 0, $config = [])
    {
        return $this->adapter->getClient()
            ->appendObject(
                $this->adapter->getBucket(),
                $this->adapter->applyPathPrefix($path),
                $content,
                $position,
                $this->adapter->getOptionsFromConfig($this->prepareConfig($config))
            );
    }
}

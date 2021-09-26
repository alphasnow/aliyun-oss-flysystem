<?php

namespace AlphaSnow\Flysystem\AliyunOss\Plugins;

class AppendContent extends AliyunOssAbstractPlugin
{
    /**
     * @return string
     */
    public function getMethod()
    {
        return 'appendContent';
    }

    /**
     * @param string $path
     * @param mixed $content
     * @param int $position
     * @return int
     * @throws \OSS\Core\OssException
     */
    public function handle($path, $content, $position = 0)
    {
        return $this->adapter->getClient()
            ->appendObject(
                $this->adapter->getBucket(),
                $this->adapter->applyPathPrefix($path),
                $content,
                $position,
                $this->adapter->getOptions()
            );
    }
}

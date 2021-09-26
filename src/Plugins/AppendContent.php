<?php

namespace AlphaSnow\Flysystem\AliyunOss\Plugins;

use AlphaSnow\Flysystem\AliyunOss\AliyunOssAdapterInterface;
use League\Flysystem\Plugin\AbstractPlugin;
use League\Flysystem\Adapter\AbstractAdapter;

class AppendContent extends AbstractPlugin
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
        /**
         * @var $adapter AbstractAdapter|AliyunOssAdapterInterface
         */
        $adapter = $this->filesystem->getAdapter();

        return $adapter->getClient()
            ->appendObject(
                $adapter->getBucket(),
                $adapter->applyPathPrefix($path),
                $content,
                $position,
                $adapter->getOptions()
            );
    }
}

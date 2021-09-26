<?php

namespace AlphaSnow\Flysystem\AliyunOss\Plugins;

use AlphaSnow\Flysystem\AliyunOss\AliyunOssAdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Plugin\AbstractPlugin;
use League\Flysystem\Adapter\AbstractAdapter;

class AppendContent extends AbstractPlugin
{
    /**
     * @var AliyunOssAdapterInterface | AbstractAdapter
     */
    protected $adapter;

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        parent::setFilesystem($filesystem);

        if ($filesystem instanceof Filesystem) {
            $adapter = $filesystem->getAdapter();
            if ($adapter instanceof AliyunOssAdapterInterface && $adapter instanceof AbstractAdapter) {
                $this->adapter = $adapter;
            }
        }
    }
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

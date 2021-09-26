<?php

namespace AlphaSnow\Flysystem\AliyunOss\Plugins;

use AlphaSnow\Flysystem\AliyunOss\AliyunOssAdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Plugin\AbstractPlugin;
use League\Flysystem\Adapter\AbstractAdapter;

abstract class AliyunOssAbstractPlugin extends AbstractPlugin
{
    /**
     * @var AliyunOssAdapterInterface | AbstractAdapter
     */
    protected $adapter;

    /**
     * @param FilesystemInterface $filesystem
     */
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
}

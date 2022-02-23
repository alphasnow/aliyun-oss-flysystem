<?php

namespace AlphaSnow\Flysystem\Aliyun\Plugins;

use AlphaSnow\Flysystem\Aliyun\AliyunOssAdapterInterface;
use League\Flysystem\Config;
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

    /**
     * @param array $config
     * @return Config
     */
    protected function prepareConfig(array $config)
    {
        $config = new Config($config);
        if ($this->filesystem instanceof Filesystem) {
            $config->setFallback($this->filesystem->getConfig());
        }
        return $config;
    }
}

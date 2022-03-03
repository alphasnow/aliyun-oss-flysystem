<?php

namespace AlphaSnow\Flysystem\Aliyun;

use League\Flysystem\Config;
use OSS\OssClient;

class OssOptions
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var VisibilityConverter
     */
    protected $visibility;

    /**
     * @param array $options
     * @param VisibilityConverter $visibility
     */
    public function __construct(array $options, VisibilityConverter $visibility)
    {
        $this->options = $options;
        $this->visibility = $visibility;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return OssOptions
     */
    public function setOptions(array $options): OssOptions
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param Config $config
     * @return array
     */
    public function mergeConfig(Config $config): array
    {
        $options = $config->get("options", []);

        if ($headers = $config->get("headers")) {
            $options[OssClient::OSS_HEADERS] = isset($options[OssClient::OSS_HEADERS]) ? array_merge($options[OssClient::OSS_HEADERS], $headers) : $headers;
        }

        if ($visibility = $config->get("visibility")) {
            $options[OssClient::OSS_HEADERS][OssClient::OSS_OBJECT_ACL] = $this->visibility->visibilityToAcl($visibility);
        }

        return array_merge_recursive($this->options, $options);
    }
}

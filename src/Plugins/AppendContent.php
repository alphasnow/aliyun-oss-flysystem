<?php

namespace AlphaSnow\Flysystem\AliyunOss\Plugins;

/**
 * @deprecated use AppendObject instead
 */
class AppendContent extends AppendObject
{
    /**
     * @return string
     */
    public function getMethod()
    {
        return "appendContent";
    }
}

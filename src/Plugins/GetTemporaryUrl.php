<?php

namespace AlphaSnow\Flysystem\AliyunOss\Plugins;

/**
 * @deprecated use SignUrl instead
 */
class GetTemporaryUrl extends SignUrl
{
    /**
     * @return string
     */
    public function getMethod()
    {
        return "getTemporaryUrl";
    }
}

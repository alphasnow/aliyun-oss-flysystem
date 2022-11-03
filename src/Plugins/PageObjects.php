<?php

namespace AlphaSnow\Flysystem\AliyunOss\Plugins;

use OSS\OssClient;

/**
 * PageObjects
 * example:
 * $dataPage1 = $flysystem->pageObjects("/uploads/", 10);
 * $dataPage2 = $flysystem->pageObjects("/uploads/", 10, end($dataPage1)["path"]);
 */
class PageObjects extends AliyunOssAbstractPlugin
{
    /**
     * @return string
     */
    public function getMethod()
    {
        return "pageObjects";
    }

    /**
     * @param string $rootPath
     * @param string $pageSize
     * @param string $startPath The path of the last data in the previous page
     * @return array
     * @throws \OSS\Core\OssException
     */
    public function handle($rootPath = "/", $pageSize = 10, $startPath = "", $config = [])
    {
        $prefix = $this->adapter->applyPathPrefix(rtrim($rootPath, "/")."/");
        $startAfter = empty($startPath) ? "" : $this->adapter->applyPathPrefix($startPath);
        $maxKeys = empty($startPath) ? intval($pageSize) + 1 : intval($pageSize);
        $options = array_merge(
            $this->adapter->getOptionsFromConfig($this->prepareConfig($config)),
            [
                OssClient::OSS_PREFIX => $prefix,
                OssClient::OSS_DELIMITER => "/",
                OssClient::OSS_MAX_KEYS => $maxKeys,
                OssClient::OSS_START_AFTER => $startAfter,
            ]
        );

        return $this->listObjectsV2($options);
    }

    protected function listObjectsV2($options)
    {
        $listObjects = $this->adapter->getClient()->listObjectsV2($this->adapter->getBucket(), $options);
        $prefixList = $listObjects->getPrefixList();
        foreach ($prefixList as $prefixInfo) {
            $prefixPath = $this->adapter->removePathPrefix($prefixInfo->getPrefix());
            $dirname = dirname($prefixPath) == "." ? "" : dirname($prefixPath);
            $result[] = [
                "type" => "dir",
                "path" => $prefixPath,
                "size" => 0,
                "mimetype" => "",
                "timestamp" => 0,
                "dirname" => $dirname
            ];
        }
        $objectList = $listObjects->getObjectList();
        foreach ($objectList as $objectInfo) {
            // Exclude the root folder
            if ($objectInfo->getKey() == $options[OssClient::OSS_PREFIX]) {
                continue;
            }
            $objectPath = $this->adapter->removePathPrefix($objectInfo->getKey());
            $objectLastModified = strtotime($objectInfo->getLastModified());
            $dirname = dirname($objectPath) == "." ? "" : dirname($objectPath);
            if (substr($objectPath, -1, 1) === "/") {
                $result[] = [
                    "type" => "dir",
                    "path" => $objectPath,
                    "size" => 0,
                    "mimetype" => "",
                    "timestamp" => $objectLastModified,
                    "dirname" => $dirname
                ];
                continue;
            }
            $result[] = [
                "type" => "file",
                "path" => $objectPath,
                "size" => $objectInfo->getSize(),
                "mimetype" => "",
                "timestamp" => $objectLastModified,
                "dirname" => $dirname
            ];
        }
        return $result;
    }
}

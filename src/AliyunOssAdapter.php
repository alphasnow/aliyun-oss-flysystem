<?php

namespace AlphaSnow\Flysystem\AliyunOss;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\CanOverwriteFiles;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Util;
use League\Flysystem\Config;
use OSS\Core\OssException;
use OSS\OssClient;

class AliyunOssAdapter extends AbstractAdapter implements CanOverwriteFiles
{
    /**
     * @var OssClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var OssException|null
     */
    protected $exception;

    /**
     * @param OssClient $client
     * @param string $bucket
     * @param string $prefix
     * @param array $options
     */
    public function __construct(OssClient $client, $bucket, $prefix = null, array $options = [])
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->setPathPrefix($prefix);
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        $object = $this->applyPathPrefix($path);
        $options = $this->getOptionsFromConfig($config);

        if (!isset($options[OssClient::OSS_LENGTH])) {
            $options[OssClient::OSS_LENGTH] = Util::contentSize($contents);
        }
        if (!isset($options[OssClient::OSS_CONTENT_TYPE])) {
            $options[OssClient::OSS_CONTENT_TYPE] = Util::guessMimeType($path, $contents);
        }

        try {
            $this->client->putObject($this->bucket, $object, $contents, $options);
        } catch (OssException $exception) {
            $this->exception = $exception;
            return false;
        }

        return [
            "type" => "file",
            "path" => $path,
            "size" => $options[OssClient::OSS_LENGTH],
            "mimetype" => $options[OssClient::OSS_CONTENT_TYPE]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        $object = $this->applyPathPrefix($path);
        $options = $this->getOptionsFromConfig($config);

        if (!isset($options[OssClient::OSS_LENGTH])) {
            $options[OssClient::OSS_LENGTH] = Util::getStreamSize($resource);
        }
        if (!isset($options[OssClient::OSS_CONTENT_TYPE])) {
            $options[OssClient::OSS_CONTENT_TYPE] = Util::guessMimeType($path, $resource);
        }

        try {
            $this->client->uploadStream($this->bucket, $object, $resource, $options);
        } catch (OssException $exception) {
            $this->exception = $exception;
            return false;
        }

        return [
            "type" => "file",
            "path" => $path,
            "size" => $options[OssClient::OSS_LENGTH],
            "mimetype" => $options[OssClient::OSS_CONTENT_TYPE]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        return $this->copy($path, $newpath) && $this->delete($path);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        $object = $this->applyPathPrefix($path);
        $newObject = $this->applyPathPrefix($newpath);

        try {
            $this->client->copyObject($this->bucket, $object, $this->bucket, $newObject, $this->options);
        } catch (OssException $exception) {
            $this->exception = $exception;
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $object = $this->applyPathPrefix($path);

        try {
            $this->client->deleteObject($this->bucket, $object, $this->options);
        } catch (OssException $exception) {
            $this->exception = $exception;
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        try {
            $contents = $this->listContents($dirname, true);
            $files = [];
            foreach ($contents as $i => $content) {
                if ($content["type"] === "dir") {
                    $this->deleteDir($content["path"]);
                    continue;
                }
                $files[] = $this->applyPathPrefix($content["path"]);
                if ($i && $i % 1000 == 0) {
                    $this->client->deleteObjects($this->bucket, $files, $this->options);
                    $files = [];
                }
            }
            !empty($files) && $this->client->deleteObjects($this->bucket, $files, $this->options);
            $this->client->deleteObject($this->bucket, $this->applyPathPrefix(rtrim($dirname, "/") . "/"), $this->options);
        } catch (OssException $exception) {
            $this->exception = $exception;
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        $object = $this->applyPathPrefix($dirname);
        $options = $this->getOptionsFromConfig($config);

        try {
            $this->client->createObjectDir($this->bucket, $object, $options);
        } catch (OssException $exception) {
            $this->exception = $exception;
            return false;
        }

        return [
            "type" => "dir",
            "path" => $dirname
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        $object = $this->applyPathPrefix($path);

        try {
            $this->client->putObjectAcl($this->bucket, $object, $this->visibilityToAcl($visibility), $this->options);
        } catch (OssException $exception) {
            $this->exception = $exception;
            return false;
        }

        return [
            "visibility" => $visibility
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        $object = $this->applyPathPrefix($path);

        return $this->client->doesObjectExist($this->bucket, $object, $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        $object = $this->applyPathPrefix($path);

        try {
            $contents = $this->client->getObject($this->bucket, $object, $this->options);
        } catch (OssException $exception) {
            $this->exception = $exception;
            return false;
        }

        return [
            "type" => "file",
            "path" => $path,
            "contents" => $contents
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        $object = $this->applyPathPrefix($path);

        $stream = fopen("php://temp", "w+b");
        $options = array_merge($this->options, [OssClient::OSS_FILE_DOWNLOAD => $stream]);
        try {
            $this->client->getObject($this->bucket, $object, $options);
        } catch (OssException $exception) {
            fclose($stream);
            $this->exception = $exception;
            return false;
        }

        rewind($stream);
        return [
            "type" => "file",
            "path" => $path,
            "stream" => $stream
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = "", $recursive = false)
    {
        $directory = $this->applyPathPrefix(rtrim($directory, "/") . "/");
        $nextMarker = "";

        $result = [];
        while (true) {
            $options = array_merge(
                $this->options,
                [
                    OssClient::OSS_PREFIX => $directory,
                    OssClient::OSS_MARKER => $nextMarker
                ]
            );
            $listObjectInfo = $this->client->listObjects($this->bucket, $options);
            $nextMarker = $listObjectInfo->getNextMarker();

            $prefixList = $listObjectInfo->getPrefixList();
            foreach ($prefixList as $prefixInfo) {
                $nextDirectory = rtrim($prefixInfo->getPrefix(), "/") . "/";
                if ($nextDirectory == $directory) {
                    continue;
                }

                $nextPath = $this->removePathPrefix($nextDirectory);
                $result[] = [
                    "type" => "dir",
                    "path" => $nextPath,
                    "size" => 0,
                    "mimetype" => "",
                    "timestamp" => 0,
                    "dirname" => dirname($nextPath) == "." ? "" : $nextPath
                ];

                if ($recursive) {
                    $nextResult = $this->listContents($nextPath, $recursive);
                    $result = array_merge($result, $nextResult);
                }
            }

            $objectList = $listObjectInfo->getObjectList();
            if (!empty($objectList)) {
                foreach ($objectList as $objectInfo) {
                    if (substr($objectInfo->getKey(), -1, 1) == "/") {
                        continue;
                    }

                    $result[] = [
                        "type" => "file",
                        "path" => $this->removePathPrefix($objectInfo->getKey()),
                        "size" => $objectInfo->getSize(),
                        "mimetype" => "",
                        "timestamp" => strtotime($objectInfo->getLastModified()),
                        "dirname" => $this->removePathPrefix($directory)
                    ];
                }
            }

            if ($listObjectInfo->getIsTruncated() === "false") {
                break;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $object = $this->applyPathPrefix($path);

        try {
            $result = $this->client->getObjectMeta($this->bucket, $object, $this->options);
        } catch (OssException $exception) {
            $this->exception = $exception;
            return false;
        }

        return [
            "type" => "file",
            "path" => $path,
            "size" => isset($result["content-length"]) ? intval($result["content-length"]) : 0,
            "mimetype" => isset($result["content-type"]) ? $result["content-type"] : "",
            "timestamp" => isset($result["last-modified"]) ? strtotime($result["last-modified"]) : 0
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return $this->getMetadata($path) && isset($this->getMetadata($path)["size"]) ? ["size" => $this->getMetadata($path)["size"]] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path) && isset($this->getMetadata($path)["mimetype"]) ? ["mimetype" => $this->getMetadata($path)["mimetype"]] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path) && isset($this->getMetadata($path)["timestamp"]) ? ["timestamp" => $this->getMetadata($path)["timestamp"]] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        $object = $this->applyPathPrefix($path);

        try {
            $acl = $this->client->getObjectAcl($this->bucket, $object, $this->options);
        } catch (OssException $exception) {
            $this->exception = $exception;
            return false;
        }

        return [
            "visibility" => $this->aclToVisibility($acl)
        ];
    }

    /**
     * @param Config $config
     * @return array
     */
    protected function getOptionsFromConfig(Config $config)
    {
        $options = $config->get("options", []);

        if ($headers = $config->get("headers")) {
            $options[OssClient::OSS_HEADERS] = isset($options[OssClient::OSS_HEADERS]) ? array_merge($options[OssClient::OSS_HEADERS], $headers) : $headers;
        }

        if ($visibility = $config->get("visibility")) {
            $options[OssClient::OSS_HEADERS][OssClient::OSS_OBJECT_ACL] = $this->visibilityToAcl($visibility);
        }

        return array_merge_recursive($this->options, $options);
    }

    /**
     * @param string $visibility
     * @return string
     */
    protected function visibilityToAcl($visibility)
    {
        return $visibility === AdapterInterface::VISIBILITY_PUBLIC ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;
    }

    /**
     * @param string $acl
     * @return string
     */
    protected function aclToVisibility($acl)
    {
        return $acl === OssClient::OSS_ACL_TYPE_PRIVATE ? AdapterInterface::VISIBILITY_PRIVATE : AdapterInterface::VISIBILITY_PUBLIC;
    }

    /**
     * @return OssClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return OssException|null
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param string $accessId
     * @param string $accessKey
     * @param string $endpoint
     * @param string $bucket
     * @param string|null $prefix
     * @param array $options
     * @return static
     * @throws \OSS\Core\OssException
     */
    public static function create($accessId, $accessKey, $endpoint, $bucket, $prefix = null, array $options = [])
    {
        $isCName = isset($options["is_cname"]) ? $options["is_cname"] : false;
        $securityToken = isset($options["security_token"]) ? $options["security_token"] : null;
        $requestProxy = isset($options["request_proxy"]) ? $options["request_proxy"] : null;
        return new static(new OssClient($accessId, $accessKey, $endpoint, $isCName, $securityToken, $requestProxy), $bucket, $prefix, $options);
    }
}

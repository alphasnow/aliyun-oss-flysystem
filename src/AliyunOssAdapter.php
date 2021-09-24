<?php

namespace AlphaSnow\Flysystem\AliyunOss;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\CanOverwriteFiles;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use OSS\OssClient;

/**
 * Here is some example file meta data
 * ["type"=>"file","path"=>"/foo/bar/qux.md","timestamp"=>1623289297,"size"=>1024]
 * ["type"=>"dir","path"=>"/foo/bar/","timestamp"=>0,"size"=>0]
 *
 * @package AlphaSnow\Flysystem\AliyunOss
 */
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
    protected $options = [];

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

        $result = $this->client->putObject($this->bucket, $object, $contents, $options);
        !isset($result["date"]) && $result["date"] = date("Y-m-d H:i:s");
        !isset($result["oss-requestheaders"]["Content-Length"]) && $result["oss-requestheaders"]["Content-Length"] = strlen($contents);
        !isset($result["oss-requestheaders"]["Content-Type"]) && $result["oss-requestheaders"]["Content-Type"] = "";

        return [
            "type" => "file",
            "path" => $path,
            "timestamp" => strtotime($result["date"]),
            "size" => intval($result["oss-requestheaders"]["Content-Length"]),
            "mimetype" => $result["oss-requestheaders"]["Content-Type"]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        $object = $this->applyPathPrefix($path);
        $options = $this->getOptionsFromConfig($config);

        $result = $this->client->uploadStream($this->bucket, $object, $resource, $options);
        !isset($result["date"]) && $result["date"] = date("Y-m-d H:i:s");
        !isset($result["oss-requestheaders"]["Content-Length"]) && $result["oss-requestheaders"]["Content-Length"] = fstat($resource)['size'];
        !isset($result["oss-requestheaders"]["Content-Type"]) && $result["oss-requestheaders"]["Content-Type"] = "";

        return [
            "type" => "file",
            "path" => $path,
            "timestamp" => strtotime($result["date"]),
            "size" => intval($result["oss-requestheaders"]["Content-Length"]),
            "mimetype" => $result["oss-requestheaders"]["Content-Type"]
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
        $newobject = $this->applyPathPrefix($newpath);

        $this->client->copyObject($this->bucket, $object, $this->bucket, $newobject, $this->options);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $object = $this->applyPathPrefix($path);

        $this->client->deleteObject($this->bucket, $object, $this->options);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        $list = $this->listContents($dirname, true);

        $objects = [];
        foreach ($list as $val) {
            if ($val["type"] === "dir") {
                $path = rtrim($val["path"], "/") . "/";
            } else {
                $path = $val["path"];
            }

            $objects[] = $this->applyPathPrefix($path);
        }

        $this->client->deleteObjects($this->bucket, $objects, $this->options);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        $object = $this->applyPathPrefix($dirname);
        $options = $this->getOptionsFromConfig($config);

        $this->client->createObjectDir($this->bucket, $object, $options);
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
        $acl = $this->visibilityToAcl($visibility);

        $this->client->putObjectAcl($this->bucket, $object, $acl, $this->options);
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

        $contents = $this->client->getObject($this->bucket, $object, $this->options);
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

        $stream = fopen('php://temp', 'w+b');
        $options = array(
            OssClient::OSS_FILE_DOWNLOAD => $stream,
        );
        $this->client->getObject($this->bucket, $object, $options);
        rewind($stream);

        return [
            'type' => 'file',
            'path' => $path,
            'stream' => $stream
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = "", $recursive = false)
    {
        $directory = $this->applyPathPrefix(rtrim($directory, "/")."/");

        $options = array_merge([
            "delimiter" => "/",
            "max-keys" => 1000,
            "marker" => "",
        ], $this->options, [
            "prefix" => $directory
        ]);

        $result = [];

        while (true) {
            $listObjectInfo = $this->client->listObjects($this->bucket, $options);

            $objectList = $listObjectInfo->getObjectList();
            $prefixList = $listObjectInfo->getPrefixList();
            $nextMarker = $listObjectInfo->getNextMarker();

            if (!empty($objectList)) {
                foreach ($objectList as $objectInfo) {
                    $result[] = [
                        "type" => "file",
                        "path" => $this->removePathPrefix($objectInfo->getKey()),
                        "size" => $objectInfo->getSize(),
                        "timestamp" => strtotime($objectInfo->getLastModified())
                    ];
                }
            }

            if (!empty($prefixList)) {
                foreach ($prefixList as $prefixInfo) {
                    $nextDirectory = rtrim($prefixInfo->getPrefix(), "/")."/";
                    if ($nextDirectory == $directory) {
                        continue;
                    }
                    $result[] = [
                        "type" => "dir",
                        "path" => $this->removePathPrefix(rtrim($prefixInfo->getPrefix(), "/")."/"),
                        "size" => 0,
                        "timestamp" => 0
                    ];
                }
            }

            if ($recursive) {
                foreach ($prefixList as $prefixInfo) {
                    $nextDirectory = rtrim($prefixInfo->getPrefix(), "/")."/";
                    if ($nextDirectory == $directory) {
                        continue;
                    }
                    $nextResult = $this->listContents($nextDirectory, $recursive);
                    $result = array_merge($result, $nextResult);
                }
            }

            if ($nextMarker === "") {
                break;
            }
            $options["marker"] = $nextMarker;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $object = $this->applyPathPrefix($path);

        $result = $this->client->getObjectMeta($this->bucket, $object, $this->options);
        return [
            "type" => "file",
            "path" => $path,
            "size" => intval($result["content-length"]),
            "timestamp" => strtotime($result["last-modified"]),
            "mimetype" => $result["content-type"],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return isset($this->getMetadata($path)["size"]) ? ["size" => $this->getMetadata($path)["size"]] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return isset($this->getMetadata($path)["mimetype"]) ? ["mimetype" => $this->getMetadata($path)["mimetype"]] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return isset($this->getMetadata($path)["timestamp"]) ? ["timestamp" => $this->getMetadata($path)["timestamp"]] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        $object = $this->applyPathPrefix($path);

        $acl = $this->client->getObjectAcl($this->bucket, $object, $this->options);
        $visibility = $this->aclToVisibility($acl);
        return [
            "visibility" => $visibility
        ];
    }

    /**
     * @param Config $config
     * @return array
     */
    protected function getOptionsFromConfig(Config $config)
    {
        $options = $config->get('options', []);

        if ($headers = $config->get('headers')) {
            $options[OssClient::OSS_HEADERS] = $headers;
        }

        if ($visibility = $config->get("visibility")) {
            $options[OssClient::OSS_HEADERS][OssClient::OSS_OBJECT_ACL] = $this->visibilityToAcl($visibility);
        }

        return array_merge($this->options, $options);
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
        $isCName = isset($options['is_cname']) ? $options['is_cname'] : false;
        $securityToken = isset($options['security_token']) ? $options['security_token'] : null;
        $requestProxy = isset($options['request_proxy']) ? $options['request_proxy'] : null;
        return new static(new OssClient($accessId, $accessKey, $endpoint, $isCName, $securityToken, $requestProxy),$bucket,$prefix,$options);
    }
}

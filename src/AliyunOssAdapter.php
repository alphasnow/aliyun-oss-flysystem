<?php

namespace AlphaSnow\Flysystem\AliyunOss;

use League\Flysystem\Adapter\CanOverwriteFiles;
use OSS\OssClient;
use League\Flysystem\Util;
use League\Flysystem\Config;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\AdapterInterface;

/**
 * @package AlphaSnow\Flysystem\AliyunOss
 */
class AliyunOssAdapter extends AbstractAdapter implements CanOverwriteFiles
{
    use NotSupportingVisibilityTrait;
    use StreamedTrait;

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
     * @var array
     */
    protected static $metaMap = [
        'CacheControl' => 'Cache-Control',
        'Expires' => 'Expires',
        'ServerSideEncryption' => 'x-oss-server-side-encryption',
        'Metadata' => 'x-oss-metadata-directive',
        'ACL' => 'x-oss-object-acl',
        'ContentType' => 'Content-Type',
        'ContentDisposition' => 'Content-Disposition',
        'ContentLanguage' => 'response-content-language',
        'ContentEncoding' => 'Content-Encoding',
    ];

    /**
     * @var null|array|\OSS\Http\ResponseCore
     */
    protected $result;

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

        $this->result = $this->client->putObject($this->bucket, $object, $contents, $options);
        return [
            'type' => 'file',
            'path' => $path
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

        $this->result = $this->client->copyObject($this->bucket, $object, $this->bucket, $newobject, $this->options);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $object = $this->applyPathPrefix($path);

        $this->result = $this->client->deleteObject($this->bucket, $object, $this->options);
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
            if ($val['type'] === 'dir') {
                $path = rtrim($val['path'], '\\/') . '/';
            } else {
                $path = $val['path'];
            }

            $objects[] = $this->applyPathPrefix($path);
        }

        $this->result = $this->client->deleteObjects($this->bucket, $objects, $this->options);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        $object = $this->applyPathPrefix($dirname);
        $options = $this->getOptionsFromConfig($config);

        $this->result = $this->client->createObjectDir($this->bucket, $object, $options);
        return [
            'type' => 'dir',
            'path' => $dirname
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        $object = $this->applyPathPrefix($path);
        $acl = $this->visibilityToAcl($visibility);

        $this->result = $this->client->putObjectAcl($this->bucket, $object, $acl, $this->options);
        return [
            'path' => $path,
            'visibility' => $visibility
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
            'path' => $path,
            'content' => $contents
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        $directory = $this->applyPathPrefix(rtrim($directory, '\\/') . '/');

        $options = array_merge([
            'delimiter' => '/',
            'max-keys' => 1000,
            'marker' => '',
        ], $this->options, [
            'prefix' => $directory
        ]);

        $listObjectInfo = $this->client->listObjects($this->bucket, $options);

        $objectList = $listObjectInfo->getObjectList();
        $prefixList = $listObjectInfo->getPrefixList();

        $result = [];
        foreach ($objectList as $objectInfo) {
            if ($objectInfo->getSize() === 0 && $directory === $objectInfo->getKey()) {
                $result[] = [
                    'type' => 'dir',
                    'path' => $this->removePathPrefix(rtrim($objectInfo->getKey(), '\\/')),
                    'timestamp' => strtotime($objectInfo->getLastModified()),
                ];
                continue;
            }

            $result[] = [
                'type' => 'file',
                'path' => $this->removePathPrefix($objectInfo->getKey()),
                'timestamp' => strtotime($objectInfo->getLastModified()),
                'size' => $objectInfo->getSize(),
            ];
        }

        foreach ($prefixList as $prefixInfo) {
            if ($recursive) {
                $next = $this->listContents($this->removePathPrefix($prefixInfo->getPrefix()), $recursive);
                $result = array_merge($result, $next);
            } else {
                $result[] = [
                    'type' => 'dir',
                    'path' => $this->removePathPrefix(rtrim($prefixInfo->getPrefix(), '\\/')),
                    'timestamp' => 0,
                ];
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

        $result = $this->client->getObjectMeta($this->bucket, $object, $this->options);

        return [
            'type' => 'file',
            'dirname' => Util::dirname($path),
            'path' => $path,
            'timestamp' => strtotime($result['last-modified']),
            'mimetype' => $result['content-type'],
            'size' => $result['content-length'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
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
            'visibility' => $visibility
        ];
    }

    /**
     * @param Config $config
     * @return array
     */
    protected function getOptionsFromConfig(Config $config)
    {
        $options = [];

        if ($visibility = $config->get('visibility')) {
            $options[OssClient::OSS_HEADERS][OssClient::OSS_OBJECT_ACL] = $this->visibilityToAcl($visibility);
        }

        foreach (static::$metaMap as $meta => $map) {
            if (!$config->has($meta)) {
                continue;
            }
            $options[$map] = $config->get($meta);
        }

        return $options;
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
}

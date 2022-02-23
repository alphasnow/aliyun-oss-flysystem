<?php

namespace AlphaSnow\Flysystem\Aliyun;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Config;
use OSS\OssClient;
use League\Flysystem\PathPrefixer;
use League\MimeTypeDetection\MimeTypeDetector;

/**
 * Here is some example file meta data
 * ["type"=>"file","path"=>"/foo/bar/qux.md","timestamp"=>1623289297,"size"=>1024]
 * ["type"=>"dir","path"=>"/foo/bar/","timestamp"=>0,"size"=>0]
 *
 * @package AlphaSnow\Flysystem\Aliyun
 */
class AliyunAdapter implements FilesystemAdapter
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
     * @var OssOptions
     */
    protected $options;

    /**
     * @var PathPrefixer
     */
    protected PathPrefixer $prefixer;
    /**
     * @var VisibilityConverter
     */
    protected VisibilityConverter $visibility;


    /**
     * @param OssClient $client
     * @param string $bucket
     * @param string $prefix
     * @param array $options
     * @param VisibilityConverter|null $visibility
     * @param MimeTypeDetector|null $mimeTypeDetector
     */
    public function __construct(
        OssClient $client,
        string $bucket,
        string $prefix = "",
        array $options = [],
        VisibilityConverter $visibility = null
    ) {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->prefixer = new PathPrefixer($prefix);
        $this->options = new OssOptions($options);
        $this->visibility = $visibility ?: new VisibilityConverter();
    }

    /**
     * {@inheritdoc}
     */
    public function fileExists(string $path): bool
    {
        return $this->client->doesObjectExist($this->bucket, $this->prefixer->prefixPath($path), $this->options->getOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function directoryExists(string $path): bool
    {
        return $this->client->doesObjectExist($this->bucket, $this->prefixer->prefixPath($path), $this->options->getOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $this->client->putObject($this->bucket, $this->prefixer->prefixPath($path), $contents, $this->options->mergeConfig($config,$this->visibility));
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->client->uploadStream($this->bucket, $this->prefixer->prefixPath($path), $contents, $this->options->mergeConfig($config,$this->visibility));
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $path): string
    {
        return $this->client->getObject($this->bucket, $this->prefixer->prefixPath($path), $this->options->getOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function readStream(string $path)
    {
        $stream = fopen("php://temp", "w+b");
        $options = array_merge($this->options->getOptions(), [OssClient::OSS_FILE_DOWNLOAD => $stream]);
        $this->client->getObject($this->bucket, $this->prefixer->prefixPath($path), $options);
        rewind($stream);
        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $path): void
    {
        $this->client->deleteObject($this->bucket, $this->prefixer->prefixPath($path), $this->options->getOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDirectory(string $path): void
    {
        $list = $this->listContents($path, true);

        $objects = [];
        foreach ($list as $val) {
            if ($val["type"] === "dir") {
                $path = rtrim($val["path"], "/") . "/";
            } else {
                $path = $val["path"];
            }

            $objects[] = $this->prefixer->prefixPath($path);
        }

        $this->client->deleteObjects($this->bucket, $objects, $this->options->getOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function createDirectory(string $path, Config $config): void
    {
        $this->client->createObjectDir($this->bucket, $this->prefixer->prefixPath($path), $this->options->mergeConfig($config,$this->visibility));
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility(string $path, string $visibility): void
    {
        $this->client->putObjectAcl($this->bucket, $this->prefixer->prefixPath($path), $this->visibility->visibilityToAcl($visibility), $this->options->getOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function visibility(string $path): FileAttributes
    {
        $acl = $this->client->getObjectAcl($this->bucket, $this->prefixer->prefixPath($path), $this->options->getOptions());

        return new FileAttributes($path, null, $this->visibility->aclToVisibility($acl));
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(string $path): FileAttributes
    {
        return $this->metadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function lastModified(string $path): FileAttributes
    {
        return $this->metadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function fileSize(string $path): FileAttributes
    {
        return $this->metadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $directory = $this->prefixer->prefixPath(rtrim($path, "/")."/");

        $options = array_merge([
            "delimiter" => "/",
            "max-keys" => 1000,
            "marker" => "",
        ], $this->options->getOptions(), [
            "prefix" => $directory
        ]);

        while (true) {
            $listObjectInfo = $this->client->listObjects($this->bucket, $options);

            $objectList = $listObjectInfo->getObjectList();
            if (!empty($objectList)) {
                foreach ($objectList as $objectInfo) {
                    if ($objectInfo->getSize() === 0 && $directory === $objectInfo->getKey()) {
                        $result = [
                            "type" => "dir",
                            "path" => $this->prefixer->stripDirectoryPrefix(rtrim($objectInfo->getKey(), "/")."/"),
                            "size" => 0,
                            "timestamp" => strtotime($objectInfo->getLastModified()),
                        ];
                        yield new DirectoryAttributes($result['path'], null, $result['timestamp']);
                        continue;
                    }

                    $result = [
                        "type" => "file",
                        "path" => $this->prefixer->stripDirectoryPrefix($objectInfo->getKey()),
                        "size" => $objectInfo->getSize(),
                        "timestamp" => strtotime($objectInfo->getLastModified())
                    ];
                    yield new FileAttributes($result['path'], $result['size'], $result['timestamp']);
                }
            }

            $prefixList = $listObjectInfo->getPrefixList();
            foreach ($prefixList as $prefixInfo) {
                $nextDirectory = rtrim($prefixInfo->getPrefix(), "/")."/";
                if ($nextDirectory == $directory) {
                    continue;
                }
                if ($deep) {
                    yield $this->listContents($this->prefixer->stripDirectoryPrefix($nextDirectory), $deep);
                } else {
                    $result = [
                        "type" => "dir",
                        "path" => $this->prefixer->stripDirectoryPrefix($nextDirectory),
                        "size" => 0,
                        "timestamp" => 0,
                    ];
                    yield new DirectoryAttributes($result['path'], null, $result['timestamp']);
                }
            }

            $nextMarker = $listObjectInfo->getNextMarker();
            $options["marker"] = $nextMarker;
            if ($nextMarker === "") {
                break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function move(string $source, string $destination, Config $config): void
    {
        $this->copy($source, $destination, $config);
        $this->delete($source);
    }

    /**
     * {@inheritdoc}
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        $this->client->copyObject($this->bucket, $this->prefixer->prefixPath($source), $this->bucket, $this->prefixer->prefixPath($destination), $this->options->getOptions());
    }

    /**
     * @param $path
     * @return FileAttributes
     */
    protected function metadata($path): FileAttributes
    {
        $result = $this->client->getObjectMeta($this->bucket, $this->prefixer->prefixPath($path), $this->options->getOptions());
        $size = isset($result["info"]["download_content_length"]) ? intval($result["info"]["download_content_length"]) : 0;
        $timestamp = isset($result["info"]["filetime"]) ? $result["info"]["filetime"] : 0;
        $mimetype = isset($result["info"]["content_type"]) ? $result["info"]["content_type"] : "";

        return new FileAttributes($path, $size, null, $timestamp, $mimetype);
    }

}

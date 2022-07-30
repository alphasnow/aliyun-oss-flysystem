<?php

namespace AlphaSnow\Flysystem\Aliyun;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use OSS\Core\OssException;
use OSS\OssClient;

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
    protected $prefixer;

    /**
     * @var VisibilityConverter
     */
    protected $visibility;

    /**
     * @var UrlGenerator
     */
    protected $urlGenerator;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param OssClient $client
     * @param string $bucket
     * @param string $prefix
     * @param array $config
     */
    public function __construct(
        OssClient $client,
        string    $bucket,
        string    $prefix = "",
        array     $config = []
    ) {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->prefixer = new PathPrefixer($prefix);
        $this->config = $config;
        $this->visibility = new VisibilityConverter();
        $this->options = new OssOptions($config["options"] ?? []);
        $this->urlGenerator = new UrlGenerator($config);
    }

    /**
     * {@inheritdoc}
     */
    public function fileExists(string $path): bool
    {
        try {
            return $this->client->doesObjectExist($this->bucket, $this->prefixer->prefixPath($path), $this->options->getOptions());
        } catch (OssException $exception) {
            throw UnableToCheckFileExistence::forLocation($path, $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function directoryExists(string $path): bool
    {
        try {
            return $this->client->doesObjectExist($this->bucket, $this->prefixer->prefixDirectoryPath($path), $this->options->getOptions());
        } catch (OssException $exception) {
            throw UnableToCheckFileExistence::forLocation($path, $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->client->putObject($this->bucket, $this->prefixer->prefixPath($path), $contents, $this->options->mergeConfig($config, $this->visibility));
        } catch (OssException $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getErrorCode(), $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $this->client->uploadStream($this->bucket, $this->prefixer->prefixPath($path), $contents, $this->options->mergeConfig($config, $this->visibility));
        } catch (OssException $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getErrorCode(), $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $path): string
    {
        try {
            return $this->client->getObject($this->bucket, $this->prefixer->prefixPath($path), $this->options->getOptions());
        } catch (OssException $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getErrorCode(), $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function readStream(string $path)
    {
        $stream = fopen("php://temp", "w+b");

        try {
            $options = array_merge($this->options->getOptions(), [OssClient::OSS_FILE_DOWNLOAD => $stream]);
            $this->client->getObject($this->bucket, $this->prefixer->prefixPath($path), $options);
        } catch (OssException $exception) {
            fclose($stream);
            throw UnableToReadFile::fromLocation($path, $exception->getErrorCode(), $exception);
        }

        rewind($stream);
        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $path): void
    {
        try {
            $this->client->deleteObject($this->bucket, $this->prefixer->prefixPath($path), $this->options->getOptions());
        } catch (OssException $exception) {
            throw UnableToDeleteFile::atLocation($path, $exception->getErrorCode(), $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDirectory(string $path): void
    {
        try {
            $contents = $this->listContents($path, false);
            $files = [];
            foreach ($contents as $i => $content) {
                if ($content instanceof DirectoryAttributes) {
                    $this->deleteDirectory($content->path());
                    continue;
                }
                $files[] = $this->prefixer->prefixPath($content->path());
                if ($i && $i % 100 == 0) {
                    $this->client->deleteObjects($this->bucket, $files, $this->options->getOptions());
                    $files = [];
                }
            }
            !empty($files) && $this->client->deleteObjects($this->bucket, $files, $this->options->getOptions());
            $this->client->deleteObject($this->bucket, $this->prefixer->prefixDirectoryPath($path), $this->options->getOptions());
        } catch (OssException $exception) {
            throw UnableToDeleteDirectory::atLocation($path, $exception->getErrorCode(), $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->client->createObjectDir($this->bucket, $this->prefixer->prefixPath($path), $this->options->mergeConfig($config, $this->visibility));
        } catch (OssException $exception) {
            throw UnableToCreateDirectory::dueToFailure($path, $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility(string $path, string $visibility): void
    {
        try {
            $this->client->putObjectAcl($this->bucket, $this->prefixer->prefixPath($path), $this->visibility->visibilityToAcl($visibility), $this->options->getOptions());
        } catch (OssException $exception) {
            throw UnableToSetVisibility::atLocation($path, $exception->getErrorCode(), $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function visibility(string $path): FileAttributes
    {
        try {
            $acl = $this->client->getObjectAcl($this->bucket, $this->prefixer->prefixPath($path), $this->options->getOptions());
        } catch (OssException $exception) {
            throw UnableToRetrieveMetadata::visibility($path, $exception->getErrorCode(), $exception);
        }

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
        $directory = $this->prefixer->prefixDirectoryPath($path);
        $nextMarker = "";
        while (true) {
            $options = array_merge(
                $this->options->getOptions(),
                [
                    OssClient::OSS_PREFIX => $directory,
                    OssClient::OSS_MARKER => $nextMarker
                ]
            );
            try {
                $listObjectInfo = $this->client->listObjects($this->bucket, $options);
                $nextMarker = $listObjectInfo->getNextMarker();
            } catch (OssException $exception) {
                throw new AliyunException($exception->getErrorMessage(), 0, $exception);
            }

            $prefixList = $listObjectInfo->getPrefixList();
            foreach ($prefixList as $prefixInfo) {
                $subPath = $this->prefixer->stripDirectoryPrefix($prefixInfo->getPrefix());
                if ($subPath == $path) {
                    continue;
                }
                yield new DirectoryAttributes($subPath);
                if ($deep === true) {
                    $contents = $this->listContents($subPath, $deep);
                    foreach ($contents as $content) {
                        yield $content;
                    }
                }
            }

            $listObject = $listObjectInfo->getObjectList();
            if (!empty($listObject)) {
                foreach ($listObject as $objectInfo) {
                    $objectPath = $this->prefixer->stripPrefix($objectInfo->getKey());
                    $objectLastModified = strtotime($objectInfo->getLastModified());
                    if (substr($objectPath, -1, 1) == "/") {
                        continue;
                    }
                    yield new FileAttributes($objectPath, $objectInfo->getSize(), null, $objectLastModified);
                }
            }

            if ($listObjectInfo->getIsTruncated() !== "true") {
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
        try {
            $this->client->copyObject($this->bucket, $this->prefixer->prefixPath($source), $this->bucket, $this->prefixer->prefixPath($destination), $this->options->getOptions());
        } catch (OssException $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    /**
     * @param string $path
     * @return FileAttributes
     */
    protected function metadata(string $path): FileAttributes
    {
        try {
            $result = $this->client->getObjectMeta($this->bucket, $this->prefixer->prefixPath($path), $this->options->getOptions());
        } catch (OssException $exception) {
            throw UnableToRetrieveMetadata::create($path, "metadata", $exception->getErrorCode(), $exception);
        }

        $size = isset($result["content-length"]) ? intval($result["content-length"]) : 0;
        $timestamp = isset($result["last-modified"]) ? strtotime($result["last-modified"]) : 0;
        $mimetype = isset($result["content-type"]) ? $result["content-type"] : "";
        return new FileAttributes($path, $size, null, $timestamp, $mimetype);
    }

    /**
     * @return OssClient
     */
    public function getClient(): OssClient
    {
        return $this->client;
    }

    /**
     * @return OssOptions
     */
    public function getOptions(): OssOptions
    {
        return $this->options;
    }

    /**
     * @return PathPrefixer
     */
    public function getPrefixer(): PathPrefixer
    {
        return $this->prefixer;
    }

    /**
     * @return string
     */
    public function getBucket(): string
    {
        return $this->bucket;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getUrl(string $path): string
    {
        $object = $this->prefixer->prefixPath($path);
        return $this->urlGenerator->fullUrl($object);
    }

    /**
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return string
     */
    public function getTemporaryUrl(string $path, \DateTimeInterface $expiration, array $options = []): string
    {
        $object = $this->prefixer->prefixPath($path);
        $options = $this->options->mergeConfig(new Config($options));
        $timeout = $expiration->getTimestamp() - (new \DateTime())->getTimestamp();

        $method = $options[OssClient::OSS_METHOD] ?? OssClient::OSS_HTTP_GET;
        $url = $this->client->signUrl($this->bucket, $object, $timeout, $method, $options);
        return $this->urlGenerator->correctDomain($url);
    }
}

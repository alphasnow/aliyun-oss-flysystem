<?php

namespace AlphaSnow\Flysystem\Aliyun;

use League\Flysystem\Filesystem;
use OSS\OssClient;

class AliyunFactory
{
    public function createFilesystem(array $config, OssClient $client = null): Filesystem
    {
        is_null($client) && $client = $this->createClient($config);
        return new Filesystem(new AliyunAdapter($client, $config['bucket'], $config['prefix'], $config['options']));
    }

    public function createClient($config): OssClient
    {
        $client = new OssClient($config['access_key_id'], $config['access_key_secret'], $config['endpoint'], $config['is_cname'], $config['security_token'], $config['security_token']);
        !is_null($config["use_ssl"]) && $client->setUseSSL($config["use_ssl"]);
        !is_null($config["max_retries"]) && $client->setMaxTries($config["max_retries"]);
        !is_null($config["enable_sts_in_url"]) && $client->setSignStsInUrl($config["enable_sts_in_url"]);
        !is_null($config["timeout"]) && $client->setTimeout($config["timeout"]);
        !is_null($config["connect_timeout"]) && $client->setConnectTimeout($config["connect_timeout"]);
        return $client;
    }
}

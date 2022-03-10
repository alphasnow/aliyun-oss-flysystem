<?php

return [
    "access_key_id" => getenv("OSS_ACCESS_KEY_ID") ?: null,
    "access_key_secret" => getenv("OSS_ACCESS_KEY_SECRET") ?: null,
    "endpoint" => getenv("OSS_ENDPOINT") ?: null,
    "bucket" => getenv("OSS_BUCKET") ?: null,
    "prefix" => getenv("OSS_PREFIX") ?: "",
    "request_proxy" => getenv("OSS_PROXY") ?: null,
    "security_token" => getenv("OSS_TOKEN") ?: null,
    "is_cname" => getenv("OSS_CNAME") == "true" ? true : false,
    "use_ssl" => getenv("OSS_SSL") == "true" ? true : null,
    "max_retries" => getenv("OSS_MAX_RETRIES") ?: null,
    "timeout" => getenv("OSS_TIMEOUT") ?: null,
    "connect_timeout" => getenv("OSS_CONNECT_TIMEOUT") ?: null,
    "enable_sts_in_url" => getenv("OSS_STS_URL") == "true" ? true : null,
    "options" => [
        // \OSS\OssClient::OSS_CHECK_MD5 => false,
    ],
    'internal' => getenv('OSS_INTERNAL', null), // For example: oss-cn-shanghai-internal.aliyuncs.com
    'domain' => getenv('OSS_DOMAIN', null), // For example: oss.my-domain.com
    "reverse_proxy" => getenv('OSS_REVERSE_PROXY', false),
];

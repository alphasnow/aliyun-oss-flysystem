<?php

return [
    "access_key_id" => "",// Required
    "access_key_secret" => "",// Required
    "endpoint" => "",// Required
    "bucket" => "",// Required
    "prefix" => "",
    "request_proxy" => null,
    "security_token" => null,
    "is_cname" => false,
    "use_ssl" => null,
    "max_retries" => null,
    "timeout" => null,
    "connect_timeout" => null,
    "enable_sts_in_url" => null,
    "options" => [
        // \OSS\OssClient::OSS_CHECK_MD5 => false,
    ]
];

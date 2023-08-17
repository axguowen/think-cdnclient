<?php
// +----------------------------------------------------------------------
// | ThinkPHP CdnClient [Simple CDN Client For ThinkPHP]
// +----------------------------------------------------------------------
// | ThinkPHP CdnClient客户端
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: axguowen <axguowen@qq.com>
// +----------------------------------------------------------------------

return [
    // 默认平台
    'default' => 'tencent',
    // 平台配置
    'platforms' => [
        // 腾讯云
        'tencent' => [
            // 驱动类型
            'type'          => 'TencentCloud',
            // SecretId
            'secret_id'     => '',
            // SecretKey
            'secret_key'    => '',
            // 服务接入地域
            'endpoint'      => 'ap-guangzhou',
            // 源站地址
            'origin_server' => 'origin.xxxx.com',
            // 回源HOST
            'origin_host'   => 'host.xxxx.com',
            // 回源请求头
            'request_header'=> [],
            // 默认缓存规则
            'cache_rules'   => [
                [
                    'CacheType' => 'index',
                    'CacheContents' => ['/'],
                    'CacheTime' => 3600 * 24 * 365,
                ],
                [
                    'CacheType' => 'directory',
                    'CacheContents' => ['/nocache/dir'],
                    'CacheTime' => 0,
                ]
            ],
            // 默认IP限频
            'access_limit'  => 0,
            // IP黑名单
            'black_ip'      => [],
            // UA黑名单
            'black_ua'      => [],
        ],
        // 百度云
        'baidu' => [
            // 驱动类型
            'type'          => 'BaiduBce',
            // AccessKey
            'access_key'    => '',
            // SecretKey
            'secret_key'    => '',
            // 服务接入地域
            'endpoint'      => 'http://cdn.baidubce.com',
            // 源站地址
            'origin_server' => 'origin.xxxx.com',
            // 回源HOST
            'origin_host'   => 'host.xxxx.com',
            // 回源请求头
            'request_header'=> [],
            // 默认缓存规则
            'cache_rules'   => [
                [
                    'type' => 'path',
                    'value' => '/',
                    'ttl' => 3600 * 24 * 365,
                    'weight' => 5,
                ],
                [
                    'type' => 'path',
                    'value' => '/nocache/dir',
                    'ttl' => 0,
                    'weight' => 10,
                ]
            ],
            // 默认IP限频
            'access_limit'  => 0,
            // IP黑名单
            'black_ip'      => [],
            // UA黑名单
            'black_ua'      => [],
        ],
        // 天翼云
        'ctyun' => [
            // 驱动类型
            'type'          => 'Ctyun',
            // API接入所使用的KeyId
            'key_id'        => '',
            // API接入所使用的密钥
            'key_secret'    => '',
            // 源站地址
            'origin_server' => 'origin.xxxx.com',
            // 回源HOST
            'origin_host'   => 'host.xxxx.com',
            // 回源请求头
            'request_header'=> [],
            // 默认缓存规则
            'cache_rules'   => [],
            // 默认IP限频
            'access_limit'  => 0,
            // IP黑名单
            'black_ip'      => [],
            // UA黑名单
            'black_ua'      => [],
        ],
        // 其它
        'other' => [
            // 驱动类型
            'type'          => 'TencentCloud',
            // SecretId
            'secret_id'     => '',
            // SecretKey
            'secret_key'    => '',
            // 服务接入地域
            'endpoint'      => '',
            // 源站地址
            'origin_server' => '',
            // 回源HOST
            'origin_host'   => '',
        ],
    ]
];

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

namespace think\cdnclient\driver;

use think\cdnclient\Platform;
use axguowen\ctyun\services\cdn\Auth;
use axguowen\ctyun\services\cdn\CdnClient;

class Ctyun extends Platform
{
    /**
     * 驱动句柄
     * @var CdnClient
     */
    protected $handler;

	/**
     * 平台配置参数
     * @var array
     */
    protected $options = [
        // API接入所使用的KeyId
        'key_id' => '',
        // API接入所使用的密钥
        'key_secret' => '',
        // 源站地址
        'origin_server' => '',
        // 回源HOST
        'origin_host' => '',
        // 回源请求头
        'request_header'=> [],
        // 缓存规则
        'cache_rules' => [],
        // 默认IP限频
        'frequency_threshold'  => 0,
        // 限频统计周期单位秒
        'frequency_time_range' => 60,
        // 触发限频后禁止访问时间单位秒
        'forbidden_duration' => 86400,
        // 限频白名单IP
        'entry_limits_white_ip' => [],
        // IP黑名单
        'black_ip' => [],
        // UA黑名单
        'black_ua' => [],
    ];
    
    /**
     * 创建句柄
     * @access protected
     * @return $this
     */
    protected function makeHandler()
    {
        // 实例化授权对象
        $credential = new Auth($this->options['key_id'], $this->options['key_secret']);
        // 实例化要请求产品的 client 对象
        $this->handler = new CdnClient($credential);
        // 返回
        return $this;
    }

    /**
     * 新增加速域名
     * @access public
     * @param string $domain
     * @return array
     */
    public function addCdnDomain(string $domain)
    {
        // 要创建的参数
        $createData = [
            'domain' => $domain,
            'origin' => [
                [
                    'origin' => $this->options['origin_server'],
                    'port' => 80,
                    'weight' => 10,
                    'role' => 'master',
                ]
            ],
            'req_host' => $this->options['origin_host'],
        ];

        // IP限频
        if($this->options['frequency_threshold'] > 0){
            $createData['entry_limits'] = [
                [
                    'id' => 'entry_condition_all',
                    'limit_element' => 'remote_addr=$remote_addr',
                    'frequency_threshold' => $this->options['frequency_threshold'],
                    'frequency_time_range' => $this->options['frequency_time_range'],
                    'forbidden_duration' => $this->options['forbidden_duration'],
                    'priority' => 10,
                    'white_ip_control' => $this->options['entry_limits_white_ip'],
                ],
            ];
            $createData['entry_limits_condition'] = [
                'entry_condition_all' => [
                    [
                        'mode' => 3,
                        'content' => '/',
                    ],
                ],
            ];
        }
        // 缓存配置
        if(!empty($this->options['cache_rules'])){
            $createData['filetype_ttl'] = $this->options['cache_rules'];
        }
        // 回源请求头配置
        if(!empty($this->options['request_header'])){
            // 回源请求头规则
            $requestHeaderRules = [];
            foreach($this->options['request_header'] as $headerName => $headerValue){
                $requestHeaderRules[] = [
                    'key' => $headerName,
                    'value' => $headerValue,
                ];
            }
            $createData['req_headers'] = $requestHeaderRules;
        }
        // IP黑名单配置
        if(!empty($this->options['black_ip'])){
            // 规则去重
            $ipList = array_unique($this->options['black_ip']);
            $createData['ip_black_list'] = implode(',', $ipList);
        }
        // UA黑名单配置
        if(!empty($this->options['black_ua'])){
            // 规则去重
            $uaList = array_unique($this->options['black_ua']);
            $createData['user_agent'] = [
                'type' => 0,
                'ua' => $uaList,
            ];
        }

        // 获取响应
        try{
            $response = $this->handler->domainManage($createData);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果返回成功
        if($response['code'] == 100000){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误
        return [null, new \Exception($response['message'])];
    }

    /**
	 * 查询域名归属权解析记录信息
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function getRecord(string $domain)
    {
        // 获取验证归属权信息
        try{
            $response = $this->handler->howToVerify($domain);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果返回失败
        if($response['code'] != 100000){
            // 返回错误
            return [null, new \Exception($response['message'], $response['code'])];
        }
        // CNAME解析地址
        $cnameRecordValue = $domain . '.ctadns.cn';
        // 要返回的信息
        $resultData = [];
        // 如果是未通过验证
        if(false === $response['verify_result']){
            // 如果没给验证解析记录值
            if(!isset($response['content'])){
                return [null, new \Exception($response['verify_desc'])];
            }
            // 获取主域名
            $primaryDomain = $response['domain_zone'];
            // 获取子域名
            $subDomain = trim(str_replace($primaryDomain, '', $domain), '.');

            // txt解析
            $record_txt = [
                // 记录类型
                'type' => 'TXT',
                // 主机记录名
                'record_name' => 'dnsverify',
                // 记录值
                'record_value' => $response['content'],
                // 主域名
                'domain' => $primaryDomain,
            ];
            // cname解析
            $record_cname = [
                // 记录类型
                'type' => 'CNAME',
                // 主机记录名
                'record_name' => $subDomain,
                // 记录值
                'record_value' => $cnameRecordValue,
                // 主域名
                'domain' => $primaryDomain,
            ];

            // 获取
            return [[
                'record_txt' => $record_txt,
                'record_cname' => $record_cname,
            ], null];
        }

        // 验证已通过则获取域名格式化信息
        $domainFormatResult = \think\cdnclient\utils\DomainFormator::format($domain);
        // 如果失败
        if(is_null($domainFormatResult[0])){
            return $domainFormatResult[1];
        }

        // 获取主域名
        $primaryDomain = $domainFormatResult[0]['primary'];
        // 获取子域名
        $subDomain = $domainFormatResult[0]['sub_domain'];

        // txt解析
        $record_txt = null;
        // cname解析
        $record_cname = [
            // 记录类型
            'type' => 'CNAME',
            // 主机记录名
            'record_name' => $subDomain,
            // 记录值
            'record_value' => $domain . '.ctadns.cn',
            // 主域名
            'domain' => $primaryDomain,
        ];

        // 获取
        return [[
            'record_txt' => $record_txt,
            'record_cname' => $record_cname,
        ], null];
	}

    /**
	 * 验证域名归属权
	 * @access public
	 * @param string $domain
     * @param int $verifyType 验证类型
	 * @return array
	 */
	public function verifyRecord(string $domain, int $verifyType = 1)
    {
        // 获取响应
        try{
            // 查询域名是否存在在途工单
            $response = $this->handler->domainIsExistOnwayOrder($domain);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }
            // 如果存在在途工单
            if(true === $response['is_exist']){
                // 返回无需验证
                return ['域名所有权验证通过', null];
            }
            // 没有在途工单则尝试获取域名信息
            $response = $this->handler->domainInfo($domain);
            // 存在域名信息则无需验证
            if($response['code'] == 100000){
                // 返回无需验证
                return ['域名所有权验证通过', null];
            }

            // 不存在域名则获取验证信息核对是否已经通过验证
            $response = $this->handler->howToVerify($domain);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }
            // 如果无需验证
            if(true === $response['verify_result']){
                // 返回无需验证
                return ['域名所有权验证通过', null];
            }

            // 需要验证则开始验证
            $response = $this->handler->verifyDomainOwnership($domain, $verifyType);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }
            // 如果验证成功
            if(true === $response['verify_result']){
                // 返回成功
                return ['域名所有权验证通过', null];
            }
            // 返回错误
            return [null, new \Exception('域名未通过TXT所有权验证，请与域名解析人员核对')];
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }

    /**
	 * 删除域名
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function deleteCdnDomain(string $domain)
    {
        // 获取响应
        try{
            // 查询域名是否存在在途工单
            $response = $this->handler->domainIsExistOnwayOrder($domain);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }
            // 如果存在在途工单
            if(true === $response['is_exist']){
                // 返回错误
                return [null, new \Exception('域名配置中, 请5分钟后再试')];
            }
            // 开始删除
            $response = $this->handler->domainChangeStatus($domain, 1);
            // 如果返回成功
            if($response['code'] == 100000){
                // 返回成功
                return ['操作成功', null];
            }
            // 返回错误
            return [null, new \Exception($response['message'], $response['code'])];
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }

    /**
	 * 设置IP黑名单
	 * @access public
	 * @param string $domain
	 * @param array $ipList
	 * @return array
	 */
	public function setIpBlackList(string $domain, array $ipList = [])
    {
        // 规则去重
        $ipList = array_unique($ipList);
        // 去除空值
        $ipList = array_filter($ipList, function($value) {
            return !empty($value);
        });
        // 获取全部值
        $ipList = array_values($ipList);
        
        // 获取响应
        try{
            // 查询域名是否存在在途工单
            $response = $this->handler->domainIsExistOnwayOrder($domain);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }
            // 如果存在在途工单
            if(true === $response['is_exist']){
                // 返回错误
                return [null, new \Exception('域名配置中, 请5分钟后再试')];
            }

            $response = $this->handler->domainIncreUpdate($domain, [
                'ip_black_list' => implode(',', $ipList)
            ]);
            // 如果返回成功
            if($response['code'] == 100000){
                // 返回成功
                return ['操作成功', null];
            }
            // 返回错误
            return [null, new \Exception($response['message'])];
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }

    /**
	 * 设置UA黑名单
	 * @access public
	 * @param string $domain
	 * @param array $uaList
	 * @return array
	 */
	public function setUaBlackList(string $domain, array $uaList = [])
    {
        // 规则去重
        $uaList = array_unique($uaList);
        // 去除空值
        $uaList = array_filter($uaList, function($value) {
            return !empty($value);
        });

        // 获取响应
        try{
            // 查询域名是否存在在途工单
            $response = $this->handler->domainIsExistOnwayOrder($domain);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }
            // 如果存在在途工单
            if(true === $response['is_exist']){
                // 返回错误
                return [null, new \Exception('域名配置中, 请5分钟后再试')];
            }
            // 开始更新
            $response = $this->handler->domainIncreUpdate($domain, [
                'user_agent' => [
                    'type' => 0,
                    'ua' => $uaList,
                ]
            ]);
            // 如果返回成功
            if($response['code'] == 100000){
                // 返回成功
                return ['操作成功', null];
            }
            // 返回错误
            return [null, new \Exception($response['message'])];
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }

    /**
	 * 设置域名证书
	 * @access public
	 * @param string $domain
	 * @param array $certificate
	 * @return array
	 */
	public function setCertificate(string $domain, array $certificate)
    {
        // 获取响应
        try{
            // 查询域名是否存在在途工单
            $response = $this->handler->domainIsExistOnwayOrder($domain);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }
            // 如果存在在途工单
            if(true === $response['is_exist']){
                // 返回错误
                return [null, new \Exception('域名配置中, 请5分钟后再试')];
            }
            // 构造证书名称
            $certName = $domain;
            // 如果指定了证书名称
            if(isset($certificate['cert_name']) && !empty($certificate['cert_name'])){
                // 获取证书名称
                $certName = $certificate['cert_name'];
            }
            // 创建证书
            $response = $this->handler->certCreate($certName, $certificate['cert_private'], $certificate['cert_public']);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }

            // 更新域名配置
            $response = $this->handler->domainIncreUpdate($domain, [
                'https_status' => 'on',
                'cert_name' => $certName,
            ]);

            // 如果返回成功
            if($response['code'] == 100000){
                // 返回成功
                return ['操作成功', null];
            }
            // 返回错误
            return [null, new \Exception($response['message'])];

        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }

    /**
	 * 查询域名证书
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function getCertificate(string $domain)
    {
        // 获取响应
        try{
            $response = $this->handler->certQuery(null, $domain);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果返回失败
        if($response['code'] != 100000){
            // 返回错误
            return [null, new \Exception($response['message'], $response['code'])];
        }
        // 返回成功
        return [[
            // 证书名称
            'cert_name' => $response['result']['name'],
            // 到期时间
            'expire_time' => $response['result']['expires'],
            // 部署时间
            'deploy_time' => $response['result']['created'],
        ], null];
    }

    /**
	 * 删除域名证书
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function deleteCertificate(string $domain)
    {
        // 获取响应
        try{
            // 查询域名是否存在在途工单
            $response = $this->handler->domainIsExistOnwayOrder($domain);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }
            // 如果存在在途工单
            if(true === $response['is_exist']){
                // 返回错误
                return [null, new \Exception('域名配置中, 请5分钟后再试')];
            }
            // 查询域名配置
            $response = $this->handler->domainInfo($domain);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }
            // 如果开启了HTTPS
            if(isset($response['https_status'])){
                // 如果开启了HTTPS
                if('on' === $response['https_status']){
                    // 更新域名配置关闭HTTPS
                    $response = $this->handler->domainIncreUpdate($domain, [
                        'https_status' => 'off',
                        'cert_name' => '',
                    ]);
                    // 如果返回失败
                    if($response['code'] != 100000){
                        // 返回错误
                        return [null, new \Exception($response['message'])];
                    }
                }
                // 返回成功
                return ['操作成功', null];
            }
            // 返回错误
            return [null, new \Exception($response['message'])];
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }

    /**
	 * 注销域名证书
	 * @access public
	 * @param string $certName
	 * @return array
	 */
	public function destroyCertificate(string $certName)
    {
        // 获取响应
        try{
            // 删除证书
            $response = $this->handler->certDelete($certName);
            // 如果返回成功
            if($response['code'] == 100000){
                // 返回成功
                return ['操作成功', null];
            }
            // 返回错误
            return [null, new \Exception($response['message'])];
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }

    /**
	 * 启用加速域名
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function startCdnDomain(string $domain)
    {
        // 获取响应
        try{
            // 查询域名是否存在在途工单
            $response = $this->handler->domainIsExistOnwayOrder($domain);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }
            // 如果存在在途工单
            if(true === $response['is_exist']){
                // 返回错误
                return [null, new \Exception('域名配置中, 请5分钟后再试')];
            }
            // 开始更新
            $response = $this->handler->domainChangeStatus($domain, 3);
            // 如果返回成功
            if($response['code'] == 100000){
                // 返回成功
                return ['操作成功', null];
            }
            // 返回错误
            return [null, new \Exception($response['message'])];
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }

    /**
	 * 停用加速域名
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function stopCdnDomain(string $domain)
    {
        // 获取响应
        try{
            // 查询域名是否存在在途工单
            $response = $this->handler->domainIsExistOnwayOrder($domain);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }
            // 如果存在在途工单
            if(true === $response['is_exist']){
                // 返回错误
                return [null, new \Exception('域名配置中, 请5分钟后再试')];
            }
            // 开始更新
            $response = $this->handler->domainChangeStatus($domain, 2);
            // 如果返回成功
            if($response['code'] == 100000 || $response['message'] == '请求参数域名校验失败，当前用户对域名' . $domain . '无权限'){
                // 返回成功
                return ['操作成功', null];
            }
            // 返回错误
            return [null, new \Exception($response['message'])];
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }

    /**
	 * 设置缓存规则
	 * @access public
	 * @param string $domain
	 * @param array $cacheRules
	 * @return array
	 */
	public function setCacheRules(string $domain, array $cacheRules)
    {
        // 获取响应
        try{
            // 查询域名是否存在在途工单
            $response = $this->handler->domainIsExistOnwayOrder($domain);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }
            // 如果存在在途工单
            if(true === $response['is_exist']){
                // 返回错误
                return [null, new \Exception('域名配置中, 请5分钟后再试')];
            }
            // 开始更新
            $response = $this->handler->domainIncreUpdate($domain, [
                'filetype_ttl' => $cacheRules
            ]);
            // 如果返回成功
            if($response['code'] == 100000){
                // 返回成功
                return ['操作成功', null];
            }
            // 返回错误
            return [null, new \Exception($response['message'])];
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }

    /**
	 * 批量设置缓存规则
	 * @access public
	 * @param array|string $domain
	 * @param array $cacheRules
	 * @return array
	 */
	public function batchSetCacheRules($domain, array $cacheRules)
    {
        // 获取响应
        try{
            // 开始更新
            $response = $this->handler->domainBatchUpdateConfigurationInformation($domain, [
                'filetype_ttl' => $cacheRules
            ]);
            // 如果返回成功
            if($response['code'] == 100000){
                // 返回成功
                return [$response['domain_details'], null];
            }
            // 返回错误
            return [null, new \Exception($response['message'])];
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }

    /**
	 * 设置IP访问限频
	 * @access public
	 * @param string $domain
	 * @param int $qps
	 * @param int $timeRange
	 * @return array
	 */
	public function setAccessLimit(string $domain, int $qps = 0, int $timeRange = 1)
    {
        // 获取响应
        try{
            // 查询域名是否存在在途工单
            $response = $this->handler->domainIsExistOnwayOrder($domain);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }
            // 如果存在在途工单
            if(true === $response['is_exist']){
                // 返回错误
                return [null, new \Exception('域名配置中, 请5分钟后再试')];
            }
            // 默认传空数据
            $data = [
                'entry_limits' => [],
                'entry_limits_condition' => (object) [],
            ];
            // 如果qps大于0
            if($qps > 0){
                $data = [
                    'entry_limits' => [
                        [
                            'id' => 'entry_condition_all',
                            'limit_element' => 'remote_addr=$remote_addr',
                            'frequency_threshold' => $qps,
                            'frequency_time_range' => $timeRange,
                            'forbidden_duration' => $this->options['forbidden_duration'],
                            'priority' => 10,
                            'white_ip_control' => $this->options['entry_limits_white_ip'],
                        ],
                    ],
                    'entry_limits_condition' => [
                        'entry_condition_all' => [
                            [
                                'mode' => 3,
                                'content' => '/',
                            ],
                        ],
                    ],
                ];
            }
            // 开始更新
            $response = $this->handler->domainIncreUpdate($domain, $data);
            // 如果返回成功
            if($response['code'] == 100000){
                // 返回成功
                return ['操作成功', null];
            }
            // 返回错误
            return [null, new \Exception($response['message'])];
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }
    
    /**
	 * 设置回源请求头
	 * @access public
	 * @param string $domain
	 * @param array $header
	 * @return array
	 */
	public function setRequestHeader(string $domain, array $header)
    {
        // 获取响应
        try{
            // 查询域名是否存在在途工单
            $response = $this->handler->domainIsExistOnwayOrder($domain);
            // 如果返回失败
            if($response['code'] != 100000){
                // 返回错误
                return [null, new \Exception($response['message'], $response['code'])];
            }
            // 如果存在在途工单
            if(true === $response['is_exist']){
                // 返回错误
                return [null, new \Exception('域名配置中, 请5分钟后再试')];
            }
            // 回源请求头规则
            $requestHeaderRules = [];
            foreach($header as $headerName => $headerValue){
                $requestHeaderRules[] = [
                    'key' => $headerName,
                    'value' => $headerValue,
                ];
            }
            // 开始更新
            $response = $this->handler->domainIncreUpdate($domain, [
                'req_headers' => $requestHeaderRules,
            ]);
            // 如果返回成功
            if($response['code'] == 100000){
                // 返回成功
                return ['操作成功', null];
            }
            // 返回错误
            return [null, new \Exception($response['message'])];
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }

    /**
	 * 刷新文件缓存
	 * @access public
	 * @param array|string $urls
	 * @return array
	 */
	public function purgeUrlsCache($urls = [])
    {
        // 如果不是数组则分隔
        if(!is_array($urls)){
            $urls = explode(',', $urls);
        }

        // 获取响应
        try{
            $response = $this->handler->refreshManageCreate($urls, 1);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response['code'] == 100000){
            // 获取响应结果
            $result = $response['result'][0];
            // 返回成功
            return [['task_id' => $result['task_id']], null];
        }
        // 返回错误
        return [null, new \Exception($response['message'])];
    }

    /**
	 * 刷新目录缓存
	 * @access public
	 * @param array|string $path
	 * @return array
	 */
	public function purgePathsCache($paths = [])
    {
        // 如果不是数组则分隔
        if(!is_array($paths)){
            $paths = explode(',', $paths);
        }

        // 获取响应
        try{
            $response = $this->handler->refreshManageCreate($paths, 2);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response['code'] == 100000){
            // 获取响应结果
            $result = $response['result'][0];
            // 返回成功
            return [['task_id' => $result['task_id']], null];
        }
        // 返回错误
        return [null, new \Exception($response['message'])];
    }

    /**
	 * 查询缓存刷新状态
	 * @access public
	 * @param string $taskId
	 * @return array
	 */
	public function getPurgeStatus($taskId)
    {
        // 获取响应
        try{
            $response = $this->handler->refreshManageQuery([
                'type' => 2,
                'task_id' => $taskId,
                'page_size' => 1,
            ]);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response['code'] != 100000){
            // 返回错误
            return [null, new \Exception($response['message'], $response['code'])];
        }
        // 获取刷新任务详情
        $purgeTask = $response['result'][0];
        // 返回信息
        $status_code = '';
        $status_text = '';
        switch($purgeTask['status']) {
            case 'processing':
                $status_code = 'processing';
                $status_text = '刷新中';
                break;
            case 'completed':
                $status_code = 'completed';
                $status_text = '刷新完成';
                break;
            case 'failed':
                $status_code = 'failed';
                $status_text = '刷新失败';
                break;
        }
        // 返回成功
        return [[
            'status_code' => $status_code,
            'status_text' => $status_text,
        ], null];
    }

    /**
	 * 查询缓存刷新限额
	 * @access public
	 * @return array
	 */
	public function getPurgeQuota()
    {
        // 获取响应
        try{
            $response = $this->handler->refreshManageQuota();
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response['code'] != 100000){
            // 返回错误
            return [null, new \Exception($response['message'], $response['code'])];
        }
        // 获取限额数据
        $purgeQuota = $response['result']['quotas'][0];
        // 返回成功
        return [[
            'url' => [
                'total' => $purgeQuota['url_max'],
                'remain' => $purgeQuota['url_surplus'],
            ],
            'path' => [
                'total' => $purgeQuota['dir_max'],
                'remain' => $purgeQuota['dir_surplus'],
            ]
        ], null];
    }

    /**
     * 查询域名是否存在
     * @access public
     * @param string $domain
     * @return array
     */
    public function isExist($domain)
    {
        // 获取响应
        try{
            $response = $this->handler->domainInfo($domain);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        $exist = false;
        // 如果返回成功
        if($response['code'] == 100000){
            $exist = true;
        }
        // 返回成功
        return [['exist' => $exist], null];
    }

    /**
     * 查询域名配置信息
     * @access public
     * @param string $domain
     * @return array
     */
    public function getDomainInfo($domain)
    {
        // 获取响应
        try{
            $response = $this->handler->domainInfo($domain);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果返回失败
        if($response['code'] != 100000){
            // 返回错误
            return [null, new \Exception($response['message'], $response['code'])];
        }
        // 返回成功
        return [$response, null];
    }

    /**
     * 查询域名配置状态
     * @access public
     * @param string $domain
     * @return array
     */
    public function getDomainStatus($domain)
    {
        // 获取响应
        try{
            $response = $this->handler->domainInfo($domain);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果返回失败
        if($response['code'] != 100000){
            // 返回错误
            return [null, new \Exception($response['message'], $response['code'])];
        }
        // 默认停止状态
        $status = 0;
        // 如果状态为启用
        if($response['status'] == 4){
            // 设置为启用状态
            $status = 1;
        }
        // 返回成功
        return [['status' => $status], null];
    }
}
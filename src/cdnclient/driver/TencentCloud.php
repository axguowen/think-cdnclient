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
use TencentCloud\Common\Credential;
use TencentCloud\Cdn\V20180606\CdnClient;
// 要请求接口对应的Request类
use TencentCloud\Cdn\V20180606\Models\DescribeDomainsRequest;
use TencentCloud\Cdn\V20180606\Models\DescribeDomainsConfigRequest;
use TencentCloud\Cdn\V20180606\Models\CreateVerifyRecordRequest;
use TencentCloud\Cdn\V20180606\Models\VerifyDomainRecordRequest;
use TencentCloud\Cdn\V20180606\Models\AddCdnDomainRequest;
use TencentCloud\Cdn\V20180606\Models\UpdateDomainConfigRequest;
use TencentCloud\Cdn\V20180606\Models\DeleteCdnDomainRequest;
use TencentCloud\Cdn\V20180606\Models\StopCdnDomainRequest;
use TencentCloud\Cdn\V20180606\Models\StartCdnDomainRequest;
use TencentCloud\Cdn\V20180606\Models\PurgeUrlsCacheRequest;
use TencentCloud\Cdn\V20180606\Models\PurgePathCacheRequest;
use TencentCloud\Cdn\V20180606\Models\DescribePurgeTasksRequest;
use TencentCloud\Cdn\V20180606\Models\DescribePurgeQuotaRequest;

class TencentCloud extends Platform
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
        // SecretId
        'secret_id' => '',
        // SecretKey
        'secret_key' => '',
        // 服务接入地域
        'endpoint' => '',
        // 源站地址
        'origin_server' => '',
        // 回源HOST
        'origin_host' => '',
        // 缓存规则
        'cache_rules' => [],
        // 默认IP限频
        'access_limit' => 0,
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
        $credential = new Credential($this->options['secret_id'], $this->options['secret_key']);
        // 实例化要请求产品的 client 对象
        $this->handler = new CdnClient($credential, $this->options['endpoint']);
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
        // 请求对象
        $request = new AddCdnDomainRequest();
        // 指定域名
        $request->Domain = $domain;
        // 业务类型
        $request->ServiceType = 'web';
        // 回源配置
        $request->Origin = [
            'OriginType' => 'domain',
            'Origins' => [
                $this->options['origin_server']
            ],
            'OriginPullProtocol' => 'follow',
            'ServerName' => $this->options['origin_host'],
        ];

        // IP限频
        if($this->options['access_limit'] > 0){
            $request->IpFreqLimit = [
                'Switch' => 'on',
                'Qps' => $this->options['access_limit'],
            ];
        }

        // 缓存键配置
        $request->CacheKey = [
            'FullUrlCache' => 'off',
            'IgnoreCase' => 'on',
        ];

        // 缓存配置
        if(!empty($this->options['cache_rules'])){
            $request->Cache = [
                'SimpleCache' => [
                    'CacheRules' => $this->options['cache_rules'],
                ],
            ];
        }

        // 回源请求头配置
        if(!empty($this->options['request_header'])){
            $requestHeaderRules = [];
            foreach($this->options['request_header'] as $headerName => $headerValue){
                $requestHeaderRules[] = [
                    'HeaderMode' => 'set',
                    'HeaderName' => $headerName,
                    'HeaderValue' => $headerValue,
                    'RuleType' => 'all',
                    'RulePaths' => ['*'],
                ];
            }
            $request->RequestHeader = [
                'Switch' => 'on',
                'HeaderRules' => $requestHeaderRules,
            ];
        }

        // IP黑名单配置
        if(!empty($this->options['black_ip'])){
            // 规则去重
            $ipList = array_unique($this->options['black_ip']);
            // 去除空值
            $ipList = array_filter($ipList, function($value) {
                return !empty($value);
            });

            $request->IpFilter = [
                'Switch' => 'on',
                'FilterType' => 'blacklist',
                'Filters' => $ipList,
            ];
        }

        // UA黑名单配置
        if(!empty($this->options['black_ua'])){
            // 规则去重
            $uaList = array_unique($this->options['black_ua']);
            // 去除空值
            $uaList = array_filter($uaList, function($value) {
                return !empty($value);
            });

            // 规则列表
            $uaFilterRules = [];
            // 临时规则列表
            $uaTempList = [];
            // 遍历
            foreach($uaList as $item){
                // 存入临时规则
                $uaTempList[] = $item;
                // 临时规则数量达到10
                if(count($uaTempList) == 10){
                    // 存入规则
                    $uaFilterRules[] = [
						'RuleType' => 'all',
						'RulePaths' => ['*'],
						'FilterType' => 'blacklist',
						'UserAgents' => $uaTempList,
                    ];
                    // 清空临时规则
                    $uaTempList = [];
                }
            }
            // 临时规则不为空
            if(!empty($uaTempList)){
                // 存入规则
                $uaFilterRules[] = [
                    'RuleType' => 'all',
                    'RulePaths' => ['*'],
                    'FilterType' => 'blacklist',
                    'UserAgents' => $uaTempList,
                ];
                // 清空临时规则
                $uaTempList = [];
            }
            $request->UserAgentFilter = [
                'Switch' => 'on',
                'FilterRules' => $uaFilterRules,
            ];
        }

        try{
            // 响应
            $response = $this->handler->AddCdnDomain($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(!isset($response->Error)){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误信息
        return [null, new \Exception($response->Error->Message)];
    }

    /**
	 * 查询域名归属权解析记录信息
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function getRecord(string $domain)
    {
        // 请求对象
        $request = new CreateVerifyRecordRequest();
        $request->Domain = $domain;
        try{
            // 响应
            $response = $this->handler->CreateVerifyRecord($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果存在错误信息
        if(isset($response->Error)){
            // 返回错误信息
            return [null, new \Exception($response->Error->Message)];
        }
        // 获取验证域名列表
        $fileVerifyDomains = $response->FileVerifyDomains;
        // 获取主域名
        $primaryDomain = $fileVerifyDomains[0];
        // 遍历列表
        foreach($fileVerifyDomains as $item){
            // 如果不是当前域名
            if($item != $domain){
                $primaryDomain = $item;
                break;
            }
        }
        // 获取子域名
        $subDomain = trim(str_replace($primaryDomain, '', $domain), '.');

        // txt解析
        $record_txt = [
            // 记录类型
            'type' => 'TXT',
            // 主机记录名
            'record_name' => $response->SubDomain,
            // 记录值
            'record_value' => $response->Record,
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
            'record_value' => $domain . '.cdn.dnsv1.com.cn',
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
	 * @return array
	 */
	public function verifyRecord(string $domain)
    {
        // 请求对象
        $request = new VerifyDomainRecordRequest();
        $request->Domain = $domain;
        try{
            // 响应
            $response = $this->handler->VerifyDomainRecord($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(!isset($response->Error)){
            // 返回成功
            return ['域名所有权验证通过', null];
        }
        // 返回错误信息
        return [null, new \Exception($response->Error->Message)];
    }

    /**
	 * 删除域名
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function deleteCdnDomain(string $domain)
    {
        // 停用加速域名请求对象
        $request = new StopCdnDomainRequest();
        $request->Domain = $domain;
        // 删除加速域名请求对象
        $deleteCdnDomainRequest = new DeleteCdnDomainRequest();
		$deleteCdnDomainRequest->Domain = $domain;

        try{
            // 停用域名的响应
            $stopCdnDomainResponse = $this->handler->StopCdnDomain($request);
            // 响应
            $deleteCdnDomainResponse = $this->handler->DeleteCdnDomain($deleteCdnDomainRequest);
        } catch (\Exception $e) {
            // 获取错误信息
            $errorMessage = $e->getMessage();
            // 如果是CDN不存在或者无此域名
            if($errorMessage == 'cdn host not exists' || false !== strpos($errorMessage, '账号下无此域名')){
                // 返回成功
                return ['操作成功', null];
			}
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(!isset($deleteCdnDomainResponse->Error)){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误信息
        return [null, new \Exception($deleteCdnDomainResponse->Error->Message)];
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
        // 开启状态
        $switch = 'on';
        // IP列表为空
        if(empty($ipList)){
            $switch = 'off';
        }
        
        // 规则去重
        $ipList = array_unique($ipList);
        // 去除空值
        $ipList = array_filter($ipList, function($value) {
            return !empty($value);
        });

        // 请求对象
        $request = new UpdateDomainConfigRequest();
        $request->Domain = $domain;
        $request->IpFilter = [
            'Switch' => $switch,
            'FilterType' => 'blacklist',
            'Filters' => $ipList
        ];

        try{
            // 响应
            $response = $this->handler->UpdateDomainConfig($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(!isset($response->Error)){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误信息
        return [null, new \Exception($response->Error->Message)];
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
        // UA黑名单配置
        $userAgentFilter = [
            'Switch' => 'off',
        ];
        // IP列表为空
        if(!empty($uaList)){
            // 规则去重
            $uaList = array_unique($uaList);
            // 去除空值
            $uaList = array_filter($uaList, function($value) {
                return !empty($value);
            });
            
            // 规则列表
            $filterRules = [];
            // 临时规则列表
            $tempList = [];
            // 遍历
            foreach($uaList as $item){
                // 存入临时规则
                $tempList[] = $item;
                // 临时规则数量达到10
                if(count($tempList) == 10){
                    // 存入规则
                    $filterRules[] = [
						'RuleType' => 'all',
						'RulePaths' => ['*'],
						'FilterType' => 'blacklist',
						'UserAgents' => $tempList,
                    ];
                    // 清空临时规则
                    $tempList = [];
                }
            }
            // 临时规则不为空
            if(!empty($tempList)){
                // 存入规则
                $filterRules[] = [
                    'RuleType' => 'all',
                    'RulePaths' => ['*'],
                    'FilterType' => 'blacklist',
                    'UserAgents' => $tempList,
                ];
                // 清空临时规则
                $tempList = [];
            }
            // 构造规则
            $userAgentFilter = [
                'Switch' => 'on',
                'FilterRules' => $filterRules,
            ];
        }
        // 请求对象
        $request = new UpdateDomainConfigRequest();
        $request->Domain = $domain;
        $request->UserAgentFilter = $userAgentFilter;

        try{
            // 响应
            $response = $this->handler->UpdateDomainConfig($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(!isset($response->Error)){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误信息
        return [null, new \Exception($response->Error->Message)];
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
        // 请求对象
        $request = new UpdateDomainConfigRequest();
        $request->Domain = $domain;
        $request->Https = [
            'Switch' => 'on',
            'CertInfo' => [
                // 证书名称
                'CertName' => $certificate['cert_name'],
                // 证书公钥
                'Certificate' => $certificate['cert_public'],
                // 证书私钥
                'PrivateKey' => $certificate['cert_private'],
            ],
        ];

        try{
            // 响应
            $response = $this->handler->UpdateDomainConfig($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(!isset($response->Error)){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误信息
        return [null, new \Exception($response->Error->Message)];
    }

    /**
	 * 查询域名证书
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function getCertificate(string $domain)
    {
        // 请求对象
        $request = new DescribeDomainsConfigRequest();
        $request->Limit = 1;
        $request->Filters = [
            [
                'Name' => 'domain',
                'Value' => [$domain],
            ]
        ];

        try{
            // 响应
            $response = $this->handler->DescribeDomainsConfig($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果存在错误信息
        if(isset($response->Error)){
            // 返回错误信息
            return [null, new \Exception($response->Error->Message)];
        }
        // 如果未查询到
        if(!isset($response->Domains[0])){
            // 返回错误信息
            return [null, new \Exception('未查询到域名信息')];
        }
        // 获取域名配置信息
        $domainDetail = $response->Domains[0];
        // 如果没有证书信息
        if(is_null($domainDetail->Https->CertInfo)){
            // 返回错误信息
            return [null, new \Exception('未查询到证书信息')];
        }
        // 获取证书信息
        $certInfo = $domainDetail->Https->CertInfo;
        // 返回成功
        return [[
            // 证书名称
            'cert_name' => $certInfo->CertName,
            // 到期时间
            'expire_time' => strtotime($certInfo->ExpireTime),
            // 部署时间
            'deploy_time' => strtotime($certInfo->DeployTime),
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
        // 请求对象
        $request = new UpdateDomainConfigRequest();
        $request->Domain = $domain;
        $request->Https = [
            'Switch' => 'on',
        ];

        try{
            // 响应
            $response = $this->handler->UpdateDomainConfig($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(!isset($response->Error)){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误信息
        return [null, new \Exception($response->Error->Message)];
    }

    /**
	 * 启用加速域名
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function startCdnDomain(string $domain)
    {
        // 请求对象
        $request = new StartCdnDomainRequest();
        $request->Domain = $domain;

        try{
            // 响应
            $response = $this->handler->StartCdnDomain($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(!isset($response->Error)){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误信息
        return [null, new \Exception($response->Error->Message)];
    }

    /**
	 * 停用加速域名
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function stopCdnDomain(string $domain)
    {
        // 请求对象
        $request = new StopCdnDomainRequest();
        $request->Domain = $domain;

        try{
            // 响应
            $response = $this->handler->StopCdnDomain($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(!isset($response->Error)){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误信息
        return [null, new \Exception($response->Error->Message)];
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
        // 请求对象
        $request = new UpdateDomainConfigRequest();
        $request->Domain = $domain;
        $request->Cache = [
            'SimpleCache' => [
                'CacheRules' => $cacheRules,
            ]
        ];

        try{
            // 响应
            $response = $this->handler->UpdateDomainConfig($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(!isset($response->Error)){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误信息
        return [null, new \Exception($response->Error->Message)];
    }

    /**
	 * 设置IP访问限频
	 * @access public
	 * @param string $domain
	 * @param int $qps
	 * @return array
	 */
	public function setAccessLimit(string $domain, int $qps = 0)
    {
        // 限频参数
        $ipFreqLimit = [
            'Switch' => 'off',
        ];
        if($qps > 0){
            $ipFreqLimit = [
                'Switch' => 'on',
                'Qps' => $qps,
            ];
        }

        // 请求对象
        $request = new UpdateDomainConfigRequest();
        $request->Domain = $domain;
        $request->IpFreqLimit = $ipFreqLimit;
        
        try{
            // 响应
            $response = $this->handler->UpdateDomainConfig($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(!isset($response->Error)){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误信息
        return [null, new \Exception($response->Error->Message)];
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
        // 配置参数
        $requestHeader = [
            'Switch' => 'off',
        ];
        if(!empty($header)){
            $requestHeaderRules = [];
            foreach($header as $headerName => $headerValue){
                $requestHeaderRules[] = [
                    'HeaderMode' => 'set',
                    'HeaderName' => $headerName,
                    'HeaderValue' => $headerValue,
                    'RuleType' => 'all',
                    'RulePaths' => ['*'],
                ];
            }
            $requestHeader = [
                'Switch' => 'on',
                'HeaderRules' => $requestHeaderRules,
            ];
        }

        // 请求对象
        $request = new UpdateDomainConfigRequest();
        $request->Domain = $domain;
        $request->RequestHeader = $requestHeader;
        
        try{
            // 响应
            $response = $this->handler->UpdateDomainConfig($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(!isset($response->Error)){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误信息
        return [null, new \Exception($response->Error->Message)];
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

        // 请求对象
        $request = new PurgeUrlsCacheRequest();
        $request->Urls = $urls;

        try{
            // 响应
            $response = $this->handler->PurgeUrlsCache($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(!isset($response->Error)){
            // 返回成功
            return [['task_id' => $response->TaskId], null];
        }
        // 返回错误信息
        return [null, new \Exception($response->Error->Message)];
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

        // 请求对象
        $request = new PurgePathCacheRequest();
        $request->Paths = $paths;
        $request->FlushType = 'delete';

        try{
            // 响应
            $response = $this->handler->PurgePathCache($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(!isset($response->Error)){
            // 返回成功
            return [['task_id' => $response->TaskId], null];
        }
        // 返回错误信息
        return [null, new \Exception($response->Error->Message)];
    }

    /**
	 * 查询缓存刷新状态
	 * @access public
	 * @param string $taskId
	 * @return array
	 */
	public function getPurgeStatus($taskId)
    {
        // 请求对象
        $request = new DescribePurgeTasksRequest();
        $request->TaskId = $taskId;

        try{
            // 响应
            $response = $this->handler->DescribePurgeTasks($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(isset($response->Error)){
            // 返回错误信息
            return [null, new \Exception($response->Error->Message)];
        }
        // 获取刷新任务详情
        $purgeTask = $response->PurgeLogs[0];
        // 返回信息
        $status_code = '';
        $status_text = '';
        switch($purgeTask->Status) {
            case 'process':
                $status_code = 'processing';
                $status_text = '刷新中';
                break;
            case 'done':
                $status_code = 'completed';
                $status_text = '刷新完成';
                break;
            case 'fail':
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
        // 请求对象
        $request = new DescribePurgeQuotaRequest();

        try{
            // 响应
            $response = $this->handler->DescribePurgeQuota($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果不存在错误信息
        if(isset($response->Error)){
            // 返回错误信息
            return [null, new \Exception($response->Error->Message)];
        }
        // 获取URL刷新限额
        $urlPurges = $response->UrlPurge;
        $urlPurgeQuota = $urlPurges[0];
        // 遍历
        foreach($urlPurges as $item){
            if($item->Area == 'mainland'){
                $urlPurgeQuota = $item;
                break;
            }
        }
        
        // 获取目录刷新限额
        $pathPurges = $response->PathPurge;
        $pathPurgeQuota = $pathPurges[0];
        // 遍历
        foreach($pathPurges as $item){
            if($item->Area == 'mainland'){
                $pathPurgeQuota = $item;
                break;
            }
        }

        // 返回成功
        return [[
            'url' => [
                'total' => $urlPurgeQuota->Total,
                'remain' => $urlPurgeQuota->Available,
            ],
            'path' => [
                'total' => $pathPurgeQuota->Total,
                'remain' => $pathPurgeQuota->Available,
            ]
        ], null];
    }
}
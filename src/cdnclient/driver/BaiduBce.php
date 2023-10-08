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
use BaiduBce\BceClientConfigOptions;
use BaiduBce\Services\Cdn\CdnClient;

class BaiduBce extends Platform
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
        // AccessKey
        'access_key' => '',
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
        // 实例化要请求产品的 client 对象
        $this->handler = new CdnClient([
            BceClientConfigOptions::CREDENTIALS => [
                'accessKeyId' => $this->options['access_key'],
                'secretAccessKey' => $this->options['secret_key'],
            ],
            BceClientConfigOptions::ENDPOINT => $this->options['endpoint'],
        ]);
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
        $body['origin'] = [
            [
                'peer' => $this->options['origin_server'],
                'host' => $this->options['origin_host'],
            ],
        ];
        $body['form'] = 'image';
        try{
            $response = $this->handler->createDomain($domain, $body);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 操作成功
        if($response->statuscode == 201){
            // 设置回源协议跟随
            $this->handler->setDomainFollowProtocol([
                'value' => '*'
            ]);
            // IP限频
            if($this->options['access_limit'] > 0){
                $this->handler->setDomainAccessLimit($domain, [
                    'enabled' => true,
                    'limit' => $this->options['access_limit'],
                ]);
            }
            // 缓存配置
            if(!empty($this->options['cache_rules'])){
                $this->handler->setDomainCacheTTL($domain, $this->options['cache_rules']);
            }
            // 回源请求头配置
            if(!empty($this->options['request_header'])){
                // 回源请求头规则
                $requestHeaderRules = [];
                foreach($header as $headerName => $headerValue){
                    $requestHeaderRules[] = [
                        'type' => 'origin',
                        'header' => $headerName,
                        'value' => $headerValue,
                        'action' => 'add',
                    ];
                }
                $this->handler->setDomainHttpHeader($domain, $requestHeaderRules);
            }
            // IP黑名单配置
            if(!empty($this->options['black_ip'])){
                // 规则去重
                $ipList = array_unique($this->options['black_ip']);
                $this->handler->setDomainIpAcl($domain, 'black', $ipList);
            }
            // UA黑名单配置
            if(!empty($this->options['black_ua'])){
                // 规则去重
                $uaList = array_unique($this->options['black_ua']);
                $this->handler->setDomainUaAcl($domain, 'black', $uaList);
            }
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误
        return [null, new \Exception($response->message)];
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
        // 如果请求状态码不为200
        if($response->statuscode != 200){
            // 返回错误
            return [null, new \Exception($response->message)];
        }
        // 获取验证方法列表
        $howToVerifys = $response->howToVerify;
        // 使用的验证方法
        $howToVerify = null;
        // 遍历所有的解析
        foreach($howToVerifys as $item){
            // 如果是DNS_TXT验证
            if($item->type == 'DNS_TXT'){
                $howToVerify = $item;
                break;
            }
        }
        // 如果没有DNS_TXT验证方法
        if(is_null($howToVerify)){
            return [null, new \Exception('该域名没有DNS_TXT验证方法')];
        }
        // 获取TXT记录列表
        $txtRecords = $howToVerify->details;
        // 要使用的TXT记录默认第一个
        $txtRecord = $txtRecords[0];
        // 遍历全部记录
        foreach($txtRecords as $item){
            if(false !== strpos($item->verifyDomain, $domain)){
                $txtRecord = $item;
                break;
            }
        }
        // 获取主域名
        $primaryDomain = trim(str_replace($txtRecord->record, '', $txtRecord->verifyDomain), '.');
        // 获取子域名
        $subDomain = trim(str_replace($primaryDomain, '', $domain), '.');

        // txt解析
        $record_txt = [
            // 记录类型
            'type' => 'TXT',
            // 主机记录名
            'record_name' => $txtRecord->record,
            // 记录值
            'record_value' => $txtRecord->targetTxt,
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
            'record_value' => $domain . '.a.bdydns.com',
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
        // 获取响应
        try{
            $response = $this->handler->validDomain($domain);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码不为200
        if($response->statuscode != 200){
            // 返回错误
            return [null, new \Exception($response->message)];
        }
        // 如果验证失败
        if(true === $response->isValid){
            // 返回成功
            return ['域名所有权验证通过', null];
        }
        // 返回错误
        return [null, new \Exception($response->message)];
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
            $response = $this->handler->deleteDomain($domain);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200或者错误信息为域名不存在
        if($response->statuscode == 200 || $response->code == 'NoSuchDomain'){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误
        return [null, new \Exception($response->message)];
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
        // 获取响应
        try{
            $response = $this->handler->setDomainIpAcl($domain, 'black', $ipList);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response->statuscode == 200){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误
        return [null, new \Exception($response->message)];
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
        // 获取响应
        try{
            $response = $this->handler->setDomainUaAcl($domain, 'black', $uaList);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response->statuscode == 200){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误
        return [null, new \Exception($response->message)];
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
            $response = $this->handler->setCertificate($domain, [
                // 证书名称
                'certName' => $certificate['cert_name'],
                // 证书公钥
                'certServerData' => $certificate['cert_public'],
                // 证书私钥
                'certPrivateData' => $certificate['cert_private'],
            ]);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response->statuscode == 200){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误
        return [null, new \Exception($response->message)];
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
            $response = $this->handler->getCertificate($domain);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码不为200或者错误信息为域名不存在
        if($response->statuscode != 200){
            // 返回错误
            return [null, new \Exception($response->message)];
        }

        // 返回成功
        return [[
            // 证书名称
            'cert_name' => $response->certName,
            // 到期时间
            'expire_time' => strtotime($response->certStopTime),
            // 部署时间
            'deploy_time' => strtotime($response->certCreateTime),
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
            $response = $this->handler->delCertificate($domain);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response->statuscode == 200){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误
        return [null, new \Exception($response->message)];
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
            $response = $this->handler->enableDomain($domain);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response->statuscode == 200){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误
        return [null, new \Exception($response->message)];
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
            $response = $this->handler->disableDomain($domain);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response->statuscode == 200){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误
        return [null, new \Exception($response->message)];
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
            $response = $this->handler->setDomainCacheTTL($domain, $cacheRules);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response->statuscode == 200){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误
        return [null, new \Exception($response->message)];
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
        $accessLimit = [
            'enabled' => false,
        ];
        if($qps > 0){
            $accessLimit = [
                'enabled' => true,
                'limit' => $qps
            ];
        }

        // 获取响应
        try{
            $response = $this->handler->setDomainAccessLimit($domain, $accessLimit);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response->statuscode == 200){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误
        return [null, new \Exception($response->message)];
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
        // 回源请求头规则
        $requestHeaderRules = [];
        foreach($header as $headerName => $headerValue){
            $requestHeaderRules[] = [
                'type' => 'origin',
                'header' => $headerName,
                'value' => $headerValue,
                'action' => 'add',
            ];
        }

        // 获取响应
        try{
            $response = $this->handler->setDomainHttpHeader($domain, $requestHeaderRules);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response->statuscode == 200){
            // 返回成功
            return ['操作成功', null];
        }
        // 返回错误
        return [null, new \Exception($response->message)];
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

        // 刷新任务列表
        $tasks = [];
        // 遍历
        foreach($urls as $url){
            $tasks[] = [
                'url' => $url,
                'type' => 'file',
            ];
        }

        // 获取响应
        try{
            $response = $this->handler->purge($tasks);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response->statuscode == 201){
            // 返回成功
            return [['task_id' => $response->id], null];
        }
        // 返回错误
        return [null, new \Exception($response->message)];
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

        // 刷新任务列表
        $tasks = [];
        // 遍历
        foreach($paths as $path){
            $tasks[] = [
                'url' => $path,
                'type' => 'directory',
            ];
        }
        
        // 获取响应
        try{
            $response = $this->handler->purge($tasks);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response->statuscode == 201){
            // 返回成功
            return [['task_id' => $response->id], null];
        }
        // 返回错误
        return [null, new \Exception($response->message)];
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
            $response = $this->handler->listPurgeStatus($taskId);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response->statuscode != 200){
            // 返回错误
            return [null, new \Exception($response->message)];
        }
        // 获取刷新任务详情
        $purgeTask = $response->details[0];
        // 返回信息
        $status_code = '';
        $status_text = '';
        switch($purgeTask->status) {
            case 'in-progress':
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
            $response = $this->handler->listQuota();
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($response->statuscode != 200){
            // 返回错误
            return [null, new \Exception($response->message)];
        }

        // 返回成功
        return [[
            'url' => [
                'total' => $response->urlQuota,
                'remain' => $response->urlRemain,
            ],
            'path' => [
                'total' => $response->dirQuota,
                'remain' => $response->dirRemain,
            ]
        ], null];
    }
}
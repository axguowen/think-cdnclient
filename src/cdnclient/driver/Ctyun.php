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
        // 返回错误
        return [null, new \Exception('暂不支持该功能')];
    }

    /**
	 * 查询域名归属权解析记录信息
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function getRecord(string $domain)
    {
        // 返回错误
        return [null, new \Exception('暂不支持该功能')];
	}

    /**
	 * 验证域名归属权
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function verifyRecord(string $domain)
    {
        // 返回错误
        return [null, new \Exception('暂不支持该功能')];
    }

    /**
	 * 删除域名
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function deleteCdnDomain(string $domain)
    {
        // 返回错误
        return [null, new \Exception('暂不支持该功能')];
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
        // 返回错误
        return [null, new \Exception('暂不支持该功能')];
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
        // 返回错误
        return [null, new \Exception('暂不支持该功能')];
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
        // 返回错误
        return [null, new \Exception('暂不支持该功能')];
    }

    /**
	 * 查询域名证书
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function getCertificate(string $domain)
    {
        // 返回错误
        return [null, new \Exception('暂不支持该功能')];
    }

    /**
	 * 删除域名证书
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function deleteCertificate(string $domain)
    {
        // 返回错误
        return [null, new \Exception('暂不支持该功能')];
    }

    /**
	 * 启用加速域名
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function startCdnDomain(string $domain)
    {
        // 返回错误
        return [null, new \Exception('暂不支持该功能')];
    }

    /**
	 * 停用加速域名
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function stopCdnDomain(string $domain)
    {
        // 返回错误
        return [null, new \Exception('暂不支持该功能')];
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
        // 返回错误
        return [null, new \Exception('暂不支持该功能')];
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
        // 返回错误
        return [null, new \Exception('暂不支持该功能')];
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
        // 返回错误
        return [null, new \Exception('暂不支持该功能')];
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
            $purgeResponse = $this->handler->refreshManageCreate($urls, 1);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($purgeResponse['code'] == 100000){
            // 获取响应结果
            $result = $purgeResponse['result'][0];
            // 返回成功
            return [['task_id' => $result['task_id']], null];
        }
        // 返回错误
        return [null, new \Exception($purgeResponse['message'])];
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
            $purgeResponse = $this->handler->refreshManageCreate($urls, 2);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($purgeResponse['code'] == 100000){
            // 获取响应结果
            $result = $purgeResponse['result'][0];
            // 返回成功
            return [['task_id' => $result['task_id']], null];
        }
        // 返回错误
        return [null, new \Exception($purgeResponse['message'])];
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
            $refreshManageResponse = $this->handler->refreshManageQuery([
                'type' => 2,
                'task_id' => $taskId,
                'page_size' => 1,
            ]);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($refreshManageResponse['code'] != 100000){
            // 返回错误
            return [null, new \Exception($refreshManageResponse['message'])];
        }
        // 获取刷新任务详情
        $purgeTask = $refreshManageResponse['result'][0];
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
            $listQuotaResponse = $this->handler->refreshManageQuota();
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
        // 如果请求状态码为200
        if($listQuotaResponse['code'] != 100000){
            // 返回错误
            return [null, new \Exception($listQuotaResponse['message'])];
        }
        // 获取限额数据
        $purgeQuota = $listQuotaResponse['result']['quotas'][0];
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
}
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
use AlibabaCloud\SDK\Cdn\V20180510\Cdn;
use AlibabaCloud\SDK\Cdn\V20180510\Models\DescribeCdnDomainDetailRequest;
use AlibabaCloud\SDK\Cdn\V20180510\Models\CheckCdnDomainICPRequest;

class Aliyun extends Platform
{
    /**
     * 平台句柄
     * @var Cdn
     */
    protected $handler;

	/**
     * 平台配置参数
     * @var array
     */
    protected $options = [
        // 公钥
        'access_id' => '',
        // 私钥
        'access_secret' => '',
        // 服务接入点
        'endpoint' => '',
    ];

    /**
     * 创建句柄
     * @access protected
     * @return $this
     */
    protected function makeHandler()
    {
        // 实例化认证对象
        $config = new \Darabonba\OpenApi\Models\Config([
            'accessKeyId' => $this->options['access_id'],
            'accessKeySecret' => $this->options['access_secret'],
            'endpoint' => $this->options['endpoint'] ?: 'cdn.aliyuncs.com',
        ]);
        // 实例化要请求产品的 client 对象
        $this->handler = new Cdn($config);
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
        return [null, new \Exception('暂不支持该方法')];
    }

    /**
	 * 查询域名归属权解析记录信息
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function getRecord(string $domain)
    {
        return [null, new \Exception('暂不支持该方法')];
    }

    /**
	 * 验证域名归属权
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function verifyRecord(string $domain)
    {
        return [null, new \Exception('暂不支持该方法')];
    }

    /**
	 * 删除域名
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function deleteCdnDomain(string $domain)
    {
        return [null, new \Exception('暂不支持该方法')];
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
        return [null, new \Exception('暂不支持该方法')];
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
        return [null, new \Exception('暂不支持该方法')];
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
        return [null, new \Exception('暂不支持该方法')];
    }

    /**
	 * 查询域名证书
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function getCertificate(string $domain)
    {
        return [null, new \Exception('暂不支持该方法')];
    }

    /**
	 * 删除域名证书
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function deleteCertificate(string $domain)
    {
        return [null, new \Exception('暂不支持该方法')];
    }

    /**
	 * 删除域名证书
	 * @access public
	 * @param string $certName
	 * @return array
	 */
	public function destroyCertificate(string $certName)
    {
        return [null, new \Exception('暂不支持该方法')];
    }

    /**
	 * 启用加速域名
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function startCdnDomain(string $domain)
    {
        return [null, new \Exception('暂不支持该方法')];
    }

    /**
	 * 停用加速域名
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function stopCdnDomain(string $domain)
    {
        return [null, new \Exception('暂不支持该方法')];
    }

    /**
	 * 设置缓存规则
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function setCacheRules(string $domain, array $cacheRules)
    {
        return [null, new \Exception('暂不支持该方法')];
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
        return [null, new \Exception('暂不支持该方法')];
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
        return [null, new \Exception('暂不支持该方法')];
    }

    /**
	 * 刷新文件缓存
	 * @access public
	 * @param array|string $urls
	 * @return array
	 */
	public function purgeUrlsCache($urls = [])
    {
        return [null, new \Exception('暂不支持该方法')];
    }

    /**
	 * 刷新目录缓存
	 * @access public
	 * @param array|string $path
	 * @return array
	 */
	public function purgePathsCache($paths = [])
    {
        return [null, new \Exception('暂不支持该方法')];
    }

    /**
	 * 查询缓存刷新状态
	 * @access public
	 * @param string $taskId
	 * @return array
	 */
	public function getPurgeStatus($taskId)
    {
        return [null, new \Exception('暂不支持该方法')];
    }

    /**
	 * 查询缓存刷新限额
	 * @access public
	 * @return array
	 */
	public function getPurgeQuota()
    {
        return [null, new \Exception('暂不支持该方法')];
    }

    /**
	 * 查询域名备案状态
	 * @access public
	 * @param string $domain
	 * @return array
	 */
	public function checkDomainICP(string $domain)
    {
        // 请求对象
        $request = new CheckCdnDomainICPRequest();
        $request->domainName = $domain;
        try{
            // 响应
            $response = $this->handler->checkCdnDomainICP($request);
            // 备案状态
            $status = $response->body->status == 'DomainIsRegistration' ? 1 : 0;
            // 返回
            return [['status' => $status], null];
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }

    /**
     * 查询域名是否存在
     * @access public
     * @param string $domain
     * @return array
     */
    public function isExist($domain)
    {
        // 存在状态
        $exist = false;
        // 请求对象
        $request = new DescribeCdnDomainDetailRequest();
        $request->domainName = $domain;
        try{
            // 响应
            $response = $this->handler->DescribeCdnDomainDetail($request);
            $exist = true;
        } catch (\Exception $e) {
            if($e->getCode() != 'InvalidDomain.NotFound') {
                // 返回错误
                return [null, $e];
            }
        }
        // 返回成功
        return [['exist' => $exist], null];
    }
}
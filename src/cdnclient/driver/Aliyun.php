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
            $status = $response->Body->Status == 'DomainIsRegistration' ? 1 : 0;
            // 返回
            return [['status' => $status], null];
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }
    }
}
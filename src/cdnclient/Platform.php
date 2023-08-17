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

namespace think\cdnclient;

/**
 * 平台抽象类
 */
abstract class Platform implements PlatformInterface
{
    /**
     * 驱动句柄
     * @var object
     */
    protected $handler = null;

	/**
     * 平台配置参数
     * @var array
     */
	protected $options = [];

    /**
     * 架构函数
     * @access public
     * @param array $options 平台配置参数
     */
    public function __construct(array $options = [])
    {
        // 合并配置参数
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        // 创建句柄
        $this->makeHandler();
    }

	/**
     * 动态设置平台配置参数
     * @access public
     * @param array $options 平台配置
     * @return $this
     */
    public function setConfig(array $options)
    {
        // 合并配置
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        // 返回
        return $this->makeHandler();
    }

	/**
     * 创建句柄
     * @access protected
     * @return $this
     */
    protected function makeHandler()
    {
        // 返回
        return $this;
    }
    
    /**
     * 返回句柄对象，可执行其它高级方法
     *
     * @access public
     * @return object
     */
    public function handler()
    {
        return $this->handler;
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->handler, $method], $args);
    }
}

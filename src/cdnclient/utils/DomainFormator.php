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

namespace think\cdnclient\utils;

class DomainFormator
{
    // 域名后缀列表
    const SUFFIX_VALID = [
        'com.cn',
        'net.cn',
        'com',
        'net',
        'cn',
        'info',
        'cc',
        'top',
        'xyz',
        'work',
        'asia',
        'tech',
        'pro',
        'co',
        'online',
        'icu',
        'shop',
        'club',
        'vip',
        'ltd',
        'site',
        'ink',
        'pub',
        'fit',
        'biz',
        'store',
        'pw',
        'org',
        'la',
        'hk',
        'name',
        'wang',
        'group',
        'in',
        'live',
    ];

    /**
     * 获取域名格式化信息
     * @access public
     * @param string $domain
     * @return array
     */
    public static function format($domain)
    {
        // 如果域名不合法
        if(true !== self::isValid($domain)){
            return [null, new \Exception('域名不合法')];
        }
        // 替换规则
        $prefixReplaceRules = '/[^.]+\.(' . implode('|', self::SUFFIX_VALID) . ')$/';
        // 获取前缀
        $prefix = trim(preg_replace($prefixReplaceRules, '', $domain), '.');
        // 子域名
        $sub_domain = $prefix;
        // 如果前缀是星号
        if($prefix == '*'){
            $prefix = '\\' . $prefix;
        }
        // 替换规则
        $primaryReplaceRules = '/^'. $prefix .'\./';
        // 替换
        $primary = preg_replace($primaryReplaceRules, '', $domain);
        // 过滤
        $primary = trim($primary, '.');
        // 返回
        return [[
            'primary' => $primary,
            'sub_domain' => $sub_domain,
        ], null];
    }

    /**
     * 获取域名的前缀
     * @access public
     * @param string $domain
     * @return string
     */
    public static function prefix($domain)
    {
        // 校验域名不合法
        if(true !== self::isValid($domain)){
            return [null, new \Exception('域名不合法')];
        }
        // 替换规则
        $replaceRules = '/[^.]+\.(' . implode('|', self::SUFFIX_VALID) . ')$/';
        // 获取前缀
        $refix = trim(preg_replace($replaceRules, '', $domain), '.');
        // 返回
	    return [$refix, null];
    }

    /**
     * 获取域名的主域
     * @access public
     * @param string $domain
     * @return string
     */
    public static function primary($domain)
    {
        // 获取域名前缀结果
        $getPrefixResult = self::prefix($domain);
        // 如果错误
        if(is_null($getPrefixResult[0])){
            return $getPrefixResult;
        }
        // 获取前缀
        $prefix = $getPrefixResult[0];
        // 如果前缀是星号
        if($prefix == '*'){
            $prefix = '\\' . $prefix;
        }
        // 替换规则
        $replaceRules = '/^'. $prefix .'\./';
        // 替换
        $primary = preg_replace($replaceRules, '', $domain);
        // 过滤
        $primary = trim($primary, '.');
        // 返回
        return [$primary, null];
    }

    /**
     * 判断域名是否合法
     * @access public
     * @param string $domain
     * @return string
     */
    public static function isValid($domain)
    {
        // 域名验证正则
        $pregRules = '/\.(' . implode('|', self::SUFFIX_VALID) . ')$/';
        // 正则校验
        if(preg_match($pregRules, $domain)){
            // 返回
            return true;
        }
        // 返回
        return false;
    }
}

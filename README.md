# ThinkPHP CDN 客户端

一个简单的 ThinkPHP CDN 客户端


## 安装
~~~
composer require axguowen/think-cdnclient
~~~

## 使用

首先配置config目录下的cdnclient.php配置文件。

### 简单使用
~~~php
// 添加域名
$addCdnDomain = \think\facade\CdnClient::addCdnDomain('xxx.xxx.com');
// 如果成功
if(!is_null($addCdnDomain[0])){
    var_dump($addCdnDomain);
}
// 失败
else{
    // 错误信息
    echo $addCdnDomain[1]->getMessage();
}
~~~

### 高级使用
~~~php
// 动态切换平台
$cdnClient = \think\facade\CdnClient::platform('baidu');

// 添加域名
$addCdnDomain = $cdnClient->addCdnDomain('xxx.xxx.com');
if(!is_null($addCdnDomain[0])){
    var_dump($addCdnDomain);
}
else{
    echo $addCdnDomain[1]->getMessage();
}

// 查询域名归属权解析记录信息
$getRecord = $cdnClient->getRecord('xxx.xxx.com');
if(!is_null($getRecord[0])){
    var_dump($getRecord);
}
else{
    echo $getRecord[1]->getMessage();
}

// 验证域名归属权
$verifyRecord = $cdnClient->verifyRecord('xxx.xxx.com');
if(!is_null($verifyRecord[0])){
    var_dump($verifyRecord);
}
else{
    echo $verifyRecord[1]->getMessage();
}

// 删除域名
$deleteCdnDomain = $cdnClient->deleteCdnDomain('xxx.xxx.com');
if(!is_null($deleteCdnDomain[0])){
    var_dump($deleteCdnDomain);
}
else{
    echo $deleteCdnDomain[1]->getMessage();
}

// 设置IP黑名单
$setIpBlackList = $cdnClient->setIpBlackList('xxx.xxx.com', [
    '192.168.1.1',
    '192.168.1.2',
]);
if(!is_null($setIpBlackList[0])){
    var_dump($setIpBlackList);
}
else{
    echo $setIpBlackList[1]->getMessage();
}

// 设置UA黑名单
$setUaBlackList = $cdnClient->setUaBlackList('xxx.xxx.com', [
    'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.3925.36 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.3171.28 Safari/537.36',
]);
if(!is_null($setUaBlackList[0])){
    var_dump($setUaBlackList);
}
else{
    echo $setUaBlackList[1]->getMessage();
}

// 设置域名证书
$setCertificate = $cdnClient->setCertificate('xxx.xxx.com', [
    'cert_name' => '我的证书',
    'cert_public' => '-----证书内容------',
    'cert_private' => '-----证书私钥------',
]);
if(!is_null($setCertificate[0])){
    var_dump($setCertificate);
}
else{
    echo $setCertificate[1]->getMessage();
}

// 查询证书信息
$getCertificate = $cdnClient->getCertificate('xxx.xxx.com');
if(!is_null($getCertificate[0])){
    var_dump($getCertificate);
}
else{
    echo $getCertificate[1]->getMessage();
}

// 删除证书
$deleteCertificate = $cdnClient->deleteCertificate('xxx.xxx.com');
if(!is_null($deleteCertificate[0])){
    var_dump($deleteCertificate);
}
else{
    echo $deleteCertificate[1]->getMessage();
}

// 启用加速域名
$startCdnDomain = $cdnClient->startCdnDomain('xxx.xxx.com');
if(!is_null($startCdnDomain[0])){
    var_dump($startCdnDomain);
}
else{
    echo $startCdnDomain[1]->getMessage();
}

// 停用加速域名
$stopCdnDomain = $cdnClient->stopCdnDomain('xxx.xxx.com');
if(!is_null($stopCdnDomain[0])){
    var_dump($stopCdnDomain);
}
else{
    echo $stopCdnDomain[1]->getMessage();
}

// 设置缓存规则
$setCacheRules = $cdnClient->setCacheRules('xxx.xxx.com');
if(!is_null($setCacheRules[0])){
    var_dump($setCacheRules);
}
else{
    echo $setCacheRules[1]->getMessage();
}

// 设置IP访问限频
$qps = 10;
$setAccessLimit = $cdnClient->setAccessLimit('xxx.xxx.com', $qps);
if(!is_null($setAccessLimit[0])){
    var_dump($setAccessLimit);
}
else{
    echo $setAccessLimit[1]->getMessage();
}

// 设置回源请求头
$requestHeader = [
    'X-MYHEADER' => 'myheader',
    'X-MYHEADER-OTHER' => 'myheaderother',
];
$setAccessLimit = $cdnClient->setRequestHeader('xxx.xxx.com', $requestHeader);
if(!is_null($setAccessLimit[0])){
    var_dump($setAccessLimit);
}
else{
    echo $setAccessLimit[1]->getMessage();
}

// 刷新文件缓存
$purgeUrlsCache = $cdnClient->purgeUrlsCache(['url_1', 'url_2']);
if(!is_null($purgeUrlsCache[0])){
    var_dump($purgeUrlsCache);
}
else{
    echo $purgeUrlsCache[1]->getMessage();
}

// 刷新目录缓存
$purgePathCache = $cdnClient->purgePathsCache(['path_1', 'path_2']);
if(!is_null($purgePathCache[0])){
    var_dump($purgePathCache);
}
else{
    echo $purgePathCache[1]->getMessage();
}

// 查询缓存刷新状态
$taskId = 'xxxxxxxxx';
$getPurgeStatus = $cdnClient->getPurgeStatus($taskId);
if(!is_null($getPurgeStatus[0])){
    var_dump($getPurgeStatus);
}
else{
    echo $getPurgeStatus[1]->getMessage();
}

// 查询缓存刷新限额
$getPurgeQuota = $cdnClient->getPurgeQuota($taskId);
if(!is_null($getPurgeQuota[0])){
    var_dump($getPurgeQuota);
}
else{
    echo $getPurgeQuota[1]->getMessage();
}


~~~
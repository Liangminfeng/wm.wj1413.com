<?php

/**
 * ECTouch E-Commerce Project
 * ============================================================================
 * Copyright (c) 2014-2016 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * This is NOT a freeware, use is subject to license terms
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/license )
 * ----------------------------------------------------------------------------
 */

// 部署模式(0:单机|1:分布式)
define('DEPLOY_MODE', 0);
define('STATICS_URL', 'http://cn-ectouch.oss-cn-hangzhou.aliyuncs.com/');
$GLOBALS['DEPLOY_CONF'] = array(
    /* 上传设置 */
    'UPLOAD_CONF' => array(
        'OSS_ACCESS_ID' => '', //您从OSS获得的AccessKeyId
        'OSS_ACCESS_KEY' => '', //您从OSS获得的AccessKeySecret
        'OSS_ENDPOINT' => 'oss-cn-hangzhou.aliyuncs.com', //您选定的OSS数据中心访问域名
        'OSS_BUCKET' => 'cn-ectouch', //空间名称
    )
);

// 兼容运行环境
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PASS', 'wanmao123');
define('REDIS_PORT','6657');

// 独立运行环境
define('EC_CHARSET', 'utf-8');
define('ADMIN_PATH', 'vmistyleback');
define('AUTH_KEY', 'this is a key');
define('OLD_AUTH_KEY', '');
define('API_URL','http://wm.wj1413.com/');
define('API_TIME', '2020-11-21 14:04:57');
define('RUN_ON_ECS', false);
define('DEFAULT_TIMEZONE', 'PRC');
define('ENV', 'SMS_DEBUG');
define('SECRET_KEY', 'e74841fde1dded5988f4a9a4aaa41790');
define('APP_DEBUG',true);
define('WECHAT_MODE', true);

$db_config = ROOT_PATH . 'data/database.php';
if (file_exists($db_config)) {
    return require($db_config);
}else{
    header('location: ./install');
    exit();
}

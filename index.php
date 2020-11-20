<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：index.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch项目入口文件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

define('IN_ECTOUCH', true);
// echo 'openid:'.$_SESSION['openid'];
// echo 'user_id:'.$_SESSION['user_id'];

require dirname(__FILE__) . '/vendor/autoload.php';
require dirname(__FILE__) . '/include/bootstrap.php';

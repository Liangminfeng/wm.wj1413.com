<?php
defined('IN_ECTOUCH') or die('Deny Access');

$payment_lang = BASE_PATH . 'languages/' .C('lang'). '/payment/mpg.php';


if (file_exists($payment_lang)) {
    global $_LANG;
    include_once($payment_lang);
}


/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE) {
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code'] = "mpg";

    /* 描述对应的语言项 */
    $modules[$i]['desc'] = 'mpg_desc';

    /* 是否支持货到付款 */
    $modules[$i]['is_cod'] = '0';

    /* 是否支持在线支付 */
    $modules[$i]['is_online'] = '1';

    /* 作者 */
    $modules[$i]['author'] = 'MPG';

    /* 网址 */
    $modules[$i]['website'] = 'https://www.spgateway.com/';

    /* 版本号 */
    $modules[$i]['version'] = '1.0';

    /* 配置信息 */
    $modules[$i]['config'] = array(
        array('name' => 'mpg_using', 'type' => 'select', 'value' => '0'),
        array('name' => 'mpg_merchantid', 'type' => 'text', 'value' => 'MS33835384'),
        array('name' => 'mpg_key', 'type' => 'text', 'value' => 'CX9g0cYiLsHXC315nPztZwH0FEsEk2qV'),
        array('name' => 'mpg_iv', 'type' => 'text', 'value' => 'rcLZj2gyKysTXazF'),
        array('name' => 'pay_button', 'type' => 'text', 'value' => '前往付款')
    );

    return;
}
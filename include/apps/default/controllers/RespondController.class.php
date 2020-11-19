<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：RespondController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 支付应答控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class RespondController extends CommonController
{

    private $data;

    public function __construct()
    {
        parent::__construct();
        // 获取参数
        $this->data = array(
            'code' => I('get.code'),
            'type' => I('get.type')
        );
    }

    // 发送
    public function index()
    {

        /* 判断是否启用 */
        $condition['pay_code'] = $this->data['code'];
        $condition['enabled'] = 1;
        $enabled = $this->model->table('payment')->where($condition)->count();
        if ($enabled == 0) {
            $msg = L('pay_disabled');
        } else {
            // 微信h5中间页面
            if (isset($_GET['style']) && $this->data['code'] == 'wxpay' && $_GET['style'] == 'wxh5') {
                
                $log_id = intval($_GET['log_id']);
                $url = url('respond/wxh5', array('code' => 'wxpay', 'log_id' => $log_id));
                $this->redirect($url);
            }
           
            $plugin_file = ADDONS_PATH.'payment/' . $this->data['code'] . '.php';
            if (file_exists($plugin_file)) {
                
                include_once($plugin_file);
                $payobj = new $this->data['code']();
                // 处理异步请求
            
                if($this->data['type'] == 'notify'){
                    @$payobj->notify($this->data);
                }
                $response = @$payobj->callback($this->data);
                $msg = $response ? L('pay_success') : L('pay_fail');
                if($response&&empty($_SESSION['user_vip'])){
                    $order = model('Payment')->get_payment($_GET['log_id']);
                    if($order&&$order['pay_status']==PS_PAYED){
                       if($order['order_type']===1 ||$order['order_type']===9){
                           $_SESSION['user_vip']=1;
                       }
                    }
                    
                }
                
            } else {
                $msg = L('pay_not_exist');
            }
        }
        
        // 根据不同订单类型（普通、充值） 跳转
        if (isset($_GET['log_id']) && !empty($_GET['log_id'])) {
            $log_id = intval($_GET['log_id']);
            $pay_log = $this->model->table('pay_log')->field('order_type, order_id')->where(array('log_id' => $log_id))->find(); // order_type 0 普通订单, 1 会员充值订单
            if ($pay_log['order_type'] == 0) {
                $order_url = url('user/order_detail', array('order_id' => $pay_log['order_id']));

            } elseif ($pay_log['order_type'] == 1) {
                $order_url = url('user/account_detail');
            }
        } else {
            $order_url = url('user/order_list'); // 订单列表
        }


       
        $order_url = str_replace('respond', 'index', $order_url);
        //显示页面
        if($_GET['out_trade_no']){
             $order = model('Order')->order_info(0, $_GET['out_trade_no']);
        }else{
             $order = model('Order')->order_info($pay_log['order_id']);
        }
        $this->assign('order',$order);
        $guessyourlike = model('Goods')->get_best_goods();
        $this->assign('message', $msg);
        $this->assign('guessyourlike',$guessyourlike);
        $this->assign('vip',$_SESSION['user_vip']);
        $this->assign('order_url', $order_url);
        $this->assign('shop_url', __URL__);
        $cart_goods = model('Order')->order_goods($pay_log['order_id']);
        if($order['pay_status']==2){
              $share_link = __URL__ . "/index.php?m=default&c=goods&a=index&id=".$cart_goods[0][goods_id]."&u=".$_SESSION['user_id'];
               $this->assign('share_link',$share_link);
             $user_info = model('Order')->user_info($order['user_id']);
            $this->assign('pre_get_amount',0.66*$order['goods_amount']);
            $this->assign('user_info',$user_info);
          
            if($order['order_type']==10){
                //列车产品，拆单
             
                $this->progressOrder($order);
            }
            
            $result = model('Goods')->get_goods_info($cart_goods['0']['goods_id']);
            $user_rank = model('Users')->getuserrank($_SESSION['user_id']);
            
            if($user_rank){
                /*重购或者零售*/
               $this->assign('order_sale_type',0); 
               }else{
                    /*入团*/
                    $this->assign('order_sale_type',$cart_goods['0']['goods_vip']);
               }
               if($order['train_id']){
                    $traininfo = model('Train')->getTrainInfo($order['train_id']);
                  $this->assign('traininfo',$traininfo);
               }
            
            $this->assign('pay_status',$order['pay_status']);
            

            $this->assign('share_title',$cart_goods['0']['goods_name']);
            $this->assign('share_description',$result['goods_brief']);
            $this->assign('share_pic',$cart_goods['0']['goods_thumb']);
            $this->assign('vipname',model('Users')->getuserkvipname($order['vip']));
            $this->display('respond.dwt');
        }else{
            $this->display('respond_fail.dwt');
        }
        
    }

    // 发送
    public function index_old()
    {
        
        /* 判断是否启用 */
        $condition['pay_code'] = $this->data['code'];
        
        $condition['enabled'] = 1;
        $enabled = $this->model->table('payment')
            ->where($condition)
            ->count();
        // Status=MPG03009
        $order = model('Base')->model->table('order_info')
            ->where('order_id = ' . $_GET['order_id'])
            ->find();
        if ($order['pay_status'] == 2 || $order['order_status'] == 2) {
            // 已付款或者已取消的订单
            show_message("訂單狀態異常",'首頁',url("index/index"));
        }
        if ($this->data['code'] == "paypal") {
            $req = 'cmd=_notify-validate'; // 验证请求
            foreach ($_POST as $k => $v) {
                $v = urlencode(stripslashes($v));
                $req .= "&{$k}={$v}";
            }
            // array(22) { ["payer_email"]=> string(27) "linaimee683-buyer@yahoo.com" ["payer_id"]=> string(13) "BB5M5NDEWGPAL" ["payer_status"]=> string(8) "VERIFIED" ["first_name"]=> string(4) "test" ["last_name"]=> string(5) "buyer" ["txn_id"]=> string(17) "5WE78355SL960203C" ["mc_currency"]=> string(3) "TWD" ["mc_gross"]=> string(6) "111.00" ["protection_eligibility"]=> string(8) "ELIGIBLE" ["payment_gross"]=> string(6) "111.00" ["payment_status"]=> string(7) "Pending" ["pending_reason"]=> string(14) "multi_currency" ["payment_type"]=> string(7) "instant" ["item_name"]=> string(43) "您的拓客网站订单2018052917080208690" ["quantity"]=> string(1) "1" ["txn_type"]=> string(10) "web_accept" ["payment_date"]=> string(20) "2018-05-29T09:08:48Z" ["business"]=> string(33) "linaimee683-facilitator@yahoo.com" ["receiver_id"]=> string(13) "9QHXXK55J8TPS" ["notify_version"]=> string(11) "UNVERSIONED" ["invoice"]=> string(19) "2018052917080208690" ["verify_sign"]=> string(56) "ANQ-SoLP8ghQC5IsaxLVDxbpzxN6Ak1EXe0DQgZE1EzSHRHC3y6uH2JE" }
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.paypal.com/cgi-bin/webscr');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            $res = curl_exec($ch);
            curl_close($ch);
            
            if ($res && ! empty($order)) {
                if (strcmp($res, 'VERIFIED') == 0) {
                    if ($_POST['payment_status'] == "Completed" || $_POST['payment_status'] == "Pending") {
                        $this->progressOrder($order);
                    }
                } else {
                    exit('fail');
                }
            }
        } elseif ($this->data['code'] == "mpg") {
            $order_id = $_REQUEST['MerchantOrderNo']; // 商店訂單編號
            $trade_no = $_REQUEST['TradeNo']; // 智付通交易序號
                                              // $log_id = $_REQUEST['log_id']; //ECShop支付單編號
            $amt = $_REQUEST['Amt']; // 交易金額
                                     
            // CheckCode 檢查碼
            $check_code = array(
                "MerchantID" => "MS351491967",
                "Amt" => $amt,
                "MerchantOrderNo" => $order_id,
                "TradeNo" => $trade_no
            );
            $payment['mpg_iv'] = 'Lhr7vgFwnt2rvIhM';
            $payment['mpg_key'] = 'PPK8Vhs8vtlJYD8cvIWaGBj9pEuCWvvn';
            ksort($check_code);
            $check_str = http_build_query($check_code, '', '&');
            $CheckCode = "HashIV=" . trim($payment['mpg_iv']) . "&" . $check_str . "&" . "HashKey=" . trim($payment['mpg_key']);
            $CheckCode = strtoupper(hash("sha256", $CheckCode));
            
            // 校验
            if ($_REQUEST['CheckCode'] != $CheckCode) {
                show_message("交易校驗失敗",'首頁',url("index/index"));
            }
            if ($_POST['Status'] == "SUCCESS") {
                $this->progressOrder($order);
            } else {
                
                $msg = L('pay_fail');
                
                $order_url = $order_url = url('user/order_list');
                $this->assign('message', $msg);
                $this->assign('order_url', $order_url);
                $this->assign('shop_url', __URL__);
                $this->display('respond_fail.dwt');
            }
        } else {
            
            if ($enabled == 0) {
                $msg = L('pay_disabled');
            } else {
                // 微信h5中间页面
                if (isset($_GET['style']) && $this->data['code'] == 'wxpay' && $_GET['style'] == 'wxh5') {
                    $log_id = intval($_GET['log_id']);
                    $url = url('respond/wxh5', array(
                        'code' => 'wxpay',
                        'log_id' => $log_id
                    ));
                    $this->redirect($url);
                }
                
                $plugin_file = ADDONS_PATH . 'payment/' . $this->data['code'] . '.php';
                if (file_exists($plugin_file)) {
                    include_once ($plugin_file);
                    $payobj = new $this->data['code']();
                    // 处理异步请求
                    if ($this->data['type'] == 'notify') {
                        @$payobj->notify($this->data);
                    }
                    $msg = (@$payobj->callback($this->data)) ? L('pay_success') : L('pay_fail');
                } else {
                    $msg = L('pay_not_exist');
                }
            }
            // 根据不同订单类型（普通、充值） 跳转
            if (isset($_GET['log_id']) && ! empty($_GET['log_id'])) {
                $log_id = intval($_GET['log_id']);
                $pay_log = $this->model->table('pay_log')
                    ->field('order_type, order_id')
                    ->where(array(
                    'log_id' => $log_id
                ))
                    ->find(); // order_type 0 普通订单, 1 会员充值订单
                if ($pay_log['order_type'] == 0) {
                    $order_url = url('user/order_list');
                } elseif ($pay_log['order_type'] == 1) {
                    $order_url = url('user/account_detail');
                }
            } else {
                $order_url = url('user/order_list'); // 订单列表
            }
            $order_url = str_replace('respond', 'index', $order_url);
            // 显示页面
            
            $guessyourlike = model('Goods')->get_best_goods();
            $this->assign('guessyourlike', $guessyourlike);
            $this->assign('message', $msg);
            $this->assign('vip',$_SESSION['user_vip']);
            $this->assign('order_url', $order_url);
            $this->assign('shop_url', __URL__);
         
            $user_info = model('Order')->user_info($order['user_id']);
            $this->assign('pre_get_amount',0.66*$order['goods_amount']);
            $this->assign('user_info',$user_info);
            $this->assign('vipname',model('Users')->getuserkvipname($order['vip']));
            $this->display('respond.dwt');
        }
    }

    /**
     * 微信支付h5同步通知中间页面
     *
     * @return
     *
     */
    /**
     * 微信支付h5同步通知中间页面
     * @return
     */
    public function wxh5()
    {
        //显示页面
        if (isset($_GET) && !empty($_GET['log_id'])) {
            $log_id = intval($_GET['log_id']);
            $pay_log = $this->model->table('pay_log')->field('order_type, order_id')->where(array('log_id' => $log_id))->find(); // order_type 0 普通订单, 1 会员充值订单
            if ($pay_log['order_type'] == 0) {
                $order_url = url('user/order_detail', array('order_id' => $pay_log['order_id']));
            } elseif ($pay_log['order_type'] == 1) {
                $order_url = url('user/account_detail');
            }
            $order_url = str_replace('respond', 'index', $order_url);

            $repond_url = __URL__ . "/index.php?c=respond&code=" .$this->data['code']. "&status=1&log_id=".$log_id;
        } else {
            $repond_url = __URL__ . "/index.php?c=respond&code=" .$this->data['code']. "&status=0";
        }
        $is_wxh5 = ($this->data['code'] == 'wxpay' && !is_wechat_browser()) ? 1 : 0;
        $this->assign('is_wxh5', $is_wxh5);
        $this->assign('repond_url', $repond_url);

        $this->assign('order_url', $order_url);
        $this->display('respond_wxh5.dwt');
    }

    private function progressOrder($order_info)
    {
        // 付款成功$order = model('Order')->order_info($order_id);
        // 付款成功后查看该笔订单的商品是否有列车商品，如果有进行拆单，如果没有的话就不拆单
        // 拆单原理1：如果有1个普通商品和列车商品2个，那么拆成3个订单
        
        // 增加等级积分
        $integral = model('ClipsBase')->integral_to_give($order_info);
        //model('ClipsBase')->log_account_change($order_info['user_id'], 0, 0, intval($integral['rank_points']), 0, sprintf(L('pay_order'), $order_info['order_sn']));
        model('ClipsBase')->new_log_account_change($order_info ['user_id'], 0,sprintf(L('pay_order'), $order_info ['order_sn']),ACT_OTHER,5);
        //处理订单
     
        progressOrder($order_info,$_GET["log_id"]);
        
        
        // $order_url = $order_url = url('user/order_list');
        // $guessyourlike = model('Goods')->get_best_goods();
        // $this->assign('guessyourlike', $guessyourlike);
        // $this->assign('message', $msg);
        // $this->assign('vip',$_SESSION['user_vip']);
        // $this->assign('order_url', $order_url);
        // $this->assign('shop_url', __URL__);
        
        // $this->display('respond.dwt');
    }
    
    public function wxpaynotify(){
        $this->data = array(
            'code' => 'wxpay',
            'type' => 'notify'
        );
        $this->index();
    }
    public function alipaynotify(){
        $this->data = array(
            'code' => 'alipay',
            'type' => 'notify'
        );
        $this->index();
    }
    public function alipayreturn(){
        $this->data = array(
            'code' => 'alipay'
          
        );
        $this->index();
    }
    public function wxpayreturn(){
        $this->data = array(
            'code' => 'wxpay'
          
        );
        $this->index();
    }
    
}
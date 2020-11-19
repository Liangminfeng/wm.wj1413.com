<?php


/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：IndexController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch首页控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class JoinController extends CommonController {

    /** 
     * 首页信息
     */

    public function index() {
            
        $this->display('joinus.dwt');
    }
    
    public function joinus(){
        
        $joindata = $_REQUEST;
        $ordersn = "JU".date("Ymd").rand(10000,99999);
        $joindata["order_sn"] = $ordersn;
        model("Joinus")->insert($joindata);
        
        $joindata["id"] = M()->insert_id();
        $joindata['order_amount'] = 999;
        
        $payment = model('Order')->payment_info(1);
        
        
        include_once (ROOT_PATH . 'plugins/payment/wxpay_join.php');
        
        $pay_obj = new wxpay();
        
        $jsApiparams = $pay_obj->get_code($joindata, unserialize_config($payment ['pay_config']));
        $order ['pay_desc'] = $payment ['pay_desc'];
        
        $pay_obj = new wxpay();
        $data = [
            'api'=>json_decode($jsApiparams),
            "join"  => $joindata
        ];
        
        return $this->jsonResult($data);
        
        
    }
    
    public function done(){

        $id = $_REQUEST['id'];
        $join = model("Joinus")->getOne("id={$id}");
        $this->assign("join",$join);
        $this->display("joinus_done.dwt");
        
    }
    
    public function pay_notify(){
        $plugin_file = ADDONS_PATH.'payment/wxpay_join.php';
        if (file_exists($plugin_file)) {
            
            include_once($plugin_file);
            $payobj = new wxpay();
            // 处理异步请求
            
            @$payobj->notify();
            
        } else {
            $msg = L('pay_not_exist');
        }
    }
    public function test(){
        echo "echo URI:";
        echo __HOST__ . $_SERVER['REQUEST_URI'];
        echo "<br/>";
        echo "echo HTTP_REFERER:";
        echo $_SERVER['HTTP_REFERER'];
        die;
    }
    
}

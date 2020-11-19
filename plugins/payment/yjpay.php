<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：alipay_wap.php
 * ----------------------------------------------------------------------------
 * 功能描述：手机易极付支付插件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * 支付插件类
 */
class yjpay
{

    /**
     * 生成支付代码
     *
     * @param array $order
     *            订单信息
     * @param array $payment
     *            支付方式信息
     */
    public function get_code($order, $payment)
    {
      
        if (! defined('EC_CHARSET')) {
            $charset = 'utf-8';
        } else {
            $charset = EC_CHARSET;
        }
        
    
        ///*易极付创建订单*/
        $respond = $this->createOrder($order,$payment);

        $this->payOrder($order,$payment,$respond);
        var_dump($postURL);

        $button = '<div class="paypal" style=" text-align:center"><input type="button" onclick="window.open(\'' . $postURL. '\')" value="易极支付"></div>';
        
        
        return $button;
    }

    /**
     * 手机支付宝同步响应操作
     * 
     * @return boolean
     */
    public function callback($data)
    {
        if (! empty($_GET)) {
            $out_trade_no = explode('B', $_GET['subject']);
            $log_id = $out_trade_no[1];
            $payment = model('Payment')->get_payment($data['code']);

            /* 检查数字签名是否正确 */
            ksort($_GET);
            reset($_GET);
            
            $sign = '';
            foreach ($_GET as $key => $val) {
                if ($key != 'sign' && $key != 'sign_type' && $key != 'code') {
                    $sign .= "$key=$val&";
                }
            }
            $sign = substr($sign, 0, - 1) . $payment['alipay_key'];
            if (md5($sign) != $_GET['sign']) {
                return false;
            }
            
            if ($_GET['result'] == 'success') {
                /* 改变订单状态 */
                model('Payment')->order_paid($log_id, 2);
                return true;
            } else {
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 手机支付宝异步通知
     * 
     * @return string
     */
    public function notify($data)
    {
         
        if (! empty($_POST)) {
            $payment = model('Payment')->get_payment($data['code']);
            // 支付宝系统通知待签名数据构造规则比较特殊，为固定顺序。
            $parameter['service'] = $_POST['service'];
            $parameter['v'] = $_POST['v'];
            $parameter['sec_id'] = $_POST['sec_id'];
            $parameter['notify_data'] = $_POST['notify_data'];
            // 生成签名字符串
            $sign = '';
            foreach ($parameter as $key => $val) {
                $sign .= "$key=$val&";
            }
            $sign = substr($sign, 0, - 1) . $payment['alipay_key'];
            // 验证签名
            if (md5($sign) != $_POST['sign']) {
                exit("fail");
            }
            // 解析notify_data
            $data = (array) simplexml_load_string($parameter['notify_data']);
            // 交易状态
            $trade_status = $data['trade_status'];
           
          
           
            // 获取支付订单号log_id
            $out_trade_no = explode('B', $data['subject']);
            $log_id = $out_trade_no[1]; // 订单号log_id
            if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
                /* 改变订单状态 */
                 
               
                model('Payment')->order_paid($log_id, 2);
                exit("success");
            } else {
                exit("fail");
            }
        } else {
            exit("fail");
        }
    }
    /*易极付创建订单*/
    public function createOrder($order_info,$payment){

        $gateway = $payment['requesturl'];
        
        $temp='test'.date('Ymd').mt_rand(0,9).date('His');
        //请求订单号
        $getrequest['requestNo']=$temp;     //$_POST["orderNo"];          //每次请求必须唯一 不区分服务
        //服务代码
        $getrequest['service']="accountpay.create"; 
        //签约的商户或合作商的ID,由平台分配。定长20字符
        $getrequest['partnerId']=$payment['partnerId']; 
        //签名方式
        $getrequest['signType']="MD5";      //$_POST["signType"];
        //交易订单号
        $getrequest['merchOrderNo'] = $order_info['order_sn'];
        //notifyUrl
        $getrequest['notifyUrl'] = notify_url(basename(__FILE__, '.php'), true);
        //returnUrl
        $getrequest['returnUrl'] = return_url(basename(__FILE__, '.php'));
        //单笔交易订单号

        $tradeOrders['subMerchOrderNo'] = $order_info['order_sn'];
        //交易名称
        $tradeOrders['tradeName'] = "支付订单".$order_info['order_sn'];
        //交易金额
        $tradeOrders['amount'] = $order_info['order_amount'];
        //收款方账号测试环境用   收款方457862652283064321   453559606044528648
        $tradeOrders['payeeAccountNo'] ="453559606044528648";
       
        $getrequest['tradeOrders'] = "[".json_encode($tradeOrders,JSON_UNESCAPED_UNICODE)."]";
        
        //$button = '<div class="paypal" style=" text-align:center"><input type="button" onclick="window.open(\'' . $respond['mweb_url']. '&redirect_url='. urlencode($redirect_url) . '\')" value="微信支付"></div>';

        /*开始签名*/
        ksort($getrequest);
        //组装待签名字符串
        
        $strSign = "";
        foreach($getrequest as $k=>$v){
            if($v==="")
                unset($getrequest[$k]);
            else
                $strSign.=$k."=".($v)."&";
        }
    
        $strSign = substr($strSign,0,-1).$payment['ckey'];
      
        $getrequest['sign'] = md5($strSign);

        $requestUrl = "";

        foreach ($getrequest as $key=>$value)  
        {
            // $requestUrl.=$key."=".urlencode($value)."&";
             $requestUrl.=$key."=".$value."&";
        }
        $requestUrl = substr($requestUrl,0,-1);  
    //    logResult("gateway".$gateway);
    //    logResult("requestUr".$requestUrl);
      $ch = curl_init();  
      curl_setopt($ch, CURLOPT_POST, 1);  
      curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);  
      curl_setopt($ch, CURLOPT_URL,$gateway);
   //  curl_setopt($ch, CURLOPT_HEADER, 1);
    //  //为了支持cookie  
      curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');  
      curl_setopt($ch, CURLOPT_POSTFIELDS, $requestUrl);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
      curl_setopt($ch, CURLOPT_AUTOREFERER,1);
     //echo "URL: $qurl <br> data: $da <br/>";
      $response = curl_exec($ch);
      //$temp = curl_multi_getcontent( $ch );
      curl_close($ch);//关闭
      $result = json_decode($response);
      if($result->success){
        return $result->merchOrderNo;
      }else{
        return false;
      }
   
       // logResult($getrequest);
       //  $result = Http::doPost($gateway,$getrequest,5,"Content-Type: application/json",'json');
       //  logResult("testcreateorder");
       //  logResult($result) ; 

        

    }
    /*易极付支付订单*/
    public function payOrder($order_info,$payment,$merchOrderNo){
        
         /*生成支付订单需要的信息*/
        $gateway = $payment['requesturl'];
        $ckey = $payment['ckey'];

        $temp='test'.date('Ymd').mt_rand(0,9).date('His');
        //请求订单号
        $parameter['requestNo']=$temp;     //$_POST["orderNo"];          //每次请求必须唯一 不区分服务
        //服务代码:快捷支付
        $parameter['service']="accountpay.cashier.pay"; 
        //签约的商户或合作商的ID,由平台分配。定长20字符
        $parameter['partnerId']=$payment['partnerId']; 
        //签名方式
        $parameter['signType']="MD5";      //$_POST["signType"];
        //原商户交易订单号
        $parameter['origMerchOrderNo'] = $order_info['order_sn'];
        //支付方式
        $parameter['payChannel'] = "quick_pay";

        $parameter['merchOrderNo'] = $merchOrderNo;
        /*开始签名*/
        $parameter['return_url'] = __URL__ . "/index.php?c=respond";
        
        ksort($parameter);
        //组装待签名字符串
        
        $strSign = "";
        foreach($parameter as $k=>$v){
            if($v==="")
                unset($parameter[$k]);
            else
                $strSign.=$k."=".($v)."&";
        }
        $strSign = substr($strSign,0,-1).$ckey;
        /*生成签名*/
        var_dump($ckey);
        var_dump($strSign);
        $parameter['sign'] = md5($strSign);
        var_dump($parameter);
        //准备URL
        $postURL = "";
        foreach ($parameter as $key=>$value)  
        {
            $postURL.=$key."=".$value."&";
        }
        
        $postURL = substr($postURL,0,-1);

        $postURL = $gateway."?".$postURL;
          $ch = curl_init();  
          curl_setopt($ch, CURLOPT_POST, 1);  
          curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);  
          curl_setopt($ch, CURLOPT_URL,$gateway);
       //  curl_setopt($ch, CURLOPT_HEADER, 1);
        //  //为了支持cookie  
          curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');  
          curl_setopt($ch, CURLOPT_POSTFIELDS, $requestUrl);
          curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
          curl_setopt($ch, CURLOPT_AUTOREFERER,1);
         //echo "URL: $qurl <br> data: $da <br/>";
          $response = curl_exec($ch);
          //$temp = curl_multi_getcontent( $ch );
          curl_close($ch);//关闭
          var_dump($response);
          $result = json_decode($response);
          var_dump($result);exit;
          if($result->success){
            return $result->redirectUrl;
          }else{
            return false;
          }


    }


}

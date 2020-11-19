<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：wxpay.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信支付插件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
//defined('IN_ECTOUCH') or die('Deny Access');

/**
 * 微信支付类
 */
class paypal
{

    var $parameters; // cft 参数
    var $payment; // 配置信息

    /**
     * 生成支付代码
     *
     * @param array $order 订单信息
     * @param array $payment 支付方式信息
     */
    
        public function get_code($order,$config)

    {
        $order_amount = $order['order_amount'] ;
        $order_title = "您的拓客网站订单".$order['order_sn'];
        $out_trade_no = $order['order_sn'] . 'A' . $order_amount . 'B' . $order['log_id'];
       //$notify_url = notify_url(basename(__FILE__, '.php'), true);
        $redirect_url = __URL__ . "/index.php?c=respond&code=paypal&type=notify&order_id=".$order['order_id']."&log_id=".$order['log_id'];

//测试 https://www.sandbox.paypal.com/cgi-bin/webscr
//正式https://www.paypal.com/cgi-bin/webscr
        $deal_url = '<form style="text-align:center;" id="form1" name="form1" action="https://www.paypal.com/cgi-bin/webscr" method="post" class="paypal">' .

        "<input type='hidden' name='cmd' value='_xclick'>" .//告诉paypal该表单是立即购买

        "<input type='hidden' name='business' value='way-kevin@hotmail.com'>" .//卖家帐号 也就是收钱的帐号

        "<input type='hidden' name='item_name' value='".$order_title."'>" .//商品名称 item_number

       // "<input type='hidden' name='item_number' value='211'>" .//物品号 item_number

        "<input type='hidden' name='amount' value='".$order_amount."'>" .// 订单金额

        "<input type='hidden' name='currency_code' value='TWD'>" .// 货币

        "<input type='hidden' name='return' value='".$redirect_url."'>" .// 支付成功后网页跳转地址

        "<input type='hidden' name='notify_url' value='".$notify_url."'>" .//支付成功后paypal后台发送订单通知地址

        "<input type='hidden' name='cancel_return' value=''>" .//用户取消交易返回地址

        "<input type='hidden' name='invoice' value='".$order['order_sn']."'>" .//自定义订单号

        "<input type='hidden' name='charset' value='utf-8'>" .// 字符集

        "<input type='hidden' name='no_shipping' value='1'>" .// 不要求客户提供收货地址

        "<input type='hidden' name='no_note' value=''>" .// 付款说明

        "<input type='hidden' name='rm' value='2'>" .
        "<input type='submit' name='submit' value='paypal支付'>".
        "</form>

       ";

        return $deal_url;

    }

    /**
     * 响应操作
     */
    public function callback($data)
    {
        if (isset($_GET) && $_GET['status'] == 1) {
            $order = [];
            $order['order_id']= intval($_GET['order_id']);
            $payment = model('Payment')->get_payment($data['code']);
            return $this->queryOrder($order, $payment);
        } else {
            return false;
        }
    }

    /**
     * 响应操作
     */
    public function notify($data)
    {
            $val = "";  
            $currentDateTime  =  date('YmdHis',time());  
            $currentDate =  date('Ymd',time());  
            $fileName = "paypallog/".$currentDate;//文件名称  
            @$data = fopen($fileName,'a+');//添加不覆盖，首先会判断这个文件是否存在，如果不存在，则会创建该文件，即每天都会创建一个新的文件记录的信息  
            $val.= $currentDateTime;  
            if($_POST){  
                $val.='|POST'.'|'.$_POST."\n";  
                foreach($_POST as $key =>$value){  
                    $val .= '|'.$key.":".$value;  
                }  
            }else{  
                $val.='|GET'.'|'.$_GET."\n";  
                foreach($_GET as $key =>$value){  
                        $val .= '|'.$key.":".$value;  
                    }  
            }  
            $val.= "\n";  
            fwrite($data,$val);//写入文本中  
            fclose($data);  
    }

    private function trimString($value)
    {
        $ret = null;
        if (null != $value) {
            $ret = $value;
            if (strlen($ret) == 0) {
                $ret = null;
            }
        }
        return $ret;
    }

    /**
     * 作用：产生随机字符串，不长于32位
     */
    private function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 作用：设置请求参数
     */
    private function setParameter($parameter, $parameterValue)
    {
        $this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }

    /**
     * 作用：生成签名
     */
    private function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        // 签名步骤一：按字典序排序参数
        ksort($Parameters);

        $buff = "";
        foreach ($Parameters as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }
        $String = '';
        if (strlen($buff) > 0) {
            $String = substr($buff, 0, strlen($buff) - 1);
        }
        // echo '【string1】'.$String.'</br>';
        // 签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->payment['wxpay_key'];
        // echo "【string2】".$String."</br>";
        // 签名步骤三：MD5加密
        $String = md5($String);
        // echo "【string3】 ".$String."</br>";
        // 签名步骤四：所有字符转为大写
        $result = strtoupper($String);
        // echo "【result】 ".$result_."</br>";
        return $result;
    }

    /**
     * 获取当前服务器的IP
     */
    private function get_client_ip()
    {
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
            $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $cip = getenv("HTTP_CLIENT_IP");
        } else {
            $cip = "unknown";
        }
        return $cip;
    }


    /**
     * 作用：以post方式提交xml到对应的接口url
     */
    private function postXmlCurl($xml, $url, $second = 30)
    {
        // 初始化curl
        $ch = curl_init();
        // 设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        // 这里设置代理，如果有的话
        // curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        // curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // 设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        // 运行curl
        $data = curl_exec($ch);
        // 返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            curl_close($ch);
            return false;
        }
    }

    /**
     * 查询订单
     * 当商户后台、网络、服务器等出现异常，商户系统最终未接收到支付通知
     *
     * @param $order
     * @param $payment
     */
    public function queryOrder($order, $payment)
    {
        // 查询未支付的订单
        $res = model('Base')->model->table('pay_log')->field('transid, is_paid, log_id, order_amount')->where(array('order_id' => $order['order_id']))->find();
        if ($res['is_paid'] == 0) {
            $options = array(
                     'appid' => $payment['wxpay_appid'], //填写高级调用功能的app id
                     'mch_id' => $payment['wxpay_mchid'], //微信支付商户号
                     'key' => $payment['wxpay_key'], //微信支付API密钥
                 );
            $weObj = new Wechat($options);

            // 微信订单号  商户订单号  二选一 ， 微信的订单号，建议优先使用
            $order_amount = $res['order_amount'] * 100;
            $order_sn = model('Base')->model->table('order_info')->field('order_sn')->where(array('order_id' => $order['order_id']))->getOne();

            $out_trade_no = $order_sn . 'A' . $order_amount . 'B' . $res['log_id'];

            // $this->setParameter("transaction_id", $transaction_id); // 微信订单号
            $this->setParameter("out_trade_no", $out_trade_no); // 商户订单号

            $respond = $weObj->PayQueryOrder($this->parameters);
            // $OrderParameters = json_encode($respond);
            if ($respond['result_code'] == 'SUCCESS' && $respond['trade_state'] == 'SUCCESS') {
                // 获取log_id
                $out_trade_no = explode('B', $respond['out_trade_no']);
                $order_sn = $out_trade_no[1]; // 订单号log_id
                 
                // 修改订单信息(openid，tranid)
                // model('Base')->model->table('pay_log')->data(array('openid' => $respond['openid'], 'transid' => $respond['transaction_id']))->where(array('log_id' => $order_sn))->update();
                // 改变订单状态
                // order_paid($order_sn, 2);
                return true;
            } else {
                return false;
            }
        } elseif ($res['is_paid'] == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 退款申请
     * array(
     *     'order_id' => '1',
     *     'order_sn' => '2017061609464501623',
     *     'pay_id' => '',
     *     'pay_status' => 2
     * )
     *
     * @param $order
     * @param $payment
     * @return bool
     */
    public function payRefund($order, $payment)
    {
        // 查询已支付的订单
        $res = model('Base')->model->table('pay_log')->field('transid, is_paid, log_id, order_amount')->where(array('order_id' => $order['order_id']))->find();

        if ($res['is_paid'] == 1 && $order['pay_status'] == 2) {
            $options = array(
                     'appid' => $payment['wxpay_appid'], //填写高级调用功能的app id
                     'mch_id' => $payment['wxpay_mchid'], //微信支付商户号
                     'key' => $payment['wxpay_key'], //微信支付API密钥
                 );
            $weObj = new Wechat($options);

            $order_amount = $res['order_amount'] * 100;
            $out_trade_no = $order['order_sn'] . 'A' . $order_amount . 'B' . $res['log_id']; // 商户订单号

            $order_return_info = model('Base')->model->table('order_return')->field('return_sn, order_sn, return_status, refound_status')->where(array('order_id' => $order['order_id']))->find();

            $out_refund_no = $order_return_info['return_sn']; // 商户退款单号
            $total_fee = $order_amount;
            $refund_fee = isset($order['should_return']) ? $order['should_return'] : $order_amount;   // 退款金额 默认退全款

            $this->setParameter("out_trade_no", $out_trade_no); // 商户订单号
            $this->setParameter("out_refund_no", $out_refund_no);// 商户退款单号
            $this->setParameter("total_fee", $total_fee);//总金额
            $this->setParameter("refund_fee", $refund_fee);//退款金额
            $this->setParameter("op_user_id", $payment['wxpay_mchid']);//操作员

            $respond = $weObj->PayRefund($this->parameters);
            // 退款申请接收成功
            if ($respond['result_code'] == 'SUCCESS') {
                $out_refund_no = $respond['out_refund_no']; // 商户退款单号
                return $out_refund_no;
            } else {
                return false;
            }
        }
    }

    /**
     * 查询退款
     *
     * @param $order
     * @param $payment
     * @return bool
     */
    public function payRefundQuery($order, $payment)
    {
        // 查询已退款的订单
        $order_return_info = model('Base')->model->table('order_return')->field('return_sn, order_sn, return_status, refound_status')->where(array('order_id' => $order['order_id']))->find();
        if ($order_return_info && $order_return_info['refound_status'] == 1) {
            $options = array(
                     'appid' => $payment['wxpay_appid'], //填写高级调用功能的app id
                     'mch_id' => $payment['wxpay_mchid'], //微信支付商户号
                     'key' => $payment['wxpay_key'], //微信支付API密钥
                 );
            $weObj = new Wechat($options);

            // 微信订单号 transaction_id， 商户订单号 out_trade_no， 商户退款单号 out_refund_no，微信退款单号 refund_id 四选一
            // $this->setParameter("out_trade_no", $out_trade_no);
            $this->setParameter("out_refund_no", $order_return_info['return_sn']);// 商户退款单号
            // $this->setParameter("transaction_id", $transaction_id);
            // $this->setParameter("refund_id", $refund_id);

            $respond = $weObj->PayRefundQuery($this->parameters);
            // 退款查询
            if ($respond['result_code'] == 'SUCCESS' && $respond['refund_status'] == 'SUCCESS') {
                /*
                refund_status_$n $n为下标，从0开始编号。
                退款状态：
                SUCCESS—退款成功
                REFUNDCLOSE—退款关闭。
                PROCESSING—退款处理中
                CHANGE—退款异常，退款到银行发现用户的卡作废或者冻结了
                 */
                $out_refund_no = $respond['out_refund_no']; // 商户退款单号
                $refund_count = $respond['refund_count']; // 退款笔数
                $refund_fee = $respond['refund_fee']; // 退款金额

                return $out_refund_no;
            } else {
                return false;
            }
        }
    }

    /**
     * 获取prepay_id
     */
    private function getPrepayId()
    {
        // 设置接口链接
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        try {
            // 检测必填参数
            if ($this->parameters["out_trade_no"] == null) {
                throw new Exception("缺少统一支付接口必填参数out_trade_no！" . "<br>");
            } elseif ($this->parameters["body"] == null) {
                throw new Exception("缺少统一支付接口必填参数body！" . "<br>");
            } elseif ($this->parameters["total_fee"] == null) {

                throw new Exception("缺少统一支付接口必填参数total_fee！" . "<br>");
            } elseif ($this->parameters["notify_url"] == null) {
                throw new Exception("缺少统一支付接口必填参数notify_url！" . "<br>");
            } elseif ($this->parameters["trade_type"] == null) {
                throw new Exception("缺少统一支付接口必填参数trade_type！" . "<br>");
            } elseif ($this->parameters["trade_type"] == "JSAPI" && $this->parameters["openid"] == NULL) {
                throw new Exception("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！" . "<br>");
            }
            $this->parameters["appid"] = $this->payment['wxpay_appid']; // 公众账号ID
            $this->parameters["mch_id"] = $this->payment['wxpay_mchid']; // 商户号
            $this->parameters["spbill_create_ip"] = $_SERVER['REMOTE_ADDR']; // 终端ip
            $this->parameters["nonce_str"] = $this->createNoncestr(); // 随机字符串
            $this->parameters["sign"] = $this->getSign($this->parameters); // 签名
            $xml = "<xml>";
            foreach ($this->parameters as $key => $val) {
                if (is_numeric($val)) {
                    $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
                } else {
                    $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
                }
            }
            $xml .= "</xml>";

        } catch (Exception $e) {
            die($e->getMessage());
        }
     

        // $response = $this->postXmlCurl($xml, $url, 30);
        $response = Http::curlPost($url, $xml, 30);

        $result = json_decode(json_encode(simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $prepay_id = $result["prepay_id"];

        return $prepay_id;
    }

    /**
     * 作用：设置jsapi的参数
     */
    private function getParameters($prepay_id)
    {
        $jsApiObj["appId"] = $this->payment['wxpay_appid'];
        $timeStamp = time();
        $jsApiObj["timeStamp"] = "$timeStamp";
        $jsApiObj["nonceStr"] = $this->createNoncestr();
        $jsApiObj["package"] = "prepay_id=$prepay_id";
        $jsApiObj["signType"] = "MD5";
        $jsApiObj["paySign"] = $this->getSign($jsApiObj);
        $this->parameters = json_encode($jsApiObj);

        return $this->parameters;
    }

    /**
     * 输出xml字符
     **/
    private function toXml($data)
    {
        if (!is_array($data) || count($data) <= 0) {
            throw new Exception("数组数据异常！");
        }

        $xml = "<xml>";
        foreach ($data as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }
}
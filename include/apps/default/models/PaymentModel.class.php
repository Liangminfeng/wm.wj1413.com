<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：PaymentModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 支付模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class PaymentModel extends BaseModel {

    /**
     *  取得某支付方式信息
     *  @param  string  $code   支付方式代码
     */
    function get_payment($code) {
        $sql = 'SELECT * FROM ' . $this->pre . "payment WHERE pay_code = '$code' AND enabled = '1'";
        $payment = $this->row($sql);

        if ($payment) {
            $config_list = unserialize($payment['pay_config']);

            foreach ($config_list AS $config) {
                $payment[$config['name']] = $config['value'];
            }
        }
        return $payment;
    }

    /**
     *  通过订单sn取得订单ID
     *  @param  string  $order_sn   订单sn
     *  @param  blob    $voucher    是否为会员充值
     */
    function get_order_id_by_sn($order_sn, $voucher = 'false') {
        if ($voucher == 'true') {
            if (is_numeric($order_sn)) {
                $sql = "SELECT log_id FROM " . $this->pre . "pay_log WHERE order_id=" . $order_sn . ' AND order_type=1';
                $res = $this->row($sql);
                return $res['log_id'];
            } else {
                return "";
            }
        } else {
            if (is_numeric($order_sn)) {
                $sql = 'SELECT order_id FROM ' . $this->pre . "order_info WHERE order_sn = '$order_sn'";
                $res = $this->row($sql);
                $order_id = $res['order_id'];
            }
            if (!empty($order_id)) {
                $sql = "SELECT log_id FROM " . $this->pre . "pay_log WHERE order_id='" . $order_id . "'";
                $res = $this->row($sql);
                return $res['log_id'];
            } else {
                return "";
            }
        }
    }

    /**
     *  通过订单ID取得订单商品名称
     *  @param  string  $order_id   订单ID
     */
    function get_goods_name_by_id($order_id) {
        $sql = 'SELECT goods_name FROM ' . $this->pre . "order_goods WHERE order_id = '$order_id'";
        $res = $this->query($sql);
        if ($res !== false) {
            foreach ($res as $key => $value) {
                $goods_name[] = $value['goods_name'];
            }
        }
        return implode(',', $goods_name);
    }

    /**
     * 检查支付的金额是否与订单相符
     *
     * @access  public
     * @param   string   $log_id      支付编号
     * @param   float    $money       支付接口返回的金额
     * @return  true
     */
    function check_money($log_id, $money) {
        if (is_numeric($log_id)) {
            $sql = 'SELECT order_amount FROM ' . $this->pre .
                    "pay_log WHERE log_id = '$log_id'";
            $res = $this->row($sql);
            $amount = $res['order_amount'];
        } else {
            return false;
        }
        if ($money == $amount) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param int $logid
     * @return array|boolean
     */
    function getOrder($logid){
        $sql = "SELECT * FROM " . $this->pre .
        "pay_log WHERE log_id = '$log_id'";
        $pay_log = $this->row($sql);
        if ($pay_log ){
            $sql = 'SELECT * ' .
                'FROM ' . $this->pre .
                "order_info WHERE order_id = '$pay_log[order_id]'";
            $order = $this->row($sql);
            return $order;
        }
        return false;
    }
    
    /**
     * 修改订单的支付状态
     *
     * @access  public
     * @param   string  $log_id     支付编号
     * @param   integer $pay_status 状态
     * @param   string  $note       备注
     * @return  void
     */
    function order_paid($log_id, $pay_status = PS_PAYED, $note = '') {
        /* 取得支付编号 */
     
        $log_id = intval($log_id);
        if ($log_id > 0) {
            /* 取得要修改的支付记录信息 */
            $sql = "SELECT * FROM " . $this->pre .
                    "pay_log WHERE log_id = '$log_id'";
            $pay_log = $this->row($sql);

            if ($pay_log && $pay_log['is_paid'] == 0) {
                /* 修改此次支付操作的状态为已付款 */
                $sql = 'UPDATE ' . $this->pre .
                        "pay_log SET is_paid = '1' WHERE log_id = '$log_id'";
                $this->query($sql);
   
                /* 根据记录类型做相应处理 */
                if ($pay_log['order_type'] == PAY_ORDER) {
                    /* 取得订单信息 */
                    $sql = 'SELECT * ' .
                            'FROM ' . $this->pre .
                            "order_info WHERE order_id = '$pay_log[order_id]'";
                    $order = $this->row($sql);
                    $order_id = $order['order_id'];
                    $order_sn = $order['order_sn'];

                     
                    /* 修改订单状态为已付款 */
                    // $sql = 'UPDATE ' . $this->pre .
                    //         "order_info SET order_status = '" . OS_CONFIRMED . "', " .
                    //         " confirm_time = '" . time() . "', " .
                    //         " pay_status = '$pay_status', " .
                    //         " pay_time = '" . time() . "', " .
                    //         " money_paid = order_amount," .
                    //         " order_amount = order_amount " .
                    //         "WHERE order_id = '$order_id'";
                    // $this->query($sql);
                   // $this->upgradeToVip($order['user_id'],$order_id);
                    //upgradeToVip($order['user_id'],$order['order_id']);
                    /* 记录订单操作记录 */
                    /*入团的省份信息*/
                    if($order['parent_id']){
                    /* 默认1 天美国际go，2 拓客*/
                    /*更新用户的来源，跟上一级一样*/
                             $parentinfo = model('Users')->get_users($order['parent_id']);
                             $updateData['resource'] = $parentinfo['resource'];
                             model("Users")->update_info_user($order['user_id'],$updateData);
                             $_SESSION['resource'] = $updateData['resource'];

                    }
                  //收件地址信息
                    $consignee_country = model('Users')->find_region_name($order['country']);
                    $consignee_province = model('Users')->find_region_name($order['province']);
                    $consignee_city = model('Users')->find_region_name($order['city']);
                    $cart_goods =  model('Order')->order_goods($order['order_id']);

                    $userinfo = model('Users')->getusersinfo($order['user_id']);

                 
                    $parent_id = $order ['parent_id'];
                    if(strpos($order['order_type'],"1")!==false||strpos($order['order_type'],"2")!==false||strpos($order['order_type'],"3")!==false||strpos($order['order_type'],"4")!==false||strpos($order['order_type'],"5")!==false){
                        if(strpos($order['order_type'],"9")!==false){
                           
                            $api_order_type = ltrim($order['order_type'],"9,");
                        
                        }else{
                            $api_order_type  = $order['order_type'];
                        }
                        
                    }


                     if($order['order_type']==10){
                              $api_order_type =10;
                        }
                    switch ($api_order_type) {
                       
                        case '1':

                        /*入团变成经销商，赠送一级vip(店主)*/
                    /*/
                        /*入团$_SESSION['user_rank']有值说明已经入团了*/
                        if($cart_goods[0]['goods_number']>1){
                             show_message(L('onlybuyone_error'));
                        }
                        if($_SESSION['user_rank']){
                            /**/
                             show_message(L('rutuan_error'));
                        }
                        /*同时有两笔入团订单的时候一笔已经支付，另外一笔未支付的时候，要把它设置为取消状态*/
                        model("Order")->cancelOrder($order['user_id'],3,$order ['order_id']);
                        model('Users')->updateAreaTotal($order['user_id'],$order['goods_amount']);
                        //model('Users')->updateVip($order['user_id'],$cart_goods[0]['goods_vip']);
                        /*赠送店主身份*/

                        //$r = model('Users')->updateUserVip($order['user_id'],1);

                        /*更新这笔订单产生的业绩*/
                     
                                   if(model("Users")->findvipmanageaccount($order['user_id'],$userinfo['vip_manage_account'],$updateData['resource']))
                                    {
                                        model("Users")->newaddvipamanageaccount($order['user_id'],$userinfo['vip_manage_account'],$updateData['resource']);
                                    }
                                    

                       
                                    $parent_accountinfo  = model("Users")->getparentuservipaccount($order['user_id']);
                                    $cart_goods =  model('Order')->order_goods($order['order_id']);
                                    if($order['discount'] =='0.00'){
                                       
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_rtpv']."pv";
                                          
                                    }else{
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_rtpv']."pv".",折扣$".$order['discount'];
                                    }
                                    if($user_info['user_vip']&&!$user_info['user_rank']){
                                        /*走激活接口api/order/activate*/
                                        $joinvipdata = array(
                                                
                                                "account" =>$userinfo['vip_manage_account'],
                                  
                                             
                       
                                                // "nickname" =>$userinfo['nick_name'],
                                                "grade"=>$order['vip'],
                                                "order_sn" =>$order['order_sn'],
                                                "order_amount" =>$order['order_amount'],
                                                "order_pv" =>$order['total_rtpv'],
                                                "purse_amount" =>0,//抵扣金额
                                                "order_type" =>1,
                                            
                                                "order_time" =>$order['add_time'],
                                                "remark"  => $remark
                                             );
                                
                                        $ret1 = model("Index")->postData($joinvipdata,"/api/order/activate");
                                    }else{
                                        $joinvipdata = array(
                                                
                                                "account" =>$userinfo['vip_manage_account'],
                                                "password" =>$userinfo['password'],
                                                "phone" =>$userinfo['mobile_phone'],
                                                "parent_account" =>$order['other_invite_code'],//上级VIP用户名
                                                // "nickname" =>$userinfo['nick_name'],
                                                "grade"=>$order['vip'],
                                                "order_sn" =>$order['order_sn'],
                                                "order_amount" =>$order['order_amount'],
                                                "order_pv" =>$order['total_rtpv'],
                                                "purse_amount" =>0,//抵扣金额
                                                "order_type" =>1,
                                                "type" =>2,
                                                "order_time" =>$order['add_time'],
                                                "remark"  => $remark
                                             );
                                
                                    $ret1 = model("Index")->postData($joinvipdata,"/api/order/joinvip");
                                    }
                                    if(!$_SESSION['user_vip']){
                                      model("Users")->update_parent_id($order['user_id'],$parent_id);
                                      model("Users")->updateUserVip($order['user_id'],1);
                                      $_SESSION['user_vip']=1;
                                    }

                         
                                    if($order['other_invite_code']){
                                        updateUserOtherInviteCode($order['user_id'],$order['other_invite_code']);
                                        /*更新parentid*/
                                         $reffer= model('Users')->get_usersbycode($order['other_invite_code']);
                                    
                                      
                                      
                                    }
                       
                            break;
                        case '2':
                      
                             /*重消*/

                        $account = model("Users")->gettmaccount($order['user_id']);
                        if($order['discount'] =='0.00'){
                                       
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_rtpv']."pv";
                                          
                                    }else{
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_rtpv']."pv".",折扣$".$order['discount'];
                        }
                  
                        $anewOrderdata = array(
                                                
                                                "account" =>$userinfo['vip_manage_account'],
                                                "grade" =>$order['vip'],//目标等级
                                                "order_sn" =>$order['order_sn'],
                                                "order_amount" =>$order['order_amount'],
                                                "order_pv" =>$order['total_rtpv'],
                                                "purse_amount"=>0,
                                                "order_type" =>2,
                                                "order_time" =>$order['add_time'],
                                                "remark" =>$remark
                                             );
                           
                            $ret1 = model("Index")->postData($anewOrderdata,"/api/order/aneworder");

                        # code...
                      
                            break;
                        case '3':
                      
                        /*差额升级*/
                        
                        if($cart_goods[0]['goods_number']>1){
                             show_message(L('onlybuyone_error'));
                        }
                        /*升级判断当前用户等级跟商品的原等级是不是一样并且目标等级是不是大于当前用户等级*/
                        if(($_SESSION['user_rank']!=$cart_goods[0]['origin_goods_vip'])&&($cart_goods[0]['goods_vip']<=$_SESSION['user_rank'])){
                                show_message(L('updategrade_error_one'));
                        }
                        $date1=strtotime(date('Y-m-d H:i:s'));
                        $date2=$userinfo['reg_time'];//注册时间
                        $result=count_days($date1,$date2);
                        if($result>60){
                                       show_message(L('updategrade_error_one'));
                        }
                        /*同时有两笔升级订单的时候一笔已经支付，另外一笔未支付的时候，要把它设置为取消状态*/
                        model("Order")->cancelOrder($order['user_id'],3,$order ['order_id']);
                        model('Users')->updateVip($order['user_id'],$cart_goods[0]['goods_vip']);
                        
                        /*差额升级和原价升级，60天时限做判断*/
                        
                        $account = model("Users")->gettmaccount($order['user_id']);
                        if($order['discount'] =='0.00'){
                                       
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_rtpv']."pv";
                                          
                                    }else{
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_rtpv']."pv".",折扣$".$order['discount'];
                        }
                            /*差额升级*/
                        $upgradeOrderdata = array(
                                                
                                                "account" =>$userinfo['vip_manage_account'],
                                                "origin_grade" =>$userinfo['user_rank'],
                                                "grade" =>$order['vip'],//目标等级
                                                "order_sn" =>$order['order_sn'],
                                                "order_amount" =>$order['order_amount'],
                                                "order_pv" =>$order['total_rtpv'],
                                                "purse_amount"=>0,
                                                "order_type" =>3,
                                                "order_time" =>$order['add_time'],
                                                "remark" =>$remark
                                             );
                        
                        $ret1 = model("Index")->postData($upgradeOrderdata,"/api/order/upgrade");
                       
                            break;
                        case '4':
                            /*原价升级*/
                           
                           if($cart_goods[0]['goods_number']>1){
                                 show_message(L('onlybuyone_error'));
                            }
                           if(($_SESSION['user_rank']!=$cart_goods[0]['origin_goods_vip'])&&($cart_goods[0]['goods_vip']<=$_SESSION['user_rank'])){
                                  show_message(L('updategrade_error_one'));
                            }
                           $account = model("Users")->gettmaccount($order['user_id']);
                           $date1=strtotime(date('Y-m-d H:i:s'));
                            $date2=$userinfo['reg_time'];//注册时间
                            $result=count_days($date1,$date2);
                            if($result<=60){
                                           show_message(L('updategrade_error_one'));
                            }
                           if($order['discount'] =='0.00'){
                                           
                                             $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_rtpv']."pv";
                                              
                                        }else{
                                             $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_rtpv']."pv".",折扣$".$order['discount'];
                            }
                           model("Order")->cancelOrder($order['user_id'],4,$order['order_id']);
                           model('Users')->updateVip($order['user_id'],$cart_goods[0]['goods_vip']);
                     
                           $upgradeOrderdata = array(
                                                    
                                                     "account" =>$userinfo['vip_manage_account'],
                                                    "origin_grade" =>$userinfo['user_rank'],
                                                    "grade" =>$order['vip'],//目标等级
                                                    "order_sn" =>$order['order_sn'],
                                                    "order_amount" =>$order['order_amount'],
                                                    "order_pv" =>$order['total_rtpv'],
                                                    "purse_amount"=>0,
                                                    "order_type" =>4,
                                                    "order_time" =>$order['add_time'],
                                                    "remark" =>$remark
                                                 );
                            
                                $ret1 = model("Index")->postData($upgradeOrderdata,"/api/order/upgrade");

                            break;
                        case '5':
                             /*重购*/
                             
                             if($cart_goods[0]['goods_number']>1){
                                 show_message(L('onlybuyone_error'));
                            }
                             if($_SESSION['user_rank']>$cart_goods[0]['goods_vip']){
                                  show_message(L('updategrade_error'));
                            }
                             model('Users')->updateAreaTotal($order['user_id'],$order['goods_amount']);
                            
                            // /*更新这笔订单产生的业绩*/

                             
                            if($order['discount'] =='0.00'){
                                           
                                             $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_rtpv']."pv";
                                              
                                        }else{
                                             $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_rtpv']."pv".",折扣$".$order['discount'];
                            }
                            /*判断是升级重购还是原等级重购*/
                           
                            if($userinfo['user_rank']==$order['vip']){
                                /*原等级重购*/
                                $order_type = 5;
                            }else{
                                /*升级重购*/
                                $order_type = 6;
                            }

                         

                            $account = model("Users")->gettmaccount($order['user_id']);
                            $reorderpdata = array(
                                                    
                                                     "account" =>$userinfo['vip_manage_account'],
                                                    "grade" =>$order['vip'],
                                                    "order_sn" =>$order['order_sn'],
                                                    "order_amount" =>$order['order_amount'],
                                                    "order_pv" =>$order['total_rtpv'],
                                                    "purse_amount"=>0,
                                                    "order_type" =>$order_type,
                                                    "order_time" =>$order['add_time'],
                                                    "remark" =>$remark
                                                 );
                            
                            $ret1 = model("Index")->postData($reorderpdata,"/api/order/reorder");

                            break;
                        case '9':
                            /*vip套餐*/
                            if(!$userinfo['user_vip']){
                                 if(model("Users")->findvipmanageaccount($order['user_id'],$userinfo['vip_manage_account'],$updateData['resource']))
                                    {
                                        model("Users")->newaddvipamanageaccount($order['user_id'],$userinfo['vip_manage_account'],$updateData['resource']);
                                    }
                                 $number = substr(time(), 2);
                                 $cart_goods =  model('Order')->order_goods($order['order_id']);
                                    if($order['discount'] =='0.00'){
                                       
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_pv']."pv";
                                          
                                    }else{
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_pv']."pv".",折扣$".$order['discount'];
                                    }
                                  
                                    $joinvipdata = array(
                                                
                                                "account" =>$userinfo['vip_manage_account'],
                                                "password" =>$userinfo['password'],
                                                "phone" =>$userinfo['mobile_phone'],
                                                "parent_account" =>$order['other_invite_code'],//上级VIP用户名
                                                // "nickname" =>$userinfo['nick_name'],
                                                "grade"=>$order['vip'],
                                                "order_sn" =>$order['order_sn'],
                                                "order_amount" =>$order['order_amount'],
                                                "order_pv" =>$order['total_vippv'],
                                                "purse_amount" =>0,//抵扣金额
                                                "order_type" =>9,
                                                "type" =>1,
                                                "order_time" =>$order['add_time'],
                                                "remark"  => $remark
                                             );

                               
                              
                                      $ret1 = model("Index")->postData($joinvipdata,"/api/order/joinvip");
                                      model("Users")->update_parent_id($order['user_id'],$parent_id);
                                      model("Users")->updateUserVip($order['user_id'],1);
                                      $_SESSION['user_vip']=1;
                                 
                               

                                model('Users')->updateCommTotal($order['user_id'],$order['order_amount']);
                            }
                            break;
                        case '10':
                            if(!$_SESSION['user_vip']){

                                      model("Users")->update_parent_id($_SESSION['user_id'],'896');
                                      model("Users")->updateUserVip($order['user_id'],1);
                                      $_SESSION['user_vip']=1;

                                    }
                            $cart_goods =  model('Order')->order_goods($order['order_id']);

                                    if($order['discount'] =='0.00'){
                                       
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_pv']."pv";
                                          
                                    }else{
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_pv']."pv".",折扣$".$order['discount'];
                                    }
                                  
                                    $joinvipdata = array(
                                                
                                                "account" =>$userinfo['vip_manage_account'],
                                                "password" =>$userinfo['password'],
                                                "phone" =>$userinfo['mobile_phone'],
                                                "parent_account" =>$order['other_invite_code'],//上级VIP用户名
                                                // "nickname" =>$userinfo['nick_name'],
                                                "grade"=>$order['vip'],
                                                "order_sn" =>$order['order_sn'],
                                                "order_amount" =>$order['order_amount'],
                                                "order_pv" =>$order['total_vippv'],
                                                "purse_amount" =>0,//抵扣金额
                                                "order_type" =>9,
                                                "type" =>1,
                                                "order_time" =>$order['add_time'],
                                                "remark"  => $remark
                                             );

                               
                              
                              $ret1 = model("Index")->postData($joinvipdata,"/api/order/joinvip");

                              model("Users")->updateuser_reposition($_SESSION['user_id'],$order['parent_id']);
                       
                           
                            


                            break;
                        case '0':
                                /*零售区产品*/
                               
                           
                                model('Users')->updateCommTotal($order['user_id'],$order['order_amount']);
           
                                

                           
                                # code...
                                break;
                        default:
                            # code...
                            break;
                    }
                    /* 修改订单状态为已付款 */
                    
                    $sql = 'UPDATE ' . $this->pre .
                            "order_info SET order_status = '" . OS_CONFIRMED . "', " .
                            " confirm_time = '" . time() . "', " .
                            " pay_status = '$pay_status', " .
                            " pay_time = '" . time() . "', " .
                            " money_paid = order_amount," .
                            " order_amount = order_amount " .
                            "WHERE order_id = '$order_id'";
                    $this->query($sql);
                    /*购买入团产品或者重购抛向制度网*/
                   $cart_goods =  model('Order')->order_goods($order['order_id']);

                        $goods_data[0]['goods_money'] = $cart_goods[0]['goods_pv']=='-1'?$cart_goods[0]['goods_price']:$cart_goods[0]['goods_pv'];
                         $goods_data[0]['goods_number'] = $cart_goods[0]['goods_number'];
                         $goods_data[0]['goods_name'] = $cart_goods[0]['goods_name'];
                         $goods_data[0]['goods_price'] = $cart_goods[0]['goods_price'];
                         $goods_data[0]['goods_id'] = $cart_goods[0]['goods_id'];
                       
                        
                     
                    model('OrderBase')->order_action($order_sn, OS_CONFIRMED, SS_UNSHIPPED, $pay_status, $note, L('buyer'));

                    /* 如果需要，发短信 */
                    if (C('sms_order_payed') == '1' && C('sms_shop_mobile') != '') {
                        $sms = new EcsSms();
                        $sms->send(C('sms_shop_mobile'), sprintf(L('order_payed_sms'), $order_sn, $order['consignee'], $order['mobile']), '', 13, 1);
                    }
                    /* 如果安装微信通,订单支付成功消息提醒 */
                    if(class_exists('WechatController')) {
                        $pushData = array(
                            
                            'first' => array('value' => '您的订单已支付成功','color' => '#173177'),
                            'keyword1' => array('value' => $order_sn,'color' => '#000'),  // 订单号
                            'keyword2' => array('value' => '已付款','color' => '#000'),   // 付款状态
                            'keyword3' => array('value' => local_date('Y-m-d H:i', time()),'color' => '#000'),  //付款时间
                            'keyword4' => array('value' => C('shop_name'),'color' => '#000'),       // 商户名
                            'keyword5' => array('value' => $pay_log['order_amount'].'元','color' => '#000'),             // 付款金额
                            'remark' => array('value' => '我们会尽快给您安排发货','color' => '#173177')
                            
                        );
                      
                        // $url = __URL__ . 'index.php?c=user&a=order_detail&order_id='.$order_id;
                        $order_url = __HOST__ . U('user/order_detail',array('order_id'=> $order_id));
                        $url = str_replace('api/notify/wxpay.php', '', $order_url);
                        pushTemplate('OPENTM204987032', $pushData, $url, $order['user_id']);
                    }

                    /* 对虚拟商品的支持 */
                    $virtual_goods = model('OrderBase')->get_virtual_goods($order_id);
                    if (!empty($virtual_goods)) {
                        $msg = '';
                        if (!model('OrderBase')->virtual_goods_ship($virtual_goods, $msg, $order_sn, true)) {
                            $pay_success = L('pay_success') . '<div style="color:red;">' . $msg . '</div>' . L('virtual_goods_ship_fail');
                            L('pay_success', $pay_success);
                        }

                        /* 如果订单没有配送方式，自动完成发货操作 */
                        if ($order['shipping_id'] == -1) {
                            /* 将订单标识为已发货状态，并记录发货记录 */
                            $sql = 'UPDATE ' . $this->pre .
                                    "order_info SET shipping_status = '" . SS_SHIPPED . "', shipping_time = '" . time() . "'" .
                                    " WHERE order_id = '$order_id'";
                            $this->query($sql);

                            /* 记录订单操作记录 */
                            model('OrderBase')->order_action($order_sn, OS_CONFIRMED, SS_SHIPPED, $pay_status, $note, L('buyer'));
                            $integral = model('Order')->integral_to_give($order);
                            //model('ClipsBase')->log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf(L('order_gift_integral'), $order['order_sn']));
                            model('ClipsBase')->new_log_account_change($order['user_id'],intval($integral['rank_points']),sprintf(L('order_gift_integral'), $order['order_sn']),ACT_OTHER,5);
                            model('ClipsBase')->new_log_account_change($order['user_id'],intval($integral['custom_points']),sprintf(L('order_gift_integral'), $order['order_sn']),ACT_OTHER,6);
                        }
                    }
                } elseif ($pay_log['order_type'] == PAY_SURPLUS) {
                    $sql = 'SELECT `id` FROM ' . $this->pre . "user_account WHERE `id` = '$pay_log[order_id]' AND `is_paid` = 1  LIMIT 1";
                    $res = $this->row($sql);
                    $res_id = $res['id'];
                    if (empty($res_id)) {
                        /* 更新会员预付款的到款状态 */
                        $sql = 'UPDATE ' . $this->pre .
                                "user_account SET paid_time = '" . time() . "', is_paid = 1" .
                                " WHERE id = '$pay_log[order_id]' LIMIT 1";
                        $this->query($sql);

                        /* 取得添加预付款的用户以及金额 */
                        $sql = "SELECT user_id, amount FROM " . $this->pre .
                                "user_account WHERE id = '$pay_log[order_id]'";
                        $arr = $this->row($sql);

                        /* 修改会员帐户金额 */
                        $_LANG = array();
                        include_once(ROOT_PATH . 'languages/' . C('lang') . '/user.php');
                       // model('ClipsBase')->log_account_change($arr['user_id'], $arr['amount'], 0, 0, 0, $_LANG['surplus_type_0'], ACT_SAVING);
                        model('ClipsBase')->new_log_account_change($arr['user_id'],$arr['amount'],$_LANG['surplus_type_0'],ACT_SAVING,1);

                    }
                }
            } else {
                /* 取得已发货的虚拟商品信息 */
                $post_virtual_goods = model('OrderBase')->get_virtual_goods($pay_log['order_id'], true);

                /* 有已发货的虚拟商品 */
                if (!empty($post_virtual_goods)) {
                    $msg = '';
                    /* 检查两次刷新时间有无超过12小时 */
                    $sql = 'SELECT pay_time, order_sn FROM ' . $this->pre . "order_info WHERE order_id = '$pay_log[order_id]'";
                    $row = $this->row($sql);
                    $intval_time = time() - $row['pay_time'];
                    if ($intval_time >= 0 && $intval_time < 3600 * 12) {
                        $virtual_card = array();
                        foreach ($post_virtual_goods as $code => $goods_list) {
                            /* 只处理虚拟卡 */
                            if ($code == 'virtual_card') {
                                foreach ($goods_list as $goods) {
                                    if ($info = model('OrderBase')->virtual_card_result($row['order_sn'], $goods)) {
                                        $virtual_card[] = array('goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'info' => $info);
                                    }
                                }

                                ECTouch::view()->assign('virtual_card', $virtual_card);
                            }
                        }
                    } else {
                        $msg = '<div>' . L('please_view_order_detail') . '</div>';
                    }
                    $pay_success = L('pay_success') . $msg;
                    L('pay_success', $pay_success);
                }

                /* 取得未发货虚拟商品 */
                $virtual_goods = model('OrderBase')->get_virtual_goods($pay_log['order_id'], false);
                if (!empty($virtual_goods)) {
                    $pay_success = L('pay_success') . '<br />' . L('virtual_goods_ship_fail');
                    L('pay_success', $pay_success);
                }
            }
        }
    }

    /**
     * 根据out_trade_no订单号获取log_id;
     */
    function get_log_id($out_trade_no = null) {
        $out_trade_no = (string)$out_trade_no;
        $sql = 'SELECT l.log_id ' .
            'FROM ' . $this->pre .
            "pay_log AS l INNER JOIN " .$this->pre .
            "order_info AS i ON i.order_id = l.order_id AND i.order_sn = '$out_trade_no'";
        $order = $this->row($sql);
        return $order['log_id'];
    }
        /*升级VIP权限和时效*/
function upgradeToVip($user_id,$order_id){
    //获得此订单的产品列表
  
    $sql0 = "select * from " . $this->pre. "order_goods  WHERE order_id=" . $order_id;
    $order_goods = $this->query($sql0);
    //var_dump($order_goods);exit();
    foreach ($order_goods as $key => $value) {
        $sql = "select integral,goods_number,rank_integral from ".$this->pre."goods where goods_id = ".$value['goods_id'];
     
        $res = $this->query($sql);
   
         /*查询每个商品的等级积分,0或者10000*/
        /*如果是3000,那么取这个商品的数量当作续费等级的年限*/
        $_SESSION['user_rank'] = 2;
        if($res['0']['rank_integral']>=10000){
             $sql1 = "select * from ". $this->pre."users  WHERE user_id = '$user_id'";
       
            $res1 = $this->query($sql1);
            
            $time = time();

            if($res1['0']['vip_validated_firsttime']){
                //在原先的失效时间上面加上num*
                
                //$t+365*24*60*60
                $sql2 = "UPDATE ". $this->pre."users SET rank_points = rank_points + '{$res['0']['rank_integral']}', vip_validated = vip_validated + '{$value['goods_number']}' WHERE user_id = '$user_id'";


             
                // file_put_contents(ROOT_PATH . "/20180999992.txt", $sql2.$value['goods_id'],FILE_APPEND);   
                $res2 = $this->query($sql2);

             }else{

                $sql2 = "UPDATE ". $this->pre."users SET rank_points = rank_points + '{$res['0']['rank_integral']}', vip_validated = vip_validated + '{$value['goods_number']}',vip_validated_firsttime = '$time' WHERE user_id = '$user_id'";
             
                
                
                $res2 = $this->query($sql2);

             }
        }
       
       
        

    }
     
    //  /* 更新库存 */
     


    


}
    //  /* 更新库存 */
     
 /*会员升级vip，入团以及升级*/
    function updateVip($user_id,$viplevel){
 /*如果用户的入团金额是否达到省代理29800 level：5，全国代理：99800 level:6，LKD股东：499800 LEVEL:7*/
        $sql = "SELECT area_amount_total,user_rank FROM ecs_users WHERE user_id = '$user_id'";
        $row= $this->row($sql);
        if($row['area_amount_total']>499800){
            
            $sql = "UPDATE ecs_users SET user_rank = '7' WHERE user_id = '$user_id'";
               
             $this->query($sql);
        }elseif($row['area_amount_total']>99800){
            $sql = "UPDATE ecs_users SET user_rank = '6' WHERE user_id = '$user_id'";
               
             $this->query($sql);
        }elseif($row['area_amount_total']>29800){
            $sql = "UPDATE ecs_users SET user_rank = '5' WHERE user_id = '$user_id'";
               
             $this->query($sql);
        }else{
            if($row['user_rank']<$viplevel){
                
             $sql = "UPDATE ecs_users SET user_rank = '$viplevel' WHERE user_id = '$user_id'";
               
             $this->query($sql);
            }
            
        }

    


    }
    function updateAreaTotal($user_id,$order_amount){
         // 统计入团金额
        $sql = "UPDATE `ecs_users` SET `area_amount_total` = `area_amount_total` + '" . $order_amount . "' WHERE user_id = '$user_id'";
       
         $this->query($sql);
    }

    function updateCommTotal($user_id,$order_amount){
         // 统计零售金额
        $sql = "UPDATE `ecs_users` SET `com_amount_total` = `com_amount_total` + '" . $order_amount . "' WHERE user_id = '$user_id'";
       
        $this->query($sql);
    }
    
}

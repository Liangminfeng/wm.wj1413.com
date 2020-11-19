<?php
/**

 * ECTouch Open Source Project

 * ============================================================================

 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.

 * ----------------------------------------------------------------------------

 * 文件名称：FlowControoller.class.php

 * ----------------------------------------------------------------------------

 * 功能描述：购物流程控制器

 * ----------------------------------------------------------------------------

 * Licensed ( http://www.ectouch.cn/docs/license.txt )

 * ----------------------------------------------------------------------------

 */

/* 访问控制 */

defined('IN_ECTOUCH') or die('Deny Access');



class FlowController extends CommonController {

   
   
    private function check_login()
    {
        // 不需要登錄的操作或自己驗證是否登錄（如ajax處理）的方法
        
        // 未登錄處理
       if(empty($_SESSION['user_id'])){

            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect(url('user/register', array(
                'back_act' => urlencode($back_act)
            )));
            exit();

       }
            
        
        
        // 已經登錄，不能訪問的方法
        $deny = array(
            'login',
            'register'
        );
        // if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0 && in_array($this->action, $deny)) {
        //     $this->redirect(url('index/index'));
        //     exit();
        // }
    }
    /**
     * 购物车列表
     */
    public function index() {

        $this->check_login();

        $_SESSION['flow_type'] = CART_GENERAL_GOODS;
        //$_SESSION['user_id'];
        /* 如果是一步购物，跳到结算中心 */
        
        // if (C('one_step_buy') == '1') {

        //     ecs_header("Location: " . url('flow/checkout') . "\n");
        // }

        // 取得商品列表，计算合计
        $cart_goods = model('Order')->get_cart_goods('',0);
        //var_dump($cart_goods);exit;
        $shipping_id = '';
        $car_shop = '';
   
        if(!empty($cart_goods['goods_list'])){
            foreach($cart_goods['goods_list'] as $k => $v){
               
                $goods_info = model('Goods')->get_goods_info($v['goods_id']);
                $cart_goods ['goods_list'][$k]['freight_type'] = $goods_info['freight_type'];
                $cart_goods ['goods_list'][$k]['shipping_id'] = $goods_info['shipping_id'];
                $cart_goods ['goods_list'][$k]['fixed_freight'] = $goods_info['fixed_freight'];
            }
            
            foreach($cart_goods['goods_list'] as $k => $v){
               if($v['freight_type'] == 1){      //固定运费划分
                    $car_shop['fixed'][$k] = $v;
               }else{
                    $car_shop['tpl'][$k] = $v;   //运费模板划分
                    $shipping_id[] = $v['shipping_id'];
               }
            }

            $count_freight = "";
            if($car_shop['fixed'] && is_array($car_shop['fixed'])){
                foreach($car_shop['fixed'] as $k => $v){
                    if($v['is_shipping'] == 0){  //是否包邮  1 包邮 0 不包邮
                        $freight =  $v['goods_number'] * $v['fixed_freight'];
                        $car_shop['fixed'][$k]['fixed_freight'] =  $v['goods_number'] * $v['fixed_freight'];
                        $car_shop['fixed'][$k]['freight'] = $v['fixed_freight'];
                    }
                    $count_freight += $freight;
                }
            }
       
            if(1){
                $shipping_id = array_unique($shipping_id);
                $shipping_list = model('Goodstransport')->goods_transport_info();

                $tpl_goods = '';
                if(1){
                    foreach($car_shop['tpl'] as $k1 => $v1){
                        
                          
                                $tpl_goods[$k1]['child'][] = $v1;
                            
                       
                    }
                }

                $arr = '';
                if(is_array($tpl_goods)) {
                    foreach ($tpl_goods as $k => $v) {
                        if(is_array($v['child'])){
                            foreach ($v['child'] as $k2 => $v2) {
                                foreach ($shipping_id as $k1 => $v1) {
                                    if ($v['shipping_id'] == $v1) {
                                        $arr[$k1]['shipping_id'] = $v['shipping_id'];
                                        $arr[$k1]['shipping_name'] = $v['shipping_name'];
                                        $arr[$k1]['child'][] = $v2;
                                    }
                                }
                            }
                        }
                    }
                }

                $car_shop['tpl'] = $arr;
            }
        }

        if(!isset($car_shop['tpl'])){
            $car_shop['tpl'] = 0;
        }

        if(!isset($car_shop['fixed'])){
            $car_shop['fixed'] = 0;
        }

        if(empty($cart_goods['goods_list'])){
            $goods_list = 0;
        }else{
            $goods_list = 1;
        }


        $cart_goods_buy = model('Order')->get_cart_goods(1);
        
        $total = $cart_goods_buy['total'];

        $order_type = $_GET['order_type'];
        if(empty($order_type)){
            $order_type = 0;
        }
        $this->assign('order_type',$order_type);
        $this->assign('goods_count_number',$total['total_number']);
        $this->assign('goods_count_money',number_format($total['goods_amount'],2));
    
        $this->assign('goods_list_tpl',$car_shop['tpl']);
        $this->assign('goods_list_fixed',$car_shop['fixed']);
        $this->assign('goods_list',$goods_list);

        $this->assign('total', $cart_goods ['total']);
    
        if ($cart_goods['goods_list']) {
            // 相关产品
            $linked_goods = model('Goods')->get_linked_goods($cart_goods ['goods_list']);
            $this->assign('linked_goods', $linked_goods);
        }
       
        // 购物车的描述的格式化
        $this->assign('shopping_money', sprintf(L('shopping_money'), $cart_goods ['total'] ['goods_price']));
        $this->assign('market_price_desc', sprintf(L('than_market_price'), $cart_goods ['total'] ['market_price'], $cart_goods ['total'] ['saving'], $cart_goods ['total'] ['save_rate']));
        // 取得优惠活动
        $favourable_list = model('Flow')->favourable_list_flow($_SESSION ['user_rank']);
        usort($favourable_list, array("FlowModel", "cmp_favourable"));
        $this->assign('favourable_list', $favourable_list);
        // 计算折扣
        $discount = model('Order')->compute_discount();
        $this->assign('discount', $discount ['discount']);
        // 折扣信息
        $favour_name = empty($discount ['name']) ? '' : join(',', $discount ['name']);
        $this->assign('your_discount', sprintf(L('your_discount'), $favour_name, price_format($discount ['discount'])));
        // 增加是否在购物车里显示商品图
        $this->assign('show_goods_thumb', C('show_goods_in_cart'));
        // 增加是否在购物车里显示商品属性
        $this->assign('show_goods_attribute', C('show_attr_in_cart'));
        // 取得购物车中基本件ID
        $condition = "session_id = '" . SESS_ID . "' " . "AND rec_type = '" . CART_GENERAL_GOODS . "' " . "AND is_gift = 0 " . "AND extension_code <> 'package_buy' " . "AND parent_id = 0 ";
        $parent_list = $this->model->table('cart')->field('goods_id')->where($condition)->getCol();
        //根据基本件id获取 购物车中商品配件列表
        //$fittings_list = model('Goods')->get_goods_fittings($parent_list);
        $this->assign('fittings_list', $fittings_list);
        $this->assign('currency_format', C('currency_format'));
        $this->assign('integral_scale', C('integral_scale'));
        $this->assign('step', 'cart');
        $this->assign('page_title', L('shopping_cart'));
        $this->display('flow.dwt');
    }
    public function ajaxgetuserlist()
    {
      
        
        

        $size = 5;
        $page = (int)($_POST['page']?$_POST['page']:1);

        $count =  (int)(model('Users')->get_userlist_num($_POST['province_id'],$_POST['city_id']));

        $data = model('Users')->get_userlist_detail($_POST['province_id'],$_POST['city_id'],$size, ($page - 1) * $size);

        $num = count($data);

        foreach ($data  as $key => $value) {
                # code...
                          
                          $html.='<div class="inviter1">';
                          $html.='<div class="inviter_left">';
                          $html.='<div>';
                          $html.='<img src="'.$value['user_avatar'].'">';
                          $html.='</div>';
                          $html.='<div>';
                          if($value['nick_name']){
                            $html.='<p>'.$value['nick_name'].'</p>';
                        }else{
                            $value['user_name'] = substr_replace($value['user_name'],'****',3,4);;
                            $html.='<p>'.$value['user_name'].'</p>';
                        }
                          
                          $html.='<p>邀请码：<span>'.$value['invite_code'].'</span></p></div></div>';
                          $html.='<div class="inviter_right">';
                          if($value['nick_name']){
                            $html.='<a href="javascript:void(0)" onclick="inviter_right(this)" code='.$value['invite_code'].' user_name='.$value['nick_name'].'>选择TA</a>';
                        }else{
                            $html.='<a href="javascript:void(0)" onclick="inviter_right(this)" code='.$value['invite_code'].' user_name='.$value['user_name'].'>选择TA</a>';
                        }
                          $html.='</div></div>';
                        

                 
           
                
            }
        
            if($num==5){

                $html .=' <div class="sb1" style="background: #fff;text-align: center;">';
                $html .='  <a href="javascript:;" class="gengduo more1" >换一批</a></div>  ';
            }
        if(!empty($data)){
             $result ['status'] = 1;

            $result ['data'] = $html;
             $result['count'] = $count;
             $result['page'] =$page;
            die(json_encode($result)); 
        }else{
            $result ['status'] = 0;

            $result ['data'] = array();
             $result['count'] = $count;
             $result['page'] =$page;
            die(json_encode($result));
        }
        
        

        
    }
    /**
     * 订单确认
     */
    public function checkout() {
        /* 取得购物类型 */
        $order_type = isset($_GET['order_type'])?$_GET['order_type']:0;
        $user_info = model('Order')->user_info($_SESSION ['user_id']);

        $cart_value = rtrim($_POST['cart_value'],",");
  
        $flow_type = isset($_SESSION ['flow_type']) ? intval($_SESSION ['flow_type']) : CART_GENERAL_GOODS;

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS) {
            $this->assign('is_group_buy', 1);
        } /* 积分兑换商品 */ elseif ($flow_type == CART_EXCHANGE_GOODS) {
            $this->assign('is_exchange_goods', 1);
        } else {
            // 正常购物流程 清空其他购物流程情况
            $_SESSION ['flow_order'] ['extension_code'] = '';
        }
        /* 检查购物车中是否有商品333333
         */

        if($_GET['one_step_buy']){
            /*入团区产品*/
        
            if($user_info['user_rank']){
            //重购入团产品
            $goods_area = 2;  

            }else{
                /*第一次购买入团产品*/

                $goods_area = 1;
                

            }
            $this->assign('one_step_buy',1);
            if($order_type==9){
                    $condition = "session_id = '" . SESS_ID . "' " . "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type' AND  FIND_IN_SET('2',goods_area)"  ;
                }elseif($order_type==10){
                    $condition = "session_id = '" . SESS_ID . "' " . "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type' AND  FIND_IN_SET('3',goods_area)"  ;
                }else{
                    $condition = "session_id = '" . SESS_ID . "' " . "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type' AND FIND_IN_SET('1',goods_area)"  ;
                }
        
        }else{
              $goods_area = 0;
              $this->assign('one_step_buy',0);
            $condition = "session_id = '" . SESS_ID . "' " . "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";
        }


        $count = $this->model->table('cart')->field('COUNT(*)')->where($condition)->getOne();
     

        if ($count == 0) {
            show_message(L('no_goods_in_cart'), '', '', 'warning');
        }

        //  检查用户是否已经登录 如果用户已经登录了则检查是否有默认的收货地址 如果没有登录则跳转到登录和注册页面
        if (empty($_SESSION ['direct_shopping']) && $_SESSION ['user_id'] == 0) {
            /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
          //  $this->redirect(url('user/login',array('step'=>'flow')));
             $this->redirect(url('user/register',array('step'=>'flow')));
            exit;
        }
        // 获取收货人信息
        $consignee = model('Order')->get_consignee($_SESSION ['user_id']);
        $user_default_address = model('UserAddRess')->user_default_address($_SESSION ['user_id']);
        
      //  $user_default_address = model('UserAddRess')->user_default_address($_SESSION ['user_id']);
        $this->assign('shipping_address', $user_default_address['shipping_address']);
        /* 检查收货人信息是否完整 */

        if (!model('Order')->check_consignee_info($consignee, $flow_type)) {
            /* 如果不完整则转向到收货人信息填写界面 */
            //echo 3;exit;
            if($order_type){
                ecs_header("Location: " . url('flow/consignee_list',array('one_step_buy'=>'1','order_type'=>$_GET['order_type'])) . "\n");
            }else{
       
                ecs_header("Location: " . url('flow/consignee_list',array('one_step_buy'=>'1','order_type'=>$_GET['order_type'])) . "\n");
            }
            


        }
        // 获取配送地址
        $consignee_list = model('Users')->get_consignee_list($_SESSION ['user_id']);
        $this->assign('consignee_list', $consignee_list);
        //获取默认配送地址
        $address_id = $this->model->table('users')->field('address_id')->where("user_id = '" . $_SESSION['user_id'] . "' ")->getOne();
        $this->assign('address_id', $address_id);

        $_SESSION ['flow_consignee'] = $consignee;
        $this->assign('consignee', $consignee);
 
        /* 对商品信息赋值 */
        if(!$_GET['one_step_buy']&&$order_type==0){
                /*普通零售产品*/
  
             $cart_goods = model('Order')->newnew_cart_goods($flow_type,$cart_value,0);
             
            //$cart_goods = model('Order')->cart_goods_group($flow_type,$cart_value); 
        }else{
        
           if($order_type ==9){

                 $cart_goods = model('Order')->cart_goods($flow_type,$cart_value,2); // 取得商品列表，计算合计
           }elseif($order_type ==10){

                 $cart_goods = model('Order')->cart_goods($flow_type,$cart_value,3); // 取得商品列表，计算合计
               
           }else{

                 $cart_goods = model('Order')->cart_goods($flow_type,$cart_value,1); // 取得商品列表，计算合计
           }
           
        }

                
         foreach($cart_goods as $k => $v){
            $goods_count_money += $v['goods_price'] * $v['goods_number'];
            $goods_count_number += $v['goods_number'];
            $goods_id[] = $v['goods_id'];
            $integral_total  += $v['integral']*$v['goods_number']; 

        }
        /*是否显示推荐人信息*/
        /*如果是从普通会员变成vip,或者加入经销商，则显示*/
        if($user_info['user_vip']==0&&($order_type==9||$order_type==1)){
            $show_invitestatus = 1;
        }else{
            $show_invitestatus = 0;
        }

        $this->assign('show_invitestatus',$show_invitestatus);
        $this->assign('order_type',$_GET['order_type']);
        
     
        $this->assign('integral_total', $integral_total);
        $this->assign('goods_list', $cart_goods);
        if($_GET['order_type']==10){
          $this->assign('train_id',$cart_goods['0']['train_id']);  
        }else{
          $this->assign('train_id',0);  
        }
        
        $this->assign('goods_rtcat_id',$cart_goods['0']['rtcat_id']);
        $this->assign('goods_lscat_id',$cart_goods['0']['lscat_id']);
        /* 对是否允许修改购物车赋值 */
        if ($flow_type != CART_GENERAL_GOODS || C('one_step_buy') == '1') {
            $this->assign('allow_edit_cart', 0);
        } else {
            $this->assign('allow_edit_cart', 1);
        }

        // 取得购物流程设置
        $this->assign('config', C('CFG'));
        // 取得订单信息
        $order = model('Order')->flow_order_info();
        $order['pay_id'] =1;
        /* 计算折扣 */
        if ($flow_type != CART_EXCHANGE_GOODS && $flow_type != CART_GROUP_BUY_GOODS) {
            $discount = model('Order')->compute_discount();
            $this->assign('discount', $discount ['discount']);
            $favour_name = empty($discount ['name']) ? '' : join(',', $discount ['name']);
            $this->assign('your_discount', sprintf(L('your_discount'), $favour_name, price_format($discount ['discount'])));
        }
       
        $order['inv_type'] = $_SESSION['inv_type'];
        $order['need_inv'] = empty($order['inv_type']) ? 0 : 1;
        
        //计算订单的费用

        $total = model('Users')->order_fee($order, $cart_goods, $consignee,0);

        
        $this->assign('shipping_address', $user_default_address['shipping_address']);
        
        $this->assign('shopping_money', sprintf(L('shopping_money'), $total ['formated_goods_price']));
        $this->assign('market_price_desc', sprintf(L('than_market_price'), $total ['formated_market_price'], $total ['formated_saving'], $total ['save_rate']));

        /* 取得可以得到的积分和红包 */
        $this->assign('total_integral', model('Order')->cart_amount(false, $flow_type) - $total ['bonus'] - $total ['integral_money']);
        $this->assign('total_bonus', price_format(model('Order')->get_total_bonus(), false));

        $parent_invite_code = model('Users')->getParentInviteCode($_SESSION['user_id']);

        $this->assign('invitecode',$parent_invite_code);
        /* 取得配送列表 */
        $region = array(
            $consignee ['country'],
            $consignee ['province'],
            $consignee ['city'],
            $consignee ['district']
        );
        $shipping_list = model('Shipping')->available_shipping_list($region);
     
        $cart_weight_price = model('Order')->cart_weight_price($flow_type);
        $cart_weight_price['old_preic'] = $cart_weight_price ['amount'];
        $insure_disabled = true;
        $cod_disabled = true;

        //团购商品计算商品价格
        if ($order['extension_code'] == 'group_buy') {
            $group_buy = model('GroupBuyBase')->group_buy_info($order['extension_id']);

        }
        // 查看购物车中是否全为免运费商品，若是则把运费赋为零
        $condition = "`session_id` = '" . SESS_ID . "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
        $shipping_count = $this->model->table('cart')->field('count(*)')->where($condition)->getOne();

        foreach ($shipping_list as $key => $val) {

            $shipping_cfg = unserialize_config($val ['configure']);
            $shipping_fee = ($shipping_count == 0 and $cart_weight_price ['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val ['configure']), $cart_weight_price ['weight'], $cart_weight_price['old_preic'] = $group_buy['cur_price'] > 0 ? $group_buy['cur_price'] : $cart_weight_price['old_preic'], $cart_weight_price ['number']);

            $shipping_list [$key] ['format_shipping_fee'] = price_format($shipping_fee, false);
            $shipping_list [$key] ['shipping_fee'] = $shipping_fee;
            $shipping_list [$key] ['free_money'] = price_format($shipping_cfg ['free_money'], false);
            $shipping_list [$key] ['insure_formated'] = strpos($val ['insure'], '%') === false ? price_format($val ['insure'], false) : $val ['insure'];

            /* 当前的配送方式是否支持保价 */
            if ($val ['shipping_id'] == $order ['shipping_id']) {
                $insure_disabled = ($val ['insure'] == 0);
                $cod_disabled = ($val ['support_cod'] == 0);
            }
            // 兼容过滤ecjia配送方式
            if (substr($val['shipping_code'], 0 , 5) == 'ship_') {
                unset($shipping_list[$key]);
            }
        }

        $res = explode(",", $cart_goods['0']['rtcat_id']);

        if($cart_goods['0']['goods_area']==1&&in_array("1", $res)){
            $show_rt_info = 1;
        }else{
            $show_rt_info = 0;
        }
        $this->assign('goods_vip',$cart_goods['0']['goods_vip']);
        $this->assign('goods_area',$cart_goods['0']['goods_area']);

        $this->assign('resource',$_SESSION['resource']);
        $this->assign('show_rt_info',$show_rt_info);
        $this->assign('shipping_list', $shipping_list);
        $this->assign('shipping_fee', $total['shipping_fee']);
        $this->assign('shipping_name', $shipping_list['0']['shipping_name']);
        $this->assign('insure_disabled', $insure_disabled);
        $this->assign('cod_disabled', $cod_disabled);
       // $total['shipping_fee'] = (intval($shipping_list['0']['shipping_fee']));
        
        $total['amount1'] = $total['goods_price']+floatval($total['shipping_fee']);
       // var_dump($total);exit();
        
        /* 取得支付列表 */
        if ($order ['shipping_id'] == 0) {
            $cod = true;
            $cod_fee = 0;
        } else {

            $shipping = model('Shipping')->shipping_info($order ['shipping_id']);
            $cod = $shipping ['support_cod'];

            if ($cod) {
                /* 如果是团购，且保证金大于0，不能使用货到付款 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $group_buy_id = $_SESSION ['extension_id'];
                    if ($group_buy_id <= 0) {
                        show_message('error group_buy_id');
                    }
                    $group_buy = model('GroupBuyBase')->group_buy_info($group_buy_id);
                    if (empty($group_buy)) {
                        show_message('group buy not exists: ' . $group_buy_id);
                    }

                    if ($group_buy ['deposit'] > 0) {
                        $cod = false;
                        $cod_fee = 0;

                        /* 赋值保证金 */
                        $this->assign('gb_deposit', $group_buy ['deposit']);
                    }
                }

                if ($cod) {
                    $shipping_area_info = model('Shipping')->shipping_area_info($order ['shipping_id'], $region);
                    $cod_fee = $shipping_area_info ['pay_fee'];
                }
            } else {
                $cod_fee = 0;
            }
        }

        // 给货到付款的手续费加<span id>，以便改变配送的时候动态显示
        $payment_list = model('Order')->available_payment_list_new(1, $cod_fee,false,$_SESSION['user_id']);
        
        if (isset($payment_list)) {
            foreach ($payment_list as $key => $payment) {
                // 只保留显示手机版支付方式
                if(!file_exists(ROOT_PATH . 'plugins/payment/'.$payment['pay_code'].'.php')){
                    unset($payment_list[$key]);
                }
                if ($payment ['is_cod'] == '1') {
                    $payment_list [$key] ['format_pay_fee'] = '<span id="ECS_CODFEE">' . $payment ['format_pay_fee'] . '</span>';
                }

                /* 如果有易宝神州行支付 如果订单金额大于300 则不显示 */
                if ($payment ['pay_code'] == 'yeepayszx' && $total ['amount'] > 300) {
                    unset($payment_list [$key]);
                }
                /* 如果有余额支付 */
                if ($payment ['pay_code'] == 'balance') {
                    /* 如果未登录，不显示 */
                    if ($_SESSION ['user_id'] == 0) {
                        unset($payment_list [$key]);
                    } else {
                        if ($_SESSION ['flow_order'] ['pay_id'] == $payment ['pay_id']) {
                            $this->assign('disable_surplus', 1);
                        }
                    }
                }
                // 如果不是微信浏览器访问并且不是微信会员 则不显示微信支付
                // if ($payment ['pay_code'] == 'wxpay' && !is_wechat_browser() && empty($_SESSION['openid'])) {
                //     unset($payment_list [$key]);
                // }
                // 兼容过滤ecjia支付方式
                if (substr($payment['pay_code'], 0 , 4) == 'pay_') {
                    unset($payment_list[$key]);
                }
            }
        }

        $this->assign('payment_list', $payment_list);

        /* 取得包装与贺卡 */
        if ($total ['real_goods_count'] > 0) {
            /* 只有有实体商品,才要判断包装和贺卡 */
            $use_package = C('use_package');
            if (!isset($use_package) || C('use_package') == '1') {
                /* 如果使用包装，取得包装列表及用户选择的包装 */
                $this->assign('pack_list', model('Order')->pack_list());
            }

            /* 如果使用贺卡，取得贺卡列表及用户选择的贺卡 */
            $use_card = C('use_card');
            if (!isset($use_card) || C('use_card') == '1') {
                $this->assign('card_list', model('Order')->card_list());
            }
        }

        $user_info = model('Order')->user_info($_SESSION ['user_id']);

        /* 如果使用余额，取得用户余额 */
        $use_surplus = C('use_surplus');
        if ((!isset($use_surplus) || C('use_surplus') == '1') && $_SESSION ['user_id'] > 0 && $user_info ['user_money'] > 0) {
            // 能使用余额
            $this->assign('allow_use_surplus', 1);
            $this->assign('your_surplus', $user_info ['user_money']);
        }

        /* 如果使用积分，取得用户可用积分及本订单最多可以使用的积分 */

        $use_integral = C('use_integral');
        if ((!isset($use_integral) || C('use_integral') == '1') && $_SESSION ['user_id'] > 0 && $user_info ['pay_points'] > 0 && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS)) {
            // 能使用积分
            $this->assign('allow_use_integral', 1);
          
            $userinfo = model('users')->get_users($_SESSION ['user_id']);
         
            //$this->assign('order_max_integral',get_user_kd_canuse($_SESSION ['user_id'],$userinfo['user_rank'],$total['goods_price']));
            $this->assign('order_max_integral', model('Flow')->flow_available_points()); // 可用积分
      
        }
        $order_max_integral  = model('Flow')->flow_available_points();

             $your_integral =  model("ClipsBase")->finduseraccount($_SESSION ['user_id'],"pay_points");

            $this->assign('your_integral', $your_integral?$your_integral:0); // 用户积分
        $this->assign('order_max_integral', $order_max_integral); //

        /* 如果使用红包，取得用户可以使用的红包及用户选择的红包 */
        $use_bonus = C('use_bonus');
        if ((!isset($use_bonus) || C('use_bonus') == '1') && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS)) {
            // 取得用户可用红包
            $user_bonus = model('Order')->user_bonus($_SESSION ['user_id'], $total ['goods_price']);
            if (!empty($user_bonus)) {
                foreach ($user_bonus as $key => $val) {
                    $user_bonus [$key] ['bonus_money_formated'] = price_format($val ['type_money'], false);
                }
                $this->assign('bonus_list', $user_bonus);
            }

            // 能使用红包
            $this->assign('allow_use_bonus', 1);
        }

        /* 如果使用缺货处理，取得缺货处理列表 */
        $use_how_oos = C('use_how_oos');
        if (!isset($use_how_oos) || $use_how_oos == '1') {
            $oos = L('oos');
            if (is_array($oos) && !empty($oos)) {
                $this->assign('how_oos_list', L('oos'));
            }
        }

        /* 如果能开发票，取得发票内容列表 */
        $can_invoice = C('can_invoice');
        $invoice_content = C('invoice_content');
        if ((!isset($can_invoice) || $can_invoice == '1') && isset($invoice_content) && trim($invoice_content) != '' && $flow_type != CART_EXCHANGE_GOODS) {
            $inv_content_list = explode("\n", str_replace("\r", '', C('invoice_content')));
            $this->assign('inv_content_list', $inv_content_list);
            $inv_type_list = array();
            $invoice_type = C('invoice_type');
            foreach ($invoice_type['type'] as $key => $type) {
                if (!empty($type)) {
                    $inv_type_list [$type] = $type . ' [' . floatval($invoice_type['rate'] [$key]) . '%]';
                }
            }
            $this->assign('inv_type_list', $inv_type_list);
        }
        
     
        if ($order_max_integral>$your_integral)
                 {$integral_total = $your_integral;}else{
                 $integral_total =     $order_max_integral;
                 }
     
        $total['integral_total'] = $integral_total*0.01;
        
      //   $order_max_integral =  get_user_kd_canuse($_SESSION ['user_id'],$userinfo['user_rank'],$total['goods_price']);
      // var_dump($order_max_integral);

        $integral_money = value_of_integral(min($your_integral,$order_max_integral));
       
         $this->assign('country_list', model('RegionBase')->get_regions());

        $this->assign('shop_country', C('shop_country'));
        
        $this->assign('shop_province_list', model('RegionBase')->get_regions(1, C('shop_country')));
        $this->assign('integral_money',$integral_money);
        $this->assign('total', $total);
        $this->assign('goods_count_number', $goods_count_number);
        $this->assign('inv_payee', $_SESSION['inv_payee']);
        $this->assign('inv_type', $_SESSION['inv_type']);
        $this->assign('inv_text_id', $_SESSION['inv_text_id']);
        $this->assign('inv_content', $_SESSION['inv_content']);
        // print_r($inv_type_list);
        $this->assign('order', $order);
        /* 保存 session */
        $this->assign('user_info',$user_info);
        $balancemoney = model('ClipsBase')->finduseraccount($_SESSION['user_id'],"balance");
        $this->assign('user_money',$balancemoney);

        $_SESSION ['flow_order'] = $order;
        $this->assign('currency_format', C('currency_format'));
        $this->assign('integral_scale', C('integral_scale'));

        $this->assign('step', ACTION_NAME);
        model('Common')->assign_dynamic('shopping_flow');
        $this->assign('cart_value',$cart_value);
        $this->assign('page_title', L('order_detail'));
        $parent_user_info =  model('Order')->user_info($user_info['parent_id']);
        $this->assign('parent_user_info',$parent_user_info);
        $user_name = model("Users")->getusername($parent_user_info['invite_code']);
        $this->assign('user_name',$user_name);
        $this->display('flow.dwt');
    }

    /**
     *  提交订单
     *列车产品拆单逻辑：
     *1.普通产品和列车产品一起购买的时候要拆成两个订单。2.列车产品购买10个的时候要拆成10个订单
   /**
     *  提交订单
     */
    public function done(){


   
        $traininfo = model('Train')->getTrainInfo($_POST['train_id']);


        $paysuccess = 0;
        /* 取得购物类型 */
        $flow_type = isset($_SESSION ['flow_type']) ? intval($_SESSION ['flow_type']) : CART_GENERAL_GOODS;
        /* 检查购物车中是否有商品 */
        
        $condition = " session_id = '" . SESS_ID . "' " . "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type' and is_buy=1";
        
        //var_dump($_POST);exit;
        $count = $this->model->table('cart')->field('COUNT(*)')->where($condition)->getOne();

        if ($count == 0) {
            
            show_message(L('no_goods_in_cart'), L("back_home_text"), './', 'warning');
        }

        /* 如果使用库存，且下订单时减库存，则减少库存 */
        if (C('use_storage') == '1' && C('stock_dec_time') == SDT_PLACE) {
            $cart_goods_stock = model('Order')->get_cart_goods();
            $_cart_goods_stock = array();
            foreach ($cart_goods_stock ['goods_list'] as $value) {
                $_cart_goods_stock [$value ['rec_id']] = $value ['goods_number'];
            }
            model('Flow')->flow_cart_stock($_cart_goods_stock);
            unset($cart_goods_stock, $_cart_goods_stock);
        }
        // 检查用户是否已经登录 如果用户已经登录了则检查是否有默认的收货地址 如果没有登录则跳转到登录和注册页面
        if (empty($_SESSION ['direct_shopping']) && $_SESSION ['user_id'] == 0) {
            /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
           // ecs_header("Location: " . url('user/login') . "\n");
             ecs_header("Location: " . url('user/register') . "\n");
        }

        // 获取收货人信息
        $consignee = model('Order')->get_consignee($_SESSION ['user_id']);
        /* 检查收货人信息是否完整 */
        if (!model('Order')->check_consignee_info($consignee, $flow_type)) {
            /* 如果不完整则转向到收货人信息填写界面 */
            ecs_header("Location: " . url('flow/consignee') . "\n");
        }
        $region = array(
            $consignee ['country'],
            $consignee ['province'],
            $consignee ['city'],
            $consignee ['district']
        );
        $shipping_list = model('Shipping')->available_shipping_list($region);
        // 处理接收信息
        foreach ($shipping_list as $key => $val) {

            $shipping_cfg = unserialize_config($val ['configure']);
            $shipping_fee = ($shipping_count == 0 and $cart_weight_price ['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val ['configure']), $cart_weight_price ['weight'], $cart_weight_price['old_preic'] = $group_buy['cur_price'] > 0 ? $group_buy['cur_price'] : $cart_weight_price['old_preic'], $cart_weight_price ['number']);

            $shipping_list [$key] ['format_shipping_fee'] = price_format($shipping_fee, false);
            $shipping_list [$key] ['shipping_fee'] = $shipping_fee;
            $shipping_list [$key] ['free_money'] = price_format($shipping_cfg ['free_money'], false);
            $shipping_list [$key] ['insure_formated'] = strpos($val ['insure'], '%') === false ? price_format($val ['insure'], false) : $val ['insure'];

            /* 当前的配送方式是否支持保价 */
            if ($val ['shipping_id'] == $order ['shipping_id']) {
                $insure_disabled = ($val ['insure'] == 0);
                $cod_disabled = ($val ['support_cod'] == 0);
            }
            // 兼容过滤ecjia配送方式
            if (substr($val['shipping_code'], 0 , 5) == 'ship_') {
                unset($shipping_list[$key]);
            }
        }
  
      if($_POST['real_name']){
        $updatedata['real_name']= $_POST['real_name'];
        model("Users")->update_info_user($_SESSION['user_id'],$updatedata);
      }
    


   
      
        $this->assign('shipping_list', $shipping_list);
        $this->assign('shipping_fee', $shipping_list['0']['shipping_fee']);
        $how_oos = I('post.how_oos', 0);
        $card_message = I('post.card_message', '');
        $inv_type = I('post.inv_type', '');
        $inv_payee = I('post.inv_payee', '');
        $cart_value = I('cart_value');
        $inv_content = I('post.inv_content', '');
        $postscript = I('post.postscript', '');
        $oos = L('oos.' . $how_oos);
        // 订单信息
        $user_info = model('Order')->user_info($_SESSION ['user_id']);
        if($_POST['goods_area']){
            /*属于入团区的商品分类*/
           
           

            $order_sale_type = $_POST['goods_area'];

        }else{
            /*零售区商品即399元vip套餐*/
            $order_sale_type = 0;
        }

      
        if(isset($_POST['vip_manage_account'])){

            $vip_manage_account = $_POST['vip_manage_account'];

        }
        /*使用的KD豆*/
        $kd_use = $_POST['integral'];

        $order = array(
            'shipping_id' => I('post.shipping'),
            'pay_id' => I('post.payment'), // 付款方式
            'pack_id' => I('post.pack', 0),
            'card_id' => isset($_POST ['card']) ? intval($_POST ['card']) : 0,
            'card_message' => $card_message,
            'surplus' => isset($_POST ['surplus']) ? floatval($_POST ['surplus']) : 0.00,
            'integral' => isset($_POST ['integral']) ? floatval($_POST ['integral']) : 0,
            'bonus_id' => isset($_POST ['bonus']) ? intval($_POST ['bonus']) : 0,
            'need_inv' => empty($_POST ['inv_type']) ? 0 : 1,
            'inv_type' => $_POST ['inv_type'],
            'inv_payee' => trim($_POST ['inv_payee']),
            'inv_content' => $_POST['inv_content'],
            'inv_text_id' => $_POST['inv_text_id'],
            'postscript' => $postscript,
            'how_oos' => isset($oos) ? addslashes("$oos") : '',
            'need_insure' => isset($_POST ['need_insure']) ? intval($_POST ['need_insure']) : 0,
            'user_id' => $_SESSION ['user_id'],
            'add_time' => time(),
            'order_status' => OS_UNCONFIRMED,
            'shipping_status' => SS_UNSHIPPED,
            'pay_status' => PS_UNPAYED,
            'order_sale_type' =>$order_sale_type,
       
            'agency_id' => model('Order')->get_agency_by_regions(array(
                $consignee ['country'],
                $consignee ['province'],
                $consignee ['city'],
                $consignee ['district']
            ))
        );
        

        if($order_sale_type==1&&$order['order_type']==1){
            /**/
            $order['join_country_id'] = 1;
            $order['join_province_id'] = $_POST['join_province_id'];

            $order['join_city_id'] = $_POST['join_city_id'];
        }

        /* 扩展信息 */
        if (isset($_SESSION ['flow_type']) && intval($_SESSION ['flow_type']) != CART_GENERAL_GOODS) {
            $order ['extension_code'] = $_SESSION ['extension_code'];
            $order ['extension_id'] = $_SESSION ['extension_id'];
        } else {
            $order ['extension_code'] = '';
            $order ['extension_id'] = 0;
        }
        /* 检查积分余额是否合法 */
        $user_id = $_SESSION ['user_id'];
        if ($user_id > 0) {

            $user_info = model('Order')->user_info($user_id);
            $order ['surplus'] = min($order ['surplus'], $user_info ['user_money'] + $user_info ['credit_line']);
            if ($order ['surplus'] < 0) {
                $order ['surplus'] = 0;
            }

            // 查询用户有多少积分
            $flow_points = model('Flow')->flow_available_points(); // 该订单允许使用的积分
            $your_integral =  model("ClipsBase")->finduseraccount($_SESSION ['user_id'],"pay_points");
            $user_points =$your_integral ; // 用户的积分总数

            $order ['integral'] = min($order ['integral'], $user_points, $flow_points);
            if ($order ['integral'] < 0) {
                $order ['integral'] = 0;
            }
        } else {
            $order ['surplus'] = 0;
            $order ['integral'] = 0;
        }

        /* 检查红包是否存在 */
        if ($order ['bonus_id'] > 0) {
            $bonus = model('Order')->bonus_info($order ['bonus_id']);
            if (empty($bonus) || $bonus ['user_id'] != $user_id || $bonus ['order_id'] > 0 || $bonus ['min_goods_amount'] > model('Order')->cart_amount(true, $flow_type)) {
                $order ['bonus_id'] = 0;
            }
        } elseif (isset($_POST ['bonus_sn'])) {
            $bonus_sn = trim($_POST ['bonus_sn']);
            $bonus = model('Order')->bonus_info(0, $bonus_sn);
            $now = time();
            if (empty($bonus) || $bonus ['user_id'] > 0 || $bonus ['order_id'] > 0 || $bonus ['min_goods_amount'] > model('Order')->cart_amount(true, $flow_type) || $now > $bonus ['use_end_date']) {

            } else {
                if ($user_id > 0) {
                    $sql = "UPDATE " . $this->model->pre . "user_bonus SET user_id = '$user_id' WHERE bonus_id = '$bonus[bonus_id]' LIMIT 1";
                    $this->model->query($sql);
                }
                $order ['bonus_id'] = $bonus ['bonus_id'];
                $order ['bonus_sn'] = $bonus_sn;
            }
        }

        /* 订单中的商品 */
        /*新增一个重消判断字段$_POST['goods_rtcat_id']*/

            if($_POST['goods_area']){

                  if($_POST['goods_rtcat_id']==2){

                     $cart_goods = model('Order')->newnew_cart_goods($flow_type,$cart_value,0);

                  }else{
                     $cart_goods = model('Order')->cart_goods($flow_type,$cart_value,$_POST['goods_area']);
                     $order['vip'] = $cart_goods[0]['goods_vip'];
                 
                  }
                  

              }else{
                  $cash_ratio = get_vip_ratio($_SESSION ['user_id']);
                  $cart_goods = model('Order')->cart_goods($flow_type,$cart_value);
                  $order['vip'] = 0;
               
              }
          
            $total_pv =0;
            $total_rtpv =0;
            $total_vippv =0;
       
        foreach($cart_goods as $k => $v){
            $goods_count_money += $v['goods_price'] * $v['goods_number'];
            $goods_count_number += $v['goods_number'];
            $goods_id[] = $v['goods_id'];
           
            $integral_total  += $v['integral']*$v['goods_number']; 

            if($_SESSION['user_vip']){
                /*如果vip用户*/
                $canuse_integral_money += $v['vipintegral']*$v['goods_number']; 
            }else{
                $canuse_integral_money += $v['vipintegral']*$v['goods_number']; 
            }



             /*有设置就按照设置的值,没有设置-1就按照商品的价格乘以比例，先固定100%*/
            // $v['goods_pv'] = $v['goods_pv']?$v['goods_pv']:$v['goods_price'];
            // if($v['goods_pv'] =='-1'){
            //     /*-1并且又是经销商商品*/
            //     if($v['goods_area']==1){
            //          $v['goods_pv']  =  ($v['goods_price']/10)*$v['goods_number'];
            //      }else{
            //          $v['goods_pv']  =  $v['goods_price']*$v['goods_number'];
            //      }
               

            // }else{
            //     $v['goods_pv']  =  $v['goods_price']*$v['goods_number'];
            // }
             if(strpos($_POST['goods_area'],"0")!==false){
                if($v['goods_pv']=='-1'){
                     $v['goods_pv']  =  ($v['goods_price']/10)*$v['goods_number'];
                 }else{
                     $v['goods_pv']  =  $v['goods_pv']*$v['goods_number'];
                 }
                   $total_pv = $total_pv + $v['goods_pv'];
            }
            if(strpos($_POST['goods_area'],"2")!==false){
                if($v['vipgoods_pv']=='-1'){
                     $v['vipgoods_pv']  =  ($v['goods_price']/10)*$v['goods_number'];
                 }else{
                     $v['vipgoods_pv']  =  $v['vipgoods_pv']*$v['goods_number'];
                 }
                     $total_vippv = $total_vippv + $v['vipgoods_pv'];
            }
             if(strpos($_POST['goods_area'],"1")!==false){
                if($v['rtgoods_pv']=='-1'){
                     $v['rtgoods_pv']  =  ($v['goods_price']/10)*$v['goods_number'];
                 }else{
                     $v['rtgoods_pv']  =  $v['rtgoods_pv']*$v['goods_number'];
                 }
              
                 $total_rtpv = $total_rtpv + $v['rtgoods_pv'];
            }
           
            //  if($v['goods_pv'] =='-1'){
            //     /*-1并且又是经销商商品*/
            //     if($v['goods_area']==1){
            //          $v['goods_pv']  =  ($v['goods_price']/10)*$v['goods_number'];
            //      }else{
            //          $v['goods_pv']  =  $v['goods_price']*$v['goods_number'];
            //      }
               

            // }else{
            //     $v['goods_pv']  =  $v['goods_price']*$v['goods_number'];
            // }
            
          
           
            

        }


        $order['canuse_integral_money'] = $canuse_integral_money;
        $order['integral_total'] = $integral_total;

        $order['shipping_fee'] = $shipping_list['0']['shipping_fee'];
        
        $this->assign('integral_total', $integral_total);
       if(strpos($_POST['goods_area'],"2")!==false){
         $order['order_type'] = '9';
      }
      /*当前行为是购买vip套餐的时候,判断是否是经销商，否则注册，是的话，判断当前等级是升级还是重购*/

      if($_POST['order_type']==9){

            if($_SESSION['user_rank']){
                /*是经销商*/
              
               if($cart_goods[0]['goods_vip']>$_SESSION['user_rank']){
                   /*升级*/ 
                   /*判断是差额升级还是原价升级*/
                    $userinfo = model("Users")->getuserinfo($_SESSION['user_id']);
                    $date1=strtotime(date('Y-m-d H:i:s'));
                    $date2=$userinfo['reg_time'];//注册时间
                    
                    $result=count_days($date1,$date2);

                    if($result<60){
                        /*差额升级*/
                        $order['order_type'] .= ','."3";
                    }else{
                        /*原价升级*/
                        $order['order_type'] .= ','."4";
                    }

               }
               if($cart_goods[0]['goods_vip']==$_SESSION['user_rank']){
                  $order['order_type'] .= ','."5";
               }
               if(strpos($_POST['goods_area'],"1")!==false){
                    // 入团海报
                        $order['order_type'] .= ','."1";
                    

                  }
            }else{
        
                 /*不是经销商*/
                if(strpos($_POST['goods_area'],"1")!==false){
                    // 入团海报
                        $order['order_type'] .= ','."1";
                    

                  }
            }
      }else{
        
                /*不是经销商*/
                if(strpos($_POST['goods_area'],"1")!==false){
                    // 入团海报
                     if($order['order_type']){
                        if($_POST['order_type']<>9){
                         $order['order_type'] .= ','.$_POST['order_type'];
                        }
                     }else{
                         $order['order_type'] = $_POST['order_type'];
                     }
                    

                  }else{
                     $order['order_type'] = $_POST['order_type'];
                  }
            }

      if(strpos($_POST['goods_area'],"0")!==false&&$_POST['order_type']<>10){
            $order['order_type'] = 0;
      }

        $this->assign('order_type',$order['order_type']);
        
        if (empty($cart_goods)) {
            show_message(L('no_goods_in_cart'), L('back_home'), './', 'warning');
        }

        /* 检查商品总额是否达到最低限购金额 */
        if ($flow_type == CART_GENERAL_GOODS && model('Order')->cart_amount(true, CART_GENERAL_GOODS) < C('min_goods_amount')) {
            show_message(sprintf(L('goods_amount_not_enough'), price_format(C('min_goods_amount'), false)));
        }

        /* 收货人信息 */
        foreach ($consignee as $key => $value) {
            $order [$key] = addslashes($value);
        }
        
        /* 判断是不是实体商品 */
    
        foreach ($cart_goods as $val) {
            /* 统计实体商品的个数 */
            if ($val ['is_real']) {
                $is_real_good = 1;
            }
             if($val['train_id']>0){
                $trainticket =1 ;
                $this->assign('trainticket', $trainticket);
             }

        }

        if (isset($is_real_good)) {

            // $res = $this->model->table('shipping')->field('shipping_id')->where("shipping_id=" . $order ['shipping_id'] . " AND enabled =1")->getOne();
            // if (!$res) {

            //     show_message(L('flow_no_shipping'));
            // }
        }

        /* 订单中的总额 */
        $total = model('Users')->order_fee($order, $cart_goods, $consignee,0);

        $order ['bonus'] = $total ['bonus'];
        $order ['goods_amount'] = $total ['goods_price'];
        $order ['discount'] = $total ['discount'];
        $order ['surplus'] = $total ['surplus'];
        $order ['tax'] = $total ['tax'];

        // 购物车中的商品能享受红包支付的总额
        $discount_amout = model('Order')->compute_discount_amount();
        // 红包和积分最多能支付的金额为商品总额
        $temp_amout = $order ['goods_amount'] - $discount_amout;
        if ($temp_amout <= 0) {
            $order ['bonus_id'] = 0;
        }

        /* 配送方式 */
        if ($order ['shipping_id'] > 0) {

            $shipping = model('Shipping')->shipping_info($order ['shipping_id']);
            $order ['shipping_name'] = addslashes($shipping ['shipping_name']);
        }
        $order ['shipping_fee'] = $total ['shipping_fee'];
        $order ['insure_fee'] = $total ['shipping_insure'];
     
        /* 支付方式 */
        if ($order ['pay_id'] > 0) {
            $payment = model('Order')->payment_info($order ['pay_id']);
            $order ['pay_name'] = addslashes($payment ['pay_name']);
        }

        $order ['pay_fee'] = $total ['pay_fee'];
        $order ['cod_fee'] = $total ['cod_fee'];

        /* 商品包装 */
        if ($order ['pack_id'] > 0) {
            $pack = model('Order')->pack_info($order ['pack_id']);
            $order ['pack_name'] = addslashes($pack ['pack_name']);
        }
        $order ['pack_fee'] = $total ['pack_fee'];

        /* 祝福贺卡 */
        if ($order ['card_id'] > 0) {
            $card = model('Order')->card_info($order ['card_id']);
            $order ['card_name'] = addslashes($card ['card_name']);
        }
        $order ['card_fee'] = $total ['card_fee'];
        $order ['order_amount'] = $total ['amount'];
        $balancemoney = model('ClipsBase')->finduseraccount($_SESSION['user_id'],"balance");
        if($payment ['pay_code'] == 'balance'){
            $this->assign('pay_code',"balance");
        }
        /* 如果全部使用余额支付，检查余额是否足够 */
        if ($payment ['pay_code'] == 'balance' && $order ['order_amount'] > 0) {
            if ($order ['surplus'] > 0) {    // 余额支付里如果输入了一个金额
                $order ['order_amount'] = $order ['order_amount'] + $order ['surplus'];
                $order ['surplus'] = 0;
            }
            if ($order ['order_amount'] > ($balancemoney)) {
                show_message(L('balance_not_enough'));
            } else {

                $balancepayment = true;
                $order ['surplus'] = $order ['order_amount'];
               // $order ['order_amount'] = 0;

                
            }
        }

        /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
        if ($order ['order_amount'] <= 0) {
      
            $order ['order_status'] = OS_CONFIRMED;
            $order ['confirm_time'] = time();
            $order ['pay_status'] = PS_PAYED;
            $order ['pay_time'] = time();
            $order ['order_amount'] = 0;
        }

        $order ['integral_money'] = $total ['integral_money'];
        $order ['integral'] = $total ['integral'];

        if ($order ['extension_code'] == 'exchange_goods') {
            $order ['integral_money'] = 0;
            $order ['integral'] = $total ['exchange_integral'];
        }

        $order ['from_ad'] = !empty($_SESSION ['from_ad']) ? $_SESSION ['from_ad'] : '0';
        $order ['referer'] = !empty($_SESSION ['referer']) ? addslashes($_SESSION ['referer']). 'Touch' : 'Touch';

        /* 记录扩展信息 */
        if ($flow_type != CART_GENERAL_GOODS) {
            $order ['extension_code'] = $_SESSION ['extension_code'];
            $order ['extension_id'] = $_SESSION ['extension_id'];
        }

        $parent_id = M()->table('users')->field('parent_id')->where("user_id=".$_SESSION['user_id'])->getOne();

        $order ['parent_id'] = $parent_id;

        /* 插入订单表 */
        $error_no = 0;
        
        //$order['shipping_fee'] = $shipping_list['0']['shipping_fee'];
        //$order ['integral_money'] = $order ['integral_total'];
        //$order ['integral'] = $total ['integral'];
        //$order['order_amount'] = $order['goods_amount']+floatval($shipping_list['0']['shipping_fee'])-floatval($order['integral_total']);
        // if($user_info['user_rank']){
        //     /**/
        //     $total_pv = $order['goods_amount']/10)

        // }else{

        // }
        $order['total_pv'] = $total_pv;
        $order['total_rtpv'] = $total_rtpv;
        $order['total_vippv'] = $total_vippv;
        $order['other_invite_code'] = $_POST['other_invite_code'];
        $other_invite_code = $order['other_invite_code'];
        $conditionparent['vip_manage_account'] = $other_invite_code;

         if(!$_SESSION['user_vip']){
              $parent_id = M()->table('users')->field('user_id')->where($conditionparent)->getOne();
              $order ['parent_id'] = $parent_id;
         }
      

       
       

        /*订单的入团类型，是注册还是重消还是升级0代表普通的零售订单*/

 $order['user_id'] = $_SESSION ['user_id'];
     
        do {
            $order ['order_sn'] = get_order_sn(); // 获取新订单号
            $new_order = model('Common')->filter_field('order_info', $order);
            $this->model->table('order_info')->data($new_order)->insert();
            $error_no = M()->errno();

            if ($error_no > 0 && $error_no != 1062) {
                die(M()->errorMsg());
            }
        } while ($error_no == 1062); // 如果是订单号重复则重新提交数据
        $new_order_id = M()->insert_id();
        
        if($new_order_id&&$order['pay_id']==1&&$kd_use>0){
         
               
                model('ClipsBase')->new_log_account_change($order ['user_id'],  $kd_use * (- 1),sprintf(L('pay_order'), $order ['order_sn']),ACT_OTHER, 6); 
        }
        $order ['order_id'] = $new_order_id;
  
        /* 插入订单商品 */
        if($_POST['goods_rtcat_id']==2){
            $sql = "INSERT INTO " . $this->model->pre . "order_goods( " . "order_id, goods_id, goods_pv,rtgoods_pv,vipgoods_pv,goods_name, goods_sn, product_id, goods_number, market_price, " . "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id) " . " SELECT '$new_order_id', goods_id,goods_pv,rtgoods_pv, vipgoods_pv,goods_name, goods_sn, product_id, goods_number, market_price, " . "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id " . " FROM " . $this->model->pre . "cart WHERE session_id = '" . SESS_ID . "' AND rec_type = '$flow_type' and is_buy=1 and (rtcat_id=2 or goods_area=0)";
        }else{
            $sql = "INSERT INTO " . $this->model->pre . "order_goods( " . "order_id, goods_id, goods_pv,rtgoods_pv,vipgoods_pv,goods_name, goods_sn, product_id, goods_number, market_price, " . "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id) " . " SELECT '$new_order_id', goods_id,goods_pv,rtgoods_pv, vipgoods_pv,goods_name, goods_sn, product_id, goods_number, market_price, " . "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id " . " FROM " . $this->model->pre . "cart WHERE session_id = '" . SESS_ID . "' AND rec_type = '$flow_type' and is_buy=1 and goods_area='".$_POST['goods_area']."'";
        }
        
  
        $this->model->query($sql);
        /* 修改拍卖活动状态 */
        if ($order ['extension_code'] == 'auction') {
            $sql = "UPDATE " . $this->model->pre . "goods_activity SET is_finished='2' WHERE act_id=" . $order ['extension_id'];
            $this->model->query($sql);
        }
       
        if($balancepayment){

         

            $paysuccess =1;

            //余额支付升级VIP处理
                   if (class_exists('WechatController') && is_wechat_browser() ) {
             
              $pushData = array(
                
                            'first' => array('value' => '您的订单已支付成功','color' => '#173177'),
                            'keyword1' => array('value' =>$order ['order_sn'],'color' => '#000'),  // 订单号
                            'keyword2' => array('value' => '已付款','color' => '#000'),   // 付款状态
                            'keyword3' => array('value' => local_date('Y-m-d H:i', time()),'color' => '#000'),  //付款时间
                            'keyword4' => array('value' => C('shop_name'),'color' => '#000'),       // 商户名
                            'keyword5' => array('value' => $order['order_amount'].'元','color' => '#000'),             // 付款金额
                            'remark' => array('value' => '我们会尽快给您安排发货','color' => '#173177')
                        );
                        // $url = __URL__ . 'index.php?c=user&a=order_detail&order_id='.$order_id;
                        $order_url = __HOST__ . U('user/order_detail',array('order_id'=> $new_order_id));
                        $url = str_replace('api/notify/wxpay.php', '', $order_url);
             pushTemplate('OPENTM204987032', $pushData, $url);

         }
            
            $userinfo = model("Users")->getuserinfo($_SESSION['user_id']);

            if($order['parent_id']){
                    /* 默认1 天美国际go，2 拓客*/
                    $parentinfo = model('Users')->get_users($order['parent_id']);
                    $updateData['resource'] = $parentinfo['resource'];
                    model("Users")->update_user_info($updateData);

                 }
            /*以下都是针对经销商行为,购买经销商区域的产品*/
            /*代表是购买入团商品分类下面的商品*/

                if(strpos($order['order_type'],"1")!==false||strpos($order['order_type'],"2")!==false||strpos($order['order_type'],"3")!==false||strpos($order['order_type'],"4")!==false||strpos($order['order_type'],"5")!==false){
                        if(strpos($order['order_type'],"9")!==false){
                           
                            $api_order_type = ltrim($order['order_type'],"9,");
                        
                        }else{
                            $api_order_type  = $order['order_type'];
                          
                        }
                        
                }



 
                if($_POST['goods_rtcat_id']==2){
                    $api_order_type = 2;
                }

                // if($_SESSION['user_rank']){
                //              $api_order_type = 5;
                //   }
                  if($order['order_type']==10){
                    $api_order_type =10;
                  }
                  if($order['order_type']==9){
                    $api_order_type =9;
                  }

                 
                switch ($api_order_type) {
                    case '1':
                     
                    /*入团变成经销商，赠送一级vip(店主)*/
                    /*/
                        /*入团$_SESSION['user_rank']有值说明已经入团了*/
                        if($cart_goods[0]['goods_number']>1){
                             show_message(L('onlybuyone_error'));
                        }
                        
                        /*同时有两笔入团订单的时候一笔已经支付，另外一笔未支付的时候，要把它设置为取消状态*/
                        model("Order")->cancelOrder($_SESSION['user_id'],3,$order ['order_id']);
                        model('Users')->updateAreaTotal($_SESSION['user_id'],$order['goods_amount']);
                        model('Users')->updateVip($_SESSION['user_id'],$cart_goods[0]['goods_vip']);
                        /*赠送店主身份*/
                        model('Users')->updateUserVip($_SESSION['user_id'],1);
                        /*更新这笔订单产生的业绩*/
                                if(model("Users")->findvipmanageaccount($_SESSION['user_id'],$user_info['vip_manage_account'],$updateData['resource']))
                                {
                                        model("Users")->newaddvipamanageaccount($_SESSION['user_id'],$user_info['vip_manage_account'],$updateData['resource']);
                                    }
                                    
                                   
                       
                                    $parent_accountinfo  = model("Users")->getparentuservipaccount($_SESSION['user_id']);
                                    $cart_goods =  model('Order')->order_goods($order['order_id']);
                                    if($order['discount'] =='0.00'){
                                       
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_rtpv']."pv";
                                          
                                    }else{
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_rtpv']."pv".",折扣$".$order['discount'];
                                    }
                                    $number = substr(time(), 2);
                                    if($user_info['user_vip']&&!$user_info['user_rank']){
                                        /*走激活接口api/order/activate*/
                                        $joinvipdata = array(
                                                
                                                "account" =>$user_info['vip_manage_account'],
                                  
                                             
                       
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
                                                
                                                "account" =>$user_info['vip_manage_account'],
                                                "password" =>$userinfo['password'],
                                                "phone" =>$userinfo['mobile_phone'],
                                                "parent_account" =>$_POST['other_invite_code'],//上级VIP用户名
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
                                  
                          model("Users")->update_parent_id($_SESSION['user_id'],$parent_id);
                          model("Users")->updateuser_reposition($_SESSION['user_id'],$order['parent_id']);
                          model("Users")->updateUserVip($order['user_id'],1);
                          $_SESSION['user_vip']=1;
                  
                    if($_POST['other_invite_code']){
                        updateUserOtherInviteCode($_SESSION['user_id'],$_POST['other_invite_code']);
                        /*更新parentid*/
                         $reffer= model('Users')->get_usersbycode($_POST['other_invite_code']);
                    
                         $reffer_id = $reffer['user_id'];
                      
                    }
                    
                    
                    updateUserJoinAddress($_SESSION['user_id'],1,$_POST['join_province_id'],$_POST['join_city_id'],'中国',$province,$city);
                        break;
                    case '2':
                     /*重消*/
                       
                        $account = model("Users")->gettmaccount($_SESSION['user_id']);
                        foreach ($cart_goods as $key => $value) {
                            # code...
                            if($value['rtcat_id']==2){

                                if($order['discount'] =='0.00'){
                                       
                                         $remark =$remark."|||".$value['goods_name']."*".$value['goods_number'].",$" .$value['subtotal'].",".$value['total_rtpv']."pv";
                                          
                                    }else{
                                         $remark = $remark."|||".$value['goods_name']."*".$value['goods_number'].",$" .$value['subtotal'].",".$value['total_rtpv']."pv".",折扣$".$order['discount'];
                                }

                                $order_amount = $order_amount + $value['subtotal'];
                                $order_total_rtpv =  $order_total_rtpv + $value['total_rtpv'];
                            }

                        }
                        
                  
                        $anewOrderdata = array(
                                                
                                                "account" =>$user_info['vip_manage_account'],
                                                "grade" =>0,//目标等级
                                                "order_sn" =>$order['order_sn'],
                                                "order_amount" =>$order_amount,
                                                "order_pv" =>$order_total_rtpv,
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
                        model("Order")->cancelOrder($user_id,3,$order ['order_id']);
                        model('Users')->updateVip($_SESSION['user_id'],$cart_goods[0]['goods_vip']);

                        /*差额升级和原价升级，60天时限做判断*/
                        
                        $account = model("Users")->gettmaccount($_SESSION['user_id']);
                        if($order['discount'] =='0.00'){
                                       
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_rtpv']."pv";
                                          
                                    }else{
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_rtpv']."pv".",折扣$".$order['discount'];
                        }
                            /*差额升级*/
                        $upgradeOrderdata = array(
                                                
                                                "account" =>$user_info['vip_manage_account'],
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
                       $account = model("Users")->gettmaccount($_SESSION['user_id']);
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
                       model("Order")->cancelOrder($user_id,4,$order ['order_id']);
                       model('Users')->updateVip($_SESSION['user_id'],$cart_goods[0]['goods_vip']);
                       $upgradeOrderdata = array(
                                                
                                                 "account" =>$user_info['vip_manage_account'],
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
                           // if($cart_goods[0]['goods_vip']<$_SESSION['user_rank']){

                           //  show_message(L('不能购买比自己'));

                           // }
                         if($cart_goods[0]['goods_number']>1){
                             show_message(L('onlybuyone_error'));
                        }
                         // if($_SESSION['user_rank']>$cart_goods[0]['goods_vip']){
                         //      show_message(L('updategrade_error'));
                         //  }
                         model('Users')->updateAreaTotal($_SESSION['user_id'],$order['goods_amount']);
                        
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


                        $account = model("Users")->gettmaccount($_SESSION['user_id']);
                        $reorderpdata = array(
                                                
                                                 "account" =>$user_info['vip_manage_account'],
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
                   
                     if(!$_SESSION['user_vip']){
                     if(model("Users")->findvipmanageaccount($_SESSION['user_id'],$user_info['vip_manage_account'],$updateData['resource']))
                                {
                                        model("Users")->newaddvipamanageaccount($_SESSION['user_id'],$user_info['vip_manage_account'],$updateData['resource']);
                                    }
                  
                         $number = substr(time(), 2);
                         $cart_goods =  model('Order')->order_goods($order['order_id']);
                                    if($order['discount'] =='0.00'){
                                       
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_pv']."pv";
                                          
                                    }else{
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_pv']."pv".",折扣$".$order['discount'];
                                    }
                                   
                                    $joinvipdata = array(
                                                
                                                 "account" =>$user_info['vip_manage_account'],
                                                "password" =>$userinfo['password'],
                                                "phone" =>$userinfo['mobile_phone'],
                                                "parent_account" =>$_POST['other_invite_code'],//上级VIP用户名
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
                       
                           
                            model("Users")->update_parent_id($_SESSION['user_id'],$parent_id); 
                            
                      
                            model("Users")->updateUserVip($order['user_id'],1);
                            $_SESSION['user_vip']=1;
                       
                        

                        $r = model("Users")->givePayPoints($_SESSION['user_id'],$cart_goods[0]['goods_id']);
                        
                      
                      

                        model('Users')->updateCommTotal($_SESSION['user_id'],$order['order_amount']);


                       
                        }
                        break;
                    case '10':
                    //YT47254029的user_id为896
                      
                       // progressOrder($order);
                        if(!$user_info['user_vip']){
                            //购买列车订单~赠送VIP
                              model("Users")->update_parent_id($_SESSION['user_id'],'896'); 
                           model('Users')->updateUserVip($order['user_id'],1); 
                             $_SESSION['user_vip']=1;

                        }
                         $cart_goods =  model('Order')->order_goods($order['order_id']);
                                    if($order['discount'] =='0.00'){
                                       
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_pv']."pv";
                                          
                                    }else{
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_pv']."pv".",折扣$".$order['discount'];
                                    }
                                    //调用API/joinVIp的规则，加入VIP空点
                        $joinvipdata = array(
                                                
                                                 "account" =>$user_info['vip_manage_account'],
                                                "password" =>$userinfo['password'],
                                                "phone" =>$userinfo['mobile_phone'],
                                                "parent_account" =>$_POST['other_invite_code'],//上级VIP用户名
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
                            //根据train_ticket这张表$new_order_id查询

                                     


                        break;
                    case '0':
                  
                        /*零售区产品*/
                       
                        model('Users')->updateCommTotal($_SESSION['user_id'],$order['order_amount']);
                        
                        

                   
                        # code...
                        break;
                    default:
                        # code...
                        break;
                }

         /*余额支付的时候，如果订单属于入团订单*/
         
            //model('Payment')->upgradeToVip($_SESSION['user_id'],$new_order_id);
        }


      
        /* 处理余额、积分、红包 */
        if ($order ['user_id'] > 0 && $order ['surplus'] > 0) {
         
            /* 计算并发放积分  并发放*/
            $integral = model('ClipsBase')->integral_to_give($order);
         
            //model('ClipsBase')->log_account_change($order ['user_id'], $order ['surplus'] * (- 1), 0, intval($integral['rank_points']), $kd_use * (- 1), sprintf(L('pay_order'), $order ['order_sn']));

            model('ClipsBase')->new_log_account_change($order ['user_id'], $order ['surplus'] * (- 1),sprintf(L('pay_order'), $order ['order_sn']),ACT_OTHER, 1); 
            model('ClipsBase')->new_log_account_change($order ['user_id'], intval($integral['rank_points']),sprintf(L('pay_order'), $order ['order_sn']),ACT_OTHER, 5); 
            if($kd_use>0){
                       model('ClipsBase')->new_log_account_change($order ['user_id'],  $kd_use * (- 1),sprintf(L('pay_order'), $order ['order_sn']),ACT_OTHER, 6); 
            }
     

             model('Flow')->splitorder($new_order_id);
        }
        if ($order ['user_id'] > 0 && $order ['integral'] > 0) {
         
            // model('ClipsBase')->log_account_change($order ['user_id'], 0, 0, 0, $order ['integral'] * (- 1), sprintf(L('pay_order'), $order ['order_sn']));
        }

        if ($order ['bonus_id'] > 0 && $temp_amout > 0) {
            model('Order')->use_bonus($order ['bonus_id'], $new_order_id);
        }
 
        /* 如果使用库存，且下订单时减库存，则减少库存 */
        // if (C('use_storage') == '1' && C('stock_dec_time') == SDT_PLACE) {
        //     model('Order')->change_order_goods_storage($order ['order_id'], true, SDT_PLACE);
        // }

        /* 给商家发邮件 */
        /* 增加是否给客服发送邮件选项 */
        if (C('send_service_email') && C('service_email') != '') {
            $tpl = model('Base')->get_mail_template('remind_of_new_order');
            $this->assign('order', $order);
            $this->assign('goods_list', $cart_goods);
            $this->assign('shop_name', C('shop_name'));
            $this->assign('send_date', date(C('time_format')));
            $content = ECTouch::$view->fetch('str:' . $tpl ['template_content']);
            send_mail(C('shop_name'), C('service_email'), $tpl ['template_subject'], $content, $tpl ['is_html']);
        }

        /* 如果需要，发短信 */
        if (C('sms_order_placed') == '1' && C('sms_shop_mobile') != '') {
            $sms = new EcsSms();
            $msg = $order ['pay_status'] == PS_UNPAYED ? L('order_placed_sms') : L('order_placed_sms') . '[' . L('sms_paid') . ']';
            $sms->send(C('sms_shop_mobile'), sprintf($msg, $order ['consignee'], $order ['mobile']), '', 13, 1);
        }
        //  /* 如果需要，微信通知 by wanglu */
        // if (method_exists('WechatController', 'snsapi_base') && is_wechat_browser()) {
        //     $order_url = __HOST__ . url('user/order_detail', array('order_id' => $order ['order_id']));
        //     $order_url = urlencode(base64_encode($order_url));
        //     send_wechat_message('order_remind', '', $order['order_sn'] . L('order_effective'), $order_url, $order['order_sn']);
        // }
        // 微信通模板消息
        $orderMoneySum =  round($order['order_amount'],2);
        if (class_exists('WechatController') && is_wechat_browser()&&!$balancepayment ) {
            
                $pushData = array(
                'first' => array('value' => '您的订单已提交成功','color' => '#173177'),
                'keyword1' => array('value' => $order['order_sn'],'color' => '#000'), //订单号
                'keyword2' => array('value' => $orderMoneySum.'元','color' => '#000'), //订单金额
                'keyword3' => array('value' => local_date(C('time_format'), $order['add_time']),'color' => '#000'),
                'remark' => array('value' => '感谢您的购买！请尽快完成支付,未支付订单将在一个小时后自动取消','color' => '#173177')
            );
            
         
            $url = __HOST__ . U('user/order_detail', array('order_id' => $new_order_id));
            pushTemplate('OPENTM415293129', $pushData, $url);
        }
        /* 如果订单金额为0 处理虚拟卡 */
        if ($order ['order_amount'] <= 0) {
            $sql = "SELECT goods_id, goods_name, goods_number AS num FROM " . $this->model->pre . "cart WHERE is_real = 0 AND extension_code = 'virtual_card'" . " AND session_id = '" . SESS_ID . "' AND rec_type = '$flow_type'";
            $res = $this->model->query($sql);

            $virtual_goods = array();
            foreach ($res as $row) {
                $virtual_goods ['virtual_card'] [] = array(
                    'goods_id' => $row ['goods_id'],
                    'goods_name' => $row ['goods_name'],
                    'num' => $row ['num']
                );
            }

            if ($virtual_goods and $flow_type != CART_GROUP_BUY_GOODS) {
                /* 虚拟卡发货 */
                if (model('OrderBase')->virtual_goods_ship($virtual_goods, $msg, $order ['order_sn'], true)) {
                    /* 如果没有实体商品，修改发货状态，送积分和红包 */
                    $count = $this->model->table('order_goods')->field('COUNT(*)')->where("order_id = '$order[order_id]' " . " AND is_real = 1")->getOne();
                    if ($count <= 0) {
                        /* 修改订单状态 */
                        model('Users')->update_order($order ['order_id'], array(
                            'shipping_status' => SS_SHIPPED,
                            'shipping_time' => time()
                        ));

                        /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
                        if ($order ['user_id'] > 0) {
                            /* 取得用户信息 */
                            $user = model('Order')->user_info($order ['user_id']);

                            /* 计算并发放积分 */
                            $integral = model('Order')->integral_to_give($order);
                         
                          //  model('ClipsBase')->log_account_change($order ['user_id'], 0, 0, intval($integral ['rank_points']), intval($integral ['custom_points']), sprintf(L('order_gift_integral'), $order ['order_sn']));
                            model('ClipsBase')->new_log_account_change($order ['user_id'], intval($integral ['rank_points']),sprintf(L('order_gift_integral'), $order ['order_sn']),ACT_OTHER, 5);
                            model('ClipsBase')->new_log_account_change($order ['user_id'], intval($integral ['custom_points']),sprintf(L('order_gift_integral'), $order ['order_sn']),ACT_OTHER, 6);
                            /* 发放红包 */
                            model('Order')->send_order_bonus($order ['order_id']);
                        }
                    }
                }
            }
        }

        // 销量
        model('Flow')->add_touch_goods($flow_type, $order ['extension_code']);
        /* 清空购物车 */
        model('Order')->clear_cart($flow_type,$cart_value);
        /* 清除缓存，否则买了商品，但是前台页面读取缓存，商品数量不减少 */
        clear_all_files();

        /* 插入支付日志 */
        $order ['log_id'] = model('ClipsBase')->insert_pay_log($new_order_id, $order ['order_amount'], PAY_ORDER);

        /* 取得支付信息，生成支付代码 */
        if ($order ['order_amount'] > 0) {
            $payment = model('Order')->payment_info($order ['pay_id']);

            if($payment['pay_id']==4){
                //智付通支付
                include_once (ROOT_PATH . 'plugins/payment/' . $payment ['pay_code'] . '.php');
            
                $pay_obj = new $payment ['pay_code'] ();
                $payment ['pay_config']['mpg_using'] = 0 ;
                  $payment ['pay_config']['mpg_merchantid'] = "MS351491967" ;
                  $payment ['pay_config']['mpg_key'] = "PPK8Vhs8vtlJYD8cvIWaGBj9pEuCWvvn" ;
                  $payment ['pay_config']['mpg_iv'] = "Lhr7vgFwnt2rvIhM" ;
                 // $payment ['pay_config']['mpg_merchantid'] = "MS33835384" ;
                 // $payment ['pay_config']['mpg_key'] = "CX9g0cYiLsHXC315nPztZwH0FEsEk2qV" ;
                 // $payment ['pay_config']['mpg_iv'] = "rcLZj2gyKysTXazF" ;
    
                $payment ['pay_config']['pay_button'] = "前往付款" ;
                
                $pay_online = $pay_obj->get_code($order, $payment ['pay_config']);
    
                $order ['pay_desc'] = $payment ['pay_desc'];
                
            }else{
                
            
            include_once (ROOT_PATH . 'plugins/payment/' . $payment ['pay_code'] . '.php');
           
            $pay_obj = new $payment ['pay_code'] ();
       
            $pay_online = $pay_obj->get_code($order, unserialize_config($payment ['pay_config']));
//            array(10) {
//   ["pay_id"]=&gt;
//   string(1) "1"
//   ["pay_code"]=&gt;
//   string(5) "wxpay"
//   ["pay_name"]=&gt;
//   string(12) "微信支付"
//   ["pay_fee"]=&gt;
//   string(1) "0"
//   ["pay_desc"]=&gt;
//   string(87) "微信支付，是基于微信公众号JS_API网页支付提供的支付服务功能。"
//   ["pay_order"]=&gt;
//   string(1) "0"
//   ["pay_config"]=&gt;
//   string(427) "a:4:{i:0;a:3:{s:4:"name";s:11:"wxpay_appid";s:4:"type";s:4:"text";s:5:"value";s:18:"wxc938a73952df8be0";}i:1;a:3:{s:4:"name";s:15:"wxpay_appsecret";s:4:"type";s:4:"text";s:5:"value";s:32:"3f7c78f40e2007d2deecdeeef37a5638";}i:2;a:3:{s:4:"name";s:9:"wxpay_key";s:4:"type";s:4:"text";s:5:"value";s:32:"8698a2dbc71bf1d6ee3429cd561c5a92";}i:3;a:3:{s:4:"name";s:11:"wxpay_mchid";s:4:"type";s:4:"text";s:5:"value";s:10:"1516857501";}}"
//   ["enabled"]=&gt;
//   string(1) "1"
//   ["is_cod"]=&gt;
//   string(1) "0"
//   ["is_online"]=&gt;
//   string(1) "1"
// }
            $order ['pay_desc'] = $payment ['pay_desc'];
            }
           

            $this->assign('pay_online', $pay_online);
        }
        if (!empty($order ['shipping_name'])) {
            $order ['shipping_name'] = trim(stripcslashes($order ['shipping_name']));
        }
        // 如果是银行汇款或货到付款 则显示支付描述
        if ($payment['pay_code'] == 'bank' || $payment['pay_code'] == 'cod'){
            if (empty($order ['pay_name'])) {
                $order ['pay_name'] = trim(stripcslashes($payment ['pay_name']));
            }
            $this->assign('pay_desc',$order['pay_desc']);
        }
        // 货到付款不显示
      
        if ($payment ['pay_code'] != 'balance') {
            /* 生成订单后，修改支付，配送方式 */

            // 支付方式
            $payment_list = model('Order')->available_payment_list(0);
            if (isset($payment_list)) {
                foreach ($payment_list as $key => $payment) {

                    /* 如果有易宝神州行支付 如果订单金额大于300 则不显示 */
                    if ($payment ['pay_code'] == 'yeepayszx' && $total ['amount'] > 300) {
                        unset($payment_list [$key]);
                    }
                    // 过滤掉当前的支付方式
                    if ($payment ['pay_id'] == $order ['pay_id']) {
                        unset($payment_list [$key]);
                    }
                    /* 如果有余额支付 */
                    if ($payment ['pay_code'] == 'balance') {
                        /* 如果未登录，不显示 */
                        if ($_SESSION ['user_id'] == 0) {
                            unset($payment_list [$key]);
                        } else {
                            if ($_SESSION ['flow_order'] ['pay_id'] == $payment ['pay_id']) {
                                $this->assign('disable_surplus', 1);
                            }
                        }
                    }
                    // 如果不是微信浏览器访问并且不是微信会员 则不显示微信支付
                    // if ($payment ['pay_code'] == 'wxpay' && !is_wechat_browser() && empty($_SESSION['openid'])) {
                    //     unset($payment_list [$key]);
                    // }
                    // 兼容过滤ecjia支付方式
                    if (substr($payment['pay_code'], 0 , 4) == 'pay_') {
                        unset($payment_list[$key]);
                    }
                }
            }

            $this->assign('payment_list', $payment_list);
            $this->assign('pay_code', 'no_balance');
        }
        $share_link = __URL__ . "/index.php?m=default&c=goods&a=index&id=".$cart_goods[0][goods_id]."&u=".$_SESSION['user_id'];
        if(!empty($traininfo)){

            $this->assign('train_link',__URL__ . "/index.php?m=default&c=train&a=info&train_id=".$traininfo['train_id']);
            $this->assign('traininfo',$traininfo);
        }
        $order_infos = model('Order')->order_info($new_order_id);
     
        $this->assign('pay_status',$order_infos['pay_status']);
        $this->assign('share_link',$share_link);
       
        $this->assign('share_title',$cart_goods['0']['goods_name']);
        $this->assign('share_description',$cart_goods['0']['goods_brief']);
        $this->assign('share_pic',$cart_goods['0']['goods_thumb']);
     

        /* 订单信息 */
        $this->assign('paysuccess',$paysuccess);
        $this->assign('order', $order);
        $this->assign('order_sale_type', $order_sale_type);
        $this->assign('pre_get_amount',0.66*$order['goods_amount']);
        $this->assign('total', $total);
        $this->assign('user_info',$user_info);
        $this->assign('goods_list', $cart_goods);
        $this->assign('vipname',model('Users')->getuserkvipname($cart_goods[0]['goods_vip']));
        $this->assign('viplevel',$cart_goods[0]['goods_vip']);

        $this->assign('order_submit_back', sprintf(L('order_submit_back'), L('back_home'), L('goto_user_center'))); // 返回提示

        user_uc_call('add_feed', array($order ['order_id'], BUY_GOODS)); // 推送feed到uc
        unset($_SESSION ['flow_consignee']); // 清除session中保存的收货人信息
        unset($_SESSION ['flow_order']);
        unset($_SESSION ['direct_shopping']);

        $this->assign('currency_format', C('currency_format'));
        $this->assign('integral_scale', C('integral_scale'));
        $this->assign('step', ACTION_NAME);
        $guessyourlike = model('Goods')->get_best_goods();
        $this->assign('user_vip',$_SESSION['user_vip']);
        $this->assign('page_title', L('order_submit'));
        $this->assign('headerContent', L("pay_status"));//
        $this->assign('guessyourlike',$guessyourlike);
        $this->display('flow.dwt');
    }


    /**
     * 获取购物车内的相关配件
     */

    public function goods_fittings() {
        if (IS_AJAX) {

            $start = $_POST ['last'];

            $limit = $_POST ['amount'];

            $condition = "session_id = '" . SESS_ID . "' " . "AND rec_type = '" . CART_GENERAL_GOODS . "' " . "AND is_gift = 0 " . "AND extension_code <> 'package_buy' " . "AND parent_id = 0";

            $parent_list = $this->model->table('cart')->field('goods_id')->where($condition)->getCol();

            //根据基本件id获取 购物车中商品配件列表

            $fittings_list = model('Goods')->get_goods_fittings($parent_list);

            if ($fittings_list) {

                foreach ($fittings_list as $key => $fittings) {

                    $this->assign('fittings', $fittings);

                    $sayList[] = array(

                        'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')

                    );

                }

            }

            echo json_encode($sayList);

            exit();

        }



        // 赋值于模板

        $this->assign('page_title', L('goods_fittings'));



        $this->display('goods_fittings.dwt');

    }



    /**

     * 优惠活动（赠品）

     */

    public function label_favourable() {

        // 取得优惠活动

        $favourable_list = model('Flow')->favourable_list_flow($_SESSION ['user_rank']);

        usort($favourable_list, array("FlowModel", "cmp_favourable"));

        $this->assign('favourable_list', $favourable_list);


        
        $this->assign('step', 'label_favourable');

        $this->assign('page_title', L('label_favourable'));

        $this->display('flow.dwt');

    }



    /**

     * 购物车列表 连接到index

     */

    public function cart() {
       

        header("Cache-Control:no-cache,must-revalidate,no-store"); 
        header("Pragma:no-cache");
        header("Expires:-1");
        $this->index();

    }
    public function mcart() {

        ecs_header("Location: " . url('flow/checkout') . "\n");

    }
    public function add_to_quick_cart()
    {
        echo 9991;exit;
    }

    /**

     * 立即购买

     */

    public function add_to_cart() {

        //对goods处理
          if(!$_SESSION['user_id']){
            $result['error'] = 9;
            $result['message'] = "您还未登录";
            die(json_encode($result));exit;
        }
       
        if($_POST['goods_area']==3){
            model('Order')->clear_cart(CART_GENERAL_GOODS,'');
        }
        $_POST ['goods'] = strip_tags(urldecode($_POST ['goods']));

        $_POST ['goods'] = json_str_iconv($_POST ['goods']);

        if (!empty($_REQUEST ['goods_id']) && empty($_POST ['goods'])) {

            if (!is_numeric($_REQUEST ['goods_id']) || intval($_REQUEST ['goods_id']) <= 0) {

                ecs_header("Location:./\n");

            }

            $goods_id = intval($_REQUEST ['goods_id']);

            exit();

        }

        // 初始化返回数组

        $result = array(

            'error' => 0,

            'message' => '',

            'content' => '',

            'goods_id' => '',

            'product_spec' => ''

        );
       

        if (empty($_POST ['goods'])) {

            $result ['error'] = 1;

            die(json_encode($result));

        }

        $json = new EcsJson;

        $goods = $json->decode($_POST ['goods']);

        $result['goods_id'] = $goods->goods_id;

        $result['product_spec'] = $goods->spec;

        // 检查：如果商品有规格，而post的数据没有规格，把商品的规格属性通过JSON传到前台
        
        if (empty($goods->spec) and empty($goods->quick)) {

            $sql = "select user_price from ".$this->model->pre."member_price where goods_id = ".$goods->goods_id ." and user_rank = ".$_SESSION['user_rank'];

            $res = $this->model->query($sql);

            $mp_price = $res[0][user_price] ;

            if(empty($mp_price)){

                    $sql = "SELECT g.goods_name as goods_name,g.rtcat_id as rtcat_id,g.lscat_id as lscat_id,g.goods_thumb as goods_thumb,g.goods_area as goods_area, g.shop_price*$_SESSION[discount]  as shop_price,g.  goods_number as goods_number FROM ".

                     $this->model->pre."goods as g  ".

                     " where g.goods_id = ".$goods->goods_id ;

            } else{

                 $sql = "SELECT g.goods_name as goods_name,g.rtcat_id as rtcat_id,g.lscat_id as lscat_id,g.goods_thumb as goods_thumb,g.goods_area as goods_area,ifnull(".$mp_price.",g.shop_price*$_SESSION[discount]) as shop_price,g.  goods_number as goods_number FROM ".

                     $this->model->pre."goods as g  ".

                     " where g.goods_id = ".$goods->goods_id ;

            }

             //var_dump($sql) ;



            $res = $this->model->query($sql);

            $goods_name = $res[0]['goods_name'];

            $shop_price = $res[0]['shop_price'];

            $goods_number = $res[0]['goods_number'];

            $goods_thumb = $res[0]['goods_thumb'];



            $sql = "SELECT user_price FROM ".$this->model->pre.

                   "member_price WHERE goods_id = ".$goods->goods_id.

                   " AND user_rank = ".$_SESSION['user_rank'];

            $res = $this->model->query($sql);

            $mp = $res[0]["user_price"];

            $sql = "SELECT a.attr_id, a.attr_name, a.attr_type, " . "g.goods_attr_id, g.attr_value, g.attr_price " . 'FROM ' . $this->model->pre . 'goods_attr AS g ' . 'LEFT JOIN ' . $this->model->pre . 'attribute AS a ON a.attr_id = g.attr_id ' . "WHERE a.attr_type != 0 AND g.goods_id = '" . $goods->goods_id . "' " . 'ORDER BY a.sort_order, g.attr_price, g.goods_attr_id';

            $res = $this->model->query($sql);

            if (!empty($res)) {

                $spe_arr = array();

                foreach ($res as $row) {

                    if(!$mp){

                        $row['attr_price'] = $row['attr_price'] * $_SESSION['discount'];

                    }

                    $spe_arr [$row ['attr_id']] ['attr_type'] = $row ['attr_type'];

                    $spe_arr [$row ['attr_id']] ['name'] = $row ['attr_name'];

                    $spe_arr [$row ['attr_id']] ['attr_id'] = $row ['attr_id'];

                    $spe_arr [$row ['attr_id']] ['values'] [] = array(

                        'label' => $row ['attr_value'],

                        'price' => $row ['attr_price'],

                        'format_price' => price_format($row ['attr_price'], false),

                        'id' => $row ['goods_attr_id']

                    );

                }

                $i = 0;

                $spe_array = array();

                foreach ($spe_arr as $row) {

                    $spe_array [] = $row;

                }

                $result ['error'] = ERR_NEED_SELECT_ATTR;

                $result ['goods_id'] = $goods->goods_id;

                $result ['parent'] = $goods->parent;

                $result ['message'] = $spe_array;

                $result ['goods_name'] = $goods_name;

                $result ['goods_thumb'] = $goods_thumb;

                $result ['goods_number'] = $goods_number;

                $result ['shop_price'] = $shop_price;
           
                die(json_encode($result));

            }

        }
       
        // 更新：如果是一步购物，先清空购物车
        $_SESSION['one_step_buy_group'] = 0;
            // var_dump(C('one_step_buy'));
            // var_dump($_POST['goods_area']);exit;

       
        
      

        // 查询：系统启用了库存，检查输入的商品数量是否有效

        // 查询

        $arrGoods = $this->model->table('goods')->field('goods_name,shop_price,goods_number,extension_code,rtcat_id,lscat_id')->where('goods_id =' . $goods->goods_id)->find();

         if ($_POST['goods_area']==1||$_POST['goods_area']==2) {
             if(strpos($arrGoods['rtcat_id'],"2")===false){
            
                 model('Order')->clear_cart(CART_GENERAL_GOODS,'');
             }
           

        }
        $goodsnmber = model('Users')->get_goods_number($goods->goods_id);

        $goodsnmber+=$goods->number;

        if (intval(C('use_storage')) > 0 && $arrGoods ['extension_code'] != 'package_buy') {
            //无库存
            if ($arrGoods ['goods_number'] ==0) {

                $result['error'] = 1;
                $result['message'] = sprintf(L('nostock'), $arrGoods ['goods_name'], $arrGoods ['goods_number'], $arrGoods ['goods_number']);

                if (C('use_how_oos') == 1){

                    $result['message'] =L('oos_tips');

                }

                die(json_encode($result));
            }

            if ($arrGoods ['goods_number'] < $goodsnmber) {

                $result['error'] = 1;

                $result['message'] = sprintf(L('stock_insufficiency'), $arrGoods ['goods_name'], $arrGoods ['goods_number'], $arrGoods ['goods_number']);

                if (C('use_how_oos') == 1){

                    $result['message'] =L('oos_tips');

                }

                die(json_encode($result));

            }

        }

        // 检查：商品数量是否合法

        if (!is_numeric($goods->number) || intval($goods->number) <= 0) {

            $result ['error'] = 1;

            $result ['message'] = L('invalid_number');

        } else {

            // 更新：添加到购物车
            
            if($goods->order_type==10){
                $yutui = 1;
            }else{
                $yutui = 0;
            }
            
            if($goods->order_type==10){
                $goods->goods_area = 3;
            }
            if (model('Order')->addto_cart($goods->goods_id, $goods->number, $goods->spec, $goods->parent,$goods->goods_area,$yutui)) {

                if (C('cart_confirm') > 2) {
                    $result ['message'] = '';
                } else {

                    $result ['message'] = C('cart_confirm') == 1 ? L('addto_cart_success_1') : L('addto_cart_success_2');

                }

                $result ['content'] = insert_cart_info();

               

            } else {

                $result ['message'] = ECTouch::err()->last_message();

                $result ['error'] = ECTouch::err()->error_no;

                $result ['goods_id'] = stripslashes($goods->goods_id);

                if (is_array($goods->spec)) {

                    $result ['product_spec'] = implode(',', $goods->spec);

                } else {

                    $result ['product_spec'] = $goods->spec;

                }

            }

        }

        $cart_confirm = C('cart_confirm');

        $result ['confirm_type'] = !empty($cart_confirm) ? C('cart_confirm') : 2;

        // 返回购物车商品总数量
        $rtcat_id = explode(",", $arrGoods['rtcat_id']);
        if((in_array("2", $rtcat_id)&&$goods->goods_area)||!$goods->goods_area){
            /*重消且是入团*/
            $result ['one_step_buy'] = 0;
        }else{
            $result ['one_step_buy'] = 1;
        }
        
        $result ['cart_number'] = insert_cart_info_number();

        die(json_encode($result));

    }

    public function  invitecodevalid(){
        $invitecode = $_POST['invitecode'];
        $r2 = $this->model->table('users')->field('invite_code')->where('invite_code="'.$invitecode.'"')->find();
    
        $invite_code = $r2['invite_code'];
        if($invite_code){
             $result['status'] = 1; 

        }else{
             
             $result['status'] =0; 
        }
       
        echo json_encode($result);
    }

    /**

     * 点击刷新购物车

     */

    public function ajax_update_cart() {
        //格式化返回数组
        $result = array(
            'error' => 0,
            'message' => ''
        );

        // 是否有接收值
        if (isset($_POST ['rec_id']) && isset($_POST ['goods_number'])) {
            $key = $_POST ['rec_id'];

            $val = $_POST ['goods_number'];

            $val = intval(make_semiangle($val));

            if ($val <= 0 && !is_numeric($key)) {

                $result ['error'] = 99;

                $result ['message'] = '';

                die(json_encode($result));

            }

            // 查询：

            $condition = " rec_id='$key' AND session_id='" . SESS_ID . "'";

            $goods = $this->model->table('cart')->field('goods_id,goods_attr_id,product_id,extension_code')->where($condition)->find();



            $sql = "SELECT g.goods_name,g.goods_number " . "FROM " . $this->model->pre . "goods AS g, " . $this->model->pre . "cart AS c " . "WHERE g.goods_id =c.goods_id AND c.rec_id = '$key'";

            $res = $this->model->query($sql);

            $row = $res[0];

            // 查询：系统启用了库存，检查输入的商品数量是否有效

            if (intval(C('use_storage')) > 0 && $goods ['extension_code'] != 'package_buy') {

                //无库存
                if ($row ['goods_number'] ==0) {

                    $result ['error'] = 1;

                    $result ['message'] = sprintf(L('nostock'), $row ['goods_name'], $row ['goods_number'], $row ['goods_number']);

                    $result ['err_max_number'] = $row ['goods_number'];

                    die(json_encode($result));

                }


                if ($row ['goods_number'] < $val) {

                    $result ['error'] = 1;

                    $result ['message'] = sprintf(L('stock_insufficiency'), $row ['goods_name'], $row ['goods_number'], $row ['goods_number']);

                    $result ['err_max_number'] = $row ['goods_number'];

                    die(json_encode($result));

                }

                /* 是货品 */

                $goods ['product_id'] = trim($goods ['product_id']);

                if (!empty($goods ['product_id'])) {

                    $condition = " goods_id = '" . $goods ['goods_id'] . "' AND product_id = '" . $goods ['product_id'] . "'";

                    $product_number = $this->model->table('products')->field('product_number')->where($condition)->getOne();

                    if ($product_number < $val) {

                        $result ['error'] = 2;

                        $result ['message'] = sprintf(L('stock_insufficiency'), $row ['goods_name'], $product_number, $product_number);

                        die(json_encode($result));

                    }

                }

            } elseif (intval(C('use_storage')) > 0 && $goods ['extension_code'] == 'package_buy') {

                if (model('Order')->judge_package_stock($goods ['goods_id'], $val)) {

                    $result ['error'] = 3;

                    $result ['message'] = L('package_stock_insufficiency');

                    die(json_encode($result));

                }

            }

            /* 查询：检查该项是否为基本件 以及是否存在配件 */

            /* 此处配件是指添加商品时附加的并且是设置了优惠价格的配件 此类配件都有parent_idgoods_number为1 */

            $sql = "SELECT b.goods_number,b.rec_id

            FROM " . $this->model->pre . "cart a, " . $this->model->pre . "cart b

                WHERE a.rec_id = '$key'

                AND a.session_id = '" . SESS_ID . "'

            AND a.extension_code <>'package_buy'

            AND b.parent_id = a.goods_id

            AND b.session_id = '" . SESS_ID . "'";



            $offers_accessories_res = $this->model->query($sql);



            // 订货数量大于0

            if ($val > 0) {

                /* 判断是否为超出数量的优惠价格的配件 删除 */

                $row_num = 1;

                foreach ($offers_accessories_res as $offers_accessories_row) {

                    if ($row_num > $val) {

                        $sql = "DELETE FROM" . $this->model->pre . "cart WHERE session_id = '" . SESS_ID . "' " . " AND rec_id ='" . $offers_accessories_row ['rec_id'] . "' LIMIT 1";

                        $this->model->query($sql);

                    }



                    $row_num++;

                }



                /* 处理超值礼包 */

                if ($goods ['extension_code'] == 'package_buy') {

                    // 更新购物车中的商品数量

                    $sql = "UPDATE " . $this->model->pre . "cart SET goods_number= '$val' WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";

                } /* 处理普通商品或非优惠的配件 */ else {

                    $attr_id = empty($goods ['goods_attr_id']) ? array() : explode(',', $goods ['goods_attr_id']);

                    $goods_price = model('GoodsBase')->get_final_price($goods ['goods_id'], $val, true, $attr_id);



                    // 更新购物车中的商品数量

                    $sql = "UPDATE " . $this->model->pre . "cart SET goods_number= '$val', goods_price = '$goods_price' WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";

                }

            }  // 订货数量等于0

            else {

                /* 如果是基本件并且有优惠价格的配件则删除优惠价格的配件 */

                foreach ($offers_accessories_res as $offers_accessories_row) {

                    $sql = "DELETE FROM " . $this->model->pre . "cart WHERE session_id= '" . SESS_ID . "' " . "AND rec_id ='" . $offers_accessories_row ['rec_id'] . "' LIMIT 1";

                    $this->model->query($sql);

                }



                $sql = "DELETE FROM " . $this->model->pre . "cart WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";

            }



            $this->model->query($sql);

            /* 删除所有赠品 */

            $sql = "DELETE FROM " . $this->model->pre . "cart WHERE session_id = '" . SESS_ID . "' AND is_gift <> 0";

            $this->model->query($sql);



            $result ['rec_id'] = $key;

            $result ['goods_number'] = $val;

            $result ['goods_subtotal'] = '';

            $result ['total_desc'] = '';

            $result ['cart_info'] = insert_cart_info();

            /* 计算合计 */

            $cart_goods = model('Order')->get_cart_goods();

            foreach ($cart_goods ['goods_list'] as $goods) {

                if ($goods ['rec_id'] == $key) {

                    $result ['goods_subtotal'] = $goods ['subtotal'];

                    break;

                }

            }

            $market_price_desc = sprintf(L('than_market_price'), $cart_goods ['total'] ['market_price'], $cart_goods ['total'] ['saving'], $cart_goods ['total'] ['save_rate']);

            /* 计算折扣 */

            $discount = model('Order')->compute_discount();

            $favour_name = empty($discount ['name']) ? '' : join(',', $discount ['name']);

            $your_discount = sprintf('', $favour_name, price_format($discount ['discount']));

            $result ['total_desc'] = $cart_goods ['total'] ['goods_price'];

            $result ['total_number'] = $cart_goods ['total'] ['total_number'];

            $result['market_total'] =  $cart_goods['total']['market_price'];//市场价格

            die(json_encode($result));

        } else {

            $result ['error'] = 100;

            $result ['message'] = '';

            die(json_encode($result));

        }

    }



    /**

     * 删除购物车中的商品

     */

    public function drop_goods() {

        $rec_id = intval($_GET ['id']);

        //删除购物车中的商品
   
        model('Flow')->flow_drop_cart_goods($rec_id);

        ecs_header("Location: " . url('flow/index') . "\n");

    }







    /**

     * 登录信息

     */

    public function login() {

        //用户登录注册

        if ($_SERVER ['REQUEST_METHOD'] == 'GET') {

            $this->assign('anonymous_buy', C('anonymous_buy'));



            /* 检查是否有赠品，如果有提示登录后重新选择赠品 */

            $count = $this->model->table('cart')->field('count(*)')->where("session_id = '" . SESS_ID . "' AND is_gift > 0")->getOne();

            if ($count > 0) {

                $this->assign('need_rechoose_gift', 1);

            }



            /* 检查是否需要注册码 */

            $captcha = intval(C('captcha'));

            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION ['login_fail'] > 2)) && gd_version() > 0) {

                $this->assign('enabled_login_captcha', 1);

                $this->assign('rand', mt_rand());

            }

            if ($captcha & CAPTCHA_REGISTER) {

                $this->assign('enabled_register_captcha', 1);

                $this->assign('rand', mt_rand());

            }

        } else {

            $act = in($_POST ['act']);

            $username = in($_POST ['username']);

            $password = in($_POST ['password']);

            $remember = in($_POST ['remember']);

            $email = in($_POST ['email']);

            $post_captcha = in($_POST ['captcha']);

            if ($act == 'signin') {

                $captcha = intval(C('captcha'));

                if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION ['login_fail'] > 2)) && gd_version() > 0) {

                    if (empty($post_captcha)) {

                        show_message(L('invalid_captcha'));

                    }



                    if ($_SESSION ['ectouch_verify'] !== $_POST ['captcha']) {

                        show_message(L('invalid_captcha'));

                    }

                }

                if (self::$user->login($username, $password, isset($remember))) {

                    model('Users')->update_user_info(); // 更新用户信息

                    model('Users')->recalculate_price(); // 重新计算购物车中的商品价格



                    /* 检查购物车中是否有商品 没有商品则跳转到首页 */

                    $count = $this->model->table('cart')->field('count(*)')->where("session_id = '" . SESS_ID . "'")->getOne();

                    if ($count > 0) {

                        ecs_header("Location: " . url('flow/checkout') . "\n");

                    } else {

                        ecs_header("Location:index.php\n");

                    }

                } else {

                    $_SESSION ['login_fail']++;

                    show_message(L('signin_failed'), '', url('flow/index', array('step' => 'register')));

                }

            } elseif ($act == 'signup') {

                if ((intval(C('captcha')) & CAPTCHA_REGISTER) && gd_version() > 0) {

                    if (empty($post_captcha)) {

                        show_message(L('invalid_captcha'));

                    }

                    if ($_SESSION ['ectouch_verify'] !== $_POST ['captcha']) {

                        show_message(L('invalid_captcha'));

                    }

                }



                if (model('Users')->register(trim($username), trim($password), trim($email))) {

                    /* 用户注册成功 */

                    ecs_header("Location: " . url('flow/consignee') . "\n");

                } else {

                    ECTouch::err()->show();

                }

            } else {

                // TODO: 非法访问的处理

            }

        }

        $this->assign('currency_format', C('currency_format'));

        $this->assign('integral_scale', C('integral_scale'));

        $this->assign('step', ACTION_NAME);

        $this->assign('action', 'login');

        /* 验证码相关设置 */

        if ((intval(C('captcha')) & CAPTCHA_REGISTER) && gd_version() > 0) {

            $this->assign('enabled_captcha', 1);

            $this->assign('rand', mt_rand());

        }
         $share_link = "http://like.yoyo1.com.cn/index.php?c=topic&topic_id=20";
         $this->assign('share_link',$share_link);
        $this->display('flow.dwt');


    }



    /**

     * 收货信息

     */

    public function consignee() {

        if ($_SERVER ['REQUEST_METHOD'] == 'GET') {

            /* 取得购物类型 */

            $flow_type = isset($_SESSION ['flow_type']) ? intval($_SESSION ['flow_type']) : CART_GENERAL_GOODS;

            //收货人信息填写界面

            if (isset($_REQUEST ['direct_shopping'])) {

                $_SESSION ['direct_shopping'] = 1;

            }



            /* 取得国家列表、商店所在国家、商店所在国家的省列表 */

            $this->assign('country_list', model('RegionBase')->get_regions());

            $this->assign('shop_country', C('shop_country'));

            $this->assign('shop_province_list', model('RegionBase')->get_regions(1, C('shop_country')));



            /* 获得用户所有的收货人信息 */

            if ($_SESSION ['user_id'] > 0) {

                $addressId = I('get.id');

                if ($addressId > 0) {

                    $consignee_list[] = model('Users')->get_consignee_list($_SESSION ['user_id'], $addressId);

                } else {

                    if(!empty($_SESSION['consignee'])){

                        $consignee = $_SESSION['consignee'];

                        $consignee_list [] = array(

                        'country' => C('shop_country'),

                        'province' => $consignee['province'],

                        'city' => $consignee['city'],

                        'district' => $consignee['district'],



                        );

                    }else{

                        $consignee_list [] = array(

                        'country' => C('shop_country'),

                        );

                    }

                }

            } else {

                if (isset($_SESSION ['flow_consignee'])) {

                    $consignee_list = array(

                        $_SESSION ['flow_consignee']

                    );

                } else {

                    $consignee_list [] = array(

                        'country' => C('shop_country')

                    );

                }

            }

            $this->assign('name_of_region', array(

                C('name_of_region_1'),

                C('name_of_region_2'),

                C('name_of_region_3'),

                C('name_of_region_4')

            ));

            $this->assign('consignee_list', $consignee_list);



            /* 取得每个收货地址的省市区列表 */

            $city_list = array();

            $district_list = array();

            foreach ($consignee_list as $region_id => $consignee) {

                $consignee ['country'] = isset($consignee ['country']) ? intval($consignee ['country']) : 1;

                $consignee ['province'] = isset($consignee ['province']) ? intval($consignee ['province']) : 0;

                $consignee ['city'] = isset($consignee ['city']) ? intval($consignee ['city']) : 0;



                $city_list [$region_id] = model('RegionBase')->get_regions(2, $consignee ['province']);

                $district_list [$region_id] = model('RegionBase')->get_regions(3, $consignee ['city']);

            }

            $this->assign('province_list', model('RegionBase')->get_regions(1, $consignee ['country']));


            $this->assign('city_list', $city_list);

            $this->assign('district_list', $district_list);



            /* 返回收货人页面代码 */

            $this->assign('real_goods_count', model('Order')->exist_real_goods(0, $flow_type) ? 1 : 0 );

        } else {

            /*  保存收货人信息      */

            $consignee = array(

                'address_id' => empty($_POST ['address_id']) ? 0 : intval($_POST ['address_id']),

                'consignee' => empty($_POST ['consignee']) ? '' : I('post.consignee'),

                'country' => empty($_POST ['country']) ? '' : intval($_POST ['country']),

                'province' => empty($_POST ['province']) ? '' : intval($_POST ['province']),

                'city' => empty($_POST ['city']) ? '' : intval($_POST ['city']),

                'district' => empty($_POST ['district']) ? '' : intval($_POST ['district']),

                'address' => empty($_POST ['address']) ? '' : I('post.address'),

                'mobile' => empty($_POST ['mobile']) ? '' : make_semiangle(I('post.mobile'))

            );

           /*array(8) {
  ["address_id"]=>
  int(177)
  ["consignee"]=>
  string(9) "测试智"
  ["country"]=>
  int(1)
  ["province"]=>
  int(710000)
  ["city"]=>
  int(710100)
  ["district"]=>
  int(710101)
  ["address"]=>
  string(12) "测试地址"
  ["mobile"]=>
  string(11) "18965159813"
}*/ 


            if ($_SESSION ['user_id'] > 0) {

                /* 如果用户已经登录，则保存收货人信息 */

                $consignee ['user_id'] = $_SESSION ['user_id'];

                model('Users')->save_consignee($consignee, true);

            }



            /* 保存到session */

            $_SESSION ['flow_consignee'] = stripslashes_deep($consignee);
          
            if($_POST['one_step_buy']){
                if($_POST['order_type']){
                  ecs_header("Location: " . url('flow/checkout',array('one_step_buy'=>'1','order_type'=>$_POST['order_type'])) . "\n");  
              }else{
                ecs_header("Location: " . url('flow/checkout',array('one_step_buy'=>'1','order_type'=>$_POST['order_type'])) . "\n");
              }
                

            }else{
               ecs_header("Location: " . url('flow/checkout') . "\n"); 
            }
            

        }

        $this->assign('order_type',$_GET['order_type']);
        $this->assign('one_step_buy',$_GET['one_step_buy']);
        $this->assign('currency_format', C('currency_format'));

        $this->assign('integral_scale', C('integral_scale'));

        $this->assign('step', ACTION_NAME);

        $this->assign('headerContent', L('consignee_info'));

        $this->display('flow.dwt');

    }

    public function changeprovince()
    {
       
        $result  = model('RegionBase')->get_regions(2, $_POST['province']);
        echo json_encode($result);
    }
    public function updateuservipprovince()
    {
        $r1 = $this->model->table('region')->field('region_name')->where('region_id='.$_POST[province])->find();
  
        $province = $r1['region_name'];
        $r2 = $this->model->table('region')->field('region_name')->where('region_id='.$_POST[city])->find();
   
        $city = $r2['region_name'];
        $data = "province='$province', city='$city'";
        
        $this->model->table('users')->data($data)->where('user_id = ' . $_SESSION['user_id'])->update();
         ecs_header("Location: " . url('flow/checkout') . "\n");
    }

    /**

     *  把优惠活动加入购物车

     */

    public function add_favourable() {

        /* 取得优惠活动信息 */

        $act_id = intval($_POST ['act_id']);

        $favourable = model('GoodsBase')->favourable_info($act_id);

        if (empty($favourable)) {

            show_message(L('favourable_not_exist'));

        }



        /* 判断用户能否享受该优惠 */

        if (!model('Flow')->favourable_available($favourable)) {

            show_message(L('favourable_not_available'));

        }



        /* 检查购物车中是否已有该优惠 */

        $cart_favourable = model('Flow')->cart_favourable();

        if (model('Flow')->favourable_used($favourable, $cart_favourable)) {

            show_message(L('favourable_used'));

        }



        /* 赠品（特惠品）优惠 */

        if ($favourable ['act_type'] == FAT_GOODS) {

            /* 检查是否选择了赠品 */

            if (empty($_POST ['gift'])) {

                show_message(L('pls_select_gift'));

            }



            /* 检查是否已在购物车 */

            $condition = " session_id = '" . SESS_ID . "'" . " AND rec_type = '" . CART_GENERAL_GOODS . "'" . " AND is_gift = '$act_id'" . " AND goods_id " . db_create_in($_POST ['gift']);

            $gift_name = $this->model->table('cart')->field('goods_name')->where($condition)->getCol();

            if (!empty($gift_name)) {

                show_message(sprintf(L('gift_in_cart'), join(',', $gift_name)));

            }



            /* 检查数量是否超过上限 */

            $count = isset($cart_favourable [$act_id]) ? $cart_favourable [$act_id] : 0;

            if ($favourable ['act_type_ext'] > 0 && $count + count($_POST ['gift']) > $favourable ['act_type_ext']) {

                show_message(L('gift_count_exceed'));

            }



            /* 添加赠品到购物车 */

            foreach ($favourable ['gift'] as $gift) {

                if (in_array($gift ['id'], $_POST ['gift'])) {

                    model('Flow')->add_gift_to_cart($act_id, $gift ['id'], $gift ['price']);

                }

            }

        } elseif ($favourable ['act_type'] == FAT_DISCOUNT) {

            model('Flow')->add_favourable_to_cart($act_id, $favourable ['act_name'], model('Flow')->cart_favourable_amount($favourable) * (100 - $favourable ['act_type_ext']) / 100);

        } elseif ($favourable ['act_type'] == FAT_PRICE) {

            model('Flow')->add_favourable_to_cart($act_id, $favourable ['act_name'], $favourable ['act_type_ext']);

        }



        /* 刷新购物车 */

        ecs_header("Location: " . url('flow/index') . "\n");

    }



    /**

     * 改变配送方式

     */

    public function select_shipping() {

        // 格式化返回数组

        $result = array(
            'error' => '',
            'content' => '',
            'need_insure' => 0
        );

        /* 取得购物类型 */

        $flow_type = isset($_SESSION ['flow_type']) ? intval($_SESSION ['flow_type']) : CART_GENERAL_GOODS;
        /* 获得收货人信息 */
        $consignee = model('Order')->get_consignee($_SESSION ['user_id']);
        /* 对商品信息赋值 */
        $cart_goods = model('Order')->cart_goods($flow_type); // 取得商品列表，计算合计
        if (empty($cart_goods) || !model('Order')->check_consignee_info($consignee, $flow_type)) {
            $result ['error'] = L('no_goods_in_cart')."ship";
        } else {
            /* 取得购物流程设置 */
            $this->assign('config', C('CFG'));
            /* 取得订单信息 */
            $order = model('Order')->flow_order_info();
            $order ['shipping_id'] = intval($_REQUEST ['shipping']);
            $regions = array(
                $consignee ['country'],
                $consignee ['province'],
                $consignee ['city'],
                $consignee ['district']

            );
            $shipping_info = model('Shipping')->shipping_area_info($order ['shipping_id'], $regions);
            /* 计算订单的费用 */
            $total = model('Users')->order_fee($order, $cart_goods, $consignee);
            $this->assign('total', $total);

            /* 取得可以得到的积分和红包 */
            $this->assign('total_integral', model('Order')->cart_amount(false, $flow_type) - $total ['bonus'] - $total ['integral_money']);
            $this->assign('total_bonus', price_format(model('Order')->get_total_bonus(), false));

            /* 团购标志 */
            if ($flow_type == CART_GROUP_BUY_GOODS) {
                $this->assign('is_group_buy', 1);
            }

            $result ['cod_fee'] = $shipping_info ['pay_fee'];
            if (strpos($result ['cod_fee'], '%') === false) {
                $result ['cod_fee'] = price_format($result ['cod_fee'], false);
            }

            $result ['need_insure'] = ($shipping_info ['insure'] > 0 && !empty($order ['need_insure'])) ? 1 : 0;
            $result ['content'] = ECTouch::$view->fetch('library/order_total.lbi');

        }
        echo json_encode($result);
    }



    function select_inv(){

        /* 如果能开发票，取得发票内容列表 */

        $can_invoice = C('can_invoice');

        $invoice_content = C('invoice_content');

        $order = model('Order')->flow_order_info();

        if ((!isset($can_invoice) || $can_invoice == '1') && isset($invoice_content) && trim($invoice_content) != '' && $flow_type != CART_EXCHANGE_GOODS) {

            $inv_content_list = explode("\n", str_replace("\r", '', C('invoice_content')));

            $this->assign('inv_content_list', $inv_content_list);

            $inv_type_list = array();

            $invoice_type = C('invoice_type');

            foreach ($invoice_type['type'] as $key => $type) {

                if (!empty($type)) {

                    $inv_type_list [$key]['content'] = $type . ' [' . floatval($invoice_type['rate'] [$key]) . '%]';

                    $inv_type_list [$key]['name'] = $type ;

                }

            }

            $this->assign('inv_type_list', $inv_type_list);

            

        }

        $this->assign('inv_payee', $_SESSION['inv_payee']);

        $this->assign('inv_type', $_SESSION['inv_type']);

        $this->assign('inv_text_id', $_SESSION['inv_text_id']);

        $this->assign('inv_content', $_SESSION['inv_content']);

        $this->assign('order', $order);

        $this->display('flow_select_inv.dwt');

    }







    /**
     * 改变支付方式
     */
    public function select_payment() {
        $json = new EcsJson;

        $result = array('error' => '', 'content' => '', 'need_insure' => 0, 'payment' => 1);
        /* 取得购物类型 */
        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;
        /* 获得收货人信息 */
        $consignee = model('Order')->get_consignee($_SESSION['user_id']);
        /* 对商品信息赋值 */
   
     
  
        if($_REQUEST['goods_area']){
         
           $cart_goods = model('Order')->cart_goods($flow_type,'',$_REQUEST['goods_area']);  

       }else{
          
           $cart_goods = model('Order')->new_cart_goods($flow_type,''); 
       }
      
        // 取得商品列表，计算合计
        if (empty($cart_goods) || !model('Order')->check_consignee_info($consignee, $flow_type)) {
            $result['error'] = L('no_goods_in_cart');
        } else {
            /* 取得购物流程设置 */
            $this->assign('config', C('CFG'));
            /* 取得订单信息 */
            $order = model('Order')->flow_order_info();
            $order['pay_id'] = intval($_REQUEST['payment']);
            $payment_info = model('Order')->payment_info($order['pay_id']);
            $result['pay_code'] = $payment_info['pay_code'];
            /* 保存 session */
            $_SESSION['flow_order'] = $order;
            /* 计算订单的费用 */
            $total = model('Users')->order_fee($order, $cart_goods, $consignee);
            $this->assign('total', $total);

            /* 取得可以得到的积分和红包 */
            $this->assign('total_integral', model('Order')->cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
            $this->assign('total_bonus', price_format(model('Order')->get_total_bonus(), false));
            /* 团购标志 */
            if ($flow_type == CART_GROUP_BUY_GOODS) {
                $this->assign('is_group_buy', 1);
            }
          $result['content'] = ECTouch::$view->fetch('library/order_total.lbi');
        }
        echo $json->encode($result);
        exit;
    }
    /**
     *  订单提交后修改付款方式
     */
    public function get_total() {

        /* 检查支付方式 */

        $pay_id = I('post.payment_id');

        $payment_info = model('Order')->payment_info($pay_id);



        /* 检查订单号 */

        $order_sn = I('post.order_sn');

        $order = model('Order')->order_info(0, $order_sn);

        $order_id = $order['order_id'];

        $order_amount = $order ['order_amount'] - $order ['pay_fee'];

        $pay_fee = pay_fee($pay_id, $order_amount);

        $order_amount += $pay_fee;

        $data = "pay_id='$pay_id', pay_name='$payment_info[pay_name]', pay_fee='$pay_fee', order_amount='$order_amount'";

        $this->model->table('order_info')->data($data)->where('order_id = ' . $order_id)->update();

        $order = model('Order')->order_info($order_id);

        /* 插入支付日志 */

        $order ['log_id'] = model('ClipsBase')->insert_pay_log($order_id, $order ['order_amount'], PAY_ORDER);



        die($order['goods_amount']);

    }



    public function select_pack() {

        $result = array('error' => '', 'content' => '', 'need_insure' => 0);

        /* 取得购物类型 */

        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;



        /* 获得收货人信息 */

        $consignee = model('Order')->get_consignee($_SESSION['user_id']);



        /* 对商品信息赋值 */

        $cart_goods = model('Order')->cart_goods($flow_type); // 取得商品列表，计算合计



        if (empty($cart_goods) || !model('Order')->check_consignee_info($consignee, $flow_type)) {

            $result['error'] = L('no_goods_in_cart');

        } else {

            /* 取得购物流程设置 */

            $this->assign('config', C('CFG'));



            /* 取得订单信息 */

            $order = model('Order')->flow_order_info();



            $order['pack_id'] = I('request.pack', 0, 'intval');



            /* 保存 session */

            $_SESSION['flow_order'] = $order;



            /* 计算订单的费用 */

            $total = model('Users')->order_fee($order, $cart_goods, $consignee);

            $this->assign('total', $total);



            /* 取得可以得到的积分和红包 */

            $this->assign('total_integral', model('Order')->cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);

            $this->assign('total_bonus', price_format(model('Order')->get_total_bonus(), false));



            /* 团购标志 */

            if ($flow_type == CART_GROUP_BUY_GOODS) {

                $this->assign('is_group_buy', 1);

            }



            $result['content'] = ECTouch::$view->fetch('library/order_total.lbi');

        }



        echo json_encode($result);

        exit;

    }



    /**

     * 改变贺卡

     */

    public function select_card() {

        $result = array('error' => '', 'content' => '', 'need_insure' => 0);



        /* 取得购物类型 */

        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;




        /* 获得收货人信息 */

        $consignee = model('Order')->get_consignee($_SESSION['user_id']);



        /* 对商品信息赋值 */

        $cart_goods = model('Order')->cart_goods($flow_type); // 取得商品列表，计算合计



        if (empty($cart_goods) || !model('Order')->check_consignee_info($consignee, $flow_type)) {

            $result['error'] = L('no_goods_in_cart');

        } else {

            /* 取得购物流程设置 */

            $this->assign('config', C('CFG'));



            /* 取得订单信息 */

            $order = model('Order')->flow_order_info();



            $order['card_id'] = intval($_REQUEST['card']);



            /* 保存 session */

            $_SESSION['flow_order'] = $order;



            /* 计算订单的费用 */

            $total = model('Users')->order_fee($order, $cart_goods, $consignee);

            $this->assign('total', $total);



            /* 取得可以得到的积分和红包 */

            $this->assign('total_integral', model('Order')->cart_amount(false, $flow_type) - $order['bonus'] - $total['integral_money']);

            $this->assign('total_bonus', price_format(model('Order')->get_total_bonus(), false));



            /* 团购标志 */

            if ($flow_type == CART_GROUP_BUY_GOODS) {

                $this->assign('is_group_buy', 1);

            }



            $result['content'] = ECTouch::$view->fetch('library/order_total.lbi');

        }



        echo json_encode($result);

        exit;

    }



    /**

     * 改变余额

     */

    public function change_surplus() {
        
        $surplus = floatval($_GET['surplus']);

        $user_info = model('Order')->user_info($_SESSION['user_id']);



        if ($user_info['user_money'] + $user_info['credit_line'] < $surplus) {

            $result['error'] = L('surplus_not_enough');

        } else {

            /* 取得购物类型 */

            $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;



            /* 取得购物流程设置 */

            $this->assign('config', C('CFG'));



            /* 获得收货人信息 */

            $consignee = model('Order')->get_consignee($_SESSION['user_id']);



            /* 对商品信息赋值 */

            $cart_goods = model('Order')->cart_goods($flow_type); // 取得商品列表，计算合计



            if (empty($cart_goods) || !model('Order')->check_consignee_info($consignee, $flow_type)) {

                $result['error'] = L('no_goods_in_cart');

            } else {

                /* 取得订单信息 */

                $order = model('Order')->flow_order_info();

                $order['surplus'] = $surplus;



                /* 计算订单的费用 */

                $total = model('Users')->order_fee($order, $cart_goods, $consignee);

                $this->assign('total', $total);



                /* 团购标志 */

                if ($flow_type == CART_GROUP_BUY_GOODS) {

                    $this->assign('is_group_buy', 1);

                }



                $result['content'] = ECTouch::$view->fetch('library/order_total.lbi');

            }

        }



        die(json_encode($result));

    }



    /**

     * 改变积分

     */

    public function change_integral() {

        $points = floatval($_GET['points']);

        $user_info = model('Order')->user_info($_SESSION['user_id']);



        /* 取得订单信息 */

        $order = model('Order')->flow_order_info();
         $cart_goods = model('Order')->get_cart_goods();
         foreach ($cart_goods['goods_list'] as $key => $value) {
             # code...
            $rec_id[] = $value['rec_id'];
         }
 
         $rec_id = implode(",", $rec_id);

           
 


        $flow_points = model('Flow')->flow_available_points();  // 该订单允许使用的积分

        $user_points = $user_info['pay_points']; // 用户的积分总数



        if ($points > $user_points) {

            $result['error'] = L('integral_not_enough');

        } elseif ($points > $flow_points) {

            $result['error'] = sprintf(L('integral_too_much'), $flow_points);

        } else {

            /* 取得购物类型 */

            $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;



            $order['integral'] = $points;



            /* 获得收货人信息 */

            $consignee = model('Order')->get_consignee($_SESSION['user_id']);



            /* 对商品信息赋值 */
           
            $cart_goods = model('Order')->cart_goods($flow_type,$cart_value); // 取得商品列表，计算合计

            

            if (empty($cart_goods) || !model('Order')->check_consignee_info($consignee, $flow_type)) {

                $result['error'] = L('no_goods_in_cart');

            } else {

                /* 计算订单的费用 */

                $total = model('Users')->order_fee($order, $cart_goods, $consignee,'');
          
                $this->assign('total', $total);

                $this->assign('config', C('CFG'));



                /* 团购标志 */

                if ($flow_type == CART_GROUP_BUY_GOODS) {

                    $this->assign('is_group_buy', 1);

                }


                $result['content'] = $total;

                //$result['content'] = ECTouch::$view->fetch('library/order_total.lbi');

                $result['error'] = '';

            }

        }



        die(json_encode($result));

    }



    /**

     * 改变红包

     */

    public function change_bonus() {

        $result = array('error' => '', 'content' => '');



        /* 取得购物类型 */

        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;



        /* 获得收货人信息 */

        $consignee = model('Order')->get_consignee($_SESSION['user_id']);



        /* 对商品信息赋值 */

        $cart_goods = model('Order')->cart_goods($flow_type); // 取得商品列表，计算合计



        if (empty($cart_goods) || !model('Order')->check_consignee_info($consignee, $flow_type)) {

            $result['error'] = L('no_goods_in_cart')."bonus";

        } else {

            /* 取得购物流程设置 */

            $this->assign('config', C('CFG'));



            /* 取得订单信息 */

            $order = model('Order')->flow_order_info();



            $bonus = model('Order')->bonus_info(intval($_GET['bonus']));



            if ((!empty($bonus) && $bonus['user_id'] == $_SESSION['user_id']) || $_GET['bonus'] == 0) {

                $order['bonus_id'] = intval($_GET['bonus']);

            } else {

                $order['bonus_id'] = 0;

                $result['error'] = L('invalid_bonus');

            }



            /* 计算订单的费用 */

            $total = model('Users')->order_fee($order, $cart_goods, $consignee);

            $this->assign('total', $total);



            /* 团购标志 */

            if ($flow_type == CART_GROUP_BUY_GOODS) {

                $this->assign('is_group_buy', 1);

            }



            $result['content'] = ECTouch::$view->fetch('library/order_total.lbi');

        }



        die(json_encode($result));

    }



    /**

     * 改变发票的设置

     */

    public function change_needinv() {

        $result = array('error' => '', 'content' => '');

        $_GET['inv_type'] = !empty($_GET['inv_type']) ? json_str_iconv(urldecode($_GET['inv_type'])) : '';

        $_GET['invPayee'] = !empty($_GET['invPayee']) ? json_str_iconv(urldecode($_GET['invPayee'])) : '';

        $_GET['inv_content'] = !empty($_GET['inv_content']) ? json_str_iconv(urldecode($_GET['inv_content'])) : '';



        /* 取得购物类型 */

        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;



        /* 获得收货人信息 */

        $consignee = model('Order')->get_consignee($_SESSION['user_id']);



        /* 对商品信息赋值 */

        $cart_goods = model('Order')->cart_goods($flow_type); // 取得商品列表，计算合计



        if (empty($cart_goods) || !model('Order')->check_consignee_info($consignee, $flow_type)) {

            $result['error'] = L('no_goods_in_cart')."inv";

            die(json_encode($result));

        } else {

            /* 取得购物流程设置 */

            $this->assign('config', C('CFG'));



            /* 取得订单信息 */

            $order = model('Order')->flow_order_info();



            if (isset($_GET['need_inv']) && intval($_GET['need_inv']) == 1) {

                $order['need_inv'] = 1;

                $order['inv_type'] = trim(stripslashes($_GET['inv_type']));

                $order['inv_payee'] = trim(stripslashes($_GET['inv_payee']));

                $order['inv_content'] = trim(stripslashes($_GET['inv_content']));

            } else {

                $order['need_inv'] = 0;

                $order['inv_type'] = '';

                $order['inv_payee'] = '';

                $order['inv_content'] = '';

            }



            /* 计算订单的费用 */

            $total = model('Users')->order_fee($order, $cart_goods, $consignee);

            $this->assign('total', $total);



            /* 团购标志 */

            if ($flow_type == CART_GROUP_BUY_GOODS) {

                $this->assign('is_group_buy', 1);

            }



            die(ECTouch::$view->fetch('library/order_total.lbi'));

        }

    }



    /**

     * 改变缺货处理时的方式

     */

    public function change_oos() {

        /* 取得订单信息 */

        $order = model('Order')->flow_order_info();



        $order['how_oos'] = intval($_GET['oos']);



        /* 保存 session */

        $_SESSION['flow_order'] = $order;

    }



    /**

     * 检查用户输入的余额

     */

    public function check_surplus() {

        /* ------------------------------------------------------ */

        //-- 检查用户输入的余额

        /* ------------------------------------------------------ */

        $surplus = floatval($_GET['surplus']);

        $user_info = model('Order')->user_info($_SESSION['user_id']);



        if (($user_info['user_money'] + $user_info['credit_line'] < $surplus)) {

            die(L('surplus_not_enough'));

        }



        exit;

    }



    /**

     * 检查用户输入的余额

     */

    public function check_integral() {

        $points = floatval($_GET['integral']);

        $user_info = model('Order')->user_info($_SESSION['user_id']);

        $flow_points = model('Flow')->flow_available_points();  // 该订单允许使用的积分

        $user_points = $user_info['pay_points']; // 用户的积分总数



        if ($points > $user_points) {

            die(L('integral_not_enough'));

        }



        if ($points > $flow_points) {

            die(sprintf(L('integral_too_much'), $flow_points));

        }



        exit;

    }



    /**

     * 放入收藏夹

     */

    public function drop_to_collect() {

        if ($_SESSION['user_id'] > 0) {

            $rec_id = intval($_GET['id']);

            $goods_id = $this->model->table('cart')->field('goods_id')->where("rec_id = '$rec_id' AND session_id = '" . SESS_ID . "'")->getOne();

            $count = $this->model->table('collect_goods')->field('goods_id')->where("user_id = '$_SESSION[user_id]' AND goods_id = '$goods_id'")->getOne();

            if (empty($count)) {

                $data['user_id'] = $_SESSION[user_id];

                $data['goods_id'] = $goods_id;

                $data['add_time'] = time();

                $this->model->table('collect_goods')->data($data)->insert();

            }

            model('Flow')->flow_drop_cart_goods($rec_id);

        }

        ecs_header("Location: " . url('flow/index') . "\n");

        exit;

    }



    /**

     *  验证红包序列号

     */

    public function validate_bonus() {

        $bonus_sn = trim($_REQUEST['bonus_sn']);

        if (is_numeric($bonus_sn)) {

            $bonus = model('Order')->bonus_info(0, $bonus_sn);

        } else {

            $bonus = array();

        }

        $bonus_kill = price_format($bonus['type_money'], false);



        $result = array('error' => '', 'content' => '');



        /* 取得购物类型 */

        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;



        /* 获得收货人信息 */

        $consignee = model('Order')->get_consignee($_SESSION['user_id']);



        /* 对商品信息赋值 */

        $cart_goods = model('Order')->cart_goods($flow_type); // 取得商品列表，计算合计



        if (empty($cart_goods) || !model('Order')->check_consignee_info($consignee, $flow_type)) {

            $result['error'] = L('no_goods_in_cart');

        } else {

            /* 取得购物流程设置 */

            $this->assign('config', C('CFG'));



            /* 取得订单信息 */

            $order = model('Order')->flow_order_info();





            if (((!empty($bonus) && $bonus['user_id'] == $_SESSION['user_id']) || ($bonus['type_money'] > 0 && empty($bonus['user_id']))) && $bonus['order_id'] <= 0) {

                //$order['bonus_kill'] = $bonus['type_money'];

                $now = time();

                if ($now > $bonus['use_end_date']) {

                    $order['bonus_id'] = '';

                    $result['error'] = L('bonus_use_expire');

                } else {

                    $order['bonus_id'] = $bonus['bonus_id'];

                    $order['bonus_sn'] = $bonus_sn;

                }

            } else {

                $order['bonus_id'] = '';

                $result['error'] = L('invalid_bonus');

            }



            /* 计算订单的费用 */

            $total = model('Users')->order_fee($order, $cart_goods, $consignee);



            if ($total['goods_price'] < $bonus['min_goods_amount']) {

                $order['bonus_id'] = '';

                /* 重新计算订单 */

                $total = model('Users')->order_fee($order, $cart_goods, $consignee);

                $result['error'] = sprintf(L('bonus_min_amount_error'), price_format($bonus['min_goods_amount'], false));

            }



            $this->assign('total', $total);



            /* 团购标志 */

            if ($flow_type == CART_GROUP_BUY_GOODS) {

                $this->assign('is_group_buy', 1);

            }



            $result['content'] = ECTouch::$view->fetch('library/order_total.lbi');

        }

        die(json_encode($result));

    }



    /**

     * 添加礼包到购物车

     */

    public function add_package_to_cart() {

        $_POST['package_info'] = json_str_iconv($_POST['package_info']);



        $result = array('error' => 0, 'message' => '', 'content' => '', 'package_id' => '');



        if (empty($_POST['package_info'])) {

            $result['error'] = 1;

            die(json_encode($result));

        }

        $json = new EcsJson;

        $package = $json->decode($_POST['package_info']);



        /* 如果是一步购物，先清空购物车 */

        if (C('one_step_buy') == '1') {

            model('Order')->clear_cart();

        }



        /* 商品数量是否合法 */

        if (!is_numeric($package->number) || intval($package->number) <= 0) {

            $result['error'] = 1;

            $result['message'] = L('invalid_number');

        } else {

            /* 添加到购物车 */

            if (model('Order')->add_package_to_cart($package->package_id, $package->number)) {

                if (C('cart_confirm') > 2) {

                    $result['message'] = '';

                } else {

                    $result['message'] = C('cart_confirm') == 1 ? L('addto_cart_success_1') : L('addto_cart_success_2');

                }



                $result['content'] = insert_cart_info();

                $result['one_step_buy'] = C('one_step_buy');

            } else {

                $result['message'] = ECTouch::err()->last_message();

                $result['error'] = ECTouch::err()->error_no;

                $result['package_id'] = stripslashes($package->package_id);

            }

        }

        $cart_confirm = C('cart_confirm');

        $result['confirm_type'] = !empty($cart_confirm) ? $cart_confirm : 2;

        die(json_encode($result));

    }



    /**

     * 改变配送地址

     */

    public function select_address() {

        $result = array('error' => '', 'content' => '', 'need_insure' => 0, 'address' => 1);

        $address_id = intval($_REQUEST['address']);

        if (model('Users')->save_consignee_default($address_id)) {

            die(json_encode($result));

        } else {

            $result['error'] = '选择错误';

            die(json_encode($result));

        }

    }



    /**

     * 更换支付方式

     */

    public function change_payment() {

        if ($_POST) {

            // 接收数据

            $payment_id = intval($_POST ['payment']);

            $order_sn = $_POST ['order_sn'];



            if ($order_sn) {

                // 订单信息

                $order_info = model('Order')->order_info(0, $order_sn);

                $payment_id = $payment_id ? $payment_id : $order_info['pay_id'];

                // 支付信息

                $payment_info = model('Order')->payment_info($payment_id);



                // 用户不对应

                if ($_SESSION ['user_id'] != $order_info ['user_id']) {

                    show_message('请选择对应的订单', '订单列表', url('user/order_list'));

                    exit();

                }

                $amount = $order_info ['order_amount'] - $order_info ['pay_fee'];

                $pay_fee = pay_fee($payment_id, $amount, 0);



                /* 如果全部使用余额支付，检查余额是否足够 */

                if ($payment_info ['pay_code'] == 'balance' && $order_info ['order_amount'] > 0) {

                    //用户信息

                    $user_info = model('Order')->user_info($_SESSION ['user_id']);



                    if ($order_info ['order_amount'] > ($user_info ['user_money'] + $user_info ['credit_line'])) {

                        show_message(L('balance_not_enough'));

                    } else {

                        $order ['surplus'] = $order_info ['order_amount'];

                        $order ['order_amount'] = 0;

                    }

                    /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */

                    if ($order ['order_amount'] <= 0) {

                        $order ['order_status'] = OS_CONFIRMED;

                        $order ['confirm_time'] = time();

                        $order ['pay_status'] = PS_PAYED;

                        $order ['pay_time'] = time();

                        $order ['order_amount'] = 0;

                    }



                    /* 处理余额 */

                    if ($order_info ['user_id'] > 0 && $order ['surplus'] > 0) {

                        //model('ClipsBase')->log_account_change($order_info ['user_id'], $order ['surplus'] * (- 1), 0, 0, 0, sprintf(L('pay_order'), $order_info ['order_sn']));
                        //余额发生变动的时候进行拆单

                        model('ClipsBase')->new_log_account_change($order_info ['user_id'], $order ['surplus'] * (- 1),sprintf(L('order_gift_integral'), $order ['order_sn']),ACT_OTHER, 1);



                    }

                    $data['pay_id'] = $payment_info['pay_id'];

                    $data['pay_name'] = $payment_info ['pay_name'];

                    $data['pay_fee'] = $pay_fee;

                    $data['surplus'] = $order['surplus'];

                    $data['order_amount'] = $order['order_amount'];

                    $data['order_status'] = $order['order_status'];

                    $data['confirm_time'] = $order['confirm_time'];

                    $data['pay_status'] = $order['pay_status'];

                    $data['pay_time'] = $order['pay_time'];

                    $this->model->table('order_info')->data($data)->where('order_id = ' . $order_info ['order_id'])->update();

                    $info['order_amount'] = $order['order_amount'];

                    $this->model->table('pay_log')->data($info)->where('order_id = ' . $order_info ['order_id'])->update();

                    $this->alert('支付成功', url('user/order_list'));

                } else {



                    // 最新总额

                    $order_amount = $amount + $pay_fee;

                    $data_order['pay_id'] = $payment_id;

                    $data_order['pay_name'] = $payment_info ['pay_name'];

                    $data_order['pay_fee'] = $pay_fee;

                    $data_order['order_amount'] = $order_amount;

                    $this->model->table('order_info')->data($data_order)->where('order_id = ' . $order_info ['order_id'])->update();

                    $data_pay['order_amount'] = $order_amount;

                    $this->model->table('pay_log')->data($data_pay)->where('order_id = ' . $order_info ['order_id'])->update();

                    /* 调用相应的支付方式文件 */

                    include_once (ROOT_PATH . 'plugins/payment/' . $payment_info ['pay_code'] . '.php');



                    /* 取得在线支付方式的支付链接，直接跳转 */

                    $pay_obj = new $payment_info ['pay_code'] ();

                    $pay_code = $pay_obj->get_code($order_info, $payment_info);



                    if (empty($pay_code)) {

                        $this->redirect('user/order_list');

                        exit;

                    }



                    echo $pay_code;

                }

                exit();

            } else {

                show_message('请重新选择支付方式', '订单列表', url('user/order_list'));

            }

        } else {

            $this->redirect(url('flow/index'));

        }

    }

     public function flow_rutuan_consignee() {
        
        $this->display('flow_rutuan_consignee.dwt');
     }

    /**



     * 获取配送地址列表



     */

    public function consignee_list() {
        

        $one_step_buy=$_GET['one_step_buy']?1:0;
        if (IS_AJAX) {

            $start = $_POST ['last'];
            $limit = $_POST ['amount'];
         
            if(!$_SESSION['user_id']){

            $_SESSION['user_id'] =$_GET['u'];
        }
            $one_step_buy=$_GET['one_step_buy']?1:0;
            $order_type=$_GET['order_type']?$_GET['order_type']:0;

            $consignee_list = model('Users')->get_consignee_list($_SESSION['user_id'], 0, $limit, $start);

            if ($consignee_list) {

                foreach ($consignee_list as $k => $v) {
                   if(!empty($v['consignee'])){
                         $address = '';

                        if ($v['province']) {

                            $address .= model('RegionBase')->get_region_name($v['province']);

                        }

                        if ($v['city']) {

                            $address .= model('RegionBase')->get_region_name($v['city']);

                        }

                        if ($v['district']) {

                            $address .= model('RegionBase')->get_region_name($v['district']);

                        }

                        $v['address'] = $address . ' ' . $v['address'];

                        $v['url'] = url('flow/consignee', array('id' => $v ['address_id']));
                
                        $v['order_type'] = $order_type;

                        $this->assign('flow_consignee', $v);

                        $this->assign('one_step_buy', $one_step_buy);
                        $this->assign('order_type', $order_type);
                        $sayList [] = array(

                            'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')

                        );
                   }
                   

                }

            }

            die(json_encode($sayList));

            exit();

        }

        // 赋值于模板
        
        $this->assign('page_title', L('consignee_info'));

        // 加载user语言包
        $order_type = $_GET['order_type'];
        require(APP_PATH . C('_APP_NAME') . '/languages/' . C('LANG') . '/user.php');

        $_LANG = array_merge(L(), $_LANG);
        $this->assign('order_type',$order_type);
        $this->assign('one_step_buy',$_GET['one_step_buy']);
        $this->assign('lang', $_LANG);
        $this->assign('user_id', $_GET['u']);
        $this->assign('headerContent', $_LANG['consignee_list']);
        $this->display('flow_consignee_list.dwt');

    }



    /**



     * 删除收货人信息



     */

    public function drop_consignee() {
        $order_type =$_GET['order_type'];
        $consignee_id = intval($_GET['id']);
        $one_step_buy =$_GET['one_step_buy'];
        if (model('Users')->drop_consignee($consignee_id)) {

            ecs_header("Location: " . url('flow/consignee_list',array('one_step_buy'=>$one_step_buy,'order_type'=>$order_type)) . "\n");

            exit;

        } else {

            show_message(L('not_fount_consignee'));

        }

    }

    /*设置默认收货地址*/

    public function edit_address_info() {

        if (IS_AJAX && IS_AJAX) {

            $address_id = I('id');

            $data['address_id'] = $address_id;

            $condition['user_id'] = $_SESSION['user_id'];

            $r = $this->model->table('users')->data($data)->where($condition)->update();

            unset($_SESSION['flow_consignee']);
            
            echo json_encode(array('status' => 1));

        } else {

            echo json_encode(array('status' => 0));

         }



    }



     /**

     *

     */

    function  add_to_cart_combo()

    {

        $_POST['goods']=strip_tags(urldecode($_POST['goods']));

        $_POST['goods'] = json_str_iconv($_POST['goods']);



        if (!empty($_REQUEST['goods_id']) && empty($_POST['goods']))

        {

            if (!is_numeric($_REQUEST['goods_id']) || intval($_REQUEST['goods_id']) <= 0)

            {

                ecs_header("Location:./\n");

            }

            $goods_id = intval($_REQUEST['goods_id']);

            exit;

        }

        $result = array('error' => 0, 'message' => '', 'content' => '', 'goods_id' => '');

        $json = new EcsJson();



        if (empty($_POST['goods']))

        {

            $result['error'] = 1;

            die($json->encode($result));

        }



        $goods = $json->decode($_POST['goods']);

        /* 检查：如果商品有规格，而post的数据没有规格，把商品的规格属性通过JSON传到前台 */

        if (empty($goods->spec) AND empty($goods->quick))

        {

            $sql = "SELECT a.attr_id, a.attr_name, a.attr_type, ".

                "g.goods_attr_id, g.attr_value, g.attr_price " .

                'FROM ' . $this->model->pre . 'goods_attr' . ' AS g ' .

                'LEFT JOIN ' . $this->model->pre . 'attribute' . ' AS a ON a.attr_id = g.attr_id ' .

                "WHERE a.attr_type != 0 AND g.goods_id = '" . $goods->goods_id . "' " .

                'ORDER BY a.sort_order, g.attr_price, g.goods_attr_id';



            $res = $this->model->query($sql);



            if (!empty($res))

            {

                $spe_arr = array();

                foreach ($res AS $row)

                {

                    $spe_arr[$row['attr_id']]['attr_type'] = $row['attr_type'];

                    $spe_arr[$row['attr_id']]['name']     = $row['attr_name'];

                    $spe_arr[$row['attr_id']]['attr_id']     = $row['attr_id'];

                    $spe_arr[$row['attr_id']]['values'][] = array(

                        'label'        => $row['attr_value'],

                        'price'        => $row['attr_price'],

                        'format_price' => price_format($row['attr_price'], false),

                        'id'           => $row['goods_attr_id']);

                }

                $i = 0;

                $spe_array = array();

                foreach ($spe_arr AS $row)

                {

                    $spe_array[]=$row;

                }

                $result['error']   = ERR_NEED_SELECT_ATTR;

                $result['goods_id'] = $goods->goods_id;

                $result['parent'] = $goods->parent;

                $result['message'] = $spe_array;

                $result['group'] = $goods->group;



                die($json->encode($result));

            }

        }





        /* 更新：如果是一步购物，先清空购物车 */

        if (C('one_step_buy') == '1')

        {

            model('Order')->clear_cart();

        }

        /* 检查：商品数量是否合法 */

        if (!is_numeric($goods->number) || intval($goods->number) <= 0)

        {

            $result['error']   = 1;

            $result['message'] = 'buzu';

        }

        /* 更新：购物车 */

        else

        {

            // 更新：添加到购物车

            if (model('Order')->addto_cart_combo($goods->goods_id, $goods->number, $goods->spec, $goods->parent, $goods->group))

            {

                if (C('cart_confirm') > 2)

                {

                    $result['message'] = '';

                }

                else

                {

                    $result['message'] = C('cart_confirm') == 1 ? L('addto_cart_success_1') : L('addto_cart_success_2');

                }



                $result['group']    = $goods->group;

                $result['goods_id'] = stripslashes($goods->goods_id);

                $result['content'] = insert_cart_info();

                $result['one_step_buy'] = C('one_step_buy');



                //返回 原价，配件价，库存信息

                $combo_goods_info = model('Order')->get_combo_goods_info($goods->goods_id, $goods->number, $goods->spec, $goods->parent);

                $result['fittings_price'] = $combo_goods_info['fittings_price'];

                $result['spec_price']   = $combo_goods_info['spec_price'];

                $result['goods_price'] = $combo_goods_info['goods_price'];

                $result['stock'] = $combo_goods_info['stock'];

                $result['parent'] = $goods->parent;

            }

            else

            {

                $result['message']  = ECTOUCH::err()->last_message();

                $result['error']    = ECTOUCH::err()->error_no;

                $result['group']    = $goods->group;

                $result['goods_id'] = stripslashes($goods->goods_id);

                if (is_array($goods->spec))

                {

                    $result['product_spec'] = implode(',', $goods->spec);

                }

                else

                {

                    $result['product_spec'] = $goods->spec;

                }

            }

        }

      $cart_confirm =  C('cart_confirm');

        $result['confirm_type'] = !empty($cart_confirm) ? $cart_confirm : 2;

        die($json->encode($result));

    }



    /**

     * 删除购物车项目

     */

    public function del_in_cart_combo()

    {

        $_POST['goods']=strip_tags(urldecode($_POST['goods']));

        $_POST['goods'] = json_str_iconv($_POST['goods']);



        if (!empty($_REQUEST['goods_id']) && empty($_POST['goods']))

        {

            if (!is_numeric($_REQUEST['goods_id']) || intval($_REQUEST['goods_id']) <= 0)

            {

                ecs_header("Location:./\n");

            }

            $goods_id = intval($_REQUEST['goods_id']);

            exit;

        }



        $result = array('error' => 0, 'message' => '');

        $json = new EcsJson();



        if (empty($_POST['goods']))

        {

            $result['error'] = 1;

            die($json->encode($result));

        }



        $goods = $json->decode($_POST['goods']);



        if($goods->parent == 0){

            //更新临时购物车（删除基本件）

            $sql = "DELETE FROM " . $this->model->pre . 'cart_combo' . " WHERE session_id='" . SESS_ID . "'".

                " AND goods_id = '" . $goods->goods_id . "' AND group_id = '" . $goods->group . "'";

            $this->model->query($sql);

            //更新临时购物车（删除配件）

            $sql = "DELETE FROM " . $this->model->pre . 'cart_combo' . " WHERE session_id='" . SESS_ID . "'".

                " AND parent_id = '".$goods->goods_id."' AND group_id = '" . $goods->group . "'";

            $this->model->query($sql);

        }else{

            //更新临时购物车（删除配件）

            $sql = "DELETE FROM " . $this->model->pre . 'cart_combo' . " WHERE session_id='" . SESS_ID . "'".

                " AND goods_id = '" . $goods->goods_id . "' AND group_id = '" . $goods->group . "'";

            $this->model->query($sql);

        }



        $result['error'] = 0;

        $result['group'] = substr($goods->group, 0, strrpos($goods->group, "_"));

        $result['parent'] = $goods->parent;



        die($json->encode($result));

    }



    /**

     * 套餐添加到购物车

     */

    function add_to_cart_group()

    {

        $_POST['goods'] = strip_tags(urldecode($_POST['goods']));

        $_POST['goods'] = json_str_iconv($_POST['goods']);

        $result = array('error' => 0, 'message' => '');

        $json = new EcsJson();



        if (empty($_POST['goods']))

        {

            $result['error'] = 1;

            $result['message'] = '系统无法接收不完整的数据';

            die($json->encode($result));

        }



        $goods = $json->decode($_POST['goods']);

        $group = $goods->group ."_". $goods->goods_id;//套餐组

        //批量加入购物车

        $sql = "SELECT rec_id FROM " . $this->model->pre . 'cart_combo' . " WHERE session_id = '" . SESS_ID . "'" .

            " AND group_id = '". $group ."' ORDER BY parent_id limit 1";

        $res = $this->model->query($sql);

        if($res){

            //清空购物车中的原有数据

            $sql = "DELETE FROM " . $this->model->pre . 'cart' . " WHERE ".

                " session_id='" . SESS_ID . "' AND group_id = '" . $group . "'";

             $this->model->query($sql);

            //插入新的数据

            $sql = "INSERT INTO " . $this->model->pre . 'cart' . " SELECT * FROM " . $this->model->pre . 'cart_combo' . " WHERE ".

                " session_id='" . SESS_ID . "' AND group_id = '" . $group . "'";

            $this->model->query($sql);

            //插入更新购物车商品数量
            $_SESSION ['flow_type'] = CART_ACCESSORY_GOODS;
            $sql = "UPDATE " . $this->model->pre . 'cart' . " set goods_number = '$goods->number' ,rec_type = ".CART_ACCESSORY_GOODS." WHERE ".

                " session_id='" . SESS_ID . "' AND group_id = '" . $group . "'";

            $this->model->query($sql);

            //清空套餐临时数据

            $sql = "DELETE FROM " . $this->model->pre . 'cart_combo' . " WHERE ".

                " session_id='" . SESS_ID . "' AND group_id = '" . $group . "'";

            $this->model->query($sql);

        }else{

            $result['error'] = 1;

            $result['message'] = '暂无数据可提交，请重新选择';

            die($json->encode($result));

        }


        $_SESSION['one_step_buy_group'] = 1;
        $result['error']  = 0;

        die($json->encode($result));

    }



    /**

     * 更新购物车中的商品数量

     *

     * @access  public

     * @param   array   $arr

     * @return  void

     */

    function flow_update_cart($arr)

    {

        /* 处理 */

        foreach ($arr AS $key => $val)

        {

            $val = intval(make_semiangle($val));

            if ($val <= 0 || !is_numeric($key))

            {

                continue;

            }



            //查询：

            $sql = "SELECT `goods_id`, `goods_attr_id`, `product_id`, `extension_code` FROM" . $this->model->pre . 'cart'.

            " WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";

            $goods = $this->model->query($sql);

            $goods = $goods['0'];

            $sql = "SELECT g.goods_name, g.goods_number ".

                "FROM " . $this->model->pre . 'goods' . " AS g, ".

                $this->model->pre . 'cart' . " AS c ".

                "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";

            $row = $this->model->query($sql);

            $row = $goods['0'];



            //查询：系统启用了库存，检查输入的商品数量是否有效

            if (intval(C('use_storage')) > 0 && $goods['extension_code'] != 'package_buy')

            {

                if ($row['goods_number'] < $val)

                {

                    show_message(sprintf(L('stock_insufficiency'), $row['goods_name'],

                    $row['goods_number'], $row['goods_number']));

                    exit;

                }

                /* 是货品 */

                $goods['product_id'] = trim($goods['product_id']);

                if (!empty($goods['product_id']))

                {

                    $sql = "SELECT product_number FROM " .$this->model->pre . 'products' . " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $goods['product_id'] . "'";



                    $product_number = $this->model->query($sql);

                    $product_number = $product_number['0']['product_number'];

                    if ($product_number < $val)

                    {

                        show_message(sprintf(L('stock_insufficiency'), $row['goods_name'],

                        $product_number['product_number'], $product_number['product_number']));

                        exit;

                    }

                }

            }

            elseif (intval(C('use_storage')) > 0 && $goods['extension_code'] == 'package_buy')

            {

                if (model('Order')->judge_package_stock($goods['goods_id'], $val))

                {

                    show_message(L('package_stock_insufficiency'));

                    exit;

                }

            }



            /* 查询：检查该项是否为基本件 以及是否存在配件 */

            /* 此处配件是指添加商品时附加的并且是设置了优惠价格的配件 此类配件都有parent_id goods_number为1 */

            $sql = "SELECT b.goods_number, b.rec_id

                FROM " .$this->model->pre . 'cart' . " a, " .$this->model->pre . 'cart' . " b

                    WHERE a.rec_id = '$key'

                    AND a.session_id = '" . SESS_ID . "'

                AND a.extension_code <> 'package_buy'

                AND b.parent_id = a.goods_id

                AND b.session_id = '" . SESS_ID . "'";



            $offers_accessories_res = $this->model->query($sql);



            //订货数量大于0

            if ($val > 0)

            {

                /* 判断是否为超出数量的优惠价格的配件 删除*/

                $row_num = 1;

                foreach ($offers_accessories_res as $key=>$offers_accessories_row){

                    if ($row_num > $val)

                    {

                        $sql = "DELETE FROM " . $this->model->pre . 'cart' .

                        " WHERE session_id = '" . SESS_ID . "' " .

                        "AND rec_id = '" . $offers_accessories_row['rec_id'] ."' AND group_id='' LIMIT 1";

                        $this->model->query($sql);

                    }



                    $row_num ++;

                }



                /* 处理超值礼包 */

                if ($goods['extension_code'] == 'package_buy')

                {

                    //更新购物车中的商品数量

                    $sql = "UPDATE " .$this->model->pre . 'cart' .

                    " SET goods_number = '$val' WHERE rec_id='$key' AND session_id='" . SESS_ID . "' AND group_id=''";

                }

                /* 处理普通商品或非优惠的配件 */

                else

                {

                    $attr_id    = empty($goods['goods_attr_id']) ? array() : explode(',', $goods['goods_attr_id']);

                    $goods_price = model('Goodsbase')->get_final_price($goods['goods_id'], $val, true, $attr_id);



                    //更新购物车中的商品数量

                    $sql = "UPDATE " .$this->model->pre . 'cart' .

                    " SET goods_number = '$val', goods_price = '$goods_price' WHERE rec_id='$key' AND session_id='" . SESS_ID . "' AND group_id=''";

                }

            }

            //订货数量等于0

            else

            {

                /* 如果是基本件并且有优惠价格的配件则删除优惠价格的配件 */

                foreach ($offers_accessories_res as $key=>$offers_accessories_row){

                   $sql = "DELETE FROM " . $this->model->pre . 'cart' .

                    " WHERE session_id = '" . SESS_ID . "' " .

                    "AND rec_id = '" . $offers_accessories_row['rec_id'] ."' AND group_id='' LIMIT 1";

                    $this->model->query($sql);

                }



                $sql = "DELETE FROM " .$this->model->pre . 'cart' .

                " WHERE rec_id='$key' AND session_id='" .SESS_ID. "' AND group_id=''";

            }



            $this->model->query($sql);

        }



        /* 删除所有赠品 */

        $sql = "DELETE FROM " . $this->model->pre . 'cart' . " WHERE session_id = '" .SESS_ID. "' AND is_gift <> 0";

        $this->model->query($sql);

    }



     /*

     * label选中价格

     */

    public function cart_label_count(){

        $goods_id  = I('goods_id','');

        $parent_id  = I('parent_id','');

        if($parent_id ){

            $shop_price = $this->model->table('goods')->where(array('goods_id'=>$parent_id))->field('shop_price')->getOne();

        }

        if($goods_id) {

            $sql = "select g.shop_price ,gg.goods_price from " . $this->model->pre ."group_goods as gg LEFT JOIN " . $this->model->pre . "goods as g on gg.goods_id = g.goods_id " . "where gg.goods_id in ($goods_id) and gg.parent_id = $parent_id ";

            $count = $this->model->query($sql);

        }

        $num=0;

        if(count($count)>0){

            foreach($count as $key){

                $count_price += floatval($key['goods_price']);

                $num ++;

            }

        }else{

            $count_price = '0.00';

        }

        if($shop_price){

            $count_price += floatval($shop_price);

            $num += 1;

        }

        $result['content'] = price_format($count_price);

        $result['cart_number'] = $num;

        die(json_encode($result));

    }



    /**

    *选中提交发票信息

    */

    public function change_inv(){

        $need_inv = $_POST['need_inv'];

        if($need_inv == 1){

            $inv_content = !empty($_POST['inv_type']) ? ($_POST['inv_type'] == 1 ? '个人' : '单位') : '';

            $inv_person_name = !empty($_POST['inv_person_name']) ? $_POST['inv_person_name'] : '';

            $inv_payee = !empty($_POST['inv_company_name']) ? $_POST['inv_company_name'] : '';

            $inv_text_id = !empty($_POST['inv_text_id']) ? $_POST['inv_text_id'] : '';

            $inv_type = !empty($_POST['inv_name']) ? $_POST['inv_name'] : '';



            if($inv_content == '个人'){

                $_SESSION['inv_payee'] = $inv_person_name;

                $_SESSION['inv_type'] = $inv_type;

                $_SESSION['inv_content'] = '个人';

                if(!empty($_SESSION['inv_text_id'])){

                    unset($_SESSION['inv_text_id']);

                }

                show_message('保存发票信息成功', '继续结算', url('flow/checkout'));



            }else{

                $_SESSION['inv_payee'] = $inv_payee;

                $_SESSION['inv_type'] = $inv_type;

                $_SESSION['inv_text_id'] = $inv_text_id;

                $_SESSION['inv_content'] = '单位';

                show_message('保存发票信息成功', '继续结算', url('flow/checkout'));

            }

        }else{

            unset($_SESSION['inv_payee']);

            unset($_SESSION['inv_type']);

            unset($_SESSION['inv_text_id']);

            unset($_SESSION['inv_content']);

            show_message('未选择开发票', '继续结算', url('flow/checkout'));

        }

    }


    public function done_old() {
        /* 取得购物类型 */
        $flow_type = isset($_SESSION ['flow_type']) ? intval($_SESSION ['flow_type']) : CART_GENERAL_GOODS;
        $fixed_freight = empty(floatval($_POST['fixed_freight'])) ? '' : floatval($_POST['fixed_freight']);
        /* 检查购物车中是否有商品
        is_gift : 是否是实物parent_id 该商品的父商品id，没有该值为0，有的话那该商品就是该id的配件
        rec_type: 购物车商品类型，0，普通；1，团够；2，拍卖；3，夺宝奇兵 */

        $condition = " session_id = '" . SESS_ID . "' " . "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";
        $count = $this->model->table('cart')->field('COUNT(*)')->where($condition)->getOne();

        if ($count == 0) {
            show_message(L('no_goods_in_cart'), '', '', 'warning');
        }


        /* 如果使用库存，且下订单时减库存，则减少库存 */
        if (C('use_storage') == '1' && C('stock_dec_time') == SDT_PLACE) {
            $cart_goods_stock = model('Order')->get_cart_goods();
            $_cart_goods_stock = array();
            foreach ($cart_goods_stock ['goods_list'] as $value) {
                $_cart_goods_stock [$value ['rec_id']] = $value ['goods_number'];
            }
            model('Flow')->flow_cart_stock($_cart_goods_stock);
            unset($cart_goods_stock, $_cart_goods_stock);
        }
        // 检查用户是否已经登录 如果用户已经登录了则检查是否有默认的收货地址 如果没有登录则跳转到登录和注册页面
        if (empty($_SESSION ['direct_shopping']) && $_SESSION ['user_id'] == 0) {
            /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
            //ecs_header("Location: " . url('user/login') . "\n");
            ecs_header("Location: " . url('user/register') . "\n");
        }

        // 获取收货人信息
        $consignee = model('Order')->get_consignee($_SESSION ['user_id']);

        /* 检查收货人信息是否完整 */
        if (!model('Order')->check_consignee_info($consignee, $flow_type)) {
            /* 如果不完整则转向到收货人信息填写界面 */
            ecs_header("Location: " . url('flow/consignee') . "\n");
        }
        // 处理接收信息
        $how_oos = I('post.how_oos', 0);
        $card_message = I('post.card_message', '');
        $inv_type = I('post.inv_type', '');
        $inv_payee = I('post.inv_payee', '');
        $inv_content = I('post.inv_content', '');
        $postscript = I('post.postscript', '');
        $oos = L('oos.' . $how_oos);
        // 订单信息
        $order = array(
            'shipping_id' => I('post.shipping'),
            'pay_id' => I('post.payment'), // 付款方式
            'pack_id' => I('post.pack', 0),
            'card_id' => isset($_POST ['card']) ? intval($_POST ['card']) : 0,
            'card_message' => $card_message,
            'surplus' => isset($_POST ['surplus']) ? floatval($_POST ['surplus']) : 0.00,
            'integral' => isset($_POST ['integral']) ? intval($_POST ['integral']) : 0,
            'bonus_id' => isset($_POST ['bonus']) ? intval($_POST ['bonus']) : 0,
            'need_inv' => empty($_POST ['inv_type']) ? 0 : 1,
            'inv_type' => $_POST ['inv_type'],
            'inv_payee' => trim($_POST ['inv_payee']),
            'inv_content' => $_POST['inv_content'],
            'inv_text_id' => $_POST['inv_text_id'],
            'postscript' => $postscript,
            'how_oos' => isset($oos) ? addslashes("$oos") : '',
            'need_insure' => isset($_POST ['need_insure']) ? intval($_POST ['need_insure']) : 0,
            'user_id' => $_SESSION ['user_id'],
            'add_time' => time(),
            'order_status' => OS_UNCONFIRMED,
            'shipping_status' => SS_UNSHIPPED,
            'pay_status' => PS_UNPAYED,
            'agency_id' => model('Order')->get_agency_by_regions(array(
                $consignee ['country'],
                $consignee ['province'],
                $consignee ['city'],
                $consignee ['district']
            ))
        );

        /* 扩展信息 */
        if (isset($_SESSION ['flow_type']) && intval($_SESSION ['flow_type']) != CART_GENERAL_GOODS) {
            $order ['extension_code'] = $_SESSION ['extension_code'];
            $order ['extension_id'] = $_SESSION ['extension_id'];
        } else {
            $order ['extension_code'] = '';
            $order ['extension_id'] = 0;
        }
        /* 检查积分余额是否合法 */
        $user_id = $_SESSION ['user_id'];
        if ($user_id > 0) {
            $user_info = model('Order')->user_info($user_id);
            $order ['surplus'] = min($order ['surplus'], $user_info ['user_money'] + $user_info ['credit_line']);
            if ($order ['surplus'] < 0) {
                $order ['surplus'] = 0;
            }
            // 查询用户有多少积分
            $flow_points = model('Flow')->flow_available_points(); // 该订单允许使用的积分
            $user_points = $user_info ['pay_points']; // 用户的积分总数
            $order ['integral'] = min($order ['integral'], $user_points, $flow_points);
            if ($order ['integral'] < 0) {
                $order ['integral'] = 0;
            }
        } else {
            $order ['surplus'] = 0;
            $order ['integral'] = 0;
        }
        /* 检查红包是否存在 */
        if ($order ['bonus_id'] > 0) {
            $bonus = model('Order')->bonus_info($order ['bonus_id']);
            if (empty($bonus) || $bonus ['user_id'] != $user_id || $bonus ['order_id'] > 0 || $bonus ['min_goods_amount'] > model('Order')->cart_amount(true, $flow_type)) {
                $order ['bonus_id'] = 0;
            }
        } elseif (isset($_POST ['bonus_sn'])) {
            $bonus_sn = trim($_POST ['bonus_sn']);
            $bonus = model('Order')->bonus_info(0, $bonus_sn);
            $now = time();
            if (empty($bonus) || $bonus ['user_id'] > 0 || $bonus ['order_id'] > 0 || $bonus ['min_goods_amount'] > model('Order')->cart_amount(true, $flow_type) || $now > $bonus ['use_end_date']) {
            } else {
                if ($user_id > 0) {
                    $sql = "UPDATE " . $this->model->pre . "user_bonus SET user_id = '$user_id' WHERE bonus_id = '$bonus[bonus_id]' LIMIT 1";
                    $this->model->query($sql);
                }
                $order ['bonus_id'] = $bonus ['bonus_id'];
                $order ['bonus_sn'] = $bonus_sn;
            }
        }

        /* 订单中的商品 */
        $cart_goods = model('Order')->cart_goods($flow_type);
        //print_r($cart_goods);

        if (empty($cart_goods)) {
            show_message(L('no_goods_in_cart'), L('back_home'), './', 'warning');
        }

        /* 检查商品总额是否达到最低限购金额 */
        if ($flow_type == CART_GENERAL_GOODS && model('Order')->cart_amount(true, CART_GENERAL_GOODS) < C('min_goods_amount')) {
            show_message(sprintf(L('goods_amount_not_enough'), price_format(C('min_goods_amount'), false)));
        }

        /* 收货人信息 */
        foreach ($consignee as $key => $value) {
            $order [$key] = addslashes($value);
        }
        /* 判断是不是实体商品 */
        foreach ($cart_goods as $val) {
            /* 统计实体商品的个数 */
            if ($val ['is_real']) {
                $is_real_good = 1;
            }
        }

        $goods_id = '';
        $is_fixed_frieght = false;  //是否是固定运费
        foreach($cart_goods as $k => $v){
            $freight_type = $this->model->table('goods')->field('freight_type')->where('goods_id='.$v['goods_id'])->find();
            if($freight_type['freight_type'] == 1){
                $is_fixed_frieght = true;
            }
            $goods_id[] = $v['goods_id'];
        }
        if($is_fixed_frieght == false){
            if (isset($is_real_good)) {
                $res = $this->model->table('shipping')->field('shipping_id')->where("shipping_id=" . $order ['shipping_id'] . " AND enabled =1")->getOne();
                if (!$res) {
                    show_message(L('flow_no_shipping'));
                }
            }
        }

        /* 订单中的总额 */
        $total = model('Users')->order_fee($order, $cart_goods, $consignee,$fixed_freight);

        $order ['bonus'] = $total ['bonus'];
        $order ['goods_amount'] = $total ['goods_price'];
        $order ['discount'] = $total ['discount'];
        $order ['surplus'] = $total ['surplus'];
        $order ['tax'] = $total ['tax'];
        // 购物车中的商品能享受红包支付的总额
        $discount_amout = model('Order')->compute_discount_amount();
        // 红包和积分最多能支付的金额为商品总额
        $temp_amout = $order ['goods_amount'] - $discount_amout;
        if ($temp_amout <= 0) {
            $order ['bonus_id'] = 0;
        }

        /* 配送方式 */

        if ($order ['shipping_id'] > 0) {
            $shipping = model('Shipping')->shipping_info($order ['shipping_id']);
            $order ['shipping_name'] = addslashes($shipping ['shipping_name']);
        }else{
            $order ['shipping_name'] = "固定运费";
        }

        $order ['shipping_fee'] = $total ['shipping_fee'];
        $order ['insure_fee'] = $total ['shipping_insure'];
        /* 支付方式 */
        if ($order ['pay_id'] > 0) {
            $payment = model('Order')->payment_info($order ['pay_id']);
            $order ['pay_name'] = addslashes($payment ['pay_name']);
        }
        $order ['pay_fee'] = $total ['pay_fee'];
        $order ['cod_fee'] = $total ['cod_fee'];
        /* 商品包装 */
        if ($order ['pack_id'] > 0) {
            $pack = model('Order')->pack_info($order ['pack_id']);
            $order ['pack_name'] = addslashes($pack ['pack_name']);
        }
        $order ['pack_fee'] = $total ['pack_fee'];
        /* 祝福贺卡 */
        if ($order ['card_id'] > 0) {
            $card = model('Order')->card_info($order ['card_id']);
            $order ['card_name'] = addslashes($card ['card_name']);
        }
        $order ['card_fee'] = $total ['card_fee'];
        $order ['order_amount'] = number_format($total ['amount'], 2, '.', '');
        $balancemoney = model('ClipsBase')->finduseraccount($_SESSION['user_id'],"balance");
        /* 如果全部使用余额支付，检查余额是否足够 */
        if ($payment ['pay_code'] == 'balance' && $order ['order_amount'] > 0) {
            if ($order ['surplus'] > 0) {    // 余额支付里如果输入了一个金额
                $order ['order_amount'] = $order ['order_amount'] + $order ['surplus'];
                $order ['surplus'] = 0;
            }

            if ($order ['order_amount'] > ($balancemoney)) {
                show_message(L('balance_not_enough'));
            } else {
                $order ['surplus'] = $order ['order_amount'];
                $order ['order_amount'] = 0;
            }
        }

        /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
        if ($order ['order_amount'] <= 0) {
            $order ['order_status'] = OS_CONFIRMED;
            $order ['confirm_time'] = time();
            $order ['pay_status'] = PS_PAYED;
            $order ['pay_time'] = time();
            $order ['order_amount'] = 0;
        }

        $order ['integral_money'] = $total ['integral_money'];
        $order ['integral'] = $total ['integral'];
        if ($order ['extension_code'] == 'exchange_goods') {
            $order ['integral_money'] = 0;
            $order ['integral'] = $total ['exchange_integral'];
        }

        $order ['from_ad'] = !empty($_SESSION ['from_ad']) ? $_SESSION ['from_ad'] : '0';
        $order ['referer'] = !empty($_SESSION ['referer']) ? addslashes($_SESSION ['referer']). 'Touch' : 'Touch';

        /* 记录扩展信息 */
        if ($flow_type != CART_GENERAL_GOODS) {
            $order ['extension_code'] = $_SESSION ['extension_code'];
            $order ['extension_id'] = $_SESSION ['extension_id'];

        }

        $parent_id = M()->table('users')->field('parent_id')->where("user_id=".$_SESSION['user_id'])->getOne();
        $order ['parent_id'] = $parent_id;

        /* 插入订单表 */
        $error_no = 0;
        do {
            $order ['order_sn'] = get_order_sn(); // 获取新订单号
            $new_order = model('Common')->filter_field('order_info', $order);
            $this->model->table('order_info')->data($new_order)->insert();
            $error_no = M()->errno();
            if ($error_no > 0 && $error_no != 1062) {
                die(M()->errorMsg());
            }
        } while ($error_no == 1062); // 如果是订单号重复则重新提交数据
        $new_order_id = M()->insert_id();
        $order ['order_id'] = $new_order_id;


        /* 插入订单商品 */
        $sql = "INSERT INTO " . $this->model->pre . "order_goods( " . "order_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price, " . "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id) " . " SELECT '$new_order_id', goods_id, goods_name, goods_sn, product_id, goods_number, market_price, " . "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id " . " FROM " . $this->model->pre . "cart WHERE session_id = '" . SESS_ID . "' AND rec_type = '$flow_type'";
        $this->model->query($sql);
        /* 修改拍卖活动状态 */
        if ($order ['extension_code'] == 'auction') {
            $sql = "UPDATE " . $this->model->pre . "goods_activity SET is_finished='2' WHERE act_id=" . $order ['extension_id'];
            $this->model->query($sql);
        }


        /* 处理余额、积分、红包 */
        if ($order ['user_id'] > 0 && $order ['surplus'] > 0) {
           // model('ClipsBase')->log_account_change($order ['user_id'], $order ['surplus'] * (- 1), 0, 0, 0, sprintf(L('pay_order'), $order ['order_sn']));
            model('ClipsBase')->new_log_account_change($order ['user_id'], $order ['surplus'] * (- 1),sprintf(L('pay_order'), $order ['order_sn']),ACT_OTHER, 1);
        }

        if ($order ['user_id'] > 0 && $order ['integral'] > 0) {
           // model('ClipsBase')->log_account_change($order ['user_id'], 0, 0, 0, (-1)*$order ['integral'], sprintf(L('pay_order'), $order ['order_sn']));
            model('ClipsBase')->new_log_account_change($order ['user_id'], (-1)*$order ['integral'],sprintf(L('pay_order'), $order ['order_sn']),ACT_OTHER, 6);
        }

        if ($order ['bonus_id'] > 0 && $temp_amout > 0) {
            model('Order')->use_bonus($order ['bonus_id'], $new_order_id);
        }

        /* 如果使用库存，且下订单时减库存，则减少库存 */
        if (C('use_storage') == '1' && C('stock_dec_time') == SDT_PLACE) {
            model('Order')->change_order_goods_storage($order ['order_id'], true, SDT_PLACE);
        }


        /* 给商家发邮件 */
        /* 增加是否给客服发送邮件选项 */
        if (C('send_service_email') && C('service_email') != '') {
            $tpl = model('Base')->get_mail_template('remind_of_new_order');
            $this->assign('order', $order);
            $this->assign('goods_list', $cart_goods);
            $this->assign('shop_name', C('shop_name'));
            $this->assign('send_date', date(C('time_format')));
            $content = ECTouch::$view->fetch('str:' . $tpl ['template_content']);
            send_mail(C('shop_name'), C('service_email'), $tpl ['template_subject'], $content, $tpl ['is_html']);
        }

        /* 如果需要，发短信 */
        if (C('sms_order_placed') == '1' && C('sms_shop_mobile') != '') {
            $sms = new EcsSms();
            $msg = $order ['pay_status'] == PS_UNPAYED ? L('order_placed_sms') : L('order_placed_sms') . '[' . L('sms_paid') . ']';
            $sms->send(C('sms_shop_mobile'), sprintf($msg, $order ['consignee'], $order ['mobile']), '', 13, 1);
        }

        //  /* 如果需要，微信通知 by wanglu */

        // if (method_exists('WechatController', 'snsapi_base') && is_wechat_browser()) {

        //     $order_url = __HOST__ . url('user/order_detail', array('order_id' => $order ['order_id']));

        //     $order_url = urlencode(base64_encode($order_url));

        //     send_wechat_message('order_remind', '', $order['order_sn'] . L('order_effective'), $order_url, $order['order_sn']);

        // }

        // 微信通模板消息

        if (class_exists('WechatController') && is_wechat_browser() ) {
            $pushData = array(
                'first' => array('value' => '您的订单已提交成功','color' => '#173177'),
                'orderID' => array('value' => $order['order_sn'],'color' => '#FF0000'), //订单号
                'orderMoneySum' => array('value' => $order['order_amount'],'color' => '#FF0000'), //订单金额
                'backupFieldName' => array('value' => ''),
                'remark' => array('value' => '请尽快完成支付，感谢您的购买！','color' =>'#173177')
            );

            $url = __HOST__ . U('user/order_detail', array('order_id' => $new_order_id));
            pushTemplate('OPENTM415293129', $pushData, $url);

        }

        /* 如果订单金额为0 处理虚拟卡 */
        if ($order ['order_amount'] <= 0) {
            $sql = "SELECT goods_id, goods_name, goods_number AS num FROM " . $this->model->pre . "cart WHERE is_real = 0 AND extension_code = 'virtual_card'" . " AND session_id = '" . SESS_ID . "' AND rec_type = '$flow_type'";
            $res = $this->model->query($sql);

            $virtual_goods = array();
            foreach ($res as $row) {
                $virtual_goods ['virtual_card'] [] = array(
                    'goods_id' => $row ['goods_id'],
                    'goods_name' => $row ['goods_name'],
                    'num' => $row ['num']
                );

            }

            if ($virtual_goods and $flow_type != CART_GROUP_BUY_GOODS) {
                /* 虚拟卡发货 */
                if (model('OrderBase')->virtual_goods_ship($virtual_goods, $msg, $order ['order_sn'], true)) {
                    /* 如果没有实体商品，修改发货状态，送积分和红包 */
                    $count = $this->model->table('order_goods')->field('COUNT(*)')->where("order_id = '$order[order_id]' " . " AND is_real = 1")->getOne();
                    if ($count <= 0) {
                        /* 修改订单状态 */
                        model('Users')->update_order($order ['order_id'], array(
                            'shipping_status' => SS_SHIPPED,
                            'shipping_time' => time()
                        ));

                        /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
                        if ($order ['user_id'] > 0) {
                            /* 取得用户信息 */
                            $user = model('Order')->user_info($order ['user_id']);
                            /* 计算并发放积分 */
                            $integral = model('Order')->integral_to_give($order);
                           // model('ClipsBase')->log_account_change($order ['user_id'], 0, 0, intval($integral ['rank_points']), intval($integral ['custom_points']), sprintf(L('order_gift_integral'), $order ['order_sn']));
                             model('ClipsBase')->new_log_account_change($order ['user_id'], intval($integral ['rank_points']),sprintf(L('order_gift_integral'), $order ['order_sn']),ACT_OTHER, 5);
                             model('ClipsBase')->new_log_account_change($order ['user_id'], intval($integral ['custom_points']),sprintf(L('order_gift_integral'), $order ['order_sn']),ACT_OTHER, 6);
                            /* 发放红包 */
                            model('Order')->send_order_bonus($order ['order_id']);

                        }
                    }
                }
            }
        }
        // 销量
        model('Flow')->add_touch_goods($flow_type, $order ['extension_code']);

        /* 清空购物车 */
        $goods_id = implode(',',$goods_id);
        if(!empty($goods_id)){
            model('Order')->clear_cart_buy($goods_id);
        }

        /* 清除缓存，否则买了商品，但是前台页面读取缓存，商品数量不减少 */
        clear_all_files();

        /* 插入支付日志 */
        $order ['log_id'] = model('ClipsBase')->insert_pay_log($new_order_id, $order ['order_amount'], PAY_ORDER);
        /* 取得支付信息，生成支付代码 */

        if ($order['order_amount'] > 0) {
            $payment = model('Order')->payment_info($order ['pay_id']);
            
            include_once (ROOT_PATH . 'plugins/payment/' . $payment ['pay_code'] . '.php');

            $pay_obj = new $payment ['pay_code'] ();
            $pay_online = $pay_obj->get_code($order, unserialize_config($payment ['pay_config']));
            $order ['pay_desc'] = $payment ['pay_desc'];
            $this->assign('pay_online', $pay_online);
        }

        if($is_fixed_frieght == false){
            if (!empty($order ['shipping_name'])) {
                $order ['shipping_name'] = trim(stripcslashes($order ['shipping_name']));
            }
        }else{
            $order ['shipping_name'] = "固定运费";

        }

        // 如果是银行汇款或货到付款 则显示支付描述
        if ($payment['pay_code'] == 'bank' || $payment['pay_code'] == 'cod'){
            if (empty($order ['pay_name'])) {
                $order ['pay_name'] = trim(stripcslashes($payment ['pay_name']));
            }
            $this->assign('pay_desc',$order['pay_desc']);
        }

        // 货到付款不显示
        if ($payment ['pay_code'] != 'balance') {
            /* 生成订单后，修改支付，配送方式 */
            // 支付方式
            $payment_list = model('Order')->available_payment_list(0);
            if (isset($payment_list)) {
                foreach ($payment_list as $key => $payment) {
                    /* 如果有易宝神州行支付 如果订单金额大于300 则不显示 */
                    if ($payment ['pay_code'] == 'yeepayszx' && $total ['amount'] > 300) {
                        unset($payment_list [$key]);
                    }
                    // 过滤掉当前的支付方式
                    if ($payment ['pay_id'] == $order ['pay_id']) {
                        unset($payment_list [$key]);
                    }
                    /* 如果有余额支付 */
                    if ($payment ['pay_code'] == 'balance') {
                        /* 如果未登录，不显示 */
                        if ($_SESSION ['user_id'] == 0) {
                            unset($payment_list [$key]);
                        } else {
                            if ($_SESSION ['flow_order'] ['pay_id'] == $payment ['pay_id']) {
                                $this->assign('disable_surplus', 1);
                            }
                        }
                    }
                    // 如果不是微信浏览器访问并且不是微信会员 则不显示微信支付
                    if ($payment ['pay_code'] == 'wxpay' && !is_wechat_browser() && empty($_SESSION['openid'])) {
                        unset($payment_list [$key]);
                    }
                    // 兼容过滤ecjia支付方式
                    if (substr($payment['pay_code'], 0 , 4) == 'pay_') {
                        unset($payment_list[$key]);
                    }
                }
            }
            $this->assign('payment_list', $payment_list);
            $this->assign('pay_code', 'no_balance');
        }

        /* 订单信息 */


        $this->assign('order', $order);
        $this->assign('total', $total);
        $this->assign('goods_list', $cart_goods);
        $this->assign('order_submit_back', sprintf(L('order_submit_back'), L('back_home'), L('goto_user_center'))); // 返回提示

        user_uc_call('add_feed', array($order ['order_id'], BUY_GOODS)); // 推送feed到uc
        unset($_SESSION ['flow_consignee']); // 清除session中保存的收货人信息
        unset($_SESSION ['flow_order']);
        unset($_SESSION ['direct_shopping']);

        $this->assign('currency_format', C('currency_format'));
        $this->assign('integral_scale', C('integral_scale'));
        $this->assign('step', ACTION_NAME);
        $this->assign('page_title', L('order_submit'));
        $this->display('flow.dwt');

    }

    public function checkout_old() {
        // 取得购物类型
        $flow_type = isset($_SESSION ['flow_type']) ? intval($_SESSION ['flow_type']) : CART_GENERAL_GOODS;
        //团购标志
        if ($flow_type == CART_GROUP_BUY_GOODS) {
            $this->assign('is_group_buy', 1);
        } elseif ($flow_type == CART_EXCHANGE_GOODS) { // 积分兑换商品
            $this->assign('is_exchange_goods', 1);
        } else {
            // 正常购物流程 清空其他购物流程情况
            $_SESSION ['flow_order'] ['extension_code'] = '';
        }
        //检查购物车中是否有商品
        $condition = "session_id = '" . SESS_ID . "' " . "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";
        $count = $this->model->table('cart')->field('COUNT(*)')->where($condition)->getOne();
        if ($count == 0) {
            show_message(L('no_goods_in_cart'), '', '', 'warning');
        }
        //检查用户是否已经登录 如果用户已经登录了则检查是否有默认的收货地址 如果没有登录则跳转到登录和注册页面
        if (empty($_SESSION ['direct_shopping']) && $_SESSION ['user_id'] == 0) {
            // 用户没有登录且没有选定匿名购物，转向到登录页面
            $this->redirect(url('user/register',array('step'=>'flow')));
            exit;
        }
        // 获取收货人信息
        $consignee = model('Order')->get_consignee($_SESSION ['user_id']);
        // 检查收货人信息是否完整
        if (!model('Order')->check_consignee_info($consignee, $flow_type)) {
            // 如果不完整则转向到收货人信息填写界面
            ecs_header("Location: " . url('flow/consignee_list') . "\n");
        }
        // 获取配送地址
        $consignee_list = model('Users')->get_consignee_list($_SESSION ['user_id']);
        $this->assign('consignee_list', $consignee_list);
        //获取默认配送地址
        $address_id = $this->model->table('users')->field('address_id')->where("user_id = '" . $_SESSION['user_id'] . "' ")->getOne();
        $this->assign('address_id', $address_id);
        $_SESSION ['flow_consignee'] = $consignee;
        $this->assign('consignee', $consignee);
        // 对商品信息赋值
        $cart_goods = model('Order')->cart_goods($flow_type); // 取得商品列表，计算合计
        $this->assign('goods_list', $cart_goods);
        // 对是否允许修改购物车赋值
        if ($flow_type != CART_GENERAL_GOODS || C('one_step_buy') == '1') {
            $this->assign('allow_edit_cart', 0);
        } else {
            $this->assign('allow_edit_cart', 1);
        }
        //取得购物流程设置
        $this->assign('config', C('CFG'));
        // 取得订单信息
        $order = model('Order')->flow_order_info();
        // 计算折扣
        if ($flow_type != CART_EXCHANGE_GOODS && $flow_type != CART_GROUP_BUY_GOODS) {
            $discount = model('Order')->compute_discount();
            $this->assign('discount', $discount ['discount']);
            $favour_name = empty($discount ['name']) ? '' : join(',', $discount ['name']);
            $this->assign('your_discount', sprintf(L('your_discount'), $favour_name, price_format($discount ['discount'])));
        }
        $order['inv_type'] = $_SESSION['inv_type'];
        $order['need_inv'] = empty($order['inv_type']) ? 0 : 1;
        //计算订单的费用
        $total = model('Users')->order_fee($order, $cart_goods, $consignee);
        $this->assign('total', $total);
        $this->assign('shopping_money', sprintf(L('shopping_money'), $total ['formated_goods_price']));
        $this->assign('market_price_desc', sprintf(L('than_market_price'), $total ['formated_market_price'], $total ['formated_saving'], $total ['save_rate']));
        // 取得可以得到的积分和红包
        $this->assign('total_integral', model('Order')->cart_amount(false, $flow_type) - $total ['bonus'] - $total ['integral_money']);
        $this->assign('total_bonus', price_format(model('Order')->get_total_bonus(), false));
        //取得配送列表
        $region = array(
            $consignee ['country'],
            $consignee ['province'],
            $consignee ['city'],
            $consignee ['district']
        );
        $shipping_list = model('Shipping')->available_shipping_list($region);
        $cart_weight_price = model('Order')->cart_weight_price($flow_type);
        $insure_disabled = true;
        $cod_disabled = true;
        // 查看购物车中是否全为免运费商品，若是则把运费赋为零
        $condition = "`session_id` = '" . SESS_ID . "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
        $shipping_count = $this->model->table('cart')->field('count(*)')->where($condition)->getOne();
        foreach ($shipping_list as $key => $val) {
            $shipping_cfg = unserialize_config($val ['configure']);
            $shipping_fee = ($shipping_count == 0 and $cart_weight_price ['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val ['configure']), $cart_weight_price ['weight'], $cart_weight_price ['amount'], $cart_weight_price ['number']);
            $shipping_list [$key] ['format_shipping_fee'] = price_format($shipping_fee, false);
            $shipping_list [$key] ['shipping_fee'] = $shipping_fee;
            $shipping_list [$key] ['free_money'] = price_format($shipping_cfg ['free_money'], false);
            $shipping_list [$key] ['insure_formated'] = strpos($val ['insure'], '%') === false ? price_format($val ['insure'], false) : $val ['insure'];
            // 当前的配送方式是否支持保价
            if ($val ['shipping_id'] == $order ['shipping_id']) {
                $insure_disabled = ($val ['insure'] == 0);
                $cod_disabled = ($val ['support_cod'] == 0);
            }

            // 兼容过滤ecjia配送方式
            if (substr($val['shipping_code'], 0 , 5) == 'ship_') {
                unset($shipping_list[$key]);
            }
        }
        $this->assign('shipping_list', $shipping_list);
        $this->assign('insure_disabled', $insure_disabled);
        $this->assign('cod_disabled', $cod_disabled);
        // 取得支付列表

        if ($order ['shipping_id'] == 0) {
            $cod = true;
            $cod_fee = 0;
        } else {
            $shipping = model('Shipping')->shipping_info($order ['shipping_id']);
            $cod = $shipping ['support_cod'];
            if ($cod) {
                // 如果是团购，且保证金大于0，不能使用货到付款
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $group_buy_id = $_SESSION ['extension_id'];
                    if ($group_buy_id <= 0) {
                        show_message('error group_buy_id');
                    }

                    $group_buy = model('GroupBuyBase')->group_buy_info($group_buy_id);
                    if (empty($group_buy)) {
                        show_message('group buy not exists: ' . $group_buy_id);
                    }
                    if ($group_buy ['deposit'] > 0) {
                        $cod = false;
                        $cod_fee = 0;
                        // 赋值保证金
                        $this->assign('gb_deposit', $group_buy ['deposit']);
                    }
                }
                if ($cod) {
                    $shipping_area_info = model('Shipping')->shipping_area_info($order ['shipping_id'], $region);
                    $cod_fee = $shipping_area_info ['pay_fee'];
                }
            } else {
                $cod_fee = 0;
            }
        }
        // 给货到付款的手续费加<span id>，以便改变配送的时候动态显示
        $payment_list = model('Order')->available_payment_list(1, $cod_fee);
        if (isset($payment_list)) {
            foreach ($payment_list as $key => $payment) {
                // 只保留显示手机版支付方式
                if(!file_exists(ROOT_PATH . 'plugins/payment/'.$payment['pay_code'].'.php')){
                    unset($payment_list[$key]);
                }
                if ($payment ['is_cod'] == '1') {
                    $payment_list [$key] ['format_pay_fee'] = '<span id="ECS_CODFEE">' . $payment ['format_pay_fee'] . '</span>';
                }
                // 如果有易宝神州行支付 如果订单金额大于300 则不显示
                if ($payment ['pay_code'] == 'yeepayszx' && $total ['amount'] > 300) {
                    unset($payment_list [$key]);
                }

                // 如果有余额支付
                if ($payment ['pay_code'] == 'balance') {
                    // 如果未登录，不显示
                    if ($_SESSION ['user_id'] == 0) {
                        unset($payment_list [$key]);
                    } else {
                        if ($_SESSION ['flow_order'] ['pay_id'] == $payment ['pay_id']) {
                            $this->assign('disable_surplus', 1);
                        }
                    }
                }

                // 如果不是微信浏览器访问并且不是微信会员 则不显示微信支付
                if ($payment ['pay_code'] == 'wxpay' && !is_wechat_browser() && empty($_SESSION['openid'])) {
                    unset($payment_list [$key]);
                }

                // 兼容过滤ecjia支付方式
                if (substr($payment['pay_code'], 0 , 4) == 'pay_') {
                    unset($payment_list[$key]);
                }
            }
        }
        $this->assign('payment_list', $payment_list);

        //取得包装与贺卡
        if ($total ['real_goods_count'] > 0) {
            // 只有有实体商品,才要判断包装和贺卡
            $use_package = C('use_package');
            if (!isset($use_package) || C('use_package') == '1') {
                // 如果使用包装，取得包装列表及用户选择的包装
                $this->assign('pack_list', model('Order')->pack_list());
            }
            // 如果使用贺卡，取得贺卡列表及用户选择的贺卡
            $use_card = C('use_card');
            if (!isset($use_card) || C('use_card') == '1') {
                $this->assign('card_list', model('Order')->card_list());
            }
        }
        $user_info = model('Order')->user_info($_SESSION ['user_id']);
        // 如果使用余额，取得用户余额
        $use_surplus = C('use_surplus');
        if ((!isset($use_surplus) || C('use_surplus') == '1') && $_SESSION ['user_id'] > 0 && $user_info ['user_money'] > 0) {
            // 能使用余额
            $this->assign('allow_use_surplus', 1);
            $this->assign('your_surplus', $user_info ['user_money']);
        }
        // 如果使用积分，取得用户可用积分及本订单最多可以使用的积分
        $use_integral = C('use_integral');
        if ((!isset($use_integral) || C('use_integral') == '1') && $_SESSION ['user_id'] > 0 && $user_info ['pay_points'] > 0 && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS)) {
            // 能使用积分
            $this->assign('allow_use_integral', 1);
            $this->assign('order_max_integral', model('Flow')->flow_available_points()); // 可用积分
            $this->assign('your_integral', $user_info ['pay_points']); // 用户积分
        }
        // 如果使用红包，取得用户可以使用的红包及用户选择的红包
        $use_bonus = C('use_bonus');
        if ((!isset($use_bonus) || C('use_bonus') == '1') && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS)) {
            // 取得用户可用红包
            $user_bonus = model('Order')->user_bonus($_SESSION ['user_id'], $total ['goods_price']);
            if (!empty($user_bonus)) {
                foreach ($user_bonus as $key => $val) {
                    $user_bonus [$key] ['bonus_money_formated'] = price_format($val ['type_money'], false);
                }
                $this->assign('bonus_list', $user_bonus);
            }
            // 能使用红包
            $this->assign('allow_use_bonus', 1);
        }
        // 如果使用缺货处理，取得缺货处理列表
        $use_how_oos = C('use_how_oos');
        if (!isset($use_how_oos) || $use_how_oos == '1') {
            $oos = L('oos');
            if (is_array($oos) && !empty($oos)) {
                $this->assign('how_oos_list', L('oos'));
            }
        }
       // 如果能开发票，取得发票内容列表
        $can_invoice = C('can_invoice');
        $invoice_content = C('invoice_content');
        if ((!isset($can_invoice) || $can_invoice == '1') && isset($invoice_content) && trim($invoice_content) != '' && $flow_type != CART_EXCHANGE_GOODS) {
            $inv_content_list = explode("\n", str_replace("\r", '', C('invoice_content')));
            $this->assign('inv_content_list', $inv_content_list);
            $inv_type_list = array();
            $invoice_type = C('invoice_type');
            foreach ($invoice_type['type'] as $key => $type) {
                if (!empty($type)) {
                    $inv_type_list [$type] = $type . ' [' . floatval($invoice_type['rate'] [$key]) . '%]';
                }
            }
            $this->assign('inv_type_list', $inv_type_list);
        }
        $this->assign('inv_payee', $_SESSION['inv_payee']);
        $this->assign('inv_type', $_SESSION['inv_type']);
        $this->assign('inv_text_id', $_SESSION['inv_text_id']);
        $this->assign('inv_content', $_SESSION['inv_content']);
        $this->assign('order', $order);
        // 保存 session
        $_SESSION ['flow_order'] = $order;
        $this->assign('currency_format', C('currency_format'));
        $this->assign('integral_scale', C('integral_scale'));
        $this->assign('step', ACTION_NAME);
        model('Common')->assign_dynamic('shopping_flow');
        $this->assign('page_title', L('order_detail'));
        $this->display('flow.dwt');
    }


    public function is_buy_radio(){
        $current_id = !empty($_POST['current_id']) ? $_POST['current_id'] : 0;
        $group_id = !empty($_POST['group_id']) ? $_POST['group_id'] : 0;
        $is_buy = $_POST['is_buy'];
        
        $result = model('Cart') -> is_buy_radio($current_id,$group_id,$is_buy);
        //print_r($result);
        $goods_list = $result['goods_list'];
        $total = $result['total'];

        if(!empty($goods_list)){
            echo json_encode(array('code' => 200,'money' => $total['goods_amount'],'number' => $total['total_number']));
        }else{
            echo json_encode(array('code' => 500));
        }
    }

    public function is_buy_multi(){
        $goods_id = !empty($_POST['goods_id']) ? $_POST['goods_id'] : 0;
        $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : 0;
        $change_goods = !empty($_POST['change_goods']) ? $_POST['change_goods'] : 0;
        $is_buy = $_POST['is_buy'];

        if($goods_id[0] == ''){
            unset($goods_id[0]);
        }

        $result = model('Cart') -> is_buy_multi($goods_id,$is_buy,$parent_id,$change_goods);

        $goods_list = $result['goods_list'];
        $total = $result['total'];

        if(!empty($goods_list)){
            echo json_encode(array('code' => 200,'money' => $total['goods_amount'],'number' => $total['total_number']));
        }else{
            echo json_encode(array('code' => 500));
        }
    }


    public function settlement(){

        $cart_goods = model('Order')->get_cart_goods(1);
    
        $chongxiaototal = 0;
    
        foreach($cart_goods['goods_list'] as $k => $v){
            $goods_info = model('Goods')->get_goods_info($v['goods_id']);
            //增加是否可以购买列车产品判断
            
             if($_SESSION ['user_id']>0&&$goods_info['train_id']>0){

                 $allowbuy = model("Train")->allowBuy($_SESSION ['user_id'],$v['goods_id'],$v["goods_number"]);
         
                 if($allowbuy!="ok"){
                    
                     echo json_encode(array('code' => 400,'message' => $allowbuy ));exit;
                     break;

                 }

             }
            if($v['rtcat_id']==2&&$v['is_buy']==1){
                $chongxiaototal +=$v['goods_price'] * $v['goods_number'];
            }
            $cart_goods ['goods_list'][$k]['shipping_id'] = $goods_info['shipping_id'];
            $shipping_id[] = $goods_info['shipping_id'];
        }
        /*如果含有重消的产品，计算重消的产品总价是否达到200/300，否则提示您选购的重消商品总额未达200/300元*/
        /*获取直销那边的用户领航员级别*/

        if($_SESSION['user_rank']&&$chongxiaototal){

            $user_info = model('Users')->get_profile($_SESSION['user_id']);

             $detaildata = array(
                                                    
                                                    "account" =>$user_info['vip_manage_account']
                                                 );
             
             $ret = model("Index")->postData($detaildata,"/api/user/info/detail");

             switch ($ret['is_super']) {
                 case '1':
                     # code...
                     break;
                 case '2':
                     # code...
                     break;
                case '3':
                     # 1星领航员
                    if($chongxiaototal<200){
                         echo json_encode(array('code' => 400,'order_type'=>2,'message' => "重消总价不够" ));exit();
                    }
                     break;
                case '4':
                     # 2星领航员.
                      if($chongxiaototal<200){
                         echo json_encode(array('code' => 400,'order_type'=>2,'message' => "重消总价不够" ));exit();
                      }
                     break;
                case '5':
                     # 3星领航员
                    if($chongxiaototal<200){
                         echo json_encode(array('code' => 400,'order_type'=>2,'message' => "重消总价不够" ));exit();
                    }
                     break;

                case '6':
                     # 首席领航员
                    if($chongxiaototal<200){
                         echo json_encode(array('code' => 400,'order_type'=>2,'message' => "重消总价不够" ));exit();
                    }
                     break;
                case '7':
                     # 国际领袖
                    if($chongxiaototal<300){
                         echo json_encode(array('code' => 400,'order_type'=>2,'message' => "重消总价不够" ));exit();
                    }
                     break;
                case '8':
                     # 环球董事
                    if($chongxiaototal<300){
                         echo json_encode(array('code' => 400,'order_type'=>2,'message' => "重消总价不够" ));exit();
                    }
                     break;
                case '9':
                     # 寰宇总裁
                    if($chongxiaototal<300){
                         echo json_encode(array('code' => 400,'order_type'=>2,'message' => "重消总价不够" ));exit();
                    }
                     break;
                 default:
                     # code...
                     break;
             }
           
        }
        
        if(!empty($shipping_id)){
            $shipping_id = array_unique($shipping_id);
            $count = count($shipping_id);
            if($count >= 2){
                echo json_encode(array('code' => 300,'message' => "亲 ~~ 购物车商品有不同的物流运送方式 请分别购买"));exit();
            }else{
                if($_SESSION['user_rank']&&$chongxiaototal){
                      echo json_encode(array('code' => 200,'order_type'=>2));exit();
                  }else{
                      echo json_encode(array('code' => 200));exit();
                  }
              

            }
        }else{
            echo json_encode(array('code' => 400,'message' => "亲 ~~ 请选择要购买的商品" ));exit();
        }
    }

    //增加购物车商品数量
    public function Increase_number(){
        $goods_id = !empty($_POST['goods_id']) ? $_POST['goods_id'] : 0;
        $is_buy = $_POST['is_buy'];
        if($is_buy == 1){

            $result = model('Cart') -> Increase_number($goods_id);
          
            $goods_list = $result['goods_list'];
            $total = $result['total'];
            if(!empty($result)){
                foreach($goods_list as $k => $v){
                    if($v['goods_id'] == $goods_id){
                        $current = $v['goods_number'];
                    }
                }
                echo json_encode(array('code' => 200,'money' => $total['goods_amount'],'number' => $total['total_number'],'current' => $current));
            }
        }else{

            $result = model('Cart') -> Increase_number($goods_id);
          
            $goods_list = $result['goods_list'];
            $total = $result['total'];
            if(!empty($result)){
                foreach($goods_list as $k => $v){
                    if($v['goods_id'] == $goods_id){
                        $current = $v['goods_number'];
                    }
                }
                echo json_encode(array('code' => 200,'money' => $total['goods_amount'],'number' => $total['total_number'],'current' => $current));
            }
        }

    }

    //减少购物车商品数量
    public function Reduction_number(){
        $goods_id = !empty($_POST['goods_id']) ? $_POST['goods_id'] : 0;
        $is_buy = $_POST['is_buy'];

        if($is_buy == 1){
            $result = model('Cart') -> Reduction_number($goods_id);
            //print_r($result);
            if($result['code'] == 200){
                $goods_list = $result['cart']['goods_list'];
                $total = $result['cart']['total'];
                if(!empty($result)){
                    foreach($goods_list as $k => $v){
                        if($v['goods_id'] == $goods_id){
                            $current = $v['goods_number'];
                        }
                    }
                    echo json_encode(array('code' => 200,'money' => $total['goods_amount'],'number' => $total['total_number'],'current' => $current));
                }
            }else{
                echo json_encode(array('code' => 300));
            }
        }else{
            $result = model('Cart') -> Reduction_number($goods_id);
            //print_r($result);
            if($result['code'] == 200){
                $goods_list = $result['cart']['goods_list'];
                $total = $result['cart']['total'];
                if(!empty($result)){
                    foreach($goods_list as $k => $v){
                        if($v['goods_id'] == $goods_id){
                            $current = $v['goods_number'];
                        }
                    }
                    echo json_encode(array('code' => 200,'money' => $total['goods_amount'],'number' => $total['total_number'],'current' => $current));
                }
            }else{
                echo json_encode(array('code' => 300));
            }
        }

    }
    //shoudongshuru
    public function Input_number(){
        $rec_id = !empty($_POST['rec_id']) ? $_POST['rec_id'] : 0;
        $goods_number = !empty($_POST['goods_number']) ? $_POST['goods_number'] : 0;
        $is_buy = $_POST['is_buy'];

          if($is_buy == 1){
            $result = model('Cart') -> Input_number($rec_id,$goods_number);
            //print_r($result);
            if($result['code'] == 200){
                $goods_list = $result['cart']['goods_list'];

                $total = $result['cart']['total'];
                if(!empty($result)){
                    foreach($goods_list as $k => $v){
                        if($v['goods_id'] == $goods_id){
                            $current = $v['goods_number'];
                        }
                    }
                    echo json_encode(array('code' => 200,'money' => $total['goods_amount'],'number' => $total['total_number'],'current' => $current));
                }
            }else{
                echo json_encode(array('code' => 300));
            }
        }else{
            $result = model('Cart') -> Reduction_Dont_buy_goods($goods_id);
            echo json_encode(array('code' => 400,'current' => $result['goods_number']));
        }

    }
    /*入团地址填写*/
    public function joingroupaddress()
    {
         $this->assign('country_list', model('RegionBase')->get_regions());

        $this->assign('shop_country', C('shop_country'));

        $this->assign('shop_province_list', model('RegionBase')->get_regions(1, C('shop_country')));
        $this->display('joingroupaddress.dwt');
    }
    public function ajaxgetusername(){
        $account = $_POST['account'];
        
        $user_info = model("Users")->getusersbyaccount($account);
        if($user_info){
           echo json_encode(array('code' => 200,'msg'=>'','data' => $user_info['user_rank'])); 
       }else{
           echo json_encode(array('code' => 400,'msg'=>'无此账号','data' =>''));
       }
    }
    
    public function ajaxgetvipaccount(){
        $account = $_POST['account'];
   
        $user_info = model("Users")->get_usersbyvipaccount($account);
        if($user_info){
           
            if($user_info['user_vip']){
                  echo json_encode(array('code' => 200,'msg'=>'','data' => $user_info['user_vip']));exit;
            }else{
                  echo json_encode(array('code' => 300,'msg'=>'该邀请人不存在','data' => $user_info['user_vip']));exit;
            }
          

        }else{
            echo json_encode(array('code' => 400,'msg'=>'无此会员编号','data' =>''));exit;
        }
    }
    
    
    public function ajaxgetuservip(){
        $code = $_POST['code'];

        $uservip = model("Users")->getuservip($code);
        if($uservip){
            echo json_encode(array('code' => 200));
        }else{
            echo json_encode(array('code' => 400));
        }


    }
    public function ajax_getUserinfo(){
        $vip_manage_account = $_POST['vip_manage_account'];
            $r = model("Users")->get_user_info_new($vip_manage_account);
            if($r){

                  echo json_encode(array('code' => 200,'msg'=>'','data'=>$r['nick_name']));
              }else{
                  echo json_encode(array('code' => 400,'msg'=>'','data'=>array()));
              }
    }




}


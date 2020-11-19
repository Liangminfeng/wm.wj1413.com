<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2016 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：AboutControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：关于我们控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class AccountController extends CommonController {
    /*优惠券*/
    public function coupon() {
    

        $this->display('coupon.dwt');
    }
    /*财富金*/
    public function fortune() {
    
        $lxpoint = model('ClipsBase')->finduseraccount($_SESSION['user_id'],"lxpoint");
        $this->assign('lxpoint', $lxpoint);
        $this->display('fortune.dwt');
    }
    /*店铺收益记录表*/
    public function store_earnings()
    {
       
        $total =  model("ClipsBase")->sumtotalprofitlog($_SESSION['user_id'],12,"3,4",0);

        $totalwithdraw = model("ClipsBase")->newtotalwithrawThisMonth($_SESSION['user_id'],12,"3,4",0);
        $res =    model("ClipsBase")->userAccount($_SESSION['user_id']);
     

        $user_info = model('Users')->get_profile($_SESSION['user_id']);

        $locateurl = url('user/autonym');
        $this->assign("totalwithdraw",$totalwithdraw);
        $this->assign('user_info',$user_info);
        $this->assign('locateurl',$locateurl);
        $this->assign('total',$total);
        $this->assign('money',$res['bonus_vip']+$res['bonus_retail']);
        $this->display('store_earnings.dwt');


    }
    /*活动收益记录表*/
     public function activity_profit()
    {
        $total =  model("ClipsBase")->activitysumtotalprofitlog($_SESSION['user_id'],99,14,0);

        $totalwithdraw = model("ClipsBase")->newtotalwithrawThisMonth($_SESSION['user_id'],19,14,0);
        $res =    model("ClipsBase")->userAccount($_SESSION['user_id']);
     

        $user_info = model('Users')->get_profile($_SESSION['user_id']);

        $locateurl = url('user/autonym');
        $this->assign("totalwithdraw",$totalwithdraw);
        $this->assign('user_info',$user_info);
        $this->assign('locateurl',$locateurl);
        $this->assign('total',$total);
        $this->assign('money',$res['train_money']);
       
        $this->display('activity_profit.dwt');
    }
    /*经销商收益记录表*/
     public function dealer_earnings()
    {
       
        /*计算这个月已经转出了多少钱*/
        
        $total =  model("ClipsBase")->sumtotalprofitlog($_SESSION['user_id'],12,2,0);
        $res =model("ClipsBase")->userAccount($_SESSION['user_id']);
        $user_info = model('Users')->get_profile($_SESSION['user_id']);
        $locateurl = url('user/autonym');
        $this->assign('user_info',$user_info);
        $this->assign('locateurl',$locateurl);
        $this->assign('total',$total);
        $this->assign('money',$res['bonus_distributor']);
        $this->display('dealer_earnings.dwt');
    }
    public function ajax_store_earnings(){
        $user_id = $_SESSION['user_id'];
        $size = 10;
        $page = $_POST['page'];
        $type = "3,4";

        $list =  model("AccountLog")->get_newaccount_log($user_id,$size, $page,$type);

        $count = model('AccountLog')->get_account_count($user_id,$type);

         $totalpage = ceil($count/$size)+1;
       
        if(!empty($list)){
            $result['status'] = 200;
            $result['msg'] ="";
            $result['size'] =$size;
            $result['count'] =$count;
            $result['totalpage'] =$totalpage;
            $result['data'] = $list;
            die(json_encode($result));
        }else{

            $result['status'] = 400;
            $result['msg'] ="";
            $result['size'] =$size;
            $result['count'] =$count;
            $result['totalpage'] =$totalpage;
            $result['data'] = array();
            die(json_encode($result));

        }
    }
        public function ajax_dealer_earnings(){
        $user_id = $_SESSION['user_id'];
        $size = 10;
        $page = $_POST['page'];
        $type = "2";
        $list =  model("AccountLog")->get_account_log($user_id,$size, $page,$type);
        $count = model('AccountLog')->get_account_count($user_id,$type);

        $totalpage = ceil($count/$size)+1;

        if(!empty($list)){
            $result['status'] = 200;
            $result['msg'] ="";
            $result['size'] =$size;
            $result['count'] =$count;
            $result['totalpage'] =$totalpage;
            $result['data'] = $list;
            die(json_encode($result));
        }else{

            $result['status'] = 400;
            $result['msg'] ="";
            $result['size'] =$size;
            $result['count'] =$count;
            $result['totalpage'] =$totalpage;
            $result['data'] = array();
            die(json_encode($result));

        }
    }
    public function ajax_activity_earnings(){
        $user_id = $_SESSION['user_id'];
        $size = 10;
        $page = $_POST['page'];
        $type = "14";
        $list =  model("AccountLog")->activity_get_account_log($user_id,$size, $page,$type);
        $count = model('AccountLog')->activity_get_account_count($user_id,$type);

        $totalpage = ceil($count/$size)+1;

        if(!empty($list)){
            $result['status'] = 200;
            $result['msg'] ="";
            $result['size'] =$size;
            $result['count'] =$count;
            $result['totalpage'] =$totalpage;
            $result['data'] = $list;
            die(json_encode($result));
        }else{

            $result['status'] = 400;
            $result['msg'] ="";
            $result['size'] =$size;
            $result['count'] =$count;
            $result['totalpage'] =$totalpage;
            $result['data'] = array();
            die(json_encode($result));

        }
    }
    /*提现至零钱*/
    /*/* 帐号变动类型 */
// define('ACT_SAVING',                0);     // 帐户冲值
// define('ACT_DRAWING',               1);     // 帐户提款
// define('ACT_ADJUSTING',             2);     // 调节帐户
// define('ACT_BONUS_OUT',             3);         // 奖金转出到账户
// define('ACT_KD',                     8);        // 福豆类型
// define('ACT_TRANSFER',               10);       // 福豆类型
// define('ACT_OTHER',                99);     // 其他类型*/
// define('ACT_BONUS_IN',                12);     // 收益转入*/
// define('ACT_BONUS_OUT',                11);     // 收益转出*/
    public function ajax_user_income_account(){

        $user_id = $_SESSION['user_id'];
        $size = 10;
        $page = $_POST['page'];
        $type =11;
        $list =  model("AccountLog")->newget_account_log($user_id,$size, $page,$type);
        $count = model('AccountLog')->newget_account_count($user_id,$type);

        $totalpage = ceil($count/$size)+1;

        if(!empty($list)){
            $result['status'] = 200;
            $result['msg'] ="";
            $result['size'] =$size;
            $result['count'] =$count;
            $result['totalpage'] =$totalpage;
            $result['data'] = $list;
            die(json_encode($result));
        }else{

            $result['status'] = 400;
            $result['msg'] ="";
            $result['size'] =$size;
            $result['count'] =$count;
            $result['totalpage'] =$totalpage;
            $result['data'] = array();
            die(json_encode($result));

        }

    }
        public function trainwithDrowMoney(){
        
        $amount = $_POST['amount'] ;
        $type = $_POST['type'];//2:bonus_distributor 3:bonus_vip

         //$totalwithdraw = model("ClipsBase")->newtotalwithrawThisMonth($_SESSION['user_id'],12,"3,4",0);
        /*vip_manage_account*/
        if($amount<=0){
            
             $this->jserror("金额错误");
             
        }

        if($type==3){

            $month = model("ClipsBase")->newtotalwithrawThisMonth($_SESSION['user_id'],19,14,0);
            $N = $amount + $month;
            if($N>0){
             $tax = model("AccountLog")->getIncomeTax($N,$amount);
            }else{
                $tax = 0;
            }
        }else{
             $tax = 0;
        }
 
       $withdrawmoney = $amount;
      //1000 1000 30
        $amount = $amount - $tax ;//970

         $change_desc1 = '提取收益至钱包';
         $r1= model('ClipsBase')->new_log_account_change($_SESSION['user_id'],  -$withdrawmoney,$change_desc1,19, 14);
         $r2 = model('ClipsBase')->new_log_account_change($_SESSION['user_id'],  $withdrawmoney,$change_desc1,11, 11);

        if($r1){
                   $this->jssuccess("ok");
                 
            }else{
              $this->jserror("提现失败");
       }

    }
    public function withDrowMoney(){
        
        $amount = $_POST['amount'] ;
        $type = $_POST['type'];//2:bonus_distributor 3:bonus_vip

         //$totalwithdraw = model("ClipsBase")->newtotalwithrawThisMonth($_SESSION['user_id'],12,"3,4",0);
        /*vip_manage_account*/
        if($amount<=0){
            
             $this->jserror("金额错误");
             
        }

        if($type==3){

            $month = model("ClipsBase")->newtotalwithrawThisMonth($_SESSION['user_id'],12,"3,4",0);
            $N = $amount + $month;
            if($N>0){
             $tax = model("AccountLog")->getIncomeTax($N,$amount);
            }else{
                $tax = 0;
            }
        }else{
             $tax = 0;
        }
        
       $withdrawmoney = $amount;
      //1000 1000 30
        $amount = $amount - $tax ;//970

        /*新增减奖金记录*/
        switch ($type) {
            case '2':
                # code...
                /*查看提现经销商钱是否够提取*/
                $r = model('ClipsBase')->isEnough($amount,$_SESSION['user_id'],2);
                if($r){
                    /*就是转出*/
                     $change_desc1 = '提取收益至钱包';
                     $r1= model('ClipsBase')->new_log_account_change($_SESSION['user_id'],  -$withdrawmoney,$change_desc1,12, 2);

                        if($tax>0){

                                $change_desc11 = '提取收益至钱包扣税';
                                $r1= model('ClipsBase')->new_log_account_change($_SESSION['user_id'],  -$tax,$change_desc11,18, 2,0);  

                        }
                     

                     $change_desc12 = '提取金额';
                     $r1= model('ClipsBase')->new_log_account_change($_SESSION['user_id'],  -$withdrawmoney,$change_desc12,19, 2,0);
                /*新增加零钱记录*/
                    $change_desc2 =   '经销商收益提取';
                     $r2 = model('ClipsBase')->new_log_account_change($_SESSION['user_id'],  $amount,$change_desc2,11, 11);
                }else{
                    $this->jserror("经销商收益不够提取");
                }
                
                break;
            case '3':
                # code...
                /*查看店主奖金是否够提取*/
                $r = model('ClipsBase')->isEnough($amount,$_SESSION['user_id'],3);
                $bonus_vip = model('ClipsBase')->finduseraccount($_SESSION['user_id'],"bonus_vip");
                if($r){
                    $change_desc1 = '提取收益至钱包';
                    /**/
                    /*如果bonus_vip够扣的情况，不用扣零售收益*/
                   

                    if($bonus_vip>=$withdrawmoney){

                        $r1= model('ClipsBase')->new_log_account_change($_SESSION['user_id'],  -$withdrawmoney,$change_desc1,12, 3);

                    }else{
                        /*bonus_vip不够扣的情况*/
                        if($bonus_vip>0){
                           

                            $r1= model('ClipsBase')->new_log_account_change($_SESSION['user_id'],  -$bonus_vip,$change_desc1,12, 3); 
                        }
                       if(($withdrawmoney-$bonus_vip)>0){
                        
                          $r2= model('ClipsBase')->new_log_account_change($_SESSION['user_id'],  -($withdrawmoney-$bonus_vip),$change_desc1,12, 4);
                       }

                      
                    }
                    

                    

                     if($tax>0){

                        $change_desc11 = '提取收益至钱包扣税';
                        $r1= model('ClipsBase')->new_log_account_change($_SESSION['user_id'],  -$tax,$change_desc11,18, 3,0);

                     }
                     

                     $change_desc12 = '提取金额';
                     $r1= model('ClipsBase')->new_log_account_change($_SESSION['user_id'],  -$withdrawmoney,$change_desc12,19, 3,0);
                /*新增加零钱记录*/
                    $change_desc2 = '店铺收益提取';
                    $r2 = model('ClipsBase')->new_log_account_change($_SESSION['user_id'],  $amount,$change_desc2,11, 11);

                }else{
                    $this->jserror("提取金额不足");
                }
                 
                break;
            default:
                # code...
                break;
        }

        if($r1&&$r2){
                   $this->jssuccess("ok");
                 
            }else{
              $this->jserror("提现失败");
       }

    }

    public function bill_accounts(){
    	$total0 =model("ClipsBase")->selectTotalBonusLog($_SESSION['user_id'],0); 
    	$total1 =model("ClipsBase")->selectTotalBonusLog($_SESSION['user_id'],1); 
    	$total2 =model("ClipsBase")->selectTotalBonusLog($_SESSION['user_id'],2); 
    	$total3 =model("ClipsBase")->selectTotalBonusLog($_SESSION['user_id'],3); 

        $this->assign("total0",$total0?$total0:0);
         $this->assign("total1",$total1?$total1:0);
          $this->assign("total2",$total2?$total2:0);
           $this->assign("total3",$total3?$total3:0);
        $this->display('bill_accounts.dwt');
    }
    public function ajax_all_bill_accounts() {
        /* 账单结算 */
        $size = 5;
        $page = (int)($_POST['page']?$_POST['page']:1);

        $start = ($page-1)*$size;
        $type = (int)($_POST['type']?$_POST['type']:0);

    
        switch ($type) {
            case '1':
                # 待结算...
                    $list = model("ClipsBase")->selectBonusLog($_SESSION['user_id'],$start,$size,1);

                 $count =model("ClipsBase")->selectTotalBonusLog($_SESSION['user_id'],0); 
                foreach ($list as $key => $value) {
                        # code...
                      
                            $buyerinfo = model('Users')->get_users($value['buyer_id']);
                            $order_info = model('Order')->order_info($value['order_id']);
                            $listdata[$key]['buyname'] = $buyerinfo['nick_name'];
                            $listdata[$key]['created_at'] = $value['created_at'];
                            $listdata[$key]['user_name'] = substr($buyerinfo['user_name'], 0, 2).'****'.substr($buyerinfo['user_name'], 6);
                            $listdata[$key]['bonusstatus'] = "待结算";
                            $listdata[$key]['order_sn'] = $order_info['order_sn'];
                            $listdata[$key]['avatar'] = empty($buyerinfo['user_avatar'])?"/themes/yutui/images/idx_user.png":$buyerinfo['user_avatar'];

                                 if($order_info['shipping_status']==1){
                                      $listdata[$key]['order_status_name'] = "待收货";
                                    }elseif($order_info['shipping_status']==2){
                                        $listdata[$key]['order_status_name'] = "待结算";
                                    }elseif($order_info['shipping_status']==0){
                                        $listdata[$key]['order_status_name'] = "已付款";

                                    }
                             $ifzero = substr($value['money'],-1,2);
                            if($ifzero=='00'){
                                        $listdata[$key]['money'] = round($value['money'],2);
                            }else{
                                        $listdata[$key]['money'] = $value['money'];
                            }         
                            
                            if($order_info['shipping_status']==1){

                                              $listdata[$key]['order_status_name'] = "待收货";
                                            }elseif($order_info['shipping_status']==2){
                                                $listdata[$key]['order_status_name'] = "待结算";
                                            }elseif($order_info['shipping_status']==0){
                                                $listdata[$key]['order_status_name'] = "已付款";

                                            }
                                            if($value['status']==1){
                                                $listdata[$key]['order_status_name'] = "已结算";
                                            }                           //判断小数点后面两位是否是00

                      
                
                        
                        
                    }


                break;
            case '2':

                # 已结算...
                  $list = model("ClipsBase")->selectBonusLog($_SESSION['user_id'],$start,$size,2);

                  $count =model("ClipsBase")->selectTotalBonusLog($_SESSION['user_id'],0); 
                foreach ($list as $key => $value) {
                        # code...
                      
                            $buyerinfo = model('Users')->get_users($value['buyer_id']);
                            $order_info = model('Order')->order_info($value['order_id']);
                            $listdata[$key]['buyname'] = $buyerinfo['nick_name'];
                            $listdata[$key]['created_at'] = $value['created_at'];
                            $listdata[$key]['user_name'] = substr($buyerinfo['user_name'], 0, 2).'****'.substr($buyerinfo['user_name'], 6);
                            $listdata[$key]['order_sn'] = $order_info['order_sn'];
                            $listdata[$key]['bonusstatus'] = "已结算";
                            $listdata[$key]['avatar'] = empty($buyerinfo['user_avatar'])?"/themes/yutui/images/idx_user.png":$buyerinfo['user_avatar'];

                     
                               $ifzero = substr($value['money'],-1,2);
                            if($ifzero=='00'){
                                        $listdata[$key]['money'] = round($value['money'],2);
                            }else{
                                        $listdata[$key]['money'] = $value['money'];
                            }
                            if($order_info['shipping_status']==1){
                                      $listdata[$key]['order_status_name'] = "待收货";
                                    }elseif($order_info['shipping_status']==2){
                                        $listdata[$key]['order_status_name'] = "待结算";
                                    }elseif($order_info['shipping_status']==0){
                                        $listdata[$key]['order_status_name'] = "已付款";

                                    }
                        

                       
                        
                       
                        
                    }


                break;
            case '3':

                # 已退款...status =2 or status = 9 achievement_vip
                  $list = model("ClipsBase")->selectBonusLog($_SESSION['user_id'],$start,$size,3);
 
                    $count =model("ClipsBase")->selectTotalBonusLog($_SESSION['user_id'],0); 

                foreach ($list as $key => $value) {
                        # code...
                             $order_info = model('Order')->order_info($value['order_id']);

                            
                            
                        
                              
                                      $buyerinfo = model('Users')->get_users($value['buyer_id']);
                                        $listdata[$key]['order_sn'] = $order_info['order_sn'];
                                        $listdata[$key]['user_name'] = substr($buyerinfo['user_name'], 0, 2).'****'.substr($buyerinfo['user_name'], 6);
                                        $listdata[$key]['order_sn'] = $order_info['order_sn'];
                                        $listdata[$key]['bonusstatus'] = "订单取消";
                                        $listdata[$key]['buyname'] = $buyerinfo['nick_name'];   
                                        $listdata[$key]['created_at'] = $value['created_at'];   
                                        $listdata[$key]['money'] = $value['money'];
                                        $listdata[$key]['avatar'] = empty($buyerinfo['user_avatar'])?"/themes/yutui/images/idx_user.png":$buyerinfo['user_avatar'];
                                        
                                        $ifzero = substr($value['money'],-1,2);
                                        if($ifzero=='00'){
                                                    $listdata[$key]['money'] = round($value['money'],2);
                                        }else{
                                                    $listdata[$key]['money'] = $value['money'];
                                        }
                                     
                                    
                               
                            

                              
                            
                          
                        
                        
                    }


                break;
            default:
                # code...
                    $list = model("ClipsBase")->selectBonusLog($_SESSION['user_id'],$start,$size,0);


                    $count =model("ClipsBase")->selectTotalBonusLog($_SESSION['user_id'],0); 

                    foreach ($list as $key => $value) {
                    # code...

                        $order_info = model('Order')->order_info($value['order_id']);

                          
                                   $buyerinfo = model('Users')->get_users($value['buyer_id']);

                                    $order_info = model('Order')->order_info($value['order_id']);
                                    $listdata[$key]['buyname'] = $buyerinfo['nick_name'];
                                    $listdata[$key]['created_at'] = $value['created_at'];
                                    $listdata[$key]['order_sn'] = $order_info['order_sn'];
                                    $listdata[$key]['user_name'] = substr($buyerinfo['user_name'], 0, 2).'****'.substr($buyerinfo['user_name'], 6);
                                    $listdata[$key]['avatar'] = empty($buyerinfo['user_avatar'])?"/themes/yutui/images/idx_user.png":$buyerinfo['user_avatar']; 

                    
  
                                    $listdata[$key]['money'] = $value['money'];
                          
                                         if($order_info['shipping_status']==1){
                                              $listdata[$key]['order_status_name'] = "待收货";
                                            }elseif($order_info['shipping_status']==2){
                                                $listdata[$key]['order_status_name'] = "待结算";
                                            }elseif($order_info['shipping_status']==0){
                                                if($value['status']==2) {
                                                    $listdata[$key]['order_status_name'] = "订单取消";
                                                }else{
                                                    $listdata[$key]['order_status_name'] = "已付款";
                                                }

                                              

                                            

                                            }
                                            if($value['status']==1){
                                                $listdata[$key]['order_status_name'] = "已结算";
                                            }
                                    
                                     $ifzero = substr($value['money'],-1,2);
                                        if($ifzero=='00'){
                                                    $listdata[$key]['money'] = round($value['money'],2);
                                        }else{
                                                    $listdata[$key]['money'] = $value['money'];
                                        }
                           
                    
                }
                break;
        }
        
        $totalpage = ceil($count/$size)+1;
        if(empty($listdata)){
            $result['status'] = 422;
            $result['page'] =$page;
            $result['size'] =$size;
            $result['count'] =0;
            $result['totalpage'] =0;
            $result['data'] = array();
            die(json_encode($result));
        }else{
            $result['status'] = 200;
            $result['page'] =$page;
            $result['size'] =$size;
            $result['count'] =$count;
            $result['totalpage'] =$totalpage;
            $result['data'] = $listdata;
            die(json_encode($result));
        }
        
        
    }
    /*获取银行卡列表*/
    public function carry_accounts(){




        $cardlist = model("ClipsBase")->get_banklist($_SESSION['user_id']);

        $this->assign("cardlist",$cardlist);

        $this->display('carry_accounts.dwt');
    }
    /*增加银行卡*/
    public function add_bankcard(){

         $user_info = model('Users')->get_profile($_SESSION['user_id']);

        if($_POST){

            $bank['bank_no'] = $_POST['bank_no'];
            $bank['bank_realname'] = $_POST['bank_realname'];
            $bank['bank_name'] = $_POST['bank_name'];
            $bank['bank_sub_name'] = $_POST['bank_sub_name'];
        
             model("ClipsBase")->add_bank($bank);

            $this->redirect(url('carry_accounts', array('u'=>$_SESSION['user_id'])));
        }

        $this->assign('user_vip',$user_info['user_vip']);

        $this->assign('real_name',$user_info['real_name']);
        
        $this->display('add_bankcard.dwt');
    }

    public function ajax_del_bankcard(){

        if($_POST){
            $bank_id = $_POST['bank_id'];
              $r = model("ClipsBase")->del_bank($bank_id, $_SESSION['user_id']);
              if($r){

                     $result['status'] = 200;
                     $result['msg'] ="删除成功";
                     
                    die(json_encode($result));
              }else{    

                     $result['status'] = 422;
                     $result['msg'] ="删除";
                     
                    die(json_encode($result));

              }
        }

      
    }
    public function center_data() {
        /* 数据中心 */
       
         $user_info = model('Users')->get_profile($_SESSION['user_id']);
     
         $user_statistics = model("ClipsBase")->user_statistics($_SESSION['user_id']);
   

        

         $taocan_total = $user_statistics['sales_vip']+$user_statistics['sales_distributor'];

         $lingshou_total = $user_statistics['sales_retail'];

         $taocanbonus_total = $user_statistics['bonus_vip']+$user_statistics['bonus_distributor'];

         $lingshoubonus_total = $user_statistics['bonus_retail'];
         /*累计收益*/
          $bonus_total = $taocanbonus_total+$lingshoubonus_total ;
          /*累计销售额*/
          $sales_total = $lingshou_total+$taocan_total;
         $this->assign("vip_name",model("Users")->getvipname($_SESSION['user_id']));
         $this->assign("vip_top",model("Users")->getvipfengding($_SESSION['user_id']));

         $this->assign("sales_total",tripzero($sales_total));//累计销售额
         $this->assign("bonus_total",tripzero($bonus_total));//累计收益
         $this->assign('user_info',$user_info);
         $this->assign("taocan_total",tripzero($taocan_total));
         $this->assign("taocanbonus_total",$taocanbonus_total);
         $this->assign("lingshoubonus_total",$lingshoubonus_total);
         $this->assign("lingshou_total",tripzero($lingshou_total));

         $this->assign("user_info",$user_info);
         $this->display('center_data.dwt');
    }
    public function ajax_center_data(){
        $user_id =  $_SESSION['user_id'];
        $size =10;
        $page  = isset($_POST['page'])?$_POST['page']:1;

           $start = ($page-1)*$size;

        $list = model("ClipsBase")->user_statistics_month($user_id,$size,$start);
        foreach ($list as $key => $value) {
            # code...
            /*销售额*/
            $list[$key]['sales_vip'] = tripzero($list[$key]['sales_vip']+$list[$key]['sales_retail']);
            /*收益*/
            $list[$key]['bonus_vip'] = tripzero($list[$key]['bonus_vip']+$list[$key]['bonus_retail']);
        }
        $count = model("ClipsBase")->total_user_statistics_month($user_id);
    
        $totalpage = ceil($count['num']/$size)+1;
       
        if(!empty($list)){
            $result['status'] = 200;
            $result['msg'] ="";
            $result['size'] =$size;
            $result['count'] =$count;
            $result['totalpage'] =$totalpage;
            $result['data'] = $list;
            die(json_encode($result));
        }else{

            $result['status'] = 400;
            $result['msg'] ="";
            $result['size'] =$size;
            $result['count'] =$count;
            $result['totalpage'] =$totalpage;
            $result['data'] = array();
            die(json_encode($result));

        }
    }

    public function dealer_data() {
        /* 数据中心 */
        
         $user_info = model('Users')->get_profile($_SESSION['user_id']);

         $detaildata = array(
                                                
                                                "account" =>$user_info['vip_manage_account']
                                             );
                            
         $ret = model("Index")->postData($detaildata,"/api/user/info/detail");

         $user_statistics = model("ClipsBase")->user_statistics($_SESSION['user_id']);
         $sales_total = $user_statistics['sales_distributor'];

         $bonus_total = $user_statistics['bonus_distributor'];

         $listdata = model("ClipsBase")->user_statistics_month($_SESSION['user_id'],10,0);
         $this->assign('bonus_not_send',round($ret['bonus_not_send']*7,2));
         $this->assign('bonus_sent',round($ret['bonus_sent']*7,2));
         $this->assign("sales_total",$sales_total);//累计销售额
         $this->assign("bonus_total",$bonus_total);//累计收益
         $this->assign('user_info',$user_info);
         $this->assign('data',$ret);
         $this->assign('listdata',$listdata);
         if($ret['cx_expire']<time()){
            $this->assign('guoqi',1);
         }else{
            $this->assign('guoqi',0);
         }
         $this->assign('chongxiaoqixian',date("Y/m/d",$ret['cx_expire']));

         $this->display('dealer_data.dwt');
    }
    public function ajax_dealer_data(){
   
            $user_info = model('Users')->get_profile($_SESSION['user_id']);
            $size =10;
            $page  = isset($_POST['page'])?$_POST['page']:1;

            $start = ($page-1)*$size;
            $detaildata = array(
                                                
                                                "account" =>$user_info['vip_manage_account'],
                                                // "start" =>date("Y-m-d",time()),
                                                // "end" =>date("Y-m-d",time()),
                                                "page" =>$page ,
                                                "page_size" =>10

                                             );
                            
        $list = model("Index")->postData($detaildata,"/api/user/bonus/dayreport");

        // $list = model("ClipsBase")->user_statistics_month($user_id,$size,$start);

        $count = $list['total'];
    
        $totalpage = ceil($count/$size)+1;
       
        if(!empty($list)){
            $result['status'] = 200;
            $result['msg'] ="";
            $result['size'] =$size;
            $result['count'] =$count;
            $result['totalpage'] =$totalpage;
            $result['data'] = $list['data'];
            die(json_encode($result));
        }else{

            $result['status'] = 400;
            $result['msg'] ="";
            $result['size'] =$size;
            $result['count'] =$count;
            $result['totalpage'] =$totalpage;
            $result['data'] = array();
            die(json_encode($result));

        }
    }
    /*店主提取收益税金配置*/
    public function ajax_getIncomeTax(){

        $amount = $_POST['amount'];
        
        $withdrawmoney = $_POST['withdrawmoney'];

        /*vip_manage_account*/
        $N = $amount;
        if($N<=5000){

            $tax = 0 ;

        }elseif ($N>5000&&$N<=15000) {
            # code...
            $tax = 0.03 * $withdrawmoney ;

        }elseif ($N>15000&&$N<=30000) {
            # code...
            $tax = 0.05 * $withdrawmoney ;

        }else{

            $tax = 0.08 * $withdrawmoney ;

        }
            $result['status'] = 200;
            $result['msg'] ="";
            $result['data'] = $tax;
            $result['withdrawmoney'] =$withdrawmoney;
            die(json_encode($result));
    }
    public function getuserbyvipmanageaccount(){

        $vip_manage_account = $_POST['vip_manage_account'];

        $account = $this->model->table('users')->where(array('vip_manage_account' => $vip_manage_account))->find();

        if($account['user_vip']){
            $result['status'] = 200;
            $result['msg'] ="";
            $result['data'] = $account;
            die(json_encode($result));
        }else{
            $result['status'] = 400;
            $result['msg'] ="邀请人不存在";
            $result['data'] = array();
            die(json_encode($result));
        }
    }

    public function setUserParent(){

            $parent_id = $_POST['parent_id'] ;
            if($_SESSION['user_id']){
                $r = model("Users")->changeParent($_SESSION['user_id'],$parent_id);
                /*更新推荐链路关系*/
                if($r){

                    $r1 = model("Users")->new_updateuser_reposition($_SESSION['user_id'],$parent_id);
                          model("Users")->updateAllChildReposition($_SESSION['user_id'],'',1);
                }
            }
      
            /*更改上级*/
            

            if($r1){
                $data =  model("Users")->get_users($parent_id);
                $result['status'] = 200;
                $result['msg'] ="更改推荐人成功";
                $result['data'] = $data;
                die(json_encode($result));
            }else{
                 $result['status'] = 400;
                $result['msg'] ="更改推荐人失败";
                 $result['data'] = array();
                die(json_encode($result));
            }

           





    }
    public function wealth_transfer(){
         $lxpoint = model('ClipsBase')->finduseraccount($_SESSION['user_id'],"lxpoint");
        $this->assign('lxpoint', $lxpoint);
     /* 财富金转账done */
     $this->display('wealth_transfer.dwt');
    }
    public function wealth_transfer_done(){

    if($_POST['lxpoint']&&$_POST['receive_user_id']){

            $receiveuserinfo =  model('Users')->getusersinfo($_POST['receive_user_id']);
            $senduserinfo =  model('Users')->getusersinfo($_SESSION['user_id']);
            //$r1 = model('ClipsBase')->log_account_change($_SESSION['user_id'], -$_POST['money'], 0, 0, 0, "余额转账", ACT_TRANSFER);
            model('ClipsBase')->new_log_account_change($_SESSION['user_id'],-$_POST['lxpoint'],"财富金转账至用户".$receiveuserinfo['user_name'],ACT_TRANSFER,12);
         
           // $r2 = model('ClipsBase')->log_account_change($_POST['receive_user_id'], $_POST['money'], 0, 0, 0, "收到余额转账", ACT_TRANSFER);
             
            model('ClipsBase')->new_log_account_change($_POST['receive_user_id'],$_POST['lxpoint'],"收到用户".$senduserinfo['user_name']."财富金转账",ACT_TRANSFER,12);

        }
         $this->assign('lxpoint',number_format($_POST['lxpoint'],2));
         $receive_info = model('Users')->get_profile($_POST['receive_user_id']);
         
         $this->assign('receive_user',$receive_info['nick_name']?$receive_info['nick_name']:$receive_info['user_name']);

         $this->assign('order_submit_back', sprintf(L('order_submit_back'), L('back_home'), L('goto_user_center')));
         $this->display('wealth_transfer_done.dwt');

    }
    public function wealth_detailed() {
        /* 财富金明细 */

           // 获取剩余余额

         $wealth_account = model('ClipsBase')->finduseraccount($_SESSION['user_id'],"lxpoint");

        if (empty($wealth_account)) {
            $wealth_account = 0;
        }

        $size = I('request.size', 8);
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $where = 'user_id = ' . $_SESSION['user_id'];
        $count = $this->model->table('account_log')
            ->field('COUNT(*)')
            ->where($where)
            ->getOne();

        $this->pageLimit(url('account/wealth_detailed'), $size);
        $this->assign('pager', $this->pageShow($count));
        $account_detail = model('Users')->get_new_account_detail($_SESSION['user_id'], $size, ($page - 1) * $size,12);

        $this->assign('headerContent', L('user_lxpoint_detail'));
        
        $this->assign('title', L('label_user_lxpoint'));
        $this->assign('wealth_account', price_format($wealth_account, false));
        $this->assign('account_log', $account_detail);
        $this->display('wealth_detailed.dwt');

    }
    public function ajax_wealth_detailed(){

        $size = I('request.size', 8);
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $where = 'user_id = ' .$_SESSION['user_id']." and account_type=12";
        $count = $this->model->table('account_log')
            ->field('COUNT(*)')
            ->where($where)
            ->getOne();
        
        $account_detail = model('Users')->get_new_account_detail($_SESSION['user_id'], $size, ($page - 1) * $size,12);
        $countpage = ceil($count/$size)+1;
        $result['totalpage'] = $countpage;
        $result['list'] = $account_detail;
        $result['page'] = $page;
         die(json_encode($result));
        exit();
    }
    public function validateuserlxpoint(){
        $user = model("account")->find([
            "user_id" => $_POST['user_id']
        ], "lxpoint");

        if($user['lxpoint']<$_POST['lxpoint']){
            $result['status'] = 303;
            $result['msg'] = "财富金金额不够";
        }else{
            $result['status'] = 200;
            $result['msg'] = "财富金金额够";
        }
    }
    public function validateuser(){

         $user = model("Users")->find([
            "user_name" => $_POST['user_name']
        ], "user_name,nick_name,user_id,user_avatar");

         if($user){
            $data['user_name'] = $user['user_name'];
            $data['nick_name'] = empty($user['nick_name'])?$user['user_name']:$user['nick_name'];
            $data['user_id'] = $user['user_id'];
            $data['user_avatar'] = empty($user['user_avatar'])?"/themes/yutui/images/idx_user.png":$user['user_avatar'];
            $result['status'] = 200;
            $result['data'] = $data;
         }else{
            $result['status'] = 303;
            $result['data'] = "暂无数据";
         }
         echo json_encode($result);
            exit();
      
       
      
    }
    //迎新奖
    public function pair_ornot() {

             if(empty($_SESSION['user_id'])){

            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect(url('user/register', array(
                'back_act' => urlencode($back_act)
            )));
            exit();

            }

            $user_info = model('Users')->get_profile($_SESSION['user_id']);
            $today =time();
          
            $endtime = $user_info['reg_time']+90*24*3600;

            $lefttime = ($endtime-$today)*1000;
            /*如果lefttime<0代表超过90天*/
 
            $returndata = array(
                                                
                                                "account" =>$user_info['vip_manage_account'],
                                  
                                             
                             );
                                        
            $ret = model("Index")->postData($returndata,"/api/user/bonus/cp");

           //$ret['list']代表的是迎新推荐列表
           
            if($ret['my_achievement']){
                $my_achievement = $ret['my_achievement'];
            }else{
                $my_achievement = '0.00';
            }

            if($ret['list']){
                foreach ($ret['list'] as $key => $value) {
                # code...

                $childuserinfo = model('Users')->getnewusersbyvipaccount($value['child_account']);
                $userinfo = model('Users')->get_profile($childuserinfo['user_id']);
                $yinxintuijian[$key]['user_name'] =  $userinfo['nick_name']?$userinfo['nick_name']:$userinfo['user_name'];
                $yinxintuijian[$key]['mobile_phone'] = substr($userinfo['mobile_phone'], 0, 2).'****'.substr($userinfo['mobile_phone'], 6);
                $yinxintuijian[$key]['user_avatar'] = empty($userinfo['user_avatar'])?"/themes/yutui/images/idx_user.png":$userinfo['user_avatar'];
                $yinxintuijian[$key]['achievement'] = $value['achievement'];

                $yinxintuijian[$key]['status'] = $value['status'];
                $yinxintuijian[$key]['order_time'] = $value['created_at'];
                if($value['status']==1){
                    $submain[$key]['user_avatar'] = empty($userinfo['user_avatar'])?"/themes/yutui/images/idx_user.png":$userinfo['user_avatar'];
                    $submain[$key]['user_name'] = $userinfo['nick_name']?$userinfo['nick_name']:$userinfo['user_name'];
                }


            }
            }
        
            if($ret['bonus']){
                $jiesuan = $ret['bonus']['achievement'] ;
                $maininfo  = model('Users')->getnewusersbyvipaccount($user_info['vip_manage_account']);
                $mainaccountinfo = model('Users')->get_profile($maininfo['user_id']);
                $main['user_name'] = $mainaccountinfo['nick_name']?$mainaccountinfo['nick_name']:$mainaccountinfo['user_name'] ;
                $main['user_avatar'] = empty($mainaccountinfo['user_avatar'])?"/themes/yutui/images/idx_user.png":$mainaccountinfo['user_avatar'];
                $this->assign('main',$main);

                $resultsubmain = array_values($submain);

                $this->assign('submain_0',$resultsubmain['0']);
                if($resultsubmain['1']){

                    $this->assign('submain_1',$resultsubmain['1']);
                }else{
                   
                    $this->assign('pair_ok',0);
                }
               
                if($ret['bonus']['status']==1){
                    /*配对成功*/
                     $this->assign("settle_time",date("Y-m-d",$ret['bonus']['settle_time']));
                     $this->assign('pair_ok',1);
                }

                
                 
            }else{

                $mainaccountinfo = model('Users')->get_profile($_SESSION['user_id']);
                $main['user_name'] = $mainaccountinfo['nick_name']?$mainaccountinfo['nick_name']:$mainaccountinfo['user_name'] ;
                $main['user_avatar'] = empty($mainaccountinfo['user_avatar'])?"/themes/yutui/images/idx_user.png":$mainaccountinfo['user_avatar'];
                $this->assign('main',$main);
                 $this->assign('pair_ok',0);
                $jiesuan = "0.00";
            }
            
                
            $userInfo = model("Users")->get_users($_SESSION["user_id"]);
            $this->assign("share_link",__HOST__.'/index.php?m=default&c=user&a=register&u='.$userInfo["user_id"]);
            $this->assign("user_info", $userInfo);
            $this->assign('lefttime',$lefttime);

            $this->assign('bonuslist',$ret['bonus']);
         
            $this->assign('jiesuan',$jiesuan);
            $this->assign('my_achievement',$my_achievement);

            $this->assign('yinxintuijian',$yinxintuijian);

            $this->display('pair_ornot.dwt');

    }


}

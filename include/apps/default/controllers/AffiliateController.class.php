<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：TrainControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：文章控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class AffiliateController extends CommonController {


    public function __construct() {
      

        parent::__construct();
        // 屬性賦值
        $this->user_id = $_SESSION['user_id'];
        $this->action = ACTION_NAME;
 
        $this->assign('u',$this->user_id);
        
     
        
        $this->check_login();

        $info = model('ClipsBase')->get_user_default($this->user_id);
        
        $shortLink = $this->getShortUrl(__URL__ . "/index.php?m=default&c=user&u=" . $_SESSION['user_id']);
        $this->assign('user_rank',$_SESSION['user_rank']);
        $this->assign('share_link', $shortLink); //
        $this->assign("back_url",urlencode(__HOST__ . $_SERVER['REQUEST_URI']));
        $this->assign('info', $info);
        


    }

    public function index(){
        $update = self::$cache->getValue("upvip{$this->user_id}");
        if(!empty($update)){
            $tmp  = model("Users")->get_users($this->user_id);
            $_SESSION["user_vip"] = $tmp["user_vip"];
            self::$cache->delValue("upvip{$this->user_id}");
            unset($tmp);
        }
      
        // $result =model("Users")->vipCommunity(1000);
        // var_dump($result);exit;
       // $r = model("Users")->updateAllChildReposition(2398);

        if($_SESSION['user_vip']=='0'){
                $url = url('user/vipmarket');
                
                ecs_header("Location: $url\n");
        }

        $auid = $this->user_id;
        
        $user_list['user_list'] = array();
       
        $up_uid = "'$auid'";
        $all_count = 0;
        for ($i = 1; $i <= 1; $i ++) {
            $count = 0;
            if ($up_uid) {
                $sql = "SELECT user_id FROM " . $this->model->pre . "users WHERE parent_id IN($up_uid)";
                $query = $this->model->query($sql);
                
                $up_uid = '';
                foreach ($query as $k => $v) {
                    $up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
                    $count ++;
                }
            }
            $all_count += $count;
            
            if ($count) {
                $sql = "SELECT user_id, user_name, '$i' AS level, email, is_validated,  user_avatar, rank_points, reg_time " . " FROM " . $this->model->pre . "users WHERE user_id IN($up_uid) and status<>9" . " ORDER by  reg_time desc";
                $user_list['user_list'] = array_merge($user_list['user_list'], $this->model->query($sql));
            }
        }

        //热卖商品
        $share = unserialize(C('affiliate'));
        
        $goodsid = I('request.goodsid', 0);
        
        if (empty($goodsid)) {
            $page = I('request.page', 1);
            $size = I(C('page_size'), 10);
            empty($share) && $share = array();
            if (empty($share['config']['separate_by'])) {
                
                // 推薦註冊分成
                $affdb = array();
                $num = count($share['item']);
                $up_uid = "'$this->user_id'";
                $all_uid = "'$this->user_id'";

                for ($i = 1; $i <= $num; $i ++) {
                    $count = 0;
                    if ($up_uid) {
                        $where = 'parent_id IN(' . $up_uid . ')';
                        $rs = $this->model->table('users')
                            ->field('user_id')
                            ->where($where)
                            ->select();
                        if (empty($rs)) {
                            $rs = array();
                        }
                        $up_uid = '';
                        foreach ($rs as $k => $v) {
                            $up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
                            if ($i < $num) {
                                $all_uid .= ", '$v[user_id]'";
                            }
                            $count ++;
                        }
                    }
                    $affdb[$i]['num'] = $count;
                    $affdb[$i]['point'] = $share['item'][$i - 1]['level_point'];
                    $affdb[$i]['money'] = $share['item'][$i - 1]['level_money'];
                    $this->assign('affdb', $affdb);
                }
                
                $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
                
                $sql = "SELECT o.*, a.log_id,a.time, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";

            } else {
                
                // 推薦訂單分成
                $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
                
                $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
            }
         }

   
        // 未处理
           
        $surplus_amount = model('ClipsBase')->get_user_surplus($this->user_id);
        //$frozen_amount = model('ClipsBase')->get_user_frozen($this->user_id);
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }
        // if (empty($frozen_amount)) {
        //     $frozen_amount = 0;
        // }
        $sql3 = "select vip_validated_firsttime,vip_validated from ".$this->model->pre."users where user_id = ".$this->user_id;
       
        $res3 = $this->model->query($sql3);
         
         $end_vip_time = $res3['0']['vip_validated_firsttime']+$res3['0']['vip_validated']*366*24*60*60;
        //var_dump(date("Y-m-d ", $res3['0']['vip_validated_firsttime']));

         $end_vip_time = date("Y-m-d", strtotime("+".$res3['0']['vip_validated']." year", strtotime(date("Y-m-d ",$res3['0']['vip_validated_firsttime']))));


         
         /*该用户已经在本商城消费的店铺金额*/
         $order_total = model('ClipsBase')->get_user_order_amount($this->user_id);
         $info = model('ClipsBase')->get_user_default($this->user_id);

         
   
        $children_users =$this->affiliate_partner_new();
        $unpaymoney = model("ClipsBase")->sumTotalBonusLog($_SESSION['user_id'],1);
        $user_info = model('Users')->get_users($_SESSION['user_id']);
        if(!empty($user_info)){
            if(!empty($user_info["xp_detail"])){
                $user_info["xp_detail"] = unserialize($user_info["xp_detail"]);
            }else{
                $user_info["xp_detail"] = [0,0];
            }
        }
        
        $time = date("Y-m",time());

        $sql31 = "select sum(`bonus_vip`+`bonus_distributor`+`bonus_retail`) as total from ".$this->model->pre."user_statistics_month where user_id = ".$this->user_id ."  and date='".$time."'";

        $res31 = $this->model->query($sql31);


        $sql32 = "select sum(`sales_vip`+`sales_distributor`+`sales_retail`) as total from ".$this->model->pre."user_statistics_month where user_id = ".$this->user_id ."  and date='".$time."'";
  
        $res32 = $this->model->query($sql32);

        $this->assign('children_userslist',array_slice($children_users,0,5));

        $this->assign('unpaymoney',$unpaymoney);


         $this->assign('resource',$info['resource']);
         $this->assign('userprofit',$ret1['data']);
         $this->assign('user_rank',$_SESSION['user_vip']);
         $this->assign('await_money', $ret1['status']=='404'?0:($ret1['data']?$ret1['data']['await_money']:0));
          $newuser_info = model('Users')->get_profile($_SESSION['user_id']);

         $returndata = array(
                                                
                                                "account" =>$newuser_info['vip_manage_account'],
                                  
                                             
                             );
                                        
        $ret = model("Index")->postData($returndata,"/api/user/bonus/cp");
  
        if($ret['list']){

            foreach ($ret['list'] as $key => $value) {
            if($value['status'] ==1){
                    $submain[$key]['user_avatar'] = empty($newuser_info['user_avatar'])?"/themes/yutui/images/idx_user.png":$newuser_info['user_avatar'];
                    $submain[$key]['user_name'] = $newuser_info['user_name'];
            }

            }
        }
        
        
         $today =time();
            
         $endtime = $newuser_info['reg_time']+90*24*3600;

         $lefttime = ($endtime-$today)*1000;
     
         if($ret['bonus']['status']==1){
            $this->assign('lefttime',0);
        }else{
            if($lefttime>0){
                $this->assign('lefttime',$lefttime);
            }else{
                $this->assign('lefttime',0);
            }
            
        }
         

         $ifzero = substr($res31[0]['total'],-1,2);
                                        if($ifzero=='00'){

                                                    $res31[0]['total'] = round($res31[0]['total'],2);
                                        
                                        }else{
                                                    $res31[0]['total'] = $res31[0]['total'];
                                        }

         $this->assign('cumsum_money',$res31[0]['total']);
         $ifzero1 = substr($res32[0]['total'],-1,2);
                                        if($ifzero1=='00'){
                                                    $res32[0]['total'] = round($res32[0]['total'],2);
                                        }else{
                                                    $res32[0]['total'] = $res32[0]['total'];
                                        }
         $this->assign('userprofit_money',$res32[0]['total']);
         $this->assign('aimamount',$aimamount);

         $this->assign('text',$text);
         $this->assign('order_total',$order_total);
   
         $this->assign('totalpeiduika',empty($ret['data'])||$ret['status']==422?0:$ret['data']['total']);
 
         $this->assign('user_info',$user_info);

         $this->assign('end_vip_time',$end_vip_time);
         $total_surplus = $surplus_amount + $frozen_amount;
         $this->assign('total_surplus', $total_surplus);
         //余额
        
         $this->assign('resultchengzhangzhi',model("Users")->lookChengzhangzhi($_SESSION['user_id']));
         $this->assign('user_id',$this->user_id);

         $hot_goods = model('Goods')->get_hot_goods();
         $this->assign('hot_goods', $hot_goods);
         $this->assign('children_users',count($user_list['user_list']));
         $this->display("affiliate_index.dwt");
    }

    
    /**
     * 未登錄驗證
     */
    private function check_login() {

        // 不需要登錄的操作或自己驗證是否登錄（如ajax處理）的方法
        $without = array(
            'VIPprivilege',
            'jiugong',
            'zhuanpan'
        );
        // 未登錄處理

        if (empty($_SESSION['user_id']) && !in_array($this->action, $without)) {
            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect(url('user/register'));
            exit();
        }
        
        // 已經登錄，不能訪問的方法
        $deny = array(
            'login',
            'register'
        );
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0 && in_array($this->action, $deny)) {
            $this->redirect(url('index/index'));
            exit();
        }
    }
         function separate_dill($oid)
    {
        $affiliate = unserialize(C('affiliate'));
        empty($affiliate) && $affiliate = array();
        
        $separate_by = $affiliate['config']['separate_by'];
        $is_dependent = $affiliate['config']['is_dependent'];
        $row = model("Users")->row("SELECT o.order_sn, o.is_separate, (o.goods_amount - o.discount) AS goods_amount, o.user_id FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " WHERE order_id = '$oid'");
        
        $order_sn = $row['order_sn'];
        
        $separate = array();
        if (empty($row['is_separate'])) {
            $affiliate['config']['level_point_all'] = (float) $affiliate['config']['level_point_all'];
            $affiliate['config']['level_money_all'] = (float) $affiliate['config']['level_money_all'];
            if ($affiliate['config']['level_point_all']) {
                $affiliate['config']['level_point_all'] /= 100;
            }
            if ($affiliate['config']['level_money_all']) {
                $affiliate['config']['level_money_all'] /= 100;
            }
            $goods_list = $this->model->query("SELECT goods_id,goods_number FROM " . $this->model->pre . "order_goods where order_id = $oid");
            
            foreach ($goods_list as $key => $val) {
                $money = round($affiliate['config']['level_money_all'] * $row['goods_amount'], 2);
                if($is_dependent){
                    /*独立现金分成，金额等于各个商品设置的佣金之和*/
                     $goods_list1 = $this->model->query("SELECT goods_id,goods_number FROM " . $this->model->pre . "order_goods where order_id = $oid");
                 
                     foreach ($goods_list1 as $key1 => $value1) {
                         # code...
                       
                            $goodsinfo = $this->model->query("SELECT indepent_bonus FROM " . $this->model->pre . "goods WHERE goods_id = '{$value1['goods_id']}'");
                      
                          
                        
                            
                            $totalmoney += $value1['goods_number']*$goodsinfo['0']['indepent_bonus'];

                     }
                    
                    $money = $totalmoney;
                }else{
                    $money = round($affiliate['config']['level_money_all'] * $row['order_amount'],2);
                }
                $point = $money;
                
                if (empty($separate_by)) {
                    // 推荐注册分成
                    $num = count($affiliate['item']);
                    for ($i = 0; $i < $num; $i ++) {
                        $affiliate['item'][$i]['level_point'] = (float) $affiliate['item'][$i]['level_point'];
                        $affiliate['item'][$i]['level_money'] = (float) $affiliate['item'][$i]['level_money'];
                        if ($affiliate['item'][$i]['level_point']) {
                            $affiliate['item'][$i]['level_point'] /= 100;
                        }
                        if ($affiliate['item'][$i]['level_money']) {
                            $affiliate['item'][$i]['level_money'] /= 100;
                        }
                        $setmoney = round($money * $affiliate['item'][$i]['level_money'], 2);
                        $setpoint = round($point * $affiliate['item'][$i]['level_point'], 0);
                        $row = model("Users")->row("SELECT o.parent_id as user_id,u.user_name FROM " . $this->model->pre . "users o" . " LEFT JOIN " . $this->model->pre . "users u ON o.parent_id = u.user_id" . " WHERE o.user_id = '$row[user_id]'");
                        $up_uid = $row['user_id'];
                        if (empty($up_uid) || empty($row['user_name'])) {
                            break;
                        } else {
                            $info = sprintf($_LANG['separate_info'], $order_sn, $setmoney, $setpoint);
                            if($up_uid==$_SESSION['user_id']){
                                $separate = [
                                "order_id" => $oid,
                                "user_id" => $up_uid,
                                "user_name" => $row['user_name'],
                                "money" => number_format($setmoney,2),
                                "point" => $setpoint,
                                "separate_by" => $separate_by
                            ];
                            }
                            
                        }
                    }
                } else {

                    // 推荐订单分成
                    $row = model("Users")->row("SELECT o.parent_id, u.user_name FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.parent_id = u.user_id" . " WHERE o.order_id = '$oid'");
                    $up_uid = $row['parent_id'];
                    if (! empty($up_uid) && $up_uid > 0) {
                        $info = sprintf($_LANG['separate_info'], $order_sn, $money, $point);
                        if($up_uid==$_SESSION['user_id']){
                               $separate = [
                            "order_id" => $oid,
                            "user_id" => $up_uid,
                            "user_name" => $row['user_name'],
                            "money" => $money,
                            "point" => $point,
                            "separate_by" => $separate_by
                        ];
                            }
                        
                    }
                }
            }
        }
        return $separate;
    }
   

    /*组团中心*/
    function groupcenter()
    {   
        
        $data2 = array(
                    
                        "mid" =>$this->user_id,
                    
                        "token" =>encryptapitoken()
                    );
       
     
       $ret2 =  model("Index")->postData($data2,"/api/user/info",5);
       
        $this->assign('weight',$ret2['data']['weight']);
        $info = model('ClipsBase')->get_user_default($this->user_id);

         switch ($info['user_vip']) {
            case '1':
                # code...
                $share_link = __URL__ . "/index.php?c=topic&topic_id=17&u=".$this->user_id;
        $this->assign('share_link',$share_link);
         $topic = $this->model->table('topic')->where('topic_id =17')->find();
        $topic['topic_img'] = get_image_topic($topic['topic_img'], true);
     
        $this->assign('share_title', $topic['title']);//
        $this->assign('share_description', $topic['description']);//
        $this->assign('share_pic',  $topic['topic_img']);
                break;
            case '2':
                # code...
                $share_link = __URL__ . "/index.php?c=topic&topic_id=18&u=".$this->user_id;
        $this->assign('share_link',$share_link);
        $topic = $this->model->table('topic')->where('topic_id =18')->find();
        $topic['topic_img'] = get_image_topic($topic['topic_img'], true);
     
        $this->assign('share_title', $topic['title']);//
        $this->assign('share_description', $topic['description']);//
        $this->assign('share_pic',  $topic['topic_img']);
                break;
            case '3':
                # code...
                $share_link = __URL__ . "/index.php?c=topic&topic_id=19&u=".$this->user_id;
        $this->assign('share_link',$share_link);
        $topic = $this->model->table('topic')->where('topic_id =19')->find();
        $topic['topic_img'] = get_image_topic($topic['topic_img'], true);
     
        $this->assign('share_title', $topic['title']);//
        $this->assign('share_description', $topic['description']);//
        $this->assign('share_pic',  $topic['topic_img']);
                break;
            case '4':
                $share_link =__URL__ . "/index.php?c=topic&topic_id=20&u=".$this->user_id;
        $this->assign('share_link',$share_link);
        $topic = $this->model->table('topic')->where('topic_id =20')->find();
        $topic['topic_img'] = get_image_topic($topic['topic_img'], true);
     
        $this->assign('share_title', $topic['title']);//
        $this->assign('share_description', $topic['description']);//
        $this->assign('share_pic',  $topic['topic_img']);
                break;

            default:
                # code...
                break;
        }

         $this->display("groupcenter.dwt");
    }



    private function getLevelName($level){
        $name = "";
        switch ($level) {
            case 1:
            $name = "白银";
                # code...
                break;
            
            case 2:
            $name = "黄金";
                # code...
                break;
            
            case 3:
            $name = "钻石";
                # code...
                break;
            
            case 4:
            $name = "至尊";
                # code...
                break;
            
            default:
                # code...
                break;
        }
        return $name;

    }    
    function ajax_group_center()
    {

        // $data = array(
                    

        //                 "mid" =>$_SESSION['user_id'],


        //                 "page"=>$_GET['page']?$_GET['page']:1,
        //                 "num"=>5,
        //                 "type" =>$_GET['type']?$_GET['type']:1,
        //                 "token" =>encryptapitoken()
        //             );
      
        //  $ret =  post_log($data,API_URL."/api/join/card",5);
        
         $num = count($ret['data']['data']);
          if(!empty($ret['data']['data'])){
           $html = "";
            foreach ($ret['data']['data'] as $key => $value) {

           # code...
             # <!-- 购买配对卡 -->
            $value['yujimoney'] = $value['money']*0.66;
            $value['numpeople'] = $value['member_right_status']+$value['member_left_status']+$value['member_status'];
              $left = 3- $value['numpeople'];
           
            if($value['numpeople']==3){
                $html .='<div class="lkd_new_card2">'; 
            $html .='<div class="lkd_card">';
            $html .='<div class="lkd_new_top">';    
            $html .='<div class="lkd_top_left color_active">'.$this->getLevelName($value["level"]).'套餐组团卡</div>' ;     
            $html .='<div class="lkd_top_right">';       
            $html .= ' <a href="javascript:;" class="color1_active"><span class="lkd_left_quan"><img src="themes/yutui/images/handshake.png" alt=""></span></a></div></div>';         
            $html .= '<div class="pair"><div class="pair_img">'.'<img style="border-radius:50%" src="'.$value['mimg'].'" alt=""><span><img src="themes/yutui/images/doyen.png"></span></div>';
            $html .='<div class="pair_bonus"><p class="bonus_yuji">预计获得组团奖金：</p>';              
            $html .='<h3 class="bonus">'. $value['yujimoney'].' <span>元</span><span class="bonus_add">+</span><span class="lkd_right_count">'.$value['weight'].'</span><span>权重</span></h3></div></div>';    
            $html .='<div class="pair_center"><div class="pair_gap"></div>';
            $html .='<div class="pair_win"><div class="pair_person">';
            $html .='<div class="pair_man">';                     
            $html .='<img src="'.$value['limg'].'" alt=""></div>';                        
            $html .='<div class="pair_wom">';                     
            $html .='<img src="'.$value['rimg'].'" alt=""></div>';                            
            $html .='</div>'          ;             
            $html .='<div class="handshake" style="background:#BDBDBD;">' ;                       
            $html .=' <span class="ok_or_not" >组团成功</span>';                    
            $html .='</div></div></div></div></div>';  
        }else{
          
            $html .='<div class="lkd_new_card3">'; 
            $html .='<div class="lkd_card">';
            $html .='<div class="lkd_new_top bgc_active">';    
            $html .='<div class="lkd_top_left color_active">'.$this->getLevelName($value["level"]).'套餐组团卡</div>' ;     
            $html .='<div class="lkd_top_right">';       
            $html .= ' <a href="javascript:;" class="color1_active"><span class="lkd_left_quan"><img src="themes/yutui/images/timer.png" alt=""></span></a></div></div>' ;  
            if($value['member_status']){

                 $html .= '<div class="pair"><div class="pair_img">'.'<img style="border-radius:50%"  src="'.$value['mimg'].'" alt=""><span><img src="themes/yutui/images/doyen.png"></span></div>';
             }else{
                switch ($value['level']) {
                    case '1':
                        # code...
                        $html .= '<div class="pair"><div class="pair_img">'.'<a href="/index.php?c=topic&topic_id=17" ><img src="themes/yutui/images/once_buy.png" alt=""><span><img src="themes/yutui/images/doyen.png"></span></a></div>';
                        break;
                    case '2':
                        # code...
                        $html .= '<div class="pair"><div class="pair_img">'.'<a href="/index.php?c=topic&topic_id=18" ><img src="themes/yutui/images/once_buy.png" alt=""><span><img src="themes/yutui/images/doyen.png"></span></a></div>';
                        break;
                    case '3':
                        # code...
                        $html .= '<div class="pair"><div class="pair_img">'.'<a href="/index.php?c=topic&topic_id=19" ><img src="themes/yutui/images/once_buy.png" alt=""><span><img src="themes/yutui/images/doyen.png"></span></a></div>';
                        break;
                    case '4':
                        # code...
                        $html .= '<div class="pair"><div class="pair_img">'.'<a href="/index.php?c=topic&topic_id=20" ><img src="themes/yutui/images/once_buy.png" alt=""><span><img src="themes/yutui/images/doyen.png"></span></a></div>';
                        break;

                    default:
                        # code...
                        break;
                }
                 
             }
           

            $html .='<div class="pair_bonus"><p class="bonus_yuji">预计获得组团奖金：</p>';              
            $html .='<h3 class="bonus">'. $value['yujimoney'].'<span>元</span><span class="bonus_add">+</span><span class="lkd_right_count">'.$value['weight'].'</span><span>权重</span></h3></div></div>';    
            if(($value['member_right_status']+$value['member_left_status'])==2){
                $html .='<div class="pair_center pair_all"><div class="pair_gap"><p>立即自购领取奖励</p></div>';
            } else{
                 $html .='<div class="pair_center pair_all"><div class="pair_gap"><p>还差'.$left.'人完成</p></div>';
            }
            

            $html .='<div class="pair_win"><div class="pair_person">';
            $html .='<div class="pair_man">';            
            if($value['member_left_status']){
            $html .='<img src="'.$value['limg'].'" alt=""></div>';    
            }else{
                   $html .='<img src="themes/yutui/images/no-people.png" alt=""></div>'; 
            }
            $html .='<div class="pair_wom">'; 
            if($value['member_right_status']){
               $html .='<img src="'.$value['rimg'].'" alt=""></div>';   
           }else{
                $html .='<img src="themes/yutui/images/no-people.png" alt=""></div>';  
           }
             

            $html .='</div>'          ;             
            

          if(($value['member_right_status']+$value['member_left_status'])<2){
            $html .='<div class="handshake color2_active">' ;
              $html .=' <span class="ok_or_not">立即分享</span>';  
              $html .='</div>';
          }elseif (($value['member_right_status']+$value['member_left_status'])==2) {
              # code....
               $html .='<div class="handshake1">' ;
              
            $html .='</div>';
          }
              

            
            $html .='</div></div></div></div>';  
           }


        }
       
      
               
                           

        

       }
        switch ($_GET['type']) {
            case '1':
                # code...
                if($html&&$num>=5){
                    $html .=' <div class="sb1" style="background: #fff;text-align: center;">';
                    $html .=          '  <a href="javascript:;" class="gengduo more1" >加载更多</a></div>  ';
                }
                  
                break;
            case '2':
                # code...
                if($html&&$num>=5){

                  $html .=' <div class="sb1" style="background: #fff;text-align: center;">';
                  $html .=          '  <a href="javascript:;" class="gengduo more2" >加载更多</a></div>  ';
                }
         
                break;
            case '3':
                # code...
              if($html&&$num>=5){
                $html .=' <div class="sb1" style="background: #fff;text-align: center;">';
                $html .='<a href="javascript:;" class="gengduo more3" >加载更多</a></div>  ';
         }
                break;
            default:
                # code...
            if($num>=5){
                $html .=' <div class="sb1" style="background: #fff;text-align: center;">';
                $html .=          '  <a href="javascript:;" class="gengduo more1" >加载更多</a></div>  ';
            }
                   
                break;
        }


       if($html){

            $result['status'] = 200;
            $result['data'] = $html;
       }else{
            $result['status'] = 303;
            $result['data'] = "暂无数据";
       }
       
       echo json_encode($result);
            exit();
    }
        public function affiliate_partner_new()
    {
        $rrr = model("Users")->getUserRankList();
        
        $share = unserialize(C('affiliate'));
        $user_info = model('Users')->get_profile($_SESSION['user_id']);

        if (empty($share['config']['separate_by'])) {
            
            // 推荐注册分成
            $affdb = array();
            $num = count($share['item']);
            $up_uid = "'$this->user_id'";
            $all_uid = "'$this->user_id'";
            for ($i = 1; $i <= $num; $i ++) {
                $count = 0;
                if ($up_uid) {
                    $where = 'parent_id IN(' . $up_uid . ')';
                    $rs = $this->model->table('users')
                        ->field('user_id')
                        ->where($where)
                        ->select();
                    if (empty($rs)) {
                        $rs = array();
                    }
                    $up_uid = '';
                    foreach ($rs as $k => $v) {
                        $up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
                        if ($i < $num) {
                            $all_uid .= ", '$v[user_id]'";
                        }
                        $count ++;
                    }
                }
                $affdb[$i]['num'] = $count;
                $affdb[$i]['point'] = $share['item'][$i - 1]['level_point'];
                $affdb[$i]['money'] = $share['item'][$i - 1]['level_money'];
                $this->assign('affdb', $affdb);
            }
          
            $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
           
            $sql1 = "SELECT o.*, a.log_id,a.time, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";

        } else {
            
            // 推荐订单分成
            $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
            
            $sql1 = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
        }
        
        $rt = $this->model->query($sql1);
       
        $auid = $this->user_id;
        
        $user_list['user_list'] = array();
        
        $up_uid = "'$auid'";
        $all_count = 0;
        for ($i = 1; $i <= 1; $i ++) {
            $count = 0;
            if ($up_uid) {
                $sql = "SELECT user_id FROM " . $this->model->pre . "users WHERE parent_id IN($up_uid)";
                $query = $this->model->query($sql);
                
                $up_uid = '';
                foreach ($query as $k => $v) {
                    $up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
                    $count ++;
                }
            }
            $all_count += $count;
          
            if ($count) {
                $sql = "SELECT user_id,country,province,city,user_rank, user_name, '$i' AS level, email, is_validated, user_avatar,nick_name, rank_points,  reg_time " . " FROM " . $this->model->pre . "users WHERE user_id IN($up_uid)" . " ORDER by  reg_time desc";
                $result1 = $this->model->query($sql);
               
                $user_list['user_list'] = array_merge($user_list['user_list'], $this->model->query($sql));
            }
        }
        $levelone = 0;
        $leveltwo = 0;
        // 查询每个下级和下两级会员的佣金分总
        foreach ($user_list['user_list'] as $key => $value) {
            // code...
            
            foreach ($rt as $key1 => $value1) {
                if ($value1['user_id'] == $value['user_id']) {
                        
                    $user_list['user_list'][$key]['money'] += $value1['money']?$value1['money']:0;
                }
                
                // code...
            }
            if($value['level']==1){
                $levelone ++;
            }
             if($value['level']==2){
                $leveltwo ++;
            }
            if ($value['reg_time']) {
                $user_list['user_list'][$key]['reg_time'] = date("Y-m-d", $value['reg_time']);
            }

            $sql1 = "select rank_name from " . $this->model->pre . "user_rank  where min_points<=" . $value['rank_points'] . " and max_points >=" . $value['rank_points'];
                
                $rs1 = $this->model->query($sql1);
                
                $user_list['user_list'][$key]['rank_name'] = $rs1[0]['rank_name'];
        }
       
        $datetime = array();
        
        foreach ($user_list['user_list'] as $user) {
            $datetime[] = $user['reg_time'];
        }
        array_multisort($datetime, SORT_DESC, $user_list['user_list']);
        

        $user = $this->model->table('users')
            ->field('parent_id')
            ->where(array(
            'user_id' => $this->user_id
        ))
            ->find();
        
        if ($user['parent_id']) {
            
            $sql2 = "select * from " . $this->model->pre . "users  where user_id=" . $user['parent_id'];
            
            $parentdusers = $this->model->query($sql2);
        }
        
        if (! empty($parentdusers)) {
            
            foreach ($parentdusers as $key1 => $value1) {
                $userinfo1 = $this->model->table('users')
                    ->field('user_name,reg_time,nick_name,user_avatar,rank_points')
                    ->where(array(
                    'user_id' => $value1['user_id']
                ))
                    ->find();
                $parentdusers[$key1]['reg_time'] = date('Y-m-d', $userinfo1['reg_time']);
                $parentdusers[$key1]['user_name'] = $userinfo1['user_name'];
                $parentdusers[$key1]['nick_name'] = $userinfo1['nick_name'];
                $parentdusers[$key1]['user_avatar'] = $userinfo1['user_avatar'];
                $sql1 = "select rank_name from " . $this->model->pre . "user_rank  where min_points<=" . $userinfo1['rank_points'] . " and max_points >=" . $userinfo1['rank_points'];
                
                $rs1 = $this->model->query($sql1);
                
                $parentdusers[$key1]['rank_name'] = $rs1[0]['rank_name'];
                ;
                // code...
            }
        }
       
        foreach ($user_list['user_list'] as $key3 => $value3) {
            /*获取*/
            $not_comment = model('ClipsBase')->not_pingjia($value3['user_id']);
            $order = model('Order')->getuserordernum($value3['user_id']);

            $isvip = model('Users')->userisvip($value3['user_id']);
            $user_list['user_list'][$key3]['rank_name'] = $value3['user_rank']?$rrr[$value3['user_rank']-1]['rank_name']:"普通会员";
            $user_list['user_list'][$key3]['isvip'] = $isvip;
            $user_list['user_list'][$key3]['ordernum'] = $order;
            $user_list['user_list'][$key3]['ordercount'] =  $not_comment ;

            $user_list['user_list'][$key3]['user_name'] =  substr($value3['user_name'], 0, 5).'****'.substr($value3['user_name'], 9) ;

            # code...
        }

         if($_SESSION['user_vip']){

           $this->assign('share_title',  $user_info['nick_name']."的".C('shop_title'));
       
       }else{

            $this->assign('share_title',  C('shop_title'));
            
       }
         
        return $user_list['user_list'];
        
    }
    public function reorder(){

        
        $this->display('reorder.dwt');
    }
    public function upgrade(){
        $userinfo = model("Users")->getuserinfo($_SESSION['user_id']);
        $date1=strtotime(date('Y-m-d H:i:s'));
        $date2=$userinfo['reg_time'];//注册时间
        
        $result=count_days($date1,$date2);

        if($result<90){
            /*差额升级*/
            $this->assign('overdue',0);
            $this->assign('days',60-$result);
        }else{
            
            $this->assign('overdue',1);
            $this->assign('days',0);
        }
        $this->assign('user_rank',$_SESSION['user_rank']);
        $this->assign('user_vip',$_SESSION['user_vip']);
        $this->display('upgrade.dwt');
    }
    public function initiation(){

         $user_info = model('Users')->get_profile($_SESSION['user_id']);

         $detaildata = array(
                                                
                                                "account" =>$user_info['vip_manage_account']
                                             );
                            
         $ret = model("Index")->postData($detaildata,"/api/user/info/detail");

         $this->assign('data',$ret);
        
        $this->display('initiation.dwt');
    }
    public function join_member(){
        $order_type = $_GET['order_type'];


        $this->assign('order_type',$order_type);

        $this->display('join_member.dwt');
    }
    /*ajax获取会员当前还能升级的等级列表*/
    public function ajaxupgrade(){

       $currentVip =  $_SESSION['user_rank'];

       $res = model('Users')->get_rank_list_canupgrade($currentVip);

       if(!empty($res)){

            $result['status'] = 200;
            $result['data'] = $res;

       }else{

            $result['status'] = 422;
            $result['data'] = "暂无客升级等级";

       }
       

       echo json_encode($result,JSON_UNESCAPED_UNICODE);
       
       exit;

    }

    /*通过ajax获取产品列表*/
    public function getAjaxGoodsList(){
        /*目标等级*/


        $rtcat_id = $_POST['rtcat_id'];



        $goods_vip = $_POST['goods_vip'];

  
     
        /*获取对应的产品列表*/
        if(strpos($rtcat_id,",")){
            $userinfo = model('Users')->getusersinfo($_SESSION['user_id']);
            
             $date1=strtotime(date('Y-m-d H:i:s'));
             $date2=$userinfo['reg_time'];//注册时间
             $result=count_days($date1,$date2);
             if($result>60){
                $rtcat_id = 4;
               $res = model('GoodsBase')->get_goods_vip_list($goods_vip,4);
             }else{
                $rtcat_id =3;
               $res = model('GoodsBase')->get_goods_vip_list($goods_vip,3);
             }

          
            
        }else{
            if($rtcat_id==5){
                /*重购不能购买比自己低*/
        
                      $res = model('GoodsBase')->get_goods_vip_list($goods_vip,$rtcat_id);
                 
              
            }else{
                $res = model('GoodsBase')->get_goods_vip_list($goods_vip,$rtcat_id);
            }
            

        }
       
       
        foreach ($res as $key => $value) {
            # code...
            $res[$key]['rtgoods_pv'] = $value['rtgoods_pv']=='-1'?0.1*intval($value['shop_price']):$value['rtgoods_pv'];
            $res[$key]['href'] = "/index.php?m=default&c=goods&a=index&id=".$value['goods_id']."&order_type=".$rtcat_id;
        }
        $result = array() ;

        if(!empty($res)){

            $result['status'] = 200;
            $result['data'] = $res;

       }else{

            $result['status'] = 422;
            $result['data'] = "暂无套餐产品";

       }

        echo json_encode($result,JSON_UNESCAPED_UNICODE);
       
        exit;

    }
    public function getAjaxUpgradeGoodsList(){

        $rtcat_id = $_POST['rtcat_id'];



        $goods_vip = $_POST['goods_vip'];


     
        /*获取对应的产品列表*/
        if(strpos($rtcat_id,",")){
            $userinfo = model('Users')->getusersinfo($_SESSION['user_id']);
            
             $date1=strtotime(date('Y-m-d H:i:s'));
             $date2=$userinfo['reg_time'];//注册时间
             $result=count_days($date1,$date2);
             if($result>60){
                $rtcat_id = 4;
               $res = model('GoodsBase')->get_upgrade_goods_vip_list($goods_vip,4);
             }else{
                $rtcat_id =3;
               $res = model('GoodsBase')->get_upgrade_goods_vip_list($goods_vip,3);
             }

          
            
        }else{
            if($rtcat_id==5){
                /*重购不能购买比自己低*/
        
                      $res = model('GoodsBase')->get_upgrade_goods_vip_list($goods_vip,$rtcat_id);
                 
              
            }else{
                $res = model('GoodsBase')->get_upgrade_goods_vip_list($goods_vip,$rtcat_id);
            }
            

        }
       
       
        foreach ($res as $key => $value) {
            # code...
            $res[$key]['rtgoods_pv'] = $value['rtgoods_pv']=='-1'?0.1*intval($value['shop_price']):$value['rtgoods_pv'];
            $res[$key]['href'] = "/index.php?m=default&c=goods&a=index&id=".$value['goods_id']."&order_type=".$rtcat_id;
        }
        $result = array() ;

        if(!empty($res)){

            $result['status'] = 200;
            $result['data'] = $res;

       }else{

            $result['status'] = 422;
            $result['data'] = "暂无套餐产品";

       }

        echo json_encode($result,JSON_UNESCAPED_UNICODE);
       
        exit;
    }

}
<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ApiController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch接口控制器
 * 调用说明：url('api/index', array('openid'=>$openid, 'title'=>$title, 'msg'=>$msg, 'url'=>$url));
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ApiController extends CommonController
{
    private $weObj = '';
    private $wechat_id = 0;

    /**
     * 构造方法
     */
    public function __construct()
    {
        parent::__construct();
        // 获取公众号配置
        $wxConf = $this->getConfig();
        $this->weObj = new Wechat($wxConf);
        $this->wechat_id = $wxConf['id'];
       
        require(APP_PATH . C('_APP_NAME') . '/languages/' . C('LANG') . '/user.php');
        L($_LANG);

        
    }

    /**
     * PC后台发送发货通知模板消息接口方法
     *
     */
    public function index()
    {
        $user_id = I('get.user_id', 0, 'intval');
        $code = I('get.code', '', 'trim');
        $pushData = I('get.pushData', '', 'trim');
        $url = I('get.url', '');
        $url = $url ? base64_decode(urldecode($url)) : '';

        if ($user_id && $code) {
            $pushData = stripslashes(urldecode($pushData));
            //转换成数组
            $pushData = unserialize($pushData);
            // 发送微信通模板消息
            pushTemplate($code, $pushData, $url, $user_id);
        }
    }

    /**
     * JSSDK 参数
     * @return
     */
    public function jssdk()
    {
        $url = I('url', '', 'addslashes');
        if (!empty($url)) {
            $sdk = $this->weObj->getJsSign($url);
            $data = array('status' => '200', 'data' => $sdk);
        } else {
            $data = array('status' => '100', 'message' => '缺少参数');
        }
        exit(json_encode($data));
    }






    /**
     * 获取公众号配置
     *
     * @return array
     */
    private function getConfig()
    {
        $config = $this->model->table('wechat')
                ->field('id, token, appid, appsecret')
                ->where(array('status' => 1, 'default_wx' => 1))
                ->find();
        if (empty($config)) {
            $config = array();
        }
        return $config;
    }




    private function setSession(){
        $touchid= I("post.touchid");
        $tse = new EcsSession(
            self::$db,
            self::$ecs->table('sessions'),
            self::$ecs->table('sessions_data'),
            C('COOKIE_PREFIX').'touch_id',$touchid);
        setcookie($tse->session_name, $tse->session_id . $tse->gen_session_key($tse->session_id), 0, $tse->session_cookie_path, $tse->session_cookie_domain, $tse->session_cookie_secure);
        parent::$sess = $tse;
        
    }


    

    



    /*返回订单数据*/
    public function insertOrders(){
          
            /*签名信息验证*/
         apiverify($_POST); 
          /*先生成一个帐号，获取到user_id*/
       
        $this->model->query("START TRANSACTION");
       
        $userinfo['user_name'] = $_POST['user_name'];

        $isfind = model("Users")->getusersbyaccount($userinfo['user_name']);

        if($isfind){

                $result['status'] = 422;
                $result['msg'] ="用户已经存在";
                $result['data'] = array();
                die(json_encode($result));
                exit(); 
        }
        
        $post_password = model("Users")->new_compile_password(array(
                'password' => $_POST['password']
            ));
        $userinfo['password'] = $post_password;

        $userinfo['reg_time'] = time();
          /*根据传过来的上一级用户查询他的用户id*/
       // $userinfo['parent_id'] = model("Users")->getuserid($_POST['parent_user_name']);
        /*插入用户表*/
      
        $userinfo['parent_id'] =  $_POST['parent_id'];

        /*经销商等级*/

       

        /*店主，服务商，社群合伙人，拓客合伙人，注册用户默认是店主身份*/
        $userinfo['user_rank'] = $_POST['vip'];
        $userinfo['user_vip'] = 1;
        $userinfo['nick_name'] = $_POST['nickname'];
        $userinfo['mobile_phone'] = $_POST['mobile_phone'];
        $userinfo['country_id'] = 1;//入团的国家默认为1中国
        $userinfo['province_id'] = $_POST['join_province_id'];//入团的省份
        $userinfo['city_id'] = $_POST['join_city_id'];//入团的城市
        $userinfo['vip_manage_account'] = $_POST['user_name'];
        $userinfo['resource'] = 1;
       
        $res0 = $this->model->table('users')->data($userinfo)->insert();

        
        if(!$res0){
                
                $this->model->query("ROLLBACK"); //事务回滚
                $result['status'] = 422;
                $result['msg'] ="用户数据插入错误";
                $result['data'] = array();
                die(json_encode($result));
                exit(); 
        }

       // $user_id = M()->insert_id(); 
        $user_id = $res0;

        $vipmanage['vip_manage_account'] = $_POST['user_name'];
        $vipmanage['user_id'] = $user_id;
        $vipmanage['add_time'] = time();
        /*插入vip管理帐号表*/
        model("Users")->updateuser_reposition($user_id,$userinfo['parent_id']);

        $res1 = model("Users")->newaddvipamanageaccount($user_id,$_POST['user_name'],1);
        if(!$res1){
           
               $this->model->query("ROLLBACK"); //事务回滚
               $result['status'] = 422;
               $result['msg'] ="vip管理账号数据插入错误";
               $result['data'] = array();
                die(json_encode($result));
                exit(); 
        }

        /*升级vip*/
        
     

        $order['user_id']   =   $res0;
        $order['order_sn'] = get_order_sn();
        $order['order_status'] = 1;//COMMENT '订单状态。0，未确认；1，已确认；2，已取消；3，无效；4，退货；', 
        $order['shipping_status'] = 0;//COMMENT '商品配送情况，0，未发货；1，已发货；2，已收货；3，备货中', 
        $order['pay_status'] = $_POST['pay_status'];//COMMENT '支付状态；0，未付款；1，付款中；2，已付款', 
        $order['consignee'] = $_POST['consignee'];//COMMENT '收货人的姓名，用户页面填写
        $order['country'] = $_POST['country'];//smallintCOMMENT '收货人的国家，用户页面填写
        $order['province'] = $_POST['province'];//smallintCOMMENT '收货人的省份，用户页面填写
        $order['city'] = $_POST['city'];//smallintCOMMENT '收货人的城市，用户页面填写
        $order['district'] = $_POST['district'];//DEFAULT '0' smallintCOMMENT '收货人的地区，用户页面填写        
        $order['address'] = $_POST['address'];//DEFAULT '0' smallintCOMMENT '收货人的详细地址，用户页面填写 
         
        $order['mobile'] = $_POST['mobile'];//DEFAULT '0' smallintCOMMENT '收货人的详细地址，用户页面填写
        $order['pay_id'] = 2;
        $order['pay_name'] = "余额支付";
        $order['how_oos'] = '等待所有商品备齐后再发';
        $order['goods_amount'] = $_POST['goods_amount'];   // 商品总金额
        $order['money_paid'] = $_POST['money_paid'];
        $order['integral'] = $_POST['integral'];//使用积分数量
        $order['integral_money'] = $_POST['integral_money'];//使用积分金额
        $order['order_amount'] = $_POST['order_amount'];
        $order['add_time'] = time();//
        $order['confirm_time'] = time();
        $order['pay_time'] = time();
        $order['join_country_id'] = $_POST['join_country_id'];//入团的国家默认为1中国
        $order['join_province_id'] = $_POST['join_province_id'];//入团的省份
        $order['join_city_id'] = $_POST['join_city_id'];//入团的城市
        $order['parent_id'] = $userinfo['parent_id'];
        $order['order_type'] = $_POST['order_type'];
        $order['total_pv'] =  $_POST['total_pv'];
        $order['total_rtpv'] =  $_POST['rtgoods_pv'];
        $order['total_vippv'] =  $_POST['vipgoods_pv'];
        $order['vip'] = $_POST['vip'];
        
        $new_order = model('Common')->filter_field('order_info', $order);
        $res2 = $this->model->table('order_info')->data($new_order)->insert();
        if(!$res2){
               
               $this->model->query("ROLLBACK"); //事务回滚
               $result['status'] = 422;
               $result['msg'] ="订单数据插入错误";
               $result['data'] = array();
                die(json_encode($result));
                exit(); 
        }

        $new_order_id = M()->insert_id();

        $order ['order_id'] = $new_order_id;

        // $order_goods['order_id'] = $new_order_id;
        // $order_goods['goods_id'] = $new_order_id;
        // $_POST['goods']  json数据
        //插入订单产品数据表
        
        $goodsid_list = json_decode(stripslashes($_POST['goodsinfo']),true);
       
        if(!$goodsid_list){
                
               $this->model->query("ROLLBACK"); //事务回滚
               $result['status'] = 422;
               $result['msg'] ="产品数据为空";
               $result['data'] = array();
                die(json_encode($result));
                exit(); 
         }
     
        foreach ($goodsid_list as $key => $value) {
            # code...

             $goodsinfo = model("Goods")->get_goods_info($value['goods_id']);
                if($order['order_type']==1){
                    $sql = "INSERT INTO ecs_order_goods " . "(`order_id`,`goods_id`,`goods_name`,`goods_number`,`goods_price`,`is_real`) VALUES ('$new_order_id','$value[goods_id]','$goodsinfo[goods_name]','1','$goodsinfo[shop_price1]','1')";
                }else{
                    $sql = "INSERT INTO ecs_order_goods " . "(`order_id`,`goods_id`,`goods_name`,`goods_number`,`goods_price`,`is_real`) VALUES ('$new_order_id','$value[goods_id]','$goodsinfo[goods_name]','$value[goods_number]','$goodsinfo[shop_price1]','1')";
                }
             
             $r = $this->model->query($sql);
             if(!$r){
              
               $this->model->query("ROLLBACK"); //事务回滚
               $result['status'] = 422;
               $result['msg'] ="数据插入错误";
               $result['data'] = array();
                die(json_encode($result));
                exit(); 
            }
        }
        //$r = model('ClipsBase')->log_account_change($_POST['user_id'], -$_POST['money_paid'], 0, 0, 0, "余额更新", ACT_ADJUSTING);

        
        if($_POST['lx_discount']){
            $r2 = model('ClipsBase')->new_log_account_change($_POST['user_id'], -$_POST['lx_discount'],"支付订单".$order['order_sn'],ACT_ADJUSTING, 12,1);
            $r = model('ClipsBase')->new_log_account_change($_POST['user_id'], -($_POST['money_paid']-$_POST['lx_discount']),"支付订单".$order['order_sn'],ACT_ADJUSTING, 1,1);
        }else{
            $r = model('ClipsBase')->new_log_account_change($_POST['user_id'], -$_POST['money_paid'],"支付订单".$order['order_sn'],ACT_ADJUSTING, 1,1);
        }
        $r1= model('ClipsBase')->create_new_log_account($user_id);

         if(!$r){
                
               $this->model->query("ROLLBACK"); //事务回滚
               $result['status'] = 422;
               $result['msg'] ="余额更新失败";
               $result['data'] = array();
                die(json_encode($result));
                exit(); 
         }
         // if(!$r1){
                
         //       $this->model->query("ROLLBACK"); //事务回滚
         //       $result['status'] = 422;
         //       $result['msg'] ="资金账号生成失败".$user_id;
         //       $result['data'] = array();
         //       die(json_encode($result));
         //       exit(); 
         // }

        $this->model->query("COMMIT"); //事务提

         

        $data['order_sn'] = $order['order_sn'];
        $data['order_amount'] = $order['order_amount'];
        $data['order_pv'] = $order['total_pv'];
        $data['order_time'] = $order['add_time'];
        $data['purse_amount'] = $order['integral_money'];
        $result['status'] = 200;
        $result['msg'] ="ok";
        $result['data'] = $data;
        die(json_encode($result));
        exit(); 
          




    }
    public function insertUsers(){
        apiverify($_POST); 
        $this->model->query("START TRANSACTION");
        $userinfo['user_name'] = $_POST['account'];
        $isfind = model("Users")->getusersbyaccount($userinfo['user_name']);

        if($isfind){

                $result['status'] = 422;
                $result['msg'] ="用户已经存在";
                $result['data'] = array();
                die(json_encode($result));
                exit(); 
        }
        $parentinfo = model("Users")->getnewusersbyvipaccount($_POST['parent_account']);

        if(!$parentinfo){

                $this->model->query("ROLLBACK"); //事务回滚
                $result['status'] = 422;
                $result['msg'] ="上级用户不存在";
                $result['data'] = array();
                die(json_encode($result));
                exit(); 

        }else{

           $userinfo['parent_id'] = $parentinfo['user_id'];

        }

        // $post_password = model("Users")->new_compile_password(array(
        //         'password' => $_POST['password']
        //     ));

        $userinfo['password'] = $_POST['password'];

        $userinfo['reg_time'] = time();
        if($_POST['user_rank']){

             $userinfo['user_rank'] = $_POST['user_rank'];
             $userinfo['user_vip'] = 1;  

        }else{

             $userinfo['user_rank'] = 0;
        }
        
        $userinfo['mobile_phone'] = $_POST['mobile_phone'];

        if($_POST['nick_name']){
            $userinfo['nick_name'] = $_POST['nick_name'];
        }
        
        $userinfo['country_id'] = 1;//入团的国家默认为1中国
        $userinfo['vip_manage_account'] = $_POST['account'];
        $userinfo['resource'] = 1;
        /*生成用户表数据*/
        $res0 = $this->model->table('users')->data($userinfo)->insert();
        if(!$res0){
                
                $this->model->query("ROLLBACK"); //事务回滚
                $result['status'] = 422;
                $result['msg'] ="user表数据插入错误";
                $result['data'] = array();
                die(json_encode($result));
                exit(); 
        }
        $accountinfo['user_id'] = $res0;
        $accountinfo['created_at'] = date("Y-m-d H:i:s",time());
        $accountinfo['updated_at'] = date("Y-m-d H:i:s",time());
        $res1 = $this->model->table('account')->data($accountinfo)->insert();
        if(!$res1){
             
                $this->model->query("ROLLBACK"); //事务回滚
                $result['status'] = 422;
                $result['msg'] ="account表数据插入错误";
                $result['data'] = array();
                die(json_encode($result));
                exit(); 
        }
        if($_POST['pay_points']){

          /*注册送积分*/

            model('ClipsBase')->new_log_account_change($res0,C('register_points'),L('register_points'),ACT_KD,6,1);


        }

        /*插入vip管理帐号表*/
        model("Users")->updateuser_reposition($res0,$userinfo['parent_id']);

        $res2 = model("Users")->newaddvipamanageaccount($res0,$_POST['account'],1);

        if(!$res2){
           
               $this->model->query("ROLLBACK"); //事务回滚
               $result['status'] = 422;
               $result['msg'] ="vip管理账号数据插入错误";
               $result['data'] = array();
                die(json_encode($result));
                exit(); 
        }

        $this->model->query("COMMIT"); //事务提

        $result['status'] = 200;
        $result['msg'] ="ok";
        $result['data'] = array();
        die(json_encode($result));
        exit(); 

    }
    public function returnProvinceList(){
         apiverify($_POST); 

        $data = model('RegionBase')->get_regions(1, $_POST['country_id']);
        $result['status'] = 200;
        $result['msg'] ="ok";
        $result['data'] = $data;
        die(json_encode($result));
        exit(); 
    }
        /*返回省份列表*/
    public function returnCityList(){
         apiverify($_POST);
        
        $data = model('RegionBase')->get_regions(2, $_POST['province_id']);
        $result['status'] = 200;
        $result['msg'] ="ok";
        $result['data'] = $data;
        die(json_encode($result));
        exit(); 
    }
            /*返回地区列表*/
    public function returnDistrictList(){
        apiverify($_POST);
        
        $data = model('RegionBase')->get_regions(3, $_POST['city_id']);
        $result['status'] = 200;
        $result['msg'] ="ok";
        $result['data'] = $data;
        die(json_encode($result));
        exit(); 
    }

        /*返回产品列表数据*/
    public function returnGoods(){
            /*签名信息验证*/
            
          apiverify($_POST);


          $wherelist = array();

          if(!empty($_POST['goods_id'])){

             $wherelist[] = "goods_id = '{$_POST['goods_id']}'";

          }

          if(!empty($_POST['goods_name'])){

             $wherelist[] = "goods_name = '{$_POST['goods_name']}'";

          }
          if(!empty($_POST['origin_goods_vip'])){

             $wherelist[] = "origin_goods_vip = '{$_POST['origin_goods_vip']}'";

          }
           if(!empty($_POST['goods_vip'])){

             $wherelist[] = "goods_vip = '{$_POST['goods_vip']}'";

          }
           if(!empty($_POST['areas_id'])){

            $wherelist[] = "find_in_set({$_POST['areas_id']},areas_id)";

          }
           if(!empty($_POST['goods_area'])){

              $wherelist[] = "find_in_set({$_POST['goods_area']},goods_area)";

          }
           $wherelist[] = "rtcat_id<>2";
           
           $wherelist[] = "is_on_sale = '1'";
          //组装查询条件
         if(count($wherelist) > 0){
            $where = " where ".implode(' AND ' , $wherelist); 
         }
         //判断查询条件
              $where = isset($where) ? $where : '';
          // echo $where;exit;
          // $this->query($sql);
   
        $data = model("Goods")->apigetgoods($where);

     //$sql = "SELECT * FROM `table` {&where} ";   
             $result['status'] = 200;
             $result['msg'] ="ok";
             $result['data'] = $data;
             die(json_encode($result));
             exit(); 



    }
        /*vip中心更新数据同步到国际购*/
    public function updateUsers(){
            
         apiverify($_POST);
          /*更新字段*/
            if(isset($_POST['password'])){
                
                $updateData['password'] = $_POST['password'];
            }
             if(isset($_POST['pay_password'])){
                
                $updateData['pay_password'] = $_POST['pay_password'];
            }
            if(isset($_POST['nick_name'])){
               
                $updateData['nick_name'] = $_POST['nick_name'];

            }
            if(isset($_POST['real_name'])){
               
                $updateData['real_name'] = $_POST['real_name'];

            }
             if(isset($_POST['email'])){
               
                $updateData['email'] = $_POST['email'];
            }
             if(isset($_POST['mobile_phone'])){
               
                $updateData['mobile_phone'] = $_POST['mobile_phone'];
            }
             if(isset($_POST['user_rank'])){
               
                $updateData['user_rank'] = $_POST['user_rank'];
            }





           
           
            $r = model('Users')->new_update_info_user($_POST['account'],$updateData);

        
            if($r){
                $result['status'] = 200;
                 $result['msg'] ="ok";
                 $result['data'] = '';
                 die(json_encode($result));
                 exit();
         }else{
             $result['status'] = 422;
             $result['msg'] ="更新失败";
             $result['data'] = array();
             die(json_encode($result));
               exit();    
         }
              

    }
    /*获取用户的信息*/
    public function  userInfo(){
          apiverify($_POST);


          $data = model('Users')->getusersbyaccount($_POST['account']);
          $result['status'] = 200;
          $result['msg'] ="ok";
          $result['data'] = $data;
          die(json_encode($result));
          exit(); 

    }


    /*更新制度网过来的用户更新信息*/
    public function updateUserInfo(){

         apiverify($_POST);
        $type = $_POST['type'];

      
        switch ($type) {

            case '61':
                /*更新余额*/
                //$r = model('ClipsBase')->log_account_change($_POST['mid'], $_POST['user_money'], 0, 0, 0, "余额更新", ACT_ADJUSTING);
                $r = model('ClipsBase')->new_log_account_change($_POST['mid'], $_POST['user_money'],"余额更新",ACT_ADJUSTING, 1);

                break;

            case '62':
                //更新福豆
                # code...
                //$r = model('ClipsBase')->log_account_change($_POST['mid'], 0, 0, 0, $_POST['pay_points'], "更新".C('integral_name'), ACT_KD);
                $r = model('ClipsBase')->new_log_account_change($_POST['mid'], $_POST['pay_points'],"更新".C('integral_name'),ACT_KD, 6);
                
                break;
            
            default:
                # code...
                break;
        }
          $result['status'] = 200;
          $result['msg'] ="ok";
          $result['data'] = '';
          die(json_encode($result));
          exit(); 


    }
    /**
 * 获取用户的购买折数
 * @param $data
 */
    public function get_vip_ratio()
    {
        apiverify($_POST);

       $usersid = $_POST['user_id'];
       $res = M()->table('users')->field('user_rank')->where('user_id = ' . $usersid)->find();
       if($res['user_rank']>0){

           $res1 =  M()->table('user_rank_list')->field('cash_ratio')->where('rank_id =' .$res['user_rank'])->find();

           $cash_ratio = $res1['cash_ratio']/100;

       }else{
        /*普通用户的支付比例*/
        $cash_ratio = 0.9;
         
       }

       
       $result['status'] = 200;
       $result['msg'] ="ok";
       $result['data'] = $cash_ratio;
       die(json_encode($result));
       exit(); 
    }





        /*检测是否存在此手机号*/
    public function isFindMobile(){
        $mobile = $_POST['mobile'];
        // 验证手机号重复
                /*终止号9可以注册*/
                 $data = $this->model->query("select  user_id,user_name,nick_name,user_avatar,vip_manage_account,status from `ecs_users`  where mobile_phone='$mobile' and status=1 or status=2 ");   
                 $data1 = $this->model->query("select  user_id,user_name,nick_name,user_avatar,vip_manage_account,status from `ecs_users`  where mobile_phone='$mobile' and status=1 "); 
                
                if(!empty($data)){
                    foreach ($data1 as $key=>$val){
                        $data1[$key]['nick_name'] = getEmoji($val['nick_name']);
                    }
                    
                     $result['status'] = 200;
                     $result['msg'] = "已经存在此手机号";
                     $result['data'] =$data1;
                     $result['sms_mobile'] = substr_replace($_SESSION['sms_mobile'], '****',3, 4);
                     
                     
                     die(json_encode($result));
                }else{
                     $result['status'] = 400;
                     $result['msg'] = "此手机号可以注册";
                    
                     $result['sms_mobile'] = substr_replace($_SESSION['sms_mobile'], '****',3, 4);
                     $result['data'] = array();
                     die(json_encode($result));
                }


    } 

    /*检验验证码*/
    public function verify_smscode(){

            $sms_code = $_POST['sms_code'];

            if($sms_code!=$_SESSION['sms_code']){
               
                 $this->jserror("验证码错误");
            }else{
                 $this->jssuccess("ok");
            }

            
    }

    public function tgesa(){
        $data = array();
        $data['Mobile'] = "6138283355";
        $mname = "GetMobileInfo";
        getTianMeiShi($mname,$data);
    }


 










    
}

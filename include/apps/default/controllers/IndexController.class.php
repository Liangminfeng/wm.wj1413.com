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

class IndexController extends CommonController {

    /**
     * 首页信息
     */

    public function index() {
        $topic = $this->model->table('topic')->where('top = 1')->find();
        
        if(!empty($topic))
        {
            /* 如果没有找到任何记录则跳回到首页 */
            $this->redirect(url('topic/index',["topic_id"=>$topic["topic_id"]]));
        }
        
        $this->redirect("article/article_index");
           
            //$this->uid = $_GET['u']?$_GET['u']:($_SESSION['parent_id']?$_SESSION['parent_id']:0);
        if($_SESSION["tmp_uuid"]){
            $this->uid = $_SESSION["tmp_uuid"];
        }else{
            $this->uid = $_GET['u']?$_GET['u']:($_SESSION["tmp_uuid"]?$_SESSION["tmp_uuid"]:0);
        }
           
            $this->user_id =$this->uid?$this->uid:$_SESSION['user_id'];
            
            $user_info = model('Users')->get_profile($_SESSION['user_id']);
          
            if($this->user_id&&$user_info['user_vip']){
               /*如果当前用户是vip*/
              

                $user_info =  model('Users')->get_profile($this->user_id); 
             
         

           }elseif($this->uid){
            /*显示当前分享出来的那个人vip信息*/
            
            $user_info = model('Users')->get_profile($this->uid);
           
            if($user_info['user_vip']){
                $user_info = model('Users')->get_profile($this->uid);
            }else{
                
                 $user_info = false;
            }
           }
            
          

           
            $resource_type = empty($user_info['resource'])?1:$user_info['resource'];

//         $cache_id = sprintf('%X', crc32($_SESSION['user_rank'] . '-subscribe' . $_SESSION['subscribe'] . '-' . C('lang')));
        
//         if (!ECTouch::view()->is_cached('index.dwt', $cache_id))
//         {
            // 自定义导航栏F
            
            $navigator = model('Common')->get_navigator();
            $this->assign('uid',$this->uid);
            $this->assign('navigator', $navigator['middle']);
            $this->assign('best_goods', model('Index')->goods_list('best', C('page_size')));
            $this->assign('new_goods', model('Index')->goods_list('new', C('page_size')));
            $this->assign('hot_goods', model('Index')->goods_list('hot', C('page_size')));
            // 调用促销商品
            $this->assign('promotion_goods', model('Index')->goods_list('promotion', C('page_size')));
            //首页推荐分类
            $cat_rec = model('Index')->get_recommend_res(10,4);
            $this->assign('cat_best', $cat_rec[1]);
            $this->assign('cat_new', $cat_rec[2]);
            $this->assign('cat_hot', $cat_rec[3]);
            // 促销活动
            $this->assign('promotion_info', model('GoodsBase')->get_promotion_info());
            // 团购商品
            $this->assign('group_buy_goods', model('Groupbuy')->group_buy_list(C('page_size'),1,'goods_id','ASC'));
            // 获取分类
            $this->assign('categories', model('CategoryBase')->get_categories_tree());
            // 获取品牌
            $this->assign('brand_list', model('Brand')->get_brands($app = 'brand', C('page_size'), 1));
            // 分类下的文章
            $this->assign('cat_articles', model('Article')->assign_articles(1,5,$resource_type)); // 1 是文章分类id ,5 是文章显示数量
//         }
       
        $navigator = model('Common')->get_navigator();

         $articles = model('Article')->assign_articles(6,2,$resource_type);
         //增加只有当前用户是分销商才能点击分享
      
         $this->assign('user_rank',$_SESSION['user_rank']);
  
         if($user_info['user_name']){
            if(isset($_SESSION['user_vip'])){
                $this->assign('user_vip',$_SESSION['user_vip']?$_SESSION['user_vip']:$user_info['user_vip']);
            }else{

                $this->assign('user_vip',$user_info['user_vip']);
            }
            
        }else{
            $this->assign('user_vip',$_SESSION['user_vip']?$_SESSION['user_vip']:0);
        }
         
         $this->assign('cat_articles', $articles['arr']); 
         //var_dump(model('Article')->assign_articles(6,5));exit;
         //查询当前登录用户是否有受赠的订单
         
      
         //公告
         $notice_list = model('ArticleBase')->get_cat_articles(4, 1, 5,"","",1);
   
         if (is_array($notice_list)) {
            foreach ($notice_list as  $key => $value) {

                
              
                $notice_list[$key]['short_title'] = C('article_title_length') > 0 ? sub_str($notice_list[$key]['title'], C('article_title_length')) : $notice_list[$key]['title'];
              
                $notice_list[$key]['url'] = $notice_list[$key]['link'] && $notice_list[$key]['link'] !='http://' ?  $notice_list[$key]['link'] : url('article/info', array('aid' => $notice_list[$key]['article_id'])) ;
                $notice_list[$key]['add_time'] = date(C('date_format'), $notice_list[$key]['add_time']); 


                
            }
           
        }

         $this->assign('notice_list', $notice_list);
         
         if($_SESSION['user_id']){
             $shouzengt = self::$cache->getValue("shou_zeng_order_t_{$_SESSION["user_id"]}");
            
             if(empty($shouzengt)){
                 $shouzeng = self::$cache->getValue("shou_zeng_order_{$_SESSION["user_id"]}");
                 if(!empty($shouzeng)){ 
                    $where['user_id'] = $_SESSION['user_id'];
                    $shouzengorders = model("Train")->getUserUnactiveTicket($_SESSION['user_id']);
                    $ticketnum =  sizeof($shouzengorders);
                    foreach ($shouzengorders as $key => $value) {
                        if(!empty($value['nick_name'])){
                            $shouzengorders[$key]['user_name'] = substr($value['nick_name'], 0, 4).'****'.substr($value['nick_name'], 6);

                        }else{
                            $shouzengorders[$key]['user_name'] = $value['nick_name'];
                        }
                        # code...
                    }
                    
                    $this->assign('shouzengorders',$shouzengorders);
                }
             }else{
                 $shouzengts = model("Train")->getUserUncheckTicket($_SESSION['user_id']);
                 $this->assign('shouzengts', $shouzengts);
             }
         }
       
         $jiankangarticlenew = model('ArticleBase')->get_cat_articles("6", 1, 1, "","",$resource_type);
         $yubangarticlenew = model('ArticleBase')->get_cat_articles("9", 1, 1, "","",$resource_type);
 
         $this->assign('jiankangarticlenew', $jiankangarticlenew);
         $this->assign('yubangarticlenew', $yubangarticlenew);
         $this->assign('ticketnum', $ticketnum);
         $this->assign('user_id',$_SESSION['user_id']);
         $this->assign('user_rank',$_SESSION['user_rank']);
         $this->assign('user_info',$user_info);
         $uid = $_GET['u']?$_GET['u']:($_SESSION['user_id']?$_SESSION['user_id']:0);
         if($uid){
              $shortLink = $this->getShortUrl(__URL__ . "/index.php?u=" . $_SESSION['user_id']);
          }else{
              $shortLink = __URL__;
          }
      
     
        $this->assign('share_link', $shortLink); //

         //$this->assign('share_link', __URL__);//
         

         if($user_info['user_rank']){

           $this->assign('share_title',  $user_info['nick_name']."的".C('shop_title'));
       
           }else{

                $this->assign('share_title',  C('shop_title'));
                
           }
            $trainModel = model("Train");
            $userId = $_SESSION["user_id"];
            $beginTrainProductList = $trainModel->getTrainList("`status`=1 and start_time<now() ")["data"];
          
            if(!empty($userId)){
            $trainList = $trainModel->gettrainidbyuser($userId);
            $userTrains = array();
            foreach ($trainList as $key=>$ut){
                $userTrains[] = $ut["train_id"];
            }

            foreach ($beginTrainProductList as $key=>$train){
                if(in_array($train["train_id"], $userTrains)){
                    $beginTrainProductList[$key]["ontrain"] = true;
                }
                $beginTrainProductList[$key]['lefttime'] = calcTime(time(), $train["end_time"]);
            }
            
              
            
            
            //查询个人收益部分
            $totalRebate = $trainModel->getUserTotalRebate($userId);
            $liveRebate = $trainModel->getUserLiveRebate($userId);
            $this->assign('total_rebate', $totalRebate);
            $this->assign('live_rebate', $liveRebate); 
        }

         foreach ($beginTrainProductList as $key1=>$train1){
                $beginTrainProductList[$key1]['ratio'] = $train1['total']?round(($train1['num']/$train1['total']),3)*100:0;
                $beginTrainProductList[$key1]['lefttime'] = calcTime(date("Y-m-d h:i:s",time()), $train1["end_time"]);
            }
            
         $this->assign('begintrainproductlist', $beginTrainProductList); 

         $this->assign('hot_goods_one', model('Index')->category_get_goods('12', 0,10));
  
         $this->assign('hot_goods_two', model('Index')->category_get_goods('112', 0,10));
         $this->assign('hot_goods_three', model('Index')->category_get_goods('125', 0,10));
         $this->assign('hot_goods_four', model('Index')->category_get_goods('99', 0,10));

         $this->assign('hot_goods_five', model('Index')->category_get_goods('62', 0,10));
         $this->assign('hot_goods_six', model('Index')->category_get_goods('136', 0,10));
         $this->assign('share_description', $this->formatDescription(C('shop_desc')));//
         $this->assign('share_pic', __URL__."/themes/yutui/images/new/yutui_logo.png");//
         
        
        // 关注按钮 是否显示
        $this->assign('user_id',$_SESSION['user_id']);
        $this->assign('subscribe', $_SESSION['subscribe']);
        $this->assign('resource',$user_info['resource']);
 
        $this->display('index.dwt', $cache_id);
    }
    
    public function ticketConfirm(){
        model("Train")->ticketConfirm($_SESSION["user_id"]);
    }
    
    public function train_invite(){
        // 获取用户邀请人和升级vip人数
        // $data = array(
        //     "mid" =>$_SESSION['user_id'],
        //     "token" =>API_TOKEN
        // );
        // $invitor_num =  post_log($data ,API_URL."/api/user/grade",5);

        /*获取目前登录的会员一级下级的人数*/
        $num = model("Users")->getChildNum($_SESSION['user_id']);
        
        $vipnum = model("Users")->getChildVipNum($_SESSION['user_id']);
        $this->assign('num',$num);
        $this->assign('vipnum',$vipnum);
        if (empty($_SESSION['user_id'])) {
            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect(url('user/register', array('back_act' => urlencode($back_act))));
            exit();
        }
        
        if(!$_SESSION["user_vip"]){
            show_message(L('error_nochuangke'), L('home'), url('index'));
        }
        
        $shortUrl = $this->getShortUrl(__URL__."/index.php?c=user&a=loadlandpage&u=".$_SESSION['user_id']);
        
        $userInfo = model("Users")->get_users($_SESSION["user_id"]);
        $viporderlist  =  model('Users')->viporderlist(10);
     
        $vad = shuffle($viporderlist);
        
        $this->assign('viporderlist',$viporderlist);
        $this->assign("user_info", $userInfo);
        $this->assign("invitor_num", $invitor_num['data']);
        $this->assign('share_link', $shortUrl);//
        $this->assign('share_title', '您的好友邀请您加入会省钱更会赚钱的商城');//
        $this->assign('share_description', htmlspecialchars('甄选全球好货社交购物平台，好的生活可以更省钱~'));//
        $this->assign('share_pic', __URL__."/themes/yutui/images/new/yutui_logo.png");//
        
        $this->assign("headerContent", L("user_invite_partner"));
        $this->assign('footer_index','affiliate');
        $this->display('train_invite.dwt');
    }
    
    public function welcome(){
        $d = self::$cache->getValue("test");
        if(!$d) {
            $d = date("Y-m-d H:i:s");
            self::$cache->setValue("test", $d, 10);
        }
        $this->assign("cacheTime", $d);
        $this->assign("headerContent",L("welcome"));
        $this->display('welcome.dwt');
    }
    
    public function towechat(){
        $this->display('towechat.dwt');
    }
    public function test(){
        
        echo  DATA_DIR;
        echo "<br>";
        echo ENV;
        die;
        echo C("integrate_code");
        die;
        self::$cache->delValue("test");
        $k = self::$cache->getValue("test");
        if($k) echo $k;
        die;
        $this->display('testerweima.dwt');
    }
    /**
     * ajax获取商品
     */
    public function ajax_goods() {
       
        if (IS_AJAX) {
            $type = I('get.type');
            $start = $_POST['last'];
            $limit = $_POST['amount'];
         
            $goods_list = model('Index')->goods_list($type, $limit, $start);
            $list = array();
            
            // 热卖商品
            if ($goods_list) {
                foreach ($goods_list as $key => $value) {
                  

                    $value['iteration'] = $key + 1;

                    
                    
                    $this->assign('goods', $value);
                    $this->assign('vip',$_SESSION['user_vip']);
                    $list [] = array(
                        'single_item' => ECTouch::view()->fetch('library/asynclist_index.lbi')
                    );
                }
            }
            echo json_encode($list);
            exit();
        } else {
            $this->redirect(url('index'));
        }
    }

}

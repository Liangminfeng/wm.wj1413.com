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

class TrainController extends CommonController {

    private $size = 10;
    private $page = 1;
    private $cat_id = 0;
    private $keywords = '';

    public function __construct() {
        parent::__construct();
        // 屬性賦值
        $this->user_id = $_SESSION['user_id'];
        $this->action = ACTION_NAME;
        $this->check_login();
    }

    /* ------------------------------------------------------ */

    //-- 文章分类
    /* ------------------------------------------------------ */
    public function index() {
        /**
         * 
         * @var TrainModel $trainModel
         */
        $trainModel = model("Train");
        
        $userId = $_SESSION["user_id"];
        //0活动未开始列车产品
        $notBeginTrainProductList = $trainModel->getTrainList(["status"=>0])["data"];
        //1活动开始
        $beginTrainProductList = $trainModel->getTrainList("`status`=1 and start_time<now() ")["data"];
        //2列车发车
        $startTrainProductList = $trainModel->getTrainList(["status"=>2])["data"];
        //3列车到站
        $arriveedTrainpProductList = $trainModel->getTrainList(["status"=>3])["data"];
        //8列车预告
        $trailerTrainpProductList = $trainModel->getTrainList("`status`=1 and start_time>now() ")["data"];
        //9列车暂停
        $pauseTrainProductList = $trainModel->getTrainList(["status"=>9])["data"];
        // var_dump($notBeginTrainProductList);
        // var_dump($beginTrainProductList);exit;
        //查询自己参与哪部列车
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
            }
             foreach ($trailerTrainpProductList as $key=>$train){
                if(in_array($train["train_id"], $userTrains)){
                    $trailerTrainpProductList[$key]["ontrain"] = true;
                }
            }
            
            foreach ($startTrainProductList as $key=>$train){
                if(in_array($train["train_id"], $userTrains)){
                    $startTrainProductList[$key]["ontrain"] = true;
                }
            }
            
            foreach ($arriveedTrainpProductList as $key=>$train){
                if(in_array($train["train_id"], $userTrains)){
                    $arriveedTrainpProductList[$key]["ontrain"] = true;
                }
            }
            
            
            //查询个人收益部分
            $totalRebate = $trainModel->getUserTotalRebate($userId);
            $liveRebate = $trainModel->getUserLiveRebate($userId);
            $this->assign('total_rebate', $totalRebate);
            $this->assign('live_rebate', $liveRebate); 
        }

        if(!empty($trainList)){
            foreach ($trainList as $key0=>$train0){
                  if(in_array($train0["train_id"], $userTrains)){
                    $trainList[$key0]["ontrain"] = true;
                }
                $trainList[$key0]['ratio'] = round(($train0['num']/$train0['total']),3)*100;
                $trainList[$key0]['lefttime'] = calcTime(date("Y-m-d h:i:s",time()), $train0["end_time"]);
            }
        }
         if(!empty($beginTrainProductList)){
             foreach ($beginTrainProductList as $key1=>$train1){
                $beginTrainProductList[$key1]['ratio'] = round(($train1['num']/$train1['total']),3)*100;

                $beginTrainProductList[$key1]['lefttime'] = calcTime(date("Y-m-d h:i:s",time()), $train1["end_time"]);

            }
         }
            if(!empty($arriveedTrainpProductList)){
               foreach ($arriveedTrainpProductList as $key2=>$train2){
                $arriveedTrainpProductList[$key2]['ratio'] = round(($train2['num']/$train2['total']),3)*100;
                $arriveedTrainpProductList[$key2]['lefttime'] = calcTime(date("Y-m-d h:i:s",time()), $train2["end_time"]);
                } 
            }
              if(!empty($trailerTrainpProductList)){
               foreach ($trailerTrainpProductList as $key3=>$train3){
                $trailerTrainpProductList[$key3]['ratio'] = round(($train3['num']/$train2['total']),3)*100;
                $trailerTrainpProductList[$key3]['lefttime'] = calcTime(date("Y-m-d h:i:s",time()), $train3["start_time"]);
                } 
            }

        $this->assign('notbegintrainproductlist', $notBeginTrainProductList); 
        $this->assign('begintrainproductlist', $beginTrainProductList); 

        $this->assign('user_id',$this->user_id);
         $this->assign('trainList', $trainList);
        $this->assign('starttrainproductlist', $startTrainProductList);
        $this->assign("trailerTrainProductList",$trailerTrainpProductList);
        $this->assign('arriveedtrainproductlist', $arriveedTrainpProductList); 
        $this->assign('pausetrainproductlist', $pauseTrainProductList); 
    
        $this->assign("headerContent",L("title_train"));
        $this->assign("yutui",1);
        $this->assign("headBack","/");
        $this->display('train.dwt');
    }
    
    public function mytrain(){
        
        $userId = $_SESSION["user_id"];
        $pager = $this->pageInit($_REQUEST);
        $list = model("Train")->gettrainidbyuser($userId,$pager);
        
        $this->assign("pager",$this->pageShow($pager["total"]));
        
        $this->display("train_my.dwt");
        
        
    }
    
    public function myshares(){
        
        $userId = $_SESSION["user_id"];
        $trainId = $_REQUEST["train_id"];
        if (empty($trainId)){
            return ;
        }
        $pager = $this->pageInit($_REQUEST);
      
        $list = model("Train")->getTrainShares($userId,$trainId,$pager,"user_id");
        
        foreach ($list as $key=>$val){
            $trainUser = model("Train")->getLevel($val["user_id"],$trainId);
            $list[$key]["class_name"] = $trainUser["class_name"];
        }

        $this->assign("trainId",$trainId);
        $this->assign("list",$list);
        $this->assign("pager",$this->pageShow($pager["total"]));
        
        $this->display("train_myshares.dwt");
        
    }


    public function ajax_myshares(){

        $userId = $_SESSION["user_id"];
        $trainId = $_POST['train_id'];

        $size = 2;
        $page = (int)($_POST['page']?$_POST['page']:1);

        $start = ($page-1)*$size;
        
        
        $count = model("Train")->TotalTrainShares($userId,$trainId);


        $listdata = model("Train")->selectTrainShares($userId,$start,$size,$trainId);
         foreach ($listdata as $key=>$val){
            $trainUser = model("Train")->getLevel($val["user_id"],$trainId);
            $listdata[$key]["class_name"] = $trainUser["class_name"];
        }
     
        $totalpage = ceil($count/$size);

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


    
    /**
     * 列車套餐
     */
    public function goods(){
        $trainId = intval(I('get.train_id'));
        
        $train = model("Train")->find(["train_id"=>$trainId]);
        if(empty($train)){
            E("Page Not Found",404);
        }
        $trainDesc = model("TrainDesc")->find(["train_id"=>$trainId]);
        
        
        $shareCode = $_REQUEST["train_invite_code"];
        if (!empty($shareCode)){
            $_SESSION["train_invite_code"] = $shareCode;
            $inviteUser = model("TrainUser")->find(["share_code"=>$shareCode]);
           
            
            if(model("Users")->ifChuangke($inviteUser["user_id"])){
                $_SESSION["tmp_parent_id"] = $inviteUser["user_id"];
            }
             if(empty($inviteUser)||($inviteUser["status"]==1&&$inviteUser["ticket_time"]<time())){
        //        E("該鏈接已失效，請聯繫您的邀請人",404);
            //     show_message(L('signin_failed'), '', url('flow/index', array('step' => 'login')));
                 show_message("該鏈接已失效，請聯繫您的邀請人", '', url('train/index'), 'warning');
            }
            $trainGoods = model("Goods")->select(["train_id"=>$trainId,"goods_id"=>$inviteUser["goods_id"]]);
        }else{
            $trainGoods = model("Goods")->select(["train_id"=>$trainId]);
        }
        foreach ($trainGoods as $key=>$tg){
            $activity = model('GoodsBase')->get_groupbuy_info($tg["goods_id"]);
            $activity["touch_img"] = get_data_path($activity['touch_img'], 'groupbuy');
            $trainGoods[$key]["activity"] = $activity;
        }
        
        $trainStatus = $train["status"];
        if($trainStatus==1&&strtotime($train["start_time"])>time()){
            $trainStatus = 8;
        }
        if($trainStatus!=1){
            $this->assign("error_show",L("train_cannot_buy_{$trainStatus}"));
        }
        $this->assign("trainStatus",$trainStatus);
        
        //1 未登陸 2未上車  3已上車
        $type = 1;
        if($_SESSION["user_id"]){
            $userId = $_SESSION["user_id"];
            
            $trainUser = model("TrainUser")->find(["user_id"=>$userId,"train_id"=>$trainId]);
            if(empty($trainUser))$type=2;
            else $type = 3;
        }else{
            $this->assign("back_act",urlencode('http://'.$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"]));
        }
        
        $shareMeta = array(
            "title" => L("train_share_facebook_title_pre").$train["title"],
            "description"   => $train["description"],
            "image" => $train["img"],
        );
         $u = $inviteUser["user_id"];
         if($u){
            if($inviteUser["user_id"]){

         $_SESSION["tmp_parent_id"] = $inviteUser["user_id"] ;
         
        }
        $invateUser = model("Users")->find("user_id={$u}", "user_name,nick_name,user_avatar");             
        $this->assign('u',$u);
        $this->assign("invite_name", empty($invateUser["nick_name"]) ? $invateUser["user_name"] : $invateUser["nick_name"]); 

         }
        
        $this->assign("user_avatar", $invateUser["user_avatar"] );           
        $this->assign("train",$train);
        $this->assign("buytype",$type);
        $this->assign("headerContent",L("train_goods_name"));
        $this->assign("headBack","/index.php?m=default&c=train&a=info&train_id={$trainId}");
      
        $this->assign("trainDesc",$trainDesc);
        $this->assign("trainGoods",$trainGoods);
        $this->assign("goods",$trainGoods['0']);
        $this->assign("goods_id",$trainGoods['0']['goods_id']);

        
        $this->display('train_goods.dwt');
    }
    
    /* ------------------------------------------------------ */
    //-- 列车详情
    /* ------------------------------------------------------ */
    public function info() {
        
        $userId = $_SESSION["user_id"];
        
        $trainId = intval(I('get.train_id'));

        $train = model('Train')->getTrainDetailByUser($trainId,$userId);

        if(empty($train)){
            ecs_header("Location: ".url('Train/index'));
            return;
        }
        $this->assign('train', $train);
        //dump($article_goods);
        /* 页面标题 */
        $page_info = get_page_title(0, $train["name"]);
        
        $trainModel = model("Train");
        $train = $trainModel->find(["train_id"=>$trainId]);
        $this->assign("train",$train);
        
        
        if (empty($userId)){
            
            $trainGoods = model("Goods")->select(["train_id"=>$trainId]);
            $this->assign("trainGoods",$trainGoods);
            
            $this->display('train_goods.dwt');
        }else{
            /**
             * 
             * @var TrainModel $trainModel
             */
        
            $userLevelInfo = $trainModel->getLevel($userId,$trainId);
         
            if(!empty($userLevelInfo["parent_user_id"])){
                $parentUser = model("Users")->find(["user_id"=>$userLevelInfo["parent_user_id"]]);
                $this->assign("parentUser",$parentUser);
            }
            
            $ticketList = model("TrainTicket")->select(["user_id"=>$userId,"train_id"=>$trainId,"status"=>1]);
            
            $classList = model("TrainClass")->select(["train_id"=>$trainId],'','sort asc');
            
            $ticketTime = date("Y/m/d H:i:s",$userLevelInfo["ticket_time"]);
            
            $goods = model("Goods")->find(["goods_id"=>$userLevelInfo["goods_id"]]);
            
            $activity = model('GoodsBase')->get_groupbuy_info($goods["goods_id"]);
            $activity["touch_img"] = get_data_path($activity['touch_img'], 'groupbuy');
            $goods["activity"] = $activity;
        
            $goods['goods_name'] = mb_substr($goods['goods_name'], 0, 16).'...';
            $this->assign("goods" ,$goods);
            $this->assign("ticketList" ,$ticketList);
            $this->assign("ticketTime",$ticketTime);
            $this->assign("userLevel",$userLevelInfo);
            $this->assign("classList",$classList);
            
            
            $userInfo = model("Users")->get_users($_SESSION["user_id"]);
            
            $mysharesNumber = model("Train")->getTrainSharesNumber($_SESSION["user_id"],$trainId);
            
            $this->assign("mysharesNumber",$mysharesNumber);
            $this->assign("user_info", $userInfo);
            $shortLink = $this->getShortUrl(__URL__."/index.php?c=train&a=goods&train_invite_code={$userLevelInfo["share_code"]}&train_id={$trainId}");
            $this->assign('share_link', $shortLink);//
            $this->assign('share_title', $train["name"]);//
            $this->assign('share_description', $this->formatDescription(htmlspecialchars($train['summary'])));//
            $trainImg = $train['img'];
            if(strpos($trainImg, "http")===false)$trainImg="http://". $_SERVER["HTTP_HOST"]."/".$trainImg;
            
            $this->assign('share_pic',$trainImg );//
            $this->assign('trainId',$trainId);
            $this->assign('headerContent', L("train_info"));//
            $this->assign("headBack","/index.php?m=default&c=train&a=index");
            
            $this->display('train_info.dwt');
            
        }
        
    }
    
    
    /**
     * 执行赠票
     */
    public function giveTicket(){

        $userId = I('request.user_id');
        $ticketId = I("request.ticket_id");
        
        
        $toUser = model("Users")->find(["user_id"=>$userId]);
        if(empty($toUser)){
            $this->jserror("找不到該用戶");
        }
        $ret = model("Train")->giveTicket($_SESSION["user_id"],$ticketId,$toUser["user_id"]);
        
        if(!$ret["status"]){
            $this->jserror($ret["msg"]);
            
        }else{
            $shouzengs =   count(model("Train")->getUserUnactiveTicket($userId));
            self::$cache->setValue("shou_zeng_order_{$userId}",$shouzengs,1800);
            
            $shouzengts =   count(model("Train")->getUserUncheckTicket($userId));
            self::$cache->setValue("shou_zeng_order_t_{$userId}",$shouzengts,1800);
        
            
            $this->jssuccess($ret["data"]);
        }
    }
    public function giveSearch(){
        if(empty($_SESSION["user_id"])){
            $this->jserror("nologin error!");
        }
        $toUsername = I('request.to_username');
        $ticketId = I('request.ticket_id');
        
        $toUser = model("Users")->find(["user_name"=>$toUsername]);
        if(empty($toUser)){
            $toUser = model("Users")->find(["vip_manage_account"=>$toUsername]);
        }
        if(empty($toUser)){
            $this->jserror(L("train_give_error_1"));
        }
        if($toUser["user_id"]==$_SESSION["user_id"]){
            $this->jserror(L("train_give_error_3"));
        }
        
        $ticket = model("TrainTicket")->find(["train_ticket_id"=>$ticketId]);
        $toTrainUser = model("TrainUser")->find(["user_id"=>$toUser["user_id"],"train_id"=>$ticket["train_id"]]);
        
        $data = array("error"=>'');
        if(!empty($toTrainUser)){
            $data["error"] = L("train_give_error_2");
        }
        if(empty($toUser["user_avatar"]))$toUser["user_avatar"]="/themes/yutui/images/new/card_logo.png";
        $data["user"] = ["user_id"=>$toUser["user_id"],"user_avatar"=>$toUser["user_avatar"],"user_name"=>$toUser["user_name"],"nick_name"=>$toUser["nick_name"]];
            
        $this->jssuccess($data);
    }
    
    
    public function level(){
        $trainId = intval(I('get.train_id'));
        $levelList = array();
        $levelList = model("Train")->levelData($trainId,false);
        echo "<pre>";
        print_r($levelList);
        echo "</pre>";
        die;
        $class=array();
        foreach ($levelList as $key =>$user){
            $class[$user["class_name"]][]=$user;
        }
        $classList = model("TrainClass")->select(["train_id"=>$trainId],'','sort asc');
        echo "<table>";
        echo "<tr><td>車廂名</td><td>編碼</td><td>坐席數</td><td>實際人數</td><td>門檻</td><td>普通門檻</td><td>VIP門檻</td></tr>";
        foreach ($classList as $ck => $c){
            echo "<tr><td>{$c["name"]}</td><td>{$c["code"]}</td><td>{$c["total"]}</td><td>{$c["num"]}</td><td>{$c["limit"]}</td><td>{$c["live_limit"]}</td><td>{$c["vip_limit"]}</td></tr>";
        }
        echo "</table>";
        echo "<br>";
        echo "<table>";
        echo "<tr><td>推薦人數</td><td>車廂坐序</td><td>---------最後推薦時間--------</td><td>userid</td><td>-----vip編碼-----</td><td>-预计收益-</td></tr>";
        
        foreach ($class as $k => $cl){
            print_r($cl);
            die;
            echo "<tr><td cosplan='4'>{$k} </td></tr>";
            foreach ($cl as $uk=>$user){
                echo "<tr><td>{$user["share"]}</td><td>{$user["class_ranking"]}</td><td>{$user["last_share_time"]}</td><td>{$user["user_id"]}</td><td>{$user["vip"]}</td><td>{$user["rebate"]}</td></tr>";
            }
        }
        echo "</table>";
        /* echo "<pre>";
        print_r($levelList);
        echo "</pre>"; */
    }
    
    
    
    function adlogin(){
        $session = $this->load_session();
        
        if(empty($session)||empty($session["adminid"])){
            
            $this->redirect("/vmistyleback");
        }
        $trainId = intval(I('get.user_id'));
        $username = I('get.username');
        
        if(!empty($trainId))
            $user = model("Users")->find(["user_id"=>$trainId]);
        elseif(!empty($username)) $user = model("Users")->find(["user_name"=>$username]);
        else {
            exit("unabletoload");
        }
        
        if (empty($user)){
            echo "unknow userid";
            exit();
        }
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user["user_name"];
        $_SESSION['email'] = $user['email'];
        $_SESSION['openid'] = $user['openid'];
        
        $_SESSION['superadminlogin'] = 1;
        //$shouzengorders = model("Train")->getUserUnactiveTicket($_SESSION['user_id']);
        
        $_SESSION['user_rank'] = $user['user_rank'];
        $_SESSION['user_vip'] = $user['user_vip'];
        $_SESSION['resource'] = $user['resource'];
        
        
        echo "ok";
        $this->redirect("/");
    }
    
    function load_session($table_name='',$table_data_mame='',$session_name='ECSCP_ID')
    {
        if(empty($table_name))$table_name = M()->pre . 'sessions' ;
        if(empty($table_data_mame))$table_data_mame = M()->pre . 'sessions_data' ;
        
        $sessionKey = $_COOKIE[$session_name];
        
        if ($sessionKey)
        {
            $tmp_session_id = substr($sessionKey, 0, 32);
            $sessionKey = $tmp_session_id;
        }
        
        $session =  $this->model->getRow('SELECT userid, adminid, user_name, user_rank, discount, email, data, expiry FROM ' . $table_name . " WHERE sesskey = '" . $sessionKey . "'");
        if (empty($session))
        {
            return false;
        }
        else
        {
            if (!empty($session['data']) && $this->_time - $session['expiry'] <= $this->max_life_time)
            {
                $session = array_merge($session,unserialize($session['data']));
            }
            else
            {
                $session_data = $this->model->getRow('SELECT data, expiry FROM ' . $table_data_mame . " WHERE sesskey = '" . $sessionKey . "'");
                if (!empty($session_data['data']) && $this->_time - $session_data['expiry'] <= $this->max_life_time)
                {
                    $session = array_merge($session,unserialize($session_data['data']));
                }
            }
        }
        return $session;
    }
    
    /**
     * 处理参数便于搜索商品信息
     */
    private function parameter() {
        $this->assign('show_asynclist', C('show_asynclist'));
        // 如果分类ID为0，则返回总分类页
        $page_size = 10;
        $this->size = intval($page_size) > 0 ? intval($page_size) : $this->size;
        $this->page = I('request.page') ? intval(I('request.page')) : 1;
        $this->cat_id = intval(I('request.id'));
        $this->keywords = I('request.keywords');
    }
    
    /**
     * 未登錄驗證
     */
    private function check_login() {
        // 不需要登錄的操作或自己驗證是否登錄（如ajax處理）的方法
        $without = array(
            'index',
            'goods',
            'info',
            'adlogin',
            'level'
        );
        // 未登錄處理
        if (empty($_SESSION['user_id']) && !in_array($this->action, $without)) {
            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect(url('user/login', array('back_act' => urlencode($back_act))));
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
    //增票记录表
    public function traingiveorder_log(){

        $userId = $_SESSION["user_id"];
        
        //songchuqude piao
        $getUsersendTicket = model("Train")->getUsersendTicket($userId);
        // $dos = $array();
        // foreach($getUsersendTicket as $key2=>$val2){  
           
        //     $dos[$key2] = $val2['train_ticket_id'];  
        // }  


        // if(!empty($getUsersendTicket)){
        //     array_multisort($dos,SORT_DESC,$getUsersendTicket);  
        // }
            

        foreach ($getUsersendTicket as $key => $value) {
             $userinfo = model('Users')->get_users($value['user_id']);
             $getUsersendTicket[$key]['user_name'] = $userinfo['user_name'];
             $getUsersendTicket[$key]['nick_name'] = $userinfo['nick_name'];
             $getUsersendTicket[$key]['create_time'] = strtotime($getUsersendTicket[$key]['create_time']);
            // $getUsersendTicket[$key]['create_time'] =  date("Y-m-d h:i",$getUsersendTicket[$key]['create_time']);
             if($getUsersendTicket[$key]['give_time']){
                $getUsersendTicket[$key]['give_time'] =  date("Y-m-d h:i",$getUsersendTicket[$key]['give_time']);
            }else{
                $getUsersendTicket[$key]['give_time'] =  0;
            }
             
           
            # code...
        }
        //收到的票
         $getUserReceivesendTicket = model("Train")->getUserReceivesendTicket($userId);
        //   foreach($getUserReceivesendTicket as $key21=>$val21){  
           
        //     $dos[$key21] = $val21['train_ticket_id'];  
        // }  
        
        // if($getUserReceivesendTicket){
            
        //  array_multisort($dos,SORT_DESC,$getUserReceivesendTicket);  
        // }

        foreach ($getUserReceivesendTicket as $key1 => $value1) {

             $userinfo1 = model('Users')->get_users($value1['buyer_id']);
             $getUserReceivesendTicket[$key1]['user_name'] = $userinfo1['user_name'];
             $getUserReceivesendTicket[$key1]['nick_name'] = $userinfo1['nick_name'];
             $getUserReceivesendTicket[$key1]['create_time'] = strtotime($getUserReceivesendTicket[$key1]['create_time']);
             $getUserReceivesendTicket[$key1]['create_time'] =  date("Y-m-d h:i",$getUserReceivesendTicket[$key1]['create_time']);
            //$getUserReceivesendTicket[$key1]['give_time'] =  date("Y-m-d h:i",$getUserReceivesendTicket[$key1]['give_time']);
                  if($getUserReceivesendTicket[$key1]['give_time']){
                $getUserReceivesendTicket[$key1]['give_time'] =  date("Y-m-d h:i",$getUserReceivesendTicket[$key1]['give_time']);
            }else{
                $getUserReceivesendTicket[$key1]['give_time'] =  0;
            }

            
            # code...
        }


        

        $this->assign("getUsersendTicket",$getUsersendTicket);
        $this->assign("getUserReceivesendTicket",$getUserReceivesendTicket);
        $this->display('traingiveorder_log.dwt');
    }

}
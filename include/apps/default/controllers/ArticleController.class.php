<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ArticleControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：文章控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ArticleController extends CommonController {

    private $size = 10;
    private $page = 1;
    private $cat_id = 0;
    private $keywords = '';

    public function __construct() {
        parent::__construct();
        
        
        
        $this->cat_id = intval(I('get.id'));
        //$this->assign('share_link', __URL__.$_SERVER['REQUEST_URI']);//
        // $this->assign('share_title',  C('shop_title'));//
        $this->assign('share_description', $this->formatDescription(C('shop_desc')));//
        $this->assign('share_pic', __URL__."/themes/yutui/images/new/yutui_logo.png");

    }

    /* ------------------------------------------------------ */

    //-- 文章分类
    /* ------------------------------------------------------ */
    public function index() {
        $userInfo = model("Users")->get_users($_SESSION["user_id"]);
          $uid = empty($_POST["u"])?(empty($_SESSION["user_id"])?0:$_SESSION["user_id"]):$_GET["u"];
        if($userInfo){
            //登录的情况根据后台设置的文章来源判断哪些用户可以看
            $sql1 = 'SELECT * from '. '{pre}article_cat '.' as ac inner join {pre}article_resource as ar on ac.cat_id = ar.cat_id AND ar.resource_type='.$userInfo['resource'];
          

            
            $res = $this->model->query($sql1);

            foreach($res as $key1=>$vo1){

                $allowcat[] = $vo1['cat_id'];

            }

        }else{
            //不登录的情况，默认只能看拓客商城来源的文章
            //  $sql = 'SELECT cat_id, cat_name' .
            // ' FROM {pre}article_cat ' .
            // ' WHERE cat_type = 1 AND parent_id = 4'.
            // ' ORDER BY sort_order ASC';
            $sql1 = 'SELECT * from '. '{pre}article_cat '.' as ac inner join {pre}article_resource as ar on ac.cat_id = ar.cat_id AND ar.resource_type=1';
            
            $res = $this->model->query($sql1);

            foreach($res as $key1=>$vo1){

                $allowcat[] = $vo1['cat_id'];

            }

        }
            $resource_type = empty($userInfo['resource'])?1:$userInfo['resource'];
        /*获取允许显示的cat_id数组*/
        $this->parameter();
        
        
        $type = I('request.type','default');
        $id = $this->cat_id;
        $this->assign('keywords', $this->keywords);
        $this->assign('id', $id);
        $this->assign("type",$type);
        $sql = 'SELECT cat_id, cat_name' .
            ' FROM {pre}article_cat ' .
            ' WHERE cat_type = 1 AND show_in_nav = 1 AND parent_id = 4'.
            ' ORDER BY sort_order ASC ';
        $data = $this->model->query($sql);

        
     
        foreach($data as $key=>$vo){
            //查询cat_id是否是允许显示的
            //var_dump($allowcat);exit;
            if(in_array($data[$key]['cat_id'], $allowcat)){
               $data[$key]['url'] = url('index', array('id'=>$vo['cat_id'])); 
           }else{
                unset($data[$key]);
           }
     
            
        }
       

       //var_dump($data);exit();
        
       if(empty($this->cat_id))$this->cat_id=current($data)['cat_id'];
 
        $artciles_list = model('ArticleBase')->get_cat_articles($this->cat_id, $this->page, $this->size, $this->keywords,$type,$resource_type);

        $count = model('ArticleBase')->get_article_count($this->cat_id, $this->keywords);
        $this->pageLimit(url('index', array('id' => $this->cat_id)), $this->size);
        if(empty($_GET['id'])){
            $id = reset($data)['cat_id'];
        }
    
        $sql1 = 'SELECT * from '. '{pre}article_cat '.' as ac inner join {pre}article_resource as ar on ac.cat_id = ar.cat_id AND ac.show_in_nav = 1 AND ac.parent_id='.$id.' AND ar.resource_type='.$resource_type;
         
           
        $subdata = $this->model->query($sql1); 
        if(!$subdata){
            $sql3 = 'SELECT parent_id' .
            ' FROM {pre}article_cat ' .
            ' WHERE  cat_id = '.$id;
           
            $data3 = $this->model->query($sql3);
            if($data3['0']['parent_id']==4){
                
            }else{
                $sql2 = 'SELECT * from '. '{pre}article_cat '.'where cat_id='.$id;
        $parentinfo = $this->model->query($sql2); 
   
        $parentid  = $parentinfo['0']['parent_id'];
                $sql1 = 'SELECT * from '. '{pre}article_cat '.' as ac inner join {pre}article_resource as ar on ac.cat_id = ar.cat_id  AND ac.show_in_nav = 1 AND ac.parent_id='.$parentid.' AND ar.resource_type='.$resource_type;
         
           
                     $subdata = $this->model->query($sql1); 
            }
            
            
        }
       
        //parentid获取当前id的上一级id
     
        $sql2 = 'SELECT * from '. '{pre}article_cat '.'where cat_id='.$id;
        $parentinfo = $this->model->query($sql2); 
   
        $parentid  = $parentinfo['0']['parent_id'];

    
        //var_dump($data);exit();
        $this->assign('cat_id',$this->cat_id);
        $this->assign('id',$id);
        $this->assign('parentid',$parentid);
        $this->assign('pager', $this->pageShow($count));
        $this->assign('artciles_list', $artciles_list);

        $this->assign('subdata',$subdata);
         // $this->assign('share_link', urlencode(__URL__ . $_SERVER['REQUEST_URI']));//
         $this->assign('share_title',  C('shop_title'));//
         $this->assign('share_description', $this->formatDescription(C('shop_desc')));//
         $this->assign('share_pic', __URL__."/themes/yutui/images/new/yutui_logo.png");//
         
        $this->assign('article_categories', $data); //文章分类树
        
        //底部导航高亮
        $this->assign('footer_index','article');
        
        $this->display('article_cat.dwt');
    }

    /* ------------------------------------------------------ */
    //-- 文章列表
    
    public function article_index(){

       $res = model('Article')->sucaiList(0);
  if($res){
     foreach ($res as $key => $value) {
           # code...
           if($value['sucai_type']==2){
            $res[$key]['imglist'] = explode(";", $value['sucai_picture']);
           }
           if(!empty($value['sucai_href'])){
                $hrefinfo = model('Article')->hrefinfo($value['sucai_href']);
                $res[$key]['pic'] = $hrefinfo['pic'];
                $res[$key]['file_url'] = $hrefinfo['file_url'];
                $res[$key]['title'] = $hrefinfo['title'];
           }
     
           //$goodsinfo=  model('Goods')->get_goods_info($value['goods_id']);
           $result= model('Article')->talkinfo($value['talk_id']);
           $res[$key]['talk'] = $result['title'];
           $res[$key]['change_time'] = date("Y-m-d",$value['change_time']);
           // $res[$key]['goods_thumb'] =$goodsinfo['goods_thumb'];
           // $res[$key]['goods_name'] =$goodsinfo['goods_name'];
           // $res[$key]['goods_href'] =__URL__ . '/index.php?m=default&c=goods&a=index&id='.$value['goods_id'].'&u=' . $_SESSION['user_id'];
       }
  }
      
     
        
        $restalk = model("Article")->talkList();

        $this->assign('talk_list',$restalk);

        $this->assign('sucai_list',$res);  
        
        //底部导航高亮
        $this->assign('footer_index','dongtai');
        
        $this->display('talk_sucai.dwt');
    }

    public function topic_of_conversation(){
         
           $restalk = model("Article")->talkList();

           foreach ($restalk as $key => $value) {
               # code...
               $restalk[$key]['total'] = model('Article')->totalsucaiList($value['talk_id']);
             
           }
           $this->assign('footer_index','dongtai');
           $this->assign('talk_list',$restalk);
           
            
        $this->display('topic_of_conversation.dwt');
    }
   //-- zz专题文章
     public function special() {
        $talk_id = isset($_REQUEST ['talk_id']) ? intval($_REQUEST ['talk_id']) : 0;
        $sortby = isset($_REQUEST ['sortby']) ? $_REQUEST ['sortby'] : "sort";
       $res = model('Article')->sucaiList($talk_id,$sortby);

        //var_dump($res);exit;
         $totalsucai = model('Article')->totalsucaiList($talk_id);
         if($res){
             foreach ($res as $key => $value) {
           # code...
           if($value['sucai_type']==2){
            $res[$key]['imglist'] = explode(";", $value['sucai_picture']);
           }
           if(!empty($value['sucai_href'])){
                $hrefinfo = model('Article')->hrefinfo($value['sucai_href']);
                $res[$key]['pic'] = $hrefinfo['pic'];
                $res[$key]['file_url'] = $hrefinfo['file_url'];
                $res[$key]['title'] = $hrefinfo['title'];
              
           }
          $res[$key]['justtime'] = date("Y-m-d",$value['change_time']);
           //$goodsinfo=  model('Goods')->get_goods_info($value['goods_id']);
           $result= model('Article')->talkinfo($value['talk_id']);
           $res[$key]['talk'] = $result['title'];

           // $res[$key]['goods_thumb'] =$goodsinfo['goods_thumb'];
           // $res[$key]['goods_name'] =$goodsinfo['goods_name'];
           // $res[$key]['goods_href'] =__URL__ . '/index.php?m=default&c=goods&a=index&id='.$value['goods_id'].'&u=' . $_SESSION['user_id'];
            }
         }
      

           //var_dump($res);exit; 
        $resnew =model('Article')->talkinfo($talk_id);

        $title = $resnew['title'];
        $content = $resnew['content'];
        $talk_banner = $resnew['talk_banner'];
        $restalk = model("Article")->talkList();

        $timeurl = url('article/special', array(
                    'talk_id' => $talk_id,'sortby'=>'time'
                ));
        $this->assign('newtalk_id',$_GET['talk_id']);
        $this->assign('timeurl',$timeurl);
        $this->assign('talk_list',$restalk);
        $this->assign('talk_banner',$talk_banner);
        $this->assign('sucai_list',$res);  
        $this->assign('totalsucai',$totalsucai);
        $this->assign('title',$title); 
        $this->assign('content',$content);   
        
        $this->assign('footer_index','dongtai');
        
        $this->display('special.dwt');
    }
    public function art_list() {
        $this->parameter();
        $this->assign('keywords', $this->keywords);
        $this->assign('id', $this->cat_id);
        $artciles_list = model('ArticleBase')->get_cat_articles($this->cat_id, $this->page, $this->size, $this->keywords);
        $count = model('ArticleBase')->get_article_count($this->cat_id, $this->keywords);
        $this->pageLimit(url('art_list', array('id' => $this->cat_id)), $this->size);
        $this->assign('pager', $this->pageShow($count));
        $this->assign('artciles_list', $artciles_list);

        //处理关键词描述
        $sql = "select * from ".M()->pre."article_cat where cat_id = ".$this->cat_id;
        $cat = M()->query($sql);
        if (!empty($cat['0']['keywords'])) {
            $this->assign('meta_keywords',htmlspecialchars($cat['0']['keywords']));
        }
        if (!empty($cat['0']['cat_desc'])) {
            $this->assign('meta_description',htmlspecialchars($cat['0']['cat_desc']));
        }

        $this->display('article_list.dwt');
    }
 
    public function time_ago($posttime){
     //当前时间的时间戳
    $nowtimes = time();
    //之前时间参数的时间戳
    $posttimes = $posttime;
  
    //相差时间戳
    $counttime = $nowtimes - $posttimes;

    //进行时间转换
    if($counttime<=10){
    
       return '刚刚';
        
    }else if($counttime>10 && $counttime<=30){
    
       return '刚才';
        
    }else if($counttime>30 && $counttime<=60){
    
        return '刚一会';
        
    }else if($counttime>60 && $counttime<=120){
    
       return '1分钟前';
        
    }else if($counttime>120 && $counttime<=180){
    
       return '2分钟前';
        
    }else if($counttime>180 && $counttime<3600){
    
       return intval(($counttime/60)).'分钟前';
        
    }else if($counttime>=3600 && $counttime<3600*24){
    
       return intval(($counttime/3600)).'小时前';
        
    }else if($counttime>=3600*24 && $counttime<3600*24*2){
    
       return '昨天';
        
    }else if($counttime>=3600*24*2 && $counttime<3600*24*3){
    
       return '前天';
        
    }else if($counttime>=3600*24*3 && $counttime<=3600*24*20){
    
       return intval(($counttime/(3600*24))).'天前';
        
    }else{
    
       return $posttime;
        
    }
}
    
    public function notice(){
     
        $catid = 19;
        $userInfo = model("Users")->get_users($_SESSION["user_id"]);
        $resource_type = empty($userInfo['resource'])?1:$userInfo['resource'];
        $this->parameter();
        $artciles_list = model('ArticleBase')->get_cat_articles($catid, $this->page, $this->size,'','default',$resource_type);
        $count = model('ArticleBase')->get_article_count($catid);
        $this->pageLimit(url('art_list', array('id' =>$catid)), $this->size);
        $this->assign('pager', $this->pageShow($count));
        $this->assign('notice_list', $artciles_list);
        
        $this->display('notice_list.dwt');
    }

    /**
     * 文章列表异步加载
     */
    public function asynclist() {
        $this->parameter();
        $asyn_last = intval(I('post.last')) + 1;
        $this->size = I('post.amount');
        $this->page = ($asyn_last > 0) ? ceil($asyn_last / $this->size) : 1;
        $list = model('ArticleBase')->get_cat_articles($this->cat_id, $this->page, $this->size, $this->keywords);
        $id = ($this->page - 1) * $this->size + 1;
        foreach ($list as $key => $value) {
            $this->assign('id', $id);
            $this->assign('article', $value);
            $sayList [] = array(
                'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
            );
            $id++;
        }
        die(json_encode($sayList));
        exit();
    }
       /**
     * 文章列表异步加载
     */
    public function ajax_asynclist() {

        $cat_id = I('post.cat_id');
        $this->size = 10;
        $this->page = I('post.page');
        $type = I('post.type')?I('post.type'):"default";
        $list = model('ArticleBase')->get_cat_articles($cat_id, $this->page, $this->size, $this->keywords,$type);

        $i = 0;
         if (is_array($list)) {
            foreach ($list as  $key => $value) {

                
              
                $list[$key]['short_title'] = C('article_title_length') > 0 ? sub_str($list[$key]['title'], C('article_title_length')) : $list[$key]['title'];
                $list[$key]['author'] = empty($list[$key]['author']) || $list[$key]['author'] == '_SHOPHELP' ? C('shop_name') : $list[$key]['author'];
                $list[$key]['url'] = $list[$key]['link'] && $list[$key]['link'] !='http://' ?  $list[$key]['link'] : url('article/info', array('aid' => $list[$key]['article_id'])) ;
                $list[$key]['add_time'] = date(C('date_format'), $list[$key]['add_time']); 


                
            }
           
        }

        $count = model('ArticleBase')->get_article_count($cat_id, $this->keywords);

         $countpage = ceil($count/$this->size)+1;
         $result['totalpage'] = $countpage;
        $result['list'] = $list;
     
        die(json_encode($result));
        exit();
    }

    /* ------------------------------------------------------ */
    //-- 文章详情
    /* ------------------------------------------------------ */
    public function info() {
        /* 文章详情 */
        $article_id = intval(I('get.aid'));
        $article = model('Article')->get_article_info($article_id);
        $parentids = model('Article')->get_article_parent_cats($article['cat_id']);


          $uid = $_GET["u"]?$_GET["u"]:$_SESSION["user_id"];

        $isdongtai = 1 ;

        if($article['cat_id'] ==18 || $article['cat_id'] ==19|| $article['cat_id'] ==14|| $article['cat_id'] ==15|| $article['cat_id'] ==16|| $article['cat_id'] ==17){
                $isdongtai = 0 ;
        }

        $this->assign('article', $article);
        
        $article_goods = model('Article')->get_article_goods($article_id);
        $this->assign('isdongtai',$isdongtai);
        $this->assign('article_goods',$article_goods);
        //dump($article_goods);
        
             
        if(!empty($uid)){
           $shareUser = $this->assginUserCard($uid);
           
           $userinfo = model('Users')->get_users($uid);
           
            //非动态栏目下面的文章只显示原作者
           if($userinfo['user_vip']){
            $this->assign('vip',1);
           }else{
            $this->assign('vip',0);
           }

           $this->assign('shareUser',$shareUser);
        }else{
            $this->assign('vip',0);
        }
       

        if($uid==$_SESSION["user_id"]&&$userinfo['user_vip']){
            //分享控制
            
            //$this->assign('share_link', "http://".$_SERVER["HTTP_HOST"]."/index.php?c=train&a=goods&train_invite_code={$userLevelInfo["share_code"]}&train_id={$trainId}");//
            $this->assign("showShare",true);
            $userInfo = model("Users")->get_users($_SESSION["user_id"]);
            $this->assign("user_info", $userInfo);
            
           
            //$shortUrl = $this->getShortUrl($this->get_current_url());
            //$this->assign('share_link', $shortUrl);//
            
            
            
           
            
            
            
        }else{
           
            $this->assign("user_info", $shareUser);
        }
         if (!empty($article['file_url'])) {
                $article_img = $article['file_url'];
            } else {
                $article_img = $article['album'][0]; // 文章内容第一张图片
            }
        $this->assign('share_title', htmlspecialchars($article['title']));//
            $this->assign('share_description', empty($article['description'])?$this->formatDescription(C('shop_desc')):$this->formatDescription(htmlspecialchars($article['description'])));//
         $shareMeta = array(
                "title" => htmlspecialchars($article['title']),
                "description"   => $this->formatDescription(htmlspecialchars($article['description'])),
                "image" => $article_img,
            );
            $this->assign("shareMeta",$shareMeta);
           
        $this->assign('share_pic', empty($article_img)?__URL__."/themes/yutui/images/new/yutui_logo.png":$article_img);//
        $this->assign("showcard", model("Article")->isShowcardCat($article['cat_id']));
        $this->assign('u',empty($uid)?0:$uid); 
        /* 页面标题 */
        $page_info = get_page_title($article['cat_id'], $article['title']);
        $this->assign('page_title', htmlspecialchars($page_info['title']));
        /* meta */
        $this->assign('meta_keywords', htmlspecialchars($article['keywords']));
        $this->assign('meta_description', htmlspecialchars($article['description']));


        // $this->model->query('UPDATE ' . $this->model->pre . "article SET click_count = click_count + 1 WHERE article_id = '$article_id'");
        
        // 微信JSSDK分享
         if (!empty($article['file_url'])) {
            $article_img = get_image_path($article['file_url']);
        } else {
            $article_img = $article['album'][0]; // 文章内容第一张图片
        }
        $share_data = array(
            'title' => $article['title'],
            'desc' => $article['description'],
            'link' => '',
            'img' => $article_img,
        );
        $this->assign('share_data', $this->get_wechat_share_content($share_data));


        
        $this->assign('article_id',$article_id);
        
        //底部导航高亮
        $this->assign('footer_index','article');
        
        $this->display('article_info.dwt');
    }
    function updateviewguest()
    {
        // $data_u['viewguest'] = $_POST['num']  ;

         $article_id = $_POST['article_id']  ;

        // $r = $this->model->table('users')
        //                     ->data($data_u)
        //                     ->where($where_u)
        //                     ->update();

        $r = $this->model->query('UPDATE ' . $this->model->pre . "article SET click_count = click_count + 1 WHERE article_id = '$article_id'");
        $article = model('Article')->get_article_info($article_id);

        if($r){
            echo json_encode(array('code' => 200,"num"=>$article['click_count']));exit;
        }else{
            echo json_encode(array('code' => 500));exit;
        }
    }
    function updatereffer()
    {
        // $data_u['viewguest'] = $_POST['num']  ;

        

        $uid = $_POST["u"];
        
        if($uid!==$_SESSION["user_id"]){
           
             $r = model('Users')->updateRefferArticle($uid);
             
          
                $data = $this->model->table('reffer_article')->field('num')->where('user_id = ' . $uid)->find();
                $userinfo = $this->model->table('users')->field('update_reffer_article_time')->where('user_id = ' . $uid)->find();
                 if($data['num']==3){
                    /*转发动态文章获取5KD豆*/
                    /*查看该会员今天的任务完成了没，如果完成了，就不需要加KD豆值*/
                    $timestamp = time();
                    if(date('Ymd', $timestamp) != date('Ymd',$userinfo['update_reffer_article_time'])){

                        $r = $this->model->query('UPDATE ' . $this->model->pre . "users SET update_reffer_article_time =".time()."  WHERE user_id = '$uid'");
                        
                        //model('ClipsBase')->log_account_change($uid, 0, 0, 0, 5, "好友阅读分享文章获取鱼宝", ACT_KD);  
                        model('ClipsBase')->new_log_account_change($uid, 5,"好友阅读分享文章获取鱼宝",ACT_KD, 6);  
                    }
                    
                    //model('Users')->updateTask($uid,5,0,0);

                 }
            
             
         
        }
    }
       function updatezannum()
    {
        $data_u['zan'] = $_POST['num'] ;
        $where_u['article_id'] = $_POST['article_id'] ;

        
        $r = $this->model->table('article')
                            ->data($data_u)
                            ->where($where_u)
                            ->update();
        if($r){
            echo json_encode(array('code' => 200));exit;
        }else{
            echo json_encode(array('code' => 500));exit;
        }
    }
    public function help(){
        $this->parameter();
        $this->assign('keywords', $this->keywords);
        $this->assign('id', $this->cat_id);
        if(empty($this->cat_id))$this->cat_id = 13;
        
        
        $artciles_list = model('ArticleBase')->get_cat_articles($this->cat_id, $this->page, $this->size, $this->keywords);
        $i = 0;
         if (is_array($artciles_list)) {
            foreach ($artciles_list as  $key => $value) {

                
              
                $artciles_list[$key]['short_title'] = C('article_title_length') > 0 ? sub_str($artciles_list[$key]['title'], C('article_title_length')) : $artciles_list[$key]['title'];
                $artciles_list[$key]['author'] = empty($artciles_list[$key]['author']) || $artciles_list[$key]['author'] == '_SHOPHELP' ? C('shop_name') : $artciles_list[$key]['author'];
                $artciles_list[$key]['url'] = $artciles_list[$key]['link'] && $artciles_list[$key]['link'] !='http://' ?  $artciles_list[$key]['link'] : url('article/info', array('aid' => $artciles_list[$key]['article_id'])) ;
                $artciles_list[$key]['add_time'] = date(C('date_format'), $artciles_list[$key]['add_time']); 


                
            }
           
        }
        $count = model('ArticleBase')->get_article_count($this->cat_id, $this->keywords);
        $this->pageLimit(url('help', array('id' => $this->cat_id)), $this->size);
        $this->assign('pager', $this->pageShow($count));
        $this->assign('help_list', $artciles_list);

       

        //处理关键词描述
        $sql = "select * from ".M()->pre."article_cat where cat_id = ".$this->cat_id;
        $cat = M()->query($sql);
        if (!empty($cat['0']['keywords'])) {
            $this->assign('meta_keywords',htmlspecialchars($cat['0']['keywords']));
        }
        if (!empty($cat['0']['cat_desc'])) {
            $this->assign('meta_description',htmlspecialchars($cat['0']['cat_desc']));
        }
     
        $this->display('article_help.dwt');
    }
    
    /* ------------------------------------------------------ */
    //-- 微信图文详情
    /* ------------------------------------------------------ */
    public function wechat_news_info() {
        /* 文章详情 */
        $news_id = I('get.id', 0, 'intval');
        $data = $this->model->table('wechat_media')->field('title, content, file, is_show, digest')->where('id = ' . $news_id)->find();
        $data['content'] = htmlspecialchars_decode($data['content']);
        $data['image'] =  $data['is_show'] ? __URL__ . '/' . $data['file'] : '';
        $this->assign('article', $data);
        $this->assign('page_title', $data['title']);
        $this->assign('meta_keywords', $data['title']);
        $this->assign('meta_description', strip_tags($data['digest']));

        // 微信JSSDK分享
        $share_data = array(
            'title' => $data['title'],
            'desc' => strip_tags($data['digest']),
            'link' => '',
            'img' => get_image_path($data['file']),
        );
        $this->assign('share_data', $this->get_wechat_share_content($share_data));

        $this->display('article_info.dwt');
    }

    /**
     * 处理参数便于搜索商品信息
     */
    private function parameter() {
        $this->assign('show_asynclist', C('show_asynclist'));
        // 如果分类ID为0，则返回总分类页
        $page_size = C('article_number');
        $this->size = intval($page_size) > 0 ? intval($page_size) : $this->size;
        $this->page = I('request.page') ? intval(I('request.page')) : 1;
        $this->cat_id = intval(I('request.id'));
        $this->keywords = I('request.keywords');
    }
    
    private function assginUserCard($uid){
        $user_info = model('Users')->getusermainpagebyuserid($uid);
    
        $userInfo = model("Users")->get_users($uid);
        $user_info['user_avatar'] = $userInfo['user_avatar'];
        $user_info['nick_name'] = $userInfo['nick_name'];
        $user_info['autonym'] = $userInfo['autonym'];
        $user_info['user_name'] = $userInfo['user_name'];
        $shareUser =$user_info;
     
       
        return $shareUser;

    }
    public function sendwithdrawmsg()
    {

      
        $amount = $_POST['amount'];
        $user_id = $_POST['user_id'];
        $add_time = date("Y-m-d H:i:s",time()); 
        $pushData = array(
               
                            'first' => array('value' => '您申请的提现金额已到帐','color' => '#173177'),
                            'keyword1' => array('value' => $add_time,'color' => '#000'),  // 订单号
                            'keyword2' => array('value' => '银行卡转账','color' => '#000'),   // 付款状态
                            'keyword3' => array('value' => $amount,'color' => '#000'),  //付款时间
                            'keyword4' => array('value' => number_format(filter_money($amount*0.05,2),2),'color' => '#000'),       // 商户名
                            'keyword5' => array('value' =>  number_format(filter_money($amount*0.95,2),2),'color' => '#000'),             // 付款金额
                            'remark' => array('value' => '点击查看详情,如有疑问请联系平台客服','color' => '#173177')
                        );
                        // $url = __URL__ . 'index.php?c=user&a=order_detail&order_id='.$order_id;
                        $url = __HOST__ . "/index.php?m=default&c=user&a=account_log";
                       
                        $r = pushTemplate('OPENTM406474850', $pushData, $url, $user_id);
                       
    }
    

}
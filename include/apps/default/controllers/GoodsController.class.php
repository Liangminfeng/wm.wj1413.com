<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：GoodsControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：商品详情控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class GoodsController extends CommonController
{

    protected $goods_id;

    /**
     * 构造函数   加载user.php的语言包 并映射到模版
     */
    public function __construct()
    {
        parent::__construct();
        $this->goods_id = isset($_REQUEST ['id']) ? intval($_REQUEST ['id']) : 0;

    }

    /**
     *  商品详情页
     */
    /**
     * @throws Exception
     */
    public function index()
    {

     
        $r = judgeResoure($this->goods_id,isset($_SESSION['resource'])?$_SESSION['resource']:0);

        if(!$r){
               $this->redirect(url('index/index'));
        }
        /*通过get方式获取到的order_type用来存储入团的属性值*/
        $order_type = isset($_GET['order_type'])?$_GET['order_type']:0;
        //这是首页
        if($_REQUEST ['u']){

         $_SESSION["tmp_parent_id"] = $_REQUEST ['u'] ;
         
        }
        // 获得商品的信息
        $goods = model('Goods')->get_goods_info($this->goods_id);
        
        $result = explode(",", "1,3,4,5");
        /*仅能购买一件商品的限制判断,非重消的商品只能购买一件,非重消的商品不能进入购物车列表,重消的商品可以进入购物车列表*/
        
        $goods_area = explode(",", $goods['goods_area']);
        $rtcat_id = explode(",", $goods['rtcat_id']);
        // if(in_array("1", $goods['goods_area'])||in_array("2", $goods['goods_area']))
        // {
        //     if(in_array("2", $goods['goods_area'])){
        //         $order_type = 9;
        //     }
        //          /*如果符合上面条件说明只能购买一件*/
        //     if(in_array("1", $goods['goods_area'])){
        //       $this->assign('allowbuyonlyone',1);  
        //   }else{
        //       $this->assign('allowbuyonlyone',0);
        //   }
             

        //      $this->assign('cartlist',0);
            
           
        // }else{
        //     $this->assign('allowbuyonlyone',0);
        //     $this->assign('cartlist',1);
        // }
        /*20190621店主套餐可以购买多件,入团只有重消类型的可以购买多件,这两种都没有购物车*/
        /*是否只允许购买一件*/

        $allowbuyonlyone = 0 ;
        /*是否显示加入购物车*/
        $cartlist = 1;

        if($order_type){
            if($order_type ==1||$order_type ==3||$order_type ==4||$order_type ==5){
                $allowbuyonlyone  =1 ;

            }else{
                $allowbuyonlyone =0 ;
            }
            if($order_type ==1||$order_type ==3||$order_type ==4||$order_type ==5||$order_type ==9||$order_type ==10){
                 $cartlist = 0;
                

            }else{
                 $cartlist = 1;
                 
            }
        }else{
            if(in_array("2", $goods_area)){

                $order_type = 9;
                $cartlist = 0;
                
            }else{
                $order_type = 0;
                $cartlist = 1;
            }
        }
        

        $this->assign('goods_area',$goods_area);
        $this->assign('rtcat_id',$rtcat_id);
        $this->assign('allowbuyonlyone',$allowbuyonlyone);
        $this->assign('cartlist',$cartlist);
        $this->assign('train_id', $goods['train_id']);
        //购物车商品数量

        $cart_goods = insert_cart_info_number();
        
        $this->assign('seller_cart_total_number', $cart_goods);
		//获取qq客户号码
		$shop_qq = $this->model->table('shop_config')->field('value')->where(array("code"=>qq))->getOne();
		if($shop_qq){
			$infoqq['centent'] = explode(',',$shop_qq);
		}
		$this->assign('shop_qq', $infoqq);
        // 如果没有找到任何记录则跳回到首页
        if ($goods === false) {
            ecs_header("Location: ./\n");
        } else {
            if ($goods ['brand_id'] > 0) {
                $goods ['goods_brand_url'] = url('brand/index', array('id' => $goods ['brand_id']));
            }
            $shop_price = $goods ['shop_price'];
            $linked_goods = model('Goods')->get_related_goods($this->goods_id);
            $goods ['goods_style_name'] = add_style($goods ['goods_name'], $goods ['goods_name_style']);

            // 购买该商品可以得到多少钱的红包
            if ($goods ['bonus_type_id'] > 0) {
                $time = time();
                $condition = "type_id = '$goods[bonus_type_id]' " . " AND send_type = '" . SEND_BY_GOODS . "' " . " AND send_start_date <= '$time'" . " AND send_end_date >= '$time'";
                $count = $this->model->table('bonus_type')->field('type_money')->where($condition)->getOne();

                $goods ['bonus_money'] = floatval($count);
                if ($goods ['bonus_money'] > 0) {
                    $goods ['bonus_money'] = price_format($goods ['bonus_money']);
                }
            }
            $comments = model('Comment')->get_comment_info($this->goods_id, 0);
            if($_SESSION['user_vip']){

              if($goods['vipintegral']==0){
            
                $goods ['shop_price'] = price_format($goods['shop_price']-$goods['vipintegral']);
              }else{

                 $goods ['shop_price'] = price_format($goods['shop_price']-$goods['vipintegral'])."+".$goods['vipintegral']."鱼宝";
              }
               
            }else{
             
                $goods ['shop_price'] = price_format($goods['shop_price'],false);
            }
                    
            $goods ['goods_brief'] = str_replace(array("\r\n", "\r", "\n"), "", $goods['goods_brief']); 
            /*判断是不是重消，如果是重消的产品可以进入购物车列表购买多件，其他入团的产品只能购买一件，并跳过购物车列表*/


           
            if(strlen($goods['goods_name'])>26){
                $goods['goods_name'] = mb_substr($goods['goods_name'],0,26,"utf-8")."...";
            }else{
                $goods['goods_name'];
            }

            $this->assign('goods', $goods);
            /*购物类型.入团,重消，重购，零售*/
            $this->assign('order_type',$order_type);
            $this->assign('comments', $comments);
            $this->assign('goods_id', $goods ['goods_id']);
            $this->assign('promote_end_time', $goods ['gmt_end_time']);
            // 获得商品的规格和属性
            $properties = model('Goods')->get_goods_properties($this->goods_id);
            // 商品属性
            $this->assign('properties', $properties ['pro']);
            // 商品规格
            $this->assign('specification', $properties ['spe']);
            // 相同属性的关联商品
            $this->assign('attribute_linked', model('Goods')->get_same_attribute_goods($properties));
            // 关联商品
            $this->assign('related_goods', $linked_goods);
            // 关联文章
            $this->assign('goods_article_list', model('Goods')->get_linked_articles($this->goods_id));
            // 配件
            $this->assign('fittings', model('Goods')->get_goods_fittings(array($this->goods_id)));
            // 会员等级价格
            
            $this->assign('rank_prices', model('Goods')->get_user_rank_prices($this->goods_id, $shop_price));
            // 商品相册
            $this->assign('pictures', model('GoodsBase')->get_goods_gallery($this->goods_id));
            // 获取关联礼包
            $package_goods_list = model('Goods')->get_package_goods_list($goods ['goods_id']);
            $this->assign('package_goods_list', $package_goods_list);
            //取得商品优惠价格列表
            $this->assign('show_goodsnumber',C('show_goodsnumber')?1:0);
            $volume_price_list = model('GoodsBase')->get_volume_price_list($goods ['goods_id'], '1');
            // 商品优惠价格区间
            $this->assign('volume_price_list', $volume_price_list);
        }

        // 检查是否已经存在于用户的收藏夹
        if ($_SESSION ['user_id']) {
            $where['user_id'] = $_SESSION ['user_id'];
            $where['goods_id'] = $this->goods_id;
            $rs = $this->model->table('collect_goods')->where($where)->count();
            if ($rs > 0) {
                $this->assign('sc', 1);
            }
        }
        if($_SESSION ['user_id']){
            $user_info = model('Users')->get_profile($_SESSION ['user_id']);

            $condition1 = "rank_id =2";
            $user_rank1 = $this->model->table('user_rank')->field('min_points,max_points')->where($condition1)->find();
            $min_points  = $user_rank1['min_points'];
        
            $condition2 = "rank_id =3";
            $user_rank2 = $this->model->table('user_rank')->field('min_points,max_points')->where($condition2)->find();
            $max_points  = $user_rank1['max_points'];
        
            if($user_info['rank_points']>$min_points&&$goods["train_id"]==0){
                $share = 1;
            }else{
                $share = 0;
            }
            
        }else{

              $share = 0;
        }

        $this->assign('share', $share);
  
        /* 记录浏览历史 */
        if (!empty($_COOKIE ['ECS'] ['history'])) {
            $history = explode(',', $_COOKIE ['ECS'] ['history']);
            array_unshift($history, $this->goods_id);
            $history = array_unique($history);
            while (count($history) > C('history_number')) {
                array_pop($history);
            }
            setcookie('ECS[history]', implode(',', $history), time() + 3600 * 24 * 30);
        } else {
            setcookie('ECS[history]', $this->goods_id, time() + 3600 * 24 * 30);
        }
        
        $comment_list = model('Comment')->get_comment($this->goods_id, 1,1);

        $this->assign('comment_list', $comment_list);
        // 更新点击次数
        $data = 'click_count = click_count + 1';
        $this->model->table('goods')->data($data)->where('goods_id = ' . $this->goods_id)->update();

        // 当前系统时间
        $this->assign('showcode',1);
        $this->assign('now_time', time());
        $this->assign('sales_count', model('GoodsBase')->get_sales_count($this->goods_id));
        $this->assign('image_width', C('image_width'));
        $this->assign('image_height', C('image_height'));
        $this->assign('id', $this->goods_id);
        $this->assign('type', 0);
        $this->assign('cfg', C('CFG'));
        $this->assign('user_id',$_SESSION ['user_id']);
        // 促销信息
        $this->assign('promotion', model('GoodsBase')->get_promotion_info($this->goods_id));
        $this->assign('title', L('goods_detail'));
        /* 页面标题 */
        $page_info = get_page_title($goods['cat_id'], $goods['goods_name']);
        /* meta */
        $this->assign('meta_keywords', htmlspecialchars($goods['keywords']));
        $this->assign('meta_description', htmlspecialchars($goods['goods_brief']));
        $this->assign('ur_here', $page_info['ur_here']);
        $this->assign('page_title', $page_info['title']);
        //组合套餐名 start
        $comboTabIndex = array(' ','一', '二', '三','四','五','六','七','八','九','十');
        $this->assign('comboTab',$comboTabIndex);
        //组合套餐组
        $fittings_list = model('Goods')->get_goods_fittings(array($this->goods_id));
        $fittings_index = array();
        if(is_array($fittings_list)){
            foreach($fittings_list as $vo){
                $fittings_index[$vo['group_id']] = 1;//关联数组
            }
        }
        ksort($fittings_index);//重新排序
        $this->assign('fittings_tab_index', $fittings_index);//套餐数量
        // 组合套餐名 end
    
       

        $userInfo = model("Users")->get_users($_SESSION["user_id"]);
        
        //客服系统信息
        $this->assign("current_url",urlencode($this->get_current_url()));
        $this->assign("mobile_phone",$userInfo['mobile_phone']);
        $real_name = $userInfo['real_name']?$userInfo['real_name']:$userInfo['user_name'];
        $user_name = $userInfo['user_name'];
        $vip_name = model("Users")->getUserVipName($userInfo['user_vip']);
        $crm = "姓名:".$real_name.";"."会员名:".$user_name.";"."会员等级:".$vip_name;
        //

        $this->assign("crm",urlencode($crm));
        $this->assign("user_info", $userInfo);
        
        $shortUrl = $this->getShortUrl(__URL__ . '/index.php?m=default&c=goods&a=index&id='.$this->goods_id.'&u=' . $_SESSION['user_id']);
        $this->assign('share_link', $shortUrl);//
        $this->assign('share_title', $goods['goods_name']);//
        $this->assign('share_description', $this->formatDescription(htmlspecialchars($goods['goods_brief'])));//
        $this->assign('share_pic',  $goods['original_img']);//
        
        
        $shareMeta = array(
            "title" => $goods['goods_name'],
            "description"   => $this->formatDescription(htmlspecialchars($goods['goods_brief'])),
            "image" => $goods['original_img'],
        );
        $this->assign("shareMeta",$shareMeta);
        
        
        
        $this->display('goods.dwt');
    }

    /**
     * 商品信息
     */
    public function info()
    {
        /* 获得商品的信息 */
        $goods = model('Goods')->get_goods_info($this->goods_id);
		//购物车商品数量
        $cart_goods = insert_cart_info_number();
        $this->assign('seller_cart_total_number', $cart_goods);
        /* 页面标题 */
        $page_info = get_page_title($goods['cat_id'], $goods['goods_name']);
        $this->assign('page_title', htmlspecialchars($page_info['title']));
        /* meta */
        $this->assign('meta_keywords', htmlspecialchars($goods['keywords']));
        $this->assign('meta_description', htmlspecialchars($goods['goods_brief']));

        $this->assign('goods', $goods);
        $properties = model('Goods')->get_goods_properties($this->goods_id);  // 获得商品的规格和属性
        $this->assign('properties', $properties['pro']);                      // 商品属性
        $this->assign('specification', $properties['spe']);                   // 商品规格
        $this->assign('title', L('detail_intro'));
        $this->display('goods_info.dwt');
    }

    /**
     * 商品评论
     */
    public function comment_list()
    {
        $cmt = new stdClass();
        $cmt->id = !empty($_GET['id']) ? intval($_GET['id']) : 0;
        $cmt->type = !empty($_GET['type']) ? intval($_GET['type']) : 0;
        $cmt->page = isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']) : 1;
        $com = model('Comment')->get_comment_info($cmt->id,0);
        $this->assign('comments_info', $com);
        $pay = 0;
        $size = I(C('page_size'), 10);
        $this->assign('show_asynclist', C('show_asynclist'));
        $count = $com['count'];
        $filter['page'] = '{page}';
        $filter['id'] = $cmt->id;
        $offset = $this->pageLimit(url('goods/comment_list', $filter), $size);
        $offset_page = explode(',', $offset);
        $comment_list = model('Comment')->get_comment($cmt->id, $cmt->type, $pay, $offset_page[1], $offset_page[0]);
          
        $this->assign('comment_list', $comment_list);
        $this->assign('username', $_SESSION['user_name']);
        $this->assign('email', $_SESSION['email']);
        /* 验证码相关设置 */
        if ((intval(C('captcha')) & CAPTCHA_COMMENT) && gd_version() > 0) {
            $this->assign('enabled_captcha', 1);
            $this->assign('rand', mt_rand());
        }
        $result['message'] = C('comment_check') ? L('cmt_submit_wait') : L('cmt_submit_done');
        $this->assign('id', $cmt->id);
        $this->assign('type', $cmt->type);
        $this->assign('pager', $this->pageShow($count));
        $this->assign('title', L('goods_comment'));
        $this->display('goods_comment_list.dwt');
    }

    /**
     * 改变属性、数量时重新计算商品价格
     */
    public function price()
    {
        //格式化返回数组
        $res = array(
            'err_msg' => '',
            'result' => '',
            'qty' => 1
        );
        // 获取参数
        $attr_id = isset($_REQUEST ['attr']) ? explode(',', $_REQUEST ['attr']) : array();
        $number = (isset($_REQUEST ['number'])) ? intval($_REQUEST ['number']) : 1;
        // 如果商品id错误
        if ($this->goods_id == 0) {
            $res ['err_msg'] = L('err_change_attr');
            $res ['err_no'] = 1;
        } else {
            // 查询
            $condition = 'goods_id =' . $this->goods_id;
            $goods = $this->model->table('goods')->field('goods_name , goods_number ,shop_price,integral,extension_code,vipintegral')->where($condition)->find();
            $attr_ids = count($attr_id) > 1 ? str_replace(',', '|', $_REQUEST ['attr']) : $attr_id['0'];
            $condition = 'goods_attr = '."'".$attr_ids."'";
            $product = $this->model->table('products')->field('product_number')->where($condition)->find();

            // 查询：系统启用了库存，检查输入的商品数量是否有效
// 			if (intval ( C('use_storage') ) > 0 && $goods ['extension_code'] != 'package_buy') {
// 				if ($goods ['goods_number'] < $number) {
// 					$res ['err_no'] = 1;
            //	$res ['err_msg'] = sprintf ( L('stock_insufficiency'), $goods ['goods_name'], $goods ['goods_number'], $goods ['goods_number'] );
// 					$res ['err_max_number'] = $goods ['goods_number'];
// 					die ( json_encode ( $res ) );
// 				}
// 			}
            if ($number <= 0) {
                $res ['qty'] = $number = 1;
            } else {
                $res ['qty'] = $number;
            }
            $shop_price = model('GoodsBase')->get_final_price($this->goods_id, $number, true, $attr_id);
            $res ['result'] = price_format($shop_price * $number);
           
             if($_SESSION['user_rank']){
                $res ['result'] = price_format(($shop_price-$goods['vipintegral'])* $number)."+".$goods['vipintegral']* $number."KD豆" ;
            }else{
                $res ['result'] = price_format(($shop_price-$goods['integral'])* $number)."+".$goods['integral']* $number."KD豆" ;
             
            }
            if(!empty($product['product_number'])) {
                $res ['product_number'] = '库存：'.$product['product_number'];
            }
        }
        die(json_encode($res));
    }

    /*获取原始等级对应的目标等级*/
    public function getOriginGrade()
    {
        $condition = 'id >' . $_POST['origin_goods_vip'];
        $result = $this->model->table('member_level')->field('id,name')->where($condition)->select();
      
      echo  json_encode($result);exit;
    }
        public function hot_sell() {
              if($_SESSION["tmp_uuid"]){
            $this->uid = $_SESSION["tmp_uuid"];
        }else{
            $this->uid = $_GET['u']?$_GET['u']:($_SESSION["tmp_uuid"]?$_SESSION["tmp_uuid"]:0);
        }
           
            $this->user_id =$this->uid?$this->uid:$_SESSION['user_id'];
        /* 每日热卖 */
         $time = date("Y-m-d",time());

         //self::$cache->delValue("hot".$time);

         $yesterdaytime = date("Y-m-d",strtotime("-1 day"));
         
         /*今天的排行数据*/
         $redisdaysort = self::$cache->getValue("hot".$time);
          logResult("redisdaysort");
         //  logResult($redisdaysort);  
         /*昨日的排行数据*/
         $redisyesterdaysort = self::$cache->getValue("hot".$yesterdaytime);
         $redisdaysresult = unserialize($redisdaysort);
         $redisyesterdayresult = unserialize($redisyesterdaysort);
         
       
         if($redisdaysort){
           
            if($redisyesterdaysort){
                /*如果昨天有数据则计算排名变化*/
             
                foreach ($redisdaysresult as $key => $value) {
                # code...
                   
                     if($redisdaysresult[$key]['sort']<$redisyesterdayresult[$key]['sort']){
                         $redisdaysresult[$key]['sortresult'] = 1;//代表名次上升
                         $redisdaysresult[$key]['sortresultnum'] = $redisyesterdayresult[$key]['sort']-$redisdaysresult[$key]['sort'];
                     }

                     if($redisdaysresult[$key]['sort']==$redisyesterdayresult[$key]['sort']){
                         $redisdaysresult[$key]['sortresult'] = 0;//代表名次不变
                         $redisdaysresult[$key]['sortresultnum'] = 0;
                     }
                   
                     if($redisdaysresult[$key]['sort']>$redisyesterdayresult[$key]['sort']){
                        $redisdaysresult[$key]['sortresult'] = 2;//代表名次下降
                         $redisdaysresult[$key]['sortresultnum'] = $redisdaysresult[$key]['sort']-$redisyesterdayresult[$key]['sort'];
                     }
                     if($redisdaysresult[$key]['sort']&&empty($redisyesterdayresult[$key]['sort'])){
                        $redisdaysresult[$key]['sortresult'] = 1;//昨日这个用户没有数据代表名次上升
                        $redisdaysresult[$key]['sortresultnum'] = 20-$redisdaysresult[$key]['sort'];
                         if(!$redisdaysresult[$key]['sortresultnum']){
                             $redisdaysresult[$key]['sortresult'] = 0;//昨日这个用户没有数据代表名次上升
                             $redisdaysresult[$key]['sortresultnum'] = $redisyesterdayresult[$key]['sort'];
                         }
                     }



                    
                }
            }else{
                foreach (unserialize($redisdaysort) as $key => $value) {
                # code...
              
                }
            }

            


         }else{

              $sortarray = array();
              $sql = "select * from `ecs_touch_goods` as t  left join `ecs_goods` as g on t.goods_id=g.goods_id and g.is_on_sale=1 and g.train_id=0 where g.is_delete=0 and g.is_on_sale=1 and g.goods_area<>1 order by sales_volume desc limit 20";

              $sales_volume = $this->model->query($sql);

              /**/
          
              foreach ($sales_volume as $key => $value) {
                  # code...
               
                 $sortarray[$value['goods_id']]['sort'] = $key+1;
                 $sortarray[$value['goods_id']]['goods_id'] = $value['goods_id'];
                 $sortarray[$value['goods_id']]['sales_volume'] = $value['sales_volume'];
                 $goodsinfo = model("Goods")->get_goods_info($value['goods_id']);
                 $sortarray[$value['goods_id']]['goods_name'] = $goodsinfo['goods_name'];
                 $sortarray[$value['goods_id']]['shop_price'] = $goodsinfo['shop_price'];
                 $sortarray[$value['goods_id']]['goods_img']  = $goodsinfo['goods_img']; 
                 $sortarray[$value['goods_id']]['sortresult']  = 0; 
                 $sortarray[$value['goods_id']]['sortresultnum']  = 0; 
                  if($_SESSION['user_vip']){

                      if($goodsinfo['vipintegral']==0.00){
                        
                            $sortarray [$value['goods_id']]['vip_price'] = price_format($goodsinfo['shop_price']-$goodsinfo['vipintegral']);
                       
                        
                      }else{

                         $sortarray[$value['goods_id']] ['vip_price'] = price_format($goodsinfo['shop_price']-$goodsinfo['vipintegral'])."+".$goodsinfo['vipintegral']."鱼宝";
                      }
                       
                    }else{
           
                        $sortarray [$value['goods_id']]['vip_price'] = price_format($goodsinfo['shop_price'],false);
                    }
                 
                 
              }
          
              /*缓存一天*/
              $redisdaysresult = $sortarray;

              $redisdaysort = serialize($redisdaysresult);
             self::$cache->setValue("hot".$time, $redisdaysort, 3600*48);

         }
         
         $sortarray = $redisdaysresult;
     
        $this->assign("uid",$this->user_id);
         $this->assign("sortarray",$sortarray);
         $this->assign("user_vip",$_SESSION['user_vip']?$_SESSION['user_vip']:0);
         $this->display('hot_sell.dwt');
    }
    

}
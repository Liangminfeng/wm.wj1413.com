<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：IndexModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 首页模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class IndexModel extends CommonModel {

    /**
     * 获取推荐商品
     * @param  $type
     * @param  $limit
     * @param  $start
     */
    public function goods_list($type = 'best', $limit = 10, $start = 0) {
        switch ($type)
        {
            case 'best':
                $type   = 'AND g.is_best = 1';
                break;
            case 'new':
                $type   = 'AND g.is_new = 1';
                break;
            case 'hot':
                $type   = 'AND g.is_hot = 1';
                break;
            case 'all_drp':
                $type   = ' ';
                break;
            case 'promotion':
                $time    = time();
                $type   = "AND g.promote_price > 0 AND g.promote_start_date <= '$time' AND g.promote_end_date >= '$time'";
                break;
            default:
                $type   = '1';
        }
  
		if(isset($_SESSION['resource'])){
             // 取出所有符合条件的商品数据，并将结果存入对应的推荐类型数组中
        $sql = 'SELECT g.goods_id,g.integral AS integral,g.vipintegral AS vipintegral,  g.goods_name,g.specialicon, g.goods_name_style, g.market_price, g.shop_price AS org_price, g.promote_price, ' . "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, " . "promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img, RAND() AS rnd " . 'FROM ' . $this->pre . 'goods AS g ' . "LEFT JOIN " . $this->pre . "member_price AS mp " . "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ";
        $sql .= ' WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.goods_area <> 1 AND FIND_IN_SET('.$_SESSION[resource].',areas_id) AND g.is_delete = 0 ' . $type;
        $sql .= ' ORDER BY g.sort_order, g.last_update DESC limit ' . $start . ', ' . $limit;
     
    }else{
         // 取出所有符合条件的商品数据，并将结果存入对应的推荐类型数组中
        $sql = 'SELECT g.goods_id,g.integral AS integral,g.vipintegral AS vipintegral,  g.goods_name,g.specialicon, g.goods_name_style, g.market_price, g.shop_price AS org_price, g.promote_price, ' . "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, " . "promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img, RAND() AS rnd " . 'FROM ' . $this->pre . 'goods AS g ' . "LEFT JOIN " . $this->pre . "member_price AS mp " . "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ";
        $sql .= ' WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.goods_area <> 1  AND g.is_delete = 0 ' . $type;
        $sql .= ' ORDER BY g.sort_order, g.last_update DESC limit ' . $start . ', ' . $limit;
    }
       



        $result = $this->query($sql);
        foreach ($result as $key => $vo) {
            if ($vo['promote_price'] > 0) {
                $promote_price = bargain_price($vo['promote_price'], $vo['promote_start_date'], $vo['promote_end_date']);
                $goods[$key]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            } else {
                $goods[$key]['promote_price'] = '';
            }
            $goods[$key]['specialicon'] = $vo['specialicon'];
            $goods[$key]['id'] = $vo['goods_id'];
            $goods[$key]['name'] = $vo['goods_name'];
            $goods[$key]['goods_name'] = $vo['goods_name'];
            $goods[$key]['brief'] = $vo['goods_brief'];
            $goods[$key]['goods_style_name'] = add_style($vo['goods_name'], $vo['goods_name_style']);
            $goods[$key]['short_name'] = C('goods_name_length') > 0 ? sub_str($vo['goods_name'], C('goods_name_length')) : $vo['goods_name'];
            $goods[$key]['short_style_name'] = add_style($goods[$key] ['short_name'], $vo['goods_name_style']);
            $goods[$key]['market_price'] = price_format($vo['market_price']);
            //$goods[$key]['shop_price'] = price_format($vo['shop_price']);
            $goods[$key]['thumb'] = get_image_path($vo['goods_id'], $vo['goods_thumb'], true);
            $goods[$key]['goods_thumb'] = get_image_path($vo['goods_id'], $vo['goods_thumb'], true);
            $goods[$key]['goods_img'] = get_image_path($vo['goods_id'], $vo['goods_img']);
            $goods[$key]['url'] = url('goods/index', array('id' => $vo['goods_id']));
            $goods[$key]['sales_count'] = model('GoodsBase')->get_sales_count($vo['goods_id']);
            $goods[$key]['sc'] = model('GoodsBase')->get_goods_collect($vo['goods_id']);
            $goods[$key]['mysc'] = 0;
            if($_SESSION['user_vip']){
               $goods[$key]['integral'] = $vo['vipintegral']?$vo['vipintegral']:0; 
               if($goods[$key]['integral']==0.00){
                $goods[$key]['integral'] = 0;
               }
               $goods[$key]['vip_price'] = $vo['shop_price']-$vo['vipintegral'];
           }else{
            $goods[$key]['integral'] = $vo['integral'];
           }
            
            

            $goods[$key]['shop_price'] = $vo['shop_price'];
            $goods[$key]['sales_count'] =  model('GoodsBase')->get_sales_count($vo['goods_id']);
            //$goods[$key]['shop_price'] = price_format($vo['shop_price']-$vo['integral'])."+".number_format(($vo['integral']*100/C(integral_scale)),0)."积分";
            // 检查是否已经存在于用户的收藏夹
            if ($_SESSION ['user_id']) {
                // 用户自己有没有收藏过
                $condition['goods_id'] = $vo['goods_id'];
                $condition['user_id'] = $_SESSION ['user_id'];
                $rs = $this->model->table('collect_goods')->where($condition)->count();
                $goods[$key]['mysc'] = $rs;
            }
            $goods[$key]['promotion'] = model('GoodsBase')->get_promotion_show($vo['goods_id']);
            $type_goods[$type][] = $goods[$key];
        }
        return $type_goods[$type];
    }
        public function newgoods_list($cat_id, $limit = 10, $start = 0) {
                $type   = 'AND g.cat_id = '.$cat_id; 
        // 取出所有符合条件的商品数据，并将结果存入对应的推荐类型数组中
        $sql = 'SELECT g.goods_id,g.integral AS integral,g.vipintegral AS vipintegral,  g.goods_name,g.specialicon, g.goods_name_style, g.market_price, g.shop_price AS org_price, g.promote_price, ' . "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, " . "promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img, RAND() AS rnd " . 'FROM ' . $this->pre . 'goods AS g ' . "LEFT JOIN " . $this->pre . "member_price AS mp " . "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ";
        $sql .= ' WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.goods_area <> 1 AND g.is_delete = 0 ' . $type;
        $sql .= ' ORDER BY g.sort_order, g.last_update DESC limit ' . $start . ', ' . $limit;



        $result = $this->query($sql);
        foreach ($result as $key => $vo) {
            if ($vo['promote_price'] > 0) {
                $promote_price = bargain_price($vo['promote_price'], $vo['promote_start_date'], $vo['promote_end_date']);
                $goods[$key]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            } else {
                $goods[$key]['promote_price'] = '';
            }
            $goods[$key]['specialicon'] = $vo['specialicon'];
            $goods[$key]['id'] = $vo['goods_id'];
            $goods[$key]['name'] = $vo['goods_name'];
            $goods[$key]['goods_name'] = $vo['goods_name'];
            $goods[$key]['brief'] = $vo['goods_brief'];
            $goods[$key]['goods_style_name'] = add_style($vo['goods_name'], $vo['goods_name_style']);
            $goods[$key]['short_name'] = C('goods_name_length') > 0 ? sub_str($vo['goods_name'], C('goods_name_length')) : $vo['goods_name'];
            $goods[$key]['short_style_name'] = add_style($goods[$key] ['short_name'], $vo['goods_name_style']);
            $goods[$key]['market_price'] = price_format($vo['market_price']);
            //$goods[$key]['shop_price'] = price_format($vo['shop_price']);
            $goods[$key]['thumb'] = get_image_path($vo['goods_id'], $vo['goods_thumb'], true);
            $goods[$key]['goods_thumb'] = get_image_path($vo['goods_id'], $vo['goods_thumb'], true);
            $goods[$key]['goods_img'] = get_image_path($vo['goods_id'], $vo['goods_img']);
            $goods[$key]['url'] = url('goods/index', array('id' => $vo['goods_id']));
            $goods[$key]['sales_count'] = model('GoodsBase')->get_sales_count($vo['goods_id']);
            $goods[$key]['sc'] = model('GoodsBase')->get_goods_collect($vo['goods_id']);
            $goods[$key]['mysc'] = 0;
            if($_SESSION['user_vip']){
               $goods[$key]['integral'] = $vo['vipintegral']?$vo['vipintegral']:0; 
               $goods[$key]['vip_price'] = $vo['shop_price']-$vo['vipintegral'];
           }else{
            $goods[$key]['integral'] = $vo['integral'];
           }
            
            

            $goods[$key]['shop_price'] = $vo['shop_price'];
            $goods[$key]['sales_count'] =  model('GoodsBase')->get_sales_count($vo['goods_id']);
            //$goods[$key]['shop_price'] = price_format($vo['shop_price']-$vo['integral'])."+".number_format(($vo['integral']*100/C(integral_scale)),0)."积分";
            // 检查是否已经存在于用户的收藏夹
            if ($_SESSION ['user_id']) {
                // 用户自己有没有收藏过
                $condition['goods_id'] = $vo['goods_id'];
                $condition['user_id'] = $_SESSION ['user_id'];
                $rs = $this->model->table('collect_goods')->where($condition)->count();
                $goods[$key]['mysc'] = $rs;
            }
            $goods[$key]['promotion'] = model('GoodsBase')->get_promotion_show($vo['goods_id']);
            $type_goods[$type][] = $goods[$key];
        }
        return $type_goods[$type];
    }
        public function category_get_goods($cat_id,$start,$size)
    {
        $this->children = get_children($cat_id);
       
        $display = $GLOBALS['display'];
        $where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND " . "g.is_delete = 0 ";
        if ($this->keywords != '') {
            $where .= " AND (( 1 " . $this->keywords . " ) ) ";
        } else {
            $where .= " AND ($this->children OR " . model('Goods')->get_extension_goods($this->children) . ') ';
        }
        if ($this->type) {
            switch ($this->type) {
                case 'best':
                    $where .= ' AND g.is_best = 1';
                    break;
                case 'new':
                    $where .= ' AND g.is_new = 1';
                    break;
                case 'hot':
                    $where .= ' AND g.is_hot = 1';
                    break;
                case 'promotion':
                    $time = time();
                    $where .= " AND g.promote_price > 0 AND g.promote_start_date <= '$time' AND g.promote_end_date >= '$time'";
                    break;
                default:
                    $where .= '';
            }
        }
        if ($this->brand > 0) {
            $where .= "AND g.brand_id=$this->brand ";
        }
        if ($this->price_min > 0) {
            $where .= " AND g.shop_price >= $this->price_min ";
        }
        if ($this->price_max > 0) {
            $where .= " AND g.shop_price <= $this->price_max ";
        }

        $where .= " AND g.train_id =0 ";
        $where .= " AND g.goods_area <>1 ";
        $start = ($this->page - 1) * $this->size;
        $sort = $this->sort == 'sales_volume' ? 'xl.sales_volume' : $this->sort;
        /* 获得商品列表 */
       if($sort == 'goods_id'){
        $sort = 'sort_order';
       }
        $sql = 'SELECT g.goods_id,g.integral,g.vipintegral,g.specialicon, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, g.last_update,' . "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, g.goods_type, g.goods_number, " .
            'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' . 'FROM ' . $this->model->pre . 'goods AS g '  . ' LEFT JOIN ' . $this->model->pre . 'member_price AS mp ' . "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " . "WHERE $where  order by g.goods_id desc  LIMIT $start , $size";
   
   
        $res = $this->model->query($sql);

        $arr = array();
        foreach ($res as $key=>$row) {
            // 销量统计
            
            $sales_volume = (int)$row['sales_volume'];
            if (mt_rand(0, 3) == 3) {
                $sales_volume = model('GoodsBase')->get_sales_count($row['goods_id']);
                $sql = 'REPLACE INTO ' . $this->model->pre . 'touch_goods(`goods_id`, `sales_volume`) VALUES(' . $row['goods_id'] . ', ' . $sales_volume . ')';
                $this->model->query($sql);
            }
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }
            /* 处理商品水印图片 */
            $watermark_img = '';

            if ($promote_price != 0) {
                $watermark_img = "watermark_promote_small";
            } elseif ($row['is_new'] != 0) {
                $watermark_img = "watermark_new_small";
            } elseif ($row['is_best'] != 0) {
                $watermark_img = "watermark_best_small";
            } elseif ($row['is_hot'] != 0) {
                $watermark_img = 'watermark_hot_small';
            }

            if ($watermark_img != '') {
                $arr[$key]['watermark_img'] = $watermark_img;
            }

            $arr[$key]['goods_id'] = $row['goods_id'];
            if ($display == 'grid') {
                $arr[$key]['goods_name'] = C('goods_name_length') > 0 ? sub_str($row['goods_name'], C('goods_name_length')) : $row['goods_name'];
            } else {
                $arr[$key]['goods_name'] = $row['goods_name'];
            }
            $arr[$key]['specialicon'] = $row['specialicon'];

            $arr[$key]['goods_area'] = $row['goods_area'];
            $arr[$key]['goods_vip'] = $row['goods_vip'];
            
            $arr[$key]['name'] = $row['goods_name'];
            $arr[$key]['goods_brief'] = $row['goods_brief'];
            $arr[$key]['goods_style_name'] = add_style($row['goods_name'], $row['goods_name_style']);
            $arr[$key]['market_price'] = price_format($row['market_price']);
            $arr[$key]['type'] = $row['goods_type'];
            // if ($promote_price > 0) {
            //     $arr[$key]['shop_price'] = price_format($promote_price);
            // } else {
            //     $arr[$key]['shop_price'] = price_format($row['shop_price']);
            // }
         
            if ($promote_price > 0) {
                $arr[$key]['shop_price'] = price_format($promote_price);
            } else {
               
                 // $arr[$key]['shop_price'] = price_format($row['shop_price']-$row['integral'])."+".number_format(($row['integral']*100/C(integral_scale)),0)."积分";
                $arr[$key]['shop_price'] = price_format($row['shop_price']);

            }

            $arr[$key]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $arr[$key]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $arr[$key]['url'] = url('goods/index', array(
                'id' => $row['goods_id']
            ));
            //$arr[$key]['sales_count'] = $sales_volume;
            $arr[$key]['sc'] = model('GoodsBase')->get_goods_collect($row['goods_id']);
            $arr[$key]['mysc'] = 0;
            // 检查是否已经存在于用户的收藏夹
            if ($_SESSION['user_id']) {
                unset($where);
                // 用户自己有没有收藏过
                $where['goods_id'] = $row['goods_id'];
                $where['user_id'] = $_SESSION['user_id'];
                $rs = $this->model->table('collect_goods')
                    ->where($where)
                    ->count();
                $arr[$key]['mysc'] = $rs;
            }

            $arr[$key]['sales_count'] = model('GoodsBase')->get_sales_count($row['goods_id']);
            $arr[$key]['goods_number'] = $row['goods_number'];

              if($_SESSION['user_vip']){
                if($row['vipintegral']){

                     $arr[$key]['integral'] = price_format($row['shop_price']-$row['vipintegral'])."+".$row['vipintegral'].C('integral_name');
                     $arr[$key]['showintegral'] = 1;
                 }else{
                     $arr[$key]['showintegral'] = 0;
                 }
               

            }else{
                if($row['integral']){
                   $arr[$key]['integral'] = price_format($row['shop_price']-$row['integral'])."+".$row['integral'].C('integral_name') ; 
                   $arr[$key]['showintegral'] = 1;
                }else{
                   $arr[$key]['integral'] = 0;
                   $arr[$key]['showintegral'] = 0;
                }
                
             
            }


       
            $arr[$key]['promotion'] = model('GoodsBase')->get_promotion_show($row['goods_id']);
            $arr[$key]['comment_count'] = model('Comment')->get_goods_comment($row['goods_id'], 0);  //商品总评论数量
            $arr[$key]['favorable_count'] = model('Comment')->favorable_comment($row['goods_id'], 0);  //获得商品好评百分比
        }
     
        return $arr;
    }
    /**
     * 获得促销商品
     *
     * @access  public
     * @return  array
     */
    function get_promote_goods($cats = '') {
        $time = time();
        $order_type = C('recommend_order');
		
        /* 取得促销lbi的数量限制 */
        $num = model('Common')->get_library_number("recommend_promotion");
        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.shop_price AS org_price, g.promote_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, " .
                "promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, goods_img, b.brand_name, " .
                "g.is_best, g.is_new, g.is_hot, g.is_promote, RAND() AS rnd " .
                'FROM ' . $this->pre . 'goods AS g ' .
                'LEFT JOIN ' . $this->pre . 'brand AS b ON b.brand_id = g.brand_id ' .
                "LEFT JOIN " . $this->pre . "member_price AS mp " .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 '  . $where .
                " AND g.is_promote = 1 AND promote_start_date <= '$time' AND promote_end_date >= '$time' ";
        $sql .= $order_type == 0 ? ' ORDER BY g.sort_order, g.last_update DESC' : ' ORDER BY rnd';
        $sql .= " LIMIT $num ";
        $result = $this->query($sql);

        $goods = array();
        foreach ($result AS $idx => $row) {
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            } else {
                $goods[$idx]['promote_price'] = '';
            }

            $goods[$idx]['id'] = $row['goods_id'];
            $goods[$idx]['name'] = $row['goods_name'];
            $goods[$idx]['brief'] = $row['goods_brief'];
            $goods[$idx]['brand_name'] = $row['brand_name'];
            $goods[$idx]['goods_style_name'] = add_style($row['goods_name'], $row['goods_name_style']);
            $goods[$idx]['short_name'] = C('goods_name_length') > 0 ? sub_str($row['goods_name'], C('goods_name_length')) : $row['goods_name'];
            $goods[$idx]['short_style_name'] = add_style($goods[$idx]['short_name'], $row['goods_name_style']);
            $goods[$idx]['market_price'] = price_format($row['market_price']);
            $goods[$idx]['shop_price'] = price_format($row['shop_price']);
            $goods[$idx]['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods[$idx]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $goods[$idx]['url'] = url('goods/index', array('id' => $row['goods_id']));
        }

        return $goods;
    }

    /**
     * 首页推荐分类
     * @return type
     *  by Leah
     */
    function get_recommend_res($cat_num = '10',$goods_num = '3') {
        $cat_recommend_res = $this->query("SELECT c.cat_id, c.cat_name, cr.recommend_type FROM " . $this->pre . "cat_recommend AS cr INNER JOIN " . $this->pre . "category AS c ON cr.cat_id=c.cat_id AND c.is_show = 1 ORDER BY c.sort_order ASC, c.cat_id ASC limit 0, $cat_num");
        if (!empty($cat_recommend_res)) {
            $cat_rec = array();
            foreach ($cat_recommend_res as $cat_recommend_data) {
                $cat_rec[$cat_recommend_data['recommend_type']][] = array(
                    'cat_id' => $cat_recommend_data['cat_id'], 
                    'cat_name' => $cat_recommend_data['cat_name'],
                    'url' => url('category/index', array('id' => $cat_recommend_data['cat_id'])), 
                    'child_id' => model('Category')->get_parent_id_tree($cat_recommend_data['cat_id']), 
                    'goods_list' => model('Category')->assign_cat_goods($cat_recommend_data['cat_id'], $goods_num)					
                );
            }
            return $cat_rec;
        }
    }


    /**
     * 获得新品，精品、热销商品数量
     *
     * @access  public
     * @return  array
     */
    function get_pro_goods($type = '') {
        switch ($type)
        {
            case 'best':
                $where   = 'AND g.is_best = 1';
                break;
            case 'new':
                $where   = 'AND g.is_new = 1';
                break;
            case 'hot':
                $where   = 'AND g.is_hot = 1';
                break;
            default:
                $where   = 'AND 1';
        }
		
        $sql = 'SELECT count(g.goods_id) as num FROM ' . $this->pre . 'goods as g WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ' 
            . $where . " ORDER BY g.sort_order, g.goods_id DESC ";
        $result = $this->row($sql);
        return $result['num'];
    }
    /*
    *接口分发
    */
    function postData($data,$posturl,$timeout=5){
        $user_id = $_SESSION['user_id'];
        $sql = 'SELECT resource FROM ' . $this->pre . 'users  WHERE user_id = "'.$user_id.'"';
        $result = $this->row($sql);

        switch ($result['resource']) {
            case '1':
                # 拓客全球go
                $posturl = API_URL.$posturl;
           
                $r = post_log($data,$posturl,$timeout);
        
               if($r['status'] ==200){
                return $r['data'];
               }else{
                return false;
               }
           
                break;
           case '2':
                # laopan
                return false;

                break; 
            default:
                # code...
                return false;
                break;
        }

    }

	
}

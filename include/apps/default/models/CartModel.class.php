<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ShippingBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 配送基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');
 
class CartModel extends BaseModel {

    protected $table = 'cart';

    function is_buy_radio($current_id,$group_id,$is_buy){

        if(!empty($current_id)){

            $currentsql = "SELECT is_buy,goods_price,goods_number FROM " . $this->pre .'cart'.
                " WHERE goods_id = " . $current_id . " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'" ;

            $current = $this->query($currentsql);
  
            if($current[0]['is_buy'] == 1){
               
                if($current[0]['is_buy'] != $is_buy){

                    $sql = "UPDATE " . $this->pre . "cart SET is_buy = '$is_buy'" .
                        " WHERE goods_id = " . $current_id . " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'" ;
                    $this->query($sql);
                }
            }else{
      
                if($current[0]['is_buy'] != $is_buy){
                    $sql = "UPDATE " . $this->pre . "cart SET is_buy = '$is_buy'" .
                        " WHERE goods_id = " . $current_id . " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'" ;
                    $this->query($sql);

                  
                }
            }
            
            $cart_goods = model('Order')->get_cart_goods(1);
            return $cart_goods;
        }
    }

    function is_buy_multi($goods_id,$is_buy,$parent_id,$change_goods){
        if(!empty($goods_id) && is_array($goods_id)){
            if($is_buy == 1){
                $goods_id = implode(',', $goods_id);
                $sql = "UPDATE " . $this->pre . "cart SET is_buy = '$is_buy'" .
                    " WHERE goods_id in (" . $goods_id . ')'. " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'";
                $this->query($sql);

                if(!empty($parent_id)) {
                    if ($is_buy == 1) {
                        $is_buy = 0;
                    }
                    if(is_array($parent_id)){
                        $parent_id= implode(',', $parent_id);
                        $where = "UPDATE " . $this->pre . "cart SET is_buy = '$is_buy'" .
                            " WHERE goods_id in (" . $parent_id . ')'. " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'";;
                        $this->query($where);
                    }
                }
            }
        }else{
            if(is_array($parent_id)){
                $parent_id= implode(',', $parent_id);
                $where = "UPDATE " . $this->pre . "cart SET is_buy = '$is_buy'" .
                    " WHERE goods_id not in (" . $parent_id . ')'. " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'";
                $this->query($where);
            }else{

                $change_goods= implode(',', $change_goods);
                $where = "UPDATE " . $this->pre . "cart SET is_buy = '$is_buy'" .
                    " WHERE goods_id  in (" . $change_goods . ')'. " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'";
             
                $this->query($where);

            }
        }

        $cart_goods = model('Order')->get_cart_goods(1);
        return $cart_goods;

    }


    function Increase_number($goods_id){
        $update = "UPDATE " . $this->pre . "cart SET goods_number = goods_number + 1" .
            " WHERE goods_id = $goods_id". " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'";
        $this->query($update);

        $cart_goods = model('Order')->get_cart_goods(1);
        return $cart_goods;
    }
    function Input_number($rec_id,$number){
        if($number){
            $update = "UPDATE " . $this->pre . "cart SET goods_number = ". $number .
            " WHERE rec_id = $rec_id". " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'";
            $this->query($update);

            $cart_goods = model('Order')->get_cart_goods(1);
             return ['code' => 200,'cart' => $cart_goods];

        }else{
             return ['code' => 300];
        }
        
        return $cart_goods;
    }

    function Reduction_number($goods_id){
        $sql = "SELECT goods_id,goods_price,goods_number FROM " . $this->pre . "cart WHERE goods_id = $goods_id". " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'";
        $goods = $this->query($sql);
        if($goods[0]['goods_number'] > 1){
            $update = "UPDATE " . $this->pre . "cart SET goods_number = goods_number - 1" .
                " WHERE goods_id = $goods_id". " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'";
            $this->query($update);

            $cart_goods = model('Order')->get_cart_goods(1);
            return ['code' => 200,'cart' => $cart_goods];
        }else{
            return ['code' => 300];
        }
    }

    function get_cart_goods(){
        $cart = "SELECT goods_id,is_buy,goods_price,goods_number FROM " . $this->pre . "cart WHERE is_buy = 1";
        $cart_goods = $this->query($cart);
        $money = '';
        $number = '';
        if(!empty($cart_goods)){
            foreach($cart_goods as $k => $v){
                $money += $v['goods_number'] * $v['goods_price'];
                $number += $v['goods_number'];
            }
        }else{
            return false;
        }
        return ['money' => $money,'number' => $number];
    }

    function Reduction_Dont_buy_goods($goods_id){
        $sql = "SELECT goods_id,goods_price,goods_number FROM " . $this->pre . "cart WHERE goods_id = $goods_id". " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'";
        $goods = $this->query($sql);
        if($goods[0]['goods_number'] > 1){
            $update = "UPDATE " . $this->pre . "cart SET goods_number = goods_number - 1" .
                " WHERE goods_id = $goods_id". " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'";
            $this->query($update);

            $cart = "SELECT goods_number FROM " . $this->pre . "cart WHERE goods_id = $goods_id". " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'";;
            $cart_goods = $this->query($cart);
            $cart_goods = model('Order')->get_cart_goods(1);
        return $cart_goods;

        }

        $cart_goods = model('Order')->get_cart_goods(1);
        return $cart_goods;
    }

    function Increase_Dont_buy_goods($goods_id){
        // $update = "UPDATE " . $this->pre . "cart SET goods_number = goods_number + 1" .
        //     " WHERE goods_id = ".$goods_id. " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'";;
        // $this->query($update);

        // $cart = "SELECT goods_number FROM " . $this->pre . "cart WHERE goods_id = $goods_id". " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'";;
        // $cart_goods = $this->query($cart);
        // return ['goods_number' => $cart_goods[0]['goods_number']];
        $update = "UPDATE " . $this->pre . "cart SET goods_number = goods_number + 1" .
            " WHERE goods_id = $goods_id". " AND session_id='".SESS_ID."' AND rec_type = '" . CART_GENERAL_GOODS . "'";
        $this->query($update);

        $cart_goods = model('Order')->get_cart_goods(1);
        return $cart_goods;
    }




}

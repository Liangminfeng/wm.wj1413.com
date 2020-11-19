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
 
class ShopConfigModel extends BaseModel {

    protected $table = 'shop_config';

    //是否可以使用红包
    function is_use_banus(){
        $sql = 'SELECT * FROM ' . $this->pre . "shop_config" .
            " WHERE id = 403";
        $result = $this->row($sql);
        return $result;
    }
    //是否可以使用积分
    function is_use_integral(){
        $sql = 'SELECT * FROM ' . $this->pre . "shop_config" .
            " WHERE id = 402";
        $result = $this->row($sql);
        return $result;
    }

    //是否可以使用积分
    function integral_conversion_ratio(){
        $sql = 'SELECT * FROM ' . $this->pre . "shop_config" .
            " WHERE id = 211";
        $result = $this->row($sql);
        return $result;
    }

    //赠送积分
    function Present_integral($user_id,$amount,$goods_id){
        $sql = 'SELECT * FROM ' . $this->pre . "shop_config" .
            " WHERE id = 909";
        $result = $this->row($sql);
        if($result['value'] == 0){
            if(!empty($goods_id)){
                $goods_id = implode(',',$goods_id);
                $sql = 'SELECT goods_id,give_integral FROM ' . $this->pre . "goods" .
                    " WHERE goods_id in ($goods_id)";
                $data = $this->query($sql);
                if(!empty($data)){
                    $goods = '';
                    $integral = '';
                    foreach($data as $k => $v){
                        if($v['give_integral'] > 0){
                            $integral += $v['give_integral'];
                        }
                    }
                    if($integral > 0){
                        $sql = 'UPDATE ' . $this->pre . "users" .
                            " SET pay_points =  pay_points + $integral WHERE user_id = $user_id";
                        $this->query($sql);
                    }
                }
            }

        }else{
            $sql = 'SELECT * FROM ' . $this->pre . "shop_config" .
                " WHERE id = 910";
            $data = $this->row($sql);

            if($data['value'] > 0){
                $integral = intval($amount / $data['value']);
                $sql = 'UPDATE ' . $this->pre . "users" .
                    " SET pay_points =  pay_points + $integral WHERE user_id = $user_id";
                $this->query($sql);
            }else{
                return;
            }
        }
    }

    function delete_bonus($user_id,$bonus_id){
        $sql = 'DELETE FROM ' . $this->pre . "user_bonus" . " WHERE bonus_id = $bonus_id AND user_id = $user_id";
        $this->query($sql);
    }

    function Present_the_proportion(){
        $sql = 'SELECT * FROM ' . $this->pre . "shop_config" . " WHERE id = 909";
        $result = $this->row($sql);
        if($result['value'] == 1){
            $sql = 'SELECT * FROM ' . $this->pre . "shop_config" . " WHERE id = 910";
            $data = $this->row($sql);
            $result['data'] = $data;
        }
        return $result;
    }

    function get_integral($goods_id){
        if(!empty($goods_id)){
            $goods_id = implode(',',$goods_id);
            $sql = 'SELECT goods_id,give_integral FROM ' . $this->pre . "goods" .
                " WHERE goods_id in ($goods_id)";
            $data = $this->query($sql);
            if(!empty($data)){
                $integral = '';
                foreach($data as $k => $v){
                    if($v['give_integral'] > 0){
                        $integral += $v['give_integral'];
                    }
                }
            }
            return $integral;
        }
    }

}

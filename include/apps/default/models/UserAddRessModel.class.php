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
 
class UserAddRessModel extends BaseModel {

    protected $table = 'user_address';

    function user_default_address($user_id = ''){
        $user_info = model('Order')->user_info($user_id);
        $sql = 'SELECT * FROM ' . $this->pre . "user_address" .
            " WHERE address_id = ".$user_info['address_id'];
        $result = $this->row($sql);

        if(is_array($result)){
            $sql = 'SELECT * FROM ' . $this->pre . "region" .
                " WHERE region_id = ".$result['province']." or region_id =".$result['city']." or region_id =".$result['district'];
          /*SELECT * FROM ecs_region WHERE region_id = 32767 or region_id =32767 or region_id =32767*/
            $address = $this->query($sql);
          
            foreach($address as $k => $v){
                $result['shipping_address'] .= " ". $v['region_name'];
                $result['shipping_address_id'] .= "-". $v['region_id'];
            }
            $result['shipping_address'] = trim($result['shipping_address']);
            $result['shipping_address_id'] = substr($result['shipping_address_id'],1);
        }
        return $result;
    }
}

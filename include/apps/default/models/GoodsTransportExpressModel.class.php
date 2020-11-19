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
 
class GoodsTransportExpressModel extends BaseModel {

    protected $table = 'goods_transport_express';

    function transport_express_info($shipping_id){
        $sql = 'SELECT * FROM ' . $this->pre . "goods_transport_express"." WHERE shipping_id = '$shipping_id' ";
        $result = $this->row($sql);
        return $result;
    }
}

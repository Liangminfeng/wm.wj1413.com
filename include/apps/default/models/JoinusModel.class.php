<?php
/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：TrainModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 优惠活动模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 * 
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');
class JoinusModel extends BaseModel {
    public $table = "join_us";
    
    //过期时间
    public $expire = 48;
    
    public function getOne($where){
        
        $sql = "select * from ".$this->pre."join_us where ".$where;
        return $this->row($sql);
    }
    
}

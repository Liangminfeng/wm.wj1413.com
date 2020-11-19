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
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class LotteryLogModel extends BaseModel {
    public $table = "user_lottery_log";
    
    public function getUserLog($userId , $page){
        
        $total = $this->count(["user_id"=>$userId]);
        
        $start = ($page["page_no"]-1)*$page["page_size"];
        $list = $this->select(["user_id"=>$userId] , '', 'create_time desc', "{$start},{$page["page_size"]}");
        
        return ["total"=>$total,"data"=>$list];
    }
    
}

<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：BrandModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 品牌模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class AccountLogModel extends BaseModel {

    public $table = "account_log";

    function get_account_log($user_id,$size, $page,$type) {

        $start = ($page - 1) * $size;
        $sql = "SELECT * FROM " . $this->pre . "account_log WHERE user_id = '$user_id' and account_type in (".$type.") and change_type in (12,2,8,4)  order by log_id desc LIMIT $start , $size";
   
        $res = $this->query($sql);
        
        $arr = array();
        foreach ($res as $key=>$row) {

                $res[$key]['time'] =local_date(C('date_format'), $row['change_time']);

        }


        return $res;

    }
    function activity_get_account_log($user_id,$size, $page,$type) {

        $start = ($page - 1) * $size;
        $sql = "SELECT * FROM " . $this->pre . "account_log WHERE user_id = '$user_id' and account_type =14 and change_type =19  order by log_id desc LIMIT $start , $size";
   
        $res = $this->query($sql);
        
        $arr = array();
        foreach ($res as $key=>$row) {

                $res[$key]['time'] =local_date(C('date_format'), $row['change_time']);

        }


        return $res;

    }

        function get_newaccount_log($user_id,$size, $page,$type) {

          $start = ($page - 1) * $size;
         $sql = "SELECT change_time,change_desc,sum(amount) as total FROM " . $this->pre . "account_log WHERE user_id = '$user_id' and account_type in (".$type.") and change_type in (12,2,8,4)  group by  change_time order by log_id desc LIMIT $start , $size";


        $res = $this->query($sql);
        
        $arr = array();
        foreach ($res as $key=>$row) {

                $res[$key]['time'] =local_date(C('date_format'), $row['change_time']);
                $res[$key]['amount'] = $row['total'];

        }


        return $res;

    }
        function newget_account_log($user_id,$size, $page,$type) {

        $start = ($page - 1) * $size;
        $sql = "SELECT * FROM " . $this->pre . "account_log WHERE user_id = '$user_id' and account_type in ('".$type."') and change_type in(1,11)  order by log_id desc LIMIT $start , $size";

        $res = $this->query($sql);
        
        $arr = array();
        foreach ($res as $key=>$row) {

                $res[$key]['time'] =local_date(C('date_format'), $row['change_time']);

        }


        return $res;

    }
        function get_account_count($user_id,$type) {

    
        $sql = "SELECT count(*) as total FROM " . $this->pre . "account_log WHERE user_id = '$user_id' and change_type in (12,8,4) and account_type in (".$type.") ";
        $res = $this->row($sql);
        $arr = array();


        return $res['total'];

    }
    function activity_get_account_count($user_id,$type) {

    
        $sql = "SELECT count(*) as total FROM " . $this->pre . "account_log WHERE user_id = '$user_id' and change_type=19 and account_type in (".$type.") ";
        $res = $this->row($sql);
        $arr = array();


        return $res['total'];

    }
     function newget_account_count($user_id,$type) {

    
        $sql = "SELECT count(*) as total FROM " . $this->pre . "account_log WHERE user_id = '$user_id' and change_type in(1,11) and account_type in ('".$type."') ";
        $res = $this->row($sql);
        $arr = array();
        
     

        return $res['total'];

    }

    function getIncomeTax($N,$withdrawmoney){

        if($N<=5000){

            $tax = 0 ;

        }elseif ($N>5000&&$N<=15000) {
            # code...
            $tax = 0.03 * $withdrawmoney ;

        }elseif ($N>15000&&$N<=30000) {
            # code...
            $tax = 0.05 * $withdrawmoney ;

        }else{

            $tax = 0.08 * $withdrawmoney ;

        }
        return $tax;
    }



}

<?php


/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2016 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：AboutControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：关于我们控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class QiniuController extends CommonController {

    public function uploadimage(){

        $fileName = $_REQUEST["upload_filename"];
        $file = $_FILES[fileName];

        $ret = Myqiniu::uploadFile($file);
        
        if(!$ret["state"]){
            $res = array(
                "state" => "FALSE",
                "msg"   => $ret["msg"]
            );
        }else{
            $res = array(
                "state" => "SUCCESS",
                "url"   => $ret["url"]
            );
        }
        
        /**
         * 得到上传文件所对应的各个参数,数组结构
         * array(
         * "state" => "", //上传状态，上传成功时必须返回"SUCCESS"
         * "url" => "", //返回的地址
         * "title" => "", //新文件名
         * "original" => "", //原始文件名
         * "type" => "" //文件类型
         * "size" => "", //文件大小
         * )
         */
        /* 返回数据 */
        return json_encode($res);
    }
    
    public function token() {
        
        $res = Myqiniu::getToken();
        
        echo json_encode($res);
        exit();
    }
    

}

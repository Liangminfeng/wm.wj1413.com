<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：TopicControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：专题页控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class TalkController extends CommonController {

    protected $id;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $this->id = isset($_REQUEST ['talk_id']) ? intval($_REQUEST ['talk_id']) : 0;
    }

    /**
     * 专题详情
     */
    public function index() {
       
        $talk = $this->model->table('talk')->where('talk_id =' . $this->id)->find();

        if(empty($talk))
        {
            /* 如果没有找到任何记录则跳回到首页 */
            $this->redirect(url('index/index'));
        }

        $this->display('user.dwt');
    }

}

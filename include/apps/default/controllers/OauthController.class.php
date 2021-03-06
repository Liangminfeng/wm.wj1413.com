<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：OauthController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch社会化登录
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class OauthController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public $errorRedirectUrl;
    
    public function index()
    {
        
        
        
        $type = I('get.type');
        $back_url = I('get.back_url', url('index/index'), 'urldecode');
        
        $this->back_act = $back_url?$back_url:url('index/index');
        $this->errorRedirectUrl = url("index/index");
                
        // 会员中心授权管理绑定
        $user_id = I('get.user_id', 0, 'intval');
        $file = ROOT_PATH . 'plugins/connect/' . $type . '.php';
        if (file_exists($file)) {
            include_once ($file);
              
        } else {
            show_message(L('process_false'), L('relogin_lnk'), url('user/login', array('back_act' => $this->errorRedirectUrl)), 'error');
        }

        // 处理url
        $url = U('index/', array('type' => $type, 'back_url' => $back_url), false, true);

        $info = model('ClipsBase')->get_third_user_info($type);
        // 判断是否安装
        if (!$info) {
             
            show_message(L('no_register_auth'), L('relogin_lnk'), url('user/login', array('back_act' => $this->errorRedirectUrl)), 'error');
        }
        $client = $type.'client';
        $obj = new $client($info);
        // 授权回调
        if (isset($_GET['code']) && $_GET['code'] != '') {
            if ($res = $obj->call_back($url, $_GET['code'])) {
                // 处理推荐u参数
                $up_uid = get_affiliate();  // 获得推荐uid
                $res['parent_id'] = $up_uid?$up_uid : 0;
                
                $res['unionid'] = $res['openid'];
                $_SESSION['openid'] = $res['openid'];
                $_SESSION['unionid'] = $res['unionid'];
                $_SESSION['parent_id'] = $res['parent_id'];
               

                // 会员中心授权管理绑定
                // if (isset($_SESSION['user_id']) && $user_id > 0 && $_SESSION['user_id'] == $user_id && !empty($res['unionid'])) {
                //     $this->UserBind($res, $user_id, $type);
                // }

                // 授权登录
                if ($this->oauthLogin($res, $type) === true) {
                    $this->redirect($back_url);
                }

                // 自动注册
                if (!empty($_SESSION['unionid']) && isset($_SESSION['unionid']) || $res['unionid']) {
                    $res['unionid'] = !empty($_SESSION['unionid']) ? $_SESSION['unionid'] : $res['unionid'];
                    $res['parent_id'] = !empty($_SESSION['parent_id']) ? $_SESSION['parent_id'] : $res['parent_id'];
                    // $res['nickname'] = session('nickname');
                    // $res['headimgurl'] = session('headimgurl');
                    if(strpos($this->back_act, "register")){

                        $first =0 ;

                    }else{

                        $first =1 ;

                    }
                    $this->doRegister($res, $type, $back_url,$first);
                } else {
                    show_message(L('msg_author_register_error'), L('msg_go_back'), url('user/login'), 'error');
                }
                return;
            } else {
                show_message(L('process_false'), L('relogin_lnk'), url('user/login', array('back_act' => urlencode($this->errorRedirectUrl))), 'error');
            }
            return;
        } else {
          
            // 开始授权登录
           

            $url = $obj->act_login($url);
            ecs_header("Location: " . $url . "\n");
            exit();
        }
    }
    

    /**
     * 会员中心授权管理绑定帐号
     * @param
     */
    private function UserBind($res, $user_id, $type)
    {
        // // 查询users用户是否存在
        // $users = $this->model->table('users')->field('user_id, user_name')->where(array('user_id' => $user_id))->find();
        // if ($users && !empty($res['unionid'])) {
        //     // 查询users用户是否被其他人绑定
        //     $connect_user_id = $this->model->table('connect_user')->field('user_id')->where(array('open_id' => $res['unionid'], 'connect_code' => 'sns_' . $type))->getOne();
        //     if ($connect_user_id > 0 && $connect_user_id != $users['user_id']) {
        //         show_message(L('msg_account_bound'), L('msg_rebound'), '', 'error');
        //     }
        //     // 更新社会化登录用户信息
        //     $res['user_id'] = $users['user_id'];
        //     model('Users')->update_connnect_user($res, $type);

        //     // 更新微信用户信息
        //     if (class_exists('WechatController') && is_wechat_browser() && $type == 'weixin') {
        //         $res['openid'] = session('openid');
        //         unset($res['user_id']); // 关联账号 登录不更新 ect_uid
        //         model('Users')->update_wechat_user($res);
        //     }

        //     // 重新登录
        //     $this->doLogin($users['username']);
        //     $back_url = empty($back_url) ? url('user/index') : $back_url;
        //     redirect($back_url);
        // } else {
        //     show_message('用户不存在', L('msg_go_back'), '', 'error');
        // }
        // return;
    }

    /**
     * 授权自动登录
     * @param  $res
     */
    private function oauthLogin($res, $type)
    {
        // 兼容原users表aite_id
        $aite_id = $res['type'] . '_' . $res['unionid'];
        $users = $this->model->table('users')->field('user_id')->where(array('aite_id' => $aite_id))->find();
        if(!empty($users)){
            // 清空aite_id
            // $this->model->table('users')->data(array('aite_id' => ''))->where(array('user_id' => $older_user['user_id']))->update();
            // 同步社会化登录表
            $res['user_id'] = $users['user_id'];
            model('Users')->update_connnect_user($res, $type);
        }
        
        // 兼容原touch_user_info表
        $aite_id = $res['type'] . '_' . $res['unionid'];
        $old_userinfo = model('Users')->get_one_user($aite_id);
        if(!empty($old_userinfo)){
            // 同步社会化登录表
            $res['user_id'] = $old_userinfo['user_id'];
            model('Users')->update_connnect_user($res, $type);
            // 删除旧表信息
            $where['user_id'] = $old_userinfo['user_id'];
            $this->model->table('touch_user_info')->where($where)->delete();
        }
      
        // 查询新用户
        $userinfo = model('Users')->get_connect_user($res['openid']);
        // 已经绑定过的 授权自动登录
        if ($userinfo) {
            //$this->doLogin($userinfo['user_name']);
            // 更新会员信息
            // $user_data = array(
            //     'nick_name' => $res['nickname'],
            //     'sex' => $res['sex'],
            //     'user_picture' => $res['headimgurl'],
            //     );
            // $this->model->table('users')->data($user_data)->where(array('user_id' => $userinfo['user_id']))->update();
            // 更新社会化登录用户信息
            // $res['user_id'] = !empty($userinfo['user_id']) ? $userinfo['user_id'] : $_SESSION['user_id'];
            // model('Users')->update_connnect_user($res, $type);
            // 更新微信用户信息
            // if(class_exists('WechatController') && is_wechat_browser() && $type == 'weixin'){
            //     $res['openid'] = session('openid');
            //     unset($res['user_id']); // 关联账号 登录不更新 ect_uid
            //     model('Users')->update_wechat_user($res);
            // }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置成登录状态
     * @param  $username
     */
    private function doLogin($username)
    {
        ECTouch::user()->set_session($username);
        ECTouch::user()->set_cookie($username);
        model('Users')->update_user_info();
        model('Users')->recalculate_price();
    }

    /**
     * 授权注册
     * @param        $res
     * @param string $back_url
     */
    private function doRegister($res, $type = '', $back_url = '', $is_drp = false,$first = 1)
    {

        //$username = model('Users')->get_wechat_username($res['unionid'], $type);
        $username = $this->createUsername();  
        $password = mt_rand(100000, 999999);
        $email = $username . '@qq.com';
        $extends = array(
            'parent_id' => $res['parent_id'],
            // 'nick_name' => $res['nickname'],
            'sex' => $res['sex'],
            // 'user_picture' => $res['headimgurl'],
        );
        // 查询是否绑定
        $userinfo = model('Users')->get_connect_user($res['openid']);
        if(empty($userinfo)){
          
        if (model('Users')->register($username, $password, $email, $extends) !== false) {
 //if (1) {
                // 同步社会化登录用户信息表
                 $res['user_id'] = $_SESSION['user_id'];
                model('Users')->update_connnect_user($res, $type);
                // // 更新用户信息
                model('Users')->update_user_info();

                // // 更新微信用户绑定信息
                if(class_exists('WechatController') && is_wechat_browser() && $type == 'wechat'){
                     // 查找微信用户是否已经绑定过
                     $result = $this->model->table('wechat_user')->where(array('ect_uid' => $_SESSION['user_id'], 'wechat_id' => 1))->find();
                     if (!empty($result)) {
                         show_message(L('msg_account_bound'), L('msg_go_back'), '', 'error');
                     }
                     $res['openid'] = session('openid');
                     model('Users')->update_wechat_user($res);
                     // 关注送红包
                //     // $this->sendBonus();
                // }
                // exit('注册成功');
                 $this->doLogin($username);
                // 跳转链接
   
             // model('Users')->unbindopenid($_SESSION['openid']);

             model('Users')->bindopenid($_SESSION['openid']);

            
                $back_url = empty($back_url) ? url('index/index') : $back_url;

                $this->redirect($back_url);
            } else {
                show_message(L('msg_author_register_error'), L('msg_re_registration'), url('user/login'), 'error');
            }
        }
            return;

        } else {
      
            show_message(L('msg_account_bound'), L('msg_go_back'), url('user/login'), 'error');
        }
        return;

    }

    /**
     * 关注送红包
     */
    private function sendBonus()
    {
        // 查询平台微信配置信息
        $wxinfo = dao('wechat')->field('id, token, appid, appsecret, encodingaeskey')->where(array('default_wx' => 1, 'status' => 1))->find();
        if ($wxinfo) {
            // 查询功能扩展 是否安装
            $rs = $this->model->table('wechat_extend')
            ->field('name, keywords, command, config')
            ->where('command = "bonus" and enable = 1 and wechat_id = ' . $wxinfo['id'])
            ->order('id asc')
            ->find();
            $file = ROOT_PATH . 'plugins/wechat/' . $rs['command'] . '/' . $rs['command'] . '.class.php';
            if (file_exists($file)) {
                require_once($file);
                $wechat = new $rs['command']();
                $data = $wechat->show($_SESSION['openid'], $rs);
                if (!empty($data)) {
                    $config['token'] = $wxinfo['token'];
                    $config['appid'] = $wxinfo['appid'];
                    $config['appsecret'] = $wxinfo['appsecret'];
                    $config['encodingaeskey'] = $wxinfo['encodingaeskey'];
                    $weObj = new Wechat($config);
                    $weObj->sendCustomMessage($data['content']);
                }
            }
        }
    }
    
    function createUsername(){
        $c=false;
        $username;
        while(!$c){
            $username = "W".rand(1000000,9999999);
            
            $users = $this->model->table('users')->field('user_id')->where(array('user_name' => $username))->find();
            $c=empty($users);
        }
        return $username;
        
    }    
}

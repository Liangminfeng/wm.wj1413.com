<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：UserModel.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch 用护模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');
class UsersModel extends BaseModel {

    protected $table = 'users';

    /**
     * 更新用护SESSION,COOKIE及登录时间、登录次数。
     *
     * @access  public
     * @return  void
     */
    function update_user_info($updateData=null) {
        if (!$_SESSION['user_id']) {
            return false;
        }

        /* 查询会员信息 */
        $time = date('Y-m-d');
        $sql = 'SELECT u.email,  u.user_rank,u.user_vip,u.resource, u.rank_points, ' .
                ' IFNULL(b.type_money, 0) AS user_bonus, u.last_login, u.last_ip' .
                ' FROM ' . $this->pre . 'users AS u ' .
                ' LEFT JOIN ' . $this->pre . 'user_bonus AS ub' .
                ' ON ub.user_id = u.user_id AND ub.used_time = 0 ' .
                ' LEFT JOIN ' . $this->pre . 'bonus_type AS b' .
                " ON b.type_id = ub.bonus_type_id AND b.use_start_date <= '$time' AND b.use_end_date >= '$time' " .
                " WHERE u.user_id = '$_SESSION[user_id]'";

        if ($row = $this->row($sql)) {
            /* 更新SESSION */
            $_SESSION['last_time'] = $row['last_login'];
            $_SESSION['last_ip'] = $row['last_ip'];
            $_SESSION['login_fail'] = 0;
            $_SESSION['email'] = $row['email'];
            $_SESSION['user_rank'] = $row['user_rank'];
            $_SESSION['user_vip'] = $row['user_vip'];
            $_SESSION['resource'] = $row['resource'];
            /* 判断是否是特殊等级，可能后台把特殊会员组更改普通会员组 */
            // if ($row['user_rank'] > 0) {
            //     $sql = "SELECT special_rank from " . $this->pre . "user_rank where rank_id='$row[user_rank]'";
            //     $res = $this->row($sql);
            //     if ($res['special_rank'] === '0' || $res['special_rank'] === null) {
            //         $sql = "update " . $this->pre . "users set user_rank='0' where user_id='$_SESSION[user_id]'";
            //         $this->query($sql);
            //         $row['user_rank'] = 0;
            //     }
            // }
            /* 取得用护等级和折扣 */
            // if ($row['user_rank'] == 0) {
            //     // 非特殊等级，根据等级积分计算用护等级（注意：不包括特殊等级）
            //     $sql = 'SELECT rank_id, discount FROM ' . $this->pre . "user_rank WHERE special_rank = '0' AND min_points <= " . intval($row['rank_points']) . ' AND max_points > ' . intval($row['rank_points']);
            //     if ($row = $this->row($sql)) {
            //         $_SESSION['user_rank'] = $row['rank_id'];
            //         $_SESSION['discount'] = $row['discount'] / 100.00;
            //     } else {
            //         $_SESSION['user_rank'] = 0;
            //         $_SESSION['discount'] = 1;
            //     }
            // } else {
            //     // 特殊等级
            //     $sql = 'SELECT rank_id, discount FROM ' . $this->pre . "user_rank WHERE rank_id = '$row[user_rank]'";
            //     if ($row = $this->row($sql)) {
            //         $_SESSION['user_rank'] = $row['rank_id'];
            //         $_SESSION['discount'] = $row['discount'] / 100.00;
            //     } else {
            //         $_SESSION['user_rank'] = 0;
            //         $_SESSION['discount'] = 1;
            //     }
            // }
        }

        /* 更新登录时间，登录次数及登录ip */
        $sql = "UPDATE " . $this->pre . "users SET" .
                " visit_count = visit_count + 1, " .
                " last_ip = '" . real_ip() . "'," .
                " last_login = '" . time() . "'" .
                " WHERE user_id = '" . $_SESSION['user_id'] . "'";
        $this->query($sql);
        if($updateData){
         
            $r = $this->update(["user_id"=>$_SESSION['user_id']],$updateData);
           
        }
    }
    function update_info_user($mid,$updateData=null){
         // $updateData['country'] = '中国';
         //     $updateData['province'] = '北京';
         //      $updateData['city'] = '北京';
         //       $updateData['parent_id'] = '0';

         //        $updateData['country_id'] = 1;
         //        $updateData['province_id'] = '2';
         //        $updateData['city_id'] = '52';
         //         $updateData['user_rank'] = '3';
        if($updateData){
            
            $r = $this->update(["user_id"=>$mid],$updateData);
        
            if($r){
                return true;
            }else{
                return false;
            }
        }
    }
    function new_update_info_user($account,$updateData=null){
         // $updateData['country'] = '中国';
         //     $updateData['province'] = '北京';
         //      $updateData['city'] = '北京';
         //       $updateData['parent_id'] = '0';

         //        $updateData['country_id'] = 1;
         //        $updateData['province_id'] = '2';
         //        $updateData['city_id'] = '52';
         //         $updateData['user_rank'] = '3';
        /**/
        $where['vip_manage_account'] = $account;
                   
        $res = $this->model->table('vip_account')
                ->field('user_id')
                ->where($where)
                ->find();

        if($updateData){
            
            $r = $this->update(["user_id"=>$res['user_id']],$updateData);
        
            if($r){
                return true;
            }else{
                return false;
            }
        }
    }
    function update_info_user_kd($mid,$updateData=null){
        if($updateData){
           
            $r = $this->update(["user_id"=>$mid],$updateData);
           
            if($r){
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     * 用护注册，登录函数
     *
     * @access  public
     * @param   string       $username          注册用护名
     * @param   string       $password          用护密码
     * @param   string       $email             注册email
     * @param   array        $other             注册的其他信息
     *
     * @return  bool         $bool
     */
    function register($username, $password, $email, $other = array()) {
     
        /* 检查注册是否关闭 */
        $shop_reg_closed = C('shop_reg_closed');
        if (!empty($shop_reg_closed)) {
            ECTouch::err()->add(L('shop_register_closed'));
        }
        /* 检查username */
        if (empty($username)) {
            ECTouch::err()->add(L('username_empty'));
        } else {
            if (preg_match('/\'\/^\\s*$|^c:\\\\con\\\\con$|[%,\\*\\"\\s\\t\\<\\>\\&\'\\\\]/', $username)) {
                ECTouch::err()->add(sprintf(L('username_invalid'), htmlspecialchars($username)));
            }
        }
        
        /* 检查email */
        // if (empty($email)) {
        //     ECTouch::err()->add(L('email_empty'));
        // } else {
        //     if (!is_email($email)) {
        //         ECTouch::err()->add(sprintf(L('email_invalid'), htmlspecialchars($email)));
        //     }
        // }

        if (ECTouch::err()->error_no > 0) {
            return false;
        }
        //判断用护名是否是手机号格式
        if (is_mobile($username)) {
            $mobile = 1;
        }

        /* 检查是否和管理员重名 */
        if (model('Users')->admin_registered($username)) {
            ECTouch::err()->add(sprintf(L('username_exist'), $username));
            return false;
        }

      
        if (!ECTouch::user()->add_user($username, $password, $email)) {
            
            if (ECTouch::user()->error == ERR_INVALID_USERNAME) {
                ECTouch::err()->add(sprintf(L('username_invalid'), $username));
            } elseif (ECTouch::user()->error == ERR_USERNAME_NOT_ALLOW) {
                ECTouch::err()->add(sprintf(L('username_not_allow'), $username));
            } elseif (ECTouch::user()->error == ERR_USERNAME_EXISTS) {
                ECTouch::err()->add(sprintf(L('username_exist'), $username));
            } elseif (ECTouch::user()->error == ERR_INVALID_EMAIL) {
                ECTouch::err()->add(sprintf(L('email_invalid'), $email));
            } elseif (ECTouch::user()->error == ERR_EMAIL_NOT_ALLOW) {
                ECTouch::err()->add(sprintf(L('email_not_allow'), $email));
            } elseif (ECTouch::user()->error == ERR_EMAIL_EXISTS) {
                ECTouch::err()->add(sprintf(L('email_exist'), $email));
            } else {
                ECTouch::err()->add('UNKNOWN ERROR!');
            }
   
            //注册失败
            return false;
        } else {
            //注册成功
       
            // model('Users')->update_nickname_headimg($_SESSION['nickname'],$_SESSION['headimgurl']);

            /* 设置成登录状态 */
            ECTouch::user()->set_session($username);
            ECTouch::user()->set_cookie($username);

            /* 注册送KD豆 */
            $register_points = C('register_points');
            /*生成account表第一条记录*/
            //model('Users')->insertAccount($_SESSION['user_id']);
            if($_SESSION['nickname']&&$_SESSION['headimgurl']){
               model('Users')->update_nickname_headimg($_SESSION['nickname'],$_SESSION['headimgurl'],$username); 
            }
       
           
            model('Users')->bindopenid($_SESSION['openid']);
          
            $r = model('ClipsBase')->new_log_account_change($_SESSION['user_id'],C('register_points'),L('register_points'),ACT_KD,6,1);

            if($other['parent_id']){
                 //如果是通过别人的推荐链接注册，给上级赠送KD豆        /*更新上级用户的KD豆*/
                $where33['user_id'] = $_SESSION['user_id'];
                $res33 = $this->model->table('users')
                        ->field('nick_name,user_name')
                        ->where($where33)
                        ->find();
                //$starusername = substr_replace($res33['user_name'],'****',3,4);
                $logName = empty($res33['nick_name'])?$res33['user_name']:$res33['nick_name'];
                //model('ClipsBase')->log_account_change($other['parent_id'], 0, 0, 0, 5, sprintf(L('register_affiliate'), $logName),ACT_KD);
                model('ClipsBase')->new_log_account_change($other['parent_id'],5,sprintf(L('register_affiliate'), $logName),ACT_KD,6);
            }
            //定义other合法的变量数组
            $other_key_array = array('msn', 'qq', 'office_phone', 'home_phone', 'mobile_phone', 'parent_id', 'aite_id','resource','invite_code','mobile_zone','user_vip');
            $update_data['reg_time'] = time();
            if ($other) {
                foreach ($other as $key => $val) {
                    //删除非法key值
                    if (!in_array($key, $other_key_array)) {
                        unset($other[$key]);
                    } else {
                        $other[$key] = htmlspecialchars(trim($val)); //防止用护输入javascript代码
                    }
                }
                $update_data = array_merge($update_data, $other);
            }
            $parent_id = $update_data['parent_id'];
           
            $condition['user_id'] = $_SESSION['user_id'];
            
            $r = $this->update($condition, $update_data);
           
            $where['mobile_phone'] = $username;
            $res = $this->model->table('users')
                        ->field('user_id')
                        ->where($where)
                        ->select();
            //用护名是手机号格式时且并不存在此手机号则把用护插入到手机号字段中
            if(($mobile == 1) && empty($res)){
                $sql = 'UPDATE ' . $this->pre . 'users SET mobile_phone = "' . $username . '" WHERE user_id = "' . $_SESSION['user_id'] . '"';
                $this->query($sql);
            }

            /* 推荐处理 */
            $affiliate = unserialize(C('affiliate'));
            if (isset($affiliate['on']) && $affiliate['on'] == 1) {
                // 推荐开关开启
                //$up_uid = get_affiliate();
                 $up_uid = $parent_id;
                empty($affiliate) && $affiliate = array();
                $affiliate['config']['level_register_all'] = intval($affiliate['config']['level_register_all']);
                $affiliate['config']['level_register_up'] = intval($affiliate['config']['level_register_up']);
                if ($up_uid || $parent_id) {
                    if (!empty($affiliate['config']['level_register_all'])) {
                        if (!empty($affiliate['config']['level_register_up'])) {
                            $up_uid =  $up_uid > 0 ? $up_uid : $parent_id;
                            $res = $this->row("SELECT rank_points FROM " . $this->pre . "users WHERE user_id = '$up_uid'");
                            if ($res['rank_points'] + $affiliate['config']['level_register_all'] <= $affiliate['config']['level_register_up']) {
                                //model('ClipsBase')->log_account_change($up_uid, 0, 0, $affiliate['config']['level_register_all'], 0, sprintf(L('register_affiliate'), $_SESSION['user_id'], $username),ACT_KD);
                                model('ClipsBase')->new_log_account_change($up_uid,$affiliate['config']['level_register_all'],sprintf(L('register_affiliate'), $_SESSION['user_id'], $username),ACT_KD,5);
                            }
                        } else {
                             //model('ClipsBase')->log_account_change($up_uid, 0, 0, $affiliate['config']['level_register_all'], 0, L('register_affiliate'),ACT_KD);
                             model('ClipsBase')->new_log_account_change($up_uid,$affiliate['config']['level_register_all'],L('register_affiliate'),ACT_KD,5);
                        }
                    }
                    
                    //设置推荐人
                    $sql = 'UPDATE ' . $this->pre . 'users SET parent_id = ' . $up_uid . ' WHERE user_id = ' . $_SESSION['user_id'];
                    $this->query($sql);
                    /*寻找是否有上上级*/
                    $where1['user_id'] = $up_uid;
                   
                    $res1 = $this->model->table('users')
                        ->field('parent_id')
                        ->where($where1)
                        ->find();
                   
                    if($res1['parent_id']){
                        /*有上上级*/
                        $u_up_uid = $res1['parent_id'];
                    }    
                    $where2['user_id'] =  $_SESSION['user_id'];
                    $res2 = $this->model->table('users')
                        ->field('nick_name,user_name')
                        ->where($where2)
                        ->find();

                    
                    $starusername = substr_replace($res2['user_name'],'****',3,4);
                    $username = empty($res2['nick_name'])?$starusername:$res2['nick_name'];


                    /*当有新加入的伙伴的时候发送短信提醒*/
                     // if (class_exists('WechatController') && is_wechat_browser() ) {
                     if (class_exists('WechatController') ) {
                          $pushData = array(
                            
                                        'first' => array('value' => '您有新的合作伙伴加入','color' => '#173177'),
                                        'keyword1' => array('value' =>date("Y-m-d H:i:s",time()),'color' => '#000'),  // 订单号
                                        'keyword2' => array('value' => $username,'color' => '#000'),   //// 付款金额
                                        'remark' => array('value' => '点击查看详情','color' => '#173177')
                                    );
                                    // $url = __URL__ . 'index.php?c=user&a=order_detail&order_id='.$order_id;
                                    $url = __HOST__ . U('user/affiliate_partner_new');
                               
                         $result = pushTemplate('OPENTM412358456', $pushData, $url,$up_uid);
                          
                         if($res1['parent_id']){
                            /*有上上级*/
                  
                            // $pushData = array(
                            
                            //             'first' => array('value' => '您有新的合作伙伴加入','color' => '#173177'),
                            //             'keyword1' => array('value' =>date("Y-m-d H:i:s",time()),'color' => '#000'),  // 订单号
                            //             'keyword2' => array('value' => '[二级]'.$username,'color' => '#000'),   //// 付款金额
                            //             'remark' => array('value' => '点击查看详情','color' => '#173177')
                            //         );
                            //         // $url = __URL__ . 'index.php?c=user&a=order_detail&order_id='.$order_id;
                            //         $url = __HOST__ . U('user/affiliate_partner_new');
                                  
                            //          pushTemplate('OPENTM412358456', $pushData, $url,$res1['parent_id']);
                         }


                     }
                    
                }
            }

            model('Users')->update_user_info();    
            model('Users')->recalculate_price();  
               // 重新计算购物车中的商品价格

            return true;
        }
    }
    
    function wechatLogin($wechatData){
        
    }
    
    function wechatRegister($wechatData){
        /* 检查注册是否关闭 */
        
        /* 检查username */
        if (empty($username)) {
            ECTouch::err()->add(L('username_empty'));
        } else {
            if (preg_match('/\'\/^\\s*$|^c:\\\\con\\\\con$|[%,\\*\\"\\s\\t\\<\\>\\&\'\\\\]/', $username)) {
                ECTouch::err()->add(sprintf(L('username_invalid'), htmlspecialchars($username)));
            }
        }
        
        /* 检查email */
        // if (empty($email)) {
        //     ECTouch::err()->add(L('email_empty'));
        // } else {
        //     if (!is_email($email)) {
        //         ECTouch::err()->add(sprintf(L('email_invalid'), htmlspecialchars($email)));
        //     }
        // }
        
        if (ECTouch::err()->error_no > 0) {
            return false;
        }
        //判断用护名是否是手机号格式
        if (is_mobile($username)) {
            $mobile = 1;
        }
        
        /* 检查是否和管理员重名 */
        if (model('Users')->admin_registered($username)) {
            ECTouch::err()->add(sprintf(L('username_exist'), $username));
            return false;
        }
        
        
        if (!ECTouch::user()->add_user($username, $password, $email)) {
            
            if (ECTouch::user()->error == ERR_INVALID_USERNAME) {
                ECTouch::err()->add(sprintf(L('username_invalid'), $username));
            } elseif (ECTouch::user()->error == ERR_USERNAME_NOT_ALLOW) {
                ECTouch::err()->add(sprintf(L('username_not_allow'), $username));
            } elseif (ECTouch::user()->error == ERR_USERNAME_EXISTS) {
                ECTouch::err()->add(sprintf(L('username_exist'), $username));
            } elseif (ECTouch::user()->error == ERR_INVALID_EMAIL) {
                ECTouch::err()->add(sprintf(L('email_invalid'), $email));
            } elseif (ECTouch::user()->error == ERR_EMAIL_NOT_ALLOW) {
                ECTouch::err()->add(sprintf(L('email_not_allow'), $email));
            } elseif (ECTouch::user()->error == ERR_EMAIL_EXISTS) {
                ECTouch::err()->add(sprintf(L('email_exist'), $email));
            } else {
                ECTouch::err()->add('UNKNOWN ERROR!');
            }
            
            //注册失败
            return false;
        } else {
            //注册成功
            
            // model('Users')->update_nickname_headimg($_SESSION['nickname'],$_SESSION['headimgurl']);
            
            /* 设置成登录状态 */
            ECTouch::user()->set_session($username);
            ECTouch::user()->set_cookie($username);
            
            /* 注册送KD豆 */
            $register_points = C('register_points');
            /*生成account表第一条记录*/
            //model('Users')->insertAccount($_SESSION['user_id']);
            if($_SESSION['nickname']&&$_SESSION['headimgurl']){
                model('Users')->update_nickname_headimg($_SESSION['nickname'],$_SESSION['headimgurl'],$username);
            }
            
            
            model('Users')->bindopenid($_SESSION['openid']);
            
            $r = model('ClipsBase')->new_log_account_change($_SESSION['user_id'],C('register_points'),L('register_points'),ACT_KD,6,1);
            
            if($other['parent_id']){
                //如果是通过别人的推荐链接注册，给上级赠送KD豆        /*更新上级用户的KD豆*/
                $where33['user_id'] = $_SESSION['user_id'];
                $res33 = $this->model->table('users')
                ->field('nick_name,user_name')
                ->where($where33)
                ->find();
                //$starusername = substr_replace($res33['user_name'],'****',3,4);
                $logName = empty($res33['nick_name'])?$res33['user_name']:$res33['nick_name'];
                //model('ClipsBase')->log_account_change($other['parent_id'], 0, 0, 0, 5, sprintf(L('register_affiliate'), $logName),ACT_KD);
                model('ClipsBase')->new_log_account_change($other['parent_id'],5,sprintf(L('register_affiliate'), $logName),ACT_KD,6);
            }
            //定义other合法的变量数组
            $other_key_array = array('msn', 'qq', 'office_phone', 'home_phone', 'mobile_phone', 'parent_id', 'aite_id','resource','invite_code','mobile_zone,user_vip');
            $update_data['reg_time'] = time();
            if ($other) {
                foreach ($other as $key => $val) {
                    //删除非法key值
                    if (!in_array($key, $other_key_array)) {
                        unset($other[$key]);
                    } else {
                        $other[$key] = htmlspecialchars(trim($val)); //防止用护输入javascript代码
                    }
                }
                $update_data = array_merge($update_data, $other);
            }
            $parent_id = $update_data['parent_id'];
            
            $condition['user_id'] = $_SESSION['user_id'];
            
            $r = $this->update($condition, $update_data);
            
            $where['mobile_phone'] = $username;
            $res = $this->model->table('users')
            ->field('user_id')
            ->where($where)
            ->select();
            //用护名是手机号格式时且并不存在此手机号则把用护插入到手机号字段中
            if(($mobile == 1) && empty($res)){
                $sql = 'UPDATE ' . $this->pre . 'users SET mobile_phone = "' . $username . '" WHERE user_id = "' . $_SESSION['user_id'] . '"';
                $this->query($sql);
            }
            
            /* 推荐处理 */
            $affiliate = unserialize(C('affiliate'));
            if (isset($affiliate['on']) && $affiliate['on'] == 1) {
                // 推荐开关开启
                //$up_uid = get_affiliate();
                $up_uid = $parent_id;
                empty($affiliate) && $affiliate = array();
                $affiliate['config']['level_register_all'] = intval($affiliate['config']['level_register_all']);
                $affiliate['config']['level_register_up'] = intval($affiliate['config']['level_register_up']);
                if ($up_uid || $parent_id) {
                    if (!empty($affiliate['config']['level_register_all'])) {
                        if (!empty($affiliate['config']['level_register_up'])) {
                            $up_uid =  $up_uid > 0 ? $up_uid : $parent_id;
                            $res = $this->row("SELECT rank_points FROM " . $this->pre . "users WHERE user_id = '$up_uid'");
                            if ($res['rank_points'] + $affiliate['config']['level_register_all'] <= $affiliate['config']['level_register_up']) {
                                //model('ClipsBase')->log_account_change($up_uid, 0, 0, $affiliate['config']['level_register_all'], 0, sprintf(L('register_affiliate'), $_SESSION['user_id'], $username),ACT_KD);
                                model('ClipsBase')->new_log_account_change($up_uid,$affiliate['config']['level_register_all'],sprintf(L('register_affiliate'), $_SESSION['user_id'], $username),ACT_KD,5);
                            }
                        } else {
                            //model('ClipsBase')->log_account_change($up_uid, 0, 0, $affiliate['config']['level_register_all'], 0, L('register_affiliate'),ACT_KD);
                            model('ClipsBase')->new_log_account_change($up_uid,$affiliate['config']['level_register_all'],L('register_affiliate'),ACT_KD,5);
                        }
                    }
                    
                    //设置推荐人
                    $sql = 'UPDATE ' . $this->pre . 'users SET parent_id = ' . $up_uid . ' WHERE user_id = ' . $_SESSION['user_id'];
                    $this->query($sql);
                    /*寻找是否有上上级*/
                    $where1['user_id'] = $up_uid;
                    
                    $res1 = $this->model->table('users')
                    ->field('parent_id')
                    ->where($where1)
                    ->find();
                    
                    if($res1['parent_id']){
                        /*有上上级*/
                        $u_up_uid = $res1['parent_id'];
                    }
                    $where2['user_id'] =  $_SESSION['user_id'];
                    $res2 = $this->model->table('users')
                    ->field('nick_name,user_name')
                    ->where($where2)
                    ->find();
                    
                    
                    $starusername = substr_replace($res2['user_name'],'****',3,4);
                    $username = empty($res2['nick_name'])?$starusername:$res2['nick_name'];
                    
                    
                    /*当有新加入的伙伴的时候发送短信提醒*/
                    // if (class_exists('WechatController') && is_wechat_browser() ) {
                    if (class_exists('WechatController') ) {
                        $pushData = array(
                            
                            'first' => array('value' => '您有新的合作伙伴加入','color' => '#173177'),
                            'keyword1' => array('value' =>date("Y-m-d H:i:s",time()),'color' => '#000'),  // 订单号
                            'keyword2' => array('value' => $username,'color' => '#000'),   //// 付款金额
                            'remark' => array('value' => '点击查看详情','color' => '#173177')
                        );
                        // $url = __URL__ . 'index.php?c=user&a=order_detail&order_id='.$order_id;
                        $url = __HOST__ . U('user/affiliate_partner_new');
                        
                        $result = pushTemplate('OPENTM412358456', $pushData, $url,$up_uid);
                        
                        if($res1['parent_id']){
                            /*有上上级*/
                            
                            // $pushData = array(
                            
                            //             'first' => array('value' => '您有新的合作伙伴加入','color' => '#173177'),
                            //             'keyword1' => array('value' =>date("Y-m-d H:i:s",time()),'color' => '#000'),  // 订单号
                            //             'keyword2' => array('value' => '[二级]'.$username,'color' => '#000'),   //// 付款金额
                            //             'remark' => array('value' => '点击查看详情','color' => '#173177')
                            //         );
                            //         // $url = __URL__ . 'index.php?c=user&a=order_detail&order_id='.$order_id;
                            //         $url = __HOST__ . U('user/affiliate_partner_new');
                            
                            //          pushTemplate('OPENTM412358456', $pushData, $url,$res1['parent_id']);
                        }
                        
                        
                    }
                    
                }
            }
            
            model('Users')->update_user_info();
            model('Users')->recalculate_price();
            // 重新计算购物车中的商品价格
            
            return true;
        }
    }
    
    
    function getChildNum($user_id){

        $sql = "SELECT count(*) as num FROM " . $this->pre . "users WHERE parent_id = '$user_id'";
        $row = $this->row($sql);
        return $row['num'];
    }
    function getChildVipNum($user_id){

        $sql = "SELECT count(*) as num FROM " . $this->pre . "users WHERE parent_id = '$user_id' and user_rank>0";
        $row = $this->row($sql);
        return $row['num'];
    }
    /**
     *  发送激活验证邮件
     *
     * @access  public
     * @param   int     $user_id        用护ID
     *
     * @return boolen
     */
    function send_regiter_hash($user_id) {
        /* 设置验证邮件模板所需要的内容信息 */
        $template = model('Base')->get_mail_template('register_validate');
        $hash = model('Users')->register_hash('encode', $user_id);
        $validate_email = __HOST__ . url('user/validate_email', array('hash' => $hash)); //ECTouch::ecs()->url() . 'user.php?act=validate_email&hash=' . $hash;

        $sql = "SELECT user_name, email FROM " . $this->pre . "users WHERE user_id = '$user_id'";
        $row = $this->row($sql);

        ECTouch::view()->assign('user_name', $row['user_name']);
        ECTouch::view()->assign('validate_email', $validate_email);
        ECTouch::view()->assign('shop_name', C('shop_name'));
        ECTouch::view()->assign('send_date', date(C('date_format')));

        $content = ECTouch::view()->fetch('str:' . $template['template_content']);

        /* 发送激活验证邮件 */
        if (send_mail($row['user_name'], $row['email'], $template['template_subject'], $content, $template['is_html'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *  生成邮件验证hash
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function register_hash($operation, $key) {
        if ($operation == 'encode') {
            $user_id = intval($key);
            $sql = "SELECT reg_time " .
                    " FROM " . $this->pre .
                    "users WHERE user_id = '$user_id' LIMIT 1";
            $res = $this->row($sql);
            $reg_time = $res['reg_time'];
            $hash = substr(md5($user_id . C('hash_code') . $reg_time), 16, 4);

            return base64_encode($user_id . ',' . $hash);
        } else {
            $hash = base64_decode(trim($key));
            $row = explode(',', $hash);
            if (count($row) != 2) {
                return 0;
            }
            $user_id = intval($row[0]);
            $salt = trim($row[1]);

            if ($user_id <= 0 || strlen($salt) != 4) {
                return 0;
            }

            $sql = "SELECT reg_time " .
                    " FROM " . $this->pre .
                    "users WHERE user_id = '$user_id' LIMIT 1";
            $res = $this->row($sql);
            $reg_time = $res['reg_time'];
            $pre_salt = substr(md5($user_id . C('hash_code') . $reg_time), 16, 4);

            if ($pre_salt == $salt) {
                return $user_id;
            } else {
                return 0;
            }
        }
    }

    /**
     * 判断超级管理员用护名是否存在
     * @param   string      $adminname 超级管理员用护名
     * @return  boolean
     */
    function admin_registered($adminname) {
        $sql = "SELECT COUNT(*) as count FROM " . $this->pre .
                "admin_user WHERE user_name = '$adminname'";
        $res = $this->row($sql);
        return $res['count'];
    }

    /**
     * 修改个人资料（Email, 性别，生日)
     *
     * @access  public
     * @param   array       $profile       array_keys(user_id int, email string, sex int, birthday string);
     *
     * @return  boolen      $bool
     */
    function edit_profile($profile) {
        if (empty($profile['user_id'])) {
            ECTouch::err()->add(L('not_login'));
            return false;
        }

        $cfg = array();
        $sql = "SELECT user_name FROM " . $this->pre . "users WHERE user_id='" . $profile['user_id'] . "'";
        $res = $this->row($sql);
        $cfg['username'] = $res['user_name'];
        if (isset($profile['sex'])) {
            $cfg['gender'] = intval($profile['sex']);
        }
        if (!empty($profile['email'])) {
            if (!is_email($profile['email'])) {
                ECTouch::err()->add(sprintf(L('email_invalid'), $profile['email']));

                return false;
            }
            $cfg['email'] = $profile['email'];
        }
        if (!empty($profile['birthday'])) {
            $cfg['bday'] = $profile['birthday'];
        }


        if (!ECTouch::user()->edit_user($cfg)) {
            if (ECTouch::user()->error == ERR_EMAIL_EXISTS) {
                ECTouch::err()->add(sprintf(L('email_exist'), $profile['email']));
            } else {
                ECTouch::err()->add('DB ERROR!');
            }

            return false;
        }

        /* 过滤非法的键值 */
        $other_key_array = array('msn', 'qq', 'office_phone', 'home_phone', 'mobile_phone');
        foreach ($profile['other'] as $key => $val) {
            //删除非法key值
            if (!in_array($key, $other_key_array)) {
                unset($profile['other'][$key]);
            } else {
                $profile['other'][$key] = htmlspecialchars(trim($val)); //防止用护输入javascript代码
            }
        }
        /* 修改在其他资料 */
        if (!empty($profile['other'])) {
            $condition['user_id'] = $profile['user_id'];
            $this->update($condition, $profile['other']);
        }

        return true;
    }

    /**
     * 获取用护帐号信息
     *
     * @access  public
     * @param   int       $user_id        用护user_id
     *
     * @return void
     */
    function get_profile($user_id) {

        /* 会员帐号信息 */
        $info = array();
        $infos = array();
        $sql = "SELECT user_name,vip_manage_account,user_vip,level_xp,real_name,resource,openid,birthday,vip_manage_account, parent_id,sex,autonym,resource, question,user_avatar,reg_time,job,sign,signcomment,nick_name, answer, rank_points,  user_rank," .
                " msn, qq, office_phone, home_phone, mobile_phone, passwd_question, lang,passwd_answer,vx_no,line_no,address " .
                "FROM " . $this->pre . "users WHERE user_id = '$user_id'";
        $infos = $this->row($sql);
        $infos['user_name'] = addslashes($infos['user_name']);

        $row = ECTouch::user()->get_profile_by_name($infos['user_name']); //获取用护帐号信息
        $_SESSION['email'] = $row['email'];    //注册SESSION

        /* 会员等级 */
        if ($infos['user_rank'] > 0) {
            $sql = "SELECT rank_id, rank_name, discount FROM " . $this->pre .
                    "user_rank WHERE rank_id = '$infos[user_rank]'";
        } else {
            $sql = "SELECT rank_id, rank_name, discount, min_points" .
                    " FROM " . $this->pre .
                    "user_rank WHERE min_points<= " . intval($infos['rank_points']) . " ORDER BY min_points DESC";
        }

        if ($row = $this->row($sql)) {
            $info['rank_name'] = $row['rank_name'];
        } else {
            $info['rank_name'] = L('undifine_rank');
        }

        $cur_date = date('Y-m-d H:i:s');

        /* 会员红包 */
        $bonus = array();
        $sql = "SELECT type_name, type_money " .
                "FROM " . $this->pre . "bonus_type AS t1, " . $this->pre . "user_bonus AS t2 " .
                "WHERE t1.type_id = t2.bonus_type_id AND t2.user_id = '$user_id' AND t1.use_start_date <= '$cur_date' " .
                "AND t1.use_end_date > '$cur_date' AND t2.order_id = 0";
        $bonus = $this->query($sql);
        if ($bonus) {
            for ($i = 0, $count = count($bonus); $i < $count; $i++) {
                $bonus[$i]['type_money'] = price_format($bonus[$i]['type_money'], false);
            }
        }
        $info['user_id'] = $user_id;
        $info['discount'] = $_SESSION['discount'] * 100 . "%";
        $info['email'] = $_SESSION['email'];
        $info['autonym'] = $infos['autonym'];
        $info['parent_id'] = $infos['parent_id'];
        $info['user_name'] = $infos['user_name'];
        $info['rank_points'] = isset($infos['rank_points']) ? $infos['rank_points'] : '';
       
        // $info['user_money'] = isset($infos['user_money']) ? $infos['user_money'] : 0;
        $info['sex'] = isset($infos['sex']) ? $infos['sex'] : 0;
        $info['birthday'] = isset($infos['birthday']) ? $infos['birthday'] : '';
        $info['question'] = isset($infos['question']) ? htmlspecialchars($infos['question']) : '';
        $info['vip_manage_account'] = $infos['vip_manage_account'];
        // $info['user_money'] = price_format($info['user_money'], false);
        // $info['pay_points'] = $info['pay_points'] . C('integral_name');
        $info['bonus'] = $bonus;
        $info['qq'] = $infos['qq'];
        $info['msn'] = $infos['msn'];
        $info['office_phone'] = $infos['office_phone'];
        $info['home_phone'] = $infos['home_phone'];
        $info['mobile_phone'] = $infos['mobile_phone'];
        $info['passwd_question'] = $infos['passwd_question'];
        $info['passwd_answer'] = $infos['passwd_answer'];
        $info['user_rank'] = $infos['user_rank'];
        $info['user_avatar'] = $infos['user_avatar'];
        $info['nick_name'] = getEmoji($infos['nick_name']);
        $info['job'] = $infos['job'];
        $info['signcomment'] = $infos['signcomment'];
        $info['sign'] = $infos['sign'];
        $info['vx_no'] = $infos['vx_no'];
        $info['user_vip'] = $infos['user_vip'];
        $info['real_name'] = $infos['real_name'];
        $info['reg_time'] = $infos['reg_time'];
        $info['line_no'] = $infos['line_no'];
        $info['address'] = $infos['address'];
        $info['resource'] = $infos['resource'];
        $info['lang'] = $infos['lang'];
        $info['level_xp'] = $infos['level_xp'];
        $info['vip_manage_account'] = $infos['vip_manage_account'];
        return $info;
    }

    /**
     * 取得收货人地址列表
     * @param   int     $user_id    用护编号
     * @param   int     $id         收货地址id
     * @return  array
     */
    function get_consignee_list($user_id, $id = 0, $num = 10, $start = 0) {
        if ($id) {
            $where['user_id'] = $user_id;
            $where['address_id'] = $id;
            $this->table = 'user_address';
            return $this->find($where);
        } else {
            $sql = 'select ua.*,u.address_id as adds_id from ' . $this->pre . 'user_address as ua left join '. $this->pre . 'users as u on ua.address_id =u.address_id'. ' where ua.user_id = ' . $user_id . ' order by ua.address_id limit ' . $start . ', ' . $num;

            return $this->query($sql);
        }
    }

    /**
     *  给指定用护添加一个指定红包
     *
     * @access  public
     * @param   int         $user_id        用护ID
     * @param   string      $bouns_sn       红包序列号
     *
     * @return  boolen      $result
     */
    function add_bonus($user_id, $bouns_sn) {
        if (empty($user_id)) {
            ECTouch::err()->add(L('not_login'));

            return false;
        }
        /* 查询红包序列号是否已经存在 */
        $sql = "SELECT bonus_id, bonus_sn, user_id, bonus_type_id FROM " . $this->pre .
                "user_bonus WHERE bonus_sn = '$bouns_sn'";
        $row = $this->row($sql);
        if ($row) {
            if ($row['user_id'] == 0) {
                //红包没有被使用
                $sql = "SELECT send_end_date, use_end_date " .
                        " FROM " . $this->pre .
                        "bonus_type WHERE type_id = '" . $row['bonus_type_id'] . "'";

                $bonus_time = $this->row($sql);

                $now = time();
                if ($now > $bonus_time['use_end_date']) {
                    ECTouch::err()->add(L('bonus_use_expire'));
                    return false;
                }

                $sql = "UPDATE " . $this->pre . "user_bonus SET user_id = '$user_id' " .
                        "WHERE bonus_id = '$row[bonus_id]'";
                $result = $this->query($sql);
                if ($result) {
                    return true;
                } else {
                    return M()->errorMsg();
                }
            } else {
                if ($row['user_id'] == $user_id) {
                    //红包已经添加过了。
                    ECTouch::err()->add(L('bonus_is_used'));
                } else {
                    //红包被其他人使用过了。
                    ECTouch::err()->add(L('bonus_is_used_by_other'));
                }

                return false;
            }
        } else {
            //红包不存在
            ECTouch::err()->add(L('bonus_not_exist'));
            return false;
        }
    }

    /**
     *  获取用护指定范围的订单列表
     *
     * @access  public
     * @param   int         $user_id        用护ID号
     * @param   int         $pay            订单状态，0未付款，1全部，默认1
     * @param   int         $num            列表最大数量
     * @param   int         $start          列表起始位置
     * @return  array       $order_list     订单列表
     */
    function get_user_orders($user_id, $pay = 1, $num = 10, $start = 0) {
        /* 取得订单列表 */
        $arr = array();

        if ($pay == 1) {
            $pay = '';
        } elseif($pay == 0) {
            // 未付款 但不包含已取消、无效、退货订单的订单
            $pay = 'and pay_status = ' . PS_UNPAYED . ' and order_status not in(' . OS_CANCELED . ','. OS_INVALID .','. OS_RETURNED .')';
        }else{
            $pay = 'and pay_status = ' . PS_UNPAYED ;
        }

        $sql = "SELECT order_id, order_sn, shipping_id,order_type,order_amount, order_status, shipping_status, pay_status,shipping_fee, add_time, " .
                "(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee " .
                " FROM " . $this->pre .
                "order_info WHERE user_id = '$user_id' and order_status!=".OS_HIDE." ".  $pay . " ORDER BY add_time DESC LIMIT $start , $num";
            
        $res = M()->query($sql);
        foreach ($res as $key => $value) {
           //未付款订单超市设置为取消
            if((time()-$value['add_time'])>3600&&$value['pay_status']==0){

                $sql1 = "UPDATE " . $this->pre . "order_info SET order_status = '" . OS_CANCELED . "' WHERE order_id = '{$value['order_id']}'";
               
                M()->query($sql1);

            }
            
            
            if ($value['order_status'] == OS_UNCONFIRMED) {

                $value['handler'] = "<a href=\"" . url('user/cancel_order', array('order_id' => $value['order_id'])) . "\" onclick=\"if (!confirm('" . L('confirm_cancel') . "')) return false;\">" . L('cancel') . "</a>";
            } else if ($value['order_status'] == OS_SPLITED) {
                /* 对配送状态的处理 */

                if ($value['shipping_status'] == SS_SHIPPED) {
                    @$value['handler'] = "<a href=\"" . url('user/affirm_received', array('order_id' => $value['order_id'])) . "\" onclick=\"if (!confirm('" . L('confirm_received') . "')) return false;\">" . L('received') . "</a>";
                } elseif ($value['shipping_status'] == SS_RECEIVED) {
                    @$value['handler'] = '<span style="color:red">' . L('ss_received') . '</span>';
                } else {

                    if ($value['pay_status'] == PS_UNPAYED) {

                        @$value['handler'] = "<a class=\"Zbtn ZsubB\" href=\"" . url('user/cancel_order', array('order_id' => $value['order_id'])) . "\">" . L('pay_money') . "</a>";
                    } else {
                        
                                 @$value['handler'] = "<a class=\"Zbtn ZsubB\" href=\"" . url('user/order_detail', array('order_id' => $value['order_id'])) . "\">" ."确认收货". "</a>";
                           

                       
                    }
                }
            } else {
                if($value['order_status']==4){
                    $value['handler'] = '<span class="ZsubE"></span>';
                    
                }else{
                     if($value['order_status']==1&&$value['pay_status']==2){
                        // $value['handler'] = '<span class="Zbtn ZsubC">' . "待发货" . '</span>';
                         $value['handler'] = '<span class="daifahuo"></span>';
                     }else{


                            if($value['order_status']==7){
                                  $value['handler'] = '<span class="ZsubE"></span>';
                            }else{
                                  $value['handler'] = '<span class="ZsubE"> </span>';
                            }


                       
                     }
                    
                }
               
            }
          

            $value['shipping_status'] = ($value['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $value['shipping_status'];
            
            if($value['order_status']==7){
                $value['order_status'] = "待激活";
            }else{
                if($value['order_status']==4){
                    $value['order_status'] = "已退货";
                }else{
                    $value['order_status'] = L('os.' . $value['order_status']) . ',' . L('ps.' . $value['pay_status']) . ',' . L('ss.' . $value['shipping_status']);
                }
                
            }
            

           // var_dump( $value['order_status']);
            if(strpos($value['order_status'], "待激活")){
                $value['order_status'] = "待激活";
            }
            if(strpos($value['order_status'], "未付款")){
                
                if(strpos($value['order_status'],"取消")){
                    
                     $value['order_status'] = "已取消";
                }else{
                     if(strpos($value['order_status'], "退货")){
                          $value['order_status'] = "已退货";
                        }else{
                            $value['order_status'] = "待付款";
                        }
                     
                }
               
               
            }
            if(strpos($value['order_status'], "已付款")){


             

                 if(strpos($value['order_status'],"收货确认")){
                            $value['order_status'] = "已收货";
                        }elseif(strpos($value['order_status'],"已发货")){

                           
                             $value['order_status'] = "待收货";
                           
                        }else{
                            
                             $value['order_status'] = "待发货";
                        }
            }

            if(strpos($value['order_status'], "收货确认")){
                $value['order_status'] = "待评价";
            }
            $order_type = explode(",", $value['order_type']);
     
            foreach ($order_type as $key1 => $value1) {
                # code...
                switch ($value1) {
                    case '1':
                        # code...
                        $value['newordertype'] .= '经销商,';
                        $value['order_type1'] = 1;
                        break;
                    case '2':

                        $value['newordertype'] .= '重消,';
                        $value['order_type1'] = 2;
                        break;
                    case '3':

                        $value['newordertype'] .= '差额升级,';
                        $value['order_type1'] = 3;
                        break;
                    case '4':

                        $value['newordertype'] .= '原价升级,';
                        $value['order_type1'] = 4;
                        break;
                    case '5':

                        $value['newordertype'] .= '重购,';
                        $value['order_type1'] = 5;
                        break;
                    case '9':

                        $value['newordertype'] .= 'VIP套餐,';
                        $value['order_type1'] = 9;
                        break;
                    case '10':

                        $value['newordertype'] .= '列车,';
                        $value['order_type1'] = 10;
                        break;
                    default:
                        # code...
                        $value['newordertype'] .= '零售,';
                        $value['order_type1'] = 0;
                        break;
                }
            }
                
          
                       $arr[] = array(
                'order_id' => $value['order_id'],
                'order_sn' => $value['order_sn'],
                'order_type' =>rtrim($value['newordertype'],","),
                'order_type1' =>$value['order_type1'],
                'img' => get_image_path(0, model('Order')->get_order_thumb($value['order_id'])),
                'order_time' => date("Y-m-d H:i:s", $value['add_time']),
                'order_status' => $value['order_status'],
                'shipping_id' => $value['shipping_id'],
                'shipping_fee' =>$value['shipping_fee'],
                'total_fee' => $value['order_amount'],
                'url' => url('user/order_detail', array('order_id' => $value['order_id'])),
                'goods_count' => model('Users')->get_order_goods_count($value['order_id']),
                'handler' => $value['handler']
            );

        }
        return $arr;
    }
        /**
     *  获取用护指定范围的订单列表
     *
     * @access  public
     * @param   int         $user_id        用护ID号
     * @param   int         $pay            订单状态，0未付款，1全部，默认1
     * @param   int         $num            列表最大数量
     * @param   int         $start          列表起始位置
     * @return  array       $order_list     订单列表
     */
    function get_user_orders_type($user_id, $pay = 1) {
        /* 取得订单列表 */
        $arr = array();

        if ($pay == 1) {
            $pay = '';
        } elseif($pay == 0) {
            // 未付款 但不包含已取消、无效、退货订单的订单
            $pay = 'and pay_status = ' . PS_UNPAYED . ' and order_status not in(' . OS_CANCELED . ','. OS_INVALID .','. OS_RETURNED .')';
        }else{
            $pay = 'and pay_status = ' . PS_UNPAYED ;
        }

        $sql = "SELECT order_id, order_sn, shipping_id,order_type,order_amount, order_status, shipping_status, pay_status,shipping_fee, add_time, " .
                "(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee " .
                " FROM " . $this->pre .
                "order_info WHERE user_id = '$user_id' and order_status!=".OS_HIDE." ".  $pay ;
        
        $res = M()->query($sql);
        foreach ($res as $key => $value) {
           //未付款订单超市设置为取消
            if((time()-$value['add_time'])>3600&&$value['pay_status']==0){

                $sql1 = "UPDATE " . $this->pre . "order_info SET order_status = '" . OS_CANCELED . "' WHERE order_id = '{$value['order_id']}'";
               
                M()->query($sql1);

            }
            
            
            if ($value['order_status'] == OS_UNCONFIRMED) {

                $value['handler'] = "<a href=\"" . url('user/cancel_order', array('order_id' => $value['order_id'])) . "\" onclick=\"if (!confirm('" . L('confirm_cancel') . "')) return false;\">" . L('cancel') . "</a>";
            } else if ($value['order_status'] == OS_SPLITED) {
                /* 对配送状态的处理 */

                if ($value['shipping_status'] == SS_SHIPPED) {
                    @$value['handler'] = "<a href=\"" . url('user/affirm_received', array('order_id' => $value['order_id'])) . "\" onclick=\"if (!confirm('" . L('confirm_received') . "')) return false;\">" . L('received') . "</a>";
                } elseif ($value['shipping_status'] == SS_RECEIVED) {
                    @$value['handler'] = '<span style="color:red">' . L('ss_received') . '</span>';
                } else {

                    if ($value['pay_status'] == PS_UNPAYED) {

                        @$value['handler'] = "<a class=\"Zbtn ZsubB\" href=\"" . url('user/cancel_order', array('order_id' => $value['order_id'])) . "\">" . L('pay_money') . "</a>";
                    } else {
                        
                                 @$value['handler'] = "<a class=\"Zbtn ZsubB\" href=\"" . url('user/order_detail', array('order_id' => $value['order_id'])) . "\">" ."确认收货". "</a>";
                           

                       
                    }
                }
            } else {
                if($value['order_status']==4){
                    $value['handler'] = '<span class="ZsubE"></span>';
                    
                }else{
                     if($value['order_status']==1&&$value['pay_status']==2){
                        // $value['handler'] = '<span class="Zbtn ZsubC">' . "待发货" . '</span>';
                         $value['handler'] = '<span class="daifahuo"></span>';
                     }else{


                            if($value['order_status']==7){
                                  $value['handler'] = '<span class="ZsubE"></span>';
                            }else{
                                  $value['handler'] = '<span class="ZsubE"> </span>';
                            }


                       
                     }
                    
                }
               
            }
          

            $value['shipping_status'] = ($value['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $value['shipping_status'];
            
            if($value['order_status']==7){
                $value['order_status'] = "待激活";
            }else{
                if($value['order_status']==4){
                    $value['order_status'] = "已退货";
                }else{
                    $value['order_status'] = L('os.' . $value['order_status']) . ',' . L('ps.' . $value['pay_status']) . ',' . L('ss.' . $value['shipping_status']);
                }
                
            }
            

           // var_dump( $value['order_status']);
            if(strpos($value['order_status'], "待激活")){
                $value['order_status'] = "待激活";
            }
            if(strpos($value['order_status'], "未付款")){
                
                if(strpos($value['order_status'],"取消")){
                    
                     $value['order_status'] = "已取消";
                }else{
                     if(strpos($value['order_status'], "退货")){
                          $value['order_status'] = "已退货";
                        }else{
                            $value['order_status'] = "待付款";
                        }
                     
                }
               
               
            }
            if(strpos($value['order_status'], "已付款")){


             

                 if(strpos($value['order_status'],"收货确认")){
                            $value['order_status'] = "已收货";
                        }elseif(strpos($value['order_status'],"已发货")){

                           
                             $value['order_status'] = "待收货";
                           
                        }else{
                            
                             $value['order_status'] = "待发货";
                        }
            }

            if(strpos($value['order_status'], "收货确认")){
                $value['order_status'] = "待评价";
            }
                switch ($value['order_type']) {
                    case '1':
                        # code...
                        $value['order_type'] = '入团';
                        $value['order_type1'] = 1;
                        break;
                    case '2':

                        $value['order_type'] = '重消';
                        $value['order_type1'] = 2;
                        break;
                    case '3':

                        $value['order_type'] = '差额升级';
                        $value['order_type1'] = 3;
                        break;
                    case '4':

                        $value['order_type'] = '原价升级';
                        $value['order_type1'] = 4;
                        break;
                    case '5':

                        $value['order_type'] = '重购';
                        $value['order_type1'] = 5;
                        break;
                    case '9':

                        $value['order_type'] = 'VIP套餐';
                        $value['order_type1'] = 9;
                        break;
                    default:
                        # code...
                        $value['order_type'] = '零售';
                        $value['order_type1'] = 0;
                        break;
                }
            
                       $arr[] = array(
                'order_id' => $value['order_id'],
                'order_sn' => $value['order_sn'],
                'order_type' =>$value['order_type'],
                'order_type1' =>$value['order_type1'],
                'img' => get_image_path(0, model('Order')->get_order_thumb($value['order_id'])),
                'order_time' => date("Y-m-d H:i:s", $value['add_time']),
                'order_status' => $value['order_status'],
                'shipping_id' => $value['shipping_id'],
                'shipping_fee' =>$value['shipping_fee'],
                'total_fee' => $value['order_amount'],
                'url' => url('user/order_detail', array('order_id' => $value['order_id'])),
                'goods_count' => model('Users')->get_order_goods_count($value['order_id']),
                'handler' => $value['handler'],
                'pay_status'=>$value['pay_status']
            );

        }
        return $arr;
    }

    /**
     *  获取用护未收货订单列表
     *
     * @access  public
     * @param   int         $user_id        用护ID号
     * @param   int         $pay            订单状态，0未付款，1全部，默认1
     * @param   int         $num            列表最大数量
     * @param   int         $start          列表起始位置
     * @return  array       $order_list     订单列表
     */
    function not_shouhuo_orders($user_id, $pay = 1, $num = 10, $start = 0) {
        /* 取得订单列表 */
        $arr = array();

        if ($pay == 1) {
            $pay = 'and pay_status = ' . PS_PAYED . ' and shipping_status not in(' . SS_RECEIVED .')';


        } else {

            $pay = '';
        }

        $sql = "SELECT order_id, order_sn, shipping_id, order_status,order_type, shipping_status, shipping_fee,pay_status, add_time, " .
                "(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee " .
                " FROM " . $this->pre .
                "order_info WHERE user_id = '$user_id' and order_status<6  " . $pay . " ORDER BY add_time DESC LIMIT $start , $num";

        $res = M()->query($sql);




        foreach ($res as $key => $value) {
            if ($value['order_status'] == OS_UNCONFIRMED) {
                $value['handler'] = "<a href=\"" . url('user/cancel_order', array('order_id' => $value['order_id'])) . "\" onclick=\"if (!confirm('" . L('confirm_cancel') . "')) return false;\">" . L('cancel') . "</a>";
            } else if ($value['order_status'] == OS_SPLITED) {
                /* 对配送状态的处理 */
                if ($value['shipping_status'] == SS_SHIPPED) {
                    @$value['handler'] = "<a class=\"Zbtn ZsubB\" href=\"" . url('user/affirm_received', array('order_id' => $value['order_id'])) . "\" onclick=\"if (!confirm('" . L('confirm_received') . "')) return false;\">" . L('received') . "</a>";
                } elseif ($value['shipping_status'] == SS_RECEIVED) {
                    @$value['handler'] = '<span style="color:red">' . L('ss_received') . '</span>';
                } else {
                   if ($value['pay_status'] == PS_UNPAYED) {

                        @$value['handler'] = "<a class=\"Zbtn ZsubB\" href=\"" . url('user/cancel_order', array('order_id' => $value['order_id'])) . "\">" . L('pay_money') . "</a>";
                    } else {
                        
                                 @$value['handler'] = "<a class=\"Zbtn ZsubB\" href=\"" . url('user/order_detail', array('order_id' => $value['order_id'])) . "\">" ."确认收货". "</a>";
                           

                       
                    }
                }
            } else {
                if($value['order_status']==1&&$value['pay_status']==2){
                         //$value['handler'] = '<span class="Zbtn ZsubC">' . "待发货" . '</span>';
                        $value['handler'] = '<span class="daifahuo"></span>';
                     }else{
                         $value['handler'] = '<span class="Zbtn ZsubC">' . L('os.' . $value['order_status']) . '</span>';
                     }
              // $value['handler'] = '<span class='.'"Zbtn ZsubB">' . L('os.' . $value['order_status']) . '</span>';
            }

            $value['shipping_status'] = ($value['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $value['shipping_status'];

            $value['order_status'] = L('os.' . $value['order_status']) . ',' . L('ps.' . $value['pay_status']) . ',' . L('ss.' . $value['shipping_status']);
        
            if(strpos($value['order_status'], "未付款")){
                $value['order_status'] = "待付款";
            }
            if(strpos($value['order_status'], "已付款")){


             

                 if(strpos($value['order_status'],"收货确认")){
                            $value['order_status'] = "已收货";
                        }elseif(strpos($value['order_status'],"已发货")){

                           
                             $value['order_status'] = "待收货";
                           
                        }else{
                            
                             $value['order_status'] = "待发货";
                        }
            }
            if(strpos($value['order_status'], "收货确认")){
                $value['order_status'] = "待评价";
            }
            
            $order_type = explode(",", $value['order_type']);
     
            foreach ($order_type as $key1 => $value1) {
                # code...
                switch ($value1) {
                    case '1':
                        # code...
                        $value['newordertype'] .= '入团,';
                        $value['order_type1'] = 1;
                        break;
                    case '2':

                        $value['newordertype'] .= '重消,';
                        $value['order_type1'] = 2;
                        break;
                    case '3':

                        $value['newordertype'] .= '差额升级,';
                        $value['order_type1'] = 3;
                        break;
                    case '4':

                        $value['newordertype'] .= '原价升级,';
                        $value['order_type1'] = 4;
                        break;
                    case '5':

                        $value['newordertype'] .= '重购,';
                        $value['order_type1'] = 5;
                        break;
                    case '9':

                        $value['newordertype'] .= 'VIP套餐,';
                        $value['order_type1'] = 9;
                        break;
                    default:
                        # code...
                        $value['newordertype'] .= '零售,';
                        $value['order_type1'] = 0;
                        break;
                }
            }
            
            $arr[] = array('order_id' => $value['order_id'],
                'order_sn' => $value['order_sn'],
                'order_type' =>rtrim($value['newordertype'],","),
                'order_type1' => $value['order_type1'],
                'img' => get_image_path(0, model('Order')->get_order_thumb($value['order_id'])),
                'order_time' => date("Y-m-d H:i:s", $value['add_time']),
                'order_status' => $value['order_status'],
                'shipping_id' => $value['shipping_id'],
                'shipping_fee' => $value['shipping_fee'],
                'total_fee' => $value['total_fee'],
                'url' => url('user/order_detail', array('order_id' => $value['order_id'])),
                'goods_count' => model('Users')->get_order_goods_count($value['order_id']),
                'handler' => $value['handler']);
        }
        return $arr;
    }
    /**
     * 取消一个用护订单
     *
     * @access  public
     * @param   int         $order_id       订单ID
     * @param   int         $user_id        用护ID
     *
     * @return void
     */
    function cancel_order($order_id, $user_id = 0) {

        /* 查询订单信息，检查状态 */
        $sql = "SELECT user_id, order_id, order_sn , surplus , integral , bonus_id, order_status, shipping_status, pay_status FROM " . $this->pre . "order_info WHERE order_id = '$order_id'";
        $order = $this->row($sql);

        if (empty($order)) {
            ECTouch::err()->add(L('order_exist'));
            return false;
        }

        // 如果用护ID大于0，检查订单是否属于该用护
        if ($user_id > 0 && $order['user_id'] != $user_id) {
            ECTouch::err()->add(L('no_priv'));

            return false;
        }

        // 订单状态只能是“未确认”或“已确认”
        if ($order['order_status'] != OS_UNCONFIRMED && $order['order_status'] != OS_CONFIRMED) {
            ECTouch::err()->add(L('current_os_not_unconfirmed'));

            return false;
        }

        //订单一旦确认，不允许用护取消
        if ($order['order_status'] == OS_CONFIRMED) {
            ECTouch::err()->add(L('current_os_already_confirmed'));

            return false;
        }

        // 发货状态只能是“未发货”
        if ($order['shipping_status'] != SS_UNSHIPPED) {
            ECTouch::err()->add(L('current_ss_not_cancel'));

            return false;
        }

        // 如果付款状态是“已付款”、“付款中”，不允许取消，要取消和商家联系
        if ($order['pay_status'] != PS_UNPAYED) {
            ECTouch::err()->add(L('current_ps_not_cancel'));

            return false;
        }

        //将用护订单设置为取消
        $sql = "UPDATE " . $this->pre . "order_info SET order_status = '" . OS_CANCELED . "' WHERE order_id = '$order_id'";
    
        if ($this->query($sql)) {
            /* 记录log */
            model('OrderBase')->order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED, L('buyer_cancel'), 'buyer');
            /* 退货用护余额、积分、红包 */
            if ($order['user_id'] > 0 && $order['surplus'] > 0) {
                $change_desc = sprintf(L('return_surplus_on_cancel'), $order['order_sn']);
                //model('ClipsBase')->log_account_change($order['user_id'], $order['surplus'], 0, 0, 0, $change_desc);
                model('ClipsBase')->new_log_account_change($order['user_id'],$order['surplus'],$change_desc,ACT_OTHER,1);
            }
            if ($order['user_id'] > 0 && $order['integral'] > 0) {
                $change_desc = sprintf(L('return_integral_on_cancel'), $order['order_sn']);

                // model('ClipsBase')->log_account_change($order['user_id'], 0, 0, 0, $order['integral'], $change_desc);
                 model('ClipsBase')->new_log_account_change($order['user_id'],$order['integral'],$change_desc,ACT_OTHER,6);
            }
            if ($order['user_id'] > 0 && $order['bonus_id'] > 0) {
                model('Order')->change_user_bonus($order['bonus_id'], $order['order_id'], false);
            }

            /* 如果使用库存，且下订单时减库存，则增加库存 */
            if (C('use_storage') == '1' && C('stock_dec_time') == SDT_PLACE) {
                model('Order')->change_order_goods_storage($order['order_id'], false, 1);
            }

            /* 修改订单 */
            $arr = array(
                'bonus_id' => 0,
                'bonus' => 0,
                'integral' => 0,
                'integral_money' => 0,
                'surplus' => 0
            );
            model('Users')->update_order($order['order_id'], $arr);

            return true;
        } else {
            die(M()->errorMsg());
        }
    }

    /**
     * 确认一个用护订单
     *
     * @access  public
     * @param   int         $order_id       订单ID
     * @param   int         $user_id        用护ID
     *
     * @return  bool        $bool
     */
    function affirm_received($order_id, $user_id = 0) {

        /* 查询订单信息，检查状态 */
        $sql = "SELECT user_id, order_sn , order_status, shipping_status, pay_status FROM " . $this->pre . "order_info WHERE order_id = '$order_id'";

        $order = $this->row($sql);

        // 如果用护ID大于 0 。检查订单是否属于该用护
        if ($user_id > 0 && $order['user_id'] != $user_id) {
            ECTouch::err()->add(L('no_priv'));

            return false;
        }
        /* 检查订单 */ elseif ($order['shipping_status'] == SS_RECEIVED) {
            ECTouch::err()->add(L('order_already_received'));

            return false;
        } elseif ($order['shipping_status'] != SS_SHIPPED) {
            ECTouch::err()->add(L('order_invalid'));

            return false;
        }
        /* 修改订单发货状态为“确认收货” */ 
        else {
            $confirm_receive_time  =time();
            $sql = "UPDATE " . $this->pre . "order_info SET shipping_status = '" . SS_RECEIVED . "', confirm_receive_time = '$confirm_receive_time' WHERE order_id = '$order_id' ";
  
            if ($this->query($sql)) {
                /* 记录日志 */
                model('OrderBase')->order_action($order['order_sn'], $order['order_status'], SS_RECEIVED, $order['pay_status'], '', L('buyer'));

                return true;
            } else {
                die(M()->errorMsg());
            }
        }
    }

    /**
     * 保存用护的收货人信息
     * 如果收货人信息中的 id 为 0 则新增一个收货人信息
     *
     * @access  public
     * @param   array   $consignee
     * @param   boolean $default        是否将该收货人信息设置为默认收货人信息
     * @return  boolean
     */
    function save_consignee($consignee, $default = false) {
        if ($consignee['address_id'] > 0) {
            /* 修改地址 */
            $this->table = 'user_address';
            $data['address_id'] = $consignee['address_id'];

            $condition['address_id'] = $consignee['address_id'];
            $condition['user_id'] = $_SESSION['user_id'];
            $res = $this->update($condition, $consignee);
        } else {
            /* 添加地址 */
            $this->table = 'user_address';
            $res = $this->insert($consignee);
            $consignee['address_id'] = M()->insert_id();
        }

        if ($default) {
            /* 保存为用护的默认收货地址 */
            $sql = "UPDATE " . $this->pre .
                    "users SET address_id = '$consignee[address_id]' WHERE user_id = '$_SESSION[user_id]'";
            $sql1 = "UPDATE " . $this->pre .
                    "user_address SET country = '$consignee[country]',province = '$consignee[province]',city = '$consignee[city]',district = '$consignee[district]' WHERE address_id = '$consignee[address_id]'";
            
            $res1 = $this->query($sql1);
           
            $res = $this->query($sql);
           
        }

        return $res !== false;
    }

    /**
     * 删除一个收货地址
     *
     * @access  public
     * @param   integer $id
     * @return  boolean
     */
    function drop_consignee($id) {
        $sql = "SELECT user_id FROM " . $this->pre . "user_address WHERE address_id = '$id'";
        $res = $this->row($sql);
        $uid = $res['user_id'];

        if ($uid != $_SESSION['user_id']) {
            return false;
        } else {
            $sql = "DELETE FROM " . $this->pre . "user_address WHERE address_id = '$id'";
            $res = $this->query($sql);
            return $res;
        }
    }

    /**
     *  添加或更新指定用护收货地址
     *
     * @access  public
     * @param   array       $address
     * @return  bool
     */
    function update_address($address) {
        $address_id = intval($address['address_id']);
        unset($address['address_id']);
        $this->table = 'user_address';
        if ($address_id > 0) {
            /* 更新指定记录 */
            $condition['address_id'] = $address_id;
            $condition['user_id'] = $address['user_id'];
            $this->update($condition, $address);
        } else {
            /* 插入一条新记录 */
            $this->insert($address);
            $address_id = M()->insert_id();
        }

        if (isset($address['defalut']) && $address['default'] > 0 && isset($address['user_id'])) {
            $sql = "UPDATE " . $this->pre .
                    "users SET address_id = '" . $address_id . "' " .
                    " WHERE user_id = '" . $address['user_id'] . "'";
            $this->query($sql);
        }

        return true;
    }

    /**
     *  获取指订单的详情
     *
     * @access  public
     * @param   int         $order_id       订单ID
     * @param   int         $user_id        用护ID
     *
     * @return   arr        $order          订单所有信息的数组
     */
    function get_order_detail($order_id, $user_id = 0) {

        $order_id = intval($order_id);
        if ($order_id <= 0) {
            ECTouch::err()->add(L('invalid_order_id'));

            return false;
        }
        $order = model('Order')->order_info($order_id);

        //检查订单是否属于该用护
        if ($user_id > 0 && $user_id != $order['user_id']) {
            ECTouch::err()->add(L('no_priv'));

            return false;
        }

        /* 对发货号处理 */
        if (!empty($order['invoice_no'])) {
            $sql = "SELECT shipping_code FROM " . $this->pre . "shipping WHERE shipping_id = '$order[shipping_id]'";
            $res = $this->row($sql);
            $shipping_code = $res['shipping_code'];
            $plugin = ADDONS_PATH . 'shipping/' . $shipping_code . '.php';
            if (file_exists($plugin)) {
                include_once($plugin);
                $shipping = new $shipping_code;
                $order_tracking = $shipping->query($order['invoice_no']);
                $order['order_tracking'] = ($order_tracking == $order['invoice_no']) ? 0:1;
            }
        }

        /* 只有未确认才允许用护修改订单地址 */
        if ($order['order_status'] == OS_UNCONFIRMED) {
            $order['allow_update_address'] = 1; //允许修改收货地址
        } else {
            $order['allow_update_address'] = 0;
        }

        /* 获取订单中实体商品数量 */
        $order['exist_real_goods'] = model('Order')->exist_real_goods($order_id);

        /* 如果是未付款状态，生成支付按钮 */
        if ($order['pay_status'] == PS_UNPAYED && ($order['order_status'] == OS_UNCONFIRMED || $order['order_status'] == OS_CONFIRMED)) {
            /*
             * 在线支付按钮
             */
            //支付方式信息
            $payment_info = array();
            $payment_info = Model('Order')->payment_info($order['pay_id']);
            // 只保留显示手机版支付方式
            if(!file_exists(ROOT_PATH . 'plugins/payment/'.$payment_info['pay_code'].'.php')){
                $payment_info = false;
            }

            //无效支付方式
            if ($payment_info === false || substr($payment_info['pay_code'], 0 , 4) == 'pay_') {
               
                $order['pay_online'] = '';
            } else {
                //取得支付信息，生成支付代码
             
                $payment = unserialize_config($payment_info['pay_config']);

                 if($payment_info['pay_id']==4){
                //智付通支付

                include_once (ROOT_PATH . 'plugins/payment/' . $payment_info ['pay_code'] . '.php');
            //获取需要支付的log_id
            $order['log_id'] = model('ClipsBase')->get_paylog_id($order['order_id'], $pay_type = PAY_ORDER);
            $pay_obj = new $payment_info ['pay_code'] ();
            $payment_info ['pay_config']['mpg_using'] = 0 ;
            $payment_info ['pay_config']['mpg_merchantid'] = "MS351491967" ;
            $payment_info ['pay_config']['mpg_key'] = "PPK8Vhs8vtlJYD8cvIWaGBj9pEuCWvvn" ;
            $payment_info ['pay_config']['mpg_iv'] = "Lhr7vgFwnt2rvIhM" ;
            $payment_info ['pay_config']['pay_button'] = "前往付款" ;
            
            $order['pay_online'] = $pay_obj->get_code($order, $payment_info ['pay_config']);

            $order ['pay_desc'] = $payment_info ['pay_desc'];
            }else{

                //获取需要支付的log_id
                $order['log_id'] = model('ClipsBase')->get_paylog_id($order['order_id'], $pay_type = PAY_ORDER);
                $order['user_name'] = $_SESSION['user_name'];
                $order['pay_desc'] = $payment_info['pay_desc'];

                /* 调用相应的支付方式文件 */
                include_once(ROOT_PATH . 'plugins/payment/' . $payment_info['pay_code'] . '.php');
      
                /* 取得在线支付方式的支付按钮 */
                $pay_obj = new $payment_info['pay_code'];

                $order['pay_online'] = $pay_obj->get_code($order, $payment);
                
            }
                
               
            }
        } else {
            $order['pay_online'] = '';
        }

        //查询订单使用的支付方式
        $sql = "SELECT pay_code  FROM ".$this->pre."payment where pay_id = '".$order['pay_id']."'" ." and enabled = 1 and is_online = 1";
        $pay = $this->row($sql);
        //普通订单用支付宝付款、已失败、已付款、未发货可申请退款
        if($order['order_status'] == 1 && $order['shipping_status'] == 0 && $order['pay_status'] == 2 && $pay['pay_code'] == 'alipay'){
            $order['can_cancle'] = 1;
        }


        /* 无配送时的处理 */
        $order['shipping_id'] == -1 and $order['shipping_name'] = L('shipping_not_need');

        /* 其他信息初始化 */
        $order['how_oos_name'] = $order['how_oos'];
        $order['how_surplus_name'] = $order['how_surplus'];
        $order['pay_status1'] = $order['pay_status'];
        $order['shipping_status1'] = $order['shipping_status'];
        $order['confirm_receive_status1'] = $order['confirm_receive_status'];
        $order['count_time'] = $order['add_time']+3600;
        $order['add_time'] = date("Y-m-d H:i:s", $order['add_time']);
        /* 虚拟商品付款后处理 */
        if ($order['pay_status'] != PS_UNPAYED) {
            /* 取得已发货的虚拟商品信息 */
            $virtual_goods = model('OrderBase')->get_virtual_goods($order_id, true);
            $virtual_card = array();
            foreach ($virtual_goods AS $code => $goods_list) {
                /* 只处理虚拟卡 */
                if ($code == 'virtual_card') {
                    foreach ($goods_list as $goods) {
                        if ($info = model('OrderBase')->virtual_card_result($order['order_sn'], $goods)) {
                            $virtual_card[] = array('goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'info' => $info);
                        }
                    }
                }
                /* 处理超值礼包里面的虚拟卡 */
                if ($code == 'package_buy') {
                    foreach ($goods_list as $goods) {
                        $sql = 'SELECT g.goods_id FROM ' . $this->pre . 'package_goods AS pg, ' . $this->pre . 'goods AS g ' .
                                "WHERE pg.goods_id = g.goods_id AND pg.package_id = '" . $goods['goods_id'] . "' AND extension_code = 'virtual_card'";
                        $vcard_arr = $this->query($sql);

                        foreach ($vcard_arr AS $val) {
                            if ($info = model('OrderBase')->virtual_card_result($order['order_sn'], $val)) {
                                $virtual_card[] = array('goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'info' => $info);
                            }
                        }
                    }
                }
            }
            $var_card = deleteRepeat($virtual_card);
            ECTouch::view()->assign('virtual_card', $var_card);
        }

        /* 确认时间 支付时间 发货时间 */
        if ($order['confirm_time'] > 0 && ($order['order_status'] == OS_CONFIRMED || $order['order_status'] == OS_SPLITED || $order['order_status'] == OS_SPLITING_PART)) {
            $order['confirm_time'] = sprintf(L('confirm_time'), local_date(C('time_format'), $order['confirm_time']));
        } else {
            $order['confirm_time'] = '';
        }
        if ($order['pay_time'] > 0 && $order['pay_status'] != PS_UNPAYED) {
             $order['add_time'] = date("Y-m-d H:i:s", $order['add_time']);
            $order['pay_time'] = sprintf(L('pay_time'), local_date(C('time_format'), $order['add_time']));
        } else {
            $order['pay_time'] = '';
        }
        if ($order['shipping_time'] > 0 && in_array($order['shipping_status'], array(SS_SHIPPED, SS_RECEIVED))) {
            $order['shipping_time'] = sprintf(L('shipping_time'), date("Y-m-d H:i:s", $order['shipping_time']));
        } else {
            $order['shipping_time'] = '';
        }
       
        return $order;
    }

        /**
     *  获取指订单的详情
     *
     * @access  public
     * @param   int         $order_id       订单ID
     * @param   int         $user_id        用护ID
     *
     * @return   arr        $order          订单所有信息的数组
     */
    function get_order_detail_new($order_id, $user_id = 0) {

        $order_id = intval($order_id);

        if ($order_id <= 0) {
            
            ECTouch::err()->add(L('invalid_order_id'));

            return false;
        }
       
        $order = model('Order')->order_info($order_id);
        
    
        //检查订单是否属于该用护
        if ($user_id > 0 && $user_id != $order['user_id']) {
            
            //ECTouch::err()->add(L('no_priv'));
            ECTouch::err()->show(L('no_priv'), './');
            return false;
        }

        /* 对发货号处理 */
        if (!empty($order['invoice_no'])) {
            $sql = "SELECT shipping_code FROM " . $this->pre . "shipping WHERE shipping_id = '$order[shipping_id]'";
            $res = $this->row($sql);
            $shipping_code = $res['shipping_code'];
            $plugin = ADDONS_PATH . 'shipping/' . $shipping_code . '.php';
            if (file_exists($plugin)) {
                include_once($plugin);
                $shipping = new $shipping_code;
                $order_tracking = $shipping->query($order['invoice_no']);
                $order['order_tracking'] = ($order_tracking == $order['invoice_no']) ? 0:1;
            }
        }

        /* 只有未确认才允许用护修改订单地址 */
        if ($order['order_status'] == OS_UNCONFIRMED) {
            $order['allow_update_address'] = 1; //允许修改收货地址
        } else {
            $order['allow_update_address'] = 0;
        }

        /* 获取订单中实体商品数量 */
        $order['exist_real_goods'] = model('Order')->exist_real_goods($order_id);

        /* 如果是未付款状态，生成支付按钮 */
    
        if ($order['pay_status'] == PS_UNPAYED && ($order['order_status'] == OS_UNCONFIRMED || $order['order_status'] == OS_CONFIRMED)) {
      

            /*
             * 在线支付按钮
             */
            //支付方式信息
            $payment_info = array();
            $payment_info = Model('Order')->payment_info(1);

            // 只保留显示手机版支付方式
            if(!file_exists(ROOT_PATH . 'plugins/payment/'.$payment_info['pay_code'].'.php')){
                $payment_info = false;
            }
    
            //无效支付方式
            if ($payment_info === false || substr($payment_info['pay_code'], 0 , 4) == 'pay_') {
              
                $order['pay_online'] = '';
            } else {
            
                //取得支付信息，生成支付代码
                $payment = unserialize_config($payment_info['pay_config']);
   
                //获取需要支付的log_id
                $order['log_id'] = model('ClipsBase')->get_paylog_id($order['order_id'], $pay_type = PAY_ORDER);
                $order['user_name'] = $_SESSION['user_name'];
                $order['pay_desc'] = $payment_info['pay_desc'];

                /* 调用相应的支付方式文件 */
                include_once(ROOT_PATH . 'plugins/payment/' . $payment_info['pay_code'] . '.php');

                /* 取得在线支付方式的支付按钮 */
          
                $pay_obj = new $payment_info['pay_code']();
                
                $order['pay_online'] = $pay_obj->get_code($order, $payment);

            }

            $payment_info1 = array();
            $payment_info1 = Model('Order')->payment_info(5);
            // 只保留显示手机版支付方式
            if(!file_exists(ROOT_PATH . 'plugins/payment/'.$payment_info1['pay_code'].'.php')){
                $payment_info1 = false;
            }

            //无效支付方式
            if ($payment_info1 === false || substr($payment_info1['pay_code'], 0 , 4) == 'pay_') {
                $order1['pay_online1'] = '';
            } else {
                //取得支付信息，生成支付代码
                $payment1 = unserialize_config($payment_info1['pay_config']);

                //获取需要支付的log_id
                $order1['log_id'] = model('ClipsBase')->get_paylog_id($order['order_id'], $pay_type = PAY_ORDER);
                $order1['user_name'] = $_SESSION['user_name'];
                $order1['pay_desc'] = $payment_info1['pay_desc'];

                /* 调用相应的支付方式文件 */
                include_once(ROOT_PATH . 'plugins/payment/' . $payment_info1['pay_code'] . '.php');

                /* 取得在线支付方式的支付按钮 */
                $pay_obj1 = new $payment_info1['pay_code']();
                $order['pay_online1'] = $pay_obj1->get_code($order1, $payment1);
            }

     
        } else {
           
            $order['pay_online'] = '';
        }


        //查询订单使用的支付方式
        $sql = "SELECT pay_code  FROM ".$this->pre."payment where pay_id = '".$order['pay_id']."'" ." and enabled = 1 and is_online = 1";
        $pay = $this->row($sql);
        //普通订单用支付宝付款、已失败、已付款、未发货可申请退款
        if($order['order_status'] == 1 && $order['shipping_status'] == 0 && $order['pay_status'] == 2 && $pay['pay_code'] == 'alipay'){
            $order['can_cancle'] = 1;
        }

     
        /* 无配送时的处理 */
        $order['shipping_id'] == -1 and $order['shipping_name'] = L('shipping_not_need');

        /* 其他信息初始化 */
        $order['how_oos_name'] = $order['how_oos'];
        $order['how_surplus_name'] = $order['how_surplus'];
        $order['pay_status1'] = $order['pay_status'];
        $order['shipping_status1'] = $order['shipping_status'];
        $order['confirm_receive_status1'] = $order['confirm_receive_status'];
        $order['count_time'] = $order['add_time']+3600;
        $order['add_time'] = date("Y-m-d H:i:s", $order['add_time']);
   
        /* 虚拟商品付款后处理 */
        if ($order['pay_status'] != PS_UNPAYED) {
            /* 取得已发货的虚拟商品信息 */
            $virtual_goods = model('OrderBase')->get_virtual_goods($order_id, true);
            $virtual_card = array();
            foreach ($virtual_goods AS $code => $goods_list) {
                /* 只处理虚拟卡 */
                if ($code == 'virtual_card') {
                    foreach ($goods_list as $goods) {
                        if ($info = model('OrderBase')->virtual_card_result($order['order_sn'], $goods)) {
                            $virtual_card[] = array('goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'info' => $info);
                        }
                    }
                }
                /* 处理超值礼包里面的虚拟卡 */
                if ($code == 'package_buy') {
                    foreach ($goods_list as $goods) {
                        $sql = 'SELECT g.goods_id FROM ' . $this->pre . 'package_goods AS pg, ' . $this->pre . 'goods AS g ' .
                                "WHERE pg.goods_id = g.goods_id AND pg.package_id = '" . $goods['goods_id'] . "' AND extension_code = 'virtual_card'";
                        $vcard_arr = $this->query($sql);

                        foreach ($vcard_arr AS $val) {
                            if ($info = model('OrderBase')->virtual_card_result($order['order_sn'], $val)) {
                                $virtual_card[] = array('goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'info' => $info);
                            }
                        }
                    }
                }
            }
            $var_card = deleteRepeat($virtual_card);
            ECTouch::view()->assign('virtual_card', $var_card);
        }

        /* 确认时间 支付时间 发货时间 */
        if ($order['confirm_time'] > 0 && ($order['order_status'] == OS_CONFIRMED || $order['order_status'] == OS_SPLITED || $order['order_status'] == OS_SPLITING_PART)) {
            $order['confirm_time'] = sprintf(L('confirm_time'), local_date(C('time_format'), $order['confirm_time']));
        } else {
            $order['confirm_time'] = '';
        }
        if ($order['pay_time'] > 0 && $order['pay_status'] != PS_UNPAYED) {
             //$order['add_time'] = date("Y-m-d H:i:s", $order['add_time']);
            $order['pay_time'] = $order['add_time'];
        } else {
            $order['pay_time'] = '';
        }
        if ($order['shipping_time'] > 0 && in_array($order['shipping_status'], array(SS_SHIPPED, SS_RECEIVED))) {
            $order['shipping_time'] = $order['shipping_time'];
        } else {
            $order['shipping_time'] = '';
        }
       
        return $order;
    }

    /**
     *  获取用护可以和并的订单数组
     *
     * @access  public
     * @param   int         $user_id        用护ID
     *
     * @return  array       $merge          可合并订单数组
     */
    function get_user_merge($user_id) {
        $sql = "SELECT order_sn FROM " . $this->pre .
                "order_info WHERE user_id  = '$user_id' " . order_query_sql('unprocessed') .
                "AND extension_code = '' " .
                " ORDER BY add_time DESC";
        $list = $this->query($sql);
        $merge = array();
        foreach ($list as $key => $value) {

            $merge[$value['order_sn']] = $value['order_sn'];
        }
        return $merge;
    }

    /**
     *  合并指定用护订单
     *
     * @access  public
     * @param   string      $from_order         合并的从订单号
     * @param   string      $to_order           合并的主订单号
     *
     * @return  boolen      $bool
     */
    function merge_user_order($from_order, $to_order, $user_id = 0) {
        if ($user_id > 0) {
            /* 检查订单是否属于指定用护 */
            if (strlen($to_order) > 0) {
                $sql = "SELECT user_id FROM " . $this->pre .
                        "order_info WHERE order_sn = '$to_order'";
                $res = $this->row($sql);
                $order_user = $res['user_id'];
                if ($order_user != $user_id) {
                    ECTouch::err()->add(L('no_priv'));
                }
            } else {
                ECTouch::err()->add(L('order_sn_empty'));
                return false;
            }
        }

        $result = model('Order')->merge_order($from_order, $to_order);
        if ($result === true) {
            return true;
        } else {
            ECTouch::err()->add($result);
            return false;
        }
    }

    /**
     *  将指定订单中的商品添加到购物车
     *
     * @access  public
     * @param   int         $order_id
     *
     * @return  mix         $message        成功返回true, 错误返回出错信息
     */
    function return_to_cart($order_id) {
        /* 初始化基本件数量 goods_id => goods_number */
        $basic_number = array();

        /* 查订单商品：不考虑赠品 */
        $sql = "SELECT goods_id, product_id,goods_number, goods_attr, parent_id, goods_attr_id" .
                " FROM " . $this->pre .
                "order_goods WHERE order_id = '$order_id' AND is_gift = 0 AND extension_code <> 'package_buy'" .
                " ORDER BY parent_id ASC";
        $res = $this->query($sql);

        $time = time();
        foreach ($res as $row) {
            // 查该商品信息：是否删除、是否上架

            $sql = "SELECT goods_sn, goods_name, goods_number, market_price, " .
                    "IF(is_promote = 1 AND '$time' BETWEEN promote_start_date AND promote_end_date, promote_price, shop_price) AS goods_price," .
                    "is_real, extension_code, is_alone_sale, goods_type " .
                    "FROM " . $this->pre .
                    "goods WHERE goods_id = '$row[goods_id]' " .
                    " AND is_delete = 0 LIMIT 1";
            $goods = $this->row($sql);

            // 如果该商品不存在，处理下一个商品
            if (empty($goods)) {
                continue;
            }
            if ($row['product_id']) {
                $order_goods_product_id = $row['product_id'];
                $sql = "SELECT product_number from " . $this->pre . "products where product_id='$order_goods_product_id'";
                $res = $this->row($sql);
                $product_number = $res['product_number'];
            }
            // 如果使用库存，且库存不足，修改数量
            if (C('use_storage') == 1 && ($row['product_id'] ? ($product_number < $row['goods_number']) : ($goods['goods_number'] < $row['goods_number']))) {
                if ($goods['goods_number'] == 0 || $product_number === 0) {
                    // 如果库存为0，处理下一个商品
                    continue;
                } else {
                    if ($row['product_id']) {
                        $row['goods_number'] = $product_number;
                    } else {
                        // 库存不为0，修改数量
                        $row['goods_number'] = $goods['goods_number'];
                    }
                }
            }

            //检查商品价格是否有会员价格
            $sql = "SELECT goods_number FROM" . $this->pre . " " .
                    "cart WHERE session_id = '" . SESS_ID . "' " .
                    "AND goods_id = '" . $row['goods_id'] . "' " .
                    "AND rec_type = '" . CART_GENERAL_GOODS . "' LIMIT 1";
            $res = $this->row($sql);
            $temp_number = $res['goods_number'];
            $row['goods_number'] += $temp_number;

            $attr_array = empty($row['goods_attr_id']) ? array() : explode(',', $row['goods_attr_id']);
            $goods['goods_price'] = model('GoodsBase')->get_final_price($row['goods_id'], $row['goods_number'], true, $attr_array);

            // 要返回购物车的商品
            $return_goods = array(
                'goods_id' => $row['goods_id'],
                'goods_sn' => addslashes($goods['goods_sn']),
                'goods_name' => addslashes($goods['goods_name']),
                'market_price' => $goods['market_price'],
                'goods_price' => $goods['goods_price'],
                'goods_number' => $row['goods_number'],
                'goods_attr' => empty($row['goods_attr']) ? '' : addslashes($row['goods_attr']),
                'goods_attr_id' => empty($row['goods_attr_id']) ? '' : addslashes($row['goods_attr_id']),
                'is_real' => $goods['is_real'],
                'extension_code' => addslashes($goods['extension_code']),
                'parent_id' => '0',
                'is_gift' => '0',
                'rec_type' => CART_GENERAL_GOODS
            );

            // 如果是配件
            if ($row['parent_id'] > 0) {
                // 查询基本件信息：是否删除、是否上架、能否作为普通商品销售
                $sql = "SELECT goods_id " .
                        "FROM " . $this->pre .
                        "goods WHERE goods_id = '$row[parent_id]' " .
                        " AND is_delete = 0 AND is_on_sale = 1 AND is_alone_sale = 1 LIMIT 1";
                $parent = $this->row($sql);
                if ($parent) {
                    // 如果基本件存在，查询组合关系是否存在
                    $sql = "SELECT goods_price " .
                            "FROM " . $this->pre .
                            "group_goods WHERE parent_id = '$row[parent_id]' " .
                            " AND goods_id = '$row[goods_id]' LIMIT 1";
                    $fitting_price = $this->row($sql);
                    if ($fitting_price['goods_price']) {
                        // 如果组合关系存在，取配件价格，取基本件数量，改parent_id
                        $return_goods['parent_id'] = $row['parent_id'];
                        $return_goods['goods_price'] = $fitting_price['goods_price'];
                        $return_goods['goods_number'] = $basic_number[$row['parent_id']];
                    }
                }
            } else {
                // 保存基本件数量
                $basic_number[$row['goods_id']] = $row['goods_number'];
            }

            // 返回购物车：看有没有相同商品
            $sql = "SELECT goods_id " .
                    "FROM " . $this->pre .
                    "cart WHERE session_id = '" . SESS_ID . "' " .
                    " AND goods_id = '$return_goods[goods_id]' " .
                    " AND goods_attr = '$return_goods[goods_attr]' " .
                    " AND parent_id = '$return_goods[parent_id]' " .
                    " AND is_gift = 0 " .
                    " AND rec_type = '" . CART_GENERAL_GOODS . "'";
            $res = $this->row($sql);
            $cart_goods = $res['goods_id'];
            if (empty($cart_goods)) {
                // 没有相同商品，插入
                $return_goods['session_id'] = SESS_ID;
                $return_goods['user_id'] = $_SESSION['user_id'];
                $this->table = 'cart';
                $this->insert($return_goods);
            } else {
                // 有相同商品，修改数量
                $sql = "UPDATE " . $this->pre . "cart SET " .
                        "goods_number = '" . $return_goods['goods_number'] . "' " .
                        ",goods_price = '" . $return_goods['goods_price'] . "' " .
                        "WHERE session_id = '" . SESS_ID . "' " .
                        "AND goods_id = '" . $return_goods['goods_id'] . "' " .
                        "AND rec_type = '" . CART_GENERAL_GOODS . "' LIMIT 1";
                $this->query($sql);
            }
        }


        // 清空购物车的赠品
        $sql = "DELETE FROM " . $this->pre .
                "cart WHERE session_id = '" . SESS_ID . "' AND is_gift = 1";
        $this->query($sql);

        return true;
    }

    /**
     *  保存用护收货地址
     *
     * @access  public
     * @param   array   $address        array_keys(consignee string, email string, address string, zipcode string, tel string, mobile stirng, sign_building string, best_time string, order_id int)
     * @param   int     $user_id        用护ID
     *
     * @return  boolen  $bool
     */
    function save_order_address($address, $user_id) {
        ECTouch::err()->clean();
        /* 数据验证 */
        empty($address['consignee']) and ECTouch::err()->add(L('consigness_empty'));
        empty($address['address']) and ECTouch::err()->add(L('address_empty'));
        $address['order_id'] == 0 and ECTouch::err()->add(L('order_id_empty'));
        if (empty($address['email'])) {
            ECTouch::err()->add($GLOBALS['email_empty']);
        } else {
            if (!is_email($address['email'])) {
                ECTouch::err()->add(sprintf(L('email_invalid'), $address['email']));
            }
        }
        if (ECTouch::err()->error_no > 0) {
            return false;
        }

        /* 检查订单状态 */
        $sql = "SELECT user_id, order_status FROM " . $this->pre . "order_info WHERE order_id = '" . $address['order_id'] . "'";
        $row = $this->row($sql);
        if ($row) {
            if ($user_id > 0 && $user_id != $row['user_id']) {
                ECTouch::err()->add(L('no_priv'));
                return false;
            }
            if ($row['order_status'] != OS_UNCONFIRMED) {
                ECTouch::err()->add(L('require_unconfirmed'));
                return false;
            }
            $this->table = 'order_info';
            $condition['order_id'] = $address['order_id'];
            $this->update($condition, $address);
            return true;
        } else {
            /* 订单不存在 */
            ECTouch::err()->add(L('order_exist'));
            return false;
        }
    }

    /**
     *
     * @access  public
     * @param   int         $user_id         用护ID
     * @param   int         $num             列表显示条数
     * @param   int         $start           显示起始位置
     *
     * @return  array       $arr             红保列表
     */
    function get_user_bouns_list($user_id, $num = 10, $start = 0) {
        $sql = "SELECT u.bonus_sn, u.order_id, b.type_name, b.type_money, b.min_goods_amount, b.use_start_date, b.use_end_date " .
                " FROM " . $this->pre . "user_bonus AS u ," .
                $this->pre . "bonus_type AS b" .
                " WHERE u.bonus_type_id = b.type_id AND u.user_id = '" . $user_id . "' ORDER BY bonus_id DESC LIMIT " . $start . ',' . $num;
        $res = $this->query($sql);
        $arr = array();

        $day = getdate();
        $cur_date = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
        foreach ($res as $row) {
            /* 先判断是否被使用，然后判断是否开始或过期 */
            if (empty($row['order_id'])) {
                /* 没有被使用 */
                if ($row['use_start_date'] > $cur_date) {
                    $row['status'] = L('not_start');
                } else if ($row['use_end_date'] < $cur_date) {
                    $row['status'] = L('overdue');
                } else {
                    $row['status'] = L('not_use');
                }
            } else {
                $url = url('user/order_detail', array('order_id'=>$row['order_id']));
                $row['status'] = '<a href="'.$url.'" >' . L('had_use') . '</a>';
            }

            $row['use_startdate'] = local_date(C('date_format'), $row['use_start_date']);
            $row['use_enddate'] = local_date(C('date_format'), $row['use_end_date']);

            $arr[] = $row;
        }
        return $arr;
    }

    /**
     * 通过判断is_feed 向UCenter提交Feed
     *
     * @access public
     * @param  integer $value_id  $order_id or $comment_id
     * @param  interger $feed_type BUY_GOODS or COMMENT_GOODS
     *
     * @return void
     */
    function add_feed($id, $feed_type) {
        $feed = array();
        if ($feed_type == BUY_GOODS) {
            if (empty($id)) {
                return;
            }
            $id = intval($id);
            $sql = "SELECT g.goods_id, g.goods_name, g.goods_sn, g.goods_desc, g.goods_thumb, o.goods_price FROM " . $this->pre . "order_goods AS o, " . $this->pre . "goods AS g WHERE o.order_id='{$id}' AND o.goods_id=g.goods_id";
            $order_res = $this->query($sql);
            foreach ($order_res as $goods_data) {
                if (!empty($goods_data['goods_thumb'])) {
                    $url = __URL__ . $goods_data['goods_thumb']; //ECTouch::ecs()->url() . $goods_data['goods_thumb'];
                } else {
                    $url = __URL__ . C('no_picture'); //ECTouch::ecs()->url() . C('no_picture');
                }
                $link = __HOST__ . url('goods/index', array('id' => $goods_data["goods_id"])); //ECTouch::ecs()->url() . "goods.php?id=" . $goods_data["goods_id"];

                $feed['icon'] = "goods";
                $feed['title_template'] = '<b>{username} ' . L('feed_user_buy') . ' {goods_name}</b>';
                $feed['title_data'] = array('username' => $_SESSION['user_name'], 'goods_name' => $goods_data['goods_name']);
                $feed['body_template'] = '{goods_name}  ' . L('feed_goods_price') . ':{goods_price}  ' . L('feed_goods_desc') . ':{goods_desc}';
                $feed['body_data'] = array('goods_name' => $goods_data['goods_name'], 'goods_price' => $goods_data['goods_price'], 'goods_desc' => sub_str(strip_tags($goods_data['goods_desc']), 150, true));
                $feed['images'][] = array('url' => $url,
                    'link' => $link);
                uc_call("uc_feed_add", array($feed['icon'], $_SESSION['user_id'], $_SESSION['user_name'], $feed['title_template'], $feed['title_data'], $feed ['body_template'], $feed['body_data'], '', '', $feed['images']));
            }
        }
        return;
    }

    /**
     * 指定默认配送地址
     *
     */
    function save_consignee_default($address_id) {
        /* 保存为用护的默认收货地址 */
        $sql = "UPDATE " . $this->pre .
                "users SET address_id = '$address_id' WHERE user_id = '$_SESSION[user_id]'";

        $res = $this->query($sql);

        return $res !== false;
    }
    function userIconSave($url,$username){
        if (!file_exists('../themes/yutui/images/user_avatar'))
           {
                @mkdir('../themes/yutui/images/user_avatar', 0777);
                @chmod('../themes/yutui/images/user_avatar', 0777);
            }
        $ch = curl_init();
      

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);
        $resource = fopen($_SERVER['DOCUMENT_ROOT']."/themes/yutui/images/user_avatar/" . $username.".jpg" ,'wb');

        fwrite($resource, $file);
        fclose($resource);
        return "/themes/yutui/images/user_avatar/" . $username.".jpg";
    }
    /*更新用户的invite_code*/
    function update_invite_code($user_id,$invite_code){
        
         $from_data = array(
                    'invite_code' => $invite_code,
                   
                    );

                $this->model->table('users')->data($from_data)->where(array('user_id' => $user_id))->update();

    }
    function update_parent_id($user_id,$parent_id){

     /*更新用户的默认语言*/
     $from_data = array(
                    'parent_id' => $parent_id,
                   
                    );
      $from_user_info = $this->model->table('users')->field('parent_id')->where(array('user_id'=> $user_id))->find();
      
         //if(!$from_user_info['parent_id']){

                $this->model->table('users')->data($from_data)->where(array('user_id' => $user_id))->update();
         //}
            

    }
    function updateLang($user_id,$lang){
        
         $from_data = array(
                    'lang' => $lang,
                   
                    );
                $this->model->table('users')->data($from_data)->where(array('user_id' => $user_id))->update();

    }


    function update_nickname_headimg($nick_name,$headimgurl,$username) {
   
         $imgurl = Myqiniu::qiniuuploadimage($headimgurl,$username);

         $headimgurl = $imgurl['url'];
        //如果该用户的头像和昵称都为空的话那么更新
        /*上传图片*/
       
         $nick_name = addEmoji($nick_name);
             $userinfo = $this->getusersinfo($_SESSION[user_id]);
            if(empty($userinfo['nick_name'])&&empty($userinfo['user_avatar'])){
                     $sql = "UPDATE " . $this->pre .
                    "users SET nick_name = '$nick_name',user_avatar = '$headimgurl'  WHERE user_id = '$_SESSION[user_id]'";
                    $res = $this->query($sql);
                    return $res !== false;
            }
        
       
       

        /* 保存为用护的默认收货地址 */
        
               

        

        
    }
    /*如果用户头像是微信头像更新头像到七牛*/
    function update_headimg($headimgurl,$username,$user_id)
    {
        //微信远程地址$headimgurl
         $imgurl = Myqiniu::qiniuuploadimage($headimgurl,$username);
         $headimgurl = $imgurl['url'];
          $sql = "UPDATE " . $this->pre .
                    "users SET user_avatar = '$headimgurl'  WHERE user_id = '$user_id'";
                  
                    $res = $this->query($sql);
                    return $res !== false;
    }

    /**
     * 根据商品id获取购物车中此id的数量
     */
    function get_goods_number($goods_id) {
        // 查询
        $sql = "SELECT IFNULL(SUM(goods_number), 0) as number " .
                " FROM " . $this->pre .
                "cart WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . CART_GENERAL_GOODS . "' AND goods_id = " . $goods_id;
        $res = $this->row($sql);
        return $res['number'];
    }

    /**
     *  获取用护信息数组
     *
     * @access  public
     * @param
     *
     * @return array        $user       用护信息数组
     */
    function get_user_info($id = 0) {
        if ($id == 0) {
            $id = $_SESSION['user_id'];
        }
        $time = date('Y-m-d');
        $sql = 'SELECT u.user_id, u.email, u.user_name, u.lang' .
                ' FROM ' . $this->pre . 'users AS u ' .
                " WHERE u.user_id = '$id'";

        $user = $this->row($sql);
        
        $bonus = model('ClipsBase')->get_user_bonus($id);

        $user['username'] = $user['user_name'];
        $user['lang'] = $user['lang'];
        // $user['user_points'] = $user['pay_points'] . C('integral_name');
        // $user['user_money'] = price_format($user['user_money'], false);
        $user['user_bonus'] = price_format($bonus['bonus_value'], false);

        return $user;
    }

    function get_user_info_new($vip_manage_account) {
        
        $time = date('Y-m-d');
        $sql = 'SELECT u.user_id, u.status,u.password,u.user_name,u.nick_name, u.mobile_phone, u.other_invite_code' .
                ' FROM ' . $this->pre . 'users AS u ' .
                " WHERE u.status<>9  and u.vip_manage_account = '$vip_manage_account'";

        $user = $this->row($sql);
        

        return $user;
    }

    /**
     * 获得订单中的费用信息
     *
     * @access  public
     * @param   array   $order
     * @param   array   $goods
     * @param   array   $consignee
     * @param   bool    $is_gb_deposit  是否团购保证金（如果是，应付款金额只计算商品总额和支付费用，可以获得的积分取 $gift_integral）
     * @return  array
     */
    function order_fee($order, $goods, $consignee,$freight = '') {

        /* 初始化订单的扩展code */
        if (!isset($order['extension_code'])) {
            $order['extension_code'] = '';
        }

        if ($order['extension_code'] == 'group_buy') {
            $group_buy = model('GroupBuyBase')->group_buy_info($order['extension_id']);
        }

        $total = array('real_goods_count' => 0,
            'gift_amount' => 0,
            'goods_price' => 0,
            'market_price' => 0,
            'discount' => 0,
            'pack_fee' => 0,
            'card_fee' => 0,
            'shipping_fee' => 0,
            'shipping_insure' => 0,
            'integral_money' => 0,
            'bonus' => 0,
            'surplus' => 0,
            'cod_fee' => 0,
            'pay_fee' => 0,
            'tax' => 0);
        $weight = 0;

        /* 商品总价 */
        foreach ($goods AS $val) {
            /* 统计实体商品的个数 */
            if ($val['is_real']) {
                $total['real_goods_count']++;
            }

            $total['goods_price'] += $val['goods_price'] * $val['goods_number'];
            $total['market_price'] += $val['market_price'] * $val['goods_number'];
        }

        $total['saving'] = $total['market_price'] - $total['goods_price'];
        $total['save_rate'] = $total['market_price'] ? round($total['saving'] * 100 / $total['market_price']) . '%' : 0;

        $total['goods_price_formated'] = price_format($total['goods_price'], false);
        $total['market_price_formated'] = price_format($total['market_price'], false);
        $total['saving_formated'] = price_format($total['saving'], false);

        /* 折扣 */
        if ($order['extension_code'] != 'group_buy') {
            $discount = model('Order')->compute_discount();
            $total['discount'] = $discount['discount'];
            if ($total['discount'] > $total['goods_price']) {
                $total['discount'] = $total['goods_price'];
            }
        }
        $total['discount_formated'] = price_format($total['discount'] , false);

        /* 税额 */
        if (!empty($order['need_inv']) && $order['inv_type'] != '') {
            /* 查税率 */
            $rate = 0;
            $invoice_type = C('invoice_type');
            foreach ($invoice_type['type'] as $key => $type) {
                if ($type == $order['inv_type']) {
                    $rate = floatval($invoice_type['rate'][$key]) / 100;
                    break;
                }
            }
            if ($rate > 0) {
                $total['tax'] = $rate * $total['goods_price'];
            }
        }
        $total['tax_formated'] = price_format($total['tax'], false);

        /* 包装费用 */
        if (!empty($order['pack_id'])) {
            $total['pack_fee'] = pack_fee($order['pack_id'], $total['goods_price']);
        }
        $total['pack_fee_formated'] = price_format($total['pack_fee'], false);

        /* 贺卡费用 */
        if (!empty($order['card_id'])) {
            $total['card_fee'] = card_fee($order['card_id'], $total['goods_price']);
        }
        $total['card_fee_formated'] = price_format($total['card_fee'], false);

        /* 红包 */
        if (!empty($order['bonus_id'])) {
            $bonus = model('Order')->bonus_info($order['bonus_id']);
            $total['bonus'] = $bonus['type_money'];
        }
        $total['bonus_formated'] = price_format($total['bonus'], false);

        /* 线下红包 */
        if (!empty($order['bonus_kill'])) {
            $bonus = model('Order')->bonus_info(0, $order['bonus_kill']);
            $total['bonus_kill'] = $order['bonus_kill'];
            $total['bonus_kill_formated'] = price_format($total['bonus_kill'], false);
        }

        /* 配送费用 */
        $shipping_cod_fee = NULL;

        if ($order['shipping_id'] > 0 && $total['real_goods_count'] > 0) 
        {
            $region['country'] = $consignee['country'];
            $region['province'] = $consignee['province'];
            $region['city'] = $consignee['city'];
            $region['district'] = $consignee['district'];
            if ($order['extension_code'] == 'group_buy') {
                    $weight_price = model('Order')->cart_weight_price(CART_GROUP_BUY_GOODS);
                } else {
                    $weight_price = model('Order')->cart_weight_price();
                }
            $shipping_info = model('Shipping')->shipping_area_info($order['shipping_id'], $region);
            $sql = 'SELECT count(*) as count FROM ' . $this->pre . "cart WHERE  `session_id` = '" . SESS_ID . "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
                $res = $this->row($sql);
                $shipping_count = $res['count'];
            if($shipping_count == 0 AND $weight_price['free_shipping'] == 1){
                $total['shipping_fee'] = 0;
            }else
            {
                $shipping_list = model('Shipping')->available_shipping_list($region);
                 foreach ($shipping_list as $key => $val) {

                    $shipping_cfg = unserialize_config($val ['configure']);
                    $shipping_fee = ($shipping_count == 0 and $weight_price ['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val ['configure']), $weight_price ['weight'], $weight_price['old_preic'] = $group_buy['cur_price'] > 0 ? $group_buy['cur_price'] : $cart_weight_price['old_preic'], $cart_weight_price ['number']);
    
                    $shipping_list [$key] ['format_shipping_fee'] = $shipping_fee;
                    $shipping_list [$key] ['shipping_fee'] = $shipping_fee;
                    $shipping_list [$key] ['free_money'] = price_format($shipping_cfg ['free_money'], false);
                    $shipping_list [$key] ['insure_formated'] = strpos($val ['insure'], '%') === false ? price_format($val ['insure'], false) : $val ['insure'];
    
                    /* 当前的配送方式是否支持保价 */
                    if ($val ['shipping_id'] == $order ['shipping_id']) {
                        $insure_disabled = ($val ['insure'] == 0);
                        $cod_disabled = ($val ['support_cod'] == 0);
                    }
                    // 兼容过滤ecjia配送方式
                    if (substr($val['shipping_code'], 0 , 5) == 'ship_') {
                        unset($shipping_list[$key]);
                    }
    
                }

                $total['shipping_fee'] = $shipping_list['0']['shipping_fee'];
            
            }
       
            // if (!empty($shipping_info)||!$shipping_info) {
               
            //     if ($order['extension_code'] == 'group_buy') {
            //         $weight_price = model('Order')->cart_weight_price(CART_GROUP_BUY_GOODS);
            //     } else {
            //         $weight_price = model('Order')->cart_weight_price();
            //     }

            //     // 查看购物车中是否全为免运费商品，若是则把运费赋为零
            //     $sql = 'SELECT count(*) as count FROM ' . $this->pre . "cart WHERE  `session_id` = '" . SESS_ID . "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
            //     $res = $this->row($sql);
            //     $shipping_count = $res['count'];

            //     $total['shipping_fee'] = ($shipping_count == 0 AND $weight_price['free_shipping'] == 1) ? 0 : shipping_fee($shipping_info['shipping_code'], $shipping_info['configure'], $weight_price['weight'], $total['goods_price'], $weight_price['number']);

            //     if (!empty($order['need_insure']) && $shipping_info['insure'] > 0) {
            //         $total['shipping_insure'] = shipping_insure_fee($shipping_info['shipping_code'], $total['goods_price'], $shipping_info['insure']);
            //     } else {
            //         $total['shipping_insure'] = 0;
            //     }

            //     if ($shipping_info['support_cod']) {
            //         $shipping_cod_fee = $shipping_info['pay_fee'];
            //     }
            //     $total['shipping_fee'] = $total['shipping_fee'];
            // }else{
            //     $total['shipping_fee'] = (intval($shipping_list['0']['shipping_fee']));
            // }
        }
        $total['shipping_fee'] = $total['shipping_fee'];

         
        //  var_dump($total['shipping_fee']);exit;
        //$total['shipping_fee'] = (intval($shipping_list['0']['shipping_fee']));
        //$total['shipping_insure_formated'] = $total['shipping_insure'];

        // 购物车中的商品能享受红包支付的总额
        $bonus_amount = model('Order')->compute_discount_amount();
        // 红包和积分最多能支付的金额为商品总额
        $max_amount = $total['goods_price'] == 0 ? $total['goods_price'] : $total['goods_price'] - $bonus_amount;

        /* 计算订单总额 */
        if ($order['extension_code'] == 'group_buy' && $group_buy['deposit'] > 0) {
            $total['amount'] = $total['goods_price'] + $freight;
        } else {
            $total['amount'] = $total['goods_price'] - $total['discount'] + $total['tax'] + $total['pack_fee'] + $total['card_fee'] +
                    $total['shipping_fee'] + $total['shipping_insure'] + $total['cod_fee'] + $freight;

            // 减去红包金额
            $use_bonus = min($total['bonus'], $max_amount); // 实际减去的红包金额
            if (isset($total['bonus_kill'])) {
                $use_bonus_kill = min($total['bonus_kill'], $max_amount);
                $total['amount'] -= $price = number_format($total['bonus_kill'], 2, '.', ''); // 还需要支付的订单金额
            }

            $total['bonus'] = $use_bonus;
            $total['bonus_formated'] = price_format($total['bonus'], false);

            $total['amount'] -= $use_bonus; // 还需要支付的订单金额
            $max_amount -= $use_bonus; // 积分最多还能支付的金额
        }

        /* 余额 */
        $order['surplus'] = $order['surplus'] > 0 ? $order['surplus'] : 0;
        if ($total['amount'] > 0) {
            if (isset($order['surplus']) && $order['surplus'] > $total['amount']) {
                $order['surplus'] = $total['amount'];
                $total['amount'] = 0;
            } else {
                $total['amount'] -= floatval($order['surplus']);
            }
        } else {
            $order['surplus'] = 0;
            $total['amount'] = 0;
        }
        $total['surplus'] = $order['surplus'];
        $total['surplus_formated'] = price_format($order['surplus'], false);
        /* 积分 */
        $order['integral'] = $order['integral'] > 0 ? $order['integral'] : 0;

        if ($total['amount'] > 0 && $max_amount > 0 && $order['integral'] > 0) {
            $integral_money = value_of_integral($order['integral']);

            // 使用积分支付
            $use_integral = min($total['amount'], $max_amount, $integral_money); // 实际使用积分支付的金额
            $total['amount'] -= $use_integral;
            $total['integral_money'] = $use_integral;
            $order['integral'] = integral_of_value($use_integral);
        } else {
            $total['integral_money'] = 0;
            $order['integral'] = 0;
        }
        $total['integral'] = $order['integral'];
        $total['integral_formated'] = price_format($total['integral_money'], false);

        /* 保存订单信息 */
        $_SESSION['flow_order'] = $order;

        $se_flow_type = isset($_SESSION['flow_type']) ? $_SESSION['flow_type'] : '';

        /* 支付费用 */
        if (!empty($order['pay_id']) && ($total['real_goods_count'] > 0 || $se_flow_type != CART_EXCHANGE_GOODS)) {
            $total['pay_fee'] = pay_fee($order['pay_id'], $total['amount'], $shipping_cod_fee);
        }

        $total['pay_fee_formated'] = price_format($total['pay_fee'], false);

        $total['amount'] += $total['pay_fee']; // 订单总额累加上支付费用
        $total['amount_formated'] = price_format($total['amount'], false);

        /* 取得可以得到的积分和红包 */
        if ($order['extension_code'] == 'group_buy') {
            $total['will_get_integral'] = $group_buy['gift_integral'];
        } elseif ($order['extension_code'] == 'exchange_goods') {
            $total['will_get_integral'] = 0;
        } else {
            $total['will_get_integral'] = model('Order')->get_give_integral($goods);
        }
        $total['will_get_bonus'] = $order['extension_code'] == 'exchange_goods' ? 0 : price_format(model('Order')->get_total_bonus(), false);
        $total['formated_goods_price'] = price_format($total['goods_price'], false);
        $total['formated_market_price'] = price_format($total['market_price'], false);
        $total['formated_saving'] = price_format($total['saving'], false);

        if ($order['extension_code'] == 'exchange_goods') {
            $sql = 'SELECT SUM(eg.exchange_integral) ' .
                    'as sum FROM ' . $this->pre . 'cart AS c,' . $this->pre . 'exchange_goods AS eg ' .
                    "WHERE c.goods_id = eg.goods_id AND c.session_id= '" . SESS_ID . "' " .
                    "  AND c.rec_type = '" . CART_EXCHANGE_GOODS . "' " .
                    '  AND c.is_gift = 0 AND c.goods_id > 0 ' .
                    'GROUP BY eg.goods_id';
            $res = $this->row($sql);
            $exchange_integral = $res['sum'];
            $total['exchange_integral'] = $exchange_integral;
        }

        return $total;
    }

    /**
     * 修改订单
     * @param   int     $order_id   订单id
     * @param   array   $order      key => value
     * @return  bool
     */
    function update_order($order_id, $order) {
        $this->table = 'order_info';
        $condition['order_id'] = $order_id;

        $res = $this->query('DESC ' . $this->pre . $this->table);

        while ($row = mysqli_fetch_row($res)) {
            $field_names[] = $row[0];
        }
     
        foreach ($field_names as $value) {
            if (array_key_exists($value, $order) == true) {
                $order_info[$value] = $order[$value];
            }
        }
        return $this->update($condition, $order_info);
    }

    /**
     * 重新计算购物车中的商品价格：目的是当用护登录时享受会员价格，当用护退出登录时不享受会员价格
     * 如果商品有促销，价格不变
     *
     * @access  public
     * @return  void
     */
    function recalculate_price() {
        /* 取得有可能改变价格的商品：除配件和赠品之外的商品 */
        $sql = 'SELECT c.rec_id, c.goods_id, c.goods_attr_id, g.promote_price, g.promote_start_date, c.goods_number,' .
                "g.promote_end_date, IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS member_price " .
                'FROM ' . $this->pre . 'cart AS c ' .
                'LEFT JOIN ' . $this->pre . 'goods AS g ON g.goods_id = c.goods_id ' .
                "LEFT JOIN " . $this->pre . "member_price AS mp " .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . $_SESSION['user_rank'] . "' " .
                "WHERE session_id = '" . SESS_ID . "' AND c.parent_id = 0 AND c.is_gift = 0 AND c.goods_id > 0 " .
                "AND c.rec_type = '" . CART_GENERAL_GOODS . "' AND c.extension_code <> 'package_buy'";

        $res = $this->query($sql);

        foreach ($res AS $row) {
            $attr_id = empty($row['goods_attr_id']) ? array() : explode(',', $row['goods_attr_id']);


            $goods_price = model('GoodsBase')->get_final_price($row['goods_id'], $row['goods_number'], true, $attr_id);


            $goods_sql = "UPDATE " . $this->pre . "cart SET goods_price = '$goods_price' " .
                    "WHERE goods_id = '" . $row['goods_id'] . "' AND session_id = '" . SESS_ID . "' AND rec_id = '" . $row['rec_id'] . "'";

            $this->query($goods_sql);
        }

        /* 删除赠品，重新选择 */
        $this->query('DELETE FROM ' . $this->pre .
                "cart WHERE session_id = '" . SESS_ID . "' AND is_gift > 0");
    }

    /**
     * 获取推荐uid
     *
     * @access  public
     * @param   void
     *
     * @return int
     * @author xuanyan
     * */
    // function get_affiliate() {
    //     if (!empty($_COOKIE['ecshop_affiliate_uid'])) {
    //         $uid = intval($_COOKIE['ecshop_affiliate_uid']);
    //         if ($this->row('SELECT user_id FROM ' . $this->pre . "users WHERE user_id = '$uid'")) {
    //             return $uid;
    //         } else {
    //             setcookie('ecshop_affiliate_uid', '', 1);
    //         }
    //     }
    //     elseif($_SESSION['user_id'] !== 0){
    //         //推荐 by ecmoban
    //         $reg_info = $this->model->table('users')->field('reg_time, parent_id')->where('user_id = '.$_SESSION['user_id'])->find();
    //         //推荐信息
    //         $config = unserialize(C('affiliate'));
    //         if (!empty($config['config']['expire'])) {
    //             if ($config['config']['expire_unit'] == 'hour') {
    //                 $c = 1;
    //             } elseif ($config['config']['expire_unit'] == 'day') {
    //                 $c = 24;
    //             } elseif ($config['config']['expire_unit'] == 'week') {
    //                 $c = 24 * 7;
    //             } else {
    //                 $c = 1;
    //             }
    //             //有效时间
    //             $eff_time = 3600 * $config['config']['expire'] * $c;
    //             //有效时间内
    //             if(time() - $reg_info['reg_time'] <= $eff_time){
    //                 return $reg_info['parent_id'];
    //             }
    //         }
    //     }

    //     return 0;
    // }
    /**
     * 检查是否为第三方用护
     * @param type $user_id
     * @return type
     */
    function is_third_user($user_id) {
        $sql = 'SELECT count(*) as count FROM ' . $this->pre . 'touch_user_info t LEFT JOIN ' . $this->pre .
                'users u ON t.user_id = u.user_id  WHERE u.user_id = "' . $user_id . '" ';
        $res = $this->row($sql);
        return $res['count'];
    }
    /**
     * 检查该用护是否启动过第三方登录
     * @param type $aite_id
     * @return type
     */
    function get_one_user($aite_id) {
        // pc兼容模式，安装pc端插件之后移除注释
        /**
        $sql = 'SELECT user_name FROM ' . $this->pre . 'users WHERE aite_id = "' . $aite_id . '" ';
        $res = $this->row($sql);
        if($res){
            return $res['user_name'];
        }
        */
        // 手机独立模式
        $sql = 'SELECT u.user_name, u.user_id FROM ' . $this->pre . 'users u LEFT JOIN ' . $this->pre .
                'touch_user_info t ON t.user_id = u.user_id WHERE t.aite_id = "' . $aite_id . '" ';
        return $this->row($sql);
    }

    /**
     * 插入第三方登录信息到数据库
     * @param type $info
     * @return boolean
     */
    function third_reg($info) {
        $username = $info['user_name'];
        $password = time();
        $email = $info['email'];
        if ($this->register($username, $password, $email) !== false) {
            $uid = $_SESSION['user_id'];
            // 更新附表
            $this->table = "touch_user_info";
            $touch_data['user_id'] = $uid;
            //$touch_data['user_id'] = $_SESSION['user_id'];
            $touch_data['aite_id'] = $info['aite_id'];
            $this->insert($touch_data);
            // 兼容pc端登录
            // $this->model->table('users')->data($touch_data)->where(array('user_id'=>$uid))->update();
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查询社会化登录用护信息
     * @param string $openid
     * @return array
     */
    function get_connect_user($openid) {
        $sql = "SELECT u.user_name, u.user_id, u.parent_id FROM {pre}users u, {pre}connect_user cu WHERE u.user_id = cu.user_id AND cu.open_id = '" . $openid. "' ";
        return $this->row($sql);
    }

    /**
     * 更新社会化登录用护信息
     * @param   $res, $type:qq,weibo,wechat
     * @return
     */
    function update_connnect_user($res, $type = '')
    {
        // 组合数据
        $profile = array(
            'nickname' => $res['nickname'],
            'sex' => $res['sex'],
            'province' => $res['province'],
            'city' => $res['city'],
            'country' => $res['country'],
            'headimgurl' => $res['headimgurl'],
        );
        $data = array(
            'connect_code' => 'sns_' . $type,
            'user_id' => $res['user_id'],
            'open_id' => $res['openid'],
            'profile' => serialize($profile)
        );
        if($res['user_id'] > 0 && $res['openid']){
            // 查询
            $connect_userinfo = $this->get_connect_user($res['openid']);
            if (empty($connect_userinfo)) {
                // 新增记录
                $data['create_at'] = time();
                $this->model->table('connect_user')->data($data)->insert();
            } else {
                // 更新记录
                $this->model->table('connect_user')->data($data)->where(array('open_id' => $res['openid']))->update();
            }
        }
    }

    /**
     * 更新微信用护信息
     * @param array    $info 微信用护信息
     * @param string   $wechat_id  公众号ID
     * @return
     */
    function update_wechat_user($info, $wechat_id = '')
    {
        //公众号id
        $wechat = $this->model->table('wechat')->field('id')->where(array('type' => 2, 'status' => 1, 'default_wx' => 1))->find();
        $wechat_id = !empty($wechat_id) ? $wechat_id : $wechat['id'];
        // 组合数据
        $data = array(
            'wechat_id' => $wechat_id,
            'openid' => $info['openid'],
            'nickname' => !empty($info['nickname']) ? $info['nickname'] : '',
            'sex' => !empty($info['sex']) ? $info['sex'] : 0,
            'language' => !empty($info['language']) ? $info['language'] : '',
            'city' => !empty($info['city']) ? $info['city'] : '',
            'province' => !empty($info['province']) ? $info['province'] : '',
            'country' => !empty($info['country']) ? $info['country'] : '',
            'headimgurl' => !empty($info['headimgurl']) ? $info['headimgurl'] : '',
            'openid' => $info['openid'],
        );
        if(!empty($info['user_id'])) { $data['ect_uid'] = $info['user_id'];}

        // unionid 微信开放平台唯一标识
        if(!empty($info['openid'])){
            // 查询
            $where = array('openid' => $info['openid'], 'wechat_id' => $wechat_id);
            $result = $this->model->table('wechat_user')->field('openid')->where($where)->find();
            if (empty($result)) {
                // 新增记录
                $this->model->table('wechat_user')->data($data)->insert();
            } else {
                // 更新记录
                $this->model->table('wechat_user')->data($data)->where($where)->update();
            }
        }
    }

    /**
     * 检查用护名是否重名 by leah
     * @param type $user_name
     * @return type
     */
    function check_user_name($user_name) {
        $this->table = 'users';
        $condition['user_name'] = $user_name;
        return $this->count($condition);
    }

     /**
     * 获取订单商品数量
     * @return type
     */
    function get_order_goods_count($order_id) {

        $sql = "SELECT  COUNT(*) as count " .
            "FROM " . $this->pre . "order_goods AS o " .
            "LEFT JOIN " . $this->pre . "products AS p ON o.product_id = p.product_id " .
            "LEFT JOIN " . $this->pre . "goods AS g ON o.goods_id = g.goods_id " .
            "WHERE o.order_id = '$order_id' ";
        $res = $this->row($sql);
        return $res['count'];
    }

    /**
     * 查询会员帐护明细
     * @access  public
     * @param   int     $user_id    会员ID
     * @param   int     $num        每页显示数量
     * @param   int     $start      开始显示的条数
     * @return  array
     */
    public function get_account_detail($user_id, $num, $start , $type='user_money') {

        // 获取余额记录
        $account_log = array();

        $sql = 'SELECT * FROM ' . $this->pre . "account_log WHERE user_id = " . $user_id . " AND {$type} <> 0 or bonus_money>0" .
        " ORDER BY log_id DESC limit " . $start . ',' . $num;
        $res = $this->query($sql);

        if (empty($res)) {
            return array();
            exit;
        }

        foreach ($res as $k => $v) {
            
            $res[$k]['change_time'] =date("Y-m-d H:i:s",$v['change_time']);
            $res[$k]['type'] = $v[$type] > 0 ? L('account_inc') : L('account_dec');
            // $res[$k]['user_money'] = price_format(abs($v['user_money']), false);
            $res[$k]['frozen_money'] = price_format(abs($v['frozen_money']), false);
            $res[$k]['rank_points'] = abs($v['rank_points']);
            // $res[$k]['user_points'] = $v['pay_points'];
            // $res[$k]['pay_points'] = abs($v['pay_points']);
            $res[$k]['short_change_desc'] = sub_str($v['change_desc'], 60);
            // $res[$k]['amount'] = $v['user_money'];
        }

        return $res;


    }
        /**
     * 查询会员帐护明细
     * @access  public
     * @param   int     $user_id    会员ID
     * @param   int     $num        每页显示数量
     * @param   int     $start      开始显示的条数
     * @return  array
     */
    public function get_new_account_detail($user_id, $num, $start , $type='1') {

        // 获取余额记录
        $account_log = array();

        $sql = 'SELECT * FROM ' . $this->pre . "account_log WHERE user_id = " . $user_id . " AND account_type=".$type." AND change_type<>99 ".
        " ORDER BY log_id DESC limit " . $start . ',' . $num;

        $res = $this->query($sql);

        if (empty($res)) {
            return array();
            exit;
        }
 
        foreach ($res as $k => $v) {
            
            $res[$k]['change_time'] =date("Y-m-d H:i:s",$v['change_time']);
           
            // $res[$k]['user_money'] = price_format(abs($v['user_money']), false);
            $res[$k]['account'] = price_format(abs($v['account']), false);
       
            // $res[$k]['user_points'] = $v['pay_points'];
            // $res[$k]['pay_points'] = abs($v['pay_points']);
            $res[$k]['short_change_desc'] = sub_str($v['change_desc'], 60);
            // $res[$k]['amount'] = $v['user_money'];
        }

        return $res;


    }
        /**
     * 查询会员帐护明细
     * @access  public
     * @param   int     $user_id    会员ID
     * @param   int     $num        每页显示数量
     * @param   int     $start      开始显示的条数
     * @return  array
     */
    public function get_userlist_detail($province_id, $city_id,$num, $start) {

        if($city_id){
        
           $sql = 'SELECT * FROM ' . $this->pre . "users WHERE user_rank>0 and user_id<>".$_SESSION['user_id']." and city_id = " . $city_id .
        " order by rand() limit 5 ";
       
            $rescity = $this->query($sql); 
           
           $res= $rescity;
        }

        if(empty($rescity)){
        
            $sqlprovince = 'SELECT * FROM ' . $this->pre . "users WHERE user_rank>0 and user_id<>".$_SESSION['user_id']." and province_id = " . $province_id .
        " order by rand() limit 5 ";
       
            $resprovince = $this->query($sqlprovince);
         
            $res= $resprovince;
            
        }
        if(empty($resprovince)&&empty($rescity)){
           
            $sqlcountry = 'SELECT * FROM ' . $this->pre . "users WHERE user_rank>0 and user_id<>".$_SESSION['user_id']." and country_id = 1  order by rand() limit 5 ";
         
            $rescountry = $this->query($sqlcountry);
           $res= $rescountry;
        }
      
            if (empty($res)) {
            return array();
                exit;
            }

        
            return $res;
        

        


    }
        /**
     * 查询会员帐护明细
     * @access  public
     * @param   int     $user_id    会员ID
     * @param   int     $num        每页显示数量
     * @param   int     $start      开始显示的条数
     * @return  array
     */
    public function get_userlist_num($province_id, $city_id) {

        if($city_id){
        
           $sql = 'SELECT count(*) as num  FROM ' . $this->pre . "users WHERE user_rank>0 and user_id<>".$_SESSION['user_id']." and city_id = " . $city_id ;
          
            $rescity = $this->row($sql); 
           
           $res= $rescity;
        }

        if(!$rescity['num']){
        
            $sqlprovince = 'SELECT count(*) as num  FROM ' . $this->pre . "users WHERE user_rank>0 and user_id<>".$_SESSION['user_id']." and province_id = " . $province_id ;
       
            $resprovince = $this->row($sqlprovince);
           
            $res= $resprovince;
            
        }
        if(!$resprovince['num']&&!$rescity['num']){
           
            $sqlcountry = 'SELECT count(*) as num  FROM ' . $this->pre . "users WHERE user_rank>0 and user_id<>".$_SESSION['user_id']." and country_id = 1 ";
            
            $rescountry = $this->row($sqlcountry);
            $res= $rescountry;
        }
      
            if (empty($res)) {
            return array();
                exit;
            }

        
            return $res['num'];
        

        


    }

    // 退换货 start
    /**
     * 获取订单所对应的服务类型数组
     * @param $order
     * @return array
     */
    public function get_service_opt($order) {

        $service_return = $this->model->table('service_type')->where("service_type = " . ST_RETURN_GOODS)->find();
        $service_exchange = $this->model->table('service_type')->where("service_type = " . ST_EXCHANGE)->find();

        $time = time();
        $type_list = array();
        if ($order['pay_status'] == PS_PAYED) {
            //订单已付款
             $type_list[] = ST_RETURN_GOODS;
             $type_list[] = ST_EXCHANGE;
            if ($order['order_status'] == OS_SPLITED) {

                //已分单
                if ($order['shipping_status'] == SS_SHIPPED) {
                    //已发货 退款
                    $action = $this->model->table('order_action')->field('log_time')->where(array('shipping_status' => SS_SHIPPED, 'order_id' => $order['order_id']))->find(); //获取发货时间
                    /* 退货退款 现在时间-发货时时间 得到天数 */
                    $days = (($time - $action['log_time']) / 3600 / 24);
                    if ($days <= $service_return['unreceived_days']) {
                        $type_list[] = ST_RETURN_GOODS;
                    } else {
                        show_message(L('time_out'));
                    }
                    if ($days <= $service_exchange['unreceived_days']) {
                        $type_list[] = ST_EXCHANGE;
                    } else {
                        show_message(L('time_out'));
                    }
                } elseif ($order['shipping_status'] == SS_RECEIVED) {
                    //已收货 退货换货，退款, 换货, 维修
                    $action = $this->model->table('order_action')->field('log_time')->where(array('shipping_status' => SS_RECEIVED, 'order_id' => $order['order_id']))->find(); //获取发货时间
                    /* 退货退款 现在时间-发货时时间 得到天数 */
                    $days = (($time - $action['log_time']) / 3600 / 24);
                    if ($days <= $service_return['unreceived_days']) {
                        $type_list[] = ST_RETURN_GOODS;
                    } else {
                        show_message(L('time_out'));
                    }
                    if ($days <= $service_exchange['unreceived_days']) {
                        $type_list[] = ST_EXCHANGE;
                    } else {
                        show_message(L('time_out'));
                    }
                } else {

                    //已下单付款,未发货->退款
                    //其他
                }
            }
        }
        return $type_list;
    }

    /** 获取所有服务类型列表
     * @param $order_id
     * @param $rec_id
     * @param $service_type
     * @return array
     */
    public function get_service_type_list($order_id, $rec_id, $service_type) {
        $where = " service_type in(" . implode(',', $service_type) . ")";
        $sql = 'SELECT service_id, service_name, service_desc, service_type FROM ' . $this->pre . 'service_type' . ' WHERE  is_show = 1 AND' . $where . ' ORDER BY sort_order, service_id';
        $result = $this->query($sql);
        $service_type = array();
        foreach ($result as $row) {
            $service_type[$row['service_id']]['service_name'] = $row['service_name'];
            $service_type[$row['service_id']]['service_id'] = $row['service_id'];
            $service_type[$row['service_id']]['service_desc'] = $row['service_desc'];
            $service_type[$row['service_id']]['received_days'] = $row['received_days'];
            $service_type[$row['service_id']]['unreceived_days'] = $row['unreceived_days'];
            $service_type[$row['service_id']]['url'] = url('user/returns_apply', array('id' => $row['service_id'], 'order_id' => $order_id, 'rec_id' => $rec_id));
        }
        return $service_type;
    }

    /**
     * 获取顶级退换货原因 by ECTouch Leah
     */
    public function get_parent_cause() {
        $sql = "SELECT * FROM " . $this->pre . "return_cause WHERE parent_id = 0  AND is_show = 1  ORDER BY sort_order";
        $result = $this->query($sql);
        if (is_array($result)) {
            $select = '';
            foreach ($result AS $var) {
                $select .= '<option value="' . $var['cause_id'] . '" ';
                $select .= ($selected == $var['cause_id']) ? "selected='ture'" : '';
                $select .= '>';
                if ($var['level'] > 0) {
                    $select .= str_repeat('&nbsp;', $var['level'] * 4);
                }
                $select .= htmlspecialchars(addslashes($var['cause_name']), ENT_QUOTES) . '</option>';
            }
            return $select;
        } else {
            return array();
        }
    }

    /**
     * 查询订单商品是否已申请过服务
     * @param type $rec_id
     * @return type
     */
    public function check_aftermarket($rec_id) {

        $service = $this->model->table('order_return')->field('COUNT(*) as count')->where('rec_id = ' . $rec_id)->find();
        return $service['count'];
    }

    /**
     * 当前可执行售后操作
     * @param $service_type
     * @return array
     */
    public function get_aftermarket_operate($service_type) {

        $operate = array();
        /**
         * 退货退款
         */
        if ($service_type == ST_RETURN_GOODS) {
            $operate['return_gods'] = true;
        } /* 仅退款* */ elseif ($service_type == ST_REFUND) {
            $operate['refund'] = true;
        } /* 退货退款* */ elseif ($service_type == ST_EXCHANGE) {

            $operate['exchange'] = true;
        } /* 维修* */ elseif ($service_type == ST_REPAIR) {
            $operate['repair'] = true;
        }
        return $operate;
    }

    /**退换货**/
    function tuihuanhuo($user_id) {
       $where['user_id'] = $user_id;
       $count = $this->model->table('order_return')->where($where)->count();
       return $count;
    }
    /**
     *  获取用护指定范围的订单列表
     *
     * @access  public
     * @param   int $user_id 用护ID号
     * @param   int $num 列表最大数量
     * @param   int $start 列表起始位置
     * @return  array       $order_list     订单列表
     */
    function get_user_aftermarket($user_id, $num = 10, $start = 0) {
        /* 取得订单列表 */
        $arr = array();

        $sql = "SELECT ret_id ,rec_id, goods_id, service_sn, order_sn, order_id,add_time, should_return, return_status, refund_status, is_check " .
                " FROM " . $this->pre .
                "order_return WHERE user_id = '$user_id'  ORDER BY add_time DESC LIMIT $start , $num";

        $res = M()->query($sql);
        foreach ($res as $key => $value) {
            if ($value['order_status'] == RF_APPLICATION) {
                $value['handler'] = "<a href=\"" . url('user/cancel_order', array('order_id' => $value['order_id'])) . "\" onclick=\"if (!confirm('" . L('confirm_cancel') . "')) return false;\">" . L('cancel') . "</a>";
            } else if ($value['is_check'] == RC_APPLY_SUCCESS) {
                /* 对配送状态的处理 */
                if ($value['shipping_status'] == SS_SHIPPED) {
                    @$value['handler'] = "<a href=\"" . url('user/affirm_received', array('order_id' => $value['order_id'])) . "\" onclick=\"if (!confirm('" . L('confirm_received') . "')) return false;\">" . L('received') . "</a>";
                } elseif ($value['shipping_status'] == SS_RECEIVED) {
                    @$value['handler'] = '<span style="color:red">' . L('ss_received') . '</span>';
                } else {
                    if ($value['pay_status'] == PS_UNPAYED) {
                        @$value['handler'] = "<a href=\"" . url('user/cancel_order', array('order_id' => $value['order_id'])) . "\">" . L('pay_money') . "</a>";
                    } else {
                        @$value['handler'] = "<a href=\"" . url('user/cancel_order', array('order_id' => $value['order_id'])) . "\">" . L('view_order') . "</a>";
                    }
                }
            } else {
                $value['handler'] = '<span>' . L('os.' . $value['order_status']) . '</span>';
            }

            $value['shipping_status'] = ($value['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $value['shipping_status'];
            $value['order_status'] = L('os.' . $value['order_status']) . ',' . L('ps.' . $value['pay_status']) . ',' . L('ss.' . $value['shipping_status']);

            $arr[] = array(
                'ret_id' => $value['ret_id'],
                'order_id' => $value['order_id'],
                'service_sn' => $value['service_sn'],
                'img' => get_image_path(0, model('Order')->get_order_thumb($value['order_id'])),
                'order_time' => local_date(C('time_format'), $value['add_time']),
                'return_status' => L('rf.' . $value['return_status']),
                'refund_status' => L('ff.' . $value['refund_status']),
                'total_fee' => price_format($value['total_fee'], false),
                'url' => url('user/order_detail', array('order_id' => $value['order_id'])),
                'goods_count' => model('Users')->get_order_goods_count($value['order_id']),
                'handler' => $value['handler']);
        }
        return $arr;
    }

    /**
     *  获取指服务订单的详情
     *
     * @access  public
     * @param   int $ret_id 订单ID
     * @param   int $user_id 用护ID
     *
     * @return   arr        $order          订单所有信息的数组
     */
    function get_aftermarket_detail($ret_id, $user_id = 0) {

        $ret_id = intval($ret_id);
        if ($ret_id <= 0) {
            ECTouch::err()->add(L('invalid_order_id'));

            return false;
        }
        $aftermarket = model('Order')->aftermarket_info($ret_id);

        //检查订单是否属于该用护
        if ($user_id > 0 && $user_id != $aftermarket['user_id']) {
            ECTouch::err()->add(L('no_priv'));

            return false;
        }
        return $aftermarket;
    }

    /**
     * 获取商家地址
     */
    function get_business_address($suppliers_id) {


        $address = '';
        if ($suppliers_id) {
            $address = '';
        } else {
            $sql = "SELECT region_name FROM " . $this->pre .
                "region WHERE region_id = '".C('shop_country')."'";
            $adress = $this->query($sql);
            //dump($adress);exit;
            $sql = "SELECT region_name FROM " . $this->pre .
                "region WHERE region_id = '".C('shop_province')."'";
            $adress = $this->query($sql);
            //dump($adress);exit;
            $sql = "SELECT region_name FROM " . $this->pre .
                "region WHERE region_id = '".C('shop_city')."'";
            $adress = $this->query($sql);

            $address.= C('shop_address') . '收件人：' . C('shop_name') . '联系电话：' . C('service_phone');
        }
        return $address;
    }

     /**
     * 获取省，市，地区id
     */
    function find_address($region_name,$region_type = 0) {

        $sql = "SELECT region_id FROM " . $this->pre .
            "region where region_name like '%$region_name%' and region_type = $region_type ";
        $address = $this->row($sql);
        return $address['region_id'];

    }
    /*获取地区名字*/
    function find_region_name($region_id){
        $sql = "SELECT region_name FROM " . $this->pre .
            "region where  region_id = $region_id ";
        $address = $this->row($sql);
        return $address['region_name'];
    }

    /**
     * 合并会员数据
     * @access  public
     * @param   id $from_user_id 原会员id
     * @param   id $to_user_id 新会员id
     * @return  boolen      $bool
     */
    function merge_user($from_user_id = 0, $to_user_id = 0)
    {
        if ($from_user_id > 0 && $to_user_id > 0 && $from_user_id != $to_user_id){
            // users表
            $from_user_info = $this->model->table('users')->field('*')->where(array('user_id'=> $from_user_id))->find();

            if(!empty($from_user_info)){
                // 更新字段值 email,sex,birthday,address_id,user_rank,is_special,parent_id,flag,alias,msn,qq,office_phone,home_phone,mobile_phone,is_validated
                // 组合数据
                $from_data = array(
                    'email' => $from_user_info['email'],
                    'sex' => $from_user_info['sex'],
                    'birthday' => $from_user_info['birthday'],
                    'address_id' => $from_user_info['address_id'],
                    'user_rank' => $from_user_info['user_rank'],
                    'is_special' => $from_user_info['is_special'],
                    'parent_id' => $from_user_info['parent_id'],
                    'flag' => $from_user_info['flag'],
                    'alias' => $from_user_info['alias'],
                    'msn' => $from_user_info['msn'],
                    'qq' => $from_user_info['qq'],
                    'office_phone' => $from_user_info['office_phone'],
                    'home_phone' => $from_user_info['home_phone'],
                    'mobile_phone' => $from_user_info['mobile_phone'],
                    'is_validated' => $from_user_info['is_validated'],
                    );
                $this->model->table('users')->data($from_data)->where(array('user_id' => $to_user_id))->update();

                // 累加字段值 user_money,frozen_money,pay_points,rank_points,credit_line
                // $sql = "UPDATE {pre}users SET user_money = user_money + '" . $from_user_info['user_money'] . "', frozen_money = frozen_money + '" . $from_user_info['frozen_money'] . "', pay_points = pay_points + '" . $from_user_info['pay_points'] . "', rank_points = rank_points + '" . $from_user_info['rank_points'] . "', credit_line = credit_line + '" . $from_user_info['credit_line'] . "'  WHERE user_id = '$to_user_id'";
                 $sql = "UPDATE {pre}users SET  frozen_money = frozen_money + '" . $from_user_info['frozen_money'] . "',  rank_points = rank_points + '" . $from_user_info['rank_points'] . "', credit_line = credit_line + '" . $from_user_info['credit_line'] . "'  WHERE user_id = '$to_user_id'";

                $this->model->query($sql);
            }

            // 用护订单
            $from_order_info = $this->model->table('order_info')->field('order_id')->where(array('user_id'=> $from_user_id))->select();
            if(!empty($from_order_info)){
                foreach ($from_order_info as $key => $value) {
                    $this->model->table('order_info')->data('user_id = ' . $to_user_id)->where('order_id = ' . $value['order_id'])->update();
                }
            }

            // 用护缺货登记
            $from_booking_goods = $this->model->table('booking_goods')->field('rec_id')->where(array('user_id'=> $from_user_id))->select();
            if(!empty($from_booking_goods)){
                foreach ($from_booking_goods as $key => $value) {
                    $this->model->table('booking_goods')->data('user_id = ' . $to_user_id)->where('rec_id = ' . $value['rec_id'])->update();
                }
            }
            // 会员收藏商品
            $from_collect_goods = $this->model->table('collect_goods')->field('rec_id')->where(array('user_id'=> $from_user_id))->select();
            if(!empty($from_collect_goods)){
                foreach ($from_collect_goods as $key => $value) {
                    $this->model->table('collect_goods')->data('user_id = ' . $to_user_id)->where('rec_id = ' . $value['rec_id'])->update();
                }
            }
            // 会员留言
            $from_feedback = $this->model->table('feedback')->field('msg_id')->where(array('user_id'=> $from_user_id))->select();
            if(!empty($from_feedback)){
                $to_user_info = $this->model->table('users')->field('user_name')->where(array('user_id'=> $to_user_id))->find();
                foreach ($from_feedback as $key => $value) {
                    $setdata = array('user_id' => $to_user_id, 'user_name' => $to_user_info['user_name']);
                    $this->model->table('feedback')->data($setdata)->where('msg_id = ' . $value['msg_id'])->update();
                }
            }
            // 会员地址
            $from_user_address = $this->model->table('user_address')->field('address_id')->where(array('user_id'=> $from_user_id))->select();
            if(!empty($from_user_address)){
                foreach ($from_user_address as $key => $value) {
                    $this->model->table('user_address')->data('user_id = ' . $to_user_id)->where('address_id = ' . $value['address_id'])->update();
                }
            }
            // 会员红包
            $from_user_bonus = $this->model->table('user_bonus')->field('bonus_id')->where(array('user_id'=> $from_user_id))->select();
            if(!empty($from_user_bonus)){
                foreach ($from_user_bonus as $key => $value) {
                    $this->model->table('user_bonus')->data('user_id = ' . $to_user_id)->where('bonus_id = ' . $value['bonus_id'])->update();
                }
            }
            // 用护帐号金额
            $from_user_account = $this->model->table('user_account')->field('id')->where(array('user_id'=> $from_user_id))->select();
            if(!empty($from_user_account)){
                foreach ($from_user_account as $key => $value) {
                    $this->model->table('user_account')->data('user_id = ' . $to_user_id)->where('id = ' . $value['id'])->update();
                }
            }
            // 用护标记
            $from_tag = $this->model->table('tag')->field('tag_id')->where(array('user_id'=> $from_user_id))->select();
            if(!empty($from_tag)){
                foreach ($from_tag as $key => $value) {
                    $this->model->table('tag')->data('user_id = ' . $to_user_id)->where('tag_id = ' . $value['tag_id'])->update();
                }
            }
            // 用护日志
            $from_account_log = $this->model->table('account_log')->field('log_id')->where(array('user_id'=> $from_user_id))->select();
            if(!empty($from_account_log)){
                foreach ($from_account_log as $key => $value) {
                    $this->model->table('account_log')->data('user_id = ' . $to_user_id)->where('log_id = ' . $value['log_id'])->update();
                }
            }

            // 用护评论
            $from_comment = $this->model->table('comment')->field('comment_id')->where(array('user_id'=> $from_user_id))->select();
            if(!empty($from_comment) && !empty($to_user_info)){
                foreach ($from_comment as $key => $value) {
                    $setdata = array('user_id' => $to_user_id, 'user_name' => $to_user_info['user_name'], 'email' => $to_user_info['email']);
                    $this->model->table('comment')->data($setdata)->where('comment_id = ' . $value['comment_id'])->update();
                }
            }

            // 用护退换货
            $from_order_return = $this->model->table('order_return')->field('ret_id')->where(array('user_id'=> $from_user_id))->select();
            if(!empty($from_order_return)){
                foreach ($from_order_return as $key => $value) {
                    $this->model->table('order_return')->data('user_id = ' . $to_user_id)->where('ret_id = ' . $value['ret_id'])->update();
                }
            }

            
            return true;
        }else{
            return false;
        }
    }

    /**
     * 查询会员信息
     * @param  integer $user_id
     * @param  integer $wechat_id
     * @return array
     */
    function get_users($user_id)
    {
        $result = $this->model->table('users')->where(array('user_id' => $user_id))->find();
        return $result;
    }
    //0:不属于任何VIP,1:白银理客VIP,2：黄金理客VIP，3：钻石理客VIP,4:至尊理客,VIP5.省代理，vip6国家代理，7股东
    function getUserVipName($userVip){
        $arr = ["普通会员","店主","服务商","合伙人","商城合伙人"];
        return $arr[$userVip];
    }
    /**
     * 查询会员信息
     * @param  integer $user_id
     * @param  integer $wechat_id
     * @return array
     */
    function getusersbyaccount($account)
    {
        $result = $this->model->table('users')->where(array('user_name' => $account))->find();
        return $result;
    }
    function get_usersbycode($invite_code)
    {
        $result = $this->model->table('users')->field('user_id')->where(array('invite_code' => $invite_code))->find();
        return $result;
    }
    function get_usersbyvipaccount($vipaccount)
    {
        $sql = "SELECT user_vip,resource,nick_name  FROM ".$this->pre."users where vip_manage_account = '".$vipaccount."'" ." and status <> 9";

        $result = $this->row($sql);
        if($result){
            return $result;
        }else{
            return false;
        }

    
    }
    function getnewusersbyvipaccount($vipaccount)
    {
        $result = $this->model->table('vip_account')->field('user_id')->where(array('vip_manage_account' => $vipaccount))->find();
        return $result;
    }

    /**
     * find user by username
     * @param $user_name
     * @return mixed
     */
    function getUsersByUserName($user_name)
    {
        $result = $this->model->table('users')->field('user_id')->where(array('user_name' => $user_name))->find();
        return $result;
    }

    /**
     * 微信粉丝生成用护名规则
     * 长度最大15个字符 兼容UCenter用护名
     * @return
     */
    function get_wechat_username($unionid, $type = '')
    {
        switch ($type) {
            case 'weixin':
                $prefix = 'wx';
                break;
            case 'qq':
                $prefix = 'qq';
                break;
            case 'weibo':
                $prefix = 'wb';
                break;
            case 'facebook':
                $prefix = 'fb';
                break;
            default:
                $prefix = 'sc';
                break;
        }
        return $prefix . substr(md5($unionid), -5) . substr(time(), 0, 4) . mt_rand(1000, 9999);
    }

    /**
     *  支付宝订单退款操作
     *
     * @access  public
     * @param   int         $order_id       订单ID
     * @param   int         $user_id        用护ID
     *
     * @return   bool                     退款状态
     */
    function get_refund_order($order_id, $user_id = 0)
    {
        $order_id = intval($order_id);
        if ($order_id <= 0) {
            ECTouch::err()->add(L('invalid_order_id'));

            return false;
        }
        $order = model('Order')->order_info($order_id);

        $sql = "SELECT pay_code  FROM ".$this->pre."payment where pay_id = '".$order['pay_id']."'" ." and enabled = 1 and is_online = 1";
        $pay = $this->row($sql);

        //对已付款未发货订单进行退款
        if($order['order_status'] == 1 && $order['shipping_status'] == 0 && $order['pay_status'] == 2 && $pay['pay_code'] == 'alipay')
        {
            $sql = "SELECT pay_code, pay_config  FROM ".$this->pre."payment where pay_id = '".$order['pay_id']."'" ." and enabled = 1 and is_online = 1";
            $pay = $this->row($sql);
            $payment = unserialize_config($pay['pay_config']);

            //查询订单的支付金额
            $sql = "SELECT order_amount, log_id from ".$this->pre."pay_log where order_id = '".$order['order_id']."' and is_paid = 1";
            $order_amount = $this->row($sql);
            $order['order_amount'] = $order_amount['order_amount'];
            $order['log_id'] = $order_amount['log_id'];
            /* 调用相应的支付方式文件 */
            include_once(ROOT_PATH . 'plugins/payment/' . $pay['pay_code'] . '.php');
             /* 在线退款*/
            $pay_obj = new $pay['pay_code'];
            $result = $pay_obj->refund($order, $payment);
            //退款成功
            return  $result;
        }
        return false;
    }

    
    /**
     * 非退货退款订单
     * @access  public
     * @param   id $user_id 原会员id
     * @return  string      $v 非退货退款订单字符串
     */
    public function order_rec_id($user_id){
        $sql = "SELECT rec_id FROM ". $this->pre ."order_return where user_id = '$user_id' and service_id = 1";
        $result = $this->query($sql);
        
        foreach($result as $key =>$val){
            if($val['rec_id']){
                $t = $val['rec_id'];
                $v .= $t.",";
            }
        }
        $v = substr($v,0,-1) ;
        return $v;
    }
    
    /**
     * 查询用户openid
     * @access  public
     * @param  $user_id 原会员id
     * @return $openid  用户openid
     */
    function get_openid($user_id) {
        $result = $this->model->table('wechat_user')->field('openid')->where(array('ect_uid' => $user_id))->find();
        return $result['openid'];
    }
    
    //订单转赠接口
    public function giveorder($user_id,$to_user_id,$order_id){
        
        // $user_id = $_SESSION['user_id'];
        // $touser_name = $_POST['user_name'];
        // $order_id = $_POST['order_id'];
        $touserinfo = $this->find(array('user_id' => $to_user_id));
        $order =model('Order')->order_info($order_id);;
        $copyorder =$order;
        $copyorder['user_id'] =  $to_user_id;
        
        
        do {
            $copyorder['order_sn'] =  get_order_sn();
            $copyorder['consignee'] = "";
            $copyorder['country'] = "";
            $copyorder['province'] = "";
            $copyorder['city'] = "";
            $copyorder['district'] = "";
            $copyorder['address'] = "";
            $copyorder['order_status'] = "7";//待激活，等被授予人登录后才去修改状态为5
            $copyorder['mobile'] = $touserinfo['mobile_phone'];
            
            $new_order = model('Common')->filter_field('order_info', $copyorder);
            unset($new_order['order_id']);
            $this->model->table('order_info')->data($new_order)->insert();
            $error_no = M()->errno();
            
            if ($error_no > 0 && $error_no != 1062) {
                die(M()->errorMsg());
            }
        } while ($error_no == 1062); // 如果是订单号重复则重新提交数据
        $new_order_id = M()->insert_id();
        $order ['order_id'] = $new_order_id;
        $order_goods =model('Base')->model->table('order_goods')
        ->where('order_id = ' . $order_id)
        ->select();
        foreach ($order_goods as $key1 => $value1) {
            
            $value2 = $value1;
            unset($value2['rec_id']);
            $value2['order_id'] =  $new_order_id;
            $this->model->table('order_goods')->data($value2)->insert();
            
            
            # code...
        }
        
        $where_u['order_id'] = $order_id;
        
        $data_u['order_status'] = "6";//订单隐藏状态
        $this->model->table('order_info')->data($data_u)->where($where_u)->update();
        if($new_order_id){
            return $new_order_id;
            
        } else{
            return false;
        }
        /* 插入订单商品 */
    }
    /* 上传文件 */
    function upload_avatar_file($upload)
    {
        if (!make_dir("../" . DATA_DIR . "/attached/avatar"))
        {

            /* 创建目录失败 */
            return false;
        }

        $filename = image::random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
         echo $filename ;exit;
        $path     = ROOT_PATH. DATA_DIR . "/attached/avatar/" . $filename;

        if (ecmoban_move_upload_file($upload, $path))
        {
            return DATA_DIR . "/attached/avatar/" . $filename;
        }
        else
        {
            return false;
        }
    }
      /**
     * 查询会员信息
     * @param  integer $user_id
     * @param  integer $wechat_id
     * @return array
     */
    function getusersinfo($user_id)
    {

        $result = $this->model->table('users')->where(array('user_id' => $user_id))->find();

        return $result;
    }
      /**
     * 查询会员信息
     * @param  integer $user_id
     * @param  integer $wechat_id
     * @return array
     */
    function getmainpagetemplatelist($catid)
    {
        $result = $this->model->table('mainpagetemplate')->where(array('catid' => $catid))->select();
        return $result;
    }
    /*查询该会员有哪些主页*/
    function getusermainpagelist($user_id)
    {
        $sql = "SELECT mainpage_id,template_id  FROM ".$this->pre."mainpage where user_id = '".$user_id."' order by mainpage_id ASC";
        $result = M()->query($sql);
        return $result;
    }
     /**
     * 查询会员信息
     * @param  integer $user_id
     * @param  integer $wechat_id
     * @return array
     */
    function getuserprofilearticleinfo($user_id)
    {
        /*获取该用户的默认mid*/
        $userinfo  = $this->model->table('users')->where(array('user_id' => $user_id))->find();
        
        $result = $this->model->table('user_profile_article')->where(array('mid' => $userinfo['mainpage_id']))->order("sort asc")->select();

        return $result;
    }
         /**
     * 查询会员信息
     * @param  integer $user_id
     * @param  integer $wechat_id
     * @return array
     */
    function getuserprofilearticlebymid($mid)
    {
        $result = $this->model->table('user_profile_article')->where(array('mid' => $mid))->order("sort asc")->select();

        return $result;
    }
    /*获取主页信息*/
    function getusermainpagebyuserid($user_id)
    {
        $userinfo  = $this->model->table('users')->where(array('user_id' => $user_id))->find();
   
        $mainpage  = $this->model->table('mainpage')->where(array('mainpage_id' => $userinfo['mainpage_id']))->find();
        if(!$mainpage){
            $mainpage['signcomment'] = '会员可打造定制化主页，展示个人品牌从而获得有效的人脉。';
            $mainpage['sign'] = '健康新蓝海,财富大未来！';
            $mainpage['job'] = '新零售经销商';
            $mainpage['company'] = '拓客(拓客)全球事业';
        }
        return $mainpage;
    }
    /*获取主页信息*/
    function getusermainpagebymid($mid)
    {
        $result = $this->model->table('mainpage')->where(array('mainpage_id' => $mid))->find();
         
        return $result;
    }
    function getarticleinfo($id){

        $result = $this->model->table('user_profile_article')->where(array('id' => $id))->find();
        return $result;

    }

    /**
     * [将Base64图片转换为本地图片并保存]
     * @E-mial wuliqiang_aa@163.com
     * @TIME   2017-04-07
     * @WEB    http://blog.iinu.com.cn
     * @param  [Base64] $base64_image_content [要保存的Base64]
     * @param  [目录] $path [要保存的路径]
     */
    function base64_image_content($base64_image_content,$path,$time){
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
            $type = $result[2];
            $new_file = $path."/".date('Ymd',time())."/";
            if(!file_exists($new_file)){
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
               
                $r = mkdir($new_file, 0700,true);
                
            }
            $rand= rand(10,100);
            $new_file = $new_file.time().$time.$rand.".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){

                return '/'.$new_file;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    //判断字符串开头
    function startwith($str,$pattern) {
    if(strpos($str,$pattern) === 0)
          return true;
    else
          return false;
    }
    
    public function ifChuangke($user_id,$rank_points=null){
        $result = $this->model->table('user_rank')->where(array('rank_id' => 2))->find();
        
        if($rank_points!==null){
            if($rank_points>$result["min_points"])return true;
            else{return false;}
        }
        $user = $this->find(["user_id"=>$user_id],"rank_points");
        $rank_points = $user["rank_points"];
        if($rank_points>$result["min_points"])return true;
        else{return false;}
    }
    public function viporderlist($num)
    {
        /*获取订单列表*/
          /*查询每个商品的等级积分,0或者3000*/
        /*如果是3000,那么取这个商品的数量当作续费等级的年限*/
        $sql = "SELECT og.order_id as order_id ,o.user_id as user_id,g.goods_id,g.goods_name,g.rank_integral" .
                    " FROM " . $this->pre . "order_goods AS og inner JOIN " .
                    $this->pre . "goods AS g " ." inner JOIN ecs_order_info AS o".
                    " on og.goods_id = g.goods_id AND " ." o.order_id = og.order_id " .
                    "AND g.goods_area =2  and o.pay_status = 2  group by o.user_id desc LIMIT " .$num  ;

              
        // SELECT og.order_id as order_id ,o.user_id as user_id,g.goods_id,g.goods_name,g.rank_integral FROM ecs_order_goods AS og inner JOIN ecs_goods AS g inner JOIN ecs_order_info AS o on og.goods_id = g.goods_id AND o.order_id = og.order_id AND g.rank_integral >=10000 order by og.order_id desc group by o.user_id desc LIMIT 20
     
        $res = M()->query($sql);
  
        foreach ($res as $key => $value) {
            # code...
             $order = model('Order')->order_info($value['order_id']);
           
            /*检测商品里面是否有升级商品*/
             $viporderlist[$key]['user_id'] = $order['user_id'];   
             $sql2 = "SELECT nick_name,user_name,user_avatar FROM " . $this->pre . "users WHERE user_id = '{$order['user_id']}'";
             $row2= $this->row($sql2);
                    
             $viporderlist[$key]['nick_name'] = $this->addstar($row2['nick_name']?$row2['nick_name']:$row2['user_name']);
             $viporderlist[$key]['user_avatar'] = $row2['user_avatar'];
             $viporderlist[$key]['goods_name'] = $value['goods_name']; 
             if(count($viporderlist)==$num){
               break;
             }

        }
        if($viporderlist){
              return $viporderlist;
          }else{
              return [];
          }
    }
    public function addstar($str)
    {
        //判断是否包含中文字符
        if(preg_match("/[\x{4e00}-\x{9fa5}]+/u", $str)) {
            //按照中文字符计算长度
            $len = mb_strlen($str, 'UTF-8');
            //echo '中文';
            if($len >= 3){
                //三个字符或三个字符以上掐头取尾，中间用*代替
                $str = mb_substr($str, 0, 1, 'UTF-8') . '***' . mb_substr($str, -1, 1, 'UTF-8');
            } elseif($len == 2) {
                //两个字符
                $str = mb_substr($str, 0, 1, 'UTF-8') . '***';
            }
        } else {
            //按照英文字串计算长度
            $len = strlen($str);
            //echo 'English';
            if($len >= 3) {
                //三个字符或三个字符以上掐头取尾，中间用*代替
                $str = substr($str, 0, 1) . '***' . substr($str, -1);
            } elseif($len == 2) {
                //两个字符
                $str = substr($str2, 0, 1) . '***';
            }
        }
        return $str;

    }
    function bindopenid($openid){
        //绑定openid
        
        $update_data['openid'] = $openid;
        $condition['user_id'] = $_SESSION['user_id'];
        
        $r = $this->update($condition, $update_data);
        
    }
    function unbindopenid($openid){
        //绑定openid
        
            
        $update_data['openid'] = "";
        $condition['openid'] = $_SESSION['openid'];
        $r = $this->update($condition, $update_data);
    }
     function unbindopenidbyuserid($user_id){
        //绑定openid
        
            
        $update_data['openid'] = "";
        $condition['user_id'] = $user_id;
        $r = $this->update($condition, $update_data);
    }

    function updateSignCount($user_id){
        
        $update_data['count'] = 0;
        $condition['user_id'] = $user_id;
        $r = $this->update($condition, $update_data);

    }


    function refferarticlenum($user_id){
        $sql1 = "SELECT user_id,updatetime,num FROM " . $this->pre . "reffer_article WHERE user_id = '{$user_id}'";

         $row1= $this->row($sql1);
         $update_time = $row1['updatetime'];
         $timestamp = time();

         if((date('Ymd', $timestamp) != date('Ymd',$update_time))){
            /*隔天重置为0*/
            $sql2 = "UPDATE `ecs_reffer_article` SET `num` = 0,`updatetime`= '" .time()."' WHERE user_id = '$user_id'";
                
            $r2 = $this->model->query($sql2);
         }

         $sql = "SELECT num FROM " . $this->pre . "reffer_article WHERE user_id = '{$user_id}'";

         $row= $this->row($sql);
         return $row['num'];
    }
    function updateRefferArticle($user_id){
         $sql = "SELECT user_id,updatetime,num FROM " . $this->pre . "reffer_article WHERE user_id = '{$user_id}'";
         
         $row= $this->row($sql);

      
         if($row){

            $update_time = $row['updatetime'];
            $int=date('Y-m-d');
            $int=strtotime($int);//5
            $ints=$int+86400;  //6
            $int_s=$int-86400;  //4
            /*该用户的文章被分享阅读数量小于3*/
            $timestamp = time(); 
            if((date('Ymd', $timestamp) == date('Ymd',$update_time))){
                /*如果是今天内且数量小于3*/
                if($row['num']<3){
                    $sql = "UPDATE `ecs_reffer_article` SET `num` = `num` + 1,`updatetime`= '" .time()."' WHERE user_id = '$user_id'";
                
                    $r = $this->model->query($sql);

                    return true;
                }
                

            }else{

                
                    $sql = "UPDATE `ecs_reffer_article` SET `num` = 1,`updatetime`= '" .time()."' WHERE user_id = '$user_id'";
                
                    $r = $this->model->query($sql);

                
                

                return false;
            }
            
         }else{
             $data['num'] = 1;
             $data['user_id'] = $user_id;
             $data['updatetime'] = time();
             $this->model->table('reffer_article')->data($data)->insert();
             return true;
         }
    }
    /**/
    function reffer_article_day_task($user_id){
        
    }
    /*更新kd*/
    function updateTask($parent_id,$kd,$count,$sign_time){
        //绑定openid
        $user_id = $parent_id?$parent_id:$_SESSION['user_id'];
        if($kd){
          //$update_data['pay_points'] = $update_data['pay_points']+$kd; 
           // $sql = "UPDATE `ecs_users` SET `pay_points` = `pay_points` + '" . $kd . "' WHERE user_id = '$user_id'";
          
           // $r = $this->model->query($sql);
        }
        if($count){
          
            $sql = "UPDATE `ecs_users` SET `count` = '" . $count . "' WHERE user_id = '$user_id'";
       
            $r =  $this->model->query($sql);
        }
        if($sign_time){
     
           $sql = "UPDATE `ecs_users` SET `sign_time` = '" . $sign_time . "' WHERE user_id = '$user_id'";
       
           $r =  $this->model->query($sql);

        }
        
        if($r){
            return true;
        }else{
            return false;
        }


    }
    /*会员升级vip，入团以及升级*/
    function updateVip($user_id,$viplevel){
        /*如果用户的入团金额是否达到省代理29800 level：5，全国代理：99800 level:6，LKD股东：499800 LEVEL:7*/
        // $sql = "SELECT area_amount_total,user_rank FROM ecs_users WHERE user_id = '$user_id'";
        // $row= $this->row($sql);
        
            
               
        $update_data['user_rank'] = $viplevel;
        $condition['user_id'] = $user_id;
        $_SESSION['user_rank'] =  $viplevel;        
               
   

        $r = $this->update($condition, $update_data);

        
        

    }
    /*更新店主身份，服务商身份，社区合伙人身份*/
    function updateUserVip($user_id,$vip){
        $condition['user_id'] = $user_id;
        $update_data['user_vip'] = $vip;
        $r = $this->update($condition, $update_data);
    }
    function updateAreaTotal($user_id,$order_amount){
         // 统计入团金额
        $sql = "UPDATE `ecs_users` SET `area_amount_total` = `area_amount_total` + '" . $order_amount . "' WHERE user_id = '$user_id'";
     
        $this->model->query($sql);
    }

    

    function updateCommTotal($user_id,$order_amount){
         // 统计零售金额
        $sql = "UPDATE `ecs_users` SET `com_amount_total` = `com_amount_total` + '" . $order_amount . "' WHERE user_id = '$user_id'";
       
        $this->model->query($sql);
    }
    function updateUserPv($user_id,$total_pv){
        $sql = "UPDATE `ecs_users` SET `pv` = `pv` + '" . $total_pv . "' WHERE user_id = '$user_id'";
        $this->model->query($sql);
        
         

    }

     /*更新用户的入团邀请码*/
    function updateUserOtherInviteCode($user_id,$other_invite_code){

        
         
         $sql = "UPDATE `ecs_users` SET `other_invite_code` = '" . $other_invite_code . "' WHERE user_id = '$user_id'";
     
         $r =  $this->model->query($sql);
        
         $sql1 = "SELECT user_id FROM " . $this->pre . "vip_account WHERE vip_manage_account = '{$other_invite_code}'";
       
         $row1= $this->row($sql1);
  
         $sql2 = "UPDATE `ecs_users` SET `parent_id` = '" . $row1['user_id'] . "' WHERE user_id = '$user_id'";

         $r2 =  $this->model->query($sql2);
         

    }
     function updateUserJoinAddress($user_id,$country_id,$province_id,$city_id,$country,$province,$city){
            
            $sql = "UPDATE `ecs_users` SET `country_id` = 1,`province_id` = ".$province_id.",`city_id` = ".$city_id.",`country` = '".$country."',`province` = '".$province."',`city` = '".$city."'  WHERE user_id = '$user_id'";
                
            $r =  $this->model->query($sql);
    }
    function getUserRankList(){

         $sql = "SELECT rank_id,rank_name FROM " . $this->pre . "user_rank_list";
         $row= $this->model->query($sql);
         return $row;

    }
    function userisvip($user_id)
    {
        $sql = "SELECT user_vip FROM " . $this->pre . "users WHERE user_id = '$user_id'";
        $row= $this->row($sql);
        
        if($row['user_vip']>0){
            $vip =1;
        }else{
            $vip =0;
        }
        return $vip;
    }
        /**
     * 查询会员信息
     * @param  integer $user_id
     * @param  integer $wechat_id
     * @return array
     */
    function getuserlist()
    {
        $result = $this->model->table('users')->select();

        return $result;
    }
    function getuserlistdata($province_id,$city_id)
    {
        $where['city_id'] = $city_id;
        $where['province_id'] = $province_id;
        $result = $this->model->table('users')->field('user_name,invite_code,user_id, user_avatar,nick_name')->where($where)->select();

        return $result;
    }

    function getParentInviteCode($user_id)
    {
        $sql = "SELECT parent_id FROM " . $this->pre . "users WHERE  user_id= '$user_id'";
        $row= $this->row($sql);
        if($row['parent_id']){
            $sql1 = "SELECT vip_manage_account FROM " . $this->pre . "users WHERE  user_id= '{$row['parent_id']}'";
             $row1= $this->row($sql1);
             if($row1['vip_manage_account']){
                return $row1['vip_manage_account'];
             }else{
                return "";
             }
        }else{
            return "";
        }

    }
    function getuserkvipname($viplevel)
    {
        $where['rank_id'] = $viplevel;
                   
        $res = $this->model->table('user_rank_list')
                ->field('rank_name')
                ->where($where)
                ->find();
        return  $res['rank_name'];
    }
    function getuserrank($user_id)
    {
        $where['user_id'] = $user_id;
                   
        $res = $this->model->table('users')
                ->field('user_rank')
                ->where($where)
                ->find();
        return  $res['user_rank'];
    }
    function getusername($code)
    {
        $where['invite_code'] = $code;
                   
        $res = $this->model->table('users')
                ->field('user_name,nick_name')
                ->where($where)
                ->find();
             
        if($res){
              if($res['nick_name']){
                     return  $res['nick_name'];
                }else{
                   return  $res['user_name'];  
                }
            }else{
                return false;
            }
              
       
    }

    function getuservip($code){
        $where['invite_code'] = $code;
                   
        $res = $this->model->table('users')
                ->field('user_rank')
                ->where($where)
                ->find();
             
        if($res){
              if($res['user_rank']){
                     return  $res['user_rank'];
                }else{
                   return  false;  
                }
            }else{
                return false;
            }
    }
     function getuserid($user_name){
        $where['user_name'] = $user_name;
                   
        $res = $this->model->table('users')
                ->field('user_id')
                ->where($where)
                ->find();
             
        if($res){
              if($res['user_id']){
                     return  $res['user_id'];
                }else{
                   return  false;  
                }
            }else{
                return false;
            }
    }
    /*增加vip管理账号*/
    function  addvipamanageaccount($user_id,$number)
    {
   
            
             $data['user_id'] = $user_id;
             $data['vip_manage_account'] = "YT".$number;
             $data['add_time'] = time();
             $this->model->table('vip_account')->data($data)->insert();
    }
    function addvipamanageaccounttouser($user_id,$number){
        
         $updateData['vip_manage_account'] = "YT".$number;

         $this->update(["user_id"=>$user_id],$updateData);
    }
    /*增加vip管理账号*/
    function  newaddvipamanageaccount($user_id,$account,$resource)
    {
   
           
           $data['user_id'] = $user_id;
           $data['vip_manage_account'] = $account;
           $data['resource'] = $resource;
           $data['add_time'] = time();

           $r = $this->model->table('vip_account')->data($data)->insert();

           return $r;
    }
    function findvipmanageaccount($user_id,$account,$resource){

          $where['user_id'] = $user_id;
          $where['vip_manage_account'] = $account;
          $where['resource'] = $resource;

          $res = $this->model->table('vip_account')
                ->field('user_id')
                ->where($where)
                ->find();
          if($res){
              return false;
            }else{
              return true;
          }
    }
    function gettmaccount($user_id)
    {
        $where['user_id'] = $user_id;
                   
        $res = $this->model->table('vip_account')
                ->field('vip_manage_account')
                ->where($where)
                ->find();
             
        if($res){
                   return  $res['vip_manage_account'];
            }else{
                return false;
        }
              
       
    }
       
        /**
     * 查询会员信息
     * @param  integer $user_id
     * @param  integer $wechat_id
     * @return array
     */
    function getuserinfo($user_id)
    {
        $result = $this->model->table('users')->where(array('user_id' => $user_id))->find();

        return $result;
    }
        /**
     * 查询上一级vip用户名
     * @param  integer $user_id
     * @param  integer $wechat_id
     * @return array
     */
    function getparentuservipaccount($user_id)
    {
        $user = $this->model->table('users')->where(array('user_id' => $user_id))->find();
        $parentuseraccount = $this->model->table('vip_account')->where(array('user_id' => $user['parent_id']))->find();
        return $parentuseraccount;
    }
    /*获取可以升级的等级*/
    function get_rank_list_canupgrade($rank_id){

        $sql = "SELECT rank_id,rank_name FROM " . $this->pre . "user_rank_list WHERE rank_id >'$rank_id'";
    
        $result= $this->query($sql);

        return $result;

    }
    /*VIP店主
      VIP服务商
      VIP社群合伙人
      VVIP拓客合伙人
    */


      /*查询用户的奖金情况*/
      function lookBonus($account){

            $sql = "SELECT * FROM " . $this->model->pre . "account WHERE account='".$account."'";
            $query = $this->row($sql);
            
            if($query['bonus_distributor']||$query['bonus_vip']||$query['bonus_retail']){
                return true;
            }else{
                return false;
            }


      }
       /*查询用户的奖金情况*/
      function lookChengzhangzhi($user_id){

            $sql = "SELECT * FROM " . $this->model->pre . "users WHERE user_id='".$user_id."'";
            $query = $this->row($sql);
            $result = array();
            switch ($query['user_vip']) {
                case '0':
                    # code...普通身份
                    $result['aim']   = 100;
                    $result['points'] = $query['dianzhu_points'];
                    return $result;
                    break;
                case '1':
                    # code...
                    #      
                    $result['aim']   = 200;
                    $result['points'] = $query['servicer_points'];
                    return $result;
                    break;
                case '2':
                    # code...
                    $result['aim']   = 300;
                    $result['points'] = $query['shequn_points'];
                    return $result;
                    break;
                case '3':
                    # code...
                    $result['aim']   = 400;
                    $result['points'] = $query['tianwei_points'];
                    return $result;
                    break;
                case '4':
                    # code...
                    return 100;
                    break;

                default:
                    # code...
                    break;
            }


      }

      function affiliate_partner_new()
     {
        $rrr = $this->getUserRankList();
        
        $share = unserialize(C('affiliate'));
        $user_info = model('Users')->get_profile($_SESSION['user_id']);

        $this->user_id=$_SESSION['user_id'];
        if (empty($share['config']['separate_by'])) {
           

            // 推荐注册分成
            $affdb = array();
            $num = count($share['item']);
            $up_uid = "'$this->user_id'";
            $all_uid = "'$this->user_id'";
            for ($i = 1; $i <= $num; $i ++) {
                $count = 0;
                if ($up_uid) {
                    $where = 'parent_id IN(' . $up_uid . ')';
                    $rs = $this->model->table('users')
                        ->field('user_id')
                        ->where($where)
                        ->select();
                      
                    if (empty($rs)) {
                        $rs = array();
                    }
                    $up_uid = '';
                    foreach ($rs as $k => $v) {
                        $up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
                        if ($i < $num) {
                            $all_uid .= ", '$v[user_id]'";
                        }
                        $count ++;
                    }
                }
                $affdb[$i]['num'] = $count;
                $affdb[$i]['point'] = $share['item'][$i - 1]['level_point'];
                $affdb[$i]['money'] = $share['item'][$i - 1]['level_money'];
             
            }
          
            $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
           
            $sql1 = "SELECT o.*, a.log_id,a.time, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";

        } else {
            
            // 推荐订单分成
            $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
            
            $sql1 = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
        }
          
        $rt = $this->model->query($sql1);
       
        $auid = $this->user_id;
        
        $user_list['user_list'] = array();
        
        $up_uid = "'$auid'";
        $all_count = 0;
        for ($i = 1; $i <= 1; $i ++) {
            $count = 0;
            if ($up_uid) {
                $sql = "SELECT user_id FROM " . $this->model->pre . "users WHERE parent_id IN($up_uid)";
                $query = $this->model->query($sql);
                
                $up_uid = '';
                foreach ($query as $k => $v) {
                    $up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
                    $count ++;
                }
            }
            $all_count += $count;
            
            if ($count) {
                $sql = "SELECT user_id,country,province,city,user_rank, user_name, '$i' AS level, email, is_validated,  user_avatar,nick_name, rank_points,  reg_time " . " FROM " . $this->model->pre . "users WHERE user_id IN($up_uid)" . " ORDER by  reg_time desc";
                $result1 = $this->model->query($sql);
               
                $user_list['user_list'] = array_merge($user_list['user_list'], $this->model->query($sql));
            }
        }
        
        $levelone = 0;
        $leveltwo = 0;
        // 查询每个下级和下两级会员的佣金分总
        foreach ($user_list['user_list'] as $key => $value) {
            // code...
            
            foreach ($rt as $key1 => $value1) {
                if ($value1['user_id'] == $value['user_id']) {
                        
                    $user_list['user_list'][$key]['money'] += $value1['money']?$value1['money']:0;
                }
                
                // code...
            }
            if($value['level']==1){
                $levelone ++;
            }
             if($value['level']==2){
                $leveltwo ++;
            }
            if ($value['reg_time']) {
                $user_list['user_list'][$key]['reg_time'] = date("Y-m-d", $value['reg_time']);
            }

            $sql1 = "select rank_name from " . $this->model->pre . "user_rank  where min_points<=" . $value['rank_points'] . " and max_points >=" . $value['rank_points'];
                
                $rs1 = $this->model->query($sql1);
                
                $user_list['user_list'][$key]['rank_name'] = $rs1[0]['rank_name'];
        }
       
        $datetime = array();
        
        foreach ($user_list['user_list'] as $user) {
            $datetime[] = $user['reg_time'];
        }
        array_multisort($datetime, SORT_DESC, $user_list['user_list']);
        

        $user = $this->model->table('users')
            ->field('parent_id')
            ->where(array(
            'user_id' => $this->user_id
        ))
            ->find();
        

        

       
        foreach ($user_list['user_list'] as $key3 => $value3) {
            /*获取*/
            $not_comment = model('ClipsBase')->not_pingjia($value3['user_id']);
            $order = model('Order')->getuserordernum($value3['user_id']);

            $isvip = model('Users')->userisvip($value3['user_id']);
            //$userYeJi = 333;
            $user_list['user_list'][$key3]['rank_name'] = $value3['user_rank']?$rrr[$value3['user_rank']-1]['rank_name']:"普通会员";
            $user_list['user_list'][$key3]['isvip'] = $isvip;
            $user_list['user_list'][$key3]['ordernum'] = $order;
            $user_list['user_list'][$key3]['ordercount'] =  $not_comment ;
           // $user_list['user_list'][$key3]['userYeJi']  = $userYeJi ;


            $user_list['user_list'][$key3]['user_name'] =  substr($value3['user_name'], 0, 5).'****'.substr($value3['user_name'], 9) ;

            # code...
        }
         
     
        return $user_list['user_list'];
        
    }

    /*vip用户的收益业绩*/
    function  userYeJi($user_id){







    }

    function new_compile_password($cfg){
       $options = [
            'cost' => 10, // 默认是10，用来指明算法递归的层数
                // mcrypt_create_iv — 从随机源创建初始向量
                // @param 初始向量大小
                // @param 初始向量数据来源
                // 可选值有： MCRYPT_RAND （系统随机数生成器）, MCRYPT_DEV_RANDOM （从 /dev/random 文件读取数据） 和 MCRYPT_DEV_URANDOM （从 /dev/urandom 文件读取数据）。 在 Windows 平台，PHP 5.3.0 之前的版本中，仅支持 MCRYPT_RAND。请注意，在 PHP 5.6.0 之前的版本中， 此参数的默认值为 MCRYPT_DEV_RANDOM。
                // 生成一个长度为22的随机向量

        ];
 

    $crypt = password_hash($cfg['password'], 1, $options);


    return $crypt;

    }

    function updatepassword($user_id,$updateData){
         $r = $this->update(["user_id"=>$user_id],$updateData);
    }

    function getvipname($user_id){
         $sql = "SELECT user_vip FROM " . $this->model->pre . "users WHERE user_id='".$user_id."' ";
           
         $query = $this->row($sql);
         switch ($query['user_vip']) {
             case '0':
                 # code...
                 return "普通会员";
                 break;
            case '1':
                return "VIP店主";
             
            case '2':
                return "VIP服务商";

            case '3':

                return "VIP社群合伙人";
            case '4':

                return "VIP商城合伙人";
             default:
                 # code...
                 break;
         }


    }
    /*获取每个会员等级成长值的上限值*/
     function getvipfengding($user_id){
         $sql = "SELECT user_vip FROM " . $this->model->pre . "users WHERE user_id='".$user_id."' ";
           
         $query = $this->row($sql);
         switch ($query['user_vip']) {
             case '0':
                 # code...
                 return "100";
                 break;
            case '1':
                return "200";
             
            case '2':
                return "300";

            case '3':

                return "400";
            case '4':

                return "--";

             default:
                 # code...
                 break;
         }


    }
    /*注册时候生成一条account数据*/
    public function insertAccount($user_id){


            $accountdata['user_id'] = $user_id;
            $accountdata['created_at'] = local_date(C('time_format'), time());
            $accountdata['updated_at'] = local_date(C('time_format'), time());
            $this->table = 'account';

            $res = $this->insert($accountdata);



    }

    /*会员推荐链路串接*/
    public function updateuser_reposition($user_id,$parent_id=0){


        if($parent_id){
            
            /*有上级  上级的user_reposition+‘_’+UID  例如 上级是U2771 自己UID是2880  user_position 就是 U2771_2880*/
            $selfsql = "SELECT user_vip from " . $this->pre . "users where user_id='$user_id'";
            $selfresult = $this->row($selfsql);
            $sql = "SELECT user_reposition,resource,user_vip from " . $this->pre . "users where user_id='$parent_id'";
            $res = $this->row($sql);

            $parentuser_reposition = $res['user_reposition'] ;
            $resource = $res['resource'] ;
            /*如果是自己的身份是vip，则不允许变更resoure*/
            $updateData['user_reposition'] = $parentuser_reposition."_".$user_id;
            
            if(!$selfresult['user_vip']){
                /*非店主VIP允许变更
                非店主VIP首次购买VIP套餐允许变更
                */
                $updateData['resource'] = $resource;
                $this->update(["user_id"=>$user_id],$updateData);
            }else{
                $this->update(["user_id"=>$user_id],$updateData);
            }
          

        }else{
            /*无上级  ‘U’+UID  例如：U2771*/
             $updateData['user_reposition'] = "U".$user_id;
             $this->update(["user_id"=>$user_id],$updateData);

        }
    }
        /*会员推荐链路串接*/
    public function new_updateuser_reposition($user_id,$parent_id=0){


        if($parent_id){
            
            /*有上级  上级的user_reposition+‘_’+UID  例如 上级是U2771 自己UID是2880  user_position 就是 U2771_2880*/
            $selfsql = "SELECT user_vip from " . $this->pre . "users where user_id='$user_id'";
            $selfresult = $this->row($selfsql);
            $sql = "SELECT user_reposition,resource,user_vip from " . $this->pre . "users where user_id='$parent_id'";
            $res = $this->row($sql);

            $parentuser_reposition = $res['user_reposition'] ;
            $resource = $res['resource'] ;
            /*如果是自己的身份是vip，则不允许变更resoure*/
            $updateData['user_reposition'] = $parentuser_reposition."_".$user_id;
            
            if(!$selfresult['user_vip']){
                /*非店主VIP允许变更
                非店主VIP首次购买VIP套餐允许变更
                */
                $updateData['resource'] = $resource;
                $r = $this->update(["user_id"=>$user_id],$updateData);
            }else{
                $r = $this->update(["user_id"=>$user_id],$updateData);
            }
          

        }else{
            /*无上级  ‘U’+UID  例如：U2771*/
             $updateData['user_reposition'] = "U".$user_id;
             $r = $this->update(["user_id"=>$user_id],$updateData);

        }
        if($r){
            return true;
        }else{
            return false;
        }
    }

//跟新某用户下面的所有子孩子的推荐关系
    public function updateAllChildReposition($user_id,$uids='',$dep=1){
        $userList =  $this->model->query("select user_id,parent_id from ecs_users where parent_id=$user_id");
     
        foreach ($userList as $key=>$value){
            $uids .= $value['user_id'].',';
            /*更新用户的推荐琏字段*/
            $this->updateuser_reposition($value['user_id'],$value['parent_id']);
            $user = $this->model->query("select user_id,parent_id from ecs_users where parent_id={$value['user_id']}");
            if($user){
                $uids = $this->updateAllChildReposition($value['user_id'],$uids,$dep+1);
            }
        }
        return $uids;
    }

    public function changeParent($user_id,$parent_id){


             $updateData['parent_id'] = $parent_id;
             $r = $this->update(["user_id"=>$user_id],$updateData);

             if($r){
                return true;
             }else{
                return false;
             }

    }
    public function getTree($data, $parent_id = '0')
    {
        $arr = [];
        foreach($data as $key => $val){
            if($val['parent_id'] == $parent_id){
                $val['children'] = $this->getTree($data, $val['user_id']);
                $arr[] = $val;
            }
        }
        return $arr;

    }
    /*购买vip套餐应该赠送的福豆数量*/
    public function givePayPoints($user_id,$goods_id){

        $usersql = "SELECT user_vip from " . $this->pre . "users where user_id='$user_id'";
        
        $userres = $this->row($usersql);

        $goodssql = "SELECT vip_give_integral,give_integral,shop_price from " . $this->pre . "goods where goods_id='$goods_id'";
        
        $goodsres = $this->row($goodssql);

        if($userres['user_vip']){

            return $goodsres['vip_give_integral']=="-1"?$goodsres['shop_price']:$goodsres['vip_give_integral'];

        }else{

            return $goodsres['give_integral']=="-1"?$goodsres['shop_price']:$goodsres['give_integral'];

        }   
       





    }
    public function getAllChild($user_id)
     {
        $rrr = $this->getUserRankList();
        
        $share = unserialize(C('affiliate'));
        $user_info = model('Users')->get_profile($user_id);
        if (empty($share['config']['separate_by'])) {
           

            // 推荐注册分成
            $affdb = array();
            $num = count($share['item']);
            $up_uid = "'$user_id'";
            $all_uid = "'$user_id'";

            for ($i = 1; $i <= $num; $i ++) {
                $count = 0;
                if ($up_uid) {
                    $where = 'parent_id IN(' . $up_uid . ')';
                    $rs = $this->model->table('users')
                        ->field('user_id')
                        ->where($where)
                        ->select();
                  
                    if (empty($rs)) {
                        $rs = array();
                    }
                    $up_uid = '';
                    foreach ($rs as $k => $v) {
                        $up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
                        if ($i < $num) {
                            $all_uid .= ", '$v[user_id]'";
                        }
                        $count ++;
                    }
                }
                $affdb[$i]['num'] = $count;
                $affdb[$i]['point'] = $share['item'][$i - 1]['level_point'];
                $affdb[$i]['money'] = $share['item'][$i - 1]['level_money'];
             
            }
         
            $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)";
           
            $sql1 = "SELECT o.*, a.log_id,a.time, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";

        } else {
            
            // 推荐订单分成
            $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$user_id' AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)";
            
            $sql1 = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$user_id' AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
        }
       
        $rt = $this->model->query($sql1);
        
        $auid = $user_id;
        
        $user_list['user_list'] = array();
        
        $up_uid = "'$auid'";
        $all_count = 0;
        for ($i = 1; $i <= 20; $i ++) {
            $count = 0;
            if ($up_uid) {
                $sql = "SELECT user_id FROM " . $this->model->pre . "users WHERE parent_id IN($up_uid)";
                $query = $this->model->query($sql);
                
                $up_uid = '';
                foreach ($query as $k => $v) {
                    $up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
                    $count ++;
                }
            }
            $all_count += $count;
            
            if ($count) {
                $sql = "SELECT user_id,country,province,city,user_rank, user_name, '$i' AS level, email, is_validated,  user_avatar,nick_name, rank_points,  reg_time " . " FROM " . $this->model->pre . "users WHERE user_id IN($up_uid)" . " ORDER by  reg_time desc";
                $result1 = $this->model->query($sql);
               
                $user_list['user_list'] = array_merge($user_list['user_list'], $this->model->query($sql));
            }
        }
        
        $levelone = 0;
        $leveltwo = 0;
        // 查询每个下级和下两级会员的佣金分总
        foreach ($user_list['user_list'] as $key => $value) {
            // code...
            
            foreach ($rt as $key1 => $value1) {
                if ($value1['user_id'] == $value['user_id']) {
                        
                    $user_list['user_list'][$key]['money'] += $value1['money']?$value1['money']:0;
                }
                
                // code...
            }
            if($value['level']==1){
                $levelone ++;
            }
             if($value['level']==2){
                $leveltwo ++;
            }
            if ($value['reg_time']) {
                $user_list['user_list'][$key]['reg_time'] = date("Y-m-d", $value['reg_time']);
            }

            $sql1 = "select rank_name from " . $this->model->pre . "user_rank  where min_points<=" . $value['rank_points'] . " and max_points >=" . $value['rank_points'];
                
                $rs1 = $this->model->query($sql1);
                
                $user_list['user_list'][$key]['rank_name'] = $rs1[0]['rank_name'];
        }
       
        $datetime = array();
        
        foreach ($user_list['user_list'] as $user) {
            $datetime[] = $user['reg_time'];
        }
        array_multisort($datetime, SORT_DESC, $user_list['user_list']);
        

        $user = $this->model->table('users')
            ->field('parent_id')
            ->where(array(
            'user_id' => $user_id
        ))
            ->find();
        

        

       
        foreach ($user_list['user_list'] as $key3 => $value3) {
            /*获取*/
            $not_comment = model('ClipsBase')->not_pingjia($value3['user_id']);
            $order = model('Order')->getuserordernum($value3['user_id']);

            $isvip = model('Users')->userisvip($value3['user_id']);
            //$userYeJi = 333;
            $user_list['user_list'][$key3]['rank_name'] = $value3['user_rank']?$rrr[$value3['user_rank']-1]['rank_name']:"普通会员";
            $user_list['user_list'][$key3]['isvip'] = $isvip;
            $user_list['user_list'][$key3]['ordernum'] = $order;
            $user_list['user_list'][$key3]['ordercount'] =  $not_comment ;
           // $user_list['user_list'][$key3]['userYeJi']  = $userYeJi ;


            $user_list['user_list'][$key3]['user_name'] =  substr($value3['user_name'], 0, 5).'****'.substr($value3['user_name'], 9) ;

            # code...
        }
         
     var_dump($user_list['user_list']);exit;
        return $user_list['user_list'];
        
    }


    
}
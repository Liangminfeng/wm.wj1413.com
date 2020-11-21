<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：UserController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch用护中心
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class UserController extends CommonController
{

    protected $user_id;

    protected $action;

    protected $back_act = '';
    private $size = 12;
    private $page = 1;


    /**
     * 构造函数
     */
    public function __construct()
    {

        parent::__construct();


        //$this->getCity();
        // 属性赋值
        $this->user_id = $_SESSION['user_id'];
        $this->cat_id = intval(I('get.id'));
        $this->action = ACTION_NAME;
        // 验证登录
        
        $this->check_login();
        

        // 用护信息
        $info = model('ClipsBase')->get_user_default($this->user_id);

                // 显示第三方API的头像
        if (isset($_SESSION['avatar'])) {
            $info['avatar'] = $_SESSION['avatar'];
        }
        
        // 如果是显示页面，对页面进行相应赋值
        assign_template();
        $this->assign('action', $this->action);
        $_SESSION['user_rank'] = $info['user_rank'];        //$this->assign('share_link', __URL__.$_SERVER['REQUEST_URI']);//
        // $this->assign('share_title',  C('shop_title'));//
        $this->assign('share_description', $this->formatDescription(C('shop_desc')));//
        $this->assign('share_pic', __URL__."/themes/yutui/images/new/yutui_logo.png");
        $this->assign('u',$this->user_id);
        $this->assign('info', $info);
    }

    /**
     * 会员中心欢迎页
     */
    public function index()
    {
       
       
        if($_SESSION['openid']&&$_SESSION['user_id']){
            /*更新用户的openid*/
               
                $data_up['openid'] = $_SESSION['openid'];
                $where_up['user_id'] = $_SESSION['user_id'];
                $this->model->table('users')
                    ->data($data_up)
                    ->where($where_up)
                    ->update();
        }

        $user_info = model('Users')->get_profile($this->user_id);
        $parent_info = model('Users')->get_profile($user_info['parent_id']);

        if(!$user_info['lang']){
            $this->get_lang();
        }
        

        // 用护等级
        // if ($rank = model('ClipsBase')->get_rank_info()) {
        //     $this->assign('rank_name', sprintf(L('your_level'), $rank['rank_name']));
        // }
        // 更新所有订单是否超时状态
        $update = model('ClipsBase')->update_allorder($this->user_id);
        // 待付款
        $not_pays = model('ClipsBase')->not_pay($this->user_id);
        
        // 待收货
        $not_shouhuos = model('ClipsBase')->not_shouhuo($this->user_id);
        // 红包
        $bonus = model('ClipsBase')->my_bonus($this->user_id);
        // 待评价
        $not_comment = model('ClipsBase')->not_pingjia($this->user_id);
        
        // 用护积分余额
        $user_pay = model('ClipsBase')->pay_money($this->user_id);
        $user_money = $user_pay['user_money']; // 余额
        $user_points = number_format($user_pay['pay_points'],2); // 积分
                                                // 获取未读取消息数量
        $msg_list = model('ClipsBase')->msg_lists($this->user_id);
        // 收藏数量
        $goods_num = model('ClipsBase')->num_collection_goods($this->user_id);
        
        $arr = array(
            'msg_list' => $msg_list,
            'goods_nums' => $goods_num,
            'not_pays' => $not_pays,
            'not_shouhuos' => $not_shouhuos,
            'not_comment' => $not_comment,
            'user_money' => $user_money,
            'user_points' => $user_points,
            'vcoin' => $user_pay['vcoin'],
            'fish_ticket' => $user_pay['fish_ticket'],
            'bonus' => $bonus
        );
        
        // 收藏
        $goods_list = model('ClipsBase')->get_collection_goods($this->user_id, 5, 0);
        // 评论
        $comment_list = model('ClipsBase')->get_comment_list($this->user_id, 5, 0);
        // 浏览记录
        $history = insert_history();
        // 信息中心是否有新回复
        $sql = 'SELECT msg_id FROM ' . $this->model->pre . 'feedback WHERE parent_id IN (SELECT f.msg_id FROM ' . $this->model->pre . 'feedback f LEFT JOIN ' . $this->model->pre . 'touch_feedback t ON f.msg_id = t.msg_id WHERE f.parent_id = 0 and f.user_id = ' . $this->user_id . ' and t.msg_read = 0 ORDER BY msg_time DESC) ORDER BY msg_time DESC';
        $rs = $this->model->query($sql);
        if ($rs) {
            $this->assign('new_msg', 1);
        }
        
        
        $this->assign('list', $arr);
        $userAccount = model('ClipsBase')->userAccount($_SESSION['user_id']);
        $balancemoney = $userAccount["balance"];
        $pay_points = $userAccount["pay_points"];
        $this->assign('balancemoney',$balancemoney);
        $this->assign('pay_points',$pay_points);
        $this->assign('lxpoint',$userAccount["lxpoint"]);
       if($_SESSION['user_vip']){

           $this->assign('share_title',  $user_info['nick_name']."的".C('shop_title'));
       
       }else{

            $this->assign('share_title',  C('shop_title'));
            
       }
       $vip_manage_account = model("Users")->gettmaccount($this->user_id);
       if(!$vip_manage_account){
        $vip_manage_account = $user_info['vip_manage_account'];
       }
        $this->assign('vip_manage_account',$vip_manage_account);
        $this->assign('parent_info',$parent_info);
        if($parent_info){
           $this->assign('parent_name',substr($parent_info['user_name'], 0, 2).'****'.substr($parent_info['user_name'], 6)); 
        }
         $userInfo = model("Users")->get_users($_SESSION["user_id"]);
        
        //客服系统信息
        $this->assign("current_url",urlencode($this->get_current_url()));
        $this->assign("mobile_phone",$userInfo['mobile_phone']);
        $real_name = $userInfo['real_name']?$userInfo['real_name']:$userInfo['user_name'];
        $user_name = $userInfo['user_name'];
        $vip_name = model("Users")->getUserVipName($userInfo['user_vip']);
        $crm = "姓名:".$real_name.";"."会员名:".$user_name.";"."会员等级:".$vip_name;
        //
        
        $info = model('ClipsBase')->get_user_default($user_info['user_id']);
        
        $shortLink = $this->getShortUrl(__URL__ . "/index.php?m=default&c=user&u=" . $_SESSION['user_id']);
        $this->assign('share_link', $shortLink); //
        $this->assign('info', $info);
        
        $this->assign("crm",urlencode($crm));
        $this->assign('parent_id',$user_info['parent_id']);
        $this->assign('share_description', $this->formatDescription(C('shop_desc')));//
        $this->assign('share_pic', __URL__."/themes/yutui/images/new/yutui_logo.png");//
        $this->assign('isbind', $bind);
        $this->assign('user_notice', C('user_notice'));
        $this->assign('goods_list', $goods_list);
        $this->assign('comment_list', $comment_list);
        $this->assign('history', $history);
        $this->assign('title', L('user_center'));
        //底部导航高亮
        $this->assign("footer_index",'user');
        
        $this->display('user_wm.dwt');
    }
    /*设置语言包*/
    public function ajaxSetLang()
    {
         
        $code = $_POST['code'];
       
        $r = read_static_cache('shop_config');
        $r['lang'] = $code;
        write_static_cache('shop_config',$r);

        $result['status'] = 200;
        $result['msg'] = "修改成功";

        die(json_encode($result));
        exit();

    }
     /*设置语言包*/
    public function setLang()
    {
         
        $lang = $_POST['code'];
       
        // $r = read_static_cache('shop_config');
        // $r['lang'] = $code;
        // write_static_cache('shop_config',$r);
        model("Users")->updateLang($this->user_id,$lang);
        $user_info = model('Users')->get_profile($this->user_id);
        $_SESSION['lang'] = $user_info['lang'];

        $result['status'] = 200;
        $result['msg'] = "修改成功";

        die(json_encode($result));
        exit();

    }

    /**
     * 获取用户真实 IP
     */
    public function getIP()
    {
        static $realip;
        if (isset($_SERVER)){
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $realip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")){
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }

        return $realip;
    }
    


    /**
     * 帐护中心
     */
    public function profile_new()
    {
        // 修改个人资料的处理
        if (IS_POST) {
            
            $email = I('post.email');
            $other['qq'] = $qq = I('post.extend_field2');
            $other['office_phone'] = $office_phone = I('post.extend_field3');
            $other['mobile_phone'] = $mobile_phone = I('post.extend_field5');
            $sel_question = I('post.sel_question');
            $passwd_answer = I('post.passwd_answer');
            
            // 读出所有扩展字段的id
            $where['type'] = 0;
            $where['display'] = 1;
            $fields_arr = $this->model->table('reg_fields')
                ->field('id')
                ->where($where)
                ->order('dis_order, id')
                ->select();
            if (empty($fields_arr)) {
                $fields_arr = array();
            }
            
            // 循环更新扩展用护信息
            foreach ($fields_arr as $val) {
                $extend_field_index = 'extend_field' . $val['id'];
                if (isset($_POST[$extend_field_index])) {
                    $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr(htmlspecialchars($_POST[$extend_field_index]), 0, 99) : htmlspecialchars($_POST[$extend_field_index]);
                    
                    $where_s['reg_field_id'] = $val['id'];
                    $where_s['user_id'] = $this->user_id;
                    $rs_s = $this->model->table('reg_extend_info')
                        ->where($where_s)
                        ->find();
                    
                    if ($rs_s) {
                        // 如果之前没有记录，则插入
                        $where_u['reg_field_id'] = $val['id'];
                        $where_u['user_id'] = $this->user_id;
                        $data_u['content'] = $temp_field_content;
                        $this->model->table('reg_extend_info')
                            ->data($data_u)
                            ->where($where_u)
                            ->update();
                    } else {
                        $data_i['user_id'] = $this->user_id;
                        $data_i['reg_field_id'] = $val['id'];
                        $data_i['content'] = $temp_field_content;
                        $this->model->table('reg_extend_info')
                            ->data($data_i)
                            ->insert();
                    }
                }
            }
            
            if (! empty($office_phone) && ! preg_match('/^[\d|\_|\-|\s]+$/', $office_phone)) {
                show_message(L('passport_js.office_phone_invalid'));
            }
            if (! is_email($email)) {
                show_message(L('msg_email_format'));
            }
            if (! empty($qq) && ! preg_match('/^\d+$/', $qq)) {
                show_message(L('passport_js.qq_invalid'));
            }
            if (! empty($mobile_phone) && ! preg_match('/^1[3|4|5|8|7][0-9]\d{4,8}$/', $mobile_phone)) {
                show_message(L('passport_js.mobile_phone_invalid'));
            }
            $data['mobile_phone'] = $mobile_phone;
            $res = $this->model->table('users')
                ->where($data)
                ->field('user_id')
                ->select();
            if (! empty($res)) {
                show_message(L('passport_js.mobile_phone_repeated'));
            }
            
            // 写入密码提示问题和答案
            if (! empty($passwd_answer) && ! empty($sel_question)) {
                $where_up['user_id'] = $this->user_id;
                $data_up['passwd_question'] = $sel_question;
                $data_up['passwd_answer'] = $passwd_answer;
                $this->model->table('users')
                    ->data($data_up)
                    ->where($where_up)
                    ->update();
            }
            
            $profile = array(
                'user_id' => $this->user_id,
                'email' => I('post.email'),
                'nick_name' => I('post.nick_name'),
                'sex' => I('post.sex', 0),
                'other' => isset($other) ? $other : array()
            );
            
            if (model('Users')->edit_profile($profile)) {
                
                show_message(L('edit_profile_success'), L('profile_lnk'), url('profile'), 'info');
            } else {
                if (self::$user->error == ERR_EMAIL_EXISTS) {
                    $msg = sprintf(L('email_exist'), $profile['email']);
                } else {
                    $msg = L('edit_profile_failed');
                }
                show_message($msg, '', '', 'info');
            }
            exit();
        }
        // 用护资料
        $user_info = model('Users')->get_profile($this->user_id);
        
        // 取出注册扩展字段
        $where = 'type < 2 and display = 1';
        $extend_info_list = $this->model->table('reg_fields')
            ->where($where)
            ->order('dis_order, id')
            ->select();
        
        $condition['user_id'] = $this->user_id;
        $extend_info_arr = $this->model->table('reg_extend_info')
            ->field('reg_field_id, content')
            ->where($condition)
            ->select();
        if (empty($extend_info_arr)) {
            $extend_info_arr = array();
        }
        
        $temp_arr = array();
        foreach ($extend_info_arr as $val) {
            $temp_arr[$val['reg_field_id']] = $val['content'];
        }
        
        foreach ($extend_info_list as $key => $val) {
            switch ($val['id']) {
                case 1:
                    unset($extend_info_list[$key]);
                    break;
                case 2:
                    $extend_info_list[$key]['content'] = $user_info['qq'];
                    break;
                case 3:
                    $extend_info_list[$key]['content'] = $user_info['office_phone'];
                    break;
                case 4:
                    unset($extend_info_list[$key]);
                    break;
                case 5:
                    $extend_info_list[$key]['content'] = $user_info['mobile_phone'];
                    break;
                default:
                    $extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']];
            }
        }
        // 增加是否是微信的判断
        if (is_wechat_browser() == false) {
            $this->assign('is_not_wechat', 1);
        }
        
        $this->assign('title', L('profile'));
        $this->assign('extend_info_list', $extend_info_list);
        // 密码提示问题
        $this->assign('passwd_questions', L('passwd_questions'));
        $u = $_GET['u'];
        
        $upload_url = __URL__ . "/index.php?m=default&c=user&a=uploadavatar&u=" . $u;
        
        $this->assign('upload_url', $upload_url);
        $this->assign('profile', $user_info);
        $this->assign("headerContent", L("my_profile"));
        $this->display('user_profile.dwt');
    }
    // 修改手机号码
    public function modifyphone()
    {
        if (IS_POST) {
          

        if(I('post.mobile'))
        {


            $sms_code = isset($_POST['sms_code']) ? $_POST['sms_code'] : '';
            $mobile_phone = isset($_POST['mobile']) ? $_POST['mobile'] : '';    
            
             if ($mobile_phone !== $_SESSION['sms_mobile']||empty($mobile_phone)) {
                
                    show_message(L('mobile_error'), L('profile_lnk'), url('profile'), 'error');

                }   
                
             if ($sms_code !== $_SESSION['sms_code']||empty($sms_code)) {
                
                    show_message(L('mobile_code_error'), L('profile_lnk'), url('profile'), 'error');

                }


            $profile = array(
             
                'mobile_phone' => I('post.mobile', 0),
                'mobile_zone' => $_SESSION['zone']
            );
           
            foreach ($profile as $key => $value) {
                
                $sql = "update ecs_users set " . "$key='" . $value . "' where user_id='" . $_SESSION[user_id] . "'";
                
                $rs = $this->model->query($sql);
                // code...
            }
        }
       
            
        }
        
        $user_info = model('Users')->get_profile($this->user_id);
   
        $u = $_GET['u'];
        
        $upload_url = __URL__ . "/index.php?m=default&c=user&a=uploadavatar&u=" . $u;
    
        $this->assign('title', L('profile'));
        $this->assign('upload_url', $upload_url);
        $this->assign('profile', $user_info);
        $this->assign("headerContent", L("my_profile"));
        $this->display('user_profile.dwt');

    }
    public function profile()
    {
           $user_info = model('Users')->get_profile($_SESSION['user_id']);
        if (IS_POST) {
            if(I('post.lang')){
          
            $lang = I('post.lang');
            model("Users")->updateLang($this->user_id,$lang);
     
        
        $_SESSION['lang'] = $user_info['lang'];
            $this->redirect('/index.php?m=default&c=user&a=profile&u=349');
            }
        if(I('post.nick_name'))
        {

            $profile = array(
                'user_id' => $this->user_id,
                
                'nick_name' => addEmoji(I('post.nick_name')),
                
                
            );
            foreach ($profile as $key => $value) {
                
                $sql = "update ecs_users set " . "$key='" . $value . "' where user_id='" . $_SESSION[user_id] . "'";
                
                $rs = $this->model->query($sql);
                // code...
            }
            
             $updatedata = array(
                                                "account" =>$user_info['vip_manage_account'],
                                                "nickname" =>addEmoji(I('post.nick_name'))
                                                
                                             );
             
                                    $ret1 = model("Index")->postData($updatedata,"/api/user/update");
                                  
        }
      
       
            $this->cleanUserCache($this->user_id);
        }
        
        $user_info = model('Users')->get_profile($this->user_id);
        $_SESSION['lang'] = $user_info['lang'];
      //  var_dump($user_info);exit;
        $u = $_GET['u'];
        
        $upload_url = __URL__ . "/index.php?m=default&c=user&a=uploadavatar&u=" . $u;
        $user_info["nick_name"] = getEmoji($user_info["nick_name"]);
        
        $this->assign('title', L('profile'));
        $this->assign('upload_url', $upload_url);
        $this->assign('profile', $user_info);
        $this->assign('fresh',1);
        $this->assign("headerContent", L("my_profile"));
        
        //底部导航高亮
        $this->assign("footer_index",'user');
        
        $this->display('user_profile.dwt');
    }

    /**
     * 资金管理
     */
    public function account_list()
    {
        // 获取剩余余额
        $surplus_amount = model('ClipsBase')->get_user_surplus($this->user_id);
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }
        $size = I('request.size', 8);
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $where = 'user_id = ' . $this->user_id;
        $count = $this->model->table('account_log')
            ->field('COUNT(*)')
            ->where($where)
            ->getOne();

        $this->pageLimit(url('user/account_list'), $size);
        $this->assign('pager', $this->pageShow($count));
        $account_detail = model('Users')->get_new_account_detail($this->user_id, $size, ($page - 1) * $size);

        $this->assign('headerContent', L('user_money_detail'));
        
        $this->assign('title', L('label_user_surplus'));
        $this->assign('surplus_amount', price_format($surplus_amount, false));
        $this->assign('account_log', $account_detail);
        $this->display('user_account_list.dwt');
    }
    public function ajax_account_list()
    {
        
        $size = I('request.size', 8);
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $where = 'user_id = ' . $this->user_id;
        $count = $this->model->table('account_log')
            ->field('COUNT(*)')
            ->where($where)
            ->getOne();
        
        $account_detail = model('Users')->get_new_account_detail($this->user_id, $size, ($page - 1) * $size);
        $countpage = ceil($count/$size)+1;
        $result['totalpage'] = $countpage;
        $result['list'] = $account_detail;
        $result['page'] = $page;
        die(json_encode($result));
        exit();
   
    }

    public function account_detail()
    {
        // 获取剩余余额
        $surplus_amount = model('ClipsBase')->get_user_surplus($this->user_id);
        $frozen_amount = model('ClipsBase')->get_user_frozen($this->user_id);
       
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }
        if (empty($frozen_amount)) {
            $frozen_amount = 0;
        }
        $size = I(C('page_size'), 5);
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $where = 'user_id = ' . $this->user_id . ' AND user_money <> 0';
        $count = $this->model->table('account_log')
            ->field('COUNT(*)')
            ->where($where)
            ->getOne();
        $this->pageLimit(url('user/account_detail'), $size);
        $this->assign('pager', $this->pageShow($count));
        
        $account_detail = model('Users')->get_account_detail($this->user_id, $size, ($page - 1) * $size);
        
        $this->assign('headerContent', L('user_money_manage'));
        
        $this->assign('title', L('label_user_surplus'));
        $total_surplus = $surplus_amount + $frozen_amount;
        
        $total_surplus = str_replace("元", "", price_format($total_surplus, false));
        $total_surplus = str_replace("NT", "<span class='f-02'>NT</span>", $total_surplus);
        
        $surplus_amount = str_replace("元", "", price_format($surplus_amount, false));
        $surplus_amount = str_replace("NT", "<span class='f-02'>NT</span>", $surplus_amount);
        $frozen_amount = str_replace("元", "", price_format($frozen_amount, false));
        $frozen_amount = str_replace("NT", "<span class='f-02'>NT</span>", $frozen_amount);
       
        $this->assign('surplus_amount', $surplus_amount);
        $this->assign('frozen_amount', $frozen_amount);
        $this->assign('total_surplus', $total_surplus);
        
        $this->assign('account_log', $account_detail);
        $this->display('user_account_detail.dwt');
    }

    /**
     * 会员充值和提现申请记录
     */
    public function account_log()
    {

        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        
        /* 获取记录条数 */
        $record_count = $this->model->table('user_account')
            ->field('COUNT(*)')
            ->where("user_id = $this->user_id AND process_type " . db_create_in(array(
            SURPLUS_SAVE,
            SURPLUS_RETURN
        )))
            ->getOne();
        
        // 分页函数
        $pager = get_pager('index.php', array(
            'c' => 'user',
            'a' => 'account_log'
        ), $record_count, $page);
        
        // 获取剩余余额
        $surplus_amount = model('ClipsBase')->get_user_surplus($this->user_id);
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }
        
        // 获取余额记录
        $account_log = model('ClipsBase')->get_account_log($this->user_id, $pager['size'], $pager['start']);
        
        $this->assign('headerContent', L('user_money_log'));
        
        // 模板赋值
        $this->assign('title', L('label_user_surplus'));
        $this->assign('surplus_amount', price_format($surplus_amount, false));
        $this->assign('account_log', $account_log);
        $this->assign('pager', $pager);
        
        $this->display('user_account_log.dwt');
    }

    /**
     * 删除会员余额
     */
    public function cancel()
    {
        $id = I('get.id', 0);
        if ($id == 0 || $this->user_id == 0) {
            ecs_header("Location: " . url('User/account_log'));
            exit();
        }
        
        $result = model('ClipsBase')->del_user_account($id, $this->user_id);
        ecs_header("Location: " . url('User/account_log'));
    }

    /**
     * 会员提现申请界面
     */
    public function account_raply()
    {
        // 获取剩余余额
        $user = model("Users")->find([
            "user_id" => $this->user_id
        ], "autonym,user_money,mobile_phone,real_name,ID_card,bank,bank_card,autonym_remark,autonym_submit_time,autonym_audit_time");
        $autonym = $user['autonym'];
        

        $surplus_amount = model('ClipsBase')->get_user_surplus($this->user_id);
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }
        $data = array(
                    
                        "mid" =>$_SESSION['user_id'],
                        "token" =>encryptapitoken()
                    );
        $ret =  json_decode(Http::doPost(API_URL."/api/user/info",$data,5,"Content-Type: application/json",'json'))   ;
    
        $this->assign('user_bonusmoney',$ret->data->money);
        $this->assign('headerContent', L('withdraw'));
        $this->assign('user', $user);
        $this->assign('surplus_amount', price_format($surplus_amount, false));
        $this->assign('title', L('label_user_surplus'));
        $this->display('user_account_raply.dwt');
    }

    /**
     * 会员预付款界面
     */
    public function account_deposit()
    {
        $this->assign('title', L('label_user_surplus'));
        $surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $account = model('ClipsBase')->get_surplus_info($surplus_id);
        
        $this->assign('payment', model('ClipsBase')->get_online_payment_list(false));
        $this->assign('order', $account);
        $this->display('user_account_deposit.dwt');
    }
    public function bonus_withdraw()
    {
        # code...
        $user = model("Users")->find([
            "user_id" => $this->user_id
        ], "autonym,mobile_phone,real_name,ID_card,bank,bank_card,autonym_remark,autonym_submit_time,autonym_audit_time");
        $account = model("ClipsBase")->userAccount($this->user_id);
        
        $cardlist = model("ClipsBase")->get_banklist($_SESSION['user_id']);
        $autonym = $user['autonym'] ;
        switch ($autonym) {
            case '1':
                // code...
                show_message('实名认证审核中', L('back_up_page'), url('my_bonus_center'), 'info');
                break;
            case '2':
                // code...
                show_message('实名认证审核未通过,请先实名认证', [
                    L('back_up_page'),
                    L('goto_autonym_audit')
                ], [
                    url('my_bonus_center'),
                    url('autonym')
                ], 'info');
                
                break;
            case '3':
                
                
                break;
            default:
                // code...
                show_message('请先实名认证', [
                    L('back_up_page'),
                    L('goto_autonym_audit')
                ], [
                    url('my_bonus_center'),
                    url('autonym')
                ], 'info');
                break;
        }
        $this->assign('cansmall_change',$account['small_change']);
        $this->assign('default_bank',$cardlist[0]);
        $this->assign('default_bank_no',substr($cardlist[0]['bank_no'], "-4"));
        $this->assign("cardlist",$cardlist);
        $this->assign('user_bonusmoney',$ret->data->money);
        $this->assign('headerContent', L('withdraw'));
        $this->assign('user', $user);
        $this->assign('surplus_amount', price_format($surplus_amount, false));
        $this->assign('title', L('label_user_surplus'));
        $this->assign('bank_card',substr($user['bank_card'], '-4'));
        $this->display('user_bonus_withdraw.dwt');
    }
    /*零钱提现的执行动作*/
    public function act_account_bonus()
    {
        
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $bank_id = isset($_POST['bank_id']) ? floatval($_POST['bank_id']) : 0;
        $bankinfo = model("ClipsBase")->getBankCardInfo($bank_id);


        $usersmall_change = model('ClipsBase')->finduseraccount($_SESSION['user_id'],"small_change");

        if($amount>$usersmall_change||$amount<=0){
            
             $this->jserror("金额错误");
             
        }


        $surplus = array(
            'user_id' => $this->user_id,
            'rec_id' => ! empty($_POST['rec_id']) ? intval($_POST['rec_id']) : 0,
            'process_type' => 1,
            'payment_id' => isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0,
            'user_note' => isset($_POST['user_note']) ? trim($_POST['user_note']) : '',
            'amount' => $amount,
            'bank_card' => isset($bankinfo['bank_no']) ? trim($bankinfo['bank_no']) : '',
            
            'bank_realname' => isset($bankinfo['bank_realname']) ? trim($bankinfo['bank_realname']) : '',
            'bank_name' => isset($bankinfo['bank_name']) ? trim($bankinfo['bank_name']) : '',
            'bank_sub_name' => isset($bankinfo['bank_sub_name']) ? trim($bankinfo['bank_sub_name']) : '',
        );

         /*先把申请提现的钱放在新制度的冻结字段，等管理员后台审核再修改*/
         model('ClipsBase')->new_log_account_change($_SESSION['user_id'], -$amount,L('bonus_apply'), ACT_DRAWING, 11);
         //model('ClipsBase')->log_bonus_change($_SESSION['user_id'], $amount,L('bonus_apply'), ACT_DRAWING,11);

         /*不操作users表的冻结字段，因为现在冻结是放在新制度*/
         model('ClipsBase')->insert_user_account($surplus, $amount);
              $this->redirect(url('bonus_out_succeed', array('money'=>number_format($amount,2))));
     
         
    }
    public function bonus_out_succeed()
    {

        $money = $_GET['money'];
        $this->assign('money',$money);
        $this->display('bonus_out_succeed.dwt');
    }
    public function my_bonus_center()
    {


       /*可提现金额收益表*/

       $res = model("ClipsBase")->userAccount($_SESSION['user_id']);

       $tixianzhong = model("ClipsBase")->findfrozemoney($_SESSION['user_id']);
       $yitixian = model("ClipsBase")->findwithdmoney($_SESSION['user_id']);
    
       $this->assign('achievement_retail',round($res['bonus_distributor'],2));
       $this->assign("tixianzhong",$tixianzhong);
       $this->assign("yitixian",$yitixian);
       $this->assign("train_bonus",round($res['train_money'],2));
       $this->assign('achievement_vip',$res['bonus_vip']+$res['bonus_retail']);
       $this->assign('totalcanwithdraw',$res['small_change']);
       $this->display('bonuscenter.dwt');
    }
    public function ajax_my_bonus()
    {
     
       // $data1 = array(
                    
       //                  "mid" =>$_SESSION['user_id'],
       //                  "type"=>isset($_GET['type'])?$_GET['type']:1,
       //                  "num"=>5,
       //                  "page"=>$_GET['page'],
       //                  "token" =>encryptapitoken()
       //              );
       
   
       // $ret1 =  post_log($data1,API_URL."/api/account/bonus",5);

       // $data2 = array(
                    
       //                  "mid" =>$_SESSION['user_id'],
                    
       //                  "token" =>encryptapitoken()
       //              );
       
     
       // $ret2 =  post_log($data2,API_URL."/api/user/info",5);
      
        if(!empty($ret1['data'])){
           $html = "";
            foreach ($ret1['data'] as $key => $value) {
           # code...

            $cash = number_format($value['bonus']*0.8,2);
            $kd=  number_format($value['bonus']*0.2,2);
            $bonus = "￥".$cash."+".$kd."KD豆";
            $html .= "<li>";
            $html .="<p>".$value['created_at']."</p>";
            $html .="<p>".$bonus."</p>";
            
                switch ($value['status']) {
                    case '0':
                        # code...
                        $html .="<p>".L("pending_hand_out")."</p>";
                        break;
                   case '1':
                        # code...
                        $html .="<p>".L("already_hand_out")."</p>";
                        break; 
                    case '2':
                        # code...
                        $html .="<p>".L("no_qualification")."</p>";
                        break;

                    default:
                        # code...
                        break;
                }
                
                $html .="</li>";
            

           }
            $num = count($ret1['data']);
        }else{
            $num = 0;
        }
       
        if($_GET['type']==4){
            /*家族奖*/

            $tip .= '';
            $tip .='<div class="capping"><div class="capping_flex">';
            $tip .='<p >'.L('current_weight').'</p> <p>';
            $tip .='<span >'.$ret2['data']['weight'].'</span></p></div></div>';

            $tip .='<div class="capping" ><div class="capping_flex">';
            $tip .='<p >'.L('top_amount').'</p> <p >';
            $tip .='<span >'.$ret2['data']['total_jz'].'</span>/<span>'.$ret2['data']['total_join'].'</span></p></div></div>';
            
        }
        if($_GET['type']==6){
            /*共享奖*/
            $tip .= '';
             $tip .='<div class="capping"><div class="capping_flex">';
            $tip .='<p >'.L('current_weight').'</p> <p>';
            $tip .='<span >'.$ret2['data']['weight'].'</span></p></div></div>';
            $tip .='<div  ><div class="capping_flex">';
            if($ret2['data']['get_share_limit']=='-1'){
                $tip .='<p >'.L('top_amount').'</p> <p>';
            $tip .='<span>'.$ret2['data']['get_share'].'</span>/<span>'.L('no_top').'</span></p></div></div>';
            }else{
                $tip .='<p >'.L('top_amount').'</p> <p>';
            $tip .='<span>'.$ret2['data']['get_share'].'</span>/<span>'.$ret2['data']['get_share_limit'].'</span></p></div></div>';
            }
            


        }
       
       if($html){
            $result['status'] = 200;
            $result['data'] = $html;
            $result['tip'] = $tip?$tip:0;
            $result['num'] = $num;
            $result['type'] = $_GET['type'];
       }else{
            $result['status'] = 303;
            $result['data'] = $html;
            $result['tip'] = $tip?$tip:0;
            $result['num'] = $num;
            $result['type'] = $_GET['type'];
            $result['data'] = L('nodata');
       }
       
       echo json_encode($result);
            exit();
    }

    /**
     * 对会员余额申请的处理
     */
    public function act_account()
    {
     $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $data = array(
                    
                        "mid" =>$_SESSION['user_id'],
                        "token" =>encryptapitoken()
                    );
        $ret =  json_decode(Http::doPost(API_URL."/api/user/info",$data,5,"Content-Type: application/json",'json'))   ;
        $data1 = array(
                    
                        "mid" =>$_SESSION['user_id'],
                        "money"=>$amount,
                        "remark"=>L('withdraw'),
                        "type"=>7,
                        "token" =>encryptapitoken()
                    );

        $ret1 =  json_decode(Http::doPost(API_URL."/api/account/get",$data1,5,"Content-Type: application/json",'json'))   ;
  
        if($_POST['amount']>$ret->data->money){
          $content = L('surplus_amount_error');
                show_message($content, L('back_page_up'), '', 'info');
        }
        
        if ($amount <= 0) {
            show_message(L('select_amount'));
        }
       
        /* 变量初始化 */
        $surplus = array(
            'user_id' => $this->user_id,
            'rec_id' => ! empty($_POST['rec_id']) ? intval($_POST['rec_id']) : 0,
            'process_type' => 1,
            'payment_id' => isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0,
            'user_note' => isset($_POST['user_note']) ? trim($_POST['user_note']) : '',
            'amount' => $amount,
            'bank_card' => isset($_POST['bank_card']) ? trim($_POST['bank_card']) : ''
        );
        
        /* 退款申请的处理 */
        if ($surplus['process_type'] == 1) {
            /* 判断是否有足够的余额的进行退款的操作 */
            $sur_amount = model('ClipsBase')->get_user_surplus($this->user_id);
            if ($amount > $sur_amount) {
                $content = L('surplus_amount_error');
                show_message($content, L('back_page_up'), '', 'info');
            }
            
            // 插入会员帐目明细
            $amount =  $amount;
            $surplus['payment'] = '';
            
            $surplus['rec_id'] = model('ClipsBase')->insert_user_account($surplus, -$amount);
            //会员申请提现的时候先扣除余额，再放在冻结金额那边
             
            // model('ClipsBase')->log_account_change($_SESSION['user_id'], $amount, -$amount, 0, 0, L('surplus_type_1'), ACT_DRAWING);
             //model('ClipsBase')->new_log_account_change($_SESSION['user_id'], $amount,L('surplus_type_1'),ACT_DRAWING,1);
             //model('ClipsBase')->new_log_account_change($_SESSION['user_id'], -$amount,L('surplus_type_1'),ACT_DRAWING,9);
            /* 如果成功提交 */
            if ($surplus['rec_id'] > 0) {
                $content = L('surplus_appl_submit');
                show_message($content, L('back_account_log'), url('User/account_log'), 'info');
            } else {
                $content = $L('process_false');
                show_message($content, L('back_page_up'), '', 'info');
            }
        }        /* 如果是会员预付款，跳转到下一步，进行线上支付的操作 */
        else {
         
            if ($surplus['payment_id'] <= 0) {
                show_message(L('select_payment_pls'));
            }
            
            // 获取支付方式名称
            $payment_info = array();
            $payment_info = model('Order')->payment_info($surplus['payment_id']);
            $surplus['payment'] = $payment_info['pay_name'];
            
            if ($surplus['rec_id'] > 0) {
                // 更新会员帐目明细
                $surplus['rec_id'] = model('ClipsBase')->update_user_account($surplus);
            } else {
                // 插入会员帐目明细
                $surplus['rec_id'] = model('ClipsBase')->insert_user_account($surplus, $amount);
            }
            
            // 取得支付信息，生成支付代码
            $payment = unserialize_config($payment_info['pay_config']);
            
            // 生成伪订单号, 不足的时候补0
            $order = array();
            $order['order_sn'] = $surplus['rec_id'];
            $order['user_name'] = $_SESSION['user_name'];
            $order['surplus_amount'] = $amount;
            
            // 计算支付手续费用
            $payment_info['pay_fee'] = pay_fee($surplus['payment_id'], $order['surplus_amount'], 0);
            
            // 计算此次预付款需要支付的总金额
            $order['order_amount'] = $amount + $payment_info['pay_fee'];
            
            // 记录支付log
            $order['log_id'] = model('ClipsBase')->insert_pay_log($surplus['rec_id'], $order['order_amount'], $type = PAY_SURPLUS, 0);
            
            /* 调用相应的支付方式文件 */
            include_once (ROOT_PATH . 'plugins/payment/' . $payment_info['pay_code'] . '.php');
            
            /* 取得在线支付方式的支付按钮 */
            $pay_obj = new $payment_info['pay_code']();
            $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);
            
            /* 模板赋值 */
            $this->assign('title', L('label_act_account'));
            $this->assign('payment', $payment_info);
            $this->assign('pay_fee', price_format($payment_info['pay_fee'], false));
            $this->assign('amount', price_format($amount, false));
            $this->assign('order', $order);
            $this->display('user_act_account.dwt');
        }
    }

    /**
     * 会员通过帐目明细列表进行再付款的操作
     */
    public function pay()
    {
        // 变量初始化
        $surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $payment_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
        
        if ($surplus_id == 0) {
            ecs_header("Location: " . url('User/account_log'));
            exit();
        }
        
        // 如果原来的支付方式已禁用或者已删除, 重新选择支付方式
        if ($payment_id == 0) {
            ecs_header("Location: " . url('User/account_deposit', array(
                'id' => $surplus_id
            )));
            exit();
        }
        
        // 获取单条会员帐目信息
        $order = array();
        $order = model('ClipsBase')->get_surplus_info($surplus_id);
        
        // 支付方式的信息
        $payment_info = array();
        $payment_info = model('Order')->payment_info($payment_id);
        
        /* 如果当前支付方式没有被禁用，进行支付的操作 */
        if (! empty($payment_info)) {
            // 取得支付信息，生成支付代码
            $payment = unserialize_config($payment_info['pay_config']);
            
            // 生成伪订单号
            $order['order_sn'] = $surplus_id;
            
            // 获取需要支付的log_id
            $order['log_id'] = model('ClipsBase')->get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS);
            
            $order['user_name'] = $_SESSION['user_name'];
            $order['surplus_amount'] = $order['amount'];
            
            // 计算支付手续费用
            $payment_info['pay_fee'] = pay_fee($payment_id, $order['surplus_amount'], 0);
            
            // 计算此次预付款需要支付的总金额
            $order['order_amount'] = $order['surplus_amount'] + $payment_info['pay_fee'];
            
            // 如果支付费用改变了，也要相应的更改pay_log表的order_amount
            $order_amount = M()->getOne("SELECT order_amount FROM " . M()->pre . 'pay_log' . " WHERE log_id = '$order[log_id]'");
            $this->model->table('order_goods')
                ->field('COUNT(*)')
                ->where("order_id = '$order[order_id]' " . " AND is_real = 1")
                ->getOne();
            if ($order_amount != $order['order_amount']) {
                M()->query("UPDATE " . M()->pre . "pay_log SET order_amount = '$order[order_amount]' WHERE log_id = '$order[log_id]'");
            }
            
            /* 调用相应的支付方式文件 */
            include_once (ROOT_PATH . 'plugins/payment/' . $payment_info['pay_code'] . '.php');
            /* 取得在线支付方式的支付按钮 */
            $pay_obj = new $payment_info['pay_code']();
            $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);
            
            /* 模板赋值 */
            $this->assign('title', L('label_user_surplus'));
            $this->assign('payment', $payment_info);
            $this->assign('order', $order);
            $this->assign('pay_fee', price_format($payment_info['pay_fee'], false));
            $this->assign('amount', price_format($order['surplus_amount'], false));
            $this->display('user_act_account.dwt');
        }        /* 重新选择支付方式 */
        else {
            $this->assign('title', L('label_user_surplus'));
            $this->assign('payment', model('ClipsBase')->get_online_payment_list());
            $this->assign('order', $order);
            $this->display('user_account_deposit.dwt');
        }
    }

    /**
     * 获取未付款订单
     */
    public function not_pay_order_list()
    {
        // header("Cache-Control:no-cache,must-revalidate,no-store");
        // header("Pragma:no-cache");
        // header("Expires:-1");
        $update = model('ClipsBase')->update_allorder($this->user_id);
        $this->user_id = $_SESSION['user_id'];
        $pay = 0;
        
        $size = I(C('page_size'), 10);
        
        $count = $this->model->table('order_info')
            ->where('user_id = ' . $this->user_id)
            ->count();
        
        $filter['page'] = '{page}';
        
        $offset = $this->pageLimit(url('not_pay_order_list', $filter), $size);
        
        $offset_page = explode(',', $offset);
       
        $orders = model('Users')->get_user_orders($this->user_id, $pay, $offset_page[1], $offset_page[0]);
        
        $this->assign('pay', $pay);
        
        foreach ($orders as $key => $value) {
            $order = model('Users')->get_order_detail_new($value['order_id'], $this->user_id);
            if ($order['order_status'] == OS_UNCONFIRMED) {
                
                $orders[$key]['handler'] = "<a class=\"Zbtn ZsubD\" href=\"" . url('user/cancel_order', array(
                    
                    'order_id' => $value['order_id']
                
                )) . "\" onclick=\"if (!confirm('" . L('confirm_cancel') . "')) return false;\">" . L('cancel_order_detail') . "</a>" . "<a class=\"Zbtn ZsubB\" href=\"" . url('user/order_detail', array(
                    
                    'order_id' => $value['order_id']
                
                )) . "\"" . " >".L('pay_atonce')."</a>";
            } 
            elseif ($order['order_status'] == OS_SPLITED) {
                
                /* 对配送状态的处理 */
                
                if ($order['shipping_status'] == SS_SHIPPED) {
                    
                    @$orders[$key]['handler'] = "<a class=\"Zbtn ZsubA\" href=\"" . url('user/affirm_received', array(
                        
                        'order_id' => $value['order_id']
                    
                    )) . "\" onclick=\"if (!confirm('" . L('confirm_received') . "')) return false;\">" . L('received') . "</a>";
                } elseif ($order['shipping_status'] == SS_RECEIVED) {
                    
                    @$orders[$key]['handler'] = '<a class="btn btn-info ect-colorf ect-bgZ" type="button" href="javascript:void(0);">' . L('ss_received') . '</a>';
                } else {
                    
                    if ($order['pay_status'] == PS_UNPAYED) {
                        
                        @$orders[$key]['handler'] = "<a class=\"Zbtn ZsubA\" href=\"" . url('user/cancel_order', array(
                            
                            'order_id' => $value['order_id']
                        
                        )) . "\">" . L('pay_money') . "</a>";
                    } else {
                        
                        // @$order['handler'] = "<a class=\"btn btn-info ect-colorf\" href=\"javascript:void(0);\">" . L('view_order') . "</a>";
                    }
                }
            } else {
                
                $order['handler'] = '<a class="Zbtn ZsubA" type="button" href="javascript:void(0);">' . L('os.' . $order['order_status']) . '</a>';
            }
            // code...
        }
        
        $this->assign('share_description', $this->formatDescription(C('shop_desc')));//
         $this->assign('share_pic', __URL__."/themes/yutui/images/new/yutui_logo.png");
        $this->assign('title', L('not_pay_list'));
        $this->assign('current', "not_pay_order_list");
        $this->assign('pager', $this->pageShow($count));
        $this->assign('pay',$pay);
        $this->assign('orders_list', $orders);
        $this->assign('type',2);
        $this->display('user_order_list.dwt');
    }

    /**
     * 获取全部订单
     */
    public function order_list()
    {
        header("Cache-Control:no-cache,must-revalidate,no-store");
        header("Pragma:no-cache");
        header("Expires:-1");
        $update = model('ClipsBase')->update_allorder($this->user_id);
        $this->user_id = $_SESSION['user_id'];
        
        $pay = 1;
        
        $size = I(C('page_size'), 10);
        
        $count = $this->model->table('order_info')
            ->where('user_id = ' . $this->user_id)
            ->count();
        
        $filter['page'] = '{page}';
        
        $offset = $this->pageLimit(url('order_list', $filter), $size);
        
        $offset_page = explode(',', $offset);
        
        $orders = model('Users')->get_user_orders($this->user_id, $pay, $offset_page[1], $offset_page[0]);
        // 循环订单列表,把超过一个小时未付款的订单设置为取消状态
        
        foreach ($orders as $key => $value) {
            $order = model('Users')->get_order_detail_new($value['order_id'], $this->user_id);
            
            if ($order['order_status'] == OS_UNCONFIRMED) {
                
                $orders[$key]['handler'] = "<a class=\"Zbtn ZsubD\" href=\"" . url('user/cancel_order', array(
                    
                    'order_id' => $value['order_id']
                
                )) . "\" onclick=\"if (!confirm('" . L('confirm_cancel') . "')) return false;\">" . L('cancel_order_detail') . "</a>" . "<a class=\"Zbtn ZsubB\" href=\"" . url('user/order_detail', array(
                    
                    'order_id' => $value['order_id']
                
                )) . "\"" . " >".L('pay_atonce')."</a>";
            } 
            elseif ($order['order_status'] == OS_SPLITED) {
                
                /* 对配送状态的处理 */
                
                if ($order['shipping_status'] == SS_SHIPPED) {
                    
                    @$orders[$key]['handler'] = "<a class=\"Zbtn ZsubB\" href=\"" . url('user/affirm_received', array(
                        
                        'order_id' => $value['order_id']
                    
                    )) . "\" onclick=\"if (!confirm('" . L('confirm_received') . "')) return false;\">" . L('received') . "</a>";
                } elseif ($order['shipping_status'] == SS_RECEIVED) {
                    
                    @$orders[$key]['handler'] = '<span class="ZsubE" ></span>';
                } else {
                    
                    if ($order['pay_status'] == PS_UNPAYED) {
                        
                        @$orders[$key]['handler'] = "<a class=\"Zbtn ZsubA\" href=\"" . url('user/cancel_order', array(
                            
                            'order_id' => $value['order_id']
                        
                        )) . "\">" . L('pay_money') . "</a>";
                    } else {
                        
                        // @$order['handler'] = "<a class=\"btn btn-info ect-colorf\" href=\"javascript:void(0);\">" . L('view_order') . "</a>";
                    }
                }
            } else {
                
                if ($order['order_status'] == "1" && $order['pay_status'] == "2") {
                    
                    $order['handler'] = '<a class="Zbtn ZsubC" type="button" href="javascript:void(0);">' . L('pending_send_out')  . '</a>';
                } else {
                    $order['handler'] = '<a class="Zbtn ZsubC" type="button" href="javascript:void(0);">' . L('os.' . $order['order_status']) . "1" . '</a>';
                }
            }
            
            // code...
        }
        // 订单 支付 配送 状态语言项
        
        // var_dump($order['handler']);
        $this->assign('pay', $pay);
        
        $this->assign('title', L('order_list_lnk'));
        
        $this->assign('pager', $this->pageShow($count));
   
        $this->assign('orders_list', $orders);
        $this->assign('type',1);
        $this->display('user_order_list.dwt');
    }

    public function ajax_order_list(){
       
        $page = $_POST['page'];
        $type= $_POST['type'];
        $pay= $_POST['pay'];
        $size = 6;
  
        switch ($type) {
            case '1':
                # 全部订单
                $count = $this->model->table('order_info')
            ->where('user_id = ' . $this->user_id)
            ->count();
        
            

            $orders = model('Users')->get_user_orders($this->user_id, $pay, $size, ($_POST['page']-1)*$size);
            // 循环订单列表,把超过一个小时未付款的订单设置为取消状态
            
            foreach ($orders as $key => $value) {
                $order = model('Users')->get_order_detail_new($value['order_id'], $this->user_id);
                
                if ($order['order_status'] == OS_UNCONFIRMED) {
                    
                    $orders[$key]['handler'] = "<a class=\"Zbtn ZsubD\" href=\"" . url('user/cancel_order', array(
                        
                        'order_id' => $value['order_id']
                    
                    )) . "\" onclick=\"if (!confirm('" . L('confirm_cancel') . "')) return false;\">" . L('cancel_order_detail') . "</a>" . "<a class=\"Zbtn ZsubB\" href=\"" . url('user/order_detail', array(
                        
                        'order_id' => $value['order_id']
                    
                    )) . "\"" . " >".L('pay_atonce')."</a>";
                } 
                elseif ($order['order_status'] == OS_SPLITED) {
                    
                    /* 对配送状态的处理 */
                    
                    if ($order['shipping_status'] == SS_SHIPPED) {
                        
                        @$orders[$key]['handler'] = "<a class=\"Zbtn ZsubB\" href=\"" . url('user/affirm_received', array(
                            
                            'order_id' => $value['order_id']
                        
                        )) . "\" onclick=\"if (!confirm('" . L('confirm_received') . "')) return false;\">" . L('received') . "</a>";
                    } elseif ($order['shipping_status'] == SS_RECEIVED) {
                        
                        @$orders[$key]['handler'] = '<span class="ZsubE" ></span>';
                    } else {
                        
                        if ($order['pay_status'] == PS_UNPAYED) {
                            
                            @$orders[$key]['handler'] = "<a class=\"Zbtn ZsubA\" href=\"" . url('user/cancel_order', array(
                                
                                'order_id' => $value['order_id']
                            
                            )) . "\">" . L('pay_money') . "</a>";
                        } else {
                            
                            // @$order['handler'] = "<a class=\"btn btn-info ect-colorf\" href=\"javascript:void(0);\">" . L('view_order') . "</a>";
                        }
                    }
                } else {
                    
                    if ($order['order_status'] == "1" && $order['pay_status'] == "2") {
                        
                        $order['handler'] = '<a class="Zbtn ZsubC" type="button" href="javascript:void(0);">' . L('pending_send_out') . '</a>';
                    } else {
                        $order['handler'] = '<a class="Zbtn ZsubC" type="button" href="javascript:void(0);">' . L('os.' . $order['order_status']) . "1" . '</a>';
                    }
                }
                
                // code...
            }
                break;
            case '2':
                # 待付款
      
        
                $count = $this->model->table('order_info')
                    ->where('user_id = ' . $this->user_id)
                    ->count();
                $orders = model('Users')->get_user_orders($this->user_id, $pay, $size, ($_POST['page']-1)*$size);
                foreach ($orders as $key => $value) {
                    $order = model('Users')->get_order_detail_new($value['order_id'], $this->user_id);
                    if ($order['order_status'] == OS_UNCONFIRMED) {
                        
                        $orders[$key]['handler'] = "<a class=\"Zbtn ZsubD\" href=\"" . url('user/cancel_order', array(
                            
                            'order_id' => $value['order_id']
                        
                        )) . "\" onclick=\"if (!confirm('" . L('confirm_cancel') . "')) return false;\">" . L('cancel_order_detail') . "</a>" . "<a class=\"Zbtn ZsubB\" href=\"" . url('user/order_detail', array(
                            
                            'order_id' => $value['order_id']
                        
                        )) . "\"" . " >".L('pay_atonce')."</a>";
                    } 
                    elseif ($order['order_status'] == OS_SPLITED) {
                        
                        /* 对配送状态的处理 */
                        
                        if ($order['shipping_status'] == SS_SHIPPED) {
                            
                            @$orders[$key]['handler'] = "<a class=\"Zbtn ZsubA\" href=\"" . url('user/affirm_received', array(
                                
                                'order_id' => $value['order_id']
                            
                            )) . "\" onclick=\"if (!confirm('" . L('confirm_received') . "')) return false;\">" . L('received') . "</a>";
                        } elseif ($order['shipping_status'] == SS_RECEIVED) {
                            
                            @$orders[$key]['handler'] = '<a class="btn btn-info ect-colorf ect-bgZ" type="button" href="javascript:void(0);">' . L('ss_received') . '</a>';
                        } else {
                            
                            if ($order['pay_status'] == PS_UNPAYED) {
                                
                                @$orders[$key]['handler'] = "<a class=\"Zbtn ZsubA\" href=\"" . url('user/cancel_order', array(
                                    
                                    'order_id' => $value['order_id']
                                
                                )) . "\">" . L('pay_money') . "</a>";
                            } else {
                                
                                // @$order['handler'] = "<a class=\"btn btn-info ect-colorf\" href=\"javascript:void(0);\">" . L('view_order') . "</a>";
                            }
                        }
                    } else {
                        
                        $order['handler'] = '<a class="Zbtn ZsubA" type="button" href="javascript:void(0);">' . L('os.' . $order['order_status']) . '</a>';
                    }
                    // code...
                }
                
                break;
            case '3':
                # 待收货
                $where = ' pay_status =2 and shipping_status not IN(2) and user_id=' . $this->user_id;
                $count = $this->model->table('order_info')
                    ->where($where)
                    ->count();
                $orders = model('Users')->not_shouhuo_orders($this->user_id, $pay, $size, ($_POST['page']-1)*$size);
                break;
            default:
                # code...
                break;
        }
       
        $countpage = ceil($count/$size)+1;
        $result['totalpage'] = $countpage;
        $result['size'] = $size;
        $result['list'] = $orders;
        $result['page'] = $_POST['page'];
        die(json_encode($result));
        exit();

        
    }

    /**
     * 获取待收货订单
     */
    public function not_shoushuo()
    {
        header("Cache-Control:no-cache,must-revalidate,no-store");
        header("Pragma:no-cache");
        header("Expires:-1");
        $this->user_id = $_SESSION['user_id'];
        $user_id = $this->user_id;
        
        $where = ' pay_status =2 and shipping_status not IN(2) and user_id=' . $user_id;
        $pay = 1;
        
        $size = I(C('page_size'), 10);
        
        $count = $this->model->table('order_info')
            ->where($where)
            ->count();
        
        $filter['page'] = '{page}';
        
        $offset = $this->pageLimit(url('not_shoushuo', $filter), $size);
        
        $offset_page = explode(',', $offset);
        
        $orders = model('Users')->not_shouhuo_orders($this->user_id, $pay, $offset_page[1], $offset_page[0]);
        
       
        
        $this->assign('pay', $pay);
        
        $this->assign('title', L('unreceived_order'));
        
        $this->assign('pager', $this->pageShow($count));
        $this->assign('current', "not_shoushuo");
        $this->assign('orders_list', $orders);
        $this->assign('pay',$pay);
        $this->assign('type',3);
        $this->display('user_order_list.dwt');
    }

    /**
     * ajax获取订单
     */
    public function async_order_list()
    {
        if (IS_AJAX) {
            $start = $_POST['last'];
            $limit = $_POST['amount'];
            $pay = isset($_GET['pay']) ? intval($_GET['pay']) : 0;
            
            $order_list = model('Users')->get_user_orders($this->user_id, $pay, $limit, $start);
            foreach ($order_list as $key => $order) {
                $this->assign('orders', $order);
                $sayList[] = array(
                    'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
                );
            }
            die(json_encode($sayList));
        } else {
            $this->redirect(url('index'));
        }
    }

    /**
     * 订单跟踪
     */
    public function order_tracking()
    {
        $order_id = I('get.order_id', 0);
        $ajax = I('get.ajax', 0);
        
        $where['user_id'] = $this->user_id;
        $where['order_id'] = $order_id;
        $orders = $this->model->table('order_info')
            ->field('order_id, order_sn, invoice_no, shipping_name, shipping_id')
            ->where($where)
            ->find();
        // 生成快递100查询接口链接

        $shipping = get_shipping_object(3);
        // 接口模式
      
        $query_link = $shipping->query($orders['invoice_no']);
      
        $get_content = Http::doGet($query_link);
        $get_content_data = json_decode($get_content, 1);
     
        if ($get_content_data['status'] != '200') {
            // 跳转模式
            $query_link = $shipping->third_party($orders['invoice_no']);
  
            if ($query_link) {
                header('Location: ' . $query_link);
                exit();
            }
        }
        $this->assign('title', L('order_tracking'));
        $this->assign('trackinfo', $get_content);
        $this->display('user_order_tracking.dwt');
    }

    /**
     * 订单详情
     */
    public function order_detail()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        
        // 订单详情

        $order = model('Users')->get_order_detail_new($order_id, $this->user_id);
  
        if (! $order['order_status']) {
            $cancel = true;
        }
        if ($order === false) {

            ECTouch::err()->show(L('back_home_lnk'), './');
            exit();
        }
        
        if ($order['order_status'] == OS_UNCONFIRMED) {
            $order['handler'] = "<a class=\"Zbtn ZsubB\"  style=\"background-color:#c7c7c7; border:1px solid #c7c7c7\" href=\"" . url('user/cancel_order', array(
                'order_id' => $order['order_id']
            )) . "\" onclick=\"if (!confirm('" . L('confirm_cancel') . "')) return false;\">" . L('cancel_order_detail') . "</a>";
        } elseif ($order['order_status'] == OS_SPLITED) {
            /* 对配送状态的处理 */
            if ($order['shipping_status'] == SS_SHIPPED) {
                @$order['handler'] = "<a class=\"Zbtn ZsubB\" href=\"" . url('user/affirm_received', array(
                    'order_id' => $order['order_id']
                )) . "\" onclick=\"if (!confirm('" . L('confirm_received') . "')) return false;\">" . L('received') . "</a>";
            } elseif ($order['shipping_status'] == SS_RECEIVED) {
                // @$order['handler'] = '<a class="btn btn-info ect-colorf Bect-bg" type="button" href="javascript:void(0);">' . L('ss_received') . '</a>';
            } else {
                if ($order['pay_status'] == PS_UNPAYED) {
                    @$order['handler'] = "<a class=\"btn btn-infoect-colorf ect-bg\" href=\"" . url('user/cancel_order', array(
                        'order_id' => $order['order_id']
                    )) . "\">" . L('pay_money') . "</a>";
                } else {
                    // @$order['handler'] = "<a class=\"btn btn-info ect-colorf\" href=\"javascript:void(0);\">" . L('view_order') . "</a>";
                }
            }
        } else {
            
            // $order['handler'] = '<a class="btn btn-info ect-colorf Bect-bg" type="button" href="javascript:void(0);">' . L('os.' . $order['order_status']) . '</a>';
        }
         
        // 订单商品
        $goods_list = model('Order')->order_goods($order_id);
    
        foreach ($goods_list as $key => $value) {
            $goods_list[$key]['market_price'] = $value['market_price'];
            $goods_list[$key]['goods_price'] = $value['goods_price'];
            $goods_list[$key]['subtotal'] = $value['subtotal'];
            $goods_list[$key]['tags'] = model('ClipsBase')->get_tags($value['goods_id']);
            $goods_list[$key]['goods_thumb'] = get_image_path($order_id, $value['goods_thumb']);
            /* 退换货 start */
            $goods_list[$key]['ret_id'] = $this->model->table('order_return')
                ->field('ret_id')
                ->where('rec_id =' . $value['rec_id'])
                ->getOne();
            $goods_list[$key]['aftermarket'] = model('Users')->check_aftermarket($value['rec_id']); // 查询是否申请过售后服务
           
         
           if($order['pay_status']==2){
            /* 只有已付款订单才能申请售后服务 */
                $goods_list[$key]['service_apply'] = true;
           }
                
           
            /* 退换货 end */
        }
        
        // 设置能否修改使用余额数
        if ($order['order_amount'] > 0) {
            if ($order['order_status'] == OS_UNCONFIRMED || $order['order_status'] == OS_CONFIRMED) {
                $user = model('Order')->user_info($order['user_id']);
                if ($user['user_money'] + $user['credit_line'] > 0) {
                    $this->assign('allow_edit_surplus', 1);
                    $this->assign('max_surplus', sprintf(L('max_surplus'), $user['user_money']));
                }
            }
        }
        
        // 未发货，未付款时允许更换支付方式
        if ($order['order_amount'] > 0 && $order['pay_status'] == PS_UNPAYED && $order['shipping_status'] == SS_UNSHIPPED) {
            $payment_list = model('Order')->available_payment_list(false, 0, true);
                    
            // 过滤掉当前支付方式和余额支付方式
            if (is_array($payment_list)) {
                foreach ($payment_list as $key => $payment) {
                    // 如果不是微信浏览器访问并且不是微信会员 则不显示微信支付
                    if ($payment['pay_code'] == 'wxpay' && ! is_wechat_browser() && empty($_SESSION['openid'])) {
                        unset($payment_list[$key]);
                    }
                    // 兼容过滤ecjia支付方式
                    if (substr($payment['pay_code'], 0, 4) == 'pay_') {
                        unset($payment_list[$key]);
                    }
                    if (! file_exists(ROOT_PATH . 'plugins/payment/' . $payment['pay_code'] . '.php')) {
                        unset($payment_list[$key]);
                    }
                    if ($payment['pay_id'] == $order['pay_id'] || $payment['pay_code'] == 'balance') {
                        unset($payment_list[$key]);
                    }
                }
            }
            $this->assign('payment_list', $payment_list);
        }

        $order['pay_desc'] = html_out($order['pay_desc']);
      
        // 如果是银行汇款或货到付款 则显示支付描述
        $payment = model('Order')->payment_info(2);
        
        if ($payment['pay_code'] == 'bank' || $payment['pay_code'] == 'cod') {
            $this->assign('pay_desc', $payment['pay_desc']);
        }
        // 如果是微信支付 不允许再使用余额修改订单价格后支付
        if ($payment['pay_code'] == 'wxpay') {
            $this->assign('allow_edit_surplus', 0);
        }
        
        // 订单 支付 配送 状态语言项
        
        if ($order['order_status'] == 2) {
            $order['order_status1'] = L('os_cancel');
        } else {
            
            if ($order['order_status'] == 7) {
                $daijihuo = 1;
            }
            $order['order_status'] = L('os.' . $order['order_status']);
            
            $order['pay_status'] = L('ps.' . $order['pay_status']);
            $order['shipping_status'] = L('ss.' . $order['shipping_status']);
            
            if ($daijihuo) {
                $order['order_status1'] = "待激活";
            } else {
                $order['order_status1'] = $order['order_status'] . $order['shipping_status'] . $order['pay_status'];
            }
            
            // var_dump($order['order_status']);
            if (strpos($order['order_status1'], L('ps_unpayed'))) {
                $order['order_status1'] = L('unpay_order');
            }
            
            
            if (strpos($order['order_status1'], L('ps_payed'))) {
                
                if (strpos($order['order_status1'], L('ss_received'))) {
                    $order['order_status1'] = L('already_receive');
                } elseif (strpos($order['order_status1'], L('ss_shipped'))) {
                    
                    $order['order_status1'] = L('unreceived_order');
                } else {
                    
                    $order['order_status1'] = L('pending_send_out');
                }
            }
            if (strpos($order['order_status1'], "收货确认")) {
                $order['order_status1'] = L('unreview_order');
            }
            if (strpos($order['order_status1'], "已收货")) {
                $order['order_status1'] = L('already_receive');
            }
        }
        if ($order['order_status'] == '退货') {
            $order['order_status1'] = L('already_refund');
        }
       
        $this->assign('title', L('order_detail'));
        $order['count_time'] = local_date(C('time_format'), $order['count_time']);

        $order['confirm_receive_time'] = $order['confirm_receive_time']?date("Y-m-d H:i:s",$order['confirm_receive_time']):0;
         
        $order['shipping_time'] = $order['shipping_time']?date("Y-m-d H:i:s",$order['shipping_time']):0;
       
        $order['goods_amount'] = floatval($order['goods_amount']);
        $order['shipping_fee'] = floatval($order['shipping_fee']);
        $order['order_amount'] = floatval($order['order_amount']);
        $order['integral_money'] = floatval($order['integral_money']);

        $this->assign('order', $order);
        $this->assign('cancel', $cancel);
        $this->assign('goods_list', $goods_list);

        $this->display('user_order_detail.dwt');
    }

    /**
     * 确认收货
     */
    public function affirm_received()
    {
      
        $order_id = I('get.order_id', 0, 'intval');

        if (model('Users')->affirm_received($order_id, $this->user_id)) {



              $r = model('Order')->order_info($order_id);
  
                     if($r['order_type']==9){


                        $cart_goods = model('Order')->order_goods($order_id);
          
                $r1 = model("Users")->givePayPoints($this->user_id,$cart_goods[0]['goods_id']);
                      
                        if($r){


                              model('ClipsBase')->new_log_account_change($this->user_id, $r1*$cart_goods[0]['goods_number'],'订单'.$r['order_sn']."赠送的鱼宝",ACT_KD, 6); 

                        }
           }
            

            ecs_header("Location: " . url('order_list') . "\n");
            exit();
        } else {
            ECTouch::err()->show(L('order_list_lnk'), url('order_list'));
        }
    }

    /**
     * 编辑使用余额支付的处理
     */
    public function edit_surplus()
    {
        
        // 检查订单号
        $order_id = intval($_POST['order_id']);
        if ($order_id <= 0) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        
        // 检查余额
        $surplus = floatval($_POST['surplus']);
        if ($surplus <= 0) {
            ECTouch::err()->add(L('error_surplus_invalid'));
            ECTouch::err()->show(L('order_detail'), url('order_detail', array(
                'order_id' => $order_id
            )));
        }
        
        // 取得订单order_id
        $order = model('Order')->order_info($order_id);
        if (empty($order)) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        
        // 检查订单用护跟当前用护是否一致
        if ($_SESSION['user_id'] != $order['user_id']) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        
        // 检查订单是否未付款，检查应付款金额是否大于0
        if ($order['pay_status'] != PS_UNPAYED || $order['order_amount'] <= 0) {
            ECTouch::err()->add(L('error_order_is_paid'));
            ECTouch::err()->show(L('order_detail'), url('order_detail', array(
                'order_id' => $order_id
            )));
        }
        
        // 计算应付款金额（减去支付费用）
        $order['order_amount'] -= $order['pay_fee'];
        
        // 余额是否超过了应付款金额，改为应付款金额
        if ($surplus > $order['order_amount']) {
            $surplus = $order['order_amount'];
        }
        
        // 取得用护信息
        $user = model('Order')->user_info($_SESSION['user_id']);
        
        // 用护帐护余额是否足够
        if ($surplus > $user['user_money'] + $user['credit_line']) {
            ECTouch::err()->add(L('error_surplus_not_enough'));
            ECTouch::err()->show(L('order_detail'), url('order_detail', array(
                'order_id' => $order_id
            )));
        }
        
        // 修改订单，重新计算支付费用
        $order['surplus'] += $surplus;
        $order['order_amount'] -= $surplus;
        if ($order['order_amount'] > 0) {
            $cod_fee = 0;
            if ($order['shipping_id'] > 0) {
                $regions = array(
                    $order['country'],
                    $order['province'],
                    $order['city'],
                    $order['district']
                );
                $shipping = model('Shipping')->shipping_area_info($order['shipping_id'], $regions);
                if ($shipping['support_cod'] == '1') {
                    $cod_fee = $shipping['pay_fee'];
                }
            }
            
            $pay_fee = 0;
            if ($order['pay_id'] > 0) {
                $pay_fee = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);
            }
            
            $order['pay_fee'] = $pay_fee;
            $order['order_amount'] += $pay_fee;
        }
        
        // 如果全部支付，设为已确认、已付款
        
        if ($order['order_amount'] == 0) {
            if ($order['order_status'] == OS_UNCONFIRMED) {
                $order['order_status'] = OS_CONFIRMED;
                $order['confirm_time'] = time();
            }
            $order['pay_status'] = PS_PAYED;
            $order['pay_time'] = time();
        }
        $order = addslashes_deep($order);
        model('Users')->update_order($order_id, $order);
        
        // 更新用护余额
        $change_desc = sprintf(L('pay_order_by_surplus'), $order['order_sn']);
        //model('ClipsBase')->log_account_change($user['user_id'], (- 1) * $surplus, 0, 0, 0, $change_desc);
        model('ClipsBase')->new_log_account_change($user['user_id'], (- 1) * $surplus,$change_desc,ACT_OTHER,1);
        // 销量
        $this->update_touch_goods($order_id);
        // 跳转
        $url = url('order_detail', array(
            'order_id' => $order_id
        ));
        ecs_header("Location: $url\n");
        exit();
    }

    /**
     * 更改支付方式的处理
     */
    public function edit_payment()
    {
        
        // 检查支付方式
        $pay_id = intval($_POST['pay_id']);
        if ($pay_id <= 0) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        $payment_info = model('Order')->payment_info($pay_id);
        if (empty($payment_info)) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        
        // 检查订单号
        $order_id = intval($_POST['order_id']);
        if ($order_id <= 0) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        
        // 取得订单
        $order = model('Order')->order_info($order_id);
        if (empty($order)) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        
        // 检查订单用护跟当前用护是否一致
        if ($_SESSION['user_id'] != $order['user_id']) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        
        // 检查订单是否未付款和未发货 以及订单金额是否为0 和支付id是否为改变
        if ($order['pay_status'] != PS_UNPAYED || $order['shipping_status'] != SS_UNSHIPPED || $order['goods_amount'] <= 0 || $order['pay_id'] == $pay_id) {
            $url = url('order_detail', array(
                'order_id' => $order_id
            ));
            ecs_header("Location: $url\n");
            exit();
        }
        
        $order_amount = $order['order_amount'] - $order['pay_fee'];
        $pay_fee = pay_fee($pay_id, $order_amount);
        $order_amount += $pay_fee;
        
        $data['pay_id'] = $pay_id;
        $data['pay_name'] = $payment_info['pay_name'];
        $data['pay_fee'] = $pay_fee;
        $data['order_amount'] = $order_amount;
        $where['order_id'] = $order_id;
        $this->model->table('order_info')
            ->data($data)
            ->where($where)
            ->update();
        
        // 跳转
        $url = url('order_detail', array(
            'order_id' => $order_id
        ));
        ecs_header("Location: $url\n");
        exit();
    }

    /**
     * 取消订单
     */
    public function cancel_order()
    {
        $order_id = I('get.order_id', 0, 'intval');
        
        if (model('Users')->cancel_order($order_id, $this->user_id)) {
            $url = url('order_list');
            ecs_header("Location: $url\n");
            exit();
        } else {
            ECTouch::err()->show(L('order_list_lnk'), url('order_list'));
        }
    }

    /**
     * 收货地址列表界面
     */
    public function address_list()
    {
        if (IS_AJAX) {
            $start = $_POST['last'];
            $limit = $_POST['amount'];
            // 获得用护所有的收货人信息
            $consignee_list = model('Users')->get_consignee_list($this->user_id, 0, $limit, $start);
            if ($consignee_list) {
                foreach ($consignee_list as $k => $v) {
                    $address = '';
                    if ($v['province']) {
                        $address .= model('RegionBase')->get_region_name($v['province']);
                    }
                    if ($v['city']) {
                        $address .= model('RegionBase')->get_region_name($v['city']);
                    }
                    if ($v['district']) {
                        $address .= model('RegionBase')->get_region_name($v['district']);
                    }
                    $v['address'] = $address . ' ' . $v['address'];
                    $v['url'] = url('user/edit_address', array(
                        'id' => $v['address_id']
                    ));
                    $this->assign('consignee', $v);
                    $sayList[] = array(
                        'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
                    );
                }
            }
            die(json_encode($sayList));
            exit();
        }
        // 赋值于模板
        $this->assign('title', L('consignee_info'));
        $this->display('user_address_list.dwt');
    }

    // 添加收货地址
    public function add_address()
    {
        if (IS_POST) {
            $address = array(
                'user_id' => $this->user_id,
                'address_id' => intval($_POST['address_id']),
                'country' => I('post.country', 0, 'intval'),
                'province' => I('post.province', 0, 'intval'),
                'city' => I('post.city', 0, 'intval'),
                'district' => I('post.district', 0, 'intval'),
                'address' => I('post.address'),
                'consignee' => I('post.consignee'),
                'mobile' => I('post.mobile')
            );
            $token = $_SESSION['token'] = md5(uniqid());
            if ($_GET['token'] == $_SESSION['token']) {
                $url = url('user/address_list');
                ecs_header("Location: $url");
            }
            if (model('Users')->update_address($address)) {
                show_message(L('edit_address_success'), L('address_list_lnk'), url('address_list'));
            }
            exit();
        }
        if (! empty($_SESSION['consignee'])) {
            $consignee = $_SESSION['consignee'];
            $this->assign('consignee', $consignee);
        }
        $province_list = model('RegionBase')->get_regions(1, 1);
        $city_list = model('RegionBase')->get_regions(2, $consignee['province']);
        $district_list = model('RegionBase')->get_regions(3, $consignee['city']);
        $this->assign("token", $token);
        $this->assign('title', L('add_address'));
        // 取得国家列表、商店所在国家、商店所在国家的省列表
        
        $this->assign('country_list', model('RegionBase')->get_regions());
        $this->assign('shop_province_list', model('RegionBase')->get_regions(1, C('shop_country')));
        $this->assign('province_list', $province_list);
        $this->assign('city_list', $city_list);
        $this->assign('district_list', $district_list);
        
        $this->assign("headerContent", L("new_consignee_address"));
        
        $this->display('user_add_address.dwt');
    }

    // 根据经纬度获取所在地区
    public function positions()
    {
        if (IS_POST) {
            $lng = I('post.lng', 0);
            $lat = I('post.lat', 0);
            $store = $lat . ',' . $lng;
            if (empty($store)) {
                exit(json_encode(array(
                    'error' => 1,
                    'message' => '暂时无法获取默认地址'
                )));
            }
            $result = Http::doGet('http://apis.map.qq.com/ws/geocoder/v1/?location=' . $store . '&key=LXDBZ-2SA3V-PXAPD-U2YGL-D47G6-C4B7O');
            $data = json_decode($result, 1);
            if (! empty($data)) {
                $address = $data['result']['address_component'];
                $province = $address['province'];
                $province = mb_substr($province, 0, - 1, 'utf-8');
                $city = $address['city'];
                $city = mb_substr($city, 0, - 1, 'utf-8');
                $district = $address['district'];
                $province_id = model('Users')->find_address($province, 1);
                $city_id = model('Users')->find_address($city, 2);
                $district_id = model('Users')->find_address($district, 3);
                $consignee = array(
                    'province' => $province_id,
                    'city' => $city_id,
                    'district' => $district_id
                );
                $_SESSION['consignee'] = $consignee;
            }
        }
    }

    /**
     * 编辑收货地址的处理
     */
    public function edit_address()
    {
        // 编辑收货地址
        if (IS_POST) {
            $address = array(
                'user_id' => $this->user_id,
                'address_id' => intval($_POST['address_id']),
                'country' => I('post.country', 0, 'intval'),
                'province' => I('post.province', 0, 'intval'),
                'city' => I('post.city', 0, 'intval'),
                'district' => I('post.district', 0, 'intval'),
                'address' => I('post.address'),
                'consignee' => I('post.consignee'),
                'mobile' => I('post.mobile')
            );
            
            if (model('Users')->update_address($address)) {
                show_message(L('edit_address_success'), L('address_list_lnk'), url('address_list'));
            }
            exit();
        }
        
        $id = isset($_GET['id']) ? intval($_GET['id']) : '';
        
        // 获得用护对应收货人信息
        $consignee = model('Users')->get_consignee_list($_SESSION['user_id'], $id);
        $province_list = model('RegionBase')->get_regions(1, $consignee['country']);
        $city_list = model('RegionBase')->get_regions(2, $consignee['province']);
        $district_list = model('RegionBase')->get_regions(3, $consignee['city']);
        $this->assign('title', L('edit_address'));
        $this->assign('consignee', $consignee);
        // 取得国家列表、商店所在国家、商店所在国家的省列表
        $this->assign('country_list', model('RegionBase')->get_regions());
        $this->assign('shop_province_list', model('RegionBase')->get_regions(1, C('shop_country')));
        $this->assign('province_list', $province_list);
        $this->assign('city_list', $city_list);
        $this->assign('district_list', $district_list);
        
        $this->display('user_edit_address.dwt');
    }

    /**
     * 删除收货地址
     */
    public function del_address_list()
    {
        $id = intval($_GET['id']);
        
        if (model('Users')->drop_consignee($id)) {
            $url = url('address_list');
            unset($_SESSION['flow_consignee']);
            ecs_header("Location: $url\n");
            exit();
        } else {
            show_message(L('del_address_false'));
        }
    }

    /**
     * 信息中心
     */
    public function msg_list()
    {
        if (IS_AJAX) {
            $order_id = I('get.order_id', 0);
            $start = $_POST['last'];
            $limit = $_POST['amount'];
            
            // 获取信息
            $message_list = model('ClipsBase')->get_message_list($this->user_id, $_SESSION['user_name'], $limit, $start, $order_id);
            if (is_array($message_list)) {
                // 修改信息状态
                $sql = 'SELECT parent_id FROM ' . $this->model->pre . 'feedback WHERE parent_id in (SELECT f.msg_id FROM ' . $this->model->pre . 'feedback f LEFT JOIN ' . $this->model->pre . 'touch_feedback t ON f.msg_id = t.msg_id WHERE f.parent_id = 0 AND f.user_id = ' . $this->user_id . ' AND t.msg_read = 0 ORDER BY msg_time DESC) ORDER BY msg_time DESC';
                $rs = $this->model->query($sql);
                if ($rs) {
                    foreach ($rs as $v) {
                        $where['msg_id'] = $v['parent_id'];
                        $data['msg_read'] = 1;
                        $this->model->table('touch_feedback')
                            ->data($data)
                            ->where($where)
                            ->update();
                    }
                }
                foreach ($message_list as $key => $vo) {
                    $this->assign('msg', $vo);
                    $sayList[] = array(
                        'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
                    );
                }
            }
            echo json_encode($sayList);
            exit();
        } else {
            $order_id = I('request.order_id') ? intval(I('request.order_id')) : 0;
            /* 获取用护留言的数量 */
            if ($order_id) {
                $sql = "SELECT COUNT(*) as count FROM " . $this->model->pre . "feedback WHERE parent_id = 0 AND order_id = '$order_id' AND user_id = '$this->user_id'";
                $order_info = $this->model->getRow("SELECT * FROM " . $this->model->pre . "order_info WHERE order_id = '$order_id' AND user_id = '$this->user_id'");
                $order_info['url'] = url('user/order_detail', array(
                    'order_id' => $order_id
                ));
            } else {
                $sql = "SELECT COUNT(*) as count FROM " . $this->model->pre . "feedback WHERE parent_id = 0 AND user_id = '$this->user_id' AND user_name = '" . $_SESSION['user_name'] . "' AND order_id=0";
            }
            $count = $this->model->getRow($sql);
            $filter['page'] = '{page}';
            $offset = $this->pageLimit(url('msg_list', $filter), 5);
            $offset_page = explode(',', $offset);
            // 获取信息
            $message_list = model('ClipsBase')->get_message_list($this->user_id, $_SESSION['user_name'], $offset_page[1], $offset_page[0], $order_id);
            $this->assign('pager', $this->pageShow($count['count']));
            $this->assign('order_id', $order_id);
            $this->assign('message_list', $message_list);
        }
        $this->assign('title', L('user_service_list'));
        $this->display('user_msg_list.dwt');
    }

    /**
     * 删除信息
     */
    public function del_msg()
    {
        $id = I('get.id', 0);
        $order_id = I('get.order_id', 0);
        
        if ($id > 0) {
            $where_s['msg_id'] = $id;
            $row = $this->model->table('feedback')
                ->field('user_id, message_img')
                ->where($where_s)
                ->find();
            
            if ($row && $row['user_id'] == $this->user_id) {
                // 验证通过，删除留言，回复，及相应文件
                if ($row['message_img']) {
                    @unlink(ROOT_PATH . DATA_DIR . '/feedbackimg/' . $row['message_img']);
                }
                
                $where_d = 'msg_id = ' . $id . ' OR parent_id = ' . $id;
                $this->model->table('feedback')
                    ->where($where_d)
                    ->delete();
            }
        }
        $url = url('msg_list', array(
            'order_id' => $order_id
        ));
        ecs_header("Location: $url\n");
        exit();
    }

    /**
     * 客护服务
     */
    public function service()
    {
        if (IS_POST) {
            $message = array(
                'user_id' => $this->user_id,
                'user_name' => $_SESSION['user_name'],
                'user_email' => $_SESSION['email'],
                'msg_type' => I('post.msg_type', 0),
                'msg_title' => I('post.msg_title'),
                'msg_content' => I('post.msg_content'),
                'order_id' => I('post.order_id', 0),
                'upload' => (isset($_FILES['message_img']['error']) && $_FILES['message_img']['error'] == 0) || (! isset($_FILES['message_img']['error']) && isset($_FILES['message_img']['tmp_name']) && $_FILES['message_img']['tmp_name'] != 'none') ? $_FILES['message_img'] : array()
            );
            
            if (model('ClipsBase')->add_message($message)) {
                $data['msg_id'] = M()->insert_id();
                $this->model->table('touch_feedback')
                    ->data($data)
                    ->insert();
                
                show_message(L('add_message_success'), L('user_service'), url('msg_list'), 'info');
            } else {
                self::err()->show(L('user_service'), url('service'));
            }
            exit();
        }
        
        // 页面显示
        $this->assign('title', L('user_service'));
        $this->assign("headerContent", L("user_service"));
        $this->assign('footer_index','affiliate');
        $this->display('user_service.dwt');
    }

    /**
     * 分享推荐
     */
    public function share()
    {
        $share = unserialize(C('affiliate'));
        
        $goodsid = I('request.goodsid', 0);
        
        if (empty($goodsid)) {
            $page = I('request.page', 1);
            $size = I(C('page_size'), 10);
            empty($share) && $share = array();
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
                    $this->assign('affdb', $affdb);
                }
                
                $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
                
                $sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
            } else {
                // 推荐订单分成
                $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
                
                $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
            }
            
            $res = $this->model->query($sqlcount);
            $count = $res[0]['count'];
            $url_format = url('share', array(
                'page' => '{page}'
            ));
            $limit = $this->pageLimit($url_format, 10);
            $sql = $sql . ' LIMIT ' . $limit;
            $rt = $this->model->query($sql);
            
            if ($rt) {
                foreach ($rt as $k => $v) {
                    if (! empty($v['suid'])) {
                        // 在affiliate_log有记录
                        if ($v['separate_type'] == - 1 || $v['separate_type'] == - 2) {
                            // 已被撤销
                            $rt[$k]['is_separate'] = 3;
                        }
                    }
                    $rt[$k]['order_sn'] = substr($v['order_sn'], 0, strlen($v['order_sn']) - 5) . "***" . substr($v['order_sn'], - 2, 2);
                }
            } else {
                $rt = array();
            }
            $pager = $this->pageShow($count);
            $this->assign('pager', $pager);
            $this->assign('affiliate_type', $share['config']['separate_by']);
            $this->assign('logdb', $rt);
        } else {
            // 单个商品推荐
            $this->assign('userid', $this->user_id);
            $this->assign('goodsid', $goodsid);
            
            $types = array(
                1,
                2,
                3,
                4,
                5
            );
            $this->assign('types', $types);
            
            $goods = model('Goods')->get_goods_info($goodsid);
            $goods['goods_img'] = get_image_path(0, $goods['goods_img']);
            $goods['goods_thumb'] = get_image_path(0, $goods['goods_thumb']);
            $goods['shop_price'] = price_format($goods['shop_price']);
            
            $this->assign('goods', $goods);
        }
        $shopurl = __URL__ . '/?u=' . $this->user_id;
        
        $this->assign('shopurl', $shopurl);
        $this->assign('domain', __HOST__);
        $this->assign('shopdesc', C('shop_desc'));
        $this->assign('title', L('label_share'));
        $this->assign('share', $share);
        $this->display('user_share.dwt');
    }

    /**
     * 生成二维码
     */
    public function create_qrcode()
    {
        $value = I('get.value');
        if ($value) {
            // 二维码
            // 纠错级别：L、M、Q、H
            $errorCorrectionLevel = 'L';
            // 点的大小：1到10
            $matrixPointSize = 8;
            QRcode::png($value, false, $errorCorrectionLevel, $matrixPointSize, 2);
        }
    }

    /**
     * 我的标签
     */
    public function tag_list()
    {
        $tags = get_user_tags($this->user_id);
        
        $this->assign('title', L('label_tag'));
        $this->assign('tags', $tags);
        $this->display('user_tag_list.dwt');
    }

    /**
     * 删除标签
     */
    public function del_tag()
    {
        if (IS_AJAX) {
            $tag_words = I('get.tag_wrods');
            $rs = model('ClipsBase')->delete_tag($tag_words, $this->user_id);
            if (empty($rs)) {
                exit(json_encode(array(
                    'status' => 0,
                    'msg' => '删除失败'
                )));
            }
        }
    }

    /**
     * 我的红包
     */
    public function bonus()
    {
        if (IS_POST) {
            $bonus_sn = I('post.bonus_sn', '', 'intval');
            if ($bonus_sn == '') {
                show_message('请输入红包序列号', L('back_up_page'), url('bonus'), 'info');
            } else {
                if (model('Users')->add_bonus($this->user_id, $bonus_sn)) {
                    show_message(L('add_bonus_sucess'), L('back_up_page'), url('bonus'), 'info');
                } else {
                    ECTouch::err()->show(L('back_up_page'), url('bonus'));
                }
            }
        }
        // 分页
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('bonus', $filter), 5);
        $offset_page = explode(',', $offset);
        $count = $this->model->table('user_bonus')
            ->where('user_id = ' . $this->user_id)
            ->count();
        $bonus = model('Users')->get_user_bouns_list($this->user_id, $offset_page[1], $offset_page[0]);
        
        $this->assign('title', L('label_bonus'));
        $this->assign('pager', $this->pageShow($count));
        $this->assign('bonus', $bonus);
        $this->display('user_bonus.dwt');
    }

    /**
     * 缺货登记列表
     */
    public function booking_list()
    {
        /* 获取缺货登记的数量 */
        $sql = "SELECT COUNT(*) as num " . "FROM " . $this->model->pre . "booking_goods AS bg, " . $this->model->pre . "goods AS g " . "WHERE bg.goods_id = g.goods_id AND bg.user_id = '$this->user_id'";
        $count = $this->model->query($sql);
        // 分页
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('booking_list', $filter), 5);
        $offset_page = explode(',', $offset);
        $booking_list = model('ClipsBase')->get_booking_list($this->user_id, $offset_page[1], $offset_page[0]);
        
        $this->assign('title', L('label_booking'));
        $this->assign('pager', $this->pageShow($count[0]['num']));
        $this->assign('booking_list', $booking_list);
        $this->display('user_booking_list.dwt');
    }

    /**
     * 添加缺货登记
     */
    public function add_booking()
    {
        if (IS_POST) {
            $booking = array(
                'goods_id' => I('post.id', 0),
                'goods_amount' => I('post.number', 0),
                'desc' => I('post.desc'),
                'linkman' => I('post.linkman'),
                'email' => I('post.email'),
                'tel' => I('post.tel'),
                'booking_id' => I('post.rec_id', 0)
            );
            
            // 查看此商品是否已经登记过
            $rec_id = model('ClipsBase')->get_booking_rec($this->user_id, $booking['goods_id']);
            if ($rec_id > 0) {
                show_message(L('booking_rec_exist'), L('back_page_up'), '', 'error');
            }
            
            if (model('ClipsBase')->add_booking($booking)) {
                show_message(L('booking_success'), L('back_booking_list'), url('booking_list'), 'info');
            } else {
                ECTouch::err()->show(L('booking_list_lnk'), url('booking_list'));
            }
        }
        $goods_id = I('get.id', 0);
        if ($goods_id == 0) {
            show_message(L('no_goods_id'), L('back_page_up'), '', 'error');
        }
        
        /* 根据规格属性获取货品规格信息 */
        $goods_attr = '';
        if ($_GET['spec'] != '') {
            $goods_attr_id = $_GET['spec'];
            
            $attr_list = array();
            $sql = "SELECT a.attr_name, g.attr_value " . "FROM " . $this->model->pre . "goods_attr AS g, " . $this->model->pre . "attribute AS a " . "WHERE g.attr_id = a.attr_id " . "AND g.goods_attr_id " . db_create_in($goods_attr_id);
            $row = $this->model->query($sql);
            if (empty($row)) {
                $row = array();
            }
            foreach ($row as $v) {
                $attr_list[] = $v['attr_name'] . ': ' . $v['attr_value'];
            }
            $goods_attr = join(chr(13) . chr(10), $attr_list);
        }
        $this->assign('goods_attr', $goods_attr);
        $this->assign('goods', model('ClipsBase')->get_goodsinfo($goods_id));
        $this->assign('title', L('label_booking'));
        $this->display('user_add_booking.dwt');
    }

    /**
     * 删除缺货登记
     */
    public function del_booking()
    {
        $id = I('get.rec_id', 0);
        if ($id == 0 || $this->user_id == 0) {
            $this->redirect(url('booking_list'));
        }
        
        $result = model('ClipsBase')->delete_booking($id, $this->user_id);
        if ($result) {
            $this->redirect(url('booking_list'));
        }
    }

    /**
     * 收藏商品列表
     */
    public function collection_list()
    {
        // 分页
        $count = $this->model->table('collect_goods')
            ->where('user_id = ' . $this->user_id)
            ->order('add_time desc')
            ->count();
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('collection_list', $filter), 5);
        $offset_page = explode(',', $offset);
        $collection_list = model('ClipsBase')->get_collection_goods($this->user_id, $offset_page[1], $offset_page[0]);
        //$this->assign('share_link', __URL__.$_SERVER['REQUEST_URI']);//
        // $this->assign('share_title',  C('shop_title'));//
        $this->assign('share_description', $this->formatDescription(C('shop_desc')));//
         $this->assign('share_pic', __URL__."/themes/yutui/images/new/yutui_logo.png");

        $this->assign('title', L('label_collection'));
        $this->assign('pager', $this->pageShow($count));
        $this->assign('collection_list', $collection_list);
        $this->assign("headerContent", L("btn_collect"));
        $this->display('user_collection_list.dwt');
    }

    public function ajax_collection_list()
    {
        $page = $_POST['page'];
        $size = 5;
        $start = ($page - 1) * $size;
        $collection_list = model('ClipsBase')->get_collection_goods($this->user_id, $size, $start);
        $count = model('ClipsBase')->num_collection_goods($this->user_id);

         $countpage = ceil($count/$size)+1;
         $result['totalpage'] = $countpage;
         $result['list'] = $collection_list;
         $result['page'] = $_POST['page'];
        die(json_encode($result));
        exit();
    }

    /**
     * 添加收藏商品
     */
    public function add_collection()
    {
        $result = array(
            'error' => 0,
            'message' => ''
        );
        $goods_id = intval($_GET['id']);
        $order_type = intval($_GET['order_type']);

        if (! isset($this->user_id) || $this->user_id == 0) {
            $result['error'] = 2;
            $result['message'] = L('login_please');
            die(json_encode($result));
        } else {
            // 检查是否已经存在于用护的收藏夹
            $where['user_id'] = $this->user_id;
            $where['goods_id'] = $goods_id;
            $where['order_type'] = $order_type;
            $rs = $this->model->table('collect_goods')
                ->where($where)
                ->count();
            if ($rs > 0) {
                $rs = $this->model->table('collect_goods')
                    ->where($where)
                    ->delete();
                if (! $rs) {
                    $result['error'] = 1;
                    $result['message'] = M()->errorMsg();
                    die(json_encode($result));
                } else {
                    $result['error'] = 0;
                    $result['message'] = L('collect_error');
                    die(json_encode($result));
                }
            } else {
                $data['user_id'] = $this->user_id;
                $data['goods_id'] = $goods_id;
                $data['order_type'] = $order_type;
                $data['add_time'] = time();
                if ($this->model->table('collect_goods')
                    ->data($data)
                    ->insert() === false) {
                    $result['error'] = 1;
                    $result['message'] = M()->errorMsg();
                    die(json_encode($result));
                } else {
                    $result['error'] = 0;
                    $result['message'] = L('collect_success');
                    die(json_encode($result));
                }
            }
        }
    }

    /**
     * 删除收藏商品
     */
    public function delete_collection()
    {
        // ajax请求
        if (IS_AJAX && IS_GET) {
            $rs = 0;
            $rec_id = I('get.rec_id', 0);
            if ($rec_id > 0) {
                $where['user_id'] = $this->user_id;
                $where['rec_id'] = $rec_id;
                $this->model->table('collect_goods')
                    ->where($where)
                    ->delete();
                $rs = 1;
            }
            echo json_encode(array(
                'status' => $rs
            ));
            exit();
        } elseif (! IS_AJAX && IS_GET) {
            $rec_id = I('get.rec_id', 0);
            if ($rec_id > 0) {
                $where['user_id'] = $this->user_id;
                $where['rec_id'] = $rec_id;
                $this->model->table('collect_goods')
                    ->where($where)
                    ->delete();
            }
            $this->redirect(url('collection_list'));
        } else {
            echo json_encode(array(
                'status' => 0
            ));
            exit();
        }
    }

    /**
     * 添加关注
     */
    public function add_attention()
    {
        $rec_id = I('get.rec_id', 0);
        if ($rec_id) {
            $this->model->table('collect_goods')
                ->data('is_attention = 1')
                ->where('rec_id = ' . $rec_id . ' and user_id = ' . $this->user_id)
                ->update();
        }
        $this->redirect(url('collection_list'));
    }

    /**
     * 取消关注
     */
    public function del_attention()
    {
        $rec_id = I('get.rec_id', 0);
        if ($rec_id) {
            $this->model->table('collect_goods')
                ->data('is_attention = 0')
                ->where('rec_id = ' . $rec_id . ' and user_id = ' . $this->user_id)
                ->update();
        }
        $this->redirect(url('collection_list'));
    }

    /**
     * 评论列表
     */
    public function comment_list()
    {
        // 分页
        $count = $this->model->table('comment')
            ->where('parent_id = 0 and user_id = ' . $this->user_id)
            ->count();
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('comment_list', $filter), 5);
        $offset_page = explode(',', $offset);
        $comment_list = model('ClipsBase')->get_comment_list($this->user_id, $offset_page[1], $offset_page[0]);
        
        $this->assign('title', L('label_comment'));
        $this->assign('pager', $this->pageShow($count));
        $this->assign('comment_list', $comment_list);
        $this->display('user_comment_list.dwt');
    }

    /**
     * 删除评论
     */
    public function delete_comment()
    {
        // ajax请求
        if (IS_AJAX && IS_GET) {
            $rs = 0;
            $id = I('get.id', 0);
            if ($id > 0) {
                $where['user_id'] = $this->user_id;
                $where['comment_id'] = $id;
                $this->model->table('comment')
                    ->where($where)
                    ->delete();
                $rs = 1;
            }
            echo json_encode(array(
                'status' => $rs
            ));
            exit();
        } elseif (IS_GET && ! IS_AJAX) {
            $id = I('get.id', 0);
            if ($id > 0) {
                $where['user_id'] = $this->user_id;
                $where['comment_id'] = $id;
                $this->model->table('comment')
                    ->where($where)
                    ->delete();
            }
            $this->redirect(url('comment_list'));
        } else {
            echo json_encode(array(
                'status' => 0
            ));
            exit();
        }
    }

    /**
     * 登录
     */
    public function oldlogin()
    {

        
        // 登录处理
        if (IS_POST) {

            
            $username = I('get.username', '', 'trim');
            $password = I('get.password', '', 'trim');
           
            $mobileZone = I('get.mobile_zone', '86', 'trim');
            $back_act = I('back_act', '', 'trim');
            $this->back_act = empty($back_act) ? url('user/index') : $back_act;
            
            $captcha = intval(C('captcha'));
            if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
                if (empty($_POST['captcha'])) {
                    show_message(L('invalid_captcha'), L('relogin_lnk'), url('login', array(
                        'back_act' => urlencode($this->back_act)
                    )), 'error');
                }
                
            }
          
            

            if (self::$user->login($username, $password, isset($_POST['remember']))) {
                model('Users')->update_user_info();
                model('Users')->recalculate_price();
                
               
                $jump_url = empty($this->back_act) ? url('index',array('u'=>$_SESSION['user_id'])) : $this->back_act;

                if(is_wechat_browser()){


                        $this->wechat_init();
                        
                        model('Users')->unbindopenid($_SESSION['openid']);

                        model('Users')->bindopenid($_SESSION['openid']);
                        
                }
                


              
                $this->redirect($jump_url);
            } else {
                $_SESSION['login_fail'] ++;
                show_message(L('login_failure'), L('relogin_lnk'), url('login', array(
                    'back_act' => urlencode($this->back_act)
                )), 'error');
            }
            exit();
        }
        
        // 登录页面显示
        $back_act = I('back_act', '', 'urldecode');
        if (empty($back_act)) {
            if (empty($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
                $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], url('user/index')) ? url('user/index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
            } else {
                $back_act = url('user/index');
            }
        }
        // 来源是退出地址时 默认会员中心
        $this->back_act = strpos($back_act, 'logout')? url('user/index') : $back_act;
        $this->back_act = (strpos($back_act, 'register')||strpos($back_act, 'get_password_phone')||strpos($back_act, 'login'))? url('index') : $back_act;
        // 验证码相关设置
        $captcha = intval(C('captcha'));
        if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
            $this->assign('enabled_captcha', 1);
            $this->assign('rand', mt_rand());
        }
        
        // 微信浏览器显示授权登录
        if (is_wechat_browser()) {
            $this->assign('oauth_url', url('user/index', array(
                'flag' => 'oauth'
            )));
        }
        
        $this->assign('title', L('login'));
        $this->assign('step', I('get.step'));
        $this->assign('anonymous_buy', C('anonymous_buy'));
        $this->assign('back_act', $this->back_act);
        $this->display('user_login.dwt');
    }

    public function login(){
        // 登录处理

        if (IS_GET) {

            $username = I('get.username', '', 'trim');
         
            // $sms_code = I('get.sms_code', '', 'trim');
           
            $back_act = I('back_act', '', 'trim');
            $this->back_act = empty($back_act) ? url('user/index') : $back_act;
            
            // if($sms_code!=$_SESSION['sms_code']){
            //    show_message(L('sms_failure'), L('relogin_lnk'), url('register', array(
            //         'back_act' => urlencode($this->back_act)
            //     )), 'error');
            // }

            $row = $this->model->table('users')
                    ->field('mobile_phone,status,openid')
                    ->where('user_name = "' . $username.'"')
                    ->find();
                    // var_dump($_GET);
                    // var_dump($_POST);
                    // exit;
    
                if($row){
                    if($row['status']==2){
                        //冻结
                       show_message(L('account_error'), L('relogin_lnk'), url('register', array(
                            'back_act' => urlencode($this->back_act)
                        )), 'error');
                    } 
                      
                    if($row['mobile_phone']!=$_SESSION['sms_mobile']){
                       show_message(L('mobile_failure'), L('relogin_lnk'), url('register', array(
                            'back_act' => urlencode($this->back_act)
                        )), 'error');
                    }
                }else{
                    show_message(L('login_error'), L('relogin_lnk'), url('register', array(
                            'back_act' => urlencode($this->back_act)
                        )), 'error');
                }
            
           
            if (self::$user->login($username, null, isset($_POST['remember']))) {
                if($_SESSION['openid']&&$_SESSION['user_id']){
            /*更新用户的openid*/
              
                $data_up['openid'] = $_SESSION['openid'];
                $where_up['user_id'] = $_SESSION['user_id'];
                $this->model->table('users')
                    ->data($data_up)
                    ->where($where_up)
                    ->update();
                }
                model('Users')->update_user_info();
                model('Users')->recalculate_price();
                $openid = $row['openid'] ;
                  //$this->cleanUserCache($this->user_id);
               
                $jump_url = empty($this->back_act) ? url('index',array('u'=>$_SESSION['user_id'])) : $this->back_act;

                if(is_wechat_browser()){


                        $this->wechat_init();
                        
                        model('Users')->unbindopenid($_SESSION['openid']);

                        model('Users')->bindopenid($_SESSION['openid']);
                        
                }
                if(is_line_browser()){
                    $this->line_init();
                    
                    model('Users')->unbindopenid($_SESSION['openid']);
                    
                    model('Users')->bindopenid($_SESSION['openid']);
                }
                
                


              
                $this->redirect($jump_url);
            } else {
               
                $_SESSION['login_fail'] ++;
                show_message(L('login_failure'), L('relogin_lnk'), url('login', array(
                    'back_act' => urlencode($this->back_act)
                )), 'error');
            }
            exit();
        }

    }

    public function loginaccount(){

        if (IS_POST) {

            
            $vip_manage_account = I('post.username', '', 'trim');

            $password = I('post.password', '', 'trim');
            $resource = I('post.resource', '', 'trim');
            $back_act = I('back_act', '', 'trim');
            $this->back_act = empty($back_act) ? url('user/index') : $back_act;
            
            /* $captcha = intval(C('captcha'));
            if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
                if (empty($_POST['captcha'])) {
                    show_message(L('invalid_captcha'), L('relogin_lnk'), url('login', array(
                        'back_act' => urlencode($this->back_act)
                    )), 'error');
                }
                
            } */
          
            
            if (self::$user->newdistributionLogin($vip_manage_account, $password,$resource, isset($_POST['remember']))) {
                model('Users')->update_user_info();
                model('Users')->recalculate_price();
                if($_SESSION['openid']&&$_SESSION['user_id']){
            /*更新用户的openid*/
               
                $data_up['openid'] = $_SESSION['openid'];
                $where_up['user_id'] = $_SESSION['user_id'];
                $this->model->table('users')
                    ->data($data_up)
                    ->where($where_up)
                    ->update();
                 }
              
                  //$this->cleanUserCache($this->user_id);
               
                $jump_url = empty($this->back_act) ? url('index',array('u'=>$_SESSION['user_id'])) : $this->back_act;

                if(is_wechat_browser()){


                        $this->wechat_init();
                        
                        model('Users')->unbindopenid($_SESSION['openid']);

                        model('Users')->bindopenid($_SESSION['openid']);
                        
                }
             

              
                $this->redirect($jump_url);
            } else {
                $_SESSION['login_fail'] ++;
                show_message(L('login_failure'), L('relogin_lnk'), url('loginaccount', array(
                    'back_act' => urlencode($this->back_act)
                )), 'error');
            }
            exit();
            // 登录页面显示
            $back_act = I('back_act', '', 'urldecode');
            if (empty($back_act)) {
                if (empty($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
                    $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], url('user/index')) ? url('user/index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
                } else {
                    $back_act = url('user/index');
                }
            }
            // 来源是退出地址时 默认会员中心
            $this->back_act = strpos($back_act, 'logout')? url('user/index') : $back_act;
            $this->back_act = (strpos($back_act, 'register')||strpos($back_act, 'get_password_phone')||strpos($back_act, 'loginaccount'))? url('index') : $back_act;
            // 验证码相关设置
            $captcha = intval(C('captcha'));
            if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
                $this->assign('enabled_captcha', 1);
                $this->assign('rand', mt_rand());
            }
            
            // 微信浏览器显示授权登录
            if (get_third_browser()) {
                $this->assign('oauth_url', url('user/index', array(
                    'flag' => 'oauth'
                )));
            }


        }


        $this->assign('title', L('login'));
        $this->assign('step', I('get.step'));
        $this->assign('anonymous_buy', C('anonymous_buy'));
        $this->assign('back_act', $this->back_act);
        $this->display('user_login.dwt');
    }



    /**
     * 注册
     */
    public function oldregister()
    {

        // 注册处理
        if (IS_POST) {
            $enabled_sms = isset($_POST['enabled_sms']) ? intval($_POST['enabled_sms']) : 0;
            $this->back_act = isset($_POST['back_act']) ? in($_POST['back_act']) : '';
            
            // 邮箱注册处理
            if (0 == $enabled_sms) {
                // 数据处理
             
                $username = isset($_POST['username']) ? in($_POST['username']) : '';
                $password = isset($_POST['password']) ? in($_POST['password']) : '';
                $email = isset($_POST['email']) ? in($_POST['email']) : ''; // substr(md5($username), 0, 3) . time() . '@qq.com';
                $mobileZone = I('mobile_zone',86);
                $other = array();
                
                // 验证码检查
                if (intval(C('captcha')) & CAPTCHA_REGISTER) {
                    if (empty($_POST['captcha'])) {
                        show_message(L('invalid_captcha'), L('sign_up'), url('register'), 'error');
                    }
                    // 检查验证码
                    if ($_SESSION['ectouch_verify'] !== strtoupper($_POST['captcha'])) {
                        show_message(L('invalid_captcha'), L('sign_up'), url('register'), 'error');
                    }
                }
                
                if (empty($_POST['agreement'])) {
                    show_message(L('passport_js.agreement'));
                }
                
                if (strlen($username) < 3) {
                    show_message(L('passport_js.username_shorter'));
                }
                if (strlen($username) > 15) {
                    show_message(L('passport_js.username_longer'));
                }
                
                if (strlen($password) < 6) {
                    show_message(L('passport_js.password_shorter'));
                }
                
                if (strpos($password, ' ') > 0) {
                    show_message(L('passwd_balnk'));
                }
            } elseif (1 == $enabled_sms) { // 手机号注册处理

                $username = isset($_POST['user_name']) ? in($_POST['user_name']) : '';
                $password = isset($_POST['password']) ? in($_POST['password']) : '';
                $sms_code = isset($_POST['sms_code']) ? in($_POST['sms_code']) : '';
                $other['autonym_mobile_phone'] = $_POST['mobile'];
               // $email = $username . '@139.com'; // 设置一个默认的邮箱
                $mobileZone = I('mobile_zone',86);
                $other['mobile_phone'] = $_POST['mobile'];
                $other['mobile_zone']  = $mobileZone;
                
                if (empty($username)) {
                    show_message(L('msg_mobile_blank'), L('register_back'), url('register'), 'error');
                }
                
                /* if ($sms_code != $_SESSION['sms_code']) {
                    show_message(L('sms_code_error'), L('register_back'), url('register'), 'error');
                } 
                if ($_POST['mobile'] != $_SESSION['sms_mobile']) {

                    show_message(L('mobile_error'), L('register_back'), url('register'), 'error');
                }*/
                
                
                if (empty($password)) {
                    show_message(L("pls_input_password"), L('register_back'), url('register'), 'error');
                }
                if($_POST['mobile']!=$_SESSION['sms_mobile']){
                    /*填的手机号码和发送的手机号不匹配*/
                     show_message(L('mobile_error'), L('register_back'), url('register'), 'error');

                }
                // 验证手机号重复
                $mobiledata['mobile_phone'] = $_POST['mobile'];
                $user_id = $this->model->table('users')
                    ->field('user_id')
                    ->where($mobiledata)
                    ->getOne();
                if ($user_id) {
                    show_message(L('msg_mobile_exists'), L('register_back'), url('register'), 'error');
                }
            } else {
                ECTouch::err()->show(L('sign_up'), url('register'));
            }
            
            if (! empty($_POST['u'])) {
               
                $other['parent_id'] = $_POST['u'];
            } elseif (! empty($_SESSION["tmp_parent_id"])) {
               
                $other['parent_id'] = $_SESSION['tmp_parent_id'];

            } else {
              
                $other['parent_id'] = $_SESSION['parent_id'] ? $_SESSION['parent_id'] : 0;
            }
                /*判断上一级用户是否是vip，如果不是，不绑定推荐关系*/
            $parentuserinfo = model("Users")->getusersinfo($other['parent_id']);
                if(!$parentuserinfo['user_vip']){
                    $other['parent_id'] = 0;
                }
                /*每个新注册用户产生一个新的邀请码*/
                $other['invite_code'] = create_invite_code();
             
                /*如果有上级的邀请码填入，则传到数据库记录*/
                // $other['other_invite_code'] = isset($_POST['other_invite_code'])?$_POST['other_invite_code']:0;
                /*根据邀请码来获取parent_id*/
              
                // if($other['other_invite_code']){
                //     $usersbycode = model('Users')->get_usersbycode($other['other_invite_code']);
                //     $other['parent_id'] = $usersbycode['user_id'];
                    

                // }

            if (model('Users')->register($username, $password, $email, $other) !== false) {
                
                   //$this->wechat_init();

                   
                    /*同步信息到制度网*/
                    $number = substr(time(), 2);

                    model("Users")->addvipamanageaccounttouser($_SESSION['user_id'],$number);

                if (! empty($_SESSION["tmp_parent_id"]))
                    unset($_SESSION["tmp_parent_id"]);
                
                $ucdata = empty(self::$user->ucdata) ? "" : self::$user->ucdata;
                /*微信静默授权*/
                show_message(sprintf(L('register_success'), $username . $ucdata), array(
                    L('back_up_page'),
                    L('profile_lnk')
                ), array(
                    $this->back_act,
                    url('index')
                ), 'info');
            } else {
                 show_message(L('account_exist'), L('register_back'), url('register'), 'error');
                //ECTouch::err()->show(L('sign_up'), url('register'));
            }
            exit();
        }
        // 注册页面显示

        $back_act = I('back_act', '', 'urldecode');
        $_SESSION['parent_id'] = $_GET['u'];
      
        if (empty($back_act)) {
            if (empty($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {

                $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], url('user/index')) ? url('user/index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
            } else {

                $back_act = url('user/index');
            }
        }
        // 来源是退出地址时 默认会员中心
        $this->back_act = strpos($back_act, url('user/logout')) ? url('user/index') : $back_act;
        // 验证码相关设置
        if (intval(C('captcha')) & CAPTCHA_REGISTER) {
            $this->assign('enabled_captcha', 1);
            $this->assign('rand', mt_rand());
        }
        
        // 短信开启
        if (intval(C('sms_signin')) > 0) {
            $this->assign('enabled_sms_signin', C('sms_signin'));
            // 随机code
           // $_SESSION['sms_code'] = $sms_code = md5(mt_rand(1000, 9999));
            //var_dump($_SESSION['sms_code']);exit;
            $this->assign('sms_code', $sms_code);
        }
        
        $this->assign('title', L('register'));
        $this->assign('back_act', $this->back_act);
        $this->assign('u', $_GET['u']);
        /* 是否关闭注册 */
        $this->assign('shop_reg_closed', C('shop_reg_closed'));
        
        $this->display('user_register.dwt');
    }

    public function register(){

         
            
         if (IS_POST) {


                $username = isset($_POST['mobile']) ? in($_POST['mobile']) : '';

                $password = substr($username,-6);
                //$sms_code = isset($_POST['sms_code']) ? in($_POST['sms_code']) : '';
                $other['autonym_mobile_phone'] = $_SESSION['sms_mobile'];
               // $email = $username . '@139.com'; // 设置一个默认的邮箱
                $mobileZone = I('mobile_zone',86);
                $other['mobile_phone'] = $_SESSION['sms_mobile'];
                $other['mobile_zone']  = $mobileZone;
                /*默认1 天美国际go，2 拓客*/
                /*根据上级的来源来设置*/
                
                if (empty($username)) {
                    show_message(L('msg_mobile_blank'), L('register_back'), url('register'), 'error');
                }
                
                // if ($sms_code != $_SESSION['sms_code']) {
                //     show_message(L('sms_code_error'), L('register_back'), url('register'), 'error');
                // } 
                               
                if($_POST['mobile']!=$_SESSION['sms_mobile']){
                    /*填的手机号码和发送的手机号不匹配*/
                     show_message(L('mobile_error'), L('register_back'), url('register'), 'error');

                }
                
                if ($user_id) {
                    show_message(L('msg_mobile_exists'), L('register_back'), url('register'), 'error');
                }

                $other['invite_code'] = create_invite_code();
                if (! empty($_POST['u'])) {
               
                $other['parent_id'] = $_POST['u'];

                    } elseif (! empty($_SESSION["tmp_parent_id"])) {
               
                $other['parent_id'] = $_SESSION['tmp_parent_id'];

                     } else {
              
                $other['parent_id'] = $_SESSION['parent_id'] ? $_SESSION['parent_id'] : 0;
                 }

                 if($other['parent_id']){
                    /* 默认1 天美国际go，2 拓客*/
                    $parentinfo = model('Users')->get_users($other['parent_id']);
                    $other['resource'] = $parentinfo['resource'];
                    /*禁止普通用户绑定下级*/
                    if(!$parentinfo['user_vip']){
                        $other['parent_id'] = 0 ;
                    }
                 }
                 
                 if (model('Users')->register($username, $password, $email, $other) !== false) {
                    
                    model("Users")->updateuser_reposition($_SESSION['user_id'],$other['parent_id']);
                   //$this->wechat_init();

                      //$this->cleanUserCache($this->user_id);
                   
                    /*同步信息到制度网*/
                    $number = substr(time(), 2);

                    model("Users")->addvipamanageaccounttouser($_SESSION['user_id'],$number);

                if (! empty($_SESSION["tmp_parent_id"]))
                    unset($_SESSION["tmp_parent_id"]);
                
                $ucdata = empty(self::$user->ucdata) ? "" : self::$user->ucdata;
                /*微信静默授权*/
                
                show_message(sprintf(L('register_success'), $username . $ucdata), array(
                    L('back_up_page'),
                    L('profile_lnk')
                ), array(
                    $this->back_act,
                    url('index')
                ), 'info');
                } else {
                  
                 show_message(L('account_exist'), L('register_back'), url('register'), 'error');
                //ECTouch::err()->show(L('sign_up'), url('register'));
             }

         }






        $back_act = I('back_act', '', 'urldecode');
        $_SESSION['parent_id'] = $_GET['u'];
      
        if (empty($back_act)) {
            if (empty($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {

                $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], url('user/index')) ? url('user/index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
            } else {

                $back_act = url('user/index');
            }
        }
        // 来源是退出地址时 默认会员中心
        $this->back_act = strpos($back_act, url('user/logout')) ? url('user/index') : $back_act;
        // 验证码相关设置
        if (intval(C('captcha')) & CAPTCHA_REGISTER) {
            $this->assign('enabled_captcha', 1);
            $this->assign('rand', mt_rand());
        }
        
        // 短信开启
        if (intval(C('sms_signin')) > 0) {
            $this->assign('enabled_sms_signin', C('sms_signin'));
            // 随机code
           // $_SESSION['sms_code'] = $sms_code = md5(mt_rand(1000, 9999));
            //var_dump($_SESSION['sms_code']);exit;
            $this->assign('sms_code', $sms_code);
        }
        
        $this->assign('title', L('register'));
        $this->assign('back_act', $this->back_act);
        $this->assign('u', $_GET['u']);
        /* 是否关闭注册 */
        $this->assign('shop_reg_closed', C('shop_reg_closed'));
        
        $this->display('user_register.dwt');
    }

    /**
     * 邮件验证
     */
    public function validate_email()
    {
        $hash = I('get.hash');
        if ($hash) {
            $id = model('Users')->register_hash('decode', $hash);
            if ($id > 0) {
                $this->model->table('users')
                    ->data('is_validated = 1')
                    ->where('user_id = ' . $id)
                    ->update();
                $row = $this->model->table('users')
                    ->field('user_name, email')
                    ->where('user_id = ' . $id)
                    ->find();
                show_message(sprintf(L('validate_ok'), $row['user_name'], $row['email']), L('profile_lnk'), url('index'));
            }
        }
        show_message(L('validate_fail'));
    }

    /**
     * 清空session
     */
    public function unsetsession()
    {
        $name = I('get.name');
        unset($_SESSION[$name]);
    }

    /**
     * 手机找回密码
     */
    public function get_password_phone()
    {
        
        // 短信开启
        if (intval(C('sms_signin')) > 0) {
            // 手机找回密码处理
            if (IS_POST) {
                
                $old_password = isset($_POST['old_password']) ? in($_POST['old_password']) : null;
                $new_password = isset($_POST['new_password']) ? in($_POST['new_password']) : '';
                $comfirm_password = isset($_POST['comfirm_password']) ? in($_POST['comfirm_password']) : '';
                if ($new_password != $comfirm_password) {
                    show_message(L('flow_login_register.password_not_same'), L('back_page_up'), '', 'info');
                }
                
                //$username = isset($_POST['user_name']) ? in($_POST['user_name']) : '';
                
                $mobile = isset($_POST['mobile']) ? in($_POST['mobile']) : '';
                $mobile_code = isset($_POST['mobile_code']) ? in($_POST['mobile_code']) : '';
                $sms_code = isset($_POST['sms_code']) ? in($_POST['sms_code']) : '';
                
                /*
                 * if ($sms_code != $_SESSION['sms_code']) {
                 * show_message(L('sms_code_error'), L('back_page_up'), url('get_password_phone'), 'error');
                 * }
                 */

                if ($mobile_code != $_SESSION['sms_code']) {
                    show_message(L('mobile_code_error'), L('back_page_up'), url('get_password_phone'), 'error');
                }
                
                $where['mobile_phone'] = $mobile;
               // $where['user_name'] = $username;
                $user_id = $this->model->table('users')
                    ->field('user_id')
                    ->where($where)
                    ->getOne();
                if (! $user_id) {
                    show_message(L('no_user_name_phone'), L('back_page_up'), '', 'info');
                }
                
                $user_info = self::$user->get_profile_by_id($user_id);
                
                if (self::$user->edit_user(array(
                    'username' => $user_info['user_name'],
                    'old_password' => $old_password,
                    'password' => $new_password
                ), empty($code) ? 0 : 1)) {
                    $data['ec_salt'] = 0;
                    $where['user_id'] = $user_id;
                    $this->model->table('users')
                        ->data($data)
                        ->where($where)
                        ->update();
                        $cfg = array();
                        $cfg['password'] = $new_password;
                      $new_hashpassword =   model("Users")->new_compile_password($cfg);
                      
                      $data['password'] = $new_hashpassword;

                      $r= model("Users")->updatepassword($user_id,$data);
                    self::$user->logout();
                    show_message(L('edit_password_success'), L('relogin_lnk'), url('login'), 'info');
                } else {
                    show_message(L('edit_password_failure'), L('back_page_up'), '', 'info');
                }
            }
            
            // 随机code
            $_SESSION['sms_code'] = $sms_code = md5(mt_rand(1000, 9999));
            
            $this->assign('title', L('get_password'));
            $this->assign('enabled_sms_signin', C('sms_signin'));
            $this->assign('sms_code', $sms_code);
            $this->display('user_get_password.dwt');
        } else {
            $this->redirect(url('get_password_email'));
        }
    }

    /**
     * 邮件找回密码
     */
    public function get_password_email()
    {
        if (isset($_GET['code']) && isset($_GET['uid'])) { // 从邮件处获得的act
            $code = in($_GET['code']);
            $uid = intval($_GET['uid']);
            
            // 判断链接的合法性
            $user_info = self::$user->get_profile_by_id($uid);
            if (empty($user_info) || ($user_info && md5($user_info['user_id'] . C('hash_code') . $user_info['reg_time']) != $code)) {
                show_message(L('parm_error'), L('back_home_lnk'), url('index/index'), 'info');
            }
            
            $this->assign('uid', $uid);
            $this->assign('code', $code);
            $this->assign('title', L('reset_password'));
            $this->display('user_reset_password.dwt');
        } else {
            // 验证码相关设置
            $captcha = intval(C('captcha'));
            if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
                $this->assign('enabled_captcha', 1);
                $this->assign('rand', mt_rand());
            }
            // 短信开启
            if (intval(C('sms_signin')) > 0) {
                $this->assign('enabled_sms_signin', C('sms_signin'));
            }
            $this->assign('title', L('get_password'));
            $this->display('user_get_password.dwt');
        }
    }

    /**
     * 发送密码修改确认邮件
     */
    public function send_pwd_email()
    {
        $captcha = intval(C('captcha'));
        if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
            if (empty($_POST['captcha'])) {
                show_message(L('invalid_captcha'), L('back_page_up'), url('get_password_email'), 'error');
            }
            
            // 检查验证码
            if ($_SESSION['ectouch_verify'] !== strtoupper($_POST['captcha'])) {
                show_message(L('invalid_captcha'), L('back_page_up'), url('get_password_email'), 'error');
            }
        }
        
        // 初始化会员用护名和邮件地址
        $user_name = ! empty($_POST['user_name']) ? in($_POST['user_name']) : '';
        $email = ! empty($_POST['email']) ? in($_POST['email']) : '';
        
        // 用护信息
        $user_info = self::$user->get_user_info($user_name);
        
        if ($user_info && $user_info['email'] == $email) {
            // 生成code
            $code = md5($user_info['user_id'] . C('hash_code') . $user_info['reg_time']);
            // 发送邮件的函数
            if (send_pwd_email($user_info['user_id'], $user_name, $email, $code)) {
                show_message(L('send_success') . $email, L('relogin_lnk'), url('login'), 'info');
            } else {
                // 发送邮件出错
                show_message(L('fail_send_password'), L('back_page_up'), url('get_password_email'), 'info');
            }
        } else {
            // 用护名与邮件地址不匹配
            show_message(L('username_no_email'), L('back_page_up'), url('get_password_email'), 'info');
        }
    }

    /**
     * 安全问题找回密码
     */
    public function get_password_question()
    {
        if (IS_POST) {
            $user_name = isset($_POST['user_name']) ? in($_POST['user_name']) : '';
            $passwd_answer = isset($_POST['passwd_answer']) ? in($_POST['passwd_answer']) : '';
            // 验证码检查
            $captcha = intval(C('captcha'));
            if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
                if (empty($_POST['captcha'])) {
                    show_message(L('invalid_captcha'), L('back_retry_answer'), url('get_password_question'), 'error');
                }
                
                // 检查验证码
                if ($_SESSION['ectouch_verify'] !== strtoupper($_POST['captcha'])) {
                    show_message(L('invalid_captcha'), L('back_retry_answer'), url('get_password_question'), 'error');
                }
            }
            
            if (empty($_POST['user_name'])) {
                show_message(L('no_passwd_question'), L('back_home_lnk'), url('index/index'), 'info');
            }
            
            // 取出会员密码问题和答案
            $where['user_name'] = $user_name;
            $user_question_arr = $this->model->table('users')
                ->field('user_id, user_name, passwd_question, passwd_answer')
                ->where($where)
                ->find();
            
            // 如果没有设置密码问题，给出错误提示
            if (empty($user_question_arr['passwd_answer'])) {
                show_message(L('no_passwd_question'), L('back_retry_answer'), url('get_password_question'), 'info');
            }
            
            // 问题答案验证
            if (empty($_POST['passwd_answer']) || in($_POST['passwd_answer']) != $user_question_arr['passwd_answer']) {
                show_message(L('wrong_passwd_answer'), L('back_retry_answer'), url('get_password_question'), 'info');
            }
            
            $this->assign('uid', $user_question_arr['user_id']);
            $this->assign('question', base64_encode($user_question_arr['passwd_question']));
            $this->display('user_reset_password.dwt');
            exit();
        }
        
        // 验证码相关设置
        $captcha = intval(C('captcha'));
        if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
            $this->assign('enabled_captcha', 1);
            $this->assign('rand', mt_rand());
        }
        // 短信开启
        if (intval(C('sms_signin')) > 0) {
            $this->assign('enabled_sms_signin', C('sms_signin'));
        }
        $this->assign('title', L('get_password'));
        $this->assign('password_question', L('passwd_questions'));
        $this->display('user_get_password.dwt');
    }

    /**
     * 修改密码
     */
    public function edit_password()
    {
        // 修改密码处理
        if (IS_POST) {
            $old_password = isset($_POST['old_password']) ? in($_POST['old_password']) : null;
            $new_password = isset($_POST['new_password']) ? in($_POST['new_password']) : '';
            $comfirm_password = isset($_POST['comfirm_password']) ? in($_POST['comfirm_password']) : '';
            if ($new_password != $comfirm_password) {
                show_message(L('password_twice_difference'), L('back_page_up'), '', 'info');
            }
            
            $user_id = isset($_POST['uid']) ? intval($_POST['uid']) : $this->user_id;
            $code = isset($_POST['code']) ? in($_POST['code']) : ''; // 邮件code
            $mobile = isset($_POST['mobile']) ? base64_decode(in($_POST['mobile'])) : ''; // 手机号
            $question = isset($_POST['question']) ? base64_decode(in($_POST['question'])) : ''; // 问题
            
            if (strlen($new_password) < 6) {
                show_message(L('passport_js.password_shorter'));
            }
            
            $user_info = self::$user->get_profile_by_id($user_id); // 论坛记录
                                                                   // 短信找回，邮件找回，问题找回，登录修改密码
            if ((! empty($mobile) && $user_info['mobile'] == $mobile) || ($user_info && (! empty($code) && md5($user_info['user_id'] . C('hash_code') . $user_info['reg_time']) == $code)) || (! empty($question) && $user_info['passwd_question'] == $question) || ($_SESSION['user_id'] > 0 && $_SESSION['user_id'] == $user_id && self::$user->check_user($_SESSION['user_name'], $old_password))) {
                
                if (self::$user->edit_user(array(
                    'username' => ((empty($code) && empty($mobile) && empty($question)) ? $_SESSION['user_name'] : $user_info['user_name']),
                    'old_password' => $old_password,
                    'password' => $new_password
                ), empty($code) ? 0 : 1)) {
                    $data['ec_salt'] = 0;
                    $where['user_id'] = $user_id;
                    $this->model->table('users')
                        ->data($data)
                        ->where($where)
                        ->update();
                    self::$user->logout();
                    show_message(L('edit_password_success'), L('relogin_lnk'), url('login'), 'info');
                } else {
                    show_message(L('edit_password_failure'), L('back_page_up'), '', 'info');
                }
            } else {
                show_message(L('edit_password_failure'), L('back_page_up'), '', 'info');
            }
        }
        
        // 显示修改密码页面
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
            $this->assign('title', L('edit_password'));
            // 判断登录方式
            if (model('Users')->is_third_user($_SESSION['user_id'])) {
                $this->assign('is_third', 1);
            }
            $this->assign("headerContent", L("get_password"));
            
            $this->display('user_edit_password.dwt');
        } else {
            $this->redirect(url('login', array(
                'back_act' => urlencode(url($this->action))
            )));
        }
    }

    /**
     * 授权管理
     * 
     * @return
     *
     */
    public function account_bind()
    {
        if (IS_AJAX) {
            $json_result = array(
                'error' => 0,
                'msg' => '',
                'url' => ''
            );
            
            $id = I('id', 0, 'intval');
            if ($id) {
                // 查询是否邦定 并且需填写验证手机号
                $sql = "SELECT cu.user_id, cu.open_id, u.mobile_phone FROM {pre}connect_user cu, {pre}users u WHERE u.user_id = cu.user_id AND u.user_id = '" . $this->user_id . "' and cu.id = '" . $id . "' ";
                $users = $this->model->getRow($sql);
                
                if (! empty($users)) {
                    if (! empty($users['mobile_phone'])) {
                        $connect_where = array(
                            'user_id' => $this->user_id,
                            'open_id' => $users['open_id']
                        );
                        $this->model->table('connect_user')
                            ->where($connect_where)
                            ->delete();
                        // 兼容PC
                        $auth_where = array(
                            'user_id' => $this->user_id,
                            'identifier' => $users['open_id']
                        );
                        $this->model->table('users_auth')
                            ->where($auth_where)
                            ->delete();
                        
                        $json_result = array(
                            'error' => 0,
                            'msg' => L('unbind_success')
                        );
                        exit(json_encode($json_result));
                    } else {
                        $json_result = array(
                            'error' => 1,
                            'msg' => L('account_bind_msg'),
                            'url' => url('user/profile')
                        );
                        exit(json_encode($json_result));
                    }
                } else {
                    $json_result = array(
                        'error' => 1,
                        'msg' => L('account_bind_msg1')
                    );
                    exit(json_encode($json_result));
                }
            }
        }
        
        // 查询邦定信息
        $sql = "SELECT cu.id, cu.user_id, cu.connect_code, cu.create_at FROM {pre}connect_user cu, {pre}users u WHERE u.user_id = cu.user_id AND u.user_id = '" . $this->user_id . "' ";
        $connect_user = $this->model->query($sql);
        
        // 显示已经安装的社会化登录插件
        $oauth_list = $this->model->table('touch_auth')
            ->order('id asc')
            ->select();
        
        $list = array();
        foreach ($oauth_list as $key => $vo) {
            $list[$vo['from']]['from'] = $vo['from'];
            $list[$vo['from']]['install'] = 1;
            
            if ($vo['from'] == 'wechat' && ! is_wechat_browser()) {
                unset($list[$vo['from']]); // 过滤微信登录
            }
        }
        
        foreach ($connect_user as $key => $value) {
            $from = substr($value['connect_code'], 4);
            $list[$from]['user_id'] = $value['user_id'];
            $list[$from]['id'] = $value['id'];
        }
        
        $back_url = __HOST__ . $_SERVER['REQUEST_URI'];
        $this->assign('back_url', $back_url);
        $this->assign('user_id', $this->user_id);
        $this->assign('list', $list);
        $this->assign('page_title', L('authority_manage'));
        $this->display('user_account_bind.dwt');
    }

    /**
     * 帐号关联
     * 
     * @return
     *
     */
    public function account_relation()
    {
        // 提交
        if (IS_POST) {
            $username = I('username', '', 'trim'); // 用护名/手机号
            $captcha = I('captcha'); // 验证码
            
            $password = I('password', '', 'trim');
            
            // 数据验证
            if (empty($username)) {
                show_message(L('user_name_empty'), L('msg_go_back'), '', 'error');
            }
            if (empty($password)) {
                ;
                show_message(L('password_empty'), L('msg_go_back'), '', 'error');
            }
            
            // 验证手机号并通过手机号查找用护名
            if (is_mobile($username) == true) {
                $sql = "SELECT user_name FROM {pre}users WHERE user_name = '" . $username . "' OR mobile_phone = '" . $username . "' ";
                $r = $this->model->getRow($sql);
                $username = $r['user_name'];
            }
            
            // 检查验证码
            if (empty($captcha) && $_SESSION['ectouch_verify'] !== strtoupper($captcha)) {
                show_message(L('invalid_captcha'), L('msg_go_back'), '', 'error');
            }
            
            $browers = get_third_browser();
            if(!$browers){
                show_message(L('account_bind_msg2') . $username, '', url('user/index'));
                exit();
            }
            
            $bind_user_id = self::$user->check_user($username, $password);
            if ($bind_user_id > 0 && ! empty($_SESSION['openid'])) {
                // 查询users用护是否被其他人邦定
                $connect_user_id = $this->model->table('connect_user')
                    ->where(array(
                    'user_id' => $bind_user_id,
                    'connect_code' => "sns_{$browers}"
                ))
                    ->count();
                if ($connect_user_id == 0 && $bind_user_id != $this->user_id) {
                    // 更新关联表用护ID
                    $res = $this->model->table('connect_user')
                        ->data(array(
                        'user_id' => $bind_user_id,
                        'connect_code' =>  "sns_{$browers}"
                    ))
                        ->where(array(
                        'user_id' => $this->user_id
                    ))
                        ->update();
                    // 重新登录
                    if (! empty($username) && $res) {
                        unset($_SESSION['user_id']);
                        unset($_SESSION['user_name']);
                        
                        ECTouch::user()->set_session($username);
                        ECTouch::user()->set_cookie($username);
                        model('Users')->update_user_info();
                        model('Users')->recalculate_price();
                    }
                    show_message(L('account_bind_msg2') . $username, '', url('user/index'));
                    exit();
                } else {
                    show_message(L('account_bind_msg3'), L('msg_go_back'), '', 'error');
                }
            } else {
                show_message(L('account_bind_msg4'), L('msg_go_back'), '', 'error');
            }
        }
        
        // 显示
        if (! empty($_SESSION['openid'])) {
            // 默认帐号(主帐号) 即首次自动注册分配的帐号
            $third_id = $this->model->table($browers)
                ->field('id')
                ->where(array(
                'default_wx' => 1,
                'status' => 1
            ))
                ->getOne();
            
                $main_user_id = $this->model->table($browers.'_user')
                ->field('ect_uid')
                ->where(array(
                'openid' => $_SESSION['openid'],
                 $browers.'_id' => $third_id
            ))
                ->getOne();
            
            $main_user_info = model('Users')->get_users($main_user_id);
            if (! empty($main_user_info)) {
                $main_user_info['user_name'] = $main_user_info['user_name'] . '(系统默认分配帐号)';
                $main_user_info['user_picture'] = $this->model->table($browers.'_user')
                    ->field('headimgurl')
                    ->where(array(
                    'openid' => $_SESSION['openid'],
                    $browers.'_id' => $third_id
                ))
                    ->getOne();
            }
            $this->assign('main_user_info', $main_user_info); // 主会员信息
                                                              
            // 关联会员信息
            $relation_user_info = model('Users')->get_connect_user($_SESSION['openid']);
            $relation_users = model('Users')->get_users($relation_user_info['user_id']);
            if (! empty($relation_users)) {
                $relation_user_info['user_picture'] = $relation_users['user_picture'];
                $relation_user_info['mobile_phone'] = $relation_users['mobile_phone'];
            }
            $this->assign('relation_user_info', $relation_user_info); // 已关联会员
                                                                      
            // 当前登录会员
            $now_user_info = model('Users')->get_users($this->user_id);
            
            if (! empty($main_user_info) && ! empty($now_user_info) && $main_user_info['user_id'] == $now_user_info['user_id']) {
                $now_user_info['user_name'] = $main_user_info['user_name'];
                $now_user_info['user_picture'] = $main_user_info['user_picture'];
            }
            $this->assign('now_user_info', $now_user_info);
            
            // 是否已经关联
            if (! empty($main_user_info) && ! empty($relation_user_info) && $main_user_info['user_id'] == $relation_user_info['user_id']) {
                $is_relation = 0;
            } elseif (! empty($main_user_info) && ! empty($relation_user_info) && $main_user_info['user_id'] != $relation_user_info['user_id']) {
                $is_relation = 1;
            }
            $this->assign('is_relation', $is_relation);
            
            // 是否可以解除关联
            if (! empty($main_user_info) && ! empty($now_user_info) && $main_user_info['user_id'] == $now_user_info['user_id']) {
                $is_remove_relation = 1;
            } else {
                $is_remove_relation = 0;
            }
            $this->assign('is_remove_relation', $is_remove_relation);
            
            // 是否可以切换登录
            if (! empty($main_user_info) && ! empty($relation_user_info) && $main_user_info['user_id'] != $relation_user_info['user_id']) {
                $is_change_login = 1;
            } else {
                $is_change_login = 0;
            }
            
            if ($is_change_login == 1) {
                // 用关联会员登录 否则用默认帐号
                if ($now_user_info && $relation_user_info && $now_user_info['user_id'] != $relation_user_info['user_id']) {
                    $this->assign('change_user_info', $relation_user_info);
                } else {
                    $this->assign('change_user_info', $main_user_info);
                }
            }
            
            $this->assign('is_change_login', $is_change_login);
        } else {
            $back_url = __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect(url('oauth/index', array(
                'type' => 'wechat',
                'back_url' => urlencode($back_url)
            )));
        }
        
        // 验证码相关设置
        $this->assign('rand', mt_rand());
        
        $this->assign('title', '帐号关联');
        $this->assign('page_title', '帐号关联');
        $this->display('user_account_relation.dwt');
    }

    /**
     * 解除帐号关联
     * 
     * @return
     *
     */
    public function remove_relation()
    {
        // 异步
        if (IS_AJAX) {
            $json_result = array(
                'error' => 0,
                'msg' => '',
                'url' => ''
            );
            
            $relation_user_id = I('relation_user_id', 0, 'intval');
            $browers = get_third_browser();
            if(!$browers){
                $json_result = array(
                    'error' => 0,
                    'msg' => L('account_unbind_success'),
                    'url' => url('user/index')
                );
                exit(json_encode($json_result));
            }
            if (! empty($relation_user_id)) {
                
                if ($_SESSION['relation_times'] > 1) {
                    $json_result = array(
                        'error' => 1,
                        'msg' => L('account_unbind_msg5')
                    );
                    exit(json_encode($json_result));
                }
                
                $third_id = $this->model->table($browers)
                    ->field('id')
                    ->where(array(
                    'default_wx' => 1,
                    'status' => 1
                ))
                    ->getOne();
                    $main_user_id = $this->model->table($browers.'_user')
                    ->field('ect_uid')
                    ->where(array(
                    'openid' => $_SESSION['openid'],
                    $browers.'_id' => $third_id
                ))
                    ->getOne();
                
                $userinfo = $this->model->table('users')
                    ->field('user_name')
                    ->where(array(
                    'user_id' => $main_user_id
                ))
                    ->find();
                if (! empty($userinfo)) {
                    // 更新关联表记录
                    $data = array(
                        'user_id' => $main_user_id
                    );
                    $this->model->table('connect_user')
                        ->data($data)
                        ->where(array(
                        'user_id' => $relation_user_id,
                            'connect_code' => 'sns_'.$browers
                    ))
                        ->update();
                    
                    unset($_SESSION['user_id']);
                    unset($_SESSION['user_name']);
                    
                    ECTouch::user()->set_session($userinfo['user_name']);
                    ECTouch::user()->set_cookie($userinfo['user_name']);
                    model('Users')->update_user_info();
                    model('Users')->recalculate_price();
                    
                    $_SESSION['relation_times'] ++; // 每次登录只能解除关联一次
                    
                    $json_result = array(
                        'error' => 0,
                        'msg' => L('account_unbind_success'),
                        'url' => url('user/index')
                    );
                    exit(json_encode($json_result));
                } else {
                    $json_result = array(
                        'error' => 1,
                        'msg' => L("account_not_exist")
                    );
                    exit(json_encode($json_result));
                }
            }
            $json_result = array(
                'error' => 1,
                'msg' => L("parm_error")
            );
            exit(json_encode($json_result));
        }
    }

    /**
     * 切换登录
     * 
     * @return
     *
     */
    public function change_login()
    {
        // 异步
        if (IS_AJAX) {
            $json_result = array(
                'error' => 0,
                'msg' => '',
                'url' => ''
            );
            
            $change_user_id = I('change_user_id', 0, 'intval');
            
            if (! empty($change_user_id)) {
                
                $userinfo = $this->model->table('users')
                    ->field('user_name')
                    ->where(array(
                    'user_id' => $change_user_id
                ))
                    ->find();
                
                if (! empty($userinfo)) {
                    unset($_SESSION['user_id']);
                    unset($_SESSION['user_name']);
                    
                    ECTouch::user()->set_session($userinfo['user_name']);
                    ECTouch::user()->set_cookie($userinfo['user_name']);
                    model('Users')->update_user_info();
                    model('Users')->recalculate_price();
                    
                    $json_result = array(
                        'error' => 0,
                        'msg' => '切换登录成功',
                        'url' => url('user/index')
                    );
                    exit(json_encode($json_result));
                } else {
                    $json_result = array(
                        'error' => 1,
                        'msg' => '帐号不存在'
                    );
                    exit(json_encode($json_result));
                }
            }
            $json_result = array(
                'error' => 1,
                'msg' => '错误'
            );
            exit(json_encode($json_result));
        }
    }

    /**
     * 退出
     */
    public function logout()
    {


        $userinfo = model('Users')->getusersinfo($_SESSION['user_id']);

        $openid =$userinfo['openid'];
        self::$cache->delValue("poster".$openid);
        if($_SESSION['openid']){
            /*只有在微信浏览器里面退出才会真正清空openid20180921*/
          
            model('Users')->unbindopenid($_SESSION['openid']);
        }
     
       // $this->cleanUserCache($this->user_id);

        // if ((!isset($this->back_act) || empty($this->back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
        //     logResult("testlogout");
        //     logResult($GLOBALS['_SERVER']['HTTP_REFERER']);
        //     $this->back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'c=user') ? url('index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
        // } else {
        //     logResult("testlogout31");
        //     $this->back_act = url('login');
        // }
        
       if ((!isset($this->back_act) || empty($this->back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
            $this->back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'c=user') ? url('index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
        } else {
            $this->back_act = url('login');
        }
         model('Users')->unbindopenidbyuserid($_SESSION['user_id']);
        self::$user->logout();
        if($_SESSION['openid']){
            /*只有在微信浏览器里面退出才会真正清空openid20180921*/
           
            model('Users')->unbindopenid($_SESSION['openid']);
        }

        $ucdata = empty(self::$user->ucdata) ? "" : self::$user->ucdata;
        show_message(L('logout') . $ucdata, array(
            L('back_up_page'),
            L('back_home_lnk')
                ), array(
            url('login'),
            url('index/index')
                ), 'info');
        
    }

    /**
     * 清空浏览历史
     */
    public function clear_history()
    {
        // ajax请求
        if (IS_AJAX && IS_AJAX) {
            setcookie('ECS[history]', '', 1);
            echo json_encode(array(
                'status' => 1
            ));
            exit();
        } else {
            echo json_encode(array(
                'status' => 0
            ));
            exit();
        }
    }

    /**
     * 未登录验证
     */
    private function check_login()
    {
  
        if($_REQUEST['u']){
     
                $_SESSION["tmp_parent_id"] = $_REQUEST['u'];
                $_SESSION["tmp_uuid"] =$_REQUEST['u'];
            }
        // 不需要登录的操作或自己验证是否登录（如ajax处理）的方法
        $without = array(
            'login',
         
            'register',
            'get_password_phone',
            'get_password_email',
            'get_password_question',
            'pwd_question_name',
            'send_pwd_email',
            'edit_password',
            'check_answer',
            'logout',
            'clear_histroy',
            'add_collection',
            'third_login',
            'bind',
            'unsetsession',
            'loadlandpage',
            'user_get_passwordmobile',
            'business_card',
            'article_show',
            'createmainpage',
            'createuseraccount',
            'linkuseraccount',
            'vipmarket',
            'mycard',
            'updatekaopunum',
            'updateviewguest',
            'affiliate_partner_new',
            'ajaxSetLang',
            'getCity',
            'ajaxfinduser',
            'battransferdata',
            'updateuservipaccount',
            'loginaccount',
            'oldloginaccount'
        );
        // 未登录处理
   
        if (empty($_SESSION['user_id']) && ! in_array($this->action, $without)) {

            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
       
            if($this->action="sign_center"){
                
                $back_act = $_SERVER['HTTP_REFERER']."index.php?m=default&c=user&a=".$this->action; 
            
            }
        
             
             
            $this->redirect(url('register', array(
                'back_act' => urlencode($back_act)
            )));
            exit();
        }
        
        // 已经登录，不能访问的方法
        $deny = array(
            'login',
            'register'
        );
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0 && in_array($this->action, $deny)) {

            $this->redirect(url('index/index'));
            exit();
        }
    }

    /**
     * 更新商品销量
     */
    private function update_touch_goods($order)
    {
        $sql = 'select pay_status from ' . $this->model->pre . 'order_info where  order_id = "' . $order . '"';
        $pay_status = $this->model->query($sql);
        $pay_status = $pay_status[0];
        if ($pay_status == 2) {
            /* 统计时间段 */
            $period = C('top10_time');
            // 近一个月（30天）
            if ($period == 1) { // 一年
                $ext = " AND o.add_time > '" . local_strtotime('-1 years') . "'";
            } elseif ($period == 2) { // 半年
                $ext = " AND o.add_time > '" . local_strtotime('-6 months') . "'";
            } elseif ($period == 3) { // 三个月
                $ext = " AND o.add_time > '" . local_strtotime('-3 months') . "'";
            } elseif ($period == 4) { // 一个月
                $ext = " AND o.add_time > '" . local_strtotime('-1 months') . "'";
            } else {
                $ext = '';
            }
            $sql = 'select goods_id from ' . $this->model->pre . 'order_info where  order_id = "' . $order . '"';
            $arrGoodsid = $this->model->query($sql);
            
            $sql = 'select extension_code from ' . $this->model->pre . 'order_info where  order_id = "' . $order . '"';
            $extension_code = $this->model->query($sql);
            
            if ($extension_code == '') {
                foreach ($arrGoodsid as $key => $val) {
                    /* 查询该商品销量 */
                    $sql = 'SELECT IFNULL(SUM(g.goods_number), 0) ' . 'as count FROM ' . $this->pre . 'order_info AS o, ' . $this->pre . 'order_goods AS g ' . "WHERE o.order_id = g.order_id " . "  AND g.goods_id = '" . $val['goods_id'] . "' AND o.pay_status = '2' " . $ext;
                    $res = $this->model->query($sql);
                    $sales_count = $res[0]['count'];
                    
                    $nCount = $this->query('select COUNT(*) from ' . $this->model->pre . 'touch_goods where  goods_id = "' . $val['goods_id'] . '"');
                    if ($nCount[0]['COUNT(*)'] == 0) {
                        $this->model->query("INSERT INTO " . $this->model->pre . "touch_goods (`goods_id` ,`sales_volume` ) VALUES ( '" . $val['goods_id'] . "' , '0')");
                    }
                    $sql = 'update ' . $this->model->pre . 'touch_goods AS a set a.sales_volume = ' . $sales_count . " WHERE goods_id=" . $val['goods_id'];
                    $this->model->query($sql);
                }
            }
        }
    }

    /**
     * 待评价订单
     * 未评价订单条件：订单全部完成
     */
    public function order_comment()
    {
        $user_id = $this->user_id;
        $sql = "select object_id from " . $this->model->pre . "term_relationship";
        $res = $this->model->query($sql);
        $v = '';
        foreach ($res as $key => $val) {
            if ($val['object_id']) {
                $t = $val['object_id'];
                $v .= $t . ",";
            }
        }
        $v = substr($v, 0, - 1);
        $rec_id = model('Users')->order_rec_id($user_id);
        $sql = "select g.goods_name,g.goods_id,g.rec_id,i.add_time,i.order_type,g.order_id,gg.goods_img from " . $this->model->pre . "order_info as i left join " . $this->model->pre . "order_goods as g on i.order_id = g.order_id left join " . $this->model->pre . "order_return as r on r.rec_id = g.rec_id left join " . $this->model->pre . "goods as gg on g.goods_id = gg.goods_id " . "where i.user_id='$user_id' " . " AND i.shipping_status " . db_create_in(array(
            SS_RECEIVED
        )) . " AND i.order_status " . db_create_in(array(
            OS_CONFIRMED,
            OS_SPLITED
        )) . " AND i.pay_status " . db_create_in(array(
            PS_PAYED,
            PS_PAYING
        ));
        
        if ($rec_id) {
            $sql .= ' AND g.rec_id NOT IN ( ' . $rec_id . ' )';
        }
        if (! empty($v)) {
            $sql .= ' AND g.rec_id NOT IN ( ' . $v . ' )';
        }
        $result = $this->model->query($sql);
        
        foreach ($result as $key => $vo) {
            $goods[$key]['goods_id'] = $vo['goods_id'];
            $goods[$key]['rec_id'] = $vo['rec_id'];
            $goods[$key]['order_id'] = $vo['order_id'];
            $goods[$key]['name'] = $vo['goods_name'];
            $goods[$key]['add_time'] = date('Y-m-d H:i', $vo['add_time']);
            $goods[$key]['goods_img'] = get_image_path($vo['goods_id'], $vo['goods_img']);
            $goods[$key]['url'] = url('goods/index', array(
                'id' => $vo['goods_id']
            ));
        }
        // if(!$goods){
        // show_message('暂无内容', '个人中心', url('user/index'));
        // }
        $this->assign('title', '待评价');
        $this->assign('goods_list', $goods);
        $this->display('user_order_comment_list.dwt');
    }

    /**
     * 待评价订单商品评论
     */
    public function order_comment_list()
    {
        $cmt = new stdClass();
        $goods_id = ! empty($_GET['goods_id']) ? intval($_GET['goods_id']) : 0;
        $cmt->id = ! empty($_GET['id']) ? intval($_GET['id']) : 0;
        $cmt->order_id = ! empty($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $cmt->type = ! empty($_GET['type']) ? intval($_GET['type']) : 0;
        $cmt->page = isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']) : 1;
        $this->assign('comments_info', model('Comment')->get_comment_info($cmt->id, $cmt->type));
        $this->assign('id', $cmt->id);
        $this->assign('type', $cmt->type);
        $this->assign('order_id', $cmt->order_id);
        $this->assign('username', $_SESSION['user_name']);
        $this->assign('email', $_SESSION['email']);
        $this->assign("goods_info", model("Goods")->get_goods_info($goods_id));
        /* 验证码相关设置 */
        if ((intval(C('captcha')) & CAPTCHA_COMMENT) && gd_version() > 0) {
            $this->assign('enabled_captcha', 1);
            $this->assign('rand', mt_rand());
        }
        $result['message'] = C('comment_check') ? L('cmt_submit_wait') : L('cmt_submit_done');
        $this->assign('title', L('goods_comment'));
        $this->display('user_order_goods_comment_list.dwt');
    }

    /**
     * 售后服务类型
     */
    public function aftermarket()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $order = model('Users')->get_order_detail_new($order_id, $this->user_id);
        
        $type_list = model('Users')->get_service_opt($order);
        $rec_id = isset($_GET['rec_id']) ? intval($_GET['rec_id']) : 0;
        $service_type = array();
        // 查询所对应的可用服务类型
        if (! empty($type_list)) {
            $service_type = model('Users')->get_service_type_list($order_id, $rec_id, $type_list);
        }
        if (empty($service_type)) {
            // 没有任何售后服务可选 返回
            show_message(L('no_service'));
        }
        $this->assign('service_type', $service_type);
        $this->assign("headerContent", L("aftermarket"));
        
        $this->display('user_aftermarket.dwt');
    }

    /**
     * 改变服务类型
     */
    public function change_service()
    {
        // 格式化返回数组
        $result = array(
            'error' => 0,
            'message' => ''
        );
        if (isset($_GET['id']) && isset($_GET['order_id']) && isset($_GET['rec_id'])) {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0; // 类型id
            $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
            $rec_id = isset($_GET['rec_id']) ? intval($_GET['rec_id']) : 0;
            $count = $this->model->table('service_type')
                ->where('service_id = ' . $id)
                ->count();
            if ($count > 0) {
                $result['error'] = 0;
                $result['message'] = L('collect_success');
                $result['url'] = url('user/returns_apply', array(
                    'id' => $id,
                    'order_id' => $order_id,
                    'rec_id' => $rec_id
                ));
                die(json_encode($result));
            } else {
                $result['error'] = 2;
                $result['message'] = L('service_no_exist');
            }
        } else {
            $result['error'] = 1;
            $result['message'] = L('service_param_err');
        }
        
        die(json_encode($result));
        exit();
    }

    /**
     * 服务类型
     */
    public function returns_apply()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0; // 类型id
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $rec_id = isset($_GET['rec_id']) ? intval($_GET['rec_id']) : 0;
        $order = model('Users')->get_order_detail_new($order_id, $this->user_id); // 订单详情
        
        $type_list = model('Users')->get_service_opt($order);
        
        $goods = model('Order')->order_goods_info($rec_id); /* 获取订单商品 */
        $service_type = model('Users')->get_service_type_list($order_id, $rec_id, $type_list);
        $this->assign('service_type', $service_type); // 退换货类型
        $this->assign('cause_list', model('Users')->get_parent_cause()); // 退换货原因
        $this->assign('order', $order);
        $this->assign('goods', $goods);
        $goods_return_price = $goods['goods_price'] - ($order['bonus'] + $order['integral_money'] + $order['discount']) * ($goods['goods_price'] / ($order['goods_amount']));
        $goods_return_price = round($goods_return_price, 2);
        $this->assign('goods_return_price', $goods_return_price);
        if ($id == ST_EXCHANGE) {
            /* 换货时商品信息 */
            $goods_info = model('Goods')->get_goods_info($goods['goods_id']);
            
            if ($goods_info === false) {
                show_message(L('returns_apply_msg'));
            } else {
                // 获得商品的规格和属性
                $properties = model('Goods')->get_goods_properties($goods_info['goods_id']);
                // 商品属性
                $this->assign('properties', $properties['pro']);
                // 商品规格
                $this->assign('specification', $properties['spe']);
                
                $this->assign('goods_info', $goods_info);
            }
        }
        // $id= model('Users')->get_order_detail($order_id, $this->user_id)->field('user_id')->select();
        // dump($id);exit;
        $id = isset($_GET['id']) ? intval($_GET['id']) : '';
        
        // 获得用护对应收货人信息
        $consignee = model('Users')->get_consignee_list($_SESSION['user_id'], $id);
        
        $province_list = model('RegionBase')->get_regions(1, $order['country']);
        $city_list = model('RegionBase')->get_regions(2, $order['province']);
        $district_list = model('RegionBase')->get_regions(3, $order['city']);
        $this->assign('title', L('edit_address'));
        $this->assign('consignee', $consignee);
        // 取得国家列表、商店所在国家、商店所在国家的省列表
        $this->assign('country_list', model('RegionBase')->get_regions());
        
        $this->assign('shop_province_list', model('RegionBase')->get_regions(1, C('shop_country')));
        $this->assign('province_list', $province_list);
        // dump($province_list);
        $this->assign('city_list', $city_list);
        $this->assign('district_list', $district_list);
        // 分割
        $adress = model('Users')->get_business_address($goods_info['suppliers_id']);
        $this->assign('business_address', $adress);
        $service = $this->model->table('service_type')
            ->field('service_type')
            ->where('service_id = ' . $id)
            ->find(); // 查询服务类型
        $type = model('Users')->get_aftermarket_operate($service['service_type']);
        $this->assign('rec_id', $rec_id);
        $this->assign('service_id', $id);
        $this->assign('type', $type); // 选择售后类型
        $this->assign('order_id', $order_id);
        $sql = 'SELECT service_id,service_name,service_desc FROM ' . M()->pre . 'service_type' . ' WHERE is_show = 1 and service_id=' . $id;
        $result = M()->query($sql);
        $this->assign('info', $result['0']);
        $this->assign('title', $result['0']['service_name']);
        
        $this->display('user_aftermarket_apply.dwt');
    }

    /**
     * 上传售后服务凭证（功能并每有实现）
     */
    public function upload_file()
    {
        if ($_REQUEST["name"]) {
            $result = ectouchUpload('', 'service_image');
            if ($result['error'] > 0) {
                $this->message($result['message'], NULL, 'error');
            }
            /* 生成logo链接 */
            $attachments = substr($result['message']['file']['savepath'], 2) . $result['message']['file']['savename'];
            // 保存图片到数据库/aftermarket_attachments
            $img['img_url'] = __URL__ . '/' . $attachments;
            $img['goods_id'] = intval(I('request.goods_id'));
            $img['rec_id'] = intval(I('request.rec_id'));
            $this->model->table('aftermarket_attachments ')
                ->data($img)
                ->insert();
        } else {
            echo "no picture";
        }
    }

    /**
     * 获取全部服务订单
     * by ECTouch Leah
     */
    public function aftermarket_list()
    {
        $size = I(C('page_size'), 10);
        $count = $this->model->table('order_return ')
            ->where('user_id = ' . $this->user_id)
            ->count();
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('aftermarket_list', $filter), $size);
        $offset_page = explode(',', $offset);
        $orders = model('Users')->get_user_aftermarket($this->user_id, $offset_page[1], $offset_page[0]);
        $this->assign('show_asynclist', C('show_asynclist'));
        $this->assign('title', L('aftermarket_list_lnk'));
        $this->assign('pager', $this->pageShow($count));
        $this->assign('aftermarket_list', $orders);
        $this->display('user_aftermarket_list.dwt');
    }

    /**
     * 售后服务申请
     */
    public function aftermarket_done()
    {
        /* 判断是否重复提交申请退换货 */

        $rec_id = empty($_REQUEST['rec_id']) ? '' : $_REQUEST['rec_id'];
        $order_id = empty($_REQUEST['order_id']) ? '' : $_REQUEST['order_id'];
        $total_exchange_goods = empty($_REQUEST['total_exchange_goods']) ? '' : $_REQUEST['total_exchange_goods'];
        $service_id = empty($_REQUEST['service_id']) ? '' : $_REQUEST['service_id'];
        if ($service_id == 1) {
            $should_return_price = $_REQUEST['return_price'] * $_REQUEST['back_num'];
        } else {
            $should_return_price = 0;
        }
        $num = 0;

        if ($rec_id) {
            $num = $this->model->table('order_return')
                ->field('COUNT(*)')
                ->where(array(
                'rec_id' => $rec_id
            ))
                ->getOne();
          
        } else {
            show_message(L('aftermarket_apply_error'), '', '', 'info', true);
        }
        $goods = model('Order')->order_goods_info($rec_id); /* 订单商品 */
        $claim = $this->model->table('service_type')
            ->field('service_name,service_type')
            ->where('service_id = ' . intval(I('post.service_id')))
            ->find(); /* 查询服务类型 */
        $reason = $this->model->table('return_cause')
            ->field('cause_name')
            ->where('cause_id = ' . intval(I('post.reason')))
            ->find(); /* 退换货原因 */
        $order = model('Users')->get_order_detail_new($order_id, $this->user_id); /* 订单详情 */
        if (($num > 0)) {
            /* 已经添加 查询服务订单 */
            $order_return = $this->model->table('order_return')
                ->field('ret_id, rec_id, add_time, service_sn, return_status, should_return,is_check,service_id')
                ->where('rec_id = ' . $rec_id)
                ->find();
            $ret_id = $order_return['ret_id'];


        } else {
            $order_return = array(
                'service_sn' => get_service_sn(),
                'rec_id' => $rec_id,
                'goods_id' => $goods['goods_id'],
                'order_id' => $order_id,
                'order_sn' => $goods['goods_sn'],
                'service_id' => intval(I('post.service_id')), /*服务类型*/
                'user_id' => $this->user_id,
                'addressee' => $order['consignee'], /*联系人*/
                //'phone' => $order['mobile'], /*联系方式*/
                'remark' => empty($_REQUEST['description']) ? '' : $_REQUEST['description'], /*退款说明*/
                'should_return' => $should_return_price, /*应退金额*/
                'cause_id' => intval(I('post.reason')), /*退换货原因*/
                'add_time' => time(),
                'return_status' => RF_APPLICATION,
                'refund_status' => FF_NOREFUND,
                'province' => intval(I('province')),
                'city' => intval(I('city')),
                'district' => intval(I('district')),
                'address' => I('address')
            
            );
            
            if ($claim['service_type'] == ST_RETURN_GOODS) {
                $regin = model('RegionBase')->get_regions();
                $order_return['country'] = $regin['region_id'];
                $order_return['province'] = $order['province'];
                $order_return['city'] = $order['city'];
                $order_return['district'] = $order['district'];
                $order_return['address'] = $order['address'];
            }
            ;
            /* 插入退换货表 */
            $this->model->table('order_return ')
                ->data($order_return)
                ->insert();
            $ret_id = M()->insert_id();

            /* 记录log */
            $action_info = sprintf(L('rd.' . $order_return['return_status']), $claim['service_name'], $reason['cause_name']);
            model('Order')->return_action($ret_id, RF_APPLICATION, $order_return['return_status'], FF_NOREFOUND, $order_return['remark'], L('buyer'), '', $action_info);
            /* 添加到换货退货表 */
            $return_goods['rec_id'] = $rec_id;
            $return_goods['goods_id'] = $goods['goods_id'];
            $return_goods['goods_name'] = $goods['goods_name'];
            $return_goods['goods_sn'] = $goods['goods_sn'];
            $return_goods['return_type'] = intval(I('post.service_id'));
            $return_goods['back_num'] = intval(I('post.back_num'));
            
            /* 添加退货表 */
            $this->model->table('return_goods')
                ->data($return_goods)
                ->insert();
            $order_return = model('Users')->get_aftermarket_detail($ret_id, $this->user_id);
        }
        
        $action_list = model('Order')->get_return_action($ret_id);
        $this->assign('action_list', $action_list); /* 操作记录 */
        
        if ($order_return['return_status'] == RF_APPLICATION && $order_return['is_check'] == OS_UNCONFIRMED) {
            $order_return['is_cancel'] = true;
        }
        $this->assign('claim', $claim);
        $this->assign('reason', $reason);
        $this->assign('return', $order_return); /* 服务订单 */
        $this->display('user_aftermarket_done.dwt');
    }

    /**
     * 服务订单详情
     */
    public function aftermarket_detail()
    {
        $ret_id = isset($_GET['ret_id']) ? intval($_GET['ret_id']) : 0;
        /* 订单详情 */
        $order = model('Users')->get_aftermarket_detail($ret_id, $this->user_id);
        $order['handler'] = model('Order')->get_return_operate($order);
        /* 订单商品 */
        $goods = model('Order')->aftermarket_goods($order['rec_id']);
        $goods['market_price'] = price_format($goods['market_price'], false);
        $goods['goods_price'] = price_format($goods['goods_price'], false);
        $goods['subtotal'] = price_format($goods['subtotal'], false);
        $goods['tags'] = model('ClipsBase')->get_tags($goods['goods_id']);
        $goods['goods_thumb'] = get_image_path($goods['goods_id'], $goods['goods_thumb']);
        
        /* 服务订单 订单状态 退款状态 审核状态 语言项 */
        if ($order['return_status'] == RF_APPLICATION && $order['is_check'] == RC_APPLY_FALSE) {
            /* 状态 ： 待审核 */
            $order['return_status'] = L('wait_check');
        } elseif ($order['return_status'] == RF_APPLICATION && $order['is_check'] == RC_APPLY_SUCCESS) {
            /* 状态 ： 审核成功 */
            $order['return_status'] = L('check_success');
        } elseif ($order['return_status'] == RF_APPLY_FALSE && $order['is_check'] == RC_APPLY_FALSE) {
            /* 状态 ： 审核失败 */
            $order['return_status'] = L('check_false');
        } elseif ($order['return_status'] == RF_CANCELED) {
            /* 状态 ： 撤销申请 */
            $order['return_status'] = L('cancel');
        } elseif ($order['refund_status'] == FF_REFUND && $order['is_check'] == RC_APPLY_SUCCESS) {
            /* 状态 ： 已退款 */
            $order['return_status'] = L('refund_success');
        } else {
            $order['return_status'] = L('rf.' . $order['return_status']);
        }
        $order['refund_status'] = L('ff.' . $order['refund_status']);
        $order['verify_status'] = L('rc.' . $order['is_check']);
        $this->assign('title', L('aftermarket_detail'));
        $this->assign('return', $order);
        $this->assign('goods', $goods);
        $this->display('user_aftermarket_detail.dwt');
    }

    /**
     * 取消服务请求
     */
    public function cancel_service()
    {
        $ret_id = intval(I('ret_id'));
        $where['ret_id'] = $ret_id;
        /* 取消提交服务订单 */
        $this->model->table('order_return')
            ->data('return_status = ' . RF_CANCELED)
            ->where($where)
            ->update();
        $note = $action_info = L('cancel_service_mess');
        model('Order')->return_action($ret_id, RF_CANCELED, FF_NOREFOUND, RC_APPLY_FALSE, $note, L('buyer'), '', $action_info);
        $this->redirect(url('user/aftermarket_detail', array(
            'ret_id' => $ret_id
        )));
    }

    /**
     * 去退货
     */
    public function to_return()
    {
        $ret_id = intval(I('ret_id'));
        if (empty($ret_id)) {
            show_message(L('to_return_error'), '', '', 'info', true);
        }
        /* 查看是否是重复操作 */
        $return = model('Users')->get_aftermarket_detail($ret_id, $this->user_id);
        if ($return['return_status'] == RF_SEND_OUT) {
            show_message(L('send_out_repeat'), L('back_page_up'), '', 'error');
        }
        $consignee = model('Order')->get_consignee($_SESSION['user_id']);
        /* 取得配送列表 */
        $region = array(
            $consignee['country'],
            $consignee['province'],
            $consignee['city'],
            $consignee['district']
        );
        $shipping_list = model('Shipping')->available_shipping_list($region);
        
        $sql = "select value from " . $this->model->pre . "shop_config where code='shop_country'";
        $res = $this->model->query($sql);
        $shop_country_id = $res[0]['value'];
        $sql = "select region_name from " . $this->model->pre . "region where region_id = '$shop_country_id'";
        $res = $this->model->query($sql);
        $shop_country = $res[0]['region_name'];
        $sql = "select value from " . $this->model->pre . "shop_config where code='shop_province'";
        $res = $this->model->query($sql);
        $shop_province_id = $res[0]['value'];
        $sql = "select region_name from " . $this->model->pre . "region where region_id = '$shop_province_id'";
        $res = $this->model->query($sql);
        $shop_province = $res[0]['region_name'];
        $sql = "select value from " . $this->model->pre . "shop_config where code='shop_city'";
        $res = $this->model->query($sql);
        $shop_city_id = $res[0]['value'];
        $sql = "select region_name from " . $this->model->pre . "region where region_id = '$shop_city_id'";
        $res = $this->model->query($sql);
        $shop_city = $res[0]['region_name'];
        $sql = "select value from " . $this->model->pre . "shop_config where code ='shop_address'";
        $res = $this->model->query($sql);
        $shop_address = $res[0]['value'];
        $sql = "select value from " . $this->model->pre . "shop_config where code ='shop_name'";
        $res = $this->model->query($sql);
        $shop_name = $res[0]['value'];
        $sql = "select value from " . $this->model->pre . "shop_config where code ='service_phone'";
        $res = $this->model->query($sql);
        $service_phone = $res[0]['value'];
        $addr_str = $shop_country . " " . $shop_province . " " . $shop_city . " " . $shop_address . "  收件人： " . $shop_name . " 联系电话：" . $service_phone;
        
        /* 处理退货信息 */
        
        if (IS_POST) {
            
            $data_u['return_status'] = RF_SEND_OUT;
            $where_u['ret_id'] = $ret_id;
         
            $info = M()->table('order_return')
                ->field('rec_id,order_id')
                ->where($where_u)
                ->select();
            /* 记录log */
            // $action_info = "退回商品已寄出，快递名称：" . $shipping_info['shipping_name'] . " ，快递单号： " . $invoice_no;
            $action_info = "退回商品已寄出";
            // $note = "退回商品已寄出，快递名称：" . $shipping_info['shipping_name'] . " ，快递单号： " . $invoice_no;
            $note = "退回商品已寄出";
            model('Order')->return_action($ret_id, RF_SEND_OUT, FF_NOREFOUND, RC_APPLY_SUCCESS, $note, L('buyer'), '', $action_info);
            ecs_header("Location: " . url('User/aftermarket_done', array(
                'rec_id' => $info['0']['rec_id'],
                'order_id' => $info['0']['order_id']
            )));
            exit();
        }
        $this->assign('shipping_list', $shipping_list);
        $this->assign('addr', $addr_str);
        $this->assign('ret_id', $ret_id);
        $this->assign('title', L('send_out'));
        $this->display('user_aftermarket_return.dwt');
    }

    // 退换货end
    
    /* 设置默认收货地址 */
    public function edit_address_info()
    {
        if (IS_AJAX && IS_AJAX) {
            $address_id = I('id');
            $data['address_id'] = $address_id;
            $condition['user_id'] = $this->user_id;
            $this->model->table('users')
                ->data($data)
                ->where($condition)
                ->update();
            unset($_SESSION['flow_consignee']);
            echo json_encode(array(
                'status' => 1
            ));
            exit();
        } else {
            echo json_encode(array(
                'status' => 0
            ));
            exit();
        }
    }

    public function autonymEdit()
    {
        $userId = $_SESSION["user_id"];
        $user = model("Users")->find([
            "user_id" => $userId
        ], "autonym,autonym_mobile_phone,real_name,ID_type,ID_card,idcardimg,idcardimg2,bank,bank_card,autonym_remark,autonym_submit_time,autonym_audit_time");
        $this->assign("headerContent", L("user_autonym_audit"));
        $upload_url = __URL__ . "/index.php?m=default&c=user&a=uploadidcardimg&u=" . $u;
      
      
        $this->assign('user',$user);
        $this->assign('upload_url',$upload_url);
        $this->assign('user',$user);
        $this->assign("page_title", L("user_autonym_audit"));
        $this->display('autonym.dwt');
    }

    public function autonym()
    {
        $userId = $_SESSION["user_id"];
        
        $user = model("Users")->find([
            "user_id" => $userId
        ], "autonym,autonym_mobile_phone,real_name,ID_type,ID_card,bank,bank_card,autonym_remark,autonym_submit_time,autonym_audit_time");
        if ($user["autonym"] == 0) {
            ecs_header("Location: " . url('User/autonymEdit'));
        }
        $user['ID_type'] = L("id_type_{$user["ID_type"]}");
        $this->assign("user", $user);
        
        $this->assign("page_title", L("user_autonym_audit"));
        $this->assign("headerContent", L("user_autonym_audit"));
        
        $this->display("autonym_audit.dwt");
    }
    

    public function autonymAudit()
    {
        $user_info = model('Users')->get_profile($_SESSION['user_id']);
        $userId = $_SESSION["user_id"];
        
        // if ($_SESSION["sms_code"] != $_POST["code"]) {
            
        //     $this->jserror("验证码错误");
        // }
        $params = array(
            "real_name" => $_POST["real_name"],

            "ID_type" => $_POST["ID_type"],
            "ID_card" => $_POST["ID_card"],
            "idcardimg" => $_POST["idcardimg"],
            "idcardimg2" => $_POST["idcardimg2"],
           // "autonym_mobile_phone" => $_SESSION["sms_mobile"],
            "autonym" => 1,
            "autonym_submit_time" => date("Y-m-d H:i:s")
        );

        foreach ($params as $key => $val) {
            if (empty($val)) {
                $this->jserror("认证信息不能为空");
            }
        }
         $updatedata = array(
                              "account" =>$user_info['vip_manage_account'],                  
                              "real_name" =>$_POST["real_name"]
                                                
                              );
                                
         $ret1 = model("Index")->postData($updatedata,"/api/user/update");
        $ret = model("Users")->update([
            "user_id" => $userId
        ], $params);
        $this->jssuccess("ok");
    }

    public function loadlandpage()
    {
        if(!isset($_GET["u"])){
            $this->redirect(url('index/index'));
        }
        if($_SESSION['user_vip']=='0'&&!isset($_GET["u"])){
                $url = url('user/vipmarket');
             
                ecs_header("Location: $url\n");
        }
        // 推荐著陆页
        // 只有是分销商分享的页面才能跑到这个中间登录页面
        $u = $_GET['u'];
        if($_REQUEST ['u']){

         $_SESSION["tmp_parent_id"] = $_REQUEST ['u'] ;
         
        }

        $invateUser = model("Users")->find("user_id={$u}", "user_name,nick_name,user_avatar");
        
        $shareMeta = array(
            "title" => L('loadlandpage_title'),
            "description" => L('loadlandpage_des'),
            "image" => "http://" . $_SERVER['HTTP_HOST'] . "/themes/yutui/images/new/invite/invite_2.png"
        );
        
        $register_url = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?m=default&c=user&a=register&u=" . $u;
        $viporderlist  =  model('Users')->viporderlist(10);
     
        $vad = shuffle($viporderlist);
        
        $this->assign('viporderlist',$viporderlist);
        $this->assign("shareMeta", $shareMeta);
        $this->assign("invite_name", empty($invateUser["nick_name"]) ? $invateUser["user_name"] : $invateUser["nick_name"]);
        $this->assign("user_avatar", $invateUser["user_avatar"]);
        $this->assign('register_url', $register_url);
        $this->display('user_loadlandpage.dwt');
    }

    public function user_alter()
    {
        $u = $_GET['u'];
        
        $register_url = $_SERVER['HTTP_HOST'] . "/index.php?m=default&c=user&a=register&u=" . $u;
        $this->assign('alterurl', $alterurl);
        $this->assign("headerContent", L("user_alter"));
        
        $this->display('user_alter.dwt');
    }

    public function uploadavatar()
    {
        $user_info = model('Users')->get_profile($this->user_id);
        $img = $_POST["img"];

        $array['code'] = 0;
        // update profile avatar
        
        $sql = "update ecs_users set user_avatar='" . $img . "' where user_id='" . $_SESSION[user_id] . "'";
        
        $rs = $this->model->query($sql);
        // $userupdatedata = array(
                       
        //                 "mid" =>$_SESSION['user_id'],
                      
        //                 "head_img" =>$img,
                       
                        
                     
        //                 "token" =>encryptapitoken()
        //             );
        // $this->cleanUserCache($this->user_id);
        // $ret = post_log($userupdatedata,API_URL."/api/user/update");
        die(json_encode($array));
    }
        public function uploadidcardimg()
    {
        
        $user_info = model('Users')->get_profile($this->user_id);
        $img = $_POST["img"];
        $img1 = $_POST['img1'];
        $array['code'] = 0;

        if($img||$img1){
             $result['code'] = 200;
             $result['msg'] = "";
             if($img){
                $result['img'] =$img;
            }else{
                $result['img1'] =$img1;
            }

            echo json_encode($result);exit;
        }else{
            $result['code'] = 422;
            $result['msg'] = "上传图片失败";
            echo json_encode($result);exit;
        }

    }

    /**
     * 编辑收货地址的处理
     */
    public function edit_address_new()
    {
        // 编辑收货地址
        if (IS_POST) {
            $address = array(
                'user_id' => $this->user_id,
                'address_id' => intval($_POST['address_id']),
                'country' => I('post.country', 0, 'intval'),
                'province' => I('post.province', 0, 'intval'),
                'city' => I('post.city', 0, 'intval'),
                'district' => I('post.district', 0, 'intval'),
                'address' => I('post.address'),
                'consignee' => I('post.consignee'),
                'mobile' => I('post.mobile')
            );
            
            if (model('Users')->update_address($address)) {
                show_message(L('edit_address_success'), L('address_list_lnk'), url('address_list'));
            }
            exit();
        }
        
        $id = isset($_GET['id']) ? intval($_GET['id']) : '';
        
        // 获得用护对应收货人信息
        $consignee = model('Users')->get_consignee_list($_SESSION['user_id'], $id);
        
        $province_list = model('RegionBase')->get_regions(1, 1);
        $city_list = model('RegionBase')->get_regions(2, $consignee['province']);
        $district_list = model('RegionBase')->get_regions(3, $consignee['city']);
        $this->assign('title', L('edit_address'));
        $this->assign('consignee', $consignee);
        
        // 取得国家列表、商店所在国家、商店所在国家的省列表
        $this->assign('country_list', model('RegionBase')->get_regions());
        $this->assign('shop_province_list', model('RegionBase')->get_regions(1, C('shop_country')));
        
        $this->assign('province_list', $province_list);
        $this->assign('city_list', $city_list);
        $this->assign('district_list', $district_list);
        
        $this->display('user_edit_address_new.dwt');
    }

    /* 添加手机密码修改 */
    public function user_get_passwordmobile()
    {
        $u = $_GET['u'];
        
        $register_url = $_SERVER['HTTP_HOST'] . "index.php?m=default&c=user&a=user_get_passwordmobile&u=" . $u;
        $this->assign('alterurl', $alterurl);
        
        $this->display('user_get_passwordmobile.dwt');
    }

    // 余额支付
    public function balancepay()
    {
        $order_id = $_POST['order_id'];
        $order_info = model('Order')->order_info($order_id);
        $user_info = model('Users')->get_profile($this->user_id);
        if ($order['order_amount'] > ($user_info['user_money'] + $user_info['credit_line'])) {
            show_message(L('balance_not_enough'));
        }
        // 进行余额支付
    }

    /**
     * 取消订单
     * 
     * @return
     *
     */
    public function cancelorder()
    {
        // 异步
        $json_result = array(
            'error' => 0,
            'msg' => '',
            'url' => ''
        );
        
        $order_id = I('order_id', 0, 'intval');
        $order = model('Users')->get_order_detail_new($order_id, $this->user_id);
        if ($order['order_status'] > 0) {
            $json_result = array(
                'error' => 2,
                'msg' => ''
            );
            exit(json_encode($json_result));
        }
        
        if (! empty($order_id)) {
            
            if (model('Users')->cancel_order($order_id, $this->user_id)) {
                
                $json_result = array(
                    'error' => 0,
                    'msg' => L('cancelorder_msg'),
                    'url' => url('order_list')
                );
                exit(json_encode($json_result));
            }
        } else {
            
            $json_result = array(
                'error' => 1,
                'msg' => L('order_not_exist_msg')
            );
            exit(json_encode($json_result));
        }
    }

    public function affiliate_order()
    {
        if($_SESSION['user_vip']=='0'){
                $url = url('user/vipmarket');
                
                ecs_header("Location: $url\n");
        }
        $share = unserialize(C('affiliate'));
      
        $goodsid = I('request.goodsid', 0);
        
        if (empty($goodsid)) {
            $page = I('request.page', 1);
            $size = I(C('page_size'), 10);
            empty($share) && $share = array();
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
                    $this->assign('affdb', $affdb);
                }
                 
                $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
                
                $sql = "SELECT o.*, a.log_id,a.time, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";

            } else {
                
                // 推荐订单分成
                $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
                
                $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
            }
        
            $res = $this->model->query($sqlcount);
            $count = $res[0]['count'];
            $url_format = url('share', array(
                'page' => '{page}'
            ));
            $limit = $this->pageLimit($url_format, 10);
            $sql = $sql . ' LIMIT ' . $limit;
            $rt = $this->model->query($sql);
          
            if ($rt) {
                $nrt = array();
                foreach ($rt as $k => $v) {
                    if (! empty($v['suid'])) {
                        // 在affiliate_log有記錄
                        if ($v['separate_type'] == - 1 || $v['separate_type'] == - 2) {
                            // 已被撤銷
                            $v['is_separate'] = 3;
                        }
                    }
                    
                    //確定是否列車商品
                    $sql = "select o.order_id from ". $this->model->pre . "order_info o join ". $this->model->pre . "order_goods og on og.order_id=o.order_id join ". $this->model->pre ."goods g on g.goods_id = og.goods_id where g.train_id>0 and o.order_id = {$v["order_id"]}" ;
                    $ret = $this->model->query($sql);
                    if(!empty($ret)){

                        continue;
                    }
                    
                    if (empty($v["is_separate"])) {
                       
                        $v["separate"] = $this->separate_dill($v["order_id"]);
                       
                    }

                    
                    // $rt[$k]['order_sn'] = substr($v['order_sn'], 0, strlen($v['order_sn']) - 5) . "***" . substr($v['order_sn'], - 2, 2);
                    $v['order_sn'] = $v['order_sn'];
                    $nrt[]=$v;
                }
                $rt = $nrt;
            } else {
                $rt = array();
            }

            $pager = $this->pageShow($count);
            $this->assign('pager', $pager);
            $this->assign('affiliate_type', $share['config']['separate_by']);
            
            foreach ($rt as $key => $value) {
                $user_info = model('Users')->get_users($value['user_id']);
                
                $rt[$key]['user_name'] = $user_info['user_name'];
                if($user_info['nick_name']){
                       $rt[$key]['nick_name'] = $user_info['nick_name']; 
                   }else{
                        $rt[$key]['nick_name'] = "";
                }
                if (! empty($value['time'])) {
                    $rt[$key]['time'] = date('Y-m-d', $value['time']);
                }
                
                $rt[$key]['add_time'] = date('Y-m-d', $value['add_time']);
                // 已经分享的资金
                $affiliate_money_done += $value['money'];
                
                // code...
            }
         
            // 未处理
            foreach ($rt as $key1 => $value1) {
                if ($value1['is_separate'] == 1) {
                    continue;
                }
                $user_info1 = model('Users')->get_users($value1['user_id']);
                $rt1[$key1]['money'] = $value1['money'];
                //$rt1[$key1]['user_name'] = $value1['time'];
                $rt1[$key1]['order_sn'] = $value1['order_sn'];
                $rt1[$key1]['is_separate'] = $value1['is_separate'];
                $rt1[$key1]['user_name'] = $user_info1['user_name'];
                if($user_info1['nick_name']){
                       $rt1[$key1]['nick_name'] = $user_info1['nick_name']; 
                   }else{
                       $rt1[$key1]['nick_name'] = "";
                   }
                $rt1[$key1]['add_time'] = $value1['add_time'];
                if (! empty($value1['time'])) {
                    $rt1[$key1]['time'] = date('Y-m-d', $value1['time']);
                }
               
                // 已经分享的资金
                $affiliate_money_yet_done += $value1["separate"]['money'];
             
                // code...
            }
        
        
            
            foreach ($rt as $key2 => $value2) {
                if ($value2['is_separate'] == 1) {
                    $user_info2 = model('Users')->get_users($value2['user_id']);
                 
                    $rt2[$key2]['money'] = $value2['money'];
                    //$rt2[$key2]['user_name'] = $value2['time'];
                    $rt2[$key2]['order_sn'] = $value2['order_sn'];
                    
                    $rt2[$key2]['is_separate'] = $value2['is_separate'];
                    $rt2[$key2]['user_name'] = $user_info2['user_name'];
                    if($user_info2['nick_name']){
                       $rt2[$key2]['nick_name'] = $user_info2['nick_name']; 
                   }else{
                        $rt2[$key2]['nick_name'] = "";
                   }
                    
                    if (! empty($value2['time'])) {
                        $rt2[$key2]['time'] = date('Y-m-d', $value2['time']);
                    }
                    $rt2[$key2]['add_time'] = $value2['add_time'];
                    
                    // 已经分享的资2金
                    $affiliate_money_allreadydone += $value2['money'];
                   
                } else {
                    continue;
                }
                
                // code...
            }
            $datetime1 = array();
            if(!empty($rt1)){
                foreach ($rt1 as $rt2value1) {
                $datetime1[] = $rt2value1['order_sn'];
                }

                array_multisort($datetime1, SORT_DESC, $rt1);
            }
            

            $datetime2 = array();
        
             if(!empty($rt2)){
                foreach ($rt2 as $rt2value2) {
                $datetime2[] = $rt2value2['order_sn'];
                }

                array_multisort($datetime2, SORT_DESC, $rt2);
            }
           

            $this->assign('logdb', $rt);
            $this->assign('logdb1', $rt1);
            $this->assign('logdb2', $rt2);
        } else {
            // 单个商品推荐
            $this->assign('userid', $this->user_id);
            $this->assign('goodsid', $goodsid);
            
            $types = array(
                1,
                2,
                3,
                4,
                5
            );
            $this->assign('types', $types);
            
            $goods = model('Goods')->get_goods_info($goodsid);
            $goods['goods_img'] = get_image_path(0, $goods['goods_img']);
            $goods['goods_thumb'] = get_image_path(0, $goods['goods_thumb']);
            $goods['shop_price'] = price_format($goods['shop_price']);
            
            $this->assign('goods', $goods);
        }
        $user_info1 = model('Users')->get_profile($this->user_id);
         if($_SESSION['user_vip']){

           $this->assign('share_title',  $user_info1['nick_name']."的".C('shop_title'));
       
       }else{

            $this->assign('share_title',  C('shop_title'));
            
       }
         
         $this->assign('share_description', $this->formatDescription(C('shop_desc')));//
         $this->assign('share_pic', __URL__."/themes/yutui/images/new/yutui_logo.png");//
       // $affiliate_money_done = $affiliate_money_allreadydone + $affiliate_money_yet_done;
        $affiliate_money_done = $affiliate_money_allreadydone;
        $this->assign('affiliate_money_done', $affiliate_money_done);
        $this->assign('footer_index','affiliate');
        $this->assign('affiliate_money_allreadydone', $affiliate_money_allreadydone);
        $this->assign('affiliate_money_yet_done', $affiliate_money_yet_done);
        $this->assign('headerContent', "分销订单");
        $this->display('user_affiliate_order.dwt');
    }

    // 转赠车票
    public function modifygivenorderaddress()
    {
        if ($_POST) {
            $where_u['order_id'] = $_POST['order_id'];
            $data_u['consignee'] = $_POST['consignee'];
            $data_u['mobile'] = $_POST['mobile'];
            $data_u['province'] = $_POST['province'];
            $data_u['city'] = $_POST['city'];
            $data_u['district'] = $_POST['district'];
            $data_u['address'] = $_POST['address'];
            $data_u['order_status'] = "1";
            $data_u['country'] = $_POST['country'];
       

            if (empty($data_u['consignee']) || empty($data_u['mobile']) || empty($data_u['province']) || empty($data_u['city']) || empty($data_u['address'])) {

                $r = false;
            } else {
                $r = $this->model->table('order_info')
                    ->data($data_u)
                    ->where($where_u)
                    ->update();
            }
            
            $shouzengorders = model("Train")->getUserUnactiveTicket($_SESSION['user_id']);
            
            if ($r) {
                $shouzengs = self::$cache->getValue("shou_zeng_order_{$_SESSION["user_id"]}");
                self::$cache->setValue("shou_zeng_order_{$_SESSION["user_id"]}",$shouzengs-1,1800);
                if ($shouzengorders) {
                    show_message('转赠票接收成功', '返回下一个赠票', url('user/modifygivenorderaddress'), 'info');
                } else {
                    show_message('转赠票接收成功', array('查单订单','列车详情'), array(url('user/order_detail', array(
                        'order_id' => $_POST['order_id']
                    )),url('train/info', array(
                        'train_id' => $_POST['train_id']
                    ))), 'info',false);
                }
            } else {
                show_message('请填写收件信息', L('back_page_up'), url('user/modifygivenorderaddress'), 'info');
            }
        } else {
            
            if ($_SESSION['user_id']) {
                
                $shouzengorders = model("Train")->getUserUnactiveTicket($_SESSION['user_id']);
                if (! empty($shouzengorders)) {
                    $order = $shouzengorders[0];
                    if(!empty($order['nick_name'])){
                         $order['user_name'] = getEmoji($order['nick_name']);
                    }
                   

                    $this->assign('order', $order);
                } else {
                    $order = false;
                    $this->assign('order', $order);
                }
            }
          
            $province_list = model('RegionBase')->get_regions(1, 1);
            
            $city_list = model('RegionBase')->get_regions(2, $consignee['province']);
            $district_list = model('RegionBase')->get_regions(3, $consignee['city']);
            // var_dump($consignee);exit;
            // 取得国家列表、商店所在国家、商店所在国家的省列表
         
            $this->assign('country_list', model('RegionBase')->get_regions());
            $this->assign('shop_province_list', model('RegionBase')->get_regions(1, C('shop_country')));
            
            $this->assign('province_list', $province_list);
            $this->assign('city_list', $city_list);
            $this->assign('district_list', $district_list);
        }

        $this->display('user_modify_givenorderaddress.dwt');
    }

    // my businsess card

        public function business_card()
    {
       if($_SESSION['user_vip']=='0'&&empty($_GET["u"])){
                $url = url('user/vipmarket');
                
                ecs_header("Location: $url\n");

        }
       $uid = empty($_GET["u"])?(empty($_SESSION["user_id"])?0:$_SESSION["user_id"]):$_GET["u"];
       $_SESSION['parent_id'] = $uid;
        if(!$uid){
            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
          
            $this->redirect(url('register', array(
                'back_act' => urlencode($back_act)
            )));
            exit();
        }
        $template_id = $_GET['template'];
        
        $userinfo = model('Users')->getusersinfo($uid);
        $mid= empty($_GET['mid'])?$userinfo['mainpage_id']:$_GET['mid'];
        $list = model('Users')->getusermainpagelist($_SESSION['user_id']);
        $num = count($list);
        $user_profile_article = model('Users')->getuserprofilearticleinfo($_SESSION['user_id']);
        dump($_REQUEST);
        dump($_SESSION);
        dump($_SERVER);return;
        if($_REQUEST ['u']){

         $_SESSION["tmp_parent_id"] = $_REQUEST ['u'] ;
         
        }
        // 判断是不是第一次进入个人主页mainpage_firsttime
        //mainpage_id为0代表还没生成默认主页
        // 如果为空，那么就随机产生3个文章，1篇外链模式，2篇图文模式
        if (!$userinfo['mainpage_id']) {

            $data['signcomment'] = '会员可打造定制化主页，展示个人品牌从而获得有效的人脉。';
            $data['sign'] = '健康新蓝海，财富新未来!';
            $data['job'] = '新零售经销商';
            $data['company'] = '青彤心大健康';
            $data['address'] = '中国';
            $data['template_id'] = '1';
            $data['user_id'] = $_SESSION['user_id'];
            $data['email'] =  $userinfo['email'];
            $data['mobile_phone_business'] =  $userinfo['mobile_phone_business'];
            $data['mobile_phone'] =  $userinfo['mobile_phone'];
            $data['nick_name'] =  $userinfo['nick_name'];
            $data['user_banner1'] = "/data/attached/userbanner/20180928/1538103106153810310642.jpeg";
            $data['user_banner2'] = "/data/attached/userbanner/20180928/1538114883153811488332.jpeg";
            $data['user_banner3'] = "/data/attached/userbanner/20180928/1538117821153811782186.jpeg";
            //返回主页ID值 $r
            $r = $this->model->table('mainpage')
                ->data($data)
                ->insert();
            $data_info['mainpage_id']  = $r;
            $where_info['user_id'] = $_SESSION['user_id'];
            $result = $this->model->table('users')
                    ->data($data_info)
                    ->where($where_info)
                    ->update();
            
            $data_i['type'] = 1;
            $data_i['title'] = "自媒体推广";
            //对应主页id
            $data_i['mid'] = $r;
            $data_i['user_id'] = $this->user_id;
            $data_i['cover_pic'] = "/data/attached/coverpic/20180928/1538117924153811792433.jpeg";
            $data_i['content'] = "";
            
            $data_i['content_pic'] = serialize(array(
                '0' => "http://img.vmi31.com/201885/15381187103461.jpg",
                '1' => "http://img.vmi31.com/201885/153811871494510.jpg",
                '2' => "http://img.vmi31.com/201885/15381187189628.jpg",
                '3' => "http://img.vmi31.com/201885/15381187235693.jpg",
                '4' => "http://img.vmi31.com/201885/15381187277599.jpg"
            ));
            $data_i['time'] = time();
            $data_i['url'] = '';
            $ri = $this->model->table('user_profile_article')
                ->data($data_i)
                ->insert();
            
            $data_ii['type'] = 1;
            //对应主页id
            $data_ii['mid'] = $r;
            $data_ii['title'] = "获客大数据分析";
            $data_ii['user_id'] = $this->user_id;
            $data_ii['cover_pic'] = "/data/attached/coverpic/20180928/1538118003153811800329.jpeg";
            $data_ii['content'] = "";
            
            $data_ii['content_pic'] = serialize(array(
                '0' => "http://img.vmi31.com/201885/153811801254510.jpg",
                '1' => "http://img.vmi31.com/201885/15381180125466.jpg"
            ));
            $data_ii['time'] = time();
            $data_ii['url'] = '';
            $rii = $this->model->table('user_profile_article')
                ->data($data_ii)
                ->insert();
            $data_iii['type'] = 1;
            $data_iii['mid'] = $r;
            $data_iii['title'] = "大健康购物商城";
            $data_iii['user_id'] = $this->user_id;
            $data_iii['cover_pic'] = "/data/attached/coverpic/20180928/1538118073153811807339.jpeg";
            $data_iii['content'] = "";
            
            $data_iii['content_pic'] = serialize(array(
                '0' => "http://img.vmi31.com/201885/15381181212565.jpg",
                '1' => "http://img.vmi31.com/201885/153811812125710.jpg"
            ));
            
            $data_iii['time'] = time();
            
            $riii = $this->model->table('user_profile_article')
                ->data($data_iii)
                ->insert();
            $data_iiii['type'] = 1;
            $data_iiii['mid'] = $r;
            $data_iiii['title'] = "积分引流";
            $data_iiii['user_id'] = $this->user_id;
            $data_iiii['cover_pic'] = "/data/attached/coverpic/20180928/1538118161153811816112.jpeg";
            $data_iiii['content'] = "";
            
            $data_iiii['content_pic'] = serialize(array(
                '0' => "http://img.vmi31.com/201885/15381181555129.jpg",
                '1' => "http://img.vmi31.com/201885/15381181555113.jpg"
            ));
            
            $data_iiii['time'] = time();
            
            $riiii = $this->model->table('user_profile_article')
                ->data($data_iiii)
                ->insert();
            $data_iiiii['type'] = 1;
            $data_iiiii['mid'] = $r;
            $data_iiiii['title'] = "拓客商城VIP店主";
            $data_iiiii['user_id'] = $this->user_id;
            $data_iiiii['cover_pic'] = "/data/attached/coverpic/20180928/1538118203153811820357.jpeg";
            $data_iiiii['content'] = "";
            
            $data_iiiii['content_pic'] = serialize(array(
                '0' => "http://img.vmi31.com/201885/15381181905963.jpg",
                '1' => "http://img.vmi31.com/201885/15381181905955.jpg",
                '2' => "http://img.vmi31.com/201885/153811819059610.jpg",
            ));
            
            $data_iiiii['time'] = time();
            
            $riiiii = $this->model->table('user_profile_article')
                ->data($data_iiiii)
                ->insert();


        }else{

            
            /*读取这个用户的当前主页的数据*/

            
        }
        
        if($mid){
            $user_profile_article = model('Users')->getuserprofilearticlebymid($mid);

        }else{
          

                $user_profile_article = model('Users')->getuserprofilearticleinfo($_SESSION['user_id']);

          
            
        }

        
        
        
        $user_id = $_SESSION['user_id'];
        if ($user_id != $_GET['u']) {
            $user_id = 0;
        }
       
        if($mid){
                $mainpageinfo = model('Users')->getusermainpagebymid($mid);
        }else{
            if(!isset($template_id)){
            $mainpageinfo = model('Users')->getusermainpagebyuserid($_GET['u']);
            $mid = $mainpageinfo['mainpage_id'];
            }
          
        }

      
       
        $mainpagelist = model('Users')->getusermainpagelist($uid);
       
        foreach ($mainpagelist as $key => $value) {
            $mainpage_id[$key]['id'] = $key;
            $mainpage_id[$key]['mid'] = $value['mainpage_id'];  
            # code...
        }
      ;
        $userBanner1 = $userinfo["user_banner1"];

        if (strpos($userBanner1, "http://") === false) {
            $userBanner1 = "http://" . $_SERVER['HTTP_HOST'] . $userBanner1;
        }

        
        $res = model('Article')->sucaiList(0);
        if($res){
            foreach ($res as $key => $value) {
                # code...
                if($value['sucai_type']==2){
                    $res[$key]['imglist'] = explode(";", $value['sucai_picture']);
                }
                if(!empty($value['sucai_href'])){
                    $hrefinfo = model('Article')->hrefinfo($value['sucai_href']);
                    $res[$key]['pic'] = $hrefinfo['pic'];
                    $res[$key]['file_url'] = $hrefinfo['file_url'];
                    $res[$key]['title'] = $hrefinfo['title'];
                }
                
                //$goodsinfo=  model('Goods')->get_goods_info($value['goods_id']);
                $result= model('Article')->talkinfo($value['talk_id']);
                $res[$key]['talk'] = $result['title'];
                $res[$key]['change_time'] = date("Y-m-d",$value['change_time']);
                // $res[$key]['goods_thumb'] =$goodsinfo['goods_thumb'];
                // $res[$key]['goods_name'] =$goodsinfo['goods_name'];
                // $res[$key]['goods_href'] =__URL__ . '/index.php?m=default&c=goods&a=index&id='.$value['goods_id'].'&u=' . $_SESSION['user_id'];
            }
        }
        $this->assign('sucai_list',$res);
        
        
        $this->assign('mainpagelist',$mainpage_id);
        //$this->assign("shareMeta", $shareMeta);
        
        $this->assign("page_title", $userinfo['nick_name'] . "的主页");
        $this->assign('share_title',  (empty($userinfo["nick_name"]) ? $userinfo["user_name"] : $userinfo["nick_name"]) . L("mainpage"));

        $this->assign('share_description', $mainpageinfo["sign"]);//
        $this->assign('share_pic', $userinfo["user_avatar"]);//
        $this->assign('user_id', $user_id);
        $this->assign('uid',$uid);
        $defaultmid = $userinfo['mainpage_id'];
  
        $this->assign('defaultmid',$defaultmid);
        $this->assign('mid',$mid);
             
        $mainpageinfo['user_avatar'] =$userinfo['user_avatar'] ;
        $mainpageinfo['nick_name'] = $userinfo['nick_name'];
         $mainpageinfo['user_name'] = $userinfo['user_name'];
         $mainpageinfo["sign"] = "健康新蓝海，财富新未来！";
         $mainpageinfo["company"] = "青彤心大健康";
        //公告
        $notice_list = model('ArticleBase')->get_cat_articles(4, 1, 5,"","",1);
        $this->assign('notice_list',$notice_list);
        $this->assign('num', $num);
        
         $artciles_list = model('ArticleBase')->get_cat_articles(10, 1, 15, '');
         foreach ($list as  $articleKey => $articleInfo) {
             $artciles_list[$articleKey]['short_title'] = C('article_title_length') > 0 ? sub_str($artciles_list[$articleKey]['title'], C('article_title_length')) : $artciles_list[$articleKey]['title'];
             $artciles_list[$articleKey]['author'] = empty($artciles_list[$articleKey]['author']) || $artciles_list[$articleKey]['author'] == '_SHOPHELP' ? C('shop_name') : $artciles_list[$articleKey]['author'];
             $artciles_list[$articleKey]['url'] = $artciles_list[$articleKey]['link'] && $artciles_list[$articleKey]['link'] !='http://' ?  $artciles_list[$articleKey]['link'] : url('article/info', array('aid' => $artciles_list[$articleKey]['article_id'])) ;
             $artciles_list[$articleKey]['add_time'] = date(C('date_format'), $artciles_list[$articleKey]['add_time']);
         }
         
        $this->assign('user_name',substr($mainpageinfo['user_name'], 0, 2).'****'.substr($mainpageinfo['user_name'], 6));
        $this->assign('artciles_list',$artciles_list);
        $this->assign('userinfo', $mainpageinfo);
        $this->assign('user_profile_article', $user_profile_article);
        $this->assign('footer_index','affiliate');
         $this->display('user_business_card_noedit.dwt');
        
    }
   
    public function add_business_card()
    {
        $template_id = $_GET['template'];
        /*判断用户是否已经达到主页数量限制3*/
      $number = model('Users')->getusermainpagelist($_SESSION['user_id']);
     $numbertemplate = count($number);
     if($numbertemplate==3){

        show_message('模板数量不能大于3个', '返回我的主页', url('user/business_card', array(
                        'u' => $_SESSION['user_id']
                    )), 'info');
     
     }
        $r = $this->model->query('UPDATE ' . $this->model->pre . "mainpagetemplate SET click_count = click_count + 1 WHERE mainpage_id = '$template_id'");
    
        $userinfo = model('Users')->getusersinfo($_SESSION['user_id']);
        switch ($template_id) {
                case '1':
                    # code...
                    $data['signcomment'] = '拓客商城是一个集合新零售+微商+社交+电商+积分的多模式平台。平台现拥有一手品牌商品的货源，进行严格把关，确保产品的质量，并提供积分优惠购物；微商分销让用户通过自购省钱，分享赚钱的模式轻松获取利润。此外，会员可打造定制化主页，展示个人品牌从而获得有效的人脉。';
                    $data['sign'] = '运气就是，机会恰好碰到了你的努力！';
                    $data['job'] = '拓客达客';
                    $data['company'] = '拓客商城';
                    $data['address'] = '中国';
                    $data['template_id'] = '1';
                    $data['user_id'] = $_SESSION['user_id'];
                    $data['email'] =  $userinfo['email'];
                    $data['mobile_phone_business'] =  $userinfo['mobile_phone_business'];
                    $data['mobile_phone'] =  $userinfo['mobile_phone'];
                    $data['nick_name'] =  $userinfo['nick_name'];
                    $data['user_banner1'] = "/data/attached/userbanner/20180928/1538103106153810310642.jpeg";
                    $data['user_banner2'] = "/data/attached/userbanner/20180928/1538114883153811488332.jpeg";
                    $data['user_banner3'] = "/data/attached/userbanner/20180928/1538117821153811782186.jpeg";
                    //返回主页ID值 $r
                    $r = $this->model->table('mainpage')
                        ->data($data)
                        ->insert();
                    $data_info['mainpage_id']  = $r;
                    $where_info['user_id'] = $_SESSION['user_id'];
                    $result = $this->model->table('users')
                            ->data($data_info)
                            ->where($where_info)
                            ->update();
                    
                    $data_i['type'] = 1;
                    $data_i['title'] = "自媒体推广";
                    //对应主页id
                    $data_i['mid'] = $r;
                    $data_i['user_id'] = $this->user_id;
                    $data_i['cover_pic'] = "/data/attached/coverpic/20180928/1538117924153811792433.jpeg";
                    $data_i['content'] = "";
                    
                    $data_i['content_pic'] = serialize(array(
                        '0' => "http://img.vmi31.com/201885/15381187103461.jpg",
                        '1' => "http://img.vmi31.com/201885/153811871494510.jpg",
                        '2' => "http://img.vmi31.com/201885/15381187189628.jpg",
                        '3' => "http://img.vmi31.com/201885/15381187235693.jpg",
                        '4' => "http://img.vmi31.com/201885/15381187277599.jpg"
                    ));
                    $data_i['time'] = time();
                    $data_i['url'] = '';
                    $ri = $this->model->table('user_profile_article')
                        ->data($data_i)
                        ->insert();
                    
                    $data_ii['type'] = 1;
                    //对应主页id
                    $data_ii['mid'] = $r;
                    $data_ii['title'] = "获客大数据分析";
                    $data_ii['user_id'] = $this->user_id;
                    $data_ii['cover_pic'] = "/data/attached/coverpic/20180928/1538118003153811800329.jpeg";
                    $data_ii['content'] = "";
                    
                    $data_ii['content_pic'] = serialize(array(
                        '0' => "http://img.vmi31.com/201885/153811801254510.jpg",
                        '1' => "http://img.vmi31.com/201885/15381180125466.jpg"
                    ));
                    $data_ii['time'] = time();
                    $data_ii['url'] = '';
                    $rii = $this->model->table('user_profile_article')
                        ->data($data_ii)
                        ->insert();
                    $data_iii['type'] = 1;
                    $data_iii['mid'] = $r;
                    $data_iii['title'] = "大健康购物商城";
                    $data_iii['user_id'] = $this->user_id;
                    $data_iii['cover_pic'] = "/data/attached/coverpic/20180928/1538118073153811807339.jpeg";
                    $data_iii['content'] = "";
                    
                    $data_iii['content_pic'] = serialize(array(
                        '0' => "http://img.vmi31.com/201885/15381181212565.jpg",
                        '1' => "http://img.vmi31.com/201885/153811812125710.jpg"
                    ));
                    
                    $data_iii['time'] = time();
                    
                    $riii = $this->model->table('user_profile_article')
                        ->data($data_iii)
                        ->insert();
                    $data_iiii['type'] = 1;
                    $data_iiii['mid'] = $r;
                    $data_iiii['title'] = "积分引流";
                    $data_iiii['user_id'] = $this->user_id;
                    $data_iiii['cover_pic'] = "/data/attached/coverpic/20180928/1538118161153811816112.jpeg";
                    $data_iiii['content'] = "";
                    
                    $data_iiii['content_pic'] = serialize(array(
                        '0' => "http://img.vmi31.com/201885/15381181555129.jpg",
                        '1' => "http://img.vmi31.com/201885/15381181555113.jpg"
                    ));
                    
                    $data_iiii['time'] = time();
                    
                    $riiii = $this->model->table('user_profile_article')
                        ->data($data_iiii)
                        ->insert();
                    $data_iiiii['type'] = 1;
                    $data_iiiii['mid'] = $r;
                    $data_iiiii['title'] = "拓客商城VIP店主";
                    $data_iiiii['user_id'] = $this->user_id;
                    $data_iiiii['cover_pic'] = "/data/attached/coverpic/20180928/1538118203153811820357.jpeg";
                    $data_iiiii['content'] = "";
                    
                    $data_iiiii['content_pic'] = serialize(array(
                        '0' => "http://img.vmi31.com/201885/15381181905963.jpg",
                        '1' => "http://img.vmi31.com/201885/15381181905955.jpg",
                        '2' => "http://img.vmi31.com/201885/153811819059610.jpg",
                    ));
                    
                    $data_iiiii['time'] = time();
                    
                    $riiiii = $this->model->table('user_profile_article')
                        ->data($data_iiiii)
                        ->insert();
                    $this->redirect(url('user/business_card', array(
                        'mid' => $r
                    )));
                    break;
                case '2':
                    # code...
                    $data['signcomment'] = '个人简介';
                    $data['sign'] = '个性签名';
                    $data['job'] = '事业职位';
                    $data['company'] = '事业名称';
                    $data['address'] = '所在地区';
                    $data['template_id'] = '2';
                    $data['user_id'] = $_SESSION['user_id'];
                    $data['email'] =  '邮件地址';
                    $data['mobile_phone_business'] =  '手机号码';
                    $data['mobile_phone'] =  '';
                    $data['nick_name'] =  '';
                    $data['user_banner1'] = "/data/attached/userbanner/20180929/1538191384153819138446.jpeg";
                    $data['user_banner2'] = "/data/attached/userbanner/20180929/1538191384153819138446.jpeg";
                    $data['user_banner3'] = "/data/attached/userbanner/20180929/1538191384153819138446.jpeg";
                    $data['vx_no'] = '微信号码';
                    //返回主页ID值 $r
                    $r = $this->model->table('mainpage')
                        ->data($data)
                        ->insert();
                    $mid = $r;
                    
                    
                    $data_i['type'] = 1;
                    $data_i['title'] = "展示标题";
                    //对应主页id
                    $data_i['mid'] = $r;
                    $data_i['user_id'] = $this->user_id;
                    $data_i['cover_pic'] = "/data/attached/coverpic/20180929/1538191251153819125163.jpeg";
                    $data_i['content'] = "";
                    
                    $data_i['content_pic'] = serialize(array(
                        '0' => "/data/attached/coverpic/20180929/1538191251153819125163.jpeg"
                      
                    ));
                    $data_i['time'] = time();
                    $data_i['url'] = '';
                    $ri = $this->model->table('user_profile_article')
                        ->data($data_i)
                        ->insert();
                    $mainpageinfo = model('Users')->getusermainpagebymid($r);    
                    $user_profile_article = model('Users')->getuserprofilearticlebymid($r);
                    $this->redirect(url('user/business_card', array(
                        'mid' => $r
                    )));
                    break;
                case '3':
                    # code...
                    $data['signcomment'] = '2010年CY女装服饰最佳新人奖；<br />
                                            2014年MY女装服饰年度百万销售；<br />
                                            2016年MY女装服饰年度销售冠军；<br />
                                            2018年MM文化传媒创始人';
                    $data['sign'] = '逃避只能逃避当时，永远逃避不了现实';
                    $data['job'] = '董事长';
                    $data['company'] = 'MM文化传媒有限公司';
                    $data['address'] = '中国 深圳';
                    $data['template_id'] = '3';
                    $data['user_id'] = $_SESSION['user_id'];
                    $data['email'] =  '邮件地址';
                    $data['mobile_phone_business'] =  '手机号码';
                    $data['mobile_phone'] =  '';
                    $data['nick_name'] =  '';
                    $data['user_banner1'] = "http://img.vmi31.com/FpeHYaXlyDn0Cnh4ZOUVu4fgtUO_";
                    $data['user_banner2'] = "http://img.vmi31.com/Fqm1neAOXK9DLGrQFjpeNbaoVRxy";
                    $data['user_banner3'] = "http://img.vmi31.com/FmJ0dYk4mY59t7LqALWZLLkcSkxH";
                    $data['vx_no'] = '微信号码';
                    //返回主页ID值 $r
                    $r = $this->model->table('mainpage')
                        ->data($data)
                        ->insert();
                    $mid = $r;
                    
                    
                    $data_i['type'] = 1;
                    $data_i['title'] = "我的经历";
                    //对应主页id
                    $data_i['mid'] = $r;
                    $data_i['user_id'] = $this->user_id;
                    $data_i['cover_pic'] = "/data/attached/coverpic/20181015/1539579956153957995636.jpeg";
                    $data_i['content'] = "2018个人简介";
                    
                    $data_i['content_pic'] = serialize(array(
                        '0' => "http://img.vmi31.com/201891/15395799441311.png",
                        '1' => "http://img.vmi31.com/201891/15395799441322.png",
                        '2' => "http://img.vmi31.com/201891/15395799441325.png",
                    ));
                    $data_i['time'] = time();
                    $data_i['url'] = '';
                    $ri = $this->model->table('user_profile_article')
                        ->data($data_i)
                        ->insert();
                        
                    $data_i['type'] = 1;
                    $data_i['title'] = "我的照片";
                    //对应主页id
                  
                    $data_i['mid'] = $r;
                    $data_i['user_id'] = $this->user_id;
                    $data_i['cover_pic'] = "/data/attached/coverpic/20181012/1539327785153932778562.jpeg";
                    $data_i['content'] = "2018个人简介";
                    
                    $data_i['content_pic'] = serialize(array(
                        '0' => "http://img.vmi31.com/201895/15393277789421.jpg"
                    ));
                    $data_i['time'] = time();
                    $data_i['url'] = '';
                    $ri = $this->model->table('user_profile_article')
                        ->data($data_i)
                        ->insert();
                    $mainpageinfo = model('Users')->getusermainpagebymid($r);    
                    $user_profile_article = model('Users')->getuserprofilearticlebymid($r);
                    $this->redirect(url('user/business_card', array(
                        'mid' => $r
                    )));
                    break;
                case '4':
                    # code...
                    $data['signcomment'] = '理想，成功的基石<br />理想，未来的希望<br />
                    <br />理想科技集团，中国西部第一家直销企业，致力推行“茶生活方式”的高科技产业化集团，集茶业和药业种植、生产、销售、文化教育、推广于一体。以强大的资源整合能力、超前的“全产业链”国际事业部战略，引领大健康产业。';
                    $data['sign'] = '新零售共享经济 互助共赢 富足人生';
                    $data['job'] = '理想人生 经销商';
                    $data['company'] = '理想科技集团 国际部';
                    $data['address'] = '中国';
                    $data['template_id'] = '4';
                    $data['user_id'] = $_SESSION['user_id'];
                    $data['email'] =  '';
                    $data['mobile_phone_business'] =  '';
                    $data['mobile_phone'] =  '';
                    $data['nick_name'] =  '小拓客客';
                    $data['user_banner1'] = "http://img.vmi31.com/FgK9LHMGJaUDn1A1zlnFOTfT1cTh";
                    $data['user_banner2'] = "http://img.vmi31.com/Fh-8WDxj2ZlsRigpX0aRnimn0XX9";
                    $data['user_banner3'] = "http://img.vmi31.com/FlqPSAxNK80jBDA9onQA2_CrllO8";
                    $data['vx_no'] = '';
                    //返回主页ID值 $r
                    $r = $this->model->table('mainpage')
                        ->data($data)
                        ->insert();
                    $mid = $r;
                   
                    
                    $data_i['type'] = 2;
                    $data_i['title'] = "理想国际 理想大健康系列商品";
                    //对应主页id
                    $data_i['mid'] = $r;
                    $data_i['user_id'] = $this->user_id;
                    $data_i['cover_pic'] = "/data/attached/coverpic/20181105/1541402219154140221990.jpeg";
                   
                    $data_i['time'] = time();
                    $data_i['url'] = 'http://www.lixiangjituan.com/html/14/index.html';
                    $ri = $this->model->table('user_profile_article')
                        ->data($data_i)
                        ->insert();
                        
                    
                    $data_i['type'] = 2;
                    $data_i['title'] = "理想科技集团官方网站";
                    //对应主页id
                    $data_i['mid'] = $r;
                    $data_i['user_id'] = $this->user_id;
                    $data_i['cover_pic'] = "/data/attached/coverpic/20181105/1541395260154139526081.jpeg";
                    $data_i['content'] = "";
                    
                    $data_i['content_pic'] = serialize(array(
                        '0' => "http://img.vmi31.com/201885/15381181905963.jpg",
                        '1' => "http://img.vmi31.com/201885/15381181905955.jpg",
                        '2' => "http://img.vmi31.com/201885/153811819059610.jpg",
                    ));
                    $data_i['time'] = time();
                    $data_i['url'] = 'http://www.lixiangjituan.com';
                    $ri = $this->model->table('user_profile_article')
                        ->data($data_i)
                        ->insert();
                    $data_i['type'] = 2;
                    $data_i['title'] = "理想科技集团 企业视频";
                    //对应主页id
                    $data_i['mid'] = $r;
                    $data_i['user_id'] = $this->user_id;
                    $data_i['cover_pic'] = "/data/attached/coverpic/20181105/1541406117154140611710.jpeg";
                    $data_i['content'] = "";
                    
                    
                    $data_i['time'] = time();
                    $data_i['url'] = 'https://m.v.qq.com/play.html?vid=t0722lg2opd&ptag=v_qq_com%23v.play.adaptor%233';
                    $ri = $this->model->table('user_profile_article')
                        ->data($data_i)
                        ->insert();
                    $mainpageinfo = model('Users')->getusermainpagebymid($r);    
                    $user_profile_article = model('Users')->getuserprofilearticlebymid($r);
                    $this->redirect(url('user/business_card', array(
                        'mid' => $r
                    )));
                    break;
                default:
                    # code...
                    break;
            }

    }
    /*删除主页*/
    public function delete_mainpage()
    {
        $id = $_POST['mid'];
        $where['mainpage_id'] = $id;
        /*查询该用户的默认主页id*/
        $userinfo = model('Users')->getusersinfo($_SESSION['user_id']);
        if($id==$userinfo['mainpage_id']){
            exit(json_encode(array(
                    'status' => 0,
                    'msg' => L('default_template_delete_error')
                )));
        }else{

            $rs = $this->model->table('mainpage')
            ->where($where)
            ->delete();
            $where1['mid'] = $id;
            $rs = $this->model->table('user_profile_article')
                ->where($where1)
                ->delete();
            exit(json_encode(array(
                    'status' => 1,
                    'msg' => L('delete_template_success')
                )));

        }

        

    }
    /*设置某主页为默认主页*/
    public function setdefaultmainpage()
    {
         $id = $_POST['mid'];
         $userinfo = model('Users')->getusersinfo($_SESSION['user_id']);
         $data['mainpage_id']  = $id;
         $where['user_id'] = $_SESSION['user_id'];
         $rr = $this->model->table('users')
                ->data($data)
                ->where($where)
                ->update();
      
      
        if ($rr) {
                exit(json_encode(array(
                    'status' => 1,
                    'msg' => L('set_default_success')
                )));
            }else{
                if($id ==$userinfo['mainpage_id']){
                    exit(json_encode(array(
                    'status' => 0,
                    'msg' => L('already_default')
                    )));
                }else{
                    exit(json_encode(array(
                    'status' => 0,
                    'msg' => L('set_default_error')
                     )));
                }
                
            }

    }
    public function edit_business_profile()
    {
        /*编辑个人资料*/
         $userinfo = model('Users')->getusersinfo($_SESSION['user_id']);
         $mid= empty($_GET['mid'])?$userinfo['mainpage_id']:$_GET['mid'];
         if ($_POST) {


            $where['user_id'] = $this->user_id;
            $where_u['mainpage_id'] = $_POST['mid'];
            $data['nick_name'] = $_POST['nick_name'];
            $res = $this->model->table('users')
                ->data($data)
                ->where($where)
                ->update();
            $data_u['company'] = $_POST['company'];

            $data_u['job'] = $_POST['job'];
            $data_u['sign'] = $_POST['sign'];

            # 替换空格和换行
            $pattern = array(
                '/ /',//半角下空格
                '/　/',//全角下空格
                '/\r\n/',//window 下换行符
                '/\n/',//Linux && Unix 下换行符
            );
            $replace = array('&nbsp;','&nbsp;','<br />','<br />');

            $data_u['signcomment'] = preg_replace($pattern, $replace, $_POST['signcomment']);
            //$data_u['signcomment'] = str_replace("\r\n","<br />",$_POST['signcomment']);
            $data_u['signcomment'] =  nl2br($_POST['signcomment']);
          
            $data_u['email'] = $_POST['email'];
            $data_u['mobile_phone_business'] = $_POST['mobile_phone_business'];
            $data_u['vx_no'] = $_POST['vx_no'];
            $data_u['address'] = $_POST['address'];
            $data_u['othercontact'] = $_POST['othercontact'];
            $rr = $this->model->table('mainpage')
                ->data($data_u)
                ->where($where_u)
                ->update();
            // $url = url('user/business_card', array(
            //     'mid' => $_POST['mid']
            // ));

            $url = __URL__ . "/index.php?m=default&c=user&a=business_card&u=" .$_SESSION['user_id'] ;

            ecs_header("Location: $url\n");
         }else{

            $upload_url = __URL__ . "/index.php?m=default&c=user&a=uploadavatar&u=" . $u;
        
            $this->assign('upload_url', $upload_url);

            $userinfo = model('Users')->getusersinfo($_SESSION['user_id']);


         }


         if($mid){
                $mainpageinfo = model('Users')->getusermainpagebymid($mid);
        }else{

            $mainpageinfo = model('Users')->getusermainpagebyuserid($_GET['u']);
            $mid = $mainpageinfo['mainpage_id'];
          
        }
        $mainpageinfo['signcomment'] = preg_replace('/<br\\s*?\/??>/i','',$mainpageinfo['signcomment']);
       // var_dump($mainpageinfo['signcomment']);exit;
        $this->assign("back_act",__URL__ . "/index.php?m=default&c=user&a=business_card&u=" . $_SESSION['user_id']);
        $this->assign('mid',$mid);
        $mainpageinfo['user_avatar']  = $userinfo['user_avatar'];
        $mainpageinfo['nick_name'] = $userinfo['nick_name'];
        $mainpageinfo['company'] = "青彤心大健康";
        $mainpageinfo['sign'] = "健康新蓝海，财富新未来！";
        $this->assign('userinfo', $mainpageinfo);
        $this->display('user_edit_business_profile.dwt');

    }
    // my businsess card
    public function edit_business_card()
    {
        $user_info = model('Users')->get_profile($this->user_id);
        $mid = $_GET['mid'];
        if($mid){
                $mainpageinfo = model('Users')->getusermainpagebymid($mid);
            }else{

                $mainpageinfo = model('Users')->getusermainpagebyuserid($_GET['u']);
                $mid = $mainpageinfo['mainpage_id'];
              
            }
          
       
        if (IS_POST) {
            
            if($_POST['sort'][$this->user_id])
            {
                 foreach ($_POST['sort'][$this->user_id] as $key => $value) {
            # code...
            
            $sql3 = "update ecs_user_profile_article set sort='" . $value . "' where id='" . $key . "'" ;
                
            $rs3 = $this->model->query($sql3);

                }
            }

            $base64_image_content = $_POST['contentpic'];
         
            $path = 'data/attached/userbanner';
            $time = time();
            // var_dump($_POST['contentpic1']);
            // var_dump($_POST['contentpic2']);
            // var_dump($_POST['contentpic3']);exit;
            if($_POST['img1_status_delete']){
                $this->newdeletebanner($mid,1);
            }
            if($_POST['img2_status_delete']){
            
                  $this->newdeletebanner($mid,2);
            }
            if($_POST['img3_status_delete']){
                  $this->newdeletebanner($mid,3);
            }
     
            if ($_POST['contentpic1']) {
               
                $r = model('Users')->base64_image_content($_POST['contentpic1'], $path, $time);
              
                $array['code'] = 0;
                $array['user_banner1'] = $_POST['contentpic1'];
                $mid = $_POST['mid'];
                
                // update profile avatar
                
                $sql = "update ecs_mainpage set user_banner1='" . $array['user_banner1'] . "' where user_id='" . $_SESSION[user_id] . "'" ." and mainpage_id='".$mid."'";
                
                
                $rs = $this->model->query($sql);
            }
            if ($_POST['contentpic2']) {
                $r = model('Users')->base64_image_content($_POST['contentpic2'], $path, $time);
                $array['code'] = 0;
                $array['user_banner2'] = $_POST['contentpic2'];
                $mid = $_POST['mid'];
                // update profile avatar
                
                $sql = "update ecs_mainpage set user_banner2='" . $array['user_banner2'] . "' where 
                user_id='" . $_SESSION[user_id] . "'" ." and mainpage_id='".$mid."'";
                
                $rs = $this->model->query($sql);
            }
            if ($_POST['contentpic3']) {
                $r = model('Users')->base64_image_content($_POST['contentpic3'], $path, $time);
                $array['code'] = 0;
                $array['user_banner3'] = $_POST['contentpic3'];
                $mid = $_POST['mid'];
                // update profile avatar
                
                $sql = "update ecs_mainpage set user_banner3='" . $array['user_banner3'] . "' where 
                user_id='" . $_SESSION[user_id] . "'" ." and mainpage_id='".$mid."'";
                
                $rs = $this->model->query($sql);
            }
            
            
            $sql3 = "update ecs_users set mainpage_firsttime=0 where user_id='" . $_SESSION[user_id] . "'";
        
            $rs3 = $this->model->query($sql3);

            $url = url('user/business_card', array(
                            'mid' => $_POST['mid']
                        ));
            ecs_header("Location: $url\n");
            // show_message('个人主页保存成功', '返回个人主页', url('user/index'), 'info');
        } else {
           
            $userinfo = model('Users')->getusersinfo($_SESSION['user_id']);
            $user_profile_article = model('Users')->getuserprofilearticlebymid($mid);
            $user_id = $_SESSION['user_id'];
            $this->assign('user_id', $user_id);
            $this->assign('mid',$_GET['mid']);
   
            $this->assign('user_profile_article', $user_profile_article);
            
            $userinfo = model('Users')->getusersinfo($_SESSION['user_id']);
            
            $this->assign('userinfo', $mainpageinfo);
        }
        
        $this->display('user_edit_business_card.dwt');
    }

    // 编辑个人展示
    public function edit_card()
    {
        $u = $_GET['u'];
        $user_info = model('Users')->get_profile($this->user_id);
        
        $path = 'data/attached/coverpic';
        if ($_POST) {
            
            // 修改或者新增
            if ($_POST['type'] == 1) {
                // tuwenmoshi.
                
                $data_i['content_pic'] = serialize($_POST['contentpic']);
                
                if ($_POST['article_id']) {
                    // UPDATE修改
                    $data_i['type'] = 1;
                    $data_i['title'] = $_POST['title1'];
                    $data_i['user_id'] = $this->user_id;
                    $data_i['content'] = $_POST['content'];
                    $data_i['time'] = time();
                    $data_i['url'] = '';
                    if ($_POST['contentpic1']) {
                        // 封面图
                        $r = model('Users')->base64_image_content($_POST['contentpic1'], $path, time());
                        $data_i['cover_pic'] = $_POST['contentpic1'];
                    }
                    
                    $where_u['id'] = $_POST['article_id'];
                    $where_u['mid'] = $_POST['mid'];
                    $this->model->table('user_profile_article')
                        ->data($data_i)
                        ->where($where_u)
                        ->update();
                        $url = url('user/edit_business_card', array(
                            'mid' => $_POST['mid']
                        ));
               
                    ecs_header("Location: $url\n");
                    // show_message('文章修改成功', '返回个人主页', url('user/edit_business_card'), 'info');
                } else {
                    
                    $data_i['type'] = 1;
                    $data_i['title'] = $_POST['title1'];
                    $data_i['user_id'] = $this->user_id;
                    $data_i['content'] = $_POST['content'];
                    $data_i['time'] = time();
                    $data_i['url'] = '';
                    $data_i['mid'] = $_POST['mid'];
                    if ($_POST['contentpic1']) {
                        $r = model('Users')->base64_image_content($_POST['contentpic1'], $path, time());
                        $data_i['cover_pic'] = $_POST['contentpic1'];
                    }
                    
                    $r = $this->model->table('user_profile_article')
                        ->data($data_i)
                        ->insert();
                    $new_order_id = M()->insert_id();
                    if ($r) {
                         $url = url('user/edit_business_card', array(
                            'mid' => $_POST['mid']
                        ));
                        ecs_header("Location: $url\n");
                        // show_message('文章编辑成功', '返回个人主页', url('user/edit_business_card'), 'info');
                    } else {
                        show_message('文章编辑失败', '返回个人主页', url('user/edit_business_card'), 'info');
                    }
                }
            } else {
                
                // 链接模式
                $data_i['content_pic'] = serialize($_POST['contentpic']);
                if ($_POST['article_id']) {
                    // UPDATE修改
                    $data_i['type'] = 2;
                    $data_i['title'] = $_POST['title2'];
                    $data_i['user_id'] = $this->user_id;
                    $data_i['content'] = '';
                    $data_i['time'] = time();
                    if (model('Users')->startwith($_POST['url'], "http")) {
                        $data_i['url'] = $_POST['url'];
                    } else {
                        $data_i['url'] = "http://" . $_POST['url'];
                    }
                    
                    if ($_POST['contentpic1']) {
                        // 封面图
                        $r = model('Users')->base64_image_content($_POST['contentpic1'], $path, time());
                        $data_i['cover_pic'] = $_POST['contentpic1'];
                    }
                    
                    $where_u['id'] = $_POST['article_id'];
                    $where_u['mid'] = $_POST['mid'];
                    $this->model->table('user_profile_article')
                        ->data($data_i)
                        ->where($where_u)
                        ->update();
                     $url = url('user/edit_business_card', array(
                            'mid' => $_POST['mid']
                        ));
                    ecs_header("Location: $url\n");
                    // show_message('文章修改成功', '返回个人主页', url('user/edit_business_card'), 'info');
                } else {
                   
                    $data_i['type'] = 2;
                    $data_i['title'] = $_POST['title2'];
                    $data_i['user_id'] = $this->user_id;
                    $data_i['content'] = '';
                    $data_i['time'] = time();
                    $data_i['mid'] = $_POST['mid'];
                    if (model('Users')->startwith($_POST['url'], "http")) {
                        $data_i['url'] = $_POST['url'];
                    } else {
                        $data_i['url'] = "http://" . $_POST['url'];
                    }
                    if ($_POST['contentpic1']) {
                        $r = model('Users')->base64_image_content($_POST['contentpic1'], $path, time());
                        $data_i['cover_pic'] = $r;
                    }
                    
                    $r = $this->model->table('user_profile_article')
                        ->data($data_i)
                        ->insert();
                    $new_order_id = M()->insert_id();
                    if ($r) {
                         $url = url('user/edit_business_card', array(
                            'mid' => $_POST['mid']
                        ));
                        ecs_header("Location: $url\n");
                        // show_message('文章编辑成功', '返回个人主页', url('user/edit_business_card'), 'info');
                    } else {
                        show_message('文章编辑失败', '返回个人主页', url('user/edit_business_card'), 'info');
                    }
                }
            }
        } else {
            
            $id = I('get.id', "", 'intval');
            
            $articleinfo = model('Users')->getarticleinfo($id);
            
            $contentpic = unserialize($articleinfo['content_pic']);
            if ($contentpic) {
                
                foreach ($contentpic as $key => $value) {
                    
                    if (! strpos($value, "avatar") && ! strpos($value, "new")) {
                        $contentpic[$key] =  $value;
                    } // code...
                }
                // code...
            }
            
            $this->assign('contentpic', $contentpic);
            $this->assign('articleinfo', $articleinfo);
        }
        $upload_url = __URL__ . "/index.php?m=default&c=user&a=contentpic&u=" . $u;
        $this->assign('mid',$_GET['mid']);
        $this->assign('upload_url', $upload_url);
        $this->display('user_edit_card.dwt');
    }

    // 上传文章图片
    public function contentpic()
    {
    
        if ((($_FILES["file"]["type"] == "image/gif") || ($_FILES["file"]["type"] == "image/jpeg") || ($_FILES["file"]["type"] == "image/pjpeg") || ($_FILES["file"]["type"] == "image/png")) && ($_FILES["file"]["size"] < 20000000)) {
            
            if ($_FILES["file"]["error"] > 0) {
                
                echo "Return Code: " . $_FILES["file"]["error"] . "<br />";
            } else {
                if (file_exists("upload/" . $_FILES["file"]["name"])) {
                    
                    $array['code'] = 1;
                    die(json_encode($array));
                } else {
                    move_uploaded_file($_FILES["file"]["tmp_name"], "data/attached/avatar/" . $_FILES["file"]["name"]);
                    $time = time();
                    // rename("data/attached/avatar/" . $_FILES["file"]["name"],"data/attached/avatar/" . "content".$time.".jpg");
                    $array['code'] = 0;
                    $array['imgurl'] = "data/attached/avatar/" . $_FILES["file"]["name"];
                    // update profile avatar
                    
                    die(json_encode($array));
                }
            }
        } else {
            
            echo "Invalid3 file";
        }
        
        $u = $_GET['u'];
    }

    public function article_show()
    {
        $id = I('get.id', 4, 'intval');
        
        $articleinfo = model('Users')->getarticleinfo($id);
        $articleinfo['time'] = date("Y-m-d", $articleinfo['time']);
        $contentpic = unserialize($articleinfo['content_pic']);
        if ($contentpic) {
            
            foreach ($contentpic as $key => $value) {
                
                if (! strpos($value, "avatar") && ! strpos($value, "new")) {
                    $contentpic[$key] =  $value;
                } // code...
            }
            // code...
        }
        $userinfo = model('Users')->getusersinfo($_GET['u']);
        $nickname = empty($userinfo["nick_name"]) ? $userinfo["user_name"] : $userinfo["nick_name"];
        $uid = empty($_GET["u"])?(empty($_SESSION["user_id"])?0:$_SESSION["user_id"]):$_GET["u"];
      
        if(!empty($uid)){

           $shareUser = $this->assginUserCard($uid);
           
           $rank_points = model('Users')->get_users($uid);

            //非动态栏目下面的文章只显示原作者
           if($rank_points['rank_points']>=10000){
            $this->assign('vip',1);
           }else{
            $this->assign('vip',0);
           }

           $this->assign('shareUser',$shareUser);
        }else{
            $this->assign('vip',0);
        }

        $mainpageinfo = model('Users')->getusermainpagebyuserid($_GET['u']);
        $this->assign('share_description', $mainpageinfo['sign']);//
        $this->assign('share_pic', __URL__.$articleinfo['cover_pic']);//
        $this->assign("page_title", $nickname . "的主页");
        
        $this->assign('nickname', $nickname);
        $this->assign('contentpic', $contentpic);
        $this->assign('articleinfo', $articleinfo);
        $this->display('user_article_show.dwt');
    }
    private function assginUserCard($uid){

        $user_info = model('Users')->getusermainpagebyuserid($uid);
    
        $userInfo = model("Users")->get_users($uid);
        $user_info['user_avatar'] = $userInfo['user_avatar'];
        $user_info['nick_name'] = $userInfo['nick_name'];
        $user_info['autonym'] = $userInfo['autonym'];
  
        $shareUser =$user_info;
     
    
        return $shareUser;

    }

    // 删除个人主页资讯
    public function deletearticleinfo()
    {
        $id = I('get.id', 4, 'intval');
        $where['id'] = $id;
        $rs = $this->model->table('user_profile_article')
            ->where($where)
            ->delete();
        if ($rs) {
            echo "success";
            exit();
        } else {
            echo "fail";
            exit();
        }
    }

    // 分销伙伴
    public function affiliate_partner()
    {
        // 上级代理
        // 上级userid
         if($_SESSION['user_vip']=='0'){
                $url = url('user/vipmarket');
                
                ecs_header("Location: $url\n");
        }
        $share = unserialize(C('affiliate'));
        $user = $this->model->table('users')
            ->field('parent_id')
            ->where(array(
            'user_id' => $this->user_id
        ))
            ->find();
        $where['user_id'] = $user['parent_id'];
        $parentdusers = $this->model->table('users')
            ->field('user_id')
            ->where($where)
            ->select();
        
        if (! empty($parentdusers)) {
            
            foreach ($parentdusers as $key1 => $value1) {
                $userinfo1 = $this->model->table('users')
                    ->field('user_name,reg_time,nick_name,user_avatar,rank_points')
                    ->where(array(
                    'user_id' => $value1['user_id']
                ))
                    ->find();
                $parentdusers[$key1]['reg_time'] = date('Y-m-d H:i:s', $userinfo1['reg_time']);
                $parentdusers[$key1]['user_name'] = $userinfo1['user_name'];
                $parentdusers[$key1]['nick_name'] = $userinfo1['nick_name'];
                $parentdusers[$key1]['user_avatar'] = $userinfo1['user_avatar'];
                $sql1 = "select rank_name from " . $this->model->pre . "user_rank  where min_points<=" . $userinfo1['rank_points'] . " and max_points >=" . $userinfo1['rank_points'];
                
                $rs1 = $this->model->query($sql1);
                
                $parentdusers[$key1]['rank_name'] = $rs1[0]['rank_name'];
                ;
                // code...
            }
        }
        
        // 下级代理

        // 下级userid
        // 下级且必须是创客
        $where1['parent_id'] = $this->user_id;
        $chilldusers = $this->model->table('users')
            ->field('user_id')
            ->where($where1)
            ->select();
        empty($share) && $share = array();
        if (empty($share['config']['separate_by'])) {
            
            // 推荐注册分成
            $affdb = array();
            $num = count($share['item']);
            $up_uid = "'$this->user_id'";
            $all_uid = "$this->user_id";
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
                        $up_uid .= $up_uid ? ",$v[user_id]" : "$v[user_id]";
                        if ($i < $num) {
                            $all_uid .= ", $v[user_id]";
                        }
                        $count ++;
                    }
                }
                $affdb[$i]['num'] = $count;
                $affdb[$i]['point'] = $share['item'][$i - 1]['level_point'];
                $affdb[$i]['money'] = $share['item'][$i - 1]['level_money'];
                $this->assign('affdb', $affdb);
            }
            
            $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
            
            $sql = "SELECT o.*, a.log_id,a.time, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
        } else {
            
            // 推荐订单分成
            $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
            
            $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
        }
        
        $chilldusers = explode(",", $all_uid);
        foreach ($chilldusers as $key0 => $value0) {
            $chillduserss[$key0][0] = $value0;
            // code...
        }

        if ($chillduserss) {
            
            foreach ($chillduserss as $key2 => $value2) {
                
                $userinfo2 = $this->model->table('users')
                    ->field('user_id,user_name,reg_time,nick_name,user_avatar,rank_points')
                    ->where(array(
                    'user_id' => $value2[0]
                ))
                    ->find();
                $sql = "SELECT o.*, a.log_id,a.time, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR o.user_id = '{$value2[0]}' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
                
                $rt = $this->model->query($sql);
                
                if ($userinfo2['reg_time']) {
                    
                    // $chillduserss[0]['reg_time'] = date('Y-m-d H:i:s',$userinfo2['reg_time']);
                }
                if ($userinfo2['user_avatar']) {
                    
                    $chillduserss[0]['user_avatar'] = $userinfo2['user_avatar'];

                }
                
                if ($userinfo2['user_name']) {
                    $chillduserss[$key2]['user_name'] = $userinfo2['user_name'];
                }
                
                if ($userinfo2['nick_name']) {
                    $chillduserss[$key2]['nick_name'] = $userinfo2['nick_name'];
                }
                if ($userinfo2['user_avatar']) {
                    $chillduserss[$key2]['user_avatar'] = $userinfo2['user_avatar'];
                }
                
                $sql2 = "select rank_name from " . $this->model->pre . "user_rank  where min_points<=" . $userinfo2['rank_points'] . " and max_points >=" . $userinfo2['rank_points'];
                
                $rs2 = $this->model->query($sql2);
                
                $chillduserss[$key2]['rank_name'] = $rs2[0]['rank_name'];
                // code...
            }
        }
        
        $this->assign('parentdusers', $parentdusers);
        $this->assign('chilldusers', $chillduserss);
        $this->display('user_affiliate_partner.dwt');
    }
    

    public function affiliate_partner_new()
    {
        $rrr = model("Users")->getUserRankList();
        
        $share = unserialize(C('affiliate'));
        $user_info = model('Users')->get_profile($this->user_id);

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
                $this->assign('affdb', $affdb);
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
        for ($i = 1; $i <= 2; $i ++) {
            $count = 0;
            if ($up_uid) {
                $sql = "SELECT user_id FROM " . $this->model->pre . "users WHERE parent_id IN($up_uid)  and status<>9";
                $query = $this->model->query($sql);
                
                $up_uid = '';
                foreach ($query as $k => $v) {
                    $up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
                    $count ++;
                }
            }
            $all_count += $count;
            
            if ($count) {
                $sql = "SELECT user_id,country,province,city,user_rank, user_name, '$i' AS level, email, is_validated,  user_avatar,nick_name, rank_points, reg_time " . " FROM " . $this->model->pre . "users WHERE user_id IN($up_uid) " . " ORDER by  reg_time desc";
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
        
        if ($user['parent_id']) {
            
            $sql2 = "select * from " . $this->model->pre . "users  where user_id=" . $user['parent_id'];
            
            $parentdusers = $this->model->query($sql2);
        }
        
        if (! empty($parentdusers)) {
            
            foreach ($parentdusers as $key1 => $value1) {
                $userinfo1 = $this->model->table('users')
                    ->field('user_name,reg_time,nick_name,user_avatar,rank_points')
                    ->where(array(
                    'user_id' => $value1['user_id']
                ))
                    ->find();
                $parentdusers[$key1]['reg_time'] = date('Y-m-d', $userinfo1['reg_time']);

                $parentdusers[$key1]['user_name'] = substr($userinfo1['user_name'], 0, 2).'****'.substr($userinfo1['user_name'], 6);
                $parentdusers[$key1]['nick_name'] = getEmoji($userinfo1['nick_name']);
                $parentdusers[$key1]['user_avatar'] = $userinfo1['user_avatar'];
                $sql1 = "select rank_name from " . $this->model->pre . "user_rank  where min_points<=" . $userinfo1['rank_points'] . " and max_points >=" . $userinfo1['rank_points'];
                
                $rs1 = $this->model->query($sql1);
                
                $parentdusers[$key1]['rank_name'] = $rs1[0]['rank_name'];
                ;
                // code...
            }
        }
       
        foreach ($user_list['user_list'] as $key3 => $value3) {
            /*获取*/
            $not_comment = model('ClipsBase')->not_pingjia($value3['user_id']);
            $order = model('Order')->getuserordernum($value3['user_id']);

            $isvip = model('Users')->userisvip($value3['user_id']);
            $user_list['user_list'][$key3]['rank_name'] = $value3['user_rank']?$rrr[$value3['user_rank']-1]['rank_name']:"普通会员";
            $user_list['user_list'][$key3]['isvip'] = $isvip;
            $user_list['user_list'][$key3]['ordernum'] = $order;
            $user_list['user_list'][$key3]['ordercount'] =  $not_comment ;
            $user_list['user_list'][$key3]['user_avatar'] =  $value3['user_avatar']==''?"/themes/yutui/images/idx_user.png":$value3['user_avatar'];
            
            $user_list['user_list'][$key3]['user_name'] = substr($value3['user_name'], 0, 2).'****'.substr($value3['user_name'], 6); ;

            # code...
        }
         if($_SESSION['user_vip']){

           $this->assign('share_title',  $user_info['nick_name']."的".C('shop_title'));
       
       }else{

            $this->assign('share_title',  C('shop_title'));
            
       }

        $this->assign('share_description', $this->formatDescription(C('shop_desc')));//
        $this->assign('share_pic', __URL__."/themes/yutui/images/new/yutui_logo.png");//
        $this->assign('parentdusers', $parentdusers);
        $this->assign('leveltotal',count($user_list['user_list']));
        $this->assign('levelone',$levelone);
        $this->assign('leveltwo',$leveltwo);
        $this->assign('chilldusers', $user_list['user_list']);
        $this->assign('totalcount',count($user_list['user_list']));

        $this->assign('footer_index','affiliate');
        $this->assign('childnum', count($user_list['user_list']));
        $this->display('user_affiliate_partner.dwt');
    }
    /*ajax 获取伙伴列表信息*/
    public function ajax_afficiate_partner()
    {
        $page = $_POST['page'];
        $size =10;
        $start = ($page - 1) * $size;
     
        /*0 代表全部，1代表一级下属，2代表二级下属*/
        $type = $_POST['type'];


        $rrr = model("Users")->getUserRankList();
        
        $share = unserialize(C('affiliate'));
        $user_info = model('Users')->get_profile($this->user_id);

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
                $this->assign('affdb', $affdb);
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
                $sql = "SELECT user_id FROM " . $this->model->pre . "users WHERE parent_id IN($up_uid) and status<>9";
                $query = $this->model->query($sql);
                
                $up_uid = '';
                foreach ($query as $k => $v) {
                    $up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
                    $count ++;
                }
            }
            $all_count += $count;
            
            if ($count) {
                switch ($type) {
                    case '1':
                        # code...
                    if($i==1){
                    
                        $sql = "SELECT user_id,resource,country,province,city,user_rank, user_name, '$i' AS level, email, is_validated, user_money, user_avatar,nick_name, rank_points, reg_time " . " FROM " . $this->model->pre . "users WHERE user_id IN($up_uid)" . " ORDER by  reg_time desc limit ".$start.",".$size;
                       
                        $totalsql1 =  "SELECT user_id,resource,country,province,city,user_rank, user_name, '$i' AS level, email, is_validated,  user_avatar,nick_name,  reg_time " . " FROM " . $this->model->pre . "users WHERE user_id IN($up_uid)" . " ORDER by  reg_time desc ";
                         $totalresult1 = $this->model->query($totalsql1);

                         $count1 = count($totalresult1);
                         
                        $countpage1 = ceil($count1/$size)+1;
                        $result['totalpage'] = $countpage1;
                                          $user_list['user_list'] = array_merge($user_list['user_list'], $this->model->query($sql));
                                    }
                        break;
                    case '2':
                        # code...
                        if($i==2){
                        
                        $sql = "SELECT user_id,country,resource,province,city,user_rank, user_name, '$i' AS level, email, is_validated,  user_avatar,nick_name, rank_points,  reg_time " . " FROM " . $this->model->pre . "users WHERE user_id IN($up_uid)" . " ORDER by  reg_time desc limit ".$start.",".$size;
                        $totalsql2 =  "SELECT user_id,country,province,resource,city,user_rank, user_name, '$i' AS level, email, is_validated,  user_avatar,nick_name, rank_points,  reg_time " . " FROM " . $this->model->pre . "users WHERE user_id IN($up_uid)" . " ORDER by  reg_time desc ";
                         $totalresult2= $this->model->query($totalsql2);
                         $count2 = count($totalresult2);
                    
                        $countpage2 = ceil($count2/$size)+1;
                        $result['totalpage'] = $countpage2;
                                          $user_list['user_list'] = array_merge($user_list['user_list'], $this->model->query($sql));

                                    }
                        break;
                    default:
                        # code...
                        $sql = "SELECT user_id,country,resource,province,city,user_rank, user_name, '$i' AS level, email, is_validated, user_avatar,frozen_money,nick_name, reg_time " . " FROM " . $this->model->pre . "users WHERE user_id IN($up_uid)" . " ORDER by  reg_time desc limit ".$start.",".$size; 
                   $totalsql3 =  "SELECT user_id,country,province,resource,city,user_rank, user_name, '$i' AS level, email, is_validated, user_avatar,nick_name, rank_points,  reg_time " . " FROM " . $this->model->pre . "users WHERE user_id IN($up_uid)" . " ORDER by  reg_time desc ";
                   $totalresult3 = $this->model->query($totalsql3);
                   $count3 = count($totalresult3);
                  
                    $resulttotal =$resulttotal + $count3; 
                    $countpage3 = ceil($count3/$size)+1;
                    $result['totalpage'] = $countpage3;
                   
                                $user_list['user_list'] = array_merge($user_list['user_list'], $this->model->query($totalsql3));
                        break;
                }
               
                
               

            }
        }


        // 查询每个下级和下两级会员的佣金分总
        foreach ($user_list['user_list'] as $key => $value) {
            // code...
      
         
         
            foreach ($rt as $key1 => $value1) {
                if ($value1['user_id'] == $value['user_id']) {
                        
                    $user_list['user_list'][$key]['money'] += $value1['money']?$value1['money']:0;
                }
                
                // code...
            }

            if ($value['reg_time']) {
                $user_list['user_list'][$key]['reg_time'] = date("Y-m-d", $value['reg_time']);
            }

            // $sql1 = "select rank_name from " . $this->model->pre . "user_rank  where min_points<=" . $value['rank_points'] . " and max_points >=" . $value['rank_points'];
                
            //     $rs1 = $this->model->query($sql1);
                
            //     $user_list['user_list'][$key]['rank_name'] = $rs1[0]['rank_name'];
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
        
        if ($user['parent_id']) {
            
            $sql2 = "select * from " . $this->model->pre . "users  where user_id=" . $user['parent_id'];
            
            $parentdusers = $this->model->query($sql2);
        }
        
        if (! empty($parentdusers)) {
            
            foreach ($parentdusers as $key1 => $value1) {
                $userinfo1 = $this->model->table('users')
                    ->field('user_name,reg_time,nick_name,user_avatar,rank_points')
                    ->where(array(
                    'user_id' => $value1['user_id']
                ))
                    ->find();
                $parentdusers[$key1]['reg_time'] = date('Y-m-d', $userinfo1['reg_time']);
                $parentdusers[$key1]['user_name'] = substr($userinfo1['user_name'], 0, 2).'****'.substr($userinfo1['user_name'], 6);
                $parentdusers[$key1]['nick_name'] = getEmoji($userinfo1['nick_name']);
                $parentdusers[$key1]['user_avatar'] = $userinfo1['user_avatar'];
                $sql1 = "select rank_name from " . $this->model->pre . "user_rank  where min_points<=" . $userinfo1['rank_points'] . " and max_points >=" . $userinfo1['rank_points'];
                
                $rs1 = $this->model->query($sql1);
                
                $parentdusers[$key1]['rank_name'] = $rs1[0]['rank_name'];
                ;
                // code...
            }
        }
       
        foreach ($user_list['user_list'] as $key3 => $value3) {
            /*获取*/
            if($value3['level']==1){
                $not_comment = model('ClipsBase')->not_pingjia($value3['user_id']);
                $order = model('Order')->getuserordernum($value3['user_id']);

                $isvip = model('Users')->userisvip($value3['user_id']);
                if($value3['user_rank']>0&&$value3['resource']==2){
                     $user_list['user_list'][$key3]['rank_name'] = "拓客经销商";
                }else{
                     $user_list['user_list'][$key3]['rank_name'] = $value3['user_rank']?$rrr[$value3['user_rank']-1]['rank_name']:"";
                }
               
                $user_list['user_list'][$key3]['isvip'] = $isvip;
                $user_list['user_list'][$key3]['ordernum'] = $order;
                $user_list['user_list'][$key3]['ordercount'] =  $not_comment ;
                /*vip身份*/
                $user_list['user_list'][$key3]['nick_name'] =  getEmoji($value3['nick_name']);
                $user_list['user_list'][$key3]['vip_name'] = model("Users")->getvipname($value3['user_id']);
                $user_list['user_list'][$key3]['user_avatar'] = $value3['user_avatar']==''?"/themes/yutui/images/idx_user.png":$value3['user_avatar'];
                $user_list['user_list'][$key3]['user_name'] =  substr($value3['user_name'], 0, 2).'****'.substr($value3['user_name'], 6); ;
            }
            

            # code...
        }

         if(!$type){
       
        $count4 = count($user_list['user_list']);
        $countpage3 = ceil($count4/$size)+1;
                    $result['totalpage'] = $countpage3;
        $user_list['user_list'] = array_slice($user_list['user_list'],$start,$size);
   

         }
        
        $result['list'] = $user_list['user_list'];
        $result['page'] = $page;
        die(json_encode($result));
        exit();






    }
    //
    public function createmainpage()
    {
        // 推荐着陆页
        // 只是有经销商分享的页面才能跑到这个并登录页面
        if($_SESSION['user_vip'])
          {
            $this->redirect(url('user/business_card', array(
                'u' => $_SESSION['user_id']
            )));
          }
        $u = $_GET['u'];
        
        $invateUser = model("Users")->find("user_id={$u}", "user_name,nick_name,user_avatar");
        
        $yaoqing_url = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?m=default&c=user&a=loadlandpage&u=" . $u;
        $this->assign("invite_name", empty($invateUser["nick_name"]) ? $invateUser["user_name"] : $invateUser["nick_name"]);
        $this->assign("user_avatar", $invateUser["user_avatar"]);
        $this->assign('yaoqing_url', $yaoqing_url);
        $this->display('user_createmainpage.dwt');
    }

    public function user_points()
    {
        $userInfo = model("Users")->get_user_info($_SESSION["user_id"]);
        
        $this->assign("user_info", $userInfo);
        
        $this->assign("page_title", L("user_integral"));
        $this->display('user_points.dwt');
    }

    public function user_points_detail()
    {
        $userInfo = model("Users")->get_user_info($_SESSION["user_id"]);
        
        $this->assign("user_info", $userInfo);
        // 获取剩余余额
        $size = I(C('page_size'), 10);
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $where = 'user_id = ' . $this->user_id ;

        $count = $this->model->table('account_log')
            ->field('COUNT(*)')
            ->where($where)
            ->getOne();

        $this->pageLimit(url('user/user_points_detail'), $size);
        $this->assign('pager', $this->pageShow($count));
        $account_detail = model('Users')->get_new_account_detail($_SESSION['user_id'], $size, ($page - 1) * $size,6);

        $this->assign('headerContent', L('user_integral_detail'));
        $this->assign('account_log', $account_detail);
        
        $this->assign("page_title", L("user_integral_detail"));
        $this->display('user_points_record.dwt');
    }

        public function ajax_user_points_detail()
        {
            $size = 10;
            $page = $_GET['page'];
            $account_detail = model('Users')->get_new_account_detail($_SESSION['user_id'], $size, ($page - 1) * $size,6);
     
            $html = "";
            $num = count($account_detail);
            foreach ($account_detail as $key => $value) {
                # code...

                 $html.='<li class="dis-shop-list padding-all b-color-f  ">';
                 $html.='<div class="dis-box dis-box-align">'   ;
                 $html.='<div class="box-flex">';
                 $html.='<h6 class="f-05 col-7"><span class="f05_span">'. $value['short_change_desc'].'</span></h6>';
                 $html.='<h5 class="f-05 col-hui m-top04 ">'.$value['change_time'].'</h5></div>';
                 $html.='<div class="box-flex">';
                 if($value['user_points']>0){
                    $html.='<p class="f-05 color-red  text-right">';
                 }else{
                    $html.='<p class="f-05  col-3 text-right">';
                 }
                 if($value['account']>0){
                    $html.='+'.$value['account'];
                 }elseif($value['account']<0){
                    $html.=$value['account'];
                 }else{
                    $html.=$value['account'];
                 }
                 $html.='</p></div></div></li>';
           
                
            }
            if($num>=10){

                $html .=' <div class="sb1" style="background: #fff;text-align: center;">';
                $html .='  <a href="javascript:;" class="gengduo more1" >加载更多</a></div>  ';
            }
          
        if($html){

            $result['status'] = 200;
            $result['data'] = $account_detail;
       }else{
            $result['status'] = 303;
            $result['data'] = "暂无数据";
       }
   
       echo json_encode($result);
            exit();
    }

    function separate_dill($oid)
    {
        $affiliate = unserialize(C('affiliate'));
        empty($affiliate) && $affiliate = array();
        
        $separate_by = $affiliate['config']['separate_by'];
        $is_dependent = $affiliate['config']['is_dependent'];
        $row = model("Users")->row("SELECT o.order_sn, o.is_separate, (o.goods_amount - o.discount) AS goods_amount, o.user_id FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " WHERE order_id = '$oid'");
        
        $order_sn = $row['order_sn'];
        
        $separate = array();
        if (empty($row['is_separate'])) {
            $affiliate['config']['level_point_all'] = (float) $affiliate['config']['level_point_all'];
            $affiliate['config']['level_money_all'] = (float) $affiliate['config']['level_money_all'];
            if ($affiliate['config']['level_point_all']) {
                $affiliate['config']['level_point_all'] /= 100;
            }
            if ($affiliate['config']['level_money_all']) {
                $affiliate['config']['level_money_all'] /= 100;
            }
            $goods_list = $this->model->query("SELECT goods_id,goods_number FROM " . $this->model->pre . "order_goods where order_id = $oid");
            
            foreach ($goods_list as $key => $val) {
                $money = round($affiliate['config']['level_money_all'] * $row['goods_amount'], 2);
                if($is_dependent){
                    /*独立现金分成，金额等于各个商品设置的佣金之和*/
                     $goods_list1 = $this->model->query("SELECT goods_id,goods_number FROM " . $this->model->pre . "order_goods where order_id = $oid");
                 
                     foreach ($goods_list1 as $key1 => $value1) {
                         # code...
                       
                            $goodsinfo = $this->model->query("SELECT indepent_bonus FROM " . $this->model->pre . "goods WHERE goods_id = '{$value1['goods_id']}'");
                      
                          
                        
                            
                            $totalmoney += $value1['goods_number']*$goodsinfo['0']['indepent_bonus'];

                     }
                    
                    $money = $totalmoney;
                }else{
                    $money = round($affiliate['config']['level_money_all'] * $row['order_amount'],2);
                }
                $point = $money;
                
                if (empty($separate_by)) {
                    // 推荐注册分成
                    $num = count($affiliate['item']);
                    for ($i = 0; $i < $num; $i ++) {
                        $affiliate['item'][$i]['level_point'] = (float) $affiliate['item'][$i]['level_point'];
                        $affiliate['item'][$i]['level_money'] = (float) $affiliate['item'][$i]['level_money'];
                        if ($affiliate['item'][$i]['level_point']) {
                            $affiliate['item'][$i]['level_point'] /= 100;
                        }
                        if ($affiliate['item'][$i]['level_money']) {
                            $affiliate['item'][$i]['level_money'] /= 100;
                        }
                        $setmoney = round($money * $affiliate['item'][$i]['level_money'], 2);
                        $setpoint = round($point * $affiliate['item'][$i]['level_point'], 0);
                        $row = model("Users")->row("SELECT o.parent_id as user_id,u.user_name FROM " . $this->model->pre . "users o" . " LEFT JOIN " . $this->model->pre . "users u ON o.parent_id = u.user_id" . " WHERE o.user_id = '$row[user_id]'");
                        $up_uid = $row['user_id'];
                        if (empty($up_uid) || empty($row['user_name'])) {
                            break;
                        } else {
                            $info = sprintf($_LANG['separate_info'], $order_sn, $setmoney, $setpoint);
                            if($up_uid==$_SESSION['user_id']){
                                $separate = [
                                "order_id" => $oid,
                                "user_id" => $up_uid,
                                "user_name" => $row['user_name'],
                                "money" => number_format($setmoney,2),
                                "point" => $setpoint,
                                "separate_by" => $separate_by
                            ];
                            }
                            
                        }
                    }
                } else {

                    // 推荐订单分成
                    $row = model("Users")->row("SELECT o.parent_id, u.user_name FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.parent_id = u.user_id" . " WHERE o.order_id = '$oid'");
                    $up_uid = $row['parent_id'];
                    if (! empty($up_uid) && $up_uid > 0) {
                        $info = sprintf($_LANG['separate_info'], $order_sn, $money, $point);
                        if($up_uid==$_SESSION['user_id']){
                               $separate = [
                            "order_id" => $oid,
                            "user_id" => $up_uid,
                            "user_name" => $row['user_name'],
                            "money" => $money,
                            "point" => $point,
                            "separate_by" => $separate_by
                        ];
                            }
                        
                    }
                }
            }
        }
        return $separate;
    }
    /*创建账号*/
    function createuseraccount()
    {
        $resouce = I("get.resource");
        $this->assign("resource",$resouce);
        $this->display('user_createuseraccount.dwt');
    }
    /*关联账号*/
    function linkuseraccount()
    {
        $resouce = I("get.resource");
        $this->assign("resource",$resouce);
        $this->display('user_linkuseraccount.dwt');
    }
    function delarticle()
    {
        # code...
        $where_u['id'] = $_POST['aid'] ;


        
        $r = $this->model->table('user_profile_article')
                            ->where($where_u)
                            ->delete();


        if($r){
            echo json_encode(array('code' => 200));exit;
        }else{
            echo json_encode(array('code' => 500));exit;
        }
    }
    /**
     * 检测积分和变更会员等级
     */
     function check_level()
    {

     var_dump($_SESSION['user_rank']);exit;

    }
    /*VIP销售*/
    function vipmarket()
    {
        if($_SESSION['user_vip']){

            $this->redirect(url('affiliate/index'));

        }
        $viporderlist  =  model('Users')->viporderlist(20);
        $user_info = model("Users")->get_users($_SESSION["user_id"]);
        $vad = shuffle($viporderlist);
        $this->assign("uid",$_SESSION["user_id"]);
        $this->assign("user_info",$user_info);
        $this->assign('viporderlist',$viporderlist);
        $this->assign('footer_index','affiliate');
        $this->display('VIPmarket.dwt');
    }
    /*VIP特权*/
    function vipprivilege()
    {
        $this->assign('footer_index','affiliate');
        $this->display('VIPprivilege.dwt');
    }
    /*我的名片*/
    function mycard()
    {



     
        $u = empty($_GET['u'])?$_SESSION['user_id']:$_GET['u'];
       
        $user_info = model('Users')->getusermainpagebyuserid($u);
        if (!$user_info) {
            # code...
             $user_info  = $this->model->table('users')->where(array('user_id' => $u))->find();
        }
      
        if(!$user_info){
            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
          
            $this->redirect(url('register', array(
                'back_act' => urlencode($back_act)
            )));
            exit();
        }
        
        if($_SESSION['user_id']!=$u){
            $_SESSION['parent_id']=$u;
        }
        
          if($_SESSION['user_vip']){

               $this->assign('share_title',  $user_info['nick_name']."的魚推商城VIP微名片请惠存");
           
           }else{

                $this->assign('share_title',  C('shop_title'));
                
         }
         
         $this->assign('share_description', $user_info['sign']);//
         $this->assign('share_pic', $user_info['user_avatar']);//

        $userinfo = model('Users')->getusersinfo($u);
        if(strpos($userinfo['user_avatar'], "qlogo.cn")){
            /*包含*/
            $picname = $userinfo['user_name'].".jpg";
            $newavatar = put_file_from_url_content($userinfo['user_avatar'],$picname,"upload/");
           
            $data_u3['user_avatar']=$newavatar;
            $where_u3['user_id'] =  $_GET['u'];
            $r = $this->model->table('users')
                            ->data($data_u3)
                            ->where($where_u3)
                            ->update();
        }
        $this->assign('id',$u);

            $mainpageinfo = model('Users')->getusermainpagebyuserid($_GET['u']);
            
         if (!$userinfo['mainpage_id']) {

            $data['signcomment'] = '拓客商城是一个集合新零售+微商+社交+电商+积分的多模式平台。平台现拥有一手品牌商品的货源，进行严格把关，确保产品的质量，并提供积分优惠购物；微商分销让用户通过自购省钱，分享赚钱的模式轻松获取利润。此外，会员可打造定制化主页，展示个人品牌从而获得有效的人脉。';
            $data['sign'] = '运气就是，机会恰好碰到了你的努力！';
            $data['job'] = '拓客达客';
            $data['company'] = '拓客商城';
            $data['address'] = '中国';
            $data['template_id'] = '1';
            $data['user_id'] = $_SESSION['user_id'];
            $data['email'] =  $userinfo['email'];
            $data['mobile_phone_business'] =  $userinfo['mobile_phone_business'];
            $data['mobile_phone'] =  $userinfo['mobile_phone'];
            $data['nick_name'] =  $userinfo['nick_name'];
            $data['user_banner1'] = "/data/attached/userbanner/20180928/1538103106153810310642.jpeg";
            $data['user_banner2'] = "/data/attached/userbanner/20180928/1538114883153811488332.jpeg";
            $data['user_banner3'] = "/data/attached/userbanner/20180928/1538117821153811782186.jpeg";
            //返回主页ID值 $r
            $r = $this->model->table('mainpage')
                ->data($data)
                ->insert();
            $data_info['mainpage_id']  = $r;
            $where_info['user_id'] = $_SESSION['user_id'];
            $result = $this->model->table('users')
                    ->data($data_info)
                    ->where($where_info)
                    ->update();
            
            $data_i['type'] = 1;
            $data_i['title'] = "自媒体推广";
            //对应主页id
            $data_i['mid'] = $r;
            $data_i['user_id'] = $this->user_id;
            $data_i['cover_pic'] = "/data/attached/coverpic/20180928/1538117924153811792433.jpeg";
            $data_i['content'] = "";
            
            $data_i['content_pic'] = serialize(array(
                '0' => "http://img.vmi31.com/201885/15381187103461.jpg",
                '1' => "http://img.vmi31.com/201885/153811871494510.jpg",
                '2' => "http://img.vmi31.com/201885/15381187189628.jpg",
                '3' => "http://img.vmi31.com/201885/15381187235693.jpg",
                '4' => "http://img.vmi31.com/201885/15381187277599.jpg"
            ));
            $data_i['time'] = time();
            $data_i['url'] = '';
            $ri = $this->model->table('user_profile_article')
                ->data($data_i)
                ->insert();
            
            $data_ii['type'] = 1;
            //对应主页id
            $data_ii['mid'] = $r;
            $data_ii['title'] = "获客大数据分析";
            $data_ii['user_id'] = $this->user_id;
            $data_ii['cover_pic'] = "/data/attached/coverpic/20180928/1538118003153811800329.jpeg";
            $data_ii['content'] = "";
            
            $data_ii['content_pic'] = serialize(array(
                '0' => "http://img.vmi31.com/201885/153811801254510.jpg",
                '1' => "http://img.vmi31.com/201885/15381180125466.jpg"
            ));
            $data_ii['time'] = time();
            $data_ii['url'] = '';
            $rii = $this->model->table('user_profile_article')
                ->data($data_ii)
                ->insert();
            $data_iii['type'] = 1;
            $data_iii['mid'] = $r;
            $data_iii['title'] = "大健康购物商城";
            $data_iii['user_id'] = $this->user_id;
            $data_iii['cover_pic'] = "/data/attached/coverpic/20180928/1538118073153811807339.jpeg";
            $data_iii['content'] = "";
            
            $data_iii['content_pic'] = serialize(array(
                '0' => "http://img.vmi31.com/201885/15381181212565.jpg",
                '1' => "http://img.vmi31.com/201885/153811812125710.jpg"
            ));
            
            $data_iii['time'] = time();
            
            $riii = $this->model->table('user_profile_article')
                ->data($data_iii)
                ->insert();
            $data_iiii['type'] = 1;
            $data_iiii['mid'] = $r;
            $data_iiii['title'] = "积分引流";
            $data_iiii['user_id'] = $this->user_id;
            $data_iiii['cover_pic'] = "/data/attached/coverpic/20180928/1538118161153811816112.jpeg";
            $data_iiii['content'] = "";
            
            $data_iiii['content_pic'] = serialize(array(
                '0' => "http://img.vmi31.com/201885/15381181555129.jpg",
                '1' => "http://img.vmi31.com/201885/15381181555113.jpg"
            ));
            
            $data_iiii['time'] = time();
            
            $riiii = $this->model->table('user_profile_article')
                ->data($data_iiii)
                ->insert();
            $data_iiiii['type'] = 1;
            $data_iiiii['mid'] = $r;
            $data_iiiii['title'] = "拓客商城VIP店主";
            $data_iiiii['user_id'] = $this->user_id;
            $data_iiiii['cover_pic'] = "/data/attached/coverpic/20180928/1538118203153811820357.jpeg";
            $data_iiiii['content'] = "";
            
            $data_iiiii['content_pic'] = serialize(array(
                '0' => "http://img.vmi31.com/201885/15381181905963.jpg",
                '1' => "http://img.vmi31.com/201885/15381181905955.jpg",
                '2' => "http://img.vmi31.com/201885/153811819059610.jpg",
            ));
            
            $data_iiiii['time'] = time();
            
            $riiiii = $this->model->table('user_profile_article')
                ->data($data_iiiii)
                ->insert();


        }  
        $this->assign('share_title',  (empty($userinfo["nick_name"]) ? $userinfo["user_name"] : $userinfo["nick_name"]) ."的拓客商城VIP微名片请惠存");

        $this->assign('share_description', $mainpageinfo["sign"]);//
        $this->assign('share_pic', $userinfo["user_avatar"]);//
        $user_info['user_avatar'] = $userinfo['user_avatar'];
        $user_info['nick_name'] = $userinfo['nick_name'];
        $user_info['kaopu'] = $userinfo['kaopu'];
        $user_info['viewguest'] = $userinfo['viewguest'];
        $this->assign('user_id',$this->user_id);
       // var_dump($user_info);exit;
        $this->assign('userinfo',$user_info);
        $this->assign('footer_index','affiliate');
        $this->display('Mycard.dwt');
    }
    function updatekaopunum()
    {
        $data_u['kaopu'] = $_POST['num'] ;
        $where_u['user_id'] = $_POST['userid'] ;

        
        $r = $this->model->table('users')
                            ->data($data_u)
                            ->where($where_u)
                            ->update();
        if($r){
            echo json_encode(array('code' => 200));exit;
        }else{
            echo json_encode(array('code' => 500));exit;
        }
    }
    function updateviewguest()
    {
        

        $userid = $_POST['userid']  ;
        $r = $this->model->query('UPDATE ' . $this->model->pre . "users SET viewguest = viewguest + 1 WHERE user_id = '$userid'");
        $userinfo = model('Users')->getusersinfo($userid);
        if($r){
            echo json_encode(array('code' => 200,"num"=>$userinfo['viewguest']));exit;
        }else{
            echo json_encode(array('code' => 500));exit;
        }
    }
    function deletebanner()
    {
        $userid = $this->user_id;
        $mid = $_POST['mid'];

        if($_POST['user_banner1']){
            $r = $this->model->query('UPDATE ' . $this->model->pre . "mainpage SET user_banner1 = '' WHERE user_id = '$userid' and mainpage_id='$mid' ");
        }
        if($_POST['user_banner2']){
            $r = $this->model->query('UPDATE ' . $this->model->pre . "mainpage SET user_banner2 = '' WHERE user_id = '$userid' and mainpage_id='$mid' ");
        }
        if($_POST['user_banner3']){
            
            $r = $this->model->query('UPDATE ' . $this->model->pre . "mainpage SET user_banner3 = '' WHERE user_id = '$userid' and mainpage_id='$mid' ");
        }
        
    }
      function newdeletebanner($mid,$banner)
    {
        $userid = $this->user_id;
        $mid = $mid;
       
        switch ($banner) {
            case '1':
                # code...
                 $r = $this->model->query('UPDATE ' . $this->model->pre . "mainpage SET user_banner1 = '' WHERE user_id = '$userid' and mainpage_id='$mid' "); 
                break;
            case '2':
                # code...
                  $r = $this->model->query('UPDATE ' . $this->model->pre . "mainpage SET user_banner2 = '' WHERE user_id = '$userid' and mainpage_id='$mid' "); 
                break;
            case '3':
                # code...
                $r = $this->model->query('UPDATE ' . $this->model->pre . "mainpage SET user_banner3 = '' WHERE user_id = '$userid' and mainpage_id='$mid' "); 
                break;
            default:
                # code...
                break;
        }
       
        
    }
    /*海报秀*/
    function postersshow()
    {
         if(!$_SESSION['user_vip']&&empty($_GET["u"])){
                $url = url('user/vipmarket');
                
                ecs_header("Location: $url\n");
        }
        $userInfo = model("Users")->get_users($_SESSION["user_id"]);
        
        if($userInfo){
            //登录的情况根据后台设置的文章来源判断哪些用户可以看
            $sql1 = 'SELECT * from '. '{pre}poster_cat '.' as pc inner join {pre}poster_resource as pr on pc.cat_id = pr.cat_id AND pr.resource_type='.$userInfo['resource'];
            

            
            $res = $this->model->query($sql1);

            foreach($res as $key1=>$vo1){

                $allowcat[] = $vo1['cat_id'];

            }

        }else{
            //不登录的情况，默认只能看拓客商城来源的文章
            //  $sql = 'SELECT cat_id, cat_name' .
            // ' FROM {pre}article_cat ' .
            // ' WHERE cat_type = 1 AND parent_id = 4'.
            // ' ORDER BY sort_order ASC';
            $sql1 = 'SELECT * from '. '{pre}poster_cat '.' as pc inner join {pre}poster_resource as pr on pc.cat_id = pr.cat_id AND pr.resource_type=1';
            
            $res = $this->model->query($sql1);

            foreach($res as $key1=>$vo1){

                $allowcat[] = $vo1['cat_id'];

            }

        }
        $resource_type = empty($userInfo['resource'])?1:$userInfo['resource'];
        $this->page = I('request.page') ? intval(I('request.page')) : 1;
        $sql = 'SELECT cat_id, cat_name' .
            ' FROM {pre}poster_cat ' .
            ' WHERE cat_type = 1 '.
            ' ORDER BY sort_order ASC';
        $data = $this->model->query($sql);
        
        foreach($data as $key=>$vo){
            //查询cat_id是否是允许显示的
            //var_dump($allowcat);exit;
            if(in_array($data[$key]['cat_id'], $allowcat)){
               $data[$key]['url'] = url('index', array('id'=>$vo['cat_id'])); 
           }else{
                unset($data[$key]);
           }
     
            
        }

        if(!$this->cat_id)$this->cat_id=$data[0]['cat_id'];
        $id =$this->cat_id;

     
        $type = I('request.type','default');

        $poster_list = model('PosterBase')->get_cat_poster($this->cat_id, $this->page, $this->size, $this->keywords,$type,$resource_type);

        $count = model('PosterBase')->get_poster_count($this->cat_id, $this->keywords);
        $this->pageLimit(url('user/postersshow', array('id' => $this->cat_id,'type' => $type)), $this->size);
        $userinfo = model('Users')->getusersinfo($_SESSION["user_id"]);
        $this->assign('shop_url',C('shop_url'));
        $this->assign('pager', $this->pageShow($count));
        $this->assign('poster_list',$poster_list);
        $this->assign('type',$type);
        $this->assign('poster_categories', $data); //文章分类树
        $this->assign('id',$id);
        $this->assign("userinfo",$userinfo);
        
        //底部导航高亮
        $this->assign("footer_index",'user');
        
        $this->display('postersShow.dwt');
    }
     function updateposterclick()
    {
        $data_u['click_count'] = $_POST['num'] ;
        $where_u['poster_id'] = $_POST['poster_id'] ;

       
        $r = $this->model->query('UPDATE ' . $this->model->pre . "poster SET click_count = click_count + 1 WHERE poster_id = '{$_POST['poster_id']}'");

  
        $result = $this->model->table('poster')
            ->field('click_count')
            ->where($where_u)
            ->find();
        if($r){
            echo json_encode(array('code' => 200,"num"=>$result['click_count'],"poster_id"=>$_POST['poster_id']));exit;
        }else{
            echo json_encode(array('code' => 500));exit;
        }
    }

    //新增主页20180928
    function user_addmainpage()
    {
        
    }

    function scanqrcode(){

         $userinfo =  model('Users')->getusersinfo($_SESSION['user_id']);
         $shortLink = $this->getShortUrl(__URL__ . "/index.php?m=default&c=user&a=register&u=" . $_SESSION["user_id"]);
      
        
        $this->assign('share_link', $shortLink); 
        $this->assign('userinfo',$userinfo);
        $this->display('scanqrcode.dwt');

    }



    /*模版选择*/
    function cardselect()
    {
        $resouce = I("get.resource");
        $templatelist= model('Users')->getmainpagetemplatelist(1);
        $templatelistt= model('Users')->getmainpagetemplatelist(2);
        /*查询该会员已经有几套模板，如果是三套就弹出提示框*/
       $mainpagelist =  model('Users')->getusermainpagelist($_SESSION["user_id"]);
       $num = count($mainpagelist);
     
        $this->assign('user_id',$_SESSION['user_id']);
        $this->assign("resource",$resouce);
        $this->assign("num",$num);
        $this->assign("templatelist",$templatelist);
        $this->assign("templatelistt",$templatelistt);
        $this->display('card_select.dwt');
    }

    /*零钱转出到自己的账户余额*/
    function bonus_out(){

        # code...
        $user = model("Users")->find([
            "user_id" => $this->user_id
        ], "autonym,mobile_phone,real_name,ID_card,bank,bank_card,autonym_remark,autonym_submit_time,autonym_audit_time");
        $autonym = $user['autonym'];
        
        $account = model("ClipsBase")->userAccount($this->user_id);
        

        $this->assign('cansmall_change',$account['small_change']);
        $this->assign('headerContent', L('withdraw'));
        $this->assign('user', $user);
        $this->assign('small_change', $account['small_change']);
        $this->assign('title', L('label_user_surplus'));
        /*转出功能*/
        $this->display('user_bonus_out.dwt');

    }
    /*奖金转出到我的余额账户*/
    function bonus_out_action()
    {
       //  $data = array(
                    
       //                  "mid" =>$_SESSION['user_id'],
       //                  "money" =>$_POST['amount'],
       //                  "remark" =>'申请转出',
       //                  "type" =>8,
       //                  "token" =>encryptapitoken()
       //              );

       // $ret =  post_log($data,API_URL."/api/account/get",5);
   
       if($_POST){
             /**/
            //model('ClipsBase')->log_account_change($_SESSION['user_id'], $_POST['amount'], 0, 0, 0, "收益账户转出到余额账户", ACT_BONUS_OUT);
             if($_POST['amount']<=0){
                show_message('转出金额错误', L('back_up_page'), url('bonus_out'), 'info');
             }
            model('ClipsBase')->new_log_account_change($_SESSION['user_id'], $_POST['amount'],"钱包转出到余额账户",1,1);
            model('ClipsBase')->new_log_account_change($_SESSION['user_id'], -$_POST['amount'],"钱包转出到余额账户",1,11);
            $this->redirect(url('pay_succeed', array('money'=>number_format($_POST['amount'],2))));
            

       }else{

             show_message('转出失败,账户余额不足', L('back_up_page'), url('bonus_out'), 'info');
       }

        

    }
    /*余额互转*/
    function user_money_transfer()
    {

        /*余额互转的页面*/

    }
    function ajaxuser_money_transfer()
    {
        $fromuser = $_POST['fromuser'];
        $touser = $_POST['touser'];
        //model('ClipsBase')->log_account_change($_SESSION['user_id'], $amount, -$amount, 0, 0, "馀额提现", ACT_DRAWING);
        model('ClipsBase')->new_log_account_change($_SESSION['user_id'], $amount,"馀额提现",ACT_DRAWING,1);
        model('ClipsBase')->new_log_account_change($_SESSION['user_id'],-$amount,"馀额提现",ACT_DRAWING,1);

    }

    // 零钱明细
    function user_income_account(){

       
     
      
         
         $this->display('user_income_account.dwt');
    }
    

    /**
     * @throws Exception
     */
    function ajaxfinduser(){
      
        $user_name = $_POST['user_name'];

        $res = model('Users')->getUsersByUserName($user_name);

        if($res){
            $result['status'] = '1';

            echo json_encode($result);exit;

        }else{
            $result['status'] = '0';

            echo json_encode($result);exit;
        }


    }
    /*签到领KD豆*/
    function ajax_sign_kd()
    {
    
       $userinfo =  model('Users')->getusersinfo($_SESSION['user_id']);
       $sign_time=$userinfo['sign_time'];
       $user_id = $_SESSION['user_id'];
       /*KD豆可以获取到的积分数量*/
       $kd_config =array("1","1","2","2","4","4","10");
    
        $int=date('Y-m-d');
        $int=strtotime($int);//5
        $ints=$int+86400;  //6
        $int_s=$int-86400;  //4
        //当天已签到

        if($int<$sign_time&&$sign_time<$ints){
          // echo '您已签到';
         
            $result['code'] = 408;
            $result['msg'] = "您已签到";
            echo json_encode($result);exit;
        }
        //昨天未签到，积分，天数在签到修改为1
        if($sign_time<$int_s){

          $count=1;
          $pay_points=$kd_config[$count-1];
          $sign_time=strtotime(date('Y-m-d H:s:i'));

          $rs =  model('Users')->updateTask($_SESSION['user_id'],$pay_points,$count,$sign_time);
         
          if($rs){
               //model('ClipsBase')->log_account_change_new($_SESSION['user_id'], 0, 0, 0, $pay_points, "签到送福豆", ACT_KD);
               model('ClipsBase')->new_log_account_change($_SESSION['user_id'],$pay_points,"签到送鱼宝",ACT_KD,6);
            $result['code'] = 200;
            $result['msg'] = "签到成功修改为1";
            $result['count'] = $count;
            $result['daykd'] =1;
            echo json_encode($result);exit;
          }else{
            $result['code'] = 403;
            $result['msg'] = "签到失败";
            $result['count'] = $count;
            echo json_encode($result);exit;
          }
          
          // echo '签到成功修改为1';
        }
        //请签到
        if($int_s<$sign_time&&$sign_time<$int){
         
          $count=$userinfo['count']+1;
          if($count==8){
            $count =1;
          }
          $pay_points=$kd_config[$count-1];
      
          $sign_time=strtotime(date('Y-m-d H:s:i'));
         
          $rs = model('Users')->updateTask($_SESSION['user_id'],$pay_points,$count,$sign_time);
          // echo '签到成功+1';
          if($count==1&&$rs){
             //model('ClipsBase')->log_account_change_new($_SESSION['user_id'], 0, 0, 0, $pay_points, "签到送福豆", ACT_KD);
             model('ClipsBase')->new_log_account_change($_SESSION['user_id'],$pay_points,"签到送鱼宝",ACT_KD,6);
            $result['code'] = 206;
            $result['msg'] = "签到成功+".$pay_points;
            $result['count'] = $count;
            $result['daykd'] =$pay_points;
            echo json_encode($result);exit;
          }
          if($rs){
             //model('ClipsBase')->log_account_change_new($_SESSION['user_id'], 0, 0, 0, $pay_points, "签到送福豆", ACT_KD);
             model('ClipsBase')->new_log_account_change($_SESSION['user_id'],$pay_points,"签到送鱼宝",ACT_KD,6);
            $result['code'] = 203;
            $result['msg'] = "签到成功+".$pay_points;
            $result['count'] = $count;
            $result['daykd'] =$pay_points;
            echo json_encode($result);exit;
          }else{
            $result['code'] = 403;
            $result['msg'] = "签到失败";
            $result['count'] = $count;
            echo json_encode($result);exit;
          }
          
        }
     
   
    
      
    }
    /*签到中心*/
    function sign_center()
    {
        $userinfo =  model('Users')->getusersinfo($_SESSION['user_id']);

        $sign_time=$userinfo['sign_time'];
   
        $yesterday = date('Y-m-d',$sign_time);
        $today=date('Y-m-d',time());
        
        $int=date('Y-m-d');

        $int=strtotime($int);//5
        $ints=$int+86400;  //6
        $int_s=$int-86400;  //4
        //当天已签到
        if($yesterday!==$today&&$userinfo['count']==7){
                /*如果昨天签到了，并且当前用户的签到天数已经是7天了*/
               
                 model("Users")->updateSignCount($_SESSION['user_id']);
        }
        if($int<$sign_time&&$sign_time<$ints){
          $this->assign('sign_already',1);
        }
         //昨天未签到，积分，天数在签到修改为
        if($sign_time<$int_s){
            
            model("Users")->updateSignCount($_SESSION['user_id']);
        }
        $userinfo =  model('Users')->getusersinfo($_SESSION['user_id']);
        $refferarticlenum = model('Users')->refferarticlenum($_SESSION['user_id']);
        $balancemoney = model('ClipsBase')->finduseraccount($_SESSION['user_id'],"pay_points");
        $this->assign('autonym',$userinfo['autonym']);
        $this->assign('u',$_SESSION['user_id']);
        $this->assign('kd',$balancemoney);
        $this->assign('count',$userinfo['count']);

        $this->assign('refferarticlenum',$refferarticlenum?$refferarticlenum:0);
        $this->display('sign_center.dwt');
    }
    /*同步接口*/
    function tongbuuser()
    {
        
        
        
        
        
         $sql = "SELECT * FROM " . $this->model->pre . "users order by user_id desc";
        //$sql = "SELECT * FROM " . $this->model->pre . "users where user_id='87'";
                $result = $this->model->query($sql);
                /*更新VIP用户入团地址，针对老用户*/
            foreach ($result as $key4 => $value4) {
                 # code...
                /*如果老用户是vip，且*/
                    if($value4['user_rank']&&!$value4['country']&&!$value4['province']&&!$value4['country_id']&&!$value4['province_id']||!$value4['city']){
                            /*取订单表的第一笔订单的收件人地址作为入团地址*/
                            $where['user_id'] = $value4['user_id'];
                           
                            $order = $this->model->table('order_info')
                             ->field('country, province, city')
                        ->where($where)
                        ->find();

                      if($order){
                        $province = model('Users')->find_region_name($order['province']);
           
                      
                      
                        $city =  model('Users')->find_region_name($order['city']);
                              model('Users')->updateUserJoinAddress($value4['user_id'],1,$order['province'],$order['city'],'中国',$province,$city);
                      }
                      
                       

                    }

             } 
             /*更新用户的老用户等级以及生成邀请码*/
        foreach ($result as $key1 => $value1) {
            # code...
            $data_u = array();
             if($value1['rank_points']>=10000&&!$value1['user_rank']){
                /*如果邀请码为空，那么更新一个邀请码给他*/
           

               
                $where_u1['user_id'] = $value1['user_id'];
                $data_u1['user_rank'] = 1;
                $data_u1['rank_points'] = 0;
                $r1= $this->model->table('users')
                            ->data($data_u1)
                            ->where($where_u1)
                            ->update();

            }
            if(empty($value1['invite_code'])){
                /*如果邀请码为空，那么更新一个邀请码给他*/
                $invite_code = create_invite_code();

               
                $where_u['user_id'] = $value1['user_id'];
                $data_u['invite_code'] = $invite_code;

               $r= $this->model->table('users')
                            ->data($data_u)
                            ->where($where_u)
                            ->update();

            }
        }
        /*把用户同步到制度网*/
         $sql3 = "SELECT * FROM " . $this->model->pre . "users order by user_id desc";
        //$sql = "SELECT * FROM " . $this->model->pre . "users where user_id='87'";
                $result3 = $this->model->query($sql3);
        foreach ($result3 as $key3 => $value3) {
      
            $data3[$key3]['mid'] = $value3['user_id'];
            $data3[$key3]['vip_level'] =$value3['user_rank'];
            $data3[$key3]['user_name'] = $value3['user_name'];
            $data3[$key3]['head_img'] = $value3['user_avatar'];
            $data3[$key3]['country'] = $value3['country'];
            $data3[$key3]['province'] = $value3['province'];
            $data3[$key3]['city'] = $value3['city'];
            $data3[$key3]['referrer_id'] = $value3['parent_id'];
            $data3[$key3]['kd'] = $value3['pay_points'];
           

            
            
            
            # code...
        }

        $userupdatedata = array(
                       
                      
                         "users" =>$data3,
                    
                        
                     
                         "token" =>encryptapitoken()
                     );
      
                 $ret = model("Index")->postData($userupdatedata,"/api/user/push");
              
                if($ret['status']==200){
                    echo "同步成功";exit;
                }else{
                    echo $ret['data'];exit;
                }
       
        exit;
     


    }
    /*余额转账*/
    function surplusTransfer(){

         /*查询该用户的余额*/
        //  $user = model("Users")->find([
        //     "user_id" => $this->user_id
        // ], "user_money");

         //$user_money = $user['user_money'];
         //$this->assign('user_money',$user_money);
          $balance = model('ClipsBase')->finduseraccount($_SESSION['user_id'],"balance");
          $this->assign('user_money',$balance);
        $this->display('surplus_transfer.dwt');
    }
    /*返回被转帐用户信息*/
    function validateuser(){

         $user = model("Users")->find([
            "user_name" => $_POST['user_name']
        ], "user_name,nick_name,user_id,user_avatar");

         if($user){
            $data['user_name'] = $user['user_name'];
            $data['nick_name'] = empty($user['nick_name'])?$user['user_name']:$user['nick_name'];
            $data['user_id'] = $user['user_id'];
            $data['user_avatar'] = empty($user['user_avatar'])?"/themes/yutui/images/idx_user.png":$user['user_avatar'];
            $result['status'] = 200;
            $result['data'] = $data;
         }else{
            $result['status'] = 303;
            $result['data'] = "暂无数据";
         }
         echo json_encode($result);
            exit();
      
       
      
    }
    function validateusermoney(){
        $user = model("Users")->find([
            "user_name" => $_POST['user_name']
        ], "user_money");

        if($user['user_money']<$_POST['money']){
            $result['status'] = 303;
            $result['msg'] = "金额不够";
        }else{
            $result['status'] = 200;
            $result['msg'] = "金额够";
        }
    }
    function pay_succeed(){
        $money = $_GET['money'];

         $this->assign('money',$money);
         $this->display('pay_succeed.dwt');
    }


    //邀请好友
    function invite_myfriend(){

        $num = model('Users')->getChildNum($this->user_id);

        /*排行榜*/
        $user_list =model('Users')->getuserlist();
        foreach ($user_list as $key => $value) {
            # code...
            $user_list[$key]['user_name'] = substr($value3['user_name'], 0, 5).'****'.substr($value3['user_name'], 9) ;
            $user_list[$key]['nick_name'] = subtext($user_list[$key]['nick_name'],4);
            $user_list[$key]['reffernum'] = model('Users')->getChildNum($value['user_id']);
        }
        $reffernum = array_column($user_list,'reffernum');
        $result = array_multisort($reffernum, SORT_DESC, $user_list);

        $top = array_slice($user_list,0,10);
         $userinfo =  model('Users')->getusersinfo($_SESSION['user_id']);
        $shortLink = $this->getShortUrl(__URL__ . "/index.php?m=default&c=user&a=register&u=" . $_SESSION["user_id"]);
      
        if($userinfo['nick_name']){
            $this->assign('share_title',  "我是".$userinfo['nick_name'].",快来领50元红包!");
        }else{

            $this->assign('share_title',  "我是". substr($userinfo['user_name'], 0, 5).'****'.substr($userinfo['user_name'], 9).",快来领50元红包!");
        } 
        $this->assign('share_description', "新人专享福利！！折抵现金，立即领取福利>");//
        $this->assign('share_link', $shortLink); 
        $this->assign('num',$num);
        $this->assign('user_name',substr($userinfo['user_name'], 0, 5).'****'.substr($userinfo['user_name'], 9));
        $this->assign('userinfo',$userinfo);
        $this->assign('topone',$top[0]);
        $this->assign('toptwo',$top[1]);
        $this->assign('topthree',$top[2]);
        $this->assign('topfour',$top[3]);
        $this->assign('topfive',$top[4]);
        $this->assign('topsix',$top[5]);
        $this->assign('topseven',$top[6]);
        $this->assign('topeight',$top[7]);
        $this->assign('topnight',$top[8]);
        $this->assign('topten',$top[9]);
        $this->assign('showcode',1);
        $this->assign('footer_index','affiliate');
        $this->display('invite_myfriend.dwt');
    }


    // 权重明细
   function weight_details(){
       $data = array(
           "mid" =>$this->user_id,
           "token" =>encryptapitoken()
       );
       $ret =  model("Index")->postData($data,"/api/user/weight",5);
     
       if($ret['status'] == 200){
           $this->assign('list', $ret['data']);
       }
       $this->display('weight_details.dwt');
    }

//余额明细
    function balance_statement(){
        // 获取账户余额

        $balancemoney = model('ClipsBase')->finduseraccount($_SESSION['user_id'],"balance");
        $this->assign('money', $balancemoney);
         $this->display('balance_statement.dwt');
    }
        /*余额转账*/
    function surplusTransferDone(){

         /*转账给别人*/

        if($_POST['money']&&$_POST['receive_user_id']){
            $receiveuserinfo =  model('Users')->getusersinfo($_POST['receive_user_id']);
            //$r1 = model('ClipsBase')->log_account_change($_SESSION['user_id'], -$_POST['money'], 0, 0, 0, "余额转账", ACT_TRANSFER);
            model('ClipsBase')->new_log_account_change($_SESSION['user_id'],-$_POST['money'],"余额转账至用户".$receiveuserinfo['user_name'],ACT_TRANSFER,1);
         
           // $r2 = model('ClipsBase')->log_account_change($_POST['receive_user_id'], $_POST['money'], 0, 0, 0, "收到余额转账", ACT_TRANSFER);
               $senduserinfo =  model('Users')->getusersinfo($_SESSION['user_id']);
            model('ClipsBase')->new_log_account_change($_POST['receive_user_id'],$_POST['money'],"收到用户".$senduserinfo['user_name']."余额转账",ACT_TRANSFER,1);

        }
         $this->assign('money',number_format($_POST['money'],2));
         $receive_info = model('Users')->get_profile($_POST['receive_user_id']);
         
         $this->assign('receive_user',$receive_info['nick_name']?$receive_info['nick_name']:$receive_info['user_name']);

         $this->assign('order_submit_back', sprintf(L('order_submit_back'), L('back_home'), L('goto_user_center')));

         $this->display('surplus_transfer_done.dwt');
    }
    /*选择语言*/
    function select_lang(){
        $this->display('select_lang.dwt');
    }

    /*用户--商城旧订单就算权重和入团累计*/
    function updateweightandmoney(){


        $sql = "SELECT * FROM " . $this->model->pre . "users order by user_id desc";
        //$sql = "SELECT * FROM " . $this->model->pre . "users where user_id='87'";
        $result = $this->model->query($sql);
       
        foreach ($result as $key => $value) {
            $where['user_id'] = $value['user_id'];
            $order = $this->model->table('order_info')
                    ->field('user_id')
                    ->where($where)
                    ->find();
                 if($order){
                    $data[$key]['id'] = $value['user_id'];
                    $data[$key]['money'] =300;
                    $data[$key]['weight'] = 1;
                 }     

            # code...
        }
         $data1 = array(

   
           "data"=>$data,
           "token" =>encryptapitoken()
       );
      
        $ret =  model("Index")->postData($data1,"/api/weightOrtotal",5);
     
        if($ret['status']==200){
                    echo "同步成功";exit;
                }else{
                    echo $ret['data'];exit;
                }
    }

    private function cleanUserCache($userid){
        $user = model("Users")->find(["user_id"=>$userid]);
        if(!empty($user["openid"]))self::$cache->setValue("poster".$user["openid"], null, 1);
        
    }


    /**
     * 获得访问者浏览器语言
     */
    public function get_lang() {
      $user_info = model('Users')->get_profile($this->user_id);
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $lang = substr($lang, 0, 5);
          
            if (preg_match('/zh-cn/i',$lang)) {
                $lang = '简体中文';
                if($user_info['lang']!=='zh_cn'){
                    model("Users")->updateLang($this->user_id,"zh_cn");
                    //如果当前操作系统的语言和网站的语言不一致，刷新一次页面
                    $_SESSION['lang'] = "zh_cn";
                    $this->redirect('/index.php?m=default&c=user&a=index&u='.$this->user_id);
                }
                
     
            } else if (preg_match('/zh/i',$lang)) {
                $lang = '繁体中文';
                if($user_info['lang']!=='tw_cn'){
                    model("Users")->updateLang($this->user_id,"tw_cn");
                    //如果当前操作系统的语言和网站的语言不一致，刷新一次页面
                    $_SESSION['lang'] = "tw_cn";
                    $this->redirect('/index.php?m=default&c=user&a=index&u='.$this->user_id);
                }
       
            } else {
                $lang = 'English';
            }
        
            
           
        } else {
         
          model("Users")->updateLang($this->user_id,"zh_cn");

        }
          
    }

    /*
    *批量转移理想数据
    */
    public function battransferdata()
    {
        $lxdata = $this->model->table('fcmember')->select();
        
        foreach ($lxdata as $key => $value) {
            # code...

            $res = $this->model->table('fcaccount')->where('mid = ' . $value['id'])
                    ->find();
            $res1 = $this->model->table('member_bank')->where('mid = ' . $value['id'])
                    ->find();   
            $data['user_id'] = $value['id'];
            $data['email'] = $value['email'];
            $data['user_name'] = $value['account'];
            $data['password'] = $value['password'];
            $data['sex'] = $value['sex'];
            $data['user_money'] =  $res['balance'];//余额
            $data['user_rank'] = $value['grade'];
            $data['nick_name'] = $value['nickname'];
            $data['parent_id'] = $value['pid'];
            $data['mobile_phone'] = $value['telephone'];
            $data['mobile_zone'] = 86;
            $data['real_name'] = $value['real_name'];
            $data['bank'] = $res1['bank_name'];
            $data['bank_card'] = $res1['bank_account'];
            $data['address_id'] = 0;
            $data['invite_code'] = create_invite_code();
            $this->model->table('users')->data($data)->insert();
            model("Users")->newaddvipamanageaccount($data['user_id'],$data['user_name']);
            model("Users")->updateUserVip($value['id'],1);
        }
        echo "转移完成";exit;

    }



    // 拓客会员老会员登录
    public function oldloginaccount(){
    
        if (IS_POST) {

            
            $vip_manage_account = I('post.username', '', 'trim');

            $password = I('post.password', '', 'trim');
            $resource = I('post.resource', '', 'trim');
            $back_act = I('back_act', '', 'trim');
            $this->back_act = empty($back_act) ? url('user/index') : $back_act;
            
            /* $captcha = intval(C('captcha'));
            if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
                if (empty($_POST['captcha'])) {
                    show_message(L('invalid_captcha'), L('relogin_lnk'), url('login', array(
                        'back_act' => urlencode($this->back_act)
                    )), 'error');
                }
                
            } */
          
            //暂时写死  等何山改
            // $resource = 1;
            $res= model("Users")->get_user_info_new($vip_manage_account);
            if($res['status']!=1){
                    show_message("您的账号已冻结", "", url('oldloginaccount'), 'error');
            }
               
            if (self::$user->newdistributionLogin($vip_manage_account, $password,$resource, isset($_POST['remember']))) {
                model('Users')->update_user_info();
                model('Users')->recalculate_price();
                if($_SESSION['openid']&&$_SESSION['user_id']){
            /*更新用户的openid*/
                
                $data_up['openid'] = $_SESSION['openid'];
                $where_up['user_id'] = $_SESSION['user_id'];
                $this->model->table('users')
                    ->data($data_up)
                    ->where($where_up)
                    ->update();
                }
               
                $jump_url = empty($this->back_act) ? url('index',array('u'=>$_SESSION['user_id'])) : $this->back_act;
                
                //兼容line注释
               /*  if(is_wechat_browser()){


                        $this->wechat_init();
                        
                        model('Users')->unbindopenid($_SESSION['openid']);

                        model('Users')->bindopenid($_SESSION['openid']);
                        
                }
                 */

                  
                $this->redirect($jump_url);
            } else {
                $_SESSION['login_fail'] ++;

                show_message(L('login_failure'), L('relogin_lnk'), url('oldloginaccount', array(
                    'back_act' => urlencode($this->back_act)
                )), 'error');
            }
            exit();
            // 登录页面显示
            $back_act = I('back_act', '', 'urldecode');
            if (empty($back_act)) {
                if (empty($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
                    $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], url('user/index')) ? url('user/index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
                } else {
                    $back_act = url('user/index');
                }
            }
            // 来源是退出地址时 默认会员中心
            $this->back_act = strpos($back_act, 'logout')? url('user/index') : $back_act;
            $this->back_act = (strpos($back_act, 'register')||strpos($back_act, 'get_password_phone')||strpos($back_act, 'oldloginaccount'))? url('index') : $back_act;
            // 验证码相关设置
            $captcha = intval(C('captcha'));
            if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
                $this->assign('enabled_captcha', 1);
                $this->assign('rand', mt_rand());
            }
            
            // 微信浏览器显示授权登录
            if (get_third_browser()) {
                $this->assign('oauth_url', url('user/index', array(
                    'flag' => 'oauth'
                )));
            }


        }


        $this->assign('title', L('login'));
        $this->assign('step', I('get.step'));
        $this->assign('anonymous_buy', C('anonymous_buy'));
        $this->assign('back_act', $this->back_act);
        $this->display('oldloginaccount.dwt');
    }

    //个人主页 测试用，暂时别删除
   public function test_business_card(){
    
     
        $this->display('test_business_card.dwt');
    }
}

<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：WechatControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信公众平台API
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class WechatController extends CommonController
{
    private $weObj = '';
    private $orgid = '';
    private $wechat_id = 0;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        // 获取公众号配置
        $this->orgid = I('get.orgid', '', 'trim');
        if ($this->orgid) {
            $wxinfo = $this->get_config($this->orgid);

            $config['token'] = "619939af304e5cded762cde04e46695c";
            $config['appid'] = "wxc938a73952df8be0";
            $config['appsecret'] = "3f7c78f40e2007d2deecdeeef37a5638";
          
            $this->weObj = new Wechat($config);
            $this->weObj->valid();
            $this->wechat_id = 1;
        }

    }

    /**
     * 执行方法
     */
    public function index()
    {
        // 事件类型
        $type = $this->weObj->getRev()->getRevType();
        $wedata = $this->weObj->getRev()->getRevData();
        $keywords = '';
        // 兼容更新用户关注状态（未配置微信通之前关注的粉丝）
        $userinfo = $this->weObj->getUserInfo($wedata['FromUserName']);



        if(!empty($userinfo) && !empty($userinfo['unionid'])){
            $user_data = array(
                'subscribe' => $userinfo['subscribe'],
                'subscribe_time' => $userinfo['subscribe_time'],
            );
            $res = $this->model->table('wechat_user')->field('openid, unionid')->where(array('unionid' => $userinfo['unionid'], 'wechat_id' => $this->wechat_id))->find();
            if(!empty($res)){
                $this->model->table('wechat_user')->data($user_data)->where(array('unionid' => $userinfo['unionid'], 'wechat_id' => $this->wechat_id))->update();
            }
        }

        if ($type == Wechat::MSGTYPE_TEXT) {
            $keywords = $wedata['Content'];
        } elseif ($type == Wechat::MSGTYPE_EVENT) {
            if ('subscribe' == $wedata['Event']) {
                $scene_id = 0;


                // 用户扫描带参数二维码(未关注)
                if (isset($wedata['Ticket']) && !empty($wedata['Ticket'])) {
                    $scene_id = $this->weObj->getRevSceneId();
                    $flag = true;
                    // 关注
                    $this->subscribe($wedata['FromUserName'], $scene_id);
                    // 关注时回复信息
                    $this->msg_reply('subscribe');
                } else {
                    // 关注
                    // $this->newsubscribe($wedata['FromUserName']);
                    // // 关注时回复信息
                    $this->msg_reply('subscribe');
                }
            } elseif ('unsubscribe' == $wedata['Event']) {
                // 取消关注
                $this->unsubscribe($wedata['FromUserName']);
                exit();
            } elseif ('CLICK' == $wedata['Event']) {
                // 点击菜单
                $keywords = $wedata['EventKey'];
            } elseif ('VIEW' == $wedata['Event']) {
                $this->redirect($wedata['EventKey']);
            } elseif ('SCAN' == $wedata['Event']) {
                $scene_id = $this->weObj->getRevSceneId();
            } elseif ('LOCATION' == $wedata['Event']) {
                // 关注开启地理位置响应
                exit('success');
            } elseif ('MASSSENDJOBFINISH' == $wedata['Event']) {
                // 群发结果
                $data['status'] = $wedata['Status'];
                $data['totalcount'] = $wedata['TotalCount'];
                $data['filtercount'] = $wedata['FilterCount'];
                $data['sentcount'] = $wedata['SentCount'];
                $data['errorcount'] = $wedata['ErrorCount'];
                // 更新群发结果
                $this->model->table('wechat_mass_history')
                    ->data($data)
                    ->where('msg_id = "' . $wedata['MsgID'] . '"')
                    ->update();
                exit();
            } elseif ($wedata['Event'] == 'TEMPLATESENDJOBFINISH') {
                // 模板消息发送结束事件
                if ($wedata['Status'] == 'success') {
                    // 推送成功
                    $data = array('status' => 1);
                } elseif ($wedata['Status'] == 'failed:user block') {
                    // 用户拒收
                    $data = array('status' => 2);
                } else {
                    // 发送失败
                    $data = array('status' => 0); // status 0 发送失败，1 发送与接收成功，2 用户拒收
                }
                // 更新模板消息发送状态
                $this->model->table('wechat_template_log')->data($data)->where(array('msgid' => $wedata['MsgID'], 'openid' => $wedata['FromUserName']))->update();
                exit();
            }
        } else {
            $this->msg_reply('msg');
            exit();
        }
        //扫描二维码
        if(!empty($scene_id)){
            $qrcode_fun = $this->model->table('wechat_qrcode')->field('function')->where('scene_id = "'.$scene_id.'"')->getOne();
            //扫码引荐
            if(!empty($qrcode_fun) && isset($flag)){
                //增加扫描量
                $this->model->table('wechat_qrcode')->data('scan_num = scan_num + 1')->where('scene_id = "'.$scene_id.'"')->update();
            }
            $keywords = $qrcode_fun;
        }
        // 回复
        if (!empty($keywords)) {
           
            if($keywords=="qrposter"){
       
                $this->poster($wedata['FromUserName']);
                return;
            }
            
            $keywords = html_in($keywords);
            //记录用户操作信息
            $this->record_msg($wedata['FromUserName'], $keywords);
            // 多客服
            $rs = $this->customer_service($wedata['FromUserName'], $keywords);
            if (empty($rs) && $keywords != 'kefu') {
                // 功能插件
                $rs1 = $this->get_function($wedata['FromUserName'], $keywords);
                if (empty($rs1)) {
                    // 关键词回复
                    $rs2 = $this->keywords_reply($keywords);
                    if (empty($rs2)) {
                      // 消息自动回复
                      $this->msg_reply('msg');
                      //推荐商品
                      // $rs_rec = $this->recommend_goods($wedata['FromUserName'], $keywords);
                    }
                }
            }
        }
    }
        /**
     * 关注处理
     *
     * @param array $info
     */
    private function newsubscribe($openid = '', $scene_id = 0)
    {
        if(empty($openid)){
            exit('null');
        }

        // 获取微信用户信息
        $info = $this->weObj->getUserInfo($openid);
        if (empty($info)) {
            $this->weObj->resetAuth();
            exit('null');
        } else {
            $data = array(
                'wechat_id' => $this->wechat_id,
                'subscribe' => $info['subscribe'],
                'openid' => $info['openid'],
                'nickname' => $info['nickname'],
                'sex' => $info['sex'],
                'language' => $info['language'],
                'city' => $info['city'],
                'country' => $info['country'],
                'province' => $info['province'],
                'headimgurl' => $info['headimgurl'],
                'subscribe_time' => $info['subscribe_time'],
                'remark' => $info['remark'],
                'group_id' => isset($info['groupid']) ? $info['groupid'] : $this->weObj->getUserGroup($openid),
                'unionid' => isset($info['unionid']) ? $info['unionid'] : '',
            );
        }
        

        // 未关注
       
         
            $msg = array(
                    'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => array(
                        'content' => "333333333"
                    )
                );
                $this->weObj->sendCustomMessage($msg);
            
       
    }
    /**
     * 关注处理
     *
     * @param array $info
     */
    private function subscribe($openid = '', $scene_id = 0)
    {
        if(empty($openid)){
            exit('null');
        }

        // 获取微信用户信息
        $info = $this->weObj->getUserInfo($openid);
        if (empty($info)) {
            $this->weObj->resetAuth();
            exit('null');
        } else {
            $data = array(
                'wechat_id' => $this->wechat_id,
                'subscribe' => $info['subscribe'],
                'openid' => $info['openid'],
                'nickname' => $info['nickname'],
                'sex' => $info['sex'],
                'language' => $info['language'],
                'city' => $info['city'],
                'country' => $info['country'],
                'province' => $info['province'],
                'headimgurl' => $info['headimgurl'],
                'subscribe_time' => $info['subscribe_time'],
                'remark' => $info['remark'],
                'group_id' => isset($info['groupid']) ? $info['groupid'] : $this->weObj->getUserGroup($openid),
                'unionid' => isset($info['unionid']) ? $info['unionid'] : '',
            );
        }
        // 公众号启用微信开发者平台，检查unionid
        if(empty($data['unionid'])){
            // exit('关注失败，请联系管理员开通微信开放平台');
            exit('null');
        }
        // 已关注用户基本信息
        update_wechat_unionid($info, $this->wechat_id); //兼容更新平台粉丝unionid

        // $condition = array('openid' => $data['openid'], 'wechat_id' => $this->wechat_id);
        $condition = array('unionid' => $data['unionid'], 'wechat_id' => $this->wechat_id);
        $result = $this->model->table('wechat_user')->field('ect_uid, openid, unionid')->where($condition)->find();
        // 查找用户是否存在
        if (isset($result)) {
            $users = $this->model->table('users')->where(array('user_id' => $result['ect_uid']))->find();
            if (empty($users) || empty($result['ect_uid'])) {
                $this->model->table('wechat_user')->where($condition)->delete();
                $result = array();
                unset($_SESSION['user_id']);
            }
        }

        // 未关注
        if (empty($result)) {

            // 兼容原users表aite_id
            // $aite_id = 'wechat_' . $data['unionid'];
            // $users = $this->model->table('users')->field('user_id')->where(array('aite_id' => $aite_id))->find();
            // if(!empty($users)){
            //     // 同步社会化登录表
            //     $res['user_id'] = $users['user_id'];
            //     model('Users')->update_connnect_user($res, $type);
            // }

            // 兼容原touch_user_info表
            $aite_id = 'wechat_' . $data['unionid'];
            $old_userinfo = model('Users')->get_one_user($aite_id);
            if(!empty($old_userinfo)){
                // 同步社会化登录表
                $res = array(
                    'user_id' => $old_userinfo['user_id'],
                    'unionid' => $data['unionid'],
                    'nickname' => $data['nickname'],
                    );
                model('Users')->update_connnect_user($res, 'wechat');
                // 删除旧表信息
                $where['user_id'] = $old_userinfo['user_id'];
                $this->model->table('touch_user_info')->where($where)->delete();
            }

            // 其他平台(PC,APP)是否注册
            $userinfo = model('Users')->get_connect_user($data['openid']);

            // 是否绑定注册
            if (empty($userinfo)){
                // 设置的用户注册信息               
                $username = model('Users')->get_wechat_username($data['unionid'], 'wechat');
                $password = mt_rand(100000, 999999);
                // 通知模版
                $template = '默认用户名：' . $username . "\r\n" . '默认密码：' . $password;              
                $email = $username . '@qq.com';
                // 查询推荐人ID
                if(!empty($scene_id)){
                    $scene_user_id = $this->model->table("users")->field('user_id')->where(array('user_id'=>$scene_id))->getOne();
                }
                $scene_user_id = empty($scene_user_id) ? 0 : $scene_user_id;
                // 用户注册
                $extend = array(
                    'parent_id' => $scene_user_id,
                    // 'nick_name' => $data['nickname'],
                    // 'aite_id' => 'wechat_' . $data['unionid'],
                    'sex' => $data['sex'],
                    // 'user_picture' => $data['headimgurl']
                );
                if (model('Users')->register($username, $password, $email, $extend) !== false) {
                    // 同步社会化登录用户信息表
                    $res = array(
                        'user_id' => $_SESSION['user_id'],
                        'unionid' => $data['unionid'],
                        'nickname' => $data['nickname'],
                        );
                    model('Users')->update_connnect_user($res, 'wechat');
                    model('Users')->update_user_info();
                } else {
                    exit('null');
                }
                // 注册微信资料
                $data['ect_uid'] = $_SESSION['user_id'];
                // $data['parent_id'] = $scene_user_id;
            }

            // 新增微信粉丝
            $this->model->table('wechat_user')->data($data)->insert();
            // 新用户送红包
            $bonus_msg = $this->send_message($openid, 'bonus', $this->weObj, 1);
            if (!empty($bonus_msg)) {
                $template = !$template ?  $template . "\r\n" . $bonus_msg['content'] : $bonus_msg['content'];
            }
            // 微信端发送消息
            if(!empty($template)){
                $msg = array(
                    'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => array(
                        'content' => $template
                    )
                );
                $this->weObj->sendCustomMessage($msg);
                //记录用户操作信息
                $this->record_msg($openid, $template, 1);
            }
            
        } else {
            $template = $data['nickname'] .  '，欢迎您再次回来';
            // 更新微信用户资料
            $this->model->table('wechat_user')->data($data)->where($condition)->update();

            // 先授权登录后再关注送红包
            $bonus_num = $this->model->table('user_bonus')->where('user_id = "'.$result['ect_uid'].'"')->count();
            if($bonus_num <= 0){
                $bonus_msg = $this->send_message($openid, 'bonus', $this->weObj, 1);
                if (! empty($bonus_msg)) {
                    $template = $template . "\r\n" . $bonus_msg['content'];
                }
            }
            // 微信端发送消息
            $msg = array(
                'touser' => $openid,
                'msgtype' => 'text',
                'text' => array(
                    'content' => $template
                )
            );
            $this->weObj->sendCustomMessage($msg);
        }
    }

    /**
     * 取消关注
     *
     * @param string $openid
     */
    public function unsubscribe($openid = '')
    {
        // 未关注
        $where['openid'] = $openid;
        $where['wechat_id'] = $this->wechat_id;
        $rs = $this->model->table('wechat_user')->where($where)->count();
        // 修改关注状态
        if ($rs > 0) {
            $data['subscribe'] = 0;
            $this->model->table('wechat_user')->data($data)->where($where)->update();
        }
    }

    /**
     * 被动关注，消息回复
     *
     * @param string $type
     * @param string $return
     */
    private function msg_reply($type, $return = 0)
    {
        $replyInfo = $this->model->table('wechat_reply')
            ->field('content, media_id')
            ->where('type = "' . $type . '" and wechat_id = ' . $this->wechat_id)
            ->find();
        if (!empty($replyInfo)) {
            if (!empty($replyInfo['media_id'])) {
                $replyInfo['media'] = $this->model->table('wechat_media')
                    ->field('title, content, file, type, file_name')
                    ->where('id = ' . $replyInfo['media_id'])
                    ->find();
                if ($replyInfo['media']['type'] == 'news') {
                    $replyInfo['media']['type'] = 'image';
                }

                // 上传多媒体文件
                $filename = ROOT_PATH . $replyInfo['media']['file'];
                $rs = $this->weObj->uploadMedia(array('media' => realpath_wechat($filename)), $replyInfo['media']['type']);

                // 回复数据重组
                if ($rs['type'] == 'image' || $rs['type'] == 'voice') {
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id']
                        )
                    );
                } elseif ('video' == $rs['type']) {
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id'],
                            'Title' => $replyInfo['media']['title'],
                            'Description' => strip_tags($replyInfo['media']['content'])
                        )
                    );
                }
                $this->weObj->reply($replyData);
                //记录用户操作信息
                $this->record_msg($this->weObj->getRev()->getRevTo(), '图文信息', 1);
            } else {
                // 文本回复
                $replyInfo['content'] = html_out($replyInfo['content']);
                if($replyInfo['content']){
                    $this->weObj->text($replyInfo['content'])->reply();
                    //记录用户操作信息
                    $this->record_msg($this->weObj->getRev()->getRevTo(), $replyInfo['content'], 1);
                }
            }
        }
    }

    /**
     * 关键词回复
     *
     * @param string $keywords
     * @return boolean
     */
    private function keywords_reply($keywords)
    {
        $endrs = false;
        $sql = 'SELECT r.content, r.media_id, r.reply_type FROM ' . $this->model->pre . 'wechat_reply r LEFT JOIN ' . $this->model->pre . 'wechat_rule_keywords k ON r.id = k.rid WHERE k.rule_keywords = "' . $keywords . '" and r.wechat_id = ' . $this->wechat_id . ' order by r.add_time desc LIMIT 1';
        $result = $this->model->query($sql);
        if (!empty($result)) {
            // 素材回复
            if (!empty($result[0]['media_id'])) {
                $mediaInfo = $this->model->table('wechat_media')
                    ->field('id, title, content, digest, file, type, file_name, article_id, link')
                    ->where('id = ' . $result[0]['media_id'])
                    ->find();

                // 回复数据重组
                if ($result[0]['reply_type'] == 'image' || $result[0]['reply_type'] == 'voice') {
                    // 上传多媒体文件
                    $filename = ROOT_PATH . $mediaInfo['file'];
                    $rs = $this->weObj->uploadMedia(array('media' => realpath_wechat($filename)), $result[0]['reply_type']);
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id']
                        )
                    );
                    // 回复
                    $this->weObj->reply($replyData);
                    $endrs = true;
                } elseif ('video' == $result[0]['reply_type']) {
                    // 上传多媒体文件
                    $filename = ROOT_PATH . $mediaInfo['file'];
                    $rs = $this->weObj->uploadMedia(array('media' => realpath_wechat($filename)), $result[0]['reply_type']);
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id'],
                            'Title' => $replyInfo['media']['title'],
                            'Description' => strip_tags($replyInfo['media']['content'])
                        )
                    );
                    // 回复
                    $this->weObj->reply($replyData);
                    $endrs = true;
                } elseif ('news' == $result[0]['reply_type']) {
                    // 图文素材
                    $articles = array();
                    if (!empty($mediaInfo['article_id'])) {
                        $artids = explode(',', $mediaInfo['article_id']);
                        foreach ($artids as $key => $val) {
                            $artinfo = $this->model->table('wechat_media')
                                ->field('id, title, digest, file, content, link')
                                ->where('id = ' . $val)
                                ->find();
                            $artinfo['content'] = sub_str(strip_tags(html_out($artinfo['content'])), 100);
                            $articles[$key]['Title'] = $artinfo['title'];
                            $articles[$key]['Description'] = empty($artinfo['digest']) ? $artinfo['content'] : $artinfo['digest'];
                            $articles[$key]['PicUrl'] = __URL__ . '/' . $artinfo['file'];
                            $articles[$key]['Url'] = empty($artinfo['link']) ? __HOST__ . url('article/wechat_news_info', array('id'=>$artinfo['id'])) : strip_tags(html_out($artinfo['link']));
                        }
                    } else {
                        $articles[0]['Title'] = $mediaInfo['title'];
                        $articles[0]['Description'] = empty($mediaInfo['digest']) ? sub_str(strip_tags(html_out($mediaInfo['content'])), 100) : $mediaInfo['digest'];
                        $articles[0]['PicUrl'] = __URL__ . '/' . $mediaInfo['file'];
                        $articles[0]['Url'] = empty($mediaInfo['link']) ? __HOST__ . url('article/wechat_news_info', array('id'=>$mediaInfo['id'])) : strip_tags(html_out($mediaInfo['link']));
                    }
                    // 回复
                    $this->weObj->news($articles)->reply();
                    //记录用户操作信息
                    $this->record_msg($this->weObj->getRev()->getRevTo(), '图文信息', 1);
                    $endrs = true;
                }
            } else {
                // 文本回复
                $result[0]['content'] = html_out($result[0]['content']);
                $this->weObj->text($result[0]['content'])->reply();
                //记录用户操作信息
                $this->record_msg($this->weObj->getRev()->getRevTo(), $result[0]['content'], 1);
                $endrs = true;
            }
        }
        return $endrs;
    }

    /**
     * 功能变量查询
     *
     * @param unknown $tousername
     * @param unknown $fromusername
     * @param unknown $keywords
     * @return boolean
     */
    public function get_function($fromusername, $keywords)
    {
        $return = false;
        $rs = $this->model->table('wechat_extend')
            ->field('name, command, config')
            ->where('(keywords like "%' . $keywords . '%" or command like "%' . $keywords . '%") and enable = 1 and wechat_id = ' . $this->wechat_id)
            ->order('id asc')
            ->find();
        $file = ROOT_PATH . 'plugins/wechat/' . $rs['command'] . '/' . $rs['command'] . '.class.php';
        if (file_exists($file)) {
            require_once ($file);
            $wechat = new $rs['command']();
            $data = $wechat->show($fromusername, $rs);
            if (! empty($data)) {
                // 数据回复类型
                if ($data['type'] == 'text') {
                    $this->weObj->text($data['content'])->reply();
                    //记录用户操作信息
                    $this->record_msg($fromusername, $data['content'], 1);
                } elseif ($data['type'] == 'news') {
                    $this->weObj->news($data['content'])->reply();
                    //记录用户操作信息
                    $this->record_msg($fromusername, '图文消息', 1);
                } elseif ($data['type'] == 'image') {
                    // 上传多媒体文件
                    $filename = dirname(ROOT_PATH) . '/' . $data['path'];
                    $rs = $this->weObj->uploadMedia(array('media' => realpath_wechat($filename)), 'image');
                    $this->weObj->image($rs['media_id'])->reply();
                    //记录用户操作信息
                    $this->record_msg($fromusername, '图片', 1);
                }
                $return = true;
            }
        }
        return $return;
    }

    /**
     * 商品推荐查询
     *
     * @param unknown $tousername
     * @param unknown $fromusername
     * @param unknown $keywords
     * @return boolean
     */
    public function recommend_goods($fromusername, $keywords)
    {
        $return = false;
        $rs = $this->model->table('wechat_extend')
            ->field('name, keywords, command, config')
            ->where('command = "recommend" and enable = 1 and wechat_id = ' . $this->wechat_id)
            ->order('id asc')
            ->find();

        $file = ROOT_PATH . 'plugins/wechat/' . $rs['command'] . '/' . $rs['command'] . '.class.php';
        if (file_exists($file)) {
            require_once($file);
            $wechat = new $rs['command']();
            $rs['user_keywords'] = $keywords;
            $data = $wechat->show($fromusername, $rs);
            if (!empty($data)) {
                // 数据回复类型
                if ($data['type'] == 'text') {
                    $this->weObj->text($data['content'])->reply();
                    //记录用户操作信息
                    $this->record_msg($fromusername, $data['content'], 1);
                } elseif ($data['type'] == 'news') {
                    $this->weObj->news($data['content'])->reply();
                    //记录用户操作信息
                    $this->record_msg($fromusername, '图文消息', 1);
                }
                $return = true;
            }
        }
        return $return;
    }

    /**
     * 主动发送信息
     *
     * @param unknown $tousername
     * @param unknown $fromusername
     * @param unknown $keywords
     * @param unknown $weObj
     * @param unknown $return
     * @return boolean
     */
    public function send_message($fromusername, $keywords, $weObj, $return = 0)
    {
        $result = false;
        $rs = $this->model->table('wechat_extend')
            ->field('name, command, config')
            ->where('keywords like "%' . $keywords . '%" and enable = 1 and wechat_id = ' . $this->wechat_id)
            ->order('id asc')
            ->find();
        $file = ROOT_PATH . 'plugins/wechat/' . $rs['command'] . '/' . $rs['command'] . '.class.php';
        if (file_exists($file)) {
            require_once($file);
            $wechat = new $rs['command']();
            $data = $wechat->show($fromusername, $rs);
            if (!empty($data)) {
                if ($return) {
                    $result = $data;
                } else {
                    $weObj->sendCustomMessage($data['content']);
                    $result = true;
                }
            }
        }
        return $result;
    }

    /**
     * 多客服
     *
     * @param unknown $fromusername
     * @param unknown $keywords
     */
    public function customer_service($fromusername, $keywords)
    {
        //$kfevent = $this->weObj->getRevKFClose();
        $result = false;
        //是否超时
        $timeout = false;
        //查找用户
        $uid = $this->model->table('wechat_user')->field('uid')->where(array('openid'=>$fromusername))->getOne();
        if($uid){
            $time_list = $this->model->table('wechat_custom_message')->field('send_time')->where(array('uid'=>$uid))->order('send_time desc')->limit(2)->select();
            if($time_list[0]['send_time'] - $time_list[1]['send_time'] > 3600 * 2){
                $timeout = true;
            }

        }

        // 是否处在多客服流程
        $kefu = $this->model->table('wechat_user')
            ->field('openid')
            ->where('openid = "' . $fromusername . '"')
            ->getOne();
        if ($kefu && $keywords == 'kefu') {
            $rs = $this->model->table('wechat_extend')
                ->field('config')
                ->where('command = "kefu" and enable = 1 and wechat_id = ' . $this->wechat_id)
                ->getOne();
            if (!empty($rs)) {
                $config = unserialize($rs);
                $msg = array(
                    'touser' => $fromusername,
                    'msgtype' => 'text',
                    'text' => array(
                        'content' => '欢迎进入多客服系统'
                    )
                );
                $this->weObj->sendCustomMessage($msg);
                //记录用户操作信息
                $this->record_msg($fromusername, $msg['text']['content'], 1);
                // 在线客服列表
                $online_list = $this->weObj->getCustomServiceOnlineKFlist();
                if ($online_list['kf_online_list']) {
                    foreach ($online_list['kf_online_list'] as $key => $val) {
                        if ($config['customer'] == $val['kf_account'] && $val['status'] > 0 && $val['accepted_case'] < $val['auto_accept']) {
                            $customer = $config['customer'];
                        } else {
                            $customer = '';
                        }
                    }
                }
                // 转发客服消息
                $this->weObj->transfer_customer_service($customer)->reply();
                $result = true;
            }
        }

        return $result;
    }

    /**
     * 获取用户昵称，头像
     *
     * @param unknown $user_id
     * @return multitype:
     */
    static function get_avatar($user_id)
    {
        $u_row = model('Base')->model->table('wechat_user')
            ->field('nickname, headimgurl')
            ->where('ect_uid = ' . $user_id)
            ->find();
        if (empty($u_row)) {
            $u_row = array();
        }
        return $u_row;
    }

    public static function snsapi_base(){
        $wxinfo = model('Base')->model->table('wechat')->field('token, appid, appsecret, status, oauth_redirecturi')->find();
        if(!empty($wxinfo['appid']) && is_wechat_browser() && ($_SESSION['user_id'] === 0 || empty($_SESSION['unionid']))){
            if($wxinfo['status']){
                self::snsapi_userinfo();
            }else{
                $config = model('Base')->model->table('wechat')->field('token, appid, appsecret, status')->find();
                if($config['status']){
                    $obj = new Wechat($config);
                    // 用code换token
                    if(isset($_GET['code']) && $_GET['state'] == 'repeat'){
                        $token = $obj->getOauthAccessToken();
                        $_SESSION['openid'] = $token['openid'];
                    }
                    // 生成请求链接
                    if (!empty($wxinfo['oauth_redirecturi'])) {
                        $callback = rtrim($wxinfo['oauth_redirecturi'], '/')  .'/'. $_SERVER['REQUEST_URI'];
                    }
                    if (!isset($callback)) {
                        $callback = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];;
                    }
                    $obj->getOauthRedirect($callback, 'repeat', 'snsapi_base');
                }
            }
        }
    }

    /**
     * 跳转到第三方登录
     */
    public static function snsapi_userinfo(){
        if(is_wechat_browser() && ($_SESSION['user_id'] === 0 || empty($_SESSION['unionid'])) && strtolower(CONTROLLER_NAME) != 'oauth'){
            $wxinfo   = model('Base')->model->table('wechat')->field('token, appid, appsecret, status, oauth_redirecturi')->find();
            if(!$wxinfo['status']){return false;}
            if (!empty($wxinfo['oauth_redirecturi'])) {
                $callback = rtrim($wxinfo['oauth_redirecturi'], '/')  .'/'. $_SERVER['REQUEST_URI'];
            }
            if (! isset($_SESSION['redirect_url'])) {
                $callback = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];;
            }
            $url = url('oauth/index', array('type' => 'wechat', 'back_url' => urlencode($callback)), 'org_mode');
            header("Location: ".$url);
            exit;
        }
    }

    /**
     * 记录用户操作信息
     */
     public function record_msg($fromusername, $keywords, $iswechat = 0){
        $uid = $this->model->table('wechat_user')->field('uid')->where(array('openid'=>$fromusername))->getOne();
        if($uid){
            $data['uid'] = $uid;
            $data['msg'] = $keywords;
            $data['send_time'] = time();
            //是公众号回复
            if($iswechat){
                $data['iswechat'] = 1;
            }
            $this->model->table('wechat_custom_message')
                ->data($data)
                ->insert();
        }
     }

    /**
     * 检查是否是微信浏览器访问
     */
    static function is_wechat_browser()
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($user_agent, 'MicroMessenger') === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 插件页面显示方法
     *
     * @param string $plugin
     */
    public function plugin_show()
    {
        if (is_wechat_browser() && (!isset($_SESSION['unionid']) || empty($_SESSION['unionid']) || empty($_SESSION['openid']))) {
            $back_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect('oauth/index', array('type' => 'wechat', 'back_url' => urlencode($back_url)));
        }
        $plugin = I('get.name');
        $file = ADDONS_PATH . 'wechat/' . $plugin . '/' . $plugin . '.class.php';
        if (file_exists($file)) {
            include_once($file);
            $wechat = new $plugin();
            $wechat->html_show();
        }
    }

    /**
     * 插件处理方法
     *
     * @param string $plugin
     */
    public function plugin_action()
    {
        $plugin = I('get.name', '', 'trim');
        $file = ADDONS_PATH . 'wechat/' . $plugin . '/' . $plugin . '.class.php';
        if (file_exists($file)) {
            include_once($file);
            $wechat = new $plugin();
            $wechat->action();
        }
    }

    /**
     * 获取公众号配置
     *
     * @param string $orgid
     * @return array
     */
    private function get_config($orgid = '')
    {
        $config = $this->model->table('wechat')
            ->field('id, token, appid, appsecret')
            ->where('orgid = "' . $orgid . '" and status = 1')
            ->find();
        if (empty($config)) {
            $config = array();
        }
        return $config;
    }

    /**
     * 获取access_token的接口
     * @return [type] [description]
     */
    public function check_auth()
    {
        $appid = I('get.appid');
        $appsecret = I('get.appsecret');
        if (empty($appid) || empty($appsecret)) {
            echo json_encode(array('errmsg' => '信息不完整，请提供完整信息', 'errcode' => 1));
            exit;
        }
        $config = $this->model->table('wechat')
            ->field('token, appid, appsecret')
            ->where('appid = "' . $appid . '" and appsecret = "'.$appsecret.'" and status = 1')
            ->find();
        if (empty($config)) {
            echo json_encode(array('errmsg' => '信息错误，请检查提供的信息', 'errcode' => 1));
            exit;
        }
        $obj = new Wechat($config);
        $access_token = $obj->checkAuth();
        if ($access_token) {
            echo json_encode(array('access_token' => $access_token, 'errcode' => 0));
            exit;
        } else {
            echo json_encode(array('errmsg' => $obj->errmsg, 'errcode' => $obj->errcode));
            exit;
        }
    }

     /**
     * 推荐分成二维码
     * @param  string  $user_name [description]
     * @param  integer $user_id   [description]
     * @param  integer $time      [description]
     * @param  string  $fun       [description]
     * @return [type]             [description]
     */
    static function rec_qrcode($user_name = '', $user_id = 0, $expire_seconds = 0, $fun = '', $force = false){
        if(empty($user_id)){
            return false;
        }
        // 默认公众号信息
        $wxinfo = model('Base')->model->table('wechat')->field('id, token, appid, appsecret, oauth_redirecturi, type, oauth_status')->where('default_wx = 1 and status = 1')->find();

        if (! empty($wxinfo) && $wxinfo['type'] == 2) {
            $config['token'] = $wxinfo['token'];
            $config['appid'] = $wxinfo['appid'];
            $config['appsecret'] = $wxinfo['appsecret'];
            // 微信通验证
            $weObj = new Wechat($config);
            if($force){
                $weObj->clearCache();
                model('Base')->model->table('wechat_qrcode')->where(array('scene_id'=>$user_id, 'wechat_id'=>$wxinfo['id']))->delete();
            }

            $qrcode = model('Base')->model->table('wechat_qrcode')->field('id, scene_id, type, expire_seconds, qrcode_url')->where(array('scene_id'=>$user_id, 'wechat_id'=>$wxinfo['id']))->find();
            if($qrcode['id'] && !empty($qrcode['qrcode_url'])){
                return $qrcode['qrcode_url'];
            }
            elseif($qrcode['id'] && empty($qrcode['qrcode_url'])){
                $ticket = $weObj->getQRCode((int)$qrcode['scene_id'], $qrcode['type'], $qrcode['expire_seconds']);
                if (empty($ticket)) {
                    $weObj->resetAuth();
                    //$weObj->errCode, $weObj->errMsg
                    return false;
                }
                $data['ticket'] = $ticket['ticket'];
                $data['expire_seconds'] = $ticket['expire_seconds'];
                $data['endtime'] = time() + $ticket['expire_seconds'];
                // 二维码地址
                $data['qrcode_url'] = $weObj->getQRUrl($ticket['ticket']);
                M()->table('wechat_qrcode')->data($data)->where(array('id'=>$qrcode['id']))->update();
                return $data['qrcode_url'];
            }
            else{
                $data['function'] = $fun;
                $data['scene_id'] = $user_id;
                $data['username'] = $user_name;
                $data['type'] = empty($expire_seconds) ? 1 : 0;
                $data['wechat_id'] = $wxinfo['id'];
                $data['status'] = 1;
                //生成二维码
                $ticket = $weObj->getQRCode((int)$data['scene_id'], $data['type'], $expire_seconds);
                if (empty($ticket)) {
                    $weObj->resetAuth();
                    //$weObj->errCode, $weObj->errMsg
                    return false;
                }
                $data['ticket'] = $ticket['ticket'];
                $data['expire_seconds'] = $ticket['expire_seconds'];
                $data['endtime'] = time() + $ticket['expire_seconds'];
                // 二维码地址
                $data['qrcode_url'] = $weObj->getQRUrl($ticket['ticket']);

                M()->table('wechat_qrcode')->data($data)->insert();
                return $data['qrcode_url'];
            }
        }
        return false;
    }
    
    
    function poster($openid){
        
        $imageid = self::$cache->getValue("poster".$openid);
       
        //    self::$cache->setValue("poster".$openid, null, 1);
        $userinfo = $this->weObj->getUserInfo($openid);
      
        $newuser = $this->model->table('users')->field("user_vip,user_id")->where(array("openid"=>$openid))->find();
        
        if(!$newuser['user_vip']){
            
            $this->weObj->text("您还未开通VIP权限")->reply();
        }
        if($imageid){
     
            /* $data = [
                "xml"=>[
                    "FromUserName"    => "gh_285bec3abc62",
                    "ToUserName"    => $openid,
                    "CreateTime"    => time(),
                    "MsgType"    => "image",
                    "Image"     => ["MediaId" => $imageid],
                ]
            ]; */
            $this->weObj->image($imageid)->reply();
            //exit(data_to_xml($data));
        }else{
            
            $user = $this->model->table('users')->field("user_id,user_name,nick_name,user_avatar")->where(array("openid"=>$openid))->find();
            if(!$user){
                /* $data = [
                    "xml"=>[
                        "FromUserName"    => "gh_285bec3abc62",
                        "ToUserName"    => $openid,
                        "CreateTime"    => time(),
                        "MsgType"    => "text",
                        "Content"    => "欢迎您的到来！您还未登录，点击蓝字-<a href='".__HOST__."/index.php?c=user&a=login'>立即登录</a>。",
                    ]
                ]; */
                $this->weObj->text("欢迎您的到来！您还未登录，点击蓝字<a href='".__HOST__."/index.php?c=user&a=register'>立即登录</a>")->reply();
            }else{
                $path = ROOT_PATH."data/attached/qrcode";
                if(!file_exists($path))mkdir($path,0777,true);
                if(!file_exists($path."/avatar_tmp/"))mkdir($path."/avatar_tmp/",0777,true);
                if(empty($user["user_avatar"])){

                 
                      $avatar = $this->download($userinfo["headimgurl"],$path."/avatar_tmp/".$user['user_id'].".jpg");
                    
                      $avatar = $this->changeCircularImg($avatar);
                     
                  }else{
                    // logResult("99999");
                    // logResult($user["user_avatar"]);
                    //   $avatar = $this->downloadImage($user["user_avatar"],$path."/avatar_tmp/");
                    //   logResult("991");
                    //   logResult($avatar);
                    //   $avatar = $this->changeCircularImg($avatar);
                    //   logResult("dsad");
                      $avatar = $this->download($user["user_avatar"],$path."/avatar_tmp/".$user['user_id'].".jpg");
                    
                      $avatar = $this->changeCircularImg($avatar);
                      
                  }
            
              
               
              
                $qrcode = $path."/".time().rand(1000,9999).".png";
           
                QRcode::png(__HOST__."/index.php?m=default&c=user&a=register&u={$user["user_id"]}",$qrcode,QR_ECLEVEL_Q);
                
                $poster = $path."/p".time().rand(1000,9999).".jpg";
                $image = [
                    [
                        "url"   => $avatar,
                        "left" =>320,
                        "top"=> 583,
                        "width" => 110,
                        "height" => 110,
                    ],
                    [
                        "url"   => $qrcode,
                        "left" => 238,
                        "top"=> 885,
                        "width" => 285,
                        "height" => 285,
                    ]
                    
                ];
                $image = [
                    [
                        "url"   => $avatar,
                        "left" =>320,
                        "top"=> 585,
                        "width" => 110,
                        "height" => 110,
                    ],
                    [
                        "url"   => $qrcode,
                        "left" => 238,
                        "top"=> 885,
                        "width" => 285,
                        "height" => 285,
                    ]
                    
                ];
                $text = [
                    [
                        "text"      => (empty($user["nick_name"])?$user["user_name"]:$user["nick_name"]),
                        "fontSize"  => 20,
                        "fontColor" => "255,255,255",
                        "left" =>330,
                        "top"=> 762,
                        "fontPath"  => $path."/ttf/SIMHEI.TTF",
                    ],

                ];

                $posterFilename = $this->createPoster(["image"=>$image,"text"=>$text,"background"=> $path."/bg/bg.jpg"],$poster);
                $res = $this->weObj->uploadMedia(["media"=>new CURLFile($posterFilename)],"image");
                if($res){
                    /* $data = [
                        "xml"=>[
                            "FromUserName"    => "gh_285bec3abc62",
                            "ToUserName"    => $openid,
                            "CreateTime"    => time(),
                            "MsgType"    => "image",
                            "Image"     => ["MediaId" => $res["media_id"]],
                        ]
                    ]; */
                    $this->weObj->image($res["media_id"])->reply();
                    self::$cache->setValue("poster".$openid, $res["media_id"], 3600*24*3);
                }else{
                    
                    $this->weObj->text("您还未注册为拓客商城用户！")->reply();
                }
                //exit(data_to_xml($data));
            }
            
            
        }
        
    }
    /**
     * 生成宣传海报
     * @param array  参数,包括图片和文字
     * @param string  $filename 生成海报文件名,不传此参数则不生成文件,直接输出图片
     * @return [type] [description]
     */
    private function createPoster($config=array(),$filename=""){
        //if(empty($filename)) header("content-type: image/png");
        $imageDefault = array(
            'left'=>0,
            'top'=>0,
            'right'=>0,
            'bottom'=>0,
            'width'=>100,
            'height'=>100,
            'opacity'=>100
        );
        $textDefault = array(
            'text'=>'',
            'left'=>0,
            'top'=>0,
            'fontSize'=>32,       //字号
            'fontColor'=>'255,255,255', //字体颜色
            'angle'=>0,
        );
        $background = $config['background'];//海报最底层得背景
        //背景方法
        $backgroundInfo = getimagesize($background);
        $backgroundFun = 'imagecreatefrom'.image_type_to_extension($backgroundInfo[2], false);
        $background = $backgroundFun($background);
        $backgroundWidth = imagesx($background);  //背景宽度
        $backgroundHeight = imagesy($background);  //背景高度
        $imageRes = imageCreatetruecolor($backgroundWidth,$backgroundHeight);
        $color = imagecolorallocate($imageRes, 0, 0, 0);
        imagefill($imageRes, 0, 0, $color);
        // imageColorTransparent($imageRes, $color);  //颜色透明
        imagecopyresampled($imageRes,$background,0,0,0,0,imagesx($background),imagesy($background),imagesx($background),imagesy($background));
        //处理了图片
        imagesavealpha($imageRes, true);
        
        if(!empty($config['image'])){
            foreach ($config['image'] as $key => $val) {
                $val = array_merge($imageDefault,$val);
                $info = getimagesize($val['url']);
                $function = 'imagecreatefrom'.image_type_to_extension($info[2], false);
                if($val['stream']){   //如果传的是字符串图像流
                    $info = getimagesizefromstring($val['url']);
                    $function = 'imagecreatefromstring';
                }
                $res = $function($val['url']);
                $resWidth = $info[0];
                $resHeight = $info[1];
                //建立画板 ，缩放图片至指定尺寸
                imagesavealpha($res,true);
                $canvas=imagecreatetruecolor($val['width'], $val['height']);
                //imagefill($canvas, 0, 0, $color);
                //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
                imagealphablending($canvas,false);
                imagesavealpha($canvas, true);
                imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'],$resWidth,$resHeight);
                $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']) - $val['width']:$val['left'];
                $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']) - $val['height']:$val['top'];
                //放置图像
                imagecopy($imageRes,$canvas, $val['left'],$val['top'],$val['right'],$val['bottom'],$val['width'],$val['height']);//左，上，右，下，宽度，高度，透明度
                unlink($val['url']);
            }
        }
        //处理文字
        if(!empty($config['text'])){
            foreach ($config['text'] as $key => $val) {
                $val = array_merge($textDefault,$val);
                list($R,$G,$B) = explode(',', $val['fontColor']);
                $fontColor = imagecolorallocate($imageRes, $R, $G, $B);
                $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']):$val['left'];
                $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']):$val['top'];
                $textContent = $str=mb_convert_encoding($val['text'], "html-entities", "utf-8"); ;
                imagettftext($imageRes,$val['fontSize'],$val['angle'],$val['left'],$val['top'],$fontColor,$val['fontPath'],$textContent);
            }
        }
        //生成图片
        if(!empty($filename)){
            $res = imagejpeg ($imageRes,$filename,90); //保存到本地
            imagedestroy($imageRes);
            if(!$res) return false;
            return $filename;
        }else{
            imagejpeg ($imageRes);     //在浏览器上显示
            imagedestroy($imageRes);
        }
    }
    
    /**
     *
     * @param string $imgpath
     * @return resource
     */
    private function changeCircularImg($imgpath) {
        $ext     = pathinfo($imgpath);
        $src_img = null;
        switch ($ext['extension']) {
            case 'jpeg':
            case 'jpg':
                $src_img = imagecreatefromjpeg($imgpath);
                break;
            case 'png':
                $src_img = imagecreatefrompng($imgpath);
                break;
        }
        
        $wh  = getimagesize($imgpath);
        $w   = $wh[0];
        $h   = $wh[1];
        $w   = min($w, $h);
        $h   = $w;
        $img = imagecreatetruecolor($w, $h);
        //这一句一定要有
        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $r   = $w / 2; //圆半径
        $y_x = $r; //圆心X坐标
        $y_y = $r; //圆心Y坐标
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($src_img, $x, $y);
                if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                    imagesetpixel($img, $x, $y, $rgbColor);
                }
            }
        }
        $newfilename = explode(".", $imgpath)[0].".png";
        imagepng($img,$newfilename);
        unlink($imgpath);
        return $newfilename;
    }

    public function download($url,$new_file){
    $header = array(   
     'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:45.0) Gecko/20100101 Firefox/45.0',    
     'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',    
     'Accept-Encoding: gzip, deflate',);
     // $url='http://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTKkGpNuUhaBniatRsiaG7ksqmhUWzkk40kTRS6icQS7kJcsfxcibQo7vDFcKibr7NHb9YIXiaXsEtLcdL6A/0';
     $curl = curl_init();
 
     curl_setopt($curl, CURLOPT_URL, $url);
     curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
     curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
     curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
     curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
     $data = curl_exec($curl);
     $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
 
     curl_close($curl);
     if ($code == 200) {//把URL格式的图片转成base64_encode格式的！    
        $imgBase64Code = "data:image/jpeg;base64," . base64_encode($data);
     }
     $img_content=$imgBase64Code;//图片内容
     //echo $img_content;exit;
     if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img_content, $result))
     { 
       $type = $result[2];//得到图片类型png?jpg?gif?  
       if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $img_content))))
       { return $new_file; }
      } 
    }
    public function downloadImage($url, $path='images/')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);
        return $this->saveAsImage($url, $file, $path);
    }
    
    private function saveAsImage($url, $file, $path)
    {
        $filename = pathinfo($url, PATHINFO_BASENAME);
        $resource = fopen($path . $filename, 'a');
        fwrite($resource, $file);
        fclose($resource);
        return $filename = $this->checkname($path.$filename);
        
    }
    
    private function checkname($filename){
        
        if(!strpos($filename, ".")){
            $finfo = finfo_open(FILEINFO_MIME); // 返回 mime 类型
            $filetype =  finfo_file($finfo, $filename);
            $filetype = explode(";", $filetype)[0];
            $filetype = explode("/", $filetype)[1];
            finfo_close($finfo);
            rename($filename, $filename.".".$filetype);
            $filename = $filename.".".$filetype;
        }
        return $filename;
    }
    
}

<?php

/**
 * ECSHOP 会员管理程序
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: users.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECTOUCH', true);

require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 用户帐号列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'accountloglist')
{
    pushWechatTemplate($userid,$code,$pushData,$url);
    //pushTemplate('', $data, $url,888);
    /* 检查权限 */
    admin_priv('users_manage');
    $sql = "SELECT rank_id, rank_name, min_points FROM ".$ecs->table('user_rank')." ORDER BY min_points ASC ";
    $rs = $db->query($sql);

    $ranks = array();
    while ($row = $db->FetchRow($rs))
    {
        $ranks[$row['rank_id']] = $row['rank_name'];
    }
    /*  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '0 未激活 1 正常  2 冻结 9终止',*/
    $status = array();
    $status[0] = '未激活';
    $status[1] = '正常';
    $status[2] = '冻结';
    $status[9] = '终止';
    $smarty->assign('user_ranks',   $ranks);
    $smarty->assign('status',   $status);
    $smarty->assign('ur_here',      $_LANG['03_users_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['04_users_add'], 'href'=>'users.php?act=add'));

    $accountlog_list = accountlog_list();
    
    $smarty->assign('accountlog_list',    $accountlog_list['accountlog_list']);
    $smarty->assign('filter',       $accountlog_list['filter']);
    $smarty->assign('record_count', $accountlog_list['record_count']);
    $smarty->assign('page_count',   $accountlog_list['page_count']);
    $smarty->assign('full_page',    1);
    $smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');

    assign_query_info();
    $smarty->display('accountlog_list.htm');
}
/*------------------------------------------------------ */
//-- ajax返回用户列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
   
    $accountlog_list = accountlog_list();

    $smarty->assign('accountlog_list',    $accountlog_list['accountlog_list']);
    $smarty->assign('filter',       $accountlog_list['filter']);
    $smarty->assign('record_count', $accountlog_list['record_count']);
    $smarty->assign('page_count',   $accountlog_list['page_count']);

    $sort_flag  = sort_flag($accountlog_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('accountlog_list.htm'), '', array('filter' => $accountlog_list['filter'], 'page_count' => $accountlog_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加会员帐号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    /* 检查权限 */
    admin_priv('users_manage');

    $user = array(  'rank_points'   => $_CFG['register_points'],
                    'pay_points'    => $_CFG['register_points'],
                    'sex'           => 0,
                    'credit_line'   => 0
                    );
    /* 取出注册扩展字段 */
    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 AND id != 6 ORDER BY dis_order, id';
    $extend_info_list = $db->getAll($sql);
    $smarty->assign('extend_info_list', $extend_info_list);

    $smarty->assign('ur_here',          $_LANG['04_users_add']);
    $smarty->assign('action_link',      array('text' => $_LANG['03_users_list'], 'href'=>'users.php?act=list'));
    $smarty->assign('form_action',      'insert');
    $smarty->assign('user',             $user);
    $smarty->assign('special_ranks',    get_rank_list(true));

    assign_query_info();
    $smarty->display('user_info.htm');
}
elseif ($_REQUEST['act'] == 'manualadd')
{
    /* 检查权限 */
    admin_priv('users_manage');

    $user = array(  'rank_points'   => $_CFG['register_points'],
                    'pay_points'    => $_CFG['register_points'],
                    'sex'           => 0,
                    'credit_line'   => 0
                    );
    /* 取出注册扩展字段 */
    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 AND id != 6 ORDER BY dis_order, id';
    $extend_info_list = $db->getAll($sql);
    $smarty->assign('extend_info_list', $extend_info_list);

    $smarty->assign('ur_here',          $_LANG['04_users_add']);
    $smarty->assign('action_link',      array('text' => $_LANG['03_users_list'], 'href'=>'users.php?act=list'));
    $smarty->assign('form_action',      'manualinsert');
    $smarty->assign('user',             $user);
    $smarty->assign('special_ranks',    get_rank_list(true));

    assign_query_info();
    $smarty->display('manual_user_info.htm');
}
elseif ($_REQUEST['act'] == 'manualinsert')
{
    /* 检查权限 */

    
    $vip_manage_account = $_POST['vip_manage_account'];

        $findaccount = array(
                                        
                                                 "account" =>$vip_manage_account
                                                
                                             );

    $sql0= "SELECT user_id,vip_manage_account,password,mobile_phone,parent_id,other_invite_code FROM " . $ecs->table('users') . " WHERE vip_manage_account = '$vip_manage_account'";
    $user_info = $db->GetRow($sql0);
    if(!$user_info){
        $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
        sys_msg(sprintf("帐号不存在", htmlspecialchars(stripslashes($_POST['vip_manage_account']))), 0, $link);
    }
    $ret0 = post_log($findaccount,API_URL."/api/user/join_info",5,$user_info['user_id']);

    if($ret0['data']){
            $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
            sys_msg(sprintf("vip中心已有帐号存在", htmlspecialchars(stripslashes($_POST['vip_manage_account']))), 0, $link);
    }else{
            $sql1= "SELECT user_id,vip_manage_account,password,mobile_phone,other_invite_code FROM " . $ecs->table('users') . " WHERE user_id = '{$user_info['parent_id']}'";

    $parent_info = $db->GetRow($sql1);

    $user_id = $user_info['user_id'];
    
    $sql2= "SELECT vip,order_sn,order_amount,total_pv,add_time,discount,order_amount,order_id FROM " . $ecs->table('order_info') . " WHERE user_id = '$user_id' and order_type=9";

    $order = $db->getAll($sql2);

    $sql3 = "SELECT goods_name,goods_number FROM " . $ecs->table('order_goods') . " WHERE order_id = '{$order[0]['order_id']}'";

    $cart_goods = $db->getAll($sql3);

    if($order['discount'] =='0.00'){
                                       
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_pv']."pv";
                                          
                                    }else{
                                         $remark = $cart_goods[0]['goods_name']."*".$cart_goods[0]['goods_number'].",$" .$order['order_amount'].",".$order['total_pv']."pv".",折扣$".$order['discount'];
                                    }


    $joinvipdata = array(
                                        
                                                 "account" =>$user_info['vip_manage_account'],
                                                "password" =>$user_info['password'],
                                                "phone" =>$user_info['mobile_phone'],
                                                "parent_account" =>$parent_info['vip_manage_account'],//上级VIP用户名
                                                // "nickname" =>$userinfo['nick_name'],
                                                "grade"=>$order[0]['vip'],
                                                "order_sn" =>$order[0]['order_sn'],
                                                "order_amount" =>$order[0]['order_amount'],
                                                "order_pv" =>$order[0]['total_pv'],
                                                "purse_amount" =>0,//抵扣金额
                                                "order_type" =>9,
                                                "type" =>1,
                                                "order_time" =>$order[0]['add_time'],
                                                "remark"  => $remark
                                             );

    $ret1 = post_log($joinvipdata,API_URL."/api/order/joinvip",5,$user_info['user_id']);




    /* 记录管理员操作 */


    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
    sys_msg(sprintf("补单成功", htmlspecialchars(stripslashes($_POST['vip_manage_account']))), 0, $link);
    }


}

/*------------------------------------------------------ */
//-- 添加会员帐号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert')
{
    /* 检查权限 */
    admin_priv('users_manage');
    $username = empty($_POST['username']) ? '' : trim($_POST['username']);
    $password = empty($_POST['password']) ? '' : trim($_POST['password']);
    $email = empty($_POST['email']) ? '' : trim($_POST['email']);
    $sex = empty($_POST['sex']) ? 0 : intval($_POST['sex']);
    $sex = in_array($sex, array(0, 1, 2)) ? $sex : 0;
    $birthday = $_POST['birthdayYear'] . '-' .  $_POST['birthdayMonth'] . '-' . $_POST['birthdayDay'];
    $rank = empty($_POST['user_rank']) ? 0 : intval($_POST['user_rank']);
    $credit_line = empty($_POST['credit_line']) ? 0 : floatval($_POST['credit_line']);
    $parent_username = empty($_POST['parent_username']) ? '' : trim($_POST['parent_username']);
    //推荐人

    $sql1= "SELECT user_id FROM " . $ecs->table('users') . " WHERE user_name = '$parent_username'";
    $parent_id = $db->getOne($sql1);
    if(!$parent_id){
        $parent_id = 0 ;
    }
    $users =& init_users();

    if (!$users->add_user($username, $password, $email))
    {
        /* 插入会员数据失败 */
        if ($users->error == ERR_INVALID_USERNAME)
        {
            $msg = $_LANG['username_invalid'];
        }
        elseif ($users->error == ERR_USERNAME_NOT_ALLOW)
        {
            $msg = $_LANG['username_not_allow'];
        }
        elseif ($users->error == ERR_USERNAME_EXISTS)
        {
            $msg = $_LANG['username_exists'];
        }
        elseif ($users->error == ERR_INVALID_EMAIL)
        {
            $msg = $_LANG['email_invalid'];
        }
        elseif ($users->error == ERR_EMAIL_NOT_ALLOW)
        {
            $msg = $_LANG['email_not_allow'];
        }
        // elseif ($users->error == ERR_EMAIL_EXISTS)
        // {
        //     $msg = $_LANG['email_exists'];
        // }
        else
        {
            //die('Error:'.$users->error_msg());
        }
        sys_msg($msg, 1);
    }

    /* 注册送积分 */
    if (!empty($GLOBALS['_CFG']['register_points']))
    {
        log_account_change($_SESSION['user_id'], 0, 0, $GLOBALS['_CFG']['register_points'], $GLOBALS['_CFG']['register_points'], $_LANG['register_points']);
    }

    /*把新注册用户的扩展信息插入数据库*/
    $sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有扩展字段的id
    $fields_arr = $db->getAll($sql);

    $extend_field_str = '';    //生成扩展字段的内容字符串
    $user_id_arr = $users->get_profile_by_name($username);
    foreach ($fields_arr AS $val)
    {
        $extend_field_index = 'extend_field' . $val['id'];
        if(!empty($_POST[$extend_field_index]))
        {
            $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
            $extend_field_str .= " ('" . $user_id_arr['user_id'] . "', '" . $val['id'] . "', '" . $temp_field_content . "'),";
        }
    }
    $extend_field_str = substr($extend_field_str, 0, -1);

    if ($extend_field_str)      //插入注册扩展数据
    {
        $sql = 'INSERT INTO '. $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
        $db->query($sql);
    }

    /* 更新会员的其它信息 */
    $other =  array();
    $other['credit_line'] = $credit_line;
    $other['user_rank']  = $rank;
    $other['sex']        = $sex;
    $other['birthday']   = $birthday;
    $other['reg_time'] = time();
    $other['parent_id'] = $parent_id;
    $other['msn'] = isset($_POST['extend_field1']) ? htmlspecialchars(trim($_POST['extend_field1'])) : '';
    $other['qq'] = isset($_POST['extend_field2']) ? htmlspecialchars(trim($_POST['extend_field2'])) : '';
    $other['office_phone'] = isset($_POST['extend_field3']) ? htmlspecialchars(trim($_POST['extend_field3'])) : '';
    $other['home_phone'] = isset($_POST['extend_field4']) ? htmlspecialchars(trim($_POST['extend_field4'])) : '';
    $other['mobile_phone'] = isset($_POST['extend_field5']) ? htmlspecialchars(trim($_POST['extend_field5'])) : '';

    $db->autoExecute($ecs->table('users'), $other, 'UPDATE', "user_name = '$username'");

    /* 记录管理员操作 */
    admin_log($_POST['username'], 'add', 'users');

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
    sys_msg(sprintf($_LANG['add_success'], htmlspecialchars(stripslashes($_POST['username']))), 0, $link);

}

/*------------------------------------------------------ */
//-- 编辑用户帐号
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit')
{
    /* 检查权限 */
    admin_priv('users_manage');

    $sql = "SELECT u.user_name,u.resource, u.sex, u.vip_manage_account,u.ID_card,u.birthday, u.rank_points, u.user_rank ,u.user_vip , u.credit_line, u.parent_id, u2.user_name as parent_username, u.qq, u.msn, u.office_phone, u.home_phone, u.mobile_phone".
        " FROM " .$ecs->table('users'). " u LEFT JOIN " . $ecs->table('users') . " u2 ON u.parent_id = u2.user_id WHERE u.user_id='$_GET[id]'";

    $row = $db->GetRow($sql);
    $row['user_name'] = addslashes($row['user_name']);
    $users  =& init_users();
    $user   = $users->get_user_info($row['user_name']);

    $sql = "SELECT u.user_id,u.resource, u.sex,u.level_xp,u.status, u.vip_manage_account,u.birthday,u.ID_card,  u.rank_points, u.user_rank ,  u.user_vip , u.credit_line, u.parent_id, u2.vip_manage_account as parent_username, u.qq, u.msn,
    u.office_phone, u.home_phone, u.mobile_phone".
        " FROM " .$ecs->table('users'). " u LEFT JOIN " . $ecs->table('users') . " u2 ON u.parent_id = u2.user_id WHERE u.user_id='$_GET[id]'";

    $row = $db->GetRow($sql);

    if ($row)
    {
        $user['user_id']        = $row['user_id'];
        $user['sex']            = $row['sex'];
        $user['birthday']       = date($row['birthday']);
        $user['ID_card']        = $row['ID_card'];
        $user['user_rank']      = $row['user_rank'];
        $user['balance'] =  finduseraccountinfo($row['user_id'],"balance");
        $user['frozen_money'] =  finduseraccountinfo($row['user_id'],"frozen_money");
        $user['rank_points'] =  finduseraccountinfo($row['user_id'],"rank_points");
        $user['pay_points'] =  finduseraccountinfo($row['user_id'],"pay_points");
        $user['credit_line']    = $row['credit_line'];
        $user['formated_user_money'] = price_format($row['user_money']);
        $user['formated_frozen_money'] = price_format($row['frozen_money']);
        $user['parent_id']      = $row['parent_id'];
        $user['parent_username']= $row['parent_username'];
        $user['qq']             = $row['qq'];
        $user['msn']            = $row['msn'];
        $user['office_phone']   = $row['office_phone'];
        $user['home_phone']     = $row['home_phone'];
        $user['mobile_phone']   = $row['mobile_phone'];
        $user['user_vip']   = $row['user_vip'];
        $user['status']   = $row['status'];
        $user['resource']   = $row['resource'];
        $user['level_xp']   = $row['level_xp'];
    
    }
    else
    {
          $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
          sys_msg($_LANG['username_invalid'], 0, $links);
//        $user['sex']            = 0;
//        $user['pay_points']     = 0;
//        $user['rank_points']    = 0;
//        $user['user_money']     = 0;
//        $user['frozen_money']   = 0;
//        $user['credit_line']    = 0;
//        $user['formated_user_money'] = price_format(0);
//        $user['formated_frozen_money'] = price_format(0);
     }
   
    /* 取出注册扩展字段 */
    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 AND id != 6 ORDER BY dis_order, id';
    $extend_info_list = $db->getAll($sql);

    $sql = 'SELECT reg_field_id, content ' .
           'FROM ' . $ecs->table('reg_extend_info') .
           " WHERE user_id = $user[user_id]";
    $extend_info_arr = $db->getAll($sql);

    $temp_arr = array();
    foreach ($extend_info_arr AS $val)
    {
        $temp_arr[$val['reg_field_id']] = $val['content'];
    }

    foreach ($extend_info_list AS $key => $val)
    {
        switch ($val['id'])
        {
            case 1:     $extend_info_list[$key]['content'] = $user['msn']; break;
            case 2:     $extend_info_list[$key]['content'] = $user['qq']; break;
            case 3:     $extend_info_list[$key]['content'] = $user['office_phone']; break;
            case 4:     $extend_info_list[$key]['content'] = $user['home_phone']; break;
            case 5:     $extend_info_list[$key]['content'] = $user['mobile_phone']; break;
            default:    $extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']] ;
        }
    }

    $smarty->assign('extend_info_list', $extend_info_list);

    /* 当前会员推荐信息 */
    $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
    $smarty->assign('affiliate', $affiliate);

    empty($affiliate) && $affiliate = array();

    if(empty($affiliate['config']['separate_by']))
    {
        //推荐注册分成
        $affdb = array();
        $num = count($affiliate['item']);
        $up_uid = "'$_GET[id]'";
        for ($i = 1 ; $i <=$num ;$i++)
        {
            $count = 0;
            if ($up_uid)
            {
                $sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE parent_id IN($up_uid)";
                $query = $db->query($sql);
                $up_uid = '';
                while ($rt = $db->fetch_array($query))
                {
                    $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                    $count++;
                }
            }
            $affdb[$i]['num'] = $count;
        }
        if ($affdb[1]['num'] > 0)
        {
            $smarty->assign('affdb', $affdb);
        }
    }

    if($user['user_vip']&&$user['resource']==1){
       
        $userdata = array(
                                                
                                                "account" => $user['vip_manage_account'],

                                             );

        $postdata = post_log($userdata,API_URL."/api/user/info/detail",5,$user['user_id']);
        // $user['parent_username'] = $postdata['data']['recommender'];
        $smarty->assign('esettlement',          $postdata['data']['esettlement']);
        $smarty->assign('recommender',$postdata['data']['recommender']);
        $smarty->assign('esettlementArea',       isset($postdata['data']['area'])?$postdata['data']['area']:'');
    }
                          
    assign_query_info();
    $smarty->assign('ur_here',          $_LANG['users_edit']);
    $smarty->assign('action_link',      array('text' => $_LANG['03_users_list'], 'href'=>'users.php?act=list&' . list_link_postfix()));
    $smarty->assign('user',             $user);
    $smarty->assign('form_action',      'update');
    $smarty->assign('special_ranks',    get_rank_list(true));
    $smarty->assign('get_user_vip_list',    get_user_vip_list());
    $smarty->assign('get_status_list',    get_newstatus_list());

    $smarty->display('user_info.htm');
}

/*------------------------------------------------------ */
//-- 更新用户帐号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'updateparent')
{
    /* 检查权限 */

    admin_priv('users_manage');
    
    $username = empty($_POST['username']) ? '' : trim($_POST['username']);
    $vip_manage_account = empty($_POST['parent_username']) ? '' : trim($_POST['parent_username']);
    $old_vip_manage_account = empty($_POST['oldparent_username']) ? '' : trim($_POST['oldparent_username']);
    $esettlement = empty($_POST['esettlement']) ? '' : trim($_POST['esettlement']); 
    $users  =& init_users();
    if(!empty($vip_manage_account)){
        $sql1= "SELECT user_id FROM " . $ecs->table('users') . " WHERE vip_manage_account = '$vip_manage_account'";
    
        $parent_id = $db->getOne($sql1);

        if(!$parent_id&&empty($vip_manage_account)){
            sys_msg("推荐人账号错误", 1);
        }

        
    }
    if(!$parent_id){
            $parent_id = 0 ;
        }

    $sql22= "SELECT resource FROM " . $ecs->table('users') . " WHERE vip_manage_account = '$vip_manage_account'";
    $parent_resource = $db->getOne($sql22);
    $sql221= "SELECT resource FROM " . $ecs->table('users') . " WHERE vip_manage_account = '$old_vip_manage_account'";
    $old_parent_resource = $db->getOne($sql221);
    $res =  $users->get_profile_by_name($username);
    
     /*如果是普通用户更改推荐人*/
      $modifyparentusername = 0;
      $modifysettlement = 0;
    if(!$res['user_vip']&&($_POST['oldparent_username']!=$_POST['parent_username'])){

         $sqll="UPDATE ".$ecs->table('users'). "SET `parent_id`='".$parent_id."',`resource` = '".$parent_resource."'  WHERE user_name= '".$username."'";
       
                  $db->query($sqll);
                $modifyparentusername =1;

                        updateuser_reposition($res['user_id'],$parent_id);

                        updateAllChildReposition($res['user_id'],'',1);
                 
                  
    }
    $db->query("START TRANSACTION");
    if($res['user_vip']){
        if($old_parent_resource==1&&$parent_resource==1){
            //由1变成2
             $deletedata = array(
                                                
                                                 "account" => $vip_manage_account
                                           
                                              );
           
                $delzhixiao = post_log($deletedata,API_URL."/api/user/delMember",5,$user['user_id']);
                var_dump($delzhixiao);exit;
                if($delzhixiao){
                    /*变更resource*/
                    $sqll="UPDATE ".$ecs->table('users'). "SET `parent_id`='".$parent_id."',`resource` = '".$parent_resource."'  WHERE user_name= '".$username."'";
       
                  $db->query($sqll);
                     $modifyparentusername =1;

                        updateuser_reposition($res['user_id'],$parent_id);

                        updateAllChildReposition($res['user_id'],'',1);
                }else{
                      $db->query("ROLLBACK");
                       sys_msg("删除全球go会员出错", 1);
                }

        }
        if($old_parent_resource==2&&$parent_resource==1){
            //由2变成1

        }
    }

    if($res['user_vip']&&($_POST['oldparent_username']!=$_POST['parent_username'])&&($_POST['oldesettlement']!=$_POST['esettlement'])){
      
        /*都有填的情况*/
                $udpatedata = array(
                                                
                                                 "account" => $res['vip_manage_account'],
                                                 /*其实是推荐人的vip_manage_account账号*/
                                                 "recommender"=>$_POST['parent_username'],
                                                 "esettlement"=>$_POST['esettlement'],
                                                 "esettlementArea"=>$_POST['esettlementArea']
                                              );
                $res0 = post_log($udpatedata,API_URL."/api/user/update",5,$user['user_id']);

                if($res0['status']!=200){
                   $db->query("ROLLBACK");
                  sys_msg($res0['msg']."1", 1);
                 }else{
                    $modifyparentusername = 1;
                    $modifysettlement = 1;
                    $sql0="UPDATE ".$ecs->table('users'). "SET `parent_id`='".$parent_id."' WHERE user_name= '".$username."'";
                  $r0 = $db->query($sql0);
                 

                  updateuser_reposition($res['user_id'],$parent_id);
                  updateAllChildReposition($res['user_id'],'',1);
                  $db->query("COMMIT");
                 }
                  
    }elseif($res['user_vip']&&($_POST['oldparent_username']!=$_POST['parent_username'])){
     
        

               $udpatedata = array(
                                                
                                                 "account" => $res['vip_manage_account'],
                                                 "recommender"=>$_POST['parent_username']

                                              );
        if($res['resource']==1){
            $res0 = post_log($udpatedata,API_URL."/api/user/update",5,$user['user_id']);
        }else{
            $res0["status"]=200;
        }
        if($res0['status']!=200){
               $db->query("ROLLBACK");
          
              sys_msg($res0['msg']."311", 1);
               $db->query("ROLLBACK");
        }else{
            $modifyparentusername = 1;
            $sql1="UPDATE ".$ecs->table('users'). "SET `parent_id`='".$parent_id."' WHERE user_name= '".$username."'";
            $r0 = $db->query($sql1);
        
        updateuser_reposition($res['user_id'],$parent_id);
        updateAllChildReposition($res['user_id'],'',1);
          $db->query("COMMIT");
        }

        /*获取上一级用户的user_id*/
        
     
    }elseif(!empty($_POST['oldesettlement'])&&$res['user_vip']){
       
           if($res['user_vip']&&($_POST['oldesettlement']!=$_POST['esettlement'])){
               $udpatedata = array(
                                                
                                                 "account" => $res['vip_manage_account'],
                                                 "esettlement"=>$_POST['esettlement'],
                                                 "esettlementArea"=>$_POST['esettlementArea']

                                              );
        
        $res1 = post_log($udpatedata,API_URL."/api/user/update",5,$res['user_id']);

            if($res1['status']!=200){
                 $db->query("ROLLBACK");
              sys_msg($res1['msg']."3", 1);
        }else{
                $modifysettlement = 1;
                $sql2="UPDATE ".$ecs->table('users'). "SET `parent_id`='".$parent_id."' WHERE user_name= '".$username."'";
                $r0 = $db->query($sql2);
                $db->query("COMMIT");
        }
                
         }
    }



      if($modifyparentusername){
     
         admin_log($username, 'edit', "更改".$username."的推荐人从".$_POST['oldparent_username']."变为".$_POST['parent_username'],"more");
      }
        if($modifysettlement){
       
         admin_log($username, 'edit', "更改".$username."的安置人从".$_POST['oldesettlement']."变为".$_POST['esettlement'],"more");
        }

      $db->query("COMMIT");
    /*更改上下级*/
       
    /**/
    /* 记录管理员操作 */
    

    /* 提示信息 */
    $links[0]['text']    = $_LANG['goto_list'];
    $links[0]['href']    = 'users.php?act=list&' . list_link_postfix();
    $links[1]['text']    = $_LANG['go_back'];
    $links[1]['href']    = 'javascript:history.back()';

    sys_msg($_LANG['update_success'], 0, $links);

}
elseif ($_REQUEST['act'] == 'update')
{
    /* 检查权限 */

    admin_priv('users_manage');
    $username = empty($_POST['username']) ? '' : trim($_POST['username']);
    $password = empty($_POST['password']) ? '' : trim($_POST['password']);

    $email = empty($_POST['email']) ? '' : trim($_POST['email']);
    $sex = empty($_POST['sex']) ? 0 : intval($_POST['sex']);
    $sex = in_array($sex, array(0, 1, 2)) ? $sex : 0;
    $birthday = $_POST['birthdayYear'] . '-' .  $_POST['birthdayMonth'] . '-' . $_POST['birthdayDay'];
    $rank = empty($_POST['user_rank']) ? 0 : intval($_POST['user_rank']);
    $credit_line = empty($_POST['credit_line']) ? 0 : floatval($_POST['credit_line']);
    $vip_manage_account = empty($_POST['parent_username']) ? '' : trim($_POST['parent_username']);
    $status = empty($_POST['status']) ? 0 : floatval($_POST['status']);
    $level_xp = empty($_POST['level_xp']) ? 0 : floatval($_POST['level_xp']);
    $user_vip = empty($_POST['user_vip']) ? 0 : floatval($_POST['user_vip']);
    //推荐人
    $mobile_phone = empty($_POST['mobile_phone']) ? '' : trim($_POST['mobile_phone']);
    if(!empty($vip_manage_account)){
        $sql1= "SELECT user_id FROM " . $ecs->table('users') . " WHERE vip_manage_account = '$vip_manage_account'";
    
        $parent_id = $db->getOne($sql1);

        if(!$parent_id&&empty($vip_manage_account)){
            sys_msg("推荐人账号错误", 1);
        }

        
    }

    if(!$parent_id){
            $parent_id = 0 ;
        }

    $sql22= "SELECT resource FROM " . $ecs->table('users') . " WHERE vip_manage_account = '$vip_manage_account'";
    $parent_resource = $db->getOne($sql22);

    $users  =& init_users();
    
    //更改数据前
    $beforeChange = $db->getRow("select * from {$ecs->table('users')} where user_name = '{$username}'");
    
    /*如果是vip用户并且推荐人和安置人有发生变化*/
    // if(($_POST['oldparent_username']!=$_POST['parent_username'])){

    //     $udpatedata = array(
                                                
    //                                             "account" => $user['vip_manage_account'],
    //                                             "recommender"=>$_POST['parent_username']

    //                                          );
    //     post_log($udpatedata,API_URL."/api/user/update",5,$user['user_id']);

    // }
    $res =  $users->get_profile_by_name($username);
     /* 更新会员的其它信息 */
    $other =  array();
    $other['credit_line'] = $credit_line;
    // $other['user_rank'] = $rank;
    $other['parent_id'] = $parent_id;
    $other['msn'] = isset($_POST['extend_field1']) ? htmlspecialchars(trim($_POST['extend_field1'])) : '';
    $other['qq'] = isset($_POST['extend_field2']) ? htmlspecialchars(trim($_POST['extend_field2'])) : '';
    $other['office_phone'] = isset($_POST['extend_field3']) ? htmlspecialchars(trim($_POST['extend_field3'])) : '';
    $other['home_phone'] = isset($_POST['extend_field4']) ? htmlspecialchars(trim($_POST['extend_field4'])) : '';
    $other['mobile_phone'] = isset($_POST['mobile_phone']) ? htmlspecialchars(trim($_POST['mobile_phone'])) : '';
   if(!$res['user_vip']&&$user_vip>0){
                sys_msg("非店主不能修改成店主", 1);
   }
   if($res['user_vip']>0&&!$user_vip){
                sys_msg("不能修改成普通身份", 1);
   }
   if($vip_manage_account){
        $updateother="UPDATE ".$ecs->table('users'). "SET `credit_line`='".$credit_line."',`parent_id`='".$parent_id."',`status`='".$status."',`level_xp`='".$level_xp."',`user_vip`='".$user_vip."',`msn`='".$other['msn']."',`office_phone`='".$other['office_phone']."',`home_phone`='".$other['home_phone']."',`mobile_phone`='".$other['mobile_phone']."' WHERE user_name= '".$username."'";
   }else{
        $updateother="UPDATE ".$ecs->table('users'). "SET `credit_line`='".$credit_line."',`status`='".$status."',`level_xp`='".$level_xp."',`user_vip`='".$user_vip."',`msn`='".$other['msn']."',`office_phone`='".$other['office_phone']."',`home_phone`='".$other['home_phone']."',`mobile_phone`='".$other['mobile_phone']."' WHERE user_name= '".$username."'";
   }
     

     $r2 = $db->query($updateother);

    //$r2 = $db->autoExecute($ecs->table('users'), $other, 'UPDATE', "user_name = '$username'");
    //var_dump($r2);exit;
     /*如果是普通用户更改推荐人*/
    if(!$res['user_vip']&&($_POST['oldparent_username']!=$_POST['parent_username'])){

         $sqll="UPDATE ".$ecs->table('users'). "SET `parent_id`='".$parent_id."',`resource` = '".$parent_resource."'  WHERE user_name= '".$username."'";
       
                  $db->query($sqll);
                

                        updateuser_reposition($res['user_id'],$parent_id);

                        updateAllChildReposition($res['user_id'],'',1);
                 
                  
    }

    $db->query("START TRANSACTION");


    
    
    if (!$users->edit_user(array('username'=>$username, 'password'=>$password, 'email'=>$email, 'gender'=>$sex, 'bday'=>$birthday), 1))
    {
       
        if ($users->error == ERR_EMAIL_EXISTS)
        {
            $msg = $_LANG['email_exists'];
        }
        else
        {
            $msg = $_LANG['edit_user_failed'];
        }
   
        $db->query("ROLLBACK");

        sys_msg($msg, 1);

    }
    if(!empty($password))
    {
            $sql="UPDATE ".$ecs->table('users'). "SET `ec_salt`='0' WHERE user_name= '".$username."'";
            $r0 = $db->query($sql);
            if(!$r0){
           
                $db->query("ROLLBACK");
            }
    }
    /* 更新用户扩展字段的数据 */
    $sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有扩展字段的id
    $fields_arr = $db->getAll($sql);
    $user_id_arr = $users->get_profile_by_name($username);
    $user_id = $user_id_arr['user_id'];

    foreach ($fields_arr AS $val)       //循环更新扩展用户信息
    {
        $extend_field_index = 'extend_field' . $val['id'];
        if(isset($_POST[$extend_field_index]))
        {
            $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];

            $sql = 'SELECT * FROM ' . $ecs->table('reg_extend_info') . "  WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
            if ($db->getOne($sql))      //如果之前没有记录，则插入
            {
                $sql = 'UPDATE ' . $ecs->table('reg_extend_info') . " SET content = '$temp_field_content' WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
            }
            else
            {
                $sql = 'INSERT INTO '. $ecs->table('reg_extend_info') . " (`user_id`, `reg_field_id`, `content`) VALUES ('$user_id', '$val[id]', '$temp_field_content')";
            }
            $r1 = $db->query($sql);
            if(!$r1){
               
                $db->query("ROLLBACK");
            }
        }
    }




    // if(($_POST['oldparent_username']!=$_POST['parent_username'])&&($_POST['oldesettlement']!=$_POST['esettlement'])){
      
    //     /*都有填的情况*/
    //             $udpatedata = array(
                                                
    //                                              "account" => $res['vip_manage_account'],
    //                                              /*其实是推荐人的vip_manage_account账号*/
    //                                              "recommender"=>$_POST['parent_username'],
    //                                              "esettlement"=>$_POST['esettlement'],
    //                                              "esettlementArea"=>$_POST['esettlementArea']
    //                                           );
    //             $res0 = post_log($udpatedata,API_URL."/api/user/update",5,$user['user_id']);

    //             if($res0['status']!=200){
    //                $db->query("ROLLBACK");
    //               sys_msg($res0['msg']."1", 1);
    //              }
    //               $sql0="UPDATE ".$ecs->table('users'). "SET `parent_id`='".$parent_id."' WHERE user_name= '".$username."'";
    //               $r0 = $db->query($sql0);
                  
    //               updateuser_reposition($res['user_id'],$parent_id);
    //               updateAllChildReposition($res['user_id'],'',1);
    //               $db->query("COMMIT");
    // }elseif($res['user_vip']&&($_POST['oldparent_username']!=$_POST['parent_username'])){
     
    //     if($res['user_vip']&&($_POST['oldparent_username']!=$_POST['parent_username'])){

    //            $udpatedata = array(
                                                
    //                                              "account" => $res['vip_manage_account'],
    //                                              "recommender"=>$_POST['parent_username']

    //                                           );
    //     if($res['resource']==1){
    //         $res0 = post_log($udpatedata,API_URL."/api/user/update",5,$user['user_id']);
    //     }else{
    //         $res0["status"]=200;
    //     }
    //     if($res0['status']!=200){
    //            $db->query("ROLLBACK");
          
    //           sys_msg($res0['msg']."311", 1);
    //            $db->query("ROLLBACK");
    //     }

    //     /*获取上一级用户的user_id*/
    //     $sql1="UPDATE ".$ecs->table('users'). "SET `parent_id`='".$parent_id."' WHERE user_name= '".$username."'";
    //     $r0 = $db->query($sql1);
        
    //     updateuser_reposition($res['user_id'],$parent_id);
    //     updateAllChildReposition($res['user_id'],'',1);
    //       $db->query("COMMIT");
    //  }
    // }elseif(!empty($_POST['oldesettlement'])){
       
    //        if($res['user_vip']&&($_POST['oldesettlement']!=$_POST['esettlement'])){
    //            $udpatedata = array(
                                                
    //                                              "account" => $res['vip_manage_account'],
    //                                              "esettlement"=>$_POST['esettlement'],
    //                                              "esettlementArea"=>$_POST['esettlementArea']

    //                                           );
        

    //     $res1 = post_log($udpatedata,API_URL."/api/user/update",5,$res['user_id']);

    //         if($res1['status']!=200){
    //              $db->query("ROLLBACK");
    //           sys_msg($res1['msg']."3", 1);
    //     }
    //             $sql2="UPDATE ".$ecs->table('users'). "SET `parent_id`='".$parent_id."' WHERE user_name= '".$username."'";
    //     $r0 = $db->query($sql2);
    //       $db->query("COMMIT");
    //      }
    // }

  
        $db->query("COMMIT");
    /*更改上下级*/
    $afterChange = $db->getRow("select * from {$ecs->table('users')} where user_name = '{$username}'");
    
    /**/
    /* 记录管理员操作 */
    admin_log($username, 'edit', getUserChanges($beforeChange, $afterChange),"more");

    /* 提示信息 */
    $links[0]['text']    = $_LANG['goto_list'];
    $links[0]['href']    = 'users.php?act=list&' . list_link_postfix();
    $links[1]['text']    = $_LANG['go_back'];
    $links[1]['href']    = 'javascript:history.back()';

    sys_msg($_LANG['update_success'], 0, $links);

}

/*------------------------------------------------------ */
//-- 批量删除会员帐号
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'batch_remove')
{
    /* 检查权限 */
    admin_priv('users_drop');

    if (isset($_POST['checkboxes']))
    {
        $sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id " . db_create_in($_POST['checkboxes']);
        $col = $db->getCol($sql);
        $usernames = implode(',',addslashes_deep($col));
        $count = count($col);
        /* 通过插件来删除用户 */
        $users =& init_users();
        $users->remove_user($col);

        admin_log($usernames, 'batch_remove', 'users');

        $lnk[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
        sys_msg(sprintf($_LANG['batch_remove_success'], $count), 0, $lnk);
    }
    else
    {
        $lnk[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
        sys_msg($_LANG['no_select_user'], 0, $lnk);
    }
}

/* 编辑用户名 */
elseif ($_REQUEST['act'] == 'edit_username')
{
    /* 检查权限 */
    check_authz_json('users_manage');

    $username = empty($_REQUEST['val']) ? '' : json_str_iconv(trim($_REQUEST['val']));
    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

    if ($id == 0)
    {
        make_json_error('NO USER ID');
        return;
    }

    if ($username == '')
    {
        make_json_error($GLOBALS['_LANG']['username_empty']);
        return;
    }

    $users =& init_users();

    if ($users->edit_user($id, $username))
    {
        if ($_CFG['integrate_code'] != 'ecshop')
        {
            /* 更新商城会员表 */
            $db->query('UPDATE ' .$ecs->table('users'). " SET user_name = '$username' WHERE user_id = '$id'");
        }

        admin_log(addslashes($username), 'edit', 'users');
        make_json_result(stripcslashes($username));
    }
    else
    {
        $msg = ($users->error == ERR_USERNAME_EXISTS) ? $GLOBALS['_LANG']['username_exists'] : $GLOBALS['_LANG']['edit_user_failed'];
        make_json_error($msg);
    }
}

/*------------------------------------------------------ */
//-- 编辑email
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_email')
{
    /* 检查权限 */
    check_authz_json('users_manage');

    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
    $email = empty($_REQUEST['val']) ? '' : json_str_iconv(trim($_REQUEST['val']));

    $users =& init_users();

    $sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '$id'";
    $username = $db->getOne($sql);


    if (is_email($email))
    {
        if ($users->edit_user(array('username'=>$username, 'email'=>$email)))
        {
            admin_log(addslashes($username), 'edit', 'users');

            make_json_result(stripcslashes($email));
        }
        else
        {
            $msg = ($users->error == ERR_EMAIL_EXISTS) ? $GLOBALS['_LANG']['email_exists'] : $GLOBALS['_LANG']['edit_user_failed'];
            make_json_error($msg);
        }
    }
    else
    {
        make_json_error($GLOBALS['_LANG']['invalid_email']);
    }
}

/*------------------------------------------------------ */
//-- 删除会员帐号
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'remove')
{
    /* 检查权限 */
    admin_priv('users_drop');

    $sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '" . $_GET['id'] . "'";
    $username = $db->getOne($sql);
    $sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE parent_id = '" . $_GET['id'] . "'";
    $isChild = $db->getOne($sql);
    if(!empty($isChild)){
        $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
        sys_msg("该会员存在下级  无法删除", 0, $link);
        return;
    }
    /* 通过插件来删除用户 */
    $users =& init_users();
    // $deletedata = array(
                       
    //                     "id" =>$_GET['id'],
                      
                  
                       
                        
                     
    //                     "token" =>encryptapitoken()
    //                 );
     
    // $ret = post_log($deletedata,API_URL."/api/user/delMember");
  
    if($r["status"]==422){
        /*shibai*/
         $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
        sys_msg(sprintf($_LANG['remove_fail'], $username), 0, $link);
    }
    $users->remove_user($username); //已经删除用户所有数据

    /* 记录管理员操作 */
    admin_log(addslashes($username), 'remove', 'users');

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
    sys_msg(sprintf($_LANG['remove_success'], $username), 0, $link);
}

/*------------------------------------------------------ */
//--  收货地址查看
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'address_list')
{
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $sql = "SELECT a.*, c.region_name AS country_name, p.region_name AS province, ct.region_name AS city_name, d.region_name AS district_name ".
           " FROM " .$ecs->table('user_address'). " as a ".
           " LEFT JOIN " . $ecs->table('region') . " AS c ON c.region_id = a.country " .
           " LEFT JOIN " . $ecs->table('region') . " AS p ON p.region_id = a.province " .
           " LEFT JOIN " . $ecs->table('region') . " AS ct ON ct.region_id = a.city " .
           " LEFT JOIN " . $ecs->table('region') . " AS d ON d.region_id = a.district " .
           " WHERE user_id='$id'";
    $address = $db->getAll($sql);
    $smarty->assign('address',          $address);
    assign_query_info();
    $smarty->assign('ur_here',          $_LANG['address_list']);
    $smarty->assign('action_link',      array('text' => $_LANG['03_users_list'], 'href'=>'users.php?act=list&' . list_link_postfix()));
    $smarty->display('user_address_list.htm');
}

/*------------------------------------------------------ */
//-- 脱离推荐关系
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'remove_parent')
{
    /* 检查权限 */
    admin_priv('users_manage');

    $sql = "UPDATE " . $ecs->table('users') . " SET parent_id = 0 WHERE user_id = '" . $_GET['id'] . "'";
    $db->query($sql);
    
    /* 记录管理员操作 */
    $sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '" . $_GET['id'] . "'";
    $username = $db->getOne($sql);
    admin_log(addslashes($username), 'edit', 'users');

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
    sys_msg(sprintf($_LANG['update_success'], $username), 0, $link);
}

/*------------------------------------------------------ */
//-- 查看用户推荐会员列表
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'aff_list')
{
    /* 检查权限 */
    admin_priv('users_manage');
    $smarty->assign('ur_here',      $_LANG['03_users_list']);

    $auid = $_GET['auid'];
    $user_list['user_list'] = array();

    $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
    $smarty->assign('affiliate', $affiliate);

    empty($affiliate) && $affiliate = array();

    $num = count($affiliate['item']);
    $up_uid = "'$auid'";
    $all_count = 0;
    for ($i = 1; $i<=$num; $i++)
    {
        $count = 0;
        if ($up_uid)
        {
            $sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE parent_id IN($up_uid)";
            $query = $db->query($sql);
            $up_uid = '';
            while ($rt = $db->fetch_array($query))
            {
                $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                $count++;
            }
        }
        $all_count += $count;

        if ($count)
        {
            $sql = "SELECT user_id, user_name, '$i' AS level, email, is_validated,  reg_time ".
                    " FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id IN($up_uid)" .
                    " ORDER by level, user_id";
            $user_list['user_list'] = array_merge($user_list['user_list'], $db->getAll($sql));
        }
    }

    $temp_count = count($user_list['user_list']);
    for ($i=0; $i<$temp_count; $i++)
    {
        $user_list['user_list'][$i]['reg_time'] = local_date($_CFG['date_format'], $user_list['user_list'][$i]['reg_time']);
        $user_list['user_list'][$i]['balance'] =  finduseraccountinfo($user_list['user_list'][$i]['user_id'],"balance");
        $user_list['user_list'][$i]['frozen_money'] = finduseraccountinfo($user_list['user_list'][$i]['user_id'],"frozen_money");
        $user_list['user_list'][$i]['rank_points'] = finduseraccountinfo($user_list['user_list'][$i]['user_id'],"rank_points");
        $user_list['user_list'][$i]['pay_points'] = finduseraccountinfo($user_list['user_list'][$i]['user_id'],"pay_points");
    }

    $user_list['record_count'] = $all_count;

    $smarty->assign('user_list',    $user_list['user_list']);
    $smarty->assign('record_count', $user_list['record_count']);
    $smarty->assign('full_page',    1);
    $smarty->assign('action_link',  array('text' => $_LANG['back_note'], 'href'=>"users.php?act=edit&id=$auid"));

    assign_query_info();
    $smarty->display('affiliate_list.htm');
}
elseif ($_REQUEST['act'] == 'editparent')
{
    /* 检查权限 */
    admin_priv('users_manage');

    $sql = "SELECT u.user_name,u.resource, u.sex, u.vip_manage_account,u.ID_card,u.birthday, u.rank_points, u.user_rank ,u.user_vip , u.credit_line, u.parent_id, u2.user_name as parent_username, u.qq, u.msn, u.office_phone, u.home_phone, u.mobile_phone".
        " FROM " .$ecs->table('users'). " u LEFT JOIN " . $ecs->table('users') . " u2 ON u.parent_id = u2.user_id WHERE u.user_id='$_GET[id]'";

    $row = $db->GetRow($sql);
    $row['user_name'] = addslashes($row['user_name']);
    $users  =& init_users();
    $user   = $users->get_user_info($row['user_name']);

    $sql = "SELECT u.user_id,u.resource, u.sex,u.level_xp,u.status, u.vip_manage_account,u.birthday,u.ID_card,  u.rank_points, u.user_rank ,  u.user_vip , u.credit_line, u.parent_id, u2.vip_manage_account as parent_username, u.qq, u.msn,
    u.office_phone, u.home_phone, u.mobile_phone".
        " FROM " .$ecs->table('users'). " u LEFT JOIN " . $ecs->table('users') . " u2 ON u.parent_id = u2.user_id WHERE u.user_id='$_GET[id]'";

    $row = $db->GetRow($sql);

    if ($row)
    {
        $user['user_id']        = $row['user_id'];
        $user['sex']            = $row['sex'];
        $user['birthday']       = date($row['birthday']);
        $user['ID_card']        = $row['ID_card'];
        $user['user_rank']      = $row['user_rank'];
        $user['balance'] =  finduseraccountinfo($row['user_id'],"balance");
        $user['frozen_money'] =  finduseraccountinfo($row['user_id'],"frozen_money");
        $user['rank_points'] =  finduseraccountinfo($row['user_id'],"rank_points");
        $user['pay_points'] =  finduseraccountinfo($row['user_id'],"pay_points");
        $user['credit_line']    = $row['credit_line'];
        $user['formated_user_money'] = price_format($row['user_money']);
        $user['formated_frozen_money'] = price_format($row['frozen_money']);
        $user['parent_id']      = $row['parent_id'];
        $user['parent_username']= $row['parent_username'];
        $user['qq']             = $row['qq'];
        $user['msn']            = $row['msn'];
        $user['office_phone']   = $row['office_phone'];
        $user['home_phone']     = $row['home_phone'];
        $user['mobile_phone']   = $row['mobile_phone'];
        $user['user_vip']   = $row['user_vip'];
        $user['status']   = $row['status'];
        $user['resource']   = $row['resource'];
        $user['level_xp']   = $row['level_xp'];
    
    }
    else
    {
          $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
          sys_msg($_LANG['username_invalid'], 0, $links);
//        $user['sex']            = 0;
//        $user['pay_points']     = 0;
//        $user['rank_points']    = 0;
//        $user['user_money']     = 0;
//        $user['frozen_money']   = 0;
//        $user['credit_line']    = 0;
//        $user['formated_user_money'] = price_format(0);
//        $user['formated_frozen_money'] = price_format(0);
     }
   
    /* 取出注册扩展字段 */
    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 AND id != 6 ORDER BY dis_order, id';
    $extend_info_list = $db->getAll($sql);

    $sql = 'SELECT reg_field_id, content ' .
           'FROM ' . $ecs->table('reg_extend_info') .
           " WHERE user_id = $user[user_id]";
    $extend_info_arr = $db->getAll($sql);

    $temp_arr = array();
    foreach ($extend_info_arr AS $val)
    {
        $temp_arr[$val['reg_field_id']] = $val['content'];
    }

    foreach ($extend_info_list AS $key => $val)
    {
        switch ($val['id'])
        {
            case 1:     $extend_info_list[$key]['content'] = $user['msn']; break;
            case 2:     $extend_info_list[$key]['content'] = $user['qq']; break;
            case 3:     $extend_info_list[$key]['content'] = $user['office_phone']; break;
            case 4:     $extend_info_list[$key]['content'] = $user['home_phone']; break;
            case 5:     $extend_info_list[$key]['content'] = $user['mobile_phone']; break;
            default:    $extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']] ;
        }
    }

    $smarty->assign('extend_info_list', $extend_info_list);

    /* 当前会员推荐信息 */
    $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
    $smarty->assign('affiliate', $affiliate);

    empty($affiliate) && $affiliate = array();

    if(empty($affiliate['config']['separate_by']))
    {
        //推荐注册分成
        $affdb = array();
        $num = count($affiliate['item']);
        $up_uid = "'$_GET[id]'";
        for ($i = 1 ; $i <=$num ;$i++)
        {
            $count = 0;
            if ($up_uid)
            {
                $sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE parent_id IN($up_uid)";
                $query = $db->query($sql);
                $up_uid = '';
                while ($rt = $db->fetch_array($query))
                {
                    $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                    $count++;
                }
            }
            $affdb[$i]['num'] = $count;
        }
        if ($affdb[1]['num'] > 0)
        {
            $smarty->assign('affdb', $affdb);
        }
    }

    if($user['user_vip']&&$user['resource']==1){
       
        $userdata = array(
                                                
                                                "account" => $user['vip_manage_account'],

                                             );

        $postdata = post_log($userdata,API_URL."/api/user/info/detail",5,$user['user_id']);
        // $user['parent_username'] = $postdata['data']['recommender'];
        $smarty->assign('esettlement',          $postdata['data']['esettlement']);
        $smarty->assign('recommender',$postdata['data']['recommender']);
        $smarty->assign('esettlementArea',       isset($postdata['data']['area'])?$postdata['data']['area']:'');
    }
                          
    assign_query_info();
    $smarty->assign('ur_here',          $_LANG['users_edit']);
    $smarty->assign('action_link',      array('text' => $_LANG['03_users_list'], 'href'=>'users.php?act=list&' . list_link_postfix()));
    $smarty->assign('user',             $user);
    $smarty->assign('form_action',      'updateparent');
    $smarty->assign('special_ranks',    get_rank_list(true));
    $smarty->assign('get_user_vip_list',    get_user_vip_list());
    $smarty->assign('get_status_list',    get_newstatus_list());

    $smarty->display('user_parent_info.htm');
}
//会员导出
elseif ($_REQUEST['act'] == 'userexport'){
    $list=($_SESSION['user_list']);
        include_once (ROOT_PATH . 'include/vendor/PHPExcel.php');
         //创建处理对象实例
        $objPhpExcel = new PHPExcel();
        $objPhpExcel->getActiveSheet()->getDefaultColumnDimension()->setAutoSize(true);//设置单元格宽度
        //设置表格的宽度  手动
        $objPhpExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $objPhpExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $objPhpExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
        $objPhpExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        //设置标题
        $rowVal = array(0=>'编号',1=>'会员名', 2=>'性别', 3=>'生日', 4=>'手机号码', 5=>'邮箱', 6=>'状态',7=>'可用资金',8=>'冻结资金',9=>'等级积分',10=>'KD豆',11=>'注册时间',);
        foreach ($rowVal as $k=>$r){
            $objPhpExcel->getActiveSheet()->getStyleByColumnAndRow($k,1)->getFont()->setBold(true);//字体加粗
            $objPhpExcel->getActiveSheet()->getStyleByColumnAndRow($k,1)->getAlignment(); //->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//文字居中
            $objPhpExcel->getActiveSheet()->setCellValueByColumnAndRow($k,1,$r);
        }
        //设置当前的sheet索引 用于后续内容操作
        $objPhpExcel->setActiveSheetIndex(0);
        $objActSheet=$objPhpExcel->getActiveSheet();
        //设置当前活动的sheet的名称
        $title="会员管理";
        $objActSheet->setTitle($title);
        //设置单元格内容
        foreach($list as $k => $v)
        {
            $num = $k+2;
            $objPhpExcel->setActiveSheetIndex(0)
            //Excel的第A列，uid是你查出数组的键值，下面以此类推
            ->setCellValue('A'.$num, $v['user_id'])
            ->setCellValue('B'.$num, $v['user_name'])
            ->setCellValue('C'.$num, $v['sex'])
            ->setCellValue('D'.$num, $v['birthday'])
            ->setCellValue('E'.$num, $v['mobile_phone'])
            ->setCellValue('F'.$num, $v['email'])
            ->setCellValue('G'.$num, $v['is_validated'])
            ->setCellValue('H'.$num, $v['user_money'])
            ->setCellValue('I'.$num, $v['frozen_money'])
            ->setCellValue('J'.$num, $v['rank_points'])
            ->setCellValue('K'.$num, $v['pay_points'])
            ->setCellValue('L'.$num, $v['reg_time'])
             ;
        }
       
        ob_end_clean();//清除缓冲区,避免乱码
        $name = date('Y-m-d'); //设置文件名
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Transfer-Encoding:utf-8");
        header("Pragma: no-cache");
        header('Content-Type: application/vnd.ms-e xcel');
        header('Content-Disposition: attachment;filename="'.$title.'_'.urlencode($name).'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPhpExcel, 'Excel5');
        $objWriter->save('php://output');
   }






/**
 *  返回用户列表数据
 *
 * @access  public
 * @param
 *
 * @return void
 */
function user_list()
{

    $result = get_filter();
    if ($result === false)
    {
        /* 过滤条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }


        $filter['rank'] = empty($_REQUEST['rank']) ? 0 : intval($_REQUEST['rank']);
        $filter['pay_points_gt'] = empty($_REQUEST['pay_points_gt']) ? 0 : intval($_REQUEST['pay_points_gt']);
        $filter['pay_points_lt'] = empty($_REQUEST['pay_points_lt']) ? 0 : intval($_REQUEST['pay_points_lt']);
        $filter['user_id'] = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
        $filter['status'] = $_REQUEST['status'];
        $filter['sort_by']    = empty($_REQUEST['sort_by'])    ? 'user_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC'     : trim($_REQUEST['sort_order']);

        $ex_where =" where user_id >0 ";  
        
        if ($filter['keywords'])
        {
            $ex_where .= " AND (user_name LIKE '%" . mysql_like_quote($filter['keywords']) ."%' OR  vip_manage_account LIKE '%" . mysql_like_quote($filter['keywords']) ."%' OR  mobile_phone = '" . mysql_like_quote($filter['keywords']) ."')";
        }
        if ($filter['rank'])
        {
            $sql = "SELECT min_points, max_points, special_rank FROM ".$GLOBALS['ecs']->table('user_rank')." WHERE rank_id = '$filter[rank]'";
            $row = $GLOBALS['db']->getRow($sql);
            if ($row['special_rank'] > 0)
            {
                /* 特殊等级 */
                $ex_where .= " AND user_rank = '$filter[rank]' ";
            }
            else
            {
                $ex_where .= " AND rank_points >= " . intval($row['min_points']) . " AND rank_points < " . intval($row['max_points']);
            }
        }
       
          if ($filter['user_id'])
        {
            $ex_where .=" AND user_id = '$filter[user_id]' ";
        }
            
                if(isset($filter[status])&&$filter[status]<>''&&$filter[status]<>11){
                  
                       $ex_where .=" AND status = '$filter[status]' ";   
                }
              

      
   
             
           

         
            
       //var_dump($ex_where);exit;

        $filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('users') . $ex_where);

        /* 分页大小 */
        $filter = page_and_size($filter);
        $sql = "SELECT user_id,status, user_name,real_name,nick_name,invite_code,sex,birthday,mobile_phone,email, is_validated,  reg_time, autonym,vip_manage_account,user_rank,user_vip  ".
                " FROM " . $GLOBALS['ecs']->table('users') . $ex_where .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];
       

        $filter['keywords'] = stripslashes($filter['keywords']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $user_list = $GLOBALS['db']->getAll($sql);

    $count = count($user_list);

    for ($i=0; $i<$count; $i++)
    {
        $user_list[$i]['nick_name'] = getEmoji($user_list[$i]['nick_name']);
        $user_list[$i]['balance'] = finduseraccountinfo($user_list[$i]['user_id'],"balance");
        $user_list[$i]['pay_points'] = finduseraccountinfo($user_list[$i]['user_id'],"pay_points");
        $user_list[$i]['frozen_money'] = finduseraccountinfo($user_list[$i]['user_id'],"frozen_money");
        $user_list[$i]['reg_time'] = local_date($GLOBALS['_CFG']['date_format'], $user_list[$i]['reg_time']);
        $user_list[$i]['user_vip_name'] = getUserVipName( $user_list[$i]['user_vip']);
        $user_list[$i]['user_rank_name'] = getUserRankName( $user_list[$i]['user_rank']);
    }
    $arr = array('user_list' => $user_list, 'filter' => $filter,
    'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    $_SESSION['user_list'] = $user_list;  
    return $arr;
   
}
/**
 *  返回用户列表数据
 *
 * @access  public
 * @param
 *
 * @return void
 */
function accountlog_list()
{

    $result = get_filter();
    
    if ($result === false)
    {

        /* 过滤条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        

         if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
           
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        
       
          if ($filter['user_id'])
        {
            $ex_where .=" AND user_id = '$filter[user_id]' ";
        }
        if ($filter['account_type'])
        {
            $ex_where .=" AND account_type = '$filter[account_type]' ";
        } 
        
              

      
   
             
           

         
            
       //var_dump($ex_where);exit;

        $filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('account_log') . $ex_where);

        /* 分页大小 */
        $filter = page_and_size($filter);
        $sql = "SELECT *  ".
                " FROM " . $GLOBALS['ecs']->table('account_log') . $ex_where .
                " ORDER by " . $filter['sort_by'] . ' ' . "log_id asc" .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];
       

        $filter['keywords'] = stripslashes($filter['keywords']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $accountlog_list = $GLOBALS['db']->getAll($sql);

    $count = count($accountlog_list);

    for ($i=0; $i<$count; $i++)
    {
      
        $accountlog_list[$i]['user_name'] = finduserinfo($accountlog_list[$i]['user_id'],"user_name");
        $accountlog_list[$i]['op_name'] = findaccountinfo($accountlog_list[$i]['account_type']);
        $accountlog_list[$i]['op_time'] = date("Y-m-d h:i:s",$accountlog_list[$i]['change_time']);
       
    }

    $arr = array('accountlog_list' => $accountlog_list, 'filter' => $filter,
    'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    $_SESSION['accountlog_list'] = $accountlog_list;  
    return $arr;
   
}
//0:不属于任何VIP,1:白银理客VIP,2：黄金理客VIP，3：钻石理客VIP,4:至尊理客,VIP 
function getUserRankName($userRank){
    $arr = ["未加入","金级","翡翠级","金钻级","至尊","领航员","钻石领航","双钻领航","红宝石","蓝宝石"];
    return $arr[$userRank];
}
//0:不属于任何VIP,1:白银理客VIP,2：黄金理客VIP，3：钻石理客VIP,4:至尊理客,VIP5.省代理，vip6国家代理，7股东
function getUserVipName($userVip){
    $arr = ["未加入","店主","服务商","合伙人","拓客合伙人"];
    return $arr[$userVip];
}
function getUserChanges($before,$after){
    $change=[];
    foreach ($before as $key=> $value){
        if($after[$key]!=$value){
            $change[$key]=["b"=>$value,"a"=>$after[$key]];
        }
    }
    $change["u"] = $before["user_id"];
    return $change;
}

function findaccountinfo($account_type){

    switch ($account_type) {
        case '1':
            
            return "账户余额";
            break;
        case '2':
            
            return "经销商奖金";
            break;
        case '3':
            
            return "VIP套餐奖金";
            break;
        case '4':
            
            return "零售奖金";
            break;
        case '5':
            
            return "等级积分";
            break;
        case '6':
            
            return "鱼宝";
            break;
        
        case '11':
            
            return "零钱钱包";
            break;
        case '12':
            
            return "财富金充值";
            break;
        case '13':
            
            return "活动奖金";
            break;
        default:
            # code...
            break;
    }


}

?>
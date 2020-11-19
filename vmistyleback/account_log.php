<?php

/**
 * ECSHOP 管理中心帐户变动记录
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: account_log.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECTOUCH', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(BASE_PATH . 'helpers/order_helper.php');

/*------------------------------------------------------ */
//-- 办事处列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 检查参数 */
    $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
    if ($user_id <= 0)
    {
        sys_msg('invalid param');
    }
    $user = user_info($user_id);
    if (empty($user))
    {
        sys_msg($_LANG['user_not_exist']);
    }
     $user['user_money'] = finduseraccountinfo($user_id,"balance");
     $user['frozen_money'] = finduseraccountinfo($user_id,"frozen_money");
     $user['rank_points'] = finduseraccountinfo($user_id,"rank_points");
     $user['pay_points'] = finduseraccountinfo($user_id,"pay_points");
    $smarty->assign('user', $user);

    // if (empty($_REQUEST['account_type']) || !in_array($_REQUEST['account_type'],
    //     array('user_money', 'frozen_money', 'rank_points', 'pay_points')))
    // {
    //     $account_type = '';
    // }
    // else
    // {
    //     $account_type = $_REQUEST['account_type'];
    // }
    $account_type = $_REQUEST['account_type'];
    $smarty->assign('account_type', $account_type);

    $smarty->assign('ur_here',      $_LANG['account_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['add_account'], 'href' => 'account_log.php?act=add&user_id=' . $user_id));
    $smarty->assign('full_page',    1);

    $account_list = get_accountlist($user_id, $account_type);
    $smarty->assign('account_list', $account_list['account']);
    $smarty->assign('filter',       $account_list['filter']);
    $smarty->assign('record_count', $account_list['record_count']);
    $smarty->assign('page_count',   $account_list['page_count']);

    assign_query_info();
    $smarty->display('account_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    /* 检查参数 */
    $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
    if ($user_id <= 0)
    {
        sys_msg('invalid param');
    }
    $user = user_info($user_id);
    if (empty($user))
    {
        sys_msg($_LANG['user_not_exist']);
    }
    $smarty->assign('user', $user);

    if (empty($_REQUEST['account_type']) || !in_array($_REQUEST['account_type'],
        array('user_money', 'frozen_money', 'rank_points', 'pay_points')))
    {
        $account_type = '';
    }
    else
    {
        $account_type = $_REQUEST['account_type'];
    }
    $smarty->assign('account_type', $account_type);

    $account_list = get_accountlist($user_id, $account_type);
    $smarty->assign('account_list', $account_list['account']);
    $smarty->assign('filter',       $account_list['filter']);
    $smarty->assign('record_count', $account_list['record_count']);
    $smarty->assign('page_count',   $account_list['page_count']);

    make_json_result($smarty->fetch('account_list.htm'), '',
        array('filter' => $account_list['filter'], 'page_count' => $account_list['page_count']));
}

/*------------------------------------------------------ */
//-- 调节帐户
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    /* 检查权限 */
    admin_priv('account_manage');
    /* 检查参数 */
    $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
    if ($user_id <= 0)
    {
        sys_msg('invalid param');
    }
    $user = user_info($user_id);
    $useraccount = useraccount_info($user_id);
    if (empty($user))
    {
        sys_msg($_LANG['user_not_exist']);
    }
    $smarty->assign('user', $user);
    $smarty->assign('useraccount', $useraccount);

    /* 显示模板 */
    $smarty->assign('ur_here', $_LANG['add_account']);
    $smarty->assign('action_link', array('href' => 'account_log.php?act=list&user_id=' . $user_id, 'text' => $_LANG['account_list']));
    assign_query_info();
    $smarty->display('account_info.htm');
}

/*------------------------------------------------------ */
//-- 提交添加、编辑办事处
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    /* 检查权限 */
    admin_priv('account_manage');
    $token=trim($_POST['token']);
    if($token!=$_CFG['token'])
    {
        sys_msg($_LANG['no_account_change'], 1);
    }



    /* 检查参数 */
    $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
    if ($user_id <= 0)
    {
        sys_msg('invalid param');
    }
    $user = user_info($user_id);
    if (empty($user))
    {
        sys_msg($_LANG['user_not_exist']);
    }

    /* 提交值 */
    $change_desc    = sub_str($_POST['change_desc'], 255, false);
    $user_money     = floatval($_POST['add_sub_user_money']) * abs(floatval($_POST['user_money']));
    $frozen_money   = floatval($_POST['add_sub_frozen_money']) * abs(floatval($_POST['frozen_money']));
    $rank_points    = floatval($_POST['add_sub_rank_points']) * abs(floatval($_POST['rank_points']));
    $pay_points     = floatval($_POST['add_sub_pay_points']) * abs(floatval($_POST['pay_points']));
    $lxpoint     = floatval($_POST['add_sub_lxpoint']) * abs(floatval($_POST['lxpoint']));
    
    /*  $vcoin     = floatval($_POST['add_sub_vcoin']) * abs(floatval($_POST['vcoin']));
    $fish_ticket     = floatval($_POST['add_sub_fish_ticket']) * abs(floatval($_POST['fish_ticket']));
    */ 
    if ($user_money == 0 && $frozen_money == 0 && $rank_points == 0 && $pay_points == 0 && $vcoin ==0 && $fish_ticket ==0 && $lxpoint ==0)
    {
        sys_msg($_LANG['no_account_change']);
    }

    /* 保存 */
    if($_POST['user_money']){
         new_log_account_change($user_id, $user_money, $change_desc, ACT_ADJUSTING,1);
    }
     if($_POST['frozen_money']){
         new_log_account_change($user_id, $frozen_money, $change_desc, ACT_ADJUSTING,9);
    }
     if($_POST['rank_points']){
         new_log_account_change($user_id, $rank_points, $change_desc, ACT_ADJUSTING,5);
    }
     if($_POST['pay_points']){
         new_log_account_change($user_id, $pay_points, $change_desc, ACT_ADJUSTING,6);
    }
    if($_POST['lxpoint']){
        new_log_account_change($user_id, $lxpoint, $change_desc, ACT_ADJUSTING,12);
    }
     if($_POST['vcoin']){
         new_log_account_change($user_id, $vcoin, $change_desc, ACT_ADJUSTING,7);
    }
     if($_POST['fish_ticket']){
         new_log_account_change($user_id, $fish_ticket, $change_desc, ACT_ADJUSTING,8);
    }

  
   

    /* 提示信息 */
    $links = array(
        array('href' => 'account_log.php?act=list&user_id=' . $user_id."&account_type=1", 'text' => $_LANG['account_list'])
    );
    sys_msg($_LANG['log_account_change_ok'], 0, $links);
}

/**
 * 取得帐户明细
 * @param   int     $user_id    用户id
 * @param   string  $account_type   帐户类型：空表示所有帐户，user_money表示可用资金，
 *                  frozen_money表示冻结资金，rank_points表示等级积分，pay_points表示消费积分
 * @return  array
 */
function get_accountlist($user_id, $account_type = '')
{
    /* 检查参数 */
    $where = " WHERE user_id = '$user_id' and account_type = '$account_type'";
    // if (in_array($account_type, array('user_money', 'frozen_money', 'rank_points', 'pay_points','vcoin','fish_ticket')))
    // {
    //     $where .= " AND $account_type <> 0 ";
    // }

    /* 初始化分页参数 */
    $filter = array(
        'user_id'       => $user_id,
        'account_type'  => $account_type
    );

    /* 查询记录总数，计算分页数 */
    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('account_log') . $where;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $filter = page_and_size($filter);

    /* 查询记录 */
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('account_log') . $where .
            " ORDER BY log_id DESC";
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $row['change_time'] = date('Y-m-d H:i:s', $row['change_time']);
        $arr[] = $row;
    }

    return array('account' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>
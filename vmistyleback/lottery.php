<?php

/**
 * ECSHOP 管理中心优惠活动管理
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: lottery.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECTOUCH', true);
require(dirname(__FILE__) . '/includes/init.php');
$image = new image($_CFG['bgcolor']);
$exc = new exchange($ecs->table('lottery_prize'), $db, 'id', 'name');

/*------------------------------------------------------ */
//-- 活动列表页
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'index')
{
    admin_priv('lottery');
    /* 模板赋值 */
    $smarty->assign('full_page',   1);
    $smarty->assign('ur_here',     "抽奖设置");
    
    /* 显示商品列表页面 */
    $smarty->display('lottery_index.htm');
}
elseif ($_REQUEST['act'] == 'log_list')
{
    admin_priv('lottery');

    /* 模板赋值 */
    $smarty->assign('full_page',   1);
    $smarty->assign('ur_here',     "用戶中獎紀錄");
    $smarty->assign('action_link', array('href' => 'lottery.php?act=index', 'text' => "返回抽獎設置"));

    $list = user_lottery_log();

    $smarty->assign('log_list', $list['item']);
    $smarty->assign('filter',          $list['filter']);
    $smarty->assign('record_count',    $list['record_count']);
    $smarty->assign('page_count',      $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    /* 显示商品列表页面 */
    assign_query_info();
    $smarty->display('user_lottery_log_list.htm');
}
elseif($_REQUEST['act']=='lottery_open')
{
    $smarty->assign('full_page',   1);

    if(isset($_POST['open'])){
    
        $ret = $db->autoExecute($ecs->table('lottery_config'), ["openlottery"=>$_POST['open']], 'UPDATE', "id = '1'");
        $url = 'lottery.php?act=index';

        ecs_header("Location: $url\n");
    }
    $r =  $db->getAll("select * from ".$ecs->table("lottery_config")." where id = '1'");

    $smarty->assign('open',   $r['0']['openlottery']);
    $smarty->display('lottery_open.htm');
}
elseif ($_REQUEST['act'] == 'log_query')
{
    $list = user_lottery_log();
    
    $smarty->assign('log_list', $list['item']);
    $smarty->assign('filter',          $list['filter']);
    $smarty->assign('record_count',    $list['record_count']);
    $smarty->assign('page_count',      $list['page_count']);
    
    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    
    make_json_result($smarty->fetch('user_lottery_log_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

/*------------------------------------------------------ */
//-- 分页、排序、查询
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'prize_list')
{
    $game = $_GET["game"];
    $smarty->assign('full_page',   1);
    $smarty->assign('ur_here',     "獎品列表");
    $smarty->assign('action_link', array('href' => "lottery.php?act=add_prize&game={$game}", 'text' => '添加獎品'));
    

    
    $prizeList = $db->getAll("select * from ".$ecs->table("lottery_prize")." where game = '{$game}' order by `index`");
    
    
    $smarty->assign('prize_list', $prizeList);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    $smarty->display('lottery_prize_list.htm');
    

}
elseif ($_REQUEST['act'] == 'prize_list')
{
    $game = $_GET["game"];
    $smarty->assign('full_page',   1);
    $smarty->assign('ur_here',     "獎品列表");
    $smarty->assign('action_link', array('href' => "lottery.php?act=add_prize&game={$game}", 'text' => '添加獎品'));
    
    
    
    $prizeList = $db->getAll("select * from ".$ecs->table("lottery_prize")." where game = '{$game}' order by `index`");
    
    
    $smarty->assign('prize_list', $prizeList);
    
    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    $smarty->display('lottery_prize_list.htm');
    
    
}
/*------------------------------------------------------ */
//-- 删除
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('lottery');

    $lotteryId = intval($_GET['id']);
    $lottery = $db->getRow("SELECT * FROM ".$ecs->table('lottery_prize')." WHERE id = {$lotteryId}");
    if (empty($lottery))
    {
        make_json_error($_LANG['lottery_not_exist']);
    }
    $name = $lottery['name'];
    $exc->drop($lotteryId);

    /* 记日志 */
    admin_log($name, 'remove', 'lottery');

    /* 清除缓存 */
    clear_cache_files();

    $url = 'lottery.php?act=prize_list&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

elseif ($_REQUEST['act'] == 'add_prize')
{
    $smarty->assign("game",$_REQUEST["game"]);
    $smarty->display('lottery_prize_info.htm');
}

elseif ($_REQUEST['act'] == 'save_prize')
{
    $smarty->assign("game",$_REQUEST["game"]);
    
    $params = $_POST;
    unset($params["act"]);
    
    $db->autoExecute($ecs->table("lottery_prize"), $params);
    
    
    $links = array(
        array('href' => "lottery.php?act=add_prize&game={$params["game"]}", 'text' =>"繼續添加獎品"),
        array('href' => "lottery.php?act=prize_list&game={$params["game"]}", 'text' => "獎品列表")
    );
    sys_msg("添加獎品成功", 0, $links);
}
elseif ($_REQUEST['act'] == 'change_log_status')
{
    $id       = intval($_POST['id']);
    
    $ret = $db->autoExecute($ecs->table('user_lottery_log'), ["status"=>2], 'UPDATE', "log_id = '{$id}'");
    make_json_result($id);
    
}

elseif ($_REQUEST['act'] == 'edit_prize_index')
{
    $id       = intval($_POST['id']);
    $index    = floatval($_POST['val']);
    
    if ($exc->edit("`index` = '$index'", $id))
    {
        clear_cache_files();
        make_json_result($index);
    }
}

elseif ($_REQUEST['act'] == 'edit_prize_number')
{
    $id       = intval($_POST['id']);
    $number    = floatval($_POST['val']);
    
    if ($exc->edit("number = '$number'", $id))
    {
        clear_cache_files();
        make_json_result($number);
    }
}
elseif ($_REQUEST['act'] == 'edit_prize_value')
{
    $id       = intval($_POST['id']);
    $value    = floatval($_POST['val']);
    
    if ($exc->edit("value = '$value'", $id))
    {
        clear_cache_files();
        make_json_result($value);
    }
}
elseif ($_REQUEST['act'] == 'edit_prize_chance')
{
    $id       = intval($_POST['id']);
    $chance    = floatval($_POST['val']);
    
    if ($exc->edit("chance = '$chance'", $id))
    {
        clear_cache_files();
        make_json_result($chance);
    }
}


/*
 * 取得优惠活动列表
 * @return   array
 */
function user_lottery_log()
{
    global $_LANG;
    $result = get_filter();
    if ($result === false)
    {
        /* 过滤条件 */
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'log_status' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'asc' : trim($_REQUEST['sort_order']);
        
        $where = "";
        if (!empty($filter['keyword']))
        {
            $where .= " AND user_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }
        if (!empty($_REQUEST["cstart"])){
            $where .= " AND log_time > '{$_REQUEST["cstart"]}'";
        }
        if (!empty($_REQUEST["cend"])){
            $where .= " AND log_time < '{$_REQUEST["cend"]}'";
            
        }
        $sql = "select count(*) from (SELECT u.user_name,u.nick_name,l.log_id,l.create_time log_time,l.`status` log_status  from  " . $GLOBALS['ecs']->table('user_lottery_log') .
        " l join ".$GLOBALS['ecs']->table("users")." u on u.user_id=l.user_id) lc WHERE 1 $where ";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        
        /* 分页大小 */
        $filter = page_and_size($filter);
        
        /* 查询 */
        $sql = "SELECT * from (select u.user_name,u.nick_name,l.log_id,l.prize_name,l.create_time log_time,l.`status` log_status ".
            "FROM " . $GLOBALS['ecs']->table('user_lottery_log') .
            " l join ".$GLOBALS['ecs']->table("users")." u on u.user_id=l.user_id ".
            " ) sc WHERE 1 $where ".
            " ORDER BY $filter[sort_by] $filter[sort_order] ".
            " LIMIT ". $filter['start'] .", $filter[page_size]";
        
        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $res = $GLOBALS['db']->query($sql);
    
    $list = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $list[] = $row;
    }
    
    return array('item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}
?>
<?php

/**
 * ECSHOP 管理中心品牌管理
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: brand.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECTOUCH', true);

require(dirname(__FILE__) . '/includes/init.php');
// include_once(ROOT_PATH . 'includes/cls_image.php');
$image = new image($_CFG['bgcolor']);
admin_priv('goods_kd_cash_scale');
$exc = new exchange($ecs->table("partner_manage"), $db, 'partner_id', 'partner_name','appid','appkey');

/*------------------------------------------------------ */
//-- 品牌列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',      $_LANG['partner_manage']);
    $smarty->assign('action_link',  array('text' => "添加供应商", 'href' => 'partner_manage.php?act=add'));
    $smarty->assign('full_page',    1);

    $partner_manage_list = get_partner_manage_list();

    $smarty->assign('partner_manage_list',   $partner_manage_list['rank']);
    $smarty->assign('filter',       $partner_manage_list['filter']);
    $smarty->assign('record_count', $partner_manage_list['record_count']);
    $smarty->assign('page_count',   $partner_manage_list['page_count']);

    assign_query_info();
    $smarty->display('partner_manage.htm');
}

/*------------------------------------------------------ */
//-- 添加品牌
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('brand_manage');

    $smarty->assign('ur_here',     $_LANG['07_brand_add']);
    $smarty->assign('action_link', array('text' => $_LANG['06_goods_brand_list'], 'href' => 'brand.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->assign('brand', array('sort_order'=>50, 'is_show'=>1));
    $smarty->display('partner_manage_info.htm');
}
elseif ($_REQUEST['act'] == 'insert')
{
    /*检查品牌名是否重复*/
    admin_priv('brand_manage');

    $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;

    $is_only = $exc->is_only('partner_name', $_POST['partner_name']);

    if (!$is_only)
    {
        sys_msg(sprintf("供应商已经存在", stripslashes($_POST['partner_name'])), 1);
    }

  



    /*插入数据*/

    $sql = "INSERT INTO ".$ecs->table('partner_manage')."(partner_name, appid, appkey,permission) ".
           "VALUES ('$_POST[partner_name]', '$_POST[appid]', '$_POST[appkey]', '$_POST[permission]')";
    $db->query($sql);

 

    /* 清除缓存 */
    clear_cache_files();

    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'partner_manage.php?act=add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'partner_manage.php?act=list';

    sys_msg("供应商添加成功", 0, $link);
}

/*------------------------------------------------------ */
//-- 编辑品牌
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    // admin_priv('brand_manage');
    $sql = "SELECT * ".
            "FROM " .$ecs->table('partner_manage'). " WHERE partner_id='$_REQUEST[id]'";
    $partner_manage_list = $db->GetRow($sql);

    $smarty->assign('ur_here',     "213");
    $smarty->assign('action_link', array('text' => $_LANG['partner_manage'], 'href' => 'partner_manage.php?act=list&' . list_link_postfix()));
    $smarty->assign('partner_manage_list',       $partner_manage_list);
    $smarty->assign('form_action', 'updata');

    assign_query_info();
    $smarty->display('partner_manage_info.htm');
}
elseif ($_REQUEST['act'] == 'updata')
{

    admin_priv('brand_manage');
   

    $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;
     /*处理URL*/



    $param = "partner_name = '$_POST[partner_name]',  appid='$_POST[appid]', appkey='$_POST[appkey]',  permission='$_POST[permission]' ";
 

    if ($exc->edit($param,  $_POST['id']))
    {
        /* 清除缓存 */
        clear_cache_files();

     

        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'partner_manage.php?act=list&' . list_link_postfix();
        $note = vsprintf("接口操作成功", $_POST['partner_name']);
        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}




/*------------------------------------------------------ */
//-- 删除品牌
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('brand_manage');

    $id = intval($_GET['id']);

   
    $exc->drop($id);

  

    $url = 'partner_manage.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}



/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $partner_list = get_partner_manage_list();

    $smarty->assign('filter',       $partner_list['filter']);
    $smarty->assign('record_count', $partner_list['record_count']);
    $smarty->assign('page_count',   $partner_list['page_count']);


    $smarty->assign('partner_manage_list',   $partner_list['rank']);
    make_json_result($smarty->fetch('partner_manage.htm'), '',array('filter' => $partner_list['filter'], 'partner_manage_list' => $partner_list['rank'],'page_count' => $partner_list['page_count']));
}

/**
 * 获取品牌列表
 *
 * @access  public
 * @return  array
 */
function get_partner_manage_list()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 分页大小 */
        $filter = array();

        /* 记录总数以及页数 */
        if (isset($_POST['partner_name']))
        {
      
            $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('partner_manage') .' WHERE partner_name = \''.$_POST['partner_name'].'\'';
        }
        else
        {

            $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('partner_manage');
        }

        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 查询记录 */
        if (isset($_POST['partner_name']))
        {
            if(strtoupper(CHARSET) == 'GBK')
            {
                $keyword = iconv("UTF-8", "gb2312", $_POST['partner_name']);
            }
            else
            {
                $keyword = $_POST['partner_name'];
            }
            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('partner_manage')." WHERE partner_name like '%{$keyword}%' ORDER BY partner_id ASC";
        }
        else
        {

            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('partner_manage')." ORDER BY partner_id ASC";

        }

        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }


    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    $data_dir = IS_ECSHOP ? '../data' : DATA_DIR . '/attached';
    $arr = array();
     while ($rows = $GLOBALS['db']->fetchRow($res))
     {
         

         $arr[] = $rows;
     }
  

    return array( 'rank' =>$arr,'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>

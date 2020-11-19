<?php

/**
 * ECSHOP 管理中心文章处理程序文件
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: article.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECTOUCH', true);

require(dirname(__FILE__) . '/includes/init.php');

$image = new image($_CFG['bgcolor']);
 admin_priv('poster_list');
// require_once(ROOT_PATH . "includes/fckeditor/fckeditor.php");
// require_once(ROOT_PATH . 'includes/cls_image.php');

/*初始化数据交换对象 */
$exc   = new exchange($ecs->table("poster"), $db, 'poster_id', 'title');
//$image = new cls_image();
$image = new image($_CFG['bgcolor']);
/* 允许上传的文件类型 */
$allow_file_types = '|GIF|JPG|PNG|BMP|SWF|DOC|XLS|PPT|MID|WAV|ZIP|RAR|PDF|CHM|RM|TXT|';

/*------------------------------------------------------ */
//-- 文章列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('cat_select',  article_cat_list(0));
    $smarty->assign('ur_here',      $_LANG['033_poster_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['poster_add'], 'href' => 'poster.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $poster_list = get_posterslist();

    $smarty->assign('poster_list',    $poster_list['arr']);
    $smarty->assign('filter',          $poster_list['filter']);
    $smarty->assign('record_count',    $poster_list['record_count']);
    $smarty->assign('page_count',      $poster_list['page_count']);

    $sort_flag  = sort_flag($poster_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('poster_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('article_manage');

    $posterslist = get_posterslist();

    $smarty->assign('poster_list',    $posterslist['arr']);
    $smarty->assign('filter',          $posterslist['filter']);
    $smarty->assign('record_count',    $posterslist['record_count']);
    $smarty->assign('page_count',      $posterslist['page_count']);

    $sort_flag  = sort_flag($posterslist['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('poster_list.htm'), '',
        array('filter' => $posterslist['filter'], 'page_count' => $posterslist['page_count']));
}

/*------------------------------------------------------ */
//-- 添加文章
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    //admin_priv('article_manage');

    /* 创建 html editor */
    create_html_editor('FCKeditor1');

    /*初始化*/
    $article = array();
    $article['is_open'] = 1;

    /* 取得分类、品牌 */
    $smarty->assign('goods_cat_list', cat_list());
   // $smarty->assign('brand_list',     get_brand_list());

    /* 清理关联商品 */
    $sql = "DELETE FROM " . $ecs->table('goods_article') . " WHERE article_id = 0";
    $db->query($sql);

    if (isset($_GET['id']))
    {
        $smarty->assign('cur_id',  $_GET['id']);
    }
    $smarty->assign('article',     $article);
    $smarty->assign('cat_select',  poster_cat_list(0));
    $smarty->assign('ur_here',     $_LANG['poster_add']);
    $smarty->assign('action_link', array('text' => $_LANG['33_poster_list'], 'href' => 'poster.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->display('poster_info.htm');
}

/*------------------------------------------------------ */
//-- 添加文章
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    //admin_priv('article_manage');

    /*检查是否重复*/

    $is_only = $exc->is_only('title', $_POST['title'],0, " cat_id ='$_POST[poster_cat]'");

    if (!$is_only)
    {
        sys_msg(sprintf($_LANG['title_exist'], stripslashes($_POST['title'])), 1);
    }

    /* 取得文件地址 */
    $file_url = '';
    if ((isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types))
        {
            sys_msg($_LANG['invalid_file']);
        }
        $file_url = $image->upload_image($_FILES['file'], 'poster');
        // 复制文件
        /* $res = upload_article_file($_FILES['file']);
        if ($res != false)
        {
            $file_url = $res;
        } */
    }

    if ($file_url == '')
    {
        $file_url = $_POST['file_url'];
    }
    /* 取得文件地址 */
    $file_url1 = '';

    if ((isset($_FILES['file1']['error']) && $_FILES['file1']['error'] == 0) || (!isset($_FILES['file1']['error']) && isset($_FILES['file1']['tmp_name']) && $_FILES['file1']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['file1']['tmp_name'], $_FILES['file1']['name'], $allow_file_types))
        {
            sys_msg($_LANG['invalid_file']);
        }
        $file_url1 = $image->upload_image($_FILES['file1'], 'poster');
       
        // 复制文件
        /* $res = upload_article_file($_FILES['file']);
        if ($res != false)
        {
            $file_url = $res;
        } */
    }

    if ($file_url1 == '')
    {
        $file_url1 = $_POST['file_url1'];
    }
     /* 取得文件地址 */
    $file_url2 = '';
    if ((isset($_FILES['file2']['error']) && $_FILES['file2']['error'] == 0) || (!isset($_FILES['file2']['error']) && isset($_FILES['file2']['tmp_name']) && $_FILES['file2']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['file2']['tmp_name'], $_FILES['file2']['name'], $allow_file_types))
        {
            sys_msg($_LANG['invalid_file']);
        }
        $file_url2 = $image->upload_image($_FILES['file2'], 'poster');
        // 复制文件
        /* $res = upload_article_file($_FILES['file']);
        if ($res != false)
        {
            $file_url = $res;
        } */
    }

    if ($file_url3 == '')
    {
        $file_url3 = $_POST['file_url3'];
    }
     /* 取得文件地址 */
    $file_url3 = '';
    if ((isset($_FILES['file3']['error']) && $_FILES['file3']['error'] == 0) || (!isset($_FILES['file3']['error']) && isset($_FILES['file3']['tmp_name']) && $_FILES['file3']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['file3']['tmp_name'], $_FILES['file3']['name'], $allow_file_types))
        {
            sys_msg($_LANG['invalid_file']);
        }
        $file_url3 = $image->upload_image($_FILES['file3'], 'poster');
        // 复制文件
        /* $res = upload_article_file($_FILES['file']);
        if ($res != false)
        {
            $file_url = $res;
        } */
    }

    if ($file_url3 == '')
    {
        $file_url3 = $_POST['file_url3'];
    }

    // /* 计算文章打开方式 */
    // if ($file_url == '')
    // {
    //     $open_type = 0;
    // }
    // else
    // {
    //     $open_type = $_POST['FCKeditor1'] == '' ? 1 : 2;
    // }

    /*插入数据*/
    $add_time = time();
    if (empty($_POST['cat_id']))
    {
        $_POST['cat_id'] = 0;
    }
 
    
  
    $sql = "INSERT INTO ".$ecs->table('poster')."(title, cat_id,  is_open, ".
                " add_time, file_url, qrcode_l,qrcode_t,qrcode_w,qrcode_h, qrcode_link,target,target_title) ".
            "VALUES ('$_POST[title]', '$_POST[cat_id]',  '$_POST[is_open]', ".
                "'$add_time', '$file_url', '$_POST[qrcode_l]','$_POST[qrcode_t]','$_POST[qrcode_w]','$_POST[qrcode_h]', '$_POST[link_url]', '$_POST[target]', '$_POST[target_title]')";
   
    $db->query($sql);

    /* 处理关联商品 */
    $poster_id = $db->insert_id();
    
    if($file_url1)
    {
        $sql1 = "INSERT INTO ".$ecs->table('poster_imglist')."(poster_id, src,  img_l, ".
                " img_t, img_w, img_h,img_circle) ".
            "VALUES ('$poster_id', '$file_url1',  '$_POST[img_l1]', '$_POST[img_t1]','$_POST[img_w1]','$_POST[img_h1]','$_POST[img_circle1]')";
          
        $db->query($sql1);
    }
   
    if($file_url2){
            $sql2 = "INSERT INTO ".$ecs->table('poster_imglist')."(poster_id, src,  img_l, ".
                " img_t, img_w, img_h,img_circle) ".
            "VALUES ('$poster_id', '$file_url2',  '$_POST[img_l2]', '$_POST[img_t2]','$_POST[img_w2]','$_POST[img_h2]','$_POST[img_circle2]')";
            $db->query($sql2);
    }
    if($file_url3){
        $sql3 = "INSERT INTO ".$ecs->table('poster_imglist')."(poster_id, src,  img_l, ".
                " img_t, img_w, img_h,img_circle) ".
            "VALUES ('$poster_id', '$file_url3',  '$_POST[img_l3]', '$_POST[img_t3]','$_POST[img_w3]','$_POST[img_h3]','$_POST[img_circle3]')";
        $db->query($sql3);
    }
    if($_POST['text1']){
        $text1sql = "INSERT INTO ".$ecs->table('poster_textarea')."(poster_id, text,  text_l, ".
                " text_t, text_font, text_style,text_w,text_h) ".
            "VALUES ('$poster_id', '$_POST[text1]',  '$_POST[text_l1]', '$_POST[text_t1]','$_POST[font1]','$_POST[style1]','$_POST[text_w1]','$_POST[text_h1]')";
        $db->query($text1sql);
    }
    if($_POST['text2']){
        $textsql2 = "INSERT INTO ".$ecs->table('poster_textarea')."(poster_id, text,  text_l, ".
                " text_t, text_font, text_style,text_w,text_h) ".
            "VALUES ('$poster_id', '$_POST[text2]',  '$_POST[text_l2]', '$_POST[text_t2]','$_POST[font2]','$_POST[style2]','$_POST[text_w2]','$_POST[text_h2]')";
        $db->query($textsql2);
    }
    if($_POST['text3']){
        $textsql3 = "INSERT INTO ".$ecs->table('poster_textarea')."(poster_id, text,  text_l, ".
                " text_t, text_font, text_style,text_w,text_h) ".
            "VALUES ('$poster_id', '$_POST[text3]',  '$_POST[text_l3]', '$_POST[text_t3]','$_POST[font3]','$_POST[style3]','$_POST[text_w3]','$_POST[text_h3]')";
        $db->query($textsql3);
    }

    

    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'poster.php?act=add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'poster.php?act=list';

    admin_log($_POST['title'],'add','poster');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg($_LANG['articleadd_succeed'],0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('article_manage');

    /* 取文章数据 */
    $sql = "SELECT * FROM " .$ecs->table('poster'). " WHERE poster_id='$_REQUEST[id]'";
    $poster = $db->GetRow($sql);

    /* 创建 html editor */
    create_html_editor('FCKeditor1',$poster['content']);

    /* 取得分类、品牌 */
    $smarty->assign('goods_cat_list', cat_list());
    $smarty->assign('brand_list', get_brand_list());

    /* 取得关联商品 */
    $posterimglist = getposterimglist($_REQUEST[id]);
   
    $postertextarea = getpostertextarea($_REQUEST[id]);
  
    $smarty->assign('posterimglist',$posterimglist);
    $smarty->assign('postertextarea',$postertextarea);
    $smarty->assign('poster',     $poster);

    $smarty->assign('cat_select',  poster_cat_list(0, $poster['cat_id']));
    $smarty->assign('ur_here',     $_LANG['article_edit']);
    $smarty->assign('action_link', array('text' => $_LANG['03_article_list'], 'href' => 'poster.php?act=list&' . list_link_postfix()));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('poster_info.htm');
}

if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
   // admin_priv('article_manage');

    /*检查文章名是否相同*/
    $is_only = $exc->is_only('title', $_POST['title'], $_POST['id'], "cat_id = '$_POST[article_cat]'");

    if (!$is_only)
    {
        sys_msg(sprintf($_LANG['title_exist'], stripslashes($_POST['title'])), 1);
    }


    if (empty($_POST['cat_id']))
    {
        $_POST['cat_id'] = 0;
    }

    /* 取得文件地址 */
    /* 取得文件地址 */
    $file_url = '';
    if ((isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types))
        {
            sys_msg($_LANG['invalid_file']);
        }
        $file_url = $image->upload_image($_FILES['file'], 'poster');
        // 复制文件
        /* $res = upload_article_file($_FILES['file']);
        if ($res != false)
        {
            $file_url = $res;
        } */
    }

    if ($file_url == '')
    {
        $file_url = $_POST['file_url'];
    }
    /* 取得文件地址 */
    $file_url1 = '';
    if ((isset($_FILES['file1']['error']) && $_FILES['file1']['error'] == 0) || (!isset($_FILES['file1']['error']) && isset($_FILES['file1']['tmp_name']) && $_FILES['file1']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['file1']['tmp_name'], $_FILES['file1']['name'], $allow_file_types))
        {
            sys_msg($_LANG['invalid_file']);
        }
        $file_url1 = $image->upload_image($_FILES['file1'], 'poster');
        // 复制文件
        /* $res = upload_article_file($_FILES['file']);
        if ($res != false)
        {
            $file_url = $res;
        } */
    }

    if ($file_url1 == '')
    {
        $file_url1 = $_POST['file_url1'];
    }
     /* 取得文件地址 */
    $file_url2 = '';
    if ((isset($_FILES['file2']['error']) && $_FILES['file2']['error'] == 0) || (!isset($_FILES['file2']['error']) && isset($_FILES['file2']['tmp_name']) && $_FILES['file2']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['file2']['tmp_name'], $_FILES['file2']['name'], $allow_file_types))
        {
            sys_msg($_LANG['invalid_file']);
        }
        $file_url2 = $image->upload_image($_FILES['file2'], 'poster');
        // 复制文件
        /* $res = upload_article_file($_FILES['file']);
        if ($res != false)
        {
            $file_url = $res;
        } */
    }

    if ($file_url2 == '')
    {
        $file_url2 = $_POST['file_url2'];
    }
     /* 取得文件地址 */
    $file_url3 = '';
    if ((isset($_FILES['file3']['error']) && $_FILES['file3']['error'] == 0) || (!isset($_FILES['file3']['error']) && isset($_FILES['file3']['tmp_name']) && $_FILES['file3']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['file3']['tmp_name'], $_FILES['file3']['name'], $allow_file_types))
        {
            sys_msg($_LANG['invalid_file']);
        }
        $file_url3 = $image->upload_image($_FILES['file3'], 'poster');
        // 复制文件
        /* $res = upload_article_file($_FILES['file']);
        if ($res != false)
        {
            $file_url = $res;
        } */
    }

    if ($file_url3 == '')
    {
        $file_url3 = $_POST['file_url3'];
    }
    $delsqlimglist = "DELETE FROM " . $ecs->table('poster_imglist') . " WHERE poster_id = '".$_POST['id']."'";
    $db->query($delsqlimglist);
    $delsqltextlist = "DELETE FROM " . $ecs->table('poster_textarea') . " WHERE poster_id = '".$_POST['id']."'";
    $db->query($delsqltextlist);
    $poster_id = $_POST['id'];
    if($file_url1)
    {
        $sql1 = "INSERT INTO ".$ecs->table('poster_imglist')."(poster_id, src,  img_l, ".
                " img_t, img_w, img_h,img_circle) ".
            "VALUES ('$poster_id', '$file_url1',  '$_POST[img_l1]', '$_POST[img_t1]','$_POST[img_w1]','$_POST[img_h1]','$_POST[img_circle1]')";
        $db->query($sql1);
    }

    if($file_url2){
            $sql2 = "INSERT INTO ".$ecs->table('poster_imglist')."(poster_id, src,  img_l, ".
                " img_t, img_w, img_h,img_circle) ".
            "VALUES ('$poster_id', '$file_url2',  '$_POST[img_l2]', '$_POST[img_t2]','$_POST[img_w2]','$_POST[img_h2]','$_POST[img_circle2]')";
            $db->query($sql2);
    }
    if($file_url3){
        $sql3 = "INSERT INTO ".$ecs->table('poster_imglist')."(poster_id, src,  img_l, ".
                " img_t, img_w, img_h,img_circle) ".
            "VALUES ('$poster_id', '$file_url3',  '$_POST[img_l3]', '$_POST[img_t3]','$_POST[img_w3]','$_POST[img_h3]','$_POST[img_circle3]')";
        $db->query($sql3);
    }
    if($_POST['text1']){
        $text1sql = "INSERT INTO ".$ecs->table('poster_textarea')."(poster_id, text,  text_l, ".
                " text_t, text_font, text_style,text_w,text_h) ".
            "VALUES ('$poster_id', '$_POST[text1]',  '$_POST[text_l1]', '$_POST[text_t1]','$_POST[font1]','$_POST[style1]','$_POST[text_w1]','$_POST[text_h1]')";
        $db->query($text1sql);
    }
    if($_POST['text2']){
        $textsql2 = "INSERT INTO ".$ecs->table('poster_textarea')."(poster_id, text,  text_l, ".
                " text_t, text_font, text_style,text_w,text_h) ".
            "VALUES ('$poster_id', '$_POST[text2]',  '$_POST[text_l2]', '$_POST[text_t2]','$_POST[font2]','$_POST[style2]','$_POST[text_w2]','$_POST[text_h2]')";
        $db->query($textsql2);
    }
   
    if($_POST['text3']){
        $textsql3 = "INSERT INTO ".$ecs->table('poster_textarea')."(poster_id, text,  text_l, ".
                " text_t, text_font, text_style,text_w,text_h) ".
            "VALUES ('$poster_id', '$_POST[text3]',  '$_POST[text_l3]', '$_POST[text_t3]','$_POST[font3]','$_POST[style3]','$_POST[text_w3]','$_POST[text_h3]')";
        $db->query($textsql3);
    }

    if ($exc->edit("title='$_POST[title]', cat_id='$_POST[cat_id]', is_open='$_POST[is_open]',click_count='$_POST[click_count]',  is_open='$_POST[is_open]',sort='$_POST[sort]',qrcode_l='$_POST[qrcode_l]',qrcode_t='$_POST[qrcode_t]',qrcode_w='$_POST[qrcode_w]',qrcode_h='$_POST[qrcode_h]',qrcode_link='$_POST[qrcode_link]',target='$_POST[target]',target_title='$_POST[target_title]', file_url ='$file_url'", $_POST['id']))
    {
        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'poster.php?act=list&' . list_link_postfix();

        $note = sprintf($_LANG['articleedit_succeed'], stripslashes($_POST['title']));
        admin_log($_POST['title'], 'edit', 'poster');

        clear_cache_files();

        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}

/*------------------------------------------------------ */
//-- 编辑文章主题
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_title')
{
    check_authz_json('article_manage');

    $id    = intval($_POST['id']);
    $title = json_str_iconv(trim($_POST['val']));

    /* 检查文章标题是否重复 */
    if ($exc->num("title", $title, $id) != 0)
    {
        make_json_error(sprintf($_LANG['title_exist'], $title));
    }
    else
    {
        if ($exc->edit("title = '$title'", $id))
        {
            clear_cache_files();
            admin_log($title, 'edit', 'article');
            make_json_result(stripslashes($title));
        }
        else
        {
            make_json_error($db->error());
        }
    }
}

/*------------------------------------------------------ */
//-- 切换是否显示
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_show')
{
    check_authz_json('article_manage');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_open = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 切换文章重要性
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_type')
{
    check_authz_json('article_manage');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("article_type = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}



/*------------------------------------------------------ */
//-- 删除文章主题
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    //check_authz_json('article_manage');

    $id = intval($_GET['id']);

    /* 删除原来的文件 */
    $sql = "SELECT file_url FROM " . $ecs->table('poster') . " WHERE poster_id = '$id'";
    $old_url = $db->getOne($sql);
    if ($old_url != '' && strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false)
    {
        @unlink(ROOT_PATH . $old_url);
    }

    $name = $exc->get_name($id);
    if ($exc->drop($id))
    {
        $db->query("DELETE FROM " . $ecs->table('poster_imglist') . " WHERE " . "poster_id =  $id");
        $db->query("DELETE FROM " . $ecs->table('poster_textarea') . " WHERE " . "poster_id = $id");

        admin_log(addslashes($name),'remove','poster');
        clear_cache_files();
    }

    $url = 'poster.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 将商品加入关联
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add_link_goods')
{
    // include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    check_authz_json('article_manage');

    $add_ids = $json->decode($_GET['add_ids']);
    $args = $json->decode($_GET['JSON']);
    $article_id = $args[0];

    if ($article_id == 0)
    {
        $article_id = $db->getOne('SELECT MAX(article_id)+1 AS article_id FROM ' .$ecs->table('article'));
    }

    foreach ($add_ids AS $key => $val)
    {
        $sql = 'INSERT INTO ' . $ecs->table('goods_article') . ' (goods_id, article_id) '.
               "VALUES ('$val', '$article_id')";
        $db->query($sql, 'SILENT') or make_json_error($db->error());
    }

    /* 重新载入 */
    $arr = get_article_goods($article_id);
    $opt = array();

    foreach ($arr AS $key => $val)
    {
        $opt[] = array('value'  => $val['goods_id'],
                        'text'  => $val['goods_name'],
                        'data'  => '');
    }

    make_json_result($opt);
}

/*------------------------------------------------------ */
//-- 将商品删除关联
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'drop_link_goods')
{
    // include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    check_authz_json('article_manage');

    $drop_goods     = $json->decode($_GET['drop_ids']);
    $arguments      = $json->decode($_GET['JSON']);
    $article_id     = $arguments[0];

    if ($article_id == 0)
    {
        $article_id = $db->getOne('SELECT MAX(article_id)+1 AS article_id FROM ' .$ecs->table('article'));
    }

    $sql = "DELETE FROM " . $ecs->table('goods_article').
            " WHERE article_id = '$article_id' AND goods_id " .db_create_in($drop_goods);
    $db->query($sql, 'SILENT') or make_json_error($db->error());

    /* 重新载入 */
    $arr = get_article_goods($article_id);
    $opt = array();

    foreach ($arr AS $key => $val)
    {
        $opt[] = array('value'  => $val['goods_id'],
                        'text'  => $val['goods_name'],
                        'data'  => '');
    }

    make_json_result($opt);
}

/*------------------------------------------------------ */
//-- 搜索商品
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'get_goods_list')
{
    // include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    $filters = $json->decode($_GET['JSON']);

    $arr = get_goods_list($filters);
    $opt = array();

    foreach ($arr AS $key => $val)
    {
        $opt[] = array('value' => $val['goods_id'],
                        'text' => $val['goods_name'],
                        'data' => $val['shop_price']);
    }

    make_json_result($opt);
}
/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'batch')
{
    /* 批量删除 */
    if (isset($_POST['type']))
    {
        if ($_POST['type'] == 'button_remove')
        {
            admin_priv('article_manage');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg($_LANG['no_select_article'], 1);
            }

            /* 删除原来的文件 */
            $sql = "SELECT file_url FROM " . $ecs->table('article') .
                    " WHERE article_id " . db_create_in(join(',', $_POST['checkboxes'])) .
                    " AND file_url <> ''";

            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {
                $old_url = $row['file_url'];
                if (strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false)
                {
                    @unlink(ROOT_PATH . $old_url);
                }
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
                if ($exc->drop($id))
                {
                    $name = $exc->get_name($id);
                    admin_log(addslashes($name),'remove','article');
                }
            }

        }

        /* 批量隐藏 */
        if ($_POST['type'] == 'button_hide')
        {
            check_authz_json('article_manage');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg($_LANG['no_select_article'], 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
              $exc->edit("is_open = '0'", $id);
            }
        }

        /* 批量显示 */
        if ($_POST['type'] == 'button_show')
        {
            check_authz_json('article_manage');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg($_LANG['no_select_article'], 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
              $exc->edit("is_open = '1'", $id);
            }
        }

        /* 批量移动分类 */
        if ($_POST['type'] == 'move_to')
        {
            check_authz_json('article_manage');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']) )
            {
                sys_msg($_LANG['no_select_article'], 1);
            }

            if(!$_POST['target_cat'])
            {
                sys_msg($_LANG['no_select_act'], 1);
            }
            
            foreach ($_POST['checkboxes'] AS $key => $id)
            {
              $exc->edit("cat_id = '".$_POST['target_cat']."'", $id);
            }
        }
    }

    /* 清除缓存 */
    clear_cache_files();
    $lnk[] = array('text' => $_LANG['back_list'], 'href' => 'article.php?act=list');
    sys_msg($_LANG['batch_handle_ok'], 0, $lnk);
}

/* 把商品删除关联 */
function drop_link_goods($goods_id, $article_id)
{
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_article') .
            " WHERE goods_id = '$goods_id' AND article_id = '$article_id' LIMIT 1";
    $GLOBALS['db']->query($sql);
    create_result(true, '', $goods_id);
}

/* 取得文章关联商品 */
function get_article_goods($article_id)
{
    $list = array();
    $sql  = 'SELECT g.goods_id, g.goods_name'.
            ' FROM ' . $GLOBALS['ecs']->table('goods_article') . ' AS ga'.
            ' LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = ga.goods_id'.
            " WHERE ga.article_id = '$article_id'";
    $list = $GLOBALS['db']->getAll($sql);

    return $list;
}

/* 获得海报列表 */
function get_posterslist()
{
 
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'a.poster_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND a.title LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }
        if ($filter['cat_id'])
        {
            echo 333;exit;
            $where .= " AND a." . get_article_children($filter['cat_id']);
        }

        /* 文章总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('poster'). ' AS a '.
               'LEFT JOIN ' .$GLOBALS['ecs']->table('poster_cat'). ' AS ac ON ac.cat_id = a.cat_id '.
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $sql = 'SELECT a.* , ac.cat_name '.
               'FROM ' .$GLOBALS['ecs']->table('poster'). ' AS a '.
               'LEFT JOIN ' .$GLOBALS['ecs']->table('poster_cat'). ' AS ac ON ac.cat_id = a.cat_id '.
               'WHERE 1 ' .$where. ' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'];

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $arr = array();
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        $rows['date'] = date($GLOBALS['_CFG']['time_format'], $rows['add_time']);

        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

/* 上传文件 */
function upload_article_file($upload)
{
    if (!make_dir("../" . DATA_DIR . "/attached/article"))
    {
        /* 创建目录失败 */
        return false;
    }

    $filename = image::random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
    $path     = ROOT_PATH. DATA_DIR . "/attached/article/" . $filename;

    if (ecmoban_move_upload_file($upload, $path))
    {
        return DATA_DIR . "/attached/article/" . $filename;
    }
    else
    {
        return false;
    }
}
    /*获取海报的小图片列表，返回数组*/
    function getposterimglist($poster_id)
    {
       
   
  
        $sql = "select * FROM " . $GLOBALS['ecs']->table('poster_imglist') .
            " WHERE poster_id = '$poster_id'";
        $posterimglist =$GLOBALS['db']->getAll($sql);
        //$posterimglist = $GLOBALS['db']->query($sql);

        return $posterimglist ;
    }
    /*获取海报的小图片列表，返回数组*/
    function getpostertextarea($poster_id)
    {
        $sql = "select * FROM " . $GLOBALS['ecs']->table('poster_textarea') .
            " WHERE poster_id = '$poster_id'";

        $postertextarea =$GLOBALS['db']->getAll($sql);
        //$posterimglist = $GLOBALS['db']->query($sql);

        return $postertextarea ;
    }
    /* 获得文章列表 */
function get_posterlist()
{
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'a.poster_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND a.title LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }
        if ($filter['cat_id'])
        {
            $where .= " AND a." . get_article_children($filter['cat_id']);
        }

        /* 文章总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('poster'). ' AS a '.
               'LEFT JOIN ' .$GLOBALS['ecs']->table('poster_cat'). ' AS ac ON ac.cat_id = a.cat_id '.
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $sql = 'SELECT a.* , ac.cat_name '.
               'FROM ' .$GLOBALS['ecs']->table('poster'). ' AS a '.
               'LEFT JOIN ' .$GLOBALS['ecs']->table('poster_cat'). ' AS ac ON ac.cat_id = a.cat_id '.
               'WHERE 1 ' .$where. ' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'];

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $arr = array();
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        $rows['date'] = date($GLOBALS['_CFG']['time_format'], $rows['add_time']);

        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>
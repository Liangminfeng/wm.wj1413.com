<?php

/**
 * ECSHOP 专题管理
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: topic.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECTOUCH', true);

require(dirname(__FILE__) . '/includes/init.php');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/* 配置风格颜色选项 */
$topic_style_color = array(
                        '0'         => '008080',
                        '1'         => '008000',
                        '2'         => 'ffa500',
                        '3'         => 'ff0000',
                        '4'         => 'ffff00',
                        '5'         => '9acd32',
                        '6'         => 'ffd700'
                          );
$allow_suffix = array('gif', 'jpg', 'png', 'jpeg', 'bmp', 'swf');

/*------------------------------------------------------ */
//-- 专题列表页面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    admin_priv('talk_sucai_list');

    $smarty->assign('ur_here',     $_LANG['09_topic']);

    $smarty->assign('full_page',   1);
    $list = get_talk_sucai_list();

    $smarty->assign('talk_sucai_list',   $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->assign('action_link', array('text' => $_LANG['sucai_add'], 'href' => 'sucai.php?act=add'));
    $smarty->display('talk_sucai_list.htm');
}
/* 添加,编辑 */
if ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
     admin_priv('talk_sucai_list');

    $isadd     = $_REQUEST['act'] == 'add';
    $smarty->assign('isadd', $isadd);
    $sucai_id  = empty($_REQUEST['sucai_id']) ? 0 : intval($_REQUEST['sucai_id']);

    // include_once(ROOT_PATH.'includes/fckeditor/fckeditor.php'); // 包含 html editor 类文件
    $smarty->assign('talk_list',talk_list());
    $smarty->assign('ur_here',     $_LANG['09_topic']);
    $smarty->assign('action_link', list_link($isadd));


    

    if (!$isadd)
    {
        $sql = "SELECT * FROM " . $ecs->table('talk_sucai') . " WHERE sucai_id = '$sucai_id'";
        $sucai = $db->getRow($sql);
        $sucai['start_time'] = local_date('Y-m-d', $sucai['start_time']);
        $sucai['end_time']   = local_date('Y-m-d', $sucai['end_time']);

  

        // require(ROOT_PATH . 'includes/cls_json.php');

    


    $res = explode(";", $sucai['sucai_picture']);
        if(empty($res[0])){
            $sucai_picture = false;
        }else{
            $sucai_picture = explode(";", $sucai['sucai_picture']);
        }
       
        $smarty->assign('sucai_picture',$sucai_picture);
        $smarty->assign('talk_sucai', $sucai);
        $smarty->assign('act',   "update");
    }
    else
    {
        $topic = array('title' => '', 'topic_type' => 0, 'url' => 'http://');
        $smarty->assign('topic', $topic);

        create_html_editor('topic_intro');
        $smarty->assign('act', "insert");
    }
    /* 创建 html editor */
    $input_name = "sucai_content";
    $input_id = "sucai_content";
    $input_value = $sucai?getEmoji($sucai['sucai_content']):'';
    $FCKeditor = '<input type="hidden" id="'.$input_id.'" name="'.$input_name.'" value="'.htmlspecialchars($input_value).'" />
    <iframe id="'.$input_id.'_frame" src="../plugins/editor/editor.php?item='.$input_id.'" width="642" height="482" frameborder="0" scrolling="no"></iframe>';
    $smarty->assign('fckeditor_content', $FCKeditor);
    
    $smarty->display('talk_sucai_edit.htm');
}
elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
     admin_priv('talk_sucai_list');

    $is_insert = $_REQUEST['act'] == 'insert';
    $sucai_id  = empty($_POST['sucai_id']) ? 0 : intval($_POST['sucai_id']);
    $sucai_user_name= $_POST['sucai_user_name'];
    $sucai_sort = empty($_POST['sucai_sort']) ? 0 : intval($_POST['sucai_sort']);
    $is_show = empty($_POST['is_show']) ? 0 : intval($_POST['is_show']);
    $sucai_content = empty($_POST['sucai_content']) ? '' : addEmoji($_POST['sucai_content']);
    $talk_id = empty($_POST['talk_id']) ? 0 : intval($_POST['talk_id']);
    $sucai_type = empty($_POST['sucai_type']) ? 0 : intval($_POST['sucai_type']);
    $sucai_vedio = empty($_POST['sucai_vedio']) ? 0 : $_POST['sucai_vedio'];
    $sucai_href = empty($_POST['sucai_href']) ? '' : $_POST['sucai_href'];
    $goods_id = empty($_POST['goods_id']) ? 0 : intval($_POST['goods_id']);
    $sucai_important = empty($_POST['sucai_important']) ? 0 : intval($_POST['sucai_important']);
    
    $change_time = time();
    if(!empty($_POST['pictures'])){
        $sucai_picture = implode(";", $_POST['pictures']);
    }else{
         $sucai_picture = '';
    }



    // 标题图上传
    if ($_FILES['sucai_user_picture']['name'] && $_FILES['sucai_user_picture']['size'] > 0)
    {

        /* 检查文件合法性 */
        if(!get_file_suffix($_FILES['sucai_user_picture']['name'], $allow_suffix))
        {
            sys_msg($_LANG['invalid_type']);
        }

        /* 处理 */
        $name = date('Ymd');
        for ($i = 0; $i < 6; $i++)
        {
            $name .= chr(mt_rand(97, 122));
        }
        $title_pic_name = explode('.', $_FILES['sucai_user_picture']['name']);
        $name .= '.' . end($title_pic_name);
        $target = ROOT_PATH . DATA_DIR . '/attached/afficheimg/' . $name;

        if (ecmoban_move_upload_file($_FILES['sucai_user_picture'], $target))
        {
            $sucai_user_picture = DATA_DIR . '/attached/afficheimg/' . $name;
         
        }
    }
   
    unset($name, $target);

    $new_sucai_user_picture = empty($sucai_user_picture) ? $_POST['img_url'] : $sucai_user_picture;





    if ($is_insert)
    {
        $sql = "INSERT INTO " . $ecs->table('talk_sucai') . " (sucai_user_name,sucai_sort,is_show,sucai_user_picture,sucai_picture,sucai_content,goods_id,talk_id,sucai_type,sucai_vedio,sucai_href,change_time,sucai_important)" .
                "VALUES ('$sucai_user_name','$sucai_sort','$is_show','$new_sucai_user_picture','$sucai_picture','$sucai_content','$goods_id','$talk_id','$sucai_type','$sucai_vedio','$sucai_href','$change_time','$sucai_important')";
    }
    else
    {
        $sql = "UPDATE " . $ecs->table('talk_sucai') .
                "SET sucai_user_name='$sucai_user_name', sucai_sort='$sucai_sort', sucai_user_picture='$new_sucai_user_picture',sucai_picture='$sucai_picture',sucai_content='$sucai_content', is_show='$is_show',goods_id='$goods_id',talk_id='$talk_id',sucai_type='$sucai_type',sucai_vedio='$sucai_vedio',sucai_href='$sucai_href',change_time='$change_time',sucai_important='$sucai_important'".
               " WHERE sucai_id='$sucai_id' LIMIT 1";
        
        
    }

    $db->query($sql);

    clear_cache_files();

    $links[] = array('href' => 'sucai.php', 'text' =>  $_LANG['back_list']);
    sys_msg($_LANG['succed'], 0, $links);
}
elseif ($_REQUEST['act'] == 'get_goods_list')
{
    // include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    $filters = $json->decode($_GET['JSON']);

    $arr = get_goods_list($filters);
    $opt = array();

    foreach ($arr AS $key => $val)
    {
        $opt[] = array('value' => $val['goods_id'],
                       'text'  => $val['goods_name']);
    }

    make_json_result($opt);
}
elseif ($_REQUEST["act"] == "delete")
{
    admin_priv('topic_manage');

    $sql = "DELETE FROM " . $ecs->table('talk_sucai') . " WHERE ";

    if (!empty($_POST['checkboxs']))
    {
        $sql .= db_create_in($_POST['checkboxs'], 'topic_id');
    }
    elseif (!empty($_GET['id']))
    {
        $_GET['id'] = intval($_GET['id']);
        $sql .= "topic_id = '$_GET[id]'";
    }
    else
    {
        exit;
    }

    $db->query($sql);

    clear_cache_files();

    if (!empty($_REQUEST['is_ajax']))
    {
        $url = 'topic.php?act=query&' . str_replace('act=delete', '', $_SERVER['QUERY_STRING']);
        ecs_header("Location: $url\n");
        exit;
    }

    $links[] = array('href' => 'topic.php', 'text' =>  $_LANG['back_list']);
    sys_msg($_LANG['succed'], 0, $links);
}
elseif ($_REQUEST["act"] == "query")
{
    $topic_list = get_topic_list();
    $smarty->assign('topic_list',   $topic_list['item']);
    $smarty->assign('filter',       $topic_list['filter']);
    $smarty->assign('record_count', $topic_list['record_count']);
    $smarty->assign('page_count',   $topic_list['page_count']);
    $smarty->assign('use_storage',  empty($_CFG['use_storage']) ? 0 : 1);

    /* 排序标记 */
    $sort_flag  = sort_flag($topic_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    $tpl = 'topic_list.htm';
    make_json_result($smarty->fetch($tpl), '',array('filter' => $topic_list['filter'], 'page_count' => $topic_list['page_count']));
}

/**
 * 获取专题列表
 * @access  public
 * @return void
 */
function get_talk_sucai_list()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 查询条件 */
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'sucai_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('talk_sucai');
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('talk_sucai'). " ORDER BY $filter[sort_by] $filter[sort_order]";

        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $query = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    $res = array();

    while($sucai = $GLOBALS['db']->fetch_array($query)){
        $sucai['start_time'] = local_date('Y-m-d',$sucai['start_time']);
        $sucai['end_time']   = local_date('Y-m-d',$sucai['end_time']);
        $sucai['sucai_content']   = mb_substr($sucai['sucai_content'], 0,30,'utf-8')."...";
        $res[] = $sucai; 
    }

    $arr = array('item' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 * 列表链接
 * @param   bool    $is_add     是否添加（插入）
 * @param   string  $text       文字
 * @return  array('href' => $href, 'text' => $text)
 */
function list_link($is_add = true, $text = '')
{
    $href = 'topic.php?act=list';
    if (!$is_add)
    {
        $href .= '&' . list_link_postfix();
    }
    if ($text == '')
    {
        $text = $GLOBALS['_LANG']['topic_list'];
    }

    return array('href' => $href, 'text' => $text);
}

function get_toppic_width_height()
{
    $width_height = array();

    $file_path = ROOT_PATH . 'themes/' . $GLOBALS['_CFG']['template'] . '/topic.dwt';
    if (!file_exists($file_path) || !is_readable($file_path))
    {
        return $width_height;
    }

    $string = file_get_contents($file_path);

    $pattern_width = '/var\s*topic_width\s*=\s*"(\d+)";/';
    $pattern_height = '/var\s*topic_height\s*=\s*"(\d+)";/';
    preg_match($pattern_width, $string, $width);
    preg_match($pattern_height, $string, $height);
    if(isset($width[1]))
    {
        $width_height['pic']['width'] = $width[1];
    }
    if(isset($height[1]))
    {
        $width_height['pic']['height'] = $height[1];
    }
    unset($width, $height);

    $pattern_width = '/TitlePicWidth:\s{1}(\d+)/';
    $pattern_height = '/TitlePicHeight:\s{1}(\d+)/';
    preg_match($pattern_width, $string, $width);
    preg_match($pattern_height, $string, $height);
    if(isset($width[1]))
    {
        $width_height['title_pic']['width'] = $width[1];
    }
    if(isset($height[1]))
    {
        $width_height['title_pic']['height'] = $height[1];
    }

    return $width_height;
}

function get_url_image($url)
{
    $ext = strtolower(end(explode('.', $url)));
    if($ext != "gif" && $ext != "jpg" && $ext != "png" && $ext != "bmp" && $ext != "jpeg")
    {
        return $url;
    }

    $name = date('Ymd');
    for ($i = 0; $i < 6; $i++)
    {
        $name .= chr(mt_rand(97, 122));
    }
    $name .= '.' . $ext;
    $target = ROOT_PATH . DATA_DIR . '/attached/afficheimg/' . $name;

    $tmp_file = DATA_DIR . '/attached/afficheimg/' . $name;
    $filename = ROOT_PATH . $tmp_file;

    $img = file_get_contents($url);

    $fp = @fopen($filename, "a");
    fwrite($fp, $img);
    fclose($fp);

    return $tmp_file;
}
?>

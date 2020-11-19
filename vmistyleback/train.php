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
 * $Id: train.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECTOUCH', true);
require(dirname(__FILE__) . '/includes/init.php');
require(BASE_PATH . 'helpers/goods_helper.php');
$image = new image($_CFG['bgcolor']);
$exc = new exchange($ecs->table('train'), $db, 'train_id', 'name');

/*------------------------------------------------------ */
//-- 活动列表页
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{
    admin_priv('train');

    /* 模板赋值 */
    $smarty->assign('full_page',   1);
    $smarty->assign('ur_here',     $_LANG['train_list']);
    $smarty->assign('action_link', array('href' => 'train.php?act=add', 'text' => $_LANG['add_train']));

    $list = train_list();

    $smarty->assign('train_list', $list['item']);
    $smarty->assign('filter',          $list['filter']);
    $smarty->assign('record_count',    $list['record_count']);
    $smarty->assign('page_count',      $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    /* 显示商品列表页面 */
    assign_query_info();
    $smarty->display('train_list.htm');
}

/*------------------------------------------------------ */
//-- 分页、排序、查询
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'query')
{
    $list = train_list();

    $smarty->assign('train_list', $list['item']);
    $smarty->assign('filter',          $list['filter']);
    $smarty->assign('record_count',    $list['record_count']);
    $smarty->assign('page_count',      $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('train_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

/*------------------------------------------------------ */
//-- 删除
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('train');

    $trainId = intval($_GET['id']);
    $train = $db->getRow("SELECT * FROM ".$ecs->table('train')." WHERE train_id = {$trainId}");
    if (empty($train))
    {
        make_json_error($_LANG['train_not_exist']);
    }
    $name = $train['name'];
    $exc->drop($trainId);

    /* 记日志 */
    admin_log($name, 'remove', 'train');

    /* 清除缓存 */
    clear_cache_files();

    $url = 'train.php?act=list&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch')
{
    /* 取得要操作的记录编号 */
    if (empty($_POST['checkboxes']))
    {
        sys_msg($_LANG['no_record_selected']);
    }
    else
    {
        /* 检查权限 */
        admin_priv('train');

        $ids = $_POST['checkboxes'];

        if (isset($_POST['drop']))
        {
            /* 删除记录 */
            $sql = "DELETE FROM " . $ecs->table('train') .
                    " WHERE train_id " . db_create_in($ids);
            $db->query($sql);

            /* 记日志 */
            admin_log('', 'batch_remove', 'train');

            /* 清除缓存 */
            clear_cache_files();

            $links[] = array('text' => $_LANG['back_train_list'], 'href' => 'train.php?act=list&' . list_link_postfix());
            sys_msg($_LANG['batch_drop_ok']);
        }
    }
}

/*------------------------------------------------------ */
//-- 修改排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_sort_order')
{
    check_authz_json('train');

    $trainId  = intval($_POST['id']);
    $val = intval($_POST['val']);

    $sql = "UPDATE " . $ecs->table('train') .
            " SET sort_order = '$val'" .
            " WHERE train_id = '$trainId' LIMIT 1";
    $db->query($sql);

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 车厢编辑
/*------------------------------------------------------ */
elseif($_REQUEST['act'] == 'class_list'){
    admin_priv('train');
    
    /* 模板赋值 */
    $smarty->assign('full_page',   1);
    $smarty->assign('ur_here',     $_LANG['train_class_list']);
    $smarty->assign('action_link', array('href' => 'train.php?act=list', 'text' => $_LANG['train_list']));
    
    $trainId = $_REQUEST["train_id"];
    
    $list = $db->getAll("select * from ".$ecs->table("train_class")." where train_id = {$trainId} order by sort");
    
    $smarty->assign('train_list', $list);
    $smarty->assign('trainId',$trainId);
    
    $smarty->display('train_class_list.htm');
}


/*------------------------------------------------------ */
//-- 车厢詳細信息
/*------------------------------------------------------ */
elseif($_REQUEST['act'] == 'class_edit'){
    admin_priv('train');
    
    $trainId = $_REQUEST["train_id"];
    
    $classId = $_REQUEST["train_class_id"];
    /* 模板赋值 */
    $smarty->assign('full_page',   1);
    $smarty->assign('ur_here',     $_LANG['train_class_info']);
    $smarty->assign('action_link', array('href' => "train.php?act=class_list&train_id={$trainId}", 'text' => $_LANG['train_class_list']));
    

    $class = array();
    if(!empty($classId)){
        $class = $db->getRow("select * from ".$ecs->table("train_class")." where train_class_id = {$classId}");
    }
    create_html_editor('description', $class["description"]);
    
    
    $smarty->assign('trainClass', $class);
    $smarty->assign('trainId',$trainId);
    $smarty->assign('form_action',"class_save");
    
    $smarty->display('train_class_info.htm');
}

/*------------------------------------------------------ */
//-- 车厢返佣编辑
/*------------------------------------------------------ */
elseif($_REQUEST['act'] == 'class_goods_edit'){
    admin_priv('train');
    
    $train_class_id = $_REQUEST["train_class_id"];
    $train_id = $_REQUEST["train_id"];
    /* 模板赋值 */
    $smarty->assign('full_page',   1);
    $smarty->assign('ur_here',     $_LANG['train_class_rebate']);
    $smarty->assign('action_link', array('href' => "train.php?act=class_list&train_id={$train_id}", 'text' => $_LANG['train_class_list']));
    
    
    
    $sql = "select  g.goods_sn,g.goods_name,g.goods_id,tcg.rebate  from ".$ecs->table("goods")." g join ".$ecs->table("train")." t on g.train_id = t.train_id LEFT JOIN (select * from ".$ecs->table("train_class_goods")." where train_class_id={$train_class_id}) tcg on tcg.goods_id=g.goods_id where t.train_id = {$train_id}";    
    
    $list = $db->getAll($sql);
    
    $smarty->assign('train_list', $list);
    $smarty->assign('train_class_id',$train_class_id);
    
    $smarty->display('train_class_goods_list.htm');
}
/*------------------------------------------------------ */
//-- 车厢保存
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'class_save')
{
    admin_priv('train');
    
    //post数据填充
    $params = $_POST;
    unset($params["act"]);
    foreach ($params as $key => $val){
        $trainClass[$key] = $val;
    }
    
    /* $json = new JSON;
    $_POST['JSON'] = '{"host":"localhost","db":"shopex","user":"root","pass":"123456","prefix":"sdb_","code":"shopex48","path":"../shopex","charset":"UTF8"}';
    $trainClass = $json->decode($_POST['JSON'],true);
     */
    if(empty($trainClass["code"])){
        $trainClass["code"] = strtoupper(substr(md5(time()), 16 , 8));
    }
        
    
    
    if(empty($trainClass["train_class_id"])){
        unset($trainClass["train_class_id"]);
        $ret = $db->autoExecute($ecs->table('train_class'), $trainClass);
        $trainClass["train_class_id"] = $db->insert_id();
    }else{
        $ret = $db->autoExecute($ecs->table('train_class'), $trainClass, 'UPDATE', "train_class_id = '{$trainClass["train_class_id"]}'");
    }
    
    $links = array(
        array('href' => "train.php?act=class_list&train_id={$trainClass["train_id"]}&" . list_link_postfix(), 'text' => $_LANG['back_train_list'])
    );
    sys_msg($_LANG['edit_train_ok'], 0 ,$links);
}
/*------------------------------------------------------ */
//-- 车厢保存
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'class_goods_save')
{
    admin_priv('train');
    $trainGoodsIds = $_REQUEST["goods_id"];
    $rebates = $_REQUEST["rebate"];
    $train_class_id = $_REQUEST["train_class_id"];
    
    $db->query("DELETE FROM ".$ecs->table("train_class_goods")." WHERE train_class_id = '{$train_class_id}'");
    foreach ($trainGoodsIds as $key => $goodsId){
        $row["goods_id"] = $goodsId;
        $row["rebate"] = $rebates[$key];
        $row["train_class_id"] = $train_class_id;
        
        $ret = $db->autoExecute($ecs->table('train_class_goods'), $row);
    }
    
    sys_msg($_LANG['edit_train_ok'], 0);
}

/*------------------------------------------------------ */
//-- 车厢编辑
/*------------------------------------------------------ */
elseif($_REQUEST['act'] == 'class_remove'){
    admin_priv('train');
    
    $trainClassId = $_REQUEST["train_class_id"];
    if(empty($trainClassId)){
        sys_msg("Params error!", 0);
    }else {
        $trainClass = $db->getRow("select * from ".$ecs->table("train_class")." where train_class_id = {$trainClassId}");
        $db->query("delete from ".$ecs->table("train_class")." where train_class_id = {$trainClassId}");
        $links = array(
            array('href' => "train.php?act=class_list&train_id={$trainClass["train_id"]}&" . list_link_postfix(), 'text' => $_LANG['back_train_list'])
            );
        sys_msg($_LANG['edit_train_ok'], 0 ,$links);
    }
}
/*------------------------------------------------------ */
//-- 添加、编辑
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
    /* 检查权限 */
    admin_priv('train');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'add';
    $smarty->assign('form_action', $is_add ? 'insert' : 'update');
    
    $desc = array();
    /* 初始化、取得优惠活动信息 */
    if ($is_add)
    {
        $train = array(
            'train_id'        => 0,
            'name'      => '',
            'start_time'    => date('Y-m-d H:i:s', time() + 86400),
            'end_time'      => date('Y-m-d H:i:s', time() + 3 * 30 * 86400),
        );
        
    }
    else
    {
        if (empty($_GET['id']))
        {
            sys_msg('invalid param');
        }
        $trainId = intval($_GET['id']);
        $train = $db->getRow("SELECT * FROM ".$ecs->table('train')." WHERE train_id = {$trainId}");
        if (empty($train))
        {
            sys_msg($_LANG['train_not_exist']);
        }
        
        $desc =  $db->getRow("SELECT * FROM ".$ecs->table('train_desc')." WHERE train_id = {$trainId}");
        
        $goodsList = $db->getAll("SELECT goods_id,goods_sn,goods_name,train_id,train_show FROM ".$ecs->table('goods')." WHERE train_id = {$trainId}");
        
        $smarty->assign("goodsList",$goodsList);
        
    }

    
    $smarty->assign('desc', $desc);
    
    $smarty->assign('train', $train);

    /* 赋值时间控件的语言 */
    $smarty->assign('cfg_lang', $_CFG['lang']);

    /* 显示模板 */
    if ($is_add)
    {
        $smarty->assign('ur_here', $_LANG['add_train']);
    }
    else
    {
        $smarty->assign('ur_here', $_LANG['edit_train']);
    }
    $href = 'train.php?act=list';
    if (!$is_add)
    {
        $href .= '&' . list_link_postfix();
    }
    /* 创建 html editor */
    $input_name = "desc[desc1]";
    $input_id = "desc1";
    $input_value = $desc["desc1"];
    $FCKeditor = '<input type="hidden" id="'.$input_id.'" name="'.$input_name.'" value="'.htmlspecialchars($input_value).'" />
    <iframe id="'.$input_id.'_frame" src="../plugins/editor/editor.php?item='.$input_id.'" width="642" height="482" frameborder="0" scrolling="no"></iframe>';
    $smarty->assign('fckeditor1', $FCKeditor);
    
    $input_name = "desc[desc2]";
    $input_id = "desc2";
    $input_value = $desc["desc2"];
    $FCKeditor = '<input type="hidden" id="'.$input_id.'" name="'.$input_name.'" value="'.htmlspecialchars($input_value).'" />
    <iframe id="'.$input_id.'_frame" src="../plugins/editor/editor.php?item='.$input_id.'" width="642" height="482" frameborder="0" scrolling="no"></iframe>';
    $smarty->assign('fckeditor2', $FCKeditor);
    
    $input_name = "desc[desc3]";
    $input_id = "desc3";
    $input_value = $desc["desc3"];
    $FCKeditor = '<input type="hidden" id="'.$input_id.'" name="'.$input_name.'" value="'.htmlspecialchars($input_value).'" />
    <iframe id="'.$input_id.'_frame" src="../plugins/editor/editor.php?item='.$input_id.'" width="642" height="482" frameborder="0" scrolling="no"></iframe>';
    $smarty->assign('fckeditor3', $FCKeditor);
    
    $input_name = "desc[desc4]";
    $input_id = "desc4";
    $input_value = $desc["desc4"];
    $FCKeditor = '<input type="hidden" id="'.$input_id.'" name="'.$input_name.'" value="'.htmlspecialchars($input_value).'" />
    <iframe id="'.$input_id.'_frame" src="../plugins/editor/editor.php?item='.$input_id.'" width="642" height="482" frameborder="0" scrolling="no"></iframe>';
    $smarty->assign('fckeditor4', $FCKeditor);
    /*列車接龍*/
    //排除自身
    $where = " status in (2,3) ";
    if(!empty($_GET["id"])){
        $where .=" and train_id != {$_GET["id"]}";
    }
    $sql = "select train_id,name,code from ".$ecs->table("train")." where {$where} order by train_id desc limit 20";
    $ret = $db->getAll($sql);
    $parentTrainList = array();
    foreach ($ret as $key=>$val){
        $parentTrainList[$val["train_id"]] = "{$val["name"]}-{$val["code"]}";
        
    }
    $smarty->assign('parentTrainList', $parentTrainList);
    
    $smarty->assign('action_link', array('href' => $href, 'text' => $_LANG['train_list']));
    assign_query_info();
    $smarty->display('train_info.htm');
}

/*------------------------------------------------------ */
//-- 添加、编辑后提交
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    /* 检查权限 */
    admin_priv('train');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'insert';

    /* 检查名称是否重复 */
    $name = sub_str($_POST['name'], 255, false);
    
    //post数据填充
    $params = $_POST;
    unset($params["act"]);
    
    $desc = $params["desc"];
    unset($params["desc"]);
    
    $goodsList = $params["goods"];
    unset($params["goods"]);
    
    foreach ($params as $key => $val){
        $train[$key] = $val;
    }
    
    if(isset($_FILES["goods"])){
        foreach ($_FILES['goods']["name"] as $key => $name){
            $goods= array(
                "name"=>$name,
                "type"=>$_FILES['goods']["type"][$key],
                "tmp_name"=>$_FILES['goods']["tmp_name"][$key],
                "error"=>$_FILES['goods']["error"][$key],
                "size"=>$_FILES['goods']["size"][$key],
            );
            if ((isset($goods['error']) && $goods['error'] == 0) || (!isset($goods['error']) && isset($goods['tmp_name'] ) &&$goods != 'none'))
            {
                $trainShow = $image->upload_image($goods, 'train');
                $db->autoExecute($ecs->table("goods"), ["train_show"=>$trainShow],"UPDATE","goods_id='$key'");
                
            }
        }
    }
    if ((isset($_FILES['img']['error']) && $_FILES['img']['error'] == 0) || (!isset($_FILES['img']['error']) && isset($_FILES['img']['tmp_name'] ) &&$_FILES['img']['tmp_name'] != 'none'))
    {
        $train["img"] = $image->upload_image($_FILES['img'], 'train');
    }

    /* if (((isset($_FILES['img']['error']) && $_FILES['img']['error'] > 0) || (!isset($_FILES['img']['error']) && isset($_FILES['img']['tmp_name']) && $_FILES['img']['tmp_name'] == 'none')) && empty($_POST['img_url']))
    {
        $link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');
        sys_msg("img error!", 0, $link);
    } */
    
    /* 保存数据 */
    if ($is_add)
    {
        unset($train["train_id"]);
        $maxid = $db->getOne("select max(train_id) from ".$ecs->table("train"));
        
        $train["code"] = "GS".sprintf("%05s",$maxid+1);
        $db->autoExecute($ecs->table('train'), $train, 'INSERT');
        $train['train_id'] = $db->insert_id();
    }
    else
    {
        $db->autoExecute($ecs->table('train'), $train, 'UPDATE', "train_id = '$train[train_id]'");
    }
    
    if(empty($desc["train_desc_id"])){
        unset($desc["train_desc_id"]);
        $desc["train_id"] = $train["train_id"];
        $db->autoExecute($ecs->table('train_desc'), $desc);
    }else{
        $db->autoExecute($ecs->table('train_desc'), $desc, 'UPDATE', "train_desc_id = '$desc[train_desc_id]'");
    }
    
    /* 记日志 */
    if ($is_add)
    {
        admin_log($train['name'], 'add', 'train');
    }
    else
    {
        admin_log($train['name'], 'edit', 'train');
    }

    /* 清除缓存 */
    clear_cache_files();

    /* 提示信息 */
    if ($is_add)
    {
        $links = array(
            array('href' => 'train.php?act=add', 'text' => $_LANG['continue_add_train']),
            array('href' => 'train.php?act=list', 'text' => $_LANG['back_train_list'])
        );
        sys_msg($_LANG['add_train_ok'], 0, $links);
    }
    else
    {
        $links = array(
            array('href' => 'train.php?act=list&' . list_link_postfix(), 'text' => $_LANG['back_train_list'])
        );
        sys_msg($_LANG['edit_train_ok'], 0, $links);
    }
}elseif ($_REQUEST['act'] == 'dill_info'){
    admin_priv('train');
    
    /* 模板赋值 */
    $smarty->assign('full_page',   1);
    $smarty->assign('ur_here',     $_LANG['train_class_dill']);
    $smarty->assign('action_link', array('href' => 'train.php?act=list', 'text' => $_LANG['train_list']));
    
    
    $trainId = $_REQUEST["train_id"];
    
    $keywords = $_REQUEST["keyword"]?$_REQUEST["keyword"]:"";
    
    $train = $db->getRow("select * from {$ecs->table("train")} where train_id ='{$trainId}'");
    if(empty($train))return false;
    
    $filter["keyword"] = $keywords;
    
    if($train["status"]==3){
       
        if(empty($keywords)){
            $filter['record_count'] = $db->getOne("select count(*) from {$ecs->table("train_dill")} where train_id = {$trainId} ");
            $data = $db->query("select * from {$ecs->table("train_dill")} where train_id = {$trainId} order by class_sort asc,class_ranking asc");
        }else{
            $join = " from {$ecs->table("train_dill")} t join {$ecs->table("users")} u on u.user_id=t.user_id ";
            $filter['record_count'] = $db->getOne("select count(t.*) {$join}  where t.train_id = {$trainId} and u.user_name like '%{$keywords}%' ");
            $data = $db->query("select t.* {$join} where t.train_id = {$trainId} and u.user_name like '%{$keywords}%' order by t.class_sort asc,t.class_ranking asc");
            
        }
    
    }else {

        $data = read_data_cache("train_level_{$trainId}");

        if(empty($data))$data=array();
        if(!empty($keywords)){
            
            $userList = $db->getAll("select * from {$ecs->table("users")} where user_name like '%{$keywords}%'");
        
            if(!empty($userList)){

                $ndata = array();
                foreach ($userList as $key=>$val){
                    $ndata[$val["user_id"]] = $data[$val["user_id"]];
                 
                }
                $data = $ndata;
            }
        }
        $filter['record_count'] = count($data);
        /* 分页大小 */

        $filter = page_and_size($filter);
        $data = array_slice($data, $filter["start"],$filter["page_size"]);
    }

    
    $class=array();
    foreach ($data as $key =>$user){

       
        $peoplecount = $db->getOne("select count(*) as total from {$ecs->table("train_user")} where {$user['user_id']}=parent_user_id and train_id={$trainId}");
        $nickname =  $db->getOne("select nick_name  from {$ecs->table("users")} where user_id={$user['user_id']}");
        
        $user['peoplecount'] = $peoplecount;
        $user['nickname'] = $nickname;
         $class[$user["class_name"]][]=$user;

    }
    
    $classList = $db->getAll("select * from {$ecs->table("train_class")} where train_id = {$trainId}");
   
    
    $smarty->assign('filter',          $filter);
    $smarty->assign('train_id',          $trainId);
    $smarty->assign('record_count',    $filter['record_count']);
    $smarty->assign('page_count',      $filter['page_count']);
    $smarty->assign('page',      $filter['page']);
    
    $smarty->assign("trainStatus",$train["status"]);
    

    $smarty->assign('userData', $class);
    $smarty->assign('classList', $classList);
    
    $smarty->display('train_dill_info.htm');
    
    
}elseif ($_REQUEST['act'] == 'train_dill'){
    
    $trainId = $_REQUEST["train_id"];
    
    $data = read_data_cache("train_level_{$trainId}");
    
    $train = $db->getRow("select * from {$ecs->table("train")} where `status`=2 and train_id = {$trainId}");
    if(empty($train)){
        return; 
    }
    $ret  =  $db->query("select * from {$ecs->table("train_class")} where train_id = {$trainId}");
    $trainClassList = array();
    foreach ($ret as  $key => $class){
        $trainClassList[$class["train_class_id"]] = $class;
    }
    
    $userList = array();
    foreach ($data as $key=> $userLevel){
        if(empty($userLevel["train_class_id"])){
            $userUpdateData = array(
                'vip' => '',
                'train_class_id' => NULL,
                'class_ranking' => '',
                'train_ranking' => 1,
                'rebate' => 0,
                'nextvip' => '',
                'class_name' => '月台',
            );
        }else{
            $class = $trainClassList[$userLevel["train_class_id"]];
            
            $userUpdateData = array(
                "train_class_id"    => $userLevel["train_class_id"],
                "class_ranking"     => $userLevel["class_ranking"],
                "train_ranking"     => $userLevel["train_ranking"],
                "rebate"            => $userLevel["rebate"],
                "class_name"        => $userLevel["class_name"],
            );
            if($userLevel["class_ranking"]<=$class["vip"]){
                $userUpdateData["nextvip"] = $class["code"];
            }
        }
        //更新列车用户表
        $db->autoExecute($ecs->table('train_user'), $userUpdateData, 'UPDATE', "train_user_id = '{$userLevel["train_user_id"]}'");
        
        //发放奖励
        if($userLevel["rebate"]>0){
            //每100个积分兑换多少个现金
            $train_bonus = floor($userLevel["rebate"]);
            // $balance = floor( $userLevel["rebate"]*0.8);
            // $pay_points = floor(($userLevel["rebate"]*0.2)/C('integral_scale')*100);
            // $vcoins =  0 ;//floor($userLevel["rebate"]/10);
           // log_account_change($userLevel["user_id"], $balance, 0, 0, 0, "列車{$train["code"]}到站獎金", ACT_ADJUSTING ,$pay_points,$vcoins);
            new_log_account_change($userLevel["user_id"], $train_bonus,"列车{$train["code"]}到站奖金", 19, 14);
         
        }
        
        $logData = array(
            "train_id"  => $userLevel["train_id"],
            "user_id"  => $userLevel["user_id"],
            "username"  => $userLevel["username"],
            "share"  => $userLevel["share"],
            "class_sort"  => $userLevel["class_sort"],
            "class_ranking"  => $userLevel["class_ranking"],
            "train_ranking"  => $userLevel["train_ranking"],
            "rebate"  => $userLevel["rebate"],
            "last_share_time"  => $userLevel["last_share_time"],
            "vip"  => $userLevel["vip"],
            "class_name"  => $userLevel["class_name"],
            "train_class_id"  => $userLevel["train_class_id"],
        );
        $db->autoExecute($ecs->table('train_dill'), $logData);
        
    }
    
    $db->autoExecute($ecs->table('train'), ["status"=>3], 'UPDATE', " train_id= '{$trainId}'");
    
    //更新待激活訂單為代發貨
    $updateSql = "update {$ecs->table("order_info")} o ,{$ecs->table("train_ticket")} tt set o.order_status = ".OS_CONFIRMED." where o.order_status = ".OS_WATTING." and tt.train_id = {$trainId} and tt.order_id=o.order_id";
    $ret  =  $db->query($updateSql);
    
    
    $links = array(
        array('href' => 'train.php?act=list&' . list_link_postfix(), 'text' => $_LANG['back_train_list'])
    );
    sys_msg("結算成功", 0, $links);
}

/*
 * 取得优惠活动列表
 * @return   array
 */
function train_list()
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
        $filter['is_going']   = empty($_REQUEST['is_going']) ? 0 : 1;
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'train_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = "";
        if (!empty($filter['keyword']))
        {
            $where .= " AND name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }
        if ($filter['is_going'])
        {
            $now = time();
            $where .= " AND start_time <= '$now' AND end_time >= '$now' ";
        }

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('train') .
                " WHERE 1 $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $sql = "SELECT * ".
                "FROM " . $GLOBALS['ecs']->table('train') .
                " WHERE 1 $where ".
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
        $status = "train_status_{$row["status"]}";
        $row['status_label'] = $_LANG[$status]; 
        $list[] = $row;
    }

    return array('item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>
<?php

/**
 * ECSHOP 会员帐目管理(包括预付款，余额)
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: user_autonym.php 17217 2011-01-19 06:29:08Z liubo $
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

/*------------------------------------------------------ */
//-- 会员余额记录列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 权限判断 */
    admin_priv('user_autonym');

    /* 指定会员的ID为查询条件 */
    $user_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;


    $smarty->assign('ur_here',       "實名認證申請管理");
    $smarty->assign('id',            $user_id);

    $list = account_list();

    $smarty->assign('list',         $list['list']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);
    $smarty->assign('full_page',    1);

    assign_query_info();
    $smarty->display('user_autonym_list.htm');
}
elseif ($_REQUEST['act'] == 'query')
{
    $list = account_list();
    
    $smarty->assign('list',         $list['list']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);
    
    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    
    make_json_result($smarty->fetch('user_autonym_list.htm'), '', array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}
elseif ($_REQUEST['act'] == 'userautomexport'){
        $list=($_SESSION['userautom_list']);
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
        $rowVal = array(0=>'用户名',1=>'真实姓名', 2=>'证件正面', 3=>'证件反面', 4=>'证件号', 5=>'注册时间', 6=>'申请时间',7=>'拒绝原因',8=>'审核时间',9=>'状态');
        foreach ($rowVal as $k=>$r){
            $objPhpExcel->getActiveSheet()->getStyleByColumnAndRow($k,1)->getFont()->setBold(true);//字体加粗
            $objPhpExcel->getActiveSheet()->getStyleByColumnAndRow($k,1)->getAlignment(); //->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//文字居中
            $objPhpExcel->getActiveSheet()->setCellValueByColumnAndRow($k,1,$r);
        }
        //设置当前的sheet索引 用于后续内容操作
        $objPhpExcel->setActiveSheetIndex(0);
        $objActSheet=$objPhpExcel->getActiveSheet();
        //设置当前活动的sheet的名称
        $title="实名认证申请列表";
        $objActSheet->setTitle($title);
        //设置单元格内容
        foreach($list as $k => $v)
        {
            $num = $k+2;
             if($v['autonym']==1){
                $v['autonym'] = "已申請";
            }elseif($v['autonym']==2){
                $v['autonym'] = "未通過";
            }else{
                $v['autonym'] = "已通過";
            }
            $objPhpExcel->setActiveSheetIndex(0)
            //Excel的第A列，uid是你查出数组的键值，下面以此类推
            ->setCellValue('A'.$num, $v['user_name'])
            ->setCellValue('B'.$num, $v['real_name'])
            ->setCellValue('C'.$num, $v['idcardimg'])
            ->setCellValue('D'.$num, $v['idcardimg2'])
            ->setCellValue('E'.$num, $v['ID_card'])
            ->setCellValue('F'.$num, $v['add_date'])
            ->setCellValue('G'.$num, $v['autonym_submit_time'])
            ->setCellValue('H'.$num, $v['autonym_remark'])
            ->setCellValue('I'.$num, $v['autonym_submit_time'])
            ->setCellValue('J'.$num, $v['autonym'])
            
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

/*------------------------------------------------------ */
//-- 审核實名認證
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'action')
{
    /* 检查权限 */
    admin_priv('user_autonym');
    
    /* 提示信息 */
    $status = $_REQUEST["status"];
    $remark = "";
    if($status==2){
        $remark = $_REQUEST["remark"];
    }
   
    
    $userId = $_REQUEST["user_id"];
    $user = $db->getRow("select  user_id,user_name from ".$ecs->table("users")." where user_id = '{$userId }'");
    $data = array(
        "autonym"       => $status,
        "autonym_remark"       => $remark,
        "audit_user_name"       => $_SESSION['admin_name'],
        "autonym_audit_time" => date("Y-m-d H:i:s")
    );
    $data1 = array(
        "user_id" =>$userId,
        "pay_points"       => 10,
        "change_desc"       => "实名认证送鱼宝",
        "change_time"       => time(),
        "change_type" => 8
    );

    $ret = $db->autoExecute($ecs->table("users"), $data,"UPDATE","user_id = '{$userId}'");
    if($status==3){
        /*认证通过*/
       new_log_account_change($userId, 10,"实名认证送鱼宝",ACT_DRAWING,6);

    }



    $link[0]['text'] = $_LANG['back_list'];
    $link[0]['href'] = 'user_autonym.php?act=list&' . list_link_postfix();
    
    sys_msg("審核完成", 0, $link);
}
/*------------------------------------------------------ */
//-- 审核会员余额页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'check')
{
    /* 检查权限 */
    admin_priv('user_autonym');

    /* 初始化 */
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    /* 如果参数不合法，返回 */
    if ($id == 0)
    {
        ecs_header("Location: user_autonym.php?act=list\n");
        exit;
    }
    $user = $db->getRow("select  user_id,user_name,".getAutonymFiled()." from ".$ecs->table("users")." where user_id = '{$id}'");
    
    $idType = array(
        "1" => "身份证",
        "2" => "护照",
        "3" => "港澳通行证",
    );
    $user["ID_type"] = $idType[$user["ID_type"]];
    /* 模板赋值 */
    $smarty->assign('ur_here',      "審核");
    $smarty->assign('user',      $user);
    $smarty->assign('id',           $id);
    $smarty->assign('action_link',  array('text' => $_LANG['12_user_autonym'],
    'href'=>'user_autonym.php?act=list&' . list_link_postfix()));

    /* 页面显示 */
    assign_query_info();
    // array(15) { ["user_id"]=> string(6) "521988" ["user_name"]=> string(11) "18890382496" ["real_name"]=> string(9) "曹玲芝" ["ID_type"]=> string(9) "身份证" ["ID_card"]=> string(18) "432821196308080022" ["bank"]=> NULL ["bank_card"]=> NULL ["idcardimg"]=> string(60) "http://img02.tenfutenmax.com.cn/FgA0ZwY0tswmtn1ZhGct6hy-HNv5" ["idcardimg2"]=> string(60) "http://img02.tenfutenmax.com.cn/FjS5HI9fFKaoRalEOuq9EsvNM_LG" ["audit_user_name"]=> string(15) "tenmax-shenhe01" ["autonym"]=> string(1) "3" ["autonym_remark"]=> string(0) "" ["autonym_submit_time"]=> string(19) "2019-07-14 15:53:19" ["autonym_audit_time"]=> string(19) "2019-07-15 09:28:07" ["autonym_mobile_phone"]=> NULL }

    $smarty->display('user_autonym_check.htm');
}
/**
 *
 *
 * @access  public
 * @param
 *
 * @return void
 */
function account_list()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 过滤列表 */
        $filter['user_id'] = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'autonym' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);


        $where = " WHERE autonym != 0 ";
        if ($filter['keywords'])
        {
            $where .= " AND user_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%'";
            $where .= " or vip_manage_account ='".mysql_like_quote($filter['keywords'])."'";
            $where .= " or user_id ='".mysql_like_quote($filter['keywords'])."'";
            $where .= " or real_name ='".mysql_like_quote($filter['keywords'])."'";
        }
       
        
         $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('users') . $where;    
         
         $filter['record_count'] =  intval($GLOBALS['db']->getOne($sql))  ;    
       
        /* 分页大小 */
        $filter = page_and_size($filter);     

        /* 查询数据 */
        $sql  = 'SELECT user_id,user_name,ID_card,reg_time,'.getAutonymFiled().' FROM ' .$GLOBALS['ecs']->table('users'). 
            $where . "ORDER by " . $filter['sort_by'] . " " .$filter['sort_order']. " LIMIT ".$filter['start'].", ".$filter['page_size'];
   
        $filter['keywords'] = stripslashes($filter['keywords']);
        set_filter($filter, $sql);    
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
      
    $list = $GLOBALS['db']->getAll($sql);  

    foreach ($list AS $key => $value)
    {
        $list[$key]['add_date']             = local_date($GLOBALS['_CFG']['time_format'], $value['reg_time']);
     }     
    $arr = array('list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
   $_SESSION['userautom_list'] = $list;
    return $arr;
}

function getAutonymFiled(){
    return "real_name,ID_type,ID_card,bank,bank_card,idcardimg,idcardimg2,audit_user_name,autonym,autonym_remark,autonym_submit_time,autonym_audit_time,autonym_mobile_phone";
}
?>
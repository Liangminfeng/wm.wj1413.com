<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：TrainControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：文章控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class LotteryController extends CommonController {


    public function __construct() {
        parent::__construct();
        // 屬性賦值
        $this->user_id = $_SESSION['user_id'];
        $this->action = ACTION_NAME;
        
        $this->check_login();
        
        $this->assign("back_url",urlencode(__HOST__ . $_SERVER['REQUEST_URI']));
        
    }

    public function record(){
        $page_size = 10;
        $pageNo = I('request.page') ? intval(I('request.page')) : 1;
        
        
        $page = ["page_no"=>$pageNo,"page_size"=>$page_size];
        $retData = model('LotteryLog')->getUserLog($_SESSION["user_id"],$page);
        
        $this->pageLimit(url('lottery/record'));
        $this->assign('pager', $this->pageShow($retData["total"]));
        
        $this->assign('logList', $retData["data"]);
        
        $this->display("lottery_record.dwt");
    }
    
     public function jiugong(){
         if($_SESSION["user_id"]){
             
            $user = model('Users')->getusersinfo($_SESSION["user_id"]);
            $this->assign("user",$user);
         }
         
         $prizes = model("LotteryPrize")->select(["game"=>1]);
         $r = model("LotteryConfig")->select(["id"=>1]);
         
         $total = 0;
         foreach ($prizes as $key=>$prize){
             $total+=$prize["value"]*$prize["number"];
         }
         $total = 0;
         $this->assign("open",$r['0']['openlottery']);
         $this->assign("total",$total);
         $this->assign("prizes",$prizes);
         
         
         $this->display("lottery_jiugong.dwt");
     }
    /**
     * 抽奖后台接口
     * @author 武当山道士
     */
     public function jiugongGo(){
         
        $nick = I('nick','','trim');
        
        if(empty($_SESSION["user_id"])){
            return $this->error(L("ws_login_please"));
        }
        $user = model('Users')->getusersinfo($_SESSION["user_id"]);
        if($user["fish_ticket"]<2){
            return $this->error(L("ticket_no_enough"));
        }
        
        $prizes = model("LotteryPrize")->select(["game"=>1],"*","`index` asc");
        //初始化奖品池，8个奖品，满概率100，最小概率为1(id,name以实际数据库取出的数据为准，percent之和等于100)
        //下标存储数组100个下表，0-7 按概率分配对应的数量
        $indexArr = array();
        for($i=0;$i<sizeof($prizes);$i++){
            //庫存等於0 機會為0
            $chance = $prizes[$i]['chance'];
            if($prizes[$i]["number"]==0)$chance=0;
            for($j=0;$j<$chance;$j++){
                //index 追加到数组indexArr
                array_push($indexArr, $i);
            }
        }
        //数组乱序
        shuffle($indexArr);
        //从下标数组中随机取一个下标作为中奖下标，$rand_index 是$indexArr的随机元素的下标（0-99）
        $rand_index = array_rand($indexArr,1);
        //获取中奖信息
        $prize_index = $indexArr[$rand_index];
        $prizeInfo = $prizes[$prize_index];


        $data['pnum'] = $prizeInfo['index']-1;//对应前端奖品编号
        $data['pid'] = $prizeInfo['index'];
        $data['pname'] = $prizeInfo['name'];
        $data['value']  = $prizeInfo['value'];
        
        model("Users")->update("user_id",["fish_ticket"=>$user["fish_ticket"]-2]);
        
        //如果是獎金
        if ($prizeInfo["value"]>0){
            $logArr = array(
                "user_id"   => $user["user_id"],
                "prize_name"  => $prizeInfo["name"],
                "status"    => 1
            );
            if ($prizeInfo["type"]==1){
                model("ClipsBase")->log_account_change($user["user_id"], $this->prizeTax($prizeInfo["value"]), 0,  0,  0, $change_desc = '魚票抽獎余額');
                model("ClipsBase")->log_account_change($user["user_id"], 0, 0,  0,  $this->yupoints($prizeInfo["value"]), $change_desc = '魚票抽獎魚積分');
                $logArr["status"] = 2;
            }
            model("UserLotteryLog")->insert($logArr);
            
            if($prizeInfo["number"]!=-1)model("LotteryPrize")->update(["id"=>$prizeInfo["id"]],["number"=>$prizeInfo["number"]-1]);
        }  
        
        
        $this->success($data);
        
    }
    private function success($data){
        $result =  array(
            "data"  => $data,
            "info"  => '成功',
            'status'=> 1
        );
        echo json_encode($result);
        exit();
    }
    private function error($info){
        $result =  array(
            "data"  => '',
            "msg"  => $info,
            'status'=> 0
        );
        echo json_encode($result);
        exit();
    }
    public function index() {
        $prize_arr = $this->getPrizeZhuanpan();
        
        $this->display("lottery.dwt");
    }
    
    public function zhuanpan(){
        $prize_arr = $this->getPrizeZhuanpan();
        
        $this->display("lottery_zhuanpan.dwt");
    }
    
    public function zhuanpanGo(){
        //奖项初始化
        $prize_arr = $this->getPrizeZhuanpan();
        
        //抽奖开始
        foreach ($prize_arr as $key => $val) {
            $arr[$val['id']] = $val['v'];
        }
        
        $rid = $this->goRandZhuanpan($arr); //根据概率获取奖项id
        
        $res = $prize_arr[$rid-1]; //中奖项
        $min = $res['min'];
        $max = $res['max'];
        if($res['id']==7){ //七等奖
            $i = mt_rand(0,5);
            $result['angle'] = mt_rand($min[$i],$max[$i]);
        }else{
            $result['angle'] = mt_rand($min,$max); //随机生成一个角度
        }
        $result['prize'] = $res['prize'];
        
        echo json_encode($result);
        exit();
    }
    
    private function goRandZhuanpan($proArr){
        $result = '';
        
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        
        return $result;
        
    }
    
    private function getPrizeZhuanpan(){
        //奖项初始化
        $prize_arr = array(
            '0' => array('id'=>1,'min'=>1,'max'=>29,'prize'=>'一等奖','v'=>1),
            '1' => array('id'=>2,'min'=>302,'max'=>328,'prize'=>'二等奖','v'=>2),
            '2' => array('id'=>3,'min'=>242,'max'=>268,'prize'=>'三等奖','v'=>5),
            '3' => array('id'=>4,'min'=>182,'max'=>208,'prize'=>'四等奖','v'=>7),
            '4' => array('id'=>5,'min'=>122,'max'=>148,'prize'=>'五等奖','v'=>10),
            '5' => array('id'=>6,'min'=>62,'max'=>88,'prize'=>'六等奖','v'=>25),
            '6' => array('id'=>7,'min'=>array(32,92,152,212,272,332),'max'=>array(58,118,178,238,298,358),'prize'=>'七等奖','v'=>50)
        );
        return $prize_arr;
        
    }
    
    /**
     * 未登錄驗證
     */
    private function check_login() {
        // 不需要登錄的操作或自己驗證是否登錄（如ajax處理）的方法
        $without = array(
            'index',
            'jiugong',
            'zhuanpan'
        );
        // 未登錄處理
        if (empty($_SESSION['user_id']) && !in_array($this->action, $without)) {
            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect(url('user/register'));
            exit();
        }
        
        // 已經登錄，不能訪問的方法
        $deny = array(
            'login',
            'register'
        );
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0 && in_array($this->action, $deny)) {
            $this->redirect(url('index/index'));
            exit();
        }
    }
    private function prizeTax($money){
        
        return $money*0.8;
        
    }
    //鱼积分
    private function yupoints($money){

        

        $points =  ($money*0.2)/C('integral_scale')*100;

        return $points;

    }

}
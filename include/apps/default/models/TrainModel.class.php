<?php
/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：TrainModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 优惠活动模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 * 
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');
class TrainModel extends BaseModel {
    public $table = "train";
    
    //过期时间
    public $expire = 48;
    
    
   /**
    * 
    * @param array|string $condition
    * @param array $page ["pageNo"=>1,"pageSize"=>10]
    * @return array ["data"=>$data,"total"=>$total,$page]
    */
   function getTrainList($condition,$page=null){
       $list = $this->select($condition,'',"sort asc",$page);
       $count = $this->count($condition);
       return $this->listResult($list, $count, $page);
   }
   
   function getTrainInfo($trainId){
       return $this->find(["train_id"=>$trainId]);
   }
   
   /**
    * 獲取未激活贈送票
    * @param int $userId
    * @param int $orderId
    * @return boolean|array|unknown|mixed
    */
   function getUserUnactiveTicket($userId,$orderId=null){
       
       $where = " where t.user_id = {$userId} and o.order_status = 7 and t.user_id!=t.buyer_id";
       if (!empty($orderId))$where .= " and o.order_id = {$orderId} ";
       
       $sql = "select t.*,b.user_name,b.nick_name,g.goods_name,g.shop_price,tr.code,g.goods_img from {$this->pre}train_ticket t join {$this->pre}goods g on t.goods_id = g.goods_id join {$this->pre}order_info o on o.order_id = t.order_id join {$this->pre}users b on b.user_id = t.buyer_id join {$this->pre}train tr on tr.train_id=t.train_id ".$where;
       return $train = $this->query($sql);
       
   }
   
   function getUserUncheckTicket($userId,$type =1){
       
       $where = " where t.user_id = {$userId} and t.`status` = 5 and t.user_id!=t.buyer_id";
       
       $sql = "select t.*,b.user_name,g.goods_name,g.shop_price,tr.code,g.goods_img from {$this->pre}train_ticket t join {$this->pre}goods g on t.goods_id = g.goods_id  join {$this->pre}users b on b.user_id = t.buyer_id join {$this->pre}train tr on tr.train_id=t.train_id ".$where;
       return $train = $this->query($sql);
   }
   
   public function ticketConfirm($userId,$ticketId=false){
       $where = ["user_id"=>$userId];
       if($ticketId!=false)$where["train_ticket_id"] = $ticketId;
       model("TrainTicket")->update($where,["status"=>2]);
       
   }
   /**
    * 买票上车 
    * @param int $goodsId
    */
   function buyTicket($userId,$goodsId,$orderId){
    
       //查询商品是否存在  是否满座可以上车
       $sql = "SELECT t.name,t.train_id,t.code,t.num,t.total,t.status FROM {$this->pre}goods g join {$this->pre}train t on t.train_id = g.train_id WHERE g.goods_id = '{$goodsId}' AND t.num<=t.total AND t.status=1 AND t.end_time>now() AND t.start_time<now()";
      
       $train = $this->row($sql);
    

       if(empty($train))return $this->error("Can't Buy this item!");
        
       //
       
       $tickModel = model("TrainTicket");
       /* 
       //检测是否已有存票
       $res = $tickModel->find(["user_id"=>$userId,"goods_id"=>$goodsId]);
       if (empty($res)){
           $type  = 1;
       }else{
           $type = 2;
           //更新补票时间
           model("TrainUser")->update(["ticket_time"=>time()+$this->expire*3600],["train_id"=>$train["train_id"],"user_id"=>$userId]);
       } */
       
       $ticketData = array(
           "user_id"    => $userId,
           "train_id"   => $train["train_id"],
           "goods_id"   => $goodsId,
       //   "type"       => $type,
           "buyer_id"   => $userId,
           "order_id"   => $orderId
           
       );

       $ticketId = $tickModel->insert($ticketData);
      
       
       //购票成功
       if($ticketId){
           
           //更新列车状态
           $train["num"]++;
           if($train["total"]<=$train["num"])$train["status"]=2;
           model("Train")->update(["train_id"=>$train["train_id"]],["status"=>$train["status"],"num"=>$train["num"]]);
                      
           //执行获取票
           return $this->getTicket($ticketId);
       }
       return $this->error("insert_error");
   }
   
   
   /**
    * 获得车票     购买或者获赠
    * @param int $ticketId
    * @return array $result 格式：["data"=>$mod,true|false]  $mod:ONTRAIN=上車|ONUSE=自用激活|TOGIVE=用於轉贈  
    */
   function getTicket($ticketId){
       
       //购票成功  判断是否已上车   未上车自动激活
       $mod = "";
       $ticket = model("TrainTicket")->find(["train_ticket_id"=>$ticketId]);

       $user = model("TrainUser")->find(["user_id"=>$ticket["user_id"],"train_id"=>$ticket["train_id"]]);
       $user_info = model('Order')->user_info($_SESSION['user_id']);
       if(!$user) {
           //自动上车 

           $this->onTrain($ticket["train_id"], $ticket);
           $mod = "ONTRAIN";
           
           //更新排名
           $this->levelData($ticket["train_id"]);
           
           
       }else{
           //自动补票激活 必须为自购票
           if($user["status"]==1&&$user["ticket_time"]<time()&&$ticket["buyer_id"]==$user["user_id"]){
               $this->useTicket( $ticket["train_id"], $ticket);
               $mod = "ONUSE";
           }else{
               //自購票累計分享人數
               if($ticket["buyer_id"]==$ticket["user_id"]){
                   $updateData = array();
                   $updateData["share"] = $user["share"]+1;
                   $updateData["last_share_time"] = date("Y-m-d H:i:s");
                   if($updateData["share"]==2){
                       $updateData["status"]=2;
                   }
                   model("TrainUser")->update(["train_user_id"=>$user["train_user_id"]],$updateData);
                   //更新排名
                   $this->levelData($ticket["train_id"]);
                   
               }
               $mod = "TOGIVE";
               model("Users")->update(["user_id"=>$ticket["user_id"]],"fish_ticket=fish_ticket+1");
           }
           

       }
       return $this->result($mod);
   }
   
   /**
    * 激活车票
    * @param int $trainId
    * @param array $ticket
    */
   function useTicket($trainId,$ticket){
       /**
        * @var TrainUserModel $userModel
        */
       $userModel = model("TrainUser");
       
       //车票状态变更为“已激活”
       model("TrainTicket")->update(["train_ticket_id"=>$ticket["train_ticket_id"]],["status"=>3]);
       //该用户变为重新激活
       $userModel->update(["train_id"=>$ticket["train_id"],"user_id"=>$ticket["user_id"]],["ticket_time"=>(time()+$this->expire*3600)]);       
   }
   
   /**
    * 
    * @param int $trainId
    * @param array $ticket
    * @return unknown
    */
   function onTrain($trainId,$ticket){
       /**
        * @var TrainUserModel $userModel
        */
       $userModel = model("TrainUser");
 
       /* 绑定用户邀请关系   */
       $parentUserId = null;
       $inviteUser = null;
       
       if($ticket["status"]!=1)return $this->error("无有效车票");
       
       //查看是否获赠票
       if($ticket["buyer_id"]!=$ticket["user_id"]){
           $parentUserId=$ticket["buyer_id"];
       }else{       
           //查看是否链接邀请
           $inviteCode = $_SESSION["train_invite_code"];
           if(!empty($inviteCode)){
               $inviteUser = $userModel->find(["share_code"=>$inviteCode]);
               //控制有效注册时间
               if(!empty($inviteUser)&&($inviteUser["ticket_time"]>time()||$inviteUser["status"]==2)){
                   $parentUserId = $inviteUser["user_id"];
               }
           }
       }
       $user = model("Users")->find(["user_id"=>$ticket["user_id"]]);

       //确认上车 插入数据

       $trainUser = array(
           "user_id"    => $ticket["user_id"],
           "train_id"   => $trainId,
           "username"   => $user["user_name"],
           "goods_id"   => $ticket["goods_id"],
           "ticket_time"=> time()+$this->expire*3600,
           "share_code" => "LC{$trainId}ND{$user["user_id"]}",
           "parent_user_id"=> $parentUserId,
           "status"     => 1,
           "train_ticket_id"=> $ticket["train_ticket_id"]
       );
       //购买列车订单 。绑定商城的上下级关系，自主购买的话绑定公司球。
 
       if(($ticket["user_id"]!=$parentUserId)&&!$user['parent_id']){
   
          model("Users")->updateuser_reposition($_SESSION['user_id'],$parentUserId);
                       
                           
          model("Users")->update_parent_id($_SESSION['user_id'],$parentUserId);
       }
       //自主购买的话绑定公司球
  
       if(!$parentUserId&&!$user['parent_id']){

          model("Users")->updateuser_reposition($_SESSION['user_id'],'888');
                       
                           
          model("Users")->update_parent_id($_SESSION['user_id'],'888');
       }

       //判断是否本列车的vip
       $train = model("Train")->find(["train_id"=>$trainId]);
       if(!empty($train["parent_train_id"])){
           $lastTrainUser = $userModel->find(["train_id"=>$train["parent_train_id"],"user_id"=>$userId]);
           if (!empty($lastTrainUser)&&!empty($lastTrainUser["nextvip"])){
               $trainUser["vip"] = $lastTrainUser["nextvip"];
           }
       }
       
       //加入列车
       $trainUserId = $userModel->insert($trainUser);
       
       
       if($trainUserId){
           //更新邀请人信息    最后邀请时间  上车状态  分享人数
           if($parentUserId&&$ticket["buyer_id"]==$ticket["user_id"]){
               //判断邀请人邀请数量
               $parentUser = $userModel->find(["train_id"=>$trainId,"user_id"=>$parentUserId]);
               $updateData =array();
               $updateData["share"] = $parentUser["share"]+1;
               $updateData["last_share_time"]=date("Y-m-d H:i:s");
               if($updateData["share"]==2){
                   $updateData["status"] = 2;
               }
               //更新魚票
                model("Users")->update(["user_id"=>$parentUser["user_id"]],"fish_ticket=fish_ticket+1");
               //推薦數
               $userModel->update(["train_user_id"=>$parentUser["train_user_id"]],$updateData);
               
               
           }
           //更新车票使用状态
           model("TrainTicket")->update(["train_ticket_id"=>$ticket["train_ticket_id"]],["status"=>2]);
           
       }
       
       
       return $this->result($trainUserId);
   }
   
   /**
    * 判断是否可购买的同类产品
    * @param int $trainId
    * @param int $goodsId
    * @param int $num
    * @return boolean
    */
   function allowBuy($userId,$goodsId,$num=1){
       
       /* $train = $this->row("SELECT t.name,t.train_id,t.code,t.num,t.total,t.status FROM {$this->pre}goods g join {$this->pre}train t on t.train_id = g.train_id WHERE goods_id = '{$goodsId}' AND t.num+{$num}<=t.total AND t.status=1 AND t.end_time>now() AND t.start_time<now()");
       if(empty($train))return false;
       $ticket = model("TrainTicket")->find(["train_id"=>$train["train_id"],"user_id"=>$userId]);
       if(empty($ticket))return true;
       if($ticket["goods_id"]==$goodsId)return true;
       else return false; */
       
       $train = $this->row("SELECT t.name,t.train_id,t.code,t.num,t.total,t.status,t.start_time,t.end_time FROM {$this->pre}goods g join {$this->pre}train t on t.train_id = g.train_id WHERE goods_id = '{$goodsId}'");
       if(empty($train)){
           return L("train_allow_buy_error_1");
       }
       if($train["status"]!=1||strtotime($train["end_time"])<time()){
           return L("train_allow_buy_error_2");
       }
       if($train["status"]==1&&strtotime($train["start_time"])>time()){
           return L("train_allow_buy_error_5");
       }
       
       if(($train["num"]+$num)>$train["total"]){
           return L("train_allow_buy_error_3").($train["total"]-$train["num"]);
       }
       
       $ticket = model("TrainTicket")->find(["train_id"=>$train["train_id"],"user_id"=>$userId]);
       if(empty($ticket))return "ok";
       if($ticket["goods_id"]==$goodsId)return "ok";
       else return L("train_allow_buy_error_4");
       
   }
   
   /**
    * 
    * @param int $userId
    * @param int $ticketId
    * @param int $toUserId
    */
   function giveTicket($userId,$ticketId,$toUserId){
       /**
        * 
        * @var TrainTicketModel $ticketModel
        */ 
       $ticketModel = model("TrainTicket");
       
       $ticket = $ticketModel->find(["train_ticket_id"=>$ticketId,"user_id"=>$userId,"status"=>1]);
       //if(empty($ticket))return $this->error("ticket_use_error：{$ticketId}：{$userId}：");
       if(empty($ticket))return $this->error("系统错误,请联系客服");
       //查看被赠送着是否上车
       $toUser = model("TrainUser")->find(["user_id"=>$toUserId,"train_id"=>$ticket["train_id"]]);
       if(!empty($toUser))return $this->error("被贈送人已上車，贈送失敗");
       
       //票转移  订单转移  0未不關聯
       if($ticket["order_id"]!=0){
           $orderId = model("Users")->giveorder($userId,$toUserId,$ticket["order_id"]);
       }else{
           $orderId = 0;
       }
       
       $ticketModel->update(["train_ticket_id"=>$ticketId],["status"=>4],["give_time"=>time()]);
       
       $giveTicket = $ticket;
       unset($giveTicket["train_ticket_id"]);
       $giveTicket["user_id"] = $toUserId;
       $giveTicket["order_id"] = $orderId;
       //new give_time
       $giveTicket["give_time"] = time();
       
       $giveTicketId = $ticketModel->insert($giveTicket);
       
       $user = model("Users")->find(["user_id"=>$toUserId]);
       if($user["user_vip"]>0) model('ClipsBase')->new_log_account_change($toUserId,  "3100","首次獲贈車票",ACT_OTHER, 13); 
       
       //执行获取车票动作
       $ret =  $this->getTicket($giveTicketId);
       
       if($ret["status"]){
           if($orderId==0)$ticketModel->update(["train_ticket_id"=>$giveTicketId],["status"=>5]);
       }
       return $ret;
   }

    
   
   private $userList = array();
   /**
    * 
    * @param int $userId
    * @param int $trainId
    * @return array
    */
   function getLevel($userId,$trainId){
       
       $data= $this->levelData($trainId);
       return $data[$userId];
   }
   
   /**
    * 乘客排序
    * @param int $trainId
    */
   
   
   public function levelData($trainId,$cache=true){
       
       // $data = read_data_cache("train_level_{$trainId}");

       // if(!$cache)$data=null;
       // if(!empty($data)){
   
       //     return $data;
       // }
       
       $this->userList = array();
       
       $classList = model("TrainClass")->select(["train_id"=>$trainId],'','sort asc');
       
       $userList = model("TrainUser")->select(["train_id"=>$trainId],'','share asc,last_share_time desc');
       
       $indexList = array();
       $classIndex=0;
       //建立基於分享人數的車廂索引
       if(!empty($userList)){
           for ($i=end($userList)["share"];$i>0;$i--){
               while ($classIndex<count($classList)&&$classList[$classIndex]["limit"]>$i){
                   $classIndex++;
  
               }
               if ($classIndex>count($classList))$indexList[$i] = null;
               else $indexList[$i] = $classIndex;
           }
           
       }
       
       //重構classList  方便快速獲取
       foreach ($classList as $ckey => &$class){
           $class["num"] = 0;
           $class["maynum"] = 0;
           $class["customers"] = array();
           $classGoods = model("TrainClassGoods")->select(["train_class_id"=>$class["train_class_id"]]);
           $ng = array();
           foreach ($classGoods as $key => $val){
               $ng[$val["goods_id"]]=$val["rebate"];
           }
           $class["goods"] =$ng;
           
       }
       
       
       foreach ($userList as $ukey => $user){
           $user["train_ranking"] = $ukey+1;
           //取到对应class
           $classIndex = isset($indexList[$user["share"]])?$indexList[$user["share"]]:null;
           //无法上车了
           if(!isset($classIndex)){
               $user["class_name"] = "月台";
               $user["train_class_id"] = null;
               $user["rebate"] = 0;
               $user["class_sort"] = -1;
               $this->userList[$user["user_id"]] = $user;
           }else{
               $this->insertClass($user,$classList,$classIndex);
           }
       }
       
       //填充列車數據  并更新
       foreach ($classList as $ckey => $nclass){
           $classUserList = $this->sysSortArray($nclass["customers"],"share","SORT_DESC","last_share_time","SORT_ASC");
           if(empty($classUserList)||$nclass["total"]>$nclass["num"])$liveLimit=$nclass["limit"];
           else {
               $liveLimit = $classUserList[0]["share"];
           }
           $vipLimit = 0;
           $normalUp = false;
           foreach ($classUserList as $key => $user){
               $user["class_ranking"] = $key+1;
               $this->userList[$user["user_id"]] = $user;
               $vipLimit =  $user["share"];
               if($user["vip"]!=$nclass["code"]){
                   $normalUp = true;
                   if($liveLimit>$user["share"]) $liveLimit= $user["share"];
               }
           }
           
           if(!$normalUp&&!empty($classUserList))$liveLimit=-1;
           model("TrainClass")->update(["train_class_id"=>$nclass["train_class_id"]],["num"=>$nclass["num"],"live_limit"=>$liveLimit,"vip_limit"=>$vipLimit]);
           
       }
       
       write_data_cache("train_level_{$trainId}", $this->userList);
       
       return $this->userList;
       
   }
   /**
    * @pager array
    * @param int $user_id
    * @return array
    */
   function gettrainidbyuser($user_id,&$pager=null,$fields='t.*')
   {
      $select = "SELECT {$fields} ";
      $sqlBody =   " FROM " . $this->pre . "train_user u join {$this->pre}train t on u.train_id = t.train_id WHERE u.user_id='$user_id'";
      
      if($pager){
          $count = $this->row("select count(*) count".$sqlBody)["count"]; 
          $pager["total"] = $count;
          $sqlBody .= " limit ".$pager["limit"];
      }

      $res = $this->query($select.$sqlBody);
      return $res;
   }
   
   /**
    * @pager array
    * @param int $user_id
    * @return array
    */
   function getTrainShares($user_id,$trainId,&$pager=null)
   {
       $select = "SELECT u.user_avatar,u.nick_name,u.user_name,tu.create_time,u.user_id ";
       $sqlBody =   " FROM " . $this->pre . "train_user tu join ".$this->pre."users u on u.user_id = tu.user_id  WHERE tu.train_id = '{$trainId}' and  tu.parent_user_id='$user_id' order by tu.create_time desc";
       
       if($pager){
           $count = $this->row("select count(*) count".$sqlBody)["count"];
           $pager["total"] = $count;
           $sqlBody .= " limit ".$pager["limit"];
       }
       
       $res = $this->query($select.$sqlBody);
       return $res;
   }
   function TotalTrainShares($user_id,$trainId){
       $select = "SELECT count(*) as total ";
       $sqlBody =   " FROM " . $this->pre . "train_user tu join ".$this->pre."users u on u.user_id = tu.user_id  WHERE tu.train_id = '{$trainId}' and  tu.parent_user_id='$user_id' order by tu.create_time desc";
      
        $res = $this->row($select.$sqlBody);

        if($res){
            return $res['total'];
        }else{
            return 0;
        }
      
    }
    function selectTrainShares($user_id,$page,$size,$trainId){
        $sql =   "SELECT u.user_avatar,u.nick_name,u.user_name,tu.create_time,u.user_id FROM " . $this->pre . "train_user tu join ".$this->pre."users u on u.user_id = tu.user_id  WHERE tu.train_id = '{$trainId}' and  tu.parent_user_id='$user_id' order by tu.create_time desc limit $page, $size";
 
       
        $res = $this->query($sql);
        return $res;
    }
   function getTrainSharesNumber($user_id,$trainId){
       $sqlBody =   " FROM " . $this->pre . "train_user  WHERE train_id = '{$trainId}' and  parent_user_id='$user_id'";
       $count = $this->row("select count(*) count".$sqlBody)["count"];
       return $count;
   }
   
   
   function getTrainDetailByUser($train_id,$user_id){
       $sql = "SELECT t.*  FROM " . $this->pre . "train_user u join {$this->pre}train t on u.train_id = t.train_id WHERE u.user_id='$user_id' and t.train_id={$train_id}";

       $res = $this->row($sql);
       return $res;
   }
   
   /**
    * 
    * @param int $userId
    */
   function getUserTotalRebate($userId){
       
       $sql = "SELECT if(SUM(u.rebate) is null,0,SUM(u.rebate)) total_rebate FROM {$this->pre}train t join {$this->pre}train_user u on u.train_id = t.train_id where t.status=3 and u.user_id = {$userId} ";
       $res = $this->row($sql);
       return $res["total_rebate"];
   }
    
   function getUserLiveRebate($userId){
       $sql = "SELECT t.train_id FROM {$this->pre}train t join {$this->pre}train_user u on u.train_id = t.train_id where t.status=1 and u.user_id = {$userId} ";
       $res = $this->query($sql);
       $total = 0;
       foreach ($res as $key => $train){
           $level = $this->getLevel($userId,$train["train_id"]);
           $total += $level["rebate"];
       }
       return $total;
   }

   private function fillUsserClassInfo(&$user,$class){
       if($class == null){
           
           $user["class_name"] = "月台";
           $user["train_class_id"] = null;
           $user["rebate"] = 0;
           $user["class_sort"] = -1;
       }else{
           $user["class_name"] = $class["name"];
           $user["class_sort"] = $class["sort"];
           $user["train_class_id"] = $class["train_class_id"];
           $user["rebate"] = $class["goods"][$user["goods_id"]];
       }
   }
   private function insertClass($user,&$classList,$classIndex){
       if($classIndex==count($classList)){
           $user["class_name"] = "月台";
           $user["train_class_id"] = null;
           $user["rebate"] = 0;
           $user["class_sort"] = -1;
           $this->userList[$user["user_id"]] = $user;
           return;
       }
       $class = &$classList[$classIndex];
       
       
   /*     $class["maynum"]++;
       if($class["maynum"]%$class["total"]==0)$this->sysSortArray($class["customers"],"share","SORT_DESC","last_share_time","SORT_ASC");
        */
       if($class["total"]==0||$class["total"]>$class["num"]){
           $this->fillUsserClassInfo($user,$class);
           $classList[$classIndex]["num"]++;
           array_push($class["customers"], $user);
           return ;
       }
       //如果是VIP或者上節車廂插入
       
       
        //從車廂末尾檢索非VIP搬遷至下輛車
        $this->fillUsserClassInfo($user,$class);
        array_push($class["customers"], $user);
        foreach ($class["customers"] as $ukey=>$user){
            if($user["vip"]!=$class["code"]){
                $this->insertClass($user,$classList,$classIndex+1,2);
                unset($class["customers"][$ukey]);
                return;
            }
        }
        //如果人數超標  第一名進入下列車廂
        if(count($class["customers"])>$class["total"]){
            foreach ($class["customers"] as $ukey=>$user){
                $this->insertClass($user,$classList,$classIndex+1,2);
                unset($class["customers"][$ukey]);
                return;
            }
        }
        
        
   }
   
   /**
    * 
    * @param array $ArrayData
    * @param string $KeyName1
    * @param string $SortOrder1 "SORT_ASC"
    * @param string $SortType1
    * @return unknown
    */
   private function sysSortArray($ArrayData,$KeyName1,$SortOrder1 = "SORT_ASC",$SortType1 = "SORT_REGULAR")
   {
       if(!is_array($ArrayData)||empty($ArrayData))  //判断传递的参数是不是数组
           {
               return $ArrayData;
       }
       // 得到参数传递过来的编号number
       $ArgCount = func_num_args();
       for($I = 1;$I < $ArgCount;$I ++)
           {
               $Arg = func_get_arg($I);
               if(!preg_match("/SORT/",$Arg))
                   {
                       $KeyNameList[] = $Arg;
                       $SortRule[]    = '$'.$Arg;
               }
               else
                   {
                       $SortRule[]    = $Arg;
               }
       }
       // 根据得到的关键字key和值value,将他们的重新组合成数组.
       foreach($ArrayData AS $Key => $Info)
           {
               foreach($KeyNameList AS $KeyName)
                   {
                       ${$KeyName}[$Key] = $Info[$KeyName];
               }
       }
       
       // 通过eval进行输出.
       $EvalString = 'array_multisort('.join(",",$SortRule).',$ArrayData);';
       eval ($EvalString);
       return $ArrayData;
   }
      /**
    * 獲取别人赠送给我的票也就是收
    * @param int $userId
    * @param int $orderId
    * @return boolean|array|unknown|mixed
    */
   function getUserReceivesendTicket($userId,$orderId=null){
       
       $where = " where t.user_id = {$userId} and t.user_id!=t.buyer_id";
       if (!empty($orderId))$where .= " and o.order_id = {$orderId} ";
       
       $sql = "select t.*,b.user_name,g.goods_name,g.shop_price,tr.code,g.goods_img from {$this->pre}train_ticket t join {$this->pre}goods g on t.goods_id = g.goods_id left join {$this->pre}order_info o on o.order_id = t.order_id join {$this->pre}users b on b.user_id = t.buyer_id join {$this->pre}train tr on tr.train_id=t.train_id ".$where.' order by t.train_ticket_id desc';;

       return $train = $this->query($sql);
       
   }
   //songchuqu
      function getUsersendTicket($userId,$orderId=null){
       
       $where = " where t.buyer_id = {$userId} and t.user_id!=t.buyer_id";
       if (!empty($orderId))$where .= " and o.order_id = {$orderId} ";
       
       $sql = "select t.*,b.user_name,g.goods_name,g.shop_price,tr.code,g.goods_img from {$this->pre}train_ticket t join {$this->pre}goods g on t.goods_id = g.goods_id left join {$this->pre}order_info o on o.order_id = t.order_id join {$this->pre}users b on b.user_id = t.user_id join {$this->pre}train tr on tr.train_id=t.train_id ".$where.' order by t.train_ticket_id desc';

       return $train = $this->query($sql);
       
   }

}

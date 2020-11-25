<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：SmsController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 短信发送控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class SmsController extends CommonController {

    protected $mobile;
    //短信验证码
    protected $mobile_code;
    //安全码
    protected $sms_code;
    //验证码
    protected $captcha;
    
    protected $zone;

    public function __construct() {
        parent::__construct();

        $this->mobile = in($_POST['mobile']);
        $this->mobile_code = in($_POST['mobile_code']);
        $this->sms_code = in($_POST['sms_code']);
        $this->captcha = in($_POST['captcha']);
        $this->zone = in(I("post.zone",86));
        
        
    }
    
    public function yutuiSend(){
        
        $this->mobile_code = $this->random(6, 1);
        $message = "您的验证码是：" . $this->mobile_code . "，请不要把验证码洩露给其他人，如非本人操作，可不用理会";
        
        
        if (empty($this->mobile)) {
            $this->jserror("手机号码不能为空");
        }
        
        /* $preg = '/^13[0-9]{9}|15[012356789][0-9]{8}|18[0-9]{9}|14[579][0-9]{8}|17[0-9]{9}$/'; //简单的方法
        if (!preg_match($preg, $this->mobile)) {
            $this->jserror("手机号码格式不正确");
        } */
        
        $sms = new EcsSms();
        $send_result = $sms->send($this->mobile, $message);
        $send_result = true;
        $this->write_file($this->mobile, date("Y-m-d H:i:s"));
        
        if ($send_result === true) {
            $_SESSION['sms_mobile'] = $this->mobile;
            $_SESSION['sms_mobile_code'] = $this->mobile_code;
            $this->jssuccess($message);
        } else {
            $this->jserror("发送失败");
        }
        
    }
    //new send sms
    public function newsendsms(){

  
            if (empty($this->mobile)) {
                $this->jserror("手机号码不能为空");
            }
            if($this->zone==86){
          
                if(!preg_match("/^1[3456789]{1}\d{9}$/",$this->mobile)){
                      $this->jserror("手机号格式不正确！");
                    
               
                }
            }else{

                if(!preg_match("/^[\d]{6,12}$/",$this->mobile)){
                    $this->jserror("手机号格式不正确！");
                   
                }
                
                
            }
            
            if(ENV=="SMS_DEBUG"){
                $_SESSION['sms_mobile'] = $this->mobile;
                $_SESSION['sms_code']   = date("Ymd");
                $_SESSION['zone']   = $this->zone;
                // if ($_SESSION['sms_mobile']) {
                //     /*短信超过4条*/

                //     if(self::$cache->getValue(date("Ymd")."{$this->mobile}")>4){
                //     $this->jserror("短信发送已经超过4条");
                //         }
                //     if(self::$cache->getValue(date("Ymd")."{$this->mobile}")){
                //              $this->jserror("发送短信过于频繁");
                //         }
                // }
                dump($_SESSION);return;
                    $user = model("Users")->select(["mobile_phone"=>$this->mobile],"user_id");

                    if ($_POST['flag'] == 'register') {
                        //手机注册
                        // if (!empty($user)) {
                            
                        //     $this->jserror("手机号码已经注册");
                        //  }
                    } elseif ($_POST['flag'] == 'forget') {
                        //找回密码
                        if (empty($user)) {
                            $this->jserror("该手机账号不存在\n无法通过该号码找回");
                        }
                        
                    }elseif($_POST['flag']=="modifyphone"){
                        if ($user) {
                            $this->jserror("手机号已被绑定,\n请填写其他手机号");
                        }
                    }
                    $sms_code = $this->random(6, 1);
            
                    // try {
                    //     $res =  AliyunSms::sendSms($this->mobile_code, $this->mobile);
                    // }catch (Exception $e){
                    //     return $e->getMessage();
                    // }
                
                    $_SESSION['sms_mobile'] = $this->mobile;
                    $_SESSION['sms_code']   = $sms_code;
                    $_SESSION['zone']   = $this->zone;
                    //$_SESSION['sms_mobile'] = "15080486089";
                    //$_SESSION['sms_code'] = "888888";
                    
                    if($this->zone == 86) {
                        // $this->turnSms($this->mobile,$sms_code);
                        $this->huyiSms($this->mobile, $sms_code);
                    }else{

                        $this->huyiInternationalSms($this->mobile, $sms_code,$this->zone);
                    } 

                        //$this->jssuccess("ok");
                    }

                    $temp = self::$cache->getValue($this->mobile."_onechance");
                    if($temp){
                        $_SESSION['sms_mobile'] = $this->mobile;
                        $_SESSION['sms_code']   = $temp;
                        $_SESSION['zone']   = $this->zone;
                        self::$cache->delValue($this->mobile."_onechance");
                        $this->jssuccess("ok");
                    }

        }
    //发送
    public function send() {
        
        header("Content-Type: text/html; charset=utf-8");
        if (empty($this->mobile)) {
            exit(json_encode(array('msg' => '手机号码不能为空')));
        }
        
        if ($_SESSION['sms_mobile']) {
            if (strtotime($this->read_file($this->mobile)) > (time() - 60)) {
                exit(json_encode(array('msg' => '获取验证码过于频繁')));
            }
        }
        
        $sql = "SELECT user_id FROM ".$this->model->pre."users where user_name ='$this->mobile' OR mobile_phone = '$this->mobile'";
     
        $user_id = $this->model->query($sql);
      
        if ($_GET['flag'] == 'register') {
            //手机注册
            if (!empty($user_id)) {
               
                exit(json_encode(array('msg' => '手机号码已经注册')));
            }
        } elseif ($_GET['flag'] == 'forget') {
            //找回密码
            if (empty($user_id)) {
                exit(json_encode(array('msg' => "该手机账号不存在\n无法通过该号码找回")));
            }
            
        }elseif($_GET['flag']=="modifyphone"){
            if ($user_id) {
                exit(json_encode(array('msg' => "手机号已被绑定,\n请填写其他手机号")));
            }
        }
        
        $this->mobile_code = $this->random(6, 1);
        
        // try {
        //     $res =  AliyunSms::sendSms($this->mobile_code, $this->mobile);
        // }catch (Exception $e){
        //     return $e->getMessage();
        // }
        $_SESSION['sms_mobile'] = $this->mobile;
        $_SESSION['sms_code'] = $this->mobile_code;
       
       
        if($this->zone=="86"||$this->zone=="86"){
            $res =  AliyunSms::sendSms($this->mobile_code, $this->mobile);
            //$_SESSION['sms_code'] = "888888";
            if ($res->Code =='OK') {
                $this->jssuccess("ok");
            } else {
                $this->jserror("您输入的是一个无效的手机号码");
            }
        }else{
            $sms = new EcsSms();
            $message = "您的验证码：${$this->mobile_code}，该验证码5分钟内有效，若非本人操作,请勿泄漏。";
            if($this->zone=="886"&&(strlen($this->mobile)!=9&&strlen($this->mobile)!=10)){
                $this->jserror("您输入的是一个无效的手机号码");
            }
            if($this->zone=="886"&&strlen($this->mobile)==9)$this->mobile = "0".$this->mobile;
            $send_result = $sms->send($this->zone." ".$this->mobile, $message);
            
            if ($send_result == 200) {
                $this->jssuccess("ok");
            } else {
                $this->jserror("您输入的是一个无111效的手机号码");
            }
        }
        
    }

    //验证
    public function check() {
        if ($this->mobile != $_SESSION['sms_mobile'] or $this->mobile_code != $_SESSION['sms_code']) {
            exit(json_encode(array('msg' => '手机验证码输入错误。')));
        } else {
            exit(json_encode(array('code' => '2')));
        }
    }

    private function random($length = 6, $numeric = 0) {
        PHP_VERSION < '4.2.0' && mt_srand((double) microtime() * 1000000);
        if ($numeric) {
            $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
        } else {
            $hash = '';
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
            $max = strlen($chars) - 1;
            for ($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }
        return $hash;
    }
    //阿里云短信接口
    public function aliyunSend(){

        if (empty($this->mobile)) {
            exit(json_encode(array('msg' => '手机号码不能为空')));
        }

        if ($_SESSION['sms_mobile']) {
            if (strtotime($this->read_file($this->mobile)) > (time() - 60)) {
                exit(json_encode(array('msg' => '获取验证码过于频繁')));
            }
        }

        $sql = "SELECT user_id FROM ".$this->model->pre."users where user_name ='$this->mobile' OR mobile_phone = '$this->mobile'";
        $user_id = $this->model->query($sql);

        if ($_GET['flag'] == 'register') {
            //手机注册
            if (!empty($user_id)) {
                exit(json_encode(array('msg' => '手机号码已经注册')));
            }
        } elseif ($_GET['flag'] == 'forget') {
            //找回密码
            if (empty($user_id)) {
                exit(json_encode(array('msg' => "该手机账号不存在\n无法通过该号码找回")));
            }

        }elseif($_GET['flag']=="modifyphone"){
            if ($user_id) {
                exit(json_encode(array('msg' => "手机号已被绑定,\n请填写其他手机号")));
            }
        }

        $this->mobile_code = $this->random(6, 1);
       
          // try {
          //     $res =  AliyunSms::sendSms($this->mobile_code, $this->mobile);
          // }catch (Exception $e){
          //     return $e->getMessage();
          // }
        $res =  AliyunSms::sendSms($this->mobile_code, $this->mobile);
        // $res = new BrandModel();
        // $res->Code = "OK";
        
        // $_SESSION['sms_mobile'] = $this->mobile;
        // $_SESSION['sms_code'] = $this->mobile_code;
       
            //$_SESSION['sms_code'] = "888888";
        
         if ($res->Code =='OK') {
            $this->jssuccess("ok");
        } else {
            $this->jserror("send_error");
        }
    }
    /**
     * 商讯短信发送
     * 用于发送国内短信
     * @param unknown $phone
     * @param unknown $code
     */
    public function huyiSms($phone,$code){
        
        $message = "您的验证码是：{$code}。请不要把验证码泄露给其他人。";
        $name   = "C55852657";
        $pwd    = "85854f1e828dc8ad20cd386f3ad7959e";
        
        $url = "http://106.ihuyi.com/webservice/sms.php?method=Submit";
        $timeout = 15;
        
        $data = [
            "account"   => $name,
            "password"  => $pwd,
            "mobile"    => "{$phone}",
            "content"   => $message,
            "format"    => "json"
                ];
        
        $file = Http::doPost($url,$data,$timeout);
        
        $res = json_decode($file,true);
        
        if($res['code']==2){
            // var_dump(date("Ymd")."{$phone}");exit;
            
            self::$cache->increase(date("Ymd")."{$phone}", 1);
            if(!self::$cache->getValue(date("Ymd")."{$phone}")){
                self::$cache->setValue(date("Ymd")."{$phone}",1,1440);
            }
            
            
            $this->jssuccess("ok");
        }else{
           
            $this->jserror("发送失败，请确认您的手机号码。");
        }
        
    }
    
    /**
     * 商讯短信发送
     * 用于发送国内短信
     * @param unknown $phone
     * @param unknown $code
     */
    public function huyiInternationalSms($phone,$code,$zone){
        
        $message = "您的验证码是：{$code}。请不要把验证码泄露给其他人。";
        $name   = "I61740252";
        $pwd    = "33555d77b7081cf43448aca39ca5b334";
        
        $url = "http://api.isms.ihuyi.com/webservice/isms.php?method=Submit";
        $timeout = 15;
        
        $data = [
            "account"   => $name,
            "password"  => $pwd,
            "mobile"    => "{$zone} {$phone}",
            "content"   => $message,
            "format"    => "json"
        ];
        
        $file = Http::doPost($url,$data,$timeout);
        
        $res = json_decode($file);
        
        if($res->code==2){
            // var_dump(date("Ymd")."{$phone}");exit;
            
            self::$cache->increase(date("Ymd")."{$phone}", 1);
            if(!self::$cache->getValue(date("Ymd")."{$phone}")){
                self::$cache->setValue(date("Ymd")."{$phone}",1,1440);
            }
            
            
            $this->jssuccess("ok");
        }else{
          
            $this->jserror("发送失败，请确认您的手机号码。");
        }
        
    }
    
    /**
     * 商讯短信发送  
     * 用于发送国内短信
     * @param unknown $phone
     * @param unknown $code
     */
    public function shangxunSms($phone,$code){

        $message = "您的验证码：".$code."，该验证码5分钟内有效，若非本人操作，请勿泄露";
        $name   = "twsc001";
        $pwd    = "Tw98656";
        $dst =  $phone;
        $msg =  mb_convert_encoding($message, gbk,utf8);
      
        $url = "http://www.139000.com/send/gsend.asp?name=$name&pwd=$pwd&dst=$dst&msg=$msg";
        $timeout = 15;
        
        $file = Http::doGet($url,$timeout);
        $resstr = explode("&", $file);
        
        $res = array();

         foreach ($resstr as $kv){
             $kv = explode("=", $kv);
             $res[$kv[0]]=$kv[$kv[1]];
         }

        if($res["num"]>=1){
           // var_dump(date("Ymd")."{$phone}");exit;

            self::$cache->increase(date("Ymd")."{$phone}", 1);
            if(!self::$cache->getValue(date("Ymd")."{$phone}")){
                self::$cache->setValue(date("Ymd")."{$phone}",1,1440);
            }
            

            $this->jssuccess("ok");
        }else{
           
            $this->jserror("发送失败，请确认您的手机号码。");
        }
        
    }
    
    public function turnSms($phone,$code){
        
        $data = array(
            "phone" => $phone,
            "code"  => $code
        );
        $url = API_URL."/api/turn/sms";
        $res = post_log($data ,$url,5);
        $timeout = 15;
        
        if($res["data"]>=1){
            // var_dump(date("Ymd")."{$phone}");exit;
            
            self::$cache->increase(date("Ymd")."{$phone}", 1);
            if(!self::$cache->getValue(date("Ymd")."{$phone}")){
                self::$cache->setValue(date("Ymd")."{$phone}",1,1440);
            }
            $this->jssuccess("ok");
        }else{
            $this->jserror("发送失败，请确认您的手机号码。");
        }
        
        
    }
    /**
     * 天天国际短信  
     * 用于发送境外短信
     * @param unknown $phone
     * @param unknown $code
     * @return boolean[]|string[]
     */
    public function tiantianSms($phone,$code,$zone){
        $message = "您的验证码：".$code."，该验证码5分钟内有效，若非本人操作，请勿泄露";
        
        $data = array (
            'src' => 'tenmax', // 你的用户名, 必须有值
            'pwd' => 'Tmx*7688', // 你的密码, 必须有值
            'ServiceID' => 'SEND', //固定，不需要改变
            'dest' => $zone.$phone, // 你的目的号码【收短信的电话号码】, 必须有值
            'sender' => '', // 你的原号码,可空【大部分国家原号码带不过去，只有少数国家支持透传，所有一般为空】
            'codec' => '8', // 编码方式， 与msg中encodeHexStr 对应
            // codec=8 Unicode 编码,  3 ISO-8859-1, 0 ASCII
            'msg' => $this->encodeHexStr(8,$message) // 编码短信内容
        );
        $uri = "http://210.51.190.233:8085/mt/mt3.ashx";
        
        $res = Http::doPost($uri,$data,15);
        if($res[0]!="-"){
            self::$cache->setValue(date("Ymd")."{$phone}",1,1440);
            $this->jssuccess("ok");
        }else{
            $this->jserror("errocod");
        }
        
        
    }
    /**
     * 阿里云短信
     * @param unknown $phone
     * @param unknown $code
     * @return boolean[]|string[]
     */
    public function aliyunSms($phone,$code){
        $res =  AliyunSms::sendSms($this->mobile_code, $this->mobile);
        if($res->Code=="ok"){
            self::$cache->setValue("sms_{$phone}",1,60);
            $status = true;
            $this->jssuccess("ok");
        }else{
            $this->jserror($res->Msg);
        }
    }
    
    
    public function encodeHexStr($dataCoding, $realStr) {
        
        if ($dataCoding == 15)
        {
            return strtoupper(bin2hex(iconv('UTF-8', 'GBK', $realStr)));
        }
        else if ($dataCoding == 3)
        {
            return strtoupper(bin2hex(iconv('UTF-8', 'ISO-8859-1', $realStr)));
        }
        else if ($dataCoding == 8)
        {
            return strtoupper(bin2hex(iconv('UTF-8', 'UCS-2BE', $realStr)));
        }
        else
        {
            return strtoupper(bin2hex(iconv('UTF-8', 'ASCII', $realStr)));
        }
    }
    
    
}
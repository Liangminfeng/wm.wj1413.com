<?php

use Qiniu\Zone;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;
use Qiniu\Auth;

final class Myqiniu
{
    private static $appkey = "bZks39r-MN2-KlD32CZgmRcoceWz5Imb4273jcPB";
    private static $appsecret = "5TNupN2zPqczX8C0mu14dP3fugJVw0pU6SvoFhxF";
    
    private static $domain = "";
    private static $region = "";
    private static $defaultBucet = "";
    
    private static $kongjian = "hk";
    
    private static $config = array(
        /* "hn"  =>  array(
            "domain"    => "http://img02.tenfutenmax.com.cn",
            "region"    => "z2",
            "bucket"    => "tgimg02",
        ),
        "tw" => array(
            "domain"    => "http://img01.tenfutenmax.com.cn",
            "region"    => "as0",
            "bucket"    => "tgimg01",
        ), */
        "hk" => array(
            "domain"    => "http://img.vmi31.com",
            "region"    => "as0",
            "bucket"    => "hk-yutui",
        ),
    );
    
    public static function getToken(){
        $config = self::$config[self::$kongjian];
        $bucket = $config["bucket"];
        
        $auth = new Auth(self::$appkey, self::$appsecret);
        $uptoken = $auth->uploadToken($bucket);
        
        return ["uptoken"=>$uptoken,"domain"=>$config["domain"],"region"=>$config["region"]];
    }
    
    /**
     * 
     * @param unknown $file
     * @param unknown $key
     * @return string[] ["state":[SUCCESS|FALSE],"msg":$msg,"url":$url]
     */
    public static function  uploadFile($file,$img_name=""){
        
        $config = self::$config[self::$kongjian];
        
        
        $auth = new Auth(self::$appkey,self::$appsecret);
        $uptoken = $auth->uploadToken($config["bucket"]);
        $uploadManager = new UploadManager();
        
        if(empty($img_name))$img_name = date("Ymd")."/".time().rand(1,100). self::get_filetype($file['name']);
        
        /* 生成上传实例对象并完成上传 */
        $ret = $uploadManager->putFile($uptoken, $img_name, $file["tmp_name"]);
     
        if(empty($ret[0])||isset($ret[0]["error"])){
            $res = array(
                "state" => false,
                "msg"   =>empty($ret[0])?$ret[1]:$ret[0]["error"]
                
            );
        }else{
            $url = $config["domain"]."/".$ret[0]["key"];
            $res = array(
                "state" => true,
                "url"   => $url
            );
        }
        
        return $res;
    }
    static function get_filetype($path)
    {
        $pos = strrpos($path, '.');
        if ($pos !== false)
        {
            return substr($path, $pos);
        }
        else
        {
            return '';
        }
    }
        public static function qiniuuploadimage($file='',$user_name){  
    // if( !$file ){
    //     return '';
    // }

    // $str = explode('.',$file);
    // $ext = $str[count($str)-1]; // 获取后缀名

    $config = self::$config[self::$kongjian];
    
    // 构建一个鉴权对象  
    $auth = new Auth(self::$appkey,self::$appsecret); 

    // 生成上传的token  
    $token = $auth->uploadToken($config["bucket"]);
    // 上传到七牛后保存的文件名  
    $key = date('Y').'/'.date('m').'/'.$user_name.date('YmdHis').mt_rand(0,9999).".jpg";  
        
    // 初始化UploadManager类  
    $bucketManager = new BucketManager($auth);  

    $ret= $bucketManager->fetch($file,$config["bucket"],$key);  

    if(empty($ret[0])||isset($ret[0]["error"])){
            $res = array(
                "state" => false,
                "msg"   =>empty($ret[0])?$ret[1]:$ret[0]["error"]
                
            );
        }else{
            $url = $config["domain"]."/".$ret[0]["key"];
            $res = array(
                "state" => true,
                "url"   => $url
            );
        }

   
        return $res;
}
}

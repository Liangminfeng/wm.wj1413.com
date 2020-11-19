<?php

/**
 * Ecshop_MPG串接模組
 * @Author Mimi
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');




/**
 * 类
 */
class mpg {

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function __construct() {

    }

     /**
     * 交易查詢 Curl 函式
     */
    function curl_work($url = "", $parameter = "") {
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => "Google Bot",
            //CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_SSL_VERIFYHOST => FALSE,
            CURLOPT_POST => "1",
            CURLOPT_POSTFIELDS => $parameter
        );
        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);
        $result = curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_errno($ch);
        curl_close($ch);

        $return_info = array(
            "url" => $url,
            "sent_parameter" => $parameter,
            "http_status" => $retcode,
            "curl_error_no" => $curl_error,
            "web_info" => $result
        );
        return $return_info;
    }


    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order, $payment) {

		$real_method = $payment['mpg_using'];

        switch ($real_method){
            case '0':
                $url = "https://ccore.spgateway.com/MPG/mpg_gateway"; //串接網址
                break;
            case '1':
                $url = "https://core.spgateway.com/MPG/mpg_gateway"; //串接網址
                break;
        }
        $url = "https://core.spgateway.com/MPG/mpg_gateway"; //串接網址
        $log_id = $order['log_id'];
        $order_id = $order['order_id'];

		//商品資訊

		$order_goods =model('Base')->model->table('order_goods')
                                ->where('order_id = '.$order_id)
                                ->select();
                                
		foreach($order_goods as $key => $value){
			$ItemArray[$key] = $value['goods_name'];
		}
		$ItemDesc = implode(",", $ItemArray);

        $MerchantID = trim($payment['mpg_merchantid']); //商店代號
        $RespondType = "String"; //回傳格式
        $TimeStamp = time(); //時間戳記
        $Version = "1.1"; //串接版本
        $MerchantOrderNo = $order_id.time(); //商店訂單編號
        $Amt = round($order['order_amount']); //訂單金額，取整數：四捨五入
        $ExpireDate = date('Ymd', strtotime('+7 day')); //繳費有效期限
        $Email = $order['email']; //付款人電子信箱
        $LoginType = '0'; //智付通會員

        $ReturnURL = return_url(basename(__FILE__, '.php')) . "&order_id=" . $order['order_id']."&log_id=" . $log_id; //返回商店網址
        $NotifyURL =return_url(basename(__FILE__, '.php')) . "&order_id=" . $order['order_id']."&log_id=" . $log_id; //付款通知網址
        $CustomerURL = "";  //商店取號網址

        $protocol = isset($_SERVER["https"]) ? 'https' : 'http';
        $ClientBackURL = $protocol."://" .$_SERVER['HTTP_HOST']; //返回商店網址

        $HashKey = trim($payment['mpg_key']);
        $HashIv = trim($payment['mpg_iv']);

        // CheckValue 產生檢查碼
        $mer_array = array(
            'MerchantID' => $MerchantID,
            'TimeStamp' => $TimeStamp,
            'MerchantOrderNo' => $MerchantOrderNo,
            'Version' => $Version,
            'Amt' => $Amt,
        );
        ksort($mer_array);
        $check_merstr = http_build_query($mer_array, '', '&');
        $CheckValue_key = "HashKey=" . $HashKey . "&" . $check_merstr . "&HashIV=" . $HashIv;
        $CheckValue = strtoupper(hash("sha256", $CheckValue_key));

        

        $str_form = "<form name='spgateway' class='paypal' style='text-align:center;' method=post action='" . $url . "' > ";
        $str_form .= "<input type='hidden' name='MerchantID' value='" . $MerchantID . "'>";
        $str_form .= "<input type='hidden' name='RespondType' value='" . $RespondType . "'>";
        $str_form .= "<input type='hidden' name='CheckValue' size='50' value='" . $CheckValue . "'>";
        $str_form .= "<input type='hidden' name='TimeStamp' value='" . $TimeStamp . "'>";
        $str_form .= "<input type='hidden' name='Version' value='" . $Version . "'>";
        $str_form .= "<input type='hidden' name='MerchantOrderNo' value='" . $MerchantOrderNo . "'>";
        $str_form .= "<input type='hidden' name='Amt' value='" . $Amt . "'>";
        $str_form .= "<input type='hidden' name='ItemDesc' value='" . $ItemDesc . "'>";
        $str_form .= "<input type='hidden' name='ExpireDate' value='" . $ExpireDate . "'>";
        $str_form .= "<input type='hidden' name='ReturnURL' value='" . $ReturnURL . "'>";
       // $str_form .= "<input type='hidden' name='NotifyURL' value='" . $NotifyURL . "'>";
        $str_form .= "<input type='hidden' name='CustomerURL' value='" . $CustomerURL . "'>";
        $str_form .= "<input type='hidden' name='ClientBackURL' value='" . $ClientBackURL . "'>";
        $str_form .= "<input type='hidden' name='Email' value='" . $Email . "'>";
        $str_form .= "<input type='hidden' name='LoginType' value='" . $LoginType . "'>";
        if($Amt>=31000){

            $str_form .= "<input type='hidden' name='InstFlag' value='3'>";
        }
        $str_form .= "<input type='hidden' name='CREDIT' value='1'>";
      
        $str_form .= "<input type='submit' value='信用卡支付'>";
        $str_form .= "</form>";

       // $str_form .= "<script language=javascript>
//document.forms.spgateway.submit();
              //        </script>";

        return $str_form;

    }



    /**
     * 處理函數：交易結束，回傳資訊
     */
    function respond() {
      
        $payment = get_payment('mpg'); //商店後台設定資料
        $order_id = $_REQUEST['MerchantOrderNo'];  //商店訂單編號
        $trade_no = $_REQUEST['TradeNo'];  //智付通交易序號
        $log_id = $_REQUEST['log_id'];   //ECShop支付單編號
        $amt = $_REQUEST['Amt']; //交易金額

        // CheckCode 檢查碼
        $check_code = array(
            "MerchantID" => $payment['mpg_merchantid'],
            "Amt" => $amt,
            "MerchantOrderNo" => $order_id,
            "TradeNo" => $trade_no,
        );
        ksort($check_code);
        $check_str = http_build_query($check_code, '', '&');
        $CheckCode = "HashIV=" . trim($payment['mpg_iv']) . "&" . $check_str . "&" . "HashKey=" . trim($payment['mpg_key']);
        $CheckCode = strtoupper(hash("sha256", $CheckCode));

        //檢查交易結果
        if ($_REQUEST['Status'] != "SUCCESS") {
            return false;
        }

        //檢查CheckCode
        if ($_REQUEST['CheckCode'] != $CheckCode) {
            return false;
        }

        //检查支付的金额是否与订单相符
        $checkAmount = "0";
        if (check_money($log_id, $amt)) {
            $checkAmount = "1";
        }

        if ($_REQUEST['Status'] == 'SUCCESS' && $checkAmount == '1' ) {
            $note = "付款完成" . date("Y-m-d H:i:s"); //備註
            order_paid($log_id, PS_PAYED, $note);
            return true;
        } else {
            return false;
        }

    }

}

?>

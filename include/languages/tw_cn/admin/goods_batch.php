<?php

/**
 * ECSHOP 商品批量上傳、修改語言檔
 * ============================================================================
 * * 版權所有 2005-2012 上海商派網路科技有限公司，並保留擁有權利。
 * 網站位址 : HTTP://www.ecshop.com ；
 * ----------------------------------------------------------------------------
 * 這不是一個自由軟體！ 您只能在不用於商業目的的前提下對程式碼進行修改和
 * 使用；不允許對程式碼以任何形式任何目的的再發佈。
 * ============================================================================
 * $Author: liubo $
 * $Id: goods_batch.php 17217 2011-01-19 06:29:08Z liubo $
 */

$_LANG['select_method'] = ' 選擇商品的方式：';
$_LANG['by_cat'] = ' 根據商品分類、品牌';
$_LANG['by_sn'] = ' 根據商品貨號';
$_LANG['select_cat'] = ' 選擇商品分類：';
$_LANG['select_brand'] = ' 選擇商品品牌：';
$_LANG['goods_list'] = ' 商品清單：';
$_LANG['src_list'] = ' 待選清單：';
$_LANG['dest_list'] = ' 選定清單：';
$_LANG['input_sn'] = ' 輸入商品貨號： <br /> （每行一個）';
$_LANG['edit_method'] = ' 編輯方式：';
$_LANG['edit_each'] = ' 逐個編輯';
$_LANG['edit_all'] = ' 統一編輯';
$_LANG['go_edit'] = ' 進入編輯';

$_LANG['notice_edit'] = ' 會員價格為 -1 表示會員價格將根據會員等級折扣比例計算';

$_LANG['goods_class'] = ' 商品類別';
$_LANG['g_class'][G_REAL] = ' 實體商品';
$_LANG['g_class'][G_CARD] = ' 虛擬卡';

$_LANG['goods_sn'] = ' 貨號';
$_LANG['goods_name'] = ' 商品名稱';
$_LANG['market_price'] = ' 市場價格';
$_LANG['shop_price'] = ' 本店價格';
$_LANG['integral'] = ' 積分購買';
$_LANG['give_integral'] = ' 贈送積分';
$_LANG['goods_number'] = ' 庫存';
$_LANG['brand'] = ' 品牌';

$_LANG['batch_edit_ok'] = ' 批量修改成功';

$_LANG['export_format'] = ' 資料格式';
$_LANG['export_ecshop'] = 'ecshop 支援資料格式';
$_LANG['export_taobao'] = ' 淘寶助理支援資料格式';
$_LANG['export_taobao46'] = ' 淘寶助理 4.6 支援資料格式';
$_LANG['export_paipai'] = ' 拍拍助理支援資料格式';
$_LANG['export_paipai3'] = ' 拍拍助理 3.0 支援資料格式';
$_LANG['goods_cat'] = ' 所屬分類：';
$_LANG['csv_file'] = ' 上傳批量 csv 檔：';
$_LANG['notice_file'] = ' （ CSV 檔中一次上傳商品數量最好不要超過 1000 ， CSV 檔案大小最好不要超過 500K. ）';
$_LANG['file_charset'] = ' 檔編碼：';
$_LANG['download_file'] = ' 下載批量 CSV 檔（ %s ）';
$_LANG['use_help'] = ' 使用說明：' .
    '<ol>' .
    '<li> 根據使用習慣，下載相應語言的 csv 檔，例如中國內地使用者下載簡體中文語言的檔，港臺使用者下載繁體語言的檔； </li>'.
    '<li> 填寫 csv 檔，可以使用 excel 或文字編輯器打開 csv 檔； <br />'.
    '碰到 「 是否精品 」 之類，填寫數位 0 或者 1 ， 0 代表 「 否 」 ， 1 代表 「 是 」 ； <br />'.
    '商品圖片和商品縮略圖請填寫帶路徑的圖片檔案名，其中路徑是相對於 [根目錄]/images/ 的路徑，例如圖片路徑為 [根目錄]/images/200610/abc.jpg ，只要填寫 200610/abc.jpg 即可； <br />'.
    '<font style="color:#FE596A;" >如果是淘寶助理格式請確保cvs編碼為在網站的編碼，如編碼不正確，可以用編輯軟體轉換編碼。 </font></li>'.
    '<li> 將填寫的商品圖片和商品縮略圖上傳到相應目錄，例如： [根目錄]/images/200610/ ； <br />'.
    '<font style="color:#FE596A;" >請首先上傳商品圖片和商品縮略圖再上傳csv檔，否則圖片無法處理。 </font></li>'.
    '<li> 選擇所上傳商品的分類以及檔編碼，上傳 csv 檔 </li>'.
    '</ol>';

$_LANG['js_languages']['please_select_goods'] = ' 請您選擇商品';
$_LANG['js_languages']['please_input_sn'] = ' 請您輸入商品貨號';
$_LANG['js_languages']['goods_cat_not_leaf'] = ' 請選擇底級分類';
$_LANG['js_languages']['please_select_cat'] = ' 請您選擇所屬分類';
$_LANG['js_languages']['please_upload_file'] = ' 請您上傳批量 csv 檔';

// 批量上傳商品的欄位
$_LANG['upload_goods']['goods_name'] = ' 商品名稱';
$_LANG['upload_goods']['goods_sn'] = ' 商品貨號';
$_LANG['upload_goods']['brand_name'] = ' 商品品牌 ' ; // 需要轉換成 brand_id
$_LANG['upload_goods']['market_price'] = ' 市場售價';
$_LANG['upload_goods']['shop_price'] = ' 本店售價';
$_LANG['upload_goods']['integral'] = ' 積分購買額度';
$_LANG['upload_goods']['original_img'] = ' 商品原始圖';
$_LANG['upload_goods']['goods_img'] = ' 商品圖片';
$_LANG['upload_goods']['goods_thumb'] = ' 商品縮略圖';
$_LANG['upload_goods']['keywords'] = ' 商品關鍵字';
$_LANG['upload_goods']['goods_brief'] = ' 簡單描述';
$_LANG['upload_goods']['goods_desc'] = ' 詳細描述';
$_LANG['upload_goods']['goods_weight'] = ' 商品重量（ kg ）';
$_LANG['upload_goods']['goods_number'] = ' 庫存數量';
$_LANG['upload_goods']['warn_number'] = ' 庫存警告數量';
$_LANG['upload_goods']['is_best'] = ' 是否精品';
$_LANG['upload_goods']['is_new'] = ' 是否新品';
$_LANG['upload_goods']['is_hot'] = ' 是否熱銷';
$_LANG['upload_goods']['is_on_sale'] = ' 是否上架';
$_LANG['upload_goods']['is_alone_sale'] = ' 能否作為普通商品銷售';
$_LANG['upload_goods']['is_real'] = ' 是否實體商品';

$_LANG['batch_upload_ok'] = ' 批量上傳成功';
$_LANG['goods_upload_confirm'] = ' 批量上傳確認';
?>
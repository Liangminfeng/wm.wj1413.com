<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ShippingBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 配送基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');
 
class GoodstransportModel extends BaseModel {

    protected $table = 'goods_transport';

    function goods_transport_info($tid = ''){
        if(!empty($tid)){
            $sql = 'SELECT * FROM ' . $this->pre . "goods_transport WHERE tid = '$tid'";
        }else{
            $sql = 'SELECT * FROM ' . $this->pre . "goods_transport";
        }

        $result = $this->query($sql);
        return $result;
    }

    function goods_express_tel($address,$goods_weight = '',$goods_number = '',$tid = ''){
        //return $tid;
        $where = '';
        if(!empty($tid)){
            $where = " WHERE tid = $tid";
        }
        $sql = 'SELECT * FROM ' . $this->pre . "goods_transport" . $where ;
        $goods_transport = $this->query($sql);
        $goods_express_tel = '';
        if(!empty($goods_transport)){
            $wheresql = 'SELECT * FROM ' . $this->pre . "goods_express_tel" . " WHERE tid = ".$goods_transport[0]['tid'];
            $goods_express_tel = $this->query($wheresql);
        }


        if(is_array($goods_express_tel)){
            if(empty($goods_number)){
                $goods_number = 1;
            }

            foreach($goods_express_tel as $k => $v){
                $goods_express_tel[$k]['configure'] = unserialize($v['configure']);
                $goods_express_tel[$k]['region_id'] = unserialize($v['region_id']);
                foreach($goods_express_tel[$k]['configure'] as $k1 => $v1){
                    $goods_express_tel[$k][$v1['name']] = $v1['value'];
                }
            }


            if(is_array($address) && !empty($address)){
                foreach($goods_express_tel as $key => $value){
                    foreach($value['region_id'] as $k => $province){
                        foreach($province as $k1 => $v1) {
                            if(!empty($v1)){
                                foreach($v1 as $k2 => $v2) {
                                    if($address[2] == $v2){       //县
                                        $info_freight = $value;

                                    }else if($address[1] == $k1){  //市
                                        $info_freight = $value;

                                    }else if($address[0] == $key){  //省
                                        $info_freight = $value;

                                    }
                                }
                            }

                        }
                    }
                }
            }


            if(!empty($info_freight)){
                foreach($info_freight['configure'] as $k => $v){
                    $info_freight[$v['name']] = $v['value'];
                }

                if ($info_freight['fee_compute_mode'] == 'by_weight') {//按重量计算
                    if ($goods_weight <= 500) {                       //500克以内费用
                        $info_freight['freight'] = $info_freight['base_fee'] - $info_freight['free_money_tel'];
                    } else {

                        if(empty($info_freight['step_fee'])){
                            $info_freight['step_fee'] = 1;
                        }

                        $info_freight['freight'] = $info_freight['base_fee'] + (ceil(($goods_weight - 500) / 500) * $info_freight['step_fee']);

                        if($info_freight['free_money_tel'] > $info_freight['freight']){   //总运费 > 免费额度
                            $info_freight['freight'] = 0;
                        }
                    }
                }

                if ($v['fee_compute_mode'] == 'by_number') { //按商品件数计算
                    $info_freight['freight'] = $info_freight['item_fee'] * $goods_number;
                    if($info_freight['free_money_tel'] > $info_freight['freight']){   //总运费 > 免费额度
                        $info_freight['freight'] = 0;
                    }
                }

                //计算附加运费
                $additional_freight = model('GoodsTransportExpress') -> transport_express_info($info_freight['shipping_id']);
                if(!empty($additional_freight)){
                    $info_freight['freight'] = $info_freight['freight'] + $additional_freight['shipping_fee'];
                }
            }

            unset($info_freight['region_id']);
        }

        return $info_freight;
    }
}

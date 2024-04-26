<?php
/**
 * Trxscan
 */

namespace app\common\library;
use think\Exception;

class Trxscan
{

    protected static $apiUrl = 'https://apiasia.tronscan.io:5566';  //主网

    /**
     * 查询转账记录*
     * https://apiasia.tronscan.io:5566/api/transfer?sort=-timestamp&count=true&limit=20&start=0&address=TRt47TVZm3mZtAbfnAGUcm4rsZnBRfvE3i
     * @param $addr 地址
     * @param $limit 条数
     * @return
     */
    public static function getTransferList($addr,$limit=50){

        $url = self::$apiUrl.'/api/transfer?sort=-timestamp&count=true&limit='.$limit.'&start=0&address='.$addr;

        $data = http($url,'','GET',[],[],[],5);

        $result = json_decode($data,true);

        if(empty($result)){
            return '';
//            throw new Exception('交易不存在');
        }
        return $result['data'];
    }

    /**
     * 查询交易记录*
     * https://apiasia.tronscan.io:5566/api/transaction?sort=-timestamp&count=true&limit=50&start=0&address=****
     * @param $addr 地址
     * @param $limit 条数
     * @return
     */
    public static function getTransactionList($addr,$limit=50){

        $url = self::$apiUrl.'/api/transaction?sort=-timestamp&count=true&limit='.$limit.'&start=0&address='.$addr;

        $data = http($url,'','GET',[],[],[],5);

        $result = json_decode($data,true);

        if(empty($result)){
            return '';
//            throw new Exception('交易不存在');
        }
        return $result['data'];
    }





    /**
     * 查询交易详细*
     * https://apiasia.tronscan.io:5566/api/transaction-info?hash=f2ff0a4a9167db66ccfcf307f737aa7b8658ca77fcfdcb79365a88cdb2af59f2
     * @param $hash hash
     * @return
     */
    public static function getTransactionInfo($hash){
        $url = self::$apiUrl.'/api/transaction-info?hash='.$hash;
        $data = http($url,'','GET',[],[],[],5);
        $result = json_decode($data,true);
        if(empty($result)){
            throw new Exception('交易不存在');
        }
        return $result;
    }

    /**
     * 查询区块详细*
     * https://apiasia.tronscan.io:5566/api/block?sort=-number&limit=1&count=true&number=38519160
     * @param $block block
     * @return
     */
    public static function getBlockInfo($block){
        $url = 'https://apiasia.tronscan.io:5566/api/block?sort=-number&limit=1&count=true&number='.$block;
        $data = http($url,'','GET',[],[],[],5);
        $result = json_decode($data,true);
        if(empty($result)){
            return [];
        }
        return $result['data'][0];
    }



}
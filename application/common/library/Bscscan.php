<?php
/**
 * Bscscan
 */

namespace app\common\library;
use app\common\library\util\HttpUtil;
use think\Exception;

class Bscscan
{
    /**
     * 获取余额
     * https://api.bscscan.com/api?module=account&action=balance&address={钱包地址}&apikey=YourApiKeyToken
     * 按地址获取“正常”交易列表
     * https://api.bscscan.com/api?module=account&action=txlist&address={钱包地址}&startblock=0&endblock=99999999&page=1&offset=10&sort=asc&apikey=YourApiKeyToken
     * 按地址获取“内部”交易列表
     * https://api.bscscan.com/api?module=account&action=txlistinternal&address={钱包地址}&startblock=0&endblock=99999999&page=1&offset=10&sort=asc&apikey=YourApiKeyToken
     * 通过事务哈希获取“内部事务”
     * https://api.bscscan.com/api?module=account&action=txlistinternal&txhash=0x4d74a6fc84d57f18b8e1dfa07ee517c4feb296d16a8353ee41adc03669982028&apikey=YourApiKeyToken
     * 按区块范围获取“内部交易”
     * https://api.bscscan.com/api?module=account&action=txlistinternal&startblock=0&endblock=9999999999&page=1&offset=10&sort=asc&apikey=YourApiKeyToken
     * 按地址获取“BEP-20 代币转移事件”列表
     * https://api.bscscan.com/api?module=account&action=tokentx&contractaddress=0x55d398326f99059fF775485246999027B3197955&address={钱包地址}&page=1&offset=5&startblock=0&endblock=999999999&sort=asc&apikey=YourApiKeyToken
     * 按地址获取“BEP-721 令牌转移事件”列表
     * https://api.bscscan.com/api?module=account&action=tokennfttx&contractaddress=0x5e74094cd416f55179dbd0e45b1a8ed030e396a1&address={钱包地址}&page=1&offset=100&startblock=0&endblock=999999999&sort=asc&apikey=YourApiKeyToken
     * 获取按地址验证的块列表
     * https://api.bscscan.com/api?module=account&action=getminedblocks&address={钱包地址}&blocktype=blocks&page=1&offset=10&apikey=YourApiKeyToken
     */

    protected static $appUrl = 'https://api.bscscan.com/';  //币安主网
    protected static $appKey = 'U477VT7MQPW72T1UIJQY3X3M2JJWYR7P63'; //adv

    /**
     * 查询交易状态
     *
     * https://api-testnet.bscscan.com/api?module=transaction&action=gettxreceiptstatus&txhash=XXXXXX&apikey=XXXXXX
     * @param $hash 交易哈希
     * @return int 1 为成功
     */
    public static function getStatus($hash){

        $url = self::$appUrl.'/api?module=transaction&action=gettxreceiptstatus&txhash='.$hash.'&apikey='.self::$appKey;

        $data = http($url,'','GET',[],[],[],5);

        $result = json_decode($data,true);

        if(empty($result)){
            throw new Exception('交易不存在');
        }

        return (int) $result['result']['status'];


    }

    /**
     * 查询交易详情
     *
     * https://api-testnet.bscscan.com/api?module=proxy&action=eth_getTransactionByHash&txhash=XXXXX&apikey=XXXXX
     * @param $hash 交易哈希
     * @return mixed
     */
    public static function getTransaction($hash){

        $url = self::$appUrl.'/api?module=proxy&action=eth_getTransactionByHash&txhash='.$hash.'&apikey='.self::$appKey;

        $data = http($url,'','GET',[],[],[],5);

        $result = json_decode($data,true);

        if(isset($result['result']['value'])){
            // 16进制转换成10进制
            $result['result']['value'] = hexdec($result['result']['value']);
            $result['result']['value'] = bcdiv($result['result']['value'],1000000000000000000,18);
        }else{
            throw new Exception('交易不存在');
        }

        return $result['result'];

    }

    /**
     * 查询交易收据
     *
     * https://api.bscscan.com/api?module=proxy&action=eth_getTransactionReceipt&txhash=XXXXXX&apikey=XXXXX
     * @param $hash 交易哈希
     * @return mixed
     */
    public static function getTransactionReceipt($hash){

        $url = self::$appUrl.'/api?module=proxy&action=eth_getTransactionReceipt&txhash='.$hash.'&apikey='.self::$appKey;
        $data = http($url,'','GET',[],[],[],5);
        $result = json_decode($data,true);
        if(isset($result['status'])){
            throw new Exception($result['message']);    //超过每秒5次调用/IP速率限制或无效API-KEY
        }
        if(!isset($result['result'])){
            $url = self::$appUrl.'/api?module=proxy&action=eth_getTransactionReceipt&txhash='.$hash.'&apikey='.self::$appKey;
            $data = http($url,'','GET',[],[],[],5);
            $result = json_decode($data,true);
            if(isset($result['status'])){
                throw new Exception($result['message']);    //超过每秒5次调用/IP速率限制或无效API-KEY
            }
        }
        if(!isset($result['result'])){
            throw new Exception('交易不存在');
        }
        if(isset($result['result']['logs']) && is_array($result['result']['logs'])){
            foreach ($result['result']['logs'] as &$vo){
                $num = gmp_init($vo['data']);
                $num = gmp_strval($num);
                $num = bcdiv($num,1000000000000000000,18);
                $vo['data'] = $num;
                $vo['blockNumber'] = hexdec($vo['blockNumber']);
                $vo['transactionIndex'] = hexdec($vo['transactionIndex']);
                $vo['logIndex'] = hexdec($vo['logIndex']);
            }
        }
        $need_hexdec_param = ['blockNumber','cumulativeGasUsed','gasUsed','status','transactionIndex','type'];
        foreach ($need_hexdec_param as $pvo){
            $result['result'][$pvo] = hexdec($result['result'][$pvo]);
        }
        return $result['result'];

    }


    /**
     * 查询交易列表数据匹配出前五条领取数据
     *
     * https://bscscan.com/address/XXXXXXX
     * @param $address
     * @return array|false
     */
    public static function queryList($address){

        try {

            $url = "https://bscscan.com/address/" . $address;
            $html = http($url);
            preg_match("/<table class=\"table table-hover\">(.*?)<\/thead>(.*?)<\/table>/is", $html, $matches);
            preg_match_all("/<tr><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td(.*?)>(.*?)<\/td><td(.*?)>(.*?)<\/td><td(.*?)>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><\/tr>/", $matches[2], $data);
            //匹配出tr
            $newdata = [];
            if(isset($data[3])) {
                foreach ($data[3] as $key => $vo) {
                    if (strpos($vo, 'With Draw Reward') !== false) {
                        for ($i = 0; $i <= 14; $i++) {
                            $newdata[$key][$i] = strip_tags($data[$i][$key], '');
                        }
                    }
                }
            }
            $newdata = array_slice($newdata, 0, 5);
            return $newdata;
        }catch (\Exception $e){

            return false;

        }

    }

    /**
     * 查询交易列表数据匹配出前五条领取数据
     *
     * https://bscscan.com/txs?a=XXXXXXX
     * @param $address
     * @return array|false
     */
    public static function querytxsList($address,$page=1){
        try {
            $url = "https://bscscan.com/txs?a=" . $address ."&ps=100&p=" . $page;
            $html = http($url);
            preg_match("/<table class=\"table table-hover\">(.*?)<\/thead>(.*?)<\/table>/is", $html, $matches);
            preg_match_all("/<tr><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td (.*?)>(.*?)<\/td><td (.*?)>(.*?)<\/td><td (.*?)>(.*?)<\/td><td>(.*?)<\/td><td (.*?)>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td (.*?)>(.*?)<\/td><td (.*?)>(.*?)<\/td><td (.*?)>(.*?)<\/td><td>(.*?)<\/td><td (.*?)>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><\/tr>/", $matches[2], $data);
            //匹配出tr
            if(!$data){
                $html = http($url);
                preg_match("/<table class=\"table table-hover\">(.*?)<\/thead>(.*?)<\/table>/is", $html, $matches);
                preg_match_all("/<tr><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td (.*?)>(.*?)<\/td><td (.*?)>(.*?)<\/td><td (.*?)>(.*?)<\/td><td>(.*?)<\/td><td (.*?)>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td (.*?)>(.*?)<\/td><td (.*?)>(.*?)<\/td><td (.*?)>(.*?)<\/td><td>(.*?)<\/td><td (.*?)>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><\/tr>/", $matches[2], $data);
            }
            $newdata = [];
            if(isset($data[3])) {
                foreach ($data[3] as $key => $vo) {
                    if (strpos($vo, 'Add Liquidity ETH') !== false) {
                        for ($i = 0; $i <= 14; $i++) {
                            $newdata[$key][$i] = strip_tags($data[$i][$key], '');
                        }
                    }
                }
            }
            $newdata = array_slice($newdata, 0, 50);
            return $newdata;
        }catch (\Exception $e){
            return false;
        }

    }
    /**
     * 按地址获取“BEP-20 代币转移事件”列表
     * https://api.bscscan.com/api?module=account&action=tokentx&contractaddress={******}&address={******}&page=1&offset=5&startblock=0&endblock=999999999&sort=asc&apikey=YourApiKeyToken
       https://api.bscscan.com/api?module=account&action=tokentx&contractaddress=0x55d398326f99059ff775485246999027b3197955&address=0x89ec6FAd217eA8655ce19554e55cc62bC7B25d57&page=1&offset=5&startblock=0&endblock=999999999&sort=asc&apikey=U4GS8KDCENTHTENHQPS4GI3X5SD81TSTK8
     *
     *
     * @param $contractaddress 要查询的合约地址
     * @param $address 要查询的账号地址
     * @param $offset 一页显示多少条记录
     * @param $startblock 开始区块
     * @param $endblock 结束区块
     */
    public static function getBscTokenTransactionList($contractaddress='0x55d398326f99059ff775485246999027b3197955',$address,$startblock,$endblock,$page=1,$offset=500,$sort='asc'){
        $url = self::$appUrl.'/api?module=account&action=tokentx&contractaddress='.$contractaddress.'&address='.$address.'&page='.$page.'&offset='.$offset.'&startblock='.$startblock.'&endblock='.$endblock.'&sort='.$sort.'&apikey='.self::$appKey;
        $data = http($url,'','GET',[],[],[],5);
        $result = json_decode($data,true);
        if(empty($result)){
            throw new Exception('不存在');
        }
//        file_put_contents(ROOT_PATH.'/public/bsc.txt', "【".date('Y-m-d H:i:s')."】->\n".$data."\n",FILE_APPEND);
        return $result['result'];
    }


    /**
     * 获取某一个地址的交易记录
     * https://www.bscscan.com/tokentxns?a={******}
     * @param $address 要查询的账号地址
     * @param $contract_address 要查询的合约地址
     * @return array|false
     */
    public static function getBscAddressTransactionList($contract_address,$address){
        try {
            $url = 'https://www.bscscan.com/tokentxns?a='.$address;
            $html = http($url);
            preg_match("/<table class=\"table table-hover\">(.*?)<\/thead>(.*?)<\/table>/is", $html, $matches);
            preg_match_all("/<a .*?href='(.*?)'.*?>/is", $matches[2], $array2);
            $newdata = [];
            for($i=0;$i<count($array2[1]);$i++)//逐个输出超链接地址
            {
                if(strpos($array2[1][$i],'/tx/') !== false){
                    $contractaddress =  explode('?',str_replace('/token/','',$array2[1][$i+2]));
                    if($contractaddress[0] == $contract_address){
                        $newdata[] = array(
                            'hash'=>str_replace('/tx/','',$array2[1][$i]),
                            'contractaddress'=>$contractaddress[0],
                        );
                    }
                }
            }
            return $newdata;
        }catch (\Exception $e){
            return false;
        }
    }
    /**
     * 获取区块信息
     * https://www.bscscan.com/block/15731145
     * @param $block int 区块
     */
    public static function getBlockInfo($block){
        $retval = HttpUtil::post(config('conf.python_ip').'/getBlockByNumber', [
            'block_num'   => $block,
        ]);
        $retval = json_decode($retval, true);
        if(isset($retval['status']) && $retval['status'] == 200){
            return ['hash'=>$retval['hash']];
        }else{
            throw new Exception('不存在');
        }
    }
    /**
     * 获取交易信息
     * https://www.bscscan.com/tx/{********}
     * @param $hash
     */
    public static function getTransactioninfo($hash){
        try {
            $url = 'https://www.bscscan.com/tx/'.$hash;
            $html = http($url);
            preg_match_all("/<a .*?href='(.*?)'.*?>/is", $html, $array2);
            if(strpos($html,'</i>Success</span>') !== false){
                $result['contractRet']='SUCCESS';
            }else{
                return '';
            }
            for($i=0;$i<count($array2[1]);$i++){//逐个输出超链接地址
                if(strpos($array2[1][$i],'/block/') !== false){
                    $result['blockNumber'] = str_replace('/block/','',$array2[1][$i]);
                }
            }
            preg_match("/<span class='text-monospace text-break d-block d-sm-inline-block' data-toggle='tooltip' title='value \(uint256 \)'>(.*?)<\/span>/is", $html, $value);
            $result['value'] = $value[1];
            preg_match("/<b>From<\/b>(.*?)title='(.*?)'(.*?)<b>To<\/b>(.*?)title='(.*?)'/is", $html, $fromandto);
            $result['from'] = $fromandto[2];
            $result['to'] = $fromandto[5];
            return $result;
        } catch (Exception $e) {
           return '';
        }



    }
}
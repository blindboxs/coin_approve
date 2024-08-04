<?php
/**
 * Ethscan
 */

namespace app\common\library\coin;
use think\Exception;

class Ethscan
{

    protected static $appUrl = 'https://etherscan.io/';  //币安主网
    protected static $appKey = ''; //adv

    /**
     * 获取交易信息是否成功
     * https://etherscan.io/tx/{********}
     * @param $hash
     */
    public static function getTransaction_ok($hash){
        try {
            $url = 'https://etherscan.io/tx/'.$hash;
            $html = http($url);
            preg_match_all("/<\/i>Status:<\/div>(.*?)<\/i>(.*?)<\/span>/is", $html, $array_pd);
            if(strpos($array_pd[2][0], 'Success') !== false){
                $result['contractRet']='SUCCESS';
            }if(strpos($array_pd[2][0], 'Fail') !== false){
                $result['contractRet'] = 'REVERT';
            }
            return $result;
        } catch (Exception $e) {
//            echo $e->getMessage();
            return '';
        }
    }

}

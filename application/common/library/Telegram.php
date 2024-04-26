<?php
/**
 * Telegram 类
 */
namespace app\common\library;

use app\common\library\util\HttpUtil;

class Telegram
{
    /** 使用例子
    $site_name = '平台名称';
    $game_id = 1;
    $game_name = '庄闲和';
    $tz_addr = '投注地址';
    $user_addr = '用户地址';
    $user_money = '2888.999 USDT';
    $t_hash = '交易哈希';
    $block_hash = '区块哈希';
    $t_money = '100';
    $tz_money = '100';
    $win_money = '195';
    $res = (new \app\common\library\Telegram)->sendMsg($token,$chat_id,$site_name,$game_id,$game_name,$tz_addr,$user_addr,$user_money,$t_hash,$block_hash,$t_money,$tz_money,$win_money);
     */
    /**
     * 发送消息
     * @param $chat_id 群组ID
     * @param $site_name 平台名称
     * @param $game_id 游戏ID
     * @param $game_name 游戏名称
     * @param $tz_addr 投注地址
     * @param $user_addr 玩家地址
     * @param $user_money 玩家余额
     * @param $t_hash 交易哈希
     * @param $block_hash 区块哈希
     * @param $t_money 转入金额
     * @param $tz_money 投注金额
     * @param $win_money 中奖金额
     */
    public function sendMsg($data){
        $site_name = $data['site_name'];
        $game_id = $data['game_id'];
        $game_name = $data['game_name'];
        $tz_addr = $data['tz_addr'];
        $user_addr = $data['user_addr'];
        $user_money = $data['user_money'];
        $t_hash = $data['t_hash'];
        $block_hash = $data['block_hash'];
        $t_money = $data['t_money'];
        $tz_money = $data['tz_money'];
        $win_money = $data['win_money'];
        $block_num = $data['block_num'];
        $content = self::msgContent($site_name,$game_id,$game_name,$tz_addr,$user_addr,$user_money,$t_hash,$block_hash,$t_money,$tz_money,$win_money,$block_num);
        $url = 'https://api.telegram.org/bot'.config('site.telegram_bot_token').'/sendMessage?parse_mode=html&chat_id='.config('site.telegram_chat_id').'&text='.$content;
        return HttpUtil::get($url);
    }
    /**
     * 消息
     * @param $site_name 平台名称
     * @param $game_id 游戏ID
     * @param $game_name 游戏名称
     * @param $tz_addr 投注地址
     * @param $user_addr 玩家地址
     * @param $user_money 玩家余额
     * @param $t_hash 交易哈希
     * @param $block_hash 区块哈希
     * @param $t_money 转入金额
     * @param $tz_money 投注金额
     * @param $win_money 中奖金额
     */
    public static function msgContent($site_name,$game_id,$game_name,$tz_addr,$user_addr,$user_money,$t_hash,$block_hash,$t_money,$tz_money,$win_money,$block_num){
        $domain = config('site.h5_url');
        $tz_addr=substr($tz_addr,0,6).'***'.substr($tz_addr,-8);
        $user_addr=substr($user_addr,0,6).'***'.substr($user_addr,-8);
        $t_hash=substr($t_hash,0,8).'***'.substr($t_hash,-11);
        $block_hash=substr($block_hash,0,8).'***'.substr($block_hash,-11);
        $html= $site_name.'%0A';
        $html= $html.'~~~~~~~~~~~~~~~~~%0A';
        $html= $html.'[游戏名称] <a href="'.$domain.'/game/'.$game_id.'">'.$game_name.'</a> 👈点击查看投注规则 %0A';
        $html= $html.'[投注地址] <a href="'.config('conf.trc_address_url').$tz_addr.'">'.$tz_addr.'</a>%0A';
        $html= $html.'[玩家地址] <a href="'.config('conf.trc_address_url').$user_addr.'">'.$user_addr.'</a>%0A';
        $html= $html.'[玩家余额] '.$user_money.'%0A';
        $html= $html.'[交易哈希] <a href="'.config('conf.trc_hash_url').$t_hash.'">'.$t_hash.'</a>%0A';
        $html= $html.'[交易区块] <a href="'.config('conf.trc_hash_url').$block_hash.'">'.$block_num.'</a>%0A';
        $html= $html.'[区块哈希] <a href="'.config('conf.trc_hash_url').$block_hash.'">'.$block_hash.'</a>%0A';
        $html= $html.'[转入金额] '.$t_money.'%0A';
        $html= $html.'[投注金额] '.$tz_money.'%0A';
        $html= $html.'[中奖金额] '.$win_money.'%0A';
        $html= $html.'————————————————%0A';
        $html= $html.'🌐 频道专属客服： <a href="tg://user?id='.config('site.telegram_user_id').'">@'.config('site.telegram').'</a> %0A';
        $html= $html.'————————————————%0A';
        $html= $html.'<a href="'.$domain.'/login">[立即登录]</a> <a href="'.$domain.'/receive">[领取福利]</a>%0A';
        $html= $html.'————————————————%0A';
        return $html;
    }

}

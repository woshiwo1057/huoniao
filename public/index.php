<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');

//$_SERVER['PATH_INFO'] = $_SERVER['REQUEST_URI' ];
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';

//开启域名部署后
/*
switch ($_SERVER['HTTP_HOST']){
    case 'www.xxxx.com':
        $model = 'index';// home模块
        $route = true;// 开启路由
        break;
    case 'admin.xxxx.com':
        $model = 'admin';// admin模块
        $route = false;// 关闭路由
        break;
}
\think\Route::bind($model ?? 'index');// 绑定当前入口文件到模块
\think\App::route($route ?? true);// 路由
\think\App::run()->send();// 执行应用
*/
<?php
namespace app\index\controller;
use think\Controller;
use think\Cookie;
use think\Session;
use think\Db;


class Identity extends Controller
{
    function index(){
        $request = request();//think助手函数
        $data_get = $request->param();//获取get与post数据
        Session::set('wb_id', $data_get['wb_id']);
        $this->redirect('/');
    }
}
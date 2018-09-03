<?php
/*
*	后台首页控制器
*	作者：YG
*	时间： 2018.7.27
*/

namespace app\admin\controller;
//use \think\controller;

class Index extends Common
{
	//登陆进来看到的画面
	public function index()
	{
		
		//var_dump($_SESSION);die;
		
		return $this->fetch('Index/index');
	} 

}

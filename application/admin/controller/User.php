<?php
/*
*	普通用户控制器
*	作者： YG
*	时间： 2018.7.28
*/
namespace  app\admin\controller;
use \think\Db;
use \think\Request;

class User extends Common
{
	//用户列表控制器
	public function index()
	{
		$user_data = Db::table('hn_user')->field('uid,nickname,age,sex,time,table,account,frozen')->where('type',0)->limit('20')->order('uid desc')->select();

		$this->assign(['user_data' => $user_data]); 
		return $this->fetch('User/index'); //载入视图
	}

	//用户详情控制器
	public function details()
	{
		$uid = Request::instance()->param('id');

		$user_data = Db::table('hn_user')->field('uid,nickname,age,sex,time,table,account,frozen,cash,mogul,level,currency,type')->where('uid',$uid)->find();
			//var_dump($user_data);die;
		$this->assign(['user_data' => $user_data]);
		return $this->fetch('User/details'); //载入视图
	}


	//冻结用户
	public function frozen_ajax()
	{
		$uid = Request::instance()->param('uid');
		
		//更改状态
		$res = Db::table('hn_user')->where('uid',$uid)->update(['frozen' => 1]);

		if($res){
			return json(['code' => 1,'msg'=> '成功']);
		}else{
			return json(['code' => 2,'msg'=> '失败']);
		}
	}
}
<?php
/*
*	后台消息管理控制器
*	作者：YG
*	时间：2018.8.15
*/
namespace app\admin\controller;
use \think\Controller;
use \think\Request;
use \think\Db;

class Msg extends Common
{

	//消息历史列表
	public function index()
	{
		$msg_data = Db::table('hn_msg')->field('id,admin_name,title,content,time')->order('id desc')->select();
		
		$this->assign(['msg_data' => $msg_data]);
		return $this->fetch('Msg/index');
	}


	//消息推送控制器
	public function add()
	{
		
		if(Request::instance()->isPost())
		{
			
			$msg_data = Request::instance()->param();
			$msg_data['time'] = time();

			//获取到管理员昵称
			$msg_data['admin_name'] = $_SESSION['admin']['admin_info']['nickname'];
			

			$res = Db::table('hn_msg')->insert($msg_data);

			if($res){
				$this->success('推送成功','Msg/index');
			}else{
				$this->error('推送失败');
			}

		}		
		return $this->fetch('Msg/add');
	}

	//消息删除控制器
	public function delete()
	{
		$id = Request::instance()->param('id');

		//根据主键删除
		$res = Db::table('hn_msg')->delete($id);

		if($res){
			$this->success('删除成功','Msg/index');
		}else{
			$this->error('删除失败','Msg/index');
		}
	}
}
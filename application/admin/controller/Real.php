<?php
/*
*	后台实名认证控制器
*	作者：YG
*	时间：2018.8.16
*/
namespace app\admin\controller;
use \think\Controller;
use \think\Request;
use \think\Db;

class Real extends Common
{
	/*
	*
	*	在陪玩师表有个 real 的字段  是来控制用户视图输出的  在后台认证成功后 更改字段值
	*
	*/
	//实名列表
	public function index()
	{
		//查询全部数据
		$real_data = Db::table('hn_acc_real')->field('id,user_id,name,time,status')->order('id desc')->select();

		$this->assign(['real_data' => $real_data]);
		return $this->fetch('Real/index');
	}

	//等待审核实名列表
	public function apply()
	{
		//查询等待审核的数据
		$real_data = Db::table('hn_acc_real')->field('id,user_id,name,time,status')->where('status',0)->order('id desc')->select();

		$this->assign(['real_data' => $real_data]);
		return $this->fetch('Real/apply');
	}

	//详情页
	public  function details()
	{	
		//获取到ID
		$id = Request::instance()->param('id');

		//查询数据
		$real_data = Db::table('hn_acc_real')->field('user_id,name,card_num,zfb,front_img,back_img')->where('id',$id)->find();

		$this->assign(['real_data' => $real_data]);
		return $this->fetch('Real/details');
	}

	//审核页
	public function examine()
	{
		//获取到ID
		$id = Request::instance()->param('id');

		//查询数据
		$real_data = Db::table('hn_acc_real')->field('id,user_id,name,card_num,zfb,front_img,back_img')->where('id',$id)->find();

		$this->assign(['real_data' => $real_data]);
		return $this->fetch('Real/examine');
	}

	//审核成功失败载入页面
	public function handle()
	{
		//获取到ID
		$data = Request::instance()->param();
		

		//更改实名认证申请表字段	
		$res = Db::table('hn_acc_real')->where('id', $data['id'])->update(['status' => 1]);
		//更改用户表字段
		$ras = Db::table('hn_accompany')->where('user_id', $data['user_id'])->update(['real' => 3]);

		if($res&&$ras){
			$this->success('成功','Real/index');
		}else{
			$this->error('失败');
		}
	}
}
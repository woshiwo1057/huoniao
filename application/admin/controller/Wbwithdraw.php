<?php
/*
*	后台网吧提现控制器
*	作者： YG
*	时间：2018.9.26
*/
namespace app\admin\controller;
use \think\Db;
use \think\Request;

class Wbwithdraw extends Common
{

	//审核列表
	public function index()
	{
		$wthdraw_data = Db::table('hn_settle')->field('id,c_id,name,alipay,money,addtime,admin_id,type')->select();

		$this->assign(['wthdraw_data' => $wthdraw_data]);
		return $this->fetch('Wbwithdraw/index');
	}

	//审核详情页
	public function details()
	{
		$id = Request::instance()->param('id');

		$wthdraw_data = Db::table('hn_settle')->field('id,name,alipay,money,type')->where('id' , $id)->find();

		$this->assign(['wthdraw_data' => $wthdraw_data]);

		return $this->fetch('Wbwithdraw/details');
	}
	//审核通过
	public function ok()
	{
		$id = Request::instance()->param('id');

		$admin_id = $_SESSION['admin']['admin_info']['id'];
		//加上审核的管理员ID
		$res = Db::table('hn_settle')->where('id' ,$id)->update(['type' => 1 , 'admin_id' => $admin_id]);

		if($res){
			$this->success('操作成功','Wbwithdraw/index');
		}else{
			$this->error('操作失败');
		}
		//return $this->fetch();
	}

	//审核拒绝
	public function no()
	{

		$data = Request::instance()->param();
//var_dump($data);die;
		$admin_id = $_SESSION['admin']['admin_info']['id'];
		//改变订单状态  拒绝说明  给余额把值加上去$data['money']  加上审核的管理员ID
		$ras = Db::table('hn_settle')->where('id' , $data['id'])->update(['type' => 3 , 'content' => $data['content'] , 'admin_id' => $admin_id]);

		
		$res = Db::table('hn_cybercafe')->where('id', $data['c_id'])->setInc('not_extract', $data['money']);		
		
		if($ras&&$res){
			$this->success('操作成功','Wbwithdraw/index');
		}else{
			$this->error('操作失败');
		}
	}
}
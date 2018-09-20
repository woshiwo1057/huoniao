<?php
/*
*	提现控制器
*	作者： YG
*	时间： 2018.9.20
*/
namespace  app\admin\controller;
use \think\Db;
use \think\Request;

class Withdraw extends Common
{
	//提现列表
	public function index()
	{
		
		$wthdraw_data = Db::table('hn_withdraw_cash')->field('id,user_id,real_name,money,zfb,admin_id,time,status')->order('id desc')->select();



		$this->assign('wthdraw_data',$wthdraw_data);
		return $this->fetch('Withdraw/index');
	}

	//同意审核
	public function ok()
	{
		
		$data = Request::instance()->param();

		//管理员ID
		$id = $_SESSION['user']['user_info']['uid'];

		//1.通过ID更改提现表对应字段
		$res = Db::table('hn_withdraw_cash')->where('id',$data['id'])->update(['status' => 2,'admin_id' => $id]);
	
		if($res){
			return json(['code' => 1 , 'msg' => '操作成功']);
		}else{
			return json(['code' => 2 , 'msg' => '操作失败']);
		}
	}

	//审核不通过
	public function no()
	{

		$data = Request::instance()->param();

		//管理员ID
		$id = $_SESSION['user']['user_info']['uid'];

		//1.查出表单详情
		$withdraw_data = Db::table('hn_withdraw_cash')->field('user_id,money')->where('id',$data['id'])->find();
		//2.给该用户余额里加钱
			//加钱数
			$withdraw_data['money'] = $withdraw_data['money']+2;
		$ras = Db::table('hn_user')->where('uid',$withdraw_data['user_id'])->setInc('cash', $withdraw_data['money']);
		//改变提现表内状态
		$res = Db::table('hn_withdraw_cash')->where('id',$data['id'])->update(['status' => 3,'admin_id' => $id]);

		if($ras&&$res){
			return json(['code' => 1 , 'msg' => '操作成功']);
		}else{
			return json(['code' => 2 , 'msg' => '操作失败']);
		}

	}
}
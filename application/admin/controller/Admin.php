<?php
/*
*	后台管理员控制器
*	作者： YG
*	时间：2018.7.28
*/
namespace app\admin\controller;
use \think\Db;
use \think\Request;

class Admin extends Common
{
	//管理员列表控制器
	public function index()
	{
		$admin_data = Db::table('hn_admin')->select();

		$this->assign(['admin_data' => $admin_data]);
		return $this->fetch('Admin/index');
	}

	//管理员添加控制器
	public function add()
	{	
		//查询权限表 
		$power_data = Db::table('hn_power')->field('id,name')->select();

		if(Request::instance()->isPost())
		{
			$admin_data = Request::instance()->param();

			$admin_data['password'] = md5($admin_data['password']);

			$res = Db::table('hn_admin')->insert($admin_data);

			if($res){
				$this->success('新增成功','Admin/index');
			}else{
				$this->error('新增失败');
			}
		}

		$this->assign(['power_data' => $power_data]);
		return $this->fetch('Admin/add');
	}

	//管理员修改控制器
	public function edit()
	{
		$id = Request::instance()->param('id');
		$admin_data = Db::table('hn_admin')->where('id',$id)->find();

		//查询权限表 
		$power_data = Db::table('hn_power')->field('id,name')->select();

		if(Request::instance()->isPost())
		{
			$data_updata = Request::instance()->param();
			
			$res = Db::table('hn_admin')->update($data_updata);

			if($res){
				$this->success('修改成功','Admin/index');
			}else{
				$this->error('修改失败,未修改任何数据');
			}
		}
		

		$this->assign(['admin_data' => $admin_data,
						'power_data' => $power_data
					]);
		return $this->fetch('Admin/edit');
	}

	//管理员删除控制器
	public function delete()
	{
		$id = Request::instance()->param('id');

		//主键删除
		$res = Db::table('hn_admin')->delete($id);

		if($res){
			$this->success('删除成功','Admin/index');
		}else{
			$this->error('删除失败','Admin/index');
		}
	} 
}  
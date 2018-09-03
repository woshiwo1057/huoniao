<?php
/*
*	权限控制器
*	作者：YG
*	时间：2018.8.1
*/
namespace app\admin\controller;
use \think\Request;
use \think\Db;

class Power extends Common
{
	//权限列表
	public function index()
	{
		$menu_arr = [];
		//查询权限表数据
		$power_data = Db::table('hn_power')->select();
		//查询菜单表数据
		$menu_data = Db::table('hn_menus')->field('id,name')->where('module','admin')->select();

		foreach ($menu_data as $k => $v)
		{
			$menu_arr[$v['id']] = $v;
		}

		foreach ($power_data as $k => $v)
		{
			$check_name = explode(',', $v['check_name']);

			$str = '';

			foreach ($check_name as $key => $val)
			{
				$str .= $menu_arr[$val]['name'].'---';
			}

			$power_data[$k]['check_name'] = rtrim($str,'---');
		}
		
		$this->assign(['power_data' => $power_data]);
		return $this->fetch('Power/index');
	}

	//权限添加
	public function add()
	{
		//查询菜单表组装数据
		$data = Db::table('hn_menus')->field('id,fid,name')->where(['fid' => 0 , 'module' => 'admin'])->select();

		foreach ($data as $k => $v) {
			$erji = Db::table('hn_menus')->field('id,name')->where(['module' => 'admin' , 'fid' => $v['id']])->select();

			$data[$k]['erji'] = $erji;
		}


		//提交数据
		if(Request::instance()->isPost())
		{
			$power_data = Request::instance()->param();
			
			//将数组转化为字符串 以 ‘,’ 隔开
			$power_data['check_name'] = implode(',', $power_data['check_name']);

			//var_dump($power_data);die;
			$res = Db::table('hn_power')->insert($power_data);

			if($res)
			{
				$this->success('添加成功','Power/index');
			}else{
				$this->error('添加失败');
			}

		}

		$this->assign(['data' => $data]);
		return  $this->fetch('Power/add');
	}

	//权限修改
	public function edit()
	{
		$id = Request::instance()->param('id');

		$power_data = Db::table('hn_power')->where('id',$id)->find();
		$power_data['check_name'] = explode(',', $power_data['check_name']);
		//查询菜单表组装数据
		$data = Db::table('hn_menus')->field('id,fid,name')->where(['fid' => 0 , 'module' => 'admin'])->select();

		foreach ($data as $k => $v) {
			$erji = Db::table('hn_menus')->field('id,name')->where(['module' => 'admin' , 'fid' => $v['id']])->select();

			$data[$k]['erji'] = $erji;
		}
		
		//提交数据
		if(Request::instance()->isPost())
		{
			$power_data = Request::instance()->param();
			
			//将数组转化为字符串 以 ‘,’ 隔开
			$power_data['check_name'] = implode(',', $power_data['check_name']);

			$res = Db::table('hn_power')->update($power_data);
			if($res)
			{
				$this->success('修改成功','Power/index');
			}else{
				$this->error('修改失败');
			}

		}

		$this->assign(['data' => $data,
					'power_data' => $power_data
			]);
		return $this->fetch('Power/edit');
	}

	//权限删除
	public function delete()
	{
		$id = Request::instance()->param('id');

		$res = Db::table('hn_power')->where('id',$id)->delete();
		if($res){
			$this->success('删除成功','Power/index');
		}else{
			$this->error('删除失败','Power/index');
		}
	}
}
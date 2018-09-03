<?php
/*
*	后台菜单控制器
*	作者：YG	
*	时间：2018.7.28
*/

namespace app\admin\controller;
use \think\Request;
use \think\Db;

class Menu extends Common
{
	//菜单列表
	public function index()
	{
		$menus_data = Db::table('hn_menus')->field('id,fid,name,module,controller,action,type')->select();


		$this->assign(['menus_data'=>$menus_data]);
		return $this->fetch('Menu/index');
	}


	//菜单添加
	public function add()
	{

		$list_data = $this->levellist();   //菜单表等级    Common里默认的参数就是查询 menus表
		
		if (Request::instance()->isPost())
		{
			$data_add = Request::instance()->param();

			$res = Db::table('hn_menus')->insert($data_add);

			if($res){
				$this->success('新增成功','Menu/index');
			}else{
				$this->error('新增失败');
			}
			
		}
		$this->assign(['list_data' => $list_data]);
		return $this->fetch('Menu/add');
	}

	//菜单修改
	public function edit()
	{
		$list_data = $this->levellist();

		$id = Request::instance()->param('id');

		$data = Db::table('hn_menus')->field('id,fid,name,module,controller,action,type')->where('id',$id)->find();

		if(Request::instance()->isPost()){

			$data_updata = Request::instance()->param();


			$res = Db::table('hn_menus')->update($data_updata);

			if($res){
				$this->success('修改成功','Menu/index');
			}else{
				$this->error('修改失败');
			}


		}

		$this->assign([
			'list_data' => $list_data,
			'data' => $data
			]);
		return $this->fetch('Menu/edit');
	}

	//菜单删除
	public function delete()
	{
		$id = Request::instance()->param('id');
		
		//根据主键删除
		$res = Db::table('hn_menus')->delete($id);

		if($res){
			$this->success('删除成功','Menu/index');
		}else{
			$this->error('删除失败','Menu/index');
		}
		

	}

	//测试控制器
	public function text()
	{
		echo 1;die;
	} 

}
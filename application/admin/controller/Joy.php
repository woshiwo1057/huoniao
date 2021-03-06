<?php
/*
*	游戏项目管理控制器
*	作者： YG
*	时间：2018.7.31
*/
namespace app\admin\controller;
use \think\Request;
use \think\Db;

class Joy extends Common
{

	//娱乐项目列表
	public function index()
	{
		$joy_data = Db::table('hn_joy')->field('id,name,joy_logo_img,joy_index_img')->select();

		$this->assign(['joy_data' => $joy_data]);
		return $this->fetch('Joy/index');
	}

	//娱乐项目添加
	public function add()
	{
		if(Request::instance()->isPost())
		{
			$joy_data = Request::instance()->param();

			$file = request()->file('cover');

			if($file)
			{
				$key = date('Y-m-d').'/'.md5(microtime()).'.jpg';

				$url_data = $this->cos($file,$key);	
				if($url_data['code'] == 0)
				{
					//成功时组装新路径
					$joy_data['joy_logo_img'] = $this->img.$key; //将此路径存入表单

				}
			}

			$files = request()->file('image');
			if($files)
			{	
				$str = time();		
				$str .= rand(1000,9999);
				$key = date('Y-m-d').'/'.md5($str).'.jpg'; //路径

				$data = $this->cos($files,$key);

				if($data['code'] == 0)
				{
					//成功时组装新路径
					$joy_data['joy_index_img'] = $this->img.$key;

				}
			}

			$res = Db::table('hn_joy')->insert($joy_data);

			if($res){

				$this->success('新增成功','Joy/index');
			}else{

				$this->error('新增失败');
			}
		}

		return $this->fetch('Joy/add');
	}

	//娱乐项目修改
	public function edit()
	{
		$id = Request::instance()->param('id');
		$joy_data = Db::table('hn_joy')->field('name,joy_logo_img,joy_index_img')->where('id',$id)->find();
		//var_dump($joy_data);die;

		if(Request::instance()->isPost())
		{
			$data_updata = Request::instance()->param();
			$file = request()->file('cover');
			if($file)
			{				
				$key = date('Y-m-d').'/'.md5(microtime()).'.jpg';

				$data = $this->cos($file,$key);

				if($data['code'] == 0)
				{
					//成功时组装新路径
					$data_updata['joy_logo_img'] = $this->img.$key;
					//用旧路径  删除cos上的图片
					$img_url =  substr($joy_data['joy_logo_img'], $this->Intercept);
					//调用删除方法 删除cos上的图片
					$this->cos_delete($img_url);
				}
			}

			$files = request()->file('image');
			if($files)
			{		
				$str = time();		
				$str .= rand(1000,9999);
				$key = date('Y-m-d').'/'.md5($str).'.jpg'; //路径

				$data = $this->cos($files,$key);

				if($data['code'] == 0)
				{
					//成功时删除原来的图片 $game_data['game_index_img']  组装新路径 
					$img_url =  substr($joy_data['joy_index_img'], $this->Intercept);
					$this->cos_delete($img_url);
					$data_updata['joy_index_img'] = $this->img.$key;
				}
			}
			
			//主键更新
			$res = Db::table('hn_joy')->update($data_updata);

			if($res){

				$this->success('修改成功','Joy/index');
			}else{

				$this->error('修改失败');
			}
		}
		$this->assign(['joy_data' => $joy_data]);
		return $this->fetch('Joy/edit');
	}

	//娱乐项目删除（全数据）
	public function delete()
	{
		$id = Request::instance()->param('id');

		$res = Db::table('hn_joy')->field('joy_logo_img,joy_index_img')->where('id',$id)->find();

		//删除图片
		if($res['joy_logo_img']){
			
			$img_url =  substr($res['joy_logo_img'], $this->Intercept);
			$this->cos_delete($img_url);
		}

		if($res['joy_index_img']){
			$img_url =  substr($res['joy_index_img'], $this->Intercept);
			$this->cos_delete($img_url);
		}
		
		//图片路径为空则表示米有图片存在   不走程序
		/*
		if(!empty($res['joy_logo_img'])){
			$url = '../../'.dirname($_SERVER['SCRIPT_NAME']).'/uploads/'.$res['joy_logo_img'];
			var_dump($url);die;
			//echo file_exists($url);die;
			if(file_exists($url)){
				unlink($url);
			}
		}
		*/
		//删除数据库数据
		//主键删除
		$res = Db::table('hn_joy')->delete($id);

		$ras = Db::table('hn_joy_grade')->where('joy_id',$id)->delete();
		if($res&&$ras){
			$this->success('删除成功','Joy/index');
		}else{
			$this->error('删除失败','Joy/index');
		}
	}

	//娱乐项目等级添加
	public function grade()
	{
		$id = Request::instance()->param('id');
		//查询游戏表的对应游戏名
		$name = Db::table('hn_joy')->field('name')->where('id',$id)->find();
		if(Request::instance()->isPost())
		{
			$grade_data = Request::instance()->param();
			
			$num = count($grade_data['type_name']);

			$data = [];
			//var_dump($grade_data);die;

			// for循环进行数组重组  
			for ($i=0; $i < $num ; $i++) { 

				$data[$i]['type_name'] = $grade_data['type_name'][$i];
				$data[$i]['joy_id'] = $grade_data['id'];
				$data[$i]['pric'] = $grade_data['pric'][$i];
			}

			$res = Db::name('hn_joy_grade')->insertAll($data);

			if($res){

				$this->success('添加成功','Joy/index');
			}else{

				$this->error('添加失败');
			}

		}

		$this->assign(['name' => $name]);
		return $this->fetch('Joy/grade');
	}
	//游戏项目等级修改
	public function grade_edit()
	{
		die;
		//不显示  像修改一样  跳转网页
		//这里通过ID 查出游戏   然后查询游戏等级表做修改
		return $this->fetch();
	}



}
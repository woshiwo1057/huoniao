<?php
/*
*	游戏项目管理控制器
*	作者： YG
*	时间：2018.7.31
*/
namespace app\admin\controller;
use \think\Request;
use \think\Db;

class Game extends Common
{

	//游戏项目列表
	public function index()
	{

		$game_data = Db::table('hn_game')->field('id,name,game_logo_img,game_index_img,sort_id')->order('sort_id esc')->select();

		$this->assign(['game_data' => $game_data]);
		return $this->fetch('Game/index');
	}

/*
	$str .= rand(1000,9999);
	$key = date('Y-m-d').'/'.md5($str).'.jpg'; //路径
*/

	//游戏项目添加
	public function add()
	{
		if(Request::instance()->isPost())
		{
			$game_data = Request::instance()->param();

			$file = request()->file('cover');

			if($file)
			{	
				$str  = time();	
				$str .= rand(1000,9999);

				$key = date('Y-m-d').'/'.md5($str).'.jpg'; //路径

				$data = $this->cos($file,$key);

				if($data['code'] == 0)
				{
					//成功时组装新路径
					$game_data['game_logo_img'] = $this->img.$key;

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
					$game_data['game_index_img'] = $this->img.$key;

				}
			}
			//var_dump($game_data);die;
			$res = Db::table('hn_game')->insert($game_data);

			if($res){
				$this->success('新增成功','Game/index');
			}else{

				$this->error('新增失败');
			}
		}
		return $this->fetch('Game/add');
	}

	//游戏项目修改
	public function edit()
	{
		$id = Request::instance()->param('id');
		$game_data = Db::table('hn_game')->field('name,game_logo_img,game_index_img,sort_id')->where('id',$id)->find();
		//var_dump($game_data);die;

		if(Request::instance()->isPost())
		{
			$data_updata = Request::instance()->param();
			$file = request()->file('cover');
			if($file)
			{	
				$str = time();	
				$str .= rand(1000,9999);
				$key = date('Y-m-d').'/'.md5($str).'.jpg'; //路径

				$data = $this->cos($file,$key);

				if($data['code'] == 0)
				{
					//成功时删除原来的图片 $game_data['game_logo_img']  组装新路径 
					//用旧路径  删除cos上的图片
					$img_url =  substr($game_data['game_logo_img'], $this->Intercept);
					$this->cos_delete($img_url);
					$data_updata['game_logo_img'] = $this->img.$key;

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
					$img_url =  substr($game_data['game_index_img'], $this->Intercept);
					$this->cos_delete($img_url);
					$data_updata['game_index_img'] = $this->img.$key;
				}
			}

			$res = Db::table('hn_game')->update($data_updata);

			if($res){

				$this->success('修改成功','Game/index');
			}else{

				$this->error('修改失败');
			}
		}
		$this->assign(['game_data' => $game_data]);
		return $this->fetch('Game/edit');
	}

	//游戏项目删除（全数据）
	public function delete()
	{
		$id = Request::instance()->param('id');

		$res = Db::table('hn_game')->field('game_logo_img,game_index_img')->where('id',$id)->find();


		//删除图片
		if($res['game_logo_img']){
			$img_url =  substr($res['game_logo_img'], $this->Intercept);
			$this->cos_delete($img_url);
		}

		if($res['game_index_img']){
			$img_url =  substr($res['game_index_img'], $this->Intercept);
			$this->cos_delete($img_url);
		}

/*
		//图片路径为空则表示米有图片存在   不走程序
		if(!empty($res['game_logo_img'])){
			$url = '../../'.dirname($_SERVER['SCRIPT_NAME']).'/uploads/'.$res['game_logo_img'];
http://uploadimg-1257183241.piccd.myqcloud.com/2018-08-29/e289076c90f37488cb91d97a6728c19b.jpg
http://uploadimg-1257183241.piccd.myqcloud.com/2018-08-29/13d18d507c4cc89db8088e6ede2a513d.jpg
			if(file_exists($url)){
				unlink($url);
			}
		}
*/
		//删除数据库数据
		//主键删除
		$res = Db::table('hn_game')->delete($id);

		$ras = Db::table('hn_game_grade')->where('game_id',$id)->delete();

		if($res){
			$this->success('删除成功','Game/index');
		}else{
			$this->error('删除失败','Game/index');
		}
	}

	//游戏项目等级添加
	public function grade()
	{
		$id = Request::instance()->param('id');
		//查询游戏表的对应游戏名
		$name = Db::table('hn_game')->field('name')->where('id',$id)->find();
		if(Request::instance()->isPost())
		{
			$grade_data = Request::instance()->param();
			
			$num = count($grade_data['type_name']);

			$data = [];
			//var_dump($grade_data);die;

			// for循环进行数组重组  
			for ($i=0; $i < $num ; $i++) { 

				$data[$i]['type_name'] = $grade_data['type_name'][$i];
				$data[$i]['game_id'] = $grade_data['id'];
				$data[$i]['pric'] = $grade_data['pric'][$i];
			}

			
			$res = Db::name('hn_game_grade')->insertAll($data);

			if($res){

				$this->success('添加成功','Game/index');
			}else{

				$this->error('添加失败');
			}

		}

		$this->assign(['name' => $name]);
		return $this->fetch('Game/grade');
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
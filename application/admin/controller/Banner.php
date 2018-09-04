<?php
/*
*	前台banner控制器
*	作者： YG
*	时间：2018.7.30
*/
namespace app\admin\controller;
use \think\Request;
use \think\Db;

class Banner extends Common
{

	//列表控制器
	public function index()
	{
		$banner_data = Db::table('hn_banner')->field('id,explain,img_url,time,status,link,link_type')->select();

		$this->assign(['banner_data' => $banner_data]);
		return $this->fetch('Banner/index');
	}

	//增加控制器
	public function add()
	{
		//进入数据提交
		if(Request::instance()->isPost())
		{	
			$banner_data = Request::instance()->param();

			$file = request()->file('cover');
			if($file){
				$key = date('Y-m-d').'/'.md5(microtime()).'.jpg';

				$url_data = $this->cos($file,$key);	
				if($url_data['code'] == 0){
					//成功
					$banner_data['img_url'] = 'http://hn-001-1256760691.picbj.myqcloud.com/'.$key; //将此路径存入表单
				}
			}
			//组装数据			
			$banner_data['time'] = time();

			//添加数据库
			$res = Db::table('hn_banner')->insert($banner_data);

			if($res){
					$this->success('添加成功','banner/index');
			}else{
					$this->error('添加失败,错误码001');
			}
			
		}
		return $this->fetch('Banner/add');
	}	

	//修改控制器
	public function edit()
	{
		//获取到ID  查询数据
		$id = Request::instance()->param('id');

		$banner_data = Db::table('hn_banner')->where('id',$id)->find();

		if(Request::instance()->isPost())
		{
			$data_updata = Request::instance()->param();
			//var_dump($data_updata);die;
			$file = request()->file('cover');
			if($file)
			{				
				$key = date('Y-m-d').'/'.md5(microtime()).'.jpg';

				$data = $this->cos($file,$key);

				if($data['code'] == 0)
				{
					//成功时组装新路径
					$data_updata['img_url'] = 'http://hn-001-1256760691.picbj.myqcloud.com/'.$key;
					//用旧路径  删除cos上的图片
					$img_url =  substr($banner_data['img_url'], 44);
					//调用删除方法 删除cos上的图片
					$this->cos_delete($img_url);
				}
			}

			$data_updata['time'] = time();//最后一次修改时间

			//更新数据库  主键更新
			$res = Db::table('hn_banner')->update($data_updata);

			if($res){

				$this->success('修改成功','Banner/index');
			}else{

				$this->error('修改失败');
			}

			
		}

		$this->assign(['banner_data' => $banner_data]);

		return $this->fetch('Banner/edit');
	}

	//删除控制器
	public function delete() 
	{

		//dirname($_SERVER['SCRIPT_NAME']).'/uploads/'   图片路径加上 $banner_data['img_url'];
		$id = Request::instance()->param('id');
		//查询出图片路径  进行删除图片
		$res = Db::table('hn_banner')->field('img_url')->where('id',$id)->find();
		
		//cos图片删除
		//用旧路径  删除cos上的图片
		$img_url =  substr($res['img_url'], 47);
		//调用删除方法 删除cos上的图片
		$this->cos_delete($img_url);
		/*
		//图片路径为空则表示米有图片存在   不走程序
		if(!empty($res['img_url'])){
			$url = '../../'.dirname($_SERVER['SCRIPT_NAME']).'/uploads/'.$res['img_url'];
			
			//echo file_exists($url);die;
			if(file_exists($url)){
				unlink($url);
			}
		}
		*/
		//删除数据库数据
		//主键删除
		$res = Db::table('hn_banner')->delete($id);

		if($res){
			$this->success('删除成功','Banner/index');
		}else{
			$this->error('删除失败','Banner/index');
		}

	}

	//下架控制器
	public function cancel()
	{
		$id = Request::instance()->param('id');

		$res = Db::table('hn_banner')->where('id',$id)->update(['status' => 0]);

		if($res){

			return json(['code' => 1 , 'msg' => '下架成功']);

		}else{

			return json(['code' => 2 , 'msg' => '操作失败']);

		}
	}
}
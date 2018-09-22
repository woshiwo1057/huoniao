<?php
/*
*   后台网吧列表
*	作者：YG
*	时间：2018.9.4
*/

namespace app\admin\controller;
use \think\Request;
use \think\Db;
//http://hn-001-1256760691.picbj.myqcloud.com/
class Netbar extends Common
{
	//网吧列表
	public function index()
	{
		$netbar_data = Db::table('hn_netbar')->field('id,c_id,name,legal_person,phone,addtime,status,examine_type')->select();


		$this->assign(['netbar_data' => $netbar_data]);
		return $this->fetch('Netbar/index');
	}

	//网吧添加
	public function add()
	{	
		if(Request::instance()->isPost())
		{
			$netbar_data = Request::instance()->param();

			$file = request()->file('cover');


			if($file)
			{	
				//网吧缩略图
				$str = time();	
				$str .= rand(1000,9999);

				$key = date('Y-m-d').'/'.md5($str).'.jpg'; //路径

				$data = $this->cos($file,$key);

				if($data['code'] == 0)
				{
					//成功时组装新路径
					$netbar_data['img'] = $tgis->img.$key;

				}
			}



			$files = request()->file('image');
			if($files)
			{		
				//网吧营业执照
				$str = time();
				$str .= rand(1000,9999);
				$key = date('Y-m-d').'/'.md5($str).'.jpg'; //路径

				$data = $this->cos($files,$key);

				if($data['code'] == 0)
				{
					//成功时组装新路径
					$netbar_data['business_license'] = $this->img.$key;
														http://hn-001-1256760691.picbj.myqcloud.com/2018-09-04/011b3ba691e177e4711b7bcc989b67fc.jpg
				}		
			}

			$res = Db::table('hn_netbar')->insert($netbar_data);

			if($res){
				$this->success('新增成功','Netbar/index');
			}else{

				$this->error('新增失败');
			}
		}

		return $this->fetch('Netbar/add');
	}

	//网吧详情
	public function details()
	{

		$id = Request::instance()->param('id');
		$netbar_data = Db::table('hn_netbar')->field('id,c_id,name,legal_person,phone,location,business_license,extract,addtime,status,examine_type')->where('id',$id)->find();

		$this->assign(['netbar_data' => $netbar_data]);
		return $this->fetch('Netbar/details');
	}

	//网吧通过审核 Ajax
	public function examine()
	{
		$id = Request::instance()->param('id');

		$res = Db::table('hn_netbar')->where('id', $id)->update(['examine_type' => 2]);

		if($res){
			return 1;
		}else{
			return 2;
		}

	}

	//网吧歇业
	public function frozen()
	{
		$id = Request::instance()->param('id');

		$res = Db::table('hn_netbar')->where('id', $id)->update(['status' => 2]);

		if($res){
			//$this->success('成功','Netbar/index');
			return json(['code' => 1,'msg'=>'成功']);
		}else{
			return json(['code' => 2,'msg'=>'失败']);
		}
	}
}  
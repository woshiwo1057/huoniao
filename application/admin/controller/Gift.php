<?php
/*
*	礼物管理控制器
*	作者 ：YG
*	时间 ：2018.8.14
*/
namespace app\admin\controller;
use \think\Request;
use \think\Db;

class Gift extends Common
{
	//礼物列表控制器
	public function index()
	{
		$gift_data = Db::table('hn_gift')->field('id,name,pice,img_url,time')->order('id desc')->select();


		$this->assign(['gift_data' => $gift_data]);
		return $this->fetch('Gift/index');
	}

	//礼物添加控制器
	public function add()
	{
		if(Request::instance()->isPost())
		{
			$gift_data = Request::instance()->param();
			$file = request()->file('cover');
						
			if($file){
				$key = date('Y-m-d').'/'.md5(microtime()).'.jpg';
				$url_data = $this->cos($file,$key);	
				if($url_data['code'] == 0){
					//成功
					$gift_data['img_url'] = 'http://hn-001-1256760691.picbj.myqcloud.com/'.$key; //将此路径存入表单
				}
			}

			$gift_data['time'] = time();  //时间戳
			$res = Db::table('hn_gift')->insert($gift_data);

			if($res){
				$this->success('新增成功','Gift/index');
			}else{
				$this->error('新增失败');
			}
		}
		return $this->fetch('Gift/add');
	}

	//礼物修改控制器
	public function edit()
	{
		$id = Request::instance()->param('id'); //获取到ID  
		$gift_data = Db::table('hn_gift')->field('id,name,pice,img_url,time')->where('id',$id)->find();//查询数据

		if(Request::instance()->isPost())
		{
			$gift_edit = Request::instance()->param();
			$file = request()->file('cover');
			if($file){
				$key = date('Y-m-d').'/'.md5(microtime()).'.jpg';
				$url_data = $this->cos($file,$key);	
				if($url_data['code'] == 0){
					//成功
					$gift_edit['img_url'] = 'http://hn-001-1256760691.picbj.myqcloud.com/'.$key; //将此路径存入表单
				}
			}

			$gift_edit['time'] = time();

			$res = Db::table('hn_gift')->update($gift_edit);

			if($res){

				$this->success('修改成功','Gift/index');
			}else{

				$this->error('修改失败');
			}


		}

		$this->assign(['gift_data' => $gift_data]);
		return $this->fetch('Gift/edit');
	}

	//礼物删除控制器
	public function delete()
	{
		$id = Request::instance()->param('id'); //获取到ID  

		$res = Db::table('hn_gift')->field('img_url')->where('id',$id)->find();

		//cos图片删除
		//用路径  删除cos上的图片
		$img_url =  substr($res['img_url'], 44);
		//调用删除方法 删除cos上的图片
		$this->cos_delete($img_url);

/*
		//图片路径为空则表示米有图片存在   不走程序
		if(!empty($res['img_url'])){
			$url = '../../'.dirname($_SERVER['SCRIPT_NAME']).'/uploads/'.$res['img_url'];

			if(file_exists($url)){
				unlink($url);
			}
		}
*/	
		//删除数据库数据
		//主键删除
		$res = Db::table('hn_gift')->delete($id);
		
		if($res){
			$this->success('删除成功','Gift/index');
		}else{
			$this->error('删除失败','Gift/index');
		}
	}

}
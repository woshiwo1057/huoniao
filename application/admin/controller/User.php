<?php
/*
*	普通用户控制器
*	作者： YG
*	时间： 2018.7.28
*/
namespace  app\admin\controller;
use \think\Db;
use \think\Request;

class User extends Common
{
	//用户列表控制器
	public function index()
	{
		$user_data = Db::table('hn_user')->field('uid,nickname,age,sex,time,table,account,frozen')->where('type',0)->limit('20')->order('uid desc')->select();

		$this->assign(['user_data' => $user_data]); 
		return $this->fetch('User/index'); //载入视图
	}

	//用户详情控制器
	public function details()
	{
		$uid = Request::instance()->param('id');

		$user_data = Db::table('hn_user')->field('uid,nickname,age,sex,time,table,account,frozen,cash,mogul,level,currency,type')->where('uid',$uid)->find();
			//var_dump($user_data);die;
		$this->assign(['user_data' => $user_data]);
		return $this->fetch('User/details'); //载入视图
	}


	//冻结用户
	public function frozen_ajax()
	{
		$uid = Request::instance()->param('uid');
		
		//更改状态
		$res = Db::table('hn_user')->where('uid',$uid)->update(['frozen' => 1]);

		if($res){
			return json(['code' => 1,'msg'=> '成功']);
		}else{
			return json(['code' => 2,'msg'=> '失败']);
		}
	}

	//头像审核列表控制器
	public function head()
	{
		/*
		* 判定该用户是否有正在审核的图片     ok
		* 用户在上传图片后数据提交至头像审核表  ok 
		* 审核成功  将此数据（用户上传的头像路径）替换至用户头像
		* 删除旧头像  删除数据
		*
		*/
		$head_data = Db::table('hn_head_examine')->field('id,uid,head_img,time,status')->order('id desc')->select();

		$this->assign(['head_data' => $head_data]);
		return $this->fetch('User/head');
	}


	//头像审核通过控制器
	public function succeed()
	{
		$uid = Request::instance()->param('uid');
		//取出头像路径
		$head_data = Db::table('hn_head_examine')->field('head_img')->where('uid',$uid)->find();

		//删除用户旧头像
		$img_url = Db::table('hn_user')->field('head_img')->where('uid',$uid)->find();
		$img_url =  substr($img_url['head_img'], $this->Intercept);
		//调用删除方法 删除图片
		$this->cos_delete($img_url);

		//将新头像路径存入用户数据表
		$res = Db::table('hn_user')->where('uid',$uid)->update(['head_img' => $head_data['head_img']]);
		
		if($res){
			//成功时删除数据库数据
			$ras = Db::table('hn_head_examine')->where('uid',$uid)->delete();
			if($ras){
				$title = '头像审核';
				$text = '您的头像审已经通过';
				$send_id = 0;
				$rec_id = $uid;
				$this->message_add($title,$text,$send_id,$rec_id);
				return json(['code' => 1,'msg'=> '成功']);
			}else{
				return json(['code' => 2,'msg'=> '失败，错误码002']);
			}
		}else{
			return json(['code' => 3,'msg'=> '失败，错误码003']);
		}
	}

	//头像审核失败控制器
	public function fail()
	{
		$uid = Request::instance()->param('uid');

		//查出图片路径  进行删除
		$img_url = Db::table('hn_head_examine')->field('head_img')->where('uid',$uid)->find();
		$img_url =  substr($img_url['head_img'], $this->Intercept);
		//调用删除方法 删除图片
		$this->cos_delete($img_url);
		//删除数据库数据
		$res = Db::table('hn_head_examine')->where('uid',$uid)->delete();

		if($res){
			$title = '头像审核';
			$text = '您的头像审核未通过，请重新上传';
			$send_id = 0;
			$rec_id = $uid;
			$this->message_add($title,$text,$send_id,$rec_id);
			return json(['code' => 1,'msg'=> '成功']);
		}else{
			return json(['code' => 2,'msg'=> '失败']);
		}
	}
}
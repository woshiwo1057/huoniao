<?php
/*
*	陪玩师审核控制器
*	作者： YG
*	时间：2018.8.17
*/
namespace app\admin\controller;
use \think\Request;
use \think\Db;

class Examine extends Common
{
	public function index()
	{
		//查询
		$examine_data = Db::table('hn_apply_acc')->field('id,user_id,hobby,status,time')->order('id desc')->select();

		$this->assign(['examine_data' => $examine_data]);
		return $this->fetch('Examine/index');
	}


	//详情页
	public function details()
	{	
		$id = Request::instance()->param('id');
		$judge = Db::table('hn_apply_acc')->field('project')->where('id',$id)->find();
		if($judge['project'] == 1)
		{
			//类型为游戏
		$examine_data = Db::table('hn_apply_acc')
						->alias('a')
						->join('hn_user u','a.user_id = u.uid')
						->join('hn_game g','a.project_id = g.id')
						->field('a.id,a.user_id,u.nickname,a.real_name,a.table,a.province,a.city,a.address,a.height,a.weight,a.duty,a.hobby,a.sexy,a.acc_type,a.project,a.project_id,g.name,a.data_url,a.explain')->where('a.id',$id)->find();

						
		}else if($judge['project'] == 2){
			//类型为娱乐
			$examine_data = Db::table('hn_apply_acc')
						->alias('a')
						->join('hn_user u','a.user_id = u.uid')
						->join('hn_joy j','a.project_id = j.id')
						->field('a.id,a.user_id,u.nickname,a.real_name,a.table,a.province,a.city,a.address,a.height,a.weight,a.duty,a.hobby,a.sexy,a.acc_type,a.project,a.project_id,j.name,a.data_url,a.explain')->where('a.id',$id)->find();

		}else{
			$this->error('数据错误,请联系开发人员');
		}


		$this->assign(['examine_data' => $examine_data]);
		return $this->fetch('Examine/details');
	}

	//申请通过
	public function ok()
	{	
		if(Request::instance()->isPost()){
			$data = Request::instance()->param();
	//var_dump($data);die;
			//修改陪玩师申请表字段
			$res = Db::table('hn_apply_acc')->where('id', $data['id'])->update(['status' => 1]);

			//修改用户表字段
			$ras = Db::table('hn_user')->where('uid', $data['user_id'])->update(['type' => 1]);

			//删除不需要的数据 将数据填陪玩师表

			unset($data['id']);
			unset($data['data_url']);
			
			$rcs = Db::table('hn_accompany')->insert($data);

			if($rcs){
				$this->success('成功','Examine/index');
			}else{
				$this->error('失败');
			}

		}
		
	}
}

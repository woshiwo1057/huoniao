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
						->field('a.id,a.user_id,u.nickname,a.real_name,a.table,a.province,a.city,a.address,a.height,a.weight,a.duty,a.hobby,a.sexy,a.acc_type,a.project,a.project_id,j.name,a.data_url,a.explain')
						->where('a.id',$id)
						->find();

		}else{
			$this->error('数据错误,请联系客服人员');
		}


		$this->assign(['examine_data' => $examine_data]);
		return $this->fetch('Examine/details');
	}

	//申请通过
	public function ok()
	{	
		if(Request::instance()->isPost()){
			$data = Request::instance()->param();
		var_dump($data);die;
			//修改陪玩师申请表字段
			$res = Db::table('hn_apply_acc')->where('id', $data['id'])->update(['status' => 1]);

			//修改用户表字段
			$ras = Db::table('hn_user')->where('uid', $data['user_id'])->update(['type' => 1]);

			//删除不需要的数据 将数据填陪玩师表  填入陪玩师服务项目表

			unset($data['id']);
			unset($data['data_url']);

			if($data['project'] == 1){
				$project_name = Db::table('hn_game')->field('name')->where('project_id')->find();
			}else if($data['project'] == 2){
				$project_name = Db::table('hn_joy')->field('name')->where('project_id')->find();
			}else{
				$this->error('数据错误,请联系客服人员');
			}
			
			//$rcs = Db::table('hn_accompany')->insert($data); //填入陪玩师表

			//组装数据填入服务项目表
			//$wow['project_name']  $wow['project_id']  $wow['time'] = time()    $wow['status'] = 1  $wow['explain'] = '第一次开通'
			//$wow['project_name'] = $project['name']

			$wow = [];
			$wow['uid'] = $data['user_id']; //用户ID(陪玩师)
			$wow['project'] = $data['project']; //项目类型  1：游戏  2：娱乐
			$wow['project_id'] = $data['project_id']; //服务内容（具体服务项目） 
			$wow['project_name'] = $project['name']; //服务名字
			$wow['explain'] = '第一次开通'; //简介
			$wow['status'] = 1; //状态  成功
			$wow['time'] = time(); //时间
			//$wow['pric']  默认为8 
			//需要游戏 单价/小时  需要订单总数  需要订单总时间吗？
			//800 24*365 == 8760

			if($rcs){
				Db::table('hn_apply_project')->insert($wow);
				$this->success('成功','Examine/index');
			}else{
				$this->error('失败');
			}

		}
		
	}

	//陪玩师服务项目管理列表
	public  function service()
	{
		$apply_data = Db::table('hn_apply_project')->field('id,uid,project_name,status,time')->order('id desc')->select();
		

		$this->assign(['apply_data' => $apply_data]);
		return $this->fetch();
	}

	//陪玩师服务项目管理审核
	public function inspect()
	{	
		$id = Request::instance()->param('id');
		$apply_data = Db::table('hn_apply_project')->field('uid,project_name,img_url,explain,time')->where('id',$id)->find();

		if(Request::instance()->isPost())
		{
			$data = Request::instance()->param();

			if($data['type'] == 1){
				//审核通过    改变状态   改变time

				$res = Db::table('hn_apply_project')->where('uid', $data['uid'])->update(['status' => 1,'time' => time()]);
				if($res){
					return 1;
				}else{
					return 2;
				}
			}else if($data['type'] == 2){
				//审核不通过  改变状态  改变时间
				$res = Db::table('hn_apply_project')->where('uid', $data['uid'])->update(['status' => 2,'time' => time()]);
				if($res){
					return 1;
				}else{
					return 2;
				}
			}

		}
		$this->assign(['apply_data' => $apply_data]);
		return $this->fetch();
	}	
}

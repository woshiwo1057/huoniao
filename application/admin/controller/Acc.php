<?php
/*
*	后台陪玩师管理控制器
*	作者： YG
*	时间：2018.7.28
*/

namespace app\admin\controller;
use \think\Db;
use \think\Request;

class Acc extends Common
{
	//列表控制器
	public function index()
	{
		$acc_data = Db::table('hn_accompany')->field('id,user_id,real_name,balance,pice,discount,convertible,gift_exchange')->limit(20)->order('id desc')
				->select();

		$this->assign(['acc_data' => $acc_data]);
		return $this->fetch('Acc/index');
	}

	//修改 折扣  兑换比例 控制器
	public function edit()
	{
		$id = Request::instance()->param('id');
												//   陪玩师折扣  兑换比例
		$acc_data = Db::table('hn_accompany')->field('discount,convertible')->where('id',$id)->find();
		//var_dump($acc_data);die;

		if(Request::instance()->isPost())
		{
			$data = Request::instance()->param();

			//包含主键的修改法
			$res = Db::table('hn_accompany')->update($data);

			if($res){
				$this->success('成功','Acc/index');
			}else{
				$this->error('失败');
			}
		}

		$this->assign(['acc_data' => $acc_data]);
		return $this->fetch('Acc/edit');
	}

	//陪玩师详情
	public function details()
	{
		//获取到陪玩师ID
		$id = Request::instance()->param('id');

		$acc_data =	Db::table('hn_accompany')
						->alias('a')
						->join('hn_user u','u.uid = a.user_id')
						->field('u.uid,u.nickname,u.head_img,u.age,u.sex,u.penguin,u.account,a.real_name,a.table,a.discount,a.convertible,a.real,a.gift_exchange')
						->where('a.id',$id)
						->find();
						//var_dump($acc_data);die;

		$this->assign(['acc_data' => $acc_data]);
		return $this->fetch('Acc/details');
	}


	//修改折扣与分成比例控制器
	public function edit_ajax()
	{
		$data = Request::instance()->param();

		if(isset($data['discount']))
		{
			
			$res = Db::table('hn_accompany')->where('user_id',$data['id'])->setField('discount', $data['discount']);
			if($res){
				return json(['code' => 1, 'msg' => '成功']);
			}else{
				return json(['code' => 2, 'msg' => '失败']);
			}
			

		}else if(isset($data['convertible'])){
			//修改订单金额兑换余额比例
			$res = Db::table('hn_accompany')->where('user_id',$data['id'])->setField('convertible', $data['convertible']);
			if($res){
				return json(['code' => 1, 'msg' => '成功']);
			}else{
				return json(['code' => 2, 'msg' => '失败']);
			}
		}else if(isset($data['gift_exchange'])){
			//修改礼物金额兑换余额比例
			$res = Db::table('hn_accompany')->where('user_id',$data['id'])->setField('gift_exchange', $data['gift_exchange']);

			if($res){
				return json(['code' => 1, 'msg' => '成功']);
			}else{
				return json(['code' => 2, 'msg' => '失败']);
			}

		}else{
			return json(['code' => 3, 'msg' => '数据错误']);
		}
	}

	//相册控制器
	public function album()
	{
		$user_id = Request::instance()->param('id');
		
		$album_data = Db::table('hn_user_album')->field('img_url')->where('user_id',$user_id)->select();

		$this->assign(['album_data' => $album_data]);
		return $this->fetch('Acc/album');

	}

	//入驻信息控制器
	public function apply()
	{
		$user_id = Request::instance()->param('id');
		$apply_data = Db::table('hn_apply_acc')
						->field('user_id,real_name,table,province,city,address,height,weight,duty,hobby,sexy,project,project_id,data_url,explain,acc_type')->where('user_id',$user_id)->find();
		//根据服务项目查表 查服务内容
		if($apply_data['project'] == 1){
			$apply_data['project_name'] = Db::table('hn_game')->field('name')->where('id',$apply_data['project_id'])->find();
		}else if($apply_data['project'] == 2){
			$apply_data['project_name'] = Db::table('hn_joy')->field('name')->where('id',$apply_data['project_id'])->find();
		}
		//var_dump($apply_data);die;
		$this->assign(['apply_data' => $apply_data]);
		return $this->fetch('Acc/apply');
	}
}
<?php
/*
*	后台订单控制器
*	作者：YG
*	时间：2018.8.2
*/
namespace app\admin\controller;
use \think\Db;
use \think\Request;

class Order extends Common
{

	//订单列表
	public function index()
	{

		$order_data = Db::table('hn_order')->field('id,number,acc_id,user_id,service,length_time,time,over_time,status,price')->order('id desc')->select();

		foreach ($order_data as $k => $v) {
			//用户
			$order_data[$k]['user_id'] = Db::table('hn_user')->field('nickname')->where('uid',$v['user_id'])->find();
			//陪玩师
			$order_data[$k]['acc_id'] = Db::table('hn_user')->field('nickname')->where('uid',$v['acc_id'])->find();
			
			//var_dump($order_data);die;
		}


		$this->assign(['order_data' => $order_data]);
		return $this->fetch('Order/index');
	}

} 
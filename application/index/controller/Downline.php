<?php
/*
*	线下模块
*	作者： YG
*	时间：2018.10.22
*/

namespace app\index\controller;
use \think\Db; 
use \think\Request;

class Downline extends  Common
{

	//下线首页
	public function index()
	{
		//echo 1;die;

		//首先需要查出所有的开启了线下服务的陪玩师的数据  
		$acc_data = Db::table('hn_user')
				->alias('u')
                ->join('hn_accompany a','u.uid = a.user_id')
                ->
                group('u.uid')
                ->field('u.uid,u.nickname,u.head_img,a.table,a.hot,a.pice,a.order_num,a.city')
                ->where(['a.down' => 2])
                ->limit('0,12')
                ->select();
                //var_dump($acc_data);die;
		//查询出服务项目
			//查询游戏项目
		$game_data = Db::table('hn_game')->field('id,name,game_logo_img')->order('sort_id esc')->select();
		
			//查询娱乐项目
		$joy_data = Db::table('hn_joy')->field('id,name,joy_logo_img')->select();

		$game_id = 0;
		$this->assign([	
						'acc_data' => $acc_data,
						'game_data' => $game_data,
						'joy_data' => $joy_data,
						'game_id'  => $game_id
					 ]);

		return $this->fetch('Downline/index');
	}

	//筛选
	public function screen()
	{

	}

}
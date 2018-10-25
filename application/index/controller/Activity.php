<?php
/*
*	前台特价专区
*	作者：YG
*	时间：2018.8.20
*/
namespace app\index\controller;
use \think\Controller;
use \think\Request;
use \think\Db;

class Activity extends Common
{	
	//特价专区首页
	public function index()
	{
		//查询游戏项目
		$game_data = Db::table('hn_game')->field('id,name,game_logo_img')->select();
		
		//查询娱乐项目
		$joy_data = Db::table('hn_joy')->field('id,name,joy_logo_img')->select();
						
		//查询一哈陪玩师列表  然后输出
		$acc_data = Db::table('hn_user')
            ->alias('u')
            ->join('hn_accompany a','u.uid = a.user_id')
            ->join('hn_apply_project p','u.uid = p.uid')
            -> group('u.uid')
            ->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.discount,a.city')
            ->where(['p.pric'=>8,'a.up'=>2])
            ->limit('0,12')

            ->select();
		
		$this->assign([
				'game_data' => $game_data,
				'joy_data' => $joy_data,

                
				'acc_data' => $acc_data
			]);
		
		return $this->fetch('Activity/index');
	}

    //筛选
    public function screen(){
        $data = Request::instance()->param();
        $where['p.pric'] = 8;
        $where['a.up'] = 2;
        if($data['project'] != 0){
            $where['p.project'] = $data['project'];
        }
        if($data['sex'] != 0){
            $where['u.sex'] = $data['sex'];
        }
        if($data['porjectLv'] != 0){
            $where['p.project_grade'] = $data['porjectLv'];
        }
        if($data['project_id'] != 0){
            $where['p.project_id'] = $data['project_id'];
        }
        $acc_data = Db::table('hn_user')
            ->alias('u')
            ->join('hn_accompany a','u.uid = a.user_id')
            ->join('hn_apply_project p','u.uid = p.uid')
            -> group('u.uid')
            ->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.discount,a.city')
            ->where($where)
            //->where('a.discount','<',1)
            ->limit('0,12')
            ->select();
        return json($acc_data);
    }


}
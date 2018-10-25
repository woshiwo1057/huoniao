<?php
/*
*	前台陪玩列表控制器
*	作者：YG
*	时间：2018.7.23
*/
namespace app\index\controller;
use \think\Controller;
use \think\Request;
use \think\Db;



class Acc extends Common
{
	//陪玩列表首页
	public function index()
	{
		//var_dump($_SESSION);die;
		//查询游戏项目
		$game_data = Db::table('hn_game')->field('id,name,game_logo_img')->order('sort_id esc')->select();
		
		//查询娱乐项目
		$joy_data = Db::table('hn_joy')->field('id,name,joy_logo_img')->select();
		
		$game_id = Request::instance()->param('game_id');
		
		if($game_id){
			$acc_data = Db::table('hn_user')->alias('u')
                ->join('hn_accompany a','u.uid = a.user_id')
                ->group('u.uid')
                ->field('u.uid,u.nickname,u.head_img,a.table,a.hot,a.pice,a.order_num,a.city')
                ->where(['a.project_id' => $game_id , 'a.up' => 2])
                ->limit('0,12')
                ->select();
		}else{
			//查询一哈陪玩师列表  然后输出
            $game_id = 0;
			$acc_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->group('u.uid')->field('u.uid,u.nickname,u.head_img,a.table,a.hot,a.pice,a.order_num,a.city')->where([ 'a.up' => 2])->select();
		}
//var_dump($acc_data);die;
		$this->assign([
				'game_data' => $game_data,
				'joy_data' => $joy_data,
				'acc_data' => $acc_data,
				'game_id' => $game_id,

			]);
		return $this->fetch('Acc/index');
	}

	public function search()
	{

		$data = Request::instance()->param();
		$data['search'] = empty($data['search'])?'':$data['search'];
		//var_dump($data['search']); die;//这是用户搜索的东西

		if(is_numeric($data['search']))
		{

			//当为整形的时候查询ID
			
			/*
			$where = [
							['uid', 'like', "%".$data['search']."%"]
			];
			*/

			$search_data = Db::table('hn_user')->where('uid','like',$data['search'].'%')->where('type',1)->limit('15')->select();

		}else{
			//不为整形的时候查询nickname
		
			$search_data = Db::table('hn_user')->where('nickname','like',$data['search'].'%')->where('type',1)->limit('15')->select();
			
		}
		
		//var_dump($search_data);die;
		$this->assign(['search_data' => $search_data]);
		return $this->fetch('Acc/search');

	}

	//等级Ajax 
	public function garde_ajax()
	{
		$data = Request::instance()->param();
		
		if($data['project'] == 1){
			//游戏项目
			$garde_data = Db::table('hn_game_grade')->field('id,type_name')->where('game_id',$data['project_id'])->select();
			return  json($garde_data);
		}else if($data['project'] == 2){
			//娱乐项目
			$garde_data = Db::table('hn_joy_grade')->field('id,type_name')->where('joy_id',$data['project_id'])->select();
			return  json($garde_data);
		}
	}

    //筛选Ajax
    public function screen(){
        $data = Request::instance()->param();
        if($data['project'] != 0){
            $where['p.project'] = $data['project'];
        }
        //$where['a.pice'] = 8;
        $where['a.up'] = 2;
        if($data['sex'] != 0){
            $where['u.sex'] = $data['sex'];
        }
        if($data['porjectLv'] != 0){
            $where['p.project_grade'] = $data['porjectLv'];
        }
        if($data['project_id'] != 0){
            $where['p.project_id'] = $data['project_id'];
        }
        if($data['type'] == 1){
            $order = 'u.uid asc';
        }
        if($data['type'] == 2){
            $order = 'a.hot desc';
        }
        if($data['type'] == 3){
            $order = 'p.pric asc';
        }
        if($data['type'] == 4){//线下
        	$order = 'u.uid asc';
            $wb_id  = $_SESSION['think']['wb_id'].',';
            $where['a.acc_type'] = 3;
            $where['a.wb_list'] = ['like',"%$wb_id%"];

        }

        $acc_data = Db::table('hn_user')
            ->alias('u')
            ->join('hn_accompany a','u.uid = a.user_id')
            ->join('hn_apply_project p','u.uid = p.uid')
            ->group('u.uid')
            ->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.discount,a.city')
            ->where($where)
            ->order($order)
            //->where('a.discount','<',1)
            ->limit('0,15')
            ->select();
        return json($acc_data);
    }

}

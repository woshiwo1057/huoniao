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
                ->group('u.uid')
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


		//线下排行榜
    		//1.陪玩师人气榜
    			//①周榜
    			$hot_day_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.level,a.hot_day')->limit(6)->select();
    			if($hot_day_data != NULL){
                    $type = 'hot_day';
                    $hot_day_data = $this->ranking($hot_day_data,$type);
                }

    			//②总榜
    			$hot_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.level,a.hot')->limit(6)->select();
    			if($hot_data != NULL){
                    $type = 'hot';
                    $hot_data = $this->ranking($hot_data,$type);
                }
                
    		//2.陪玩师大神榜（线下）
    			//①周榜
    			$okami_day_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.level,a.down_order_week')->limit(6)->select();
    			if($okami_day_data != NULL){
                    $type = 'down_order_week';
                    $okami_day_data = $this->ranking($okami_day_data,$type);
                }

    			//②总榜	
    			$okami_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.level,a.down_order')->limit(6)->select();
    			if($okami_data != NULL){
                    $type = 'down_order';
                    $okami_data = $this->ranking($okami_data,$type);
                }
              
    		//3.用户贡献榜
    			//①周榜
    			$mogul_day_data = Db::table('hn_user')->field('uid,nickname,head_img,mogul_day,level')->limit(6)->select();
    			if($mogul_day_data != NULL){
                    $type = 'mogul_day';
                    $mogul_day_data = $this->ranking($mogul_day_data,$type);
                }
                
    			//②总榜
    			$mogul_data = Db::table('hn_user')->field('uid,nickname,head_img,mogul,level')->limit(6)->select();
    			if($mogul_data != NULL){
                    $type = 'mogul';
                    $mogul_data = $this->ranking($mogul_data,$type);
                }
		$this->assign([
				'game_data' => $game_data,
				'joy_data' => $joy_data,
				'acc_data' => $acc_data,
				'game_id' => $game_id,

				//排行榜
				//人气榜数据   
              	'hot_day_data'=> $hot_day_data,      
    		    'hot_data'=> $hot_data,
	    		//大神榜数据
	    		'okami_day_data'=> $okami_day_data,
	    		'okami_data'=> $okami_data,
	    		//贡献榜数据
	    		'mogul_day_data'=> $mogul_day_data,
	    	    'mogul_data'=> $mogul_data
			]);

		return $this->fetch('Downline/index');
	}

     //筛选Ajax
    public function screen(){
        $data = Request::instance()->param();
        if($data['project'] != 0){
            $where['p.project'] = $data['project'];
        }
   
        $where['a.down'] = 2;

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


	 //排行榜Ajax   周榜
    public function ranking_list_week()
    {

        $data = Request::instance()->param();

        $wow = Db::table('hn_apply_project')
                ->alias('p')
                ->join('hn_user u' , 'p.uid = u.uid')
                ->join('hn_accompany a' , 'p.uid = a.user_id')
                ->field('u.uid,u.nickname,u.head_img,u.level,p.down_order_week')
                ->where(['p.project' => $data['project'] , 'p.project_id' => $data['project_id']])
                ->order('down_order_week desc')
                ->limit(6)
                ->select();

        //var_dump($wow);die;

        return $wow;
    
    }


    //排行榜Ajax 总榜
    public function ranking_list()
    {

        $data = Request::instance()->param();

        $wow = Db::table('hn_apply_project')
                ->alias('p')
                ->join('hn_user u' , 'p.uid = u.uid')
                ->join('hn_accompany a' , 'p.uid = a.user_id')
                ->field('u.uid,u.nickname,u.head_img,u.level,p.down_order')
                ->where(['p.project' => $data['project'] , 'p.project_id' => $data['project_id']])
                ->order('down_order desc')
                ->limit(6)
                ->select();

        return $wow;
    }



}
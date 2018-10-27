<?php
/*
*	前台线上陪玩列表控制器
*	作者：YG
*	时间：2018.7.23
*/
namespace app\index\controller;
use \think\Controller;
use \think\Request;
use \think\Db;



class Acc extends Common
{
	//线上陪玩列表首页
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

		//线上排行榜
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
                
    		//2.陪玩师大神榜（线上）
    			//①周榜
    			$okami_day_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.level,a.okami_day')->limit(6)->select();
    			if($okami_day_data != NULL){
                    $type = 'okami_day';
                    $okami_day_data = $this->ranking($okami_day_data,$type);
                }

    			//②总榜	
    			$okami_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.level,a.okami')->limit(6)->select();
    			if($okami_data != NULL){
                    $type = 'okami';
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


//var_dump($okami_day_data);die;
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

    //排行榜Ajax
    public function ranking_list_week()
    {

    	$data = Request::instance()->param();

    	//var_dump($data);die;
    	/*
    	$okami_day_data = Db::table('hn_user')
    					->alias('u')
    					->join('hn_accompany a','u.uid = a.user_id')
    					->field('u.uid,u.nickname,u.head_img,u.level,a.okami_day')
    					->limit(6)
    					->select();
    					*/

    	//down_order_week  周线上单数
    	//down_order 总单数  
    	//$where = '';
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

    	//思路
    	/*
			1.查陪玩师项目表关联陪玩师表，关联用户表
			2.查询条件：
				陪玩师项目表的项目ID
				用户表性别
			3.区分线上与线下的订单数

			
    	*/





		/*
			排行榜订单量排名思路
			首页（index/index）
			显示包括线上线下的所有订单总数  从陪玩师表中查取
 

			线上页面
			显示所有线上的订单总数排名 从陪玩师表中查取

				选择某游戏后的订单数排名  从陪玩师项目表中查取

			线下页面
			显示所有线下的订单总数排名 从陪玩师表中查取

				选择某游戏后的订单数排名  从陪玩师姓项目表中查取
		*/
    
    }


    //排行榜Ajax 总榜
    public function ranking_list()
    {

    	$data = Request::instance()->param();

    	//down_order_week  周线上单数
    	//down_order 总单数  
    	//$where = '';
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

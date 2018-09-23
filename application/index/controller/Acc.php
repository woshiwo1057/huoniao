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
		//查询游戏项目
		$game_data = Db::table('hn_game')->field('id,name,game_logo_img')->order('sort_id esc')->select();
		
		//查询娱乐项目
		$joy_data = Db::table('hn_joy')->field('id,name,joy_logo_img')->select();
		
		$game_id = Request::instance()->param('game_id');
		
		if($game_id){
			$acc_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,a.table,a.hot,a.pice,a.order_num,a.city')->where(['a.status'=>1,'a.project_id' => $game_id , 'a.up' => 2])->limit('0,12')->select();
		}else{
			//查询一哈陪玩师列表  然后输出
			$acc_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,a.table,a.hot,a.pice,a.order_num,a.city')->where(['a.status' => 1 , 'a.up' => 2])->limit('0,12')->select();
		}
//var_dump($acc_data);die;
		$this->assign([
				'game_data' => $game_data,
				'joy_data' => $joy_data,
				'acc_data' => $acc_data
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
	public function screen()
	{
	
		$data = Request::instance()->param();
		//var_dump($data);die;
		//$data['project'];    $data['type'] == 1全部  $data['type'] == 2魅力  $data['type'] == 3价格  $data['type'] == 4线下 
		//$data['project_id'];
		if($data['type'] == 1){
		//全部
			if($data['project'] == 1&&$data['project_id'] == 1){

				//用户有没有选择性别
				if($data['sex'] == 0){
					//没有选择性别
					$acc_data = Db::table('hn_user')
								->alias('u')
								->join('hn_accompany a','u.uid = a.user_id')
								->join('hn_apply_project p','u.uid = p.uid')
								->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
								->where(['a.status'=> 1,'a.up' => 2,])
								->limit('0,15')
								->select();
							
					// var_dump($acc_data);die;
								/*
					if(!empty($acc_data)){
						$key = 'nickname';
						$acc_data = $this->out_repeat($acc_data,$key);
					}
						*/
					
					$data_arr = [];
					$aa = [];
					foreach ($acc_data as $k => $v){
						if(in_array($v['uid'], $aa)){
							continue;
						}
						$data_arr[$k] = $v;
						$aa[] = $v['uid'];
					}

						//var_dump($data_arr);die;      //continue跳出本次循环
					return json($data_arr);
				}else{
					//选择了性别
					
					$acc_data = Db::table('hn_user')
								->alias('u')
								->join('hn_accompany a','u.uid = a.user_id')
								->join('hn_apply_project p','u.uid = p.uid')
								->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
								->where(['a.status'=> 1,'a.up' => 2,'u.sex' => $data['sex']])
								->limit('0,15')
								->select();



					$data_arr = [];
					$aa = [];
					foreach ($acc_data as $k => $v){
						if(in_array($v['uid'], $aa)){
							continue;
						}
						$data_arr[$k] = $v;
						$aa[] = $v['uid'];
					}		

					return json($data_arr);
				}

			}else{

				if($data['porjectLv'] == 0){

					if($data['sex'] == 0){
						//没有选择性别
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.up' => 2,'p.project' => $data['project'],'p.project_id' => $data['project_id']])
									->limit('0,15')
									->select();
								
						return json($acc_data);
					}else{
						//选择了性别
						
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.up' => 2,'p.project' => $data['project'],'p.project_id' => $data['project_id'],'u.sex' => $data['sex']])
									->limit('0,15')
									->select();
										
						
						return json($acc_data);
					}

				}else{

					if($data['sex'] == 0){
					//没有选择性别
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.up' => 2,'p.project' => $data['project'],'p.project_id' => $data['project_id'],'p.project_grade' => $data['porjectLv']])
									->limit('0,15')
									->select();
								
						
						return json($acc_data);
					}else{
						//选择了性别
						
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.up' => 2,'p.project' => $data['project'],'p.project_id' => $data['project_id'],'p.project_grade' => $data['porjectLv'] ,'u.sex' => $data['sex']])
									->limit('0,15')
									->select();
										
						
						return json($acc_data);
					}
				}
				
				//        'p.project_grade' => $data['porjectLv']    
				//用户有没有选择性别
				
			}
		}else if($data['type'] == 2){
			//魅力
			if($data['project'] == 1&&$data['project_id'] == 1){

					if($data['sex'] == 0){
						//没有选择性别
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.up' => 2])
									->order('a.hot desc')
									->limit('0,15')
									->select();
								
						
						$data_arr = [];
						$aa = [];
						foreach ($acc_data as $k => $v){
							if(in_array($v['uid'], $aa)){
								continue;
							}
							$data_arr[$k] = $v;
							$aa[] = $v['uid'];
						}

						return json($data_arr);
					}else{
						//选择了性别
						
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.up' => 2,'u.sex' => $data['sex']])
									->order('a.hot desc')
									->limit('0,15')
									->select();
								
						
						$data_arr = [];
						$aa = [];
						foreach ($acc_data as $k => $v){
							if(in_array($v['uid'], $aa)){
								continue;
							}
							$data_arr[$k] = $v;
							$aa[] = $v['uid'];
						}
					
						return json($data_arr);
					}

			}else{

				if($data['porjectLv'] == 0){

					if($data['sex'] == 0){
						//没有选择性别
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.up' => 2,'p.project' => $data['project'],'p.project_id' => $data['project_id']])
									->order('a.hot desc')
									->limit('0,15')
									->select();
								
						
						return json($acc_data);
					}else{
						//选择了性别
						
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.up' => 2,'p.project' => $data['project'],'p.project_id' => $data['project_id'],'u.sex' => $data['sex']])
									->order('a.hot desc')
									->limit('0,15')
									->select();
								
						
						$data_arr = [];
						$aa = [];
						foreach ($acc_data as $k => $v){
							if(in_array($v['uid'], $aa)){
								continue;
							}
							$data_arr[$k] = $v;
							$aa[] = $v['uid'];
						}
					
						return json($data_arr);
					}

				}else{

					if($data['sex'] == 0){
						//没有选择性别
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.up' => 2,'p.project' => $data['project'],'p.project_id' => $data['project_id'],'p.project_grade' => $data['porjectLv']])
									->order('a.hot desc')
									->limit('0,15')
									->select();
								
						
						return json($acc_data);
					}else{
						//选择了性别
						
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.up' => 2,'p.project' => $data['project'],'p.project_id' => $data['project_id'],'p.project_grade' => $data['porjectLv'],'u.sex' => $data['sex']])
									->order('a.hot desc')
									->limit('0,15')
									->select();
								

						
						return json($acc_data);
					}


				}
					
			}

		}else if($data['type'] == 3){
			//价格
			if($data['project'] == 1&&$data['project_id'] == 1){
				if($data['sex'] == 0){
					//没有选择性别
					$acc_data = Db::table('hn_user')
								->alias('u')
								->join('hn_accompany a','u.uid = a.user_id')
								->join('hn_apply_project p','u.uid = p.uid')
								->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
								->where(['a.status'=> 1,'a.up' => 2])
								->order('p.pric asc')
								->limit('0,15')
								->select();
					
					$data_arr = [];
						$aa = [];
						foreach ($acc_data as $k => $v){
							if(in_array($v['uid'], $aa)){
								continue;
							}
							$data_arr[$k] = $v;
							$aa[] = $v['uid'];
						}
					
						return json($data_arr);
				}else{
					//选择了性别
					
					$acc_data = Db::table('hn_user')
								->alias('u')
								->join('hn_accompany a','u.uid = a.user_id')
								->join('hn_apply_project p','u.uid = p.uid')
								->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
								->where(['a.status'=> 1,'a.up' => 2 ,'u.sex' => $data['sex']])
								->order('p.pric asc')
								->limit('0,15')
								->select();
							

					
					$data_arr = [];
						$aa = [];
						foreach ($acc_data as $k => $v){
							if(in_array($v['uid'], $aa)){
								continue;
							}
							$data_arr[$k] = $v;
							$aa[] = $v['uid'];
						}
					
						return json($data_arr);
				}

			}else{

				if($data['porjectLv'] == 0){
					if($data['sex'] == 0){
						//没有选择性别
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.up' => 2,'p.project' => $data['project'],'p.project_id' => $data['project_id']])
									->order('p.pric asc')
									->limit('0,15')
									->select();
								
						
						return json($acc_data);
					}else{
						//选择了性别
						
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.up' => 2,'p.project' => $data['project'],'p.project_id' => $data['project_id'],'u.sex' => $data['sex']])
									->order('p.pric asc')
									->limit('0,15')
									->select();
								

						
						return json($acc_data);
					}

				}else{

					if($data['sex'] == 0){
						//没有选择性别
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.up' => 2,'p.project' => $data['project'],'p.project_id' => $data['project_id'],'p.project_grade' => $data['porjectLv']])
									->order('p.pric asc')
									->limit('0,15')
									->select();
								
						
						return json($acc_data);
					}else{
						//选择了性别
						
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.up' => 2,'p.project' => $data['project'],'p.project_id' => $data['project_id'],'p.project_grade' => $data['porjectLv'],'u.sex' => $data['sex']])
									->order('p.pric asc')
									->limit('0,15')
									->select();
								

						
						return json($acc_data);
					}
				}

					
			}
		}else if($data['type'] == 4){

			//取出网吧ID
			if(isset($_SESSION['think']['wb_id'])){
				$wb_id  = '24,';//$_SESSION['think']['wb_id'].','; // ->where('a.list'['name' => ['like',"%$content%"]])	

				if($data['sex'] == 0){
					if($data['project'] == 1){
					//没有选择性别
						if($data['project'] == 1&&$data['project_id'] == 1){
							$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.down' => 2,'a.acc_type' => 3,'p.project' => 1])
									->where(['a.wb_list' => ['like',"%$wb_id%"]])
									->limit('0,15')
									->select();

						}else{
							$acc_data = Db::table('hn_user')
										->alias('u')
										->join('hn_accompany a','u.uid = a.user_id')
										->join('hn_apply_project p','u.uid = p.uid')
										->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
										->where(['a.status'=> 1,'a.down' => 2,'p.project' => 1,'p.project_id' => $data['project_id'],'a.acc_type' => 3])
										->where(['a.wb_list' => ['like',"%$wb_id%"]])					
										->limit('0,15')
										->select();
						}	
						
							
						return json($acc_data);
					}else{
						//选择了性别
						
							$acc_data = Db::table('hn_user')
										->alias('u')
										->join('hn_accompany a','u.uid = a.user_id')
										->join('hn_apply_project p','u.uid = p.uid')
										->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
										->where(['a.status'=> 1,'a.down' => 2,'p.project' => 1,'p.project_id' => $data['project_id'],'u.sex' => $data['sex'],'a.acc_type' => 3])
										->where(['a.wb_list' => ['like',"%$wb_id%"]])
										->limit('0,15')
										->select();
				
						
							return json($acc_data);
					}
				}





			}else{
			//线下  acc_type == 3  这里写的已经没用了  因为不在网吧  无法看到下线按钮
				if($data['sex'] == 0){
					if($data['project'] == 1){
					//没有选择性别
						if($data['project'] == 1&&$data['project_id'] == 1){
							$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.down' => 2,'a.acc_type' => 3,'p.project' => 1])
											
									->limit('0,15')
									->select();

						}else{
							$acc_data = Db::table('hn_user')
										->alias('u')
										->join('hn_accompany a','u.uid = a.user_id')
										->join('hn_apply_project p','u.uid = p.uid')
										->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
										->where(['a.status'=> 1,'a.down' => 2,'p.project' => 1,'p.project_id' => $data['project_id'],'a.acc_type' => 3])					
										->limit('0,15')
										->select();
						}	
						
						return json($acc_data);
					}else{
						//选择了性别
						
						$acc_data = Db::table('hn_user')
									->alias('u')
									->join('hn_accompany a','u.uid = a.user_id')
									->join('hn_apply_project p','u.uid = p.uid')
									->field('u.uid,u.nickname,u.head_img,a.table,a.hot,p.pric,a.order_num,a.city')
									->where(['a.status'=> 1,'a.down' => 2,'p.project' => 1,'p.project_id' => $data['project_id'],'u.sex' => $data['sex'],'a.acc_type' => 3])
									->limit('0,15')
									->select();
			
						
						return json($acc_data);
					}
				}
			}

		}
	}
}

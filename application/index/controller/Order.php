<?php
/*
*	前台创建订单
*	作者：YG
*	时间：2018.8.20
*/
namespace app\index\controller;
use \think\Controller;
use \think\Request;
use \think\Db;

class Order extends Common
{
	public function __construct()
	{
		parent:: __construct(); //集成父级构造函数、
		//session_start();
		if(!isset($_SESSION['user']['user_info']))
		{
			$this->redirect('Index/index',['qingnidenglu' => 1]);
		}

	}

	public function index()
	{
		
		//获取到服务数据
	    $data = Request::instance()->param();
	    //$data['id']陪玩师ID    $data['project']服务类型   $data['project_id']服务内容
	    
	    //查询服务项目表与用户表（陪玩师）
	    $acc_data = Db::table('hn_apply_project')
		     		->alias('p')
		     		->join('hn_user u' , 'u.uid = p.uid')
		     		->field('u.head_img,u.nickname,p.uid,p.project,p.project_id,p.project_name,p.pric')
		     		->where(['p.uid' => $data['id'],'project' => $data['project'],'project_id' => $data['project_id'],'status' => 1])
		     		->find();
		//var_dump($acc_data);die;
		//获取到用户ID
		$user_id = $_SESSION['user']['user_info']['uid'];
		//查出用户的QQ与手机号
		$user_data['phone'] = $_SESSION['user']['user_info']['account'];
		$user_data['qq'] = $_SESSION['user']['user_info']['penguin'];

		//查优惠券
		$coupon_data = Db::table('hn_coupon_user')
					->alias('cu')
					->join('hn_coupon c' , 'cu.cid = c.id')
					->field('c.id,c.name,c.discount')
					->where(['uid' => $user_id,'status' => 1])->find();
	
		$this->assign([ 'acc_data' => $acc_data,
					   'user_data' => $user_data,
					 'coupon_data' => $coupon_data]);
	
		return $this->fetch('Order/index');
	}


	//确认订单
	public function confirm_order()
	{
		//获取到用户ID
		$user_id = $_SESSION['user']['user_info']['uid']; 
		$data = Request::instance()->param();

		if($data['acc_id'] == $user_id){
			return json(['code'=>8,'msg'=>'自己不能给自己下订单呦']);
		}

		//容错处理
		empty($data['explain'])?'':$data['explain'];
		empty($data['wechat'])?'':$data['wechat'];
		$data['price'] = $data['money']*$data['length_time'];//订单总价 = 小时价*时长
			
		if($data['type'] == 2){
			//判断陪玩师是否在线 $data['acc_id']   hn_accompany   status == 1 在线 
			$status = Db::table('hn_accompany')->field('status,up')->where('user_id',$data['acc_id'])->find();
			if($status['status'] != 1){
				return json(['code'=>8,'msg'=>'陪玩师未在线，请稍后再来']);
			}

			if($status['up'] == 1){
				return json(['code'=>5,'msg'=>'该陪玩师尚未开启线上接单']);
			}

			//判断陪玩师是否有订单为完结 查 hn_order 表     当前时间戳 > (接单时间+订单时长*60*60)
			$order_acc = Db::table('hn_order')->field('status,length_time,single_time,price')->where('acc_id',$data['acc_id'])->where('status','<',4)->where('status','>',1)->find();

			if($order_acc){
				if($order_acc['price']==8){
					if (time()-$order_acc['single_time']<=300) {
						return json(['code'=>9,'msg'=>'该陪玩师尚有订单未完成，暂时无法接单，稍后再来吧']);
					}
				}else{
					if (time()>$order_acc['length_time']*60*60+$order_acc['single_time']) {
						return json(['code'=>9,'msg'=>'该陪玩师尚有订单未完成，暂时无法接单，稍后再来吧']);
					}
				}
					
			}
			//判断之前是否有未完成的订单   status < 4
			/*
			$order_data = Db::table('hn_order')->field('status')->where(['user_id' => $user_id])->where('status','<',4)->where('status','>',0)->find();
			if($order_data != ''){
						return json(['code'=>5,'msg'=>'请完成之前的订单后再来下单，谢谢']);
			}
			*/
			//这里是微信支付  因为没有回调  所以 die
			//echo '现在不能微信支付';die;
			//价钱
				/*1.判断是否使用了优惠券
				if($data['coupon_type'] != 0){
					//查出优惠券优惠额度，用总价减去
					$discount = Db::table('hn_coupon')->field('discount')->where('id',$data['coupon_type'])->find();
					$data['price'] = $data['price']-$discount['discount'];
				}
			*///$data['price'];
			
			//订单号
			$data['number'] = time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
			//将订单号存入session  做查询跳转使用
			$_SESSION['user']['user_info']['order_number'] = $data['number'];
			$code = $this->wechat_pay($user_id,$data['number'],$data['price']);

			

			if($code){
				//填表
				unset($data['money']);
				unset($data['type']);
				$data['user_id'] = $user_id;
				$data['time'] = time();
				$data['status'] = 0;

				//判断是否在合作网吧
				if(isset($_SESSION['think']['wb_id'])){
					$data['wb_id'] = $_SESSION['think']['wb_id'];
				
				}
				
				

				/************************************************
				                                 这里放在回调里写，需要判断是否支付成功
				//判断是否在合作网吧
				if(isset($_SESSION['think']['wb_id'])){
					$data['wb_id'] = $_SESSION['think']['wb_id'];

					//给网吧钱
						//1.通过 $data['wb_id'] 查hn_netbar(网吧入驻表) 联查hn_cybercafe(网吧管理员表) 查ratio（分成比例） $data['price']*ratio
					$wb_data = Db::table('hn_netbar')
						->alias('n')
						->join('hn_cybercafe c' , 'n.c_id = c.id')
						->field('c.ratio,c.id')
						->where(['n.id' => $data['wb_id']])
						->find();

					$wb_money = $data['price']*$wb_data['ratio'];
						//2.给网吧表 extract 添值
					Db::table('hn_netbar')->where('id',$data['wb_id'])->setInc('extract',$wb_money);
						//3.给网吧管理员表 extract not_extract添值
					Db::table('hn_cybercafe')->where('id',$wb_data['id'])->setInc('extract',$wb_money);
					Db::table('hn_cybercafe')->where('id',$wb_data['id'])->setInc('not_extract',$wb_money);
				}
				**************************************************/
				//查出陪玩师订单分成比例，计算陪玩师应得的钱数
				$convertible = Db::table('hn_accompany')->field('convertible')->where('user_id',$data['acc_id'])->find(); //比例
				$data['really_price'] = $convertible['convertible']*$data['price'];//实际到达陪玩师账户的金钱数

				$ras = Db::table('hn_order')->insert($data);
				return json(['code' => 6,'msg' => $code]);
			}else{
				return json(['code' =>7,'msg' =>'失败，错误码007']);
			}


			
		}else{
			//判断陪玩师是否在线 $data['acc_id']   hn_accompany   status == 1 在线 
			$status = Db::table('hn_accompany')->field('status,up')->where('user_id',$data['acc_id'])->find();
			if($status['status'] != 1){
				return json(['code'=>8,'msg'=>'陪玩师未在线，请稍后再来']);
			}

			if($status['up'] == 1){
				return json(['code'=>5,'msg'=>'该陪玩师尚未开启线上接单']);
			}

			//判断陪玩师是否有订单为完结 查 hn_order 表
			$order_acc = Db::table('hn_order')->field('status,length_time,single_time,price')->where('acc_id',$data['acc_id'])->where('status','<',4)->where('status','>',1)->find();

			if($order_acc){
				if($order_acc['price']==8){
					if (time()-$order_acc['single_time']<=300) {
						return json(['code'=>9,'msg'=>'该陪玩师尚有订单未完成，暂时无法接单，稍后再来吧']);
					}
				}else{
					if (time()>$order_acc['length_time']*60*60+$order_acc['single_time']) {
						return json(['code'=>9,'msg'=>'该陪玩师尚有订单未完成，暂时无法接单，稍后再来吧']);
					}
				}
					
			}
		
			//获取到用户数据   在这里要判断一下  是不是来自余额的支付方式  扣除账户余额
			$user_data = Db::table('hn_user')->field('cash')->where('uid',$user_id)->find();

			//判断是否使用了优惠券
			if($data['coupon_type'] != 0){
				//查出优惠券优惠额度，用总价减去
				$discount = Db::table('hn_coupon')->field('discount')->where('id',$data['coupon_type'])->find();
				$data['pric'] = $data['price']-$discount['discount'];
			}else{
				$data['pric'] = $data['price'];
			}



			if($data['pric']>$user_data['cash']){
				return json(['code'=>1,'msg'=>'余额不足，请更换支付方式']);
			}
			//判断之前是否有未完成的订单   status < 4
			//$order_data = Db::table('hn_order')->field('status')->where(['user_id' => $user_id])->where('status','<',4)->where('status','>',0)->find();
			//var_dump($order_data);die;
			$order_data = 1;
			if($order_data != 1){
				return json(['code'=>5,'msg'=>'请完成之前的订单后再来下单，谢谢，错误码999']);
			}else{

				//减去用户余额
				$res = Db::table('hn_user')->where('uid', $user_id)->setDec('cash', $data['pric']);
				unset($data['money']);
				unset($data['type']);
				if($res){
					//这里组装订单表数据  填表
					$data['number'] = time() . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
					//将订单号存入session 进行回调判断
					//$_SESSION['user']['user_info']['numner'] =  $data['number'];
					$data['user_id'] = $user_id;
					$data['time'] = time();
					$data['status'] = 1;
					//查出陪玩师订单分成比例，计算陪玩师应得的钱数
					$convertible = Db::table('hn_accompany')->field('convertible')->where('user_id',$data['acc_id'])->find(); //比例
					$data['really_price'] = $convertible['convertible']*$data['price'];//实际到达陪玩师账户的金钱数
					//echo 1;die;
					//优惠券处理
					if($data['price'] != $data['pric']){
						Db::table('hn_coupon_user')->where(['uid' => $user_id , 'cid' => $data['coupon_type']])->delete();
					}
					if(isset($_SESSION['think']['wb_id'])){
						$data['wb_id'] = $_SESSION['think']['wb_id'];
					}
					/*
					//判断是否在合作网吧
					if(isset($_SESSION['think']['wb_id'])){
						$data['wb_id'] = $_SESSION['think']['wb_id'];

						//给网吧钱
							//1.通过 $data['wb_id'] 查hn_netbar(网吧入驻表) 联查hn_cybercafe(网吧管理员表) 查ratio（分成比例） $data['price']*ratio
						$wb_data = Db::table('hn_netbar')
							->alias('n')
							->join('hn_cybercafe c' , 'n.c_id = c.id')
							->field('c.ratio,c.id')
							->where(['n.id' => $data['wb_id']])
							->find();

						$wb_money = $data['price']*$wb_data['ratio'];
							//2.给网吧表 extract 添值
						Db::table('hn_netbar')->where('id',$data['wb_id'])->setInc('extract',$wb_money);
							//3.给网吧管理员表 extract not_extract添值
						Db::table('hn_cybercafe')->where('id',$wb_data['id'])->setInc('extract',$wb_money);
						Db::table('hn_cybercafe')->where('id',$wb_data['id'])->setInc('not_extract',$wb_money);
					}*/

					//用户实际付的钱
					$data['price'] = $data['pric'];
					unset($data['pric']); 

					$ras = Db::table('hn_order')->insert($data);
					if($ras){		
						$title = '您有新的订单';
						$text = '快去接单吧！！！！！！！';
						$send_id = 0;
						$rec_id = $data['acc_id'];
						$this->message_add($title,$text,$send_id,$rec_id);

						//给陪玩师发短信
						//1.陪玩师电话  2.短信内容  3. 6
						$phone = Db::table('hn_user')->field('account,nickname')->where('uid' , $data['acc_id'])->find();
						$dat = [
				           	'time' =>'8',
				            'name' => $phone['nickname']
				            	           
				        ];
						
						$this->sendCms($phone['account'],$dat,6);

						return 	json(['code' => 4 ,'msg'=>'成功']);
					}else{
						return 	json(['code' => 3 ,'msg'=>'支付失败，错误码003']);
					}
				}else{
					return 	json(['code' => 2 ,'msg'=>'支付失败，错误码002']);
				}
			}
			//判断到这里结束
						
		}


	}


	//声音鉴定订单
	public function voice()
	{
		//获取到用户ID
		$user_id = $_SESSION['user']['user_info']['uid']; 
		$data = Request::instance()->param();

		if($data['acc_id'] == $user_id){
			return json(['code'=>8,'msg'=>'自己不能给自己下订单呦']);
		}
		
		//容错处理
		empty($data['explain'])?'':$data['explain'];
		empty($data['wechat'])?'':$data['wechat'];
		$data['price'] = $data['money']*$data['length_time'];//订单总价 = 小时价*时长
		

		if($data['type'] == 2){

			//判断陪玩师是否在线 $data['acc_id']   hn_accompany   status == 1 在线 
			$status = Db::table('hn_accompany')->field('status,up')->where('user_id',$data['acc_id'])->find();
			if($status['status'] != 1){
				return json(['code'=>8,'msg'=>'陪玩师未在线，请稍后再来']);
			}

			if($status['up'] == 1){
				return json(['code'=>5,'msg'=>'该陪玩师尚未开启线上接单']);
			}

			//判断陪玩师是否有订单为完结 查 hn_order 表
			/*
			$order_acc = Db::table('hn_order')->field('status')->where('acc_id',$data['acc_id'])->where('status','<',4)->where('status','>',1)->find();
			if($order_acc['status']<4&&$order_acc['status']>1){
						return json(['code'=>9,'msg'=>'该陪玩师尚有订单未完成，暂时无法接单，稍后再来吧']);
			}
			*/
			//判断之前是否有未完成的订单   status < 4
			/*
			$order_data = Db::table('hn_order')->field('status')->where(['user_id' => $user_id])->where('status','<',4)->where('status','>',0)->find();
			if($order_data != ''){
						return json(['code'=>5,'msg'=>'请完成之前的订单后再来下单，谢谢']);
			}
			*/
			//这里是微信支付  因为没有回调  所以 die
			//echo '现在不能微信支付';die;
			//价钱
				/*1.判断是否使用了优惠券
				if($data['coupon_type'] != 0){
					//查出优惠券优惠额度，用总价减去
					$discount = Db::table('hn_coupon')->field('discount')->where('id',$data['coupon_type'])->find();
					$data['price'] = $data['price']-$discount['discount'];
				}
			*///$data['price'];
			
			//订单号
			$data['number'] = time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
			//将订单号存入session  做查询跳转使用
			$_SESSION['user']['user_info']['order_number'] = $data['number'];
			//var_dump($_SESSION['user']['user_info']['order_number']);die;
			$code = $this->wechat_pay($user_id,$data['number'],$data['price']);

			

			if($code){
				//填表
				unset($data['money']);
				unset($data['type']);
				$data['user_id'] = $user_id;
				$data['time'] = time();
				$data['status'] = 0;

				//判断是否在合作网吧
				/*
				if(isset($_SESSION['think']['wb_id'])){
					$data['wb_id'] = $_SESSION['think']['wb_id'];
				}
				*/

				//查出陪玩师订单分成比例，计算陪玩师应得的钱数
				$convertible = Db::table('hn_accompany')->field('convertible')->where('user_id',$data['acc_id'])->find(); //比例
				$data['really_price'] = $convertible['convertible']*$data['price'];//实际到达陪玩师账户的金钱数
				
				$ras = Db::table('hn_order')->insert($data);
				return json(['code' => 6,'msg' => $code]);
			}else{
				return json(['code' =>7,'msg' =>'失败，错误码007']);
			}


			
		}else{

			//判断陪玩师是否在线 $data['acc_id']   hn_accompany   status == 1 在线 
			$status = Db::table('hn_accompany')->field('status,up')->where('user_id',$data['acc_id'])->find();
			if($status['status'] != 1){
				return json(['code'=>8,'msg'=>'陪玩师未在线，请稍后再来']);
			}

			if($status['up'] == 1){
				return json(['code'=>5,'msg'=>'该陪玩师尚未开启线上接单']);
			}

			//判断陪玩师是否有订单为完结 查 hn_order 表
			/*
			$order_acc = Db::table('hn_order')->field('status')->where('acc_id',$data['acc_id'])->where('status','<',4)->where('status','>',1)->find();
			if($order_acc != ''){
						return json(['code'=>9,'msg'=>'该陪玩师尚有订单未完成，暂时无法接单，稍后再来吧']);
			}
			*/
			//获取到用户数据   在这里要判断一下  是不是来自余额的支付方式  扣除账户余额
			$user_data = Db::table('hn_user')->field('cash')->where('uid',$user_id)->find();

			//判断是否使用了优惠券
			if($data['coupon_type'] != 0){
				//查出优惠券优惠额度，用总价减去
				$discount = Db::table('hn_coupon')->field('discount')->where('id',$data['coupon_type'])->find();
				$data['pric'] = $data['price']-$discount['discount'];
			}else{
				$data['pric'] = $data['price'];
			}



			if($data['pric']>$user_data['cash']){
				return json(['code'=>1,'msg'=>'余额不足，请更换支付方式']);
			}
			//判断之前是否有未完成的订单   status < 4
			//$order_data = Db::table('hn_order')->field('status')->where(['user_id' => $user_id])->where('status','<',4)->where('status','>',0)->find();
			//var_dump($order_data);die;
			$order_data = 1;
			if($order_data != 1){
				return json(['code'=>5,'msg'=>'请完成之前的订单后再来下单，谢谢，错误码999']);
			}else{

				//减去用户余额
				
					$res = Db::table('hn_user')->where('uid', $user_id)->setDec('cash', $data['pric']);
					
					//优惠券处理
					if($data['price'] != $data['pric']){
						Db::table('hn_coupon_user')->where(['uid' => $user_id , 'cid' => $data['coupon_type']])->delete();
					}					

					//用户实际付的钱
					$data['price'] = $data['pric'];

					//组装数据存入order表
					//查出陪玩师订单分成比例，计算陪玩师应得的钱数
					$wow = [];
					$convertible = Db::table('hn_accompany')->field('convertible')->where('user_id',$data['acc_id'])->find(); //比例
					$wow['really_price'] = $convertible['convertible']*$data['price'];//实际到达陪玩师账户的金钱数
					$wow['number'] = time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//订单号
					$wow['acc_id'] = $data['acc_id'];
					$wow['user_id'] = $user_id;
					$wow['service'] = $data['service'];
					$wow['length_time'] = $data['length_time'];
					$wow['explain'] = $data['explain'];
					$wow['phone'] = $data['phone'];
					$wow['qq'] = $data['qq'];
					$wow['wechat'] = $data['wechat'];
					$wow['price'] = $data['price'];
					$wow['time'] = time();
					$wow['status'] = 1;
					Db::table('hn_order')->insert($wow);
	

				
					$code = $this->voice_order($data['acc_id'],$data['price']);	

					
					$qq = Db::table('hn_user')->field('penguin')->where('uid',$data['acc_id'])->find();
					if($code == 1){
						//给陪玩师发短信
						//1.陪玩师电话  2.短信内容  3. 6
						$phone = Db::table('hn_user')->field('account,nickname')->where('uid' , $data['acc_id'])->find();
						$dat = [
				           	'time' =>'8',
				            'name' => $phone['nickname']
				            	           
				        ];
						
						$this->sendCms($phone['account'],$dat,6);

						$mom = ['code' => '1',
									'msg' => '支付成功，联系陪玩师,qq:'.$qq['penguin']
								];
						return json($mom);
					}else{
						return json(['code' =>2 , 'msg' =>'失败']);

					}				
				
			}
		}
	}

	//线下服务订单
	public function offline()
	{
		//获取到服务数据
	    $data = Request::instance()->param();
	    //$data['id']陪玩师ID    $data['project']服务类型   $data['project_id']服务内容
	    //查询服务项目表与用户表（陪玩师）
	    $acc_data = Db::table('hn_apply_project')
		     		->alias('p')
		     		->join('hn_user u' , 'u.uid = p.uid')
		     		->field('u.head_img,u.nickname,p.uid,p.project,p.project_id,p.project_name')
		     		->where(['p.uid' => $data['id'],'project' => $data['project'],'project_id' => $data['project_id'],'status' => 1])
		     		->find();
		//var_dump($acc_data);die;
		//获取到用户ID
		$user_id = $_SESSION['user']['user_info']['uid'];
		//查出用户的QQ与手机号
		$user_data['phone'] = $_SESSION['user']['user_info']['account'];
		$user_data['qq'] = $_SESSION['user']['user_info']['penguin'];

		//查优惠券
		$coupon_data = Db::table('hn_coupon_user')
					->alias('cu')
					->join('hn_coupon c' , 'cu.cid = c.id')
					->field('c.id,c.name,c.discount')
					->where(['uid' => $user_id,'status' => 1])->find();
		
		$this->assign([ 'acc_data' => $acc_data,
					   'user_data' => $user_data,
					 'coupon_data' => $coupon_data]);
	

		return $this->fetch('Order/offline');
	}

	//线下服务确认订单
	public function confirm_offline()
	{
		//获取到用户ID
		$user_id = $_SESSION['user']['user_info']['uid']; 
		$data = Request::instance()->param();
		
		if($data['acc_id'] == $user_id){
			return json(['code'=>8,'msg'=>'自己不能给自己下订单呦']);
		}
		//容错处理
		empty($data['explain'])?'':$data['explain'];
		empty($data['wechat'])?'':$data['wechat'];
		$data['price'] = $data['money']*$data['length_time'];//订单总价 = 小时价*时长
			
		if($data['type'] == 2){
			//判断陪玩师是否在线 $data['acc_id']   hn_accompany   status == 1 在线 
			$status = Db::table('hn_accompany')->field('status,down')->where('user_id',$data['acc_id'])->find();
			if($status['status'] != 1){
				return json(['code'=>8,'msg'=>'陪玩师未在线，请稍后再来']);
			}

			if($status['down'] == 1){
				return json(['code'=>5,'msg'=>'该陪玩师尚未开启线下接单']);
			}


			//判断陪玩师是否有订单为完结 查 hn_order 表
			$order_acc = Db::table('hn_order')->field('status')->where('acc_id',$data['acc_id'])->where('status','<',4)->where('status','>',1)->find();
			if($order_acc['status']<4&&$order_acc['status']>1){
						return json(['code'=>9,'msg'=>'该陪玩师尚有订单未完成，暂时无法接单，稍后再来吧']);
			}
			//判断之前是否有未完成的订单   status < 4
			/*
			$order_data = Db::table('hn_order')->field('status')->where(['user_id' => $user_id])->where('status','<',4)->where('status','>',0)->find();
			if($order_data != ''){
						return json(['code'=>5,'msg'=>'请完成之前的订单后再来下单，谢谢']);
			}
			*/
			//这里是微信支付  因为没有回调  所以 die
			//echo '现在不能微信支付';die;
			//价钱
				/*1.判断是否使用了优惠券
				if($data['coupon_type'] != 0){
					//查出优惠券优惠额度，用总价减去
					$discount = Db::table('hn_coupon')->field('discount')->where('id',$data['coupon_type'])->find();
					$data['price'] = $data['price']-$discount['discount'];
				}
			*///$data['price'];
			
			//订单号
			$data['number'] = time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
			//将订单号存入session  做查询跳转使用
			$_SESSION['user']['user_info']['order_number'] = $data['number'];
			$code = $this->wechat_pay($user_id,$data['number'],$data['price']);

			//查出陪玩师订单分成比例，计算陪玩师应得的钱数
			$convertible = Db::table('hn_accompany')->field('convertible')->where('user_id',$data['acc_id'])->find(); //比例
			$data['really_price'] = $convertible['convertible']*$data['price'];//实际到达陪玩师账户的金钱数

			if($code){
				//填表
				unset($data['money']);
				unset($data['type']);
				$data['user_id'] = $user_id;
				$data['time'] = time();
				$data['status'] = 0;

				//判断是否在合作网吧
				if(isset($_SESSION['think']['wb_id'])){
					$data['wb_id'] = $_SESSION['think']['wb_id'];
				}
				
				$ras = Db::table('hn_order')->insert($data);
				return json(['code' => 6,'msg' => $code]);
			}else{
				return json(['code' =>7,'msg' =>'失败，错误码007']);
			}


			
		}else{
			//判断陪玩师是否在线 $data['acc_id']   hn_accompany   status == 1 在线 
			$status = Db::table('hn_accompany')->field('status,down')->where('user_id',$data['acc_id'])->find();
			if($status['status'] != 1){
				return json(['code'=>8,'msg'=>'陪玩师未在线，请稍后再来']);
			}

			if($status['down'] == 1){
				return json(['code'=>5,'msg'=>'该陪玩师尚未开启线下接单']);
			}

			//判断陪玩师是否有订单为完结 查 hn_order 表
			$order_acc = Db::table('hn_order')->field('status')->where('acc_id',$data['acc_id'])->where('status','<',4)->where('status','>',1)->find();
			if($order_acc != ''){
						return json(['code'=>9,'msg'=>'该陪玩师尚有订单未完成，暂时无法接单，稍后再来吧']);
			}
		
			//获取到用户数据   在这里要判断一下  是不是来自余额的支付方式  扣除账户余额
			$user_data = Db::table('hn_user')->field('cash')->where('uid',$user_id)->find();

			//判断是否使用了优惠券
			if($data['coupon_type'] != 0){
				//查出优惠券优惠额度，用总价减去
				$discount = Db::table('hn_coupon')->field('discount')->where('id',$data['coupon_type'])->find();
				$data['pric'] = $data['price']-$discount['discount'];
			}else{
				$data['pric'] = $data['price'];
			}



			if($data['pric']>$user_data['cash']){
				return json(['code'=>1,'msg'=>'余额不足，请更换支付方式']);
			}
			//判断之前是否有未完成的订单   status < 4
			//$order_data = Db::table('hn_order')->field('status')->where(['user_id' => $user_id])->where('status','<',4)->where('status','>',0)->find();
			//var_dump($order_data);die;
			$order_data = 1;
			if($order_data != 1){
				return json(['code'=>5,'msg'=>'请完成之前的订单后再来下单，谢谢,错误码999']);
			}else{

				//减去用户余额
				$res = Db::table('hn_user')->where('uid', $user_id)->setDec('cash', $data['pric']);
				unset($data['money']);
				unset($data['type']);
				if($res){
					//这里组装订单表数据  填表
					$data['number'] = time() . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
					//将订单号存入session 进行回调判断
					//$_SESSION['user']['user_info']['numner'] =  $data['number'];
					$data['user_id'] = $user_id;
					$data['time'] = time();
					$data['status'] = 1;
					//查出陪玩师订单分成比例，计算陪玩师应得的钱数
					$convertible = Db::table('hn_accompany')->field('convertible')->where('user_id',$data['acc_id'])->find(); //比例
					$data['really_price'] = $convertible['convertible']*$data['price'];//实际到达陪玩师账户的金钱数
					//echo 1;die;
					//优惠券处理
					if($data['price'] != $data['pric']){
						Db::table('hn_coupon_user')->where(['uid' => $user_id , 'cid' => $data['coupon_type']])->delete();
					}
					
					if(isset($_SESSION['think']['wb_id'])){
						$data['wb_id'] = $_SESSION['think']['wb_id'];
					}
					/*
					//判断是否在合作网吧
					if(isset($_SESSION['think']['wb_id'])){
						$data['wb_id'] = $_SESSION['think']['wb_id'];

						//给网吧钱
							//1.通过 $data['wb_id'] 查hn_netbar(网吧入驻表) 联查hn_cybercafe(网吧管理员表) 查ratio（分成比例） $data['price']*ratio
						$wb_data = Db::table('hn_netbar')
							->alias('n')
							->join('hn_cybercafe c' , 'n.c_id = c.id')
							->field('c.ratio,c.id')
							->where(['n.id' => $data['wb_id']])
							->find();

						$wb_money = $data['price']*$wb_data['ratio'];
							//2.给网吧表 extract 添值
						Db::table('hn_netbar')->where('id',$data['wb_id'])->setInc('extract',$wb_money);
							//3.给网吧管理员表 extract not_extract添值
						Db::table('hn_cybercafe')->where('id',$wb_data['id'])->setInc('extract',$wb_money);
						Db::table('hn_cybercafe')->where('id',$wb_data['id'])->setInc('not_extract',$wb_money);
					}
					*/
					

					//用户实际付的钱
					$data['price'] = $data['pric'];
					unset($data['pric']); 

					$ras = Db::table('hn_order')->insert($data);
					if($ras){		
						$title = '您有新的订单';
						$text = '快去接单吧！！！！！！！';
						$send_id = 0;
						$rec_id = $data['acc_id'];
						$this->message_add($title,$text,$send_id,$rec_id);

						//给陪玩师发短信
						//1.陪玩师电话  2.短信内容  网吧地址 3. 6
						$phone = Db::table('hn_user')->field('account,nickname')->where('uid' , $data['acc_id'])->find();
						$location = Db::table('hn_netbar')->field('location')->where('id' , $data['wb_id'])->find();
						$dat = [
				           
				            'name' => $phone['nickname'],
				            'time' =>'8',
				            'location'  =>	$location['location']
				        ];
						
						$this->sendCms($phone['account'],$dat,6);

						return 	json(['code' => 4 ,'msg'=>'成功']);
					}else{
						return 	json(['code' => 3 ,'msg'=>'支付失败，错误码003']);
					}
				}else{
					return 	json(['code' => 2 ,'msg'=>'支付失败，错误码002']);
				}
			}
			//判断到这里结束
						
		}
	}

	//微信支付查询回调
	public function query()
	{

		return json(['daw' => 123,'msg'=>'zhaoshunchao']);
	}

}
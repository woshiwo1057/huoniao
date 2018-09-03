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
		//获取到陪玩师ID
		$acc_id = Request::instance()->param('id');
		//查询陪玩师数据 
		$acc_data = Db::table('hn_user')
						->alias('u')
						->join('hn_accompany a','u.uid = a.user_id')
						->field('u.nickname,u.head_img,a.project,a.project_id,a.pice,u.uid')
						->where('uid',$acc_id)
						->find();
							
		//获取到用户ID
		$user_id = $_SESSION['user']['user_info']['uid'];

		$service_data = [];
		 if($acc_data['project_id'] == 1){
            //游戏项目查询
            $service_data = Db::table('hn_game')->field('name')->where('id',$acc_data['project'])->find();
            $acc_data['name'] = $service_data['name'];
        }else{
            //娱乐项目查询
            $service_data = Db::table('hn_joy')->field('name')->where('id',$acc_data['project'])->find();
             $acc_data['name'] = $service_data['name'];
        }
	
		$this->assign(['acc_data' => $acc_data]);
		return $this->fetch('Order/index');
	}


	//确认订单
	public function confirm_order()
	{
		//获取到用户ID
		$user_id = $_SESSION['user']['user_info']['uid']; 
		$data = Request::instance()->param();
		
		//容错处理
		empty($data['explain'])?'':$data['explain'];
		empty($data['wechat'])?'':$data['wechat'];
		$data['price'] = $data['money']*$data['length_time'];//订单总价 = 小时价*时长
		
		if($data['type'] == 2){
			//判断陪玩师是否在线 $data['acc_id']   hn_accompany   status == 1 在线 
			$status = Db::table('hn_accompany')->field('status')->where('user_id',$data['acc_id'])->find();
			if($status['status'] != 1){
				return json(['code'=>8,'msg'=>'陪玩师未在线，请稍后再来']);
			}

			//判断陪玩师是否有订单为完结 查 hn_order 表
			$order_acc = Db::table('hn_order')->field('status')->where('acc_id',$data['acc_id'])->where('status','<',4)->where('status','>',0)->find();
			if($order_acc != ''){
						return json(['code'=>9,'msg'=>'该陪玩师尚有订单未完成，暂时无法接单，稍后再来吧']);
			}
			//判断之前是否有未完成的订单   status < 4
			$order_data = Db::table('hn_order')->field('status')->where(['user_id' => $user_id])->where('status','<',4)->find();
			if($order_data != ''){
						return json(['code'=>5,'msg'=>'请完成之前的订单后再来下单，谢谢']);
			}
			//这里是微信支付  因为没有回调     所以 die
			die;
			//价钱
			$data['price'];
			
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
				$ras = Db::table('hn_order')->insert($data);
				return json(['code' => 6,'msg' => $code]);
			}else{
				return json(['code' =>7,'msg' =>'失败，错误码007']);
			}


			
		}else{
			//判断陪玩师是否在线 $data['acc_id']   hn_accompany   status == 1 在线 
			$status = Db::table('hn_accompany')->field('status')->where('user_id',$data['acc_id'])->find();
			if($status['status'] != 1){
				return json(['code'=>8,'msg'=>'陪玩师未在线，请稍后再来']);
			}

			//判断陪玩师是否有订单为完结 查 hn_order 表
			$order_acc = Db::table('hn_order')->field('status')->where('acc_id',$data['acc_id'])->where('status','<',4)->where('status','>',0)->find();
			if($order_acc != ''){
						return json(['code'=>9,'msg'=>'该陪玩师尚有订单未完成，暂时无法接单，稍后再来吧']);
			}
		
			//获取到用户数据   在这里要判断一下  是不是来自余额的支付方式  扣除账户余额
			$user_data = Db::table('hn_user')->field('cash')->where('uid',$user_id)->find();

			if($data['money']>$user_data['cash']){
				return json(['code'=>1,'msg'=>'余额不足，请更换支付方式']);
			}
				//判断之前是否有未完成的订单   status < 4
				$order_data = Db::table('hn_order')->field('status')->where(['user_id' => $user_id])->where('status','<',4)->find();
				//var_dump($order_data);die;
				if($order_data != ''){
					return json(['code'=>5,'msg'=>'请完成之前的订单后再来下单，谢谢']);
				}else{
								
					$res = Db::table('hn_user')->where('uid', $user_id)->setDec('cash', $data['money']);
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
						//echo 1;die;
						$ras = Db::table('hn_order')->insert($data);
						if($ras){						
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
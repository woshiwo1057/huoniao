<?php
/*
*	前台充值页面
*	作者： YG
*	时间：2018.7.23
*/
namespace app\index\controller;
use \think\Controller;
use \think\Request;
use \think\Db; 

class Recharge extends Common
{

	public function __construct()
	{
		parent:: __construct(); //集成父级构造函数、
		//验证登录
		
		//$status = ['status' => '已登陆', 'code'=> true];
		if(!isset($_SESSION['user']['user_info']))
		{
			//redirect()->restore();
			$this->redirect('Index/index',['qingnidenglu' => 1]);

			//$status = ['status' => '未登陆', 'code'=> false];
		}

		
/*
		$this->assign([
				'status' => $status				
			]);
			*/
		//var_dump($status);die;

	}	

	public function index()
	{
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];

		//查询出用户的账户余额和钻石余额
		$user_data = Db::table('hn_user')->field('cash,currency')->where('uid',$id)->find();

		$this->assign(['user_data' => $user_data]);
		return $this->fetch('Recharge/index');
	}

	public function recharge()
	{
		$data = Request::instance()->param();
		
		if($data['price'] == ''){
			return json(['code' => 1,'msg' =>'输入错误']);
		}	
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];
		if($data['recharge'] == 1){
			//余额充值   只能是微信充值   因为没有回调    所以 die    
			die;
			$data['number'] = time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//订单号
			$data['price'] ; //微信的钱数
			$data['user_id'] = $id;

			//调用微信扫码支付
			$code = $this->wechat_pay($data['user_id'],$data['number'],$data['price']);

			if($code){
				return json(['code' => 2, 'msg' => $code]);
			}else{
				return json(['code' => 3, 'msg' => '获取二维码失败']);
			}


		}else if($data['recharge'] == 2){
			//钻石充值
			
			if($data['type'] == 0){
							//余额支付    $data['price']与人民币比例为 1:1

				//查询用户余额
				$user_balance  = Db::table('hn_user')->field('cash,currency,mogul_day,mogul')->where('uid',$id)->find();

				if($data['price']>$user_balance['cash']){
					return json(['code' => 4,'msg' => '余额不足']);
				}

				//组装数据
				$data['number'] = time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);	 //订单号		
				$data['money'] = $data['price']; //充值的钱数
				$data['diamond'] = $data['price']*10; //充值的鸟蛋数
				$data['user_id'] = $id;  //充值的用户ID
				$data['time'] = time();
				$data['status'] = 1;
				//删除无用数据
				unset($data['recharge']);				
				
				//给该用户表里修改值 更改余额数 更改账户鸟蛋余额 更改总充值额度  更改当天充值额度数
					 $user_balance['cash'] = $user_balance['cash']-$data['price'];
				 $user_balance['currency'] = $user_balance['currency']+$data['diamond'];
					$user_balance['mogul'] = $user_balance['mogul']+$data['price'];
				$user_balance['mogul_day'] = $user_balance['mogul_day']+$data['price'];

				$ras = Db::table('hn_user')->where('uid', $id)->update($user_balance);
				//删除无用数据
				unset($data['price']);

				//填鸟蛋充值表
				$res = Db::name('hn_recharge_diamond')->insert($data);

				if($res&&$ras){
					return json(['code' => 5,'msg' => '充值鸟蛋成功']);
				}else{
					return json(['code' => 6,'msg' => '充值鸟蛋失败']);
				}

			}else if($data['type'] = 2){
							//微信支付

				$wow['number'] = time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//订单号	
				//将验证码存入session  回调使用
				$_SESSION['user']['user_info']['order_number'] = $wow['number'];

				$wow['money'] = $data['price']; //充值的钱数
				$wow['diamond'] = $data['price']*10; //充值的鸟蛋数
				$wow['user_id'] = $id;  //充值的用户ID
				$wow['time'] = time();
				$wow['status'] = 2;
				
				//存入数据库（鸟蛋充值表）
				$res = Db::name('hn_recharge_diamond')->insert($wow);
				

				if($res){
					
					$data['user_id'] = $id;
	
					//调用微信扫码支付
					$code = $this->wechat_pay($data['user_id'],$wow['number'],$data['price']);

					if($code){
						return json(['code' => 2 , 'msg' => $code]);
					}else{
						return json(['code' => 3 , 'msg' => '获取二维码失败']);
					}

				}else{
					return json(['code' => 4 , 'msg' => '获取二维码失败，请重试']);
				}

			}
		}

						
	}

} 
<?php
/*
*	用户个人中心
*	作者： YG
*	时间：2018.8.8
*/

namespace app\index\controller;
use \think\Db; 
use \think\Request;
use \think\Session;

class User extends Common
{
	//构造函数
	public function __construct()
	{
		parent:: __construct(); //集成父级构造函数、

		if(!isset($_SESSION['user']['user_info']))
		{
			//redirect()->restore();
			$this->redirect('Index/index',['qingnidenglu' => 1]);

			//$status = ['status' => '未登陆', 'code'=> false];
		}
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];
		//看用户是否是陪玩师
		$type = Db::table('hn_user')->field('type')->where('uid',$id)->find();
		//查询个人中心导航
		if($type['type'] == 0){
			//不是陪玩师
			$nav_data = Db::table('hn_user_nav')->field('name,action')->where('type',1)->select();
		}else{
			//是陪玩师
			$nav_data = Db::table('hn_user_nav')->field('name,action')->select();
		}
		
		$this->assign(['nav_data' =>$nav_data]);
		$this->assign(['user_' =>$_SESSION['user']['user_info']]);

		define('D', Request::instance()->action());
	}
	//个人中心首页
	public function index()
	{
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];
		
		//查询用户数据
		$user_data = Db::table('hn_user')->field('head_img,sex,nickname,penguin,table,account,change_name,type')->where('uid',$id)->find();
		//var_dump($user_data);die;
		if($user_data['type'] == 1){
			//用户是陪玩师
			$user_data = Db::table('hn_user')
								->alias('u')
								->join('hn_accompany a','u.uid = a.user_id')       //详细地址 身高  体重  职业  爱好
								->field('u.head_img,u.sex,u.nickname,u.penguin,u.table,u.account,u.change_name,u.type,a.address,a.height,a.weight,a.duty,a.hobby')
								->where('uid',$id)
								->find();
		}
	
		//var_dump($user_data);die;
		if(Request::instance()->isPost())
		{
			$user_edit = Request::instance()->param();
			//$user_edit['uid'] = $id;
		
			if($user_edit['type'] == 1){
				//判断是否改了名字  提交的数据  与原数据对比
				if($user_edit['nickname'] != $user_data['nickname']){

					$repeat = Db::table('hn_user')->field('uid')->where('nickname',$user_edit['nickname'])->find();
					if($repeat){
						return json(['code' => 4,'msg'=>'昵称重复，请换一个吧0.0']);
					}			
					$res = Db::table('hn_user')->where('uid',$id)->update($user_edit);

					if($res){
						//改字段为0
						Db::table('hn_user')->where('uid',$id)->setField('change_name', 0);
						return json(['code' => 3,'msg' => '成功']);
					}else{ 
						return json(['code' => 2,'msg' => '失败']);
					}
				}

				//组装数据 该去用户表的去用户表  该去陪玩师表的去陪玩师 
				$users_data['sex'] = $user_edit['sex'];
				$users_data['table'] = $user_edit['table'];
				$users_data['penguin'] = $user_edit['penguin'];
				$users_data['nickname'] = $user_edit['nickname'];
				$res = Db::table('hn_user')->where('uid',$id)->update($users_data);

				$acc_data['address'] = $user_edit['address'];
				$acc_data['height'] = $user_edit['height'];
				$acc_data['weight'] = $user_edit['weight'];
				$acc_data['duty'] = $user_edit['duty'];
				$acc_data['hobby'] = $user_edit['hobby'];
				$ras = Db::table('hn_accompany')->where('user_id',$id)->update($acc_data);
				if($res||$ras){
					return json(['code' => 1,'msg' => '成功']);
				}else{ 
					return json(['code' => 2,'msg' => '未修改任何数据']);
				}
			}else{
			//判断是否改了名字  提交的数据  与原数据对比
				if($user_edit['nickname'] != $user_data['nickname']){

					$repeat = Db::table('hn_user')->field('uid')->where('nickname',$user_edit['nickname'])->find();
					if($repeat){
						return json(['code' => 4,'msg'=>'昵称重复，请换一个吧0.0']);
					}			
					$res = Db::table('hn_user')->where('uid',$id)->update($user_edit);

					if($res){
						//改字段为0
						Db::table('hn_user')->where('uid',$id)->setField('change_name', 0);
						return json(['code' => 3,'msg' => '成功']);
					}else{ 
						return json(['code' => 2,'msg' => '失败']);
					}
				}
				//var_dump($user_edit);die;
				$res = Db::table('hn_user')->where('uid',$id)->update($user_edit);

				if($res){
					return json(['code' => 1,'msg' => '成功']);
				}else{ 
					return json(['code' => 2,'msg' => '未修改任何数据']);
				}

			}
		}

		$this->assign(['user_data' => $user_data]);
		return $this->fetch('User/index');
	}

	//个人资料头像上传
	public function head_img()
	{	
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];
		$type = $_SESSION['user']['user_info']['type'];

		if($type == 1){
			//判断是否有尚未审核过的数据
			$data = Db::table('hn_head_examine')->field('id')->where(['uid' => $id,'status' => 1])->find();
			if($data){
				return json(['code' => 1,'msg' => '后台工作人员正在审核头像，请勿重新提交']);
			}
			$file = Request::instance()->param('option');
					
			$key = date('Y-m-d').'/'.md5(microtime()).'.jpg'; //路径

			$data = $this->cos($file,$key);
			//hn_head_examine
			if($data['code'] == 0){
				// //成功后填入审核表单
				
				$head_img = $this->img.$key; //将此路径存入表单
				//组装数据
				$head_data['uid'] = $id;
				$head_data['head_img'] = $head_img;
				$head_data['time'] = time();
				$res = Db::table('hn_head_examine')->insert($head_data);

				if($res){

					return json(['code' => 0,'msg' => '已提交，请耐心等待后台审核']);

				 }else{

				 	return json(['code' => 2,'msg' => '失败，错误码002']);

				}
				
			}else{
				return json(['code' => 3,'msg' => '失败，错误码003']);
			}
		}else{

			$file = Request::instance()->param('option');
					
			$key = date('Y-m-d').'/'.md5(microtime()).'.jpg'; //路径

			$data = $this->cos($file,$key);

			if($data['code'] == 0){
					// //成功后填入审核表单
					
					$head_img = $this->img.$key; //将此路径存入表单
					//组装数据
					
					$head_data['head_img'] = $head_img;

			//查出旧路径  删除cos上的图片
				$img_url = Db::table('hn_user')->field('head_img')->where('uid',$id)->find();
				$img_url =  substr($img_url['head_img'], 44);
				//调用删除方法 删除图片
				$this->cos_delete($img_url);
				//将路径存入用户表 更新字段值
				$res = Db::table('hn_user')->where('uid',$id)->setField('head_img', $head_img);
				//重置session的图片路径
				$_SESSION['user']['user_info']['head_img'] = $head_img;
				if($res){

					return json(['code' => 0,'msg' => '成功']);

				}else{

					return json(['code' => 2,'msg' => '失败']);

				}
			}

		}
																	
	}

	//用户 账户/手机号  修改页面
	public function change_account()
	{	
		//获取到用户ID
		$uid = $_SESSION['user']['user_info']['uid'];
		//通过ID查出该用户手机号
		$account = Db::table('hn_user')->field('account')->where('uid',$uid)->find();


		$this->assign(['account' => $account]);
		return $this->fetch('User/change_account');
	}
		//更改手机号的Ajax 短信验证码验证
	public function change_ajax()
	{
		$phone = Request::instance()->param('phone');
	
		$code = rand(1000,9999);
	
		$_SESSION['think']['change'] = $code;
		
		$sms = 'SMS_141581179';
		//调用短信服务
		$result = $this->sendSms($phone,$code,$sms);
		//将回调对象转化为数组
		$code_data = get_object_vars($result);	
	
		if($code_data['Code'] == 'OK')
	    {
	        return json(['code' => 1,'msg' => '发送成功请注意查收']);
	    }else{
	        return json(['code' => 2,'msg' => '失败']);
	    }
	}

	//更换手机号
	public function change()
	{
		//获取数据 $data['code']   $data['type']
 		$data = Request::instance()->param();

	 		$code = $_SESSION['think']['change'];

	 		if(!isset($code)){
			return  json(['code' => 1,'msg' => '自己填的验证码不算']);
			}

			if(empty($data['code'])){
				return json(['code' => 2,'msg' => '验证码不能为空']);
			}


			if($code != $data['code']){
				return json(['code' => 3,'msg' => '验证码输入错误']);
			}
			if($data['type'] == 2){
				//新手机号提交  $data['phone']  判断该手机号是否已注册
				$account = Db::table('hn_user')->field('uid')->where('account',$data['phone'])->find();
				if($account){
					return json(['code' => 7,'msg' => '该账户已注册']);
				}
				//获取用户ID
				$id = $_SESSION['user']['user_info']['uid'];
				//更新手机号/账户
				$res = Db::table('hn_user')->where('uid', $id)->update(['account' => $data['phone']]);

				if($res){
					return json(['code' => 5,'msg' => '成功']);
				}else{
					return json(['code' => 6,'msg' => '失败，错误码006']);
				}
			}
			return json(['code' => 4,'msg' => 'OK']);
	}


	//更改密码
	public function change_password()
	{	
		//通过旧密码来修改至新密码
		//echo 1;die;
		if(Request::instance()->isPost())
		{
			//获取到用户ID
			$id = $_SESSION['user']['user_info']['uid'];
			//接收数据 处理POST Ajax请求
			$data = Request::instance()->param();
			//md5($data['used'])密码    $data['new_pwd']新密码
			//查出用户密码数据
			$pwd = Db::table('hn_user')->field('password')->where('uid',$id)->find();
			if(md5($data['used']) != $pwd['password']){
				return json(['code' =>1,'msg' => '旧密码输入错误']);
			}
			$password = md5($data['new_pwd']);
			//将新密码存入表中

			$res = Db::table('hn_user')->where('uid', $id)->update(['password' => $password]);

			if($res){
				return json(['code' =>2,'msg' => '修改成功，正在跳转']);
			}else{
				return json(['code' =>3,'msg' => '修改失败，错误码003']);
			}

		}

		return $this->fetch('User/change_password');
	}

	//我的账户
	public function account()
	{
		$id = $_SESSION['user']['user_info']['uid'];

		//从个人资料里获取到余额与鸟蛋
		$user_data = Db::table('hn_user')->field('cash,currency')->where('uid',$id)->find();


		//查询充值与消费记录    需要查询 充值表  订单表  礼物表 通过时间来排序

		//1. 查询充值表
		$recharge_data = Db::table('hn_recharge_balance')->field('time,type,money')->where('user_id',$id)->order('id desc')->limit('9')->select();
		//组装数据
		/*array(1) { [0]=> array(3) { ["time"]=> int(1234567891) ["type"]=> int(2) ["money"]=> int(40) } } */
			/*		这里规定一下字段命名
			*		类型：type   额度：money   描述：explan   时间：time			
			*/
		foreach ($recharge_data as $k => $v){
			if($v['type'] == 1){
				$recharge_data[$k]['type'] = '支付宝支付';
			}else if($v['type'] == 2){
				$recharge_data[$k]['type'] = '微信支付';
			}

			$recharge_data[$k]['money'] = $v['money'];
			$recharge_data[$k]['explan'] = '充值';
			$recharge_data[$k]['time'] = $v['time'];

		}
		//2.查询订单表  
			//消费
		$order_data = Db::table('hn_order')->field('time,price')->where('user_id',$id)->order('id desc')->limit('9')->select();

		foreach ($order_data as $k => $v){
			$order_data[$k]['type'] = '陪玩消费';
			$order_data[$k]['money'] = $v['price'];
			$order_data[$k]['explan'] = '支付';
			$order_data[$k]['time'] = $v['time'];
		}
			//提现
		$take_data = Db::table('hn_withdraw_cash')->field('time,money,status')->where('user_id',$id)->select();
		foreach ($take_data as $k => $v) {

			$take_data[$k]['type'] = '提现';
			$take_data[$k]['money'] = $v['money'];

			if($v['status'] == 1){
				$take_data[$k]['explan'] = '等待后台审核中';
			}else if($v['status'] == 2){
				$take_data[$k]['explan'] = '审核通过，已提现';
			}else if($v['status'] == 3){
				$take_data[$k]['explan'] = '审核失败';
			}
			
			$take_data[$k]['time'] = $v['time'];
		}
			//获得
				
		$order_get = Db::table('hn_order')->field('time,really_price')->where('acc_id' , $id)->where('status','>',3)->order('id desc')->limit('9')->select();
		//var_dump($order_get);die;
		foreach ($order_get as $k => $v){
			$order_get[$k]['type'] = '订单结算';
			$order_get[$k]['money'] = $v['really_price'];
			$order_get[$k]['explan'] = '赚取金额';
			$order_get[$k]['time'] = $v['time'];
		}
		//3.查询通过账户余额购买的鸟蛋记录
		$gift_data  = Db::table('hn_recharge_diamond')->field('time,type,money')->where(['user_id' => $id,'type' => 0])->order('id desc')->limit('9')->select();
		foreach ($gift_data as $k => $v){
			$gift_data[$k]['type'] = '余额购买鸟蛋';
			$gift_data[$k]['money'] = $v['money'];
			$gift_data[$k]['explan'] = '充值';
			$gift_data[$k]['time'] = $v['time'];
		}

		//4.查询别人给他送的礼物换算为余额
		$gift_get = Db::table('hn_give_gift')->field('time,egg_num')->where('acc_id',$id)->limit('9')->order('id desc')->select();
		if(isset($gift_get)){
			//(1.查出该陪玩师的余额换算)
			$gift_exchange = Db::table('hn_accompany')->field('gift_exchange')->where('user_id',$id)->limit('9')->order('id desc')->find();
			
			foreach ($gift_get as $k => $v){
				$gift_get[$k]['type'] = '他人赠送礼物收入';
				$gift_get[$k]['money'] = $v['egg_num']/10*$gift_exchange['gift_exchange'];
				$gift_get[$k]['explan'] = '赚取金额';
				$gift_get[$k]['time'] = $v['time'];
			}
		}
		//var_dump($gift_data);die;
		//合并数组  以时间排序
		$detailed_data = array_merge($recharge_data,$order_data,$gift_data,$order_get,$gift_get);
		$type = "time";
		$detailed_data = $this->infinite_ranking($detailed_data,$type);
		//var_dump($detailed_data);die;
		$this->assign([
					'user_data' => $user_data,
					'detailed_data' => $detailed_data
					]);

		return $this->fetch('User/account');
	}

	//鸟蛋明细Ajax
	public function account_egg()
	{
		$id = $_SESSION['user']['user_info']['uid'];
		//查询鸟蛋充值表  礼物消费表
		/*		这里同样规定一下字段命名
		*		类型：type   额度：money   描述：explan   时间：time			
		*/
		//1.查询充值表
		$diamond_data = Db::table('hn_recharge_diamond')->field('time,type,diamond')->where('user_id',$id)->order('id desc')->limit('9')->select();
		foreach ($diamond_data as $k => $v){
			if($v['type'] == 0){
				$diamond_data[$k]['type'] = '余额';
			}else if($v['type'] == 1){
				$diamond_data[$k]['type'] = '支付宝支付';
			}else if($v['type'] == 2){
				$diamond_data[$k]['type'] = '微信支付';
			}

			$diamond_data[$k]['money'] = $v['diamond'];
			$diamond_data[$k]['explan'] = '充值';
			$diamond_data[$k]['time'] = $v['time'];

		}

		//2.查询礼物消费表
		$gift_data = Db::table('hn_give_gift')->field('egg_num,time')->where('user_id',$id)->order('id desc')->limit('9')->select();
		foreach ($gift_data as $k => $v){
			$gift_data[$k]['type'] = '送礼物';
			$gift_data[$k]['money'] = $v['egg_num'];
			$gift_data[$k]['explan'] = '消费';
			$gift_data[$k]['time'] = $v['time'];
		}

		//合并数组
		$egg_data = array_merge($diamond_data,$gift_data);
		
		//if($egg_data){
		//	$type = 'time';
		//	$egg_data = $this->ranking($egg_data,$type);
		//}
		//var_dump($egg_data);die;
		return json($egg_data);

	}

	//账户明细
	public function account_details()
	{
		$data = Request::instance()->param();
		
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];

		if($data['mivaType'] == 1){
			//资金明细
			if($data['sonType'] == 1){
				//资金全部明细
				//1. 查询充值表
				$recharge_data = Db::table('hn_recharge_balance')->field('time,type,money')->where('user_id',$id)->order('id desc')->limit('9')->select();

				foreach ($recharge_data as $k => $v){
					if($v['type'] == 1){
						$recharge_data[$k]['type'] = '支付宝支付';
					}else if($v['type'] == 2){
						$recharge_data[$k]['type'] = '微信支付';
					}

					$recharge_data[$k]['money'] = $v['money'];
					$recharge_data[$k]['explan'] = '充值';
					$recharge_data[$k]['time'] = $v['time'];

				}
				//2.查询订单表  
					//消费
				$order_data = Db::table('hn_order')->field('time,price')->where('user_id',$id)->order('id desc')->limit('9')->select();

				foreach ($order_data as $k => $v){
					$order_data[$k]['type'] = '陪玩消费';
					$order_data[$k]['money'] = $v['price'];
					$order_data[$k]['explan'] = '支付';
					$order_data[$k]['time'] = $v['time'];
				}

					//提现
				$take_data = Db::table('hn_withdraw_cash')->field('time,money,status')->where('user_id',$id)->select();
				foreach ($take_data as $k => $v) {

					$take_data[$k]['type'] = '提现';
					$take_data[$k]['money'] = $v['money'];

					if($v['status'] == 1){
						$take_data[$k]['explan'] = '等待后台审核中';
					}else if($v['status'] == 2){
						$take_data[$k]['explan'] = '审核通过，已提现';
					}else if($v['status'] == 3){
						$take_data[$k]['explan'] = '审核失败';
					}

					$take_data[$k]['time'] = $v['time'];
				}
					//获得
						
				$order_get = Db::table('hn_order')->field('time,really_price')->where('acc_id' , $id)->where('status','>',3)->order('id desc')->limit('9')->select();
				//var_dump($order_get);die;
				foreach ($order_get as $k => $v){
					$order_get[$k]['type'] = '订单结算';
					$order_get[$k]['money'] = $v['really_price'];
					$order_get[$k]['explan'] = '赚取金额';
					$order_get[$k]['time'] = $v['time'];
				}
				//3.查询通过账户余额购买的鸟蛋记录
				$gift_data  = Db::table('hn_recharge_diamond')->field('time,type,money')->where(['user_id' => $id,'type' => 0])->order('id desc')->limit('9')->select();
				foreach ($gift_data as $k => $v){
					$gift_data[$k]['type'] = '余额购买鸟蛋';
					$gift_data[$k]['money'] = $v['money'];
					$gift_data[$k]['explan'] = '充值';
					$gift_data[$k]['time'] = $v['time'];
				}

				//4.查询别人给他送的礼物换算为余额
				$gift_get = Db::table('hn_give_gift')->field('time,egg_num')->where('acc_id',$id)->limit('9')->order('id desc')->select();
				if(isset($gift_get)){
					//(1.查出该陪玩师的余额换算)
					$gift_exchange = Db::table('hn_accompany')->field('gift_exchange')->where('user_id',$id)->limit('9')->order('id desc')->find();
					
					foreach ($gift_get as $k => $v){
						$gift_get[$k]['type'] = '他人赠送礼物收入';
						$gift_get[$k]['money'] = $v['egg_num']/10*$gift_exchange['gift_exchange'];
						$gift_get[$k]['explan'] = '赚取金额';
						$gift_get[$k]['time'] = $v['time'];
					}
				}

						//合并数组
						$detailed_data = array_merge($recharge_data,$order_data,$gift_data,$order_get,$gift_get,$take_data);
						$type = "time";
						$detailed_data = $this->infinite_ranking($detailed_data,$type);
						return json($detailed_data);

			}else if($data['sonType'] == 2){
				//资金收入明细
					//1. 查询充值表
				$recharge_data = Db::table('hn_recharge_balance')->field('time,type,money')->where('user_id',$id)->order('id desc')->limit('9')->select();
	
				foreach ($recharge_data as $k => $v){
					if($v['type'] == 1){
						$recharge_data[$k]['type'] = '支付宝支付';
					}else if($v['type'] == 2){
						$recharge_data[$k]['type'] = '微信支付';
					}

					$recharge_data[$k]['money'] = $v['money'];
					$recharge_data[$k]['explan'] = '充值';
					$recharge_data[$k]['time'] = $v['time'];

				}

					//2.订单获得收益					
					$order_get = Db::table('hn_order')->field('time,really_price')->where('acc_id' , $id)->where('status','>',3)->order('id desc')->limit('9')->select();
					//var_dump($order_get);die;
					foreach ($order_get as $k => $v){
						$order_get[$k]['type'] = '订单结算';
						$order_get[$k]['money'] = $v['really_price'];
						$order_get[$k]['explan'] = '赚取金额';
						$order_get[$k]['time'] = $v['time'];
					}
					//3.查询别人给他送的礼物换算为余额
					$gift_get = Db::table('hn_give_gift')->field('time,egg_num')->where('acc_id',$id)->limit('9')->order('id desc')->select();
					if(isset($gift_get)){
						//(1.查出该陪玩师的余额换算)
						$gift_exchange = Db::table('hn_accompany')->field('gift_exchange')->where('user_id',$id)->limit('9')->order('id desc')->find();
						
						foreach ($gift_get as $k => $v){
							$gift_get[$k]['type'] = '他人赠送礼物收入';
							$gift_get[$k]['money'] = $v['egg_num']/10*$gift_exchange['gift_exchange'];
							$gift_get[$k]['explan'] = '赚取金额';
							$gift_get[$k]['time'] = $v['time'];
						}
					}
				//合并数据
				$recharge_data = array_merge($recharge_data,$order_get,$gift_get);
				$type = "time";
				$recharge_data = $this->infinite_ranking($recharge_data,$type);
				return json($recharge_data);
			}else if($data['sonType'] == 3){
				//资金支出
				//2.查询订单表
				$order_data = Db::table('hn_order')->field('time,price')->where('user_id',$id)->order('id desc')->limit('9')->select();

				foreach ($order_data as $k => $v){
					$order_data[$k]['type'] = '陪玩消费';
					$order_data[$k]['money'] = $v['price'];
					$order_data[$k]['explan'] = '支付';
					$order_data[$k]['time'] = $v['time'];
				}

				//3.查询通过账户余额购买的鸟蛋记录
				$gift_data  = Db::table('hn_recharge_diamond')->field('time,type,money')->where(['user_id' => $id,'type' => 0])->order('id desc')->limit('9')->select();
				foreach ($gift_data as $k => $v){
					$gift_data[$k]['type'] = '余额购买鸟蛋';
					$gift_data[$k]['money'] = $v['money'];
					$gift_data[$k]['explan'] = '充值鸟蛋';
					$gift_data[$k]['time'] = $v['time'];
				}

				//合并数组
				$detailed_data = array_merge($order_data,$gift_data);
				$type = "time";
				$detailed_data = $this->infinite_ranking($detailed_data,$type);
				return json($detailed_data);
			}



		}else if($data['mivaType'] == 2){
			//鸟蛋明细
			if($data['sonType'] == 1){
				//鸟蛋全部明细
					//1.查询充值表
				$diamond_data = Db::table('hn_recharge_diamond')->field('time,type,diamond')->where('user_id',$id)->order('id desc')->limit('9')->select();
				foreach ($diamond_data as $k => $v){
					if($v['type'] == 0){
						$diamond_data[$k]['type'] = '余额';
					}else if($v['type'] == 1){
						$diamond_data[$k]['type'] = '支付宝支付';
					}else if($v['type'] == 2){
						$diamond_data[$k]['type'] = '微信支付';
					}

					$diamond_data[$k]['money'] = $v['diamond'];
					$diamond_data[$k]['explan'] = '充值';
					$diamond_data[$k]['time'] = $v['time'];

				}

					//2.查询礼物消费表
				$gift_data = Db::table('hn_give_gift')->field('egg_num,time')->where('user_id',$id)->order('id desc')->limit('9')->select();
				foreach ($gift_data as $k => $v){
					$gift_data[$k]['type'] = '送礼物';
					$gift_data[$k]['money'] = $v['egg_num'];
					$gift_data[$k]['explan'] = '消费';
					$gift_data[$k]['time'] = $v['time'];
				}

				//合并数组
				$egg_data = array_merge($diamond_data,$gift_data);
				$type = "time";
				$egg_data = $this->infinite_ranking($egg_data,$type);
				//var_dump($egg_data);die;
				return json($egg_data);
			}else if($data['sonType'] == 2){
				//鸟蛋收入明细
				//1.查询充值表
				$diamond_data = Db::table('hn_recharge_diamond')->field('time,type,diamond')->where('user_id',$id)->order('id desc')->limit('9')->select();
				foreach ($diamond_data as $k => $v){
					if($v['type'] == 0){
						$diamond_data[$k]['type'] = '余额';
					}else if($v['type'] == 1){
						$diamond_data[$k]['type'] = '支付宝支付';
					}else if($v['type'] == 2){
						$diamond_data[$k]['type'] = '微信支付';
					}

					$diamond_data[$k]['money'] = $v['diamond'];
					$diamond_data[$k]['explan'] = '充值';
					$diamond_data[$k]['time'] = $v['time'];

				}

				$type = "time";
				$diamond_data = $this->infinite_ranking($diamond_data,$type);
				return json($diamond_data);

			}else if($data['sonType'] == 3){
				//鸟蛋支出明细
				//2.查询礼物消费表
				$gift_data = Db::table('hn_give_gift')->field('egg_num,time')->where('user_id',$id)->order('id desc')->limit('9')->select();
				foreach ($gift_data as $k => $v){
					$gift_data[$k]['type'] = '送礼物';
					$gift_data[$k]['money'] = $v['egg_num'];
					$gift_data[$k]['explan'] = '消费';
					$gift_data[$k]['time'] = $v['time'];
				}

				$type = "time";
				$gift_data = $this->infinite_ranking($gift_data,$type);
				return json($gift_data);
			}
		}
	}

	//提现
	public function withdraw_cash()
	{

		//var_dump($_SESSION['think']['withdraw_code']);die;
		//获取用户ID
		$id = $_SESSION['user']['user_info']['uid'];
		//查出账号（手机号）与当前账户余额
		$user_data = Db::table('hn_user')->field('account,cash')->where('uid',$id)->find();

		if(Request::instance()->isPost()){
			$data = Request::instance()->param();

			//var_dump($data);die;
			//判断验证码是否正确
			if(!isset($_SESSION['think']['withdraw_code'])){
				return json(['code' => 4 , 'msg' => '自己填的验证码不算']);
			}
			if($data['code'] != $_SESSION['think']['withdraw_code']){
				return json(['code' => 1 , 'msg' => '验证码错误']);
			}
			//删除无用的数据  组装数据并填表  更新用户余额数据
			unset($data['code']);
			unset($_SESSION['think']['withdraw_code']);
			$data['user_id'] = $id;
			$data['time'] = time();

				//1.更新用户账户数据
			$ras = Db::table('hn_user')->where('uid', $id)->setDec('cash', $data['money']);
				//2.填表
			$data['money'] = $data['money']-2;  //减去扣除的手续费是用户真正提取的余额钱数
			$res = Db::table('hn_withdraw_cash')->insert($data);

			if($res&&$ras){
				return json(['code' => 2 , 'msg' => '成功提交，请耐心等待审核']);
			}else{
				return json(['code' => 3 , 'msg' => '提交失败，请检查数据后重试']);
			}
		}

		$this->assign('user_data' , $user_data);
		return $this->fetch('User/withdraw_cash');
	}

	//提现短信验证码控制器
	public function withdraw_code()
	{
		$phone = Request::instance()->param('phone');
		
		$code = rand(1000,9999);
		//Session::set('real_code',$code);//将验证码存入Session
		$_SESSION['think']['withdraw_code'] = $code;
		
		$sms = 'SMS_145591893';

		$result = $this->sendSms($phone,$code,$sms);
			//将回调对象转化为数组
		$code_data = get_object_vars($result);

		if($code_data['Code'] == 'OK')
	    {	    	
	        return json(['code' => 1,'msg' => '发送成功请注意查收']);
	    }else{	    	
	        return json(['code' => 2,'msg' => '失败']);
	    }
	}

	//相册管理
	public function album()
	{
		$id = $_SESSION['user']['user_info']['uid'];

		$album_data = Db::table('hn_user_album')->where('user_id',$id)->order('id desc')->select();
		//var_dump($album_data);die;


		$this->assign(['album_data' => $album_data]);
		return $this->fetch('User/album');
	}

	//相册添加   Ajax
	public function album_add()
	{
		$album['user_id'] = $_SESSION['user']['user_info']['uid'];
		//若图片数大于等于8张则不给上传
		$num = Db::table('hn_user_album')->where('user_id',$album['user_id'])->select();

		if(count($num) <= 7){

			$file = Request::instance()->param('option');	

			$key = date('Y-m-d').'/'.md5(microtime()).'.jpg'; //路径		

			$url_data = $this->cos($file,$key); //上传图片至服务器  

			//判断返回数据是否成功
			if($url_data['code'] == 0){
				// 成功  将用户ID与图片存入数据库
				$album['img_url'] = $this->img.$key; //拼装路径
				$res = Db::table('hn_user_album')->insert($album);

				if($res){

					return json(['code' => 0,'msg' => '成功']);

				}else{

					return json(['code' => 2,'msg' => '失败，错误码002']);

				}

			}else{

				return json(['code' => 3,'msg' => '失败，错误码003']);

			}
	
		}else{

			return json(['code' => 4,'msg' => '上传失败,最多显示8张图片']);

		}
					
	}

	//相册删除
	public function album_delete()
	{

		$id = Request::instance()->param('id');
		//根据ID查数据  进行分割字符串 组装路径
		$key = Db::table('hn_user_album')->field('img_url')->where('id',$id)->find();
		$key =  substr( $key['img_url'], $this->Intercept);

		//调用删除方法 删除图片
		$data = $this->cos_delete($key);

		//删除数据库数据
		if($data['code'] == 1)
		{
			$res = Db::table('hn_user_album')->delete($id);

			if($res){
				return json(['code' => 1,'msg'=>'成功']);
			}else{
				return json(['code' => 3,'msg'=>'失败，错误码003']);
			}
		}else{

			return json(['code' => 2,'msg'=>'失败，错误码002']);
		}

		/*
		Guzzle\Service\Resource\Model Object ( 
			[structure:protected] => [data:protected] => Array 
				( [DeleteMarker] => [VersionId] => [RequestCharged] => [RequestId] => NWI4MTEyNjZfNWFiMjU4NjRfMjkyNl8yYWFjMDU= ) )
		*/
	}


	//个人中心订单
	public function order()
	{
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];


			//需要查询的东西：1.通过acc_id查出陪玩师的名字与他的单价
							//2.通过service
		//查询订单信息    ->field('number,acc_id,user_id,service,length_time,time,status,price')

		$order_data = Db::table('hn_order')
						->alias('o')
						->join('hn_user u','o.acc_id = u.uid')
						->field('o.id,o.number,o.service,o.length_time,o.time,o.status,o.price,u.nickname,u.head_img')
						->where('o.user_id',$id)->limit('8')->order('id desc')->select();
		//var_dump($order_data);die;
		$this->assign(['order_data' => $order_data]);
		return $this->fetch('User/order');
	}

	//订单状态Ajax
	public function order_ajax()
	{
		//获取到提交数据
		$data = Request::instance()->param();
		//$tata['type'] == 1;  接单
		//$tata['type'] == 2;  取消订单（删除数据）
		//$tata['type'] == 3;  完成订单（用户确认订单完成，给陪玩师账户打钱） 将status改为4
		if($data['type'] == 1){
			//这里是接单 通过$data['order_id']来操作  将status变为2
			$res = Db::table('hn_order')->where('id', $data['order_id'])->update(['status' => 2]);
			if($res){
				return json(['code' => 1,'msg' => '成功接单']);
			}else{
				return json(['code' => 2,'msg' => '失败，错误码002']);
			}
		}else if($data['type'] == 2){		
			//这里是取消订单 通过$data['order_id']来操作  删除该订单
				//主键删除
			$res = Db::table('hn_order')->delete($data['order_id']);
			if($res){
				return json(['code' => 3,'msg' => '成功取消订单']);
			}else{
				return json(['code' => 4,'msg' => '失败，错误码004']);
			}
		}else if($data['type'] == 3){
			//确认完成 通过订单ID查出订单详情 给陪玩师账户余额加钱
				//1.查出陪玩师ID 和订单 价格 时长
			$order_data = Db::table('hn_order')->field('acc_id,really_price,service,length_time')->where('id',$data['order_id'])->find();
				//2.给陪玩师账户余额加钱   	陪玩师增加时长订单
			$ras = Db::table('hn_user')->where('uid', $order_data['acc_id'])->setInc('cash',$order_data['really_price']); //加钱
			//加接单时长
			$length_time = $order_data['length_time']*60*60;
			Db::table('hn_apply_project')->where(['uid' => $order_data['acc_id'] , 'project_name' => $order_data['service']])->setInc('length_time', $length_time);
			//加订单数
			Db::table('hn_apply_project')->where(['uid' => $order_data['acc_id'] , 'project_name' => $order_data['service']])->setInc('order_num', 1);
			if($ras){
				//改变订单状态
				$time = time(); //结束时间
				$res = Db::table('hn_order')->where('id', $data['order_id'])->update(['status' => 4,'over_time' => $time]);

				if($res){
					return json(['code' => 5,'msg' => '订单结算成功，可以选择评论']);
				}else{
					return json(['code' => 6,'msg' => '失败，错误码006']);
				}

			}else{
				return json(['code' => 7,'msg' => '失败，错误码007']);
			}
					
		}
	}

	//送出礼物详情控制器
	public function gift()
	{
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];

		//查询数据  注意  是送出的礼物
						
		$gift_data = Db::table('hn_give_gift')
					->alias('g')
					->join('hn_user u', 'g.acc_id = u.uid')
					->field('g.img_url,u.nickname,g.num,g.egg_num,g.time')->where('g.user_id',$id)->order('g.id desc')
					->limit('15')
					->select();

		$this->assign(['gift_data' => $gift_data]);
		return $this->fetch('User/gift');
	}

	//我的消息控制器
	public function msg()
	{
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];

		//查询消息表
		$msg_data = Db::table('hn_msg')->field('title,content,time')->order('id desc')->select();

		//之后会有其他消息类型  完了数组合并
		$this->assign(['msg_data' => $msg_data]);
		return $this->fetch('User/msg');
	}

	//我的接单控制器
	public function receipt()
	{
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];
		//查订单表 查出自己的接单
		$order_data = Db::table('hn_order')->field('id,number,price,length_time,service,status')->where('acc_id',$id)->limit(10)->order('id desc')->select();

		$this->assign(['order_data' => $order_data]);
		return $this->fetch('User/receipt');
	}

	//收到礼物详情控制器
	public function package()
	{
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];

		//查询数据  注意  是收到的礼物
		//->alias('u')->join('hn_accompany a','u.uid = a.user_id')	
		$package_data = Db::table('hn_give_gift')
						->alias('g')
						->join('hn_user u', 'g.user_id = u.uid')
						->field('g.img_url,u.nickname,g.num,g.time,g.egg_num')->where('g.acc_id',$id)->order('g.id desc')
						->limit('15')
						->select();

		$this->assign(['package_data' => $package_data]);
		
		return $this->fetch('User/package');
	}


	//实名认证控制器
	public function real()
	{
		//获取到用户ID   查询陪玩师表 查询认证信息
		$id = $_SESSION['user']['user_info']['uid'];
		$acc_data = Db::table('hn_accompany')->field('real')->where('id',$id)->find();

		//提交过认证信息后给他在页面中显示  不能修改    查询实名认证表
		if($acc_data['real'] != 0)
		{
			$real_data = Db::table('hn_acc_real')->field('name,card_num,zfb,front_img,back_img')->where('user_id',$id)->find();

			$this->assign(['real_data' => $real_data]);
		}

		//判断是否提交数据
		
		$this->assign(['acc_data' => $acc_data]);
		return $this->fetch('User/real');	
	}

	//实名认证提交数据处理控制器
	public function real_data()
	{	
		//获取到用户ID
		$uid = $_SESSION['user']['user_info']['uid'];

		$real_data = Db::table('hn_apply_acc')->field('real')->where(['user_id' => $uid , 'real' => 1])->find();

		if($real_data){
			return json(['code' => 1 , 'msg' => '已经提交审核，请耐心等待']);
		}

		//获取数据内容
		$user_real = Request::instance()->param();
		//$user_real['card_num'] //身份证号
		

		$file = $user_real['zhengData']; //上传图片的base64
		$key = date('Y-m-d').'/'.md5(microtime()).'.jpg';
		$this->cos($file,$key); //上传正面图片至服务器

		$user_real['card_photo'] = $this->img.$key; //图片路径

		$user_real['real'] = 1;//改变状态
		//删除没用的数据
		unset($user_real['zhengData']);

		//填表
		$res = Db::table('hn_apply_acc')->where('user_id',$uid)->update($user_real);

		if($res){
			return json(['code' => 2 , 'msg' => '已经提交审核，请耐心等待']);
		}else{
			return json(['code' => 3 , 'msg' => '失败，错误码003']);
		}


		/*
		$user_real = Request::instance()->param();
		//获取到用户ID
		$real['user_id'] = $_SESSION['user']['user_info']['uid'];
		//获取数据内容

		if(!isset($_SESSION['think']['real_code'])){
		return  json(['code' => 5,'msg' => '自己填的验证码不算']);
		}
		//判断验证码
		if($_SESSION['think']['real_code'] != $user_real['code']){
			return json(['code' => 1,'msg' => '验证码输入错误']);
		}

		//删除验证码（已经不需要了）
		//Session::delete('real_code');
		$_SESSION['think']['real_code'] = null;
		unset($user_real['code']);

		//上传身份证照片
		$file_zheng = $user_real['zhengData']; 
		$file_fan = $user_real['fanData'];
		$key_zheng = date('Y-m-d').'/'.md5(microtime()).'.jpg'; //正面路径
		$key_fan = date('Y-m-d').'/'.md5(time()).'.jpg';		//反面路径
		$zheng_data = $this->cos($file_zheng,$key_zheng); //上传正面图片至服务器
		$fan_data = $this->cos($file_fan,$key_fan); //上传反面图片至服务器

		if($zheng_data['code'] == 0&&$fan_data['code'] == 0)
		{
			//当图片都成功上传后  组装数据
			$real['name'] = $user_real['name'];
			$real['card_num'] = $user_real['card_num'];
			$real['zfb'] = 	$user_real['zfb'];
			$real['front_img'] = $this->img.$key_zheng;//拼装路径
			$real['back_img'] = $this->img.$key_fan;//拼装路径
			$real['time'] = time();

			$res = Db::table('hn_acc_real')->insert($real);

			if($res){
				//更新陪玩师表 real字段 改为 1
				Db::table('hn_accompany')->where('user_id',$real['user_id'])->setField('real', '1');
					return json(['code' => 3,'msg' => '等待审核']);
			}else{
					return json(['code' => 4,'msg' => '数据上传失败']);
			}

		}else{
				return json(['code' => 2,'msg' => '图片上传失败']);
		}	
		*/
		
	}

	//实名认证短信验证码控制器
	public function real_code()
	{
		$phone = Request::instance()->param('phone');
		
		$code = rand(1000,9999);
		//Session::set('real_code',$code);//将验证码存入Session
		$_SESSION['think']['real_code'] = $code;

		$sms = 'SMS_141606178';

		$result = $this->sendSms($phone,$code,$sms);
			//将回调对象转化为数组
		$code_data = get_object_vars($result);

		if($code_data['Code'] == 'OK')
	    {	    	
	        return json(['code' => 1,'msg' => '发送成功请注意查收']);
	    }else{	    	
	        return json(['code' => 2,'msg' => '失败']);
	    }
	}

	//关注
	public function follow()
	{
        $uid = $_SESSION['user']['user_info']['uid'];
        $follow = \db('hn_follow');//关注表
        $follows1 = $follow->alias('f')->join('hn_user u','u.uid = f.followed_user')->join('hn_accompany a','a.user_id = f.followed_user')->where(['f.user_id'=>$uid,'f.status'=>1])->field('a.hot,f.id,u.sex,u.age,u.uid,u.nickname,u.head_img')->paginate(25);
        $page1 = $follows1->render();
      
      if($_SESSION['user']['user_info']['type'] == 1){
            $follows2 = $follow->alias('f')->join('hn_user u','f.user_id = u.uid')->where(['f.followed_user'=>$uid,'f.status'=>1])->field('f.id,u.sex,u.age,u.uid,u.nickname,u.head_img')->paginate(25);
            $page2 = $follows2->render();

        }else{
            $follows2 = null;
            $page2 = null;
        }
        $this->assign([
            'data1'=> $follows1,
            'data2'=> $follows2,
            'page1'=> $page1,
            'page2'=> $page2
        ]);

        return $this->fetch('User/follow');
	}

	function follow_operate(){
        $request = request();//think助手函数
        $data_get = $request->param();//获取get与post数据
        $follow = \db('hn_follow');//关注表
        $uid = $_SESSION['user']['user_info']['uid'];
        $res = $follow->where('id',$data_get['id'])->find();
        if($res){
            if($res['status'] == 1){
                $data = [
                    'status'=>2
                ];
            }else{
                $data = [
                    'status'=>1
                ];
            }
            $red = $follow->where('id',$data_get['id'])->update($data);

        }else{
            $data = [
                'user_id'=> $uid,
                'followed_user'=> $data_get['followed_user'],
                'status'=>1
            ];
            $red = $follow->insert($data);
        }
        if($red){
            return ['code' => 1,'msg' => '操作成功'];
        }else{
            return ['code' => 2,'msg' => '操作失败，请重试'];
        }
    }

    function follow_add(){
        $request = request();//think助手函数
        $data_get = $request->param();//获取get与post数据
        $follow = \db('hn_follow');//关注表
        $uid = $_SESSION['user']['user_info']['uid'];
        $res = $follow->where(['user_id'=>$uid,'followed_user'=>$data_get['followed_user']])->find();
        if($res){
            if($res['status'] == 1){
                $data = [
                    'status'=>2
                ];
                $aa = ['code' => 2,'msg' => '操作成功'];
            }else{
                $data = [
                    'status'=>1
                ];
                $aa = ['code' => 1,'msg' => '操作成功'];
            }
            $red = $follow->where(['user_id'=>$uid,'followed_user'=>$data_get['followed_user']])->update($data);

        }else{
            $data = [
                'user_id'=> $uid,
                'followed_user'=> $data_get['followed_user'],
                'status'=>1
            ];
            $red = $follow->insert($data);
            $aa = ['code' => 1,'msg' => '操作成功'];
        }
        if($red){
            return $aa;
        }else{
            return ['code' => 3,'msg' => '操作失败，请重试'];
        }
    }

	//服务项目管理
	public function service()
	{
		//$this->error('暂未开放','User/index');
		//查询申请记录
			//获取用户ID
		$uid = $_SESSION['user']['user_info']['uid'];
		$apply_data = Db::table('hn_apply_project')->field('id,project,project_id,project_grade,project_name,status,time,order_num,pric,type')->where('uid',$uid)->order('id desc')->limit(10)->select();
		
		//组装价格
		foreach($apply_data as $k => $v){

			if($v['project'] == 1)
			{	
				//查到项目初始的价格
				$pric = Db::table('hn_game_grade')->field('pric')->where('id',$v['project_grade'])->find();

				//查到当前陪玩师该项目最高的价格
				$height_pric = $this->pric($v['order_num'],$pric['pric']);

				$count = ($height_pric-$pric['pric'])/5;
					
				$data = [];
				
					
					for($i=0; $i<=$count; ++$i){

						$data[$i] = (5*$i)+$pric['pric'];
					}
				

				$data[0] = $v['pric'];

				$apply_data[$k]['pric'] = $data;	
		
				
			}else if($v['project'] == 2){
				//查到项目初始的价格
				$pric = Db::table('hn_joy_grade')->field('pric')->where('id',$v['project_grade'])->find();

				//查到当前陪玩师该项目最高的价格
				$height_pric = $this->pric($v['order_num'],$pric['pric']);

				$count = ($height_pric-$pric['pric'])/5;
					
				$data[0] = $v['pric'];

				for($i=1; $i <= $count; $i++){ 
					if($count == 0){
						$data[0] = $v['pric'];
					}
					$data[$i] = (5*$i)+$pric['pric'];

				}
		
				$apply_data[$k]['pric'] = $data;
			
			}

		}


		$this->assign(['apply_data' => $apply_data]);
		return $this->fetch('User/service');

	}

	//申请服务
	public function service_add()
	{
		if(Request::instance()->isPost())
		{
			//获取用户ID
			$uid = $_SESSION['user']['user_info']['uid'];

			$data = Request::instance()->param();

			$judge = Db::table('hn_apply_project')->field('id')->where(['uid' => $uid,'project' => $data['project'] , 'project_id' => $data['project_id'] , 'status' => 0])->find();
			if($judge){
				return json(['code' => 4 , 'msg' => '该项目已有数据，请勿重复提交']);
			}
			//var_dump($data);die;
			$file = $data['img_url'];

			$key = date('Y-m-d').'/'.md5(microtime()).'.jpg'; //路径
			//$head_img = $this->img.$key; //将此路径存入表单
			$status = $this->cos($file,$key);

			if($status['code'] == 0){
				//组装数据
					//1.图片路径
				$data['img_url'] = $this->img.$key;
					//2.陪玩服务名字与陪玩等级名字
					if($data['project'] == 1){
						//游戏
						$gama_name = Db::table('hn_game')->field('name')->where('id',$data['project_id'])->find();
						$grade_name = Db::table('hn_game_grade')->field('type_name,pric')->where('id',$data['project_grade'])->find();
						$data['project_name'] = $gama_name['name'];
						$data['project_grade_name'] = $grade_name['type_name'];
						//服务项目初始价格
						$data['pric'] = $grade_name['pric'];

					}else if($data['project'] == 2){
						//娱乐
						$gama_name = Db::table('hn_joy')->field('name')->where('id',$data['project_id'])->find();
						$grade_name = Db::table('hn_joy_grade')->field('type_name,pric')->where('id',$data['project_grade'])->find();
						$data['project_name'] = $gama_name['name'];
						$data['project_grade_name'] = $grade_name['type_name'];
						//服务项目初始价格
						$data['pric'] = $grade_name['pric'];
						
					}
					//3.用户ID
				$data['uid'] = $_SESSION['user']['user_info']['uid'];
					//4.时间
				$data['time'] = time();
					

				
				
				//填表
				$res = Db::table('hn_apply_project')->insert($data);

				if($res){
					return json(['code' => 1 , 'msg' => '成功']);
				}else{
					return json(['code' => 2 , 'msg' => '失败']);
				}
			}else{
				return json(['code' => 3 , 'msg' => '失败，错误码003']);
			}

		}
		
		return $this->fetch('User/service_add');
	}

	//修改服务价格
	public function pric_edit()
	{
		$data = Request::instance()->param();
		
		//获取用户ID
		$uid = $_SESSION['user']['user_info']['uid'];

		//容错  防止他人修改
		$id = Db::table('hn_apply_project')->field('uid')->where('id',$data['id'])->find();
		if($uid != $id['uid']){
			return json(['code' => 1 , 'msg' => '非法操作']);
		}
		//修改数据
		$res = Db::table('hn_apply_project')->where('id', $data['id'])->update(['pric' => $data['pric']]);

		if($res){
			return json(['code' => 2 , 'msg' => '修改成功']);
		}else{
			return json(['code' => 3 , 'msg' => '操作失败']);
		}



	}


	//开启服务
	public function open_service()
	{
		//获取用户ID
		$uid = $_SESSION['user']['user_info']['uid'];
		//查询实名认证字段
		$real = Db::table('hn_accompany')->field('real,up,down')->where('user_id',$uid)->find();
		
		$this->assign(['real' => $real]);
		return $this->fetch('User/open_service');
	}

	//开启服务Ajax
	public function open_service_ajax()
	{
		$data = Request::instance()->param();
		
		//获取用户ID
		$uid = $_SESSION['user']['user_info']['uid'];

		if($data['type'] == 1){
			//线上服务
			if($data['xianshang'] == 1){
				//这里开启服务
				$res = Db::table('hn_accompany')->where('user_id',$uid)->update(['up' => 2]);

				if($res){
					return json(['code' => 1 , 'msg' => '成功']);
				}else{
					return json(['code' => 2 , 'msg' => '操作失败，请重试']);
				}

			}else{
				//这里关闭服务
				$res = Db::table('hn_accompany')->where('user_id',$uid)->update(['up' => 1]);

				if($res){
					return json(['code' => 1 , 'msg' => '成功']);
				}else{
					return json(['code' => 2 , 'msg' => '操作失败，请重试']);
				}

			}
		}else if($data['type'] == 2){
				//线下服务
			if($data['xianxia'] == 1){
				//这里开启
				$res = Db::table('hn_accompany')->where('user_id',$uid)->update(['down' => 2]);

				if($res){
					return json(['code' => 1 , 'msg' => '成功']);
				}else{
					return json(['code' => 2 , 'msg' => '操作失败，请重试']);
				}
			}else{
				//这里关闭
				$res = Db::table('hn_accompany')->where('user_id',$uid)->update(['down' => 1]);

				if($res){
					return json(['code' => 1 , 'msg' => '成功']);
				}else{
					return json(['code' => 2 , 'msg' => '操作失败，请重试']);
				}
			}
		}
	}
	

	//优惠券
	public function coupon()
	{
		//获取用户ID
		$uid = $_SESSION['user']['user_info']['uid'];

		//查询用户优惠券表 
			//需要 优惠券图片  优惠金额  有效期  描述  状态
			$coupon_data = Db::table('hn_coupon_user')
								->alias('cu')
								->join('hn_coupon c' , 'cu.cid = c.id')
								->field('c.img_url,c.discount,c.explain,cu.validity,cu.status')
								->where('uid' , $uid)
								->select();  

			//var_dump($coupon_data);die;

		$this->assign(['coupon_data' => $coupon_data]);
		return $this->fetch('User/coupon');
	}

}




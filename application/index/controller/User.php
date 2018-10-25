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
		
		//$this->assign(['type' => $type]);
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
		$user_data = Db::table('hn_user')->field('uid,head_img,sex,nickname,penguin,table,account,change_name,type,neice')->where('uid',$id)->find();
		//var_dump($user_data);die;
		if($user_data['type'] == 1){
			//用户是陪玩师
			$user_data = Db::table('hn_user')
								->alias('u')
								->join('hn_accompany a','u.uid = a.user_id')       //详细地址 身高  体重  职业  爱好
								->field('u.uid,u.head_img,u.sex,u.nickname,u.penguin,u.table,u.account,u.change_name,u.type,u.neice,a.address,a.height,a.weight,a.duty,a.hobby')
								->where('uid',$id)
								->find();
		}
	
		//var_dump($user_data);die;
		if(Request::instance()->isPost())
		{
			$user_edit = Request::instance()->param();
			//$user_edit['uid'] = $id;
		
			if($user_edit['type'] == 1){
				//组装数据 该去用户表的去用户表  该去陪玩师表的去陪玩师 
				$acc_data['address'] = $user_edit['address'];
				$acc_data['height'] = $user_edit['height'];
				$acc_data['weight'] = $user_edit['weight'];
				$acc_data['duty'] = $user_edit['duty'];
				$acc_data['hobby'] = $user_edit['hobby'];
				unset($user_edit['address']);
				unset($user_edit['height']);
				unset($user_edit['weight']);
				unset($user_edit['duty']);
				unset($user_edit['hobby']);
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

				
				
				$users_data['sex'] = $user_edit['sex'];
				$users_data['table'] = $user_edit['table'];
				$users_data['penguin'] = $user_edit['penguin'];
				$users_data['nickname'] = $user_edit['nickname'];
				$res = Db::table('hn_user')->where('uid',$id)->update($users_data);

			
				
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
		$type = Db::table('hn_user')->field('type')->where('uid',$id)->find();
		//var_dump($nav_data);die;
		if($type['type'] == 1){
			//判断是否有尚未审核过的数据
			$data = Db::table('hn_head_examine')->field('id')->where(['uid' => $id,'status' => 1])->find();
			if($data){
				return json(['code' => 1,'msg' => '后台工作人员正在审核头像，请勿重新提交']);
			}
			$file = Request::instance()->param('option');
					
			$key = $id.'/'.md5(microtime()).'.jpg'; //路径

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
					
			$key = $id.'/'.md5(microtime()).'.jpg'; //路径

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
		//var_dump($detailed_data);die;
		if(!empty($detailed_data)){
		$detailed_data = $this->infinite_ranking($detailed_data,$type);
		}
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
				$order_data = Db::table('hn_order')->field('time,price')->where('user_id',$id)->where('status','>',0)->order('id desc')->limit('9')->select();

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
						$gift_get[$k]['money'] = floor($v['egg_num']/10*$gift_exchange['gift_exchange']*100)/100;;
						$gift_get[$k]['explan'] = '赚取金额';
						$gift_get[$k]['time'] = $v['time'];
					}
				}

						//合并数组
						$detailed_data = array_merge($recharge_data,$order_data,$gift_data,$order_get,$gift_get,$take_data);
						if(!empty($detailed_data)){
						$type = "time";
						$detailed_data = $this->infinite_ranking($detailed_data,$type);
						}
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
							$gift_get[$k]['money'] = floor($v['egg_num']/10*$gift_exchange['gift_exchange']*100)/100;
							$gift_get[$k]['explan'] = '赚取金额';
							$gift_get[$k]['time'] = $v['time'];
						}
					}
				//合并数据

				$recharge_data = array_merge($recharge_data,$order_get,$gift_get);

				if(!empty($recharge_data)){
					$type = "time";
					$recharge_data = $this->infinite_ranking($recharge_data,$type);
					
				}

				return json($recharge_data);
			}else if($data['sonType'] == 3){
				//资金支出
				//2.查询订单表
				$order_data = Db::table('hn_order')->field('time,price')->where('user_id',$id)->where('status','>',0)->order('id desc')->limit('9')->select();

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
				if(!empty($detailed_data)){
				$type = "time";
				$detailed_data = $this->infinite_ranking($detailed_data,$type);
				}
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
				if(!empty($egg_data)){
				$type = "time";
				$egg_data = $this->infinite_ranking($egg_data,$type);
				}
				//var_dump($egg_data);die;
				return json($egg_data);
			}else if($data['sonType'] == 2){
				//鸟蛋收入明细
				//1.查询充值表
				$diamond_data = Db::table('hn_recharge_diamond')->field('time,type,diamond')->where(['user_id' => $id , 'status' => 1])->order('id desc')->limit('9')->select();
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

				if(!empty($diamond_data)){
				$type = "time";
				$diamond_data = $this->infinite_ranking($diamond_data,$type);
				}
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

				if(!empty($gift_data)){
				$type = "time";
				$gift_data = $this->infinite_ranking($gift_data,$type);
				}
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
			$user_data = Db::table('hn_user')->field('account,cash')->where('uid',$id)->find();
			//var_dump($data);die;
			//判断验证码是否正确
			if(!isset($_SESSION['think']['withdraw_code'])){

				
				return json(['code' => 4 , 'msg' => '自己填的验证码不算']);
			}
			if($data['code'] != $_SESSION['think']['withdraw_code']){
				return json(['code' => 1 , 'msg' => '验证码错误']);
			}
			if($user_data['cash'] < $data['money']){
				return json(['code' => 1 , 'msg' => '账户余额不足']);
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

			

			if($ras){
				$res = Db::table('hn_withdraw_cash')->insert($data);
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
		//获取用户ID
		$id = $_SESSION['user']['user_info']['uid'];

		$album['user_id'] = $_SESSION['user']['user_info']['uid'];
		//若图片数大于等于8张则不给上传
		$num = Db::table('hn_user_album')->where('user_id',$album['user_id'])->select();

		if(count($num) <= 7){

			$file = Request::instance()->param('option');	

			$key = $id.'/'.'album'.'/'.md5(microtime()).'.jpg'; //路径		

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
		$key =  substr($key['img_url'], $this->Intercept);

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
						->field('o.id,o.number,o.service,o.length_time,o.time,o.status,o.price,u.nickname,u.head_img,u.uid uid')
						->where('o.user_id',$id)->where('status' , '>' ,'0')->limit('8')->order('id desc')->select();
		//var_dump($order_data);die;
		$this->assign(['order_data' => $order_data]);
		return $this->fetch('User/order');
	}

	//订单状态Ajax
	public function order_ajax()
	{
		//获取到提交数据
		$data = Request::instance()->param();
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];
		//$tata['type'] == 1;  接单
		//$tata['type'] == 2;  取消订单（删除数据）
		//$tata['type'] == 3;  完成订单（用户确认订单完成，给陪玩师账户打钱） 将status改为4
		if($data['type'] == 1){

			//判断陪玩师是否有订单为完结 查 hn_order 表
			$order_acc = Db::table('hn_order')->field('status')->where('acc_id',$id)->where('status','<',4)->where('status','>',0)->find();
			if($order_acc['status']<4&&$order_acc['status']>1){
				
				return json(['code'=>8,'msg'=>'您尚有订单未完成，暂时无法接单，完成后再来吧']);
			}
			//增加一次陪玩师的接单量（陪玩师表）
			Db::table('hn_accompany')->where('user_id' , $id)->setInc('okami');//（总接单数）
			Db::table('hn_accompany')->where('user_id' , $id)->setInc('okami_day'); //(当天接单数)
			//这里是接单 通过$data['order_id']来操作  将status变为2
			$res = Db::table('hn_order')->where('id', $data['order_id'])->update(['status' => 2,'single_time' => time()]);
			if($res){
				return json(['code' => 1,'msg' => '成功接单']);
			}else{
				return json(['code' => 2,'msg' => '失败，错误码002']);
			}
		}else if($data['type'] == 2){		
			//这里是取消订单 通过$data['order_id']来操作  删除该订单
				//主键删除
			$order_data = Db::table('hn_order')->field('user_id,price,status')->where('id' , $data['order_id'])->find();
			if($order_data['status'] != 1){
			 	return json(['code' => 2,'msg' => 'SB']);
			 }

			$res = Db::table('hn_user')->where('uid', $order_data['user_id'])->setInc('cash', $order_data['price']);

			if($res){
				Db::table('hn_order')->where('id' , $order_data['user_id'])->delete($data['order_id']);

				$title = '订单信息';
				$text = '您的订单陪玩师没有接单，系统已为您删除，余额返回到了您的账户，如有误请与工作人员联系';
				$send_id = 0;
				$rec_id = $order_data['user_id'];
				$this->message_add($title,$text,$send_id,$rec_id);

				return json(['code' => 3,'msg' => '成功取消订单']);
			}else{
				return json(['code' => 4,'msg' => '失败，错误码004']);
			}

			
		}else if($data['type'] == 3){
			//确认完成 通过订单ID查出订单详情 给陪玩师账户余额加钱
				//1.查出陪玩师ID 和订单 价格 时长
			$order_data = Db::table('hn_order')->field('wb_id,acc_id,really_price,service,length_time,price,status')->where('id',$data['order_id'])->find();
			 if($order_data['status'] != 2){
			 	return json(['code' => 2,'msg' => 'SB']);
			 }
				//2.给陪玩师账户余额加钱   	陪玩师增加时长订单
			
			$ras = Db::table('hn_user')->where('uid', $order_data['acc_id'])->setInc('cash',$order_data['really_price']); //加钱
			//加接单时长
			$length_time = $order_data['length_time']*60*60;
			Db::table('hn_apply_project')->where(['uid' => $order_data['acc_id'] , 'project_name' => $order_data['service']])->setInc('length_time', $length_time);
			//加订单数
			Db::table('hn_apply_project')->where(['uid' => $order_data['acc_id'] , 'project_name' => $order_data['service']])->setInc('order_num', 1);

			//给网吧钱
			if($order_data['wb_id'] != 0){
				//①.通过 $order_data['wb_id'] 查hn_netbar(网吧入驻表) 联查hn_cybercafe(网吧管理员表) 查ratio（分成比例） $status['price']*ratio
					$wb_data = Db::table('hn_netbar')
						->alias('n')
						->join('hn_cybercafe c' , 'n.c_id = c.id')
						->field('c.ratio,c.id')
						->where(['n.id' => $order_data['wb_id']])
						->find();

						$wb_money = $order_data['price']*$wb_data['ratio']; //给的钱数
						//②给网吧表 extract 添值
						Db::table('hn_netbar')->where('id',$order_data['wb_id'])->setInc('extract',$wb_money);
						//③.给网吧管理员表 extract not_extract添值
						Db::table('hn_cybercafe')->where('id',$wb_data['id'])->setInc('extract',$wb_money);
						Db::table('hn_cybercafe')->where('id',$wb_data['id'])->setInc('not_extract',$wb_money);
			}
			if($ras){
				//改变订单状态
				$time = time(); //结束时间
				$res = Db::table('hn_order')->where('id', $data['order_id'])->update(['status' => 4,'over_time' => $time]);

				if($res){
					//给陪玩师发结算成功短信
					//

					return json(['code' => 5,'msg' => '订单结算成功，可以选择评论']);
				}else{
					return json(['code' => 6,'msg' => '失败，错误码006']);
				}

			}else{
				return json(['code' => 7,'msg' => '失败，错误码007']);
			}
					
		}
	}


	//超出8分钟订单删除  3分钟订单提醒
	public function order_delete()
	{
		
		$data = Request::instance()->param();

		//1.给用户把钱返余额   2.删除订单   3.给陪玩师发短信      $data['order_id'] 订单ID

		if($data['type'] == 1){
			//提醒接单 1.给陪玩师发短信
			$acc_data = Db::table('hn_order')
							->alias('o')
							->join('hn_user u' , 'o.acc_id  = u.uid')
							->field('u.account,u.nickname')
							->where('o.id' , $data['order_id'])
							->find();


			$phone = $acc_data['account'];
			$data = [
            
            'name' =>$acc_data['nickname'],
            'time' =>'3',
            
        	];
		$this->sendCms($phone,$data,5);
		return 1;

		}else if($data['type'] == 2){
			//删除订单  1.给用户把钱返余额   2.删除订单
			$order_data = Db::table('hn_order')->field('user_id,price')->where('id' , $data['order_id'])->find();
			if($order_data['status'] != 2){
			 	return json(['code' => 2,'msg' => 'SB']);
			 }
			 
			$res = Db::table('hn_user')->where('uid', $order_data['user_id'])->setInc('cash', $order_data['price']);

			if($res){
				Db::table('hn_order')->where('id' , $order_data['user_id'])->delete($data['order_id']);

				$title = '订单信息';
				$text = '您的订单陪玩师没有接单，系统已为您删除，余额返回到了您的账户，如有误请与工作人员联系';
				$send_id = 0;
				$rec_id = $order_data['user_id'];
				$this->message_add($title,$text,$send_id,$rec_id);
			}

		}	

	}

	//评论页面
	public function comment()
	{
		if(Request::instance()->isPost())
		{
			$data = Request::instance()->param();
			

			//1.通过订单ID改变订单状态后删除
			$ras = Db::table('hn_order')->where('id',$data['order_id'])->update(['status' => 5]);
			unset($data['order_id']);
			//2.给评论表添加评论
				//获取到用户ID
			$data['user_id'] = $_SESSION['user']['user_info']['uid'];
			$data['time'] = time();

			$res = Db::table('hn_comment')->insert($data);
			if($res&&$ras){
				return json(['code' => 1,'msg' => '评论成功']);
			}else{
				return json(['code' => 2,'msg' => '评论失败']);
			}

		}
		return $this->fetch('User/comment');
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
		/*
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];

		//查询消息表
		$msg_data = Db::table('hn_msg')->field('title,content,time')->order('id desc')->select();

		//之后会有其他消息类型  完了数组合并
		$this->assign(['msg_data' => $msg_data]);
		*/
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];


		Db::table('hn_message')->where('rec_id',$id)->update(['status' => 1]);

		$request = request();//think助手函数
        $data_get = $request->param();//获取get与post数据
        
        $message = \db('hn_message');//个人消息表
        $where['m.is_del'] = 1;
        $where['m.rec_id'] = $id;
        $w = '';
        if(isset($data_get['type'])&&$data_get['type']){
            if($data_get['type'] ==1){
                $where['m.status'] = 2;//未读
            }elseif($data_get['type'] == 2){
                $where['m.status'] = 1;//已读
            }
            $w = $data_get['type'];
        }

        $data_arr = $message->alias('m')->join('hn_message_text a','m.text_id = a.id')->field('m.id,m.status,a.time,a.text,a.title')->where($where)->order('m.id desc')->paginate(25)->appends($w);
        $page = $data_arr->render();
        $this->assign(
            [
                'data_arr' => $data_arr,
                'page' => $page,
                //'type' => $data_get['type']
            ]
        );

       // var_dump($data_arr);die;
		return $this->fetch('User/msg');
	}

	//改变消息状态Ajax
	public function change_msg()
	{
		$msg_id = Request::instance()->param('id');
		
		$res = Db::table('hn_message')->where('id',$msg_id)->setField('status', 1);

		if($res){
			return 1;
		}else{
			return 2;
		}
	}
	

	//我的接单控制器
	public function receipt()
	{
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];
		//查订单表 查出自己的接单
		$order_data = Db::table('hn_order')
				->alias('o')
				->join('hn_user u','o.user_id = u.uid')
				->field('o.id,o.number,o.price,o.length_time,o.service,o.qq,o.phone,o.status,u.nickname')
				->where('o.acc_id',$id)->where('o.status' , '>' , '0')
				->limit(10)
				->order('o.id desc')
				->select();

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
        $nickname = $_SESSION['user']['user_info']['nickname'];
        $res = $follow->where(['user_id'=>$uid,'followed_user'=>$data_get['followed_user']])->find();
        if($res){
            if($res['status'] == 1){
                $data = [
                    'status'=>2
                ];
                $aa = ['code' => 2,'msg' => '关注成功'];
            }else{
                $data = [
                    'status'=>1
                ];
                $aa = ['code' => 1,'msg' => '关注成功'];

	            $title = '有人关注了你';
				$text = $nickname.'关注了你';
				$send_id = 0;
				$rec_id = $data_get['followed_user'];
				$this->message_add($title,$text,$send_id,$rec_id);
            }
            $red = $follow->where(['user_id'=>$uid,'followed_user'=>$data_get['followed_user']])->update($data);

        }else{
            $data = [
                'user_id'=> $uid,
                'followed_user'=> $data_get['followed_user'],
                'status'=>1
            ];

            //Db::table('hn_user')->field('nickname')->where('uid',$uid)->find();
            $red = $follow->insert($data);
            $aa = ['code' => 1,'msg' => '关注成功'];
            
            $title = '有人关注了你';
			$text = $nickname.'关注了你';
			$send_id = 0;
			$rec_id = $data_get['followed_user'];
			$this->message_add($title,$text,$send_id,$rec_id);
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
		$apply_data = Db::table('hn_apply_project')->field('id,project,project_id,project_grade,project_name,status,time,order_num,pric,type')->where('uid',$uid)->order('id desc')->select();
		
		//组装价格
		foreach($apply_data as $k => $v){

			if($v['project'] == 1)
			{	
				//查到项目初始的价格
				$pric = Db::table('hn_game_grade')->field('pric')->where('id',$v['project_grade'])->find();

				//查到当前陪玩师该项目最高的价格
				$height_pric = $this->pric($v['order_num'],$pric['pric']);

				$count = (($height_pric-$pric['pric'])/5);
				if($count == 0){
					$count = 1;
				}
					
				$data = [];
				
				$data[0] = 8;//$v['pric'];

				for($i=0; $i<=$count; $i++){

					$data[$i+1] = (5*$i)+$pric['pric'];
				}
				

				

//var_dump($data);die;

				$apply_data[$k]['pice'] = $data;
			
			}else if($v['project'] == 2){
				//查到项目初始的价格
				$pric = Db::table('hn_joy_grade')->field('pric')->where('id',$v['project_grade'])->find();

				//查到当前陪玩师该项目最高的价格
				$height_pric = $this->pric($v['order_num'],$pric['pric']);

				$count = ($height_pric-$pric['pric'])/5;
				if($count == 0){
					$count = 1;
				}
					
				$data = [];

				$data[0] = 8;//$v['pric'];
				
					
				for($i=0; $i<=$count; ++$i){

					$data[$i+1] = (5*$i)+$pric['pric'];
				}
				

				

				$apply_data[$k]['pice'] = $data;
			
			}

		}
		//var_dump($apply_data);die;


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

			$key = $uid.'/'.'project'.'/'.md5(microtime()).'.jpg'; //路径
			//$head_img = $this->img.$key; //将此路径存入表单
			$status = $this->cos($file,$key);


			//3.处理音频	
			if($data['video'] != ''){
				$file_video = $data['video'];
				$key_video = $uid.'/'.'project'.'/'.md5(microtime()).'.mp3';  //路径
				$video_data = $this->cos($file_video,$key_video);

				if($video_data['code'] == 0){

					$data['video_url'] = $this->img.$key_video;
				}
			}

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
					

				//删除无用的数据
				unset($data['video']);
				
				//填表
				$res = Db::table('hn_apply_project')->insert($data);

				if($res){
					return json(['code' => 1 , 'msg'=>'提交成功,等待审核。加审核群：783816869']);
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
		$id = Db::table('hn_apply_project')->field('uid,project,project_id')->where('id',$data['id'])->find();
	
		if($uid != $id['uid']){
			return json(['code' => 1 , 'msg' => '非法操作']);
		}
		//修改数据
		$res = Db::table('hn_apply_project')->where('id', $data['id'])->update(['pric' => $data['pric']]);

		//修改陪玩师表数据  不是第一次添加的项目则不给修改
		$service = Db::table('hn_accompany')->field('project,project_id')->where('user_id',$uid)->find();
		if($service['project'] == $id['project']&&$service['project_id'] == $id['project_id']){
			$res = Db::table('hn_accompany')->where('user_id',$uid)->update(['pice' => $data['pric']]);
		}

		if($res){
			return json(['code' => 2 , 'msg' => '修改成功']);
		}else{
			return json(['code' => 3 , 'msg' => '操作失败']);
		}

	}

	//下上架服务项目
	public function up_down()
	{
		$data = Request::instance()->param();
		
		$res = Db::table('hn_apply_project')->where('id',$data['id'])->update(['type' => $data['type']]);

		if($res){
			return json(['code' => 1 , 'msg' => '成功']);
		}else{
			return json(['code' => 2 , 'msg' => '操作失败，请重试']);
		}
	}



	//开启服务
	public function open_service()
	{
		//获取用户ID
		$uid = $_SESSION['user']['user_info']['uid'];
		//查询陪玩师必要信息
		$real = Db::table('hn_accompany')->field('real,up,down,wb_list,address')->where('user_id',$uid)->find();
		
		if($real['down'] == 2){
			$longitude = Db::table('hn_accompany')->field('lng,lat')->where('user_id',$uid)->find();

			$wb_id = explode(',', $real['wb_list']);

			$wb_data = Db::table('hn_netbar')->where(['id' => ['in' , $wb_id] ,'status' => 1 , 'examine_type' => 2])->select();

			$data = [];
	        foreach ($wb_data as $k => $v){
	        	$data[$k] = $v;
	        			//$lat1, $lng1, $lat2, $lng2
	        	$data[$k]['length'] = $this->calcDistance($longitude['lat'],$longitude['lng'],$v['lat'] ,$v['lng']);
	        }
			

		}else{

			//1.查询陪玩师的经纬度
			$longitude = Db::table('hn_accompany')->field('lng,lat')->where('user_id',$uid)->find();


				$array = $this->calcScope($longitude['lat'],$longitude['lng']);

	        	$where['lat'] = array(array('egt',$array['minLat']),array('elt',$array['maxLat']),'and');
	        	$where['lng'] = array(array('egt',$array['minLng']),array('elt',$array['maxLng']),'and');
	        	
	        	$where['status'] = 1;
	        	$where['examine_type'] = 2;

	        $wb_data = db('hn_netbar')->where($where)->select();

	        $data = [];
	        foreach ($wb_data as $k => $v){
	        	$data[$k] = $v;
	        			//$lat1, $lng1, $lat2, $lng2
	        	$data[$k]['length'] = $this->calcDistance($longitude['lat'],$longitude['lng'],$v['lat'] ,$v['lng']);
	        }
	 
	    }

//var_dump($data);die;

		$this->assign(['real' => $real,
						'data' => $data]);
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
					return json(['code' => 1 , 'msg' => '开启成功']);
				}else{
					return json(['code' => 2 , 'msg' => '操作失败，请重试']);
				}

			}else{
				//这里关闭服务
				$res = Db::table('hn_accompany')->where('user_id',$uid)->update(['up' => 1]);

				if($res){
					return json(['code' => 1 , 'msg' => '关闭成功']);
				}else{
					return json(['code' => 2 , 'msg' => '操作失败，请重试']);
				}

			}
		}else if($data['type'] == 2){

				//线下服务
			if($data['xianxia'] == 1){
				//var_dump($data);die;
				//这里开启
				$list = implode(',' , $data['netBar']).',';


				$res = Db::table('hn_accompany')->where('user_id',$uid)->update(['down' => 2,'wb_list' => $list]);

				if($res){
					return json(['code' => 1 , 'msg' => '开启成功']);
				}else{
					return json(['code' => 2 , 'msg' => '操作失败，请重试']);
				}
			}else{
				//var_dump($data);die;
				//这里关闭
				$res = Db::table('hn_accompany')->where('user_id',$uid)->update(['down' => 1,'wb_list' => '']);

				if($res){
					return json(['code' => 1 , 'msg' => '关闭成功']);
				}else{
					return json(['code' => 2 , 'msg' => '操作失败，请重试']);
				}
			}
		}
	}


	//地址修改
	public function address_edit()
	{

		$data = Request::instance()->param();

		//获取用户ID
		$uid = $_SESSION['user']['user_info']['uid'];

		$city = Db::table('hn_accompany')->field('city')->where('user_id',$uid)->find();


		$location = $this->address($city['city'].$data['address']);


		if($location){
            $data['lat'] = $location['lat'];
            $data['lng'] = $location['lng'];
        }

        $res = Db::table('hn_accompany')->where('user_id',$uid)->update($data);

        if($res){
        	return json(['code' => 1 , 'msg' => '修改成功']);
        }else{
        	return json(['code' => 2 , 'msg' => '修改失败']); 
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

	//定时修改订单状态，并分成
	function order_but(){
        $order = db('hn_order');

        $where['status'] = 3;
        $time = 60*60*2;
        $where['single_time'] = array('lt',time()-$time);
        $res = $order->where($where)->field('id,id')->select();
        foreach ($res as $v){
            //1.查出陪玩师ID 和订单 价格 时长
            $order_data = db('hn_order')->field('wb_id,acc_id,really_price,service,length_time')->where('id',$v['id'])->find();
            //2.给陪玩师账户余额加钱   	陪玩师增加时长订单

            $ras = db('hn_user')->where('uid', $order_data['acc_id'])->setInc('cash',$order_data['really_price']); //加钱
            //加接单时长
            $length_time = $order_data['length_time']*60*60;
            db('hn_apply_project')->where(['uid' => $order_data['acc_id'] , 'project_name' => $order_data['service']])->setInc('length_time', $length_time);
            //加订单数
            db('hn_apply_project')->where(['uid' => $order_data['acc_id'] , 'project_name' => $order_data['service']])->setInc('order_num', 1);

            //给网吧钱
            if($order_data['wb_id'] != 0){
                //①.通过 $order_data['wb_id'] 查hn_netbar(网吧入驻表) 联查hn_cybercafe(网吧管理员表) 查ratio（分成比例） $status['price']*ratio
                $wb_data = db('hn_netbar')
                    ->alias('n')
                    ->join('hn_cybercafe c' , 'n.c_id = c.id')
                    ->field('c.ratio,c.id')
                    ->where(['n.id' => $order_data['wb_id']])
                    ->find();

                $wb_money = $order_data['price']*$wb_data['ratio']; //给的钱数
                //②给网吧表 extract 添值
                db('hn_netbar')->where('id',$order_data['wb_id'])->setInc('extract',$wb_money);
                //③.给网吧管理员表 extract not_extract添值
                db('hn_cybercafe')->where('id',$wb_data['id'])->setInc('extract',$wb_money);
                db('hn_cybercafe')->where('id',$wb_data['id'])->setInc('not_extract',$wb_money);
                
            }
            db('hn_order')->where('id', $v['id'])->update(['status' => 4,'over_time' => time()]);
        }

    }


    //声鉴卡
    public function sound_card()
    {

    	//获取用户ID
		$uid = $_SESSION['user']['user_info']['uid'];

		$sound_card = Db::table('hn_identify')->field('identify_card')->where(['status' => 2 , 'uid' => $uid])->find();
		//var_dump($sound_card);die;
    	$this->assign(['sound_card' => $sound_card]);
    	return $this->fetch('User/sound_card');
    }


}




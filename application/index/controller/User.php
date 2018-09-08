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

		define('D', Request::instance()->action());
	}
	//个人中心首页
	public function index()
	{
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];
		
		//查询用户数据
		$user_data = Db::table('hn_user')->field('head_img,sex,nickname,penguin,table,account,change_name')->where('uid',$id)->find();
//var_dump($user_data);die;
	
		//var_dump($user_data);die;
		if(Request::instance()->isPost())
		{
			$user_edit = Request::instance()->param();
			//$user_edit['uid'] = $id;
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
				return json(['code' => 2,'msg' => '失败']);
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
			
			$head_img = 'http://hn-001-1256760691.picbj.myqcloud.com/'.$key; //将此路径存入表单
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
			// //查出旧路径  删除cos上的图片
			// $img_url = Db::table('hn_user')->field('head_img')->where('uid',$id)->find();
			// $img_url =  substr($img_url['head_img'], 44);
			// //调用删除方法 删除图片
			// $this->cos_delete($img_url);
			// //将路径存入用户表 更新字段值
			// $res = Db::table('hn_user')->where('uid',$id)->setField('head_img', $head_img);
			// //重置session的图片路径
			// $_SESSION['user']['user_info']['head_img'] = $head_img;
			// if($res){

			// 	return json(['code' => 0,'msg' => '已提交']);

			// }else{

			// 	return json(['code' => 2,'msg' => '失败']);

			// }

		}else{

			return json(['code' => 3,'msg' => '失败，错误码003']);


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

			var_dump($data);die;
		}

		return $this->fetch();
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
			$gift_data[$k]['explan'] = '充值';
			$gift_data[$k]['time'] = $v['time'];
		}
//var_dump($gift_data);die;
		//合并数组
		$detailed_data = array_merge($recharge_data,$order_data,$gift_data);
		//$type = "money";
		//$this->ranking($data,$type);
		//var_dump($data);die;
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
				$album['img_url'] = 'http://hn-001-1256760691.picbj.myqcloud.com/'.$key; //拼装路径
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
		$key =  substr( $key['img_url'], 44);

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
			//通过订单ID查出订单详情 给陪玩师账户余额加钱
			$order_data = Db::table('hn_order')->field('acc_id,price')->where('id',$data['order_id'])->find();
					//在这里看要不要给算上平台的收益		
			$ras = Db::table('hn_user')->where('uid', $order_data['acc_id'])->setInc('cash',$order_data['price']);

			if($ras){
				//改变订单状态
				$res = Db::table('hn_order')->where('id', $data['order_id'])->update(['status' => 4]);

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
						//这里需要用到多表联查   等礼物表完成后  完善这里
		$gift_data = Db::table('hn_give_gift')->field('user_id,acc_id,gift_id,num,time')->where('user_id',$id)->order('id desc')->select();

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

		//查询数据  注意  是送出的礼物
						//这里需要用到多表联查   等礼物表完成后  完善这里
		$package_data = Db::table('hn_give_gift')->field('gift_id,num,time,egg_num')->where('user_id',$id)->order('id desc')->select();

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
			$real['front_img'] = 'http://hn-001-1256760691.picbj.myqcloud.com/'.$key_zheng;//拼装路径
			$real['back_img'] = 'http://hn-001-1256760691.picbj.myqcloud.com/'.$key_fan;//拼装路径
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

	
	public function follow()
	{
		$this->error('暂未开放','User/index');
	}

	//服务项目
	public function service()
	{
		$this->error('暂未开放','User/index');
	}

	//优惠券
	public function coupon()
	{
		$this->error('暂未开放','User/index');
	}

}




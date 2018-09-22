<?php
/*
*	前台登录注册控制器
*	作者：YG
*	时间：2018.7.13
*/

namespace app\index\controller;
use \think\captcha\Captcha;
use \think\Request;
use \think\Session;
use \think\Cookie;
use \think\Db;

//短信验证码引入阿里云短信命名空间
use Aliyun\Core\Config;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\SendBatchSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;

class Login extends \think\Controller
{
	static $acsClient = null;
	
	//普通登陆控制器
	public function login()
	{	
		//$ip = $this->getIP();
		// $ip =   $this->ipid();
		//$ip = $this->ip();
		//echo $ip; die;
		//登录时获取IP地址
		if(Request::instance()->isPost())
		{
			//$user = db('hn_user');
			//获取到登录提交数据
			$login_data = Request::instance()->param();

//var_dump($login_data);
			//验证验证码是否错误
			if(!captcha_check($login_data['captcha_code']))
			{
				
				return json(['code' => 4, 'msg' => '哇，验证码被妖怪吃了，请重新输入']);
			}

		
			//进行账号（手机号）验证
			$res = Db::table('hn_user')->where('account',$login_data['account'])->find();
			if(!$res)
			{
				return json(['code' => 1,'msg' => '该手机号未注册']);
			}

			
			//进行密码验证
			if(md5($login_data['password']) != $res['password'])
			{				
				return json(['code' => 2, 'msg' => '账号或密码错误']);
			}
			//成功登录 将用户数据存入session
			Session::set('user_info',$res,'user');
 	
 			//取值（用户数据）
			$id = Session::get('user_info','user');
										
			$type = Db::table('hn_user')->where('uid', $id['uid'])->field('type')->find();
			if($type['type'] == 1){
			//改变在线状态
			Db::table('hn_accompany')->where('user_id', $id['uid'])->update(['status' => 1]);
			}

			return json(['code' => 3,'msg' => '登录成功','url' => url('Index/index')]);

		}

		return $this->fetch('Login/login');
	}

	//普通注册控制器
	public function register()
	{  
	

	//注册成功跳转至个人中心的话同样获取IP地址
		
	$register_data = Request::instance()->param();

	if($register_data['password'] == ''){
		return  json(['code' => 5,'msg' => '密码不能为空']);
	}
	//从session里取出code验证码
	$code = Session::get('code','think');

	if(!isset($code['code'])){
		return  json(['code' => 1,'msg' => '自己填的验证码不算']);
	}

	if(empty($register_data['code'])){
		return json(['code' => 2,'msg' => '验证码不能为空']);
	}


	if($code['code'] != $register_data['code']){
		return json(['code' => 3,'msg' => '验证码输入错误']);
		 
	}
	//手机号
	$register_data['account'] = $code['phone'];
	//删除验证码（已经不需要了）
	Session::delete('code');
	unset($register_data['code']);

	//注册时间
	$register_data['time'] = time();
	//加密密码
	$register_data['password'] = md5($register_data['password']);

	//将数据存入用户表  返回主键
	$id = Db::name('hn_user')->insertGetId($register_data);
	//查询出该用户数据
	$res =  Db::table('hn_user')->where('uid',$id)->find();

	Session::set('user_info',$res,'user');

	//新注册的用户给加一个优惠券
		//优惠券ID  用户ID  领取时间  OK
	/*
	$coupon = [];
	$coupon['uid'] = $res['uid'];
	$coupon['cid'] = 1;
	$coupon['time'] = time();
	Db::table('hn_coupon_user')->insert($coupon);
	*/
	return json(['code' => 4,'msg' => '注册成功，正在登录','url' => url('index/index')]);

	}

	//退出登录
	public function loginOut()
	{	
		//取值（用户数据）
		$id = Session::get('user_info','user');

		$type = Db::table('hn_user')->field('type')->find();
		if($type['type'] == 1){
			//改变在线状态
			Db::table('hn_accompany')->where('user_id', $id['uid'])->update(['status' => 2]);

		} 
		//清除Session think  （当前作用域）
		Session::clear('user');
		$this->redirect('Index/index');
	}

	//注册口 调用阿里云短信验证码
	public function code()
	{

		$code['phone'] = Request::instance()->param('phone');
		//检测是否以注册
		$res = Db::table('hn_user')->field('account')->where('account',$code['phone'])->find();

		if($res)
		{
			return json(['code' => 3 , 'msg' => '该账号以注册']);
		}else{
	
			$code['code'] = rand(1000,9999);
			Session::set('code',$code);//将验证码存入Session
			//$_SESSION['think']['code'] = $code;
			
			$sms = 'SMS_141616064';
			//调用短信服务
			$result = $this->sendSms($code['phone'],$code['code'],$sms);
			//将回调对象转化为数组
			$code_data = get_object_vars($result);	
	
			if($code_data['Code'] == 'OK')
	        {
	            return json(['code' => 1,'msg' => '发送成功请注意查收']);
	        }else{
	            return json(['code' => 2,'msg' => '失败']);
	        }
	    }

	}

	//忘记密码
	public function forget()
	{
		$forget_data = Request::instance()->param();

		//从session里取出code验证码 forget_data['phone']  forget_data['code']  forget_data['password']
		$code = Session::get('code','think');

		if(!isset($code)){
			return  json(['code' => 1,'msg' => '自己填的验证码不算']);
		}

		if(empty($forget_data['code'])){
			return  json(['code' => 2,'msg' => '验证码不能为空']);
		}

		if($code != $forget_data['code']){
			return  json(['code' => 3,'msg' => '验证码输入错误']);
		}

		//删除验证码（已经不需要了）
		Session::delete('code');
		unset($forget_data['code']);

		//通过账号将数据更新
		$forget_data['password'] = md5($forget_data['password']);
		$res = Db::table('hn_user')->where('account',$forget_data['phone'])->update([ 'password' => $forget_data['password'] ]);

		if($res){
			return  json(['code' => 4,'msg' => '重置成功，请去登录']);
		}else{
			return  json(['code' => 5,'msg' => '重置失败，错误码005']);
		}

	}


	//忘记密码，短信验证码
	public function forget_code()
	{
		$phone = Request::instance()->param('phone');

		$res = Db::table('hn_user')->field('uid')->where('account',$phone)->find();

		if(!$res){
			return json(['code' => 1,'msg' => '该手机号未注册']);
		}

		$code = rand(1000,9999);
		Session::set('code',$code);//将验证码存入Session

		$sms = 'SMS_141581178';
		//调用验证码服务
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

	//阿里云短信服务（因为Common 不继承   所以重新写一份）
	public function  sendSms($phone,$code,$sms)
	{
		

		require_once EXTEND_PATH.'alisms/vendor/autoload.php';

		Config::load();

		$product = "Dysmsapi";
	
        $domain = "dysmsapi.aliyuncs.com";

        $accessKeyId = "LTAIUTctPQIcLx5d";

        $accessKeySecret = "kWXlikz4MGlJDpeWBaQs9uVnwCRMSF";
      
        $region = 'cn-hangzhou';
      
        $endPointName = 'cn-hangzhou';

        if(static::$acsClient == null)
        {
        	
        	$profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);

        	
        	DefaultProfile::addEndpoint($endPointName,$region,$product,$domain);

        
        	static::$acsClient = new DefaultAcsClient($profile);
        }
		
		$request = new SendSmsRequest();

	
		$request->setPhoneNumbers($phone);
     
		$request->setSignName("火鸟陪玩");
      
        $request->setTemplateCode($sms);
        
        $request->setTemplateParam(json_encode(array(
        	
        		'code' => $code,
        		'product' => 'zsc'

        	),JSON_UNESCAPED_UNICODE));   

        $acsResponse = static::$acsClient->getAcsResponse($request);

        return $acsResponse;

	}

	//图像验证码
	public function captcha_code()
	{
		$config =    [
	    // 验证码字体大小
	    'fontSize'    => 30,    
	    // 验证码位数
	    'length'      => 4,   
	    //验证码高度
	    'imageH'   => 60,
	    //验证码宽度
	    'imageW'	=>200,
	  	//验证码背景
		'useCurve'=>false
		];
		
		$captcha = new Captcha($config);
		$captcha->codeSet = '0123456789';//设置纯数字验证码
		return $captcha->entry();

	}
}











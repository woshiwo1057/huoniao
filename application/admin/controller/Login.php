<?php
/*
*	后台登录注册控制器
*	作者：YG
*	时间：2018.7.13
*/

namespace app\admin\controller;
use \think\Db;
use \think\Cookie;
use \think\Request;
use \think\Session;
use \think\captcha\Captcha; 


class Login extends \think\Controller
{	
	//普通登陆控制器
	public function login()
	{

		//登录提交的数据进入 进行验证  post  Ajax
		if(Request::instance()->isPost())
		{
			$data = Request::instance()->param();
			
			if(empty($data))
			{
				return json(['code' => 1,'msg' => '你他娘的**吧，没东西还要点登录']);
			}

			//var_dump($data);die;
			//验证验证码是否错误
			if(!captcha_check($data['code']))
			{
				return json(['code' => 2, 'msg' => '哇，验证码被妖怪吃了，请重新输入']);
			}

			//判断账号是否正确
			$res = Db::table('hn_admin')->field('account,password,nickname,id,power_id')->where('account',$data['account'])->find();

			if(!$res)
			{
				return json(['code' => 3, 'msg' => '废物 账号/密码 都记不住 食屎吧你']);
			}

			//判断密码是否正确
			
			if(md5($data['password'])!= $res['password'])
			{
				
				return json(['code' => 3, 'msg' => '废物 账号/密码 都记不住 食屎吧你']);
			}

			//管理员信息存入session(当前作用域)
			Session::set('admin_info',$res,'admin');

			//查询管理员权限 存入session
			$power = Db::table('hn_power')->where('id',$res['power_id'])->find();
			$power['check_name'] = explode(',', $power['check_name']);
			Session::set('admin_info',$power['check_name'],'power'); //var_dump($_SESSION['power']['admin_info']);die; 

			//全部通过了给他返回成功数据  并跳转
			return json(['code' => 4,'msg' => '登录成功','url' => url('Index/index')]);

		}
		
		
		return $this->fetch('Login/login');//载入视图
	}

	//普通注册控制器 （后台无注册）
	public function register()
	{

		echo "你进入了错误的页面";
		die;
		
		
	}

	//普通退出登录控制器
	public function loginOut()
	{
		//清除Session think  （当前作用域）
		Session::clear('admin');
		Session::clear('power');
		$this->redirect('login');
	}


	public function code()
	{
		$config =    [
	    // 验证码字体大小
	    'fontSize'    =>    30,    
	    // 验证码位数
	    'length'      =>    4,   
	    //验证码高度
	    'imageH'   => 60,
	    //验证码宽度
	    'imageW'	=>200,
	  	//验证码背景
		'useCurve'=>false
		];
		
		$captcha = new Captcha($config);
		$captcha->codeSet = '0123456789';
		return $captcha->entry();
	}
}
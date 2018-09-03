<?php
/*
*	前台悬赏陪玩控制器
*	作者： YG
*	时间：2018.7.23
*/
namespace app\index\controller;
use \think\Db;
use \think\Request;

class Playing extends Common
{
	public function __construct()
	{
		parent:: __construct(); //集成父级构造函数、
		//验证登录
		
		//$status = ['status' => '已登陆', 'code'=> true];
		if(!isset($_SESSION['user']['user_info']))
		{
			$this->redirect('Index/index',['qingnidenglu' => 1]);
		}

	}	
	//悬赏约玩主页
	public  function index()
	{
		//查询数据
		$playing_data = Db::table('hn_bounty_order')
							->alias('b')
							->join('hn_user u','b.user_id = u.uid')
							->field('b.id,b.project,b.grade,b.time,b.content,b.images,b.money,b.method,u.nickname,u.head_img,u.level')
							->where('status',1)
							->order('id desc')
							->select();
		
		foreach ($playing_data as $k => $v)
		{	$url = [];
			$url = explode(',', $v['images']);
			$playing_data[$k]['images'] = $url;
		}
		//查询banner
		$banner_data = Db::table('hn_banner')->field('id,img_url,link,link_type')->where('status',4)->find();
	
		$this->assign(['playing_data' => $playing_data,
						'banner_data' => $banner_data
			]);		
		return $this->fetch('Playing/index');
	}

	//发布悬赏约玩页面
	public function release()
	{
		$game_data = Db::table('hn_game')->field('id,name')->select();
		$joy_data =  Db::table('hn_joy')->field('id,name')->select();

		$this->assign([
				'game_data' => $game_data,
				 'joy_data' => $joy_data
			]);
		return $this->fetch('Playing/release');
	}

	//等级Ajax
	public function garde_ajax()
	{
		$id = Request::instance()->param();
	
		if(isset($id['game_id'])){
			$id = $id['game_id'];
			$garde_data = Db::table('hn_game_grade')->field('id,type_name')->where('game_id',$id)->select();
			return  json($garde_data);			

		}else{
			$id = $id['joy_id'];
			$garde_data = Db::table('hn_joy_grade')->field('id,type_name')->where('joy_id',$id)->select();
			return  json($garde_data);			

		}
		
	}

	//创建悬赏约玩订单
	public function release_add()
	{
		$data = Request::instance()->param();
		//获取到用户ID
		$data['user_id'] = $_SESSION['user']['user_info']['uid']; 
		//var_dump($data);die;
		//判断之前是否有未完成的订单   status < 4
		$order_data = Db::table('hn_order')->field('status')->where(['user_id' => $data['user_id']])->where('status','<',4)->find();
		if($order_data != ''){
			return json(['code'=>7,'msg'=>'请完成之前的订单后再来下单，谢谢']);
		}
		//判断余额支付与微信扫码支付   type  0:余额  2：微信
		if($data['type'] == 0){
			//余额支付
				//判断账户余额是否充足
			$user_data = Db::table('hn_user')->field('cash')->where('uid',$data['user_id'])->find();

			if($data['money']>$user_data['cash']){
				return json(['code'=>1,'msg'=>'余额不足，请更换支付方式']);
			}
				//减去余额
			$res = Db::table('hn_user')->where('uid', $data['user_id'])->setDec('cash', $data['money']);
			if($res){
				//图片处理
				$images = [];
				foreach ($data['base64box'] as $k => $v)
				{
					$str = time();
					$str .= rand(1000,9999);
					$key = date('Y-m-d').'/'.md5($str).'.jpg'; //路径

					$images[$k] = 'http://uploadimg-1257183241.piccd.myqcloud.com/'.$key;		
					$this->cos($v,$key);
				}
				$images = implode(',', $images);  //图片路径 
				$data['images'] = $images;
				/***********图片处理结束***********/
				//组装数据  存入表单
				unset($data['base64box']);
				$data['time'] = time();
				$data['number'] = 'php'.time() . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//订单号
				$ras = Db::table('hn_bounty_order')->insert($data);

				if($ras){
					return json(['code'=>2,'msg'=>'付款成功']);
				}else{
					return json(['code'=>6,'msg'=>'支付失败，错误码006']);
				}
				
			}else{
				return json(['code'=>3,'msg'=>'付款失败，错误码003']);
			}

		}else if($data['type'] == 2){
			//因为没有回调    所以 die;
			//微信支付   $data['user_id']  $data['money']  $data['number']
			$data['number'] = 'php'.time() . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
			//var_dump($_SERVER['REMOTE_ADDR']);die;
			$code = $this->wechat_pay($data['user_id'],$data['number'],$data['money']);
			//将订单号存入session 进行回调判断
			//$_SESSION['user']['user_info']['numner'] =  $data['number'];
			if($code){
				return json(['code' => 4,'msg' => $code]);
			}else{
				return json(['code' => 5,'msg' =>'失败，错误码005']);
			}
		}




			die;

		//图片处理
		$images = [];
		foreach ($data['base64box'] as $k => $v)
		{
			$str = time();
			$str .= rand(1000,9999);
			$key = date('Y-m-d').'/'.md5($str).'.jpg'; //路径

			$images[$k] = 'http://uploadimg-1257183241.piccd.myqcloud.com/'.$key;		
			//$this->cos($v,$key);
		}
		$images = implode(',', $images);  //图片路径 
		$data['images'] = $images;
		/***********图片处理结束***********/
		//组装数据  存入表单
		unset($data['base64box']);
		$data['time'] = time();
		
	}

	//约玩详情
	public function details()
	{		
		$id = Request::instance()->param('id');

		$playing_data = Db::table('hn_bounty_order')
							->alias('b')
							->join('hn_user u','b.user_id = u.uid')
							->field('b.id,b.project,b.grade,b.time,b.content,b.images,b.money,b.method,b.user_id,u.nickname,u.head_img,u.level')
							->where('id',$id)
							->find();
		$playing_data['images'] = explode(',', $playing_data['images']);

		//查询申请该单的陪玩师
		$acc_data = Db::table('hn_bounty_entered')->field('user_id,head_img')->where('bounty_id',$id)->select();
		//var_dump($acc_data);die;
		$num = count($acc_data);
		$this->assign(['playing_data' => 	$playing_data,
						  'acc_data'  =>  	$acc_data,
						  'num'		  =>	$num
					]);			
		return $this->fetch('Playing/details');

	}

	//参加悬赏约玩
	public function entered()
	{
		
		$bounty_id = Request::instance()->param();
		//获取到用户ID   $data['bounty_id'] 是订单ID
		$user_id = $_SESSION['user']['user_info']['uid'];

		//判断是否自己申请  通过 $data['bounty_id'] 查hn_bounty_order 的user_id 与$user_id 做对比
		$id = Db::table('hn_bounty_order')->field('user_id')->where('id',$bounty_id['bounty_id'])->find();
		
		if($id['user_id'] == $user_id){
			return json(['code' => 5,'msg' => '嘿，自己可不能申请呦']);
		}

		//判断该用户是否重复申请
		$num = Db::table('hn_bounty_entered')->field('id')->where(['bounty_id' => $bounty_id['bounty_id'],'user_id' => $user_id])->find();
		if($num){
			return json(['code' => 3,'msg' => '您已经申请过嘞，请勿重复申请']);
		}
			//通过用户ID查出 头像，填装入表
			$data = Db::table('hn_user')->field('head_img')->where(['uid'=>$user_id,'type'=>1])->find();
			if(!$data){
				return json(['code' => 4,'msg' => '您不是陪玩师，不能申请']);
			}
			$data['bounty_id'] = $bounty_id['bounty_id'];
			$data['user_id'] = $user_id;

			//填表
			$res = Db::table('hn_bounty_entered')->insert($data);

			if($res){
				return json(['code' => 1,'msg' => '成功申请']);
			}else{
				return json(['code' => 2,'msg' => '申请失败，错误码002']);
			}
	}

	//选择陪玩师--输出陪玩师数据
	public function choice()
	{
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];
		//获取到订单ID
		$bounty_id = Request::instance()->param('id');
		//查到所有的申请陪玩师
		$acc_id = Db::table('hn_bounty_entered')->field('user_id')->where('bounty_id',$bounty_id)->select();

		$data = [];

		foreach ($acc_id as $k => $v){
			$data[$k] = Db::table('hn_user')->alias('u')->join('hn_accompany a','a.user_id = u.uid')->field('u.uid,u.nickname,u.head_img,a.status')->where('u.uid',$v['user_id'])->find();
		}

		//查出所有陪玩师的数据
		$this->assign(['data' => $data,'bounty_id' => $bounty_id]);
		return $this->fetch('Playing/choice');
	}

	//选择陪玩师--选择陪玩师
	public function choice_acc()
	{
		//获取订单ID和 陪玩师ID
		$id_data = Request::instance()->param();

		//通过订单ID查出悬赏订单的信息  $id_data['id']订单ID           $id_data['acc_id']陪玩师ID
		$data = Db::table('hn_bounty_order')->field('number,project,phone,qq,wechat,times,time,content,money')->where('id',$id_data['id'])->find();
		if(!$data){
			return json(['code' => 1,'msg' => '失败，错误码001']);
		}
		//数据重组  project->service    times->length_time   content->explain  money->price   $data['acc_id'] = $id_data['acc_id'];  $data['status'] = 1
		//获取自己的ID
		$data['user_id'] = $_SESSION['user']['user_info']['uid'];
		$data['service'] = $data['project'];
		$data['length_time'] = $data['times'];
		$data['explain'] = $data['content'];
		$data['price'] = $data['money'];
		$data['acc_id'] = $id_data['acc_id'];
		$data['status'] = 1;
		//删除没用的数据
		unset($data['project']);
		unset($data['times']);
		unset($data['content']);
		unset($data['money']);
		//将数据填往订单表

		$res = Db::table('hn_order')->insert($data);
		if(!$res){
			return json(['code' => 2,'msg' => '失败，错误码002']);
		}

		//更改悬赏订单状态
		$ras = Db::table('hn_bounty_order')->where('id',$id_data['id'])->setField('status', 2);
		if(!$ras){
			return json(['code' => 3,'msg' => '失败，错误码003']);
		}

		return json(['code' => 4,'msg' => '选择成功']);
		
	}
	
}

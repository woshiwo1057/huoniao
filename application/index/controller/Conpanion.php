<?php
/*
*	前台陪玩师入驻控制器
*	作者： YG
*	时间：2018.7.23
*/
namespace app\index\controller;
use \think\Controller;
use \think\Request;
use \think\Db;

class Conpanion extends Common
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

	}	

	//陪玩师入驻首页
	public function index()
	{	
		//获取到用户ID
		$id = $_SESSION['user']['user_info']['uid'];
		//查省份表
		$province_data = Db::table('hn_province')->field('code,name')->select();

		$this->assign(['province_data' => $province_data]);
		return $this->fetch('Conpanion/index');
	}

	//选择服务项目Ajax  传递数据
	public function project()
	{
		$type = Request::instance()->param('type');
		
		//1为游戏
		//2为娱乐
		if($type == 1){
			$data = Db::table('hn_game')->field('name,id')->select();
		}else{
			$data = Db::table('hn_joy')->field('name,id')->select();
		}

		return json($data);
	}

	//假二级联动，省份 城市表获取
	public function city_ajax()
	{
		$data = Request::instance()->param('province');
		
		//查城市表
		$city_data = Db::table('hn_city')->where('provincecode',$data)->select();
		
		return json($city_data);
	}

	//申请成为陪玩师的数据处理
	public function apply()
	{
		//获取到用户ID
		
			
			$data = Request::instance()->param();

			$data['user_id'] = $_SESSION['user']['user_info']['uid'];
			
			//判断是否有提交
			$record = Db::table('hn_apply_acc')->field('id')->where('user_id',$data['user_id'])->find();

			if($record){
				return json(['code' => 4,'msg'=>'已有记录，请勿再次提交']);
			}else{
			//进行数据处理
				//1.处理城市
				$data['city'];
				$city = Db::table('hn_city')->field('name')->where('code',$data['city'])->find();
				$data['city'] = $city['name'];
				//2.处理图片
				$key = date('Y-m-d').'/'.md5(microtime()).'.jpg'; //路径
				$file = $data['img_data'];
				//3.时间戳
				$data['time'] = time();
				$img_data = $this->cos($file,$key);
				if($img_data['code'] == 0){
					//拼装路径
					$data['data_url'] = 'http://uploadimg-1257183241.piccd.myqcloud.com/'.$key;
					//删除无用的数据
					unset($data['img_data']);

					//填表
					$res = Db::table('hn_apply_acc')->insert($data);

					if($res){
						return json(['code' => 1,'msg'=>'提交成功,等待审核']);
					}else{
						return json(['code' => 2,'msg'=>'提交失败，错误码002']);
					}
				}else{
					return json(['code' => 3,'msg'=>'提交失败,错误码003']);
				}
			}
		
	} 

	//入驻协议
	public function agreement()
	{
		return $this->fetch('Conpanion/agreement');
	}
}

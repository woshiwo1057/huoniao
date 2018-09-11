<?php
/*
*	后台应用公共文件
*	作者：YG
*	时间：2018.7.13
*/
namespace app\admin\controller;
use \think\Request;
use \think\Session;
use \think\Db;
use \think\File;

//引入腾讯对象存储
use \Qcloud\Cos\Client;

class Common extends \think\Controller
{

	public $img = 'https://hn-001-1256760691.picbj.myqcloud.com/'; //腾讯云路径
    public $Intercept = 45; //cos路径截取长度
    
	//构造函数
	public function __construct()
	{
		parent:: __construct(); //集成父级构造函数、

		//验证登录
		session_start();
		if(!isset($_SESSION['admin']['admin_info']))
		{
			$this->redirect('login/login');
		}
		//定义常量
		define('C', Request::instance()->controller());
		define('A', Request::instance()->action());	
		$this->power_check();

		$menu_data = $this->menu_data();
		
		$this->assign([
			'menu_data' => $menu_data
			]);

		
	}


	//等级列表  遍历
	public function levelList($table = "hn_menus",$id = 0,$data_list = [],$level = 0)
	{
		$table_data = Db::table($table)->where(['fid' => $id])->select();

		foreach ($table_data as $k => $v) 
		{
			//将数据存入
			$data_list[$v['id']]['name'] = str_repeat('&nbsp;&nbsp;',$level).'|-'.$v['name'];
			$data_list[$v['id']]['id'] = $v['id'];
			$data_list[$v['id']]['fid'] = $v['fid'];
			
			if(isset($v['type']))
			{
				$data_list[$v['id']]['type'] = $v['type'];
			}

			if(isset($v['module']))
			{
				$data_list[$v['id']]['module'] = $v['module'];
			}

			$data_list[$v['id']]['level'] = $level;

			$data_list = $this->levelList($table,$v['id'],$data_list,$level+1);

		}
		
		return $data_list;

	}

	//后台菜单列表组装数据
	public function  menu_data()
	{
		$menu_data = Db::table('hn_menus')->where(['type'=>1,'module'=>'admin','fid'=>0])->select();
		
		foreach ($menu_data as $k => $v){
			$erji = Db::table('hn_menus')->where(['type'=>1,'module'=>'admin','fid'=>$v['id']])->select();
			$menu_data[$k]['erji'] = $erji;
		}
	
		return $menu_data;

	}

	//判断权限
	public function power_check()
	{
		if($_SESSION['power'])
		{
			//权限存在的时候          ->fidle('id,controller,action')
			$menus_data = Db::table('hn_menus')->where('module','admin')->select();

			$arr = ['Index/index','Login/login','Login/loginOut'];
			foreach($menus_data as $k => $v)
			{
				//进行权限判断  存在的存入$arr
				if(in_array($v['id'],$_SESSION['power']['admin_info'])){
					$arr[] = $v['controller'].'/'.$v['action'];
				}
			}	
			
			if(!in_array(C.'/'.A,$arr))
			{      
			//var_dump(C.'/'.A);
			//var_dump($arr);
				//Session::clear('admin');
				//Session::clear('power');
				$this->error('非法访问','index/index');
			}

		}

	}


	//图片上传（单图）
	public function uploadimg()
	{
		$file = request()->file('cover');
		if(!empty($file)){
		    // 移动到框架应用根目录/public/uploads/ 目录下
		    $info = $file->validate(['size'=>700000,'ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads');

		    if($info){
		        // 成功上传后 获取上传信息
		        // 输出 jpg
		      //  echo $info->getExtension();
		        // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
		        return $info->getSaveName();
		        // 输出 42a79759f284b767dfcb2a0197904287.jpg
		      // echo $info->getFilename(); 
		    }else{
		        // 上传失败获取错误信息
		        return $file->getError();
		    }
		}
		else{
		return '';
		}
	}

	//1.上传图片至腾讯cos   文件形式上传  Tp方法
     public function cos($file,$key)
    {
     /*
     *  cos图片上传
     *  作者： YG
     *  时间：2018.7.26
     */
     
        require_once EXTEND_PATH.'wow/vendor/autoload.php';  //载入腾讯云文件

        $cosClient = new Client(config('tengxunyun'));

        //$file = request()->file('image');
            
        //若初始化 Client 时未填写 appId，则 bucket 的命名规则为{name}-{appid} ，此处填写的存储桶名称必须为此格式
        $bucket = 'hn-001-1256760691'; //YG的桶名 
       //路径组装  http://uploadimg-1257183241.piccd.myqcloud.com/ 为固定 .$key 就OK

      	if($file)
            {
                try
                {
                    $result = $cosClient->putObject(
                        [
                            'Bucket' => $bucket,  //桶名
                            'Key'   => $key,      //上传至服务器，服务器上的路径
                            'Body'  => fopen($file->getInfo()['tmp_name'],'rb')      
                           //'Body'  => fopen($file,'rb')                  
                        ]
                    );
                   
                    $data = ['code' => '0',
                            'msg' => '成功'
                            ];
                    return $data;                  

                }catch (\Exception $e){
                    echo($e);

                    $data = ['code' => '1',
                            'msg' => '失败,错误码1'
                            ];
                    return $data;
                }
          
                //var_dump($file);die;
                
            }else{

                $data = ['code' => '3',
                            'msg' => '失败，错误码3'
                        ];
                return $data;
               
            }  	
        
        
        
    }

    //还有一个cos图片删除的方法  cos图片存储删除控制器
    public function cos_delete($key)
    {
        /*
        *   cos图片删除（单个删除）
        *   作者：YG
        */
        require_once EXTEND_PATH.'wow/vendor/autoload.php';  //载入腾讯云文件

        $cosClient = new Client(config('tengxunyun'));
       
        $bucket = 'hn-001-1256760691'; //YG的桶名

        try {
            $result = $cosClient->deleteObject(array(
                'Bucket' => $bucket,
                'Key' => $key,
            ));
                $data = [   
                            'code' => 1
                        ];
                   return $data;      
        } catch (\Exception $e){
             $data = ['code' => '2',
                       
                    ];
                    return $data;
        }
    }

}
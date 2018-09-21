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

	/*******************************************************站内信开始************************************************************************/
     
    //是否有新消息(此方法在构造函数中调用)
    function is_new(){
        $id = $_SESSION['user']['user_info']['uid'];
       // $users = Session::get('users');
        $message = \db('hn_message');//个人消息表
        $res= $message->where(['rec_id'=> $id,'status'=>2])->count();
        return $res;
    }

    //普通站内信发送（一般只发送一个人）
    /*
     * $title:标题
     * $text：内容
     * $send_id：发送者id（系统发送时，此ID为0）
     * $rec_id：接收者id
     *
     * */
    function message_add($title,$text,$send_id,$rec_id){
        $message = \db('hn_message');//个人消息表
        $message_text = \db('hn_message_text');
        $text_id = $this->text_add($title,$text);
        $data = [
            'text_id' => $text_id,
            'send_id'   => $send_id,
            'rec_id'    => $rec_id,
            'addtime'=> time()
        ];
        $res = $message->insert($data);
    }


    //消息文本插入
    function text_add($title,$text){
        $message_text = \db('hn_message_text');

        $data = [
            'title'=> $title,
            'text' => $text,
            'time' => time()
        ];
        $res = $message_text->insertGetId($data);
        return $res;
    }

    /*************************************************************站内信结束************************************************************/


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

     //1.上传图片至腾讯cos   文件形式上传  base64
    public function coss($file,$key)
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
                        //'Body'  => fopen($file->getInfo()['tmp_name'],'rb')
                        'Body'  => fopen($file,'rb')
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


     //声鉴卡生成
    function identify_card($data_arr){
        /*$data_arr = [
            'head_img' => __IMG__.'touxiang.png',
            'name' => '赵顺超',
            'type' => '1',
            'trait' => '少女音',
            'acc_name' => '月月',
            'timbre_value' => '60%',//音色总分
            'timbre_1' => '少女',//主音色1
            'timbre_2' => '萝莉',//主音色2
            'timbre_val_1' => '60%',//主音色1总分
            'timbre_val_2' => '40%',//主音色2总分
            'tone_1' => '磁性迷人沙哑音',//辅音色1
            'tone_2' => '举步轻盈风铃音',//辅音色2
            'tone_3' => '玫瑰性感女神音',//辅音色3
            'tone_val_1' => '10%',//辅音色1总分
            'tone_val_2' => '20%',//辅音色2总分
            'tone_val_3' => '10%',//辅音色3总分
            'mate' => '大叔',//最佳伴侣
            'value_1' => '9',//市场值
            'value_2' => '9',//诱惑值
            'value_3' => '9',//活跃值
            'value_4' => '8',//心动值
        ];*/
        $headimg = $data_arr['head_img'];
        if($data_arr['type']==1){
            $filename = ROOT_PATH.'public/uploads/admin/bj1.jpg';//背景图;
        }else{
            $filename = ROOT_PATH.'public/uploads/admin/bj2.jpg';//背景图;
        }

        $width=700;
        $height=600;

        $QR = imagecreatefromstring(file_get_contents($filename));
        //$logo = $image_p;
        $headimg = imagecreatefromstring(file_get_contents($headimg));
        $logo_width = 280;//logo图片宽度
        $logo_height = 280;//logo图片高度
        $head_width = imagesx($headimg);
        $head_height = imagesy($headimg);
        //重新组合图片并调整大小
        imagecopyresampled($QR, $headimg, 58, 68, 0, 0, $logo_width,$logo_height, $head_width, $head_height);

        $background_color = imagecolorallocate($QR, 255, 255, 255);
        $text_color1 = imagecolorallocate($QR, 255, 255, 255);
        if($data_arr['type']==1){
            $text_color2 = imagecolorallocate($QR, 0, 11, 37);
        }else{
            $text_color2 = imagecolorallocate($QR, 19, 0, 37);
        }
        $text_color3 = imagecolorallocate($QR, 139, 139, 139);
        $font = ROOT_PATH.'public/uploads/admin/hkcd.otf';
        $fontBox1 = imagettfbbox(25, 0, $font, $data_arr['name']);//文字水平居中实质
        //$fontBox2 = imagettfbbox(20, 0, $font, $data_arr['trait']);//文字水平居中实质
        //var_dump($fontBox);die;
        imagettftext($QR, 25, 0, ceil(500 - ($fontBox1[2]/2)), 105, $text_color3, $font, $data_arr['name']);
        imagettftext($QR, 25, 0, ceil(500 - ($fontBox1[2]/2)+2), 107, $text_color1, $font, $data_arr['name']);
        //@imagettftext($QR, 20, 0, ceil(545 - ($fontBox2[2]/2)), 105, $text_color1, $font, $data_arr['trait']);
        imagettftext($QR, 14, 0, 460, 170, $text_color2, $font, $data_arr['acc_name']);
        imagettftext($QR, 14, 0, 460, 210, $text_color2, $font, $data_arr['trait']);
        imagettftext($QR, 14, 0, 480, 250, $text_color2, $font, $data_arr['timbre_value']);
        imagettftext($QR, 14, 0, 465, 288, $text_color2, $font, $data_arr['timbre_1']);
        imagettftext($QR, 14, 0, 505, 288, $text_color2, $font, $data_arr['timbre_val_1']);
        imagettftext($QR, 14, 0, 560, 288, $text_color2, $font, $data_arr['timbre_2']);
        imagettftext($QR, 14, 0, 605, 288, $text_color2, $font, $data_arr['timbre_val_2']);
        imagettftext($QR, 14, 0, 465, 328, $text_color2, $font, $data_arr['tone_1']);
        imagettftext($QR, 14, 0, 465, 368, $text_color2, $font, $data_arr['tone_2']);
        imagettftext($QR, 14, 0, 465, 408, $text_color2, $font, $data_arr['tone_3']);
        imagettftext($QR, 14, 0, 603, 328, $text_color2, $font, $data_arr['tone_val_1']);
        imagettftext($QR, 14, 0, 603, 368, $text_color2, $font, $data_arr['tone_val_2']);
        imagettftext($QR, 14, 0, 603, 408, $text_color2, $font, $data_arr['tone_val_3']);
        imagettftext($QR, 14, 0, 480, 443, $text_color2, $font, $data_arr['mate']);
        imagettftext($QR, 14, 0, 450, 483, $text_color2, $font, $data_arr['value_1']);
        imagettftext($QR, 14, 0, 600, 483, $text_color2, $font, $data_arr['value_2']);
        imagettftext($QR, 14, 0, 450, 520, $text_color2, $font, $data_arr['value_3']);
        imagettftext($QR, 14, 0, 600, 520, $text_color2, $font, $data_arr['value_4']);
        imagepng ($QR);
        $image_data = ob_get_contents ();
        ob_end_clean ();
        $image_data_base64 = "data:image/png;base64,". base64_encode ($image_data);
        return( $image_data_base64);exit;

    }

}
<?php
/*
*	前台应用公共文件
*	作者：YG
*	时间：2018.7.13
*/
namespace app\index\controller;
use \think\Request;
use \think\Session;
use \think\Db;


//短信验证码引入阿里云短信命名空间
use Aliyun\Core\Config;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\SendBatchSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;

//引入腾讯对象存储
use \Qcloud\Cos\Client;


class Common extends \think\Controller
{

	static $acsClient = null;
    public $img = 'https://hn-001-1256760691.picbj.myqcloud.com/'; //腾讯云图片路径
    public $Intercept = 45; //cos路径截取长度
	 

	//构造函数
	public function __construct()
	{
		parent:: __construct(); //集成父级构造函数
		session_start();
        	
		$menus_index = $this->menus_index();

        $num = 0;

        if(isset($_SESSION['user'])){
            $less_user_data = $this->user_data();
            $num = $this->is_new();

            $this->assign(['less_user_data' => $less_user_data,
                            'num' => $num]);
        }
        
        
		$index = 'index';
		$this->assign([
			'menus_index' =>  $menus_index,
			'index' => $index,
            'num' => $num
					]);
		
		
		define('A', Request::instance()->action());
		define('C', Request::instance()->controller());
		
	}

    //用户的基本信息
    public function user_data()
    {
        //获取到用户ID
        $id = $_SESSION['user']['user_info']['uid'];

        //查出用户的 头像 昵称 ID   等级  余额  鸟蛋
        $user_data = Db::table('hn_user')->field('uid,nickname,head_img,level,cash,currency,neice,type')->where('uid',$id)->find();

       /* if($user_data['type'] == 1){
            Db::table('hn_accompany')->fidle('')
        }*/
        return $user_data;
    }

	//公共前台首页输出信息（导航栏）
	public function menus_index()
	{
		$menus_data  = Db::table('hn_menus')->field('name,module,controller,action')->where(['module' =>'index','type' => 1])->select();

    	//Db::table('think_user')->field('id,title,content')->select();
    	return $menus_data;
	}

	//前台公共脚部信息（公司信息）
	public function foot_index()
	{	
		//公共脚部决定不从数据库输出，直接给url路径  此控制器暂时关闭

		//$foot_data = Db::table()->field->select();

		//return $foot_data;

	}

    //去重
    public function out_repeat($arr,$key){     
            //建立一个目标数组  
            $res = array();        
            foreach ($arr as $value) {           
               //查看有没有重复项  
               if(isset($res[$value[$key]])){  
                  unset($value[$key]);  //有：销毁  
               }else{    
                  $res[$value[$key]] = $value;  
               }    
            }  
            return $res;  
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
       return  $res = $message->insert($data);
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
	// 截取前8  用于排行榜   
			  //参数说明: 用户信息    排序字段
	public function ranking($data,$type)
	{

		//开始进行2维数组根据数字排序从大到小
		foreach ($data as $k => $v) 
		{
			$mogul_day[$k] = $v[$type];
		}

		array_multisort($mogul_day,SORT_DESC,$data);


		//截取数组
		$data = array_slice($data, 0,8);
		return $data;
	}

    public function infinite_ranking($data,$type)
    {

        //开始进行2维数组根据数字排序从大到小
        foreach ($data as $k => $v) 
        {
            $mogul_day[$k] = $v[$type];
        }

        array_multisort($mogul_day,SORT_DESC,$data);


        //截取数组
        $data = array_slice($data, 0);
        return $data;
    }

//陪玩师价格计算方式    订单数  项目初始价格
    public function pric($order_num,$pric){
        if($order_num>=500){
            return $pric+30;
        }else if($order_num>=300){
           return $pric+25;
        }else if($order_num>=200){
           return $pric+20;
        }else if($order_num>=100){
            return $pric+15;
        }else if($order_num>=20){
            return $pric+10;
        }else if($order_num>=10){
            return $pric+5;
        }else{
            return $pric;
        }
    }

    //声音鉴定订单生成
    public function voice_order($acc_id,$price){
        $uid = $_SESSION['user']['user_info']['uid'];
        //$request = request();//think助手函数
       // $data_get = $request->param();//获取get与post数据

        $identify = \db('hn_identify');

        $data = [
            'uid'=> $uid,
            'order_id' => time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'acc_id' => $acc_id,
            'price' => $price,
            'addtime' => time(),
            'identify'=> '声音鉴定',
            'status' => 1
        ];
        $res = $identify->insert($data);
        if($res){
            return 1;
        }else{
            return 2;
        }
    }



	//手机验证码			手机号  验证码
	public function  sendSms($phone,$code,$sms)
	{
		/*
		*	短信接口
		*	作者：YG
		*	时间：20187.24
		*/

		require_once EXTEND_PATH.'alisms/vendor/autoload.php';  //载入阿里云文件

		Config::load();

		//产品名称:云通信流量服务API产品,开发者无需替换
		$product = "Dysmsapi";

		//产品域名,开发者无需替换
        $domain = "dysmsapi.aliyuncs.com";

        //设置自己的信息
        $accessKeyId = "LTAIUTctPQIcLx5d";

        $accessKeySecret = "kWXlikz4MGlJDpeWBaQs9uVnwCRMSF";

        //官方说暂时不支持其他地区的  只有这一个
        $region = 'cn-hangzhou';

        //服务节点
        $endPointName = 'cn-hangzhou';

        if(static::$acsClient == null)
        {
        	//初始化acsClient,暂不支持region化
        	$profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);

        	//增加服务节点
        	DefaultProfile::addEndpoint($endPointName,$region,$product,$domain);

        	//初始化AcsClient用于发送请求
        	static::$acsClient = new DefaultAcsClient($profile);
        }



		// 初始化SendSmsRequest实例用于设置发送短信的参数
		$request = new SendSmsRequest();

		//可选-启用https协议
        //$request->setProtocol("https");

        //设置接收短信的号码
		$request->setPhoneNumbers($phone);
        //设置签名名称
		$request->setSignName("火鸟陪玩");
        //设置模板CODE
        $request->setTemplateCode($sms);

        //设置验证码
        $request->setTemplateParam(json_encode(array(
        		'code' => $code,
        		'product' => 'zsc'

        	),JSON_UNESCAPED_UNICODE));

        //设置流水号  可写可不写
        //$request->setOutId('');


        //发起访问请求
        $acsResponse = static::$acsClient->getAcsResponse($request);

        return $acsResponse;

	}

	public function ip()
    {
        $ip=false;
        if(!empty($_SERVER["HTTP_CLIENT_IP"]))
        {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if($ip)
            { 
                array_unshift($ips, $ip); $ip = FALSE; 
            }
            for($i = 0; $i < count($ips); $i++)
            {
                if (!eregi ("^(10│172.16│192.168).", $ips[$i]))
                {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }


	public function getIP()
    {
        static $realip;
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $realip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } elseif (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }
        return $realip;
    }


    //1.上传图片至腾讯cos   文件形式上传 base64
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
             //应该是上传的路径文件名文件名

       //    http://uploadimg-1257183241.piccd.myqcloud.com/2018-08-09/9ecb98ce31fee5a16f1ebd857239abfb.jpg  万象优图上图片路径

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
                    //$result['ObjectURL']  文件路径
                    //var_dump($result);
                   // var_dump($result['ObjectURL']); //是不是把这个数据放到下面的src里
                   
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

    //还有一个cos文件删除的方法  cos文件存储删除控制器
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
           // echo($e);
        }
    }

    //扫码支付
    
    public function wechat_pay($user_id,$number,$pice)
    {
        //扫二维码进行支付 
        require_once EXTEND_PATH.'wechatpay/lib/WxPay.Api.php'; //载入微信支付相关文件
        require_once EXTEND_PATH.'wechatpay/example/WxPay.NativePay.php'; //载入微信支付相关文件
        require_once EXTEND_PATH.'wechatpay/example/log.php';//载入微信支付相关文件
        require_once EXTEND_PATH.'wechatpay/lib/WxPay.Data.php'; //载入微信支付相关文件
        //模式二
        /**
         * 流程：
         * 1、调用统一下单，取得code_url，生成二维码
         * 2、用户扫描二维码，进行支付
         * 3、支付完成之后，微信服务器会通知支付成功
         * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
         */
        //初始化日志
       
            $logHandler= new \CLogFileHandler(EXTEND_PATH.'wechatpay/example/log.php'.date('Y-m-d').'.log');
            $log = \Log::Init($logHandler, 15);
            //
            $notify = new \NativePay();
            $input = new \WxPayUnifiedOrder();
            $input->SetBody("充值使我快乐");    //设置商品或支付单简要描述
            $input->SetAttach($user_id);   //设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据  我这里设置成用户ID？
            $input->SetOut_trade_no($number);  //设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
            $input->SetTotal_fee($pice*100);  //设置订单总金额，只能为整数，详见支付金额
            $input->SetTime_start(date("YmdHi"));  //设置订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。其他详见时间规则
            $input->SetTime_expire(date("YmdHis", time() + 180));  //设置订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则
            //$input->SetGoods_tag("test");  //设置商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠
            $input->SetNotify_url("http://localhost/huoniao/extends/wechatpay/example/native_notify.php");//设置接收微信支付异步通知回调地址
            $input->SetTrade_type("NATIVE"); //设置取值如下：JSAPI，NATIVE，APP，详细说明见参数规定
            $input->SetProduct_id("0"); //设置trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
            ///var_dump($input);die;
            $result = $notify->GetPayUrl($input);
            //var_dump($result);die;
            return urlencode($result['code_url']);

            //需要生成变量的值
                //1.充值的用户ID   $user_id
                //2.订单号        $number
                //3.金额（微信是以分为单位1的） $pice
                //4.商品ID   不传  默认为0 
              
    }

    public function wechat_query()
    {
        /*
        require_once EXTEND_PATH.'wechatpay/example/WxPay.MicroPay.php'; //载入微信支付相关文件

        $query_order = new \MicroPay();

       
        $succCode = 0;

        $data = $query_order->query($out_trade_no,$succCode);

        var_dump($data);die;
        return $data;*/
        
        
        require_once EXTEND_PATH.'wechatpay/lib/WxPay.Api.php';
        //require_once EXTEND_PATH.'wechatpay/lib/WxPay.Data.php'; //载入微信支付相关文件
        require_once EXTEND_PATH.'wechatpay/example/log.php';
        require_once EXTEND_PATH.'wechatpay/example/WxPay.Config.php';
        //初始化日志
        $logHandler= new \CLogFileHandler(EXTEND_PATH.'wechatpay/example/log.php'.date('Y-m-d').'.log');
        $log = \Log::Init($logHandler, 15);
/*
        $transaction_id = 4200000184201808260151070018;
      
        if($transaction_id){
            try {
               //$transaction_id = $_REQUEST["transaction_id"];
                $input = new \WxPayOrderQuery();
                $input->SetTransaction_id($transaction_id);
                $config = new \WxPayConfig();
               var_dump(\WxPayApi::orderQuery($config, $input));
            } catch(Exception $e) {
                Log::ERROR(json_encode($e));
            }
            exit();
        }
*/
      $out_trade_no = 153537968496188;
        if($out_trade_no){
            try{
               // $out_trade_no = $out_trade_no;
                $input = new \WxPayOrderQuery();
                $input->SetOut_trade_no($out_trade_no);
                $config = new \WxPayConfig();
                $order_data = \WxPayApi::orderQuery($config, $input);
               var_dump($order_data);die;
            } catch(Exception $e){
                Log::ERROR(json_encode($e));
            }
            exit();
        }




    }

    /*
     *
     *
     *地图位置检索
     * query:地理位置
     * region：所在地区
     *
     */
    function suggestion(){
        $request = request();//think助手函数
        $data_get = $request->param();//获取get与post数据
        $query = urlencode($data_get['query']);
        $region = urlencode($data_get['region']);
        $url = 'http://api.map.baidu.com/place/v2/suggestion?query='.$query.'&region='.$region.'&city_limit=true&output=json&ak='._AK_;
        $data = curlGet($url);
        return $data;
    }

    /*
     *
     *地理编码,返回所在的经纬度
     *
     */

    function address($address){
        $address = urlencode($address);
        $url = 'http://api.map.baidu.com/geocoder/v2/?address='.$address.'&output=json&ak='._AK_;
        $data = curlGet($url);
        $data = json_decode($data,true);
        if($data['status']==0){
            $location = $data['result']['location'];
        }else{
            $location = null;
        }
        return $location;
    }



    /**
     * 根据经纬度和半径计算出范围
     * @param string $lat 经度
     * @param String $lng 纬度
     * @param float $radius 半径
     * @return Array 范围数组
     */
    public function calcScope($lat, $lng) {
        $radius = 5000;
        $PI = 3.14159265;
        $degree = (24901*1609)/360.0;
        $dpmLat = 1/$degree;

        $radiusLat = $dpmLat*$radius;
        $minLat = $lat - $radiusLat;       // 最小经度
        $maxLat = $lat + $radiusLat;       // 最大经度

        $mpdLng = $degree*cos($lat * ($PI/180));
        $dpmLng = 1 / $mpdLng;
        $radiusLng = $dpmLng*$radius;
        $minLng = $lng - $radiusLng;      // 最小纬度
        $maxLng = $lng + $radiusLng;      // 最大纬度

        /** 返回范围数组 */
        $scope = array(
            'minLat'    =>  $minLat,
            'maxLat'    =>  $maxLat,
            'minLng'    =>  $minLng,

            'maxLng'    =>  $maxLng
        );
        return $scope;
    }

    /**
     * 获取两个经纬度之间的距离
     * @param  string $lat1 经一
     * @param  String $lng1 纬一
     * @param  String $lat2 经二
     * @param  String $lng2 纬二
     * @return float  返回两点之间的距离
     */
    public function calcDistance($lat1, $lng1, $lat2, $lng2) {
        /** 转换数据类型为 double */
        $lat1 = doubleval($lat1);
        $lng1 = doubleval($lng1);
        $lat2 = doubleval($lat2);
        $lng2 = doubleval($lng2);
        /** 以下算法是 Google 出来的，与大多数经纬度计算工具结果一致 */
        $theta = $lng1 - $lng2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return (int)($miles * 1.609344 * 1000);
    }

    //手机验证码              手机号  参数数组      模板
    public function  sendSmss($phone,$templateParam,$sms)
    {
        /*
        *   短信接口
        *   作者：YG
        *   时间：20187.24
        */

        require_once EXTEND_PATH.'alisms/vendor/autoload.php';  //载入阿里云文件

        Config::load();

        //产品名称:云通信流量服务API产品,开发者无需替换
        $product = "Dysmsapi";

        //产品域名,开发者无需替换
        $domain = "dysmsapi.aliyuncs.com";

        //设置自己的信息
        $accessKeyId = "LTAIUTctPQIcLx5d";

        $accessKeySecret = "kWXlikz4MGlJDpeWBaQs9uVnwCRMSF";

        //官方说暂时不支持其他地区的  只有这一个
        $region = 'cn-hangzhou';

        //服务节点
        $endPointName = 'cn-hangzhou';

        if(static::$acsClient == null)
        {
            //初始化acsClient,暂不支持region化
            $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);

            //增加服务节点
            DefaultProfile::addEndpoint($endPointName,$region,$product,$domain);

            //初始化AcsClient用于发送请求
            static::$acsClient = new DefaultAcsClient($profile);
        }



        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();

        //可选-启用https协议
        //$request->setProtocol("https");

        //设置接收短信的号码
        $request->setPhoneNumbers($phone);
        //设置签名名称
        $request->setSignName("火鸟陪玩");
        //设置模板CODE
        $request->setTemplateCode($sms);

        //设置验证码
        if($templateParam) {
            $request->setTemplateParam(json_encode($templateParam));
        }

        //设置流水号  可写可不写
        //$request->setOutId('');


        //发起访问请求
        $acsResponse = static::$acsClient->getAcsResponse($request);

        return $acsResponse;

    }

    //发送通知短信 $type  ：1：提醒用户陪玩师接单通知  2：提醒陪玩师已接取线下订单  3：提醒陪玩师通过审核 4：用户订单超时提醒  5：陪玩师订单超时提醒  6：陪玩师有新订单提醒
    function sendCms($phone,$data,$type){

        $sms_arr = ['SMS_145593533','SMS_145593493','SMS_145593448','SMS_145598482','SMS_145598470','SMS_145598411'];

       $res =  $this->sendSmss($phone,$data,$sms_arr[$type-1]);
       return $res;
        exit;
    }
    /*
    $phone ='13186119291';
        $data = [
            'player' =>'围棋少年',
            'players' =>'围棋少年',
            'name' =>'孙浩',
            'time' =>'8',
            'location' =>'老板的家里',
        ];


        //调用验证码服务
        $result = $this->sendCms($phone,$data,2);//1：提醒用户陪玩师接单通知  2：提醒陪玩师已接取线下订单  3：提醒陪玩师通过审核 4：用户订单超时提醒  5：陪玩师订单超时提醒  6：陪玩师有新订单提醒

    */


}
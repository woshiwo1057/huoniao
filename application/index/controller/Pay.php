<?php
/*
*	微信支付测试控制器
*	作者： YG
*	时间：2018.8.22
*/

namespace app\index\controller;
use \think\Controller;
use \think\Request;
use \think\Db;
//use \think\Loader;

/*
require_once EXTEND_PATH.'wechatpay/lib/WxPay.Api.php'; //载入微信支付相关文件
require_once EXTEND_PATH.'wechatpay/lib/WxPay.Config.Interface.php'; //载入微信支付相关文件
require_once EXTEND_PATH.'wechatpay/example/WxPay.JsApiPay.php'; //载入微信支付相关文件
require_once EXTEND_PATH.'wechatpay/example/log.php'; //载入微信支付相关文件
*/

//扫二维码进行支付 
require_once EXTEND_PATH.'wechatpay/lib/WxPay.Api.php'; //载入微信支付相关文件
require_once EXTEND_PATH.'wechatpay/example/WxPay.NativePay.php'; //载入微信支付相关文件
require_once EXTEND_PATH.'wechatpay/example/log.php';//载入微信支付相关文件
require_once EXTEND_PATH.'wechatpay/lib/WxPay.Data.php'; //载入微信支付相关文件
require_once EXTEND_PATH.'wechatpay/example/WxPay.MicroPay.php';//载入微信支付相关文件




class Pay extends Common
{
	public function wechat()
	{
		//$url    = \think\Url::build('wxpayNotify', '', true, true);
		//var_dump($url);die;
		
		return $this->fetch();
	}

	public function wechatpay()
	{


		$data['number'] = time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
		//将订单号存入session  做查询跳转使用
		$_SESSION['user']['user_info']['order_number'] = $data['number'];
		//var_dump($_SESSION['user']['user_info']['order_number']);die;
		//模式二
		/**
		 * 流程：
		 * 1、调用统一下单，取得code_url，生成二维码
		 * 2、用户扫描二维码，进行支付
		 * 3、支付完成之后，微信服务器会通知支付成功
		 * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
		 */
		//初始化日志

		if(Request::instance()->isPost()){
			//var_dump(EXTEND_PATH);die;
			$logHandler= new \CLogFileHandler(EXTEND_PATH.'wechatpay/example/log.php'.date('Y-m-d').'.log');
			$log = \Log::Init($logHandler, 15);

			$notify = new \NativePay();
			$input = new \WxPayUnifiedOrder();
			$input->SetBody("快乐的加班");    //设置商品或支付单简要描述
			$input->SetAttach("1");   //设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据  我这里设置成用户ID？
			$input->SetOut_trade_no($data['number']);   //设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
			$input->SetTotal_fee("1");  //设置订单总金额，只能为整数，详见支付金额
			$input->SetTime_start(date("YmdHis"));  //设置订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。其他详见时间规则
			$input->SetTime_expire(date("YmdHis", time() + 180));  //设置订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则
			//$input->SetGoods_tag("test");  //设置商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠
			$input->SetNotify_url("https://csq.huoniaopeiwan.com/");   //设置接收微信支付异步通知回调地址
			$input->SetTrade_type("NATIVE"); //设置取值如下：JSAPI，NATIVE，APP，详细说明见参数规定
			$input->SetProduct_id("6"); //设置trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
				//http://localhost/huoniao/public/index.php/index/pay/wechat

			$result = $notify->GetPayUrl($input);
            //var_dump($result);die;
			//var_dump(urlencode($result['code_url']));die;
			return urlencode($result['code_url']);
			
		}
	}

	public function query()
	{
		//查询订单状态
		/*
		$qurey = new \MicroPay();
		
		$order = $_SESSION['user']['user_info']['order_number'];
		$num ='';

		$data = $qurey->query($order,$num);
		*/
		//var_dump($data);
		//var_dump($_SESSION['user']['user_info']['order_number']);
	
			return json(['daw' => 123,'msg'=>'zhaoshunchao']);
		
	}

	//充值回调
	public function recharge()
	{
		$qurey = new \MicroPay();
		$order_num = $_SESSION['user']['user_info']['order_number'];
		$num ='';
		$data = $qurey->query($order_num,$num);

		if($data['trade_state_desc'] == '支付成功'){
			//查询订单状态是否为已支付                         状态  用户ID 充值的鸟蛋数  充值的金额
			$status = Db::table('hn_recharge_diamond')->field('status,user_id,diamond,money')->where('number' , $order_num)->find();

			if($status['status'] == 2){
				//没有付款 且付款成功
				//1.改变订单状态  status   给用户加鸟蛋  currency 与土豪值  mogul_day  mogul
				Db::table('hn_recharge_diamond')->where('number', $order_num)->update(['status' => 1]);

				Db::table('hn_user')->where('uid', $status['user_id'])->setInc('currency', $status['diamond']);//加鸟蛋

				Db::table('hn_user')->where('uid', $status['user_id'])->setInc('mogul', $status['money']);//加总土豪值

				Db::table('hn_user')->where('uid', $status['user_id'])->setInc('mogul_day', $status['money']);//加当天土豪值

				return json(['code' => 'Ok' , 'msg' => '支付成功']);
			}else{

				return '123456';
			}

			return json(['daw' => 123,'msg'=>'zhaoshunchao']);
		}

	}

	//订单回调
	public function pt_order()
	{

		$qurey = new \MicroPay();
		$order_num = $_SESSION['user']['user_info']['order_number'];
		$num ='';

		$data = $qurey->query($order_num,$num);
		
		if($data['trade_state_desc'] == '支付成功'){
			
			//查询订单状态  是否已经支付成功
			$status = Db::table('hn_order')->field('status,acc_id,user_id,price,wb_id')->where('number',$order_num)->find();

			if($status['status'] == 0)
			{	

			
				//订单提交并未支付的时候
				//1.给网吧钱
				if($status['wb_id'] != 0){

					//①.通过 $data['wb_id'] 查hn_netbar(网吧入驻表) 联查hn_cybercafe(网吧管理员表) 查ratio（分成比例） $status['price']*ratio
					$wb_data = Db::table('hn_netbar')
						->alias('n')
						->join('hn_cybercafe c' , 'n.c_id = c.id')
						->field('c.ratio,c.id')
						->where(['n.id' => $data['wb_id']])
						->find();

						$wb_money = $data['price']*$wb_data['ratio']; //给的钱数
						//②给网吧表 extract 添值
						Db::table('hn_netbar')->where('id',$data['wb_id'])->setInc('extract',$wb_money);
						//③.给网吧管理员表 extract not_extract添值
						Db::table('hn_cybercafe')->where('id',$wb_data['id'])->setInc('extract',$wb_money);
						Db::table('hn_cybercafe')->where('id',$wb_data['id'])->setInc('not_extract',$wb_money);

				}
				//2.给用户添加土豪值
				Db::table('hn_user')->where('uid', $status['user_id'])->setInc('mogul', $status['price']);//加总土豪值

				Db::table('hn_user')->where('uid', $status['user_id'])->setInc('mogul_day', $status['price']);//加当天土豪值

				//3.判断用户充值的等级
				$mogul = Db::table('hn_user')->field('mogul,level')->where('uid', $status['user_id'])->find();

				$level = Db::table('hn_recharge_level')->field('level')->where('money','>',$mogul['mogul'])->select();
				$level = $level['0']['level']-1;
				if($mogul['level'] !=  $level){
					Db::table('hn_user')->where('uid',$status['user_id'])->setField('level', $level);
				}

				//4.给陪玩师发站内信
				$title = '您有新的订单';
				$text = '快去接单吧！！！！！！！';
				$send_id = 0;
				$rec_id = $status['acc_id'];
				$this->message_add($title,$text,$send_id,$rec_id);	
				//4.改变订单状态	
				Db::table('hn_order')->where('number', $order_num)->update(['status' => 1]);
				unset($_SESSION['user']['user_info']['order_number']);
				return json(['code' => 'Ok' , 'msg' => '支付成功']);

			}else{
				return json(['code' => 'no' , 'msg' => '支付失败中']);
			}
			
		}

	}

	//声鉴订单回调
	public function sj_order()
	{
		$qurey = new \MicroPay();
		//var_dump($_SESSION['user']['user_info']['order_number']);die;
		$order_num = $_SESSION['user']['user_info']['order_number'];
		$num ='';

		$data = $qurey->query($order_num,$num);
		
		if($data['trade_state_desc'] == '支付成功'){
			
			//查询订单状态  是否已经支付成功
			$status = Db::table('hn_order')->field('status,acc_id,user_id,price,wb_id')->where('number',$order_num)->find();

			if($status['status'] == 0)
			{	

			
				//订单提交并未支付的时候
				/*1.声音鉴定不给网吧钱
				if($status['wb_id'] != 0){

					//①.通过 $data['wb_id'] 查hn_netbar(网吧入驻表) 联查hn_cybercafe(网吧管理员表) 查ratio（分成比例） $status['price']*ratio
					$wb_data = Db::table('hn_netbar')
						->alias('n')
						->join('hn_cybercafe c' , 'n.c_id = c.id')
						->field('c.ratio,c.id')
						->where(['n.id' => $data['wb_id']])
						->find();

						$wb_money = $data['price']*$wb_data['ratio']; //给的钱数
						//②给网吧表 extract 添值
						Db::table('hn_netbar')->where('id',$data['wb_id'])->setInc('extract',$wb_money);
						//③.给网吧管理员表 extract not_extract添值
						Db::table('hn_cybercafe')->where('id',$wb_data['id'])->setInc('extract',$wb_money);
						Db::table('hn_cybercafe')->where('id',$wb_data['id'])->setInc('not_extract',$wb_money);

				}*/
				//2.给用户添加土豪值
				Db::table('hn_user')->where('uid', $status['user_id'])->setInc('mogul', $status['price']);//加总土豪值

				Db::table('hn_user')->where('uid', $status['user_id'])->setInc('mogul_day', $status['price']);//加当天土豪值

				//3.判断用户充值的等级
			
				$mogul = Db::table('hn_user')->field('mogul,level')->where('uid', $status['user_id'])->find();

				$level = Db::table('hn_recharge_level')->field('level')->where('money','>',$mogul['mogul'])->select();
				$level = $level['0']['level']-1;
				if($mogul['level'] !=  $level){
					Db::table('hn_user')->where('uid',$status['user_id'])->setField('level', $level);
				}

				//4.给陪玩师发站内信
				$title = '您有新的声音鉴定订单';
				$text = '您有新的声音鉴定订单，快去接单吧！！！！！！！';
				$send_id = 0;
				$rec_id = $status['acc_id'];
				$this->message_add($title,$text,$send_id,$rec_id);	
				//5.改变订单状态	
				Db::table('hn_order')->where('number', $order_num)->update(['status' => 1]);
				unset($_SESSION['user']['user_info']['order_number']);


				//6.给声鉴表添加声鉴订单
				$code = $this->voice_order($status['acc_id'],$status['price']);		
				$qq = Db::table('hn_user')->field('penguin')->where('uid',$status['acc_id'])->find();
					if($code == 1){
						$mom = ['code' => 'Ok',
									'msg' => '支付成功，联系陪玩师,qq:'.$qq['penguin']
								];
						return json($mom);
					}else{
						return json(['code' =>2 , 'msg' =>'失败']);

					}

			}else{
				return json(['code' => 'no' , 'msg' => '支付失败中']);
			}
			
		}
	}

}

/*
{ 	["appid"]=> string(18) "wx28758915df4b5364" 
	["code_url"]=> string(35) "weixin://wxpay/bizpayurl?pr=NflTyic" 
	["mch_id"]=> string(10) "1505642971" 
	["nonce_str"]=> string(16) "yOCN7VxaNzsCEQQa" 
	["prepay_id"]=> string(36) "wx24112435264956628949664a2133977853" 
	["result_code"]=> string(7) "SUCCESS" 
	["return_code"]=> string(7) "SUCCESS" 
	["return_msg"]=> string(2) "OK" 
	["sign"]=> string(64) "02E12AF9F4C4458A6E98E4CDB5C9C39BABDC6EAB529BBF50E3867616FCA84133" 
	["trade_type"]=> string(6) "NATIVE" }

	 ["appid"]=> string(18) "wx28758915df4b5364" 
	 ["err_code"]=> string(13) "ORDERNOTEXIST" 
	 ["err_code_des"]=> string(15) "order not exist" 
	 ["mch_id"]=> string(10) "1505642971" 
	 ["nonce_str"]=> string(16) "jYUx9hcLYNoS7KI1" 
	 ["result_code"]=> string(4) "FAIL" 
	 ["return_code"]=> string(7) "SUCCESS" 
	 ["return_msg"]=> string(2) "OK" 
	 ["sign"]=> string(64) "8D429DBE24EF75E495D129A8F88BCF06EDD805F3B249A52F713023BB6662B6ED"

	

	 array(20) { 
	 	["appid"]=> string(18) "wx28758915df4b5364" 
	 	["attach"]=> string(1) "1" 
	 	["bank_type"]=> string(3) "CFT" ["cash_fee"]=> string(1) "1" 
	 	["fee_type"]=> string(3) "CNY" 
	 	["is_subscribe"]=> string(1) "Y" 
	 	["mch_id"]=> string(10) "1505642971" 
	 	["nonce_str"]=> string(16) "DQwFLNMGFKIBEUxL" 
	 	["openid"]=> string(28) "owwvf0jGEIvvd3SkFnNOsFNXw7-c" 
	 	["out_trade_no"]=> string(15) "153761407108781" 
	 	["result_code"]=> string(7) "SUCCESS" 
	 	["return_code"]=> string(7) "SUCCESS" 
	 	["return_msg"]=> string(2) "OK" 
	 	["sign"]=> string(64) "68832C6A8AAB14713401DF901C6DDB2B33698CE65D316300F3CF38341CD4DD22" 
	 	["time_end]=> string(14) "20180922190231" 
	 	["total_fee"]=> string(1) "1" 
	 	["trade_state"]=> string(7) "SUCCESS" 
	 	["trade_state_desc"]=> string(12) "支付成功" 
	 	["trade_type"]=> string(6) "NATIVE" 
	 	["transaction_id"]=> string(28) "4200000187201809229425077186" 
	 	} string(15) "153761407108781"



	 	array(20) { 
	 		["appid"]=> string(18) "wx28758915df4b5364" 
	 		["attach"]=> string(1) "1" 
	 		["bank_type"]=> string(3) "CFT" 
	 		["cash_fee"]=> string(1) "1" 
	 		["fee_type"]=> string(3) "CNY" 
	 		["is_subscribe"]=> string(1) "Y" 
	 		["mch_id"]=> string(10) "1505642971" 
	 		["nonce_str"]=> string(16) "mXmnBmtoW6r8qhqZ" 
	 		["openid"]=> string(28) "owwvf0jGEIvvd3SkFnNOsFNXw7-c" 
	 		["out_trade_no"]=> string(15) "153761420106647" 
	 		["result_code"]=> string(7) "SUCCESS" 
	 		["return_code"]=> string(7) "SUCCESS" 
	 		["return_msg"]=> string(2) "OK" 
	 		["sign"]=> string(64) "57DD515BF90362D4DE3E83B249B9D317459690E52CC4617E16F20C3402ADC2E1" 
	 		["time_end"]=> string(14) "20180922190437" 
	 		["total_fee"]=> string(1) "1" 
	 		["trade_state"]=> string(7) "SUCCESS" 
	 		["trade_state_desc"]=> string(12) "支付成功" 
	 		["trade_type"]=> string(6) "NATIVE" 
	 		["transaction_id"]=> string(28) "4200000174201809224157106716" 
	 	} string(15) "153761420106647" 
	*/
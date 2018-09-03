<?php
/*
*	微信支付测试控制器
*	作者： YG
*	时间：2018.8.22
*/

namespace app\index\controller;
use \think\Controller;
use \think\Request;
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



class Pay extends Common
{
	public function wechat()
	{
		$url    = \think\Url::build('wxpayNotify', '', true, true);
		var_dump($url);die;
		return $this->fetch();
	}

	public function wechatpay()
	{
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
			$input->SetBody("test");    //设置商品或支付单简要描述
			$input->SetAttach("1");   //设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据  我这里设置成用户ID？
			$input->SetOut_trade_no(time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT));   //设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
			$input->SetTotal_fee("5");  //设置订单总金额，只能为整数，详见支付金额
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
		return json(['daw' => 123,'msg'=>'zhaoshunchao']);
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
*/

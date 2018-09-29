<?php
namespace app\index\controller;
use \think\Controller;
use \think\Request;
use \think\Db;
use think\Session;
/**
 * 微信授权登录类
 * User: summer
 * Date: 2017/11/27
 * Time: 13:57
 */
class Wxlogin extends Controller
{


    private $appid = 'wx7c317697041aa656';                 //微信公众号APPID
    private $appsecret = 'b441fbe117fcf323ca0b6375d8f6b772';             //密匙
    private $url = 'http://www.huoniaopeiwan.com/index/wxlogin/WxCallback';       //微信回调地址

    public function index()
    {
        $state = 123;

        $callback = urlencode($this->url);

        $url = "https://open.weixin.qq.com/connect/qrconnect?appid=".$this->appid."&redirect_uri=".$callback."&response_type=code&scope=snsapi_login&state=".$state."#wechat_redirect";

        return redirect($url);

    }


    /**
     *微信授权回调
     **/
    public function WxCallback()
    {
        $code = input('code');

        $acctoken = $this->getAccessToken($code);

        $userinfo = $this->getUserInfo($acctoken['openid'],$acctoken['access_token']);
        //用户信息入库
        //var_dump($userinfo);
        $user = db('hn_user');
        $users = $user->where('wx_openid',$userinfo['openid'])->find();
        if($users){//数据存在，直接登录
            Session::set('user_info',$users,'user');
            //echo exit('<script>top.location.href="/"</script>');
            $this->redirect('/');
        }else{//不存在，绑定手机号

            $data_arr = [
                'wx_openid'=>$userinfo['openid'],
                'nickname' => $userinfo['nickname'],
                'head_img' =>$userinfo['headimgurl']
            ];
            Session::set('user_arr', $data_arr);
            echo '<script>location.href="/index/login/bind"</script>';
            //return $this->fetch('Login/bind');
            //$this->redirect('Login/bind');
        }

    }
    /**
     *获取accesstoken
     **/

    public function getAccessToken($code)
    {
        $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code={$code}&grant_type=authorization_code";

        $array=(array)json_decode($this->curlGet($url));

        return $array;
    }

    //获取用户信息
    public function getUserInfo($openid,$access_token)
    {
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN ";

        $array=(array)json_decode($this->curlGet($url));

        return $array;
    }

    //curl请求
    private function curlGet($url)
    {
        $ch = curl_init($url) ;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // 获取数据返回
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ; // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
        $userInfo = curl_exec($ch) ;
        curl_close($ch);
        return $userInfo;
    }
}
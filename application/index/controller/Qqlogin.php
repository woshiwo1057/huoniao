<?php
namespace app\index\controller;
use think\Controller;
use \think\Session;
use think\Config;
class Qqlogin extends Controller{
    //发起请求
    public function qqsend(){
        //参数
        $url = "https://graph.qq.com/oauth2.0/authorize";
        $param['response_type'] = "code";
        $param['client_id']="101500777";
        $param['redirect_uri'] ="https://www.huoniaopeiwan.com/index/qqlogin/qqback";
        $param['scope'] ="get_user_info";
        //-------生成唯一随机串防CSRF攻击
        $param['state'] = md5(uniqid(rand(), TRUE));
        Session::set('state', $param['state']);
        //$_SESSION['state'] = $param['state'];

        //拼接url
        $param = http_build_query($param,"","&");
        $url = $url."?".$param;
        header("Location:".$url);
    }
    //回调
    public function qqback(){
        $request = request();//think助手函数
        $data_get = $request->param();//获取get与post数据
        $code = $data_get['code'];
        $state = $data_get['state'];

        if($code && $state == Session::get('state')){
            //获取access_token
            $res = $this->getAccessToken($code,"101500777","8816f3dafa419698375e5e60fe9fcba3");
            /*dump($res);
            exit();*/
            parse_str($res,$data);
            $access_token = $data['access_token'];
            $url  = "https://graph.qq.com/oauth2.0/me?access_token=$access_token";
            $open_res = $this->httpsRequest($url);
            if(strpos($open_res,"callback") !== false){
                $lpos = strpos($open_res,"(");
                $rpos = strrpos($open_res,")");
                $open_res = substr($open_res,$lpos + 1 ,$rpos - $lpos - 1);
            }
            $user_arr = json_decode($open_res,true);

            $open_id = $user_arr['openid'];

            $user = db('hn_user');
            $users = $user->where('qq_openid',$open_id)->find();
            if($users){//数据存在，直接登录
                Session::set('user_info',$users,'user');
                echo exit('<script>top.location.href="/"</script>');
                //$this->redirect('/');
            }else{//不存在，绑定手机号
                $url = "https://graph.qq.com/user/get_user_info?access_token=$access_token&oauth_consumer_key=101500777&openid=$open_id";
                $user_info = $this->httpsRequest($url);
                //输出qq用户信息
                $user_info = json_decode($user_info,true);

                $data_arr = [
                    'qq_openid'=>$open_id,
                    'nickname' => $user_info['nickname'],
                    'head_img' =>$user_info['figureurl_qq_2']
                ];
                Session::set('user_arr', $data_arr);

                $this->redirect('Login/bind');
            }
        }
    }




    //通过Authorization Code获取Access Token
    public function getAccessToken($code,$app_id,$app_key){
        $url="https://graph.qq.com/oauth2.0/token";
        $param['grant_type']="authorization_code";
        $param['client_id']=$app_id;
        $param['client_secret']=$app_key;
        $param['code']=$code;
        $param['redirect_uri']="http://www.huoniaopeiwan.com/index/qqlogin/qqback";
        $param =http_build_query($param,"","&");
        $url=$url."?".$param;
        return $this->httpsRequest($url);
    }
    //httpsRequest
    public function httpsRequest($post_url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$post_url);//要访问的地址
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//执行结果是否被返回，0是返回，1是不返回
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);//设置超时
        $res = curl_exec($ch);//执行并获取数据
        return $res;
        curl_close($ch);
    }

}

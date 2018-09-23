<?php
namespace app\admin\controller;
use \think\Controller;
use \think\Session;
use \think\Request;
use \think\Db;

//引入腾讯对象存储
use \Qcloud\Cos\Client;


class Identify extends Common
{

    //未鉴定订单列表
    public function index(){
        $acc_id = $_SESSION['admin']['admin_info']['acc_id'];
        $identify = \db('hn_identify');
        //$res = $identify->where(['acc_id'=>$acc_id])->paginate(10);
        $data = $identify->alias('i')->join('hn_user u','i.uid = u.uid')->where('i.acc_id',$acc_id)->field('i.id,i.order_id,i.uid,i.acc_id,i.addtime,i.identify,i.status,i.price,u.nickname')->paginate(25);
        //print_r($data);die;
        $page = $data->render();
        $this->assign([
            'data'=>$data,
            'page'=>$page,
        ]);
        return $this->fetch('Identify/index');
    }
    //鉴定订单列表
    function completed(){
        $uid = $_SESSION['admin']['admin_info']['acc_id'];
        $identify = \db('hn_identify');
        $data = $identify->alias('i')->join('hn_user u','i.uid = u.uid')->where(['i.acc_id'=>$uid,'i.status'=>2])->field('i.id,i.uid,i.acc_id,i.addtime,i.identify,i.status,i.price,u.nickname')->paginate(25);
        $page = $data->render();
        $this->assign([
            'data'=>$data,
            'page'=>$page,
        ]);
        return $this->fetch('Identify/completed');
    }

    //用户下订单（登陆后才能访问）
    function add(){
        $uid = $_SESSION['user']['user_info']['uid'];
        $request = request();//think助手函数
        $data_get = $request->param();//获取get与post数据

        $identify = \db('hn_identify');

        $data = [
            'uid'=> $uid,
            'order_id' => time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'acc_id' => $data_get['acc_id'],
            'price' => $data_get['price'],
            'addtime' => time(),
            'identify'=> '声音鉴定',
            'status' => 1
        ];
        $res = $identify->insert($data);
        if($res){
            return ['msg'=>'申请成功','code'=>1];
        }else{
            return ['msg'=>'网络出错，请重试','code'=>2];
        }
    }





    //鉴定订单详情（鉴定师访问）
    public function identify(){
        $request = request();//think助手函数
        $data_get = $request->param();//获取get与post数据
        $identify = \db('hn_identify');
        $accompany = \db('hn_accompany');
        $user = \db('hn_user');

        $res = $identify->where('id',$data_get['i_id'])->find();
        $users = $user->where('uid',$res['uid'])->field('nickname,head_img')->find();
        $acc = $user->where('uid',$res['acc_id'])->field('nickname,head_img')->find();

        $res['name'] = $users['nickname'];
        $res['head_img'] = $users['head_img'];
        $res['acc_name'] = $acc['nickname'];
        $this->assign('data',$res);
        return $this->fetch('Identify/identify');
    }


    //鉴定卡生成
    function identify_add(){
        $request = request();//think助手函数
        $data_get = $request->param();//获取get与post数据
        $identify = \db('hn_identify');
        $data_arr = $data_get;
        $img = $this->identify_card($data_arr);//鉴定卡生成
        $file = $img;
        $key = date('Y-m-d').'/'.md5(microtime()).'.jpg'; //路径
        $data = $this->coss($file,$key);
        $url = $this->img.$key;
        if($data['code'] == 0){
            $datas = [
                'identify_card' => $url,
                'status' => 2
            ];
            $res = $identify->where('id',$data_get['i_id'])->update($datas);
            if($res){
                $this->success('生成成功',url('Identify/identify',['i_id'=>$data_arr['i_id'] ]));
            }else{
                $this->success('网络出错，请重试');
            }
        }else{
            $this->success('生成失败，请重试');
        }

    }

    function yulan(){
        $request = request();//think助手函数
        $data_get = $request->param();//获取get与post数据

        $identify = \db('hn_identify');
        $data_arr = $data_get['data'];
        //print_r($data_arr['head_img']);
        $img = $this->identify_card($data_arr);//鉴定卡生成
        $file = $img;
        $key = date('Y-m-d').'/'.md5(microtime()).'.jpg'; //路径
        $data = $this->coss($file,$key);
        if($data['code']==0){
            $url = $this->img.$key;
            return ['code'=>1,'msg'=>'生成成功','url'=>$url];
        }else{
            return ['code'=>2,'msg'=>'生成失败'];
        }

    }

}
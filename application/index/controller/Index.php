<?php
/*
*	前台首页控制器
*	作者：YG
*	时间：2018.7.13
*/
namespace app\index\controller;
use \think\Controller;
use \think\Session;
use \think\Request;
use \think\Db;

//引入腾讯对象存储
use \Qcloud\Cos\Client;


class Index  extends Common
{
   

    public function index()
    { 	
        
    	//Session::delete('think'); 
    	//var_dump($_SESSION);die;

    	//公共前台首页输出信息（导航栏）   完后删除
    	//$acc_data = Db::table()->field('id,nickname,head_img,label')->select();
    	//首页banner图
    	$banner_data = Db::table('hn_banner')->field('img_url,link,link_type')->where('status',1)->select();

    	//首页服务项目
    	$game_data = Db::table('hn_game')->field('id,name,game_index_img')->order('sort_id esc')->limit(4)->select();
       

    	//首页明星推荐
    	$acc_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,a.table,a.hot,a.pice,a.order_num')->where('a.status',1)->limit('0,15')->select();
   //  	 if($acc_data != NULL){          
   //          $type = 'order_num';
   //          $acc_data = $this->ranking($acc_data,$type);
   //      }
   //     $acc_data  =Db::table('hn_accompany')->select();
   // var_dump($acc_data);die;
    	//优质新人  先注册的排前面（15天内）
    	$new_data =	Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.age')->where('a.new_people',1)->limit('15 ')->select();
    	
        //$adhwuhwad = $this->wechat_query();
        //var_dump($adhwuhwad);die;

//*********************
//*首页排行榜处未优化，上线后记得及时优化为Ajax
//*********************
    	//首页排行榜
    		//1.陪玩师人气榜
    			//①日榜
    			$hot_day_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.level,a.hot_day')->limit(10)->select();
    			if($hot_day_data != NULL){
                    $type = 'hot_day';
                    $hot_day_data = $this->ranking($hot_day_data,$type);
                }

    			//②总榜
                
    			$hot_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.level,a.hot')->limit(10)->select();
    			if($hot_data != NULL){
                    $type = 'hot';
                    $hot_data = $this->ranking($hot_data,$type);
                }
                
    		//2.陪玩师大神榜
    			//①日榜
    			$okami_day_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.level,a.okami_day')->limit(10)->select();
    			if($okami_day_data != NULL){
                    $type = 'okami_day';
                    $okami_day_data = $this->ranking($okami_day_data,$type);
                }
    			//②总榜	
                
    			$okami_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.level,a.okami')->limit(10)->select();
    			if($okami_data != NULL){
                    $type = 'okami';
                    $okami_data = $this->ranking($okami_data,$type);
                }
              
    		//3.用户贡献榜
    			//①日榜
    			$mogul_day_data = Db::table('hn_user')->field('uid,nickname,head_img,mogul_day,level')->limit(10)->select();
    			if($mogul_day_data != NULL){
                    $type = 'mogul_day';
                    $mogul_day_data = $this->ranking($mogul_day_data,$type);
                }
    			//②总榜
    			$mogul_data = Db::table('hn_user')->field('uid,nickname,head_img,mogul,level')->limit(10)->select();
    			if($mogul_data != NULL){
                    $type = 'mogul';
                    $mogul_data = $this->ranking($mogul_data,$type);
                }
                
    	$this->assign([
    		//首页banner图
              'banner_data' => $banner_data,
    		//首页服务项目数据
    		    'game_data' => $game_data,
    		//明星推荐数据
    		     'acc_data' => $acc_data,
    		//优质新人数据
    		      'new_data'=> $new_data,
    		//人气榜数据   
              'hot_day_data'=> $hot_day_data,      
    		      'hot_data'=> $hot_data,
    		//大神榜数据
    		'okami_day_data'=> $okami_day_data,
    		    'okami_data'=> $okami_data,
    		//贡献榜数据
    		'mogul_day_data'=> $mogul_day_data,
    	        'mogul_data'=> $mogul_data
    		     
    				]);

    	return $this->fetch('Index/index');
    }

    //模糊搜索
    public function search()
    {
    	if(Request::instance()->param('search'))
    	{
    		$content = $_POST['content'];

    		$res = Db::table('hn_accompany')->where(['name' => ['like',"%$content%"]])->find();

    		$rea = Db::table('hn_accompany')->where(['name' => ['like' , "%$content%"]])->find();

    		return json_encode($res,$rea);

    	}

    }

    public function ranking_list()
    {

        $rank = Request::instance()->param('rank');
       
        if($rank == 1){
            $hot_day_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.level,a.hot_day')->limit(10)->select();
            if($hot_day_data != NULL){
                $type = 'hot_day';
                $hot_day_data = $this->ranking($hot_day_data,$type);
            }
             return json($hot_day_data);
        }else if($rank == 2){
            $hot_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.level,a.hot')->limit(10)->select();
            if($hot_data != NULL){
                $type = 'hot';
                $hot_data = $this->ranking($hot_data,$type);
            }
             return json($hot_data);
        }else if($rank == 3){
            $okami_day_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.level,a.okami_day')->limit(8)->select();
            if($okami_day_data != NULL){
                $type = 'okami_day';
                $okami_day_data = $this->ranking($okami_day_data,$type);
            }
             return json($okami_day_data);
        }else if($rank == 4){
            $okami_data = Db::table('hn_user')->alias('u')->join('hn_accompany a','u.uid = a.user_id')->field('u.uid,u.nickname,u.head_img,u.level,a.okami')->limit(8)->select();
            if($okami_data != NULL){
                $type = 'okami';
                $okami_data = $this->ranking($okami_data,$type);
            }
             return json($okami_data);
        }else if($rank == 5){
            $mogul_day_data = Db::table('hn_user')->field('uid,nickname,head_img,mogul_day,level')->limit(8)->select();
            if($mogul_day_data != NULL){
                $type = 'mogul_day';
                $mogul_day_data = $this->ranking($mogul_day_data,$type);
            }
            return json($mogul_day_data);
        }else if($rank == 6){
            $mogul_data = Db::table('hn_user')->field('uid,nickname,head_img,mogul,level')->limit(8)->select();
            if($mogul_data != NULL){
                $type = 'mogul';
                $mogul_data = $this->ranking($mogul_data,$type);
            }
            return json($mogul_data);

        }

    } 

    //前台index/user  用户点进去看的地方  可以下单
    public function user()
    {
        //获取到陪玩师ID
        $id = Request::instance()->param('id');
        //var_dump($id);die;
        //查询陪玩师数据
        $user_data = Db::table('hn_user')
                        ->alias('u')
                        ->join('hn_accompany a','u.uid = a.user_id')
                        ->field('u.uid,u.nickname,u.head_img,u.age,a.table,a.status,a.hot,a.explain,a.height,a.weight,a.hobby,a.duty,a.pice,a.acc_time')
                        ->where('user_id',$id)
                        ->find();
        //查询相册数据
        $album_data = Db::table('hn_user_album')->field('id,img_url')->where('user_id',$id)->limit(8)->select();
        //查询礼物数据
        $gift_data = Db::table('hn_gift')->field('id,name,pice,img_url')->select();
        //查询服务项目(只查询第一个服务项目  其他的走Ajax)
            //查出所有名字循环输出
        $service_name = Db::table('hn_apply_project')->field('project_name,project_id,project')->where(['status' => 1, 'type' => 1,'uid' => $id])->select();

        $project_data = Db::table('hn_apply_project')->field('project,project_id,project_name,project_grade_name,pric,length_time')->where(['status' => 1, 'type' => 1,'uid' => $id])->find();
        
        if($project_data['project'] == 1){
            //查游戏表
            $game_data = Db::table('hn_game')->field('id,name,game_index_img')->where('id',$project_data['project_id'])->find();
            $service_data = array_merge($project_data,$game_data);
        }else if($project_data['project'] == 2){
            //查娱乐表
            $joy_data = Db::table('hn_joy')->field('id,name,joy_logo_img')->where('id',$project_data['project_id'])->find();
            $service_data = array_merge($project_data,$joy_data);
        }

//var_dump($service_name);die;
        //查询评论数据
        $comment_data = Db::table('hn_comment')
                        ->alias('c')
                        ->join('hn_user u','c.user_id = u.uid ')
                        ->field('c.id,c.content,c.time,c.zan,u.nickname,u.head_img')
                        ->where('acc_id',$id)
                        ->order('id desc')
                        ->limit(8)
                        ->select();

        //查询送礼人数据
        $user_gift = Db::table('hn_give_gift')->field('user_id,egg_num')->where('acc_id',$id)->limit(6)->select();
        $user_gift = $this->sort($user_gift);
            
        $sort_data = [];
        foreach ($user_gift as $k => $v){
           $user_header = Db::table('hn_user')->field('nickname,head_img')->where('uid',$k)->find();
           $user_header['egg_num'] = $v;
           $sort_data[] = $user_header;
        }        
       if($sort_data != NULL){
            $sort_data =  $this->ranking($sort_data,$type='egg_num');
        }
       
        //查询自己收到的礼物数据
        $my_gift = Db::table('hn_give_gift')->field('gift_id,num')->where('acc_id',$id)->select(); 
        $my_gift = $this->gift_sort($my_gift);
        $sort_gift = [];
        foreach ($my_gift as $k => $v) {
            $gift_header = Db::table('hn_gift')->field('img_url')->where('id',2)->find();
            $gift_header['num'] = $v;
            $sort_gift[] =  $gift_header;
        }
        
        $this->assign([
            //陪玩师数据
            'user_data' => $user_data,
            //相册数据
            'album_data' => $album_data,
            //礼物数据
            'gift_data' => $gift_data,
            //服务项目名
            'service_name' => $service_name,
            //服务项目（单个）
            'service_data' => $service_data,
            //礼物排序数据
            'sort_data' => $sort_data,
            //我的礼物数据
            'sort_gift' => $sort_gift,
            //评论数据
            'comment_data' => $comment_data

            ]);
        return $this->fetch('Index/user');
    }

    //陪玩师服务项目Ajax
    public function  service_ajax()
    {   

    }

    //无限点赞
    public function zan_ajax()
    {
        $id = Request::instance()->param('id');

        $res = Db::table('hn_comment')->where('id', $id)->setInc('zan');

        if($res){
            return json(['code' => 1,'msg' => '成功']);
        }else{
            return json(['code' => 2,'msg' => '失败']);
        }
    }
    //根据用户送礼的金钱排序
    public function sort($arr)
    {
            $data = [];

            foreach ($arr as $key => $val){
                $data[$key][$val['user_id']] = $val['egg_num'];
            }
        
            $item = array();

            foreach($data as $key=>$value)
            {
                foreach($value as $k=>$v){
                    if(isset($item[$k])){
                                $item[$k] = $item[$k] +$v;
                    }else{
                                $item[$k] = $v;
                    }
                }
            }
            return $item;             
                    
    }

    //这个方法不想传参了  所以粘贴复制  RUA！！！
     public function gift_sort($arr)
    {
            $data = [];

            foreach ($arr as $key => $val){
                $data[$key][$val['gift_id']] = $val['num'];
            }
        
            $item = array();

            foreach($data as $key=>$value)
            {
                foreach($value as $k=>$v){
                    if(isset($item[$k])){
                                $item[$k] = $item[$k] +$v;
                    }else{
                                $item[$k] = $v;
                    }
                }
            }
            return $item;                   
    }

    //送礼物控制器
    public function giving_gifts()
    {
        if(!isset($_SESSION['user'])){
            return  json(['code' => 1,'msg' => '跳去登录']);
        }
      
        //获取到用户ID
        $user_id = $_SESSION['user']['user_info']['uid'];
        $gift_data = Request::instance()->param(); //gift_data['gift_id']礼物ID   gift_data['acc_id']该陪玩师ID

        //查询出该礼物的价格  判断用户的鸟蛋够不够    存表 完成
        $pice = Db::table('hn_gift')->field('pice')->where('id',$gift_data['gift_id'])->find();
            //查询出用户的鸟蛋余额
        $currency = Db::table('hn_user')->field('currency')->where('uid',$user_id)->find();

        if($currency['currency']<$pice['pice']){
            return  json(['code' => 2,'msg' => '剩余鸟蛋不足，请去充值']);
        }
        //组装数据填表
    
        $data['user_id'] =  $user_id; //送礼的人
        $data['acc_id'] = $gift_data['acc_id'];//得礼的人
        $data['gift_id'] =  $gift_data['gift_id'];//礼物ID
        $data['egg_num'] = $pice['pice'];  //一次性赠送出的鸟蛋的数量  这里就是送出一个的钱（单价）
        $data['time'] = time();

        $ras = Db::table('hn_user')->where('uid',$user_id)->setDec('currency',$pice['pice']);
        $res = Db::table('hn_give_gift')->insert($data);

        
        if($ras&&$res){
            return  json(['code' => 3,'msg' => '赠送成功']);
        }else{
            return  json(['code' => 4,'msg' => '赠送失败，错误码004']);
        }
    }


}

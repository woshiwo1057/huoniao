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
use \think\File;

//引入腾讯对象存储
use \Qcloud\Cos\Client;


class Index  extends Common
{
   

    public function index()
    { 	
         
        //$wb_id = Session::get('wb_id');
        if(isset($_SESSION['think']['wb_id'])){
            $wb_id = $_SESSION['think']['wb_id'];
          
            if(isset($wb_id)&&$wb_id){
                $data = db('hn_netbar')->where('id',$wb_id)->field('id,name')->find();
                $name = $data['name'];
            
                $this->assign([ 'name' => $name ]);
            }
        }
    	//Session::delete('think');
    	//var_dump($_SESSION);die;

    	//公共前台首页输出信息（导航栏）   完后删除
    	//$acc_data = Db::table()->field('id,nickname,head_img,label')->select();
    	//首页banner图
    	$banner_data = Db::table('hn_banner')->field('img_url,link,link_type')->where('status',1)->select();

    	//首页服务项目
    	$game_data = Db::table('hn_game')->field('id,name,game_index_img')->order('sort_id esc')->limit(4)->select();
       

    	//首页明星推荐
    	$acc_data = Db::table('hn_user')
                    ->alias('u')
                    ->join('hn_accompany a','u.uid = a.user_id')
                    ->join('hn_apply_project p' , 'a.project_id = p.project_id')
                    ->field('u.uid,u.nickname,u.head_img,a.table,a.hot,a.pice,a.order_num,p.project_name,u.sex,a.city')
                    ->order('a.okami desc')->limit('0,15')->select();
                    //->where('a.status',1)
        $acc_data = $this->out_repeat($acc_data,'nickname');
        //var_dump($acc_data);die;
  
    	//优质新人  先注册的排前面（15天内）
    	$new_data =	Db::table('hn_accompany')->alias('a')
                        ->join('hn_user u','u.uid = a.user_id')                  
                        ->field('u.uid,u.nickname,u.head_img,u.age,u.sex,a.city')->where('a.new_people',1)->limit('15')->select();
    	//->join('hn_apply_acc p','p.user_id = a.user_id')
        //$adhwuhwad = $this->wechat_query();
        //var_dump($new_data);die;

//*********************
//*首页排行榜
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
                //var_dump($okami_data[0]['okami']);die;
              
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
                        ->field('u.uid,u.nickname,u.head_img,u.age,u.neice,a.table,a.status,a.hot,a.explain,a.height,a.weight,a.hobby,a.duty,a.pice,a.acc_time,a.city,a.sexy,a.down,a.up')
                        ->where('user_id',$id)
                        ->find();
        //查询相册数据
        $album_data = Db::table('hn_user_album')->field('id,img_url')->where('user_id',$id)->limit(8)->select();
        //查询礼物数据
        $gift_data = Db::table('hn_gift')->field('id,name,pice,img_url')->order('pice asc')->select();
        //查询服务项目(只查询第一个服务项目  其他的走Ajax)
            //查出所有名字循环输出
        $service_name = Db::table('hn_apply_project')->field('project_name,project_id,project')->where(['status' => 1, 'type' => 1,'uid' => $id])->select();
        //var_dump($service_name);die;
        $project_data = Db::table('hn_apply_project')->field('project,project_id,project_name,project_grade_name,pric,length_time')->where(['status' => 1, 'type' => 1,'uid' => $id])->find();
        
        if($project_data['project'] == 1){
            //查游戏表
            $game_data = Db::table('hn_game')->field('id,name,game_index_img')->where('id',$project_data['project_id'])->find();
            $service_data = array_merge($project_data,$game_data);
        }else if($project_data['project'] == 2){
            //查娱乐表
            $joy_data = Db::table('hn_joy')->field('id,name,joy_logo_img')->where('id',$project_data['project_id'])->find();
            $service_data = array_merge($project_data,$joy_data);
            $service_data['game_index_img'] = $joy_data['joy_logo_img']; //容错
        }else{
            $service_data = null;
        }
        //var_dump($service_data);die;
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
        $user_gift = Db::table('hn_give_gift')->field('user_id,egg_num')->where('acc_id',$id)->order('egg_num desc')->select();
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
            $gift_header = Db::table('hn_gift')->field('img_url')->where('id',$k)->find();
            $gift_header['num'] = $v;
            $sort_gift[] =  $gift_header;
        }

        //统计此陪玩师关注数量  确定是否已关注
        $follow = \db('hn_follow');
        //获取到用户ID
         $number = $follow->where('followed_user',$id)->count();
        if(isset($_SESSION['user']['user_info']['uid'])){
            $user_id = $_SESSION['user']['user_info']['uid'];
           
            $is_follow = $follow->where(['user_id'=>$user_id,'followed_user'=> $id,'status'=>1])->find();
            if($is_follow){
                $is_follow = 1;
            }else{
                $is_follow = 2;
            }
        }else{
             $is_follow = 2;
        }
      

        //查询几条送礼物的数据去循环
        $song_data = Db::table('hn_give_gift')
                ->alias('g')
                ->join('hn_user u', 'g.user_id = u.uid')
                ->join('hn_gift f', 'f.id = g.gift_id')
                ->field('g.user_id,g.acc_id,g.time,g.num,u.nickname user_name,f.name')
                ->limit(7)
                ->order('g.id desc')
                ->select();

        foreach ($song_data as $k => $v) {
            $name = Db::table('hn_user')->field('nickname')->where('uid' ,$v['acc_id'])->find();
            $song_data[$k]['acc_name'] = $name['nickname'];
        }

        //var_dump($song_data);die;
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
            'comment_data' => $comment_data,
            'number' => $number,
            'is_follow' => $is_follow,
            //送礼人与收礼人的数据
            'song_data' => $song_data

            ]);
        return $this->fetch('Index/user');
    }

    //陪玩师服务项目Ajax
    public function  service_ajax()
    {   
        $data = Request::instance()->param();
    
        //需要的数据 1.游戏图片   2.陪玩师该项目级别  3.价钱  4.接单时长 5.当前项目接单数  6.status == 1(审核成功) 7.type == 1(不下架)   以后需要陪玩师音频
        $service_data = [];
        if($data['project'] == 1){
            //游戏项目
            $img = Db::table('hn_game')->field('game_index_img')->where('id',$data['project_id'])->find();
           
            $service_data = Db::table('hn_apply_project')
                        ->field('project_grade,project_grade_name,pric,length_time,order_num')
                        ->where(['status' => 1, 'type' => 1, 'project_id' => $data['project_id'], 'uid' => $data['acc_id']])
                        ->find();
            $service_data['project_img'] = $img['game_index_img'];

            //算出一个正确的价钱  $service_data['pric']
            /*
            if($service_data['order_num']>=1){
                $pric = Db::table('hn_game_grade')->field('pric')->where('id',$service_data['project_grade'])->find(); //项目初始价格

           
                $service_data['pric'] = $this->pric($service_data['order_num'],$pric['pric']);

            }
            */
           

            return json($service_data);
        }else if($data['project'] == 2){
            //娱乐项目
            $img = Db::table('hn_joy')->field('joy_logo_img')->where('id',$data['project_id'])->find();
           
            $service_data = Db::table('hn_apply_project')
                        ->field('project_grade,project_grade_name,pric,length_time,order_num')
                        ->where(['status' => 1, 'type' => 1, 'project_id' => $data['project_id'], 'uid' => $data['acc_id']])
                        ->find();
            $service_data['project_img'] = $img['joy_logo_img'];
            //var_dump($service_data);die;
            //算出一个正确的价钱  $service_data['pric']
            /*
            if($service_data['order_num']>=2){
                $pric = Db::table('hn_game_grade')->field('pric')->where('id',$service_data['project_grade'])->find(); //项目初始价格

                $service_data['pric'] = $this->pric($service_data['order_num'],$pric['pric']);
            }
           */
            return json($service_data);
        }

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
      
        
        $gift_data = Request::instance()->param(); //gift_data['gift_id']礼物ID   gift_data['acc_id']该陪玩师ID   $gift_data['num']//一次性送出的礼物数量
        //var_dump($gift_data);die;

        //获取到用户ID
        $user_id = $_SESSION['user']['user_info']['uid'];

        //查询出该礼物的价格与图片路径和名字  判断用户的鸟蛋够不够    存表 完成
        $pice = Db::table('hn_gift')->field('pice,img_url,name')->where('id',$gift_data['gift_id'])->find();
        $pice['pice'] = $pice['pice']*$gift_data['num'];  //算出所需要的鸟蛋数    单价*数量
            //查询出用户的鸟蛋余额
        $currency = Db::table('hn_user')->field('currency,nickname')->where('uid',$user_id)->find();

        if($currency['currency']<$pice['pice']){
            return  json(['code' => 2,'msg' => '剩余鸟蛋不足，充值后再来给心爱的陪玩师送礼哦']);
        }

        //组装数据填表
    
        $data['user_id'] =  $user_id; //送礼的人
        $data['acc_id'] = $gift_data['acc_id'];//得礼的人
        $data['gift_id'] =  $gift_data['gift_id'];//礼物ID
        $data['img_url'] = $pice['img_url']; //礼物图片路径
        $data['num'] = $gift_data['num'];//一次性赠送出去的礼物的数量
        $data['egg_num'] = $pice['pice'];  //一次性赠送出的鸟蛋的数量  这里就是送出一个的钱（单价）
        $data['time'] = time();

        $ras = Db::table('hn_user')->where('uid',$user_id)->setDec('currency',$pice['pice']);//减去用户的鸟蛋余额
        //增加陪玩师账户余额
            //1.查出陪玩师礼物兑换比例
            $gift_exchange = Db::table('hn_accompany')->field('gift_exchange')->where('user_id',$gift_data['acc_id'])->find();
            //2.算出应该加给陪玩师的余额数      总鸟蛋数/10*$gift_exchange['gift_exchange']
            $money = $pice['pice']/10*$gift_exchange['gift_exchange'];
            //3.给陪玩师余额字段加值  查询用户表
            Db::table('hn_user')->where('uid', $gift_data['acc_id'])->setInc('cash', $money);
            //4.给陪玩师增加魅力值
            Db::table('hn_accompany')->where('user_id',$gift_data['acc_id'])->setInc('hot' , $pice['pice']);
            Db::table('hn_accompany')->where('user_id',$gift_data['acc_id'])->setInc('hot_day' , $pice['pice']);
        $res = Db::table('hn_give_gift')->insert($data);

        if($ras&&$res){
            //谁给您送了几个什么
            //$currency['nickname']  给您送了$data['num']个$pice['name']
            $title = '收礼物啦';
            $text = $currency['nickname'].'给您送了'.$data['num'].'个'.$pice['name'];
            $send_id = 0;
            $rec_id = $data['acc_id'];
            $this->message_add($title,$text,$send_id,$rec_id);

            return  json(['code' => 3,'msg' => '赠送成功']);
        }else{
            return  json(['code' => 4,'msg' => '赠送失败，错误码004']);
        }
    }

    //关注
    function follow_add(){
        $request = request();//think助手函数
        $data_get = $request->param();//获取get与post数据

        $follow = db('hn_follow');//关注表
        if(!isset($_SESSION['user']['user_info']['uid'])){
            return ['code' => 4,'msg' => '您还未登录，请先登录'];exit;
        }

        $uid = $_SESSION['user']['user_info']['uid'];
        $nickname = $_SESSION['user']['user_info']['nickname'];
        
        if($uid == $data_get['followed_user']){
            return ['code' => 5,'msg' => '不能关注自己哦'];exit;
        }
        $res = $follow->where(['user_id'=>$uid,'followed_user'=>$data_get['followed_user']])->find();
        if($res){
            if($res['status'] == 1){
                $data = [
                    'status'=>2
                ];
              
                $aa = ['code' => 2,'msg' => '操作成功'];
            }else{
                $data = [
                    'status'=>1
                ];
                $aa = ['code' => 1,'msg' => '操作成功'];

                $title = '有人关注了你';
                $text = $nickname.'关注了你';
                $send_id = 0;
                $rec_id = $data_get['followed_user'];
                $this->message_add($title,$text,$send_id,$rec_id);
            }
            $red = $follow->where(['user_id'=>$uid,'followed_user'=>$data_get['followed_user']])->update($data);

        }else{
            $data = [
                'user_id'=> $uid,
                'followed_user'=> $data_get['followed_user'],
                'status'=>1
            ];
            $red = $follow->insert($data);
            $aa = ['code' => 1,'msg' => '操作成功'];

            $title = '有人关注了你';
            $text = $nickname.'关注了你';
            $send_id = 0;
            $rec_id = $data_get['followed_user'];
            $this->message_add($title,$text,$send_id,$rec_id);
        }
        if($red){
            return $aa;
        }else{
            return ['code' => 3,'msg' => '操作失败，请重试'];
        }
    }


    //线下陪玩师详情 用户点进去看的地方  可以下线下单
    public function offline_user()
    {   
        //获取到陪玩师ID
        $id = Request::instance()->param('id');
        //var_dump($id);die;
        //查询陪玩师数据
        $user_data = Db::table('hn_user')
                        ->alias('u')
                        ->join('hn_accompany a','u.uid = a.user_id')
                        ->field('u.uid,u.nickname,u.head_img,u.age,u.neice,a.table,a.status,a.hot,a.explain,a.height,a.weight,a.hobby,a.duty,a.acc_time,a.city,a.sexy,a.down')
                        ->where('user_id',$id)
                        ->find();
        //查询相册数据
        $album_data = Db::table('hn_user_album')->field('id,img_url')->where('user_id',$id)->limit(8)->select();
        //查询礼物数据
        $gift_data = Db::table('hn_gift')->field('id,name,pice,img_url')->select();
        //查询服务项目(只查询第一个服务项目  其他的走Ajax)
            //查出所有名字循环输出
        $service_name = Db::table('hn_apply_project')->field('project_name,project_id,project')->where(['status' => 1, 'type' => 1,'uid' => $id,'project' =>1])->select();

        $project_data = Db::table('hn_apply_project')->field('project,project_id,project_name,project_grade_name,pric,length_time')->where(['status' => 1, 'type' => 1,'uid' => $id])->find();
        
        if($project_data['project'] == 1){
            //查游戏表
            $game_data = Db::table('hn_game')->field('id,name,game_index_img')->where('id',$project_data['project_id'])->find();
            $service_data = array_merge($project_data,$game_data);
        }else if($project_data['project'] == 2){
            //查娱乐表
            $joy_data = Db::table('hn_joy')->field('id,name,joy_logo_img')->where('id',$project_data['project_id'])->find();
            $service_data = array_merge($project_data,$joy_data);
            $service_data['game_index_img'] = $joy_data['joy_logo_img']; //容错
        }else{
            $service_data = null;
        }
        //var_dump($service_data);die;
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
        $user_gift = Db::table('hn_give_gift')->field('user_id,egg_num')->where('acc_id',$id)->order('egg_num desc')->select();
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
            $gift_header = Db::table('hn_gift')->field('img_url')->where('id',$k)->find();
            $gift_header['num'] = $v;
            $sort_gift[] =  $gift_header;
        }

        //统计此陪玩师关注数量  确定是否已关注
        $follow = \db('hn_follow');
        //获取到用户ID
         $number = $follow->where('followed_user',$id)->count();
        if(isset($_SESSION['user']['user_info']['uid'])){
            $user_id = $_SESSION['user']['user_info']['uid'];
           
            $is_follow = $follow->where(['user_id'=>$user_id,'followed_user'=> $id,'status'=>1])->find();
            if($is_follow){
                $is_follow = 1;
            }else{
                $is_follow = 2;
            }
        }else{
             $is_follow = 2;
        }
      

        //查询几条送礼物的数据去循环
        //$song_data = Db::table('hn_give_gift')->->field('user_id,acc_id')->limit(10)->select();

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
            'comment_data' => $comment_data,
            'number' => $number,
            'is_follow' => $is_follow,

            ]);
        
        return $this->fetch('Index/offline_user');
    }

    public function wow()
    {   
        if(Request::instance()->isPost()){

            $data = Request::instance()->param();
            $file =  request()->file('video');
            var_dump($file);die;
            //var_dump($_FILES['video']);die;

            $key = 'zhaochunchao'.'.mp3'; //路径

            $data = $this->cos($file,$key);

            if($data['code'] == 0){
                echo $this->audio.$key;
            }else{
                echo '1';
            }

        }
        return $this->fetch();
    }

    public function mom()
    {
        $data = Request::instance()->param();
        //var_dump($data);die; //base64

        $file = $data['base64'];

        $num = rand(0 ,100);
        $key = 'zhaochunchao'.$num.'.mp3'; //路径

        $data = $this->cos($file,$key);

        if($data['code'] == 0){

                return 1;

        }else{
                return 2;
        }

    } 


}

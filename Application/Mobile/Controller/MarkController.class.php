<?php
/**
 * 打卡模块
 * 2015-7-1
 */
namespace Mobile\Controller;
use Think\Controller;
class MarkController extends Controller{
    public function _initialize(){
     $model_info = get_appinfo('Mark');
        if(!$model_info){
            $this->error('应用未开启');
        }
        if(!is_login()){
            $this->redirect('Mobile/User/login');
        }
    }
	/**
	 * 打卡首页
	 */
	public function index(){
        $uid=is_login();
		$user=D('Common/Mark')->get_user($uid);                //获取打卡首页用户头像及昵称
        $partner_list=D('Common/Mark')->get_partner_list();    //获取所有关联约伴
        foreach ($partner_list as $k => $v) {
            if(get_ch_en_length($v['title'])>15){
                $partner_list[$k]['title']=msubstr($v['title'],0,15,"utf-8",true);
            }
        }
        $this->assign('user',$user);
        $this->assign('partner_list',$partner_list);
        $this->setTitle('打卡');
		$this->display();
	}
    /**
     * 关联活动搜索
     */
    public function partner_list(){
         $partner_list=D('Common/Mark')->get_partner_list($_POST['partner_name']);  //关联活动搜索
        foreach ($partner_list as $k => $v) {
            if(get_ch_en_length($v['title'])>15){
                $partner_list[$k]['title']=msubstr($v['title'],0,15,"utf-8",true);
            }
        }
         if($partner_list){
           exit(json_encode(array('status'=>1,'info'=>$partner_list)));
         }else{
            exit(json_encode(array('status'=>0)));
         }
    }
	/**
	 * 写入打卡
	 */
	public function set_mark(){
		$res['status']=0;
		$data['distance']=trim(I('post.distance'));
		$data['day']=I('post.day');
		$data['hour']=trim(I('post.hour'));
		$data['minute']=trim(I('post.minute'));
		$data['second']=trim(I('post.second'));
		$data['imgids']=I('post.imgids');
		$data['typecode']=I('post.typecode');
		$data['partnerid']=I('post.yuebanmasterid');      
		if(!$data['distance']){
    		$res['info']="请填写打卡距离";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
        if(!is_numeric($data['distance'])){
            $res['info']="打卡距离错误";
            exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
        }
    	if($data['hour']){
    		if(!preg_match('/^[0-9]*$/',$data['hour'])){		            //只能输入数字
    		$res['info']="小时格式错误,最多可以输入两位数字";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
			}
    	}
    	if($data['minute']){
    		if(!preg_match('/^[1-9]$|^[1-5][0-9]$/',$data['minute'])){      //只可输入1~59
    		$res['info']="分钟格式错误";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
			}
    	}
    	if($data['second']){
    		if(!preg_match('/^[1-9]$|^[1-5][0-9]$/',$data['second'])){      //只可输入1~59
    		$res['info']="秒数格式错误";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
			}
    	}
        if((!$data['hour']) and (!$data['minute']) and (!$data['second'])){
            $res['info']="请输入时间";
            exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
        }
		if(!$data['typecode']){ 
    		$res['info']="请选择打卡类型";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	$data['userid'] = is_login();
    	$data['siteid'] = SITEID;
    	//处理打卡时间
         $daka_day=strtotime(date('Y-m-d',time()));
    	switch ($data['day']) {
    		case '-2':
    		$data['daka_day'] =  $daka_day - 60*60*24*2;
    		break;
    		case '-1':
    		$data['daka_day'] = $daka_day - 60*60*24;
    		break;
    		case '0':
    		$data['daka_day'] = $daka_day;
    		break;
    	}
        //速度=运动时间(秒数)/距离;
        $dot="'";
        $quotes='"';
        $speed=($data['hour']*3600+$data['minute']*60+$data['second'])/$data['distance'];
            if($speed<60){                              //小于60秒
                $data['speed']="0:0'". ceil($speed) .'"';
             }else{                                     //大于60秒
                 if($speed<3600){                       //小于1小时
                    $i=floor($speed/60).$dot;
                    $i_remainder= ceil($speed%60);
                    $data['speed']='0:'.$i.($i_remainder?$i_remainder.$quotes:'0"');
                 }else{                                 //大于1小时
                    $h=floor($speed/3600).':';
                    $h_remainder=$speed%3600;
                         if($h_remainder>=60){          //大于1分钟
                         $i=floor($h_remainder/60).$dot;
                         $i_remainder= ceil($h_remainder%60);
                          $data['speed']=$h.$i.($i_remainder?$i_remainder.$quotes:'0"');
                            }else{
                                $data['speed']=$h."0'".'0"';        
                            }
                }
             }  
    	$data['update_datetime'] = time();
        $mark=D('Common/Mark')->set_mark($data);
        //同时写入排行
        $uid=is_login();
        $distance=$data['distance'];
        $mark_summary=D('Common/Mark')->set_mark_summary($uid,$distance,$data['daka_day']);
        if($mark && $mark_summary){
            $res['status']=1;
            exit(json_encode(array('status'=>$res['status'])));
        }else{
            $res['status']=0;
            $res['info']="打卡失败";
            exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
        }
	}
    /**
     * 打卡成功
     */
    public function success(){
        $uid=is_login();
        $user=D('Common/Mark')->get_user($uid);          //打卡成功页面头像及昵称
        $summary=D('Common/Mark')->get_summary($uid);    //获取排行榜
        $this->assign('user',$user);
        $this->assign('summary',$summary);
        $this->setTitle('打卡成功');
        $this->display();
    }
    /**
     * 我的打卡
     */
    public function mymark(){
        $user=D("Common/Mark")->get_user(is_login());       //我的打卡页面头像及昵称
        $mark_ttldistance_count=D("Common/Mark")->get_user_markcount(is_login()); //打卡次数及总距离
        $get_url = json_encode($_GET);
        $this->assign('user',$user);
        $this->assign('get_url', $get_url);
        $this->assign('count',$mark_ttldistance_count[0]);
        $this->assign('distance',$mark_ttldistance_count[1]); 
        $this->setTitle('我的打卡');     
        $this->display();
    }
    /**
     * 异步加载我的打卡列表
     */
    public function get_mymark($page =0){
        $start = $page*10;
        $uid=is_login();
        $get_mymark=D('Common/mark')->get_mark_list($uid,$start);
        foreach ($get_mymark as &$v) {
           // 打卡类型
            switch ($v['typecode']) {
                case '1':
                    $v['typetext'] ="跑步";
                    break;
                case '2':
                    $v['typetext'] ="徒步";
                    break;
                case '3':
                    $v['typetext'] ="骑行";
                    break;
                case '4':
                    $v['typetext'] ="游泳";
                    break;
            }
         $v['record']=U('Mobile/Mark/record',array('id'=>$v['id'],'userid'=>$v['userid']));
        //运动距离
        $v['time']=$v['hour'].':'.$v['minute']."'".$v['second']."''";
        $v['daka_day']=date('Y-m-d',$v['daka_day']);
        $partner=D('Common/Mark')->get_partner_title($v['partnerid']);
          if ($v['partnerid']){
                $partner=D('Common/Mark')->get_partner_title($v['partnerid']);
                if(get_ch_en_length($partner[0])>10){
                    $v['yuebanname']=msubstr($partner[0],0,10,"utf-8",true);
                 }else{
                   $v['yuebanname']=$partner[0];
                 }
                $v['thumb'] =getThumbImageById($partner[1],200,150);
            }else{
                $v['yuebanname']='没有最好的活动,只有更好的宝塔镇河妖';
            }       
        }
        exit(json_encode($get_mymark));
    }
    /**
     * 伙伴打卡
     */
    public function markfriends(){
        $user=D("Common/Mark")->get_user($_GET['userid']);      //伙伴打卡页面头像及昵称
        $mark_ttldistance_count=D("Common/Mark")->get_user_markcount($_GET['userid']); //伙伴打卡次数及总距离
        $get_url = json_encode($_GET);
        $this->assign('user',$user);
        $this->assign('get_url', $get_url);
        $this->assign('userid', is_login());
        $this->assign('count',$mark_ttldistance_count[0]);
        $this->assign('distance',$mark_ttldistance_count[1]); 
        $this->setTitle('伙伴打卡');     
        $this->display();
    }
    /**
     * 伙伴打卡异步加载列表
     */
      public function get_markfriends($page =0){
        $start = $page*10;
        $uid=$_GET['userid'];
        $get_mymark=D('Common/mark')->get_mark_list($uid,$start);
        foreach ($get_mymark as &$v) {
           // 打卡类型
            switch ($v['typecode']) {
                case '1':
                    $v['typetext'] ="跑步";
                    break;
                case '2':
                    $v['typetext'] ="徒步";
                    break;
                case '3':
                    $v['typetext'] ="骑行";
                    break;
                case '4':
                    $v['typetext'] ="游泳";
                    break;
            }
         $v['record']=U('Mobile/Mark/record',array('id'=>$v['id'],'userid'=>$v['userid']));
        //运动距离
        $v['time']=$v['hour'].':'.$v['minute']."'".$v['second']."''";
        $v['daka_day']=date('Y-m-d',$v['daka_day']);
            if ($v['partnerid']){
                $partner=D('Common/Mark')->get_partner_title($v['partnerid']);
                if(get_ch_en_length($partner[0])>10){
                    $v['yuebanname']=msubstr($partner[0],0,10,"utf-8",true);
                 }else{
                   $v['yuebanname']=$partner[0];
                 }
                $v['thumb'] =getThumbImageById($partner[1],200,150);
            }else{
                $v['yuebanname']='没有最好的活动,只有更好的宝塔镇河妖';
            }       
        }
        exit(json_encode($get_mymark));
    }
    /**
     * 打卡详情
     */
    public function record(){
        $get_mark=D('Common/Mark')->get_mark($_GET['id'],$_GET['userid']);     //打卡数据
        $user=D("Common/Mark")->get_user($_GET['userid']);               //打卡详情页面头像及昵称
        if($get_mark['imgids']){
        $imgids=explode(",",$get_mark['imgids']);
            foreach ($imgids as $k => $v) {
           $img[$k]['id']=$v;
           $img[$k]['path']=D('picture')->where('id='.$v)->getField('path');
            }
        }
        $this->assign('user',$user);
        $this->assign('get_mark',$get_mark);
        $this->assign('img',$img);
	    $this->setTitle('打卡记录');
        $this->display();
    }
}
?>

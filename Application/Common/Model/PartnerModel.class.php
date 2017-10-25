<?php
/*
 * 约伴 1.0 
 * 2015-06-09
 */
namespace Common\Model;
use Think\Model;

class PartnerModel extends Model{
	/**
	 * 获取用户信息
	 * $id 约伴id
	 */
	public function user($id){
		$user=$this->get_user($id);
		foreach($user as $k=>$v){
			$time=$v['registration_time'];
			$user[$k]['nickname']=query_user('nickname',$v['uid']);
			$user[$k]['avatar128']=query_user('avatar128',$v['uid']);
			$user[$k]['registration_time']=$this->formatDate($time);
		}
		return($user);
	}
	/**
	 * 查询星期
	 * @param string $time 时间戳
	 */
	private function formatWeek($time){
		$week=date("w",$time);
			switch ($week) {
				case '1':
				$week='周一';
					break;
				case '2':
				$week='周二';	
					break;
				case '3':
				$week='周三';	
					break;
				case '4':
				$week='周四';		
					break;
				case '5':
				$week='周五';		
					break;
				case '6':
				$week='周六';		
					break;
				default:
				$week='周日';		
					break;
				}
		return($week);
	}
	/**
	 * 时间处理
	 * @param string $time时间戳
	 */
	private function formatDate($time){
		$rtime = date ( "m-d H:i", $time );  
        $htime = date ( "H:i", $time );  
        $time = time () - $time;  
        if ($time < 60) {
            $str = '刚刚';  
        } elseif ($time < 60 * 60) {  
            $min = floor ( $time / 60 );  
            $str = $min . '分钟前';  
        } elseif ($time < 60 * 60 * 24) {  
            $h = floor ( $time / (60 * 60) );  
            $str = $h . '小时前 ' . $htime;  
        } elseif ($time < 60 * 60 * 24 * 3) {  
            $d = floor ( $time / (60 * 60 * 24) );  
            if ($d == 1)  
                $str = '昨天 ' . $rtime;  
            else  
                $str = '前天 ' . $rtime;  
        } else {  
            $str = $rtime;  
        }
        return $str;
    }
	/**
	 * 获取约伴活动
	 * @param string $id 	    约伴id
	 * @param string $start 	翻页参数
	 * @param string $typecode 约伴类型
	 */
	public function get_parevent($id="",$start=0,$typecode=0){
		if(!$id){
		//查询约伴活动列表数据
			if($typecode){
				$map['event_type']=$typecode;
			}
			$map['siteid']=SITEID;
			$map['status']=1;
			$parevent=D('partner')->where($map)->order('releasetime desc')->limit($start,10)->select();
		foreach ($parevent as $key => $value) {
			 $id=$value['id'];							  //查询出活动参加人数
			 $parevent[$key]['participate_number']=count($this->get_user($id));
														  //查询星期
			 $parevent[$key]['week']=$this->formatWeek($value['start_time']);
			if(is_login()){							      //判断是否报名
				$get_user=$this->get_user($id,is_login());
				if($get_user){							  //已经报名
				$parevent[$key]['apply']= true;
				}
			}
		
			if($value['deadline']<=time()){				  //判断活动状态
				D("partner")->where('id='.$id)->save(array('event_status'=>2));
			}
		}
	}else{
		// 查询单条约伴数据
		$map=array('id'=>$id,'siteid'=>SITEID,'status'=>1);
		$parevent=D('partner')->where($map)->find();
														   //查询出活动参加人数
		$parevent['participate_number']=count($this->get_user($parevent['id']));	
														   //查询星期
		$parevent['week']=$this->formatWeek($parevent['start_time']);
														   //查询男女比例
		$parevent['man']=count($this->get_user($id,$uid,$sex="1"));
		$parevent['woman']=count($this->get_user($id,$uid,$sex="2"));		
		if(is_login()){
			if($parevent['uid'] == is_login()){
				$parevent['user']= true;					//发布者不参加活动
			}
			$get_user=$this->get_user($id,is_login());
			if($get_user){
				$parevent['apply']=true;					//用户是否报名
				}
			}
			if($parevent['deadline']<=time()){				//判断活动状态
				D("partner")->where('id='.$id)->save(array('event_status'=>2));
			}
		  }
		return($parevent);
	}
	/**
	 * 获取我发布的约伴
	 * @param string $uid   用户uid
	 * @param string $start 翻页参数
	 */
	public function get_myparevent($uid="",$start=0){
		$map=array('uid'=>$uid,'siteid'=>SITEID,'status'=>1);
		$parevent=D('partner')->where($map)->order('releasetime desc')->limit($start,10)->select();
		foreach ($parevent as $key => $value) {
		//查询出活动参加人数
			 $id=$value['id'];
			 $parevent[$key]['participate_number']=count($this->get_user($id));
		//查询星期
			 $parevent[$key]['week']=$this->formatWeek($value['start_time']);
			}
		return($parevent);
	}
	/**
	 * 获取我参加的约伴
	 * @param string $uid   用户uid
	 * @param string $start 翻页参数
	 */
	public function get_mypar_event($uid="",$start=""){
		$map=array('uid'=>$uid,'siteid'=>SITEID,'status'=>1);
		$mypar_event=D('partner_user')->where($map)->limit($start,10)->select();
		foreach($mypar_event as $key=>$value){
			$id=$value['partner_id'];
			$get_parevent[]=$this->get_parevent($id);
		}
		return($get_parevent);
	}
	/**
	 * 获取报名用户信息
	 * @param string $uid 用户uid
	 * @param string $start 翻页参数
	 */
	public function get_user($id="",$uid="",$sex=""){
			 if($id){
			 	$map['partner_id']=$id;
			 }
			 if($uid){
			 	$map['uid']=$uid;
			 }
			 if($sex){
			 	$map['sex']=$sex;
			 }
			$map['siteid']=SITEID;
			$map['status']= 1;
		$get_user=D('partner_user')->where($map)->select();
		return($get_user);
	}
	/**
	 * 创建约伴
	 * @param array $data
	 */
	public function set_partner($data){
		$set_partner=D("partner")->add($data);
		return($set_partner);
	}
	/**
	 * 报名约伴
	 * @param array $data
	 */
	public function set_partner_user($data){
		//查询用户是否已经报名
		$map=array('uid'=>$data['uid'],'partner_id'=>$data['partner_id'],'siteid'=>$data['siteid'],'status'=>1);
		$partner_user_count=D('partner_user')->where($map)->find();
		if(!$partner_user_count){		//不存在就报名
			$partner_user=D('partner_user')->add($data);
		}else{
			$partner_user="";
		}
		return($partner_user);
	}
	/**
	 * 取消约伴报名
	 * @param string $uid 用户uid
	 * @param string $id  活动id
	 */
	public function set_partner_cancel($uid,$id){
		$map=array('uid'=>$uid,'partner_id'=>$id);
		$set_cancel=D('partner_user')->where($map)->save(array('status'=>2));
		return($set_cancel);
	}
}

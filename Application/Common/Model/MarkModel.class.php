<?php
/*打卡模型
 *2015-7-1
 */
namespace Common\Model;
use Think\Model;
class MarkModel extends Model{
	/**
	 * 获取用户信息
	 * @param integer 用户id
	 */
	public function get_user($uid){
		$user=query_user(array('nickname','avatar128'),$uid);
		return($user);
	}
	/**
	 * 写入打卡数据
	 * @param array $data 写入的数据
	 */
	public function set_mark($data){
		return(D("mark")->add($data));
	}
	/**
	 * 写入排行表数据
	 * @param integer $uid 用户uid
	 * @param integer $distance 单次打卡距离
	 * @param integer $daka_day 	打卡日期
	 */
    public function set_mark_summary($uid,$distance,$daka_day){
		 $map=array('userid'=>$uid,'status'=>1,'siteid'=>SITEID);
		 $get_summary=D("mark_summary")->where($map)->find();
		 $map_info['siteid']=SITEID;
		 $map_info['status']=1;
		 //修改
		 if($get_summary){	
		 	$summary['ttldistance']=$get_summary['ttldistance']+$distance;	//总距离
		 	$summary['ttlcount']=$get_summary['ttlcount']+1;				//打卡次数

		 	//排名查询条件

			$map_info['ttldistance'] = array('gt',$summary['ttldistance']);
			$summary['ttlranking']=D('mark_summary')->where($map_info)->count()+1;//排名
		 //计算日汇总
		$daily=strtotime(date('Y-m-d',time())); 	//查询当前日期
        $map['daka_day']=$daily;
        $dailydistance=D('mark')->where($map)->getfield('distance',true);
        $summary['dailydistance']= array_sum($dailydistance);		//日汇总
        //计算周汇总
		$arr=array();
		$arr=getdate();	 									//sql返回当前时间
		$num=$arr['wday'];									//当前星期
		if($num == 0){										//星期天=0
			$num = 7;
		}
		$start=strtotime(date('Y-m-d',time()))-($num-1)*24*60*60;
		$end=strtotime(date('Y-m-d',time()))+(7-$num)*24*60*60;
		$map['daka_day']=array('between',array($start,$end));
		$weeklydistance=D('mark')->where($map)->getfield('distance',true);
		$summary['weeklydistance']= array_sum($weeklydistance);		//周汇总
		//计算月汇总
		$start=strtotime(date('Y-m-1',time()));						//当月开始时间
		$end=time();										
		$map['daka_day']=array('between',array($start,$end));
		$monthlydistance=D('mark')->where($map)->getfield('distance',true);
		$summary['monthlydistance']= array_sum($monthlydistance);	//月汇总
		unset($map['daka_day']);
		$set_mark_summary=D('mark_summary')->where($map)->save($summary);	//修改日月周汇总
	}else{
		$summary['userid']=$uid;
		$summary['siteid']=SITEID;
		$summary['ttldistance']=$distance;
		$summary['ttlcount']=1;
		if(strtotime(date('Y-m-d',time())) == $daka_day){
			$summary['dailydistance']=$distance;
		}
		if(date('W',time()) == date('W',$daka_day)){
			$summary['weeklydistance']=$distance;
		}
		if(date('Y-m',time()) == date('Y-m',$daka_day)){
			$summary['monthlydistance']=$distance;
		}
		$summary['update_datetime']=time();
		$map_info['ttldistance'] = array('gt',$summary['ttldistance']);
		$summary['ttlranking']=D('mark_summary')->where($map_info)->count()+1;
		$set_mark_summary=D('mark_summary')->add($summary);
	}
	return($set_mark_summary);
  }
  /**
 * 获取某人打卡汇总
 * @param integer $uid 用户id
 */
  public function get_summary($uid){
  	if(!$uid){
		$this->error('404 not found');
	}
  	$map=array('userid'=>$uid,'status'=>1,'siteid'=>SITEID);
  	$get_summary=D("mark_summary")->where($map)->find();
  	return($get_summary);
  }
  /**
 * 获取某人打卡记录
 * @param integer $uid 用户id
 * @param integer $id  打卡id
 */
  public function get_mark($id,$uid){
  	 if((!$uid) or (!$id)){
			$this->error('404 not found');
	}
  	$map=array('id'=>$id,'status'=>1,'userid'=>$uid,'siteid'=>SITEID);
  	$get_mark=D('mark')->where($map)->find();
  	$get_mark['yuebanname']=D('partner')->where(array('siteid'=>SITEID,'id'=>$get_mark['partnerid']))->getField('title');
  	return($get_mark);
  }
  /**
 * 获取俱乐部关联的约伴活动
 */
  public function get_partner_list($partner_name=""){
  	if($partner_name){
  		$map['title']= array('like',array('%'.$partner_name.'%'));
  	}
  		$map['siteid']=SITEID;
  		$map['status']=1;
  	$partner_list=D('partner')->where($map)->select();
  	return($partner_list);
  }

/**
 * 日 、周、 月汇总排行
 * @param integer $distance 查询字段
 */
  public function fordistance($distance){
  	$map=array('siteid'=>SITEID,'status'=>1);
  	$distance=D('mark_summary')->where($map)->order($distance .' desc')->limit(5)->getfield('userid,' . $distance,true);
			foreach($distance as $k=>$v){
				$daily[]=$v;
				$nickname[]=query_user('nickname',$k);
			}
			$daily=array_reverse($daily);		
			$nickname=array_reverse($nickname);		//数组倒序排列		
			//数组变成带单引号的字符串
			$nickname = join(",",$nickname);
			$nickname = "'".str_replace(",","','",$nickname)."'";	//为插件处理数组
			$daily=implode(',',$daily);
			$distance=array('0'=>$nickname,'1'=>$daily);
	return($distance);
  }
 /**
 * 获取某俱乐部全部打卡
 * @param integer $typecode 运动类型
 * @param integer $start  翻页参数
 */
 public function getListForSite($start=0,$typecode=0){
 	if($typecode){
 		$map=array('siteid'=>SITEID,'status'=>1,'typecode'=>$typecode);
 	}else{
	 	$map=array('siteid'=>SITEID,'status'=>1);
 	}
	$getListForSite=D('mark')->where($map)->order('update_datetime desc')->limit($start,10)->select();
		foreach($getListForSite as $k=>$v){
			$user=$this->get_user($v['userid']);
			$getListForSite[$k]['avatar128']=$user['avatar128'];
			$getListForSite[$k]['nickname']=$user['nickname'];
		}
 	return($getListForSite);
 }
  /**
 * 获取某俱乐部打卡次数、总里程
 * @param integer $typecode 运动类型
 */
  public function get_mark_count($typecode=""){
 	if($typecode){
 		$map=array('siteid'=>SITEID,'status'=>1,'typecode'=>$typecode);
	}else{
  		$map=array('siteid'=>SITEID,'status'=>1);
	}
  	$mark_count=D("mark")->where($map)->count();
  	$ttldistance=D("mark")->where($map)->getfield('distance',true);
  	$mark_ttldistance=array_sum($ttldistance);
  	$mark_ttldistance_count=array('0'=>$mark_count,'1'=>$mark_ttldistance);
  	return($mark_ttldistance_count);
  }
 /**
 * 获取某个用户打卡次数 总里程
 * @param integer $uid 用户id
 */  
public function get_user_markcount($uid){
  	$map=array('siteid'=>SITEID,'status'=>1,'userid'=>$uid);
  	$mark_count=D("mark")->where($map)->count();
  	$ttldistance=D("mark")->where($map)->getfield('distance',true);
  	$mark_ttldistance=array_sum($ttldistance);
  	$user_mark=array('0'=>$mark_count,'1'=>$mark_ttldistance);
  	return($user_mark);
  }
/**
 * 获取用户7日运动量
 * @param integer $uid 用户id
 */
	public function get_seven_ttldistance($uid){
		$map['status']=1;
		$map['siteid']=SITEID;
		$map['userid']=$uid;
		for ($i=0; $i < 7; $i++) { 
			$map['daka_day']= strtotime(date('Y-m-d',time()))-$i*86400;
			$distance[]=array_sum(D('mark')->where($map)->getfield('distance',true));
		}										//天数相加
		$distance=array_reverse($distance);		//反转
		$seven_distance = '';		
		foreach ($distance as $k=>$v) {
			if($v == ''){
				$seven_distance .= "'',";
			}else{ 
				$seven_distance.=$v.',';
			}
		}
		$seven_distance=substr($seven_distance,0,-1);
		return($seven_distance);
	}
 /**
 * 获取某用户全部打卡
 * @param integer $uid 	  用户id
 * @param integer $start  翻页参数
 */
	public function get_mark_list($uid,$start=""){
	 if(!$uid){
		$this->error('404 not found');
		}
		$map=array('userid'=>$uid,'siteid'=>SITEID,'status'=>1);
		$get_mark_list=D('mark')->where($map)->order('update_datetime desc')->limit($start,10)->select();
		return($get_mark_list);
	}
 /**
 * 获取约伴标题
 * @param integer $id 	  约伴id
 */
 	public function get_partner_title($id){
 		$partner_title=D('partner')->where(array('siteid'=>SITEID,'id'=>$id))->getField('title');
 		$partner_img=D('partner')->where(array('siteid'=>SITEID,'id'=>$id))->getField('picture_id');
 		$partner=array('0'=>$partner_title,'1'=>$partner_img);
 		return($partner);
 	}
}

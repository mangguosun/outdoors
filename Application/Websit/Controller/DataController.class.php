<?php

namespace Websit\Controller;

use Think\Controller;

class DataController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize();    
	}
	public function index(){
		$status=$_GET['status'];
		$status = isset($status)? $status:0;
		switch($status){

			case 0:			
			/*111111111111111111111111111111111111会员统计部分begin111111111111111111111111111111111*/
				/****历史注册会员总计****/
				$allmember = D('member')->where(array('siteid'=>SITEID))->count();
				/****当月注册会员总计****/
				$map = "siteid = ".SITEID." and MONTH(FROM_UNIXTIME(reg_time,'%Y-%m-%d')) = MONTH(NOW()) and YEAR(FROM_UNIXTIME(reg_time,'%Y-%m-%d')) = YEAR(NOW())";				
				$monthmember = D('member')->where($map)->count();
				/****当天注册会员总计****/
				$where = "siteid = ".SITEID." and DAYOFYEAR(FROM_UNIXTIME(reg_time,'%Y-%m-%d')) = DAYOFYEAR(NOW()) and YEAR(FROM_UNIXTIME(reg_time,'%Y-%m-%d')) = YEAR(NOW())";
				$daymember = D('member')->where($where)->count();
				$this->assign('allmember',$allmember);
				$this->assign('monthmember',$monthmember);
				$this->assign('daymember',$daymember);
			/*111111111111111111111111111111111111会员统计部分end111111111111111111111111111111111111*/
				
						
			/*222222222222222222222222222222222222线路统计部分begin2222222222222222222222222222222222*/
				/****线路总数统计****/
				$allevent = D('event')->where(array('siteid'=>SITEID))->count();
				/****当天线路统计****/
				$map = "siteid = ".SITEID." and DAYOFYEAR(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = DAYOFYEAR(NOW()) and YEAR(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = YEAR(NOW())";
				$dayevent = D('event')->where($map)->count();
				$this->assign('allevent',$allevent);
				$this->assign('dayevent',$dayevent);
			/*222222222222222222222222222222222222线路统计结束end222222222222222222222222222222222222*/
			
						
			/*333333333333333333333333333333333333故事统计部分begin3333333333333333333333333333333333*/
				/****故事总数统计****/
				$allstory = D('issue_content')->where(array('siteid'=>SITEID))->count();
				/****当天故事统计****/
				$map = "siteid = ".SITEID." and DAYOFYEAR(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = DAYOFYEAR(NOW()) and YEAR(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = YEAR(NOW())";
				$daystory = D('issue_content')->where($map)->count();
				$this->assign('allstory',$allstory);
				$this->assign('daystory',$daystory);
			/*333333333333333333333333333333333333故事统计部分end333333333333333333333333333333333333*/
			
			
			/*444444444444444444444444444444444444排期统计部分begin4444444444444444444444444444444444*/
				/****排期总数统计****/
				$allschedule = D('event_calendar_time')->where(array('siteid'=>SITEID))->count();
				/****当天排期统计****/
				$map = "siteid = ".SITEID." and DAYOFYEAR(FROM_UNIXTIME(time,'%Y-%m-%d')) = DAYOFYEAR(NOW()) and YEAR(FROM_UNIXTIME(time,'%Y-%m-%d')) = YEAR(NOW())";
				$dayschedule = D('event_calendar_time')->where($map)->count();
				$this->assign('allschedule',$allschedule);
				$this->assign('dayschedule',$dayschedule);
			/*444444444444444444444444444444444444排期统计部分end444444444444444444444444444444444444*/
			
			
			/*555555555555555555555555555555555555活动订单统计部分begin555555555555555555555555555555*/
				/****活动订单总数统计****/
				$allorder = D('event_attend')->where(array('siteid'=>SITEID))->count();
				/****当天活动订单统计****/
				$map = "siteid = ".SITEID." and DAYOFYEAR(FROM_UNIXTIME(creat_time,'%Y-%m-%d')) = DAYOFYEAR(NOW()) and YEAR(FROM_UNIXTIME(creat_time,'%Y-%m-%d')) = YEAR(NOW())";
				$dayorder = D('event_attend')->where($map)->count();
				$this->assign('allorder',$allorder);
				$this->assign('dayorder',$dayorder);
			/*555555555555555555555555555555555555活动订单统计部分end55555555555555555555555555555555*/
			
			
			/*666666666666666666666666666666666666评论统计部分begin6666666666666666666666666666666666*/
				/****评论总数统计****/
				$allcomment = D('local_comment')->where(array('siteid'=>SITEID))->count();
				/****当天评论总数****/
				$map = "siteid = ".SITEID." and DAYOFYEAR(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = DAYOFYEAR(NOW()) and YEAR(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = YEAR(NOW())";
				$daycomment = D('local_comment')->where($map)->count();
				$this->assign('allcomment',$allcomment);
				$this->assign('daycomment',$daycomment);
			/*666666666666666666666666666666666666评论统计部分end666666666666666666666666666666666666*/
			
			break;
		}
		$this->assign('status',$status);
		
		$this->display();
    }	
}  
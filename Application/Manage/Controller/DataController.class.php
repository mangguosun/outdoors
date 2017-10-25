<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;

/*评论管理*/
class DataController extends BaseController{
	
   
    public function _initialize(){
        parent::_initialize();
		
	}
	
   	



   	public function index(){ 
   		$this->all_information();
   		$this->display();

   	}
   	public function picture_show(){ 
   		$this->all_information();
   		$this->display();

   	}

   	public function monthMap($time='time'){ 
   		$map = "siteid = ".SITEID." and MONTH(FROM_UNIXTIME(".$time.",'%Y-%m-%d')) = MONTH(NOW()) and YEAR(FROM_UNIXTIME(".$time.",'%Y-%m-%d')) = YEAR(NOW())";
   		return $map;

   	}
   	public function dayMap($time='time'){
   		$where = "siteid = ".SITEID." and DAYOFYEAR(FROM_UNIXTIME(".$time.",'%Y-%m-%d')) = DAYOFYEAR(NOW()) and YEAR(FROM_UNIXTIME(".$time.",'%Y-%m-%d')) = YEAR(NOW())"; 
   		return $where;

   	}
   	public function all_information(){ 
			/****历史注册会员总计********************************/
			$allmember = D('member')->where(array('siteid'=>SITEID))->count();
			/****当月注册会员总计****/				
			$monthmember = D('member')->where($this->monthMap('reg_time'))->count();
			/****以前注册会员总计****/	
			$othermember=$allmember-$monthmember;
			/****当天注册会员总计****/	
			$daymember = D('member')->where($this->dayMap('reg_time'))->count();
			$monthmember=$monthmember-$daymember;
			$this->assign('othermember',$othermember);
			$this->assign('allmember',$allmember);
			$this->assign('monthmember',$monthmember);
			$this->assign('daymember',$daymember);

			/****线路总数统计*********************************/
			$allevent = D('event')->where(array('siteid'=>SITEID))->count();
			/****当月线路统计****/
			$monthevent=D('event')->where($this->monthMap('create_time'))->count();
			$otherevent=$allevent-$monthevent;
			/****当天线路统计****/		
			$dayevent = D('event')->where($this->dayMap('create_time'))->count();
			$monthevent=$monthevent-$dayevent;
			$this->assign('allevent',$allevent);
			$this->assign('monthevent',$monthevent);
			$this->assign('otherevent',$otherevent);
			$this->assign('dayevent',$dayevent);

			/****故事总数统计**************************************/
			$allstory = D('issue_content')->where(array('siteid'=>SITEID))->count();
			/****当月故事统计****/
			$monthstory=D('issue_content')->where($this->monthMap('create_time'))->count();
			$otherstory=$allstory-$monthstory;			
			/****当天故事统计****/			
			$daystory = D('issue_content')->where($this->dayMap('create_time'))->count();
			$monthstory=$monthstory-$daystory;
			$this->assign('otherstory',$otherstory);
			$this->assign('monthstory',$monthstory);
			$this->assign('allstory',$allstory);
			$this->assign('daystory',$daystory);

			/****排期总数统计****/
			$allschedule = D('event_calendar_time')->where(array('siteid'=>SITEID))->count();
			
			/****当月排期统计****/
			$monthschedule=D('event_calendar_time')->where($this->monthMap('time'))->count();
			$otherschedule=$allschedule-$monthschedule;
			/****当天排期统计****/
			$dayschedule = D('event_calendar_time')->where($this->dayMap('time'))->count();
			$monthschedule=$monthschedule-$dayschedule;
			$this->assign('allschedule',$allschedule);
			$this->assign('monthschedule',$monthschedule);
			$this->assign('otherschedule',$otherschedule);
			$this->assign('dayschedule',$dayschedule);
			
			/****定制订单总统计***/
			$allcustom = D('event_tailor')->where(array('siteid'=>SITEID))->count();
			/****当月定制统计****/
			$monthcustom=D('event_tailor')->where($this->monthMap('createtime'))->count();
			$othercustom=$allcustom-$monthcustom;
			/****当天定制统计****/
			$daycustom = D('event_tailor')->where($this->dayMap('createtime'))->count();
			$monthcustom=$monthcustom-$daycustom;
			$this->assign('allcustom',$allcustom);
			$this->assign('monthcustom',$monthcustom);
			$this->assign('othercustom',$othercustom);
			$this->assign('daycustom',$daycustom);

			/****活动订单总数统计****/
			$allorder = D('event_attend')->where(array('siteid'=>SITEID))->count();
			/****当月活动订单统计****/
			$monthorder=D('event_attend')->where($this->monthMap('creat_time'))->count();
			$otherorder=$allorder-$monthorder;
			/****当天活动订单统计****/
			
			$dayorder = D('event_attend')->where($this->dayMap('creat_time'))->count();
			$monthorder=$monthorder-$dayorder;

			$this->assign('allorder',$allorder);
			$this->assign('monthorder',$monthorder);
			$this->assign('otherorder',$otherorder);			
			$this->assign('dayorder',$dayorder);
			
			/****评论总数统计****/
			$allcomment = D('local_comment')->where(array('siteid'=>SITEID))->count();
			/****当月评论总数****/
			$monthcomment=D('local_comment')->where($this->monthMap('create_time'))->count();
			$othercomment=$allcomment-$monthcomment;
			/****当天评论统计****/
			
			$daycomment = D('local_comment')->where($this->dayMap('create_time'))->count();
			$monthcomment=$monthcomment-$daycomment;
			$this->assign('allcomment',$allcomment);
			$this->assign('monthcomment',$monthcomment);
			$this->assign('othercomment',$othercomment);
			$this->assign('daycomment',$daycomment);
			
			/*********************商品统计部分*******************************/
			$allgoods = D('shop')->where(array('siteid'=>SITEID))->count();//商城货物总数
			$ongoods = D('shop')->where(array('siteid'=>SITEID,status=>1))->count();//商城在售货物总数
			$outgoods = D('shop')->where(array('siteid'=>SITEID,status=>2))->count();//商城下架货物总数
			$offgoods = D('shop')->where(array('siteid'=>SITEID,status=>0))->count();//商城禁用货物总数
			$this->assign('allgoods',$allgoods);
			$this->assign('ongoods',$ongoods);
			$this->assign('outgoods',$outgoods);
			$this->assign('offgoods',$offgoods);
			
			$todayorder = D('shop_ordersn')->where($this->dayMap('create_time'))->count();
			$todayprice = D('shop_ordersn')->where($this->dayMap('create_time'))->sum('alltotalprice');
			if(!$todayprice){$todayprice=0;}
			$todayprice=sprintf("%.2f",  $todayprice); 
			if($todayorder==0){
					$guest_unit_price=0;
			}else{
				$guest_unit_price=sprintf("%.2f",  $todayprice/$todayorder); //保留两位
			}
			$this->assign('allgoods',$allgoods);
			$this->assign('todayorder',$todayorder);
			$this->assign('todayprice',$todayprice);
			$this->assign('guest_unit_price',$guest_unit_price);
				/***************今日概括结束*********************/
			
			

   		
   	}

   	public function shop_count(){ 
   		

   		$this->display();
   	}

	

}  
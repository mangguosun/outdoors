<?php
/**
 * 所属项目 110.
 * 开发者: 陈一枭
 * 创建日期: 2014-11-18
 * 创建时间: 10:27
 * 版权所有 想天软件工作室(www.ourstu.com)
 */

namespace Common\Model;

use Think\Model;

class EventModel extends Model
{
/****************************************公共的一些活动相关信息方法****************************************/
	

	/*
	*  获取活动排期下的订单信息
	*  $id 排期id
	*  $map array 筛选订单条件 为空时 查找排期下所有订单
	*/
	public function getCalendarOrder($id, $map=''){ 
		if(empty($map)){ 
			$map['calendar_id'] = $id;
			$map['siteid'] = SITEID;
			$list = D('event_attend')->where($map)->order('creat_time',desc)->select();
			return $list;
		}
		$map['calendar_id'] = $id;
		$map['siteid'] = SITEID;
		$list = D('event_attend')->where($map)->order('creat_time',desc)->select();
		return $list;
	}
	/*
	* 同一排期活动的参加者
	* $id 排期id
	* 
	*/
	public function getCalendarSigner($id){ 
		$map['status'] = 1;
		$map['siteid'] = SITEID;
		$map['calendar_id'] = $id;
		$map['order_status'] = array(array('neq', -1),array('neq', 0));
		$list = D('event_signer')->where($map)->order('id desc')->select();
		foreach($list as $key => $val){		
			$list[$key]['user_info'] = json_decode($val['user_info'],true);
		}
		return $list;
	}

	

/*********************************公共的一些活动相关信息方法*************************************************/
	
	/*
	* 活动列表页展示数据
	* 	
	*/
	public function getEventList($page = 1, $type_id = 0, $norh = 'new'){

		//$ceshitime['start'] = microtime();


		$map = "status = 1 and siteid=".SITEID;
		$type_id = intval($_GET['type_id']);
		$tag = intval($_GET['tag']);
		$starttime = intval($_GET['starttime']);
		$price = intval($_GET['price']);	
		$holiday = intval($_GET['holiday']);
		$finalcity = intval($_GET['finalcity']);
		$days = intval($_GET['days']);
		$recent = intval($_GET['recent']);
		$keywords = trim(op_t(I('post.keywords')));
		$custom = $_GET['custom'];
		if($keywords != ''){
			$map .= " and title like '%$keywords%'";
		}
		//判断get过来的类型
		if($type_id != ''){
			if($type_id == 'all'){
				unset($type_id);
			}else{
				$map .= " and type_id = $type_id";
			}
		}
		if($tag != ''){
			if($tag == 'all'){
				unset($tag);
			}else{
				$map .= " and find_in_set($tag,tag)";
			}
		}		
		if($finalcity){
			$getdata = D('district')->where(array('id'=>$finalcity))->find();
			if($getdata){
				$map .= " and finalcity in($getdata[arrchildid])";
			}
		}	
		if($starttime){
			$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = $starttime";	
		}
		
		switch ($recent) {
			case '1';					
					$map .= " and (WEEKDAY(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 4 or WEEKDAY(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 5 or WEEKDAY(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 6)";
			break;

			case '2';
				$map .= " and DAYOFYEAR(FROM_UNIXTIME(abs(lasted_time - UNIX_TIMESTAMP(NOW())))) <= 3 and DAYOFYEAR(FROM_UNIXTIME(abs(lasted_time - UNIX_TIMESTAMP(NOW())))) > 0"; 
			break;

			case '3';
				$map .= " and DAYOFYEAR(FROM_UNIXTIME(abs(lasted_time - UNIX_TIMESTAMP(NOW())))) <= 7 and DAYOFYEAR(FROM_UNIXTIME(abs(lasted_time - UNIX_TIMESTAMP(NOW())))) > 0"; 
			break;

			case '4';
				$map .= " and DAYOFYEAR(FROM_UNIXTIME(abs(lasted_time - UNIX_TIMESTAMP(NOW())))) <= 14 and DAYOFYEAR(FROM_UNIXTIME(abs(lasted_time - UNIX_TIMESTAMP(NOW())))) > 0"; 
			break;

			case '5';
				$map .= " and DAYOFYEAR(FROM_UNIXTIME(abs(lasted_time - UNIX_TIMESTAMP(NOW())))) <= 30 and DAYOFYEAR(FROM_UNIXTIME(abs(lasted_time - UNIX_TIMESTAMP(NOW())))) > 0"; 
			break;

			case '6';
				$map .= " and DAYOFYEAR(FROM_UNIXTIME(abs(lasted_time - UNIX_TIMESTAMP(NOW())))) <= 90 and DAYOFYEAR(FROM_UNIXTIME(abs(lasted_time - UNIX_TIMESTAMP(NOW())))) > 0"; 
			break;
			default:
			break;
		
		}

		if($custom){ 

			$map .= " and custom = $custom";
		}

		switch($price){
			case '1';
				$map .= " and price <= 1000 and price > 0";
			break;
			case '2';
				$map .= " and price > 1000 and price <= 2000";
			break;
			case '3';
				$map .= " and price > 2000 and price <= 3000";
			break;
			case '4';
				$map .= " and price > 3000 and price <= 4000";
			break;
			case '5';
				$map .= " and price > 4000 and price <= 5000";
			break;
			case '6';
				$map .= " and price >= 5000";
			break;
			default:
			break;
		}

		switch ($days) {
			case '1':
				$map .= " and traveldays >= 1 and traveldays <= 2";
				break;
			case '2':
				$map .= " and traveldays >= 3 and traveldays <= 7";
				break;
			case '3':
				$map .= " and traveldays > 7";
				break;
			default:
				break;
		}

		switch($holiday){
			case '1';
				$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 1 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 1 and 3 and lasted_time != 0";
			break;
			case '2';
				$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 2 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 17 and 24";
			break;
			case '3';
				$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 4 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 4 and 6";
			break;
			case '4';
				$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 5 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 1 and 3";
			break;
			case '5';
				$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 6 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 20 and 22";
			break;
			case '6';
				$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 7 and 8";
			break;
			case '7';
				$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 9 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 27 and 29";
			break;
			case '8';
				$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 10 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 1 and 7";
			break;
			case '9';
				$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 12 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 25 and 27";
			break;
			case '10';				
				$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 1 and 2";
			break;
			default:
			break;
		}

        $order =" abs(TIMESTAMPDIFF(DAY,FROM_UNIXTIME(lasted_time,'%Y-%m-%d'),now())) , diff_time  ";
        $norh == 'hot' && $order = 'signCount desc';
        $content = D('Event')->field('id,uid,title,description,traveldays,begincity,finalcity,paytype,price,price_text,tag,lasted_time,type_id,cover_id')->where($map)->order($order)->page($page, 10)->select();
        $totalCount = D('Event')->where($map)->count();


		$str = "<d style='color:red'>".$keywords."</d>";
        foreach ($content as &$v) {
			if($keywords != ''){
				$v['title'] = str_replace($keywords,$str,$v['title']);
			}
			if($v['lasted_time'] != 0){
				$v['lasted_time'] = date("Y-m-d",$v['lasted_time']);
			}else{
				$v['lasted_time'] ='';
			}

			$v['schedule_arr'] = $this->getSchedule($v['id']);//排期信息
			
			$event_content['tagarr'] = explode(',',$v['tag']);
			foreach ($event_content['tagarr'] as $key => $a) {
				$v['tags'][$a]['id'] = $a;
				$v['tags'][$a]['name'] = get_event_tag($a);
			}
        }
        unset($v);
		if($show != "<div class='pagination'>    </div>"){
			
			$eventList['page'] = $show;
		}
		
		if($type_id){
			$type_arr = D('event_type')->where(array('status' => 1 ,'siteid'=>SITEID,'id'=>$type_id))->cache(true,3600)->find();
			$eventList['customization'] = $type_arr['customization'];
		}

		$eventList['type_id'] = $type_id;
		$eventList['contents'] = $content;
		$eventList['norh'] = $norh;
		$eventList['totalPageCount'] = $totalCount;

		return $eventList;
	}

	/*
	* 活动详情的信息
	* $id 活动id
	* $delS 强制删除缓存
	*/
	public function getEventDetail($id,$delS=false){ 
		$detail_Eventid_keys = 'detail_Eventid_'.SITEID.'-'.$id;
	    D('Event')->where(array('id' => $id))->setInc('view_count');
		if($delS){ 
			S($detail_Eventid_keys,null);
			S("mobileEventDetail".SITEID."_".$id ,null);
			return false;
		}
		$detailEventS = S($detail_Eventid_keys);
		
		if($detailEventS){ 
			return $detailEventS;
		}
		$event_content = D('Event')->where(array('status' => 1, 'id' => $id,'siteid'=>SITEID))->find();
	    if (!$event_content) {
	    	 $detailEvent['error'] = '404';
	    	 return $detailEvent;
        }
        $star_con = D('event_attribute')->where(array('event_id'=>$id,'siteid'=>SITEID))->select();
		$event_content['star_con'] = $star_con;
        if($event_content['lasted_time'] != 0){ 
        	$event_content['lasted_time'] = date("Y-m-d ",$event_content['lasted_time'] );
        }else{ 
        	$event_content['lasted_time'] = '敬请期待';
        }
        $event_content['user'] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $event_content['uid']);
        $event_content['type'] = $this->getType($event_content['type_id']);
        $menber = D('event_attend')->where(array('event_id' => $id, 'status' => 1))->select();
        foreach ($menber as $k => $v) {
            $event_content['member'][$k] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $v['uid']);

        }
		$event_content['travel_table'] = json_decode($event_content['travel_table'],true);
		$event_content['tagarr'] = explode(',',$event_content['tag']);
		foreach ($event_content['tagarr'] as $k => $v) {
				$tags[$v]['id'] = $v;
				$tags[$v]['name'] = get_event_tag($v);
        }		
		$event_calendar = $this->getEventCalendarTime($id);
		foreach ($event_calendar as $key => $av){
			$leaders_arr = explode(',',$av['leader']);
				foreach ($leaders_arr as $k => &$le){
					$_leaders[] = $le;
				}    
        }
		foreach ($event_calendar as $key => &$v){
			$detail_schedule[$key]['id']= $v['id'];
			$detail_schedule[$key]['price']= $v['price'];
			$detail_schedule[$key]['paytype']= $v['paytype'];
			switch ($v['paytype']) {
				case 0:
					$detail_schedule[$key]['paytype']= '<b style="color: #5CB5EE;">全额</b>';
					$detail_schedule[$key]['dispaytype']= 0;
					break;
				case 1:
					$detail_schedule[$key]['paytype']= '<b style="color: #58CB66;">定金</b>';
					$detail_schedule[$key]['dispaytype']= 1;
					break;
				case 2:
					$detail_schedule[$key]['paytype']= '<b style="color: #F20858;">免费</b>';
					$detail_schedule[$key]['dispaytype']= 2;
					break;
				default:
					$detail_schedule[$key]['paytype']= 'error';
					break;
			}
			$nowticket = $v['maxpeople'] - $v['regnumber'];
			$seats= $v['days_left']?$v['days_left']:'有座';	
				if($v['status'] == 1){						
					if(strtotime("$v[endtime]")-time() > 0){
						if($v['maxpeople'] != 0){
							if(($v['maxpeople']-$v['regnumber']) < 0){
								$v['reg_num'] = D('event_signer')->where(array('siteid'=>SITEID,'calendar_id'=>$v['id'],'order_status'=>1))->count();
								$detail_schedule[$key]['msgtext']= '报满候补';
								$detail_schedule[$key]['status']= 3;
								$detail_schedule[$key]['seats']= !empty($v['reg_num']) ?'候补'.$v['reg_num'].'人' : '∞' ;
							}else{
								$detail_schedule[$key]['msgtext']= '接受报名';
								$detail_schedule[$key]['status']= 1;
								$detail_schedule[$key]['seats']= $seats;
							}
						}else{
								$detail_schedule[$key]['msgtext']= '接受报名';
								$detail_schedule[$key]['status']= 1;
								$detail_schedule[$key]['seats']= $seats;
						}
					}else{
						$dataList['del'][] =$key; 
						if(strtotime("$v[starttime]")-time() > 0){
							$detail_schedule[$key]['msgtext']= '报名截止';
							$detail_schedule[$key]['status']= 4;
							$detail_schedule[$key]['seats']= '--';
						}else{
							if(strtotime("$v[overtime]")-time() >= 0){
								$detail_schedule[$key]['msgtext']= '进行中';
								$detail_schedule[$key]['status']= 5;
								$detail_schedule[$key]['seats']= '--';
							}else{
								$detail_schedule[$key]['msgtext']= '已结束';
								$detail_schedule[$key]['status']= 6;
								$detail_schedule[$key]['seats']= '--';
							}
						}
					}
				}elseif($v['status'] == 2){
					$detail_schedule[$key]['msgtext']= '接受报名';
					$detail_schedule[$key]['status']= 2;
					$detail_schedule[$key]['seats']= $seats;
				}elseif($v['status'] == 3){
					$v['reg_num'] = D('event_signer')->where(array('siteid'=>SITEID,'calendar_id'=>$v['id'],'order_status'=>1))->count();
					$detail_schedule[$key]['msgtext']= '报满候补';
					$detail_schedule[$key]['status']= 3;
					$detail_schedule[$key]['seats']= !empty($v['reg_num']) ?'候补'.$v['reg_num'].'人' : '∞' ;
				}elseif($v['status'] == 4){
					$detail_schedule[$key]['msgtext']= '报名截止';
					$detail_schedule[$key]['status']= 4;
					$detail_schedule[$key]['seats']= '--';
				}elseif($v['status'] == 5){
					$detail_schedule[$key]['msgtext']= '进行中';
					$detail_schedule[$key]['status']= 5;
					$detail_schedule[$key]['seats']= '--';
				}elseif($v['status'] == 6){
					$detail_schedule[$key]['msgtext']= '已结束';
					$detail_schedule[$key]['status']= 6;
					$detail_schedule[$key]['seats']= '--';
				}
			$weekarray=array("日","一","二","三","四","五","六");	
			$detail_schedule[$key]['starttime']= strtotime($v['starttime']);
			$detail_schedule[$key]['disstarttime']= date('Y-m-d',strtotime($v['starttime']));
            $detail_schedule[$key]['Weekstarttime'] = "星期".$weekarray[date("w",strtotime($v['starttime']))];
			$detail_schedule[$key]['overtime']= strtotime($v['overtime']);
			$detail_schedule[$key]['disovertime']= date('Y-m-d',strtotime($v['overtime']));
			$detail_schedule[$key]['Weekovertime'] = "星期".$weekarray[date("w",strtotime($v['overtime']))];
			$detail_schedule[$key]['endtime']= strtotime($v['endtime']);
			$detail_schedule[$key]['disendtime']= date('Y-m-d',strtotime($v['endtime']));
			$detail_schedule[$key]['Weekendtime'] = "星期".$weekarray[date("w",strtotime($v['endtime']))];
			$detail_schedule[$key]['days']= $v['days'];
			$detail_schedule[$key]['vehicle']= $v['vehicle'];
			$detail_schedule[$key]['accommodation']= $v['accommodation'];
			$detail_schedule[$key]['team_name']= ($v['team_name'] != '')?$v['team_name']:'无';
		}
		foreach ($dataList['del'] as $ke => &$val) {
			unset($detail_schedule[$val]);
		}
		$leaders = array_unique($_leaders);
		unset($dataList['del']);
		unset($_leaders);
		$leaders_string='';
		foreach ($leaders as $ku=> &$u) {
			$member = D('member')->where(array('uid' => $u))->find();
			if(!$member) continue;
			$leaders_string .='<a target="_blank" href="'.U('Usercenter/Index/index',array('uid'=>$member['uid'])).'">'.$member['nickname'].'</a> ';
		}	
	    if($event_content['pictures_id']) {
            $pictures = M("Picture")->field('id,path')->where("id in ({$event_content['pictures_id']})")->select();
            foreach ($pictures as &$img) {
                $img['path'] = fixAttachUrl($img['path']);
            }
            unset($img);
            $detailEvent['pictures'] = $pictures;
        }
		if($event_content['type_id']){
			$type_arr = D('event_type')->where(array('status' => 1 ,'siteid'=>SITEID,'id'=>$event_content['type_id']))->find();
			$detailEvent['customization']  = $type_arr['customization'];
		}
		
		$get_qrcode = D('qrcode')->where(array('types'=>'event','siteid'=>SITEID,'linkid'=>$id))->find();
		if($get_qrcode){
			$detailEvent['qrcode_link']   = $get_qrcode['url'];
		}
		$tpid = D('local_comment')->where(array('status'=>1,'app'=>'Event','mod'=>'event','row_id'=>$event_content['id']))->count();
		
		$detailEvent['tpid']            = $tpid;
		$detailEvent['detail_schedule'] = $detail_schedule;
		$detailEvent['leaders']         = $leaders_string;
		$detailEvent['tags']            = $tags;
		$detailEvent['in_event']        = $event_calendar['in_event'];
		$detailEvent['content']         = $event_content;
		S($detail_Eventid_keys,$detailEvent,3600);
		return $detailEvent;
        
	} 


	/*
	* 判断排期状态
	*	$id 排期id
	*/


	public function getCalendarStatus($id){ 
		$map = " siteid = ".SITEID ;
		$map .= " and status > 0 and status < 4 ";
		$map .= " and display = 1 ";
		$map .= " and  id = ".$id ;
		$event_calendar = D("event_calendar_time")->field('id,maxpeople,regnumber,days_left,endtime,status,starttime,overtime')->where($map)->cache(true,1800)->select(); 
		
		foreach ($event_calendar as $key => &$v){

			$nowticket = $v['maxpeople'] - $v['regnumber'];
			$seats= $v['days_left']?$v['days_left']:'有座';	
				if($v['status'] == 1){						
					if(strtotime("$v[endtime]")-time() > 0){
						if($v['maxpeople'] != 0){
							if(($v['maxpeople']-$v['regnumber']) < 0){
								$v['reg_num'] = D('event_signer')->where(array('siteid'=>SITEID,'calendar_id'=>$v['id'],'order_status'=>1))->count();
								$detail_schedule['msgtext']= '报满候补';
								$detail_schedule['status']= 3;
								$detail_schedule[$key]['seats']= !empty($v['reg_num']) ?'候补'.$v['reg_num'].'人' : '∞' ;
							}else{
								$detail_schedule['msgtext']= '接受报名';
								$detail_schedule['status']= 1;
								$detail_schedule['seats']= $seats;
							}
						}else{
								$detail_schedule['msgtext']= '接受报名';
								$detail_schedule['status']= 1;
								$detail_schedule['seats']= $seats;
						}
					}else{

						if(strtotime("$v[starttime]")-time() > 0){
							$detail_schedule['msgtext']= '报名截止';
							$detail_schedule['status']= 4;
							$detail_schedule['seats']= '--';
						}else{
							if(strtotime("$v[overtime]")-time() >= 0){
								$detail_schedule['msgtext']= '进行中';
								$detail_schedule['status']= 5;
								$detail_schedule['seats']= '--';
							}else{
								$detail_schedule['msgtext']= '已结束';
								$detail_schedule['status']= 6;
								$detail_schedule['seats']= '--';
							}
						}
					}
				}elseif($v['status'] == 2){

					$detail_schedule['msgtext']= '接受报名';
					$detail_schedule['status']= 2;
					$detail_schedule['seats']= $seats;
				}elseif($v['status'] == 3){
					
					$detail_schedule['msgtext']= '报满候补';
					$detail_schedule['status']= 3;
					
				}elseif($v['status'] == 4){
					$detail_schedule['msgtext']= '报名截止';
					$detail_schedule['status']= 4;
					$detail_schedule['seats']= '--';
				}elseif($v['status'] == 5){
					$detail_schedule['msgtext']= '进行中';
					$detail_schedule['status']= 5;
					$detail_schedule['seats']= '--';
				}elseif($v['status'] == 6){
					$detail_schedule['msgtext']= '已结束';
					$detail_schedule['status']= 6;
					$detail_schedule['seats']= '--';
				}
			
		}
		
		return $detail_schedule;

	}


	/*
	* 获取活动下排期信息
	* $id 活动的id
	* $find 活动下的一个排期信息
	* $delS 强制清除缓存
	* $calendar_info array 返回排期信息
	*/
	public function getEventCalendarTime($id,$find='',$delS=false)
	{	
		$keys = SITEID.'calendar-Event'.$id;
		if($delS){ 
			S($keys,null);
			$siteid = SITEID;
			$this->getEventDetail($id,$delS=true);
			update_event($siteid);
		}
		$calendar_info_S = S($keys);
		if($calendar_info_S){
			if($find){ 
				return $calendar_info_S[$find];
			}else{ 
				return $calendar_info_S;
			}  
		}
		$map = "eventid = $id and siteid = ".SITEID." and status >=1 and  status <= 3 and display = 1";
		$calendar_info = D('event_calendar_time')->where($map)->select();
		if($calendar_info){
			foreach ($calendar_info as $key=> &$v) {			
				if($v['status'] == 1){
					if(strtotime($v['endtime'])-time() > 0){
						$v['com_time'] = strtotime($v['starttime']);
					}else{
						$v['com_time'] = abs(-strtotime($v['starttime']) + time()) + time() * 3;
					}			
				}elseif($v['status'] == 2 || $v['status'] == 3){
					if(strtotime($v['endtime'])-time() > 0){
						$v['com_time'] = strtotime($v['starttime']);
					}else{	
						$v['com_time'] = strtotime($v['starttime']) + time();
					}		
				}elseif($v['status'] == 4 || $v['status'] == 5 || $v['status'] == 6){
					if(strtotime($v['endtime'])-time() > 0){
						$v['com_time'] = abs(-strtotime($v['starttime']) + time()) + time() * 2;
					}else{
						$v['com_time'] = abs(-strtotime($v['starttime']) + time()) + time() * 3;
					}
				}
			}		
			usort($calendar_info, function($a, $b){							
				$al = $a['com_time'];
				$bl = $b['com_time'];
				if ($al == $bl)
					return 0;
				return ($al > $bl) ? 1 : -1;
			});	
			S($keys,$calendar_info,3600);
			if($find){
				return $calendar_info[$find];
			}else{			
				return $calendar_info;					
			}		
		}else{
			return '';	
		}	
	}
	/*
	* 排期时间处理
	* 筛选排期  返回可以报名或能候补的排期
	* $event_id 活动id
	* $find 排期id
	*/
	public function getSchedule($event_id ,$find = ''){
		$schedule_arr =$this->getEventCalendarTime($event_id ,$find = '');
		
		if($find){ 
			$keys = "Event_time_".SITEID."_".$event_id."_".$find;
		}else{ 
			$keys = "Event_time_".SITEID."_".$event_id;
		}
		
		$time_arr_S = S($keys);

		if($time_arr_S){ 
			return $time_arr_S;
		}

		$time_arr = array();
		$weekarray=array("日","一","二","三","四","五","六");	
		foreach($schedule_arr as $key => $val){
			if($val['status'] == 1){
				if(strtotime($val['endtime'])-time() <= 0){
					unset($schedule_arr[$key]);
				}else{
					$time_arr[$key]['team_name'] = ($val['team_name'] != '')?$val['team_name']:'无';
					$time_arr[$key]['endtime'] = date('Y.m.d',strtotime($val['endtime']));
					$time_arr[$key]['endtimetype'] = strtotime($val['endtime']);
					$time_arr[$key]['disendtime'] =  "周".$weekarray[date("w",strtotime($val['endtime']))];
					$time_arr[$key]['starttime'] = date('Y.m.d',strtotime($val['starttime']));
					$time_arr[$key]['starttimetype'] = strtotime($val['starttime']);
					$time_arr[$key]['disstarttime'] =  "周".$weekarray[date("w",strtotime($val['starttime']))];
					$time_arr[$key]['overtime'] = date('Y.m.d',strtotime($val['overtime']));
					$time_arr[$key]['overtimetype'] = strtotime($val['overtime']);
					$time_arr[$key]['disovertime'] =  "周".$weekarray[date("w",strtotime($val['overtime']))];
					$time_arr[$key]['price'] = !empty($val['price']) ? '￥'.$val['price'] : '免费活动' ;
					if(!empty($val['maxpeople'])){
						$time_arr[$key]['text'] = $val['maxpeople'] > $val['regnumber'] ? '马上报名' : '马上候补';
						$time_arr[$key]['status'] = $val['maxpeople'] > $val['regnumber'] ? 2 : 3; 
					}else{
						$time_arr[$key]['text'] = '马上报名';
						$time_arr[$key]['status'] = 2; 
					}
				}
			}elseif($val['status'] == 2 || $val['status'] == 3){
				$time_arr[$key]['team_name'] = ($val['team_name'] != '')?$val['team_name']:'无';
				$time_arr[$key]['endtime'] = date('Y.m.d',strtotime($val['endtime']));
				$time_arr[$key]['endtimetype'] = strtotime($val['endtime']);
				$time_arr[$key]['disendtime'] =  "周".$weekarray[date("w",strtotime($val['endtime']))];
				$time_arr[$key]['starttime'] = date('Y.m.d',strtotime($val['starttime']));
				$time_arr[$key]['starttimetype'] = strtotime($val['starttime']);
				$time_arr[$key]['disstarttime'] =  "周".$weekarray[date("w",strtotime($val['starttime']))];
				$time_arr[$key]['overtime'] = date('Y.m.d',strtotime($val['overtime']));
				$time_arr[$key]['overtimetype'] = strtotime($val['overtime']);
				$time_arr[$key]['disovertime'] =  "周".$weekarray[date("w",strtotime($val['overtime']))];
				$time_arr[$key]['price'] = '￥'.$val['price'];
				$time_arr[$key]['text'] = $val['status'] == 2 ? '马上报名' : '马上候补';
				$time_arr[$key]['status'] = $val['status'];
			}elseif($val['status'] <= 0 || $val['status'] > 3){				
				unset($schedule_arr[$key]);				
			}				
		}

		S($keys,$time_arr,3600);

		return $time_arr;
	}
	/**
     * 获取活动类型
     * @param $type_id
     * @return mixed
     * autor:xjw129xjt
     */
    private function getType($type_id)
    {
        $type = D('EventType')->where('id=' . $type_id)->find();
        return $type;
    }

	/*
	* 活动信息
	* 推荐活动信息或最新活动
	* $recommend 判断是推荐活动信息
	*/
	public function getEventRecommend($limit=3,$page=1 ,$recommend='',$order='',$event_type=''){ 

		$map['status'] = 1;
		$map['siteid'] = SITEID;
		
		if($recommend){ 
			$map['is_recommend'] = 1;
		}

		if($order ){ 
			$order = $order;
		}else{ 
			$order = 'diff_time asc';
		}

		switch ($event_type) {
			case 'recommend':

				$map['is_recommend'] = 1 ;
				break;
			case 'new':

				$order = 'create_time desc ';
				break;
			case 'hot':

				$order = 'signCount desc ';
				break;
		}

		$content = D('Event')->field('id,uid,type_id,finalcity,tag,lasted_time,title,cover_id,traveldays,price_text,price,description')->where($map)->order($order)->page($page, $limit)->select(); 
		foreach ($content as &$v) {
            $v['user'] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['type'] = $this->getType($v['type_id']);
			if($v['lasted_time'] != 0){
				$v['dislasted_time'] = date("Y-m-d H:i",$v['lasted_time']);
				$v['lasted_time'] = date("Y-m-d ",$v['lasted_time']);
			}else{
				$v['dislasted_time'] ='敬请期待';
				$v['lasted_time'] = '';
			}
			$finalcity = get_citys($v['finalcity']);//目的地
			$v['disfinalcity'] = get_city($finalcity['province']).get_city($finalcity['city']); 
			$v['schedule_arr'] = $this->getSchedule($v['id']);
			$event_content['tagarr'] = explode(',',$v['tag']);
			foreach ($event_content['tagarr'] as $key => $a) {
				$v['tags'][$a]['id'] = $a;
				$v['tags'][$a]['name'] = get_event_tag($a);
			}
        }
        unset($v);

		return $content;
	}

/******************************************************************************/

/****************************************UsercenterController*********************************************/

	/*
	* 个人活动订单
	* $map array 查询条件
	* $data array 返回数据
	*/
	public function getmyEvent($map){ 

		switch ($map['status']) {
			case '':
				$dkey = 'all';
				break;
			case '10':
				$map['status'] = array('in','10,20');
				$dkey = 'unpay';
				break;
			case '11':
				$map['status'] = array('in','11,12');
				$dkey = 'deposit';
				break;
			case '30':
				$map['status'] = array('in','30,31,32,33');
				$dkey = 'pay';
				break;
			default:
				break;
		}
		$map['uid'] = is_login();
		$count=D('event_attend')->where($map)->count();//总数
		$Page  = new \Think\Page($count,10);// 
		$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
		$show  = $Page->show();
		$data['show'] = $show; 
		$event_attend = D('event_attend')->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach ($event_attend as $key => $v) {
			$event = D('event')->where(array('id'=>$v['event_id'],'siteid'=>SITEID))->order('id desc')->find();
			$event_calendar_time = D('event_calendar_time')->where(array('id'=>$v['calendar_id'],'siteid'=>SITEID))->order('id desc')->find();
			$event_attend[$key]['title'] = $event['title'];
			$event_attend[$key]['cover_id'] = $event['cover_id'];
			$event_attend[$key]['price'] = $event_calendar_time['price'];
			$event_attend[$key]['start_time'] = $event_calendar_time['starttime'];
			$event_attend[$key]['over_time'] = $event_calendar_time['overtime'];
			$event_attend[$key]['end_time'] = $event_calendar_time['endtime'];
			$event_attend[$key]['creat_time'] = date("Y-m-d H:i:s",$v['creat_time']);
			$map = "siteid = ".SITEID." and calendar_id = ".$event_calendar_time['id']." and event_id = ".$event['id']." and status = 1";
			$event_attend[$key]['signer'] = D('event_signer')->where($map)->select();
			$event_attend[$key]['travel_number'] = count($event_attend[$key]['signer']);
			
			if($event_attend[$key]['cardid'] != ''){ 
				$event_attend[$key]['cardid'] = cardinfo_num($event_attend[$key]['cardid']);
			}
		}
		$data['event_attend'] = $event_attend;
		
		return $data;
	}

	/* 
	* 个人订单详情页
	* $trade_sn 活动订单号
	* 
	*/
	public function getDetailmyEvent($trade_sn){ 
		//$key = 'event_'.$trade_sn;
		//$detailData = S($key);
		//if($detailData){ 
			//return $detailData;
		//}
		$uid = is_login();
		$event_attend = D('event_attend')->where(array('trade_sn'=>$trade_sn,'uid'=>$uid,'siteid'=>SITEID))->find();
		if($event_attend){ 
			$signer_info = D('event_signer')->where(array('siteid'=>SITEID,'order_id'=>$event_attend['id']))->select();
			$total_num = count($signer_info);
			foreach($signer_info as $key => $val){
				$signerinfo[$key]['user_info'] = json_decode($val['user_info'],true); 
				$signerinfo[$key]['id'] = $val['id'];
				$signerinfo[$key]['insurance_info'] = json_decode($val['insurance_info'],true); 
				$member_id_arr[] = $val['member_id'];
			}
			if($event_attend['cardid']  != ''){ 
				$event_attend_num['cardid'] = explode(',', $event_attend['cardid']);
				$check_num = count($event_attend_num['cardid']); 
				for($i=0;$i<$check_num;$i++){ 
					$card_info = D('pointcard')->where(array('cardid'=>$event_attend_num['cardid'][$i],'siteid'=>SITEID))->find();
					$amount += $card_info['amount'];
					$event_attend['cardinfo'][$i]['cardid'] = $event_attend_num['cardid'][$i];
					$event_attend['cardinfo'][$i]['typename'] = $card_info['typename'];
					$event_attend['cardinfo'][$i]['amount'] = $card_info['amount'];
				}
				$event_attend['card_amount'] = $amount;
			}
			$member_left = D('member_contacts')->where(array('uid'=>is_login(),'siteid'=>SITEID))->select();
			foreach($member_left as $key => $val){			
				if(in_array($val['id'],$member_id_arr)){
					unset($member_left[$key]);
				}
			}
			$event = D('event')->where(array('id'=>$event_attend['event_id'],'siteid'=>SITEID))->find();
			$calendar_info = D('event_calendar_time')->where(array('id'=>$event_attend['calendar_id'],'siteid'=>SITEID,'eventid'=>$event_attend['event_id']))->find();

			$detailData['signerinfo']    = $signerinfo;
			$detailData['totalnum']     = $total_num;
			$detailData['event']         = $event;
			$detailData['calendarinfo'] = $calendar_info;
			$detailData['cardinfo']     = $card_info;
			$detailData['eventattend']  = $event_attend;
			$detailData['memberleft']   = $member_left;
			//$key = 'event_'.$trade_sn;
			//S($key,$detailData,1800);
			return $detailData;
		}else{ 

			return false;
		}

	}

	/* 
	* 个人订单详情修改页
	* $trade_sn 活动订单号
	*/
	public function getDetailmyEventEdit($trade_sn){
		/*
		$key = 'Devent_'.$trade_sn; 
		$detailData = S($key);
		if($detailData){ 
			return $detailData;
		}
		*/
		$uid = is_login();
		$event_attend = D('event_attend')->where(array('trade_sn'=>$trade_sn,'uid'=>$uid,'siteid'=>SITEID))->find();		
		if($event_attend){
			if($event_attend['paytype'] != 2 ){
				if($event_attend['pay_status'] > 0){ 
					$detailData['error']= '您的订单已支付，无法修改';
					return $detailData;
				} 
			}		
			$signer_info = D('event_signer')->where(array('siteid'=>SITEID,'order_id'=>$event_attend['id']))->select();
			$total_num = count($signer_info);
			foreach($signer_info as $key => $val){
				$signerinfo[$key]['user_info'] = json_decode($val['user_info'],true); 
				$signerinfo[$key]['id'] = $val['id'];
				$signerinfo[$key]['insurance_info'] = json_decode($val['insurance_info'],true); 
				$member_id_arr[] = $val['member_id'];
				$signer_num_id[] = $val['id'];
			} 
			$event_attend['signer_num_id'] = implode(',', $signer_num_id);
			$member_left = D('member_contacts')->where(array('uid'=>is_login(),'siteid'=>SITEID))->select();
			foreach($member_left as $key => $val){			
				if(in_array($val['id'],$member_id_arr)){
					unset($member_left[$key]);
				}
			}
			if($event_attend['cardid']  != ''){ 
				$event_attend_num['cardid'] = explode(',', $event_attend['cardid']);
				$check_num = count($event_attend_num['cardid']); 
				for($i=0;$i<$check_num;$i++){ 
					$card_info = D('pointcard')->where(array('cardid'=>$event_attend_num['cardid'][$i],'siteid'=>SITEID))->find();
					$amount += $card_info['amount'];
					$event_attend['cardinfo'][$i]['cardid'] = $event_attend_num['cardid'][$i];
					$event_attend['cardinfo'][$i]['typename'] = $card_info['typename'];
					$event_attend['cardinfo'][$i]['amount'] = $card_info['amount'];
				}
				$event_attend['card_amount'] = $amount;
			}

			$event = D('event')->where(array('id'=>$event_attend['event_id'],'siteid'=>SITEID))->find();
			$calendar_info = D('event_calendar_time')->where(array('id'=>$event_attend['calendar_id'],'siteid'=>SITEID,'eventid'=>$event_attend['event_id']))->find();
			$detailData['signerinfo']    = $signerinfo;
			$detailData['total_num']     = $total_num;
			$detailData['event']         = $event;
			$detailData['calendar_info'] = $calendar_info;
			$detailData['card_info']     = $card_info;
			$detailData['event_attend']  = $event_attend;
			$detailData['member_left']   = $member_left;
			//$key = 'Devent_'.$trade_sn;
			//S($key,$detailData,1800);
			return $detailData;
		}else{
			$detailData['error'] = '订单不存在';
			return $detailData;
		}

	}

	/* 
	* 个人订单详情修改页
	* $dataEdit 活动订单信息
	*/
	public function setDetailmyEventEdit($dataEdit){ 
		$card_membercontacts     =	$dataEdit['card_membercontacts'];
		$card_membercontacts_del =  $dataEdit['card_membercontacts_del'];	 
		$card_membercontacts_t	 =  $dataEdit['card_membercontacts_t'];
		$contact_email	         =  $dataEdit['contact_email'];
		$contact_name	         =  $dataEdit['contact_name'];
		$contact_telephone	     =  $dataEdit['contact_telephone'];
		$event_id	             =  $dataEdit['event_id'];
		$event_membercontacts	 =  $dataEdit['event_membercontacts'];
		$event_membercontacts_del=  $dataEdit['event_membercontacts_del'];
		$event_membercontacts_t	 =  $dataEdit['event_membercontacts_t'];
		$order_id	             =  $dataEdit['order_id'];
		$remarks	             =  $dataEdit['remarks'];
		$event_num               =  $dataEdit['event_num'];
		$card_num                =  $dataEdit['card_num'];
		$calendar_id             =  $dataEdit['calendar_id'];
		$data['remarks'] 	     =  $remarks;
		//进行数据判断
		if((preg_match("/^1[0-9]{10}$/",$contact_telephone)) && ($contact_telephone != '')){ 
			$data['contact_telephone'] = $contact_telephone;
			$data['contact_name'] = $contact_name;
		}else{ 
			return(json_encode(array('status'=>1,'msg'=>'请输入正确的订单联系人手机号码。')));
		}
		$pattern = "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
        if (preg_match($pattern, $contact_email)) {
           $data['contact_email'] = $contact_email; 
           
        }else{ 
        	return(json_encode(array('status'=>1,'msg'=>'请输入正确的订单联系人的邮箱！')));
        } 
		
		$event_num_diff = explode(',',$event_num );
		$card_num_diff = explode(',',$card_num );
		$event_num_diff = count($event_num_diff);
		$card_num_diff = count($card_num_diff);
		
        if($card_num_diff <= $event_num_diff){ 

        	if($event_membercontacts_del != ''){ 
        		$event_member = explode(',',$event_membercontacts_del );
        		foreach ($event_member as $key => $value) {
        			$delMember = A("Usercenter/Config");
        			$delMember->do_detail_member_del($value);
        		}
        	}
        	if($card_membercontacts_del != ''){ 
        		$card_del = explode(',',$card_membercontacts_del );
        		foreach ($card_del as $key => $value) {
        			$delCard = A("Usercenter/Config");
        			$delCard->edit_card_del($value);
        		}
        	}
        	if($event_membercontacts != ''){ 
        		$addMember = A("Usercenter/Config");
        		$addMember->detail_member_add($event_id ,$calendar_id ,$event_membercontacts,$order_id);
        	}
    		$data['cardid'] = $card_num;
    		$cardinfo_arr = D('pointcard')->where(array('cardid'=>array('in',$data['cardid']),'siteid'=>SITEID))->select();
			$cardinfo_money = 0;
			foreach($cardinfo_arr as $val){			
				$cardinfo_money += $val['amount'];
			}
			$payrs = D('event_attend')->field('price,deposit,payprice,trade_sn')->where(array('id'=>$order_id,'event_id'=>$event_id,'siteid'=>SITEID))->find();
        	$datas['totalprice'] =$payrs['price'] * $event_num_diff;
        	$data['leftprice'] = $datas['totalprice'] - $cardinfo_money - $payrs['payprice']; 
        	$data['totalprice'] = $datas['totalprice'] - $cardinfo_money ; 
        	if($data['leftprice'] <=0 ){ 
        		$data['leftprice'] = 0;
        		$data['totalprice'] = $datas['totalprice'] - $payrs['payprice'];
        	}
        	$rs = D('event_attend')->where(array('id'=>$order_id,'event_id'=>$event_id,'siteid'=>SITEID))->save($data);	
        	$this->getEventCalendarTime($event_id,'',$delS=true);
        }else{ 
        	return(json_encode(array('status'=>1,'msg'=>'优惠券使用数量比参加者数多,请减少优惠券使用数量')));
        }

        return(json_encode(array('status'=>2,'msg'=>'修改成功,请返回订单详情页。')));

	}

	/*
	*  活动订单修改时参加者人数增加是  订单状态的修改
	*/
	public function setEventStatusUpdate($event_membercontacts='',$event_id='',$calendar_id=''){
		$user_contacts = explode(',',$event_membercontacts);
		$mem_count = count($user_contacts);
		$map = "status >=1 and eventid = $event_id and id = $calendar_id and siteid = ".SITEID;
		$calendar_info = D('event_calendar_time')->where($map)->find();
		$staus = $calendar_info['status'];
		switch($staus){
			case 1;
				$maxpeople = $calendar_info['maxpeople'];
				$regnumber = $calendar_info['regnumber'];
				if(!empty($maxpeople)){ 
					if($maxpeople >= $regnumber){
						if(($maxpeople - $regnumber - $mem_count) >= 0){
							$seats_left = 'continue';
						}else{
							$seats_left = -($maxpeople - $regnumber - $mem_count);
						}
					}else{
						$seats_left = 'continue';
					}
				}else{
					$seats_left = 'continue';
				}
			break;
			case 2;
				$seats_left = 'continue';
			break;
			case 3;
				$seats_left = 'continue';
			break;
			default:
				
			break;
		}

		return(json_encode(array('seats_left'=>$seats_left))); 
	}

	/*
	* 活动订单报名页
	*
	*/
	public function getSign($event_id,$schedule_id,$ordertype=''){
		//$key = "Event_".SITEID.'-'.$event_id.'-'.$schedule_id.'-'.is_login();
		//$dataSign = S($key);
		//if($dataSign){
			//return $dataSign;
		//}
		if(!$event_id || !$schedule_id){
			return $dataSign['error'] = '参数错误！';
		}else{
			/********************判断订单类型*********************************************/
			$ordertype = return_ordertype($schedule_id);
			/*****************************************************************/
			$event_content = D('Event')->where(array('status' => 1, 'id' => $event_id))->find();
			if (!$event_content) {
				return $dataSign['error'] = '活动不存在！';
			}else{
				$content = D('event_calendar_time')->where(array('id' => $schedule_id))->find();
				if(!$content){
					return $dataSign['error'] = '排期不存在！';
				}else{
					$member = D('member_contacts')->where(array('uid'=>is_login(),'status'=>1,'siteid'=>SITEID))->select();
					if($member){
						foreach ($member as $k => $v) {
							$member_arr[$v['id']] = $v;
						}
					}
					$member_json = json_encode($member_arr);
					$insurance_info = get_insurance($event_content['insurance']);
					if(!empty($insurance_info)){
						$insurance_string = $insurance_info['name'].'('.$insurance_info['sum_insured'].') '.$insurance_info['price'].'元/人';
					}else{
						$insurance_string = '暂无保险';
					}
					$dataSign['insurance_string'] =  $insurance_string ;
				}
			}
		}
		$mem_info_creat_time = D('event_attend')->field('MAX(creat_time) creat_time')->where(array('siteid'=>SITEID,'uid'=>is_login()))->find();
		$mem_info_order = D('event_attend')->field('contact_name,contact_telephone,contact_email')->where(array('siteid'=>SITEID,'uid'=>is_login(),'creat_time'=> $mem_info_creat_time['creat_time']))->find();
		if($mem_info_order['contact_name'] == ''){ 
			$mem_info  = D('ucenter_member')->where(array('siteid'=>SITEID,'id'=>is_login()))->find();
		}else{ 
			$mem_info['real_name'] = $mem_info_order['contact_name']; 
			$mem_info['mobile'] = $mem_info_order['contact_telephone']; 
			$mem_info['email'] = $mem_info_order['contact_email']; 
		}
		$dataSign['mem_info']= $mem_info;
		$dataSign['ordertype']=$ordertype;
        $dataSign['content']= $content;
		$dataSign['event_content']= $event_content;
		$dataSign['member']=$member;
		$dataSign['member_json']=$member_json;
		$dataSign['card_arr']=$card_final;
		//$key = "Event_".SITEID.'-'.$event_id.'-'.$schedule_id.'-'.is_login();
		//S($key,$dataSign,1800);
		return $dataSign;
	}

	/*
	 * 下单操作
     * 报名参加活动
     * $dataSign 订单信息
     */
	public function setConfigDoSign($dataSign){
		$_POST = $dataSign;
		$event_id = $_POST['event_id'];
		$calendar_id = $_POST['calendar_id'];
		$event_membercontacts = $_POST['event_membercontacts'];
		$user_agreement = $_POST['user_agreement'];
		$sign_notice =$_POST['sign_notice'];
		
		if(!$event_id || !$calendar_id){ 
			$dataDoSign['error'] = '参数错误';
			return $dataDoSign;
		}
		if(!$event_membercontacts){ 
			$dataDoSign['error'] = '请选择参加者';
			return $dataDoSign;
		}
		if($sign_notice == 1 ){ 
		 	if($user_agreement != 1){ 
		 		$dataDoSign['error'] = '请点击同意活动协议';
				return $dataDoSign;
		 	}
		}
		
		$event_content = D('Event')->where(array('status' => 1, 'id' => $event_id))->find();
		if (!$event_content){ 
			$dataDoSign['error'] = '活动不存在或已经下线！';
			return $dataDoSign;
		}
		$calendar_info = D('event_calendar_time')->where(array('siteid'=>SITEID,'eventid' => $event_id, 'id' => $calendar_id))->find();
		if(!$calendar_info){ 
			$dataDoSign['error'] = '没有这个时间段的活动！';
			return $dataDoSign;
		}
		
		$user_contacts = explode(',',$event_membercontacts);
		$mycontact_count = count($user_contacts);
		if($mycontact_count==0){ 
			$dataDoSign['error'] = '最少要有一个人参加活动！';
			return $dataDoSign;
		}
		
		/********************判断订单类型*********************************************/
		$ordertype = return_ordertype($calendar_id,$mycontact_count);
		/*****************************************************************/
		
		$contact_name = $_POST['contact_name'];
		$contact_telephone = $_POST['contact_telephone'];
		$contact_email = $_POST['contact_email'];
		$remarks = $_POST['remarks'];
				
		if(!$contact_name){ 
			$dataDoSign['error'] = '请填写订单联系人的姓名！';
			return $dataDoSign;
		}
		if($contact_telephone==''){
            $dataDoSign['error'] = '手机号码不能为空';
			return $dataDoSign;
         }
		if(!get_every_check('mobile',$contact_telephone)){
			$dataDoSign['error'] = '请输入正确的手机号码';
			return $dataDoSign;
        }
		if(!$contact_email){
			$dataDoSign['error'] = '请填写订单联系人的邮箱！';
			return $dataDoSign;
		}else{
			
			if (!get_every_check('email',$contact_email)) {
				$dataDoSign['error'] = '邮箱格式错误，请重新输入！';
				return $dataDoSign;
			}
		}
		/************优惠券***********************/
		$check_card_use = $_POST['check_card_use'];

		//修改 优惠券使用规则
		$card_membercontacts = $_POST['card_membercontacts'];
		$card_contacts = explode(',',$card_membercontacts);
		if($card_contacts[0] == ''){ 
			$card_contacts = null;
		}
		$mycard_count = count($card_contacts);//获取卡券数
		switch($check_card_use){
			case 2;
				if($mycard_count == 0){
					$dataDoSign['error'] = '亲，既然选择了使用优惠券，就不能为空哦！';
				    return $dataDoSign;
				}else{
					
					for($i=0;$i<$mycard_count;$i++){ 
						$card_info = D('Pointcard')->check_card($card_contacts[$i]);
						if(!$card_info['status']){
							$this->error($card_info['msg']);
						}else{
							$card_info[$i] = D('pointcard')->where(array('cardid'=>$card_contacts[$i],'siteid'=>SITEID))->find();
							$card_price_amount[] = $card_info[$i]['amount'];
							$card_name[] = $card_info[$i]['typename'];
							$savedata_card[] = $card_contacts[$i];	
						}		
					}
					$savedata['cardid'] = implode(',', $savedata_card);
					$card_price_num = count($card_price_amount);
					for($i=0;$i<$card_price_num;$i++){ 
						$card_price += $card_price_amount[$i];
					}									
				}
			break;
		
		}		
		/****************************************/
		
		$user_info = D('ucenter_member')->where(array('siteid'=>SITEID,'id'=>is_login()))->find();
		if($user_info['real_name'] == ''){
			$list['real_name'] = $contact_name;
			D('ucenter_member')->where(array('siteid'=>SITEID,'id'=>is_login()))->save($list);
		}

		/****************************************/
		$check = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $event_id, 'calendar_id' => $calendar_id))->select();
		if($check){
			foreach ($check as $key => $c_a_info) {
				if($c_a_info['status'] == -1 || $c_a_info['status'] == 0)  continue;
				$card_arr = D('event_signer')->where(array('siteid'=>SITEID,'order_id'=>$c_a_info['id'],'status'=>1))->field('card')->select();
				if($card_arr){
					foreach ($card_arr as $k => $c_act) {
						if(!$c_act['card'])  continue;
						$c_c_act[] = $c_act['card'];
					}
				}							
				if($c_c_act){
					$m_c_data = D('member_contacts')->where("siteid=".SITEID ." and status=1 and id in ($event_membercontacts)")->select();
					foreach ($m_c_data as $u => $u_info) {	
						if($u_info['card']){
							if (in_array($u_info['card'],$c_c_act)){
								$this->error('<b>'.$u_info['realname'].'_'.$u_info['card'].'</b><br>已经提交过该订单了，您可以取消原订单或选择其他人员');
							}
						}
					}
				}	
			}		
		}
		$totalprice = $calendar_info['price'] * $mycontact_count;
		$card_price = isset($card_price) ? $card_price : 0;
		if($calendar_info['paytype'] == 0 ){
			$diff_price = $totalprice - $card_price;
			$payprice =  $calendar_info['price'] * $mycontact_count;
			if(!empty($calendar_info['price'])){
				if($diff_price < 0){
					$savedata['totalprice'] = $totalprice;
					$savedata['payprice'] = 0;
					$savedata['leftprice'] = 0;
					$card_data['status'] = 3;
				}else{
					$savedata['totalprice'] = $totalprice - $card_price;
					$savedata['payprice'] = $payprice - $card_price;
					$savedata['leftprice'] = 0;
					$card_data['status'] = 2;
				}
			}else{
				$savedata['totalprice'] = 0;
				$savedata['payprice'] = 0;
				$savedata['leftprice'] = 0;
			}
		}else{
			$payprice =  $calendar_info['deposit'] * $mycontact_count;//多人报名时的支付金额
			$leftpay_temp = $totalprice - $payprice;
			if($card_price > $leftpay_temp){
				$savedata['totalprice'] = $payprice;				
			}else{
				$savedata['totalprice'] = $totalprice - $card_price;
			}
			$card_data['status'] = 2;
			$savedata['payprice'] = $payprice;
			$savedata['leftprice'] = $savedata['totalprice'] - $savedata['payprice'];
		}
		
		$event_membercontacts = implode(',',$user_contacts);
		/**查询选择的所有活动参加者**/
		$m_c_data = D('member_contacts')->where("siteid=".SITEID. " and status=1 and id in ($event_membercontacts)")->select();		
		$savedata['trade_sn'] = create_sn();
		$savedata['uid'] = is_login();
		$savedata['event_id'] = $event_id;
		$savedata['calendar_id'] = $calendar_id;
		$savedata['creat_time'] = time();
		$savedata['overdue_time'] = time()+1800;//过期时间设置		
		$savedata['ordertype'] = $ordertype;
		$savedata['siteid'] = $event_content['siteid'];				
		$savedata['price'] = $calendar_info['price'];				
		$savedata['paytype'] = $calendar_info['paytype'];	
		$savedata['contact_name'] = $contact_name;
		$savedata['contact_telephone'] = $contact_telephone;
		$savedata['contact_email'] = $contact_email;
		$savedata['remarks'] = $remarks;
		$savedata['deposit'] = $calendar_info['deposit'];
		if($ordertype ==1){
			switch($calendar_info['paytype']){
				case 0;
					if(!empty($calendar_info['price'])){
						if($diff_price < 0){
							$savedata['status'] = 30;
							$savedata['pay_status'] = 2;
						}else{
							$savedata['status'] = 20;
							$savedata['pay_status'] = 0;
						}
					}else{
						$savedata['status'] = 30;
						$savedata['pay_status'] = 2;
					}
				break;
				case 1;
					$savedata['status'] = 10;
					$savedata['pay_status'] = 0;	
				break;
				case 2;
					$savedata['status'] = 30;
					$savedata['pay_status'] = 2;	
				break;
			}
		}else{
			$savedata['status'] = 1;
			$savedata['pay_status'] = 0;				
		}
		
		$res = D('event_attend')->add($savedata);
		if ($res) {	
			$this->getEventCalendarTime($event_id,'',true);		
			/*********订单提交成功更新优惠券状态*********/
			if($savedata['cardid'] != ''){	

				for($i=0;$i<$mycard_count;$i++){ 
					$card_data['userid'] = is_login();
					D('pointcard')->where(array('cardid'=>$card_contacts[$i],'siteid'=>SITEID))->save($card_data);
					$card_user = D('pointcard_user')->where(array('cardid'=>$card_contacts[$i],'siteid'=>SITEID))->find();
					if(!$card_user){
						$result['siteid'] = SITEID;
						$result['cardid'] = $card_contacts[$i];
						$result['userid'] = is_login();
						$result['bindtime'] = time();
						$result['usetime'] = time();			
						D('pointcard_user')->add($result);
					}else{
						$resu['usetime'] = time();
						D('pointcard_user')->where(array('cardid'=>$card_contacts[$i],'siteid'=>SITEID))->save($resu);					
					}
					
					/**********写入日志表*************************/
					add_card_log($card_contacts[$i],$card_data['status'],'提交活动订单-[使用]','[代金券/活动卡][使用/取消]');
					/********************************************/
  				}
			}
			/*报名人添加到event_signer表*/			
			foreach($m_c_data as $key => $val){
				$event_signer_data[$val['id']]['siteid'] = SITEID;
				$event_signer_data[$val['id']]['order_id'] = $res;
				$event_signer_data[$val['id']]['card'] = $val['card'];
				$event_signer_data[$val['id']]['user_info'] = json_encode($val);
				$event_signer_data[$val['id']]['order_status'] = $savedata['status'];
				$event_signer_data[$val['id']]['status'] = 1;
				$event_signer_data[$val['id']]['member_id'] = $val['id'];
				$event_signer_data[$val['id']]['event_id'] = $event_id;
				$event_signer_data[$val['id']]['calendar_id'] = $calendar_id;
				$event_signer_data[$val['id']]['insurance_id'] = !empty($event_content['insurance'])? $event_content['insurance'] : null ;
				$insurance_arr_info = get_insurance($event_content['insurance']);
				$event_signer_data[$val['id']]['insurance_info'] = !empty($insurance_arr_info) ? json_encode($insurance_arr_info) : null ;
				D('event_signer')->add($event_signer_data[$val['id']]);
			}
				
			D('Message')->sendMessageWithoutCheckSelf($event_content['uid'],query_user('nickname',is_login()).'报名参加了活动]'.$event_content['title'].']，请速去审核！' ,'报名通知', U('Manage/Order/event_detail',array('trade_sn'=>$savedata['trade_sn'])),is_login());

			 D('Common/Dynamic')->sendMessage(is_login(),'Event',$event_content['title'],$event_id,U('Event/Index/detail') );


			D('event_calendar_time')->where(array('eventid' => $event_id, 'id' => $calendar_id))->setInc('regnumber',$mycontact_count);
			if($ordertype ==1){
				/*************************发送邮件***********************************************/
				$event_info = D('event_attend')->where(array('siteid'=>SITEID,'id'=>$res))->find();
				$uid = $event_info['uid'];
				$user_info = query_user(array('nickname',$uid));
				$user_name = $user_info['nickname'];
				$webinfo = json_decode(WEBSITEINFO,true);
				$web_url = "http://".$_SERVER['HTTP_HOST'];
				$title = "[".$webinfo['webname']."]-下单成功";
				$webinfo = json_decode(WEBSITEINFO,true);
				$orderdata= array(
						'user_name'			=>	$user_name,
						'trade_sn' 			=>	$event_info['trade_sn'],
						'event_title'		=>	$event_content['title'],
						'calendar_starttime'=>	$calendar_info['starttime'],
						'total_member'		=>	$mycontact_count,
						'webname'			=>	$webinfo['webname'],
						'web_slogan'		=>	$webinfo['slogan'],
						'web_url'			=>	$web_url,
						'noticetype'   		=>  'order_message',
						'title'				=>  $title,		
					);
				$contactway=array($contact_email);                
				D('Message')->addSendMessage('send_email_to_user',$contactway,$orderdata,0,1);
				/*
				$eventdata=array(
					'event_order_sn'  => $savedata['trade_sn'],
					'execute_time'   => $savedata['creat_time']+1800,
					);
				*/
				//D('Message')->addSendMessage('event_order_countdown_update','',$eventdata,0,1);
				/***********************************************************************************/
				if($diff_price < 0 || $calendar_info['price'] == 0){
					$dataDoSign['success'] = "已下单，即将跳到我的订单详情。";
					$dataDoSign['url'] = 'Usercenter/Eventorder/myevent_detail';
					$dataDoSign['trade_sn'] = $savedata['trade_sn'];
					return $dataDoSign;
				}else{
					$dataDoSign['success'] = "已下单，等待支付。";
					$dataDoSign['url'] = 'Usercenter/Pay/pay';
					$dataDoSign['trade_sn'] = $savedata['trade_sn'];
					return $dataDoSign;
				}
				
			}else{
				$map['id'] = $res;
				$data_update_att['overdue_time'] = 0;
				D('event_attend')->where($map)->save($data_update_att);
				$dataDoSign['success'] = "已候补，等待确认。";
				$dataDoSign['url'] = 'Usercenter/Eventorder/myevent_detail';
				$dataDoSign['trade_sn'] = $savedata['trade_sn'];
				return $dataDoSign;
			}
		} else {
			$dataDoSign['error'] = '报名失败。';
			return $dataDoSign;
		}

	}

/****************************************UsercenterController********************************************/
 

/**************************************Home-index************************************************/
	/*
	* sports-index 的活动日历
	* 
	*/
	public function getTypeEventContent(){ 
		$map['status'] = array(array('EGT',1),array('ELT',3),'and');
		$map['siteid'] = SITEID;
		$map['starttime'] = array('EGT',date('Y-m-d',time()));
		//赛事数据match_category
		$apply_event = D('websit_install_apply')->field('config')->where(array('siteid'=> SITEID,'status' => 1, 'app_model'=> 'Event'))->find();

		$apply_event_old = string2array($apply_event['config']);
		//活动数据 match：赛事  event：官方活动
		$list = D('event_calendar_time')->field('starttime,eventid')->where($map)->select();
		foreach ($list as $ke => $val) {
			$list_type = D('Event')->field('type_id')->where(array('id'=>$val['eventid'],'siteid'=> SITEID,'status'=>1))->find();
			$list[$ke]['Eventtype'] = $list_type['type_id'];
			if($list[$ke]['Eventtype'] == $apply_event_old['match_category']  &&  $list[$ke]['Eventtype'] != ''){ 
				$match_list[] = $val['starttime'];
			}elseif( $list[$ke]['Eventtype'] != ''){ 

				$event_list[] = $val['starttime'];
			}
		}

		$matchord  = array_count_values($match_list);
		$eventord  = array_count_values($event_list); 

		$m = "[";
		foreach ($eventord as $key => $value) {
			$match_list_calendar['event'][] = $key;
			if($m == "["){ 
				$m .= "{id:'".$key."', title : '".$value."' , start : '".$key."' ,colorclass:'event'  }" ;
				
			}else{ 
				$m .= ", { id:'".$key."',title : '".$value."' , start : '".$key."',colorclass:'event' }" ;
			}
			 
		}

		foreach ($matchord as $k => $v) {
			$match_list_calendar['match'][] = $k;
			if($m == "["){ 
				$m .= "{id:'".$k."', title : '".$v."' , start : '".$k."' ,colorclass:'match'  }" ;
				
			}else{ 
				$m .= ", { id:'".$k."',title : '".$v."' , start : '".$k."',colorclass:'match' }" ;
			}
			 
		}
		$m .= "]";


		$match_calendar['Event'] = $match_list_calendar['event'][0];
		$match_calendar['match'] = $match_list_calendar['match'][0];

		if($match_calendar['Event'] == $match_calendar['match']){ 
			$calendar_Con = $this->getTypeEventList($match_calendar['Event']);
			$calendar_list_Con['Event'] = $calendar_Con['event_list'];
			$calendar_list_Con['match'] = $calendar_Con['match_list'];

		}else{ 
			$calendarlistCon['Event'] = $this->getTypeEventList($match_calendar['Event']);
			$calendar_list_Con['Event'] = $calendarlistCon['Event']['event_list'];

			$calendarlistCon['match'] = $this->getTypeEventList($match_calendar['match']);
			$calendar_list_Con['Match'] = $calendarlistCon['match']['match_list'];

		}

		$date['calendarList'] = $m ;
		$date['calendar_list_Con'] = $calendar_list_Con;
		return $date;
	}



	/* 
	* $type 类型
	* $time 时间
	*/
	public function getTypeEventList($time,$type=''){ 
	
		$map['status'] = array(array('EGT',1),array('ELT',3),'and');
		$map['siteid'] = SITEID;
		$map['starttime'] = $time;
		$apply_event = D('websit_install_apply')->field('config')->where(array('siteid'=> SITEID,'status' => 1, 'app_model'=> 'Event'))->find();
		$apply_event_old = string2array($apply_event['config']);
		$list = D('event_calendar_time')->field('starttime,eventid')->where($map)->select();
		foreach ($list as $ke => $val) {
			$list_type = D('Event')->field('id,uid,title,vice_title,cover_id,type_id')->where(array('id'=>$val['eventid'],'siteid'=> SITEID,'status'=>1))->find();
			if($list_type['type_id'] == $apply_event_old['match_category']  &&  $list_type['type_id'] != ''){ 
				$match_list[$ke]['id']         = $list_type['id'];
				$match_list[$ke]['url']		   = U('Event/Index/detail',array('id'=>$list_type['id']));
				$match_list[$ke]['uid']        = query_user('nickname',$list_type ['uid']); 
				$match_list[$ke]['title']      = $list_type['title'];
				$match_list[$ke]['vice_title'] = ($list_type['vice_title'] == null )?'':$list_type['vice_title'];
				$match_list[$ke]['cover_id']   = getThumbImageById($list_type['cover_id'],120,90);
				$match_list[$ke]['time']       = $time;

			}elseif( $list_type['type_id'] != ''){ 
				$event_list[$ke]['id']         = $list_type['id'];
				$event_list[$ke]['url']		   = U('Event/Index/detail',array('id'=>$list_type['id']));
				$event_list[$ke]['uid']        = query_user('nickname',$list_type ['uid']); 
				$event_list[$ke]['title']      = $list_type['title'];
				$event_list[$ke]['vice_title'] = ($list_type['vice_title'] == null )?'':$list_type['vice_title'];
				$event_list[$ke]['cover_id']   = getThumbImageById($list_type['cover_id'],120,90);
				$event_list[$ke]['time']       = $time;
			}
		}

		if(!empty($match_list)){ 
			$eventListCon['match_list'] = $match_list;
		}

		if(!empty($event_list)){ 
			$eventListCon['event_list'] = $event_list;
		}
		
		return $eventListCon;

	}



/**************************************Home-index************************************************/


	/**缓存清除**/
 	public function clean_event_cache(){ 

 		/*Home-index*/
 		S('Home_Event_Default_'.SITEID.'_recommend',null);//default
 		S('Home_Event_Default_'.SITEID.'_new',null);//default

 		S('Home_Event_Runners_'.SITEID.'_new',null);//Runners

 		S('Home_Event_Sports_'.SITEID.'_typeEvent',null);//sports

 		S('Home_Event_Tourism_'.SITEID.'_recommend',null);//tourism 
 		S('Home_Event_Tourism_'.SITEID.'_new',null);//tourism
 	}

} 
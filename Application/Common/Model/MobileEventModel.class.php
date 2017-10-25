<?php 
	namespace Common\Model;
    use Think\Model;

    class MobileEventModel extends Model
    {   

    	/*获取活动列表*/
    	public function mobileEventList($page=0){ 
    		$map = "status = 1 and siteid=".SITEID;
			$type_id = intval($_GET['type_id']);
			$tag = intval($_GET['tag']);
			$starttime = intval($_GET['starttime']);
			$price = intval($_GET['price']);	
			$holiday = intval($_GET['holiday']);
			$finalcity = intval($_GET['finalcity']);
			$days = intval($_GET['days']);
			$recommend = intval($_GET['recommend']);
			if($recommend){
				$map .= " and is_recommend =1";
			}
			$keywords = trim(op_t(I('post.keywords')));
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
				switch($starttime){
					case '13';					
						$map .= " and WEEKDAY(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 5 or WEEKDAY(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 6";
					break;
					case '14';
						$map .= " and DAYOFYEAR(FROM_UNIXTIME(abs(lasted_time - UNIX_TIMESTAMP(NOW())))) <= 30 and DAYOFYEAR(FROM_UNIXTIME(abs(lasted_time - UNIX_TIMESTAMP(NOW())))) > 0"; 
					break;
					default:
						$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = $starttime";
				}
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
					$map .= " and price > 4000 and price <= 5000)";
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

	        $order = " abs(TIMESTAMPDIFF(DAY,FROM_UNIXTIME(lasted_time,'%Y-%m-%d'),now())),diff_time";
			//获取请求的页数
			$start = $page*10; 
	        $content = D('Event')->where($map)->order($order)->field('id,siteid,uid,title,description,create_time,pictures_id,cover_id,view_count,type_id,minpeople,maxpeople,begincity,finalcity,paytype,price,tag,detailadd,price_text,lasted_time,insurance,traveldays')->cache(true,1800)->limit($start, 10)->select();
	        $totalCount = D('Event')->where($map)->cache(true,1800)->count();
			$str = "<d style='color:red'>".$keywords."</d>";
	        foreach ($content as &$v) {
				if($keywords != ''){
					$v['title'] = str_replace($keywords,$str,$v['title']);
				}            
				if($v['lasted_time'] != 0){
					$v['lasted_time'] = date("Y-m-d",$v['lasted_time']);
				}else{
					$v['lasted_time'] ='暂无排期';
				}		
				$event_content['tagarr'] = explode(',',$v['tag']);
				foreach ($event_content['tagarr'] as $key => $a) {
					$v['tags'][$a]['id'] = $a;
					$v['tags'][$a]['name'] = get_event_tag($a);
				}

				$view_status = $this->getCalendarList($v['id']);
				$v['view_status'] = $view_status['view_status'];
				$v['url'] =U('Mobile/Event/detail',array('id'=>$v['id']));
				$v['thumb'] =getThumbImageById($v['cover_id'],400,300);
				
				$begincity = get_citys($v['begincity']);
				$finalcity = get_citys($v['finalcity']);
				
				$v['begincity_province'] = get_city($begincity['province']);
				$v['begincity_city'] = get_city($begincity['city']);
				$v['finalcity_province'] = get_city($finalcity['province']);
				$v['finalcity_city'] = get_city($finalcity['city']);

	        }
	        unset($v);
	        return $content;

    	}

    	/*筛选活动类型*/
    	public function mobileEventType($page=0){ 
    		$type = $_GET['type'];
    		$map = " status = 1 and siteid = ". SITEID ;
    		switch ($type) {
    			case 'nearby':

    					$lng = $_GET['lng'] ;
						$lat = $_GET['lat'];
						$squares =  $this->returnSquarePoint($lng,$lat,$distance = 3 );
    					$map .= " and point_lat <> 0 and  point_lat > ".$squares['right-bottom']['lat']." and  point_lat < ".$squares['left-top']['lat']." and point_lng > ".$squares['left-top']['lng']." and point_lng < ".$squares['right-bottom']['lng'] ;

    					$order = ' ACOS(SIN(('.$lat.' * 3.1415) / 180 ) *SIN((point_lat * 3.1415) / 180 ) +COS(('.$lat.' * 3.1415) / 180 ) * COS((point_lat * 3.1415) / 180 ) *COS(('.$lng.'* 3.1415) / 180 - (point_lng * 3.1415) / 180 ) ) * 6380 asc';
    				break;
    			case 'new':

    				$order = 'create_time , diff_time';
    				break;

    			case 'vacation':
    				
    				$map .= " and ( (MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 1 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 1 and 3 and lasted_time != 0)";
    				$map .= " or (MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 2 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 17 and 24)";
    				$map .= " or (MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 4 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 4 and 6)";
    				$map .= " or (MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 5 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 1 and 3)";
    				$map .= " or (MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 6 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 20 and 22)";
					$map .= " or (MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 7 and 8)";
					$map .= " or (MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 9 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 27 and 29)";
					$map .= " or (MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 10 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 1 and 7)";
					$map .= " or (MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 12 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 25 and 27)";

					$map .= " or (MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 1 and 2) )";
					$order = 'diff_time';
    				break;
    		}

			//获取请求的页数
			$start = $page*10; 
	        $content = D('Event')->where($map)->order($order)->field('id,siteid,uid,title,description,create_time,pictures_id,cover_id,view_count,type_id,minpeople,maxpeople,begincity,finalcity,paytype,price,tag,detailadd,price_text,lasted_time,insurance,traveldays')->cache(true,1800)->limit($start, 10)->select();
	        $totalCount = D('Event')->where($map)->cache(true,1800)->count();

			$str = "<d style='color:red'>".$keywords."</d>";
	        foreach ($content as &$v) {
				if($keywords != ''){
					$v['title'] = str_replace($keywords,$str,$v['title']);
				}            
				if($v['lasted_time'] != 0){
					$v['lasted_time'] = date("Y-m-d",$v['lasted_time']);
				}else{
					$v['lasted_time'] ='暂无排期';
				}		
				$event_content['tagarr'] = explode(',',$v['tag']);
				foreach ($event_content['tagarr'] as $key => $a) {
					$v['tags'][$a]['id'] = $a;
					$v['tags'][$a]['name'] = get_event_tag($a);
				}

				$view_status = $this->getCalendarList($v['id']);
				$v['view_status'] = $view_status['view_status'];
				$v['url'] =U('Mobile/Event/detail',array('id'=>$v['id']));
				$v['thumb'] =getThumbImageById($v['cover_id'],400,300);
				
				$begincity = get_citys($v['begincity']);
				$finalcity = get_citys($v['finalcity']);
				
				$v['begincity_province'] = get_city($begincity['province']);
				$v['begincity_city'] = get_city($begincity['city']);
				$v['finalcity_province'] = get_city($finalcity['province']);
				$v['finalcity_city'] = get_city($finalcity['city']);
	        }
	        unset($v);
	        return $content;
    	}

    	/*获取活动详情*/
    	public function mobileEventDetail($id){

    	   $keys = "mobileEventDetail".SITEID."_".$id;
		   D('Event')->where(array('id' => $id))->setInc('view_count');
    	   $dataLsit_S = S($keys);
    	   if($dataLsit_S){ 
    	   		return $dataLsit_S;
    	   }

		   $event_content = D('Event')->where(array('status' => 1, 'id' => $id,'siteid'=>SITEID))->find();
		   if (!$event_content) {
		   		$dataLsit['error'] = 404;
		   		return $dataLsit;
	        }

	        $star_con = D('event_attribute')->where(array('event_id'=>$id,'siteid'=>SITEID))->select();
			$event_content['star_con'] = $star_con;
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
			$event_calendar = D('Common/Event')->getEventCalendarTime($id);
			foreach ($event_calendar as $key => $av){
				$leaders_arr = explode(',',$av['leader']);
				foreach ($leaders_arr as $k => &$le){
					$_leaders[] = $le;
				}    
	        }
			foreach ($event_calendar as $key => &$v){
				if($v['status'] == 1){						
					if(strtotime("$v[endtime]")-time() > 0){
						if($v['maxpeople'] != 0){
							if(($v['maxpeople']-$v['regnumber']) < 0){
								$detail_schedule[$v['id']]= date('y-m-d',strtotime($v['starttime'])).'~'.date('y-m-d',strtotime($v['overtime'])).' ￥'.$v['price'];
							}else{
								$detail_schedule[$v['id']]= date('y-m-d',strtotime($v['starttime'])).'~'.date('y-m-d',strtotime($v['overtime'])).' ￥'.$v['price'];
							}
						}else{
								$detail_schedule[$v['id']]= date('y-m-d',strtotime($v['starttime'])).'~'.date('y-m-d',strtotime($v['overtime'])).' ￥'.$v['price'];
								
						}
					}else{
						continue;
					}
				}elseif($v['status'] == 2){
					$detail_schedule[$v['id']]= date('y-m-d',strtotime($v['starttime'])).'~'.date('y-m-d',strtotime($v['overtime'])).' ￥'.$v['price'];
				}elseif($v['status'] == 3){
					$detail_schedule[$v['id']]= date('y-m-d',strtotime($v['starttime'])).'~'.date('y-m-d',strtotime($v['overtime'])).' ￥'.$v['price'];
				}elseif($v['status'] == 4){
					continue;
				}elseif($v['status'] == 5){
					continue;
				}elseif($v['status'] == 6){
					continue;
				}
	        }
			foreach ($event_calendar as $key => &$v){
				if($v['status'] == 1){						
					if(strtotime("$v[endtime]")-time() > 0){
						$event_signlist[$v['id']]['team_name'] = ($v['team_name'] != null)?$v['team_name']:'';
						if($v['maxpeople'] != 0){
							if(($v['maxpeople']-$v['regnumber']) < 0){
								
								$event_signlist[$v['id']]['id'] = $v['id'];
								$event_signlist[$v['id']]['starttime'] = date('Ymd',strtotime($v['starttime']));
								$event_signlist[$v['id']]['overtime'] = date('Ymd',strtotime($v['overtime']));
								$event_signlist[$v['id']]['price'] = $v['price'];
								$event_signlist[$v['id']]['sign_text'] = '预约';
								$event_signlist[$v['id']]['sign_type'] = 2;
							}else{
								$event_signlist[$v['id']]['id'] = $v['id'];
								$event_signlist[$v['id']]['starttime'] = date('Ymd',strtotime($v['starttime']));
								$event_signlist[$v['id']]['overtime'] = date('Ymd',strtotime($v['overtime']));
								$event_signlist[$v['id']]['price'] = $v['price'];
								$event_signlist[$v['id']]['sign_text'] = '报名';
								$event_signlist[$v['id']]['sign_type'] = 1;
							}
						}else{
								$event_signlist[$v['id']]['id'] = $v['id'];
								$event_signlist[$v['id']]['starttime'] = date('Ymd',strtotime($v['starttime']));
								$event_signlist[$v['id']]['overtime'] = date('Ymd',strtotime($v['overtime']));
								$event_signlist[$v['id']]['price'] = $v['price'];
								$event_signlist[$v['id']]['sign_text'] = '报名';
								$event_signlist[$v['id']]['sign_type'] = 1;
								
						}
					}else{
						continue;
					}
				}elseif($v['status'] == 2){
					$event_signlist[$v['id']]['team_name'] = ($v['team_name'] != null)?$v['team_name']:'无';
					$event_signlist[$v['id']]['id'] = $v['id'];
					$event_signlist[$v['id']]['starttime'] = date('Ymd',strtotime($v['starttime']));
					$event_signlist[$v['id']]['overtime'] = date('Ymd',strtotime($v['overtime']));
					$event_signlist[$v['id']]['price'] = $v['price'];
					$event_signlist[$v['id']]['sign_text'] = '报名';
					$event_signlist[$v['id']]['sign_type'] = 1;
				}elseif($v['status'] == 3){
					$event_signlist[$v['id']]['team_name'] = ($v['team_name'] != null)?$v['team_name']:'无';
					$event_signlist[$v['id']]['id'] = $v['id'];
					$event_signlist[$v['id']]['starttime'] = date('Ymd',strtotime($v['starttime']));
					$event_signlist[$v['id']]['overtime'] = date('Ymd',strtotime($v['overtime']));
					$event_signlist[$v['id']]['price'] = $v['price'];
					$event_signlist[$v['id']]['sign_text'] = '预约';
					$event_signlist[$v['id']]['sign_type'] = 2;
				}elseif($v['status'] == 4){
					continue;
				}elseif($v['status'] == 5){
					continue;
				}elseif($v['status'] == 6){
					continue;
				}
	        }
			
			$dataLsit['event_signlist'] = $event_signlist;
		
			$leaders = array_unique($_leaders);
			unset($_leaders);
			$leaders_string='';
			foreach ($leaders as $ku=> &$u) {
				$member = D('member')->where(array('uid' => $u))->find();
				if(!$member) continue;
				$leaders_string .='<a target="_blank" href="'.U('Mobile/People/peopledetail',array('uid'=>$member['uid'])).'">'.$member['nickname'].'</a> ';
			}	
		    if ($event_content['pictures_id']) {
	            $pictures = M("Picture")->field('id,path')->where("id in ({$event_content['pictures_id']})")->select();
	            foreach ($pictures as &$img) {
	                $img['path'] = fixAttachUrl($img['path']);
	            }
	            unset($img);
	            $dataLsit['pictures'] = $pictures;
	        }	
			$tpid = D('local_comment')->where(array('status'=>1,'app'=>'Event','mod'=>'event','row_id'=>$event_content['id']))->count();
			$event_content['travel_point'] = ludou_remove_width_height_attribute($event_content['travel_point']);
			$event_content['explain'] = ludou_remove_width_height_attribute($event_content['explain']);
			$event_content['pay_info'] = ludou_remove_width_height_attribute($event_content['pay_info']);
			$event_content['attention'] = ludou_remove_width_height_attribute($event_content['attention']);

			$event_content['ForumBookmark']    = D('forum_bookmark')->where("siteid=".SITEID."  and content_id=".$event_content['id'])->count();
			if(is_login()){
				$mark=D('forum_bookmark')->where(array('siteid'=>SITEID,'content_id'=>$id,'uid'=>is_login()))->find();
				if($mark){
					$is_collection = 1;
				}else{
					$is_collection = 0;
				}
			}else{
				$is_collection = 0;
			}

			$begincity = get_citys($event_content['begincity']);
			$finalcity = get_citys($event_content['finalcity']); 

			$event_content['finalcity_province'] =  get_city($finalcity['province']);
			$event_content['finalcity_city'] =  get_city($finalcity['city']);
			
			$event_content['begincity_province'] =  get_city($begincity['province']);
			$event_content['begincity_city']= get_city($begincity['city']);

			$signPackage=D('WeiShare')->getSignPackage();
			$dataLsit['signPackage']     = $signPackage;
			$dataLsit['is_collection']   = $is_collection;
			$dataLsit['tpid']            = $tpid;
			$dataLsit['detail_schedule'] = $detail_schedule;
			$dataLsit['leaders']         = $leaders_string;
			$dataLsit['tags'] 			 = $tags;
			$dataLsit['in_event'] 	 	 = $event_calendar['in_event'];
			$dataLsit['content'] 		 = $event_content;
			
			S($keys,$dataLsit,1800); 

		    return $dataLsit;

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
		* 活动排期数据处理
		* $id 活动id
		* $calendarid  排期id
	    */
	    public function getCalendarList($id,$calendarid = ''){
	    	
	    	$dataLsit = D('Common/Event')->getSchedule($id,$calendarid= '');
	    	
	    	foreach ($dataLsit as $key => &$value) {
	    		if($value['status'] == 1 || $value['status'] == 2){ 
	    			$dataLsit_s['d'][] = '报名中';
	    		}elseif($value['status'] == 3){ 
	    			$dataLsit_s['v'][] = '报满中';
	    		}
	    	}
	    	if(isset($dataLsit_s['d'])){ 
	    		$dataLsit_s = 1;
	    	}elseif(isset($dataLsit_s['v'])){ 
	    		$dataLsit_s = 3;
	    	}else{ 
	    		$dataLsit_s = 0;
	    	}
	    	$dataLsit['view_status'] = $dataLsit_s;
	    	return $dataLsit;

	    }

	    /*
		* 活动日历
	    */
	   	public function  getCalendarDate(){ 
	   		$match['calendarlist']['one']['dismonth'] = date('m');
	   		$match['calendarlist']['one']['month'] = date('n');
	   		$match['calendarlist']['one']['daynum'] = date('t');
	   		$match['calendarlist']['one']['year'] = date('Y');

	   		$lastmonth = mktime(0, 0, 0, date("m")+1, date("d"),   date("Y"));
	   		$lastmonth2 = mktime(0, 0, 0, date("m")+2, date("d"),   date("Y"));
			$match['calendarlist']['two']['dismonth'] = date('m',$lastmonth );
			$match['calendarlist']['two']['month'] = date('n',$lastmonth );
			$match['calendarlist']['two']['daynum'] = date('t',$lastmonth );
			$match['calendarlist']['two']['year'] = date('Y',$lastmonth );
			$match['calendarlist']['three']['dismonth'] = date('m',$lastmonth2 );
			$match['calendarlist']['three']['month'] = date('n',$lastmonth2 );
			$match['calendarlist']['three']['daynum'] = date('t',$lastmonth2 );
			$match['calendarlist']['three']['year'] = date('Y',$lastmonth2 );
			$weekarray=array("日","一","二","三","四","五","六");
			foreach ($match['calendarlist'] as $key => &$value) {
				
				for($i=1;$i<=$value['daynum'];$i++){
					if($i < 10){ 
						$i = '0'.$i;
					}
					$time = $value['year'].'-'.$value['dismonth'].'-'.$i;
					$data =  $this->getCalendarInfo('month',$time);
					
					if($data){ 
						foreach ($data as $ke => &$val) {
							$data[$ke]['dis_lasted_time']  = $val['starttime'];
							$data[$ke]['dis_lasted_time_week']  = "周".$weekarray[date("w",mktime(0, 0, 0, $value['dismonth'], $i ,$value['year']))];
							$CalendarList = $this->getCalendarList($val['eventid'],$val['id']);

							$data[$ke]['calendar']['view_status'] = $CalendarList['view_status'];
							$data[$ke]['calendar']['num'] = $val['days'];
		
							$data[$ke]['calendar']['disstart'] = date('n',strtotime($val['starttime'])).'月' .date('d',strtotime($val['starttime'])).'日';
							$data[$ke]['calendar']['disend'] = date('n',strtotime($val['overtime'])).'月' .date('d',strtotime($val['overtime'])).'日';
							
						}
						$match['calendarlist'][$key][$value['month']][$i]['days'] = $i;
						$match['calendarlist'][$key][$value['month']][$i]['week'] = $data[$ke]['dis_lasted_time_week'];
						$match['calendarlist'][$key][$value['month']][$i]['content'] = $data;
					
					}
				}

				if($match['calendarlist'][$key][$value['month']] == ''){ 
					unset($match['calendarlist'][$key]);
				}

			}
	   		return $match['calendarlist'];

	    }


	    /*
		* 获取给定条件排期信息
		* $type 给定筛选条件的类型
		* $date 条件
	    */
		private function getCalendarInfo($type,$data){ 
		
			switch ($type) {
				case 'month':
				    $map = " siteid = ".SITEID ;
				    $map .= " and display = 1";
				    $map .= " and  starttime = '".$data."' ";
					break;
				
				default:
					
					break;
			}
			
			 $content = D("event_calendar_time")->field('id,eventid,starttime,overtime,days,team_name')->where($map)->cache(true,1800)->select();
			 foreach ($content as $key => &$value) {
			 	 $calendarlist  = D("Common/Event")->getCalendarStatus($value['id']);
			 	 if($calendarlist['status'] > 3 ){ 
			 	 	unset($content[$key]);
			 	 }else{ 
			 	 	$content[$key]['status'] =  $calendarlist ;
			 	 	$eventstatus = $this->getEventInfo('calendar', $value['eventid']);
			 	 	if($eventstatus['title'] == '' ){ 
						unset($content[$key]);
					}else{ 
						$content[$key]['event'] = $eventstatus;
					}
			 	 }
	
			 }

			return  $content;

		}

		 /*
		* 获取给定条件活动信息
		* $type 给定筛选条件的类型
		* $date 条件
	    */
		private function getEventInfo($type,$id){ 
			
			switch ($type) {
				case 'calendar':
					$content = D('Common/Event')->getEventDetail($id);
				   	$contentEvent['title'] = $content['content']['title'];
				   	$contentEvent['cover_id'] = $content['content']['cover_id'];
				   	$contentEvent['view_count'] = $content['content']['view_count'];
				   
					break;
				
				default:
					
					break;
			}
		
			return  $contentEvent;

		}

		/**
		*计算某个经纬度的周围某段距离的正方形的四个点
		*
		*@param lng float 经度
		*@param lat float 纬度
		*@param distance float 该点所在圆的半径，该圆与此正方形内切，默认值为0.5千米
		*@return array 正方形的四个点的经纬度坐标
		*/ 
		private function returnSquarePoint($lng, $lat,$distance = 0.5){
		define(EARTH_RADIUS, 6371);//地球半径，平均半径为6371km
		$dlng = 2 * asin(sin($distance / (2 * EARTH_RADIUS)) / cos(deg2rad($lat)));
		$dlng = rad2deg($dlng);
		$dlat = $distance/EARTH_RADIUS;
		$dlat = rad2deg($dlat);
		return array(
			'left-top'=>array('lat'=>$lat + $dlat,'lng'=>$lng-$dlng),
			'right-top'=>array('lat'=>$lat + $dlat, 'lng'=>$lng + $dlng),
			'left-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng - $dlng),
			'right-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng + $dlng)
		);
		}

		




    }



?>
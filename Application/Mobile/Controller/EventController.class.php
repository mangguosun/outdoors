<?php

namespace Mobile\Controller;

use Think\Controller;

class EventController extends Controller
{
    /**
     * 业务逻辑都放在 WeiboApi 中
     * @var
     */
    public function _initialize()
    {
		$model_info = get_appinfo('Event');
		if(!$model_info){
			$this->error('应用未开启');
		}
        $tree = D('EventType')->where(array('status' => 1 ,'siteid'=>SITEID))->select();	
		$url = $_SERVER['QUERY_STRING'];
		$url_arr = explode('/',$url);
		$dest_url = $url_arr[2];
		$dest_url = $dest_url == ''?'index':$url_arr[2];
		$this->assign('dest_url',$dest_url);
 		$this->assign('model_info', $model_info);
        $this->assign('tree', $tree);

    }

    /**
     * 活动首页
     * @param int    $page
     * @param int    $type_id
     * @param string $norh
     * autor:xjw129xjt
     */
    public function index($page = 1, $type_id = 0, $norh = 'new')
    {   
		$get_url = json_encode($_GET);
        $type_id = $_GET['type_id'];
        $this->assign('type_id',$type_id);
		$this->assign('get_url', $get_url);
		$this->setTitle('活动首页');
        $this->setKeywords('活动');
        $this->display();
    }
	
    public function calendar()
    {   
	   $dataList = D("Common/MobileEvent")->getCalendarDate();
        $this->assign('calendarlist',$dataList);
        $this->setKeywords('活动');
        $this->display();
    }	
	
    
    public function typeEvent($type){ 

        $get_url = json_encode($_GET);
        $this->assign('get_url', $get_url);
        $this->assign('type',$type);
        $this->display();
	}
    
    public function get_event_type($page = 0,$lng = '',$lat = ''){
        
    
        $content = D('Common/MobileEvent')->mobileEventType($page,$lng,$lat);
        
        exit(json_encode($content));
    }

    public function get_point(){ 
       
        $map['uid'] = is_login();
        $map['updatetime'] = array('EGT',(time()-1800));
        $point =  D('member_footprint')->where($map)->order(' updatetime desc')->select();
        exit(json_encode($point[0]));  
    }


    public function set_point($lng,$lat){ 

        if(is_login()){ 
            $date['uid'] = is_login();
            $date['point_lng'] = $lng;
            $date['point_lat'] = $lat;
            $date['updatetime'] = time();
            D('member_footprint')->add($date);
            return true;
        }else{ 
            return false;
        }
        
    }


    public function get_event_index($page = 0)
    {   
        $content = D('Common/MobileEvent')->mobileEventList($page);

		exit(json_encode($content));
    }

	public function edit_daily($eid=0){
		$rs = D('Event')->where(array('id'=>$eid,'uid'=>is_login()))->find();
		if(!$rs){
			$this->error('活动不存在或已被删除！');
		}else{
			if(is_login() != $rs['uid']){
				$this->error('您的权限不足!');
			}
		}
		$travel = json_decode($rs['travel_table'],true);
		$this->assign('list',$travel);
		$this->assign('contents',$rs);
		$this->display();
	}
   

    /**
     * 活动详情
     * @param int $id
     * autor:xjw129xjt
     */

    /*加入收藏页面*/
    public function dodetail(){
      $map['uid'] = is_login();
      if(!empty($map['uid'])){
          $id=$_POST['id'];
          $data['uid']=$map['uid'];
          $data['content_id']=$id;
          $data['siteid']=SITEID;
          $res=D('ForumBookmark')->where("siteid=".SITEID." and uid={$map['uid']} and content_id={$id}")->find();
          if($res){
             $eventdate['status'] = 1 ;
            exit(json_encode( $eventdate));
         }else{
             $data['create_time']=date('Y-m-d H:i:s',time());
             $e = D('ForumBookmark')->data($data)->add();
             if($e){
               S("mobileEventDetail".SITEID."_".$id,null);
               $eventdate['ForumBookmark']  = D('ForumBookmark')->where("siteid=".SITEID."  and content_id={$id}")->count();
               $eventdate['status'] = 0 ;
               exit(json_encode( $eventdate));
             }
         }
        }
    }
    public function detail($id = 0)
    {
			            
        $dataList = D('Common/MobileEvent')->mobileEventDetail($id);
        if($dataList['error'] == 404){ 
            $this->error('404 not found');
        }
      
       if(!empty($dataList['event_signlist'])){ 
            foreach ($dataList['event_signlist'] as $key => $value) {
                $starttime[] =   date('m-d',strtotime($value['starttime']));
            }

            $timelist['num'] = count( $starttime);
            $timelist['time'] = implode(' , ', $starttime);
            $this->assign('timelist', $timelist);
       }
        
        $this->assign('pictures',$dataList['pictures']);
        $this->assign('event_signlist', $dataList['event_signlist']);  
        $this->assign('signPackage',$dataList['signPackage']);
		$this->assign('is_collection',$dataList['is_collection']);
		$this->assign('tpid',$dataList['tpid']);
		$this->assign('detail_schedule', $dataList['detail_schedule']);
		$this->assign('leaders', $dataList['leaders']);
		$this->assign('tags', $dataList['tags']);
		$this->assign('in_event', $dataList['in_event']);
        $this->assign('content', $dataList['content']);
        $this->setTitle('{$content.title|op_t}' . '——活动');
        $this->setKeywords('{$content.title|op_t}' . ',活动');
        $this->display();
    }


}
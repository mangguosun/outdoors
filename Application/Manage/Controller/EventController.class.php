<?php
namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;
/*
*活动设置
**/
class EventController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize(); 
		$tree = D('EventType')->where(array('status' => 1 ,'siteid'=>SITEID))->select();		
        $this->assign('tree', $tree);
	}


	public function index(){
		//读取列表
		$event_type = I('event_type');
		$event_id = I('event_id');
		$event_title = I('event_title');
	    
		$map = array('status' => array('egt',0),'siteid'=>SITEID);
		if($event_type){ 
			$map['type_id'] = $event_type;
		}
		if($event_id){ 
			$map['id'] = $event_id;
		}
		if($event_title){
			$event_title	=	urlsafe_b64decode($event_title);
			$map['title'] = array('like', '%' . (string)$event_title . '%');
		}
		
		$eventall = D('event')->where($map)
							->field(array('id','is_recommend','title','status','sort'))
							->order(array('is_recommend'=>'desc','sort'=>'desc','id'=>'desc','diff_time'=>'desc'))
							->select();

		foreach ($eventall as $key => $value) {
			$eventall[$key]['disstatus'] = $value['status']?'启用':'禁用';
			$eventall[$key]['disrecommend'] = $value['is_recommend']?'是':'否';


			$map = "siteid = ".SITEID." and event_id = ".$value['id']." and order_status >= 10 and status = 1";
            $tnum = D('event_signer')->where($map)->count();
            $eventall[$key]['signer_tnum'] = $tnum;

        	$where = "siteid = ".SITEID." and status >= 1 and eventid = ".$value['id']; 
         	$rs = D('event_calendar_time')->where($where)->find();
         	$eventall[$key]['discalendar_time'] = $rs?'查看排期':'暂无排期';

		}
		
		$this->assign('datainfo',$eventall);
		$this->display();

    }

    public function seekUrl(){ 
    	$data = $_GET;
    	unset($_GET['url']);
    	if($_GET['ord_type'] == 3){ 
    		$_GET['order_type_con'] = urlsafe_b64encode($_GET['order_type_con']);
    	}
    	if($_GET['event_title'] != ''){
    		$_GET['event_title'] = urlsafe_b64encode($_GET['event_title']);
    	}
    	if($_GET['ord_event'] == 3){ 
    		$_GET['ord_event_con'] = urlsafe_b64encode($_GET['ord_event_con']);
    	}


    	$url=U($data['url'],$_GET);
		header("Location:$url");

    }
	/*
	*活动推荐*
	*/
	public function doRecommend($id='',$tip=0){

	    if(is_array($id)){
			$ids = $id;
			if(empty($ids)){
				$this->error('请选择要操作的数据!');
			}
			$ids = implode(',',$ids);
			$event_info = D('event')->where("id in($ids)")->field('id,status,is_recommend')->select();
			$event_num	= 0;
		    foreach($event_info as $val){
				if($val['is_recommend']!=$tip){
					if($val['status'] == 1){
						$event_num++;
						$reds = D('event')->where(array('id'=>$val['id']))->setField('is_recommend', $tip);
					}
					
				}
				
			}

			if($reds){ 
				D('Common/Event')->clean_event_cache();
			}
			$reds ? $this->success('设置成功'.$event_num.'条', $_SERVER['HTTP_REFERER']):$this->error('设置失败!');
        
		}else{
			if($id=='') $this->error('请选择要操作的数据!');
			$status = D('event')->where(array('siteid'=>SITEID,'id'=>$id))->getField('status');
			if($status==0){
				$this->error('该活动已被禁用，无法推荐！');
			}else{
				$reds = D('event')->where("id=".$id)->setField('is_recommend', $tip);
				$reds ? $this->success('设置成功', $_SERVER['HTTP_REFERER']):$this->error('设置失败!');
			}
			
		}
	  
	}

	
	/*
	 * 活动添加
	 */
	public function add(){
		$id = $_GET['event_id'];
		if($id){
			$event_content = D('Event')->where(array('id' =>$id,'siteid'=>SITEID))->find();
			
			if(!checked_admin(is_login()) && !is_administrator(is_login())){		
				if (!$event_content) {
					$this->error('404 not found');
				}
				 if ($event_content['uid'] != is_login()) {
					$this->error('404 not found');
				}
			}else{
				if (!$event_content) {
					$this->error('404 not found');
				}
			}
			if ($event_content['pictures_id']) {
				$pictures = M("Picture")->field('id,path')->where("id in ({$event_content['pictures_id']})")->select();
				foreach ($pictures as &$img) {
					$img['path'] = fixAttachUrl($img['path']);
				}
				unset($img);
				$this->assign('pictures', $pictures);
			}

			$this->assign('content',$event_content);
		}
		$order = "update_time desc";
		$add = D('event')->where(array('uid'=>is_login(),'siteid'=>SITEID))->order($order)->field(array('detailadd','begincity','attention'))->find();
	
		if($add != ''){
			$this->assign('add',$add);
		}
		$this->assign('url',$url);
		$this->setTitle('添加活动'.'——活动');
		$this->setKeywords('添加'.',活动');
		$this->display();
	}
	
	/**
     * 编辑活动
     * @param $id
     * autor:xjw129xjt
     */
    public function edit($id)
    {
	    $event_content = D('Event')->where(array('id' =>$id,'siteid'=>SITEID))->find();
		if(!checked_admin(is_login()) && !is_administrator(is_login())){		
			if (!$event_content) {
				$this->error('404 not found');
			}
			 if ($event_content['uid'] != is_login()) {
                $this->error('404 not found');
            }
		}else{
			if (!$event_content) {
				$this->error('404 not found');
			}
		}

		$insurance_id = $event_content['insurance'];
		$websit_insurance_info = get_insurance();
		if(!empty($websit_insurance_info)){
			if(!empty($insurance_id)){
				$insurance_string = get_insurance_select($insurance_id);
			}else{
				$insurance_string = get_insurance_select();
			}					
		}else{
			$insurance_string = '';
		}
	
		$event_content['point'] = $event_content['point_lng'].','.$event_content['point_lat'];


        $event_content['user'] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $event_content['uid']);
        $map['event_id'] =  $event_content['id'];
        $map['siteid'] = SITEID;
        $star_content = D('event_attribute')->where($map)->select();
        $this->assign('star_content',$star_content);
        $this->assign('content', $event_content);
		$this->assign('insurance_string',$insurance_string);
        $this->setTitle('编辑活动'.'——活动');
        $this->setKeywords('编辑'.',活动');
        $this->display();
    }
	
	
    /*得到当前活动的所有排期*/
	public function event_schedule($id = 0){
		$uid = is_login();
		$rs = D('Event')->where(array('id' => $id,'siteid'=>SITEID))->find();
		if($rs){
			$content_arr = D('event_calendar_time')->where(array('eventid' => $rs['id'],'siteid'=>SITEID))->order('starttime desc')->select();
			$count = count($content_arr);
			$Page = new \Think\Page($count,10);
			$show = $Page->show();// 分页显示输出
			$content_arr = D('event_calendar_time')
							->where(array('eventid' => $rs['id'],'siteid'=>SITEID))
							->limit($Page->firstRow.','.$Page->listRows)
							->order('starttime desc')->select();
			$maxpeople = $rs['maxpeople'];
			foreach ($content_arr as $key=> &$v) {
				$v['id']= $v['id'];
				$v['ticket']= $maxpeople - $v['regnumber'];
				$v['starttime']= $v['starttime'];
				$v['endtime']= $v['endtime'];
				
				if($v['leader']){
					$leader_arr = explode(',',$v['leader']);
					$leaders ='';
					foreach ($leader_arr as $ku=> &$u) {
						$member = D('member')->where(array('uid' => $u))->find();
						if(!$member) continue;
						$leaders .='<a target="_blank" href="'.U('Usercenter/Index/index',array('uid'=>$member['uid'])).'">'.$member['nickname'].'</a> ';
					}	
					$v['leader'] =$leaders;
				}
				if($v['status'] <= 1){
						if($v['status'] == 1){
							if(strtotime("$v[endtime]")-time() > 0){
								if($v['maxpeople'] != 0){
									if(($v['maxpeople']-$v['regnumber']) < 0){
										$v['info'] = '<span style="color:green"><span style="color:red">报满</span>候补</span>';
									}else{
										$v['info'] = '<span style="color:green">接受报名</span>';
									}
								}else{
									$v['info'] = '<span style="color:green">接受报名</span>';
								}
							}else{
								if(strtotime("$v[starttime]")-time() > 0 ){
									$v['info'] = '<span style="color:red">报名截止</span>';
								}else{
									if(strtotime("$v[overtime]")-time() >= 0  && strtotime("$v[starttime]")-time() <= 0){
										$v['info'] = '<span style="color:red">进行中</span>';
									}elseif(strtotime("$v[overtime]")-time() < 0){
										$v['info'] = '<span style="color:red">已结束</span>';
									}
								}
							}
						}elseif($v['status'] == -1){
							$v['info'] = '<span style="color:red">已删除</span>';
						}
				}elseif($v['status'] == 2){
					$v['info'] = '<span style="color:green">接受报名</span>';
				}elseif($v['status'] == 3){
					$v['info'] = '<span style="color:green"><span style="color:red">报满</span>候补</span>';
				}elseif($v['status'] == 4){
					$v['info'] = '<span style="color:red">报名截止</span>';
				}elseif($v['status'] == 5){
					$v['info'] = '<span style="color:red">进行中</span>';
				}elseif($v['status'] == 6){
					$v['info'] = '<span style="color:red">已结束</span>';
				}
				
				if($v['vehicle']){
					$vehicle_arr = explode(',',$v['vehicle']);
					$vehicles='';
					foreach ($vehicle_arr as &$ve) {
						$vehicles .= get_vehicle($ve).' ';
					}
					$v['vehicle'] =$vehicles;
				}

				if($v['accommodation']){
					$accommodation_arr = explode(',',$v['accommodation']);
					$accommodations='';
					foreach ($accommodation_arr as &$ac) {
						$accommodations .= get_accommodation($ac).' ';
					}
					$v['accommodation'] =$accommodations;
				}

				
				$content_arr[$key]['dis_display'] = ($v['display']==1)?'显示':'隐藏';

				$content_arr[$key]['disPre_num'] = get_status_num($v['id'],'1');//预约报名人数
				$content_arr[$key]['disEffective_num'] = get_status_num($v['id']);//有效报名人数
				
				$content_arr[$key]['disUnpay_num'] = get_pay_num($v['id'],0);//未付款人数
				$content_arr[$key]['disDeposit_num'] = get_pay_num($v['id'],1);//定金已支付人数
				$content_arr[$key]['disFullpay_num'] = get_pay_num($v['id'],2);//全款已支付人数
				
			} 
		}
	
		if($show != "<div class='pagination'></div>"){
			$this->assign('page',$show);
		}
		$this->assign('event_content',$rs);
        $this->assign('contents',$content_arr);
		
        $this->display();
	}
	
	/*排期订单信息*/
	public function event_schedule_reservation($event_id,$calendar_id){

		$map['event_id'] = $event_id;
		$map['calendar_id'] = $calendar_id;
		$map['siteid'] = SITEID;
		$event_attend = D('event_attend')->where($map)->order('id desc')->select();
		foreach ($event_attend as $kk=>&$vv) {
			$member = D('member')->where(array('uid'=>$vv['uid']))->find();
			$vv['nickname']  = $member['nickname'];
			$vv['countnubmer']  = count(json_decode($vv['userinfo'],true));
				
		}

		$this->assign('detainfo',$event_attend);
		$this->display();

	}

	/**
	*修改活动 2015-1-23
	***/
	public function doPost($insurance = 0,$price_text = '',$price_type = 0,$frontmoney = 0,$detailadd = '',$paytype = 0,$id = 0,$cover_id = 0, $title = '',$price = '',$explain = '',$travel_point = '',$pay_info = '',$attention = '',$tag = '',$sort = 0,$traveldays = '',$containertext='',$vice_title='')
    {

	    if (!is_login()) {
			$this->redirect('Home/User/login');
        }
        $pictures_id = explode(',', $_POST['pictures_id']);
    	$cover_id = $pictures_id[0];
		if (!$pictures_id) {
  		       $this->error('请上传活动图面。');
  		 }

        if (trim(op_t($title)) == '') {
            $this->error('请输入标题。');
        }
		
		if(empty($tag)){
			$this->error('请添加活动标签。');
		}
		
    	if($traveldays == ''){ 
    		$this->error('请填写活动天数。');
    	}else{ 
    		if(!is_numeric($traveldays)){ 
    			$this->error('活动天数必须为数字。');
    		}
    	}

	
		switch ($price_type) {
			case '1':
				if($price_text == ''){ 
					$this->error('参考价格不能为空！');
				}	
				break;
			case '2':
				if($price != ''){ 
					if(is_numeric($price) == ''){ 
						$this->error('参考价格输入的金额必须为数字！');
					}
				}else{ 
					$this->error('参考价格不能为空！');
				}
				
				break;
		}
 

		$tmp = @iconv('gbk', 'utf-8', $title);
		if(!empty($tmp)){
		 $title = $tmp;
		}
		preg_match_all('/./us', $title, $match);
		if(count($match[0])>25){ 
			$this->error('标题字数不得超过25个字！');
		}
     
        if (trim(op_h($explain)) == '') {
            $this->error('请输入行程安排。');
        }
		if (trim(op_h($travel_point)) == '') {
            $this->error('请输入线路亮点。');
        }
		 if (trim(op_h($pay_info)) == '') {
            $this->error('请输入费用说明。');
        }
		 if (trim(op_h($attention)) == '') {
            $this->error('请输入注意事项。');
        }
     

        $pictures_id = explode(',', $_POST['pictures_id']);
    	$cover_id = $pictures_id[0];		
        $content = D('Event')->create();

         if($containertext != '' ){ 

        	$containertext = explode(',', $containertext);
        	$content['point_lng'] = $containertext[0];
			$content['point_lat'] = $containertext[1];

        }

		$content['begincity'] = $_POST['begincity'];
		$content['finalcity'] = $_POST['finalcity'];
		
        $content['explain'] = op_h($content['explain']);
        $content['title'] = op_t($content['title']);
		$content['status'] = 0;
		$content['detailadd'] = trim(op_t($detailadd));
		$content['siteid'] = SITEID;
		$tag = implode(',',$_POST['tag']);
		$content['tag'] = $tag;
		$content['sort'] = $sort;


		$content['cover_id'] = $cover_id;
		$content['vice_title'] = $vice_title;
		
		$content['is_recommend'] = $_POST['recommend'];

		if (!$content['begincity']) {
            $this->error('请完善集合地点。');
        }
        if($detailadd == '') $this->error('请填写具体地址！');
		if (!$content['finalcity']) {
            $this->error('请完善目的地点。');
        }
       
        if ($id) {
            $content_temp = D('Event')->find($id);
			$status = $content_temp['status'];
            if (!is_administrator(is_login())) { //不是管理员则进行检测
                if ($content_temp['uid'] != is_login() || !checked_admin(is_login()) || !checked_vip(is_login())) {
                    $this->error('小样儿，可别学坏。别以为改一下页面元素就能越权操作。');
                }
            }
            $content['uid'] = $content_temp['uid']; //权限矫正，防止被改为管理员
			$content['status'] = $status;
			$content['update_time'] = time();
            $rs = D('Event')->where(array('id'=>$id))->save($content);
            if ($rs) {
            	$starnum = $_POST['starnum'];
	        	foreach ($starnum as $key => $value) {
	        		$data2 = explode(',' , $value);
	        		$data['siteid'] = SITEID;
	        		$data['title'] = $data2[0];
	        		$data['grade'] = $data2[1];
	        		$starid = $data2[2];
	        		if($starid){ 
	        			D('event_attribute')->where('id = '.$starid)->save($data);
	        		}else{ 
	        			$data['event_id'] = $id;
	        			D('event_attribute')->add($data);
	        		}
	        		
	        	}
	        	$stardel =  $_POST['stardel'];
	        	foreach ($stardel as $k => $val) {
	        		$map['id'] = $val;
	        		$map['siteid'] = SITEID;
	        		D('event_attribute')->where($map)->delete();
	        	}

            	D('Common/Event')->getEventCalendarTime($id,$find='',$delS=true);
				$_SESSION['event']['fid'] = $rs;
				D('Common/Event')->clean_event_cache();
				$this->success('编辑成功',U('Manage/Event/index'));
            } else {
                $this->error('编辑失败。');
            }
        } else {
			$content['create_time'] = time();
			$content['status'] = 1;	
			$content['uid'] = is_login();	
            $rs = D('Event')->add($content);
            if ($rs) {
            	$starnum = $_POST['starnum'];
	        	foreach ($starnum as $key => $value) {
	        		$data2 = explode(',' , $value);
	        		$data['event_id'] = $rs;
	        		$data['siteid'] = SITEID;
	        		$data['title'] = $data2[0];
	        		$data['grade'] = $data2[1];
	        		D('event_attribute')->add($data);
	        	}

            	D('Common/Event')->getEventCalendarTime($id,$find='',$delS=true);			
				$qrcode_url = set_qrcode(array('id'=>$rs),'event');
				if($qrcode_url){
					$qrcode_data['siteid'] =  SITEID;
					$qrcode_data['uid'] =  is_login();
					$qrcode_data['linkid'] =  $rs;
					$qrcode_data['types'] =  'event';
					$qrcode_data['url'] =  $qrcode_url;
					$qrcode_data['create_time'] =  time();
					D('qrcode')->add($qrcode_data);
				}

				D('Common/Dynamic')->sendMessage(is_login(),'EventAction',op_t($content['title']),$rs,U('Event/Index/detail') );
				D('Common/Event')->clean_event_cache();
				$this->success('发布成功,请完善排期资料！' . $tip, $id?$_SERVER['REQUEST_URI']:U('Manage/Event/index'));
            } else {
				$this->error('发布失败。');
            }
        }
    }

	/*修改线路分类*/
	public function line_type_edit(){
	     if(IS_POST){
		    $id=$_POST['id'];
			$title=trim($_POST['title']);
			$sort=$_POST['sort'];
			
			if($title=='') $this->error('请填写分类');
			$data['title']=$title;
			$data['sort']=$sort;
			$data['update_time']=time();
			$event_save=D('event_type')->where("id = $id")->save($data);
			if($event_save){
			   $this->success('修改成功','refresh');
			}else{
			   $this->error('修改失败');
			}
		 }else{
		   $id=$_GET['id'];
		   $event_type=D('event_type')->where('id='.$id)->find();
		   $this->assign('event_type',$event_type);
		   $this->display();
		 }
	
	}
	/*增加排期*/

	public function event_schedule_add($id=0,$eventid=0,$event_url='',$card_use = '',$price='',$starttime='',$endtime='',$days='',$minpeople='',$maxpeople='',$paytype = '',$deposit = '')
   {	
	    if(IS_POST){
			$insure_info = D('insurance')->where(array('siteid'=>SITEID,'status'=>1))->count();
			if($insure_info == 0){
				$this->error('请先添加活动保险再做操作！');
			}
			if(!$eventid) $this->error('参数错误！');
			$event_content = D('Event')->where(array('id' => $eventid,'siteid'=>SITEID))->find();
			if(!$event_content) $this->error('活动不存在或已被删除！');
			if(!$starttime) $this->error('请输入出发时间！');
			if(!$endtime) $this->error('请输入截止时间！');
			if(strtotime($endtime) > strtotime($starttime)) $this->error('截止日期不能大于出发时间！');
			if(!$days) $this->error('请输入排期天数！');
			if(is_numeric($days) == '') $this->error('排期天数必须为纯数字');
			if(!$minpeople) $this->error('请输入最低人数！');
			if(is_numeric($minpeople) == '') $this->error('最低人数必须为纯数字');
			$maxpeople = intval($maxpeople);
			if($maxpeople != '' || $maxpeople != 0){
				if(is_numeric($maxpeople) == '') $this->error('队员上限必须为纯数字');
				if($minpeople > $maxpeople) $this->error('队员上限不能低于最低人数！');
			}
			$ca_data = D('event_calendar_time')->create();	

			if($paytype == ''){
				$this->error('请选择支付方式！');
			}else{
				switch($paytype){
					case 0;
						if(empty($price)){
							$this->error('请输入日程价格！');
						}else{
							if(is_numeric($price) == '') $this->error('排期价格必须为纯数字');
							if($price < 0.01) $this->error('排期价格不能少于 ￥0.01 ！');
						} 
						$ca_data['deposit'] = 0;						
					break;
					case 1;
						if(empty($price)){
							$this->error('请输入日程价格！');
						}else{
							if(is_numeric($price) == '') $this->error('排期价格必须为纯数字');
							if($price < 0.01) $this->error('排期价格不能少于 ￥0.01 ！');
							if(is_numeric($deposit) == '') $this->error('排期定金必须为纯数字');
							if($deposit < 0.01) $this->error('排期定金不能少于 ￥0.01 ！');
							if($price <= $deposit) $this->error('排期价格必须大于定金');
						}
					break;
					case 2;
						$ca_data['price'] = 0;
						$ca_data['deposit'] = 0;
					break;
				}
			}
			
			$ca_data['begincity'] = $_POST['begincity'];
			if (!$ca_data['begincity']) {
				$this->error('请完善集合地点。');
			}
			
			if($_POST['leader'][0] != 0){ 
				$ca_data['leader'] = implode(",",$_POST['leader']);
			}
		
			if($_POST['vehicle'][0] != ''){ 
				$ca_data['vehicle'] = implode(",",$_POST['vehicle']);
			}
			if($_POST['accommodation'][0] != ''){ 
				$ca_data['accommodation'] = implode(",",$_POST['accommodation']);
			}
			
			if($_POST['team_name'] !=''){ 
				$team_name =trim($_POST['team_name']);
		    	$team_name_len  = strlen($team_name);
		    	if($team_name_len > 9)$this->error('排期队名过长');
		    	$ca_data['team_name'] = $team_name;
			}
			$ca_data['siteid'] = SITEID;	
			$ca_data['overtime'] = date('Y-m-d',strtotime($starttime) +(86400*($days-1)));
			$ca_data['uid'] = is_login();
			$ca_data['status'] = 1;
			$ca_data['time'] = time();
			$ev_ca = D('event_calendar_time')->add($ca_data);
			if($ev_ca){
				D('Common/Event')->getEventCalendarTime($eventid,$find='',$delS=true);
				$this->success('添加成功!',$event_url);
			}else{
				$this->error('添加失败！');
			}
			
		}else{
			$rs = D('Event')->where(array('id' => $eventid,'siteid'=>SITEID))->find();
			if(!$rs){
				$this->error('活动不存在或已被删除');
			}else{
				$calendar_time = D('event_calendar_time')->field('MAX(time) time')->where(array('eventid'=>$eventid,'siteid'=>SITEID))->find();
				if($calendar_time){ 
					$calendar_con = D('event_calendar_time')->field('minpeople,maxpeople,paytype,deposit,vehicle,leader,accommodation,begincity,detailadd')->where(array('eventid'=>$eventid,'siteid'=>SITEID,'creat_time'=> $calendar_time['time']))->find();
					$this->assign('content', $calendar_con);
				}
			}
			$map['status'] = 1;
			$map['is_use'] = 2;
			$map['siteid'] = SITEID;
			$member = D('member')->where($map)->select();
			if($member){
				foreach ($member as $ku=> &$u) {
					$get_leader[$u['uid']] =$u['nickname'];
				}	
			}else{ 
				$get_leader['0'] = '无';
			}
			$this->assign('get_leader', $get_leader);
			$this->assign('event', $rs);
			$this->display();
		}
    }
	/*增加排期*/
	/*排期的编辑*/
	public function event_schedule_edit($id=0,$detailadd='',$event_url='',$eventid=0,$card_use = '',$status = 0,$eventid='',$price='',$starttime='',$endtime='',$days='',$minpeople='',$maxpeople='',$days_left = '',$paytype = '',$deposit = '')
   {
	  
	    $isEdit = $id ? 1 : 0;
		if(IS_POST){
		    if(!$id || !$eventid) $this->error('参数错误！');
		    $event_content = D('Event')->where(array('id' => $eventid,'siteid'=>SITEID))->find();
			if(!$event_content) $this->error('活动不存在或已被删除！');
			if(!$starttime) $this->error('请输入出发时间！');
			$schedule_data = D('event_calendar_time')->where(array('eventid' => $eventid,'uid' => $event_content['uid'],'id' => $id,'siteid'=>SITEID))->find();
			if(!$schedule_data) $this->error('排期已经被删除！');
			if(!$endtime) $this->error('请输入截止时间！');
			if(strtotime($endtime) > strtotime($starttime)) $this->error('截止日期不能大于出发时间！');
			
			if(!$days) $this->error('请输入排期天数！');
			if(is_numeric($days) == '') $this->error('排期天数必须为纯数字');
			
			if(!$minpeople) $this->error('请输入最低人数！');
			if(is_numeric($minpeople) == '') $this->error('最低人数必须为纯数字');
			$maxpeople = intval($maxpeople);
			if($maxpeople != '' || $maxpeople != 0){
				if(is_numeric($maxpeople) == '') $this->error('队员上限必须为纯数字');
				if($minpeople > $maxpeople) $this->error('队员上限不能低于最低人数！');	
			}
			$ca_data = D('event_calendar_time')->create();	
			if($paytype == ''){
				$this->error('请选择支付方式！');
			}else{
				switch($paytype){
					case 0;
						if(empty($price)){
							$this->error('请输入日程价格！');
						}else{
							if(is_numeric($price) == '') $this->error('排期价格必须为纯数字');
							if($price < 0.01) $this->error('排期价格不能少于 ￥0.01 ！');
						} 
						$ca_data['deposit'] = 0;						
					break;
					case 1;
						if(empty($price)){
							$this->error('请输入日程价格！');
						}else{
							if(is_numeric($price) == '') $this->error('排期价格必须为纯数字');
							if($price < 0.01) $this->error('排期价格不能少于 ￥0.01 ！');
							if(is_numeric($deposit) == '') $this->error('排期定金必须为纯数字');
							if($deposit < 0.01) $this->error('排期定金不能少于 ￥0.01 ！');
							if($price <= $deposit) $this->error('排期价格必须大于定金');
						}
					break;
					case 2;
						$ca_data['price'] = 0;
						$ca_data['deposit'] = 0;
					break;
				}
			}
			if($status != ''){
				$ca_data['status'] = $status; 
			}else{
				$ca_data['status'] = 1; 
			}
			
			$ca_data['begincity'] = $_POST['begincity'];
			if (!$ca_data['begincity']) {
			$this->error('请完善集合地点。');
			}
			if($_POST['leader'][0] == 0){ 
				$ca_data['leader'] = null;
			}else{ 
				$ca_data['leader'] = implode(",",$_POST['leader']);
			}
		
			$team_name =trim($_POST['team_name']);
			if($team_name != ''){ 
				$team_name_len  = strlen($team_name);
		    	if($team_name_len > 9)$this->error('排期队名过长');
		    	$ca_data['team_name'] = $team_name;
			}
			

			if($_POST['vehicle'][0] != ''){ 
				$ca_data['vehicle'] = implode(",",$_POST['vehicle']);
			}
			if($days_left != ''){
				$ca_data['days_left'] = $days_left;
			}
		
			if($_POST['accommodation'][0] != ''){ 
				$ca_data['accommodation'] = implode(",",$_POST['accommodation']);
			}
			$ca_data['overtime'] = date('Y-m-d',strtotime($starttime) +(86400*($days-1)));

			$ev_ca = D('event_calendar_time')->where(array('id'=>$_POST['id'],'siteid'=>SITEID))->save($ca_data);
			if($ev_ca){
				D('Common/Event')->getEventCalendarTime($eventid,$find='',$delS=true);
				$this->success('修改成功!',$event_url);
			}else{
				$this->error('修改失败！');
			}
		
		}else{
			$rs = D('Event')->where(array('id' => $eventid,'siteid'=>SITEID))->find();
			if(!$rs){
				$this->error('活动不存在或已被删除');
			}else{
				
				$tiem_arr = D('event_calendar_time')->where(array('eventid' => $eventid,'id' => $id,'siteid'=>SITEID))->find();
				
			}
			$map['status'] = 1;
			$map['is_use'] = 2;
			$map['siteid'] = SITEID;
			$member = D('member')->where($map)->select();
			if($member){
				foreach ($member as $ku=> &$u) {
					$get_leader[$u['uid']] =$u['nickname'];
				}
			}else{ 
				$get_leader['0'] = '无';
			}
			$this->assign('get_leader', $get_leader);
			$status = get_event_status();
			$this->assign('status',$status);
			$this->assign('event_content', $rs);
			$this->assign('content', $tiem_arr);
			$this->display();
		
		}
    }
	/*
	 * 排期的隐藏或显示
	 */
	public function schedule_display($id = '',$display = '',$event_id=0){
			
		   if(is_array($id)){
		   		
                if(empty($id)){
					$this->error('请选择要操作的数据!');
				}
				if($display==''){
					$this->error('请选择显示还是隐藏');
				}
              
				$id = implode(',', $id);
				$rs = D('event_calendar_time')->where(array('siteid'=>SITEID,'eventid'=>$event_id,'id'=>array('in', $id)))->setField('display',$display);

			    if($rs){
			    	D('Common/Event')->getEventCalendarTime($event_id,$find='',$delS=true);
					$this->success('操作成功');
				}else{
					$this->error('操作失败');
				}
			   
		   }else{
			    if($id=='' || $display==''){
					$this->error('请选择要操作的数据');
				}
				$rs = D('event_calendar_time')->where(array('id'=>$id,'siteid'=>SITEID))->setField('display',$display);
				if($rs){
					D('Common/Event')->getEventCalendarTime($event_id,$find='',$delS=true);
					$this->success('操作成功');
				}else{
					$this->error('操作失败');
				} 
			   
		   }
	}
	






    /*排期删除*/
	public function event_schedule_del($id=0)
	{
		if(!$id) exit('参数错误!');
		
		$data = D('event_calendar_time')->where(array('id'=>$id,'siteid'=>SITEID))->find();
		
		if(!$data) exit('排期不存在');
		
		if($data['uid'] !=is_login() && !checked_admin(is_login())) exit('权限不足');
		
		
		$ca_data['status'] = '-1';
		$updata = D('event_calendar_time')->where(array('id'=>$id,'siteid'=>SITEID))->save($ca_data);
		if($updata){
			echo 1;
		}else{
			exit('删除失败!');
		}
	}
	/*添加保险*/
	public function insurance_doAdd($name,$sum_insured,$price){
        if(IS_POST){
		    $name=op_t($name);
		    $sum_insured=op_t($sum_insured);
		    $price=op_t($price);
		    if($name=='') exit(json_encode(array('status'=>false,'msg'=>'请填写保险名称')));
		    if($sum_insured=='') exit(json_encode(array('status'=>false,'msg'=>'请填写正确的保额哦')));
		    if($price=='') exit(json_encode(array('status'=>false,'msg'=>'请填写正确的保险价格哦')));
			
			$data['name']=$name;
			$data['sum_insured']=$sum_insured;
			$data['price']=$price;
			$data['time']=time();
			$data['siteid']=SITEID;
			
		    D('insurance')->create($data);
			$list=D('insurance')->add();
			$string = get_insurance_select();	
				if($list){
					exit(json_encode(array('status'=>true,'string'=>$string)));
				}else{
					exit(json_encode(array('status'=>false,'msg'=>'添加失败')));
				}
	    }
	}


/**********************************活动类型********************************************************/

	/*
	*活动报名须知
	***/
	public function event_sign_notice($sign_notice=''){
		$id = D('websit')->where("siteid=".SITEID ." and sign_notice !=''")->find();
        $isEdit = $id ? 1 : 0;
	   if (IS_POST) {
            $sign_notice = op_t($sign_notice) =='' ? $this->error('请填写报名须知') : $sign_notice;
			
			$rs_content = D('websit')->where("siteid=".SITEID)->setField('sign_notice',$sign_notice);	
            if ($rs_content) {
            	$domain = $_SERVER['HTTP_HOST'];
            	clean_website_info_cash($domain);
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Event/event_sign_notice'));
            } else {
                $this->error($isEdit ? '编辑失败' : '添加失败');
            }
			
        } else {
			
			if ($isEdit) {
             	$notice_data = $id;
            } 
            $notice_data['page_title'] = $isEdit ? '编辑活动报名须知' : '添加活动报名须知';
	       	$this->assign('datainfo',$notice_data);
	       	$this->display(); 
        }
  
	}



/**********************************活动类型********************************************************/

	/*
	*活动类型***
	*/
	public function event_type(){
		//读取列表
        $map = array('status' => array('egt',0),'siteid'=>SITEID);
        $list = D('event_type')->where($map)->page($page, $r)->select();
		foreach($list as &$v){
			$v['new_status'] = $v['status']?'已启用':'已禁用';
			$v['display_view'] = $v['display']?'显示':'不显示';
			$v['customization_view'] = $v['customization']?'显示':'不显示';
			
		}
		$this->assign('datainfo',$list);
		$this->display();
	}
	
	/*
	*添加or修改活动类型
	**/
	public function event_type_edit($id=0,$title='',$sort=0,$customization=0){
		$isEdit = $id ? 1 : 0;
		if (IS_POST) {
			$title	= op_t(trim($title));
			$sort	= op_t(trim($sort));
			if($title=='') $this->error('活动类型不能为空');
			if($sort!=''){
			   if(!preg_match('/^\d+$/',$sort)) $this->error('排序必须为数字!');	
			}
			
			$data['title'] = $title;
			$data['sort']  = $sort;
			$data['customization'] = $customization;
			
			if ($isEdit) {
				$rs_content = D('event_type')->where("id=".$id)->save($data);
				
            } else {
				$event_types=D('event_type')->where(array('status'=>array('egt','0'),'siteid'=>SITEID))->select();
				if(count($event_types)<3){
				    $data['siteid']		=	SITEID;
					$data['status']		=	1;
					$data['display']	=	1;
					$data['create_time']=	time();
					$rs_content	=	D('event_type')->data($data)->add();
					
				}else{
					$this->error('最多可以添加3条数据哦');
				}	
			}
			
            if ($rs_content) {
            	D('Common/Event')->clean_event_cache();
				$this->success($isEdit ? '编辑成功' : '添加成功', U('Event/event_type'));
				
            } else {
				$this->error($isEdit ? '编辑失败' : '添加失败');
				
            }
			
        } else {
			
            if ($isEdit) {
                $event_type_data =D('event_type')->where('id=' . $id)->find();
            }else{
				$event_type_data['sort'] = 0 ;
			} 
			$event_type_data['page_title'] = $isEdit ? '编辑活动类型' : '添加活动类型';
            $this->assign('datainfo',$event_type_data);
            $this->display();
        }
	}
	 /**
     * 设置显示or取消显示
     * @param $ids
     * @param $tip
     * autor:xjw129xjt
     */
    public function doDisplay($tip)
    {	

    	$id = array_unique((array)I('id', 0));
		$id = is_array($id) ? implode(',', $id) : $id;
	    if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}
        $reds=D('event_type')->where(array('id' => array('in', $id)))->setField('display', $tip);
        if($reds){ 
        	D('Common/Event')->clean_event_cache();
        	$this->success('设置成功', $_SERVER['HTTP_REFERER']);
        }else{ 
        	$this->error('设置失败');
        }
		
    }

}  

<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM3:40
 */

namespace Usercenter\Controller;

use Think\Controller;

class EventorderController extends BaseController
{
	protected $userdata;
    protected $mTalkModel;
	public function _initialize()
    {	
        parent::_initialize();
        if (!is_login()) {
           // $this->error('请登录后再访问本页面。');
			 $this->redirect('Home/User/login');
        }
		$this->userdata = query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $uid);
        $this->mTalkModel = D('Talk');
        $this->setTitle('个人中心');
	}

	public function index(){ 
		if(I('status')!=''){
		   $map['status']=I('status');
		}
		$trade_sn = op_t(I('trade_sn'));
		//---订单号---
		if($trade_sn!=''){
		  $map['trade_sn']=$trade_sn;
		}
		$data = D('Event')->getmyEvent($map);
		$show = $data['show'];
		$event_attend = $data['event_attend'];
		$this->assign('user', $this->userdata);
		$this->assign('event',$event_attend);
		$this->assign('tab',$tab);
		$this->assign('page',$show);
        $this->display();

	} 

	//订单详情
	public function myevent_detail($trade_sn){
		$detailData = D('Event')->getDetailmyEvent($trade_sn);
		if($detailData){
			$signerinfo   	= $detailData['signerinfo']; 
			$total_num  	= $detailData['totalnum'];
			$event 			= $detailData['event'];
			$calendar_info  = $detailData['calendarinfo'];
			$card_info  	= $detailData['cardinfo'];
			$event_attend 	= $detailData['eventattend'];
			$member_left    = $detailData['memberleft'];

			$this->assign('member',$signerinfo);
			$this->assign('total_num',$total_num);
			$this->assign('event_content',$event);
			$this->assign('content',$calendar_info);
			$this->assign('card_info',$card_info);
			$this->assign('event_attend',$event_attend);
			$this->assign('member_left',$member_left);
			$this->display();
		}else{ 
			$this->error('订单不存在');
		}
	}

	//执行修改过之后
    public function event_member($id=0,$calendar_id){
		$event_attend = D('event_signer')->where(array('order_id'=>$id,'calendar_id'=>$calendar_id,'siteid'=>SITEID))->order('id desc')->select();		
		foreach($event_attend as $val){
			$userinfo[] = json_decode($val['user_info'],true);
		}
        $this->assign('userinfo',$userinfo);
        $this->display();
    }

     public function myevent_detail_upstatus($id,$status)
    {
		if(!$id) $this->error('参数错误！');
		$status = trim($status);
		$msg = updata_evevt_status($id,$status);
		
		if($msg['s'] == 1){
			exit(json_encode(array('status'=>$msg['s'],'m'=>$msg['m'])));
		}else{
			exit(json_encode(array('status'=>$msg['s'],'m'=>$msg['m'])));
		}
		
    }

    /*活动订单的修改*/
	public function myevent_detail_edit($trade_sn){

		$detailData = D('Event')->getDetailmyEventEdit($trade_sn);
		if($detailData['error'] == ''){
			$signerinfo   	= $detailData['signerinfo']; 
			$total_num  	= $detailData['total_num'];
			$event 			= $detailData['event'];
			$calendar_info  = $detailData['calendar_info'];
			$card_info  	= $detailData['card_info'];
			$event_attend 	= $detailData['event_attend'];
			$member_left    = $detailData['member_left'];

			$this->assign('member',$signerinfo);
			$this->assign('total_num',$total_num);
			$this->assign('event_content',$event);
			$this->assign('content',$calendar_info);
			$this->assign('card_info',$card_info);
			$this->assign('event_attend',$event_attend);
			$this->assign('member_left',$member_left);
			$this->display();
		}else{ 
			$this->error($detailData['error']);
		}
	}

	public function edit_order_info(){ 
		$dataEdit =  $_POST;
		$data = D('Event')->setDetailmyEventEdit($dataEdit);
		exit($data) ;
	}

	public function get_seats_left($event_membercontacts='',$event_id='',$calendar_id=''){
		exit(D('Event')->setEventStatusUpdate($event_membercontacts,$event_id,$calendar_id));
	}

    /**
     * 报名页面
     * @param $event_id
     * autor:xjw129xjt
     */
    public function sign($event_id,$schedule_id,$ordertype='')
    {	
    	$dataSign = D('Event')->getSign($event_id,$schedule_id,$ordertype);
    	if($dataSign['error'] == ''){ 
    		$insurance_string = $dataSign['insurance_string'];
    		$mem_info         = $dataSign['mem_info'];
    		$ordertype        = $dataSign['ordertype'];
    		$content          = $dataSign['content'];
    		$event_content    = $dataSign['event_content'];
    		$member 		  = $dataSign['member'];
    		$member_json 	  = $dataSign['member_json'];
    		$card_arr 		  = $dataSign['card_arr'];
    		$this->assign('insurance_string', $insurance_string);
    		$this->assign('mem_info',$mem_info);
			$this->assign('ordertype', $ordertype);
	        $this->assign('content', $content);
			$this->assign('event_content', $event_content);
			$this->assign('member',$member);
			$this->assign('member_json',$member_json);
			$this->assign('card_arr',$card_final);
	        $this->display();
	    		
    	}else{ 
    		$this->error($dataSign['error']);
    	}
    }

    /**
     * 报名参加活动
     * @param $event_id
     * @param $name
     * @param $phone
     * autor:xjw129xjt
     */
    public function doSign()
    {	
		
		if (!is_login()){
	   		$this->redirect('Home/User/login');
		}
		$dataSign = $_POST;
		$dataDoSign = D('Event')->setConfigDoSign($dataSign);
		if($dataDoSign['error'] == ''){ 
			$this->success($dataDoSign['success'],U($dataDoSign['url'],array('trade_sn'=>$dataDoSign['trade_sn'])));
		}else{ 
			$this->error($dataDoSign['error']);
		}
	}
	



}
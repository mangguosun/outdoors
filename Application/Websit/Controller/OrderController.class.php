<?php

namespace Websit\Controller;

use Think\Controller;

class OrderController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize();    
	}
	    public function index(){
			$status = $_GET['status'];
		$status = isset($status)?$status:1;
		switch($status){
			case 1;
				$ord_status = $_GET['ord_status'];
				$trade_sn = op_t(I('event_trade_sn'));
				$ord_status = isset($ord_status) ? $ord_status : 'inuse';
				if(!$trade_sn){
					switch($ord_status){
						case 'inuse':
							$map = "siteid = ".SITEID." and status != -1 and status != 0";
						break;
						case 'halfpay':
							$map = "siteid = ".SITEID." and (status = 11 or status = 12) and pay_status = 1 and paytype = 1 and status != -1 and status != 0";
						break;
						case 'succ':
							$map = "siteid = ".SITEID." and pay_status = 2 and status != -1 and status != 0";
						break;
						case 'unpay':
							$map = "siteid = ".SITEID." and pay_status = 0 and status != -1 and status != 0";
						break;
						case 'all':
							$map = "siteid = ".SITEID;
						break;
					}
				}else{
					$map = "siteid = ".SITEID." and trade_sn = $trade_sn";
				}
				$count=D('event_attend')->where($map)->count();//总数				 
				$Page  = new \Think\Page($count,10);// 
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				$show  = $Page->show();// 
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
					$event_attend[$key]['create_time'] = date("Y-m-d H:i:s",$v['creat_time']);
					$map = "siteid = ".SITEID." and calendar_id = ".$event_calendar_time['id']." and event_id = ".$event['id']." and status = 1";
					$event_attend[$key]['signer'] = D('event_signer')->where($map)->select();					
					$event_attend[$key]['travel_number'] = count($event_attend[$key]['signer']);					
				}
				$this->assign('event',$event_attend);
				$this->assign('page',$show);
			break;
			case 2;			
				$tailor_trade_sn = I('tailor_trade_sn');
				if(!$tailor_trade_sn){
					$map = "siteid = ".SITEID." and status = 1";
				}else{
					$map = "siteid = ".SITEID." and status = 1 and trade_sn = $tailor_trade_sn";
				}
				$count = D('event_tailor')->where(array('siteid'=>SITEID,'status'=>1))->count();
				$Page  = new \Think\Page($count,10);// 
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				$show  = $Page->show();// 
				$tailor_arr = D('event_tailor')->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
				foreach($tailor_arr as $key => $val){
					$tailor_arr[$key]['tailor_note'] = D('event_tailor_note')->where(array('tailor_id'=>$val['id'],'siteid'=>SITEID,'status'=>1))->select();
				}
				$this->assign('tailor_arr',$tailor_arr);
				$this->assign('page',$show);
			break;
			case 3;
				if(I('order_status')!=''){
						if(I('order_status')==2){
							$map['status'] = array('elt',2);
						}else{
					   $map['status']=I('order_status');
					   }
					}
				if(I('pay_status')!=''){
				   $map['pay_status']=I('pay_status');
				}
				$seek = op_t(I('seek'));
				
				//---查询---
				if($seek!=''){
					$where['order_sn']  = array('like','%'.$seek.'%');
					$where['consignee_name']  = array('like','%'.$seek.'%');
					$where['phone']  = array('like','%'.$seek.'%');
					$where['_logic'] = 'or';
					$map['_complex'] = $where;
				}
				$map['siteid']	=	SITEID;

				
				$count=D('shop_ordersn')->where($map)->count();//总数
				$Page  = new \Think\Page($count,10);// 
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				$show  = $Page->show();// 
				$shop_order_arr = D('shop_ordersn')->where($map)->order("create_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();
				foreach($shop_order_arr as $key => $value){
					$shop_order_arr[$key]['nickname'] = query_user('nickname',$shop_order_arr[$key]['uid']);
				}
				
				
				$this->assign('shop_arr',$shop_order_arr);
				$this->assign('page',$show);
			break;
		}	
		$this->assign('status',$status);
        $this->display();
    }
	public function event_detail($trade_sn){
		if(!checked_admin(is_login()) || !checked_vip(is_login())){
			$this->error('您没有查看权限！');
		}
		
		$event_attend = D('event_attend')->where(array('trade_sn'=>$trade_sn,'siteid'=>SITEID))->find();
		if($event_attend){
			$signer_info = D('event_signer')->where(array('siteid'=>SITEID,'order_id'=>$event_attend['id']))->select();
			$total_num = count($signer_info);
			foreach($signer_info as $key => $val){
				$signerinfo[$key]['user_info'] = json_decode($val['user_info'],true); 
				$signerinfo[$key]['id'] = $val['id'];
				$signerinfo[$key]['insurance_info'] = json_decode($val['insurance_info'],true); 
			} 
			$card_info = D('pointcard')->where(array('cardid'=>$event_attend['cardid'],'siteid'=>SITEID))->find();
			$typeinfo = D('pointcard_type')->where(array('siteid'=>SITEID,'ptypeid'=>$card_info['ptypeid']))->find();
			$card_info['name'] = $typeinfo['name'];		
			$event = D('event')->where(array('id'=>$event_attend['event_id'],'siteid'=>SITEID))->find();
			$calendar_info = D('event_calendar_time')->where(array('id'=>$event_attend['calendar_id'],'siteid'=>SITEID,'eventid'=>$event_attend['event_id']))->find();
			$this->assign('member',$signerinfo);
			$this->assign('total_num',$total_num);
			$this->assign('event_content',$event);
			$this->assign('content',$calendar_info);
			$this->assign('card_info',$card_info);
			$this->assign('event_attend',$event_attend);
			$this->display();
		}else{
			$this->error('订单不存在');
		}
	}
	/*查看所有活动参加者*/
	public function event_allmember($id=0,$eventid=0){	
		$map = "calendar_id = $id and siteid = ".SITEID." and event_id = $eventid and status = 1 and order_status != 0 and order_status != -1";
		$event_attend = D('event_signer')->where($map)->order('id desc')->select();	
		foreach($event_attend as $key => $val){		
			$arr_info[$key] = json_decode($val['user_info'],true);
			$arr_info[$key]['order_status'] = $val['order_status'];
		}
        $this->assign('arr_info',$arr_info);
        $this->display();
    }
	/********************/
	/*查看商城订单详情*/
	public function shop_order_detail($order_sn){

		$order_info = D('shop_ordersn')->where(array('order_sn'=>$order_sn,'siteid'=>SITEID))->find();
		$goods_list = D('shop_order_info')->where(array('order_sn'=>$order_sn,'siteid'=>SITEID))->select();
		/*******优惠券信息*********/
		$card_info = D('pointcard')->where(array('cardid'=>$order_info['cardid'],'siteid'=>SITEID))->find();
		$typeinfo = D('pointcard_type')->where(array('ptypeid'=>$card_info['ptypeid'],'siteid'=>SITEID))->find();
		$amount = $card_info['amount'];
		$endtime = !empty($card_info['endtime']) ? date('Y-m-d H:i:s',$card_info['endtime']) : 0 ;
		$order_info['amount']=$amount;
		$order_info['cardname']=$typeinfo['name'];
		/*******优惠券信息结束*********/
		/**************计算商品总价和总数*******************/
		foreach($goods_list as $value){
			$fr_freight=$value['freight'];
			$price=$value['goods_price'];
			$num=$value['goods_num'];
			$alltotalprice=$alltotalprice+($price*$num);
			$allgoodsnum=$allgoodsnum+$num;
			$freight=$freight+$fr_freight;
		}
			$order_info['allfreight']=$freight;
		$order_info['totalcostprice']=$alltotalprice;
		$order_info['allgoodsnum']=$allgoodsnum;
		/**************计算商品总价和总数结束*******************/
		$order_info['nickname'] = query_user('nickname',$order_info['uid']);
	    $begincity = get_citys($order_info['consignee_address']);
		
		$this->assign('goods_list',$goods_list);
		$this->assign('begincity',$begincity);
		$this->assign('order_info',$order_info);
		$this->display();
	}
	//更改商品总价
	public function shop_order_editmoney(){
		$order_sn=$_GET['order_sn'];
		$alltotalprice = D('shop_ordersn')->where(array('order_sn'=>$order_sn,'siteid'=>SITEID))->find();
		$this->assign('alltotalprice',$alltotalprice);
		$this->display();
	}
	//更改商品总价
	public function shop_order_doeditmoney(){
		$order_sn=$_POST['order_sn'];
		$status = D('shop_ordersn')->where(array('order_sn'=>$order_sn))->getField('status');
		if($status==20){
			if(!is_numeric($_POST['real_amount']) || $_POST['real_amount']<=0 ){
					$this->error('请填写正确的价格!');
				}
				if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $_POST['real_amount'])) {  
					$this->error('价格最多可保留两位小数点');  
				}
			$order['alltotalprice']=$_POST['real_amount'];
			$res = D('shop_ordersn')->where(array('order_sn'=>$order_sn))->save($order);
			if($res){
				$this->success('更改成功',U('Websit/Order/shop_order_detail',array('order_sn'=>$order_sn)));
			}
		}else{
			$this->error('订单状态错误');
		}

	}
	
	
	/*查看活动参加者*/
	public function event_member($id=0,$calendar_id){
       $event_attend = D('event_signer')->where(array('order_id'=>$id,'calendar_id'=>$calendar_id,'siteid'=>SITEID))->order('id desc')->select();		
		foreach($event_attend as $val){
			$userinfo[] = json_decode($val['user_info'],true);
		}
        $this->assign('userinfo',$userinfo);
        $this->display();
    }
	public function sms_notice($id = '',$eventid = ''){
		if(!$id || !$eventid) $this->error('参数错误！');
		$event = D('event')->where(array('id'=>$eventid,'siteid'=>SITEID))->find();
		$calendar = D('event_calendar_time')->where(array('siteid'=>SITEID,'id'=>$id))->find();
		$map = "calendar_id = {$id} and event_id = {$eventid} and siteid = ".SITEID." and status > 0 and order_status != -1 and order_status != 0";
		$status_arr = D('event_signer')->where($map)->group('order_status')->Field('order_status')->select();
		foreach($status_arr as $key => $val){
			$status_arr[$key]['text'] = return_order_status($val['order_status']);
		}
		$this->assign('status_arr',$status_arr);
		$this->assign('event',$event);
		$this->assign('calendar',$calendar);
		$this->display();
	}	
	public function use_excel($id,$eventid){  
		$map = "calendar_id = $id and siteid = ".SITEID." and event_id = $eventid and status > 0 and order_status != -1 and order_status != 0";
		$event_attend = D('event_signer')->where($map)->order('id desc')->select();
		foreach($event_attend as $key => $val){
			$arr_info[$key] = json_decode($val['user_info'],true);
			$arr_info[$key]['order_status'] = $val['order_status'];
		}
		$Model = new \Think\Model();
		$result = $Model->table(array('thinkox_event_calendar_time'=>'tct','thinkox_event'=>'te'))
						->where("tct.siteid = te.siteid and tct.siteid = ".SITEID." and tct.eventid= te.id and tct.id = $id")
						->field("tct.starttime,te.title")
						->find();
						//dump($result);
		$title = $result['title'];
		$starttime = $result['starttime'];
		/*******************************************************************/
		vendor('PHPExcel.PHPExcel');
		vendor('PHPExcel.IOFactory');
		vendor('PHPExcel.Writer#Excel5');
		vendor('PHPExcel.Writer#Excel2007');
		//创建一个excel对象
		$objPHPExcel = new \PHPExcel();
		//$objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
		$objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
		 //激活第一个选项， 然后填充数据
		$objPHPExcel->setActiveSheetIndex( 0 );
		$objActSheet = $objPHPExcel->getActiveSheet ();
		$objActSheet->setCellValue ( 'A1', '姓名');
		$objActSheet->setCellValue ( 'B1', '身份证');
		$objActSheet->setCellValue ( 'C1', '电话');
		$objActSheet->setCellValue ( 'D1', '邮箱');
		$objActSheet->setCellValue ( 'E1', 'QQ');
		$objActSheet->setCellValue ( 'F1', '社会角色');
		$objActSheet->setCellValue ( 'G1', '紧急人姓名');
		$objActSheet->setCellValue ( 'H1', '紧急人联系方式');
		$objActSheet->setCellValue ( 'I1', '订单状态');		
		$objActSheet->getColumnDimension('A')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('B')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('C')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('D')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('G')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('E')->setWidth(15);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('F')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('H')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('I')->setWidth(30);//改变此处设置的长度数值		
		foreach($arr_info as $key => $val){	
				$key = $key + 2;
				$objActSheet->setCellValue("A{$key}",$val['realname']);  
				$objActSheet->setCellValue("B{$key}",chunk_split($val['card']),4," ");           
				$objActSheet->setCellValue("C{$key}",$val['telephone']);         
				$objActSheet->setCellValue("D{$key}",$val['email']);
				$objActSheet->setCellValue("E{$key}",$val['qq']);
				$objActSheet->setCellValue("F{$key}",get_role($val['role']));
				$objActSheet->setCellValue("G{$key}",$val['emergencycontact']);
				$objActSheet->setCellValue("H{$key}",$val['emergencyphone']);
				$objActSheet->setCellValue("I{$key}",strip_tags(get_event_order_status($val['order_status'])));			
		}
		$filename = get_webinfo('webname').'—'.$title.'['.$starttime.']';
		$filename = iconv("utf-8", "gbk", $filename);
		ob_end_clean();
		ob_start();
		header("Pragma:public0");
		header("Expires:0");
		header("Cache-Control:must-relalidate,post-check = 0,pre-check = 0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-excel");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition:attachment;filename="'.$filename.'.xls" ');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		
		/***********************************************************************/
	}
	/*查看单个活动活动参加者*/
	public function open_event_dayinfo($eventid = 0,$id=0)
    {
	   $event_content = D('event')->where(array('id'=>$eventid,'siteid'=>SITEID))->find();
	   $content = D('event_calendar_time')->where(array('id'=>$id,'siteid'=>SITEID))->find();
	   $event_attend = D('event_attend')->where(array('event_id'=>$eventid,'calendar_id'=>$id,'siteid'=>SITEID))->order('id desc')->select();
		foreach ($event_attend as $key => &$v) {
			$member = D('member')->where(array('uid'=>$v['uid']))->find();
			$v['nickname']  = $member['nickname'];
			$v['countnubmer']  = count(json_decode($v['userinfo'],true));
		}
		$this->assign('event_content', $event_content);
		$this->assign('content', $content);
		$this->assign('event_attend', $event_attend);		
        $this->display();
    }

	 public function do_send_sms($id = '',$eventid = '',$notice = '',$order_status = ''){
		if(!$id || !$eventid) $this->error('参数错误！');
		if(!$notice) $this->error('请填写短信内容');
		if($order_status == 0){
			$map = "calendar_id = $id and siteid = ".SITEID." and event_id = $eventid and status = 1 and order_status != 0 and order_status != -1";
		}else{
			$map = "calendar_id = $id and siteid = ".SITEID." and event_id = $eventid and status = 1 and order_status = $order_status";
		}	
		$event_attend = D('event_signer')->where($map)->order('id desc')->select();	
		foreach($event_attend as $key => $val){		
			$arr_info[$key]['user_info'] = json_decode($val['user_info'],true);
			$arr_info[$key]['event_id'] = $val['event_id'];
			$arr_info[$key]['calendar_id'] = $val['calendar_id'];
			$arr_info[$key]['signer_id'] = $val['id'];
		}
		$webinfo = json_decode(WEBSITEINFO,true);
		foreach($arr_info as $key => $val){
			$arr[$key] = sms_alerts($val['user_info']['telephone'],$notice,'活动短信通知');
			$this->do_add_smslog($val,$arr[$key]['error'],$notice);
		}	
		$this->success('短信已批量发送！',U('Websit/Order/sms_backinfo',array('calendar_id'=>$id,'event_id'=>$eventid)));
	 }
	public function do_add_smslog($arr,$back_num,$msg){
		$calendar_id = $arr['calendar_id'];
		$event_id = $arr['event_id'];
		$map = "calendar_id = $calendar_id and event_id = $event_id and uid = ".is_login()." and siteid = ".SITEID."";
		$map1 = "$map and send_count = (select MAX(send_count) from thinkox_event_sms_log where $map)";
		$send_info = D('event_sms_log')->where($map1)->find();
		if($send_info){
			$data['send_count'] = $send_info['send_count'] +1;
		}else{
			$data['send_count'] = 1;
		}
		$webinfo = json_decode(WEBSITEINFO,true);
		$data['uid'] = is_login();
		$data['signer_id'] = $arr['signer_id'];
		$data['event_id'] = $arr['event_id'];
		$data['calendar_id'] = $arr['calendar_id'];
		$data['siteid'] = SITEID;
		$data['create_time'] = time();	
		$data['msg'] = $msg;	
		$data['reciever_name'] = $arr['user_info']['realname'];	
		$data['reciever_telephone'] = $arr['user_info']['telephone'];	
		$data['back_info'] = sms_back_info($back_num) != '' ? sms_back_info($back_num):'未知错误';
		$data['send_web'] = $webinfo['webname'];
		$data['user_info'] = json_encode($arr['user_info']);
		D('event_sms_log')->add($data);
	}
	public function sms_backinfo($calendar_id,$event_id){
		if(!$calendar_id || !$event_id) $this->error('参数错误！');
		$Model = new \Think\Model();
		$map = "calendar_id = $calendar_id and event_id = $event_id and uid = ".is_login()." and siteid = ".SITEID."";
		$sql = "select * from thinkox_event_sms_log where $map and send_count = (select MAX(send_count) from thinkox_event_sms_log where $map)";
		$sms_arr = $Model->query($sql);
		foreach($sms_arr as $key => $val){
			$sms_arr[$key]['user_info'] = json_decode($val['user_info'],true);
		}
		$succ_arr = array();
		$err_arr = array();
		foreach($sms_arr as $key => $val){
			if($val['back_info'] == '发送成功'){
				$succ_arr[$key] = $val;
				$succ_arr[$key]['user_info'] = json_decode($val['user_info'],true);
			}else{
				$err_arr[$key] = $val;
			}
		} 	
		$tid = count($sms_arr);
		$sid = count($succ_arr);
		$eid = count($err_arr);
		$this->assign('tid',$tid);
		$this->assign('sid',$sid);
		$this->assign('eid',$eid);
		$this->assign('contents',$sms_arr);
		$this->display();
	}
	/***************私家定制*****************************************/
	public function do_add_tailor_note($tailor_id=0,$content=''){
		if(!$tailor_id) $this->error('参数错误！');
		$tailor_info = D('event_tailor')->where(array('id'=>$tailor_id,'siteid'=>SITEID,'status'=>1))->find();
		if(!$tailor_info) $this->error('未知的需求！');
		if(!checked_admin(is_login()) || !checked_vip(is_login())) $this->error('权限不足，无法添加！');
		if(!$content) $this->error('备注内容不能为空！');
		$data['content'] = $content;
		$data['uid'] = is_login();
		$data['create_time'] = time();
		$data['tailor_id'] = $tailor_id;
		$data['siteid'] = SITEID;
		$data['status'] = 1;
		$rs = D('event_tailor_note')->add($data);
		if($rs){
			$this->success('添加备注成功！',U('Websit/Order/index',array('status'=>2)));
		}else{
			$this->error('添加失败！');
		}
		
	}
	public function show_note($id){	
		if(!$id) $this->error('参数错误!');
		$tailor_info = D('event_tailor')->where(array('id'=>$id,'siteid'=>SITEID,'status'=>1))->find();
		if(!$tailor_info) $this->error('未知的需求！');
		if(!checked_admin(is_login()) || !checked_vip(is_login())) $this->error('权限不足，无法查看！');
		$map = "tailor_id = $id and siteid = ".SITEID." and status = 1";
		$count=D('event_tailor_note')->where($map)->count();//总数
		$Page  = new \Think\Page($count,10);// 
		$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
		$show  = $Page->show();// 
		$note_arr = D('event_tailor_note')->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('note_arr',$note_arr);
		$this->assign('page',$show);
		$this->display();
	}
	public function show_refe($id){
		if(!$id) $this->error('参数错误!');
		$tailor_info = D('event_tailor')->where(array('id'=>$id,'siteid'=>SITEID,'status'=>1))->find();
		if(!$tailor_info) $this->error('未知的需求！');
		if(!checked_admin(is_login()) || !checked_vip(is_login())) $this->error('权限不足，无法查看！');
		$this->assign('refe',$tailor_info['reference']);
		$this->display();
	}
	public function show_need($id){
		if(!$id) $this->error('参数错误!');
		$tailor_info = D('event_tailor')->where(array('id'=>$id,'siteid'=>SITEID,'status'=>1))->find();
		if(!$tailor_info) $this->error('无相关信息！');
		if(!checked_admin(is_login()) || !checked_vip(is_login())) $this->error('权限不足，无法查看！');
		$this->assign('tailor_info',$tailor_info);
		$this->display();
	}
	public function show_contact($id){
		if(!$id) $this->error('参数错误!');
		$tailor_info = D('event_tailor')->where(array('id'=>$id,'siteid'=>SITEID,'status'=>1))->find();
		if(!$tailor_info) $this->error('无相关信息！');
		if(!checked_admin(is_login()) || !checked_vip(is_login())) $this->error('权限不足，无法查看！');
		$this->assign('tailor_info',$tailor_info);
		$this->display();
	}
	public function add_tailor_note($id){
		if(!$id) $this->error('参数错误!');
		$tailor_info = D('event_tailor')->where(array('id'=>$id,'siteid'=>SITEID,'status'=>1))->find();
		if(!$tailor_info) $this->error('未知的需求！');
		if(!checked_admin(is_login()) || !checked_vip(is_login())) $this->error('权限不足，无法添加！');
		$this->display();
	}
	/****************************************************************/
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
	public function deliver_goods($order_sn){
		$this->assign('order_sn',$order_sn);
		$this->display();
	}
	public function do_deliver($order_sn = 0,$express_com = 0,$express_num = 0,$express_desc=""){
		if(empty($express_com)) $this->error('请选择快递公司');
		if(empty($express_num)) $this->error('请输入快递单号');
		if(!is_numeric($express_num)) $this->error('快递单号必须为数字');
		$data['express_com'] = $express_com;
		$data['express_num'] = $express_num;
		$data['express_desc'] = $express_desc;
		$rs = D('shop_ordersn')->where(array('order_sn'=>$order_sn,'siteid'=>SITEID))->save($data);
		if($rs){
			$save['status'] = 22;
			D('shop_ordersn')->where(array('order_sn'=>$order_sn,'siteid'=>SITEID))->save($save);
			//reduce_goods_num($order_sn);
			$this->success('发货成功！',U('Websit/Order/index',array('status'=>3)));
		}else{
			$this->error('发货失败！',U('Websit/Order/index',array('status'=>3)));
		}
	}
	public function shop_order_editexpress($order_sn = 0){
		$rs = D('shop_ordersn')->where(array('order_sn'=>$order_sn,'siteid'=>SITEID))->find();
		$this->assign('rs',$rs);
		$this->display();
	}
	public function shop_order_doeditexpress($order_sn = 0,$express_desc=""){
		$data['express_desc'] = $express_desc;
		$rs = D('shop_ordersn')->where(array('order_sn'=>$order_sn,'siteid'=>SITEID))->save($data);
		if($rs){
			$this->success('修改成功',U('Websit/Order/shop_order_detail',array('order_sn'=>$order_sn)));
		}else{
			$this->error('未修改属性',U('Websit/Order/shop_order_detail',array('order_sn'=>$order_sn)));
		}
	}
	public function do_update_shop_status($id,$status){
		if(!$id) $this->error('参数错误！');
		$status = trim($status);
		$msg = update_shop_order_status($id,$status);
		if($msg['s'] == 1){
			exit(json_encode(array('status'=>$msg['s'],'m'=>$msg['m'])));
		}else{
			exit(json_encode(array('status'=>$msg['s'],'m'=>$msg['m'])));
		}
	}
	public function order_source($trade_sn='',$order_source=''){
		if(IS_POST){
			if(empty($order_source)){
				$this->error('请输入订单来源！');
			}else{
				$data['order_source'] = $order_source;
				$rs = D('event_attend')->where(array('trade_sn'=>$trade_sn,'siteid'=>SITEID))->save($data);
				if($rs){
					$this->success('修改成功！','refresh');
				}else{
					$this->error('修改失败！');
				}
			}
		}else{
			if(!$trade_sn) $this->error('参数错误！');
			$order_info = D('event_attend')->where(array('trade_sn'=>$trade_sn,'siteid'=>SITEID))->find();
			if(empty($order_info['order_source'])){
				$order_source = $_SERVER['HTTP_HOST'];
			}else{
				$order_source = $order_info['order_source'];
			}
			$this->assign('trade_sn',$trade_sn);
			$this->assign('order_source',$order_source);
			$this->display();
		}
	}
}  
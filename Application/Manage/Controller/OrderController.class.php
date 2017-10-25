<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;
use Think\Controller;

class OrderController extends BaseController
{
	protected $event_tailor;

	public function _initialize(){
   		
   		 parent::_initialize();
		$this->event_tailor=D('event_tailor');
	   
	}
	

	/***************************************************活动订单**********************************************************************/
	public function seekevent(){
		
		$data = $_GET;
    	unset($_GET['url']);
    	if($_GET['ord_type'] == 3 && $_GET['order_type_con'] != ''){ 
    		$_GET['order_type_con'] = urlsafe_b64encode($_GET['order_type_con']);
    	}
    	
    	if($_GET['ord_event'] == 3 && $_GET['ord_event_con'] != ''){
    		$_GET['ord_event_con'] = urlsafe_b64encode($_GET['ord_event_con']);
    	}

    	$url=U($data['url'],$_GET);
		header("Location:$url");
	}
	
	public function index(){

		$map['siteid'] = SITEID ;
		$ord_status = $_GET['ord_status'];
		$ord_type = $_GET['ord_type'];
		$order_type_con = $_GET['order_type_con'];
		$ord_event = $_GET['ord_event'];
		$ord_event_con = $_GET['ord_event_con'];
		if($ord_status){ 
			switch($ord_status){
				case 'inuse'://有效订单
					$map['status'] =  array('not in', '-1 ,0');
				break;
				case 'halfpay'://定金已支付
					$map['status'] = array(array('eq',11),array('eq',12), 'or');
					$map['pay_status'] = 1;
					$map['paytype'] = 1;
				break;
				case 'succ'://全额已支付
					$map['pay_status'] = 2;
					$map['status'] =  array('not in', '-1 ,0');
					
				break;
				case 'unpay'://未支付
					$map['status'] =  array('not in', '-1 ,0');
					$map['pay_status'] = 0;
				break;
				case 'all'://全部订单
				break;
			}
		}

		if($ord_type && $order_type_con != ''){ 
			switch ($ord_type) {
				case 1://所有订单
	
				break;
				case 2://订单编号

					$map['trade_sn'] = array('like','%'.$order_type_con.'%');
				break;
				case 3://订单联系人
					$order_type_con	=	urlsafe_b64decode($order_type_con);
				 	$map['contact_name'] = array('like','%'.$order_type_con.'%');
				break;
				case 4://联系人电话
					$map['contact_telephone'] = array('like','%'.$order_type_con.'%');
				break;
			
			}

		}

		if($ord_event && $ord_event_con != ''){ 
			switch ($ord_event) {
				
				case 1://所有活动
	
				break;
				case 2://活动排期
					if($ord_event_con != ''){ 
						$map['calendar_id'] = array('like','%'.$ord_event_con.'%');
					}
					
				break;
				case 3://活动名称
					if($ord_event_con != ''){ 
						$ord_event_con	=	urlsafe_b64decode($ord_event_con);
						$mapEvent['siteid'] = SITEID;
						//$mapEvent['status'] = array('egt',0);
						$mapEvent['title'] = array('like', '%' .$ord_event_con. '%');
						$eventall = D('event')->where($mapEvent)
								->field(array('id'))
								->select();
						foreach ($eventall as $k => $va) {
							$dateids[] = $va['id'];
						}
						$ids = implode(',', $dateids);
					 	$map['event_id'] = array('in',$ids);

					}
				
				break;
				
			}
		}
		
		

		 $count=D('event_attend')->where($map)->count();//总数
		 $Page  = new \Think\Page($count,10);// 
		 //$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");

		$show  = $Page->show(); 	
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
			$event_attend[$key]['travel_number'] = D('event_signer')->where($map)->count();
			
			$event_attend[$key]['cardid_info'] = D('pointcard')->field("amount,typename")->where(array('siteid'=>SITEID,'cardid'=>$v['cardid']))->find();

			if($event_attend[$key]['cardid'] != ''){ 
				$event_attend[$key]['cardid'] = cardinfo_num($event_attend[$key]['cardid']);
			}

			if(empty($v['order_source'])){
				$event_attend[$key]['order_source'] = $_SERVER['HTTP_HOST'];
			}else{
				$event_attend[$key]['order_source'] = $v['order_source'];
			}

			$event_attend[$key]['calendar_num'] = get_status_num($v['calendar_id']);
			$event_attend[$key]['sign_num'] = get_signnum($v['id']);
			
		}

			$this->assign('title','活动订单');
			$this->assign('dateinfo',$event_attend);
			$this->assign('page',$show);
			$this->display();	

	}

	/*查看订单的参加者*/
	public function order_allmember($order_id=0,$calendar_id=0){ 
		$map['order_id'] = $order_id;
		$map['calendar_id'] = $calendar_id;
		$map['siteid'] = SITEID;
		$user_info = D('event_signer')->where($map)->order('id desc')->select();
		foreach($user_info as $ke => $va){		
			$userinfo[] = json_decode($va['user_info'],true);
		}

		$this->assign('dateinfo',$userinfo);
		$this->display();

	}

	/*查看排期所有的活动参加者*/
	public function event_allmember($id=0,$eventid=0){	
		$map = "calendar_id = $id and siteid = ".SITEID." and event_id = $eventid and status > 0 and order_status != -1 and order_status != 0";
		$event_attend = D('event_signer')->where($map)->order('id desc')->select();	
		foreach($event_attend as $key => $val){		
			$arr_info[$key] = json_decode($val['user_info'],true);
			$arr_info[$key]['order_status'] = $val['order_status'];
			$allmap['id'] = $val['order_id'];
			$allmap['siteid'] = SITEID;
			$data = D('event_attend')->field('contact_name,uid')->where($allmap)->find();
			$arr_info[$key]['member_user'] = $data['contact_name'];
			$arr_info[$key]['member_nickname'] = query_user('nickname',$data['uid']);
			$arr_info[$key]['member_uid'] = $data['uid'];
		}
        $this->assign('arr_info',$arr_info);
        $this->display();
    }

	
	/*订单详情*/
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


			$card_info['name'] = $card_info['typename'];		
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

	/*活动人数数据导入excel文件*/
	public function use_excel($id,$eventid){  
		$map = "calendar_id = $id and siteid = ".SITEID." and event_id = $eventid and status > 0 and order_status != -1 and order_status != 0";
		$event_attend = D('event_signer')->where($map)->order('id desc')->select();
		foreach($event_attend as $key => $val){
			$arr_info[$key] = json_decode($val['user_info'],true); 
			$arr_info[$key]['order_status'] = $val['order_status'];
			$arr_info[$key]['sex'] = ($arr_info[$key]['sex']==1)?'男':'女';
			if($arr_info[$key]['hand'] == 1){ 
				$arr_info[$key]['hand'] = '左手';
			}elseif($arr_info[$key]['hand'] == 2){ 
				$arr_info[$key]['hand'] = '右手';
			}
		}
	
		$Model = new \Think\Model();
		$result = $Model->table(array('thinkox_event_calendar_time'=>'tct','thinkox_event'=>'te'))
						->where("tct.siteid = te.siteid and tct.siteid = ".SITEID." and tct.eventid= te.id and tct.id = $id")
						->field("tct.starttime,te.title")
						->find();
					
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
		$objActSheet->setCellValue ( 'B1', '性别');
		$objActSheet->setCellValue ( 'C1', '身份证');
		$objActSheet->setCellValue ( 'D1', '电话');
		$objActSheet->setCellValue ( 'E1', '邮箱');
		$objActSheet->setCellValue ( 'F1', 'QQ');
		$objActSheet->setCellValue ( 'G1', '社会角色');
		$objActSheet->setCellValue ( 'H1', '紧急人姓名');
		$objActSheet->setCellValue ( 'I1', '紧急人联系方式');
		$objActSheet->setCellValue ( 'J1', '惯用手');
		$objActSheet->setCellValue ( 'K1', '年龄');	
		$objActSheet->setCellValue ( 'L1', '过敏史');	
		$objActSheet->setCellValue ( 'M1', '订单状态');	
		$objActSheet->getColumnDimension('A')->setWidth(20);//改变此处设置的长度数值
		$objActSheet->getColumnDimension('B')->setWidth(10);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('C')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('D')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('G')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('E')->setWidth(25);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('F')->setWidth(15);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('H')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('I')->setWidth(20);//改变此处设置的长度数值
		$objActSheet->getColumnDimension('J')->setWidth(10);//改变此处设置的长度数值
		$objActSheet->getColumnDimension('K')->setWidth(20);//改变此处设置的长度数值
		$objActSheet->getColumnDimension('L')->setWidth(20);//改变此处设置的长度数值
		$objActSheet->getColumnDimension('M')->setWidth(20);//改变此处设置的长度数值

		foreach($arr_info as $key => $val){	
				$key = $key + 2;
				$objActSheet->setCellValue("A{$key}",$val['realname']);  
				$objActSheet->setCellValue("B{$key}",$val['sex']); 
				$objActSheet->setCellValue("C{$key}",chunk_split($val['card']),4," ");           
				$objActSheet->setCellValue("D{$key}",$val['telephone']);         
				$objActSheet->setCellValue("E{$key}",$val['email']);
				$objActSheet->setCellValue("F{$key}",$val['qq']);
				$objActSheet->setCellValue("G{$key}",get_role($val['role']));
				$objActSheet->setCellValue("H{$key}",$val['emergencycontact']);
				$objActSheet->setCellValue("I{$key}",$val['emergencyphone']);
				$objActSheet->setCellValue("J{$key}",$val['hand']);
				$objActSheet->setCellValue("K{$key}",$val['age']);  
				$objActSheet->setCellValue("L{$key}",$val['allergies']);	
				$objActSheet->setCellValue("M{$key}",strip_tags(get_event_order_status($val['order_status'])));

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
		
	}
	/***********************************************************************/

	/*短信通知*/
	public function sms_notice($id = '',$eventid = ''){
		if(!$id || !$eventid) $this->error('参数错误！');
		$event = D('event')->where(array('id'=>$eventid,'siteid'=>SITEID))->find();
		$calendar = D('event_calendar_time')->where(array('siteid'=>SITEID,'id'=>$id))->find();
		$map = "calendar_id = {$id} and event_id = {$eventid} and siteid = ".SITEID." and status > 0 and order_status != -1 and order_status != 0";
		$status_arr = D('event_signer')->where($map)->group('order_status')->Field('order_status')->select();
		foreach($status_arr as $key => $val){
			$status_arr[$key]['text'] = get_event_order_status($val['order_status']);
		}
		
		$this->assign('status_arr',$status_arr);
		$this->assign('event',$event);
		$this->assign('calendar',$calendar);
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
			$tel[$key]=$val['user_info']['telephone'];
			$this->do_add_smslog($val,0,$notice);
		}
		$smsdata=array( 
			'content'=>$notice,
			'title' =>'活动短信通知',
			);
		$tels=array_unique($tel);
		foreach ($tels as $key => $val) {
			$contactways[]=$val;
		}
		$login_arr = query_user(array('id','mobile'), is_login());
		$contactways[]=$login_arr['mobile'];
		D('Message')->addSendMessage('send_sms_to_user',$contactways,$smsdata,0,1);
		$this->success('短信已批量发送！',U('Manage/Order/sms_backinfo',array('calendar_id'=>$id,'event_id'=>$eventid)));
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
		$sqltime=time()-600;
		$map = "calendar_id = $calendar_id and event_id = $event_id and uid = ".is_login()." and siteid = ".SITEID." and create_time > ".$sqltime;
		$sql = "select * from thinkox_event_sms_log where $map ";
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
		$login_arr = query_user(array('id','nickname','mobile'), is_login());
		if($succ_arr){ 
			$login_arr['msg']='发送成功';
		}else{ 
			$login_arr['msg']='发送失败';
		}
		$tid = count($sms_arr);
		$sid = count($succ_arr);
		$eid = count($err_arr);
		$this->assign('login_arr',$login_arr);
		$this->assign('tid',$tid);
		$this->assign('sid',$sid);
		$this->assign('eid',$eid);
		$this->assign('contents',$sms_arr);
		$this->display();
	}

	/*订单的取消*/
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
    /*************************************************************活动订单**********************************************************************/


    /**商城订单**/
    public function shop(){ 
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
			$seek	=	urlsafe_b64decode($seek);
			$where['order_sn']  = array('like','%'.$seek.'%');
			$where['consignee_name']  = array('like','%'.$seek.'%');
			$where['phone']  = array('like','%'.$seek.'%');
			$where['_logic'] = 'or';
			$map['_complex'] = $where;
		}

		$map['_string'] = 'siteid='.SITEID.' OR supplier_id='.SITEID;
		
		$count=D('shop_ordersn')->where($map)->count();//总数
		$Page  = new \Think\Page($count,10);// 
		$show  = $Page->show();// 
		$shop_order_arr = D('shop_ordersn')->where($map)->order("create_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($shop_order_arr as $key => $value){
			$shop_order_arr[$key]['nickname'] = query_user('nickname',$shop_order_arr[$key]['uid']);
		}
		$this->assign('siteid',SITEID);
		$this->assign('title','商城订单');
		$this->assign('shop_arr',$shop_order_arr);
		$this->assign('page',$show);
		$this->display();
    }

	public function seek($seek){
		$seek = op_t(I('seek'));
		if($seek!=''){
			$seek	=	urlsafe_b64encode($seek);
		$url=U('Manage/Order/shop',array('seek'=>$seek));
			header("Location:$url");
		}else{
			$this->error('请填写查询内容');
		}
	}
	
   	public function do_update_shop_status($id,$status){
		if(!$id) $this->error('参数错误！');
		$status = trim($status);
		$msg = update_shop_order_status($id,$status);
		if($msg['s'] == 1){
			$this->success('订单取消成功');
		}else{
			$this->error($msg['m']);
		}
	}
	public function do_update_success_status($id,$status){
		if(!$id) $this->error('参数错误！');
		$status = trim($status);
		$msg = update_shop_order_status($id,$status);
		if($msg['s'] == 1){
			$this->success('已确认收货');
		}else{
			$this->error($msg['m']);
		}
	}

	public function deliver_goods($order_sn){
		$this->assign('order_sn',$order_sn);
		$this->display();
	}
	
	/*查看商城订单详情*/
	public function shop_order_detail($order_sn){

		$shop_order_detail	=	D('Common/Shop')->shop_order_detail($order_sn,'admin');
		$shop_order_detail['nickname'] = query_user('nickname',$shop_order_detail['uid']);
		/*************退款信息********************/
		$refund_list	=	D('Common/Shop')->shop_order_refund($order_sn);
		
		$this->assign('siteid',SITEID);
		$this->assign('refund_list',$refund_list);
		$this->assign('goods_list',$shop_order_detail['goods_list']);
		$this->assign('begincity',$shop_order_detail['begincity']);
		$this->assign('order_info',$shop_order_detail);
		$this->display();
	}

	//更改订单总价
	public function shop_order_editmoney(){
		$order_sn=$_GET['order_sn'];
		$alltotalprice = D('shop_ordersn')->where(array('order_sn'=>$order_sn,'siteid'=>SITEID))->find();
		$this->assign('alltotalprice',$alltotalprice);
		$this->display();
	}

	//更改订单总价
	public function shop_order_doeditmoney(){
		$order_sn=$_POST['order_sn'];
		$shoporder = D('shop_ordersn')->where(array('order_sn'=>$order_sn))->field('status,alltotalprice')->find();
		if($shoporder['status']==20){
			if(!is_numeric($_POST['real_amount']) || $_POST['real_amount']<=0 ){
					$this->error('请填写正确的价格!');
				}
				if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $_POST['real_amount'])) {  
					$this->error('价格最多可保留两位小数点');  
				}
			$order['alltotalprice']=$_POST['real_amount'];
			$res = D('shop_ordersn')->where(array('order_sn'=>$order_sn))->save($order);
			if($res){
				add_shop_order_log($order_sn,is_login(),'从'.$shoporder['alltotalprice'].'修改到'.$_POST['real_amount'],'修改价格',time());
				$this->success('更改成功',U('Manage/Order/shop_order_detail',array('order_sn'=>$order_sn)));
			}
		}else{
			$this->error('订单状态错误');
		}

	}
	//修改退款申请
	public function shop_order_refund_updata($id=''){
		/*****需要加判定，没想好************************************/
		$status=D('shop_order_refund')->where('id='.$id)->getField('refund_status');
		if($status==1){
			$refund['refund_status']=2;
			$updata_refund	=	D('shop_order_refund')->where('id='.$id)->save($refund);
			if($updata_refund){
				echo json_encode(array('status'=>1));
			}
		}else{
			$this->error('状态错误');
		}
	}
	//修改退款申请
	public function shop_order_refund_edit($order_sn='',$id=''){
			/*****需要加判定，没想好************************************/
		if(IS_POST){
			$id			=	$_POST['id'];
			$order_sn	=	$_POST['order_sn'];
			$refund_status	=	$_POST['refund_status'];
			$refund_price	=	$_POST['refund_price'];
			$approval_comments	=	op_t(trim($_POST['approval_comments']));
			$order_status	=	D('shop_ordersn')->where(array('order_sn'=>$order_sn))->getField('status');
			if($order_status!=60){
				$this->error('订单状态错误',U('Manage/Order/shop_order_detail',array('order_sn'=>$order_sn)));
			}
			switch ($refund_status){
				case 11;
					if(!$refund_price){
						$this->error('请填写退款金额');
					}
				break;
				case -1;
					$refund_price	=	0;
				break;
				default:
					$this->error('请选择是否同意退款');
			}
			if($approval_comments==''){
				$this->error('请填写店主回复');
			}
			$info_id	=	D('shop_order_refund')->where(array('order_sn'=>$order_sn,'id'=>$id))->getField('shop_order_info_id');
			$goods = D('shop_order_info')->where(array('order_sn'=>$order_sn,'id'=>$info_id))->find();
			$goods_refund = D('shop_order_refund')->where(array('order_sn'=>$order_sn,'shop_order_info_id'=>$info_id))->select();
			foreach($goods_refund as $key=>$value){
				$has_refund_num	=	$has_refund_num+$value['refund_num'];
			}
			if(!$goods){
				$this->error('订单信息错误');
			}else{
				if($refund_num>$goods['goods_num']-$has_refund_num){
					$this->error('选择的数量超出已选购商品'.$goods['goods_num']);
				}else{
					$refund['order_sn']	=	$order_sn;
					$refund['approval_time']	=	time();
					$refund['refund_status']=	$refund_status;
					$refund['approval_comments']	=	$approval_comments;
					$refund['id']	=	$id;
					$refund['refund_price']=	$refund_price;
					$updata_refund	=	D('shop_order_refund')->save($refund);
					if($updata_refund){
						$refund_count	=	D('shop_order_refund')->where('order_sn='.$order_sn.' and refund_status>0 and refund_status<10')->count();
						if($refund_count==0){
							
							$updata_order['status']		=	22;
							$a=D('shop_ordersn')->where('order_sn="'.$order_sn.'"')->save($updata_order);
						}
						add_shop_order_log($order_sn,is_login(),'','审批退款',time());
						$this->success('审批成功',U('Manage/Order/shop_order_detail',array('order_sn'=>$order_sn)));
					}else{
						$this->error('审批失败',U('Manage/Order/shop_order_detail',array('order_sn'=>$order_sn)));
					}
				}
			}
		}else{
			$goods_refund = D('shop_order_refund')->where('order_sn="'.$order_sn.'" and id='.$id)->find();
		//	dump($goods_refund);
			$goods = D('shop_order_info')->where(array('order_sn'=>$order_sn,'id'=>$goods_refund['shop_order_info_id']))->find();
			$goods_refund_list = D('shop_order_refund')->where(array('order_sn'=>$order_sn,'shop_order_info_id'=>$goods_refund['shop_order_info_id']/*,'siteid'=>SITEID*/))->select();
			foreach($goods_refund_list as $key=>$value){
				$has_refund_num	=	$has_refund_num+$value['refund_num'];
			}
			$goods['goods_num']	=	$goods['goods_num']-$has_refund_num+$goods_refund['refund_num'];;
			$info_id	=	D('shop_order_refund')->where(array('order_sn'=>$order_sn,'id'=>$id))->getField('shop_order_info_id');
			$refund_reason=	$_POST['refund_reason'];
			$this->assign('goods',$goods);
			$this->assign('goods_refund',$goods_refund);
			$this->display();
		}
	}

	public function do_deliver($order_sn = 0,$express_com = 0,$express_num = 0,$express_desc=""){
		if(empty($express_com)) $this->error('请选择快递公司');
		if(empty($express_num)) $this->error('请输入快递单号');
		if(!is_numeric($express_num)) $this->error('快递单号必须为数字');
		$data['express_com'] = $express_com;
		$data['express_num'] = $express_num;
		$data['express_desc'] = $express_desc;
		/*************新增判定******************/
		$order	=	D('shop_ordersn')->where(array('order_sn'=>$order_sn))->find();
		if($order['supplier_id']==""){
			if($order['siteid']!=SITEID){ $this->error('非法操作');}
		}else{
			if($order['supplier_id']!=SITEID){ $this->error('非法操作');}
		}
		/*************新增判定结束******************/
		$rs = D('shop_ordersn')->where(array('order_sn'=>$order_sn))->save($data);
		if($rs){
			$save['status'] = 22;
			D('shop_ordersn')->where(array('order_sn'=>$order_sn))->save($save);
			add_shop_order_log($order_sn,is_login(),'','确认发货',time());
			$shopstuatus_data=array(
                'id'  => $order['id'],
                'shop_order_sn'  =>$order_sn,
                'execute_time'   =>time()+604800,
                'change_status'  =>$save['status'],
                );
            D('Message')->addSendMessage('auto_change_shop_status','',$shopstuatus_data,0,1); 
			//reduce_goods_num($order_sn);
			$this->success('发货成功！',U('Manage/Order/shop'));
		}else{
			$this->error('发货失败！',U('Manage/Order/shop'));
		}
	}

	public function shop_order_editexpress($order_sn = 0){
		$rs = D('shop_ordersn')->where(array('order_sn'=>$order_sn))->find();
		$this->assign('rs',$rs);
		$this->display();
	}
	public function shop_order_doeditexpress($order_sn = 0,$express_desc=""){
		$data['express_desc'] = $express_desc;
		$rs = D('shop_ordersn')->where(array('order_sn'=>$order_sn))->save($data);
		if($rs){
			$this->success('修改成功',U('Manage/Order/shop_order_detail',array('order_sn'=>$order_sn)));
		}else{
			$this->error('未修改属性',U('Manage/Order/shop_order_detail',array('order_sn'=>$order_sn)));
		}
	}

	  /*订单来源修改*/
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
	
	
/************************************************定制活动订单 备注******************************************************************/
	public function custom(){ 
		
		$map=array('status' => array('egt',0),'siteid'=>SITEID);
	
        $list = D('event_tailor')->field('id,trade_sn,minprice,maxprice,childnum,totalnum,minage,maxage,desty_type,desty,earlytime,latetime,traveldays,status')->where($map)->order('id desc')->select();
        foreach($list as $key => $val){
			$list[$key]['tailor_note'] = D('event_tailor_note')->where(array('tailor_id'=>$val['id'],'siteid'=>SITEID,'status'=>1))->find();
			$list[$key]['disearlytime'] = date("Y-m-d ",$val['earlytime']);
			$list[$key]['dislatetime'] = date("Y-m-d ",$val['latetime']);
		}
		
		$this->assign('dateinfo',$list);
		$this->display();
	}

	/*添加定制活动备注*/
    public function add_tailor_note($tailor_id=0, $content=''){ 
    	if(IS_POST){ 
    		$content=op_t($content);
    		$tailor_id=op_t($tailor_id);
    		if(!$tailor_id) $this->error('参数错误！');
			if(!checked_admin(is_login()) || !checked_vip(is_login())) $this->error('权限不足，无法添加！');
			if(!$content) $this->error('备注内容不能为空！');
			$data['siteid']=SITEID;
			$data['uid'] = is_login();
			$data['create_time'] = time();
			$data['tailor_id'] = $tailor_id;
			$data['content'] = $content;
			$data['status'] = 1;
			D('event_tailor_note')->create($data);
			$res=D('event_tailor_note')->add($data);
			if($res){ 
				$this->success('添加成功',U('Order/custom'));
			}else{ 
				$this->error('添加失败');
			}

    	}	     	
       
       $this->display();

    }
	
    /*备注列表*/
    public function show_note($id=0){ 
    	if(!$id){ 
    		$this->error('参数错误');
    	}
    	
		if(!checked_admin(is_login()) || !checked_vip(is_login())) $this->error('权限不足，无法查看！');
		$map = "tailor_id = $id and siteid = ".SITEID." and status = 1";
		$data=D('event_tailor_note')->where($map)->select();
		foreach($data as $key=>$val){ 
			$new_data[$key]=$val;
			$users= query_user(array('id','nickname'),$val['uid']);
			$new_data[$key]['nickname']=$users['nickname'];
		}
		
		$this->assign('page',$show);
		$this->assign('tailor_note',$new_data);
		$this->display();

    }
	

  
 	public function custom_detail($id){
 		
 		$map['id'] = $id;
 		$map['status']= array('egt',0);
		$map['siteid'] = SITEID;
 		$list = D('event_tailor')->field('id,tag,other,contact_name,contact_telephone,contact_email,reference,sex')->where($map)->find();
 		$list['sex'] = $list['sex']?'先生':'女士';
 		$this->assign('dateinfo',$list);
		$this->display();
	   
	}	

}  
		
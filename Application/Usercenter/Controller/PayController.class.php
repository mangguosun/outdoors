<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-6-27
 * Time: 下午1:54
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace Usercenter\Controller;


use Think\Controller;


class PayController extends BaseController
{
	protected $userdata;
    public function _initialize()
    {
        parent::_initialize();
        if (!is_login()) {
            //$this->error('请登录后再访问本页面。');
			$this->redirect('Home/User/login');
        }
		$this->userdata = query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $uid);
        $this->mTalkModel = D('Talk');
        $this->setTitle('个人中心');
		
		$this->handle = D('Paydeposit');
		
		/******************pay status********************/


    }

    public function index($uid = null, $page = 1, $count = 10)
    {
        $map['uid'] = is_login();
	    $map['siteid'] = SITEID;
		$count = D('pay_account')->where($map)->count();
		$Page       = new \Think\Page($count,10);
		$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
		$show       = $Page->show();// 分页显示输出  
		
		$infos = D('pay_account')->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		
		if (is_array($infos) && !empty($infos)) {
			foreach($infos as $key=>&$info) {
				
			}
		}
              
		$this->assign('pay_account',$infos);
		$this->assign('user', $this->userdata);
		$this->assign('page',$show);
		$this->display();
    }

	/*
	*$trade_sn  活动订单号
	*/
    public function pay($trade_sn)
    {
		if(!$trade_sn) $this->error('参数错误！');
		$datas['status'] = $status;
		$check = D('event_attend')->where(array('trade_sn' => $trade_sn,'siteid'=>SITEID))->find();
		if (!$check) {
			$this->error('订单不存在！');
		}else{
			if($check['pay_status']==2){
				$this->error('本订单已经支付过了，请到详情页查看',U('Usercenter/Eventorder/myevent_detail',array('trade_sn'=>$check['trade_sn'])));
			}
			$ispay = check_event_order_ispay($check['status']);
			if($ispay){
				$this->error($ispay,U('Usercenter/Eventorder/myevent_detail',array('trade_sn'=>$check['trade_sn'])));
			}

		}

		if($check['cardid']  != ''){ 
			$check['cardid'] = explode(',', $check['cardid']);
			$check_num = count($check['cardid']); 
			for($i=0;$i<$check_num;$i++){ 
				$card_info = D('pointcard')->where(array('cardid'=>$check['cardid'][$i],'siteid'=>SITEID))->find();
				$amount += $card_info['amount'];
				$check['cardinfo'][$i]['cardid'] = $check['cardid'][$i];
				$check['cardinfo'][$i]['typename'] = $card_info['typename'];
				$check['cardinfo'][$i]['amount'] = $card_info['amount'];
			}
			$check['card_amount'] = $amount;
		}

		
		$event_content = D('event')->where(array('sitid'=>SITEID, 'id' => $check['event_id']))->find();
        if (!$event_content) {
            $this->error('活动不存在！');
        }
		
		$calendar = D('event_calendar_time')->where(array('sitid'=>SITEID, 'id' => $check['calendar_id']))->find();
        if (!$calendar) {
            $this->error('排期不存在！');
        }
		
		$event_signer = D('event_signer')->where(array('sitid'=>SITEID, 'order_id' => $check['id'], 'status' => 1))->select();
		$total_num = count($event_signer);
		if($event_signer){
			foreach($event_signer as $key=>&$info) {
				
				$contacts_info[$key] = json_decode($info['user_info'],true);
				$insurance_info = json_decode($info['insurance_info'],true);
				if($info['insurance_id']){
					$contacts_info[$key]['insurance_name'] = $insurance_info['name'];
					$contacts_info[$key]['insurance_sum_insured'] = $insurance_info['sum_insured'];
					$contacts_info[$key]['insurance_price'] = $insurance_info['price'];
					$contacts_info[$key]['insurance_policy_number'] = $insurance_info['policy_number'];
				}
			}
				
		}
		$pay_types = D('Paydeposit')->get_paytype();
		/*$financial_order = D('pay_account')->where(array('sitid'=>SITEID, 'order_id' => $check['trade_sn'], 'order_type' => 1, 'uid' => is_login()))->find();
		if($financial_order){
			$factory_info = D('Paydeposit')->get_record($financial_order['trade_sn']);
		    if(!$factory_info) $this->error('订单已完成支付或已经取消支付');
		}*/
		//银行汇款
		$this->assign('pay_types',$pay_types);
		$this->assign('total_num',$total_num);
		$this->assign('contacts_info',$contacts_info);
		$this->assign('event_content',$event_content);
		$this->assign('card_info',$card_info);
		$this->assign('calendar_content',$calendar);
 		$this->assign('event_attend',$check);
		$this->assign('user', $this->userdata);
        $this->display();
		
		
    }
	
	
    public function do_pay()
    {
		
		if(IS_POST){
			$order_id = op_t($_POST['order_id']);
			$pay_id = $_POST['pay_type'];
			if(!$pay_id) $this->error('请选择支付方式');
			if(!$order_id) $this->error('订单参数出错，请重试');
			
			
			$event_attend = D('event_attend')->where(array('trade_sn' => $order_id,'siteid'=>SITEID))->find();
			if (!$event_attend) {
				$this->error('订单不存在！');
			}else{
				if($event_attend['pay_status']==2){
					$this->error('本订单已经支付过了，请到详情页查看',U('Usercenter/Eventorder/myevent_detail',array('trade_sn'=>$check['trade_sn'])));
				}
				$ispay = check_event_order_ispay($event_attend['status']);
				if($ispay){
					$this->error($ispay);
				}
			}
			$event_content = D('event')->where(array('sitid'=>SITEID, 'id' => $event_attend['event_id']))->find();
			if (!$event_content) {
				$this->error('活动不存在！');
			}else{
				$iserrormsg = check_event($event_content['status']);
				if($iserrormsg){
					$this->error($iserrormsg);
				}
			}
			$calendar_content = D('event_calendar_time')->where(array('sitid'=>SITEID, 'id' => $event_attend['calendar_id']))->find();
			if (!$calendar_content) {
				$this->error('排期不存在！');
			}else{
				$iserrormsg = check_event_scheduling($calendar_content);
				if($iserrormsg){
					$this->error($iserrormsg);
				}
			}
			$user_info = $this->userdata;
		
			$trade_sn = create_sn();//生成流水号
			$payment = D('Paydeposit')->get_payment($pay_id);//获取充值方试
			if($payment['is_online'] === '1') {
				$surplus_data['status']  = 'unpay';
				$surplus_data['pay_type']  = 'recharge';
			} else {
				$surplus_data['status']  = 'waitting';
				$surplus_data['pay_type']  = 'offline';
			}
			
			if($event_attend['paytype'] ==0){
				$payprice = $event_attend['totalprice'];
				$order_paytype = 2;
			}elseif($event_attend['paytype'] ==1){
				if($event_attend['pay_status'] ==0){
					$payprice = $event_attend['payprice'];
					$order_paytype = 0;
				}elseif($event_attend['pay_status'] ==1){
					$payprice = $event_attend['totalprice'] - $event_attend['payprice'];
					$order_paytype = 1;
				}
			}
			$usernote =  $event_content['title'].'【'.$calendar_content['starttime'].'~'.$calendar_content['overtime'].'_活动单号:'.$event_attend['trade_sn'].'】';
			$surplus = array(
					'uid'      => is_login(),
					'username'    => $event_attend['contact_name'],
					'siteid'    => SITEID,
					'money'       => trim(floatval($payprice)),
					'order_paytype'       => trim(floatval($order_paytype)),
					'quantity'    => $_POST['quantity'] ? trim(intval($_POST['quantity'])) : 1,
					'telephone'   => $event_attend['contact_telephone'] ? trim($event_attend['contact_telephone']) : '',
					'contactname' => '【订单号:'.$event_attend['trade_sn'].'】'.$calendar_content['starttime'].'出发',
					'email'       => $event_attend['contact_email'],
					'addtime'	  => time(),
					'pay_type'	  => $surplus_data['pay_type'],
					'status'	  => $surplus_data['status'],
					'pay_id'      => $payment['pay_id'],		
					'payment'     => trim($payment['pay_name']),
					'usernote'    => $usernote,
					'trade_sn'	  => $trade_sn,
					'order_type'	  => 1,
					'order_id'	  => $order_id,	
			);
			
			$result = D('Paydeposit')->set_record($surplus);//得到支付流水记录ID
			if($result){
				$updata_event_attend['in_trade_sn'] = $trade_sn;
				D('event_attend')->where(array('trade_sn'=>$order_id))->save($updata_event_attend);
				D('RecordContent')->setuseprice_account($trade_sn);//流水号的余额
				redirect(U('Usercenter/Pay/pay_redirect',array('trade_sn'=>$trade_sn)));
			} else {
				$this->error('操作失败',U('Usercenter/Pay/pay_redirect',array('trade_sn'=>$order_id)));
			}
	
		}else{
			
			
			
		}
    }



	 public function pay_redirect($trade_sn)
	{
			//$trade_sn = I('trade_sn');
			$factory_info = D('Paydeposit')->get_record($trade_sn);
			if(!$factory_info) $this->error('订单已完成支付或已经取消支付');
			

			$payment = D('Paydeposit')->get_payment($factory_info['pay_id']);

			$cfg = unserialize_config($payment['config']);
			$pay_name = ucwords($payment['pay_code']);

			$pay_fee = pay_fee($factory_info['money'],$payment['pay_fee'],$payment['pay_method']);
			$logistics_fee = $factory_info['logistics_fee'];
			$discount = $factory_info['discount'];

			// calculate amount
			$factory_info['price'] = $factory_info['money'] + $pay_fee + $logistics_fee + $discount;
			
			// add order info
			$order_info['id']	= $factory_info['trade_sn'];
			$order_info['quantity']	= $factory_info['quantity'];
			$order_info['buyer_email']	= $factory_info['email'];
			$order_info['order_time']	= $factory_info['addtime'];
			
			//add product info
			$product_info['name'] = $factory_info['contactname'];
			$product_info['body'] = $factory_info['usernote'];
			$product_info['price'] = $factory_info['price'];
			
			//add set_customerinfo
			$customerinfo['telephone'] = $factory_info['telephone'];
			if($payment['is_online'] === '1') {
				$payment_handler = new \Pay\Payfactory($pay_name, $cfg);
				$payment_handler->set_productinfo($product_info)->set_orderinfo($order_info)->set_customerinfo($customer_info);
				$code = $payment_handler->get_code('value="进入支付页面" class="btn btn-primary"');	
				$this->assign('code', $code);
			} else {
				$financial_bank =  D('financial')->where(array('siteid'=>SITEID,'status'=>1))->select();
				$this->assign('financial_bank', $financial_bank);
			}
			$this->assign('factory_info', $factory_info);
			$this->display();
	}
	
	
    public function pay_popup()
    {
              
               
		if(IS_POST){
		       
			$trade_sn = $_POST['trade_sn'];//支付流水号
			if(!$trade_sn) $this->error('订单号错误');
			$financial_order = D('pay_account')->where(array('sitid'=>SITEID, 'trade_sn' => $trade_sn))->find();
			if($financial_order['status'] == 'succ'){
				$this->success('订单支付成功，跳转到订单页面',U('Usercenter/Eventorder/myevent_detail',array('trade_sn'=>$financial_order['order_id'])));
			}else{
				$this->error('支付还未完成请重新支付',U('Usercenter/Pay/pay',array('trade_sn'=>$financial_order['order_id'])));
			}
		}else{
			$id = $_GET['id'];
			$event_attend = D('event_attend')->where(array('id' => $id,'siteid'=>SITEID))->find();
			$financial_order = D('pay_account')->where(array('sitid'=>SITEID, 'trade_sn' => $event_attend['in_trade_sn']))->find();
		   
		    $this->assign('order_info', $financial_order);
			$this->display();
			
		}
		
    }	
	
	
	

    private function userInfo($uid = null)
    {
        $user_info = query_user(array('avatar128', 'nickname', 'uid', 'space_url', 'icons_html', 'score', 'title', 'fans', 'following', 'weibocount', 'rank_link', 'signature'), $uid);
        $this->assign('user_info', $user_info);
        return $user_info;
    }


  

} 
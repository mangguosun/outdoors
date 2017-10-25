<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM1:13
 */
 
namespace Usercenter\Controller;
set_time_limit(0);
use Think\Controller;

class ShoporderController extends BaseController
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

    public function index()
    {	
		if(I('status')!=''){
			$map['pay_status']=I('status');
			}
			$order_sn = op_t(I('trade_sn'));
			
			//---订单号---
			if($order_sn!=''){
			  $map['order_sn']=$order_sn;
			}
			$map['uid'] 	=	 is_login();
			$map['siteid']	=	SITEID;
		
		$count=D('shop_ordersn')->where($map)->count();//总数
		$Page  = new \Think\Page($count,10);// 
		$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
		$show  = $Page->show();// 
		$shop_order_arr = D('shop_ordersn')->where($map)->order("create_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		$tab=1;
		$this->assign('user', $this->userdata);
		$this->assign('shop_arr',$shop_order_arr);
		$this->assign('tab',$tab);
		$this->assign('page',$show);
        $this->display();
		
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
	/*查看商城订单详情*/
	public function shop_order_detail($order_sn){
		$shop_order_detail	=	D('Common/Shop')->shop_order_detail($order_sn,'user');
		if(!$shop_order_detail){
			$this->error('订单不存在');
		}
		$refund	=	D('shop_order_refund')->where('order_sn="'.$order_sn.'" and refund_status=11')->find();
		if($refund){
			$refund_num	=	D('shop_order_refund')->where('order_sn="'.$order_sn.'" and refund_status=11')->sum('refund_num');
			$refund_price	=	D('shop_order_refund')->where('order_sn="'.$order_sn.'" and refund_status=11')->sum('refund_price');
			$this->assign('refund', $refund);
			$this->assign('refund_num', $refund_num);
			$this->assign('refund_price', $refund_price);
		}
		
		$this->assign('user', $this->userdata);
		$this->assign('goods_list',$shop_order_detail['goods_list']);
		$this->assign('begincity',$shop_order_detail['begincity']);
		$this->assign('order_info',$shop_order_detail);
		$this->display();
	}
	/*订单退货信息*/
	public function shop_order_refund($order_sn){
	
		$shop_order_detail	=	D('Common/Shop')->shop_order_detail($order_sn,'user');
		if(!$shop_order_detail){
			$this->error('订单不存在');
		}
		/*************退款信息********************/
		$refund_list	=	D('Common/Shop')->shop_order_refund($order_sn);
		
		$this->assign('user', $this->userdata);
		$this->assign('goods_list',$shop_order_detail['goods_list']);
		$this->assign('begincity',$shop_order_detail['begincity']);
		$this->assign('order_info',$shop_order_detail);
		$this->assign('refund_list',$refund_list);
		$this->display();
	}
	//增加退款申请
	public function shop_order_refund_detail($order_sn='',$id=''){
		if(IS_POST){
			$id			=	$_POST['id'];
			$order_sn	=	$_POST['order_sn'];
			$refund_num	=	$_POST['refund_num'];
			$reason_select	=	$_POST['reason_select'];
			$refund_reason=	op_t(trim($_POST['refund_reason']));
			if($refund_num==0){
				$this->error('请选择正确数量');
			}
			if($reason_select==0){
				$this->error('请选择退款原因');
			}
			if($refund_reason==''){
				$this->error('请填写详细说明');
			}
			$goods = D('shop_order_info')->where(array('order_sn'=>$order_sn,'id'=>$id,'siteid'=>SITEID))->find();
			$goods_refund = D('shop_order_refund')->where(array('order_sn'=>$order_sn,'shop_order_info_id'=>$id,'siteid'=>SITEID))->select();
			$order_status	=	D('shop_ordersn')->where(array('order_sn'=>$order_sn))->getField('status');
			if($order_status==33){
				$this->error('订单状态错误',U('Usercenter/Shoporder/shop_order_refund',array('order_sn'=>$order_sn)));
			}
			foreach($goods_refund as $key=>$value){
				$has_refund_num	=	$has_refund_num+$value['refund_num'];
			}
			if(!$goods){
				$this->error('订单信息错误');
			}else{
				if($refund_num>$goods['goods_num']-$has_refund_num){
					$this->error('选择的数量超出已选购商品'.$goods['goods_num']);
				}else{
					$supplier_id	=	D('shop_ordersn')->where(array('order_sn'=>$order_sn))->getField('supplier_id');
					$refund['uid']	=	is_login();
					$refund['siteid']	=	SITEID;
					$refund['supplier_id']	=	$supplier_id;
					$refund['order_sn']	=	$order_sn;
					$refund['shop_order_info_id']	=	$id;
					$refund['refund_status']	=	1;
					$refund['create_time']	=	time();
					$refund['refund_num']	=	$refund_num;
					$refund['reason_select']=	$reason_select;
					$refund['refund_reason']	=	$refund_reason;
					if(!$refund['refund_num']){
						$refund['is_refund']	=	0;
						$refund['refund_reason']	=	'';
					}else{
						$refund['is_refund']	=	1;
					}
					
					$updata_refund	=	D('shop_order_refund')->add($refund);
					if($updata_refund){
						$updata_order['status']	=	60;
						$updata_shop_order	=	D('shop_ordersn')->where('order_sn="'.$order_sn.'"')->save($updata_order);
						add_shop_order_log($order_sn,$uid,'','申请退款',time());
						$this->success('提交申请成功',U('Usercenter/Shoporder/shop_order_refund',array('order_sn'=>$goods['order_sn'])));
					}else{
						$this->error('提交申请失败',U('Usercenter/Shoporder/shop_order_refund',array('order_sn'=>$goods['order_sn'])));
					}
				}
			}
		}else{
			$goods = D('shop_order_info')->where(array('order_sn'=>$order_sn,'id'=>$id,'siteid'=>SITEID))->find();
			$goods_refund = D('shop_order_refund')->where('order_sn="'.$order_sn.'" and shop_order_info_id='.$id.' and siteid='.SITEID.' and refund_status>0')->select();
			foreach($goods_refund as $key=>$value){
				$has_refund_num	=	$has_refund_num+$value['refund_num'];
			}
			$goods['goods_num']	=	$goods['goods_num']-$has_refund_num;
			$this->assign('goods',$goods);
			$this->display();
		}
	}
	//修改退款申请
	public function shop_order_refund_edit($order_sn='',$id=''){
		if(IS_POST){
			$id			=	$_POST['id'];
			$order_sn	=	$_POST['order_sn'];
			$refund_num	=	$_POST['refund_num'];
			$reason_select	=	$_POST['reason_select'];
			$refund_reason=	op_t(trim($_POST['refund_reason']));
			if($reason_select==0){
				$this->error('请选择退款原因');
			}
			if($refund_reason==''){
				$this->error('请填写详细说明');
			}
			$old_refund	=	D('shop_order_refund')->where(array('order_sn'=>$order_sn,'id'=>$id))->field('shop_order_info_id,refund_status,refund_num')->find();
			$refund_status	=	D('shop_order_refund')->where(array('order_sn'=>$order_sn,'id'=>$id))->getField('refund_status');
			$info_id	=	$old_refund['shop_order_info_id'];
			$refund_status	=	$old_refund['refund_status'];
			$old_refund_num	=	$old_refund['refund_num'];
			$order_status	=	D('shop_ordersn')->where(array('order_sn'=>$order_sn))->getField('status');
			if($order_status!=60){
				$this->error('订单状态错误',U('Usercenter/Shoporder/shop_order_refund',array('order_sn'=>$order_sn)));
			}
			if($refund_status!=1){
				$this->error('申请状态错误',U('Usercenter/Shoporder/shop_order_refund',array('order_sn'=>$order_sn)));
			}
			if($refund_num==0){
				$del_shop_refund	=	D('shop_order_refund')->where(array('order_sn'=>$order_sn,'id'=>$id))->delete();
				if($del_shop_refund){
					$refund_count	=	D('shop_order_refund')->where('order_sn='.$order_sn.' and refund_status>0 and refund_status<10')->count();
					if($refund_count==0){
						
						$updata_order['status']		=	22;
						$a=D('shop_ordersn')->where('order_sn="'.$order_sn.'"')->save($updata_order);
					}
					$this->success('取消申请成功',U('Usercenter/Shoporder/shop_order_refund',array('order_sn'=>$order_sn)));
				}
			}
			
			$goods = D('shop_order_info')->where(array('order_sn'=>$order_sn,'id'=>$info_id,'siteid'=>SITEID))->find();
			$goods_refund = D('shop_order_refund')->where(array('order_sn'=>$order_sn,'shop_order_info_id'=>$info_id,'siteid'=>SITEID))->select();
			foreach($goods_refund as $key=>$value){
				$has_refund_num	=	$has_refund_num+$value['refund_num'];
			}
			if(!$goods){
				$this->error('订单信息错误');
			}else{
				if($refund_num>$goods['goods_num']-$has_refund_num+$old_refund_num){
					$this->error('选择的数量超出已选购商品'.$goods['goods_num']);
				}else{
					$refund['uid']	=	is_login();
					$refund['siteid']	=	SITEID;
					$refund['order_sn']	=	$order_sn;
					$refund['create_time']	=	time();
					$refund['refund_num']	=	$refund_num;
					$refund['reason_select']=	$reason_select;
					$refund['refund_reason']	=	$refund_reason;
					$refund['id']	=	$id;
					if(!$refund['refund_num']){
						$refund['is_refund']	=	0;
						$refund['refund_reason']	=	'';
					}else{
						$refund['is_refund']	=	1;
					}
					
					$updata_refund	=	D('shop_order_refund')->save($refund);
					if($updata_refund){
						$this->success('提交申请成功',U('Usercenter/Shoporder/shop_order_refund',array('order_sn'=>$goods['order_sn'])));
					}else{
						$this->error('提交申请失败',U('Usercenter/Shoporder/shop_order_refund',array('order_sn'=>$goods['order_sn'])));
					}
				}
			}
		}else{
			$goods_refund = D('shop_order_refund')->where(array('order_sn'=>$order_sn,'id'=>$id,'siteid'=>SITEID))->find();
			$goods = D('shop_order_info')->where(array('order_sn'=>$order_sn,'id'=>$goods_refund['shop_order_info_id'],'siteid'=>SITEID))->find();
			$goods_refund_list = D('shop_order_refund')->where(array('order_sn'=>$order_sn,'shop_order_info_id'=>$goods_refund['shop_order_info_id'],'siteid'=>SITEID))->select();
			foreach($goods_refund_list as $key=>$value){
				$has_refund_num	=	$has_refund_num+$value['refund_num'];
			}
			$goods['goods_num']	=	$goods['goods_num']-$has_refund_num+$goods_refund['refund_num'];
			$this->assign('goods',$goods);
			$this->assign('goods_refund',$goods_refund);
			$this->display();
		}
	}
	
	public function pay($order_sn){	
		
		$shop_order_detail	=	D('Common/Shop')->shop_order_detail($order_sn,'user');
		$pay_types = D('Paydeposit')->get_paytype();
		$this->assign('pay_types',$pay_types);

		$this->assign('order_info',$shop_order_detail);
		$this->assign('sign',$shop_order_detail);
		$this->assign('list',$shop_order_detail['goods_list']);
		$this->display();	
	}
	
	 public function do_pay()
    {
		$uid=is_login();
		$siteid=SITEID;
		if(IS_POST){
			$order_id = op_t($_POST['order_id']);
			
			$pay_id = $_POST['pay_type'];
			if(!$pay_id) $this->error('请选择支付方式');
			if(!$order_id) $this->error('订单参数出错，请重试');
			
			//判断订单是否存在，判断支付情况
			$order_attend = D('shop_ordersn')->where(array('order_sn' => $order_id,'siteid'=>SITEID))->find();
			if (!$order_attend) {
				$this->error('订单不存在！');
			}else{
				
				if($order_attend['pay_status']==2){
					$this->error('本订单已经支付过了，请到详情页查看');
				}
				//$ispay = check_event_order_ispay($order_attend['status']);
				if($ispay){
					
					$this->error($ispay);
				}
			}
			$alltotalprice	=	$order_attend['alltotalprice'];
			
			
			//生成流水号
			$trade_sn = create_sn();
			//获取支付方式
			$payment = D('Paydeposit')->get_payment($pay_id);
			//判断是否为线上支付
			if($payment['is_online'] === '1') {
				$surplus_data['status']  = 'unpay';
				$surplus_data['pay_type']  = 'recharge';
			} else {
				$surplus_data['status']  = 'waitting';
				$surplus_data['pay_type']  = 'offline';
			}
			//支付总金额
			if($order_attend['pay_status'] ==0){
				$payprice = $order_attend['alltotalprice'];
				$order_paytype = 0;
			}
		
			$usernote='【'.get_webinfo('webname').'商城订单号:'.$order_attend['order_sn'].'】'.$order_attend['consignee_address'].$order_attend['create_time'];
			$surplus = array(
					'uid'      => is_login(),
					'username'    => $order_attend['consignee_name'],
					'siteid'    => SITEID,
					'money'       => trim(floatval($payprice)),
					'order_paytype'       => trim(floatval($order_paytype)),
					'quantity'    => $_POST['quantity'] ? trim(intval($_POST['quantity'])) : 1,
					'telephone'   => $order_attend['phone'],
					'contactname' => '【'.get_webinfo('webname').'商城订单号:'.$order_attend['order_sn'].'】',
					'email'       => $order_attend['email'],
					'addtime'	  => time(),
					'pay_type'	  => $surplus_data['pay_type'],
					'status'	  => $surplus_data['status'],
					'pay_id'      => $payment['pay_id'],		
					'payment'     => trim($payment['pay_name']),
					'usernote'    => $usernote,
					'trade_sn'	  => $trade_sn,
					'order_type'	  => 1,
					'type' => 2,
					'order_id'	  => $order_id,	
			);
			$result=D('Paydeposit')->set_record($surplus);
			if($result){
				$updata['trade_sn'] = $trade_sn;
				D('shop_ordersn')->where(array('order_sn'=>$order_id))->save($updata);
				D('RecordContent')->setuseprice_account($trade_sn);//流水号的余额
				redirect(U('Usercenter/Shoporder/pay_redirect',array('trade_sn'=>$trade_sn)));
			} else {
				$this->error('操作失败',U('Usercenter/Shoporder/pay',array('trade_sn'=>$trade_sn)));
			}
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


}
<?php
namespace Shop\Controller;

use Think\Controller;

class PayController extends Controller
{
		protected $userdata;
    public function _initialize()
    {

        if (!is_login()) {
            //$this->error('请登录后再访问本页面。');
			$this->redirect('Home/User/login');
        }
		$this->handle = D('Paydeposit');

    }
	
   public function pay($order_sn){	
		$uid=is_login();
		$siteid=SITEID;
		//$pay=$_SESSION['pay'];
		//dump($pay);
		$list= D('shop_ordersn') -> where('uid='.$uid.' and siteid='.$siteid.' and order_sn='.'"'.$order_sn.'"')->find();
		//使用的优惠券信息
		
		$card_info = D('pointcard')->where(array('cardid'=>$list['cardid'],'siteid'=>SITEID))->find();
		$amount = $card_info['amount'];
		$endtime = !empty($card_info['endtime']) ? date('Y-m-d H:i:s',$card_info['endtime']) : 0 ;
		$list['amount']=$amount;
		$list['cardname']=$card_info['typename'];
		
		//查询详细区域
		$list['district']=D('district')->where("id=".$list['consignee_address'])->getField('upid');
		$list['city']=D('district')->where("id=".$list['district'])->getField('upid');
		$list['province']=D('district')->where("id=".$list['city'])->getField('upid');
		
		$list['topid']=D('district')->where("id=".$list['province'])->getField('upid');
		
		if($list['district']==0 && $list['consignee_address']!=0){
			$list['thetop']=$list['consignee_address'];
		}else if($list['city']==0 && $list['district']!=0){
			$list['thetop']=$list['district'];
		}else if($list['province']==0 && $list['city']!=0){
			$list['thetop']=$list['city'];
		}else if($list['topid']==0 && $list['province']!=0){
			$list['thetop']=$list['province'];
		}
		$list['address_province']=D('district')->where("id=".$list['thetop'])->getField('name');
		
		$fr=D('shop_freight');
		
		$listAddress=$fr->order('f_id')->where('f_id>1  and goods_address like "%'.$list['address_province'].'%"')->Field('f_id,goods_address,f_money,f_num,f_freight')->find();
		if($listAddress){
			$freight_list=$listAddress;
		}else{
			$freight_list = $fr->order('f_id')->limit(1)->find();//这是当没有符合条件时就选择第一条数据信息
		}
	//	dump($freight_list);
		
		$list2= D('shop_order_info') -> where('uid='.$uid.' and siteid='.$siteid.' and order_sn='.'"'.$order_sn.'"')->select();

		foreach($list2 as $key=>$value){
			$price=$value['goods_price'];
			$num=$value['goods_num'];
			$fr_freight=$value['freight'];
			$alltotalprice=$alltotalprice+$price*$num;
			$totalnum=$totalnum+$num;
			$allfreight=$allfreight+$fr_freight;
			if($allfreight==0){$allfreight='包邮产品';}
			$list2[$key]['freight']=$fr_freight;
		}
		$list['totalcostprice']=$alltotalprice;
		$list['totalnum']=$totalnum;
		$list['allfreight']=$allfreight;
		$pay_types = D('Paydeposit')->get_paytype();
		/*$financial_order = D('pay_account')->where(array('sitid'=>SITEID, 'order_id' => $check['trade_sn'], 'order_type' => 1, 'uid' => is_login()))->find();
		if($financial_order){
			$factory_info = D('Paydeposit')->get_record($financial_order['trade_sn']);
		    if(!$factory_info) $this->error('订单已完成支付或已经取消支付');
		}*/
		//银行汇款
		$fr=D('shop_freight');
		$list5 = $fr->order('f_id')->limit(1)->find();
		$this->assign('listMoney',$list5);
		
		//运费设置
		$fr=D('shop_freight');
		$listAddress=$fr->order('f_id')->where('f_id>1')->getField('f_id,goods_address,f_money,f_num,f_freight');
		//echo D('shop_freight')->getLastSql();die;
		$this->assign('listAddress',$listAddress);
		
		$this->assign('pay_types',$pay_types);

		$this->assign('sign',$list);
		$this->assign('list',$list2);
		$this->display();	
	}
	
	 public function do_pay()
    {
		$uid=is_login();
		$siteid=SITEID;
		if(IS_POST){
			$order_id = op_t($_POST['order_id']);
			$alltotalprice = $_POST['alltotalprice'];
			$allfreight=$_POST['allfreight'];
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
			//判断商品状态是否正确
			$order_attend2 = D('shop_order_info')->where(array('order_sn' => $order_id,'siteid'=>SITEID))->select();
			foreach($order_attend2 as $key=>$value){
			$shop_content = D('shop')->where(array('sitid'=>SITEID, 'id' => $order_attend2[$key]['goods_id']))->find();
				if (!$shop_content) {
					$this->error('商品状态错误');
				}
			}

			
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
				$payprice = $order_attend['alltotalprice']+$allfreight;
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
				redirect(U('Shop/Pay/pay_redirect',array('trade_sn'=>$trade_sn)));
			} else {
				$this->error('操作失败',U('Shop/Pay/pay_redirect',array('trade_sn'=>$trade_sn)));
			}
			//dump($surplus);
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
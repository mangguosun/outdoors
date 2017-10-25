<?php

namespace Pays\Controller;
set_time_limit (0);
use Think\Controller;


class IndexController extends Controller{
	
	 	
	public function index($trade_sn){ 
		$factory_info = D('Mobile/Paydeposit')->get_record($trade_sn);
		if(!$factory_info) $this->error('订单已完成支付或已取消支付');
		$payment = D('Mobile/Paydeposit')->get_payment($factory_info['pay_id']);
		$cfg = unserialize_config($payment['config']);
		$pay_name = ucwords($payment['pay_code']);
		$pay_fee = pay_fee($factory_info['money'],$payment['pay_fee'],$payment['pay_method']);
		$logistics_fee = $factory_info['logistics_fee'];
		$discount = $factory_info['discount'];
		$factory_info['price'] = $factory_info['money'] + $pay_fee + $logistics_fee + $discount;
		$order_info['id']	= $factory_info['trade_sn'];
		$order_info['quantity']	= $factory_info['quantity'];
		$order_info['buyer_email']	= $factory_info['email'];
		$order_info['order_time']	= $factory_info['addtime'];
		$product_info['name'] = $factory_info['contactname'];
		$product_info['body'] = $factory_info['usernote'];
		$product_info['price'] = $factory_info['price'];
		$customerinfo['telephone'] = $factory_info['telephone'];		
		if($payment['is_online'] === '1') {	
			$payment_handler = new \Pay\Payfactory($pay_name, $cfg);
			$payment_handler->set_productinfo($product_info)->set_orderinfo($order_info)->set_customerinfo($customer_info);
			if($factory_info['type']==1){ 
				$web_url['url'] = "http://".$factory_info['website_url']."/mobile/config/myevent_detail/trade_sn/".$factory_info['order_id'];
				$web_url['refer_url']="http://".$factory_info['website_url']."/mobile/pay/pay/trade_sn/".$factory_info['order_id'];
			}elseif($factory_info['type']==2){ 
				$web_url['url'] = "http://".$factory_info['website_url']."/mobile/shoporder/order_detail/order_sn/".$factory_info['order_id'];
				$web_url['refer_url']="http://".$factory_info['website_url']."/mobile/shoporder/pay/order_sn/".$factory_info['order_id'];
			}
			$jsApiParameters = $payment_handler->get_code_weixin();
		    $this->assign('web_url',$web_url);
		   // var_dump($web_url);
			$this->assign('jsApiParameters', $jsApiParameters);
			//vendor('MobilePay.alipayapi');	
		} else {
			$this->error('订单出现未知错误');
		}

		$this->assign('factory_info', $factory_info);
		$this->display();
	}
    
}
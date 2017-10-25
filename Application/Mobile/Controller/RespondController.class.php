<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-6-27
 * Time: 下午1:54
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace Mobile\Controller;


use Think\Controller;
use Mobile\Paydeposit;

class RespondController extends Controller
{
 	/**
	 * return_url get形式响应
	 */
	public function respond_get() {
		if ($_GET['code']){
			$payment = get_by_code($_GET['code']);
			if(!$payment) $this->error('支付方式发生错误');
			$cfg = unserialize_config($payment['config']);
			$pay_name = ucwords($payment['pay_code']);
			$payment_handler = new \Pay\Payfactory($pay_name, $cfg);
			$return_data = $payment_handler->receive();
			if($return_data) {
				if($return_data['order_status'] == 0) {				
					update_member_behavior($return_data['order_id']);
				}
				update_recode_status_by_sn($return_data['order_id'],$return_data['order_status']);
				$arr = D('pay_account')->where('trade_sn='.$return_data['order_id'])->find();
				$type=$arr['type'];		//判断订单是活动还是商城
					if($type==1){
						$this->success('恭喜您，支付成功',U('mobile/Config/myevent_detail',array('trade_sn'=>$arr['order_id'])));
					}elseif($type==2){
						$msg	=	'支付方式：'.$arr['payment'].'，支付'.$arr['money'].'元';
						add_shop_order_log($arr['order_id'],is_login(),$msg,'商城订单支付',$arr['addtime']);
						$this->success('恭喜您，支付成功',U('mobile/Shoporder/order_detail',array('order_sn'=>$arr['order_id'])));
					}
			} else {
						$this->error('支付失败，请联系管理员',U('mobile/Config/index'));
			}
		} else {
			$this->success('恭喜您，支付成功',U('mobile/Config/index'));
		}
	}
	/**
	 * 服务器端 POST形式响应
	 */
	public function respond_post() {
		$_POST['code'] = $_POST['code'] ? $_POST['code'] : $_GET['code'];
		if ($_POST['code']){
			$payment = get_by_code($_POST['code']);
			if(!$payment) $result = FALSE;
			$cfg = unserialize_config($payment['config']);
			$pay_name = ucwords($payment['pay_code']);
			$payment_handler = new \Pay\Payfactory($pay_name, $cfg);
			$return_data = $payment_handler->notify();
			if($return_data) {
				if($return_data['order_status'] == 0) {
					update_member_behavior($return_data['order_id']);
				}
				update_recode_status_by_sn($return_data['order_id'],$return_data['order_status']);				
                $result = TRUE;
			} else {
				$result = FALSE;
			}
			$payment_handler->response($result);
		}
	}
	public function notify($trade_status){
		switch ($trade_status) {
			case 'WAIT_BUYER_PAY': $return_data['order_status'] = 3; break;
			case 'WAIT_SELLER_SEND_GOODS': $return_data['order_status'] = 3; break;
			case 'WAIT_BUYER_CONFIRM_GOODS': $return_data['order_status'] = 3; break;
			case 'TRADE_CLOSED': $return_data['order_status'] = 5; break;						
			case 'TRADE_FINISHED': $return_data['order_status'] = 0; break;
			case 'TRADE_SUCCESS': $return_data['order_status'] = 0; break;
			default:
				 $return_data['order_status'] = 5;
		}
		return $return_data;
	}

} 
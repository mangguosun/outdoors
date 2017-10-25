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
use Usercenter\Paydeposit;

class RespondController extends Controller
{
    public function _initialize()
    {
		$this->handle = D('Paydeposit');
		
		/******************pay status********************/


    }
	public function ceshi() {
		//update_member_behavior('2014110313323162709');
	//print_r($_GET);
	
	}
	
	
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
						$this->success('恭喜您，支付成功',U('Usercenter/Eventorder/myevent_detail',array('trade_sn'=>$arr['order_id'])));
					}elseif($type==2){
						$msg	=	'支付方式：'.$arr['payment'].'，支付'.$arr['money'].'元';
						add_shop_order_log($arr['order_id'],is_login(),$msg,'商城订单支付',$arr['addtime']);
						$this->success('恭喜您，支付成功',U('Usercenter/Shoporder/shop_order_detail',array('order_sn'=>$arr['order_id'])));
					}
			} else {
						$this->error('支付失败，请联系管理员',U('Usercenter/Eventorder/index'));
			}
		} else {
			$this->success('恭喜您，支付成功',U('Usercenter/Eventorder/index'));
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




	

} 
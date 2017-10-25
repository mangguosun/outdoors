<?php
namespace Usercenter\Model;

use Think\Model;
class PaydepositModel extends Model{
	
	public function __construct() {
		$this->pay_db = M('pay_payment');
		$this->account_db = M('pay_account');
	}
	/**
	 * 生成流水记录
	 * @param unknown_type 
	 */
	public function set_record($data){
		$require_items = array('uid','siteid','username','email','contactname','telephone','trade_sn','money','quantity','addtime','paytime','usernote','usernote','pay_type','pay_id','payment','status','order_type','type','order_id','order_paytype');
		if(is_array($data)) {
			foreach($data as $key=>$item) {
				if(in_array($key,$require_items)) $info[$key] = $item;
			}			
		} else {
			return false;
		}
		
		
		$trade_exist = $this->account_db->where(array('trade_sn'=>$info['trade_sn']))->find();
		if($trade_exist) return $trade_exist['id'];
		$res = $this->account_db->add($info);
		return $res;
	}
	
	/**
	 * 获取流水记录
	 * @param init $id 流水帐号
	 */
	public function get_record($trade_sn) {
		$trade_sn = trim($trade_sn);
		$result = array();
		$result = $this->account_db->where(array('trade_sn'=>$trade_sn))->find();
		$status_arr = array('succ','failed','error','timeout','cancel');
		return ($result && !in_array($result['status'],$status_arr)) ? $result: false;
	}
	/**
	 * 获取充值方式信息
	 * @param unknown_type $pay_id
	 * @return unknown
	 */
	public function get_payment($pay_id) {
		$pay_id = intval($pay_id);
		$result = array();
		$result = $this->pay_db->where(array('pay_id'=>$pay_id))->find();
		return $result;
	}
		
	/**
	 * 获取充值类型
	 */
	public function get_paytype() {
		$result = $this->pay_db->where(array('enabled'=>1,'pay_platform'=>'pc'))->select();
		$info = array();
		foreach($result as $key =>$r) {
			$out_siteid=str2arr($r['out_siteid']);
			if(in_array(SITEID,$out_siteid)) continue;
			$info[] = $r;
		}
		return $info;
	}
}
?>
<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 想天
 * 创建日期: 3/13/14
 * 创建时间: 7:41 PM
 * 版权所有 想天工作室(www.ourstu.com)
 */

namespace Common\Model;

use Think\Model;

class RecordContentModel extends Model
{
   public function __construct() {
		$this->pay_db = M('pay_payment');//支付方式
		$this->account_db = M('pay_account');//支付记录
		$this->websit_cash_record_db = M('websit_cash_record');//站点账户
		$this->websit_cashout_record_db = M('websit_cashout_record');//提现记录
	}


     /*
	* 更改提现记录中 本站中总账户中的总可用余额
	* $trade_sn 提现流水号	
	*/
	public function setuseprice_cashout($trade_sn){ 
		
		$data =  $this->websit_cash_record_db->where(array('siteid'=>SITEID,'status'=>1))->find();//得到站点账户的资金信息
		$list['useprice'] = $data['balance'];
		//更改提现 记录中的可用余额
		$id['id'] = $this->websit_cashout_record_db->where(array('siteid'=>SITEID,'flownumber'=>$trade_sn))->save($list);
		$id['sql'] = $this->account_db->getLastSql();
		return $id;
	} 


	/*
	* 更改支付记录中 本站中总账户中的总可用余额
	* $trade_sn 支付流水号	
	*/
	public  function setuseprice_account($trade_sn,$siteid=0){ 
		if(!$siteid){ 
			$siteid=SITEID;
		}
	  	$data =  $this->websit_cash_record_db->where(array('siteid'=>$siteid,'status'=>1))->find();//得到站点账户的资金信息
		$list['useprice'] = $data['balance'];
		//更改支付 记录中的可用余额
		$id['id'] = $this->account_db->where(array('siteid'=>$siteid,'trade_sn'=> $trade_sn))->save($list);
		//$id['sql'] = $this->account_db->getLastSql();
		return $id;

	}


} 
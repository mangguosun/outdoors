<?php

namespace Addons\AliPlay\Model;
use Think\Model;

/**
 * AliPlay模型
 */
class AliPlayModel extends Model{
	
			/*
	 *读取配置参数写入文件
	 */
	public function  get_config(){
		//读取配置信息
		$financial_alipay=D('financial_alipay')->where("siteid =".SITEID." and codelogin =1")->find();
		
		
		$config['partner'] = $financial_alipay['partner'];
		$config['key'] = $financial_alipay['key'];
		$config['seller_email'] = $financial_alipay['seller_email'];
		$config['codelogin'] = $financial_alipay['codelogin'];
		$config['pay_type'] = 2;
		
		return $config;
	}

}

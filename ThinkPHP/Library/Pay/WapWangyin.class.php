<?php
namespace Pay;

use Pay\Paymentabstract;

use Pay\wangyin\DesUtils;
use Pay\wangyin\SignUtil;
use Pay\wangyin\WebAsynNotificationCtrl;

/**对数组排序
 * @param $array 排序前的数组
* @param return 排序后的数组
*/
function argSort($array) {
	ksort($array);
	reset($array);
	return $array;
}

class WapWangyin extends paymentabstract{

	public function __construct($config = array()) {	
		if (!empty($config)) $this->set_config($config);
		
		$this->config['gateway_url'] = 'https://m.jdpay.com/wepay/web/pay';
		$this->config['serverQueryUrl'] = 'https://m.jdpay.com/wepay/query';
		$this->config['serverRefundUrl'] = 'https://m.jdpay.com/wepay/refund';
		$this->config['gateway_method'] = 'POST';
		$this->config['notify_url'] = mobile_return_url('WapWangyin',1);
		$this->config['successCallbackUrl'] = mobile_return_url('WapWangyin');
		$this->config['failCallbackUrl'] = mobile_return_url('WapWangyin');
		$this->config['version']    = '2.0';
		$this->config['currency']    = 'CNY';
		
		//字符编码格式 目前支持 gbk 或 utf-8
		$this->config['input_charset']= 'utf-8';
		
	}

	/**对数组排序
 * @param $array 排序前的数组
 * @param return 排序后的数组
 */
	
	
//**************************************************************

	public function getpreparedata() {

		// 商品信息
	//	$prepare_data['subject'] = $this->product_info['name'];
//		$prepare_data['price'] = $this->product_info['price'];
//		$prepare_data['body'] = $this->product_info['body'];
//		
//		//订单信息
//		$prepare_data['out_trade_no'] = $this->order_info['id'];
//		$prepare_data['quantity'] = $this->order_info['quantity'];
//			//买家信息
//		$prepare_data['buyer_email'] = $this->order_info['buyer_email'];
		
		$prepare_data["currency"] = $this->config['currency'];//币种
		$prepare_data["failCallbackUrl"] = $this->config['failCallbackUrl'];//支付失败页面跳转路径
		$prepare_data["merchantNum"] = $this->config['merchantNum'];//商户号
		$prepare_data["merchantRemark"] = $this->product_info['Remarks'];//商户备注
		$prepare_data["notifyUrl"] = $this->config['notify_url'];//异步通知地址
		$prepare_data["successCallbackUrl"] = $this->config['successCallbackUrl'];//支付成功页面跳转路径
		//$prepare_data["tradeAmount"] = $this->product_info['price']*100;//交易金额
		$prepare_data["tradeAmount"] = $this->product_info['price']*100;//交易金额
		$prepare_data["tradeDescription"] = $this->product_info['body'];//交易描述
		$prepare_data["tradeName"] = $this->product_info['name'];//交易名称
		$prepare_data["tradeNum"] = $this->order_info['id'];//交易流水号
		$prepare_data["tradeTime"] = date('Y-m-d H:i:s', time());//交易时间
		$prepare_data["token"] = $this->order_info['token'];//用户交易令牌
		$prepare_data["version"] = $this->config['version'];//用户交易令
		
		
		
		$sign = SignUtil::sign($prepare_data);
		//dump($prepare_data);die;
		$prepare_data["merchantSign"] = $sign;
		if ($this->config['version'] == "1.0") {
			//敏感信息未加密
		} else if ($this->config['version'] == "2.0") {
			
			//敏感信息加密
			//获取商户 DESkey
			//对敏感信息进行 DES加密
			$desUtils  = new DesUtils();
			$key = $this->config['desKey'];
			$param["merchantRemark"] = $desUtils->encrypt($prepare_data["merchantRemark"],$key);
			$prepare_data["tradeNum"] =$desUtils->encrypt($prepare_data["tradeNum"],$key);
			$prepare_data["tradeName"] = $desUtils->encrypt($prepare_data["tradeName"],$key);
			$prepare_data["tradeDescription"] = $desUtils->encrypt($prepare_data["tradeDescription"],$key);
			$prepare_data["tradeTime"] =$desUtils->encrypt($prepare_data["tradeTime"],$key);
			$prepare_data["tradeAmount"] = $desUtils->encrypt($prepare_data["tradeAmount"],$key);
			$prepare_data["currency"] = $desUtils->encrypt($prepare_data["currency"],$key);
			$prepare_data["notifyUrl"] = $desUtils->encrypt($prepare_data["notifyUrl"],$key);
			$prepare_data["successCallbackUrl"] = $desUtils->encrypt($prepare_data["successCallbackUrl"],$key);
			$prepare_data["failCallbackUrl"] =$desUtils->encrypt($prepare_data["failCallbackUrl"],$key);
		}
		return $prepare_data;
	}
	
	/**
	 * GET接收数据
	 * 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付5交易取消6交易发生错误）
	 */
    public function receive() {
		
		
		if(empty($_GET)) {
			$log_data['title'] = 'GET信息返回错误';
			$log_data['content'] = array2string($receive_data);
			D('pay_log')->add($log_data);
			E('信息返回错误');
			return false;
		}else {
		
			//dump($_GET);die;//取到 code token  tradeNum
			$receive_data = $this->filterParameter($_GET);
			//dump($receive_data);die;
			$receive_data = argSort($receive_data);
			
	
			
			//生成签名结果
			//$isSign = $this->getSignVeryfy($receive_data, $_GET["resp"],true);
			if ($receive_data['tradeNum']) {
				$return_data['order_id'] = $receive_data['tradeNum'];
				$arr = D('pay_account')->where('trade_sn='.$return_data['order_id'])->find();	
				$trade_status = $arr['status'];
				if($trade_status == 'succ'){
					$return_data['order_status'] = 0;	
				}else{
					$return_data['order_status'] = 5;	
				}
				return $return_data;
						
			} else {
				$log_data['title'] = 'JD-GET数据错误';
				$log_data['content'] = array2string($return_data);
				D('pay_log')->add($log_data);
				return false;
			}
		}
    

	}

	
	/**
	 * GET接收数据
	 * 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付5交易取消6交易发生错误）
	 */
    public function notify() {
    	//$WebAsynNotificationCtrl = new WebAsynNotificationCtrl();
    	//$receive_sign = $_POST['resp'];
    	
    	/* $log_data['title'] = 'POST信息返回错误检查888888';
    	$log_data['content'] =array2string($_POST);;
    	D('pay_log')->add($log_data);
    	E('信息返回错误'); */
    	
		//$_POST['resp'] = 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4NCjxDSElOQUJBTks+CiAgPFZFUlNJT04+MS4wLjA8L1ZFUlNJT04+CiAgPE1FUkNIQU5UPjExMDA1NzExNDAwMTwvTUVSQ0hBTlQ+CiAgPFRFUk1JTkFMPjAwMDAwMDAxPC9URVJNSU5BTD4KICA8REFUQT5Sd1ZKQzE1S2wvL2ZVNzZJUzRyNlQ3Q1FDY3FlcnFDUmpFb2UxVFlKYVp3K05PdnRncnhyb0pDOVFSN05JQ2lNbVdSaklXbnhEM2VDZUtGOEUvZjdzZTFlZDErYUtPMDRwQkgvOHBDS0pNZ0xRLzJUOEZXMFI5Nm9YaU1rQ0E1NEtyUTJwZTJWZ2N6RXIwL3dXVVNDS1NZZ2I2eUx4bWgrQTZMQWtnMGVjVlZ6NXlsbkw5UDZkUGZUL0l0RUFwRDNHQXMyQ0Ewbmc4TkRvY3ZTNytuNE9QZk1ERTRjbFZCcUh2ZVJZcEVZL0lMOURWaXhFMlFFUE9MbytCRUhtZlh0cDdnRHlhTzJEVTVNS2JoYVF1V24yZUxvK0JFSG1mWHQ3dDhTUG9zYmZ3YUxiMEZjZ2JzUW1URFQ1Q1cyTjBWWk1OUGtKYlkzUlZsZGNOczFDSmtmeEtNMzN1bS82SFozejBIUDh4cUhFdGljUmRleHYxV0VnM0MzN0YwQkdBR3JmM3FBT1pYUnZURU9xS2FDSndHb29ZNm9nZloxOVVySmFzRERuUUNYcUlEeFF1cGxpaUl4UmdlamNJYlNoUTZrRW5KK21TaUlOR09oZkpUeWNDZWFRRFdXTkx6dnFRa0dYRmVPNEgrVk4wUk84UW05UmpNZUJRN2xQZ1AxMmQwejR1ajRFUWVaOWUxTTErSW9SZ2NwQVFaRFpCVmNJWDVnSTFIRFlpbStaM3RDbkNCdXV3Wks2YmhCVXpsTEs3eEU8L0RBVEE+CiAgPFNJR04+M2ZkZmMyNTJjNGNiNjdkZjQyZDk0ZjY1MWQ2OWI2YjE8L1NJR04+CjwvQ0hJTkFCQU5LPg==';
		
    	$receive_sign=$_POST['resp'];
    	//$receive_data = $this->filterParameter($_POST);
		
		$receive_data = $_POST;
		
		
    	//$receive_data = argSort($receive_data);
    	
		
		
    	/*$content = $receive_sign;
    	$file1 = 'newfile.txt';
    	$fp = fopen($file1, 'w');
    	fwrite($fp, $content);
    	fclose($fp);//此次在测试版本，写入国LOG 记录*/
    	
    	if ($receive_data) {
			
			
			
			if($receive_sign){
				
				$WebAsynNotificationCtrl = new WebAsynNotificationCtrl();
				$params = $WebAsynNotificationCtrl->xml_to_array ( base64_decode ( $receive_sign ) );
				$ownSign = $WebAsynNotificationCtrl->generateSign ( $params, $this->config['md5Key']);
			
			
				if ($params ['SIGN'] [0] == $ownSign) {
					// 验签不对
					//echo "签名验证正确!" . "\n";
					
					$des = new DesUtils (); // （秘钥向量，混淆向量）
					$decryptArr = $des->decrypt ( $params ['DATA'] [0], $this->config['desKey'] ); // 加密字符串
					//echo "对<DATA>进行解密得到的数据:" . $decryptArr . "\n";
					$params ['data'] = $decryptArr;
					
					
					if($params ['data']){
						
						
						$doc = new \DOMDocument();	
						
						$doc->loadXML($params ['data']);
						$return_data['order_id']  = $doc->getElementsByTagName( "ID" )->item(0)->nodeValue;
						$trade_status = $doc->getElementsByTagName( "STATUS" )->item(0)->nodeValue;
						switch ($trade_status)
						{
							case '6': $return_data['order_status'] = 3; break;
							case '7': $return_data['order_status'] = 5; break;						
							case '0': $return_data['order_status'] = 0; break;
							default:
							$return_data['order_status'] = 5;						
						}	
						
						return $return_data;
						
						
						$log_data['title'] = 'JD-POST成功数据';
						$log_data['content'] = array2string($params);
						D('pay_log')->add($log_data);
						return false;
						
					}else{
						$log_data['title'] = 'JD-POST-DATA数据出错';
						$log_data['content'] = array2string($params);
						D('pay_log')->add($log_data);
						return false;
					}
				} else {
					$log_data['title'] = 'JD-POST签名验证错误';
					$log_data['content'] = array2string($ownSign);
					D('pay_log')->add($log_data);
					return false;
				}
			}else{
				$log_data['title'] = 'JD-POST签名错误';
				$log_data['content'] = array2string($receive_sign);
				D('pay_log')->add($log_data);
				return false;
			}
    	}else {

			$log_data['title'] = 'JD-POST信息返回错误';
			$log_data['content'] = array2string($receive_data);
			D('pay_log')->add($log_data);
			return false;
		}   	
    	//$receive_data = argSort($receive_data);

    	//return $WebAsynNotificationCtrl->execute($this->config['desKey'],$this->config['md5Key'],$receive_sign);
	}

	
    /**
     * 相应服务器应答状态
     * @param $result
     */
    public function response($result) {
    	if (FALSE == $result) echo 'fail';
		else echo 'success';
    }
    
    /**
     * 返回字符过滤
     * @param $parameter
     */
	private function filterParameter($parameter)
	{
		$para = array();
		foreach ($parameter as $key => $value)
		{
			if ('sign' == $key || 'sign_type' == $key || '' == $value  || 'code' == $key ) continue;
			else $para[$key] = $value;
		}
		return $para;
	}
}
?>
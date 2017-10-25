<?php
namespace Pay;

use Pay\Paymentabstract;

/**
 *功能：支付宝接口公用函数
 *详细：该页面是请求、通知返回两个文件所调用的公用函数核心处理文件，不需要修改
 *版本：3.0
 *修改日期：2010-05-24
 '说明：
 '以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 '该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

*/
/**
 * 生成签名结果
 * @param $array要加密的数组
 * @param return 签名结果字符串
*/
function build_mysign($sort_array,$security_code,$sign_type = "MD5") {
    $prestr = create_linkstring($sort_array);     	//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
    $prestr = $prestr.$security_code;				//把拼接后的字符串再与安全校验码直接连接起来
    $mysgin = sign($prestr,$sign_type);			    //把最终的字符串加密，获得签名结果
    return $mysgin;
}	


/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
 * @param $array 需要拼接的数组
 * @param return 拼接完成以后的字符串
*/
function create_linkstring($array) {
    $arg  = "";
    while (list ($key, $val) = each ($array)) {
        $arg.=$key."=".$val."&";
    }
    $arg = substr($arg,0,count($arg)-2);		     //去掉最后一个&字符
    return $arg;
}

/********************************************************************************/

/**除去数组中的空值和签名参数
 * @param $parameter 加密参数组
 * @param return 去掉空值与签名参数后的新加密参数组
 */
function para_filter($parameter) {
    $para = array();
    while (list ($key, $val) = each ($parameter)) {
        if($key == "sign" || $key == "sign_type" || $val == "")continue;
        else	$para[$key] = $parameter[$key];
    }
    return $para;
}

/********************************************************************************/

/**对数组排序
 * @param $array 排序前的数组
 * @param return 排序后的数组
 */
function arg_sort($array) {
    ksort($array);
    reset($array);
    return $array;
}

/********************************************************************************/

/**加密字符串
 * @param $prestr 需要加密的字符串
 * @param return 加密结果
 */
function sign($prestr,$sign_type) {
    $sign='';
    if($sign_type == 'MD5') {
        $sign = md5($prestr);
    }elseif($sign_type =='DSA') {
        //DSA 签名方法待后续开发
        die('DSA 签名方法待后续开发，请先使用MD5签名方式');
    }else {
        die('支付宝暂不支持'.$sign_type.'类型的签名方式');
    }
    return $sign;
}

// 日志消息,把支付宝返回的参数记录下来
function log_result($word='') {
	$fp = fopen("log.txt","a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}

/**实现多种字符编码方式
 * @param $input 需要编码的字符串
 * @param $_output_charset 输出的编码格式
 * @param $_input_charset 输入的编码格式
 * @param return 编码后的字符串
 */
function charset_encode($input,$_output_charset ,$_input_charset) {
    $output = "";
    if(!isset($_output_charset) )$_output_charset  = $_input_charset;
    if($_input_charset == $_output_charset || $input ==null ) {
        $output = $input;
    } elseif (function_exists("mb_convert_encoding")) {
        $output = mb_convert_encoding($input,$_output_charset,$_input_charset);
    } elseif(function_exists("iconv")) {
        $output = iconv($_input_charset,$_output_charset,$input);
    } else die("sorry, you have no libs support for charset change.");
    return $output;
}

/********************************************************************************/

/**实现多种字符解码方式
 * @param $input 需要解码的字符串
 * @param $_output_charset 输出的解码格式
 * @param $_input_charset 输入的解码格式
 * @param return 解码后的字符串
 */
function charset_decode($input,$_input_charset ,$_output_charset) {
    $output = "";
    if(!isset($_input_charset) )$_input_charset  = $_input_charset ;
    if($_input_charset == $_output_charset || $input ==null ) {
        $output = $input;
    } elseif (function_exists("mb_convert_encoding")) {
        $output = mb_convert_encoding($input,$_output_charset,$_input_charset);
    } elseif(function_exists("iconv")) {
        $output = iconv($_input_charset,$_output_charset,$input);
    } else die("sorry, you have no libs support for charset changes.");
    return $output;
}

/*********************************************************************************/

/**用于防钓鱼，调用接口query_timestamp来获取时间戳的处理函数
注意：由于低版本的PHP配置环境不支持远程XML解析，因此必须服务器、本地电脑中装有高版本的PHP配置环境。建议本地调试时使用PHP开发软件
 * @param $partner 合作身份者ID

 * @param return 时间戳字符串
*/
function query_timestamp($partner) {
    $URL = "https://mapi.alipay.com/gateway.do?service=query_timestamp&partner=".$partner;
	$encrypt_key = "";
    return $encrypt_key;
}

class Alipay extends paymentabstract{
	
	public function __construct($config = array()) {	
		if (!empty($config)) $this->set_config($config);
		
	    if ($this->config['service_type']==1) $this->config['service'] = 'trade_create_by_buyer';
		elseif($this->config['service_type']==2) $this->config['service'] = 'create_direct_pay_by_user';
        else $this->config['service'] = 'create_partner_trade_by_buyer';	
        
		$this->config['gateway_url'] = 'https://mapi.alipay.com/gateway.do?_input_charset=utf-8';
		$this->config['gateway_method'] = 'POST';
		$this->config['notify_url'] = return_url('alipay',1);
		$this->config['return_url'] = return_url('alipay');
		
	}

	public function getpreparedata() {		
		$prepare_data['service'] = $this->config['service'];
		$prepare_data['payment_type'] = '1';
		$prepare_data['seller_email'] = $this->config['alipay_account'];
		$prepare_data['partner'] = $this->config['alipay_partner'];
		$prepare_data['_input_charset'] = 'utf-8';		
		$prepare_data['notify_url'] = $this->config['notify_url'];
		$prepare_data['return_url'] = $this->config['return_url'];
		
		// 商品信息
		$prepare_data['subject'] = $this->product_info['name'];
		$prepare_data['price'] = $this->product_info['price'];
		if (array_key_exists('url', $this->product_info)) $prepare_data['show_url'] = $this->product_info['url'];
		$prepare_data['body'] = $this->product_info['body'];
		
		//订单信息
		$prepare_data['out_trade_no'] = $this->order_info['id'];
		$prepare_data['quantity'] = $this->order_info['quantity'];

		// 物流信息
		if($this->config['service'] == 'create_partner_trade_by_buyer' || $this->config['service'] == 'trade_create_by_buyer') {
			$prepare_data['logistics_type'] = 'EXPRESS';
			$prepare_data['logistics_fee'] = '0.00';
			$prepare_data['logistics_payment'] = 'SELLER_PAY';
		}
		//买家信息
		$prepare_data['buyer_email'] = $this->order_info['buyer_email'];
		
		$prepare_data = arg_sort($prepare_data);
		// 数字签名
		$prepare_data['sign'] = build_mysign($prepare_data,$this->config['alipay_key'],'MD5');
		return $prepare_data;
	}
	
	/**
	 * GET接收数据
	 * 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付5交易取消6交易发生错误）
	 */
    public function receive() {
    	$receive_sign = $_GET['sign'];
    	$receive_data = $this->filterParameter($_GET);
    	$receive_data = arg_sort($receive_data);
		
	    $log_data['title'] = 'GET入口数据';
		$log_data['content'] = array2string($_GET);
		D('pay_log')->add($log_data);
			
			
    	if ($receive_data) {
			$verify_result = $this->get_verify('http://notify.alipay.com/trade/notify_query.do?partner=' . $this->config['alipay_partner'] . '&notify_id=' . $receive_data['notify_id']);
			if (preg_match('/true$/i', $verify_result))
			{
				$sign = '';
				$sign = build_mysign($receive_data,$this->config['alipay_key'],'MD5');				
				if ($sign != $receive_sign)
				{
					$log_data['title'] = 'GET签名错误';
					$temp_log['sign'] = $sign;
					$temp_log['receive_sign'] = $receive_sign;
					$log_data['content'] = array2string($temp_log);
					D('pay_log')->add($log_data);
					 E('签名错误');
					return false;
				}
				else
				{
					$return_data['order_id'] = $receive_data['out_trade_no'];
					$return_data['order_total'] = $receive_data['total_fee'];
					$return_data['price'] = $receive_data['price'];
					switch ($receive_data['trade_status'])
					{
						case 'WAIT_BUYER_PAY': $return_data['order_status'] = 3; break;
						case 'WAIT_SELLER_SEND_GOODS': $return_data['order_status'] = 3; break;
						case 'WAIT_BUYER_CONFIRM_GOODS': $return_data['order_status'] = 3; break;
						case 'TRADE_CLOSED': $return_data['order_status'] = 5; break;						
						case 'TRADE_FINISHED': $return_data['order_status'] = 0; break;
						case 'TRADE_SUCCESS': $return_data['order_status'] = 0; break;
						default:
							 $return_data['order_status'] = 5;						
					}	
					$log_data['title'] = 'GET数据返回';
					$log_data['content'] = array2string($return_data);
					D('pay_log')->add($log_data);
					return $return_data;
				}
			}
			else
			{
				$log_data['title'] = 'GET通知错误';
				$log_data['content'] = array2string($verify_result);
				D('pay_log')->add($log_data);
				E('通知错误');
				return false;
			}
		} else {
			$log_data['title'] = 'GET信息返回错误';
			$log_data['content'] = array2string($receive_data);
			D('pay_log')->add($log_data);
			E('信息返回错误');
			return false;
		}   	
    }	

    /**
	 * POST接收数据
	 * 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付 5交易取消6交易发生错误）
	 */
    public function notify() {
			

    	$receive_sign = $_POST['sign'];
    	$receive_data = $this->filterParameter($_POST);
    	$receive_data = arg_sort($receive_data);
		
		$log_data['title'] = 'POST入口数据';
		$log_data['content'] = array2string($_POST);
		D('pay_log')->add($log_data);
			
    	if ($receive_data) {
			$verify_result = $this->get_verify('http://notify.alipay.com/trade/notify_query.do?service=notify_verify&partner=' . $this->config['alipay_partner'] . '&notify_id=' . $receive_data['notify_id']);
			if (preg_match('/true$/i', $verify_result))
			{
				$sign = '';
				$sign = build_mysign($receive_data,$this->config['alipay_key'],'MD5');				
				if ($sign != $receive_sign)
				{		
					$log_data['title'] = 'POST签名错误';
					$temp_log['sign'] = $sign;
					$temp_log['receive_sign'] = $receive_sign;
					$log_data['content'] = array2string($temp_log);
					D('pay_log')->add($log_data);
					
					return false;
				}
				else
				{
					$return_data['order_id'] = $receive_data['out_trade_no'];
					$return_data['order_total'] = $receive_data['total_fee'];
					$return_data['price'] = $receive_data['price'];
					switch ($receive_data['trade_status']) {
						case 'WAIT_BUYER_PAY': $return_data['order_status'] = 3; break;
						case 'WAIT_SELLER_SEND_GOODS': $return_data['order_status'] = 3; break;
						case 'WAIT_BUYER_CONFIRM_GOODS': $return_data['order_status'] = 3; break;
						case 'TRADE_CLOSED': $return_data['order_status'] = 5; break;						
						case 'TRADE_FINISHED': $return_data['order_status'] = 0; break;
						case 'TRADE_SUCCESS': $return_data['order_status'] = 0; break;
						default:
							 $return_data['order_status'] = 5;
					}
					$log_data['title'] = 'POST数据返回';
					$log_data['content'] = array2string($return_data);
					D('pay_log')->add($log_data);
					return $return_data;
				}

			}
			else
			{	
			
				$log_data['title'] = 'POST通知错误';
				$log_data['content'] = array2string($verify_result);
				D('pay_log')->add($log_data);
				return false;
			}
		} else {

			$log_data['title'] = 'POST信息返回错误';
			$log_data['content'] = array2string($receive_data);
			D('pay_log')->add($log_data);
			return false;
		}   	
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
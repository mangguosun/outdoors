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
    $prestr = createlinkstring($sort_array);     	//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
    $prestr = $prestr.$security_code;				//把拼接后的字符串再与安全校验码直接连接起来
    $mysgin = sign($prestr,$sign_type);			    //把最终的字符串加密，获得签名结果
    return $mysgin;
}






/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstring($para) {
	$arg  = "";
	while (list ($key, $val) = each ($para)) {
		$arg.=$key."=".$val."&";
	}
	//去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);
	
	//如果存在转义字符，那么去掉转义
	if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
	
	return $arg;
}

/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstringUrlencode($para) {
	$arg  = "";
	while (list ($key, $val) = each ($para)) {
		$arg.=$key."=".urlencode($val)."&";
	}
	//去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);
	
	//如果存在转义字符，那么去掉转义
	if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
	
	return $arg;
}
/********************************************************************************/
/**
 * 除去数组中的空值和签名参数
 * @param $para 签名参数组
 * return 去掉空值与签名参数后的新签名参数组
 */
function paraFilter($para) {
	$para_filter = array();
	while (list ($key, $val) = each ($para)) {
		if($key == "sign" || $key == "sign_type" || $val == "")continue;
		else	$para_filter[$key] = $para[$key];
	}
	return $para_filter;
}
/********************************************************************************/

/**对数组排序
 * @param $array 排序前的数组
 * @param return 排序后的数组
 */
function argSort($array) {
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
/**
 * RSA签名
 * @param $data 待签名数据
 * @param $private_key_path 商户私钥文件路径
 * return 签名结果
 */
function rsaSign($data, $private_key_path) {
    $priKey = file_get_contents($private_key_path);
    $res = openssl_get_privatekey($priKey);
    openssl_sign($data, $sign, $res);
    openssl_free_key($res);
	//base64编码
    $sign = base64_encode($sign);
    return $sign;
}

/**
 * RSA验签
 * @param $data 待签名数据
 * @param $ali_public_key_path 支付宝的公钥文件路径
 * @param $sign 要校对的的签名结果
 * return 验证结果
 */
function rsaVerify($data, $ali_public_key_path, $sign)  {
	$pubKey = file_get_contents($ali_public_key_path);
    $res = openssl_get_publickey($pubKey);
    $result = (bool)openssl_verify($data, base64_decode($sign), $res);
    openssl_free_key($res);    
    return $result;
}

/**
 * RSA解密
 * @param $content 需要解密的内容，密文
 * @param $private_key_path 商户私钥文件路径
 * return 解密后内容，明文
 */
function rsaDecrypt($content, $private_key_path) {
    $priKey = file_get_contents($private_key_path);
    $res = openssl_get_privatekey($priKey);
	//用base64将内容还原成二进制
    $content = base64_decode($content);
	//把需要解密的内容，按128位拆开解密
    $result  = '';
    for($i = 0; $i < strlen($content)/128; $i++  ) {
        $data = substr($content, $i * 128, 128);
        openssl_private_decrypt($data, $decrypt, $res);
        $result .= $decrypt;
    }
    openssl_free_key($res);
    return $result;
}
// 日志消息,把支付宝返回的参数记录下来
function log_result($word='') {
	$fp = fopen("log.txt","a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}

/**
 * 远程获取数据，POST模式
 * 注意：
 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
 * @param $url 指定URL完整路径地址
 * @param $cacert_url 指定当前工作目录绝对路径
 * @param $para 请求的数据
 * @param $input_charset 编码格式。默认值：空值
 * return 远程输出的数据
 */
function getHttpResponsePOST($url, $cacert_url, $para, $input_charset = '') {

	if (trim($input_charset) != '') {
		//$url = $url."_input_charset=".$input_charset;
	}
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
	curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
	curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
	curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
	curl_setopt($curl,CURLOPT_POST,true); // post传输数据
	curl_setopt($curl,CURLOPT_POSTFIELDS,$para);// post传输数据
	$responseText = curl_exec($curl);
	//var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
	curl_close($curl);
	
	return $responseText;
}

/**
 * 远程获取数据，GET模式
 * 注意：
 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
 * @param $url 指定URL完整路径地址
 * @param $cacert_url 指定当前工作目录绝对路径
 * return 远程输出的数据
 */
function getHttpResponseGET($url,$cacert_url) {
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
	curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
	curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
	$responseText = curl_exec($curl);
	//var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
	curl_close($curl);
	
	return $responseText;
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
/**
 * 实现多种字符编码方式
 * @param $input 需要编码的字符串
 * @param $_output_charset 输出的编码格式
 * @param $_input_charset 输入的编码格式
 * return 编码后的字符串
 */
function charsetEncode($input,$_output_charset ,$_input_charset) {
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
/**
 * 实现多种字符解码方式
 * @param $input 需要解码的字符串
 * @param $_output_charset 输出的解码格式
 * @param $_input_charset 输入的解码格式
 * return 解码后的字符串
 */
function charsetDecode($input,$_input_charset ,$_output_charset) {
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
/**
 * 签名字符串
 * @param $prestr 需要签名的字符串
 * @param $key 私钥
 * return 签名结果
 */
function md5Sign($prestr, $key) {
	$prestr = $prestr . $key;
	return md5($prestr);
}

/**
 * 验证签名
 * @param $prestr 需要签名的字符串
 * @param $sign 签名结果
 * @param $key 私钥
 * return 签名结果
 */
function md5Verify($prestr, $sign, $key) {
	$prestr = $prestr . $key;
	$mysgin = md5($prestr);

	if($mysgin == $sign) {
		return true;
	}
	else {
		return false;
	}
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

class WapAlipay extends paymentabstract{

	public function __construct($config = array()) {	
		if (!empty($config)) $this->set_config($config);
		$this->config['gateway_url'] = 'http://wappaygw.alipay.com/service/rest.htm';
		$this->config['gateway_method'] = 'GET';
		$this->config['notify_url'] = mobile_return_url('wapalipay',1);
		$this->config['call_back_url'] = mobile_return_url('wapalipay');
		$this->config['cacert']    = getcwd().'\\cacert.pem';
		$this->config['transport']    = 'http';
		$this->config['sign_type']    = 'MD5';
		//商户的私钥（后缀是.pen）文件相对路径
		//如果签名方式设置为“0001”时，请设置该参数
		$this->config['private_key_path']	= 'key/rsa_private_key.pem';
		
		//支付宝公钥（后缀是.pen）文件相对路径
		//如果签名方式设置为“0001”时，请设置该参数
		$this->config['ali_public_key_path']= 'key/alipay_public_key.pem';
		//字符编码格式 目前支持 gbk 或 utf-8
		$this->config['input_charset']= 'utf-8';


	}

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
		
		$req_data = '<direct_trade_create_req><notify_url>' . $this->config['notify_url'] . '</notify_url><call_back_url>' . $this->config['call_back_url'] . '</call_back_url><seller_account_name>' . $this->config['alipay_account'] . '</seller_account_name><out_trade_no>' . $this->order_info['id'] . '</out_trade_no><subject>' . $this->product_info['name'] . '</subject><total_fee>' . $this->product_info['price'] . '</total_fee></direct_trade_create_req>';
		//必填
		$req_id = date('Ymdhis');

		//构造要请求的参数数组，无需改动
		$para_token = array(
				"service" => "alipay.wap.trade.create.direct",
				"partner" => $this->config['alipay_partner'],
				"sec_id" => $this->config['sign_type'],
				"format"	=> 'xml',
				"v"	=> '2.0',
				"req_id"	=> $req_id,
				"req_data"	=> $req_data,
				"_input_charset"	=>  trim(strtolower($this->config['input_charset']))
		);

	
		$html_text = $this->buildRequestHttp($para_token);
		//URLDECODE返回的信息
		$html_text = urldecode($html_text);
		//解析远程模拟提交后返回的信息
		$para_html_text = $this->parseResponse($html_text);
		//获取request_token
		$request_token = $para_html_text['request_token'];
		
		//业务详细
		$req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
		//必填
		$prepare_data['service'] = 'alipay.wap.auth.authAndExecute';
		$prepare_data['partner'] = $this->config['alipay_partner'];
		$prepare_data['sec_id'] = $this->config['sign_type'];
		$prepare_data['format']	= 'xml';
		$prepare_data['v']	= '2.0';		
		$prepare_data['req_id'] = $req_id ;
		$prepare_data['req_data'] = $req_data;
		$prepare_data['_input_charset'] = $this->config['input_charset'];
		$para = $this->buildRequestPara($prepare_data);
		return $para;
	}
	
	

	
	/**
     * 生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
	public function buildRequestPara($para_temp) {
		
		
		//除去待签名参数数组中的空值和签名参数
		$para_filter = paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = argSort($para_filter);

		//生成签名结果
		$mysign = build_mysign($para_sort,$this->config['alipay_key'],'MD5');	
		
		//签名结果与签名方式加入请求提交参数组中
		$para_sort['sign'] = $mysign;
		if($para_sort['service'] != 'alipay.wap.trade.create.direct' && $para_sort['service'] != 'alipay.wap.auth.authAndExecute') {
			$para_sort['sign_type'] = strtoupper(trim($this->config['sign_type']));
		}
		
		return $para_sort;
	}
	/**
     * 建立请求，以模拟远程HTTP的POST请求方式构造并获取支付宝的处理结果
     * @param $para_temp 请求参数数组
     * @return 支付宝处理结果
     */
	public function buildRequestHttp($para_temp) {
		$sResult = '';
		
		//待请求参数数组字符串
		$request_data = $this->buildRequestPara($para_temp);

		//远程获取数据
		$sResult = getHttpResponsePOST($this->config['gateway_url'], $this->config['cacert'],$request_data,trim(strtolower($this->config['input_charset'])));

		return $sResult;
	}
	
	/**
     * 解析远程模拟提交后返回的信息
	 * @param $str_text 要解析的字符串
     * @return 解析结果
     */
	public function parseResponse($str_text) {
		//以“&”字符切割字符串
		$para_split = explode('&',$str_text);
		//把切割后的字符串数组变成变量与数值组合的数组
		foreach ($para_split as $item) {
			//获得第一个=字符的位置
			$nPos = strpos($item,'=');
			//获得字符串长度
			$nLen = strlen($item);
			//获得变量名
			$key = substr($item,0,$nPos);
			//获得数值
			$value = substr($item,$nPos+1,$nLen-$nPos-1);
			//放入数组中
			$para_text[$key] = $value;
		}
		
		if( ! empty ($para_text['res_data'])) {
			//解析加密部分字符串
			if($this->config['sign_type'] == '0001') {
				$para_text['res_data'] = rsaDecrypt($para_text['res_data'], $this->config['private_key_path']);
			}
			
			//token从res_data中解析出来（也就是说res_data中已经包含token的内容）
			$doc = new \DOMDocument();
			$doc->loadXML($para_text['res_data']);
			$para_text['request_token'] = $doc->getElementsByTagName( "request_token" )->item(0)->nodeValue;
			
		}
		return $para_text;
	}
	/**
     * 解密
     * @param $input_para 要解密数据
     * @return 解密后结果
     */
	public function decrypt($prestr) {
		return rsaDecrypt($prestr, trim($this->config['private_key_path']));
	}
	/**
     * 异步通知时，对参数做固定排序
     * @param $para 排序前的参数组
     * @return 排序后的参数组
     */
	public function sortNotifyPara($para) {
		$para_sort['service'] = $para['service'];
		$para_sort['v'] = $para['v'];
		$para_sort['sec_id'] = $para['sec_id'];
		$para_sort['notify_data'] = $para['notify_data'];
		return $para_sort;
	}
    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @param $isSort 是否对待签名数组排序
     * @return 签名验证结果
     */
	public function getSignVeryfy($para_temp, $sign, $isSort) {
		//除去待签名参数数组中的空值和签名参数
		$para = paraFilter($para_temp);
		
		//对待签名参数数组排序
		if($isSort) {
			$para = argSort($para);
		} else {
			$para = $this->sortNotifyPara($para);
		}
		
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = createLinkstring($para);
		
		$isSgin = false;
		switch (strtoupper(trim($this->config['sign_type']))) {
			case "MD5" :
				$isSgin = md5Verify($prestr, $sign, $this->config['alipay_key']);
				break;
			case "RSA" :
				$isSgin = rsaVerify($prestr, trim($this->config['ali_public_key_path']), $sign);
				break;
			case "0001" :
				$isSgin = rsaVerify($prestr, trim($this->config['ali_public_key_path']), $sign);
				break;
			default :
				$isSgin = false;
		}
		
		return $isSgin;
	}
    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空 
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
	function getResponse($notify_id) {
		$transport = strtolower(trim($this->config['transport']));
		$partner = trim($this->config['alipay_partner']);
		$veryfy_url = '';
		if($transport == 'https') {
			$veryfy_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
		}
		else {
			$veryfy_url = 'http://notify.alipay.com/trade/notify_query.do?';
		}
		$veryfy_url = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
		$responseTxt = getHttpResponseGET($veryfy_url, $this->alipay_config['cacert']);
		
		return $responseTxt;
	}
	/**
	 * GET接收数据
	 * 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付5交易取消6交易发生错误）
	 */
    public function receive() {
		
		
		
		if(empty($_GET)) {//判断GET来的数组是否为空
			$log_data['title'] = 'GET信息返回错误';
			$log_data['content'] = array2string($receive_data);
			D('pay_log')->add($log_data);
			E('信息返回错误');
			return false;
		}else {
			$receive_data = $this->filterParameter($_GET);
			$receive_data = argSort($receive_data);
			//生成签名结果
			$isSign = $this->getSignVeryfy($receive_data, $_GET["sign"],true);
			if ($isSign) {
				
				
				$return_data['order_id'] = $receive_data['out_trade_no'];
				$return_data['trade_no'] = $receive_data['trade_no'];
				$trade_status = $receive_data['result'];
				if($trade_status == 'success'){
					$return_data['order_status'] = 0;	
				}else{
					$return_data['order_status'] = 5;	
				}
				$log_data['title'] = 'GET数据返回';
				$log_data_array['get'] = $_GET;
				$log_data_array['receive_data'] = $receive_data;
				$log_data_array['isSign'] = $isSign;
				$log_data['content'] = array2string($log_data_array);
				D('pay_log')->add($log_data);
				return $return_data;
				
				
			} else {
				$log_data['title'] = 'GET签名错误';
				$log_data['content'] = array2string($return_data);
				D('pay_log')->add($log_data);
				E('签名错误');
				return false;
			}
		}
    }	

    /**
	 * POST接收数据
	 * 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付 5交易取消6交易发生错误）
	 */
    public function notify() {
			

    	$receive_sign = $_POST['sign'];
    	$receive_data = $this->filterParameter($_POST);
    	$receive_data = argSort($receive_data);
		
		//$log_data['title'] = 'POST入口数据';
//		$log_data['content'] = array2string($_POST);
//		D('pay_log')->add($log_data);
			
    	if ($receive_data) {
			
			
			if ($this->config['sign_type'] == '0001') {
				$receive_data['notify_data'] = rsaDecrypt($receive_data['notify_data'], $this->config['private_key_path']);
			}
			
			//notify_id从receive_data中解析出来（也就是说receive_data中已经包含notify_id的内容）
			$doc = new \DOMDocument();
			$doc->loadXML($receive_data['notify_data']);
			$notify_id = $doc->getElementsByTagName( "notify_id" )->item(0)->nodeValue;
			
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true';
			if (! empty($notify_id)) {$responseTxt = $this->getResponse($notify_id);}
			
			//生成签名结果
			$isSign = $this->getSignVeryfy($receive_data, $_POST["sign"],false);
			
			if (preg_match("/true$/i",$responseTxt) && $isSign) {
				
				
							
				$doc = new \DOMDocument();	
				if ($this->config['sign_type'] == 'MD5') {
					$doc->loadXML($_POST['notify_data']);
				}
				
				if ($this->config['sign_type'] == '0001') {
					$doc->loadXML($this->decrypt($_POST['notify_data']));
				}
				
				if( ! empty($doc->getElementsByTagName( "notify" )->item(0)->nodeValue) ) {

					$return_data['order_id'] =  $doc->getElementsByTagName( "out_trade_no" )->item(0)->nodeValue;
					$return_data['trade_no'] = $doc->getElementsByTagName( "trade_no" )->item(0)->nodeValue;
					$trade_status = $doc->getElementsByTagName( "trade_status" )->item(0)->nodeValue;
					switch ($trade_status)
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
				}
				
				
				$log_data['title'] = 'POST数据返回';
				$log_data_array['get'] = $_POST;
				$log_data_array['receive_data'] = $receive_data;
				$log_data_array['isSign'] = $isSign;
				$log_data_array['trade_status'] = $trade_status;
				$log_data_array['return_data'] = $return_data;
				$log_data['content'] = array2string($log_data_array);
				D('pay_log')->add($log_data);
				
				
				return $return_data;

			} else {
				$log_data['title'] = 'POST通知错误';
				$log_data['content'] = array2string($isSign);
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
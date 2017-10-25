<?php
namespace Pay;

use Pay\Paymentabstract;
use Pay\weixin\WxPayException;
use Pay\weixin\WxPayData;
/**对数组排序
 * @param $array 排序前的数组
* @param return 排序后的数组
*/
function argSort($array) {
	ksort($array);
	reset($array);
	return $array;
}

class WapWeixin extends paymentabstract{
	public function __construct($config = array()) {

		
		if (!empty($config)) $this->set_config($config);
		$this->config['openid']=$this->GetOpenid();
		//$this->config['openid']='oyonAju9cQnlQ4JeyeP_AnIBzLH4';
		$this->config['gateway_url'] = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
		$this->config['serverQueryUrl'] = 'https://api.weixin.qq.com/pay/orderquery?access_token='.$access_token;
		$this->config['serverRefundUrl'] = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
		$this->config['trade_type'] = 'JSAPI';
 		$this->config['device_info']='WEB';
 		$this->config['nonce_str']  =$this->getNonceStr();
		$this->config['notify_url'] = mobile_return_url('WapWeixin',1);
		$this->config['successCallbackUrl'] = mobile_return_url('WapWeixin');
		$this->config['failCallbackUrl'] = mobile_return_url('WapWeixin');
		$this->config['spbill_create_ip']=$_SERVER['REMOTE_ADDR'];
		//var_dump($this->config);
	}

	
	function  log_d($word) 
    {
        
        $fp = fopen('./log_weixin',"a+");
 
        fwrite($fp,"执行日期：".strftime("%Y-%m-%d-%H:%M:%S",time())."n".$word."nn");
       
        fclose($fp);
    }
	
//**************************************************************

	public function getpreparedata() {
		$prepare_data["appid"]           = $this->config['appid'];//公众账号ID
		//$prepare_data["body"]            = $this->product_info['body'];
		$prepare_data["body"]            = 'hdl';//交易描述
		$prepare_data["mch_id"]          = $this->config['mch_id'];//商户号 	
		$prepare_data["device_info"]     = $this->config['device_info'];//设备号		
		$prepare_data['spbill_create_ip']= $this->config['spbill_create_ip'];//终端IP
		$prepare_data["nonce_str"]  	 = $this->config['nonce_str'];//随机字符串			
		//$prepare_data['notify_url']		 = "http://www.zoyohike.com/Mobile/Respond/respond_post/codes/WapWeixin.html";//通知地址
		$prepare_data['notify_url']		 = $this->config['notify_url'];//通知地址
		$prepare_data["out_trade_no"]    = $this->order_info['id'];//交易流水号
		$prepare_data["total_fee"]       = $this->product_info['price']*100;//交易金额
		$prepare_data['trade_type']      = $this->config['trade_type'];
		$prepare_data["openid"]     	 = $this->config['openid']; 	
		$wx_data = $prepare_data;
		$wx_data['sign'] =  $this->MakeSign($wx_data);
		$xml=$this->ToXml($wx_data);

		//调用统一下单
		if($this->CheckSign($wx_data,$wx_data['sign'])){
			$response = $this->postXmlCurl($xml, $this->config['gateway_url'], false, 6);
			$UnifiedOrderResult=$this->FromXml($response);
			$parameters=$this->GetJsApiParameters($UnifiedOrderResult);
		}
		return $parameters;
	}
	
	/**
	* 判断签名，详见签名生成算法是否存在
	* @return true 或 false
	**/
	public function IsSignSet($prepare_data)
	{
		return array_key_exists('sign', $prepare_data);
	}
	
	/**
	 * 
	 * 检测签名
	 */
	public function CheckSign($prepare_data,$getsign)
	{
		if(!$this->IsSignSet($prepare_data)){
			return true;
		}
		$sign = $this->MakeSign($prepare_data);
		if($getsign == $sign){
			return true;
		}
		throw new WxPayException("签名错误！");
	}
	
	
	
	
	/**
	 * 
	 * 获取jsapi支付的参数
	 * @param array $UnifiedOrderResult 统一支付接口返回的数据
	 * @throws WxPayException
	 * 
	 * @return json数据，可直接填入js函数作为参数
	 */

	//XML解析
	public function FromXml($xml){	
		if(!$xml){
			throw new WxPayException("xml数据异常！");
		}
        //将XML转为array 
        $UnifiedOrderResult = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
		return $UnifiedOrderResult;
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
    	$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
    	if($postStr){ 
    		$array = $this->xmlToArray($postStr);
			if($this->checkSign($array,$array['sign']) == true){
				if($array['return_code']=='SUCCESS'){
					if($array['result_code']=='SUCCESS'){ 
						if($array['out_trade_no']){ 
							$return_data['order_status'] = 0;
							$return_data['order_id']=$array['out_trade_no'];
							return $return_data;
						}else{ 
							$log_data['title'] = 'WX-POST参数错误';
							$log_data['content'] = array2string($array);
							D('pay_log')->add($log_data);
							return false;
						}
					}else{ 
						$log_data['title'] = 'WX-POST支付业务错误';
						$log_data['content'] = array2string($array['err_code_des']);
						D('pay_log')->add($log_data);
						return false;
					}
				}else{ 
					$log_data['title'] = 'WX-POST支付通信错误';
					$log_data['content'] = array2string($array['err_code_des']);
					D('pay_log')->add($log_data);
					return false;
				}
			}else{
				$log_data['title'] = 'WX-POST签名验证错误';
				$log_data['content'] = array2string($array);
				D('pay_log')->add($log_data);
				return false;
			}
    	}else{ 
    		$log_data['title'] = 'WX-POST信息返回错误';
			$log_data['content'] = array2string($postStr);
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
 	public function GetJsApiParameters($UnifiedOrderResult){
		if(!array_key_exists("appid", $UnifiedOrderResult)
		|| !array_key_exists("prepay_id", $UnifiedOrderResult)
		|| $UnifiedOrderResult['prepay_id'] == ""){
			throw new WxPayException("参数错误");
		}
		
		$jsapi['appId']=$UnifiedOrderResult["appid"];
		$timeStamp = time();
		$jsapi['timeStamp']="$timeStamp";
		$jsapi['nonceStr']=$this->getNonceStr();
		$jsapi['package']  ="prepay_id=".$UnifiedOrderResult['prepay_id'];
		$jsapi['signType'] ="MD5";
		$jsapi['paySign']  =$this->MakeSign($jsapi);
		$parameters=json_encode($jsapi);
		return $parameters;
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


	public function xmlToArray($xml)
	{		
        //将XML转为array        
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
		return $array_data;
	}

	public function ToXml($prepare_data)
	{
		if(!is_array($prepare_data) 
			|| count($prepare_data) <= 0)
		{
    		throw new WxPayException("数组数据异常！");
    	}
    	
    	$xml = "<xml>";
    	foreach ($prepare_data as $key=>$val)
    	{
    		if (is_numeric($val)){
    			$xml.="<".$key.">".$val."</".$key.">";
    		}else{
    			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
    		}
        }
        $xml.="</xml>";
        return $xml; 
	}
	public function postXmlCurl($xml, $url, $useCert = false, $second = 30){		
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	
		if($useCert == true){
			//设置证书
			//使用证书：cert 与 key 分别属于两个.pem文件
			curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLCERT, WxPayConfig::SSLCERT_PATH);
			curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLKEY, WxPayConfig::SSLKEY_PATH);
		}
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
		$data = curl_exec($ch);
		//返回结果
		if($data){
			curl_close($ch);
			return $data;
		} else { 
			$error = curl_errno($ch);
			curl_close($ch);
			throw new WxPayException("curl出错，错误码:$error");
		}
	}
	
	public function MakeSign($value)
	{
		//签名步骤一：按字典序排序参数
		ksort($value);
		$string = $this->ToUrlParams($value);
		//签名步骤二：在string后加入KEY
		$string = $string . "&key=".$this->config['key'];
		//签名步骤三：MD5加密
		$string = md5($string);
		
		//签名步骤四：所有字符转为大写
		$result = strtoupper($string);
		return $result;
	}

	
	public  function getNonceStr($length = 32) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		} 
		return $str;
	}

	public function GetOpenid()
	{

		//通过code获得openid
		if (!isset($_GET['code'])){
			$trade_sn = I('get.trade_sn');
			$type=I('get.type');
			//触发微信返回code码
			$baseUrl="http://pay.huodongli.cn/pays/index/index";
			$url = $this->__createOauthUrlForCode($baseUrl."?trade_sn=".$trade_sn);
			Header("Location: $url");
			exit();
		} else {
			//获取code码，以获取openid
		    $code = $_GET['code'];
			$openid = $this->getOpenidFromMp($code);
			return $openid;
		}
	}
	public function GetOpenidFromMp($code)
	{
		$url = $this->__CreateOauthUrlForOpenid($code);
		//初始化curl
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOP_TIMEOUT, $this->curl_timeout);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		/*if(WxPayConfig::CURL_PROXY_HOST != "0.0.0.0" 
			&& WxPayConfig::CURL_PROXY_PORT != 0){
			curl_setopt($ch,CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
			curl_setopt($ch,CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
		}*/
		//运行curl，结果以jason形式返回
		$res = curl_exec($ch);
		curl_close($ch);
		//取出openid
		$data = json_decode($res,true);
		$this->data = $data;
		$openid = $data['openid'];
		return $openid;
	}
	private function __CreateOauthUrlForOpenid($code)
	{
		$urlObj["appid"] =$this->config['appid'];
		$urlObj["secret"] = $this->config['appsecret'];
		$urlObj["code"] = $code;
		$urlObj["grant_type"] = "authorization_code";
		$bizString = $this->ToUrlParams($urlObj);
		return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
	}
	private function __CreateOauthUrlForCode($redirectUrl)
	{
		$urlObj["appid"] = $this->config['appid'];
		$urlObj["redirect_uri"] = "$redirectUrl";
		$urlObj["response_type"] = "code";
		$urlObj["scope"] = "snsapi_base";
		$urlObj["state"] = "STATE"."#wechat_redirect";
		$bizString = $this->ToUrlParams($urlObj);
		return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
	}


	private function ToUrlParams($urlObj)
	{
		
		$buff = "";
		foreach ($urlObj as $k => $v)
		{
			if($k != "sign"){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}
}
?>

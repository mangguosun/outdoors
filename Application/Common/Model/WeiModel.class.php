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

class WeiModel {
    /**获取全部没有提示过的消息
     * @param $uid 用户ID
     * @return mixed
     */
   /*微信公众号的类型
	$type 类型
*/
	//自定义菜单路径
	const MENU_CREATE_URL         = 'https://api.weixin.qq.com/cgi-bin/menu/create';//创建
	const MENU_GET_URL            = 'https://api.weixin.qq.com/cgi-bin/menu/get';	//得到
	const MENU_DELETE_URL         = 'https://api.weixin.qq.com/cgi-bin/menu/delete';//删除
	

	private $access_token;
	function get_weixin_public_type($type){ 
		switch ($type) {
			case 0:
				$result="普通订阅号";
				break;
			case 1:
				$result="认证订阅号/普通服务号";
				break;
			case 2:
				$result="认证服务号";
				break;
			default:
				$result="错误信息";
				break;
		}
		return $result;
	}
	function getaccess_token($access){
		$access_token = $access;
		if (!empty($access_token)) {
			return $access_token;
		}else {
			return $this->getAccessToken();
		}
		exit;		
	}
	function getAccessToken() {
		$data['id']=session('mid');
		$data['siteid']=SITEID;
		$mp_list=D('weixin_memberpublic')->where($data)->find();
		$url_get = (('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $mp_list['appid']) . '&secret=') . $mp_list['appsecret'];
		$json = json_decode($this->curlGet($url_get));
		if (!$json->errmsg) {
        } else {
			return ((('获取access_token发生错误：错误代码' . $json->errcode) . ',微信返回错误信息：') . $json->errmsg);
        }
        return $json->access_token;
	
	} 
	function curlGet($url, $method = 'get', $data = ''){		
        $ch = curl_init();
        $headers = array('Accept-Charset: utf-8');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible;MSIE 5.01;Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);
        return $temp;
    }

} 
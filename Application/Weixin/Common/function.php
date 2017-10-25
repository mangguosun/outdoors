<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

function get_token($token = NULL) {
	if ($token !== NULL) {
		session ( 'token', $token );
	} elseif (! empty ( $_REQUEST ['token'] )) {
		session ( 'token', $_REQUEST ['token'] );
	}
	$token = session ( 'token' );
	
	if (empty ( $token )) {
		return - 1;
	}
	
	return $token;
}

// 防超时的file_get_contents改造函数
function wp_file_get_contents($url) {
	$context = stream_context_create ( array (
			'http' => array (
					'timeout' => 30 
			) 
	) ) // 超时时间，单位为秒

	;
	
	return file_get_contents ( $url, 0, $context );
}

function addWeixinLog($data, $data_post = '') {
	$log ['cTime'] = time ();
	$log ['cTime_format'] = date ( 'Y-m-d H:i:s', $log ['cTime'] );
	$log ['data'] = is_array ( $data ) ? serialize ( $data ) : $data;
	$log ['data_post'] = is_array ( $data_post ) ? serialize ( $data_post ) : $data_post;
	D( 'weixin_log' )->add ( $log );
}


// 获取当前用户的OpenId
function get_openid($openid = NULL) {
	$token = get_token ();
	if ($openid !== NULL) {
		session ( 'openid_' . $token, $openid );
	} elseif (! empty ( $_REQUEST ['openid'] )) {
		session ( 'openid_' . $token, $_REQUEST ['openid'] );
	}
	$openid = session ( 'openid_' . $token );
	$isWeixinBrowser = isWeixinBrowser ();
	if (empty ( $openid ) && $isWeixinBrowser) {
		$callback = GetCurUrl ();
		OAuthWeixin ( $callback );
	}
	
	if (empty ( $openid )) {
		return - 1;
	}
	
	return $openid;
}
function isWeixinBrowser() {
	$agent = $_SERVER ['HTTP_USER_AGENT'];
	if (! strpos ( $agent, "icroMessenger" )) {
		return false;
	}
	return true;
}
function GetCurUrl() {
	$url = 'http://';
	if (isset ( $_SERVER ['HTTPS'] ) && $_SERVER ['HTTPS'] == 'on') {
		$url = 'https://';
	}
	if ($_SERVER ['SERVER_PORT'] != '80') {
		$url .= $_SERVER ['HTTP_HOST'] . ':' . $_SERVER ['SERVER_PORT'] . $_SERVER ['REQUEST_URI'];
	} else {
		$url .= $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
	}
	// 兼容后面的参数组装
	if (stripos ( $url, '?' ) === false) {
		$url .= '?t=' . time ();
	}
	return $url;
}


function OAuthWeixin($callback) {
	$isWeixinBrowser = isWeixinBrowser ();
	$info = get_token_appinfo ();
	if (! $isWeixinBrowser || $info ['type'] != 2 || empty ( $info ['appid'] )) {
		redirect ( $callback . '&openid=-1' );
	}
	$param ['appid'] = $info ['appid'];
	if (! isset ( $_GET ['getOpenId'] )) {
		$param ['redirect_uri'] = $callback . '&getOpenId=1';
		$param ['response_type'] = 'code';
		$param ['scope'] = 'snsapi_base';
		$param ['state'] = 123;
		$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query ( $param ) . '#wechat_redirect';
		redirect ( $url );
	} elseif ($_GET ['state']) {
		$param ['secret'] = $info ['secret'];
		$param ['code'] = I ( 'code' );
		$param ['grant_type'] = 'authorization_code';
		
		$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?' . http_build_query ( $param );
		$content = file_get_contents ( $url );
		$content = json_decode ( $content, true );
		redirect ( $callback . '&openid=' . $content ['openid'] );
	}
}
function get_token_appinfo($token = '') {
	empty ( $token ) && $token = get_token ();
	$map ['token'] = $token;
	$info = M ( 'weixin_memberpublic' )->where ( $map )->find ();
	return $info;
}



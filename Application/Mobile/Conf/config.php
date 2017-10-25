<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.thinkphp.cn>
// +----------------------------------------------------------------------

/**
 * 前台配置文件
 * 所有除开系统级别的前台配置
 */
//回调地址
 $website_url=$_SERVER['HTTP_HOST'];
 $SITE_URL = "http://beta.huodongli.cn/mobile";
 define('URL_CALLBACK', "" . $SITE_URL . "/User/callback.html?siteid=".SITEID."&website_url=".$website_url."&type=");
 //$SITE_URL = "http://beta.huodongli.cn/Mobile/Index/callback.html";
 //define('URL_CALLBACK', "" . $SITE_URL . "/type/");
  // define('URL_CALLBACK', 'http://beta.huodongli.cn/Mobile/Index/callback.html/type=');
return array(

    // 预先加载的标签库
    'TAGLIB_PRE_LOAD'     =>    'OT\\TagLib\\Article,OT\\TagLib\\Think',
        
    /* 主题设置 */
    'DEFAULT_THEME' =>  'ios',  // 默认模板主题名称
	//'DEFAULT_THEME' =>  'default',  // 默认模板主题名称
    /* 数据缓存设置 */
    'DATA_CACHE_PREFIX' => 'onethink_', // 缓存前缀
    'DATA_CACHE_TYPE'   => 'File', // 数据缓存类型


    /* 模板相关配置 */
    'TMPL_PARSE_STRING' => array(
        '__STATIC__' => __ROOT__ . '/Public/static',
        '__ADDONS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/Addons',
        '__IMG__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/images',
        '__CSS__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/css',
        '__JS__'     => __ROOT__ . '/Public/' . MODULE_NAME . '/js',
    ),

    //腾讯QQ登录配置
    'THINK_SDK_QQ' => array(
        'APP_KEY' => '101234530', //应用注册成功后分配的 APP ID
        'APP_SECRET' => 'fd9113d26b1872e46aeb6d3778ab8f3b', //应用注册成功后分配的KEY
        'CALLBACK' => URL_CALLBACK . 'qq',
       // 'CALLBACK' => 'http://beta.huodongli.cn/Mobile/Index/index.html',
    ),

    /* SESSION 和 COOKIE 配置 */
    'SESSION_PREFIX' => 'onethink_home', //session前缀
    'COOKIE_PREFIX'  => 'onethink_home_', // Cookie前缀 避免冲突

 
  
);
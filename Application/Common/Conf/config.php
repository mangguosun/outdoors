<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com><http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
* 系统配文件
* 所有系统级别的配置
*/



return array(
    /* 模块相关配置 */
    'AUTOLOAD_NAMESPACE' => array('Addons' => ONETHINK_ADDON_PATH), //扩展模块列表
    'DEFAULT_MODULE'     => 'Home',
    'MODULE_DENY_LIST'   => array('Common', 'User'),
    //'MODULE_ALLOW_LIST'  => array('Home','Admin'),
     
    /* 系统数据加密设置 */
    'DATA_AUTH_KEY' => 'vk0lQBLK`6"c&d-AbDtu[F47$;i~%!#YCZGRh^>?', //默认数据加密KEY

    /* 调试配置 */
    'SHOW_PAGE_TRACE' => false,

    /* 用户相关设置 */
    'USER_MAX_CACHE'     => 1000, //最大缓存用户数
    'USER_ADMINISTRATOR' => 1, //管理员用户ID

    /* URL配置 */
    'URL_CASE_INSENSITIVE' => true, //默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'            => 2, //URL模式  默认关闭伪静态
    'VAR_URL_PARAMS'       => '', // PATHINFO URL参数变量
    'URL_PATHINFO_DEPR'    => '/', //PATHINFO URL分割符
    
    /* 全局过滤配置 */
    'DEFAULT_FILTER' => '', //全局过滤函数

    /* 数据库配置 */
    'DB_TYPE'   => 'mysql', // 数据库类型
    'DB_HOST'   => '127.0.0.1', // 服务器地址
    'DB_NAME'   => '10wl_v1', // 数据库名
    'DB_USER'   => 'root', // 用户名
    'DB_PWD'    => '',  // 密码
    'DB_PORT'   => '3306', // 端口
    'DB_PREFIX' => 'thinkox_', // 数据库表前缀

    /* 文档模型配置 (文档模型核心配置，请勿更改) */
    'DOCUMENT_MODEL_TYPE' => array(2 => '主题', 1 => '目录', 3 => '段落'),
   
    /*  加载扩展配置文件 */
    'LOAD_EXT_CONFIG' => 'router',
    /* 文件上传相关配置 */
    'DOWNLOAD_UPLOAD' => array(
    'mimes'    => '', //允许上传的文件MiMe类型
    'maxSize'  => 5*1024*1024, //上传的文件大小限制 (0-不做限制)
    'exts'     => 'jpg,gif,png,jpeg,zip,rar,tar,gz,7z,doc,docx,txt,xml', //允许上传的文件后缀
    'autoSub'  => true, //自动子目录保存文件
    'subName'  => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
    'rootPath' => './Uploads/Download/', //保存根路径
    'savePath' => '', //保存路径
    'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
    'saveExt'  => '', //文件保存后缀，空则使用原后缀
    'replace'  => false, //存在同名是否覆盖
    'hash'     => true, //是否生成hash编码
    'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ), //下载模型上传配置（文件上传类配置）


    /* 图片上传相关配置 */
    'PICTURE_UPLOAD' => array(
    'mimes'    => '', //允许上传的文件MiMe类型
    'maxSize'  => 2*1024*1024, //上传的文件大小限制 (0-不做限制)
    'exts'     => 'jpg,gif,png,jpeg,ico', //允许上传的文件后缀
    'autoSub'  => true, //自动子目录保存文件
    'subName'  => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
    'rootPath' => './Uploads/Picture/', //保存根路径
    'savePath' => '', //保存路径
    'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
    'saveExt'  => '', //文件保存后缀，空则使用原后缀
    'replace'  => true, //存在同名是否覆盖
    'hash'     => true, //是否生成hash编码
    'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ), //图片上传相关配置（文件上传类配置）

    'PICTURE_UPLOAD_DRIVER'=>'local',
    'DOWNLOAD_UPLOAD_DRIVER'=>'local',
    //本地上传文件驱动配置
    'UPLOAD_LOCAL_CONFIG'=>array(),

    'UPLOAD_QINIU_CONFIG'=>array(
    'accessKey'=>'xNBzD_m7hoJrb60Qtyomdr2WVJSb4vnO0MkSYCym',
    'secrectKey'=>'g-p3P9vOWrg_WZOd_3OtL4FNTh8xhgBOeEVPJw3Y',
    'bucket'=>'tox-shang',
    'domain'=>'tox-shang.qiniudn.com',
    'timeout'=>3600,
    ),


    /* 编辑器图片上传相关配置 */
    'EDITOR_UPLOAD' => array(
    'mimes'    => '', //允许上传的文件MiMe类型
    'maxSize'  => 2*1024*1024, //上传的文件大小限制 (0-不做限制)
    'exts'     => 'jpg,gif,png,jpeg', //允许上传的文件后缀
    'autoSub'  => true, //自动子目录保存文件
    'subName'  => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
    'rootPath' => './Uploads/Editor/', //保存根路径
    'savePath' => '', //保存路径
    'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
    'saveExt'  => '', //文件保存后缀，空则使用原后缀
    'replace'  => false, //存在同名是否覆盖
    'hash'     => true, //是否生成hash编码
    'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ),
	//'CdnImgUrl'=> true,
	//'CDNHDL'=>'http://hdlcdn.com',
	/**************邮件配置*******************/
	'MAIL_ADDRESS'=>'info@huodongli.cn', // 邮箱地址
	'MAIL_SMTP'=>'smtp.qq.com', // 邮箱SMTP服务器
	'MAIL_LOGINNAME'=>'info@huodongli.cn', // 邮箱登录帐号
	'MAIL_PASSWORD'=>'hdl004', // 邮箱密码
	/*************************************/
	/***************短信apikey设置**********************/
	 'SMSKEY' => '35d030eaf78564a036ed07d0394e6e6f',
	 
	/***************************************/
);

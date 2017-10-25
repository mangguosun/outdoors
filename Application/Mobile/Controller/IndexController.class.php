<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Mobile\Controller;
use OT\DataDictionary;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class IndexController extends MobileController {
	 public function _initialize()
    {
        $tree = D('EventType')->where(array('status' => 1))->select();
		$url = $_SERVER['QUERY_STRING'];
		$url_arr = explode('/',$url);
		$dest_url = $url_arr[2];
		$dest_url = $dest_url == ''?'index':$url_arr[2];
		$this->assign('dest_url',$dest_url);
        $this->assign('tree', $tree);
    }
    public function index2()
    {	
        $this->display();
	}
	
	//系统首页
    public function index()
    {	
		if($_GET['login_type']==2){ 
            D('UcMemberpublic')->updateLogin($_GET['uid']);
            D('Memberpublic')->login($_GET['uid'],$remember=='on');
            redirect(U('Mobile/Index/index'));  
        }
        $this->display();
    }
	/*
     * 获取表情列表。
     */
    public function getSmile()
    {
        //这段代码不是测试代码，请勿删除
        exit(json_encode(D('Common/Expression')->getAllExpression()));
    }
}
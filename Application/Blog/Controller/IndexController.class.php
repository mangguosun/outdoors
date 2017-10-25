<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Blog\Controller;

use OT\DataDictionary;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class IndexController extends BlogController
{

    //系统首页
    public function index($page = 1)
    {

        /* 分类信息 */
        $category = 0; //$this->category();

        /* 获取当前分类列表 */
	    $map['siteid']=SITEID;
		$map['status']=1;
        $count=D('document')->where($map)->count();
        $Page       = new \Think\Page($count,10);// 
		$Page->setConfig('theme'," %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u>");
		$show       = $Page->show();
		$list = D('document')->where($map)->limit($Page->firstRow.','.$Page->listRows)->order("id desc")->select();
	    if (false === $list) {
            $this->error('获取列表数据失败！');
        }
        /* 模板赋值并渲染模板 */
        $this->assign('category', $category);
        $this->assign('list', $list);
        $this->assign('page',$show); //分页
        $this->display();
    }

    /* 文档分类检测 */
    private function category($id = 0)
    {

    }
}
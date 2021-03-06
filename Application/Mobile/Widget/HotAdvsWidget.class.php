<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Mobile\Widget;

use Think\Action;

/**
 * 分类widget
 * 用于动态调用分类信息
 */
class HotAdvsWidget extends Action
{

    /* 显示指定分类的同级分类或子分类列表 */
    public function lists($limit = 5)
    {
		$peoples = D('advs_banner')->where('status=1 and siteid = '.SITEID)->order('create_time desc')->limit($limit)->select();	
        $this->assign('lists', $peoples);
        $this->display('Widget/hotadvs');
    }

}

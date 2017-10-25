<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Shop\Widget;

use Think\Action;

/**
 * 分类widget
 * 用于动态调用分类信息
 */
class HotWidget extends Action
{

    /* 显示指定分类的同级分类或子分类列表 */
    public function lists($limit = 5)
    {
        $Document=D('Document');
        $map['status']=1;
		$map['siteid']=SITEID;
        //$map['time']=array('gt',time()-$timespan);//一周以内
        $lists = $Document->where($map)->order('id desc')->limit($limit)->select();
        $this->assign('lists', $lists);
        $this->assign('category',$category);
        $this->display('Widget/hot');
    }

}

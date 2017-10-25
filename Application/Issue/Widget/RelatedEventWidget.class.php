<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Issue\Widget;

use Think\Action;

/**
 * 推荐活动widget
 * 用于动态调用分类信息
 */
class RelatedEventWidget extends Action
{

    /* 显示指定分类的同级分类或子分类列表 */
    public function relatedEvent($issue_id)
    {
		$event_re_id = D('issue_content')->where(array('id'=>$issue_id,'status'=>1,'siteid'=>SITEID))->getField('related_event');
		$event_info = D('event')->where(array('id'=>$event_re_id,'siteid'=>SITEID,'status'=>1))->find();
		$this->assign('event_info',$event_info);
		$this->display('Widget/event_related');
    }
}

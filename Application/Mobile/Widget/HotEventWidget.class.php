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
class HotEventWidget extends Action
{

    /* 显示指定分类的同级分类或子分类列表 */
    public function lists($limit = 5)
    {
		$rs_issue = D('issue_content')->where(array('status'=>1,'is_recommend'=>1,'siteid'=>SITEID))->order("view_count desc")->limit($limit)->select();
		foreach ($rs_issue as &$v) {
            $v['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['issue'] = D('Issue')->field('id,title')->find($v['issue_id']);
        }
		
		$map['status'] = 1;
		$map['is_recommend'] = 1;
		$map['siteid'] = SITEID;
        $rec_event = D('Event')->where($map)->limit($limit)->order('rand()')->select();
        foreach ($rec_event as &$v) {
            $v['user'] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['type'] = $this->getType($v['type_id']);
            $v['check_isSign'] = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $v['id']))->select();
        }
        unset($v);
        $this->assign('rec_event', $rec_event);
        $this->display('Widget/hotevent');
    }
    /**
     * 获取活动类型
     * @param $type_id
     * @return mixed
     * autor:xjw129xjt
     */
    private function getType($type_id)
    {

        $type = D('EventType')->where('id=' . $type_id)->find();
        return $type;
    }
}

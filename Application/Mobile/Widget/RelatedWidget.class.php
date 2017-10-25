<?php

namespace Mobile\Widget;
use Think\Action;
/**
 * 推荐活动widget
 * 用于动态调用分类信息
 */
class RelatedWidget extends Action
{

    /* 显示指定分类的同级分类或子分类列表 */
    public function RelatedHotEvent($issue_id)
    {
		$event_re_id = D('issue_content')->where(array('id'=>$issue_id,'status'=>1,'siteid'=>SITEID))->getField('related_event');
		if($event_re_id){
			$event_recommend_info = D('Event')->where(array('is_recommend' => 1,'siteid'=>SITEID,'id'=>array('neq',$event_re_id)))->limit(1)->order('rand()')->getField('id');
			$aaa[0] = $event_re_id;
			if($event_recommend_info){
				$aaa[1] = $event_recommend_info;
			}
			$event_info = D('event')->where(array('id'=>array('in',$aaa),'siteid'=>SITEID,'status'=>1))->select();
		}else{
			$event_info = D('Event')->where(array('is_recommend' => 1,'siteid'=>SITEID,'status'=>1))->limit(2)->order('rand()')->select();
		}
		foreach ($event_info as &$v) {
            $v['user'] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
			if($v['lasted_time'] != 0){
				$v['lasted_time'] = date("Y-m-d",$v['lasted_time']);
			}else{
				$v['lasted_time'] ='无';
			}
        }
		$this->assign('event_relatedhot',$event_info);
		$this->display('Widget/relatedhot_event');
    }
}

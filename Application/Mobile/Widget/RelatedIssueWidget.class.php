<?php
    
namespace Mobile\Widget;
use Think\Action;
/**
 * 推荐旅行故事widget
 */
 class RelatedIssueWidget extends Action
 {
	 public function relatedissue($event_id)
	 {
		$issue_arr = D('issue_content')->where(array('status'=>1,'related_event'=>$event_id,'siteid'=>SITEID))->order("view_count desc")->limit(2)->select();
		foreach ($issue_arr as &$v) {
            $v['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['issue'] = D('Issue')->field('id,title')->find($v['issue_id']);
        }
		$issue_total = count($issue_arr);
		$this->assign('issue_arr', $issue_arr);
		$this->assign('issue_total', $issue_total);
		$this->display('Widget/related_issue');
	 }
 
 
 
 }

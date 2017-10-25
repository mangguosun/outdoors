<?php
    
namespace Issue\Widget;

use Think\Action;
header("content-type:text/html;charset=utf-8");
/**
 * ÍÆ¼öÂÃĞĞ¹ÊÊÂwidget
 */
 class RecommendIssueWidget extends Action
 {
	 public function recommendIssue($limit = 5)
	 {
		$rs_issue = D('issue_content')->where(array('status'=>1,'is_recommend'=>1,'siteid'=>SITEID))->order("view_count desc")->limit($limit)->select();
		foreach ($rs_issue as &$v) {
            $v['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['issue'] = D('Issue')->field('id,title')->find($v['issue_id']);
        }
	    $this->assign('rs_issue', $rs_issue);
		//$this->display(T('Event@Widget/issue'));
		$this->display('Widget/issue');
	
	 }
 
 
 
 }

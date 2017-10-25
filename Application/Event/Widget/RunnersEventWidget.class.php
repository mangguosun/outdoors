<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Event\Widget;

use Think\Action;

/**
 * 推荐活动widget
 * 用于动态调用分类信息
 */
class RunnersEventWidget extends Action
{
    /* 服务service */
    public function RunnersEventService($limit = 5)
    {	
    	
        $member_service=D('member_service');
		$lists = $member_service->where(array('siteid'=>SITEID,'status'=>1))->select();
	    $this->assign('lists', $lists);
        $this->display('Widget/service');
    }

    /*推荐故事Issue*/
    public function RunnersEventIssue($limit = 5)
	{	
		$rs_issue = D('issue_content')->where(array('status'=>1,'is_recommend'=>1,'siteid'=>SITEID))->order("view_count desc")->limit($limit)->select();
		foreach ($rs_issue as &$v) {
            $v['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['issue'] = D('Issue')->field('id,title')->find($v['issue_id']);
        }
		$this->assign('rs_issue', $rs_issue);
		$this->display('Widget/issue');
	}
	/*相关故事Issue*/
	public function RunnersEventRelatedIssue($event_id)
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


	/*推荐商品shop*/
	public function RunnersEventShop($limit = 5)
	{	
		
		$rs_shop = D('shop')->where(array('status'=>1,'is_recommend'=>1,'siteid'=>SITEID))->order("view_count desc")->limit($limit)->select();
		foreach ($rs_shop as &$v) {
            $v['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['shop'] = D('shop')->field('id,goods_name')->find($v['id']);
        }
		$this->assign('rs_shop', $rs_shop);
		//$this->display(T('Event@Widget/issue'));
		$this->display('Widget/recommend_shop');
	}

	/*活动Event*/
	public function RunnersEventRecommendEvent($limit = 3)
    {
		
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
        //$this->display(T('Event@Widget/recommend'));
		$this->display('Widget/recommend');
    }

    /*对比sex*/
    public function RunnersEventSexCompare($id = '')
    {	
    	
		$map['siteid'] = SITEID;
		$map['event_id'] = $id;
		$map['order_status'] = array('gt',0);
		$map['status'] = 1;
		$signer_arr = D('event_signer')->where($map)->select();
		$men_num = 0;
		$women_num = 0;
		foreach($signer_arr as $key => $val){
			$user_info = json_decode($val['user_info'],true);
			$user_info['sex'] == 1 ? $men_num++ : $women_num++ ;
		}

		$this->assign('signer_arr',$signer_arr);
		$this->assign('men_num',$men_num);
		$this->assign('women_num',$women_num);
		$this->display('Widget/sexcompare');
    }

    /*私人定制TailorEvent*/
    public function  RunnersEventTailorEvent()
    {	

		$tailor_color = get_webinfo('template_color');
		$template_info = get_template_color();
		$this->assign('tailor_color', $tailor_color);
        $this->assign('template_info', $template_info);
        $this->display('Widget/tailor');
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

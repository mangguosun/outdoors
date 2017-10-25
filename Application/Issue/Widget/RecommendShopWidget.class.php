<?php
    

namespace Issue\Widget;
use Think\Action;
/**
 * ÍÆ¼öÂÃÐÐ¹ÊÊÂwidget
 */
 class RecommendShopWidget extends Action
 {
	 public function recommendShop($limit = 5)
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
 
 
 
 }

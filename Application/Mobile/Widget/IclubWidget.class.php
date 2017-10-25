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
class IclubWidget extends Action
{
	
	public function recommend($where='recommend',$limit = 4)
    {
		$content =D('Shop')->get_lists($where,$limit); 
		$this->assign('shop_recommend', $content);
		$this->display('Widget/recommend');
    }
	
	public function bargain($where='bargain',$limit=2)
    {
		$content =D('Shop')->get_lists($where,$limit);
		
		foreach($content as $key=>$v){
			$limit_bargain	=	D('shop')->shop_bargain($v['id']);
			$content[$key]['bargain_price']=$limit_bargain['bargain_price'];
			$content[$key]['overtime']=$limit_bargain['overtime']*1000;
			//dump($limit_bargain);
		}	
		$this->assign('shop_discount_data', $content);
        $this->display('Widget/shop_bargain');
    }
	
	
	
    /* 显示指定分类的同级分类或子分类列表 */
    public function event($where='new',$limit = 10)
    {
		
		$order='diff_time asc';
		
		$map = "status = 1 and siteid=".SITEID;
		switch ($where)
		{
		case 'recommend':
		  $map .= " and is_recommend = 1";
		  $order='sort desc';
		  break;
		case 'new':
			$type_id = intval($_GET['type_id']);
			$tag = intval($_GET['tag']);
			$starttime = intval($_GET['starttime']);
			$price = intval($_GET['price']);	
			$holiday = intval($_GET['holiday']);
			$finalcity = intval($_GET['finalcity']);
			$days = intval($_GET['days']);
			$keywords = trim(op_t(I('keywords')));
			if($keywords != ''){
				$map .= " and title like '%$keywords%'";
			}
			//判断get过来的类型
			if($type_id != ''){
				$map .= " and type_id = $type_id";
			}
			if($tag != ''){
				$map .= " and find_in_set($tag,tag)";
			}		
			if($finalcity){
				$getdata = D('district')->where(array('id'=>$finalcity))->find();
				if($getdata){
					$map .= " and finalcity in($getdata[arrchildid])";
				}
			}	
			if($starttime){
				switch($starttime){
					case '13';					
						$map .= " and WEEKDAY(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 5 or WEEKDAY(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 6";
					break;
					case '14';
						$map .= " and DAYOFYEAR(FROM_UNIXTIME(abs(lasted_time - UNIX_TIMESTAMP(NOW())))) <= 30 and DAYOFYEAR(FROM_UNIXTIME(abs(lasted_time - UNIX_TIMESTAMP(NOW())))) > 0"; 
					break;
					default:
						$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = $starttime";
				}
			}
			
	
			switch($price){
				case '1';
					$map .= " and price <= 1000 and price > 0";
				break;
				case '2';
					$map .= " and price > 1000 and price <= 2000";
				break;
				case '3';
					$map .= " and price > 2000 and price <= 3000";
				break;
				case '4';
					$map .= " and price > 3000 and price <= 4000";
				break;
				case '5';
					$map .= " and price > 4000 and price <= 5000)";
				break;
				case '6';
					$map .= " and price >= 5000";
				break;
				default:
				break;
			}
			switch ($days) {
				case '1':
					$map .= " and traveldays >= 1 and traveldays <= 2";
					break;
				case '2':
					$map .= " and traveldays >= 3 and traveldays <= 7";
					break;
				case '3':
					$map .= " and traveldays > 7";
					break;
				default:
					break;
			}
			switch($holiday){
				case '1';
					$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 1 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 1 and 3 and lasted_time != 0";
				break;
				case '2';
					$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 2 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 17 and 24";
				break;
				case '3';
					$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 4 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 4 and 6";
				break;
				case '4';
					$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 5 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 1 and 3";
				break;
				case '5';
					$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 6 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 20 and 22";
				break;
				case '6';
					$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 7 and 8";
				break;
				case '7';
					$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 9 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 27 and 29";
				break;
				case '8';
					$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 10 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 1 and 7";
				break;
				case '9';
					$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) = 12 and DAYOFMONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 25 and 27";
				break;
				case '10';				
					$map .= " and MONTH(FROM_UNIXTIME(lasted_time,'%Y-%m-%d')) between 1 and 2";
				break;
				default:
				break;
			}
		  break;
		default:
		 
		}
		
		$content = D('Event')
		->where($map)
		->order($order)
		->limit(0,$limit)
		->getField('id,siteid,uid,title,description,create_time,pictures_id,cover_id,attentionCount,status,update_time,view_count,reply_count,type_id,signCount,minpeople,maxpeople,begincity,finalcity,is_recommend,traveldays,paytype,frontmoney,price,strength_level,adresss,scene_level,fun_level,human_level,money_level,travel_table,tag,detailadd,lasted_time,diff_time,price_text,insurance,custom,sort');
        foreach ($content as &$v) {          
			if($v['lasted_time'] != 0){
				$v['lasted_time'] = date("Y-m-d",$v['lasted_time']);
			}else{
				$v['lasted_time'] ='敬请期待';
			}		
			$event_content['tagarr'] = explode(',',$v['tag']);
			foreach ($event_content['tagarr'] as $key => $a) {
				$v['tags'][$a]['id'] = $a;
				$v['tags'][$a]['name'] = get_event_tag($a);
			}


			$view_status = D('Common/MobileEvent')->getCalendarList($v['id']);
			$v['view_status'] = $view_status['view_status'];

			$v['url'] =U('Mobile/Event/detail',array('id'=>$v['id']));
			$v['thumb'] =getThumbImageById($v['cover_id'],400,300);
			$begincity = get_citys($v['begincity']);
			$finalcity = get_citys($v['finalcity']);
			
			$v['begincity_province'] = get_city($begincity['province']);
			$v['begincity_city'] = get_city($begincity['city']);
		
			$v['finalcity_province'] = get_city($finalcity['province']);
			$v['finalcity_city'] = get_city($finalcity['city']);
			
			
			
        }
        unset($v);
		$this->assign('event_data', $content);
        $this->display('Widget/event');
    }
    /* 显示指定分类的同级分类或子分类列表 */
    public function issue($where='new',$limit = 10)
    {
		
		$order='id desc';
		$map = "status = 1 and siteid=".SITEID;
		switch ($where)
		{
		case 'recommend':
		  $map .= " and is_recommend = 1";
		  $order='id desc';
		  break;
		case 'new':
		
		
		$keywords = trim(op_t(I('keywords')));
		$issue_id = intval($_GET['issue_id']);
		
		if($keywords != ''){
			$map .= " and title like '%$keywords%'";
		}
		  break;
		
		
		default:
		 
		}
	    $content = D('IssueContent')
		->where($map)
		->order($order)
		->limit(0,$limit)
		->getField('id,siteid,title,view_count,cover_id,issue_id,uid,reply_count,create_time,update_time,status,url,finalcity,is_recommend,tag,recommend_brand,related_event');
	   foreach ($content as &$v) {
            $v['user'] = query_user(array('id', 'nickname', 'mobile_space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['category'] = D('Issue')->field('id,title')->where(array('id'=>$v['issue_id'],'siteid'=>SITEID))->find();
			$v['url'] =U('Mobile/Issue/issuecontentdetail',array('id'=>$v['id']));
			$v['thumb'] =getThumbImageById($v['cover_id'],400,300);
			$finalcity = get_citys($v['finalcity']);
			$v['finalcity_province'] = get_city($finalcity['province']);
			$v['finalcity_city'] = get_city($finalcity['city']);
			
			$v['comment_count'] = D('local_comment')->where(array('app'=>'Issue','row_id'=>$v['id'],'siteid'=>SITEID))->count();
			$v['collect_count'] = D('forum_bookmark')->where(array('issue_id'=>$v['id'],'siteid'=>SITEID))->count();
			
        }
		unset($v);
		$this->assign('issue_data', $content);
        $this->display('Widget/issue');
    }
    /* 显示指定分类的同级分类或子分类列表 */
    public function blog($where='new',$limit = 10)
    {
		
		$order='id desc';
		$map = "status = 1 and siteid=".SITEID;
		switch ($where)
		{
		case 'recommend':
		  $map .= " and is_recommend = 1";
		  $order='id desc';
		  break;
		case 'new':
		$keywords = trim(op_t(I('keywords')));
		$issue_id = intval($_GET['issue_id']);
		
		if($keywords != ''){
			$map .= " and title like '%$keywords%'";
		}
		  break;
		default:
		 
		}
	    $content = D('document')
		->where($map)
		->order($order)
		->limit(0,$limit)
		->select();
		
	   foreach ($content as &$v) {
            $v['user'] = query_user(array('id', 'nickname', 'mobile_space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['category'] = D('category')->field('id,title')->where(array('id'=>$v['category_id'],'siteid'=>SITEID))->find();
			$v['url'] =U('Mobile/Blog/detail',array('id'=>$v['id']));
			$v['comment_count'] = D('local_comment')->where(array('app'=>'Blog','row_id'=>$v['id'],'siteid'=>SITEID))->count();
        }
		unset($v);
		$this->assign('blog_data', $content);
        $this->display('Widget/blog');
    }
    /* 显示指定分类的同级分类或子分类列表 */
    public function shop($where='new',$limit = 10)
    {
		$content =D('Shop')->get_lists($where,$limit); 
		if($where =='new'){
			 $this->assign('shop_new_data', $content);
			 $this->display('Widget/shop_new');
		}elseif($where =='recommend'){
			$this->assign('shop_recommend_data', $content);
			$this->display('Widget/shop_recommend');
		}elseif($where == 'widget'){
			$content =D('Shop')->get_lists('recommend',$limit);
			$this->assign('shop_recommend_data', $content);
			$this->display('Widget/shop_recommend_show');

		}else{
			$this->assign('shop_discount_data', $content);
			 $this->display('Widget/shop_discount');
		}
    }



}

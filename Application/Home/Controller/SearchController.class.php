<?php

namespace Home\Controller;

use Think\Controller;
/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class SearchController extends HomeController {
	
	public function seek($name,$status='')
    {
		if($name!=''){
			$name	=	urlsafe_b64encode($name);
		$url=U('Home/Search/index',array('name'=>$name,'status'=>$status));
			header("Location:$url");
		}else{
			$this->error('请填写查询内容');
		}
	}
	
	public function index($page=1)
    {
    	$map['siteid']=SITEID;
		$status=I('status');
		
		if(!$status) $status=0;

		if(($_GET['name'] != '' )){
			$name = $_GET['name'];
			//$get_name = urlencode(iconv("gb2312","utf-8",$name));
			$url['status_0']=U('Home/Search/index',array('status'=>0,'name'=>$name));
			$url['status_1']=U('Home/Search/index',array('status'=>1,'name'=>$name));
			$url['status_2']=U('Home/Search/index',array('status'=>2,'name'=>$name));
			$url['status_3']=U('Home/Search/index',array('status'=>3,'name'=>$name));
			$url['status_4']=U('Home/Search/index',array('status'=>4,'name'=>$name));
		}else{
				$url['status_0']=U('Home/Search/index',array('status'=>0));
				$url['status_1']=U('Home/Search/index',array('status'=>1));
				$url['status_2']=U('Home/Search/index',array('status'=>2));
				$url['status_3']=U('Home/Search/index',array('status'=>3));
				$url['status_4']=U('Home/Search/index',array('status'=>4));
		}

		$shop_map	=	D('Common/shop')->goodsmap($goods_id,$category_id);
		$event_map['status']=1;
		$event_map['siteid']=SITEID;
		//dump($event_map);
		$issue_content_map['status']=1;
		$issue_content_map['siteid']=SITEID;
		$member_map['status']=1;
		$member_map['siteid']=SITEID;
		$document_map['status']=1;
		$document_map['siteid']=SITEID;
		
		
		if($name){
			$name	=	urlsafe_b64decode($name);
			$shop_map['goods_name']=array('like',"%".$name."%");
			$event_map['title']=array('like',"%".$name."%");
			$issue_content_map['title']=array('like',"%".$name."%");
			$member_map['nickname']=array('like',"%".$name."%");
			$document_map['title']=array('like',"%".$name."%");
		}
		//dump($shop_map);
		$num=D('shop')->where($shop_map)->count();
		$num1=D('event')->where($event_map)->count();
		$num2=D('issue_content')->where($issue_content_map)->count();
		$num3=D('member')->where($member_map)->count();
		$num4=D('document')->where($document_map)->count();
		
		$hot_map=D('Common/Shop')->goodsmap();
		$hot_map['is_hot']	=	1;
		
		$documentlist2=D('shop')->where($hot_map)->limit(3)->select();
		foreach($documentlist2 as $key=>$val){
			$documentlist2[$key]['market_price']	=	D('Common/shop')->sku_ids_price($val['id']);
	
			$bargain_price = D('shop_bargain')->where('goods_id='.$val['id'].' and overtime>'.time().' and  starttime<'.time().' and surplus_num>0')->getField('bargain_price');
			
			if($bargain_price){
				$documentlist2[$key]['market_price']	=	$bargain_price;
			}
		}
		unset($key);unset($val);
		//特价
		$new_map=D('Common/Shop')->goodsmap();
		$new_map['is_recommend']	=	1;
		
		$documentlist3=D('shop')->where($new_map)->limit(1)->select();
		foreach($documentlist3 as $key=>$val){
			$documentlist3[$key]['market_price']	=	D('Common/shop')->sku_ids_price($val['id']);
	
			$bargain_price = D('shop_bargain')->where('goods_id='.$val['id'].' and overtime>'.time().' and  starttime<'.time().' and surplus_num>0')->getField('bargain_price');
			
			if($bargain_price){
				$documentlist3[$key]['market_price']	=	$bargain_price;
			}
		}
		unset($key);unset($val);
		switch($status){
			case 0:
				
				$documentlist = D('shop')->where($shop_map)->page($page, 16)->select();
				$totalCount = $num;
				foreach($documentlist as $key=>$val){
					$documentlist[$key]['market_price']	=	D('Common/shop')->sku_ids_price($val['id']);
			
					$bargain_price = D('shop_bargain')->where('goods_id='.$val['id'].' and overtime>'.time().' and  starttime<'.time().' and surplus_num>0')->getField('bargain_price');
					
					if($bargain_price){
						$documentlist[$key]['market_price']	=	$bargain_price;
					}
				}
				
			break;
			
			case 1:
				
				$documentlist = D('event')->where($event_map)->page($page, 10)->select();
				$totalCount = $num1;
				
				//$documentlist=D('event')->where($event_map)->limit($Page->firstRow.','.$Page->listRows)->select();
			
				// 出发地
				foreach ($documentlist as $key => $value) {
					$documentlist[$key]['begincity']		=	get_citys($value['begincity']);
				
				// 目的地
					$documentlist[$key]['finalcity']		=	get_citys($value['finalcity']);
					if($value['tag']){
						$tag_arr = explode(',',$value['tag']);
						foreach ($tag_arr as $k => $v) {
								//$documentlist[$key]['tag'][$v]['id']		= $v;

								$documentlist[$key]['tagname']	= $documentlist[$key]['tagname']."&nbsp;&nbsp;".get_event_tag($v);
								
						}
					}
				}
				break;


				case 2:
				
				
					$documentlist = D('issue_content')->where($issue_content_map)->page($page, 10)->select();
					$totalCount = $num2;

					foreach ($documentlist as $key => $value) {
						$map1=array('uid'=>$value['uid']);
						$userinfo=query_user(array('uid','nickname','avatar128','create_time', 'begincity'), $val['uid']);
						$documentlist[$key]['userinfo']	= $userinfo;
					//	dump($userinfo);
						// 搜公告作者名
						$nickname=$documentlist[$key]['nickname']=D('member')->where($map1)->getfield('nickname');
						
					}

					// 出发地
					foreach ($documentlist as $key => $value) {
					
					// 目的地
						$documentlist[$key]['finalcity']		=	get_citys($value['finalcity']);
						if($value['tag']){
							$tag_arr = explode(',',$value['tag']);
							foreach ($tag_arr as $k => $v) {
									//$documentlist[$key]['tag'][$v]['id']		= $v;

									$documentlist[$key]['tagname']	= $documentlist[$key]['tagname']."&nbsp;&nbsp;".get_event_tag($v);
									
							}
						}
						
					}

					//dump($documentlist);
				break;


				case 3:
					$documentlist = D('member')->where($member_map)->page($page, 10)->select();
					$totalCount = $num3;
					
				
					foreach ($documentlist as $key => $value) {
						$map1=array('uid'=>$value['uid']);
						// 会员
				
						$userinfo=query_user(array('uid','signature','avatar128', 'email','mobile','sex','self_introduction','constellation'), $value['uid']);
						if(!$userinfo['constellation']) $userinfo['constellation']=1; 
						$userinfo['constellation']=get_constellation($userinfo['constellation']);
						/*if($userinfo['sex']==1){
									$userinfo['sex']='男'; 
								}else{ 
									$userinfo['sex']='女';
								}
						*/
						$documentlist[$key]['userinfo']	= $userinfo;
					//	dump($userinfo);
						// 搜公告作者名
						$nickname=$documentlist[$key]['nickname']=D('member')->where($map1)->getfield('nickname');
						
					}
					
					//dump($documentlist);
				break;
				case 4:
	
					$documentlist = D('document')->where($document_map)->page($page, 10)->select();
					$totalCount = $num4;
					//$documentlist=D('document')->where($document_map)->select();

					foreach ($documentlist as $key => $value) {
						$map1=array('uid'=>$value['uid']);
				
						// 搜公告作者名
						$nickname=$documentlist[$key]['nickname']=D('member')->where($map1)->getfield('nickname');
						
					}
				break;
		}
		$this->assign('totalCount',$totalCount);
		$this->assign('searurl',$url);
		$this->assign('status',$status);
		$this->assign('documentlist',$documentlist);
		$this->assign('documentlist2',$documentlist2);
		$this->assign('documentlist3',$documentlist3);
		$this->assign('shop_num',$num);
		$this->assign('event_num',$num1);
		$this->assign('story_num',$num2);
		$this->assign('vip_num',$num3);
		$this->assign('link_num',$num4);
		$this->display();
	}
}
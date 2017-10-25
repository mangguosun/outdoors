<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Widget;

use Think\Action;

/**
 * 推荐活动widget
 * 用于动态调用分类信息
 */
class RunnersHomeWidget extends Action
{
    /*
    * 最新活动信息
    */
    public function newEvent($limit = 3, $type_id = 0, $norh = 'new')
    {   
        $Event_Runners__new_keys = 'Event_Runners_'.SITEID.'_new';
        $content_S = S($Event_Runners__new_keys);
        if($content_S){
            $this->assign('contents', $content_S); 
        }else{ 
            $order = 'create_time desc ';
            $content = D('Common/Event')->getEventRecommend($limit,$page,$recommend,$order);
            
            $this->assign('contents', $content);
            S($Event_Runners__new_keys, $content,1800);
        }
        $this->assign('type_id', $type_id);
        $this->assign('norh', $norh);
        $this->display('Widget/recommendnew');
    }

    /*最新公告*/
    public function hotDocument($limit = 5)
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


    /*故事*/
    public function newIssue($limit = 5)
    {
        $order = " view_count desc ";
        $map['status'] = 1;
        $map['siteid'] = SITEID;
        $rs_newissue = D('issue_content')->where($map)->order($order)->limit($limit)->select();
        foreach ($rs_newissue as &$v) {
            $v['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['issue'] = D('Issue')->field('id,title')->find($v['issue_id']);
            
            if($v['tag'] != ''){ 
                $i_content['tagarr'] = explode(',',$v['tag']);
                foreach ($i_content['tagarr'] as $key => $a) {
                    if($key >= 4) break ;
                    $v['tags'][$a]['id'] = $a;
                    $v['tags'][$a]['name'] = get_event_tag($a);
                }

            }else{ 
                $i_content['tagarr'] = '' ;
            }
            
            $v['discreate_time'] = Date("Y-m-d ",$v['create_time']);
            $v['event_title'] = D('Event')->field('title')->where(array('id'=>$v['related_event'],'siteid'=>SITEID))->find();
            $v['comment_count'] = D('local_comment')->where(array('app'=>'Issue','row_id'=>$v['id'],'siteid'=>SITEID))->count();
            $v['collect_count'] = D('forum_bookmark')->where(array('issue_id'=>$v['id'],'siteid'=>SITEID))->count();
        }
        $this->assign('issue_type',$issue_type);
        $this->assign('rs_newissue', $rs_newissue);
        $this->display('Widget/newissue');
    }

    /*视频*/
    public function video($limit = 5)
    {   $map['siteid']=SITEID;
        $map['status']=1;
        $map['video_recommend']=1;
        $video=D('websit_video')->where($map)->find();

        $this->assign('video',$video);
        $this->display('Widget/video');
    }

    /***********************SHOP*************************/

        /*图片导航*/
    public function classification()
    {
        $list=D('shop_classification')->where(array('siteid'=>SITEID,'status'=>1))->select();
        $this->assign('shop_classification_info',$list);
        $this->display('Widget/shop_classification');
    }
    /*品牌导航*/
    public function brand(){
        $brand=D('shop_brand')->where(array('siteid'=>SITEID,'status'=>1))->select();
        $this->assign('brand_info',$brand);
        $this->display('Widget/shop_brand');
    }
    
    public function module($limit=5,$action=''){
        $map    =   D('Common/shop')->goodsmap();
        $order = 'sort';
        switch($action){
            case 'new_products':    //新品
                $map['is_new']  =   1;
                $view   =   'Widget/shop_new';
            break;
            case 'recommend':   //推荐
                $map['is_recommend']    =   1;
                $view   =   'Widget/shop_recommend';
            break;
            case 'bargains':    //特价
                $map['is_bargains'] =   1;
                $view   =   'Widget/shop_bargains';
            break;
            case 'fiery':   //热销
                $map['is_firey']    =   1;
                $view   =   'Widget/shop_fiery';
            break;
        }

        $info   =   D('shop')->where($map)->order($order)->limit($limit)->select();
        
        foreach ($info as $k => $v) {
            if($v['shop_brand'] != 0){
                $info[$k]['custom_brand_name']=D('shop_brand_manage')->where('id='.$v['shop_brand'])->getField('name');
        
            }
            $info[$k]['market_price']   =   D('Common/shop')->sku_ids_price($info[$k]['id']);
        }
        $this->assign('data',$info);
        $this->display($view);

    }
    
    /*最新评论*/
    public function comment($limit=9){
       $comment=D('local_comment')->where("status=1 and siteid=".SITEID)->order('id desc')->limit($limit)->select();
       foreach($comment as $k => $v){
          $comment[$k]['nickname']=D('member')->where('uid='.$v['uid'])->getField('nickname');
          $list=query_user(array('avatar64','space_url'), $v['uid']);
          $comment[$k]['user_img']=$list['avatar64'];
          $comment[$k]['space_url']=$list['space_url'];
       }
       $this->assign("shop_comment_info",$comment);
       $this->display('Widget/shop_comment');
    }
    /**
    * 导航分类
    */
    public function navigation($limit=4,$data=5){
     $map2  = array('status' => array('eq', 1),'siteid' => SITEID,'pid'=>0);
      $is_distribute_category = D('shop_category')->where($map2)->limit($limit)->select();
      foreach($is_distribute_category as $k=>$v){
        $map3  = array('status' => array('eq', 1),'siteid' => SITEID,'pid'=>$v['id']);
        $is_distribute_category[$k]['category_2nd'] = D('shop_category')->limit($data)->where($map3)->select();
      }
        $this->assign('is_distribute_category',$is_distribute_category);
        $this->display('Widget/shop_navigation');
    }
	//限时特价
    public function limit_bargains(){
		$map	=	D('Common/shop')->goodsmap();
		unset($map['status']);
		$map['overtime']	=	array('gt',time());
		$map['starttime']	=	array('lt',time());
		$map['surplus_num']	=	array('gt',0);
		if($map['_complex']['id']){
			$map['_complex']['goods_id']	=	$map['_complex']['id'];
			unset($map['_complex']['id']);
		}
		$bargain = D('shop_bargain')->where($map)->select();
        foreach($bargain as $key => $v) {
                $bargain[$key]['goods_name']= D('shop')->where("id=".$v['goods_id'])->getField('goods_name');
                
                $bargain[$key]['tox_money_need']=D('shop')->where("id=".$v['goods_id'])->getField('tox_money_need');
                $bargain[$key]['goods_ico']=D('shop')->where("id=".$v['goods_id'])->getField('goods_ico');
                $bargain[$key]['overtime']=$bargain[$key]['overtime']*1000;
            }
            
        $this->assign('shop_limit_bargains_info', $bargain);
        $this->display('Widget/shop_limit_bargains');
    }
    

 
}

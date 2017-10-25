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
class DefaultHomeWidget extends Action
{   

     /*推荐活动 default*/

    public function recommendEvent($limit = 3)
    {   

        $Event_Default__recommend_keys = 'Event_Default_'.SITEID.'_recommend';
        $rec_event_S = S($Event_Default__recommend_keys);
        if($rec_event_S){ 
              $this->assign('rec_event', $rec_event_S); 
        }else{
          
            $recommend = true;
            $rec_event = D('Common/Event')->getEventRecommend($limit,$page,$recommend,$order);
            S($Event_Default__recommend_keys, $rec_event,1800);
            $this->assign('rec_event', $rec_event);
        }

        $this->display('Widget/recommend');
    }

     /*
    * 最新活动信息
    */
    public function newEvent($limit = 3, $type_id = 0, $norh = 'new')
    {   


        $Event_Default__new_keys = 'Event_Default_'.SITEID.'_new';
        $content_S = S($Event_Default__new_keys);
        if($content_S){ 
            $this->assign('contents', $content_S); 
        }else{ 
            $order = 'create_time desc ';
            $content = D('Common/Event')->getEventRecommend($limit,$page,$recommend,$order);
            
            $this->assign('contents', $content);
            S($Event_Default__new_keys, $content,1800);
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

    /*推荐故事*/
    public function hotIssue($limit = 5)
    {   
        $rs_issue = D('issue_content')->where(array('status'=>1,'is_recommend'=>1,'siteid'=>SITEID))->order("view_count desc")->limit($limit)->select();
        foreach ($rs_issue as &$v) {
            $v['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['issue'] = D('Issue')->field('id,title')->find($v['issue_id']);
            
            
            $i_content['tagarr'] = explode(',',$v['tag']);
            foreach ($i_content['tagarr'] as $key => $a) {
                if($key >= 4) break ;
                $v['tags'][$a]['id'] = $a;
                $v['tags'][$a]['name'] = get_event_tag($a);
            }
            
        }
        $this->assign('rs_issue', $rs_issue);
        $this->display('Widget/hotissue');
    }

    /*推荐官方领队*/
      /* 显示指定分类的同级分类或子分类列表 */
    public function hotUser($limit = 5)
    {
  
        $peoples = D('Member')->where('status=1 and is_use=2 and recommendm =1 and siteid='.SITEID)->order('last_login_time desc')->limit($limit)->select();        
        foreach ($peoples as &$v) {
            
            $v['user'] = query_user(array('avatar64', 'space_url', 'username', 'fans', 'following', 'signature', 'nickname'), $v['uid']);
        }
        unset($v);
        $m_level_name=get_upgrading(2);
        $this->assign('m_level_name',$m_level_name);
        $this->assign('lists', $peoples);
        $this->display('Widget/hotuser');
    }

    /*推荐品牌*/
    public function brandIssue($limit = 5)
    {
        $rs_issue = D('issue_content')->where(array('status'=>1,'recommend_brand'=>1,'siteid'=>SITEID))->order("id desc")->limit($limit)->select();
        foreach ($rs_issue as &$v) {
            $v['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['issue'] = D('Issue')->field('id,title')->find($v['issue_id']);
        }
        $this->assign('rs_issue', $rs_issue);
        $this->display('Widget/brandissue');
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
  
}

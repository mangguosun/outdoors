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
class SportsHomeWidget extends Action
{

    /* 显示指定分类的同级分类或子分类列表 */
    /*活动-sports*/
    public function EventList($limit = 3)
    {   
        $Event_sports_keys = 'Event_sports_'.SITEID.'_'.$event_type;
        $rec_event_sports_S =  S( $Event_sports_keys);
        if( $rec_event_sports_S ){ 
           
            $this->assign('rec_event_recommend', $rec_event_sports_S['recommend'] );
            $this->assign('rec_event_new', $rec_event_sports_S['new']);
            $this->assign('rec_event_hot',  $rec_event_sports_S['hot']);

        }else{ 
           
            $order = 'sort desc';
            $rec_event_recommend = D('Common/Event')->getEventRecommend($limit,$page,$recommend,$order,$event_type='recommend');
            $rec_event_new = D('Common/Event')->getEventRecommend($limit,$page,$recommend,$order,$event_type='new');
            $rec_event_hot = D('Common/Event')->getEventRecommend($limit,$page,$recommend,$order,$event_type='hot');
            $num_recommend = 1;
            foreach ($rec_event_recommend as $key => $value) {
             if( ($key%2)  == 0  && $key != 0){ 
                $num_recommend++;
              }
              $rec_event_new_recommend[$num_recommend][$key] = $value;
            }

            $num_new = 1;
            foreach ($rec_event_new as $key => $value) {
             if( ($key%2)  == 0  && $key != 0){ 
                $num_new++;
              }
              $rec_event_new_new[$num_new][$key] = $value;
            }


            $num_hot = 1;
            foreach ($rec_event_hot as $key => $value) {
             if( ($key%2)  == 0  && $key != 0){ 
                $num_hot++;
              }
              $rec_event_new_hot[$num_hot][$key] = $value;
            }

            $rec_event_new_S['recommend'] = $rec_event_new_recommend;
            $rec_event_new_S['new'] = $rec_event_new_new;
            $rec_event_new_S['hot'] = $rec_event_new_hot;

            S($Event_sports_keys,$rec_event_new_S,1800);

            $this->assign('rec_event_recommend', $rec_event_new_recommend);
            $this->assign('rec_event_new', $rec_event_new_new);
            $this->assign('rec_event_hot', $rec_event_new_hot);
        
        }

        $this->assign('event_type',$event_type);
        $this->display('Widget/recommend');
    }

    /*故事*/
    public function IssueList($limit = 5)
    {   
        $maprecommend['status'] = 1;
        $maprecommend['siteid'] = SITEID;
        $maprecommend['is_recommend'] = 1;
        $orderrecommend = " view_count desc ";
        $rs_newissue_recommend = D('issue_content')->where($maprecommend)->order($orderrecommend)->limit($limit)->select();
        foreach ($rs_newissue_recommend as &$v) {
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
    
        $mapnew['status'] = 1;
        $mapnew['siteid'] = SITEID;
        $ordernew = " create_time desc , view_count desc  ";
        $rs_newissue_new = D('issue_content')->where($mapnew)->order($ordernew)->limit($limit)->select();
        foreach ($rs_newissue_new as &$v) {
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
    
        $maphot['status'] = 1;
        $maphot['siteid'] = SITEID;
        $orderhot = " view_count desc ";
        $rs_newissue_hot = D('issue_content')->where($maphot)->order($orderhot)->limit($limit)->select();
        foreach ($rs_newissue_hot as &$v) {
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
        $this->assign('rs_newissue_recommend', $rs_newissue_recommend);
        $this->assign('rs_newissue_new', $rs_newissue_new);
        $this->assign('rs_newissue_hot', $rs_newissue_hot);
        $this->display('Widget/newissue');
    }

    /*推荐装备*/
    public function shopRecommend($limit=5){
        $map    =   D('Common/shop')->goodsmap();
        $map['is_recommend']    =   1;
        $info   =   D('shop')->where($map)->order('sell_num desc')->limit($limit)->select();
        foreach ($info as $k => $v) {
            if($v['shop_brand'] != 0){
                $info[$k]['custom_brand_name']=D('shop_brand_manage')->where('id='.$v['shop_brand'])->getField('name');
        
            }
            $info[$k]['market_price']   =   D('Common/shop')->sku_ids_price($info[$k]['id']);
        }
        $this->assign('data',$info);
        $this->display('Widget/shop_recommend');

    }

    /*活动日历*/
    public function eventCalendar($limit = 5)
    {   

        $list = D("Common/Event")->getTypeEventContent();
        $newtime['newtime_ym'] = date("Y-m",time());
        $newtime['newtime_d'] = date("d",time());
        $this->assign('calendarList', $list['calendarList']);
        $this->assign('calendarEvent',$list['calendar_list_Con']['Event']);
        $this->assign('calendarMatch',$list['calendar_list_Con']['Match']);
        $this->assign('calendarEventnum',count($list['calendar_list_Con']['Event']));
        $this->assign('calendarMatchnum',count($list['calendar_list_Con']['Match']));
        $this->assign('lists', true);
        $this->assign('newtime',$newtime);
        $this->display('Widget/calendar');
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


    /*动态信息*/
    public function dynamic($limit = 5)
    {   
       
        $lists= D("Common/Dynamic")->getDynamicMessage($limit);
        foreach ($lists as $key => $value) {
            $lists[$key]['nickname'] =   query_user('nickname',$value['from_uid']) ; 
            $lists[$key]['dis_createtime'] = date("Y-m-d H:i:s",$value['create_time']);
            $url = explode('.', $value['url']);
             $lists[$key]['url'] = $url[0];
            switch ($value['appname']) {
                case 'Issue':
                   $lists[$key]['dis_action'] = '发布文章';
                    break;
                case 'Event':
                   $lists[$key]['dis_action'] = '参加了';
                    break;
                case 'EventAction':
                   $lists[$key]['dis_action'] = '发布了';
                    break;
                case 'Shop':
                   $lists[$key]['dis_action'] = '购买了';
                    break;
                case 'ShopAction':
                   $lists[$key]['dis_action'] = '发布了装备';
                    break;
                case 'Register':
                   $lists[$key]['dis_action'] = '注册为';
                   $webname = D('websit')->field('webname')->where('siteid = '.$value['siteid'])->find();
                   $lists[$key]['webname'] = $webname['webname'].'的会员';
                    break;
            }

            
        }

        $this->assign('lists', $lists);
        $this->display('Widget/dynamic');
    }

    /*打卡排行*/
    public function Mark(){
        //日
        $distance='dailydistance';
        $daily=D('Common/Mark')->fordistance($distance);
        $dailydistance_user=$daily[0];
        $dailydistance=$daily[1];
        //周
        $distance='weeklydistance';
        $weekly=D('Common/Mark')->fordistance($distance);
        $weeklydistance_user=$weekly[0];
        $weeklydistance=$weekly[1];
        //月
        $distance='monthlydistance';
        $monthly=D('Common/Mark')->fordistance($distance);
        $monthlydistance_user=$monthly[0];
        $monthlydistance=$monthly[1];
        $this->assign('dailydistance_user',$dailydistance_user);
        $this->assign('dailydistance',$dailydistance);
        $this->assign('weeklydistance_user',$weeklydistance_user);
        $this->assign('weeklydistance',$weeklydistance);
        $this->assign('monthlydistance_user',$monthlydistance_user);
        $this->assign('monthlydistance',$monthlydistance);
        $this->display('Widget/charts');
        
    }

    /*推荐官方领队*/
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

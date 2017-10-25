<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Mobile\Controller;
use OT\DataDictionary;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class PeopleController extends MobileController {
	 public function _initialize()
    {
        $model_info = get_appinfo('People');
		if(!$model_info){
			$this->error('应用未开启');
		}
		$this->assign('model_info', $model_info);
    }
	//系统首页
    public function index($page = 1)
    {	
	
	
		$peoples = D('Member')->where('siteid = '.SITEID.' and status=1')->order('last_login_time desc')->limit(0,10)->select();
		foreach ($peoples as &$v) {
            $v['user'] = query_user(array('avatar128', 'space_url', 'username', 'fans', 'following', 'signature', 'nickname'), $v['uid']);
        }
		$get_url = json_encode($_GET);
		$this->assign('get_url', $get_url);
		$this->assign('peoples', $peoples);
        $this->setTitle('会员中心');
        $this->setKeywords('会员中心');
        $this->display();
    }
	
	public function get_people_index($page = 0)
	{	
	
		$start = $page*10; 
		$isuse = I('isuse');
		if($isuse){
			$map['is_use'] = $isuse;
		}
		$map['siteid'] = SITEID;
		$map['status'] = 1;
		
		$peoples = D('Member')->where($map)->order('last_login_time desc')->limit($start,10)->select();
		foreach ($peoples as &$v) {	
			$tmp = query_user(array('avatar128', 'space_url', 'username', 'fans', 'following', 'signature', 'nickname'), $v['uid']);
			$v['thumb'] =$tmp['avatar128'];
			$v['space_url'] =$tmp['space_url'];
			$v['fans'] =$tmp['fans'];
			$v['isuse'] =get_upgrading($v['is_use']);
			$v['following'] =$tmp['following'];
			$v['url'] =U('Mobile/People/peopledetail',array('uid'=>$v['uid']));
			$v['signature'] =getShortSp($v['signature'],'10');
		}
		exit(json_encode($peoples));
	}
    /**关注某人
     * @param int $uid
     * @auth 陈一枭
     */

	public function peopledetail($uid=''){
		
		$user = query_user(array('uid','username','nickname', 'signature','is_use','title','email', 'mobile', 'avatar128','qq','rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation','space_url', 'icons_html', 'score', 'tox_money','title', 'fans', 'following', 'weibocount', 'rank_link', 'address','is_following','is_followed'), $uid);
		

		$map = "uid = $uid and siteid = ".SITEID;
		$issue_arr = D('issue_content')->field('id,siteid,uid,title,create_time,cover_id,view_count,issue_id,finalcity,tag,recommend_brand,related_event,is_recommend')->where($map)->order('id desc')->select();
		$user['issue_count'] = count($issue_arr);
		
		foreach ($issue_arr as &$v) {
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
		

		$address = get_citys($user['address']);
		$user['addresss']['community'] = get_city($address['community']);
		$user['addresss']['district'] = get_city($address['district']);
		$user['addresss']['city'] = get_city($address['city']);
		$user['addresss']['province'] = get_city($address['province']);
		
		$this->assign('myissue_arr',$issue_arr);
		
		
		
		$this->assign('city',$city);
		$this->assign('user', $user);
		$this->display();
	}

    public function peoplefollow($uid=''){

        $user = query_user(array('uid','username','nickname', 'signature','is_use','title','email', 'mobile', 'avatar128','qq','rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation','space_url', 'icons_html', 'score', 'tox_money','title', 'fans', 'following', 'weibocount', 'rank_link', 'address','is_following','is_followed'), $uid);

        $map = "uid = $uid and siteid = ".SITEID;
        $issue_arr = D('issue_content')->field('id,siteid,uid,title,create_time,cover_id,view_count,issue_id,finalcity,tag,recommend_brand,related_event,is_recommend')->where($map)->order('id desc')->select();
        $user['issue_count'] = count($issue_arr);
        $address = get_citys($user['address']);
        $user['addresss']['community'] = get_city($address['community']);
        $user['addresss']['district'] = get_city($address['district']);
        $user['addresss']['city'] = get_city($address['city']);
        $user['addresss']['province'] = get_city($address['province']);

        $get_url = json_encode($_GET);
        $this->assign('get_url', $get_url);



        $this->assign('city',$city);
        $this->assign('user', $user);
        $this->display();
    }
    public function get_people_follow($page = 0,$uid='')
    {

        $start = $page*10;

        $fans_uid = D('Follow')->field('follow_who')->where('who_follow = ' . $uid)->limit($start,10)->select();
        foreach ($fans_uid as &$v) {
            $tmp = query_user(array('avatar128', 'space_url', 'username', 'fans', 'following', 'signature', 'nickname'),$v['follow_who']);
            $is_following = D('Follow')->where(array('who_follow'=>is_login(),'follow_who'=>$v['follow_who']))->find();
            if($is_following){
                $v['follow'] ="取消关注";
            }elseif(is_login()===$v['who_follow']){
                $v['follow'] ="自己";
            }else{
                $v['follow'] ="关注";
            }
            $v['follow_id'] =$v['follow_who'];
            $v['thumb'] =$tmp['avatar128'];
            $v['title'] =$tmp['nickname'];
            $v['space_url'] =$tmp['space_url'];
            $v['fans'] =$tmp['fans'];
            $v['following'] =$tmp['following'];
            $v['signature'] =$tmp['signature'];
        }
        exit(json_encode($fans_uid));
    }

    public function peoplefan($uid=''){

        $user = query_user(array('uid','username','nickname', 'signature','is_use','title','email', 'mobile', 'avatar128','qq','rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation','space_url', 'icons_html', 'score', 'tox_money','title', 'fans', 'following', 'weibocount', 'rank_link', 'address','is_following','is_followed'), $uid);

//        $fans_uid = D('Follow')->field('who_follow')->where('follow_who = ' . $uid)->select();
//        var_dump(is_login());
        $map = "uid = $uid and siteid = ".SITEID;
        $issue_arr = D('issue_content')->field('id,siteid,uid,title,create_time,cover_id,view_count,issue_id,finalcity,tag,recommend_brand,related_event,is_recommend')->where($map)->order('id desc')->select();
        $user['issue_count'] = count($issue_arr);
        $address = get_citys($user['address']);
        $user['addresss']['community'] = get_city($address['community']);
        $user['addresss']['district'] = get_city($address['district']);
        $user['addresss']['city'] = get_city($address['city']);
        $user['addresss']['province'] = get_city($address['province']);

        $get_url = json_encode($_GET);
        $this->assign('get_url', $get_url);



        $this->assign('city',$city);
        $this->assign('user', $user);
        $this->display();
    }
    public function get_people_fun($page = 0,$uid='')
    {

        $start = $page*10;
        $fans_uid = D('Follow')->field('who_follow')->where('follow_who = ' . $uid)->limit($start,10)->select();
        foreach ($fans_uid as &$v) {
            $tmp = query_user(array('avatar128', 'space_url', 'username', 'fans', 'following', 'signature', 'nickname'),$v['who_follow']);
            $is_following = D('Follow')->where(array('who_follow'=>is_login(),'follow_who'=>$v['who_follow']))->find();
            if($is_following){
            $v['follow'] ="取消关注";
            }elseif(is_login()===$v['who_follow']){
                $v['follow'] ="自己";
            }else{
            $v['follow'] ="关注";
            }
            $v['who_id'] =$v['who_follow'];
            $v['thumb'] =$tmp['avatar128'];
            $v['title'] =$tmp['nickname'];
            $v['space_url'] =$tmp['space_url'];
            $v['fans'] =$tmp['fans'];
            $v['following'] =$tmp['following'];
            $v['signature'] =$tmp['signature'];
        }
        exit(json_encode($fans_uid));
    }
}
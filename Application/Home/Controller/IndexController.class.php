<?php

// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;

use OT\DataDictionary;
set_time_limit(0);
/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class IndexController extends HomeController {

	 public function _initialize()
    {
        $tree = D('EventType')->where(array('status' => 1))->select();		
        $this->assign('tree', $tree);
    }
	/*修改公告*/
	public function ueditor(){
	      $list=D('document_article')->Table(array('thinkox_document_article'=>'da','thinkox_document'=>'d'))
		                             ->where("da.id = d.id and d.id =".I('id'))
									 ->field('d.id,d.title,da.content')
									 ->find();
		  $this->assign('list',$list);
	      $this->display();
	}
	/*20150526 官方公告二维码批量生成*/
   public function qrcode_blog_bak(){ 

         $rs_find = D('document')->where(array('status' => 1))->select();
         foreach ($rs_find as $key => $value) {
            $websits    =  D('websit')->where(array('siteid'=>$value['siteid']))->find();
            $web_url    =  $websits['url'].".huodongli.cn";
             $qrcode_url = set_qrcode(array('id'=>$value['id']),'blog',$value['siteid'],$web_url);
             $user = D('qrcode');
             $qrcode_data = array(
                            'siteid'        => $value['siteid'],
                            'uid'           => $value['uid'],
                            'linkid'        => $value['id'],
                            'types'         => 'blog',
                            'url'           => $qrcode_url,
                            'create_time'   => $value['create_time']
                        );
           
            $rs_find = D('qrcode')->where(array('linkid'=> $value['id'],'types'=>'blog'))->find();
            if($rs_find){ 
                $user->where(array('linkid'=> $value['id'],'types'=>'blog'))->save($qrcode_data);
            }else{ 
                $user->add($qrcode_data);
            }
            
         }
         echo 'ok';   
    }
		
	



    public function fullCalendar($time , $type){ 

        $list = D('Common/Event')->getTypeEventList($time,$ype='');
        $list['match_listnum']  = count($list['match_list']);
        $list['event_listnum']  = count($list['event_list']);
        exit(json_encode($list));


    }
	
	//系统首页
    public function index($page = 1, $type_id = 0, $norh = 'new')
    {	
	   
        $this->setTitle('活动首页');
        $this->setKeywords('活动');
        $this->display();
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
    /**
     * 获取推荐活动数据
     * autor:xjw129xjt
     */
    public function getRecommend()
    {
        $rec_event = D('Event')->where(array('is_recommend' => 1))->limit(2)->order('rand()')->select();
        foreach ($rec_event as &$v) {
            $v['user'] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['type'] = $this->getType($v['type_id']);
            $v['check_isSign'] = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $v['id']))->select();
        }
        unset($v);

        $this->assign('rec_event', $rec_event);


    }
        /**
     * 获取表情列表。
     */
    public function getSmile()
    {
        //这段代码不是测试代码，请勿删除
        exit(json_encode(D('Common/Expression')->getAllExpression()));
    }
    public function getuploadthumb($id='',$width='',$height='')
    {
	  if(!$id) return false;
		if($width && $height){
			$data['url'] = getThumbImageById($id,$width,$height);
		}else{
			$data['url'] = get_cover($id,'path');
		} 
	  echo json_encode($data);
      exit;
    }
	
    public function get_uploadthumb_pc($id='',$thumb_width=80,$thumb_height=80,$width=0,$height=0)
    {
	  if(!$id) return false;
		
	  $data['url_thumb'] = getThumbImageById($id,$thumb_width,$thumb_height);
	  if($width && $height){
			$data['url_rel'] = getThumbImageById($id,$width,$height);
		}else{
			$data['url_rel'] = get_cover($id,'path');

		} 
	
	  echo json_encode($data);
      exit;
    }
	
    public function get_uploadthumb_pc_singie($id='',$thumb_width=80,$thumb_height=80)
    {
	  if(!$id) return false;
		
	  $data['url_thumb'] = getThumbImageById($id,$thumb_width,$thumb_height);
	  echo json_encode($data);
      exit;
    }
}
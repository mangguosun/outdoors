<?php

namespace Event\Controller;

use Think\Controller;
use Weibo\Api\WeiboApi;

class IndexController extends Controller
{
    /**
     * 业务逻辑都放在 WeiboApi 中
     * @var
     */
    public function _initialize()
    {
        $tree = D('EventType')->where(array('status' => 1 ,'siteid'=>SITEID))->select();		
        $this->assign('tree', $tree);
    }
	
	 public function district_updata( $id='')
    {
		if($id=='999'){
			set_time_limit(0); 
			echo('开始更新');
			$city_arr = D('district')->select();	
			foreach ($city_arr as &$v) {
				$r = get_citys_subclass($v['id']);
				$data['arrchildid'] = $v['id'].$r;
				D('district')->where(array('id' => $v['id']))->save($data);
			}
			echo('成功更新');
		}	
	}




    public function addPoint($id,$point_lng,$point_lat){ 
        //$id = $id;
        $ponit['point_lng'] = $point_lng;
        $ponit['point_lat'] = $point_lat;
       $rs = D('Event')->where('id = '.$id )->save($ponit);
       if($rs){ 
            exit(json_encode(array('msg'=>1)));
       }else{ 
            exit(json_encode(array('msg'=>0)));
       }
    }

	//转义数据
	//强度指数strength_level 景致指数scene_level 趣味指数fun_level 人文指数human_level 腐败指数money_level
    public function star_up(){ 
      /* $map['status'] = 1;
      $content = D('Event')->where($map)->field('id,siteid,strength_level,scene_level,fun_level,human_level,money_level')->select(); 
      foreach ($content as $key => $value) {
        $data1['event_id'] = $value['id'];
        $data1['siteid'] = $value['siteid'];
        $data1['title'] = '强度指数';
        $data1['grade'] = $value['strength_level'];
        D('event_attribute')->add($data1);

        $data2['event_id'] = $value['id'];
        $data2['siteid'] = $value['siteid'];
        $data2['title'] = '景致指数';
        $data2['grade'] = $value['scene_level'];
        D('event_attribute')->add($data2);

        $data3['event_id'] = $value['id'];
        $data3['siteid'] = $value['siteid'];
        $data3['title'] = '趣味指数';
        $data3['grade'] = $value['fun_level'];
        D('event_attribute')->add($data3);

        $data4['event_id'] = $value['id'];
        $data4['siteid'] = $value['siteid'];
        $data4['title'] = '人文指数';
        $data4['grade'] = $value['human_level'];
        D('event_attribute')->add($data4);

        $data5['event_id'] = $value['id'];
        $data5['siteid'] = $value['siteid'];
        $data5['title'] = '腐败指数';
        $data5['grade'] = $value['money_level'];
        D('event_attribute')->add($data5);
      }

      echo 'ok';*/
    }


	
    /**
     * 活动首页
     * @param int    $page
     * @param int    $type_id
     * @param string $norh
     * autor:xjw129xjt
     */
    public function index($page = 1, $type_id = 0, $norh = 'new')
    {
		
		$eventList = D('Common/Event')->getEventList($page, $type_id, $norh);

		$this->assign('page',$eventList['page']);
		$this->assign('customization', $eventList['customization']);
        $this->assign('type_id', $eventList['type_id']);
        $this->assign('contents', $eventList['contents']);
        $this->assign('norh', $eventList['norh']);
        $this->assign('totalPageCount', $eventList['totalPageCount']);
        $this->getRecommend();
        $this->setTitle('活动首页');
        $this->setKeywords('活动');
        $this->display();
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
     * 活动详情
     * @param int $id
     * autor:xjw129xjt
     */

    /*加入收藏页面*/
	 public function dodetail(){
	      $map['uid'] = is_login();
          if(!empty($map['uid'])){
	      $id=$_POST['id'];
          $data['uid']=$map['uid'];
          $data['content_id']=$id;
		  $data['siteid']=SITEID;
          $res=D('ForumBookmark')->where("siteid=".SITEID." and uid={$map['uid']} and content_id={$id}")->find();
		  if($res){
             echo 1;
         }else{
             $data['create_time']=date('Y-m-d H:i:s',time());
             $e=D('ForumBookmark')->data($data)->add();
             if($e){
               echo '0';
             }
         }
        }
      }
    public function detail($id = 0)
    {
		$detailEvent = D("Common/Event")->getEventDetail($id);
    
		if($detailEvent['error'] == 404){ 
			$this->error('404 not found');
		}
		$this->assign('pictures', $detailEvent['pictures']);
		$this->assign('customization', $detailEvent['customization']);
		$this->assign('qrcode_link',$detailEvent['qrcode_link']);
		$this->assign('tpid',$detailEvent['tpid'] );
		$this->assign('detail_schedule', $detailEvent['detail_schedule']);
		$this->assign('leaders', $detailEvent['leaders'] );
		$this->assign('tags', $detailEvent['tags']  );
		$this->assign('in_event', $detailEvent['in_event']);
        $this->assign('content', $detailEvent['content']  );
		$_GET['type_id'] = $detailEvent['content']['type_id'] ;
        $this->setTitle('{$content.title|op_t}' . '——活动');
        $this->setKeywords('{$content.title|op_t}' . ',活动');
        $this->getRecommend();
        $this->display();
    }

 
    /**
     * 审核
     * @param $uid
     * @param $event_id
     * @param $tip
     * autor:xjw129xjt
     */
    public function shenhe($uid, $event_id, $tip)
    {
        $event_content = D('Event')->where(array('status' => 1, 'id' => $event_id,'siteid'=>SITEID))->find();
        if (!$event_content) {
            $this->error('活动不存在！');
        }
        if ($event_content['uid'] == is_login()) {
            $res = D('event_attend')->where(array('uid' => $uid, 'event_id' => $event_id))->setField('status', $tip);
            if ($tip) {
                D('Event')->where(array('id' => $event_id,'siteid'=>SITEID))->setInc('attentionCount');
                D('Message')->sendMessageWithoutCheckSelf($uid,query_user('nickname',is_login()).'已经通过了您对活动'.$event_content['title'].'的报名请求' ,'审核通知', U('Event/Index/detail',array('id'=>$event_id)),is_login());
            } else {
                D('Event')->where(array('id' => $event_id,'siteid'=>SITEID))->setDec('attentionCount');
                D('Message')->sendMessageWithoutCheckSelf($uid,query_user('nickname',is_login()).'取消了您对活动['.$event_content['title'].']的报名请求' ,'取消审核通知', U('Event/Index/member',array('id'=>$event_id)),is_login());
            }
            if ($res) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败！');
            }
        } else {
            $this->error('操作失败，非活动发起者操作！');
        }
    }
	
	 public function get_calendar_time(){
		 
		  $id=$_POST['id'];
		  $res=D('event_calendar_time')->where(array('id'=>$id))->find();
		  if($res){
			  $res['remaining'] = $res['maxpeople']- $res['regnumber'];			  
			  exit(json_encode($res));
		 }else{
			  exit();
		 }
     }
	

    /**
     * ajax提前结束活动
     * @param $event_id
     * autor:xjw129xjt
     */
    public function doEndEvent($event_id)
    {

        $event_content = D('Event')->where(array('status' => 1, 'id' => $event_id,'siteid'=>SITEID))->find();
        if (!$event_content) {
            $this->error('活动不存在！');
        }
        if ($event_content['uid'] == is_login() || is_administrator(is_login())) {
            $res = D('Event')->where(array('status' => 1, 'id' => $event_id,'siteid'=>SITEID))->setField('eTime', time());
            if ($res) {
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！');
            }
        }
        else{
            $this->error('非活动发起者操作！');
        }

    }
	
	
}
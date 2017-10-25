<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 想天
 * 创建日期: 3/13/14
 * 创建时间: 7:41 PM
 * 版权所有 想天工作室(www.ourstu.com)
 */

namespace Common\Model;

use Think\Model;

class MessageModel extends Model
{
    /**获取全部没有提示过的消息
     * @param $uid 用户ID
     * @return mixed
     */
    public function getHaventToastMessage($uid)
    {
        $messages = D('message')->where('to_uid=' . $uid . ' and  is_read=0  and last_toast=0')->order('id desc')->limit(99999)->select();
        foreach ($messages as &$v) {
            $v['ctime'] = friendlyDate($v['create_time']);
            $v['content'] = op_t($v['content']);
        }
        unset($v);
        return $messages;
    }

    /**设置全部未提醒过的消息为已提醒
     * @param $uid
     */
    public function setAllToasted($uid)
    {
        $now = time();
        D('message')->where('to_uid=' . $uid . ' and  is_read=0 and last_toast=0')->setField('last_toast', $now);
    }

    public function setAllReaded($uid)
    {
        D('message')->where('to_uid=' . $uid . ' and  is_read=0')->setField('is_read', 1);
    }


    /**取回全部未读信息
     * @param $uid
     * @return mixed
     */
    public function getHaventReadMeassage($uid, $is_toast = 0)
    {
        $messages = D('message')->where('to_uid=' . $uid . ' and  is_read=0 ')->order('id desc')->limit(99999)->select();
        foreach ($messages as &$v) {
            $v['ctime'] = friendlyDate($v['create_time']);
            $v['content'] = op_t($v['content']);
        }
        unset($v);
        return $messages;
    }

    /**取回全部未读,也没有提示过的信息
     * @param $uid
     * @return mixed
     */
    public function getHaventReadMeassageAndToasted($uid)
    {
        $messages = D('message')->where('to_uid=' . $uid . ' and  is_read=0  and last_toast!=0')->order('id desc')->limit(99999)->select();
        foreach ($messages as &$v) {
            $v['ctime'] = friendlyDate($v['create_time']);
            $v['content'] = op_t($v['content']);
        }
        unset($v);
        return $messages;
    }



    /**
     * 注：appname及之后的参数，一般情况下无需填写
     * @param        $to_uid 接受消息的用户ID
     * @param string $content 内容
     * @param string $title 标题，默认为  您有新的消息
     * @param        $url 链接地址，不提供则默认进入消息中心
     * @param int    $from_uid 发起消息的用户，根据用户自动确定左侧图标，如果为用户，则左侧显示头像
     * @param int    $type 消息类型，0系统，1用户，2应用
     * @param string $appname 应用名，默认不需填写，如果填写了就必须实现对应的消息处理模型，例如贴吧里面可以基于某个回复开启聊天
     * @param string $apptype 同上，应用里面的一个标识符
     * @param int    $source_id 来源ID，通过来源ID获取基于XX聊天的来源信息
     * @param int    $find_id 查找ID，通过查找ID获得标识ID
     * @return int
     * @auth 陈一枭
     */
    public function sendMessage($to_uid, $content = '', $title = '您有新的消息', $url, $from_uid = 0, $type = 0, $appname = '', $apptype = '', $source_id = 0, $find_id = 0)
    {
        if ($to_uid == is_login()) {
            return 0;
        }
        $this->sendMessageWithoutCheckSelf($to_uid, $content, $title, $url, $from_uid, $type, $appname, $apptype, $source_id, $find_id);
    }

    /**
     * @param $to_uid 接受消息的用户ID
     * @param string $content 内容
     * @param string $title 标题，默认为  您有新的消息
     * @param $url 链接地址，不提供则默认进入消息中心
     * @param $int $from_uid 发起消息的用户，根据用户自动确定左侧图标，如果为用户，则左侧显示头像
     * @param int $type 消息类型，0系统，1用户，2应用
     */
    public function sendMessageWithoutCheckSelf($to_uid, $content = '', $title = '您有新的消息', $url, $from_uid = 0, $type = 0, $appname = '', $apptype = '', $source_id = 0, $find_id = 0)
    {
        $message['to_uid'] = $to_uid;
        $message['content'] = op_h($content);
        $message['title'] = $title;
        $message['url'] = $url;
        $message['from_uid'] = $from_uid;
        $message['type'] = $type;
        $message['create_time'] = time();
        $message['appname'] = $appname == '' ? strtolower(MODULE_NAME) : $appname;
        $message['source_id'] = $source_id;
        $message['apptype'] = $apptype;
        $message['find_id'] = $find_id;

        $rs = $this->add($message);
        return $rs;
    }

    public function readMessage($message_id)
    {
        return $this->where(array('id' => $message_id))->setField('is_read', 1);
    }

    public function addSendMessage($taskphp='send_sms_to_user',$contactways='',$param='',$level=0,$temp=1,$siteid=SITEID){
    	if($param['shop_order_sn']){ 
    		$data['tempid']='shop_'.$param['shop_order_sn'];
    	}
    	if($param['event_order_sn']){ 
    		$data['tempid']='event_'.$param['event_order_sn'];
    	}
   		$param['title']=($param['title']) ? $param['title']:'系统消息';
   		$param['execute_time']=$param['execute_time']?$param['execute_time']:0;
		$data['execute_time']=!empty($param['execute_time'])?$param['execute_time']:time();
   		$data['contactways']=$contactways;
   		$data['param']=$param;
   		$data['siteid']=$siteid;
   		$data['param']['siteid']=$siteid;
   		$data['create_time']=time();
   		$data['status']=0;
   		$data['fromuid']=($param['fromuid'])?$param['fromuid']:0;
   		$data['level']=($level==0)?0:$level;
   		$data['temp']=$temp?$temp:1;
   		if($taskphp){ 
   			$this->do_addSendMessage($taskphp,$data);
   		}

   	}
    public function do_addSendMessage($taskphp,$data){

   		$data['taskphp']=mysql_real_escape_string($taskphp);
   
   		if($data['contactways']){ 
   			$length=count($data['contactways']);
			for($i=0;$i<$length;$i++){  
				$data['contactway']=$data['contactways'][$i];
				$data['param']['contactway']=$data['contactway'];
				$data['param']=array2string($data['param']);
				$returnlist=D('send_message')->add($data);
				$data['param']=string2array($data['param']);		
			}
   		}else{
   			$data['param']=array2string($data['param']);
   			$returnlist=D('send_message')->add($data);

   		} 	
   		
   	}
   	//得到数据并进行判断是否有联系方式，锁定进行发送的数据为-2
   	public  function getSendMessage($limit=1){ 
		$limit=(INT)$limit;
		$map =array('status'=>0,'execute_time'=>array('elt',time()));
		$list=D('send_message')->limit($limit)->order('level desc,execute_time asc,id asc')->where($map)->select();
		if($list){ 
			foreach ($list as $key => $val) {
    			D('send_message')->where("id = ".$val['id'])->setField('status',-2);
    		}
    		return $list;
    	}else{ 
    		return false;
    	}   
	}
	/*优先级处理*/

/*此方法用于在队列中显示，但不走队列，逻辑有瑕疵，周末回去改进*/
    public function do_addSendMessage_first($taskphp='send_email_to_user',$contactways='',$param='',$level=0,$temp=1){
    	$param['title']=($param['title']) ? $param['title']:'系统消息';
    	$param['execute_time']=$param['execute_time']?$param['execute_time']:0;
		$data['execute_time']=!empty($param['execute_time'])?$param['execute_time']:time();
   		$data['contactway']=$contactways[0];
   		$data['param']=$param;
   		$data['siteid']=SITEID;
		$data['param']['siteid']=SITEID;
   		$data['create_time']=time();
   		$data['status']=0;
   		$data['fromuid']=($param['fromuid'])?$param['fromuid']:0;
   		$data['level']=($level==0)?0:$level;
   		$data['temp']=$temp?$temp:1;
   		if($taskphp){ 
   			$data['taskphp']=mysql_real_escape_string($taskphp);
   			$data['param']['contactway']=$data['contactway'];
   			$data['param']=array2string($data['param']);
      		$returnid=D('send_message')->add($data);
			$this->first_circulate($returnid);   			
   		}
    	
    }
	public function first_circulate($returnid=''){
		D('send_message')->where("id = ".$returnid)->setField('status',-2);
		$list=D('send_message')->where("id = ".$returnid)->find();
		//var_dump($list);
		if($list['temp']==1){ 
			if($list['times'] >= 2){ 
				D('send_message')->where("id = ".$returnid)->setField(array('status'=>-1,'update_time'=>time()));
				return;
			} 
	
		}elseif($list['temp']==2){
			//这里的执行时间字段，作为最后操作时间节点
			if($list['execute_time']<time()){
				D('send_message')->where("id = ".$returnid)->setField(array('status'=>-1,'update_time'=>time()));
				return;
			}
		}
		$list['param']=string2array($list['param']);
		$data=$list['taskphp']($list['param']);
		//var_dump($list);
		if($data['error']){ 
				//成功后改状态为1  ,并移除数组
			D('send_message')->where('id = '.$returnid)->setField(array('status'=>1,'update_time'=>time(),'content'=>$data['content']));
			unset($list);
		}else{
				//失败后把锁定状态解除并次数加1 ，移除数组
			D('send_message')->where('id = '.$returnid)->setField(array('status'=>0,'times'=>$list['times']+1,'content'=>$data['content']));
			//再次调用自己，进入循环
			$this->first_circulate($returnid);
	
		}
	
	
	}



} 
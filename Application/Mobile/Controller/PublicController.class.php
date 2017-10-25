<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 想天
 * 创建日期: 3/12/14
 * 创建时间: 12:49 PM
 * 版权所有 想天工作室(www.ourstu.com)
 */

namespace Mobile\Controller;


use Think\Controller;

class PublicController extends MobileController
{
    /**获取个人资料，用以支持小名片
     * @auth 陈一枭
     */
    public function getProfile()
    {
        $uid = intval($_REQUEST['uid']);
        $userProfile = query_user(array('uid', 'nickname','constellation', 'avatar64', 'space_url', 'following', 'fans', 'weibocount', 'signature', 'rank_link'), $uid);
        $follow['follow_who'] = $userProfile['uid'];
        $follow['who_follow'] = is_login();
        $userProfile['followed'] = D('Follow')->where($follow)->count();
        $userProfile['following_url'] = U('Mobile/People/following', array('uid' => $uid));
        $userProfile['fans_url'] = U('Mobile/People/fans', array('uid' => $uid));
        $userProfile['weibo_url'] = U('Mobile/People/appList', array('uid' => $uid, 'type' => "weibo"));
        if($userProfile['constellation']>0){
		  $userProfile['constellation']=get_constellation($userProfile['constellation']);
		}else{
		  $userProfile['constellation']='未填写';
		}
		
		$html = '';
        if (count($userProfile['rank_link'])) {
            foreach ($userProfile['rank_link'] as $val) {
                if ($val['is_show']) {
                    $html = $html . '<img class="img-responsive" src="' . $val['logo_url'] . '" title="' . $val['title'] . '" alt="' . $val['title'] . '" style="width: 18px;height: 18px;vertical-align: middle;margin-left: 3px;display: inline;"/>';
                }
            }
            unset($val);
        }
        $userProfile['rank_link']=$html;
        echo json_encode($userProfile);
    }

    /**关注某人
     * @param int $uid
     * @auth 陈一枭
     */
    public function follow($uid = 0)
    {

        if (D('Follow')->follow($uid)) {
            exit(json_encode(array('status' => 1)));
        } else {
            exit(json_encode(array('status' => 0)));
        }
    }
	
    /**取消对某人的关注
     * @param int $uid
     * @auth 陈一枭
     */
    public function unfollow($uid = 0)
    {
        if (D('Follow')->unfollow($uid)) {
            exit(json_encode(array('status' => 1)));
        } else {
            exit(json_encode(array('status' => 0)));
        }
    }
    public function verify()
    {
        $verify = new \Think\Verify();
        $verify->entry(2);
    }
    public function currentSession()
    {
        $currentSession=D('Common/Talk')->getCurrentSessions();
		print_r($currentSession);
		exit;
    }
    /**检测消息
     * 返回新聊天状态和系统的消息
     * @auth 陈一枭
     */
    public function getInformation()
    {
        $message = D('Common/Message');
        //取到所有没有提示过的信息
        $haventToastMessages = $message->getHaventToastMessage(is_login());

        $message->setAllToasted(is_login()); //消息中心推送

        $new_talks = D('TalkPush')->getAllPush(); //聊天推送
        D('TalkPush')->where(array('uid' => get_uid(), 'status' => 0))->setField('status', 1); //读取到推送之后，自动删除此推送来防止反复推送。

        $new_talk_messages = D('TalkMessagePush')->getAllPush(); //聊天消息推送
        D('TalkMessagePush')->where(array('uid' => get_uid(), 'status' => 0))->setField('status', 1); //读取到推送之后，自动删除此推送来防止反复推送。

        foreach($new_talk_messages as &$message){
            $message=D('TalkMessage')->find($message['source_id']);
            $message['user'] = query_user(array('avatar64', 'uid', 'username'), $message['uid']);
            $message['ctime'] = date('m-d h:i', $message['create_time']);
            $message['avatar64'] = $message['user']['avatar64'];
            $message['content']=parse_expression($message['content']);
        }
        exit(json_encode(array('messages' => $haventToastMessages, 'new_talk_messages' => $new_talk_messages, 'new_talks' => $new_talks)));
    }

    /**设置全部的系统消息为已读
     * @auth 陈一枭
     */
    public function setAllMessageReaded()
    {
        D('Message')->setAllReaded(is_login());
    }

    /**设置某条系统消息为已读
     * @param $message_id
     * @auth 陈一枭
     */
    public function readMessage($message_id)
    {
        exit(json_encode(array('status' => D('Common/Message')->readMessage($message_id))));

    }
	
	public function getmobile_login_verify($mobile='',$verifyCodebythink=''){
		//var_dump($verifyCodebythink);
		$check_verify_result=D('Usercenter')->check_verify_think($verifyCodebythink,2);
		if($check_verify_result['status']==0){ 
			exit(json_encode(array('status'=>$check_verify_result['status'],'info'=>$check_verify_result['info'])));
		}
		$result=D('Usercenter')->get_verify($mobile,0,'mobile');
		exit(json_encode(array('status'=>$result['status'],'info'=>$result['info'])));
	}
	
	public function getmobile_reg_verify($mobile='',$verifyCodebythink=''){
		$check_verify_result=D('Usercenter')->check_verify_think($verifyCodebythink,2);
		if($check_verify_result['status']==0){ 
			exit(json_encode(array('status'=>$check_verify_result['status'],'info'=>$check_verify_result['info'])));
		}
		$result=D('Usercenter')->get_verify($mobile,2,'mobile');
		exit(json_encode(array('status'=>$result['status'],'info'=>$result['info'])));
	}
	public function getmobile_mi_verify($mobile='',$verifyCodebythink=''){
		$check_verify_result=D('Usercenter')->check_verify_think($verifyCodebythink,2);
		if($check_verify_result['status']==0){ 
			exit(json_encode(array('status'=>$check_verify_result['status'],'info'=>$check_verify_result['info'])));
		}
		$result=D('Usercenter')->get_verify($mobile,1,'mobile');
		exit(json_encode(array('status'=>$result['status'],'info'=>$result['info'])));
	}
	public function getmobile_public_verify($mobile='',$verifyCodebythink=''){
		$check_verify_result=D('Usercenter')->check_verify_think($verifyCodebythink,2);
		if($check_verify_result['status']==0){ 
			exit(json_encode(array('status'=>$check_verify_result['status'],'info'=>$check_verify_result['info'])));
		}
		$result=D('Usercenter')->get_verify($mobile,3,'mobile');
		exit(json_encode(array('status'=>$result['status'],'info'=>$result['info'])));
	}
	
	
    public function upload_avatar($img = null)
    {
		
		$data=$img;
		preg_match('/^(data:\s*image\/(\w+);base64,)/', $data,$result);
		$ext = $result[2];
		if(!in_array($ext,array("jpg","jpeg","png","gif"))){
			exit(json_encode(array('status'=>0,'info'=>'不支持本格式上传')));
		}
		$path='./Uploads/Avatar/'.date('Y-m-d').'/';
		if(!file_exists($path)){
            mkdir($path,0777);
        }
		$file=$path.uniqid().'.'.$ext;
		$data = str_replace($result[1],"",$data);
		if (file_put_contents($file,base64_decode($data))==true) {
			$outdata['url'] = substr($file,1);
			exit(json_encode(array('status'=>1,'info'=>'上传成功','data'=>$outdata)));
		}else{
			exit(json_encode(array('status'=>0,'info'=>'图片上传失败')));
		}
    }
	
	
	
   /**
	 * 处理图片上传
	 */
	public function upload(){

		$data=$_POST['formFile'];
		preg_match('/^(data:\s*image\/(\w+);base64,)/', $data,$result);
		$ext = $result[2];
		if(!in_array($ext,array("jpg","jpeg","png","gif"))){
			exit(json_encode(array('status'=>0,'info'=>'不支持本格式上传')));
		}
		$path='./Uploads/Picture/'.date('Y-m-d').'/';
		if(!file_exists($path)){
            mkdir($path,0777);
        }
		$file=$path.time().'.'.$ext;
		$data = str_replace($result[1],"",$data);
		if (file_put_contents($file,base64_decode($data))==true) {
			$save_data=array('type'=>'local','path'=>substr($file,1),'create_time'=>time(),'status'=>1);
			$images=D('picture')->add($save_data);
			if($images){
				$outdata['id'] = $images;
				$outdata['path'] = substr($file,1);
				exit(json_encode(array('status'=>1,'info'=>'上传成功','data'=>$outdata)));
			}else{
				exit(json_encode(array('status'=>0,'info'=>'图片上传失败')));
			}	
		}else{
			exit(json_encode(array('status'=>0,'info'=>'图片上传失败')));
		}
		
	}
	
    public function get_uploadthumb($id='',$thumb_width=80,$thumb_height=80,$width=0,$height=0)
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
	
    public function get_upload_singlethumb($id='',$thumb_width=80,$thumb_height=80)
    {
	  if(!$id) return false;
	  if($thumb_width && $thumb_width){
			$data['url_thumb'] = getThumbImageById($id,$thumb_width,$thumb_height);
		}else{
			$data['url_thumb'] = get_cover($id,'path');

		} 
	  echo json_encode($data);
      exit;
    }
}
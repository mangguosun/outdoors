<?php
namespace Manage\Controller;
/*
*活动设置
**/
class PartnerController extends BaseController
{
	//列表
	public function index(){
		$event_type=I('event_type');
		$event_name=I('event_name');
		$nickname=I('nickname');
		if($event_type){
    		$map['event_type']=$event_type;
    	}
    	if($event_name){
    		$event_name    =   urlsafe_b64decode($event_name);
    		$map['title']=array('like', '%' . (string)$event_name . '%');
    	}
    	if($nickname){
            $nickname    =   urlsafe_b64decode($nickname);
            $nickname_map['nickname']=array('like', '%' . (string)$nickname . '%');
            $userids=D('Member')->where($nickname_map)->getField('uid',true);
            $userid = implode(',', $userids);
            $map['uid']= array('in',$userid);
       }
       $map['siteid']=SITEID;
       $partner_data = D('Partner')->where($map)->select();
         foreach($partner_data as $k=>$v){                                      
            $partner_data[$k]['nickname']=query_user('nickname',$v['uid']);
            if($v['deadline']<=time()){
            	D("Partner")->where('id='.$v['id'])->save(array('event_status'=>2));
            }
        }
        $this->assign('partner_data', $partner_data);
		$this->display();
    }

     public function partner_search(){
    	$data = $_GET;
    	unset($_GET['url']);
        if($_GET['event_name'] != ''){
            $_GET['event_name'] = urlsafe_b64encode($_GET['event_name']);
        }
        if($_GET['event_type'] != ''){
            $_GET['event_type']= $_GET['event_type'];
        }
        if($_GET['nickname'] != ''){
            $_GET['nickname']=urlsafe_b64encode($_GET['nickname']);
        }
    	$url=U($data['url'],$_GET);
		header("Location:$url");
    }
    //修改页面
    public function edit($id=""){
    	$partner=D('Partner')->where('id='.$id)->find();
    	if(!$partner){
    		$this->error('404 not found');
    	}
    	$partner['start_time']=date('Y-m-d H:i:s',$partner['start_time']);
    	if($partner['end_time']){
    		$partner['end_time']=date('Y-m-d H:i:s',$partner['end_time']);
    	}
    	$partner['deadline']=date('Y-m-d H:i:s',$partner['deadline']);
    	$this->assign('partner',$partner);
    	$this->display();
    }
    //修改操作
    public function set_partner($id,$title="",$partner_type="",$starttime="",$endtime="",$stoptime="",$pictures_id="",$address="",$content="",$maxpeople=""){
    	$starttime=strtotime($starttime);
    	$endtime=strtotime($endtime);
    	$stoptime=strtotime($stoptime);
		if (trim(op_t($title)) == '') {
            $this->error('请输入标题');
        }
        if(!$starttime){
        	$this->error('请选择活动开始时间');
        }
        if(!$stoptime){
        	$this->error('请选择报名截止时间');
        }
        if($starttime <= time()){
    		$this->error('开始时间必须大于当前时间');
    	}
    	if(($stoptime >= $starttime) OR $stoptime <= time()){
    		$this->error('截止报名时间大于当前时间且必须小于开始时间');
    	}
    	if($endtime){
    		if($endtime <= $starttime){
    			$this->error('结束时间必须大于开始时间');
    		}
    	}
        if(!$pictures_id){
        	$this->error('请上传封面图片');
        }
        if(trim(op_t($address)) == ''){
        	$this->error('请输入活动地址');
        }
        if(trim(op_t($content)) == ''){
        	$this->error('请输入的活动描述');
        }
        $partner['title']=$title;
        $partner['event_type']=$partner_type;
        $partner['start_time']=$starttime;
        $partner['end_time']=$endtime;
        $partner['deadline']=$stoptime;
        $partner['picture_id']=$pictures_id;
        $partner['address']=$address;
        $partner['details']=$content;
        $partner['human_number']=$maxpeople;
        $set_partner=D('Partner')->where('id='.$id)->save($partner);
        if($set_partner){
        	$this->success('编辑成功',U('Manage/Partner/index'));
        }else{
        	$this->error('数据未修改');
        }
    }
    
}  

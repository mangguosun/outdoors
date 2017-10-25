<?php
namespace Manage\Controller;

class MarkController extends BaseController
{
    public function index(){
    	$mark_type=I('mark_type');
    	$mark_event=I('mark_event');
        $mark_cname=I('mark_cname');
    	if($mark_type){
    		$map['typecode']=$mark_type;
    	}
       if($mark_event){
            $mark_event    =   urlsafe_b64decode($mark_event);
            $title['title']=array('like', '%' . (string)$mark_event . '%');
            $partnerids=D('Partner')->where($title)->getField('id',true); 
            $partnerid = implode(',', $partnerids);
            $map['partnerid']= array('in',$partnerid);  
       }
       if($mark_cname){
            $mark_cname    =   urlsafe_b64decode($mark_cname);
            $nickname_map['nickname']=array('like', '%' . (string)$mark_cname . '%');
            $userids=D('Member')->where($nickname_map)->getField('uid',true);
            $userid = implode(',', $userids);
            $map['userid']= array('in',$userid);
       }
       $map['siteid']=SITEID;
        $mark_data = D('Mark')->where($map)->select();
        foreach($mark_data as $k=>$v){                                      
            $map=array('id'=>$v['partnerid']);
            $mark_data[$k]['partnertitle'] =D('Partner')->where($map)->getField('title');
            $mark_data[$k]['nickname']=query_user('nickname',$v['userid']);
        }
        $this->assign('mark_data', $mark_data);
		$this->display();
    }

    public function mark_search(){
    	$data = $_GET;
    	unset($_GET['url']);
        if($_GET['mark_event'] != ''){
            $_GET['mark_event'] = urlsafe_b64encode($_GET['mark_event']);
        }
        if($_GET['mark_type'] != ''){
            $_GET['mark_type']= $_GET['mark_type'];
        }
        if($_GET['mark_cname'] != ''){
            $_GET['mark_cname']=urlsafe_b64encode($_GET['mark_cname']);
        }
    	$url=U($data['url'],$_GET);
		header("Location:$url");
    }


    
}  

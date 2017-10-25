<?php

namespace Websit\Controller;

use Think\Controller;

class MemberController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize();    
	}
	public function index(){
	       $status=I('status');
		   switch($status){
		      case 0:
				$map = "siteid= ".SITEID;
				$mobile = $_POST['mobile'];
				if(!empty($mobile)){
					$map .= " and (select mobile from thinkox_ucenter_member as um where um.id = thinkox_member.uid) = {$mobile}";
				}				
			    $count=D('member')->where($map)->count();
				$Page       = new \Think\Page($count,10);// 
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				$show       = $Page->show();// 
			    $member=D('member')->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
			    
				    foreach($member as $key=>$val){
				        $umeber=D('ucenter_member')->where("id=".$val['uid'])->find();
						$member[$key]['email']=$umeber['email'];
						$member[$key]['mobile']=$umeber['mobile'];
				    }
				    $this->assign('member',$member);
					$this->assign('mobile',$mobile);
					$this->assign('page',$show);// 
			  break;
			  case 1:
			     $recommendm=$_GET['recommendm'];
				 if($recommendm){
				    $map['recommendm']	= $recommendm;
				 }
				    $map['is_use']	= 	2;
					$map['siteid']	=	SITEID;
					
			     $count=D('member')->where($map)->count();
				 $Page       = new \Think\Page($count,10);// 
				 $Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				 $show       = $Page->show();// 
				 $member=D('member')->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
						foreach($member as $key=>$val){
							$user	=	query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $val['uid']);
							$member[$key]['email']=$user['email'];
						}
				
			       $this->assign('team',$member);
				   $this->assign('page',$show);//
			  break;
			  case 2://达人
					$map['is_use']	= 	4;
					$map['siteid']	=	SITEID;
				 $count=D('member')->where($map)->count();
				 $Page       = new \Think\Page($count,10);// 
				 $Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				 $show       = $Page->show();// 
				 $member=D('member')->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
						foreach($member as $key=>$val){
							$user	=	query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $val['uid']);
							$member[$key]['email']=$user['email'];
						}
				
				   $this->assign('master',$member);
				   $this->assign('page',$show);//
			  break;
		      case 3://角色 
					$count=D('member_upgrad_group')->where("siteid=".SITEID)->count();
					$Page       = new \Think\Page($count,10);// 
					$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
					$show       = $Page->show();// 
					$role	=	D('member_upgrad_group')->where("siteid=".SITEID)->limit($Page->firstRow.','.$Page->listRows)->select();
				      foreach($role as $k=>$v){
						$user	=	query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $v['uid']);
						$role[$k]['nickname']	= $user['nickname'];
						
					 }
					$this->assign('role_member',$role);
					$this->assign('page',$show);//
				
			  break;
		   }
		   $this->assign('status',$status);
		   $this->assign('user',$this->userdata);
		   $this->display();
	}
	/*更改用户状态*/
	public function member_manage_status(){
	       $status=I('status');
		   $data['status']=$status;
		   $member=D('member')->where(" uid =".I('id'))->save($data);
		   $ucenter=D('ucenter_member')->where('id='.I('id'))->save($data);
		   
		   if($ucenter && $member){
			  $this->success('更改成功');
		   }else{
			  $this->error('更改失败');
		   }
	}
	/*2014-11-26-dlx--角色申请*/
	public function member_manage_role(){
		$status	=	$_POST['status'];
		 $uid	=	$_POST['uid'];
		 $id		=	$_POST['id'];
		 $is_use	=	$_POST['is_use'];
		if($status=='' || $id=='' || $uid=='' || $is_use=='') $this->error('参数错误!');
		
		if($status==1){
		       $data	= array(
			         'status'	=> $status,
					 'gmuid'	=>	is_login(),
					 'update_time'=>time()
			    );
			   $upgradlist	=	D('member_upgrad_group')->where("id=".$id)->save($data);
			   $member		=	D('member')->where("uid=".$uid)->save(array('is_use'=>$is_use));
			   if($upgradlist && $member){
					$this->success('操作成功');
			   }else{
					$this->error('操作失败!');
			   }
		
		}elseif($status==-2){
					$cates	= array(
						'status'	=> $status,
						'gmuid'	=>	is_login(),
						'update_time'=>time()
					);
				 $upgradNlist	=	D('member_upgrad_group')->where("id=".$id)->save($cates);
				if($upgradNlist){
					$this->success('操作成功');
				}else{
					$this->error('操作失败!');
				}
		
		}
	
	}
	/*会员管理*/
	public function member_manage(){
	       $status=I('status');
		   switch($status){
		      case 0:
			    $count=D('member')->where("siteid=".SITEID)->count();
				$Page       = new \Think\Page($count,10);// 
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				$show       = $Page->show();// 
			    $member=D('member')->where("siteid= ".SITEID)->limit($Page->firstRow.','.$Page->listRows)->select();
			    
				    foreach($member as $key=>$val){
				        $umeber=D('ucenter_member')->where("id=".$val['uid'])->find();
						$member[$key]['email']=$umeber['email'];
				       
				    }
				    $this->assign('member',$member);
					$this->assign('page',$show);// 
			  break;
			  case 1:
			     $recommendm=$_GET['recommendm'];
				 if($recommendm){
				    $map['recommendm']	= $recommendm;
				 }
				    $map['is_use']	= 	2;
					$map['siteid']	=	SITEID;
					
			     $count=D('member')->where($map)->count();
				 $Page       = new \Think\Page($count,10);// 
				 $Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				 $show       = $Page->show();// 
				 $member=D('member')->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
						foreach($member as $key=>$val){
							$user	=	query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $val['uid']);
							$member[$key]['email']=$user['email'];
						}
				
			       $this->assign('team',$member);
				   $this->assign('page',$show);//
			  break;
			  case 2://达人
					$map['is_use']	= 	4;
					$map['siteid']	=	SITEID;
				 $count=D('member')->where($map)->count();
				 $Page       = new \Think\Page($count,10);// 
				 $Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				 $show       = $Page->show();// 
				 $member=D('member')->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
						foreach($member as $key=>$val){
							$user	=	query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $val['uid']);
							$member[$key]['email']=$user['email'];
						}
				
				   $this->assign('master',$member);
				   $this->assign('page',$show);//
			  break;
		      case 3://角色 
					$count=D('member_upgrad_group')->where("siteid=".SITEID)->count();
					$Page       = new \Think\Page($count,10);// 
					$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
					$show       = $Page->show();// 
					$role	=	D('member_upgrad_group')->where("siteid=".SITEID)->limit($Page->firstRow.','.$Page->listRows)->select();
				      foreach($role as $k=>$v){
						$user	=	query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $v['uid']);
						$role[$k]['nickname']	= $user['nickname'];
						
					 }
					$this->assign('role_member',$role);
					$this->assign('page',$show);//
				
			  break;
		   }
		   $this->assign('status',$status);
		   $this->assign('user',$this->userdata);
		   $this->display();
	}
	/*禁用--退出领队--2014-11-26*/
	public function manage_team_disable(){
	     $uid	=	$_POST['uid'];
		 if($uid=='') $this->error('参数错误!');
		 $data['recommendm']=0;
		 $data['is_use']	=1;
		 
		 $team_mem	=	D('member')->where(" uid =".$uid)->save($data);
			 if($team_mem){
				$this->success('操作成功');
			 }else{
				$this->error('操作失败');
			 }
	}
	/*首页推荐领队*/
	public function doRecommendm(){
	        $count=D('member')->where("recommendm = 1 and siteid=".SITEID)->count();
			if($count <3){
				 $data['recommendm']=I('recommendm');
				 $reds=D('member')->where(" uid =".I('uid'))->save($data);
				 if($reds){
					$this->success('操作成功');
				 }else{
					$this->error('操作失败');
				 }
			}else{
			    $this->success('亲!最多推荐3个领队哦');
			}
	
	}
	/*取消推荐*/
	public function cancelRecommendm(){
		$data['recommendm']=I('recommendm');
		$reds=D('member')->where(" uid =".I('uid'))->save($data);
			 if($reds){
				$this->success('操作成功');
			 }else{
				$this->error('操作失败');
			 }
	
	}
	public function pwd_reset(){
		if(IS_POST){
			$uid = $_POST['uid'];
			$new_pwd = $_POST['new_pwd'];
			$new_pwd_r = $_POST['new_pwd_r'];
			if($new_pwd != $new_pwd_r){
				$this->error('两次密码不一致！');
			}
			if(strlen($new_pwd) > 30 || strlen($new_pwd) < 6){
				$this->error('密码长度为6-30位之间');
			}
			$data = array('id' => $uid, 'password' => $new_pwd);
			$model = D('User/UcenterMember');
			$data = $model->create($data);
			if (!$data) {
				$this->error('密码格式不正确');
			}			
			$result = $model->where(array('id' => $uid,'siteid' => SITEID))->save($data);
			if ($result===false) {
				$this->error('数据库写入错误');
			}
			
			$this->success('密码重置成功','refresh');
		}else{	
			$uid = $_GET['id'];
			$this->assign('uid',$uid);
			$this->display();
		}
	}
}  
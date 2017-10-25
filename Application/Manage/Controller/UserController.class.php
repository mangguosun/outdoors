<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;
use User\Api\UserApi;


class UserController extends BaseController
{
	protected $member;

    public function _initialize()
    {
        parent::_initialize();
		$this->member = D('member');
		$this->uc_member = D('ucenter_member');
		
	}
	

	public function index(){

		$endtime=strtotime(I('endtime'));
	 	$starttime=strtotime(I('starttime'));
	 	if(empty($starttime)){ 
	 		$starttime=0;
	 	}
	 	if(!$endtime){ 
	 		$endtime=time();
	 	}
	 	$map['reg_time']=array('between',array($starttime,$endtime));
	 	$user_list=I('user_list');
	 	$sl_check=I('sl_check');
	 	if(!empty($user_list)){ 
	 		switch ($sl_check) {
	 				case 0:
	 					$map['nickname']=array('like',$user_list.'%');
	 					break;
 					case 1:
 						$map2['email'] =array('like',$user_list.'%');
 					break;
 					case 2:
 						$map2['mobile']=array('like',$user_list.'%');
 					break;
	 				
	 				default:
	 					# code...
	 					break;
	 			}	
	 	}
	 	if($map2){ 
	 		$id=$this->uc_member->where($map2)->Field('id')->select();
		 	foreach ($id as $ke => $va) {
		 		$uid .=$va['id'].",";
		 	}
		 	$uid=substr($uid ,0 ,-1) ; 
	 		$map['uid']=array('in',$uid); 
	 	}
		$map['siteid']=SITEID; 
		$count=$this->member->where($map)->count();
		$Page       = new \Think\Page($count,10);// 
		$show       = $Page->show(); 
		$list = $this->member->where($map)->limit($Page->firstRow.','.$Page->listRows)->order('uid desc')->select();
		foreach($list as $key=>$val){
            $new_list[$key]=$val;
		    $users  = query_user(array('id','username','nickname', 'email','mobile'), $val['uid']);
			$new_list[$key]['id']=$users['id'];
			$new_list[$key]['mobile']=$users['mobile'];
			$new_list[$key]['email']=$users['email'];
		}
		$time=date("Y-m-d",time());
		$this->assign('endtime',$time);
		$this->assign('user_info',$new_list);
		$m_level_name=get_upgrading();
		$this->assign('m_level_name',$m_level_name);	
	 	$this->assign('page',$show);
		$this->display();


	}	
	//领队
    public function team_member(){
        $map = array('status' => array('egt',0),'siteid'=>SITEID,'is_use' => array('eq',2));
		$list = $this->member->where($map)->order('recommendm desc,uid desc')->select();
		foreach($list as $key=>$val){
			 $new_list[$key]=$val;
			 $users  = query_user(array('id','username','nickname','is_use','recommendm'), $val['uid']);
			 $new_list[$key]['id']=$users['id'];
			 $new_list[$key]['mobile']=$users['mobile'];
			 $new_list[$key]['email']=$users['email'];
			 
		}
		$this->meta_title = '领队信息';
       	$this->assign('datainfo',$new_list);
       	$m_level_name=get_upgrading();
		$this->assign('m_level_name',$m_level_name);
       	$this->display();
    }
	//达人
	  public function master(){
		//读取列表
        $map = array('status' => array('egt',0),'siteid'=>SITEID,'is_use' => array('eq',4));
		$list = $this->member->where($map)->order('uid desc')->select();
		foreach($list as $key=>$val){
			 $new_list[$key]=$val;
			 $users  = query_user(array('id','username','nickname', 'signature','is_use','mobile','recommendm'), $val['uid']);
			 $new_list[$key]['id']=$users['id'];
			 $new_list[$key]['mobile']=$users['mobile'];
			 $new_list[$key]['email']=$users['email']; 
		}
        $this->meta_title = '达人信息';
       	$this->assign('datainfo',$new_list);
       	$m_level_name=get_upgrading();
		$this->assign('m_level_name',$m_level_name);
       	$this->display();
	}
	//会员审核
	public function audit(){ 
		$user_id = I('user_id');
		$map = array('siteid'=>SITEID);
		if (is_numeric($user_id)) {
            $map['uid'] = array(intval($user_id), array('like', '%' . $user_id . '%'), '_multi' => true);
            
        }
		$role	=	D('member_upgrad_group')->where($map)->select();
	      foreach($role as $k=>$v){
			$user	=	query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $v['uid']);
			$role[$k]['nickname']	= $user['nickname'];
			}
		$this->meta_title = '会员审核';	
		$this->assign('role_member',$role);
		$this->assign('page',$show);
		$this->display();


	}
	//角色申请
	public function member_manage_role($status='',$uid='',$id='',$is_use=''){
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
	 /*
	*设置推荐
	**/
	public function doRecommendm($tip,$id=array())
    {
    	$ids = is_array($id) ? implode(',', $id) : $id;
        if (empty($ids)) {
            $this->error('请选择要操作的数据!');
        }
		$count = $this->member->where(array('siteid'=>SITEID,'recommendm'=>1))->count();
		if($count+count($id)>3){
			$this->error('亲!每个网站总共可推荐3个'.get_upgrading(2).'哦');
		}
		$this->member->where(array('uid' => array('in', $ids)))->setField('recommendm', $tip);
        $this->success('设置成功', $_SERVER['HTTP_REFERER']);
    }
    /*
	*取消推荐
	**/
	public function cancel_recommendm($tip,$id=array()){
		$ids = is_array($id) ? implode(',', $id) : $id;
        if (empty($ids)) {
            $this->error('请选择要操作的数据!');
        }
		$this->member->where(array('uid' => array('in', $ids)))->setField('recommendm', $tip);
		$this->success('设置成功', $_SERVER['HTTP_REFERER']);	
	}
	 /*
	*踢出领队和达人
	**/
	public function manage_team_disable($uid=''){
		 if($uid=='') $this->error('参数错误!');
		 $data['recommendm']=0;
		 $data['is_use']	=1;
		 $team_mem	=$this->member->where(" uid =".$uid)->save($data);
			 if($team_mem){
			 	clean_query_user_cache($uid,array('is_use','recommendm'));
				$this->success('更改成功',$_SERVER['HTTP_REFERER']);
			 }else{
				$this->error('操作失败');
			 }
	}
	/**
     * @param int $id
     * @param $password
     * @param $is_use
     * @param $url
     * @author 郑钟良<zzl@ourstu.com>
     */
	//更改密码
	public function password_edit($id=0,$newpassword='',$uid=0){ 
		if(IS_POST){ 
			if($newpassword != '' || $newpassword != null){
				$data = array('password'=>$newpassword);
				$model = D('Common/UcMemberpublic');
				$data = $model->create($data);
				if (!$data) {
					$this->error('密码格式不正确');
				}
				$rs=$this->uc_member->where(array('id' => $uid,'siteid' => SITEID))->save($data);
				if ($rs===false) {
					$this->error('数据库写入错误');
				}
				//显示成功消息
				$this->success('密码更改成功', U('User/index'));
			}else{
				$this->error('密码不能为空');
			}
		}else{ 
			$user=query_user(array('uid','is_use','nickname','last_login_time'),$id);
		}
		
		$this->assign('datainfo',$user);
		$this->display();
	}

	//更改角色
	public function user_edit($id=0,$is_use=1,$uid=0){

		if(IS_POST){ 
			$data['is_use']=$is_use;
			$rs=$this->member->where('uid = '.$uid)->save($data);

			if($rs){
				clean_query_user_cache($uid,'is_use');
				clean_people_info_cash();
				$this->success('更改成功',U('User/index'));
			}else{
				$this->error('更改失败');
			}
		}else{ 
			$user=query_user(array('uid','is_use','nickname','last_login_time'),$id);
		}
		
		$this->assign('datainfo',$user);
		$this->display();

	}


	/*
	*清空回收站**
	*/
	public function usertrash($page = 1, $r = 20, $model = ''){
		
		$builder = new AdminListBuilder();
		$builder->clearTrash($model);
		//读取列表
        $map = array('status' => -1,'siteid'=>SITEID,);
        $list = $this->member->where($map)->page($page, $r)->select();
        $totalCount = $this->member->where($map)->count();
        foreach($list as $key=>$val){
			 $new_list[$key]=$val;
			 $users  = query_user(array('id','username','nickname', 'signature','is_use','mobile','recommendm'), $val['uid']);
			 $new_list[$key]['id']=$users['id'];
			
			 
		}
        //显示页面
        $attr['class'] = 'ajax-post';
        $attr['target-form'] = 'ids';

        $builder->title('用户回收站')
            ->setStatusUrl(U('setUserStatus'))->buttonRestore()->buttonClear('member')
			->keyUid('uid','用户ID')
			->key('score','积分')
			->keyCreateTime('reg_time','创建时间')
			->keyUpdateTime('last_login_time','最后登录时间')
			->keyMap('is_use', '角色', get_upgrading())
			->keyMap('status', '状态', array(0 => '禁用', 2=> '启用',-1=>'回收站'))
			
            ->data($new_list)
            ->pagination($totalCount, $r)
            ->display();
    }
    //删除用户
 


    //启用和禁用
	 public function changeStatus($method = null)
    {
        $id = array_unique((array)I('id', 0));
        foreach ($id as $k => $v) {
        	if (is_admin($v)) {
	            $this->error("不允许对超级管理员执行该操作!");
	        }
        }
        $id = is_array($id) ? implode(',', $id) : $id;
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        
         $map = array('uid' => array('in',$id),'siteid'=>SITEID);
        switch (strtolower($method)) {
            case 'resumeuser':
              	$this->member->where($map)->setField('status', 1);
              	clean_people_info_cash();
              	$this->success('启用成功');
				 break;
            case 'forbiduser':
               $this->member->where($map)->setField('status',0);
               clean_people_info_cash();
              	$this->success('禁用成功');
                break;
            case 'deleteuser':
               	$this->member->where($map)->setField('status',-1);
               	clean_people_info_cash();
              	$this->success('删除成功');
                break;
            case 'user_delete':
            	$this->success('清除成功');
            	break;
            default:
                $this->error('参数非法');
        }
    }


    //更改用户级别显示
   	public function channel_member($status=0,$admin='',$leader='',$master='',$member=''){ 
   		if(IS_POST){ 
   			$data['status']=$status;
	   		$data['siteid']=SITEID;
	   		if($data['status']==1){ 
	   			$data['admin']=$admin;
	   			$data['leader']=$leader;
	   			$data['master']=$master;
	   			$data['member']=$member;
	   		}	
	   		$oldlist=D('channel_member')->where("siteid = ".$data['siteid'])->find();
	   		if($oldlist){ 
	   			$list=D('channel_member')->where('id = '.$oldlist['id'])->save($data);
	   		}else{ 
	   			$list=D('channel_member')->add($data);
	   		}
	   		if($list){ 
				$this->success('保存成功',U('Manage/User/index'));
			}else{ 
				$this->error('保存失败');
			}
   		}
   		$newlist=D('channel_member')->where("siteid = ".SITEID)->find();
   		$list['status']=$newlist['status']?1:0;
   		$list['admin']=$newlist['admin']?$newlist['admin']:'管理员';
   		$list['leader']=$newlist['leader']?$newlist['leader']:'官方领队';
   		$list['master']=$newlist['master']?$newlist['master']:'认证达人';
   		$list['member']=$newlist['member']?$newlist['member']:'普通会员';
   		$this->assign('title','会员角色自定义');
   		$this->assign('newlist',$list);

   		$this->display();
   	}
    /**
     * 设置状态
     * @param $ids
     * @param $status
     * autor:xjw129xjt
     */
    public function setUserStatus($ids, $status){
    
	    $data['status']=$status;
        $builder = new AdminListBuilder();
        if (is_array($ids)) {
        	$map['uid']=array('in',$ids);
        }else{ 

        	$map['uid']=array('eq',$ids);
        }
        $member=M('member');
        $result=$member->where($map)->data($data)->save();

        $builder ->doSetStatus('ucenter_member', $ids, $status);
    }
	
}

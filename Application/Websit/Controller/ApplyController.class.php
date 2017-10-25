<?php

namespace Websit\Controller;

use Think\Controller;

class ApplyController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize();    
	}
	/*
	 * 应用管理首页
	 * $param $id 公共表应用的ID
	 */
	public function index(){
		$group = $_GET['group'];
		$group = isset($group)?$group:'all';		
		switch($group){

			case 'all';
				$apply_event = D('websit_apply')->where(array('app_model'=>'Event'))->find();			
				$webinfo = json_decode(WEBSITEINFO,true);
				$type = $webinfo['theme_type'];
				
				
				$apply_all = D('websit_apply')->where(array('status'=>1))->select();
				$app_arr = array();
				foreach($apply_all as $key => $val){
					if($val['fit_type'] ==0 && $val['setup_id'] == 0){
						$app_arr[$key] = $val;
						
					}else{
						if($val['setup_id']){
							$setup_id_arr = explode(',',$val['setup_id']);
							if(in_array(SITEID,$setup_id_arr)){
								if($val['fit_type'] !=0){
									$fit_arr = explode(',',$val['fit_type']);
									if(in_array($type,$fit_arr)){
										$app_arr[$key] = $val;
									
									}
								}else{
									$app_arr[$key] = $val;	
									
								}
							}
						}else{
							$fit_arr = explode(',',$val['fit_type']);
							
							if(in_array($type,$fit_arr)){
								$app_arr[$key] = $val;
							}
						}
					}
				}
				
				$this->assign('apply_event',$apply_event);
				$this->assign('apply',$app_arr);
			break;
			
			case 1;
				$apply_arr = D('websit_install_apply')->where(array('siteid'=>SITEID))->select();
				foreach($apply_arr as $key => &$val){
					if(ucfirst($val['app_model']) == 'Event') continue;
					$app_arr[] = $val;
				}
				$apply_event = D('websit_apply')->where(array('app_model'=>'Event'))->find();
				$this->assign('apply_event',$apply_event);
				$this->assign('apply',$app_arr);
			break;
		}
		$this->assign('group',$group);
		$this->display();
	}
	/*
	 * 应用启用
	 * $param $id 公共表应用的ID
	 */
	public function install_app($id){
		$this->check_right();
		if(!$id) exit(json_encode(array('status'=>0,'msg'=>'参数错误！')));
		$app_info = D('websit_apply')->where(array('id'=>$id))->find();
		$data['siteid'] = SITEID;
		$data['app_id'] = $id;
		$data['app_name'] = $app_info['app_name'];
		$data['install_time'] = time();
		$data['app_model'] = $app_info['app_model'];
		$data['describe'] = $app_info['describe'];
		$data['ifconfig'] = $app_info['ifconfig'];
		$rs = D('websit_install_apply')->add($data);
		if($rs){
			$this->update_channel($app_info['app_model'],$app_info['is_nav'],1);
            exit(json_encode(array('status'=>1,'msg'=>'启用成功,页面即将跳转...')));
		}else{
			exit(json_encode(array('status'=>0,'msg'=>'启用失败')));
		}
	}
		
	/*
	 * 禁用应用
	 * $param $type 1表示在全部应用页操作 0表示在我的应用页操作
	 * $param $id 当$type 为1是 $id 必须为公共应用表id 当$type为0时，$id必须为私人应用表id
	 */
	public function unstall_app($id = 0){
		$this->check_right();
		if(!$id) exit(json_encode(array('status'=>0,'msg'=>'参数错误！')));
		$app_info = D('websit_apply')->where(array('id'=>$id))->find();	
		$rs = D('websit_install_apply')->where(array('app_id'=>$id,'siteid'=>SITEID))->delete();

		if($rs){
			$this->update_channel($app_info['app_model'],$app_info['is_nav'],0);
			exit(json_encode(array('status'=>1,'msg'=>'禁用成功,页面即将跳转...')));
		}else{
			exit(json_encode(array('status'=>0,'msg'=>'禁用失败！')));
		}
	}
	

	/*
	 * 检查操作权限
	 */
   public function check_right(){
		if(!checked_admin(is_login()) || !checked_vip(is_login())){
			exit(json_encode(array('status'=>0,'msg'=>'权限不足，无法操作！')));
		}
   }

	/*
	 * 启用或者禁用应用时，改变表channel的display字段值
	 * $param $model 模块名 $display 要改变的状态
	 */
	public function update_channel($model,$is_nav,$display){
		$model = ucfirst($model);
		
		
		
		
		if($is_nav){
			if($display == 1){
				$channel_websit_data = D('channel_websit')->where(array('siteid'=>SITEID,'model'=>$model))->find();
				
				if($channel_websit_data){
					$data['status'] = 1;
					$data['display'] = $display;
					D('channel_websit')->where(array('siteid'=>SITEID,'model'=>$model))->save($data);
				}else{
					if($model!='Event'){
						
					    $channel_data = D('websit_apply')->where(array('app_model'=>$model,'status'=>1))->find();
					    if($channel_data){
							$add_data['siteid'] = SITEID;
							$add_data['title'] 		= 	$channel_data['app_name'];
							$add_data['status']		= 	$channel_data['status'];
							$add_data['model'] 		= 	$channel_data['app_model'];
							$add_data['display']	=	$channel_data['is_nav'];
							D('channel_websit')->add($add_data);
					    }
						
					}
					
				}
				
			}else{
				$data['status'] = 0;
				$data['display'] = $display;
				if($model!='Event'){
					D('channel_websit')->where(array('siteid'=>SITEID,'model'=>$model))->save($data);
				}
			
			}
		}
	}
	
	
	
	
   /*
	* 操作前检查公共应用表的应用状态
	* $param $id int 公共表应用的ID
	* $param $type string 'json'表示json格式返回 'html'表示TP格式返回
	*/
	public function check_common_app_status($id,$type){
		$app_info = D('websit_apply')->where(array('id'=>$id))->find();
		switch($type){
			case 'json';
				if(!$app_info) exit(json_encode(array('status'=>0,'msg'=>'该应用不存在,无法操作！')));
				switch($app_info['status']){
					case -2;
						exit(json_encode(array('status'=>0,'msg'=>'该应用还在开发中,无法操作！')));
					break;
					case -1;
						exit(json_encode(array('status'=>0,'msg'=>'该应用已被下架,无法操作！')));
					break;
					case 0;
						exit(json_encode(array('status'=>0,'msg'=>'该应用已被禁用,无法操作！')));
					break;
				}
			break;
			case 'html';
				if(!$app_info) $this->error('该应用不存在,无法操作！');
				switch($app_info['status']){
					case -2;
						$this->error('该应用还在开发中,无法操作！');
					break;
					case -1;
						$this->error('该应用已被下架,无法操作！');
					break;
					case 0;
						$this->error('该应用已被禁用,无法操作！');
					break;
				}
			break;
		}
	}
	
	
	
	public function do_update(){
		$channel_arr = D('channel_websit')->select();
		foreach($channel_arr as $key => $val){
			$channel_info[$key] = D('channel')->where(array('id'=>$val['homeid']))->find();
			$model[$key] = ucfirst($channel_info[$key]['model']);
			$data[$key]['model'] = $model[$key];
			D('channel_websit')->where(array('id'=>$val['id']))->save($data[$key]);
		}
	}
	 public function apply_update(){
		$app_arr = D('websit_apply')->select();
		$siteid_arr = D('websit')->field('siteid')->select();
		foreach($siteid_arr as $key => $val){
			foreach($app_arr as $ke => $va){
				$list[$key]['siteid'] = $val['siteid'];
				$list[$key]['app_id'] = $va['id'];
				$list[$key]['app_name'] = $va['app_name'];
				$list[$key]['status'] = $va['status'];
				$list[$key]['install_time'] = time();
				$list[$key]['describe'] = $va['describe'];
				$list[$key]['app_model'] = $va['app_model'];
				$list[$key]['ifconfig'] = 0;
				D('websit_install_apply')->add($list[$key]);
			}
		}
   }
   
}  
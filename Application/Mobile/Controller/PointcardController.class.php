<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Mobile\Controller;

/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class PointcardController extends MobileController
{

    /* 领取入口 */
    public function index()
    {	
		
		if(is_login()){
			//$this->get_pointcard();
			$this->assign('receive_pointcard_type', 1);
		}else{
			//$this->get_pointcard();
			$this->assign('receive_pointcard_type', 2);
		}
		$this->display();
    }
	

    /* 未登录验证领取 */
    private function get_pointcard()
    {	
		$cardid = I('cardid');
		
		
		//$this->error('参数错误！');
		
    }
	
    /* 绑定劵码 */
	public function bindcard(){
		$cardid = I('cardid');
		$cardkey = I('cardkey');
		$cardid = strtoupper($cardid);
		if(!$cardid){
			$this->error('请正确输入劵码号！');
		}
		$result=D('Pointcard')->bindcard($cardid,$cardkey);
		if($result['status']==1){ 
			$this->success($result['info'],U('Mobile/Config/mypointcard'));
		}else{ 
			$this->error($result['info']);
		}
	}

    /* 未登录绑定劵码 */
	public function bindcard_tourist(){
		$cardid = I('cardid');
		$cardkey = I('cardkey');
		$cardid = strtoupper($cardid);
		$mobileverify = I('mobileverify');
		$mobile = I('mobile');
		
		if(!$cardid){
			$this->error('请正确输入劵码号！');
		}
		if(!$mobile){
			$this->error('请正确输入手机号！');
		}
		if(!$mobileverify){
			$this->error('请正确输入手机验证码！');
		}
		$result_verify=D('Usercenter')->check_verify($mobileverify,3);
		if($result_verify['status']!=1){
			$this->error($result_verify['info']);
		}
		
		$result=D('Pointcard')->CheckCardStatus($cardid);
		if($result['status']!=1){
			$this->error($result['info']);
		}
		

		$result_check=D('Usercenter')->logintoreg($mobile);
		if($result_check['status'] == 1){
			$result=D('Pointcard')->bindcard($cardid,$cardkey,$result_check['info']);
			if($result['status']==1){ 
				D('Usercenter')->unset_verify(3);
				$this->success($result['info'],U('Mobile/Config/mypointcard'));
			}else{ 
				$this->error($result['info']);
			}
		}else{ 
			$this->error($result_check['info']);
		}
	}
}
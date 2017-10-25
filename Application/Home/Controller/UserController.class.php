<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;

use User\Api\UserApi;

require_once APP_PATH . 'User/Conf/config.php';

/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class UserController extends HomeController
{

    /* 用户中心首页 */
    public function index()
    {

    }
    /**
	emails
	ajax判断
	*/
	public function doemails(){
	  $email=$_POST['email'];
	  $result_data=D('Usercenter')->check_user_email($email);
	  if($result_data){
	    echo 1;
	  }else{
	    echo 0;
	  }
	
	}
    /**
	mobile
	ajax判断             1 表示存在  0 表示不存在
	*/
	public function domobile(){
	  $mobile=$_POST['mobile'];
	   $result_data=D('Usercenter')->check_user_mobile($mobile);
	  if($result_data){
	    echo 1;
	  }else{
	    echo 0;
	  }
	
	}
	/*昵称*/
	public function doNicknames(){
	   $nickname=$_POST['nickname'];
	   $result_data=D('Usercenter')->check_user_nickname($nickname);
	   if($result_data){
	    echo 1;
	   }else{
	    echo 0;
	   }
	}
	/*注册协议*/
	public function user_agreement(){
	   $this->display();
	}


    /* 注册页面 */
    public function register($user_agreement=0,$nickname='', $password = '', $email = '', $verify = '',$mobileverify='')
    {

        if (IS_POST) { //注册用户
        	$result_reg=D('Usercenter')->register($user_agreement,$email,$password,$nickname,$mobileverify,$verify);
	        if($result_reg['status']==1){ 
	        	$this->success('登录成功!',U('Usercenter/Config/index'),1);
	        }else{ 
	        	$this->error($result_reg['info']);
	        } 
        } else { //显示注册表单
            if (is_login()) {
                redirect(U('Home/Index/index'));
            }
            $this->display();
        }
    }


    public function doCropAvatar($crop)
    {
        //调用上传头像接口改变用户的头像
        $result = callApi('User/applyAvatar', array($crop));
        $this->ensureApiSuccess($result);

        //显示成功消息
        $this->success($result['message'], U('Home/User/step3'));
    }

 

    /* 登录页面 */
    public function login($email = '', $password = '', $verify = '', $remember = '',$login_type=1,$login_url='')
    {
		

        $this->setTitle('用户登录');

        if (IS_POST) { //登录验证

        	if($login_type == 0){ 
        		$result_verify=D('Usercenter')->check_verify($verify,0);
        		if($result_verify['status']!=1){
					$this->error($result_verify['info']);
				}
				$result_login=D('Usercenter')->logintoreg($email);
        	}else{ 
        		$result_login=D('Usercenter')->login($email, $password, $remember = true,3,$verify);
        	}
        	if($result_login['status']==1){ 
        		//购物车存入数据库
        		D('Usercenter')->unset_verify();
				D('Shop')->shop_cart_session();
				$login_url = $_POST['login_url'];//特殊页面跳转
				if(!empty($login_url)){
					$this->success('登录成功!',$login_url,1);
				}else{
					//TODO:跳转到登录前页面
					$referer_url=$_POST['referer_url'];
						if(checked_admin($result_login['uid'])){
							$this->success('登录成功!',U('Manage/Index/index'));
						}else{
							$this->success('登录成功!',$referer_url,1);
						}
				}
			}else{
				  $this->error($result_login['info']);
			}
            
        } else { //显示登录表单
            if (is_login()) {
                redirect(U('Home/Index/index'));
            }
            $map_adv['pos'] = 'login_adv_new';
            $map_adv['status'] = 1;
            $sing = M('advertising')->where($map_adv)->find();
            $advMap['position'] = $sing['id'];
            $advMap['status'] = 1;
            $advMap['siteid'] = SITEID;
            $dataadv = D('advs')->where($advMap)->order('level asc,id asc')->find();

            $this->assign('dataadv',$dataadv);
			$this->assign('login_url',think_decrypt($_GET['login_url']));

	        $this->display();
        }
    }

    /* 退出登录 */
    public function logout()
    {
        if (is_login()) {
            D('Memberpublic')->logout();
            $this->success('退出成功！', U('User/login'));
        } else {
            $this->redirect('User/login');
        }
    }

    /* 验证码，用于登录和注册 */
    public function verify()
    {
        $verify = new \Think\Verify();
        $verify->entry(1);
    }
    /* 验证码，用于手机验证码 */
    public function mobile_verify()
    {
        $verify = new \Think\Verify();
        $verify->entry(2);
    }
    /* 用户密码找回首页 */
    public function mi($email = '', $verify = '')
    {
        //$username = strval($username);
        $email = strval($email);

        if (IS_POST) { //登录验证
            //检测验证码
        	$result=D('Usercenter')->mi($email,$verify);
        	if($result['status']==0){ 
        		$this->error($result['info']);
        	}else{ 
        		$this->success($result['info'], U('User/login'));
        	}
        } else {
            if (is_login()) {
                redirect(U('Home/Index/index'));
            }

            $this->display();
        }
    }

    /**
     * 重置密码
     */
    public function reset($uid, $verify)
    {
        $result=D('Usercenter')->reset($uid,$verify);
        if($result['status']==0){ 
        	$this->error($result['info']);
        } 
        //显示新密码页面
        $this->display();
    }

    public function doReset($password, $repassword){
      $result=D('Usercenter')->doReset($password,$repassword);
      if($result['status']==0){ 
      	$this->error($result['info']);
      }else{ 
      	  //显示成功消息
        $this->success('密码重置成功', U('Home/User/login',array('login_url'=>think_encrypt(U('Home/Index/index')))));
      }
      
    }

    /**
     * 修改密码提交
     * @author huajie <banhuajie@163.com>
     */
    public function profile()
    {
        if (!is_login()) {
            $this->error('您还没有登录', U('User/login'));
        }
        if (IS_POST) {
            //获取参数
            $uid = is_login();
            $password = I('post.old');
            $repassword = I('post.repassword');
            $data['password'] = I('post.password');
            empty($password) && $this->error('请输入原密码');
            empty($data['password']) && $this->error('请输入新密码');
            empty($repassword) && $this->error('请输入确认密码');

            if ($data['password'] !== $repassword) {
                $this->error('您输入的新密码与确认密码不一致');
            }

            $Api = new UserApi();
            $res = $Api->updateInfo($uid, $password, $data);
            if ($res['status']) {
                $this->success('修改密码成功！');
            } else {
                $this->error($res['info']);
            }
        } else {
            $this->display();
        }
    }
	public function getmobile_login_verify($mobile='',$verifyCodebythink=''){
		$check_verify_result=D('Usercenter')->check_verify_think($verifyCodebythink);
		if($check_verify_result['status']==0){ 
			exit(json_encode(array('status'=>$check_verify_result['status'],'info'=>$check_verify_result['info'])));
		}
		$result=D('Usercenter')->get_verify($mobile,0);
		exit(json_encode(array('status'=>$result['status'],'info'=>$result['info'])));
	}
	
	public function getmobile_reg_verify($mobile='',$verifyCodebythink=''){
		$check_verify_result=D('Usercenter')->check_verify_think($verifyCodebythink);
		if($check_verify_result['status']==0){ 
			exit(json_encode(array('status'=>$check_verify_result['status'],'info'=>$check_verify_result['info'])));
		}
		$result=D('Usercenter')->get_verify($mobile,2);
		exit(json_encode(array('status'=>$result['status'],'info'=>$result['info'])));
	}
	public function getmobile_mi_verify($mobile=''){
		$result=D('Usercenter')->get_verify($mobile,1);
		exit(json_encode(array('status'=>$result['status'],'info'=>$result['info'])));
	}
	public function getmobile_public_verify($mobile=''){
		$result=D('Usercenter')->get_verify($mobile,3);
		exit(json_encode(array('status'=>$result['status'],'info'=>$result['info'])));
	}


}
<?php

// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Mobile\Controller;
use Mobile\Api\UserApi;
use Common\Model\MemberpublicModel;
require_once APP_PATH . 'User/Conf/config.php';
use ThinkOauth;

/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class UserController extends MobileController
{
	private $access_token;
	
    /**
	emails
	ajax判断
	*/
	public function doemails(){
	  $email=I('email');
	  $result_data=D('Usercenter')->check_user_email($email);
	  if($result_data){
	    echo 1;
	  }else{
	    echo 0;
	  }
	
	}
    /**
	mobile
	ajax判断
	*/
	public function domobile(){
	  $mobile=I('mobile');
	  $result_data=D('Usercenter')->check_user_mobile($mobile);
	  if($result_data){
	    echo 1;
	  }else{
	    echo 0;
	  }
	
	}
	
	/*昵称*/
	public function doNicknames(){
	   $nickname=I('nickname');
	   $result_data=D('Usercenter')->check_user_nickname($nickname);
	   if($result_data){
	    echo 1;
	   }else{
	    echo 0;
	   }
	}
	
    /* 注册页面 */
    public function register($user_agreement=0,$nickname='', $password = '', $email = '', $verify = '',$mobileverify='')
    {	

    	if(IS_POST){ 
	        $result_reg=D('Usercenter')->register($user_agreement,$email,$password,$nickname,$mobileverify,$verify);
	        if($result_reg['status']==1){ 
	        	$this->success('登录成功!',U('Mobile/Config/index'),1);
	        }else{ 
	        	$this->error($result_reg['info']);
	        }
        } else { //显示注册表单
            if (is_login()) {
                redirect(U('Mobile/Index/index'));
            }
            $this->display();
        }
    }

    /* 登录页面 */
    public function login()
    {
		if (is_login()) {
			redirect(U('Mobile/User/index'));
		}
		$this->assign('login_url',think_decrypt($_GET['login_url']));
		$this->setTitle('用户登录');
		$this->display();
    }

       //qq登录

    //
   
    public function qqlogin($type = null) {
        //echo $type;
        empty($type) && $this->error('参数错误');
        import("Org.ThinkSDK.ThinkOauth");//导入SDK基类 

        $sdk=\ThinkOauth::getInstance($type);//获取SDK实例 

        redirect($sdk->getRequestCodeURL());//跳转到授权页面

    }


    /* 登录页面 */
    public function ajax_login($username = '', $password = '', $login_type = 1,$verify = '', $mobileverify = '',$remember = true,$verify_num = 0,$referer_url = ''){

		if (IS_POST) { 
			if($login_type == 0){
				/*快速登录+自动注册*/
				$result_verify=D('Usercenter')->check_verify($mobileverify,0);
				if($result_verify['status']!=1){
					$this->error($result_verify['info']);
				}
				$result_login=D('Usercenter')->logintoreg($username);
			}else{	
				$result_login=D('Usercenter')->login($username, $password, $remember = true);
			}
			if($result_login['status']==1){
				D('Shop')->shop_cart_session();
			
				if(!empty($referer_url)){
					$this->success('登录成功!',$referer_url,1);	
				}else{
					$this->success('登录成功!',$login_url,1);
				}
			}else{
				  $this->error($result_login['info']);
			}
		}
    }


    public function redirect_uri(){
	   $siteid=SITEID;
       $website_url=$_SERVER['HTTP_HOST'];
       //$url = "https://open.weixin.qq.com/connect/qrconnect?appid=wx4fa439e705c5456b&redirect_uri=".urlencode('http://passport.huodongli.cn/Passports/index/index/siteid/'.$siteid.'/website_url/'.$website_url)."&response_type=code&scope=snsapi_login&state=STATE#wechat_redirect";
       $url = "//open.weixin.qq.com/connect/oauth2/authorize?appid=wx4fa439e705c5456b&redirect_uri=".urlencode('http://passport.huodongli.cn/Passports/index/index/siteid/'.$siteid.'/website_url/'.$website_url)."&response_type=code&scope=snsapi_login&state=1#wechat_redirect";

       header('location:'.$url);
    }
   
    /* 退出登录 */
    public function logout()
    {

		$url= U('Mobile/Index/index');
        if (is_login()) {
            D('Memberpublic')->logout();
           exit(json_encode(array('status'=>1,'url'=>$url)));
        } else {
           exit(json_encode(array('status'=>0,'url'=>'')));
        }
    }

    /* 验证码，用于登录和注册 */
    public function verify()
    {
        $verify = new \Think\Verify();
        $verify->entry(1);
    }

    /* 用户密码找回首页 */
    public function mi($mobile = '', $mobileverify = '',$verify ='',$type=0,$email='')
    {	
    	if(IS_POST){
       		$result=D('Usercenter')->mi($mobile,$mobileverify,1);
       		if($result['status']!=1){ 
       			$this->error($result['info']);
       		}else{ 
       			$this->success($result['info'],U('Mobile/User/reset',array('uid'=>$result['uid'],'verify'=>$mobileverify,'type'=>$type)));
       		}
            
        }else {
            if (is_login()) {
                redirect(U('Mobile/Index/index'));
            }
            $this->display();
    	}

    }

    /**
     * 重置密码
     */
    public function reset($uid=0,$type=0,$verify=''){
      	$result=D('Usercenter')->reset($uid,$verify,$type);
      	if($result['status']==0){ 
      		$this->error($result['info']);
      	}
      	$this->assign('type',$type);
      	$this->assign('mobileverify',$verify);
        $this->display();
    }
    //执行密码重置
    public function doReset($password, $repassword){
    	$param['type']=$_POST['type'];
    	$param['verify']=$_POST['mobileverify'];
    	$result=D('Usercenter')->doReset($password,$repassword,$param);
    	if($result['status']==0){ 
    		$this->error($result['info']);
    	}else{ 
    		 //显示成功消息
       		 $this->success('密码重置成功', U('Mobile/User/login'));
    	}
    }

   

    /**
     * 修改密码提交
     * @author huajie <banhuajie@163.com>
     */
    public function profile()
    {
        if (!is_login()) {
			redirect(U('Mobile/User/login'));
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


}
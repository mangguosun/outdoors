<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Official\Controller;

use User\Api\UserApi;

require_once APP_PATH . 'User/Conf/config.php';

/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class UserController extends OfficialController
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
			echo json_encode(array(
				'valid' => false,
			));
		  }else{
			echo json_encode(array(
				'valid' => true,
			));
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
	        	$this->success('注册成功',U('Official/Index/index'),1);
	        }else{ 
	        	$this->error($result_reg['info']);
	        } 
        } else { //显示注册表单
            if (is_login()) {
                redirect(U('Official/Index/index'));
            }
            $this->display();
        }
    }


    /* 注册页面step2 */
    public function step2($type = 'upload')
    {
        $type = op_t($type); //显示上传头像页面
        $this->assign('type', $type);
        $this->display('register');
    }

    public function doCropAvatar($crop)
    {
        //调用上传头像接口改变用户的头像
        $result = callApi('User/applyAvatar', array($crop));
        $this->ensureApiSuccess($result);

        //显示成功消息
        $this->success($result['message'], U('Official/User/step3'));
    }

    /* 注册页面step3 */
    public function step3($type = 'finish')
    {
        $type = op_t($type);
        $this->assign('type', $type);
        $this->display('register');
    }

    /* 登录页面 */
    public function login($email = '', $password = '', $verify = '', $remember = '',$login_type=0)
    {
	    
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
                $referer_url=$_POST['referer_url'];
                if($referer_url){ 
                    $this->success('登录成功!',$referer_url,1);
                }else{ 
                    $this->success('登录成功！',U('Official/Index/index'));
                }
               

            }else{
                  $this->error($result_login['info']);
            }
           
        } else { //显示登录表单
            if (is_login()) {
                redirect(U('Official/Index/index'));
            }
            $this->display();
        }
    }

    /* 退出登录 */
    public function logout()
    {
        if (is_login()) {
            D('Member')->logout();
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

    /* 用户密码找回首页 */
    public function mi($email = '', $verify = '')
    {
        //$username = strval($username);
        $email = strval($email);

        if (IS_POST) { //登录验证
            //检测验证码
            if (C('VERIFY_OPEN')) {
                if (!check_verify($verify)) {
                    $this->error('验证码输入错误');
                }
            }
           //根据用户名获取用户UID
            $user = D('User/UcenterMember')->where(array('email' => $email, 'status' => 1, 'siteid' => SITEID))->find();
            $uid = $user['id'];
            if (!$uid) {
                $this->error("用户名或邮箱错误");
            }

            //生成找回密码的验证码
            $verify = $this->getResetPasswordVerifyCode($uid);

            //发送验证邮箱
            $url = 'http://' . $_SERVER['HTTP_HOST'] . U('Official/User/reset?uid=' . $uid . '&verify=' . $verify);
            $content = C('USER_RESPASS') . "<br/>" . $url . "<br/>" . get_webinfo('webname') . "系统自动发送--请勿直接回复<br/>" . date('Y-m-d H:i:s', TIME()) . "</p>";
            send_mail($email, get_webinfo('webname') . "密码找回", $content);
            $this->success('密码找回邮件发送成功', U('Official/User/login'));
        } else {
            if (is_login()) {
                redirect(U('Official/Index/index'));
            }

            $this->display();
        }
    }

    /**
     * 重置密码
     */
    public function reset($uid, $verify)
    {
        //检查参数
        $uid = intval($uid);
        $verify = strval($verify);
        if (!$uid || !$verify) {
            $this->error("参数错误");
        }

        //确认邮箱验证码正确
        $expectVerify = $this->getResetPasswordVerifyCode($uid);
        if ($expectVerify != $verify) {
            $this->error("参数错误");
        }

        //将邮箱验证码储存在SESSION
        session('reset_password_uid', $uid);
        session('reset_password_verify', $verify);

        //显示新密码页面
        $this->display();
    }

    public function doReset($password, $repassword)
    {
        //确认两次输入的密码正确
        if ($password != $repassword) {
            $this->error('两次输入的密码不一致');
        }

        //读取SESSION中的验证信息
        $uid = session('reset_password_uid');
        $verify = session('reset_password_verify');

        //确认验证信息正确
        $expectVerify = $this->getResetPasswordVerifyCode($uid);
        if ($expectVerify != $verify) {
            $this->error("验证信息无效");
        }

        //将新的密码写入数据库
        $data = array('id' => $uid, 'password' => $password);
        $model = D('User/UcenterMember');
        $data = $model->create($data);
        if (!$data) {
            $this->error('密码格式不正确');
        }
        $result = $model->where(array('id' => $uid,'siteid' => SITEID))->save($data);
        if ($result===false) {
            $this->error('数据库写入错误');
        }

        //显示成功消息
        $this->success('密码重置成功', U('Official/User/login'));
    }

    private function getResetPasswordVerifyCode($uid)
    {
        $user = D('User/UcenterMember')->where(array('id' => $uid))->find();
        $clear = implode('|', array($user['uid'], $user['email'], $user['last_login_time'], $user['password']));
        $verify = thinkox_hash($clear, UC_AUTH_KEY);
        return $verify;
    }

    /**
     * 获取用户注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    private function showRegError($code = 0)
    {
        switch ($code) {
            case -1:
                $error = '用户名长度必须在16个字符以内！';
                break;
            case -2:
                $error = '用户名被禁止注册！';
                break;
            case -3:
                $error = '用户名被占用！';
                break;
            case -4:
                $error = '密码须字母开头 长度在6-30个数字加字母！';
                break;
            case -5:
                $error = '邮箱格式不正确！';
                break;
            case -6:
                $error = '邮箱长度必须在1-32个字符之间！';
                break;
            case -7:
                $error = '邮箱被禁止注册！';
                break;
            case -8:
                $error = '邮箱被占用！';
                break;
            case -9:
                $error = '手机格式不正确！';
                break;
            case -10:
                $error = '手机被禁止注册！';
                break;
            case -11:
                $error = '手机号被占用！';
                break;
            case -20:
                $error = '用户名只能由数字、字母和"_"组成！';
                break;
            case -30:
                $error = '昵称被占用！';
                break;
            case -31:
                $error = '昵称被禁止注册！';
                break;
            case -32:
                $error = '昵称只能由数字、字母、汉字和"_"组成！';
                break;
			case -33:
                $error = '昵称不能少于两个字！';
                break;
            default:
                $error = '未知错误24';
        }
        return $error;
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

    public function getmobile_reg_verify($mobile='',$verifyCodebythink=''){
		$check_verify_result=D('Usercenter')->check_verify_think($verifyCodebythink);
		if($check_verify_result['status']==0){ 
			exit(json_encode(array('status'=>$check_verify_result['status'],'info'=>$check_verify_result['info'])));
		}
		$result=D('Usercenter')->get_verify($mobile,2);
		exit(json_encode(array('status'=>$result['status'],'info'=>$result['info'])));
		
		//exit(json_encode(array('status'=>1,'info'=>'发送成功呛吼叫啊呆了')));
	}

}
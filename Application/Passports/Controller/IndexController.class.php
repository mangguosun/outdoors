<?php

namespace Passports\Controller;

use Think\Controller;
use ThinkOauth;


class IndexController extends Controller{
	private $access_token;
	 	
	public function index(){ 
		$website_url=$_GET['website_url'];
		if(isset($_GET['code'])){
            $code = $_GET['code'];
            $siteid=$_GET['siteid'];
            //通过code换取网页授权access_token
            $access_token = $this->access_token = D('Oauth')->get_code_access_token($code);
            $refresh_token =  $access_token['refresh_token'];
             //判断openID是否在数据库
            $openid = $access_token['openid'];
            $map['openid'] = $openid;
            $map['siteid'] = $siteid;
            $map['login_type']   = 1;
            $result = D('member_joint_login') ->where($map)->find();     
            if($result){ 
                redirect(U('Mobile/Index/index@'.$website_url,array('uid'=>$result['uid'],'login_type'=>2)));
            }else{
                $this->assign('openid',$openid);
                $this->assign('siteid',$siteid);
                $this->assign('access_token',$access_token);
                $this->assign('website_url',$website_url);
                $this->display('Index/weixin_bind');
            }
        }

	}
	public function weixin_bind(){ 
        if(IS_POST){
            $type        = $_POST['type'];       
            $siteid      = $_POST['siteid'];
            $access['acceess_token']= $_POST['access_token'];    
            $access['expires_in']= $_POST['expires_in'];    
            $access['refresh_token']= $_POST['refresh_token'];    
            $access['openid']= $_POST['openid'];    
            $access['scope']= $_POST['scope'];    
            $access['unionid']= $_POST['unionid']; 
            $website_url = $_POST['website_url'];
            if($type=='' ||$siteid==''||$website_url==''||$access['openid']==''){ 
                $this->error('参数错误,请重试',U('Mobile/Index/index@'.$website_url));
            }  
            if($type==1){ 
                $username=$_POST['username'];
                $password=$_POST['password'];
                if(get_every_check('email',$username)){
                    $uid = D('UcMemberpublic')->login($username, $password,2,$siteid);
                }elseif(get_every_check('mobile',$username)){
                    $uid = D('UcMemberpublic')->login($username, $password,3,$siteid);
                }else{
                    $this->error('请填写正确的信息');
                }

                if($uid>0){
                    $have_arr=array( 
                        'uid'=>$uid,
                        'login_type'=>1,
                        'siteid'=>$siteid,
                        );
                    $haveid=D('member_joint_login')->where($have_arr)->find();
                    if($haveid){ 
                        $this->error('该用户已绑定微信账号');
                    }
                    $a_data['openid']=$access['openid'];
                    $a_data['uid']   =$uid;
                    $a_data['login_type']  =1;
                    $a_data['siteid'] =$siteid;

                    $weixin_a_result=D('member_joint_login')->add($a_data);
                    if($weixin_a_result){ 
                        $this->success('绑定成功，请稍后！',U('Mobile/Index/index@'.$website_url,array('uid'=>$uid,'login_type'=>2)));
                    }else{ 
                        $this->error('绑定失败，请重试！',U('Mobile/Index/index@'.$website_url));
                    }

                }else{ 
                    switch ($uid) {
                        case -1:
                           $this->error('用户不存在或被禁用！');
                            break; //系统级别禁用
                        case -2:
                           $this->error('密码错误！');
                            break;
                    }
                }
            }elseif($type==2){ 
                $data =  D('Oauth')->checkAvail($access,$access['openid']);
                if($data['errcode'] != '0' || $data['error_msg'] != 'ok'){
                    //刷新access_token
                    $access_token = D('Oauth')->refresh_access_token($access['refresh_token']);
                    
                    //得到拉取用户信息
                    $userinfo = D('Oauth')->get_user_info($access_token['access_token'],$access_token['openid']);
                }else{
                    //获取用户信息
                    $openid=$access['openid'];
                    $userinfo = D('Oauth')->get_user_info($access['access_token'],$openid);
                }
                
                if(!$userinfo){ 
                     $this->error('登录失败，请重试！',U('Mobile/Index/index@'.$website_url));
                }
                $ip_arr=ip2long(get_client_ip());
                $time_arr=time();
                $uc_member_arr = array(
                    'status'            =>  1,
                    'siteid'            =>  $siteid,
                    'reg_ip'            =>  $ip_arr,
                    'reg_time'          =>  $time_arr,
                    'last_login_ip'     =>  $ip_arr,
                    'last_login_time'   =>  $time_arr,
                    'update_time'       =>  $time_arr,
                    );
                $uid = D('ucenter_member')->add($uc_member_arr);
                if($uid){ 

                    $nickname=msubstr($userinfo['nickname'],0,10,'utf-8',false);
                    $str = mt_rand(10000,99999);
                    $member_arr=array( 
                        'nickname'          =>  $nickname.'_'.$str,
                        'siteid'            =>  $siteid,
                        'uid'               =>  $uid,
                        'login'             =>  0,
                        'reg_ip'            =>  $ip_arr,
                        'reg_time'          =>  $time_arr,
                        'last_login_ip'     =>  $ip_arr,
                        'last_login_time'   =>  $time_arr,
                        'status'            =>  1,
                        );
                    $mid = D('member')->add($member_arr);
                    $joint_login_arr=array(
                        'siteid'     =>$siteid,
                        'uid'        =>$uid,
                        'status'     =>1,
                        'openid'     =>$userinfo['openid'],
                        'login_type' =>1,
                        );
                    $jid=D('member_joint_login')->add($joint_login_arr);
                    if($mid && $jid){ 
                        $this->success('登录成功',U('Mobile/Index/index@'.$website_url,array('uid'=>$uid,'login_type'=>2)));
                    }else{ 
                        $this->error('登录失败，请重试！',U('Mobile/Index/index@'.$website_url));

                    }
                }else{ 
                    $this->error('登录失败，请重试！',U('Mobile/Index/index@'.$website_url));
                }
            }   
        }
    }
    public function callback($type = null, $code = null) {
        header("Content-type: text/html; charset=utf-8");
        (empty($type) || empty($code)) && $this->error('参数错误');
        import('Org.ThinkSDK.ThinkOauth');
        $type=$_GET['type'];
        $siteid=$_GET['siteid'];
        $website_url=$_GET['website_url'];
        $sdk = \ThinkOauth::getInstance($type);
        $extend = null;
        //$tokenArr我在bind中可以传 隐藏传
        $tokenArr = $sdk->getAccessToken($code, $extend);
        $openid = $tokenArr['openid'];
        $token = $tokenArr['access_token'];

        if ($openid) {
            $map['openid'] = $openid;
            $map['siteid'] = $siteid;
            $map['login_type']   = 2;
            $result = D('member_joint_login') ->where($map)->find();
            if ($result) { //若是有该账号就登录
                redirect(U('Mobile/Index/index@'.$website_url,array('uid'=>$result['uid'],'login_type'=>2)));
            } else {
                //没查到就去绑定啦啦啦  $tokenArr 125行  就是获取到的URL中的东西
                // $openid = $tokenArr['openid'];$token = $tokenArr['access_token'];
                $data = $sdk->call('user/get_user_info');
                $userinfo['nickname']=$data['nickname'];
                $this->assign('userinfo',$userinfo);
                $this->assign('siteid',$siteid);
                $this->assign('tokenArr',$tokenArr);
                $this->assign('website_url',$website_url);
                $this->display('Index/qq_bind');
            }
        }
    }
    public function qq_bind(){
        if(IS_POST){
            $type        = $_POST['type'];       
            $openid      = $_POST['openid'];
            $token       = $_POST['token'];
            $siteid      = $_POST['siteid'];
            $website_url = $_POST['website_url'];
            $user_nickname    = $_POST['user_nickname'];
            if($type=='' ||$siteid==''||$website_url==''){ 
                $this->error('参数错误,请重试',U('Mobile/Index/index@'.$website_url));
            }

            if($type==1){ 
                $username=$_POST['username'];
                $password=$_POST['password'];
                if(get_every_check('email',$username)){
                    $uid = D('UcMemberpublic')->login($username, $password,2,$siteid);
                }elseif(get_every_check('mobile',$username)){
                    $uid = D('UcMemberpublic')->login($username, $password,3,$siteid);
                }else{
                    $this->error('请填写正确的信息');
                }
                if($uid>0){
                    $have_arr=array( 
                        'uid'=>$uid,
                        'login_type'=>2,
                        'siteid'=>$siteid,
                        );
                    $haveid=D('member_joint_login')->where($have_arr)->find();
                    if($haveid){ 
                        $this->error('该用户已绑定微信账号');
                    }
                    $a_data['openid']=$openid;
                    $a_data['uid']   =$uid;
                    $a_data['login_type']  =2;
                    $a_data['siteid'] =$siteid;

                    $qq_a_result=D('member_joint_login')->add($a_data);
                    if($qq_a_result){ 
                        $this->success('绑定成功，请稍后！',U('Mobile/Index/index@'.$website_url,array('uid'=>$uid,'login_type'=>2)));
                    }else{ 
                        $this->error('绑定失败，请重试！',U('Mobile/Index/index@'.$website_url));
                    }

                }else{ 
                    switch ($uid) {
                        case -1:
                           $this->error('用户不存在或被禁用！');
                            break; //系统级别禁用
                        case -2:
                           $this->error('密码错误！');
                            break;
                    }
                }


            }elseif($type==2){ 
                if(!$user_nickname){ 
                     $this->error('登录失败，请重试！',U('Mobile/Index/index@'.$website_url));
                }
                $ip_arr=ip2long(get_client_ip());
                $time_arr=time();
                $uc_member_arr = array(
                    'status'            =>  1,
                    'siteid'            =>  $siteid,
                    'reg_ip'            =>  $ip_arr,
                    'reg_time'          =>  $time_arr,
                    'last_login_ip'     =>  $ip_arr,
                    'last_login_time'   =>  $time_arr,
                    'update_time'       =>  $time_arr,
                    );
                $uid = D('ucenter_member')->add($uc_member_arr);
                if($uid){ 

                    $nickname=msubstr($user_nickname,0,10,'utf-8',false);
                    $str = mt_rand(10000,99999);
                    $member_arr=array( 
                        'nickname'          =>  $nickname.'_'.$str,
                        'siteid'            =>  $siteid,
                        'uid'               =>  $uid,
                        'login'             =>  0,
                        'reg_ip'            =>  $ip_arr,
                        'reg_time'          =>  $time_arr,
                        'last_login_ip'     =>  $ip_arr,
                        'last_login_time'   =>  $time_arr,
                        'status'            =>  1,
                        );
                    $mid = D('member')->add($member_arr);
                    $joint_login_arr=array(
                        'siteid'     =>$siteid,
                        'uid'        =>$uid,
                        'status'     =>1,
                        'openid'     =>$openid,
                        'login_type' =>2,
                        );
                    $jid=D('member_joint_login')->add($joint_login_arr);
                    if($mid && $jid){ 
                        $this->success('登录成功',U('Mobile/Index/index@'.$website_url,array('uid'=>$uid,'login_type'=>2)));
                    }else{ 
                        $this->error('登录失败，请重试！',U('Mobile/Index/index@'.$website_url));

                    }
                }else{ 
                    $this->error('登录失败，请重试！',U('Mobile/Index/index@'.$website_url));
                }

            }
                                
        }
    }





    
}
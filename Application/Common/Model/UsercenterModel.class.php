<?php
/**
 * 所属项目 110.
 * 开发者: 陈一枭
 * 创建日期: 2014-11-18
 * 创建时间: 10:27
 * 版权所有 想天软件工作室(www.ourstu.com)
 */

namespace Common\Model;

use Think\Model;
use Common\Model\UcMemberpublicModel;
use Common\Model\MemberpublicModel;
class UsercenterModel extends Model{
	protected $UcMemberpublic;
	protected $Memberpublic;
	public function __construct(){ 
		$this->UcMemberpublic=D('UcMemberpublic');
		$this->Memberpublic=D('Memberpublic');
	}
	/*验证码生成*/
	//$type  0 登录 1找回密码  2注册、3 手机验证码

	public function get_verify($mobile='',$type=0,$from_source='pc'){
		if(get_every_check('mobile',$mobile)){
			$is_mobile=M('ucenter_member')->where("mobile='{$mobile}' and siteid = ".SITEID)->find();
			if($is_mobile){
				if($is_mobile['status'] != 1){ 
					$res['status']=0;
					if($type==0){ 
						$res['info']= '此用户已禁用或未通过审核！';
					}else{ 
						$res['info']= '此手机号已使用,请更换其它手机号';
					}
					return $res;
				}
			}
			$webname   = "[".get_webinfo('webname')."]";
			$msg       =  mt_rand(100000,999999);
			if($type==1){ 
				$msgss     = "【活动力】您的找回密码验证码为 ：".$msg.$webname;
				$msglog    = '找回密码验证';
				$_SESSION['mobile_mi'] = $msg;
				$_SESSION['mobile_mi_time'] = time();
			}elseif($type==2){ 
				$msgss     ="【活动力】您的注册验证码为 ：".$msg.$webname;
				$msglog    = '注册验证';
				$_SESSION['mobile_reg'] = $msg;
				$_SESSION['mobile_reg_time'] = time();
			}elseif($type==3){ 
				$msgss     ="【活动力】您的手机验证码为 ：".$msg.$webname;
				$msglog    = '手机验证';
				$_SESSION['mobile_public'] = $msg;
				$_SESSION['mobile_public_time'] = time();
			}else{ 
				$msgss     = "【活动力】您的登录验证码为 ：".$msg.$webname;
				$msglog    = '登录验证';
				$_SESSION['mobile_login'] = $msg;
				$_SESSION['mobile_login_time'] = time();
			}
			$ip_result=sendMessageIp();
			if($ip_result['status']==0){ 
				return $ip_result;
			}else{ 
				$this->sms_alerts_phone($mobile,$msgss,$msglog,$from_source);
				$res['status']=1;
				$res['info']='发送成功';
				return $res;
			}
			
		}else{
			$res['status']=0;
			$res['info']='发送失败,手机格式不正确！';
			return $res; 
		}
	}

	private function sendTemplateSMS($tel,$msg,$type,$from_source){
		$smsapi = "http://www.smsbao.com/"; //短信网关
		$user = "hdldev"; //短信平台帐号
		$pass = md5("dongsms001"); //短信平台密码
		$content=$msg;//要发送的短信内容
		$phone = $tel;
		$sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$phone."&c=".urlencode($content);
		$result =file_get_contents($sendurl) ;
		$data['uid'] =is_login();
		$data['from_source']=$from_source;
		$data['siteid']=SITEID;
		$data['create_time'] = time();
		$data['item_type'] = $type;
		$data['back_info'] = sendbackinfo($result) != '' ?sendbackinfo($result):'未知错误';
		$data['msg'] = $msg;
		$data['telephone'] = $tel;
		$data['sendmessage_ip'] = ip2long(get_client_ip());
		D('sms_log')->add($data);
		return $result;
	}
	private function sms_alerts_phone($tel,$msg,$type,$from_source,$webname='',$siteid=''){
		$webinfo = json_decode(WEBSITEINFO,true);
		$webname = $webname?$webname:$webinfo['webname'];
		$siteid =$siteid?$siteid:SITEID;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://sms-api.luosimao.com/v1/send.json");
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPAUTH , CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD  , "api:key-".C('SMSKEY'));
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('mobile' => $tel,'message' => $msg.'【活动力】'));
		$res = curl_exec( $ch );
		curl_close( $ch );
		$result_arr = json_decode($res,true);
		$data['uid'] = is_login();
		$data['siteid'] = $siteid;
		$data['from_source']=$from_source;
		$data['create_time'] = time();
		$data['item_type'] = $type;
		$data['back_info'] = sms_back_info($result_arr['error']) != '' ? sms_back_info($result_arr['error']):'未知错误';
		$data['msg'] = $msg;
		$data['telephone'] = $tel;
		$data['sendmessage_ip'] = ip2long(get_client_ip());	
		D('sms_log')->add($data);
		return $result_arr;
	}
	
	
	/*验证验证码*/
	//$type  0 登录 1找回密码  2注册、3 手机验证码
	public function check_verify($verify,$type){ 
		$res['status']=1;
		$res['info']='验证通过';
		if(!$verify){
			$res['status']=0;
			$res['info']='请输入手机验证码!';
		}else{
			switch ($type) {
				case 0:
					if((time() -$_SESSION['mobile_login_time']) > 600){
						unset($_SESSION['mobile_login']);
						$res['status']=0;
						$res['info']='手机验证码过期，请重新获取!';
					}else{ 
						if($verify != $_SESSION['mobile_login']){
							$res['status']=0;
							$res['info']='手机验证码输入错误!';
						}
					}
					
					break;
				case 1:
					if((time() -$_SESSION['mobile_mi_time']) > 600){
						unset($_SESSION['mobile_mi']);
						$res['status']=0;
						$res['info']='手机验证码过期，请重新获取!';
					}else{ 
						if($verify != $_SESSION['mobile_mi']){
							$res['status']=0;
							$res['info']='手机验证码输入错误!';
						}
					}
					break;
				case 2:
					if((time() -$_SESSION['mobile_reg_time']) > 600){
						unset($_SESSION['mobile_reg']);
						$res['status']=0;
						$res['info']='手机验证码过期，请重新获取!';
					}else{ 
						if($verify != $_SESSION['mobile_reg']){
							$res['status']=0;
							$res['info']='手机验证码输入错误!';
						}
					}
					break;
				case 3:
					if((time() -$_SESSION['mobile_public_time']) > 600){
						unset($_SESSION['mobile_public']);
						$res['status']=0;
						$res['info']='手机验证码过期，请重新获取!';
					}else{ 
						if($verify != $_SESSION['mobile_public']){
							$res['status']=0;
							$res['info']='手机验证码输入错误!';
						}
					}
					break;
				
				default:
					$res['status']=0;
					$res['info']='参数错误!';
					break;
			}
		}
		return $res;
	}
	/*删除验证SESSION*/
	//$type  0 登录 1找回密码  2注册、3 手机验证码
	public function unset_verify($type=0){ 
		switch ($type) {
			case 0:
				unset($_SESSION['mobile_login']);
				break;
			case 1:
				unset($_SESSION['mobile_mi']);
				break;
			case 2:
				unset($_SESSION['mobile_reg']);
				break;
			case 3:
				unset($_SESSION['mobile_public']);
				break;
			default:
				unset($_SESSION['mobile_login']);
				unset($_SESSION['mobile_mi']);
				unset($_SESSION['mobile_reg']);
				unset($_SESSION['mobile_public']);
				break;
		}
	}
	/*普通登录（手机/邮箱）*/
    public function login($username = '', $password = '', $remember = true,$verify_num=1,$verify=''){ 
		$res['status']=0;
		 /* 检测验证码*/ 
		if (C('VERIFY_OPEN') == 1 or C('VERIFY_OPEN') == 3){
			if($verify_num >= 3){
				if (!check_verify($verify)) {
					$res['info']='请填写正确的验证码';
					return $res;
				} 
			}
		}
	   /* 调用UC登录接口登录 */
		if(get_every_check('email',$username)){
			$uid = $this->UcMemberpublic->login($username, $password,2);
		}elseif(get_every_check('mobile',$username)){
			$uid = $this->UcMemberpublic->login($username, $password,3);
		}else{
			$res['status']=0;
			$res['info']='请填写正确的手机号或邮箱!';
			return $res;
		}

		if (0 < $uid) {
            /* 登录用户 */
           
		   if ($this->Memberpublic->login($uid, $remember =="on")) { //登录用户
		   		$res['status']=1;
		        $res['info']='登陆成功！';
		        $res['uid']=$uid;
				
            } else {
                $res['info']=$this->Memberpublic->getError();
            }
        } else { //登录失败
        
            switch ($uid) {
                case -1:
                    $res['info'] = '用户不存在或被禁用！';
                    break; //系统级别禁用
                case -2:
                    $res['info'] = '密码错误！';
                    break;
                default:
                   $res['info'] = $uid;
                    break; // 0-接口参数错误（调试阶段使用）
            }
          
        }
         return $res; 
    }
    /*普通注册*/
    public function register($user_agreement=0,$username='',$password='',$nickname='',$mobileverify='',$verify = ''){ 
    	$res['status']=0;
    	if (!C('USER_ALLOW_REGISTER')) {
    		$res['info']='注册已关闭';
            return $res;
        }
        if($user_agreement!=1){
        	$res['info']='请点击同意服务协议';
            return $res;
		}
		if(get_every_check('email',$username)){ 
			if($verify != ''){
				if (C('VERIFY_OPEN') == 1 or C('VERIFY_OPEN') == 2) {
					if (!check_verify($verify)) {
						$res['info']='验证码输入错误。';
						 return $res;
					}
				}
			}
			$uid =$this->UcMemberpublic->register('',$nickname, $password, $username,'');
			if(0 < $uid){ //注册成功
				$uid = $this->UcMemberpublic->login($username, $password,2);
				$this->Memberpublic->login($uid, false);
				$res['status']=1;
				$res['info']='注册成功';
			}else{ 
				$res['info']=$this->showRegError($uid);
			}

		}elseif(get_every_check('mobile',$username)){ 
			$result_verify=$this->check_verify($mobileverify,2);
			if($result_verify['status']!=1){ 
				$res['info']=$result_verify['info'];
				 return $res;
			}
			$uid =$this->UcMemberpublic->register('',$nickname, $password,'', $username);
			if(0 < $uid){ //注册成功
				$uid = $this->UcMemberpublic->login($username, $password,3);
				$this->Memberpublic->login($uid, false);
				$res['status']=1;
				$res['info']='注册成功';
			}else{ 
				$res['info']=$this->showRegError($uid);
			}
		}
		return $res;
    }
	/*快速登录（手机）*/
	public function fastLogin($mobile){
    	$map = array();
    	$map['mobile'] = $mobile;
    	$map['siteid'] = SITEID;
    	/*获取用户数据*/
    	$user = D('ucenter_member')->where($map)->find();
    	if(is_array($user) && $user['status']){
    		D('UcMemberpublic')->updateLogin($user['id']);
    		D('Memberpublic')->login($user['id'], false);//登录
			$res['status']=1;
			$res['info'] =$user['id'];
			$res['uid']=$user['id'];
    	}else{
    		$res['status']=0;
    		$res['info'] ='用户不存在或被禁用!';
    	} 
		return $res;
	}
	/*快速注册（手机）*/
	public function fastRegister($mobile){ 
		$password = mt_rand(100000,999999);
		if(get_every_check('mobile',$mobile)){ 
			$nickname = $mobile;
			$pattern = "/(1\d{1,2})\d\d(\d{0,3})/";
			$replacestr=$this->mobile_replace_str(4);
			$replacement = "\$1".$replacestr."\$3";
			$nickname2=preg_replace($pattern, $replacement, $nickname);
			$uid=D('UcMemberpublic')->register('',$nickname2,$password,'',$mobile);
			if($uid>0){ 
				$webinfo = json_decode(WEBSITEINFO,true);
				$webname = $webinfo['webname'];
				$userdata= array(
					'password'		=>	$password,
					'webname'		=>	$webname,
					'noticetype'    =>  'quicklog',
					'nickname'      =>	$nickname,
				);
				$contactway=array($mobile);
				D('Message')->addSendMessage('send_sms_to_user',$contactway,$userdata,0,1);
				if (0 < $uid) { //注册成功
				    $uid =D('UcMemberpublic')->login($mobile, $password,3);//通过账号密码取到uid
					D('Memberpublic')->login($uid, false);//登录
					$res['status']=1;
					$res['info'] =$uid;	
					$res['uid']=$uid;				
				} else { //注册失败，显示错误信息
					$res['status']=0;
					$res['info']=$this->showRegError($uid);
				}
			}else{ 
				$res['status']=0;
				$res['info']=$this->showRegError($uid);
			}
			return $res;
		}else{ 
			$res['status']=0;
			$res['info'] ='请输入正确的手机号码';
			return $res;
		}
	}
	//快速注册的随机数
	public function mobile_replace_str($length){ 
		$str=substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"),0,$length);
		return $str;
	}
	public function check_verify_think($verifyCodebythink='',$id=1){ 
		$res['status']=1;
		if (!check_verify($verifyCodebythink,$id)) {
			$res['status']=0;
			$res['info']='请输入正确的图片验证码！';
			 return $res;
		}	
		return $res;
	}
	/*快速登录（账号不存在自动注册）*/
	public function logintoreg($mobile){
		$result_check=$this->check_user_mobile($mobile);
		if($result_check){
			$result=$this->fastLogin($mobile);
		}else{ 
			$result=$this->fastRegister($mobile);
		}
		return $result;
	}
	/*忘记密码 $type 1 手机号码 0发送邮箱*/
	public function mi($email='',$verify='',$type=0){
	 	$email=trim($email);
	 	$res['status']=0;
	 	if($type==1){ 
			$result_verify=$this->check_verify($verify,1);
			if($result_verify['status']!=1){
				$res['info']=$result_verify['info'];
				return $res;
			}
			//手机号码正确性
			if(get_every_check('mobile',$email)){ 
				//是否有此手机用户
				$user = $this->UcMemberpublic->where(array('mobile' => $email, 'status' => 1, 'siteid' => SITEID))->find();
				$uid = $user['id'];
	            if (!$uid) {
	            	$res['info']="手机号码错误！";
	            }else{
	            	$res['uid']=$uid;
	            	$res['status']=1;
	            	$res['info']= '验证成功，请重置密码！';
	            }
			}else{
			 	$res['info']="请填写正确的手机号!";	
			}
			return $res;
    	}else{ 
    		if (!check_verify($verify)) {
    			$res['info']="请输入正确验证码!";
                return $res;
            }	
  			
           //根据用户名获取用户UID
            $user = $this->UcMemberpublic->where(array('email' => $email, 'status' => 1, 'siteid' => SITEID))->find();
            $uid = $user['id'];
            if (!$uid) {
            	$res['info']="用户名或邮箱错误!";
            	return $res;
            }

            //生成找回密码的验证码
            $verify = $this->getResetPasswordVerifyCode($uid);

            //发送验证邮箱
            $url = 'http://' . $_SERVER['HTTP_HOST'] . U('Home/User/reset?uid=' . $uid . '&verify=' . $verify);
            $content = C('USER_RESPASS') . "<br/>" . $url . "<br/>" . get_webinfo('webname') . "系统自动发送--请勿直接回复<br/>" . date('Y-m-d H:i:s', TIME()) . "</p>";
            send_mail($email, get_webinfo('webname') . "密码找回", $content);
            $res['status']=1;
            $res['info']='密码找回邮件发送成功';
            
        }
        return $res;
	}
	/*密码重置 $type 0邮箱 1 手机*/
	public function reset($uid,$verify,$type=0){
 		//检查参数
        $uid = intval($uid);
        $verify = strval($verify);
        $res['status']=0;
        if (!$uid || !$verify) {
        	$res['info']="参数错误";
        	return $res;
        }
		if($type!=1){ 
	        //确认邮箱验证码正确
	        $expectVerify = $this->getResetPasswordVerifyCode($uid);
	        if ($expectVerify != $verify) {
	            $res['info']="参数错误11";
	        	return $res;
	        }
		}
		//将邮箱验证码储存在SESSION
        session('reset_password_uid', $uid);
        session('reset_password_verify', $verify);
        $res['status']=1;
        return $res;
		
	}
	public function doReset($password, $repassword,$param=''){ 
		$res['status']=0;
		if ($password != $repassword) {
			$res['info']="两次输入的密码不一致";
            return $res;
        }
        //读取SESSION中的验证信息
        $uid = session('reset_password_uid');
        $verify = session('reset_password_verify');
        if($param!=''){ 
        	if($param['verify']!=$verify){ 
        		$res['info']="验证信息无效";
        		return $res;
        	}

        }else{ 
        	//确认验证信息正确
	        $expectVerify = $this->getResetPasswordVerifyCode($uid);
	        if ($expectVerify != $verify) {
	        	$res['info']="验证信息无效";
	            return $res;
	        }
        }
        //将新的密码写入数据库
        $data = array('id' => $uid, 'password' => $password);
        $data = $this->UcMemberpublic->create($data);
        if (!$data) {
        	$res['info']="密码格式不正确";
            return $res;
        }
        $result = $this->UcMemberpublic->where(array('id' => $uid,'siteid' => SITEID))->save($data);
        if ($result===false) {
        	$res['info']="数据库写入错误";
            return $res;
        }
        $res['status']=1;
        return $res;
	}

	private function getResetPasswordVerifyCode($uid)
    {
        $user = $this->UcMemberpublic->where(array('id' => $uid))->find();
        $clear = implode('|', array($user['uid'], $user['email'], $user['last_login_time'], $user['password']));
        $verify = thinkox_hash($clear, UC_AUTH_KEY);
        return $verify;
    }


    /**
     * 获取用户注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
    */
    private function showRegError($code = 0){
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
                $error = '邮箱长度必须在4-32个字符之间！';
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
    /*验证手机号码是否存在*/
    public function check_user_mobile($mobile){
    	$datas=M('ucenter_member')->where("mobile='{$mobile}' and siteid = ".SITEID)->find();
		if($datas){
			return true;
		}else{
			return false;
		}
    }	
    /*验证邮箱是否存在*/
    public function check_user_email($email){
		$datas=M('ucenter_member')->where("email='{$email}' and siteid = ".SITEID)->find();
		if($datas){
			return true;
		}else{
			return false;
		}
    }
    /*验证昵称是否用过*/
    public function check_user_nickname($nickname){
		$datas=M('member')->where("nickname='{$nickname}' and siteid = ".SITEID)->find();
		if($datas){
			return true;
		}else{
			return false;
		}
    }




} 
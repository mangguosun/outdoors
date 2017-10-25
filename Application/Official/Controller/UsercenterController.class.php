<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM1:13
 */
namespace Official\Controller;

use Think\Controller;

class UsercenterController extends BaseController
{
	protected $userdata;
	protected $mTalkModel;
    public function _initialize()
    {
        parent::_initialize();
        if (!is_login()) {
            //$this->error('请登录后再访问本页面。');
			$this->redirect('Official/User/login');
        }
		$this->userdata = query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $uid);
        $this->mTalkModel = D('Talk');
        $this->setTitle('个人中心');
	}

    public function index($uid = null, $tab = '', $nickname = '',$qq = 0, $sex = 0, $email = '',$real_name = '', $signature = '', $address = '',$mobile = 0,$self_introduction = '',$constellation=0)
    {

    	if (IS_POST) {
            $nickname = op_t(trim($nickname));
            $real_name=op_t(trim($real_name));
            $signature = op_t(trim($signature));
            $sex = intval($sex);
            $email = op_t(trim($email));
            $province = intval(trim($province));
            $city = intval(trim($city));
            $community = intval(trim($community));
            $district = intval(trim($district));
            $mobile=op_t(trim($mobile));
            $self_introduction=op_t(trim($self_introduction));
            $qq=op_t(trim($qq));
            /*---验证--*/
            $this->checkNickname($nickname);
            $this->checkSex($sex);
            //$this->checkEmail($email);
            $this->checkSignature($signature);
            $this->check_qq($qq);
            $this->checkTelphone($mobile);
            $this->check_self_introduction($self_introduction);
            

			
		

			$user['address'] = $address;


            $user['real_name']=$real_name;
            $user['pos_province'] = $province;
            $user['pos_city'] = $city;
            $user['pos_district'] = $district;
            $user['pos_community'] = $community;
            $user['qq']=$qq;

            $user['nickname'] = $nickname;
            $user['sex'] = intval($sex);
            $user['signature'] = $signature;
            $user['uid'] = get_uid();
            $user['constellation']=$constellation;
            
			$rs_member=D('Home/Member')->save($user);
			
            $ucuser['id'] = get_uid();
            //$ucuser['email'] = $email;
            $ucuser['mobile']=$mobile;
            $ucuser['self_introduction']=$self_introduction;
            $ucuser['real_name']=$real_name;
            $rs_ucmember = D('UcenterMember')->save($ucuser);
            
            clean_query_user_cache(get_uid(), array('nickname', 'sex', 'signature', 'email','qq','pos_province', 'pos_city', 'pos_district', 'pos_community','mobile','address','constellation'));

            //TODO tox 清空缓存
            if ($rs_member || $rs_ucmember) {
                $this->success('设置成功。',U('Official/Usercenter/index'));

            } else {
                $this->error('未修改数据。');
            }

        } else {

		    
			$uid=is_login();
			$this->check_checked($uid);
            $list=D('member_personal')->where("uid=$uid")->find();//--扩展资料里查看个人资料--
            $this->assign('list',$list);
			
            //调用API获取基本信息
            //TODO tox 获取省市区数据
            //显示页面
			$this->assign('content_address', $content_address);
            $this->assign('user', $this->userdata);
            $tab = op_t($tab);
            $this->assign('tab', $tab);

            $this->getExpandInfo();
            $this->display();
        }

    }
   public function webmanage()
   {
		$uid=is_login();
		$web_info=D('websit')->where("gmuid=".$uid)->find();
		//调用API获取基本信息
		//TODO tox 获取省市区数据
		//显示页面
		$this->assign('web_info', $web_info);
		$this->assign('user', $this->userdata);
		$this->display();
    }
	/*网站申请-2014-10-19-edit*/
	public function do_webconfig($siteid,$webname,$url,$domain='',$telphone=0,$email='',$oldurl='',$olddomain='',$theme_type=0,$theme='',$club_name='',$club_mobile='',$club_email='',$club_address=''){
		
		
	    if($webname=='') $this->error('请填写网站名称');
		if($theme_type=='')	$this->error('请选择俱乐部类型');
	    if($theme=='') $this->error('请选择模板');
		if($email=='') $this->error('请输入正确的email');
		if($telphone=='') $this->error('客服电话不能为空');
		if($url=='') $this->error('请输入二级域名前缀');
		if($club_address=='') $this->error('请选择所在城市');
		//if($mobile=='') $this->error("请输入手机号码!");
		if($club_name=='') $this->error('请填写负责人名称');
		if(Gcheck_Mobile($club_mobile)){
			$this->error('负责人手机号码不正确');
		}
		if(Gcheck_Email($club_email)){
			$this->error('负责人邮箱不正确');
		}
		$url = strtolower($url);
		$oldurl = strtolower($oldurl);
		$domain = strtolower($domain);
		$olddomain = strtolower($olddomain);
		
		if($siteid){
			if($oldurl && $oldurl!=$url){
				$check_url=D('websit')->where("url='{$url}' and siteid !=".$siteid)->find();
				if($check_url){
					$this->error('域名前缀已经被使用，使换成其它前缀！');
				}
			}
			
			if($olddomain && $domain && $olddomain!=$domain){
				$check_domain=D('webdomain')->where("domain='{$domain}' and siteid !=".$siteid)->find();
				if($check_domain){
					$this->error('域名已经被使用，使换成其它域名！');
				}
			}

			$data	= array(
				'webname'	=>$webname,
				'url'		=>$url,
				'email'		=>$email,
				'mobile'	=>$mobile,
				'telphone'	=>$telphone,
				'domail'	=>$domain,
				'theme_type'=>$theme_type,
				'theme'		=>$theme,
				'club_name'	=>$club_name,
				'club_mobile'	=>$club_mobile,
				'club_email'	=>$club_email,
				'club_address'	=>$club_address
			
			);
		
			$savedata=D('websit')->where("siteid = ".$siteid)->save($data);
			if($savedata){
				if($domain){
					
					$get_domain=D('webdomain')->where("domain='{$domain}' and siteid =".$siteid)->find();
					if($get_domain){
						$domain_data['domain'] = $domain;
						D('webdomain')->where("siteid = ".$siteid)->save($domain_data);
					}else{
						$domain_data['domain'] = $domain;
						$domain_data['siteid'] = $siteid;
						D('webdomain')->add($domain_data);
					}
					
				}
				$this->success('修改成功',U('Official/Usercenter/webmanage'));
			}else{
				$this->error('没更改任何数据');
			}
			
		}else{
			
			$check_url=D('websit')->where("url='{$url}'")->find();
			if($check_url){
				$this->error('域名前缀已经被使用，使换成其它前缀！');
			}
			
			if($domain){
				$check_domain=D('webdomain')->where("domain='{$domain}'")->find();
				if($check_domain){
					$this->error('域名已经被使用，请换成其它域名！');
				}
			}
			
			$data	= array(
				'gmuid'		=>is_login(),
				'webname'	=>$webname,
				'url'		=>$url,
				'email'		=>$email,
				'mobile'	=>$mobile,
				'telphone'	=>$telphone,
				'domail'	=>$domain,
				'theme_type'=>$theme_type,
				'theme'		=>$theme,
				'club_rank'	=>$club_rank,
				'address'	=>$club_address,
				'cover_id'	=>308,
				'logo_footer'=>309,
				'logo_icons'=>310,
				'club_rank'	=>1,
				'club_name'	=>$club_name,
				'club_mobile'	=>$club_mobile,
				'club_email'	=>$club_email,
				'club_address'	=>$club_address,
				'createtime'=>time()
			);
			
			D('websit')->create($data);
			$addsiteid=D('websit')->add();
			if($addsiteid){
			    /*申请成功再写入*/
				if($domain !='' ){
					$domain_data['domain'] = $domain;
					$domain_data['siteid'] = $addsiteid;
					D('webdomain')->add($domain_data);
				}
				$this->success('网站申请成功，等待管理员审核');
			}else{
				$this->error('添加失败');
			}
		}
	}
   /*联动菜单--2014-11-28 dlx pm*/
   public function dotheme(){
		$theme	=	$_POST['theme'];
		if($theme=='') $this->error('请选择模板');
		$website_theme_config =  website_theme_config();
		exit(json_encode($website_theme_config[$theme]));
   }
   public function check_website(){
	    $webname=I('webname');
		if($webname!=''){
			$map_str="webname like '%$webname%'";
		}
		
		$count=D('websit')->where($map_str)->count();
		$Page       = new \Think\Page($count,10);
		$show       = $Page->show();// 分页显示输出
	    $web_info=D('websit')->where($map_str)->field('gmuid,siteid,url,webname,telphone,status,createtime,is_vip,is_online')->limit($Page->firstRow.','.$Page->listRows)->order("siteid desc")->order('siteid desc')->select();
		
		foreach ($web_info as $k=>$vo) {
		    $webdomain=D('webdomain')->where(array('siteid'=>$vo['siteid']))->select();
			$web_info[$k]['domain']=$webdomain;
			$users  = query_user(array('username','nickname', 'signature', 'email'), $vo['gmuid']);
			$web_info[$k]['nickname'] =  $users['nickname'];
		}
		$this->assign('contacts_arr', $web_info);
		$this->assign('user', $this->userdata);
		$this->assign('page',$show);
		$this->display();
	
	}		

    public function do_check_website($siteid,$status)
    {
		if(!$siteid) $this->error('参数错误！');
		
		$check = D('websit')->where(array('siteid' => $siteid))->find();	

        if (!$check) {
		   $this->error('网站不存在！');
        }else{
				if($status == '10'){
					$usermember_data = D('ucenter_member')->where("id=".$check['gmuid'])->find();
					unset($usermember_data['id']);
					unset($usermember_data['username']);
					$usermember_data['siteid'] = $siteid;
					$u_m_id= D('User/UcenterMember')->add($usermember_data);
					if($u_m_id){
						$member_data =  D('member')->where("uid=".$check['gmuid'])->find();
						$member_data_save['siteid'] = $siteid;
						$member_data_save['uid'] = $u_m_id;
						$member_data_save['nickname'] = $member_data['nickname'];
						$member_data_save['status'] = 1;
						$member_data_save['check_admin']=1;
						$member_data_save['is_use'] = 3;
						$member_data_save['checked'] = 1;
						$m_id=D('member')->add($member_data_save);
							
						if($m_id){
							$datas['status'] = 1;
							$datas['uid']=$m_id;
							$res = D('websit')->where(array('siteid' => $siteid))->save($datas);
							
							//--copy--复制主导航---2014-10-19---*/
							$apply_all = D('websit_apply')->where("status=1")->select(); 
						    $chanel_sel = array();
							foreach($apply_all as $key => $val){
								if($val['fit_type'] ==0 && $val['setup_id'] == 0){
									$chanel_sel[$key] = $val;
									
								}else{
									if($val['setup_id']){
										$setup_id_arr = explode(',',$val['setup_id']);
										if(in_array($siteid,$setup_id_arr)){
											if($val['fit_type'] !=0){
												$fit_arr = explode(',',$val['fit_type']);
												if(in_array($type,$fit_arr)){
													$chanel_sel[$key] = $val;
												
												}
											}else{
												$chanel_sel[$key] = $val;	
												
											}
										}
									}else{
										$fit_arr = explode(',',$val['fit_type']);
										
										if(in_array($type,$fit_arr)){
											$chanel_sel[$key] = $val;
										}
									}
								}
							}
				            unset($key);
							unset($val);
							
							foreach($chanel_sel as $key=>$val){
							    
								if($chanel_sel[$key]['app_model']!='Event'){
								    $data_chanel['siteid'] 	=	$siteid;
									$data_chanel['title']  	=	$chanel_sel[$key]['app_name'];
									$data_chanel['model']  	=	$chanel_sel[$key]['app_model'];
								    $data_chanel['status'] 	=	$chanel_sel[$key]['status'];
									$data_chanel['target'] 	=	0;
									$data_chanel['display'] 	=$chanel_sel[$key]['is_nav'];
									D('channel_websit')->data($data_chanel)->add();
									
								}
								
								$install_app_arr['siteid']		= $siteid;
								$install_app_arr['app_id']		= $chanel_sel[$key]['id'];
								$install_app_arr['status']      = 1;
								$install_app_arr['app_name']	= $chanel_sel[$key]['app_name'];
								$install_app_arr['install_time']= time();
								$install_app_arr['app_model']	= $chanel_sel[$key]['app_model'];
								$install_app_arr['describe']	= $chanel_sel[$key]['describe'];
								$install_app_arr['ifconfig']    = $chanel_sel[$key]['ifconfig'];
								D("websit_install_apply")->add($install_app_arr);
								 
							}
							//--copy--复制活动类型---
							$event_type=array(
											   array('title'=>'长途旅行','status'=>1,'allow_post'=>0,'pid'=>0,'sort'=>0,'display'=>1,'customization'=>0),
											   array('title'=>'短途旅行','status'=>1,'allow_post'=>0,'pid'=>0,'sort'=>1,'display'=>1,'customization'=>0),
											   array('title'=>'团队旅行','status'=>1,'allow_post'=>0,'pid'=>0,'sort'=>2,'display'=>1,'customization'=>1)
											);
							
							 foreach($event_type as $key=>$val){
								$data_type['siteid']       = $event_type[$key]['siteid']=$siteid;
								$data_type['title']        = $event_type[$key]['title'];
								$data_type['create_time']  = $event_type[$key]['create_time']=time();
								$data_type['status']       = $event_type[$key]['status'];
								$data_type['allow_post']   = $event_type[$key]['allow_post'];
								$data_type['sort']         = $event_type[$key]['sort'];
								$data_type['pid']          = $event_type[$key]['pid'];
								$data_type['display']      = $event_type[$key]['display'];
								$data_type['customization']= $event_type[$key]['customization'];
								$event_add	=	D('event_type')->data($data_type)->add();
							 }
							 /*--复制官方公告--自定义1条-2014-10-29*/
							  
								   $blogdatas['siteid']      = $siteid;
								   $blogdatas['title']       = '热门公告';
								   $blogdatas['sort']        = 0;
								   $blogdatas['pid']         = 0;
								   $blogdatas['create_time'] = time();
								   $blogdatas['status']      = 1;
							
								 D('category')->data($blogdatas)->add();
							 /*复制故事分类--自定义1条-2014-10-29*/
							
								   $issuedatas['siteid']     = $siteid;
								   $issuedatas['title']      = '品牌故事';
								   $issuedatas['allow_post'] = 0;
								   $issuedatas['sort']       = 0;
								   $issuedatas['pid']        = 0;
								   $issuedatas['create_time']= time();
								   $issuedatas['status']     = 1;
								   $issuedatas['customization']= 1;
						
								 D('issue')->data($issuedatas)->add();
							 /*生成二维码--2014-11-19--dlx*/
                                $qrcode_url = set_qrcode(array('id'=>$siteid),'website',$siteid);
								if($qrcode_url){
									$qrcode_data['siteid'] = $siteid;
									$qrcode_data['uid'] =  is_login();
									$qrcode_data['linkid'] =  $siteid;
									$qrcode_data['types'] =  'website';
									$qrcode_data['url'] =  $qrcode_url;
									$qrcode_data['create_time'] =  time();
									D('qrcode')->add($qrcode_data);
								}
								
							if($res && $event_add){
								$this->success('操作成功','refresh');
							}else{
								$this->error('操作失败');
							}
						}else{
							$this->error('用户数据复制失败');
						}
					}else{
						$this->error('用户信息复制失败');
					}	
									
			   
				}else{
					$datas['status'] = $status;
					$res = D('websit')->where(array('siteid' => $siteid))->save($datas);
					if ($res) {
						$this->success('操作成功','refresh');
					} else {
						$this->error('操作失败');
					}
				
				}
		
		}

    }
	public function websit_add(){
		if(IS_POST){
		  $siteid=$_POST['siteid'];
		  $domain=trim($_POST['domain']);
		  
		  if($siteid=='') $this->error('参数错误');
		  if($domain=='') $this->error('请填写完整');
			$checkdomain = D('webdomain')->where(array('domain' => strtolower($domain)))->field('domain')->find();
			if( $checkdomain){
				$this->error('域名已经存在,请更换其它域名或联系官方客服');
			}
		  
			$data['siteid']=$siteid;
			$data['domain']=$domain;
		  
			$web_add=D('webdomain')->data($data)->add();
			if($web_add){
				$this->success('添加成功',U('Official/Usercenter/check_website'));
			}else{
				$this->error('添加失败');
			}
		
		}else{
		   $this->assign('siteid',$_GET['siteid']);
		   $this->assign('user',$this->userdata);
		   $this->display();
		}
		
	
	}
	/*网站编辑-修改**
	**2014-10-18**
	**denglixing***/
	
	
	
	 public function websit_edit($siteid=0,$webname='',$url='',$telphone=0,$email='',$oldurl='',$theme_type=0,$theme='',$club_name='',$club_mobile='',$club_email='',$club_address='',$is_vip=0,$is_online=1)
	 {
	    if(IS_POST){
		    //$webname=trim($_POST['webname']);
			
			if($webname=='') $this->error('请填写网站名称');
			if($theme_type=='')	$this->error('请选择俱乐部类型');
			if($theme=='') $this->error('请选择模板');
			if($email=='') $this->error('请输入正确的email');
			if($telphone=='') $this->error('客服电话不能为空');
			if($url=='') $this->error('请输入二级域名前缀');
			if($club_address=='') $this->error('请选择所在城市');
			//if($mobile=='') $this->error("请输入手机号码!");
			if($club_name=='') $this->error('请填写负责人名称');
			if(Gcheck_Mobile($club_mobile)){
				$this->error('负责人手机号码不正确');
			}
			if(Gcheck_Email($club_email)){
				$this->error('负责人邮箱不正确');
			}
			$domain=$_POST['domain'];


			$save_data['is_vip'] = $is_vip;
			$save_data['is_online'] = $is_online;
			$url = strtolower($url);
			$oldurl = D('websit')->where(array('siteid' => $siteid))->field('url')->find();
			if( strtolower($oldurl['url']) != $url){
				$oldurl = D('websit')->where(array('siteid' => array('neq',$siteid),'url' => $url))->field('url')->find();
				if($oldurl) $this->error('二级域名已经存在');
			}
			$save_data['url']=strtolower($url);
			if($domain[0]){
				$olddomainurl = D('webdomain')->where(array('siteid' => $siteid))->field('domain')->select();
				if($olddomainurl){
					foreach($olddomainurl as $key=>$d){
						$olddomain_arr[$key] = strtolower($d['domain']);
					}
				}
				
				foreach($domain as $k=>$v){
					if(!trim($v)) continue;
						$newurl = strtolower($v);
						if($olddomain_arr){
							if (in_array($newurl,$olddomain_arr)){
							 	$add_domain[$k] = $newurl;
							}else{
								$checkdomain = D('webdomain')->where(array('domain' =>$newurl))->find();
								if($checkdomain) $this->error('【'.$newurl.'】已经存在，请更换其它域名');
							  	$add_domain[$k] = $newurl;
							}
						}else{
							$checkdomain = D('webdomain')->where(array('domain' =>$newurl))->find();
							if($checkdomain) $this->error('【'.$newurl.'】已经存在，请更换其它域名');
							$add_domain[$k] = $newurl;
						}
				}
			}
			
			
			if($add_domain){
				D('webdomain')->where(array('siteid' => $siteid))->delete();//--先delete--
				foreach($add_domain as $k=>$domain){
					$save_adddomin['domain']=strtolower($domain);
					$save_adddomin['siteid'] = $siteid;
					$web_add= D('webdomain')->add($save_adddomin);
				}
			}
			
			
			$save_data['webname']=$webname;
			$save_data['theme_type']=$theme_type;
			$save_data['theme']=$theme;
			
			$save_data['email']=$email;
			$save_data['telphone']=$telphone;
			$save_data['club_name']=$club_name;
			$save_data['club_mobile']=$club_mobile;
			$save_data['club_email']=$club_email;
			$save_data['club_address']=$club_address;
			$websit_save=D('websit')->where("siteid=".$siteid)->save($save_data);
			
			if($web_add || $websit_save){ 
			/****修改二维码--2014-11-19 dlx--*/
			/*--先查询--*/
			    $qrcodefind =  D('qrcode')->where(array('siteid'=>$siteid,'types'=>'website'))->find();
			    $qrcode_url = set_qrcode(array('id'=>$siteid),'website',$siteid);
				if($qrcodefind){
				    $qrcode_data['siteid'] = $siteid;
		            $qrcode_data['linkid'] =  $siteid;
					$qrcode_data['types'] =  'website';
					$qrcode_data['url'] =  $qrcode_url;
				    D('qrcode')->where(array('siteid'=>$siteid,'types'=>'website'))->save($qrcode_data);
				}else{
				    $qrcode_data['siteid'] = $siteid;
		            $qrcode_data['linkid'] =  $siteid;
					$qrcode_data['types'] =  'website';
					$qrcode_data['url'] =  $qrcode_url;
				    D('qrcode')->add($qrcode_data);
				}

			    $this->success('更改成功',U('Official/Usercenter/check_website'));
			}else{
			    $this->error('未更改数据');
			}
		}else{
		   	$sitied=$_GET['id'];
			$web_info=D('websit')->where("siteid=$sitied")->find();
			$webdomain=D('webdomain')->field('siteid,domain')->where("siteid = ".$sitied)->select();
			$yesorno = array('1'=>'是','0'=>'否');
			$this->assign('yesorno',$yesorno);
			$this->assign('user',$this->userdata);
			$this->assign('web_name',$web_info);
			$this->assign('data',$webdomain);
			$this->display();
		}
		   
    }
    //---网站账务管理-2014-10-27-
	public function financial_management(){
	      $status=I('status');
		  if($status != ''){
		     $map['status']=$status;
		  }
	      $count=D('websit_cashout_record')->where($map)->count();
		  $Page       = new \Think\Page($count,10);
		  $show       = $Page->show();// 分页显示输出  
	      
		  $list=D('websit_cashout_record')->where($map)->order('time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		  
		  foreach($list as $key=>$val){
		    $cardinfo=json_decode($list[$key]['cardinfo'],true);
		    $list_websit=D('websit')->where("siteid=".$val['siteid'])->find();
			$user= query_user(array('username','nickname', 'signature', 'email'), $vo['uid']);
			$list[$key]['webname']=$list_websit['webname'];
		    $list[$key]['username']=$user['username'];
			$list[$key]['name']=$cardinfo['name'];
			$list[$key]['card']=$cardinfo['card'];
			$list[$key]['open_bank']=$cardinfo['open_bank'];
		  
		  }
		  $this->assign('cash_list',$list);
	      $this->assign('user',$this->userdata); 
          $this->assign('page',$show);		  
		  $this->display();
	     
	}
	/***审审核通过--
	***2014-10-25**/
	public function doFinancial(){
	    if(IS_POST){
		    $id     = I('id');
			$status = I('status');
			$siteid = I('siteid');
			$cash   = I('cash');
			if($id     =='') $this->error('参数错误! ');
			if($siteid =='') $this->error('参数错误! ');
			if($status =='') $this->error('参数错误! ');
		    if($cash   =='') $this->error('参数错误! ');
			$cash_record    = D('websit_cash_record');  //支付记录
			$cashout_record = D('websit_cashout_record'); //支出
			$cash_record->startTrans();
			
			$cashdata['status'] = I('status');
			$cashout_record_save=$cashout_record->where('id='.$id)->save($cashdata);//支付记录表更改状态
			  
			$list_find      = $cash_record->where('siteid='.$siteid)->find();/**/
		
				 //$datarecord['total']  = $list_find['total']  + $cash; //总额 - 申请钱数
				 $datarecord['frozen'] = $list_find['frozen'] - $cash; //冻结 - 申请钱数
				 
				 $cash_record_save=$cash_record->where("siteid=".$siteid)->save($datarecord);//申请财务记录表
					 
					 $cate['total'] = $cash;	//记录支出
					 $cate['siteid']= $siteid;
					 $cate['uid']   = $list_find['uid'];
					 $cate['time']  = time();
					 $cate['type']  = 1;
					 $cate['status']= 2;
					 $websit_log=D('websit_log')->data($cate)->add();
					
					if($cashout_record_save && $cash_record_save && $websit_log){
					     $cash_record->commit();
						 $this->success('申请成功','refresh');
					
					}else{
					    $cash_record->rollback();
						$this->error('申请失败');
					
					}
				
			
		 }
	      
		
	}
	/**2014-10-25**
	***申请不通过*
	**denglixing**/
	public function financialban(){
	      if(IS_POST){
			  $siteid  = I('siteid');
			  $id      = I('id');
			  $cash    = I('cash');
			  $status  = I('status');
				  if($id      =='') $this->error('参数错误! ');
				  if($siteid  =='') $this->error('参数错误! ');
				  if($status  =='') $this->error('参数错误! ');
				  if($cash    =='') $this->error('参数错误! ');
			  
			  $datas['status']=$status; 
			  $cash_record    = D('websit_cash_record');  //支付记录
			  $cashout_record = D('websit_cashout_record'); //支出
			  $cash_record->startTrans();
			  
			  $cashout_record_save=$cashout_record->where("id=".$id)->save($datas);//--更改成功--
			  $cash_find=$cash_record->where('siteid='.$siteid)->find();
					
					$cates['frozen']  = $cash_find['frozen']    - $cash; //冻结的钱
					$cates['balance'] = $cash_find['balance'] + $cash;  //余额
					$cash_record_save=$cash_record->where('siteid='.$siteid)->save($cates);
				  
					$cate['total'] = $cash;	//记录支出
					$cate['siteid']= $siteid;
					$cate['uid']   = $cash_find['uid'];
					$cate['time']  = time();
					$cate['type']  = 1;
                    $cate['status']= 0;					
					$websit_log=D('websit_log')->data($cate)->add();
					
						if($cashout_record_save && $cash_record_save && $websit_log){
						   $cash_record->commit();
						   $this->success('操作成功');
						}else{
						   $cash_record->rollback();
						   $this->error('操作失败');
						}
					
					
				 
			   
		  
		  }
		  
	}
	
	/*2014*/
	public function all_tags()
	{
		
		$count=D('tags')->count();
		$Page       = new \Think\Page($count,10);// 
		$show       = $Page->show(); 
		$tags_arr=D('tags')->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('tags', $tags_arr);
		$this->assign('user', $this->userdata);
		$this->assign('page',$show);
		$this->display();
	}
	
   public function tags_add(){
			
		$this->assign('user', $this->userdata);
		$this->display();
    }
	
	
   public function tags_edit($id){
			$tags=D('tags')->where("id=".$id)->find();
			$this->assign('data', $tags);
            $this->assign('user', $this->userdata);
            $this->display();
    }
	
	
   public function do_tags_edit($id,$title){
	   
		$check = D('tags')->where(array('id' => $id))->find();	
        if (!$check) {
            $this->error('标签不存在！');
        }
		$datas['title'] = $title;
		$res = D('tags')->where(array('id' => $id))->save($datas);
		if ($res) {
			$this->success('修改成功',U('Official/Usercenter/all_tags'));
		} else {
			$this->error('修改失败');
		}
    }
	
   public function do_tags_add($title){
	   
		$check = D('tags')->where(array('id' => $id))->find();	
        if (!$title) $this->error('请填写标签名！');
		$datas['title'] = op_t($title);
		$res = D('tags')->add($datas);
		if ($res) {
			$this->success('添改成功',U('Official/Usercenter/all_tags'));
		} else {
			$this->error('添改失败');
		}
    }
	


   
	
    
    /*扩展得到权限*/
    public function doevent_edit(){
           
           $uid=is_login();
           $is_use=$_POST['is_use'];
           $data['uid']=$uid;
           $data['is_use']=$is_use;
           $cate=D('member')->where("uid=$uid")->save($data);
            if($cate){
              //$this->success('申请成功等待管理员审核',U('Config/index'));
            	echo 1;
            }else{
               //$this->error('保存失败!'); 
            	echo 0;
           }
         }
  
	
    /**验证用户名
     * @param $nickname
     * @auth 陈一枭
     */
    private function checkNickname($nickname)
    {
        $length = mb_strlen($nickname, 'utf8');
        if ($length == 0) {
            $this->error('请输入昵称。');
        } else if ($length >= 10) {
            $this->error('昵称不能超过10个字。');
        } else if ($length <= 1) {
            $this->error('昵称不能少于1个字。');
        }
        $match = preg_match('/^(?!_|\s\')[A-Za-z0-9*_\x80-\xff\s\']+$/', $nickname);
        if (!$match) {
            $this->error('昵称只允许中文、字母、下划线和数字。');
        }

        $map_nickname['nickname'] = $nickname;
        $map_nickname['uid'] = array('neq', is_login());
		$map_nickname['siteid'] = SITEID;
        $had_nickname = D('Member')->where($map_nickname)->count();
        if ($had_nickname) {
            $this->error('昵称已被人使用。');
        }
    }


    /**验证签名
     * @param $signature
     * @auth 陈一枭
     */
    private function checkSignature($signature)
    {
        $length = mb_strlen($signature, 'utf8');
        if ($length >= 30) {
            $this->error('签名不能超过30个字');
        }
        /*中英文*/
        /*if(!preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$signature)){
            $this->error('个性签名只能为中文');
        }*/

    }

    /*验证qq*/
    public function check_qq($qq)
     {
        if(!preg_match("/^[1-9]*[1-9][0-9]*$/",$qq)){
            $this->error('请输入正确的QQ号码');
        }
        if($qq==''){
            $this->error('不能为空');
        }
     }
     /*验证电话号码*/
     public function checkTelphone($telphone){
         if($telphone==''){
            $this->error('手机号码不能为空');
         }
		if(!preg_match("/^1[0-9]{10}$/",$telphone)){

            $this->error('请输入正确的手机号码');
        }
       
     }
     /*验证自我介绍*/
     public function check_self_introduction($self_introduction){
        if($self_introduction == ''){
            $this->error('自我介绍不能为空');
        }
        $length = mb_strlen($self_introduction, 'utf8');
        if($length >= 500){
            $this->error('自我介绍不能超过500个字');
        }
        /*if(!preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$self_introduction)){
            $this->error('自我介绍只能为中文');
        }*/

     }
     
     /*验证身份证号码*/
     public function check_card($card){
        if($card ==''){
            $this->error('请输入身份证号');
        }
        if(!preg_match("/(^\d{15}$)|(^\d{17}([0-9]|X)$)/",$card)){
            $this->error('请输入正确15-18位身份证号码');
        }
     }
     /*验证添加联系人中的电话号码*/
     public function check_telephone($telephone){
        if(!preg_match("/^1[0-9]{10}$/",$telephone)){
            $this->error('请输入正确的联系电话。');
          }
        if($telephone=''){
            $this->error('请填写手机号码');
        }
     }
     public function check_realname($realname){
        if (trim(op_t($realname)) == '') {
            $this->error('请输入真实姓名。');
        }
     }
     /*验证微博号码*/
     public function checkMicroBo($microBo){
        if($microBo==''){
            $this->error('微博号码不能为空');
        }

     }
     /*选中判断*/
     /*
      checked
       0 审核没有通过
       1 审核通过
       2 审核未通过
     */
     private function check_checked($uid){
     	//-----------申请过后的记录-----------
        $group_user=D('Member')->field('is_use,checked')
                               ->where("uid=$uid and is_use>1")
                               ->find();
         if($group_user){
              $this->assign('u_status',$group_user);
              
              $this->assign('num','2');
              $checked=$group_user['checked'];
              if($checked=='0'){
                $this->assign('check',$checked);
              }else{
                $this->assign('check','1');
              }
           }

     }
    /**用户信息表与
    用户资料两表连查*/
    private function data($uid){
        $data_user=D('thinkox_member');
           return $data=$data_user->Table(array('thinkox_member'=>'m','thinkox_ucenter_member'=>'u'))
                             ->field('m.uid,m.nickname,m.qq,m.score,m.signature,m.pos_province,m.pos_city,m.pos_district,m.is_use,m.pos_community,m.self_introduction,m.sex,m.telphone,m.real_name,u.email,u.username')
                             ->where("m.uid=u.id and m.uid=$uid")
                             ->find();
      }

    /**获取用户扩展信息
     * @param null $uid
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function getExpandInfo($uid = null)
    {
        $profile_group_list = $this->_profile_group_list($uid);
        if ($profile_group_list) {
            $info_list = $this->_info_list($profile_group_list[0]['id'], $uid);
            $this->assign('info_list', $info_list);
            $this->assign('profile_group_id', $profile_group_list[0]['id']);
            //dump($info_list);exit;
        }

        $this->assign('profile_group_list', $profile_group_list);
    }


    /**显示某一扩展分组信息
     * @param null $profile_group_id
     * @param null $uid
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function showExpandInfo($profile_group_id = null, $uid = null)
    {
        $res = D('field_group')->where(array('id' => $profile_group_id, 'status' => '1'))->find();
        if (!$res) {
            $this->error('信息出错！');
        }
        $profile_group_list = $this->_profile_group_list($uid);
        $info_list = $this->_info_list($profile_group_id, $uid);
        $this->assign('info_list', $info_list);
        $this->assign('profile_group_id', $profile_group_id);
        //dump($info_list);exit;
        $this->assign('profile_group_list', $profile_group_list);
        $this->defaultTabHash('expand-info');
        $this->display('expandinfo');
    }

    /**修改用户扩展信息
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function edit_expandinfo($profile_group_id)
    {

        $field_setting_list = D('field_setting')->where(array('profile_group_id' => $profile_group_id, 'status' => '1'))->order('sort asc')->select();

        if (!$field_setting_list) {
            $this->error('没有要修改的信息！');
        }

        $data = null;
        foreach ($field_setting_list as $key => $val) {
            $data[$key]['uid'] = is_login();
            $data[$key]['field_id'] = $val['id'];
            switch ($val['form_type']) {
                case 'input':
                    $val['value'] = op_t($_POST['expand_' . $val['id']]);
                    if (!$val['value'] || $val['value'] == '') {
                        if ($val['required'] == 1) {
                            $this->error($val['field_name'] . '内容不能为空！');
                        }
                    } else {
                        $val['submit'] = $this->_checkInput($val);
                        if ($val['submit'] != null && $val['submit']['succ'] == 0) {
                            $this->error($val['submit']['msg']);
                        }
                    }
                    $data[$key]['field_data'] = $val['value'];
                    break;
                case 'radio':
                    $val['value'] = op_t($_POST['expand_' . $val['id']]);
                    $data[$key]['field_data'] = $val['value'];
                    break;
                case 'checkbox':
                    $val['value'] = $_POST['expand_' . $val['id']];
                    if (!is_array($val['value']) && $val['required'] == 1) {
                        $this->error('请至少选择一个：' . $val['field_name']);
                    }
                    $data[$key]['field_data'] = is_array($val['value']) ? implode('|', $val['value']) : '';
                    break;
                case 'select':
                    $val['value'] = op_t($_POST['expand_' . $val['id']]);
                    $data[$key]['field_data'] = $val['value'];
                    break;
                case 'time':
                    $val['value'] = op_t($_POST['expand_' . $val['id']]);
                    $val['value'] = strtotime($val['value']);
                    $data[$key]['field_data'] = $val['value'];
                    break;
                case 'textarea':
                    $val['value'] = op_t($_POST['expand_' . $val['id']]);
                    if (!$val['value'] || $val['value'] == '') {
                        if ($val['required'] == 1) {
                            $this->error($val['field_name'] . '内容不能为空！');
                        }
                    } else {
                        $val['submit'] = $this->_checkInput($val);
                        if ($val['submit'] != null && $val['submit']['succ'] == 0) {
                            $this->error($val['submit']['msg']);
                        }
                    }
                    $val['submit'] = $this->_checkInput($val);
                    if ($val['submit'] != null && $val['submit']['succ'] == 0) {
                        $this->error($val['submit']['msg']);
                    }
                    $data[$key]['field_data'] = $val['value'];
                    break;
            }
        }
        $map['uid'] = is_login();
        $is_success = false;
        foreach ($data as $dl) {
            $map['field_id'] = $dl['field_id'];
            $res = D('field')->where($map)->find();
            if (!$res) {
                if ($dl['field_data'] != '' && $dl['field_data'] != null) {
                    $dl['createTime'] = $dl['changeTime'] = time();
                    if (!D('field')->add($dl)) {
                        $this->error('信息添加时出错！');
                    }
                    $is_success = true;
                }
            } else {
                $dl['changeTime'] = time();
                if (!D('field')->where('id=' . $res['id'])->save($dl)) {
                    $this->error('信息修改时出错！');
                }
                $is_success = true;
            }
            unset($map['field_id']);
        }
        clean_query_user_cache(is_login(), 'expand_info');
        if ($is_success) {
            $this->success('保存成功！');
        } else {
            $this->error('没有要保存的信息！');
        }
    }

    /**input类型验证
     * @param $data
     * @return mixed
     * @author 郑钟良<zzl@ourstu.com>
     */
    function _checkInput($data)
    {
        if ($data['form_type'] == "textarea") {
            $validation = $this->_getValidation($data['validation']);
            if (($validation['min'] != 0 && mb_strlen($data['value'], "utf-8") < $validation['min']) || ($validation['max'] != 0 && mb_strlen($data['value'], "utf-8") > $validation['max'])) {
                if ($validation['max'] == 0) {
                    $validation['max'] = '';
                }
                $info['succ'] = 0;
                $info['msg'] = $data['field_name'] . "长度必须在" . $validation['min'] . "-" . $validation['max'] . "之间";
            }
        } else {
            switch ($data['child_form_type']) {
                case 'string':
                    $validation = $this->_getValidation($data['validation']);
                    if (($validation['min'] != 0 && mb_strlen($data['value'], "utf-8") < $validation['min']) || ($validation['max'] != 0 && mb_strlen($data['value'], "utf-8") > $validation['max'])) {
                        if ($validation['max'] == 0) {
                            $validation['max'] = '';
                        }
                        $info['succ'] = 0;
                        $info['msg'] = $data['field_name'] . "长度必须在" . $validation['min'] . "-" . $validation['max'] . "之间";
                    }
                    break;
                case 'number':
                    if (preg_match("/^\d*$/", $data['value'])) {
                        $validation = $this->_getValidation($data['validation']);
                        if (($validation['min'] != 0 && mb_strlen($data['value'], "utf-8") < $validation['min']) || ($validation['max'] != 0 && mb_strlen($data['value'], "utf-8") > $validation['max'])) {
                            if ($validation['max'] == 0) {
                                $validation['max'] = '';
                            }
                            $info['succ'] = 0;
                            $info['msg'] = $data['field_name'] . "长度必须在" . $validation['min'] . "-" . $validation['max'] . "之间，且为数字";
                        }
                    } else {
                        $info['succ'] = 0;
                        $info['msg'] = $data['field_name'] . "必须是数字";
                    }
                    break;
                case 'email':
                    if (!preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $data['value'])) {
                        $info['succ'] = 0;
                        $info['msg'] = $data['field_name'] . "格式不正确，必需为邮箱格式";
                    }
                    break;
                case 'phone':
                    if (!preg_match("/^\d{11}$/", $data['value'])) {
                        $info['succ'] = 0;
                        $info['msg'] = $data['field_name'] . "格式不正确，必须为手机号码格式";
                    }
                    break;
            }
        }
        return $info;
    }

    /**处理$validation
     * @param $validation
     * @return mixed
     * @author 郑钟良<zzl@ourstu.com>
     */
    function _getValidation($validation)
    {
        $data['min'] = $data['max'] = 0;
        if ($validation != '') {
            $items = explode('&', $validation);
            foreach ($items as $val) {
                $item = explode('=', $val);
                if ($item[0] == 'min' && is_numeric($item[1]) && $item[1] > 0) {
                    $data['min'] = $item[1];
                }
                if ($item[0] == 'max' && is_numeric($item[1]) && $item[1] > 0) {
                    $data['max'] = $item[1];
                }
            }
        }
        return $data;
    }

    /**分组下的字段信息及相应内容
     * @param null $id 扩展分组id
     * @param null $uid
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function _info_list($id = null, $uid = null)
    {
        $info_list = null;

        if (isset($uid) && $uid != is_login()) {
            //查看别人的扩展信息
            $field_setting_list = D('field_setting')->where(array('profile_group_id' => $id, 'status' => '1', 'visiable' => '1'))->order('sort asc')->select();

            if (!$field_setting_list) {
                return null;
            }
            $map['uid'] = $uid;
        } else if (is_login()) {
            $field_setting_list = D('field_setting')->where(array('profile_group_id' => $id, 'status' => '1'))->order('sort asc')->select();

            if (!$field_setting_list) {
                return null;
            }
            $map['uid'] = is_login();

        } else {
            $this->error('请先登录！');
        }
        foreach ($field_setting_list as $val) {
            $map['field_id'] = $val['id'];
            $field = D('field')->where($map)->find();
            $val['field_content'] = $field;
            $info_list[$val['id']] = $val;
            unset($map['field_id']);
        }

        return $info_list;
    }


    /**扩展信息分组列表获取
     * @return mixed
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function _profile_group_list($uid = null)
    {
        if (isset($uid) && $uid != is_login()) {
            $map['visiable'] = 1;
        }
        $map['status'] = 1;
        $profile_group_list = D('field_group')->where($map)->order('sort asc')->select();

        return $profile_group_list;
    }


    public function changeAvatar()
    {
        $this->defaultTabHash('change-avatar');
        $this->display();
    }
    /*扩展资料里面内容*/
    public function doExpand($is_use=0,$realname='',$telphone='',$sex=0,$qq=0,$email=''){
          $uid=is_login();
    	if(IS_POST){ 
    	   $is_use=op_t($is_use);
           $realname=op_t(trim($realname));
    	   $telphone=op_t($telphone);
    	   $sex=op_t($sex);
    	   $qq=op_t($qq);
    	   $email=op_t($email);
		   /*验证*/
    	   if($realname==''){
    	   	 $this->error('请输入真实姓名');
    	   }
            $this->checkTelphone($telphone);
            $this->checkSex($sex);
            $this->checkEmail($email);
            $this->check_qq($qq);
            /*if($agree !=1){
              $this->error('请点击同意');
            }*/
            $data['realname']=$realname;
            $data['telphone']=$telphone;
            $data['sex']=$sex;
            $data['qq']=$qq;
            $data['email']=$email;
            $data['uid']=$uid;
            
            $das['is_use']=$is_use;
            $user=D('member')->where("uid=$uid")->save($das);
           
            //-----------先查询------------
            $pres=D('member_personal')->where("uid=$uid")->find();
            if(!$pres){
              $cate=D('member_personal')->data($data)->add();
               if($cate>0 && $user>0){
            	   $this->success('申请成功,管理员将会24小时之内确定');
                }else{
            	    $this->error('未提交成功');
              }
            }else{
               $cate=D('member_personal')->where("uid=$uid")->save($data);
               if($user>0){
               	  $this->success('申请成功,管理员将会24小时之内确定');
               }else{
               	  $this->error('未提交成功');
               }
            }
          }
    }
    //---修改领队会员信息------
    public function doExpandAgree($realname='',$telphone='',$sex=0,$qq=0,$email=''){
           if(IS_POST){
           	  
	           $realname=op_t(trim($realname));
	    	   $telphone=op_t($telphone);
	    	   $qq=op_t($qq);
	    	   $email=op_t($email);
	    	    /*验证*/
	    	  if($realname==''){
	    	   	 $this->error('请输入真实姓名');
	    	   }
	    	    $this->checkTelphone($telphone);
	            $this->checkSex($sex);
	            $this->checkEmail($email);
	            $this->check_qq($qq);
	            $data['realname']=$realname;
	            $data['telphone']=$telphone;
	            $data['sex']=$sex;
	            $data['qq']=$qq;
	            $data['email']=$email;
				$uid=is_login();
	            $pers=D('member_personal')->where("uid=$uid")->find();
				if($pers){
				  $cats=D('member_personal')->where("uid=$uid")->save($data);
					 if($cats){
							$this->success('修改成功');
						}else{
							$this->error('修改失败!');
						}
				  }else{
				     $data['uid']=$uid;
				     $dats=D('member_personal')->data($data)->add();
					 if($dats){
					  $this->success('添加成功');
					 }else{
					  $this->error('添加失败');
					 }
				  }
	            
	           
	     }
    }
    public function doCropAvatar($crop)
    {
        //调用上传头像接口改变用户的头像
        $result = callApi('User/applyAvatar', array($crop));
        $this->ensureApiSuccess($result);

        //显示成功消息
        $this->success($result['message'], U('Official/Usercenter/index', array('tab' => 'avatar')));
    }

    public function doUploadAvatar()
    {
        //调用上传头像接口
        $result = callApi('User/uploadTempAvatar');

        $this->ensureApiSuccess($result);

        //显示成功消息
        $this->iframeReturn(apiToAjax($result));
    }

    private function iframeReturn($result)
    {
        $json = json_encode($result);
        $json = htmlspecialchars($json);
        $html = "<textarea data-type=\"application/json\">$json</textarea>";
        echo $html;
        exit;
    }
	
    //执行修改过之后
    public function event_member($id=0){
        $id=$_GET['id'];
		$event_attend = D('event_attend')->where(array('id'=>$id))->order('id desc')->find();
		$userinfo = json_decode($event_attend['userinfo'],true);
        $this->assign('userinfo',$userinfo);
        $this->display();
    }
	
    //执行修改过之后
    public function event_allmember($id=0){
        $id=$_GET['id'];
		
		
		$event_attend = D('event_attend')->where(array('calendar_id'=>$id))->order('creat_time desc')->select();
		
		foreach ($event_attend as $key => $v) {	
			$userinfo_arr = json_decode($v['userinfo'],true);
			foreach ($userinfo_arr as $ukey => $u) {
				$userinfo[]= $u;
			}
		}
        $this->assign('userinfo',$userinfo);
        $this->display();
    }
	
	public function del_event($id = 0){
		if($id ==""){
			$this->error('未知的活动!');
		}
		$data['status'] ='-1';
		$rs = D('Event')->where(array('id'=>$id))->save($data);
		if($rs){
			$this->success('删除成功!');
		}else{
			$this->error('删除失败！');
		}
	}
    public function officiallogout()
    {
        //调用退出登录的API
        $result = callApi('Public/logout');
        $this->ensureApiSuccess($result);

        exit(json_encode(array('message' => $result['message'], 'url' => U('Official/Index/index'))));
        //显示页面
        //$this->success($result['message'], U('Home/Index/index'));
    }
	
    public function doChangePassword($old_password, $new_password)
    {
        //调用接口
        $result = callApi('User/changePassword', array($old_password, $new_password));
        $this->ensureApiSuccess($result);

        //显示成功信息
        $this->success($result['message'],'refresh');
    }
    
    /**
     * @param $sex
     * @return int
     * @auth 陈一枭
     */
    private function checkSex($sex)
    {

        if ($sex < 0 || $sex > 2) {
            $this->error('性别必须属于男、女、保密。');
            return $sex;
        }
        return $sex;
    }

    /**
     * @param $email
     * @param $email
     * @auth 陈一枭
     */
    private function checkEmail($email)
    {
        $pattern = "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
        if (!preg_match($pattern, $email)) {
            $this->error('邮箱格式错误。');
        }

        $map['email'] = $email;
        $map['id'] = array('neq', get_uid());
        $had = D('UcenterMember')->where($map)->count();
        if ($had) {
            $this->error('该邮箱已被人使用。');
        }
    }
}
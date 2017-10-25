<?php

namespace Usercenter\Controller;

use Think\Controller;

class PointcardController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize();
		$this->userdata = query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $uid);
    }

    public function index(){
		$stas=$_GET['stas'];
		$stas = isset($stas)? $stas:'mycard';
		switch($stas){
		    case 'mycard'://我的优惠券
				$status=$_GET['status'];
				if($status){
					$map['status']=$status;
				}
				$map['siteid']=SITEID;
				$map['userid']=is_login();
				$count=D('pointcard_user')->where($map)->count();
				$Page       = new \Think\Page($count,10);
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				$show       = $Page->show();// 
				$list	=	D('pointcard_user')->where($map)->limit($Page->firstRow .','.$Page->listRows)->order("diff_time")->select();
				foreach($list as $key=>$val){
					$list[$key]['card_info'] = D('pointcard')->where(array('cardid'=>$val['cardid'],'siteid'=>SITEID))->find();
					$list[$key]['cardname'] = 	$list[$key]['card_info']['typename'];				
				}
				$this->assign('cardlist',$list);
				$this->assign('page',$show);
			break;
			case 'bindcard':
			
			break;
			
		}
		$this->assign('user',$this->userdata);
		$this->assign('stas',$stas);
		
		$this->display();
    }
	/*
	*bindcard验证--2014-11-28 pm
	*/
	public function doCard(){
		$cardid	=$_POST['cardid'];
		$cardid =strtoupper($cardid);
		if($cardid=="") $this->error('请输入券码!');
		$data=D('pointcard_unified')->where("unifiedcardid='{$cardid}' and siteid=".SITEID)->find();
		if($data){ 
			if($data['leftnum']==0){ 
				echo json_encode(array('status'=>0,'info'=>'该券已全部抢完!'));
			}else{
				if(!empty($data['endtime'])){ 
					if($data['endtime']<=time()){ 
						echo json_encode(array('status'=>0,'info'=>'该券已到期!'));
					}else{ 
						$cardmap=array('unifiedcardid'=>$cardid,'status'=>1,'userid'=>is_login());
						$cardhavelist=D('pointcard')->where($cardmap)->find();
						if($cardhavelist){ 
							echo json_encode(array('status'=>0,'info'=>'亲，您已经领取过该券!'));
						}else{ 
							$cardmap2=array('unifiedcardid'=>$cardid,'status'=>1,'userid'=>0);
							$cardlist=D('pointcard')->where($cardmap2)->find();
							if($cardlist){ 
								echo json_encode(array('status'=>1,'cardtype'=>$data['cardtype'],'info'=>'该券可以使用'));
							}else{ 
								echo json_encode(array('status'=>0,'info'=>'该券已全部抢完!'));
							}

						}

						
					}
				}else{ 
					echo json_encode(array('status'=>1,'cardtype'=>$data['cardtype'],'info'=>'该券可以使用'));
				}				
			}

		}else{ 
			$list	=	D('pointcard')->where("cardid='{$cardid}' and siteid=".SITEID)->find();
		
			if($list){
				
				if($list['userid']){
					echo json_encode(array('status'=>0,'info'=>'该券已被绑定!'));
				
				}else{
					//if($list['status']==0){ 
						//echo json_encode(array('status'=>0,'info'=>'该券已失效!'));
					//}else{ 
						if(!empty($list['endtime'])){
							if($list['endtime']<=time()){
								echo json_encode(array('status'=>0,'info'=>'该券已到期!'));
							}else{
								echo json_encode(array('status'=>1,'cardtype'=>$list['cardtype'],'info'=>'该券可以使用'));
							}
						
						}else{
							echo json_encode(array('status'=>1,'cardtype'=>$list['cardtype'],'info'=>'该券可以使用'));
						}


					//}
					
				}
			
			}else{
				
				echo json_encode(array('status'=>0,'info'=>'你所输入的券码不存在!'));
				
			}


		}
		
	}
	/*
	**验证密码cardkey--2014-11-28 dlx-pm
	*/
	public function doCardkey(){
		$cardid		=	$_POST['cardid'];
		$cardkey	=	$_POST['cardkey'];
		$list	=	D('pointcard')->where("cardid='{$cardid}' and cardkey='{$cardkey}' and siteid=".SITEID)->find();
		if($list){
			echo 1;
		}else{
			echo 0;
		}
	
	}
	/*
	*绑定卡--2014-11-28 dlx -pm
	*/
	public function bindcard(){
		 if(IS_POST){
			$cardid = $_POST['cardid'];
			$cardkey = $_POST['cardkey'];
			$cardid	= op_t(trim($cardid));
			$cardid = strtoupper($cardid);
			$cardkey=op_t(trim($cardkey));
			if($cardid=='')	$this->error('请输入券码');
			$havemap=array('unifiedcardid'=>$cardid,'userid'=>is_login(),'siteid'=>SITEID);
			$havelist=D('pointcard')->where($havemap)->find();
			if($havelist){ 
				$this->error('亲，您已经领取过该券!');
			}
			
			$data=D('pointcard_unified')->where("unifiedcardid = '{$cardid}' and siteid=".SITEID)->find();
			if($data){
				if($data['leftnum']==0) $this->error('该券已全部抢完!');
				if(!empty($data['endtime'])){
					if($data['endtime']<=time()) $this->error('该券已过期!');
				}

				if($data['cardtype']==2){ 
					$unlist['leftnum']=$data['leftnum']-1;
					$point_unified_save=D('pointcard_unified')->where("id = ".$data['id'])->save($unlist);
					if($point_unified_save){ 
						D('pointcard')->startTrans();
						$unifiedcardid=$data['unifiedcardid'];
						$pointmap=array('unifiedcardid'=>$unifiedcardid,'siteid'=>SITEID,'userid'=>0,'endtime'=>array(array('gt',time()),array('eq',0),'or'),'mobile'=>array('eq',0));
						$point_list=D('pointcard')->where($pointmap)->find();
						$point_save=D('pointcard')->where("id = ".$point_list['id'])->save(array('userid'=>is_login()));
						$point_arr	= array(
									   'bindtime'  => time(),
									   'cardid' => $point_list['cardid'],
									   'siteid' => SITEID,
									   'userid'	=> is_login()						
								);
						$bindcarduser	=	D('pointcard_user')->data($point_arr)->add();


						if($point_save && $bindcarduser){
							D('pointcard')->commit(); 
							add_card_log($point_list['cardid'],1,'领取活动卡','领取(代金券/活动卡)');
							$this->success('领取成功','refresh');
						}else{ 

							D('pointcard')->rollback();
							D('pointcard_unified')->where(array('unifiedcardid'=>$unifiedcardid,'siteid'=>SITEID))->setInc('leftnum',1);
							$this->error('领取失败!');

						}

					}else{ 

						$this->error('领取失败!');
					}
					

				}
				


			}else{
				$cardid=strtoupper($cardid);
				$list	=	D('pointcard')->where("cardid='{$cardid}' and siteid=".SITEID)->find();
				if($list){
					
					if($list['userid']) $this->error('该券已被领取!');
					//if($list['status']==0) $this->error('该券已失效');
					if(!empty($list['endtime'])){
						if($list['endtime']<=time()) $this->error('该券已过期!');
					}
					if($list['cardtype']==1){
						if($cardkey=='') $this->error('请输入密码!');
						$cardfind	=	D('pointcard')->where("cardid='{$cardid}' and cardkey='{$cardkey}' and siteid=".SITEID)->find();
						if($cardfind){
							$bindcards	=	D('pointcard')->where("cardid='{$cardid}' and siteid=".SITEID)->save(array('userid'=>is_login()));
							$point_arr	= array(
								   'bindtime'  => time(),
								   'cardid' => $cardid,
								   'siteid' => SITEID,
								   'userid'	=> is_login()						
							);
							$bindcarduser	=	D('pointcard_user')->data($point_arr)->add();
							if($bindcards	&& $bindcarduser){
								add_card_log($cardid,1,'领取活动卡','领取(代金券/活动卡)');
								$this->success('领取成功','refresh');
							}else{
								$this->error('领取失败!');
							}
						
						}else{
							$this->error('密码有误!');
						
						}
					
					
					}elseif($list['cardtype']==2){
						
						$bindcards	=	D('pointcard')->where("cardid='{$cardid}' and siteid=".SITEID)->save(array('userid'=>is_login()));
						$point_arr	= array(
								   'bindtime'  => time(),
								   'cardid' => $cardid,
								   'siteid' => SITEID,
								   'userid'	=> is_login()						
							);
						$bindcarduser	=	D('pointcard_user')->data($point_arr)->add();
						
						if($bindcards && $bindcarduser){
							add_card_log($cardid,1,'领取代金券','领取(代金券/活动卡)');
							$this->success('领取成功','refresh');
						 }else{
							$this->error('领取失败!');
						 }
					
					}
				
				}else{
					$this->error('券码不存在');
				}


			}
		 
		 }else{
			$this->display();
		 }
	
	}
	public function donate($cardid){
		if(!$cardid) $this->error('参数错误！');
		$this->assign('cardid',$cardid);
		$this->display();
	}
	public function do_donate($cardid = '',$reviever_tel = '',$user_select=''){
		$this->checkTelphone($reviever_tel);
		$user_info = D('ucenter_member')->where(array('mobile'=>$reviever_tel,'siteid'=>SITEID))->select();
		if($user_info){
			if(count($user_info) > 1 && $user_select== '' ){
				$this->error('该手机号码对应多个账号，请选择您要操作的账号再赠送');
			}elseif((count($user_info) > 1 && $user_select != '') || count($user_info) == 1){
				$uid = count($user_info) > 1 ? $user_select : $user_info[0]['id'] ;
				if($uid == is_login()) $this->error('赠送对象不能为自己！');
				$carddata=D('pointcard')->where("cardid = '{$cardid}'")->find();
				if($carddata['stamp']==2){ 
					$havemap=array('unifiedcardid'=>$carddata['unifiedcardid'],'siteid'=>SITEID,'userid'=>$uid,'stamp'=>2);
					$havebigcard=D('pointcard')->where($havemap)->find();
					if($havebigcard){ 
						$this->error('亲，赠送对象已领过该类券,不能再赠送喽！');
					}
				}
				

				$card_check =D('Common/Pointcard')->check_card($cardid,true);	
				if($card_check['status']){
				/**********pointcard_user 表增加一条被赠送的记录***************/
				$card_info = D('pointcard_user')->where(array('siteid'=>SITEID,'cardid'=>$cardid,'userid'=>is_login()))->find();
				if(!empty($card_info['givetime'])) $this->error('您已经赠送过该代金券了，无法重复赠送！');
					$data['siteid'] = SITEID;
					$data['cardid'] = $cardid;
					$data['userid'] = $uid;
					$data['to_uid'] = $uid;
					$data['from_uid'] = is_login();
					$data['bindtime'] = time();
					$data['gettime'] = time();
					$p_add = D('pointcard_user')->add($data);
					/*******************改变 pointcard_user原个人优惠券的信息******/
					$savedata_1['to_uid'] = $uid;
					$savedata_1['from_uid'] = is_login();
					$savedata_1['givetime'] = time();
					$p_save = D('pointcard_user')->where(array('siteid'=>SITEID,'userid'=>is_login(),'cardid'=>$cardid))->save($savedata_1);
					/**************改变总表userid uid字段**************************/
					$savedata['userid'] = $uid;
					/**开启事务**/
					D('pointcard')->startTrans();
					$pid = D('pointcard')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->save($savedata);
					if($p_add && $p_save && $pid){
						$webinfo = json_decode(WEBSITEINFO,true);
						$web_url = "http://".$_SERVER['HTTP_HOST'];
						$webname = $webinfo['webname'];
						$user_info = query_user(array('nickname',is_login()));
						$user_name = $user_info['nickname'];
						$pointdata= array(
								'user_name'		=>	$user_name,
								'webname'		=>	$webname,
								'cardid'	 	=>	$cardid,
								'web_url'		=>	$web_url,
								'noticetype'    =>  'distribute_coupons',
							);

						$contactway=array($reviever_tel);                
						D('Message')->addSendMessage('send_sms_to_user',$contactway,$pointdata,0,1);
						add_card_log($cardid,1,'','',$uid);
						D('pointcard')->commit();
						$this->success('赠送成功！','refresh');
					}else{
						D('pointcard')->rollback();
						$this->error('赠送失败！');
					}
				}else{
					$this->error($card_check['msg']);
				}
			}
		}else{
			$carddata=D('pointcard')->where("cardid = '{$cardid}'")->find();
			if($carddata['stamp']==2){ 
				$havemap=array('unifiedcardid'=>$carddata['unifiedcardid'],'siteid'=>SITEID,'mobile'=>$reviever_tel,'stamp'=>2);
				$havebigcard=D('pointcard')->where($havemap)->find();
				if($havebigcard){ 
					$this->error('亲，赠送对象已领过该类券,不能再赠送喽！');
				}
			}
				$card_check = D('Common/Pointcard')->check_card($cardid,true);
				if($card_check['status']){ 
					$card_info = D('pointcard_user')->where(array('siteid'=>SITEID,'cardid'=>$cardid,'userid'=>is_login()))->find();
					if(!empty($card_info['givetime'])) $this->error('您已经赠送过该代金券了，无法重复赠送！');
					$data['siteid'] = SITEID;
					$data['cardid'] = $cardid;
					$data['userid'] = 0;
					$data['to_uid'] = 0;
					$data['from_uid'] = is_login();
					$data['bindtime'] = time();
					$data['gettime'] = time();
					
					$p_add = D('pointcard_user')->add($data);

					$savedata_1['to_uid'] = 0;
					$savedata_1['from_uid'] = is_login();
					$savedata_1['givetime'] = time();
					$p_save = D('pointcard_user')->where(array('siteid'=>SITEID,'userid'=>is_login(),'cardid'=>$cardid))->save($savedata_1);
					
					$savedata['mobile'] = $reviever_tel;
					$savedata['userid'] = 0;
					D('pointcard')->startTrans();
					$pid = D('pointcard')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->save($savedata);
					if($p_add && $p_save && $pid){
						$webinfo = json_decode(WEBSITEINFO,true);
						$web_url = "http://".$_SERVER['HTTP_HOST'];
						$webname = $webinfo['webname'];
						$pointdata= array(
								'webname'		=>	$webname,
								'cardid'	 	=>	$cardid,
								'web_url'		=>	$web_url,
								'noticetype'    =>  'distribute_coupons',
							);

						$contactway=array($reviever_tel);                
						D('Message')->addSendMessage('send_sms_to_user',$contactway,$pointdata,0,1);
						add_card_log($cardid,1,'','',0);
						D('pointcard')->commit();
						$this->success('赠送成功,该手机号非本站会员，请尽快通知机主上线领取！','refresh');
					}else{
						D('pointcard')->rollback();
						$this->error('赠送失败！');
					}


				}else{ 
					$this->error($card_check['msg']);
				}	
			
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
	 public function check_user($mobile){
		if(!preg_match("/^1[0-9]{10}$/",$mobile)){
			echo json_encode(array('status'=>false,'msg'=>'请输入正确的手机号码'));
        }else{
			$user_arr = D('ucenter_member')->where(array('mobile'=>$mobile,'siteid'=>SITEID))->select();
			if($user_arr){
				foreach($user_arr as $key => $val){
					$user_arr[$key]['nickname'] = query_user('nickname',$val['id']);
				}
				echo json_encode(array('status'=>true,'user_arr'=>$user_arr));
			}else{ 

				echo json_encode(array('status'=>true,'user_out'=>1,'user_arr'=>true));
			}
		}				
	 }
}  

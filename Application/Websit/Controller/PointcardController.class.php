<?php

namespace Websit\Controller;

use Think\Controller;

class PointcardController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize();
		
        //$this->handle = D('Paydeposit');
	}

    public function index(){
		$status=$_GET['status'];
		$status = isset($status)? $status:0;
		switch($status){
		    case 0:
				$map['siteid']=SITEID;
				$count=D('pointcard')->where($map)->count();
				$Page       = new \Think\Page($count,10);
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				$show       = $Page->show();// 
				$list	=	D('pointcard')->where($map)->limit($Page->firstRow .','.$Page->listRows)->order("id desc")->select();
				foreach($list as $key=>$val){
					$typename[$key] = D('pointcard_type')->where(array('ptypeid'=>$val['ptypeid'],'siteid'=>SITEID))->getField('name');
					if(list[$key]['endtime']){
						$list[$key]['show_send']=($list[$key]['endtime']>time())?1:0;
					}else{  
						$list[$key]['show_send']=1;
					}
					$list[$key]['cardname'] = $typename[$key];
					$user	=	query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $val['gmuid']);
					$list[$key]['admin']	=	$user['nickname'];
					if($list[$key]['userid']!=0){
						$card_use_info[$key] = D('pointcard_user')->where(array('cardid'=>$val['cardid'],'siteid'=>SITEID))->find();
						$list[$key]['usetime'] = $card_use_info[$key]['usetime'];
						$list[$key]['bindtime'] = $card_use_info[$key]['bindtime'];
						$userid	=	query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $val['userid']);
						$list[$key]['usernickname']	=	$userid['nickname'];	
						
					}else{
						$list[$key]['usernickname']	= '';
					}
				}
				
				$this->assign('cardlist',$list);
				$this->assign('page',$show);
			break;
			case 2;
				$map['siteid']=SITEID;
				$count=D('pointcard_type')->where($map)->count();
				$Page       = new \Think\Page($count,10);
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				$show       = $Page->show();// 
				$list=D('pointcard_type')->where($map)->limit($Page->firstRow .','.$Page->listRows)->order("ptypeid desc")->select();
				$this->assign('cardlist',$list);
				$this->assign('page',$show);
			break;
			case 3;
			    $pictrue_arr = D('advs')->where("siteid = ".SITEID)->order('position asc,level asc')->select();
				foreach ($pictrue_arr as $key => &$val) {
					$position_arr = D('advertising')->where("id = ".$val['position'])->find();
					$val['positiontext'] = $position_arr['title'];
					if($val['status']){
						$val['statustext']= '启用';
					}else{
						$val['statustext']= '禁用';
					}
					
				}
		
				$this->assign('pictrue',$pictrue_arr);
		    break;
		
		}
		$this->assign('status',$status);
		
		$this->display();
    }
	/*
	**添加卡类型 2014-11-27 dlx am
	*/
	public function pointer_type_doAdd($cardtype=0,$name='',$amount=0)
	{
	    if(IS_POST){
		    $cardtype	=	op_t($cardtype);
			$name		=	op_t(trim($name));
			$amount		=	op_t(trim($amount));
			if($cardtype=='') $this->error('参数错误!');
			if($name=='') $this->error('请填写卡型名称!');
			if($amount=='' ||  !is_numeric($amount)) $this->error('请输入正确的面值金额!');
			
			$data	=	array(
				'siteid'	=>	SITEID,
				'cardtype'	=>	$cardtype,
				'name'		=>	$name,
				'amount'	=>	$amount
			);	
			$samemap=array('siteid'=>SITEID,'name'=>$name);
			$samedata=D('pointcard_type')->where($samemap)->find();
			if($samedata){ 
				$this->error('已经有相同名称的优惠券了！建议修改类型名称');

			}else{ 
				$list	=	D('pointcard_type')->data($data)->add();
				if($list){
					$this->success('添加成功','refresh');
				}else{
					$this->error('添加失败!');
				}


			}
		
		}                 
	
	
	}
	/*
	**修改点卡类型 2014-11-27 dlx pm
	*/
	public function pointer_type_edit($ptypeid=0,$cardtype=0,$name='',$amount=0)
	{
		if(IS_POST){
			$cardtype	=	op_t($cardtype);
			$name		=	op_t(trim($name));
			$amount		=	op_t(trim($amount));
			
			if($cardtype==''||$ptypeid=='') $this->error('参数错误!');
			if($name=='') $this->error('请填写卡型名称!');
			if($amount=='' ||  !is_numeric($amount)) $this->error('请输入正确的面值金额!');
			
			$data	=	array(
				'siteid'	=>	SITEID,
				'cardtype'	=>	$cardtype,
				'name'		=>	$name,
				'amount'	=>	$amount
			);	
			$samemap=array('siteid'=>SITEID,'name'=>$name,'ptypeid'=>array('neq',$ptypeid));
			$samedata=D('pointcard_type')->where($samemap)->find();
			if($samedata){ 
				$this->error('已经有相同名称的优惠券了！建议修改类型名称');

			}else{ 
				$list	=	D('pointcard_type')->where("ptypeid=".$ptypeid)->save($data);
				if($list){
					$this->success('修改成功','refresh');
				}else{
					$this->error('修改失败!');
				}

			}
		
		}else{
			$ptypeid=$_GET['id'];
			$list	=	D('pointcard_type')->where("ptypeid=".$ptypeid)->find();
			$this->assign("typelist",$list);
			$this->display();
		}
	}
	
	/*
	*是否禁用**2014-11-27 dlx pm
	*/
	public function pointcard_disable()
	{
		$list	=	D('pointcard_type')->where("ptypeid=".$_POST['ptypeid'])->save(array('status'=>$_POST['status']));
		if($list){
			$this->success('操作成功');
		}else{
			$this->error('操作失败!');
		}
	
	}
	/*
	*点卡连动
	*/
	public function pointcard_add(){
		
			
			$map['siteid']	=	SITEID;
			$map['status']	=	1;
			$list	=	D('pointcard_type')->where($map)->select();
			$this->assign('cardlist',$list);
			$this->display();
			
	}
	/*
	*批量生成点卡**2014-11-27- dlx
	*/
	public function pointer_doAdd($cardnum,$prefix,$cardlength,$endtime,$starttime)
	{
	  
		$endtime = op_t(trim($endtime));
		$starttime = op_t(trim($starttime));
		if(!empty($endtime)){
			 $endtime = strtotime($endtime);
			 if($endtime	<=	time()) $this->error('结束使用时间不能小于当前时间!');
		}else{
			 $endtime = 0;
		}
		if(!empty($starttime)){
			$starttime	= strtotime($starttime);//开始使用时间
		}else{
			$starttime = 0;
		}
		if(!empty($endtime) && !empty($starttime)){
			if($starttime > $endtime){
				$this->error('结束使用时间不能小于开始使用时间');
			}
		}
		$ptypeid = intval($_POST['ptypeid']);//卡类型
		$number  = intval($cardnum); //生数量
		$length  = intval($cardlength); //卡密的长度
		$prefix = $_POST['prefix'];//卡号前缀
		$createtime = time();
		
		if($ptypeid	=='') $this->error('请添加完点卡类型后再进行操作！',U('Websit/Pointcard/index',array('status'=>2)));
		
		if($number	==''||!is_numeric($number)) $this->error('请填写正确的生成数量!');	
		if($prefix	=='') $this->error('请填写卡号前缀');
		if(preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$prefix)) $this->error('卡号前缀不能使用中文!');
		if($length	==''||!is_numeric($length))  $this->error('卡号长度必须为数字!');
		$row =	D('pointcard_type')->where(array('ptypeid'=>$ptypeid))->find();
		$card_name = $row['name'];
		$count_card = 0;
		for ($i = 0 ; $i < $number ; $i++ )
		{
			$cardid	= $prefix.substr(str_shuffle("01234567890123456789"),0,$length);
			switch($row['cardtype']){
				case 1://有密卡
					$cardkey	 = substr(str_shuffle("01234567890123456789"),0,8);
				break;
				case 2://无密卡
					$cardkey	 = 	'';
				break;
				
			}
			$r = $this->checkCardId($cardid);
            if(empty($r)){	
				$data	=	array(
				      'siteid'	=>	SITEID,
					  'ptypeid'	=>	$ptypeid,
					  'cardid'	=>	$cardid,
					  'gmuid'	=>	is_login(),
					  'createtime'	=>	$createtime,
					  'cardkey'	=>	$cardkey,
					  'amount'	=> 	$row['amount'],
					  'endtime'	=> 	$endtime,
					  'point'	=>	$row['point'],
					  'cardtype'=>	$row['cardtype'],
					  'status'	=>  1,
					  'starttime' => $starttime
					
				);
							
				$list	=	D('pointcard')->data($data)->add();
				if($list){
					$count_card ++;
				}
            }else{
                continue;
            }
		}
			if($list){			
				$this->success('添加成功！',U('Websit/Pointcard/card_back_info',array('total'=>$number,'succ'=>$count_card)));
			}else{
				$this->error('添加失败!');
			}
	
	}
	public function card_back_info($total = 0,$succ = 0){	
		$this->assign('total',$total);
		$this->assign('succ',$succ);
		$this->display();
	}
	/*
	*修改点卡信息--2014-11-28-dlx-am**
	*/
	public function pointcard_edit()
	{
		if(IS_POST){
			$id		=	$_POST['id'];
			$endtime=	$_POST['endtime'];
			if($id=='') $this->error('参数错误!');
			$list	=	D('pointcard')->where("id=".$id)->save(array('endtime'=>$endtime));
			if($list){
				$this->success('操作成功','refresh');
			}else{
				$this->error('操作失败!');
			}
		
		}else{
			$id	= $_GET['id'];
			$list	=	D('pointcard')->where("id=".$id)->find();
			$listcard	=	D('pointcard_type')->where(array('ptypeid'=>$list['ptypeid'],'status'=>1))->find();
			
			$this->assign('cardinfo',$list);
			$this->assign('listcard',$listcard);
			$this->display();
		}
	
	
	}
	/*
	*验证卡号
	*/
    public function checkCardId($cardid)
    {
		$list	=	D('pointcard')->where(array('cardid'=>$cardid))->find();
		if($list){
			return true;
		}else{
			return false;
		}
    }
	/**
	* 获取随机字符串
	*/
	public function randString($length) {
		$mt_string = 'P5kQjRi6ShTgU7fVeK3pLoM4nNmOlW8dXcY9bZaAzBy0CxDwEv1FuGtHs2IrJq';
		$str = '';
		while ($length--) {
			$str .= $mt_string[mt_rand(0, 61)];
		}
		return $str;
	}
	/*
	*是否禁用卡号
	**/
	public function pointcard_is_disable(){
			$id	= $_POST['id'];
			$status = $_POST['status'];
			$card_info = D('pointcard')->where(array('id'=>$id,'siteid'=>SITEID))->find();
			if($id=='' || $status=='') $this->error('参数错误');
			$list = D('pointcard')->where("id=".$id)->save(array('status'=>$status));
			if($list){
				if($status == 1){
					if($card_info['cardtype'] == 1){
						add_card_log($card_info['cardid'],$status,'活动卡启用','代金券/活动卡(禁/启)用');
					}elseif($card_info['cardtype'] == 2){
						add_card_log($card_info['cardid'],$status,'代金券启用','代金券/活动卡(禁/启)用');
					}
				}else{
					if($card_info['cardtype'] == 1){
						add_card_log($card_info['cardid'],$status,'活动卡禁用','代金券/活动卡(禁/启)用');
					}elseif($card_info['cardtype'] == 2){
						add_card_log($card_info['cardid'],$status,'代金券禁用','代金券/活动卡(禁/启)用');
					}
				}
				$this->success('操作成功');
			
			}else{
				$this->error('操作失败!');
			}
	
	}
	
	/*
	 * 点击查看相关日志
	 * $param $cardid 点卡卡号
	 */
	public function card_log($cardid){
		$card_log = D('pointcard_log')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->order('id desc')->select();
		foreach($card_log as $key => &$val){
			$val['userinfo']	=	query_user(array('nickname'), $val['uid']);
		}
		$this->assign('cardlist',$card_log);
		$this->display();
	}
	
	public function send_to_user($cardid = ''){
		if(!$cardid) $this->error('参数错误！');
		$this->assign('cardid',$cardid);
		$this->display();
	}
	public function do_send($cardid = '',$mobile = '',$user_select=''){
		if(!Gcheck_Mobile($mobile)){
			$user_info = D('ucenter_member')->where(array('mobile'=>$mobile,'siteid'=>SITEID))->select();
			if($user_info){
				if(count($user_info) > 1 && $user_select== '' ){
					$this->error('该手机号码对应多个账号，请选择您要操作的账号再派发');
				}elseif((count($user_info) > 1 && $user_select != '') || count($user_info) == 1){
					$uid = count($user_info) > 1 ? $user_select : $user_info[0]['id'] ;
					if($uid == is_login()) $this->error('派发对象不能为自己！');
					$card_check = check_card($cardid,true);	
					if($card_check['status']){
						$card_info = D('pointcard')->where(array('siteid'=>SITEID,'cardid'=>$cardid))->find();
						$data['siteid'] = SITEID;
						$data['cardid'] = $cardid;
						$data['userid'] = $uid;
						$data['to_uid'] = $uid;
						$data['from_uid'] = is_login();
						$data['bindtime'] = time();
						$data['gettime'] = time();
						$p_add = D('pointcard_user')->add($data);
						/**开启事务**/
						$savedata['userid'] = $uid;
						D('pointcard')->startTrans();
						$pid = D('pointcard')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->save($savedata);
						if($p_add && $pid){
							$webinfo = json_decode(WEBSITEINFO,true);
							$webname = $webinfo['webname'];
							$web_url = "http://".$_SERVER['HTTP_HOST'];
							$user_info = query_user(array('nickname',$event_uid));
							$user_name = $user_info['nickname'];
							/*
							$msg = "您收到管理员[{$user_name}]派发的来自［{$webname}］的代金券,券码为[{$cardid}], 请尽快登录［{$webname}］，确认代金券内容";
							sms_alerts($mobile,$msg,'派发优惠券');
							*/
							$pointdata= array(
								'user_name'		=>	$user_name,
								'webname'		=>	$webname,
								'cardid'	 	=>	$cardid,
								'noticetype'    =>  'distribute_coupons',
								'web_url'       =>  $web_url,

							);
							$contactway=array($mobile);                
							D('Message')->addSendMessage('send_sms_to_user',$contactway,$pointdata,0,1);
							add_card_log($cardid,1,'','',$uid,true);						
							D('pointcard')->commit();
							$this->success('派发成功！','refresh');
						}else{
							D('pointcard')->rollback();
							$this->error('派发失败！');
						}
					}else{
						$this->error($card_check['msg']);
					}
				}
				
				/*************************************************************************/
				$uid = $user_info['id'];
				if($user_info['id'] == is_login()) $this->error('派发对象不能为自己！');
				$card_check = check_card($cardid,true);
				if($card_check['status']){
					$card_info = D('pointcard')->where(array('siteid'=>SITEID,'cardid'=>$cardid))->find();
					$data['siteid'] = SITEID;
					$data['cardid'] = $cardid;
					$data['userid'] = $uid;
					$data['to_uid'] = $uid;
					$data['from_uid'] = is_login();
					$data['bindtime'] = time();
					$data['gettime'] = time();
					$p_add = D('pointcard_user')->add($data);
					/**开启事务**/
					$savedata['userid'] = $uid;
					D('pointcard')->startTrans();
					D('pointcard')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->save($savedata);
					if($p_add){
						$webinfo = json_decode(WEBSITEINFO,true);
						$webname = $webinfo['webname'];
						$web_url = "http://".$_SERVER['HTTP_HOST'];
						$user_info = query_user(array('nickname',$event_uid));
						$user_name = $user_info['nickname'];
						/*
						$msg = "您收到管理员[{$user_name}]派发的来自［{$webname}］的代金券,券码为[{$cardid}], 请尽快登录［{$webname}］，确认代金券内容";
						sms_alerts($mobile,$msg,'派发优惠券');
						*/
					  	$pointdata= array(
								'user_name'		=>	$user_name,
								'webname'		=>	$webname,
								'cardid'	 	=>	$cardid,
								'noticetype'    =>  'distribute_coupons',
								'web_url'       =>  $web_url,

							);
						$contactway=array($mobile);                
						D('Message')->addSendMessage('send_sms_to_user',$contactway,$pointdata,0,1);
						add_card_log($cardid,1,'','',$uid,true);						
						D('pointcard')->commit();
						$this->success('派发成功！','refresh');
					}else{
						D('pointcard')->rollback();
						$this->error('派发失败！');
					}
				}
			}else{
				$this->error('您提供的手机号无对应账号，请确认账号手机信息是否正确');
			}			
		}else{
			$this->error('请输入有效的手机号码！');
		}
	}

}  
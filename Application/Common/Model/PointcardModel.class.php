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

class PointcardModel extends Model{
	
	/*绑定卡券*/
	public function bindcard($cardid,$cardkey='',$uid=0){ 
		$uid=$uid?$uid:is_login();
		if($cardid==''){ 
			$res['status']=0;
			$res['info']="请输入券码!";
			return $res;
		}
		
		$havemap=array('unifiedcardid'=>$cardid,'userid'=>$uid,'siteid'=>SITEID);
		$havelist=D('pointcard')->where($havemap)->find();
		if($havelist){ 
			$res['status']=0;
			$res['info']="亲，您已经领取过该券!";
			return $res;
		}		
		$data=D('pointcard_unified')->where("unifiedcardid = '{$cardid}' and siteid=".SITEID)->find();
		if($data){
			if($data['leftnum']==0){ 
				$res['status']=0;
				$res['info']="该券已全部抢完!";
				return $res;
			} 
			if(!empty($data['endtime'])){
				if($data['endtime']<=time()){ 
					$res['status']=0;
					$res['info']="该券已过期!";
					return $res;
				}
			}
			if($data['cardtype']==2){ 
				$unlist['leftnum']=$data['leftnum']-1;
				$point_unified_save=D('pointcard_unified')->where("id = ".$data['id'])->save($unlist);
				if($point_unified_save){ 
					D('pointcard')->startTrans();
					$unifiedcardid=$data['unifiedcardid'];
					$pointmap=array('unifiedcardid'=>$unifiedcardid,'siteid'=>SITEID,'userid'=>0,'endtime'=>array(array('gt',time()),array('eq',0),'or'),'mobile'=>array('eq',0));
					$point_list=D('pointcard')->where($pointmap)->find();
					$point_save=D('pointcard')->where("id = ".$point_list['id'])->save(array('userid'=>$uid));
					$point_arr	= array(
								   'bindtime'  => time(),
								   'cardid' => $point_list['cardid'],
								   'siteid' => SITEID,
								   'userid'	=> $uid						
							);
					$bindcarduser	=	D('pointcard_user')->data($point_arr)->add();
					if($point_save && $bindcarduser){
						D('pointcard')->commit(); 
						add_card_log($point_list['cardid'],1,'领取活动卡','领取(代金券/活动卡)');
						$res['status']=1;
						$res['info']="领取成功!";
						return $res;
					}else{ 

						D('pointcard')->rollback();
						D('pointcard_unified')->where(array('unifiedcardid'=>$unifiedcardid,'siteid'=>SITEID))->setInc('leftnum',1);
						$res['status']=0;
						$res['info']="领取失败!";
						return $res;
					}
				}else{ 
					$res['status']=0;
					$res['info']="领取失败!";
					return $res;
				}	
			}
		}else{
			$cardid=strtoupper($cardid);
			$list	=	D('pointcard')->where("cardid='{$cardid}' and siteid=".SITEID)->find();
			if($list){
				$listmap=array('unifiedcardid'=>$list['unifiedcardid'],'siteid'=>SITEID,'userid'=>$uid,'stamp'=>2);
				$havebigcard=D('pointcard')->where($listmap)->find();
				if($havebigcard){ 
					$res['status']=0;
					$res['info']="亲，你已经领过该类劵!";
					return $res;
				}
				if($list['userid']){ 
					$res['status']=0;
					$res['info']="该券已被领取!";
					return $res;
				}
				//if($list['status']==0) $this->status('该券已失效');
				if(!empty($list['endtime'])){
					if($list['endtime']<=time()){ 
						$res['status']=0;
						$res['info']="该券已过期!";
						return $res;
					}
				}
				if($list['cardtype']==1){
					if($cardkey==''){ 
						$res['status']=0;
						$res['info']="请输入密码!";
						return $res;
					}
					$cardfind	=	D('pointcard')->where("cardid='{$cardid}' and cardkey='{$cardkey}' and siteid=".SITEID)->find();
					if($cardfind){
						$bindcards	=	D('pointcard')->where("cardid='{$cardid}' and siteid=".SITEID)->save(array('userid'=>$uid));
						$point_arr	= array(
							   'bindtime'  => time(),
							   'cardid' => $cardid,
							   'siteid' => SITEID,
							   'userid'	=> $uid						
						);
						$bindcarduser	=	D('pointcard_user')->data($point_arr)->add();
						if($bindcards	&& $bindcarduser){
							add_card_log($cardid,1,'领取活动卡','领取(代金券/活动卡)');
							$res['status']=1;
							$res['info']="领取成功!";
							return $res;	
						}else{
							$res['status']=0;
							$res['info']="领取失败!";
							return $res;
						}
					}else{
						$res['status']=0;
						$res['info']="密码有误!";
						return $res;
					}
				}elseif($list['cardtype']==2){
					$bindcards	=	D('pointcard')->where("cardid='{$cardid}' and siteid=".SITEID)->save(array('userid'=>$uid));
					$point_arr	= array(
							   'bindtime'  => time(),
							   'cardid' => $cardid,
							   'siteid' => SITEID,
							   'userid'	=> $uid						
						);
					$bindcarduser	=	D('pointcard_user')->data($point_arr)->add();
					
					if($bindcards && $bindcarduser){
						add_card_log($cardid,1,'领取代金券','领取(代金券/活动卡)');
						$res['status']=1;
						$res['info']="领取成功!";
						return $res;
					 }else{
					 	$res['status']=0;
						$res['info']="领取失败!";
						return $res;
					 }
				}
			}else{
				$res['status']=0;
				$res['info']="券码不存在!";
				return $res;
			}
		}
	}
	public function CheckCardStatus($cardid=''){
		if($cardid==""){ 
			$res['status']=0;
			$res['info']="请输入券码！";
			return $res;
		}
		$cardid =strtoupper($cardid);
		$data=D('pointcard_unified')->where("unifiedcardid='{$cardid}' and siteid=".SITEID)->find();
		if($data){ 
			if($data['leftnum']==0){ 
				$res['status']=0;
				$res['info']="该券已全部抢完!";
				return $res;
			}else{
				if(!empty($data['endtime'])){ 
					if($data['endtime']<=time()){ 
						$res['status']=0;
						$res['info']="该券已到期!";
						return $res;
					}else{ 
						$cardmap=array('unifiedcardid'=>$cardid,'status'=>1,'userid'=>is_login());
						$cardhavelist=D('pointcard')->where($cardmap)->find();
						if($cardhavelist){ 
							$res['status']=0;
							$res['info']="亲，您已经领取过该券!";
							return $res;
						}else{ 
							$cardmap2=array('unifiedcardid'=>$cardid,'status'=>1,'userid'=>0);
							$cardlist=D('pointcard')->where($cardmap2)->find();
							if($cardlist){ 
								$res['status']=1;
								$res['cardtype']=$data['cardtype'];
								$res['info']="该券可以使用!";
								return $res;
							}else{ 
								$res['status']=0;
								$res['info']="该券已全部抢完!";
								return $res;
							}

						}

						
					}
				}else{ 
					$res['status']=1;
					$res['cardtype']=$data['cardtype'];
					$res['info']="该券可以使用!";
					return $res;
				}				
			}

		}else{ 
			$list	=	D('pointcard')->where("cardid='{$cardid}' and siteid=".SITEID)->find();
		
			if($list){
				
				if($list['userid']){
					$res['status']=0;
					$res['info']="该券已被绑定!";
					return $res;
				}else{
					//if($list['status']==0){ 
							//$res['status']=0;
							//$res['info']="该券已失效!";
							//return $res;
					//}else{ 
						if(!empty($list['endtime'])){
							if($list['endtime']<=time()){
								$res['status']=0;
								$res['info']="该券已到期!";
								return $res;
							}else{
								$res['status']=1;
								$res['cardtype']=$data['cardtype'];
								$res['info']="该券可以使用!";
								return $res;
							}
						
						}else{
							$res['status']=1;
							$res['cardtype']=$data['cardtype'];
							$res['info']="该券可以使用!";
							return $res;
						}
					//}		
				}
			
			}else{
				$res['status']=0;
				$res['info']="你所输入的券码不存在!";
				return $res;
				
			}


		}
		
	}

	
	public function mobile_bind_card($mobile='',$uid=0){ 
		$mostatus=get_every_check('mobile',$mobile);
		if($mostatus){ 
			$map=array('mobile'=>$mobile,'siteid'=>SITEID,'userid'=>0);
			$list=D('pointcard')->where($map)->select();
			if($list){ 
				foreach ($list as $k => $v){

					var_dump($v);

				}
			}
		}else{ 
			$res['status']=0;
			$res['info']="手机号码错误!";
			return $res;
		}
	}
	
	/*
    * 优惠券列表
	*/
	public function ajax_card_select($card_membercontacts='',$server_condition=0,$shop_id='',$card_type=1){
		$map['userid']=is_login();
		$map['siteid']=SITEID;
		$map['server_condition']=array('elt',$server_condition);
		if($card_type==1){ 
			$map['card_type']=1;
		}
		$card_arr = D('pointcard')->where($map)->select();
		$card_final = array();
		foreach($card_arr as $key => &$val){
			$cardlist=$this->check_card($val['cardid']);
			if(!$cardlist['status']){
				continue;
			}else{
				if($val['card_type']==1){ 
					$card_final[] = $val;
				}elseif($val['card_type']==2){
					if($shop_id){ 
						if(in_array($shop_id, explode(',', $val['shop_id']))){ 
							$card_final[] = $val;
						}else{ 
							continue;
						}
					}
				}else{ 
					continue;
				}
				
				
			} 
		}
		foreach($card_final as $key => &$val){
			if($val['endtime'] != 0){
				$endtime[$key] = date('Y-m-d,H:i:s',$val['endtime']);
				$val['useinfo'] = "该【".$val['typename']."】有效期至【".$endtime[$key].'】';
			}else{
				$val['useinfo'] = "该【".$val['typename']."】长期有效";
			}
		}

		if($card_membercontacts != ''){ 
			$card_contacts = explode(',',$card_membercontacts);
			foreach($card_final as $key => &$val){
				foreach ($card_contacts as $k => &$v) {
					if($v == $val['cardid']){ 
						$card[$key] = $val; 
					}else{ 
						continue;
					}
				}
			}

			$card_final = array_diff_assoc($card_final,$card);
		}

		$result	=	array(
			'res'	=>	$card_final,
		);
		return $result;
   }
	
	
	/*
    * 验证优惠券
	*/
	public function ajax_check_card($card){
		$card=strtoupper($card);
		$re_arr = $this->check_card($card);
		if(!$re_arr['status']){
			$result		=	array(
				'status'	=>	$re_arr['status'],
				'msg'		=>	$re_arr['msg'],
			);
		}else{
			$unified_info=D('pointcard_unified')->where(array('unifiedcardid'=>$card,'siteid'=>SITEID))->find();

			if($unified_info){ 
				if($re_arr['pointid']){ 
					$card_my_info=D('pointcard')->where(array('id'=>$re_arr['pointid']))->find();
					$typename=$card_my_info['typename'];
					$amount=$card_my_info['amount'];
					$endtime=!empty($card_my_info['endtime']) ? date('Y-m-d H:i:s',$card_my_info['endtime']) : 0 ;
				}else{ 
					$card_info = D('pointcard')->where(array('unifiedcardid'=>$card,'siteid'=>SITEID,'userid'=>0))->find();
					if(!$card_info){ 
						$re_arr['status']	=	false;
						$msg="亲，该券已被枪光！";
						$result		=	array(
							'status'	=>	$re_arr['status'],
							'msg'		=>	$msg,
						);

					}else{ 
						$typename=$card_info['typename'];
						$amount = $card_info['amount'];
						$endtime = !empty($card_info['endtime']) ? date('Y-m-d H:i:s',$card_info['endtime']) : 0 ;
					}
					
				}

			}else{ 
				$card_info = D('pointcard')->where(array('cardid'=>$card,'siteid'=>SITEID))->find();
				$typename=$card_info['typename'];
				$amount = $card_info['amount'];
				$endtime = !empty($card_info['endtime']) ? date('Y-m-d H:i:s',$card_info['endtime']) : 0 ;
			}
			$result	=	array(
				'status'	=>	$re_arr['status'],
				'amount'	=>	$amount,
				'name'		=>	$typename,
				'endtime'	=>	$endtime,
			);	
		}
		return $result;
	}
	/*
    * 验证优惠券是否可用
	* $param $card varchar 我的优惠券卡号
	*/

	public function check_card($card,$type){
		$judge_arr = array();
		$card=strtoupper($card);
		$unified_info=D('pointcard_unified')->where(array('unifiedcardid'=>$card,'siteid'=>SITEID))->find();
		if($unified_info){ 		
			if($unified_info['leftnum']==0){ 
				$judge_arr['status'] = false;
				$judge_arr['msg'] = "亲，该券已被枪光！";
			}else{ 
				$card_info=D('pointcard')->where(array('unifiedcardid'=>$card,'siteid'=>SITEID,'cardtype'=>2,'userid'=>is_login()))->find();
				if($card_info){ 
					$judge_arr['pointid']=$card_info['id'];
				}else{ 
					//统一优惠券现在只存在cradtype为2 的代金券状态
					$card_info=D('pointcard')->where(array('unifiedcardid'=>$card,'siteid'=>SITEID,'cardtype'=>2,'userid'=>0,'endtime'=>array(array('gt',time()),array('eq',0),'or'),'mobile'=>array('eq',0)))->find();
					if(!$card_info){ 
						$judge_arr['status'] = false;
						$judge_arr['msg'] = "亲，该券已被枪光！";
					}
				}
			}	

		}else{ 
			$card_info = D('pointcard')->where(array('cardid'=>$card,'siteid'=>SITEID))->find();
			if(!$card_info){
				$judge_arr['status'] = false;
				$judge_arr['msg'] = "券码不存在，无法操作！";
			}
		}
		$typename = $card_info['typename'];
		if($card_info){
			if($card_info['cardtype'] == 1){
				$judge_arr['status'] = false;
				$judge_arr['msg'] = '代金券类型错误，无法操作！';
			}elseif($card_info['cardtype'] == 2){
				if($card_info['status'] == 3){
					$judge_arr['status'] = false;
					$judge_arr['msg'] = "该【".$typename."】已被使用，无法操作！";
				}elseif($card_info['status'] == 2){
					$judge_arr['status'] = false;
					$judge_arr['msg'] = "该【".$typename."】已被冻结，无法操作！";
				}elseif($card_info['status'] == -1){
					$judge_arr['status'] = false;
					$judge_arr['msg'] = "该【".$typename."】已被删除，无法操作！";
				}elseif($card_info['status'] == 0){
					$judge_arr['status'] = false;
					$judge_arr['msg'] = "该【".$typename."】已被禁用，无法操作！";
				}elseif($card_info['starttime'] != 0 && $card_info['endtime'] == 0){
					if($card_info['starttime'] - time() > 0){
						if($type){
							if($card_info['userid'] != 0){
								if($card_info['userid'] != is_login()){
									$judge_arr['status'] = false;
									$judge_arr['msg'] = "该【".$typename."】已被其他用户绑定，无法操作！";
								}else{
									$judge_arr['status'] = true;
								}
							}else{
								$judge_arr['status'] = true;
							}
						}else{
							$judge_arr['status'] = false;
							$judge_arr['msg'] = "该【".$typename."】未到使用时间，无法操作！";
						}						
					}else{
						$judge_arr['status'] = true;
					}
				}elseif($card_info['starttime'] != 0 && $card_info['endtime'] != 0){
					if($card_info['starttime'] - time() > 0){
						if($type){
							if($card_info['userid'] != 0){
								if($card_info['userid'] != is_login()){
									$judge_arr['status'] = false;
									$judge_arr['msg'] = "该【".$typename."】已被其他用户绑定，无法操作！";
								}else{
									$judge_arr['status'] = true;
								}
							}else{
								$judge_arr['status'] = true;
							}
						}else{
							$judge_arr['status'] = false;
							$judge_arr['msg'] = "该【".$typename."】未到使用时间，无法操作！";
						}						
					}else{
						if($card_info['endtime'] - time() < 0){
							$judge_arr['status'] = false;
							$judge_arr['msg'] = "该【".$typename."】已到期，无法操作！";
						}else{
							if($card_info['userid'] != 0){
								if($card_info['userid'] != is_login()){
									$judge_arr['status'] = false;
									$judge_arr['msg'] = "该【".$typename."】已被其他用户绑定，无法操作！";
								}else{
									$judge_arr['status'] = true;
								}
							}else{
								$judge_arr['status'] = true;
							}
						}
					}
				}elseif($card_info['starttime'] == 0 && $card_info['endtime'] != 0){
					if($card_info['endtime'] - time() < 0){
						$judge_arr['status'] = false;
						$judge_arr['msg'] = "该【".$typename."】已到期，无法操作！";
					}else{
						if($card_info['userid'] != 0){
							if($card_info['userid'] != is_login()){
								$judge_arr['status'] = false;
								$judge_arr['msg'] = "该【".$typename."】已被其他用户绑定，无法操作！";
							}else{
								$judge_arr['status'] = true;
							}
						}else{
							$judge_arr['status'] = true;
						}
					}
				}else{
					if($card_info['userid'] != 0){
						if($card_info['userid'] != is_login()){
							$judge_arr['status'] = false;
							$judge_arr['msg'] = "该【".$typename."】已被其他用户绑定，无法操作！";
						}else{
							$judge_arr['status'] = true;
						}
					}else{
						$judge_arr['status'] = true;
					}
				}
			}
		}	
		return $judge_arr;
	}
	/*订单成功后更改优惠券状态*/
	public function order_success_card($cardid='',$status=2){ 
		if($cardid!=''){ 
			$card_data['status']=$status;
			$card_data['userid'] = is_login();
			D('pointcard')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->save($card_data);
			$card_user = D('pointcard_user')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->find();
			if(!$card_user){
				$add_card_data['siteid'] = SITEID;
				$add_card_data['cardid'] = $cardid;
				$add_card_data['userid'] = is_login();
				$add_card_data['bindtime'] = time();
				$add_card_data['usetime'] = time();			
				D('pointcard_user')->add($add_card_data);
			}else{
				$update_card_data['usetime'] = time();
				D('pointcard_user')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->save($update_card_data);					
			}
			
			/**********写入日志表*************************/
			add_card_log($cardid,$card_data['status'],'提交商城订单-[使用]','[代金券/活动卡][使用/取消]');
		}
	}

} 
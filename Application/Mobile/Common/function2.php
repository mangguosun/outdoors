<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------


	/**
	 * 更新用户账户余额
	 * @param unknown_type $trade_sn
	 */
	function update_member_behavior($trade_sn) {
		$data = $userinfo = array();
		$orderinfo = get_userinfo_by_sn($trade_sn);//
		if($orderinfo){	
			if($orderinfo['order_type']==1){
				$event_attend = D('event_attend')->where(array('trade_sn'=>$orderinfo['order_id']))->find();
				if($event_attend['paytype']==0){
					$data['status'] = 21;
					$data['pay_status'] = 2;
				}elseif($event_attend['paytype']==1){
					if($event_attend['leftprice'] == 0){
						$data['status'] = 30;
						$data['pay_status'] = 2;
					}else{
						if($event_attend['pay_status']==0){
							$data['status'] = 11;
							$data['pay_status'] = 1;
						}elseif($event_attend['pay_status']==1){
							$data['status'] = 30;
							$data['pay_status'] = 2;							
						}
					}
				}
				if($event_attend['succ_trade_sn']){
					$data['succ_trade_sn'] = $event_attend['succ_trade_sn'].','.$trade_sn;
				}else{
					$data['succ_trade_sn'] = $trade_sn;
				}
				$data['in_trade_sn'] = '';
				M('event_attend')->where(array('trade_sn'=>$orderinfo['order_id']))->save($data);
				$list['order_status'] = $data['status'];
				D('event_signer')->where(array('siteid'=>SITEID,'order_id'=>$event_attend['id'],'status'=>1))->save($list);				
				/********更改优惠券状态并写入日志表**********/
				do_update_card($orderinfo['order_id']);
				/*2014-11-3 dlx--*/
				$websit_total   = $orderinfo['money'];//得到--money--
				$cash_record    = D('websit_cash_record');  //支付记录
				$cash_record->startTrans();
				
			/*2014-10-27 -dlx--*/
				$webdata['pay_status'] = $data['pay_status'];
				$webdata['status']     = $event_attend['status'];
				$webdata['total']      = $event_attend['payprice'];
				$webdata['leftprice']  = $event_attend['leftprice'];
				$webdata['uid']        = $event_attend['uid'];
				$webdata['time']       = time();
				$webdata['type']       = 1;//活动代表1，2代表商城
				$webdata['siteid']     = $event_attend['siteid'];
				$webdata['order_sn']   = $event_attend['trade_sn'];				
				$websit_log_add = D('websit_log')->data($webdata)->add();				
		        $websit_cash_find = $cash_record->where(array('siteid'=>$event_attend['siteid']))->find();
				    if($websit_cash_find){
						$websitdata['total']    = $websit_cash_find['total']   + $websit_total;//总额
						$websitdata['balance']  = $websit_cash_find['balance'] + $websit_total;//余额
						
						$cash_record_save = $cash_record->where(array('siteid'=>$event_attend['siteid']))->save($websitdata);

					     if($cash_record_save && $websit_log_add){
							   $cash_record->commit();
						   }else{
							   $cash_record->rollback();
						   }						
			        }else{
					    $websitdata['total']    =  $websit_total;//总额
						$websitdata['balance']  =  $websit_total;//余额
						$websitdata['siteid']   =  $event_attend['siteid'];
						$websitdata['status']   =  1;
						$cash_record_add = $cash_record->data($websitdata)->add();
					   
						   if($cash_record_add && $websit_log_add){
							   $cash_record->commit();
						   }else{
							   $cash_record->rollback();
						   }				
					}         				
				/************查询用户的用户名**********************/
				$user_id = $event_attend['uid'];
				$user_info = query_user(array('nickname'), $user_id);
				$user_name = $user_info['nickname'];
				/************查询用户的用户名**********************/
				
				/*************邮件发送*******************************************/
				$webinfo = json_decode(WEBSITEINFO,true);
				$event_info = D('event')->where(array('status'=>1,'siteid'=>SITEID,'id'=>$event_attend['event_id']))->find();
				$calendar_info = D('event_calendar_time')->where(array('siteid'=>SITEID,'id'=>$event_attend['calendar_id']))->find();
				$total_member = D('event_signer')->where(array('order_id'=>$event_attend['id'],'siteid'=>SITEID))->count();
				$title = '['.$webinfo['webname'].'] - 支付成功';
				$web_url = "http://".$_SERVER['HTTP_HOST'];
				$eventdata= array(
						'user_name'=>$user_name,
						'order_id' =>$orderinfo['order_id'],
						'event_title'=>$event_info['title'],
						'calendar_starttime'=>$calendar_info['starttime'],
						'total_member'=>$total_member,
						'webname'=>$webinfo['webname'],
						'web_slogan'=>$webinfo['slogan'],
						'totalprice'=>$event_attend['totalprice'],
						'deposit'=>$event_attend['deposit'],
						'leftprice'=>$event_attend['leftprice'],
						'web_url'=>$web_url,
						'web_telphone'=>$webinfo['telphone'],
					);
				if($event_attend['paytype'] == 0){
					
					$mess=all_type_send_message('mobile','pay_totalprice',$eventdata);
					addSendMessage($mess['msg'],'mobile_to_user','',0,0,'【活动】【全款】支付成功短信提醒',$event_attend['contact_telephone']);
							
					/*******************短信发送********************************************/
					//sms_alerts($event_attend['contact_telephone'],$mess['msg'],'【活动】【全款】支付成功短信提醒');
					/***********************************************************************/
				}else{
					if($event_attend['pay_status'] == 0){
						$mess=all_type_send_message('mobile','pay_deposit',$eventdata);
						addSendMessage($mess['msg'],'mobile_to_user','',0,0,'【活动】【定金】支付成功短信提醒',$event_attend['contact_telephone']);					
						/*******************短信发送********************************************/
						//sms_alerts($event_attend['contact_telephone'],$mess['msg'],'【活动】【定金】支付成功短信提醒');
						/***********************************************************************/
					}elseif($event_attend['pay_status'] == 1){	
						$mess=all_type_send_message('mobile','pay_leftprice',$eventdata);
						addSendMessage($mess['msg'],'mobile_to_user','',0,0,'【活动】【余额】支付成功短信提醒',$event_attend['contact_telephone']);
						/*******************短信发送********************************************/
						//sms_alerts($event_attend['contact_telephone'],$mess['msg'],'【活动】【余额】支付成功短信提醒');
						/***********************************************************************/
					}
				}
					/*邮箱发送*/
					addSendMessage($mess['message'],'email_to_user','',0,0,$title,$event_attend['contact_email']);
					addSendMessage($mess['notice'],'email_to_user','',0,0,$title,$webinfo['email']);
				//sendMail($event_attend['contact_email'],$title,$mess['message']);
				//sendMail($webinfo['email'],$title,$mess['notice']);
				
			}
		}
	}
	

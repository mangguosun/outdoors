<?php

namespace Websit\Controller;

use Think\Controller;

class RevenueController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize();    
	}
	public function index(){
     $stas=I('stas');
	  switch($stas){
	    case 0://银行卡信息
		    $account=D('websit_account_record')->where('siteid='.SITEID)->find();
			$this->assign('list',$account);
		break;
		case 1://申请提现
		    $balance=D('websit_cash_record')->where("siteid=".SITEID)->find();
		    $list=D('websit_account_record')->where("siteid=".SITEID)->select();
		    $k_balance=$balance['balance']-$balance['frozen'];
			
			$this->assign('balance',$balance);
			$this->assign('k_balance',$k_balance);//可用余额
			$this->assign('list',$list);
		break;
		case 2://提现记录
		    $status=I('status');
			if($status!=''){
			   $map['status']=$status;
			}
		       $map['siteid']=SITEID;
			   
			$count = D('websit_cashout_record')->where($map)->count();
			$Page       = new \Think\Page($count,10);
			$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
			$show       = $Page->show();// 分页显示输出  
           
		    $reds  = D('websit_cashout_record')->where($map)->order('time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		            foreach($reds as $key=>$val){
					    $cardinfo=json_decode($reds[$key]['cardinfo'],true);
						$reds[$key]['name']=$cardinfo['name'];
						$reds[$key]['card']=$cardinfo['card'];
						$reds[$key]['open_bank']=$cardinfo['open_bank'];
					}
					
			$this->assign('datas',$reds);			
		    $this->assign('page',$show);
		break;
		case 3://提现记录
		   $this->all_payment_records();
		break;
		
		
	  }
	 $this->assign('stas',$stas);
     $this->assign('user',$this->userdata);
     $this->display();
   }
   /*账务--支付记录-select*/
	public function all_payment_records(){
			$map['siteid']=SITEID;
			$count=D('pay_account')->where($map)->count();
			$Page       = new \Think\Page($count,10);
			$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
			$show       = $Page->show();// 
			$infos = D('pay_account')->where($map)->limit($Page->firstRow .','.$Page->listRows)->order('id desc')->select();
			if (is_array($infos) && !empty($infos)) {
				foreach($infos as $key=>&$info) {
					$list=D('event_attend')->where("siteid=".SITEID." and trade_sn= ".$info['order_id'])->find();
					if($info['order_paytype']==0){
						$infos[$key]['pay_text'] = '定金';
					}elseif($info['order_paytype']==1){
						$infos[$key]['pay_text'] = '余额';
					}elseif($info['order_paytype']==2){
						$infos[$key]['pay_text'] = '全额';
					}
				}
			}
        
		$this->assign('pay_account',$infos);
		$this->assign('page',$show);
	}
	/*添加网站提现帐号*/
 public function doCashAccount($name='',$card='',$open_bank=''){
        if(IS_POST){
		    $name=op_t(trim($name));
			$card=op_t(trim($card));
			$open_bank=op_t(trim($open_bank));
			
			if($name=='') $this->error('名字不能为空');
			if($card==''|| !is_numeric($card)) $this->error('请输入正确的卡号');
            if(!preg_match('/^\d{16}|\d{19}$/',$card)) $this->error('请输入正确的银行卡号');			
			if($open_bank=='') $this->error('请填写开户行');
			
			$accountFind=D('websit_account_record')->where('siteid='.SITEID)->find();
			    $data['name']      =   $name;
                $data['uid']       =   is_login();
                $data['siteid']	   =   SITEID;
                $data['open_bank'] =   $open_bank;
                $data['card']	   =   $card;
                $data['status']	   =   1;	
				
				if(!$accountFind){
				    $accountAdd=D('websit_account_record')->data($data)->add();
					if($accountAdd){
					    $this->success('添加成功');
					}else{
					    $this->error('添加失败');
					}
				   
				}else{
				    $account_save=D('websit_account_record')->where("siteid=".SITEID)->save($data);
					if($account_save){
					    $this->success('更新成功');
					}else{
					    $this->success('未更新数据');
					}
				  
				}
		
		
		}
	}
	/*申请提现*/
 public function doCashout($card='',$name='',$open_bank='',$cash=''){
     if(IS_POST){
	    $cash       = op_t(trim($cash));
		$card       = op_t(trim($card));
		$name       = op_t(trim($name));
		$open_bank  = op_t(trim($open_bank));
	    
		$account_cards=D('websit_account_record')->where('siteid='.SITEID)->find();
		if(!$account_cards) $this->error('请先绑定银行卡',U('Websit/my_income',array('stas'=>0)));
	    if($card =='') $this->error('请选择银行卡!');
		if($cash=='' || !is_numeric($cash)) $this->error('请输入正确的金额');
	
		$cash_record    = D('websit_cash_record');  //支付记录
		$cashout_record = D('websit_cashout_record'); //支出
		$cash_record->startTrans();
		
		$list=$cash_record->where("siteid=".SITEID)->find();//得到支付记录表信息
			if($list){
				
					if($cash>$list['balance']) $this->error('提现金额不能大于可用余额');
					if($cash<=0) $this->error('提现金额不能小于等于0 ');
					$data['cash']   = $cash; //提现金额-写入申请记录表--
					$cardinfo['card']     = $card;
					$cardinfo['name']     = $name;
					$cardinfo['open_bank']= $open_bank;
					$data['cardinfo']   = json_encode($cardinfo);
					
					$data['siteid'] = SITEID;
					$data['uid']    = is_login();
					$data['status'] = 1;
					$data['type']   = 1;
					$data['time']   = time();
					$data['flownumber']= create_sn();//流水单号
					$trade_sn = $data['flownumber'];

					$cash_save      = $cashout_record->data($data)->add();//--添加申请记录--
					
					$cate['frozen']  = $list['frozen']  + $cash; //--得到冻结的钱-- 因为多条记录  +加上 
					$cate['balance'] = $list['balance'] - $cash; //--余额做减法--
					$cate['status']  = 1;
					$cash_record_save=$cash_record->where('siteid='.SITEID)->save($cate);
						if($cash_record_save && $cash_save ){
							 D('RecordContent')->setuseprice_cashout($trade_sn);
							 $cash_record->commit();
							 $this->success('申请成功',U('Websit/Index/my_income',array('stas'=>2)));
						}else{
							D('RecordContent')->setuseprice_cashout($trade_sn);
							$cash_record->rollback();
							$this->error('申请失败');
						}
					
				
			}else{
				$this->error('申请失败');
			
			}
		
	}
 
 }
}  
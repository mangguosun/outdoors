<?php

namespace Manage\Controller;

/*收入管理-2015-1-16*/
class RevenueController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize();
		
       $this->websit_account_record = D('websit_account_record');
	   $this->category = D('category');
	   if(!is_admin()){ 
	   		$this->error('亲，没有权限啊',U('Manage/Index/index'));
	   }
	
	   
	}
	
    public function index($page = 1, $r = 20){
		
  	    $map = array('status' => array('egt',0),'siteid'=>SITEID);
        $list =  $this->websit_account_record->where($map)->find();
        $this->assign('list',$list);
        $this->display();
    }
	
	
	/***
	**添加/修改提现帐号**
	**/
    public function mention_account($name = '',$open_bank ='', $card = '')
    {
		$account_data = $this->websit_account_record->where(array('status'=>array('egt','0'),'siteid'=>SITEID))->find();
		$id = $account_data['id'];
        $isEdit = $id ? 1 : 0;
        if (IS_POST) {
            $name		=	op_t(trim($name));
			$card		=	op_t(trim($card));
			$open_bank	=	op_t(trim($open_bank));
			if($name=='') $this->error('名字不能为空');
			if($card==''|| !is_numeric($card)) $this->error('请输入正确的卡号');
            if(!preg_match('/^\d{16}|\d{19}$/',$card)) $this->error('请输入正确的银行卡号');			
			if($open_bank=='') $this->error('请填写开户行');
			$data['name'] = $name;
			$data['card'] = $card;
			$data['open_bank'] = $open_bank;
            if ($isEdit) {
			    $rs_content = $this->websit_account_record->where("id=".$id)->save($data);
            } else {
				 $data['siteid'] = SITEID;
				 $data['status'] = 1;
				 $data['uid']    = is_login();
				 $rs_content = $this->websit_account_record->add($data);
               
            }

      	    if ($rs_content) {
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Revenue/index'));
           	} else {
              	$this->error($isEdit ? '编辑失败' : '添加失败');
            }

        }else{ 

        	if($isEdit){ 
        		$datainfo = $account_data;
        	}
        	$datainfo['page_title'] = $isEdit ? '编辑提帐号' : '添加提现帐号';
        	$this->assign('datainfo',$datainfo);
		    $this->display();

        }
       
    }
	/*
	*提现申请**
	**/
	public function cashout($card_id=0,$cash=''){
		
		if(IS_POST){
		
			$cash 		= op_t(trim($cash));
		    //--验证卡号--
			switch($card_id){
				case -1:
				   $this->error('请先绑定提现帐号',U("Revenue/mention_account"));
				break;
				case '':
				   $this->error('请选择银行卡号');
				break;
			}
			
            if($cash<=0) $this->error('请输入正确的金额');
		    if (!preg_match('/^[0-9]+(.[0-9]{2})?$/', $cash)) {  
				$this->error('提现金额最多可两位小数点');  
			}
			$cash_record    = D('websit_cash_record');  //站点资金信息
			$cashout_record = D('websit_cashout_record'); //提现记录
			$cash_record->startTrans();
            $list	=	$cash_record->where("siteid=".SITEID)->find();//得到站点资金表信息
			
			if($list){
				  
					if($cash>$list['balance']) $this->error('提现金额不能大于可用余额');
					$cardfind	=	D('websit_account_record')->where(array('id'=>$card_id,'siteid'=>SITEID))->find();
					$cardinfo = array(
						'card'=>$cardfind['card'],
						'name'=>$cardfind['name'],
						'open_bank'=>$cardfind['open_bank'],
					
					);
				
					$data['cash']     = $cash; //提现金额-写入申请记录表--
				    $data['cardinfo']   = json_encode($cardinfo);
					$data['siteid'] = SITEID;
					$data['uid']    = is_login();
					$data['status'] = 1;
					$data['type']   = 1;
					$data['time']   = time();
					$data['flownumber']= create_sn();//流水单号
					$trade_sn = $data['flownumber'];//流水号（提现）
					$cash_save      = $cashout_record->add($data);//--添加申请记录--

					$cate['frozen']  = $list['frozen']  + $cash; //--得到冻结的钱-- 因为多条记录  +加上 
					$cate['balance'] = $list['balance'] - $cash; //--余额做减法--
					$cate['status']  = 1;
					
					$cash_record_save=$cash_record->where('siteid='.SITEID)->save($cate);

					if($cash_record_save && $cash_save ){
						 D('RecordContent')->setuseprice_cashout($trade_sn);//提现余额
						 $cash_record->commit();//提交事务

						 $this->success('申请成功',U('Manage/Revenue/cash_records'));
					}else{
						D('RecordContent')->setuseprice_cashout($trade_sn);
						$cash_record->rollback();//回滚事务
						$this->error('申请失败');
					}
					
				
			}else{
				$this->error('申请失败');
			
			}
		}else{
			
			$balance=D('websit_cash_record')->where("siteid=".SITEID)->find();
			$balance['total']=empty($balance['total'])?0:$balance['total'];
			$balance['balance']=empty($balance['balance'])?0:$balance['balance'];
			$balance['distribute_frozen']=empty($balance['distribute_frozen'])?0:$balance['distribute_frozen'];
			$balance['frozen']=empty($balance['frozen'])?0:$balance['frozen'];
		    $list=D('websit_account_record')->where("siteid=".SITEID)->select();
		    $k_balance=$balance['balance']-$balance['frozen'];
			
			$this->assign('balance',$balance);
			$this->assign('k_balance',$k_balance);//可用余额
			$this->assign('list',$list);
			$this->display();
		}
    }
   	/*
	*提现记录*
	*/	
	public function cash_records(){
		$map = array('status' => array('egt',0),'siteid'=>SITEID);
		$count=D('websit_cashout_record')->where($map)->count();
		$Page       = new \Think\Page($count,10);// 
		$show       = $Page->show(); 
		$reds  = D('websit_cashout_record')->where($map)->limit($Page->firstRow.','.$Page->listRows)->order('time desc')->select();
		foreach($reds as $key=>$val){
			$cardinfo=json_decode($reds[$key]['cardinfo'],true);
			$reds[$key]['name']=$cardinfo['name'];
			$reds[$key]['card']=$cardinfo['card'];
			$reds[$key]['open_bank']=$cardinfo['open_bank'];
			if($reds[$key]['time'] < 1425916799){ //1425916799 时间戳 是2015 30 09 23：59：59
				$reds[$key]['useprice'] = '--';
			}
			if($reds[$key]['status']==0){ 
				$reds[$key]['status']='拒绝打款';
			}elseif($reds[$key]['status']==1){ 
				$reds[$key]['status']='申请成功待打款';
			}elseif($reds[$key]['status']==2){ 
				$reds[$key]['status']='已打款';
			}
		}
		$this->assign('page',$show);
		$this->assign('list',$reds);
		$this->display();
	 }	
	/**
	*支付记录**
    **/
	public function payment_records(){
		    $order_id = I('order_id');
			if($order_id !=''){
				if (is_numeric($order_id)) {
					$map['id|order_id'] = array(intval($order_id), array('like', '%' . $order_id . '%'), '_multi' => true);
				} else {
					$map['order_id'] = array('like', '%' . (string)$order_id . '%');
				}
			}
			$map['siteid']=SITEID;
			$count=D('pay_account')->where($map)->count();
			$Page       = new \Think\Page($count,5);
			$show       = $Page->show();// 
			$infos = D('pay_account')->where($map)->limit($Page->firstRow .','.$Page->listRows)->order('id desc')->select();
			if (is_array($infos) && !empty($infos)) {
				foreach($infos as $key=>&$info) {
					//$list=D('event_attend')->where("siteid=".SITEID." and trade_sn= ".$info['order_id'])->find();
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
		$this->display();
	}

	//商城流水账
	public function websit_cash_log()
    {
    	$order_sn = I('order_sn');
		if($order_sn !=''){
			$map['order_sn'] = array('like', '%' . (string)$order_sn . '%');
			
		}
		$map['siteid']=SITEID;
		$count=D('websit_cash_log')->where($map)->count();
		$Page       = new \Think\Page($count,10);
		$show       = $Page->show();// 	
        $websit_cash_log	=	D('websit_cash_log')->where($map)->limit($Page->firstRow .','.$Page->listRows)->order('id desc')->select();
		$this->assign('websit_cash_log',$websit_cash_log);
		$this->assign('page',$show);
		$this->display();
		
    }

	

}  

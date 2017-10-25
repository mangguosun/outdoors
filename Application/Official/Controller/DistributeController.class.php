<?php

namespace Official\Controller;

use Think\Controller;

class DistributeController extends BaseController
{
	protected $userdata;
	protected $mTalkModel;
   function _initialize()
    {
		$this->userdata = query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $uid);
		$this->mTalkModel = D('Talk');
		$this->setTitle('个人中心');
    }
	public function index(){
		$tab	= $_GET['tab'];
		 switch($tab){
			case 3:
			$apply	=	D('shop_distribute_site_apply')->where('distribute_status=0')->order("apply_time desc")->select();
			foreach($apply as $key=>$val){
				$website	=	D('websit')->where('siteid='.$val['siteid'])->find();
				$apply[$key]['webname']	=	$website['webname'];
			}
			break;
			case 1:
			$apply	=	D('shop_distribute_site_apply')->where('distribute_status=1')->order("apply_time desc")->select();
			foreach($apply as $key=>$val){
				$website	=	D('websit')->where('siteid='.$val['siteid'])->find();
				$apply[$key]['webname']	=	$website['webname'];
			}
			break;
			case 4:
			$apply	=	D('shop_distribute_site_apply')->where('distribute_status=2')->order("apply_time desc")->select();
			foreach($apply as $key=>$val){
				$website	=	D('websit')->where('siteid='.$val['siteid'])->find();
				$apply[$key]['webname']	=	$website['webname'];
			}
			break;
			default:
			$site_ids	=	D('distribution_authority')->where('shop_authority=2')->select();
			foreach($site_ids as $key=>$val){
				$apply[$key]	=	D('shop_distribute_site_apply')->where('siteid='.$val['siteid'].' and distribute_status=2')->order("apply_time desc")->find();
				$website	=	D('websit')->where('siteid='.$val['siteid'])->find();
				$apply[$key]['webname']	=	$website['webname'];
				$apply[$key]['domain']	=	$website['domain'];
				$apply[$key]['shop_over_time']	=	$val['shop_over_time'];
				$apply[$key]['shop_authority']	=	$val['shop_authority'];
				$apply[$key]['siteid']	=	$val['siteid'];
			}
			$tab=2;
		}
		$this->assign('apply',$apply);
		$this->assign('tab',$tab);
		$this->assign('user',$this->userdata);
		$this->display();
	}
	

	public function apply_list(){
		$apply	=	D('shop_distribute_site_apply')->where($map)->order("apply_time desc")->select();
		foreach($apply as $key=>$val){
			$website	=	D('websit')->where('siteid='.$val['siteid'])->find();
			$apply[$key]['webname']	=	$website['webname'];
		}
		$this->assign('apply',$apply);
		$this->assign('user',$this->userdata);
		$this->display();
	}
	public function apply_detail($id=''){
		if(!$id){
			$this->error("参数错误");
		}
		$map['id']	=	$id;
		$apply_detail	=	D('shop_distribute_site_apply')->where($map)->find();
		if(!$apply_detail){
			$this->error('无该信息');
		}
		$webinfo	=	D('websit')->where('siteid='.$apply_detail['siteid'])->find();
		$bank	=	D('websit_account_record')->where('siteid='.$apply_detail['siteid'])->find();
		$supplier_agreement	=	D('shop_distribute_config')->where('name="SUPPLIER_AGREEMENT"')->getField('value');
		$this->assign('supplier_agreement',$supplier_agreement);
		$this->assign('bank',$bank);
		$this->assign('webinfo',$webinfo);
		$this->assign('apply_detail',$apply_detail);
		$this->assign('user',$this->userdata);
		$this->display();
	}
	public function distribute_site_examine($id="",$distribute_status="",$reply="",$commission=0){
		/************主判定部分***********************/
		if(!$id){
			$this->error("参数错误");
		}
		if($distribute_status==""){
			$this->error("请选择审批结果");
		}
		$shop_distribute_site_apply	=	D('shop_distribute_site_apply')->where('id='.$id)->find();
		$status	=	$shop_distribute_site_apply['distribute_status'];
		$siteid	=	$shop_distribute_site_apply['siteid'];
		$distribution_authority	=	D('distribution_authority')->where('siteid='.$siteid)->find();
		if(!$distribution_authority){	//权限列表无该商家，增加该商家空白记录
			$distribution_authority_arr	=	array(
				'siteid'	=>	$siteid,
				'shop_authority'	=>	0,
				'shop_over_time'	=>	'',
				'event_authority'	=>	0,
				'event_over_time'	=>	''
			);
			$add_distribution_authority	=	D('distribution_authority')->add($distribution_authority_arr);
			if(!$add_distribution_authority){
				$this->error("操作失败");
			}
			$distribution_authority	=	D('distribution_authority')->where('siteid='.$siteid)->find();
		}
		if($status!=1){
			$this->error("已审批过");
		}
		/************主判定部分结束***********************/
		/******************操作开始*************************/
		
			$examine_arr	=	array(
				'distribute_status'	=>	$distribute_status,
				'reply'				=>	$reply,
				'examine_time'		=>	time(),
				'commission'		=>	$commission,
				);
		if($distribute_status==2){
			$examine_arr['overtime']	=	strtotime("next year");
		}elseif($distribute_status==0){
			$examine_arr['overtime']	=	'';
		}
			//审批信息保存shop_distribute_site_apply
		$examine	=	D('shop_distribute_site_apply')->where('id='.$id)->save($examine_arr);
		if($examine){	//审批成功后，决定赋给该商户商品集市供货权限
			$distribution_authority_arr['shop_authority']	=	$distribute_status;
			if($distribute_status==2){
				$distribution_authority_arr['shop_over_time']	=	strtotime("next year");
				$mess	=	'您提交的入驻分销集市的申请单已经审核通过，可发布商品到分销集市中！';
			}elseif($distribute_status==0){
				$distribution_authority_arr['shop_over_time']	=	'';
				$mess	=	'您提交的入驻分销集市的申请单已被管理员驳回，请联系活动力平台管理员！';
			}
			$update_distribution_authority	=	D('distribution_authority')->where('siteid='.$siteid)->save($distribution_authority_arr);
		}
		$gm_id	=	D('websit')->where(array('siteid'=>$siteid))->getField('uid');
		D('Message')->sendMessageWithoutCheckSelf($gm_id,$mess ,'系统通知', U('/Manage/Distribute/index'),is_login());
		$this->success("审批成功",U('Official/Distribute/index'));
	}
	
	public function cancel_authority($siteid=''){
		if(!$siteid) $this->error('参数错误');
		
		/***************删除所有合作中的商品**********************/
		$remove_goods_relation	=	D('shop_distribute_item_relation')->where('supplier_id='.$siteid)->delete();
		
		/***************下架商品**********************/
		$remove_goods	=	D('shop_distribute_item')->where('siteid='.$siteid)->save(array('is_distribute'=>0));
		$shop_distribute_item	=	D('shop_distribute_item')->where('supplier_id='.$siteid)->field('goods_id')->select();
		$shop_distribute_site_relation	=	D('shop_distribute_site_relation')->where('supplier_id='.$siteid)->field('seller_id')->select();
		foreach($shop_distribute_site_relation as $val){
			foreach($shop_distribute_item as $v){
				D('Common/shop')->cc_goodsmap_s($val['seller_id'],$v['goods_id']);
			}
			D('Common/shop')->cc_goodsmap_s($val['seller_id']);
			D('Common/shop')->cc_goodsmap_s($val['seller_id'],'','other');
		}
		
		/***************取消所有合作**********************/
		$remove_site_relation	=	D('shop_distribute_site_relation')->where('supplier_id='.$siteid)->save(array('status'=>0));
		
		/***************更改权限表**********************/
		$cancel_authority	=	D('distribution_authority')->where('siteid='.$siteid)->save(array('shop_authority'=>0));
		
		
		/******************全站分销申请改状态**********************/
		$relation_a_apply_update	=	D('shop_distribute_relation_a_apply')->where('supplier_id='.$siteid)->save(array('examine_status'=> -1));
		
		/******************全站分销申请改状态**********************/
		$examine	=	D('shop_distribute_site_apply')->where('siteid='.$siteid)->save(array('distribute_status'	=>	0));
		if($cancel_authority) $this->success('取消成功');
		if(!$cancel_authority) $this->error('取消失败');
		
	}
	
}  

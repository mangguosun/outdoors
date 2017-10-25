<?php
namespace Common\Model;

/**
 * 生成多层树状下拉选框的工具模型
 */
class DataManageModel {
	/**
	*活动订单统计数量 2015-2-12 dlx pm
	*/
	public function eventOrderStat($day='yesterday'){
		switch($day){
			case 'yesterday'://昨日新增活动单数
				$day_start = strtotime(date('Y-m-d',strtotime('-1 day'))); 
                $day_end   = strtotime(date('Y-m-d'));
                $map = "siteid = ".SITEID." and status=21 and pay_status = 2 and status != -1 and status != 0 and creat_time between $day_start and $day_end";
              
			break;
			case 'sevenday'://这个星期活动单数
				$day_start = strtotime(date('Y-m-d',strtotime('-6 day'))); 
                $day_end   = strtotime(date('Y-m-d 23:59:59'));
                $map = "siteid = ".SITEID." and status != -1 and creat_time between $day_start and $day_end";
			break;
		}
		$list = D('event_attend')->where($map)->count();

		if($list){
			return $list;
		}else{
			return 0;
		}
	}
	/*
	*活动交易金额**
	**/
	public function eventOrderAmount($day='all',$travel=1){
		switch($day){
			case 'all':
				//--全部--
				$map = "siteid = ".SITEID." and status != -1 and status != 0 and pay_status >= 1";
				if($travel==1){//总额
					$res = D('event_attend')->where($map)->getField('payprice',true);
					$list = array_sum($res);
				}else{
					//出行人数-
					$res = D('event_attend')->where($map)->getField('id',true);
					foreach($res as $v){
						$new_res[] = get_signnum($v);
					}
					$list = array_sum($new_res);
				}
            break;
        }
		if($list){
			return $list;
		}else{
			return 0;
		}
		
	}
	/**
	*待处理订单数量
	**/
	public function eventDepositOrder($type='halfpay'){
		switch($type){
			case 'order_total': //-订单总数-
				$map = "siteid = ".SITEID." and status>=0";
            break;
			case 'halfpay': //--订金待确认--
				$map = "siteid = ".SITEID." and status = 11 and pay_status = 1 and paytype = 1 and status != -1 and status != 0";
            break;
			case 'succ':   //-全额待支付-
				$map = "siteid = ".SITEID." and status=21 and pay_status = 2 and status != -1 and status != 0";
            break;
			
		}
		$list = D('event_attend')->where($map)->count();
		if($list){
			return $list;
		}else{
			return 0;
		}
	
	
    }
	
	
	/*
	*发布活动总数
	**/
	public function eventNums($day='all'){
		switch($day){
			case 'all':
				$list = D('event')->where(array('status' => array('egt',0),'siteid'=>SITEID))->count();
			break;
		}
		if($list){
			return $list;
		}else{
			return 0;
		}
		
	}
	
	/**
	*商品订单数量统计* 2015-2-12 dlx pm
	*/
	public function shopOrderStat($day='yesterday',$type=1){
		switch($day){
            case 'yesterday'://昨天
				$day_start = strtotime(date('Y-m-d',strtotime('-1 day'))); 
                $day_end   = strtotime(date('Y-m-d'));
				$list = D('shop_ordersn')->where(array('siteid'=>SITEID,'status'=>21,'create_time'=>array('between',array($day_start,$day_end))))->count();
            break;
			case 'sevenday'://7天内新增商品订单数量
				$day_start = strtotime(date('Y-m-d',strtotime('-6 day'))); 
                $day_end   = strtotime(date('Y-m-d 23:59:59'));
				$list = D('shop_ordersn')->where(array('siteid'=>SITEID,'status'=>21,'create_time'=>array('between',array($day_start,$day_end))))->count();
			break;
			case 'all'://全部
			    if($type==1){
				   $list = D('shop_ordersn')->where(array('siteid'=>SITEID,'status'=>21))->count();
				}elseif($type==2){//--得到总交易额-
				  $rs = D('shop_ordersn')->where(array('siteid'=>SITEID,'status'=>21))->getField('alltotalprice',true);
				  $list = array_sum($rs);
                }
            break;
			case 'order_all':
			    $list = D('shop_ordersn')->where(array('siteid'=>SITEID))->count();
			break;
			case 'processed':
			    $list = D('shop_ordersn')->where(array('siteid'=>SITEID,'status'=>21))->count();
			break;
			
			
		}
	    if($list){
			return $list;
        }else{
			return 0;
		}
    }
	/*
	*商品总数**
	***/
	public function shopNums($day='all'){
		switch($day){
			case 'all'://所有发布的商品总数-
				$list = D('shop')->where(array('siteid'=>SITEID,'status'=>array('egt',0)))->count();
            break;
			
		}
		if($list){
			return $list;
		}else{
			return 0;
		}
	}
	
	/*
	*当前定制订单 2015-2-10 dlx pm 
	**/
	public function customOrder($days='day'){
		   $map['siteid'] = SITEID;
		switch($days){
			case 'day': //-当天-
				$day_start = strtotime(date('Y-m-d')); 
                $day_end   = strtotime(date('Y-m-d',strtotime('+1 day')));
				$map['createtime'] = array('between',array($day_start,$day_end));
			break;
			case 'month'://--当月--
				$BeginDate = date('Y-m-01', strtotime(date("Y-m-d")));
				$day_start = strtotime($BeginDate);
				$day_end   = strtotime(date('Y-m-d', strtotime("$BeginDate +1 month -1 day")));
				$map['createtime'] = array('between',array($day_start,$day_end));
			break;
			case 'all':
			break;
			
		}
		
		$count = D('event_tailor')->where($map)->count();
		

		if($count>0){
		  return $count;
		}else{
			return 0;
		}
		
		
		
	}
	/*
	**收益**2015-2-12
	**/
	public function cashRecord($type='balance'){
		$map['siteid'] = SITEID;
		$rs = D('websit_cash_record')->where($map)->find();
		switch($type){
			case 'balance':
				$list = $rs['balance'];
			break;
			case 'total':
				$list = $rs['total'];
			break;
		}
		if($list){
			return $list;
		}else{
			return 0;
		}
		
	}
   /**
	*会员数量统计 2015-2-12 dlx pm
	***/
	public function MemberNums($is_use='yesterday'){
			$map['status'] = array('egt',0);
			$map['siteid'] = SITEID;
		switch($is_use){
            case 'yesterday':
				$day_start = strtotime(date('Y-m-d',strtotime('-1 day'))); 
				$day_end   = strtotime(date('Y-m-d'));
				$map['reg_time']=array('between'=>array($day_start,$day_end));
			break;
			case 'sevenday':
			    $day_start = strtotime(date('Y-m-d',strtotime('-6 day'))); 
                $day_end   = strtotime(date('Y-m-d 23:59:59'));
				$map['reg_time']=array('between'=>array($day_start,$day_end));
			break;
			case 'all':
			break;
			case 2:$map['is_use'] = 2;break; //官方领队
			case 4:$map['is_use'] = 4;break; //达人
        }
		$list = D('member')->where($map)->count();
		if($list>0){
			return $list;
		}else{
			return 0;
		}
	}
	
	/*
	*得到评论总数*
	*/
	public function commentNums($day='day'){
		$map['siteid'] = SITEID;
		$map['status'] = array('egt',0);
		switch($day){
			case 'day': //-当天-
				$day_start = strtotime(date('Y-m-d')); 
                $day_end   = strtotime(date('Y-m-d',strtotime('+1 day')));
				$map['create_time'] = array('between',array($day_start,$day_end));
			break;
			case 'month'://--当月--
				$BeginDate = date('Y-m-01', strtotime(date("Y-m-d")));
				$day_start = strtotime($BeginDate);
				$day_end   = strtotime(date('Y-m-d', strtotime("$BeginDate +1 month -1 day")));
				$map['create_time'] = array('between',array($day_start,$day_end));
			break;
			case 'all':
			break;
			
		}
        $list = D('local_comment')->where($map)->count();
		
		if($list){
			return $list;
		}else{
			return 0;
		}
	}

}
?>

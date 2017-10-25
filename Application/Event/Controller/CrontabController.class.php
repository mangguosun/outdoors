<?php
namespace Event\Controller;
set_time_limit(0);
use Think\Controller;

class CrontabController extends Controller
{
	public $siteid_arr = array();
	public function _initialize()
    {
        $siteid_arr = D('websit')->where(array('status' => 1))->field('siteid')->select();	
		$this->siteid_arr = $siteid_arr;
    }
	/*计划任务更新*/
	public function do_crontab(){
		foreach($this->siteid_arr as $key => $val){
			update_event($val['siteid']);
			$this->card_update($val['siteid']);
			
		}
		$data = $this->event_order_update();
	}
	public function card_update($siteid){
		$card_arr = D('pointcard')->where(array('siteid'=>$siteid))->select();
		foreach($card_arr as $key => $val){
			if(!empty($val['endtime'])){				
				if($val['endtime'] > time()){
					switch($val['status']){
						case -1;
							$diff_time = $val['endtime'] + time() * 7;
						break;
						case 0;
							$diff_time = $val['endtime'] + time() * 6;
						break;
						case 1;
							$diff_time = $val['endtime'] - time();
						break;
						case 2;
							$diff_time = $val['endtime'] + time();
						break;
						case 3;
							$diff_time = $val['endtime'] + time() * 2;
						break;
					}
				}else{
					switch($val['status']){
						case -1;
							$diff_time = $val['endtime'] + time() * 9;
						break;
						case 0;
							$diff_time = $val['endtime'] + time() * 8;
						break;
						case 1;
							$diff_time = $val['endtime'] + time() * 3;
						break;
						case 2;
							$diff_time = $val['endtime'] + time() * 4;
						break;
						case 3;
							$diff_time = $val['endtime'] + time() * 5;
						break;
					}
				}				
			}else{			
				$diff_time = $val['createtime'];				
			}	
			$pointcard_user = D('pointcard_user')->where(array('siteid'=>$siteid,'cardid'=>$val['cardid']))->select();
			if($pointcard_user){
				foreach($pointcard_user as $k => $v){
					$data[$k]['diff_time'] = $diff_time;
					D('pointcard_user')->where(array('cardid'=>$v['cardid'],'siteid'=>$siteid))->save($data[$k]);
				}
			}
			
		}
	}
	public function insurance_update(){	
		/*$event_arr = D('event')->select();
		foreach($event_arr as $key => $val){
			$insurance_arr[$key] = D('insurance')->where(array('status'=>1,'siteid'=>$val['siteid']))->find();
			$data[$key]['insurance'] = $insurance_arr[$key]['id'];
			D('event')->where(array('siteid'=>$val['siteid'],'id'=>$val['id']))->save($data[$key]);
		}*/
		exit('ok');
	}

		//活动订单完成修改
	function event_order_update(){ 
		//$map['status']  = array('in','11,12,21,30,31,32');
		$map['status']  = array('in','21,30,31,32');
		$map['pay_status'] = 2;
		$map['ordertype'] = 1;

		$event_attend = D('event_attend')->field('id,event_id,calendar_id')->where($map)->order('id desc')->select();
		if($event_attend == ''){ 
			return false;
		}
		foreach ($event_attend as $key => $value) {
			$map_over['id'] = $value['calendar_id'];
			$map_over['eventid'] = $value['event_id'];
			$time = D('event_calendar_time')->field('starttime,overtime')->where($map_over)->find();
			if($time == ''){ 
				$data['status'] = 33;
				$mapsave['id'] = $value['id'];
				D('event_attend')->where($mapsave)->save($data);
			}else{ 

				$timeover = $time['overtime'].' 23:59:59';
				$timestat = $time['starttime'].' 00:00:00';
				$timeover = strtotime($timeover);
				$timestat = strtotime($timestat);
				$nowtime = time();
				if($nowtime < $timestat ){ 
					continue;
				}elseif( ($nowtime > $timestat) && ($nowtime < $timeover) ){
					$data['status'] = 31;
					$mapsave['id'] = $value['id'];
					$ddd_dd['a'][] = $value['id'];
					D('event_attend')->where($mapsave)->save($data);	
				}elseif($nowtime > $timeover){ 
					$data['status'] = 33;
					$mapsave['id'] = $value['id'];
					D('event_attend')->where($mapsave)->save($data);
				}
			}
		}
		return true;
		
	}


	//商城订单过期修改
	public function shop_order_update($siteid){
		$shop_order_arr = D('shop_ordersn')->where(array('siteid'=>$siteid))->select();
		foreach($shop_order_arr as $key => $val){
			if(!empty($val['create_time'])){				
				if(time() > $val['create_time']+1800){
					switch($val['status']){
						case 20;
							$order_sn = $val['order_sn'];
							$data['status']=2;
							$save_shop_order = D('shop_ordersn')->where(array('order_sn'=>$order_sn,'siteid'=>$siteid,'status'=>20))->save($data);
							
							/***************补全未交易库存*************************/
							$goods_list = D('shop_order_info')->where(array('order_sn'=>$order_sn,'siteid'=>SITEID))->select();
							foreach($goods_list as $k => $v){
								D('shop')->where(array('id'=>$v['goods_id'],'siteid'=>SITEID))->setDec('sell_num',$v['goods_num']);
								D('shop')->where(array('id'=>$v['goods_id'],'siteid'=>SITEID))->setInc('goods_num',$v['goods_num']);
								if(!empty($v['sku_id'])){
									D('shop_sku_detailed')->where(array('sku_id'=>$v['sku_id'],'goods_id'=>$v['goods_id'],'siteid'=>SITEID))->setInc('stock',$v['goods_num']);
								}		
							}
							/**************补全未交易库存结束**************/
						break;
					}
				}
			}	
		}
	}
}
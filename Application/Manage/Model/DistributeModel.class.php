<?php

namespace Manage\Model;

use Think\Model;

class DistributeModel extends Model {
	public function goodsdistributemap($category_id=''){ 	//查询商品条件
		
		if($goods_id){
			$goodsmap['id'] = $goods_id;
		}
		$distribute_item_smap['is_distribute'] = 1;
		$distribute_item_smap['distribute_type_b'] = 1;
		if($category_id){
			$distribute_category = D('shop_category')->where(array('status' => array('gt', -1),'is_distribute' => 1,'pid'=>$category_id))->order('sort')->field('id')->select();
			$distribute_category_ids	=	$category_id;
			foreach($distribute_category as $k=>$v){	//将分销产品条条件加入查询的条件中
				if(!$distribute_category_ids){$distribute_category_ids=$v['id'];}
				else{
					$distribute_category_ids=$distribute_category_ids.",".$v['id'];
				}
			}
			unset($k);unset($v);
			$distribute_item_smap['distribute_category_id'] = array('in',$distribute_category_ids);
		}

		$distribute_item_goods_ids	=	D('shop_distribute_item')->where($distribute_item_smap)->field('goods_id')->select();
		
		foreach($distribute_item_goods_ids as $k=>$v){	//将分销产品条条件加入查询的条件中
			if(!$distribute_goods_ids){$distribute_goods_ids=$v['goods_id'];}
			else{
				$distribute_goods_ids=$distribute_goods_ids.",".$v['goods_id'];
			}
		}
		if($distribute_goods_ids){	//若存在分销商品将分销产品条条件加入查询的条件中
			$goodsmap['id']	=	array('in',$distribute_goods_ids);
		}
		return $goodsmap;
	}
	
	
	public function shopdetail($id=''){ 	//商品信息
		$shop_list = D('shop')->where("id=".$id)->find();
		if($id=='' || !$shop_list){
			$url=U('Manage/Distribute/goods');
			header("location:$url");
		}
		$shop_distribute_item	=	D('shop_distribute_item')->where('goods_id='.$id)->find();
		
		$shop_detail	=	D('Common/shop')->shop_sku_detailed($id,'edit');
		/**************/
		if($shop_list['shop_brand'] != 0){
			$brand	=	D('shop_brand_manage')->where('id='.$shop_list['shop_brand'])->field('name,englist_name')->find();
		   $shop_list['custom_brand_name'] =	$brand['name'];
		   $shop_list['englist_name']	=	$brand['englist_name'];
		}

		$shop_list['market_price']	=	D('Common/shop')->sku_ids_price($_GET['id']);
		/*********************/
		if ($shop_list['goods_pictures_id']) {
			$pictures = M("Picture")->field('id,path')->where("id in ({$shop_list['goods_pictures_id']})")->select();
			foreach ($pictures as &$img) {
				$img['path'] = fixAttachUrl($img['path']);
			}
			unset($img);
			$result['pictures']			=	$pictures;
		}
		
		$result['shop_distribute_item']	=	$shop_distribute_item;
		$result['shop_detail']			=	$shop_detail;
		$result['shop_list']			=	$shop_list;
		return $result;
	}
	
	
	public function has_item_relation($id='',$supplier_id,$seller_id){ 	//查询是否代理商品
		$map['goods_id']	=	$id;
		$map['supplier_id']	=	$supplier_id;
		$map['seller_id']	=	$seller_id;

		$has_item_relation	=	D('shop_distribute_item_relation')->where($map)->find();
		return $has_item_relation;
	}
	
	public function shop_address($address){ 	//查询商家地址
		$shop_address['community']	=	get_city($address['community']);
		$shop_address['district']	=	get_city($address['district']);
		$shop_address['city']		=	get_city($address['city']);
		$shop_address['province']	=	get_city($address['province']);
		
		return $shop_address;
	}
	
	public function get_distribute_website($siteid){ 	//查询商家地址
		$website	=	D('websit')->where('siteid='.$siteid)->find();
		$address = get_citys($website['club_address']);	//换算地区
		$website['shop_address']	=	$this->shop_address($address);//换算地区
		return $website;
	}
	
	
	
} 
<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 想天
 * 创建日期: 3/13/14
 * 创建时间: 7:41 PM
 * 版权所有 想天工作室(www.ourstu.com)
 */

namespace Manage\Model;

use Think\Model;

class ShopModel extends Model {
	public function goodsdistributemap($category_id=''){ 	//查询商品条件
		
		if($goods_id){
			$goodsmap['id'] = $goods_id;
		}
		$distribute_item_smap['is_distribute'] = 1;
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
} 
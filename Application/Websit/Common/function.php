<?php

function shop_cate_info($id, $field = true){
        /* 获取分类信息 */
        $map = array();
        if(is_numeric($id)){ //通过ID查询
            $map['id'] = $id;
        } else { //通过标识查询
            $map['name'] = $id;
        }
        return D('shop_category')->field($field)->where($map)->find();
}

function get_shop_cate_tree($id = 0, $field = true){
		if($id){     /* 获取当前分类信息 */
		$info = shop_cate_info($id);
		$id   = $info['id'];
		}

		/* 获取所有分类 */
		$map  = array('status' => array('gt', -1),'siteid' => SITEID);
		$list = D('shop_category')->field($field)->where($map)->order('sort')->select();
		$list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_', $root = $id);
		/* 获取返回数据 */
		if(isset($info)){ //指定分类则返回当前分类极其子分类
		$info['_'] = $list;
		} else { //否则返回所有分类
		$info = $list;
		}

		return $info;
}




/**
*得到商品类目2014-12-27
*/
function get_shop_sku_category($find=''){
    $map['status']=1;
	$sku_cate=D('shop_sku_category')->where($map)->select();	
	$str_arr=array();
	if($sku_cate){
		foreach($sku_cate as $val){
			$str_arr[$val['sku_category_id']]=$val['title'];
			
		}
	}
    if($find){
		return $str_arr[$find];
		
	}else{
		return $str_arr;
	}
	
}

/*
*得到商品分类*** 2014-12-27 am dlx
*/
function get_shop_category($find=''){
	$tree =get_shop_cate_tree(0, 'id,title,sort,pid,status');
	$array_info=array();
	if($tree){
		foreach($tree as $value){
		    $array_info[$value['id']]  =  $value['title']; //---一级分类---
			if($value[_]){
				foreach($value[_] as $val){
				   $array_info[$val['id']] = "&nbsp;&nbsp;&nbsp;&nbsp;".$val['title'];  //---二级分类---
					if($val[_]){
						foreach($val[_] as $v){
						  $array_info[$v['id']] ="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$v['title'];  //--三级分类---
						   
						}
					}
				}
				
			}
		
		}
		
	}
	if($find){
		return $array_info[$find];
	}else{
		return $array_info;
	}
}
/*
*得到商品分类名称***2014-12-29 dlx am
**/
function get_shop_categrory_names($id){
	$list = D('shop_category')->where("id=".$id)->find();
	if($list){
		return $list['title'];
	}else{
		return '暂无分类';
	}
}
/**
*选择品牌
**/
function get_shop_brand($find=''){
	$list = D('shop_brand_manage')->select();
	if($list){
		foreach($list as $key=>$val){
			$str_arr[$list[$key]['id']]=$list[$key]['name'];
		}
	}
	
	if($find){
            return $str_arr[$find];
		}else{
			return $str_arr;
			
		}
	
	
}
/*验证三级分类*/
function get_category_level($pid){
	$list = get_shop_cate_tree(0,true);
	foreach($list as $val){    //-一级--
		foreach($val[_] as $v){  // --二级--
			foreach($v[_] as $temp){ //--三级--
				if($temp['id'] == $pid){
				   return true;
				}
			}
		}

	}
	
}

function return_order_status($status){
	$order_status_arr = array();
	$order_status_arr = array(
		array('status'=>1,'text'=>'报名预约，等待空位'),
		array('status'=>10,'text'=>'下单成功，待定金支付'),
		array('status'=>11,'text'=>'定金已支付，待确认出行'),
		array('status'=>12,'text'=>'已确认出行，待余款支付'),
		array('status'=>20,'text'=>'下单成功，待全款支付'),
		array('status'=>21,'text'=>'支付成功，待确认出行'),
		array('status'=>30,'text'=>'待出行签到'),
		array('status'=>31,'text'=>'活动进行中'),
		array('status'=>32,'text'=>'活动结束，等待评论'),
		array('status'=>33,'text'=>'订单完成'),
		array('status'=>60,'text'=>'退款申请中'),
		array('status'=>61,'text'=>'退款完成')
	);
	$order_status_temp = array();
	foreach($order_status_arr as $key => $val){
		$order_status_temp[$val['status']] = $val['text'];
	}
	if($status){
		return $order_status_temp[$status];
	}else{
		return $order_status_arr;
	}
}


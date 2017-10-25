<?php


namespace Shop\Controller;

use Think\Controller;

class ShopcartController extends Controller
{
	//如果未登录，检测是否已有$_SESSION['cart']
    public function __cunstruct() {
		if(!is_login()){
			if(!isset($_SESSION['cart'])){
				$_SESSION['cart'] = array();
			}
		}else{
			if(!isset($_SESSION['pay'])){
				$_SESSION['pay'] = '';//生成随机数，用来判断后续生成跳转页操作是否一部完成，如果$_SESSION['pay']不存在就创建一个
			}
		}
    }
	//购物车列表，未登录读取session，登录后读取数据库
	public function shopcartitem(){
		if(!is_login()){
			$arr=$_SESSION['cart'];
			foreach($arr as $key => &$value){
				$goods_id	= $value['goods_id'];
				$sku_id		= $value['sku_id'];
				/**************************************/
				$data = D('Common/shop')->compgoods($goods_id,$sku_id);
				
				$value['goods_name']	= $data['goods']['goods_name'];
				$value['goods_ico']		= $data['goods']['goods_ico'];
				$value['num']			= $value['goods_num'];
				//若商品下架删除该商品
		
				if(!$data['goods'] || $data['error'] || $data['shop_goods_stock'] == 0 ){
					unset($_SESSION['cart'][$goods_id.'|'.$sku_id]);
				}
				
				if($value['num'] > $data['shop_goods_stock']){
					$_SESSION['cart'][$goods_id.'|'.$sku_id]['goods_num']	=	$data['shop_goods_stock'];
					$value['num']	=	$data['shop_goods_stock'];
				}
				$value['price']	=	$data['shop_goods_price'];
				$shop_bargain =	D('shop_bargain')->where('goods_id='.$goods_id.' and overtime>'.time().' and  starttime<'.time().' and surplus_num>0')->find();
				if($shop_bargain){
					$value['price'] = $shop_bargain['bargain_price'];
				}
				
				/*********************/
				/****商品属性*****/
				$sku_info = D('shop_sku_detailed')->where("goods_id=".$goods_id.' and sku_id='.$value['sku_id'])->getField("sku_id,sku_title,price,stock");
				foreach($sku_info as $tp=>$temp){
					$sku_info[$tp]['sku_title'] = explode(",",$sku_info[$tp]['sku_title']); 
				}
				foreach($sku_info as $kk=>$vas){
					if($sku_info[$kk]['sku_id']==$sku_id){
						foreach($sku_info[$kk]['sku_title'] as  $tnp=>$ttp){
						   $arr[$key]['sku_title'][] = get_shop_sku_types_name($ttp,2).":".get_shop_sku_types_attribute($ttp);
						}						
					}
				}
				unset($key);
				unset($val);
				/************商品属性结束***************/
			}
		}else{
			//登录后的购物车
			$uid=is_login();
			$siteid=SITEID;
			if($_SESSION['pay']==''){
				$_SESSION['pay']=create_sn();  //生成随机数，用来判断后续生成跳转页操作是否一部完成，如果$_SESSION['pay']为空就生成一个
			}	
			//$pay=$_SESSION['pay'];
			//dump($pay);
			$arr	=	D('shop_cart')->where('uid='.$uid.' and siteid='.$siteid)->select();
			foreach($arr as $key => &$value){
				$goods_id	= $value['goods_id'];
				$sku_id		= $value['sku_id'];
				/**************************************/
				$data 		= D('Common/shop')->compgoods($goods_id,$sku_id);
				/**************************************/
				$value['goods_name']	= $data['goods']['goods_name'];
				$value['goods_ico']		= $data['goods']['goods_ico'];
				
				//若商品下架删除该商品
				if(!$data['goods'] || $data['error'] || $data['shop_goods_stock'] == 0){
					$res=D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id. ' and siteid='.$siteid)->delete(); 
				}
				//若商品数量超过最大值，改变最大值
				if($value['num'] > $data['shop_goods_stock']){
					$res	=	D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id. ' and siteid='.$siteid)->save(array('num'=>$data['shop_goods_stock'])); 
					$value['num']	= $data['shop_goods_stock'];
				}
				$value['price']	=	$data['shop_goods_price'];
				
				$shop_bargain =	D('shop_bargain')->where('goods_id='.$goods_id.' and overtime>'.time().' and  starttime<'.time().' and surplus_num>0')->find();
				if($shop_bargain){
					$value['price'] = $shop_bargain['bargain_price'];
				}
				
				/****商品属性*****/
				$sku_info = D('shop_sku_detailed')->where("goods_id=".$goods_id.' and sku_id='.$value['sku_id'])->getField("sku_id,sku_title,price,stock");
				foreach($sku_info as $tp=>$temp){
					$sku_info[$tp]['sku_title'] = explode(",",$sku_info[$tp]['sku_title']); 
				}
				
				
				foreach($sku_info as $kk=>$vas){
					if($sku_info[$kk]['sku_id']==$sku_id){
						foreach($sku_info[$kk]['sku_title'] as  $tnp=>$ttp){
						   $arr[$key]['sku_title'][] = get_shop_sku_types_name($ttp,2).":".get_shop_sku_types_attribute($ttp);
						}						
					}
				}
				unset($key);
				unset($val);
			}
		}
		$this->assign('cart',$arr);
		$this->display();			
	}
 
 
 
    /*
    添加商品，未登录，改SESSION。若已登录更改数据库
		$goods_id 商品主键
          $sku_id 商品skuid
          $goods_num 购物数量
    */
    public  function additem($goods_id,$sku_id='',$goods_num=''){
		
		if(!$goods_id) $this->error('请选择相关商品');
		if(!$goods_num) $this->error('最少要选择一个数量');
		
		$data = D('Common/shop')->compgoods($goods_id,$sku_id);
		
		if(!$data['goods']){
			$this->error('商品不存在或已被删除');
		}
		
		if($data['error']) $this->error($data['error']);
		if($data['shop_goods_stock'] == 0 ) $this->error('本商品已经无库存，请选择其它商品');
		if($goods_num > $data['shop_goods_stock']) $this->error('你的商品数量大于了本配置的库存,本商品配置物品只有'.$data['shop_goods_stock'].'件了');
		
		
        //如果未登录，写入session
		if(!is_login()){
		//如果该商品已存在则直接加其数量
			if($sku_id==''){
				$sku_id=0;
			}
			if(isset($_SESSION['cart'][$goods_id.'|'.$sku_id])){
				$x	=	$_SESSION['cart'][$goods_id.'|'.$sku_id]['goods_num'];
				$newnum	=	$x+$goods_num;
				$_SESSION['cart'][$goods_id.'|'.$sku_id]['goods_num']	=	$newnum;
			}else{				//否则商品不存在创建商品
				$item = array();
				$item['goods_id']	= $goods_id;
				$item['sku_id']		= $sku_id;
				$item['goods_num']	= $goods_num;
				$item['siteid']		= SITEID;
				$_SESSION['cart'][$goods_id.'|'.$sku_id] = $item; 
			}
			
			$arr=$_SESSION['cart'];
			foreach($arr as $key => &$value){
				$goods_id	= $value['goods_id'];
				$sku_id		= $value['sku_id'];
				/**************************************/
				$data = D('Common/shop')->compgoods($goods_id,$sku_id);
				$value['num']			= $value['goods_num'];
				//若商品下架删除该商品
				if(!$data['goods'] || $data['error'] || $data['shop_goods_stock'] == 0 ){
					unset($_SESSION['cart'][$goods_id.'|'.$sku_id]);
				}
				
				if($value['num'] > $data['shop_goods_stock']){
					$_SESSION['cart'][$goods_id.'|'.$sku_id]['goods_num']	=	$data['shop_goods_stock'];
					$value['num']	=	$data['shop_goods_stock'];
				}
			}
			unset($key);
			unset($val);
			
			
			$arr	=	$_SESSION['cart'];
			$item_num	=	0;
		
			foreach($arr as $value){
				$cart_num	=	$cart_num+$value['goods_num'];
				$goods_id	=	$value['goods_id'];
				$sku_id		=	$value['sku_id'];
				/**************************************/
				$data = D('Common/shop')->compgoods($goods_id,$sku_id);
				/************************************/
				$value['price']	=	$data['shop_goods_price'];

				$cart_price		=	$cart_price+$value['price']*$value['goods_num'];
				$item_num++;
			}
			
			$cart['item_num']	= $item_num;
			$cart['allnum']	= $cart_num;
			$cart['cart_price']	= $cart_price;
			exit(json_encode(array('status'=>1,'info'=>'添加成功','datainfo'=>$cart)));
		}else{
			$uid	=	is_login();
			$siteid	=	SITEID;
			if($sku_id==''){
				$sku_id=0;
			}
			//判断是否已存在所加入商品
			$exists	=	D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id. ' and siteid='.$siteid)->count();
			//如果该商品已存在则直接加其数量
			if($exists){
				$list	=	D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id.' and siteid='.$siteid)->find();
				$x		=	$list['num'];
				//计算某物品加入购物车总数
				$newnum	=	$x+$goods_num;

				$data['num']	=	$newnum;
				
				$result	=	D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id. ' and siteid='.$siteid)->save($data);
			}else{
				$data['goods_id']	=	$goods_id;
				$data['sku_id']		=	$sku_id;
				$data['num']		=	$goods_num;
				$data['uid']		=	$uid;
				$data['siteid']		=	$siteid;
				$list	=	D('shop_cart')->create($data);
				$result	=	D('shop_cart')->add();
			}
			if($result){
				$arr = D('shop_cart')->where(array('uid' => is_login(),'siteid'=>SITEID))->select();
				foreach($arr as $key => &$value){
					$goods_id	= $value['goods_id'];
					$sku_id		= $value['sku_id'];
					/**************************************/
					$data 		= D('Common/shop')->compgoods($goods_id,$sku_id);
					/**************************************/					
					//若商品下架删除该商品
					if(!$data['goods'] || $data['error'] || $data['shop_goods_stock'] == 0){
						$res=D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id. ' and siteid='.$siteid)->delete(); 
					}
					//若商品数量超过最大值，改变最大值
					if($value['num'] > $data['shop_goods_stock']){
						$res	=	D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id. ' and siteid='.$siteid)->save(array('num'=>$data['shop_goods_stock'])); 
						$value['num']	= $data['shop_goods_stock'];
					}
				}
				unset($key);unset($value);
				$arr = D('shop_cart')->where(array('uid' => is_login(),'siteid'=>SITEID))->select();
				$item_num=0;
				foreach($arr as $value){
					$cart_num=$cart_num+$value['num'];
					$goods_id= $value['goods_id'];
					$sku_id= $value['sku_id'];
					
					/**************************************/
					$data = D('Common/shop')->compgoods($goods_id,$sku_id);
					/************************************/
					$value['price']	=	$data['shop_goods_price'];
					/*********************/
					$cart_price		=	$cart_price+$value['price']*$value['num'];
					$item_num++;
				}
				$cart['item_num']	= $item_num;
				$cart['allnum']	= $cart_num;
				$cart['cart_price']	= $cart_price;
				echo json_encode(array('status'=>1,'info'=>'添加成功','datainfo'=>$cart));
			}else{
			   $this->error('未更改数据');
			}
		}
    }
 
 
	
	 // 直接输入改变数量
	public function changeNum($goods_id,$sku_id,$goods_num){
		if(!$goods_id) $this->error('请选择相关商品');
		$data = D('Common/shop')->compgoods($goods_id,$sku_id);
		if(!$data['goods']){
			$this->error('商品不存在或已被删除');
		}
		
		if($data['error']) $this->error($data['error']);
		if($data['shop_goods_stock'] == 0 ) $this->error('本商品已经无库存，请选择其它商品');
		if($goods_num > $data['shop_goods_stock']) $this->error('你的商品数量大于了本配置的库存,本商品配置物品只有'.$data['shop_goods_stock'].'件了');
		if($goods_num <= 0) $this->error('最少要选择一个数量');
		if($num<1){
				$num=1;
			}
		if(!is_login()){
			if (!isset($_SESSION['cart'][$goods_id."|".$sku_id])) {
				return false;
			}
			$_SESSION['cart'][$goods_id."|".$sku_id]['goods_num'] = $goods_num;
			$this->success('更改成功');
		
		}else{

			$uid=is_login();
			$siteid=SITEID;
			
			$data['num']=$goods_num;
			
			$cate=D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id. ' and siteid='.$siteid)->save($data);
			if($cate){
			   $this->success('更改成功');
			}else{
			   $this->success('未更改数据');
			}
		}
	}
	
 
   
 
    // 删除商品
    public function delItem($goods_id,$sku_id) {
		if(!is_login()){
			unset($_SESSION['cart'][$goods_id.'|'.$sku_id]);
			$this->success();
		}
		else{
				$uid=is_login();
				$siteid=SITEID;
				$res=D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id. ' and siteid='.$siteid)->delete(); 
				if($res){
				   $this->success('操作成功');
					}else{
					   $this->error('操作失败');
					}
		}
    }

 
    //清空购物车

    public function clear() {
		if(!is_login()){
			$_SESSION['cart'] = array();
			 $this->success('操作成功');
		
		}else{
			$res = D('shop_cart')->where('uid='.is_login().' and siteid='.SITEID)->delete(); 
		    if($res){
				echo json_encode(array('status'=>1,'info'=>'操作成功'));
			}else{
				echo json_encode(array('status'=>0,'info'=>'操作失败'));
			}
		
		}
	
	}
		
	//删除购物车内选中商品
	public function delselectitem() {
	    
		$goodssku_ids = $_POST['ids'];
	    if(!is_login()){
			foreach($goodssku_ids as $key=>$val){
				$goodssku_id[$key]=$goodssku_ids[$key];
	            unset($_SESSION['cart'][$goodssku_id[$key]]);
			
			}
			
			if(!array_key_exists($goodssku_id[0],$_SESSION['cart'])){
				exit(json_encode(array('status'=>1,'info'=>'删除成功')));
			}else{
				exit(json_encode(array('status'=>0,'info'=>'删除失败')));
			}
		}else{
			
			foreach($goodssku_ids as $v){
				$goods=explode("|",$v);
				$res=D('shop_cart')->where('uid='.is_login().' and goods_id='.$goods[0].' and sku_id='.$goods[1]. ' and siteid='.SITEID)->delete(); 			
			}
		
			if($res){
				exit(json_encode(array('status'=>1,'info'=>'删除成功')));
			}else{
				exit(json_encode(array('status'=>0,'info'=>'删除失败')));
			}
		}
	}
}
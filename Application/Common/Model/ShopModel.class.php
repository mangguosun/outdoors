<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 想天
 * 创建日期: 3/13/14
 * 创建时间: 7:41 PM
 * 版权所有 想天工作室(www.ourstu.com)
 */

namespace Common\Model;

use Think\Model;

class ShopModel extends Model {
   /* protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('status', '1', self::MODEL_INSERT),
    );*/
	
	/**获取商品数据
	 * @param $uid 用户ID
	 * @return mixed
	 */
	protected $goods_info = 'id,goods_name,goods_ico,goods_pictures_id,goods_introduct,tox_money_need,market_price,goods_num,changetime,status,createtime,category_id,is_new,sell_num';
	
	
	private function _lists_detail($id){

		$content  = S('shoplist_goods_'.$id);

		if(empty($content)){
			
			$v = D('shop')->where(array('id' =>$id))->find();

			$v['user'] = query_user(array('id', 'nickname', 'mobile_space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
			$v['category'] = D('shop_category')->field('id,title')->where(array('id'=>$v['category_id'],'siteid'=>SITEID))->find();
			$v['pc_url'] =U('Shop/index/goodsDetail',array('id'=>$v['id']));
			$v['mobile_url'] =U('Mobile/Shop/goodsDetail',array('id'=>$v['id']));
			
			//商品缩略图
			$v['thumb_path'] =get_cover($v['goods_ico'],'path');
			$v['thumb_200_200'] =getThumbImageById($v['goods_ico'],200,200);
			$v['thumb_400_400'] =getThumbImageById($v['goods_ico'],400,400);
			$v['thumb_600_600'] =getThumbImageById($v['goods_ico'],600,600);
			//商品图片组
			if ($v['goods_pictures_id']) {
				$pictures = M("Picture")->field('id,path')->where("id in ({$v['goods_pictures_id']})")->select();
				foreach ($pictures as &$img) {
					$img['pictures_path'] = fixAttachUrl($img['path']);
					$img['pictures_200_200'] =getThumbImageById($img['id'],200,200);
					$img['pictures_400_400'] =getThumbImageById($img['id'],400,400);
					$img['pictures_600_600'] =getThumbImageById($img['id'],600,600);
				}
				unset($img);
				$v['pictures']=$pictures;
			}
			
			//品牌
			if($v['shop_brand']){
				$shop_brand = D('shop_brand_manage')->where("id=".$v['shop_brand'])->find();
				if($shop_brand){
					$v['shop_brand'] = $shop_brand;
				}
			}elseif(!$v['shop_brand']){
				$v['shop_brand']['name']	= $v['custom_brand_name'];
				$v['shop_brand']['englist_name']	= $v['custom_brand_enname'];
			}
			//标签
			if($v['tag']){
				$shop_tag_arr = explode(',',$v['tag']);
				foreach ($shop_tag_arr as $k => $t) {
					$tags[$t]['id'] = $t;
					$tags[$t]['name'] = get_event_tag($t);
				}
				if($tags){
					$v['tags'] = $tags;
				}
			}
			/*评论数*/
			$v['comment_count'] = D('local_comment')->where(array('app'=>'Shop','row_id'=>$v['id'],'siteid'=>SITEID))->count();
			/*收藏数*/
			$v['collect_count'] = D('forum_bookmark')->where(array('shop_id'=>$v['id'],'siteid'=>SITEID))->count();
	
			/*商品SKU-价格更新*/
			$v['market_price']	=	$this->sku_ids_price($v['id']);
			$content = $v;
			unset($v);
			S("shoplist_goods_".$id, $content, 1800);
		}
		return $content;
	}	
	
	
	
	
	private function _lists($where='',$order='id desc',$limit=10,$start=0){
		
		$content = D('shop')->where($where)->order($order)->limit($start,$limit)->select();
		foreach ($content as $key => &$v) {
		   $content_new[$key] = $this->_lists_detail($v['id']);
		}
		return $content_new;
	}
	
	/**获取全部没有提示过的消息
	 * @param $uid 用户ID
	 * @return mixed
	 */
	/*微信公众号的类型
	$type 类型
	*/
	public function get_lists($where_type,$limit,$start=0,$order=''){
		
		//$map = "status = 1 and siteid=".SITEID;
		$map	=	$this->goodsmap();
		switch ($where_type)
		{
		//推荐数据
		case 'recommend':
			//$map .= " and is_recommend = 1";
			$map['is_recommend']	=	1;
			$order='sort';
		break;
		//特价数据
		case 'discount':
			//$map .= " and is_bargains = 1";
			$map['is_bargains']	=	1;
			$order='sort';
		break;
		//最新数据
		case 'new':
			//$map .= " and is_new = 1";
			$map['is_new']	=	1;
			$order='sort';
			
			$keywords = trim(op_t(I('keywords')));
			if($keywords != ''){
				$map['goods_name']	=	array('like','%'.$keywords.'%');
			}
		break;
		//热卖单品
		case 'fire':
			//$map .= " and is_new = 1";
			$map['is_firey']	=	1;
			$order='sort';
			
			$keywords = trim(op_t(I('keywords')));
			if($keywords != ''){
				$map['goods_name']	=	array('like','%'.$keywords.'%');
			}
		break;
		case 'bargain':
		  $map['id']	=	array('in',$this->limit_bargains_map());
		  $order='sort';
		  break;
		default:
		 	$map = $where_type;
		}
		$content = $this->_lists($map,$order,$limit,$start);
		return $content;
		
	}

	public function goodsmap($goods_id='',$category_id='',$delS=false){ 	//查询商品条件
		$shop_goodsmap_keys = 'shop_goodsmap_'.SITEID.'-'.$goods_id.'-'.$category_id;

		if($delS){ 
			S($shop_goodsmap_keys,null);
			return false;
		}
		$goodsmapS = S($shop_goodsmap_keys);
		if($goodsmapS){ 
			/*dump(s);dump($goodsmapS);*/
			return $goodsmapS;
		}
		/**************************/
		if($goods_id){
			$goodsmap['id'] = $goods_id;
		}
		$goodsmap['status'] = 1;
		//$goodsmap['purchase_status'] = 1;

		$distribute_item_goods_ids	=	D('shop_distribute_item_relation')->where('seller_id='.SITEID.' and apply_status=1')->field('goods_id')->select();//查询自己分销的产品
		foreach($distribute_item_goods_ids as $k=>$v){	//将分销产品条条件加入查询的条件中
			if(!$distribute_goods_ids){$distribute_goods_ids=$v['goods_id'];}
			else{
				$distribute_goods_ids=$distribute_goods_ids.",".$v['goods_id'];
			}
		}
		if($distribute_goods_ids){	//若存在分销商品将分销产品条条件加入查询的条件中
			$where['siteid'] = SITEID;
			$where['_logic'] = 'or';
			$where['id']	=	array('in',$distribute_goods_ids);
			$goodsmap['_complex'] = $where;
		}else{
			$goodsmap['siteid'] = SITEID;	//不存在分销商品时
		}
		
		if(!$category_id){
			$goodsmap = $goodsmap;
		}elseif($category_id && $category_id!='other'){
			$goods_category = D('shopCategory')->find($category_id);
			if ($category_id) {
				$category_id = intval($category_id);
				$goods_categorys = D('shop_category')->where("id=%d OR pid=%d", array($category_id, $category_id))->limit(999)->select();
				$ids = array();
				foreach ($goods_categorys as $v) {
					$ids[] = $v['id'];
				}
				$goodsmap['category_id']	=	array('in',implode(',', $ids));
			}
		}elseif($category_id=='other'){
			unset($goodsmap);
			$goodsmap['status']	=	1;
			$goodsmap['id']	=	array('in',$distribute_goods_ids);
		}
		/*dump(g);dump($goodsmap);*/
		S($shop_goodsmap_keys,$goodsmap,3600);
		return $goodsmap;
	}
	
	public function cc_goodsmap_s($siteid,$goods_id='',$category_id=''){
		$shop_goodsmap_keys = 'shop_goodsmap_'.$siteid.'-'.$goods_id.'-'.$category_id;
		S($shop_goodsmap_keys,null);
	}
	/**
    * 限时特价查询条件
    */
    public function limit_bargains_map(){
		$map	=	$this->goodsmap();
		
		$map['overtime']	=	array('gt',time());
		$map['starttime']	=	array('lt',time());
		$map['surplus_num']	=	array('gt',0);
		if($map['_complex']['id']){
			$map['_complex']['goods_id']	=	$map['_complex']['id'];
			unset($map['_complex']['id']);
		}
		unset($map['status']);
		
		$bargain = D('shop_bargain')->where($map)->select();


		foreach($bargain as $key => $v) {
			if(!$limit_bargains_ids){
				$limit_bargains_ids	=	$v['goods_id'];
			}else{
				$limit_bargains_ids	=	$limit_bargains_ids.",".$v['goods_id'];
			}
		}
		return $limit_bargains_ids;
    }
	
	public function compgoods($goods_id='',$sku_id=''){ 	//验证商品信息

		$goodsmap	=	$this->goodsmap($goods_id);
		
        $goods = D('shop')->where($goodsmap)->find();
		$sku_info = D('shop_sku_detailed')->where("goods_id=".$goods_id)->getField("sku_id,sku_title,price,stock");
		if($sku_info){
			if(!$sku_id){
				$result['error']= '请选对相关商品配置';
			}
			if(!$sku_info[$sku_id]){
				$result['error']= '商品配置请重试或联系'.get_upgrading(3);
			} 
			$shop_goods_price = $sku_info[$sku_id]['price'];
			$shop_goods_stock = $sku_info[$sku_id]['stock'];
			
		}else{
			$shop_goods_price = $goods['market_price'];
			$shop_goods_stock = $goods['goods_num'];
		}
		$result	=	array(
			'shop_goods_price'	=>	$shop_goods_price,
			'shop_goods_stock'	=>	$shop_goods_stock,
			'goods'				=>	$goods,
		);
		
		return $result;
	}
	
	
	public function goods_freight($goods_id='',$goods_num=0){ 	//计算邮费
		$goods	= D('shop')->where('id='.$goods_id)->field('fr_freight,fr_num,fr_addnum,fr_money,fr_id')->find();
		$freight	=	$goods['fr_freight'];	//首次邮费
		$fr_num		=	$goods['fr_num'];		//首次件数
		$fr_addnum	=	$goods['fr_addnum'];	//追加件数
		$fr_money	=	$goods['fr_money'];		//追加件数
		$fr_id		=	$goods['fr_id'];		//是否包邮
		if($fr_id==0){
			if($goods_num>$fr_num){
				$fr_addnum=($goods_num-$fr_num)/$fr_addnum;
				$goods_freight	= 	$freight+($fr_addnum)*$fr_money;
			}else{	
				$goods_freight	= 	$freight;
			}
		}else{
			$goods_freight		='0';
		}
		return $goods_freight;
	}
	
	public function sku_ids_price($goods_id,$delS=false){ 	//计算商品展示价格
		$shop_skuidsprice_keys = 'shop_skuidsprice_'.SITEID.'-'.$goods_id;

		if($delS){ 
			S($shop_skuidsprice_keys,null);
			return false;
		}
		$skuidspriceS = S($shop_skuidsprice_keys);
		if($skuidspriceS){ 
			/*dump(s);dump($skuidspriceS);*/
			return $skuidspriceS;
		}
		$market_price	=	D('shop')->where('id='.$goods_id)->getField('market_price');
		$sku_ids		= D('shop_sku_detailed')->where('goods_id='.$goods_id)->field('sku_id')->select();
		if($sku_ids){
			
			$sku_price = D('shop_sku_detailed')->where('goods_id='.$goods_id)->field('price')->select();

				$max_price_val = array_search(max($sku_price), $sku_price);
				$min_price_val = array_search(min($sku_price), $sku_price);
				$max_price = $sku_price[$max_price_val]['price'];
				$min_price = $sku_price[$min_price_val]['price'];
			if($max_price!=$min_price){
				$market_price	= $min_price.'~'.$max_price;
			}else{
				$market_price	= $min_price;
			}
		}
		/*dump(g);dump($market_price);*/
		S($shop_skuidsprice_keys,$market_price,3600);
		return $market_price;
	}
	
	public function shop_bargain($goods_id){ 	//计算限时特价信息
		$shop_bargain =	D('shop_bargain')->where('goods_id='.$goods_id.' and overtime>'.time().' and  starttime<'.time().' and surplus_num>0')->find();
		if($shop_bargain){
			$result['bargain_price'] = $shop_bargain['bargain_price'];
			$result['overtime']		= $shop_bargain['overtime'];
		}
		return $result;
	}
	
	public function shop_detail($goods_id){ 	//商品信息
		$shop_list		=	D('shop')->where("id=".$goods_id)->find();
		if ($shop_list['goods_pictures_id']) {
			$pictures = M("Picture")->field('id,path')->where("id in ({$shop_list['goods_pictures_id']})")->select();
			foreach ($pictures as &$img) {
				$img['path'] = fixAttachUrl($img['path']);
			}
			unset($img);
		}

		return $shop_list;
	}
	
	public function shop_sku_detailed($goods_id,$action){ 	//商品SKU
		switch ($action)
		{
			case 'edit':
				$shop_sku_detailed	=	D('shop_sku_detailed')->where('goods_id='.$goods_id)->select();
				
				foreach($shop_sku_detailed as $key=>&$val){
					$val['sku_title']=json_decode($val['sku_title'],true);
					foreach($shop_sku_detailed[$key]['sku_title'] as $k=>$v){
						   $cods[$key] .= $v['value'].",";
					}
					$shop_sku_detailed[$key]['sku_title'] = rtrim($cods[$key],",");
					$shop_sku_detailed[$key]['sku_title'] = explode(",",$shop_sku_detailed[$key]['sku_title']);
					$shop_sku_detailed[$key]['sort']	=	D('shop_sku_types_attribute_stystem')->where('attribute_id='.$v['value'])->getField('sort');
				}
				$list = D('shop_sku_types_system')->where("is_system=1")->order("sort desc")->select();
				foreach($list as $key=>$value){
					$list[$key]['arr']	=	D('shop_sku_types_attribute_stystem')->where("sku_types_id=".$list[$key]['sku_types_id'])->select();
				}
			break;
			case 'show':
				$shop_sku_detailed	=	D('shop_sku_detailed')->where('goods_id='.$goods_id)->select();
				
				foreach($shop_sku_detailed as $key=>&$val){
					$val['sku_title']=json_decode($val['sku_title'],true);
					foreach($shop_sku_detailed[$key]['sku_title'] as $k=>$v){
						   $cods[$key] .= $v['value'].",";
					}
					$shop_sku_detailed[$key]['sku_title'] = rtrim($cods[$key],",");
					$shop_sku_detailed[$key]['sku_title'] = explode(",",$shop_sku_detailed[$key]['sku_title']);
				}
				$list = D('shop_sku_types_system')->where("is_system=1")->order("sort desc")->select();
				foreach($list as $key=>$value){
					$list[$key]['arr']	=	D('shop_sku_types_attribute_stystem')->where("sku_types_id=".$list[$key]['sku_types_id'])->select();
				}
			break;
		}
		return $shop_sku_detailed;
	}
	
	public function shop_order_detail($order_sn,$user){ 	//商品订单
		switch ($user)
		{
			case 'user':
				$map['order_sn']	=	$order_sn;
				$map['siteid']		=	SITEID;
				$map['uid']	=	is_login();
			break;
			case 'admin':
				$map['order_sn']	=	$order_sn;
				$map['_string'] = 'siteid='.SITEID.' OR supplier_id='.SITEID;
			break;
		}
		$order_info		=	D('shop_ordersn')->where($map)->find();
		$goods_list		=	D('shop_order_info')->where($map)->select();
		/*******优惠券信息*********/
		$card_info		=	D('pointcard')->where(array('cardid'=>$order_info['cardid'],'siteid'=>SITEID))->find();
		$typeinfo = D('pointcard_type')->where(array('ptypeid'=>$card_info['ptypeid'],'siteid'=>SITEID))->find();
		$amount			=	$card_info['amount'];
		$endtime		= 	!empty($card_info['endtime']) ? date('Y-m-d H:i:s',$card_info['endtime']) : 0 ;
		$order_info['amount']	=	$amount;
		$order_info['cardname']	=	$card_info['typename'];
		/*******优惠券信息结束*********/
		/**************计算商品总价和总数*******************/
		foreach($goods_list as $key=>$value){
			$price	=	$value['goods_price'];
			$num	=	$value['goods_num'];
			$alltotalprice	=	$alltotalprice+$price*$num;
			$allgoodsnum	=	$allgoodsnum+$num;
			$fr_freight		=	$value['freight'];
			if(!$fr_freight){$fr_freight	=	0;}
			$allfreight		=	$allfreight+$fr_freight;
			$goods_list[$key]['freight']	=	$fr_freight;
		}
		$order_info['allfreight']		=	$allfreight;
		$order_info['totalcostprice']	=	$alltotalprice;
		$order_info['allgoodsnum']		=	$allgoodsnum;
		/**************计算商品总价和总数结束*******************/
		$order_info['begincity']		=	get_citys($order_info['consignee_address']);
		$order_info['goods_list']		=	$goods_list;
		return $order_info;
	}
	
	public function shop_order_refund($order_sn){ 	//商品退款信息
		$refund_list = D('shop_order_refund')->where(array('order_sn'=>$order_sn))->select();
		foreach($refund_list as $key=>$value){
			$refund_goods	=	D('shop_order_info')->where('id='.$value['shop_order_info_id'])->field('goods_name,goods_desc,goods_ico')->find();
			$refund_list[$key]['goods_name']	=	$refund_goods['goods_name'];
			$refund_list[$key]['goods_desc']	=	$refund_goods['goods_desc'];
			$refund_list[$key]['goods_ico']		=	$refund_goods['goods_ico'];
		}
		return $refund_list;
	}

	//购物车
	public function shop_cart_session(){ 
    	$uid=is_login();
		$siteid=SITEID;
		if(isset($_SESSION['cart'])){
			foreach($_SESSION['cart'] as $k=>$v){
				$d['uid'] = $uid;
				$d['goods_id'] = $_SESSION['cart'][$k]['goods_id'];
				$d['sku_id'] = $_SESSION['cart'][$k]['sku_id'];
				$d['num'] = $_SESSION['cart'][$k]['goods_num'];
				$d['siteid'] = $siteid;
				//判断是否已存在所加入商品
				$goods_id=$d['goods_id'];
				$sku_id=$d['sku_id'];
				$exists=D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id. ' and siteid='.$siteid)->count();
				//如果该商品已存在则直接加其数量
				if($exists){
					$list=D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id.  ' and siteid='.$siteid)->find();
					//dump($list);
					$x=$list['num'];
					$newnum=$x+$d['num'];
					//echo "$newnum";
					$data['num']=$newnum;		
					$cate=D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id.  ' and siteid='.$siteid)->save($data);
				}else{
					//如果该商品已存在直接加入购物车
					$cart=D('shop_cart')->add($d);
				
				}
			}
			//如果登录成功删除$_SESSION['cart']
			$_SESSION['cart'] = array();
		}

    }
	
	
	function screening($field = '',$diyarr = array(),$is_diy = 0,$isall = 1) {

		$options = $diyarr;
		$field_value = $_GET[$field];
		foreach($options as $key => $_k) {
			
			if($is_diy){
				$v = explode("|",$_k);
				$k = trim($v[1]);
				$option[$k]['name'] = $v[0];
				$option[$k]['value'] = $k;
				$option[$k]['url'] = structure_filters_url($field,array($field=>$k),2);
				$option[$k]['menu'] = $field_value == $k ? '<span class="btn btn-primary">'.$v[0].'</span>' : '<a class="btn" href='.$option[$k]['url'].'>'.$v[0].'</a>';	
			}else{
				$option[$key]['url'] = structure_filters_url($field,array($field=>$key),2);
				$option[$key]['menu'] = $field_value == $key ? '<dd><a class="seled">'.$_k.'</a></dd>' : '<dd><a href='.$option[$key]['url'].'>'.$_k.'</a></dd>';	
			}
		}
		if ($isall) {
			$all['name'] = '全部';
			$all['url'] = structure_filters_url($field,array($field=>''),2);
			$all['menu'] = $field_value == '' ? '<dd><a class="seled">'.$all['name'].'</a></dd>' : '<dd><a href='.$all['url'].'>'.$all['name'].'</a></dd>';
			array_unshift($option,$all);
		}

		return $option;
	}

	/**
	*选择品牌筛选
	**/
	function get_shop_brand($delS=false){
		
		$shop_getshopbrand_keys = 'shop_getshopbrand_'.SITEID;

		if($delS){ 
			S($shop_getshopbrand_keys,null);
			return false;
		}
		$shopgetshopbrandS = S($shop_getshopbrand_keys);
		if($shopgetshopbrandS){ 
			/*dump(s);
			dump($shopgetshopbrandS);*/
			return $shopgetshopbrandS;
		}
		/**************************/		
		
		$goodsmap	=	$this->goodsmap();
		$list_custom = D('shop')->where($goodsmap)->field('custom_brand_name,shop_brand,tag')->select();
		if($list_custom){
			foreach($list_custom as $key=>$val){
				if($list_custom[$key]['custom_brand_name']!=""){
					$str_arr[0][urlsafe_b64encode($list_custom[$key]['custom_brand_name'])]=$val['custom_brand_name'];
				}else{
					unset($list_custom[$key]);
				}
				if(!$shop_brand_ids){
					$shop_brand_ids	=	$val['shop_brand'];
				}else{
					$shop_brand_ids	=	$shop_brand_ids.",".$val['shop_brand'];
				}
				if(!$tag_ids){
					$tag_ids	=	$val['tag'];
				}else{
					$tag_ids	=	$tag_ids.",".$val['tag'];
				}
			}
		}
		unset($key);unset($val);

		$list = D('shop_brand_manage')->where(array('id'=>array('in',$shop_brand_ids)))->field('name')->select();

		if($list){
			foreach($list as $key=>$val){
				$str_arr[0][urlsafe_b64encode($list[$key]['name'])]=$list[$key]['name'];
			}
		}
		unset($key);unset($val);
		
		$list_custom[]	=	$list;
		
		$tag_list = D('tags')->where(array('id'=>array('in',$tag_ids)))->select();
		foreach($tag_list as $key=>$val){
			$str_arr[1][$val['id']]=	$val['title'];
		}
		unset($key);unset($val);
		S($shop_getshopbrand_keys,$str_arr,3600);
		/*dump(g);
		dump($str_arr);*/
		return $str_arr;

	}
	
	public function index_module($limit=5,$action=''){
		$shop_indexmodule_keys = 'shop_indexmodule_'.SITEID.'-'.$action;

		$shopindexmodulekeysS = S($shop_indexmodule_keys);
		if($shopindexmodulekeysS['Sstatus']){ 
			/*dump(s);
			dump($shopindexmodulekeysS);*/
			return $shopindexmodulekeysS['items'];
		}
		$map	=	D('Common/shop')->goodsmap();
		$order = 'sort';
		switch($action){
			case 'new_products':	//新品
				$map['is_new']	=	1;
			break;
			case 'recommend':	//推荐
				$map['is_recommend']	=	1;
			break;
			case 'bargains':	//特价
				$map['is_bargains']	=	1;
			break;
			case 'fiery':	//热销
				$map['is_firey']	=	1;
			break;
		}

		$info	=	D('shop')->where($map)->order($order)->limit($limit)->field('id,goods_name,goods_ico,market_price,shop_brand,custom_brand_name,tox_money_need')->select();
		
		foreach ($info as $k => $v) {
            if($v['shop_brand'] != 0){
                $info[$k]['custom_brand_name']=D('shop_brand_manage')->where('id='.$v['shop_brand'])->getField('name');
		
			}
			$info[$k]['market_price']	=	D('Common/shop')->sku_ids_price($info[$k]['id']);
		}
		$data['Sstatus']	=	1;
		$data['items']	=	$info;
		S($shop_indexmodule_keys,$data,3600);
		/*dump(g);
		dump($info);*/
		return $info;
	}
	
	public function clear_index_module_s($siteid=SITEID){
		S('shop_indexmodule_'.$siteid.'-new_products',null);
		S('shop_indexmodule_'.$siteid.'-recommend',null);
		S('shop_indexmodule_'.$siteid.'-bargains',null);
		S('shop_indexmodule_'.$siteid.'-fiery',null);
	}

} 
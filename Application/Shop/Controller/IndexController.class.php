<?php



namespace Shop\Controller;

use Think\Controller;

/**
 * Class IndexController
 * @package Shop\Controller
 * @郑钟良
 */
class IndexController extends Controller
{
    protected $goods_info = 'id,goods_name,goods_ico,goods_introduct,tox_money_need,market_price,goods_num,changetime,status,createtime,category_id,is_new,sell_num';

    /**
     * 商城初始化
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function _initialize()
    {
		
		$model_info = get_appinfo('Shop');
		if(!$model_info){
			$this->error('参数错误或没有找该应用');
		}
		
		
		
        $tree = D('shopCategory')->getTree();
        $this->assign('tree', $tree);
        if (is_login()) {
            $this->assign('my_tox_money', getMyToxMoney());
        }
        $this->assign('tox_money_name', getToxMoneyName());
        $hot_num = D('shop_config')->where(array('ename' => 'min_sell_num'))->getField('cname');
		if(is_login()){
			$cart_num = D('shop_cart')->where(array('uid' => is_login()))->sum('num');
		}else{
			$arr=$_SESSION['cart'];
					foreach($arr as $val){
						$cart_num=$cart_num+$val['goods_num'];
					}
		}
		if(!$cart_num){
			$cart_num=0;
		}
        $this->assign('hot_num', $hot_num);
        $menu_list = array(
            'left' =>
                array(
                    array('tab' => 'home', 'title' => '首页', 'href' => U('shop/index/index')),
                    array('tab' => 'all', 'title' => '所有商品', 'href' => U('shop/index/goods')),
                ),
            'btnhelf' =>
                array(
                    array('tab' => 'shopcartitem', 'title' => '<span id="cart">我的购物车('.$cart_num.')</span>', 'href' => U('shop/Shopcart/shopcartitem'), 'icon' => 'shopping-cartp'),
                ),
        );
		$webinfo = json_decode(WEBSITEINFO,true);
		if($webinfo['theme']=="runners"){
			$menu_list['btnhelf']	=	'';
		}
        foreach ($tree as $category) {
            $menu = array('tab' => 'category_' . $category['id'], 'title' => $category['title'], 'href' => U('shop/index/goods', array('category_id' => $category['id'])));
            if ($category['_']) {
               /* $menu['children'][] = array('tab' => 'category_' . $category['id'],'title' => '全部', 'href' => U('shop/index/goods', array('category_id' => $category['id'])));*/
                foreach ($category['_'] as $child)
                    $menu['children'][] = array('tab' => 'category_' . $child['id'],'title' => $child['title'], 'href' => U('shop/index/goods', array('category_id' => $child['id'])));
            }
            $menu_list['left'][] = $menu;
        }

			$menu_list['left'][] = array('tab' => 'category_other', 'title' => '其他', 'href' => U('shop/index/goods', array('category_id' => 'other')));

        $this->assign('sub_menu', $menu_list);
		$this->assign('model_info', $model_info);
        $this->assign('current', 'home');
    }

    /**
     * 商城首页
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function index()
    {
		$webinfo = json_decode(WEBSITEINFO,true);
		if($webinfo['theme']=="runners"){
			$url=U("Shop/Index/goods");
			header("Location:$url");
			exit;
		}
        $this->display();
    }

    /**
     * 商品页
     * @param int $page
     * @param int $category_id
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function goods($page = 1, $category_id = 0,$distribute_category_id = 0)
    {	
		$current	=	'category_'.$_GET['category_id'];
       
        $this->setTitle('{$category_name|op_t}' . ' 商城');
        $this->setKeywords('{$category_name|op_t}' . ', 商城');
        if($_GET['category_id']==0 && $_GET['category_id']!='other'){
            $current	=	 'all';
        }elseif($_GET['category_id']=='other'){
			 $current	=	 'other';
		}
		$this->assign('current', $current);
        $this->display();
    }

    /**
     * 商品详情页
     * @param int $id
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function goodsDetail($id = 0)
    {
		$good_arr	=	D('Common/shop')->compgoods($id);
		$goods	=	$good_arr['goods'];
		$goods['market_price']	=	D('Common/shop')->sku_ids_price($id);
		/***********限时特价***************/
		$shop_bargain	=	D('Common/shop')->shop_bargain($id);
		
		$goods['bargain_price'] = $shop_bargain['bargain_price'];
		$goods['overtime']		= $shop_bargain['overtime']*1000;
		/***********限时特价结束***************/
		$childcategory= D('shop_category')->where('id='.$goods['category_id'])->find();
		$goods['category_title'] = $childcategory['title'];
		$goods['category_id'] = $childcategory['id'];
		$upcategory= D('shop_category')->where('id='.$childcategory['pid'])->find();
		$goods['up_category_title'] = $upcategory['title'];
		$goods['up_category_id'] = $upcategory['id'];
		$view_count = $goods['view_count'];
		$view_count++;
		D('shop')->where('id='.$id)->save(array('view_count'=>$view_count));
        if (!$goods['status']) {
            $this->error('商品不存在或尚未上架',U('Shop/Index/index'),3);
        }
		
		if($goods['shop_brand']){
			$shop_brand = D('shop_brand_manage')->where("id=".$goods['shop_brand'])->find();
			
			if($shop_brand){
				 $this->assign('shop_brand',$shop_brand);
			}
		}elseif(!$goods['shop_brand']){
			$shop_brand['name']	= $goods['custom_brand_name'];
			$shop_brand['englist_name']	= $goods['custom_brand_enname'];
			$this->assign('shop_brand',$shop_brand);
		}
		
		if($goods['tag']){
			$shop_tag_arr = explode(',',$goods['tag']);
			foreach ($shop_tag_arr as $k => $v) {
					$tags[$v]['id']		= $v;
					$tags[$v]['name']	= get_event_tag($v);
			}
			if($tags){
				$this->assign('tags',$tags);
			}
		}
		if ($goods['goods_pictures_id']) {
				$pictures = M("Picture")->field('id,path')->where("id in ({$goods['goods_pictures_id']})")->select();
				foreach ($pictures as &$img) {
					$img['path'] = fixAttachUrl($img['path']);
				}
				unset($img);
				$this->assign('pictures', $pictures);
			}
		///------------------------------------------得到sku---属性---------------
		$goods_detail_info = D('shop_sku_detailed')->where("goods_id=".$id)->order('price desc')->field("sku_id,sku_title,price,stock,sku_sn")->select();
		$sku_info['price']  = $goods['market_price'];
		$sku_info['stock'] = $goods['goods_num'];
		foreach($goods_detail_info as $key=>&$val){
            $val['sku_title']=json_decode($val['sku_title'],true);
                foreach($goods_detail_info[$key]['sku_title'] as $k=>$v){
				       $cods[$key] .= $v['value'].",";
					   $types_cate[$k] = $v['type_id'];  //---得到升序--
					 
					 
				}
                $goods_detail_info[$key]['sku_title'] = rtrim($cods[$key],",");
				$sku_info[$goods_detail_info[$key]['sku_title']]= array('sku_id'   =>$goods_detail_info[$key]['sku_id'],
				                                                        'stock'	   =>$goods_detail_info[$key]['stock'],
				                                                        'price'	   =>$goods_detail_info[$key]['price'],
				                                                        );
		}
		
		unset($key);
		unset($val);
		unset($k);
		unset($v);
		$this->assign('sku_info',json_encode($sku_info));  //--sku--价格-和库存--
		if($goods_detail_info){
			$this->assign('is_sku',1);  //--sku--价格-和库存--
		}
		
	    $sku_detailed_display = D('shop_sku_detailed_display')->where("goods_id=".$goods['id'])->order('sort')->select();
			foreach($sku_detailed_display as $s_d_d){
			 $sku_type_detailed[$s_d_d['types_id']]['type_id'] = $s_d_d['types_id'];
			 if($s_d_d['is_system']==1){
				  //-----------------------------------------------------------得到默认分类-属性--------------------------------------------
					$sys_tem_type = D('shop_sku_types_system')->where("sku_types_id=".$s_d_d['types_id'])->find();
					
					$sku_type_detailed[$s_d_d['types_id']]['type_name'] = $sys_tem_type['types_name'];
					$sku_type_detailed[$s_d_d['types_id']]['is_color'] = $sys_tem_type['is_color'];
					$sku_type_detailed[$s_d_d['types_id']]['is_system'] = $sys_tem_type['is_system'];
					$sku_type_detailed[$s_d_d['types_id']]['data'][$s_d_d['attribute_value']]['attribute_id'] = $s_d_d['attribute_value'];
					$sku_type_detailed[$s_d_d['types_id']]['data'][$s_d_d['attribute_value']]['attribute_name'] = $s_d_d['attribute_name'];
					
					
					if($sys_tem_type['is_color']){
						$sys_tem_type_detail = D('shop_sku_types_attribute_stystem')->where("attribute_id=".$s_d_d['attribute_value'])->find();
						$sku_type_detailed[$s_d_d['types_id']]['data'][$s_d_d['attribute_value']]['attribute_color'] = $sys_tem_type_detail['attribute_value'];
					}
						
			 }
		
           }
			//---------------------------对数组重新排序---------------------------------
			foreach($types_cate as $val){
				foreach($sku_type_detailed as $k=>$v){
					 if($val==$k){
						$new_sku_type_detailed[$val]=$sku_type_detailed[$k];
					 }
				}
				
			}
		
		 $this->assign('sku_type_detailed',$new_sku_type_detailed);
		 
		 //-----------------------------------------------------------得到指定属性所有分组-属性-------------------------------------------- 
		foreach($sku_detailed_display as $group_var){
			$suk_group[$group_var['sku_id']][$group_var['attribute_value']] = $group_var;
		}
		
		foreach($sku_detailed_display as $key22=>$group_var){
			
			$attribute_value = $group_var['attribute_value'];
			$types_id = $group_var['types_id'];
			
			foreach($sku_detailed_display as $types_key=>$types_var){
				
				if($types_var['types_id'] == $types_id){
					$chick_all_data[$types_var['attribute_value']] = $types_var;
				}
			}
			foreach($suk_group as $keyss=>$suk_group_var){
				foreach($suk_group_var as $k=>$tmp){
					
					$temp_arr[$k] = $tmp['attribute_value'];
				}
				if(in_array($attribute_value,$temp_arr)){
					$new_group_arr[] = $suk_group_var;
				}else{
					continue;
				}
				unset($temp_arr);
			}
			if($new_group_arr){
				foreach($new_group_arr as $kk=>$suk_attribute_var){
					foreach($suk_attribute_var as $kkk=>$rs){
						if($rs['types_id'] == $types_id){
							$return_data[$rs['types_id']] = $chick_all_data;
						    
						}else{
							$return_data[$rs['types_id']][$rs['attribute_value']] = $rs;
						}	
					}
				}
			}
			unset($chick_all_data);
			unset($new_group_arr);
			$shop_detail_infos[$attribute_value] = $return_data;
			unset($return_data);
		}
		 
		$this->assign('shop_detail_infos',json_encode($shop_detail_infos));
	  
		//-----------------------------------------------------------得到sku-属性--------------------------------------------
		
		//同类对比
        $goods_categorys_ids = D('shop_category')->where("siteid = ".SITEID." and (id=%d OR pid=%d)", array($category['id'], $category['id']))->limit(999)->field('id')->select();
        foreach ($goods_categorys_ids as &$v) {
            $v = $v['id'];
        }
		
		if($goods['siteid']	!=	SITEID){
			$goods['category_id']	=	'other';
		}
		$map	=	D('Common/shop')->goodsmap('',$goods['category_id']);
        $same_category_goods = D('shop')->where($map)->limit(2)->order('sell_num desc')->field($this->goods_info)->select();
        $this->assign('contents_same_category', $same_category_goods);
		
		
        //最近浏览
        if (is_login()) {
            //关联查询最近浏览
            $sql = "SELECT a." . $this->goods_info . " FROM `" . C('DB_PREFIX') . "shop` AS a , `" . C('DB_PREFIX') . "shop_see` AS b WHERE ( b.`uid` =" . is_login() . " ) AND ( b.`goods_id` <> '" . $id . "' ) AND ( a.`status` = 1 )AND(a.`id` =b.`goods_id`) ORDER BY b.update_time desc LIMIT 3";
            $Model = new \Think\Model();
            $goods_see_list = $Model->query($sql);
            $this->assign('goods_see_list', $goods_see_list);
            //添加最近浏览
            $map_see['uid'] = is_login();
            $map_see['goods_id'] = $id;
            $rs = D('ShopSee')->where($map_see)->find();
            if ($rs) {
                $data['update_time'] = time();
                D('ShopSee')->where($map_see)->save($data);
            } else {
                $map_see['create_time'] = $map_see['update_time'] = time();
                D('ShopSee')->add($map_see);
            }
        }
		if(is_login()){
			$cart_num = D('shop_cart')->where(array('uid' => is_login()))->sum('num');
		}else{
			$arr=$_SESSION['cart'];
					foreach($arr as $val){
						$cart_num=$cart_num+$val['goods_num'];
					}
		}
		if(!$cart_num){
			$cart_num=0;
		}
		$this->assign('cart_num',$cart_num);
		$this->assign('cart_price',$cart_price);
        $this->assign('current', 'category_'.$goods['category_id']);
	    $this->assign('content', $goods);
		$this->setTitle('{$content.goods_name|op_t}' . $this->model_info['name']);
        $this->setKeywords('{$content.goods_name|op_t}' . ', '.$this->model_info['name']);
        $this->display();
    }
}
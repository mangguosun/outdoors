<?php

//梁朝阳


namespace Shop\Widget;

use Think\Action;

/**
 * 商城
 */
class CommonShopWidget extends Action
{
	
    /*图片导航*/
    public function classification()
    {
        $list=D('shop_classification')->where('siteid='.SITEID)->select();
        $this->assign('list',$list);
        $this->display('Widget/shop_classification');
    }
    /*品牌导航*/
    public function brand(){
        $brand=D('shop_brand')->where('siteid='.SITEID)->select();
        $this->assign('brand',$brand);
        $this->display('Widget/shop_brand');
    }
	
   /*最新评论*/
   public function comment($limit=9){
       $comment=D('local_comment')->where("status=1 and siteid=".SITEID)->order('id desc')->limit($limit)->select();
       foreach($comment as $k => $v){
          $comment[$k]['nickname']=D('member')->where('uid='.$v['uid'])->getField('nickname');
          $list=query_user(array('avatar64','space_url'), $v['uid']);
          $comment[$k]['user_img']=$list['avatar64'];
          $comment[$k]['space_url']=$list['space_url'];
       }
       $this->assign("comment",$comment);
       $this->display('Widget/shop_comment');
   }
   /**
    * 导航分类
    */
   public function navigation($limit=4,$data=5){
     $map2  = array('status' => array('eq', 1),'siteid' => SITEID,'pid'=>0);
      $is_distribute_category = D('shop_category')->where($map2)->limit($limit)->select();
      foreach($is_distribute_category as $k=>$v){
        $map3  = array('status' => array('eq', 1),'siteid' => SITEID,'pid'=>$v['id']);
        $is_distribute_category[$k]['category_2nd'] = D('shop_category')->limit($data)->where($map3)->select();
      }
    $this->assign('is_distribute_category',$is_distribute_category);
    $this->display('Widget/shop_navigation');
   }

   
   /**
    * 限时特价
    */
    public function limit_bargains(){
		$map	=	D('Common/shop')->goodsmap();
		unset($map['status']);
		$map['overtime']	=	array('gt',time());
		$map['starttime']	=	array('lt',time());
		$map['surplus_num']	=	array('gt',0);
		if($map['_complex']['id']){
			$map['_complex']['goods_id']	=	$map['_complex']['id'];
			unset($map['_complex']['id']);
		}
		$bargain = D('shop_bargain')->where($map)->select();
        foreach($bargain as $key => $v) {
                $bargain[$key]['goods_name']= D('shop')->where("id=".$v['goods_id'])->getField('goods_name');
                
                $bargain[$key]['tox_money_need']=D('shop')->where("id=".$v['goods_id'])->getField('tox_money_need');
                $bargain[$key]['goods_ico']=D('shop')->where("id=".$v['goods_id'])->getField('goods_ico');
                $bargain[$key]['overtime']=$bargain[$key]['overtime']*1000;
            }
		$this->assign('shop_limit_bargains_info', $bargain);
		
        $this->display('Widget/shop_limit_bargains');
    }
   /**
    * 特价商品
    */
   public function bargains($limit=5){
       $bargains=D('shop')->where("is_bargains = 1 AND status = 1 AND siteid=".SITEID)->order('id desc')->limit($limit)->select();
        foreach ($bargains as $k => $v) {
            if($v['shop_brand'] != 0){
                $bargains[$k]['custom_brand_name']=D('shop_brand_manage')->where('id='.$v['shop_brand'])->getField('name');}
            }
        $this->assign('shop_bargains_info',$bargains);
        $this->display('Widget/shop_bargains');
   }
   
   
   public function module($limit=5,$action=''){
		$info	=	D('Common/Shop')->index_module($limit,$action);
		switch($action){
			case 'new_products':	//新品
				$view	=	'Widget/shop_new';
			break;
			case 'recommend':	//推荐
				$view	=	'Widget/shop_recommend';
			break;
			case 'bargains':	//特价
				$view	=	'Widget/shop_bargains';
			break;
			case 'fiery':	//热销
				$view	=	'Widget/shop_fiery';
			break;
		}
		
		$this->assign('data',$info);
		$this->display($view);

	}
	
   public function goods($page = 1, $category_id = 0,$tag='',$shop_brand='',$action='')
	{
		$map	=	D('Common/shop')->goodsmap($goods_id,$category_id);
		$order	=	'sort';
		$shop_title	=	'全部商品';
		
		//筛选分类，筛选按钮
		 if($_GET['category_id']==0 && $_GET['category_id']!='other'){
            $category_title	=	'';
        }elseif($_GET['category_id']=='other'){
			$category_title['title']	=	'其他';
			$category_title['href']	=	structure_filters_url($field,array("category_id"=>''));
		}else{
            $category_title['title']	=	D('shop_category')->where('id='.$category_id)->getField('title');
			$category_title['href']	=	structure_filters_url($field,array("category_id"=>''));
        }
		$this->assign('category_title', $category_title);
		
		
		//筛选其他，筛选按钮
		if($action){
			switch($action){
				case 'new_products':
					$action_title['title']	=	'新品上市';
					$map['is_new']	=	1;
				break;
				case 'recommend':
					$map['is_recommend']	=	1;
					$action_title['title']	=	'精品推荐';
				break;
				case 'fiery':
					$action_title['title']	=	'热卖排行';
					$map['is_firey']	=	1;
				break;
				case 'bargains':
					$action_title['title']	=	'特价商品';
					$map['is_bargains']	=	1;
				break;
				case 'limit_bargains':
					$action_title['title']	=	'限时特价';
					$map['id']	=	array('in',D('Common/shop')->limit_bargains_map());
				break;
			}
			$action_title['href']	=	structure_filters_url($field,array("action"=>''));
			$this->assign('action_title', $action_title);
		}
		//筛选标签，筛选按钮
		if($tag){
			$map['tag']=array('like',"%".$tag."%");
            $tag_title['title']	=	D('tags')->where('id='.$_GET['tag'])->getField('title');
			$tag_title['href']	=	structure_filters_url($field,array("tag"=>''));
			$this->assign('tag_title', $tag_title);
		}
		//筛选品牌，筛选按钮
		if($shop_brand){
			$shop_brand	=	urlsafe_b64decode($shop_brand);
			$brand_map['name']=	$shop_brand;
			$brand_id	=	D('shop_brand_manage')->where($brand_map)->getField('id');
			$map['name']=	$shop_brand;
			if($brand_id){
				$where['shop_brand']  = $brand_id;
				$where['_logic'] = 'or';

			}
			$where['custom_brand_name']  = $shop_brand;
			
			$map['_complex'] = $where;
			$brand_title['title']	=	$shop_brand;
			$brand_title['href']	=	structure_filters_url($field,array("shop_brand"=>''));
			$this->assign('brand_title', $brand_title);
		}
		
        $goods_list = D('shop')->where($map)->order($order)->page($page, 20)->select();
		$totalCount = D('shop')->where($map)->count();
		if(!$goods_list){
			$page	=	ceil($totalCount/20);
		}
		$goods_list = D('shop')->where($map)->order($order)->page($page, 20)->select();
		foreach($goods_list as $key=>$val){
			$goods_list[$key]['market_price']	=	D('Common/shop')->sku_ids_price($val['id']);
			if($val['shop_brand'] != 0){
				$goods_list[$key]['custom_brand_name']=D('shop_brand_manage')->where('id='.$val['shop_brand'])->getField('name');
			}
			$bargain_price = D('shop_bargain')->where('goods_id='.$val['id'].' and overtime>'.time().' and  starttime<'.time().' and surplus_num>0')->getField('bargain_price');
			if($bargain_price){
				$goods_list[$key]['market_price']	=	$bargain_price;
			}
		}
		
		$this->assign('action', $action);
		$this->assign('shop_brand', $shop_brand);
		$this->assign('tag', $tag);
		$this->assign('category_id', $category_id);
		$this->assign('goods_list', $goods_list);
		$this->assign('shop_title', $shop_title);
		$this->assign('totalPageCount', $totalCount);
		$this->display('Widget/common_shop_mall_module');
	}
	/**********筛选菜单************/
	  public function screeningmenu($menu_list, $brand, $id){
		$seen	=	D('Common/shop')->get_shop_brand();
		$select_tag_arr		= D('Common/shop')->screening('tag',$seen[1]);
		$select_brand_arr 	= D('Common/shop')->screening('shop_brand', $seen[0]);
		$screen['limit_bargains']	=	"限时特价";
		$screen['new_products']	=	"新品速递";
		$screen['bargains']		=	"特价商品";
		$screen['recommend']	=	"精品推荐";
		$screen['fiery']	=	"热卖排行";
		$select_action_arr 	= D('Common/shop')->screening('action', $screen);

        $this->assign('menu_list', $menu_list);
        $this->assign('brand', $brand);

		
		$this->assign('shop_brand', $select_brand_arr);
		$this->assign('tags', $select_tag_arr);
		$this->assign('action', $select_action_arr);
		
		
		/*********分类*************/
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
		
        $menu_list_tree = array(
            'left' =>
                array(
                    array('tab' => 'all', 'title' => '全部', 'href' =>structure_filters_url($field,array($field=>$k,"category_id"=>$category['id']),2)),
                ),
            'btnhelf' =>
                array(
                    array('tab' => 'shopcartitem', 'title' => '<span id="cart">我的购物车('.$cart_num.')</span>', 'href' => U('shop/Shopcart/shopcartitem'), 'icon' => 'shopping-cartp'),
                ),
        );
        foreach ($tree as $category) {
            $menu = array('tab' => 'category_' . $category['id'], 'title' => $category['title'], 'href' => structure_filters_url($field,array($field=>$k,"category_id"=>$category['id'])) );
            if ($category['_']) {

                foreach ($category['_'] as $child)
                    $menu['children'][] = array('tab' => 'category_' . $child['id'],'title' => $child['title'], 'href' => structure_filters_url($field,array($field=>$k,"category_id"=>$child['id'])));
            }
            $menu_list_tree['left'][] = $menu;
			
        }
		$menu_list_tree['left'][] =  array('tab' => 'other', 'title' => '其他', 'href' =>structure_filters_url($field,array("category_id"=>'other')));
        $this->assign('scr_menu', $menu_list_tree);
		$this->display('Widget/screeningmenu');
		
	  }
	  
	  /*二级菜单*/
	public function comshopmenu($menu_list, $current, $brand, $id)
    {
	
		$this->assign('urlmap', $map);
		$this->assign('current', $current);
        $this->assign('menu_list', $menu_list);
        $this->assign('brand', $brand);
        $this->display('Widget/common_shopmenu');
    }

}

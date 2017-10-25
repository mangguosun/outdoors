<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Mobile\Controller;


/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class ShopController extends MobileController
{
	 protected $goods_info = 'id,goods_name,goods_ico,goods_introduct,tox_money_need,market_price,goods_num,changetime,status,createtime,category_id,is_new,sell_num';
	 
	 public function _initialize(){
	   if (!is_login()) {
			//$this->redirect('Mobile/User/login');
		}
		$model_info = get_appinfo('Shop');
		if(!$model_info){
			$this->error('应用未开启');
		}
		$this->assign('model_info', $model_info);
		$this->setTitle($model_info['name']);
	}
	
	public function index(){
		
		$this->display();
	}
	public function goods(){
        /**
         * Mobile商城导航 梁朝阳 2015-4-27
         */
        $map=array('siteid'=>SITEID, 'pid'=>0);
       
        $navigation = D('shop_category')->where($map)->select();
        foreach ($navigation as $k => $v) {
        	$navigation[$k]['list'] = D('shop_category')->where('pid='.$v['id'])->select();
        }
    	
    	//导航商品

        $this->assign('navigation',$navigation);
        $get_url = json_encode($_GET);
        $this->assign('get_url', $get_url);
        $this->display();
	}

    public function get_goods($page=0,$mod=''){
        $start = $page *10;
		if($_GET['category_id']){
			$category_id	=	$_GET['category_id'];
		}
		$map	=	D('Common/shop')->goodsmap($goods_id,$category_id);
        $tag = intval($_GET['tag']);
        if($tag != ''){
            if($tag == 'all'){
                unset($tag);
            }else{
                $map .= " and find_in_set($tag,tag)";
            }
        }

        /**
         * 导航商品
         */
		if($mod==''){$mod=$map;}
		$goods_list =D('Shop')->get_lists($mod,10,$start);
		foreach($goods_list as $key=>$v){
			$limit_bargain	=	D('shop')->shop_bargain($v['id']);
			if($limit_bargain){
				$goods_list[$key]['market_price']=$limit_bargain['bargain_price'];
			}
		}
        exit(json_encode($goods_list));
    }

	
	public function goodsDetail($id){
		
		$good_arr	=	D('Common/shop')->compgoods($id);
		$goods	=	$good_arr['goods'];
		
		if (!$goods['status']) {
			$this->error('商品不存在或尚未上架',U('Mobile/Shop/index'),3);
		}
		$goods['market_price']	=	D('Common/shop')->sku_ids_price($id);
		$shop_bargain	=	D('Common/shop')->shop_bargain($id);
		if($shop_bargain){
			$goods['bargain_price']=$shop_bargain['bargain_price'];
			$goods['overtime']=$shop_bargain['overtime'];
		} 


		$childcategory= D('shop_category')->where('id='.$goods['category_id'])->find();
		$goods['category_title'] = $childcategory['title'];
		$goods['category_id'] = $childcategory['id'];
		$upcategory= D('shop_category')->where('id='.$childcategory['pid'])->find();
		$goods['up_category_title'] = $upcategory['title'];
		$goods['up_category_id'] = $upcategory['id'];
		$view_count = $goods['view_count'];
		$view_count++;
		D('shop')->where('id='.$id)->save(array('view_count'=>$view_count));

		
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
					$tags[$v]['id'] = $v;
					$tags[$v]['name'] = get_event_tag($v);
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
		$goods_detail_info = D('shop_sku_detailed')->where("goods_id=".$id)->order('price desc')->field("sku_id,sku_title,price,stock")->select();
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

	    $sku_detailed_display = D('shop_sku_detailed_display')->where("goods_id=".$goods['id'])->select();
		 
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
						
			 }else{
				 
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
        $map['category_id'] = array('in', $goods['category_id']);
        $map['status'] = 1;
		$map['siteid'] = SITEID;
        $map['id'] = array('neq', $id);
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
		
		if(is_login()){
			$mark=D('forum_bookmark')->where(array('siteid'=>SITEID,'shop_id'=>$id,'uid'=>is_login()))->find();
			if($mark){
				$is_collection = 1;
			}else{
				$is_collection = 0;
			}
		}else{
			$is_collection = 0;
		}
		
		$this->assign('is_collection',$is_collection);
		
		
		$goods['goods_detail'] = ludou_remove_width_height_attribute($goods['goods_detail']);
		$this->assign('cart_num',$cart_num);
		$this->assign('cart_price',$cart_price);
        $this->assign('current', 'category_'.$goods['category_id']);
	    $this->assign('content', $goods);
		$this->setTitle('{$content.goods_name|op_t}' . $this->model_info['name']);
        $this->setKeywords('{$content.goods_name|op_t}' . ', '.$this->model_info['name']);
		$this->display();
	}	
	/*加入收藏*/
	public function shop_collection(){
		$map['uid'] = is_login();
		if(!empty($map['uid'])){
			$id=$_POST['id'];
			$data['uid']=$map['uid'];
			$data['shop_id']=$id;
			$data['siteid']=SITEID;
			$res=D('ForumBookmark')->where("siteid=".SITEID." and uid={$map['uid']} and shop_id={$id}")->find();
			if($res){
				echo 1;
			}else{
				$data['create_time']=date('Y-m-d H:i:s',time());
				$e=D('ForumBookmark')->data($data)->add();
				if($e){
				echo '0';
				}
			}
		}
	}
}

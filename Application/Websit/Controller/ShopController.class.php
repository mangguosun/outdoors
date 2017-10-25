<?php

namespace Websit\Controller;

use Think\Controller;

class ShopController extends BaseController
{
	protected $shop_categoryModel;
    function _initialize()
    {
		 parent::_initialize();   
      $this->shop_categoryModel = D('Shop/ShopCategory');  
    }
	/*
	**商品列表* 2014-12-3 dlx pm
	*/
    public function index(){
		$status	= $_GET['status'];
		$status = isset($status)? $status:0;
		 switch($status){
			case 0:
				 $map['status']=array('egt','0');
				 $map['siteid']=SITEID;
				 $count = D('shop')->where($map)->count();
				 $Page       = new \Think\Page($count,10);
				 $Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				 $show       = $Page->show();// 分页显示输出
                 $list = D('shop')->where($map) ->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
				
				 $this->assign('shop_list',$list);
				 $this->assign('page',$show);
			break;
			case 1:
				$list =	D('shop_category')->where("siteid=".SITEID ." and pid=0 and status>=0")->select();
				foreach($list as $k=>$v){
				     $list[$k]['childnum']=D('shop_category')->where("pid=".$v['id']." and siteid=".SITEID ." and status>=0")->count();
				}
				
				$this->assign('shop_cates',$list);
			break;
			case 2:
			    $pid = $_GET['pid'];
				$up_title = D('shop_category')->where("siteid=".SITEID ." and id=".$pid." and status>=0")->getField('title');
				$list = D('shop_category')->where("siteid=".SITEID ." and pid=".$pid." and status>=0")->select();
				foreach($list as $k=>$v){
				     $list[$k]['child']=D('shop_category')->where("pid=".$v['id']." and siteid=".SITEID ." and status>=0")->select();
				}
				//if(!$list) $this->error('暂无分类!');
				$this->assign('up_title',$up_title);
				$this->assign('cate_sub',$list);
			break;
			case 3:  //属性名
				$list = D('shop_sku_types_system')->where("siteid=".SITEID)->select();
				$this->assign('system',$list);
			break;
			case 4:  //属性值
				$id	= $_GET['id'];
				$is_color = $_GET['is_color'];
				
				if($id=='') $this->redirect('Websit/Shop/index?status=3');
				$list	=	D('shop_sku_types_attribute_stystem')->where("sku_types_id=".$id)->select();
				$this->assign('attr_stystem',$list);
				$this->assign('typesid',$id);
				$this->assign('is_color',$is_color);
			break;
			case 5: //类目
				$list = D('shop_sku_category')->where('status>=0 and siteid='.SITEID)->select();
				$this->assign('sku_cates',$list);
				break;
			case 10:
				$map['siteid']=SITEID;
				$map['goods_id'] = $_GET['goods_id'];
				$goods_name= D('shop')->where($map) ->getField('goods_name');
				$count = D('shop_order_info')->where($map)->count();
				 $Page       = new \Think\Page($count,10);
			    $list = D('shop_order_info')->where($map)->order("order_sn desc")->limit($Page->firstRow.','.$Page->listRows)->select();
				 $Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				 $show       = $Page->show();// 分页显示输出
				foreach($list as $key=>$value){
					$list2 = D('shop_ordersn')->where('siteid='.SITEID.' and order_sn='.$list[$key]['order_sn'])->find();
					if($list2){
						$list[$key]['consignee_name']=$list2['consignee_name'];
						$list[$key]['create_time']=$list2['create_time'];
						$list[$key]['consignee_address_detailed']=$list2['consignee_address_detailed'];
					}else{
						//unset($list[$key]);//主表内若无订单信息，筛除
					}
				}
				$this->assign('goods_name',$goods_name);
				$this->assign('list',$list);
				$this->assign('page',$show);
			break;
		 }
		
		$this->assign('status',$status);
		$this->display();
	}
	
	/*
	*添加一级分类**2014-12-4
	*/
	
	public function add($id=0,$pid=0,$title='',$sort=0)
	{
		if(IS_POST){
		            ///---------------------------修改------------------------------------
			if($id !=0){
				if($id=='') $this->error('参数错误!');
				$title	=	op_t(trim($title));
				
				if($title =='') $this->error('请填写分类名称');
				if($sort !=0){
					if(!is_numeric($sort)) $this->error('排序必须为数字!');
				}
                $rs = D('shop_category')->where("title='{$title}' and id!=".$id." and pid=".$pid." and status>=0 and siteid=".SITEID)->find();
				if($rs) $this->error('亲!不能添加重复数据哦');
                
				$data = array(
					'title' 		=> $title,
					'update_time'	=> time(),
					'sort'			=> $sort,
				);
				
				$catelist	=	D('shop_category')->where("id=".$id)->save($data);
				if($catelist){
					$up_shop_category['update_time']=time();
					$uplist = D('shop_category')->where('id='.$pid)->save($up_shop_category);
					$this->success("更改成功",'refresh');
				}else{
					$this->error('更改失败!');
				}
			}else{
				///-----------------------------------添加-------------------------------
			    $title	=	op_t(trim($title));
				$sort	=	op_t(trim($sort));
				if($title =='') $this->error('请填写一级分类名称');
				if($sort !=0){
					if(!is_numeric($sort)) $this->error('排序必须为数字!');
				}
				//---验证是否添加重复---
				$rs = D('shop_category')->where("title='{$title}' and pid=0 and status>=0 and siteid=".SITEID)->find();
				if($rs) $this->error('亲!你已添加该分类!不能重复添加哦!');

				$data = array(
					'title' 		=> $title,
					'create_time'	=> time(),
					'pid'			=> 0,
					'sort'			=> $sort,
					'siteid'		=> SITEID,
					
				);
				$cate	=	D('shop_category')->data($data)->add();
				if($cate){
					$this->success("添加成功",'refresh');
				}else{
					$this->error("添加失败!");
				}
			
			}
			
		}
	
	}
	
	/*
	*修改一级分类* 2014-12-4**
	*/
	
	public function shop_category_edit()
	{
		$id	= $_GET['id'];
		$list = D('shop_category')->where("id=".$id." and siteid=".SITEID)->find();
	    $this->assign('catefind',$list);
		$this->display();
	}
	/*
	**添加子分类**
	*/
	
	public function shop_sub_add($pid=0,$title='',$sort=0)
    {
		if(IS_POST){
	        $title = op_t(trim($title));
			$sort  = op_t(trim($sort));
		
			if(get_category_level($pid)){
				$this->error('亲!最多可添加三级分类哦');
			}
			
            if($pid =='') $this->error('参数错误!');
			if($title =='') $this->error('请填写分类名称!!');
			if($sort !=0){
				if(!is_numeric($sort)) $this->error('排序必须为数字!');
			}
			
           $rs = D('shop_category')->where("title='{$title}' and status>=0 and pid=".$pid." and siteid=".SITEID)->find();
            if($rs) $this->error('亲!你已添加该分类!不能重复添加哦!');
			
			$data = array(
				'siteid'		=>SITEID,
				'pid'			=>$pid,
				'title'			=>$title,
				'sort'			=>$sort,
				'status'		=>1,
				'create_time'	=>time(),
				
			);
			
			$list = D('shop_category')->data($data)->add();
			if($list){
				$up_shop_category['update_time']=time();
				$uplist = D('shop_category')->where('id='.$pid)->save($up_shop_category);
				$this->success('添加成功','refresh');
			}else{
				$this->error('添加失败!');
			}
		}else{
			$pid = $_GET['pid'];
			$list = D('shop_category')->where("id=".$pid)->find();
			$shop_list = D('shop_category')->where(array('status'=>1,'siteid'=>SITEID))->select();
			$this->assign('cates',$list);
			$this->display();
			
		}
	}
	
	/*
	**删除禁用*** 2014-12-4 
	*/
	public function shop_cate_disable($id=0,$status=0)
	{
	    if(IS_POST){
			 $id = $_POST['id'];
			 $status = $_POST['status'];
			 if($id =='' || $status=='') $this->error('参数错误!');
			 if($status==0){
				$list = D('shop_category')->where("pid=".$id." and status=1")->select();
				if($list){
					$this->error('有子类,不能直接禁用');
				}
				$cates = D('shop_category')->where("id=".$id)->save(array('status'=>$status));
				if($cates){
					$this->success('操作成功');
				}else{
					$this->error('操作失败!');
				}
			 
			 }else{
				$cates = D('shop_category')->where("id=".$id)->save(array('status'=>$status));
				if($cates){
					$this->success('操作成功');
				}else{
					$this->error('操作失败!');
				}
			 
			 }
			 
		}
		
	}
    /*
	*发布商品**dlx 2014-12-9 pm
	*/
	public function shop_add($shop_brand_mode=1,$category_id=0,$shop_url='',$goods_name='',$goods_detail='',$goods_ico='',$goods_pictures_id='',$tox_money_need='',$market_price='',$goods_num=0,$is_new=0,$tag = '',$shop_brand=0,$purchase_status=1,$goods_introduct='',$custom_brand_name='',$custom_brand_firstuc='',$custom_brand_enname=''){
	    if(IS_POST){
				$shop_url=U('Websit/Shop/index');
	    		$fr_id=$_POST['select'];
	    		$fr_freight=$_POST['fr_freight'];
		    $category_id = op_t(trim($category_id));
			$goods_name	 = op_t(trim($goods_name));
			$goods_ico	 = op_t(trim($goods_ico));
			$market_price = op_t(trim($market_price));
			$goods_introduct 	= op_t(trim($goods_introduct));
			$goods_introduct	= mb_substr($goods_introduct, 0, 99, 'utf-8');
			if($shop_brand_mode==1){
				$shop_brand 	= op_t(trim($shop_brand));
				$custom_brand_name		= '';
				$custom_brand_firstuc	= '';
				$custom_brand_enname	= '';
			}elseif($shop_brand_mode==2){
				$shop_brand 			= '';
				$custom_brand_name		=op_t(trim($custom_brand_name));
				$custom_brand_enname	=op_t(trim($custom_brand_enname));
				if($custom_brand_name	=='') $this->error('请输入品牌名称!');
				$custom_brand_firstuc  = $this->getWords($custom_brand_name);
				$custom_brand_firstuc  = strtolower($custom_brand_firstuc);
			}else{
				$shop_brand 	= '';
				$custom_brand_name		= '';
				$custom_brand_firstuc	= '';
				$custom_brand_enname	= '';
			}
			
			$tox_money_need = op_t(trim($tox_money_need));//-价格-
			$goods_num	 = op_t(trim($goods_num)); //-数量-
			
			$goods		 = $_POST['goods'];  //-整个商品记录-
			//$sku_title	 = $goods['sku_title'];
			$attribute_name		 = $goods['attribute_name'];
			$price		 = $goods['price'];
			$stock		 = $goods['stock'];
			/*foreach($goods as $key =>$value){
						foreach($value as $k=>$v){
							$arr1[$k][$key]=$v; 
							$attribute_add['sku_types_id']=$sku_types_id;
							$attribute_add['attribute_name']=$goods[$k]['attribute_name'];
						}
					}
				print_r($arr1);
				exit;	*/
			if($category_id	=='') $this->error('请选择分类');
			if($goods_name	=='') $this->error('请输入商品名称!');
			if(!is_numeric($tox_money_need) || $tox_money_need<=0 ){
				$this->error('请填写正确的市场价格!');
			}
			
			if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $tox_money_need)) {  
				$this->error('市场参考价格最多可保留两位小数点');  
			}
            
			if(!is_numeric($market_price) || $market_price<=0 ){
				$this->error('请填写正确的销售价格!');
			} 
			
			if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $market_price)) {  
				$this->error('销售价格最多可保留两位小数点');  
			}
			
			if($market_price>$tox_money_need){
				$this->error('销售价格不应该高于市场参考价');
				
			}
			if(!preg_match("/^\+?[1-9][0-9]*$/",$goods_num)){
				$this->error("请填写正确的库存数量!");
			} 

			if($goods_ico	=='') $this->error('请先上传商品图片封面!');
			if($is_new == '') $this->error('请选择是否新品');
			
			if(empty($tag)){
			$this->error('请添加商品标签!');
			}
			if($goods_detail=='') $this->error('请填写商品详情');
			$tag = implode(',',$_POST['tag']);
		    
			if($goods==null){ //-单品-
			  
			    $shop_goods = array(
				    'siteid'		=>	SITEID,
					'goods_name'	=>	$goods_name,
					'goods_introduct'	=>	$goods_introduct,
					'fr_id'			=>$fr_id,
			    		'fr_freight'	=>$fr_freight,
					'category_id'	=>	$category_id,
					'market_price'	=>  $market_price,
					'tox_money_need'=>  $tox_money_need,
					'goods_num'		=>	$goods_num,
					'goods_ico'		=>	$goods_ico,
					'goods_pictures_id'	=>	$goods_pictures_id,
					'is_new'		=>  $is_new,
					'createtime'	=>	time(),
					'changetime'	=>  time(),
					'goods_detail'	=>	$goods_detail,
					'status'		=>	1,
					'purchase_status'	=>	$purchase_status,
					'tag'			=>  $tag,
					'shop_brand'	=>  $shop_brand,
					'custom_brand_name'	=>	$custom_brand_name,
					'custom_brand_firstuc'	=>$custom_brand_firstuc,
					'custom_brand_enname'	=>$custom_brand_enname,
				);
				$shop_list	= D('shop')->add($shop_goods);
				if($shop_list){
					$this->success('添加成功!',$shop_url);
				}else{
					$this->error('添加失败');
				}
			
			}else{	/**----------------------------------------------------有系统属性----颜色/尺码/大小/--------------------------------------------------**/
				    /*-判断价格-*/
					for($i=0;$i<count($price);$i++){
						$j=$i+1;
						if($price[$i]==''){
							$this->error("请填写商品属性第 " .$j. " 条数据价格!");
						  exit;
						}
					if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $price[$i])) {  
							$this->error('商品属性第 '.$j.'最多可保留两位小数点!');
							exit;							
						}
					}
					unset($j);
					/*判断库存*/
					for($i=0;$i<count($stock);$i++){
						 $j=$i+1;
						if($stock[$i] ==''){
							$this->error('请填写第 '.$j.' 条商品库存数量!!!');
						  exit;
						}	
						if(!preg_match("/^\+?[1-9][0-9]*$/",$stock[$i])){
							$this->error('商品第'.$j.'库存请输入整数!');
						    exit;
						}
						 
					}
					/**************** 商品规格（简版）********************/
					for($i=0;$i<count($attribute_name);$i++){	//判断是否有重复属性
						for($j=0;$j<count($attribute_name);$j++){
							if($attribute_name[$i]==$attribute_name[$j]){
								if($i!=$j){
									$this->error('请勿重复添加商品规格');
									exit;
								}
							}
						}
						if(!$attribute_name[$i]){
							$this->error('商品规格名称不得为空');
							exit;
						}
					}
					unset($j);
					$sku_types_id = D('shop_sku_types_system')->where('is_system=0')->getField('sku_types_id');
					if(!$sku_types_id){
						$add_sku_types['types_name'] = "规格属性"; 
						$add_sku_types['is_system'] = 0;
						$sku_types_id = D('shop_sku_types_system')->add($add_sku_types);
					}
					foreach($goods as $key =>$value){
						foreach($value as $k=>$v){
							$arr0[$k][$key]=$v; 
						}
					}
					
					unset($value);
					unset($key);
					unset($v);
					unset($k);
					foreach($arr0 as $key=>$val){
							$attribute_add['sku_types_id']=$sku_types_id;
							$attribute_add['attribute_name']=$arr0[$key]['attribute_name'];
							$attribute_id = D('shop_sku_types_attribute_stystem')->add($attribute_add);
							$arr0[$key]['attribute_id'] = $attribute_id;
							$goods['sku_title'][$sku_types_id][$key]=$attribute_id;
							}

					/*
						数组样式例：
						Array( [0] => Array ( [attribute_name] => 属性1 [price] => 1 [stock] => 11 ) [1] => Array ( [attribute_name] => 属性2 [price] => 2 [stock] => 22 ) [2] => Array ( [attribute_name] => 属性3 [price] => 3 [stock] => 33 ) [3] => Array ( [attribute_name] => 属性4 [price] => 4 [stock] => 44 ))
					
					*/
					unset($value);
					unset($key);
					unset($v);
					unset($k);
					foreach($goods['sku_title'] as $key =>$value){
						foreach($value as $k=>$v){
							$arr1[$k][$key]=$v; 
						}
				
					}
					
					unset($value);
					unset($key);
					unset($v);
					unset($k);
					
					foreach($arr1 as $key=>$val){
						foreach($val as $v){
							$sku_cods_info[$key][]=array('type_id'=>get_shop_sku_types_name($v),'value'=>$v);
						}
					}
					unset($key);
					unset($val);
					unset($v);
                 
					foreach($arr1 as $key=>$val){
						$goods_sku_title = implode(",",$arr1[$key]);
						$goods['new_sku_title'][]= $goods_sku_title;
                    }
					/******************* 商品规格（简版）结束*************************/
					//--判断是否有重复数据--
					if(count($goods['new_sku_title']) !=count(array_unique($goods['new_sku_title']))){
						$this->error('亲!  不能添加重复数据哦!!!');
					}
					$goods['new_sku_title'] = $sku_cods_info;
					
					unset($goods['sku_title']);
					unset($key);
					unset($val);
                    
					//-添加商品-
					$shop_goods	= array(
						'siteid'		=>	SITEID,
						'goods_name'	=>	$goods_name,
						'category_id'	=>	$category_id,
						'market_price'	=>  $market_price,
						'tox_money_need'=>  $tox_money_need,
						'goods_num'		=>	$goods_num,
						'goods_ico'		=>	$goods_ico,
						'goods_pictures_id'	=>	$goods_pictures_id,
						'is_new'		=>  $is_new,
						'createtime'	=>	time(),
						'changetime'	=>  time(),
						'goods_detail'	=>	$goods_detail,
						'status'		=>	1,
						'purchase_status'	=>	$purchase_status,
						'tag'			=>  $tag,
						'shop_brand'	=>  $shop_brand,
						'custom_brand_name'	=>	$custom_brand_name,
						'custom_brand_firstuc'	=>$custom_brand_firstuc,
						'custom_brand_enname'	=>$custom_brand_enname,
					);
				
					$shop_list	= D('shop')->add($shop_goods);
					//--写入商品表格---
				    if($shop_list){
						/*-得到新的数组-*/
						foreach($goods as $key =>$val){
							foreach($val as $k=>$v){
								$arr2[$k][$key]=$v; 
							}

						}
						
						unset($key);
						unset($val);
						unset($k);
						unset($v);
						
						$goods_nums = 0;
						foreach($arr2 as $key=>$val){
						    $data['siteid']		=   SITEID;
							$data['goods_id']	=	$shop_list;
							$data['sku_title']	=	json_encode($arr2[$key]['new_sku_title']);
							$data['price']		=	$arr2[$key]['price'];
							$data['stock']		=	$arr2[$key]['stock'];
							$goods_nums		   +=	$arr2[$key]['stock'];//-总数-
							$shop_sku_detail    =   D('shop_sku_detailed')->add($data);
						}
						
						unset($arr2);
						unset($key);
						unset($val);
						$shop_list_save	= D('shop')->where("id=".$shop_list)->save(array('goods_num'=>$goods_nums));
                        
						$shop_sku_list	=	D('shop_sku_detailed')->where("goods_id=".$shop_list)->field("sku_id,goods_id,sku_title")->select();
                        $data_sku=array();
							foreach($shop_sku_list as $key=>&$val){
								$val['sku_title']=json_decode($val['sku_title'],true);
								
								foreach($val['sku_title'] as $k=>$v){
									 $news[$key][$k]['sku_id']		   = $val['sku_id'];
									 $news[$key][$k]['goods_id']	   = $val['goods_id'];
									 $news[$key][$k]['attribute_name'] = get_shop_sku_types_attribute($v['value']);
									 $news[$key][$k]['attribute_value'] = $v['value'];
									 $news[$key][$k]['types_id']  = $v['type_id'];
								}
								
								foreach($news[$key] as $tp=>$temp){
								    $data_sku['siteid']				=   SITEID;	
									$data_sku['sku_id']				=	$news[$key][$tp]['sku_id'];
									$data_sku['attribute_name'] 	=   $news[$key][$tp]['attribute_name'];
									$data_sku['attribute_value']	=	$news[$key][$tp]['attribute_value'];
									$data_sku['goods_id']			=   $news[$key][$tp]['goods_id'];
									$data_sku['types_id']			=   $news[$key][$tp]['types_id'];
									$data_sku['is_system']			=	1;                              //--是否系统--
								
									$shop_sku_list_display		    =	D('shop_sku_detailed_display')->add($data_sku);
									
								}
								
                         	}
						
							if($shop_sku_list_display){
								$this->success('发布成功!',$shop_url);

							}else{
								$this->error('发布失败1');

							}

						
					
					}else{
						$this->error('发布失败!');
					
					}
				
			
			}
			
			
			
			
		}else{
			//查询属性
			$siteid=SITEID;
			$list = D('shop_sku_types_system')->where("is_system=1")->order("sort desc")->select();
			foreach($list as $key=>$value){
				$list[$key]['arr']	=	D('shop_sku_types_attribute_stystem')->where("sku_types_id=".$list[$key]['sku_types_id'])->select();
			}
			$this->assign('page',$show);
			$this->assign('list',$list);
			//查询属性结束	
			
			$category = D('Shopcategory');
			
			$this->assign('category',$category);
			$this->display();
		   
		}
		
	}
	/**
	*修改商品*2014-12-28 pm dlx
	***/
	public function shop_edit($shop_brand_mode=1,$category_id=0,$shop_url='',$goods_name='',$goods_detail='',$goods_ico='',$goods_pictures_id='',$tox_money_need='',$market_price='',$goods_num=0,$is_new=0,$tag='',$shop_brand=0,$purchase_status=1,$goods_introduct='',$custom_brand_name='',$custom_brand_firstuc='',$custom_brand_enname=''){
		
		if(IS_POST){
			$shop_url=U('Websit/Shop/index');
			$id = $_POST['id'];
			$fr_id=$_POST['select'];
			$fr_freight=$_POST['fr_freight'];
			if($id=='') $this->error('参数错误!');
			if($shop_brand_mode==1){
				$shop_brand 	= op_t(trim($shop_brand));
				$custom_brand_name		= '';
				$custom_brand_firstuc	= '';
				$custom_brand_enname	= '';
			}elseif($shop_brand_mode==2){
				$shop_brand 			= '';
				$custom_brand_name		=op_t(trim($custom_brand_name));
				$custom_brand_enname	=op_t(trim($custom_brand_enname));
				if($custom_brand_name	=='') $this->error('请输入品牌名称!');
				$custom_brand_firstuc  = $this->getWords($custom_brand_name);
				$custom_brand_firstuc  = strtolower($custom_brand_firstuc);
			}else{
				$shop_brand 	= '';
				$custom_brand_name		= '';
				$custom_brand_firstuc	= '';
				$custom_brand_enname	= '';
			}
			
			$goods_name	 	= op_t(trim($goods_name));
			$goods_detail	= trim($goods_detail);
			$goods_ico	 	= op_t(trim($goods_ico));
			$market_price 	= op_t(trim($market_price));
			$goods_introduct 	= op_t(trim($goods_introduct));
			$goods_introduct	= mb_substr($goods_introduct, 0, 99, 'utf-8');
			
			$tox_money_need = op_t(trim($tox_money_need));//-价格-
			$goods_num	 	= op_t(trim($goods_num)); //-数量-
			
			$oldgoods	 	= $_POST['oldgoods'];  //--本身记录--
			$oldprice		= $oldgoods['price'];
			$oldstock		= $oldgoods['stock'];
			$oldsku_id		= $oldgoods['sku_id'];
			$old_attribute_name		= $oldgoods['attribute_name'];
			
			$goods		 	= $_POST['goods'];  //-整个商品记录-
			//$sku_title	 	= $goods['sku_title'];
			$price		 	= $goods['price'];
			$stock		 	= $goods['stock'];
			$attribute_name		= $goods['attribute_name'];
			
		    if($goods_name	=='') $this->error('请输入商品名称!');
			//--验证市场价格开始--
	        if(!is_numeric($tox_money_need) || $tox_money_need<=0 ){
				$this->error('请填写正确的市场参考价格!');
			}
			if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $tox_money_need)) {  
				$this->error('市场参考价格最多可保留两位小数点');  
			}
            //--验证促销价格---
			if(!is_numeric($market_price) || $market_price<=0 ){
				$this->error('请填写正确的销售价格!');
			} 
			
			if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $market_price)) {  
				$this->error('销售价格最多可保留两位小数点');  
			}
			
			if($market_price>$tox_money_need){
				$this->error('销售价格不应该高于市场参考价');
				
			}
			
			if(!preg_match("/^\+?[1-9][0-9]*$/",$goods_num)){
				$this->error("请填写正确的库存数量!");
			} 
			
            if($goods_ico  =='') $this->error('请先上传商品图片封面!');
			if($is_new == '') $this->error('请选择是否新品');
			if(empty($tag)){
				$this->error('请添加商品标签!');
			}
		    $tag = implode(',',$_POST['tag']);
			if($goods_detail=='') $this->error('请填写商品详情');
            //----验证之前天际的sku-价格与库存----
			if($oldgoods){
				/*-判断价格-*/
				for($i=0;$i<count($oldprice);$i++){
					$j=$i+1;
					if($oldprice[$i]==''){
						$this->error("请填写商品规格第 " .$j. " 条数据价格!");
					  exit;
					}
					if(!is_numeric($oldprice[$i])){
					  $this->error('商品规格第 '.$j.' 条单价必须为数字!');
					  exit;
					}
					if($oldprice[$i]>$tox_money_need){
					  $this->error('商品规格第 '.$j.' 条单价不得大于市场价!');
					  exit;
					}
				
				}
				unset($j);
				/*判断库存*/
				for($i=0;$i<count($oldstock);$i++){
					$j=$i+1;
					if($oldstock[$i] ==''){
						$this->error('请填写第 '.$j.' 条商品库存数量!!!');
						exit;
					}	
                    if(!preg_match("/^\+?[1-9][0-9]*$/",$oldstock[$i])){
						$this->error('商品第'.$j.'库存请输入整数!');
						exit;
					}

				}
				unset($j);
				/****判断旧属性是否有重复属性***/
				for($i=0;$i<count($old_attribute_name);$i++){
					for($j=0;$j<count($old_attribute_name);$j++){
						if($old_attribute_name[$i]==$old_attribute_name[$j]){
							if($i!=$j){
								$this->error('请勿重复添加商品规格');
								exit;
							}
						}
					}
					if(!$old_attribute_name[$i]){
						$this->error('商品规格名称不得为空');
						exit;
					}
				}
				unset($j);
				/****判断旧属性是否有重复属性结束***/
				
			}
			//----验证添加新的sku属性-数据---
			if($goods){
				for($i=0;$i<count($price);$i++){
					$j=$i+1;
					if(empty($price[$i])){
						$this->error("请填写商品新规格第 " .$j. " 条数据价格!");
					  exit;
					}
					if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $price[$i])) {  
							$this->error('商品新规格第  '.$j.'最多可保留两位小数点!');
							exit;							
						}
					if($price[$i]>$tox_money_need){
					  $this->error('商品新规格第 '.$j.' 条单价不得大于市场价!');
					  exit;
					}
				
				}
				unset($j);
				
				/*判断库存*/
				for($i=0;$i<count($stock);$i++){
					 $j=$i+1;
					if($stock[$i] ==''){
						$this->error('请填写第 '.$j.' 条商品库存数量!!!');
					  exit;
					}	
                    if(!preg_match("/^\+?[1-9][0-9]*$/",$stock[$i])){
					 $this->error('商品第'.$j.'库存请输入整数!');
					exit;
					}
					 
				}
				unset($j);
				for($i=0;$i<count($attribute_name);$i++){	//判断是否有重复属性
					for($j=0;$j<count($attribute_name);$j++){
						if($attribute_name[$i]==$attribute_name[$j]){
							if($i!=$j){
								$this->error('请勿重复添加商品规格');
								exit;
							}
						}
					}
					if(!$attribute_name[$i]){
						$this->error('商品规格名称不得为空');
						exit;
					}
				}
				unset($j);
			
			}

			
			  $shop_goods = array(
				    'siteid'		=>	SITEID,
					'goods_name'	=>	$goods_name,
					'goods_introduct'	=>	$goods_introduct,
					'category_id'	=>	$category_id,
					'fr_id'			=>$fr_id,
			  		'fr_freight'	=>$fr_freight,
					'market_price'	=>  $market_price,
					'tox_money_need'=>  $tox_money_need,
					'goods_num'		=>	$goods_num,
					'goods_ico'		=>	$goods_ico,
					'goods_pictures_id'	=>	$goods_pictures_id,
					'is_new'		=>  $is_new,
					'createtime'	=>	time(),
					'changetime'	=>  time(),
					'goods_detail'	=>	$goods_detail,
					'tag'			=> $tag,
					'shop_brand'	=> $shop_brand,
					'purchase_status'	=>	$purchase_status,
					'custom_brand_name'	=>	$custom_brand_name,
					'custom_brand_firstuc'	=>$custom_brand_firstuc,
					'custom_brand_enname'	=>$custom_brand_enname,
				);
               $shop_list	= D('shop')->where("id=".$id)->save($shop_goods);
			
			
			if($oldgoods==null && $goods==null){ //-单品---
			    
				if($shop_list){
					$this->success('更改成功',$shop_url);
				}else{
					$this->error('未更改商品!');
				}
			
			}else{
				    
				//-----当没有再添加sku时 只修改原有sku--属性----------------
				if($goods==null && $oldgoods){
				
						foreach($oldgoods as $key =>$val){
							foreach($val as $k=>$v){
								$oldsgoods_save[$k][$key]=$v; 
							}

						}
						unset($key);
						unset($val);
						unset($k);
						unset($v);

					/****更改旧属性规格***/
						foreach($oldsgoods_save as $key=>$val){
								$old_attribute['attribute_id']		=	$oldsgoods_save[$key]['attribute_id'];
								$old_attribute['attribute_name']	=	$oldsgoods_save[$key]['attribute_name'];
								
								$shop_attribute_save  =   D('shop_sku_types_attribute_stystem')->save($old_attribute);
								$data_info['price']		=	$oldsgoods_save[$key]['price'];
								$data_info['stock']		=	$oldsgoods_save[$key]['stock'];
								$data_info_num		   +=	$oldsgoods_save[$key]['stock'];//-总数-
								$shop_sku_detail_save   =   D('shop_sku_detailed')->where("sku_id=".$oldsgoods_save[$key]['sku_id'])->save($data_info);	
						}
						unset($key);
						unset($val);
						unset($k);
						unset($v);
						
						D('shop')->where("id=".$id)->save(array('goods_num'=>$data_info_num));
						//--写入商品表格---
						if($shop_sku_detail_save || $shop_list){
							$this->success('更改成功',$shop_url);
						}else{
							$this->error('未更改商品');
						}
						
					//-----当用户全部删除原有sku--时---并且添加新的sku--
				}elseif($oldgoods==null && $goods){
					/**************** 商品规格（简版）********************/
					$sku_types_id=D('shop_sku_types_system')->where('is_system=0')->getField('sku_types_id');
					if(!$sku_types_id){
						$add_sku_types['types_name'] = "规格属性"; 
						$add_sku_types['is_system'] = 0;
						$sku_types_id = D('shop_sku_types_system')->add($add_sku_types);
					}
					foreach($goods as $key =>$value){
						foreach($value as $k=>$v){
							$arr0[$k][$key]=$v; 
						}
					}
					
					unset($value);
					unset($key);
					unset($v);
					unset($k);
					foreach($arr0 as $key=>$val){
							$attribute_add['sku_types_id']=$sku_types_id;
							$attribute_add['attribute_name']=$arr0[$key]['attribute_name'];
							$attribute_id = D('shop_sku_types_attribute_stystem')->add($attribute_add);
							$arr0[$key]['attribute_id'] = $attribute_id;
							$goods['sku_title'][$sku_types_id][$key]=$attribute_id;
							}

					/*
						数组样式例：
						Array( [0] => Array ( [attribute_name] => 属性1 [price] => 1 [stock] => 11 ) [1] => Array ( [attribute_name] => 属性2 [price] => 2 [stock] => 22 ) [2] => Array ( [attribute_name] => 属性3 [price] => 3 [stock] => 33 ) [3] => Array ( [attribute_name] => 属性4 [price] => 4 [stock] => 44 ))
					
					*/
					unset($value);
					unset($key);
					unset($v);
					unset($k);
					foreach($goods['sku_title'] as $key =>$value){
						foreach($value as $k=>$v){
							$arr1[$k][$key]=$v; 
						}
				
					}
					
					unset($value);
					unset($key);
					unset($v);
					unset($k);
					/******************* 商品规格（简版）结束*************************/

						foreach($goods['sku_title'] as $key =>$value){
							foreach($value as $k=>$v){
								$arr1[$k][$key]=$v; 
							}

						}
					
						unset($value);
						unset($key);
						unset($v);
						unset($k);
						
						foreach($arr1 as $key=>$val){
							$goods_sku_title = implode(",",$arr1[$key]);
							$goods['new_sku_title'][]= $goods_sku_title;
							unset($goods['sku_title']);
							foreach($val as $v){
								$sku_cods_info[$key][]=array('type_id'=>get_shop_sku_types_name($v),'value'=>$v);
							}

						}
						unset($key);
						unset($val);
						//--判断是否有重复数据--
						/*if(count($goods['new_sku_title']) !=count(array_unique($goods['new_sku_title']))){
							$this->error('亲!  不能添加重复数据哦!!!');
						}*/

						//-拼接数组--
						$goods['new_sku_title'] = $sku_cods_info;
						foreach($goods as $key =>$value){
							foreach($value as $k=>$v){
								$arr2[$k][$key]=$v; 
								
							}
						}
						unset($key);
						unset($value);
						unset($k);
						unset($v);
						
						$num_num=0; //---定义总数---
						foreach($arr2 as $key=>$val){
							$data_info['siteid']	=	SITEID;
							$data_info['goods_id']	=	$id;
							$data_info['price']		=	$arr2[$key]['price'];
							$data_info['stock']		=	$arr2[$key]['stock'];
							$data_info['sku_title']		=  json_encode($arr2[$key]['new_sku_title']);
							$num_num+=$arr2[$key]['stock'];
							$shop_sku_detail_save   =   D('shop_sku_detailed')->add($data_info);
						}
						unset($arr2);
						unset($key);
						unset($val);
					   
						D('shop')->where("id=".$id)->save(array('goods_num'=>$num_num));
					
						$shop_sku_list	=	D('shop_sku_detailed')->where("goods_id=".$id)->field("sku_id,goods_id,sku_title")->select();
						
						$data_sku=array();
						foreach($shop_sku_list as $key=>&$val){
							$val['sku_title']=json_decode($val['sku_title'],true);
							
							foreach($val['sku_title'] as $k=>$v){
								 $news[$key][$k]['sku_id']		   = $val['sku_id'];
								 $news[$key][$k]['goods_id']	   = $val['goods_id'];
								 $news[$key][$k]['attribute_name'] = get_shop_sku_types_attribute($v['value']);
								 $news[$key][$k]['attribute_value'] = $v['value'];
								 $news[$key][$k]['types_id']  = $v['type_id'];
							}
							
							foreach($news[$key] as $tp=>$temp){
								$data_sku['siteid']				=   SITEID;	
								$data_sku['sku_id']				=	$news[$key][$tp]['sku_id'];
								$data_sku['attribute_name'] 	=   $news[$key][$tp]['attribute_name'];
								$data_sku['attribute_value']	=	$news[$key][$tp]['attribute_value'];
								$data_sku['goods_id']			=   $id;
								$data_sku['types_id']			=   $news[$key][$tp]['types_id'];
								$data_sku['is_system']			=	1;                              //--是否系统--
							
								$shop_sku_list_display		    =	D('shop_sku_detailed_display')->add($data_sku);
								
							}
							
						}
					
						if($shop_sku_list_display || $shop_list){
								$this->success('更改成功',$shop_url);
							
						}else{
								$this->error('未更改商品!');
							
						}
					
					
				//------修改原有的sku---并且添加新的sku-----------	
				}elseif($oldgoods && $goods){
					 
						foreach($goods['sku_title'] as $key =>$value){
							foreach($value as $k=>$v){
								$arr1[$k][$key]=$v; 
							}

						}
						unset($value);
						unset($key);
						unset($v);
						unset($k);
						$goods_detail_infos = D('shop_sku_detailed')->where('goods_id='.$id)->field('sku_title')->select();
						
						foreach($goods_detail_infos as $key=>&$val){
							$val['sku_title']=json_decode($val['sku_title'],true);
							foreach($goods_detail_infos[$key]['sku_title'] as $v){
								$cods[$key] .= $v['value'].",";
							}
								$goods_detail_infos[$key]['sku_title'] = rtrim($cods[$key],",");
								$sku_arr[$key] = $goods_detail_infos[$key]['sku_title'];
						}
						unset($val);
						unset($key);
						unset($v);
					
					/**************** 商品规格（简版）********************/
					$sku_types_id=D('shop_sku_types_system')->where('is_system=0')->getField('sku_types_id');
					if(!$sku_types_id){
						$add_sku_types['types_name'] = "规格属性"; 
						$add_sku_types['is_system'] = 0;
						$sku_types_id = D('shop_sku_types_system')->add($add_sku_types);
					}
					foreach($goods as $key =>$value){
						foreach($value as $k=>$v){
							$arr0[$k][$key]=$v; 
						}
					}
					unset($value);
					unset($key);
					unset($v);
					unset($k);
					/********判断新属性与旧属性是否重复************/
					foreach($arr0 as $key=>$val){
						$oldsku_attribute_name		= $oldgoods['attribute_name'];
						foreach($oldsku_attribute_name	as $v){
									$attribute_name = $v;
									if($arr0[$key]['attribute_name']==$attribute_name){
										$this->error('亲!  不能添加重复数据哦!!!');
									}
							}
						}
					unset($value);
					unset($key);
					unset($v);
					unset($v2);
					
					foreach($arr0 as $key=>$val){
							$attribute_add['sku_types_id']=$sku_types_id;
							$attribute_add['attribute_name']=$arr0[$key]['attribute_name'];
							$attribute_id = D('shop_sku_types_attribute_stystem')->add($attribute_add);
							$arr0[$key]['attribute_id'] = $attribute_id;
							$goods['sku_title'][$sku_types_id][$key]=$attribute_id;
							}

					/*
						数组样式例：
						Array( [0] => Array ( [attribute_name] => 属性1 [price] => 1 [stock] => 11 ) [1] => Array ( [attribute_name] => 属性2 [price] => 2 [stock] => 22 ) [2] => Array ( [attribute_name] => 属性3 [price] => 3 [stock] => 33 ) [3] => Array ( [attribute_name] => 属性4 [price] => 4 [stock] => 44 ))
					
					*/
					unset($value);
					unset($key);
					unset($v);
					unset($k);
					foreach($goods['sku_title'] as $key =>$value){
						foreach($value as $k=>$v){
							$arr1[$k][$key]=$v; 
						}
				
					}
					
					unset($value);
					unset($key);
					unset($v);
					unset($k);
					/******************* 商品规格（简版）结束*************************/

						foreach($arr1 as $key=>$val){
							$goods_sku_title = implode(",",$arr1[$key]);
							$goods['new_sku_title'][]= $goods_sku_title;
							foreach($val as $v){
								$sku_cods_info[$key][]=array('type_id'=>get_shop_sku_types_name($v),'value'=>$v);
							}
							
						}
						/*
						if($goods['new_sku_title']){
							$goods_news_sku_title  = array_merge($goods['new_sku_title'],$sku_arr);
							//--判断是否有重复数据--
							if(count($goods_news_sku_title) !=count(array_unique($goods_news_sku_title))){
								$this->error('亲!  不能添加重复数据哦!!!');
							}
						}*/
						
						unset($goods['sku_title']);
						unset($key);
						unset($val);
						unset($v);
						
						foreach($oldgoods as $key =>$val){
								foreach($val as $k=>$v){
									$oldsgoods_save[$k][$key]=$v; 
								}

							}
						unset($key);
						unset($val);
						unset($k);
						unset($v);
						$data_info_num = 0;
						//----------------执行修改原有的sku-------------------
						foreach($oldsgoods_save as $key=>$val){
								$old_attribute['attribute_id']		=	$oldsgoods_save[$key]['attribute_id'];
								$old_attribute['attribute_name']	=	$oldsgoods_save[$key]['attribute_name'];
								
								$shop_attribute_save  =   D('shop_sku_types_attribute_stystem')->save($old_attribute);
								$data_info['price']		=	$oldsgoods_save[$key]['price'];
								$data_info['stock']		=	$oldsgoods_save[$key]['stock'];
								$data_info_num		   +=	$oldsgoods_save[$key]['stock'];//-总数-
								$shop_sku_detail_save   =   D('shop_sku_detailed')->where("sku_id=".$oldsgoods_save[$key]['sku_id'])->save($data_info);	
						}
						unset($key);
						unset($val);
						unset($k);
						unset($v);

						$goods['new_sku_title'] = $sku_cods_info;
						/*-得到新的数组-*/
						foreach($goods as $key =>$val){
							foreach($val as $k=>$v){
								$arr2[$k][$key]=$v; 
								
							}
							
						}
					
						unset($key);
						unset($val);
						unset($k);
						unset($v);
						
						//---添加新的sku----------
						$sku_add_num=0;
						foreach($arr2 as $key=>$val){
							$data['siteid']		=   SITEID;
							$data['goods_id']	=	$id;
							$data['sku_title']	=	json_encode($arr2[$key]['new_sku_title']);
							$data['price']		=	$arr2[$key]['price'];
							$data['stock']		=	$arr2[$key]['stock'];
							$sku_add_num		   +=	$arr2[$key]['stock'];//-总数-
							$shop_sku_detail    =   D('shop_sku_detailed')->add($data);
						}
						
						unset($arr2);
						unset($key);
						unset($val);
						$goods_num	= $data_info_num+$sku_add_num;
						D('shop')->where("id=".$id)->save(array('goods_num'=>$goods_num));
					
						$shop_sku_list	=	D('shop_sku_detailed')->where("goods_id=".$id)->field("sku_id,goods_id,sku_title")->select();
						
						//---去除原有sku与新添加sku之间的重复--------------------------------
						foreach($oldsgoods_save as $key=>$val){
							foreach($shop_sku_list as $k=>$v){
								if($oldsgoods_save[$key]['sku_id']==$v['sku_id']){
									unset($shop_sku_list[$k]);
								
								}
							}
							
						}
						unset($key);
						unset($val);
						unset($k);
						unset($v);
						$shop_sku_list=array_values($shop_sku_list);
						
						$data_sku=array();
						foreach($shop_sku_list as $key=>&$val){
							$val['sku_title']=json_decode($val['sku_title'],true);
							
							foreach($val['sku_title'] as $k=>$v){
								 $news[$key][$k]['sku_id']		   = $val['sku_id'];
								 $news[$key][$k]['goods_id']	   = $val['goods_id'];
								 $news[$key][$k]['attribute_name'] = get_shop_sku_types_attribute($v['value']);
								 $news[$key][$k]['attribute_value'] = $v['value'];
								 $news[$key][$k]['types_id']  = $v['type_id'];
							}
							
							foreach($news[$key] as $tp=>$temp){
								$data_sku['siteid']				=   SITEID;	
								$data_sku['sku_id']				=	$news[$key][$tp]['sku_id'];
								$data_sku['attribute_name'] 	=   $news[$key][$tp]['attribute_name'];
								$data_sku['attribute_value']	=	$news[$key][$tp]['attribute_value'];
								$data_sku['goods_id']			=   $id;
								$data_sku['types_id']			=   $news[$key][$tp]['types_id'];
								$data_sku['is_system']			=	1;                              //--是否系统--
							
								$shop_sku_list_display		    =	D('shop_sku_detailed_display')->add($data_sku);
								
							}
							
						}
					
						if($shop_sku_list_display || $shop_list){
							$this->success('更改成功',$shop_url);

						}else{
							$this->error('未更改商品');

						}
                    
					}
				
			
			
			
			}
			
		}else{
			$shop_list = D('shop')->where("id=".$_GET['id'])->find();
		    $shop_detail=D('shop_sku_detailed')->where('goods_id='.$_GET['id'])->select();

		    foreach($shop_detail as $key=>&$val){
			    $val['sku_title']=json_decode($val['sku_title'],true);
                foreach($shop_detail[$key]['sku_title'] as $k=>$v){
				       $cods[$key] .= $v['value'].",";
				}
                $shop_detail[$key]['sku_title'] = rtrim($cods[$key],",");
				$shop_detail[$key]['sku_title'] = explode(",",$shop_detail[$key]['sku_title']);
            }
			if ($shop_list['goods_pictures_id']) {
				$pictures = M("Picture")->field('id,path')->where("id in ({$shop_list['goods_pictures_id']})")->select();
				foreach ($pictures as &$img) {
					$img['path'] = fixAttachUrl($img['path']);
				}
				unset($img);
				$this->assign('pictures', $pictures);
			}
			
			//查询属性
			$siteid=SITEID;
			$list = D('shop_sku_types_system')->where("is_system=1")->order("sort desc")->select();
			foreach($list as $key=>$value){
				$list[$key]['arr']	=	D('shop_sku_types_attribute_stystem')->where("sku_types_id=".$list[$key]['sku_types_id'])->select();
			}
			$this->assign('page',$show);
			$this->assign('list',$list);
			//查询属性结束	
          
		    $this->assign('shop_list',$shop_list);
			$this->assign('shop_detail',$shop_detail);
			$this->assign('shop_sku_detail',json_encode($this->get_category($shop_list['category_id'])));
			$this->display();
		}
	}
	
	/*
	*判断一级分类
	*/
	public function doCates(){
		$id	= $_POST['id'];
		if($this->get_category($id)){
			echo json_encode($this->get_category($id));
		}else{
			echo json_encode(array('status'=>0,'info'=>'不能选择一级分类'));
		}
	}
	public function get_category($id=''){
		$data = D('shop_category')->where("id=".$id)->find();
        //if($data['pid']==0){
			//echo json_encode(array('status'=>1,'info'=>'不能选择一级分类'));
		//}else{
			if($data['sku_category_id']){
				$sku_category_id = $data['sku_category_id'];
				$list = D('shop_sku_category')->where("sku_category_id=".$sku_category_id)->find();	//--得到绑定的类目--
			    
				$types_id = explode(",",$list['sku_types_id']);
				$sku=array();
				    
					foreach($types_id as $k=>$v){
						//---得到系统属性值-----红色/28,29---
						$sku_types=D('shop_sku_types_attribute_stystem')->where("sku_types_id=".$types_id[$k])->select();
						foreach($sku_types as $tmp)
						{
							if($tmp['sku_types_id']==$types_id[$k]){
							 $sku[$types_id[$k]][] = $tmp;
							}
						}
						 
						if($sku[$types_id[$k]]){
						    $str[$types_id[$k]].="<select name='goods[sku_title][$types_id[$k]][]'	class='form_check col-md-3' check-type='Text'>";
							foreach($sku[$types_id[$k]] as $key=>$value){
								$str[$types_id[$k]].= "<option value=".$value['attribute_id'].">".$value['attribute_name']."</option>";
							}
							$str[$types_id[$k]].= "</select>";
							$str_arr.=$str[$types_id[$k]];
							
						}
						
					}
						$str_arr.="<input type='text' name='goods[price][]' class='col-md-2' placeholder='价格'>";
						$str_arr.="<input type='text' class='col-md-2 new_goods_stock' name='goods[stock][]' placeholder='请输入库存'>";
						$str_arr.="<span class='col-md-1 glyphicon glyphicon-remove goods_detail_span' title='删除'></span>";
					    return $str_arr;
			
			}else{
					
					/* $str_arr.="<input type='text' name='goods[sku_title][]' class='col-md-4' placeholder='商品型号' >";
					 $str_arr.="<input type='text' name='goods[price][]' class='col-md-4' placeholder='价格'>";
					 $str_arr.="<input type='text' class='col-md-3' name='goods[stock][]' placeholder='请输入库存'>";
					 $str_arr.="<span class='col-md-1 glyphicon glyphicon-remove goods_detail_span' title='删除'></span>";
                     */
					 //return $str_arr;
			
			}
          //}
	}
	/*
	*得到第一个属性值*
	*/
	public function attribute_first(){
		
		$data = D('shop_sku_types_attribute_stystem')->where("sku_types_id=".$_POST['id'])->select();
		if($data){
			echo json_encode($data);
		}else{
			echo json_encode(array('status'=>0,'info'=>'你还没有添加内容!'));
		}
	}
	/*
	*返回属性值-*
	*/
	public function attribute_val(){
			
	       $cate = D('shop_sku_types_attribute_stystem')->where("attribute_id=".$_POST['id'])->find();
		   //--假如是颜色--
		   $is_color = shop_is_color($cate['sku_types_id']);
		   if($is_color){
				echo json_encode(array('status'=>1,'attribute_id'=>$cate['attribute_id'],'attribute_name'=>$cate['attribute_name']));
		   }else{
				echo json_encode(array('status'=>0,'attribute_id'=>$cate['attribute_id'],'attribute_name'=>$cate['attribute_name']));
		   }
		 
	}
	/**
	*删除sku_id*
	*/
	public function shop_sku_delete(){
		$sku_id	= $_POST['sku_id'];
		if($sku_id=='') $this->error('参数错误!');
		
		$list = D('shop_sku_detailed')->where("sku_id=".$sku_id)->field("sku_title")->find();
		$sku_title = json_decode($list['sku_title'],true);
		
		foreach($sku_title as $val){
			$sku_types_id=$val['title'];
			$attribute_id=$val['value'];
			$is_system = D('shop_sku_types_system')->where("sku_types_id=".$sku_types_id)->field("is_system")->find();
			if($is_system==0){
				$shop_sku_types_attribute_stystem = D('shop_sku_types_attribute_stystem')->where("attribute_id=".$attribute_id)->delete();
				}
		}
		
		$shop_sku_detail = D('shop_sku_detailed')->where("sku_id=".$sku_id)->delete();
		$shop_sku_detail_display = D('shop_sku_detailed_display')->where("sku_id=".$sku_id)->delete();
		
		if($shop_sku_detail && $shop_sku_detail_display ){
			$this->success('删除成功');
		}else{
			$this->error('删除失败!');
		}
	}	
	public function shop_sku_alldelete(){
		$goods_id	= $_POST['goods_id'];
		if($goods_id=='') $this->error('参数错误!');
		$sku = D('shop_sku_detailed')->where("goods_id=".$goods_id)->select();
		if($sku){
			$shop_sku_detail = D('shop_sku_detailed')->where("goods_id=".$goods_id)->delete();
			$shop_sku_detail_display = D('shop_sku_detailed_display')->where("goods_id=".$goods_id)->delete();
			
			if($shop_sku_detail && $shop_sku_detail_display ){
				$this->success('删除成功');
			}else{
				$this->error('删除失败!');
			}
		}else{
			$this->success('');
		}
	}
	
	/*商品禁用**/
	public function shop_disable($id=0,$status=0){
		   if($id=='') $this->error('参数错误');
		   $list = D('shop')->where("id=".$id)->save(array('status'=>$status));
		   if($list){
			  $this->success('操作成功','refresh'); 
		   }else{
			   $this->error('操作失败!');
		   }
	}
	/**
	*商品推荐**最多推荐10个
	*/
	public function shop_recommend($id=0,$is_recommend=0){
			if($id=='') $this->error('参数错误!');
			if($is_recommend==1){
				
				$shop_reds = D('shop')->where(array('siteid'=>SITEID,'is_recommend'=>1,'status'=>array('egt','0')))->count();
				if($shop_reds<10){
					$list = D('shop')->where("id=".$id)->save(array('is_recommend'=>$is_recommend));
					if($list){
						$this->success('操作成功','refresh'); 
					}else{
						$this->error('操作失败!');
					}
				}else{
					$this->error('亲!最多推荐10商品');
				}
				
			}else{
				
				$list = D('shop')->where("id=".$id)->save(array('is_recommend'=>$is_recommend));
				if($list){
					$this->success('操作成功','refresh'); 
				}else{
					$this->error('操作失败!');
				}
			
			}
	
	
	}
	/*
	*获取中文首字母
	*/
	public function getLimit(){
		    $limit = 	array( //gb2312 拼音排序
			array(45217,45252), //A
			array(45253,45760), //B
			array(45761,46317), //C
			array(46318,46825), //D
			array(46826,47009), //E
			array(47010,47296), //F
			array(47297,47613), //G
			array(47614,48118), //H
			array(0,0),         //I
			array(48119,49061), //J
			array(49062,49323), //K
			array(49324,49895), //L
			array(49896,50370), //M
			array(50371,50613), //N
			array(50614,50621), //O
			array(50622,50905), //P
			array(50906,51386), //Q
			array(51387,51445), //R
			array(51446,52217), //S
			array(52218,52697), //T
			array(0,0),         //U
			array(0,0),         //V
			array(52698,52979), //W
			array(52980,53688), //X
			array(53689,54480), //Y
			array(54481,55289), //Z
		);
		return $limit;
	}
	
	public function getWords($str){
		$str= iconv("UTF-8","gb2312", $str);
		$i=0;
		while($i<strlen($str)){
			$tmp=bin2hex(substr($str,$i,1));
			if($tmp>='B0'){ //汉字的开始
				$t=$this->getLetter(hexdec(bin2hex(substr($str,$i,2))));
				$value[] = sprintf("%c",$t==-1 ? '*' : $t );
				$i+=2;
			}
			else{
				$value[] = sprintf("%s",substr($str,$i,1));
				$i++;
			}
		}
		$result = implode('',$value); ;
		return $result;
	}
	/*
	**/
	public function getLetter($num){
		$limit	= $this->getLimit();
		$char_index=65;
		foreach($limit as $k=>$v){
			if($num>=$v[0] && $num<=$v[1]){
				$char_index+=$k;
				return $char_index;
			}
		}
		return -1;
    }
}  

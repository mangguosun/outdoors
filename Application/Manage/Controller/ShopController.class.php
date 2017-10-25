<?php
namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;

class ShopController extends BaseController
{
	protected $shop_categoryModel;
    function _initialize()
    {
	  parent::_initialize();   
      $this->shop_categoryModel = D('Shop/ShopCategory');  
    }
	/*配置文件*/
	 public function config()
    {
        $admin_config = new AdminConfigBuilder();
        $data = $admin_config->handleConfig();
        $admin_config->title('活动基本配置')
					->keyBool('NEED_VERIFY', '创建活动是否需要审核','默认无需审核')
					->buttonSubmit('', '保存')->data($data);
        $admin_config->display();
    }
	
	/*
	**商品列表* 2014-12-3 dlx pm
	*/
    public function index(){
		    $goods_name = I('goods_name');
			$map['status']=array('egt','0');
			$map['siteid']=SITEID;
			if($goods_name !=''){
				$goods_name	=	urlsafe_b64decode($goods_name);
				$this->assign('goods_name',$goods_name);
				if (is_numeric($goods_name)) {
					$map['id|goods_name'] = array(intval($goods_name), array('like', '%' . $goods_name . '%'), '_multi' => true);
				} else {
					$map['goods_name'] = array('like', '%' . (string)$goods_name . '%');
				}
			}
			$count = D('shop')->where($map)->count();
			$Page       = new \Think\Page($count,10);
			$show       = $Page->show();// 分页显示输出
			$list = D('shop')->where($map) ->order("sort")->limit($Page->firstRow.','.$Page->listRows)->select();

			foreach($list as $k=>$v){
				$list[$k]['market_price']	=	D('Common/shop')->sku_ids_price($v['id']);
			}
			
			$this->assign('datainfo',$list);
			$this->assign('page',$show);
			$this->display();
		
	}
	
	
	public function seek($goods_name){
		$seek = op_t(I('goods_name'));
		if($seek!=''){
			$seek	=	urlsafe_b64encode($seek);
		$url=U('Manage/Shop/index',array('goods_name'=>$seek));
			header("Location:$url");
		}else{
			$this->error('请填写查询内容');
		}
	}
	//--商城回收站---
	
	public function shopstrash($page = 1, $r = 20, $model = ''){
		
		$builder = new AdminListBuilder();
		$builder->clearTrash($model);
		//读取列表
        $map = array('status' => -1,'siteid'=>SITEID);
        $list = D('shop')->where($map)->page($page, $r)->select();
        $totalCount = D('shop')->where($map)->count();
        //显示页面
        $attr['class'] = 'ajax-post';
        $attr['target-form'] = 'ids';

        $builder->title('商品回收站')
            ->setStatusUrl(U('setShopStatus'))->buttonRestore()
			//->buttonClear('shop')
            ->keyId('id','序号')
			->keyLink('goods_name', '商品名称', 'Shop/Index/goodsDetail?id=###')
			->key('category_id','商品分类')
			->key('tox_money_need','商品分类')
			->key('market_price','商品分类')
			->key('goods_num','商品分类')
			->key('sell_num','商品分类')
			->keyMap('is_new', '是否新品', array(0 => '否', 1 => '是'))
			->keyMap('is_recommend', '是否推荐', array(0 => '否', 1 => '是'))
			->keyUpdateTime('changetime','更新时间')
			->keyCreateTime('createtime','创建时间')
			->KeyStatusReversion()
		    ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }
	
	/**
     * 设置状态
     * @param $ids
     * @param $status
     * autor:xjw129xjt
     */
    public function setShopStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('shop', $ids, $status);
    }
    //---商品订单列表---
	public function goods_list(){
		$map['siteid']=SITEID;
		$map['goods_id'] = $_GET['goods_id'];
		$goods_name= D('shop')->where($map) ->getField('goods_name');
		$count = D('shop_order_info')->where($map)->count();
		 $Page       = new \Think\Page($count,10);
		$list = D('shop_order_info')->where($map)->order("order_sn desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		 $show       = $Page->show();// 分页显示输出
		foreach($list as $key=>$value){
			$list2 = D('shop_ordersn')->where('siteid='.SITEID.' and order_sn="'.$list[$key]['order_sn'].'"')->find();
			if($list2){
				$list[$key]['consignee_name']=$list2['consignee_name'];
				$list[$key]['create_time']=$list2['create_time'];
				$list[$key]['consignee_address_detailed']=$list2['consignee_address_detailed'];
			}else{
				//unset($list[$key]);//主表内若无订单信息，筛除
			}
		}
		$this->assign('goods_name',$goods_name);
		$this->assign('datainfo',$list);
		$this->assign('page',$show);
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
	*发布商品**dlx 2014-12-9 pm
	*/
	public function shop_add($shop_brand_mode=1,$category_id=0,$shop_url='',$goods_sn='',$goods_name='',$goods_detail='',$goods_ico='',$goods_pictures_id='',$tox_money_need='',$market_price='',$goods_num=0,$is_new=0,$tag = '',$shop_brand=0,$purchase_status=1,$goods_introduct='',$custom_brand_name='',$custom_brand_firstuc='',$custom_brand_enname='',$distribute_type_b=0,$exhibit_status=0,$seller_price='',$distribute_desc='',$distribute_category_id='',$is_bargains=1,$status=1,$is_recommend=1,$sort='',$is_firey=1){
	
		$find_site_relation	=	D('shop_distribute_site_relation')->where('seller_id='.SITEID.' and distribute_relation_status=1 and status=1')->field('seller_id')->select();
		if($find_site_relation)	$this->error('已为全站分销商，无法进行此操作');	
	    if(IS_POST){
		
			$goods_name	 = op_t(trim($goods_name));
			$category_id = op_t(trim($category_id));
			$goods_ico	 = op_t(trim($goods_ico));
			$market_price = op_t(trim($market_price));
			$goods_introduct 	= op_t(trim($goods_introduct));
			$goods_introduct	= mb_substr($goods_introduct, 0, 99, 'utf-8');
			$tox_money_need = op_t(trim($tox_money_need));//-价格-
			$goods_num	 = op_t(trim($goods_num)); //-数量-
			
			$tmp = @iconv('gbk', 'utf-8', $goods_name);
			if(!empty($tmp)){
			 $goods_name_count = $tmp;
			}
			preg_match_all('/./us', $goods_name_count, $match);
			if(count($match[0])>30){ 
				$this->error('标题字数不得超过30个字！');
			}
			if($goods_name	=='') $this->error('请输入商品名称!');
		
	    	//$fr_id=$_POST['select'];
	    	$fr_id=$_POST['rd_freight'];//是否包邮产品
	    	$fr_freight=$_POST['fr_freight'];
	    	$fr_num=$_POST['fr_num'];
	    	$fr_addnum=$_POST['fr_addnum'];
	    	$fr_money=$_POST['fr_money'];

		    
			if($shop_brand_mode==1){
				$shop_brand 	= op_t(trim($shop_brand));
				if($shop_brand	=='') $this->error('请选择品牌!');
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
			
			$goods		 = $_POST['goods'];  //-整个商品记录-
			//$sku_title	 = $goods['sku_title'];
			$attribute_name		 = $goods['attribute_name'];
			$price		 = $goods['price'];
			$stock		 = $goods['stock'];

			if($category_id	=='') $this->error('请选择分类');
			
			if(!$tox_money_need) $tox_money_need=0;
			if(!is_numeric($tox_money_need) || $tox_money_need<0 ){
				$this->error('请填写正确的市场价格!');
			}
			
			if (!preg_match('/^(([1-9]\d*)|0)(\.\d{2})?$/', $tox_money_need)) {  
				$this->error('市场参考价格最多可保留两位小数点');  
			}
			
			
			/*********************************************/
			if($goods==null){ //-单品-
				//--为单品时，验证销售价格与库存填写是否符合要求
				if(!$market_price) $market_price=0;
				if(!is_numeric($market_price) || $market_price<0 ){
					$this->error('请填写正确的销售价格!');
				} 
				
				if (!preg_match('/^(([1-9]\d*)|0)(\.\d{2})?$/', $market_price)) {  
					$this->error('销售价格最多可保留两位小数点');  
				}
				
				if(!$goods_num) $goods_num=0;
				if(!preg_match("/^\d+$/",$goods_num)){
					$this->error("请填写正确的库存数量!");
				} 
				//--为单品时，验证销售价格与库存填写是否符合要求结束
			}
			if($goods!=null){
				/*-判断价格-*/
				$this->get_shop_sku($price,'price');
				/*判断库存*/
				$this->get_shop_sku($stock,'stock');
				/*判断名称*/
				$this->get_shop_sku($attribute_name,'attribute_name');
			}
			
			
			

			if($goods_ico	=='') $this->error('请先上传商品图片封面!');
			
			if($fr_id==0){
	    		if(!is_numeric($fr_num) || $fr_num<=0 ){
	    			$this->error('请填写正确的件数!');
	    		}
	    		if(!preg_match('/^\d*$/',$fr_num)){
	    			$this->error('请输入正确的件数,必须为正整数');
	    		}
	    	
	    		if(!is_numeric($fr_freight) || $fr_freight<=0 ){
	    			$this->error('请填写正确的运费!');
	    		}
	    		if(!preg_match('/^\d*$/',$fr_freight)){
	    			$this->error('请输入正确的运费');
	    		}
	    	
	    		if(!is_numeric($fr_addnum) || $fr_addnum<=0 ){
	    			$this->error('请填写正确输入每增加的件数!');
	    		}
	    		if(!preg_match('/^\d*$/',$fr_addnum)){
	    			$this->error('请填写正确输入每增加的件数');
	    		}
	    	
	    		if(!is_numeric($fr_money) || $fr_money<=0 ){
	    			$this->error('请填写正确输入增加产品的运费');
	    		}
	    		if(!preg_match('/^\d*$/',$fr_money)){
	    			$this->error('请填写正确输入增加产品的运费');
	    		}
	    	}
			
			if($is_new == '') $is_new = 0;
			
			if(empty($tag)){
			$this->error('请添加商品标签!');
			}
			if($goods_detail=='') $this->error('请填写商品详情');
			$tag = implode(',',$_POST['tag']);
		    
			
			$shop_goods = array(
				'siteid'		=>	SITEID,
				'goods_name'	=>	$goods_name,
				'goods_sn'		=>	$goods_sn,
				'goods_introduct'	=>	$goods_introduct,
				'fr_id'			=>$fr_id,
				'fr_freight'	=>$fr_freight,
				'fr_freight'	=>$fr_freight,
				'fr_addnum'		=>$fr_addnum,
				'fr_num'		=>$fr_num,
				'fr_money'		=>$fr_money,
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
				'status'		=>	$status,
				'is_recommend' => $is_recommend,
				'purchase_status'	=>	$purchase_status,
				'tag'			=>  $tag,
				'shop_brand'	=>  $shop_brand,
				'custom_brand_name'	=>	$custom_brand_name,
				'custom_brand_firstuc'	=>$custom_brand_firstuc,
				'custom_brand_enname'	=>$custom_brand_enname,
				'is_bargains'			=>	$is_bargains,
				'sort'			=>		$sort,
				'is_firey'		=>		$is_firey,
			);
			$shop_list	= D('shop')->add($shop_goods);
			D('Common/shop')->goodsmap('',$category_id,true);
			D('Common/shop')->get_shop_brand(true);
			D('Common/shop')->clear_index_module_s();
			if($goods==null){ //-单品-
				if($shop_list){

					$this->success('添加成功!',U('Manage/Shop/index'));
				}else{
					$this->error('添加失败');
				}
			}else{
				if($shop_list){							/**----------------------------------------------------有系统属性----颜色/尺码/大小/--------------------------------------------------**/

					$add_sku	=	$this->save_shop_sku($goods,'add',$shop_list);
					$goods_nums		=	$add_sku['goods_nums'];

					$shop_list_save	= D('shop')->where("id=".$shop_list)->save(array('goods_num'=>$goods_nums));
					if($add_sku){

						D('Common/Dynamic')->sendMessage(is_login(),'ShopAction',$goods_name,$shop_list ,U('Shop/Index/goodsDetail') );
						$this->success('发布成功!',U('Manage/Shop/index'));

					}else{
						$this->error('发布失败1');
					}
				}else{
					$this->error('发布失败!');
				
				}
			}
			/**************************************************/
			
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
	public function shop_edit($id,$shop_brand_mode=1,$category_id=0,$shop_url='',$goods_name='',$goods_sn='',$goods_detail='',$goods_ico='',$goods_pictures_id='',$tox_money_need='',$market_price='',$goods_num=0,$is_new=0,$tag='',$shop_brand=0,$purchase_status=0,$goods_introduct='',$custom_brand_name='',$custom_brand_firstuc='',$custom_brand_enname='',$distribute_type_b=0,$exhibit_status=0,$seller_price='',$distribute_desc='',$distribute_category_id='',$is_bargains=0,$status=0,$is_recommend=0,$sort='',$is_firey=0){
	
		$find_site_relation	=	D('shop_distribute_site_relation')->where('seller_id='.SITEID.' and distribute_relation_status=1 and status=1')->field('seller_id')->select();
		if($find_site_relation)	$this->error('已为全站分销商，无法进行此操作');
		
		
		$shop_item_list	=	D('Common/shop')->shop_detail($id);
		if(!$shop_item_list) $this->error('商品不存在');
			
		if(IS_POST){
		
			$goods_name	 	= op_t(trim($goods_name));
			$goods_detail	= trim($goods_detail);
			$category_id = op_t(trim($category_id));
			$goods_ico	 	= op_t(trim($goods_ico));
			$market_price 	= op_t(trim($market_price));
			$goods_introduct 	= op_t(trim($goods_introduct));
			$goods_introduct	= mb_substr($goods_introduct, 0, 99, 'utf-8');
			$tox_money_need = op_t(trim($tox_money_need));//-价格-
			$goods_num	 	= op_t(trim($goods_num)); //-数量-
			
			
			$tmp = @iconv('gbk', 'utf-8', $goods_name);
			if(!empty($tmp)){
			 $goods_name_count = $tmp;
			}
			preg_match_all('/./us', $goods_name_count, $match);
			if(count($match[0])>30){ 
				$this->error('标题字数不得超过30个字！');
			}
			if($goods_name	=='') $this->error('请输入商品名称!');
			
			$id = $_POST['id'];
			$fr_id=$_POST['rd_freight'];//显示是否包邮产品
			$fr_freight=$_POST['fr_freight'];
			$fr_addnum=$_POST['fr_addnum'];
			$fr_num=$_POST['fr_num'];
			$fr_money=$_POST['fr_money'];
			
			if($category_id	=='') $this->error('请选择分类');
			if($goods_ico  =='') $this->error('请先上传商品图片封面!');

			if($fr_id==0){
				if(!is_numeric($fr_num) || $fr_num<=0 ){
					$this->error('请填写正确的件数!');
				}
				if(!preg_match('/^\d*$/',$fr_num)){
					$this->error('请输入正确的件数,必须为正整数');
				}
			
				if(!is_numeric($fr_freight) || $fr_freight<=0 ){
					$this->error('请填写正确的运费!');
				}
				if(!preg_match('/^\d*$/',$fr_freight)){
					$this->error('请输入正确的运费');
				}
			
				if(!is_numeric($fr_addnum) || $fr_addnum<=0 ){
					$this->error('请填写正确输入每增加的件数!');
				}
				if(!preg_match('/^\d*$/',$fr_addnum)){
					$this->error('请填写正确输入每增加的件数');
				}
			
				if(!is_numeric($fr_money) || $fr_money<=0 ){
					$this->error('请填写正确输入增加产品的运费');
				}
				if(!preg_match('/^\d*$/',$fr_money)){
					$this->error('请填写正确输入增加产品的运费');
				}
			}
			
			if($id=='') $this->error('参数错误!');
			if($shop_brand_mode==1){
				$shop_brand 	= op_t(trim($shop_brand));
				if($shop_brand	=='') $this->error('请选择品牌!');
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
			
			//--验证市场价格开始--
	       if(!$tox_money_need) $tox_money_need=0;
			if(!is_numeric($tox_money_need) || $tox_money_need<0 ){
				$this->error('请填写正确的市场价格!');
			}
			
			if (!preg_match('/^(([1-9]\d*)|0)(\.\d{2})?$/', $tox_money_need)) {  
				$this->error('市场参考价格最多可保留两位小数点');  
			}
			if($oldgoods==null && $goods==null){ //-单品时----验证促销价格---
				
				if(!$market_price) $market_price=0;
				if(!is_numeric($market_price) || $market_price<0 ){
					$this->error('请填写正确的销售价格!');
				} 
				
				if (!preg_match('/^(([1-9]\d*)|0)(\.\d{2})?$/', $market_price)) {  
					$this->error('销售价格最多可保留两位小数点');  
				}
				
				if(!$goods_num) $goods_num=0;
				if(!preg_match("/^\d+$/",$goods_num)){
					$this->error("请填写正确的库存数量!");
				} 
			}
			//-单品时----验证促销价格结束---
            
			if($is_new == '') $is_new=0;
			if(empty($tag)){
				$this->error('请添加商品标签!');
			}
		    $tag = implode(',',$_POST['tag']);
			if($goods_detail=='') $this->error('请填写商品详情');
            //----验证之前天际的sku-价格与库存----
			if($oldgoods){
				/*-判断价格-*/
				$this->get_shop_sku($oldprice,'price');
				
				/*判断库存*/
				$this->get_shop_sku($oldstock,'stock');

				/****判断旧属性是否有重复属性***/
				$this->get_shop_sku($old_attribute_name,'attribute_name');

			}
			//----验证添加新的sku属性-数据---
			if($goods){
				/*-判断价格-*/
				$this->get_shop_sku($price,'price');
				
				/*判断库存*/
				$this->get_shop_sku($stock,'stock');
				
				/****判断旧属性是否有重复属性***/
				$this->get_shop_sku($attribute_name,'attribute_name');
			}

			
			$shop_goods = array(
				'siteid'		=>	SITEID,
				'goods_name'	=>	$goods_name,
				'goods_sn'		=>	$goods_sn,
				'goods_introduct'	=>	$goods_introduct,
				'category_id'	=>	$category_id,
				'fr_id'			=>$fr_id,
				'fr_freight'	=>$fr_freight,
				'fr_addnum'		=>$fr_addnum,
				'fr_num'		=>$fr_num,
				'fr_money'		=>$fr_money,
				'market_price'	=>  $market_price,
				'tox_money_need'=>  $tox_money_need,
				'goods_num'		=>	$goods_num,
				'goods_ico'		=>	$goods_ico,
				'goods_pictures_id'	=>	$goods_pictures_id,
				'is_new'		=>  $is_new,
				//'createtime'	=>	time(),
				'changetime'	=>  time(),
				'goods_detail'	=>	$goods_detail,
				'tag'			=> $tag,
				'shop_brand'	=> $shop_brand,
				'purchase_status'	=>	$purchase_status,
				'custom_brand_name'	=>	$custom_brand_name,
				'custom_brand_firstuc'	=>$custom_brand_firstuc,
				'custom_brand_enname'	=>$custom_brand_enname,
				'is_bargains'			=>	$is_bargains,
				'status'=>$status,
				'is_recommend'=>$is_recommend,
				'sort'		=>		$sort,
				'is_firey'	=>	$is_firey,
			);
            $shop_list	= D('shop')->where("id=".$id)->save($shop_goods);
			if($category_id	!=	$shop_item_list['category_id']){
				D('Common/shop')->goodsmap('',$category_id,true);	//清除缓存：查询商品基础条件
			}
			D('Common/shop')->get_shop_brand(true);	//清除缓存：'shop_getshopbrand_'.SITEID; 筛选内容里的标签以及品牌
			D('Common/shop')->goodsmap($id,'',true);	//清除缓存：查询商品基础条件
			D('Common/shop')->clear_index_module_s();

			if($oldgoods==null && $goods==null){ //-单品---
			    
				if($shop_list){
					D('Common/shop')->sku_ids_price($id,true);	//清除缓存：'shop_skuidsprice_'.SITEID.'-'.$goods_id;商品价格
					$this->success('更改成功',$shop_url);
				}else{
					$this->error('未更改商品!');
				}
				
			}else{  
				//-----当没有再添加sku时 只修改原有sku--属性----------------
				if($goods==null && $oldgoods){
					$update_sku	=	$this->save_shop_sku($oldgoods,'update',$id);
					$data_info_num	=	$update_sku['goods_nums'];
					D('shop')->where("id=".$id)->save(array('goods_num'=>$data_info_num));
					//--写入商品表格---
					if($update_sku || $shop_list){
						D('Common/shop')->sku_ids_price($id,true);	//清除缓存：'shop_skuidsprice_'.SITEID.'-'.$goods_id;商品价格
						$this->success('更改成功',$shop_url);
					}else{
						$this->error('未更改商品');
					}
						
					//-----当用户全部删除原有sku--时---并且添加新的sku--
				}elseif($oldgoods==null && $goods){
					$add_sku	=	$this->save_shop_sku($goods,'add',$id);
					$goods_nums		=	$add_sku['goods_nums'];
					D('shop')->where("id=".$id)->save(array('goods_num'=>$goods_nums));
					if($add_sku || $shop_list){
							$this->success('更改成功',$shop_url);
					}else{
							$this->error('未更改商品!');
					}
				//------修改原有的sku---并且添加新的sku-----------	
				}elseif($oldgoods && $goods){

					foreach($goods['attribute_name'] as $key =>$value){
						$oldsku_attribute_name		= $oldgoods['attribute_name'];
						foreach($oldsku_attribute_name	as $v){
							$attribute_name = $v;
							if($value==$v){
								$this->error('亲!  不能添加重复数据哦!!!');
							}
						}
					}
					unset($value);
					unset($key);
					unset($v);
					unset($k);
					$data_info_num = 0;	
					$update_sku	=	$this->save_shop_sku($oldgoods,'update',$id);
					$data_info_num	=	$update_sku['goods_nums'];
					$goods['new_sku_title'] = $sku_cods_info;
					
					//---添加新的sku----------
					$sku_add_num=0;
					$add_sku	=	$this->save_shop_sku($goods,'add',$id);
					$sku_add_num		=	$add_sku['goods_nums'];
					$goods_num	= $data_info_num+$sku_add_num;
					D('shop')->where("id=".$id)->save(array('goods_num'=>$goods_num));

					if($update_sku || $add_sku || $shop_list){
						D('Common/shop')->sku_ids_price($id,true);	//清除缓存：'shop_skuidsprice_'.SITEID.'-'.$goods_id;商品价格
						$this->success('更改成功',$shop_url);
					}else{
						$this->error('未更改商品');
					} 
				}
			}
			
		}else{

		    $shop_detail=D('shop_sku_detailed')->where('goods_id='.id)->select();
			$shop_detail	=	D('Common/shop')->shop_sku_detailed($id,'edit');
			//查询属性结束	

		    $this->assign('shop_list',$shop_item_list);
			$this->assign('shop_detail',$shop_detail);
			$this->assign('shop_sku_detail',json_encode($this->get_category($shop_item_list['category_id'])));
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
			   D('Common/shop')->clear_index_module_s();
			  $this->success('操作成功','refresh'); 
		   }else{
			   $this->error('操作失败!');
		   }
	}
	/**
	*商品推荐**最多推荐8个
	*/
	public function shop_recommend($id=0,$is_recommend=0){
		    if(is_array($id)){
				$ids = $_POST['id'];
				if(empty($id)){
					$this->error('请选择要操作的数据!');
				}
				$ids = implode(',',$ids);
				$shop_info = D('shop')->where('siteid='.SITEID)->field('id,status,is_recommend')->select();
				$shop_num	= 0;
				if($is_recommend==1){
					foreach($shop_info as $val){
						if($val['is_recommend']==1 && $val['status']==1){
							$list[]=$val;
						}
						
						if($val['status']==1 && $val['is_recommend']==0){
							$new_list[] = $val['id'];
						}

					}
					$num = 8 - count($list); //--还可以推荐条数--
				    $new_shop_save = array_intersect($new_list,$id); 
					if($num==0) $this->error('你已推荐了8条,不能再推荐了哦');
				    
					foreach(array_slice($new_shop_save,0,$num) as $v){
						$shop_num++;
						$reds = D('shop')->where(array('id'=>$v))->setField('is_recommend', $is_recommend);
					}
					
				}elseif($is_recommend==0){
					foreach($shop_info as $val){
						if($val['status']==1 && $val['is_recommend']==1){
							$new_list[] = $val['id'];
						}

					}
				    $new_shop_save = array_intersect($new_list,$id);
					foreach($new_shop_save as $v){
						    $shop_num++;
						    $reds = D('shop')->where(array('id'=>$v))->setField('is_recommend', $is_recommend);
					}
					
				}
				
				
				
			}else{

				if($id=='') $this->error('请选择要操作的数据!');
				if($is_recommend==1){
					$shop_info = D('shop')->where(array('siteid'=>SITEID,'status'=>array('eq',1),'is_recommend'=>1))->field('id,status,is_recommend')->select();
					$shop_num = 0;
					if(count($shop_info)<=8){
						 $shop_num++;
						 //---禁用不能推荐--
						 $reds = D('shop')->where(array('id'=>$id,'status'=>1))->setField('is_recommend', $is_recommend);
					     if(!$reds){
							 $this->error('商品禁用,不能推荐!');
						 }
					}else{
						$this->error('你已推荐了8条,不能再推荐了哦');
					}
					
					
				}else{
					
					$reds = D('shop')->where(array('id'=>$id))->setField('is_recommend', $is_recommend);
				}
			
			}
		D('Common/shop')->clear_index_module_s();
		$reds ? $this->success('设置成功'.$shop_num.'条', $_SERVER['HTTP_REFERER']):$this->error('设置失败!');
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
	
	/***************设置特价页面************************/
	  public function shop_bargains_add(){
		    $goods_name = I('goods_name');
			$map['status']=array('egt','0');
			$map['siteid']=SITEID;
			if($goods_name !=''){
				if (is_numeric($goods_name)) {
					$map['id|goods_name'] = array(intval($goods_name), array('like', '%' . $goods_name . '%'), '_multi' => true);
				} else {
					$map['goods_name'] = array('like', '%' . (string)$goods_name . '%');
				}
			}
		
			$list = D('shop')->where($map) ->order("id desc")->select();
			foreach($list as $key=>$val){
				$list[$key]['market_price']	=	D('Common/shop')->sku_ids_price($val['id']);
				$map_bargain	=	'goods_id='.$val['id'].' and overtime>'.time().' and surplus_num>0';
				$list[$key]['is_bargain'] = D('shop_bargain')->where($map_bargain)->getField('id');
			}
			$this->assign('now',time());
			$this->assign('datainfo',$list);
			$this->display();
		
	}
	
	/***************设置特价************************/
	 public function add_shop_bargains(){
		$goods_arr	 = $_POST['goods']; 
		if(!$goods_arr){
			$this->error('未设置信息');
		}
		foreach($goods_arr as $key=>$val){
			foreach($val as $k=>$v){
				$bargain_goods[$k][$key]=$v;
			}
		}
		unset($key);unset($val);unset($k);unset($v);
		foreach($bargain_goods as $key=>$val){
		/***********判定开始********************/
			
			$goods	=	D('shop')->where('id='.$val['goods_id'])->find();
			
			$sku_ids = D('shop_sku_detailed')->where('goods_id='.$val['goods_id'])->field('sku_id')->select();
			
			if($sku_ids){
				$sku_price = D('shop_sku_detailed')->where('goods_id='.$val['goods_id'])->field('price')->select();
			}
			
			$bargain_goods[$key]['bargain_num']	=	$goods['goods_num'];
			$starttime = strtotime($val['starttime']);
			$endtime = strtotime($val['endtime']);
			
			if(time()	>	$endtime){
				$this->error($goods['goods_name'].'设置的结束时间不能早于目前时间');
			}
			if($starttime	>	$endtime){
				$this->error($goods['goods_name'].'设置的开始时间不能早于结束时间');
			}
			if(!$val['bargain_price']){
				
			}
			if(!is_numeric($val['bargain_price']) || $val['bargain_price']<0 ){
				$this->error($goods['goods_name'].'设置的特价格式不正确');
			}
			
			if (!preg_match('/^(([1-9]\d*)|0)(\.\d{2})?$/', $val['bargain_price'])) {  
				$this->error($goods['goods_name'].'设置的特价最多可保留两位小数点');  
			}
			$map	=	'siteid='.SITEID.' and goods_id='.$val['goods_id'].' and starttime<'.time().' and overtime>'.time().' and surplus_num>0';
			
			$find_shop_bargain	=	D('shop_bargain')->where($map)->find();
			
			if($find_shop_bargain){
				$this->error($goods['goods_name'].'正在进行特价活动，无法重复添加');
			}
			
		}	
		unset($key);unset($val);
		/***********判定结束********************/
		foreach($bargain_goods as $key=>$val){
			
			$starttime = strtotime($val['starttime']);
			$endtime = strtotime($val['endtime']);
			if(time()	>	$starttime){
				$starttime	=	time();
			}
		
			$bargain_arr	=	array(
				'goods_id'	=>	$val['goods_id'],
				'bargain_price'	=>	$val['bargain_price'],
				'starttime'		=>	$starttime,
				'overtime'		=>	$endtime,
				'bargain_num'	=>	$val['bargain_num'],
				'surplus_num'	=>	$val['bargain_num'],
				'siteid'		=>	SITEID,
			);
			$shop_bargain	=	D('shop_bargain')->add($bargain_arr);
		}
		if($shop_bargain){
			$this->success('设置成功',U('Manage/Shop/shop_bargains_list'));
		}else{
			$this->error('未设置');
		};
		

	 }
	/***************特价列表************************/
	  public function shop_bargains_list(){
			$map	=	'siteid='.SITEID.' and overtime>'.time().' and surplus_num>0';
		
			$shop_bargain_list = D('shop_bargain')->where($map) ->order("overtime desc")->select();
			
			foreach($shop_bargain_list as $key=>$val){
			
				$goods_item	=	D('shop')->where('siteid='.SITEID.' and id='.$val['goods_id'])->find();
				$shop_bargain_list[$key]['goods_name']	=	$goods_item['goods_name'];
				$shop_bargain_list[$key]['category_id']	=	$goods_item['category_id'];
				
				$shop_bargain_list[$key]['market_price']	=	D('Common/shop')->sku_ids_price($val['goods_id']);
				$shop_bargain_list[$key]['goods_num']		=	$goods_item['goods_num'];
			}
			$this->assign('now',time());
			$this->assign('datainfo',$shop_bargain_list);
			$this->display();
	}
	
	
	/***************取消特价************************/
	public function remove_shop_bargains($id=''){
		/**************判定开始***************/
		if(!$id) $this->error('参数错误');
		/*$map	=	'siteid='.SITEID.' and id='.$id.' and starttime<'.time().' and overtime>'.time().' and surplus_num>0';
		$find_shop_bargains	=	D('shop_bargain')->where($map)->find();
		if($find_shop_bargains) $this->error('参数错误');*/
		/**************判定结束***************/
		$remove_shop_bargains	=	D('shop_bargain')->where('id='.$id)->delete();
		if($remove_shop_bargains){
			$this->success('操作成功');
		}else{
			$this->error('操作失败');
		}
	}
	public function get_shop_sku($arr,$action){
		/*-判断价格-*/
		 switch ($action){
			case 'price':
				for($i=0;$i<count($arr);$i++){
					$j=$i+1;
					if($arr[$i]==''){
						$this->error("请填写商品规格第 " .$j. " 条数据价格!");
					  exit;
					}
				if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $arr[$i])) {  
						$this->error('商品规格第 '.$j.'条价格最多可保留两位小数点!');
						exit;							
					}
				}
				unset($j);
			break;
			/*判断库存*/
			case 'stock':
				for($i=0;$i<count($arr);$i++){
					 $j=$i+1;
					if($arr[$i] ==''){
						$this->error('请填写第 '.$j.' 条商品库存数量!!!');
					  exit;
					}	
					if(!preg_match("/^\+?[0-9][0-9]*$/",$arr[$i])){
						$this->error('商品第'.$j.'库存请输入整数!');
						exit;
					}
					 
				}
				unset($j);
			break;
			case 'attribute_name':
			/**************** 商品规格（简版）********************/
				for($i=0;$i<count($arr);$i++){	//判断是否有重复属性
					for($j=0;$j<count($arr);$j++){
						if($arr[$i]==$arr[$j]){
							if($i!=$j){
								$this->error('请勿重复添加商品规格');
								exit;
							}
						}
					}
					if(!$arr[$i]){
						$this->error('商品规格名称不得为空');
						exit;
					}
				}
				unset($j);
			break;
		}
	}
	public function save_shop_sku($goods,$action,$id){
		$sku_types_id = D('shop_sku_types_system')->where('is_system=0')->getField('sku_types_id');
		if(!$sku_types_id){
			$add_sku_types['types_name'] = "规格"; 
			$add_sku_types['is_system'] = 0;
			$sku_types_id = D('shop_sku_types_system')->add($add_sku_types);
		}
		switch ($action){
			case 'add':
				
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
					$attribute_add['sort']=$arr0[$key]['sort'];
					$attribute_id = D('shop_sku_types_attribute_stystem')->add($attribute_add);
					$arr0[$key]['attribute_id'] = $attribute_id;
					$goods['sku_title'][$sku_types_id][$key]=$attribute_id;
				}
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

				//--判断是否有重复数据--
				if(count($goods['new_sku_title']) !=count(array_unique($goods['new_sku_title']))){
					$this->error('亲!  不能添加重复数据哦!!!');
				}
				$goods['new_sku_title'] = $sku_cods_info;
				
				unset($goods['sku_title']);
				unset($key);
				unset($val);
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
					$data['goods_id']	=	$id;
					$data['sku_title']	=	json_encode($arr2[$key]['new_sku_title']);
					$data['price']		=	$arr2[$key]['price'];
					$data['stock']		=	$arr2[$key]['stock'];
					$data['sku_sn']		=	$arr2[$key]['sku_sn'];
					$goods_nums		   +=	$arr2[$key]['stock'];//-总数-
					$shop_sku_detail    =   D('shop_sku_detailed')->add($data);
				}
				
				unset($arr2);
				unset($key);
				unset($val);
				
				$shop_sku_list	=	D('shop_sku_detailed')->where("goods_id=".$id)->field("sku_id,goods_id,sku_title,sku_sn")->select();
				$data_sku=array();
				foreach($shop_sku_list as $key=>&$val){
					$val['sku_title']=json_decode($val['sku_title'],true);
					foreach($val['sku_title'] as $k=>$v){
						 $news[$key][$k]['sku_id']		   = $val['sku_id'];
						 $news[$key][$k]['goods_id']	   = $val['goods_id'];
						 $news[$key][$k]['attribute_name'] = get_shop_sku_types_attribute($v['value']);
						 $news[$key][$k]['attribute_value'] = $v['value'];
						 $news[$key][$k]['types_id']  = $v['type_id'];
						 $news[$key][$k]['sort']  = D('shop_sku_types_attribute_stystem')->where('attribute_id='.$v['value'])->getField('sort');
						 $news[$key][$k]['sku_sn']	  = $val['sku_sn'];
					}
					
					foreach($news[$key] as $tp=>$temp){
						$data_sku['siteid']				=   SITEID;	
						$data_sku['sku_id']				=	$news[$key][$tp]['sku_id'];
						$data_sku['attribute_name'] 	=   $news[$key][$tp]['attribute_name'];
						$data_sku['attribute_value']	=	$news[$key][$tp]['attribute_value'];
						$data_sku['goods_id']			=   $news[$key][$tp]['goods_id'];
						$data_sku['types_id']			=   $news[$key][$tp]['types_id'];
						$data_sku['sort']				=   $news[$key][$tp]['sort'];
						$data_sku['sku_sn']				=   $news[$key][$tp]['sku_sn'];
						$data_sku['is_system']			=	1;                              //--是否系统--
					
						$shop_sku_list_display		    =	D('shop_sku_detailed_display')->add($data_sku);
					}	
				}
				if($shop_sku_list_display){
					$result['goods_nums']		=	$goods_nums;
					$result['res']		=	true;
					return $result;
				}
			break;
			case 'update';
				foreach($goods as $key =>$val){
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
					$old_attribute['sort']				=	$oldsgoods_save[$key]['sort'];
					
					$shop_attribute_save  =   D('shop_sku_types_attribute_stystem')->save($old_attribute);
					$data_info['price']		=	$oldsgoods_save[$key]['price'];
					$data_info['stock']		=	$oldsgoods_save[$key]['stock'];
					$data_info['sku_sn']	=	$oldsgoods_save[$key]['sku_sn'];
					$data_info_num		   +=	$oldsgoods_save[$key]['stock'];//-总数-
					$shop_sku_detail_save   =   D('shop_sku_detailed')->where("sku_id=".$oldsgoods_save[$key]['sku_id'])->save($data_info);	
					

					$old_data_sku['sku_id']				=	$oldsgoods_save[$key]['sku_id'];
					$old_data_sku['attribute_name'] 	=   $oldsgoods_save[$key]['attribute_name'];
					$old_data_sku['sku_sn'] 			=   $oldsgoods_save[$key]['sku_sn'];
					$old_data_sku['sort'] 				=   $oldsgoods_save[$key]['sort'];
					$shop_sku_list_display		    =	D('shop_sku_detailed_display')->where("sku_id=".$oldsgoods_save[$key]['sku_id'])->save($old_data_sku);
					
				}
				unset($key);
				unset($val);
				unset($k);
				unset($v);
				$result['goods_nums']		=	$data_info_num;
				$result['res']		=	true;
				return $result;

			break;
		}
	}
	
}  

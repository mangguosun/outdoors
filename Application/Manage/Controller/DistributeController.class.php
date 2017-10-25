<?php
namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;

class DistributeController extends BaseController
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
	public function authority($siteid=SITEID){
		
		$shop_authority	=	D('distribution_authority')->where('siteid='.$siteid.' and shop_authority=2')->find();
		if(!$shop_authority){
			if($siteid==SITEID){
				$this->error('您没有入驻商品集市，请到集市首页申请入驻！',U('Manage/Distribute/index'),3);
			}else{
				$this->error('该商家没有入驻商品集市，请选择其他商品！',U('Manage/Distribute/index'),3);
			}
		}
	}
	
	public function has_a_relation($siteid=SITEID){
	//判断是否已经为全站合作状态
		$has_a_relation	=	D('shop_distribute_site_relation')->where('seller_id='.SITEID.' and distribute_relation_status=1 and status=1')->find();	
		if($has_a_relation) $error['error1']	=	1; /*$this->error('已为全站分销商');*/
		
		//判断是否正在申请全站分销
		$has_relation_a_apply	=	D('shop_distribute_relation_a_apply')->where('seller_id='.SITEID.' and examine_status=1')->find();
		if($has_relation_a_apply)  $error['error2']	=	1;   /*$this->error('已提交过全站分销申请');*/
		return $error;
	}	
		
	public function index(){ //r
		$status	=	D('distribution_authority')->where('siteid='.SITEID.' and shop_authority=2')->find();//判断是否有分销权限
		$this->assign('status',$status);
		$this->display();
	}
	//填写申请信息
	public function site_distribute_apply($qq="",$phone="",$license_phone="",$license_photo="",$name="",$idcard="",$idcardnumber="",$idcard_photo="",$auto_renewal=1,$agreement=""){
		$has_a_relation	=	$this->has_a_relation();
		if($has_a_relation['error1']){$this->error('已为全站分销商,无法申请供货权限，请先取消');}
		if($has_a_relation['error2']){$this->error('已提交过全站分销申请,无法申请供货权限，请先取消');}
		$supplier_agreement	=	D('shop_distribute_config')->where('name="SUPPLIER_AGREEMENT"')->getField('value');
		if(IS_POST){	//r
		/*************判定部分************************/
			if($agreement==""){
				$this->error("您还未接受供应商活动力云系统分销条款协议");
			}
			if($license_photo==""){
				$this->error("请上传许可证照");
			}
			if($name==""){
				$this->error("请填写联系人姓名");
			}
			if($idcardnumber==""){
				$this->error("请填写证件号码");
			}
			$this->check_card($idcardnumber);
			if($idcard_photo==""){
				$this->error("请上传证件照");
			}
			if($qq==""){
				$this->error("请填写QQ号码");
			}
			$this->check_qq($qq);
			if($phone==""){
				$this->error("请填写电话号码");
			}
			$this->checkTelphone($phone);
			if(!$auto_renewal) $auto_renewal=0;
			/*************判定部分结束*********************/
			$siteid	=	SITEID;	//商家站点ID
			$qq		=	op_t(trim($_POST['qq']));	//QQ	
			$phone		=	op_t(trim($_POST['phone']));	//电话
			$information = array(
				'siteid'	=>	$siteid,
				'qq'		=>	$qq,
				'phone'		=>	$phone,
				'license_photo'	=>	$license_photo,
				'distribute_apply_sn'	=>	create_sn(),
				'apply_time'	=>	time(),
				'name'			=>	$name,
				'idcard'		=>	$idcard,
				'idcardnumber'	=>	$idcardnumber,
				'idcard_photo'	=>	$idcard_photo,
				'distribute_status'	=>	1,
				'apply_mode'	=>	1,
				'auto_renewal'	=>	$auto_renewal,
				'supplier_agreement'	=>	$supplier_agreement,
			);
			$add_distribute	=	D('shop_distribute_site_apply')->add($information);
				if($add_distribute){
					$this->success('提交申请成功!',U('Manage/Distribute/apply_list'));
				}
		}else{
			/*************判定部分************************/
			$shop_authority	=	D('distribution_authority')->where('siteid='.SITEID.' and shop_authority=2')->find();
			if($shop_authority)	$this->error("已审批过",U('Manage/Distribute/apply_list'),3);
			$distribute_status	=	D('shop_distribute_site_apply')->where('siteid='.SITEID.' and (distribute_status=1 or distribute_status=2)')->find();
			if($distribute_status)	$this->error("已提交过申请",U('Manage/Distribute/apply_list'),3);
			/*************判定部分结束*********************/
			$webinfo = json_decode(WEBSITEINFO,true);	//商家已填信息
							
			$address = get_citys($webinfo['club_address']);	//换算地区
			$shop_address	=	D('Distribute')->shop_address($address);
			$this->assign('shop_address',$shop_address);
			$bank	=	D('websit_account_record')->where('siteid='.SITEID)->find();	//账户信息
			$this->assign('bank',$bank);
			$this->assign('webinfo',$webinfo);
			
			
			
			$this->assign('supplier_agreement',$supplier_agreement);
			$this->assign('time',time());
			$this->display();
		}
	}
	
	//编辑分销商家信息
	public function distribute_site($qq="",$license_phone="",$site_ico="",$keyword="",$desc=""){
		if(IS_POST){	//r
			$siteid	=	SITEID;	//商家站点ID
			$qq		=	op_t(trim($_POST['qq']));	//QQ	
			$phone		=	op_t(trim($_POST['phone']));	//电话
			$keyword	=	op_t(trim($_POST['keyword']));
			$information = array(
				'siteid'	=>	$siteid,
				'qq'		=>	$qq,
				'phone'		=>	$phone,
				'site_ico'	=>	$site_ico,
				'keyword'	=>	$keyword,
				'create_time'	=>	time(),
				'desc'		=>	$desc
			);
			$has_distribute	=	D('shop_distribute_site')->where('siteid='.$siteid)->find();
			if(!$has_distribute){
				$add_distribute	=	D('shop_distribute_site')->add($information);
				if($add_distribute){
					$this->success('发布成功!',U('Manage/Distribute/index'));
				}
			}else{
				unset($information['create_time']);
				$information['update_time']	=	time();
				$add_distribute	=	D('shop_distribute_site')->where('siteid='.SITEID)->save($information);
				if($add_distribute){
					$this->success('发布成功!',U('Distribute/distribute_site'));
				}else{
					$this->error('信息未更改!');
				}
			}
		}else{
			$edit_distribute_site	=	D('shop_distribute_site')->where('siteid='.SITEID)->find();
			if($edit_distribute_site){
				$this->assign('edit_distribute_site',$edit_distribute_site);
			}
			$this->display();
		}
	}
	
	//分销商家申请供货权限列表
	public function company_list(){	//r
		
		$siteid_arr = D('distribution_authority')->where('shop_authority=2')->field('siteid')->select();
		foreach($siteid_arr as $v){
			if($siteids==''){
				$siteids	=	$v['siteid'];
			}else{
				$siteids	=	$siteids.",".$v['siteid'];
			}
		}
		$map['siteid'] = array('in',$siteids);
		if($map){
			$count	=	D('websit')->where($map)->count();
		
			$Page       = new \Think\Page($count,10);
		
			$show       = $Page->show();// 分页显示输出

			$company	=	D('websit')->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();	//查询申请，查询哪些商家有供货权限，
			foreach($company as $key=>$val){
				$company[$key]['goods_count']	=	D('shop_distribute_item')->where('siteid='.$val['siteid'].' and is_distribute=1 and (distribute_type_a=1 or distribute_type_b=1)')->count();	//计算全站分销商品总数
				
				$address = get_citys($company[$key]['club_address']);	//换算地区
				$shop_address	=	D('Distribute')->shop_address($address);
				$company[$key] = array_merge_recursive($company[$key], $shop_address);
			}
			$supplier_id_a	=	D('shop_distribute_site_relation')->where('seller_id='.SITEID.' and distribute_relation_status=1 and status=1')->field('supplier_id')->find();
		}
		$this->assign('page',$show);
		$this->assign('supplier_id_a',$supplier_id_a);
		$this->assign('company',$company);	
		$this->display();
	}
	
	
	public function company_detail($siteid=''){	//r
		/************判定部分：判定SITEID是否存在，不满足条件跳到自己的页面*****************/
		if($siteid==''){
			$siteid	=	SITEID;
		}
		$website	=	D('Distribute')->get_distribute_website($siteid);
		if(!$website){
			$this->error('该商家不存在');
		}

		/**************判定部分结束***************/
		$company	=	D('shop_distribute_site')->where('siteid='.$siteid)->find();
		
		$company_goods_ids	=	D('shop_distribute_item')->where('siteid='.$siteid.' and is_distribute=1')->select();
		foreach($company_goods_ids as $key=>$val){
			$company_goods	=	D('shop')->where('id='.$val['goods_id'])->find();
			$company_goods_ids[$key]['goods_ico']	=	$company_goods['goods_ico'];
			$company_goods_ids[$key]['goods_name']	=	$company_goods['goods_name'];
			$company_goods_ids[$key]['market_price']	=	D('Common/shop')->sku_ids_price($val['goods_id']);
			$company_goods_ids[$key]['has_item_relation']	=	D('Distribute')->has_item_relation($val['goods_id'],$val['siteid'],SITEID);
		}
		$company['count_a']	=	D('shop_distribute_item')->where('siteid='.$siteid.' and is_distribute=1 and distribute_type_a=1')->count();	//计算全站分销商品总数
		$company['count_b']	=	D('shop_distribute_item')->where('siteid='.$siteid.' and is_distribute=1 and distribute_type_b=1')->count();	//计算单品分销商品总数
		//dump($company);
		$this->assign('shop_address',$website['shop_address']);
		$this->assign('company',$company);	
		$this->assign('website',$website);
		$this->assign('company_goods',$company_goods_ids);
		$this->display();
	}
	
	//商家申请供货权限列表
	public function apply_list(){
		$distribution_authority	=	D('distribution_authority')->where('siteid='.SITEID)->find();
		$apply_list	=	D('shop_distribute_site_apply')->where('siteid='.SITEID)->order('apply_time desc')->select();
		
		$this->assign('authority',$distribution_authority);
		$this->assign('datainfo',$apply_list);
		$this->display();
	}
	//申请内容详情
	public function apply_detail($id){	//r
		/************判定开始************/
		if(!$id){
			$this->error("无该信息");
		}
		$apply_detail	=	D('shop_distribute_site_apply')->where('id='.$id.' and siteid='.SITEID)->find();
		if(!$apply_detail){
			$this->error('无该信息');
		}
		/************判定开始结束************/
		$webinfo	=	D('Distribute')->get_distribute_website($apply_detail['siteid']);
		$bank	=	D('websit_account_record')->where('siteid='.$apply_detail['siteid'])->find();
		$supplier_agreement	=	D('shop_distribute_config')->where('name="SUPPLIER_AGREEMENT"')->getField('value');
		$this->assign('supplier_agreement',$supplier_agreement);
		$this->assign('shop_address',$webinfo['shop_address']);
		$this->assign('bank',$bank);
		$this->assign('webinfo',$webinfo);
		$this->assign('apply_detail',$apply_detail);
		$this->assign('user',$this->userdata);
		$this->display();
	}
	//个人的所有商品列表
    public function goods_list(){	//r
		$this->authority();
		$goods_name = I('goods_name');
		$map['status']=array('gt','0');
		$map['siteid']=SITEID;
		if($goods_name !=''){
			$goods_name	=	urlsafe_b64decode($goods_name);
			$this->assign('goods_name',$goods_name);
			$map['goods_name'] = array('like', '%' . (string)$goods_name . '%');
		}

		$count = D('shop')->where($map)->count();
		$Page       = new \Think\Page($count,10);
		$show       = $Page->show();// 分页显示输出
		$list = D('shop')->where($map) ->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($list as $key=>$val){
			$list[$key]['market_price']	=	D('Common/shop')->sku_ids_price($val['id']);
			
			$shop_distribute_item = D('shop_distribute_item')->where('goods_id='.$val['id'])->find();
			$list[$key]['supplier_id']	=	$shop_distribute_item['supplier_id'];
			$list[$key]['is_distribute']	=	$shop_distribute_item['is_distribute'];
			$list[$key]['distribute_type_a']	=	$shop_distribute_item['distribute_type_a'];
			$list[$key]['distribute_type_b']	=	$shop_distribute_item['distribute_type_b'];
			$list[$key]['seller_price']	=	$shop_distribute_item['seller_price'];
		}
		$this->assign('datainfo',$list);
		$this->assign('page',$show);
		$this->display();
	
	}
	
	public function seek($goods_name){
		$seek = op_t(I('goods_name'));
		if($seek!=''){
			$seek	=	urlsafe_b64encode($seek);
		$url=U('Manage/Distribute/goods_list',array('goods_name'=>$seek));
			header("Location:$url");
		}else{
			$this->error('请填写查询内容');
		}
	}
	
	//设置分销信息
	public function goods_edit($id='',$seller_price=0,$distribute_type='',$distribute_category_id='',$is_distribute='0',$distribute_desc=''){	//r
		
		if(IS_POST){
			/***********判定部分*****************/
			$shop_list = D('shop')->where("id=".$id." and siteid=".SITEID)->find();
			if($id=='' || !$shop_list){		//如果未传过来ID或者无此信息，跳转到其他页面
				$this->error('商品信息错误');
			}
			if(!preg_match("/^\+?[0-9][0-9]*$/",$seller_price)){
				$this->error("请填写正确的分销佣金!");
			}
			if($seller_price>100){$this->error("请填写正确的分销佣金!");}
			if(!$distribute_category_id){		
				$this->error('请选择商品分销分类');
			}
			if(!$distribute_desc){		//
				$this->error('请填写分销详情');
			}
			if(!$distribute_type){		//
				$this->error('请选择分销类型');
			}
			
			/***********判定部分结束*****************/
			$distribute_type_a	=	0;
			$distribute_type_b	=	0;
			foreach($distribute_type as $val){
				if($val==0){
					$distribute_type_b	=	1;
				}elseif($val==1){
					$distribute_type_a	=	1;
				}
			}
			
			$distribute_item_arr	=	array(
				'goods_id'		=>	$id,
				'seller_price'	=> $seller_price,
				'siteid'	=> $shop_list['siteid'],
				'distribute_type_a'			=> $distribute_type_a,
				'distribute_type_b'			=> $distribute_type_b,
				'distribute_category_id'	=> $distribute_category_id,
				'distribute_desc'			=> $distribute_desc,
				'is_distribute'				=>$is_distribute,
			);
			$shop_distribute_item	=	D('shop_distribute_item')->where('goods_id='.$id)->find();
			if($shop_distribute_item){
				$update_shop_distribute_item	=	D('shop_distribute_item')->where('goods_id='.$id)->save($distribute_item_arr);
			}else{
				$update_shop_distribute_item	=	D('shop_distribute_item')->add($distribute_item_arr);
			}
			if($update_shop_distribute_item){
				if($distribute_item_arr['distribute_type_a']){	//若设置该商品全站分销，给所有全站分销商上架
					$find_site_relation	=	D('shop_distribute_site_relation')->where('supplier_id='.SITEID.' and distribute_relation_status=1')->field('seller_id')->select();
					foreach($find_site_relation as $key=>$val){
						$item_relation_arr	=	array(
							'goods_id'	=>	$id,
							'supplier_id'	=>	SITEID,
							'seller_id'		=>	$val['seller_id'],
							'apply_time'		=>	time(),
							'apply_status'	=>	1,
							'distribute_relation_status'	=>	1,
						);
						D('Common/shop')->cc_goodsmap_s($val['seller_id']);
						D('Common/shop')->cc_goodsmap_s($val['seller_id'],$id);
						D('Common/shop')->cc_goodsmap_s($val['seller_id'],'','other');
						D('Common/shop')->clear_index_module_s($val['seller_id']);
						$add_item_relation	=	D('shop_distribute_item_relation')->add($item_relation_arr);
						$has_item_relation_pool	=	D('shop_distribute_item_relation_pool')->where('goods_id='.$id.' and supplier_id='.SITEID.' and seller_id='.$val['seller_id'])->find();	
						if(!$has_item_relation_pool){
							$distribute_item_relation_pool	=	D('shop_distribute_item_relation_pool')->add($item_relation_arr);
						}
					}
				}else{
					$remove_distribute_items	=	D('shop_distribute_item_relation')->where('goods_id='.$id.' and supplier_id='.SITEID)->delete();
					$find_site_relation	=	D('shop_distribute_site_relation')->where('supplier_id='.SITEID)->field('seller_id')->select();
					foreach($find_site_relation as $key=>$val){
						D('Common/shop')->cc_goodsmap_s($val['seller_id']);
						D('Common/shop')->cc_goodsmap_s($val['seller_id'],$id);
						D('Common/shop')->cc_goodsmap_s($val['seller_id'],'','other');
						D('Common/shop')->clear_index_module_s($val['seller_id']);
					}
				}
				$this->success('设置成功',U('Manage/Distribute/goods_list'));
			}else{
				$this->error('未修改设置');
			}
		}else{
			/*********分销信息**********************/
			$data	=	D('Distribute')->shopdetail($id);
			if($data['shop_distribute_item']['is_distribute']) $this->error('该商品在集市中，修改前请先从集市下架');
			if(!$data['shop_distribute_item']){$data['shop_distribute_item']['is_distribute']=1;}
			//查询属性结束	
			$this->assign('pictures',$data['pictures']);
			$this->assign('shop_distribute_item',$data['shop_distribute_item']);	
		    $this->assign('shop_list',$data['shop_list']);
			$this->assign('shop_detail',$data['shop_detail']);

			$this->display();
		}
	}
	
	//添加至分销集市
    public function add_to_distribute($id=''){	//r
		/*************
			主判定部分
				区分未设置分销模式/未设置分销佣金的商品
		************************/
		$this->authority();
		if($id=='') $this->error('请正确选择您要发布的商品');
		$goods_distribute_item	=	D('shop_distribute_item')->where('goods_id='.$id.' and siteid='.SITEID.' and (distribute_type_b=1 or distribute_type_a=1) and seller_price>0')->find();
		if(!$goods_distribute_item) $this->error('该商品未设置分销信息');
		if($goods_distribute_item['is_distribute']==0){
			$update_arr['is_distribute']	=	1;
			$copy="您的商品已发布到商品集市中！";
			$url=U('Manage/Distribute/goods_list');
			
			if($goods_distribute_item['distribute_type_a']){	//若设置该商品未全站分销，给所有全站分销商上架
				$find_site_relation	=	D('shop_distribute_site_relation')->where('supplier_id='.SITEID.' and distribute_relation_status=1')->field('seller_id')->select();
				foreach($find_site_relation as $key=>$val){
					$item_relation_arr	=	array(
						'goods_id'	=>	$id,
						'supplier_id'	=>	SITEID,
						'seller_id'		=>	$val['seller_id'],
						'apply_time'		=>	time(),
						'apply_status'	=>	1,
						'distribute_relation_status'	=>	1,
					);
					$add_item_relation	=	D('shop_distribute_item_relation')->add($item_relation_arr);
					D('Common/shop')->cc_goodsmap_s($val['seller_id']);
					D('Common/shop')->cc_goodsmap_s($val['seller_id'],$id);
					D('Common/shop')->cc_goodsmap_s($val['seller_id'],'','other');
					D('Common/shop')->clear_index_module_s($val['seller_id']);

					$has_item_relation_pool	=	D('shop_distribute_item_relation_pool')->where('goods_id='.$id.' and supplier_id='.SITEID.' and seller_id='.$val['seller_id'])->find();	
					if(!$has_item_relation_pool){
						$distribute_item_relation_pool	=	D('shop_distribute_item_relation_pool')->add($item_relation_arr);
					}
				}
			}
		
			
		}elseif($goods_distribute_item['is_distribute']==1){
			$update_arr['is_distribute']	=	0;
			$copy="您的商品已从商品集市中移除！";
			$url=U('Manage/Distribute/goods_list');

			/******************删除合作中的商品**************************/
			
			$remove_distribute_items	=	D('shop_distribute_item_relation')->where('goods_id='.$id.' and supplier_id='.SITEID)->delete();
			$find_site_relation	=	D('shop_distribute_site_relation')->where('supplier_id='.SITEID)->field('seller_id')->select();
			foreach($find_site_relation as $key=>$val){
				D('Common/shop')->cc_goodsmap_s($val['seller_id']);
				D('Common/shop')->cc_goodsmap_s($val['seller_id'],$id);
				D('Common/shop')->cc_goodsmap_s($val['seller_id'],'','other');
				D('Common/shop')->clear_index_module_s($val['seller_id']);
			}
			
			/*******************删除合作中的商品结束**********************/
		}
		/*************主判定部分结束************************/

		$update_arr['create_time']	=	time();
		$add_to_distribute	=	D('shop_distribute_item')->where('goods_id='.$id)->save($update_arr);
		if(!$add_to_distribute) $this->error('设置失败');
		$this->success($copy,$url);
	}
	
	//批量添加至分销集市
    public function batch_add_to_distribute($ids=''){	//r
		/*************
			判定部分
				区分未设置分销模式/未设置分销佣金的商品
		************************/
		$this->authority();
		if($ids=='') $this->error('请勾选您要发布的商品');
		$i=0;
		foreach($ids as $key=>$val){
			$goods_distribute_item	=	D('shop_distribute_item')->where('goods_id='.$val.' and siteid='.SITEID.' and (distribute_type_b=1 or distribute_type_a=1) and seller_price>0')->find();
			if(!$goods_distribute_item){
				unset($ids[$key]);
				$unset_ids[$key]=$val;
				$i++;
			}
		}
		if($unset_ids) $this->error('选中项中有'.$i.'项未设置分销信息,请设置后再进行操作');
		unset($key);unset($val);
		/*************判定部分结束************************/
		$update_arr['is_distribute']	=	1;
		$update_arr['create_time']	=	time();
		foreach($ids as $v){
			$batch_add_to_distribute	=	D('shop_distribute_item')->where('goods_id='.$v)->save($update_arr);
			$goods	=	D('shop_distribute_item')->where('goods_id='.$v)->find();
			if($goods['distribute_type_a']){	//若设置该商品未全站分销，给所有全站分销商上架
					$find_site_relation	=	D('shop_distribute_site_relation')->where('supplier_id='.SITEID.' and distribute_relation_status=1')->field('seller_id')->select();
					foreach($find_site_relation as $key=>$val){
						$item_relation_arr	=	array(
							'goods_id'	=>	$v,
							'supplier_id'	=>	SITEID,
							'seller_id'		=>	$val['seller_id'],
							'apply_status'	=>	1,
						);
						$add_item_relation	=	D('shop_distribute_item_relation')->add($item_relation_arr);
					}
				}
		}
		$this->success('您的商品已发布到商品集市中！',U('Manage/Distribute/goods_list'));
	}
	
	
	//集市商品列表
	public function goods($category_id=''){	//r  精选单品页
		/************查数据库条件***************/
		$map	=	D('Distribute')->goodsdistributemap($category_id);
		/************查数据库条件***************/
		if($map){
			$count = D('shop')->where($map)->count();
			$Page       = new \Think\Page($count,10);
			$show       = $Page->show();// 分页显示输出
			$list = D('shop')->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
			//dump(D('shop')->getLastSql());
			/************查询条件****************************/
			foreach($list as $key=>$val){
				$list[$key]['has_item_relation']	=	D('Distribute')->has_item_relation($val['id'],$val['siteid'],SITEID);
				$list[$key]['goods_id']	=	$val['id'];
				$list[$key]['market_price']	=	D('Common/shop')->sku_ids_price($val['id']);
				$list[$key]['seller_price'] = D('shop_distribute_item')->where('goods_id='.$val['id'])->getField('seller_price');
				$website	=	D('Distribute')->get_distribute_website($val['siteid']);
				$list[$key]['webname']		= $website['webname'];
				$list[$key]['domain']		= $website['domain'];
				$list[$key] = array_merge_recursive($list[$key], $website['shop_address']);
			}
		}
		/**************查询分类*******************/
		 $map2  = array('status' => array('gt', -1),'is_distribute' => 1,'pid'=>0);
		$is_distribute_category = D('shop_category')->where($map2)->order('sort desc')->select();
		foreach($is_distribute_category as $k=>$v){
			$map3  = array('status' => array('gt', -1),'is_distribute' => 1,'pid'=>$v['id']);
			$is_distribute_category[$k]['category_2nd'] = D('shop_category')->where($map3)->order('sort')->select();
		}
		$this->assign('is_distribute_category',$is_distribute_category);
		$this->assign('shop_list',$list);
		$this->assign('page',$show);
		$this->display();
	}

    //集市商品详情
	public function shop_detail($id=''){	//r	商品详情
		$data	=	D('Distribute')->shopdetail($id);
		if(!$data['shop_distribute_item']['is_distribute']) $this->error('商品不再集市上');
		$has_item_relation	=	D('Distribute')->has_item_relation($id,$data['shop_list']['siteid'],SITEID);
		
		$website	=	D('Distribute')->get_distribute_website($data['shop_list']['siteid']);
		$company	=	D('shop_distribute_site')->where('siteid='.$data['shop_list']['siteid'])->find();
		$this->assign('has_item_relation',$has_item_relation['apply_status']);
		//查询属性结束	
		$this->assign('pictures',$data['pictures']);
		$this->assign('distribute',$data['shop_distribute_item']);	
		$this->assign('shop_list',$data['shop_list']);
		$this->assign('website',$website);
		$this->assign('company',$company);
		$this->assign('shop_detail',$data['shop_detail']);
		$this->display();

	}
	//商品加入到我的销售列表
	public function addtomine($goods_id=''){		//r
		/*******判定部分1*****************/
		if(!$goods_id){
			$this->error('参数错误');
		}
		$seller_id	=	SITEID;
		$find_site_relation	=	D('shop_distribute_site_relation')->where('seller_id='.$seller_id.' and distribute_relation_status=1 and status=1')->field('seller_id')->select();
		if($find_site_relation)	$this->error('已为全站分销商，无法进行此操作');
		$goods	=	D('shop')->where('id='.$goods_id)->find();
		if(!$goods){$this->error('无此商品');}
		if($goods['siteid']==SITEID){	//判断所选商品是否为自己的商品
			$this->error('此商品为自己的商品');
		}
		$this->authority($goods['siteid']);
		$shop_is_distribute = D('shop_distribute_item')->where("goods_id=".$goods_id." and is_distribute=1")->find();
		if($shop_is_distribute['distribute_type_b']!=1){	//判断所选商品是否为单品分销商品
			$this->error('此商品状态错误');
		}
		$has_item_relation	=	D('Distribute')->has_item_relation($goods_id,$goods['siteid'],$seller_id);
		if($has_item_relation['apply_status']	==	1){	//判断所选商品是否已加入自己分销列表
			$this->error('此商品已添加');
		}
		/*******判定部分1结束*****************/
		/*******判定部分2*****************/
		$site_relation	=	D('shop_distribute_site_relation')->where('supplier_id='.$goods['siteid'].' and seller_id='.SITEID)->find();	//判断是否与该商家建立过合作
		if(!$site_relation){		//如未与该商家建立过合作，新建合作关系
			$site_relation_arr	=	array(
				'supplier_id'	=>	$goods['siteid'],
				'seller_id'	=>	SITEID,	
				'distribute_relation_status'	=> 0,	//为单品分销
				'apply_time'	=>	time(),
				'examine_time'	=>	time(),
				'status'		=>1,	//状态正在合作
			);
			$create_site_relation	=	D('shop_distribute_site_relation')->add($site_relation_arr);
		}else{	//之前与该商家建立过合作，修改合作状态
			$site_relation_arr	=	array(
				'distribute_relation_status'	=> 0,	//为单品分销
				'status'		=>1,					//状态正在合作
			);
			$create_site_relation	=	D('shop_distribute_site_relation')->where(array('supplier_id'	=>$goods['siteid'],'seller_id'=>SITEID))->save($site_relation_arr);
		}
		/*******判定部分2结束*****************/
		$supplier_id	=	$goods['siteid'];
		$seller_id		=	SITEID;
		$apply_time		=	time();
		$distribute_item	=	array(
				'goods_id'		=>	$goods_id,
				'supplier_id'	=>	$supplier_id,
				'seller_id'	=>	$seller_id,
				'apply_status'	=>	1,
				'distribute_relation_status'	=> 0,
				'apply_time'	=>	$apply_time,
			);
			//判断之前是否加入过此商品
		$has_item_relation	=	D('Distribute')->has_item_relation($goods_id,$supplier_id,$seller_id);
		//查询商品关系池是否有记录
		$has_item_relation_pool	=	D('shop_distribute_item_relation_pool')->where('goods_id='.$goods_id.' and supplier_id='.$supplier_id.' and seller_id='.$seller_id)->find();	
		if(!$has_item_relation_pool){
			$distribute_item_relation_pool	=	D('shop_distribute_item_relation_pool')->add($distribute_item);
		}
		
		
		if($has_item_relation){
			$distribute_item_relation	=	D('shop_distribute_item_relation')->where('goods_id='.$goods_id.' and supplier_id='.$supplier_id.' and seller_id='.$seller_id)->save(array('apply_status'	=>	1));
		}else{
			$distribute_item_relation	=	D('shop_distribute_item_relation')->add($distribute_item);
		}
		if($distribute_item_relation){
			D('Common/shop')->cc_goodsmap_s($seller_id);
			D('Common/shop')->cc_goodsmap_s($seller_id,$goods_id);
			D('Common/shop')->cc_goodsmap_s($seller_id,'','other');
			D('Common/shop')->clear_index_module_s($seller_id);
			$this->success('分销成功');
		}else{
			$this->error('操作失败');
		}
	}
	
	
	//distribute_relation_status开启全站分销
	public function relation_a_apply($supplier_id=''){
		/********判定部分开始***********/
		if($supplier_id=='') $this->error('未选择任何商家');		//传递商家ID
		if($supplier_id==SITEID) $this->error('选中的商家为自己');		//排除自己的商家
		
		//判断是否为供货商，如果是，不能进行全站分销
		$has_distribution_authority	=	D('distribution_authority')->where('shop_authority=2 and siteid='.SITEID)->find();	
		if($has_distribution_authority) $this->error('为供货商，不能成为全站分销商');
		
		//判断是否已经为全站合作状态
		$has_a_relation	=	$this->has_a_relation();
		if($has_a_relation['error1']){$this->error('已为全站分销商');}
		if($has_a_relation['error2']){$this->error('已提交过全站分销申请');}
		/********判定部分结束***********/
		
		$relation_a_apply_arr	=	array(
			'seller_id'	=>	SITEID,
			'supplier_id'	=>	$supplier_id,
			'apply_time'	=>time(),
			'examine_status'=>1,
			'relation_a_apply'	=> create_sn()
		);
		$relation_a_apply	=	D('shop_distribute_relation_a_apply')->add($relation_a_apply_arr);
		if($relation_a_apply){
			$this->success('提交申请成功');
		}
	
	}
	
	
	public function relation_a_apply_list(){
		$relation_a_apply_list	=	D('shop_distribute_relation_a_apply')->where('supplier_id='.SITEID)->order('apply_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		$count=D('shop_distribute_relation_a_apply')->where($map)->count();//总数
		$Page  = new \Think\Page($count,10);// 
		$show  = $Page->show();// 
		foreach($relation_a_apply_list as $key=>$value){
			$website	=	D('Distribute')->get_distribute_website($value['seller_id']);
			$relation_a_apply_list[$key]['domain']	=	$website['domain'];
			$relation_a_apply_list[$key]['webname']	=	$website['webname'];
			$relation_a_apply_list[$key] = array_merge_recursive($relation_a_apply_list[$key], $website['shop_address']);
		}
		
		
		
		$this->assign('datainfo',$relation_a_apply_list);
		$this->assign('page',$show);
		$this->display();
		
	}
	
	
	public function relation_a_apply_detail($id){
		$relation_a_apply_detail	=	D('shop_distribute_relation_a_apply')->where('id='.$id)->find();
		
		$webinfo	=	D('Distribute')->get_distribute_website($relation_a_apply_detail['seller_id']);

		$this->assign('shop_address',$webinfo['shop_address']);
		$this->assign('webinfo',$webinfo);
		$this->assign('apply_detail',$relation_a_apply_detail);
		$this->display();
	}
	
	public function relation_a_apply_update($id="",$action=''){	//r
		/********判定部分开始***********/
		if(!id) $this->error('非法操作1');
		$relation_a_apply_detail	=	D('shop_distribute_relation_a_apply')->where('id='.$id)->find();
		if($relation_a_apply_detail['supplier_id']	!= SITEID)	$this->error('非法操作2');
		
		/********判定部分结束***********/
		switch ($action){	
			case 'throgh':			//通过
			/********判定部分1开始***********/
			if($relation_a_apply_detail['examine_status']==0 || $relation_a_apply_detail['examine_status']==2|| $relation_a_apply_detail['examine_status']==-1){$this->error('已审批过');}
			
			//判断是否为供货商，如果是，不能进行全站分销
			$has_distribution_authority	=	D('distribution_authority')->where('shop_authority=2 and siteid='.$relation_a_apply_detail['seller_id'])->find();	
			if($has_distribution_authority) $this->error('该商家为供货商，不能成为全站分销商');
			
			//判断是否已经为全站合作状态
			$has_a_relation	=	D('shop_distribute_site_relation')->where('seller_id='.$relation_a_apply_detail['seller_id'].' and distribute_relation_status=1 and status=1')->find();	
			if($has_a_relation) $this->error('该商家已为全站分销商');
			
			/********判定部分1结束***********/
			/********操作1 审批，更改状态***********/
			$relation_a_apply_arr	=	array(
				'examine_status'	=> 2,
				'examine_time'	=>	time(),
			);
			
			$relation_a_apply_update	=	D('shop_distribute_relation_a_apply')->where('id='.$id)->save($relation_a_apply_arr);
			if(!$relation_a_apply_update) $this->error('审核失败');
			$site_relation	=	D('shop_distribute_site_relation')->where('supplier_id='.$relation_a_apply_detail['supplier_id'].' and seller_id='.$relation_a_apply_detail['seller_id'])->find();	//判断是否与该商家建立过合作
			if(!$site_relation){		//如未与该商家建立过合作，新建合作关系
				$site_relation_arr	=	array(
					'supplier_id'	=>	$relation_a_apply_detail['supplier_id'],
					'seller_id'	=>	$relation_a_apply_detail['seller_id'],	
					'distribute_relation_status'	=> 1,	//为全站分销
					'apply_time'	=>	time(),
					'examine_time'	=>	time(),
					'status'		=>1,	//状态正在合作
				);
				$create_site_relation	=	D('shop_distribute_site_relation')->add($site_relation_arr);
			}else{	//之前与该商家建立过合作，修改合作状态
				$site_relation_arr	=	array(
					'distribute_relation_status'	=> 1,	//为全站分销
					'status'		=>1,					//状态正在合作
				);
				$create_site_relation	=	D('shop_distribute_site_relation')->where(array('supplier_id'	=>$relation_a_apply_detail['supplier_id'],'seller_id'=>$relation_a_apply_detail['seller_id']))->save($site_relation_arr);
			}
			/********操作1结束***********/
			/********操作2：下架商品，取消申请方其他的单品分销关系及商品***********/
				//取消申请方其他的单品分销关系
				$site_relation_status	=	array(
					'status'		=>0,					
				);
				$create_site_relation	=	D('shop_distribute_site_relation')->where('seller_id='.$relation_a_apply_detail['seller_id'].' and supplier_id!='.$relation_a_apply_detail['supplier_id'])->save($site_relation_status);
				//删除分销商品
				$remove_distribute_items	=	D('shop_distribute_item_relation')->where('seller_id='.$relation_a_apply_detail['seller_id'])->delete();
				//下架申请方商品
				$site_relation_status	=	array(
					'status'		=>0,					
				);
				$update_shop_status	=	D('shop')->where('siteid='.$relation_a_apply_detail['seller_id'])->save(array('status'=>0));
			
			/********操作2结束***********/
			/********操作3：上架分销商品***********/
				$supplier_goods	=	D('shop_distribute_item')->where('siteid='.$relation_a_apply_detail['supplier_id'].' and distribute_type_a=1 and is_distribute=1')->select();
				foreach($supplier_goods as $key=>$val){
					$item_relation_arr	= array(
						'goods_id'	=>	$val['goods_id'],
						'supplier_id'	=>	$relation_a_apply_detail['supplier_id'],
						'seller_id'		=>	$relation_a_apply_detail['seller_id'],
						'apply_time'	=>	time(),
						'distribute_relation_status'	=> 1,
						'apply_status'	=> 1,
					);
					D('Common/shop')->cc_goodsmap_s($relation_a_apply_detail['seller_id'],$val['goods_id'],'');
							
					$add_supplier_goods	=	D('shop_distribute_item_relation')->add($item_relation_arr);
					$has_item_relation_pool	=	D('shop_distribute_item_relation_pool')->where('goods_id='.$val['goods_id'].' and supplier_id='.$relation_a_apply_detail['supplier_id'].' and seller_id='.$relation_a_apply_detail['seller_id'])->find();	
					if(!$has_item_relation_pool){
						$distribute_item_relation_pool	=	D('shop_distribute_item_relation_pool')->add($item_relation_arr);
					}
				}
				D('Common/shop')->cc_goodsmap_s($relation_a_apply_detail['seller_id'],'','other');
				D('Common/shop')->cc_goodsmap_s($relation_a_apply_detail['seller_id']);
				D('Common/shop')->clear_index_module_s($relation_a_apply_detail['seller_id']);
			
			/*****************操作3结束************************/
			if($relation_a_apply_update) $this->success('审批成功');
			break;
		case 'reject':		//驳回
			if($relation_a_apply_detail['examine_status']==0 || $relation_a_apply_detail['examine_status']==2|| $relation_a_apply_detail['examine_status']==-1){$this->error('已审批过');}
			/********操作1 审批，更改状态***********/
			$relation_a_apply_arr	=	array(	//直接否定
				'examine_status'	=> 0,
				'examine_time'	=>	time(),
			);
			$relation_a_apply_update	=	D('shop_distribute_relation_a_apply')->where('id='.$id)->save($relation_a_apply_arr);
			if(!$relation_a_apply_update) $this->error('驳回申请失败');
			/*****************操作3结束************************/
				if($relation_a_apply_update) $this->success('驳回申请成功');
			
			break;
		case 'cancel':			//取消合作
			/********判定部分1开始***********/
			if($relation_a_apply_detail['examine_status']==0){$this->error('该审批未通过，不需要取消');}
			if($relation_a_apply_detail['examine_status']==-1){$this->error('该审批已取消');}
			//判断是否已经为全站合作状态
			/********判定部分1结束***********/
			/************清除缓存****************/
			$supplier_goods	=	D('shop_distribute_item')->where('siteid='.$relation_a_apply_detail['supplier_id'].' and distribute_type_a=1 and is_distribute=1')->select();
			foreach($supplier_goods as $key=>$val){
				D('Common/shop')->cc_goodsmap_s($relation_a_apply_detail['seller_id'],$val['goods_id'],'');
			}
			D('Common/shop')->cc_goodsmap_s($relation_a_apply_detail['seller_id'],'','other');
			D('Common/shop')->cc_goodsmap_s($relation_a_apply_detail['seller_id']);
			D('Common/shop')->clear_index_module_s($relation_a_apply_detail['seller_id']);
			/********操作1 审批，更改状态***********/
			$relation_a_apply_arr	=	array(
				'examine_status'	=> -1,
				'examine_time'	=>	time(),
			);
			
			$relation_a_apply_update	=	D('shop_distribute_relation_a_apply')->where('id='.$id)->save($relation_a_apply_arr);
			if(!$relation_a_apply_update) $this->error('审核失败');
			$site_relation	=	D('shop_distribute_site_relation')->where('supplier_id='.$relation_a_apply_detail['supplier_id'].' and seller_id='.$relation_a_apply_detail['seller_id'])->find();	//判断是否与该商家建立过合作
			if($site_relation){		
					//之前与该商家建立过合作，修改合作状态
				$site_relation_arr	=	array(
					'distribute_relation_status'	=> 1,	//为全站分销
					'status'		=>0,					//状态正在合作
				);
				$create_site_relation	=	D('shop_distribute_site_relation')->where(array('supplier_id'	=>$relation_a_apply_detail['supplier_id'],'seller_id'=>$relation_a_apply_detail['seller_id']))->save($site_relation_arr);
			}
			/********操作1结束***********/
			/********操作2：下架商品，取消申请方其他的单品分销关系及商品***********/
				//取消申请方其他的单品分销关系
				$site_relation_status	=	array(
					'status'		=>0,					
				);
				$create_site_relation	=	D('shop_distribute_site_relation')->where('seller_id='.$relation_a_apply_detail['seller_id'].' and supplier_id='.$relation_a_apply_detail['supplier_id'])->save($site_relation_status);
				//删除分销商品
				$remove_distribute_items	=	D('shop_distribute_item_relation')->where('seller_id='.$relation_a_apply_detail['seller_id'])->delete();
			
			/********操作2结束***********/
			if($relation_a_apply_update) $this->success('取消成功');
			break;
		}
		
	}
	
	public function relation_a_list(){
		$count=D('shop_distribute_relation_a_apply')->where('seller_id='.SITEID)->count();//总数
		$Page  = new \Think\Page($count,10);// 
		$show  = $Page->show();// 
		$relation_a_apply_list	=	D('shop_distribute_relation_a_apply')->where('seller_id='.SITEID)->order('apply_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		
		foreach($relation_a_apply_list as $key=>$value){
			$website	=	D('Distribute')->get_distribute_website($value['supplier_id']);
			$relation_a_apply_list[$key]['webname']	=	$website['webname'];
			$relation_a_apply_list[$key]['domain']	=	$website['domain'];
			$relation_a_apply_list[$key] = array_merge_recursive($relation_a_apply_list[$key], $website['shop_address']);
		}
		
		
		
		$this->assign('datainfo',$relation_a_apply_list);
		$this->assign('page',$show);
		$this->display();
		
	}
	
	
	
	public function relation_a_detail($id){
		$relation_a_apply_detail	=	D('shop_distribute_relation_a_apply')->where('id='.$id)->find();
		$webinfo	=	D('Distribute')->get_distribute_website($relation_a_apply_detail['supplier_id']);
		
		$this->assign('shop_address',$webinfo['shop_address']);
		$this->assign('webinfo',$webinfo);
		$this->assign('apply_detail',$relation_a_apply_detail);
		$this->display();
	}
	
	public function relation_a_update($id="",$action=''){	//r
		/********判定部分开始***********/
		if(!id) $this->error('非法操作1');
		$relation_a_apply_detail	=	D('shop_distribute_relation_a_apply')->where('id='.$id)->find();
		if($relation_a_apply_detail['seller_id']	!= SITEID)	$this->error('非法操作2');
		
		/********判定部分结束***********/
		switch ($action){	
			case 'cancel':			//取消合作
				/********判定部分1开始***********/
				if($relation_a_apply_detail['examine_status']==0){$this->error('该审批未通过，不需要取消');}
				if($relation_a_apply_detail['examine_status']==-1){$this->error('该审批已取消');}
				//判断是否已经为全站合作状态
				/********判定部分1结束***********/
				/********操作1 审批，更改状态***********/
				$relation_a_apply_arr	=	array(
					'examine_status'	=> -1,
				);
				
				$relation_a_apply_update	=	D('shop_distribute_relation_a_apply')->where('id='.$id)->save($relation_a_apply_arr);
				if(!$relation_a_apply_update) $this->error('审核失败');
				$site_relation	=	D('shop_distribute_site_relation')->where('supplier_id='.$relation_a_apply_detail['supplier_id'].' and seller_id='.$relation_a_apply_detail['seller_id'])->find();	//判断是否与该商家建立过合作
				if($site_relation){		
						//之前与该商家建立过合作，修改合作状态
					if($site_relation['distribute_relation_status']	==	0){
						$site_relation_arr	=	array(
							'distribute_relation_status'	=> $site_relation['distribute_relation_status'],
							'status'		=>$site_relation['status'],					//状态正在合作
						);
					}else{
						$site_relation_arr	=	array(
							'distribute_relation_status'	=> 1,	//为全站分销
							'status'		=>0,					//状态正在合作
						);
					}
					$create_site_relation	=	D('shop_distribute_site_relation')->where(array('supplier_id'	=>$relation_a_apply_detail['supplier_id'],'seller_id'=>$relation_a_apply_detail['seller_id']))->save($site_relation_arr);
				}
				/********操作1结束***********/
				/********操作2：下架商品，取消申请方其他的单品分销关系及商品***********/
					//取消申请方其他的单品分销关系
					$remove_distribute_items	=	D('shop_distribute_item_relation')->where('seller_id='.SITEID)->field('goods_id')->select();
					foreach($remove_distribute_items as $key=>$val){
						D('Common/shop')->cc_goodsmap_s(SITEID,$val['goods_id'],'');
					}
					D('Common/shop')->cc_goodsmap_s(SITEID);
					D('Common/shop')->cc_goodsmap_s(SITEID,'','other');
					D('Common/shop')->clear_index_module_s();

					//删除分销商品
					$remove_distribute_items	=	D('shop_distribute_item_relation')->where('seller_id='.$relation_a_apply_detail['seller_id'].' and distribute_relation_status=1')->delete();
				
				/********操作2结束***********/
				if($relation_a_apply_update) $this->success('取消成功');
			break;
		}
		
	}
	
	
	//取消分销某商品
	public function removefrommine($goods_id=''){	//r
		/*******判定部分1*****************/
		if(!$goods_id){
			$this->error('参数错误');
		}
		$find_site_relation	=	D('shop_distribute_site_relation')->where('seller_id='.SITEID.' and distribute_relation_status=1 and status=1')->field('seller_id')->select();
		if($find_site_relation)	$this->error('已为全站分销商，无法进行此操作');
		$seller_id	=	SITEID;
		$goods	=	D('shop')->where('id='.$goods_id)->find();
		if(!$goods){$this->error('无此商品');}
		if($goods['siteid']==SITEID){	//判断所选商品是否为自己的商品
			$this->error('此商品为自己的商品');
		}
		$shop_is_distribute = D('shop_distribute_item')->where("goods_id=".$goods_id." and is_distribute=1")->find();
		if($shop_is_distribute['distribute_type_b']!=1){	//判断所选商品是否为单品分销商品
			$this->error('此商品未分销，不需再次取消');
		}
		$has_item_relation	=	D('Distribute')->has_item_relation($goods_id,$goods['siteid'],$seller_id);
		if(!$has_item_relation['apply_status']){	//判断所选商品是否已加入自己分销列表
			$this->error('未分销此商品，不需再次取消');
		}
		/*******判定部分结束*****************/
		$distribute_item_relation	=	D('shop_distribute_item_relation')->where('goods_id='.$goods_id.' and supplier_id='.$goods['siteid'].' and seller_id='.$seller_id)->save(array('apply_status'	=>	0));
		if($distribute_item_relation){
			D('Common/shop')->cc_goodsmap_s($seller_id,$goods_id,'');
			D('Common/shop')->cc_goodsmap_s($seller_id);
			D('Common/shop')->cc_goodsmap_s($seller_id,'','other');
			D('Common/shop')->clear_index_module_s($seller_id);
			$this->success('取消分销成功');
		}else{
			$this->error('取消分销失败');
		}
	}
	
	//我的销售员列表
	public function myseller(){
		$this->authority();
		$count=D('shop_distribute_site_relation')->where($map)->count();//总数
		$Page  = new \Think\Page($count,10);// 
		
		$show  = $Page->show();// 
		$myseller	=	D('shop_distribute_site_relation')->where('supplier_id='.SITEID)->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($myseller as $key=>$value){
			$webname	=	D('websit')->where('siteid='.$value['seller_id'])->getField('webname');
			$myseller[$key]['webname']	=	$webname;
		}
		$this->assign('datainfo',$myseller);
		$this->assign('page',$show);
		$this->display();
	}

	//我的销售员在销售我的商品的列表
	public function isselling($seller_id=''){
		$this->authority();
		$seller_id=$_GET['seller_id'];
		if($seller_id){
			$map['seller_id']	=	$seller_id;
		}
		$map['supplier_id']		=	SITEID;
		$map['apply_status']	=	1;
		$count=D('shop_distribute_item_relation')->where($map)->count();//总数
		$Page  = new \Think\Page($count,10);// 
		$show  = $Page->show();//
		$relation_item	=	D('shop_distribute_item_relation')->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($relation_item as $key=>$value){
			$goods	=	D('shop')->where('siteid='.$value['supplier_id'].' and id='.$value['goods_id'])->find();
			$relation_item[$key]['goods_name']	=	$goods['goods_name'];
			$relation_item[$key]['createtime']	=	$goods['createtime'];
			$webname	=	D('websit')->where('siteid='.$value['supplier_id'])->getField('webname');
			$relation_item[$key]['webname']	=	$webname;
			if(!$goods){
				unset($relation_item[$key]);
			}
		}
		$this->assign('datainfo',$relation_item);
		$this->assign('page',$show);
		$this->display();
	}
	
	//我的供货商列表
	public function mysupplier(){
		$count=D('shop_distribute_site_relation')->where($map)->count();//总数
		$Page  = new \Think\Page($count,10);// 
		$show  = $Page->show();//
		$mysupplier	=	D('shop_distribute_site_relation')->where('seller_id='.SITEID)->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($mysupplier as $key=>$value){
			$webname	=	D('websit')->where('siteid='.$value['supplier_id'])->getField('webname');
			$mysupplier[$key]['webname']	=	$webname;
		}
		$this->assign('datainfo',$mysupplier);
		$this->assign('page',$show);
		$this->display();
	}
	//我选择了某个供应商的哪些分销商品
	public function issupplying($supplier_id=''){
		if($supplier_id){
			$map['supplier_id']	=	$supplier_id;
		}
		$map['seller_id']	=	SITEID;
		$map['apply_status']=	1;
		
		$count=D('shop_distribute_item_relation')->where($map)->count();//总数
		$Page  = new \Think\Page($count,10);// 
		$show  = $Page->show();//

		$relation_item	=	D('shop_distribute_item_relation')->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
		
		//dump($relation_item);
		foreach($relation_item as $key=>$value){
			$goods	=	D('shop')->where('siteid='.$value['supplier_id'].' and id='.$value['goods_id'])->find();
			$relation_item[$key]['goods_name']	=	$goods['goods_name'];
			$relation_item[$key]['createtime']	=	$goods['createtime'];
			$webname	=	D('websit')->where('siteid='.$value['supplier_id'])->getField('webname');
			$relation_item[$key]['webname']	=	$webname;
			if(!$goods){
				unset($relation_item[$key]);
			}
		}
		$this->assign('datainfo',$relation_item);
		$this->assign('page',$show);
		$this->display();
	}
	
	
	 public function supplier_order(){ 
    	if(I('order_status')!=''){
			if(I('order_status')==2){
				$map['status'] = array('elt',2);
				}else{
			   $map['status']=I('order_status');
			   }
		}
		if(I('pay_status')!=''){
		   $map['pay_status']=I('pay_status');
		}
		$seek = op_t(I('seek'));
		
		//---查询---
		if($seek!=''){
			$where['order_sn']  = array('like','%'.$seek.'%');
			$where['consignee_name']  = array('like','%'.$seek.'%');
			$where['phone']  = array('like','%'.$seek.'%');
			$where['_logic'] = 'or';
			$map['_complex'] = $where;
		}

		$map['_string'] = 'siteid!='.SITEID.' and supplier_id='.SITEID;
		
		$count=D('shop_ordersn')->where($map)->count();//总数
		$Page  = new \Think\Page($count,10);// 
		$show  = $Page->show();// 
		$shop_order_arr = D('shop_ordersn')->where($map)->order("create_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($shop_order_arr as $key => $value){
			$shop_order_arr[$key]['nickname'] = query_user('nickname',$shop_order_arr[$key]['uid']);
		}
		$this->assign('siteid',SITEID);
		$this->assign('title','我的分销订单');
		$this->assign('datainfo',$shop_order_arr);
		$this->assign('page',$show);
		$this->display();
    }
	public function supplier_order_seek($seek){
		if($seek!='' && $status='3'){
		$url=U('Manage/Distribute/supplier_order',array('seek'=>$seek));
			header("Location:$url");
		}else{
			$this->error('请填写查询内容');
		}
	}
	
	public function seller_order(){ 
    	if(I('order_status')!=''){
			if(I('order_status')==2){
				$map['status'] = array('elt',2);
				}else{
			   $map['status']=I('order_status');
			   }
		}
		if(I('pay_status')!=''){
		   $map['pay_status']=I('pay_status');
		}
		$seek = op_t(I('seek'));
		
		//---查询---
		if($seek!=''){
			$where['order_sn']  = array('like','%'.$seek.'%');
			$where['consignee_name']  = array('like','%'.$seek.'%');
			$where['phone']  = array('like','%'.$seek.'%');
			$where['_logic'] = 'or';
			$map['_complex'] = $where;
		}

		$map['_string'] = 'siteid='.SITEID.' and supplier_id!='.SITEID.' and supplier_id is not null';
		
		$count=D('shop_ordersn')->where($map)->count();//总数
		$Page  = new \Think\Page($count,10);// 
		$show  = $Page->show();// 
		$shop_order_arr = D('shop_ordersn')->where($map)->order("create_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($shop_order_arr as $key => $value){
			$shop_order_arr[$key]['nickname'] = query_user('nickname',$shop_order_arr[$key]['uid']);
		}
		$this->assign('siteid',SITEID);
		$this->assign('title','我的分销订单');
		$this->assign('datainfo',$shop_order_arr);
		$this->assign('page',$show);
		$this->display();
    }
	
	public function seller_order_seek($seek){
		if($seek!='' && $status='3'){
		$url=U('Manage/Distribute/seller_order',array('seek'=>$seek));
			header("Location:$url");
		}else{
			$this->error('请填写查询内容');
		}
	}
	
	
	  /*验证qq*/
    public function check_qq($qq)
     {
        if(!preg_match("/^[1-9]*[1-9][0-9]*$/",$qq)){
            $this->error('请输入正确的QQ号码');
        }
        if($qq==''){
            $this->error('不能为空');
        }
     }
     /*验证电话号码*/
     public function checkTelphone($telphone){
         if($telphone==''){
            $this->error('手机号码不能为空');
         }
		if(!preg_match("/^1[0-9]{10}$/",$telphone)){

            $this->error('请输入正确的手机号码');
        }
       
     }
     
     /*验证邮箱*/

	  private function checkEmail($email)
    {
        $pattern = "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
        if (!preg_match($pattern, $email)) {
            $this->error('邮箱格式错误。');
        }

        $map['email'] = $email;
        $map['id'] = array('neq', get_uid());

    }

     
     /*验证身份证号码*/
     public function check_card($card){
        if($card ==''){
            $this->error('请输入身份证号');
        }
        if(!preg_match("/(^\d{15}$)|(^\d{17}([0-9]|X|x)$)/",$card)){
            $this->error('请输入正确15-18位身份证号码');
        }
     }
     /*验证添加联系人中的电话号码*/
     public function check_telephone($telephone){
        if(!preg_match("/^1[0-9]{10}$/",$telephone)){
            $this->error('请输入正确的联系电话。');
          }
        if($telephone==''){
            $this->error('请填写手机号码');
        }
     }
	 /*验证紧急联系人号码*/
	 public function check_emergencyphone($emergencyphone){
	      if($emergencyphone==''){
            $this->error('请填写紧急联系人手机号码');
           }
		  if(!preg_match("/^1[0-9]{10}$/",$emergencyphone)){
            $this->error('请输入正确的紧急联系人手机号码。');
          }
    }
        
	public function check_realname($realname){
        if (trim(op_t($realname)) == '') {
            $this->error('请输入真实姓名。');
        }
     }

}  

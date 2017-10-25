<?php

namespace Official\Controller;

use Think\Controller;

class ShopController extends BaseController
{
	protected $shop_categoryModel;
    function _initialize()
    {
		$this->userdata = query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $uid);
      $this->shop_categoryModel = D('Shop/ShopCategory');  
    }
	/*
	**商品列表* 2014-12-3 dlx pm
	*/
    public function index(){
		$status	= $_GET['status'];
		$status = isset($status)? $status:1;
		 switch($status){
		    case 1:
				$count = D('shop_brand_manage')->count();
			    $Page       = new \Think\Page($count,10);
				
				$show       = $Page->show();// 分页显示输出  
			    $list = D('shop_brand_manage')->limit($Page->firstRow.','.$Page->listRows)->select();
				$this->assign('shop_brand_list',$list);
				$this->assign('page',$show);
			break;
			case 3:  //属性名
				$count = D('shop_sku_types_system')->count();
			    $Page       = new \Think\Page($count,10);
				
				$show       = $Page->show();// 分页显示输出  
				$list = D('shop_sku_types_system')->limit($Page->firstRow.','.$Page->listRows)->select();
				$this->assign('page',$show);
				$this->assign('system',$list);
			break;
			case 4:  //属性值
				$id	= $_GET['id'];
				$is_color = $_GET['is_color'];
				$count = D('shop_sku_types_attribute_stystem')->where("sku_types_id=".$id)->count();
			    $Page       = new \Think\Page($count,10);
				
				$show       = $Page->show();// 分页显示输出  
				if($id=='') $this->redirect('Websit/Shop/index?status=3');
				$list	=	D('shop_sku_types_attribute_stystem')->limit($Page->firstRow.','.$Page->listRows)->where("sku_types_id=".$id)->select();
				$this->assign('attr_stystem',$list);
				$this->assign('typesid',$id);
				$this->assign('page',$show);
				$this->assign('is_color',$is_color);
			break;
			case 5: //类目
				$count = D('shop_sku_category')->where('status>=0')->count();
			    $Page       = new \Think\Page($count,10);
				
				$show       = $Page->show();// 分页显示输出
				$list = D('shop_sku_category')->limit($Page->firstRow.','.$Page->listRows)->where('status>=0')->select();
				$this->assign('page',$show);
				$this->assign('sku_cates',$list);
			break;
			case 6: //类目
			
				$map['status'] = array('egt',0);
				
				if(I('pid')){
					$map['pid'] = I('pid');
				}else{
					$map['pid'] = 0;
				}
				$count = D('shop_category')->where($map)->count();
			    $Page       = new \Think\Page($count,10);
				
				$show       = $Page->show();// 分页显示输出
				$list =	D('shop_category')->limit($Page->firstRow.','.$Page->listRows)->where($map)->select();
				foreach($list as $k=>$v){
				     $list[$k]['childnum']=D('shop_category')->where("pid=".$v['id']." and siteid=".SITEID ." and status>=0")->count();
				}
				$this->assign('shop_cates',$list);
			break;
		 }
		
		$this->assign('status',$status);
		$this->assign('user',$this->userdata);
		$this->assign('page',$show);
		$this->display();
		
	}
	
	
	public function shop_brand_manage_add(){
		$this->assign('user',$this->userdata);
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
					$this->success("更改成功",U('Official/Shop/index',array('status'=>6)));
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
					$this->success("添加成功",U('Official/Shop/index',array('status'=>6)));
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
		$this->assign('user',$this->userdata);
		$this->display();
	}
	
	
	public function shop_category_add()
	{
		$this->assign('user',$this->userdata);
		$this->display();
	}
	
	
	
    /*
	*添加类目与修改类目* 2014-12-8 pm dlx
	*/
	public function shop_cates_add($sku_category_id=0,$sort=0,$title=''){
			if(IS_POST){
			    
				if($sku_category_id	==''){ //--添加--
					
					$pid	=	op_t(trim($pid));
					$sort	=   op_t(trim($sort));
					$title	=	op_t(trim($title));
					$sku_types_id = $_POST['sku_types_id'];
					
					if(get_shop_sku_types()==''){
						$this->error('请先添加类目属性',U('Websit/Shop/index',array('status'=>3)));
					}
					if($title =='') $this->error('请填写类目名称');
					if($sort!=''){
						if(!is_numeric($sort)) $this->error('排序必须为数字');
					}
					if($sku_types_id == null) $this->error('请先选择系统属性');
					$sku_types_id	=	implode(',',$sku_types_id);
					
					$data = array(
						
						'status'	=>	1,
						'create_time'=> time(),
						'pid'		=>	$pid,
						'sort'		=>	$sort,
						'title'		=>	$title,
						'sku_types_id'=> $sku_types_id
						);
					
					$list	=	D('shop_sku_category')->data($data)->add();
					if($list){
						$this->success('添加成功',U('Official/Shop/index',array('status'=>5)));
					
					}else{
						$this->error('添加失败!');
					}
					
				}else{	//--修改--
					
					$sort	=   op_t(trim($sort));
					$title	=	op_t(trim($title));
					$sku_types_id =	$_POST['sku_types_id'];
					if($sku_category_id=='') $this->error('参数错误!');
					if($title =='') $this->error('请填写类目名称');
					if($sort!=''){
						if(!is_numeric($sort)) $this->error('排序必须为数字');
					}
					if($sku_types_id == null) $this->error('请选择类目属性');
					$sku_types_id	=	implode(',',$sku_types_id);
					$data = array(
						'update_time'=>time(),
						'sort'		=>	$sort,
						'title'		=>	$title,
						'sku_types_id' => $sku_types_id
						);
					
					$list	=	D('shop_sku_category')->where("sku_category_id=".$sku_category_id)->save($data);
					if($list){
						$this->success('修改成功',U('Official/Shop/index',array('status'=>5)));
					
					}else{
						$this->error('修改失败!');
					}
				
				}
			
			}else{
			
				$list = D('shop_sku_types_system')->select();
				$this->assign('user',$this->userdata);
				$this->assign('sku_types',$list);
				$this->display();
			}
			
	}
	
	/*
	**修改类目信息**dlx -2014-12-8 pm 
	*/
	public function shop_cates_edit(){
	    $list = D('shop_sku_category')->where('sku_category_id='.$_GET['id'])->find();
		$this->assign('sku_cates_find',$list);
		$this->assign('user',$this->userdata);
		$this->display();
	}
	
	public function shop_attribute_add(){
		$this->assign('user',$this->userdata);
		$this->display();
	}
	
	/*
	*添加和修改商品属性名称 dlx -2014-12-6 pm
	*/
	public function shop_attribute_doAdd($id=0,$sort=0,$types_name='')
	{
		if(IS_POST){
			if($id!=0){ //-修改-
				$types_name	= op_t(trim($types_name));
				$sort		= op_t(trim($sort));
				$is_color	= $_POST['is_color'];
				if($id =='') $this->error('参数错误!');
				if($types_name == '') $this->error('请填写属性名称');
				if($sort !=0){
					if(!is_numeric($sort)) $this->error('排序必须为数字!');
				}
				$data = array(
					'types_name'	=>$types_name,
					'sort'			=>$sort,
					'is_color'		=>$is_color,
					'is_system'		=>1
					
				);
				$list = D('shop_sku_types_system')->where("sku_types_id=".$id)->save($data);
				if($list){
					$this->success('修改成功',U('Official/Shop/index',array('status'=>3)));
				}else{
					$this->error('修改失败');
				}
			
			}else{  //-添加-
			   
			   $types_name	= op_t(trim($types_name));
			   $sort		= op_t(trim($sort));
			   $is_color	= $_POST['is_color'];
			  
			   if($types_name == '') $this->error('请填写属性名称');
			   if($sort !=0){
					if(!is_numeric($sort)) $this->error('排序必须为数字!');
			   }
			   $data = array(
				
			        'types_name'	=>$types_name,
					'sort'			=>$sort,
					'is_color'		=>$is_color,
					'is_system'		=>1
				);
				
				$list = D('shop_sku_types_system')->data($data)->add();
				if($list){
					$this->success('添加成功',U('Official/Shop/index',array('status'=>3)));
				}else{
					$this->error('添加失败');
				}
			}
		
		}
	
	}
	/*
	*修改属性名称*
	*/
	public function shop_attribute_edit()
	{
		$id	= $_GET['id'];
		$list = D('shop_sku_types_system')->where("sku_types_id=".$id)->find();
		$this->assign('system_val',$list);
		$this->assign('user',$this->userdata);
		$this->display();
	
	}
	

	/*
	*添加和修改-属性值*
	*/
	public function shop_attribute_val_add($id=0,$typesid=0,$attribute_name='',$sort=0)
	{
		if(IS_POST){
			if($id==''){  //-添加-
				
				$attribute_name	= op_t(trim($attribute_name));
				$sort	=	op_t(trim($sort));
				
				if($typesid=='') $this->error('参数错误!');
				if($attribute_name =='') $this->error('请填写属性值');
				if($sort != ''){
				   if(!is_numeric($sort)) $this->error('排序必须为数字');
				}
				$attribute_value = $_POST['attribute_value'];
				
				if($attribute_value){
				   $attribute_value = op_t(trim($attribute_value));
				}
				
				$data = array(
				    'sku_types_id'   => $typesid,
					'attribute_name' => $attribute_name,
					'sort'			 => $sort,
					'attribute_value'=> $attribute_value
				);
				
				$list = D('shop_sku_types_attribute_stystem')->data($data)->add();
				if($list){
					$this->success('添加成功!',U('Official/Shop/index',array('status'=>3)));
				}else{
					$this->error('添加失败');
				}
				
			}else{ //-修改-
				$attribute_name	= op_t(trim($attribute_name));
				$sort	=	op_t(trim($sort));
				
				if($id=='') $this->error('参数错误!');
				if($attribute_name =='') $this->error('请填写属性值');
				if($sort != ''){
				   if(!is_numeric($sort)) $this->error('排序必须为数字');
				}
				
				$attribute_value = $_POST['attribute_value']; //选择色系
				if($attribute_value ==''){
				    $attribute_value='';
				}
				
				$data = array(
				    'attribute_name' => $attribute_name,
					'sort'			 => $sort,
					'attribute_value'=> $attribute_value
				);	
				
				$list = D('shop_sku_types_attribute_stystem')->where("attribute_id=".$id)->save($data);
				if($list){
					$this->success('更改成功',U('Official/Shop/index',array('status'=>3)));
				}else{
					$this->error('更改失败!');
				}
			
			}
		
		
		}else{
		    $is_color = $_GET['is_color'];
			$id = $_GET['typesid'];
			$this->assign('typesid',$id);
			$this->assign('is_color',$is_color);
			$this->assign('user',$this->userdata);
			$this->display();
		
		}
		
	}
	/*
	*修改属性值* 2014-12-5 pm
	*/
	public function shop_attribute_val_edit()
	{
		$id	= $_GET['id'];
		if($id =='') $this->error('参数错误!');
		$list = D('shop_sku_types_attribute_stystem')->where("attribute_id=".$id)->find();
		$this->assign('attrfind',$list);
		$this->assign('user',$this->userdata);
		$this->display();
	}
	/*
	*是否禁用类目*2014-12-9
	**/
	
	
	
	
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
	**添加品牌*** 2014-12-29 dlx - pm
	**/
	public function shop_brand_manage_doAdd($id=0,$name='',$englist_name=''){
		if(IS_POST){
			//----------------------添加--------------------------------------
		    $name    = op_t(trim($name));
			$englist_name = op_t(trim($englist_name));
			if($name=='') $this->error('请填写品牌名称!');
			if($englist_name !=''){
				if(!preg_match('/^[A-Za-z]+$/',$englist_name)){
					$this->error('请填写正确的英文English');
				}
				
			}
			
			$ucfirst  = $this->getWords($name);
			$ucfirst  = strtolower($ucfirst);
			$data = array(
				'name'			=>	$name,
				'ucfirst'   	=>  $ucfirst,
				'englist_name' 	=>  $englist_name,
			
			);
			
			if($id==''){
			
			$list =  D('shop_brand_manage')->add($data);
			   if($list){
					$this->success('添加成功',U('Official/Shop/index'));
				}else{
					$this->error('添加失败!');
				}
				
			}else{ //----修改--
				$list_save = D("shop_brand_manage")->where("id=".$id)->save($data);
				if($list_save){
					$this->success('改更成功',U('Official/Shop/index'));
				}else{
					$this->error('请更新你要更新的内容!');
				}
			
			}
			
		}
		
	}
	/**
	*修改品牌
	*/
	public function shop_brand_manage_edit(){
		$id = $_GET['id'];
		$list = D('shop_brand_manage')->where(array('id'=>$id))->find();
		$this->assign('brand_info',$list);
		$this->assign('user',$this->userdata);
		$this->display();
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

<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;

/*
**模板中心****2015-2-10 dlx pm
**/
class TemplateController extends BaseController
{
	protected $local_comment;
   
    public function _initialize()
    {
        parent::_initialize();
		$this->local_comment = D('local_comment');
	}
	
    public function config()
    {
    }
	/*
	*模板中心*2015-2-10 dlx pm
	*/
    public function index(){
    }
	
    public function template_color(){
	
		if(IS_POST){
			$template_color = I('template_color');
			if(!$template_color) $this->error('请选择模板色系!');
			$reds = D('websit')->where(array('siteid'=>SITEID))->setField('template_color',$template_color);
			if($reds){
				$domain = $_SERVER['HTTP_HOST'];
            	clean_website_info_cash($domain);
				$this->success('更改成功');
			}else{
				$this->error('未更改数据!');
			}
		}else{
			$rs = D('websit')->where('siteid='.SITEID)->getField('template_color');
			$this->assign('theme',$rs);
			$this->assign('template_color',get_template_color('',2));
			$this->assign('template_color_text',get_template_color('',1));
			$this->display();
		}
	}
	
    public function template_left_navs(){
		$list	=	D('websit_product_config')->where("siteid=".SITEID." and status>=0")->order('id asc')->select();
		$this->assign('datainfo',$list);
		$this->display();
	}
	
	/*是否禁用产品设置dlx-2014-11-25*/
	public function template_left_nav_disable(){
	     $list	=	D('websit_product_config')->where("id=".$_POST['id'])->save(array('status'=>$_POST['status']));
		 if($list){
			$this->success('操作成功!');
		 }else{
			$this->error('操作失败!');
		 }
	
	}
	
	/*修改产品配置*/
	public function template_left_nav_edit($id ='',$title='',$description='',$address=''){
		
		$isEdit = $id ? 1 : 0;
		
		
		if(IS_POST){
				$title       = op_t(trim($title));
				$description = op_t(trim($description));
				$address	 = op_t(trim($address));
				if(strlen($title)>30) $this->error('标题10汉字以内!');
				if(strlen($description)>30) $this->error('标题10汉字以内!');
				if($title=='') $this->error('请填写标题');
				if($description=='') $this->error('请填写描述!');
				if(Gcheck_url($address)){
					$this->error('url格式不正确');
				}
				$data=array(
					 'title'		=>	$title,
					 'description'	=>	$description,
					 'address'		=>	$address
				);
				
				
				if($isEdit){
					$rs	=	D('websit_product_config')->where("id=".$id)->save($data);
				}else{
					$data['siteid']		=	SITEID;
					$data['status']		=	1;
					$data['pid']		=	0;	
					$rs	=	D('websit_product_config')->add($data);
				}
				if ($rs) {
					$this->success($isEdit ? '编辑成功' : '添加成功', U('Template/template_left_navs'));
				} else {
					$this->error($isEdit ? '未修改数据' : '添加失败');
				}
		}else{
			
			if ($isEdit) {
               $datainfo	=	D('websit_product_config')->where("id=".$id)->find();
            } else {
				$datainfo['status'] = 1;
				$datainfo['sort'] = 0;
            }
			
			$datainfo['pag_title'] = $isEdit ? '编辑顶部左侧导航' : '添加顶部左侧导航';	
			$this->assign('datainfo',$datainfo);
			$this->display();
		}
	}
	/*标签配置--2014-11-25--dlx-*/
	public function template_tags_config($tag=''){
		
	
	   if(IS_POST){
			if(count($tag)>5) $this->error('亲!最多选择5个标签');
			$tag = implode(',',$tag);
			$content['tag'] = $tag;
			$list	=	D('websit')->where("siteid=".SITEID)->save($content);
			if($list){
				$domain = $_SERVER['HTTP_HOST'];
            	clean_website_info_cash($domain);
				$this->success('操作成功',U('Template/template_tags_config'));
			}else{
				$this->error('操作失败');
			}
	    }else{
			
			$tags = D('websit')->where("siteid=".SITEID)->getField('tag');
			
			$system_tags = get_event_tag('','selected');
			if($tags){
				$mytags = $tags;
			}else{
				/*foreach($system_tags as $key=>&$info) {
					$new_system_tags[] = $key;
				}
				$mytags = implode(',',$new_system_tags);
				unset($new_system_tags);*/
			}
			$this->assign('mytags',$mytags); 
			$this->assign('system_tags',$system_tags); 
			$this->display();	
		}
	    
	}

	/**
	 * 分类导航管理
	 */
	 public function template_tags_classifications(){
		$list=D('shop_classification')->where("siteid=".SITEID)->select();
		foreach ($list as $key => &$value) {	
			 if($value['address']){
				 $value['url'] = $value['address'];
			 }else{
				 $value['url'] = U('Shop/Index/goods',array('tag'=>$value['tags_id']));
			  }
		}
		$this->assign('datainfo',$list);
		$this->display();
	}
	/**
	 * 添加导航
	 */
	 public function template_tags_classification_edit($id='',$title='',$description='',$address='',$goods_ico='',$tags=''){
		 $isEdit = $id ? 1 : 0;
		if(IS_POST){
				$title       = op_t(trim($title));
				$description = op_t(trim($description));
				$address	 = op_t(trim($address));
				if(strlen($title)>30) $this->error('标题10汉字以内!');
				if(strlen($description)>30) $this->error('标题10汉字以内!');
				if($title=='') $this->error('请填写标题');
				if($description=='') $this->error('请填写描述!');
				if($tags=='') $this->error('请选择导航标签!');
				if($address != ''){
					if(Gcheck_url($address)){
					$this->error('url格式不正确');
					}
				}
				if($goods_ico=='') $this->error('请上传图片!');
				$data=array(
					 'title'		=>	$title,
					 'description'	=>	$description,
					 'address'		=>	$address,
					 'images'		=>	$goods_ico,
					 'tags_id'		=>  $tags,
					 'siteid'		=>	SITEID
				);

								
				if($isEdit){
					$rs	=D('shop_classification')->where("id=".$id)->save($data);
				}else{
					$list=D('shop_classification')->where(array('status' => array('egt',0),'siteid'=>SITEID))->count();
					if($list <= 5){
						$data['status'] = 1;
						$rs	=D('shop_classification')->add($data);
					}else{
						$this->error('最多只能添加6个');
					}
				}
				if ($rs) {
					$this->success($isEdit ? '编辑成功' : '添加成功', U('Template/template_tags_classifications'));
				} else {
					$this->error($isEdit ? '未修改数据' : '添加失败');
				}
	 	}else{
			if ($isEdit) {
               $datainfo	=	D('shop_classification')->where(array('id'=>$id,'siteid'=>SITEID))->find();
            } else {
				$datainfo['status'] = 1;
				$datainfo['sort'] = 0;
            }
			$datainfo['pag_title'] = $isEdit ? '编辑分类导航' : '添加分类导航';	
			$this->assign('datainfo',$datainfo);
			$list=D("Common/Shop")->get_shop_brand();
			$this->assign('tags',$list[1]);
			$this->display();
		}
	}
	/**
	 *	品牌管理	梁朝阳
	 */
	public function template_tags_brands(){
	   $list=D('shop_brand')->where("siteid=".SITEID)->select();
	   
	   foreach ($list as $key => &$value) {	
			 if($value['address']){
				 $value['url'] = $value['address'];
			 }else{
				 $value['url'] = U('Shop/Index/goods',array('shop_brand'=>$value['brand']));
			  } 
		}
	   $this->assign('datainfo',$list);
	   $this->display();
	}
	public function template_tags_brand_edit($id='',$brand_name='',$address='',$brand_logo='',$brand=''){
		 $isEdit = $id ? 1 : 0;
		if(IS_POST){
			$brand_name  = op_t(trim($brand_name));
			$address	 = op_t(trim($address));
			if(strlen($brand_name)>30) $this->error('品牌名字10汉字以内!');
			if($brand_name=='') $this->error('请填写品牌名字');
			if($brand=='') $this->error('请选择品牌');
			if($address != ''){
				if(Gcheck_url($address)){
				$this->error('url格式不正确');
				}
			}
			if($brand_logo=='') $this->error('请上传品牌logo');
			$map=array(
				 'brand_name'	=>	$brand_name,
				 'address'		=>	$address,
				 'brand_logo'	=>	$brand_logo,
				 'brand'		=>	$brand,
				 'siteid'		=>	SITEID
			);
							
			if($isEdit){
				$rs	=D('shop_brand')->where("id=".$id)->save($map);
			}else{
				
				
				$map['status'] = 1;
				
				$rs	=D('shop_brand')->add($map);	
			}
			if ($rs) {
				$this->success($isEdit ? '编辑成功' : '添加成功', U('Template/template_tags_brands'));
			} else {
				$this->error($isEdit ? '未修改数据' : '添加失败');
			}
		}else{
			if ($isEdit) {
               $datainfo	=	D('shop_brand')->where(array('id'=>$id,'siteid'=>SITEID))->find();
            } else {
				$datainfo['status'] = 1;
				$datainfo['sort'] = 0;
            }
			$datainfo['pag_title'] = $isEdit ? '编辑品牌' : '添加品牌';	
			// 查询品牌
			$this->assign('datainfo',$datainfo);
			$list=D("Common/Shop")->get_shop_brand();
			$this->assign('shop_brand_manage',$list[0]);
			$this->display();
		}
	}
}
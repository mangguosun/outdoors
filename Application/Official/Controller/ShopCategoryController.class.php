<?php
namespace Official\Controller;
use Official\Builder\AdminConfigBuilder;
use Official\Builder\AdminListBuilder;
use Official\Builder\AdminTreeListBuilder;
use Official\Builder\AdminSortBuilder;

class ShopCategoryController extends BaseController
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
	*商品分类
	**/
	public function shop_category($tab=0){
		
		$data =D('shop_category')->where(array('siteid'=>SITEID,'status'=>array('egt',0)))->order('pid ASC,sort ASC,id ASC')->select();

		$info = getTree($data,0);
		$tree = $info;
		
		
		if($tab==0){
			$this->assign('tree',$tree);
			$this->display('shop_category');
		}elseif($tab==1){
			foreach($tree as $key=>$val){
				$up_title =D('shop_category')->where('id='.$val['pid'])->getfield('title');
				$tree[$key]['up_title']=$up_title;
			}
			$this->assign('tree',$tree);
			$this->display('shop_category_pic');
		}
	}
	
	  /**
     * 新增商品分类
     * @author yangweijie <yangweijiester@gmail.com>
     */
    public function add($title='',$sort=0,$pid=0,$level=0,$category_pic=''){
        if(IS_POST){
			$Category = D('ShopCategory');
			$title=trim($title);
		    if($title=='')$this->error('请输入分类名称');
		    if($sort!=''){
				if(!preg_match('/^\d+$/',$sort)) $this->error('请输入正确的数字排序');
			}
			
			if($level+1==2) $this->error('只能添加二级分类');
			$reds = $Category->where(array('siteid'=>SITEID,'status'=>array('egt',0)))->select();
			$infos = getTree($reds,0);
			
			if($pid==0){
				$level = $level;
			}else{
				$level = $level+1;
			}
		  
			foreach($infos as $val){
			   if($val['level']==$level && $val['pid']==$pid){
				   $new_infos[]=$val;
			    }
			}
			foreach($new_infos as $v){
				if($v['title']==$title){
					$this->error('您已添加该分类了!');
				}
			}
			
			$data['title'] = $title;
			$data['pid']   = $pid;
			$data['sort']  = $sort;
		    $data['level'] = $level;
		    $data['siteid'] = SITEID;
			$data['status'] = 1;
			$data['category_pic'] = $category_pic;
			$Category = D('ShopCategory');
            //$data = $Category->create();
		    if($data){
                $id = $Category->add($data);
                if($id){
                   $this->success('新增成功',U('ShopCategory/shop_category'));
                } else {
                    $this->error('新增失败');
                }
            } else {
                $this->error($Category->getError());
            }
        } else {
            $this->assign('info',array('pid'=>I('pid')));
			$category = D('ShopCategory')->where(array('siteid'=>SITEID,'status'=>array('gt',0)))->field(true)->select();
			
            $category = D('Common/Tree')->toFormatTree($category);
            $cates = array_merge(array(0=>array('id'=>0,'title_show'=>'顶级菜单')), $category);
		
		    $this->assign('cates', $cates);
            $this->meta_title = '新增菜单';
            $this->display('edit');
        }
    }
	
	 /**
	 * 编辑配置
	 * @author yangweijie <yangweijiester@gmail.com>
	 */
    public function edit($id = 0,$title='',$sort=0,$pid=0,$level=0,$category_pic=''){
        if(IS_POST){
            $Category = D('ShopCategory');
			$title=trim($title);
		    if($title=='')$this->error('请输入分类名称');
		    if($sort!=''){
				if(!preg_match('/^\d+$/',$sort)) $this->error('请输入正确的数字排序');
			}
			
			if($level+1==2) $this->error('只能添加二级分类');
			$reds = $Category->where(array('siteid'=>SITEID,'status'=>array('egt',0)))->select();
			$infos = getTree($reds,0);
			
			if($pid==0){
				$level = $level;
			}else{
				$level = $level+1;
			}
		   
			foreach($infos as $val){
			   if($val['level']==$level && $val['pid']==$pid){
				   $new_infos[]=$val;
			    }
			}
		
			foreach($new_infos as $v){
				if($v['title']==$title && $v['id']!=$id){
					$this->error('您已添加该分类了!');
				}
			}
			$data['title'] = $title;
			$data['pid']   = $pid;
			$data['sort']  = $sort;
		    $data['level'] = $level;
			$data['category_pic'] = $category_pic;
			
			$rs = $Category->where('id='.$id)->save($data);
            if($rs){
              $this->success('修改成功',U('ShopCategory/shop_category'));
               
            } else {
                $this->error('更新失败');
            }
			
        } else {
            $info = array();
            /* 获取数据 */
            $info = D('ShopCategory')->field(true)->find($id);
            $category = D('ShopCategory')->where(array('siteid'=>SITEID,'status'=>array('gt',0)))->field(true)->select();
			$category = D('Common/Tree')->toFormatTree($category);

            $cates = array_merge(array(0=>array('id'=>0,'title_show'=>'顶级菜单')), $category);
            $this->assign('cates', $cates);
            if(false === $info){
                $this->error('获取后台菜单信息错误');
            }
            $this->assign('info', $info);
            $this->meta_title = '编辑后台菜单';
            $this->display();
        }
    }
	
	public function ajax_status($id=0,$status=0){
		    
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
	/*
	*删除*
	*/
	public function ajax_del($id=0){
		if($id=='') $this->error('参数错误!');
		//$map=array('status' => array('egt',0),'siteid'=>SITEID);
		$map="status >= 0 and siteid = ".SITEID." and pid= " .$id;
       
		$childrs = D('shop_category')->where($map)->find();
	
		if($childrs){ 
			$this->error('有子类，不能删除');
		}else{ 
			$list = D('shop_category')->where("id=".$id)->setField('status',-1);
			if($list){ 
				$this->success('删除成功');
			}else{ 
				$this->error('删除失败');
			}
		}	
	}
	/*
	**
	*/
	/*public function  ajax_del($id=0){ 
		if($id=='') $this->error('参数错误');
		$map="status >= 0 and siteid = ".SITEID." and pid= " .$id;
		$data=D('shop_category')->where($map)->find();
		if($data){
			$re = D('shop_category')->where($map)->setField('status',-1);
			if($re){ 
				$list = D('shop_category')->where("id=".$id)->setField('status',-1);
				if($list){ 
					$this->success('删除成功');
				}else{ 
					$this->error('删除失败');
				}

			}else{ 
				$this->error('删除失败');

			}
		}else{ 
			$list = D('shop_category')->where("id=".$id)->setField('status',-1);
				if($list){ 
					$this->success('删除成功');
				}else{ 
					$this->error('删除失败');
				}

		}

	}*/



	public function categorystrash(){ 
		$map=array('status' => -1,'siteid'=>SITEID);
		$count=D('shop_category')->where($map)->count();
		$Page = new \Think\Page($count,10);
				$Page->setConfig('theme',"%UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END% <u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>共%TOTAL_ROW%条数据</u> ");
		$show   = $Page->show();		
		$list=D('shop_category')->where($map)->limit($Page->firstRow .','.$Page->listRows)->order('pid')->select();
		foreach ($list as $key => $val) {
			if(!$list[$key]['pid']){ 
				$fatherlist[$key]['title']='顶级分类';
			}else{
				$fatherlist[$key]=D('shop_category')->where("id= ".$val['pid']." and siteid = ".SITEID)->find();
			}
			$list[$key]['fathertitle']=$fatherlist[$key]['title'];
		}

		$this->assign('strash_list',$list);
		$this->assign('page',$show);

		//var_dump($list);

		$this->display();




		


	}
	 public function changeStatus($id=0,$method = null)
    {
    	
        $id = array_unique((array)I('id', 0)); 
        $id = is_array($id) ? implode(',', $id) : $id;
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        
        
        $map = "siteid = ".SITEID." and id in ($id)";
        
        switch (strtolower($method)) {
            case 'forbidcategory':
              	D('shop_category')->where($map)->setField('status', 1);
              	$this->success('启用成功');
				 break;
            case 'resumecategory':
                D('shop_category')->where($map)->setField('status',0);
              	$this->success('禁用成功');
                break;
            case 'deletecategory':
               D('shop_category')->where($map)->setField('status',-1);
              	$this->success('删除成功');
                break;
            case 'backcategory':
               
            	$list=D('shop_category')->where($map)->select();
            	foreach($list as $k=>$v){ 
            		if($list[$k]['pid']!=0){ 
            			
            			$data[$k]=D('shop_category')->where("id = ".$v['pid'])->find();
            			if($data[$k]['status']<0){ 
            				$this->error('父类已删除，请先还原父类！');
            			}
            			
            		}


            	}

            	D('shop_category')->where($map)->setField('status', 1);
              	$this->success('还原成功');
				break;
            
            default:
                $this->error('参数非法');
        }
    }











	
}  

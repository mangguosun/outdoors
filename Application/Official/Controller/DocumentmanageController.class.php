<?php

namespace Official\Controller;

use Think\Controller;

class DocumentmanageController extends BaseController
{	
    function _initialize()
    {
		$this->userdata = query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $uid);
  
    }
    public function index(){

    	$count	=	D('offcialdocument')->count();
		$Page       = new \Think\Page($count,10);
		$Pageshow       = $Page->show();
		$list =	D('offcialdocument')->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		
		
		foreach($list as $key=>&$val){
			 
			 if($val['category_id'] == 1){
				 $val['category_name'] = '最新公告';
			 }elseif($val['category_id'] == 2){
				$val['category_name'] = '常见问题' ;
			 }elseif($val['category_id'] == 3){
				$val['category_name'] = '精彩案例';
			 }elseif($val['category_id'] == 4){
				$val['category_name'] = '操作手册';
			 }
			 
		}
		
		$this->assign('document_cates',$list);
		$this->assign('page',$Pageshow);
		
		$this->assign('user',$this->userdata);
		$this->display();
	}
	public function document_add()
	{
	    $this->assign('user',$this->userdata);
		$this->display();
	}	
	
	
	public function document_edit()
	{
		$id	= $_GET['id'];
		$list = D('offcialdocument')->where("id=".$id)->find();
	    $this->assign('data_info',$list);
	    $this->assign('user',$this->userdata);
		$this->display();
	}




	public function document_doadd($category_id=0,$content='',$title='',$cover_id=0,$id=0)
	{
		if(IS_POST){
			
			if($id !=0){ //-修改-
				$data = array(
					'title' 		=> $title,
					'content'	=> $content,
					'category_id'			=> $category_id,
					'cover_id'     =>$cover_id,
					'update_time'   =>time(),
					'status'  =>   1,
					'siteid'  =>   1,
					'uid'  =>   is_login(),
					
				);

				$list = D('offcialdocument')->where("id=".$id)->save($data);
				if($list){
					$this->success('修改成功',U('Official/Documentmanage/index'));
				}else{
					$this->error('修改失败');
				}
			
			}else{  //-添加-
			   
			    $data = array(
				
			        'title' 		=> $title,
					'content'	=> $content,
					'category_id'			=> $category_id,
					'cover_id'     =>$cover_id,
					'create_time'   =>time(),
					'status'  =>   1,
					'siteid'  =>   1,
					'uid'  =>   is_login(),
				);
				
				$list = D('offcialdocument')->data($data)->add();
				if($list){
					$this->success('添加成功',U('Official/Documentmanage/index'));
				}else{
					$this->error('添加失败');
				}
			}
		
		}
	
	}

}


	
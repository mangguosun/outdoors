<?php
namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;

class LinksController extends BaseController
{
	
	protected $links;
	
    public function _initialize()
    {
        parent::_initialize();
		$this->links = D('links');
	}
	
	/*
	获取友情链接列表 Add by Jones Gong 2015-Jun-25
	*/
	public function index()
    {
	
	 //读取列表
		$map = array('siteid'=>SITEID,'status'=>array('egt','0'));
        $list=D('links')->where($map)->select();
		$this->assign('datainfo',$list);
		$this->display();	
	
	}
	
	
	/*
	*添加修改友情链接 add by Jones 2015-Jun-24**
	**/
	public function links_edit($id = 0,$title = '',$link = '',$level = 0){
		
		$isEdit = $id ? 1 : 0;
		if (IS_POST) {
			
            if ($title == '' || $title == null) {
                $this->error('请输入链接标题');
            }
			if($link!=''){
				if(checked_url($link)){
					$this->error('请输入正确的链接地址');
				}
			   $link=new_url($link);
			}
			if(!is_numeric($level)) $this->error('优先级必须为数字');
			
			//dump($link);
			$link_data['title'] = $title;
			$link_data['link'] = $link;
			$link_data['level'] = $level; 
			
			
			
 			if ($isEdit) {
                $rs = $this->links->where('id=' . $id)->save($link_data);
            } else {
				
				$link_data['create_time']=time();
				$link_data['siteid']=SITEID;
				$link_data['status'] = 1;
                $rs = $this->links->add($link_data);
            }
            if ($rs) {
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Links/index'));
            } else {
                $this->error($isEdit ? '编辑失败' : '添加失败');
            }
		
		}else{
			
			if ($isEdit) {
                $links_data = D('links')->where('id=' . $id)->find();
            } else {
				$links_data['status'] = 1;
				$links_data['level']=0;
            }
			$links_data['pag_title'] = $isEdit ? '编辑友情链接' : '添加友情链接';		
			$this->assign('datainfo',$links_data);
			$this->display();
		}
	}

}
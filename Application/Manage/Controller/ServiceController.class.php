<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;

/*服务企业*/
class ServiceController extends BaseController
{
	protected $member_service;
   
    public function _initialize()
    {
        parent::_initialize();
		$this->member_service = D('member_service');
	}
	
    public function config()
    {
        $admin_config = new AdminConfigBuilder();
        $data = $admin_config->handleConfig();

        $admin_config->title('关于我们基本设置')
            ->keyBool('NEED_VERIFY', '关于我们是否需要审核','默认无需审核')
            ->buttonSubmit('', '保存')->data($data);
        $admin_config->display();
    }
	
    public function index($page = 1, $r = 20){
		
	           //读取列表
       $map = array('status' => array('egt',0),'siteid'=>SITEID);
       $list = $this->member_service->where($map)->page($page, $r)->order(array('id'=>'desc'))->select();
    
       $attr['class'] = 'ajax-post';
       $attr['target-form'] = 'ids';
        foreach($list as &$v){
            $v['new_status'] = $v['status'] == 1 ?'启用':'禁用';
        }
        $content = D('websit')->where(array('siteid'=>SITEID))->find();
        $title = $content['service_process'] ? '编辑服务企业URL' : '添加服务企业URL';
        $this->assign('title',$title);
        $this->assign('list',$list);
        $this->assign('content',$content);
        $this->display();
       
    }
	

	
    /*
	*添加~修改* 2015-1-13 dlx
	***/
    public function service_edit($id=0,$title='',$url=''){
		$isEdit = $id ? 1 : 0;
        if (IS_POST) {
			$title=op_t(trim($title));
			if($title==''){
				$this->error('公司名称不能为空');
			}
			if($url!=''){
				if(!preg_match('/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',$url)){
					$this->error('url不正确');
				}
			}
			
            $data['title']	=	$title;
			$data['url']    =   $url;
		    
			if ($isEdit) {
			  $rs_content = $this->member_service->where('id=' . $id)->save($data);
				
            }else{
				$data['siteid']	 =	SITEID;
				$data['status']  =  1;
				$rs_content = $this->member_service->add($data);
			} 
			
            if ($rs_content) {
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Service/index'));
            } else {
                $this->error($isEdit ? '编辑失败' : '添加失败');
            }
			
        } else {

			$title = $isEdit ? '编辑服务企业URL' : '添加服务企业URL';
            $this->assign('title',$title);
			if ($isEdit) {
				
                $comment_data = $this->member_service->where('id=' . $id)->find();

                $this->assign('comment_data',$comment_data);
                 $this->display();
            }else{
				$comment_data['status']=1;

                $this->assign('comment_data',$comment_data);
                $this->display();
			} 
        
		}
		
	}
	/*
	*编辑服务流程URL
	**/
	public function service_process_edit($service_process=''){
		$content = D('websit')->where(array('siteid'=>SITEID))->find();
		$isEdit = $content['service_process'] ? 1 : 0;
        if (IS_POST) {
			
			if(!preg_match('/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',$service_process)){
				$this->error('url不正确');
			}
			
			$data['service_process']    =   $service_process;
		    
			if ($isEdit) {
			   $rs_content = D('websit')->where('siteid='.SITEID)->save($data);				
            }else{
			   $rs_content = D('websit')->where('siteid='.SITEID)->save($data);
			} 
			
            if ($rs_content) {
            	$domain = $_SERVER['HTTP_HOST'];
            	clean_website_info_cash($domain);
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Service/index'));
            } else {
                $this->error($isEdit ? '编辑失败' : '添加失败');
            }
			
        } else {
         
			$title = $isEdit ? '编辑服务企业URL' : '添加服务企业URL';
			$this->assign('title',$title);
			if ($isEdit) {

                $this->assign('content',$content);
                $this->display();
            }else{
				
                 $this->assign('content',$content);
                $this->display();
			} 
        
		}
		
	}
	

}  
<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;


class AboutController extends BaseController
{
	protected $about;
   
    public function _initialize()
    {
        parent::_initialize();
		$this->about = D('about');
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
	
    public function index($page = 1, $r = 10){
		
	    //读取列表
        $map = array('status' => array('egt',0),'siteid'=>SITEID);
        $list = $this->about->where($map)->page($page, $r)->order(array('id'=>'desc'))->select();
        foreach($list as &$v){
            $v['new_status'] = $v['status']?'已启用':'已禁用';
            $data = query_user(array('nickname'),$v['uid']);
            $v['uid_name'] = $data['nickname']?$data['nickname']:'游客';
        }
       
        $this->assign('datainfo',$list);
        $this->display();
    }
	
	 /*
	*添加修改* 2015-1-12 dlx
	***/
    public function about_edit($id=0,$content='',$title=''){
		$isEdit = $id ? 1 : 0;
		
        if (IS_POST) {
			$title = op_t(trim($title));
		    if($title==''){
			  $this->error('请填写类别名称');
			}
		    if($content==''){
			  $this->error('请填写内容');
			}
            
			$about_data['title']	=	$title;
			$about_data['content']	=	$content;
		    
			if ($isEdit) {
			  
                $rs_content = $this->about->where('id=' . $id)->save($about_data);
				
            } else {
				$about_data['uid']	=   is_login();
				$about_data['siteid']	= SITEID;
				$about_data['status'] = 1;
              
                $rs_content = $this->about->add($about_data);
				
            }
            if ($rs_content) {
                $this->success($isEdit ? '编辑成功' : '添加成功', U('About/index'));
            } else {
                $this->error($isEdit ? '编辑失败' : '添加失败');
            }
			
        } else {
     
			
            if ($isEdit) {			
                $about_data = $this->about->where('id=' . $id)->find();
               

            } else {
				
               
               
               
            }
	    $about_data['page_title'] = $isEdit ? '编辑关于我们' : '添加关于我们';
	     $this->assign('datainfo',$about_data);
	     $this->display();
	    
        }
		
	}
	
	
   
	

}  
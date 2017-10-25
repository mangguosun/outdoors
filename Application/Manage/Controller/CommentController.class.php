<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;

/*评论管理*/
class CommentController extends BaseController
{
	protected $local_comment;
   
    public function _initialize()
    {
        parent::_initialize();
		$this->local_comment = D('local_comment');
	}
	
    public function config($is_comment=0)
    {
       if (IS_POST) {
        	$rs=addWebsitConfig($is_comment,'is_comment');
            if ($rs) {
                $this->success('编辑成功', U('comment/config'));
            } else {
                $this->error('编辑失败');
            }
        
        } else {
  
			
			$is_comment = getWebsitConfig('is_comment',2);
			$this->assign('is_comment',$is_comment);
			$this->display();
        }
    }
	/*
	*活动评论
	*/
	public function index(){
	    //读取列表
        $map = array('status' => array('egt',0),'siteid'=>SITEID);
        $list = $this->local_comment->where($map)->order(array('id'=>'desc'))->select();
		foreach ($list as $key => &$value) {
			if ($value['uid'] != 0) {
               	$users  = query_user(array('id','username','nickname', 'email','mobile'), $value['uid']);
            }else{ 
            	$users['nickname']='游客';
            }	
			 $value['nickname'] =$users['nickname'];
			 
				if($value['app'] =='Event'){
					 $value['url'] =U('Event/Index/detail',array('id'=>$value['row_id']));
					 $value['model'] ='活动';
					 $value['model_url'] =U('Comment/event');	 
				}elseif($value['app'] =='Issue'){
					 $value['url'] =U('Issue/Index/issueContentDetail',array('id'=>$value['row_id']));	
					 $value['model'] ='故事';
					 $value['model_url'] =U('Comment/issue');
				}elseif($value['app'] =='Blog'){
					 $value['url'] =U('Blog/Article/detail',array('id'=>$value['row_id']));
					 $value['model'] ='公告';
					 $value['model_url'] =U('Comment/blog');
				} 
		}
		$this->assign('datainfo',$list);
		$this->assign('page_title','评论');
		
		$this->display();

    }
	/*
	*活动评论
	*/
	public function event(){
	    //读取列表
        $map = array('status' => array('egt',0),'siteid'=>SITEID,'app'=>'Event');
        $list = $this->local_comment->where($map)->order(array('id'=>'desc'))->select();
		foreach ($list as $key => &$value) {
			if ($value['uid'] != 0) {
               	$users  = query_user(array('id','username','nickname', 'email','mobile'), $value['uid']);
            }else{ 
            	$users['nickname']='游客';
            }		
			 $value['nickname'] =$users['nickname'];
			 $value['url'] =U('Event/Index/detail',array('id'=>$value['row_id']));	 
		}
		$this->assign('datainfo',$list);
		$this->assign('page_title','公告评论');
		$this->display('Comment/comment');

    }
	/*
	*故事评论
	*/
	public function issue(){
	    //读取列表
        $map = array('status' => array('egt',0),'siteid'=>SITEID,'app'=>'Issue');
        $list = $this->local_comment->where($map)->order(array('id'=>'desc'))->select();
		foreach ($list as $key => &$value) {
            if ($value['uid'] != 0) {
               	$users  = query_user(array('id','username','nickname', 'email','mobile'), $value['uid']);
            }else{ 
            	$users['nickname']='游客';
            }
		$value['nickname'] =$users['nickname'];
		$value['url'] =U('Issue/Index/issueContentDetail',array('id'=>$value['row_id']));
			 
			 
		}
		$this->assign('datainfo',$list);
		$this->assign('page_title','故事评论');
		$this->display('Comment/comment');

    }
	/*
	*公告评论**
	*/
	public function blog(){
	    //读取列表
        $map = array('status' => array('egt',0),'siteid'=>SITEID,'app'=>'Blog');
        $list = $this->local_comment->where($map)->order(array('id'=>'desc'))->select();
		foreach ($list as $key => &$value) {	
			 if ($value['uid'] != 0) {
               	$users  = query_user(array('id','username','nickname', 'email','mobile'), $value['uid']);
            }else{ 
            	$users['nickname']='游客';
            }
			 $value['nickname'] =$users['nickname'];
			 $value['url'] =U('Blog/Article/detail',array('id'=>$value['row_id']));
		}
		$this->assign('datainfo',$list);
		$this->assign('page_title','公告评论');
		$this->display('Comment/comment');

    }
	
	/*
	*清空回收站**
	*/
	public function commenttrash($page = 1, $r = 20, $model = ''){
		
		$builder = new AdminListBuilder();
		$builder->clearTrash($model);
		//读取列表
        $map = array('status' => -1,'siteid'=>SITEID);
        $list = $this->local_comment->where($map)->page($page, $r)->select();
        $totalCount = $this->local_comment->where($map)->count();
        //显示页面
     

        $attr['class'] = 'ajax-post';
        $attr['target-form'] = 'ids';

        $builder->title('故事回收站')
            ->setStatusUrl(U('setCommentContentStatus'))->buttonRestore()->buttonClear('local_comment')
            
			->keyId('id','ID')
			->keyId('content','评论内容')
		
			->keyUid('uid','发布人')
			->keyCreateTime('create_time','评论时间')
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
    public function setCommentContentStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('local_comment', $ids, $status);
    }
	
   
	

}  
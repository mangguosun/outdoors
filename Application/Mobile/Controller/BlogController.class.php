<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Mobile\Controller;

use Think\Controller;

/**
 * 前台公共控制器
 * 为防止多分组Controller名称冲突，公共Controller名称统一使用分组名称
 */
class BlogController extends Controller {

	/* 空操作，用于输出404页面 */
	public function _empty(){
		$this->redirect('Blog/index');
	}
    protected function _initialize(){
        /* 读取站点配置 */
        $config = api('Config/lists');
        C($config); //添加配置

        if (!C('WEB_SITE_CLOSE')) {
            $this->error('站点已经关闭，请稍后访问~');
        }
		$model_info = get_appinfo('Blog');
		if(!$model_info){
			$this->error('应用未开启');
		}
		$tree=D('category')->where("siteid=".SITEID ." and status=1")->getTree();
		$this->assign('tree',$tree);
		
		$this->assign('model_info', $model_info);
        $this->setTitle('官方公告');

    }

    
    protected function ensureApiSuccess($result)
    {
        if (!$result['success']) {
            $this->error($result['message'], $result['url']);
        }
    }
	//系统首页
    public function index($page = 1, $id = 0)
    {

        /* 分类信息 */
        $category = 0; //$this->category();

        /* 获取当前分类列表 */
        $Document = D('Document');
	    $map['siteid']=SITEID;
		$map['status']=1;
		if($id !=''){
		  $map['category_id']=$id;
		}
		$list = $Document->where($map)->page($page,10)->order("id desc")->select();
		foreach($list as $key=>$val){
		 $list[$key]['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $val['uid']);
			// $val['issue'] = D('Category')->field('id,title')->where(array('id'=>$v['issue_id'],'siteid'=>SITEID))->find();
		}
		if($id){
			$category_blog=D('category')->where("siteid=".SITEID ." and id=".$id ." and status=1")->find();
			$this->assign('category_blog', $category_blog);
		}
		$get_url = json_encode($_GET);
		$this->assign('get_url', $get_url);
        $this->assign('list', $list);
		$this->assign('blog_id', $id);
		$this->assign('page', D('Document')->page); //分页
        $this->display();
    }
	
    public function get_blog_index($page = 0, $id = 0)
    {

        /* 分类信息 */
        $category = 0; //$this->category();

        /* 获取当前分类列表 */
        $Document = D('Document');
	    $map['siteid']=SITEID;
		$map['status']=1;
		if($id !=''){
		  $map['category_id']=$id;
		}
		$start = $page*10; 
		$list = $Document->where($map)->field('id,siteid,uid,title,create_time')->limit($start, 10)->order("id desc")->select();
		foreach($list as $key=> &$val){
		 	$val['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $val['uid']);
			$val['create_time'] = date('Y-m-d',$val['create_time']);
			$val['url'] =U('Mobile/Blog/detail',array('id'=>$val['id']));
		}

		exit(json_encode($list));
    }	
	

    /* 文档分类检测 */
    private function category($id = 0)
    {

    }
	public function detail($id = 0){
		/* 标识正确性检测 */
	    if(id && is_numeric($id)){
			$blog_content=D('document')->where('id='.$id)->find();
			$blog_content['content']=ludou_remove_width_height_attribute(D('document_article')->where("id=".$id)->getField('content'));
			
			
			
			$blog_content['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $blog_content['uid']);
			
			
			
			$this->assign('content',$blog_content);
			$this->setTitle('{$content.title|op_t}' . '——公告');
		}
		$this->display();
	}
	
}

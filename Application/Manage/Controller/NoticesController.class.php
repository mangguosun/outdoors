<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;


class NoticesController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize();
		
       $this->documentModel = D('document');
	   $this->category = D('category');
	
	   
	}
	
    public function index(){
			$map['status']=array('egt','0');
			$map['siteid']=SITEID;
			$list = D('document')->where($map) ->order("id desc")->select();
			foreach ($list as $key => &$value) {	
				 $users  = query_user(array('id','username','nickname', 'email','mobile'), $value['uid']);
				$value['nickname'] =$users['nickname'];
			}
			$this->assign('decument_list',$list);
			$this->display();
		
    }
	

	
	public function noticestrash($page = 1, $r = 20, $model = ''){
		
		$builder = new AdminListBuilder();
		$builder->clearTrash($model);
		//读取列表
        $map = array('status' => -1,'siteid'=>SITEID);
        $list = $this->documentModel->where($map)->page($page, $r)->select();
        $totalCount = $this->documentModel->where($map)->count();
        //显示页面
        $attr['class'] = 'ajax-post';
        $attr['target-form'] = 'ids';

        $builder->title('公告回收站')
            ->setStatusUrl(U('setEventContentStatus'))->buttonRestore()->buttonClear('document')
            ->keyId('id','ID')
			->keyLink('title', '标题', 'Blog/Article/detail?id=###')
			->keyUid('uid','发布人')
			->keyCreateTime('create_time','创建时间')
			->keyUpdateTime('update_time','更新时间')
			->KeyStatusReversion()
		    ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }
	
	 /**
     * @param int $id
     * @param str $title
     * @author 郑钟良<dlx@huodongli.cn>
     */
    public function noticesedit($id = 0, $title = '',$category_id = 0, $content = '')
    {
        $isEdit = $id ? 1 : 0;
        if (IS_POST) {
            if ($title == '' || $title == null) {
                $this->error('请输入公告标题');
            }
            if ($category_id == '' || $category_id == 0) {
                $this->error('请选择公告分类');
            }
            if ($content == '' || $content == null) {
                $this->error('请输入公告内容');
            }
			
            $notice_data['title'] = $title;
			$notice_data['category_id'] = $category_id;
			$notice_data_content['content'] = $content; 
            if ($isEdit) {
				$notice_data['update_time']=time();
                $rs = $this->documentModel->where('id=' . $id)->save($notice_data);
				if($rs){
					$rs_content = D('document_article')->where('id=' . $id)->save($notice_data_content);
				}	
            } else {
				$notice_data['create_time']=time();
				$notice_data['siteid']=SITEID;
				$notice_data['uid']=is_login();
				$notice_data['status'] = 1;
                $notice_data['createtime'] = time();
				$notice_data['update_time']=time();
                $rs = $this->documentModel->add($notice_data);
				if($rs){
					$notice_data_content['id'] = $rs;
					$notice_data_content['siteid'] = SITEID;
					$rs_content = D('document_article')->add($notice_data_content);
				}	
            }
            if ($rs_content) {
				    $rs_find = D('qrcode')->where(array('linkid'=>$rs,'types'=>'blog'))->find();

					if(!$rs_find){
                        $qrcode_url = set_qrcode(array('id'=>$rs),'blog');
                        $qrcode_data = array(
										'siteid'  		=> SITEID,
										'uid'	  		=> is_login(),
										'linkid'  		=> $rs,
										'types'   		=> 'blog',
										'url'	 	 	=> $qrcode_url,
										'create_time'	=> time()
									);
						D('qrcode')->add($qrcode_data);
					}
					
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Notices/index'));
            } else {
                $this->error($isEdit ? '编辑失败' : '添加失败');
            }
        } else {

            //获取分类列表
            $category_map['status'] = array('egt', 0);
			$category_map['siteid'] = SITEID;
            $goods_category_list = $this->category->where($category_map)->order('pid desc')->select();
			
			
            $category_arr = array_combine(array_column($goods_category_list, 'id'), array_column($goods_category_list, 'title'));
			if ($isEdit) {
               $notice_data = $this->documentModel->where('id=' . $id)->find();
			   if($notice_data){
					$notice_content = D('document_article')->field('content')->where('id=' . $notice_data['id'])->find();
			   }
			   if($notice_content)$notice_data = array_merge($notice_data,$notice_content);
            } else {
				$notice_data['status'] = 1;
            }
			$notice_data['pag_title'] = $isEdit ? '编辑公告' : '添加公告';	
			$notice_data['categorys'] = $category_arr;
			$this->assign('datainfo',$notice_data);
			$this->display();
			
        }
    }
	
    /**
     * 设置推荐or取消推荐
     * @param $ids
     * @param $tip
     * autor:xjw129xjt
     */
    public function doRecommend($id, $tip)
    {
        $this->documentModel->where(array('id' => array('in', $id)))->setField('is_recommend', $tip);
        $this->success('设置成功', $_SERVER['HTTP_REFERER']);
    }

    /**
     * 设置状态
     * @param $ids
     * @param $status
     * autor:xjw129xjt
     */
    public function setEventContentStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('document', $ids, $status);
    }
	
   
	/*
	*公告类型列表*
	**/
    public function noticestype(){
		//读取列表
        $map = array('status' => array('egt',0),'siteid'=>SITEID);
        $list = $this->category->where($map)->select();
		$this->assign('datainfo',$list);
		$this->display();
	}
	
	 /*
	 **新增公告类型**
	 */
     public function noticestype_edit($id = 0, $title = '',$sort = 0)
	 {
       $isEdit = $id ? 1 : 0;
        if (IS_POST) {
            if ($title == '' || $title == null) {
                $this->error('请输入公告类型');
            }
            if($sort != ''){
				if(!is_numeric($sort)){
					$this->error('排序请输入数字!');
				}
				
			}
          
			$noticestype_data['title'] = $title;
			$noticestype_data['sort'] = $sort;
		
            if ($isEdit) {
				$noticestype_data['update_time']=time();
                $rs = $this->category->where('id=' . $id)->save($noticestype_data);
				
            } else {
				$noticestype_data['siteid']			=	SITEID;
			    $noticestype_data['pid']			=	0;
				$noticestype_data['status'] 		= 	1;
                $noticestype_data['create_time'] 	= 	time();
				$noticestype_data['update_time']	=	time();
				$rs = $this->category->add($noticestype_data);
			}
            if ($rs) {
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Notices/noticestype'));
            } else {
                $this->error($isEdit ? '编辑失败' : '添加失败');
            }
        
		
		} else {
			if ($isEdit) {
               $notice_data = $this->category->where('id=' . $id)->find();
            } else {
				$notice_data['status'] = 1;
				$notice_data['sort'] = 0;
            }
			$notice_data['pag_title'] = $isEdit ? '编辑公告类型' : '添加公告类型';	
			$notice_data['categorys'] = $category_arr;
			$this->assign('datainfo',$notice_data);
			$this->display();
		}
	
    }
	/**公告分类回收站**/
	public function noticetype_strash($page = 1, $r = 20, $model = ''){
		
		$builder = new AdminListBuilder();
		$builder->clearTrash($model);
		//读取列表
        $map = array('status' => -1,'siteid'=>SITEID);
        $list = $this->category->where($map)->page($page, $r)->select();
        $totalCount = $this->category->where($map)->count();
        //显示页面
        
		$attr['class'] = 'ajax-post';
        $attr['target-form'] = 'ids';

        $builder->title('公告类型回收站')
            ->setStatusUrl(U('setNoticesContentStatus'))->buttonRestore()//->buttonClear('category')
            ->keyId('id','ID')
			->keyLink('title', '标题', 'Blog/Article/lists?category=###')
			->keyCreateTime('create_time','创建时间')
			->keyUpdateTime('update_time','更新时间')
			->KeyStatusReversion()
		    ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }
	

}  
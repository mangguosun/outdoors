<?php
namespace Addons\Topic\Controller;
use Admin\Controller\AddonsController;
class TopicController extends AddonsController{
	/* 添加话题设置 */
	public function add(){
		$this->meta_title = '添加话题设置';
		$current = U('/Admin/Addons/adminList/name/Topic');
		$this->assign('current',$current);
		$this->display(T('Addons://Topic@Topic/edit'));
	}
	
	/* 编辑话题设置 */
	public function edit(){
		$this->meta_title = '修改话题设置';
		$id     =   I('get.id','');
		$current = U('/Admin/Addons/adminList/name/Topic');
		$detail = D('Addons://Topic/Topic')->detail($id);
		$this->assign('info',$detail);
		$this->assign('current',$current);
		$this->display(T('Addons://Topic@Topic/edit'));
	}
	
	/* 取消推荐话题设置 */
	public function offtop(){
		$this->meta_title = '取消置顶话题设置';
		$id     =   I('get.id','');
		if(D('Addons://Topic/Topic')->offtop($id)){
			$this->success('成功取消置顶该话题设置', U('/Admin/Addons/adminList/name/Topic'));
		}else{
			$this->error(D('Addons://Topic/Topic')->getError());
		}
	}
	
	/* 推荐话题设置*/
	public function top(){
		$this->meta_title = '置顶话题设置';
		$id     =   I('get.id','');
		if(D('Addons://Topic/Topic')->top($id)){
			$this->success('成功置顶该话题设置', U('/Admin/Addons/adminList/name/Topic'));
		}else{
			$this->error(D('Addons://Topic/Topic')->getError());
		}
	}
	
	
	/* 删除话题设置 */
	public function del(){
		$this->meta_title = '删除话题设置';
		$id     =   I('get.id','');
		if(D('Addons://Topic/Topic')->del($id)){
			$this->success('删除成功', U('/Admin/Addons/adminList/name/Topic'));
		}else{
			$this->error(D('Addons://Topic/Topic')->getError());
		}
	}
	
	
	/* 批量操作推荐*/
	public function topsave($Model=CONTROLLER_NAME){
		$ids    =   I('post.ids');
		$top =   I('get.top');
		if(empty($ids)){
			$this->error('请选择要操作的数据');
		}
	
		if($top == 1){
			foreach ($ids as $id)
			{
				D('Addons://Topic/Topic')->top($id);
			}
			$this->success('成功推荐选择的话题设置',U('/Admin/Addons/adminList/name/Topic'));
		}else{
			foreach ($ids as $id)
			{
				D('Addons://Topic/Topic')->offtop($id);
			}
			$this->success('成功取消推荐选择的话题设置',U('/Admin/Addons/adminList/name/Topic'));
		}
	}
	
	
	/* 更新话题设置 */
	public function update(){
		$this->meta_title = '更新话题设置';
		$res = D('Addons://Topic/Topic')->update();
		if(!$res){
			$this->error(D('Addons://Topic/Topic')->getError());
		}else{
			if($res['id']){
				$this->success('更新成功', U('/admin/addons/adminlist/name/Topic'));
			}else{
				$this->success('新增成功', U('/admin/addons/adminlist/name/Topic'));
			}
		}
	}
}

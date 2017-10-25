<?php
namespace Addons\Bboard\Controller;
use Admin\Controller\AddonsController;
class BboardController extends AddonsController{
	/* 添加更新日志 */
	public function add(){
		$this->meta_title = '添加更新日志';
		$info['title'] = date('Ymd',time());
		$this->assign('info',$info);
		$current = U('/admin/addons/adminlist/name/Bboard');
		$this->assign('current',$current);
		$this->display(T('Addons://Bboard@Bboard/edit'));
	}
	
	/* 编辑更新日志 */
	public function edit(){
		$this->meta_title = '修改更新日志';
		$id     =   I('get.id','');
		$current = U('/admin/addons/adminlist/name/Bboard');
		$detail = D('Addons://Bboard/Bboard')->detail($id);
		$this->assign('info',$detail);
		$this->assign('current',$current);
		$this->display(T('Addons://Bboard@Bboard/edit'));
	}
	
	/* 禁用更新日志 */
	public function forbidden(){
		$this->meta_title = '禁用更新日志';
		$id     =   I('get.id','');
		if(D('Addons://Bboard/Bboard')->forbidden($id)){
			$this->success('成功禁用该更新日志', U('/admin/addons/adminlist/name/Bboard'));
		}else{
			$this->error(D('Addons://Bboard/Bboard')->getError());
		}
	}
	
	/* 启用更新日志*/
	public function off(){
		$this->meta_title = '启用更新日志';
		$id     =   I('get.id','');
		if(D('Addons://Bboard/Bboard')->off($id)){
			$this->success('成功启用该更新日志', U('/admin/addons/adminlist/name/Bboard'));
		}else{
			$this->error(D('Addons://Bboard/Bboard')->getError());
		}
	}
	
	/* 删除更新日志 */
	public function del(){
		$this->meta_title = '删除更新日志';
		$id     =   I('get.id','');
		if(D('Addons://Bboard/Bboard')->del($id)){
			$this->success('删除成功', U('/admin/addons/adminlist/name/Bboard'));
		}else{
			$this->error(D('Addons://Bboard/Bboard')->getError());
		}
	}
	
	/* 更新更新日志 */
	public function update(){
		$this->meta_title = '更新更新日志';
		$res = D('Addons://Bboard/Bboard')->update();
		if(!$res){
			$this->error(D('Addons://Bboard/Bboard')->getError());
		}else{
			if($res['id']){
				$this->success('更新成功', U('/admin/addons/adminlist/name/Bboard'));
			}else{
				$this->success('新增成功', U('/admin/addons/adminlist/name/Bboard'));
			}
		}
	}
}

<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <code-tech.diandian.com>
// +----------------------------------------------------------------------

namespace Manage\Model;
use Think\Model;

/**
 * 插件模型
 * @author yangweijie <yangweijiester@gmail.com>
 */

class MenuModel extends Model {
	protected $tableName='websit_menu_new';
	protected $_validate = array(
		array('url','require','url必须填写'), //默认情况下用正则进行验证
	);

	//获取树的根到子节点的路径
	public function getPath($id){
		$path = array();
		$nav = $this->where("id={$id}")->field('id,pid,title,url')->find();
		$path[] = $nav;
		if($nav['pid'] >1){
			$path = array_merge($this->getPath($nav['pid']),$path);
		}
		return $path;
	}
	
	//获取树的根到子节点的路径
	public function getPath_all($pid){
		$map['pid']=$pid;
		$authority=$this->authority_value();
		if(!empty($authority)){ 
			$map['id']=array('not in',$authority);
		}
		
		$map['hide']=0;
		$map['is_dev']=0;
		$data = $this->where($map)->field(true)->order('sort asc')->select();
		return $data;
	}

	public function authority_value(){
		$data=array();

		if(!is_admin()){ 
			array_push($data,"164");
		}
		if(!websit_trave()){ 
			array_push($data,'187','188');
		}
		if(!websit_runners()){ 
			array_push($data,'189','190');
		}
		return $data;
	}
	
	
	
	
	
}
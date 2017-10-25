<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <code-tech.diandian.com>
// +----------------------------------------------------------------------

namespace Addons\Ueditor;
use Common\Controller\Addon;

/**
 * 编辑器插件
 * @author yangweijie <yangweijiester@gmail.com>
 */

	class UeditorAddon extends Addon{

		public $info = array(
				'name'=>'Ueditor',
				'title'=>'百度编辑器',
				'description'=>'用于增强整站长文本的输入和显示',
				'status'=>1,
				'author'=>'sundawei',
				'version'=>'0.1'
			);

		public function install(){
			
			//添加钩子
			$Hooks = M("Hooks");
			$Ueditor = array(array(
				'name' => 'Ueditor',
				'description' => '百度编辑器钩子',
				'type' => 1,
				'update_time' => NOW_TIME,
				'addons' => 'Ueditor'
			));
			$Hooks->addAll($Ueditor,array(),true);
			if ( $Hooks->getDbError() ) {
				session('addons_install_error',$Hooks->getError());
				return false;
			}
            return true;
		}

		public function uninstall(){
			$Hooks = M("Hooks");
			$map['name']  = array('in','Ueditor');
			$res = $Hooks->where($map)->delete();
			if($res == false){
				session('addons_install_error',$Hooks->getError());
				return false;
			}
            return true;
		}

		/**
		 * 编辑器挂载的文章内容钩子
		 * @param array('name'=>'表单name','value'=>'表单对应的值')
		 */
		public function Ueditor($data){
			$this->assign('addons_data', $data);
			$this->assign('addons_config', $this->getConfig());
			$this->display('content');
		}

	}

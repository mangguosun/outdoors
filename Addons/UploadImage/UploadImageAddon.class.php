<?php

namespace Addons\UploadImage;
use Common\Controller\Addon;

/**
 * 图片批量上传插件
 * @author 原作者:tjr&jj
 * @author 木梁大囧
 */

    class UploadImageAddon extends Addon{
        public $info = array(
            'name' => 'UploadImage',
            'title' => '单图上传',
            'description' => '单图上传',
            'status' => 1,
            'author' => '单图上传',
            'version' => '1.1'
        );

        public function install(){
            return true;
        }

        public function uninstall(){
            return true;
        }

        //实现的UploadImages钩子方法
        public function UploadImage($param){

			
            $param['find_name'] = $param['find_name'] ? : 'pics';
			$param['buttontext'] = $param['buttontext'] ? : '上传图片了';
			$param['buttonwidth'] = $param['buttonwidth'] ? : '120';
			$param['multi'] =  $param['multi']? : 'false';
			$param['ds'] =  $param['ds']? '(建议'.$param['ds'].')' : '';
			$param['picture_type'] = $param['picture_type']?$param['picture_type']:'*.jpg; *.png; *.gif; *.ico;';
			$param['width'] =  $param['width']? $param['width']: '';
			$param['height'] =  $param['height']? $param['height']: '';
			if($param['width'] && $param['height']){
				
				if($param['width']=='auto'){
					$param['style_height']=$param['height'].'px';
					$param['style_width'] = '100%';
				}else if($param['width'] > 100){
					$param['style_width']='100px';
					$param['style_height'] =  $param['height'] *(100/$param['width'] ).'px';
				}else{
					$param['style_width']=$param['width'].'px';
					$param['style_height'] =  $param['style_width'] *($param['width'] / $param['height'] ).'px';
				}	
				
			}else{
				$param['style_width']='100px;';
				$param['style_height'] =  '70px';
			}

	
			
	
			
            $valArr = $param['value'] ? explode(',', $param['value']) : array();
            $this->assign('infos',$param);
            $this->assign('valArr',$valArr);
            $this->display('upload');
        }
    }
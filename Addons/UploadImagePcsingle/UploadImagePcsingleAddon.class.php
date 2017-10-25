<?php

namespace Addons\UploadImagePcsingle;
use Common\Controller\Addon;

/**
 * 图片批量上传插件
 * @author 原作者:tjr&jj
 * @author 木梁大囧
 */

    class UploadImagePcsingleAddon extends Addon{
        public $info = array(
            'name' => 'UploadImagePcsingle',
            'title' => '电脑单图上传',
            'description' => '电脑单图上传',
            'status' => 1,
            'author' => '电脑单图上传',
            'version' => '1.1'
        );

        public function install(){
            return true;
        }

        public function uninstall(){
            return true;
        }

        //实现的UploadImages钩子方法
        public function UploadImagePcsingle($param){
			
            $param['find_name'] = $param['find_name'] ? : 'pics';
			$param['title'] =  $param['title']? $param['title'] : '';
			$param['picture_type'] = $param['picture_type']?$param['picture_type']:'*.jpg; *.png; *.gif; *.ico;';
			
			$param['display_width'] =  $param['display_width']? $param['display_width']: 80;
			$param['display_height'] =  $param['display_height']? $param['display_height']: 80;
			
			$param['thumb_width'] =  $param['thumb_width']? $param['thumb_width']: 0;
			$param['thumb_height'] =  $param['thumb_height']? $param['thumb_height']: 0;
			
			
            $thumb_pic = $param['value'] ? explode(',', $param['value']) :'';
			if($thumb_pic){
				foreach ($thumb_pic as $key => $pic) {
					$thumb_picture[$key]['id'] = $pic;
					if($param['thumb_width']&& $param['thumb_height']){ 
						$thumb_picture[$key]['url_thumb']=getThumbImageById($pic,$param['thumb_width'],$param['thumb_height']);
					}else{ 
						$thumb_picture[$key]['url_thumb']=get_cover($pic,'path');
					}
				}	
				$this->assign('thumb_picture',$thumb_picture);
			}

            $this->assign('infos',$param);
            $this->display('upload_pc');
        }
    }
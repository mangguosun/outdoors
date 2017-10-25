<?php

namespace Addons\UploadImageMobile;
use Common\Controller\Addon;

/**
 * 图片批量上传插件
 * @author 原作者:tjr&jj
 * @author 木梁大囧
 */

    class UploadImageMobileAddon extends Addon{
        public $info = array(
            'name' => 'UploadImageMobile',
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
        public function UploadImageMobile($param){
			
            $param['find_name'] = $param['find_name'] ? : 'pics';
			$param['multi'] =  $param['multi']? : 'false';
			$param['ds'] =  $param['ds']? '(建议'.$param['ds'].')' : '';
			$param['picture_type'] = $param['picture_type']?$param['picture_type']:'*.jpg; *.png; *.gif; *.ico;';
			$param['width'] =  $param['width']? $param['width']: 0;
			$param['height'] =  $param['height']? $param['height']: 0;
			$param['thumb_width'] =  $param['thumb_width']? $param['thumb_width']: 200;
			$param['thumb_height'] =  $param['thumb_height']? $param['thumb_height']: 200;
			
            $thumb_pic = $param['value'] ? explode(',', $param['value']) :'';
			if($thumb_pic){
				foreach ($thumb_pic as $key => $pic) {
					$thumb_picture[$key]['id'] = $pic;
					$thumb_picture[$key]['url_thumb']=getThumbImageById($pic,$param['thumb_width'],$param['thumb_height']);
					if($param['width']&& $param['height']){ 
						$thumb_picture[$key]['url_rel']=getThumbImageById($pic,$param['width'],$param['height']);
					}else{ 
						$thumb_picture[$key]['url_rel']=get_cover($pic,'path');
					}
				}	
				$this->assign('thumb_picture',$thumb_picture);
			}

            $this->assign('infos',$param);
            $this->display('upload_mobile');
        }
    }
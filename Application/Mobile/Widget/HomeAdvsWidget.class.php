<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Mobile\Widget;

use Think\Action;

/**
 * 分类widget
 * 用于动态调用分类信息
 */
class HomeAdvsWidget extends Action
{

    /* 显示指定分类的同级分类或子分类列表 */
    public function lists($advtype='advtype',$limit = 5)
    {
		
		$map['pos'] = $advtype;
		$map['status'] = 1;
		
		$sing =  M('advertising')->where($map)->find();//找到当前调用的广告位
		
		$advMap['position'] = $sing['id'];
		$advMap['status'] = 1;
		$advMap['siteid'] = SITEID;

		if($sing['type'] == 2 || $sing['type'] == 5 ||$sing['type']==6){
			$advs =  M('advs')->where($advMap)->order('level asc,id asc')->select();
			if(!$advs){
				$advMap['siteid'] = 1;
				$advs = M('advs')->where($advMap)->order('level asc,id asc')->select();
			}
			
			foreach($advs as $key=>$val){		
                if (intval($val['create_time']) != 0) {

                    if ($val['create_time'] > time()) {
                        unset($advs[$key]);
                        continue;
                    }
                }
                if(intval($val['end_time'])!=0){
                    if ($val['end_time'] < time()) {
                        unset($advs[$key]);
                        continue;
                    }
                }
				$data['res'][$key] = $val;
				$cover = M('picture')->find($val['advspic']);
				$data['res'][$key]['path'] = $cover['path'];
			}
            $data['style']=$sing['style'];
			$data['type'] = $sing['type'];
			$data['width'] = $sing['width'];
			$data['height'] = $sing['height'];
            $data['ad']=$sing;
		}

	
        $this->assign('lists', $data);
        $this->display('Widget/Homeadvs');
		
    }

}

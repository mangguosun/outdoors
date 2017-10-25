<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Blog\Controller;

use Think\Controller;

/**
 * 前台公共控制器
 * 为防止多分组Controller名称冲突，公共Controller名称统一使用分组名称
 */
class BlogController extends Controller
{

    /* 空操作，用于输出404页面 */
    public function _empty()
    {
        $this->redirect('Index/index');
    }


    protected function _initialize()
    {
        /* 读取站点配置 */
        $config = api('Config/lists');
        C($config); //添加配置

		$model_info = get_appinfo('Blog');
		if(!$model_info){
			$this->error('参数错误或没有找该应用');
		}
		$menutype = array();
		$menutype[$rs['id']]['tab'] = 'home';
		$menutype[$rs['id']]['title'] = '全部';
		$menutype[$rs['id']]['href'] = U($model_info['url']);
		$groupType = D('category')->where(array('status' => 1,'pid'=>0,'siteid'=>SITEID))->order('sort asc')->select();
		if($groupType){
			foreach($groupType as $m=> &$rs){
				$menutype[$rs['id']]['tab'] = 'category_'.$rs['id'];
				$menutype[$rs['id']]['title'] = $rs['title'];
				$menutype[$rs['id']]['href'] =  U('Blog/article/lists', array('category' => $rs['id']));
				$childgroupType =D('GroupType')->where(array('status' => 1,'pid'=>$rs['id'],'siteid'=>SITEID))->select();
				if($childgroupType){
					foreach($childgroupType as $mc=> &$rsc){
						$menutype[$rs['id']]['children'][$rsc['id']]['tab'] = 'category_'.$rsc['id'];
						$menutype[$rs['id']]['children'][$rsc['id']]['title'] = $rsc['title'];
						$menutype[$rs['id']]['children'][$rsc['id']]['href'] = U('Blog/article/lists', array('category' => $rsc['id']));
					}
				}
			}
		}
        $sub_menu =
            array(
                'left' =>$menutype,
            );
        $this->assign('sub_menu', $sub_menu);
		$this->assign('model_info', $model_info);
        $this->assign('current', 'home');

    }


    protected function ensureApiSuccess($result)
    {
        if (!$result['success']) {
            $this->error($result['message'], $result['url']);
        }
    }
}

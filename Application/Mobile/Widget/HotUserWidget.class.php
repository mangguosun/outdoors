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
class HotUserWidget extends Action
{

    /* 显示指定分类的同级分类或子分类列表 */
    public function lists($limit = 5)
    {
  
		$peoples = D('Member')->where('status=1 and is_use=2 and checked=1 and recommendm =1 and siteid='.SITEID)->order('last_login_time desc')->limit($limit)->select();		
        foreach ($peoples as &$v) {
			
            $v['user'] = query_user(array('avatar64', 'space_url', 'username', 'fans', 'following', 'signature', 'nickname'), $v['uid']);
        }
		unset($v);
		$m_level_name=get_upgrading(2);
		$this->assign('m_level_name',$m_level_name);
        $this->assign('lists', $peoples);
        $this->display('Widget/hotuser');
    }

}

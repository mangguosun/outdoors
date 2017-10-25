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
class PartnerWidget extends Action
{

    /* 显示指定分类的同级分类或子分类列表 --- 约伴活动 */
    public function index($limit = 3)
    {
        $map['siteid']=SITEID;
        $map['start_time']=array('GT',time());
        $partner_list = D('Partner')->where($map)->order('start_time asc')->limit($limit)->select();    
        foreach($partner_list as $k=>$v){
            $week=date("w",$v['start_time']);
            switch ($week) {
                case '1':
                $week='周一';
                    break;
                case '2':
                $week='周二'; 
                    break;
                case '3':
                $week='周三'; 
                    break;
                case '4':
                $week='周四';     
                    break;
                case '5':
                $week='周五';     
                    break;
                case '6':
                $week='周六';     
                    break;
                default:
                $week='周日';     
                    break;
                }
            $partner_list[$k]['week'] = $week;
            $map=array('partner_id'=>$v['id'],'status'=>1);
            $partner_list[$k]['user_count'] =D('Partner_user')->where($map)->count();
        }


        $this->assign('partner_list', $partner_list);
        $this->display('Widget/partner');
    }

}

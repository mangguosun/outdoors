<?php

namespace Addons\Recharge;

use Common\Controller\Addon;

/**
 * 签到插件
 * @author 嘉兴想天信息科技有限公司
 */
class RechargeAddon extends Addon
{

    public $info = array(
        'name' => 'Recharge',
        'title' => '充值中心插件',
        'description' => '用于显示充值中心顶部菜单栏入口的插件',
        'status' => 1,
        'author' => '嘉兴想天信息科技有限公司',
        'version' => '0.1'
    );


    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    //实现的checkin钩子方法
    public function personalMenus($param)
    {
        if (modC('OPEN_RECHARGE', 1, 'recharge'))
            echo '<li> <a href="' . U('usercenter/Recharge/recharge') . '"><i class="glyphicon glyphicon-briefcase"></i>  &nbsp;充值中心</a></li>';
    }

    public function ucenterSideMenu($param)
    {
        $url=U('usercenter/Recharge/recharge');
        if (modC('OPEN_RECHARGE', 1, 'recharge'))
        echo <<<str
 <li id="side_recharge">
                    <a href="{$url}">
                        充值中心
                        <span class="glyphicon glyphicon-briefcase pull-right"></span>
                    </a>
                </li>
str;

    }
}









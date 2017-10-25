<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM3:40
 */

namespace Manage\Controller;

use Think\Controller;

class BaseController extends Controller
{	
	protected $userdata;
    public function _initialize()
    {
       $uid = intval($_REQUEST['uid']) ? intval($_REQUEST['uid']) : is_login();
        if (!$uid) {
            //$this->error('需要登录',U('Home/User/login'));
			$this->redirect('Home/User/login');
        }else{
		    $admin=checked_admin(is_login());
			if(!$admin){
			  $this->error('你没有权限');
			}
		}
		$this->userdata = query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $uid);
        $this->assign('uid', $uid);
		$this->assign('user', $this->userdata);
        $this->mid = is_login();
		
		$config = api('Config/lists');
		C($config); //添加配置
		
		$this->assign('__MENU__', $this->getMenus());
    }

    protected function defaultTabHash($tabHash)
    {
        $tabHash = op_t($_REQUEST['tabHash']) ?  op_t($_REQUEST['tabHash']): $tabHash;
        $this->assign('tabHash', $tabHash);
    }

    protected function getCall($uid)
    {
        if ($uid == is_login()) {
            return '我';
        } else {
            $apiProfile = callApi('User/getProfile', array($uid));
            return $apiProfile['sex'] == 'm' ? '他' : '她';
        }
    }

    protected function ensureApiSuccess($result)
    {
        if (!$result['success']) {
            $this->error($result['message'], $result['url']);
        }
    }

    protected function requireLogin()
    {
        if (!is_login()) {
            $this->error('必须登录才能操作');
        }
    }
    /**
     * 获取控制器菜单数组,二级菜单元素位于一级菜单的'_child'元素中
     * @author 朱亚杰  <xcoolcc@gmail.com>
     */
    final public function getMenus($controller=CONTROLLER_NAME){
			// 获取主菜单
		    $where['pid']   =   0;
            $where['hide']  =   0;

            if(!C('DEVELOP_MODE')){ // 是否开发者模式
                $where['is_dev']    =   0;
            }
            $menus  =  D('Menu')->where($where)->order('sort asc')->select();
			$current = D('Menu')->where("url like '{$controller}/".ACTION_NAME."%'")->find();
			
			if($current){
				if($current['hide']==1 || $current['is_dev']==1){ 
					$currentid = $current['pid'];
				}else{ 
					$currentid= $current['id'];
				}
				$breadcrumb_lists = D('Menu')->getPath($current['pid']);
				$r_menus['breadcrumb'] = $breadcrumb_lists;
				$r_menus['current'] = $current;
				
				
			}
			$sub1_active = false;

			foreach ($menus as $key1 => &$item) {
				if (!app_isopen($item['model']) ) {
					unset($menus[$key1]);
					continue;//继续循环
				}
				$sub2_active = false;
				$child_list = D('Menu')->getPath_all($item['id']);
				if(!$child_list){
					$item['is_child'] = 0;
					
					if($currentid == $item['id']){
						$item['is_active']  = true;
						$item['is_open'] = false;
					}
				}else{
					
					foreach ($child_list as $key2 => &$child_item) {
						if (!app_isopen($child_item['model']) ) {
							unset($child_list[$key2]);
							continue;//继续循环
						}
						$sub3_active = false;
						$child_list_sub = D('Menu')->getPath_all($child_item['id']);
						if(!$child_list_sub){
							$child_item['is_child']  = 0 ;
							
							if($currentid == $child_item['id']){
								$child_item['is_active']  = true;
								$child_item['is_open'] = false;
								$sub2_active =true;
							}
						}else{
							foreach ($child_list_sub as $key3=>&$v) {
								if (!app_isopen($v['model']) ) {
									unset($child_list_sub[$key3]);
									continue;//继续循环
								}
								if($currentid == $v['id']){
									$v['is_active'] = true;
									$v['is_open'] = false;
									$sub3_active =true;
								}
							}
							
							if($sub3_active){
								$child_item['is_active']  = false;
								$child_item['is_open'] = true;
								$sub2_active =true;
							}
							$child_item['is_child']  = 1; 
							$child_item['child'] = $child_list_sub;
						}
					}
					if($sub2_active){
						$item['is_active']  = true;
						$item['is_open'] = true;
					}
					$item['is_child'] = 1;
					$item['child'] = $child_list;
				}
				
			}
			
			
			

		//高亮主菜单
		/*if($current){
			
			$nav = D('Menu')->getPath($current['id']);
            $nav_first_title = $nav[0]['title'];
			foreach ($menus['main'] as $key => $item) {
				if (!is_array($item) || empty($item['title']) || empty($item['url']) ) {
					$this->error('控制器基类$menus属性元素配置有误');
				}
				if( stripos($item['url'],MODULE_NAME)!==0 ){
					$item['url'] = MODULE_NAME.'/'.$item['url'];
				}	
			    // 判断主菜单权限
				if (!app_isopen($item['model']) ) {
					unset($menus['main'][$key]);
					continue;//继续循环
				}
				// 获取当前主菜单的子菜单项
				if($item['title'] == $nav_first_title){
					$menus['main'][$key]['class']='current';
					$groups = D('Menu')->where("pid = {$item['id']}")->distinct(true)->field("`group`")->order('sort asc')->select();
					if($groups){
						$groups = array_column($groups, 'group');
					}else{
						$groups =   array();
					}
					foreach ($groups as $k => $v) {
						if($v=='收入管理'){ 
							if(!is_admin()){ 
								unset($groups[$k]);
							}
						}
					}
					
					//获取二级分类的合法url
					// 按照分组生成子菜单树
					foreach ($groups as $gsid => $g) {
						$map['group'] = $g;
						$map['pid'] =   $item['id'];
						$map['hide']    =   0;
						if(!C('DEVELOP_MODE')){ // 是否开发者模式
							$map['is_dev']  =   0;
						}
						$menuList = D('Menu')->where($map)->field('id,pid,title,url,tip,model')->order('sort asc')->select();
							foreach ($menuList as $mid => $md) {
								if (!app_isopen($md['model']) ) {
									unset($menuList[$mid]);
									continue;//继续循环
								}
							}
						$menus['child'][$g] = list_to_tree($menuList, 'id', 'pid', 'operater', $item['id']);
						
						unset($menuList);
					}
					if($menus['child'] === array()){
						//$this->error('主菜单下缺少子菜单，请去系统=》后台菜单管理里添加');
					}
				}
			}
		}*/
            // session('ADMIN_MENU_LIST'.$controller,$menus);
       
	   
	   $r_menus['main'] = $menus;
	   
	   

        return $r_menus;
    }
	
	
}
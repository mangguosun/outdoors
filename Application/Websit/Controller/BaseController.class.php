<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM3:40
 */

namespace Websit\Controller;

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
}
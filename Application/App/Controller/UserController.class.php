<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 1/16/14
 * Time: 9:40 PM
 */

namespace App\Controller;

use Addons\Avatar\AvatarAddon;

//use Addons\LocalComment\LocalCommentAddon;
//use Addons\Favorite\FavoriteAddon;
use Addons\Tianyi\TianyiAddon;
use User\Api\UserApi;

class UserController extends AppController
{
    public function register($username, $password)
    {
        //调用用户中心
        $api = new UserApi();
        $uid = $api->register($username, $username, $password, $username . '@username.com'); // 邮箱为空
        if ($uid <= 0) {
            $message = $this->getRegisterErrorMessage($uid);
            $code = $this->getRegisterErrorCode($uid);
            $this->apiError( $message,$code);
        }
        //返回成功信息
        $extra = array();
        $extra['uid'] = $uid;
        $this->apiSuccess("注册成功", $extra);
    }
    public function login($username, $password)
    {
        //登录单点登录系统
        $result = $this->userApi->login($username, $password, 1); //1表示登录类型，使用用户名登录。
        if ($result <= 0) {
            $message = $this->getLoginErrorMessage($result);
            $code = $this->getLoginErrorCode($result);
            $this->apiError( $message,$code);
        } else {
            $uid = $result;
        }
        //登录前台
        $model = D('Home/Member');
        $result = $model->login($uid);
        if (!$result) {
            $message = $model->getError();
            $this->apiError( $message,604);
        }
        //返回成功信息
        $extra = array();
        $extra['session_id'] = session_id();
        $extra['uid'] = $uid;
        C(api('Config/lists'));
        $extra['weibo_words_limit'] = C('WEIBO_WORDS_COUNT');
        $extra['version'] = C('APP_VERSION');
        $extra['self']=query_user(array('uid', 'nickname','avatar128','avatar256'), is_login());

        $this->apiSuccess("登录成功", $extra);
    }
    protected function getLoginErrorMessage($error_code) {
        switch($error_code) {
            case -1: $error = '用户不存在或被禁用！'; break; //系统级别禁用
            case -2: $error = '密码错误！'; break;
            default: $error = '未知错误！'; break; // 0-接口参数错误（调试阶段使用）
        }
        return $error;
    }

    protected function getLoginErrorCode($error_code) {
        switch($error_code) {
            case -1: return 601;
            case -2: return 602;
            default: return 600;
        }
    }

    public function logout()
    {
        $this->requireLogin();
        //调用用户中心
        $model = D('Home/Member');
        $model->logout();
        session_destroy();
        //返回成功信息
        $this->apiSuccess("退出成功");
    }

    protected function getRegisterErrorMessage($error_code) {
        switch ($error_code) {
            case -1:  $error = '用户名长度必须在16个字符以内！'; break;
            case -2:  $error = '用户名被禁止注册！'; break;
            case -3:  $error = '用户名被占用！'; break;
            case -4:  $error = '密码长度必须在6-30个字符之间！'; break;
            case -5:  $error = '邮箱格式不正确！'; break;
            case -6:  $error = '邮箱长度必须在1-32个字符之间！'; break;
            case -7:  $error = '邮箱被禁止注册！'; break;
            case -8:  $error = '邮箱被占用！'; break;
            case -9:  $error = '手机格式不正确！'; break;
            case -10: $error = '手机被禁止注册！'; break;
            case -11: $error = '手机号被占用！'; break;
            case -12:$error='用户名必须以中文或字母开始，只能包含拼音数字，字母，汉字！';break;
            default:  $error = '未知错误';
        }
        return $error;
    }

    protected function getRegisterErrorCode($error_code) {
        switch ($error_code) {
            case -1:  return 701;
            case -2:  return 702;
            case -3:  return 703;
            case -4:  return 704;
            case -5:  return 705;
            case -6:  return 706;
            case -7:  return 707;
            case -8:  return 708;
            case -9:  return 709;
            case -10: return 710;
            case -11: return 711;
            default:  return 700;
        }
    }
    public function getProfile($uid = null, $fields = 'avatar256,sex,nickname,username,score,tox_money,email,weibo_count,rank_link,expand_info,fans,following')
    {
        //默认查看自己的详细资料
        if (!$uid) {
            $this->requireLogin();
            $uid = $this->getUid();
        }
        $fileds = explode(',', $fields);
        $user = query_user($fileds, $uid);
        foreach ($fileds as $key => $value) {
            if ($value == 'password') {
                unset($fileds[$key]);
            }
        }
        //只返回必要的详细资料
        $this->apiSuccess("获取成功",  $user);
    }


    public function setProfile($signature = null, $email = null, $name = null, $sex = null, $birthday = null)
    {
        $this->requireLogin();
        //获取用户编号
        $uid = $this->getUid();
        //将需要修改的字段填入数组
        $fields = array();
        if ($signature !== null) $fields['signature'] = $signature;
        if ($email !== null) $fields['email'] = $email;
        if ($name !== null) $fields['name'] = $name;
        if ($sex !== null) $fields['sex'] = $sex;
        if ($birthday !== null) $fields['birthday'] = $birthday;

        foreach ($fields as $key => $field) {
            clean_query_user_cache($this->getUid(), $key); //删除缓存
        }
        //将字段分割成两部分，一部分属于ucenter，一部分属于home
        $split = $this->splitUserFields($fields);
        $home = $split['home'];
        $ucenter = $split['ucenter'];
        //分别将数据保存到不同的数据表中
        if ($home) {
            /*if (isset($home['sex'])) {
                $home['sex'] = $this->decodeSex($home['sex']);
            }*/
            $home['uid'] = $uid;
            $model = D('Home/Member');
            $home = $model->create($home);
            $result = $model->where(array('uid' => $uid))->save($home);
            if (!$result) {
                $this->apiError( '设置失败，请检查输入格式!',0);
            }
        }
        if ($ucenter) {
            $model = D('User/UcenterMember');
            $ucenter['id'] = $uid;
            $ucenter = $model->create($ucenter);
            $result = $model->where(array('id' => $uid))->save($ucenter);
            if (!$result) {
                $this->apiError( '设置失败，请检查输入格式!',0);
            }
        }
        //返回成功信息
        $this->apiSuccess("设置成功!");
    }


}
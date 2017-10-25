<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 1/15/14
 * Time: 4:20 PM
 */

namespace App\Controller;
use Api\Exception\ReturnException;
use Think\Controller;
use User\Api\UserApi;
require_once(dirname(__FILE__).'/../Common/function.php');

abstract class AppController extends Controller {
    protected $userApi;
    protected $isInternalCall;

  //  protected $api;

    public function _initialize() {
        //读取站点信息
        $config = api('Config/lists');
        C($config); //添加配置
        //站点关闭，显示关闭消息
        if(!C('WEB_SITE_CLOSE')){
            $this->apiError( '站点已经关闭，请稍后访问~',403);
        }
        //定义API
        $this->userApi = new UserApi();

        $this->$isInternalCall = false;
    }

    public function setInternalCallApi($value=true) {
        $this->isInternalCall = $value ? true : false;
    }

    /**
     * 找不到接口时调用该函数
     */
    public function _empty() {
        $this->apiError( "找不到该接口",404);
    }

    protected function apiReturn($success, $error_code=0, $message=null, $redirect=null, $extra=null) {
        //生成返回信息
        $result = array();
        $result['success'] = $success;
        $result['error_code'] = $error_code;
        if($message !== null) {
            $result['message'] = $message;
        }
        if($redirect !== null) {
            $result['redirect'] = $redirect;
        }
        foreach($extra as $key=>$value) {
            $result[$key] = $value;
        }
        //将返回信息进行编码
        $format = $_REQUEST['format'] ? $_REQUEST['format'] : 'json';//返回值格式，默认json
        if($this->isInternalCall) {
            throw new ReturnException($result);
        } else if($format == 'json') {
            echo json_encode($result);
            exit;
        } else if($format == 'xml') {
            echo xml_encode($result);
            exit;
        } else {
            $_GET['format'] = 'json';
            $_REQUEST['format'] = 'json';
            return $this->apiError("format参数错误", 400);
        }
    }

    protected function apiSuccess($message, $extra=null) {
        return $this->apiReturn(true, 0, $message, null, $extra);
    }

    protected function apiError($message,$error_code=800, $extra=null) {
        return $this->apiReturn(false, $error_code, $message, null, $extra);
    }

    /**
     * 返回当前登录用户的UID
     * @return int
     */
    protected function getUid() {
        return is_login();
    }

    protected function requireLogin() {
        $uid = $this->getUid();
        if(!$uid) {
            $this->apiError("需要登录",401);
        }
    }


}
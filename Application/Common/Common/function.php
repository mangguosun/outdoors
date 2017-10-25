<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

// OneThink常量定义
use Admin\Model\AuthRuleModel;
const ONETHINK_VERSION = '1.0.131218';
const ONETHINK_ADDON_PATH = './Addons/';

/**
 * 系统公共库文件
 * 主要定义系统公共函数库
 */

/**
 * 检测用户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function is_login()
{
    $user = session('user_auth');
    if (empty($user)) {
        return 0;
    } else {
        return session('user_auth_sign') == data_auth_sign($user) ? $user['uid'] : 0;
    }
}
function is_admin($uid = null)
{
    $uid = is_null($uid) ? is_login() : $uid;
  	$check_admin=D('member')->where(array('uid'=>$uid,'siteid'=>SITEID))->getField('check_admin');
  	return $check_admin;
    //调整验证机制，支持多管理员，用,分隔
}


function get_uid()
{
    return is_login();
}

/**
 * 检测权限
 */
function CheckPermission($uids)
{
    if (is_administrator()) {
        return true;
    }
    if (in_array(is_login(), $uids)) {
        return true;
    }
    return false;
}

function check_auth($rule, $type = AuthRuleModel::RULE_URL )
{
    if (is_administrator()) {
        return true;//管理员允许访问任何页面
    }
    static $Auth = null;
    if (!$Auth) {
        $Auth = new \Think\Auth();
    }
    if (!$Auth->check($rule, get_uid(), $type)) {
        return false;
    }
    return true;

}
/*
	$param
	$name 要验证的名称：手机号mobile ，邮件email等
	$string 要验证的内容 如1383838438
	return 匹配成功返回true 失败返回false
*/


function get_every_check($name='',$string=''){ 
	switch ($name) {
		//手机号码
		case 'mobile':
			if(preg_match("/^1[0-9]{10}$/",$string)){
				return true;
			}else{
				return false;
			}
			break;
		//email
		case 'email':      	
			if(preg_match("/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/",$string)){
				return true;
			}else{
				return false;
			}
			break;
		//邮编编
		case 'zipcode':      	
			if(preg_match("/[1-9]\d{5}(?!\d)/",$string)){
				return true;
			}else{
				return false;
			}
			break;
		//电话
		case 'telephone':
		
			if(preg_match("/^(\d{3}-\d{8}|\d{4}-\d{7})$/",$string)){
					return true;
			}else{
					return false;
			}
			break;
		//护照号
		case 'passport':
			if(preg_match("/^(E\d{8}|P.{1}\d{7}|G\d{8}|S[.\d]{1}\d{7}|D\d+|1[4,5]\d{7})$/",$string)){
				return true;
			}else{
				return false;
			}
			break;
		//身份证号
		case 'idnumber':
			if(preg_match("/^\d{15}|\d{18}$/",$string)){
				return true;
			}else{
				return false;
			}
			break;
		//中文
		case 'chinese':
			if(preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$string)){
				return true;
			}else{
				return false;
			}
			break;
		default:
			return "参数错误！";
			break;
	}

}
/*
	$param
		$string 输入的长度
		$type 为1 则return 字符数 （中文为1 ，英文为1）
	 	$type 为2 则return 字节数  (中文为3 ，英文为1)
*/
function get_ch_en_length($string,$type=1){
	if($type==1){ 
		$tmp = @iconv('gbk', 'utf-8', $string);
		if(!empty($tmp)){
		 	$string = $tmp;
		}
		preg_match_all('/./us', $string, $match);
		return count($match[0]);
	}elseif($type==2){ 
		return strlen($string); 
	}
}


/**
 * 检测当前用户是否为管理员
 * @return boolean true-管理员，false-非管理员
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function is_administrator($uid = null)
{
    $uid = is_null($uid) ? is_login() : $uid;
    $admin_uids = explode(',', C('USER_ADMINISTRATOR'));//调整验证机制，支持多管理员，用,分隔
    //dump($admin_uids);exit;
    return $uid && (in_array(intval($uid), $admin_uids));//调整验证机制，支持多管理员，用,分隔
}

/**
 * 字符串转换为数组，主要用于把分隔符调整到第二个参数
 * @param  string $str 要分割的字符串
 * @param  string $glue 分割符
 * @return array
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function str2arr($str, $glue = ',')
{
    return explode($glue, $str);
}

/**
 * 数组转换为字符串，主要用于把分隔符调整到第二个参数
 * @param  array  $arr 要连接的数组
 * @param  string $glue 分割符
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function arr2str($arr, $glue = ',')
{
    return implode($glue, $arr);
}

/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice . '...' : $slice;
}

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key 加密密钥
 * @param int    $expire 过期时间 单位 秒
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_encrypt($data, $key = '', $expire = 0)
{
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = base64_encode($data);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time() : 0);

    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
    }
    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
}

/**
 * 系统解密方法
 * @param  string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param  string $key 加密密钥
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_decrypt($data, $key = '')
{
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data = base64_decode($data);
    $expire = substr($data, 0, 10);
    $data = substr($data, 10);

    if ($expire > 0 && $expire < time()) {
        return '';
    }
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = $str = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}

/**
 * 数据签名认证
 * @param  array $data 被认证的数据
 * @return string       签名
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function data_auth_sign($data)
{
    //数据类型检测
    if (!is_array($data)) {
        $data = (array)$data;
    }
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    $sign = sha1($code); //生成签名
    return $sign;
}

/**
 * 对查询结果集进行排序
 * @access public
 * @param array  $list 查询结果
 * @param string $field 排序的字段名
 * @param array  $sortby 排序类型
 * asc正向排序 desc逆向排序 nat自然排序
 * @return array
 */
function list_sort_by($list, $field, $sortby = 'asc')
{
    if (is_array($list)) {
        $refer = $resultSet = array();
        foreach ($list as $i => $data)
            $refer[$i] = &$data[$field];
        switch ($sortby) {
            case 'asc': // 正向排序
                asort($refer);
                break;
            case 'desc': // 逆向排序
                arsort($refer);
                break;
            case 'nat': // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ($refer as $key => $val)
            $resultSet[] = &$list[$key];
        return $resultSet;
    }
    return false;
}

/**
 * 把返回的数据集转换成Tree
 * @param array  $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
{


    // 创建Tree
    $tree = array();
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }

    return $tree;
}

/**
 * 将list_to_tree的树还原成列表
 * @param  array  $tree 原来的树
 * @param  string $child 孩子节点的键
 * @param  string $order 排序显示的键，一般是主键 升序排列
 * @param  array  $list 过渡用的中间数组，
 * @return array        返回排过序的列表数组
 * @author yangweijie <yangweijiester@gmail.com>
 */
function tree_to_list($tree, $child = '_child', $order = 'id', &$list = array())
{
    if (is_array($tree)) {
        $refer = array();
        foreach ($tree as $key => $value) {
            $reffer = $value;
            if (isset($reffer[$child])) {
                unset($reffer[$child]);
                tree_to_list($value[$child], $child, $order, $list);
            }
            $list[] = $reffer;
        }
        $list = list_sort_by($list, $order, $sortby = 'asc');
    }
    return $list;
}

/**
 * 格式化字节大小
 * @param  number $size 字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function format_bytes($size, $delimiter = '')
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 设置跳转页面URL
 * 使用函数再次封装，方便以后选择不同的存储方式（目前使用cookie存储）
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function set_redirect_url($url)
{
    cookie('redirect_url', $url);
}

/**
 * 获取跳转页面URL
 * @return string 跳转页URL
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_redirect_url()
{
    $url = cookie('redirect_url');
    return empty($url) ? __APP__ : $url;
}

/**
 * 处理插件钩子
 * @param string $hook 钩子名称
 * @param mixed  $params 传入参数
 * @return void
 */
function hook($hook, $params = array())
{
    \Think\Hook::listen($hook, $params);
}

/**
 * 获取插件类的类名
 * @param strng $name 插件名
 */
function get_addon_class($name)
{
    $name=ucfirst($name);
    $class = "Addons\\{$name}\\{$name}Addon";
    return $class;
}

/**
 * 获取插件类的配置文件数组
 * @param string $name 插件名
 */
function get_addon_config($name)
{
    $class = get_addon_class($name);
    if (class_exists($class)) {
        $addon = new $class();
        return $addon->getConfig();
    } else {
        return array();
    }
}

/**
 * 插件显示内容里生成访问插件的url
 * @param string $url url
 * @param array  $param 参数
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function addons_url($url, $param = array())
{
    $url = parse_url($url);
    $case = C('URL_CASE_INSENSITIVE');
    $addons = $case ? parse_name($url['scheme']) : $url['scheme'];
    $controller = $case ? parse_name($url['host']) : $url['host'];
    $action = trim($case ? strtolower($url['path']) : $url['path'], '/');

    /* 解析URL带的参数 */
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }

    /* 基础参数 */
    $params = array(
        '_addons' => $addons,
        '_controller' => $controller,
        '_action' => $action,
    );
    $params = array_merge($params, $param); //添加额外参数
    if(strtolower(MODULE_NAME)=='admin'){
        return U('Admin/Addons/execute', $params);
    }else{
        return U('Home/Addons/execute', $params);

    }

}

/**
 * 时间戳格式化
 * @param int $time
 * @return string 完整的时间显示
 * @author huajie <banhuajie@163.com>
 */
function time_format($time = NULL, $format = 'Y-m-d H:i')
{
    $time = $time === NULL ? NOW_TIME : intval($time);
    return date($format, $time);
}

/**
 * 根据用户ID获取用户名
 * @param  integer $uid 用户ID
 * @return string       用户名
 */
function get_username($uid = 0)
{
    static $list;
    if (!($uid && is_numeric($uid))) { //获取当前登录用户名
        return $_SESSION['onethink_home']['user_auth']['username'];
    }

    /* 获取缓存数据 */
    if (empty($list)) {
        $list = S('sys_active_user_list');
    }

    /* 查找用户信息 */
    $key = "u{$uid}";
    if (isset($list[$key])) { //已缓存，直接使用
        $name = $list[$key];
    } else { //调用接口获取用户信息
        $User = new User\Api\UserApi();
        $info = $User->info($uid);
        if ($info && isset($info[1])) {
            $name = $list[$key] = $info[1];
            /* 缓存用户 */
            $count = count($list);
            $max = C('USER_MAX_CACHE');
            while ($count-- > $max) {
                array_shift($list);
            }
            S('sys_active_user_list', $list);
        } else {
            $name = '';
        }
    }
    return $name;
}

/**
 * 根据用户ID获取用户昵称
 * @param  integer $uid 用户ID
 * @return string       用户昵称
 */
function get_nickname($uid = 0)
{
    static $list;
    if (!($uid && is_numeric($uid))) { //获取当前登录用户名
        return session('user_auth.username');
    }

    /* 获取缓存数据 */
    if (empty($list)) {
        $list = S('sys_user_nickname_list');
    }

    /* 查找用户信息 */
    $key = "u{$uid}";
    if (isset($list[$key])) { //已缓存，直接使用
        $name = $list[$key];
    } else { //调用接口获取用户信息
        $info = M('Member')->field('nickname')->find($uid);
        if ($info !== false && $info['nickname']) {
            $nickname = $info['nickname'];
            $name = $list[$key] = $nickname;
            /* 缓存用户 */
            $count = count($list);
            $max = C('USER_MAX_CACHE');
            while ($count-- > $max) {
                array_shift($list);
            }
            S('sys_user_nickname_list', $list);
        } else {
            $name = '';
        }
    }
    return $name;
}

/**
 * 获取分类信息并缓存分类
 * @param  integer $id 分类ID
 * @param  string  $field 要获取的字段名
 * @return string         分类信息
 */
function get_category($id, $field = null)
{
    static $list;

    /* 非法分类ID */
    if (empty($id) || !is_numeric($id)) {
        return '';
    }

    /* 读取缓存数据 */
    if (empty($list)) {
        $list = S('sys_category_list');
    }

    /* 获取分类名称 */
    if (!isset($list[$id])) {
        $cate = M('Category')->find($id);
        if (!$cate || 1 != $cate['status']) { //不存在分类，或分类被禁用
            return '';
        }
        $list[$id] = $cate;
        S('sys_category_list', $list); //更新缓存
    }
    return is_null($field) ? $list[$id] : $list[$id][$field];
}

/* 根据ID获取分类标识 */
function get_category_name($id)
{
    return get_category($id, 'name');
}

/* 根据ID获取分类名称 */
function get_category_title($id)
{
    return get_category($id, 'title');
}

/**
 * 获取文档模型信息
 * @param  integer $id 模型ID
 * @param  string  $field 模型字段
 * @return array
 */
function get_document_model($id = null, $field = null)
{
    static $list;

    /* 非法分类ID */
    if (!(is_numeric($id) || is_null($id))) {
        return '';
    }

    /* 读取缓存数据 */
    if (empty($list)) {
        $list = S('DOCUMENT_MODEL_LIST');
    }

    /* 获取模型名称 */
    if (empty($list)) {
        $map = array('status' => 1, 'extend' => 1);
        $model = M('Model')->where($map)->field(true)->select();
        foreach ($model as $value) {
            $list[$value['id']] = $value;
        }
        S('DOCUMENT_MODEL_LIST', $list); //更新缓存
    }

    /* 根据条件返回数据 */
    if (is_null($id)) {
        return $list;
    } elseif (is_null($field)) {
        return $list[$id];
    } else {
        return $list[$id][$field];
    }
}

/**
 * 解析UBB数据
 * @param string $data UBB字符串
 * @return string 解析为HTML的数据
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function ubb($data)
{
    //TODO: 待完善，目前返回原始数据
    return $data;
}

/**
 * 记录行为日志，并执行该行为的规则
 * @param string $action 行为标识
 * @param string $model 触发行为的模型名
 * @param int    $record_id 触发行为的记录id
 * @param int    $user_id 执行行为的用户id
 * @return boolean
 * @author huajie <banhuajie@163.com>
 */
function action_log($action = null, $model = null, $record_id = null, $user_id = null)
{

    //参数检查
    if (empty($action) || empty($model) || empty($record_id)) {
        return '参数不能为空';
    }
    if (empty($user_id)) {
        $user_id = is_login();
    }

    //查询行为,判断是否执行
    $action_info = M('Action')->getByName($action);
    if ($action_info['status'] != 1) {
        return '该行为被禁用或删除';
    }

    //插入行为日志
    $data['action_id'] = $action_info['id'];
    $data['user_id'] = $user_id;
    $data['action_ip'] = ip2long(get_client_ip());
    $data['model'] = $model;
    $data['record_id'] = $record_id;
    $data['create_time'] = NOW_TIME;

    //解析日志规则,生成日志备注
    if (!empty($action_info['log'])) {
        if (preg_match_all('/\[(\S+?)\]/', $action_info['log'], $match)) {
            $log['user'] = $user_id;
            $log['record'] = $record_id;
            $log['model'] = $model;
            $log['time'] = NOW_TIME;
            $log['data'] = array('user' => $user_id, 'model' => $model, 'record' => $record_id, 'time' => NOW_TIME);
            foreach ($match[1] as $value) {
                $param = explode('|', $value);
                if (isset($param[1])) {
                    $replace[] = call_user_func($param[1], $log[$param[0]]);
                } else {
                    $replace[] = $log[$param[0]];
                }
            }
            $data['remark'] = str_replace($match[0], $replace, $action_info['log']);
        } else {
            $data['remark'] = $action_info['log'];
        }
    } else {
        //未定义日志规则，记录操作url
        $data['remark'] = '操作url：' . $_SERVER['REQUEST_URI'];
    }

    M('ActionLog')->add($data);

    if (!empty($action_info['rule'])) {
        //解析行为
        $rules = parse_action($action, $user_id);

        //执行行为
        $res = execute_action($rules, $action_info['id'], $user_id);
    }
}

/**
 * 解析行为规则
 * 规则定义  table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
 * 规则字段解释：table->要操作的数据表，不需要加表前缀；
 *              field->要操作的字段；
 *              condition->操作的条件，目前支持字符串，默认变量{$self}为执行行为的用户
 *              rule->对字段进行的具体操作，目前支持四则混合运算，如：1+score*2/2-3
 *              cycle->执行周期，单位（小时），表示$cycle小时内最多执行$max次
 *              max->单个周期内的最大执行次数（$cycle和$max必须同时定义，否则无效）
 * 单个行为后可加 ； 连接其他规则
 * @param string $action 行为id或者name
 * @param int    $self 替换规则里的变量为执行用户的id
 * @return boolean|array: false解析出错 ， 成功返回规则数组
 * @author huajie <banhuajie@163.com>
 */
function parse_action($action = null, $self)
{
    if (empty($action)) {
        return false;
    }

    //参数支持id或者name
    if (is_numeric($action)) {
        $map = array('id' => $action);
    } else {
        $map = array('name' => $action);
    }

    //查询行为信息
    $info = M('Action')->where($map)->find();
    if (!$info || $info['status'] != 1) {
        return false;
    }

    //解析规则:table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
    $rules = $info['rule'];
    $rules = str_replace('{$self}', $self, $rules);
    $rules = explode(';', $rules);
    $return = array();
    foreach ($rules as $key => &$rule) {
        $rule = explode('|', $rule);
        foreach ($rule as $k => $fields) {
            $field = empty($fields) ? array() : explode(':', $fields);
            if (!empty($field)) {
                $return[$key][$field[0]] = $field[1];
            }
        }
        //cycle(检查周期)和max(周期内最大执行次数)必须同时存在，否则去掉这两个条件
        if (!array_key_exists('cycle', $return[$key]) || !array_key_exists('max', $return[$key])) {
            unset($return[$key]['cycle'], $return[$key]['max']);
        }
    }

    return $return;
}

/**
 * 执行行为
 * @param array $rules 解析后的规则数组
 * @param int   $action_id 行为id
 * @param array $user_id 执行的用户id
 * @return boolean false 失败 ， true 成功
 * @author huajie <banhuajie@163.com>
 */
function execute_action($rules = false, $action_id = null, $user_id = null)
{
    if (!$rules || empty($action_id) || empty($user_id)) {
        return false;
    }

    $return = true;
    foreach ($rules as $rule) {

        //检查执行周期
        $map = array('action_id' => $action_id, 'user_id' => $user_id);
        $map['create_time'] = array('gt', NOW_TIME - intval($rule['cycle']) * 3600);
        $exec_count = M('ActionLog')->where($map)->count();
        if ($exec_count > $rule['max']) {
            continue;
        }

        //执行数据库操作
        $Model = M(ucfirst($rule['table']));
        /**
         * 判断是否加入了货币规则
         * @author 郑钟良<zzl@ourstu.com>
         */
        if ($rule['tox_money_rule'] != '' && $rule['tox_money_rule'] != null) {
            $change = array($rule['field'] => array('exp', $rule['rule']), $rule['tox_money_field'] => array('exp', $rule['tox_money_rule']));
            $res = $Model->where($rule['condition'])->setField($change);
        } else {
            $field = $rule['field'];
            $res = $Model->where($rule['condition'])->setField($field, array('exp', $rule['rule']));
        }
        if (!$res) {
            $return = false;
        }
    }
    return $return;
}

//基于数组创建目录和文件
function create_dir_or_files($files)
{
    foreach ($files as $key => $value) {
        if (substr($value, -1) == '/') {
            mkdir($value);
        } else {
            @file_put_contents($value, '');
        }
    }
}

if (!function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null)
    {
        $result = array();
        if (null === $indexKey) {
            if (null === $columnKey) {
                $result = array_values($input);
            } else {
                foreach ($input as $row) {
                    $result[] = $row[$columnKey];
                }
            }
        } else {
            if (null === $columnKey) {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row;
                }
            } else {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row[$columnKey];
                }
            }
        }
        return $result;
    }
}

/**
 * 获取表名（不含表前缀）
 * @param string $model_id
 * @return string 表名
 * @author huajie <banhuajie@163.com>
 */
function get_table_name($model_id = null)
{
    if (empty($model_id)) {
        return false;
    }
    $Model = M('Model');
    $name = '';
    $info = $Model->getById($model_id);
    if ($info['extend'] != 0) {
        $name = $Model->getFieldById($info['extend'], 'name') . '_';
    }
    $name .= $info['name'];
    return $name;
}

/**
 * 获取属性信息并缓存
 * @param  integer $id 属性ID
 * @param  string  $field 要获取的字段名
 * @return string         属性信息
 */
function get_model_attribute($model_id, $group = true)
{
    static $list;

    /* 非法ID */
    if (empty($model_id) || !is_numeric($model_id)) {
        return '';
    }

    /* 读取缓存数据 */
    if (empty($list)) {
        $list = S('attribute_list');
    }

    /* 获取属性 */
    if (!isset($list[$model_id])) {
        $map = array('model_id' => $model_id);
        $extend = M('Model')->getFieldById($model_id, 'extend');

        if ($extend) {
            $map = array('model_id' => array("in", array($model_id, $extend)));
        }
        $info = M('Attribute')->where($map)->select();
        $list[$model_id] = $info;
        //S('attribute_list', $list); //更新缓存
    }

    $attr = array();
    foreach ($list[$model_id] as $value) {
        $attr[$value['id']] = $value;
    }

    if ($group) {
        $sort = M('Model')->getFieldById($model_id, 'field_sort');

        if (empty($sort)) { //未排序
            $group = array(1 => array_merge($attr));
        } else {
            $group = json_decode($sort, true);

            $keys = array_keys($group);
            foreach ($group as &$value) {
                foreach ($value as $key => $val) {
                    $value[$key] = $attr[$val];
                    unset($attr[$val]);
                }
            }

            if (!empty($attr)) {
                $group[$keys[0]] = array_merge($group[$keys[0]], $attr);
            }
        }
        $attr = $group;
    }
    return $attr;
}

/**
 * 调用系统的API接口方法（静态方法）
 * api('User/getName','id=5'); 调用公共模块的User接口的getName方法
 * api('Admin/User/getName','id=5');  调用Admin模块的User接口
 * @param  string       $name 格式 [模块名]/接口名/方法名
 * @param  array|string $vars 参数
 */
function api($name, $vars = array())
{
    $array = explode('/', $name);
    $method = array_pop($array);
    $classname = array_pop($array);
    $module = $array ? array_pop($array) : 'Common';
    $callback = $module . '\\Api\\' . $classname . 'Api::' . $method;
    if (is_string($vars)) {
        parse_str($vars, $vars);
    }
    return call_user_func_array($callback, $vars);
}

/**
 * 根据条件字段获取指定表的数据
 * @param mixed  $value 条件，可用常量或者数组
 * @param string $condition 条件字段
 * @param string $field 需要返回的字段，不传则返回整个数据
 * @param string $table 需要查询的表
 * @author huajie <banhuajie@163.com>
 */
function get_table_field($value = null, $condition = 'id', $field = null, $table = null)
{
    if (empty($value) || empty($table)) {
        return false;
    }

    //拼接参数
    $map[$condition] = $value;
    $info = M(ucfirst($table))->where($map);
    if (empty($field)) {
        $info = $info->field(true)->find();
    } else {
        $info = $info->getField($field);
    }
    return $info;
}

/**
 * 获取链接信息
 * @param int    $link_id
 * @param string $field
 * @return 完整的链接信息或者某一字段
 * @author huajie <banhuajie@163.com>
 */
function get_link($link_id = null, $field = 'url')
{
    $link = '';
    if (empty($link_id)) {
        return $link;
    }
    $link = M('Url')->getById($link_id);
    if (empty($field)) {
        return $link;
    } else {
        return $link[$field];
    }
}

/**
 * 获取文档封面图片
 * @param int    $cover_id
 * @param string $field
 * @return 完整的数据  或者  指定的$field字段值
 * @author huajie <banhuajie@163.com>
 */
function get_cover($cover_id, $field = null)
{
    if (empty($cover_id)) {
        return false;
    }
    $picture = M('Picture')->where(array('status' => 1))->getById($cover_id);

	if(!$picture) return false;

    if (is_bool(strpos($picture['path'], 'http://'))) {
        $picture['path'] = fixAttachUrl($picture['path']);
    }

    return empty($field) ? $picture : $picture[$field];
}

/**
 * 检查$pos(推荐位的值)是否包含指定推荐位$contain
 * @param number $pos 推荐位的值
 * @param number $contain 指定推荐位
 * @return boolean true 包含 ， false 不包含
 * @author huajie <banhuajie@163.com>
 */
function check_document_position($pos = 0, $contain = 0)
{
    if (empty($pos) || empty($contain)) {
        return false;
    }

    //将两个参数进行按位与运算，不为0则表示$contain属于$pos
    $res = $pos & $contain;
    if ($res !== 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获取数据的所有子孙数据的id值
 * @author 朱亚杰 <xcoolcc@gmail.com>
 */

function get_stemma($pids, Model &$model, $field = 'id')
{
    $collection = array();

    //非空判断
    if (empty($pids)) {
        return $collection;
    }

    if (is_array($pids)) {
        $pids = trim(implode(',', $pids), ',');
    }
    $result = $model->field($field)->where(array('pid' => array('IN', (string)$pids)))->select();
    $child_ids = array_column((array)$result, 'id');

    while (!empty($child_ids)) {
        $collection = array_merge($collection, $result);
        $result = $model->field($field)->where(array('pid' => array('IN', $child_ids)))->select();
        $child_ids = array_column((array)$result, 'id');
    }
    return $collection;
}

/**
 * 获取导航URL
 * @param  string $url 导航URL
 * @return string      解析或的url
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_nav_url($url)
{
    switch ($url) {
        case 'http://' === substr($url, 0, 7):
		case 'https://' === substr($url, 0, 8):
        case '#' === substr($url, 0, 1):
            break;
        default:
            $url = U($url);
            break;
    }
    return $url;
}

/**
 * @param $url 检测当前url是否被选中
 * @return bool|string
 * @auth 陈一枭
 */
function get_nav_active($url)
{
    switch ($url) {
        case 'http://' === substr($url, 0, 7):
            $nowurl=preg_replace("(http://(.+?)/)","",$url);
			$url_array = explode('/', $nowurl);
			if ($url_array[0] == '') {
				$MODULE_NAME = $url_array[1];
			} else {
				$MODULE_NAME = $url_array[0]; //发现模块就是当前模块即选中。
	
			}
			if (strtolower($MODULE_NAME) === strtolower(MODULE_NAME)) {
				if(I('get.type_id') != ''){
					if(I('get.type_id') == $url_array[4]||I('get.type_id') == str_replace(".html","",$url_array[4])){
						return 1;
					}else{
						return 0;
					}
				}else{
					return 1;
				}
			}
			break;
        case '#' === substr($url, 0, 1):
            return 0;
            break;
        default:
            $url_array = explode('/', $url);
            if ($url_array[0] == '') {
                $MODULE_NAME = $url_array[1];
            } else {
                $MODULE_NAME = $url_array[0]; //发现模块就是当前模块即选中。

            }
            if (strtolower($MODULE_NAME) === strtolower(MODULE_NAME)) {
				if(I('get.type_id') != ''){
					if(I('get.type_id') == $url_array[5]){
						return 1;
					}else{
						return 0;
					}
				}else{
					return 1;
				}
            };
            break;

    }
    return 0;
}

function get_nav_active_official($url)
{	
    switch ($url) {
        case 'http://' === substr($url, 0, 7):
            $nowurl=preg_replace("(http://(.+?)/)","",$url);
			$url_array = explode('/', $nowurl);
			if ($url_array[0] == '') {
				$MODULE_NAME = $url_array[1];
			} else {
				$MODULE_NAME = $url_array[0]; //发现模块就是当前模块即选中。
	
			}
			if (strtolower($MODULE_NAME) === strtolower(MODULE_NAME)) {
				if(I('get.type_id') != ''){
					if(I('get.type_id') == $url_array[4]||I('get.type_id') == str_replace(".html","",$url_array[4])){
						return 1;
					}else{
						return 0;
					}
				}else{
					return 1;
				}
			}
			break;
        case '#' === substr($url, 0, 1):
            return 0;
            break;
        default:
		
			//$now_url = strtolower(ACTION_NAME);
		//ACTION_NAME  CONTROLLER_NAME   MODULE_NAME
		
            $url_array = explode('/', $url);
            if ($url_array[1] == '') {
                $MODULE_NAME = $url_array[2];
            } else {
                $MODULE_NAME = $url_array[1]; //发现模块就是当前模块即选中。
            }
            if (strtolower($MODULE_NAME) === strtolower('index') && strtolower(CONTROLLER_NAME) ===strtolower('index') ) {
				
				if(ACTION_NAME == $url_array[2]){
					return 1;
				}else{
					return 0;
				}
				
            };
            break;

    }
    return 0;
}

function get_nav_active_mobile($url)
{	
	$now_url = strtolower(CONTROLLER_NAME);
	$url = strtolower($url);
    switch ($url) {
        case 'home':
			if ($now_url == 'index') {
				return 1;
            }
			break;
        case 'event':
			if ($now_url == 'event') {
				return 1;
            }
            break;
		case 'shop':
			if ($now_url == 'shop') {
				return 1;
            }
            break;	
			
        case 'issue':
			if (($now_url == 'issue') || ($now_url == 'people')  || ($now_url == 'summary')) {
				return 1;
            }
            break;
        case 'user':
			if ($now_url == 'config') {
				return 1;
            }
            break;
        default:
            if ($url == $now_url) {
				return 1;
            }
            break;
    }
    return 0;
}
/**
 * 获取列表总行数
 * @param  string  $category 分类ID
 * @param  integer $status 数据状态
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_list_count($category, $status = 1)
{
    static $count;
    if (!isset($count[$category])) {
        $count[$category] = D('Document')->listCount($category, $status);
    }
    return $count[$category];
}

/**
 * t函数用于过滤标签，输出没有html的干净的文本
 * @param string text 文本内容
 * @return string 处理后内容
 */
function op_t($text)
{
    $text = nl2br($text);
    $text = real_strip_tags($text);
    $text = addslashes($text);
    $text = trim($text);
    return $text;
}

/**
 * h函数用于过滤不安全的html标签，输出安全的html
 * @param string $text 待过滤的字符串
 * @param string $type 保留的标签格式
 * @return string 处理后内容
 */
function op_h($text, $type = 'html')
{
    // 无标签格式
    $text_tags = '';
    //只保留链接
    $link_tags = '<a>';
    //只保留图片
    $image_tags = '<img>';
    //只存在字体样式
    $font_tags = '<i><b><u><s><em><strong><font><big><small><sup><sub><bdo><h1><h2><h3><h4><h5><h6>';
    //标题摘要基本格式
    $base_tags = $font_tags . '<p><br><hr><a><img><map><area><pre><code><q><blockquote><acronym><cite><ins><del><center><strike>';
    //兼容Form格式
    $form_tags = $base_tags . '<form><input><textarea><button><select><optgroup><option><label><fieldset><legend>';
    //内容等允许HTML的格式
    $html_tags = $base_tags . '<ul><ol><li><dl><dd><dt><table><caption><td><th><tr><thead><tbody><tfoot><col><colgroup><div><span><object><embed><param>';
    //专题等全HTML格式
    $all_tags = $form_tags . $html_tags . '<!DOCTYPE><meta><html><head><title><body><base><basefont><script><noscript><applet><object><param><style><frame><frameset><noframes><iframe>';
    //过滤标签
    $text = real_strip_tags($text, ${$type . '_tags'});
    // 过滤攻击代码
    if ($type != 'all') {
        // 过滤危险的属性，如：过滤on事件lang js
        while (preg_match('/(<[^><]+)(ondblclick|onclick|onload|onerror|unload|onmouseover|onmouseup|onmouseout|onmousedown|onkeydown|onkeypress|onkeyup|onblur|onchange|onfocus|action|background|codebase|dynsrc|lowsrc)([^><]*)/i', $text, $mat)) {
            $text = str_ireplace($mat[0], $mat[1] . $mat[3], $text);
        }
        while (preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
            $text = str_ireplace($mat[0], $mat[1] . $mat[3], $text);
        }
    }
    return $text;
}

function real_strip_tags($str, $allowable_tags = "")
{
    $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    return strip_tags($str, $allowable_tags);
}

/**span
 * 获取楼层信息
 * @param $k
 */
function getLou($k)
{
    $lou = array(
        2 => '沙发',
        3 => '板凳',
        4 => '地板'
    );
    !empty($lou[$k]) && $res = $lou[$k];
    empty($lou[$k]) && $res = $k . '楼';
    return $res;
}

function getMyScore()
{
    $user = query_user(array('score'), is_login());
    $score = $user['score'];
    return $score;
}

function getScoreTip($before, $after)
{
    $score_change = $after - $before;
    $tip = '';
    if ($score_change) {
        $tip = '积分' . ($score_change > 0 ? '加&nbsp;' . $score_change : '减&nbsp;' . $score_change) . ' 。';
    }
    return $tip;
}

/**获取我的货币数
 * @return mixed
 * @author 郑钟良<zzl@ourstu.com>
 */
function getMyToxMoney()
{
    $user = query_user(array('tox_money'), is_login());
    $tox_money = $user['tox_money'];
    return $tox_money;
}

/**获取货币名称
 * @return string
 * @author 郑钟良<zzl@ourstu.com>
 */
function getToxMoneyName()
{
    $tox_money_name = "金币";
    $tox_money_name = D('shop_config')->where('ename=' . "'tox_money'")->getField('cname');
    return $tox_money_name;
}

/**获取货币提示消息
 * @param $before
 * @param $after
 * @return string
 * @author 郑钟良<zzl@ourstu.com>
 */
function getToxMoneyTip($before, $after)
{
    $tox_money_change = $after - $before;
    $tip = '';
    if ($tox_money_change) {
        $tip = getToxMoneyName() . ($tox_money_change > 0 ? '加&nbsp;' . $tox_money_change : '减&nbsp;' . $tox_money_change) . ' 。';
    }
    return $tip;
}

function action_log_and_get_score($action = null, $model = null, $record_id = null, $user_id = null)
{
    $score_before = getMyScore();
    action_log($action, $model, $record_id, $user_id);
    $score_after = getMyScore();
    return $score_after - $score_before;
}

function is_ie()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $pos = strpos($userAgent, ' MSIE ');
    if ($pos === false) {
        return false;
    } else {
        return true;
    }
}

function array_subtract($a, $b)
{
    return array_diff($a, array_intersect($a, $b));
}

require_once(APP_PATH . '/Common/Common/pagination.php');
require_once(APP_PATH . '/Common/Common/query_user.php');
require_once(APP_PATH . '/Common/Common/thumb.php');
require_once(APP_PATH . '/Common/Common/api.php');
require_once(APP_PATH . '/Common/Common/time.php');
require_once(APP_PATH . '/Common/Common/match.php');
require_once(APP_PATH . '/Common/Common/seo.php');
require_once(APP_PATH . '/Common/Common/type.php');
require_once(APP_PATH . '/Common/Common/cache.php');
require_once(APP_PATH . '/Common/Common/vendors.php');
require_once(APP_PATH . '/Common/Common/parse.php');
require_once(APP_PATH . '/Common/Common/website_info.php');

/*require_once(APP_PATH . '/Common/Common/extend.php');*/
function tox_addons_url($url, $param)
{
    // 拆分URL
    $url = explode('/', $url);
    $addon = $url[0];
    $controller = $url[1];
    $action = $url[2];

    // 调用u函数
    $param['_addons'] = $addon;
    $param['_controller'] = $controller;
    $param['_action'] = $action;
    return U("Home/Addons/execute", $param);
}


/**
 * 取一个二维数组中的每个数组的固定的键知道的值来形成一个新的一维数组
 * @param $pArray 一个二维数组
 * @param $pKey 数组的键的名称
 * @return 返回新的一维数组
 */
function getSubByKey($pArray, $pKey = "", $pCondition = "")
{
    $result = array();
    if (is_array($pArray)) {
        foreach ($pArray as $temp_array) {
            if (is_object($temp_array)) {
                $temp_array = (array)$temp_array;
            }
            if (("" != $pCondition && $temp_array[$pCondition[0]] == $pCondition[1]) || "" == $pCondition) {
                $result[] = ("" == $pKey) ? $temp_array : isset($temp_array[$pKey]) ? $temp_array[$pKey] : "";
            }
        }
        return $result;
    } else {
        return false;
    }
}

function get_event_calendar($id,$find='')
{
	$map = "eventid = $id and siteid = ".SITEID." and status >=1 and display = 1";
	$calendar_info = D('event_calendar_time')->where($map)->select();
	if($calendar_info){
		foreach ($calendar_info as $key=> &$v) {			
			if($v['status'] == 1){
				if(strtotime($v['endtime'])-time() > 0){
					$v['com_time'] = strtotime($v['starttime']);
				}else{
					$v['com_time'] = abs(-strtotime($v['starttime']) + time()) + time() * 3;
				}			
			}elseif($v['status'] == 2 || $v['status'] == 3){
				if(strtotime($v['endtime'])-time() > 0){
					$v['com_time'] = strtotime($v['starttime']);
				}else{	
					$v['com_time'] = strtotime($v['starttime']) + time();
				}		
			}elseif($v['status'] == 4 || $v['status'] == 5 || $v['status'] == 6){
				if(strtotime($v['endtime'])-time() > 0){
					$v['com_time'] = abs(-strtotime($v['starttime']) + time()) + time() * 2;
				}else{
					$v['com_time'] = abs(-strtotime($v['starttime']) + time()) + time() * 3;
				}
			}
		}		
		usort($calendar_info, function($a, $b){							
			$al = $a['com_time'];
			$bl = $b['com_time'];
			if ($al == $bl)
				return 0;
			return ($al > $bl) ? 1 : -1;
		});	
		if($find){
			return $calendar_info[$find];
		}else{			
			return $calendar_info;					
		}		
	}else{
		return '';	
	}	
}

/*---------用户网站管理员------*/
function checked_admin($uid){

   $group_user=D('Member')->where(array('uid'=>$uid,'siteid'=>SITEID,'is_use'=>3))->find();
						
	if($group_user){
		return true;
	}else{
		return false;
	}
}
/*-------------vip----领队会员-------*/
function checked_vip($uid){
	$group_user=D('Member')->where(array('uid'=>$uid,'siteid'=>SITEID,'is_use'=>2))->find();			 
	if($group_user || checked_admin($uid)){
		return true;
	}else{
		return false;
	}

 }



/*
$param['province']
$param['city']
$param['district']
$param['community']
*/

function set_city($param){
	if($param){
		if($param['community']){
			return $param['community'];
		}else{
			if($param['district']){
				return $param['district'];
			}else{
				if($param['city']){
					return $param['city'];
				}else{
					if($param['province']){
						return $param['province'];
					}else{
						return 0;
					}
				}
			}
		}
	}else{
		return 0;
	}	
}
//遍历城市子级数据
function get_citys_subclass ($sort_id)
{
	$result = D('district')->where(array('upid' =>$sort_id))->select();
	if ($result)
	{
	   foreach ($result as $key=>$val)
	   {
		   $ids .= ','.$val['id'];
		   $ids .= get_citys_subclass ($val['id']);
	   }
	}
	return $ids;
}



function get_citys($cityid){
	if($cityid){
		  $citys=D('district')->where("id=$cityid") ->find();
		  if($citys){
			switch ($citys['level'])
			{
				case 1:
					$data['community'] = 0;
					$data['district'] = 0;
					$data['city'] = 0;
					$data['province'] = $citys['id'];
				  break;  
				case 2:
					$data['community'] = 0;
					$data['district'] = 0;
					$data['city'] = $citys['id'];
					$district_data= D('district')->where("id={$citys['upid']}") ->find();
					$data['province'] = $district_data['id'];
				  break;
				case 3:
					$data['community'] = 0;
					$data['district'] = $citys['id'];
					$district_data= D('district')->where("id={$citys['upid']}") ->find();
					$data['city'] = $district_data['id'];
					$district_city= D('district')->where("id={$district_data['upid']}") ->find();
					$data['province'] = $district_city['id'];
				  break;
				case 4:
					$data['community'] = $citys['id'];
					$district_data= D('district')->where("id={$citys['upid']}") ->find();
					$data['district'] = $district_data['id'];
					$district_city= D('district')->where("id={$district_data['upid']}") ->find();
					$data['city'] = $district_city['id'];
					$district_province= D('district')->where("id={$district_city['upid']}") ->find();
					$data['province'] = $district_province['id'];
				  break;
				default:
				  break;
			}
			  return $data; 
		  }else{
			return 0;  
		  }		
	}else{
		return 0;
	}	
}


function get_city($cityid){
	
	if(!$cityid) return '';
	//$data = get_citys($cityid);
	//$incityids =  $data['province'].','. $data['city'].','. $data['district'].','. $data['community'];
	//$citys_arr=D('district')->where("id in($incityids)") ->select();
	$data_arr=D('district')->where(array('id'=>$cityid)) ->find();
	return $data_arr['name'];
}

/**
 * 单选框
 * 
 * @param $array 选项 二维数组
 * @param $id 默认选中值
 * @param $str 属性
 */


 function form_switche_manage($id = '', $str = '') {
	$string = '';
	$id = trim($id);
	if($id != '') $id = $id;
	$checked = $id  ? 'checked' : '';
	$string .= '<div>';
	$string .= '<input class="" type="checkbox" '.$str.' '.$checked.' value="1">';
	$string .= '</div>';

	return $string;
}

/**
 * 下拉选择框
 */
function form_select_manage($array = array(), $id = 0, $str = '', $default_option = '') {
	$string = '<select '.$str.'>';
	$default_selected = (empty($id) && $default_option) ? 'selected' : '';
	if($default_option) $string .= "<option value='' $default_selected>$default_option</option>";
	if(!is_array($array) || count($array)== 0){
		
		if(!$default_option) return false;
		
		
	}else{
		$ids = array();
		if(isset($id)) $ids = explode(',', $id);
		foreach($array as $key=>$value) {
			$selected = in_array($key, $ids) ? 'selected' : '';
			$string .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
		}
	} 
	$string .= '</select>';
	return $string;
}

/**
 * 单选框
 * 
 * @param $array 选项 二维数组
 * @param $id 默认选中值
 * @param $str 属性
 */
 function form_radio_manage($array = array(), $id = 0, $str = '', $width = 0, $field = '') {
	$string = '';
	foreach($array as $key=>$value) {
		$checked = trim($id)==trim($key) ? 'checked' : '';
		 $string .= '<label class="" style="width:'.$width.'px">';
		$string .= '<input type="radio" '.$str.' id="'.$field.'_'.$key.'" '.$checked.' value="'.$key.'"><span class="text">'.$value;
		 $string .= '</span></label>';
	}
	return $string;
}

 function form_checkbox_manage($array = array(), $id = '', $str = '', $defaultvalue = '', $width = 0, $field = '') {
	$string = '';
	$id = trim($id);
	if($id != '') $id = strpos($id, ',') ? explode(',', $id) : array($id);
	if($defaultvalue) $string .= '<input type="hidden" '.$str.' value="-99">';
	$i = 1;
	foreach($array as $key=>$value) {
		$key = trim($key);
		$checked = ($id && in_array($key, $id)) ? 'checked' : '';
		$string .= '<label class="" style="width:'.$width.'px">';
		$string .= '<input type="checkbox" '.$str.' id="'.$field.'_'.$i.'" '.$checked.' value="'.$key.'"><span class="text">'.$value;
		$string .= '</span></label>';
		$i++;
	}
	return $string;
}

/**
 * 根据box类型字段获取显示名称
 * @param $field 字段名称
 * @param $value 字段值
 * @param $modelid 字段所在模型id
 */
function get_box($value ,$boxtype , $box_data='') {

	foreach($box_data as $key=>$_k) {
		$option[$key] = $_k;
	}
	$string = '';
	switch($boxtype) {
			case 'radio':
				$string = $option[$value];
			break;

			case 'checkbox':
				$value_arr = explode(',',$value);
				foreach($value_arr as $_v) {
					if($_v) $string .= $option[$_v].' 、';
				}
			break;

			case 'select':
				$string = $option[$value];
			break;

			case 'multiple':
				$value_arr = explode(',',$value);
				foreach($value_arr as $_v) {
					if($_v) $string .= $option[$_v].' 、';
				}
			break;
		}
            $string = rtrim($string,' 、');
			return $string;
}

/**
 * 下拉选择框
 */
function form_select($array = array(), $id = 0, $str = '', $default_option = '') {
	$string = '<select '.$str.'>';
	$default_selected = (empty($id) && $default_option) ? 'selected' : '';
	if($default_option) $string .= "<option value='' $default_selected>$default_option</option>";
	if(!is_array($array) || count($array)== 0) return false;
	$ids = array();
	if(isset($id)) $ids = explode(',', $id);
	foreach($array as $key=>$value) {
		$selected = in_array($key, $ids) ? 'selected' : '';
		$string .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
	}
	$string .= '</select>';
	return $string;
}
/**
 * 下拉选择（菜单）
 */
function form_select_menu($array = array(), $id = 0, $str = '', $default_option = '') {
	$string = '<select '.$str.'>';
	$default_selected = (empty($id) && $default_option) ? 'selected' : '';
	if($default_option) $string .= "<option value='' $default_selected>$default_option</option>";
	if(!is_array($array) || count($array)== 0) return false;
	$ids = array();
	if(isset($id)) $ids = explode(',', $id);
	foreach($array as $key=>$value) {
		$selected = in_array($value['id'], $ids) ? 'selected' : '';
		$string .= '<option value="'.$value['id'].'" '.$selected.'>'.$value['title_show'].'</option>';
	}
	$string .= '</select>';
	return $string;
}


/**
 * 复选框
 * 
 * @param $array 选项 二维数组
 * @param $id 默认选中值，多个用 '逗号'分割
 * @param $str 属性
 * @param $defaultvalue 是否增加默认值 默认值为 -99
 * @param $width 宽度
 */
 function form_checkbox($array = array(), $id = '', $str = '', $defaultvalue = '', $width = 0, $field = '') {
	$string = '';
	$id = trim($id);
	if($id != '') $id = strpos($id, ',') ? explode(',', $id) : array($id);
	if($defaultvalue) $string .= '<input type="hidden" '.$str.' value="-99">';
	$i = 1;
	foreach($array as $key=>$value) {
		$key = trim($key);
		$checked = ($id && in_array($key, $id)) ? 'checked' : '';
		if($width) $string .= '<label class="ib" style="width:'.$width.'px">';
		$string .= '<input type="checkbox" '.$str.' id="'.$field.'_'.$i.'" '.$checked.' value="'.$key.'"> '.$value;
		if($width) $string .= '</label>';
		$i++;
	}
	return $string;
}

/**
 * 单选框
 * 
 * @param $array 选项 二维数组
 * @param $id 默认选中值
 * @param $str 属性
 */
 function form_radio($array = array(), $id = 0, $str = '', $width = 0, $field = '') {
	$string = '';
	foreach($array as $key=>$value) {
		$checked = trim($id)==trim($key) ? 'checked' : '';
		if($width) $string .= '<label class="ib" style="width:'.$width.'px">';
		$string .= '<input type="radio" '.$str.' id="'.$field.'_'.$key.'" '.$checked.' value="'.$key.'"> '.$value;
		if($width) $string .= '</label>';
	}
	return $string;
}

/*特色标签*/
function get_event_tag($find='',$select='all')
{	
	$tag_arr = explode(',',$find);

	$tid = count($tag_arr);
    switch($select){
	    case 'all':
		   $tags_arr = D('tags')->where(array('status' => 1))->select();
		break;
		case 'selected':
			if(getWebsitConfig('tags',2)){
				$tags = getWebsitConfig('tags',1);
				$tags_arr = D('tags')->where(array('status' => 1,'id'=>array('in',$tags)))->select();
			}else{
				$tags_arr = D('tags')->where(array('status' => 1))->select();
			}
		break;
	}
	
   if($tags_arr){
		if($tid == 1){
			foreach($tags_arr as $key=>$value) {
				$srting_arr[$value['id']] = $value['title'];
			}
			if($find){
				return $srting_arr[$find];
			}else{
				return $srting_arr;
			}
		}elseif($tid >1){
			$title_arr = array();
			foreach($tags_arr as $key=>$value) {
				if(in_array($value['id'],$tag_arr)){
					$title_arr[] = $value['title'];
				}
			}
			$str = implode(',',$title_arr);
			return $str;
		}
	}else{
		return '';
	}
}

/*站点活动自定义标签*/
function get_custom_eventtag(){
	$map['siteid'] = SITEID;
	$map['app_model'] = 'Event';
	$data = D('websit_install_apply')->field('config')->where($map)->find();
	$list = string2array($data['config']);
	return $list;
}


/*站点配置*/
function get_custom_tag($id){
	$map['siteid'] = SITEID;
	$map['app_id'] = $id;
	$data = D('websit_install_apply')->field('config')->where($map)->find();
	$list = string2array($data['config']);
	return $list;
}

function get_vehicle($find='')
{
    $srting_arr=array(
		1=> '大巴',
		2=> '中巴',
		3=> '私家车',
		4=> '高铁',
		5=> '火车',
		6=> '飞机',
		7=>'商务车',
		8=>'越野车',
		100=> '无'
	);
	
	if($find){
		return $srting_arr[$find];
	}else{
		return $srting_arr;
	}
}
//获取城市信息
function get_zhishu($find=''){
    $srting_arr=array(
		1=> '一级',
		2=> '二级',
		3=> '三级',
		4=> '四级',
		5=> '五级'
	);
	if($find){
		return $srting_arr[$find];
	}else{
		return $srting_arr;
	}
}
function get_accommodation($find='')
{
    $srting_arr=array(
		1=> '帐篷',
		2=> '农家',
		3=> '客栈',
		4=> '酒店',
		100=> '无',
	);
	
	if($find){
		return $srting_arr[$find];
	}else{
		return $srting_arr;
	}
	
}

function get_bloodtype($find='')
{
    $srting_arr=array(
		0=>'不知道',
		1=> 'A型',
		2=> 'B型',
		3=> 'O型',
		4=> 'AB型',
		5=> 'RH阴型'
	);
	
	if($find){
		return $srting_arr[$find];
	}else{
		return $srting_arr;
	}
	
}

function get_timetoweek($find='')
{
    $srting_arr=array(
		//13=> '周末',
		//14=> '30天内',
		1=> '1月',
		2=> '2月',
		3=> '3月',
		4=> '4月',
		5=> '5月',
		6=> '6月',
		7=> '7月',
		8=> '8月',
		9=> '9月',
		10=> '10月',
		11=> '11月',
		12=> '12月'		
	);
	
	if($find){
		return $srting_arr[$find];
	}else{
		return $srting_arr;
	}
}
function get_recent($find='')
{ 
     $srting_arr=array(
        1=> '周末',
        2=> '3天以内',
        3=> '7天以内',
        4=> '14天以内',
        5=> '1个月以内',
        6=> '3个月以内'
    );
    
    if($find){
        return $srting_arr[$find];
    }else{
        return $srting_arr;
    }

}

function get_holiday($find='')
{
    $srting_arr=array(
		1=> '元旦',
		2=> '春节',
		3=> '清明',
		4=> '五一',
		5=> '端午',
		6=> '暑假',
		7=> '中秋',
		8=> '十一',
		9=> '圣诞节',
		10=> '寒假'
	);
	
	if($find){
		return $srting_arr[$find];
	}else{
		return $srting_arr;
	}
}

function get_participant($find='',$choice= false){ 
    $srting_arr = array( 
            1=>'紧急联系人',
            2=>'紧急联系电话',
            3=>'活动昵称',
            4=>'邮箱',
            5=>'QQ',
            6=>'血型',
            7=> '住宿偏好',
            8=> '过敏史',
            9=> '社会角色',
            10=>'角色说明',
            11=> '年龄',
            12=> '惯用手',
           
        );
    $srting_arr_name = array( 
            1=>'emergencycontact',
            2=>'emergencyphone',
            3=>'nickname',
            4=>'email',
            5=>'qq',
            6=>'bloodtype',
            7=> 'accpre',
            8=> 'allergies',
            9=> 'role',
            10=>'role_description',
            11=> 'age',
            12=> 'hand',

        );
    if($choice){ 
        return $srting_arr_name;
    }
    if($find){
        return $srting_arr[$find];
    }else{
        return $srting_arr;
    }
}

function get_days($find='')
{
    $srting_arr=array(
		1=> '1~2天',
		2=> '3~7天',
		3=> '7天以上',
	);
	
	if($find){
		return $srting_arr[$find];
	}else{
		return $srting_arr;
	}
}


function get_constellation($find='')
    {
       $str_arr=array(
	      1=>'白羊座',
		  2=>'金牛座',
		  3=>'双子座',
		  4=>'巨蟹座',
		  5=>'狮子座',
		  6=>'处女座',
		  7=> '天秤座',
		  8=> '天蝎座',
		  9=> '射手座',
		  10=>'摩羯座',
		  11=>'水瓶座',
		  12=>'双鱼座'
		 );
		if($find){
		  return $str_arr[$find];
		}else{
		  return $str_arr;
		}

}
/*社交帐号--2014-10-31--dlx*/
function get_share($find=''){
     $str_arr=array(
	       1=>'人人',
		   2=>'北面',
		   3=>'豆瓣',
		   4=>'新浪微博'
	 
	       );
		if($find){
		   return $str_arr[$find];
		}else{
		   return $str_arr;
		}


}
function get_role($find='')
    {
       $str_arr=array(
	      1=>'学生',
		  2=>'白领',
		  3=>'自由职业',
		 );
		if($find){
		  return $str_arr[$find];
		}else{
		  return $str_arr;
		}

}
function get_price($find='')
{
    $srting_arr=array(
		1=> '1000元以下',
		2=> '1000~2000',
		3=> '2000~3000',
		4=> '3000~4000',
		5=> '4000~5000',
		6=> '5000元以上'
	);
	
	if($find){
		return $srting_arr[$find];
	}else{
		return $srting_arr;
	}
}
function get_enenttype($find='')
{
	$tree = D('EventType')->where(array('status' => 1,'siteid' => SITEID))->select();	
	foreach($tree as $key=>$value) {
		$srting_arr[$value['id']] = $value['title'];
	}
	if($find){
		return $srting_arr[$find];
	}else{
		return $srting_arr;
	}
}

function get_issuetype($find=''){
	$tree = D('Issue')->where(array('status' => 1,'siteid' => SITEID))->select();	
	foreach($tree as $key=>$value) {
		$srting_arr[$value['id']] = $value['title'];
	}
		if($find){
			return $srting_arr[$find];
		}else{
			return $srting_arr;
		}
	
	}
	
function structure_filters_url($fieldname,$array=array(),$type = 1) {
	if(empty($array)) {
		$params = $_GET;
	} else {
		$params = array_merge($_GET,$array);
		if($params['page']){
			$params['page']=1;
		}	
	}
	$urlrule = U(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME, $params);
	return 	$urlrule;
}



function filters($field = '',$diyarr = array(),$is_diy = 0,$isall = 1) {

	$options = $diyarr;
	$field_value = intval($_GET[$field]);
	foreach($options as $key => $_k) {
		
		if($is_diy){
			$v = explode("|",$_k);
			$k = trim($v[1]);
			$option[$k]['name'] = $v[0];
			$option[$k]['value'] = $k;
			$option[$k]['url'] = structure_filters_url($field,array($field=>$k),2);
			$option[$k]['menu'] = $field_value == $k ? '<span class="btn btn-primary">'.$v[0].'</span>' : '<a class="btn" href='.$option[$k]['url'].'>'.$v[0].'</a>';	
		}else{
			$option[$key]['url'] = structure_filters_url($field,array($field=>$key),2);
			$option[$key]['menu'] = $field_value == $key ? '<span class="btn btn-primary">'.$_k.'</span>' : '<a class="btn" href='.$option[$key]['url'].'>'.$_k.'</a>';	
		}
	}
	if ($isall) {
		$all['name'] = '全部';
		$all['url'] = structure_filters_url($field,array($field=>''),2);
		$all['menu'] = $field_value == '' ? '<span class="btn btn-primary">'.$all['name'].'</span>' : '<a class="btn" href='.$all['url'].'>'.$all['name'].'</a>';
		array_unshift($option,$all);
	}
	return $option;


}

function show_linkage($fieldid = '', $lid = 0) {
	$r = D('district')->where(array('upid' =>$lid))->select();
	$data = array();
			$data[0]['id'] = 0;
			$data[0]['title'] = '全部';
			$data[0]['url'] = structure_filters_url($fieldid, array($fieldid=>0), 2);
	if (is_array($r) && !empty($r)) {
		foreach ($r as $d) {
			$data[$d['id']]['id'] = $d['id'];
			$data[$d['id']]['title'] = $d['name'];
			$data[$d['id']]['url'] = structure_filters_url($fieldid, array($fieldid=>$d['id']), 2);
		}
	}
	return $data;
}

function get_event_status($sid){
	$status = array(
		array('id'=>2,'name'=>'接受报名'),
		array('id'=>3,'name'=>'报满预约'),
		array('id'=>4,'name'=>'报名截止'),
		array('id'=>5,'name'=>'进行中'),
		array('id'=>6,'name'=>'已结束')
		);
		$status_arr = array();
		foreach($status as $k => $v){
			$status_arr[$v['id']] = $v['name'];
		}
		if($sid){
			return $status_arr[$sid];
		}else{
			return $status;
		}
}

function get_province($find = '',$select='all') {
	switch($select){
		case 'selected'://-已选择-
		    if(getWebsitConfig('province',2)){
				$province = getWebsitConfig('province',1);
				$r = D('district')->where(array('upid' =>'0','id'=>array('in',$province)))->select();
			}else{
			    $r = D('district')->where(array('upid' =>'0'))->select();	
			}
		break;
		case 'all': //所有
		   $r = D('district')->where(array('upid' =>'0'))->select();
		break;
    }
	
	foreach ($r as $d) {
		$srting_arr[$d['id']] = $d['name'];
	}
	if($find){
		return $srting_arr[$find];
	}else{
		return $srting_arr;
	}
}
 /**
     * 对活动进行计划任务更新
     * autor:ououmu
     */
	 function update_event($siteid){		
		$event_arr = D('event')->where(array('siteid'=>$siteid))->select();
		foreach($event_arr as $val){
			if($val['status'] == 1){
				$map = "eventid = ".$val['id']." and siteid = $siteid and status != 0 and status != -1 and display = 1";
				$val['rs'] = D('event_calendar_time')->where($map)->select();	
				if($val['rs']){			
					$val['time_arr'] = array();
					foreach($val['rs'] as $v){					
							if($v['status'] == 1 && strtotime("$v[endtime]")-time() > 0){
								$val['time_arr'][] = $v['starttime'];
							}elseif($v['status'] == 2 || $v['status'] == 3){
								$val['time_arr'][] = $v['starttime'];
							}
					}
					$val['timearr1'] = array();
					$val['timearr2'] = array();
					foreach($val['time_arr'] as $v){
						if(strtotime($v) > time()){
							$val['timearr1'][] = strtotime($v);
						}else{
							$val['timearr2'][] = strtotime($v);
						}
					}
					if(!empty($val['timearr1'])){
						$val['pos1'] = array_search(min($val['timearr1']),$val['timearr1']);
						$val['time1'] = $val['timearr1'][$val['pos1']];
						$val['dtime'] = $val['time1'];
						$val['data']['lasted_time'] = $val['dtime'];
						$val['data']['diff_time'] = abs($val['dtime'] - time());
						$val['finaltime'] = date("Y-m-d",$val['dtime']);
						$map .= " and starttime = '".$val['finaltime']."'";
						$re = D('event_calendar_time')->where($map)->find();
					}else{
						$val['pos2'] = array_search(max($val['timearr2']),$val['timearr2']);
						$val['time2'] = $val['timearr2'][$val['pos2']];
						$val['dtime'] = $val['time2'];
						$val['data']['lasted_time'] = $val['dtime'];
						$val['data']['diff_time'] = (abs($val['dtime'] - time()))+time();
					}							
				}else{
						$val['data']['diff_time'] = (abs($val['update_time'] - time()))+time()*2;
						$val['data']['lasted_time'] = 0;
					
				}
			}elseif($val['status'] == 0){
				$val['data']['diff_time'] = (abs($val['update_time'] - time()))+time()*3;
				$val['data']['lasted_time'] = 0;
			}elseif($val['status'] == -1){
				$val['data']['diff_time'] = (abs($val['update_time'] - time()))+time()*4;
				$val['data']['lasted_time'] = 0;
			}
			D('event')->where(array('id'=>$val['id'],'siteid'=>$siteid))->save($val['data']);
		}		
	}
	 /**
     * 得到我的订单中的报名人数
	 * @param $order_id 订单ID。如果有传入值，则查询单条订单的报名人数
     * autor:ououmu
     */
	function get_signnum($order_id){		
		$where = "siteid = ".SITEID." and order_id = $order_id";
		$num = D('event_signer')->where($where)->count();
		return $num;
	}
	/**
     * 得到一定状态下的订单报名人数
	 * $id  排期id 
	 * $status 传值 查询该状态下的报名人数，如果不传值，则查询该排期所有的报名人数
     */
	function get_status_num($id,$status){
		if($status){
			$map = "calendar_id = $id and siteid=".SITEID." and status = 1 and order_status in ($status)";
			$num = D('event_signer')->where($map)->count();		
			return $num;
		}else{				
			$map = "calendar_id = $id and siteid=".SITEID." and status = 1 and order_status >= 10";
			$num = D('event_signer')->where($map)->count();		
			return $num;
		}
	}
	/**
     * 得到一定条件下的订单付款或未付款人数 
	 * $judge   0-未付款 1-定金已支付 2-全额已支付
	 * $id  排期id 
     */
	function get_pay_num($id,$judge){
		if($judge == 1){
			$map = "calendar_id = $id and (order_status = 11 or order_status = 12) and status = 1 and siteid = ".SITEID;
			$num = D('event_signer')->where($map)->count();			
			return $num;
		}elseif($judge == 0){
			$map = "calendar_id = $id and (order_status = 10 or order_status = 20) and status = 1 and siteid = ".SITEID;
			$num = D('event_signer')->where($map)->count();		
			return $num;
		}elseif($judge == 2){
			$map = "calendar_id = $id and order_status >= 21 and status = 1 and siteid = ".SITEID;
			$num = D('event_signer')->where($map)->count();
			return $num;
		}else{
			$str = '参数错误';
			return $str;
		}
	}
	
	/**
     * 得到符合条件的排期的所有信息
	 * @param $id - 活动的id
     * autor:ououmu
     */
	function get_timeinfo($id){
		$event_content = D('Event')->where(array('status' => 1, 'id' => $id,'siteid'=>SITEID))->find();
		$vid = $event_content['id'];
		$lasted_time = date('Y-m-d',$event_content['lasted_time']);
		$where = "eventid = $vid and siteid = ".SITEID." and status != -1 and status <= 3 and starttime = '$lasted_time' ";
		return $timeinfo = D('event_calendar_time')->where($where)->find();
	}

function get_webinfo($find = ''){
	
	$website_info = json_decode(WEBSITEINFO,true);
	
	if($find){
		return $website_info[$find];
	}else{
		return $website_info;
	}
	
}

function get_websit_nav(){
	
	$nav = M('channel_websit')->where("status=1 and display = 1 and siteid = ".SITEID)->order("sort")->select();
	if($nav){
		foreach($nav as &$val){
			
			$model_system = D('websit_apply')->where(array('app_model' => $val['model']))->find();
			
			$val['url'] = $model_system['url'];
		}
	}
	return $nav;
}


function get_websit_nav_custom(){
	
	$nav = M('channel_websit_custom')->where("status=1 and siteid = ".SITEID)->order("sort asc")->select();
	return $nav;
}

function get_websit_event_nav(){
	$nav = M('event_type')->where("status=1 and display = 1 and siteid = ".SITEID)->order("sort")->select();
	if($nav){
		foreach($nav as &$val){
			$val['url'] = '/Event/index/index/type_id/'.$val['id'];
		}
	}
	return $nav;
}
function get_pay_type($find=''){
       $str_arr=array(
	      1=>'银行汇款',
		  2=>'支付宝',
		 );
		if($find){
		  return $str_arr[$find];
		}else{
		  return $str_arr;
		}

}

function get_event_pay_type($find=''){
       $str_arr=array(
	      0=>'全额支付',
		  1=>'定金支付',
		  2=>'免费活动'
		 );
		if($find){
		  return $str_arr[$find];
		}else{
		  return $str_arr;
		}

}
function get_order_type($find=''){
       $str_arr=array(
	      1=>'活动线路',
		  2=>'商城物品',
		 );
		if($find){
		  return $str_arr[$find];
		}else{
		  return $str_arr;
		}

}

function get_order_sn($find=''){
	$order_sn = date('ymdHis').substr(microtime(),2,4);
	return $order_sn;
}


/**
 * 生成流水号
 */
function create_sn(){
	mt_srand((double )microtime() * 1000000 );
	return date("YmdHis" ).str_pad( mt_rand( 1, 99999 ), 5, "0", STR_PAD_LEFT );
}

function get_pay_status($status=''){
	
	
	$arr['all_status'] = '全部状态';
	$arr['unpay'] = '<font color="red" class="onError">交易未支付</font>';
	$arr['succ'] = '<font color="green" class="onCorrect">交易成功</font>';
	$arr['failed'] = '交易失败';
	$arr['error'] = '交易错误';
	$arr['progress'] = '<font color="orange" class="onTime">交易处理中</font>';
	$arr['timeout'] = '交易超时';
	$arr['cancel'] = '交易取消';
	$arr['waitting'] = '<font color="orange" class="onTime">等待付款</font>';
	$arr['pay_btn'] = '付款';
	return $arr[$status];
	
}

function get_pay_lang($find='',$find2=''){
	$arr['all_status'] = '全部状态';
	$arr['unpay'] = '<font color="red" class="onError">交易未支付</font>';
	$arr['succ'] = '<font color="green" class="onCorrect">交易成功</font>';
	$arr['failed'] = '交易失败';
	$arr['error'] = '交易错误';
	$arr['progress'] = '<font color="orange" class="onTime">交易处理中</font>';
	$arr['timeout'] = '交易超时';
	$arr['cancel'] = '交易取消';
	$arr['waitting'] = '<font color="orange" class="onTime">等待付款</font>';
	$arr['pay_btn'] = '付款';
	
	$arr['select']['unpay'] = '交易未支付';
	$arr['select']['succ'] = '交易成功';
	$arr['select']['progress'] = '交易处理中';
	$arr['select']['cancel'] = '交易取消';
	$arr['dsa'] = 'DSA 签名方法待后续开发，请先使用MD5签名方式';
	$arr['alipay_error'] = '支付宝暂不支持{sign_type}类型的签名方式';
	$arr['execute_date'] = '执行日期';
	$arr['illegal_sign'] = '签名错误';
	$arr['illegal_notice'] = '通知错误';
	$arr['illegal_return'] = '信息返回错误';
	$arr['illegal_pay_method'] = '支付方式错误';
	$arr['illegal_creat_sn'] = '订单号生成错误';
	if($find && $find2){
		return $arr[$find][$find2];
	}else{
		return $arr[$find];
	}
}

function check_event_order_ispay($status){
	
	switch ($status)
	{
	case -1:
	  $result ='该订单已经被删除';
	  break;  
	case 0:
	   $result ='该订单已经被取消';
	  break;
	case 1:
	   $result ='该订单为预约订单,暂不能支付，请等待'.get_upgrading(2).'排座';
	  break;	
	case 21:
	   $result ='已经支付成功，待确认出行';
	  break;	  
	case 30:
	   $result ='订单已经支付，无需再次支付';
	  break; 
	case 31:
	   $result ='活动进行中，不能支付';
	  break; 
	case 32:
	   $result ='活动已结束，不能支付';
	  break; 
	case 33:
	   $result ='订单已完成，不能支付';
	  break; 
	  
	case 60:
	   $result ='退款申请中，不能支付';
	  break; 
	case 61:
	   $result ='订单已经退款，不能支付';
	  break;
	
	}
	return $result;
}


               
function get_event_list_btn($data){
	
	if(!$data) return '';

	$status = $data['status'];
	$pay_status = $data['pay_status'];
	$id = $data['id'];
	$trade_sn = $data['trade_sn'];

	
	if($status == 10){
		$out_data = array('trade_sn'=>$trade_sn,'text'=>'定金支付','is_show'=>true);
	}elseif($status == 12 ){
		$out_data = array('trade_sn'=>$trade_sn,'text'=>'余款支付','is_show'=>true);
	}elseif($status == 20 ){
		$out_data = array('trade_sn'=>$trade_sn,'text'=>'全款支付','is_show'=>true);
	}
	$string_status='<a class="mbtn btn-info updataSign" data-eventID="'.$out_data['id'].'" data-status="'.$out_data['status'].'" href="javascript:">'.$out_data['text'].'</a>';
	$string_pay='<a class="mbtn btn-info" href="'.U('Usercenter/Pay/pay',array('trade_sn'=>$out_data['trade_sn'])).'">'.$out_data['text'].'</a>';
	
	if($out_data['is_show']){
		return $string_pay;
	}
}
/*
 * @param $choose 不带参数=>详情页 带参数=>我的订单页
 */
function get_event_detail_btn($data,$choose = 0){
	
	if(!$data) return '';
	$paytype = $data['paytype'];
	$status = $data['status'];
	$pay_status = $data['pay_status'];
	$id = $data['id'];
	$trade_sn = $data['trade_sn'];
	
	
	//btn_class: btn mbtn btn-primary btn-info btn-default
	if(!$choose){
		if($status == 1){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'btn btn-default updataSign','is_show'=>true);
			//$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>-1,'text'=>'删除订单','btn_type'=>'status_btn','btn_class'=>'btn btn-default updataSign','is_show'=>true);
		}elseif($status == 10){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>11,'text'=>'已下单，支付定金','btn_type'=>'pay_btn','btn_class'=>'btn btn-primary','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'btn btn-default updataSign','is_show'=>true);
		}elseif($status == 11){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'定金已支付，待确认出行','btn_type'=>'show_btn','btn_class'=>'btn btn-primary','is_show'=>true);
			//$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'btn btn-default updataSign','is_show'=>true);
		}elseif($status == 12){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>30,'text'=>'已确认出行，支付余款','btn_type'=>'pay_btn','btn_class'=>'btn btn-primary','is_show'=>true);
		}elseif($status == 20 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>21,'text'=>'已下单，全款支付','btn_type'=>'pay_btn','btn_class'=>'btn btn-primary','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'btn btn-default updataSign','is_show'=>true);
		}elseif($status == 21 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'支付成功，待确认出行','btn_type'=>'show_btn','btn_class'=>'btn btn-primary','is_show'=>true);
		}
		elseif($status == 30 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'待出行签到 ','btn_type'=>'show_btn','btn_class'=>'btn btn-primary','is_show'=>true);
			if($paytype == 2){
				$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
			}
		}
		/*elseif($status == 31 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'活动进行中','btn_type'=>'show_btn','btn_class'=>'btn btn-primary','is_show'=>true);
		}
		elseif($status == 32 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>33,'text'=>'活动结束，进行评论','btn_type'=>'status_btn','btn_class'=>'btn btn-primary updatacommne','is_show'=>true);
		}elseif($status == 33 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单完成','btn_type'=>'show_btn','btn_class'=>'btn btn-primary updatacommne','is_show'=>true);
		}*/
		elseif($status == -1){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已删除','btn_type'=>'show_btn','btn_class'=>'btn btn-primary updatacommne','is_show'=>true);
		}elseif($status == 0){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已取消','btn_type'=>'show_btn','btn_class'=>'btn btn-primary updatacommne','is_show'=>true);
		}

		if($out_data){
			$out_string = '';

			foreach($out_data as &$val){
				
				if(!$val['is_show']) continue;
				
				if($val['btn_type'] == 'pay_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Usercenter/Pay/pay',array('trade_sn'=>$val['trade_sn'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'status_btn'){
					$string='<a class="'.$val['btn_class'].'" data-eventID="'.$val['id'].'" data-status="'.$val['status'].'" href="javascript:">'.$val['text'].'</a> ';
				}elseif($val['status'] == 33){
					$string='<a class="'.$val['btn_class']. ' comment'.'" href="'.U('Usercenter/Config/discuss',array('event_id'=>$data['status'],'trade_sn'=>$data['trade_sn'])).'">'.$val['text'].'</a> ';		
				}elseif($val['btn_type'] == 'show_btn'){
					$string = '<span style="color:red">'.$val['text'].'</span>';
				}
				$out_string .= $string;
			}
			return $out_string;
		}	
	}else{
		if($status == 1){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
			//$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>-1,'text'=>'删除订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
		}elseif($status == 10){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>11,'text'=>'已下单，支付定金','btn_type'=>'pay_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
			//$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
		}elseif($status == 11){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'定金已支付，待确认出行','btn_type'=>'show_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}elseif($status == 12){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>30,'text'=>'已确认出行,支付余款','btn_type'=>'pay_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}elseif($status == 20 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>21,'text'=>'已下单，全款支付','btn_type'=>'pay_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
			//$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
		}elseif($status == 21 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'支付成功，待确认出行','btn_type'=>'show_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}
		elseif($status == 30 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>"待出行签到 ",'btn_type'=>'show_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
			if($paytype == 2){
				$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
			}
		}
		/*elseif($status == 31 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'活动进行中','btn_type'=>'show_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}elseif($status == 32 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>33,'text'=>'活动结束，进行评论','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updatacommne','is_show'=>true);
		}elseif($status == 33 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单完成','btn_type'=>'show_btn','btn_class'=>'mbtn btn-primary updatacommne','is_show'=>true);
		}*/
		elseif($status == -1){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已删除','btn_type'=>'show_btn','btn_class'=>'btn btn-primary updatacommne','is_show'=>true);
		}elseif($status == 0){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已取消','btn_type'=>'show_btn','btn_class'=>'btn btn-primary updatacommne','is_show'=>true);
		}

		if($out_data){
			$out_string = '';

			foreach($out_data as &$val){
				
				if(!$val['is_show']) continue;
				
				if($val['btn_type'] == 'pay_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Usercenter/Pay/pay',array('trade_sn'=>$val['trade_sn'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'status_btn'){
					$string='<a class="'.$val['btn_class'].'" data-eventID="'.$val['id'].'" data-status="'.$val['status'].'" href="javascript:">'.$val['text'].'</a> ';
				}elseif($val['status'] == 33){
					$string='<a class="'.$val['btn_class']. ' comment'.'" href="'.U('Usercenter/Config/discuss',array('event_id'=>$data['status'],'trade_sn'=>$data['trade_sn'])).'">'.$val['text'].'</a> ';	
				}elseif($val['btn_type'] == 'show_btn'){
					$string = '<span style="color:red">'.$val['text'].'</span>';
				}
				$out_string .= $string;
			}
			return $out_string;
		}	
	}
	
	
}
/*
 * @param $choose 带参数=>我的订单页
 */
function mobile_get_event_detail_btn($data,$choose = 0){
	
	if(!$data) return '';
	$paytype = $data['paytype'];
	$status = $data['status'];
	$pay_status = $data['pay_status'];
	$id = $data['id'];
	$trade_sn = $data['trade_sn'];
	
	
	//btn_class: btn mbtn btn-primary btn-info btn-default am-btn am-btn-block am-btn-primary
	if(!$choose){
		if($status == 1){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'am-btn am-btn-sm am-btn-default updataSign','is_show'=>true);
			//$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>-1,'text'=>'删除订单','btn_type'=>'status_btn','btn_class'=>'am-btn btn-default updataSign','is_show'=>true);
		}elseif($status == 10){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>11,'text'=>'已下单，支付定金','btn_type'=>'pay_btn','btn_class'=>'am-btn am-btn-sm am-btn-primary','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'am-btn am-btn-sm am-btn-default updataSign','is_show'=>true);
		}elseif($status == 11){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'定金已支付，待确认出行','btn_type'=>'show_btn','btn_class'=>'am-btn am-btn-sm am-btn-danger','is_show'=>true);
		}elseif($status == 12){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>30,'text'=>'已确认出行,支付余款','btn_type'=>'pay_btn','btn_class'=>'am-btn am-btn-sm am-btn-primary','is_show'=>true);
		}elseif($status == 20 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>21,'text'=>'已下单，全款支付','btn_type'=>'pay_btn','btn_class'=>'am-btn am-btn-sm am-btn-primary','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'am-btn am-btn-sm am-btn-default updataSign','is_show'=>true);
		}elseif($status == 21 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'支付成功，待确认出行','btn_type'=>'show_btn','btn_class'=>'am-btn am-btn-sm am-btn-danger','is_show'=>true);
		}
		elseif($status == 30 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'待出行签到 ','btn_type'=>'show_btn','btn_class'=>'am-btn am-btn-sm am-btn-danger','is_show'=>true);
			if($paytype == 2){
				$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'am-btn am-btn-sm am-btn-default updataSign','is_show'=>true);
			}
		}
		/*elseif($status == 31 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'活动进行中','btn_type'=>'show_btn','btn_class'=>'am-btn am-btn-sm am-btn-primary','is_show'=>true);
		}elseif($status == 32 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>33,'text'=>'活动结束，进行评论','btn_type'=>'status_btn','btn_class'=>'am-btn am-btn-sm am-btn-primary updatacommne','is_show'=>true);
		}elseif($status == 33 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单完成','btn_type'=>'show_btn','btn_class'=>'am-btn am-btn-sm am-btn-primary updatacommne','is_show'=>true);
		}*/
		elseif($status == -1){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已删除','btn_type'=>'show_btn','btn_class'=>'am-btn am-btn-sm am-btn-danger updatacommne','is_show'=>true);
		}elseif($status == 0){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已取消','btn_type'=>'show_btn','btn_class'=>'am-btn am-btn-sm am-btn-danger updatacommne','is_show'=>true);
		}

		if($out_data){
			$out_string = '';

			foreach($out_data as &$val){
				
				if(!$val['is_show']) continue;
				
				if($val['btn_type'] == 'pay_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Mobile/Pay/pay',array('trade_sn'=>$val['trade_sn'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'status_btn'){
					$string='<a class="'.$val['btn_class'].'" data-eventID="'.$val['id'].'" data-status="'.$val['status'].'" href="javascript:">'.$val['text'].'</a> ';
				}elseif($val['status'] == 33){
					 $string='<a class="'.$val['btn_class']. ' comment'.'" href="'.U('Usercenter/Config/discuss',array('event_id'=>$data['status'],'trade_sn'=>$data['trade_sn'])).'">'.$val['text'].'</a> ';	
				}elseif($val['btn_type'] == 'show_btn'){
					$string = '<a class="'.$val['btn_class'].'" href="javascript:">'.$val['text'].'</a> ';
				}
				$out_string .= $string;
			}
			return $out_string;
		}	
	}else{
		if($status == 1){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
			//$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>-1,'text'=>'删除订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
		}elseif($status == 10){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>11,'text'=>'已下单，支付定金','btn_type'=>'pay_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
		}elseif($status == 11){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'定金已支付，待确认出行','btn_type'=>'show_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}elseif($status == 12){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>30,'text'=>'已确认出行,支付余款','btn_type'=>'pay_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}elseif($status == 20 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>21,'text'=>'已下单，全款支付','btn_type'=>'pay_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
		}elseif($status == 21 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'支付成功,待确认出行','btn_type'=>'show_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}
		elseif($status == 30 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'待出行签到 ','btn_type'=>'show_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
			if($paytype == 2){//免费
				$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
			}
		}
		/*elseif($status == 31 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'活动进行中','btn_type'=>'show_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}elseif($status == 32 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>33,'text'=>'活动结束，进行评论','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updatacommne','is_show'=>true);
		}elseif($status == 33 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单完成','btn_type'=>'show_btn','btn_class'=>'mbtn btn-primary updatacommne','is_show'=>true);
		}*/
		elseif($status == -1){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已删除','btn_type'=>'show_btn','btn_class'=>'btn btn-primary updatacommne','is_show'=>true);
		}elseif($status == 0){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已取消','btn_type'=>'show_btn','btn_class'=>'btn btn-primary updatacommne','is_show'=>true);
		}

		if($out_data){
			$out_string = '';

			foreach($out_data as &$val){
				
				if(!$val['is_show']) continue;
				
				if($val['btn_type'] == 'pay_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Mobile/Pay/pay',array('trade_sn'=>$val['trade_sn'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'status_btn'){
					$string='<a class="'.$val['btn_class'].'" data-eventID="'.$val['id'].'" data-status="'.$val['status'].'" href="javascript:">'.$val['text'].'</a> ';
				}elseif($val['status'] == 33){
					 $string='<a class="'.$val['btn_class']. ' comment'.'" href="'.U('Usercenter/Config/discuss',array('event_id'=>$data['status'],'trade_sn'=>$data['trade_sn'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'show_btn'){
					$string = '<span style="color:red">'.$val['text'].'</span>';
				}
				$out_string .= $string;
			}
			return $out_string;
		}	
	}
	
	
}
/**
* @param $choose  随便给值 就有取消订单选项 给网站管理员的权限
*/
function admin_get_event_detail_btn($data,$choose =0){
	if(!$data) return '';
	$paytype = $data['paytype'];
	$status = $data['status'];
	$pay_status = $data['pay_status'];
	$id = $data['id'];
	$trade_sn = $data['trade_sn'];
	$paytype = $data['paytype'];
	
	if($choose){
		if($status == 1){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
			if($paytype == 0){
				$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>20,'text'=>'通过','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			}elseif($paytype == 1){
				$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>10,'text'=>'通过','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			}elseif($paytype == 2){
				$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>30,'text'=>'通过','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			}		
		}elseif($status == 10){
			
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
		}elseif($status == 11){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>12,'text'=>'定金已支付，确认出行','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);	
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>61,'text'=>'取消订单','btn_type'=>'refund_btn','btn_class'=>'mbtn btn-default updataRefund','is_show'=>true);
				
		}elseif($status == 12){
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>61,'text'=>'取消订单','btn_type'=>'refund_btn','btn_class'=>'mbtn btn-default updataRefund','is_show'=>true);
		}elseif($status == 20 ){
			
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
		}elseif($status == 21 ){
			
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>30,'text'=>'全款已支付，确认出行','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>61,'text'=>'取消订单','btn_type'=>'refund_btn','btn_class'=>'mbtn btn-default updataRefund','is_show'=>true);
		}
		elseif($status == 30 && $paytype != 2){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>31,'text'=>'进行签到','btn_type'=>'','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>61,'text'=>'取消订单','btn_type'=>'refund_btn','btn_class'=>'mbtn btn-default updataRefund','is_show'=>true);

		}elseif($status == 30 && $paytype == 2){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>31,'text'=>'进行签到','btn_type'=>'','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
		}

		elseif($status == -1){
		
		}elseif($status == 0){
		
		}

		if($out_data){
			$out_string = '';
			foreach($out_data as &$val){
				
				if(!$val['is_show']) continue;
				
				if($val['btn_type'] == 'pay_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Usercenter/Pay/pay',array('trade_sn'=>$val['trade_sn'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'status_btn'){
					$string='<a class="'.$val['btn_class'].'" data-eventID="'.$val['id'].'" data-status="'.$val['status'].'" href="javascript:void(0)">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'show_btn'){
					$string = '<span style="color:red">'.$val['text'].'</span>';
				}elseif($val['btn_type'] == 'refund_btn') {
					$string='<a class="'.$val['btn_class'].'" data-eventID="'.$val['id'].'" data-status="'.$val['status'].'" href="javascript:void(0)">'.$val['text'].'</a> ';
				}
				$out_string .= $string;
			}
			
			return $out_string;
		}	
	}else{
		if($status == 1){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
			if($paytype == 0){
				$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>20,'text'=>'通过','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			}else{
				$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>10,'text'=>'通过','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			}		
		}elseif($status == 10){
		
		}elseif($status == 11){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>12,'text'=>'定金已支付，确认出行','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>61,'text'=>'取消订单','btn_type'=>'refund_btn','btn_class'=>'mbtn btn-default updataRefund','is_show'=>true);		
		}elseif($status == 12){
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>61,'text'=>'取消订单','btn_type'=>'refund_btn','btn_class'=>'mbtn btn-default updataRefund','is_show'=>true);
		}elseif($status == 20 ){
		
		}elseif($status == 21 ){
			
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>30,'text'=>'全款已支付，确认出行','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>61,'text'=>'取消订单','btn_type'=>'refund_btn','btn_class'=>'mbtn btn-default updataRefund','is_show'=>true);
		}
		elseif($status == 30 && $paytype != 2 ){

			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>61,'text'=>'取消订单','btn_type'=>'refund_btn','btn_class'=>'mbtn btn-default updataRefund','is_show'=>true);
		
		}elseif($status == 30 && $paytype == 2) {
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>31,'text'=>'进行签到','btn_type'=>'','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
		}

		elseif($status == -1){
			
		}
		elseif($status == 0){
			
		}

		if($out_data){
			$out_string = '';
			foreach($out_data as &$val){
				
				if(!$val['is_show']) continue;
				
				if($val['btn_type'] == 'pay_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Usercenter/Pay/pay',array('trade_sn'=>$val['trade_sn'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'status_btn'){
					$string='<a class="'.$val['btn_class'].'" data-eventID="'.$val['id'].'" data-status="'.$val['status'].'" href="javascript:void(0)">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'show_btn'){
					$string = '<span style="color:red">'.$val['text'].'</span>';
				}elseif($val['btn_type'] == 'refund_btn') {
					$string='<a class="'.$val['btn_class'].'" data-eventID="'.$val['id'].'" data-status="'.$val['status'].'" href="javascript:void(0)">'.$val['text'].'</a> ';
				}
				$out_string .= $string;
			}
			
			return $out_string;
		}	
		
	}
}


function admin_get_event_detail_btn_2($data,$choose =0){
	if(!$data) return '';
	$paytype = $data['paytype'];
	$status = $data['status'];
	$pay_status = $data['pay_status'];
	$id = $data['id'];
	$trade_sn = $data['trade_sn'];
	$paytype = $data['paytype'];
	
	//btn_class: btn mbtn btn-primary btn-info btn-default
	if($choose){
		if($status == 1){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
			if($paytype == 0){
				$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>20,'text'=>'通过','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			}elseif($paytype == 1){
				$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>10,'text'=>'通过','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			}elseif($paytype == 2){
				$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>30,'text'=>'通过','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			}		
		}elseif($status == 10){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'下单成功，待定金支付','btn_type'=>'show_btn','btn_class'=>'mbtn btn-default','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
		}elseif($status == 11){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>12,'text'=>'定金已支付，确认出行','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);		
		}elseif($status == 12){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'已确认出行,待支付余款','btn_type'=>'show_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
		}elseif($status == 20 ){
			//$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'下单成功，待支付','btn_type'=>'show_btn','btn_class'=>'mbtn btn-default','is_show'=>true);
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
		}elseif($status == 21 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>30,'text'=>'全款已支付，确认出行','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
		}
		elseif($status == 30 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>31,'text'=>'进行签到','btn_type'=>'','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			if($paytype == 2){
				$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
			}
		}
		/*elseif($status == 31 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>32,'text'=>'活动进行中','btn_type'=>'show_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}elseif($status == 32 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'活动结束，等待评论','btn_type'=>'show_btn','btn_class'=>'mbtn btn-default updatacommne','is_show'=>true);
		}elseif($status == 33 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单完成','btn_type'=>'show_btn','btn_class'=>'mbtn btn-default','is_show'=>true);
		}*/
		elseif($status == -1){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已删除','btn_type'=>'show_btn','btn_class'=>'btn btn-primary updatacommne','is_show'=>true);
		}elseif($status == 0){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已取消','btn_type'=>'show_btn','btn_class'=>'btn btn-primary updatacommne','is_show'=>true);
		}

		if($out_data){
			$out_string = '';
			foreach($out_data as &$val){
				
				if(!$val['is_show']) continue;
				
				if($val['btn_type'] == 'pay_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Usercenter/Pay/pay',array('trade_sn'=>$val['trade_sn'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'status_btn'){
					$string='<a class="'.$val['btn_class'].'" data-eventID="'.$val['id'].'" data-status="'.$val['status'].'" href="javascript:void(0)">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'show_btn'){
					$string = '<span style="color:red">'.$val['text'].'</span>';
				}
				$out_string .= $string;
			}
			
			return $out_string;
		}	
	}else{
		if($status == 1){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
			if($paytype == 0){
				$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>20,'text'=>'通过','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			}else{
				$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>10,'text'=>'通过','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			}		
		}elseif($status == 10){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'下单成功，待定金支付 ','btn_type'=>'show_btn','btn_class'=>'mbtn btn-default','is_show'=>true);
		}elseif($status == 11){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>12,'text'=>'定金已支付，确认出行','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);		
		}elseif($status == 12){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'已确认出行，待支付余款','btn_type'=>'show_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
		}elseif($status == 20 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'下单成功，待支付 ','btn_type'=>'show_btn','btn_class'=>'mbtn btn-default','is_show'=>true);
		}elseif($status == 21 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>30,'text'=>'全款已支付，确认出行','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
		}
		elseif($status == 30 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>31,'text'=>'进行签到','btn_type'=>'','btn_class'=>'mbtn btn-primary updataSign','is_show'=>true);
			if($paytype == 2){
				$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updataSign','is_show'=>true);
			}
		}
		/*elseif($status == 31 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>32,'text'=>'活动进行中','btn_type'=>'show_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}
		elseif($status == 32 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'活动结束，等待评论','btn_type'=>'show_btn','btn_class'=>'mbtn btn-default updatacommne','is_show'=>true);
		}
		elseif($status == 33 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单完成','btn_type'=>'show_btn','btn_class'=>'mbtn btn-default','is_show'=>true);
		}*/
		elseif($status == -1){
				//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已删除','btn_type'=>'show_btn','btn_class'=>'btn btn-primary updatacommne','is_show'=>true);
		}
		elseif($status == 0){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已取消','btn_type'=>'show_btn','btn_class'=>'btn btn-primary updatacommne','is_show'=>true);
		}

		if($out_data){
			$out_string = '';
			foreach($out_data as &$val){
				
				if(!$val['is_show']) continue;
				
				if($val['btn_type'] == 'pay_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Usercenter/Pay/pay',array('trade_sn'=>$val['trade_sn'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'status_btn'){
					$string='<a class="'.$val['btn_class'].'" data-eventID="'.$val['id'].'" data-status="'.$val['status'].'" href="javascript:void(0)">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'show_btn'){
					$string = '<span style="color:red">'.$val['text'].'</span>';
				}
				$out_string .= $string;
			}
			
			return $out_string;
		}	
		
	}
}











function get_event_order_pay_status($status,$ismanage=false){
	switch ($status)
	{
	case 0:
	   $result ='未支付';
	  break;
	case 1:
	   $result ='定金已支付';
	  break;
	case 2:
	   $result ='全额已支付';
	  break;
	}
	return $result;
}
function get_shop_order_pay_status($status){
	switch ($status)
	{
	case 0:
	   $result ='未支付';
	  break;
	case 2:
	   $result ='已支付';
	  break;
	case 3:
		$result ='优惠券免单';
		break;
	}
	return $result;
}

function get_event_order_status($status,$ismanage=false){
	switch ($status)
	{
	case -1:
	  $result ='订单已删除';
	  break;  
	case 0:
	   $result ='订单已取消';
	  break;
//预约报名	  
	case 1:
	   $result ='报名预约，等待空位';
	  break;
//定金支付报名	  
	case 10:
	   $result ='下单成功，待定金支付';
	  break;
	case 11:
	   $result ='定金已支付，待确认出行';
	  break;
	case 12:
	   $result ='已确认出行，待余款支付';
	  break;
//全额支付报名	  
	case 20:
	   $result ='下单成功，待全款支付';
	  break;
	case 21:
	   $result ='支付成功，待确认出行';
	  break;
	
//交费成功后
	case 30:
	   $result ='待出行签到';
	  break;
	case 31:
	   $result ='活动进行中';
	  break;
	case 32:
	   $result ='活动结束，等待评论';
	  break;
	case 33:
	   $result ='订单完成';
	  break;
	  
	case 60:
	   $result ='退款申请中';
	  break;	  
	case 61:
	   $result ='退款完成';
	  break;
	default:
	  $result ='未知订单状态';
	}
	return $result;
}

function updata_evevt_status($id,$status,$siteid=''){
	$siteid = $siteid?$siteid:SITEID;
	$event_attend_content = D('event_attend')->where(array('id' => $id,'siteid'=>$siteid))->find();	
	if (!$event_attend_content) return array('s'=>0,'m'=>'订单不存在！','url'=>'');
	$event_content = D('event')->where(array('id' => $event_attend_content['event_id'],'siteid'=>$siteid))->find();
	if (!$event_content) return array('s'=>0,'m'=>'活动不存在！','url'=>'');
	$current_status  = $event_attend_content['status'];
	$current_pay_status  = $event_attend_content['pay_status'];
	$current_pay_type  = $event_attend_content['paytype'];
	$current_order_type = $event_attend_content['ordertype'];
	$result_msg='';
	$result =false;
	switch ($status){
		case '-1'://管理员操作，暂不显示
		if($current_status == -1){
			$result_msg ='当前订单已被删除，无法重复删除';
		}else{
			if($current_status =! 0){
				$result_msg ='请先取消订单后再进行删除';
			}else{
				$result =true;
			} 
		} 
		break;
		  
		case '0'://用户操作//未支付前使用
		if($current_status == 0){
			$result_msg ='当前订单已被取消，无法重复取消';	
		}else{
			if($current_pay_type != 2){
				if($current_pay_status > 0){
					$result_msg ='当前订单已支付，无法取消';
				}else{
					$result =true;
				}
			}else{
				$result =true;
			}
			
		}
		break;
		  
		case '10'://管理员操作——确认约预为确正报名时使用
		if($current_status == 10){
			$result_msg ='当前订单已是定金待支付状态';
		}else{
			if($current_status !=1){
				$result_msg ='该订单不是预约订单，无法进行该操作';
			}else{
				if($current_pay_type !=1){
					$result_msg ='当前订单不是定金支付订单';
				}else{
					$result =true; 
				}
			}
		} 
		break;

		case '11'://管理员操作//手动确认定支付时使用
		if($current_status == 11){
			$result_msg ='当前订单已是定金已付状态';
		}else{
			if($current_status != 10){
				$result_msg ='当前订单不是定金未支付状态,无法进行该操作';
			}else{
				if($current_pay_status !=0){
					$result_msg ='当前订单已经是已支付状态';
				}else{
					if($current_pay_type !=1){
						$result_msg ='当前订单不是定金支付订单';
					}else{
						$save_data['pay_status'] = 1;
						$result =true;
					}
				} 
			}
		}
		break;
		case '12'://管理员操作//保险单员完成后确认让用户确认时使用
		if($current_status == 12){
			$result_msg ='当前订单已是保险处理完毕待确认状态';
		}else{
			if($current_status != 11){
				$result_msg ='当前订单不是定金已支付状态';
			}else{
				if($current_pay_status !=1){
					$result_msg ='当前订单定金还未支付，无法进行操作';
				}else{
					if($current_pay_type !=1){
						$result_msg ='当前订单不是定金支付订单';
					}else{
						$result =true;
					}
				} 
			}
		}
		break;
		
		case '20'://管理员操作——确认约预为确正报名进使用//全额支付订单
		if($current_status == 20){
			$result_msg ='当前订单已是订单未支付状态';
		}else{
			if($current_status !=1){
				$result_msg ='只有预约报名订单才可变更';
			}else{
				if($current_pay_type !=0){
					$result_msg ='当前订单为全额支付订单';
				}else{
					$result =true; 
				}
			}
		} 
		break;
		
		case '21'://管理员操作//手动确认定支付时使用//全额支付
		if($current_status == 21){
			$result_msg ='当前订单已是订单已支付状态';
		}else{
			if($current_status != 20){
				$result_msg ='当前订单不是未支付状态';
			}else{
				if($current_pay_status !=0){
					$result_msg ='当前订单已经是已支付状态';
				}else{
					if($current_pay_type !=0){
						$result_msg ='当前订单为定金支付订单';
					}else{
						$save_data['pay_status'] = 2;
						$result =true;
					}
				} 
			}
		}
		break; 

		case '30'://用户操作管理员操作//支付成功<br>等待参加
		if($current_status == 30){
			$result_msg ='当前订单已是支付成功待参加状态';
		}else{
			if($current_pay_type ==0){//全额支付订单
			
				if($current_status != 21){
					$result_msg ='当前订单不是待确认出行状态';
				}else{
					$result = true;
				}	
			}elseif($current_pay_type ==1){//余款支付订单
			
				if($current_status != 12){
					$result_msg ='当前订单不是余额未支付状态';
				}else{
					$save_data['pay_status'] = 2;
					$result =true;
				}
			}elseif($current_pay_type ==2){
				$save_data['pay_status'] = 2;
				$result =true;
			}
		
		}
		break;
		
		case '31'://管理员操作//确认签到后操作
		if($current_status == 31){
			$result_msg ='当前订单已签到';
		}else{
			if($current_status != 30){
				$result_msg ='当前订单不是待签到状态';
			}else{
				$result =true;
			}
		}
		break;
		
		case '32'://管理员操作//活动结束等待评论
		if($current_status == 32){
			$result_msg ='当前订单已评论';
		}else{
			if($current_status != 31){
				$result_msg ='当前订单不是进行中的状态';
			}else{
				$result =true;
			}
		}
		break;
		
		case '33'://用户操作//评论后结束后结束活动
		if($current_status == 33){
			$result_msg ='当前订单已经完成';
		}else{
			if($current_status != 32){
				$result_msg ='当前订单不是活动结束待评论状态';
			}else{
				$result =true;
			}
		}
		break;
		case '61'://管理员操作//退款
			if($current_status == 61){
				$result_msg ='当前订单已经退款';
			}else{
				$result =true;
			}
		break;
		default:
		  $result_msg ='操作失败，如果您是用户，请联系'.get_upgrading(2).'或者'.get_upgrading(3);
		  $result =false;

	}
	if ($result == true) {
		if($status == 10 || $status == 20 || $status == 30){
			if($current_order_type == 2){
				$save_data['ordertype'] = 1;
			}
		}
		$save_data['status'] = $status;
		$calendar_info = D('event_calendar_time')->where(array('siteid'=>$siteid,'id'=>$event_attend_content['calendar_id']))->find();
		$event_info = D('event')->where(array('id'=>$event_attend_content['event_id'],'siteid'=>$siteid))->find();
		$total_member = D('event_signer')->where(array('order_id'=>$event_attend_content['id'],'siteid'=>$siteid))->count();
		$res = D('event_attend')->where(array('id' => $id,'siteid'=>$siteid))->save($save_data);		
		if($res){
		/**********************对该订单中报名人表中的订单状态也进行相对应的改变*********************************/
			$resul['order_status'] = $status;
			$signer_arr = D('event_signer')->where(array('order_id'=>$id,'siteid'=>$siteid,'status'=>1))->save($resul);
		/*********************************************************************************************************/
			$event_uid = $event_attend_content['uid'];
			$user_info = query_user(array('nickname'),$event_uid);
			$user_name = $user_info['nickname'];
			$web_url = "http://".$_SERVER['HTTP_HOST'];
			$webinfo = json_decode(WEBSITEINFO,true);			
			$signer_info_count = D('event_signer')->where(array('siteid'=>$siteid,'order_id'=>$event_attend_content['id']))->count();
			$traveldata= array(
							'user_name'=>$user_name,
							'trade_sn' =>$event_attend_content['trade_sn'],
							'event_title'=>$event_content['title'],
							'calendar_starttime'=>$calendar_info['starttime'],
							'total_member'=>$signer_info_count,
							'webname'=>$webinfo['webname'],
							'web_slogan'=>$webinfo['slogan'],
							'web_url'=>$web_url,
							'noticetype'=>'confirmed_travel',
							'web_telphone'=>$webinfo['telphone'],
						);
			
			switch($status){
				case '0':					
					//得到排期的id
					$cid = $event_attend_content['calendar_id'];
					//排期regnumber人数减掉订单中的人数
					$list['order_status'] = $status;
					$cardid = $event_attend_content['cardid'];
					if($cardid){
						$card_save_data['status'] = 1;
						D('pointcard')->where(array('userid'=>is_login(),'siteid'=>$siteid,'cardid'=>$cardid))->save($card_save_data);
						$event_card_data['cardid'] = '';
						D('event_attend')->where(array('id' => $id,'siteid'=>$siteid))->save($event_card_data);
					}
					D('event_signer')->where(array('order_id'=>$event_attend_content['id'],'siteid'=>$siteid))->save($list);
					D('event_calendar_time')->where(array('id' => $cid,'siteid'=>$siteid))->setDec('regnumber',$signer_info_count);
					D('Message')->sendMessageWithoutCheckSelf($event_content['uid'],query_user('nickname',is_login()).'取消了对活动['.$event_content['title'].']的报名' ,'取消报名通知', U('Manage/Order/event_detail',array('trade_sn'=>$event_attend_content['trade_sn'])),is_login());
				break;
				case '12':

					$contactways=array($event_attend_content['contact_email']);
					D('Message')->addSendMessage('send_email_to_user',$contactways,$traveldata,0,1);
					
				break;
				case '30':
					$contactways=array($event_attend_content['contact_email']);
					D('Message')->addSendMessage('send_email_to_user',$contactways,$traveldata,0,1);			
				break;
				case '61':
					//得到排期的id
					$cid = $event_attend_content['calendar_id'];
					//排期regnumber人数减掉订单中的人数
					$list['order_status'] = 0;
					D('event_signer')->where(array('order_id'=>$event_attend_content['id'],'siteid'=>$siteid))->save($list);
					D('event_calendar_time')->where(array('id' => $cid,'siteid'=>$siteid))->setDec('regnumber',$signer_info_count);
				break;

			}
			return array('s'=>1,'m'=>'操作成功','url'=>'');
		}else{
			return array('s'=>0,'m'=>'操作失败，请重试','url'=>'');
		}
	} else {
		return array('s'=>0,'m'=>$result_msg,'url'=>'');
	}
}
function get_event_order_status_detail_title($status,$ismanage=false){
	switch ($status)
	{
	case -1:
	  $result ='';
	  break;  
	case 0:
	   $result ='';
	  break;
//预约报名	  
	case 1:
	   $result ='活动预约';
	  break;
//定金支付报名	  
	case 10:
	   $result ='定金支付';
	  break;
	case 11:
	   $result ='出行确认中';
	  break;
	case 12:
	   $result ='余款支付';
	  break;
//全额支付报名	  
	case 20:
	   $result ='全款支付';
	  break;
	case 21:
	   $result ='出行确认中';
	  break;
	

//交费成功后
	case 30:
	   $result ='待签到';
	  break;
	case 31:
	   $result ='进行中';
	  break;
	case 32:
	   $result ='等待评论';
	  break;
	case 33:
	   $result ='活动完成';
	  break;
	  
	case 60:
	   $result ='退款申请中';
	  break;	  
	case 61:
	   $result ='退款完成';
	  break;
	default:
	  $result ='';
	}
	return $result;
}

function check_event($status){
	
	switch ($status)
	{
	case -1:
	  $result ='该活动已经被删除';
	  break;  
	case 0:
	   $result ='该活动已经被禁用';
	  break;
	case 1:
	   $result ='';
	  break;
	default:
	  $result ='未知活动条件错误，请联系管理员';
	}
	return $result;
}

function check_event_scheduling($content){
	if($content['status'] == 1){						
		if(strtotime("$content[endtime]")-time() > 0){
			if($content['maxpeople'] != 0){
				if(($content['maxpeople']-$content['regnumber']) < 0){
						$result = '';//报满预约
				}else{
					$result= '';//接受报名
				}
			}else{
					$result= '';//接受报名
			}
		}else{
			if(strtotime("$content[starttime]")-time() > 0){
				$result= '';
			}else{
				if(strtotime("$content[overtime]")-time() >= 0){
					$result= '进行中';
				}else{
					$result= '已结束';
				}
			}
		}
	}elseif($content['status'] == 2){
		$result = '';//接受报名
	}elseif($content['status'] == 3){
		$result = '';//报满预约
	}elseif($content['status'] == 4){
		$result = '';
	}elseif($content['status'] == 5){
		$result = '进行中';
	}elseif($content['status'] == 6){
		$result = '已结束';
	}elseif($content['status'] == -1){
		$result = '已删除';
	}elseif($content['status'] == 0){
		$result = '已禁用';
	}
	if($result){
		return '该排期'.$result.',无法付款';
	}
}

function get_insurance($id=''){
	if($id){
		$insurance_arr = M('insurance')->where("status=1 and siteid = ".SITEID.' and id='.$id)->order("id")->find();
	}else{
		$insurance_arr = M('insurance')->where("status=1 and siteid = ".SITEID)->order("id")->select();
	}
	if($insurance_arr){
		return $insurance_arr;
	}
}


function get_insurance_select($id = ''){
	$get_insurance = get_insurance();
	if($get_insurance){
		$string = '<select class="insurance_li form-control form_check" name="insurance">';
		$string .= '<option value="" >不使用保险</option>';
		foreach ($get_insurance as $k => $v) {
			if($id){
				$selected = ($v['id']==$id) ? 'selected' : '';
			}
			$string .= '<option value="'.$v['id'].'" '.$selected.'>'.$v['name'].'(保额'.$v['sum_insured'].') '.$v['price'].'元/人 </option>';
		}
		$string .= '</select>';
	}
	
	return $string;
	
}
   /*** update_order_info 该方法用于用户更改订单信息后对订单进行数据更新
	**  @param $order_id 订单ID $calendar_id 排期ID
	***/
function update_order_info($order_id,$calendar_id){
		$signer_arr = D('event_signer')->where(array('calendar_id'=>$calendar_id,'siteid'=>SITEID,'order_id'=>$order_id))->select();
		$order_info = D('event_attend')->where("id = ".$order_id." and siteid = ".SITEID."")->find();
		//$insurance_total = 0;
		$total_count = count($signer_arr);
		$event_price = $order_info['price'];
	
		if($order_info['paytype'] == 0){
			$total_price = $event_price * $total_count;
			if($order_info['cardid'] != ''){
				$card_contacts = explode(',', $order_info['cardid']);
				$mycard_count = count($card_contacts);
					for($i=0;$i<$mycard_count;$i++){ 
						$card_info = D('Pointcard')->check_card($card_contacts[$i]);
						if(!$card_info['status']){
							$this->error($card_info['msg']);
						}else{
							$card_info[$i] = D('pointcard')->where(array('cardid'=>$card_contacts[$i],'siteid'=>SITEID))->find();
							$card_price_amount[] = $card_info[$i]['amount'];
							$card_name[] = $card_info[$i]['typename'];
							$savedata_card[] = $card_contacts[$i];	
						}		
					}
					$savedata['cardid'] = implode(',', $savedata_card);
					$card_price_num = count($card_price_amount);
					for($i=0;$i<$card_price_num;$i++){ 
						$card_price += $card_price_amount[$i];
					}

				$card_amount = $card_price;				
				if($total_price >= $card_amount){
					$data['totalprice'] = $total_price - $card_amount;
					$data['payprice'] = $data['totalprice'];
					$data['leftprice'] = 0;
				}else{
					$data['totalprice'] = $total_price;
					$data['payprice'] = 0;
					$data['leftprice'] = 0;
				}				
			}else{
				$data['totalprice'] = $total_price;
				$data['payprice'] = $total_price;
				$data['leftprice'] = 0;
			}			
			D('event_attend')->where(array('id'=>$order_info['id'],'siteid'=>SITEID))->save($data);
		}else{
			$total_price = $event_price * $total_count;
			$deposit_total = $order_info['deposit'] * $total_count;
			$leftprice_price = $total_price - $deposit_total;
			if($order_info['cardid'] != ''){
				$card_info = D('pointcard')->where(array('siteid'=>SITEID,'cardid'=>$order_info['cardid']))->find();
				$card_amount = $card_info['amount'];								
				if($card_amount > $leftprice_price){
					$data['totalprice'] = $deposit_total;
					$data['payprice'] = $deposit_total;
					$data['leftprice'] = 0;
				}else{
					$data['totalprice'] = $total_price - $card_amount;
					$data['payprice'] = $deposit_total;
					$data['leftprice'] = $data['totalprice'] - $data['payprice'];
				}
			}else{
				$data['totalprice'] = $total_price;
				$data['payprice'] = $deposit_total;
				$data['leftprice'] = $leftprice_price;
			}
			D('event_attend')->where(array('id'=>$order_info['id'],'siteid'=>SITEID))->save($data);
		}		
}

/*二维码生成*/
function set_qrcode($param,$type='website',$siteid='',$web_url=''){
	$is_save = false;
	
	$siteid = $siteid?$siteid:get_webinfo('siteid');
	
	$dir =  './Uploads/QRCode/website_'.$siteid.'/';
	$part =  '/Uploads/QRCode/website_'.$siteid.'/';


	if(!is_dir($dir)){
		if(mkdir($dir, 0777, true)){
			$is_save = true;
		} else {
			$is_save = false;
		}
	}else{
		$is_save = true;
	}
	

	if($is_save){
		switch ($type)
		{
			case 'website':
			  $websits    = D('websit')->where("siteid=".$siteid)->find();
			  $mobile_url = "http://".$websits['url'].".huodongli.cn/";
		
			  $qrcode_url = $type.'_'.$siteid.'.png';
			  break;			
			case 'event':
			  $mobile_url = U('mobile/event/detail', $param,true,true,$web_url);
			  $qrcode_url = $type.'_'.$param['id'].'.png';
			  break;
			case 'issue':
			  $mobile_url = U('mobile/issue/issuecontentdetail', $param,true,true,$web_url);
			  $qrcode_url = $type.'_'.$param['id'].'.png';
			  break;
			case 'blog':
			  $mobile_url = U('mobile/blog/detail', $param,true,true,$web_url);
			  $qrcode_url = $type.'_'.$param['id'].'.png';
			  break;
			default:
			  $mobile_url = U('mobile/index/index', '',true,true,$web_url);
			  $qrcode_url = 'website_'.$siteid.'.png';
		}
		
		
		vendor("PHPQRCode.phpqrcode");
		// 纠错级别：L、M、Q、H
		$level = 'L';
		// 点的大小：1到10,用于手机端4就可以了
		$size = 8;
		// 下面注释了把二维码图片保存到本地的代码,如果要保存图片,用$fileName替换第二个参数false
	
		// 生成的文件名
		$fileName = $dir.$qrcode_url;
		$QRcode = new \QRCode();
		$QRcode->png($mobile_url,$fileName,$level,$size);
		
		/*
		$logo_icons = get_cover(get_webinfo('cover_id'),'path');
		$logo = '.'.$logo_icons;//准备好的logo图片   
		$QR = $fileName;//已经生成的原始二维码图   
		

		if ($logo !== FALSE) {  
		
			$QR = imagecreatefromstring(file_get_contents($QR));   
			$logo = imagecreatefromstring(file_get_contents($logo));   
			$QR_width = imagesx($QR);//二维码图片宽度
			$QR_height = imagesy($QR);//二维码图片高度   
			$logo_width = imagesx($logo);//logo图片宽度   
			$logo_height = imagesy($logo);//logo图片高度 
			
			imagesavealpha($logo, true);
			imagesavealpha($QR, true);
			
			$logo_qr_width = $QR_width / 5;   
			$scale = $logo_width/$logo_qr_width;   
			//$logo_qr_height = $logo_height/$scale; 
			$logo_qr_height = $QR_height/5; 
			$from_width = ($QR_width - $logo_qr_width) / 2;   
			//重新组合图片并调整大小   
			imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height);   
		}
		//输出图片   
		ImagePng($QR, $fileName);  
 		ImageDestroy($QR);*/
		
		if(file_exists($fileName)){
			return $part.$qrcode_url;
		}else{
			return false;
		}
			
		
	}else{
		return false;
	}
}

function sms_alerts($tel,$msg,$type,$webname='',$siteid=''){
	$webinfo = json_decode(WEBSITEINFO,true);
	$webname = $webname?$webname:$webinfo['webname'];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://sms-api.luosimao.com/v1/send.json");
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_HTTPAUTH , CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD  , "api:key-".C('SMSKEY'));
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, array('mobile' => $tel,'message' => $msg.'['.$webname.']【活动力】'));
	$res = curl_exec( $ch );
	curl_close( $ch );
	$result_arr = json_decode($res,true);
	$data['uid'] = is_login();
	$data['siteid'] = $siteid;
	$data['create_time'] = time();
	$data['item_type'] = $type;
	$data['back_info'] = sms_back_info($result_arr['error']) != '' ? sms_back_info($result_arr['error']):'未知错误';
	$data['msg'] = $msg;
	$data['telephone'] = $tel;
	D('sms_log')->add($data);
	return $result_arr;
}

function sendMessageIp(){ 
	$sendmessage_ip=ip2long(get_client_ip());
	$endtime=time();
	$starttime=$endtime-6000;
	$map['sendmessage_ip']=$sendmessage_ip;
	$map['create_time']=array('between',$starttime.",".$endtime);
	$num=D('sms_log')->where($map)->count();
	if($num<10){ 
		$res['status']=1;
	}else{ 
		$res['status']=0;
		$res['info']='您操作太频繁了，请稍后再试！';
	}
	return $res;
}



function sendbackinfo($result){ 
	$statusStr = array(
		"0" => "发送成功",
		"-1" => "参数不全",
		"-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
		"30" => "密码错误",
		"40" => "短信内容缺少签名信息",
		"41" => "短信余额不足",
		"42" => "帐户已过期",
		"43" => "请求发送IP不在白名单内",
		"50" => "短信内容存在敏感词"
		);	

	return $statusStr[$result];
}
	


function sendMail($address,$title,$message,$webname)
{
	
    vendor('PHPMailer.phpmailer#class');
 
    $mail=new PHPMailer();
	
    // 设置PHPMailer使用SMTP服务器发送Email
    $mail->IsSMTP();
 
    // 设置邮件的字符编码，若不指定，则为'UTF-8'
    $mail->CharSet='UTF-8';
 
    // 添加收件人地址，可以多次使用来添加多个收件人
    $mail->AddAddress($address);
 
    // 设置邮件正文
    $mail->Body=$message;
 
    // 设置邮件头的From字段。
    $mail->From=C('MAIL_ADDRESS');
 
    // 设置发件人名字
    $mail->FromName=$webname;
 
    // 设置邮件标题
    $mail->Subject=$title;
 
    // 设置SMTP服务器。
    $mail->Host=C('MAIL_SMTP');
 
    // 设置为"需要验证"
    $mail->SMTPAuth=true;
 
    // 设置用户名和密码。
    $mail->Username=C('MAIL_LOGINNAME');
    $mail->Password=C('MAIL_PASSWORD');
 
    // 发送邮件。
    return($mail->Send());
}

function websit_type($find=''){
       $str_arr=array(
	      'default'=>'户外',
		  'tourism'=>'旅游',
		 );
		if($find){
		  return $str_arr[$find];
		}else{
		  return $str_arr;
		}

}
/*判断是不是旅游网站*/
function websit_trave(){
	$webinfo_theme = C('GETWEBSITE_INFO')['theme'];
	if($webinfo_theme == 'tourism'){
	  return true; 
	}else{
	  return false;
	}
}
/*梁朝阳*/
function websit_runners(){
    $webinfo_theme = C('GETWEBSITE_INFO')['theme'];
    if($webinfo_theme == 'runners'){
      return true; 
    }else{
      return false;
    }
}


function website_theme_config(){
	 $str_arr=array(
		'default'=>array('id'=>'default','name'=>'默认','img'=>__ROOT__.'default.png'),
		'tourism'=>array('id'=>'tourism','name'=>'旅行','img'=>__ROOT__.'tourism.png'),
		'sports'=>array('id'=>'sports','name'=>'体育','img'=>__ROOT__.'sports.png'),
        'runners'=>array('id'=>'runners','name'=>'商城','img'=>__ROOT__.'runners.png'),
	 );
	return $str_arr;
}


function get_website_theme($find=''){
	foreach(website_theme_config() as $key => $val){
		$str_arr[$val['id']] =$val['name'] ;
	}

	if($find){
	  return $str_arr[$find];
	}else{
	  return $str_arr;
	}
}
function template_color_config(){
   $str_arr=array(
				'default'=>array(),
				'tourism'=>array(
					'tourism_black'=>array('id'=>'tourism_black','name'=>'黑色','img'=>__ROOT__.'tourism_black.jpg'),
					'tourism_blue'=>array('id'=>'tourism_blue','name'=>'蓝色','img'=>__ROOT__.'tourism_blue.jpg'),
					'tourism_green' =>array('id'=>'tourism_green','name'=>'绿色','img'=>__ROOT__.'tourism_green.jpg'),
					'tourism_orange'=>array('id'=>'tourism_orange','name'=>'橙色','img'=>__ROOT__.'tourism_orange.jpg'),
					'tourism_red' =>array('id'=>'tourism_red','name'=>'红色','img'=>__ROOT__.'tourism_red.jpg'),
				),
				'sports'=>array(),
				'runners'=>array(
				'runners_orange'=>array('id'=>'runners_orange','name'=>'橙色','img'=>__ROOT__.'tourism_black.jpg'),
					'runners_black'=>array('id'=>'runners_black','name'=>'黑色','img'=>__ROOT__.'tourism_orange.jpg'),
					'runners_blue'=>array('id'=>'runners_blue','name'=>'蓝色','img'=>__ROOT__.'tourism_blue.jpg'),
					'runners_green' =>array('id'=>'runners_green','name'=>'绿色','img'=>__ROOT__.'tourism_green.jpg'),
					
					'runners_red' =>array('id'=>'runners_red','name'=>'红色','img'=>__ROOT__.'tourism_red.jpg'),
				),
			 );
     return $str_arr;

}

/*
*色系**
*param 1 -返回颜色
*paran 2 -返回图片
*/
function get_template_color($find='',$a=1){
    $rs = D('websit')->where('siteid='.SITEID)->getField('theme');
	if(!empty(template_color_config()[$rs])){
		switch($a){
			case 1:
				foreach(template_color_config()[$rs] as $key => $val){
					$str_arr[$val['id']] = $val['name'] ;
				}
				if($find){
					return $str_arr[$find];
				}else{
					
					return $str_arr;
				}
			break;
			case 2:
			   foreach(template_color_config()[$rs] as $key => $val){
					$str_arr[$val['id']] =$val['id'] ;
				
				}
				if($find){
					return $str_arr[$find];
				}else{
					return $str_arr;
				}
			break;
          
		}
		
		
	}else{
		return false;
	
	}

}

/**
	 * 目的地-国内
	*/
	function return_desty_in($no){
		$desty = array(
			array('id'=>1,'name'=>'周边短途'),
			array('id'=>2,'name'=>'国内长线')
		);
		$temp = array();
		foreach($desty as $key => $val){
			$temp[$val['id']] = $val['name'];
		}
		if($no){
			return $temp[$no];
		}else{
			return $desty;
		}
	}
	/**
	 * 目的地-国外
	*/
	function return_desty_out($no){
		$desty = array(
			array('id'=>1,'name'=>'欧美'),
			array('id'=>2,'name'=>'东南亚'),
			array('id'=>3,'name'=>'日韩'),
			array('id'=>4,'name'=>'港澳台'),
			array('id'=>5,'name'=>'澳新'),
			array('id'=>6,'name'=>'非洲'),
			array('id'=>7,'name'=>'南北极'),
			array('id'=>8,'name'=>'星际'),
			array('id'=>9,'name'=>'其他')
		);
		$temp = array();
		foreach($desty as $key => $val){
			$temp[$val['id']] = $val['name'];
		}
		if($no){
			return $temp[$no];
		}else{
			return $desty;
		}
	}
	/*
	 * 得到目的地
	 */
	 function get_desty($desty_type,$desty){
		$desty_arr = explode(',',$desty);
		$dtid = count($desty_arr);
		switch($desty_type){
			case 1;
			if($dtid == 1){
				return return_desty_in($desty);				
			}elseif($dtid > 1){
				$arr = array();
				foreach(return_desty_in() as $key => $val){
					if(in_array($val['id'],$desty_arr)){
						$arr[] = $val['name'];
					}
				}
				$str = implode(',',$arr);
				return $str;
			}
			break;
			case 2;
			if($dtid == 1){
				return return_desty_out($desty);				
			}elseif($dtid > 1){
				$arr = array();
				foreach(return_desty_out() as $key => $val){
					if(in_array($val['id'],$desty_arr)){
						$arr[] = $val['name'];
					}
				}
				$str = implode(',',$arr);
				return $str;
			}
			break;
		}
	 }
	 
	 function sms_back_info($back_num){
		$sms_arr = array(
			0 => '发送成功',
		  -10 => '验证信息失败',
		  -20 => '短信余额不足',
		  -30 => '短信内容为空',
		  -31 => '短信内容存在敏感词',
		  -32 => '短信内容缺少签名信息',
		  -40 => '错误的手机号',
		  -50 => '请求发送IP不在白名单内'		
		);		
		return $sms_arr[$back_num];
	 }
	 /*批量生成二维码*/
	function websit_batch_qcode(){
		$websitqcode = D('websit')->where("status=1")->select();
		foreach($websitqcode as $k=>$v){
			$qrcodefind =  D('qrcode')->where(array('siteid'=>$websitqcode[$k]['siteid'],'types'=>'website'))->find();  
			$qrcode_url = set_qrcode(array('id'=>$websitqcode[$k]['siteid']),'website',$websitqcode[$k]['siteid']);
			if($qrcodefind){
				$qrcode_data['siteid'] = $websitqcode[$k]['siteid'];
				$qrcode_data['linkid'] = $websitqcode[$k]['siteid'];
				$qrcode_data['types'] =  'website';
				$qrcode_data['uid'] =  is_login();
				$qrcode_data['url'] =  $qrcode_url;
				D('qrcode')->where(array('siteid'=>$websitqcode[$k]['siteid'],'types'=>'website'))->save($qrcode_data);
			}else{
				$qrcode_data['siteid'] = $websitqcode[$k]['siteid'];
				$qrcode_data['linkid'] = $websitqcode[$k]['siteid'];
				$qrcode_data['types'] =  'website';
				$qrcode_data['uid'] =  is_login();
				$qrcode_data['url'] =  $qrcode_url;
				D('qrcode')->add($qrcode_data);
			}
		}
		
	}
	//角色升级-2014-11-26-dlx
	function get_upgrading($find='')
	{

		$newlist=D('channel_member')->where(array('siteid'=>SITEID,'status'=>1))->find();
		
		if($newlist){ 
			$newlist['member']=$newlist['member']?$newlist['member']:'普通会员';
			$newlist['leader']=$newlist['leader']?$newlist['leader']:'官方领队';
			$newlist['master']=$newlist['master']?$newlist['master']:'认证达人';
			$newlist['admin'] =$newlist['admin']?$newlist['admin']:'管理员';
			$string_arr=array(
				1=>$newlist['member'],
				2=>$newlist['leader'],
				4=>$newlist['master'],
				3=>$newlist['admin']
			);
		}else{ 
			$string_arr=array(
				1=>'普通会员',
				2=>'官方领队',
				4=>'认证达人',
				3=>'管理员'
			);
		}
		if($find){
			return $string_arr[$find];
		}else{
			return $string_arr;
		}
	}
	/*更改checked*/
   function batch_vip_edit(){
       $member	=	D('member')->where("is_use>1 and checked=0")->select();
       foreach($member as $k=>$v){
			$data['is_use']	=	1;
			D('member')->where("is_use>1 and checked=0")->save($data);
	   
	   }
   
   }
  /*
  **俱乐部类型-2014-11-28-dlx -pm**
  */
  	function get_clubName($find='')
	{
	   /**1俱乐部 3商城 2旅行版**/
		$string_arr	=	array(
				1=>'iClub 户外版', 
				2=>'iTrip 旅行版',
				3=>'iStore'
		);
		if($find){
			return $string_arr[$find];
		}else{
			return $string_arr;
		}
		
	}
	
/*卡类--2014-11-28--dlx -pm*/
 function cardtype_cipher($find='')
 {
		$string_arr=array(
			//1=>'活动卡',//有密卡
			2=>'代金券',//无密卡
			
		);
		if($find){
			return $string_arr[$find];
		}else{
			return $string_arr;
		}
 
 }
   /*
	* @param $id int 公共表应用ID
    * 得到当前应用的状态 
	*/
	function get_common_app_status($id){
		$app_common = D('websit_apply')->where(array('id'=>$id))->find();
		$app_primary = D('websit_install_apply')->where(array('app_id'=>$id,'siteid'=>SITEID))->find();
		$status_common = $app_common['status'];
		if(ucfirst($app_common['app_model']) == 'Event'){
			return $str = '默认开启';
		}else{
			switch($status_common){
				case -2;
					return $str = '应用开发中'; 
				break;
				case -1;
					return $str = '应用已下架';
				break;
				case 0;
					return $str = '应用已禁用'; 
				break;
				case 1;
					if(!$app_primary){
						return $str = '未启用';
					}else{
						return $str = '已启用';
					}
				break;
				default;
					return $str = '状态错误'; 
			}
		}
	}
	 /*
	* @param $id int 私人应用表ID 
    * 得到当前应用的状态 
	*/
	function get_primary_app_status($id){
		$app_primary = D('websit_install_apply')->where(array('id'=>$id,'siteid'=>SITEID))->find();
		$app_common = D('websit_apply')->where(array('id'=>$app_primary['app_id']))->find();		
		$status_common = $app_common['status'];
		switch($status_common){
			case -2;
				return $str = '应用开发中'; 
			break;
			case -1;
				return $str = '应用已下架' ;
			break;
			case 0;
				return $str = '应用已禁用'; 
			break;
			case 1;				
				if($app_primary){
					return $str = '已启用';
				}else{
					return $str = '未启用';
				}			
			break;
			default;
				return $str = '状态错误'; 
		}
	}
	/*
	 * $param $id 公共表应用ID
	 * 生成全部应用栏目下的操作按钮
	 */
	 function get_all_app_btn($id){
		$apply_primary = D('websit_install_apply')->where(array('app_id'=>$id,'siteid'=>SITEID))->find();
		$apply_common = D('websit_apply')->where(array('id'=>$id))->find();
		if(ucfirst($apply_common['app_model']) == 'Event'){
			$str =  1;
			$data['str'] = $str;
			return $data;
		}else{
			$str = '';
			switch($apply_common['status']){
				case -2;
					$str = '应用开发中';
					$data['str'] = $str;
					return $data;
				break;
				case -1;
					$str = '应用已下架';
				    $data['str'] = $str;
					return $data;
				break;
				case 0;
					 $str = '应用已禁用';
					$data['str'] = $str;
					return $data;
				break;
				case 1;
					if(!$apply_primary){
						$str .= "<a class='install_app' href='javascript:void(0)' data-ID=".$id.">启用</a>";
						$data['str'] = $str;
						return $data;
					}else{
						$str .= "<a class='unstall_app' href='javascript:void(0)' data-ID=".$id.">禁用</a> ";				
						if($apply_common['ifconfig'] == 1){
							$ifconfig = $apply_common['ifconfig'];
						}
						$data['ifconfig']= $ifconfig;
						$data['str'] = $str;
						return $data;				
					}
				break;
			}
		}
	 }
	 /*
	 * $param $id 私人表应用ID
	 * 生成我的应用栏目下的操作按钮
	 */
	 function get_my_app_btn($id){
		$apply_primary = D('websit_install_apply')->where(array('id'=>$id,'siteid'=>SITEID))->find();
		$app_id = $apply_primary['app_id'];
		$apply_common = D('websit_apply')->where(array('id'=>$app_id))->find();
		$str = '';
		switch($apply_common['status']){
			case -2;
				return $str = '应用开发中';
			break;
			case -1;
				$str .= "<a class='mbtn unstall_app btn-danger' href='javascript:void(0)' data-ID=".$id.">禁用</a> ";
				$str .= '应用已下架 ';				
				return $str;
			break;
			case 0;
				$str .= "<a class='mbtn unstall_app btn-danger' href='javascript:void(0)' data-ID=".$id.">禁用</a> ";
				$str .= '应用已禁用 ';			
				return $str;
			break;
			case 1;				
				$str .= "<a class='mbtn unstall_app btn-danger' href='javascript:void(0)' data-ID=".$id.">禁用</a> ";				
				if($apply_common['ifconfig'] == 1){
					$str .= "<a class='mbtn config_app btn-info' href='javascript:void(0)' data-ID=".$id.">设置</a> ";
				}
				return $str;								
			break;
		}			
	}
	 
/*
 **俱乐部等级 2014-12-2 dlx -pm
 */
  function get_clubRank($find='')
  {
		$string_arr	= array(
				1=>A,
				2=>B,
				3=>C
		);
		if($find){
			return $string_arr[$find];
		}else{
			return $string_arr;
		}
  }
  /*
  *验证手机号码
  */
    function Gcheck_Mobile($mobile){
	    if(!preg_match("/^1[0-9]{10}$/",$mobile) || $mobile==''){
			return true;
		}else{
			return false;
		}
    
	}
  /*
  *验证邮箱
  */
    function Gcheck_Email($email){
		if(!preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',$email) || $email ==''){
			return true;
		}else{
			return false;
		}
    
	}
	
	function Gcheck_url($url){
		if(!preg_match('/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',$url)){
			return true;
		}else{
			return false;
		}
	}
	
	
/*
**批量生成公告二维码 12-2-dlx pm
*/
 function websit_blog_qcode(){
	$websitqcode = D('document')->where("status>=0")->select();
	foreach($websitqcode as $k=>$v){
		$qrcodefind =  D('qrcode')->where(array('siteid'=>$websitqcode[$k]['siteid'],'linkid'=>$websitqcode[$k]['id'],'types'=>'blog'))->find();
		$websits	=  D('websit')->where(array('siteid'=>$websitqcode[$k]['siteid']))->find();
		
		$web_url    =  $websits['url'].".huodongli.cn";
		
		$qrcode_url = set_qrcode(array('id'=>$websitqcode[$k]['id']),'blog',$websitqcode[$k]['siteid'],$web_url);
		
		if($qrcodefind){		
			   $qrcode_data = array(
					'siteid'	=>	$websitqcode[$k]['siteid'],
					'linkid'	=>	$websitqcode[$k]['id'],
					'types'		=>	'blog',
					'uid'		=>	is_login(),
					'url'		=>	$qrcode_url
			   );
			   $list = D('qrcode')->where(array('siteid'=>$websitqcode[$k]['siteid'],'linkid'=>$websitqcode[$k]['id'],'types'=>'blog'))->save($qrcode_data);
		  
		}else{
				$qrcode_data = array(
					'siteid'	=>	$websitqcode[$k]['siteid'],
					'linkid'	=>	$websitqcode[$k]['id'],
					'types'		=>	'blog',
					'uid'		=>	is_login(),
					'url'		=>	$qrcode_url
				);
				$list = D('qrcode')->add($qrcode_data);
		
		}

    }
}

/*
**批量活动二维码 
*/
 function websit_event_qcode(){
	$websitqcode = D('event')->where("status>=0")->select();
	foreach($websitqcode as $k=>$v){
		$qrcodefind =  D('qrcode')->where(array('siteid'=>$websitqcode[$k]['siteid'],'linkid'=>$websitqcode[$k]['id'],'types'=>'event'))->find();
		$websits	=  D('websit')->where(array('siteid'=>$websitqcode[$k]['siteid']))->find();
		
		$web_url    =  $websits['url'].".huodongli.cn";
		
		$qrcode_url = set_qrcode(array('id'=>$websitqcode[$k]['id']),'event',$websitqcode[$k]['siteid'],$web_url);
		
		if($qrcodefind){		
			   $qrcode_data = array(
					'siteid'	=>	$websitqcode[$k]['siteid'],
					'linkid'	=>	$websitqcode[$k]['id'],
					'types'		=>	'event',
					'uid'		=>	is_login(),
					'url'		=>	$qrcode_url
			   );
			   $list = D('qrcode')->where(array('siteid'=>$websitqcode[$k]['siteid'],'linkid'=>$websitqcode[$k]['id'],'types'=>'event'))->save($qrcode_data);
		  
		}else{
				$qrcode_data = array(
					'siteid'	=>	$websitqcode[$k]['siteid'],
					'linkid'	=>	$websitqcode[$k]['id'],
					'types'		=>	'event',
					'uid'		=>	is_login(),
					'url'		=>	$qrcode_url
				);
				$list = D('qrcode')->add($qrcode_data);
		
		}

    }
}

/*
**批量故事二维码** 
*/
 function websit_issue_qcode(){
	$websitqcode = D('issue_content')->where("status>=0")->select();
	foreach($websitqcode as $k=>$v){
		$qrcodefind =  D('qrcode')->where(array('siteid'=>$websitqcode[$k]['siteid'],'linkid'=>$websitqcode[$k]['id'],'types'=>'issue'))->find();
		$websits	=  D('websit')->where(array('siteid'=>$websitqcode[$k]['siteid']))->find();
		
		$web_url    =  $websits['url'].".huodongli.cn";
		
		$qrcode_url = set_qrcode(array('id'=>$websitqcode[$k]['id']),'issue',$websitqcode[$k]['siteid'],$web_url);
		
		if($qrcodefind){		
			   $qrcode_data = array(
					'siteid'	=>	$websitqcode[$k]['siteid'],
					'linkid'	=>	$websitqcode[$k]['id'],
					'types'		=>	'issue',
					'uid'		=>	is_login(),
					'url'		=>	$qrcode_url
			   );
			   $list = D('qrcode')->where(array('siteid'=>$websitqcode[$k]['siteid'],'linkid'=>$websitqcode[$k]['id'],'types'=>'issue'))->save($qrcode_data);
		  
		}else{
				$qrcode_data = array(
					'siteid'	=>	$websitqcode[$k]['siteid'],
					'linkid'	=>	$websitqcode[$k]['id'],
					'types'		=>	'issue',
					'uid'		=>	is_login(),
					'url'		=>	$qrcode_url
				);
				$list = D('qrcode')->add($qrcode_data);
		
		}

    }
}

 /*
 **卡类状态 2014-12-2 dlx -pm
 */
  function get_pointcard($cardid)
  {
	$card_info = D('pointcard')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->find();
	$status = $card_info['status'];
	if(!empty($card_info['endtime'])){
		if($card_info['endtime'] <= time()){
			$string_arr	= array(
				-1=> '已删除<span style="color:red">(已到期)</span>',
				 0=> '已禁用<span style="color:red">(已到期)</span>',
				 1=> '未使用<span style="color:red">(已到期)</span>',
				 2=> '已冻结<span style="color:red">(已到期)</span>',
				 3=> '已使用<span style="color:red">(已到期)</span>'
			);
		}else{
			$string_arr	= array(
				-1=> '已删除',
				 0=> '已禁用',
				 1=> '未使用',
				 2=> '已冻结',
				 3=> '已使用'
			);
		}
	}else{
		$string_arr	= array(
				-1=> '已删除',
				 0=> '已禁用',
				 1=> '未使用',
				 2=> '已冻结',
				 3=> '已使用'
			);
	}
	return $string_arr[$status];
  }
	/*
	 * 优惠券行为写入日志
	 * @param $cardinfo 优惠券单条信息 $action_name 行为名称 $action_type 行为类型 $status 行为完成后 优惠券状态 $to_uid 如果是赠送行为，传入受赠人的uid
	 */
	 function add_card_log($cardid,$card_status,$action_name,$action_type,$to_uid,$admin){
		$card_info = D('pointcard')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->find();
		/*$card_name = D('pointcard_type')->where(array('ptypeid'=>$card_info['ptypeid'],'siteid'=>SITEID))->getField('name');*/
		$card_name =$card_info['typename'];
		if($to_uid){			
			$save_data['siteid'] = SITEID;
			$save_data['card_status'] = $card_status;
			$save_data['action_name'] = $admin ? '派发代金券' : '赠送代金券' ;
			$save_data['action_type'] = $admin ? '派发(代金券/活动卡)' : '赠送(代金券/活动卡)' ;
			$save_data['createtime'] = time();
			$save_data['uid'] = is_login();
			$save_data['from_uid'] = is_login();
			$save_data['to_uid'] = $to_uid;
			$save_data['amount'] = $card_info['amount'];
			$save_data['card_name'] = $card_name;
			$save_data['cardid'] = $card_info['cardid'];
			$save_data['cardtype'] = $card_info['cardtype'];
			$save_data['starttime'] = $card_info['starttime'] != 0 ? $card_info['starttime'] : 0 ;
			$save_data['endtime'] = $card_info['endtime'] != 0 ? $card_info['endtime'] : 0 ;
			$save_data['card_info'] = json_encode($card_info);
			D('pointcard_log')->add($save_data);
			$data['siteid'] = SITEID;
			$data['card_status'] = $card_status;
			$data['action_name'] = $admin ? '获得派发代金券' : '获赠代金券' ;
			$data['action_type'] = $admin ? '获得派发(代金券/活动卡)' : '获赠(代金券/活动卡)' ;
			$data['createtime'] = time();
			$data['uid'] = $to_uid;
			$data['from_uid'] = is_login();
			$data['to_uid'] = $to_uid;
			$data['amount'] = $card_info['amount'];
			$data['card_name'] = $card_name;
			$data['cardid'] = $card_info['cardid'];
			$data['cardtype'] = $card_info['cardtype'];
			$data['starttime'] = $card_info['starttime'] != 0 ? $card_info['starttime'] : 0 ;
			$data['endtime'] = $card_info['endtime'] != 0 ? $card_info['endtime'] : 0 ;
			$data['card_info'] = json_encode($card_info);
			D('pointcard_log')->add($data);
		}else{
			$list['siteid'] = SITEID;
			$list['card_status'] = $card_status;
			$list['action_name'] = $action_name;
			$list['action_type'] = $action_type;
			$list['createtime'] = time();
			$list['uid'] = is_login();
			$list['amount'] = $card_info['amount'];
			$list['card_name'] = $card_name;
			$list['cardid'] = $card_info['cardid'];
			$list['cardtype'] = $card_info['cardtype'];
			$list['starttime'] = $card_info['starttime'] != 0 ? $card_info['starttime'] : 0 ;
			$list['endtime'] = $card_info['endtime'] != 0 ? $card_info['endtime'] : 0 ;
			$list['card_info'] = json_encode($card_info);		
			$list['from_uid'] = 0;
			$list['to_uid'] = 0;			
			D('pointcard_log')->add($list);
		}
		
	 }
	 /*
	  *  付款成功之后 如果有使用优惠券，更新优惠券状态 并写入日志表
	  * @param $订单号 卡号
	  */
	 function do_update_card($trade_sn,$siteid=0){
	 	if(!$siteid){
	 		$siteid=SITEID; 
	 	}
		$event_info = D('event_attend')->where(array('trade_sn'=>$trade_sn,'siteid'=>$siteid))->find();
		$cardid = $event_info['cardid'];
		if($cardid){
			$data['status'] = 3;
			D('pointcard')->where(array('cardid'=>$cardid,'siteid'=>$siteid))->save($data);
			add_card_log($cardid,3,'活动订单付款成功后更改【代金券】状态','(代金券/活动卡)(使用/取消)');
		}
	 }
	function do_update_shop_card($order_sn,$siteid=0){
		if(!$siteid){ 
			$siteid=SITEID;
		}
		$shop_card_id = D('shop_ordersn')->where(array('siteid'=>$siteid,'order_sn'=>$order_sn))->getField('cardid');
		if($shop_card_id){
			$data['status'] = 3;
			D('pointcard')->where(array('cardid'=>$shop_card_id,'siteid'=>$siteid))->save($data);
			add_card_log($cardid,3,'商城订单付款成功后更改【代金券】状态','(代金券/活动卡)(使用/取消)');
		}
	 }
	function app_isopen($app_model='',$siteid=''){	
		if(!$app_model) return false;
		$siteid = $siteid?$siteid:SITEID;
		$app_model = strtolower($app_model);	
		if (in_array($app_model,array("system", "index", "apply", "event"))){
			return true;
		}		
		$app_info = D('websit_install_apply')->where(array('app_model'=>$app_model,'siteid'=>$siteid))->find();
		if($app_info){
			return true;
		}else{
			return false;
		}
	}
	function get_appinfo($model='',$siteid=''){	
	
		$siteid = $siteid?$siteid:SITEID;
		if(!$model){
			return false;
		}		
		if(!app_isopen($model)){
			return false;
		}
		
		
		
		$model_system = D('websit_apply')->where(array('app_model' => $model))->find();
		if(!$model_system){
			return false;
		}
		if($model_system['is_nav']){
			$channel_websit = D('channel_websit')->where(array('model' => $model,'siteid' => $siteid))->find();
			if($channel_websit){
				$model_data['name'] = $channel_websit['title'];
			}else{
				$model_data['name'] = $model_system['app_name'];
			}
		}else{
			$model_data['name'] = $model_system['app_name'];
		}
		
		$model_data['model'] = $model_system['app_model'];
		$model_data['icon'] = $model_system['icon'];
		$model_data['url'] = $model_system['url'];
		
		
		return $model_data;
	}
/**
*得到商品属性** dlx -2014-12-8 pm 
**/
function get_shop_sku_types($find='')
{	
	$sku_arr = explode(',',$find);
	$tid = count($sku_arr);
	$sku_arr = D('shop_sku_types_system')->select();	
	if($sku_arr){
		if($tid == 1){
			foreach($sku_arr as $key=>$value) {
				$srting_arr[$value['sku_types_id']] = $value['types_name'];
			}
			if($find){
				return $srting_arr[$find];
			}else{
				return $srting_arr;
			}
		}elseif($tid >1){
			$title_arr = array();
			foreach($sku_arr as $key=>$value) {
				if(in_array($value['sku_types_id'],$sku_arr)){
					$title_arr[] = $value['types_name'];
				}
			}
			$str = implode(',',$title_arr);
			return $str;
		}
	}else{
		return '';
	}
}

/**
*判断有没有商品类目** dlx -2014-12-11pm 
**/
function shop_is_sku_category($id='')
{	

	$str_arr = D('shop_category')->where(array('status'=>1,'siteid' => SITEID,'id'=>$id))->find();
  
	$sku_cate =$str_arr['sku_category_id'];
	$sku_cate_arr	=  D('shop_sku_category')->where("sku_category_id=$sku_cate")->find();
	$sku_types_id	=	$sku_cate_arr['sku_types_id'];
	$sku_types_id_arr = D('shop_sku_types_attribute_stystem')->where("sku_types_id in($sku_types_id)")->select();
	
	if($sku_types_id_arr){
		return true;
	}else{
		return false;
	}   
		
}
/*
*得到商品类目* 2014-12-16
*/
function get_shop_sku_detailed($id=''){
   $category = D('shopCategory')->find($id);   //--先查询分类--
   $top_category_id = $category['pid'] == 0 ? $category['id'] : $category['pid'];
	if ($top_category_id == $category['id']) {
		
	} else {
	    $list = D('shop_sku_category')->where("sku_category_id=".$category['sku_category_id'])->find();   //--关联类目--
		$sku_types_id=explode(",",$list['sku_types_id']);
		return $sku_list = D('shop_sku_types_system')->where("sku_types_id in($list[sku_types_id])")->select();   //--得到属性--
	}
	
}
/**
得到属性值-的名称--*/
function get_shop_sku_types_attribute($attribute_id=''){
		$list = D('shop_sku_types_attribute_stystem')->select();
		foreach($list as $key=>$val){
		  $string_arr[$list[$key]['attribute_id']]=$list[$key]['attribute_name'];
		}
	
	
		if($attribute_id){
			return $string_arr[$attribute_id];
		}else{
			return '单品';
		}
	
}
/**
*通过属性值反查-属性表
*/
function get_shop_sku_types_name($attribute_id,$result=1){
	$list = D('shop_sku_types_attribute_stystem')->where("attribute_id=".$attribute_id)->find();
	$list_types = D("shop_sku_types_system")->where("sku_types_id=".$list['sku_types_id'])->find();
	if($result==1){
		  return $list_types['sku_types_id'];
		
    }elseif($result==2){
		 return $list_types['types_name'];
	}
	
}



function get_shop_sku_type_name($sku_types_id=''){
		$list = D('shop_sku_types_system')->select();
		foreach($list as $key=>$val){
		  $string_arr[$list[$key]['sku_types_id']]=$list[$key]['types_name'];
		}
	
	
		if($sku_types_id){
			return $string_arr[$sku_types_id];
		}else{
			return '商品型号';
		}
	
}
/*判断是不是颜色--*/
function shop_is_color($sku_types_id=0){
	$str_arr  = D('shop_sku_types_system')->where("is_color=1 and sku_types_id=".$sku_types_id)->find();
	if($str_arr){
		return true;
	}else{
	   return false;
	}
}

/**
--得到对应的属性值和名称--dlx 12-22*****
**/
function get_shop_sku_detail_name($goods_id){
	$list = D("shop_sku_detailed_display")->where("goods_id=".$goods_id)->field("attribute_value")->select();
	foreach($list as $key=>$val){
		foreach($val as $k=>$v){
			$arr[$k][$key]=$v; 
		}
	}
	unset($key);
	unset($val);
	unset($k);
	unset($v);
	foreach($arr['attribute_value'] as $key=>$val){
		 $arr1[] = explode(",",$arr['attribute_value'][$key]);
	}
	
	
	
}
 

function get_shop_order_status($status,$ismanage=false){
	switch ($status)
	{
	case -1;
		$result ='订单已删除';
	break;
	case 0;
		$result ='订单已取消';
	break;
	case 2;
		$result ='订单已过期';
	break;
	case 20:
	   $result ='下单成功，待支付';
	  break;
	case 21:
	   $result ='支付成功，待发货';
	  break;
	case 22;
		$result ='已发货,待确认';
	break;
	case 32:
	   $result ='交易成功';
	  break;
	case 33:
	   $result ='交易成功';
	  break;
	  
	case 60:
	   $result ='退款申请中';
	  break;	 
	case 61:
	   $result ='退款申请中';
	  break;	  
	default:
	  $result ='未知订单状态';
	}
	return $result;
}

function get_mobile_shop_order_status($status,$ismanage=false){
	switch ($status)
	{
	case -1;
		$result ='订单删除';
	break;
	case 0;
		$result ='订单取消';
	break;
	case 2;
		$result ='订单关闭';
	break;
	case 20:
	   $result ='待支付';
	  break;
	case 21:
	   $result ='待发货';
	  break;
	case 22;
		$result ='待收货';
	break;
	case 32:
	   $result ='交易成功';
	  break;
	case 33:
	   $result ='交易成功';
	  break;
	case 60:
	   $result ='申请退款';
	  break;
	case 61:
	   $result ='退款申请中';
	  break;	  
	default:
	  $result ='未知订单状态';
	}
	return $result;
}



function update_shop_order_status($id,$status,$siteid=SITEID){
	
	$shop_order_info = D('shop_ordersn')->where(array('id' => $id))->find();
	
	if (!$shop_order_info) return array('s'=>0,'m'=>'订单不存在！','url'=>'');
	if(!$shop_order_info['supplier_id']){$shop_order_info['supplier_id']	=	$shop_order_info['siteid'];}
	if($shop_order_info['supplier_id']!=$siteid){return array('s'=>0,'m'=>'您不是本订单供货商，无修改权限！','url'=>''); }
	$current_status  = $shop_order_info['status'];
	$current_pay_status  = $shop_order_info['pay_status'];
	$result_msg='';
	$result =false;
	switch($status){
		case -1;
		if($current_status == -1){
			$result_msg ='当前订单已被删除，无法重复删除';
		}else{
			if($current_status =! 0){
				$result_msg ='请先取消订单后再进行删除';
			}else{
				$result =true;
			} 
		} 
		break;
		case 0://用户操作//未支付前使用
		if($current_status == 0){
			$result_msg ='当前订单已被取消，无法重复取消';	
		}else{
			if($current_pay_status > 0){
				$result_msg ='当前订单已支付，无法取消';
			}else{
				$result =true;
			}
		}
		break;
		case 22://用户操作//未支付前使用
				$status = 32;
				$result =true;
		break;
		case 32://用户操作//未支付前使用
				$status = 33;
				$result =true;
		break;
		case 33;
			if($current_status == 33){
				$result_msg ='当前订单已完成，无法确认收货';	
			}else{
				if($current_pay_status == 0 && $current_status!=22){
					$result_msg ='当前订单未支付，无法确认收货';
				}else{
					$result =true;
				}
			}
		break;
	}
	if($result){
		$save_data['status'] = $status;
		$res = D('shop_ordersn')->where(array('id' => $id))->save($save_data);
        if($status==32){
            $shopstuatus_data=array(
                'id'  => $id,
                'shop_order_sn'  =>$shop_order_info['order_sn'],
                'execute_time'   =>time()+604800,
                'change_status'  =>$status,
                );
            D('Message')->addSendMessage('auto_change_shop_status','',$shopstuatus_data,0,1,$siteid); 
        }
		if($status==33){
			$websit_cash_log	=	D('websit_cash_log')->where('order_sn="'.$shop_order_info['order_sn'].'"')->select();
			foreach($websit_cash_log as $key=>$val){
				if($val['operation_type']=='a'){
					$supplier_thaw_price	=	$supplier_thaw_price+$val['total'];
					$supplier_id	=	$val['siteid'];
				}elseif($val['operation_type']=='b'){
					$supplier_thaw_price	=	$supplier_thaw_price-$val['total'];
					$supplier_id	=	$val['siteid'];
				}elseif($val['operation_type']=='d'){
					$supplier_thaw_price	=	$supplier_thaw_price-$val['total'];
					$supplier_id	=	$val['siteid'];
				}
				if($val['operation_type']=='c'){
					$seller_thaw_price	=	$seller_thaw_price+$val['total'];
					$seller_id	=	$val['siteid'];
				}
			}
			$supplier_cash_record	=	D('websit_cash_record')->where('siteid='.$supplier_id)->find();	//查询供货商账户余额
			$supplier_balance	=	$supplier_cash_record['balance']	+	$supplier_thaw_price;	//计算：增加供货商余额
			$supplier_distribute_frozen	=	$supplier_cash_record['distribute_frozen']	-	$supplier_thaw_price;//计算：解冻供货商金额
			$supplier_cash_record_arr['balance']	=	$supplier_balance;
			$supplier_cash_record_arr['distribute_frozen']	=	$supplier_distribute_frozen;
			D('websit_cash_record')->where('siteid='.$supplier_id)->save($supplier_cash_record_arr);	//保存供货商解冻数据
			
			$seller_cash_record	=	D('websit_cash_record')->where('siteid='.$seller_id)->find();	//查询分销商账户余额
			$seller_balance	=	$seller_cash_record['balance']	+	$seller_thaw_price;		//计算：增加分销商余额
			$seller_distribute_frozen	=	$seller_cash_record['distribute_frozen']	-	$seller_thaw_price;		//计算：解冻分销商金额
			$seller_cash_record_arr['balance']	=	$seller_balance;
			$seller_cash_record_arr['distribute_frozen']	=	$seller_distribute_frozen;
			D('websit_cash_record')->where('siteid='.$seller_id)->save($seller_cash_record_arr);	//保存分销商解冻数据
		}
		if($res){
			switch($status){
				case 0;
				break;
				case -1;
				break;
			}
			return array('s'=>1,'m'=>'操作成功');
		}else{
			return array('s'=>0,'m'=>'操作失败，请重试');
		}
	}else{
		return array('s'=>0,'m'=>$result_msg);
	}
}






function get_shop_admin_btn($data){
	if(!$data) return '';

	$status = $data['status'];
	$pay_status = $data['pay_status'];
	$id = $data['id'];
	$trade_sn = $data['order_sn'];
	
	//btn_class: btn mbtn btn-primary btn-info btn-default
	
		if($status == 20 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updateOrder','is_show'=>true);
		}elseif($status == 21 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'发货','btn_type'=>'link_btn','btn_class'=>'mbtn deliver_goods btn-primary','is_show'=>true);
		}elseif($status == 22){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'已发货,待确认','btn_type'=>'show_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}elseif($status == 33){
				$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单完成','btn_type'=>'show_btn','btn_class'=>'btn btn-primary','is_show'=>true);
		}elseif($status == 0){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已取消','btn_type'=>'show_btn','btn_class'=>'btn btn-primary','is_show'=>true);
		}elseif($status == 2){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已过期','btn_type'=>'show_btn','btn_class'=>'btn btn-primary','is_show'=>true);
		}

		if($out_data){
			$out_string = '';
			foreach($out_data as &$val){
				
				if(!$val['is_show']) continue;
				
				if($val['btn_type'] == 'status_btn'){
					$string='<a class="'.$val['btn_class'].'" data-orderID="'.$val['id'].'" data-status="'.$val['status'].'" href="javascript:void(0)">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'show_btn'){
					$string = '<span style="color:red">'.$val['text'].'</span>';
				}elseif($val['btn_type'] == 'link_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Websit/Order/deliver_goods',array('order_sn'=>$val['trade_sn'])).'">'.$val['text'].'</a> ';
				}
				$out_string .= $string;
			}
			
			return $out_string;
		}	
	
}

function get_shop_admin_btn_tow($data){
	if(!$data) return '';

	$status = $data['status'];
	$pay_status = $data['pay_status'];
	$id = $data['id'];
	$trade_sn = $data['order_sn'];
	
	//btn_class: btn mbtn btn-primary btn-info btn-default
	
		if($status == 20 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updateOrder','is_show'=>true);
		}elseif($status == 21 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'发货','btn_type'=>'link_btn','btn_class'=>'mbtn btn-default bootbox-option2','is_show'=>true);
		}elseif($status == 22 ){

			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'22','text'=>'确认收货','btn_type'=>'confirm_btn','btn_class'=>'mbtn btn-default bootbox-option1','is_show'=>true);
        }elseif($status == 32 ){

			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'32','text'=>'完结订单','btn_type'=>'confirm_btn','btn_class'=>'mbtn btn-default bootbox-option1','is_show'=>true);
        }

		if($out_data){
			$out_string = '';
			foreach($out_data as &$val){
				
				if(!$val['is_show']) continue;
				
				if($val['btn_type'] == 'status_btn'){
					$string='<a class="'.$val['btn_class'].' ajax-get " href="'.U('Manage/Order/do_update_shop_status',array('id'=>$val['id'],'status'=>$val['status'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'confirm_btn'){
                                                    $string='<a class="'.$val['btn_class'].' ajax-get " href="'.U('Manage/Order/do_update_success_status',array('id'=>$val['id'],'status'=>$val['status'])).'">'.$val['text'].'</a> ';
                                                }elseif($val['btn_type'] == 'show_btn'){
					$string = '<span style="color:red">'.$val['text'].'</span>';
				}elseif($val['btn_type'] == 'link_btn'){
					$string='<a class="'.$val['btn_class'].'" href="javascript:" data-id="'.$val['trade_sn'].'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'refund_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Manage/Order/deliver_goods',array('order_sn'=>$val['trade_sn'])).'">'.$val['text'].'</a> ';
				}
				$out_string .= $string;
			}
			
			return $out_string;
		}	
	
}





function get_shop_detail_btn($data,$choose = 0){
	
	if(!$data) return '';

	$status = $data['status'];
	$pay_status = $data['pay_status'];
	$id = $data['id'];
	$trade_sn = $data['order_sn'];
	
	//btn_class: btn mbtn btn-primary btn-info btn-default
	
		if($status == 20 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'mbtn btn-default updateOrder','is_show'=>true);
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'去支付','btn_type'=>'pay_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}elseif($status == 21 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'待发货','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'申请退款','btn_type'=>'refund_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}elseif($status == 22){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>22,'text'=>'确认收货','btn_type'=>'status_btn','btn_class'=>'mbtn btn-primary updateOrder','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'申请退款','btn_type'=>'refund_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}elseif($status == 60 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'申请退货中','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'申请退款','btn_type'=>'refund_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}elseif($status == 32){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'交易成功','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'申请退款','btn_type'=>'refund_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}elseif($status == 61 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'申请退货中','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'申请退款','btn_type'=>'refund_btn','btn_class'=>'mbtn btn-primary','is_show'=>true);
		}elseif($status == 33){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'交易成功','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
		}elseif($status == 0){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已取消','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
		}elseif($status == 2){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已过期','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
		}elseif($status == -1){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已删除','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
		}

		if($out_data){
			$out_string = '';
			foreach($out_data as &$val){
				
				if(!$val['is_show']) continue;
				
				if($val['btn_type'] == 'pay_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Usercenter/Shoporder/pay',array('order_sn'=>$val['trade_sn'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'refund_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Usercenter/Shoporder/shop_order_refund',array('order_sn'=>$val['trade_sn'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'status_btn'){
					$string='<a class="'.$val['btn_class'].'" data-orderID="'.$val['id'].'" data-status="'.$val['status'].'" href="javascript:void(0)">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'show_btn'){
					$string = '<span style="color:red">'.$val['text'].'</span>';
				}
				$out_string .= $string;
			}
			
			return $out_string;
		}	
	
}

function get_mobile_shop_detail_btn($data,$choose = 0){
	
	if(!$data) return '';

	$status = $data['status'];
	$pay_status = $data['pay_status'];
	$id = $data['id'];
	$trade_sn = $data['order_sn'];
	
	//btn_class: btn mbtn btn-primary btn-info btn-default
	
		if($status == 20 ){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>0,'text'=>'取消订单','btn_type'=>'status_btn','btn_class'=>'am-btn am-btn-default updateOrder','is_show'=>true);
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'去支付','btn_type'=>'pay_btn','btn_class'=>'am-btn am-btn-primary','is_show'=>true);
		}elseif($status == 21 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'待发货','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'申请退款','btn_type'=>'refund_btn','btn_class'=>'am-btn am-btn-primary','is_show'=>true);
		}elseif($status == 22){
			$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>33,'text'=>'确认收货','btn_type'=>'status_btn','btn_class'=>'am-btn am-btn-primary updateOrder','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'申请退款','btn_type'=>'refund_btn','btn_class'=>'am-btn am-btn-primary','is_show'=>true);
		}elseif($status == 60 ){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'申请退货中','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
			$out_data[1] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'查看退款信息','btn_type'=>'refund_btn','btn_class'=>'am-btn am-btn-primary','is_show'=>true);
		}elseif($status == 33){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单完成','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
		}elseif($status == 0){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已取消','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
		}elseif($status == 2){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已过期','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
		}elseif($status == -1){
			//$out_data[0] = array('trade_sn'=>$trade_sn,'id'=>$id,'status'=>'','text'=>'订单已删除','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
		}

		if($out_data){
			$out_string = '';
			foreach($out_data as &$val){
				
				if(!$val['is_show']) continue;
				
				if($val['btn_type'] == 'pay_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Mobile/Shoporder/pay',array('order_sn'=>$val['trade_sn'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'refund_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Mobile/Shoporder/shop_order_refund',array('order_sn'=>$val['trade_sn'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'status_btn'){
					$string='<a class="'.$val['btn_class'].'" data-orderID="'.$val['id'].'" data-status="'.$val['status'].'" href="javascript:void(0)">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'show_btn'){
					$string = '<span style="color:red">'.$val['text'].'</span>';
				}
				$out_string .= $string;
			}
			
			return $out_string;
		}	
	
}
//申请退款状态
function get_shop_refund_status($status){
	switch ($status)
	{
	case -1;
		$result ='未同意';
	break;
	case 0;
		$result ='已取消';
	break;
	case 1;
		$result ='申请中';
	break;
	case 2;
		$result ='处理中';
	break;
	case 11;
		$result ='同意退款';
	break;
	default:
	  $result ='未知订单状态';
	}
	return $result;
}
//申请退款状态
function get_shop_refund_select($status){
	switch ($status)
	{
	case 0;
		$result ='请选择退款原因';
	break;
	case 1;
		$result ='未按约时间发货';
	break;
	case 2;
		$result ='虚假发货';
	break;
	case 3;
		$result ='商品质量问题';
	break;
	case 4;
		$result ='收到商品描述不符';
	break;
	case 5;
		$result ='其他';
	break;	
	default:
	  $result ='其他';
	}
	return $result;
}
function get_shop_refund_btn($data,$choose = 0){
		if(!$data) return '';

		$status = $data['refund_status'];
		$pay_status = $data['pay_status'];
		$id = $data['id'];
		$order_sn = $data['order_sn'];
	//btn_class: btn mbtn btn-primary btn-info btn-default
	
		if($status == -1 ){
			$out_data[0] = array('order_sn'=>$order_sn,'id'=>$id,'status'=>'','text'=>'查看回复','btn_type'=>'refund_btn','btn_class'=>'mbtn refund_goods btn-primary','is_show'=>true);
		}elseif($status == 0 ){
			$out_data[0] = array('order_sn'=>$order_sn,'id'=>$id,'status'=>'','text'=>'已取消','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
		}elseif($status == 1){
			$out_data[0] = array('order_sn'=>$order_sn,'id'=>$id,'status'=>'','text'=>'修改信息','btn_type'=>'refund_btn','btn_class'=>'mbtn refund_goods btn-primary','is_show'=>true);
		}elseif($status == 2 ){
			$out_data[0] = array('order_sn'=>$order_sn,'id'=>$id,'status'=>'','text'=>'查看信息','btn_type'=>'refund_btn','btn_class'=>'mbtn refund_goods btn-primary','is_show'=>true);
		}elseif($status == 11){
			$out_data[0] = array('order_sn'=>$order_sn,'id'=>$id,'status'=>'','text'=>'查看回复','btn_type'=>'refund_btn','btn_class'=>'mbtn refund_goods btn-primary','is_show'=>true);
		}
		if($out_data){
			$out_string = '';
			foreach($out_data as &$val){
				
				if(!$val['is_show']) continue;
				
				if($val['btn_type'] == 'refund_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Usercenter/Shoporder/shop_order_refund_edit',array('order_sn'=>$val['order_sn'],'id'=>$val['id'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'show_btn'){
					$string = '<span style="color:red">'.$val['text'].'</span>';
				}
				$out_string .= $string;
			}
			
			return $out_string;
		}	
	
}
function get_shop_refund_admin_btn($data,$choose = 0){
		if(!$data) return '';

		$status = $data['refund_status'];
		$pay_status = $data['pay_status'];
		$id = $data['id'];
		$order_sn = $data['order_sn'];
	//btn_class: btn mbtn btn-primary btn-info btn-default
	
		if($status == -1 ){
			$out_data[0] = array('order_sn'=>$order_sn,'id'=>$id,'status'=>'','text'=>'查看/审批','btn_type'=>'refund_btn','btn_class'=>'btn refund_goods btn-info','is_show'=>true);
		}elseif($status == 0 ){
			$out_data[0] = array('order_sn'=>$order_sn,'id'=>$id,'status'=>'','text'=>'已取消','btn_type'=>'show_btn','btn_class'=>'','is_show'=>true);
		}elseif($status == 1){
			$out_data[0] = array('order_sn'=>$order_sn,'id'=>$id,'status'=>'','text'=>'查看/审批','btn_type'=>'refund_btn','btn_class'=>'btn refund_goods btn-info','is_show'=>true);
		}elseif($status == 2 ){
			$out_data[0] = array('order_sn'=>$order_sn,'id'=>$id,'status'=>'','text'=>'查看/审批','btn_type'=>'refund_btn','btn_class'=>'btn refund_goods btn-info','is_show'=>true);
		}elseif($status == 11){
			$out_data[0] = array('order_sn'=>$order_sn,'id'=>$id,'status'=>'','text'=>'查看/审批','btn_type'=>'refund_btn','btn_class'=>'btn refund_goods btn-info','is_show'=>true);
		}
		if($out_data){
			$out_string = '';
			foreach($out_data as &$val){
				
				if(!$val['is_show']) continue;
				
				if($val['btn_type'] == 'refund_btn'){
					$string='<a class="'.$val['btn_class'].'" href="'.U('Manage/Order/shop_order_refund_edit',array('order_sn'=>$val['order_sn'],'id'=>$val['id'])).'">'.$val['text'].'</a> ';
				}elseif($val['btn_type'] == 'show_btn'){
					$string = '<span style="color:red">'.$val['text'].'</span>';
				}
				$out_string .= $string;
			}
			
			return $out_string;
		}	
	
}

function get_deliver_com($id){
	$deliver_com_arr = array();
	$deliver_com_arr = array(
		array('id'=>1,'name'=>'顺丰快递','url'=>''),
		array('id'=>2,'name'=>'圆通快递','url'=>''),
		array('id'=>3,'name'=>'申通快递','url'=>''),
		array('id'=>4,'name'=>'中通快递','url'=>''),
		array('id'=>5,'name'=>'EMS','url'=>''),
		array('id'=>6,'name'=>'包裹邮件','url'=>''),
		array('id'=>7,'name'=>'天天快递','url'=>'')
	);
	$deliver_com_arr_temp = array();
	foreach($deliver_com_arr as $key => $val){
		$deliver_com_arr_temp[$val['id']] = $val;
	}
	if($id){
		return $deliver_com_arr_temp[$id];
	}else{
		return $deliver_com_arr;
	}
}
function get_deliver_com_select($id){
	$get_deliver_com = get_deliver_com();
	$string = "<select style='border:1px solid #ccc' class='btn col-md-9' name='express_com'>";
	$string .= '<option value="" >选择快递公司</option>';
	foreach($get_deliver_com as $k => $v){
		if($id){
				$selected = ($v['id']==$id) ? 'selected' : '';
		}
		$string .= '<option value="'.$v['id'].'" '.$selected.'>'.$v['name'].'</option>';
	}
	$string .= '</select>';
	return $string;
}
/***************减少库存数量********************/
function reduce_goods_num($order_sn){
	$goods_list = D('shop_order_info')->where(array('order_sn'=>$order_sn,'siteid'=>SITEID))->select();
	/*************************/
	foreach($goods_list as $key => $val){
		D('shop')->where(array('id'=>$val['goods_id'],'siteid'=>SITEID))->setInc('sell_num',$val['goods_num']);
		D('shop')->where(array('id'=>$val['goods_id'],'siteid'=>SITEID))->setDec('goods_num',$val['goods_num']);
		if(!empty($val['sku_id'])){
			D('shop_sku_detailed')->where(array('sku_id'=>$val['sku_id'],'goods_id'=>$val['goods_id'],'siteid'=>SITEID))->setDec('stock',$val['goods_num']);
		}		
	}
	/****************************/
}
/***************记录订单操作********************/
function add_shop_order_log($order_sn,$uid,$msg,$action,$dateline){
	$order_log['order_sn']	=	$order_sn;
	$order_log['uid']	=	$uid;
	$order_log['user_name']	=	query_user('nickname',$uid);
	$order_log['msg']	=	$msg;
	$order_log['action']	=	$action;
	$order_log['dateline']	=	$dateline;
	$order_log['isadmin']	=	checked_admin($uid);
	$order_log['clientip'] = get_client_ip();
	D('shop_order_log')->add($order_log);
}

/*
*通过sku_id查询得到 -颜色-
**/
function get_shop_types_attribute_names($sku_id=0){
	$list = D('shop_sku_detailed')->where("sku_id=".$sku_id)->field("sku_title")->find();
	$sku_title = json_decode($list['sku_title'],true);
	
	foreach($sku_title as $val){
		$str.="&nbsp;&nbsp;".get_shop_sku_types_name($val['value'],2)."&nbsp;:&nbsp;".get_shop_sku_types_attribute($val['value']);
		
	}
	return $str;
}
function get_schedule($event_id){
		$schedule_arr = get_event_calendar($event_id);
		$time_arr = array();
		foreach($schedule_arr as $key => $val){
			if($val['status'] == 1){
				if(strtotime($val['endtime'])-time() <= 0){
					unset($schedule_arr[$key]);
				}else{
					$time_arr[$key]['starttime'] = date('Y.m.d',strtotime($val['starttime']));
					$time_arr[$key]['overtime'] = date('Y.m.d',strtotime($val['overtime']));
					$time_arr[$key]['price'] = !empty($val['price']) ? '￥'.$val['price'] : '免费活动' ;
					if(!empty($val['maxpeople'])){
						$time_arr[$key]['text'] = $val['maxpeople'] > $val['regnumber'] ? '马上报名' : '马上预约';
					}else{
						$time_arr[$key]['text'] = '马上报名'; 
					}
				}
			}elseif($val['status'] == 2 || $val['status'] == 3){
				$time_arr[$key]['starttime'] = date('Y.m.d',strtotime($val['starttime']));
				$time_arr[$key]['overtime'] = date('Y.m.d',strtotime($val['overtime']));
				$time_arr[$key]['price'] = '￥'.$val['price'];
				$time_arr[$key]['text'] = $val['status'] == 2 ? '马上报名' : '马上预约';
			}elseif($val['status'] <= 0 || $val['status'] > 3){				
				unset($schedule_arr[$key]);				
			}				
		}
		return $time_arr;
}
function ludou_remove_width_height_attribute($content){

  $content =  preg_replace("/style=\".*?\"/i", "", $content);
  preg_match_all("/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png\.bmp]))[\'|\"].*?[\/]?>/i", $content, $images);
  if(!empty($images)) {
    foreach($images[0] as $index => $value){
      $new_img = preg_replace('/(width|height|border)="\d*"\s/', "", $images[0][$index]);
	  $new_img = preg_replace('/style=".+?"/','',$new_img);
	  $new_img = preg_replace('/data-original=".+?"/','',$new_img);
	  //$new_img = preg_replace('/src=".+?"/','src="'.$images[1][$index].'" data-original="'.$images[1][$index].'"',$new_img);
	  $new_img = preg_replace('/src=".+?"/','src="/Public/Core/images/grey.gif" class="lazy" data-original="'.$images[1][$index].'" data-rel="'.$images[1][$index].'"',$new_img);
      $content = str_replace($images[0][$index], $new_img, $content);
    }
  }

   $content = str_replace("<br />","<\\n\\r>",$content);
   $content = str_replace("<br/>","<\\n\\r>",$content);
   preg_match_all("/<[embed|EMBED].*?src=[\"](.*?(\.swf))[\"].*?[\/]?>/", $content, $video);

	if(!empty($video)){
		foreach ($video[1] as $kes => $vs) {
			$video[1][$kes]=gethtml5($vs);
			$new_video=preg_replace($video[0][$kes], 'iframe width="100%" height="300" frameborder="0" allowfullscreen="" src="'.$video[1][$kes].'" ></iframe', $video[0][$kes]);
			$content = str_replace($video[0][$kes], $new_video, $content);
		}	
	}
    $content = str_replace("<\\n\\r>","<br />",$content);
  return $content;
}
 function gethtml5($url='') {
        if(isset($url) && !empty($url)){
            preg_match_all('/http:\/\/(.*?)?\.(.*?)?\.com\/(.*)/',$url,$types);
        }else{
            return false;
        }
        $type = $types[2][0];
        $domain = $types[1][0];
        $isswf = strpos($types[3][0], 'v.swf') === false ? false : true;
        $method = substr($types[3][0],0,1);
        switch ($type){
            case 'youku' :
                if( $domain == 'player' ) {
					preg_match_all('/http:\/\/player\.youku\.com\/player.php\/sid\/(.*)?\/v\.swf/',$url,$url_array); 
                    $swf = 'http://player.youku.com/embed/'.str_replace('/','',$url_array[1][0]);//http://player.youku.com/embed/XNTg1ODcyMzcy
					
                }else if( $domain == 'v' ) {
                    preg_match_all('/http:\/\/v\.youku\.com\/v_show\/id_(.*)?\.html/',$url,$url_array);
                    $swf = 'http://player.youku.com/embed/'.str_replace('/','',$url_array[1][0]);//http://player.youku.com/embed/XNTg1ODcyMzcy
                }else{
                    $swf = $url;
                }
                break;
            case 'tudou' :
                if($isswf){
                    $swf = $url;
                }else{
                    $method = $method == 'p' ? 'v' : $method ;
                    preg_match_all('/http:\/\/www.tudou\.com\/(.*)?\/(.*)?/',$url,$url_array);
                    $str_arr = explode('/',$url_array[1][0]);
                    $count = count($str_arr);
                    if($count == 1) {
                        $id = explode('.',$url_array[2][0])[0];
                    }else if($count == 2){
                        $id = $str_arr[1];
                    }else if($count == 3){
                        $id = $str_arr[2];
                    }
                    $swf = 'http://www.tudou.com/'.$method.'/'.$id.'/v.swf';
                }
                break;
            default :
                $swf = $url;
                break;
        }
        return $swf;
    }
/*
 * 返回下单后订单类型
 * $param （$calendar_id 必填 排期ID） （$mem_count 选填 报名人数 传入值表示报名后的该排期订单类型，不传则表示报名前的排期订单类型）
 * author:vincent
 */
function return_ordertype($calendar_id,$mem_count){
	$calendar_info = D('event_calendar_time')->where(array('siteid'=>SITEID,'id' => $calendar_id))->find();
	$staus = $calendar_info['status'];
	$maxpeople = $calendar_info['maxpeople'];
	$regnumber = $calendar_info['regnumber'];
	switch($staus){
		case 1;
			if(!empty($maxpeople)){ 
				$total_reg_num = !empty($mem_count) ? $regnumber + $mem_count : $regnumber;
				if($maxpeople >= $total_reg_num){
					$ordertype = 1;
				}else{
					$ordertype = 2;
				}
			}else{
				$ordertype = 1;
			}
		break;
		case 2;
			$ordertype = 1;
		break;
		case 3;
			$ordertype = 2;
		break;
	}
	return $ordertype;
}

function get_issue_brand(){
	$rs_issue = D('issue')->where(array('status'=>1,'customization'=>1,'siteid'=>SITEID))->find();
	if($rs_issue){
		return $rs_issue['id'];
	}
	
}

/**
* 将字符串转换为数组
*
* @param	string	$data	字符串
* @return	array	返回数组格式，如果，data为空，则返回空数组
*/
function string2array($data) {
	if($data == '') return array();
	@eval("\$array = $data;");
	return $array;
}

/**
* 将数组转换为字符串
*
* @param	array	$data		数组
* @param	bool	$isformdata	如果为0，则不使用new_stripslashes处理，可选参数，默认为1
* @return	string	返回字符串，如果，data为空，则返回空
*/
function array2string($data, $isformdata = 1) {
	if($data == '') return '';
	if($isformdata) $data = $data;
	return var_export($data, TRUE);
	//return addslashes(var_export($data, TRUE));
}

function unserialize_config($cfg){
        if (is_string($cfg) ) {
            $arr = string2array($cfg);
		$config = array();
		foreach ($arr AS $key => $val) {
			$config[$key] = $val['value'];
		}
		return $config;
	} else {
		return false;
	}
}

/**
 * 返回响应地址
 */
function return_url($code, $is_api = 0){
	if($is_api){
		$domain = 'http://'.$_SERVER['HTTP_HOST'].'/Usercenter/Respond/respond_post/code/'.$code.'.html';
		return $domain;
		//return U('Usercenter/Respond/respond_post',array('code'=>$code),true,true);
	}else {
		return U('Usercenter/Respond/respond_get',array('code'=>$code),true,true);
	}
}
/**
 * 返回响应地址
 */
function mobile_return_url($code, $is_api = 0){
	if($is_api){
		$domain = 'http://'.$_SERVER['HTTP_HOST'].'/Mobile/Respond/respond_post/code/'.$code.'.html';
		return $domain;
		//return U('Usercenter/Respond/respond_post',array('code'=>$code),true,true);
	}else {
		return U('Mobile/Respond/respond_get',array('code'=>$code),true,true);
	}
}
/**
 * 返回订单状态
 */
function return_status($status) {
	$trade_status = array('0'=>'succ', '1'=>'failed', '2'=>'timeout', '3'=>'progress', '4'=>'unpay', '5'=>'cancel','6'=>'error');
	return $trade_status[$status];
}
/**
 * 返回订单手续费
 * @param  $amount 订单价格
 * @param  $fee 手续费比率
 * @param  $method 手续费方式
 */
function pay_fee($amount, $fee=0, $method=0) {
    $pay_fee = 0;
    if($method == 0) {
    	$val = floatval($fee) / 100;
    	$pay_fee = $val > 0 ? $amount * $val : 0;
    } elseif($method == 1) {
        $pay_fee = $fee;
    }
    return round($pay_fee, 2);
}

/**
 * 生成支付按钮
 * @param $data 按钮数据
 * @param $attr 按钮属性 如样式等
 * @param $ishow 是否显示描述
 */
function mk_pay_btn($data,$var='',$attr='style="float:left; width:100px;"',$ishow='1') {
	$pay_type = '';
	if(is_array($data)){
			$pay_type .= '<div class="banklist">';
		foreach ($data as $v) {
			
			if($var && $var == $v['pay_id']){
				$string =' checked';
			}else{
				$string ='';
			}
			
			
			$pay_type .= '<label '.$attr.'>';
			$pay_type .='<input name="pay_type" type="radio" value="'.$v['pay_id'].'" '.$string.'> <span class="'.$v['pay_code'].'">'.$v['name'].'</span>';
			$pay_type .= '</label>';
		}
		
		$pay_type .= '</div>';
	}
	return $pay_type;
}
/**
 * 生成支付按钮
 * @param $data 按钮数据
 * @param $attr 按钮属性 如样式等
 * @param $ishow 是否显示描述
 */
function mobile_mk_pay_btn($data,$var='',$attr='style="float:left; width:100px;"',$ishow='1') {
	$pay_type = '';
	if(is_array($data)){
			$pay_type .= '<div class="banklist">';
		foreach ($data as $v) {
			
			if($var && $var == $v['pay_id']){
				$string =' checked';
			}else{
				$string ='';
			}
			
			
			$pay_type .= '<label '.$attr.'>';
			$pay_type .='<input name="pay_type" type="radio" value="'.$v['pay_id'].'" '.$string.'> <span class="'.$v['pay_code'].'"><div class="pay_icon"></div><div class="pay_info"><div class="pay_name">'.$v['name'].'</div><div class="pay_desc">'.$v['pay_desc'].'</div></div></span>';
			$pay_type .= '</label>';
		}
		
		$pay_type .= '</div>';
	}
	return $pay_type;
}
/**
 * 通过订单ID抓取用户信息
 * @param unknown_type $trade_sn(流水号)
 */
function get_userinfo_by_sn($trade_sn) {
	$trade_sn = trim($trade_sn);
	$result = M('pay_account')->where(array('trade_sn'=>$trade_sn))->find();
	$status_arr = array('succ','failed','error','timeout','cancel');
	return ($result && !in_array($result['status'],$status_arr)) ? $result : false;
}
/**
 * 更新订单状态
 * @param unknown_type $trade_sn 订单ID
 * @param unknown_type $status 订单状态
 */
function update_recode_status_by_sn($trade_sn,$status) {
	$trade_sn = trim($trade_sn);
	$status = trim(intval($status));
	$data = array();
	$status = return_status($status);
	$data = array('status'=>$status);
	return M('pay_account')->where(array('trade_sn'=>$trade_sn))->save($data);
}

/**
 * 更新用户账户余额
 * @param unknown_type $trade_sn(流水号)
 */
function update_member_behavior($trade_sn) {		
	$data = $userinfo = array();
	$orderinfo = get_userinfo_by_sn($trade_sn);			
	if($orderinfo){			
	$order_type = $orderinfo['type'];
		switch($order_type){
			case 1;
				if($orderinfo['order_type']==1){				
					$event_attend = D('event_attend')->where(array('trade_sn'=>$orderinfo['order_id']))->find();
					if($event_attend['paytype']==0){
						$data['status'] = 21;
						$data['pay_status'] = 2;
					}elseif($event_attend['paytype']==1){
						if($event_attend['leftprice'] == 0){
							$data['status'] = 30;
							$data['pay_status'] = 2;
						}else{
							if($event_attend['pay_status']==0){//未支付
								$data['status'] = 11;
								$data['pay_status'] = 1;
							}elseif($event_attend['pay_status']==1){//定金已支付
								$data['status'] = 30;
								$data['pay_status'] = 2;
							}
						}
					}
					
					if($event_attend['succ_trade_sn']){
						$data['succ_trade_sn'] = $event_attend['succ_trade_sn'].','.$trade_sn;
					}else{
						$data['succ_trade_sn'] = $trade_sn;
					}
					$data['in_trade_sn'] = '';
					
					M('event_attend')->where(array('trade_sn'=>$orderinfo['order_id']))->save($data);//更新活动订单状态
					$event_signer_list['order_status'] = $data['status'];
					D('event_signer')->where(array('siteid'=>$event_attend['siteid'],'order_id'=>$event_attend['id'],'status'=>1))->save($event_signer_list);//更新参加活动者的订单状态与活动订单状态一致
											
					/********更改优惠券状态并写入日志表**********/
					do_update_card($orderinfo['order_id'],$event_attend['siteid']);
					/*2014-11-3 dlx--*/
					$websit_total   = $orderinfo['money'];//得到--money--
					$cash_record    = D('websit_cash_record');  //支付记录
					$cash_record->startTrans();
					
					/*2014-10-27 -dlx--*/
					$webdata['pay_status'] = $data['pay_status'];
					$webdata['status']     = $event_attend['status'];
					$webdata['total']      = $event_attend['payprice'];
					$webdata['leftprice']  = $event_attend['leftprice'];
					$webdata['uid']        = $event_attend['uid'];
					$webdata['time']       = time();
					$webdata['type']       = 1;//类型
					$webdata['siteid']     = $event_attend['siteid'];
					$webdata['order_sn']   = $event_attend['trade_sn'];
					
					$websit_log_add = D('websit_log')->data($webdata)->add();
					
					$websit_cash_find = $cash_record->where(array('siteid'=>$event_attend['siteid']))->find();
					if($websit_cash_find){
						$websitdata['total']    = $websit_cash_find['total']   + $websit_total;//总额
						$websitdata['balance']  = $websit_cash_find['balance'] + $websit_total;//余额
						
						$cash_record_save = $cash_record->where(array('siteid'=>$event_attend['siteid']))->save($websitdata);
	
						 if($cash_record_save && $websit_log_add){

						 		D('RecordContent')->setuseprice_account($trade_sn,$event_attend['siteid']);
							   $cash_record->commit();
						   }else{
						   		D('RecordContent')->setuseprice_account($trade_sn,$event_attend['siteid']);
							   $cash_record->rollback();
						   }
						
					}else{
						$websitdata['total']    =  $websit_total;//总额
						$websitdata['balance']  =  $websit_total;//余额
						$websitdata['siteid']   =  $event_attend['siteid'];
						$websitdata['status']   =  1;
						$cash_record_add = $cash_record->data($websitdata)->add();
					   
						   if($cash_record_add && $websit_log_add){
						   		D('RecordContent')->setuseprice_account($trade_sn,$event_attend['siteid']);
							   $cash_record->commit();
						   }else{
						   		D('RecordContent')->setuseprice_account($trade_sn,$event_attend['siteid']);
							   $cash_record->rollback();
						   }
						
					
					} 
					/************查询用户的用户名**********************/
					$user_id = $event_attend['uid'];
					$user_info = query_user(array('nickname'), $user_id);
					$user_name = $user_info['nickname'];
					/************查询用户的用户名**********************/
					/*************邮件发送*******************************************/
					$webinfo = D('websit')->where("siteid = ".$event_attend['siteid'])->find();
					$event_info = D('event')->where(array('status'=>1,'siteid'=>$event_attend['siteid'],'id'=>$event_attend['event_id']))->find();
					$calendar_info = D('event_calendar_time')->where(array('siteid'=>$event_attend['siteid'],'id'=>$event_attend['calendar_id']))->find();
					$total_member = D('event_signer')->where(array('order_id'=>$event_attend['id'],'siteid'=>$event_attend['siteid']))->count();
					$title = '['.$webinfo['webname'].'] - 支付成功';
					if($orderinfo['website_url']){ 
						$web_url = "http://".$orderinfo['website_url'];
					}else{ 
						$web_url ='http://'.$_SERVER['HTTP_HOST'];
					}
					
					$eventdata= array(
						'user_name'=>$user_name,
						'order_id' =>$orderinfo['order_id'],
						'event_title'=>$event_info['title'],
						'calendar_starttime'=>$calendar_info['starttime'],
						'total_member'=>$total_member,
						'webname'=>$webinfo['webname'],
						'web_slogan'=>$webinfo['slogan'],
						'totalprice'=>$event_attend['totalprice'],
						'deposit'=>$event_attend['deposit'],
						'leftprice'=>$event_attend['leftprice'],
						'web_url'=>$web_url,
						'web_telphone'=>$webinfo['telphone'],
						'title'   =>$title,

					);
					
					if($event_attend['paytype'] == 0){	
						$contactways=array($event_attend['contact_telephone']);
						$eventdata['noticetype']='pay_totalprice';
						$eventdata['contactway_type']='msg';
						D('Message')->addSendMessage('send_sms_to_user',$contactways,$eventdata,0,1);


						
				/*******************短信发送********************************************/
				//sms_alerts($event_attend['contact_telephone'],$msg,'【活动】【全款】支付成功短信提醒');
				/***********************************************************************/
			}else{
				if($event_attend['pay_status'] == 0){
						$contactways=array($event_attend['contact_telephone']);
						$eventdata['noticetype']='pay_deposit';
						$eventdata['contactway_type']='msg';
						D('Message')->addSendMessage('send_sms_to_user',$contactways,$eventdata,0,1);
						
						/*******************短信发送********************************************/
						//sms_alerts($event_attend['contact_telephone'],$msg,'【活动】【定金】支付成功短信提醒');

					/***********************************************************************/
				}elseif($event_attend['pay_status'] == 1){	
								
						$contactways=array($event_attend['contact_telephone']);
						$eventdata['noticetype']='pay_leftprice';
						$eventdata['contactway_type']='msg';
						D('Message')->addSendMessage('send_sms_to_user',$contactways,$eventdata,0,1);
						
						/*******************短信发送********************************************/
						//sms_alerts($event_attend['contact_telephone'],$msg,'【活动】【余额】支付成功短信提醒');

					/***********************************************************************/
				}
			}
					
					//sendMail($event_attend['contact_email'],$title,$message);
					//sendMail($webinfo['email'],$title,$notice);
					$contactways=array($event_attend['contact_email']);
					$eventdata['contactway_type']='message';
					D('Message')->addSendMessage('send_email_to_user',$contactways,$eventdata,0,1);
					$contactways=array($webinfo['email']);
					$eventdata['contactway_type']='notice';
					D('Message')->addSendMessage('send_email_to_user',$contactways,$eventdata,0,1);
					/*******************************************************************/				
				}
			break;
			case 2;
				$shop_order_info = D('shop_ordersn')->where(array('order_sn'=>$orderinfo['order_id']))->find();
				if(!$shop_order_info['supplier_id']){			//查询供货方与销售方，若不同，为分销商品；若相同，为非分销商品；如无供货方信息，也为非分销商品，将供货商与销售方值统一
					$shop_order_info['supplier_id']=$shop_order_info['siteid'];
				}

				$data['status'] = 21;
				$data['pay_status'] = 2;
				$data['trade_sn'] = $trade_sn;						
				M('shop_ordersn')->where(array('order_sn'=>$orderinfo['order_id']))->save($data);
				do_update_shop_card($orderinfo['order_id'],$shop_order_info['siteid']);						
				/*2014-11-3 dlx--*/
				$websit_total   = $orderinfo['money'];//得到--money--
				$cash_record    = D('websit_cash_record');  //支付记录
				$cash_record->startTrans();
				
				/*2014-10-27 -dlx--*/
				$webdata['pay_status'] = $data['pay_status'];
				$webdata['status']     = $shop_order_info['status'];
				$webdata['total']      = $shop_order_info['alltotalprice'];
				$webdata['leftprice']  = 0;
				$webdata['uid']        = $shop_order_info['uid'];
				$webdata['time']       = time();
				$webdata['type']       = 2;//类型
				//$webdata['siteid']     = $shop_order_info['siteid'];
				$webdata['order_sn']   = $shop_order_info['order_sn'];
				
			
				/****************开始区分分销与非分销订单*************************/
				if($shop_order_info['siteid']==$shop_order_info['supplier_id']){	//供货商ID与销售站ID相同，为非分销订单
						$webdata['siteid']     = $shop_order_info['siteid'];	//记录入日志，支付记录划给供货商
						$websit_log_add = D('websit_log')->data($webdata)->add();
					
					$websit_cash_find = $cash_record->where(array('siteid'=>$shop_order_info['siteid']))->find();
					/*****************资金流水账a：供货方入账***********************/
					
					$cash_log_a['siteid']	=	$webdata['siteid'];
					$cash_log_a['total']	=	$websit_total;
					$cash_log_a['order_sn']	=	$orderinfo['order_id'];
					$cash_log_a['from']	=	2;
					$cash_log_a['time']	=	time();
					$cash_log_a['operation_type']	=	"a";
					$cash_log_a['message']		=	"订单支付收入";
					D('websit_cash_log')->add($cash_log_a);
					
					
					if($websit_cash_find){
						$websitdata['total']    = $websit_cash_find['total']   + $websit_total;//总额
						$websitdata['distribute_frozen']  = $websit_cash_find['distribute_frozen'] + $websit_total;//交易冻结金额
						
						$cash_record_save = $cash_record->where(array('siteid'=>$shop_order_info['siteid']))->save($websitdata);

						 if($cash_record_save && $websit_log_add){
								D('RecordContent')->setuseprice_account($trade_sn,$shop_order_info['siteid']);
							   $cash_record->commit();

						   }else{
								D('RecordContent')->setuseprice_account($trade_sn,$shop_order_info['siteid']);
							   $cash_record->rollback();


						   }
						
					}else{
						$websitdata['total']    =  $websit_total;//总额
						$websitdata['distribute_frozen']  =  $websit_total;//交易冻结金额
						$websitdata['siteid']   =  $shop_order_info['siteid'];
						$websitdata['status']   =  1;
						$cash_record_add = $cash_record->data($websitdata)->add();
					   
					   if($cash_record_add && $websit_log_add){
						   D('RecordContent')->setuseprice_account($trade_sn,$shop_order_info['siteid']);
						   $cash_record->commit();

					   }else{
						   D('RecordContent')->setuseprice_account($trade_sn,$shop_order_info['siteid']);
						   $cash_record->rollback();


					   }					
					}  
				}else{	//销售站点与供货站点不同，为分销订单
					$supplier_id	=	$shop_order_info['supplier_id']; //供货商ID
					$seller_id		=	$shop_order_info['siteid']; //分销商ID
					

					$webdata['siteid']     = $shop_order_info['supplier_id'];	//记录入日志，支付记录划给供货商
					$websit_log_add = D('websit_log')->data($webdata)->add();
					
					/*******************计算分销佣金*******************/	

					$seller_total	=	$shop_order_info['seller_price'];

					//$seller_total	该订单分销佣金
					//$commission	活动力平台佣金
					$distribute	=	D('shop_distribute_site_apply')->where('distribute_status=2 and siteid='.$supplier_id)->find();		//供货商申请信息，主要查询平台提成
				
					//$commission		=	$websit_total*$distribute['commission']/100;	//活动力平台佣金
					$commission		=	number_format($websit_total*$distribute['commission']/100, 2, '.', '');	
					$supplier_total	=	$websit_total - $commission - $seller_total;	//计算扣除所有佣金的金额

					/*****************资金流水账a：供货方入账***********************/
					
					$cash_log_a['siteid']	=	$supplier_id;
					$cash_log_a['total']	=	$websit_total;
					$cash_log_a['order_sn']	=	$orderinfo['order_id'];
					$cash_log_a['from']	=	2;
					$cash_log_a['time']	=	time();
					$cash_log_a['operation_type']	=	"a";
					$cash_log_a['message']		=	"订单支付收入";
					D('websit_cash_log')->add($cash_log_a);
					
					/*****************资金流水账b：支付分销佣金***********************/
					
					$cash_log_b['siteid']	=	$supplier_id;
					$cash_log_b['total']	=	$seller_total;	//分销佣金
					$cash_log_b['order_sn']	=	$orderinfo['order_id'];
					$cash_log_b['from']	=	2;
					$cash_log_b['time']	=	time();
					$cash_log_b['operation_type']	=	"b";
					$cash_log_b['message']		=	"分销佣金支付";
					D('websit_cash_log')->add($cash_log_b);
					
					/*****************资金流水c：分销商分销佣金入账***********************/
					
					$cash_log_c['siteid']	=	$seller_id;
					$cash_log_c['total']	=	$seller_total;	//分销佣金
					$cash_log_c['order_sn']	=	$orderinfo['order_id'];
					$cash_log_c['from']	=	2;
					$cash_log_c['time']	=	time();
					$cash_log_c['operation_type']	=	"c";
					$cash_log_c['message']		=	"分销佣金支付";
					D('websit_cash_log')->add($cash_log_c);
					
					/*****************资金流水d：平台佣金支付***********************/
					
					$cash_log_d['siteid']	=	$supplier_id;
					$cash_log_d['total']	=	$commission;	//分销佣金
					$cash_log_d['order_sn']	=	$orderinfo['order_id'];
					$cash_log_d['from']	=	2;
					$cash_log_d['time']	=	time();
					$cash_log_d['operation_type']	=	"d";
					$cash_log_d['message']		=	"平台佣金支付";
					D('websit_cash_log')->add($cash_log_d);
					
					/*****************供货商信息处理***************************/
					
					$supplier_cash_find = $cash_record->where(array('siteid'=>$supplier_id))->find();	//供货商资金总信息
					if($supplier_cash_find){	
						//供货方利润冻结
						$supplier_websitdata['total']    = $supplier_cash_find['total']   + $supplier_total;	//总额
						$supplier_websitdata['distribute_frozen']  = $supplier_cash_find['distribute_frozen'] + $supplier_total;	//冻结分销收益余额
						
						$supplier_cash_record_save = $cash_record->where(array('siteid'=>$supplier_id))->save($supplier_websitdata);	//分配给供货商冻结分销收益余额
						
						 if($supplier_cash_record_save && $websit_log_add){
								D('RecordContent')->setuseprice_account($trade_sn,$supplier_id);
							   $cash_record->commit();

						   }else{
								D('RecordContent')->setuseprice_account($trade_sn,$supplier_id);
							   $cash_record->rollback();


						   }
						   
						
					}else{
						$websitdata['total']    =  $supplier_total;//总额
						$websitdata['balance']  =  $supplier_total;//余额
						$websitdata['siteid']   =  $supplier_id;
						$websitdata['status']   =  1;
						$supplier_cash_record_add = $cash_record->data($websitdata)->add();
					 
					   if($supplier_cash_record_add && $websit_log_add){
						   D('RecordContent')->setuseprice_account($trade_sn,$supplier_id);
						   $cash_record->commit();

					   }else{
						   D('RecordContent')->setuseprice_account($trade_sn,$supplier_id);
						   $cash_record->rollback();
					   }				
					}
					/*****************支付给分销商***************************/
					$seller_cash_find = $cash_record->where(array('siteid'=>$seller_id))->find();	//分销商资金总信息
					if($seller_cash_find){	
						//供货方利润冻结
						$seller_websitdata['total']    = $seller_cash_find['total']   + $seller_total;	//总额
						$seller_websitdata['distribute_frozen']  = $seller_cash_find['distribute_frozen'] + $seller_total;	//冻结分销收益余额
						
						$seller_cash_record_save = $cash_record->where(array('siteid'=>$seller_id))->save($seller_websitdata);	//分配给供货商冻结分销收益余额
						
						 if($seller_cash_record_save && $websit_log_add){
								D('RecordContent')->setuseprice_account($trade_sn,$supplier_id);
							   $cash_record->commit();

						   }else{
								D('RecordContent')->setuseprice_account($trade_sn,$supplier_id);
							   $cash_record->rollback();


						   }
						   
						
					}else{
						$seller_websitdata['total']    =  $supplier_total;//总额
						$seller_websitdata['balance']  =  $supplier_total;//余额
						$seller_websitdata['siteid']   =  $seller_id;
						$seller_websitdata['status']   =  1;
						$supplier_cash_record_add = $cash_record->data($seller_websitdata)->add();
					   
					   if($supplier_cash_record_add && $websit_log_add){
						   D('RecordContent')->setuseprice_account($trade_sn,$supplier_id);
						   $cash_record->commit();

					   }else{
						   D('RecordContent')->setuseprice_account($trade_sn,$supplier_id);
						   $cash_record->rollback();
					   }					
					}
				}
				/********************写入 websit_cash_log******************************/
				//D('websit_cash_log')->add();
				/*********************************************************/
			break;
		}
		
	}
}

/**
 * 通过支付代码获取支付信息
 * @param unknown_type $code
 */
function get_by_code($code) {
	$result = array();
	$code = trim($code);
	$result = M('pay_payment')->where(array('pay_code'=>$code))->find();
	return $result;
}
/**
 * 检测验证码
 * @param  integer $id 验证码ID
 * @return boolean     检测结果
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function check_verify($code, $id = 1){
	$verify = new \Think\Verify();
	return $verify->check($code, $id);
}

/**
 * 获取段落总数
 * @param  string $id 文档ID
 * @return integer    段落总数
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_part_count($id){
    static $count;
    if(!isset($count[$id])){
        $count[$id] = D('Document')->partCount($id);
    }
    return $count[$id];
}


/**
 * 生成系统AUTH_KEY
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function build_auth_key(){
    $chars  = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
   // $chars .= '`~!@#$%^&*()_+-=[]{};:"|,.<>/?';
    $chars  = str_shuffle($chars);
    return substr($chars, 0, 40);
}






//发送手机短信
function send_sms_to_user($param=''){
	$mobile=$param['contactway'];
	$noticetype=$param['noticetype'];
	if(!$param['contactway_type']){
		//不存在或者为0
		$contactway_type='msg';
	}elseif(is_int($param['contactway_type'])){ 
		//为索引数组
		$contactway_type='msg';
	}else{ 
		//为详细值
		$contactway_type=$param['contactway_type'];
	}
	if($fromuid){ 
		$data=D('member')->where('uid = '.$fromuid)->field('nickname')->find();
		$fromuid = $data['nickname'].'发送的消息：';
	}else{ 
		$fromuid= "";
	}
	$message=all_type_send_message('',$noticetype,$param);
	$title=$message[$contactway_type.'_title']?$message[$contactway_type.'_title']:$param['title'];
	$content=$message[$contactway_type]?$message[$contactway_type]:$param['content'];
	$siteid=$param['siteid'];
	$webname=$param['webname'];
	if(!Gcheck_Mobile($mobile)){ 			
		$msg=$fromuid.$content;
		$list=sms_alerts($mobile,$msg,$title,$webname,$siteid);
		if($list['error']==0){
			$returnlist['error']=true;
			$returnlist['content']=$msg; 
			return $returnlist;
		}else{ 
			$returnlist['error']=false;
			$returnlist['content']='消息发送失败';
			return $returnlist;
		}

	}else{ 
		$returnlist['error']=false;
		$returnlist['content']='手机号码格式不正确';
		return $returnlist;
	}
}

//发送邮箱
function send_email_to_user($param=''){ 
	$email=$param['contactway'];
	$noticetype=$param['noticetype'];
	$webname=$param['webname'];
	if(!$param['contactway_type']){
		$contactway_type='msg';
	}elseif(is_int($param['contactway_type'])){ 
		$contactway_type='msg';
	}else{ 
		$contactway_type=$param['contactway_type'];
	}
	if($fromuid){ 
		$data=D('member')->where('uid = '.$fromuid)->field('nickname')->find();
		$fromuid = $data['nickname'].'发送的消息：';
	}else{ 
		$fromuid= "";
	}
	$message=all_type_send_message('',$noticetype,$param);		
	$title=$message[$contactway_type.'_title']?$message[$contactway_type.'_title']:$param['title'];
	$content=$message[$contactway_type]?$message[$contactway_type]:$param['content'];
	if(!Gcheck_Email($email)){ 
		$msg=$fromuid.$content;
		$list=sendMail($email,$title,$msg,$webname);
		if($list){
			$returnlist['error']=true;
			$returnlist['content']=$msg; 
			return $returnlist;
		}else{ 
			$returnlist['error']=false;
			$returnlist['content']='消息发送失败';
			return $returnlist;
		}

	}else{ 
		$returnlist['error']=false;
		$returnlist['content']='邮箱格式不正确';
		return $returnlist;
	}
}

 function update_summary(){
    $map=array('status'=>1);
    $summary_userid=D('mark_summary')->where($map)->getField('userid',true);    //所有排行uid
    foreach ($summary_userid as &$v) {
        $map['userid']=$v;
        $daily=strtotime(date('Y-m-d',time()));
        $map['daka_day']=$daily;
        $map['status']=1;
        $dailydistance=D('mark')->where($map)->getfield('distance',true);
        $summary['dailydistance']= array_sum($dailydistance);       //日汇总
        if(!$summary['dailydistance']){
            $summary['dailydistance'] = 0;
        }
        //周
        $arr=array();
        $arr=getdate();
        $num=$arr['wday'];                                  //当前星期
        if($num == 0){
            $num = 7;
        }
        $start=strtotime(date('Y-m-d',time()))-($num-1)*24*60*60;
        $end=strtotime(date('Y-m-d',time()))+(7-$num)*24*60*60;
        $map['daka_day']=array('between',array($start,$end));
        $weeklydistance=D('mark')->where($map)->getfield('distance',true);
        $summary['weeklydistance']= array_sum($weeklydistance);     //周汇总
        if(! $summary['weeklydistance']){
            $summary['weeklydistance'] = 0;
        }
        //月
        $start=strtotime(date('Y-m-1',time()));
        $end=time();                                        
        $map['daka_day']=array('between',array($start,$end));
        $monthlydistance=D('mark')->where($map)->getfield('distance',true);
        $summary['monthlydistance']= array_sum($monthlydistance);       //月汇总
        if(! $summary['monthlydistance']){
            $summary['monthlydistance'] = 0;
        }
        $summary['update_datetime']= time();                            //更新时间
        $map_mark_summary=array('userid'=>$v,'status'=>1);
        $set_mark_summary=D('mark_summary')->where($map_mark_summary)->save($summary);
    }
    
    if($set_mark_summary){ 
        $returnlist['error']=true;
        $returnlist['content']='排行清除成功'; 
        return $returnlist;
    }else{ 
        $returnlist['error']=false;
        $returnlist['content']='排行清除失败'; 

        return $returnlist;
    }
}



   
//商城订单过期修改
  	function shop_order_update($param){ 
		$order_sn=$param['shop_order_sn'];
		$siteid  =$param['siteid'];
		$map=array('siteid'=>$siteid,'order_sn'=>$order_sn);
		$shop_order_arr=D('shop_ordersn')->where($map)->find();
		if($shop_order_arr['status']==20){
			$data['status']=2 ;
			$save_shop_order=D('shop_ordersn')->where("id = ".$shop_order_arr['id'])->save($data);
			$goods_list=D('shop_order_info')->where(array('order_sn'=>$order_sn,'siteid'=>$siteid))->select();
			foreach ($goods_list as $k => $v) {
				D('shop')->where(array('id'=>$v['goods_id'],'siteid'=>$siteid))->setDec('sell_num',$v['goods_num']);
				D('shop')->where(array('id'=>$v['goods_id'],'siteid'=>$siteid))->setInc('goods_num',$v['goods_num']);
				if(!empty($v['sku_id'])){
					D('shop_sku_detailed')->where(array('sku_id'=>$v['sku_id'],'goods_id'=>$v['goods_id'],'siteid'=>$siteid))->setInc('stock',$v['goods_num']);
				}	
			}
			
			
			if($save_shop_order){
			
				$returnlist['error']=true;
				$returnlist['content']='商品订单关闭成功';
			
			}else{
			
				$returnlist['error']=false;
				$returnlist['content']='商品订单更新失败，等待排队';
			}	
			
		
		}else{ 
			$returnlist['error']=true;
			$returnlist['content']='商品订单已更改';

		}
		return $returnlist;

	}
	/*
	*活动下订单倒计时控制
	*$trade_sn 订单号
	*/
	function event_order_countdown($trade_sn){ 
		$map['trade_sn'] = $trade_sn;
		$map['status'] = array('in','10,20');
		$map['siteid'] = SITEID;
		$event_attend = D('event_attend')->field('id,calendar_id,event_id,overdue_time')->where($map)->find();
		$overdue = $event_attend['overdue_time'];
		$time = time();
		if($event_attend == ''){ 
			return -2;
		}
		if($time < $overdue){ 
			$timeover = $overdue-$time ;
			return  $timeover ;
		}else{ 

			$data['status'] = 0;
			updata_evevt_status($event_attend['id'],$data['status']); 
			$event_attend = D('event_attend')->where($map)->save($data);
			return -1;
		}

	}

	/*
	*活动下订单倒计时控制
	*$param array 订单号 siteid
	*/
	function event_order_countdown_update($param){ 
		$map['trade_sn'] = $param['event_order_sn'];
		$map['status'] = array('in','10,20');
		$map['siteid'] = $param['siteid'];
		$event_attend_arr = D('event_attend')->field('id,status')->where($map)->find();
		if($event_attend_arr){ 
			$overdue = $event_attend_arr['overdue_time'];
			$time = time();
			if($time > $overdue){ 
				$data['status'] = 0; 
				$msg=updata_evevt_status($event_attend_arr['id'],$data['status'],$map['siteid']);
				if($msg['s']==1){
					$returnlist['error']=true;
					$returnlist['content']='活动订单更改成功';

				}else{ 
					$returnlist['error']=false;
					$returnlist['content']='活动订单更改失败，等待排队';
				}
			
			}else{ 

				$returnlist['error']=false;
				$returnlist['content']='活动订单无需更改，等待排队';
			}

		}else{ 

			$returnlist['error']=true;
			$returnlist['content']='活动订单已更改';
		}
		return $returnlist;
	}

    //七天定时更改订单状态
function auto_change_shop_status($param){ 
    $shop_order_list=D('shop_ordersn')->where(array('id'=>$param['id'],'order_sn'=>$param['shop_order_sn']))->find();
    if($shop_order_list && $shop_order_list['status']==$param['change_status']){ 
        $result=update_shop_order_status($param['id'],$param['change_status'],$shop_order_list['siteid']);
        if($result['s']==1){ 
            $returnlist['error']=true;
            $returnlist['content']='订单状态更改成功'; 
            return $returnlist;
        }else{ 
            $returnlist['error']=false;
            $returnlist['content']=$result['m']; 
            return $returnlist;
        }
    }else{
        $returnlist['error']=false;
        $returnlist['content']='订单不存在，或订单状态已更改'; 
        return $returnlist;   
    }
}

//七天
  	//发送信息
	function all_type_send_message($aaaa='',$noticetype='',$data=''){

    	switch($noticetype){ 
    		//全款支付成功
    		case'pay_totalprice':
    			if($data['web_slogan'] !=''){ 
    				$data['web_slogan']="【".$data['web_slogan']."】";
    			}
				 $mess['message']="亲爱的".$data['user_name']."，您的活动订单支付成功通知：
				【全款支付】".$data['totalprice']."
				【订 单 号】".$data['order_id']."
				【活动名称】".$data['event_title']."
				【出发日期】".$data['calendar_starttime']."
				【报名人数】".$data['total_member']."人
				【咨询热线】".$data['web_telphone']."
				 相信我们会带给您不一样的旅行体验！
				【".$data['webname']."】".$data['web_slogan'].$data['web_url'];
				 $mess['notice']="恭喜【".$data['webname']."】领队大大，活动订单成功支付喜报传来：
				【全款支付】".$data['totalprice']."
				【活动名称】".$data['event_title']."
				【订 单 号】".$data['order_id']."
				【出发日期】".$data['calendar_starttime']."
				【报名人数】".$data['total_member']."人";
				$mess['msg']="您报名的".$data['webname']."的".$data['event_title']."，已全额支付成功，订单号：".$data['order_id']."，热线电话：".$data['web_telphone'];
				$mess['msg_title']="【活动】【全额】支付成功短信提醒 ";
				$mess['message_title']="[".$data['webname']."] - 支付成功";
				$mess['notice_title']="[".$data['webname']."] - 支付成功";
			return $mess;
    		break;
    		//定金支付
    		case 'pay_deposit':
    			if($data['web_slogan'] !=''){ 
    				$data['web_slogan']="【".$data['web_slogan']."】";
    			}

    		$mess['message']="亲爱的".$data['user_name']."，您的活动订单支付成功通知：
				【定金支付】".$data['deposit']."
				【订 单 号】".$data['order_id']."
				【活动名称】".$data['event_title']."
				【出发日期】".$data['calendar_starttime']."
				【报名人数】".$data['total_member']."人
				【咨询热线】".$data['web_telphone']."
				 相信我们会带给您不一样的旅行体验！
				【".$data['webname']."】".$data['web_slogan'].$data['web_url'];
				 $mess['notice']="恭喜【".$data['webname']."】领队大大，活动订单成功支付喜报传来：
				【定金支付】".$data['deposit']."
				【活动名称】".$data['event_title']."
				【订 单 号】".$data['order_id']."
				【出发日期】".$data['calendar_starttime']."
				【报名人数】".$data['total_member']."人";
				$mess['msg']="您报名的".$data['webname']."的".$data['event_title']."，已定金支付成功，订单号：".$data['order_id']."，热线电话：".$data['web_telphone'];
				$mess['msg_title']="【活动】【定金】支付成功短信提醒 ";
				$mess['message_title']="[".$data['webname']."] - 支付成功";
				$mess['notice_title']="[".$data['webname']."] - 支付成功";
			return $mess;

    		break;
    		//余额支付
    		case 'pay_leftprice':
    			if($data['web_slogan'] !=''){ 
    				$data['web_slogan']="【".$data['web_slogan']."】";
    			}
    			 $mess['message']="亲爱的".$data['user_name']."，您的活动订单支付成功通知：
				【余额支付】".$data['leftprice']."
				【订 单 号】".$data['order_id']."
				【活动名称】".$data['event_title']."
				【出发日期】".$data['calendar_starttime']."
				【报名人数】".$data['total_member']."人
				【咨询热线】".$data['web_telphone']."
				 相信我们会带给您不一样的旅行体验！
				【".$data['webname']."】".$data['web_slogan'].$data['web_url'];

				 $mess['notice']="恭喜【".$data['webname']."】领队大大，活动订单成功支付喜报传来：
				【余额支付】".$data['leftprice']."
				【活动名称】".$data['event_title']."
				【订 单 号】".$data['order_id']."
				【出发日期】".$data['calendar_starttime']."
				【报名人数】".$data['total_member']."人";
				$mess['msg']="您报名的".$data['webname']."的".$data['event_title']."，已余额支付成功，订单号：".$data['order_id']."，热线电话：".$data['web_telphone'];
				$mess['msg_title']="【活动】【余额】支付成功短信提醒 ";
				$mess['message_title']="[".$data['webname']."] - 支付成功";
				$mess['notice_title']="[".$data['webname']."] - 支付成功";
			return $mess;
			//下单成功信息内容
    		break;
    		case 'order_message':
    			if($data['web_slogan'] !=''){ 
    				$data['web_slogan']="【".$data['web_slogan']."】";
    			}
    			 $message['msg'] ="亲爱的".$data['user_name']."，您成功下单的活动通知：
				【活动名称】".$data['event_title']."
				【订 单 号】".$data['trade_sn']."
				【出发日期】".$data['calendar_starttime']."
				【报名人数】".$data['total_member']."人
				 相信我们会带给您不一样的旅行体验！
				【".$data['webname']."】".$data['web_slogan'].$data['web_url'];

				return $message;
				
    		break;
    		//私家定制发送的信息
    		case 'custom_message'	:
				 $message['msg']="恭喜".$data['webname']."领队大大，私家定制活动喜报传来：
				【联 系 人】".$data['contact_name']."
				【联系方式】".$data['contact_telephone']."
				【电子邮箱】".$data['contact_email']."
				【提交时间】".$data['createtime']."
				【定制单号】".$data['trade_sn']."
				 这可是送上门的单子，请速速处理！";
    			$message['msg_title']=$data['webname']."-私家定制通知";
    			return $message;

    		break;
    		//派发优惠券的信息
    		case 'distribute_coupons':
    			$message['msg'] = "恭喜您收到来自[".$data['webname']."]的代金券，券码为[".$data['cardid']."]，请尽快登录官网。".$data['web_url']."领取  ";
    			$message['msg_title']='派发优惠卷';
    			return $message;

    		break;
    		//确认出行的信息
    		case 'confirmed_travel':
    			if($data['web_slogan'] !=''){ 
    				$data['web_slogan']="【".$data['web_slogan']."】";
    			}
				$message['msg'] = "亲爱的".$data['user_name']."，您的活动确认出行通知：
				【订 单 号】".$data['trade_sn']."
				【活动名称】".$data['event_title']."
				【出发日期】".$data['calendar_starttime']."
				【报名人数】".$data['total_member']."人
				【咨询热线】".$data['web_telphone']."
				 相信我们会带给您不一样的旅行体验！
				【".$data['webname']."】".$data['web_slogan'].$data['web_url'];
				$message['msg_title'] = "[".$data['webname']."] - 确认出行";

				
				return $message;

    		break;
    		case 'quicklog':
    			$message['msg'] = "您已成功注册为[".$data['webname']."]的会员,账号名为 :".$data['nickname'].",默认密码为 :".$data['password'];
    			$message['msg_title']='快速注册';
    			return $message;	
    		

    		break;

    	}
    	
		
	}
/* 
	*cardid传入的优惠劵码
	用来直接输入优惠券给用户绑定

*/
	function checked_unified_pointcard_blind($cardid){ 
		$unified_info=D('pointcard_unified')->where("unifiedcardid = '{$cardid}' and siteid = ".SITEID)->find();
		if($unified_info){ 
			$havemap=array('unifiedcardid'=>$cardid,'userid'=>is_login(),'siteid'=>SITEID);
			$havelist=D('pointcard')->where($havemap)->find();
			if($havelist){ 
				$card_info=$havelist;
				$card_info['error']=true;
			}else{ 
				$unlist['leftnum']=$unified_info['leftnum']-1;
				if($unlist['leftnum']<0){ 
					$card_info['error']=false;
					$card_info['mag']="该券已被抢光！ ";
				}else{ 

					$point_unified_save=D('pointcard_unified')->where("id = ".$unified_info['id'])->save($unlist);
					if($point_unified_save){ 
						$card_no_info=D('pointcard')->where(array('unifiedcardid'=>$cardid,'siteid'=>SITEID,'userid'=>0))->find();
						D('pointcard')->startTrans();
						$point_save=D('pointcard')->where("id = ".$card_no_info['id'])->save(array('userid'=>is_login()));
						$point_arr	= array(
									   'bindtime'  => time(),
									   'cardid' => $card_no_info['cardid'],
									   'siteid' => SITEID,
									   'userid'	=> is_login()						
								);
						$bindcarduser	=	D('pointcard_user')->data($point_arr)->add();
						if($point_save && $bindcarduser){
							D('pointcard')->commit();
							
						 	$card_info=$card_no_info;
						 	$card_info['error']=true;
						}else{ 
							D('pointcard')->rollback();
							D('pointcard_unified')->where(array('unifiedcardid'=>$cardid,'siteid'=>SITEID))->setInc('leftnum',1);
							$card_info['error']=false;
							$card_info['msg']="亲，该优惠劵不存在或已取消,请再次确认号码!";
						}
					}else{ 
						$card_info['error']=false;
						$card_info['msg']="亲，该优惠劵不存在或已取消,请再次确认号码!";
					}


				}
				
			}
		}else{ 
			
			$card_info = D('pointcard')->where('cardid="'.$cardid.'" and siteid='.$siteid)->find();
			$card_info['error']=true;
		}

		return $card_info;
	}






 /*
	*param写入的名称
	*data 传入的数组
    *deploy 数据库字段
	** 2015-3-3 dlx pm
	**/
	function addWebsitConfig($data,$param,$deploy='config'){
        $websitconfig_list = D('websit')->where(array('siteid'=>SITEID))->getField($deploy);	
		if(!empty($websitconfig_list)){
			$new_websitconfig_list = string2array($websitconfig_list);
			foreach($new_websitconfig_list as $k=>$v){
				$list[$k] = $new_websitconfig_list[$k];
			}
		}
		
		$list[$param]=$data;
		$data=array2string($list);
		$rs = D('websit')->where(array('siteid'=>SITEID))->setField($deploy,$data);
		if($rs){
           $domain = $_SERVER['HTTP_HOST'];
            clean_website_info_cash($domain); 
		   return true;
		}else{
		   return false;	
		}
	}
    /*
	*得到配置数据*** 2015-3-3 dlx pm
	*/
    function getWebsitConfig($param,$type=1,$deploy='config'){
		$websitconfig_list = D('websit')->where(array('siteid'=>SITEID))->getField($deploy);	
        $new_websitconfig_list = string2array($websitconfig_list);
		$list = $new_websitconfig_list[$param];
		switch($type){
			case 1:
				if(!empty($list)){
					return implode(',',$list);
				}else{
					return false;	
				}
			break;
			case 2:
				if(isset($list)){
					return $list;
				}else{
					return false;	
				}
			break;
		}
		
	}



/*优惠券数据处理 显示类型*/
 function cardinfo_num($data){ 
    	$cardid_num =explode(',', $data);
		foreach ($cardid_num as $key => $value) {
			$cardid_num_con[] = D('pointcard')->field('amount')->where(array('cardid'=>$value,'siteid'=>SITEID))->find();
		}	
		foreach($cardid_num_con as $value){ 
			$card_amount[] =  $value['amount'];
		}
		$cardid_num_con = array_count_values($card_amount);

		return $cardid_num_con;
    }
/*微信公众号的类型
	$type 类型
*/
function get_weixin_public_type($type){ 
	switch ($type) {
		case 0:
			$result="普通订阅号";
			break;
		case 1:
			$result="认证订阅号/普通服务号";
			break;
		case 2:
			$result="认证服务号";
			break;
		default:
			$result="错误信息";
			break;
	}
	return $result;

     }
/**
 * 导航
 */
    function navigation($limit=10){

      $map2  = array('status' => array('eq', 1),'siteid' => SITEID,'pid'=>0);
      $is_distribute_category = D('shop_category')->where($map2)->order('sort desc')->limit($limit)->select();
      foreach($is_distribute_category as $k=>$v){
        $map3  = array('status' => array('eq', 1),'siteid' => SITEID,'pid'=>$v['id']);
        $is_distribute_category[$k]['category_2nd'] = D('shop_category')->where($map3)->select();
      }

        return $is_distribute_category;
 
}

/**
 *头部 购物车
 */
 function cart(){
    if(is_login()){
            $cart_num = D('shop_cart')->where(array('uid' => is_login()))->sum('num');
        }else{
            $arr=$_SESSION['cart'];
                    foreach($arr as $val){
                        $cart_num=$cart_num+$val['goods_num'];
                    }
        }
        if(!$cart_num){
            $cart_num=0;
        }
        return $cart_num;
 }

    //快速导航栏设置 
    function fast_nav_settings(){ 
        $str_arr=array(
            1=>'最新单品',
            2=>'特价商品',
            3=>'热卖单品',
            4=>'订单查询',
            5=>'长假活动',
            6=>'附近活动',
            7=>'活动日历',
            8=>'最新发布',
         );
        return $str_arr;
    }
    //首页模块展示
    function mobile_module_display(){ 
        $str_arr=array(
            1=>'限时抢购',
            2=>'精品推荐',
            3=>'马上出发',
            4=>'活动天数',
            5=>'私人定制',
            6=>'最新故事',
            7=>'约伴活动',
            8=>'最新公告',
        );
        return $str_arr;

    }
    function get_mobile_fast_nav($param){ 
        $str=getWebsitConfig($param,2,'mobile_config');
        foreach ($str as $key => $val) {
            $fast_nav[$val]=true;
        }
        return $fast_nav;
    }
    function get_mobile_module($param){ 
        $str=getWebsitConfig($param,2,'mobile_config');
        if(!$str){ 
            $mobile_module[3]=true;
            $mobile_module[4]=true;
            $mobile_module[5]=true;
            $mobile_module[6]=true;
            $mobile_module[8]=true;
        }else{ 
            foreach ($str as $key => $val) {
                $mobile_module[$val]=true;
            }
        }
        return $mobile_module;

    }
 
 //URL安全的字符串编码：
	function urlsafe_b64encode($string) {
	   $data = base64_encode($string);
	   $data = str_replace(array('+','/','='),array('-','_',''),$data);
	   return $data;
	}
	//URL安全的字符串解码： 
	function urlsafe_b64decode($string) {
	   $data = str_replace(array('-','_'),array('+','/'),$string);
	   $mod4 = strlen($data) % 4;
	   if ($mod4) {
		   $data .= substr('====', $mod4);
	   }
	   return base64_decode($data);
	}

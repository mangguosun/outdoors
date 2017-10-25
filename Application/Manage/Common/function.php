<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * 后台公共文件
 * 主要定义后台公共函数库
 */

/* 解析列表定义规则*/

function get_list_field($data, $grid,$model){

	// 获取当前字段数据
    foreach($grid['field'] as $field){
        $array  =   explode('|',$field);
        $temp  =	$data[$array[0]];
        // 函数支持
        if(isset($array[1])){
            $temp = call_user_func($array[1], $temp);
        }
        $data2[$array[0]]    =   $temp;
    }
    if(!empty($grid['format'])){
        $value  =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data2){return $data2[$match[1]];}, $grid['format']);
    }else{
        $value  =   implode(' ',$data2);
    }

	// 链接支持
	if(!empty($grid['href'])){
		$links  =   explode(',',$grid['href']);
        foreach($links as $link){
            $array  =   explode('|',$link);
            $href   =   $array[0];
            if(preg_match('/^\[([a-z_]+)\]$/',$href,$matches)){
                $val[]  =   $data2[$matches[1]];
            }else{
                $show   =   isset($array[1])?$array[1]:$value;
                // 替换系统特殊字符串
                $href	=	str_replace(
                    array('[DELETE]','[EDIT]','[MODEL]'),
                    array('del?ids=[id]&model=[MODEL]','edit?id=[id]&model=[MODEL]',$model['id']),
                    $href);

                // 替换数据变量
                $href	=	preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];}, $href);

                $val[]	=	'<a href="'.U($href).'">'.$show.'</a>';
            }
        }
        $value  =   implode(' ',$val);
	}
    return $value;
}

// 获取模型名称
function get_model_by_id($id){
    return $model = M('Model')->getFieldById($id,'title');
}

// 获取属性类型信息
function get_attribute_type($type=''){
    // TODO 可以加入系统配置
    static $_type = array(
        'num'       =>  array('数字','int(10) UNSIGNED NOT NULL'),
        'string'    =>  array('字符串','varchar(255) NOT NULL'),
        'textarea'  =>  array('文本框','text NOT NULL'),
        'datetime'  =>  array('时间','int(10) NOT NULL'),
        'bool'      =>  array('布尔','tinyint(2) NOT NULL'),
        'select'    =>  array('枚举','char(50) NOT NULL'),
    	'radio'		=>	array('单选','char(10) NOT NULL'),
    	'checkbox'	=>	array('多选','varchar(100) NOT NULL'),
    	'editor'    =>  array('编辑器','text NOT NULL'),
    	'picture'   =>  array('上传图片','int(10) UNSIGNED NOT NULL'),
    	'file'    	=>  array('上传附件','int(10) UNSIGNED NOT NULL'),
    );
    return $type?$_type[$type][0]:$_type;
}

/**
 * 获取对应状态的文字信息
 * @param int $status
 * @return string 状态文字 ，false 未获取到
 * @author huajie <banhuajie@163.com>
 */
function get_status_title($status = null){
    if(!isset($status)){
        return false;
    }
    switch ($status){
        case -1 : return    '已删除';   break;
        case 0  : return    '禁用';     break;
        case 1  : return    '正常';     break;
        case 2  : return    '待审核';   break;
        default : return    false;      break;
    }
}

// 获取数据的状态操作
function show_status_op($status) {
    switch ($status){
        case 0  : return    '启用';     break;
        case 1  : return    '禁用';     break;
        case 2  : return    '审核';		break;
        default : return    false;      break;
    }
}

/**
 * 获取文档的类型文字
 * @param string $type
 * @return string 状态文字 ，false 未获取到
 * @author huajie <banhuajie@163.com>
 */
function get_document_type($type = null){
    if(!isset($type)){
        return false;
    }
    switch ($type){
        case 1  : return    '目录'; break;
        case 2  : return    '主题'; break;
        case 3  : return    '段落'; break;
        default : return    false;  break;
    }
}

/**
 * 获取配置的类型
 * @param string $type 配置类型
 * @return string
 */
function get_config_type($type=0){
    $list = C('CONFIG_TYPE_LIST');
    return $list[$type];
}

/**
 * 获取配置的分组
 * @param string $group 配置分组
 * @return string
 */
function get_config_group($group=0){
    $list = C('CONFIG_GROUP_LIST');
    return $group?$list[$group]:'';
}

/**
 * select返回的数组进行整数映射转换
 *
 * @param array $map  映射关系二维数组  array(
 *                                          '字段名1'=>array(映射关系数组),
 *                                          '字段名2'=>array(映射关系数组),
 *                                           ......
 *                                       )
 * @author 朱亚杰 <zhuyajie@topthink.net>
 * @return array
 *
 *  array(
 *      array('id'=>1,'title'=>'标题','status'=>'1','status_text'=>'正常')
 *      ....
 *  )
 *
 */
function int_to_string(&$data,$map=array('status'=>array(1=>'正常',-1=>'删除',0=>'禁用',2=>'未审核',3=>'草稿'))) {
    if($data === false || $data === null ){
        return $data;
    }
    $data = (array)$data;
    foreach ($data as $key => $row){
        foreach ($map as $col=>$pair){
            if(isset($row[$col]) && isset($pair[$row[$col]])){
                $data[$key][$col.'_text'] = $pair[$row[$col]];
            }
        }
    }
    return $data;
}

/**
 * 动态扩展左侧菜单,base.html里用到
 * @author 朱亚杰 <zhuyajie@topthink.net>
 */
function extra_menu($extra_menu,&$base_menu){
    foreach ($extra_menu as $key=>$group){
        if( isset($base_menu['child'][$key]) ){
            $base_menu['child'][$key] = array_merge( $base_menu['child'][$key], $group);
        }else{
            $base_menu['child'][$key] = $group;
        }
    }
}

/**
 * 获取参数的所有父级分类
 * @param int $cid 分类id
 * @return array 参数分类和父类的信息集合
 * @author huajie <banhuajie@163.com>
 */
function get_parent_category($cid){
    if(empty($cid)){
        return false;
    }
    $cates  =   M('Category')->where(array('status'=>1))->field('id,title,pid')->order('sort')->select();
    $child  =   get_category($cid);	//获取参数分类的信息
    $pid    =   $child['pid'];
    $temp   =   array();
    $res[]  =   $child;
    while(true){
        foreach ($cates as $key=>$cate){
            if($cate['id'] == $pid){
                $pid = $cate['pid'];
                array_unshift($res, $cate);	//将父分类插入到数组第一个元素前
            }
        }
        if($pid == 0){
            break;
        }
    }
    return $res;
}



/**
 * 获取当前分类的文档类型
 * @param int $id
 * @return array 文档类型数组
 * @author huajie <banhuajie@163.com>
 */
function get_type_bycate($id = null){
    if(empty($id)){
        return false;
    }
    $type_list  =   C('DOCUMENT_MODEL_TYPE');
    $model_type =   M('Category')->getFieldById($id, 'type');
    $model_type =   explode(',', $model_type);
    foreach ($type_list as $key=>$value){
        if(!in_array($key, $model_type)){
            unset($type_list[$key]);
        }
    }
    return $type_list;
}

/**
 * 获取当前文档的分类
 * @param int $id
 * @return array 文档类型数组
 * @author huajie <banhuajie@163.com>
 */
function get_cate($cate_id = null){
    if(empty($cate_id)){
        return false;
    }
    $cate   =   M('Category')->where('id='.$cate_id)->getField('title');
    return $cate;
}

 // 分析枚举类型配置值 格式 a:名称1,b:名称2
function parse_config_attr($string) {
    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
    if(strpos($string,':')){
        $value  =   array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k]   = $v;
        }
    }else{
        $value  =   $array;
    }
    return $value;
}

// 获取子文档数目
function get_subdocument_count($id=0){
    return  M('Document')->where('pid='.$id)->count();
}



 // 分析枚举类型字段值 格式 a:名称1,b:名称2
 // 暂时和 parse_config_attr功能相同
 // 但请不要互相使用，后期会调整
function parse_field_attr($string) {
    if(0 === strpos($string,':')){
        // 采用函数定义
        return   eval(substr($string,1).';');
    }
    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
    if(strpos($string,':')){
        $value  =   array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k]   = $v;
        }
    }else{
        $value  =   $array;
    }
    return $value;
}

/**
 * 获取行为数据
 * @param string $id 行为id
 * @param string $field 需要获取的字段
 * @author huajie <banhuajie@163.com>
 */
function get_action($id = null, $field = null){
	if(empty($id) && !is_numeric($id)){
		return false;
	}
	$list = S('action_list');
	if(empty($list[$id])){
		$map = array('status'=>array('gt', -1), 'id'=>$id);
		$list[$id] = M('Action')->where($map)->field(true)->find();
	}
	return empty($field) ? $list[$id] : $list[$id][$field];
}

/**
 * 根据条件字段获取数据
 * @param mixed $value 条件，可用常量或者数组
 * @param string $condition 条件字段
 * @param string $field 需要返回的字段，不传则返回整个数据
 * @author huajie <banhuajie@163.com>
 */
function get_document_field($value = null, $condition = 'id', $field = null){
	if(empty($value)){
		return false;
	}

	//拼接参数
	$map[$condition] = $value;
	$info = M('Model')->where($map);
	if(empty($field)){
		$info = $info->field(true)->find();
	}else{
		$info = $info->getField($field);
	}
	return $info;
}

/**
 * 获取行为类型
 * @param intger $type 类型
 * @param bool $all 是否返回全部类型
 * @author huajie <banhuajie@163.com>
 */
function get_action_type($type, $all = false){
	$list = array(
		1=>'系统',
		2=>'用户',
	);
	if($all){
		return $list;
	}
	return $list[$type];
}
/*
*验证url
**/
function checked_url($url){
	$myArray=explode("://",$url,2);
    if($myArray[0]=="http"||$myArray[0]=="https"){
		$url;
	}else{
		$url = "http://".$url;
	}
		
	if(!preg_match('/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',$url)){
		return true;
	}else{
	    return false;	
	}
	
}

/*得到新的url**/
function new_url($url){
	$myArray=explode("://",$url,2);
		if($myArray[0]=="http"||$myArray[0]=="https"){
			return $url;
		}else{
			$url = "http://".$url;
			
		}
	return $url;

}
//---得到商品分类---
function get_shop_categrory_title($id){
	$list = D('shop_category')->where("id=".$id)->find();
	if($list){
		return $list['title'];
	}else{
		return '暂无分类';
	}
}

///---商城移入----


function shop_cate_info($id, $field = true){
        /* 获取分类信息 */
        $map = array();
        if(is_numeric($id)){ //通过ID查询
            $map['id'] = $id;
        } else { //通过标识查询
            $map['name'] = $id;
        }
        return D('shop_category')->field($field)->where($map)->find();
}

function get_shop_cate_tree($id = 0, $field = true){
		if($id){     /* 获取当前分类信息 */
		$info = shop_cate_info($id);
		$id   = $info['id'];
		}

		/* 获取所有分类 */
		$map  = array('status' => array('gt', -1),'siteid' => SITEID);
		$list = D('shop_category')->field($field)->where($map)->order('sort')->select();
		$list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_', $root = $id);
		/* 获取返回数据 */
		if(isset($info)){ //指定分类则返回当前分类极其子分类
		$info['_'] = $list;
		} else { //否则返回所有分类
		$info = $list;
		}

		return $info;
}




/**
*得到商品类目2014-12-27
*/
function get_shop_sku_category($find=''){
    $map['status']=1;
	$sku_cate=D('shop_sku_category')->where($map)->select();	
	$str_arr=array();
	if($sku_cate){
		foreach($sku_cate as $val){
			$str_arr[$val['sku_category_id']]=$val['title'];
			
		}
	}
    if($find){
		return $str_arr[$find];
		
	}else{
		return $str_arr;
	}
	
}

/*
*得到商品分类*** 2014-12-27 am dlx
*/
function get_shop_category($find=''){
	$tree =get_shop_cate_tree(0, 'id,title,sort,pid,status');
	$array_info=array();
	if($tree){
		foreach($tree as $value){
		    $array_info[$value['id']]  =  $value['title']; //---一级分类---
			if($value[_]){
				foreach($value[_] as $val){
				   $array_info[$val['id']] = "&nbsp;&nbsp;&nbsp;&nbsp;".$val['title'];  //---二级分类---
					if($val[_]){
						foreach($val[_] as $v){
						  $array_info[$v['id']] ="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$v['title'];  //--三级分类---
						   
						}
					}
				}
				
			}
		
		}
		
	}
	if($find){
		return $array_info[$find];
	}else{
		return $array_info;
	}
}
/*
*得到商品分类名称***2014-12-29 dlx am
**/
function get_shop_categrory_names($id){
	$list = D('shop_category')->where("id=".$id)->find();
	if($list){
		return $list['title'];
	}else{
		return '暂无分类';
	}
}
/**
*选择品牌
**/
function get_shop_brand($find='',$select="all"){
	switch($select){
	    case 'all':
		  $list = D('shop_brand_manage')->select();
		break;
		case 'selected':
		   $brand_manage = getWebsitConfig('shop_brand',1);
		   $list = D('shop_brand_manage')->where(array('id'=>array('in',$brand_manage)))->select();
		break;
	}
	
	
	if($list){
		foreach($list as $key=>$val){
			$str_arr[$list[$key]['id']]=$list[$key]['name'];
		}
	}
	
	if($find){
            return $str_arr[$find];
		}else{
			return $str_arr;
			
		}
	
	
}
/*验证三级分类*/
function get_category_level($pid){
	$list = get_shop_cate_tree(0,true);
	foreach($list as $val){    //-一级--
		foreach($val[_] as $v){  // --二级--
			foreach($v[_] as $temp){ //--三级--
				if($temp['id'] == $pid){
				   return true;
				}
			}
		}

	}
	
}

function return_order_status($status){
	$order_status_arr = array();
	$order_status_arr = array(
		array('status'=>1,'text'=>'报名预约，等待空位'),
		array('status'=>10,'text'=>'下单成功，待定金支付'),
		array('status'=>11,'text'=>'定金已支付，待确认出行'),
		array('status'=>12,'text'=>'已确认出行，待余款支付'),
		array('status'=>20,'text'=>'下单成功，待全款支付'),
		array('status'=>21,'text'=>'支付成功，待确认出行'),
		array('status'=>30,'text'=>'待出行签到'),
		array('status'=>31,'text'=>'活动进行中'),
		array('status'=>32,'text'=>'活动结束，等待评论'),
		array('status'=>33,'text'=>'订单完成'),
		array('status'=>60,'text'=>'退款申请中'),
		array('status'=>61,'text'=>'退款完成')
	);
	$order_status_temp = array();
	foreach($order_status_arr as $key => $val){
		$order_status_temp[$val['status']] = $val['text'];
	}
	if($status){
		return $order_status_temp[$status];
	}else{
		return $order_status_arr;
	}
}

/**
 * 分类添加层级
 * @staticvar array $tree
 * @param type $list
 * @param type $parent_id
 * @param type $level
 * @return type
 */
function getTree($list,$pid,$level=0){
    static $tree=array();
    foreach ($list as $row){
        if($row['pid']==$pid){
            $row['level']=$level;
            $tree[]=$row;
            getTree($list, $row['id'],$level+1);
        }
    }
    return $tree;
}

function get_token($token = NULL) {
	if ($token !== NULL) {
		session ( 'token', $token );
	} elseif (! empty ( $_REQUEST ['token'] )) {
		session ( 'token', $_REQUEST ['token'] );
	}
	$token = session ( 'token' );
	
	if (empty ( $token )) {
		return - 1;
	}
	
	return $token;
}
function get_menu_first($token=''){ 
	empty ( $token ) && $token = get_token ();
	$map ['token'] = $token;
	//var_dump($map['token']);
	$map['pid'] =0;
	$map['type']="none";
	$map['status']=1;
	$list=D('weixin_custom_menu')->where($map)->select();
	$title=array();
	$title[0]="顶级菜单";
	foreach ($list as $k => $v) {
		$title[$v['id']]=$v['title'];
	}

	return $title;
}

function get_weixin_type_button2($find='',$type='')
	{
		$string_arr=array(
				//'1'=>'点击推事件',
				'2'=>'跳转URL',
				'3'=>'扫码推事件',
				//'4'=>'扫码推事件且弹出“消息接收中”提示框',
				'5'=>'弹出系统拍照发图',
				'6'=>'弹出拍照或者相册发图',
				'7'=>'弹出微信相册发图器',
				'8'=>'弹出地理位置选择器',
		);
		if($type==1){ 
			$string_arr[0]='无事件顶级菜单';
		}
		if($find){
			return $string_arr[$find];
		}else{
			return $string_arr;
		}

	}

function get_weixin_type_button($find='',$type='')
	{
		$string_arr=array(
				//'click'=>'点击推事件',
				'view'=>'跳转URL',
				'scancode_push'=>'扫码推事件',
				//'scancode_waitmsg'=>'扫码推事件且弹出“消息接收中”提示框',
				'pic_sysphoto'=>'弹出系统拍照发图',
				'pic_photo_or_album'=>'弹出拍照或者相册发图',
				'pic_weixin'=>'弹出微信相册发图器',
				'location_select'=>'弹出地理位置选择器',
		);
		if($type==1){ 
			$string_arr['none']='无事件顶级菜单';
		}
		if($find){
			return $string_arr[$find];
		}else{
			return $string_arr;
		}

	}
function get_weixin_type_button_name($find=''){ 
	$string_arr=array(
				0=>'none',
				//1=>'click',
				2=>'view',
				3=>'scancode_push',
				//4=>'scancode_waitmsg',
				5=>'pic_sysphoto',
				6=>'pic_photo_or_album',
				7=>'pic_weixin',
				8=>'location_select',
		);
		if($find==0){
			return $string_arr[$find];
		}elseif($find){ 
			return $string_arr[$find];
		}else{ 
			return $string_arr;
		}	
}
function reback_weixin_key($find=''){ 

	$string_arr=array(
				'none'=>0,
				//'click'=>1,
				'view'=>2,
				'scancode_push'=>3,
				//'scancode_waitmsg'=>4,
				'pic_sysphoto'=>5,
				'pic_photo_or_album'=>6,
				'pic_weixin'=>7,
				'location_select'=>8,
				
		);
		if($find==0){
			return $string_arr[$find];
		}elseif($find){ 
			return $string_arr[$find];
		}else{ 
			return $string_arr;
		}
}
/*
*分销商品分类
*/
function get_distribute_category($find=''){
	$tree =get_distribute_cate_tree(0, 'id,title,sort,pid,status');
	$array_info=array();
	if($tree){
		foreach($tree as $value){
		    $array_info[$value['id']]  =  $value['title']; //---一级分类---
			if($value[_]){
				foreach($value[_] as $val){
				   $array_info[$val['id']] = "&nbsp;&nbsp;&nbsp;&nbsp;".$val['title'];  //---二级分类---
					if($val[_]){
						foreach($val[_] as $v){
						  $array_info[$v['id']] ="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$v['title'];  //--三级分类---
						   
						}
					}
				}
				
			}
		
		}
		
	}
	if($find){
		return $array_info[$find];
	}else{
		return $array_info;
	}
}
function get_distribute_cate_tree($id = 0, $field = true){
		if($id){     /* 获取当前分类信息 */
		$info = shop_cate_info($id);
		$id   = $info['id'];
		}

		/* 获取所有分类 */
		$map  = array('status' => array('gt', -1),'is_distribute' => 1);
		$list = D('shop_category')->field($field)->where($map)->order('sort')->select();
		$list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_', $root = $id);
		/* 获取返回数据 */
		if(isset($info)){ //指定分类则返回当前分类极其子分类
		$info['_'] = $list;
		} else { //否则返回所有分类
		$info = $list;
		}

		return $info;
}


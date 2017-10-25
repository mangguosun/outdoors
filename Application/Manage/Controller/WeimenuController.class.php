<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;

class WeimenuController extends WeixinController{

    public function _initialize(){
      	parent::_initialize(); 
      	$map['siteid']=SITEID;
		$map ['token'] = get_token ();
		$info=D('weixin_memberpublic')->where($map)->find();
		if(!$info){ 
			$this->error('当前没有选定的公众号！');
		}
		if($info['type']<2){ 
			$this->error('微信公众平台暂不支持未认证的订阅号的自定义菜单接口！');
		}

      	
	}
	public function index(){
		$map['siteid']=SITEID;
		$map ['token'] = get_token ();
		$info=D('weixin_memberpublic')->where($map)->find();
		if(!$info){ 
			$info['public_name']="当前没有选定的公众号!";
			$info['id']="无";
		}
		$count=D('weixin_custom_menu')->where($map)->count();
		$Page       = new \Think\Page($count,20);// 
		$show       = $Page->show();
		$list=D('weixin_custom_menu')->where($map)->limit($Page->firstRow.','.$Page->listRows)->order ( 'pid asc,id desc, sort asc ' )->select();
		foreach ( $list as $k => $vo ) {
			if ($vo ['pid'] != 0)
				continue;
			
			$one_arr [$vo ['id']] = $vo;
			unset ( $list [$k] );
		}
		foreach ( $one_arr as $p ) {
			$data [] = $p;
			
			$two_arr = array ();
			foreach ( $list as $key => $l ) {
				if ($l ['pid'] != $p ['id'])
					continue;
				
				$l ['title'] = '├──' . $l ['title'];
				$two_arr [] = $l;
				unset ( $list [$key] );
			}
			
			$data = array_merge ( $data, $two_arr );
		}
		foreach ($data as $ke => $va) {
			$data[$ke]['type']=get_weixin_type_button($va['type'],1);
		}
		$this->assign('page',$show);
		$this->assign('present',$info);
		$this->assign('list_data',$data);
		$this->display();
		
	}
	public function error_code(){ 
		$this->display();
	}
	public function add(){
		if(IS_POST){ 

			$sort 			=intval(trim($_POST['sort']));
			$first_menu		=trim($_POST['first_menu']);
			$type 			=trim($_POST['type']);
			$title 			=trim($_POST['title']);
			$keyword		=trim($_POST['keyword']);
			$url   			=trim($_POST['url']);
			$token=get_token();
			if(!$token){ 
				$this->error('请先选择微信公众账号！');
			}
			if($first_menu==0){ 
				$map=array('status'=>1,'token'=>$token,'siteid'=>SITEID,'pid'=>0);
				$count=D('weixin_custom_menu')->where($map)->count();
				if($count>=3){ 
					$this->error('已经开启三个一级菜单!');
				}
			}else{ 
				$map=array('status'=>1,'token'=>$token,'siteid'=>SITEID,'pid'=>$first_menu);
				$count=D('weixin_custom_menu')->where($map)->count();
				if($count>=5){ 
					$this->error('该菜单已经开启5个二级菜单！');
				}
			}
			if($title==''){ 
				$this->error('菜单名不能为空！');
			}			
			$title_length=get_ch_en_length($title,2);

			if($first_menu==0){ 
				if($title_length>16){ 
					$this->error('菜单名过长！');
				}
			}else{ 
				if($title_length>40){ 
					$this->error('菜单名过长！');
				}
			}
			if($keyword!=''){ 
				$key_length=get_ch_en_length($keyword,2);
				if($key_length>128){ 
					$this->error('关键字过长！');
				}
			}else{ 
				if($url!=''){ 
					$url_length=get_ch_en_length($url,2);
					if($url_length>256){ 
						$this->error('URL长度过长!');
					}
				}else{ 
					if($type==2){ 
						$this->error('关联URL不能为空！');
					}else{ 
						$this->error('关键字不能为空！');
					}
				}
			}
			$type=get_weixin_type_button_name($type);
			$data=array(
				'sort'		=>$sort,
				'pid'		=>$first_menu,
				'type'		=>$type,
				'title'		=>$title,
				'keyword'	=>$keyword,
				'url'		=>$url,
				'siteid'	=>SITEID,
				'status'    =>1,
				'token'		=>$token,
				);
	
			$list=D('weixin_custom_menu')->data($data)->add();
			if($list){ 
				$this->success('添加成功！',U('Weimenu/index'));
			}else{ 
				$this->error('添加失败！');
			}
		}
		$buttom_type=get_weixin_type_button2();
		$first_menu=get_menu_first();
		$this->assign('type_list',$buttom_type);
		$this->assign('first_menu',$first_menu);
		$this->display ();
		
	}
	public function edit($id=0){
		$id = I ( 'id' );
		if(!$id){ 
			$this->error('参数错误！');
		}
		$info=D('weixin_custom_menu')->where("id = ".$id)->find();
		if(IS_POST){
			$id             =$_POST['id'];
			$sort 			=intval(trim($_POST['sort']));
			$first_menu		=trim($_POST['first_menu']);
			$type 			=trim($_POST['type']);
			$title 			=trim($_POST['title']);
			$keyword		=trim($_POST['keyword']);
			$url   			=trim($_POST['url']);
			$token=get_token();
			if(!$token){ 
				$this->error('请先选择微信公众账号！');
			}
			if($first_menu==0){ 
				$map=array('status'=>1,'token'=>$token,'siteid'=>SITEID,'pid'=>0);
				$count=D('weixin_custom_menu')->where($map)->count();
				if($info['pid']==0){ 
					if($count>3){ 
						$this->error('已经开启3个顶级菜单!');
					}
				}else{ 
					if($count>=3){ 
						$this->error('已经开启3个顶级菜单!');
					}
				}
			}else{
				$childlist=D('weixin_custom_menu')->where("pid = ".$id)->find();
				if($childlist){ 
					$this->error('该菜单有子菜单，不能更改为二级菜单！');
				} 
				$map=array('status'=>1,'token'=>$token,'siteid'=>SITEID,'pid'=>$first_menu);
				$count=D('weixin_custom_menu')->where($map)->count();
				if($info['pid']==$first_menu){ 
					if($count>5){ 
						$this->error('该菜单已经开启5个二级菜单！');
					}
				}else{	 
					if($count>=5){ 
						$this->error('该菜单已经开启5个二级菜单！');
					}
				}
			}
			if($title==''){ 
				$this->error('菜单名不能为空！');
			}
			$title_length=get_ch_en_length($title,2);
		
			if($first_menu==0){ 
				if($title_length>16){ 
					$this->error('菜单名过长！');
				}
			}else{ 
				if($title_length>40){ 
					$this->error('菜单名过长！');
				}
			}
			if($keyword!=''){ 
				$key_length=get_ch_en_length($keyword,2);
				if($key_length>128){ 
					$this->error('关键字过长！');
				}
			}else{ 
				if($url!=''){ 
					$url_length=get_ch_en_length($url,2);
					if($url_length>256){ 
						$this->error('URL长度过长!');
					}
				}else{ 
					if($type==2){ 
						$this->error('关联URL不能为空！');
					}else{ 
						$this->error('关键字不能为空！');
					}
				}
			}
			if($type ==2){ 
				$keyword='';
			}else{ 
				$url='';
			}
			$type=get_weixin_type_button_name($type);
			$data=array(
				'sort'		=>$sort,
				'pid'       =>$first_menu,
				'type'		=>$type,
				'title'		=>$title,
				'keyword'	=>$keyword,
				'url'		=>$url,
				'siteid'	=>SITEID,
				'status'    =>1,
				'token'		=>$token,
				);
			$list=D('weixin_custom_menu')->where("id = ".$id)->save($data);
			if($list){ 
				$this->success('编辑成功！',U('Weimenu/index'));
			}else{ 
				$this->error('添加失败！');
			}
		}
		
		$buttom_type=get_weixin_type_button2();
		$first_menu=get_menu_first();
		$info['type']=reback_weixin_key($info['type']);
		$this->assign('type_list',$buttom_type);

		$this->assign('first_menu',$first_menu);
		$this->assign('menu_list',$info);
		$this->display();
	}
	public  function changeStatus($id=0,$method = null){
    	$id = array_unique((array)I('id', 0));
    	$method=$_GET['method'];
    	$id = is_array($id) ? implode(',', $id) : $id;
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        $map = "siteid = ".SITEID." and id in ($id)";
        $token =get_token();
        $map1= array('siteid'=>SITEID,'token'=>$token,'status'=>1);
        $info=D('weixin_custom_menu')->where($map)->select();
       	switch ($method) {
       		case 'forbid':
       			if($info[0]['pid']==0){ 
       				$map1['pid']=0;
       				$count=D('weixin_custom_menu')->where($map1)->count();
       				if($count>2){ 
       					$this->error('只能开启三个一级菜单!');
       				}
       				D('weixin_custom_menu')->where("id = ".$info[0]['id'])->setField('status', 1);
       			}else{ 
       				$map1['pid']=$info[0]['pid'];
       				$count=D('weixin_custom_menu')->where($map1)->count();
       				if($count>4){ 
       					$this->error('同一菜单只能开启五个二级菜单!');
       				}
       				$list=D('weixin_custom_menu')->where("id = ".$info[0]['pid'])->field('status')->find();
       				if($list['status']==0){ 
       					$this->error('一级菜单已禁用，请先开启一级菜单！');
       				}else{ 
       					D('weixin_custom_menu')->where("id = ".$info[0]['id'])->setField('status', 1);
       				}
       			}
       			$this->success('启用成功！');
       			break;
       		case 'resume':
       			if($info[0]['pid']==0){ 
       				D('weixin_custom_menu')->where("pid = ".$info[0]['id'])->setField('status', 0);
       				D('weixin_custom_menu')->where("id = ".$info[0]['id'])->setField('status', 0);
       			}else{ 
       				D('weixin_custom_menu')->where("id = ".$info[0]['id'])->setField('status', 0);
       			}
       			$this->success('禁用成功！');
       			break;
       		case 'delete':
       			foreach ($info as $ke => $va) {
       				if($info[$ke]['pid']==0){ 
       					D('weixin_custom_menu')->where("pid = ".$info[$ke]['id'])->delete();
       					D('weixin_custom_menu')->where("id = ".$info[$ke]['id'])->delete();
       				}else{ 
       					D('weixin_custom_menu')->where("id = ".$info[$ke]['id'])->delete();
       				}
       			}
       			$this->success('删除成功！');
       			break;


       		default:
       			$this->error('参数非法');
       			break;
       	}
            
   }

	public function send_to_weixin(){ 
		
		$map['siteid'] = SITEID;
		$map['token'] = get_token ();
		$map['status'] = 1;
		$map['pid']    =0;
		$count=D('weixin_custom_menu')->where($map)->count();

		if($count>3){ 
			$this->error('一级菜单只允许开启三个!');
		}
		unset($map['pid']);
		$list=D('weixin_custom_menu')->where($map)->order ( 'pid asc, sort asc ' )->select();
		foreach ( $list as $k => $d ) {
			if ($d ['pid'] != 0)
				continue;
			$tree ['button'] [$d ['id']] = $this->_deal_data ( $d );
			unset ( $list [$k] );
		}
		foreach ( $list as $k => $d ) {
			$tree ['button'] [$d ['pid']] ['sub_button'] [] = $this->_deal_data ( $d );
			unset ( $list [$k] );
		}
		$tree2 = array ();
		$tree2 ['button'] = array ();
		
		foreach ( $tree ['button'] as $k => $d ) {
			$tree2 ['button'] [] = $d;
		}

		$tree=$this->json_encode_cn($tree2);
		$info=D('weixin_memberpublic')->where($map)->find();
		$url_get = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $info ['appid'] . '&secret=' . $info ['appsecret'];
		$ch1 = curl_init ();
		$timeout = 5;
		curl_setopt ( $ch1, CURLOPT_URL, $url_get );
		curl_setopt ( $ch1, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch1, CURLOPT_CONNECTTIMEOUT, $timeout );
		curl_setopt ( $ch1, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch1, CURLOPT_SSL_VERIFYHOST, false );
		$accesstxt = curl_exec ( $ch1 );
		curl_close ( $ch1 );
		$access = json_decode ( $accesstxt, true );
		if (empty ( $access ['access_token'] )) {
			$this->error ( '获取access_token失败,请确认AppId和Secret配置是否正确,然后再重试。' );
		}
		file_get_contents ( 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . $access ['access_token'] );
		
		$url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $access ['access_token'];
		$header [] = "content-type: application/x-www-form-urlencoded; charset=UTF-8";
		
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header );
		curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)' );
		curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $tree );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		$res = curl_exec ( $ch );
		curl_close ( $ch );
		$res = json_decode ( $res, true );
		if ($res ['errcode'] == 0) {
			$this->success ( '发送菜单成功' );
		} else {
			$this->error ( '发送菜单失败，错误的返回码是：' . $res ['errcode'] . ', 错误的提示是：' . $res ['errmsg'] );
		}
	}

	function json_encode_cn($data) {
		$data = json_encode ( $data );
		return preg_replace ( "/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $data );
	}
	public function _deal_data($d) {
		$res ['name'] = str_replace ( '├──', '', $d ['title'] );
		
		if($d['type']=='view'){
			$res ['type'] = 'view';
			$res ['url'] = trim ( $d ['url'] );			
		}elseif($d['type']!='none'){
			$res ['type'] = trim( $d['type'] );
			$res ['key'] = trim ( $d ['keyword'] );			
		}
		return $res;
	}

	public function get_weixin_menu(){ 


	}

	


}  

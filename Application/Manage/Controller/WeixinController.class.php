<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;

class WeixinController extends BaseController{

    public function _initialize(){
      	parent::_initialize();
      	if(empty($_SESSION['mid'])){ 
      		$map=array('siteid'=>SITEID,'is_create'=>1);
      	}else{ 
      		$map=array('siteid'=>SITEID,'is_use'=>1);
      	}
      	$create_mp=D('weixin_memberpublic_link')->where($map)->find();
  		if($create_mp){ 
  			session('mid',$create_mp['mp_id']);
  			$token=session('token');
			if(empty($token)){ 
				$token=D('weixin_memberpublic')->where("id = ".$create_mp['mp_id'])->getField('token');
				get_token($token);
			}
  		}
       
	}
	public function index(){
		$map1['token']=get_token();
		$map1['siteid']=SITEID;
		$info=D('weixin_memberpublic')->where($map1)->find();
		if(!$info){ 
			$info['public_name']="当前没有选定的公众号!";
		}
		$map=array('status'=>array('egt',0),'siteid'=>SITEID);
		$count=D('weixin_memberpublic')->where($map)->count();
		$Page       = new \Think\Page($count,10);// 
		$show       = $Page->show();
		$list=D('weixin_memberpublic')->where($map)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		foreach ($list as $key => $val) {
			$list[$key]['type']=D('Wei')->get_weixin_public_type($val['type']);
		}
		$this->assign('page',$show);
		$this->assign('present',$info);
		$this->assign('weixin_info',$list);
		$this->display();
		
	}

	public function public_add(){
		$id=I('id');
		if($id){ 			
			$list=D('weixin_memberpublic')->where("id = ".$id)->find();
			$this->assign('public_info',$list);			
		}
		$this->assign('id',$id);
		$this->display();
	}
	public  function do_publicadd(){ 
		$id=trim($_POST['id']);
		$data['public_name']	=	trim($_POST['public_name']);
		$data['public_id']  	=	trim($_POST['public_id']);
		$data['wechat']		    =	trim($_POST['wechat']);
		$data['type']           =   trim($_POST['type']);
		if($data['public_name'] =='')$this->error('公众号名称不能为空');
		if($data['public_id']   =='')$this->error('原始ID不能为空');
		if($data['wechat']      =='')$this->error('微信号不能为空');
		if($data['type']        =='')$this->error('请选择公众号类型');
		$data['siteid']     =SITEID;
		$data['token']      =$data['public_id'];
		if($id){ 
			$listmap=array('public_id'=>$data['public_id'],'id'=>array('neq',$id));
			$list=D('weixin_memberpublic')->where($listmap)->find();
			if($list){ 
				$this->error('公众号原始ID已存在，请勿重复添加！');
			}
			$saveid=D('weixin_memberpublic')->where("id = ".$id)->save($data);
			if($saveid){ 
				$newid=$id;
			}else{ 
				$newid=false;
			}

		}else{ 
			$listmap=array('public_id'=>$data['public_id']);
			$list=D('weixin_memberpublic')->where($listmap)->find();
			if($list){ 
				$this->error('公众号原始ID已存在，请勿重复添加！');
			}
			$newid=D('weixin_memberpublic')->data($data)->add();
			if($newid){ 
				$map['siteid']=SITEID;
				$linklist=D('weixin_memberpublic_link')->where($map)->find();
				if(!$linklist){ 
					$map['is_create']=1;
				}else{ 
					$map['is_creata']=0;
				}
				$map['mp_id']=$newid;
				$map['is_use']=0;
				D('weixin_memberpublic_link')->data($map)->add();
			}
		}
		if($newid){ 
			$this->success('保存基本配置成功，',U('Weixin/step_1',array('id'=>$newid)));
		}else{ 
			$this->error('操作失败');
		}
	}
	public function step_1(){ 
		$id=I('id');
		$url="id/".$id."/siteid/".SITEID;
		$newurl=rawurlencode(base64_encode($url));
		$addressURL="http://beta.huodongli.cn/Weixin/index/index/urldata/".$newurl.".html";
		$this->assign('url',$addressURL);
		$this->assign ( 'id', $id );
		$this->display();
	}
	public function step_2(){ 
		$id=I('id');
		$list=D('weixin_memberpublic')->where("id = ".$id)->find();
		$this->assign('public_info',$list);			
		$this->assign('id',$id);
		$this->display();
	}
	public function do_step_2(){ 
		$data['appid']			=	trim($_POST['appid']);
		$data['appsecret']		=	trim($_POST['appsecret']);
		$data['encodingaeskey']	=	trim($_POST['encodingaeskey']);
		$id 					=	trim($_POST['id']);
		if($data['appid'] 			=='')$this->error('应用ID不能为空');
		if($data['appsecret'] 		=='')$this->error('应用密钥不能为空');
		if($data['encodingaeskey'] 	=='')$this->error('EncodingAESKey不能为空');
		$data['Tokens'] ='huodongli';
		$list=D('weixin_memberpublic')->where("id = ".$id)->save($data);
		if($list){ 
			$this->success('保存成功，页面即将跳转！',U('Weixin/index'));
		}else{ 
			if($data['appid'] !=''&& $data['appsecret'] !=''&& $data['encodingaeskey']!=''){ 
				$this->success('保存成功，页面即将跳转！',U('Weixin/index'));
			}else{ 
				$this->error('保存失败');
			}
		}

	}

	public  function changeStatus($id=0,$method = null){
    	$id = array_unique((array)I('id', 0));
    	$method=$_GET['method'];
    	$id = is_array($id) ? implode(',', $id) : $id;
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        $map = "siteid = ".SITEID." and id in ($id)";
        $list=D('weixin_memberpublic')->where($map)->select();
       
       	switch ($method) {
       		case 'forbid':
       			D('weixin_memberpublic')->where($map)->setField('status', 1);
       			$this->success('启用成功！');
       			break;
       		case 'resume':
       			D('weixin_memberpublic')->where($map)->setField('status', 0);
       			$this->success('禁用成功！');
       			break;
       		default:
       			$this->error('参数非法');
       			break;
       	}
            
   }
   public  function changPublic(){ 
   		$map ['id']    	= I ( 'id', 0, 'intval' );
   		$map ['siteid'] = SITEID;
		$info = M ( 'weixin_memberpublic' )->where ( $map )->find ();
		
		unset ( $map );
		$map ['uid'] = $_SESSION['uid'];
		D( 'weixin_memberpublic_link' )->where ( $map )->setField ( 'is_use', 0 );		
		$map ['mp_id'] = $info ['id'];
		D( 'weixin_memberpublic_link' )->where ( $map )->setField ( 'is_use', 1 );
		
		get_token ( $info ['public_id'] );

		$this->success('切换成功');
		
   }
   public function help(){
   		if (empty ( $_GET ['id'] )) {
			$this->error ( '公众号参数非法' );
		}
		$id=$_GET['id'];
		$url="id/".$id."/siteid/".SITEID;
		$newurl=rawurlencode(base64_encode($url));
		$addressURL="http://beta.huodongli.cn/Weixin/index/index/urldata/".$newurl.".html";
		$this->assign('url',$addressURL);
		$this->display();

   }

   




}  

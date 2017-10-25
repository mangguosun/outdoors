<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;
/*
*网站管理* 2015-1-19 dlx-
*/
class WebsitController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize();
		$this->documentModel = D('document');
		$this->category = D('category');
	}
     
	public function index($siteid=null,$url='',$slogan='',$cover_id='',$logo_icons='',$email='',$telphone=0,$webname='',$domain='',$user_agreement='',$logo_footer='',$icp='',$micro='',$weibo='',$qq=''){
	
		if(IS_POST){
			if($webname=='') $this->error('请填写网站名称');
			if($cover_id=='') $this->error('请上传网站导航LOGO');
			if($url=='') $this->error('请输入二级域名前缀');
			if($email=='') $this->error('请输入正确的客服Email');
			if($telphone=='') $this->error('请输入正确的客服电话');

			//if($logo_footer=='') $this->error('请上传页脚logo图片');

			
			$oldurl=D('websit')->where("siteid !=".$siteid." and url='{$url}'")->find();
			if($oldurl) $this->error('该域名已被注册！');
		   
			$data = array(
				'webname' 		=> $webname,
				'domain'  		=> $domain,
				'uid'	  		=> is_login(),
				'url'	  		=> $url,
				'email'   		=> $email,
				'telphone'		=> $telphone,
				'cover_id'		=> $cover_id,
				'logo_icons'	=> $logo_icons,
				'slogan'  		=> $slogan,
			    'user_agreement'=> $user_agreement,
				'logo_footer'   => $logo_footer,
				'icp'           => $icp,
				'weibo'         => $weibo,
				'qq'            => $qq,
				'micro'         => $micro,
				'uid'			=> is_login(),
				
			); 
			
			$cate=D('websit')->where("siteid = ".SITEID)->save($data);
			
			if($cate){
				$domain = $_SERVER['HTTP_HOST'];
            	clean_website_info_cash($domain);
				$this->success('修改成功',U('Manage/Websit/index'));
			}else{
				$this->error('未修改数据');
			}
			
		}else{
			//读取列表
			$map = array('status' => array('eq',1),'siteid'=>SITEID);
			$list = D('websit')->where($map)->find();
		    $this->assign('list',$list);
			$this->display();
        }
		
    }
    //--轮播大图--
	public function picture(){
	

		  $pictrue_arr = D('advs')->where($map=array('siteid'=>SITEID,'status'=>array('egt',0)))->order('position asc,level asc,id asc')->select();
			foreach ($pictrue_arr as $key => &$val) {
				$position_arr = D('advertising')->where("id = ".$val['position'])->find();
				$val['positiontext'] = $position_arr['title'];
				
			}
		
		$this->assign('pictrue',$pictrue_arr);
		$this->assign('page',$show);
		$this->display();
	}
	public function login_together($qq_jointLogin='0',$wx_jointLogin='0'){
		if(IS_POST){ 
			$rs_qq = addWebsitConfig($qq_jointLogin,'qq_jointLogin');
			$rs_wx = addWebsitConfig($wx_jointLogin,'wx_jointLogin');
			if($rs_wx || $rs_qq){
				$this->success('操作成功','refresh');
			}else{
				$this->error('未修改数据');
			}

		}else{ 
			$website_config_qq = getWebsitConfig('qq_jointLogin',2);
			$website_config_wx = getWebsitConfig('wx_jointLogin',2);
			$this->assign('qq_jointLogin',$website_config_qq);
			$this->assign('wx_jointLogin',$website_config_wx);
			$this->display();
		}	
	}	



	 public function picture_edit($id = 0)
    {
        $isEdit = $id ? 1 : 0;
        if (IS_POST) {
            $post = $_POST;
			if(!$post['title']) $this->error('请填写广告名称');
			if(!$post['position']) $this->error('请选择广告类型');
			if(!$post['advspic']) $this->error('请上传广告图片');
			if(!$post['create_time']) $this->error('请选择开始时间');
			
			
			$post['create_time'] = strtotime($post['create_time']);
			
			if($post['end_time']) {
				$post['end_time'] = strtotime($post['end_time']);
				if( $post['end_time'] <= $post['create_time']) $this->error('结束时间不能小于或等于开始时间');
			}else{
				$post['end_time'] = $post['create_time']+ 31536000 ;
				
			}
			
			if ($isEdit) {
				$id  = $post['id'];
				unset($post['id']);
				$post['siteid'] =SITEID;
				$rs_content = D('advs')->where('id = '.$id )->save($post);
				
					
            } else {
				$post['siteid'] =SITEID;
				$post['status'] =1;
				$rs_content = D('advs')->add($post);
			}
            if ($rs_content) {
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Websit/picture'));
            } else {
                $this->error($isEdit ? '编辑失败' : '添加失败');
            }
			
        } else {
            
			if ($isEdit) {
				$detail = D('advs')->where("id =".$id)->find();
				$position_arr = D('advertising')->where("id = ".$detail['position'])->find();
				$detail['type'] = $position_arr['type'];
				$detail['create_time'] = date('Y-m-d H:i',$detail['create_time']);
				$detail['end_time'] = date('Y-m-d H:i',$detail['end_time']);
			}else{
				$detail['create_time'] = date('Y-m-d H:i',time());
				$detail['level'] = 0;
				
			}
			$detail['pag_title'] = $isEdit ? '编辑广告' : '添加广告';	
			$this->assign('datainfo',$detail);
			$positions = D('advertising')->where("status = 1")->select();
			
			$this->assign('positions',$positions);
			$this->display();
        }
    }
	/*
	*轮播图回收站
	*/
	public function picture_strash($page = 1, $r = 20, $model = ''){
		$builder = new AdminListBuilder();
		$builder->clearTrash($model);
		//读取列表
        $map = array('status' => -1,'siteid'=>SITEID);
        $list = D('advs')->where($map)->page($page, $r)->select();
		foreach ($list as $key => &$val) {
			$position_arr = D('advertising')->where("id = ".$val['position'])->find();
			$val['positiontext'] = $position_arr['title'];
			if($val['status']){
				$val['statustext']= '启用';
			}else{
				$val['statustext']= '禁用';
			}

		}
        $totalCount = D('advs')->where($map)->count();
        //显示页面
        
		$attr['class'] = 'ajax-post';
        $attr['target-form'] = 'ids';

        $builder->title('轮播图回收站')
				->setStatusUrl(U('setPictureContentStatus'))->buttonRestore()//->buttonClear('advs')
				->keyId('id','序号')
				->key('title', '广告名称')
				->key('positiontext','广告类型')
				->keyCreateTime('create_time','开始时间')
				->keyUpdateTime('end_time','结束时间')
				->KeyStatusReversion()
				->data($list)
				->pagination($totalCount, $r)
				->display();
	}
	
	
	
	/*
	*顶部导航配置**
	**/
	public function top_nav_config($nav_config=0){
		if (IS_POST) {
		
  			$navs_data['nav_config'] = $nav_config;
			$navs_data['siteid'] = SITEID;
			
			$rs = D('websit')->save($navs_data);
            if ($rs) {
            	$domain = $_SERVER['HTTP_HOST'];
            	clean_website_info_cash($domain);
                $this->success('编辑成功', U('Websit/top_nav_config'));
            } else {
                $this->error('未修改数据');
            }
		
		} else {
            $builder = new AdminConfigBuilder();
           
			$rs = D('websit')->where(array('siteid'=>SITEID))->find();
			$nav_data['nav_config'] = $rs['nav_config'];
			$this->assign('datainfo',$nav_data);
			$this->display();

		}
	}	
	
	
	/*
	*顶部导航
	**/
	public function top_nav_custom(){
		//读取列表
        $map = array('siteid'=>SITEID);
        $list = D('channel_websit_custom')->where($map)->order(array('sort'=>'asc'))->select();
   		$this->assign('datainfo',$list);
		$this->display();
	}	
	
	/*
	*编辑导航
	**/
	public function top_nav_custom_edit($id = 0, $title = '',$sort = 0,$url = ''){
		$isEdit = $id ? 1 : 0;
        if (IS_POST) {
            if ($title == '' || $title == null) {
                $this->error('请输入导航名称');
            }
            if($url == ''){
					$this->error('排序请输入连接地址!');
			}
			if($sort != ''){
				if(!is_numeric($sort)){
					$this->error('排序请输入数字!');
				}
			}
			$navs_data['title'] = $title;
			$navs_data['sort'] = $sort;
			$navs_data['url'] = $url;
            if ($isEdit) {
				$rs = D('channel_websit_custom')->where('id=' . $id)->save($navs_data);
				
            } else {
				$navs_data['siteid']			=	SITEID;
			    $navs_data['status'] 		= 	1;
				$navs_data['display'] 		= 	1;
                
				$rs = D('channel_websit_custom')->add($navs_data);
			}
            if ($rs) {
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Websit/top_nav_custom'));
            } else {
                $this->error($isEdit ? '未修改数据' : '添加失败');
            }
        
		
		} else {
			
			
  			if ($isEdit) {
                $nav_data = D('channel_websit_custom')->where('id=' . $id)->find();
            } else {
				$nav_data['status'] = 1;
				$nav_data['sort'] = 0;
				$nav_data['url'] = '';
            }
			$nav_data['pag_title'] = $isEdit ? '编辑自定义导航' : '添加自定义导航';		
			$this->assign('datainfo',$nav_data);
			$this->display();	
		}
	
	}	
	

	
	
	/*
	*顶部导航
	**/
	public function top_nav_system(){
		//读取列表
        $map = array('status' => array('egt',0),'siteid'=>SITEID,'display'=>1);
        $list = D('channel_websit')->where($map)->order(array('sort'=>'asc'))->select();
   		$this->assign('datainfo',$list);
		$this->display();
	}
	/*
	*编辑导航
	**/
	public function top_nav_system_edit($id = 0, $title = '',$sort = 0){
		$isEdit = $id ? 1 : 0;
        if (IS_POST) {
            if ($title == '' || $title == null) {
                $this->error('请输入导航名称');
            }
            if($sort != ''){
				if(!is_numeric($sort)){
					$this->error('排序请输入数字!');
				}
				
			}
          
			$navs_data['title'] = $title;
			$navs_data['sort'] = $sort;
		
            if ($isEdit) {
				$rs = D('channel_websit')->where('id=' . $id)->save($navs_data);
				
            } else {
				$navs_data['siteid']			=	SITEID;
			    $navs_data['status'] 		= 	1;
				$navs_data['display'] 		= 	1;
                
				$rs = D('channel_websit')->add($navs_data);
			}
            if ($rs) {
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Websit/top_nav_system'));
            } else {
                $this->error($isEdit ? '未修改数据' : '添加失败');
            }
        
		
		} else {
			
			if ($isEdit) {
                $nav_data = D('channel_websit')->where('id=' . $id)->find();
            } else {
				$nav_data['status'] = 1;
				$nav_data['sort'] = 0;
            }
			$nav_data['pag_title'] = $isEdit ? '编辑顶部系统导航' : '添加顶部系统导航';		
			$this->assign('datainfo',$nav_data);
			$this->display();	
		}
	
	}
	/*
	*social***
	**/
	public function social(){
		//读取列表
		$map = array('status' => array('egt',0),'siteid'=>SITEID);
        $list=D('share')->where($map)->order(array('id'=>'desc'))->select();
		$this->assign('datainfo',$list);
		$this->display();
    }
	/*
	*添加修改social帐号**
	**/
	public function social_edit($id = 0,$url='',$cover_logo=''){
		 $isEdit = $id ? 1 : 0;
		 if (IS_POST) {
			$url = op_t(trim($url));
            if($cover_logo=='') $this->error('请输入social名称');
            if($url!=''){
				if(checked_url($url)){
					$this->error('请输入正确的链接地址');
				}
			   $url=new_url($url);
			}
			$data['cover_logo'] = $cover_logo;
			$data['url']		= $url;

            if ($isEdit) {
				$rs_content = D('share')->where("id=".$id)->save($data);
            } else {
				$reds = D('share')->where(array('siteid'=>SITEID,'status'=>array('egt','0')))->select();
				if(count($reds)<5){
					$data['uid'] 	= is_login();
					$data['siteid']	= SITEID;
					$data['status'] = 1;
					$rs_content	= D('share')->add($data);
					
				}else{
					$this->error('最多可以添加五条数据');
				}
				
			}
            if ($rs_content) {
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Websit/social'));
            } else {
                $this->error($isEdit ? '编辑内容没有更新' : '添加失败');
            }
        } else {
			
            if ($isEdit) {
                $share_data = D('share')->where('id=' . $id)->find();
            } else {
				$share_data['status'] = 1;
            }
			$share_data['pag_title'] = $isEdit ? '编辑Social账号' : '添加Social账号';		
			$share_data['share'] = get_share();	
			$this->assign('datainfo',$share_data);
			$this->display();
        }
		
	}
	
	/*
	*social回收站*
	*/
	public function social_strash($page = 1, $r = 20, $model = ''){
		$builder = new AdminListBuilder();
		$builder->clearTrash($model);
		//读取列表
        $map = array('status' => -1,'siteid'=>SITEID);
        $list = D('share')->where($map)->page($page, $r)->select();
        $totalCount = D('share')->where($map)->count();
		foreach($list as &$val){
			$val['img']        = "<img src='PUBLIC/Core/images/share/$val[cover_logo].png'>";
			$val['cover_logo'] = get_share($val['cover_logo']);
			
		}
        //显示页面
		$attr['class'] = 'ajax-post';
        $attr['target-form'] = 'ids';

        $builder->title('social回收站')
				->setStatusUrl(U('setSocialStatus'))->buttonRestore()//->buttonClear('share')
				->keyId('id','序号')
				->keyNewLink('cover_logo','名称','url=###')
				->key('img','图标')
				->KeyStatusReversion()
				->data($list)
				->pagination($totalCount, $r)
				->display();
	}
	/*
	*页脚导航 add by Jones
	**/
	public function footer_nav(){
		 //读取列表
		$map = array('siteid'=>SITEID,'status'=>array('egt','0'));
        $list=D('enterprises')->where($map)->select();
		foreach($list as $key=>&$val){
		    $user  = query_user(array('id','username','nickname'), $val['uid']);
			$val['nickname'] = $user['nickname'];
		}
		$this->assign('datainfo',$list);
		$this->display();	
		 
	}
	/*
	*页脚导航**/
	public function footer_nav_strash($page = 1, $r = 20, $model = ''){
		
		$builder = new AdminListBuilder();
		$builder->clearTrash($model);
		//读取列表
        $map = array('status' => -1,'siteid'=>SITEID);
        $list = D('enterprises')->where($map)->page($page, $r)->select();
        $totalCount = D('enterprises')->where($map)->count();
        //显示页面
     

        $attr['class'] = 'ajax-post';
        $attr['target-form'] = 'ids';

        $builder->title('页脚导航回收站')
            ->setStatusUrl(U('setFooterNavStatus'))->buttonRestore()//->buttonClear('enterprises')
            
			->keyId('id','ID')
			->keyNewLink('company_name', '名称', 'url=###')
			->keyUid('uid','发布人')
			->KeyStatusReversion()
			->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }
	
	
	
	/*
	*添加修改footer_edit导航帐号 add by Jones 2015-Jun-24**
	**/
	public function footer_nav_edit($id = 0,$url='',$company_name=''){
		 $isEdit = $id ? 1 : 0;
		 if (IS_POST) {
			$url = op_t(trim($url));
            if($company_name=='') $this->error('请输入导航名称');
            if($url!=''){
				if(checked_url($url)){
					$this->error('请输入正确的链接地址');
				}
			   $url=new_url($url);
			}
			$data['company_name'] = $company_name;
			$data['url']		= $url;

            if ($isEdit) {
				$rs_content = D('enterprises')->where("id=".$id)->save($data);
            } else {
				$reds = D('enterprises')->where(array('siteid'=>SITEID,'status'=>array('egt','0')))->select();
				if(count($reds)<4){
					$data['uid'] 	= is_login();
					$data['siteid']	= SITEID;
					$data['status'] = 1;
					$rs_content	= D('enterprises')->add($data);
					
				}else{
					$this->error('最多可以添加四条数据');
				}
			}
            if ($rs_content) {
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Websit/footer_nav'));
            } else {
                $this->error($isEdit ? '编辑内容没有更新' : '添加失败');
            }
        } else {
			
            if ($isEdit) {
                $enterprises_data = D('enterprises')->where('id=' . $id)->find();
            } else {
				$enterprises_data['status'] = 1;
            }
			$enterprises_data['pag_title'] = $isEdit ? '编辑页脚导航' : '添加页脚导航';		
			$this->assign('datainfo',$enterprises_data);
			$this->display();
        }
		
	}
	
	
	
	
	
	
	
	/***
	*QQ客服服务
	***/
	public function customer_service(){
		//读取列表
		$map = array('status' => array('egt',0),'siteid'=>SITEID);
        $list=D('customer_service')->where($map)->order(array('id'=>'asc'))->select();
		$this->assign('datainfo',$list);
		$this->display();	
	}
	/***
	*QQ客服回收站**
	**/
	public function customer_service_strash($page = 1, $r = 20, $model = ''){
		$builder = new AdminListBuilder();
		$builder->clearTrash($model);
		//读取列表
        $map = array('status' => -1,'siteid'=>SITEID);
        $list = D('customer_service')->where($map)->page($page, $r)->select();
        $totalCount = D('customer_service')->where($map)->count();
        //显示页面
        $attr['class'] = 'ajax-post';
        $attr['target-form'] = 'ids';

        $builder->title('QQ客服回收站')
            ->setStatusUrl(U('setCustomerServiceStatus'))->buttonRestore()//->buttonClear('customer_service')
            ->keyId('id','序号')
			->keyNewLink('nickname', '标题', 'url=###')
		    ->KeyStatusReversion()
		    ->data($list)
            ->pagination($totalCount, $r)
            ->display();
	}
	/*
	*添加修改QQ客服*
	*/
	public function customer_service_edit($id=0,$qq='',$nickname=''){
		 $isEdit = $id ? 1 : 0;
		 if (IS_POST) {
            $qq = op_t(trim($qq));
			$nickname = trim($nickname);
			if(!preg_match('/^\+?[1-9][0-9]*$/',$qq)){
				$this->error('客服QQ必须为数字哦');
			}
			if($nickname==''){
				$this->error('请填写昵称');
			}
			$data['url']="http://wpa.qq.com/msgrd?v=3&uin=" .$qq. "&site=qq&menu=yes";
			$data['qq']			=	$qq;
			$data['nickname']	=	$nickname;

            if ($isEdit) {
				$rs_content	=	D('customer_service')->where("id=".$id)->save($data);
            } else {
				$data['siteid']=SITEID;
				$data['uid']=is_login();
				$data['status'] = 1;
				$rs_content	=	D('customer_service')->add($data);
            }
            if ($rs_content) {
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Websit/customer_service'));
            } else {
                $this->error($isEdit ? '无修改内容' : '添加失败');
            }
			
        } else {
            if ($isEdit) {
				
				$service_data = D('customer_service')->where('id=' . $id)->find();
            } else {
				
                $service_data['status'] = 1;
            }
			
			$service_data['pag_title'] = $isEdit ? '编辑QQ客服' : '添加QQ客服';		
			$this->assign('datainfo',$service_data);
			$this->display();
        }
		
	}
    public function mobile_index_show(){ 
    	if(IS_POST){ 
    		$fast_nav_settings=$_POST['fast_nav'];
    		$mobile_module_display=$_POST['mobile_module'];
    		$rs=addWebsitConfig($fast_nav_settings,'fast_nav','mobile_config');
    		$rs_mobile_model=addWebsitConfig($mobile_module_display,'mobile_module','mobile_config');
    		if($rs||$rs_mobile_model){ 
    			$domain = $_SERVER['HTTP_HOST'];
            	clean_website_info_cash($domain);
    			$this->success('操作成功','refresh');
    		}else{
    			$this->error('操作失败');
    		}
    	}
    	$mobile_module=getWebsitConfig('mobile_module',1,'mobile_config');  
    	if(!$mobile_module){ 
    		$mobile_module='3,4,5,6,8';
    	}
    	$fast_nav=getWebsitConfig('fast_nav',1,'mobile_config');
    	$this->assign('fast_nav',$fast_nav);
    	$this->assign('mobile_module',$mobile_module);
    	$this->display();
    }
	

}  

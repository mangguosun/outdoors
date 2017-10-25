<?php

namespace Websit\Controller;

use Think\Controller;

class IndexController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize();
		
        $this->handle = D('Paydeposit');
	}

    public function index(){
		$status=$_GET['status'];
		$status = isset($status)? $status:0;
		switch($status){
			case 0;
				$list=D('websit')->where("siteid = ".SITEID)->find();
				$this->assign('list',$list);
			break;
			case 1;
			    $pictrue_arr = D('advs')->where("siteid = ".SITEID)->order('position asc,level asc')->select();
				foreach ($pictrue_arr as $key => &$val) {
					$position_arr = D('advertising')->where("id = ".$val['position'])->find();
					$val['positiontext'] = $position_arr['title'];
					if($val['status']){
						$val['statustext']= '启用';
					}else{
						$val['statustext']= '禁用';
					}
					
				}
		
				$this->assign('pictrue',$pictrue_arr);
		    break;
			case 2;
			    $list=D('share')->where("uid=".is_login()." and siteid=".SITEID)->select();
				$this->assign('shares',$list);
			break;
			case 3:
			    
			break;
			case 4:
			    $footers=D('websit')->where("siteid=".SITEID)->find();
				$this->assign('footers',$footers);
			break;
			case 5:
			    $enter=D('enterprises')->where("siteid=".SITEID)->select();
				$this->assign('enter',$enter);
			break;
			case 6:
				$websitnavs = D('channel_websit')->where("display = 1 and siteid = ".SITEID)->order("sort")->select();
		        $this->assign('websitnavs',$websitnavs);
			break;
			case 7:
			    $list=D('customer_service')->where("status>=0 and siteid=".SITEID)->select();
				$this->assign('list',$list);
			break;
			case 8:
			    $list	=	D('websit_product_config')->where("siteid=".SITEID." and status>=0")->select();
				$this->assign('product_config',$list);
			break;
			case 9:
			    $content = D('websit')->where("siteid=".SITEID)->find();
				$this->assign('websit_config_tags',$content);
				
			break;
		}
		$this->assign('status',$status);
		
		$this->display();
    }
	/*添加产品配置*/
	public function product_config_doAdd($title='',$description='',$address=''){
	        $title       = op_t(trim($title));
			$description = op_t(trim($description));
			//--一级分类只能添加5条记录--
			$configNum	=	D('websit_product_config')->where(array("pid"=>0,'siteid'=>SITEID))->count();
			if($configNum	>=5	) $this->error('最多可以添加5条记录哦!');
			
			if(strlen($title)>30) $this->error('标题10汉字以内!');
			if(strlen($description)>30) $this->error('标题10汉字以内!');
			if($title=='') $this->error('请填写标题');
			if($description=='') $this->error('请填写描述!');
			$this->check_url($address);
			  $data=array(
					 'siteid'		=>	SITEID,
					 'title'		=>	$title,
					 'description'	=>	$description,
					 'address'		=>	$address,
					 'status'		=>	1,
					 'pid'			=>	0
				);
			$list	=	D('websit_product_config')->data($data)->add();
			if($list){
				$this->success('添加成功','refresh');
			}else{
				$this->error('添加失败!');
			}
	
	}
	/*修改产品配置*/
	public function product_config_edit($title='',$description='',$address=''){
		if(IS_POST){
				$title       = op_t(trim($title));
				$description = op_t(trim($description));
				$id			 = I('id');
				$address	 = op_t(trim($address));	
				if($id=='')	$this->error('参数错误!');
				if(strlen($title)>30) $this->error('标题10汉字以内!');
				if(strlen($description)>30) $this->error('标题10汉字以内!');
				if($title=='') $this->error('请填写标题');
				if($description=='') $this->error('请填写描述!');
				$this->check_url($address);
					$data=array(
						 'title'		=>	$title,
						 'description'	=>	$description,
						 'address'		=>	$address
					);
					
				$list	=	D('websit_product_config')->where("id=".$id)->save($data);
				
				if($list){
					$this->success('修改成功','refresh');
				}else{
					$this->error('修改失败');
				}
		
		
		}else{
				$list	=	D('websit_product_config')->where("id=".I('id'))->find();
				$address = get_citys($list['address']);
				
				$content_address['district'] = $address['district'];
				$content_address['city'] = $address['city'];
				$content_address['province'] = $address['province'];
			
			$this->assign('content_address', $content_address);
			$this->assign('list',$list); 
			$this->display();
		}
	}
	/*是否禁用产品设置dlx-2014-11-25*/
	public function product_config_disable(){
	     $list	=	D('websit_product_config')->where("id=".$_POST['id'])->save(array('status'=>$_POST['status']));
		 if($list){
			$this->success('操作成功!');
		 }else{
			$this->error('操作失败!');
		 }
	
	}
	/*标签配置--2014-11-25--dlx-*/
	public function tags_doConfig($tag=''){
	   if(IS_POST){
	  
			if(count($tag)>5) $this->error('亲!最多选择5个标签');
			$tag = implode(',',$_POST['tag']);
			$content['tag'] = $tag;
			
			$list	=	D('websit')->where("siteid=".SITEID)->save($content);
			
			if($list){
				$this->success('操作成功','refresh');
			}else{
				$this->error('操作失败');
			}
	    }
	    
	}
	
    public function data_statistics(){
		$status=$_GET['status'];
		$status = isset($status)? $status:0;
		switch($status){

			case 0:			
			/*111111111111111111111111111111111111会员统计部分begin111111111111111111111111111111111*/
				/****历史注册会员总计****/
				$allmember = D('member')->where(array('siteid'=>SITEID))->count();
				/****当月注册会员总计****/
				$map = "siteid = ".SITEID." and MONTH(FROM_UNIXTIME(reg_time,'%Y-%m-%d')) = MONTH(NOW()) and YEAR(FROM_UNIXTIME(reg_time,'%Y-%m-%d')) = YEAR(NOW())";				
				$monthmember = D('member')->where($map)->count();
				/****当天注册会员总计****/
				$where = "siteid = ".SITEID." and DAYOFYEAR(FROM_UNIXTIME(reg_time,'%Y-%m-%d')) = DAYOFYEAR(NOW()) and YEAR(FROM_UNIXTIME(reg_time,'%Y-%m-%d')) = YEAR(NOW())";
				$daymember = D('member')->where($where)->count();
				$this->assign('allmember',$allmember);
				$this->assign('monthmember',$monthmember);
				$this->assign('daymember',$daymember);
			/*111111111111111111111111111111111111会员统计部分end111111111111111111111111111111111111*/
				
						
			/*222222222222222222222222222222222222线路统计部分begin2222222222222222222222222222222222*/
				/****线路总数统计****/
				$allevent = D('event')->where(array('siteid'=>SITEID))->count();
				/****当天线路统计****/
				$map = "siteid = ".SITEID." and DAYOFYEAR(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = DAYOFYEAR(NOW()) and YEAR(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = YEAR(NOW())";
				$dayevent = D('event')->where($map)->count();
				$this->assign('allevent',$allevent);
				$this->assign('dayevent',$dayevent);
			/*222222222222222222222222222222222222线路统计结束end222222222222222222222222222222222222*/
			
						
			/*333333333333333333333333333333333333故事统计部分begin3333333333333333333333333333333333*/
				/****故事总数统计****/
				$allstory = D('issue_content')->where(array('siteid'=>SITEID))->count();
				/****当天故事统计****/
				$map = "siteid = ".SITEID." and DAYOFYEAR(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = DAYOFYEAR(NOW()) and YEAR(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = YEAR(NOW())";
				$daystory = D('issue_content')->where($map)->count();
				$this->assign('allstory',$allstory);
				$this->assign('daystory',$daystory);
			/*333333333333333333333333333333333333故事统计部分end333333333333333333333333333333333333*/
			
			
			/*444444444444444444444444444444444444排期统计部分begin4444444444444444444444444444444444*/
				/****排期总数统计****/
				$allschedule = D('event_calendar_time')->where(array('siteid'=>SITEID))->count();
				/****当天排期统计****/
				$map = "siteid = ".SITEID." and DAYOFYEAR(FROM_UNIXTIME(time,'%Y-%m-%d')) = DAYOFYEAR(NOW()) and YEAR(FROM_UNIXTIME(time,'%Y-%m-%d')) = YEAR(NOW())";
				$dayschedule = D('event_calendar_time')->where($map)->count();
				$this->assign('allschedule',$allschedule);
				$this->assign('dayschedule',$dayschedule);
			/*444444444444444444444444444444444444排期统计部分end444444444444444444444444444444444444*/
			
			
			/*555555555555555555555555555555555555活动订单统计部分begin555555555555555555555555555555*/
				/****活动订单总数统计****/
				$allorder = D('event_attend')->where(array('siteid'=>SITEID))->count();
				/****当天活动订单统计****/
				$map = "siteid = ".SITEID." and DAYOFYEAR(FROM_UNIXTIME(creat_time,'%Y-%m-%d')) = DAYOFYEAR(NOW()) and YEAR(FROM_UNIXTIME(creat_time,'%Y-%m-%d')) = YEAR(NOW())";
				$dayorder = D('event_attend')->where($map)->count();
				$this->assign('allorder',$allorder);
				$this->assign('dayorder',$dayorder);
			/*555555555555555555555555555555555555活动订单统计部分end55555555555555555555555555555555*/
			
			
			/*666666666666666666666666666666666666评论统计部分begin6666666666666666666666666666666666*/
				/****评论总数统计****/
				$allcomment = D('local_comment')->where(array('siteid'=>SITEID))->count();
				/****当天评论总数****/
				$map = "siteid = ".SITEID." and DAYOFYEAR(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = DAYOFYEAR(NOW()) and YEAR(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = YEAR(NOW())";
				$daycomment = D('local_comment')->where($map)->count();
				$this->assign('allcomment',$allcomment);
				$this->assign('daycomment',$daycomment);
			/*666666666666666666666666666666666666评论统计部分end666666666666666666666666666666666666*/
			
			break;
			case 1;
				/*********************商品统计部分*******************************/
				$allgoods = D('shop')->where(array('siteid'=>SITEID))->count();//商城货物总数
				$ongoods = D('shop')->where(array('siteid'=>SITEID,status=>1))->count();//商城在售货物总数
				$outgoods = D('shop')->where(array('siteid'=>SITEID,status=>2))->count();//商城下架货物总数
				$offgoods = D('shop')->where(array('siteid'=>SITEID,status=>0))->count();//商城禁用货物总数
				$this->assign('allgoods',$allgoods);
				$this->assign('ongoods',$ongoods);
				$this->assign('outgoods',$outgoods);
				$this->assign('unorder',$unorder);
				$this->assign('offgoods',$offgoods);
				$this->assign('order',$order);
				$this->assign('final_order',$final_order);
				/*********************商品统计部分结束*******************************/
				/***************今日概括*********************/
				$map2 = "siteid = ".SITEID." and DAYOFYEAR(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = DAYOFYEAR(NOW()) and YEAR(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = YEAR(NOW())";

				$todayorder = D('shop_ordersn')->where($map2)->count();
				$todayprice = D('shop_ordersn')->where($map2)->sum('alltotalprice');
				if(!$todayprice){$todayprice=0;}
				$todayprice=sprintf("%.2f",  $todayprice); 
				if($todayorder==0){
						$guest_unit_price=0;
				}else{
					$guest_unit_price=sprintf("%.2f",  $todayprice/$todayorder); //保留两位
				}
				$this->assign('allgoods',$allgoods);
				$this->assign('todayorder',$todayorder);
				$this->assign('todayprice',$todayprice);
				$this->assign('guest_unit_price',$guest_unit_price);
				/***************今日概括结束*********************/
			break;
		}
		$this->assign('status',$status);
		
		$this->display();
    }	
	
	
	
	
	
	
	
	
	/*添加QQ客服*/
	public function customer_service_doAdd($qq,$nickname){
	    if(!is_numeric($qq)){
		   $this->error('客服QQ必须为数字哦');
		}
		$data['url']="http://wpa.qq.com/msgrd?v=3&uin=" .$qq. "&site=qq&menu=yes";
	    $data['qq']=$qq;
		$data['nickname']=$nickname;
		$data['siteid']=SITEID;
		$data['uid']=is_login();
		D('customer_service')->create($data);
		$cate=D('customer_service')->add();
		if($cate){
		  $this->success('添加成功','refresh');
		}else{
		  $this->error('添加失败');
		}
	}
	/*修改QQ服务*/
	public function customer_service_edit(){
	    $customer=D('customer_service')->where("id=".I('id'))->find();
		$this->assign('customer',$customer);
		$this->display();
	
	}
	/*执行修改QQ客服*/
	public function customer_service_doEdit($id,$qq,$nickname){
			if(!is_numeric($qq)){
			   $this->error('客服QQ必须为数字哦');
			}
		$data['url']="http://wpa.qq.com/msgrd?v=3&uin=" .$qq. "&site=qq&menu=yes";
		$data['qq']=$qq;
		$data['nickname']=$nickname;
		$reds=D('customer_service')->where("id=".$id)->save($data);
			if($reds){
			   $this->success('修改成功','refresh');
			}else{
			   $this->error('修改失败');
			}
	}
	/*是否禁用QQ客服*/
	public function customer_is_disable(){
	     $count=D('customer_service')->where("status=1 and siteid=".SITEID)->count();
		 if($count<3){
	     $data['status']=I('status');
		 $customer=D('customer_service')->where("id=".I('id'))->save($data);
			 if($customer){
				$this->success('更改成功');
			 }else{
				$this->error('更改失败');
			 }
	        }else{
			  $this->success('亲!最多可启用3个哦');
			}
	}
	/*取消禁用QQ客服*/
	public function customer_cancel_disable(){
	      $data['status']=I('status');
		  $customer=D('customer_service')->where("id=".I('id'))->save($data);
			 if($customer){
				$this->success('更改成功');
			 }else{
				$this->error('更改失败');
			 }
	
	}
	/*首页推荐领队*/
	public function doRecommendm(){
	        $count=D('member')->where("recommendm = 1 and siteid=".SITEID)->count();
			if($count <3){
				 $data['recommendm']=I('recommendm');
				 $reds=D('member')->where(" uid =".I('uid'))->save($data);
				 if($reds){
					$this->success('操作成功');
				 }else{
					$this->error('操作失败');
				 }
			}else{
			    $this->success('亲!最多推荐3个领队哦');
			}
	
	}
	/*取消推荐*/
	public function cancelRecommendm(){
		$data['recommendm']=I('recommendm');
		$reds=D('member')->where(" uid =".I('uid'))->save($data);
			 if($reds){
				$this->success('操作成功');
			 }else{
				$this->error('操作失败');
			 }
	
	}
	
	/*网站配置*/
	public function do_webconfig($siteid=null,$url='',$slogan='',$cover_id='',$logo_icons='',$email='',$telphone=0,$webname,$domain,$user_agreement=''){
        
		if($webname==''){
			$this->error('请填写网站名称');
		}
		if($cover_id==''){
			$this->error('请上传网站导航LOGO');
		}
		
		if($logo_icon==''){
			//$this->error('你还没有上传网站ICO图片');
		}
		if($email==''){
			$this->error('请输入正确的email');
		}
		if($telphone==''){
			$this->error('400电话不能为空');
		}
		if($url==''){
			$this->error('请输入二级域名前缀');
		}
		
		$oldurl=D('websit')->where("siteid !=".$siteid." and url='{$url}'")->find();
		if($oldurl){
		   $this->error('该域名已被注册！');
		}
		
		$data['webname']=$webname;
		$data['domain']=$domain;
		
		$data['uid']=is_login();
		$data['url']=$url;
		$data['email']=$email;
		$data['telphone']=$telphone;
		$data['cover_id']=$cover_id;
		$data['logo_icons']=$logo_icons;
		$data['slogan'] = $slogan;
		$data['user_agreement'] = $user_agreement;
        $cate=D('websit')->where("siteid = ".SITEID)->save($data);
		
	   if($cate){
			$this->success('修改成功',U('Websit/Index/index',array('status'=>0)));
	   }else{
			$this->error('修改失败');
	   }
		
	}
	
	
	/*常驻企业*/
	public function information_add(){
		$this->display();
	}
	/*---企业添加--*/
	public function information_Doadd($company_name,$url){
	    if(IS_POST){
		   $company_name=op_t($company_name);
		   if($company_name==''){
		     $this->error('请输入名称');
		   }
		   $this->check_url($url);
		   $data['company_name']=$company_name;
		   $data['url']=$url;
		   $data['uid']=is_login();
		   $data['siteid']=SITEID;
		    
		   $de=D('enterprises')->where("siteid=".SITEID." and uid=".is_login())->select();
		    if(count($de)<4){
			   
			   $list=D('enterprises')->where("siteid=". SITEID ." and company_name="."'$company_name'")->find();
		       if(!$list){
				  D('enterprises')->create($data);
				  $res=D('enterprises')->add();
					  if($res){
						$this->success('添加成功',U('Websit/Index/index',array('status'=>5)));
					  }else{
						$this->error('添加失败');
					  }
			    }else{
				   $this->error('你已添加过');
			    }
		   }else{
		        $this->success('亲!最多只能添加4条数据喔','refresh');
		   }
		
		}
	}
	/*edit修改*/
	public function information_edit(){
		  $id=I('id');
		  $res=D('enterprises')->where("id=$id")->find();
		  $this->assign('data',$res);
		  $this->assign('id',$id);
		  $this->display();
		 
	}
	
	public function information_doEdit($id,$url,$company_name){
	       if(IS_POST){
		    if($company_name==''){
			    $this->error('请填写企业名');
			 }
			  $this->check_url($url);
		      $data['company_name']=$company_name;
			  $data['url']=$url;
			  $res=D('enterprises')->where("id=$id")->save($data);
			  if($res){
			    $this->success('修改成功',U('Websit/Index/index',array('status'=>5)));
			  }else{
			    $this->success('未更改数据');
			  }
		   }
	}
	
	/*页脚导航是否禁用*/
	public function information_disable(){
	   $data['status']=I('status');
	   $res=D('enterprises')->where("id=".I('id'))->save($data);
			if($res){
			   $this->success('操作成功');
			}else{
			   $this->error('操作失败');
			}
    }
	/*add*/
    public function share_doAdd($url,$cover_logo){
	    if(IS_POST){
		   $cover_logo=op_t($cover_logo);
		   if($cover_logo=='') $this->error('请输入分享名称');
		   $this->check_url($url);
		   $data['cover_logo']=$cover_logo;
		   $data['url']=$url;
		
		   $uid=is_login();
		   $data['uid']=$uid;
		   $data['siteid']=SITEID;
		   
		   $res=D('share')->where("siteid =".SITEID)->select();
		   if(count($res)<5){
		      D('share')->create($data);
			  $gds=D('share')->add();
			  if($gds){
			    $this->success('添加成功',U('Websit/Index/index',array('status'=>2)));
			  }else{
			    $this->error('添加失败');
			  }
		   
		   }else{
		      $this->error('最多可以添加五条');
		   }
		
		}
	
	}
	/*share_edit*/
	public function share_edit(){
	     $id=I('id');
		 $res=D('share')->where("id=$id")->find();
		 $this->assign('reds',$res);
	     $this->display();
	
	}
	
	/*分享到*/
	public function share_doEdit($url,$cover_logo){
	     $id=I('id');
		 $cover_logo=I('cover_logo');
		 $url=I('url');
		 
		
		 if($cover_logo==''){
		    $this->error('请选择');
		 }
		 $this->check_url($url);
		 $data['url']=$url;
		 $data['cover_logo']=$cover_logo;
		 
		 $cate=D('share')->where("id=$id")->save($data);
		 if($cate){
		    $this->success('更新成功',U('Websit/Index/index',array('status'=>2)));
		 }else{
		    $this->success('未更新数据',U('Websit/Index/index',array('status'=>2)));
		 }
	}
	/*分享是否禁用--*/
	public function share_disable(){
	    $data['status']=I('status');
		$res=D('share')->where("id=".I('id'))->save($data);
		if($res){
		  $this->success('操作成功');
		}else{
		  $this->error('操作失败'); 
		}
	}
	/*---页脚--*/
	public function do_wefooter($logo_footer='',$icp,$micro='',$weibo='',$qq=''){
	      if(IS_POST){
		    if($logo_footer==''){
			   $this->error('请上传页脚logo图片');
			}
			if($micro==''){
			   $this->error('请填写微信号码');
			}
			if($weibo==''){
			   $this->error('请填写微博号码');
			}
			
			$data['logo_footer']=$logo_footer;
		    $data['icp']=$icp;
			$data['weibo']=$weibo;
			$data['qq']=$qq;
			$data['micro']=$micro;
			$data['uid']=is_login();
			
			$res=D('websit')->where("siteid=".SITEID)->find();
			if($res){
			    $cate=D('websit')->where("siteid=".SITEID)->save($data);	
			   if($cate){
				  $this->success('添加成功','refresh');
				}else{
				  $this->error('添加失败');
				}			   
			}else{
			   $data['siteid']=SITEID;
			   $uid=is_login();
			   $fo_add=D('websit')->data($data)->add();
			   if($fo_add){
			     $this->success('添加成功','refresh');
			   }else{
			     $this->error('添加失败');
			   }
			}
	    }
	}
	
	/*关于我们--弹窗--*/
	public function about_add(){
	    $this->assign('user',$this->userdata);
	    $this->display();
	}
	
	/*关于我们*/
    public function doAbout($content='',$title='',$siteid=null){
	    if(IS_POST){
		    $title=op_t($title);
		    if($title==''){
			  $this->error('请填写类别名称');
			}
		    if($content==''){
			  $this->error('请填写内容');
			}
			$siteid=SITEID;
			$uid=is_login();
            $data['content']=$content;
			$data['title']=$title;
			$data['siteid']=$siteid;
			$data['uid']=$uid;
			
			D('about')->create($data);
			$cate=D('about')->add();
			if($cate){
			   $this->success('添加成功',U('Websit/Index/content',array('status'=>1)));
			}else{
			   $this->error('添加失败');
			}
		
        }
	}
	
	/*关于我们--修改---*/
	public function about_edit(){
	        $id=I('id');
			$list=D('about')->where("siteid=".SITEID." and id=".$id)->find();
			$this->assign('list',$list);
			$this->assign('user',$this->userdata);
			$this->display();
    }
	
	public function about_doEdit($id,$content,$title){
		if(IS_POST){
		 $title=op_t($title);
		  if($title==''){
			  $this->error('请填写类别名称');
			}
	      if($content==''){
			  $this->error('请填写内容');
			}
		 $data['title']=$title;
		 $data['content']=$content;
	 
		 $res=D('about')->where("id=$id")->save($data);
		  if($res){
			  $this->success('更改成功',U('Websit/Index/content',array('status'=>1)));
		  }else{
			  $this->success('未更新数据');
		  }	 
	    } 
	 
	}
	
	//--是否禁用---关于我们----
	public function about_disable(){
		$data['status']=I('status');
		$reds=D('about')->where("id=".I('id'))->save($data);
			if($reds){
			   $this->success('操作成功');
			}else{
			   $this->error('操作失败');
			}
	}
	
	/*--评论管理--*/
   public function review(){
		  $list=D('local_comment')-> where("siteid = ".SITEID)->order('create_time desc')->select();
		  $this->assign('list',$list);
		  $this->assign('user',$this->userdata);
		  $this->display();
	}
	
	/*review_delete*
	*/
	public function review_delete(){
	      $id=$_GET['id'];
	      $data=M('local_comment')->where("id=$id")->delete();
		  if($data){
		    $this->redirect('Websit/Index/review');
		  }else{
		    $this->redirect('Websit/Index/review');
		  }
	
	}
	
	/*param[review_edit]*/
	public function review_edit(){
	   $id=I('id');
	   $list=M('local_comment')->where("id=$id")->find();
	   $this->assign('list',$list);
	   $this->display();
	}
	
	/*---评论管理执行修改--*/
	public function review_doEdit(){
	      $id=I('id');
		  $data['content']=I('content');
		  $reds=M('local_comment')->where("id=$id")->save($data);
		  if($reds){
		    $this->success('更新成功','refresh');
		  }else{
		    $this->success('未更新数据');
		  }
	
	}
	/*账务管理*/
	public function financial_management(){
	         $status=I('status');
			 switch($status){
			    case 0://账务账号
				    $list=D('financial')->where("siteid=".SITEID)->select();
					$this->assign('data',$list);
				break;
				case 1://支付记录
				  // $this->all_payment_records();
				break;
			    }
	    
		$this->assign('status',$status);
		$this->assign('user',$this->userdata);
		$this->display();
    }
	//--财务账号添加add
	public function financial_account_doAdd($card,$open_bank,$payee){
	     if(IS_POST){
		    if($payee==''){
			  $this->error('请填写收款人');
			}
			if($card==''){
			  $this->error('请填写卡号');
			}
			if($open_bank==''){
			  $this->error('请填写开户行');
			}
			$data['payee']=$payee;
			$data['card']=$card;
			$data['open_bank']=$open_bank;
			$data['siteid']=SITEID;
			$data['uid']=is_login();
			
			$des=D('financial')->data($data)->add();
			if($des){
			   $this->success('添加成功','refresh');
			}else{
			   $this->error('添加失败');
			}
		 
		 }
	
	} 
	/*财务账号修改edit*/
	public function financial_account_edit(){
	        $list=D('financial')->where("id=".I('id'))->find();
			$this->assign('list',$list);
	        $this->display();
	}
	/*执行财务账号修改doEdit*/
    public function financial_account_doEdit($id,$payee,$card,$open_bank){
	       if(IS_POST){
		     if($payee==''){
			    $this->error('请填写收款人');
			 }
			 if($card==''){
			    $this->error('请填写卡号');
			 }
			 if($open_bank==''){
			    $this->error('请填写开户银行');
			 }
			 $data['payee']=$payee;
			 $data['card']=$card;
			 $data['open_bank']=$open_bank;
		    
			 $res=D('financial')->where("id=$id")->save($data);
			 if($res){
			    $this->success('更改成功','refresh');
			 }else{
			    $this->success('未更改数据');
			 }
		   }
	}
	/*是否禁用-账务账号-*/
	public function card_disable(){
	       $data['status']=I('status');
	       $cate=D('financial')->where("id=".I('id'))->save($data);
		   if($cate){
		     $this->success('操作成功');
		   }else{
		      $this->readdir('操作失败');
		   }
	}
	/*账务--支付记录-select*/
	public function all_payment_records(){
			$map['siteid']=SITEID;
			$count=D('pay_account')->where($map)->count();
			$Page       = new \Think\Page($count,10);
			$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
			$show       = $Page->show();// 
			$infos = D('pay_account')->where($map)->limit($Page->firstRow .','.$Page->listRows)->order('id desc')->select();
			if (is_array($infos) && !empty($infos)) {
				foreach($infos as $key=>&$info) {
					$list=D('event_attend')->where("siteid=".SITEID." and trade_sn= ".$info['order_id'])->find();
					if($info['order_paytype']==0){
						$infos[$key]['pay_text'] = '定金';
					}elseif($info['order_paytype']==1){
						$infos[$key]['pay_text'] = '余额';
					}elseif($info['order_paytype']==2){
						$infos[$key]['pay_text'] = '全额';
					}
				}
			}
        
		$this->assign('pay_account',$infos);
		$this->assign('page',$show);
	}
	/*保险管理--2014-10-11 pm**/
	public function insurance_management(){
	     $status=I('status');
			 switch($status){
				case 0:
					$list=D('insurance')->where("siteid=".SITEID)->select();
					$this->assign('insurance',$list);
				break;
			  }
	 $this->assign('user',$this->userdata);
	 $this->assign('status',$status);
	 $this->display();
	
	}
	/*添加保险*/
	public function insurance_doAdd($name,$sum_insured,$price){
        if(IS_POST){
		    $name=op_t($name);
		    $sum_insured=op_t($sum_insured);
		    $price=op_t($price);
		    if($name=='') $this->error('请填写保险名称');
		    if($sum_insured=='') $this->error('请填写正确的保额哦');
		    if($price=='') $this->error('请填写正确的保额哦!');
			
			$data['name']=$name;
			$data['sum_insured']=$sum_insured;
			$data['price']=$price;
			$data['time']=time();
			$data['siteid']=SITEID;
			
		    D('insurance')->create($data);
			$list=D('insurance')->add();
				
				if($list){
					$this->success('添加成功','refresh');
				}else{
					$this->error('添加失败');
				}
	    }
	}
	/*修改保险信息*/
	public function insurance_edit(){
	       $list=D('insurance')->where("id=".I('id'))->find();
		   $this->assign('data',$list);
		   $this->display();
	}
	/*执行修改保险信息*/
	public function insurance_doEdit($id,$name,$sum_insured,$price){
	    if(IS_POST){
		    $name=op_t($name);
		    $sum_insured=op_t($sum_insured);
		    $price=op_t($price);
			if($id=='')  $this->error('参数错误');
		    if($name=='') $this->error('请填写保险名称');
		    if($sum_insured=='') $this->error('请填写正确的保额哦');
		    if($price=='') $this->error('请填写正确的保额哦!');
			
			$data['name']=$name;
			$data['sum_insured']=$sum_insured;
			$data['price']=$price;
			
			$reds=D('insurance')->where("id=".$id)->save($data);
		          if($reds){
				      $this->success('更改成功','refresh');
				  }else{
				      $this->error('更改失败');
				  }
		}
		   
		   
	}
   /*是否禁用保险*/
   public function insurance_disable(){
          $data['status']=I('status');
		  $reds=D('insurance')->where("id=".I('id'))->save($data);
			  
			  if($reds){
			      $this->success('操作成功');
			  }else{
			      $this->error('操作失败');
			  }
   
   }
	/*kv--管理*/

	public function websit_picture_add(){
		
		if(IS_POST){
			
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
			if($post['id']){
				$id  = $post['id'];
				unset($post['id']);
				$post['siteid'] =SITEID;
				$res = D('advs')->where('id = '.$id )->save($post);
				if($res){
					$this->success('修改成功',U('Websit/Index/index',array('status'=>1)));
				}else{
					$this->error('修改失败');
				}
			}else{
				
				$post['siteid'] =SITEID;
				$post['status'] =1;
				$res = D('advs')->add($post);
				if($res){
					$this->success('添加成功',U('Websit/Index/index',array('status'=>1)));
				}else{
					$this->error('添加失败');
				}
			}
			
		}else{
			$id  =   I('get.id','');
			$positions = D('advertising')->where("status = 1")->select();
			if($id){
				$detail = D('advs')->where("id =".$id)->find();
				$position_arr = D('advertising')->where("id = ".$detail['position'])->find();
				$detail['type'] = $position_arr['type'];
				$detail['create_time'] = date('Y-m-d H:i',$detail['create_time']);
				$detail['end_time'] = date('Y-m-d H:i',$detail['end_time']);
				$this->assign('info',$detail);
			}
			$this->assign('positions',$positions);
			$this->display();
		}
	}
	//kv-管理-禁用 启用
	public function websit_picture_status(){
	    
		$id = I('id');
		$data['status']=I('status');
		$pict_dis=D('advs')->where("id = ".$id)->save($data);
          if($pict_dis){
            $this->success('操作成功');
          }else{
			$this->error('操作失败');
		  }
	 }
	public function websitnav_status_status(){
	    
		$id = I('id');
		$data['status']=I('status');
		$pict_dis=D('channel_websit')->where("id = ".$id)->save($data);
          if($pict_dis){
            $this->success('操作成功');
          }else{
			$this->error('操作失败');
		  }
	 }	 
	public function websit_nav_edit(){
		
		if(IS_POST){
			$post = $_POST;
			if(!$post['title']) $this->error('请填写导航名称');
				$id  = $post['id'];
				unset($post['id']);
				$res = D('channel_websit')->where('id = '.$id )->save($post);
				if($res){
					$this->success('修改成功',U('Websit/Index/index',array('status'=>6)));
				}else{
					$this->error('修改失败');
				}
		}else{
			$id  =   I('get.id','');
			if($id){
				$detail = D('channel_websit')->where("id =".$id)->find();
				$this->assign('info',$detail);
			}
			$this->display();
		}
	}
	/*内容管理*/
	public function content(){
		  $status=I('status');
		  switch($status){
			case 0:/*官方公告*/
			     $notices=D('document')->where("status>=0 and siteid=".SITEID)->order("id desc")->select();
				 $this->assign('notices',$notices);
			break;
			case 1:/*关于我们*/
			   $cates=D('about')->where("siteid=".SITEID)->order("id asc")->select();
			   $this->assign('list',$cates);
			break;
			case 2:
			    /*服务企业*/
				$count=D('member_service')->where("status>=0 and siteid=".SITEID)->count();
				$Page       = new \Think\Page($count,10);
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
			    $show       = $Page->show();// 分页显示输出
				$list=D('member_service')->where("status>=0 and siteid=".SITEID) ->limit($Page->firstRow.','.$Page->listRows)->select();
				$this->assign('list',$list);
				$this->assign('page',$show);
			break;
			case 3:
			    /*评论管理*/
			    $count = D('local_comment')->where("status>=0 and siteid=".SITEID)->count();
				$Page       = new \Think\Page($count,10);
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
			    $show       = $Page->show();// 分页显示输出
			    $com=D('local_comment')->where("status>=0 and siteid=".SITEID)
				                       ->order("create_time desc")
								       ->limit($Page->firstRow.','.$Page->listRows)
									   ->select(); 
			   
			    $this->assign('event_com',$com);
				$this->assign('page',$show);
			break;
			case 4:
			    /*故事管理*/
			    $count=D('IssueContent')->where("status>=0 and siteid=".SITEID)->count();
				$Page       = new \Think\Page($count,10);
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				$show       = $Page->show();
			    $issue=D('IssueContent')->where("status>=0 and siteid=".SITEID)
				                        ->order("id desc")
										->limit($Page->firstRow.','.$Page->listRows)
										->select();
			    $tree = D('Issue')->where('siteid='.SITEID .' and customization=1')->getTree();
				
				$this->assign('tree', $tree);
			    $this->assign('story_comment',$issue);
				$this->assign('page',$show);
			break;			
			
			
			case 5:
			    /*公告类型*/
				 $tree = D('Category')->where("siteid=".SITEID." and status = 1")->getTree(0,'id,title,sort,pid,allow_publish,status,siteid');
				 $this->assign('tree', $tree);
				 C('_SYS_GET_CATEGORY_TREE_', true); //标记系统获取分类树模板
				 $this->meta_title = '分类管理';
		    break;
			case 6:
			   $event_all = D('event')->where(array('siteid'=>SITEID))->select();
				$count = count($event_all);
				$Page = new \Think\Page($count,10);
				$show = $Page->show();// 分页显示输出
				$eventall = D('event')->where(array('siteid'=>SITEID))
									->field(array('id','is_recommend','title','status'))
									->order("diff_time")
									->limit($Page->firstRow.','.$Page->listRows)
									->select();
				if($show != "<div class='pagination'>    </div>"){
					$this->assign('page',$show);
				}
				$this->assign('count',$count);
				$this->assign('event_all',$eventall);			
				     
				break;
			case 7:
			    /*故事类型*/
			    $tree = D('Issue')->where('siteid='.SITEID)->getTree();
			    $this->assign('tree', $tree);
			break;
			case 8:
			    $line=D('event_type')->where("siteid=".SITEID)->select();
				$this->assign('line_type',$line);
			break;

		    case 9:
				$count      =D('websit_video')->where("siteid=".SITEID)->count();
				$Page       = new \Think\Page($count,5);
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>
					总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
			    $show       = $Page->show();// 分页显示输出
				$video      =D('websit_video')->where("siteid=".SITEID)->limit($Page->firstRow.','.$Page->listRows)->select();
				$this->assign('video',$video);
				$this->assign('page',$show);

			break;
		  }
		$this->assign('status',$status);
		$this->assign('user',$this->userdata);
		
		if($status==6||$status==8){
			$this->display('content_event');
		}else{
			$this->display();
		}
	  
	}
	
	 /* 新增分类 */
    public function notice_type_add($pid = 0){
        $Category = D('Category');

        if(IS_POST){ //提交表单
            /*if(false !== $Category->update()){
                //$this->success('新增成功！', U('index'));
            } else {
                $error = $Category->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }*/
        } else {
            $cate['title']=I('title');
			$cate['id']=I('id');
            /* 获取分类信息 */
            $this->assign('category', $cate);
            $this->display('notice_type_edit');
        }
    }
	
    /* 编辑分类 */
    public function notice_type_edit($sort=0,$title='',$id = null, $pid = 0){
	    $Category = D('Category');
	    if(IS_POST){ //提交表单
				 if($pid!=0){       //---添加--子分类 ---
				        if($title==''){
						  $this->error('请填写分类名称');
						}
						if($sort!=''){
							if(!is_numeric($sort)){
							  $this->error('排序必须为数字');
							}
						}
						 $data['title']=$title;
						 $data['pid']=$pid;
						 $data['sort']=$sort;
						 $data['siteid']=SITEID;
						 $Category->create($data);
						 $notice=$Category->add();
						if($notice){
							$this->success('添加子分类成功',U('Websit/Index/content',array('status'=>5)));
						  }else{
							$this->error('添加子分类失败');
						  }
				 
				  }else{   
				             //----添加分类----
						 if($title==''){
							$this->error('请填写分类名称');
						 }
						 if($sort!=''){
						    if(!is_numeric($sort)){
							  $this->error('排序必须为数字');
							}
						}
						$find_name=$Category->where("siteid=".SITEID." and name='{$name}'")->find();
						
						
						 $data['sort']=$sort;
						 $data['title']=$title;
						 $data['pid']=$pid;
						 $data['siteid']=SITEID;
						 $Category->create($data);
						 $notice=$Category->add();
						if($notice){
							$this->success('添加成功',U('Websit/Index/content',array('status'=>5)));
						  }else{
							$this->error('添加失败');
						  }
                   }
		
		} else {
            $cate = '';
            if($pid){
                /* 获取上级分类信息 */
                $cate = $Category->info($pid, 'id,name,title,status');
                if(!($cate && 1 == $cate['status'])){
                    $this->error('指定的上级分类不存在或被禁用！');
                }
            }

            /* 获取分类信息 */
            $info = $id ? $Category->info($id) : '';

            $this->assign('info',       $info);
            $this->assign('category',   $cate);
            $this->meta_title = '编辑分类';
            //$this->display();
        }
    }
	/*修改公告*/
	public function edit_sign_notice(){
	      
		  $sign_notice = get_webinfo('sign_notice');
		  
		  $this->assign('sign_notice',$sign_notice);
		  $this->assign('list',$list);
	      $this->display();
	}
	/*执行修改*/
	public function do_edit_sign_notice($sign_notice){
	       
		  if(IS_POST){
		     $data['sign_notice']=$sign_notice;
			 $docus=D('websit')->where("siteid = ".SITEID)->save($data);
			 if($docus){
			    $this->success('更改成功',U('Websit/Index/content',array('status'=>6)));
			  }else{
			    $this->error('未更改数据');
			 }
		  
		  }
	
	}
	
	//--执行修改----
	public function notice_type_doedit($sort='',$title='',$id=0,$pid=0){
	         if(IS_POST){
				    if($title==''){
					  $this->error('请填写分类名称');
					}
					if($sort!=''){
						if(!is_numeric($sort)){
						  $this->error('排序必须为数字');
						}
					}
					$data['id']=$id;
					$data['pid']=$pid;
					$data['sort']=$sort;
					$data['title']=$title;
					$up_cate=D('category')->where("id = ".I('id'))->save($data);
					if($up_cate){
					   $this->success('更改成功','refresh');
					}else{
					   $this->success('未更改数据');
					}
				
			  }else{
				   $cate=D('category')->where("id=".I('id'))->find();
					   if($cate['pid']=='0'){
						  $this->assign('category','');
						  $this->assign('info',$cate);
					   }else{
						  $this->assign('category',$cate);
						  $this->assign('info',$cate);
						}
				    $this->display();
			   }
	
	}
	//---删除一级分类--
	public function content_notice_type_del(){
	        $id=I('id');
			$data['status']='-1';
			$cate=D('category')->where("pid=$id")->find();
			if($cate){
			   $this->error('有子类不能直接删除');
			}else{
			   $cate_del=D('category')->where("id=$id")->save($data);
			   if($cate_del){
			     $this->success('删除成功');
			   }else{
			     $this->error('删除失败');
			   }
			}
	
	}
	/*是否推荐品牌故事-2014-11-10-dlx*/
	public function recommend_brand(){
	    $data['recommend_brand']=$_POST['recommend_brand'];
		$issue = D('Issue_content')->where("id=".$_POST['id'])->save($data);
		if($issue){
			$this->success('操作成功');
		}else{
			$this->error('操作失败');
		}
	}
   /*是否推荐故事*/
	public function story_recommend(){
	     $data['is_recommend']=I('is_recommend');
		 $story=D('Issue_content')->where("id=".I('id'))->save($data);
		 if($story){
		    $this->success('操作成功');
		 }else{
		    $this->error('操作失败');
		 }
	
	}
	/*编辑故事*/
	public function story_comment_edit(){
	    $id=I('id');
	    $issue_content = D('IssueContent')->where(array('id'=>$id,'siteid'=>SITEID))->find();
        if (!$issue_content) {
            $this->error('404 not found');
        }
		
        $issue = D('Issue')->where(array('id' => $issue_content['issue_id'],'siteid'=>SITEID))->find();
		$event_content['tagarr'] = explode(',',$issue_content['tag']);
			foreach ($event_content['tagarr'] as $key => $a) {
				$issue_content['tags'][$a]['id'] = $a;
				$issue_content['tags'][$a]['name'] = get_event_tag($a);
			}
		if(app_isopen('Event')){
			$map = "status = 1 and siteid=".SITEID;
			$related_event = D('Event')->where($map)->order('id desc')->field('id,title')->select();
			if($related_event){
				foreach ($related_event as $key => $rs_r) {
					$related_event_list[$rs_r['id']] = $rs_r['title'];
				}
			}
			$this->assign('related_event_list',$related_event_list);
		}
			
		$cid = $issue_content['finalcity'];
		$citys = get_citys("$cid");
        $this->assign('top_issue', $issue['pid'] == 0 ? $issue['id'] : $issue['pid']);
        $this->assign('issue_id', $issue['id']);
		$this->assign('citys',$citys);
        $issue_content['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $issue_content['uid']);
        $this->assign('content', $issue_content);
	    $tree = D('Issue')->where('siteid='.SITEID)->getTree();
	    $this->assign('tree', $tree);
		$this->assign('story_url',$_SERVER['HTTP_REFERER']);
		$this->assign('user',$this->userdata);
	    $this->display();
	    
	}
   /*执行修改*/
   public function story_doPost($id = 0,$url='', $cover_id = 0, $issue='', $title = '', $final_city = '', $tag = '', $content = '',$related_event = ''){
        

		if (trim(op_t($title)) == '') {
            $this->error('请输入标题。');
        }       
        if (!$cover_id) {
            $this->error('请上传封面。');
        }	   
		if(!$issue){
			$this->error('请选择分类');
		}
		if($tag == ''){
			$this->error('请选择特色');
		}
		if($final_city == ''){
			$this->error('请完善目的地');
		}
		
        if (trim(op_h($content)) == '') {
            $this->error('请输入详情。');
        }
		
		
		if(app_isopen('Event')){
			if($related_event){
				$content['related_event'] = $related_event;
			}
		}
       
		$tag = implode(',',$tag);
        $content = D('IssueContent')->create();
		$content['tag'] = $tag;
		$content['issue_id'] = $issue;
		$content['finalcity'] = $final_city;
        $content['content'] = op_h($content['content']);
        $content['title'] = op_t($content['title']);
		$content['update_time'] = time();
        
       if ($id) {
            $rs = D('IssueContent')->where(array('id'=>$id,'siteid'=>SITEID))->save($content);
            if ($rs) {
                $this->success('编辑成功。',$url);
            } else {
                $this->success('未更改数据。', '');
            }
        }

    }
   /*是否禁用故事*/
   public function story_disable(){
     $data['status']=I('status');
     $story=D('Issue_content')->where("id=".I('id'))->save($data);
	 if($story){
	    $this->success('操作成功');
	 }else{
	    $this->error('操作失败');
	 }
   }
   /*--加载分类--*/
   public function story_type_add(){
        $this->display();
   }
   /*故事添加分类*/
   public function story_type_doAdd($sort=0,$title='',$id = null, $pid = 0){
      $Issue = D('issue');
	    if(IS_POST){ //提交表单
				 if($pid!=0){       //---添加--子分类 ---
				        if($title==''){
						  $this->error('请填写分类名称');
						}
						if($sort !=''){
							if(!is_numeric($sort)){
							  $this->error('排序必须为数字');
							}
						}
						 $data['title']=$title;
						 $data['pid']=$pid;
						 $data['sort']=$sort;
						 $data['siteid']=SITEID;
						 $Issue->create($data);
						 $notice=$Issue->add();
						if($notice){
							$this->success('添加子分类成功',U('Websit/Index/content',array('status'=>7)));
						  }else{
							$this->error('添加子分类失败');
						  }
				 
				  }else{   
				             //----添加分类----
						if($title==''){
							$this->error('请填写分类名称');
						}
						if($sort!=''){
							if(!is_numeric($sort)){
							  $this->error('排序必须为数字');
							}
						}
						 $data['sort']=$sort;
						 $data['title']=$title;
						 $data['pid']=$pid;
						 $data['siteid']=SITEID;
						 $Issue->create($data);
						 $notice=$Issue->add();
						if($notice){
							$this->success('添加成功',U('Websit/Index/content',array('status'=>7)));
						  }else{
							$this->error('添加失败');
						  }
                   }
		
		} 

   }
   /*修改故事分类*/
   public function story_type_edit(){
        $Issue=D('issue')->where("id=".I('id'))->find();
		$this->assign('info',$Issue);
		$this->display();
   }
   /*执行故事分类*/
   public function story_type_doEdit($sort='',$title='',$id=0,$pid=0){
		if(IS_POST){
		        if($title==''){
				  $this->error('请填写分类名称');
				}
				if($sort!=''){
					if(!is_numeric($sort)){
					  $this->error('排序必须为数字');
					}
				}
				$data['id']=$id;
				$data['pid']=$pid;
				$data['sort']=$sort;
				$data['title']=$title;
				$up_issue=D('issue')->where("id = ".I('id'))->save($data);
				if($up_issue){
				   $this->success('更改成功','refresh');
				}else{
				   $this->success('未更改数据');
				}
			
		  }else{
			   $cate=D('issue')->where("id=".I('id'))->find();
			       if($cate['pid']=='0'){
					  $this->assign('issue','');
					  $this->assign('info',$cate);
				   }else{
					  $this->assign('issue',$cate);
					  $this->assign('info',$cate);
					}
				$this->display();
		   }
	 
   }
   /*删除故事类型*/
   public function story_type_delete(){
             $data['status']='-1';
			 $Issue=D('issue')->where("id=".I('id'))->save($data);
			 if($Issue){
			    $this->success('禁用成功','refresh');
			 }else{
			    $this->error('禁用失败');
			 }
   
   }
   /*发布公公告*/
	public function notice_add(){
	    $this->assign('user',$this->userdata);
	    $this->display();
	}
	
	/*执行发布公公告*/
	public function notice_doAdd($category_id,$title,$content){
	               
			   if(IS_POST){
			     
					 if($title==''){
						$this->error('请填写标题');
					 }
					 if($content==''){
						$this->error('请填写公告内容');
					 }
					 if($category_id==''){
					    $this->error('未选择分类');
					 }
				 $data['category_id']=$category_id;	 
				 $data['title']=$title;
				 $data['create_time']=time();
				 $data['update_time']=time();
				 $data['siteid']=SITEID;
				 $data['status']=1;
				 $data['uid']=is_login();
				 
				  D('document')->create($data);
				 $docu=D('document')->add();
				 if($docu){
				        $cate['id']=$docu;
					    $cate['content']=$content;
					    $cate['siteid']=SITEID;
					    $docu_content=D('document_article')->data($cate)->add();
					    if($docu_content){
							$qrcode_url = set_qrcode(array('id'=>$docu),'blog');
							
								$qrcode_data = array(
										'siteid'  		=> SITEID,
										'uid'	  		=> is_login(),
										'linkid'  		=> $docu,
										'types'   		=> 'blog',
										'url'	 	 	=> $qrcode_url,
										'create_time'	=> time()
								);
							D('qrcode')->add($qrcode_data);
							  
							  $this->success('添加成功',U('Websit/Index/content',array('status'=>0)));
							}else{
							   $this->error('添加失败');
							}
					    }else{
						    $this->error('添加失败');
						}
			    }
		
	}
	/*修改公告*/
	public function notice_edit(){
	      $list=D('document_article')->Table(array('thinkox_document_article'=>'da','thinkox_document'=>'d'))
		                             ->where("da.id = d.id and d.id =".I('id'))
									 ->field('d.id,d.title,da.content')
									 ->find();
		  $this->assign('list',$list);
		  $this->assign('user',$this->userdata);
	      $this->display();
	}
	/*执行公告修改*/
	public function notice_doEdit($category_id,$title,$content){
	       
		  if(IS_POST){
		     $data['title']=$title;
			 $data['update_time']=time();
			 $data['category_id']=$category_id;
			 $data['update_time']=time();
			 $list=D('document')->where(" id =".I('id'))->save($data);
			 
			 $cate['content']=$content;
			 $docus=D('document_article')->where("id=".I('id'))->save($cate);
			 if($list || $docus){
			    $this->success('更改成功',U('Websit/Index/content',array('status'=>0)));
			  }else{
			    $this->success('未更改数据');
			 }
		  
		  }
	
	}
	/*改更状态*/
	public function content_notice_status(){
		$data['status']=I('status');
		$reds=D('document')->where("id=".I('id'))->save($data);
		if($reds){
			$this->redirect('Websit/Index/content?status=0');
		}else{
			$this->redirect('Websit/Index/content?status=0');
		}
	}
	/*公告推荐*/
	public function content_notice_recommend(){
		$data['is_recommend']=I('is_recommend');
		$reds=D('document')->where("id=".I('id'))->save($data);
		if($reds){
		   $this->redirect('Websit/Index/content?status=0');
		}else{
		   $this->redirect('Websit/Index/content?status=0');
		}
	}
	
	
	
	/*是否禁用公告*/
	public function content_notice_disable(){
	  $data['status']=I('status');
	  $reds=D('document')->where("id=".I('id'))->save($data);
		  if($reds){
			$this->success('操作成功');
		  }else{
			$this->error('操作失败');
		  }
	}

	/*修改评论内容*/
	public function control_comment_edit(){
	       $list=D('local_comment')->where("id = ".I('id'))->find();
		   $this->assign('list',$list);
		   $this->assign('comment_rul',$_SERVER ["HTTP_REFERER"]);
		   $this->assign('user',$this->userdata);
		   $this->display();
	}
	/*执行评论修改*/
	public function control_comment_doEdit(){
	      
	       $data['content']=I('content');
		   $event_coms=D('local_comment')->where("id = ".I('id'))->save($data);
		   $comment_rul=I('comment_url');
		   if($event_coms){
		      $this->success('更改成功',$comment_rul);
		   }else{
		      $this->success('未更改数据');
		   }
	
	}
	/*是否--禁用-评论*/
	public function control_disable(){
			  $data['status']=I('status');
			  $res=D('local_comment')->where("id=".I('id'))->save($data);
			  if($res){
				$this->success('操作成功');
			  }else{
				$this->error('操作失败');
			  }
	 }
	/**/ 
	public function manage_add(){
	 
	  $this->display();
	}
	public function service_add(){
		$rs = D('websit')->where(array('siteid'=>SITEID))->find();
		$this->assign('content',$rs);
		$this->display();
	}
	public function do_service_add($url){
		 if(!preg_match('/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',$url)){
			  $this->error('url不正确');
		   }
		  $data['service_process'] = $url;
		  $rs=D('websit')->where(array('siteid'=>SITEID))->save($data);
		  if($rs){
			$this->success('添加成功!',U('Websit/Index/content',array('status'=>2)));
		  }else{
			$this->error('添加失败！');
		  }	
	}
	public function do_service_edit($url){
		 if(!preg_match('/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',$url)){
			  $this->error('url不正确');
		   }
		  $data['service_process'] = $url;
		  $rs=D('websit')->where(array('siteid'=>SITEID))->save($data);
		  if($rs){
			$this->success('编辑成功',U('Websit/Index/content',array('status'=>2)));
		  }else{
			$this->error('编辑失败！');
		  }
	}
	public function manage_doAdd($title,$url){
			if(IS_POST){
			$title=op_t($title);
			if($title==''){
			  $this->error('公司名称不能为空');
		   }
		   if($url!=''){
			   if(!preg_match('/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',$url)){
				  $this->error('url不正确');
			   }
		   }
		   $data['uid']=is_login();
		   $data['siteid']=SITEID;
		   $data['title']=$title;
		   $data['url']=$url;
		 
		   D('member_service')->create($data);
		   $list=D('member_service')->add();
		   if($list){
			 $this->success('添加成功','refresh');
		   }else{
			 $this->error('添加失败');
		   }
		}
	}
	/*修改服务企业*/
	public function manage_edit(){
	     $id=$_GET['id'];
	     $list=D('member_service')->where("id=$id")->find();
	     $this->assign('list',$list);
	     $this->display();
	
	}
	/*执行修改服务企业*/
	public function manage_doEdit($title,$id,$url){
	    if(IS_POST){
		   $title=op_t($title);
		    if($title==''){
		     $this->error('公司名称不能为空');
	        }
			if($url!=''){
			   if(!preg_match('/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',$url)){
				  $this->error('url不正确');
				}
			}
			$data['title']=$title;
			$data['url']=$url;
			$cate=D('member_service')->where("id=$id")->save($data);
			if($cate){
			  $this->success('更该成功',U('Websit/Index/content',array('status'=>2)));
			}else{
			  $this->error('更改失败');
			}
		}
	
	}
	/*--是否禁用企业*/
	public function service_is_disable(){
	     $data['status']=I('status');
		 $res=D('member_service')->where("id=".I('id'))->save($data);
		 if($res){
		    $this->success('修改成功');
		 }else{
		    $this->error('修改失败');
		 }
	}
	/*会员管理*/
	public function member_manage(){
	       $status=I('status');
		   switch($status){
		      case 0:
			    $count=D('member')->where("siteid=".SITEID)->count();
				$Page       = new \Think\Page($count,10);// 
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				$show       = $Page->show();// 
			    $member=D('member')->where("siteid= ".SITEID)->limit($Page->firstRow.','.$Page->listRows)->select();
			    
				    foreach($member as $key=>$val){
				        $umeber=D('ucenter_member')->where("id=".$val['uid'])->find();
						$member[$key]['email']=$umeber['email'];
				       
				    }
				    $this->assign('member',$member);
					$this->assign('page',$show);// 
			  break;
			  case 1:
			     $recommendm=$_GET['recommendm'];
				 if($recommendm){
				    $map['recommendm']	= $recommendm;
				 }
				    $map['is_use']	= 	2;
					$map['siteid']	=	SITEID;
					
			     $count=D('member')->where($map)->count();
				 $Page       = new \Think\Page($count,10);// 
				 $Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				 $show       = $Page->show();// 
				 $member=D('member')->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
						foreach($member as $key=>$val){
							$user	=	query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $val['uid']);
							$member[$key]['email']=$user['email'];
						}
				
			       $this->assign('team',$member);
				   $this->assign('page',$show);//
			  break;
			  case 2://达人
					$map['is_use']	= 	4;
					$map['siteid']	=	SITEID;
				 $count=D('member')->where($map)->count();
				 $Page       = new \Think\Page($count,10);// 
				 $Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				 $show       = $Page->show();// 
				 $member=D('member')->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
						foreach($member as $key=>$val){
							$user	=	query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $val['uid']);
							$member[$key]['email']=$user['email'];
						}
				
				   $this->assign('master',$member);
				   $this->assign('page',$show);//
			  break;
		      case 3://角色 
					$count=D('member_upgrad_group')->where("siteid=".SITEID)->count();
					$Page       = new \Think\Page($count,10);// 
					$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
					$show       = $Page->show();// 
					$role	=	D('member_upgrad_group')->where("siteid=".SITEID)->limit($Page->firstRow.','.$Page->listRows)->select();
				      foreach($role as $k=>$v){
						$user	=	query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $v['uid']);
						$role[$k]['nickname']	= $user['nickname'];
						
					 }
					$this->assign('role_member',$role);
					$this->assign('page',$show);//
				
			  break;
		   }
		   $this->assign('status',$status);
		   $this->assign('user',$this->userdata);
		   $this->display();
	}
	/*更改用户状态*/
	public function member_manage_status(){
	       $status=I('status');
		   $data['status']=$status;
		   $member=D('member')->where(" uid =".I('id'))->save($data);
		   $ucenter=D('ucenter_member')->where('id='.I('id'))->save($data);
		   
		   if($ucenter && $member){
			  $this->success('更改成功');
		   }else{
			  $this->error('更改失败');
		   }
	}
	/*领队审核*/
	
	/*禁用--退出领队--2014-11-26*/
	public function manage_team_disable(){
	     $uid	=	$_POST['uid'];
		 if($uid=='') $this->error('参数错误!');
		 $data['recommendm']=0;
		 $data['is_use']	=1;
		 
		 $team_mem	=	D('member')->where(" uid =".$uid)->save($data);
			 if($team_mem){
				$this->success('操作成功');
			 }else{
				$this->error('操作失败');
			 }
	}
	
	/*添加线路类型*/
	public function line_type_add(){
	     if(IS_POST){
		    $title=$_POST['title'];
			$sort=$_POST['sort'];
			if($title=='') $this->error('线路类型不能为空');
			$event_types=D('event_type')->where('siteid='.SITEID)->select();
			
			if(count($event_types)<3){
			    $data['title']=$title;
				$data['sort']=$sort;
			    $data['siteid']=SITEID;
				$data['status']=1;
				$data['display']=1;
				$data['create_time']=time();
			    $event_add=D('event_type')->data($data)->add();
				if($event_add){
				   $this->success('添加成功','refresh');
				 }else{
				   $this->error('添加失败');
				 }
			
			}else{
			   $this->error('最多可以添加3条数据');
			
			}
		 
		 }else{
		 
		    $this->display();
		 }
	
	
	}
	/*修改线路分类*/
	public function line_type_edit(){
	     if(IS_POST){
		    $id=$_POST['id'];
			$title=trim($_POST['title']);
			$sort=$_POST['sort'];
			
			if($title=='') $this->error('请填写分类');
			$data['title']=$title;
			$data['sort']=$sort;
			$data['update_time']=time();
			$event_save=D('event_type')->where("id = $id")->save($data);
			if($event_save){
			   $this->success('修改成功','refresh');
			}else{
			   $this->error('修改失败');
			}
		 }else{
		   $id=$_GET['id'];
		   $event_type=D('event_type')->where('id='.$id)->find();
		   $this->assign('event_type',$event_type);
		   $this->display();
		 }
	
	}
	/*是否禁用*/
	public function line_type_del(){
	     $data['status']=$_POST['status'];
		 $id=$_POST['id'];
		 $event_find=D('event')->where("type_id=".$id." and siteid=".SITEID)->find();
		 if($event_find){
		    $this->error('不能删除');
		 }else{
		     $event_del=D('event_type')->where('id='.$id)->delete();
			 if($event_del){
			    $this->success('删除成功','refresh');
			  }else{
			    $this->error('该类下有发布活动,不能直接删除哦');
			  }
		}
		
	}
	/*得到分享数据*/
	public function check_share($uid){
	    return D('share')->where("uid=$uid")->select();
	}
	
	 /*删除活动*/
	 public function del_event($id = 0){
		if($id ==""){
			$this->error('未知的活动!');
		}
		$page = I('page');
		$data['status'] ='-1';
		$rs = D('Event')->where(array('id'=>$id))->save($data);
		if($rs){
			$this->redirect('Websit/Index/content',array('status'=>6,'page'=>$page));
		}else{
			$this->error('删除失败！');
		}
	}
	/*禁用或启用活动*/
	public function up_event($id = 0,$status = 0){
		if($id ==""){
			$this->error('未知的活动!');
		}
		$data['status'] = $status;
		if($status == 1){
			$str = '启用成功！';
			$str1 = '启用失败！';			
		}else{
			$str = '禁用成功！';
			$str1 = '禁用失败！';
			$data['is_recommend'] = 0;
		}
		$rs = D('Event')->where(array('id'=>$id,'siteid'=>SITEID))->save($data);
		if($rs){
			$this->success($str);
		}else{
			$this->error($str1);
		}
	}
	/*推荐活动*/
	public function event_recommend($id,$is_recommend){
	   $status = D('event')->where(array('siteid'=>SITEID,'id'=>$id))->getField('status');
	   if($status == 0){
			$this->error('该活动已被禁用，无法推荐！');
	   } 
	   $data['is_recommend'] = $is_recommend;
	   if($status == 1){
			$str = '推荐成功！';
			$str1 = '推荐失败！';			
		}else{
			$str = '推荐成功！';
			$str1 = '推荐失败！';
		}
	   $event=D('event')->where(array('id'=>$id,'siteid'=>SITEID))->save($data);
	   if($event){
	     $this->success($str);
	   }else{
	     $this->error($str1);
	   }
	
	}
	/*得到当前活动的所有排期*/
	public function event_schedule($id = 0){
		$uid = is_login();
		$rs = D('Event')->where(array('id' => $id,'siteid'=>SITEID))->find();
		if($rs){
			$content_arr = D('event_calendar_time')->where(array('eventid' => $rs['id'],'siteid'=>SITEID))->order('starttime desc')->select();
			$count = count($content_arr);
			$Page = new \Think\Page($count,10);
			$show = $Page->show();// 分页显示输出
			$content_arr = D('event_calendar_time')
							->where(array('eventid' => $rs['id'],'siteid'=>SITEID))
							->limit($Page->firstRow.','.$Page->listRows)
							->order('starttime desc')->select();
			$maxpeople = $rs['maxpeople'];
			foreach ($content_arr as $key=> &$v) {
				$v['id']= $v['id'];
				$v['ticket']= $maxpeople - $v['regnumber'];
				$v['starttime']= $v['starttime'];
				$v['endtime']= $v['endtime'];
				
				if($v['leader']){
					$leader_arr = explode(',',$v['leader']);
					$leaders ='';
					foreach ($leader_arr as $ku=> &$u) {
						$member = D('member')->where(array('uid' => $u))->find();
						if(!$member) continue;
						$leaders .='<a target="_blank" href="'.U('Usercenter/Index/index',array('uid'=>$member['uid'])).'">'.$member['nickname'].'</a> ';
					}	
					$v['leader'] =$leaders;
				}
				if($v['status'] <= 1){
						if($v['status'] == 1){
							if(strtotime("$v[endtime]")-time() > 0){
								if($v['maxpeople'] != 0){
									if(($v['maxpeople']-$v['regnumber']) < 0){
											$v['info'] = '<span style="color:green"><span style="color:red">报满</span>预约</span>';
									}else{
										$v['info'] = '<span style="color:green">接受报名</span>';
									}
								}else{
									$v['info'] = '<span style="color:green">接受报名</span>';
								}
							}else{
								if(strtotime("$v[starttime]")-time() > 0 ){
									$v['info'] = '<span style="color:red">报名截止</span>';
								}else{
									if(strtotime("$v[overtime]")-time() >= 0  && strtotime("$v[starttime]")-time() <= 0){
										$v['info'] = '<span style="color:red">进行中</span>';
									}elseif(strtotime("$v[overtime]")-time() < 0){
										$v['info'] = '<span style="color:red">已结束</span>';
									}
								}
							}
						}elseif($v['status'] == -1){
							$v['info'] = '<span style="color:red">已删除</span>';
						}
				}elseif($v['status'] == 2){
					$v['info'] = '<span style="color:green">接受报名</span>';
				}elseif($v['status'] == 3){
					$v['info'] = '<span style="color:green"><span style="color:red">报满</span>预约</span>';
				}elseif($v['status'] == 4){
					$v['info'] = '<span style="color:red">报名截止</span>';
				}elseif($v['status'] == 5){
					$v['info'] = '<span style="color:red">进行中</span>';
				}elseif($v['status'] == 6){
					$v['info'] = '<span style="color:red">已结束</span>';
				}
				
				if($v['vehicle']){
					$vehicle_arr = explode(',',$v['vehicle']);
					$vehicles='';
					foreach ($vehicle_arr as &$ve) {
						$vehicles .= get_vehicle($ve).' ';
					}
					$v['vehicle'] =$vehicles;
				}

				if($v['accommodation']){
					$accommodation_arr = explode(',',$v['accommodation']);
					$accommodations='';
					foreach ($accommodation_arr as &$ac) {
						$accommodations .= get_accommodation($ac).' ';
					}
					$v['accommodation'] =$accommodations;
				}
				
			}
		}
		if($show != "<div class='pagination'>    </div>"){
			$this->assign('page',$show);
		}
		$this->assign('event_content',$rs);
        $this->assign('contents',$content_arr);
		
        $this->display();
	}
	/*排期删除*/
	public function event_schedule_del($id=0)
	{
		if(!$id) exit('参数错误!');
		
		$data = D('event_calendar_time')->where(array('id'=>$id,'siteid'=>SITEID))->find();
		
		if(!$data) exit('排期不存在');
		
		if($data['uid'] !=is_login() && !checked_admin(is_login())) exit('权限不足');
		
		
		$ca_data['status'] = '-1';
		$updata = D('event_calendar_time')->where(array('id'=>$id,'siteid'=>SITEID))->save($ca_data);
		if($updata){
			echo 1;
		}else{
			exit('删除失败!');
		}
	}
	/*排期的编辑*/
	public function event_schedule_edit($id=0,$eventid=0)
  {
		$data['msg'] = '';
		$data['status'] = true;
		$rs = D('Event')->where(array('id' => $eventid,'siteid'=>SITEID))->find();
		if(!$rs){
			$data['msg'] = '活动不存在或已被删除';
			$data['status'] = false;
		}else{
			if($rs['uid']!=is_login()){
				$data['msg'] = '你的权限不足';
				$data['status'] = false;
			}else{
				$tiem_arr = D('event_calendar_time')->where(array('eventid' => $eventid,'id' => $id,'siteid'=>SITEID))->find();
			}
		}
		$map['status'] = 1;
		$map['is_use'] = 2;
		//$map['checked'] = 1;
		$map['siteid'] = SITEID;
		$member = D('member')->where($map)->select();
		if($member){
			foreach ($member as $ku=> &$u) {
				$get_leader[$u['uid']] =$u['nickname'];
			}
			$this->assign('get_leader', $get_leader);
		}
		$status = get_event_status();
		$this->assign('status',$status);
		$this->assign('event_content', $rs);
		$this->assign('data_msg', $data);
		$this->assign('content', $tiem_arr);
        $this->display();
    }
	/*增加排期*/
	public function event_schedule_add($id=0,$eventid=0)
  {
		$data['msg'] = '';
		$data['status'] = true;
		$rs = D('Event')->where(array('id' => $eventid,'siteid'=>SITEID))->find();
		if(!$rs){
			$data['msg'] = '活动不存在或已被删除';
			$data['status'] = false;
		}else{
			if($rs['uid']!=is_login()){
				$data['msg'] = '你的权限不足';
				$data['status'] = false;
			}
		}
		$map['status'] = 1;
		$map['is_use'] = 2;
		//$map['checked'] = 1;
		$map['siteid'] = SITEID;
		$member = D('member')->where($map)->select();
		if($member){
			foreach ($member as $ku=> &$u) {
				$get_leader[$u['uid']] =$u['nickname'];
			}
			$this->assign('get_leader', $get_leader);
		}
		$this->assign('event', $rs);
		$this->assign('data_msg', $data);
        $this->display();
    }
	/*增加排期*/

    public function do_event_schedule_add($eventid='',$price='',$starttime='',$endtime='',$days='',$minpeople='',$maxpeople='',$paytype = '',$deposit = '')
    {
			$insure_info = D('insurance')->where(array('siteid'=>SITEID,'status'=>1))->count();
			if($insure_info == 0){
				$this->error('请先添加活动保险再做操作！');
			}
			if(!$eventid) $this->error('参数错误！');
			$event_content = D('Event')->where(array('id' => $eventid,'siteid'=>SITEID))->find();
			if(!$event_content) $this->error('活动不存在或已被删除！');
			//if($event_content['uid']!=is_login()) $this->error('您的权限不足！');
			
			if(!$starttime) $this->error('请输入出发时间！');
			$schedule_data = D('event_calendar_time')->where(array('eventid' => $eventid,'uid' => $event_content['uid'],'starttime' => $starttime,'siteid'=>SITEID))->find();
			if($schedule_data) $this->error('当前排期已经存在，请添加其他日期！');
			
			if(!$endtime) $this->error('请输入截止时间！');
			if(strtotime($endtime) > strtotime($starttime)) $this->error('截止日期不能大于出发时间！');
			
			
			if(!$days) $this->error('请输入排期天数！');
			if(is_numeric($days) == '') $this->error('排期天数必须为纯数字');
			
			if(!$minpeople) $this->error('请输入最低人数！');
			if(is_numeric($minpeople) == '') $this->error('最低人数必须为纯数字');
			$maxpeople = intval($maxpeople);
			if($maxpeople != '' || $maxpeople != 0){
				if(is_numeric($maxpeople) == '') $this->error('队员上限必须为纯数字');
				if($minpeople > $maxpeople) $this->error('队员上限不能低于最低人数！');
			}
			$ca_data = D('event_calendar_time')->create();		
			if($paytype == ''){
				$this->error('请选择支付方式！');
			}else{
				switch($paytype){
					case 0;
						if(empty($price)){
							$this->error('请输入日程价格！');
						}else{
							if(is_numeric($price) == '') $this->error('排期价格必须为纯数字');
							if($price < 0.01) $this->error('排期价格不能少于 ￥0.01 ！');
						} 
						$ca_data['deposit'] = 0;						
					break;
					case 1;
						if(empty($price)){
							$this->error('请输入日程价格！');
						}else{
							if(is_numeric($price) == '') $this->error('排期价格必须为纯数字');
							if($price < 0.01) $this->error('排期价格不能少于 ￥0.01 ！');
							if(is_numeric($deposit) == '') $this->error('排期定金必须为纯数字');
							if($deposit < 0.01) $this->error('排期定金不能少于 ￥0.01 ！');
							if($price <= $deposit) $this->error('排期价格必须大于定金');
						}
					break;
					case 2;
						$ca_data['price'] = 0;
						$ca_data['deposit'] = 0;
					break;
				}
			}
			/*if(!$_POST['leader'][0]){
				$this->error('最少要选择一个领队！');
			}else{
				$ca_data['leader'] = implode(",",$_POST['leader']);
			}*/
			$ca_data['leader'] = implode(",",$_POST['leader']);
			if(!$_POST['vehicle'][0]){
				$this->error('请选择交通工具！');
			}else{
				$ca_data['vehicle'] = implode(",",$_POST['vehicle']);
			}
			
			if(!$_POST['accommodation'][0]){
				$this->error('请选择住宿条件！');
			}else{
				$ca_data['accommodation'] = implode(",",$_POST['accommodation']);
			}
			$ca_data['siteid'] = SITEID;	
			$ca_data['overtime'] = date('Y-m-d',strtotime($starttime) +(86400*($days-1)));
			$ca_data['uid'] = is_login();
			$ca_data['status'] = 1;
			$ca_data['time'] = time();	
			$ev_ca = D('event_calendar_time')->add($ca_data);
			if($ev_ca){
				$this->success('添加成功!',U('Websit/Index/event_schedule',array('id'=>$eventid)));
			}else{
				$this->error('添加失败！');
			}
    }
	/*修改排期**/
	//排期日期的修改
   	//排期日期的修改
    public function do_event_schedule_edit($status = 0,$id = 0,$eventid='',$price='',$starttime='',$endtime='',$days='',$minpeople='',$maxpeople='',$days_left = '',$paytype = '',$deposit = '')
    {
		if(!$id || !$eventid) $this->error('参数错误！');
		
		$event_content = D('Event')->where(array('id' => $eventid,'siteid'=>SITEID))->find();
		if(!$event_content) $this->error('活动不存在或已被删除！');
		//if($event_content['uid']!=is_login()) $this->error('您的权限不足！');
				
		if(!$starttime) $this->error('请输入出发时间！');
		$schedule_data = D('event_calendar_time')->where(array('eventid' => $eventid,'uid' => $event_content['uid'],'id' => $id,'siteid'=>SITEID))->find();
		if(!$schedule_data) $this->error('排期已经被删除！');
		if(!$endtime) $this->error('请输入截止时间！');
		if(strtotime($endtime) > strtotime($starttime)) $this->error('截止日期不能大于出发时间！');
		
		if(!$days) $this->error('请输入排期天数！');
		if(is_numeric($days) == '') $this->error('排期天数必须为纯数字');
		
		if(!$minpeople) $this->error('请输入最低人数！');
		if(is_numeric($minpeople) == '') $this->error('最低人数必须为纯数字');
		$maxpeople = intval($maxpeople);
		if($maxpeople != '' || $maxpeople != 0){
			if(is_numeric($maxpeople) == '') $this->error('队员上限必须为纯数字');
			if($minpeople > $maxpeople) $this->error('队员上限不能低于最低人数！');	
		}
		$ca_data = D('event_calendar_time')->create();	
		if($paytype == ''){
			$this->error('请选择支付方式！');
		}else{
			switch($paytype){
				case 0;
					if(empty($price)){
						$this->error('请输入日程价格！');
					}else{
						if(is_numeric($price) == '') $this->error('排期价格必须为纯数字');
						if($price < 0.01) $this->error('排期价格不能少于 ￥0.01 ！');
					} 
					$ca_data['deposit'] = 0;						
				break;
				case 1;
					if(empty($price)){
						$this->error('请输入日程价格！');
					}else{
						if(is_numeric($price) == '') $this->error('排期价格必须为纯数字');
						if($price < 0.01) $this->error('排期价格不能少于 ￥0.01 ！');
						if(is_numeric($deposit) == '') $this->error('排期定金必须为纯数字');
						if($deposit < 0.01) $this->error('排期定金不能少于 ￥0.01 ！');
						if($price <= $deposit) $this->error('排期价格必须大于定金');
					}
				break;
				case 2;
					$ca_data['price'] = 0;
					$ca_data['deposit'] = 0;
				break;
			}
		}
		if($status != ''){
			$ca_data['status'] = $status; 
		}else{
			$ca_data['status'] = 1; 
		}
		/*if(!$_POST['leader'][0]){
			$this->error('最少要选择一个领队！');
		}else{
			$ca_data['leader'] = implode(",",$_POST['leader']);
		}*/
		
		$ca_data['leader'] = implode(",",$_POST['leader']);
		
	
		if(!$_POST['vehicle'][0]){
			$this->error('请选择交通工具！');
		}else{
			$ca_data['vehicle'] = implode(",",$_POST['vehicle']);
		}
		if($days_left != ''){
			$ca_data['days_left'] = $days_left;
		}
		if(!$_POST['accommodation'][0]){
			$this->error('请选择住宿条件！');
		}else{
			$ca_data['accommodation'] = implode(",",$_POST['accommodation']);
		}
		$ca_data['overtime'] = date('Y-m-d',strtotime($starttime) +(86400*($days-1)));
		$ev_ca = D('event_calendar_time')->where(array('id'=>$_POST['id'],'siteid'=>SITEID))->save($ca_data);
		if($ev_ca){				
			$this->success('修改成功!', 'refresh');
		}else{
			$this->error('修改失败！');
		}
    }
	/*查看单个活动活动参加者*/
	public function open_event_dayinfo($eventid = 0,$id=0)
    {
	   $event_content = D('event')->where(array('id'=>$eventid,'siteid'=>SITEID))->find();
	   $content = D('event_calendar_time')->where(array('id'=>$id,'siteid'=>SITEID))->find();
	   $event_attend = D('event_attend')->where(array('event_id'=>$eventid,'calendar_id'=>$id,'siteid'=>SITEID))->order('id desc')->select();
		foreach ($event_attend as $key => &$v) {
			
			
			
			
			$member = D('member')->where(array('uid'=>$v['uid']))->find();
			$v['nickname']  = $member['nickname'];
			$v['countnubmer']  = count(json_decode($v['userinfo'],true));
		}
		$this->assign('event_content', $event_content);
		$this->assign('content', $content);
		$this->assign('event_attend', $event_attend);
		
        $this->display();
    }
	public function event_detail($trade_sn){
		if(!checked_admin(is_login()) || !checked_vip(is_login())){
			$this->error('您没有查看权限！');
		}
		
		$event_attend = D('event_attend')->where(array('trade_sn'=>$trade_sn,'siteid'=>SITEID))->find();
		if($event_attend){
			$signer_info = D('event_signer')->where(array('siteid'=>SITEID,'order_id'=>$event_attend['id']))->select();
			$total_num = count($signer_info);
			foreach($signer_info as $key => $val){
				$signerinfo[$key]['user_info'] = json_decode($val['user_info'],true); 
				$signerinfo[$key]['id'] = $val['id'];
				$signerinfo[$key]['insurance_info'] = json_decode($val['insurance_info'],true); 
			} 
			$card_info = D('pointcard')->where(array('cardid'=>$event_attend['cardid'],'siteid'=>SITEID))->find();
			$typeinfo = D('pointcard_type')->where(array('siteid'=>SITEID,'ptypeid'=>$card_info['ptypeid']))->find();
			$card_info['name'] = $typeinfo['name'];		
			$event = D('event')->where(array('id'=>$event_attend['event_id'],'siteid'=>SITEID))->find();
			$calendar_info = D('event_calendar_time')->where(array('id'=>$event_attend['calendar_id'],'siteid'=>SITEID,'eventid'=>$event_attend['event_id']))->find();
			$this->assign('member',$signerinfo);
			$this->assign('total_num',$total_num);
			$this->assign('event_content',$event);
			$this->assign('content',$calendar_info);
			$this->assign('card_info',$card_info);
			$this->assign('event_attend',$event_attend);
			$this->display();
		}else{
			$this->error('订单不存在');
		}
	}
	public function myevent_detail_upstatus($id,$status)
    {
		if(!$id) $this->error('参数错误！');
		$status = trim($status);
		$msg = updata_evevt_status($id,$status);
		
		if($msg['s'] == 1){
			exit(json_encode(array('status'=>$msg['s'],'m'=>$msg['m'])));
		}else{
			exit(json_encode(array('status'=>$msg['s'],'m'=>$msg['m'])));
		}
		
    }
	public function use_excel($id,$eventid){  
		$map = "calendar_id = $id and siteid = ".SITEID." and event_id = $eventid and status > 0 and status < 10";
		$event_attend = D('event_signer')->where($map)->order('id desc')->select();
		foreach($event_attend as $key => $val){
			$arr_info[$key] = json_decode($val['user_info'],true);
			$arr_info[$key]['order_status'] = $val['order_status'];
		}
		$Model = new \Think\Model();
		$result = $Model->table(array('thinkox_event_calendar_time'=>'tct','thinkox_event'=>'te'))
						->where("tct.siteid = te.siteid and tct.siteid = ".SITEID." and tct.eventid= te.id and tct.id = $id")
						->field("tct.starttime,te.title")
						->find();
						//dump($result);
		$title = $result['title'];
		$starttime = $result['starttime'];
		/*******************************************************************/
		vendor('PHPExcel.PHPExcel');
		vendor('PHPExcel.IOFactory');
		vendor('PHPExcel.Writer#Excel5');
		vendor('PHPExcel.Writer#Excel2007');
		//创建一个excel对象
		$objPHPExcel = new \PHPExcel();
		//$objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
		$objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
		 //激活第一个选项， 然后填充数据
		$objPHPExcel->setActiveSheetIndex( 0 );
		$objActSheet = $objPHPExcel->getActiveSheet ();
		$objActSheet->setCellValue ( 'A1', '姓名');
		$objActSheet->setCellValue ( 'B1', '身份证');
		$objActSheet->setCellValue ( 'C1', '电话');
		$objActSheet->setCellValue ( 'D1', '邮箱');
		$objActSheet->setCellValue ( 'E1', 'QQ');
		$objActSheet->setCellValue ( 'F1', '社会角色');
		$objActSheet->setCellValue ( 'G1', '紧急人姓名');
		$objActSheet->setCellValue ( 'H1', '紧急人联系方式');
		$objActSheet->setCellValue ( 'I1', '订单状态');		
		$objActSheet->getColumnDimension('A')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('B')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('C')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('D')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('G')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('E')->setWidth(15);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('F')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('H')->setWidth(20);//改变此处设置的长度数值		
		$objActSheet->getColumnDimension('I')->setWidth(30);//改变此处设置的长度数值		
		foreach($arr_info as $key => $val){	
				$key = $key + 2;
				$objActSheet->setCellValue("A{$key}",$val['realname']);  
				$objActSheet->setCellValue("B{$key}",chunk_split($val['card']),4," ");           
				$objActSheet->setCellValue("C{$key}",$val['telephone']);         
				$objActSheet->setCellValue("D{$key}",$val['email']);
				$objActSheet->setCellValue("E{$key}",$val['qq']);
				$objActSheet->setCellValue("F{$key}",get_role($val['role']));
				$objActSheet->setCellValue("G{$key}",$val['emergencycontact']);
				$objActSheet->setCellValue("H{$key}",$val['emergencyphone']);
				$objActSheet->setCellValue("I{$key}",strip_tags(get_event_order_status($val['order_status'])));			
		}
		$filename = get_webinfo('webname').'—'.$title.'['.$starttime.']';
		$filename = iconv("utf-8", "gbk", $filename);
		ob_end_clean();
		ob_start();
		header("Pragma:public0");
		header("Expires:0");
		header("Cache-Control:must-relalidate,post-check = 0,pre-check = 0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-excel");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition:attachment;filename="'.$filename.'.xls" ');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		
		/***********************************************************************/
	}
	
	/*查看所有活动参加者*/
	public function event_allmember($id=0,$eventid=0){	
		$map = "calendar_id = $id and siteid = ".SITEID." and event_id = $eventid and status = 1 and order_status != 0 and order_status != -1";
		$event_attend = D('event_signer')->where($map)->order('id desc')->select();	
		foreach($event_attend as $key => $val){		
			$arr_info[$key] = json_decode($val['user_info'],true);
			$arr_info[$key]['order_status'] = $val['order_status'];
		}
		
        $this->assign('arr_info',$arr_info);
        $this->display();
    }
	/*改价*/
	 public function edit_payprice($id)
    {
		if(!$id) $this->error('参数错误！');
		$datas['status'] = $status;
		$check = D('event_attend')->where(array('id' => $id,'siteid'=>SITEID))->find();
		if (!$check) {
			$this->error('订单不存在！');
		}
		$event_content = D('event')->where(array('siteid'=>SITEID, 'id' => $check['event_id']))->find();
        if (!$event_content) {
            $this->error('活动不存在！');
        }
		
		$content = D('event_calendar_time')->where(array('siteid'=>SITEID, 'id' => $check['calendar_id']))->find();
        if (!$content) {
            $this->error('排期不存在！');
        }
		$this->assign('event_content',$event_content);
 		$this->assign('event_attend',$check);
		$this->assign('content',$content);
		
        $this->display();
    }
	/*执行改价*/
	 public function doeditpayprice($id,$payprice)
    {
		if(!$id) $this->error('参数错误！');
		if(!$payprice) $this->error('改价不能为空');
		
		$check = D('event_attend')->where(array('id' => $id,'siteid'=>SITEID))->find();
		if (!$check) {
			$this->error('订单不存在！');
		}
		
		$data['payprice'] = $payprice;
		$cate=D('event_attend')->where(array('id'=>$id,'siteid'=>SITEID))->save($data);
		if($cate){
		  $this->success('改价成功','refresh');
		}else{
		   $this->error('修改失败!'); 
	   }
		
    }
 /**
     * 取消报名
     * @param $event_id
     * autor:xjw129xjt
     */
    public function unSign($id,$status)
    {
		if(!$id) $this->error('参数错误！');
		$datas['status'] = $status;
		$check = D('event_attend')->where(array('id' => $id,'siteid'=>SITEID))->find();	
		//得到订单中的人数
		$user = json_decode($check['userinfo'],true);
		$tuser = count($user);	
        if (!$check) {
            $this->error('订单不存在！');
        }
		$event_content = D('event')->where(array('id' => $check['event_id'],'siteid'=>SITEID))->find();
        if (!$event_content) {
            $this->error('活动不存在！');
        }
		

        $res = D('event_attend')->where(array('id' => $id,'siteid'=>SITEID))->save($datas);
        if ($res) {
			if($status == '0'){
			//得到排期的id
		$cid = $check['calendar_id'];
		//排期regnumber人数减掉订单中的人数
		D('event_calendar_time')->where(array('id' => $cid,'siteid'=>SITEID))->setDec('regnumber',$tuser);
  				D('Message')->sendMessageWithoutCheckSelf($event_content['uid'],query_user('nickname',is_login()).'取消了对活动['.$event_content['title'].']的报名' ,'取消报名通知', U('Usercenter/Config/open_event_dayinfo',array('id'=>$check['calendar_id'],'eventid'=>$check['event_id'])),is_login());
			}
			
			if($status = '2'){
				
			}
			
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }
	/*查看活动参加者*/
	public function event_member($id=0,$calendar_id){
       $event_attend = D('event_signer')->where(array('order_id'=>$id,'calendar_id'=>$calendar_id,'siteid'=>SITEID))->order('id desc')->select();		
		foreach($event_attend as $val){
			$userinfo[] = json_decode($val['user_info'],true);
		}
        $this->assign('userinfo',$userinfo);
        $this->display();
    }	
	
	/*验证url*/
	public function check_url($url){
		if(!preg_match('/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',$url)){
			$this->error('url不正确');
		}
	}
	/*---getTree---*/
	public function getTree($id = 0, $field = true){
        /* 获取当前分类信息 */
        if($id){
            $info = $this->info($id);
            $id   = $info['id'];
        }

        /* 获取所有分类 */
        $map  = array('status' => array('gt', 0));
        $list = $this->field($field)->where($map)->order('sort')->select();
        $list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_', $root = $id);


        /* 获取返回数据 */
        if(isset($info)){ //指定分类则返回当前分类极其子分类
            $info['_'] = $list;
        } else { //否则返回所有分类
            $info = $list;
        }

        return $info;
    }
 /*提现记录-2014-10-27*/
 public function my_income(){
     $stas=I('stas');
	  switch($stas){
	    case 0://银行卡信息
		    $account=D('websit_account_record')->where('siteid='.SITEID)->find();
			$this->assign('list',$account);
		break;
		case 1://申请提现
		    $balance=D('websit_cash_record')->where("siteid=".SITEID)->find();
		    $list=D('websit_account_record')->where("siteid=".SITEID)->select();
		    $k_balance=$balance['balance']-$balance['frozen'];
			
			$this->assign('balance',$balance);
			$this->assign('k_balance',$k_balance);//可用余额
			$this->assign('list',$list);
		break;
		case 2://提现记录
		    $status=I('status');
			if($status!=''){
			   $map['status']=$status;
			}
		       $map['siteid']=SITEID;
			   
			$count = D('websit_cashout_record')->where($map)->count();
			$Page       = new \Think\Page($count,10);
			$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
			$show       = $Page->show();// 分页显示输出  
           
		    $reds  = D('websit_cashout_record')->where($map)->order('time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		            foreach($reds as $key=>$val){
					    $cardinfo=json_decode($reds[$key]['cardinfo'],true);
						$reds[$key]['name']=$cardinfo['name'];
						$reds[$key]['card']=$cardinfo['card'];
						$reds[$key]['open_bank']=$cardinfo['open_bank'];
					}
					
			$this->assign('datas',$reds);			
		    $this->assign('page',$show);
		break;
		case 3://提现记录
		   $this->all_payment_records();
		break;
		
		
	  }
	 $this->assign('stas',$stas);
     $this->assign('user',$this->userdata);
     $this->display();
   }
 /*添加网站提现帐号*/
 public function doCashAccount($name='',$card='',$open_bank=''){
        if(IS_POST){
		    $name=op_t(trim($name));
			$card=op_t(trim($card));
			$open_bank=op_t(trim($open_bank));
			
			if($name=='') $this->error('名字不能为空');
			if($card==''|| !is_numeric($card)) $this->error('请输入正确的卡号');
            if(!preg_match('/^\d{16}|\d{19}$/',$card)) $this->error('请输入正确的银行卡号');			
			if($open_bank=='') $this->error('请填写开户行');
			
			$accountFind=D('websit_account_record')->where('siteid='.SITEID)->find();
			    $data['name']      =   $name;
                $data['uid']       =   is_login();
                $data['siteid']	   =   SITEID;
                $data['open_bank'] =   $open_bank;
                $data['card']	   =   $card;
                $data['status']	   =   1;	
				
				if(!$accountFind){
				    $accountAdd=D('websit_account_record')->data($data)->add();
					if($accountAdd){
					    $this->success('添加成功');
					}else{
					    $this->error('添加失败');
					}
				   
				}else{
				    $account_save=D('websit_account_record')->where("siteid=".SITEID)->save($data);
					if($account_save){
					    $this->success('更新成功');
					}else{
					    $this->success('未更新数据');
					}
				  
				}
		
		
		}
	}
 /*申请提现*/
 public function doCashout($card='',$name='',$open_bank='',$cash=''){
     if(IS_POST){
	    $cash       = op_t(trim($cash));
		$card       = op_t(trim($card));
		$name       = op_t(trim($name));
		$open_bank  = op_t(trim($open_bank));
	    
		$account_cards=D('websit_account_record')->where('siteid='.SITEID)->find();
		if(!$account_cards) $this->error('请先绑定银行卡',U('Websit/my_income',array('stas'=>0)));
	    if($card =='') $this->error('请选择银行卡!');
		if($cash=='' || !is_numeric($cash)) $this->error('请输入正确的金额');
	
		$cash_record    = D('websit_cash_record');  //支付记录
		$cashout_record = D('websit_cashout_record'); //支出
		$cash_record->startTrans();
		
		$list=$cash_record->where("siteid=".SITEID)->find();//得到支付记录表信息
			if($list){
				
					if($cash>$list['balance']) $this->error('提现金额不能大于可用余额');
					if($cash<=0) $this->error('提现金额不能小于等于0 ');
					$data['cash']   = $cash; //提现金额-写入申请记录表--
					$cardinfo['card']     = $card;
					$cardinfo['name']     = $name;
					$cardinfo['open_bank']= $open_bank;
					$data['cardinfo']   = json_encode($cardinfo);
					
					$data['siteid'] = SITEID;
					$data['uid']    = is_login();
					$data['status'] = 1;
					$data['type']   = 1;
					$data['time']   = time();
					$data['flownumber']= create_sn();//流水单号
					$trade_sn = $data['flownumber'];//流水单号
					
					$cash_save      = $cashout_record->data($data)->add();//--添加申请记录--
					
					$cate['frozen']  = $list['frozen']  + $cash; //--得到冻结的钱-- 因为多条记录  +加上 
					$cate['balance'] = $list['balance'] - $cash; //--余额做减法--
					$cate['status']  = 1;
					$cash_record_save=$cash_record->where('siteid='.SITEID)->save($cate);
						if($cash_record_save && $cash_save ){
							 D('RecordContent')->setuseprice_cashout($trade_sn);//提现余额
							 $cash_record->commit();
							 $this->success('申请成功',U('Websit/Index/my_income',array('stas'=>2)));
						}else{
							 D('RecordContent')->setuseprice_cashout($trade_sn);//提现余额
							$cash_record->rollback();
							$this->error('申请失败');
						}
					
				
			}else{
				$this->error('申请失败');
			
			}
		
	}
 
 }
	
 /*订单管理*/
   public function web_event($paytype=0){
		$status = $_GET['status'];
		$status = isset($status)?$status:1;
		switch($status){
			case 1;
				$ord_status = $_GET['ord_status'];
				$trade_sn = op_t(I('event_trade_sn'));
				$ord_status = isset($ord_status) ? $ord_status : 'inuse';
				if(!$trade_sn){
					switch($ord_status){
						case 'inuse':
							$map = "siteid = ".SITEID." and status != -1 and status != 0";
						break;
						case 'halfpay':
							$map = "siteid = ".SITEID." and (status = 11 or status = 12) and pay_status = 1 and paytype = 1 and status != -1 and status != 0";
						break;
						case 'succ':
							$map = "siteid = ".SITEID." and pay_status = 2 and status != -1 and status != 0";
						break;
						case 'unpay':
							$map = "siteid = ".SITEID." and pay_status = 0 and status != -1 and status != 0";
						break;
						case 'all':
							$map = "siteid = ".SITEID;
						break;
					}
				}else{
					$map = "siteid = ".SITEID." and trade_sn = $trade_sn";
				}
				$count=D('event_attend')->where($map)->count();//总数				 
				$Page  = new \Think\Page($count,10);// 
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				$show  = $Page->show();// 
				$event_attend = D('event_attend')->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
				foreach ($event_attend as $key => $v) {				
					$event = D('event')->where(array('id'=>$v['event_id'],'siteid'=>SITEID))->order('id desc')->find();
					$event_calendar_time = D('event_calendar_time')->where(array('id'=>$v['calendar_id'],'siteid'=>SITEID))->order('id desc')->find();
					$event_attend[$key]['title'] = $event['title'];
					$event_attend[$key]['cover_id'] = $event['cover_id'];
					$event_attend[$key]['price'] = $event_calendar_time['price'];
					$event_attend[$key]['start_time'] = $event_calendar_time['starttime'];
					$event_attend[$key]['over_time'] = $event_calendar_time['overtime'];
					$event_attend[$key]['end_time'] = $event_calendar_time['endtime'];
					$event_attend[$key]['create_time'] = date("Y-m-d H:i:s",$v['creat_time']);
					$map = "siteid = ".SITEID." and calendar_id = ".$event_calendar_time['id']." and event_id = ".$event['id']." and status = 1";
					$event_attend[$key]['signer'] = D('event_signer')->where($map)->select();					
					$event_attend[$key]['travel_number'] = count($event_attend[$key]['signer']);					
				}
				$this->assign('event',$event_attend);
				$this->assign('page',$show);
			break;
			case 2;			
				$tailor_trade_sn = I('tailor_trade_sn');
				if(!$tailor_trade_sn){
					$map = "siteid = ".SITEID." and status = 1";
				}else{
					$map = "siteid = ".SITEID." and status = 1 and trade_sn = $tailor_trade_sn";
				}
				$count = D('event_tailor')->where(array('siteid'=>SITEID,'status'=>1))->count();
				$Page  = new \Think\Page($count,10);// 
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				$show  = $Page->show();// 
				$tailor_arr = D('event_tailor')->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
				foreach($tailor_arr as $key => $val){
					$tailor_arr[$key]['tailor_note'] = D('event_tailor_note')->where(array('tailor_id'=>$val['id'],'siteid'=>SITEID,'status'=>1))->select();
				}
				$this->assign('tailor_arr',$tailor_arr);
				$this->assign('page',$show);
			break;
		}	
		$this->assign('status',$status);
        $this->display();
    }


    public function video_add(){
        $this->display();
    }

    public function  video_doadd($video_name='',$video_url=''){
    	if (IS_POST) {
    	    $data['video_name']      =trim($video_name);
    	    $data['video_url']       =trim($video_url);
    	    $data['video_real_url']  =$this->getswf($data['video_url']);
    	    $data['siteid']          =SITEID;
    	    $data['status']    =1;
            $res=M('websit_video')->data($data)->add();
            if ($res) {
               $this->success('您好，视频添加成功！',U('Websit/Index/content',array('status'=>9)));
            }else{
               $this->error('对比起，你的视频添加不成功。请重试');
            }
    	}
    }


    	public function video_edit(){
	     $id=$_GET['id'];
	     $list=D('websit_video')->where("id=$id")->find();
	     $this->assign('list',$list);
	     $this->display();
	
	}
	/*执行更新视频*/
	public function video_doEdit(){
	    if(IS_POST){
			$id            =$_POST['video_id'];
			$video_name     =trim($_POST['video_name']);
			$video_url      =trim($_POST['video_url']);
		    if($video_name==''){
		     $this->error('视频名称不能为空');
	        }
			if($video_url==''){
			    $this->error('视频url不正确');
			}
			$data['video_name']=$video_name;
			$data['video_url']=$video_url;
			$data['video_real_url']=$this->getswf($data['video_url']);
			
			$cate=D('websit_video')->where("id=$id")->save($data);
			if($cate){
			  $this->success('更该成功',U('Websit/Index/content',array('status'=>9)));
			}else{
			  $this->error('更改失败');
			}
		}
	
	}

	public function video_is_disable(){
	     $data['status'] = $_POST['status'];
	     $id=$_POST['id'];
		 $res=D('websit_video')->where("id=$id")->save($data);
			if($res){
				$this->success('修改成功',U('Websit/Index/content',array('status'=>9)));
			}else{
				$this->error('修改失败');
			}
	}
    
    public function is_recommend()
    {
         $data['video_recommend']=I('video_recommend');
		 $video =D('websit_video')->where("id=".I('id'))->save($data);
		 if($video){
		    $this->success('取消推荐操作成功');
		 }else{
		    $this->error('取消推荐操作失败');
		 }
	
    }

 //推荐
    public function video_recommend(){
         $data['video_recommend']=I('video_recommend');
         $map['siteid']=SITEID;
		 $video =D('websit_video')->where($map)->select();
		 foreach ($video as $key => $value) {
		 	 if ($value['video_recommend']==1) {
		 	 	$this->error('对比起，您已经推荐了一个视频！');
		 	 }
		 }
		 $video =D('websit_video')->where("id=".I('id'))->save($data);
		 if($video){
		    $this->success('视频推荐操作成功');
		 }else{
		    $this->error('视频操作操作失败');
		 }

    }

	  public function getswf($url='') {
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
                    $swf = $url;
                }else if( $domain == 'v' ) {
                    preg_match_all('/http:\/\/v\.youku\.com\/v_show\/id_(.*)?\.html/',$url,$url_array);
                    $swf = 'http://player.youku.com/player.php/sid/'.str_replace('/','',$url_array[1][0]).'/v.swf';
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
	 * 排期的隐藏或显示
	 */
	public function schedule_display($schedule_id = '',$display = ''){
			if($schedule_id == '' || $display == '' ){
				exit(json_encode(array('status'=>false,'msg'=>'参数错误！')));
			}
			$data['display'] = $display;
			if($display == 1){
				$msg = '显示成功！';
			}else{
				$msg = '隐藏成功！';
			}
			$rs = D('event_calendar_time')->where(array('id'=>$schedule_id,'siteid'=>SITEID))->save($data);
			if($rs){
				exit(json_encode(array('status'=>true,'msg'=>$msg)));
			}else{
				exit(json_encode(array('status'=>false,'msg'=>'操作失败！')));
			}
	}
	public function sms_notice($id = '',$eventid = ''){
		if(!$id || !$eventid) $this->error('参数错误！');
		$event = D('event')->where(array('id'=>$eventid,'siteid'=>SITEID))->find();
		$calendar = D('event_calendar_time')->where(array('siteid'=>SITEID,'id'=>$id))->find();
		$this->assign('event',$event);
		$this->assign('calendar',$calendar);
		$this->display();
	}
	 public function do_send_sms($id = '',$eventid = '',$notice = ''){
		if(!$id || !$eventid) $this->error('参数错误！');
		if(!$notice) $this->error('请填写短信内容');
		$map = "calendar_id = $id and siteid = ".SITEID." and event_id = $eventid and status = 1 and order_status != 0 and order_status != -1";
		$event_attend = D('event_signer')->where($map)->order('id desc')->select();	
		foreach($event_attend as $key => $val){		
			$arr_info[$key]['user_info'] = json_decode($val['user_info'],true);
			$arr_info[$key]['event_id'] = $val['event_id'];
			$arr_info[$key]['calendar_id'] = $val['calendar_id'];
			$arr_info[$key]['signer_id'] = $val['id'];
		}
		$webinfo = json_decode(WEBSITEINFO,true);
		foreach($arr_info as $key => $val){
			$arr[$key] = sms_alerts($val['user_info']['telephone'],$notice,'活动短信通知');
			$this->do_add_smslog($val,$arr[$key]['error'],$notice);
		}	
		$this->success('短信已批量发送！',U('Websit/Index/sms_backinfo',array('calendar_id'=>$id,'event_id'=>$eventid)));
	 }
	public function do_add_smslog($arr,$back_num,$msg){
		$calendar_id = $arr['calendar_id'];
		$event_id = $arr['event_id'];
		$map = "calendar_id = $calendar_id and event_id = $event_id and uid = ".is_login()." and siteid = ".SITEID."";
		$map1 = "$map and send_count = (select MAX(send_count) from thinkox_event_sms_log where $map)";
		$send_info = D('event_sms_log')->where($map1)->find();
		if($send_info){
			$data['send_count'] = $send_info['send_count'] +1;
		}else{
			$data['send_count'] = 1;
		}
		$webinfo = json_decode(WEBSITEINFO,true);
		$data['uid'] = is_login();
		$data['signer_id'] = $arr['signer_id'];
		$data['event_id'] = $arr['event_id'];
		$data['calendar_id'] = $arr['calendar_id'];
		$data['siteid'] = SITEID;
		$data['create_time'] = time();	
		$data['msg'] = $msg;	
		$data['reciever_name'] = $arr['user_info']['realname'];	
		$data['reciever_telephone'] = $arr['user_info']['telephone'];	
		$data['back_info'] = sms_back_info($back_num) != '' ? sms_back_info($back_num):'未知错误';
		$data['send_web'] = $webinfo['webname'];
		$data['user_info'] = json_encode($arr['user_info']);
		D('event_sms_log')->add($data);
	}
	public function sms_backinfo($calendar_id,$event_id){
		if(!$calendar_id || !$event_id) $this->error('参数错误！');
		$Model = new \Think\Model();
		$map = "calendar_id = $calendar_id and event_id = $event_id and uid = ".is_login()." and siteid = ".SITEID."";
		$sql = "select * from thinkox_event_sms_log where $map and send_count = (select MAX(send_count) from thinkox_event_sms_log where $map)";
		$sms_arr = $Model->query($sql);
		foreach($sms_arr as $key => $val){
			$sms_arr[$key]['user_info'] = json_decode($val['user_info'],true);
		}
		$succ_arr = array();
		$err_arr = array();
		foreach($sms_arr as $key => $val){
			if($val['back_info'] == '发送成功'){
				$succ_arr[$key] = $val;
				$succ_arr[$key]['user_info'] = json_decode($val['user_info'],true);
			}else{
				$err_arr[$key] = $val;
			}
		} 	
		$tid = count($sms_arr);
		$sid = count($succ_arr);
		$eid = count($err_arr);
		$this->assign('tid',$tid);
		$this->assign('sid',$sid);
		$this->assign('eid',$eid);
		$this->assign('contents',$sms_arr);
		$this->display();
	}
	/***************私家定制*****************************************/
	public function do_add_tailor_note($tailor_id=0,$content=''){
		if(!$tailor_id) $this->error('参数错误！');
		$tailor_info = D('event_tailor')->where(array('id'=>$tailor_id,'siteid'=>SITEID,'status'=>1))->find();
		if(!$tailor_info) $this->error('未知的需求！');
		if(!checked_admin(is_login()) || !checked_vip(is_login())) $this->error('权限不足，无法添加！');
		if(!$content) $this->error('备注内容不能为空！');
		$data['content'] = $content;
		$data['uid'] = is_login();
		$data['create_time'] = time();
		$data['tailor_id'] = $tailor_id;
		$data['siteid'] = SITEID;
		$data['status'] = 1;
		$rs = D('event_tailor_note')->add($data);
		if($rs){
			$this->success('添加备注成功！',U('Websit/Index/web_event',array('status'=>2)));
		}else{
			$this->error('添加失败！');
		}
		
	}
	public function show_note($id){	
		if(!$id) $this->error('参数错误!');
		$tailor_info = D('event_tailor')->where(array('id'=>$id,'siteid'=>SITEID,'status'=>1))->find();
		if(!$tailor_info) $this->error('未知的需求！');
		if(!checked_admin(is_login()) || !checked_vip(is_login())) $this->error('权限不足，无法查看！');
		$map = "tailor_id = $id and siteid = ".SITEID." and status = 1";
		$count=D('event_tailor_note')->where($map)->count();//总数
		$Page  = new \Think\Page($count,10);// 
		$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
		$show  = $Page->show();// 
		$note_arr = D('event_tailor_note')->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('note_arr',$note_arr);
		$this->assign('page',$show);
		$this->display();
	}
	public function show_need($id){
		if(!$id) $this->error('参数错误!');
		$tailor_info = D('event_tailor')->where(array('id'=>$id,'siteid'=>SITEID,'status'=>1))->find();
		if(!$tailor_info) $this->error('无相关信息！');
		if(!checked_admin(is_login()) || !checked_vip(is_login())) $this->error('权限不足，无法查看！');
		$this->assign('tailor_info',$tailor_info);
		$this->display();
	}
	public function show_contact($id){
		if(!$id) $this->error('参数错误!');
		$tailor_info = D('event_tailor')->where(array('id'=>$id,'siteid'=>SITEID,'status'=>1))->find();
		if(!$tailor_info) $this->error('无相关信息！');
		if(!checked_admin(is_login()) || !checked_vip(is_login())) $this->error('权限不足，无法查看！');
		$this->assign('tailor_info',$tailor_info);
		$this->display();
	}
	public function add_tailor_note($id){
		if(!$id) $this->error('参数错误!');
		$tailor_info = D('event_tailor')->where(array('id'=>$id,'siteid'=>SITEID,'status'=>1))->find();
		if(!$tailor_info) $this->error('未知的需求！');
		if(!checked_admin(is_login()) || !checked_vip(is_login())) $this->error('权限不足，无法添加！');
		$this->display();
	}
	/****************************************************************/
	
	/*2014-11-26-dlx--角色申请*/
	public function member_manage_role(){
		$status	=	$_POST['status'];
		 $uid	=	$_POST['uid'];
		 $id		=	$_POST['id'];
		 $is_use	=	$_POST['is_use'];
		if($status=='' || $id=='' || $uid=='' || $is_use=='') $this->error('参数错误!');
		
		if($status==1){
		       $data	= array(
			         'status'	=> $status,
					 'gmuid'	=>	is_login(),
					 'update_time'=>time()
			    );
			   $upgradlist	=	D('member_upgrad_group')->where("id=".$id)->save($data);
			   $member		=	D('member')->where("uid=".$uid)->save(array('is_use'=>$is_use));
			   if($upgradlist && $member){
					$this->success('操作成功');
			   }else{
					$this->error('操作失败!');
			   }
		
		}elseif($status==-2){
					$cates	= array(
						'status'	=> $status,
						'gmuid'	=>	is_login(),
						'update_time'=>time()
					);
				 $upgradNlist	=	D('member_upgrad_group')->where("id=".$id)->save($cates);
				if($upgradNlist){
					$this->success('操作成功');
				}else{
					$this->error('操作失败!');
				}
		
		}
	
	}
	public function show_refe($id){
		if(!$id) $this->error('参数错误!');
		$tailor_info = D('event_tailor')->where(array('id'=>$id,'siteid'=>SITEID,'status'=>1))->find();
		if(!$tailor_info) $this->error('未知的需求！');
		if(!checked_admin(is_login()) || !checked_vip(is_login())) $this->error('权限不足，无法查看！');
		$this->assign('refe',$tailor_info['reference']);
		$this->display();
	}

}  
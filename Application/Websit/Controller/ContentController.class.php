<?php

namespace Websit\Controller;

use Think\Controller;

class ContentController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize(); 
	}
	    public function index(){
			$status=I('status');
		  switch($status){
			case 0:/*官方公告*/
			     $notices=D('document')->where("status>=0 and siteid=".SITEID)->order("id desc")->select();
				 $this->assign('notices',$notices);
			break;
			case 1:
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
			case 2:/*关于我们*/
			   $cates=D('about')->where("siteid=".SITEID)->order("id asc")->select();
			   $this->assign('list',$cates);
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
			    /*服务企业*/
				$count=D('member_service')->where("status>=0 and siteid=".SITEID)->count();
				$Page       = new \Think\Page($count,10);
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
			    $show       = $Page->show();// 分页显示输出
				$list=D('member_service')->where("status>=0 and siteid=".SITEID) ->limit($Page->firstRow.','.$Page->listRows)->select();
				$this->assign('list',$list);
				$this->assign('page',$show);
			break;
		    case 5:
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
	public function notice_type(){
		$tree = D('Category')->where("siteid=".SITEID." and status = 1")->getTree(0,'id,title,sort,pid,allow_publish,status,siteid');
		 $this->assign('tree', $tree);
		 C('_SYS_GET_CATEGORY_TREE_', true); //标记系统获取分类树模板
		 $this->meta_title = '分类管理';
		 $this->display();
	}
	 /* 新增分类 */
    public function notice_type_add($pid = 0){
        $Category = D('Category');

        if(IS_POST){ //提交表单
             $title = trim($_POST['title']);
				$sort  = trim($_POST['sort']);
				if($title=='') $this->error('请填写分类名称');
				$data = array(
				   'siteid' => SITEID,
				   'pid'	=> 0,
				   'sort'	=> $sort,
				   'title'	=> $title,
				   'create_time' =>time(),
				   'update_time' =>time(),
				   'status'		 =>1,
				
				);
				$up_cate=D('category')->add($data);
				if($up_cate){
					$this->success('添加成功','refresh');
				}else{
					$this->error('添加失败');
				}
        } else {
            $cate['title']=I('title');
			$cate['id']=I('id');
            /* 获取分类信息 */
            $this->assign('category', $cate);
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
	//--执行修改----
	public function notice_type_edit($sort='',$title='',$id=0,$pid=0){
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
							  
							  $this->success('添加成功',U('Websit/Content/index',array('status'=>0)));
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
			    $this->success('更改成功',U('Websit/Content/index',array('status'=>0)));
			  }else{
			    $this->success('未更改数据');
			 }
		  
		  }
	
	}
	/*公告推荐*/
	public function content_notice_recommend(){
		$data['is_recommend']=I('is_recommend');
		$reds=D('document')->where("id=".I('id'))->save($data);
		if($reds){
		   $this->redirect('Websit/Content/index?status=0');
		}else{
		   $this->redirect('Websit/Content/index?status=0');
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
							$this->success('添加子分类成功',U('Websit/Content/story_type'));
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
							$this->success('添加成功',U('Websit/Content/story_type'));
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
   /*编辑故事*/
	public function story_content_edit(){
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
		$this->assign('user',$this->userdata);
	    $this->display();
	    
	}
   /*执行修改*/
   public function story_doPost($id = 0,$url = '', $cover_id = 0, $issue='', $title = '', $final_city = '', $tag = '', $content = '',$related_event = ''){
        
		
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
		
        if (trim($content) == '') {
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
        $content['content'] = $content['content'];
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
	public function story_type(){
		 /*故事类型*/
		$tree = D('Issue')->where('siteid='.SITEID)->getTree();
		$this->assign('tree', $tree);
		$this->display();
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
			  $this->success('更改成功',U('Websit/Content/index',array('status'=>2)));
		  }else{
			  $this->error('未更新数据');
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
			   $this->success('添加成功',U('Websit/Content/index',array('status'=>2)));
			}else{
			   $this->error('添加失败');
			}
		
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
			  $this->success('更该成功',U('Websit/Content/index',array('status'=>4)));
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
	public function do_service_add($url){
		 if(!preg_match('/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',$url)){
			  $this->error('url不正确');
		   }
		  $data['service_process'] = $url;
		  $rs=D('websit')->where(array('siteid'=>SITEID))->save($data);
		  if($rs){
			$this->success('添加成功!',U('Websit/Content/index',array('status'=>4)));
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
			$this->success('编辑成功',U('Websit/Content/index',array('status'=>4)));
		  }else{
			$this->error('编辑失败！');
		  }
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
               $this->success('您好，视频添加成功！',U('Websit/Content/index',array('status'=>5)));
            }else{
               $this->error('对不起，你的视频添加不成功。请重试');
            }
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
	public function video_is_disable(){
	     $data['status'] = $_POST['status'];
	     $id=$_POST['id'];
		 $res=D('websit_video')->where("id=$id")->save($data);
			if($res){
				$this->success('操作成功',U('Websit/Index/content',array('status'=>9)));
			}else{
				$this->error('操作失败');
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
	public function video_edit(){
	     $id=$_GET['id'];
	     $list=D('websit_video')->where("id=$id")->find();
	     $this->assign('list',$list);
	     $this->display();
	
	}
	/*执行更新视频*/
	public function video_doedit(){
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
			  $this->success('更该成功',U('Websit/Content/index',array('status'=>5)));
			}else{
			  $this->error('更改失败');
			}
		}
	
	}
	
}  
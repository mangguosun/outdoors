<?php


namespace Mobile\Controller;

use Think\Controller;


class IssueController extends Controller
{
    /**
     * 业务逻辑都放在 WeiboApi 中
     * @var
     */
    public function _initialize()
    {
		$model_info = get_appinfo('Issue');
		if(!$model_info){
			$this->error('应用未开启');
		}
		$url = $_SERVER['QUERY_STRING'];
		$url_arr = explode('/',$url);
		$dest_url = $url_arr[2];
		$dest_url = $dest_url == ''?'index':$url_arr[2];
		$this->assign('dest_url',$dest_url);
		$tree = D('Issue')->where(array('status' => 1 ,'siteid'=>SITEID))->select();
		$this->assign('model_info', $model_info);
		$this->assign('tree', $tree);
    }

    public function index($page = 1, $issue_id = 0)
    {
	    $issue_id = intval($issue_id);
        $issue = D('Issue')->where(array('id'=>$issue_id,'siteid'=>SITEID,'status' => 1))->find();
	
		if($issue_id){
			$map['issue_id'] = $issue_id;
		}
		$map['status'] = 1;
		$map['siteid'] = SITEID;	
        $content = D('IssueContent')->field('id')->where($map)->order('create_time desc')->page($page, 10)->select();
      	$totalCount = D('IssueContent')->where($map)->count();
		
		foreach ($content as $key => &$v) {

			$content[$key] = $this->cacheIssueMobileContent($v['id']); 
		
        }
     
		$get_url = json_encode($_GET);
		$this->assign('get_url', $get_url);
		$this->assign('issue_id', $issue_id);
		$this->assign('issue', $issue);
        $this->assign('contents', $content);
        $this->assign('totalPageCount', $totalCount);
        $this->assign('top_issue', $issue['pid'] == 0 ? $issue['id'] : $issue['pid']);
        $this->setTitle('旅行故事');
        $this->display();
    }
	
	
    public function get_issue_index($page = 0, $issue_id = 0, $recommend = 0)
    {
	    $issue_id = intval($issue_id);
        $issue = D('Issue')->where(array('id'=>$issue_id,'siteid'=>SITEID,'status' => 1))->find();
      
		if($recommend){
			$map['is_recommend'] = 1;
		}
		if($issue_id){
			$map['issue_id'] = $issue_id;
		}
		$map['status'] = 1;
		$map['siteid'] = SITEID;	
		
		$start = $page*10; 
        $content = D('IssueContent')->where($map)->field('id')->order('create_time desc')->limit($start, 10)->select();
		
		foreach ($content as $key => &$v) {
			$content[$key] = $this->cacheIssueMobileContent($v['id']); 
        }
    
		exit(json_encode($content));
    }
	

  	private function cacheIssueMobileContent($id){ 

  		$cacheIssueMobileContent_keys  = "Issue_Mobile_ContentList_".SITEID."_".$id;
  		$content_S = S($cacheIssueMobileContent_keys);

  		if($content_S){
  			return $content_S;
  		}else{ 

			$content = D('IssueContent')->field('id,siteid,uid,title,create_time,cover_id,view_count,issue_id,finalcity,tag,recommend_brand,related_event,is_recommend')->where("id = ".$id)->find();

			$content['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $content['uid']);  
			$event_content['tagarr'] = explode(',',$content['tag']);
			foreach ($event_content['tagarr'] as $key => $a) {
				$content['tags'][$a]['id'] = $a;
				$content['tags'][$a]['name'] = get_event_tag($a);
			}
			$content['url'] =U('Mobile/Issue/issuecontentdetail',array('id'=>$content['id']));
			$content['thumb'] =getThumbImageById($content['cover_id'],400,300);
			$content['user_thumb'] =$content['user']['avatar128'];
			$finalcity = get_citys($content['finalcity']);
			$content['finalcity_province'] = get_city($finalcity['province']);
			$content['finalcity_city'] = get_city($finalcity['city']);

			S($cacheIssueMobileContent_keys,$content,1800);

		 	return $content;


  		}




  	}


	public function mobileissuedetail($id){ 

		$mobile_Issue_detail_keys = "Issue_Mobile_Detail_".SITEID."_".$id;
		D('IssueContent')->where(array('id' => $id,'siteid'=>SITEID))->setInc('view_count');

		$issue_content_S = S($mobile_Issue_detail_keys);

		if($issue_content_S){

			$this->assign('is_collection',$issue_content_S['is_collection']);
	        $this->assign('top_issue', $issue_content_S['top_issue']);
	        $this->assign('issue_id',$issue_content_S['issue_id']);
			$this->assign('issue', $issue_content_S['issue']);
			$this->assign('imgissue',$issue_content_S['imgissue']);
			$this->assign('content', $issue_content_S['content']);
			$this->setKeywords($issue_content_S['content']['title']);

		}else{  

			$issue_content = D('IssueContent')->where(array('id'=>$id,'siteid'=>SITEID))->find();
	        $issue = D('Issue')->where(array('id' => $issue_content['issue_id'],'siteid'=>SITEID))->find();
			$issue_sltu['imgid'] = explode(',',$issue_content['imgids']);
			foreach ($issue_sltu['imgid'] as $key => $a) {
				$issue_content['imgid'][$key]= D('picture')->where('id ='.$a)->getField('path');
			}
			if(is_login()){
				$mark=D('forum_bookmark')->where(array('siteid'=>SITEID,'issue_id'=>$id,'uid'=>is_login()))->find();
				if($mark){
					$is_collection = 1;
				}else{
					$is_collection = 0;
				}
			}else{
				$is_collection = 0;
			}
			
			$this->assign('is_collection',$is_collection);
	        $this->assign('top_issue', $issue['pid'] == 0 ? $issue['id'] : $issue['pid']);
	        $this->assign('issue_id', $issue['id']);
			$this->assign('issue', $issue);

	        $issue_content['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $issue_content['uid']);
			$issue_content['content'] = ludou_remove_width_height_attribute($issue_content['content']);

			$this->assign('imgissue',$issue_sltu['imgid']);
			$this->assign('content', $issue_content);
			$this->setKeywords($issue_content['title']);

			$issueDetail_S['is_collection'] = $is_collection;
			$issueDetail_S['top_issue'] = $issue['pid'] == 0 ? $issue['id'] : $issue['pid'];
			$issueDetail_S['issue_id'] =$issue['id'];
			$issueDetail_S['issue'] =  $issue;
			$issueDetail_S['imgissue'] = $issue_sltu['imgid'];
			$issueDetail_S['content'] = $issue_content;

			S($mobile_Issue_detail_keys,$issueDetail_S,1800);


		}

        $this->setTitle('{$content.title|op_t}' . '——旅行故事');
        $this->display('Issue/mobileissuedetail');
	}

	public function issueredetail($id){ 
		$mobile_Issue_detail_keys = "Issue_Mobile_Detail_".SITEID."_".$id;
		D('IssueContent')->where(array('id' => $id,'siteid'=>SITEID))->setInc('view_count');

		$issue_content_S = S($mobile_Issue_detail_keys);

		if($issue_content_S){
			$this->assign('is_collection',$issue_content_S['is_collection']);
			$this->assign('desty',$issue_content_S['desty']);
	        $this->assign('top_issue', $issue_content_S['top_issue']);
	        $this->assign('issue_id',$issue_content_S['issue_id']);
			$this->assign('issue', $issue_content_S['issue']);
			$this->assign('content', $issue_content_S['content']);
			$this->setKeywords($issue_content_S['content']['title']);

		}else{ 
			$issue_content = D('IssueContent')->where(array('id'=>$id,'siteid'=>SITEID))->find();
	        $issue = D('Issue')->where(array('id' => $issue_content['issue_id'],'siteid'=>SITEID))->find();
			$event_content['tagarr'] = explode(',',$issue_content['tag']);
				foreach ($event_content['tagarr'] as $key => $a) {
					$issue_content['tags'][$a]['id'] = $a;
					$issue_content['tags'][$a]['name'] = get_event_tag($a);
				}
			$disid = $issue_content['finalcity'];
			$data_arr=D('district')->where(array('id'=>$disid))->find();
			if($data_arr['level'] ==2){
				$upname = D('district')->where(array('id'=>$data_arr['upid']))->find();
			}
			$desty = "".$upname['name']." ".$data_arr['name']."";
			if(is_login()){
				$mark=D('forum_bookmark')->where(array('siteid'=>SITEID,'issue_id'=>$id,'uid'=>is_login()))->find();
				if($mark){
					$is_collection = 1;
				}else{
					$is_collection = 0;
				}
			}else{
				$is_collection = 0;
			}
			
			$this->assign('is_collection',$is_collection);
			$this->assign('desty',$desty);
	        $this->assign('top_issue', $issue['pid'] == 0 ? $issue['id'] : $issue['pid']);
	        $this->assign('issue_id', $issue['id']);
			$this->assign('issue', $issue);
	        $issue_content['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $issue_content['uid']);
			$issue_content['content'] = ludou_remove_width_height_attribute($issue_content['content']);
			$this->assign('content', $issue_content);
			$this->setKeywords($issue_content['title']);

			$issueDetail_S['is_collection'] = $is_collection;
			$issueDetail_S['desty'] = $desty;
			$issueDetail_S['top_issue'] = $issue['pid'] == 0 ? $issue['id'] : $issue['pid'];
			$issueDetail_S['issue_id'] =$issue['id'];
			$issueDetail_S['issue'] =  $issue;
			$issueDetail_S['content'] = $issue_content;

			S($mobile_Issue_detail_keys,$issueDetail_S,1800);

		}

        $this->setTitle('{$content.title|op_t}' . '——旅行故事');
        $this->display('Issue/issuecontentdetail');
	}
    public function issuecontentdetail($id = 0)
    {
      	$issue_content = D('IssueContent')->where(array('id'=>$id,'siteid'=>SITEID))->find();
        if (!$issue_content) {
            $this->error('404 not found');
        }
        if($issue_content['type']==1){ 
        	$this->mobileissuedetail($id);
        }else{ 
        	$this->issueredetail($id);
        }
    }
  	
    public function selectDropdown($pid)
    {
        $issues = D('Issue')->where(array('pid' => $pid, 'status' => 1,'siteid'=>SITEID))->limit(999)->select();
        exit(json_encode($issues));


    }
    //---发布故事---
    public function publishstory(){
    	if(!is_login()){
			$this->redirect('Mobile/Index/index');
		}
	
    	$tree = D('Issue/Issue')->where(array('status' => 1 ,'siteid'=>SITEID))->getTree();
        $this->assign('tree', $tree);
    	$this->display();
    }
    public function editstory($id=0){ 
    	if(!is_login() || !$id){ 
			$this->redirect('Mobile/Index/index');
    	}
    	if($id){
    		$issue_content = D('IssueContent')->where(array('id'=>$id,'siteid'=>SITEID))->find();
			$issue_content['content']=str_replace("<br/>","\n",$issue_content['content']);
			$this->assign('content',$issue_content);


		}
		$tree = D('Issue/Issue')->where(array('status' => 1 ,'siteid'=>SITEID))->getTree();
        $this->assign('tree', $tree);
    	$this->display();

    }

    public function dopublishstory($id=0){
    	$res['status']=0;
    	$data['title']=trim(I('post.title'));
    	$data['issue_id']=I('post.issue');
    	$data['type']=I('post.type');
    	$data['content']=$_POST['content'];
    	$data['content']=str_replace("\n","<br />",$data['content']);
    	$imgids=I('post.imgids');
		
		
		
    	if($data['title']==''){ 
    		$res['info']="请输入故事标题";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if(!$data['issue_id']){
			$res['info']='请选择分类';
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
		}
		
    	if($data['content']==''){ 
    		$res['info']="请输入故事详情";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}else{ 
    		$content_count=get_ch_en_length($data['content'],1);
			if($content_count<30||$content_count>2000){ 
				$res['info']="故事详情需30字到2000字之间";
				exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
			}
    	}
		
		if(!$imgids){
			$res['info']="请上传故事图片";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
		}else{
			$cover_id_arr= explode(",",$imgids);
			$data['cover_id']=$cover_id_arr[0];
			$data['imgids']=$imgids;
		}
    	
		$data['update_time'] = time();
		
		if(!$id){
			$data['siteid']=SITEID;
			$data['create_time']=time();
			$data['uid']=is_login();
			$data['view_count']=0;
			$data['reply_count']=0;
			$rs=D('issue_content')->add($data);
			if($rs){ 
				$qrcode_url = set_qrcode(array('id'=>$rs),'issue');
				if($qrcode_url){
					$qrcode_data['siteid'] =  SITEID;
					$qrcode_data['uid'] =  is_login();
					$qrcode_data['linkid'] =  $rs;
					$qrcode_data['types'] =  'issue';
					$qrcode_data['url'] =  $qrcode_url;
					$qrcode_data['create_time'] =  time();
					D('qrcode')->add($qrcode_data);
				}
				
				D('Common/Dynamic')->sendMessage(is_login(),'Issue',op_t($data['title']),$rs,U('Issue/Index/issueContentDetail') );
				$res['status']=1;


				$res['info']='发布成功';
			}else{ 
				$res['info']='发布失败，请重试';
			} 
		}else{ 
			$rs=D('issue_content')->where("id = ".$id)->save($data);
			if($rs){ 
				$res['status']=1;
				$res['info']='编辑成功';

			}else{ 
				$res['info']='编辑失败，请重试';
			}

		}
		exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	
    }
    public function storyedit($id){ 
    	if(!$id){
    		$this->error('404 not found'); 	
    	}
    }


	//---故事收藏---
	public function issue_collection(){
	    
	      $map['uid'] = is_login();
          if(!empty($map['uid'])){
			  $id=$_POST['id'];
			  $data['uid']=$map['uid'];
			  $data['issue_id']=$id;
			  $data['siteid']=SITEID;
			  $res=D('ForumBookmark')->where("siteid=".SITEID." and uid={$map['uid']} and issue_id={$id}")->find();
			  if($res){
				 echo 1;
			 }else{
					 $data['create_time']=time();
					 $e=D('ForumBookmark')->data($data)->add();
					 if($e){
					   echo '0';
					 }
			   }
        }
	}

}

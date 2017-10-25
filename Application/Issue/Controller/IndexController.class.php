<?php
namespace Issue\Controller;
use Think\Controller;

class IndexController extends Controller
{
    /**
     * 业务逻辑都放在 WeiboApi 中
     * @var
     */
    public function _initialize()
    {
		$model_info = get_appinfo('Issue');
		if(!$model_info){
			$this->error('参数错误或没有找该应用');
		}
		$menutype = array();
		$menutype[$rs['id']]['tab'] = 'home';
		$menutype[$rs['id']]['title'] = '全部';
		$menutype[$rs['id']]['href'] = U($model_info['url']);
		$groupType = D('Issue')->where(array('status' => 1,'pid'=>0,'siteid'=>SITEID))->select();
		if($groupType){
			foreach($groupType as $m=> &$rs){
				$menutype[$rs['id']]['tab'] = 'category_'.$rs['id'];
				$menutype[$rs['id']]['title'] = $rs['title'];
				$menutype[$rs['id']]['href'] =  U('Issue/index/index',array('issue_id'=>$rs['id']));
				$childgroupType =D('Issue')->where(array('status' => 1,'pid'=>$rs['id'],'siteid'=>SITEID))->select();
				if($childgroupType){
					foreach($childgroupType as $mc=> &$rsc){
						$menutype[$rs['id']]['children'][$rsc['id']]['tab'] = 'category_'.$rsc['id'];
						$menutype[$rs['id']]['children'][$rsc['id']]['title'] = $rsc['title'];
						$menutype[$rs['id']]['children'][$rsc['id']]['href'] =  U('Issue/index/index',array('issue_id'=>$rsc['id']));
					}
				}
			}
		}
        $sub_menu =
            array(
                'left' =>$menutype,
				'btnhelf' =>
                    array(
                        array('title' => '发布故事', 'href' =>U('Issue/Index/add_story'), 'icon' => 'edit'),
                    )
            );
        $this->assign('sub_menu', $sub_menu);
		$this->assign('model_info', $model_info);
        $this->assign('current', 'home');
        $tree = D('Issue')->where(array('status' => 1 ,'siteid'=>SITEID))->getTree();
        $this->assign('tree', $tree);
		
    }

    public function index($page = 1, $issue_id = 0)
    {

        $issue_id = intval($issue_id);
        $issue = D('Issue')->field('id,pid')->where(array('id'=>$issue_id,'siteid'=>SITEID,'status' => 1))->find();
        $map['status'] = 1;
		$map['siteid'] = SITEID;
       
		if($issue_id){
			$map['issue_id'] = $issue_id;
			$this->assign('current', 'category_'.$issue_id);
		}
        $content = D('IssueContent')->field('id')->where($map)->order('create_time desc')->page($page, 16)->select();
        $totalCount = D('IssueContent')->where($map)->count();
		
        foreach ($content as $key => &$v) {

        	$content[$key] =  $this->cacheIssueContent($v['id']);
			
        }
        $this->assign('contents', $content);
        $this->assign('totalPageCount', $totalCount);
        $this->assign('top_issue', $issue['pid'] == 0 ? $issue['id'] : $issue['pid']);
        $this->setTitle('旅行故事');
        $this->display();
    }


    /*故事内容缓存*/
    private function cacheIssueContent($id){ 
    	$cacheIssueContent_keys = "Issue_ContentList_".SITEID."_".$id;

    	$content_S = S($cacheIssueContent_keys);
    	if($content_S){ 
    		return $content_S;
    	}else{ 
	    	$content = D('IssueContent')->field('id,siteid,uid,title,create_time,cover_id,view_count,issue_id,finalcity,tag,recommend_brand,related_event,is_recommend')->where("id = ".$id)->find();
    		$content['discreate_time'] = Date("Y-m-d H:i",$content['create_time']);
            $content['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $content['uid']);
            $content['issue'] = D('Issue')->field('id,title')->where(array('id'=>$content['issue_id'],'siteid'=>SITEID))->find();
			
			$content['comment_count'] = D('local_comment')->where(array('app'=>'Issue','row_id'=>$content['id'],'siteid'=>SITEID))->count();
			$content['collect_count'] = D('forum_bookmark')->where(array('issue_id'=>$content['id'],'siteid'=>SITEID))->count();
	        S($cacheIssueContent_keys,$content,1800);
	        return $content;

    	}
    }




    public function doPost($s_url = '',$id = 0, $cover_id = 0, $issue='', $title = '', $final_city = '', $tag = '', $content = '',$url = '',$related_event = '')
    {
        if (!is_login()) {
		   $this->redirect('Home/User/login');
        }

        if (trim(op_t($title)) == '') {
            $this->error('请输入标题');
        }
        if (!$cover_id) {
            $this->error('请上传封面');
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
            $this->error('请输入详情');
        }else{ 
   			$contentss =   trim($content);
        }
		
		if(app_isopen('Event')){
			if($related_event){
				$content['related_event'] = $related_event;
			}
		}
		if($url != '') {
			$url = trim(op_h($url));
			$pa = '/\b((?#protocol)https?|ftp):\/\/((?#domain)[-A-Z0-9.]+)((?#file)\/[-A-Z0-9+&@#\/%=~_|!:,.;]*)?((?#parameters)\?[A-Z0-9+&@#\/%=~_|!:,.;]*)?/i';
			preg_match($pa,$url,$r);		
			if($r[1] != 'http'){
				$this->error('请输入正确的URL地址！');
			}else{
				$content['url'] = $url;
			}
        }
		$tag = implode(',',$tag);
        $content = D('IssueContent')->create();
		$content['tag'] = $tag;
		$content['issue_id'] = $issue;
		$content['finalcity'] = $final_city;
        $content['content'] = $contentss;
        $content['title'] = trim(op_t($title));
		$content['update_time'] = time();
		$content['cover_id'] =  $cover_id ;

        if ($id) {
            $content_temp = D('IssueContent')->where(array('id'=>$id,'siteid'=>SITEID))->find();
            if (!is_administrator(is_login()) || !checked_admin(is_login())) { //不是管理员则进行检测
                if ($content_temp['uid'] != is_login()) {
                    $this->error('权限不够！');
                }
            }
            $content['uid'] = $content_temp['uid']; //权限矫正，防止被改为管理员
            $rs = D('IssueContent')->where(array('id'=>$id,'siteid'=>SITEID))->save($content);
            if ($rs) {


            	S("Issue_Detail_".SITEID."_".$id,null);
            	S("Issue_ContentList_".SITEID."_".$id,null);
            	S("Issue_Mobile_Detail_".SITEID."_".$id,null);
            	S("Issue_Mobile_ContentList_".SITEID."_".$id,null);

                $this->success('编辑成功。',$s_url);
            } else {
                $this->error('编辑失败。');
            }
        } else {
            /*if (modC('NEED_VERIFY', 0) && !is_administrator()) //需要审核且不是管理员
            {
                $content['status'] = 0;
                $tip = '但需管理员审核通过后才会显示在列表中，请耐心等待。';
                $user = query_user(array('nickname'), is_login());
                $admin_uids = explode(',', C('USER_ADMINISTRATOR'));
                foreach ($admin_uids as $admin_uid) {
                    D('Common/Message')->sendMessage($admin_uid, "{$user['nickname']}向旅行故事投了一份稿件，请到后台审核。", $title = '旅行故事投稿提醒', U('Admin/Issue/verify'), is_login(), 2);
                }
            }*/
			$content['uid'] = is_login();
			$content['siteid'] = SITEID;
			$content['status'] = 1;
			$content['create_time'] = time();
            $rs = D('IssueContent')->add($content);
            if ($rs) {
				
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
				
 				D('Common/Dynamic')->sendMessage(is_login(),'Issue',op_t($content['title']),$rs,U('Issue/Index/issueContentDetail') );


                $this->success('发布成功!' . $tip, $s_url);
            } else {
                $this->error('发布失败!');
            }
        }


    }
    public function doPostMobile($s_url = '',$id=0,$cover_id=0,$issue='',$title='',$content='',$imgids=''){ 
    	if (!is_login()) {
           // $this->error('请登录后再投稿。');
		   $this->redirect('Home/User/login');
        }
        if (trim(op_t($title)) == '') {
            $this->error('请输入标题');
        }
        if (!$cover_id) {
            $this->error('请上传封面');
        }
		if(!$issue){
			$this->error('请选择分类');
		}
		if(!$imgids){ 
			$this->error('请上传故事图集。');
		}
		if(trim($content) == '') {
			$this->error('请输入详情。');
		}
		$issue_content = D('issue_content')->where(array('id'=>$id,'siteid'=>SITEID))->find();
		if($issue_content['type']==1){ 
			$content_count=get_ch_en_length($content,1);
			if($content_count<30||$content_count>2000){ 
				$this->error('故事详情需30字到2000字之间');
			}
			$list['imgids']=$imgids;
			$list['content']=str_replace("\n","<br />",$content);

		}
		$list['issue_id'] = $issue;
		$list['cover_id'] =$cover_id;
		$list['title'] = op_t($title);
		$list['update_time'] = time();
		$rs_content = D('IssueContent')->where(array('id'=>$id,'siteid'=>SITEID))->save($list);
		if($rs_content){ 
			S("Issue_Detail_".SITEID."_".$id,null);
        	S("Issue_ContentList_".SITEID."_".$id,null);
        	S("Issue_Mobile_Detail_".SITEID."_".$id,null);
        	S("Issue_Mobile_ContentList_".SITEID."_".$id,null);
			$this->success('编辑成功', $s_url);
		}else{ 
			$this->error('编辑失败');
		}

    }


	public function add_story(){		
		if(!is_login()){
			$this->redirect('Home/User/login');
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
		
		$this->assign('url',$_SERVER['HTTP_REFERER']);
		$this->display();
	}
	
	public function edit_story($id){
			if(!is_login()){
				$this->redirect('Home/User/login');
			}
			$url = $_SERVER['HTTP_REFERER'];			
			$rs = D('Issue_content')->where(array('id'=>$id,'siteid'=>SITEID))->find();
			if(!$rs){
				$this->error('参数错误！');
			}
			if(!is_administrator($id) || !checked_admin($id)){
				if(is_login() != $rs['uid']){
					$this->error('权限不够！');
				}
			}
			if($rs['type']==1){ 
				$rs['content']=str_replace("<br />","\n",$rs['content']);
				$this->assign('content',$rs);
				$this->assign('url',$url);
				$this->display('edit_storyMobile');
			}else{ 
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
				$finalcity = get_citys($rs['finalcity']);
				$rs['final_city']['city'] = $finalcity['city'];
				$rs['final_city']['province'] = $finalcity['province'];
				$this->assign('url',$url);
				$this->assign('content',$rs);
				$this->display('edit_storyPc');

			}

	}

	public function issueContentDetail($id=0){ 

		$issueContentDetail_keys = "Issue_Detail_".SITEID."_".$id;

		$issue_content_S = S($issueContentDetail_keys);
		D('IssueContent')->where(array('id' => $id,'siteid'=>SITEID))->setInc('view_count');
		if($issue_content_S){ 
			$this->assign('content', $issue_content_S['content']);
			$issue_content['type'] = $issue_content_S['content']['type'];
			$this->assign('top_issue', $issue_content_S['top_issue']);
	        $this->assign('issue_id', $issue_content_S['issue_id']);
			$this->assign('issue', $issue_content_S['issue']);
			$this->assign('qrcode_link',$issue_content_S['qrcode_link']);

			if($issue_content['type']==1){ 
				$this->assign('imgissue',$issue_content_S['imgissue']);
			}else{ 

				$this->assign('desty',$issue_content_S['desty']);
			}

		}else{ 
			
			$issue_content = D('IssueContent')->where(array('id'=>$id,'siteid'=>SITEID))->find();
	        if (!$issue_content) {
	            $this->error('404 not found');
	        }
	        $issue = D('Issue')->where(array('id' => $issue_content['issue_id'],'siteid'=>SITEID))->find();
	        $this->assign('top_issue', $issue['pid'] == 0 ? $issue['id'] : $issue['pid']);
	        $this->assign('issue_id', $issue['id']);
			$this->assign('issue', $issue);
			$issue_content_ConS['top_issue'] = $issue['pid'] == 0 ? $issue['id'] : $issue['pid'];
			$issue_content_ConS['issue_id'] = $issue['id'];
			$issue_content_ConS['issue'] = $issue;
			$get_qrcode = D('qrcode')->where(array('types'=>'issue','siteid'=>SITEID,'linkid'=>$id))->find();
			if($get_qrcode){
				$this->assign('qrcode_link',$get_qrcode['url']);
				$issue_content_ConS['qrcode_link'] = $get_qrcode['url'];
			}

	        $issue_content['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $issue_content['uid']);
	        
	        if($issue_content['type']==1){ 
	        	$issue_sltu['imgid'] = explode(',',$issue_content['imgids']);
				foreach ($issue_sltu['imgid'] as $key => $a) {
					$issue_content['imgid'][$key]= D('picture')->where('id ='.$a)->getField('path');
				}
				$this->assign('imgissue',$issue_sltu['imgid']);
				$issue_content_ConS['imgissue'] =$issue_sltu['imgid'];
	        }else{ 
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
				$this->assign('desty',$desty);
				$issue_content_ConS['desty'] = $desty;

	        }

	        $this->assign('content', $issue_content);
	        $issue_content_ConS['content'] = $issue_content;
	       	S($issueContentDetail_keys,$issue_content_ConS,1800);

		} 

	
        $this->setTitle('{$content.title|op_t}' . '——旅行故事');
        $this->setKeywords($issue_content['title']);
        if($issue_content['type']==1){ 
        	$this->display('Index/issueMobileDetail');
        }else{ 
        	$this->display('Index/issuePCDetail');
        }


	}
	
    public function selectDropdown($pid)
    {	


        $issues = D('Issue')->where(array('pid' => $pid, 'status' => 1,'siteid'=>SITEID))->limit(999)->select();
        exit(json_encode($issues));


    }

    public function edit($id)
    {
       /* if (!check_auth('addIssueContent') && !check_auth('editIssueContent')) {
            $this->error('抱歉，您不具备投稿权限。');
        }*/
        $issue_content = D('IssueContent')->where(array('id'=>$id,'siteid'=>SITEID))->find();
        if (!$issue_content) {
            $this->error('404 not found');
        }
        if (!is_administrator(is_login()) || !checked_admin(is_login())) { //不是管理员则进行检测
            if ($issue_content['uid'] != is_login()) {
                $this->error('404 not found');
            }
        }
		$issue = D('Issue')->where(array('id' => $issue_content['issue_id'],'siteid'=>SITEID))->find();
		$event_content['tagarr'] = explode(',',$issue_content['tag']);
			foreach ($event_content['tagarr'] as $key => $a) {
				$issue_content['tags'][$a]['id'] = $a;
				$issue_content['tags'][$a]['name'] = get_event_tag($a);
			}
		$cid = $issue_content['finalcity'];
		$citys = get_citys("$cid");
        $this->assign('top_issue', $issue['pid'] == 0 ? $issue['id'] : $issue['pid']);
        $this->assign('issue_id', $issue['id']);
		$this->assign('citys',$citys);
        $issue_content['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $issue_content['uid']);
        $this->assign('content', $issue_content);
        $this->display();
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
						S("Issue_Detail_".SITEID."_".$id,null);
			        	S("Issue_ContentList_".SITEID."_".$id,null);
			        	S("Issue_Mobile_Detail_".SITEID."_".$id,null);
			        	S("Issue_Mobile_ContentList_".SITEID."_".$id,null);
					   echo '0';
					 }
			   }
        }
	}


	//故事删除
	public function issue_del($id){
		$issue_content = D('IssueContent')->where(array('id'=>$id,'siteid'=>SITEID))->find();
        if (!$issue_content) {
            $this->error('404 not found');
        }
        if (!is_administrator(is_login()) || !checked_admin(is_login())) { //不是管理员则进行检测
            if ($issue_content['uid'] != is_login()) {
                $this->error('404 not found');
            }
        }
		$page = I('page');
		$data['status'] = -1;
		$rs = D('issue_content')->where(array('id'=>$id,'siteid'=>SITEID))->save($data);
		if($rs){
			$this->redirect('Usercenter/Config/event_story',array('page'=>$page));
		}else{
			$this->error('删除失败！');
		}
	}
}

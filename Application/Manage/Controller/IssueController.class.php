<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;


class IssueController extends BaseController
{
	protected $issueContent;
	
    public function _initialize()
    {
        parent::_initialize();
		$this->issueContent = D('IssueContent');
		$this->category = D('Issue');
	   
	}
	
	// 故事主页
    public function index(){
		//读取列表
        $map = array('status' => array('egt',0),'siteid'=>SITEID);
        $list = $this->issueContent->field('id,siteid,uid,title,create_time,cover_id,view_count,issue_id,finalcity,tag,recommend_brand,related_event,is_recommend,status,update_time')->where($map)->order(array('is_recommend'=>'desc','recommend_brand'=>'desc','id'=>'desc'))->select();

     	foreach ($list as $key => &$value) {	
			$users  = query_user(array('id','username','nickname', 'email','mobile'), $value['uid']);
			$value['nickname'] =$users['nickname'];
			$list[$key]['discreate_time'] = date("Y-m-d H:i",$value['create_time']);
			$list[$key]['disupdate_time'] = date("Y-m-d H:i",$value['update_time']);
			
		}
		$this->assign('datainfo',$list);
	    $this->display();
    }
	
	
	

    public function story_edit($id = 0,$url = '', $cover_id = 0, $issue='', $title = '',$imgid='', $final_city = '', $tag = '', $content = '',$related_event = ''){ 
    	if(!$id){ 
    		$this->error('参数错误');
    	}
    	$issue_content = $this->issueContent->where(array('id'=>$id,'siteid'=>SITEID))->find();
    	if(IS_POST){

    		if(trim(op_t($title)) == '') {
    			
				$this->error('请输入标题。');
			}
			
			if(!$issue){
				$this->error('请选择分类');
			}
			if(trim($content) == '') {
				$this->error('请输入详情。');
			}

    		if($issue_content['type']==1){
    			$content_count=get_ch_en_length($content,1);
				if($content_count<30||$content_count>2000){ 
					$this->error('故事详情需30字到2000字之间');
				}
    			if(!$imgid){ 
					$this->error('请上传故事图集。');
				}	
			
				$list['imgids']=$imgid;
				$cover_id_arr= explode(",",$imgid);
				$list['cover_id']=$cover_id_arr[0];
				$list['content']=str_replace("\n","<br />",$content);

    		}else{ 
    			if(!$cover_id) {
					$this->error('请上传封面。');
				}
    			if($tag == ''){
					$this->error('请选择特色');
				}
				if($final_city == ''){
					$this->error('请完善目的地');
				}
				if(app_isopen('Event')){
					if($related_event){
						$list['related_event'] = $related_event;
					}
				}
				$tag = implode(',',$tag);
				
				$list['tag'] = $tag;
				$list['finalcity'] = $final_city;
				$list['content'] = $content;
				$list['cover_id'] =$cover_id;
			}	
			$list['issue_id'] = $issue;
			
			$list['title'] = op_t($title);
			$list['update_time'] = time();

			$rs_content = D('IssueContent')->where(array('id'=>$id,'siteid'=>SITEID))->save($list);
			if($rs_content){ 
				S("Issue_Detail_".SITEID."_".$id,null);
            	S("Issue_ContentList_".SITEID."_".$id,null);
            	S("Issue_Mobile_Detail_".SITEID."_".$id,null);
            	S("Issue_Mobile_ContentList_".SITEID."_".$id,null);
				 $this->success('编辑成功', $url);
			}else{ 
				 $this->error('编辑失败');
			}

    	}else{ 
			if (!$issue_content) {
				$this->error('404 not found');
			}
			$issue = D('Issue')->where(array('id' => $issue_content['issue_id'],'siteid'=>SITEID))->find();
			if($issue_content['type']==1){ 
				$issue_content['content']=str_replace("<br />","\n",$issue_content['content']);
				$issue_sltu['imgid'] = explode(',',$issue_content['imgids']);
				foreach ($issue_sltu['imgid'] as $key => $a) {
					$issue_content['imgid'][$key]= D('picture')->where('id ='.$a)->getField('path');
				}
				$this->assign('imgissue',$issue_sltu['imgid']);
			}else{ 
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
				$this->assign('citys',$citys);
			}
			$this->assign('top_issue', $issue['pid'] == 0 ? $issue['id'] : $issue['pid']);
			$this->assign('issue_id', $issue['id']);
			$issue_content['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $issue_content['uid']);
			$this->assign('content', $issue_content);
			$tree = D('Issue')->where('siteid='.SITEID)->getTree();
			$this->assign('tree', $tree);
			if($issue_content['type']==1){ 
				$this->display('story_editmobile');
			}else{ 
				$this->display('story_editpc');
			}
		}
    }

	
    /**
     * 设置推荐or取消推荐
     * @param $ids
     * @param $tip
     * autor:xjw129xjt
     */
    public function doRecommend( $tip)
    {
		$id = array_unique((array)I('id', 0));
		$ids = $id;
		$id = is_array($id) ? implode(',', $id) : $id;
	    if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}
		$map['id'] = array('in', $id);
		if( $tip == 1){ 
			$count = $this->issueContent->where(array('siteid'=>SITEID,'is_recommend'=>1,'status'=>array("eq","1")))->count();
			if($count+count($ids)>10){
				$this->error('亲!最多可推荐10个故事哦');
			}
		}
		
		$this->issueContent->where($map)->setField('is_recommend', $tip);
        $this->success('设置成功', $_SERVER['HTTP_REFERER']);
    }
    
	
    /**
     * 设置推荐or取消推荐
     * @param $ids
     * @param $tip
     * autor:xjw129xjt
     */
    public function doRecommendbrand( $tip)
    {
		$id = array_unique((array)I('id', 0));
		$ids = $id;
		$id = is_array($id) ? implode(',', $id) : $id;
	    if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}
		$map['id'] = array('in', $id);

		if($tip == 1){ 
			$count = $this->issueContent->where(array('siteid'=>SITEID,'recommend_brand'=>1,'status'=>array("eq","1")))->count();

			if($count+count($ids)>10){
				$this->error('亲!最多可推荐10个品牌故事哦');
			}

		}
		$this->issueContent->where($map)->setField('recommend_brand', $tip);
        $this->success('设置成功', $_SERVER['HTTP_REFERER']);
    }
   

	
	/*
	*故事分类
	**/
	public function issuetype(){
		$map = array('status' => array('egt',0),'siteid'=>SITEID);
        $list = $this->category->where($map)->page($page, $r)->order(array('id'=>'desc'))->select();
		foreach ($list as $key => &$value) {	
			$users  = query_user(array('id','username','nickname', 'email','mobile'), $value['uid']);
			$value['nickname'] =$users['nickname'];
		}
		$this->assign('datainfo',$list);
	    $this->display();
       
    }
	

	
	/*
	*添加修改* 2015-1-12 dlx
	***/
    public function issuetype_edit($sort='',$title='',$id=0,$pid=0,$customization=0){
		$isEdit = $id ? 1 : 0;
        if (IS_POST) {
		    if($title==''){
				$this->error('请填写分类名称');
			}
			if($sort!=''){
				if(!is_numeric($sort)){
					$this->error('排序必须为数字');
				}
			}
		
		    $issue_data['sort']	=	$sort;
			$issue_data['title']	=	$title;
			$issue_data['customization'] = $customization;
		    
			if ($isEdit) {
			    switch($customization){
					case 0:
						$issue_data['update_time'] = time();
						$rs_content = $this->category->where('id=' . $id)->save($issue_data);
					break;
					case 1:
						$res =  $this->category->where(array('siteid'=>SITEID,'customization'=>1,'id'=>array('neq',$id)))->find();
						if(!$res){ //--更改本身--
							$issue_data['update_time'] = time();
							$rs_content = $this->category->where('id=' . $id)->save($issue_data);
						}else{
							$this->category->where(array('id'=>$res['id'],'siteid'=>SITEID))->setField('customization',0);
							$rs_content = $this->category->where('id=' . $id)->save($issue_data);
						}
					break;
				}
				
            } else {
			   
				if($customization==1){
					$res =  $this->category->where(array('siteid'=>SITEID,'customization'=>1))->find();
					$this->category->where(array('id'=>$res['id'],'siteid'=>SITEID))->setField('customization',0);
				}
				$issue_data['pid']	= 0;
				$issue_data['create_time'] = time();
				$issue_data['update_time']=time();
				$issue_data['siteid']	=   SITEID;
				$issue_data['status'] = 1;
              
                $rs_content = $this->category->add($issue_data);
				
            }
            if ($rs_content) {
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Issue/issuetype'));
            } else {
                $this->error($isEdit ? '编辑失败' : '添加失败');
            }
			
        } else {
          
            $category_map['status'] = array('egt', 0);
			$category_map['siteid'] = SITEID;
            $goods_category_list = $this->category->where($category_map)->order('pid desc')->select();
            $options = array_combine(array_column($goods_category_list, 'id'), array_column($goods_category_list, 'title'));

            if ($isEdit) {
				
                $issue_data = $this->category->where('id=' . $id)->find();
			
            }else{ 
            	 $issue_data['sort'] = 0 ;
            } 
            $issue_data['page_title'] = $isEdit?'编辑故事分类':'新增故事分类';
            $datainfo = $issue_data;

            $this->assign('datainfo',$datainfo);
            $this->display();
        }
		
	}


}  

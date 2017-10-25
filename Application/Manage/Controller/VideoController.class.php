<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;

/*视屏管理*/
class VideoController extends BaseController
{
	protected $websit_video;
   
    public function _initialize()
    {
        parent::_initialize();
		$this->websit_video = D('websit_video');
	}
	

	
    public function index(){
		$map['siteid'] = SITEID;
        $list = $this->websit_video->where($map)->order(array('id'=>'desc'))->select();
        $this->assign('datainfo',$list);
        $this->display();
    }
	

	 public function doRecommend( $tip)
    {
        $id = array_unique((array)I('id', 0));
        $ids = $id;
        $id = is_array($id) ? implode(',', $id) : $id;
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        $map['id'] = array('in', $id);

        $tip = $_GET['tip'];
		$ids_count = count($_POST['ids']);
	    
		if(($tip == 0 || $tip==1) && $ids_count>1){
			$this->error('最多可以推荐1个视频');
		}
		
		if($tip==1){
			$rs = $this->websit_video->where("video_recommend=1 and siteid=".SITEID)->find();
				  $this->websit_video->where("id=".$rs['id'])->save(array('video_recommend'=>0));
		}
		
		$this->websit_video->where(array('id' => array('in', $ids)))->setField('video_recommend', $tip);
        $this->success('设置成功', $_SERVER['HTTP_REFERER']);
    }

	
    /*
	*添加~修改* 2015-1-13 dlx
	***/
    public function video_edit($id=0,$video_name='',$video_url=''){

		$isEdit = $id ? 1 : 0;
        if (IS_POST) {
			$video_name=op_t(trim($video_name));
			if($video_name==''){
				$this->error('视屏名称不能为空');
			}
			
			if(!preg_match('/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',$video_url)){
				$this->error('视屏连接地址不正确');
			}
			 
			
            $data['video_name']	  =	  $video_name;
			$data['video_url']    =   $video_url;
			$data['video_real_url'] = $this->getswf($video_url);
			$data['video_html5_url'] = $this->gethtml5($video_url);  
			if ($isEdit) {
			  $rs_content = $this->websit_video->where('id=' . $id)->save($data);
				
            }else{
				$data['siteid']	 =	SITEID;
				$data['status']  =  1;
				$rs_content = $this->websit_video->add($data);
			} 
			
            if ($rs_content) {
                $this->success($isEdit ? '编辑成功' : '添加成功', U('Video/index'));
            } else {
                $this->error($isEdit ? '编辑失败' : '添加失败');
            }
			
        }else{ 

            if($isEdit){ 
                $video_data = $this->websit_video->where('id=' . $id)->find();
            }
            $video_data['video_title'] = $isEdit?'编辑视频链接':'新增视频链接';
            $datainfo = $video_data;
            $this->assign('datainfo',$datainfo);
            $this->display();


        } 
	}

	
	Private function getswf($url='') {
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
	
	Private function gethtml5($url='') {
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
}  

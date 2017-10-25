<?php


namespace Video\Controller;

use Think\Controller;

/**
 * Class IndexController
 * @package Shop\Controller
 * @郑钟良
 */
class IndexController extends Controller
{
   
	public function index(){ 
		//获取视频URL
		//分页
		$map['siteid']=SITEID;
        $map['status']=1;
   		$video_num = D('websit_video')->where($map)->count();
   		

   		$count=D('websit_video')->where($map)->count();
		$Page       = new \Think\Page($count,6);
		$Page->setConfig('theme'," <u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>共%TOTAL_ROW%部视频</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END% ");
		$show       = $Page->show();// 
		$video = D('websit_video')->where($map)->limit($Page->firstRow .','.$Page->listRows)->select();
		//将视频图片URL拼接上
   		foreach ($video as $key => $value) {

   			$video[$key]['path'] = M('Picture')->field('path')->where("id = ".$value['pictures_id'])->find();
   			$video[$key]['path'] = $video[$key]['path']['path'];
   			//$pictures = M("Picture")->field('id,path')->where("id in ({$event_content['pictures_id']})")->select();
   		}
   		
		$this->assign('title','视频列表');
		$this->assign('video_num',$video_num);
		$this->assign('video',$video);
		$this->assign('show',$show);
		$this->display();
	}	

	public function detail($id=0){ 

		$map['siteid']=SITEID;
        $map['id'] = $id;
        $video =  D('websit_video')->where($map)->find();
		
		$this->assign('video',$video);
		$this->display();
	} 




}
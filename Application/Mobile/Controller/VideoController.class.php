<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Mobile\Controller;
use OT\DataDictionary;
 /*
获取优酷视频地址    
*/
 class YoukuFlv{
 
    static private $error   =   "";
    static private $result  =   array();
 
    static public function getYoukuFlv($url){
        //从url获取youkuid
        if(! $id    =   self::getYoukuId($url)){
            return false;
        }
        //获取youku视频详细信息
        $content    =   self::get_curl_contents( "http://v.youku.com/player/getPlayList/VideoIDS/".$id );
        $data   =   json_decode($content);
        if(!isset($data->data[0]->streamfileids)){
            self::$error    =   "Cannot find this video";
            return false;
        }
        foreach($data->data[0]->streamfileids AS $k=>$v){
            if($k == 'flv' || $k == 'mp4'){         
                //sid
                $sid=   self::getSid();
                //fileid
                $fileid =   self::getfileid($v,$data->data[0]->seed);
                $one=($data->data[0]->segs->$k);
                self::$result[$k]   = "http://f.youku.com/player/getFlvPath/sid/{$sid}_00/st/{$k}/fileid/{$fileid}?K={$one[0]->k}";
            }
        }
        if(empty(self::$result)){
            self::$error    =   "THIS VIOD IS NOT IN MP4 OR FLV FORMAT";
            return false;
        }else{
            return true;
        }
    } 
    static public function error(){
        return self::$error;
    }
 
    static public function result(){
        return self::$result;
    }
 
    static private function getYoukuId($url){       
        //url 不能为空
        if($url == "" || substr($url , 0 , 29) != "http://v.youku.com/v_show/id_"){
            self::$error    =   "URL IS ERROR";
            return false;
        }
        return substr($url , 29 , -5);      
    }
 
    static private function get_curl_contents($url, $second = 5){
        if(!function_exists('curl_init')) die('php.ini未开启php_curl.dll');
        $c = curl_init();
        curl_setopt($c,CURLOPT_URL,$url);
        $UserAgent=$_SERVER['HTTP_USER_AGENT'];
        curl_setopt($c,CURLOPT_USERAGENT,$UserAgent);
        curl_setopt($c,CURLOPT_HEADER,0);
        curl_setopt($c,CURLOPT_TIMEOUT,$second);
        curl_setopt($c,CURLOPT_RETURNTRANSFER, true);
        $cnt = curl_exec($c);
        curl_close($c);
        return $cnt;
    }
    static private function getSid() {
        $sid = time().(rand(0,9000)+10000);
        return $sid;
    }
    static private function getfileid($fileId,$seed) {
        $mixed = self::getMixString($seed);
        $ids = explode("*",$fileId);
        unset($ids[count($ids)-1]);
        $realId = "";
        for ($i=0;$i < count($ids);++$i) {
            $idx = $ids[$i];
            $realId .= substr($mixed,$idx,1);
        }
        return $realId;
    }
    static private function getMixString($seed) {
        $mixed = "";
        $source = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ/\\:._-1234567890";
        $len = strlen($source);
        for($i=0;$i< $len;++$i){
            $seed = ($seed * 211 + 30031) % 65536;
            $index = ($seed / 65536 * strlen($source));
            $c = substr($source,$index,1);
            $mixed .= $c;
            $source = str_replace($c, "",$source);
        }
        return $mixed;
    }
 }
/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class VideoController extends MobileController {
	 public function _initialize()
    {
        $model_info = get_appinfo('Video');
		if(!$model_info){
			$this->error('应用未开启');
		}
		$this->assign('model_info', $model_info);
    }
	//系统首页
    public function index()
    {	
		$get_url = json_encode($_GET);
		$this->assign('get_url', $get_url);
		$this->assign('peoples', $peoples);
        $this->setTitle($this->model_info['name']);
        $this->setKeywords($this->model_info['name']);
        $this->display();
    }
	
	public function get_video_index($page = 0)
    	{	
		$start = $page*10; 
		$recommend = I('recommend');
		if($recommend){
			$map['video_recommend'] = $recommend;
		}
		$map['siteid'] = SITEID;
		$map['status'] = 1;
		
		
		
		
		$video = D('websit_video')->where($map)->order('id desc')->limit($start,10)->select();
		foreach ($video as &$v) {	
			$v['url'] =U('Mobile/Video/detail',array('id'=>$v['id']));
        }
		
		exit(json_encode($video));
    }
	
	
	//系统首页
    public function detail($id='')
    {	
	
	/*if(YoukuFlv::getYoukuFlv("http://v.youku.com/v_show/id_XNTg1ODcyMzcy.html"))
 	{
    var_dump( YoukuFlv::result() );
	 }
	 else
	 {
		echo YoukuFlv::error();
	 }
	exit;*/
		$map['siteid'] = SITEID;
		$map['status'] = 1;
		$map['id'] = $id;
		$video = D('websit_video')->where($map)->find();
		$this->assign('content', $video);
        $this->setTitle($this->model_info['name']);
        $this->setKeywords($this->model_info['name']);
        $this->display();
    }	
	
	
	
    /**
     * 获取活动类型
     * @param $type_id
     * @return mixed
     * autor:xjw129xjt
     */
    private function getType($type_id)
    {
        $type = D('EventType')->where('id=' . $type_id)->find();
        return $type;
    }
}
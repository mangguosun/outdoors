<?php 
/**
 * 约伴 1.0
 * 2015-06-09
 */
namespace Mobile\Controller;
use Think\Controller;
class PartnerController extends Controller{
	public function _initialize(){
     $model_info = get_appinfo('Partner');
		if(!$model_info){
			$this->error('应用未开启');
		}
	}
	//约伴首页列表
	public function index(){
		$get_url = json_encode($_GET);
        $this->assign('get_url', $get_url);
        $this->setTitle('约伴');
		$this->display();
	}
	//列表异步加载
	public function get_partner($page =0,$typecode=0){
	   $start = $page*10;
       $partner=D("Common/Partner")->get_parevent($id,$start,$typecode);
        foreach ($partner as &$v) {
            $v['url'] =U('Mobile/Partner/parevent_sign',array('id'=>$v['id']));
            $v['thumb'] =getThumbImageById($v['picture_id'],200,150);
            $v['time'] = date("m-d H:i",$v['deadline']);
            if($v['participate_number']){
            	$v['people'] = $v['participate_number']."人参加";
            }else{
            	$v['people'] = '暂无参加者';
            }
            if($v['apply']){
            	$v['apply'] = '已报名';
            }
            if($v['event_status'] == 2){
            	$v['info'] = '报名已结束';
            }
        }
        exit(json_encode($partner));
	}
	//创建约伴
	public function partnercreate(){
	if (!is_login()) {
        $this->redirect('Mobile/User/login');
    	}
	$this->display();
	$this->setTitle('创建约伴');
	}
	// 创建活动页面
	public function set_partnercreate($title="",$start_time="",$deadline="",$details="",$imgids="",$address="",$partnertype="",$human_number=""){
		$res['status']=0;
		if(IS_POST){
			    $data['title']       = op_t(trim($title));
				$data['start_time']  = strtotime($start_time);
				$data['deadline']	 = strtotime($deadline);
				$data['details']     = op_t(trim($details));
				$data['picture_id']  = $imgids;
				$data['address']	 = op_t(trim($address));
				$data['event_type']  = $partnertype;
				$data['human_number']= op_t(trim($human_number));
		}
		if(!$data['title']){
    		$res['info']="请填写标题";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if(get_ch_en_length($data['title'])>=25){
    		$res['info']="标题输入过长";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if(!$data['start_time']){
    		$res['info']="请选择开始时间";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if(!$data['deadline']){
    		$res['info']="请选择截止报名时间";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if($data['start_time'] <= time()){
    		$res['info']="开始时间必须大于当前时间";
    		exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if(($data['deadline'] >= $data['start_time']) OR ($data['deadline'] <= time())){
    		$res['info']="截止报名时间大于当前时间且必须小于开始时间";
    		exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if(!$data['details']){
    		$res['info']="请填写介绍";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if(get_ch_en_length($data['details'])>=500){
    		$res['info']="活动介绍输入过长";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if(!$data['picture_id']){ 
    		$res['info']="请上传封面图";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if(!$data['address']){ 
    		$res['info']="请填写约伴地址";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if(get_ch_en_length($data['address'])>=25){ 
    		$res['info']="地址输入过长";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if(!$data['event_type']){ 
    		$res['info']="请选择类型";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if($data['human_number']){
    		if (!preg_match('/^\+?[1-9][0-9]*$/',$data['human_number'])) {
    		$res['info']="人数格式错误";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    		}
    	}
    	$data['releasetime'] = time();
		$data['uid']	     = is_login();
		$data['siteid']	  	 = SITEID;
		$partner=D('Common/Partner')->set_partner($data);
		if($partner){
			$res['status']=1;
			$res['info']="发布成功";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
		}else{
			$res['status']=0;
			$res['info']="发布失败";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
		}
	}
	// 约伴活动详情
	public function parevent_sign($id=""){
		if(!$id){
			$this->error('404 not found');
		}
		$parevent=D("Common/Partner")->get_parevent($id);
		$user=D("Common/Partner")->user($id);			//查询出参加活动的所有成员
		$website_info  ='http://'.$_SERVER['HTTP_HOST'];//本站点域名
	$parevent['details']=ludou_remove_width_height_attribute($parevent['details']);
	$this->assign('website_info',$website_info);
	$this->assign('user',$user);
	$this->assign('parevent',$parevent);
    $this->setTitle('约伴详情');
	$this->display();
	}
	//取消报名
	public function set_cancel($id){
		$cancel=D("Common/Partner")->set_partner_cancel(is_login(),$id);
		if($cancel){
			exit(json_encode(array('status'=>1,'info'=>'取消成功')));
		}else{
			exit(json_encode(array('status'=>0,'info'=>'取消失败')));
		}
	}
	//报名
	public function set_par_success($name="",$docVlGender="",$phone="",$udetails="",$partner_id=""){
		$res['status']=0;
		if(IS_POST){
				$data['name'] 		  = op_t(trim($name));
				$data['sex']		  = $docVlGender;
				$data['phone']		  = op_t(trim($phone));
				$data['details']	  = op_t(trim($udetails));
				$data['partner_id']   = $partner_id;
			}
		if((!$data['name'])OR(strlen($data['name'])>15)){ 	//姓名5个汉字之内
    		$res['info']="姓名格式错误";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if((!$data['phone'])OR(!get_every_check($name="mobile",$string=$data['phone']))){ 
    		$res['info']="手机号格式错误";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if(!$data['sex']){ 
    		$res['info']="请选选择性别";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
    	if(!$data['details']){ 
    		$res['info']="请填写自我介绍";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
    	}
		$data['registration_time']=	time();
		$data['uid']	      	  = is_login();
		$data['siteid']			  = SITEID;
		$partner_user=D('Common/Partner')->set_partner_user($data);
		if($partner_user){
			$res['status']=1;
			$res['info']="报名成功";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
		}else{
			$res['status']=0;
			$res['info']="你已经报名过此活动啦";
			exit(json_encode(array('status'=>$res['status'],'info'=>$res['info'])));
		}
	}
	//成员详细资料页面
	public function par_mubdet($id="",$uid=""){
	if((!$id)OR(!$uid)){
    		$this->error('404 not found');
    	}
    if (!is_login()) {
        $this->redirect('Mobile/User/login');
    	}
		$user=query_user(array('nickname','avatar128','sex','constellation','address','signature'),$uid);	
		if($user['constellation']){				//星座有则查询
			$user['constellation']=get_constellation($user['constellation']);
		}else{
			$user['constellation']="未填写";
		}
		$address=get_citys($user['address']);
		unset($user['address']);
		foreach(array_reverse($address) as $k=>$v){	//反向遍历
			if($v){
				if(!$user['constellation']){
					$user['address']=get_city($v);
				}else{
					$user['address'].='&nbsp;'.get_city($v);	//拼接地址
				}
			}
		  }
		//查询此活动下用户信息
		$partner_user=D('Common/Partner')->get_user($id,$uid);
		$partner=D('Common/Partner')->get_parevent($id);
	$this->assign('user',$user);					//uid用户信息	
	$this->assign('partner_user',$partner_user);	//活动参加信息
	$this->assign('parevent_uid',$partner['uid']);	//发起活动的uid
	$this->setTitle('约伴');
	$this->display();
	}
	//我发布的活动
	public function findpar_event(){
	if (!is_login()) {
        $this->redirect('Mobile/User/login');
    	}
    	$get_url = json_encode($_GET);
        $this->assign('get_url', $get_url);
        $this->setTitle('我发布的活动');
		$this->display();
	}
	//异步我发布的活动
	public function get_findpar_event($page =0){
	   $start = $page*10;
	   $uid=is_login();
       $partner=D("Common/Partner")->get_myparevent($uid,$start);
        foreach ($partner as &$v) {
            $v['url'] = U('Mobile/Partner/parevent_sign',array('id'=>$v['id']));
            $v['thumb'] =getThumbImageById($v['picture_id'],200,150);
            $v['time'] = date("m-d H:i",$v['deadline']);
            if($v['participate_number']){
            	$v['people'] = $v['participate_number']."人参加";
            }else{
            	$v['people'] = '暂无参加者';
            }
        }
        exit(json_encode($partner));
	}
	//我参加的活动
	public function mypar_event(){
	if (!is_login()) {
        $this->redirect('Mobile/User/login');
    	}
 	 	$get_url = json_encode($_GET);
        $this->assign('get_url', $get_url);
        $this->setTitle('约伴');
	$this->display();
	}
	public function get_mypar_event($page =0){
		$start = $page*10;
		$uid=is_login();
		$get_mypar_event=D('Common/partner')->get_mypar_event($uid,$start);
		  foreach ($get_mypar_event as &$v) {
            $v['url'] = U('Mobile/Partner/parevent_sign',array('id'=>$v['id']));
            $v['thumb'] =getThumbImageById($v['picture_id'],200,150);
            $v['time'] = date("m-d H:i",$v['deadline']);
            if($v['participate_number']){
            	$v['people'] = $v['participate_number']."人参加";
            }else{
            	$v['people'] = '暂无参加者';
            }
         }
        exit(json_encode($get_mypar_event));
	}
	// 约伴活动--用户页面
	public function parevent_user(){
	if (!is_login()) {
        $this->redirect('Mobile/User/login');
    }
    $this->setTitle('报名约伴');
	$this->display();
	}
}
?>

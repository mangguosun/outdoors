<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Mobile\Controller;


/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class TailorController extends MobileController
{
	 public function _initialize()
		{
		   if (!is_login()) {
				//$this->redirect('Mobile/User/login');
			}
			$model_info = get_appinfo('Tailor');
			if(!$model_info){
				$this->error('应用未开启');
			}
			$this->assign('model_info', $model_info);
			$this->setTitle($model_info['name']);
		}
	public function index(){
		$desty_in = return_desty_in();
		$desty_out = return_desty_out();
		$this->assign('desty_in',$desty_in);
		$this->assign('desty_out',$desty_out);
		$this->display();
	}
	public function doPost($minprice='',$maxprice='',$totalnum='',$childnum='',$minage='',$maxage='',$earlytime='',$latetime='',$traveldays='',
	$tag='',$other='',$unsure='',$reference = '',$desty_type='',$contact_name='',$contact_telephone='',$contact_email=''){
		if($minprice==''){ 
			$this->error('请填写最低预算！');
		}else{
			if(is_numeric($minprice) == ''){
				$this->error('最低预算必须为数字！');
			}
		}
		if($maxprice==''){ 
			$this->error('请填写最高预算！');
		}else{
			if(is_numeric($maxprice) == ''){
				$this->error('最高预算必须为数字！');
			}
		}
		if($minprice > $maxprice){
			$this->error('最低预算不能大于最高预算！');
		}
		if($totalnum==''){ 
			$this->error('请填写参加人数！');
		}else{
			if(is_numeric($totalnum) == ''){
				$this->error('参加人数必须为数字！');
			}
		}
		$data = D('event_tailor')->create();
		if($childnum != ''){
			if(is_numeric($childnum) == ''){
				$this->error('小孩人数必须为数字！');
			}
			if($minage >= $maxage){
				$this->error('年龄段选择不合法！');
			}
			if($childnum >= $totalnum) $this->error('小孩人数不能大于或等于总数！');
			$data['minage'] = $minage;
			$data['maxage'] = $maxage;
			$data['childnum'] = $childnum;
		}else{
			$data['minage'] = '';
			$data['maxage'] = '';
		}

		if(!$_POST['desty']){
		   $this->error('请选择目的地！');
		}else{
			$desty =implode(',',$_POST['desty']);	
		}
		
		
		if($unsure == ''){
			if($earlytime ==''){
				$this->error('请选择最早出发日期');
			}
			if($latetime ==''){
				$this->error('请选择最晚出发日期');
			}
			if(strtotime($earlytime) >= strtotime($latetime)){
				$this->error('最早出发日期不能大于最晚出发日期！');
			}
			$data['earlytime'] = strtotime($earlytime);
			$data['latetime'] = strtotime($latetime);
		}else{
			$data['earlytime'] = '';
			$data['latetime'] = '';
		}
		if($traveldays != ''){
			if(is_numeric($traveldays) == ''){
				$this->error('行程天数必须为数字！');
			}else{
				$data['traveldays'] = $traveldays;
			}
		}else{
			$data['traveldays'] = '';
		}
		if($tag == ''){
			$this->error('请选择活动特色！');
		}
		if($reference != ''){			
			$data['reference'] = $reference;
		}else{
			$data['reference'] = '';
		}
		if(trim(op_t($contact_name)) == ''){
			$this->error('请输入联系人姓名！');
		}
		$this->checkTelphone($contact_telephone);
		$this->checkEmail($contact_email);
		$tag = implode(',',$tag);
		$data['desty'] = $desty;
		$data['desty_type'] = $desty_type;
		
		$data['trade_sn'] = create_sn();
		$data['tag'] = $tag;
		$data['createtime'] = time();
		$data['siteid'] = SITEID;
		$data['uid'] = is_login();
		$data['ifdeal'] = 0;
		$rs = D('event_tailor')->add($data);
		if($rs){
			$tailor_info = D('event_tailor')->where(array('id'=>$rs,'siteid'=>SITEID))->find();
			$webinfo = json_decode(WEBSITEINFO,true);
			$title = $webinfo['webname'].'-私家定制通知';
			$address = $webinfo['email'];
			$createtime = date('Y-m-d H:i:s',$tailor_info['createtime']);
			/*
			$message = "私家定制活动：【定制需求单号：".$tailor_info['trade_sn']."】-【联系人：".$tailor_info['contact_name']."】-【联系手机：".$tailor_info['contact_telephone']."】-【联系Email：".$tailor_info['contact_email']."】-【提交时间：".$createtime."】已成功提交，请速去处理！";
			sendMail($address,$title,$message);
			*/
			$orderdata= array(
						'trade_sn'          =>  $tailor_info['trade_sn'],
						'contact_name'      =>	$tailor_info['contact_name'],
						'contact_telephone' =>	$tailor_info['contact_telephone'],
						'contact_email' 	=>	$tailor_info['contact_email'],
						'createtime'		=>	$createtime,
						'noticetype'    	=>  'custom_message',
						'webname'			=>  $webinfo['webname'],
						
					);
			$contactways=array($address);
			D('Message')->addSendMessage('send_email_to_user',$contactways,$orderdata,$level=0,$temp=1);
			$this->success('提交成功！',U('Mobile/Tailor/tailor_info',array('id'=>$rs)));
		}else{
			$this->error('提交失败！');
		}
	}
	public function tailor_info($id){
		if(!$id){
			$this->error('参数错误!');
		}
		$tailor_info = D('event_tailor_')->where(array('id'=>$id,'siteid'=>SITEID))->find();
		if(!$tailor_info) $this->error('没有相关需求信息');
		$webinfo = json_decode(WEBSITEINFO,true);
		$this->assign('webinfo',$webinfo);
		$this->assign('tailor_info',$tailor_info);
		$this->display();
	}
	public function checkEmail($email)
    {
		if($email == ''){
			$this->error('请输入联系邮箱！');
		}
		$pattern = "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
		if (!preg_match($pattern, $email)) {
			$this->error('邮箱格式错误。');
		}
    }
	 /*验证电话号码*/
     public function checkTelphone($telphone){
         if($telphone==''){
            $this->error('请输入联系手机！');
         }
		if(!preg_match("/^1[0-9]{10}$/",$telphone)){
            $this->error('请输入正确的手机号码');
        }    
     }

}
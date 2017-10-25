<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM1:13
 */
 
namespace Mobile\Controller;
set_time_limit(0);
use Think\Controller;

class ConfigController extends MobileController
{
	protected $userdata;
	protected $mTalkModel;
    public function _initialize()
    {
        if (!is_login()) {
            $this->redirect('Mobile/User/login');
        }
		$url = $_SERVER['QUERY_STRING'];
		$url_arr = explode('/',$url);
		$dest_url = $url_arr[2];
		$dest_url = $dest_url == ''?'index':$url_arr[2];
		$this->assign('dest_url',$dest_url);
		$this->userdata = query_user(array('uid','username','nickname', 'signature','is_use','title','email', 'mobile', 'avatar128','qq','rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation','space_url', 'icons_html', 'score', 'tox_money','title', 'fans', 'following', 'weibocount', 'rank_link', 'address'), $uid);
        $this->mTalkModel = D('Talk');
        $this->setTitle('个人中心');
	}


	public function set()
    {
		$this->display();
	}
	
	
	 public function ajax_get_address(){
		$shop_address=D('shop_address')->where("siteid=".SITEID." and uid=".is_login())->order("isdefault desc")->select();
		 foreach($shop_address as $key=>&$val){
			 $address = get_citys($val['address']);
			 $val['community']	=	get_city($address['community']);
			 $val['district']	=	get_city($address['district']);
			 $val['city']		=	get_city($address['city']);
			 $val['province']	=	get_city($address['province']);
		}
		exit(json_encode($shop_address));
	 }

	
	
	public function address()
    {
		$address_list=D('shop_address')->where("siteid=".SITEID." and uid=".is_login())->order('isdefault desc')->select();
		foreach($address_list as $key=>&$val){
			 $address = get_citys($val['address']);
			 $val['community']	=	get_city($address['community']);
			 $val['district']	=	get_city($address['district']);
			 $val['city']		=	get_city($address['city']);
			 $val['province']	=	get_city($address['province']);
		}
		
		$this->assign('address_list',$address_list);
		$this->display();
	}
	
	
	
    public function  address_add (){
        $this->display();
    }
	
	public function address_edit ($id=''){
		
		if($id){
			$address_data=D('shop_address')->where("siteid=".SITEID." and uid=".is_login()." and id=".$id)->find();
			$address = get_citys($address_data['address']);
			$address_data['community'] = $address['community'];
			$address_data['district'] = $address['district'];
			$address_data['city'] = $address['city'];
			$address_data['province'] = $address['province'];
			$this->assign('address_data',$address_data);
		}
        $this->display();
    }
	
    public function do_address($id='' ,$name='', $phone='', $email='',$address_community = 0, $address_district = 0, $address_city = 0, $address_province = 0,$detailed='',$isdefault='')
	{
		if (!is_login()) exit(json_encode(array('status'=>0,'msg'=>'请登录后再添加')));

		if (trim(op_t($name)) == '') {
			exit(json_encode(array('status'=>0,'msg'=>'请填写收件人姓名')));
		}

		if($phone==''){
		   exit(json_encode(array('status'=>0,'msg'=>'请填写手机号码')));
		}
		if(!get_every_check('mobile',$phone)){
			exit(json_encode(array('status'=>0,'msg'=>'请填写正确的手机号码')));
		}
		if($email != '' ){
			if(!get_every_check('email',$email)){
				exit(json_encode(array('status'=>0,'msg'=>'请填写正确的邮箱')));
			}
		}
		if($address_province ==0 || $address_province =='' ){
			exit(json_encode(array('status'=>0,'msg'=>'请选择地区')));
		}
		if(!$detailed){
			exit(json_encode(array('status'=>0,'msg'=>'请填写具体地址')));
		}
		
		if($email){
			$data['email'] = $email;
		}
		
		$data['name'] = $name;
		$data['phone'] = $phone;
		$cityparam['province'] = $address_province;
		$cityparam['city'] = $address_city;
		$cityparam['district'] = $address_district;
		$cityparam['community'] = $address_community;
		$data['address'] = set_city($cityparam);
		$data['detailed'] =$detailed;

		if($isdefault){
			$data['isdefault'] =1;
		}else{
			$data['isdefault'] =0;
		}

		if($id){
			$res = D('shop_address')->where("id=".$id)->save($data);
			if ($res) {
				if($isdefault){
					D('shop_address')->where("siteid=".SITEID." and uid=".is_login()." and id !=".$id)->save(array('isdefault'=>0));
				}
				 exit(json_encode(array('status'=>1,'msg'=>'成功修改')));
			} else {
				exit(json_encode(array('status'=>0,'msg'=>'未更改数据')));
			}
		}else{
			$data['uid'] = is_login();
			$data['siteid'] = SITEID;
			$data['create_time'] = time();
			$data['status'] = 1;
			$res = D('shop_address')->add($data);
			if ($res) {
				if($isdefault){
					D('shop_address')->where("siteid=".SITEID." and uid=".is_login()." and id !=".$res)->save(array('isdefault'=>0));
				}
				 exit(json_encode(array('status'=>1,'msg'=>'添加成功')));
			} else {
				exit(json_encode(array('status'=>0,'msg'=>'添加失败')));
			}
		}

	}

    //删除---收货地址--------
    public function address_delete(){
          $id=I('id');
		  if(!$id){$this->success('请选择要删除的收货地址');}
          $res=D('shop_address')->where("id=$id")->delete();
          if($res){
             $this->success('删除成功');
          }else{
		     $this->error('删除失败');
		  }
    }
    public function camera () {
        $this->display();
    }
	public function wallet()
    {
		
		$wallet = $this->userdata;
		$card_arr  = D('pointcard_user')->where(array('userid'=>is_login(),'siteid'=>SITEID))->select();
		$card_final = array();
		foreach($card_arr as $key => &$val){
			$cardlist=D('Common/Pointcard')->check_card($val['cardid']);
			if(!$cardlist['status']){
				continue;
			}else{
				$card_final[] = $val;
			}
		}
		$wallet['pointcard']  =count($card_final);
		$this->assign('wallet',$wallet);
		$this->display();
	}	
	
	public function mypointcard()
    {
		$pointcard  = D('pointcard_user')->where(array('userid'=>is_login(),'siteid'=>SITEID))->select();
		foreach($pointcard as $key=> &$val){
			$pointcard_arr = D('pointcard')->where(array('cardid'=>$val['cardid'],'siteid'=>SITEID))->find();
			$val['card_info'] = 	$pointcard_arr;	
			$val['card_status'] = get_pointcard($val['cardid']);
		}
		$this->assign('pointcard',$pointcard);
		$this->display();
	}		
	
	

	public function myissue()
    {
		
		$uid = is_login();
		$map = "uid = $uid and siteid = ".SITEID;
		$issue_arr = D('issue_content')->field('id,siteid,uid,title,create_time,cover_id,view_count,issue_id,finalcity,tag,recommend_brand,related_event,is_recommend')->where($map)->order('id desc')->select();

		 foreach ($issue_arr as &$v) {
            $v['user'] = query_user(array('id', 'nickname', 'mobile_space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['category'] = D('Issue')->field('id,title')->where(array('id'=>$v['issue_id'],'siteid'=>SITEID))->find();
			$v['url'] =U('Mobile/Issue/issuecontentdetail',array('id'=>$v['id']));
			$v['thumb'] =getThumbImageById($v['cover_id'],400,300);
			$finalcity = get_citys($v['finalcity']);
			$v['finalcity_province'] = get_city($finalcity['province']);
			$v['finalcity_city'] = get_city($finalcity['city']);
			
			$v['comment_count'] = D('local_comment')->where(array('app'=>'Issue','row_id'=>$v['id'],'siteid'=>SITEID))->count();
			$v['collect_count'] = D('forum_bookmark')->where(array('issue_id'=>$v['id'],'siteid'=>SITEID))->count();
			
        }

        $get_url = json_encode($_GET);
        $this->assign('get_url', $get_url);
		$this->assign('myissue_arr',$issue_arr);
		$this->display();
	}

    public function get_myissue($page=0){
        $start = $page*10;
        $uid = is_login();
        $map = "uid = $uid and siteid = ".SITEID;
        $issue_arr = D('issue_content')->field('id,siteid,uid,title,create_time,cover_id,view_count,issue_id,finalcity,tag,recommend_brand,related_event,is_recommend')->where($map)->order('id desc')->limit($start,10)->select();

        foreach ($issue_arr as &$v) {
            $v['create_time'] = date("Y-m-d H:i:s",$v['create_time']);
            $v['user'] = query_user(array('id', 'nickname', 'mobile_space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['category'] = D('Issue')->field('id,title')->where(array('id'=>$v['issue_id'],'siteid'=>SITEID))->find();
            $v['url'] =U('Mobile/Issue/issuecontentdetail',array('id'=>$v['id']));
            $v['thumb'] =getThumbImageById($v['cover_id'],400,300);
            $finalcity = get_citys($v['finalcity']);
            $v['finalcity_province'] = get_city($finalcity['province']);
            $v['finalcity_city'] = get_city($finalcity['city']);

            $v['comment_count'] = D('local_comment')->where(array('app'=>'Issue','row_id'=>$v['id'],'siteid'=>SITEID))->count();
            $v['collect_count'] = D('forum_bookmark')->where(array('issue_id'=>$v['id'],'siteid'=>SITEID))->count();

        }

        exit(json_encode($issue_arr));
    }
    public function index()
    {		    
		
		$uid=is_login();
		
		
		
		$collection_iisue=D('thinkox_forum_bookmark')->Table(array('thinkox_issue_content'=>'i','thinkox_forum_bookmark'=>'f'))->field('i.title,i.id,f.create_time')->where("f.siteid=".SITEID." and i.id=f.issue_id and f.uid=$uid")->count();
		$collection_event=D('thinkox_forum_bookmark')->Table(array('thinkox_event'=>'e','thinkox_forum_bookmark'=>'f'))->field('e.id,e.uid,e.cover_id,e.title,e.create_time,e.update_time,e.traveldays,e.begincity')->where("f.siteid=".SITEID." and e.id=f.content_id and f.uid=$uid")->count();

		$my_event = D('event_attend')->where(array('uid'=>is_login()))->count();
		$contacts = D('member_contacts')->where(array('uid'=>is_login(),'siteid'=>SITEID))->count();
		
		
		$user = $this->userdata;
		$address = get_citys($user['address']);
		$user['addresss']['community'] = get_city($address['community']);
		$user['addresss']['district'] = get_city($address['district']);
		$user['addresss']['city'] = get_city($address['city']);
		$user['addresss']['province'] = get_city($address['province']);
		$this->assign('user', $user);
		
		$this->assign('collection_iisue', $collection_iisue);
		$this->assign('collection_event', $collection_event);
		
		
		
		
		
		$this->assign('my_event', $my_event);	 
		$this->assign('contacts', $contacts);
		$this->display();
    }
  /*添加活动联系人表*/
   public function contacts($tab = '')
    {   
		//调用API获取基本信息
		//TODO tox 获取省市区数据
		//显示页面
		$this->assign('user', $this->userdata);
		//$map['uid'] = is_login();
        $uid = is_login();
		$contacts_arr = D('member_contacts')->where( "uid=".$uid." and siteid = ". SITEID)->order('id desc')->select();

		
		
		$tab = op_t($tab);
		$this->assign('tab', $tab);
		$this->assign('contacts_arr', $contacts_arr);
		
		
		
		$this->getExpandInfo();
		$this->display();
	}
    public function contacts_add($uid = null, $tab = '')
    {    
	    $this->contacts_choice();
        $this->display('contacts_add');
    }
	public function doAdd($schedule_id = '',$event_id = '',$ordertype = '',$realname, $sex, $card, $telephone, $qq, $email,$bloodtype,$allergies,$emergencycontact,$emergencyphone,$role,$role_description)
    {
        if (!is_login()) $this->error('请登录后再添加。');
		
		$this->check_realname($realname);
		$this->checkSex($sex);
		$this->check_card($card);
		$this->check_telephone($telephone);
		if($qq !=''){
		   $this->check_qq($qq);
		   }
        $this->check_contacts_email($email);/*自已添加邮箱可以一样*/
		if($emergencycontact==''){
			   $this->error('请填写紧急联系人');
			}
		if($emergencycontact == $realname){ 
			$this->error('紧急联系人不能为自己');
		}

	    
		if($telephone==$emergencyphone){
			   $this->error('紧急联系人与联系电话不能一致');
			}
		$this->check_emergencyphone($emergencyphone);
		
        $check = D('member_contacts')->where(array('uid' => is_login(), 'card' =>$card))->select();
	    if (!$check) {
            $data['uid'] = is_login();
            $data['realname'] = $realname;
            $data['sex'] = $sex;
			$data['card'] = strtoupper($card);//转为大写
			$data['telephone'] = $telephone;
			$data['qq'] = $qq;
			$data['email'] = $email;
            $data['creat_time'] = time();
			if(!$_POST['accpre'][0]){
				$this->error('请选择住宿偏好！');
			}else{
				$data['accpre'] = implode(",",$_POST['accpre']);
			}
			
			$data['allergies'] = $allergies;
			$data['bloodtype'] = $bloodtype;
			$data['emergencycontact'] = $emergencycontact;
			$data['emergencyphone'] = $emergencyphone;
			$data['role'] = $role;
			$data['role_description'] = $role_description;
			$data['siteid'] = SITEID;
			$data['status'] = 1;
            $res = D('member_contacts')->add($data);
            if ($res) {
				 $this->success('添加修改。',U('Mobile/Config/contacts'));

            } else {
                $this->error('添加失败。', '');
            }
        } else {
            $this->error('您已经添加过这个常用联系人了。', '');
        }
    }
	
	public function ajax_doAdd($realname, $card, $telephone, $qq, $email,$emergencycontact,$emergencyphone,$sex,$qq,$bloodtype,$allergies,$role,$role_description,$nickname,$hand = '',$age)
    {
		if (!is_login()) exit(json_encode(array('status'=>0,'msg'=>'请登录后再添加')));
		
		if (trim(op_t($realname)) == '') {
            exit(json_encode(array('status'=>0,'msg'=>'请输入真实姓名')));
        }
		if($card ==''){
            exit(json_encode(array('status'=>0,'msg'=>'请输入身份证或护照号！')));
        }

        if(preg_match("/[\x7f-\xff]/",$card)){
            exit(json_encode(array('status'=>0,'msg'=>'请正确输入身份证或护照号！')));
        }
		
		if($telephone==''){
           exit(json_encode(array('status'=>0,'msg'=>'请填写手机号码')));
        }
		if(!preg_match("/^1[0-9]{10}$/",$telephone)){
            exit(json_encode(array('status'=>0,'msg'=>'请输入正确的手机号码')));
        }
        $participant_t = getWebsitConfig('participant');
	    $participant = explode(',', $participant_t); 
	    $participant_name =  get_participant('',true);
	    $accpre =  $_POST['accpre'][0];
	  	if( $participant_t != ''){ 
	  		foreach ( $participant as $key => &$val){
		    	$dataListdis[$val] =  $participant_name[$val];
		    	if($$participant_name[$val] == '' ){
		    		exit(json_encode(array('status'=>0,'msg'=>'请填写'.get_participant($val))));
		    	}
		    }
	  	}

	  	if($age != ''){ 
	  		if(!is_numeric($age) ){ 
	  			exit(json_encode(array('status'=>0,'msg'=>'年龄请输入数字！')));
	  		}
	  		if( ($age < 0) ||  ($age > 130)){ 
	  			exit(json_encode(array('status'=>0,'msg'=>'请输入合理年龄！')));
	  		}

	  	}

		if($email !=''){
			if (!preg_match("/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i", $email)) {
				exit(json_encode(array('status'=>0,'msg'=>'邮箱格式错误')));
			}
		}
		if($emergencycontact!=''){
			if($emergencycontact == $realname){ 
				 exit(json_encode(array('status'=>0,'msg'=>'紧急联系人不能为自己')));
			} 
		}
		if($emergencyphone!=''){
			if(!preg_match("/^1[0-9]{10}$/",$emergencyphone)){
				exit(json_encode(array('status'=>0,'msg'=>'请输入正确的紧急联系人手机号码')));
			}
			if($telephone==$emergencyphone){
			   exit(json_encode(array('status'=>0,'msg'=>'紧急联系人与联系电话不能一致')));
			}
		}
		if($qq !=''){
		    if(!preg_match("/^[1-9]*[1-9][0-9]*$/",$qq)){
				exit(json_encode(array('status'=>0,'msg'=>'请输入正确的QQ号码')));
			}
		}
		if($_POST['accpre']){
			$accpre =implode(',',$_POST['accpre']);
		}
		
		$data['realname'] = $realname;
		$data['sex'] = $sex;
		$data['card'] = strtoupper($card);//转为大写
		$data['telephone'] = $telephone;
		$data['nickname'] = $nickname;
		$data['qq'] = $qq;
		$data['email'] = $email;
		$data['creat_time'] = time();
		$data['accpre'] = $accpre;
		$data['allergies'] = $allergies;
		$data['bloodtype'] = $bloodtype;
		$data['emergencycontact'] = $emergencycontact;
		$data['emergencyphone'] = $emergencyphone;
		$data['role'] = $role;
		$data['role_description'] = $role_description;
		 $data['age'] = $age;
        $data['hand'] = $hand;
		$id=op_t($_POST['id']);
		if($id){
			$res = D('member_contacts')->where("id=".$id)->save($data);
			if ($res) {
				 exit(json_encode(array('status'=>1,'msg'=>'成功修改')));
			} else {
				exit(json_encode(array('status'=>0,'msg'=>'未更改数据')));
			}
			
		}else{
			
			 $check = D('member_contacts')->where(array('uid' => is_login(), 'card' =>$card))->select();
	   		 if (!$check) {
				$data['uid'] = is_login();
				$data['siteid'] = SITEID;
				$data['status'] = 1;
				$res = D('member_contacts')->add($data);
				if ($res) {
					 exit(json_encode(array('status'=>1,'msg'=>'添加成功')));
				} else {
					exit(json_encode(array('status'=>0,'msg'=>'添加失败')));
				}
				
			} else {
				exit(json_encode(array('status'=>0,'msg'=>'您已经添加过这个常用联系人了')));
			}
			
		}
		
    }
	
	
	
	
   //执行修改过之后
    public function contacts_edit($uid=null,$tab=''){
        $id=$_GET['id'];
        $data=D('member_contacts')->where("id=$id")->find();

        $this->contacts_choice();
        $this->assign('data',$data);
        $this->display();
    }

   public function contacts_choice(){ 
   		$participant = getWebsitConfig('participant');
	    $participant = explode(',', $participant); 
	    $dataList = get_participant('',true);
	    foreach ( $dataList as $key => &$val){
	    	if(in_array($key, $participant)){ 
	    		$dataListdis[$key][0] = "style='display:none'";
	    	 	$dataListdis[$key][1] = "";
	    	 	$dataListdis[$key]['name'][0] = '';
	    	 	$dataListdis[$key]['name'][1] = $val;
	    	}else{ 
	    		$dataListdis[$key][0] = "";
	    		$dataListdis[$key][1] = "style='display:none '";
	    		$dataListdis[$key]['name'][0] = $val;
	    	 	$dataListdis[$key]['name'][1] = '';
	    	}
	    }
	    $this->assign('dataListdis',$dataListdis);
    }
	 //----edit----
    public function doEdit($id,$realname, $sex, $card, $telephone, $qq, $email,$bloodtype,$allergies,$emergencycontact,$emergencyphone,$role,$role_description)
    {
            $id=op_t($id);
            $this->check_realname($realname);
            $this->check_card($card);
            $this->check_telephone($telephone);
			if($qq !=''){
                $this->check_qq($qq);
			}
            $this->check_contacts_email($email);
            $this->checkSex($sex);
			if($emergencycontact==''){
			   $this->error('请填写紧急联系人');
			}
		    $this->check_emergencyphone($emergencyphone);
			
			if($telephone==$emergencyphone){
			   $this->error('紧急联系人与联系电话不能一致');
			}
			if(!$_POST['accpre'][0]){
				$this->error('请选择住宿偏好！');
			}else{
				$data['accpre'] = implode(",",$_POST['accpre']);
			}
			$data['allergies'] = $allergies;
			$data['bloodtype'] = $bloodtype;
			$data['emergencycontact'] = $emergencycontact;
			$data['emergencyphone'] = $emergencyphone;
			$data['role'] = $role;
			$data['role_description'] = $role_description;
            $data['realname'] = $realname;
            $data['sex'] = $sex;
            $data['card'] = strtoupper($card);//转为大写
            $data['telephone'] = $telephone;
            $data['qq'] = $qq;
            $data['email'] = $email;
            $res = D('member_contacts')->where("id='$id'")->save($data);
            if ($res) {
                $this->success('成功修改。',U('Mobile/Config/contacts'));
            } else {
                $this->error('修改失败。', '');
            }
   }
    //删除---常用报名人--------
    public function contacts_delete(){
          $id=I('id');
          $res=D('member_contacts')->where("id=$id")->delete();
          if($res){
             $this->success('删除成功');
          }else{
		     $this->error('删除失败');
		  }
    }
    
    /*扩展得到权限*/
    public function doevent_edit(){
           
           $uid=is_login();
           $is_use=$_POST['is_use'];
           $data['uid']=$uid;
           $data['is_use']=$is_use;
           $cate=D('member')->where("uid=$uid")->save($data);
            if($cate){
              //$this->success('申请成功等待管理员审核',U('Config/index'));
            	echo 1;
            }else{
               //$this->error('保存失败!'); 
            	echo 0;
           }
         }
    /*我的收藏*/
	public function my_collection(){
	         $status=I('status');
			 switch($status){
			    case 0:
				  $this->docollection();
				break;
				case 1:
				  $uid=$map['uid']=is_login();
					if(!empty($uid)){
					   $mark=D('thinkox_forum_bookmark'); //---
					   $list=$mark->Table(array('thinkox_issue_content'=>'i','thinkox_forum_bookmark'=>'f'))
								  ->field('i.title,i.id,f.create_time')
								  ->where("f.siteid=".SITEID." and i.id=f.issue_id and f.uid=$uid")
								  ->select();
						//--i.id--可以找到故事的页面--9.21--	
					   $this->assign('issue_list',$list);
					}
				break;
		    }
			$this->assign('status',$status);
			$this->assign('user',$this->userdata);
			$this->display();
	}
    /*--活动收藏--*/
    public function docollection($type='forum_event',$page=1){
            $uid=$map['uid']=is_login();
            if(!empty($uid)){
               $mark=D('thinkox_forum_bookmark'); //---
               $list=$mark->Table(array('thinkox_event'=>'e','thinkox_forum_bookmark'=>'f'))
                          ->field('e.id,e.uid,e.cover_id,e.title,e.create_time,e.update_time,e.traveldays,e.begincity')
                          ->where("f.siteid=".SITEID." and e.id=f.content_id and f.uid=$uid")
                          ->select();
               
			   $this->assign('list',$list);
            }
        }
	/*删除收藏活动*/
	/*del*/
    public function del_collection(){
            $mark=D('forum_bookmark')->where(array('siteid'=>SITEID,'content_id'=>I('id'),'uid'=>is_login()))->delete();
            if($mark){
                $this->success('删除成功');
            }else{
                 $this->error('删除失败');
            }
      }
	   /*删除--故事收藏--*/
	public function issue_collection_del(){
	      $mark=D('forum_bookmark')->where(array('siteid'=>SITEID,'issue_id'=>I('id'),'uid'=>is_login()))->delete();
            if($mark){
                $this->success('删除成功');
            }else{
                 $this->error('删除失败');
            } 
	
	 }

    /**验证用户名
     * @param $nickname
     * @auth 陈一枭
     */
    private function checkNickname($nickname)
    {
        $length = mb_strlen($nickname, 'utf8');
        if ($length == 0) {
            $this->error('请输入昵称。');
        } else if ($length >= 10) {
            $this->error('昵称不能超过10个字。');
        } else if ($length <= 1) {
            $this->error('昵称不能少于1个字。');
        }
        $match = preg_match('/^(?!_|\s\')[A-Za-z0-9_\x80-\xff\s\']+$/', $nickname);
        if (!$match) {
            $this->error('昵称只允许中文、字母、下划线和数字。');
        }

        $map_nickname['nickname'] = $nickname;
        $map_nickname['uid'] = array('neq', is_login());
		$map_nickname['siteid'] = SITEID;
        $had_nickname = D('Member')->where($map_nickname)->count();
        if ($had_nickname) {
            $this->error('昵称已被人使用。');
        }
    }


    /**验证签名
     * @param $signature
     * @auth 陈一枭
     */
    private function checkSignature($signature)
    {
        $length = mb_strlen($signature, 'utf8');
        if ($length >= 30) {
            $this->error('签名不能超过30个字');
        }
        /*中英文*/
        /*if(!preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$signature)){
            $this->error('个性签名只能为中文');
        }*/

    }

    /*验证qq*/
    public function check_qq($qq)
     {
        if(!preg_match("/^[1-9]*[1-9][0-9]*$/",$qq)){
            $this->error('请输入正确的QQ号码');
        }
        if($qq==''){
            $this->error('不能为空');
        }
     }
     /*验证电话号码*/
     public function checkTelphone($telphone){
         if($telphone==''){
            $this->error('手机号码不能为空');
         }
		if(!preg_match("/^1[0-9]{10}$/",$telphone)){

            $this->error('请输入正确的手机号码');
        }
       
     }
     /*验证自我介绍*/
     public function check_self_introduction($self_introduction){
        if($self_introduction == ''){
            $this->error('自我介绍不能为空');
        }
        $length = mb_strlen($self_introduction, 'utf8');
        if($length >= 500){
            $this->error('自我介绍不能超过500个字');
        }
        /*if(!preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$self_introduction)){
            $this->error('自我介绍只能为中文');
        }*/

     }
     
     /*验证身份证号码*/
     public function check_card($card){
        if($card ==''){
            $this->error('请输入身份证号');
        }
        if(!preg_match("/(^\d{15}$)|(^\d{17}([0-9]|X|x)$)/",$card)){
            $this->error('请输入正确15-18位身份证号码');
        }
     }
     /*验证添加联系人中的电话号码*/
     public function check_telephone($telephone){
        if(!preg_match("/^1[0-9]{10}$/",$telephone)){
            $this->error('请输入正确的联系电话。');
          }
        if($telephone==''){
            $this->error('请填写手机号码');
        }
     }
	 /*验证紧急联系人号码*/
	 public function check_emergencyphone($emergencyphone){
	      if($emergencyphone==''){
            $this->error('请填写紧急联系人手机号码');
           }
		  if(!preg_match("/^1[0-9]{10}$/",$emergencyphone)){
            $this->error('请输入正确的紧急联系人手机号码。');
          }
    }
        
	public function check_realname($realname){
        if (trim(op_t($realname)) == '') {
            $this->error('请输入真实姓名。');
        }
     }
     /*验证微博号码*/
     public function checkMicroBo($microBo){
        if($microBo==''){
            $this->error('微博号码不能为空');
        }

     }
     /*选中判断*/
     /*
      checked
       0 审核没有通过
       1 审核通过
       2 审核未通过
     */
     private function check_checked($uid){
     	//-----------申请过后的记录-----------
        $group_user=D('Member')->field('is_use,checked')
                               ->where("uid=$uid and is_use>1")
                               ->find();
         if($group_user){
              $this->assign('u_status',$group_user);
              
              $this->assign('num','2');
              $checked=$group_user['checked'];
              if($checked=='0'){
                $this->assign('check',$checked);
              }else{
                $this->assign('check','1');
              }
           }

     }
    /**用户信息表与
    用户资料两表连查*/
    private function data($uid){
        $data_user=D('thinkox_member');
           return $data=$data_user->Table(array('thinkox_member'=>'m','thinkox_ucenter_member'=>'u'))
                             ->field('m.uid,m.nickname,m.qq,m.score,m.signature,m.pos_province,m.pos_city,m.pos_district,m.is_use,m.pos_community,m.self_introduction,m.sex,m.telphone,m.real_name,u.email,u.username')
                             ->where("m.uid=u.id and m.uid=$uid")
                             ->find();
      }

    /**获取用户扩展信息
     * @param null $uid
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function getExpandInfo($uid = null)
    {
        $profile_group_list = $this->_profile_group_list($uid);
        if ($profile_group_list) {
            $info_list = $this->_info_list($profile_group_list[0]['id'], $uid);
            $this->assign('info_list', $info_list);
            $this->assign('profile_group_id', $profile_group_list[0]['id']);
            //dump($info_list);exit;
        }

        $this->assign('profile_group_list', $profile_group_list);
    }


    /**显示某一扩展分组信息
     * @param null $profile_group_id
     * @param null $uid
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function showExpandInfo($profile_group_id = null, $uid = null)
    {
        $res = D('field_group')->where(array('id' => $profile_group_id, 'status' => '1'))->find();
        if (!$res) {
            $this->error('信息出错！');
        }
        $profile_group_list = $this->_profile_group_list($uid);
        $info_list = $this->_info_list($profile_group_id, $uid);
        $this->assign('info_list', $info_list);
        $this->assign('profile_group_id', $profile_group_id);
        //dump($info_list);exit;
        $this->assign('profile_group_list', $profile_group_list);
        $this->defaultTabHash('expand-info');
        $this->display('expandinfo');
    }

    /**修改用户扩展信息
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function edit_expandinfo($profile_group_id)
    {

        $field_setting_list = D('field_setting')->where(array('profile_group_id' => $profile_group_id, 'status' => '1'))->order('sort asc')->select();

        if (!$field_setting_list) {
            $this->error('没有要修改的信息！');
        }

        $data = null;
        foreach ($field_setting_list as $key => $val) {
            $data[$key]['uid'] = is_login();
            $data[$key]['field_id'] = $val['id'];
            switch ($val['form_type']) {
                case 'input':
                    $val['value'] = op_t($_POST['expand_' . $val['id']]);
                    if (!$val['value'] || $val['value'] == '') {
                        if ($val['required'] == 1) {
                            $this->error($val['field_name'] . '内容不能为空！');
                        }
                    } else {
                        $val['submit'] = $this->_checkInput($val);
                        if ($val['submit'] != null && $val['submit']['succ'] == 0) {
                            $this->error($val['submit']['msg']);
                        }
                    }
                    $data[$key]['field_data'] = $val['value'];
                    break;
                case 'radio':
                    $val['value'] = op_t($_POST['expand_' . $val['id']]);
                    $data[$key]['field_data'] = $val['value'];
                    break;
                case 'checkbox':
                    $val['value'] = $_POST['expand_' . $val['id']];
                    if (!is_array($val['value']) && $val['required'] == 1) {
                        $this->error('请至少选择一个：' . $val['field_name']);
                    }
                    $data[$key]['field_data'] = is_array($val['value']) ? implode('|', $val['value']) : '';
                    break;
                case 'select':
                    $val['value'] = op_t($_POST['expand_' . $val['id']]);
                    $data[$key]['field_data'] = $val['value'];
                    break;
                case 'time':
                    $val['value'] = op_t($_POST['expand_' . $val['id']]);
                    $val['value'] = strtotime($val['value']);
                    $data[$key]['field_data'] = $val['value'];
                    break;
                case 'textarea':
                    $val['value'] = op_t($_POST['expand_' . $val['id']]);
                    if (!$val['value'] || $val['value'] == '') {
                        if ($val['required'] == 1) {
                            $this->error($val['field_name'] . '内容不能为空！');
                        }
                    } else {
                        $val['submit'] = $this->_checkInput($val);
                        if ($val['submit'] != null && $val['submit']['succ'] == 0) {
                            $this->error($val['submit']['msg']);
                        }
                    }
                    $val['submit'] = $this->_checkInput($val);
                    if ($val['submit'] != null && $val['submit']['succ'] == 0) {
                        $this->error($val['submit']['msg']);
                    }
                    $data[$key]['field_data'] = $val['value'];
                    break;
            }
        }
        $map['uid'] = is_login();
        $is_success = false;
        foreach ($data as $dl) {
            $map['field_id'] = $dl['field_id'];
            $res = D('field')->where($map)->find();
            if (!$res) {
                if ($dl['field_data'] != '' && $dl['field_data'] != null) {
                    $dl['createTime'] = $dl['changeTime'] = time();
                    if (!D('field')->add($dl)) {
                        $this->error('信息添加时出错！');
                    }
                    $is_success = true;
                }
            } else {
                $dl['changeTime'] = time();
                if (!D('field')->where('id=' . $res['id'])->save($dl)) {
                    $this->error('信息修改时出错！');
                }
                $is_success = true;
            }
            unset($map['field_id']);
        }
        clean_query_user_cache(is_login(), 'expand_info');
        if ($is_success) {
            $this->success('保存成功！');
        } else {
            $this->error('没有要保存的信息！');
        }
    }

    /**input类型验证
     * @param $data
     * @return mixed
     * @author 郑钟良<zzl@ourstu.com>
     */
    function _checkInput($data)
    {
        if ($data['form_type'] == "textarea") {
            $validation = $this->_getValidation($data['validation']);
            if (($validation['min'] != 0 && mb_strlen($data['value'], "utf-8") < $validation['min']) || ($validation['max'] != 0 && mb_strlen($data['value'], "utf-8") > $validation['max'])) {
                if ($validation['max'] == 0) {
                    $validation['max'] = '';
                }
                $info['succ'] = 0;
                $info['msg'] = $data['field_name'] . "长度必须在" . $validation['min'] . "-" . $validation['max'] . "之间";
            }
        } else {
            switch ($data['child_form_type']) {
                case 'string':
                    $validation = $this->_getValidation($data['validation']);
                    if (($validation['min'] != 0 && mb_strlen($data['value'], "utf-8") < $validation['min']) || ($validation['max'] != 0 && mb_strlen($data['value'], "utf-8") > $validation['max'])) {
                        if ($validation['max'] == 0) {
                            $validation['max'] = '';
                        }
                        $info['succ'] = 0;
                        $info['msg'] = $data['field_name'] . "长度必须在" . $validation['min'] . "-" . $validation['max'] . "之间";
                    }
                    break;
                case 'number':
                    if (preg_match("/^\d*$/", $data['value'])) {
                        $validation = $this->_getValidation($data['validation']);
                        if (($validation['min'] != 0 && mb_strlen($data['value'], "utf-8") < $validation['min']) || ($validation['max'] != 0 && mb_strlen($data['value'], "utf-8") > $validation['max'])) {
                            if ($validation['max'] == 0) {
                                $validation['max'] = '';
                            }
                            $info['succ'] = 0;
                            $info['msg'] = $data['field_name'] . "长度必须在" . $validation['min'] . "-" . $validation['max'] . "之间，且为数字";
                        }
                    } else {
                        $info['succ'] = 0;
                        $info['msg'] = $data['field_name'] . "必须是数字";
                    }
                    break;
                case 'email':
                    if (!preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $data['value'])) {
                        $info['succ'] = 0;
                        $info['msg'] = $data['field_name'] . "格式不正确，必需为邮箱格式";
                    }
                    break;
                case 'phone':
                    if (!preg_match("/^\d{11}$/", $data['value'])) {
                        $info['succ'] = 0;
                        $info['msg'] = $data['field_name'] . "格式不正确，必须为手机号码格式";
                    }
                    break;
            }
        }
        return $info;
    }

    /**处理$validation
     * @param $validation
     * @return mixed
     * @author 郑钟良<zzl@ourstu.com>
     */
    function _getValidation($validation)
    {
        $data['min'] = $data['max'] = 0;
        if ($validation != '') {
            $items = explode('&', $validation);
            foreach ($items as $val) {
                $item = explode('=', $val);
                if ($item[0] == 'min' && is_numeric($item[1]) && $item[1] > 0) {
                    $data['min'] = $item[1];
                }
                if ($item[0] == 'max' && is_numeric($item[1]) && $item[1] > 0) {
                    $data['max'] = $item[1];
                }
            }
        }
        return $data;
    }

    /**分组下的字段信息及相应内容
     * @param null $id 扩展分组id
     * @param null $uid
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function _info_list($id = null, $uid = null)
    {
        $info_list = null;

        if (isset($uid) && $uid != is_login()) {
            //查看别人的扩展信息
            $field_setting_list = D('field_setting')->where(array('profile_group_id' => $id, 'status' => '1', 'visiable' => '1'))->order('sort asc')->select();

            if (!$field_setting_list) {
                return null;
            }
            $map['uid'] = $uid;
        } else if (is_login()) {
            $field_setting_list = D('field_setting')->where(array('profile_group_id' => $id, 'status' => '1'))->order('sort asc')->select();

            if (!$field_setting_list) {
                return null;
            }
            $map['uid'] = is_login();

        } else {
            //$this->error('请先登录！');
			$this->redirect('Mobile/User/login');
        }
        foreach ($field_setting_list as $val) {
            $map['field_id'] = $val['id'];
            $field = D('field')->where($map)->find();
            $val['field_content'] = $field;
            $info_list[$val['id']] = $val;
            unset($map['field_id']);
        }

        return $info_list;
    }


    /**扩展信息分组列表获取
     * @return mixed
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function _profile_group_list($uid = null)
    {
        if (isset($uid) && $uid != is_login()) {
            $map['visiable'] = 1;
        }
        $map['status'] = 1;
        $profile_group_list = D('field_group')->where($map)->order('sort asc')->select();

        return $profile_group_list;
    }


    public function changeAvatar()
    {
        $this->defaultTabHash('change-avatar');
        $this->display();
    }
    /*扩展资料里面内容*/
    public function doExpand($is_use=0,$realname='',$telphone='',$sex=0,$qq=0,$email=''){
              
    	if(IS_POST){ 
			   $is_use=op_t($is_use);
			   $realname=op_t(trim($realname));
			   $telphone=op_t($telphone);
			   $sex=op_t($sex);
			   $qq=op_t($qq);
			   $email=op_t($email);
			   /*验证*/
			   if($realname==''){
				 $this->error('请输入真实姓名');
			   }
				$this->checkTelphone($telphone);
				$this->checkSex($sex);
				$this->checkEmail($email);
				$this->check_qq($qq);
				
				$data['realname']=$realname;
				$data['telphone']=$telphone;
				$data['sex']=$sex;
				$data['qq']=$qq;
				$data['email']=$email;
				$data['uid']=is_login();
				$data['siteid']=SITEID;
				//-先查询-
				$pres=D('member_personal')->where("siteid=". SITEID ." and uid =".is_login())->find();
				$das['is_use']=$is_use;
				$user=D('member')->where("siteid=". SITEID ." and uid = ".is_login())->save($das);
				if(!$pres){
					   $cate=D('member_personal')->data($data)->add();
						if($cate>0 && $user>0){
						   $this->success('申请成功,管理员将会24小时之内确定',U('Mobile/Config/index',array('status'=>1)));
						}else{
							$this->error('未提交成功');
						}
                }else{
					   $cate=D('member_personal')->where("siteid =". SITEID ." and uid = ".is_login())->save($data);
					   if($cate>0){
						  $this->success('申请成功,管理员将会24小时之内确定',U('Mobile/Config/index',array('status'=>1)));
					   }else{
						  $this->success('亲!你已经申请过,请耐心等待管理员审核');
					   }
                }
       
 	    }
    }
    //---修改领队会员信息------
    public function doExpandAgree($realname='',$telphone='',$sex=0,$qq=0,$email=''){
           if(IS_POST){
           	  
	           $realname=op_t(trim($realname));
	    	   $telphone=op_t($telphone);
	    	   $qq=op_t($qq);
	    	   $email=op_t($email);
	    	    /*验证*/
	    	  if($realname==''){
	    	   	 $this->error('请输入真实姓名');
	    	   }
	    	    $this->checkTelphone($telphone);
	            $this->checkSex($sex);
	            $this->checkEmail($email);
	            $this->check_qq($qq);
	            $data['realname']=$realname;
	            $data['telphone']=$telphone;
	            $data['sex']=$sex;
	            $data['qq']=$qq;
	            $data['email']=$email;
				$cate=D('member_personal')->where("uid = ".is_login()." and siteid=".SITEID)->save($data);
				if($cate){
				   $this->success('修改成功 ');
				}else{
				   $this->success('未更改数据');
				}
	        }
    }

    public function doUpload($img,$crp)
    {
        $crop =$img.'*'.$crp;
        $result = callApi('User/applyAvatar', array($crop));
        echo json_encode($result);
    }
    public function doCropAvatar($crop)
    {
        //调用上传头像接口改变用户的头像
        $result = callApi('User/applyAvatar', array($crop));
        $this->ensureApiSuccess($result);

        //显示成功消息
        $this->success($result['message'], U('Mobile/Config/index', array('tab' => 'avatar')));
    }

    public function doUploadAvatar()
    {
        //调用上传头像接口
        $result = callApi('User/uploadTempAvatar');

        $this->ensureApiSuccess($result);

        //显示成功消息
        $this->iframeReturn(apiToAjax($result));
    }

    private function iframeReturn($result)
    {
        $json = json_encode($result);
        $json = htmlspecialchars($json);
        $html = "<textarea data-type=\"application/json\">$json</textarea>";
        echo $html;
        exit;
    }
	
    public function myevent(){
		$status_menu=array('0'=>array('id'=>0,'title'=>'全部'),'1'=>array('id'=>1,'title'=>'未支付'),'2'=>array('id'=>2,'title'=>'已支付'),'3'=>array('id'=>3,'title'=>'已取消'),'4'=>array('id'=>4,'title'=>'已出行'));
		
		$status_menu_id = I('status_menuid');
		if(!$status_menu_id){
			$status_menu_id = 0;
		}
		if($status_menu_id == 0){
		}elseif($status_menu_id == 1){
			$map['status']  = array('in','10,20');
		}elseif($status_menu_id == 2){
		$map['status']  = array('in','11,21,12,30');
		}elseif($status_menu_id == 3){
			$map['status']  = 0;
		}elseif($status_menu_id == 4){
			$map['status']  = array('in','12,30');
		}
		$map['uid'] = is_login();
		$event_attend = D('event_attend')->where($map)->order('id desc')->select();
		foreach ($event_attend as $key => $v) {
			$event = D('event')->where(array('id'=>$v['event_id'],'siteid'=>SITEID))->order('id desc')->find();
			$event_calendar_time = D('event_calendar_time')->where(array('id'=>$v['calendar_id'],'siteid'=>SITEID))->order('id desc')->find();
			$event_attend[$key]['title'] = $event['title'];
			$event_attend[$key]['cover_id'] = $event['cover_id'];
			$event_attend[$key]['price'] = $event_calendar_time['price'];
			$event_attend[$key]['start_time'] = $event_calendar_time['starttime'];
			$event_attend[$key]['over_time'] = $event_calendar_time['overtime'];
			$event_attend[$key]['end_time'] = $event_calendar_time['endtime'];
			$event_attend[$key]['end_time'] = $event_calendar_time['endtime'];
			$event_attend[$key]['thumb'] =getThumbImageById($event['cover_id'],400,300);
			$map = "siteid = ".SITEID." and calendar_id = ".$event_calendar_time['id']." and event_id = ".$event['id']." and status = 1";
			$event_attend[$key]['signer'] = D('event_signer')->where($map)->select();
			$event_attend[$key]['travel_number'] = count($event_attend[$key]['signer']);
		}
        $get_url = json_encode($_GET);
        $this->assign('get_url', $get_url);
		$this->assign('status_menu', $status_menu);
		$this->assign('status_menuid', $status_menu_id);
		$this->assign('user', $this->userdata);
		$this->assign('event',$event_attend);
		$this->assign('page',$show);
        $this->display();
    }


    public function get_myevent($page=0){
        $status_menu=array('0'=>array('id'=>0,'title'=>'全部'),'1'=>array('id'=>1,'title'=>'未支付'),'2'=>array('id'=>2,'title'=>'已支付'),'3'=>array('id'=>3,'title'=>'已取消'),'4'=>array('id'=>4,'title'=>'已出行'));

        $status_menu_id = I('status_menuid');
        if(!$status_menu_id){
            $status_menu_id = 0;
        }
        if($status_menu_id == 0){
        }elseif($status_menu_id == 1){
            $map['status']  = array('in','10,20');
        }elseif($status_menu_id == 2){
            $map['status']  = array('in','11,21,12,30');
        }elseif($status_menu_id == 3){
            $map['status']  = 0;
        }elseif($status_menu_id == 4){
            $map['status']  = array('in','12,30');
        }
        $start = $page*10;
        $map['uid'] = is_login();

        $event_attend = D('event_attend')->where($map)->order('id desc')->field('id,event_id,calendar_id,trade_sn,status')->limit($start,10)->select();
        foreach ($event_attend as $key => $v) {
            $event = D('event')->where(array('id'=>$v['event_id'],'siteid'=>SITEID))->order('id desc')->field('id,title,cover_id,cover_id')->find();
            $event_attend[$key]['url']=U('Mobile/Config/myevent_detail',array('trade_sn'=>$v['trade_sn']));
            $event_calendar_time = D('event_calendar_time')->where(array('id'=>$v['calendar_id'],'siteid'=>SITEID))->order('id desc')->find();
            $event_attend[$key]['title'] = $event['title'];
            $event_attend[$key]['cover_id'] = $event['cover_id'];
            $event_attend[$key]['price'] = $event_calendar_time['price'];
            $event_attend[$key]['start_time'] = $event_calendar_time['starttime'];
            $event_attend[$key]['over_time'] = $event_calendar_time['overtime'];
            $event_attend[$key]['end_time'] = $event_calendar_time['endtime'];
            $event_attend[$key]['end_time'] = $event_calendar_time['endtime'];
            $event_attend[$key]['thumb'] =getThumbImageById($event['cover_id'],400,300);
            $map = "siteid = ".SITEID." and calendar_id = ".$event_calendar_time['id']." and event_id = ".$event['id']." and status = 1";
            $event_attend[$key]['signer'] = D('event_signer')->where($map)->select();
            $event_attend[$key]['travel_number'] = count($event_attend[$key]['signer']);
            $event_attend[$key]['text_status']=get_event_order_status($v['status']);
            $content[$key]['event']=$event_attend[$key];
        }


        exit(json_encode($content));

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
	public function collection_event(){
		$uid = is_login();
		$map = "uid = $uid and siteid = ".SITEID." and content_id != ''";
		$my_collection = D('forum_bookmark')->where($map)->field('content_id')->select();
		foreach($my_collection as $val){
			$id_arr[] = $val['content_id'];
		}
		$str_id = implode(',',$id_arr);
		$event_arr = D('event')->where("id in ($str_id)")->field('id,siteid,uid,title,description,create_time,pictures_id,cover_id,view_count,type_id,minpeople,maxpeople,begincity,finalcity,paytype,price,tag,detailadd,price_text,lasted_time,insurance,traveldays')->select();
		
		
 		foreach ($event_arr as &$v) {        
			if($keywords != ''){
				$v['title'] = str_replace($keywords,$str,$v['title']);
			}            
			if($v['lasted_time'] != 0){
				$v['lasted_time'] = date("Y-m-d",$v['lasted_time']);
			}else{
				$v['lasted_time'] ='无';
			}		
			$event_content['tagarr'] = explode(',',$v['tag']);
			foreach ($event_content['tagarr'] as $key => $a) {
				$v['tags'][$a]['id'] = $a;
				$v['tags'][$a]['name'] = get_event_tag($a);
			}
			$v['url'] =U('Mobile/Event/detail',array('id'=>$v['id']));
			$v['thumb'] =getThumbImageById($v['cover_id'],400,300);
			
			$begincity = get_citys($v['begincity']);
			$finalcity = get_citys($v['finalcity']);
			
			$v['begincity_province'] = get_city($begincity['province']);
			$v['begincity_city'] = get_city($begincity['city']);
		
			$v['finalcity_province'] = get_city($finalcity['province']);
			$v['finalcity_city'] = get_city($finalcity['city']);

        }

        $get_url = json_encode($_GET);
        $this->assign('get_url', $get_url);
		
		$this->assign('list',$event_arr);
		$this->display();
	}



    public function get_collection_event($page =0){
        $start = $page*10;
        $uid = is_login();
        $map = "uid = $uid and siteid = ".SITEID." and content_id != ''";
        $my_collection = D('forum_bookmark')->where($map)->field('content_id')->select();
        foreach($my_collection as $val){
            $id_arr[] = $val['content_id'];
        }
        $str_id = implode(',',$id_arr);
        $event_arr = D('event')->where("id in ($str_id)")->field('id,siteid,uid,title,description,create_time,pictures_id,cover_id,view_count,type_id,minpeople,maxpeople,begincity,finalcity,paytype,price,tag,detailadd,price_text,lasted_time,insurance,traveldays')->limit($start,10)->select();


        foreach ($event_arr as &$v) {
            if($keywords != ''){
                $v['title'] = str_replace($keywords,$str,$v['title']);
            }
            if($v['lasted_time'] != 0){
                $v['lasted_time'] = date("Y-m-d",$v['lasted_time']);
            }else{
                $v['lasted_time'] ='无';
            }
            $event_content['tagarr'] = explode(',',$v['tag']);
            foreach ($event_content['tagarr'] as $key => $a) {
                $v['tags'][$a]['id'] = $a;
                $v['tags'][$a]['name'] = get_event_tag($a);
            }
            $v['url'] =U('Mobile/Event/detail',array('id'=>$v['id']));
            $v['thumb'] =getThumbImageById($v['cover_id'],400,300);

            $begincity = get_citys($v['begincity']);
            $finalcity = get_citys($v['finalcity']);

            $v['begincity_province'] = get_city($begincity['province']);
            $v['begincity_city'] = get_city($begincity['city']);

            $v['finalcity_province'] = get_city($finalcity['province']);
            $v['finalcity_city'] = get_city($finalcity['city']);

        }
        exit(json_encode($event_arr));
    }

	public function cancel_collection($id){
		if(!$id){
			exit(json_encode(array('status'=>'0','msg'=>'参数错误！')));
		}
		$did = D('forum_bookmark')->where(array('siteid'=>SITEID,'content_id'=>$id,'uid'=>is_login()))->delete();
		if($did){
			exit(json_encode(array('status'=>'1','msg'=>'取消成功！')));
		}else{
			exit(json_encode(array('status'=>'0','msg'=>'取消失败！')));
		}		
	}
	public function collection_issue(){
		$uid = is_login();
		$map = "uid = $uid and siteid = ".SITEID." and issue_id != ''";
		$my_collection = D('forum_bookmark')->where($map)->field('issue_id')->select();
		foreach($my_collection as $val){
			$id_arr[] = $val['issue_id'];
		}
		$str_id = implode(',',$id_arr);
		$issue_arr = D('issue_content')->field('id,siteid,uid,title,create_time,cover_id,view_count,issue_id,finalcity,tag,recommend_brand,related_event,is_recommend')->where("id in ($str_id)")->order('id desc')->select();
		
		
		 foreach ($issue_arr as &$v) {
            $v['user'] = query_user(array('id', 'nickname', 'mobile_space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['category'] = D('Issue')->field('id,title')->where(array('id'=>$v['issue_id'],'siteid'=>SITEID))->find();
			$v['url'] =U('Mobile/Issue/issuecontentdetail',array('id'=>$v['id']));
			$v['thumb'] =getThumbImageById($v['cover_id'],400,300);
			$finalcity = get_citys($v['finalcity']);
			$v['finalcity_province'] = get_city($finalcity['province']);
			$v['finalcity_city'] = get_city($finalcity['city']);
			
			$v['comment_count'] = D('local_comment')->where(array('app'=>'Issue','row_id'=>$v['id'],'siteid'=>SITEID))->count();
			$v['collect_count'] = D('forum_bookmark')->where(array('issue_id'=>$v['id'],'siteid'=>SITEID))->count();
			
        }

        $get_url = json_encode($_GET);
        $this->assign('get_url', $get_url);
		
		$this->assign('list',$issue_arr);
		
		
		
		
		$this->display();
	}

    public function get_collection_issue($page){
        $start = $page*10;
        $uid = is_login();
        $map = "uid = $uid and siteid = ".SITEID." and issue_id != ''";
        $my_collection = D('forum_bookmark')->where($map)->field('issue_id')->select();
        foreach($my_collection as $val){
            $id_arr[] = $val['issue_id'];
        }
        $str_id = implode(',',$id_arr);
        $issue_arr = D('issue_content')->field('id,siteid,uid,title,create_time,cover_id,view_count,issue_id,finalcity,tag,recommend_brand,related_event,is_recommend')->where("id in ($str_id)")->order('id desc')->limit($start,10)->select();


        foreach ($issue_arr as &$v) {
            $v['create_time'] = date("Y-m-d H:i:s",$v['create_time']);
            $v['user'] = query_user(array('id', 'nickname', 'mobile_space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['category'] = D('Issue')->field('id,title')->where(array('id'=>$v['issue_id'],'siteid'=>SITEID))->find();
            $v['url'] =U('Mobile/Issue/issuecontentdetail',array('id'=>$v['id']));
            $v['thumb'] =getThumbImageById($v['cover_id'],400,300);
            $finalcity = get_citys($v['finalcity']);
            $v['finalcity_province'] = get_city($finalcity['province']);
            $v['finalcity_city'] = get_city($finalcity['city']);

            $v['comment_count'] = D('local_comment')->where(array('app'=>'Issue','row_id'=>$v['id'],'siteid'=>SITEID))->count();
            $v['collect_count'] = D('forum_bookmark')->where(array('issue_id'=>$v['id'],'siteid'=>SITEID))->count();

        }
        exit(json_encode($issue_arr));

    }
	public function collection_del_issue($id){
		if(!$id){
			exit(json_encode(array('status'=>'0','msg'=>'参数错误！')));
		}
		$did = D('forum_bookmark')->where(array('siteid'=>SITEID,'issue_id'=>$id,'uid'=>is_login()))->delete();
		if($did){
			exit(json_encode(array('status'=>'1','msg'=>'取消成功！')));
		}else{
			exit(json_encode(array('status'=>'0','msg'=>'取消失败！')));
		}		
	}
	
	
	//订单详情
	public function myevent_detail($trade_sn){
		$uid = is_login();
		$event_attend = D('event_attend')->where(array('trade_sn'=>$trade_sn,'uid'=>$uid,'siteid'=>SITEID))->find();
		if($event_attend){
			$signer_info = D('event_signer')->where(array('siteid'=>SITEID,'order_id'=>$event_attend['id']))->select();
			$total_num = count($signer_info);
			
			foreach($signer_info as $key => $val){
				$signerinfo[$key]['user_info'] = json_decode($val['user_info'],true); 
				$signerinfo[$key]['id'] = $val['id'];
				$signerinfo[$key]['insurance_info'] = json_decode($val['insurance_info'],true); 
				$member_id_arr[] = $val['member_id'];
			}
			
			
			
			
			$member_left = D('member_contacts')->where(array('uid'=>is_login(),'siteid'=>SITEID))->select();
			foreach($member_left as $key => $val){			
				if(in_array($val['id'],$member_id_arr)){
					unset($member_left[$key]);
				}
			}
			$card_info = D('pointcard')->where(array('cardid'=>$event_attend['cardid'],'siteid'=>SITEID))->find();
			/*$typeinfo = D('pointcard_type')->where(array('siteid'=>SITEID,'ptypeid'=>$card_info['ptypeid']))->find();*/
			$card_info['name'] = $card_info['typename'];
			

			$event = D('event')->where(array('id'=>$event_attend['event_id'],'siteid'=>SITEID))->find();
			$calendar_info = D('event_calendar_time')->where(array('id'=>$event_attend['calendar_id'],'siteid'=>SITEID,'eventid'=>$event_attend['event_id']))->find();
			if($event_attend['cardid']){
				$cardinfo_arr = D('pointcard')->where(array('cardid'=>array('in',$event_attend['cardid']),'siteid'=>SITEID))->select();
				$cardinfo_money = 0;
				foreach($cardinfo_arr as $val){			
					$cardinfo_money += $val['amount'];
				}
			}
			
			$this->assign('cardinfo_money',$cardinfo_money);
			$this->assign('cardinfo_arr',$cardinfo_arr);
			
			
			
			$this->assign('member',$signerinfo);
			$this->assign('total_num',$total_num);
			$this->assign('event_content',$event);
			$this->assign('card_info',$card_info);
			$this->assign('content',$calendar_info);
			$this->assign('event_attend',$event_attend);
			$this->assign('member_left',$member_left);
			$this->display();
		}else{
			$this->error('订单不存在');
		}
	}	
	/*我的故事*/
	public function event_story(){
		$id = is_login();
		if(is_login() == 0){
			//$this->error('您还没有登录！');
			$this->redirect('Mobile/User/login');
		}
		$map = "uid = $id and siteid = ".SITEID." and status >= 0";
		$scount = D('issue_content')->where($map)->count();
		$Page   = new \Think\Page($scount,10);				
		$show   = $Page->show();// 分页显示输出
		$Model = new \Think\Model();			
		$rs = $Model ->table(array('thinkox_issue_content'=>'issue_content','thinkox_issue'=>'issue'))
				->field("issue_content.*,issue.title as it")
				->where("issue_content.issue_id = issue.id and issue_content.uid = $id and issue_content.siteid = ".SITEID." and issue_content.status >= 0")
				->order("issue_content.create_time desc")
				->limit($Page->firstRow.','.$Page->listRows)
				->select();
		if($show != "<div class='pagination'>    </div>"){
			$this->assign('page',$show);
		}
		$this->assign('list',$rs);
		$this->assign('user', $this->userdata);
        $this->display();
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
		$this->assign('user', $this->userdata);
        $this->display();
    }
	
	
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
	/*我的吐槽*/
	public function my_comment(){
	       $status=I('status');
		   switch($status){
		     case 0:
					$com=D('local_comment')->Table(array('thinkox_event'=>'e','thinkox_local_comment'=>'l'))
										   ->where("l.uid=".is_login()." and l.app='Event' and e.id=l.row_id and l.siteid=".SITEID)
										   ->field('e.title,l.row_id,l.id,l.content,l.create_time')
										   ->select(); 
					$this->assign('event_com',$com);
			 break;
			 case 1:
					$com=D('local_comment')->where("uid=".is_login()." and app='Blog' and siteid=".SITEID)
										   ->field('id,content,create_time')
										   ->select(); 
		            
					$this->assign('blog_com',$com);
			 break;
			 case 2:
				   $com=D('local_comment')->Table(array('thinkox_issue_content'=>'i','thinkox_local_comment'=>'l'))
										   ->where("l.uid=".is_login()." and l.app='Issue' and i.id=l.row_id and l.siteid=".SITEID)
										   ->field('i.title,l.row_id,l.id,l.content,l.create_time')
										   ->select(); 
					$this->assign('issue_com',$com);
			 break;
		   }
		   
		   $this->assign('status',$status);
		   $this->assign('user',$this->userdata);
		   $this->display();
	
	}
	/*删除活动评论*/
	public function event_comment_del(){
			  $id=I('id');
			 if(!$id) $this->error('参数错误！');
			  $res=D('local_comment')->where("id=$id")->delete();
			  if($res){
				$this->success('删除成功',U('Usercenter/Config/my_comment',array('status'=>'0')));
			  }else{
				$this->error('删除失败');
			  }
	 }
	 /*删除公告评论*/
	 public function blog_comment_del(){
	         $id=I('id');
			 if(!$id) $this->error('参数错误！');
			  $res=D('local_comment')->where("id=$id")->delete();
			  if($res){
				$this->success('删除成功',U('Usercenter/Config/my_comment',array('status'=>1)));
			  }else{
				$this->error('删除失败');
			  }
	 
	 }
	 /*删除故事评论*/
	 public function issue_comment_del(){
	         $id=I('id');
		      if(!$id) $this->error('参数错误！');
			  $res=D('local_comment')->where("id=$id")->delete();
			  if($res){
				$this->success('删除成功',U('Usercenter/Config/my_comment',array('status'=>2)));
			  }else{
				$this->error('删除失败');
			  }
	 
	 }

	public function mypublic(){
		$uid = is_login();
		$map = "uid= $uid and siteid = ".SITEID." and status >=0";
		$tp = D('event')->where($map)->count();
		$Page = new \Think\Page($tp,10);				
		$show = $Page->show();// 分页显示输出
		$public = D('event')->where($map)->limit($Page->firstRow.','.$Page->listRows)->order("create_time desc")->select();
		if($show != "<div class='pagination'>    </div>"){
			$this->assign('page',$show);
		}
		$this->assign('list',$public);
		$this->assign('user', $this->userdata);
        $this->display();
	}

    public function doChangePassword($old_password, $new_password)
    {
        //调用接口
        $result = callApi('User/changePassword', array($old_password, $new_password));
        $this->ensureApiSuccess($result);

        //显示成功信息
        $this->success($result['message']);
    }
    
    /**
     * @param $sex
     * @return int
     * @auth 陈一枭
     */
    private function checkSex($sex)
    {

        if ($sex < 0 || $sex > 2) {
            $this->error('性别必须属于男、女、保密。');
            return $sex;
        }
        return $sex;
    }

    /**
     * @param $email
     * @param $email
     * @auth 陈一枭
     */
    private function checkEmail($email)
    {
        $pattern = "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
        if (!preg_match($pattern, $email)) {
            $this->error('邮箱格式错误。');
        }

        $map['email'] = $email;
        $map['id'] = array('neq', get_uid());
        $had = D('UcenterMember')->where($map)->count();
        if ($had) {
            $this->error('该邮箱已被人使用。');
        }
    }
	private function check_contacts_email($email){
	    $pattern = "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
        if (!preg_match($pattern, $email)) {
            $this->error('邮箱格式错误。');
        }
	}
    /*帖子收藏*/
	 public function collection($type='forum',$page=1)
    {   
        $uid=$map['uid']=is_login();
        $this->requireLogin();
        $type=op_t($type);
        $totalCount=0;
        $list=$this->_getList($type,$totalCount,$page);
      
        $this->assign('totalCount', $totalCount);
        $this->assign('list', $list);
        //设置Tab
		$this->defaultTabHash('collection');
        $this->assign('type', $type);
        $this->setTitle('我的收藏');
        $this->display($type);
    }
	public function _getList($type='forum',&$totalCount=0,$page=1,$r=15)
    {
        $map['uid']=is_login();
        switch ($type) {
            case 'forum':
                $forums = $this->getForumList();
                $forum_key_value = array();
                foreach ($forums as $f) {
                    $forum_key_value[$f['id']] = $f;
                }
                $post_ids=D('ForumBookmark')->where($map)->field('post_id')->select();
                $post_ids=array_column($post_ids,'post_id');
                $map_forum=array('id'=>array('in',$post_ids),'status'=>1);
                $model=D('ForumPost');
                $list=$model->where($map_forum)->page($page,$r)->order('update_time desc')->select();
                $totalCount=$model->where($map_forum)->count();
                foreach ($list as &$v) {
                    $v['forum'] = $forum_key_value[$v['forum_id']];
                }
                break;
            default:
                $this->error('非法操作！');
                break;
        }
        return $list;
    }
	 private function getForumList()
    {
        $forum_list = S('forum_list');
        if (empty($forum_list)) {
            //读取板块列表
            $forum_list = D('Forum/Forum')->where(array('status' => 1))->order('sort asc')->select();
            S('forum_list', $forum_list, 300);
        }
        return $forum_list;
    }

		
    /**
     * 报名弹出框页面
     * @param $event_id
     * autor:xjw129xjt
     */
    public function sign($event_id,$schedule_id)
    {
		if(!$event_id || !$schedule_id){
			$this->error('参数错误！');
		}else{
			/**********************判断该排期类型**********************/
			$ordertype = return_ordertype($schedule_id);
			/**********************判断该排期类型**********************/
			$event_content = D('Event')->where(array('status' => 1, 'id' => $event_id))->find();
			if (!$event_content) {
				$this->error('活动不存在！');
			}else{
				$content = D('event_calendar_time')->where(array('id' => $schedule_id))->find();
				if(!$content){
					$this->error('排期不存在！');
				}else{
					$member = D('member_contacts')->where(array('uid'=>is_login(),'status'=>1,'siteid'=>SITEID))->select();
					if($member){
						foreach ($member as $k => $v) {
							$member_arr[$v['id']] = $v;
						}
					}
					$member_json = json_encode($member_arr);
					$insurance_info = D('insurance')->where(array('siteid'=>SITEID,'status'=>1,'id'=>$event_content['insurance']))->find();
					if(!empty($insurance_info)){
						$insurance_string = $insurance_info['name'].'('.$insurance_info['sum_insured'].') '.$insurance_info['price'].'元/人';
					}else{
						$insurance_string = '暂无保险';
					}
					$this->assign('insurance_string', $insurance_string);
				}
			}
		}

		$mem_info_creat_time = D('event_attend')->field('MAX(creat_time) creat_time')->where(array('siteid'=>SITEID,'uid'=>is_login()))->find();
		$mem_info_order = D('event_attend')->field('contact_name,contact_telephone,contact_email')->where(array('siteid'=>SITEID,'uid'=>is_login(),'creat_time'=> $mem_info_creat_time['creat_time']))->find();
		if($mem_info_order['contact_name'] == ''){ 
			$mem_info  = D('ucenter_member')->where(array('siteid'=>SITEID,'id'=>is_login()))->find();
		}else{ 

			$mem_info['real_name'] = $mem_info_order['contact_name']; 
			$mem_info['mobile'] = $mem_info_order['contact_telephone']; 
			$mem_info['email'] = $mem_info_order['contact_email']; 
		}
		 $this->contacts_choice();
		$this->assign('mem_info',$mem_info);
		$this->assign('ordertype', $ordertype);
        $this->assign('content', $content);
		$this->assign('event_content', $event_content);
		$this->assign('member',$member);
		$this->assign('member_json',$member_json);
        $this->display();
    }
		
	 public function ajax_get_contacts(){
		$event_membercontacts = trim($_GET['event_membercontacts']);
		$contacts_arr = explode(',',$event_membercontacts);
		$member = D('member_contacts')->where(array('uid'=>is_login(),'status'=>1,'siteid'=>SITEID))->order('id desc')->select();
		if($member){
			foreach ($member as $k => $v) {
				if($contacts_arr){
					if (in_array($v['id'],$contacts_arr)) continue;
				}
				$member_arr[$v['id']] = $v;
			}
		}
		exit(json_encode($member_arr));
	 }
	 
	 
	 public function ajax_check_event_contact($contactid,$event_id,$calendar_id){
		if(!$contactid || !$event_id || !$calendar_id){
			exit(json_encode(array('status'=>0,'msg'=>'参数错误')));
		}
		
		$m_c_data = D('member_contacts')->where("siteid = ".SITEID ." and status=1 and id = ".$contactid)->find();
		/**********************************/
		$check = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $event_id, 'calendar_id' => $calendar_id))->select();
		if($check){
			foreach ($check as $key => $c_a_info) {
				if($c_a_info['status'] == -1 || $c_a_info['status'] == 0)  continue;
				$card_arr = D('event_signer')->where(array('siteid'=>SITEID,'order_id'=>$c_a_info['id'],'status'=>1))->field('card')->select();
				if($card_arr){
					foreach ($card_arr as $k => $c_act) {
						if(!$c_act['card'])  continue;
						$c_c_act[] = $c_act['card'];
					}
				}
				
				if($c_c_act){
					if($m_c_data['card']){
						if (in_array($m_c_data['card'],$c_c_act)){						
							$msg = '参加者：<b>'.$m_c_data['realname'].'</b><br>身份证：<b>'.$m_c_data['card'].'</b><br>已经在订单中了，您可以取消原订单或选择其他人员';
							exit(json_encode(array('status'=>3,'msg'=>$msg)));
						}
					}
				}	
			}
		}
		$calendar_info = D('event_calendar_time')->where("status >=1 and eventid = ".$event_id." and id = ".$calendar_id." and siteid = ".SITEID)->find();
		if(!$calendar_info)  exit(json_encode(array('status'=>0,'msg'=>'没有这个时间段的活动')));
		$event_signer_data = D('event_signer')->where("siteid = ".SITEID." and member_id = ".$contactid." and order_status > 0")->field('calendar_id,user_info')->select();
		if($event_signer_data){
			
			foreach($event_signer_data as $va){
				
				$event_calendar_time_data = D('event_calendar_time')->where("siteid = ".SITEID." and id = ".$va['calendar_id']." and status >= 1 and status <= 5")->field("starttime,overtime,id")->select();
				if($event_calendar_time_data){
					foreach($event_calendar_time_data as $k => $v){	
						if(($v['id'] != $calendar_id && strtotime($calendar_info['starttime']) <= strtotime($v['starttime']) && strtotime($calendar_info['overtime']) >= strtotime($v['starttime']))||($v['id'] != $calendar_id && strtotime($calendar_info['starttime']) >= strtotime($v['starttime']) && strtotime($calendar_info['starttime']) <= strtotime($v['overtime']))){
							//if(!in_array($contactid,$check_arr)){
								$msg ='参加者：<b>' .$m_c_data['realname'].'</b><br>身份证：<b>'.$m_c_data['card'].'</b><br>所报名的其他排期与当前排期有时间冲突，请慎重选择！';	
								exit(json_encode(array('status'=>2,'msg'=>$msg)));
							//}
						}
					}
				}
			}
			
		}
		exit(json_encode(array('status'=>1,'msg'=>'可以报名')));
		/***********************************/	
	 }
	 
	 public function ajax_oreder_info_ios($event_id,$calendar_id,$event_membercontacts,$card_id=''){
		$event_id = $_POST['event_id'];
		$calendar_id = $_POST['calendar_id'];
		$event_membercontacts = $_POST['event_membercontacts'];
		if(!$event_id || !$calendar_id) exit(json_encode(array('status'=>0,'msg'=>'参数错误')));
		$event_content = D('Event')->where(array('status' => 1, 'id' => $event_id,'siteid'=>SITEID))->find();
		if (!$event_content) {
			 exit(json_encode(array('status'=>0,'msg'=>'活动不存在或已经下线')));
		}
		
		$map = "status >=1 and eventid = $event_id and id = $calendar_id and siteid = ".SITEID;
		$calendar_info = D('event_calendar_time')->where($map)->find();
		if(!$calendar_info)  exit(json_encode(array('status'=>0,'msg'=>'没有这个时间段的活动')));
		if($event_membercontacts != ''){
			$user_contacts = explode(',',$event_membercontacts);
			$mycontact_count = count($user_contacts);	
		}else{
			$mycontact_count = 0;
		}
		
		if($mycontact_count == 0){
			exit(json_encode(array('status'=>0,'msg'=>'最少要有一个人参加')));
		}
		$card_amount = 0;
		if($card_id){
			$cardid_arr = explode(',', $card_id);
			foreach ($cardid_arr as $key => $value) {
				$card_info_status = D('Common/Pointcard')->check_card($value);
				if(!$card_info_status['status']){
					exit(json_encode(array('status'=>0,'msg'=>$card_info['msg'])));
				}else{
					$card_info = D('pointcard')->where(array('cardid'=>$value,'siteid'=>SITEID))->find();
					$card_amount += $card_info['amount'];
				}
			}
		}
		if($calendar_info['paytype'] == 0  || $calendar_info['paytype'] == 2 ){
			$totalprice = $calendar_info['price'] * $mycontact_count;
			$deposit = 0 ;
			$balance = $totalprice - $deposit ;
			$payprice =  $calendar_info['price'] * $mycontact_count;
			
		}else{
			$totalprice = $calendar_info['price'] * $mycontact_count ;
			$deposit = $calendar_info['deposit'] * $mycontact_count ;
			$balance = $totalprice - $deposit ;
			$payprice =  $calendar_info['deposit'] * $mycontact_count ;
		}
		 exit(json_encode(array(
				'status'=>1,
				'msg'=>$msg,
				'data'=>array(
					  'totalprice'=>$totalprice,
					  'payprice'=>$payprice,
					  'deposit'=>$deposit,
					  'balance'=>$balance,
					  
					  'calendar_price'=>$calendar_info['price'],
					  'calendar_deposit'=>$calendar_info['deposit'],
					  'paytype'=>$calendar_info['paytype'],
					  'contact_count'=>$mycontact_count,
					  'card_amount'=>$card_amount,
					  'card_id'=>$card_id,
				 )
			)
		));

	 }
    /**
     * 报名参加活动
     * @param $event_id
     * @param $name
     * @param $phone
     * autor:xjw129xjt
     */
    public function doSign_ios($event_id,$calendar_id,$event_membercontacts='',$contact_name='',$contact_telephone='',$contact_email='',$remarks='',$cardid='')
    {	
	
		if (!is_login()) exit(json_encode(array('status'=>-1,'msg'=>'请登录后再报名')));
		
	
		if(!$event_id || !$calendar_id) exit(json_encode(array('status'=>0,'msg'=>'参数错误')));
		if(!$event_membercontacts) exit(json_encode(array('status'=>0,'msg'=>'请选择参加者')));
		
		$event_content = D('Event')->where(array('status' => 1, 'id' => $event_id))->find();
		if (!$event_content) exit(json_encode(array('status'=>0,'msg'=>'活动不存在或已经下线')));
			
		$calendar_info = D('event_calendar_time')->where(array('eventid' => $event_id, 'id' => $calendar_id,'siteid'=>SITEID))->find();
		if(!$calendar_info)  exit(json_encode(array('status'=>0,'msg'=>'没有这个时间段的活动')));
		
		$user_contacts = explode(',',$event_membercontacts);
		$mycontact_count = count($user_contacts);
		if($mycontact_count==0) exit(json_encode(array('status'=>0,'msg'=>'最少要有一个人参加活动')));
		
	
		
		/*************************************************************************/
		if(!$contact_name) exit(json_encode(array('status'=>0,'msg'=>'请填写订单联系人的姓名')));
		if(Gcheck_Mobile($contact_telephone)){
            exit(json_encode(array('status'=>0,'msg'=>'订单联系手机号码有误')));
        }
		if(Gcheck_Email($contact_email)){
            exit(json_encode(array('status'=>0,'msg'=>'订单联系邮箱有误')));
        }
		/**********************************/
		
		
		$check = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $event_id, 'calendar_id' => $calendar_id))->select();
		if($check){
			foreach ($check as $key => $c_a_info) {
				if($c_a_info['status'] == -1 || $c_a_info['status'] == 0)  continue;
				$card_arr = D('event_signer')->where(array('siteid'=>SITEID,'order_id'=>$c_a_info['id'],'status'=>1))->field('card')->select();
				if($card_arr){
					foreach ($card_arr as $k => $c_act) {
						if(!$c_act['card'])  continue;
						$c_c_act[] = $c_act['card'];
					}
				}
				
				if($c_c_act){
					$m_c_data = D('member_contacts')->where("siteid = ".SITEID ." and status=1 and id in ($event_membercontacts)")->select();
					foreach ($m_c_data as $u => $u_info) {	
						if($u_info['card']){
							if (in_array($u_info['card'],$c_c_act)){						
							$str = '参加者：<b>'.$u_info['realname'].'</b><br>身份证：<br><b>'.$u_info['card'].'</b><br>已经在订单中了，您可以取消原订单或选择其他人员';
							exit(json_encode(array('status'=>0,'msg'=>$str)));
							}
						}
					}
				}	
			}
		
		}
		/***********************************/
		
		/********************判断订单类型*********************************************/
		$ordertype = return_ordertype($calendar_id,$mycontact_count);
		/*****************************************************************/	

		/************优惠券***********************/

		if($cardid){

			$cardid_arr = explode(',', $cardid);
			foreach ($cardid_arr as $key => $value) {
				$card_info_status = D('Common/Pointcard')->check_card($value);
				if(!$card_info_status['status']){
					exit(json_encode(array('status'=>0,'msg'=>$card_info_status['msg'].'--'.$value)));
				}else{
					$card_info = D('pointcard')->where(array('cardid'=>$value,'siteid'=>SITEID))->find();
					$card_price += $card_info['amount'];
					$cardidstr[] = $value;
				}
			}
			$savedata['cardid'] = implode(',', $cardidstr);
		}
		/****************************************/
		$user_info = D('ucenter_member')->where(array('siteid'=>SITEID,'id'=>is_login()))->find();
		if($user_info['real_name'] == ''){
			$list['real_name'] = $contact_name;
			D('ucenter_member')->where(array('siteid'=>SITEID,'id'=>is_login()))->save($list);
		}	
		/****************************************/
		/*************************************************************************/
		$event_insurance = $_POST['insurance'];
		
		if($event_insurance){
			foreach($event_insurance as $uk=> $rs){
				if(!$rs) continue;
				$insurance_arr[$uk] = $rs;
				$event_signer_data[$uk]['insurance_id'] = $rs;
				$insurance_arr_info =  get_insurance($rs);
				if($insurance_arr_info){
					$insurance_data_temp['name'] =$insurance_arr_info['name'];
					$insurance_data_temp['sum_insured'] =$insurance_arr_info['sum_insured'];
					$insurance_data_temp['price'] =$insurance_arr_info['price'];
					$insurance_data_temp['policy_number'] ='';
					$event_signer_data[$uk]['insurance_info'] = json_encode($insurance_data_temp);
				}
			}
		}	
		$totalprice = $calendar_info['price'] * $mycontact_count;
		$card_price = isset($card_price) ? $card_price : 0;
		if($calendar_info['paytype'] == 0 ){
			$diff_price = $totalprice - $card_price;
			$payprice =  $calendar_info['price'] * $mycontact_count;
			if(!empty($calendar_info['price'])){
				if($diff_price < 0){
					$savedata['totalprice'] = $totalprice;
					$savedata['payprice'] = 0;
					$savedata['leftprice'] = 0;
					$card_data['status'] = 3;
				}else{
					$savedata['totalprice'] = $totalprice - $card_price;
					$savedata['payprice'] = $payprice - $card_price;
					$savedata['leftprice'] = 0;
					$card_data['status'] = 2;
				}
			}else{
				$savedata['totalprice'] = 0;
				$savedata['payprice'] = 0;
				$savedata['leftprice'] = 0;
			}
		}else{
			$payprice =  $calendar_info['deposit'] * $mycontact_count;
			$leftpay_temp = $totalprice - $payprice;
			if($card_price > $leftpay_temp){
				$savedata['totalprice'] = $payprice;				
			}else{
				$savedata['totalprice'] = $totalprice - $card_price;
			}
			$card_data['status'] = 2;
			$savedata['payprice'] = $payprice;
			$savedata['leftprice'] = $savedata['totalprice'] - $savedata['payprice'];
		}
		
		$event_membercontacts = implode(',',$user_contacts);
		/**查询选择的所有活动参加者**/
		$m_c_data = D('member_contacts')->where("siteid=".SITEID. " and status=1 and id in ($event_membercontacts)")->select();
		$savedata['trade_sn'] = create_sn();
		$savedata['uid'] = is_login();
		$savedata['event_id'] = $event_id;
		$savedata['calendar_id'] = $calendar_id;
		$savedata['creat_time'] = time();
		$savedata['overdue_time'] = time()+1800;//过期时间设置	
		$savedata['ordertype'] = $ordertype;
		$savedata['siteid'] = $event_content['siteid'];				
		$savedata['price'] = $calendar_info['price'];				
		$savedata['paytype'] = $calendar_info['paytype'];	
		$savedata['contact_name'] = $contact_name;
		$savedata['contact_telephone'] = $contact_telephone;
		$savedata['contact_email'] = $contact_email;
		$savedata['remarks'] = $remarks;
		$savedata['deposit'] = $calendar_info['deposit'];
		
		if($ordertype ==1){
			switch($calendar_info['paytype']){
				case 0;
					if(!empty($calendar_info['price'])){
						if($diff_price < 0){
							$savedata['status'] = 30;
							$savedata['pay_status'] = 2;
						}else{
							$savedata['status'] = 20;
							$savedata['pay_status'] = 0;
						}
					}else{
						$savedata['status'] = 30;
						$savedata['pay_status'] = 2;
					}
				break;
				case 1;
					$savedata['status'] = 10;
					$savedata['pay_status'] = 0;	
				break;
				case 2;
					$savedata['status'] = 30;
					$savedata['pay_status'] = 2;	
				break;
			}
		}else{
			$savedata['status'] = 1;
			$savedata['pay_status'] = 0;				
		}
		
		
		$res = D('event_attend')->add($savedata);
		if ($res) {
			/*********订单提交成功更新优惠券状态*********/
			if($savedata['cardid'] != ''){	
				$cardid_arr_up = explode(',', $savedata['cardid']);
				foreach ($cardid_arr_up as $key => $value) { 
					$card_data['userid'] = is_login();
					D('pointcard')->where(array('cardid'=>$value,'siteid'=>SITEID))->save($card_data);
					$card_user = D('pointcard_user')->where(array('cardid'=>$value,'siteid'=>SITEID))->find();
					if(!$card_user){
						$result['siteid'] = SITEID;
						$result['cardid'] = $value;
						$result['userid'] = is_login();
						$result['bindtime'] = time();
						$result['usetime'] = time();			
						D('pointcard_user')->add($result);
					}else{
						$resu['usetime'] = time();
						D('pointcard_user')->where(array('cardid'=>$value,'siteid'=>SITEID))->save($resu);					
					}
					
					/**********写入日志表*************************/
					add_card_log($value,$card_data['status'],'提交活动订单-[使用]','[代金券/活动卡][使用/取消]');
					/********************************************/

				}
			}	
			foreach($m_c_data as $key => $val){
				$event_signer_data[$val['id']]['siteid'] = SITEID;
				$event_signer_data[$val['id']]['order_id'] = $res;
				$event_signer_data[$val['id']]['card'] = $val['card'];
				$event_signer_data[$val['id']]['user_info'] = json_encode($val);
				$event_signer_data[$val['id']]['order_status'] = $savedata['status'];
				$event_signer_data[$val['id']]['status'] = 1;
				$event_signer_data[$val['id']]['member_id'] = $val['id'];
				$event_signer_data[$val['id']]['event_id'] = $event_id;
				$event_signer_data[$val['id']]['calendar_id'] = $calendar_id;
				$event_signer_data[$val['id']]['insurance_id'] = !empty($event_content['insurance'])? $event_content['insurance'] : null ;
				$insurance_arr_info = get_insurance($event_content['insurance']);
				$event_signer_data[$val['id']]['insurance_info'] = !empty($insurance_arr_info) ? json_encode($insurance_arr_info) : null ;
				D('event_signer')->add($event_signer_data[$val['id']]);
			}
				
			D('Message')->sendMessageWithoutCheckSelf($event_content['uid'],query_user('nickname',is_login()).'报名参加了活动]'.$event_content['title'].']，请速去审核！' ,'报名通知', U('Manage/Order/event_detail',array('trade_sn'=>$savedata['trade_sn'])),is_login());
			D('event_calendar_time')->where(array('eventid' => $event_id, 'id' => $calendar_id))->setInc('regnumber',$mycontact_count);
			if($ordertype ==1){
					/*************************发送邮件***********************************************/
				$event_info = D('event_attend')->where(array('siteid'=>SITEID,'id'=>$res))->find();
				$uid = $event_info['uid'];
				$user_info = query_user(array('nickname',$uid));
				$user_name = $user_info['nickname'];
				$webinfo = json_decode(WEBSITEINFO,true);
				$web_url = "http://".$_SERVER['HTTP_HOST'];
				$title = "[".$webinfo['webname']."]-下单成功";
				$webinfo = json_decode(WEBSITEINFO,true);
				/*if($webinfo['slogan'] != ''){
					$message = "【".$user_name."】,您的活动订单下单成功通知：
				
				【订单号：".$event_info['trade_sn']."】
				【活动标题：".$event_content['title']."】-【出发日期：".$calendar_info['starttime']."】-【报名人数：".$mycontact_count."人】
				 感谢您报名参加".$webinfo['webname']."本次活动，欢迎进入【".$webinfo['webname']."】官网进行交流与咨询。
				
				【".$webinfo['webname']."】【".$webinfo['slogan']."】
				".$web_url;
				}else{
					$message = "【".$user_name."】,您的活动订单下单成功通知：
				
				【订单号：".$event_info['trade_sn']."】
				【活动标题：".$event_content['title']."】-【出发日期：".$calendar_info['starttime']."】-【报名人数：".$mycontact_count."人】
				 感谢您报名参加".$webinfo['webname']."本次活动，欢迎进入【".$webinfo['webname']."】官网进行交流与咨询。
				
				【".$webinfo['webname']."】
				".$web_url;
				}
				
				sendMail($contact_email,$title,$message);*/
				$orderdata= array(
						'user_name'			=>		$user_name,
						'trade_sn' 			=>		$event_info['trade_sn'],
						'event_title'		=>		$event_content['title'],
						'calendar_starttime'=>		$calendar_info['starttime'],
						'total_member'		=>		$mycontact_count,
						'webname'			=>		$webinfo['webname'],
						'web_slogan'		=>		$webinfo['slogan'],
						'web_url'			=>		$web_url,
						'noticetype'   		=>  	'order_message',
						'title'				=>  	$title,
						
					);
				$contactway=array($contact_email);                
				D('Message')->addSendMessage('send_email_to_user',$contactway,$orderdata,0,1);
				
				/***********************************************************************************/
				/*
				$eventdata=array(
					'event_order_sn'  => $savedata['trade_sn'],
					'execute_time'   => $savedata['creat_time']+1800,

					);
				D('Message')->addSendMessage('event_order_countdown_update','',$eventdata,0,1);
				*/
				/***********************************************************************************/
				if($diff_price < 0 || $calendar_info['price'] == 0){

					exit(json_encode(array('status'=>1,'msg'=>'已下单，即将跳到我的订单详情','url'=>U('Mobile/Config/myevent_detail',array('trade_sn'=>$savedata['trade_sn'])))));
				}else{

					exit(json_encode(array('status'=>1,'msg'=>'已下单，等待支付','url'=>U('Mobile/Pay/pay',array('trade_sn'=>$savedata['trade_sn'])))));
				}
			}else{
				$map['id'] = $res;
				$data_update_att['overdue_time'] = 0;
				D('event_attend')->where($map)->save($data_update_att);
				exit(json_encode(array('status'=>1,'msg'=>'已预约，等待确认','url'=>U('Mobile/Config/myevent',array('trade_sn'=>$savedata['trade_sn'])))));
			}
		} else {
			exit(json_encode(array('status'=>0,'msg'=>'报名失败')));
		}
	}
	public function Order_message($user_name='',$orderinfo='',$event_info='',$calendar_info='',$mycontact_count='',$event_content='',$webinfo='',$web_url=''){
				if($webinfo['slogan']!=''){ 
					$webinfo['slogan']="【".$webinfo['slogan']."】";
				}
				$message = "【".$user_name."】,您的活动订单下单成功通知：
				
				【订单号：".$event_info['trade_sn']."】
				【活动标题：".$event_content['title']."】-【出发日期：".$calendar_info['starttime']."】-【报名人数：".$mycontact_count."人】
				 感谢您报名参加".$webinfo['webname']."本次活动，欢迎进入【".$webinfo['webname']."】官网进行交流与咨询。
				
				【".$webinfo['webname']."】".$webinfo['slogan'].$web_url;
				
			
			return $message;
	}
	 
	 public function ajax_oreder_info(){
		$msg['status'] = 1 ;
		$msg['msg'] = "";
		$check = $_POST['check'];
		$check_arr = explode(',',$check);
		$event_id = $_POST['event_id'];
		$calendar_id = $_POST['calendar_id'];
		$event_membercontacts = $_POST['event_membercontacts'];
		$event_insurance = $_POST['event_insurance'];
		
		if(!$event_id || !$calendar_id) exit(json_encode(array('status'=>0,'msg'=>'参数错误')));
		 
		 
		$event_content = D('Event')->where(array('status' => 1, 'id' => $event_id,'siteid'=>SITEID))->find();
		if (!$event_content) {
			 exit(json_encode(array('status'=>0,'msg'=>'活动不存在或已经下线')));
		}
		
		$map = "status >=1 and eventid = $event_id and id = $calendar_id and siteid = ".SITEID;
		$calendar_info = D('event_calendar_time')->where($map)->find();
		if(!$calendar_info)  exit(json_encode(array('status'=>0,'msg'=>'没有这个时间段的活动')));
		if($event_membercontacts != ''){
			$user_contacts = explode(',',$event_membercontacts);
			$mycontact_count = count($user_contacts);	
		}else{
			$mycontact_count = 0;
		}
		/************************************************************************************************************************************/
			foreach($user_contacts as $val){
				$map = "siteid = ".SITEID." and member_id = ".$val." and order_status >0";
				$data[$val]['calendar_arr'] = D('event_signer')->where($map)->field('calendar_id,user_info')->select();
				$data[$val]['user_info'] = json_decode($data[$val]['calendar_arr'][0]['user_info'],true);
				foreach($data[$val]['calendar_arr'] as $va){
					$map = "siteid = ".SITEID." and id = ".$va['calendar_id']." and status >= 1 and status <= 5";
					$data[$val]['schedule_arr'] = D('event_calendar_time')->where($map)->field("starttime,overtime,id")->select();
					foreach($data[$val]['schedule_arr'] as $k => $v){
						if($v['id'] != $calendar_id && strtotime($calendar_info['starttime']) <= strtotime($v['starttime']) && strtotime($calendar_info['overtime']) >= strtotime($v['starttime'])){
							if(!in_array($val,$check_arr)){
								$contacts_id = $val;
								$double = 1;							
								$msg ='<b>温馨提示</b>：<br>参加者：<b>' .$data[$val]['user_info']['realname'].'</b><br>身份证：<b>'.$true.$data[$val]['user_info']['card'].'</b><br>所报名的其他排期与当前排期有时间冲突，请慎重选择！';	
							}
						}elseif($v['id'] != $calendar_id && strtotime($calendar_info['starttime']) >= strtotime($v['starttime']) && strtotime($calendar_info['starttime']) <= strtotime($v['overtime'])){
							if(!in_array($val,$check_arr)){
								$contacts_id = $val;
								$double = 1;							
								$msg ='<b>温馨提示</b>：<br>参加者：<b>' .$data[$val]['user_info']['realname'].'</b><br>身份证：<b>'.$true.$data[$val]['user_info']['card'].'</b><br>所报名的其他排期与当前排期有时间冲突，请慎重选择！';
							}
						}
					}
				}	
			}
		/************************************************************************************************************************************/
		
		if($calendar_info['paytype'] == 0 ){
			$totalprice = $calendar_info['price'] * $mycontact_count;
			$payprice =  $calendar_info['price'] * $mycontact_count;
		}else{
			$totalprice = $calendar_info['price'] * $mycontact_count;
			$payprice =  $calendar_info['deposit'] * $mycontact_count;
		}
		
		 exit(json_encode(array(
				'status'=>1,
				'msg'=>$msg,
				'contacts_id'=>$contacts_id,
				'double'=>$double,
				'data'=>array(
							  'totalprice'=>$totalprice,
							  'payprice'=>$payprice,
							  'calendar_price'=>$calendar_info['price'],
							  'calendar_deposit'=>$calendar_info['deposit'],
							  'paytype'=>$calendar_info['paytype'],
							  'contact_count'=>$mycontact_count,
				 )
			)
		));
       
     }
    /**
     * 报名参加活动
     * @param $event_id
     * @param $name
     * @param $phone
     * autor:xjw129xjt
     */
    public function doSign()
    {		
		$event_id = $_POST['event_id'];
		$calendar_id = $_POST['calendar_id'];
		$event_membercontacts = $_POST['event_membercontacts'];
		/**********************************/
		$check = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $event_id, 'calendar_id' => $calendar_id))->select();
		if($check){
			foreach ($check as $key => $c_a_info) {
				if($c_a_info['status'] == -1 || $c_a_info['status'] == 0)  continue;
				$card_arr = D('event_signer')->where(array('siteid'=>SITEID,'order_id'=>$c_a_info['id'],'status'=>1))->field('card')->select();
				if($card_arr){
					foreach ($card_arr as $k => $c_act) {
						if(!$c_act['card'])  continue;
						$c_c_act[] = $c_act['card'];
					}
				}
				
				if($c_c_act){
					$m_c_data = D('member_contacts')->where("siteid = ".SITEID ." and status=1 and id in ($event_membercontacts)")->select();
					foreach ($m_c_data as $u => $u_info) {	
						if($u_info['card']){
							if (in_array($u_info['card'],$c_c_act)){						
							$str = '参加者：<b>'.$u_info['realname'].'</b><br>身份证：<br><b>'.$u_info['card'].'</b><br>已经在订单中了，您可以取消原订单或选择其他人员';
							$this->error($str);
							}
						}
					}
				}	
			}
		
		}
		/***********************************/
		if (!is_login()) $this->error('请登录后再报名。');
		if(!$event_id || !$calendar_id) $this->error('参数错误');
		if(!$event_membercontacts) $this->error('请选择参加者');
		
		$event_content = D('Event')->where(array('status' => 1, 'id' => $event_id))->find();
		if (!$event_content) $this->error('活动不存在或已经下线！'); 
			
		$calendar_info = D('event_calendar_time')->where(array('eventid' => $event_id, 'id' => $calendar_id,'siteid'=>SITEID))->find();
		if(!$calendar_info)  $this->error('没有这个时间段的活动！');
		
		$user_contacts = explode(',',$event_membercontacts);
		$mycontact_count = count($user_contacts);
		if($mycontact_count==0) $this->error('最少要有一个人参加活动！');
		/********************判断订单类型*********************************************/
		$ordertype = return_ordertype($calendar_id,$mycontact_count);
		/*****************************************************************/	
		$contact_name = $_POST['contact_name'];
		$contact_telephone = $_POST['contact_telephone'];
		$contact_email = $_POST['contact_email'];
		$remarks = $_POST['remarks'];
		
		/*************************************************************************/
		if(!$contact_name) $this->error('请填写订单联系人的姓名！');
		$this->checkTelphone($contact_telephone);
		if(!$contact_email){
			$this->error('请填写订单联系人的邮箱！');
		}else{
			$pattern = "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
			if (!preg_match($pattern, $contact_email)) {
				$this->error('邮箱格式错误，请重新输入！');
			}
		}
		/************优惠券***********************/
		$check_card_use = $_POST['check_card_use'];
		$cardid = $_POST['cardid'];
		$cardid1 = $_POST['cardid1'];		
		switch($check_card_use){
			case 2;
				if($cardid == ''){
					 $this->error('亲，既然选择了使用优惠券，就不能为空哦！');
				}else{	
					$card_info =D('Common/Pointcard')->check_card($cardid);
					if(!$card_info['status']){
						$this->error($card_info['msg']);
					}else{
						$savedata['cardid'] = $cardid;
						$card_info = D('pointcard')->where(array('cardid'=>$savedata['cardid'],'siteid'=>SITEID))->find();
						$card_price = $card_info['amount'];
						$card_name =$card_info['typename'];	
					}											
				}
			break;
			case 3;
				if($cardid1 == ''){
					 $this->error('亲，既然选择了使用优惠券，就不能为空哦！');
				}else{					
					$card_info =D('Common/Pointcard')->check_card($cardid1);
					if(!$card_info['status']){
						$this->error($card_info['msg']);
					}else{
						$savedata['cardid'] = $cardid1;
						$card_info = D('pointcard')->where(array('cardid'=>$savedata['cardid'],'siteid'=>SITEID))->find();
						$card_price = $card_info['amount'];
						$card_name = $card_info['typename'];
					}			
				}
			break;
		
		}		
		/****************************************/
		$user_info = D('ucenter_member')->where(array('siteid'=>SITEID,'id'=>is_login()))->find();
		if($user_info['real_name'] == ''){
			$list['real_name'] = $contact_name;
			D('ucenter_member')->where(array('siteid'=>SITEID,'id'=>is_login()))->save($list);
		}	
		/*************************************************************************/
		$event_insurance = $_POST['insurance'];
		
		if($event_insurance){
			foreach($event_insurance as $uk=> $rs){
				if(!$rs) continue;
				$insurance_arr[$uk] = $rs;
				$event_signer_data[$uk]['insurance_id'] = $rs;
				$insurance_arr_info =  get_insurance($rs);
				if($insurance_arr_info){
					$insurance_data_temp['name'] =$insurance_arr_info['name'];
					$insurance_data_temp['sum_insured'] =$insurance_arr_info['sum_insured'];
					$insurance_data_temp['price'] =$insurance_arr_info['price'];
					$insurance_data_temp['policy_number'] ='';
					$event_signer_data[$uk]['insurance_info'] = json_encode($insurance_data_temp);
				}
			}
		}	
		$totalprice = $calendar_info['price'] * $mycontact_count;
		$card_price = isset($card_price) ? $card_price : 0;
		if($calendar_info['paytype'] == 0 ){
			$diff_price = $totalprice - $card_price;
			$payprice =  $calendar_info['price'] * $mycontact_count;
			if(!empty($calendar_info['price'])){
				if($diff_price < 0){
					$savedata['totalprice'] = $totalprice;
					$savedata['payprice'] = 0;
					$savedata['leftprice'] = 0;
					$card_data['status'] = 3;
				}else{
					$savedata['totalprice'] = $totalprice - $card_price;
					$savedata['payprice'] = $payprice - $card_price;
					$savedata['leftprice'] = 0;
					$card_data['status'] = 2;
				}
			}else{
				$savedata['totalprice'] = 0;
				$savedata['payprice'] = 0;
				$savedata['leftprice'] = 0;
			}
		}else{
			$payprice =  $calendar_info['deposit'] * $mycontact_count;
			$leftpay_temp = $totalprice - $payprice;
			if($card_price > $leftpay_temp){
				$savedata['totalprice'] = $payprice;				
			}else{
				$savedata['totalprice'] = $totalprice - $card_price;
			}
			$card_data['status'] = 2;
			$savedata['payprice'] = $payprice;
			$savedata['leftprice'] = $savedata['totalprice'] - $savedata['payprice'];
		}
		
		$event_membercontacts = implode(',',$user_contacts);
		/**查询选择的所有活动参加者**/
		$m_c_data = D('member_contacts')->where("siteid=".SITEID. " and status=1 and id in ($event_membercontacts)")->select();
		//exit;
		$savedata['trade_sn'] = create_sn();
		$savedata['uid'] = is_login();
		$savedata['event_id'] = $event_id;
		$savedata['calendar_id'] = $calendar_id;
		$savedata['creat_time'] = time();
		$savedata['ordertype'] = $ordertype;
		$savedata['siteid'] = $event_content['siteid'];				
		$savedata['price'] = $calendar_info['price'];				
		$savedata['paytype'] = $calendar_info['paytype'];	
		$savedata['contact_name'] = $contact_name;
		$savedata['contact_telephone'] = $contact_telephone;
		$savedata['contact_email'] = $contact_email;
		$savedata['remarks'] = $remarks;
		$savedata['deposit'] = $calendar_info['deposit'];
		
		if($ordertype ==1){
			switch($calendar_info['paytype']){
				case 0;
					if(!empty($calendar_info['price'])){
						if($diff_price < 0){
							$savedata['status'] = 30;
							$savedata['pay_status'] = 2;
						}else{
							$savedata['status'] = 20;
							$savedata['pay_status'] = 0;
						}
					}else{
						$savedata['status'] = 30;
						$savedata['pay_status'] = 2;
					}
				break;
				case 1;
					$savedata['status'] = 10;
					$savedata['pay_status'] = 0;	
				break;
				case 2;
					$savedata['status'] = 30;
					$savedata['pay_status'] = 2;	
				break;
			}
		}else{
			$savedata['status'] = 1;
			$savedata['pay_status'] = 0;				
		}
		
		
		$res = D('event_attend')->add($savedata);
		if ($res) {
			/*********订单提交成功更新优惠券状态*********/
			if($savedata['cardid'] != ''){			
				$card_data['userid'] = is_login();
				D('pointcard')->where(array('cardid'=>$savedata['cardid'],'siteid'=>SITEID))->save($card_data);
				$card_user = D('pointcard_user')->where(array('cardid'=>$savedata['cardid'],'siteid'=>SITEID))->find();
				if(!$card_user){
					$result['siteid'] = SITEID;
					$result['cardid'] = $savedata['cardid'];
					$result['userid'] = is_login();
					$result['bindtime'] = time();
					$result['usetime'] = time();			
					D('pointcard_user')->add($result);
				}else{
					$resu['usetime'] = time();
					D('pointcard_user')->where(array('cardid'=>$savedata['cardid'],'siteid'=>SITEID))->save($resu);					
				}
				
				/**********写入日志表*************************/
				add_card_log($savedata['cardid'],$card_data['status'],'提交活动订单-[使用]','[代金券/活动卡][使用/取消]');
				/********************************************/
			}	
			foreach($m_c_data as $key => $val){
				$event_signer_data[$val['id']]['siteid'] = SITEID;
				$event_signer_data[$val['id']]['order_id'] = $res;
				$event_signer_data[$val['id']]['card'] = $val['card'];
				$event_signer_data[$val['id']]['user_info'] = json_encode($val);
				$event_signer_data[$val['id']]['order_status'] = $savedata['status'];
				$event_signer_data[$val['id']]['status'] = 1;
				$event_signer_data[$val['id']]['member_id'] = $val['id'];
				$event_signer_data[$val['id']]['event_id'] = $event_id;
				$event_signer_data[$val['id']]['calendar_id'] = $calendar_id;
				$event_signer_data[$val['id']]['insurance_id'] = !empty($event_content['insurance'])? $event_content['insurance'] : null ;
				$insurance_arr_info = get_insurance($event_content['insurance']);
				$event_signer_data[$val['id']]['insurance_info'] = !empty($insurance_arr_info) ? json_encode($insurance_arr_info) : null ;
				D('event_signer')->add($event_signer_data[$val['id']]);
			}
				
			D('Message')->sendMessageWithoutCheckSelf($event_content['uid'],query_user('nickname',is_login()).'报名参加了活动]'.$event_content['title'].']，请速去审核！' ,'报名通知', U('Manage/Order/event_detail',array('trade_sn'=>$savedata['trade_sn'],'eventid'=>$event_id)),is_login());

			D('Common/Dynamic')->sendMessage(is_login(),'Event',$event_content['title'],$event_id,U('Event/Index/detail') );

			D('event_calendar_time')->where(array('eventid' => $event_id, 'id' => $calendar_id))->setInc('regnumber',$mycontact_count);
			if($ordertype ==1){
					/*************************发送邮件***********************************************/
				$event_info = D('event_attend')->where(array('siteid'=>SITEID,'id'=>$res))->find();
				$uid = $event_info['uid'];
				$user_info = query_user(array('nickname',$uid));
				$user_name = $user_info['nickname'];
				$webinfo = json_decode(WEBSITEINFO,true);
				$web_url = "http://".$_SERVER['HTTP_HOST'];
				$title = "[".$webinfo['webname']."]-下单成功";
				$webinfo = json_decode(WEBSITEINFO,true);
				/*if($webinfo['slogan'] != ''){
					$message = "【".$user_name."】,您的活动订单下单成功通知：
				
				【订单号：".$event_info['trade_sn']."】
				【活动标题：".$event_content['title']."】-【出发日期：".$calendar_info['starttime']."】-【报名人数：".$mycontact_count."人】
				 感谢您报名参加".$webinfo['webname']."本次活动，欢迎进入【".$webinfo['webname']."】官网进行交流与咨询。
				
				【".$webinfo['webname']."】【".$webinfo['slogan']."】
				".$web_url;
				}else{
					$message = "【".$user_name."】,您的活动订单下单成功通知：
				
				【订单号：".$event_info['trade_sn']."】
				【活动标题：".$event_content['title']."】-【出发日期：".$calendar_info['starttime']."】-【报名人数：".$mycontact_count."人】
				 感谢您报名参加".$webinfo['webname']."本次活动，欢迎进入【".$webinfo['webname']."】官网进行交流与咨询。
				
				【".$webinfo['webname']."】
				".$web_url;
				}
				
				sendMail($contact_email,$title,$message);*/
				$orderdata= array(
						'user_name'			=>	$user_name,
						'trade_sn' 			=>	$event_info['trade_sn'],
						'event_title'		=>	$event_content['title'],
						'calendar_starttime'=>	$calendar_info['starttime'],
						'total_member'		=>	$mycontact_count,
						'webname'			=>	$webinfo['webname'],
						'web_slogan'		=>	$webinfo['slogan'],
						'web_url'			=>	$web_url,
						'noticetype'   		=>  'order_message',
						'title'				=>  $title,
						
					);
				$contactway=array($contact_email);                
				D('Message')->addSendMessage('send_email_to_user',$contactway,$orderdata,0,1);
				
				/***********************************************************************************/
				if($diff_price < 0 || $calendar_info['price'] == 0){
					$this->success('已下单，即将跳到我的订单详情。', U('Mobile/Config/myevent_detail',array('trade_sn'=>$savedata['trade_sn'])));
				}else{
					$this->success('已下单，等待支付。', U('Mobile/Pay/pay',array('trade_sn'=>$savedata['trade_sn'])));
				}
			}else{
				$this->success('已预约，等待确认。', U('Mobile/Config/myevent',array('trade_sn'=>$savedata['trade_sn'])));
			}
		} else {
			$this->error('报名失败。', '');
		}
	}
	public function ajax_card_select($card_membercontacts='',$server_condition=0,$shop_id='',$card_type=1){
		$cardid = trim($_GET['cardid']);
		$cardid_arr = explode(',',$cardid);
		$map['userid']=is_login();
		$map['siteid']=SITEID;
		$map['server_condition']=array('elt',$server_condition);
		if($card_type==1){ 
			$map['card_type']=1;
		}
		$card_arr = D('pointcard')->where($map)->select();
		$card_final = array();
		foreach($card_arr as $key => &$val){
			
			if($cardid_arr){
				if (in_array($val['cardid'],$cardid_arr)) continue;
			}
			$cardlist=D('Common/Pointcard')->check_card($val['cardid']);
			if(!$cardlist['status']){
				continue;
			}else{
				$card_final[] = $val;
			}
		}
		foreach($card_final as $key => &$val){
			if($val['endtime'] != 0){
				$endtime[$key] = date('Y-m-d,H:i:s',$val['endtime']);
				$val['useinfo'] = "该【".$val['typename']."】有效期至【".$endtime[$key].'】';
			}else{
				$val['useinfo'] = "该【".$val['typename']."】长期有效";
			}
		}
		
		exit(json_encode(array('res'=>$card_final)));
	
	

   }
   /*
	 * ajax验证优惠券卡号
	 */
	public function ajax_check_card($card){
		$ajax_check_card	=	D('Common/Pointcard')->ajax_check_card($card);
		exit(json_encode(array('status'=>$ajax_check_card['status'],'amount'=>$ajax_check_card['amount'],'name'=>$ajax_check_card['name'],'endtime'=>$ajax_check_card['endtime'],'msg'=>$ajax_check_card['msg'],'cardid'=>$card)));
		
	}
	
	//账号设置页面的展示
	 public function setup($uid = null, $tab = '', $nickname = '',$qq = 0, $sex = 0, $email = '',$real_name = '', $signature = '', $community = 0, $district = 0, $city = 0, $province = 0,$mobile = 0,$self_introduction = '',$constellation=0)
    {
         
   
			$address = get_citys($this->userdata['address']);
			$content_address['community'] = $address['community'];
			$content_address['district'] = $address['district'];
			$content_address['city'] = $address['city'];
			$content_address['province'] = $address['province'];
			$this->assign('content_address', $content_address);
			$this->assign('user', $this->userdata);
			$this->display();

    }
	
	//账号设置页面的展示
	 public function dosetup($uid = null, $tab = '', $nickname = '',$qq = '', $sex = 0, $email = '',$real_name = '', $signature = '', $community = 0, $district = 0, $city = 0, $province = 0,$mobile = '',$self_introduction = '',$constellation=0)
    {
    	if (IS_POST) {
			
            $nickname = op_t(trim($nickname));
            $real_name=op_t(trim($real_name));
            $signature = op_t(trim($signature));
            $sex = intval($sex);
            $province = intval(trim($province));
            $city = intval(trim($city));
            $community = intval(trim($community));
            $district = intval(trim($district));
            $self_introduction=op_t(trim($self_introduction));
            $qq=op_t(trim($qq));
            //$microBo=op_t(trim($microBo));//微博号
            
			
			if($email){
				$this->checkEmail($email);
				$ucuser['email']=$mobile;
			}
			if($mobile){
				$mobile_find  = D('ucenter_member')->where(array('siteid'=>SITEID,'id'=>array('neq',is_login()),'mobile'=>$mobile))->find();
				if($mobile_find){
					$this->error('此号码已被使用!');
				}
				$this->checkTelphone($mobile);
				$ucuser['mobile']=$mobile;
			}
			
            /*---验证--*/
            $this->checkNickname($nickname);
            $this->checkSex($sex);
            
			if($sex == 0 ){
				$this->error('请选择性别!');
			}
            $this->checkSignature($signature);
			
			if($qq !=''){
				$this->check_qq($qq);
				$user['qq']=$qq;
			}
			if($self_introduction !=''){
				$this->check_self_introduction($self_introduction);
				$ucuser['self_introduction']=$self_introduction;
			}			
			$cityparam['province'] = $_POST['address_province'];
			$cityparam['city'] = $_POST['address_city'];
			$cityparam['district'] = $_POST['address_district'];
			$cityparam['community'] = $_POST['address_community'];

			$user['address'] = set_city($cityparam);


            $user['real_name']=$real_name;
            $user['pos_province'] = $province;
            $user['pos_city'] = $city;
            $user['pos_district'] = $district;
            $user['pos_community'] = $community;
            

            $user['nickname'] = $nickname;
            $user['sex'] = intval($sex);
            $user['signature'] = $signature;
            $user['uid'] = get_uid();
            $user['constellation']=$constellation;
            
			$rs_member=D('Home/Member')->save($user);
			
			
            $ucuser['id'] = get_uid();
            
            $ucuser['real_name']=$real_name;
			
			
            $rs_ucmember = D('UcenterMember')->save($ucuser);
            
            clean_query_user_cache(get_uid(), array('nickname', 'sex', 'signature','real_name', 'email','qq','pos_province', 'pos_city', 'pos_district', 'self_introduction','pos_community','mobile','address','constellation'));

            if ($rs_member || $rs_ucmember) {
                $this->success('修改成功！');

            } else {
                $this->error('未修改数据。');
            }

        } 
    }
	
    //
    public function doAddset($schedule_id = '',$event_id = '',$ordertype = '',$realname, $sex, $card, $telephone, $qq, $email,$bloodtype,$allergies,$emergencycontact,$emergencyphone,$role,$role_description)
    {
        if (!is_login()) $this->error('请登录后再添加。');
		
		$this->check_realname($realname);
		$this->checkSex($sex);
		$this->check_card($card);
		$this->check_telephone($telephone);
		if($qq !=''){
		   $this->check_qq($qq);
		   }
        $this->check_contacts_email($email);/*自已添加邮箱可以一样*/
		if($emergencycontact==''){
			   $this->error('请填写紧急联系人');
			}
	    
		if($telephone==$emergencyphone){
			   $this->error('紧急联系人与联系电话不能一致');
			}
		$this->check_emergencyphone($emergencyphone);
		
        $check = D('member_contacts')->where(array('uid' => is_login(), 'card' =>$card))->select();
	    if (!$check) {
            $data['uid'] = is_login();
            $data['realname'] = $realname;
            $data['sex'] = $sex;
			$data['card'] = strtoupper($card);//转为大写
			$data['telephone'] = $telephone;
			$data['qq'] = $qq;
			$data['email'] = $email;
            $data['creat_time'] = time();
			if(!$_POST['accpre'][0]){
				$this->error('请选择住宿偏好！');
			}else{
				$data['accpre'] = implode(",",$_POST['accpre']);
			}
			
			$data['allergies'] = $allergies;
			$data['bloodtype'] = $bloodtype;
			$data['emergencycontact'] = $emergencycontact;
			$data['emergencyphone'] = $emergencyphone;
			$data['role'] = $role;
			$data['role_description'] = $role_description;
			$data['siteid'] = SITEID;
			$data['status'] = 1;
            $res = D('member_contacts')->add($data);
            if ($res) {
				 $this->success('添加修改。',U('Mobile/Config/contacts'));

            } else {
                $this->error('添加失败。', '');
            }
        } else {
            $this->error('您已经添加过这个常用联系人了。', '');
        }
    }
	public function get_seats_left($event_membercontacts='',$event_id='',$calendar_id=''){
		$user_contacts = explode(',',$event_membercontacts);
		$mem_count = count($user_contacts);
		$map = "status >=1 and eventid = $event_id and id = $calendar_id and siteid = ".SITEID;
		$calendar_info = D('event_calendar_time')->where($map)->find();
		$staus = $calendar_info['status'];
		switch($staus){
			case 1;
				$maxpeople = $calendar_info['maxpeople'];
				$regnumber = $calendar_info['regnumber'];
				if(!empty($maxpeople)){ 
					if($maxpeople >= $regnumber){
						if(($maxpeople - $regnumber - $mem_count) >= 0){
							$seats_left = 'continue';
						}else{
							$seats_left = -($maxpeople - $regnumber - $mem_count);
						}
					}else{
						$seats_left = 'continue';
					}
				}else{
					$seats_left = 'continue';
				}
			break;
			case 2;
				$seats_left = 'continue';
			break;
			case 3;
				$seats_left = 'continue';
			break;
		}
		exit(json_encode(array('seats_left'=>$seats_left)));
	}
	









}
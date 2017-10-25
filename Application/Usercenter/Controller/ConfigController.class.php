<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM1:13
 */
 
namespace Usercenter\Controller;
set_time_limit(0);
use Think\Controller;

class ConfigController extends BaseController
{
	protected $userdata;
	protected $mTalkModel;
    public function _initialize()
    {
        parent::_initialize();
        if (!is_login()) {
           // $this->error('请登录后再访问本页面。');
			 $this->redirect('Home/User/login');
        }
		$this->userdata = query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $uid);
        $this->mTalkModel = D('Talk');
        $this->setTitle('个人中心');
	}

    public function index($uid = null, $tab = '', $nickname = '',$qq = '', $sex = 0, $email = '',$real_name = '', $signature = ''
        , $community = 0, $district = 0, $city = 0, $province = 0,$mobile ='',$old_mobile ='',$self_introduction = '',$constellation=0)
    {
         
    	if (IS_POST) {
			
            $nickname = op_t(trim($nickname));
            $real_name=op_t(trim($real_name));
            $signature = op_t(trim($signature));
            $sex = intval($sex);
            $email = op_t(trim($email));
            $province = intval(trim($province));
            $city = intval(trim($city));
            $community = intval(trim($community));
            $district = intval(trim($district));
            $mobile=op_t(trim($mobile));
            $self_introduction=op_t(trim($self_introduction));
            $qq=op_t(trim($qq));
            //$microBo=op_t(trim($microBo));//微博号 
            /*---验证--*/
            $this->checkNickname($nickname);
            $this->checkSex($sex);
			
			if($sex == 0 ){
				$this->error('请选择性别!');
			}
            //$this->checkEmail($email);
            $this->checkSignature($signature);
   
            $this->checkTelphone($mobile);
			if($mobile != $old_mobile){
				$mobile_find  = D('ucenter_member')->where(array('siteid'=>SITEID,'id'=>array('neq',is_login()),'mobile'=>$mobile))->find();
				if($mobile_find){
					$this->error('此号码已被使用!');
				}
			}
            $this->check_self_introduction($self_introduction);
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
            //$ucuser['email'] = $email;
            $ucuser['mobile']=$mobile;
            $ucuser['real_name']=$real_name;
            $rs_ucmember = D('UcenterMember')->save($ucuser);
            clean_query_user_cache(get_uid(), array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'));


            //TODO tox 清空缓存
            if ($rs_member || $rs_ucmember) {
            	clean_people_info_cash();
                $this->success('设置成功。',U('Usercenter/Config/index'));

            } else {
                $this->error('未修改数据。');
            }

        } else {

			$address = get_citys($this->userdata['address']);
			$content_address['community'] = $address['community'];
			$content_address['district'] = $address['district'];
			$content_address['city'] = $address['city'];
			$content_address['province'] = $address['province'];
		    
			
            //调用API获取基本信息
            //TODO tox 获取省市区数据
            //显示页面
			$status=I('status');
			switch($status){
			    case 0:
				    $address = get_citys($this->userdata['address']);
					$content_address['community'] = $address['community'];
					$content_address['district'] = $address['district'];
					$content_address['city'] = $address['city'];
					$content_address['province'] = $address['province'];
				    $this->assign('content_address', $content_address);
				break;
				case 1:
				    $this->check_checked(is_login());
					$list	=	D('member_personal')->where(" siteid = ". SITEID ." and uid =".is_login())->find();//--扩展资料里查看个人资料--
					$data	=	D('member_upgrad_group')->where("uid=".is_login()." and siteid=".SITEID)->order("id desc")->find();
					$member	=	D('member')->where(" siteid = ". SITEID ." and uid=".is_login())->find();
					if($data){
						$this->assign('master',$data);
					}
					
				    $this->assign('member',$member);
					$this->assign('list',$list);
			    break;
				case 2:
				break;
				case 3:
				break;
				case 4:
					  $tab = op_t($tab);
					  $this->assign('tab', $tab);
					  //$this->assign('contacts_arr',$this->contacts(is_login()));
					  $this->contacts(is_login());
				break;
				case 5:
				      $this->assign('content_address', $content_address);
				      $this->address(SITEID,is_login());
				break;
				case 6:
				   $data	=	D('member_upgrad_group')->where("uid=".is_login()." and siteid=".SITEID)->select(); 
				    $this->assign('member_record',$data);
				break;
			}
	                      
				$this->assign('status',$status);
				$this->assign('user', $this->userdata);
				$this->getExpandInfo();
				$this->display();
        }

    }
  /*添加活动联系人表*/
   public function contacts($uid = null,$tab = '')
    {   
		//调用API获取基本信息
		//TODO tox 获取省市区数据
		//显示页面
		$this->assign('user', $this->userdata);
		//$map['uid'] = is_login(); 
		$contacts_arr = D('member_contacts')->where( "uid=".$uid." and siteid = ". SITEID)->select();
		$tab = op_t($tab);
		$this->assign('tab', $tab);
		$this->assign('contacts_arr', $contacts_arr);
		$this->getExpandInfo();
	 }
    public function contacts_add($uid = null, $tab = '')
    {    
	    $participant = getWebsitConfig('participant');
	    $participant = explode(',', $participant); 
	    foreach ( $participant as $key => &$val){
	    	$dataListdis[$val] = "<span class='common-color-red'>*</span>";
	    }
	    $this->assign('dataListdis',$dataListdis);
        $this->display('contacts_add');

    }

	public function doAdd($nickname = '',$schedule_id = '',$event_id = '',$ordertype = '',$realname, $sex, $card, $telephone, $qq, $email,$bloodtype,$allergies,$emergencycontact,$emergencyphone,$role,$role_description,$hand = '' ,$age = '')
    {
        if (!is_login())  $this->redirect('Home/User/login');
		$this->check_realname($realname);
		$this->checkSex($sex);
		$this->check_mem_card($card);
		$this->check_telephone($telephone);

		$participant_t = getWebsitConfig('participant');
	    $participant = explode(',', $participant_t); 
	    $participant_name =  get_participant('',true);
	    $accpre =  $_POST['accpre'][0];

	    if($participant_t != ''){ 
	    	foreach ( $participant as $key => &$val){
		    	$dataListdis[$val] =  $participant_name[$val];
		    	if($$participant_name[$val] == '' ){
		    		$this->error('请填写'.get_participant($val) );
		    	}
		    }

	    }
	  	 
	    if($age != ''){ 
	    	if(!is_numeric($age) ){ 
	  			$this->error('年龄请输入数字！');
	  		}
	  		if( ($age < 0) ||  ($age > 130)){ 
	  			$this->error('请输入合理年龄！');
	  		}

	    }

  		
	  		
		if($qq !=''){
			$this->check_qq($qq);
		}
		if($nickname != ''){
			$nickname = trim(op_t($nickname));			
		}
		if($email != ''){ 
			$this->check_contacts_email($email);/*自已添加邮箱可以一样*/
		}
		if($emergencycontact !=''){
			if($emergencycontact == $realname){ 
				$this->error('紧急联系人不能为自己');
			}
		}
	    if($emergencyphone != ''){ 
	    	if($telephone==$emergencyphone){
			  $this->error('紧急联系人与联系电话不能一致');
			}
			$this->check_emergencyphone($emergencyphone);
	    }
        $check = D('member_contacts')->where(array('uid' => is_login(), 'card' =>$card))->select();
	    if (!$check) {
            $data['uid'] = is_login();
			$data['nickname'] = $nickname;
            $data['realname'] = $realname;
            $data['sex'] = $sex;
			$data['card'] = str_replace(" ","",strtoupper($card));//转为大写
			$data['telephone'] = $telephone;
			$data['qq'] = $qq;
			$data['email'] = $email;
            $data['creat_time'] = time();
			if($_POST['accpre'][0] != ''){
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
			$data['age'] = $age;
        	$data['hand'] = $hand;
            $res = D('member_contacts')->add($data);
            if ($res) {
				$this->success('添加成功。', 'refresh');

            } else {
                $this->error('添加失败。', '');
            }
        } else {
            $this->error('您已经添加过这个常用联系人了。', '');
        }
    }

	public function check_mem_card($card){
		if(!$card) $this->error('请输入身份证或护照号！');
		if(preg_match("/[\x7f-\xff]/", $card)) $this->error('不允许有中文字符！');
	}
    //执行修改过之后
    public function contacts_edit($uid=null,$tab=''){
        $id=$_GET['id'];
        $data=D('member_contacts')->where("id=$id")->find();
        $participant = getWebsitConfig('participant');
	    $participant = explode(',', $participant); 
	    foreach ( $participant as $key => &$val){
	    	$dataListdis[$val] = "<span class='common-color-red'>*</span>";
	    }

	    $this->assign('dataListdis',$dataListdis);
        $this->assign('data',$data);
        $this->display();
    }
	//----edit----
    public function doEdit($nickname = '',$id,$realname, $sex, $card, $telephone, $qq, $email,$bloodtype,$allergies,$emergencycontact,$emergencyphone,$role,$role_description,$hand = '' ,$age)
    {
        $id=op_t($id);
        $this->check_realname($realname);
		$this->check_mem_card($card);
        $this->check_telephone($telephone);

        $participant_t = getWebsitConfig('participant');
	    $participant = explode(',', $participant_t); 
	    $participant_name =  get_participant('',true);
	    $accpre =  $_POST['accpre'][0];

	    if($participant_t != ''){ 
  			foreach ( $participant as $key => &$val){
		    	$dataListdis[$val] =  $participant_name[$val];
		    	if($$participant_name[$val] == '' ){
		    		$this->error('请填写'.get_participant($val) );
		    	}
		    }
	    }

	    if($age != ''){ 
		  	if(!is_numeric($age) ){ 
	  			$this->error('年龄请输入数字！');
	  		}
	  		if( ($age < 0) ||  ($age > 130)){ 
	  			$this->error('请输入合理年龄！');
	  		}
	    }

		if($qq !=''){
            $this->check_qq($qq);
		}
		if($nickname != ''){
			$nickname = trim(op_t($nickname));			
		}
		if($email != ''){ 
			$this->check_contacts_email($email);
		}
        $this->checkSex($sex);
    	if($emergencycontact !=''){
			if($emergencycontact == $realname){ 
				$this->error('紧急联系人不能为自己');
			}
		}
	    if($emergencyphone != ''){ 
	    	if($telephone==$emergencyphone){
			  $this->error('紧急联系人与联系电话不能一致');
			}
			$this->check_emergencyphone($emergencyphone);
	    }
		if($_POST['accpre'][0] != ''){
			$data['accpre'] = implode(",",$_POST['accpre']);
		}

		$data['allergies'] = $allergies;
		$data['bloodtype'] = $bloodtype;
		$data['nickname'] = $nickname;
		$data['emergencycontact'] = $emergencycontact;
		$data['emergencyphone'] = $emergencyphone;
		$data['role'] = $role;
		$data['role_description'] = $role_description;
        $data['realname'] = $realname;
        $data['sex'] = $sex;
        $data['card'] = str_replace(" ","",strtoupper($card));//转为大写
        $data['telephone'] = $telephone;
        $data['qq'] = $qq;
        $data['email'] = $email;
        $data['age'] = $age;
        $data['hand'] = $hand;

        $res = D('member_contacts')->where("id='$id'")->save($data);
        if ($res) {
            $this->success('成功修改。',U('Usercenter/Config/index',array('status'=>4)));
        } else {
            $this->error('未做任何信息修改！', '');
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
	/*常用收货地址*/

  public function address($siteid,$uid){
	  if(!is_login()){
	    $this->error('请先登录');
	  }
	  $uid=is_login();
	  $list=D('shop_address')->where("siteid=".$siteid." and uid=".$uid)->select();
	  foreach($list as $key=>$value){
		 if($list[$key]['address']!=0){
                $list[$key]['district']=D('district')->where("id=".$list[$key]['address'])->getField('upid');
                $list[$key]['city']=D('district')->where("id=".$list[$key]['district'])->getField('upid');
                $list[$key]['province']=D('district')->where("id=".$list[$key]['city'])->getField('upid');
            }
	  }
	  $this->assign('list',$list);
	  $this->assign('user', $this->userdata);

	}


  /**添加地址**/
	public function address_doadd($community = 0, $district = 0, $city = 0, $province = 0,$name='',$detailed='',$zipcode='',$phone='',$email=''){
  
		
			$name = op_t(trim($name));
			$phone=op_t(trim($phone));
			if($name==''){
			  $this->error('请填写姓名');
			}
			if($detailed==''){
			  $this->error('请填写详细地址');
			}
			if($zipcode!=''){
			$this->checkCode($zipcode);
			}
			$this->checkEmail($email);
			$this->checkTelphone($phone);
			$cityparam['province'] = $_POST['address_province'];
			$cityparam['city'] = $_POST['address_city'];
			$cityparam['district'] = $_POST['address_district'];
			$cityparam['community'] = $_POST['address_community'];
			$user['name']=$name;

			$user['address'] = set_city($cityparam);
			$user['detailed']=$detailed;
			$user['zipcode']=$zipcode;
			$user['phone']=$phone;
			$user['email']=$email;
			$user['uid']=is_login();
			$user['siteid']=SITEID;
			$user['create_time']=time();
			$uder['change_time']=time();
			$user['isdefault']=0;

			$list= D('shop_address')->create($user);
			  $res=D('shop_address')->add();
			// echo D('shop_address')->getLastSql();die;
				  if($res){
					   $this->success('添加成功',U('Config/index',array('status'=>5)));
				  }else{
					   $this->error('添加失败');
				  }
			
	}


	/*修改收件地址*/
	public function address_edit( $community = 0, $district = 0, $city = 0, $province = 0,$name='',$detailed='',$zipcode='',$phone='',$email=''){
	                             
		if(IS_POST){
		    $id=I('id');
		 		  $name = op_t(trim($name));
			$phone=op_t(trim($phone));
		    if($name==''){
			  $this->error('请填写姓名');
			}
        if($detailed==''){
			  $this->error('请填写详细地址');
			}
		
        if($zipcode!=''){
        $this->checkCode($zipcode);
      }
	    $this->checkEmail($email);
        $this->checkTelphone($phone);
			$cityparam['province'] = $_POST['address_province'];
			$cityparam['city'] = $_POST['address_city'];
			$cityparam['district'] = $_POST['address_district'];
			$cityparam['community'] = $_POST['address_community'];
      
      $user['id']=$id;
      $user['name']=$name;
			$user['address'] = set_city($cityparam);
      $user['detailed']=$detailed;
      $user['zipcode']=$zipcode;
			$user['phone']=$phone;
      $user['change_time']=time();
      $user['email']=$email;
			
			$cate=D('shop_address')->save($user);
			if($cate){
			   $this->success('更改成功',U('Config/index',array('status'=>5)));
			}else{
			   $this->success('未更改数据');
			}
		}else{
			$id=$_GET['id'];
			$res=D('shop_address')->where("id=$id")->find();
			$address = get_citys($res['address']);
			$content_address['community'] = $address['community'];
			$content_address['district'] = $address['district'];
			$content_address['city'] = $address['city'];
			$content_address['province'] = $address['province'];
			
			$this->assign('content_address',$content_address);
			$this->assign('data',$res);
			$this->assign('id',$id);
			$this->display();
		}
	}  
  
  /* 设定为默认收件地址*/
    public function address_default(){
	    $id=I('id');
	    $uid=is_login();
        $user['isdefault']=0;
			
		$cate=D('shop_address')->where("isdefault=1 and uid=".$uid)->save($user);
		$user1['uid']=$uid;
	
		$user1['id']=$id;
		$user1['isdefault']=1;
			
			$cate1=D('shop_address')->where("id=".$id." and uid=".$uid)->save($user1);
			if($cate1){
			   $this->success('设置成功',U('Config/index',array('status'=>5)));
			}else{
			   $this->success('设置失败');
			}
	}  
  
  
  
	/*删除收件地址*/
	public function address_delete(){
	    $id=I('id');
		  $res=D('shop_address')->where("id=$id")->delete();
		  if($res){
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
        $match = preg_match('/^(?!_|\s\')[A-Za-z0-9*_\x80-\xff\s\']+$/', $nickname);
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
     
     /*验证邮编*/
     public function checkCode($code){
     
		if(!preg_match("/^[0-9][0-9]{5}$/",$code)){

            $this->error('请输入正确的邮政编码');
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
                             ->field('m.uid,m.nickname,m.qq,m.score,m.signature,m.pos_province,m.pos_city,m.pos_district,m.is_use,m.pos_community,m.self_introduction,m.sex,m.mobile,m.real_name,u.email,u.username')
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
            $this->error('请先登录！');
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
    /*认证领队--2014-11-26--edit*/
    public function doExpand($is_use=0,$realname='',$mobile='',$sex=0,$qq=0,$email=''){
              
    	if(IS_POST){ 
			   $is_use		=	op_t($is_use);
			   $realname	=	op_t(trim($realname));
			   $mobile		=	op_t(trim($mobile));
			   $sex			=	op_t($sex);
			   $qq			=	op_t(trim($qq));
			   $email		=	op_t(trim($email));

				if($realname=='') $this->error('请输入真实姓名');
			 
				$this->checkTelphone($mobile);
				$this->checkSex($sex);
				$this->checkEmail($email);
				$this->check_qq($qq);
				$member	=	D('member')->where("uid=".is_login()." and siteid=".SITEID)->find();
				if($is_use==3) $this->error('暂时不能申请管理员!');
				if($is_use == $member['is_use']){
				   $this->error("您已是".get_upgrading($is_use));
				}   
				//-先查询-
				$groupfind	= D('member_upgrad_group')->where(array('is_use'=> $is_use,'status'=>0,'uid'=>is_login()))->find();
				    if(!$groupfind){
						  $das	=	array(
								'uid'			=>	is_login(),
								'is_use'		=>	$is_use,
								'siteid'		=> 	SITEID,
								'create_time'	=>	time()
								);
							$member_grad =  D('member_upgrad_group')->data($das)->add();
							
							$data['realname']	=	$realname;
							$data['mobile']		=	$mobile;
							$data['sex']		=	$sex;
							$data['qq']			=	$qq;
							$data['email']		=	$email;
							$data['uid']		=	is_login();
							$data['siteid']		=	SITEID;
							
							$personal=D('member_personal')->where("siteid=". SITEID ." and uid =".is_login())->find();
							
							if(!$personal){
								$cate	=	D('member_personal')->data($data)->add();
							}else{
								$cate	=	D('member_personal')->where("siteid =". SITEID ." and uid = ".is_login())->save($data);
							}
							
							if($member_grad){
								$this->success('申请成功,等待管理员审核','refresh');
							}else{
								$this->error('申请失败');
							}
					
					}else{
					
					    $this->error("您已申请过".get_upgrading($is_use)."请耐心等待管理员审核");
					
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
    public function doCropAvatar($crop)
    {
        //调用上传头像接口改变用户的头像
        $result = callApi('User/applyAvatar', array($crop));
        $this->ensureApiSuccess($result);

        //显示成功消息
        $this->success($result['message'], U('Usercenter/Config/index', array('tab' => 'avatar')));
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
		$tab =  $_GET['tab'];
		$tab = $tab != '' ? $tab : 0;
		switch($tab){
			case 0;
				if(I('status')!=''){
				   $map['status']=I('status');
				}
				$trade_sn = op_t(I('trade_sn'));
				//---订单号---
				if($trade_sn!=''){
				  $map['trade_sn']=$trade_sn;
				}
				$data = D('Event')->getConfigmyEvent($map);

				$show = $data['show'];
				$event_attend = $data['event_attend'];
			break;
			case 1;
				if(I('status')!=''){
					   $map['pay_status']=I('status');
					}
					$order_sn = op_t(I('trade_sn'));
					
					//---订单号---
					if($order_sn!=''){
					  $map['order_sn']=$order_sn;
					}
					$map['uid'] 	=	 is_login();
					$map['siteid']	=	SITEID;
				
				$count=D('shop_ordersn')->where($map)->count();//总数
				$Page  = new \Think\Page($count,10);// 
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				$show  = $Page->show();// 
				$shop_order_arr = D('shop_ordersn')->where($map)->order("create_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();
				$this->assign('shop_arr',$shop_order_arr);
			break;
		}
		$this->assign('user', $this->userdata);
		$this->assign('event',$event_attend);
		$this->assign('tab',$tab);
		$this->assign('page',$show);
        $this->display();
    }



  


	public function do_update_shop_status($id,$status){
		if(!$id) $this->error('参数错误！');
		$status = trim($status);
		$msg = update_shop_order_status($id,$status);
		if($msg['s'] == 1){
			exit(json_encode(array('status'=>$msg['s'],'m'=>$msg['m'])));
		}else{
			exit(json_encode(array('status'=>$msg['s'],'m'=>$msg['m'])));
		}
	}
	

	/**********************************************以下活动操作****************************************************************/
   
  
	/********加载修改订单紧急联系人页面***********/
	public function emergency_edit($id){
		$emergency = D('event_attend')->where(array('id'=>$id,'siteid'=>SITEID))->find();
		$this->assign('emergency',$emergency);
		$this->display();
	}
	/*******修改订单紧急联系人***********/
	public function do_emergency_edit($id=0,$contact_email = '',$contact_name = '',$contact_telephone = ''){
		if(!$id){
			$this->error('参数错误！');
		}else{
			$attend = D('event_attend')->where(array('id'=>$id,'siteid'=>SITEID))->find();
		}
		if(!$contact_email || !$contact_name || !$contact_telephone){
			$this->error('请完善紧急联系人信息！');
		}
		$this->check_telephone($contact_telephone);
		$this->check_contacts_email($contact_email);		
		$data['contact_email'] = $contact_email;
		$data['contact_name'] = $contact_name;
		$data['contact_telephone'] = $contact_telephone;
		$rs = D('event_attend')->where(array('id'=>$id,'siteid'=>SITEID))->save($data);
		if($rs){
			$this->success('修改成功',U('Usercenter/Eventorder/myevent_detail_edit',array('trade_sn'=>$attend['trade_sn'])));
		}else{
			$this->error('修改失败！');
		}
	}
  
	/*添加报名人*/
	public function detail_member_add($event_id = 0,$calendar_id = 0,$event_membercontacts = '',$order_id = 0){
		if($event_id == '' || $calendar_id == '' || $order_id == ''){
			return (json_encode(array('status'=>0,'msg'=>'参数错误,无法添加！')));
		}
		if($event_membercontacts == ''){
			return (json_encode(array('status'=>0,'msg'=>'请选择参加者！')));
		}
		
		$attend = D('event_attend')->where(array('siteid'=>SITEID,'id'=>$order_id))->find();		
		if($attend['status'] == -1 || $attend['status'] == 0){
			return (json_encode(array('status'=>0,'msg'=>'该订单已被取消或删除，无法添加参加者！')));
		}
		$membercontacts_arr = explode(',',$event_membercontacts);
		$memtotal = count($membercontacts_arr);
				
		/***查询单条订单活动参加者的保险信息**/
		$signer_info = D('event_signer')->where(array('siteid'=>SITEID,'calendar_id'=>$calendar_id,'order_id'=>$order_id))->limit(1)->select();
		/**查询选择的所有活动参加者**/
		$m_c_data = D('member_contacts')->where("siteid=".SITEID. " and status=1 and id in ($event_membercontacts)")->select();
		
		foreach($m_c_data as $key => $val){
			$event_signer_data[$val['id']]['siteid'] = SITEID;
			$event_signer_data[$val['id']]['order_id'] = $order_id;
			$event_signer_data[$val['id']]['card'] = $val['card'];
			$event_signer_data[$val['id']]['user_info'] = json_encode($val);
			$event_signer_data[$val['id']]['status'] = 1;
			$event_signer_data[$val['id']]['member_id'] = $val['id'];
			$event_signer_data[$val['id']]['event_id'] = $event_id;
			$event_signer_data[$val['id']]['calendar_id'] = $calendar_id;
			$event_signer_data[$val['id']]['insurance_id'] = $signer_info[0]['insurance_id'];
			$event_signer_data[$val['id']]['insurance_info'] = $signer_info[0]['insurance_info'];
			$red[] = D('event_signer')->add($event_signer_data[$val['id']]);
		}		
		if(count($red) == $memtotal && !empty($red)){
			/*********************更改订单的订单类型*****************************/
			$data_order['ordertype'] = return_ordertype($calendar_id,$memtotal);
			return_ordertype($calendar_id,$memtotal) == 2 ? $data_order['status'] = 1 : '' ; 
			D('event_attend')->where(array('siteid'=>SITEID,'id'=>$order_id))->save($data_order);
			/*********************************************************************/
			update_order_info($order_id,$calendar_id);
			D('event_calendar_time')->where(array('eventid' => $event_id, 'id' => $calendar_id))->setInc('regnumber',$memtotal);
			$data['status'] = 2;
			$data['msg'] = '添加成功!';
			return $data;
		}else{
			$map = "siteid = ".SITEID." and order_id = $order_id and calendar_id = $calendar_id and event_id = $event_id and member_id in ($event_membercontacts)";
			D('event_signer')->where($map)->delete();
			$data['status'] = 1;
			$data['msg'] = '添加失败!';
			return $data;
		}
	}

	/*删除报名人*/
	public function do_detail_member_del($id){
		if(!$id){
			return (json_encode(array('status'=>0,'msg'=>'参数错误！')));
		}
		$signer_info = D('event_signer')->where(array('id'=>$id,'siteid'=>SITEID))->find();
		$total_count = D('event_signer')->where(array('siteid'=>SITEID,'order_id'=>$signer_info['order_id']))->count();
		if($total_count == 1){
			return(json_encode(array('status'=>0,'msg'=>'订单中至少需要一个参加者！')));
		}
		
		$rs = D('event_signer')->where(array('id'=>$id,'siteid'=>SITEID))->delete();
		if($rs){			
			D('event_calendar_time')->where(array('eventid' => $signer_info['event_id'], 'id' =>$signer_info['calendar_id']))->setInc('regnumber',-1);
			update_order_info($signer_info['order_id'],$signer_info['calendar_id']);
			return(json_encode(array('status'=>1,'msg'=>'删除成功！')));
		}else{
			return(json_encode(array('status'=>0,'msg'=>'删除失败！')));
		}
		
	}
	public function detail_member_edit($id){
		$member_info = D('event_signer')->where(array('id'=>$id,'siteid'=>SITEID))->find();
		$order = D('event_attend')->where(array('id'=>$member_info['order_id'],'siteid'=>SITEID))->find();
		if($member_info && $order){
			$member_info['trade_sn'] = $order['trade_sn'];
			$member_info['member'] = json_decode($member_info['user_info'],true);
			$member_info['insurance_info'] = !empty($member_info['insurance_info']) ? json_decode($member_info['insurance_info'],true) : '' ;
			$this->assign('data',$member_info);
			$this->display();
		}else{
			$this->error('参数错误！');
		}		
	}

	public function do_detail_member_edit($id,$trade_sn = ''){
	
		if(!$id || !$trade_sn){
			$this->error('参数错误');
		}
		$realname = I('realname');
		$card = I('card');
		$telephone = I('telephone');
		$email = I('email');
		$emergencycontact = I('emergencycontact');
		$emergencyphone = I('emergencyphone');
		$sex = I('sex');
		$qq = I('qq');
		$accpre = I('accpre');
		$bloodtype = I('bloodtype');
		$allergies = I('allergies');
		$role = I('role');
		$role_description = I('role_description');
		$nickname = I('nickname');
		$nickname = I('nickname');
		/**************************************************/
		$this->check_realname($realname);
		$this->checkSex($sex);
		$this->check_mem_card($card);
		$this->check_telephone($telephone);
		if($qq !=''){
			$this->check_qq($qq);
		}
		if($nickname != ''){
			$nickname = trim(op_t($nickname));			
		}
		if($email != ''){ 
			$this->check_contacts_email($email);
		}

		if($emergencyphone != ''){ 
			if($telephone == $emergencyphone){
			   $this->error('紧急联系人与联系电话不能一致');
			}
			$this->check_emergencyphone($emergencyphone);
		}
		
		/**************************************************/
		$signer_info = D('event_signer')->where(array('id'=>$id,'siteid'=>SITEID))->find();
		$data['user_info']['realname'] = $realname;
		$data['user_info']['card'] = $card;
		$data['user_info']['telephone'] = $telephone;
		$data['user_info']['email'] = $email;
		$data['user_info']['emergencycontact'] = $emergencycontact;
		$data['user_info']['emergencyphone'] = $emergencyphone;
		$data['user_info']['sex'] = $sex;
		$data['user_info']['qq'] = $qq;
		$data['user_info']['accpre'] = implode(',',$accpre);
		$data['user_info']['bloodtype'] = $bloodtype;
		$data['user_info']['allergies'] = $allergies;
		$data['user_info']['role'] = $role;
		$data['user_info']['role_description'] = $role_description;
		$data['user_info']['nickname'] = $nickname;
		$data['user_info'] = json_encode($data['user_info']);
		
		$rs = D('event_signer')->where(array('id'=>$id,'siteid'=>SITEID))->save($data);
		if($rs){
			update_order_info($signer_info['order_id'],$signer_info['calendar_id']);
			$this->success('修改成功',U('Usercenter/Eventorder/myevent_detail_edit',array('trade_sn'=>$trade_sn)));				
		}else{
			$this->error('修改失败');
		}		
	}
    	
	/*我的故事*/
	public function event_story(){
		$id = is_login();
		if(is_login() == 0){
			$this->error('您还没有登录！');
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
		
	/*
	 * ajax验证优惠券卡号
	 */
	public function ajax_check_card($card){
		$ajax_check_card	=	D('Common/Pointcard')->ajax_check_card($card);

		exit(json_encode(array('status'=>$ajax_check_card['status'],'amount'=>$ajax_check_card['amount'],'name'=>$ajax_check_card['name'],'endtime'=>$ajax_check_card['endtime'],'msg'=>$ajax_check_card['msg'])));
		
		
	}
	 /**
     * 修改我的优惠券
     */
	public function edit_card(){
		if(IS_POST){
			$type = $_POST['check_card_use'];
			$id = $_POST['id'];
			$mem_num = D('event_signer')->where(array('order_id'=>$id,'siteid'=>SITEID))->count();
			$event_info = D('event_attend')->where(array('id'=>$id,'siteid'=>SITEID))->find();
			$paytype = $event_info['paytype'];
			$price = $event_info['price'];
			switch($type){
				case 1;		
					if($paytype == 1){
						$deposit = $event_info['deposit'];
						$data['totalprice'] = $mem_num * $price;
						$data['payprice'] = $mem_num * $deposit;
						$data['leftprice'] = $data['totalprice'] - $data['payprice'];
					}else{
						$data['totalprice'] = $mem_num * $price;
						$data['payprice'] = $data['totalprice'];
						$data['leftprice'] = $data['totalprice'] - $data['payprice'];
					}							
					$data['cardid'] = '';
					$res = D('event_attend')->where(array('id'=>$id,'siteid'=>SITEID))->save($data);
					if($res){
						if(!empty($event_info['cardid'])){
							$cardid = $event_info['cardid'];
							$save_data['status'] = 1;
							add_card_log($cardid,1,'变更活动订单-[取消]','[代金券/活动卡][使用/取消]');
							D('pointcard')->where(array('cardid'=>$cardid,'userid'=>is_login()))->save($save_data);
						}							
						$this->success('修改成功','refresh');
					}else{
						$this->error('您未做任何修改');
					}
				break;
				case 2;
					$cardid = $_POST['cardid'];
					if($cardid){
						if($event_info['cardid'] != ''){
							$card_old = $event_info['cardid'];
						}
						$card_info = D('Common/Pointcard')->check_card($cardid);
						if(!$card_info['status']){
							$this->error($card_info['msg']);
						}else{
							$card_info = D('pointcard')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->find();
							$amount = $card_info['amount'];
							$totalprice = $mem_num * $price;
							if($paytype == 1){
								$deposit = $event_info['deposit'];						
								$payprice = $mem_num * $deposit;
								$leftprice = $totalprice - $payprice;
								if($leftprice >= $amount){
									$data['totalprice'] = $totalprice - $amount;
									$data['payprice'] = $payprice;
								}else{
									$data['totalprice'] = $payprice;
									$data['payprice'] = $payprice;
								}
								$data_update['status'] = 2; 
								$data['leftprice'] = $data['totalprice'] - $data['payprice'];
							}else{
								$diff_price = $totalprice - $amount;
								if($diff_price >= 0){
									$data['totalprice'] = $totalprice - $amount;
									$data['payprice'] =  $data['totalprice'];
									$data['leftprice'] = 0;
									$data_update['status'] = 2; //新优惠券状态
								}else{
									$data['totalprice'] = $totalprice;
									$data['payprice'] =  0;
									$data['leftprice'] = 0;
									$data['pay_status'] = 2;
									$data['status'] = 30;
									$data_update['status'] = 3; 
								}
							}
							$data['cardid'] = $cardid;
							$rs = D('event_attend')->where(array('id'=>$id,'siteid'=>SITEID))->save($data);
							if($rs){							
								D('pointcard')->where(array('cardid'=>$cardid,'userid'=>is_login()))->save($data_update);
								add_card_log($cardid,$data_update['status'],'变更活动订单-[使用]','[代金券/活动卡][使用/取消]');
								if($card_old){
									$save_data['status'] = 1;//旧优惠券状态
									D('pointcard')->where(array('cardid'=>$card_old,'userid'=>is_login()))->save($save_data);
									add_card_log($card_old,1,'变更活动订单-[取消]','[代金券/活动卡][使用/取消]');
								}
								if($data['pay_status'] == 2){
									$this->success('优惠券使用成功，即将跳转到我的订单详情',U('Usercenter/Config/myevent_detail',array('trade_sn'=>$event_info['trade_sn'])));
								}else{
									$this->success('优惠券使用成功！','refresh');
								}								
							}else{
								$this->error('优惠券使用失败！');
							}
						}								
					}else{
						$this->error('未知的优惠券！');
					}
				break;
				case 3;
					$cardid = $_POST['cardid1'];
					if($cardid){
						if($event_info['cardid'] != ''){
							$card_old = $event_info['cardid'];
						}
						$card_info = D('Common/Pointcard')->check_card($cardid);
						if(!$card_info['status']){
							$this->error($card_info['msg']);
						}else{
							$card_info = D('pointcard')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->find();
							$amount = $card_info['amount'];
							$totalprice = $mem_num * $price;
							if($paytype == 1){
								$deposit = $event_info['deposit'];						
								$payprice = $mem_num * $deposit;
								$leftprice = $totalprice - $payprice;
								if($leftprice >= $amount){
									$data['totalprice'] = $totalprice - $amount;
									$data['payprice'] = $payprice;
								}else{
									$data['totalprice'] = $payprice;
									$data['payprice'] = $payprice;
								}
								$data_update['status'] = 2; 
								$data['leftprice'] = $data['totalprice'] - $data['payprice'];
							}else{
								$diff_price = $totalprice - $amount;
								if($diff_price >= 0){
									$data['totalprice'] = $totalprice - $amount;
									$data['payprice'] =  $data['totalprice'];
									$data['leftprice'] = 0;
									$data_update['status'] = 2; 
								}else{
									$data['totalprice'] = $totalprice;
									$data['payprice'] =  0;
									$data['leftprice'] = 0;
									$data['pay_status'] = 2;
									$data['status'] = 30;
									$data_update['status'] = 3; 
								}
							}
							$data['cardid'] = $cardid;
							$rs = D('event_attend')->where(array('id'=>$id,'siteid'=>SITEID))->save($data);
							if($rs){
								D('pointcard')->where(array('cardid'=>$cardid,'userid'=>is_login()))->save($data_update);
								add_card_log($cardid,$data_update['status'],'变更活动订单-[使用]','[代金券/活动卡][使用/取消]');
								if($card_old){
									$save_data['status'] = 1;
									D('pointcard')->where(array('cardid'=>$card_old,'userid'=>is_login()))->save($save_data);
									add_card_log($cardid,1,'变更活动订单-[取消]','[代金券/活动卡][使用/取消]');
								}
								$this->success('优惠券使用成功！','refresh');
							}else{
								$this->error('优惠券使用失败！');
							}
						}								
					}else{
						$this->error('未知的优惠券！');
					}
				break;
			}
		}else{
			$id = $_GET['id'];
			$event_attend = D('event_attend')->where(array('id'=>$id,'siteid'=>SITEID))->find();
			if($event_attend['pay_status'] != 0) $this->error('订单已支付，无法修改优惠券信息！');
			$paytype = $event_attend['paytype'];
			$price = $event_attend['price'];
			$deposit = $event_attend['deposit'];
			$mem_num = D('event_signer')->where(array('order_id'=>$id,'siteid'=>SITEID))->count();
			/*************************优惠券**************************************************************/
			$card_arr = D('pointcard')->where(array('userid'=>is_login(),'siteid'=>SITEID))->select();
			$card_final = array();
			foreach($card_arr as $key => &$val){
				$cardlist=D('Common/Pointcard')->check_card($val['cardid']);
				if(!$cardlis['status']){
					continue;
				}else{
					$card_final[] = $val;
				}
			}
			foreach($card_final as $key => &$val){
				if($val['endtime'] != 0){
					$endtime[$key] = date('Y-m-d H:i:s',$val['endtime']);
					$val['useinfo'] = "该<span style='color:red'>【".$val['typename']."】</span>有效期至 ".$endtime[$key]."";
				}else{
					$val['useinfo'] = "该<span style='color:red'>【".$val['typename']."】</span>长期有效";
				}
			}
			/*********************************************************************************************/
			if($event_attend['cardid'] != ''){
				$card_info = D('pointcard')->where(array('cardid'=>$event_attend['cardid'],'siteid'=>SITEID))->find();
				$card_info['name'] = $card_info['typename'];
			
			}else{
				$card_info['amount'] = 0;
			}
			
			if($paytype == 1){
			
				$totalprice = $mem_num * $price;
				$deposit = $event_attend['deposit'];			
				$payprice = $deposit * $mem_num;
				$diff_price = ($price - $event_attend['deposit']) * $mem_num;
				if($card_info['amount'] > $diff_price){
					$card_amount = $diff_price;
				}else{
					$card_amount = $card_info['amount'];		
				}
			}else{
				
				$totalprice = $mem_num * $price;
				$payprice = $totalprice;
				if($totalprice > $card_info['amount']){				
					$card_amount = $card_info['amount'];
				}else{
					$card_amount = $event_attend['totalprice'];
				}
			}
			$this->assign('totalprice',$totalprice);
			$this->assign('id',$id);
			$this->assign('card_info',$card_info);
			$this->assign('card_amount',$card_amount);
			$this->assign('total_num',$mem_num);
			$this->assign('payprice',$payprice);
			$this->assign('card_arr',$card_final);
			$this->assign('event_attend',$event_attend);
			$this->display();
			
		}
	
   	}

   /*
	* 更改删除优惠券的状态
   */
	public function edit_card_del($cardid){ 
		$save_data['status'] = 1;
		add_card_log($cardid,1,'变更活动订单-[取消]','[代金券/活动卡][使用/取消]');
		D('pointcard')->where(array('cardid'=>$cardid,'userid'=>is_login()))->save($save_data);
	}
	/*******详情页添加报名人验证*********/
	public function detail_member_check($event_id = 0,$calendar_id = 0,$check = '',$event_membercontacts = ''){
		$user_contacts = explode(',',$event_membercontacts);
		$check_arr = explode(',',$check);
		$map = "status >=1 and eventid = $event_id and id = $calendar_id";
		$calendar_info = D('event_calendar_time')->where($map)->find();
		foreach($user_contacts as $val){
			$data[$val]['calendar_arr'] = D('event_signer')->where(array('siteid'=>SITEID,'member_id'=>$val,'status'=>1))->field('calendar_id,user_info')->select();
			$data[$val]['user_info'] = json_decode($data[$val]['calendar_arr'][0]['user_info'],true);
			foreach($data[$val]['calendar_arr'] as $va){
				$map = "siteid = ".SITEID." and id = ".$va['calendar_id']." and status >= 1 and status <= 5";
				$data[$val]['schedule_arr'] = D('event_calendar_time')->where($map)->field("starttime,overtime")->select();
				foreach($data[$val]['schedule_arr'] as $k => $v){
					if(strtotime($calendar_info['starttime']) <= strtotime($v['starttime']) && strtotime($calendar_info['overtime']) >= strtotime($v['starttime'])){
						if(!in_array($val,$check_arr)){
							$contacts_id = $val;				
							$msg ='<b>温馨提示</b>：<br>参加者：<b>' .$data[$val]['user_info']['realname'].'</b><br>身份证：<b>'.$true.$data[$val]['user_info']['card'].'</b><br>所报名的其他排期与当前排期有时间冲突，请慎重选择！';
							exit(json_encode(array('status'=>0,'contacts_id'=>$contacts_id,'msg'=>$msg)));
						}
					}elseif(strtotime($calendar_info['starttime']) >= strtotime($v['starttime']) && strtotime($calendar_info['starttime']) <= strtotime($v['overtime'])){
						if(!in_array($val,$check_arr)){
							$contacts_id = $val;					
							$msg ='<b>温馨提示</b>：<br>参加者：<b>' .$data[$val]['user_info']['realname'].'</b><br>身份证：<b>'.$true.$data[$val]['user_info']['card'].'</b><br>所报名的其他排期与当前排期有时间冲突，请慎重选择！';
							exit(json_encode(array('status'=>0,'contacts_id'=>$contacts_id,'msg'=>$msg)));
						}
					}
				}
			}	
		}	
	}
	
	//优惠券使用数量与参加者数比较
	public function ajax_card_info(){ 
		$event_membercontacts = $_POST['event_membercontacts'];	
		$card_membercontacts = $_POST['card_membercontacts'];
		$user_contacts = explode(',',$event_membercontacts);
		$mycontact_count = count($user_contacts);//获取参加人数
		$card_contacts = explode(',',$card_membercontacts);
		if($card_contacts[0] == ''){ 
			$card_contacts = null;
		}
		$mycard_count = count($card_contacts);//获取卡券数
		if($mycard_count >= $mycontact_count)exit(json_encode(array('status'=>1,'msg'=>'优惠券使用数量超出参加者人数')));	
	
	}

	//参加者信息处理及订单价格处理
	public function ajax_oreder_info(){
		$msg['status'] = 1 ;
		$msg['msg'] = "";
		$check = $_POST['check'];
		$check_arr = explode(',',$check);
		$event_id = $_POST['event_id'];
		$calendar_id = $_POST['calendar_id'];
		$event_membercontacts = $_POST['event_membercontacts'];	
		$card_membercontacts = $_POST['card_membercontacts'];	
		$card_amount_num = $_POST['card_amount_num'];
		if(!$event_id || !$calendar_id) exit(json_encode(array('status'=>0,'msg'=>'参数错误')));
		if(!$event_membercontacts) exit(json_encode(array('status'=>0,'msg'=>'请选择参加者')));		 
		$event_content = D('Event')->where(array('status' => 1, 'id' => $event_id))->find();
		if (!$event_content) {
			 exit(json_encode(array('status'=>0,'msg'=>'活动不存在或已经下线')));
		}
		$map = "status >=1 and eventid = $event_id and id = $calendar_id and siteid = ".SITEID;
		$calendar_info = D('event_calendar_time')->where($map)->find();
		if(!$calendar_info)  exit(json_encode(array('status'=>0,'msg'=>'没有这个时间段的活动')));
		
		$user_contacts = explode(',',$event_membercontacts);
		$mycontact_count = count($user_contacts);//获取参加人数
		if($mycontact_count==0) exit(json_encode(array('status'=>0,'msg'=>'最少要有一个人参加活动')));

		$card_amount = explode(',',$card_amount_num);

		$card_num = count($card_amount);

		for($i=0;$i<=($card_num-1);$i++){ 
			$card_amount_m += $card_amount[$i];
		}

		$check = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $event_id, 'calendar_id' => $calendar_id,'siteid'=>SITEID))->select();
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
							exit(json_encode(array('status'=>0,'contacts_id'=>$u_info['id'],'msg'=>'<b>温馨提示</b>：<br>参加者：<b>'.$u_info['realname'].'</b><br>身份证：<b>'.$u_info['card'].'</b><br>已经在订单中了，您可以取消原订单或选择其他人员')));
							}
						}
					}
				}	
			}		
		}			
		foreach($user_contacts as $val){
			$map = "siteid = ".SITEID." and member_id = $val and order_status >0";
			$data[$val]['calendar_arr'] = D('event_signer')->where($map)->field('calendar_id,user_info')->select();
			$data[$val]['user_info'] = json_decode($data[$val]['calendar_arr'][0]['user_info'],true);
			foreach($data[$val]['calendar_arr'] as $va){
				$map = "siteid = ".SITEID." and id = ".$va['calendar_id']." and status >= 1 and status <= 5";
				$data[$val]['schedule_arr'] = D('event_calendar_time')->where($map)->field("starttime,overtime")->select();
				foreach($data[$val]['schedule_arr'] as $k => $v){
					if(strtotime($calendar_info['starttime']) <= strtotime($v['starttime']) && strtotime($calendar_info['overtime']) >= strtotime($v['starttime'])){
						if(!in_array($val,$check_arr)){
							$contacts_id = $val;
							$double = 1;							
							$msg ='<b>温馨提示</b>：<br>参加者：<b>' .$data[$val]['user_info']['realname'].'</b><br>身份证：<b>'.$true.$data[$val]['user_info']['card'].'</b><br>所报名的其他排期与当前排期有时间冲突，请慎重选择！';	
						}
					}elseif(strtotime($calendar_info['starttime']) >= strtotime($v['starttime']) && strtotime($calendar_info['starttime']) <= strtotime($v['overtime'])){
						if(!in_array($val,$check_arr)){
							$contacts_id = $val;
							$double = 1;							
							$msg ='<b>温馨提示</b>：<br>参加者：<b>' .$data[$val]['user_info']['realname'].'</b><br>身份证：<b>'.$true.$data[$val]['user_info']['card'].'</b><br>所报名的其他排期与当前排期有时间冲突，请慎重选择！';
						}
					}
				}
			}	
		}	
		if($calendar_info['paytype'] == 0 ){//全额支付
			$totalprice = $calendar_info['price'] * $mycontact_count;//总价
			$payprice =  $calendar_info['price'] * $mycontact_count;
		}else{
			$totalprice = $calendar_info['price'] * $mycontact_count;
			$payprice =  $calendar_info['deposit'] * $mycontact_count;//总定金
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
						'card' => $card_amount_m,
					)
				)
			)
		);     
    }

	
	public function discuss($event_id,$trade_sn){
		if (isset($event_id)&&isset($trade_sn)) {
			$map['id']=$event_id;
            $res=M('event')->where($map)->find();
            $this->assign('content',$res);
		}else{
	      $this->error('页面出现未知错误！');
		}
		$this->assign('res',$res);
		$this->assign('trade_sn',$trade_sn);
		$this->assign('event_id',$event_id);
	    $this->display();
	}


	public function  do_discuss($event_id,$trade_sn){
		if (IS_POST&&  isset($event_id) &&isset($trade_sn)) {
			$data['event_id']=$event_id;
			$data['trade_sn']=$trade_sn;
			$data['content'] =$_POST['discuss'];
			$data['time']    =date("Y-m-d H:i:s");
			$discuss=M('event_discuss');
			$result=$discuss->add($data);
			if (empty($_POST['discuss'])) {
				$this->error('对不起，您的点评类容为空！！点评不成功',U('Usercenter/Config/myevent'));
			}
			if ($result) {
			    $event=M('event_attend');
			    $map['trade_sn']=$trade_sn;
			    $t=$event->where($map)->setField('status',33);
			    if ($t) {
			    	$this->success('您好，你的点评已通过。谢谢',U('Usercenter/Config/myevent'));
			    }
			   else{
			   	 $this->error('对不起，点评不成功！！',U('Usercenter/Config/myevent'));
			   }
		     }else{
			$this->error('对不起，点评成功！',U('Usercenter/Config/myevent'));
		}
	}
   }
   

	public function insurance($status_id,$id){
		$status_id=I('status_id');
		$id =I('id'); 
		$map['order_id']=$id;
		$map['siteid']=SITEID;
		$result=D('event_signer')->where($map)->select();
		$arr1=json_decode($result['user_info'],true);
		$this->assign('arr',$arr1);
		$this->assign('status_id',$status_id);
		$this->assign('id',$id);
		$this->assign();	       	  
		$this->display();
	} 
	              
   public function do_insurance(){
      if(IS_POST){
         $data['status']=$_POST['status'];
         $data['id']    =$_POST['id'];
         $map['siteid']=SITEID;
         $map['order_id']=$data['id'];

         $result=M('event_signer')->where($map)->find();    
            $insurance_info=json_decode($result['insurance_info'],true);

            $insurance_info['policy_number']=$_POST['policy_number'];
            $result['insurance_info'] = $insurance_info;
            unset($data);
	        $data['insurance_info'] = json_encode($result['insurance_info']);
            $res=M('event_signer')->where($map)->save($data);
             if ($res) {
             	 $map['siteid']=SITEID;
             	 $map['id']=$_POST['id'];
             	 $arr=M('event_attend')->where($map)->find();
             	 if ($arr['paytype']==1) {
             	 	$result=M('event_attend')->where($map)->setField('status',13);
             	 }elseif ($arr['paytype']==0) {
             	    $result=M('event_attend')->where($map)->setField('status',22);}
					if($result)
             	$this->success('保单填写成功！',U('Usercenter/Config/open_event_dayinfo'));
             }
			 else{
             	$this->error('保单填写不成功！，请重试',U('Usercenter/Config/open_event_dayinfo'));
             }
      }else{
      	$this->error('对不起 您的操作不合法！');
      }
   }
   //得到会员可以使用的优惠券
   public function ajax_card_select(){
   		$card_membercontacts = $_POST['card_membercontacts'];
   		$server_condition    =$_POST['server_condition'];
		$ajax_card_select	=	D('Common/Pointcard')->ajax_card_select($card_membercontacts,$server_condition);
		exit(json_encode($ajax_card_select));
   }
   
	/***
	*验证手机号码**
	***/
  public function domobile(){
		$mobile = $_POST['mobile'];
		$mobile_find  = D('ucenter_member')->where(array('siteid'=>SITEID,'id'=>array('neq',is_login()),'mobile'=>$mobile))->find();
		if($mobile_find){
		   echo 1;
		}else{
		   echo 0;
		}
	  
  }
}
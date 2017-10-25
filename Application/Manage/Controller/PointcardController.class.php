<?php

namespace Manage\Controller;
set_time_limit(0);
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;


class PointcardController extends BaseController
{
	protected $pointcard;
	protected $pointtype;
    public function _initialize()
    {
        parent::_initialize();
		$this->pointcard=D('pointcard');
	}
//卡清单
	public function index(){ 
		$card_id=I('cardid');
		$map=array('status'=>array('egt',0),'siteid'=>SITEID);
		if(!empty($card_id)){
            $map['cardid|unifiedcardid']=array(trim($card_id), array('like', '%' . $card_id . '%'), '_multi' => true);
        } 
		$count=$this->pointcard->where($map)->count();
		$Page       = new \Think\Page($count,10);// 
		$show       = $Page->show(); 

		$list=$this->pointcard->where($map)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		foreach($list as $key=>$val){
			
			if($list[$key]['endtime']){
				$list[$key]['show_send']=($list[$key]['endtime']>time())?1:0;
			}else{ 
				$list[$key]['show_send']=1;
			}
			$user	=	query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $val['gmuid']);
			$list[$key]['admin']	=	$user['nickname'];
			if($list[$key]['userid']!=0){
				$card_use_info[$key] = D('pointcard_user')->where(array('cardid'=>$val['cardid'],'siteid'=>SITEID))->find();
				$list[$key]['usetime'] = $card_use_info[$key]['usetime'];
				$list[$key]['bindtime'] = $card_use_info[$key]['bindtime'];
				$userid	=	query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $val['userid']);
				$list[$key]['usernickname']	=	$userid['nickname'];	
				
			}elseif($list[$key]['mobile']!=0){
				$list[$key]['usernickname']	= '外站游客（未绑定）';
			}else{ 
				$list[$key]['usernickname']	= '暂无';
			}
			


		}
		
		$this->assign('page',$show);
		$this->assign('pointInfo',$list);
		$this->display();
	}

//启用和禁用卡卷
	 public function changeStatus($id=0,$method = null){
    	$id = array_unique((array)I('id', 0));
    	$method=$_GET['method'];
        $id = is_array($id) ? implode(',', $id) : $id;
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
       
        $map = "siteid = ".SITEID." and id in ($id)";

        $card_info = D('pointcard')->where($map)->select();
       	$list=$this->pointcard->where($map)->select();
        switch (strtolower($method)) {
            case 'forbidpoint':
            	foreach($list as $key=>$va){ 
              		if($list[$key]['status'] !=0){ 
              			$this->error('卡券状态有误,不能启用');
              		}
              	}
               	$this->pointcard->where($map)->setField('status', 1);
              		foreach($card_info as $key=>$val){ 
                      	if($val['cardtype']==1){
               				add_card_log($val['cardid'],1,'活动卡启用','代金券/活动卡(禁/启)用');
						}elseif($val['cardtype'] == 2){
							
							add_card_log($val['cardid'],1,'代金券启用','代金券/活动卡(禁/启)用');
						}
              		}
                
              	$this->success('启用成功');
				 break;
            case 'resumepoint':
            	foreach($list as $key=>$va){ 
              		if($list[$key]['status'] !=1){ 
              			$this->error('卡券状态有误,不能禁用');
              		}
              	}
                $this->pointcard->where($map)->setField('status',0);
                	foreach($card_info as $key=>$val){
	                	if($val['cardtype'] == 1){
							add_card_log($val['cardid'],0,'活动卡禁用','代金券/活动卡(禁/启)用');
						}elseif($val['cardtype'] == 2){
							add_card_log($val['cardid'],0,'代金券禁用','代金券/活动卡(禁/启)用');
						}
					}
              	$this->success('禁用成功');
                break;
            case 'deletepoint':
              	
              	foreach($list as $key=>$va){ 
              		if($list[$key]['status'] !=1){ 
              			$this->error('只有未使用的劵可以删除');
              		}
              	}
              	$this->pointcard->where($map)->setField('status',-1);
              	foreach ($card_info as $key => $val) {
          			if($val['cardtype'] == 1){
						add_card_log($val['cardid'],-1,'活动卡删除','代金券/活动卡删除用');
					}elseif($val['cardtype'] == 2){
						add_card_log($val['cardid'],-1,'代金券删除','代金券/活动卡删除用');
					}
              	}
              	$this->success('删除成功');
                break;          
            default:
                $this->error('参数非法');
        }
   }

//添加只有商品的卡券
	public function pointcard_add_shop(){
		$map['siteid']=SITEID;
		$map['status']=1;
		$shopinfo=D('shop')->where($map)->select();
		foreach($shopinfo as $k=>$v){
			$shopinfo[$k]['market_price']	=	D('Common/shop')->sku_ids_price($v['id']);
		}
		$this->assign('shopinfo',$shopinfo);
		$this->display();

	}
	//执行添加的入口
    public  function pointer_doAdd2(){ 
    	$couponcode	=  $_POST['couponcode'];
    	$cardnum  	=  $_POST['cardnum'];
    	$endtime	=  $_POST['endtime'];
    	$starttime  =  $_POST['starttime'];
    	$typename   =  trim($_POST['typename']);
    	$amount     =  $_POST['amount'];
    	$prefix     =  $_POST['prefix'];
    	$have_service= $_POST['have_service'];
    	$server_condition =$_POST['server_condition'];
    	$card_type =$_POST['card_type'];
    	$prefix     = strtoupper($prefix);
    	
    	if($card_type==2){ 
    		if(!empty($_POST['prtids'])){
    			
				$shop_id=implode(',',$_POST['prtids']);
			}else{

				$this->error('请勾选要使用的商品');
			}
    	}

    	if($have_service==1){ 
    		if(!preg_match("/^[1-9]\d*$/",$server_condition)){ 
				$this->error('使用条件必须为大于0的整数');
			}
    	}elseif($have_service==2){ 
    		$server_condition=0;

    	}elseif($have_service==3){ 
    		if($server_condition==0 || !is_numeric($server_condition)){ 
				$this->error('使用条件必须为整数');
			}
    	}
    	if($cardnum>10000){ 
    		$this->error('最大不能生成超过10000张');
    	}
    	if($typename==''){ 
    		$this->error('请填写卡型名称!');
    	}
    	if($amount=='' ||  !is_numeric($amount)) $this->error('请输入正确的面值金额!');
    	if($couponcode==1){ 
    		$this->pointer_doAdd($cardnum,$prefix,$endtime,$starttime,$typename,$amount,$server_condition);
		}else{ 
			$this->unifiedcard_doAdd($cardnum,$prefix,$endtime,$starttime,$typename,$amount,$server_condition,$card_type,$shop_id);
		}

    }

//添加卡卷	
    public function pointcard_add(){ 
		$this->display();
    }
//补发卡券
    public function pointcard_edit(){ 
    	$cardid=$_GET['cardid'];
    	$card_info=D('pointcard')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->find();
    	$this->assign('card_info',$card_info);
    	$this->display();
    }
    //补发商品卡券
    public function pointcard_edit_shop(){ 
    	$cardid=$_GET['cardid'];
    	$card_info=D('pointcard')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->find();
    	$map['id']=array('in',$card_info['shop_id']);
    	$shop_count=substr_count($card_info['shop_id'],',')+1;
		$shopinfo=D('shop')->where($map)->select();
		foreach($shopinfo as $k=>$v){
			$shopinfo[$k]['market_price']	=	D('Common/shop')->sku_ids_price($v['id']);
		}
		$this->assign('shop_count',$shop_count);
		$this->assign('shopinfo',$shopinfo);
    	$this->assign('card_info',$card_info);
    	$this->display();
    }
//
    public function pointcard_replacement(){
    	$couponcode	=  $_POST['couponcode'];
    	$cardnum  	=  $_POST['cardnum'];
    	$endtime	=  $_POST['endtime'];
    	$starttime  =  $_POST['starttime'];
    	$typename   =  trim($_POST['typename']);
    	$amount     =  $_POST['amount'];
    	$prefix     =  strtoupper($_POST['prefix']);
    	$server_condition =$_POST['server_condition'];
    	$card_type =$_POST['card_type'];
    	$shop_id   =$_POST['shop_id'];
    	if($cardnum>10000){ 
    		$this->error('最大不能生成超过10000张');
    	}
    	if($couponcode==2){ 
    		$this->unifiedcard_replacement($cardnum,$prefix,$endtime,$starttime,$typename,$amount,$server_condition,$card_type,$shop_id);
    		
    	}else{ 
    		$this->pointer_doAdd($cardnum,$prefix,$endtime,$starttime,$typename,$amount,$server_condition);
    	}

    }
    //商品详情
    public function point_shop_info($cardid=0){ 
    	if(!$cardid){ 
    		$this->error('非法入侵，请重试');
    	}
    	$shop_info=D('pointcard')->field('shop_id,unifiedcardid')->where("id = ".$cardid)->find();
    	$map['id']=array('in',$shop_info['shop_id']);
		$list = D('shop')->where($map) ->order("sort")->select();
		foreach($list as $k=>$v){
			$list[$k]['market_price']	=	D('Common/shop')->sku_ids_price($v['id']);
		}
		$this->assign('cardid',$shop_info['unifiedcardid']);
		$this->assign('datainfo',$list);
		$this->display();
    }






 	/*
	 * 点击查看相关日志
	 * $param $cardid 点卡卡号
	 */
	public function card_log($cardid){
		$map=array('cardid'=>$cardid,'siteid'=>SITEID);
		$count= D('pointcard_log')->where($map)->count();
		$Page       = new \Think\Page($count,10);// 
		$show       = $Page->show();
		$card_log = D('pointcard_log')->where($map)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		foreach($card_log as $key => &$val){
			$val['userinfo']	=	query_user(array('nickname'), $val['uid']);
			if($val['userinfo']==null){ 
				$val['userinfo']['nickname']="外站游客（未绑定）";
			}
			if($val['uid']==0 || $val['uid']==2147483647){ 
				$val['uid'] ='外站游客';
			}
		}
		$this->assign('page',$show);
		$this->assign('card_log',$card_log);
		$this->display();
	}

	//改变卡券状态
	 public function setpointcardStatus($ids, $status){
        $builder = new AdminListBuilder();
        $builder->doSetStatus('pointcard', $ids, $status);
    }


	/*
	*批量生成点卡
	*/
	public function pointer_doAdd($cardnum,$prefix,$endtime,$starttime,$typename,$amount,$server_condition){  
		$endtime = op_t(trim($endtime));
		$starttime = op_t(trim($starttime));
		if(!empty($endtime)){
			 $endtime = strtotime($endtime);
			 if($endtime	<=	time()) $this->error('结束使用时间不能小于当前时间!');
		}else{
			 $endtime = 0;
		}
		if(!empty($starttime)){
			$starttime	= strtotime($starttime);//开始使用时间
		}else{
			$starttime = 0;
		}
		if(!empty($endtime) && !empty($starttime)){
			if($starttime > $endtime){
				$this->error('结束使用时间不能小于开始使用时间');
			}
		}
		$number  = intval($cardnum); //生数量
		$createtime = time();
		if($number	==''||!is_numeric($number)) $this->error('请填写正确的生成数量!');	
		if($prefix	=='') $this->error('请填写卡号前缀');
		if(preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$prefix)) $this->error('卡号前缀不能使用中文!');
		if(!preg_match("/^[a-zA-Z0-9]{4,8}$/", $prefix)){ 
			$this->error("需为4到8位字母或数字");
		}
	
		$cycledata=array( 
				'siteid'	=>	SITEID,
				'gmuid'		=>	is_login(),
				'createtime'=>	$createtime,
				'cardkey'	=>	$cardkey,
				'amount'	=> 	$amount,
				'endtime'	=> 	$endtime,
				'point'		=>	0,
				'cardtype'	=>	2,
				'status'	=>  1,
				'starttime' => 	$starttime,
				'unifiedcardid'=>$prefix,
				'stamp'     =>1,
				'typename'  =>$typename,
				'server_condition'=>$server_condition,
			);
		$list=$this->cycle_add_card($number,$prefix,$cycledata);
		if($list){
			$this->success('添加成功!',U('Pointcard/index'));
		}else{
			$this->error('添加失败!');
		}
	
	}
	public function unifiedcard_replacement($cardnum,$prefix,$endtime,$starttime,$typename,$amount,$server_condition,$card_type=1,$shop_id){ 
		$endtime = op_t(trim($endtime));
		$starttime = op_t(trim($starttime));
		$num  = intval($cardnum); //生数量
		$prefix =trim($prefix);  //统一券码
		$createtime=time();
		if(!empty($endtime)){
			 $endtime = strtotime($endtime);
			 if($endtime	<=	time()) $this->error('结束使用时间不能小于当前时间!');
		}else{
			 $endtime = 0;
		}
		if(!empty($starttime)){
			$starttime	= strtotime($starttime);//开始使用时间
		}else{
			$starttime = 0;
		}
		if(!empty($endtime) && !empty($starttime)){
			if($starttime > $endtime){
				$this->error('结束使用时间不能小于开始使用时间');
			}
		}
		if($num	==''||!is_numeric($num)) $this->error('请填写正确的生成数量!');	
		if($prefix	=='') $this->error('请填写号码前缀');
		if(preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$prefix)) $this->error('统一券码不能使用中文!');
		if(!preg_match("/[a-zA-Z0-9]{4,8}/", $prefix)){ 
			$this->error("需为4到8位字母或数字");
		}

		$undata=D('pointcard_unified')->where(array('unifiedcardid'=>$prefix,'siteid'=>SITEID))->find();
		$replacementdata['num']=$undata['num']+$num;
		$replacementdata['leftnum']=$undata['leftnum']+$num;
		$savereplacement=D('pointcard_unified')->where(array('unifiedcardid'=>$prefix,'siteid'=>SITEID))->save($replacementdata);
		if($savereplacement){ 
			$cycledata = array(
				'siteid'	=> SITEID,
				'gmuid'		=>	is_login(),
				'createtime'=>	$createtime,
				'cardkey'	=>	$cardkey,
				'amount'	=> 	$amount,
				'endtime'	=> 	$endtime,
				'point'		=>	0,
				'cardtype'	=>	2,
				'status'	=>  1,
				'starttime' =>  $starttime,
				'unifiedcardid'=>$prefix,
				'stamp'     =>  2,
				'typename'  =>  $typename,
				'server_condition'=>$server_condition,//金额限定
			  	'card_type'  =>$card_type,//1为通用 2 为商品
			    'shop_id'    =>$shop_id,  //商品的id
				);
			$list=$this->cycle_add_card($num,$prefix,$cycledata);
			if($list){
				$this->success('补发成功!',U('Pointcard/index'));
			}else{
				$this->error('补发失败!');
			}
		}else{ 
			$this->error('补发失败！');
		}



	}
	
	//执行2个表的添加
	public function unifiedcard_doAdd($cardnum,$prefix,$endtime,$starttime,$typename,$amount,$server_condition,$card_type=1,$shop_id){ 
		$endtime = op_t(trim($endtime));
		$starttime = op_t(trim($starttime));
		$num  = intval($cardnum); //生数量
		$prefix =trim($prefix);  //统一券码
		$prefix=strtoupper($prefix);
		$createtime = time();
		if(!empty($endtime)){
			 $endtime = strtotime($endtime);
			 if($endtime	<=	time()) $this->error('结束使用时间不能小于当前时间!');
		}else{
			 $endtime = 0;
		}
		if(!empty($starttime)){
			$starttime	= strtotime($starttime);//开始使用时间
		}else{
			$starttime = 0;
		}
		if(!empty($endtime) && !empty($starttime)){
			if($starttime > $endtime){
				$this->error('结束使用时间不能小于开始使用时间');
			}
		}
		if($num	==''||!is_numeric($num)) $this->error('请填写正确的生成数量!');	
		if($prefix	=='') $this->error('请填写号码前缀');
		if(preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$prefix)) $this->error('统一券码不能使用中文!');
		if(!preg_match("/[a-zA-Z0-9]{4,8}/", $prefix)){ 
			$this->error("需为4到8位字母或数字");
		}
		$r=$this->checkCardUnifiedId($prefix);
		$rr=$this->checkUnifiedId($prefix);
		if(!$r && !$rr){ 
		}else{ 
			$this->error('此券码已存在，请填写其他码号');
		}
		//添加主码
		$unlist=array(
				      'siteid'	 	=>	SITEID,	
					  'gmuid'		=>	is_login(),
					  'createtime'	=>	$createtime,
					  'unifiedcardid'	=>	$prefix,
					  'num'     	=>	$num,
					  'leftnum'		=> 	$num,
					  'amount'		=> 	$amount,
					  'endtime'		=> 	$endtime,
					  'cardtype'	=>	2,
					  'status'		=>  1,
					  'starttime' 	=> $starttime,
					  'server_condition'=>$server_condition,//金额限定
					  'card_type'  =>$card_type,//1为通用 2 为商品
					  'shop_id'    =>$shop_id,  //商品的id
					);
		$undata=D('pointcard_unified')->add($unlist);
		//循环添加小码
		if($undata){
			$cycledata = array(
				'siteid'	=> SITEID,
				'gmuid'		=>	is_login(),
				'createtime'=>	$createtime,
				'cardkey'	=>	$cardkey,
				'amount'	=> 	$amount,
				'endtime'	=> 	$endtime,
				'point'		=>	0,
				'cardtype'	=>	2,
				'status'	=>  1,
				'starttime' => $starttime,
				'unifiedcardid'=>$prefix,
				'stamp'     =>2,
				'typename'  =>$typename,
				'server_condition'=>$server_condition,
				'card_type'  =>$card_type,//1为通用 2 为商品
				'shop_id'    =>$shop_id,  //商品的id
				);	
			$list=$this->cycle_add_card($num,$prefix,$cycledata);
			if($list){
				$this->success('添加成功!',U('Pointcard/index'));
			}else{
				$this->error('添加失败!');
			}

		}else{ 
			$this->error('添加失败！');
		}
	}
	/*循环添加卡券小码
		$num 数量
		$prefix 前缀
		$cycledata 参数
	*/
	public function cycle_add_card($num,$prefix,$cycledata){ 
		$length= 8;
		$data=$cycledata;
		$j=$num;
		$prefix=strtoupper($prefix);
		for ($i = 0 ; $i < $num ; $i++ ){
			$cardid	= $prefix.substr(str_shuffle("01234567890123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"),0,$length);
			switch($data['cardtype']){
				case 1://有密卡
					$data['cardkey']	 = substr(str_shuffle("01234567890123456789"),0,8);
				break;
				case 2://无密卡
					$data['cardkey']	 = 	'';
				break;
				
			}
			$r = $this->checkCardId($cardid);
			if(empty($r)){ 
				$data['cardid']=$cardid;
				$list	=	D('pointcard')->add($data);
				$j--;
			}else{ 
				continue;
			}
		}
		if($j>0){ 
			$this->cycle_add_card($j,$prefix,$cycledata);
		}else{ 
			return true;
		} 

	}

	public function send_to_user($cardid=''){ 
		if(!$cardid) $this->error('参数错误！');
		$this->assign('cardid',$cardid);
		$this->display();
	}

	public function do_send($cardid = '',$mobile = '',$user_select='',$userid='',$select_send=''){

		if($select_send==2){ 
			if($userid==''){ 
				$this->error('用户昵称或ID不能为空');
			}
			if($userid==is_login()){ 
				$this->error('派发对象不能为自己！');
			}

			$map=array('nickname'=>$userid,'siteid'=>SITEID,'status'=>1);
			$memlist=D('member')->where($map)->find();
			if($memlist){ 
				$userid=$memlist['uid'];
			}else{ 
				$memdata=D('member')->where(array('uid'=>$userid,'siteid'=>SITEID,'status'=>1))->find();
				if($memdata){ 
					$userid=$userid;
				}else{ 
					$this->error('您填写的内容无对应账号，请确认内容是否填写正确!');
				}
			}
			$list=D('pointcard')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->find();
			$data=D('pointcard')->where(array('unifiedcardid'=>$list['unifiedcardid'],'userid'=>$userid,'siteid'=>SITEID,'stamp'=>2))->find();
			if($data){ 
				$this->error('该用户已领取过此类劵');
			}else{ 
				D('pointcard_unified')->where(array('unifiedcardid'=>$list['unifiedcardid'],'siteid'=>SITEID))->setDec('leftnum');
				$pid=D('pointcard')->where(array('siteid'=>SITEID,'cardid'=>$cardid))->save(array('userid'=>$userid));
				$point_arr=array(
					'bindtime' =>time(),
					'cardid' =>$cardid,
					'siteid' =>SITEID,
					'userid' =>$userid,
					'to_uid' => $userid,
					'from_uid'=> is_login()
					);
				$p_add=$bindcarduser=D('pointcard_user')->data($point_arr)->add();
				if($p_add && $pid){ 
					$mobile=D('ucenter_member')->where(array('siteid'=>SITEID,'uid'=>$userid))->getField('mobile');
					if(!Gcheck_Mobile($mobile)){ 
						$webinfo = json_decode(WEBSITEINFO,true);
						$webname = $webinfo['webname'];
						$web_url = "http://".$_SERVER['HTTP_HOST'];
						$user_info = query_user(array('nickname',$userid));
						$user_name = $user_info['nickname'];
						$pointdata= array(
							'user_name'		=>	$user_name,
							'webname'		=>	$webname,
							'cardid'	 	=>	$cardid,
							'noticetype'    =>  'distribute_coupons',
							'web_url'       =>  $web_url,

						);
						$contactway=array($mobile);
						D('Message')->addSendMessage('send_sms_to_user',$contactway,$pointdata,0,1);
						add_card_log($cardid,1,'','',$userid,true);
						$this->success('派发成功,我们将尽快短信通知对方!',U('Pointcard/index'));	
					}else{ 
						$this->success('派发成功',U('Pointcard/index'));
					} 
				}
			}

		}else{
			if(!Gcheck_Mobile($mobile)){
				$user_info = D('ucenter_member')->where(array('mobile'=>$mobile,'siteid'=>SITEID))->select();
				if($user_info){
					if(count($user_info) > 1 && $user_select== '' ){
						$this->error('该手机号码对应多个账号，请选择您要操作的账号再派发');
					}elseif((count($user_info) > 1 && $user_select != '') || count($user_info) == 1){
						$uid = count($user_info) > 1 ? $user_select : $user_info[0]['id'] ;
						if($uid == is_login()) $this->error('派发对象不能为自己！');

						$card_check = D('Pointcard')->check_card($cardid,true);	
						if($card_check['status']){
							$card_info = D('pointcard')->where(array('siteid'=>SITEID,'cardid'=>$cardid))->find();
							if($card_info['stamp']==2){ 
								$unifiedata=D('pointcard')->where(array('unifiedcardid'=>$card_info['unifiedcardid'],'userid'=>$uid,'siteid'=>SITEID))->find();
								if($unifiedata){ 
									$this->error('该用户已领取过此类劵');
								}else{
									D('pointcard_unified')->where(array('unifiedcardid'=>$card_info['unifiedcardid'],'siteid'=>SITEID))->setDec('leftnum');
								}
							}
							$data['siteid'] = SITEID;
							$data['cardid'] = $cardid;
							$data['userid'] = $uid;
							$data['to_uid'] = $uid;
							$data['from_uid'] = is_login();
							$data['bindtime'] = time();
							$data['gettime'] = time();
							$p_add = D('pointcard_user')->add($data);

							/**开启事务**/
							$savedata['userid'] = $uid;
							D('pointcard')->startTrans();
							$pid = D('pointcard')->where(array('cardid'=>$cardid,'siteid'=>SITEID))->save($savedata);
							
							if($p_add && $pid){
								
								$webinfo = json_decode(WEBSITEINFO,true);
								$webname = $webinfo['webname'];
								$web_url = "http://".$_SERVER['HTTP_HOST'];
								$user_info = query_user(array('nickname',$event_uid));
								$user_name = $user_info['nickname'];
								$pointdata= array(
									'user_name'		=>	$user_name,
									'webname'		=>	$webname,
									'cardid'	 	=>	$cardid,
									'noticetype'    =>  'distribute_coupons',
									'web_url'       =>  $web_url,

								);
								$contactway=array($mobile);                
								D('Message')->addSendMessage('send_sms_to_user',$contactway,$pointdata,0,1);
								add_card_log($cardid,1,'','',$uid,true);						
								D('pointcard')->commit();
								$this->success('派发成功!',U('Pointcard/index'));
							}else{
								D('pointcard')->rollback();
								$this->error('派发失败！');
							}
						}else{
							$this->error($card_check['msg']);
						}
					}
					
					
				}else{
					$this->error('您提供的手机号无对应账号，请确认账号手机信息是否正确');
				}			
			}else{
				$this->error('请输入有效的手机号码！');
			}
		}
	}

	/*验证统一码*/
	public function checkUnifiedId($unifiedcardid){
		$map=array('unifiedcardid'=>$unifiedcardid,'siteid'=>SITEID);
		$list=D('pointcard_unified')->where($map)->find();
		if($list){ 
			return true;
		}else{ 
			return false;
		}

	}

	 public function checkCardUnifiedId($unifiedcardid){
		$list	=	D('pointcard')->where(array('unifiedcardid'=>$unifiedcardid,'siteid'=>SITEID))->find();
		if($list){
			return true;
		}else{
			return false;
		}
    }

	public function check_user_id($userid,$cardid){
		$userid=trim($userid);
		$cardmap=array('cardid'=>$cardid,'siteid'=>SITEID);
		$cardlist=D('pointcard')->where($cardmap)->find();
		$map=array('nickname'=>$userid,'siteid'=>SITEID,'status'=>1);
		$list=D('member')->where($map)->find();
		if($list){
			if($list['uid']!=is_login()){
				if($cardlist['stamp']==2){ 
					$carddata=D('pointcard')->where(array('unifiedcardid'=>$cardlist['unifiedcardid'],'userid'=>$list['uid']))->find();
					if($carddata){ 
						exit(json_encode(array('status'=>false,'msg'=>'用户已经领取过该类劵！')));
					}
				}

				$v['user'] = query_user(array('id','sex','nickname','reg_time' ,'last_login_time','mobile_space_url', 'space_link','avatar64', 'rank_html'), $list['uid']);
				if($v['user']['sex']==0){
						$v['user']['sex']='女'; 
					}else{ 
						$v['user']['sex']='男';
					}
				$v['user']['reg_time']=date('Y-m-d H:i:s',$v['user']['reg_time']);
				$v['user']['last_login_time']=date('Y-m-d H:i:s',$v['user']['last_login_time']);
				echo json_encode(array('status'=>true,'list'=>$v['user']));
			}else{ 
				echo json_encode(array('status'=>false,'msg'=>'派发对象不能为自己！'));
			} 
		

		}else{ 

			$map2=array('uid'=>$userid,'siteid'=>SITEID,'status'=>1);
			$data=D('member')->where($map2)->find();

			if($data){ 

				if($data['uid']!=is_login()){ 

					if($cardlist['stamp']==2){ 
						$carddata=D('pointcard')->where(array('unifiedcardid'=>$cardlist['unifiedcardid'],'userid'=>$data['uid']))->find();
						if($carddata){ 
							exit(json_encode(array('status'=>false,'msg'=>'用户已经领取过该类劵！')));
						}
					}
					$v['user'] = query_user(array('id','sex','nickname','reg_time' ,'last_login_time','mobile_space_url', 'space_link','avatar64', 'rank_html'), $data['uid']);
					if($v['user']['sex']==0){
						$v['user']['sex']='女'; 
					}else{ 
						$v['user']['sex']='男';
					}
					$v['user']['reg_time']=date('Y-m-d H:i:s',$v['user']['reg_time']);
					$v['user']['last_login_time']=date('Y-m-d H:i:s',$v['user']['last_login_time']);
					echo json_encode(array('status'=>true,'list'=>$v['user']));
				}else{ 
					echo json_encode(array('status'=>false,'msg'=>'派发对象不能为自己！'));
				}
			}else{ 
				echo json_encode(array('status'=>false,'msg'=>'您填写的内容无对应账号，请确认内容是否填写正确'));

			}
		} 

			
	}



	/*
	*验证卡号
	*/
    public function checkCardId($cardid)
    {
		$list	=	D('pointcard')->where(array('cardid'=>$cardid))->find();
		if($list){
			return true;
		}else{
			return false;
		}
    }
	/**
	* 获取随机字符串
	*/
	public function randString($length) {
		$mt_string = 'P5kQjRi6ShTgU7fVeK3pLoM4nNmOlW8dXcY9bZaAzBy0CxDwEv1FuGtHs2IrJq';
		$str = '';
		while ($length--) {
			$str .= $mt_string[mt_rand(0, 61)];
		}
		return $str;
	}


}  

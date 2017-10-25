<?php

namespace Websit\Controller;

use Think\Controller;

class EventController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize(); 
		$tree = D('EventType')->where(array('status' => 1 ,'siteid'=>SITEID))->select();		
        $this->assign('tree', $tree);
	}
	    public function index(){
			$event_all = D('event')->where(array('siteid'=>SITEID))->select();
			$count = count($event_all);
			$Page = new \Think\Page($count,10);
			$show = $Page->show();// 分页显示输出
			$eventall = D('event')->where(array('siteid'=>SITEID))
								->field(array('id','is_recommend','title','status'))
								->order("diff_time")
								->limit($Page->firstRow.','.$Page->listRows)
								->select();
			if($show != "<div class='pagination'>    </div>"){
				$this->assign('page',$show);
			}
			$this->assign('count',$count);
			$this->assign('event_all',$eventall);
			$this->display();
    }
	/*
	 * 活动添加
	 */
	public function add(){
		$id = $_GET['event_id'];
		if($id){
			$event_content = D('Event')->where(array('id' =>$id,'siteid'=>SITEID))->find();
			
			if(!checked_admin(is_login()) && !is_administrator(is_login())){		
				if (!$event_content) {
					$this->error('404 not found');
				}
				 if ($event_content['uid'] != is_login()) {
					$this->error('404 not found');
				}
			}else{
				if (!$event_content) {
					$this->error('404 not found');
				}
			}
			if ($event_content['pictures_id']) {
				$pictures = M("Picture")->field('id,path')->where("id in ({$event_content['pictures_id']})")->select();
				foreach ($pictures as &$img) {
					$img['path'] = fixAttachUrl($img['path']);
				}
				unset($img);
				$this->assign('pictures', $pictures);
			}

			
			$begincity = get_citys($event_content['begincity']);
			unset($event_content['begincity']);
			
			$event_content['begincity']['community'] = $begincity['community'];
			$event_content['begincity']['district'] = $begincity['district'];
			$event_content['begincity']['city'] = $begincity['city'];
			$event_content['begincity']['province'] = $begincity['province'];

			
			$finalcity = get_citys($event_content['finalcity']);
			unset($event_content['finalcity']);
			$event_content['finalcity']['community'] = $finalcity['community'];
			$event_content['finalcity']['district'] = $finalcity['district'];
			$event_content['finalcity']['city'] = $finalcity['city'];
			$event_content['finalcity']['province'] = $finalcity['province'];	
			$this->assign('event_arr',$event_content);
		}
		$order = "update_time desc";
		$add = D('event')->where(array('uid'=>is_login(),'siteid'=>SITEID))->order($order)->field(array('detailadd','begincity','attention'))->find();
		$begincity = get_citys($add['begincity']);
		unset($add['begincity']);
		$add['begincity']['district'] = $begincity['district'];
		$add['begincity']['city'] = $begincity['city'];
		$add['begincity']['province'] = $begincity['province'];
		if($add != ''){
			$this->assign('add',$add);
		}
		$this->assign('url',$url);
		$this->setTitle('添加活动'.'——活动');
		$this->setKeywords('添加'.',活动');
		$this->display();
	}
   	/*得到当前活动的所有排期*/
	public function event_schedule($id = 0){
		$uid = is_login();
		$rs = D('Event')->where(array('id' => $id,'siteid'=>SITEID))->find();
		if($rs){
			$content_arr = D('event_calendar_time')->where(array('eventid' => $rs['id'],'siteid'=>SITEID))->order('starttime desc')->select();
			$count = count($content_arr);
			$Page = new \Think\Page($count,10);
			$show = $Page->show();// 分页显示输出
			$content_arr = D('event_calendar_time')
							->where(array('eventid' => $rs['id'],'siteid'=>SITEID))
							->limit($Page->firstRow.','.$Page->listRows)
							->order('starttime desc')->select();
			$maxpeople = $rs['maxpeople'];
			foreach ($content_arr as $key=> &$v) {
				$v['id']= $v['id'];
				$v['ticket']= $maxpeople - $v['regnumber'];
				$v['starttime']= $v['starttime'];
				$v['endtime']= $v['endtime'];
				
				if($v['leader']){
					$leader_arr = explode(',',$v['leader']);
					$leaders ='';
					foreach ($leader_arr as $ku=> &$u) {
						$member = D('member')->where(array('uid' => $u))->find();
						if(!$member) continue;
						$leaders .='<a target="_blank" href="'.U('Usercenter/Index/index',array('uid'=>$member['uid'])).'">'.$member['nickname'].'</a> ';
					}	
					$v['leader'] =$leaders;
				}
				if($v['status'] <= 1){
						if($v['status'] == 1){
							if(strtotime("$v[endtime]")-time() > 0){
								if($v['maxpeople'] != 0){
									if(($v['maxpeople']-$v['regnumber']) < 0){
											$v['info'] = '<span style="color:green"><span style="color:red">报满</span>预约</span>';
									}else{
										$v['info'] = '<span style="color:green">接受报名</span>';
									}
								}else{
									$v['info'] = '<span style="color:green">接受报名</span>';
								}
							}else{
								if(strtotime("$v[starttime]")-time() > 0 ){
									$v['info'] = '<span style="color:red">报名截止</span>';
								}else{
									if(strtotime("$v[overtime]")-time() >= 0  && strtotime("$v[starttime]")-time() <= 0){
										$v['info'] = '<span style="color:red">进行中</span>';
									}elseif(strtotime("$v[overtime]")-time() < 0){
										$v['info'] = '<span style="color:red">已结束</span>';
									}
								}
							}
						}elseif($v['status'] == -1){
							$v['info'] = '<span style="color:red">已删除</span>';
						}
				}elseif($v['status'] == 2){
					$v['info'] = '<span style="color:green">接受报名</span>';
				}elseif($v['status'] == 3){
					$v['info'] = '<span style="color:green"><span style="color:red">报满</span>预约</span>';
				}elseif($v['status'] == 4){
					$v['info'] = '<span style="color:red">报名截止</span>';
				}elseif($v['status'] == 5){
					$v['info'] = '<span style="color:red">进行中</span>';
				}elseif($v['status'] == 6){
					$v['info'] = '<span style="color:red">已结束</span>';
				}
				
				if($v['vehicle']){
					$vehicle_arr = explode(',',$v['vehicle']);
					$vehicles='';
					foreach ($vehicle_arr as &$ve) {
						$vehicles .= get_vehicle($ve).' ';
					}
					$v['vehicle'] =$vehicles;
				}

				if($v['accommodation']){
					$accommodation_arr = explode(',',$v['accommodation']);
					$accommodations='';
					foreach ($accommodation_arr as &$ac) {
						$accommodations .= get_accommodation($ac).' ';
					}
					$v['accommodation'] =$accommodations;
				}
				
			}
		}
		if($show != "<div class='pagination'>    </div>"){
			$this->assign('page',$show);
		}
		$this->assign('event_content',$rs);
        $this->assign('contents',$content_arr);
		
        $this->display();
	}
	/*推荐活动*/
	public function event_recommend($id,$is_recommend){
	   $status = D('event')->where(array('siteid'=>SITEID,'id'=>$id))->getField('status');
	   if($status == 0){
			$this->error('该活动已被禁用，无法推荐！');
	   } 
	   $data['is_recommend'] = $is_recommend;
	   if($status == 1){
			$str = '推荐成功！';
			$str1 = '推荐失败！';			
		}else{
			$str = '推荐成功！';
			$str1 = '推荐失败！';
		}
	   $event=D('event')->where(array('id'=>$id,'siteid'=>SITEID))->save($data);
	   if($event){
	     $this->success($str);
	   }else{
	     $this->error($str1);
	   }
	
	}
	 /**
     * 编辑活动
     * @param $id
     * autor:xjw129xjt
     */
    public function edit($id)
    {
        $event_content = D('Event')->where(array('id' =>$id,'siteid'=>SITEID))->find();
		if(!checked_admin(is_login()) && !is_administrator(is_login())){		
			if (!$event_content) {
				$this->error('404 not found');
			}
			 if ($event_content['uid'] != is_login()) {
                $this->error('404 not found');
            }
		}else{
			if (!$event_content) {
				$this->error('404 not found');
			}
		}
		if ($event_content['pictures_id']) {
            $pictures = M("Picture")->field('id,path')->where("id in ({$event_content['pictures_id']})")->select();
            foreach ($pictures as &$img) {
                $img['path'] = fixAttachUrl($img['path']);
            }
            unset($img);
            $this->assign('pictures', $pictures);
        }

		$insurance_id = $event_content['insurance'];
		$websit_insurance_info = get_insurance();
		if(!empty($websit_insurance_info)){
			if(!empty($insurance_id)){
				$insurance_string = get_insurance_select($insurance_id);
			}else{
				$insurance_string = get_insurance_select();
			}					
		}else{
			$insurance_string = '';
		}
		$begincity = get_citys($event_content['begincity']);
		unset($event_content['begincity']);
		
		$event_content['begincity']['community'] = $begincity['community'];
		$event_content['begincity']['district'] = $begincity['district'];
		$event_content['begincity']['city'] = $begincity['city'];
		$event_content['begincity']['province'] = $begincity['province'];

		
		$finalcity = get_citys($event_content['finalcity']);
		unset($event_content['finalcity']);
		$event_content['finalcity']['community'] = $finalcity['community'];
		$event_content['finalcity']['district'] = $finalcity['district'];
		$event_content['finalcity']['city'] = $finalcity['city'];
		$event_content['finalcity']['province'] = $finalcity['province'];		
		/*********************加载排期*****************************************/		
			$count = D('event_calendar_time')->where(array('eventid' => $id,'siteid'=>SITEID))->order('starttime desc')->count();
			$Page = new \Think\Page($count,10);
			$show = $Page->show();// 分页显示输出
			$content_arr = D('event_calendar_time')
							->where(array('eventid' => $id,'siteid'=>SITEID))
							->limit($Page->firstRow.','.$Page->listRows)
							->order('starttime desc')->select();
			$maxpeople = $rs['maxpeople'];
			foreach ($content_arr as $key=> &$v) {
				$v['id']= $v['id'];
				$v['ticket']= $maxpeople - $v['regnumber'];
				$v['starttime']= $v['starttime'];
				$v['endtime']= $v['endtime'];
				
				if($v['leader']){
					$leader_arr = explode(',',$v['leader']);
					$leaders ='';
					foreach ($leader_arr as $ku=> &$u) {
						$member = D('member')->where(array('uid' => $u))->find();
						if(!$member) continue;
						$leaders .='<a target="_blank" href="'.U('UserCenter/Index/index',array('uid'=>$member['uid'])).'">'.$member['nickname'].'</a> ';
					}	
					$v['leader'] =$leaders;
				}
				if($v['status'] <= 1){
						if($v['status'] == 1){
							if(strtotime("$v[endtime]")-time() > 0){
								if($v['maxpeople'] != 0){
									if(($v['maxpeople']-$v['regnumber']) < 0){
											$v['info'] = '<span style="color:green"><span style="color:red">报满</span>预约</span>';
									}else{
										$v['info'] = '<span style="color:green">接受报名</span>';
									}
								}else{
									$v['info'] = '<span style="color:green">接受报名</span>';
								}
							}else{
								if(strtotime("$v[starttime]")-time() > 0 ){
									$v['info'] = '<span style="color:red">报名截止</span>';
								}else{
									if(strtotime("$v[overtime]")-time() >= 0  && strtotime("$v[starttime]")-time() <= 0){
										$v['info'] = '<span style="color:red">进行中</span>';
									}elseif(strtotime("$v[overtime]")-time() < 0){
										$v['info'] = '<span style="color:red">已结束</span>';
									}
								}
							}
						}elseif($v['status'] == -1){
							$v['info'] = '<span style="color:red">已删除</span>';
						}
				}elseif($v['status'] == 2){
					$v['info'] = '<span style="color:green">接受报名</span>';
				}elseif($v['status'] == 3){
					$v['info'] = '<span style="color:green"><span style="color:red">报满</span>预约</span>';
				}elseif($v['status'] == 4){
					$v['info'] = '<span style="color:red">报名截止</span>';
				}elseif($v['status'] == 5){
					$v['info'] = '<span style="color:red">进行中</span>';
				}elseif($v['status'] == 6){
					$v['info'] = '<span style="color:red">已结束</span>';
				}
				
				if($v['vehicle']){
					$vehicle_arr = explode(',',$v['vehicle']);
					$vehicles='';
					foreach ($vehicle_arr as &$ve) {
						$vehicles .= get_vehicle($ve).' ';
					}
					$v['vehicle'] =$vehicles;
				}

				if($v['accommodation']){
					$accommodation_arr = explode(',',$v['accommodation']);
					$accommodations='';
					foreach ($accommodation_arr as &$ac) {
						$accommodations .= get_accommodation($ac).' ';
					}
					$v['accommodation'] =$accommodations;
				}
				
			}
		/********************************************************************/
        $event_content['user'] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $event_content['uid']);
        $this->assign('content', $event_content);
		$this->assign('content_arr',$content_arr);
		$this->assign('insurance_string',$insurance_string);
		$this->assign('url',$url);
        $this->setTitle('编辑活动'.'——活动');
        $this->setKeywords('编辑'.',活动');
        $this->display();
    }
	/*禁用或启用活动*/
	public function up_event($id = 0,$status = 0){
		if($id ==""){
			$this->error('未知的活动!');
		}
		$data['status'] = $status;
		if($status == 1){
			$str = '启用成功！';
			$str1 = '启用失败！';			
		}else{
			$str = '禁用成功！';
			$str1 = '禁用失败！';
			$data['is_recommend'] = 0;
		}
		$rs = D('Event')->where(array('id'=>$id,'siteid'=>SITEID))->save($data);
		if($rs){
			$this->success($str);
		}else{
			$this->error($str1);
		}
	}
	public function line_type(){
		 $line=D('event_type')->where("siteid=".SITEID)->select();
		 $this->assign('line_type',$line);
		 $this->display();
	}
	/*添加线路类型*/
	public function line_type_add(){
	     if(IS_POST){
		    $title=$_POST['title'];
			$sort=$_POST['sort'];
			if($title=='') $this->error('线路类型不能为空');
			$event_types=D('event_type')->where('siteid='.SITEID)->select();
			
			if(count($event_types)<3){
			    $data['title']=$title;
				$data['sort']=$sort;
			    $data['siteid']=SITEID;
				$data['status']=1;
				$data['display']=1;
				$data['create_time']=time();
			    $event_add=D('event_type')->data($data)->add();
				if($event_add){
				   $this->success('添加成功','refresh');
				 }else{
				   $this->error('添加失败');
				 }
			
			}else{
			   $this->error('最多可以添加3条数据');
			
			}
		 
		 }else{
		 
		    $this->display();
		 }
	
	
	}
	/*是否删除*/
	public function line_type_del(){
	     $data['status']=$_POST['status'];
		 $id=$_POST['id'];
		 $event_find=D('event')->where("type_id=".$id." and siteid=".SITEID)->select();
		 if($event_find){
		    $this->error('该类别下有相关线路，无法删除');
		 }else{
		     $event_del=D('event_type')->where('id='.$id)->delete();
			 if($event_del){
			    $this->success('删除成功','refresh');
			  }else{
			    $this->error('删除失败！');
			  }
		}
		
	}
	/*修改公告*/
	public function edit_sign_notice(){
	      
		  $sign_notice = get_webinfo('sign_notice');
		  
		  $this->assign('sign_notice',$sign_notice);
		  $this->assign('list',$list);
	      $this->display();
	}
	/*执行修改*/
	public function do_edit_sign_notice($sign_notice){
	       
		  if(IS_POST){
		     $data['sign_notice']=$sign_notice;
			 $docus=D('websit')->where("siteid = ".SITEID)->save($data);
			 if($docus){
			    $this->success('更改成功',U('Websit/Event/index'));
			  }else{
			    $this->error('未更改数据');
			 }
		  
		  }
	
	}
	public function doPost($insurance = 0,$price_text = '',$price_type = 0,$frontmoney = 0,$detailadd = '',$paytype = 0,$id = 0,$cover_id = 0, $title = '',$price = '', $minpeople = '',$maxpeople = '',$explain = '',$travel_point = '',$pay_info = '',$attention = '',$tag = '')
    {
	
        if (!is_login()) {
            //$this->error('请登录后再发布活动。');
			$this->redirect('Home/User/login');
        }
		if(empty($tag)){
			$this->error('请添加活动标签。');
		}
		if($maxpeople != '' && $maxpeople != 0){
			if(is_numeric($maxpeople) == ''){
				$this->error('队员上限必须为数字！');
			}
			if($maxpeople < $minpeople){
				$this->error('最低人数不能大于队员上限');
			}
		}
        if (!$cover_id) {
            $this->error('请上传封面。');
        }
        if (trim(op_t($title)) == '') {
            $this->error('请输入标题。');
        }
        if (trim(op_h($explain)) == '') {
            $this->error('请输入行程安排。');
        }
		if (trim(op_h($travel_point)) == '') {
            $this->error('请输入线路亮点。');
        }
		 if (trim(op_h($pay_info)) == '') {
            $this->error('请输入费用说明。');
        }
		 if (trim(op_h($attention)) == '') {
            $this->error('请输入注意事项。');
        }		
        $content = D('Event')->create();
		/******************************************************************/
		if($paytype == ''){
				$this->error('请选择支付方式！');
		}else{		
			switch($paytype){
				case 0;
					if($price_type == ''){
						$this->error('请选择活动起价方式！');
					}else{
						switch($price_type){
							case 1;
								if($price_text == '') $this->error('请输入活动起价文本');
								$content['price_text'] = $price_text;
								$content['price'] = 0;
								$content['frontmoney'] = 0;
							break;
							case 2;
								if(empty($price)){
									$this->error('请添加活动起价。');
								}else{
									if(is_numeric($price) == '') $this->error('活动价格必须为纯数字');
									if($price < 0.01) $this->error('活动价格不能少于 ￥0.01 ！');
								} 
								$content['price'] = $price;
								$content['price_text'] = '';
								$content['frontmoney'] = 0;
							break;
						}				
					}
												
				break;
				case 1;
					if($price_type == ''){
						$this->error('请选择活动起价方式！');
					}else{
						switch($price_type){
							case 1;
								if($price_text == '') $this->error('请输入活动起价文本');
								$content['price_text'] = $price_text;
								$content['price'] = 0;
								$content['frontmoney'] = 0;
							break;
							case 2;
								if(empty($price)){
									$this->error('请输入活动起价金额！');
								}else{								
									if(is_numeric($price) == '') $this->error('活动价格必须为纯数字');
									if($price < 0.01) $this->error('活动价格不能少于 ￥0.01 ！');
									if(empty($frontmoney)) $this->error('请输入活动定金！');
									if(is_numeric($frontmoney) == '') $this->error('活动定金必须为纯数字');
									if($frontmoney < 0.01) $this->error('活动定金不能少于 ￥0.01 ！');
									if($price <= $frontmoney) $this->error('活动价格必须大于定金');
									$content['price_text'] = '';
									$content['price'] = $price;
									$content['frontmoney'] = $frontmoney;
								}
							break;
						}
					}				
				break;
				case 2;					
					if($price_type == ''){
						$this->error('请选择活动起价方式！');
					}else{
						switch($price_type){
							case 1;
								if($price_text == '') $this->error('请输入活动起价文本');
								$content['price_text'] = $price_text;
								$content['frontmoney'] = 0;
								$content['price'] = 0;
							break;
							case 2;								
								$content['price'] = 0;
								$content['frontmoney'] = 0;	
								$content['price_text'] = '';
							break;
						}
					}
				break;
			}
		}
		/******************************************************************/
		if($detailadd == '') $this->error('请填写具体地址！');
		$cityparam['begincity']['province'] = $_POST['begincity_province'];
		$cityparam['begincity']['city'] = $_POST['begincity_city'];
		$cityparam['begincity']['district'] = $_POST['begincity_district'];
		
		
		$cityparam['finalcity']['province'] = $_POST['finalcity_province'];	
		$cityparam['finalcity']['city'] = $_POST['finalcity_city'];	
		$cityparam['finalcity']['district'] = $_POST['finalcity_district'];	
		$content['begincity'] = set_city($cityparam['begincity']);
		$content['finalcity'] = set_city($cityparam['finalcity']);
        $content['explain'] = op_h($content['explain']);
        $content['title'] = op_t($content['title']);
		$content['status'] = 0;
		$content['detailadd'] = trim(op_t($detailadd));
		$content['siteid'] = SITEID;
		$tag = implode(',',$_POST['tag']);
		$content['tag'] = $tag;


		if (!$content['begincity']) {
            $this->error('请完善集合地点。');
        }
		if (!$content['finalcity']) {
            $this->error('请完善目的地点。');
        }

		
        if ($id) {
            $content_temp = D('Event')->find($id);
			$status = $content_temp['status'];
            if (!is_administrator(is_login())) { //不是管理员则进行检测
                if ($content_temp['uid'] != is_login() || !checked_admin(is_login()) || !checked_vip(is_login())) {
                    $this->error('小样儿，可别学坏。别以为改一下页面元素就能越权操作。');
                }
            }
            $content['uid'] = $content_temp['uid']; //权限矫正，防止被改为管理员
			$content['status'] = $status;
			$content['update_time'] = time();
            $rs = D('Event')->where(array('id'=>$id))->save($content);

            /*$postUrl = "http://$_SERVER[HTTP_HOST]" . U('Event/Index/detail', array('id' => $id));
            $weiboApi = new WeiboApi();
            $weiboApi->resetLastSendTime();
            $weiboApi->sendWeibo("我修改了活动【" . $title . "】：" . $postUrl);*/


            if ($rs) {
				$_SESSION['event']['fid'] = $rs;
				/*if($str != 'mypublic.html'){
					$this->success('编辑成功', U('Usercenter/Websit/content',array('status'=>6)));					
				}else{
					 $this->success('编辑成功', U('Usercenter/Config/mypublic'));
				}*/
				$this->success('编辑成功',U('Websit/Event/index'));
            } else {
                $this->error('编辑失败。');
            }
        } else {
           /* if (C('NEED_VERIFY') && !is_administrator()) //需要审核且不是管理员
            {
                $content['status'] = 0;
                $tip = '但需管理员审核通过后才会显示在列表中，请耐心等待。';
                $user = query_user(array('username', 'nickname'), is_login());
                D('Common/Message')->sendMessage(C('USER_ADMINISTRATOR'), "{$user['nickname']}发布了一个活动，请到后台审核。", $title = '活动发布提醒', U('Admin/Event/verify'), is_login(), 2);
            }*/
			$content['create_time'] = time();
			$content['status'] = 1;	
			$content['uid'] = is_login();		
            $rs = D('Event')->add($content);


//同步到微博
           /*$postUrl = "http://$_SERVER[HTTP_HOST]" . U('Event/Index/detail', array('id' => $rs));
            $weiboApi = new WeiboApi();
            $weiboApi->resetLastSendTime();
            $weiboApi->sendWeibo("我发布了一个新的活动【" . $title . "】：" . $postUrl);*/
            if ($rs) {
				/*if($url == 's=/Usercenter/Websit/content/status/6.html'){
					$this->success('发布成功,请完善排期资料！' . $tip, U('Usercenter/Websit/event_schedule',array('id'=>$rs)));					
				}else{
					 $this->success('发布成功,请完善排期资料！' . $tip, U('Usercenter/Config/event_schedule',array('id'=>$rs)));
				}*/
				
				$qrcode_url = set_qrcode(array('id'=>$rs),'event');
				if($qrcode_url){
					$qrcode_data['siteid'] =  SITEID;
					$qrcode_data['uid'] =  is_login();
					$qrcode_data['linkid'] =  $rs;
					$qrcode_data['types'] =  'event';
					$qrcode_data['url'] =  $qrcode_url;
					$qrcode_data['create_time'] =  time();
					D('qrcode')->add($qrcode_data);
				}
				$this->success('发布成功,请完善排期资料！' . $tip, U('Websit/Event/index'));
            } else {
				$this->error('发布失败。');
            }
        }
    }
	/*修改线路分类*/
	public function line_type_edit(){
	     if(IS_POST){
		    $id=$_POST['id'];
			$title=trim($_POST['title']);
			$sort=$_POST['sort'];
			
			if($title=='') $this->error('请填写分类');
			$data['title']=$title;
			$data['sort']=$sort;
			$data['update_time']=time();
			$event_save=D('event_type')->where("id = $id")->save($data);
			if($event_save){
			   $this->success('修改成功','refresh');
			}else{
			   $this->error('修改失败');
			}
		 }else{
		   $id=$_GET['id'];
		   $event_type=D('event_type')->where('id='.$id)->find();
		   $this->assign('event_type',$event_type);
		   $this->display();
		 }
	
	}
	/*增加排期*/
	public function event_schedule_add($id=0,$eventid=0)
  {
		$data['msg'] = '';
		$data['status'] = true;
		$rs = D('Event')->where(array('id' => $eventid,'siteid'=>SITEID))->find();
		if(!$rs){
			$data['msg'] = '活动不存在或已被删除';
			$data['status'] = false;
		}else{
			if($rs['uid']!=is_login()){
				$data['msg'] = '你的权限不足';
				$data['status'] = false;
			}
		}
		/********************************************************/
		$order = "update_time desc";
		$add = D('event')->where(array('uid'=>is_login(),'siteid'=>SITEID))->order($order)->field(array('detailadd','begincity','attention'))->find();
		$begincity = get_citys($add['begincity']);
		unset($add['begincity']);
		$add['begincity']['district'] = $begincity['district'];
		$add['begincity']['city'] = $begincity['city'];
		$add['begincity']['province'] = $begincity['province'];
		if($add != ''){
			$this->assign('add',$add);
		}
		/*********************************************************/
		$map['status'] = 1;
		$map['is_use'] = 2;
		//$map['checked'] = 1;
		$map['siteid'] = SITEID;
		$member = D('member')->where($map)->select();
		if($member){
			foreach ($member as $ku=> &$u) {
				$get_leader[$u['uid']] =$u['nickname'];
			}
			$this->assign('get_leader', $get_leader);
		}
		$this->assign('event', $rs);
		$this->assign('data_msg', $data);
        $this->display();
    }
	/*增加排期*/
	/*排期的编辑*/
	public function event_schedule_edit($id=0,$eventid=0)
  {
		$data['msg'] = '';
		$data['status'] = true;
		$rs = D('Event')->where(array('id' => $eventid,'siteid'=>SITEID))->find();
		if(!$rs){
			$data['msg'] = '活动不存在或已被删除';
			$data['status'] = false;
		}else{
			if($rs['uid']!=is_login()){
				$data['msg'] = '你的权限不足';
				$data['status'] = false;
			}else{
				$tiem_arr = D('event_calendar_time')->where(array('eventid' => $eventid,'id' => $id,'siteid'=>SITEID))->find();
			}
		}
		$map['status'] = 1;
		$map['is_use'] = 2;
		//$map['checked'] = 1;
		$map['siteid'] = SITEID;
		$member = D('member')->where($map)->select();
		if($member){
			foreach ($member as $ku=> &$u) {
				$get_leader[$u['uid']] =$u['nickname'];
			}
			$this->assign('get_leader', $get_leader);
		}
		if(!empty($tiem_arr['begincity']) && !empty($tiem_arr['detailadd'])){
			$begincity = get_citys($tiem_arr['begincity']);
			unset($tiem_arr['begincity']);		
			$tiem_arr['begincity']['community'] = $begincity['community'];
			$tiem_arr['begincity']['district'] = $begincity['district'];
			$tiem_arr['begincity']['city'] = $begincity['city'];
			$tiem_arr['begincity']['province'] = $begincity['province'];
		}else{
			$begincity = get_citys($rs['begincity']);
			$tiem_arr['begincity']['community'] = $begincity['community'];
			$tiem_arr['begincity']['district'] = $begincity['district'];
			$tiem_arr['begincity']['city'] = $begincity['city'];
			$tiem_arr['begincity']['province'] = $begincity['province'];
			$tiem_arr['detailadd'] = $rs['detailadd'];
		}
		
		$status = get_event_status();
		$this->assign('status',$status);
		$this->assign('event_content', $rs);
		$this->assign('data_msg', $data);
		$this->assign('content', $tiem_arr);
        $this->display();
    }
	/*
	 * 排期的隐藏或显示
	 */
	public function schedule_display($schedule_id = '',$display = ''){
			if($schedule_id == '' || $display == '' ){
				exit(json_encode(array('status'=>false,'msg'=>'参数错误！')));
			}
			$data['display'] = $display;
			if($display == 1){
				$msg = '显示成功！';
			}else{
				$msg = '隐藏成功！';
			}
			$rs = D('event_calendar_time')->where(array('id'=>$schedule_id,'siteid'=>SITEID))->save($data);
			if($rs){
				exit(json_encode(array('status'=>true,'msg'=>$msg)));
			}else{
				exit(json_encode(array('status'=>false,'msg'=>'操作失败！')));
			}
	}
	/*增加排期*/

    public function do_event_schedule_add($eventid='',$card_use = '',$price='',$starttime='',$endtime='',$days='',$minpeople='',$maxpeople='',$paytype = '',$deposit = '',$detailadd='')
    {
			$insure_info = D('insurance')->where(array('siteid'=>SITEID,'status'=>1))->count();
			if($insure_info == 0){
				$this->error('请先添加活动保险再做操作！');
			}
			if(!$eventid) $this->error('参数错误！');
			$event_content = D('Event')->where(array('id' => $eventid,'siteid'=>SITEID))->find();
			if(!$event_content) $this->error('活动不存在或已被删除！');
			//if($event_content['uid']!=is_login()) $this->error('您的权限不足！');
			
			if(!$starttime) $this->error('请输入出发时间！');
			$schedule_data = D('event_calendar_time')->where(array('eventid' => $eventid,'uid' => $event_content['uid'],'starttime' => $starttime,'siteid'=>SITEID))->find();
			if($schedule_data) $this->error('当前排期已经存在，请添加其他日期！');
			
			if(!$endtime) $this->error('请输入截止时间！');
			if(strtotime($endtime) > strtotime($starttime)) $this->error('截止日期不能大于出发时间！');
			
			
			if(!$days) $this->error('请输入排期天数！');
			if(is_numeric($days) == '') $this->error('排期天数必须为纯数字');
			
			if(!$minpeople) $this->error('请输入最低人数！');
			if(is_numeric($minpeople) == '') $this->error('最低人数必须为纯数字');
			$maxpeople = intval($maxpeople);
			if($maxpeople != '' || $maxpeople != 0){
				if(is_numeric($maxpeople) == '') $this->error('队员上限必须为纯数字');
				if($minpeople > $maxpeople) $this->error('队员上限不能低于最低人数！');
			}
			$ca_data = D('event_calendar_time')->create();		
			if($paytype == ''){
				$this->error('请选择支付方式！');
			}else{
				switch($paytype){
					case 0;
						if(empty($price)){
							$this->error('请输入日程价格！');
						}else{
							if(is_numeric($price) == '') $this->error('排期价格必须为纯数字');
							if($price < 0.01) $this->error('排期价格不能少于 ￥0.01 ！');
						} 
						$ca_data['deposit'] = 0;						
					break;
					case 1;
						if(empty($price)){
							$this->error('请输入日程价格！');
						}else{
							if(is_numeric($price) == '') $this->error('排期价格必须为纯数字');
							if($price < 0.01) $this->error('排期价格不能少于 ￥0.01 ！');
							if(is_numeric($deposit) == '') $this->error('排期定金必须为纯数字');
							if($deposit < 0.01) $this->error('排期定金不能少于 ￥0.01 ！');
							if($price <= $deposit) $this->error('排期价格必须大于定金');
						}
					break;
					case 2;
						$ca_data['price'] = 0;
						$ca_data['deposit'] = 0;
					break;
				}
			}
			if($detailadd == '') $this->error('请填写具体地址！');
			
			$cityparam['begincity']['province'] = $_POST['begincity_province'];
			$cityparam['begincity']['city'] = $_POST['begincity_city'];
			$cityparam['begincity']['district'] = $_POST['begincity_district'];							
			$ca_data['begincity'] = set_city($cityparam['begincity']);
			if (!$ca_data['begincity']) {
				$this->error('请完善集合地点。');
			}
			/*if(!$_POST['leader'][0]){
				$this->error('最少要选择一个领队！');
			}else{
				$ca_data['leader'] = implode(",",$_POST['leader']);
			}*/
			$ca_data['leader'] = implode(",",$_POST['leader']);
			if(!$_POST['vehicle'][0]){
				$this->error('请选择交通工具！');
			}else{
				$ca_data['vehicle'] = implode(",",$_POST['vehicle']);
			}
			
			if(!$_POST['accommodation'][0]){
				$this->error('请选择住宿条件！');
			}else{
				$ca_data['accommodation'] = implode(",",$_POST['accommodation']);
			}
			$ca_data['siteid'] = SITEID;	
			$ca_data['overtime'] = date('Y-m-d',strtotime($starttime) +(86400*($days-1)));
			$ca_data['uid'] = is_login();
			$ca_data['status'] = 1;
			$ca_data['time'] = time();	
			$ev_ca = D('event_calendar_time')->add($ca_data);
			if($ev_ca){
				$this->success('添加成功!','refresh');
			}else{
				$this->error('添加失败！');
			}
    }
	/*修改排期**/
	//排期日期的修改
   	//排期日期的修改
    public function do_event_schedule_edit($detailadd = '',$card_use = '',$status = 0,$id = 0,$eventid='',$price='',$starttime='',$endtime='',$days='',$minpeople='',$maxpeople='',$days_left = '',$paytype = '',$deposit = '')
    {
		if(!$id || !$eventid) $this->error('参数错误！');
		
		$event_content = D('Event')->where(array('id' => $eventid,'siteid'=>SITEID))->find();
		if(!$event_content) $this->error('活动不存在或已被删除！');
		//if($event_content['uid']!=is_login()) $this->error('您的权限不足！');
				
		if(!$starttime) $this->error('请输入出发时间！');
		$schedule_data = D('event_calendar_time')->where(array('eventid' => $eventid,'uid' => $event_content['uid'],'id' => $id,'siteid'=>SITEID))->find();
		if(!$schedule_data) $this->error('排期已经被删除！');
		if(!$endtime) $this->error('请输入截止时间！');
		if(strtotime($endtime) > strtotime($starttime)) $this->error('截止日期不能大于出发时间！');
		
		if(!$days) $this->error('请输入排期天数！');
		if(is_numeric($days) == '') $this->error('排期天数必须为纯数字');
		
		if(!$minpeople) $this->error('请输入最低人数！');
		if(is_numeric($minpeople) == '') $this->error('最低人数必须为纯数字');
		$maxpeople = intval($maxpeople);
		if($maxpeople != '' || $maxpeople != 0){
			if(is_numeric($maxpeople) == '') $this->error('队员上限必须为纯数字');
			if($minpeople > $maxpeople) $this->error('队员上限不能低于最低人数！');	
		}
		$ca_data = D('event_calendar_time')->create();	
		if($paytype == ''){
			$this->error('请选择支付方式！');
		}else{
			switch($paytype){
				case 0;
					if(empty($price)){
						$this->error('请输入日程价格！');
					}else{
						if(is_numeric($price) == '') $this->error('排期价格必须为纯数字');
						if($price < 0.01) $this->error('排期价格不能少于 ￥0.01 ！');
					} 
					$ca_data['deposit'] = 0;						
				break;
				case 1;
					if(empty($price)){
						$this->error('请输入日程价格！');
					}else{
						if(is_numeric($price) == '') $this->error('排期价格必须为纯数字');
						if($price < 0.01) $this->error('排期价格不能少于 ￥0.01 ！');
						if(is_numeric($deposit) == '') $this->error('排期定金必须为纯数字');
						if($deposit < 0.01) $this->error('排期定金不能少于 ￥0.01 ！');
						if($price <= $deposit) $this->error('排期价格必须大于定金');
					}
				break;
				case 2;
					$ca_data['price'] = 0;
					$ca_data['deposit'] = 0;
				break;
			}
		}
		if($status != ''){
			$ca_data['status'] = $status; 
		}else{
			$ca_data['status'] = 1; 
		}
		/*if(!$_POST['leader'][0]){
			$this->error('最少要选择一个领队！');
		}else{
			$ca_data['leader'] = implode(",",$_POST['leader']);
		}*/
		if($detailadd == '') $this->error('请填写具体地址！');
			
		$cityparam['begincity']['province'] = $_POST['begincity_province'];
		$cityparam['begincity']['city'] = $_POST['begincity_city'];
		$cityparam['begincity']['district'] = $_POST['begincity_district'];							
		$ca_data['begincity'] = set_city($cityparam['begincity']);
		if (!$ca_data['begincity']) {
			$this->error('请完善集合地点。');
		}
		$ca_data['leader'] = implode(",",$_POST['leader']);
		
	
		if(!$_POST['vehicle'][0]){
			$this->error('请选择交通工具！');
		}else{
			$ca_data['vehicle'] = implode(",",$_POST['vehicle']);
		}
		if($days_left != ''){
			$ca_data['days_left'] = $days_left;
		}
		if(!$_POST['accommodation'][0]){
			$this->error('请选择住宿条件！');
		}else{
			$ca_data['accommodation'] = implode(",",$_POST['accommodation']);
		}
		$ca_data['overtime'] = date('Y-m-d',strtotime($starttime) +(86400*($days-1)));
		$ev_ca = D('event_calendar_time')->where(array('id'=>$_POST['id'],'siteid'=>SITEID))->save($ca_data);
		if($ev_ca){				
			$this->success('修改成功!', 'refresh');
		}else{
			$this->error('修改失败！');
		}
    }
	/*排期删除*/
	public function event_schedule_del($id=0)
	{
		if(!$id) exit('参数错误!');
		
		$data = D('event_calendar_time')->where(array('id'=>$id,'siteid'=>SITEID))->find();
		
		if(!$data) exit('排期不存在');
		
		if($data['uid'] !=is_login() && !checked_admin(is_login())) exit('权限不足');
		
		
		$ca_data['status'] = '-1';
		$updata = D('event_calendar_time')->where(array('id'=>$id,'siteid'=>SITEID))->save($ca_data);
		if($updata){
			echo 1;
		}else{
			exit('删除失败!');
		}
	}
	/*添加保险*/
	public function insurance_doAdd($name,$sum_insured,$price){
        if(IS_POST){
		    $name=op_t($name);
		    $sum_insured=op_t($sum_insured);
		    $price=op_t($price);
		    if($name=='') exit(json_encode(array('status'=>false,'msg'=>'请填写保险名称')));
		    if($sum_insured=='') exit(json_encode(array('status'=>false,'msg'=>'请填写正确的保额哦')));
		    if($price=='') exit(json_encode(array('status'=>false,'msg'=>'请填写正确的保险价格哦')));
			
			$data['name']=$name;
			$data['sum_insured']=$sum_insured;
			$data['price']=$price;
			$data['time']=time();
			$data['siteid']=SITEID;
			
		    D('insurance')->create($data);
			$list=D('insurance')->add();
			$string = get_insurance_select();	
				if($list){
					//$this->success('添加成功','refresh');
					exit(json_encode(array('status'=>true,'string'=>$string)));
				}else{
					exit(json_encode(array('status'=>false,'msg'=>'添加失败')));
				}
	    }
	}
}  
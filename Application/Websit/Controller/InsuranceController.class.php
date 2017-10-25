<?php

namespace Websit\Controller;

use Think\Controller;

class InsuranceController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize();    
	}
	/*保险管理--2014-10-11 pm**/
	public function index(){
	     $status=I('status');
			 switch($status){
				case 0:
					$list=D('insurance')->where("siteid=".SITEID)->select();
					$this->assign('insurance',$list);
				break;
			  }
	 $this->assign('user',$this->userdata);
	 $this->assign('status',$status);
	 $this->display();
	
	}
	/*修改保险信息*/
	public function insurance_edit(){
	       $list=D('insurance')->where("id=".I('id'))->find();
		   $this->assign('data',$list);
		   $this->display();
	}
	/*执行修改保险信息*/
	public function insurance_doEdit($id,$name,$sum_insured,$price){
	    if(IS_POST){
		    $name=op_t($name);
		    $sum_insured=op_t($sum_insured);
		    $price=op_t($price);
			if($id=='')  $this->error('参数错误');
		    if($name=='') $this->error('请填写保险名称');
		    if($sum_insured=='') $this->error('请填写正确的保额哦');
		    if($price=='') $this->error('请填写正确的保额哦!');
			
			$data['name']=$name;
			$data['sum_insured']=$sum_insured;
			$data['price']=$price;
			
			$reds=D('insurance')->where("id=".$id)->save($data);
		          if($reds){
				      $this->success('更改成功','refresh');
				  }else{
				      $this->error('更改失败');
				  }
		}
		   
		   
	}
   /*是否禁用保险*/
   public function insurance_disable(){
          $data['status']=I('status');
		  $reds=D('insurance')->where("id=".I('id'))->save($data);
			  
			  if($reds){
			      $this->success('操作成功');
			  }else{
			      $this->error('操作失败');
			  }
   
   }
   /*添加保险*/
	public function insurance_doAdd($name,$sum_insured,$price){
        if(IS_POST){
		    $name=op_t($name);
		    $sum_insured=op_t($sum_insured);
		    $price=op_t($price);
		    if($name=='') $this->error('请填写保险名称');
		    if($sum_insured=='') $this->error('请填写正确的保额哦');
		    if($price=='') $this->error('请填写正确的保额哦!');
			
			$data['name']=$name;
			$data['sum_insured']=$sum_insured;
			$data['price']=$price;
			$data['time']=time();
			$data['siteid']=SITEID;
			
		    D('insurance')->create($data);
			$list=D('insurance')->add();
				
				if($list){
					$this->success('添加成功','refresh');
				}else{
					$this->error('添加失败');
				}
	    }
	}
}  
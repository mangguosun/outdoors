<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;

/*保险管理-2015-1-16*/
class InsuranceController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize();
		$this->insurance = D('insurance');
    }
	

    public function index(){
   		//读取列表
        $map = array('status' => array('egt',0),'siteid'=>SITEID);
        $list = $this->insurance->where($map)->select();
		foreach($list as &$v){
			$v['new_status'] = $v['status']?'已启用':'已禁用';
			$v['distime'] = date("Y-m-d H:i",$v['time']);
		}

		$this->assign('datainfo',$list);
        $this->display();
    }
    /*添加和更改保险信息*/
	public function insurance_edit($id=0,$name='',$sum_insured='',$price=0){
		$isEdit = $id ? 1 : 0; 
		if(IS_POST){ 
			$name=op_t($name);
			$sum_insured=op_t($sum_insured);
		    $price=op_t($price);
		    if($name=='') $this->error('请填写保险名称');
		    if($sum_insured=='') $this->error('请填写正确的保额哦');
		    if($price=='') $this->error('请填写正确的保险价格哦!');
			if(!preg_match('/^[0-9]+(.[0-9]{1,2})?$/',$price)){
				$this->error('请填写正确的保险价格哦');
			}
		    $data['name']=$name;
			$data['sum_insured']=$sum_insured;
			$data['price']=$price;
			$data['siteid']=SITEID;
			if($isEdit){
				//更改
				if($id=='')  $this->error('参数错误');
				$reds=$this->insurance->where("id=".$id)->save($data);
		        if($reds){
				    $this->success('更改成功',U('Insurance/index'));
				}else{
				    $this->error('更改失败');
				}
			}else{ 
				$data['time']=time();
			    $this->insurance->create($data);
			    $res=$this->insurance->add($data);
			    if($res){ 
			    	$this->success('添加成功',U('Insurance/index'));
			    }else{ 
			    	$this->error('添加失败');
			    }
			}

		}else{ 

            if($isEdit){
            	$insur_map['siteid'] = SITEID;
            	$insur_map['id']=$id;
                $insur_data = $this->insurance->where($insur_map)->find();
         
   			}
			$insur_data['page_title'] = $isEdit ? '修改保险' : '添加保险';
			$this->assign('datainfo',$insur_data);
			$this->display();
		}
	}

}	
	
	

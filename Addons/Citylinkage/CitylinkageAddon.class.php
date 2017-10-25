<?php
// +----------------------------------------------------------------------
// | i友街 [ 新生代贵州网购社区 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2014 http://www.iyo9.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: i友街 <iyo9@iyo9.com> <http://www.iyo9.com>
// +----------------------------------------------------------------------
// 
namespace Addons\Citylinkage;
use Common\Controller\Addon;

/**
 * 中国省市区三级联动插件
 * @author i友街
 */

    class CitylinkageAddon extends Addon{

        public $info = array(
            'name'=>'ChinaCity',
            'title'=>'中国省市区三级联动',
            'description'=>'每个系统都需要的一个中国省市区三级联动插件。',
            'status'=>1,
            'author'=>'i友街',
            'version'=>'2.0'
        );
        public function install(){
            return true;
        }

        public function uninstall(){
            return true;
        }
        //实现的J_China_City钩子方法
        public function Citylinkage($param){
			
			if(!$param['find']) $param['find'] = 'find';
			if(!$param['linkageid']) $param['linkageid'] = 0;
			if(!$param['level']) $param['level'] = 1;
			if($param['level']>= 1 ){
				$data['province']  = $this->_list(array('level'=>1,'upid'=>0));
			}
			if($param['linkageid']){
				
				$citys=D('district')->where(array('id'=>$param['linkageid'])) ->field('id,name,level,upid')->find();
				if($citys){
					$linkage_data = $this->get_linkage($citys);
					//$data['province'] = $linkage_data['province'];
					//$data['city'] = $linkage_data['city'];
					//$data['district'] = $linkage_data['district'];
					//$data['community'] = $linkage_data['community'];
					
					if($param['level']>= 2 ){
						if($linkage_data['province']){
							$data['city']  = $this->_list(array('level'=>2,'upid'=>$linkage_data['province']));
						}	
					}
					if($param['level']>= 3 ){
						if($linkage_data['city']){
							$data['district']  = $this->_list(array('level'=>3,'upid'=>$linkage_data['city']));
						}	
					}
					if($param['level']>= 4 ){
						if($linkage_data['district']){
							$data['community']  = $this->_list(array('level'=>4,'upid'=>$linkage_data['district']));
						}	
					}
					$data['on']  = $linkage_data;
					$data['linkageid']  = $param['linkageid'];
				}
			}
			$this->assign('linkage_info', $data);
            $this->assign('param', $param);
            $this->display('citylinkage');
        }
		
		public function _list($map){
			$order = 'id ASC';
			$data = D('district')->where($map)->order($order)->getfield('id,name');
			return $data;
		}
			
        public function get_linkage($datainfo){
           
		   
		   	if($datainfo){
					switch ($datainfo['level'])
					{
						case 1:
							$data['community'] = 0;
							$data['district'] = 0;
							$data['city'] = 0;
							$data['province'] = $datainfo['id'];
						  break;  
						case 2:
							$data['community'] = 0;
							$data['district'] = 0;
							$data['city'] = $datainfo['id'];
							$district_data= D('district')->where("id={$datainfo['upid']}") ->find();
							$data['province'] = $district_data['id'];
						  break;
						case 3:
							$data['community'] = 0;
							$data['district'] = $datainfo['id'];
							$district_data= D('district')->where("id={$datainfo['upid']}") ->find();
							$data['city'] = $district_data['id'];
							$district_city= D('district')->where("id={$district_data['upid']}") ->find();
							$data['province'] = $district_city['id'];
						  break;
						case 4:
							$data['community'] = $datainfo['id'];
							$district_data= D('district')->where("id={$datainfo['upid']}") ->find();
							$data['district'] = $district_data['id'];
							$district_city= D('district')->where("id={$district_data['upid']}") ->find();
							$data['city'] = $district_city['id'];
							$district_province= D('district')->where("id={$district_city['upid']}") ->find();
							$data['province'] = $district_province['id'];
						  break;
						default:
						  break;
					}
					return $data; 
			}else{
				return false;
			}	
        }	
		
		


    }
<?php

namespace Manage\Controller;
class PublicController extends BaseController
{
    public function _initialize()
    {
        parent::_initialize();
		
	}
	public function changeStatus($method = null){
		$id = array_unique((array)I('id', 0));
		$id = is_array($id) ? implode(',', $id) : $id;
	    if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}
		$map['id'] = array('in', $id);
		 switch (strtolower($method)) { 
		  	//--广告图片--
            case 'forbidadvs': //禁用
			    $this->forbid('Advs', $map);
                break;
            case 'resumeadvs': //启用
			   $this->resume('Advs', $map);
                break;
            case 'deleteadvs': //删除
                $this->delete('Advs', $map);
                break;

            //---social帐号
			case 'forbidshare': 
			    $this->forbid('Share', $map);
                break;
            case 'resumeshare':
			   $this->resume('Share', $map);
                break;
            case 'deleteshare':
                $this->delete('Share', $map);
                break;

            //----活动
			case 'forbidevent': //禁用
			    $this->forbid('Event', $map);
                break;
            case 'resumeevent': //启用
			   $this->resume('Event', $map);
                break;
            case 'deleteevent': //删除
                $this->delete('Event', $map);
                break;

            //----活动类型
            case 'forbideventtype': //禁用
			    $this->forbid('event_type', $map);
                break;
            case 'resumeeventtype': //启用
			   $this->resume('event_type', $map);
                break;
            case 'deleteeventtype': //删除
                $this->delete('event_type', $map);
                break;    

			 //---商品---
			 case 'forbidshop': //禁用
			    $this->forbid('Shop', $map);
                break;
            case 'resumeshop': //启用
			$find_site_relation	=	D('shop_distribute_site_relation')->where('seller_id='.SITEID.' and distribute_relation_status=1 and status=1')->find();
				if($find_site_relation)	$this->error('已为全站分销商，无法进行此操作');
			   $this->resume('Shop', $map);
                break;
            case 'deleteshop': //删除
                $this->delete('Shop', $map);
                break;
			case 'deleteshopcate': //删除
                $this->delete('Shop_category', $map);
                break;

            //----定制订单
           	case 'forbidcustom': //禁用
			    $this->forbid('event_tailor', $map);
                break;
            case 'resumecustom': //启用
			   	$this->resume('event_tailor', $map);
                break;
            case 'deletecustom': //删除
                $this->delete('event_tailor', $map);
                break;   

            //----评论（活动、故事、公告）
           	case 'forbidcomment': //禁用
			    $this->forbid('local_comment', $map);
                break;
            case 'resumecomment': //启用
			   	$this->resume('local_comment', $map);
                break;
            case 'deletecomment': //删除
                $this->delete('local_comment', $map);
                break;

            //-----服务企业
            case 'forbidmemberservice': //禁用
				$this->forbid('member_service', $map);
                break;
            case 'resumememberservice': //启用
			   	$this->resume('member_service', $map);
                break;
            case 'deletememberservice': //删除
                $this->delete('member_service', $map);
                break;

            //----保险
            case 'forbidinsurance': //禁用
				$this->forbid('insurance', $map);
                break;
            case 'resumeinsurance': //启用
			   	$this->resume('insurance', $map);
                break;
            case 'deleteinsurance': //删除
                $this->delete('insurance', $map);
                break;
            //--卡券

            //----公告
            case 'forbiddocument': //禁用
				$this->forbid('document', $map);
                break;
            case 'resumedocument': //启用
			   	$this->resume('document', $map);
                break;
            case 'deletedocument': //删除
                $this->delete('document', $map);
                break;  

            //----公告类型
            case 'forbiddocumenttype': //禁用
				$this->forbid('category', $map);
                break;
            case 'resumedocumenttype': //启用
			   	$this->resume('category', $map);
                break;
            case 'deletedocumenttype': //删除
                $this->delete('category', $map);
                break; 

           	//----故事列表
            case 'forbidissuecontent': //禁用
				$this->forbid('issue_content', $map);
                break;
            case 'resumeissuecontent': //启用
			   	$this->resume('issue_content', $map);
                break;
            case 'deleteissuecontent': //删除
                $this->delete('issue_content', $map);
                break;     

           	//----故事分类
            case 'forbidissuetype': //禁用
				$this->forbid('issue', $map);
                break;
            case 'resumeissuetype': //启用
			   	$this->resume('issue', $map);
                break;
            case 'deleteissuetype': //删除
                $this->delete('issue', $map);
                break; 

            //----关于我们
            case 'forbidabout': //禁用
				$this->forbid('about', $map);
                break;
            case 'resumeabout': //启用
			   	$this->resume('about', $map);
                break;
            case 'deleteabout': //删除
                $this->delete('about', $map);
                break;

            //----视频管理
            case 'forbidwebsitvideo': //禁用
				$this->forbid('websit_video', $map);
                break;
            case 'resumewebsitvideo': //启用
			   	$this->resume('websit_video', $map);
                break;
            case 'deletewebsitvideo': //删除
                $this->delete('websit_video', $map);
                break;

            //----页脚导航
            case 'forbidfooternav': //禁用
				$this->forbid('enterprises', $map);
                break;
            case 'resumefooternav': //启用
			   	$this->resume('enterprises', $map);
                break;
            case 'deletefooternav': //删除
                $this->delete('enterprises', $map);
                break;

             //---QQ客服
            case 'forbidqqserver': //禁用
				$this->forbid('customer_service', $map);
                break;
            case 'resumeqqserver': //启用
			   	$this->resume('customer_service', $map);
                break;
            case 'deleteqqserver': //删除
                $this->delete('customer_service', $map);
                break;

            //顶部导航(系统) 
            case 'forbidtopnavsystem': //禁用
				$this->forbid('channel_websit', $map);
                break;
            case 'resumetopnavsystem': //启用
			   	$this->resume('channel_websit', $map);
                break;
            case 'deletetopnavsystem': //删除
                $this->delete('channel_websit', $map);
                break;
                
            //顶部导航(自定义) 
            case 'forbidtopnavcustom': //禁用
				$this->forbid('channel_websit_custom', $map);
                break;
            case 'resumetopnavcustom': //启用
			   	$this->resume('channel_websit_custom', $map);
                break;
            case 'deletetopnavcustom': //删除
                $this->delete('channel_websit_custom', $map);
                break;  

            //友情链接
            case 'forbidlinks': //禁用
				$this->forbid('links', $map);
                break;
            case 'resumelinks': //启用
			   	$this->resume('links', $map);
                break;
            case 'deletelinks': //删除
                $this->delete('links', $map);
                break;
            //顶部左侧导航
            case 'forbidleftnav': //禁用
				$this->forbid('websit_product_config', $map);
                break;
            case 'resumeleftnav': //启用
			   	$this->resume('websit_product_config', $map);
                break;
            case 'deleteleftnav': //删除
                $this->delete('websit_product_config', $map);
                break;
				
            //分类导航
            case 'forbidclassification': //禁用
				$this->forbid('shop_classification', $map);
                break;
            case 'resumeclassification': //启用
			   	$this->resume('shop_classification', $map);
                break;
            case 'deleteclassification': //删除
                $this->delete('shop_classification', $map);
                break;
			//品牌管理
            case 'forbidshopbrand': //禁用
				$this->forbid('shop_brand', $map);
                break;
            case 'resumeshopbrand': //启用
			   	$this->resume('shop_brand', $map);
                break;
            case 'deleteshopbrand': //删除
                $this->delete('shop_brand', $map);
                break;	
			
            //约伴管理
            case 'forbidpartner': //禁用
                $this->forbid('partner', $map);
                break;
            case 'resumepartner': //启用
                $this->resume('partner', $map);
                break;
            case 'deletepartner': //删除
                $this->delete('partner', $map);
                break;  
			//打卡管理
            case 'forbidmark': //禁用
                $this->forbid('mark', $map);
                break;
            case 'resumemark': //启用
                $this->resume('mark', $map);
                break;
            case 'deletemark': //删除
                $this->delete('mark', $map);
                break;  
				
            default:
                $this->error('参数非法');
        }
	}
	  /**
     * 恢复条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    protected function resume (  $model , $where = array() , $msg = array( 'success'=>'状态设置成功！', 'error'=>'状态设置失败！')){
        $data    =  array('status' => 1);
        $this->editRow(   $model , $data, $where, $msg);
    }
	  /**
     * 禁用条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的 where()方法的参数
     * @param array  $msg   执行正确和错误的消息,可以设置四个元素 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    protected function forbid ( $model , $where = array() , $msg = array( 'success'=>'状态设置成功！', 'error'=>'状态设置失败！')){
        $data    =  array('status' => 0);
        $this->editRow( $model , $data, $where, $msg);
    }
	
	  /**
     * 条目假删除
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    protected function delete ( $model , $where = array() , $msg = array( 'success'=>'删除成功！', 'error'=>'删除失败！')) {
        $data['status']         =   -1;
        $data['update_time']    =   NOW_TIME;
        $this->editRow(   $model , $data, $where, $msg);
    }

	
    /**
     * 设置一条或者多条数据的状态
     */
    public function setStatus($Model=CONTROLLER_NAME){

        $ids    =   I('request.ids');
        $status =   I('request.status');
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }

        $map['id'] = array('in',$ids);
        switch ($status){
            case -1 :
                $this->delete($Model, $map, array('success'=>'删除成功','error'=>'删除失败'));
                break;
            case 0  :
                $this->forbid($Model, $map, array('success'=>'禁用成功','error'=>'禁用失败'));
                break;
            case 1  :
                $this->resume($Model, $map, array('success'=>'启用成功','error'=>'启用失败'));
                break;
            default :
                $this->error('参数错误');
                break;
        }
    }	
	  /**
     * 对数据表中的单行或多行记录执行修改 GET参数id为数字或逗号分隔的数字
     *
     * @param string $model 模型名称,供M函数使用的参数
     * @param array  $data  修改的数据
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     *
     * @author 朱亚杰  <zhuyajie@topthink.net>
     */
    final protected function editRow ( $model ,$data, $where , $msg ){
        $id    = array_unique((array)I('id',0));
		$id    = is_array($id) ? implode(',',$id) : $id;
        $where = "id in($id)";
		
		$msg   = array_merge( array( 'success'=>'操作成功！', 'error'=>'操作失败！', 'url'=>'' ,'ajax'=>IS_AJAX) , (array)$msg );
        if( M($model)->where($where)->save($data)!==false ) {
			if($model=='Shop'){D('Common/shop')->clear_index_module_s();}
            if($model=='Event'){
                $eventid = is_array($id)?array($id):explode(',', $id);
                foreach ($eventid as $key => $value) {
                   D("Common/Event")->getEventDetail($value,$delS=true);
                }
            
            }
            $this->success($msg['success'],$msg['url'],$msg['ajax']);
        }else{
            $this->error($msg['error'],$msg['url'],$msg['ajax']);
        }
    }
	
	
	
}

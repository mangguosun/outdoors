<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM1:13
 */

namespace Official\Controller;

use Think\Controller;

class PublicController extends BaseController
{
    public function _initialize()
    {
        parent::_initialize();
        if (!is_login()) {
            //$this->error('请登录后再访问本页面。');
            $this->redirect('Official/User/login');
        }
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
            case 'resumetags'://特色管理标签启用
                $this->resume('tags', $map);
                break;
            case 'forbidtags'://特色管理标签禁用
                $this->forbid('tags', $map);
                break;
           
            case 'resumedocument'://信息管理标签启用
                $this->resume('offcialdocument', $map);
                break;
            case 'forbiddocument'://信息管理标签禁用
                $this->forbid('offcialdocument', $map);
                break;		   
		   
		   
		   
		   
            default:
                $this->error('参数非法');
        }
    }

    //商品分类
    public function shop_cate_disable($id=0,$status=0)
    {   
        if($id =='' || $status=='') $this->error('参数错误!');
        if($status==0){
            $list = D('shop_category')->where("pid=".$id." and status=1")->select();
            if($list){
                $this->error('有子类,不能直接禁用');
            }
            $cates = D('shop_category')->where("id=".$id)->save(array('status'=>$status));
            if($cates){
                $this->success('操作成功');
            }else{
                $this->error('操作失败!');
            }
     
        }else{
            $cates = D('shop_category')->where("id=".$id)->save(array('status'=>$status));
            if($cates){
                $this->success('操作成功');
            }else{
                $this->error('操作失败!');
            }
                 
        }
        
    }
	//商品类目 
	public function shop_cates_is_disable($id=0,$status=0){
			if($id=='') $this->error('参数错误!');
			$list = D('shop_sku_category')->where("sku_category_id = ".$id)->save(array('status'=>$status));
	        if($list){
				$this->success('操作成功');
			}else{
				$this->error('操作失败!');
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
            $this->success($msg['success'],$msg['url'],$msg['ajax']);
        }else{
            $this->error($msg['error'],$msg['url'],$msg['ajax']);
        }
    }
}
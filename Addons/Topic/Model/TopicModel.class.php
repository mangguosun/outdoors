<?php
namespace Addons\Topic\Model;
use Think\Model;

/**
 * 分类模型
 */
class TopicModel extends Model{
	
	/* 自动完成规则 */
	protected $_auto = array(
			array('create_time', 'getCreateTime', self::MODEL_BOTH,'callback'),
	);
	
	
	protected function _after_find(&$result,$options) {
		$result['istop'] = $result['is_top'] == 1 ? '推荐' : '普通';
		$result['useradmin'] = $result['uadmin'] == 0 ? '暂无主持' : 'onep2p';
		$result['create_time'] = date('Y-m-d H:i:s', $result['create_time']);
	}
	
	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}
	
	
	/* 获取编辑数据 */
	public function detail($id){
		$data = $this->find($id);
		return $data;
	}
	
	/* 推荐*/
	public function top($id){
		return $this->save(array('id'=>$id,'is_top'=>'1'));
	}
	
	/* 取消推荐*/
	public function offtop($id){
		return $this->save(array('id'=>$id,'is_top'=>'0'));
	}
	
	/* 删除 */
	public function del($id){
		return $this->delete($id);
	}
	
	/**
	 * 新增或更新一个文档
	 * @return boolean fasle 失败 ， int  成功 返回完整的数据
	 * @author huajie <banhuajie@163.com>
	 */
	public function update(){
		/* 获取数据对象 */
		$data = $this->create();
		if(empty($data)){
			return false;
		}
		
		/* 添加或新增基础内容 */
		if(empty($data['id'])){ //新增数据
			$id = $this->add($data); //添加基础内容
			if(!$id){
				$this->error = '新增话题内容出错！';
				return false;
			}
		} else { //更新数据
			$status = $this->save($data); //更新基础内容
			if(false === $status){
				$this->error = '更新话题内容出错！';
				return false;
			}
		}
	
		//内容添加或更新完成
		return $data;
	
	}	
	
	/* 时间处理规则 */
	protected function getCreateTime(){
		$create_time    =   I('post.create_time');
		return $create_time?strtotime($create_time):NOW_TIME;
	}
}
<?php
namespace Websit\Controller;
use Think\Controller;

class LinksController extends BaseController
{
    public function index()
    {
		$status = $_GET['status'] ? $_GET['status'] : 0 ;
		switch($status){
			case 0;
				$links_total = D('links')->where(array('siteid'=>SITEID))->order('level')->count();
				$Page  = new \Think\Page($links_total,10);// 
				$Page->setConfig('theme',"<u style='float:left;font-size:14px;line-height:30px;padding-right:20px;'>总共%TOTAL_ROW%条数据 %NOW_PAGE%/%TOTAL_PAGE%页</u> %UP_PAGE% %FIRST% %LINK_PAGE% %DOWN_PAGE% %END%");
				$links_arr = D('links')->where(array('siteid'=>SITEID))->order('level')->limit($Page->firstRow.','.$Page->listRows)->select();
				$show  = $Page->show();// 
				$this->assign('page',$show);
				$this->assign('links_arr',$links_arr);
			break;
			case 1;
			break;
		}
		$this->assign('status',$status);
        $this->display();
    }
	public function do_linksadd($link = '',$title = '',$level = ''){
		if (trim(op_t($title)) == '') {
            $this->error('请输入站点名称！');
        }
		if($link != '') {
			$link = trim(op_h($link));
			$pa = '/\b((?#protocol)https?|ftp):\/\/((?#domain)[-A-Z0-9.]+)((?#file)\/[-A-Z0-9+&@#\/%=~_|!:,.;]*)?((?#parameters)\?[A-Z0-9+&@#\/%=~_|!:,.;]*)?/i';
			preg_match($pa,$link,$r);
			if($r[1] != 'http'){
				$this->error('请输入正确的URL地址！');
			}
        }else{
			$this->error('请输入URL地址！');
		}
		if(!is_numeric($level)) $this->error('优先级必须为数字！');
		$data['siteid'] = SITEID;
		//$data['type'] = $type;
		$data['link'] = $link;
		$data['status'] = 1;
		$data['create_time'] = time();
		$data['level'] = $level;
		$data['title'] = trim(op_t($title));
		$rs = D('links')->add($data);
		if($rs){
			$this->success('添加成功！','refresh');
		}else{
			$this->error('添加失败！');
		}
	}
	public function do_linksedit($id = '',$link = '',$title = '',$level = ''){
		if (trim(op_t($title)) == '') {
            $this->error('请输入站点名称！');
        }
		if($link != '') {
			$link = trim(op_h($link));
			$pa = '/\b((?#protocol)https?|ftp):\/\/((?#domain)[-A-Z0-9.]+)((?#file)\/[-A-Z0-9+&@#\/%=~_|!:,.;]*)?((?#parameters)\?[A-Z0-9+&@#\/%=~_|!:,.;]*)?/i';
			preg_match($pa,$link,$r);
			if($r[1] != 'http'){
				$this->error('请输入正确的URL地址！');
			}
        }else{
			$this->error('请输入URL地址！');
		}
		if(!is_numeric($level)) $this->error('优先级必须为数字！');
		$data['link'] = $link;
		$data['level'] = $level;
		$data['title'] = trim(op_t($title));
		$rs = D('links')->where(array('id'=>$id,'siteid'=>SITEID))->save($data);
		if($rs){
			$this->success('编辑成功！','refresh');
		}else{
			$this->error('编辑失败！');
		}
	}
	public function links_update($status = '',$id = ''){
		$data['status'] = $status;
		$msg = $status == 1 ? '启用成功' : '禁用成功' ;
		$rs = D('links')->where(array('id'=>$id,'siteid'=>SITEID))->save($data);
		if($rs){
			exit(json_encode(array('status'=>true,'msg'=>$msg)));
		}else{
			exit(json_encode(array('status'=>false,'msg'=>'操作失败！')));
		}
	}
	public function links_edit($id){
		if(!$id) $this->error('参数错误！');
		$links_info = D('links')->where(array('id'=>$id,'siteid'=>SITEID))->find();
		if(!$links_info) $this->error('数据出错！');
		$this->assign('links_info',$links_info);
		$this->display();
	}
}
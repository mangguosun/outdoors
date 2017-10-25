<?php 
namespace Official\Controller;
use Think\Controller;

class IndexController extends Controller{
   
    public function _initialize()
    {
		if(SITEID != 1){
			header('HTTP/1.1 404 Not Found'); 
			header("status: 404 Not Found"); 
			exit;	
		}
	}
	public function index(){
		
		$map['siteid'] = 1;
		$map['status'] = 1;
		$map['category_id'] = 3;
		$cases_datainfo	=	D('offcialdocument')->where($map)->order('create_time desc')->limit(0,12)->field('id,title,cover_id')->select();



		$this->assign('cases_datainfo',$cases_datainfo); 
		
		
		$this->setTitle('心有所想，跃行无限');
		$this->display();
	}
	
	public function cases(){
		$map['siteid'] = 1;
		$map['status'] = 1;
		$map['category_id'] = 3;
		$count	=	D('offcialdocument')->where($map)->count();
		$Page       = new \Think\Page($count,12);
		$Pageshow       = $Page->show();// 分页显示输出
		$data_info	=	D('offcialdocument')->where($map)->order('create_time desc')->limit($Page->firstRow.','.$Page->listRows)->field('id,title,category_id,cover_id,create_time,status')->select();
		$this->assign('page',$Pageshow);
		$this->assign('data_info',$data_info); 
	  $this->setTitle('成功案例');
	  $this->display();
	}
	
	public function faq(){
	   
		$map['siteid'] = 1;
		$map['status'] = 1;
		$map['category_id'] = 2;
		$count	=	D('offcialdocument')->where($map)->count();
		$Page       = new \Think\Page($count,10);
		$Pageshow       = $Page->show();// 分页显示输出
		$data_info	=	D('offcialdocument')->where($map)->order('create_time desc')->limit($Page->firstRow.','.$Page->listRows)->field('id,title,category_id,cover_id,create_time,status')->select();
		$this->assign('page',$Pageshow);
		$this->assign('data_info',$data_info); 
		$this->setTitle('最新公告');
		$this->display();
	} 
	public function notice(){
	
		$map['siteid'] = 1;
		$map['status'] = 1;
		$map['category_id'] = 1;
		$count	=	D('offcialdocument')->where($map)->count();
		$Page       = new \Think\Page($count,10);
		$Pageshow       = $Page->show();// 分页显示输出
		$data_info	=	D('offcialdocument')->where($map)->order('create_time desc')->limit($Page->firstRow.','.$Page->listRows)->field('id,title,category_id,cover_id,create_time,status')->select();
		$this->assign('page',$Pageshow);
		$this->assign('data_info',$data_info); 
		$this->setTitle('最新公告');
		$this->display();
	} 
	
	public function detail($id){
	
		
		$map['id'] = $id;
		$map['siteid'] = 1;
		$map['status'] = 1;
		$data_info	=	D('offcialdocument')->where($map)->find();
		
		if(!$data_info){
			$this->error('信息不存在或已被删除');
		}
		$this->assign('data_info',$data_info); 
		$this->setTitle('最新公告');
		$this->display();
	} 
	
}


 ?>
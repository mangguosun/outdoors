<?php
namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;

class CourseController extends BaseController
{
	private $course = null;
	private $course_subclass = null;
	private $course_place = null;
	private $course_subject = null;
	private $course_course_subclasslink = null;
	private $course_subclasslink = null;

    public function _initialize()
    {
       parent::_initialize(); 
	   $this->course = D('course');
	   $this->course_subclass = D('course_subclass');
	   $this->course_place = D('course_place');
	   $this->course_subject = D('course_subject');
	   $this->title = I('name');
	}

		/**
	*科目添加方法
	**/
	public function add($name,$subsn)
    {	
    	
		if (!$name) {
            $this->error('填写科目名称');
        }
		preg_match_all('/./us', $name, $match);
		if(count($name)>25){ 
			$this->error('标题字数不得超过25个字!');
		}

		//添加科目
        $this->course_subject->create();
        $id = $this->course_subject->add();
        $pid= I('pid');
        $arr = array(
        	'id'=>$id,
        	'pid'=>$pid
        	);
        var_dump($arr);

	}
		
		//编辑科目
		public function editsubject(){

			$where = 'id = '.I('get.id');
			
			$data = $this->course_subject->where($where)->find();

			$this->assign('data',$data);

			$this->display('Course/editsubject');			
		}
	
	    //编辑完成
		public function  updatesubject(){

			$this->course_subject->create();

			if($this->course_subject->save()){
				$this->success('编辑成功',U('Course/subject'));
			}
		}

		
	//删除科目
	public  function deletesubject(){
		$id = I('get.id');
		$list=$this->course_subject->where("pid =".$id)->find();
		if($list){ 
			$this->error('有下级菜单的不可以删除');
		}else{
			$rel	=	$this->course_subject->where("id =".$id)->delete();
			if($rel){
				$this->success("删除成功",U('Course/subject'));
			}else{
				$this->error("删除失败",U('Course/subject'));
			}
		}
	}
	
	

	// //搜索科目管理页
	 public function  subject(){
	 	$data = $this->course_subject->table(array('course_subclass'=>'c','course_subject'=>'s','course_subclasslink'=>'l'))->field('c.id cid,c.name cname,s.id sid,s.num,s.name sname')->where('l.subclass_id= s.id and l.subjectclass = c.id')->select();
		$data = D('course_subject')->where('pid=0')->select();
		foreach($data as $k=>$v){
			$data[$k]['data_2nd']	=	D('course_subject')->where('pid='.$v['id'])->select();
			foreach($data[$k]['data_2nd'] as $key=>$val){
					$data[$k]['data_2nd'][$key]['name']	=	'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$val['name'];
					$data[$k]['data_2nd'][$key]['subsn']	=	'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$val['subsn'];
			}
		
		
		}
		$this->assign('data',$data);
		
	 	$this->display();
	 }
	
	public function createsub($name='',$pid=0){
		$subsn= I('subsn');
		if(IS_POST){
			$subject['name']	=	$name;
			$subject['subsn']	=	$subsn;
			$subject['pid']	=	$pid;
			$add_subject= D('course_subject')->add($subject);

			if($add_subject){
				$this->success('添加成功',U('Course/subject'));
			}else{
				$this->success('添加失败',U('Course/subject'));
			}
			
		}else{
		$subject = D('course_subject')->where('pid=0')->select();
		$this->assign('subject', $subject);
		$this->display();
		}
	}
	

	/**
     * 场地页面
     * @param $id
     */
	public function createplace(){
			$this->display();
	}

	/**
     * 课程页面
     * @param $id
     */
	public function createcourse(){




		$this->display();
	}
	/**
	*课程添加
	**/
	public function addcourse($couresname='',$couresclass=0,$brief_desc='',$desc='',$apply_notice='')
    {


    	if($IS_POST){

			if (!$couresname) {
	            $this->error('填写课程名称');
	        }
			preg_match_all('/./us', $couresname, $match);
			if(count($couresname)>25){ 
				$this->error('课程名称字数不得超过25个字！');
			}
	      // $course = D('course')->create();
				
		
	 		 print_r(11111);
	 		 exit;


			$save_data = array(
				'name'=>$couresname,
				'class'=>$couresclass,
				'brief_desc'=>$brief_desc,
				'desc'=>$desc,
				'apply_notice'=>$apply_notice
				);

			print_r($save_data);
			exit;


			if($this->course->add($arr)){

				$this->success('添加成功',U('Course/index'));
				
			}else{
				$this->error('添加失败',U('Course/index'));
			}
    	}

	}
	/**
	*场地添加
	**/
	public function addPlace($name,$num,$detail)
    {	
		if (!$num) {
            $this->error('填写场地编号');
        }
		preg_match_all('/./us', $name, $match);
		if(count($num)>25){ 
			$this->error('场地编号字数不得超过25个字！');
		}
        $course_place = D('course_place')->create();
			$num = I('post.num');
			$name = I('post.name');
			$detail = I('post.detail');
			$arr = array(
				"id"=>$id,
				'num'=>$num,
				'name'=>$name,
				'detail'=>$detail
				);

			if($this->course_place->add($arr)){

				$this->success('添加成功',U('Course/place'));
				
			}else{
				$this->error('添加失败',U('Course/place'));
			}
		$this->assign('num',$num);
		$this->assign('name',$name);
		$this->assign('detail',$detail);
		$this->display();
	}
	/**
     * 场地页面place.html
     * @param $id
     */
	public function place(){
			$list = $this->course_place->select();

			$this->assign('data',$list);

			$this->display();
	}
		

		//编辑场地
		public function editplace(){
			echo "<meta charset=utf8>";
			$where = 'id = '.I('get.id');
			
			$data = $this->course_place->where($where)->find();
			
			$this->assign('data',$data);

			$this->display();
		}
	
		public function  updateplace(){

	    //编辑完成
			$this->course_place->create();

			if($this->course_place->save()){
				$this->success('编辑成功',U('Course/place'));
			}else{
				$this->success('编辑失败',U('Course/place'));
			}
		}

		//删除场地
		public  function  deleteplace(){

			$id = I('get.id');
			$where = "id = ".$id;
			if($this->course_place->where($where)->delete()){
				$this->success('删除成功',U('Course/place'));
			}else{
				$this->error('删除失败',U('Course/place'));
			}
		}
}  

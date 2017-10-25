<?php 
/*
关于我们
*/
namespace About\Controller;

use Think\Controller;

class IndexController extends Controller{
	
   private $demo=null;
   
   public function _initialize(){
		$model_info = get_appinfo('About');
		if(!$model_info){
			$this->error('参数错误或没有找该应用');
		}
		$menutype = array();
		$red_about=D('about')->where("status=1 and siteid=".SITEID)->select();
		if($red_about){
			foreach($red_about as $m=> &$rs){
				$menutype[$rs['id']]['tab'] = 'cat_'.$rs['id'];
				$menutype[$rs['id']]['title'] = $rs['title'];
				$menutype[$rs['id']]['href'] =  U('About/Index/index',array('id'=>$rs['id']));
			}
		}
		$id = $_GET['id'];
		if(!$id){
			$list=D('about')->where("siteid=".SITEID." and status=1")->order("id asc")->find();
			$id = $list['id'];
		}
        $sub_menu =
            array(
                'left' =>$menutype,

            );
        $this->assign('sub_menu', $sub_menu);
		$this->assign('model_info', $model_info);
        $this->assign('current', 'cat_'.$id);
	    $this->demo=D('about');
    }
   public function index($id=''){
		if(!$id){
			$list=$this->demo->where("siteid=".SITEID." and status=1")->order("id asc")->find();
		}else{
			$list=$this->demo->where("siteid=".SITEID." and id=".$id." and status=1")->find();
		}
		$this->assign('list',$list);		
		$this->display();
    }
}


 ?>
<?php

namespace Official\Controller;

use Think\Controller;

class DistributecategoryController extends BaseController
{
	protected $shop_categoryModel;
    function _initialize()
    {
		$this->userdata = query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $uid);
		$this->mTalkModel = D('Talk');
		$this->setTitle('个人中心');
      $this->shop_categoryModel = D('Shop/ShopCategory');  
    }
	/*
	**商品列表* 2014-12-3 dlx pm
	*/
    public function index(){

		$map['pid']=0;
		$map['status']  = array('egt',0);
		$map['is_distribute']=1;
		if($_GET['pid']){
			$map['pid']=$_GET['pid'];
		}
		$list =	D('shop_category')->where($map)->select();
		foreach($list as $k=>$v){
			 $list[$k]['childnum']=D('shop_category')->where("pid=".$v['id']." and is_distribute=1 and status>=0")->count();
		}
		$this->assign('shop_cates',$list);
		$this->assign('user',$this->userdata);
		$this->display();
	}
	
	/*
	*添加一级分类**2014-12-4
	*/
	
	public function add($id=0,$pid=0,$title='',$sort=0)
	{
		if(IS_POST){
		            ///---------------------------修改------------------------------------
			if($id !=0){
				if($id=='') $this->error('参数错误!');
				$title	=	op_t(trim($title));
				
				if($title =='') $this->error('请填写分类名称');
				if($sort !=0){
					if(!is_numeric($sort)) $this->error('排序必须为数字!');
				}
                $rs = D('shop_category')->where("title='".$title."' and pid=".$pid." and status>=0  and is_distribute=1")->find();
		
                
				$data = array(
					'title' 		=> $title,
					'update_time'	=> time(),
					'sort'			=> $sort,
					'is_distribute'			=> 1,
				);
				
				$catelist	=	D('shop_category')->where("id=".$id." and is_distribute=1")->save($data);
				if($catelist){
					$up_shop_category['update_time']=time();
					$uplist = D('shop_category')->where('id='.$pid." and is_distribute=1")->save($up_shop_category);
					$this->success("更改成功",U('Official/Distributecategory/index'));
				}else{
					$this->error('更改失败!');
				}
			}else{
				///-----------------------------------添加-------------------------------
			    $title	=	op_t(trim($title));
				$sort	=	op_t(trim($sort));
				if($title =='') $this->error('请填写分类名称');
				if($sort !=0){
					if(!is_numeric($sort)) $this->error('排序必须为数字!');
				}
				//---验证是否添加重复---
				$rs = D('shop_category')->where("title='{$title}' and pid=".$pid." and status>=0 and is_distribute=1")->find();
				if($rs) $this->error('亲!你已添加该分类!不能重复添加哦!');

				$data = array(
					'title' 		=> $title,
					'create_time'	=> time(),
					'pid'			=> $pid,
					'sort'			=> $sort,
					'is_distribute'			=> 1,
				);
				$cate	=	D('shop_category')->data($data)->add();
				if($cate){
					$this->success("添加成功",U('Official/Distributecategory/index'));
				}else{
					$this->error("添加失败!");
				}
			
			}
			
		}
	
	}
	
	/*
	*修改一级分类* 2014-12-4**
	*/
	
	public function shop_category_edit()
	{
		$id	= $_GET['id'];
		$list = D('shop_category')->where("id=".$id." and is_distribute=1")->find();
	    $this->assign('catefind',$list);
	    $this->assign('user',$this->userdata);
		$this->display();
	}
	public function shop_category_add(){
		$this->assign('user',$this->userdata);
		$this->display();

	}
  
	
	
	/*
	*获取中文首字母
	*/
	public function getLimit(){
		    $limit = 	array( //gb2312 拼音排序
			array(45217,45252), //A
			array(45253,45760), //B
			array(45761,46317), //C
			array(46318,46825), //D
			array(46826,47009), //E
			array(47010,47296), //F
			array(47297,47613), //G
			array(47614,48118), //H
			array(0,0),         //I
			array(48119,49061), //J
			array(49062,49323), //K
			array(49324,49895), //L
			array(49896,50370), //M
			array(50371,50613), //N
			array(50614,50621), //O
			array(50622,50905), //P
			array(50906,51386), //Q
			array(51387,51445), //R
			array(51446,52217), //S
			array(52218,52697), //T
			array(0,0),         //U
			array(0,0),         //V
			array(52698,52979), //W
			array(52980,53688), //X
			array(53689,54480), //Y
			array(54481,55289), //Z
		);
		return $limit;
	}
	
	public function getWords($str){
		$str= iconv("UTF-8","gb2312", $str);
		$i=0;
		while($i<strlen($str)){
			$tmp=bin2hex(substr($str,$i,1));
			if($tmp>='B0'){ //汉字的开始
				$t=$this->getLetter(hexdec(bin2hex(substr($str,$i,2))));
				$value[] = sprintf("%c",$t==-1 ? '*' : $t );
				$i+=2;
			}
			else{
				$value[] = sprintf("%s",substr($str,$i,1));
				$i++;
			}
		}
		$result = implode('',$value); ;
		return $result;
	}
	/*
	**/
	public function getLetter($num){
		$limit	= $this->getLimit();
		$char_index=65;
		foreach($limit as $k=>$v){
			if($num>=$v[0] && $num<=$v[1]){
				$char_index+=$k;
				return $char_index;
			}
		}
		return -1;
    }
	

}  
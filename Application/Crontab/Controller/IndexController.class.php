<?php

namespace Crontab\Controller;
set_time_limit (0);
use Think\Controller;


class IndexController extends Controller{
    /**
     * 业务逻辑都放在 WeiboApi 中
     * @var
     */
    public function _initialize(){
    }
	
 	public function index($limit=100){ 
		$list=D('Message')->getSendMessage($limit);
		//var_dump($list);
		if($list){ 
			foreach ($list as $key => $va) {
				if($list[$key]['temp']==1){ 
					//超过2次跳出循环
					if($list[$key]['times'] >= 2){ 
	    				D('send_message')->where("id = ".$list[$key]['id'])->setField(array('status'=>-1,'update_time'=>time()));
	    		   	 unset($list[$key]);
    				} 
				}elseif($list[$key]['temp']==2){
					//无限循环是加10分钟
					$execute_time=time()+600;
					D('send_message')->where("id = ".$list[$key]['id'])->setField(array('execute_time'=>$execute_time));
				}elseif($list[$key]['temp']==3){ 
                    if($list[$key]['times'] >= 2){ 
                        D('send_message')->where("id = ".$list[$key]['id'])->setField(array('status'=>-1,'update_time'=>time()));
                        unset($list[$key]);
                    }else{ 
                         $execute_time=time()+600;
                         D('send_message')->where("id = ".$list[$key]['id'])->setField(array('execute_time'=>$execute_time));
                    } 
                    
                }
	    		
	    		$list[$key]['param']=string2array($list[$key]['param']);	
		    	$data=$list[$key]['taskphp']($list[$key]['param']);
		    	if($data['error']){ 
		    		if($list[$key]['temp']==3){
		    			//成功排行的时间变成第二天12点
                        $execute_time_success=$list[$key]['param']['execute_time']+86400;
                        $param['execute_time']=$execute_time_success;
                        $param=array2string($param);
                        D('send_message')->where('id = '.$list[$key]['id'])->setField(array('status'=>0,'times'=>0,'update_time'=>time(),'execute_time'=>$execute_time_success,'content'=>$data['content'],'param'=>$param));
                        unset($list[$key]);
                    }else{ 
                    	//成功后改状态为1  ,并移除数组
                        D('send_message')->where('id = '.$list[$key]['id'])->setField(array('status'=>1,'update_time'=>time(),'content'=>$data['content']));
                        unset($list[$key]);

                    }
		    	}else{
		    		if($list[$key]['temp']==3){
		    			//失败后次数加1 
                        D('send_message')->where('id = '.$list[$key]['id'])->setField(array('status'=>0,'times'=>$va['times']+1,'content'=>$data['content']));
                        unset($list[$key]);
                    }else{

                        //失败后把锁定状态解除并次数加1 ，移除数组
                        D('send_message')->where('id = '.$list[$key]['id'])->setField(array('status'=>0,'times'=>$va['times']+1,'content'=>$data['content']));
                        unset($list[$key]);
                    }
		    	}
		    }
		}
	}

     //最后执行发送操作
    public function do_send($limit=2){ 
    	$list=$this->getSendMessage($limit);
   	    if($list){    	
	    	
	    	foreach ($list as $key => $va) {
	    		if($list[$key]['times'] >= 2){ 
	    			D('send_message')->where("id = ".$list[$key]['id'])->setField(array('status'=>-1,'update_time'=>time()));
	    		    unset($list[$key]);
    			} 
	    		$list[$key]['param']=string2array($list[$key]['param']);	
		    	$data=$list[$key]['taskphp']($list[$key]['param']);
		    	
		    	if($data){ 
		    		//成功后改状态为1  ,并移除数组
		    		D('send_message')->where('id = '.$list[$key]['id'])->setField(array('status'=>1,'update_time'=>time()));
		    		unset($list[$key]);
		    	}else{
		    		//失败后把锁定状态解除并次数加1 ，移除数组
		    		D('send_message')->where('id = '.$list[$key]['id'])->setField(array('status'=>0,'times'=>$va['times']+1));
		    		unset($list[$key]);
		    	}

		    }
		    
		}    
	}
    
}
<?php

namespace Weixin\Controller;
use Think\Controller;
class IndexController extends Controller{

    public function index(){ 
   		if(!empty($_GET['echostr'])){        
		    D('Weixin')->valid($_GET);  		      
		}else{  		      
		    D('Weixin')->responseMsg($_GET);  
		} 
	}

	
}
?>

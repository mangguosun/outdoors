<?php


namespace Shop\Controller;

use Think\Controller;

class OrderController extends Controller
{
	 public function _initialize()
    {

        if (!is_login()) {
            //$this->error('请登录后再访问本页面。');
			$this->redirect('Home/User/login');
        }

    }
	
	
	
	
	//商品供货商拆分
	public function formatsiteid($siteids='')
	{
		function formatArray($array){	//函数 合并数组中相同元素
			sort($array);
			$tem = "";
			$temarray = array();
			$j = 0;
			for($i=0;$i<count($array);$i++){
				if($array[$i]!=$tem){
					$temarray[$j] = $array[$i];
					$j++;
				}
				$tem = $array[$i];
			}
			return $temarray;
		}
		//统计该信息内包含了哪些站点的商品
		$siteids = formatArray($siteids);
		return $siteids;
	}
	
	//结算页，选择收货信息
	public function getorderinfo(){
		$uid=is_login();
		$siteid=SITEID;
		/*************查询已保存默认地址开始*************/
		$shop_address=D('shop_address')->where("siteid=".$siteid." and uid=".$uid)->order("isdefault desc")->select();
		 foreach($shop_address as $key=>&$val){
			 $address = get_citys($val['address']);
			 $val['community']	=	get_city($address['community']);
			 $val['district']	=	get_city($address['district']);
			 $val['city']		=	get_city($address['city']);
			 $val['province']	=	get_city($address['province']);
			 
			 if($val['isdefault']==1){
				$shop_address_default	=	$val;
			 }
		}
		unset($key);unset($val);
		/*************查询已保存默认地址结束*************/
		/***************判定部分1********************/	
		if(isset($_POST['tocreateorder'])){
			if(!$_SESSION['pay']  && $_POST['random_code'] != md5($_SESSION['pay']) ){
				$this->error('信息已过期！', U('Shop/Shopcart/shopcartitem'),3);
			}
			$goodssku_ids 		= 	$_POST['ids'];
		}
		if(isset($_POST['fastbuy'])){
			if(!$_POST['goods_sku']) $_POST['goods_sku']=0;
			$goodssku_ids	=	array(
					'0'	=>	$_POST['goods_id'].'|'.$_POST['goods_sku'],
			);
		}
		if(!$goodssku_ids) $this->error('请选择相关商品');
		
		/***************判定部分1结束********************/
		foreach($goodssku_ids as $k=>$v){
			/************拆数组***************/
			$goodssku_id	=	$goodssku_ids[$k];
			$goods			=	explode("|",$goodssku_id);
			$goods_id		=	$goods[0];
			$sku_id			=	$goods[1];
			/************商品数量***************/
			if(isset($_POST['tocreateorder'])){
				$goods_num=D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id. ' and siteid='.$siteid)->getField('num');
			}elseif(isset($_POST['fastbuy'])){
				$goods_num	=	$_POST['goods_num'];
			}
			/************商品数量结束***************/
			
			if(!$goods_num) $this->error('最少要选择一个数量');
			
			/*****************判定部分开始*******************/
			$data = D('Common/shop')->compgoods($goods_id,$sku_id);
			
			if(!$data['goods']){
				$this->error('商品不存在或已被删除');
			}
			
			if($data['error']) $this->error($data['error']);
			if($data['shop_goods_stock'] == 0 ) $this->error('本商品已经无库存，请选择其它商品');
			if($goods_num > $data['shop_goods_stock']) $this->error('你的商品数量大于了本配置的库存,本商品配置物品只有'.$data['shop_goods_stock'].'件了');
			$siteids[$k]['siteid']	=	$data['goods']['siteid'];
		}
		
		$siteids	=	$this->formatsiteid($siteids);
		foreach($siteids as $key=>$val){
			foreach($goodssku_ids as $k=>$v){
				/************拆数组***************/
				$goodssku_id	=	$goodssku_ids[$k];
				$goods			=	explode("|",$goodssku_id);
				$goods_id		=	$goods[0];
				$sku_id			=	$goods[1];		
				
				/*****************判定部分开始*******************/

				$data = D('Common/shop')->compgoods($goods_id,$sku_id);

				$shop_goods_price	=	$data['shop_goods_price'];
				/************商品数量***************/
			if(isset($_POST['tocreateorder'])){
				$goods_num=D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id. ' and siteid='.$siteid)->getField('num');
			}elseif(isset($_POST['fastbuy'])){
				$goods_num	=	$_POST['goods_num'];
			}
			/************商品数量结束***************/

				/*****************查询单价结束*******************/
				$shop_bargain =	D('shop_bargain')->where('goods_id='.$goods_id.' and overtime>'.time().' and  starttime<'.time().' and surplus_num>0')->find();
				if($shop_bargain){
					$shop_goods_price = $shop_bargain['bargain_price'];
				}
				$goods_arr	=	$data['goods'];
				/****商品属性*****/
				$sku_info = D('shop_sku_detailed')->where("goods_id=".$goods_id.' and sku_id='.$sku_id)->getField("sku_id,sku_title,price,stock");
				
				$item[$k]['id']			=	$goods_id;
				$item[$k]['num']		=	$goods_num;
				$item[$k]['price']		=	$shop_goods_price;
				$item[$k]['goods_name']	=	$goods_arr['goods_name'];
				$item[$k]['fr_freight']	=	$goods_arr['fr_freight'];
				$item[$k]['fr_money']	=	$goods_arr['fr_money'];
				$item[$k]['fr_addnum']	=	$goods_arr['fr_addnum'];
				$item[$k]['fr_num']		=	$goods_arr['fr_num'];
				$item[$k]['fr_id']		=	$goods_arr['fr_id'];
				$item[$k]['sku_id']		=	$sku_id;
				$item[$k]['desc']		=	$sku_id;
				
				$totalnum	=	$totalnum	+	$goods_num;
				$item[$k]['totle_price']		=	$shop_goods_price	*	$goods_num;
				
				
				if(!$item[$k]['fr_id']){
					if($item[$k]['num']>$item[$k]['fr_num']){
						$fr_addnum=($item[$k]['num']-$item[$k]['fr_num'])/$item[$k]['fr_addnum'];
						$item[$k]['a1']	=	$item[$k]['fr_freight']+($fr_addnum)*$item[$k]['fr_money'];
					}else{
						$item[$k]['a1']	=	$item[$k]['fr_freight'];
					}
				}else{
					$item[$k]['a1']	=	0;
				}
				$siteids[$key]['item'][$k]	=	$item[$k];
				if($goods_arr['siteid']!=$val['siteid']){
					unset($siteids[$key]['item'][$k]);
				}else{
					$all_totle_price	=	$all_totle_price+$item[$k]['totle_price'];
					$money_fr = $money_fr + $item[$k]['a1'];
				}
				
			}
			unset($k);
			
			
		}
		$ajax_card_select	=	D('Common/Pointcard')->ajax_card_select('',$shop_goods_price,$goods_id,2);
		
		$this->assign('ajax_card_select',$ajax_card_select['res']);
		$this->assign('siteids',$siteids);
		$this->assign('alltotalprice',$all_totle_price);
		$this->assign('totalnum',$totalnum);
		$this->assign('money_fr',$money_fr);
		$this->assign('shop_address_default',$shop_address_default);
		$this->assign('shop_address',$shop_address);
		$this->assign('ord',$item);
		
		$this->display();
		
	}
	/*-添加地址-*/
	public function address_doadd(){
		$cate	=	D('shop_address')->where("uid=".is_login())->save(array('isdefault'=>0));
	   
		$name 	=	trim($_POST['name']);
		$phone  =	trim($_POST['phone']);
		$detailed =	trim($_POST['detailed']);
		$zipcode  =	trim($_POST['zipcode']);
		$email  =	trim($_POST['email']);
		
		$province  	=	trim($_POST['address_province']);
		$city  	    =	trim($_POST['address_city']);
		$district  	=	trim($_POST['address_district']);
		$community  =	trim($_POST['address_community']);
		
	    if($name=='') $this->error('请填写姓名');
		if($detailed=='') $this->error('请填写详细地址');
		if($zipcode!=''){
          $this->checkCode($zipcode);
		}
		$this->checkTelphone($phone);
		$this->checkEmail($email);
		
			$cityparam['province'] = $province;
			$cityparam['city'] = $city;
			$cityparam['district'] = $district;
			$cityparam['community'] = $community ;
			
			$user	=	array(
				'name'		=>	$name,
				'address'	=>	set_city($cityparam),
				'detailed'	=>	$detailed,
				'zipcode'	=>	$zipcode,
				'phone'		=>	$phone,
				'email'		=>	$email,
				'uid'		=>	is_login(),
				'siteid'	=>	SITEID,
				'create_time'	=>	time(),
				'change_time'	=>	time(),
				'isdefault'		=>	1,
			);
			
	        $res	=	D('shop_address')->add($user);
      
			if($res){
				$address= get_citys($user['address']);
				$user['community']	=	get_city($address['community']);
				$user['district']	=	get_city($address['district']);
				$user['city']		=	get_city($address['city']);
				$user['province']	=	get_city($address['province']);
				$user['id']	=	$res;
				
				echo json_encode(array('status'=>1,'info'=>'添加成功','datainfo'=>$user));
			}else{
				echo json_encode(array('status'=>0,'info'=>'添加失败'));
			}
			
	}
	
	
	
	
		/*生成订单*/
	public function dosign(){
		
		$uid	=	is_login();
		$siteid	=	SITEID;
		
		$goods_post		=	$_POST['ids']; //数组商品id
		if(isset($_POST['dosign'])){	//购物车下单        
			if(!$_SESSION['pay']  && $_POST['random_code'] != md5($_SESSION['pay']) ){
				$this->error('信息已过期！', U('Shop/Shopcart/shopcartitem'),3);
			}
		}
		
		foreach($goods_post as $key=>$value){
			//购物车内容是否发生变化，若变化返回购物车重新结算
			$goods_arr	=	$value;
			$goods		=	explode("|",$goods_arr);
			$goods_id	=	$goods[0];
			$sku_id		=	$goods[1];
			$num		=	$goods[2];
			
			if(isset($_POST['dosign'])){
				$shop_cart	=	D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$sku_id.' and num='.$num. ' and siteid='.$siteid)->find();
				if(!$shop_cart) $this->error('购物车内容发生变化，请重新结算', U('Shop/Shopcart/shopcartitem'),3);
			}
			$siteids[$key]	=	D('shop')->where("id=".$goods_id)->getField('siteid');
		}
		unset($key);
		unset($value);
		
		
		
		//收货地址id
		$address_id	 =	$_POST['reds_id'];
		if($address_id=='') {
			$this->error('请填写或选择一个有效收货地址');
		}
		
		$list	=	D('shop_address')->where('id='.$address_id)->find();   //查询收货地址
		$consignee_name		=	$list['name'];       //收货人
		$consignee_address	=	$list['address'];    //收货区域编号
		$consignee_address_detailed	=	$list['detailed'];     //收货地址详情
		$zipcode			=	$list['zipcode'];            //邮编
		$phone				=	$list['phone'];             //手机号
		$email				=	$list['email']; 
		
		$status		=	20;		//订单状态：20下单成功，待全款支付、33完成、60退款、61已退款、0取消、-1删除
		if(isset($_POST['postscript'])){
			$postscript=$_POST['postscript'];
		}
		$pay_status	=	0;        //支付状态：0未支付、2全额、3优惠券支付
		


		$siteids	=	$this->formatsiteid($siteids);
		
		foreach($siteids as $k=>$v){
			$alltotalprice	=	0;
			$itselftotalprice	=	0;
			$freight_price	=	0;
			$seller_price	=	0;
			$order_seller_price	=	0;
			$order_sn	=	create_sn(); //账号
					
			//如果供货商为SITEID，优惠券可以使用，写入该订单
			if($v	==	SITEID){
				$cardid			= $_POST['cardid'];
				if($cardid){
					$cardid=strtoupper($cardid);
					$card_info = D('Common/Pointcard')->check_card($cardid);
					if(!$card_info['status']){
						$this->error($card_info['msg']);
					}else{
						
						$orderlist['cardid'] = $cardid;
						$card_info = D('pointcard')->where('cardid="'.$cardid.'" and siteid='.$siteid)->find();
						$card_price = $card_info['amount'];
					}
				}else{
					$card_price = 0;
				}
				
			}else{	
				$card_price = 0;
			}
			
			foreach($goods_post as $key=>$value){
				//订单商品相关信息
				$goods_arr	=	$goods_post[$key];
				$items		=	explode("|",$goods_arr);
				$goods_id	=	$items[0];
				$sku_id		=	$items[1];
				$goods_num	=	$items[2];
				
				/***************查询单价*********************/
				
				if(!$goods_id) $this->error('请选择相关商品');
				if(!$goods_num) $this->error('最少要选择一个数量');
				
				
				$goodsmap['id']		= $goods_id;
				$goodsmap['status'] = 1;
				$goodsmap['purchase_status'] = 1;
				$goodsmap['siteid'] = $v;
				
				$goods_item = D('shop')->where($goodsmap)->find();
				
				if($v	!=	SITEID){
					$seller_price = D('shop_distribute_item')->where('siteid='.$v.' and goods_id='.$goods_id)->getField('seller_price');
				}
				
				if($goods_item){
					
					$sku_info = D('shop_sku_detailed')->where("goods_id=".$goods_id)->getField("sku_id,sku_title,price,stock");
					

					if($sku_info){
						if(!$sku_id) $this->error('请选对相关商品配置');
						if(!$sku_info[$sku_id]) $this->error('商品配置请重试或联系管理员');
						
						
						$shop_goods_price = $sku_info[$sku_id]['price'];
						$shop_goods_stock = $sku_info[$sku_id]['stock'];
						
					}else{
						$shop_goods_price = $goods_item['market_price'];
						$shop_goods_stock = $goods_item['goods_num'];
					}
					if($shop_goods_stock == 0 ) $this->error('本商品已经无库存，请选择其它商品');
					if($goods_num > $shop_goods_stock) $this->error('你的商品数量大于了本配置的库存,本商品配置物品只有'.$shop_goods_stock.'件了');
					
					
					/*****************查询单价结束*******************/
					
					$goods_name		=	$goods_item['goods_name'];
					$goods_ico		=	$goods_item['goods_ico'];

					$orderg['goods_id']		=	 $goods_id;
					$orderg['goods_name'] 	= 	op_t(trim($goods_name));
					$orderg['sku_id']		= 	$sku_id;
					$orderg['goods_desc'] 	= 	get_shop_types_attribute_names($sku_id);
					$orderg['goods_num']	= 	$goods_num;
					$orderg['goods_price']	= 	$shop_goods_price;
					$orderg['total_price']	=	$shop_goods_price*$goods_num;
					$orderg['order_sn'] 	= 	$order_sn;
					$orderg['uid'] 			= 	$uid;
					$orderg['siteid']		= 	SITEID;
					$orderg['supplier_id'] 	=	$v;
					$orderg['goods_ico']	= 	$goods_ico;
					$orderg['seller_price']	= 	$seller_price;
					
					$goods_freight	=	D('Common/shop')->goods_freight($goods_id,$goods_num);
					$orderg['freight']	=	$goods_freight;
					
					
					$itselftotalprice	=	$itselftotalprice	+	$orderg['total_price'];
					
					
					$shop_bargain		=	D('shop_bargain')->where('goods_id='.$goods_id.' and overtime>'.time().' and  starttime<'.time().' and surplus_num>0')->find();
					if($shop_bargain){
						$bargain_price	= $shop_bargain['bargain_price'];
						$alltotalprice	= $alltotalprice	+	$bargain_price*$goods_num;
						$order_seller_price	=	$order_seller_price	+	number_format($bargain_price*$goods_num*$orderg['seller_price']/100, 2, '.', '');
					}else{
						$alltotalprice	= $alltotalprice	+	$shop_goods_price*$goods_num;
						$order_seller_price	=	$order_seller_price	+	number_format($orderg['total_price']*$orderg['seller_price']/100, 2, '.', '');
					}

					$freight_price	=	$freight_price	+	$orderg['freight'];
					

					$result = D('shop_order_info') -> add($orderg);  //写入订单商品表
					if($result && $_POST['dosign']){
						$res	=	D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$orderg['sku_id']. ' and siteid='.$siteid)->delete(); 
					}
				}
				

			}	
			
			//$alltotalprice=$alltotalprice-$card_price;

			
			if($result){
				//写入商城订单主表
				$orderlist['order_sn']	=	$order_sn;
				$orderlist['uid']		=	$uid;
				$orderlist['siteid']	=	SITEID;
				$orderlist['supplier_id']	=	$v;			//新增：供货商城
				$orderlist['create_time']	=	time();
				
				$alltotalprice	=	$alltotalprice - $card_price;	//商品总价减去优惠券
				if($alltotalprice<0){	//商品总价减去优惠券，若小于0，结果改成0
					$alltotalprice	=	0;
				}
				$orderlist['alltotalprice']	=	$alltotalprice	+	$freight_price;	//加上邮费
				if($orderlist['alltotalprice']==0){	//加上邮费后的支付金额为0，商品为优惠券支付或者免费
					$status=21;
					$pay_status=3;        //支付状态：0未支付、2全额、3优惠券支付/免费
				}
				
				$orderlist['status']		=	$status;
				$orderlist['pay_status']	=	$pay_status;
				
				$orderlist['itselftotalprice']	=	$itselftotalprice	+	$freight_price;
				$orderlist['freight_price']	=	$freight_price;
				$orderlist['seller_price']	=	$order_seller_price;
				
				$orderlist['consignee_name']	=	$consignee_name;
				$orderlist['consignee_address']	=	$consignee_address;
				$orderlist['consignee_address_detailed']	=	$consignee_address_detailed;
				$orderlist['zipcode']	=	$zipcode;
				$orderlist['phone']		=	$phone;
				$orderlist['email']		=	$email;
				$orderlist['postscript']	=	op_t(trim($postscript));
				//$orderlist['confirm_time']=time();
				$add_shop_ordersn	=	D('shop_ordersn') -> add($orderlist);//写入商城订单表

				if($add_shop_ordersn){
					$alltotalprice="";
					/*********订单提交成功更新优惠券状态*********/
					D('Common/Pointcard')->order_success_card($orderlist['cardid']);
					$gm_id	=	D('websit')->where(array('siteid'=>SITEID))->getField('uid');
					D('Message')->sendMessageWithoutCheckSelf($gm_id,query_user('nickname',is_login()).'在商城下了订单，订单号【'.$order_sn.'】。' ,'下单通知', U('/Manage/Order/shop'),is_login());
					reduce_goods_num($order_sn);//减少货物总量
						add_shop_order_log($order_sn,$uid,'','创建商城订单',time());
					$shopdata=array(
						'shop_order_sn'  => $order_sn,
						'execute_time'   => $orderlist['create_time']+1800,

						);
					D('Message')->addSendMessage('shop_order_update','',$shopdata,0,1);	
				
				}else{
					$this->error('下单失败');
				}
			}else{
				$this->error('下单失败2');
			}
			
		}
		$_SESSION['pay']='';
		$this->success('已下单，等待支付。', U('Usercenter/Shoporder/index',array('tab'=>1,'status'=>0)));
		
	}	
	
	
	private function checkSignature($signature){
        $length = mb_strlen($signature, 'utf8');
        if ($length >= 30) {
            $this->error('签名不能超过30个字');
        }
        /*中英文*/
        /*if(!preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$signature)){
            $this->error('个性签名只能为中文');
        }*/

    }

    /*验证qq*/
    public function check_qq($qq)
     {
        if(!preg_match("/^[1-9]*[1-9][0-9]*$/",$qq)){
            $this->error('请输入正确的QQ号码');
        }
        if($qq==''){
            $this->error('不能为空');
        }
     }
     /*验证电话号码*/
     public function checkTelphone($telphone){
         if($telphone==''){
            $this->error('手机号码不能为空');
         }
		if(!preg_match("/^1[0-9]{10}$/",$telphone)){

            $this->error('请输入正确的手机号码');
        }
       
     }
     
     /*验证邮编*/
     public function checkCode($code){
     
		if(!preg_match("/^[0-9][0-9]{5}$/",$code)){

            $this->error('请输入正确的邮政编码');
        }
       
     }
	  private function checkEmail($email)
    {
        $pattern = "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
        if (!preg_match($pattern, $email)) {
            $this->error('邮箱格式错误。');
        }

        $map['email'] = $email;
        $map['id'] = array('neq', get_uid());

    }
 
	public function check_realname($realname){
        if (trim(op_t($realname)) == '') {
            $this->error('请输入真实姓名。');
        }
     }
 
}

<?php


namespace Mobile\Controller;


/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class ShoporderController extends MobileController
{
	 protected $goods_info = 'id,goods_name,goods_ico,goods_introduct,tox_money_need,market_price,goods_num,changetime,status,createtime,category_id,is_new,sell_num';
	 public function _initialize()
	{
	   if (!is_login()) {
			$this->redirect('Mobile/User/login');
		}
		$model_info = get_appinfo('Shop');
		if(!$model_info){
			$this->error('应用未开启');
		}
		$this->assign('model_info', $model_info);
		$this->setTitle($model_info['name']);
	}
	
	
    public function index()
    {	
		$status_menu=array('0'=>array('id'=>0,'title'=>'全部'),'1'=>array('id'=>1,'title'=>'待付款'),'2'=>array('id'=>2,'title'=>'待发货'),'3'=>array('id'=>3,'title'=>'待收货'));
        $status_menu_id = I('status_menuid');
        if(!$status_menu_id){
            $status_menu_id = 0;
        }
        $get_url = json_encode($_GET);
        $this->assign('get_url', $get_url);
		$this->assign('status_menu', $status_menu);
		$this->assign('status_menuid', $status_menu_id);
		$this->assign('page',$show);
        $this->display();
		
    }


    public function get_orderinfo($page=0){
        $status_menu=array('0'=>array('id'=>0,'title'=>'全部'),'1'=>array('id'=>1,'title'=>'待付款'),'2'=>array('id'=>2,'title'=>'待发货'),'3'=>array('id'=>3,'title'=>'待收货'));

        $status_menu_id = I('status_menuid');
        if(!$status_menu_id){
            $status_menu_id = 0;
        }
        if($status_menu_id == 0){
        }elseif($status_menu_id == 1){
            $map['status']  = 20;
        }elseif($status_menu_id == 2){
            $map['status']  = 21;
        }elseif($status_menu_id == 3){
            $map['status']  = 22;
        }
		$order_sn = op_t(I('trade_sn'));
		//---订单号---
		if($order_sn!=''){
		  $map['order_sn']=$order_sn;
		}
		
		
        $start = $page*10;
		$map['uid'] 	=	 is_login();
		$map['siteid']	=	SITEID;

        $shop_order = D('shop_ordersn')->where($map)->order('create_time desc')->limit($start,10)->select();
        foreach ($shop_order as $key => &$v) {
			$pay_price = 0 ;
			$all_goods_num = 0 ;
			$freight = 0 ;
			$v['status_text'] = get_mobile_shop_order_status($v['status']);
            $shop_order_info = D('shop_order_info')->where(array('order_sn'=>$v['order_sn']))->order('id desc')->select();
			foreach ($shop_order_info as &$so) {
				if(!$so['freight']){$so['freight']=0;}
				$pay_price += $so['total_price']+$so['freight'];
				$all_goods_num += $so['goods_num'];
				$freight += $so['freight'];
				$so['thumb'] = getThumbImageById($so['goods_ico'],200,200);
			}
			$v['pay_price'] = $pay_price;
			$v['all_goods_num'] = $all_goods_num;
			$v['allfreight'] = $freight;
			
			$v['url'] = $v['url'] =U('Mobile/Shoporder/order_detail',array('order_sn'=>$v['order_sn']));
			$v['order_info'] = $shop_order_info;
        }
        exit(json_encode($shop_order));
    }
	/*查看商城订单详情*/
	public function order_detail($order_sn){
		$order_info = D('shop_ordersn')->where(array('order_sn'=>$order_sn,'uid'=>is_login(),'siteid'=>SITEID))->find();
		$goods_list = D('shop_order_info')->where(array('order_sn'=>$order_sn,'uid'=>is_login(),'siteid'=>SITEID))->select();
		/*******优惠券信息*********/
		$card_info = D('pointcard')->where(array('cardid'=>$order_info['cardid'],'siteid'=>SITEID))->find();
		$amount = $card_info['amount'];
		$endtime = !empty($card_info['endtime']) ? date('Y-m-d H:i:s',$card_info['endtime']) : 0 ;
		$order_info['amount']=$amount;
		$order_info['cardname']=$card_info['typename'];
		/*******优惠券信息结束*********/
		/**************计算商品总价和总数*******************/
		$pay_price = 0;
		$all_goods_num = 0;
		$freight = 0 ;
		foreach($goods_list as $key=>$value){
			if(!$value['freight']){$value['freight']=0;}
			$pay_price += $value['total_price']+$value['freight'];
			$all_goods_num += $value['goods_num'];
			$freight += $value['freight'];
		}
		
		$order_info['pay_price'] = $pay_price;
		$order_info['all_goods_num'] = $all_goods_num;
		$order_info['allfreight'] = $freight;
		/**************计算商品总价和总数结束*******************/
	
		
		$address = get_citys($order_info['consignee_address']);
		$order_info['community']	=	get_city($address['community']);
		$order_info['district']	=	get_city($address['district']);
		$order_info['city']		=	get_city($address['city']);
		$order_info['province']	=	get_city($address['province']);
		
		$order_info['address'] = $order_info['province'].$order_info['city'].$order_info['district'];
		
		

		
		$refund	=	D('shop_order_refund')->where('order_sn="'.$order_sn.'" and refund_status=11')->find();
		if($refund){
			$refund_num	=	D('shop_order_refund')->where('order_sn="'.$order_sn.'" and refund_status=11')->sum('refund_num');
			$refund_price	=	D('shop_order_refund')->where('order_sn="'.$order_sn.'" and refund_status=11')->sum('refund_price');
			$this->assign('refund', $refund);
			$this->assign('refund_num', $refund_num);
			$this->assign('refund_price', $refund_price);
		}
		$this->assign('user', $this->userdata);
		$this->assign('goods_list',$goods_list);
		$this->assign('order_info',$order_info);
		$this->display();
	}




	
	public function pay($order_sn){	
		$uid=is_login();
		$siteid=SITEID;
		$order_info = D('shop_ordersn')->where(array('order_sn'=>$order_sn,'uid'=>is_login(),'siteid'=>SITEID))->find();
		$goods_list = D('shop_order_info')->where(array('order_sn'=>$order_sn,'uid'=>is_login(),'siteid'=>SITEID))->select();
		/*******优惠券信息*********/
		$card_info = D('pointcard')->where(array('cardid'=>$order_info['cardid'],'siteid'=>SITEID))->find();
		$amount = $card_info['amount'];
		$endtime = !empty($card_info['endtime']) ? date('Y-m-d H:i:s',$card_info['endtime']) : 0 ;
		$order_info['amount']=$amount;
		$order_info['cardname']=$card_info['typename'];
		/*******优惠券信息结束*********/
		/**************计算商品总价和总数*******************/
		$pay_price = 0;
		$all_goods_num = 0;
		$freight = 0 ;
		foreach($goods_list as $key=>$value){
			if(!$value['freight']){$value['freight']=0;}
			$pay_price += $value['total_price']+$value['freight'];
			$all_goods_num += $value['goods_num'];
			$freight += $value['freight'];
		}
		
		$order_info['pay_price'] = $pay_price;
		$order_info['all_goods_num'] = $all_goods_num;
		$order_info['allfreight'] = $freight;
		/**************计算商品总价和总数结束*******************/
	
		
		$address = get_citys($order_info['consignee_address']);
		$order_info['community']	=	get_city($address['community']);
		$order_info['district']	=	get_city($address['district']);
		$order_info['city']		=	get_city($address['city']);
		$order_info['province']	=	get_city($address['province']);
		$order_info['address'] = $order_info['province'].$order_info['city'].$order_info['district'];

		$pay_types	 = 	D('Paydeposit')->get_paytype();
		
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		if (strpos($user_agent, 'MicroMessenger') === false) {
			$default_paytypeid = 3;
		} else {
			$default_paytypeid = 4;
		}
		$this->assign('default_paytypeid',$default_paytypeid);
		
		$this->assign('pay_types',$pay_types);
		$this->assign('goods_list',$goods_list);
		$this->assign('order_info',$order_info);
		$this->display();	
	}
	
	
	 public function do_pay()
    {
		$uid=is_login();
		$siteid=SITEID;
		if(IS_POST){
			$order_id = op_t($_POST['order_id']);
			$pay_id = $_POST['pay_type'];
			if(!$pay_id) $this->error('请选择支付方式');
			if(!$order_id) $this->error('订单参数出错，请重试');
			
			//判断订单是否存在，判断支付情况
			$order_attend = D('shop_ordersn')->where(array('order_sn' => $order_id,'siteid'=>SITEID))->find();
			if (!$order_attend) {
				$this->error('订单不存在！');
			}else{
				if($order_attend['pay_status']==2){
					$this->error('本订单已经支付过了，请到详情页查看',U('Mobile/Shoporder/order_detail',array('order_sn'=>$order_attend['order_sn'])));
				}
				//$ispay = check_event_order_ispay($order_attend['status']);
				if($ispay){
					$this->error($ispay);
				}
			}
			//判断商品状态是否正确
			$goods_list = D('shop_order_info')->where(array('order_sn'=>$order_id,'uid'=>is_login(),'siteid'=>SITEID))->select();
			
			
			$pay_price = 0;
			$all_goods_num = 0;
			$freight = 0 ;
			foreach($goods_list as $key=>$value){
				if(!$value['freight']){$value['freight']=0;}
				$pay_price += $value['total_price']+$value['freight'];
				$all_goods_num += $value['goods_num'];
				$freight += $value['freight'];
			}
			
			$order_info['pay_price'] = $pay_price;
			$order_info['all_goods_num'] = $all_goods_num;
			$order_info['allfreight'] = $freight;
			//生成流水号
			$trade_sn = create_sn();
			//获取支付方式
			$payment = D('Paydeposit')->get_payment($pay_id);
			//判断是否为线上支付
			if($payment['is_online'] === '1') {
				$surplus_data['status']  = 'unpay';
				$surplus_data['pay_type']  = 'recharge';
			} else {
				$surplus_data['status']  = 'waitting';
				$surplus_data['pay_type']  = 'offline';
			}
			//支付总金额

			$payprice = $order_info['pay_price'];
			$order_paytype = 0;
			$usernote='【'.get_webinfo('webname').'商城订单号:'.$order_attend['order_sn'].'】'.$order_attend['consignee_address'].$order_attend['create_time'];
			$website_url=$_SERVER['HTTP_HOST'];
			$surplus = array(
					'uid'      => is_login(),
					'username'    => $order_attend['consignee_name'],
					'siteid'    => SITEID,
					'money'       => trim(floatval($payprice)),
					'order_paytype'       => trim(floatval($order_paytype)),
					'quantity'    =>  1,
					'telephone'   => $order_attend['phone'],
					'contactname' => '【'.get_webinfo('webname').'商城订单号:'.$order_attend['order_sn'].'】',
					'email'       => $order_attend['email'],
					'addtime'	  => time(),
					'pay_type'	  => $surplus_data['pay_type'],
					'status'	  => $surplus_data['status'],
					'pay_id'      => $payment['pay_id'],		
					'payment'     => trim($payment['pay_name']),
					'usernote'    => $usernote,
					'trade_sn'	  => $trade_sn,
					'order_type'	  => 1,
					'type' => 2,
					'order_id'	  => $order_id,	
					'website_url'  => $website_url

			);
			
			
			$result=D('Paydeposit')->set_record($surplus);
			if($result){
				$updata['trade_sn'] = $trade_sn;
				D('shop_ordersn')->where(array('order_sn'=>$order_id))->save($updata);
				D('RecordContent')->setuseprice_account($trade_sn);//流水号的余额
				if($pay_id == 5 ){
					
					redirect(U('Pays/Index/index@pay.huodongli.cn',array('trade_sn'=>$trade_sn)));
					
				}else{
					redirect(U('Mobile/Shoporder/pay_redirect',array('trade_sn'=>$trade_sn)));
				}
				
			} else {
				$this->error('操作失败',U('Mobile/Shoporder/pay',array('trade_sn'=>$trade_sn)));
			}
			//dump($surplus);
		}
	
    }	
	
	
	 public function pay_redirect($trade_sn)
	{	
			//$trade_sn = I('trade_sn');
			$factory_info = D('Paydeposit')->get_record($trade_sn);
			if(!$factory_info) $this->error('订单已完成支付或已经取消支付');
			

			$payment = D('Paydeposit')->get_payment($factory_info['pay_id']);

			$cfg = unserialize_config($payment['config']);
			$pay_name = ucwords($payment['pay_code']);

			$pay_fee = pay_fee($factory_info['money'],$payment['pay_fee'],$payment['pay_method']);
			$logistics_fee = $factory_info['logistics_fee'];
			$discount = $factory_info['discount'];

			// calculate amount
			$factory_info['price'] = $factory_info['money'] + $pay_fee + $logistics_fee + $discount;
			
			// add order info
			$order_info['id']	= $factory_info['trade_sn'];
			$order_info['quantity']	= $factory_info['quantity'];
			$order_info['buyer_email']	= $factory_info['email'];
			 $order_info['order_time']	= $factory_info['addtime'];
			
			//add product info
			$product_info['name'] = $factory_info['contactname'];
			$product_info['body'] = $factory_info['usernote'];
			$product_info['price'] = $factory_info['price'];
			
			//add set_customerinfo
			$customerinfo['telephone'] = $factory_info['telephone'];
			if($payment['is_online'] === '1') {
				$payment_handler = new \Pay\Payfactory($pay_name, $cfg);
				$payment_handler->set_productinfo($product_info)->set_orderinfo($order_info)->set_customerinfo($customer_info);
				$code = $payment_handler->get_code('value="进入支付页面" class="btn btn-primary"');	
				$this->assign('code', $code);
			} else {
				$financial_bank =  D('financial')->where(array('siteid'=>SITEID,'status'=>1))->select();
				$this->assign('financial_bank', $financial_bank);
			}
			$this->assign('factory_info', $factory_info);
			$this->display();
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
				$this->error('信息已过期！', U('Mobile/Shopcart/shopcartitem'),3);
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
			
			$goodsmap['id'] = $goods_id;
			$goodsmap['status'] = 1;
			$goodsmap['purchase_status'] = 1;
			/********20150319修改新增*******/
			$distribute_item_goods_ids	=	D('shop_distribute_item_relation')->where('seller_id='.SITEID)->field('goods_id')->select();//查询自己分销的产品
			foreach($distribute_item_goods_ids as $key=>$val){	
				$distribute_goods_ids[$key]=$val['goods_id'];	//分销商品的商品id
			}
			if($distribute_goods_ids){	//若存在分销商品将分销产品条条件加入查询的条件中
				$where['siteid'] = SITEID;
				$where['_logic'] = 'or';
				$where['id']	=	array('in',$distribute_goods_ids);
				$goodsmap['_complex'] = $where;
			}else{
				$goodsmap['siteid'] = SITEID;	//不存在分销商品时
			}
			/********20150319修改新增结束*******/
			/*******商品判定开始*************/
			$goods = D('shop')->where($goodsmap)->find();

			$siteids[$k]	=	$goods['siteid'];
			if(!$goods) $this->error('商品不存在或已被删除');
			$sku_info = D('shop_sku_detailed')->where("goods_id=".$goods_id)->getField("sku_id,sku_title,price,stock");
			if($sku_info){
				if(!$sku_id) $this->error('请选对相关商品配置');
				
				if(!$sku_info[$sku_id]) $this->error('商品配置请重试或联系管理员');
				
				
				$shop_goods_price = $sku_info[$sku_id]['price'];
				$shop_goods_stock = $sku_info[$sku_id]['stock'];
				
			}else{
				$shop_goods_price = $goods['market_price'];
				$shop_goods_stock = $goods['goods_num'];
			}
			if($shop_goods_stock == 0 ) $this->error('本商品已经无库存，请选择其它商品');
			if($goods_num > $shop_goods_stock) $this->error('你的商品数量大于了本配置的库存,本商品配置物品只有'.$shop_goods_stock.'件了');
			
			/*****************查询单价结束，判定部分结束*******************/
			
			$goods_arr	=	D('shop')->where('id='.$goods_id)->find();
			/****商品属性*****/
			$sku_info = D('shop_sku_detailed')->where("goods_id=".$goods_id.' and sku_id='.$sku_id)->getField("sku_id,sku_title,price,stock");
			
			$item[$k]['id']			=	$goods_id;
			$item[$k]['num']		=	$goods_num;
			$item[$k]['price']		=	$shop_goods_price;
			$item[$k]['goods_name']	=	$goods_arr['goods_name'];
			$item[$k]['goods_ico']	=	$goods_arr['goods_ico'];
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
			$money_fr = $money_fr + $item[$k]['a1'];
			$all_totle_price	=	$all_totle_price  +  $shop_goods_price	*	$goods_num + $money_fr;
		}
		$siteids	=	$this->formatsiteid($siteids);

		$this->assign('alltotalprice',$all_totle_price);
		$this->assign('totalnum',$totalnum);
		$this->assign('money_fr',$money_fr);
		$this->assign('shop_address_default',$shop_address_default);
		$this->assign('shop_address',$shop_address);
		$this->assign('ord',$item);
		
		$this->display();
		
	}
	
	/*订单退货信息*/
	public function shop_order_refund($order_sn){
		$order_info = D('shop_ordersn')->where(array('order_sn'=>$order_sn,'uid'=>is_login(),'siteid'=>SITEID))->find();
		$goods_list = D('shop_order_info')->where(array('order_sn'=>$order_sn,'uid'=>is_login(),'siteid'=>SITEID))->select();
		/*******优惠券信息*********/
		$card_info = D('pointcard')->where(array('cardid'=>$order_info['cardid'],'siteid'=>SITEID))->find();
		$amount = $card_info['amount'];
		$endtime = !empty($card_info['endtime']) ? date('Y-m-d H:i:s',$card_info['endtime']) : 0 ;
		$order_info['amount']=$amount;
		$order_info['cardname']=$card_info['typename'];
		/*******优惠券信息结束*********/
		/**************计算商品总价和总数*******************/
		$pay_price = 0;
		$all_goods_num = 0;
		$freight = 0 ;
		foreach($goods_list as $key=>$value){
			if(!$value['freight']){$value['freight']=0;}
			$pay_price += $value['total_price']+$value['freight'];
			$all_goods_num += $value['goods_num'];
			$freight += $value['freight'];
		}
		
		$order_info['pay_price'] = $pay_price;
		$order_info['all_goods_num'] = $all_goods_num;
		$order_info['allfreight'] = $freight;
		
		/**************计算商品总价和总数结束*******************/

		//退款信息
		$refund_list = D('shop_order_refund')->where(array('order_sn'=>$order_sn,'uid'=>is_login(),'siteid'=>SITEID))->select();
		foreach($refund_list as $key=> &$value){
			$refund_goods	=	D('shop_order_info')->where('id='.$value['shop_order_info_id'])->field('goods_name,goods_desc,goods_ico')->find();
			$value['goods_name']	=	$refund_goods['goods_name'];
			$value['goods_desc']	=	$refund_goods['goods_desc'];
			$value['goods_ico']	=	$refund_goods['goods_ico'];
			
			$shop_order_info_ids[] =  $value['shop_order_info_id'];
		}
		
		
		$this->assign('shop_order_info_ids', $shop_order_info_ids);
		$this->assign('user', $this->userdata);
		$this->assign('goods_list',$goods_list);
		$this->assign('refund_list',$refund_list);
		$this->assign('begincity',$begincity);
		$this->assign('order_info',$order_info);
		$this->display();
	}
	
	//增加退款申请
	public function shop_order_refund_detail($order_sn='',$id=''){
		if(IS_POST){
			$id			=	$_POST['id'];
			$order_sn	=	$_POST['order_sn'];
			$refund_num	=	$_POST['refund_num'];
			$reason_select	=	$_POST['reason_select'];
			$refund_reason=	op_t(trim($_POST['refund_reason']));
			if($reason_select==0){
				$this->error('请选择退款原因');
			}
			if($refund_reason==''){
				$this->error('请填写详细说明');
			}
			$goods = D('shop_order_info')->where(array('order_sn'=>$order_sn,'id'=>$id,'siteid'=>SITEID))->find();
			$goods_refund = D('shop_order_refund')->where(array('order_sn'=>$order_sn,'shop_order_info_id'=>$id,'siteid'=>SITEID))->select();
			$order_status	=	D('shop_ordersn')->where(array('order_sn'=>$order_sn))->getField('status');
			if($order_status==33){
				$this->error('订单状态错误',U('Mobile/Shoporder/shop_order_refund',array('order_sn'=>$order_sn)));
			}
			foreach($goods_refund as $key=>$value){
				$has_refund_num	=	$has_refund_num+$value['refund_num'];
			}
			if(!$goods){
				$this->error('订单信息错误');
			}else{
				if($refund_num>$goods['goods_num']-$has_refund_num){
					$this->error('选择的数量超出已选购商品'.$goods['goods_num']);
				}else{
					$supplier_id	=	D('shop_ordersn')->where(array('order_sn'=>$order_sn))->getField('supplier_id');
					$refund['uid']	=	is_login();
					$refund['siteid']	=	SITEID;
					$refund['supplier_id']	=	$supplier_id;
					$refund['order_sn']	=	$order_sn;
					$refund['shop_order_info_id']	=	$id;
					$refund['refund_status']	=	1;
					$refund['create_time']	=	time();
					$refund['refund_num']	=	$refund_num;
					$refund['reason_select']=	$reason_select;
					$refund['refund_reason']	=	$refund_reason;
					if(!$refund['refund_num']){
						$refund['is_refund']	=	0;
						$refund['refund_reason']	=	'';
					}else{
						$refund['is_refund']	=	1;
					}
					
					$updata_refund	=	D('shop_order_refund')->add($refund);
					if($updata_refund){
						$updata_order['status']	=	60;
						$updata_shop_order	=	D('shop_ordersn')->where('order_sn="'.$order_sn.'"')->save($updata_order);
						add_shop_order_log($order_sn,$uid,'','申请退款',time());
						$this->success('提交申请成功',U('Mobile/Shoporder/shop_order_refund',array('order_sn'=>$goods['order_sn'])));
					}else{
						$this->error('提交申请失败',U('Mobile/Shoporder/shop_order_refund',array('order_sn'=>$goods['order_sn'])));
					}
				}
			}
		}else{
			$goods = D('shop_order_info')->where(array('order_sn'=>$order_sn,'id'=>$id,'siteid'=>SITEID))->find();
			$goods_refund = D('shop_order_refund')->where('order_sn="'.$order_sn.'" and shop_order_info_id='.$id.' and siteid='.SITEID.' and refund_status>0')->select();
			foreach($goods_refund as $key=>$value){
				$has_refund_num	=	$has_refund_num+$value['refund_num'];
			}
			$goods['goods_num']	=	$goods['goods_num']-$has_refund_num;
			
			if($goods['goods_num'] <= 0){$this->error('已经没有可退的商品',U('Mobile/Shoporder/shop_order_refund',array('order_sn'=>$goods['order_sn'])));}
			
			$this->assign('goods',$goods);
			$this->display();
		}
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
				$this->error('信息已过期！', U('Mobile/Shopcart/shopcartitem'),3);
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
				if(!$shop_cart) $this->error('购物车内容发生变化，请重新结算', U('Mobile/Shopcart/shopcartitem'),3);
			}
			$siteids[$key]	=	D('shop')->where("id=".$goods_id)->getField('siteid');
		}
		unset($key);
		unset($value);
		
		
		
		//收货地址id
		$address_id	 =	$_POST['reds_id'];
		if(!$address_id) {
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
		
		
		/************优惠券***********************/
		$check_card_use	= $_POST['check_card_use'];
		$cardid			= $_POST['cardid'];
		
		$cardid1 		= $_POST['cardid1'];
			
		switch($check_card_use){
			case 1;
				//$card_info = D('pointcard')->where('cardid="zoyohike41873" and siteid='.$siteid)->find();
				//$card_price = $card_info['amount'];
				$card_price = 0;
			break;
			case 2;
			
				if($cardid == ''){
					 $this->error('亲，既然选择了使用优惠券，就不能为空哦！');
				}else{	
					$cardid=strtoupper($cardid);
					$card_info =D('Common/Pointcard')->check_card($cardid);
					if(!$card_info['status']){
						$this->error($card_info['msg']);
					}else{
						
						$orderlist['cardid'] = $cardid;
						$card_info = D('pointcard')->where('cardid="'.$cardid.'" and siteid='.$siteid)->find();
						$card_price = $card_info['amount'];

					}	
				}
			break;
		
		}
		/************优惠券***********************/
		
		
		$siteids	=	$this->formatsiteid($siteids);
		
		foreach($siteids as $k=>$v){		//
			$order_sn	=	create_sn(); //账号
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
				if($goods_item){
					//if(!$goods_item) $this->error('商品不存在或已被删除');
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
					$seller_price	=	$goods_item['seller_price'];
					$goods_ico		=	$goods_item['goods_ico'];
					$freight		=	$goods_item['fr_freight'];
					$fr_num		=	$goods_item['fr_num'];
					$fr_addnum	=	$goods_item['fr_addnum'];
					$fr_money	=	$goods_item['fr_money'];
					$fr_id		=	$goods_item['fr_id'];
					
					$orderg['goods_id']		 =	 $goods_id;
					$orderg['goods_name'] 	= 	op_t(trim($goods_name));
					$orderg['sku_id']	 = 	$sku_id;
					$orderg['goods_desc'] 	= 	get_shop_types_attribute_names($sku_id);
					$orderg['goods_num']	 = 	$goods_num;
					$orderg['goods_price']	 = 	$shop_goods_price;
					$orderg['total_price']	=	$shop_goods_price*$goods_num;
					$orderg['order_sn'] 	= 	$order_sn;
					$orderg['uid'] 			= 	$uid;
					$orderg['siteid']		 = 	SITEID;
					$orderg['supplier_id'] = $v;
					$orderg['goods_ico']	 = 	$goods_ico;
					$orderg['seller_price']	 = 	$seller_price;
					if($fr_id==0){
						if($goods_num>$fr_num){
							$fr_addnum=($goods_num-$fr_num)/$fr_addnum;
							$orderg['freight']		 = 	$freight+($fr_addnum)*$fr_money;
						}else{	
							$orderg['freight']		 = 	$freight;
						}
					}else{
						$orderg['freight']='0';
					}

					$alltotalprice = $alltotalprice+$shop_goods_price*$goods_num;
					$result = D('shop_order_info') -> add($orderg);  //写入订单商品表
					if($result && $_POST['dosign']){
						$res	=	D('shop_cart')->where('uid='.$uid.' and goods_id='.$goods_id.' and sku_id='.$orderg['sku_id']. ' and siteid='.$siteid)->delete(); 
					}
				}
			}	
			
			//$alltotalprice=$alltotalprice-$card_price;

			if($alltotalprice<=0 && isset($orderlist['cardid']) ){
				$alltotalprice=0;
				$status=21;
				$pay_status=3;        //支付状态：0未支付、2全额、3优惠券支付
			}
			if($result){
				//写入商城订单主表
				$orderlist['order_sn']	=	$order_sn;
				$orderlist['uid']		=	$uid;
				$orderlist['siteid']	=	SITEID;
				$orderlist['supplier_id']	=	$v;			//新增：供货商城
				$orderlist['create_time']	=	time();
				$orderlist['status']		=	$status;
				$orderlist['pay_status']	=	$pay_status;
				$orderlist['alltotalprice']	=	$alltotalprice;
				$orderlist['consignee_name']	=	$consignee_name;
				$orderlist['consignee_address']	=	$consignee_address;
				$orderlist['consignee_address_detailed']	=	$consignee_address_detailed;
				$orderlist['zipcode']	=	$zipcode;
				$orderlist['phone']		=	$phone;
				$orderlist['email']		=	$email;
				$orderlist['postscript']	=	op_t(trim($postscript));
				//$orderlist['confirm_time']=time();
				$result2	=	D('shop_ordersn') -> add($orderlist);//写入商城订单表
		
				if($result2){
					$alltotalprice="";
					/*********订单提交成功更新优惠券状态*********/
					$card_data['status'] = 2;
					
					if($orderlist['cardid'] != ''){	
						$card_data['userid'] = is_login();
						D('pointcard')->where(array('cardid'=>$orderlist['cardid'],'siteid'=>SITEID))->save($card_data);
						$card_user = D('pointcard_user')->where(array('cardid'=>$orderlist['cardid'],'siteid'=>SITEID))->find();
						if(!$card_user){
							$add_card_data['siteid'] = SITEID;
							$add_card_data['cardid'] = $orderlist['cardid'];
							$add_card_data['userid'] = is_login();
							$add_card_data['bindtime'] = time();
							$add_card_data['usetime'] = time();			
							D('pointcard_user')->add($add_card_data);
						}else{
							$update_card_data['usetime'] = time();
							D('pointcard_user')->where(array('cardid'=>$orderlist['cardid'],'siteid'=>SITEID))->save($update_card_data);					
						}
						
						/**********写入日志表*************************/
						add_card_log($orderlist['cardid'],$card_data['status'],'提交商城订单-[使用]','[代金券/活动卡][使用/取消]');
					}
					/********************************************/
					$gm_id	=	D('websit')->where(array('siteid'=>SITEID))->getField('uid');
					D('Message')->sendMessageWithoutCheckSelf($gm_id,query_user('nickname',is_login()).'在商城下了订单，订单号【'.$order_sn.'】。' ,'下单通知', U('/Manage/Order/shop'),is_login());
					reduce_goods_num($order_sn);//减少货物总量
						add_shop_order_log($order_sn,$uid,'','创建商城订单',time());
					$shopdata=array(
						'shop_order_sn'  => $order_sn,
						'execute_time'   => $orderlist['create_time']+1800,
						);
					D('Message')->addSendMessage('shop_order_update','',$shopdata,0,1);	
					//写入商城订单主表 over
				}else{
					$this->error('下单失败');
				}
						
			}else{	////订单商品相关信息shibai
				$this->error('下单失败2');
			}
	
		}	// foreach   siteid   over
		$_SESSION['pay']='';
		$this->success('已下单，等待支付。', U('Mobile/Shoporder/index'));
		
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

     
     /*验证身份证号码*/
     public function check_card($card){
        if($card ==''){
            $this->error('请输入身份证号');
        }
        if(!preg_match("/(^\d{15}$)|(^\d{17}([0-9]|X|x)$)/",$card)){
            $this->error('请输入正确15-18位身份证号码');
        }
     }

}
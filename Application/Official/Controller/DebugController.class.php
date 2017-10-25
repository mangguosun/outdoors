<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Official\Controller;
set_time_limit (0);
use Think\Controller;
/**
 *调试测试--
 */
class DebugController extends OfficialController
{
	
	public function _initialize()
    {
		header("Content-type: text/html; charset=utf-8");
        parent::_initialize();
        if (!is_administrator(is_login())) {
            $this->error('小样,你不是管理员.不能进入此页面');
		}
		$this->userdata = query_user(array('username','nickname', 'signature', 'email', 'mobile', 'avatar128','qq', 'rank_link', 'sex', 'pos_province', 'real_name','self_introduction','pos_city', 'pos_district', 'pos_community','address','constellation'), $uid);
		$this->mTalkModel = D('Talk');
		$this->setTitle('个人中心');
		
	}
	public function index(){
		
		$this->assign('user',$this->userdata);
		$this->display();
	}
	/*执行修改sku数据--*/
	public function repair_sku(){
		$list = D('shop_sku_detailed')->select();

		foreach($list as $key=>$val){
			 $reds[$key] = json_decode($val['sku_title'],true);
			 if(empty($reds[$key])){
				$arr[$val['sku_id']] = explode(",",$val['sku_title']);
			}
		}
		unset($key);
		unset($val);
		
		foreach($arr as $key=>$val){
			foreach($val as $temp){
				$sku_cods_info[$key][]=array('type_id'=>get_shop_sku_types_name($temp),'value'=>$temp);
			}
			$list_info = D('shop_sku_detailed')->where("sku_id=".$key)->save(array('sku_title'=>json_encode($sku_cods_info[$key])));
		}
		exit('yes-成功');
	}
    public function shop_brand_add(){
		
		$str = "阿迪达斯户外|adidas,阿珂姆|ACOME,艾高|AIGLE,遨游仕|OUNCE,奥索卡|ozark,巴塔哥尼亚|patagonia,北面|The North Face,贝豪斯|Berghaus,博格纳|Bogner,布来亚克|BLACK YAK,崔克|Trek,登天|DENTIK,迪卡侬|Decathlon,东丽|toray,多耐福|Duraflex,多特|deuter,戈尔特斯R品牌|GORE-TEX,哥仑步|Kolumb,哥伦比亚|Columbia,格里高利|GREGORY,哈士奇|HASKI,黑钻|Black Diamond,户外吧|HWB,花岗岩|Granite Gear,火柴棍|Haglofs,吉田拉链|YKK,极星|Shehe,捷安特|GIANT,喀尔沃|CARAVA,卡瓦格博|Kawadgarbo,凯乐石|KAILAS,凯瑞摩|karrimor,凯图|k2summit,康尔|KingCamp,科诺.修思|KROCEUS,拉思珀蒂瓦|La sportiva,莱卡|LYCRA,老人头|Norrona,乐飞叶|lafuma,里昂.比恩|L.L.Bean,鲁滨逊|REBOUNSUN,骆驼户外|CAMEL,旅行家|Traveler,美利达|merida,猛犸象|Mammut,牧高笛|MOBIGARDEN,慕士塔格|muztaga,诺可文|ROCVAN,诺诗兰|NORTHLAND,派格|BIGPACK,派乐天|PROTW,攀山鼠|KLATTERMUSEN,普尔兰德|pureland,强氧|STRONG OXYGEN,日高|NIKKO,瑞典北极狐|Fjallraven,瑞速|RBZSOE,萨洛蒙|Salomon,沙乐华|SALEWA,砂岩|SANDSTONE,山浩|Mountain Hardwear,神秘农场|Mystery Ranch,始祖鸟|ARC TERYX,思凯乐|SCALER,泰尼卡|TECNICA,探路者|TOREAD,天伦天|Telent,天石|HIGHROCK,土拨鼠|Marmot,威斯|Vasque,沃德|VAUDE,新保适|Sympatex,亚瑟士|asics,伊凯文|EAMKEVC,伊思佳|YISIJIA,硬骨|HARDBONE,牧高笛|MOBIGARDEN,康尔|KingCamp, |Therm-a-Rest,普尔兰德|pureland, |Exped,杰鹜|JRGEAR,钠丽德|NEXTORCH,奈特科尔| nitecore, |BRUNTON,黑钻|Black Diamond,菲尼克斯|Fenix, |Petzl, |Princeton,山力士|SUNREE,爱赖|i-ray,山瑞|GLAREE,神火|surefire,佳明|Garmin,松拓|Suunto,麦哲仑|Magellan,卡西欧|CASIO,宜准|EZON,十点半|Ten  Thirty, |SCARPA,赞贝拉|Zamberlan,阿索罗|ASOLO,拉思帕蒂瓦|LA  Sportiva,添柏岚|timberland,觅乐|MILLET, |CRISPI, |MEINDL,戈尼尔|GRONELL, |kayland,佳明|Garmin,尼康|NIKON,佳能|Canon,凯乐石|KAILAS,康尔|KingCamp,极星|Shehe,砂岩|SANDSTONE,铁仕|TTISS,骆驼户外|CAMEL,奥索卡|ozark,喀尔沃|CARAVA,天伦添|Telent,哥仑布|Kolumb,山浩|Mountain Hardwear,土拨鼠|Marmot, |rab,比格尼斯|Big Agnes,凯乐石|KAILAS,坎普|C.A.M.P,天石|HIGHROCK, |Sea to Summit, |Therm-a-Rest, |KELTY, |Sierra Designs, |Western  Mountaineeri, |Mountain Equipment, |Stoic,始祖鸟|ARC TERYX,山浩|Mountain Hardwear,猛犸象|Mammut,土拨鼠|Marmot,火柴棍|Haglofs,趣岳|Decathlon, |Westcomb,奥索卡|ozark, |BURTON,哈迪|Halti,奈乔|NITRO,乐飞叶|lafuma,铁仕|TTISS, |peakperformance,阿迪达斯户外|adidas,迪桑特|Descent, |ROSSIGNOL, |Volkl, |phenix, |GOLDWIN,阿尔派妮|ALPINE PRO,巴塔哥尼亚|patagonia,黑钻|Blank Diamond,攀索|Petzl, |Grivel,凯乐石|KAILAS,坎普|C.A.M.P,沙乐华|SALEWA, |DMM,爱德瑞德|Edelrid, |Beal, |MSR,膳魔师|Thermos,鸭嘴兽|Platypus,普里默斯|Primus,美国驼峰|Camelbak, |nalgene,希格|SIGG,溹思|SOURCE,铠斯|Keith,斯坦利|Stanley, |MSR, |BRUNTON,火枫|fire-maple, |Jetboil, |ALOCS,普里默斯|Primus,风雨雪|KOVEA,兄弟 捷登|BRS,野乐|CAMPINGACE,步林|BULIN, |GSI Outdoors, |soto, |Snow Peak, |optimus,勇敢者|Resolutes, |Evernew,巴塔哥尼亚|patagonia,哥伦比亚|Columbia,可隆|KOLON SPORT,快乐狐狸|ActionFox,哈士奇|HASKI,思凯乐|SCALER,布来亚克|BLACK YAK, |phenix";
		
		$arr1 = explode(',',$str);
		foreach($arr1 as $key=>$val){
			$arr2[$key]=explode('|',$val);
			foreach($arr2 as $v){
				foreach($v as $k=>$temp){
					if($k==0){
						$new[$key]['name']=trim($temp);
					}elseif($k==1){
						$new[$key]['englist_name']=trim($temp);
					}
				}
				
			}
		}
		unset($key);
		unset($val);
		unset($k);
		unset($v);
		unset($temp);
		
		foreach($new as $key=>$val){
		  $data['name'] = $val['name'];
		  $data['englist_name'] = $val['englist_name'];
		  $data['ucfirst'] = strtolower(getWords($val['name']));
		  $reds = D('shop_brand_manage')->add($data);
		}
	
		exit('yes-更新成功');	

	}
	/*
	*二维码全站*
	*/
	public function websit_create_qcode($type='websit'){
		
		switch($type){
			case 'websit':
				websit_batch_qcode();
				exit('yes_websit成功生成站点二维码');
			break;
			case 'event':
			    websit_event_qcode();
				exit('yes_event成功生成活动二维码');
			break;
			case 'issue':
			    websit_issue_qcode();
				exit('yes_issue成功生成故事二维码');
			break;
			case 'blog':
			    websit_blog_qcode();
				exit('yes_blog成功生成公告二维码');
			break;
		}
		
	}
   
   
}
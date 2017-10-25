<?php
/**
 * 排行榜  
 * 梁朝阳  
 * 2015-7-6
 */
namespace Mobile\Controller;
use Think\Controller;
class SummaryController extends Controller{
	public function _initialize(){
     $model_info = get_appinfo('Mark');
		if(!$model_info){
			$this->error('应用未开启');
		}
	}
	/**
	 * 排行榜
	 */
	public function index(){
		//日
		$distance='dailydistance';
		$daily=D('Common/Mark')->fordistance($distance);
		$dailydistance_user=$daily[0];
		$dailydistance=$daily[1];
		//周
		$distance='weeklydistance';
		$weekly=D('Common/Mark')->fordistance($distance);
		$weeklydistance_user=$weekly[0];
		$weeklydistance=$weekly[1];
		//月
		$distance='monthlydistance';
		$monthly=D('Common/Mark')->fordistance($distance);
		$monthlydistance_user=$monthly[0];
		$monthlydistance=$monthly[1];
		//打卡次数
		$mark_count=D("Common/Mark")->get_mark_count();		//俱乐部打卡次数及总距离
		//七日运动量
		$seven_ttldistance=D("Common/Mark")->get_seven_ttldistance(is_login());
		for ($i=6; $i >= 0 ; $i--) { 
			$seven_date.="'".date("m-d",strtotime("- $i day"))."',";
		}
		$seven_date=substr($seven_date,0,-1);
		$this->assign('seven_ttldistance',$seven_ttldistance);
		$this->assign('seven_date',$seven_date);
		$this->assign('dailydistance_user',$dailydistance_user);
		$this->assign('dailydistance',$dailydistance);
		$this->assign('weeklydistance_user',$weeklydistance_user);
		$this->assign('weeklydistance',$weeklydistance);
		$this->assign('monthlydistance_user',$monthlydistance_user);
		$this->assign('monthlydistance',$monthlydistance);
		$this->assign('mark_count',$mark_count[0]);
		$this->assign('mark_ttldistance',$mark_count[1]);
		$get_url = json_encode($_GET);
        $this->assign('get_url', $get_url);
	$this->setTitle('排行榜');
		$this->display();
	}
	/**
	 * 异步加载全部打卡列表
	 */
	public function get_chart($page =0,$typecode=""){
		$start = $page*10;
		$getListForSite=D('Common/Mark')->getListForSite($start,$typecode);
		foreach ($getListForSite as &$v) {
           // 打卡类型
            switch ($v['typecode']) {
            	case '1':
            		$v['typetext'] ="跑步";
            		break;
            	case '2':
            		$v['typetext'] ="徒步";
            		break;
            	case '3':
            		$v['typetext'] ="骑行";
            		break;
            	case '4':
            		$v['typetext'] ="游泳";
            		break;
            }
         $v['url'] =U('Mobile/Mark/markfriends',array('userid'=>$v['userid']));
         $v['record']=U('Mobile/Mark/record',array('id'=>$v['id'],'userid'=>$v['userid']));
        //运动距离
     	$v['time']=$v['hour'].':'.$v['minute']."'".$v['second']."''";
     	$v['daka_day']=date('Y-m-d',$v['daka_day']);
	     	if ($v['partnerid']){
                $partner=D('Common/Mark')->get_partner_title($v['partnerid']);
                if(get_ch_en_length($partner[0])>10){
                    $v['yuebanname']=msubstr($partner[0],0,10,"utf-8",true);
                 }else{
                   $v['yuebanname']=$partner[0];
                 }
                $v['thumb'] =getThumbImageById($partner[1],200,150);
            }else{
                $v['yuebanname']='没有最好的活动,只有更好的宝塔镇河妖';
            }       
        }
		exit(json_encode($getListForSite));
	}
	/**
	 * 异步加载打卡次数
	 */
	public function get_count($typecode){
		$mark_count=D("Common/Mark")->get_mark_count($typecode);
			exit(json_encode(array('mark_count'=>$mark_count[0],'mark_ttldistance'=>$mark_count[1])));
	}
}

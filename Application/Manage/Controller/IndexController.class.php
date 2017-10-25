<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;
/*
*网站管理* 2015-1-19 dlx-
*/
class IndexController extends BaseController
{
	/*
	*实例数据统计Model**
	**/
	protected $date_manage;
    public function _initialize()
    {
        parent::_initialize();
		  $this->date_manage = D('Common/DataManage');
	}
     
	 public function config()
    {
        $admin_config = new AdminConfigBuilder();
        $data = $admin_config->handleConfig();

        $admin_config->title('网站基本设置')
            ->keyBool('NEED_VERIFY', '创建菜单是否需要审核','默认无需审核')
            ->buttonSubmit('', '保存')->data($data);
        $admin_config->display();
    }
	public function index(){
      
		$this->assign('event_yesterday_order_add',$this->date_manage->eventOrderStat($day='sevenday'));//7天内新增活动单数
		$this->assign('event_amount',$this->date_manage->eventOrderAmount($day='all'));
		$this->assign('event_amount_people',$this->date_manage->eventOrderAmount($day='all',2));//活动交易金额
		$this->assign('event_nums',$this->date_manage->eventNums($day='all'));//活动总数
		//--商品-
		$this->assign('shop_yesterday_order_add',$this->date_manage->shopOrderStat($day='sevenday'));//7天内新增商品单数
		$this->assign('shop_amount',$this->date_manage->shopOrderStat($day='all',$type=2));
		$this->assign('shop_order_all',$this->date_manage->shopOrderStat($day='all',$type=1));
		$this->assign('shop_nums',$this->date_manage->shopNums($day='all'));
		//-会员-
		$m_level_name=get_upgrading();
		$this->assign('m_level_name',$m_level_name);
		$this->assign('member_yesterday_add',$this->date_manage->MemberNums($is_use='sevenday'));
		$this->assign('member_all',$this->date_manage->MemberNums($is_use='all'));
		$this->assign('member_team',$this->date_manage->MemberNums($is_use=2));
		$this->assign('member_master',$this->date_manage->MemberNums($is_use=4));
		//-待办事项-
	    $this->assign('event_order_halfpay',$this->date_manage->eventDepositOrder());
		$this->assign('event_order_total',$this->date_manage->eventDepositOrder($type='order_total'));
		$this->assign('event_order_succ',$this->date_manage->eventDepositOrder($type='succ'));
		
		$this->assign('shop_processed',$this->date_manage->shopOrderStat($day='processed',$type=1));
		$this->assign('shop_order_all',$this->date_manage->shopOrderStat($day='order_all',$type=1));
		$this->assign('event_tailor_order',$this->date_manage->customOrder($day='month'));
		$this->assign('event_tailor_order_all',$this->date_manage->customOrder($day='all'));
		//--收益--
		$this->assign('cash_balance',$this->date_manage->cashRecord($day='balance'));
		$this->assign('cash_total',$this->date_manage->cashRecord($day='total'));
		$this->assign('comment_news',$this->date_manage->commentNums($day='day'));
		$this->assign('comment_all',$this->date_manage->commentNums($day='all'));
		
		
		
		
		
		$map['siteid'] = 1;
		$map['status'] = 1;
		$map['category_id'] = 1;
		$notice_datainfo	=	D('offcialdocument')->where($map)->order('create_time desc')->limit(0,6)->field('id,title,create_time')->select();
		$this->assign('notice_datainfo',$notice_datainfo); 
	    $this->display();
		
		
    }

}  

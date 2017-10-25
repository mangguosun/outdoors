<?php

namespace Manage\Controller;
use Manage\Builder\AdminConfigBuilder;
use Manage\Builder\AdminListBuilder;
use Manage\Builder\AdminTreeListBuilder;
use Manage\Builder\AdminSortBuilder;

/*配置管理*/
class WebsitSystemController extends BaseController
{
	
    public function _initialize()
    {
        parent::_initialize();
	
	}
	
    public function config()
    {
        $admin_config = new AdminConfigBuilder();
        $data = $admin_config->handleConfig();

        $admin_config->title('系统基本配置')
            ->keyBool('NEED_VERIFY', '视频管理是否需要审核','默认无需审核')
            ->buttonSubmit('', '保存')->data($data);
        $admin_config->display();
    }
	
    public function system($tag='',$province='',$shop_brand='',$index_event_recommend='0',$index_event_new='0',$participant = ''){
		if(IS_POST){

			$rs = addWebsitConfig($tag,'tags');
			$rs_province = addWebsitConfig($province,'province'); //设置省份
			$rs_shop_brand = addWebsitConfig($shop_brand,'shop_brand');
			$rs_index_event_recommend = addWebsitConfig($index_event_recommend,'index_event_recommend');
			$rs_index_event_new = addWebsitConfig($index_event_new,'index_event_new');
			$rs_participant= addWebsitConfig($participant,'participant');
			if($rs || $rs_province || $rs_shop_brand || $rs_index_event_recommend || $rs_index_event_new|| $rs_participant){
				$this->success('操作成功','refresh');
			}else{
				$this->error('未修改数据');
			}
		}else{

			$website_config_tags = getWebsitConfig('tags');
			if(!$website_config_tags){
				
				$website_config_tags = get_event_tag();
				foreach ($website_config_tags as $key => &$val) {
						$website_config_tags_arr_key[] = $key;
				}
				$website_config_tags = implode(',',$website_config_tags_arr_key);
			}
			
			$website_config_province = getWebsitConfig('province');
			if(!$website_config_province){
				$website_config_province = get_province();
				foreach ($website_config_province as $key => &$val) {
					$website_config_province_arr_key[] = $key;
				}	
				$website_config_province = implode(',',$website_config_province_arr_key);
			}
			
			$website_config_shop_brand = getWebsitConfig('shop_brand');
			if(!$website_config_shop_brand){
				$website_config_shop_brand = get_shop_brand();
				foreach ($website_config_shop_brand as $key => &$val) {
					$website_config_shop_brand_arr_key[] = $key;
				}	
				$website_config_shop_brand = implode(',',$website_config_shop_brand_arr_key);
			}
			
			$participant_data = getWebsitConfig('participant');
			if(!$participant_data){
				$participant_data = get_participant();
				foreach ($participant_data as $key => &$val) {
					$participant_data_arr_key[] = $key;
				}	
				$participant_data = implode(',',$participant_data_arr_key);
			}
			
			$index_event_recommend = getWebsitConfig('index_event_recommend',2);
			if($index_event_recommend === ''){
				$index_event_recommend=1;
			}
			

			$index_event_new = getWebsitConfig('index_event_new',2);
			if($index_event_new === ''){
				$index_event_new=1;
			}	
			$this->assign('index_event_recommend',$index_event_recommend);
			$this->assign('index_event_new',$index_event_new);
			
			$this->assign('feature_tags',$website_config_tags);
			$this->assign('feature_province',$website_config_province);
			$this->assign('feature_shop_brand',$website_config_shop_brand);
			$this->assign('participant',$participant_data);
			$this->display();
		}
    }
	
	public function participant($participant = ''){ 
		
		if(IS_POST){ 
			$rs= addWebsitConfig($participant,'participant');

			if($rs){
				$this->success('操作成功','refresh');
			}else{
				$this->error('操作失败');
			}
		}else{ 
			$participant = getWebsitConfig('participant');
			
			if($participant){ 
				$participant_data = $participant;
			}
			$this->assign('participant',$participant_data);
			$this->display();
		}
		
	}
	
}  
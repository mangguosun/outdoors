<?php


namespace People\Controller;

use Think\Controller;


class IndexController extends Controller
{
    /**
     * 业务逻辑都放在 WeiboApi 中
     * @var
     */
    public function _initialize()
    {
    }

    public function index($page = 1)
    {
       $List =  read_people_info_cash();

       if($List){
            $this->assign('leaders', $List['leaders']);
            $this->assign('peoples',$List['peoples']);
            $this->assign('master',$List['master']);//达人

       }else{ 

            $peoples = D('Member')->where('siteid = '.SITEID.' and status=1 and  is_use=1')->order('last_login_time desc')->limit(0,9)->select();
            foreach ($peoples as &$v) {
                $v['user'] = query_user(array('avatar128', 'space_url', 'username', 'fans', 'following', 'signature', 'nickname'), $v['uid']);
                $v['is_leaders'] =0;
            }
            unset($v);
            
            $master = D('Member')->where('siteid = '.SITEID.' and status=1 and is_use=4')->order('last_login_time desc')->limit(0,9)->select();
            foreach ($master as &$v) {
                $v['user'] = query_user(array('avatar128', 'space_url', 'username', 'fans', 'following', 'signature', 'nickname'), $v['uid']);
                $v['is_leaders'] =0;
            }
            
            unset($v);
            
            $leaders = D('Member')->where('siteid = '.SITEID.' and status=1 and ( is_use=2 or is_use = 3)')->order('last_login_time desc')->limit(0,9)->select();
            foreach ($leaders as &$v) {
                $v['user'] = query_user(array('avatar128', 'space_url', 'username', 'fans', 'following', 'signature', 'nickname'), $v['uid']);
                $v['is_leaders'] =1;
            }
            unset($v);
            $peoples_index['leaders'] =  $leaders;
            $peoples_index['peoples'] =  $peoples;
            $peoples_index['master']  =  $master;
            write_people_info_cash($peoples_index);         
            $this->assign('leaders', $leaders);
            $this->assign('peoples', $peoples);
            $this->assign('master',$master);//达人
           

       }

        $m_level_name=get_upgrading();
        $this->assign('m_level_name',$m_level_name);
        $this->display();


		
    }

    public function find($page = 1, $keywords = '', $is_use = '')
    {
        $nickname = op_t($keywords);
        $province =I('province');
     	if($province){ 
     		$citys=D('district')->where("id =".$province)->getField('arrchildid');
     		if($citys){ 
     			$map['address']=array('in',$citys);
     		}
     	}

     	
        if ($nickname != '') {
            $map['nickname'] = array('like','%'.$nickname.'%');
        }
		if ($is_use != '') {
            $map['is_use'] = $is_use;
        }
		
		$map['siteid'] = SITEID;
		$count=D('Member')->where($map)->count();
		$Page       = new \Think\Page($count,18);
		$peoples_page       = $Page->show();// 分页显示输出	
		$peoples = D('Member')->where($map)->order('last_login_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		$str = "<d style='color:red'>".$keywords."</d>";
        foreach ($peoples as &$v) {
            $v['user'] = query_user(array('avatar128', 'space_url', 'username', 'fans', 'following', 'signature', 'nickname'), $v['uid']);
			$v['user']['nickname'] = str_replace($keywords,$str,$v['user']['nickname']);
        }
        unset($v);
        $this->assign('peoples', $peoples);
        $this->assign('nickname',$nickname);
		$this->assign('page',$peoples_page);
        $this->display();
    }
    /**签到排行榜
     * @param int $page
     * @param int $limit
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function ranking($page = 1, $limit = 50)
    {
        $checkInfoModel = D('CheckInfo');
        $memberModel=D('Member');
        if(is_login()){
            //获取用户信息
            $user_info = query_user(array('uid', 'nickname', 'space_url', 'avatar64',), is_login());
            $check_info=$checkInfoModel->where(array('uid'=>is_login()))->find();

            if(!$check_info){
                $check_info['con_num']=0;
                $check_info['total_num']=0;
                $check_info['total_score']=0;
                $check_info['is_sign']=0;
            }else{
                if($check_info['ctime']>=get_some_day(0)){
                    $check_info['is_sign']=1;
                }else{
                    $check_info['is_sign']=0;
                }
            }
            $user_info=array_merge($user_info,$check_info);
            $ranking = $checkInfoModel->field('uid')->order('total_num desc,uid asc')->select();
            $ranking = array_column($ranking, 'uid');
            if(array_search(is_login(), $ranking)===false){
                $user_info['ranking'] = count($ranking) + 1;
            }else{
                $user_info['ranking'] = array_search(is_login(), $ranking) + 1;
            }
            $this->assign('user_info', $user_info);
            //获取用户信息end
        }
        $user_list=D('Member as m')->where(array('m.status'=>1))->field('m.uid,m.nickname,c.total_num,c.con_num,c.total_score,c.ctime')->page($page,$limit)->order('c.total_num desc,m.uid asc')->join('LEFT JOIN __CHECK_INFO__ as c ON c.uid=m.uid')->cache('ranking_list_'.$page,60)->select();
        $totalCount = $memberModel->count();
        $time = get_some_day(0);
        foreach ($user_list as $key => &$val) {
            $val['ranking'] = ($page - 1) * $limit + $key + 1;
            if ($val['ranking'] <= 3) {
                $val['ranking'] = '<span style="color:#EB7112;">' . $val['ranking'] . '</span>';
            }
            if(!$val['total_num']){
                $val['con_num']=$val['total_num']=$val['total_score']=0;
            }
            if ($val['ctime']&&$time <= $val['ctime']) {
                $val['status'] = '<span>已签到</span>';
            } else {
                $val['status'] = '<span style="color: #BDBDBD;">未签到</span>';
            }
        }
        unset($val, $key);
        $this->assign('user_list', $user_list);
        $this->assign('totalCount', $totalCount);
        $this->display();
    }
}
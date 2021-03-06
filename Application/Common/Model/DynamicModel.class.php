<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 想天
 * 创建日期: 3/13/14
 * 创建时间: 7:41 PM
 * 版权所有 想天工作室(www.ourstu.com)
 */

namespace Common\Model;

use Think\Model;

class DynamicModel extends Model
{
    

    /**
     * 注：appname及之后的参数，一般情况下无需填写
     * @param        $to_uid 接受消息的用户ID
     * @param string $content 内容
     * @param string $title 标题，默认为  您有新的消息
     * @param        $url 链接地址，不提供则默认进入消息中心
     * @param int    $from_uid 发起消息的用户，根据用户自动确定左侧图标，如果为用户，则左侧显示头像
     * @param int    $type 消息类型，0系统，1用户，2应用
     * @param string $appname 应用名，默认不需填写，如果填写了就必须实现对应的消息处理模型，例如贴吧里面可以基于某个回复开启聊天
     * @param string $apptype 同上，应用里面的一个标识符
     * @param int    $source_id 来源ID，通过来源ID获取基于XX聊天的来源信息
     * @param int    $find_id 查找ID，通过查找ID获得标识ID
     * @return int
     * @auth 陈一枭
     */
    public function sendMessage($from_uid, $appname = '',  $content = '',  $app_id = 0, $url='' )
    {	

        $this->sendMessageWithoutCheckSelf($from_uid, $appname,  $content ,  $app_id , $url );
        
    }

  

    /**
     * @param $to_uid 接受消息的用户ID
     * @param string $content 内容
     * @param string $title 标题，默认为  您有新的消息
     * @param $url 链接地址，不提供则默认进入消息中心
     * @param $int $from_uid 发起消息的用户，根据用户自动确定左侧图标，如果为用户，则左侧显示头像
     * @param int $type 消息类型，0系统，1用户，2应用
     */
    public function sendMessageWithoutCheckSelf($from_uid, $appname ,  $content,  $app_id , $url )
    {	
    	
        $message['siteid'] = SITEID;
        $message['from_uid'] = $from_uid;
        $message['create_time'] = time();
        $message['appname'] = $appname == '' ? strtolower(MODULE_NAME) : $appname;
		$message['content'] = $content;
		$message['app_id'] = $app_id;
		$message['url'] = $url;
        $message['status'] = 1;
      	
        $rs = D('dynamic_message')->add($message);
        return $rs;
    }


    public function getDynamicMessage($limit = 5){ 

        $map['siteid'] = SITEID;
        $map['status'] = 1;
        $dynamicList =  D('dynamic_message')->where($map)->order('create_time desc')->limit($limit)->select();
        return $dynamicList;
    }

    


} 
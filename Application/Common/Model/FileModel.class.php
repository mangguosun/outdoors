<?php
/**
 * 所属项目 商业版.
 * 开发者: 陈一枭
 * 创建日期: 8/6/14
 * 创建时间: 2:20 PM
 * 版权所有 想天软件工作室(www.ourstu.com)
 */

namespace Common\Model;


class FileModel
{

    /**
     * 文件上传
     * @param  array  $files 要上传的文件列表（通常是$_FILES数组）
     * @param  array  $setting 文件上传配置
     * @param  string $driver 上传驱动名称
     * @param  array  $config 上传驱动配置
     * @return array           文件上传成功后的信息
     */
    /**上传文件，采用Home下的配置文件
     * @return bool
     * @auth 陈一枭
     */
    public function uploadFile($config, $driver, $driver_config)
    {
        C(require_once(APP_PATH . 'Home/Conf/config.php'));
        $config = $config ? $config : C('PICTURE_UPLOAD');
        $driver = $driver ? $driver : C('PICTURE_UPLOAD_DRIVER');
        $pic_driver = C('PICTURE_UPLOAD_DRIVER');
        $driver_config = $driver_config ? $driver_config : C("UPLOAD_{$pic_driver}_CONFIG");
        //TODO: 用户登录检测

        /* 调用文件上传组件上传文件 */
        $Picture = D('Admin/Picture');
        $info = $Picture->upload(
            $_FILES,
            $config,
            $driver,
            $driver_config
        ); //TODO:上传到远程服务器

        /* 记录图片信息 */
        if ($info) {
            return $info;
        } else {
            return false;
        }

    }
} 
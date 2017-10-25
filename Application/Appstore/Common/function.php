<?php
function get_all_compat_version()
{
    return array(
        '20' => '1.5.0',
        '9' => '1.4.0',
        '8' => '1.3.0',
        '7' => '1.2.0',
        '6' => '1.1.0',
        '5' => '1.0.2 V1正式版',
        '4' => '1.0.1 V1RC+1号修复包',
        '3' => '1.0.0 V1RC版',
        '2' => '0.5 周年庆版',
        '1' => '0.1 正式版',
        '-2' => 'Onethink V1.1',
        '-1' => 'Onethink V1.0',
    );
}

function decode_compat($version)
{
    $version_list = get_all_compat_version();
    $result = array();
    foreach ($version as $v) {
        $result[] = $version_list[$v];
    }
    return $result;
}

function decode_compat_to_array_from_db($version)
{
    $version = str_replace('[', '', $version);
    $version = str_replace(']', '', $version);
    $version = explode(',', $version);
    return $version;
}

function decode_compat_by_str($version)
{
    $version = str_replace('[', '', $version);
    $version = str_replace(']', '', $version);
    $version = explode(',', $version);
    $versions = decode_compat($version);
    $return = '';
    foreach ($versions as $tag) {
        $return .= '<span class="label label-success" style="margin:5px">' . $tag . '</span>';
    }
    return $return;
}

function get_type_select($entity = 1)
{

    $type = D('AppstoreType')->where(array('status' => 1, 'entity' => $entity))->select();
    $options = array();
    $options[''] = '- 选择一个插件分类 -';
    foreach ($type as $v) {
        $options[$v['id']] = $v['title'];
    }
    return $options;
}

function display_fee($fee)
{
    if ($fee == 0) {
        return '免费';
    } else {
        return '￥ ' . $fee . ' 元';
    }
}

function display_cover($cover, $width = 90, $height = 90)
{
    if (intval($cover) == 0) {
        $img = C('TMPL_PARSE_STRING.__IMG__');
        echo <<<Eof
 <img class="appstore_cover" src="{$img}/no_icon.png"/>
Eof;
    } else {
        $cover = getThumbImageById($cover, $width, $height);
        echo <<<Eof
<img class="appstore_cover" src = "{$cover}"/>
Eof;

    }
}

function display_download($id, $class = 'btn btn-primary')
{
    if (intval($id) == 0) {
        return '';
    }
    $version = D('AppstoreVersion')->find(intval($id));
    if (intval($version['fee']) != 0) {
        //TODO 判断付费
        return '付费版本';
    }
    if (intval($version['pack']) == 0) {
        return '未上传';
    }
    return '<a class="' . $class . '" href="' . U('index/download', array('id' => $id)) . '" target="_blank">下载</a>';
}

function display_version($version_name)
{
    if (op_t($version_name) == '') {
        return '暂无新版';
    } else {
        return op_t($version_name);
    }
}

function is_tip($uid)
{
    $setting = D('AppstoreDeveloper')->find($uid);
    return !$setting['refuse_message'] || !$setting;
}
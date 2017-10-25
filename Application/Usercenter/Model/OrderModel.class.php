<?php
/**
 * 所属项目 商业版.
 * 开发者: 陈一枭
 * 创建日期: 14-10-9
 * 创建时间: 下午7:26
 * 版权所有 想天软件工作室(www.ourstu.com)
 */

namespace Usercenter\Model;


use Think\Model;

class OrderModel extends Model
{
    protected $tableName = 'recharge_order';

    public function getList($map = array('status' => 1), $order = 'create_time desc', $num = 10)
    {
        $list = $this->where($map)->order($order)->findPage($num);
        return $list;
    }


    public  function getMethod()
    {
        return explode("\n", str_replace("\r", '', modC('METHOD', 'alipay', 'recharge')));
    }


    public  function getFields()
    {
        $fields_config = modC('R_FIELD', "member|score|100|积分\nmember|tox_money|1|金币", 'recharge');
        $fields_array = explode("\n", str_replace("\r", '', $fields_config));
        $fields = array();
        foreach ($fields_array as $v) {
            $field = explode('|', $v);
            $account = D($field[0])->where('uid=' . is_login())->find();
            $have = $account[$field[1]] ? $account[$field[1]] : 0;
            $field['have'] =number_format($have,2);
            $fields[] = $field;

        }
        return $fields;
    }

    /**
     * @param $fields_config
     * @param $field
     * @return bool
     * @auth 陈一枭
     */
    public  function validateField($field)
    {
        $fields_config = $this->getFields();
        $validated_fields = false;
        foreach ($fields_config as $v) {
            if ($field[0] == $v[0] && $field[1] == $v[1]) {
                $validated_fields = true;
                break;
            }
        }
        return $validated_fields;
    }


    public  function getFieldByFieldStr($str)
    {
        $array = explode('|', $str);
        return $this->getFieldByFieldName($array[0], $array[1]);
    }

    public  function getFieldByFieldName($table, $field)
    {
        $fields_config = $this->getFields();
        foreach ($fields_config as $v) {
            if ($table == $v[0] && $field == $v[1]) {
                return $v;
            }
        }
    }

    /**
     * @param $method
     * @auth 陈一枭
     */
    public  function validateMethod($method)
    {
        $method_config = $this->getMethod();
        return in_array($method, $method_config);

    }


    /**获取支付方式
     * @param $aMethod
     * @auth 陈一枭
     */
    public  function getPayMethod($aMethod)
    {
        switch ($aMethod) {
            case 'alipay':
                return '支付宝';


        }
    }

    /**
     * @param $amount
     * @param $method
     * @return array
     * @auth 陈一枭
     */
    public  function create_order($amount, $method, $field)
    {
        $order = array();
        $order['amount'] = $amount;
        $order['method'] = $method;
        $order['field'] = $field;
        $order['uid'] = is_login();
        $order['create_time'] = time();
        $order['status'] = 1;
        $order_id = D('RechargeOrder')->add($order);
        if (!$order_id) {
            $this->error('订单创建失败。');
        }
        return array($order, $order_id);
    }
} 
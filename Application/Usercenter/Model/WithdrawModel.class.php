<?php
/**
 * 所属项目 商业版.
 * 开发者: 陈一枭
 * 创建日期: 2014-11-10
 * 创建时间: 13:08
 * 版权所有 想天软件工作室(www.ourstu.com)
 */

namespace Usercenter\Model;


use Think\Model;

class WithdrawModel extends Model
{
    protected $tableName = 'recharge_withdraw';

    public function getList($map = array('status' => 1), $order = 'create_time desc', $num = 10)
    {
        $list = $this->where($map)->order($order)->findPage($num);
        foreach ($list['data'] as &$v) {
            $v['_field'] = $this->getFieldByFieldStr($v['field']);
            $v['_method'] = $this->getPayMethod($v['method']);
        }
        unset($v);
        return $list;
    }

    /**创建提现
     * @param $amount
     * @param $method
     * @return array
     * @auth 陈一枭
     */
    public function createWithdraw($amount, $method, $field, $account_info)
    {


        $field_array = $this->getFieldByFieldStr($field);
        $f_table = $field_array[0];
        $f_field = $field_array[1];

        //验证账户余额是否充足
        $model = D($f_table);
        if ($model) {
            $user = $model->where(array('uid' => is_login()))->find();
            if (!$user) {
                $this->error = '账户余额为0，无法提现。';
                return false;
            }
            $account = $user[$f_field];
            $withdrawAmount = $amount * $field_array[2];
            if ($account - $withdrawAmount < 0) {
                $this->error = '余额不足，无法提现。提现需' . $withdrawAmount . '，账户余额' . $account;
                return false;
            }
        } else {
            $this->error = '模型不存在。';
            return false;
        }
        //冻结账户余额
        $result = $model->where(array('uid' => is_login()))->setField($f_field, $account - $withdrawAmount);


        if (!$result) {
            $this->error = '冻结账户余额失败。';
            return false;
        }


        $order = array();
        $order['amount'] = $amount;
        $order['method'] = $method;
        $order['field'] = $field;
        $order['uid'] = is_login();
        $order['create_time'] = time();
        $order['status'] = 1;
        $order['account_info'] = $account_info;
        $order['frozen_amount'] = $withdrawAmount;

        $order_id = $this->add($order);


        if (!$order_id) {
            $this->error = '提现订单创建失败。';
            return false;
        }


        return array($order, $order_id);
    }

    /**从文本中提取字段信息
     * @param $str
     * @return mixed
     * @auth 陈一枭
     */
    public function getFieldByFieldStr($str)
    {
        $array = explode('|', $str);
        return $this->getFieldByFieldName($array[0], $array[1]);
    }

    public function getFieldByFieldName($table, $field)
    {
        $fields_config = $this->getWithdrawFields();
        foreach ($fields_config as $v) {
            if ($table == $v[0] && $field == $v[1]) {
                return $v;
            }
        }
    }

    /**获取到提现方式配置项
     * @return array
     * @auth 陈一枭
     */
    public function getWithdrawMethod()
    {
        return explode("\n", str_replace("\r", '', modC('WITHDRAW_METHOD', 'alipay', 'recharge')));
    }

    public function getWithdrawFields()
    {
        $fields_config = modC('WITHDRAW_R_FIELD', "member|score|100|积分\nmember|tox_money|1|金币", 'recharge');
        $fields_array = explode("\n", str_replace("\r", '', $fields_config));
        $fields = array();
        foreach ($fields_array as $v) {
            $field = explode('|', $v);
            $account = D($field[0])->where('uid=' . is_login())->find();
            $have = $account[$field[1]] ? $account[$field[1]] : 0;
            $field['have'] = number_format($have, 2);
            $fields[] = $field;

        }
        return $fields;
    }

    /**验证提现类型
     * @param $fields_config
     * @param $field
     * @return bool
     * @auth 陈一枭
     */
    public function validateWithdrawField($field)
    {
        $fields_config = $this->getWithdrawFields();
        $validated_fields = false;
        foreach ($fields_config as $v) {
            if ($field[0] == $v[0] && $field[1] == $v[1]) {
                $validated_fields = true;
                break;
            }
        }
        return $validated_fields;
    }

    /**验证提现方式
     * @param $method
     * @auth 陈一枭
     */
    public function validateWithdrawMethod($method)
    {
        $method_config = $this->getWithdrawMethod();
        return in_array($method, $method_config);

    }

    /**获取支付方式
     * @param $aMethod
     * @auth 陈一枭
     */
    public function getPayMethod($aMethod)
    {
        switch ($aMethod) {
            case 'alipay':
                return '支付宝';


        }
    }


    /**取消提现
     * @param $id
     * @auth 陈一枭
     */
    public function cancelWithdraw($id)
    {
        if ($id <= 0) {
            $this->error('提现不存在。');
            return false;
        }
        $withdraw = $this->find($id);
        if (!$this->requireExist($withdraw)) {
            return false;
        }
        if ($withdraw['uid'] != get_uid() && !is_administrator()) {
            $this->error = '越权操作。';
            return false;
        }
        //取消订单
        $withdraw['payok'] = -1;
        $rs = $this->save($withdraw);
        if (!$rs) {
            $this->error = '取消订单失败。';
            return false;
        }
        //返还现金
        $this->setFieldInc($withdraw['field'], $withdraw['frozen_amount'], $withdraw['uid']);
        if (!$rs) {
            $withdraw['payok'] = 2;//待返还状态
            $this->save($withdraw);
            $this->error = '返还金额失败。请联系管理员。';
            return false;
        }
        $this->success = '取消订单成功。冻结金额' . $withdraw['frozen_amount'] . '已返还到您的指定账户。';
        return true;

    }

    public function completeWithdraw($id)
    {
        if ($id <= 0) {
            $this->error('提现不存在。');
            return false;
        }
        $withdraw = $this->find($id);
        if (!$this->requireExist($withdraw)) {
            return false;
        }
        if ($withdraw['payok'] != 0) {
            $this->error = '处理的提现订单错误。';
            return false;
        }
        $withdraw['payok'] = 1;
        $withdraw['pay_uid'] = get_uid();
        $withdraw['pay_time'] = time();
        $rs = $this->save($withdraw);
        if (!$rs) {
            $this->error = '完成提现失败。';
            return false;
        }
        D("Common/Message")->sendMessageWithoutCheckSelf($withdraw['uid'], '您的提现已经受理，请注意查收。', '【充值中心】提现完成通知', U('usercenter/recharge/wlists'), is_login());

        return true;
    }

    /**要求操作权限
     * @param $withdraw
     * @return bool
     * @auth 陈一枭
     */
    public function requireExist($withdraw)
    {
        if (!$withdraw) {
            $this->error = '订单不存在。';
            return false;
        }
        return true;
    }


    /**设置某个字段的值增长
     * @param $field
     * @param $value
     * @param $uid
     * @return mixed
     * @auth 陈一枭
     */
    private function setFieldInc($field, $value, $uid)
    {
        $field_array = $this->getFieldByFieldStr($field);
        $f_table = $field_array[0];
        $f_field = $field_array[1];
        $model = D($f_table);
        $result = $model->where(array('uid' => $uid))->setInc($f_field, $value);
        return $result;
    }

    public function getConditionText($payok)
    {
        switch ($payok) {
            case 0:
                return '提现中';
            case 1:
                return '完成';
            case 2:
                return '异常，未退款';
            case -1:
                return '已被取消';
        }

    }
} 
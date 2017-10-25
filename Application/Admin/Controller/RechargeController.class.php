<?php
/**
 * 所属项目 商业版.
 * 开发者: 陈一枭
 * 创建日期: 14-9-23
 * 创建时间: 下午7:28
 * 版权所有 想天软件工作室(www.ourstu.com)
 */

namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Think\Controller;

class RechargeController extends Controller
{

    public function recharge()
    {
        $configBuilder = new AdminConfigBuilder();
        $data = $configBuilder->handleConfig();
        if (!$data) {
            $data['OPEN_RECHARGE'] = 1;
            $data['RECHARGE_AMOUNT'] = "50\n100\n200\n500";
            $data['CAN_INPUT'] = 1;
            $data['MIN_AMOUNT'] = 0;
            $data['METHOD'] = 'alipay';
            $data['R_FIELD'] = "member|score|100|积分\nmember|tox_money|1|金币";


        }
        $configBuilder->title('充值设置')
            ->keyBool('OPEN_RECHARGE', '开启充值')
            ->keyTextArea('RECHARGE_AMOUNT', '充值面额', '一行一个')
            ->keyBool('CAN_INPUT', '允许自由充值')
            ->keyText('MIN_AMOUNT', '最小充值面额，0为不限制，只对自由充值开启有效')
            ->keyCheckBox('METHOD', '支付方式', '选择支付种类，可能需要不同的支付插件支持', array('alipay' => '支付宝(需安装支付宝插件)'))
            ->keyTextArea('R_FIELD', '充值到的字段', '要求被充值字段所在表有uid作为用户标识，一行一个，用竖线分隔，格式参考：表名|字段|兑率|字段显示名，兑率表示1元人民币兑换多少')
            ->buttonSubmit()
            ->buttonBack()
            ->data($data);
        $configBuilder->display();
    }

    public function withdraw()
    {
        $configBuilder = new AdminConfigBuilder();
        $data = $configBuilder->handleConfig();
        if (!$data) {
            $data['OPEN_WITHDRAW'] = 0;
            $data['WITHDRAW_AMOUNT'] = "50\n100\n200\n500";
            $data['WITHDRAW_CAN_INPUT'] = 1;
            $data['WITHDRAW_MIN_AMOUNT'] = 0;
            $data['WITHDRAW_METHOD'] = 'alipay';
            $data['WITHDRAW_R_FIELD'] = "member|score|100|积分\nmember|tox_money|1|金币";
            $data['OPEN_WITHDRAW'] = 0;
        }
        $configBuilder->title('提现设置')
            ->keyBool('OPEN_WITHDRAW', '开启提现')
            ->keyTextArea('WITHDRAW_AMOUNT', '提现面额', '一行一个')
            ->keyBool('WITHDRAW_CAN_INPUT', '允许自由提现')
            ->keyText('WITHDRAW_MIN_AMOUNT', '最小提现面额，0为不限制，只对自由提现开启有效')
            ->keyCheckBox('WITHDRAW_METHOD', '提现方式', '选择提现种类', array('alipay' => '支付宝'))
            ->keyTextArea('WITHDRAW_R_FIELD', '允许提现的字段', '要求被提现字段所在表有uid作为用户标识，一行一个，用竖线分隔，格式参考：表名|字段|兑率|字段显示名，兑率表示多少兑换1元人民币，即与充值相同即可')
            ->buttonSubmit()
            ->buttonBack()
            ->data($data);
        $configBuilder->display();
    }

    public function lists($r = 15, $p = 1)
    {
        $aBuyerEmail = I('buyer_email', '', 'op_t');
        if ($aBuyerEmail != '') {
            $map['buyer_email'] = array('like', '%' . $aBuyerEmail . '%');
        }
        $listBuilder = new AdminListBuilder();
        $recordModel = D('recharge_record_alipay');
        $data = $recordModel->where($map)->order('notify_time desc')->page($p, $r)->select();
        $totalCount = $recordModel->where($map)->count();
        foreach ($data as &$v) {
            $v['is_success'] = $v['is_success'] == 'T' ? 1 : 0;

        }
        unset($v);
        $listBuilder->keyId()->keyText('out_trade_no', '订单编号')->keyText('buyer_email', '付款人支付宝')->keyText('seller_email', '收款账户')
            ->keyText('total_fee', '充值金额')->keyText('trade_no', '支付宝订单号')->keyBool('is_success', '支付成功')->keyTime('notify_time', '付款时间');
        $listBuilder->search('付款人支付宝', 'buyer_email');
        $listBuilder->data($data)->pagination($totalCount, $r);
        $listBuilder->display();
    }

    public function tlists($r = 15, $p = 1)
    {
        $listBuilder = new AdminListBuilder();
        $recordModel = D('recharge_order');
        $data = $recordModel->order('create_time desc')->page($p, $r)->select();
        $totalCount = $recordModel->count();

        unset($v);
        $listBuilder->keyId()->keyText('field', '充值字段')->keyText('amount', '充值金额')->keyText('method', '充值方式')
            ->keyUid()->keyCreateTime()->keyStatus()->keyText('record_id', '关联的支付记录ID')->keyBool('payok', '付款成功');
        $listBuilder->data($data)->pagination($totalCount, $r);
        $listBuilder->display();
    }

    public function wlists($r = 15, $p = 1)
    {
        $listBuilder = new AdminListBuilder();
        $recordModel = D('Usercenter/Withdraw');
        $data = $recordModel->order('create_time desc')->page($p, $r)->select();
        $totalCount = $recordModel->count();
        foreach ($data as &$v) {
            $v['pay_condition'] = $recordModel->getConditionText($v['payok']);
            if ($v['pay_uid'] != 0) {
                $user = query_user(array('space_link'), $v['pay_uid']);
                $v['operator'] = $user['space_link'];
            } else {
                $v['operator'] = '-';
            }
            $v['pay_time'] = $v['pay_time'] == 0 ? '-' : $v['pay_time'];
        }
        unset($v);
        $listBuilder->keyId()->keyText('field', '提现字段')->keyText('amount', '提现金额')->keyText('frozen_amount', '冻结金额')->keyUid()->keyText('method', '提现方式')
            ->keyCreateTime()->keyText('pay_condition', '支付状态')->keyText('operator', '操作者')->keyTime('pay_time', '提现操作时间')->keyText('account_info', '收款账户信息');
        $listBuilder->data($data)->pagination($totalCount, $r);


        $listBuilder->ajaxButton(U('recharge/dowithdraw'), null, '提现');
        $listBuilder->ajaxButton(U('recharge/cancelwithdraw'), null, '关闭提现');
        $listBuilder->display();
    }


    public function dowithDraw($ids = array())
    {
        $withdrawModel = D('Usercenter/Withdraw');
        foreach ($ids as $id) {
            $rs = $withdrawModel->completeWithdraw($id);

        }

        //提现成功，向用户发送消息

        $this->success('提现成功。');
    }

    public function cancelWithdraw($ids = array())
    {
        $withdrawModel = D('Usercenter/Withdraw');
        foreach ($ids as $id) {
            $rs = $withdrawModel->cancelWithdraw($id);

        }
        $this->success('关闭订单成功。');

    }
} 
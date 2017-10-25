<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 想天
 * 创建日期: 3/12/14
 * 创建时间: 12:49 PM
 * 版权所有 想天工作室(www.ourstu.com)
 */

namespace Usercenter\Controller;


use Think\Controller;
use Think\Page;
use Usercenter\Model\OrderModel;
use Usercenter\Model\WithdrawModel;

class RechargeController extends BaseController
{
    protected $mOrderModel;
    protected $mWithdrawModel;

    public function _initialize()
    {
        if (!modC('OPEN_RECHARGE', 1, 'recharge')) {
            $this->error('系统未开启充值接口。');
        }
        $this->assign('tabHash','recharge');
        $this->mOrderModel = new OrderModel();
        $this->mWithdrawModel = new WithdrawModel();
        parent::_initialize();

    }

    /**充值表单
     * @auth 陈一枭
     */
    public function recharge()
    {

        C('TOKEN_ON', true);
        C('TOKEN_RESET', true);
        $amount = explode("\n", str_replace("\r", '', modC('RECHARGE_AMOUNT', "50\n100\n200\n500", 'recharge')));
        $method = $this->mOrderModel->getMethod();

        $fields = $this->mOrderModel->getFields();
        $this->assign('fields', $fields);
        $this->assign('method', $method);
        $this->assign('amount', $amount);

        $this->assign('tab', 'recharge');
        $this->display();
    }

    /**从提交表单中获取到订单
     * @return mixed
     * @auth 陈一枭
     */
    private function getOrderFromPost()
    {
        C('TOKEN_ON', true);
        C('TOKEN_RESET', true);
        if (!D('')->autoCheckToken($_POST)) {
            $this->error('请不要重复提交。');
        }
        $aAmount = I('post.amount', 0, 'floatval');
        $aAmount = number_format($aAmount, 2, ".", ""); //去除两位小数后的数额
        $minAmount = modC('MIN_AMOUNT', 0, 'recharge');
        if ($aAmount <= 0) {
            $this->error('充值金额不能小于等于0。');
        }
        $canInput = modC('CAN_INPUT', 1, 'recharge');
        if ($aAmount <= $minAmount && $canInput && $minAmount!=0) {
            $this->error('充值金额不能小于' . $minAmount . '。');
        }
        $aMethod = I('post.method', '', 'op_t');
        $aField = I('post.field', '', 'op_t');

        $field = explode('|', $aField);

        if (!$this->mOrderModel->validateField($field)) {
            $this->error('不允许的充值类型。');
        }
        if (!$this->mOrderModel->validateMethod($aMethod)) {
            $this->error('不存在的支付方式。');
        }


        list($order, $order_id) = $this->mOrderModel->create_order($aAmount, $aMethod, $aField);

        if (!$order_id) {
            $this->error('订单创建失败。');
        }
        $order['id'] = $order_id;
        $this->assign('field', $this->mOrderModel->getFieldByFieldStr($aField));
        $this->assign('pay_method', $this->mOrderModel->getPayMethod($aMethod));
        return $order;


    }

    /**订单确认
     * @auth 陈一枭
     */
    public function order()
    {
        $aOrderId = I('get.id', 0, 'intval');
        if ($aOrderId != 0) {
            $order = D('Order')->find($aOrderId);
            $aMethod = $order['method'];
            $this->assign('pay_method', $this->mOrderModel->getPayMethod($aMethod));
            $this->assign('field', $this->mOrderModel->getFieldByFieldStr($order['field']));

        } else {
            $order = $this->getOrderFromPost();
        }
        $this->assign('order', $order);
        $this->assign('tab', 'recharge');
        $this->display('order' . $order['method']);

    }

    /**充值列表
     * @auth 陈一枭
     */
    public function lists()
    {
        $list = D('Order')->getList(array('uid' => get_uid(), 'status' => 1, 'field' => array('neq', '')));
        foreach ($list['data'] as &$v) {
            $v['_field'] = $this->mOrderModel->getFieldByFieldStr($v['field']);
            $v['_method'] = $this->mOrderModel->getPayMethod($v['method']);
        }
        unset($v);
        $this->assign('list', $list);
        $this->assign('tab', 'lists');
        $this->display();
    }

    /**充值列表
     * @auth 陈一枭
     */
    public function wlists()
    {
        $list = D('Withdraw')->getList(array('uid' => get_uid(), 'status' => 1, 'field' => array('neq', '')));
        $this->assign('list', $list);
        $this->assign('tab', 'wlists');
        $this->display();
    }


    public function cancelWithdraw()
    {
        $aId = I('post.id', 0, 'intval');
        //取消提现，可以是管理员或者当事人
        $withdrawModel = D('Withdraw');
        $result = $withdrawModel->cancelWithdraw($aId);
        if ($result) {
            $this->success($withdrawModel->success);
        }

        if (!$result) {
            $this->error($withdrawModel->getError());
        }

    }

    /**支付宝付款确认
     * @auth 陈一枭
     */
    public function alipayok()
    {
        require_once(ONETHINK_ADDON_PATH . '/AliPlay/Play/jishi/alipay.config.php');
        require_once(ONETHINK_ADDON_PATH . '/AliPlay/Play/jishi/lib/alipay_notify.class.php');
        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();
        if ($verify_result) { //验证成功

            $alipayRecordModel = D('recharge_record_alipay');
            $record['body'] = I('get.body', '未获取', 'op_t');
            $record['buyer_email'] = I('get.buyer_email', '未获取', 'op_t');
            $record['buyer_id'] = I('get.buyer_id', '未获取', 'op_t');
            $record['exterface'] = I('get.exterface', '未获取', 'op_t');
            $record['is_success'] = I('get.is_success', '未获取', 'op_t');
            $record['notify_id'] = I('get.notify_id', '未获取', 'op_t');
            $record['notify_time'] = I('get.notify_time', '0', 'strtotime');
            $record['notify_type'] = I('get.notify_type', '未获取', 'op_t');
            $record['out_trade_no'] = I('get.out_trade_no', '未获取', 'op_t');
            $record['payment_type'] = I('get.payment_type', '未获取', 'op_t');
            $record['seller_email'] = I('get.seller_email', '未获取', 'op_t');
            $record['seller_id'] = I('get.seller_id', '未获取', 'op_t');
            $record['subject'] = I('get.subject', '未获取', 'op_t');
            $record['total_fee'] = I('get.total_fee', '未获取', 'op_t');
            $record['trade_no'] = I('get.trade_no', '未获取', 'op_t');
            $record['trade_status'] = I('get.trade_status', '未获取', 'op_t');
            $record['sign'] = I('get.sign', '未获取', 'op_t');
            $record['sign_type'] = I('get.sign', '未获取', 'op_t');
            if (!$rs = $alipayRecordModel->add($record)) {
                $this->error('保存支付结果失败。请联系管理员。');
            };
            //商户订单号
            $order_id = $record['out_trade_no'];
            //支付宝交易号
            $trade_no = $_GET['trade_no'];

            //交易状态


            if ($record['trade_status'] == 'TRADE_FINISHED' || $record['trade_status'] == 'TRADE_SUCCESS') {
                $rechargeModel = D('recharge_order');
                $order = $rechargeModel->find($record['out_trade_no']);
                if ($order['record_id'] == 0) {
                    //未作处理
                    if (!$order['amount'] == $record['total_fee']) {
                        $this->error('付款订单出错，数额与订单不符，付款失败。请联系管理员。');
                    }
                    $order['record_id'] = $rs;
                    if (!$rechargeModel->save($order)) {
                        $this->error('更改订单状态失败。');
                    };
                    $field = $order['field'];
                    $field = explode('|', $field);
                    if (!$this->mOrderModel->validateField($field)) {
                        $this->error('充值字段合法性验证失败，请联系管理员。');
                    }
                    $table = $field[0];
                    $field = $field[1];
                    $convention = $this->mOrderModel->getFieldByFieldName($table, $field); //获得兑率
                    $inc = $convention[2];
                    $name = $convention[3];
                    //加分
                    $step = floor($order['amount'] * $inc);
                    $tableModel = D($table);
                    $account = $tableModel->where(array('uid' => is_login()))->find();
                    if ($account) {
                        if ($tableModel->where(array('uid' => is_login()))->setInc($field, $step)) {
                            $order['payok'] = 1;
                            $rechargeModel->save($order);
                            $this->success('充值成功。您的' . $name . ' 增加 ' . $step . '。即将跳转回充值页面。', U('usercenter/recharge/recharge'), 15);
                        } else {
                            $this->error('支付成功，但充值到数据库失败。请联系管理员。');
                        }
                    } else {
                        $account = array();
                        $account['uid'] = is_login();
                        $account[$field] = $step;
                        $res = $tableModel->add($account);
                        if ($res) {
                            $this->success('充值成功。您的' . $name . ' 增加 ' . $step . '。即将跳转回充值页面。', U('usercenter/recharge/recharge'), 15);
                        } else {
                            $this->error('支付成功，但充值到数据库失败。请联系管理员。');
                        }
                    }

                } else {
                    $this->error('该订单已经支付，请勿重复支付。');
                }

                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序

            } else {
                $this->error('支付状态出错。' . $record['trade_status']);
            }
        } else {
            $this->error('验证失败。');
        }
    }


    /**从提交表单中获取到订单
     * @return mixed
     * @auth 陈一枭
     */
    private function getWithdrawFromPost()
    {
        C('TOKEN_ON', true);
        C('TOKEN_RESET', true);
        if (!D('')->autoCheckToken($_POST)) {
            $this->error('请不要重复提交。');
        }
        $aAmount = I('post.amount', 0, 'floatval');
        $aAmount = number_format($aAmount, 2, ".", ""); //去除两位小数后的数额

        if ($aAmount <= 0) {
            $this->error('提现金额不能小于等于0。');
        }
        $canInput = modC('WITHDRAW_CAN_INPUT', 1, 'recharge');
        $minAmount = modC('WITHDRAW_MIN_AMOUNT', 0, 'recharge');
        if ($canInput && $aAmount < $minAmount && $minAmount!=0) {
            $this->error('最小提现金额不能小于' . $minAmount . '。');
        }
        $aMethod = I('post.method', '', 'op_t');
        $aField = I('post.field', '', 'op_t');
        $aAccountInfo = I('post.account_info', '', 'op_t');
        if (strlen($aAccountInfo) <= 0) {
            $this->error('请填写完整的收款信息。');
        }
        $field = explode('|', $aField);

        if (!$this->mWithdrawModel->validateWithdrawField($field)) {
            $this->error('不允许的提现类型。');
        }
        if (!$this->mWithdrawModel->validateWithdrawMethod($aMethod)) {
            $this->error('不存在的提现方式。');
        }


        list($order, $order_id) = $this->mWithdrawModel->createWithdraw($aAmount, $aMethod, $aField, $aAccountInfo);

        if (!$order_id) {
            $this->error($this->mWithdrawModel->getError());
        }
        //     if (!$order_id) {
        //        $this->error('提现订单创建失败。');
        //     }
        //     $order['id'] = $order_id;
        //     $this->assign('field', $this->mOrderModel->getFieldByFieldStr($aField));
        //   $this->assign('pay_method', $this->mOrderModel->getPayMethod($aMethod));
        return $order;


    }

    /**提现
     * @auth 陈一枭
     */
    public function withdraw()
    {

        if (IS_POST) {
            $withdraw = $this->getWithdrawFromPost();
            $this->success('提现成功。即将跳转到提现列表页。', U('wlists'));
        } else {
            if (modC('OPEN_WITHDRAW', 0, 'recharge') == 0) {
                $this->error('404未找到。');
            }
            $this->assign('tab', 'withdraw');
            C('TOKEN_ON', true);
            C('TOKEN_RESET', true);
            $amount = explode("\n", str_replace("\r", '', modC('WITHDRAW_AMOUNT', "50\n100\n200\n500", 'recharge')));
            $method = $this->mWithdrawModel->getWithdrawMethod();

            $fields = $this->mWithdrawModel->getWithdrawFields();

            $this->assign('fields', $fields);
            $this->assign('method', $method);
            $this->assign('amount', $amount);
            $this->display();
        }

    }


}
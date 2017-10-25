<?php
namespace Store\Model;

use Think\Model;

/**
 * 所属项目 商业版.
 * 开发者: 陈一枭
 * 创建日期: 14-10-10
 * 创建时间: 上午9:06
 * 版权所有 想天软件工作室(www.ourstu.com)
 */
class CurrencyModel extends Model
{
    protected $tableName = 'store_currency';
    protected $mField = 'currency';

    protected function _initialize()
    {
        parent::_initialize();
        //获取到货币配置
        $this->mField = modC('CURRENCY_FIELD', 'currency', 'store');
    }

    /**获得表名
     * @return string
     * @auth 陈一枭
     */
    public function getTableName()
    {
        return C('DB_PREFIX') . modC('CURRENCY_TABLE', 'store_currency', 'store');
    }

    /**获得某个用户的ID
     * @param int $uid
     * @return string
     * @auth 陈一枭
     */
    public function getCurrency($uid = 0)
    {
        if ($uid == 0) {
            $uid = get_uid();
        }
        $currency = $this->where(array('uid' => $uid))->find();
        if (!$currency[$this->mField]) {
            return '0';
        }
        return $currency[$this->mField];
    }

    /**获得当前货币字段
     * @return mixed|string
     * @auth 陈一枭
     */
    public function getField()
    {
        return modC('CURRENCY_FIELD', 'currency', 'store');
    }

    /**付款
     * @param int $price
     * @param int $uid
     * @return bool
     * @auth 陈一枭
     */
    public function  pay($price = 0, $uid = 0)
    {
        if ($price <= 0) {
            $this->error = '支付价格小于0元';
            return false;
        }
        $currency = $this->getCurrency($uid);
        $hasLeft = $currency - $price;
        if ($hasLeft >= 0) {
            return $this->adjust(-$price, $uid);
        } else {
            $this->error = '余额不足。';
            return false;
        }
    }

    /**调整积分，允许增减
     * @param int $amount
     * @param int $uid
     * @return bool
     * @auth 陈一枭
     */
    public function adjust($amount = 0, $uid = 0)
    {
        if ($uid == 0) {
            $uid = is_login();
        }
        if ($amount == 0) {
            $this->error = '调整数值为0';
            return false;
        }
        $currency = $this->find($uid);
        if (!$currency) {
            $rs = $this->add(array('uid' =>$uid , 'currency' => $amount));
            if ($rs) {
                return true;
            } else {
                $this->error = '创建账户失败。';
                return false;
            }
        } else {
            $result = $this->where(array('uid' => $uid))->setInc($this->getField(), $amount);

            if ($result) {
                return true;
            } else {
                $this->error = '设置货币失败。';
                return false;
            }
        }


    }
}
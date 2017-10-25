<?php
namespace Store\Controller;

use Think\Controller;
class StoreController extends Controller
{

    protected $view = null;
    protected $APP_NAME = '';

    public function  _initialize()
    {
    }

    public function ajaxReturnS()
    {
        $this->ajaxReturn(null, null, 1);
    }

    public function ajaxReturnF()
    {
        $this->ajaxReturn(null, null, 0);
    }

    public function quickReturn($rs)
    {
        if ($rs) {
            $this->ajaxReturnS();
        } else {
            $this->ajaxReturnF();
        }
    }
}
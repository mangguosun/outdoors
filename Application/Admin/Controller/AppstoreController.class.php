<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM5:41
 */

namespace Admin\Controller;

use Admin\Builder\AdminListBuilder;

class AppstoreController extends AdminController
{
    protected $mAdminListBuilder;

    protected $mGoodsModel;

    public function _initialize()
    {
        $this->mAdminListBuilder;
        $this->mGoodsModel = D('AppstoreGoods');
    }

    public function index($page = 1, $r = 20, $content = '')
    {
        redirect(U('verify'));
    }

    public function goods($page = 1, $r = 20)
    {
        //TODO 支持搜索
        $this->mAdminListBuilder = new AdminListBuilder();
        $goods = $this->mGoodsModel->where('status > -1')->page($page, $r)->select();


        $this->mAdminListBuilder->title('商品审核')
            ->buttonSetStatus(U('Appstore/setStatus'), 2, '设为待审核', array())

            ->search('搜索', 'title', null, '支持标题')
            ->keyLink('title', '名称', 'appstore/edit?id=###')->keyUid()->keyCreateTime()->keyUpdateTime()
            ->data($goods)->display();
    }

    /**审核商品
     * @param int $page
     * @param int $r
     * @auth 陈一枭
     */
    public function verify($page = 1, $r = 20)
    {
        //TODO 支持搜索
        $this->mAdminListBuilder = new AdminListBuilder();
        $goods = $this->mGoodsModel->where(array('status' => 2))->page($page, $r)->select();


        $this->mAdminListBuilder->title('商品审核')
            ->buttonSetStatus(U('Appstore/setStatus'), 1, '审核通过', array())
            ->search('搜索', 'title', null, '支持标题')
            ->keyLink('title', '名称', 'appstore/edit?id=###')->keyUid()->keyCreateTime()->keyUpdateTime()
            ->data($goods)->display();
    }

    /**
     * @auth 陈一枭
     */
    public function trash($page = 1, $r = 20)
    {
        $this->mAdminListBuilder = new AdminListBuilder();
        $goods = $this->mGoodsModel->where(array('status' => -1))->page($page, $r)->select();


        $this->mAdminListBuilder->title('商品审核')
            ->buttonSetStatus(U('Appstore/setStatus'), 1, '审核通过', array())
            ->search('搜索', 'title', null, '支持标题')
            ->keyLink('title', '名称', 'appstore/edit?id=###')->keyUid()->keyCreateTime()->keyUpdateTime()
            ->data($goods)->display();
    }


    /**设置商品状态，用于审核通过
     * @param int $ids id，表单自动获取
     * @param int $status 默认为正常状态1
     * @auth 陈一枭
     */
    public function setStatus($ids = 0, $status = 1)
    {
        if ($ids == 0) {
            $this->error('请选择商品');
        }
        $appstoreModel=D('AppstoreGoods');
        $weiboModel=D('Weibo/weibo');
        $map['id'] = array('in', implode(',', $ids));
        $rs = $this->mGoodsModel->where($map)->setField('status', $status);
        if($status==1){
            foreach($ids as $id){
                $goods=$appstoreModel->find($id);
                $user=query_user(array('nickname'),$goods['uid']);
                $weibo_content = '管理员审核通过了@' . $user['nickname'] . ' 的商品：【' . op_t($goods['title']) . '】，快去看看吧：' ."http://$_SERVER[HTTP_HOST]" .U('appstore/index/goodsDetail',array('id'=>$goods['id']));
                $weiboModel->addWeibo(is_login(),$weibo_content,'feed',null,'云平台');
            }
        }

        $this->success('成功设置' . $rs . '件商品的状态。');
    }


}

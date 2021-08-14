<?php

namespace app\admin\controller;

use think\Controller;
use think\facade\Request;
use think\facade\View;
use app\common\model\Daili as DailiModel;
View::share(['siteName' => getSiteName()]);
class Daili extends Controller
{
    protected $middleware = ['AdminCheck'];

    /**
     * 代理等级表
     * @return mixed
     */
    public function dailiLevel()
    {
        return $this->fetch();
    }

    /**
     * 获取代理信息
     * @return array
     */
    public function getDailiInfo()
    {
        $Daili = DailiModel::order('id','asc')->select();
        return ['code'=>0,'msg'=>'获取成功','data'=>$Daili];
    }

    /**
     * 修改代理信息
     * @return \think\response\Json|void
     */
    public function setDaili()
    {
        if (Request::isPost())
        {
            $id = Request::post('id');
            $field = Request::post('field');
            $value = Request::post('value');
            DailiModel::update([$field=>$value],['id'=>$id]);
            return msg(0,'修改成功');
        }
    }
}
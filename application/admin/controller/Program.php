<?php

namespace app\admin\controller;

use think\Controller;
use think\facade\Request;
use think\facade\View;
use app\common\model\Server;
use app\common\model\Program as ProgramModel;

View::share(['siteName' => getSiteName()]);

class Program extends Controller
{
    protected $middleware = ['AdminCheck'];

    /**
     * 程序列表
     * @return mixed
     */
    public function programList()
    {
        $server = new Server();
        $list = $server->field('id,name,realname')->select();
        $this->assign([
            'server' => $list
        ]);
        return $this->fetch();
    }

    /**
     * 获取程序列表
     * @return array|void
     */
    public function getProgram()
    {
        if (Request::isPost()) {
            if (Request::param('serverId')) {
                $program = new ProgramModel();
                $data = $program->where('server_id', Request::post('serverId'))->field('id,productId,name,install,price')->select();
                return ['code' => 0, 'msg' => '获取成功', 'data' => $data];
            }
            return ['code' => 0, 'msg' => '获取成功', 'data' => [], 'count' => 0];
        }
    }

    /**
     * 添加程序
     * @return mixed|\think\response\Json
     */
    public function addProgram()
    {
        if (Request::isPost()) {
            $serverId = Request::post('serverId');
            $productId = Request::post('productId');
            $name = Request::post('name');
            $install = Request::post('install');
            $price = Request::post('price');
            $program = new ProgramModel();
            $check = $program->where('install', $install)->find();
            if ($check) {
                return msg(1, '程序存在');
            }
            $array = [
                'server_id' => $serverId,
                'productId' => $productId,
                'name' => $name,
                'install' => $install,
                'price' => $price
            ];
            $program->save($array);
            return msg(0, '添加成功');
        }
        $server = new Server();
        $list = $server->field('id,name,realname')->select();
        $this->assign([
            'server' => $list
        ]);
        return $this->fetch();
    }

    /**
     * 删除程序
     * @return \think\response\Json|void
     */
    public function delProgram()
    {
        if (Request::isPost()) {
            $programId = Request::post('programId');
            ProgramModel::destroy($programId);
            return msg(0, '删除成功');
        }
    }

    /**
     * 编辑程序
     * @return mixed|\think\response\Json
     */
    public function editProgram()
    {
        $id = Request::param('id');
        $program = new ProgramModel();
        if (Request::isPost()) {
            $array = [
                'productId' => Request::post('product_id'),
                'name' => Request::post('name'),
                'install' => Request::post('install'),
                'price' => Request::post('price')
            ];
            $program->save($array, ['id' => $id]);
            return msg(0, '修改成功');
        }
        $sql = $program->where('id', $id)->find();
        $this->assign([
            'id'=>$sql['id'],
            'product_id' => $sql['productId'],
            'name' => $sql['name'],
            'install' => $sql['install'],
            'price' => $sql['price']
        ]);
        return $this->fetch();
    }
}
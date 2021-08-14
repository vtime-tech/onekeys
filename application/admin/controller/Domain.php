<?php

namespace app\admin\controller;

use app\common\model\Server;
use app\common\model\Domain as DomainModel;
use app\common\model\Web as WebModel;
use think\Controller;
use think\facade\Request;
use think\facade\View;

View::share(['siteName' => getSiteName()]);
class Domain extends Controller
{
    protected $middleware = ['AdminCheck'];

    /**
     * 域名列表
     * @return mixed
     */
    public function domainList()
    {
        $server = new Server();
        $list = $server->field('id,name,realname')->select();
        $this->assign([
            'server' => $list
        ]);
        return $this->fetch();
    }

    /**
     * 获取域名列表
     * @return array|void
     */
    public function getDomain()
    {
        if (Request::isPost())
        {
            if (Request::param('serverId')) {
                $data = DomainModel::where('server_id',Request::post('serverId'))->select();
                return ['code' => 0, 'msg' => '获取成功', 'data' => $data];
            }
            return ['code' => 0, 'msg' => '获取成功', 'data' => [], 'count' => 0];
        }
    }

    /**
     * 删除域名
     * @return \think\response\Json
     */
    public function delDomain()
    {
        $id = Request::post('id');
        $WebInfo = WebModel::where('domain_id', $id)->find();
        if ($WebInfo) return msg(1,'存在使用该域名网站，禁止删除');
        DomainModel::destroy($id);
        return msg(0,'删除成功');
    }

    /**
     * 添加域名
     * @return mixed|\think\response\Json
     */
    public function addDomain()
    {
        if (Request::isPost()){
            $serverId = Request::post('serverId');
            $domain = Request::post('domain');
            $check = DomainModel::where('domain',$domain)->find();
            if ($check) return msg(1,'域名存在');
            DomainModel::create(['server_id'=>$serverId,'domain'=>$domain]);
            return msg(0,'添加成功');
        }
        $server = new Server();
        $list = $server->field('id,name,realname')->select();
        $this->assign([
            'server' => $list
        ]);
        return $this->fetch();
    }

    /**
     * 编辑域名
     * @return mixed|\think\response\Json
     */
    public function editDomain()
    {
        $id = Request::param('id');
        if (Request::isPost())
        {
            DomainModel::update(['domain'=>Request::post('domain')],['id'=>$id]);
            return msg(0,'修改成功');
        }
        $domainInfo = DomainModel::where('id',$id)->find();
        $this->assign([
           'id'=>$domainInfo['id'],
           'domain'=>$domainInfo['domain']
        ]);
        return $this->fetch();
    }
}
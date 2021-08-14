<?php

namespace app\admin\controller;

use app\common\model\Domain;
use app\common\model\Web as WebModel;
use app\common\model\Server;
use app\common\model\Program;
use Kangle\KangleApi;
use think\Controller;
use think\facade\Request;
use think\facade\View;

View::share(['siteName' => getSiteName()]);

class Web extends Controller
{
    protected $middleware = ['AdminCheck'];

    /**
     * 网站列表
     * @return mixed
     */
    public function webList()
    {
        $server = new Server();
        $list = $server->field('id,name,realname')->select();
        $this->assign([
            'server' => $list
        ]);
        return $this->fetch();
    }

    /**
     * 获取对应服务器下所有的网站
     * @return array|void
     */
    public function getWeb()
    {
        if (Request::isPost()) {
            if (Request::param('serverId')) {
                $web = new WebModel();
                $sql = $web->alias('web')
                    ->where('web.server_id',Request::param('serverId'))
                    ->join('users', 'web.uid = users.id')
                    ->join('program', 'web.program_id =  program.id')
                    ->join('server', 'web.server_id = server.id')
                    ->join('domain', 'web.domain_id = domain.id')
                    ->field('web.id,web.uid,users.username,server.name,program.name as program_name,web.secondName,domain.domain,web.begin_time,web.end_time')
                    ->select();
                $data = [];
                foreach ($sql as $item) {
                    $data[] = [
                        'id' => $item['id'],
                        'uid' => $item['uid'],
                        'username' => $item['username'],
                        'name' => $item['name'],
                        'program_name' => $item['program_name'],
                        'domain' => $item['secondName'] . '.' . $item['domain'],
                        'begin_time' => date('Y-m-d', $item['begin_time']),
                        'end_time' => date('Y-m-d', $item['end_time']),
                    ];
                }
                return ['code' => 0, 'msg' => '获取成功', 'data' => $data];
            }
            return ['code' => 0, 'msg' => '获取成功', 'data' => [], 'count' => 0];
        }
    }

    /**
     * 续费网站
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function reNew()
    {
        $id = Request::post('id');
        WebModel::where('id', $id)->setInc('end_time', 365 * 24 * 60 * 60);
        return msg(0, '续费成功');
    }

    /**
     * 删除网站
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function del()
    {
        $id = Request::post('id');
        $WebInfo = WebModel::where('id', $id)->find();
        $serverInfo = Server::where('id', $WebInfo['server_id'])->find();
        $kangle = new KangleApi($serverInfo['ip'], $serverInfo['port'], $serverInfo['authcode']);
        $res = $kangle->delHost($WebInfo['secondName']);
        if ($res['code'] == 0) {
            WebModel::destroy($id);
            return $res;
        }
        return $res;
    }

    /**
     * 重装网站
     * @return \think\response\Json|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function reInstall()
    {
        $id = Request::post('id');
        $WebInfo = WebModel::where('id', $id)->find();
        $serverInfo = Server::where('id', $WebInfo['server_id'])->find();
        $programInfo = Program::where('id', $WebInfo['program_id'])->find();
        $domain = Domain::where('id', $WebInfo['domain_id'])->value('domain');
        switch ($serverInfo['type']) {
            //如果这台服务器是Kangle 执行kangle的方法
            case 1:
                $kangle = new KangleApi($serverInfo['ip'], $serverInfo['port'], $serverInfo['authcode'], $programInfo['productId']);
                //删除开始
                $delRes = $kangle->delHost($WebInfo['secondName']);
                if ($delRes['code'] != 0) return msg(1, '重装失败');
                //重建开始
                $addRes = $kangle->addHost($WebInfo['secondName'], $domain, $programInfo['install']);
                if ($addRes['code'] != 0) return msg(1, $addRes['msg']);
                WebModel::update(['password' => $addRes['data']['password']], ['id' => $WebInfo['id']]);
                return msg(0, '重装成功');
        }
    }
}
<?php

namespace app\admin\controller;
use app\common\model\Web;
use app\common\model\Server as ServerModel;
use app\common\model\Domain as DomainModel;
use Kangle\KangleApi;
use think\Controller;
use think\facade\Request;
use think\facade\View;

View::share(['siteName' => getSiteName()]);
class Server extends Controller
{
    protected $middleware = ['AdminCheck'];

    /**
     * 服务器列表
     * @return mixed
     */
    public function serverList()
    {
        if (Request::isPost())
        {
            $page = Request::post('page');
            $limit = Request::post('limit');
            $serverModel = new ServerModel();
            $data = $serverModel->page($page,$limit)->select();
            $count = $serverModel->count();
            return ['code'=>0,'msg'=>'获取成功','data'=>$data,'count'=>$count];
        }
        return $this->fetch();
    }

    /**
     * 删除服务器
     * @return \think\response\Json|void
     */
    public function delServer()
    {
        if (Request::isPost())
        {
            $id = Request::post('id');
            $sql = Web::where('server_id',$id)->find();
            if ($sql) return msg(1,'无法删除，存在未过期站点');
            $domainCheck = DomainModel::where('server_id',$id)->find();
            if ($domainCheck) return msg(1,'无法删除，存在未删除域名');
            ServerModel::destroy($id);
            return msg(0,'删除成功');
        }
    }

    /**
     * 添加服务器
     * @return mixed|\think\response\Json
     */
    public function addServer()
    {
        if (Request::isPost())
        {
            $name = Request::post('name');
            $ip = Request::post('ip');
            $port = Request::post('port');
            $authcode = Request::post('authcode');
            $type = Request::post('type');
            $realname = Request::post('realname');
            $server = new ServerModel();
            $sql = $server->where('ip',$ip)->find();
            if ($sql)
            {
                return msg(1,'服务器存在');
            }
            switch ($type){
                case 1:
                    $kangle = new KangleApi($ip,$port,$authcode);
                    $res = $kangle->serverInfo();
                    if ($res['code'] != 0){
                        return msg(1,'节点返回错误，请检查您的参数');
                    }
            }
            $array = [
                'name'=>$name,
                'type'=>$type,
                'ip'=>$ip,
                'port'=>$port,
                'authcode'=>$authcode,
                'realname'=>$realname
            ];
            $server = new ServerModel();
            $server->save($array);
            return msg(0,'添加成功');
        }
        return $this->fetch();
    }

    /**
     * 修改服务器
     * @return mixed|\think\response\Json
     */
    public function editServer()
    {
        $id = Request::param('id');
        $server = new ServerModel();
        $sql = $server->where('id',$id)->find();
        if (Request::isPost())
        {
            $name = Request::post('name');
            $ip = Request::post('ip');
            $port = Request::post('port');
            $authcode = Request::post('authcode');
            $type = Request::post('type');
            $realname = Request::post('realname');
            //检查填写的信息是否正确
            switch ($type){
                case 1:
                    $kangle = new KangleApi($ip,$port,$authcode);
                    $res = $kangle->serverInfo();
                    if ($res['code'] != 0){
                        return msg(1,'节点返回错误，请检查您的参数');
                    }
            }
            $array = [
                'name'=>$name,
                'ip'=>$ip,
                'port'=>$port,
                'authcode'=>$authcode,
                'type'=>$type,
                'realname'=>$realname
            ];
            $server->save($array,['id'=>$id]);
            return msg(0,'修改成功');
        }
        $this->assign([
            'id'=>$sql['id'],
            'type'=>$sql['type'],
            'name'=>$sql['name'],
            'ip'=>$sql['ip'],
            'port'=>$sql['port'],
            'authcode'=>$sql['authcode'],
            'realname'=>$sql['realname']
        ]);
        return $this->fetch();
    }

    /**
     * 服务器状态
     * @return mixed|\think\response\Json
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function serverStatus()
    {
        if (Request::isPost())
        {
            $server = new ServerModel();
            $list = $server->select();
            $data = [];
            foreach ($list as $item) {
                switch ($item['type']){
                    case 1:
                        $kangle = new KangleApi($item['ip'],$item['port'],$item['authcode']);
                        $info = $kangle->serverInfo();
                        if ($info['code'] == 0){
                            $data[] = [
                                'id'=>$item['id'],
                                'name'=>$item['name'],
                                'ip'=>$item['ip'],
                                'type'=>$item['type'],
                                'os'=>$info['data']['os'][0][0],
                                'system'=>$info['data']['type'][0][0],
                                'connect'=>$info['data']['connect'][0][0],
                                'disk_free'=>$info['data']['disk_free'][0][0],
                            ];
                        }else{
                            $data[] = [
                                'id'=>$item['id'],
                                'name'=>$item['name'],
                                'ip'=>$item['ip'],
                                'type'=>$item['type'],
                                'os'=>'读取失败',
                                'system'=>'读取失败',
                                'connect'=>'读取失败',
                                'disk_free'=>'读取失败',
                            ];
                        }
                }
            }
            return msg(0,'获取成功',$data);
        }
        return $this->fetch();
    }
}
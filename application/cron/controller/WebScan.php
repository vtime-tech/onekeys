<?php

namespace app\cron\controller;

use app\common\model\Web;
use Kangle\KangleApi;
use think\worker\Server;
use Workerman\Lib\Timer;
use Workerman\Worker;
use app\common\model\Server as HostServer;
class WebScan extends Server
{
    protected $option = [
        "name" => "WebDeadlineCheck",
        "count" => 1
    ];

    public function onWorkerStart()
    {
        echo "站点到期检测已开启".PHP_EOL;
        Timer::add(10, function () {
            $this->check();
        });
    }

    private function check()
    {
        $webMsg = Web::select();
        foreach ($webMsg as $item)
        {
            if ($item['end_time'] < time())
            {
                $server = HostServer::where('id',$item['server_id'])->find();
                $kangle = new KangleApi($server['ip'],$server['port'],$server['authcode']);
                $kangle->delHost($item['secondName']);
                Web::destroy($item['id']);
                echo 'ID:'.$item['id'].'站点已到期，执行删除成功';
            }else{
                echo '站点检测完毕'.date('Y-m-d H:i:s').PHP_EOL;
            }
        }
    }
}
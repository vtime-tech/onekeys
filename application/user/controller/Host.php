<?php


namespace app\user\controller;


use app\common\model\Domain;
use app\common\model\ExpenseRecord;
use app\common\model\Program;
use app\common\model\Server;
use app\common\model\Users;
use app\common\model\Web;
use Kangle\KangleApi;
use think\Controller;
use think\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

View::share(['siteName' => getSiteName()]);

class Host extends Controller
{
    protected $middleware = ['UserCheck'];

    /**
     * 程序列表
     * @return mixed
     */
    public function program()
    {
        $server = new Server();
        $user = new Users();
        //列出所有服务器组
        $serverInfo = $server->field('id,name,realname')->select();
        //查询当前代理等级与折扣
        $uid = Session::get('uid');
        //查询当前用户的折扣信息
        $discount = $user->alias('users')
            ->where('users.id', $uid)
            ->join('daili', 'users.level = daili.level')
            ->field('daili.level,daili.discount')
            ->find();
        $this->assign([
            'server' => $serverInfo,
            'discount' => $discount
        ]);
        return $this->fetch();
    }

    /**
     * 读取程序列表
     * @return array
     */
    public function programList()
    {
        if (Request::isPost()) {
            if (Request::param('serverId')) {
                $serverId = Request::post('serverId');
                $program = new Program();
                $user = new Users();
                $data = $program->where('server_id', $serverId)->select();
                $count = $data->count();
                //查询当前用户的折扣信息
                $uid = Session::get('uid');
                $discount = $user->alias('users')
                    ->where('users.id', $uid)
                    ->join('daili', 'users.level = daili.level')
                    ->field('daili.discount')
                    ->find();
                $showUser = [];
                foreach ($data as $item) {
                    $showUser[] = [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'price' => $item['price'] * ($discount['discount'] / 10)
                    ];
                }
                return ['code' => 0, 'msg' => '获取成功', 'data' => $showUser, 'count' => $count];
            }
            return ['code' => 0, 'msg' => '获取成功', 'data' => [], 'count' => 0];
        }
    }

    /**
     * 添加网站
     * @return mixed
     */
    public function addHost()
    {
        $programId = Request::get('id');
        $program = new Program();
        $server = new Server();
        $domain = new Domain();
        $programInfo = $program->where('id', $programId)->find();
        $serverInfo = $server->where('id', $programInfo['server_id'])->find();
        $domainInfo = $domain->where('server_id', $programInfo['server_id'])->select();
        //查询当前用户的折扣信息
        $user = new Users();
        $uid = Session::get('uid');
        $discount = $user->alias('users')
            ->where('users.id', $uid)
            ->join('daili', 'users.level = daili.level')
            ->field('daili.level,daili.discount')
            ->find();
        $this->assign([
            'programId' => $programId,
            'name' => $programInfo['name'],
            'serverName' => $serverInfo['name'],
            'price' => $programInfo['price'] * ($discount['discount'] / 10),
            'domainInfo' => $domainInfo
        ]);
        return $this->fetch();
    }

    /**
     * 开始运行搭建
     * @return \think\response\Json
     */
    public function startBuild()
    {
        if (Request::isPost()) {
            //程序ID
            $id = Request::post('id');
            //二级域名
            $secondName = Request::post('secondName');
            //一级域名ID
            $domainId = Request::post('domain');
            $program = new Program();
            //安装程序需要用到的信息
            $install = $program->alias('program')
                ->where('program.id', $id)
                ->join('server', 'program.server_id = server.id')
                ->leftJoin('domain', ['domain.server_id = server.id', 'domain.id =' . $domainId])
                ->field('program.id as programId,program.productId,program.name,program.install,program.htaccess,program.price,program.server_id,
                               server.name as server_name,server.type,server.ip,server.port,server.authcode,server.realname,
                               domain.domain,domain.id as domain_id')
                ->find();
            //查用户余额与实名记录
            $user = new Users();
            $userInfo = $user->alias('user')
                ->where('user.id', Session::get('uid'))
                ->join('daili', 'user.level = daili.level')
                ->field('user.balance,user.realname,daili.discount')
                ->find();
            //计算折扣后的价格
            $discountPrice = $install['price'] * $userInfo['discount'] / 10;
            if ($userInfo['balance'] < $discountPrice) return msg(1, '余额不足');
            if ($userInfo['realname'] == 1 && $install['realname'] == 0) return msg(1, '该节点需要实名，请实名');
            //判断同级服务器是否存在相同域名
            $check = Web::where('domain_id', $install['domain_id'])->where('secondName', $secondName)->find();
            if ($check) return msg(1, '二级域名存在');
            //开始搭建站点
            switch ($install['type']) {
                case 1:
                    $kangle = new KangleApi($install['ip'], $install['port'], $install['authcode'], $install['productId']);
                    $result = $kangle->addHost($secondName, $install['domain'], $install['install']);
            }
            if ($result['code'] != 0) return msg(1, $result['msg']);
            //搭建成功后扣费
            Users::update(['balance' => $userInfo['balance'] - $discountPrice], ['id' => Session::get('uid')]);
            $Expense = new ExpenseRecord();
            $Expense->log($discountPrice, $userInfo['balance'], $userInfo['balance'] - $discountPrice, $install['name'] . ' - 网站搭建');
            //网站信息写表
            $array = [
                'uid' => Session::get('uid'),
                'server_id' => $install['server_id'],
                'program_id' => $install['programId'],
                'secondName' => $secondName,
                'domain_id' => $install['domain_id'],
                'password' => $result['data']['password'],
                'begin_time' => strtotime("now"),
                'end_time' => strtotime("+1 year")
            ];
            Web::create($array);
            return msg(0, '搭建成功');
        }
    }

    /**
     * 已搭建网站
     * @return array|mixed
     */
    public function hostList()
    {
        if (Request::isPost()) {
            $page = Request::post('page');
            $limit = Request::post('limit');
            $web = new Web();
            $sql = $web->alias('web')
                ->where('web.uid', Session::get('uid'))
                ->join('server', 'web.server_id = server.id')
                ->join('domain', 'web.domain_id = domain.id')
                ->join('program', 'web.program_id = program.id')
                ->field('web.id,web.secondName,web.begin_time,web.end_time,
                         domain.domain,server.name as server_name,program.name as programName')
                ->page($page, $limit)
                ->select();
            $count = $web->where('uid', Session::get('uid'))->count();
            $data = [];
            foreach ($sql as $item) {
                if (strtotime("now") > $item['end_time']) $item['status'] = 1;
                $data[] = [
                    'id' => $item['id'],
                    'program' => $item['programName'],
                    'server' => $item['server_name'],
                    'domain' => $item['secondName'] . '.' . $item['domain'],
                    'begin_time' => date('Y-m-d', $item['begin_time']),
                    'end_time' => date('Y-m-d', $item['end_time'])
                ];
            }
            return ['code' => 0, 'msg' => '获取成功', 'data' => $data, 'count' => $count];
        }
        return $this->fetch();
    }

    /**
     * 重装
     * @return \think\response\Json|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function reInstall()
    {
        if (Request::isPost()) {
            $id = Request::post('id');
            //查询一下这个ID是否属于这个用户
            $web = Web::where('id', $id)->where('uid', Session::get('uid'))->find();
            if (!$web) return msg(1, '您没有这个网站的权限');
            //执行删除
            $server = Server::where('id', $web['server_id'])->find();
            $program = Program::where('id', $web['program_id'])->field('productId,install')->find();
            $domain = Domain::where('id', $web['domain_id'])->value('domain');
            switch ($server['type']) {
                //如果这台服务器是Kangle 执行kangle的方法
                case 1:
                    $kangle = new KangleApi($server['ip'], $server['port'], $server['authcode'], $program['productId']);
                    //删除开始
                    $delRes = $kangle->delHost($web['secondName']);
                    if ($delRes['code'] != 0) return msg(1, '重装失败,请联系管理员');
                    //重建开始
                    $addRes = $kangle->addHost($web['secondName'], $domain, $program['install']);
                    if ($addRes['code'] != 0) return msg(1, $addRes['msg']);
                    Web::update(['password' => $addRes['data']['password']], ['id' => $web['id']]);
                    return msg(0, '重装成功');
            }
        }
    }

    public function reNew()
    {
        $id = Request::post('id');
        //查询一下这个ID是否属于这个用户
        $web = Web::where('id', $id)->where('uid', Session::get('uid'))->find();
        if (!$web) return msg(1, '您没有这个网站的权限');
        //查用户余额与实名记录
        $user = new Users();
        $userInfo = $user->alias('user')
            ->where('user.id', Session::get('uid'))
            ->join('daili', 'user.level = daili.level')
            ->field('user.balance,user.realname,daili.discount')
            ->find();
        //查询程序价格
        $program = Program::where('id',$web['program_id'])->find();
        //计算折扣后的价格
        $discountPrice = $program['price'] * $userInfo['discount'] / 10;
        if ($userInfo['balance'] < $discountPrice) return msg(1, '余额不足');
        try {
            Db::startTrans();
            $expense = new ExpenseRecord();
            Web::where('id',$id)->setInc('end_time',365*24*60*60);
            Users::update(['balance'=>$userInfo['balance'] - $discountPrice],['id'=>Session::get('uid')]);
            $expense->log($discountPrice,$userInfo['balance'],$userInfo['balance'] - $discountPrice,'ID:'.$web['id'].'  - '.$program['name'].' - 网站续费');
            Db::commit();
            return msg(0,'续费成功');
        }catch (\Exception $e){
            Db::rollback();
            return msg(1,$e->getMessage());
        }
    }
}
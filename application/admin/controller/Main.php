<?php


namespace app\admin\controller;


use app\common\model\Admin;
use app\common\model\RealnamePersonal;
use app\common\model\Recharge;
use app\common\model\Sms;
use app\common\model\Users;
use think\Controller;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

View::share(['siteName' => getSiteName()]);

class Main extends Controller
{
    protected $middleware = ['AdminCheck'];

    /**
     * 修改密码
     * @return mixed|\think\response\Json
     */
    public function setpass()
    {
        if (Request::isPost()) {
            $oldPassword = Request::post('oldPassword');
            $password = Request::post('password');
            $repassword = Request::post('repassword');
            if ($oldPassword == '' || $password == '' || $repassword == '') {
                return msg(1, '参数错误');
            }
            if ($password != $repassword) {
                return msg(1, '两次输入不匹配');
            }
            $AdminUsername = Session::get('AdminUsername');
            $AdminUid = Session::get('AdminUid');
            $userInfo = Admin::where('username', $AdminUsername)->where('id', $AdminUid)->find();
            if (!passwordVerify($oldPassword, $userInfo['password'], $userInfo['salt'])) {
                return msg(1, '原密码错误');
            }
            $passwordSalt = random(6);
            Admin::update(['password' => passwordCreate($password, $passwordSalt), 'salt' => $passwordSalt], ['username' => $AdminUsername, 'id' => $AdminUid]);
            Session::clear();
            return msg(0, '修改成功，请重新登录');
        }
        return $this->fetch();
    }

    /**
     * 用户列表
     * @return array|mixed|\think\response\Json
     */
    public function userList()
    {
        if (Request::isPost()) {
            $users = new Users();
            $page = Request::post('page');
            $limit = Request::post('limit');
            if (Request::param('username')) {
                $username = Request::post('username');
                $sql = $users->whereLike('username', '%' . $username . '%')
                    ->page($page, $limit)
                    ->field('id,username,phone,email,balance,level,realname,create_time,status')
                    ->select();
                $count = $users->whereLike('username', '%' . $username . '%')->count();
                return ['code' => 0, 'msg' => '获取成功', 'data' => $sql, 'count' => $count];
            }
            if (Request::param('action')) {
                if (Request::param('action') == 'repass') {
                    $userId = Request::post('userId');
                    $passwordUser = random(7);
                    $passwordSalt = random(6);
                    $password = passwordCreate($passwordUser, $passwordSalt);
                    Users::update(['password' => $password, 'salt' => $passwordSalt], ['id' => $userId]);
                    return msg(0, '重置成功', ['newpass' => $passwordUser]);
                }
                if (Request::param('action') == 'userStatus') {
                    $userId = Request::post('userId');
                    $status = Request::post('status');
                    Users::update(['status' => $status], ['id' => $userId]);
                    return msg(0, '操作成功');
                }
                if (Request::param('action') == 'bind') {
                    $userId = Request::post('userId');
                    $phone = Request::post('phone');
                    Users::update(['phone' => $phone], ['id' => $userId]);
                    return msg(0, '操作成功');
                }
                if (Request::param('action') == 'login') {
                    $userId = Request::post('userId');
                    $sql = Users::where('id', $userId)->find();
                    Session::set('userLogin', 0);
                    Session::set('username', $sql['username']);
                    Session::set('uid', $sql['id']);
                    return msg(0, '权限写入成功');
                }
            }
            $sql = $users->page($page, $limit)
                ->field('id,username,phone,email,balance,level,realname,create_time,status')
                ->select();
            $count = $users->count();
            return ['code' => 0, 'msg' => '获取成功', 'data' => $sql, 'count' => $count];
        }
        return $this->fetch();
    }

    /**
     * 充值列表
     * @return array|mixed
     */
    public function rechargeList()
    {
        if (Request::isPost()) {
            $page = Request::post('page');
            $limit = Request::post('limit');
            $sql = Recharge::alias('list')
                ->page($page, $limit)
                ->join('users user', 'list.uid = user.id')
                ->order('id desc')
                ->field('list.id,list.uid,user.username,list.out_trade_no,list.money,list.name,list.status,list.create_time,list.update_time,list.gateway')
                ->select();
            $count = Recharge::count();
            return ['code' => 0, 'msg' => '获取成功', 'data' => $sql, 'count' => $count];
        }
        return $this->fetch();
    }

    public function recharge()
    {
        if (Request::isPost())
        {
            $id = Request::post('id');
            $money = Request::post('money');
            $do = Request::post('do');
            $user = Users::where('id',$id)->find();
            if (!$user)
            {
                return msg(1,'查无此人');
            }
            if ($do == 'add')
            {
                $user->setInc('balance',$money);
                $array = [
                    'uid'=>$id,
                    'out_trade_no'=>0,
                    'name'=>'管理员加款',
                    'money'=>$money,
                    'gateway'=>'Admin',
                    'status'=>0
                ];
                Recharge::create($array);
            }
            if ($do == 'dec')
            {
                if ($user['balance'] < $money)
                {
                    return msg(1,'用户余额不足');
                }
                $user->setDec('balance',$money);
            }
            return msg(0,'操作成功');
        }
        $sql = Users::field('id,username')->select();
        $this->assign('users',$sql);
        return $this->fetch();
    }

    /**
     * 实名列表
     * @return array|mixed
     */
    public function realnameList()
    {
        if (Request::isPost()) {
            $page = Request::post('page');
            $limit = Request::post('limit');
            $sql = RealnamePersonal::alias('RealnameList')
                ->page($page, $limit)
                ->join('users user', 'RealnameList.uid = user.id')
                ->order('id desc')
                ->field('RealnameList.id,user.username,RealnameList.type,RealnameList.name,RealnameList.mobile,RealnameList.idcard,RealnameList.create_time,RealnameList.status')
                ->select();
            $count = RealnamePersonal::count();
            $result = [];
            foreach ($sql as $k) {
                $name = displayTrueName($k['name']);
                $idcard = displayIdCardVerify($k['idcard']);
                $type = realNameType($k['type']);
                $mobile = displayMobile($k['mobile']);
                $result[] = [
                    'id' => $k['id'],
                    'username' => $k['username'],
                    'type' => $type,
                    'name' => $name,
                    'idcard' => $idcard,
                    'mobile' => $mobile,
                    'create_time' => $k['create_time'],
                    'status' => $k['status']
                ];
            }
            return ['code' => 0, 'msg' => '获取成功', 'data' => $result, 'count' => $count];
        }
        return $this->fetch();
    }

    /**
     * 管理员列表
     * @return array|mixed|string|\think\response\Json
     */
    public function adminList()
    {
        $uid = Session::get('AdminUid');
        $Admin = new Admin();
        $sql = $Admin->where('id', $uid)->where('identity', 1)->find();
        if (!$sql) {
            return '无权限!';
        }
        if (Request::isPost()) {
            $page = Request::post('page');
            $limit = Request::post('limit');
            if (Request::param('username')) {
                $sql = $Admin->page($page, $limit)
                    ->whereLike('username', '%' . Request::post('username') . '%')
                    ->field('id,username,phone,email,status,create_time')
                    ->select();
                $count = $Admin->whereLike('username', '%' . Request::post('username') . '%')->count();
                return ['code' => 0, 'msg' => '获取成功', 'data' => $sql, 'count' => $count];
            }
            if (Request::param('action')) {
                if (Request::param('action') == 'repass') {
                    $userId = Request::post('userId');
                    $sql = $Admin->where('id',$userId)->find();
                    if ($sql['username'] == 'admin')
                    {
                        return msg(1,'超级管理员仅支持自行修改密码');
                    }
                    $passwordUser = random(7);
                    $passwordSalt = random(6);
                    $password = passwordCreate($passwordUser, $passwordSalt);
                    $Admin->update(['password' => $password, 'salt' => $passwordSalt], ['id' => $userId]);
                    return msg(0, '重置成功', ['newpass' => $passwordUser]);
                }
                if (Request::param('action') == 'AdminStatus') {
                    $userId = Request::post('userId');
                    $status = Request::post('status');
                    $sql = $Admin->where('id',$userId)->find();
                    if ($sql['username'] == 'admin')
                    {
                        return msg(1,'禁止操作超级管理员');
                    }
                    $Admin->update(['status' => $status], ['id' => $userId]);
                    return msg(0, '操作成功');
                }
                if (Request::param('action') == 'del') {
                    $userId = Request::post('userId');
                    $sql = $Admin->where('id',$userId)->find();
                    if ($sql['username'] == 'admin')
                    {
                        return msg(1,'禁止删除超级管理员');
                    }
                    Admin::destroy($userId);
                    return msg(0, '删除成功');
                }
            }
            $sql = $Admin->page($page, $limit)
                ->field('id,username,phone,email,status,create_time')
                ->select();
            $count = Admin::count();
            return ['code' => 0, 'msg' => '获取成功', 'data' => $sql, 'count' => $count];
        }
        return $this->fetch();
    }

    /**
     * 添加管理员
     * @return mixed|\think\response\Json
     */
    public function addAdmin()
    {
        if (Request::isPost())
        {
            $username = Request::post('username');
            $password = Request::post('password');
            $phone = Request::post('phone');
            $email = Request::post('email');
            $status = Request::post('status');
            $identity  = Request::post('identity');
            $sql = Admin::where('username',$username)->find();
            if ($sql)
            {
                return msg(0,'管理员已存在');
            }
            $passwordSalt = random(6);
            $array = [
              'username'=>$username,
              'password'=>passwordCreate($password,$passwordSalt),
                'salt'=>$passwordSalt,
                'phone'=>$phone,
                'email'=>$email,
                'identity'=>$identity,
                'status'=>$status
            ];
            Admin::create($array);
            return msg(0,'添加成功');
        }
        return $this->fetch();
    }

    /**
     * 修改管理员
     * @return mixed|\think\response\Json
     */
    public function editAdmin(){
        if (Request::isPost())
        {
            $id = Request::post('id');
            $username = Request::post('username');
            $phone = Request::post('phone');
            $email = Request::post('email');
            $array = [
              'phone'=>$phone,
              'email'=>$email
            ];
            Admin::update($array,['id'=>$id,'username'=>$username]);
            return msg(0,'修改成功');
        }
        $userId = Request::param('userId');
        $sql = Admin::where('id',$userId)->find();
        $this->assign([
           'id'=>$userId,
           'username'=>$sql['username'],
            'phone'=>$sql['phone'],
            'email'=>$sql['email'],
        ]);
        return $this->fetch();
    }

    /**
     * 短信列表
     * @return array|mixed
     */
    public function smsList()
    {
        if (Request::isPost())
        {
            $page = Request::post('page');
            $limit = Request::post('limit');
            $sql = Sms::order('id','desc')->page($page,$limit)->select();
            $count = Sms::count();
            return ['code' => 0, 'msg' => '获取成功', 'data' => $sql, 'count' => $count];
        }
        return $this->fetch();
    }
}
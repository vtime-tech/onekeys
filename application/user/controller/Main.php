<?php


namespace app\user\controller;


use app\common\model\Config;
use app\common\model\ExpenseRecord;
use app\common\model\LoginLog;
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
    protected $middleware = ['UserCheck'];

    /**
     * 个人信息
     * @return mixed
     */
    public function userinfo()
    {
        $userName = Session::get('username');
        $uid = Session::get('uid');
        $smsSwitch = Config::where('name', 'sms_switch')->find();
        $realnameSwitch = Config::where('name', 'realname_switch')->find();
        $userInfo = Users::where('username', $userName)->where('id', $uid)->find();
        $array = [
            'username' => $userInfo['username'],
            'phone' => $userInfo['phone'],
            'level' => $userInfo['level'],
            'realname' => $userInfo['realname'],
            'balance' => $userInfo['balance'],
            'smsSwitch' => $smsSwitch['value'],
            'realnameSwitch' => $realnameSwitch['value']
        ];
        $this->assign($array);
        return $this->fetch();
    }

    /**
     * 登录记录
     * @return array|mixed
     */
    public function loginlog()
    {
        if (Request::isPost()) {
            $page = Request::post('page');
            $limit = Request::post('limit');
            $username = Session::get('username');
            $uid = Session::get('uid');
            $sql = LoginLog::where('uid', $uid)->order('id desc')->page($page, $limit)->select();
            $count = LoginLog::where('uid', $uid)->count();
            $data = [];
            foreach ($sql as $k) {
                $data[] = [
                    'username' => $username,
                    'uid' => $uid,
                    'ip' => $k['ip'],
                    'ua' => $k['ua'],
                    'status' => $k['status'],
                    'create_time' => $k['create_time']
                ];
            }
            return ['code' => 0, 'msg' => '获取成功', 'data' => $data, 'count' => $count];
        }
        return $this->fetch();
    }

    /**
     * 充值记录
     * @return array|mixed
     */
    public function rechargeList()
    {
        if (Request::isPost()) {
            $page = Request::post('page');
            $limit = Request::post('limit');
            $uid = Session::get('uid');
            $sql = Recharge::where('uid', $uid)->order('id desc')->page($page, $limit)->select();
            $count = Recharge::where('uid', $uid)->count();
            return ['code' => 0, 'msg' => '获取成功', 'data' => $sql, 'count' => $count];
        }
        return $this->fetch();
    }

    /**
     * 消费记录
     * @return array|mixed
     */
    public function expenseList()
    {
        if (Request::isPost()) {
            $page = Request::post('page');
            $limit = Request::post('limit');
            $uid = Session::get('uid');
            $sql = ExpenseRecord::where('uid', $uid)->order('id desc')->page($page, $limit)->select();
            $count = ExpenseRecord::where('uid', $uid)->count();
            return ['code' => 0, 'msg' => '获取成功', 'data' => $sql, 'count' => $count];
        }
        return $this->fetch();
    }

    /**
     * 绑定手机
     * @return mixed|\think\response\Json
     */
    public function bindphone()
    {
        if (Request::isPost()) {
            $cellphone = Request::post('cellphone');
            $smscode = Request::post('vercode');
            //验证是否为空
            if ($cellphone === '' || $smscode === '') {
                return msg(1, '参数错误');
            }
            //验证验证码
            $smsResult = Sms::where('mobile', $cellphone)->where('status', 1)->whereTime('create_time', '-60 second')->order('id desc')->find();
            if (!$smsResult) {
                return msg(1, '验证码不存在或者过期,请检查');
            }
            if ($smsResult['code'] != $smscode) {
                return msg(1, '验证码不正确');
            }
            Sms::update(['status' => 0], ['id' => $smsResult['id']]);
            $username = Session::get('username');
            $uid = Session::get('uid');
            Users::update(['phone' => $cellphone], ['username' => $username, 'id' => $uid]);
            return msg(0, '绑定成功');
        }
        return $this->fetch();
    }


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
            $username = Session::get('username');
            $uid = Session::get('uid');
            $userInfo = Users::where('username', $username)->where('id', $uid)->find();
            if (!passwordVerify($oldPassword, $userInfo['password'], $userInfo['salt'])) {
                return msg(1, '原密码错误');
            }
            $passwordSalt = random(6);
            Users::update(['password' => passwordCreate($password, $passwordSalt), 'salt' => $passwordSalt], ['username' => $username, 'id' => $uid]);
            Session::clear();
            return msg(0, '修改成功，请重新登录');
        }
        return $this->fetch();
    }
}
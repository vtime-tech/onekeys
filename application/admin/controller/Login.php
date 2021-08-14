<?php


namespace app\admin\controller;


use app\common\model\Admin;
use think\Controller;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

View::share(['siteName' => getSiteName()]);
class Login extends Controller
{
    /**
     * 登录页
     * @return mixed|\think\response\Json
     */
    public function login()
    {
        if (Request::isPost())
        {
            $username = Request::post('username');
            $password = Request::post('password');
            //登录前验证是否为空
            if ($username === '' || $password === '') {
                return msg(1, '账号或密码不得为空');
            }
            //查询是否存在账号
            $sql = Admin::where('username', $username)->find();
            if (!$sql) {
                return msg(1, '账号或密码错误');
            }

            //账号存在验证密码是否正确
            if (!passwordVerify($password, $sql['password'], $sql['salt'])) {
                return msg(1, '账号或密码错误');
            }
            if ($sql['status'] == 1)
            {
                return msg(1,'账号异常，请联系管理员');
            }
            //写入Session
            Session::set('AdminLogin', 0);
            Session::set('AdminUsername', $username);
            Session::set('AdminUid', $sql['id']);
            return msg(0, '登录成功');
        }
        return $this->fetch();
    }
}
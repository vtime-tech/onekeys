<?php

namespace app\index\controller;

use app\common\model\Config;
use app\common\model\LoginLog;
use app\common\model\Sms;
use app\common\model\Users;
use think\Controller;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

View::share(['siteName' => getSiteName()]);

class Index extends Controller
{
    private function common()
    {
        $siteCompany = Config::where('name', 'site_company')->find();
        $siteIcp = Config::where('name', 'site_icp')->find();
        $siteGov = Config::where('name', 'site_gov')->find();
        $this->assign([
            'siteTitle' => getSiteTitle(),
            'siteKeywords' => getSiteKeywords(),
            'siteDescription' => getSiteDescription(),
            'siteCompany' => $siteCompany['value'],
            'siteIcp' => $siteIcp['value'],
            'siteGov' => $siteGov['value']
        ]);
    }

    //首页
    public function index()
    {
        $this->common();
        return $this->fetch();
    }

    //登录
    public function login()
    {
        if (Request::isPost()) {
            $username = Request::post('username');
            $password = Request::post('password');
            //登录前验证是否为空
            if ($username === '' || $password === '') {
                return msg(1, '账号或密码不得为空');
            }
            //查询是否存在账号
            $sql = Users::where('username', $username)->find();
            if (!$sql) {
                return msg(1, '账号或密码错误');
            }
            if ($sql['status'] != 0) {
                return msg(1, '账号异常,请联系管理员');
            }
            //账号存在验证密码是否正确
            if (!passwordVerify($password, $sql['password'], $sql['salt'])) {
                LoginLog::create(['uid'=>$sql['id'],'ip'=>Request::ip(),'ua'=>Request::header('user-agent'),'status'=>1]);
                return msg(1, '账号或密码错误');
            }
            //写入Session
            Session::set('userLogin', 0);
            Session::set('username', $username);
            Session::set('uid', $sql['id']);
            LoginLog::create(['uid'=>$sql['id'],'ip'=>Request::ip(),'ua'=>Request::header('user-agent'),'status'=>0]);
            return msg(0, '登录成功');
        }
        return $this->fetch();
    }

    //注册
    public function register()
    {
        //获取短信模块状态
        $smsStatus = Config::where('name', 'sms_switch')->find();
        if (Request::isPost()) {
            $username = Request::post('username');
            $password = Request::post('password');
            $repass = Request::post('repass');
            $cellphone = Request::post('cellphone');
            $smscode = Request::post('smscode');
            //当短信模块开启但表单并无验证码参数
            if ($smsStatus['value'] === 0 && $cellphone === '' || $smscode === '') {
                return msg(1, '参数错误');
            }
            //无论短信模块是否开启都要验证的参数
            if ($username === '' || $password === '' || $repass === '' || $password != $repass) {
                return msg(1, '参数错误');
            }
            //短信模块开启开始验证验证码
            if ($smsStatus['value'] === 0) {
                $smsResult = Sms::where('mobile', $cellphone)->where('status', 1)->whereTime('create_time', '-60 second')->order('id desc')->find();
                if (!$smsResult || $smsResult['status'] === 0) {
                    return msg(1, '验证码不存在或者过期,请检查');
                }
                if ($smsResult['code'] != $smscode) {
                    return msg(1, '验证码不正确');
                } else {
                    Sms::update(['status' => 0], ['id' => $smsResult['id']]);
                }
            }
            //注册 判断手机和用户名是否存在 防止找回密码冲突
            $sql = Users::where('username', $username)->whereOr('phone', $cellphone)->find();
            if ($sql) {
                return msg(1, '用户名或绑定手机存在,请检查');
            }
            $passwordSalt = random(6);
            $array = [
                'username' => $username,
                'password' => passwordCreate($password, $passwordSalt),
                'salt' => $passwordSalt,
                'phone' => $cellphone
            ];
            Users::create($array);
            return msg(0, '注册成功');
        }
        //当开启短信模块需要判断是否在页面显示
        if ($smsStatus['value'] === '0') {
            $this->assign('smsOpen', '0');
        } else {
            $this->assign('smsOpen', '1');
        }
        return $this->fetch();
    }

    //找回密码
    public function forget()
    {
        if (Request::isPost()) {
            if (Request::param('action') === 'reset') {
                $cellphone = Request::post('cellphone');
                $smscode = Request::post('vercode');
                //验证是否为空
                if ($cellphone === '' || $smscode === '') {
                    return msg(1, '参数错误');
                }
                //验证验证码
                $smsResult = Sms::where('mobile', $cellphone)->where('status', 0)->whereTime('create_time', '-60 second')->order('id desc')->find();
                if (!$smsResult) {
                    return msg(1, '验证码不存在或者过期,请检查');
                }
                if ($smsResult['code'] != $smscode) {
                    return msg(1, '验证码不正确');
                }
                Session::delete('resetPhone');
                Sms::update(['status' => 1], ['id' => $smsResult['id']]);
                Session::set('resetPhone', $cellphone);
                return msg(0, '验证通过');
            }
            //重置密码
            if (Request::param('action') === 'repass') {
                $password = Request::post('password');
                $repass = Request::post('repass');
                if ($password != $repass) {
                    return msg(1, '参数错误');
                }
                $passwordSalt = random(6);
                $phone = Session::get('resetPhone');
                Users::update(['password' => passwordCreate($password,$passwordSalt),'salt'=>$passwordSalt], ['phone' => $phone]);
                return msg(0, '重置成功');
            }
        }
        return $this->fetch();
    }

    //发送短信验证码
    public function smscaptcha()
    {
        $phone = Request::post('phone');
        return sendSmsCode($phone);
    }


    //退出登录
    public function logout()
    {
        Session::clear();
        return msg(0, '退出成功');
    }
}
<?php

namespace app\http\middleware;

use think\Controller;
use think\facade\Request;
use think\facade\Session;

class AdminCheck extends Controller
{
    public function handle($request, \Closure $next)
    {
        $loginStatus = Session::get('AdminLogin');
        if ($loginStatus === null || $loginStatus != 0)
        {
            if (Request::isPost())
            {
                return msg(1001,'未登录');
            }
            $this->error('未登录!','/admin/login/login');
        }
        return $next($request);
    }
}

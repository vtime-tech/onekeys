<?php
namespace app\admin\controller;


use app\common\model\Recharge;
use app\common\model\Users;
use think\Controller;
use think\facade\Session;
use think\facade\View;

View::share(['siteName'=>getSiteName()]);
class Index extends Controller
{
    protected $middleware = ['AdminCheck'];

    /**
     * Iframe框
     * @return mixed
     */
    public function console()
    {
        $username = Session::get('AdminUsername');
        $this->assign([
            'username'=>$username,
        ]);
        return $this->fetch();
    }

    /**
     * 默认页面跳转
     * @return \think\response\Redirect
     */
    public function index()
    {
        return redirect('/admin/index/console');
    }

    /**
     * 控制台
     * @return mixed
     */
    public function home()
    {
        $users = new Users();
        $recharges = new Recharge();
        $usersCount = $users->count();
        $realUsersCount = $users->where('realname',0)->count();
        $yesterdayRegister = $users->whereTime('create_time','yesterday')->count();
        $yesterdayRecharge = $recharges->whereTime('create_time','yesterday')->where('status',0)->sum('money');
        $this->assign([
            'usersCount'=>$usersCount,
            'realUsersCount'=>$realUsersCount,
            'yesterdayRegister'=>$yesterdayRegister,
            'yesterdayRecharge'=>$yesterdayRecharge,
            'version'=>VERSION_NUM,
            'version_date'=>VERSION_DATE,
            'version_type'=>VERSION_SUFFIX
        ]);
        return $this->fetch();
    }
}
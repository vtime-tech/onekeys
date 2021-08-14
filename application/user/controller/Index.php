<?php
namespace app\user\controller;


use app\common\model\Config;
use app\common\model\ExpenseRecord;
use app\common\model\Users;
use app\common\model\Web;
use think\Controller;
use think\facade\Session;
use think\facade\View;

View::share(['siteName'=>getSiteName()]);
class Index extends Controller
{
    protected $middleware = ['UserCheck'];

    /**
     * Iframe框
     * @return mixed
     */
    public function console()
    {
        $realnameSwitch = Config::where('name','realname_switch')->find();
        $paySwitch = Config::where('name','pay_switch')->find();
        $username = Session::get('username');
        $this->assign([
            'username'=>$username,
            'realnameSwitch'=>$realnameSwitch['value'],
            'paySwitch'=>$paySwitch['value']
        ]);
        return $this->fetch();
    }

    /**
     * 默认index跳转
     * @return \think\response\Redirect
     */
    public function index()
    {
        return redirect('/user/index/console');
    }

    /**
     * 控制台信息
     * @return mixed
     */
    public function home()
    {
        $uid = Session::get('uid');
        $userInfo = Users::where('id',$uid)->find();
        $count = Web::where('uid',$uid)->count();
        $expense = ExpenseRecord::where('uid',$uid)->sum('money');
        $this->assign([
           'balance'=>$userInfo['balance'],
            'vip'=>$userInfo['level'],
            'count'=>$count,
            'expense'=>$expense
        ]);
        return $this->fetch();
    }

}
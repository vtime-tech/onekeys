<?php

namespace app\user\controller;

use app\common\model\ExpenseRecord;
use app\common\model\Users;
use think\Controller;
use think\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;
use app\common\model\Daili as DailiModel;

View::share(['siteName' => getSiteName()]);

class Daili extends Controller
{
    protected $middleware = ['UserCheck'];

    /**
     * 代理等级
     * @return mixed
     */
    public function level()
    {
        $level = Users::where('id', Session::get('uid'))->value('level');
        $nextLevel = DailiModel::where('level', $level + 1)->find();
        if ($nextLevel) {
            $this->assign([
                'level' => $level,
                'maxLevel' => 1,
                'nextLevel' => $nextLevel['level'],
                'price' => $nextLevel['price']
            ]);
        } else {
            $this->assign([
                'level' => $level,
                'maxLevel' => 0,
                'nextLevel' => '',
                'price' => ''
            ]);
        }
        return $this->fetch();
    }

    /**
     * 代理升级
     * @return \think\response\Json|void
     */
    public function upLevel()
    {
        if (Request::isPost()) {
            $userInfo = Users::where('id', Session::get('uid'))->find();
            $nextLevel = $userInfo['level'] + 1;
            $level = DailiModel::where('level', $nextLevel)->find();
            if ($userInfo['balance'] < $level['price']) return msg(1, '余额不足');
            try {
                Db::startTrans();
                $Expense = new ExpenseRecord();
                Users::update(['level' => $userInfo['level'] + 1, 'balance' => $userInfo['balance'] - $level['price']], ['id' => Session::get('uid')]);
                $Expense->log($level['price'], $userInfo['balance'], $userInfo['balance'] - $level['price'], '代理升级 - VIP' . $nextLevel);
                Db::commit();
                return msg(0, '升级成功');
            } catch (\Exception $e) {
                Db::rollback();
                return msg(1, $e->getMessage());
            }
        }
    }

    /**
     * API信息
     * @return mixed
     */
    public function api()
    {
        $userInfo = Users::where('id', Session::get('uid'))->find();
        if ($userInfo['secret_id'] == '' && $userInfo['secret_key'] == '') {
            $hasApi = 1;
            $secret_id = '';
            $secret_key = '';
        } else {
            $hasApi = 0;
            $secret_id = $userInfo['secret_id'];
            $secret_key = $userInfo['secret_key'];
        }
        $this->assign([
            'hasApi' => $hasApi,
            'secret_id' => $secret_id,
            'secret_key' => $secret_key,
            'api_url'=>getSiteUrl().'/api'
        ]);
        return $this->fetch();
    }

    /**
     * 开通API
     * @return \think\response\Json|void
     */
    public function payload()
    {
        if (Request::isPost())
        {
            $userInfo = Users::where('id', Session::get('uid'))->find();
            if ($userInfo['secret_id'] != '' && $userInfo['secret_key'] != '') return msg(1,'存在API信息，请检查');
            $secret_id = random(7,true);
            $secret_key = random(16,false);
            Users::update(['secret_id'=>$secret_id,'secret_key'=>$secret_key],['id'=>Session::get('uid')]);
            return msg(0,'开通成功');
        }
    }
}
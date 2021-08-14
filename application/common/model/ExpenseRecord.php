<?php


namespace app\common\model;


use think\facade\Session;
use think\Model;

class ExpenseRecord extends Model
{
    public function log($money,$before,$after,$detail)
    {
        $uid = Session::get('uid');
        $this->save([
            'uid'=>$uid,
            'money'=>$money,
            'before'=>$before,
            'after'=>$after,
            'detail'=>$detail
        ]);
    }
}
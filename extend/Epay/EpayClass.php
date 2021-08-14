<?php
/**
 * 易支付扩展
 * @Author Mr.Zhang<7491821@qq.com>
 * @Date 2021-02-18
 */

namespace Epay;

use app\common\model\Config;
use app\common\model\Recharge;
use app\common\model\Users;
use think\Controller;
use think\Db;
use think\facade\Session;
use think\Response;
use think\Exception;
class EpayClass extends Controller
{
    /**
     * 支付方法
     * @return Response
     * @throws \Exception
     */
    public function pay(string $money,string $way)
    {
        $epay_switch = Config::where('name','epay_switch')->find();
        if ($epay_switch['value'] == 1)
        {
            return msg(1,'支付通道已关闭,请联系管理员');
        }
        $pid = Config::where('name','epay_id')->find();
        $siteUrl = Config::where('name','site_url')->find();
        $key = Config::where('name','epay_key')->find();
        $epay_url = Config::where('name','epay_url')->find();
        $param = [
            'pid' =>$pid['value'],
            'notify_url' => $siteUrl['value'].'/user/pay/epayNotify',
            'return_url' => $siteUrl['value'].'/user/pay/epayReturn',
        ];

        // 自定义区域
        $param['type'] = $way;
        $param['out_trade_no'] = createOutTradeNo();
        $param['name'] = '在线充值 - ' . $param['out_trade_no'];
        $param['money'] = $money;
        $param['sitename'] = getSiteName();

        // 将签名整合进参数
        $param['sign'] = $this->sign($param, $key['value']);
        $param['sign_type'] = 'MD5';

        $url = $epay_url['value'].'/submit.php?' . http_build_query($param);

        //将订单储存进数据库
        $uid = Session::get('uid');
        $array = [
            'uid'=>$uid,
            'out_trade_no'=>$param['out_trade_no'],
            'name'=>$param['name'],
            'money'=>$param['money'],
            'gateway'=>'Epay - '.$way,
            'status'=>1
        ];
        Recharge::create($array);
        return msg(0, '成功捕获', ['url' => $url]);
    }

    /**
     * 计算签名
     * @param array $param 需要提交的参数
     * @param string $sign_key 用户的支付秘钥
     * @return string
     */
    private function sign(array $param, string $sign_key): string
    {
        // 计算签名
        // 1.清除值为空的参数
        $param = array_filter($param);
        // 2.将参数降序排序
        ksort($param);
        // 3.将参数json编码
        $sign_param = http_build_query($param);
        $sign_param = urldecode($sign_param);
        // 4.MD5加密计算签名值
        return md5($sign_param . $sign_key);
    }

    /**
     * 异步回调验签
     * @return Response
     */
    public function notify(): Response
    {
        $param = request()->param('', false);

        // 验证out_trade_no和money
        $sql = Recharge::where('out_trade_no',$param['out_trade_no'])->find();
        if (!$sql)
        {
            return msg(1,'未找到订单,请联系管理员');
        }
        if ($sql['money'] != $param['money'])
        {
            return msg(1,'订单异常,请联系管理员');
        }
        // 验证这个订单状态是不是未支付
        if ($sql['status'] === 0)
        {
            return msg(1,'该订单已入账,请勿重新操作');
        }

        // 校验服务端签名
        $sever_sign = $param['sign'];
        unset($param['sign'],$param['sign_type']);
        $key = Config::where('name','epay_key')->find();
        $client_sign = $this->sign($param,$key['value']);
        if($sever_sign !== $client_sign) return msg(1,'签名错误');

        // 判断当前支付所属状态
        switch ($param['trade_status']) {
            case 'WAIT_BUYER_PAY':
                break;
            case 'TRADE_SUCCESS':
                // 支付成功，开始实现逻辑
                Db::startTrans();
                try {
                    Recharge::update(['status'=>0],['out_trade_no'=>$param['out_trade_no']]);
                    Users::where('id',$sql['uid'])->setInc('balance',$sql['money']);
                }catch (Exception $e)
                {
                    Db::rollback();
                    return msg(1,$e->getMessage());
                }
                Db::commit();
                break;
            case 'TRADE_FINISHED':
            case 'TRADE_CLOSED':
            default:
        }

        return response('success');
    }

    /**
     * 同步回调验签
     * @return Response
     */
    public function return(): Response
    {
        $param = request()->param('', false);

        // 验证out_trade_no和money
        $sql = Recharge::where('out_trade_no',$param['out_trade_no'])->find();
        if (!$sql)
        {
            return response('未找到订单,请联系管理员');
        }
        if ($sql['money'] != $param['money'])
        {
            return response('订单异常,请联系管理员');
        }
        // 验证这个订单状态是不是未支付
        if ($sql['status'] === 0)
        {
            return response('该订单已入账,请勿重新操作');
        }

        // 校验服务端签名
        $sever_sign = $param['sign'];
        unset($param['sign'],$param['sign_type']);
        $key = Config::where('name','epay_key')->find();
        $client_sign = $this->sign($param,$key['value']);
        if($sever_sign !== $client_sign) return msg(1,'签名错误');

        // 判断当前支付所属状态
        switch ($param['trade_status']) {
            case 'WAIT_BUYER_PAY':
                $tips = '未支付';
                break;
            case 'TRADE_SUCCESS':
                // 支付成功，开始实现逻辑
                Db::startTrans();
                try {
                    Recharge::update(['status'=>0],['out_trade_no'=>$param['out_trade_no']]);
                    Users::where('id',$sql['uid'])->setInc('balance',$sql['money']);
                }catch (Exception $e)
                {
                    Db::rollback();
                    return msg(1,$e->getMessage());
                }
                Db::commit();
                $tips = '支付成功';
                break;
            case 'TRADE_FINISHED':
            case 'TRADE_CLOSED':
            default:
                $tips = '支付状态异常';
        }
        $this->success($tips,'/user/index/console');
    }
}
<?php


namespace app\user\controller;

use Alipay\EasySDK\Kernel\Factory;
use app\common\model\Config;
use app\common\model\Recharge;
use app\common\model\Users;
use Epay\EpayClass;
use think\Controller;
use think\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;
use EasyWeChat\Factory as WxFactory;
View::share(['siteName' => getSiteName()]);

class Pay extends Controller
{
    protected $middleware = [
        'UserCheck' => ['only' => ['index','wxpayScan','wxpayCheck','alipayJump']],
    ];

    public function initialize()
    {
        parent::initialize();
        Factory::setOptions(alipayOptions());
    }

    /**
     * 用户支付
     * @return mixed|string|\think\Response|\think\response\Json
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\Exception
     */
    public function index()
    {
        $pay_switch = Config::where('name', 'pay_switch')->find();
        $alipaySwitch = Config::where('name', 'alipay_switch')->find();
        $wxpaySwitch = Config::where('name', 'wxpay_switch')->find();
        $qqpaySwitch = Config::where('name', 'qqpay_switch')->find();
        if (Request::isPost()) {
            $money = Request::post('money');
            $gateway = Request::post('gateway');
            if ($money === '' || $gateway === '') {
                return msg(1, '参数错误');
            }
            if ($pay_switch['value'] == 1) {
                return msg(1, '支付功能未开启');
            }
            switch ($gateway) {
                case 'alipay':
                    //当支付宝开关不开启时 自动走易支付通道
                    if ($alipaySwitch['value'] != '0') {
                        $epay = new EpayClass();
                        $result = $epay->pay($money, 'alipay');
                        return $result;
                    }
                    $out_trade_no = createOutTradeNo();
                    $array = [
                        'uid' => Session::get('uid'),
                        'out_trade_no' => $out_trade_no,
                        'name' => '在线充值 - ' . $out_trade_no,
                        'money' => $money,
                        'status' => 1,
                        'gateway' => 'alipay'
                    ];
                    Recharge::create($array);
                    return msg(0, 'Success', ['tradeNo' => $out_trade_no, 'type' => 'alipay']);
                    break;
                case 'wxpay':
                    //当微信支付开关不开启时 自动走易支付通道
                    if ($wxpaySwitch['value'] != '0') {
                        $epay = new EpayClass();
                        $result = $epay->pay($money, 'wxpay');
                        return $result;
                    }
                    $wxpay = WxFactory::payment(wxpayOptions());
                    $out_trade_no = createOutTradeNo();
                    $siteUrl = Config::where('name', 'site_url')->find();
                    $result = $wxpay->order->unify([
                        'body'=>'在线充值 - ' . $out_trade_no,
                        'out_trade_no' => $out_trade_no,
                        'total_fee' => $money * 100,
                        'trade_type' => 'NATIVE',
                        'notify_url' => $siteUrl['value'].'/user/pay/wxpayReturn'
                    ]);
                    $array = [
                        'uid' => Session::get('uid'),
                        'out_trade_no' => $out_trade_no,
                        'name' => '在线充值 - ' . $out_trade_no,
                        'money' => $money,
                        'status' => 1,
                        'gateway' => 'wxpay'
                    ];
                    Recharge::create($array);
                    if ($result['return_code'] == 'SUCCESS')
                    {
                        return msg(0,'获取成功',['url'=>$result['code_url'],'out_trade_no'=>$out_trade_no,'type'=>'wxpay']);
                    }
                    if ($result['return_code'] == 'FAIL')
                    {
                        return msg(1,$result['return_msg']);
                    }
                    return msg(1,'异常错误');
                    break;
                case 'qqpay':
                    //当QQ支付开关不开启时 自动走易支付通道
                    if ($qqpaySwitch['value'] != '0') {
                        $epay = new EpayClass();
                        $result = $epay->pay($money, 'wxpay');
                        return $result;
                    }
                    break;
            }
        }
        if ($pay_switch['value'] == 1) {
            return '支付功能未开启';
        }
        return $this->fetch();
    }

    /**
     * 支付宝订单支付
     * @return string|\think\Response
     */
    public function alipayJump()
    {
        $trade_No = Request::param('tradeNo');
        if (!$trade_No || $trade_No == '') {
            return '参数错误';
        }
        $sql = Recharge::where('out_trade_no', $trade_No)->find();
        if (!$sql || $sql['status'] == 0) {
            return '订单不存在，或是已经支付';
        }
        $siteUrl = Config::where('name', 'site_url')->find();
        $result = Factory::payment()->page()->pay($sql['name'], $sql['out_trade_no'], $sql['money'], $siteUrl['value'] . '/user/pay/alipayReturn');
        return response($result->body);
    }

    /**
     * 支付宝同步回调
     * @return string
     */
    public function alipayReturn()
    {
        $returnParam = Request::param();
        //验签
        $result = Factory::payment()->common()->verifyNotify($returnParam);
        if ($result) {
            $out_trade_no = Request::param('out_trade_no');
            $money = Request::param('total_amount');
            $result = Recharge::where('out_trade_no', $out_trade_no)->find();
            if (!$result) {
                return '订单不存在';
            }
            if ($result['money'] != $money) {
                return '订单信息不匹配';
            }
            Db::startTrans();
            try {
                Recharge::update(['status' => 0], ['out_trade_no' => $out_trade_no]);
                Users::where('id', $result['uid'])->setInc('balance', $money);
                Db::commit();
            } catch (\Exception $e) {
                return $e->getMessage();
            }
            $this->success('充值成功！', '/user/index/console');
        }
    }

    /**
     * 微信扫码
     * @return mixed|\think\response\Redirect
     */
    public function wxpayScan()
    {
        $code_url = Request::param('code_url');
        $out_trade_no = Request::param('out_trade_no');
        if ($code_url == '' || !isset($code_url)) {
            return redirect('/user/pay/index');
        }
        $this->assign([
            'qrcodeUrl' => $code_url,
            'out_trade_no' => $out_trade_no
        ]);
        return $this->fetch();
    }

    /**
     * 微信支付状态检查
     * @return string|\think\response\Json
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function wxpayCheck()
    {
        $out_trade_no = Request::param('out_trade_no');
        $sql = Recharge::where('out_trade_no',$out_trade_no)->where('status',1)->find();
        if (!$sql)
        {
            return '订单不存在或已经支付';
        }
        $wxpay = WxFactory::payment(wxpayOptions());
        $result = $wxpay->order->queryByOutTradeNumber($out_trade_no);
        if ($result['trade_state'] == 'SUCCESS' && $result['total_fee'] == $sql['money'] * 100)
        {
            Db::startTrans();
            try {
                Recharge::update(['status'=>0],['out_trade_no'=>$out_trade_no]);
                Users::where('id',$sql['uid'])->setInc('balance',$sql['money']);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                return msg(1,$e->getMessage());
            }
            return msg(0,'支付成功');
        }
        return msg(1,'暂无结果');
    }

    /**
     * 易支付同步回调
     * @return \think\Response
     */
    public function epayReturn()
    {
        $epay = new EpayClass();
        return $epay->return();
    }

    /**
     * 易支付异步回调
     * @return \think\Response
     */
    public function epayNotify()
    {
        $epay = new EpayClass();
        return $epay->notify();
    }
}
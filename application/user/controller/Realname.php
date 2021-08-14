<?php

namespace app\user\controller;

use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Member\Identification\Models\IdentityParam;
use Alipay\EasySDK\Member\Identification\Models\MerchantConfig;
use app\common\model\Config;
use app\common\model\ExpenseRecord;
use app\common\model\RealnamePersonal;
use app\common\model\Users;
use ShumaiData\IdentityCertify;
use think\Controller;
use think\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

View::share(['siteName' => getSiteName()]);

class Realname extends Controller
{
    protected $middleware = ['UserCheck' => ['except' => ['certifyjump']]];

    public function initialize()
    {
        parent::initialize();
        Factory::setOptions(alipayOptions());
    }

    /**
     * 实名认证
     * @return mixed|string|\think\response\Json
     * @throws \think\Exception
     */
    public function index()
    {
        //读取实名状态
        $username = Session::get('username');
        $uid = Session::get('uid');
        $realnameStatus = Users::where('username', $username)->where('id', $uid)->find();
        $realnameSwitch = Config::where('name', 'realname_switch')->find();
        if (Request::isPost()) {
            $realname = Request::post('realname');
            $idnum = Request::post('idnum');
            $gateway = Request::post('gateway');
            if ($realnameSwitch['value'] == 1) {
                return msg(1, '实名功能并未开启');
            }
            if ($realname == '' || $idnum == '' || $gateway == '') {
                return msg(1, '参数错误');
            }
            $userInfo = Users::where('username', $username)->where('id', $uid)->find();
            $checkPrice = Config::where('name', 'realname_price')->find();
            if ($userInfo['balance'] < $checkPrice['value']) {
                return msg(1, '余额不足，请充值后再试');
            }
            //先收钱 后干活 拿钱办事
            Users::where('username', $username)->where('id', $uid)->setDec('balance', $checkPrice['value']);
            $log = new ExpenseRecord();
            $log->log($checkPrice['value'], $userInfo['balance'], $userInfo['balance'] - $checkPrice['value'], '实名认证');
            switch ($gateway) {
                case 'generic':
                    $Switch = Config::where('name', 'tydata_realnameSwitch')->find();
                    if ($Switch['value'] == 1) {
                        return msg(0, '该通道并未开启');
                    }
                    //接入天眼数据
                    $identityCertify = new IdentityCertify();
                    $sql = Config::where('name', 'realname_tydata')->find();
                    $res = $identityCertify->phoneCheck($realname, $idnum, $userInfo['phone'], $sql['value']);
                    $result = json_decode($res->getContent(), true);
                    $array = [
                        'uid' => $userInfo['id'],
                        'type' => 'generic',
                        'name' => $realname,
                        'mobile' => $userInfo['phone'],
                        'idcard' => $idnum,
                        'status' => 1
                    ];
                    if ($result['code'] != 1) {
                        Users::update(['realname' => 0], ['username' => $username, 'id' => $uid]);
                        $array['status'] = 0;
                    }
                    RealnamePersonal::create($array);
                    return $res;
                case 'alipay':
                    $Switch = Config::where('name', 'alipay_realnameSwitch')->find();
                    if ($Switch['value'] == 1) {
                        return msg(0, '该通道并未开启');
                    }
                    //接入支付宝身份认证
                    $identityParam = new IdentityParam();
                    $identityParam->identityType = "CERT_INFO";
                    $identityParam->certType = "IDENTITY_CARD";
                    $identityParam->certName = $realname;
                    $identityParam->certNo = $idnum;

                    $merchantConfig = new MerchantConfig();
                    $merchantConfig->returnUrl = "";
                    $result = Factory::member()->identification()->init(createOutTradeNo(), 'FACE', $identityParam, $merchantConfig);
                    if ($result->code != 10000) {
                        return msg(1, '初始化错误');
                    }
                    $array = [
                        'uid' => $userInfo['id'],
                        'type' => 'alipay',
                        'name' => $realname,
                        'mobile' => $userInfo['phone'],
                        'idcard' => $idnum,
                        'certify_id' => $result->certifyId,
                        'status' => 1
                    ];
                    RealnamePersonal::create($array);
                    return msg(0, '获取成功', ['certifyId' => $result->certifyId]);
                default :
                    return msg(0, '错误的认证方式！');
            }
        }
        $userInfo = Users::where('username', $username)->where('id', $uid)->find();
        if ($realnameSwitch['value'] == 1 && $realnameStatus['realname'] == 1) {
            return '实名功能并未开启';
        }
        if ($userInfo['phone'] == ''){
            return '您未绑定手机，请联系管理员绑定';
        }
        $realnamePrice = Config::where('name', 'realname_price')->find();
        $realnamePersonalInfo = RealnamePersonal::where("uid", $uid)->find();
        //当状态为实名渲染脱敏处理后的内容
        if ($realnameStatus['realname'] == 0) {
            $this->assign(
                [
                    //经过脱敏处理后的实名信息
                    'type' => realNameType($realnamePersonalInfo['type']),
                    'idCard' => displayIdCardVerify($realnamePersonalInfo['idcard']),
                    'trueName' => displayTrueName($realnamePersonalInfo['name']),
                    'date' => $realnamePersonalInfo['update_time']
                ]
            );
        }
        $this->assign([
            'realnameStatus' => $realnameStatus['realname'],
            'realnamePrice' => $realnamePrice['value']
        ]);
        return $this->fetch();
    }

    /**
     * 身份验证生成验证码
     * @return mixed|\think\response\Redirect
     */
    public function alipayCertify()
    {
        $certifyId = Request::param('certifyId');
        if ($certifyId == '' || !isset($certifyId)) {
            return redirect('/user/realname/index');
        }
        $site_url = Config::where('name', 'site_url')->find();
        $this->assign([
            'qrcodeUrl' => $site_url['value'] . '/user/realname/certifyjump?certifyId=' . $certifyId,
            'certifyId' => $certifyId
        ]);
        return $this->fetch();
    }

    /**
     * 身份验证跳转支付宝验证
     * @return string|\think\response\Redirect
     */
    public function certifyjump()
    {
        $certifyId = Request::param('certifyId');
        if ($certifyId == '' || !isset($certifyId)) {
            return 'error';
        }
        $sql = RealnamePersonal::where('certify_id', $certifyId)->where('status', 1)->find();
        if (!$sql) {
            return 'error';
        }
        $result = Factory::member()->identification()->certify($certifyId);
        return redirect($result->body);
    }

    /**
     * 身份验证检查状态
     * @return \think\response\Json
     * @throws \Exception
     */
    public function checkStatus()
    {
        if (Request::isPost()) {
            $certifyId = Request::post('certifyId');
            $result = Factory::member()->identification()->query($certifyId);
            if ($result->passed == 'F') {
                return msg(1, '验证失败');
            }
            if ($result->passed == 'T') {
                $sql = RealnamePersonal::where('certify_id', $certifyId)->find();
                if (!$sql) {
                    return msg(1, '系统错误');
                };
                if ($sql['status'] == 0) {
                    return msg(1, '您已验证过了');
                }
                Db::startTrans();
                try {
                    RealnamePersonal::update(['status' => 0], ['certify_id' => $certifyId]);
                    Users::update(['realname' => 0], ['id' => $sql['uid']]);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    return msg(1, $e->getMessage());
                }
                return msg(0, '验证成功');
            }
        }
    }
}
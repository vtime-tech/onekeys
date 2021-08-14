<?php

use app\common\model\Config;
use app\common\model\Sms;
use smsbao\Smsbao;
use think\Exception;
use think\facade\Request;
use think\facade\Cache;
use Alipay\EasySDK\Kernel\Config as AlipayConfig;
use think\response\Json;

// 应用公共文件
define('VERSION_NUM', '1.0.1');
define('VERSION_DATE', '20210814');
define('VERSION_SUFFIX', 'release');
define('VERSION_NAME', 'v' . VERSION_NUM . '.' . VERSION_DATE . '_' . VERSION_SUFFIX);

//获取网站名称
function getSiteName()
{
    $siteName = Config::where('name', 'site_name')->find();
    return $siteName['value'];
}

//获取网站标题
function getSiteTitle()
{
    $siteTitle = Config::where('name', 'site_title')->find();
    return $siteTitle['value'];
}

//获取网站关键字
function getSiteKeywords()
{
    $siteKeywords = Config::where('name', 'site_keywords')->find();
    return $siteKeywords['value'];
}

//获取网站描述
function getSiteDescription()
{
    $siteDescription = Config::where('name', 'site_description')->find();
    return $siteDescription['value'];
}

//获取网站地址
function getSiteUrl()
{
    $siteUrl = Config::where('name', 'site_url')->find();
    return $siteUrl['value'];
}

/**
 * 获取客户端IP
 * @return string IP地址
 */
function getIp(): string
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = $list[0];
    }
    if (!ip2long($ip)) {
        $ip = '';
    }
    return $ip;
}

/**
 * 生产随机值
 * @param int $length 长度
 * @param bool $numeric 是否全数值
 * @return string
 */
function random(int $length, bool $numeric = false): string
{
    $seed = base_convert(md5(microtime()), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
    $hash = '';
    $max = strlen($seed) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $seed[mt_rand(0, $max)];
    }
    return $hash;
}

/**
 * ajax返回信息
 * @param int $code 状态码
 * @param string $msg 返回信息
 * @param array $data 返回数据
 * @return Json
 */
function msg(int $code = 1, string $msg = '未知错误', array $data = [])
{
    $array = [
        'code' => $code,
        'msg' => $msg,
        'data' => $data
    ];
    return json($array);
}

/**
 * 发送短信验证码
 * @param int $phone 手机号码
 * @return Json
 */
function sendSmsCode(int $phone)
{
    if ($phone === '' || strlen($phone) != 11) {
        return msg(1, '参数错误');
    }
    $ipCheck = Cache::get(Request::ip());
    if ($ipCheck != null) {
        return msg(1, '您的IP请求过于频繁');
    }
    $phoneCheck = Cache::get($phone);
    if ($phoneCheck != null) {
        return msg(1, '您发送的太频繁了，60秒仅发一次');
    }
    $username = Config::where('name', 'sms_username')->find();
    $password = Config::where('name', 'sms_password')->find();
    $name = Config::where('name', 'sms_sign')->find();
    $smsBao = new Smsbao($username['value'], $password['value']);
    $code = $smsBao->random(6, true);
    $content = "【" . $name['value'] . '】您的验证码为' . $code . '，1分钟内有效，请尽快使用。';
    $result = $smsBao->send($phone, $content);
    if (!$result) {
        return msg(1, '发送失败,请联系管理员');
    }
    $array = [
        'mobile' => $phone,
        'code' => $code,
        'ip' => Request::ip()
    ];
    Cache::set($phone, 0, 60);
    Cache::set($ipCheck, 0, 60);
    Sms::create($array);
    return msg(0, '发送成功');
}

/**
 * 发送短信【自定义】
 * @param int $phone 手机号码
 * @param string $content 短信内容
 * @return Json
 */
function sendSms(int $phone, string $content)
{
    $username = Config::where('name', 'sms_username')->find();
    $password = Config::where('name', 'sms_password')->find();
    $name = Config::where('name', 'sms_sign')->find();
    $smsBao = new Smsbao($username['value'], $password['value']);
    $sms = "【" . $name['value'] . '】' . $content;
    $result = $smsBao->send($phone, $sms);
    if (!$result) {
        return msg(1, '发送失败');
    }
    return msg(0, '发送成功');
}

/**
 * 创建哈希密码
 * @param string $password 明文密码
 * @param string $salt 盐
 * @return string 哈希密码
 */
function passwordCreate(string $password, string $salt): string
{
    return password_hash($password . $salt, PASSWORD_DEFAULT);
}

/**
 * 哈希密码验证
 * @param string $password 明文密码
 * @param string $hash 哈希密码
 * @param string $salt 盐
 * @return bool 是否匹配
 */
function passwordVerify(string $password, string $hash, string $salt): bool
{
    //兼容旧版密码算法
    if (strlen($hash) === 32) {
        return md5($password) === $hash;
    } else {
        return password_verify($password . $salt, $hash);
    }
}

/**
 * 是否是旧版密码算法
 * @param string $password
 * @return bool
 */
function isOldPassword(string $password): bool
{
    return strlen($password) === 32;
}

/**
 * 验证身份证号是否正确
 * @param $idCard
 * @return bool
 */
function verifyIdCardVerify($idCard): bool
{
    // 检查身份证号码
    if (strlen($idCard) !== 18) {
        return false;
    }
    // 取出身份证前17位信息码
    $idInfo = substr($idCard, 0, 17);
    // 取出校验码
    $verifyCode = substr($idCard, 17, 1);
    // 加权因子
    $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
    // 校验码对应值
    $verifyCodeList = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
    // 根据前17位计算校验码
    $total = 0;
    for ($i = 0; $i < 17; $i++) {
        $total += substr($idInfo, $i, 1) * $factor[$i];
    }
    // 取模
    $mod = $total % 11;
    // 比较校验码，不相等则拒绝请求
    if ($verifyCode !== $verifyCodeList[$mod]) {
        return false;
    }
    return true;
}

/**
 * 实名认证方式
 * @param string $type
 * @return string
 */
function realNameType(string $type) : string
{
    switch ($type) {
        case "generic" :
            return "三要素身份认证";
        case "alipay":
            return "支付宝APP认证";
        case "artificial":
            return "人工认证";
        default :
            return "未知认证方式";
    }
}

/**
 * 身份证号脱敏处理
 * @param string $idCard 身份证号码
 * @return string
 */
function displayIdCardVerify(string $idCard): string
{
    return substr($idCard, 0, 6) . "**********" . mb_substr($idCard, mb_strlen($idCard) - 2, 2);
}

/**
 * 手机号脱敏处理
 * @param string $mobile 手机号码
 * @return string
 */
function displayMobile(string $mobile): string
{
    return substr($mobile, 0, 3) . "****" . mb_substr($mobile, mb_strlen($mobile) - 4, 4);
}

/**
 * 姓名脱敏处理
 * @param string $trueName 姓名
 * @return string
 */
function displayTrueName(string $trueName): string
{
    if (strpos($trueName, '·') !== false) {
        $trueName = explode('·', $trueName);
        $hide = "";
        for ($i = 0; $i < mb_strlen($trueName[1]); $i++) {
            $hide .= "*";
        }
        return $trueName[0] . $hide;
    } else {
        $hide = "";
        for ($i = 0; $i < mb_strlen($trueName) - 1; $i++) {
            $hide .= "*";
        }
        return $hide . mb_substr($trueName, mb_strlen($trueName) - 1, mb_strlen($trueName), 'utf-8');
    }
}

/**
 * 时间差计算  这里只提供月差
 * @parem  int $begin   开始时间
 * @parem  int $end     结束时间
 */
function date_month_diff($begin, $end){
    if(!$begin || !$end) return FALSE;
    $begin = intval($begin);
    $end= intval($end);
    //计算月份差
    $mon = date('m', $end) - date('m', $begin);
    //计算月份差
    $day = date('d', $end) - date('d', $begin);
    //计算年份差
    $y  = date('y', $end) - date('y', $begin);
    //如果结束日期的天  减去  开始时间的天数   小于  0   &&  并且 月份相减的差 等于 1
    if($day < 0 && $mon == 1){
        //begin的当月最大天数
        $begin_m_d_n = date('t', $begin);
        //begin的当天数值
        $begin_day = date('d', $begin);
        $day = (date('d', $end)) + ($begin_m_d_n  - $begin_day);
    }
    //如果年份不同
    if( $y>0){
        //累加月份
        $mon +=  $y*12;
    }
    $datedif = array('mon' => $mon, 'day' => $day);
    return $datedif;
}

/**
 * 创建订单号
 * @return string 订单号
 * @throws Exception
 */
function createOutTradeNo(): string
{
    do {
        // 生成商户订单号
        $datetime = date('YmdHis');
        $micro_time = substr(explode(' ', microtime())[0], 2, 5);
        $rand = random_int(10000, 99999);

        // 整理商户订单号
        $out_trade_no = $datetime . $micro_time . $rand;

        // 判断是否重复，若重复则重新生成
        $cache = Cache::get('outTradeNo_' . $out_trade_no);
        Cache::set('outTradeNo_' . $out_trade_no, true, 1);

    } while ($cache);

    return $out_trade_no;
}

function alipayOptions()
{
    $appId= Config::where('name','alipay_appId')->find();
    $privateKey= Config::where('name','alipay_privateKey')->find();
    $publicKey= Config::where('name','alipay_publicKey')->find();
    $options = new AlipayConfig();
    $options->protocol = 'https';
    $options->gatewayHost = 'openapi.alipay.com';
    $options->signType = 'RSA2';
    $options->appId = $appId->value;
    $options->merchantPrivateKey = $privateKey->value;
    $options->alipayPublicKey = $publicKey->value;
    //可设置异步通知接收服务地址（可选）
//    $options->notifyUrl = "/user/pay/notify";
    return $options;
}

function wxpayOptions()
{
    $appId = Config::where('name','wxpay_appId')->find();
    $mchId = Config::where('name','wxpay_mchId')->find();
    $key = Config::where('name','wxpay_key')->find();
    $config = [
        'app_id'             => $appId['value'],
        'mch_id'             => $mchId['value'],
        'key'                => $key['value']
    ];
    return $config;
}
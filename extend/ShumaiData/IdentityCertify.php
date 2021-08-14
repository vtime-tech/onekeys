<?php


namespace ShumaiData;


class IdentityCertify
{
    public function phoneCheck(string $idname, int $idnum, string $phone, string $appcode)
    {
        //检查字段
        if ($idname === '' || $idnum ==='')
        {
            return msg(1,'参数错误');
        }
        // 检查身份证号码
        if (strlen($idnum) !== 18) {
            return msg(1, '仅支持使用18位身份证号码进行实名认证');
        }

        if (!verifyIdCardVerify($idnum)) {
            return msg(1, '身份证号码不正确');
        }

        // 请求第三方接口进行实名认证资料校验
        $curl = curl_init();
        $url = 'https://mobile3elements.shumaidata.com/mobile/verify_real_name?';
        $url .= 'name=' . urlencode($idname) . '&idcard=' . $idnum . '&mobile=' . $phone;

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        // 初始化header
        $header = ['Authorization:APPCODE ' . $appcode];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($result, true);
        if (!$result)
        {
            return msg(1,'系统异常');
        }
        if ($result['code'] != 0 )
        {
            return msg(1, $result['message']);
        }
        switch ($result['result']['res']) {
            case 1:
                return msg(0, '认证成功');
            default:
                return msg(1, '认证失败');
        }
    }
}
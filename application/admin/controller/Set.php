<?php


namespace app\admin\controller;


use app\common\model\Config;
use think\Controller;
use think\Db;
use think\facade\Request;
use think\facade\View;

View::share(['siteName' => getSiteName()]);
class Set extends Controller
{
    protected $middleware = ['AdminCheck'];

    //系统设置
    public function systemSet()
    {
        if (Request::isPost())
        {
            Db::startTrans();
            try {
                Config::update(['value'=>Request::param('name')],['name'=>'site_name']);
                Config::update(['value'=>Request::param('title')],['name'=>'site_title']);
                Config::update(['value'=>Request::param('description')],['name'=>'site_description']);
                Config::update(['value'=>Request::param('keywords')],['name'=>'site_keywords']);
                Config::update(['value'=>Request::param('url')],['name'=>'site_url']);
                Config::update(['value'=>Request::param('email')],['name'=>'site_email']);
                Config::update(['value'=>Request::param('icp')],['name'=>'site_icp']);
                Config::update(['value'=>Request::param('gov')],['name'=>'site_gov']);
                Config::update(['value'=>Request::param('company')],['name'=>'site_company']);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                return msg(1,$e->getMessage());
            }
            return msg(0,'修改成功');
        }
        $siteUrl = Config::where('name','site_url')->find();
        $siteTitle = Config::where('name','site_title')->find();
        $siteIcp = Config::where('name','site_icp')->find();
        $siteGov = Config::where('name','site_gov')->find();
        $siteEmail = Config::where('name','site_email')->find();
        $siteCompany = Config::where('name','site_company')->find();
        $array = [
          'name'=>getSiteName(),
            'keywords'=>getSiteKeywords(),
            'description'=>getSiteDescription(),
            'url'=>$siteUrl['value'],
            'title'=>$siteTitle['value'],
            'icp'=>$siteIcp['value'],
            'gov'=>$siteGov['value'],
            'email'=>$siteEmail['value'],
            'company'=>$siteCompany['value']
        ];
        $this->assign($array);
        return $this->fetch();
    }

    //支付设置
    public function paySet()
    {
        if (Request::isPost())
        {
            Db::startTrans();
            try {
                Config::update(['value'=>Request::param('alipay_switch')],['name'=>'alipay_switch']);
                Config::update(['value'=>Request::param('epay_switch')],['name'=>'epay_switch']);
                Config::update(['value'=>Request::param('wxpay_switch')],['name'=>'wxpay_switch']);
                Config::update(['value'=>Request::param('pay_switch')],['name'=>'pay_switch']);
                Config::update(['value'=>Request::param('alipay_appId')],['name'=>'alipay_appId']);
                Config::update(['value'=>Request::param('alipay_privateKey')],['name'=>'alipay_privateKey']);
                Config::update(['value'=>Request::param('alipay_publicKey')],['name'=>'alipay_publicKey']);
                Config::update(['value'=>Request::param('wxpay_appId')],['name'=>'wxpay_appId']);
                Config::update(['value'=>Request::param('wxpay_mchId')],['name'=>'wxpay_mchId']);
                Config::update(['value'=>Request::param('wxpay_key')],['name'=>'wxpay_key']);
                Config::update(['value'=>Request::param('epay_url')],['name'=>'epay_url']);
                Config::update(['value'=>Request::param('epay_id')],['name'=>'epay_id']);
                Config::update(['value'=>Request::param('epay_key')],['name'=>'epay_key']);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                return msg(1,$e->getMessage());
            }
            return msg(0,'修改成功');
        }
        $pay_switch = Config::where('name','pay_switch')->find();
        $alipay_switch = Config::where('name','alipay_switch')->find();
        $wxpay_switch = Config::where('name','wxpay_switch')->find();
        $epay_switch = Config::where('name','epay_switch')->find();
        $alipay_appId = Config::where('name','alipay_appId')->find();
        $alipay_privateKey = Config::where('name','alipay_privateKey')->find();
        $alipay_publicKey = Config::where('name','alipay_publicKey')->find();
        $epay_url = Config::where('name','epay_url')->find();
        $epay_id = Config::where('name','epay_id')->find();
        $epay_key = Config::where('name','epay_key')->find();
        $wxpay_appId = Config::where('name','wxpay_appId')->find();
        $wxpay_mchId = Config::where('name','wxpay_mchId')->find();
        $wxpay_key = Config::where('name','wxpay_key')->find();
        $this->assign([
            'alipay_switch'=>$alipay_switch['value'],
            'epay_switch'=>$epay_switch['value'],
            'pay_switch'=>$pay_switch['value'],
            'wxpay_switch'=>$wxpay_switch['value'],
            'alipay_appId'=>$alipay_appId['value'],
            'alipay_privateKey'=>$alipay_privateKey['value'],
            'alipay_publicKey'=>$alipay_publicKey['value'],
            'wxpay_appId'=>$wxpay_appId['value'],
            'wxpay_mchId'=>$wxpay_mchId['value'],
            'wxpay_key'=>$wxpay_key['value'],
            'epay_url'=>$epay_url['value'],
            'epay_id'=>$epay_id['value'],
            'epay_key'=>$epay_key['value']
        ]);
        return $this->fetch();
    }

    //实名设置
    public function realnameSet()
    {
        if (Request::isPost())
        {
            Db::startTrans();
            try {
                Config::update(['value'=>Request::param('alipay_realnameSwitch')],['name'=>'alipay_realnameSwitch']);
                Config::update(['value'=>Request::param('realname_tydata')],['name'=>'realname_tydata']);
                Config::update(['value'=>Request::param('tydata_realnameSwitch')],['name'=>'tydata_realnameSwitch']);
                Config::update(['value'=>Request::param('realname_switch')],['name'=>'realname_switch']);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                return msg(1,$e->getMessage());
            }
            return msg(0,'修改成功');
        }
        $realname_switch = Config::where('name','realname_switch')->find();
        $alipay_realnameSwitch = Config::where('name','alipay_realnameSwitch')->find();
        $tydata_realnameSwitch = Config::where('name','tydata_realnameSwitch')->find();
        $realname_tydata = Config::where('name','realname_tydata')->find();
        $this->assign([
           'alipay_realnameSwitch'=>$alipay_realnameSwitch['value'],
            'tydata_realnameSwitch'=>$tydata_realnameSwitch['value'],
            'realname_tydata'=>$realname_tydata['value'],
            'realname_switch'=>$realname_switch['value']
        ]);
        return $this->fetch();
    }

    /**
     * 短信设置
     * @return mixed
     */
    public function smsSet()
    {
        if (Request::isPost())
        {
            Db::startTrans();
            try {
                Config::update(['value'=>Request::param('sms_switch')],['name'=>'sms_switch']);
                Config::update(['value'=>Request::param('sms_sign')],['name'=>'sms_sign']);
                Config::update(['value'=>Request::param('sms_username')],['name'=>'sms_username']);
                Config::update(['value'=>Request::param('sms_password')],['name'=>'sms_password']);
                Db::commit();
            }catch (\Exception $e)
            {
                Db::rollback();
                return msg(1,$e->getMessage());
            }
            return msg(0,'修改成功');
        }
        $sms_switch = Config::where('name','sms_switch')->find();
        $sms_sign = Config::where('name','sms_sign')->find();
        $sms_username = Config::where('name','sms_username')->find();
        $sms_password = Config::where('name','sms_password')->find();
        $this->assign([
            'sms_switch'=>$sms_switch['value'],
            'sms_username'=>$sms_username['value'],
            'sms_sign'=>$sms_sign['value'],
            'sms_password'=>$sms_password['value']
        ]);
        return $this->fetch();
    }
}
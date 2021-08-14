<?php


namespace Kangle;


use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use think\Exception;

class KangleApi
{
    private $client;
    private $ip;
    private $port;
    private $authcode;
    private $productId;

    public function __construct($ip = "", $port = "", $authcode = "", $productId = "")
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->authcode = $authcode;
        $this->productId = $productId;
        $this->client = new Client(['timeout' => 10]);
    }

    /**
     * 安装
     * @param $secondName
     * @param $domain
     * @param $install
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addHost($secondName, $domain, $install)
    {
        $password = substr(md5(uniqid() . rand(1, 10000)), 16);
        $authcode = $this->authcode;
        $rand = rand(1, 9999);
        $do = 'add_vh';
        $skey = md5($do . $authcode . $rand);
        $wholeDomain = $secondName . '.' . $domain;
        $array = [
            'c' => 'whm',
            'a' => $do,
            'r' => $rand,
            's' => $skey,
            'name' => $secondName,
            'passwd' => $password,
            'init' => 1,
            'json' => 1,
            'product_id' => $this->productId
        ];
        try {
            //创建主机
            $response = $this->client->request('GET', trim($this->ip) . ':' . trim($this->port) . '/api/?' . http_build_query($array));
            $buildReturn = json_decode($response->getBody()->getContents(), true);
            //判断是否创建成功
            if ($buildReturn['result'] != 200) {
                switch ($buildReturn['result']) {
                    case 500:
                        return ['code' => 1, 'msg' => '操作失败,前缀已占用'];
                    case 403:
                        return ['code' => 1, 'msg' => $buildReturn['msg']];
                    default :
                        return ['code' => 1, 'msg' => '未知错误,请联系开发者'];
                }
            }
//            //登录获取Cookie Guzzlehttp Post玄学问题
//            $response = $this->client->request('POST', trim($this->ip) . ':' . trim($this->port) . '/vhost/index.php?c=session&a=login',[
//                'multipart'=>[
//                    [
//                        'name'     => 'username',
//                        'contents' => $secondName,
//                    ],
//                    [
//                        'name'     => 'passwd',
//                        'contents' => $password,
//                    ]
//                ]
//            ]);
            //登录获取Cookie
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->ip . ":" . $this->port . '/vhost/index.php?c=session&a=login');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('username' => $secondName, 'passwd' => $password)));
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
            $p = curl_exec($ch);
            curl_close($ch);
            preg_match('/PHPSESSID=(.{26})/i', $p, $matches);
            $cookie = $matches[1];

            //设置Cookie
            $cookieJar = CookieJar::fromArray([
                'PHPSESSID' => $cookie
            ], trim($this->ip));

            //访问首页(必须访问才可以操作后续的步骤)
            $this->client->request('GET', trim($this->ip) . ':' . trim($this->port) . '/vhost/index.php', [
                'cookies' => $cookieJar
            ]);

            //拼接绑定域名的参数
            $array = [
                'c' => 'domain',
                'a' => 'add',
                'domain' => urlencode($wholeDomain),
                'subdir' => '/wwwroot',
                'replace' => 0
            ];
            //绑定域名
            $response = $this->client->request('GET', trim($this->ip) . ':' . trim($this->port) . '/vhost/index.php?' . http_build_query($array), [
                'cookies' => $cookieJar
            ]);
            $returnMsg = $response->getBody()->getContents();
            if ($returnMsg != '成功') {
                return ['code' => 1, 'msg' => $returnMsg];
            }
            //绑定成功开始初始化搭建
            $array = [
                'user' => $secondName,
                'domain' => $domain,
                'password' => $password
            ];
            $response = $this->client->request('GET', trim($this->ip) . '/' . $install . '.php?' . http_build_query($array));
            $result = json_decode($response->getBody()->getContents(), true);
            //返回错误
            if ($result['code'] == 1) {
                return ['code' => 1, 'msg' => $result['msg']];
            }
            //返回成功
            return ['code' => 0, 'msg' => '搭建成功', 'data' => ['password' => $password]];
        } catch (RequestException $e) {
            return ['code' => 1, 'msg' => '服务器异常'];
        }
    }

    /**
     * 删除主机
     * @param $secondName
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delHost($secondName)
    {
        $authcode = $this->authcode;
        $rand = rand(1, 9999);
        $do = 'del_vh';
        $skey = md5($do . $authcode . $rand);
        $array = [
            'c' => 'whm',
            'a' => $do,
            'r' => $rand,
            's' => $skey,
            'name' => $secondName,
            'json' => 1
        ];
        //执行主机删除
        try {
            $response = $this->client->request('GET', trim($this->ip) . ':' . trim($this->port) . '/api/index.php?' . http_build_query($array));
            $returnMsg = json_decode($response->getBody()->getContents(), true);
            if ($returnMsg['result'] != 200) {
                return ['code' => 1, 'msg' => '删除失败'];
            }
            return ['code' => 0, 'msg' => '删除成功'];
        } catch (RequestException $e) {
            return ['code' => 1, 'msg' => '服务器异常'];
        }
    }

    /**
     * 系统信息
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function serverInfo()
    {
        $authcode = $this->authcode;
        $rand = rand(1, 9999);
        $do = 'info';
        $skey = md5($do . $authcode . $rand);
        $array = [
            'c' => 'whm',
            'a' => $do,
            'r' => $rand,
            's' => $skey,
            'json' => 1
        ];
        try {
            $response = $this->client->request('GET', trim($this->ip) . ':' . trim($this->port) . '/api/index.php?' . http_build_query($array));
            $returnMsg = json_decode($response->getBody()->getContents(), true);
            if ($returnMsg['result'] != 200) {
                return ['code' => 1, 'msg' => $returnMsg['msg']];
            }
            return ['code' => 0, 'msg' => '获取成功', 'data' => $returnMsg];
        } catch (RequestException $e) {
            return ['code' => 1, 'msg' => '服务器异常'];
        }
    }
}
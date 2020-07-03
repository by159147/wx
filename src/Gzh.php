<?php


namespace Faed\Wx;
use Illuminate\Support\Facades\Cache;

class Gzh
{
    public $config;

    public $accessToken;

    public $openid;

    public $userinfo;

    public function __construct($app = 'default')
    {
        $this->config = config("wx.app_gzh.{$app}");
    }


    /**
     * @param $code
     * @return $this
     * @throws \Exception
     */
    public function getOpenid($code)
    {
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->config['appid']}&secret={$this->config['secret']}&code={$code}&grant_type=authorization_code";
        $c = $this->getContents($url);
        $this->openid = $c['openid'];
        $this->accessToken = $c['access_token'];
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function getUserInfo()
    {
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$this->accessToken}&openid={$this->openid}&lang=zh_CN";
        $c = $this->getContents($url);
        $this->userinfo = $c;
        return $this;
    }

    /**
     * @param $templateId
     * @param $openid
     * @param array $templateData
     * @param string $page
     * @param string $xcxTag
     * @param string $http
     * @return mixed
     * @throws \Exception
     */
    public function templateSend( $templateId, $openid ,$templateData = [],$page = '' , $xcxTag = 'default' , $http = '')
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$this->token}";

        $data = [
            'touser' => $openid,
            'template_id'=>$templateId,
            'data'=>$templateData,
        ];

        //小程序跳转
        if ($page){
            $data = array_merge($data,['miniprogram'=>[
                'appid'=> config("wx.app_xcx.{$xcxTag}.appid"),
                'pagepath'=> $page,
            ]]);
        }

        //网页跳转
        if ($http){
            $data = array_merge(['url'=>$http],$data);
        }

       return $this->post($url,$data);
    }


    /**
     * @return $this
     * @throws \Exception
     */
    public function getAccessToken()
    {
        if (!($this->accessToken = Cache::get("access_token:{$this->config['appid']}"))){

            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->config['appid']}&secret={$this->config['secret']}";

            $c = $this->getContents($url);

            Cache::put("access_token:{$this->config['appid']}",$c['access_token'],$c['expires_in']-10);

            $this->accessToken = $c['access_token'];

        }
        return $this;
    }

    /**
     * @param $name
     * @return mixed | void
     * @throws \Exception
     */
    public function __get($name)
    {
        if ($name === 'token'){
            return $this->getAccessToken()->accessToken;
        }
    }



    /**
     * @param $url
     * @return mixed
     * @throws \Exception
     */
    public function getContents($url)
    {
        $c = file_get_contents($url);
        $c = json_decode($c,true);
        $this->outError($c);
        return $c;
    }


    /**
     * @param $url
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function post($url,$data)
    {
        $c = $this->requestPost($url,$data);
        $c = json_decode($c,true);
        $this->outError($c);
        return $c;
    }


    /**
     * @param $c
     * @throws \Exception
     */
    public function outError($c)
    {
        if (!empty($c['errcode'])){
            throw new \Exception("失败错误码:{$c['errcode']},错误msg:{$c['errmsg']}");
        }
    }

    /**
     * @param string $url
     * @param string $param
     * @return bool|mixed
     */
    function requestPost($url = '', $param = '') {

        if (empty($url) || empty($param)) {
            return false;
        }

        $postUrl = $url;
        $curlPost = $param;

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curlPost));
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }
}

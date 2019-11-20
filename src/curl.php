<?php


class curl
{

    /**
     * url地址
     *
     * @var string
     */
    private $_url = '';
    /**
     * @var string
     */
    private $_cookie = '';

    /**
     * @var string
     */
    private $_addUserAgent = '';

    /**
     * curl constructor.
     * @param string $url
     */
    function __construct($url = '')
    {
        if (!function_exists('curl_init'))
            die('请开启Curl模块');
        $this->_url = $url;
    }

    /**
     * 私有变量赋值
     *
     * @param string $value
     */
    public function setUrl($value)
    {
        $this->_url = $value;
    }

    /**
     * 设置cookie的值
     * 形如：hello1=111;hello2=222;hello3=333;hello4=444
     * @param array $value
     */
    public function setCookie($value)
    {
        $this->_cookie = $value;
    }

    /**
     * 获取私有变量的值
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * @param $agent
     */
    public function addUserAgent($agent)
    {
        $this->_addUserAgent .= $agent;
    }

    /**
     * 将data post到指定的url
     *
     * @param array|string $data //如name=aboc&num=bbb
     * @param $user_agent bool
     * @return bool|mixed
     */
    public function post($data, $user_agent = false)
    {
        if (is_array($data)) {
            $str = array();
            foreach ($data as $key => $value) {
                if (!is_array($value))
                    $str[] = $key . '=' . urlencode($value);
            }
            $str = join('&', $str);
        } else {
            $str = $data;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_url);
        if (is_array($data)) {
            curl_setopt($ch, CURLOPT_POST, count($data));
        }
//		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        if ($this->isHttps()) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
        if ($user_agent) {
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 ' . $this->_addUserAgent);
        }
        if (!empty($this->_cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->_cookie);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * @param bool $user_agent
     * @return mixed
     */
    public function get($user_agent = false)
    {
        $ch = curl_init($this->_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        if ($this->isHttps()) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        if ($user_agent) {
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 ' . $this->_addUserAgent);
        }
        if (!empty($this->_cookie))
            curl_setopt($ch, CURLOPT_COOKIE, $this->_cookie);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }


    /**
     * @param bool $user_agent
     * @return mixed
     */
    public function curlget($user_agent = false)
    {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $this->_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($user_agent) {
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 ' . $this->_addUserAgent);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        if ($this->isHttps()) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        $result = curl_exec($ch);
        //$httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $result;
    }

    public function download($savepath, $user_agent = false, $refferer = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_url);
        if($user_agent) {
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 ' . $this->_addUserAgent);
        }
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(!empty($refferer)) {
            curl_setopt($ch, CURLOPT_REFERER, $refferer);
        }
        //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        $content = curl_exec($ch);
        curl_close($ch);

        if(empty($savepath)) {
            return $content;
        }

        file_put_contents($savepath, $content);
        unset($content);
    }

    function isHttps()
    {
        $scheme = parse_url($this->_url, PHP_URL_SCHEME);
        return (strtolower($scheme) == 'https');
    }

    /**
     *
     */
    function __destruct()
    {

    }

}
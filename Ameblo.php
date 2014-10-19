<?php
class Ameblo
{
    protected $atomapi_url = "http://atomblog.ameba.jp/servlet/_atom/blog";
    protected $title = '';
    protected $body = '';
    protected $wsse = '';
    protected $create_date = '';

    public function __construct($userid,$password,$time = 0)
    {
        if (!$time) {
            $time = time();
        }

        /**
         * @see http://kanndume.blogspot.jp/2012/11/php.html
         */
        date_default_timezone_set('UTC');
        $date_time = new DateTime(date('Y-m-d H:i:s', $time), new DateTimeZone('UTC'));
        $this->create_date = $date_time->format('Y-m-d\TH:i:s\Z');

        $nonce = sha1(md5($time));
        $pass_digest = base64_encode(pack('H*', sha1($nonce.$this->create_date.strtolower(md5($password)))));
        $this->wsse =
            'UsernameToken Username="'.$userid.'", '.
            'PasswordDigest="'.$pass_digest.'", '.
            'Nonce="'.base64_encode($nonce).'", '.
            'Created="'.$this->create_date.'"';
    }

    /**
     * 投稿するためのURL取得
     */
    protected function getPostUrl()
    {
        $hr = new HTTP_Request($this->atomapi_url);
        $hr->addHeader('X-WSSE', $this->wsse);
        $hr->addHeader('Accept', 'application/x.atom+xml, application/xml, text/xml, */*');
        $hr->addHeader('Authorization', 'WSSE profile="UsernameToken"');
        $hr->addHeader('Content-Type', 'application/x.atom+xml');
        $hr->sendRequest();
        $res = $hr->getResponseBody();

        $xml = simplexml_load_string($res);

        return (string) $xml->link[0]->attributes()->href;
    }

    public function getTime()
    {
        if (!$this->time) {
            $this->time = time();
        }

        return $this->time;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * 投稿用データ取得
     */
    protected function getRawdata()
    {
        $rawdata = sprintf('<?xml version="1.0" encoding="utf-8"?>
          <entry xmlns="http://purl.org/atom/ns#"
          xmlns:app="http://www.w3.org/2007/app#"
          xmlns:mt="http://www.movabletype.org/atom/ns#">
          <title>%s</title>
          <content type="application/xhtml+xml">
          <![CDATA[%s]]>
          </content>
          <issued>%s</issued>
          </entry>', $this->title, $this->body, $this->create_date);

        return $rawdata;
    }

    /**
     * 投稿実行
     */
    public function post()
    {
        $hr = new HTTP_Request($this->getPostUrl());
        $hr->addHeader('X-WSSE', $this->wsse);
        $hr->addHeader('Accept', 'application/x.atom+xml, application/xml, text/xml, */*');
        $hr->addHeader('Authorization', 'WSSE profile="UsernameToken"');
        $hr->addHeader('Content-Type', 'application/x.atom+xml');
        $hr->addRawPostData($this->getRawdata());
        $hr->setMethod(HTTP_REQUEST_METHOD_POST);
        $hr->sendRequest();
        $hr->clearPostData();
    }
}

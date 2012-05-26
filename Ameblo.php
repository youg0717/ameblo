<?php
class Ameblo{
	protected $atomapi_url = "http://atomblog.ameba.jp/servlet/_atom/blog";
	protected $title = '';
	protected $body = '';
	protected $wsse = '';
	protected $create_date = '';
	
	public function __construct($userid,$password,$time = 0){
		if (!$time){
			$time = time();
		}
		$this->create_date = date('Y-m-d\TH:i:s\Z',$time);
		$nonce = sha1(md5(time()));
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
	protected function getPostUrl(){
		$headers = array("X-WSSE" => $this->wsse);
		$context = stream_context_create(
			array('http' => array('header' => "X-WSSE: $this->wsse"))
			);
		//投稿するURLを取得
		$xml = simplexml_load_string(file_get_contents($this->atomapi_url,0,$context));
		return (string) $xml->link[0]->attributes()->href;
	}
	
	public function getTime(){
		if (!$this->time){
			$this->time = time();
		}
		return $this->time;
	}
	
	public function setTitle($title){
		$this->title = $title;
	}
	
	public function setBody($body){
		$this->body = $body;
	}
	
	/**
	 * 投稿用データ取得
	 */
	protected function getRawdata(){
		$rawdata = sprintf('<?xml version="1.0" encoding="utf-8"?>
		<entry xmlns="http://purl.org/atom/ns#"
		xmlns:app="http://www.w3.org/2007/app#"
		xmlns:mt="http://www.movabletype.org/atom/ns#">
		<title>%s</title>
		<content type="application/xhtml+xml">
		<![CDATA[%s]]>
		</content>
		<issued>%s</issued>
		</entry>',$this->title,$this->body,$this->create_date);
		
		return $rawdata;
	}
	
	/**
	 * 投稿実行
	 */
	public function post(){
		$rawdata = $this->getRawdata();
		$header_list = array(
			'Content-Type: application/x.atom+xml',
			'X-WSSE: '.$this->wsse,
		);
		$context = stream_context_create(
			array('http' => array(
				'method' => 'POST',
				'header' => implode("\r\n",$header_list),
				'content' => $rawdata,
				))
			);
		echo file_get_contents($this->getPostUrl(),0,$context);
	}
}

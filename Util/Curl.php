<?php
/**
 * @link https://github.com/octans/WXDev
 * @author octansneu@gmail.com
 */

/**
 * 简易的网络访问类
 * 
 * 支持GET,POST
 * 
 */
class Curl
{
	/**
	 * The curl session handle
	 *
	 * @var resource|null
	 */
	protected $curl = null;
	
	public static function factory($proxy = '')
	{
		return new self($proxy);
	}
	
	public function __construct($proxy = '')
	{
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		if ($proxy) {
			$this->setHttpProxy($proxy);
		}
	}
	
	public function __destruct()
	{
		curl_close($this->curl);
	}
	
	public function write()
	{
		$res = curl_exec($this->curl);
		
		if(curl_errno($this->curl) != 0) {
			throw new CurlException("Request is failed: ".curl_error($this->curl));
		}
		
		return $res;
	}
	
	
	public function doPostRequest($url, $params = array())
	{
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
		
		return $this->write();
	}
	
	public function doGetRequest($url, $params = array())
	{
		if (count($params) > 0) {
			$url .= '?' . http_build_query($params);
		}
		curl_setopt($this->curl, CURLOPT_URL, $url);

		return $this->write();
	}
	
	public function setHttpProxy($proxy)
	{
		curl_setopt($this->curl, CURLOPT_PROXY, $proxy);
	}
}
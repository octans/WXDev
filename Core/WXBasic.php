<?php
/**
 * @link https://github.com/octans/WXDev
 * @author octansneu@gmail.com
 */

/**
 * 微信开发基础类
 */
class WXBasic
{
	const CACHE_TYPE = 'FILE';
	
	private $accessTokenCacheKey = null;
	
	protected  $curl = null;
	
	public static function factory()
	{
		return new self();
	}
	
	public function __construct()
	{
		$this->setAccessTokenCacheKey();
		$this->setCurl();
	}
	
	protected function setCurl()
	{
		$curl = new Curl();
		$this->curl = $curl;
		
		return $this;
	}
	
	protected function getCurl()
	{
		if (!$this->curl) {
			$this->setCurl();
		}
		
		return $this->curl;
	}
	
	protected function setAccessTokenCacheKey()
	{
		
		$this->accessTokenCacheKey =  __METHOD__;
	}
	
	/**
	 * 获取access_token缓存键名
	 * @return string
	 */
	protected function getAccessTokenCacheKey()
	{
		if ($this->accessTokenCacheKey) {
			return $this->accessTokenCacheKey;
		}
		
		$this->setAccessTokenCacheKey();
		
		return $this->accessTokenCacheKey;
	}
	
	/**
	 * 获取access_token
	 */
	public function getAccessToken()
	{
		// 读取缓存
		if (self::CACHE_TYPE == 'FILE') {
			$file = '/tmp/wxaccesstoken.txt';
			if (file_exists($file)) {
				$cacheData = json_decode(file_get_contents($file), true);
				if (isset($cacheData['created']) && isset($cacheData['access_token']) && isset($cacheData['expires_in']) && 
					(time() + 10 - $cacheData['created'] < $cacheData['expires_in'])) {
					$cachedAccessToken = $cacheData['access_token'];
				} else {
					$cachedAccessToken = '';
				}
				
			}
		}
		if (!empty($cachedAccessToken)) {
			return $cachedAccessToken;
		}
		
		// 请求微信
		$res = $this->requestAccessToken();
		$accessToken = $res['access_token'];
		$expiresIn = $res['expires_in'];
		
		// 写入缓存
		if (self::CACHE_TYPE == 'FILE') {
			$res = file_put_contents($file, json_encode(array_merge($res, array('created' => time()))));
			if (!$res) {
				throw new WXBasicException('error write to ' . $file . var_export($res), WXBasicException::ACCESS_TOKEN_API_RECEIVE_ERROR_CODE);
			}
		}
		
		return $accessToken;
	}
	
	/**
	 * 向微信请求access_token
	 * @throws WXBasicException
	 * @return array
	 */
	public function requestAccessToken()
	{
		$url = WXConfig::ACCESS_TOEKN_URL;
		$params = array(
			'grant_type' => 'client_credential',
			'appid' => WXConfig::APP_ID,
			'secret' => WXConfig::APP_SECRET,
		);
		$res = $this->sendGetRequest($url, $params);
		$res = json_decode($res, true);
		if (isset($res['errcode'])) {
			throw new WXBasicException(var_export($res), WXBasicException::ACCESS_TOKEN_API_RECEIVE_ERROR_CODE);
		}
		if (empty($res['access_token'])) {
			throw new WXBasicException(var_export($res), WXBasicException::ACCESS_TOKEN_API_RECEIVE_NO_ACCESS_TOKEN);
		}
		
		return $res;
	}
	
	/**
	 * 发送GET请求
	 */
	public function sendGetRequest($url, $params = array())
	{
		$res = $this->curl->doGetRequest($url, $params);
		
		return json_decode($res, true);
	}
	
	/**
	 * 发送POST请求
	 * @param string $url
	 * @param array $postData
	 * @return array
	 */
	public function sendPostRequest($url, $postData)
	{
		$res = $this->curl->doPostRequest($url, $postData);
		
		return json_decode($res, true);
	}
	
	/**
	 * 将xml字符串转成数组
	 * @param string $xml
	 * @return mixed
	 */
	public function xml2Array($xml)
	{
		$obj =  simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		
		return json_decode(json_encode($obj), true);
	}
	
}
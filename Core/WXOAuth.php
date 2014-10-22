<?php
/**
 * @link https://github.com/octans/WXDev
 * @author octansneu@gmail.com
 * 
 * 使用文档：https://github.com/octans/WXDev/blob/master/Doc/WXOAuth.md
 */

/**
 * 微信oauth接口类
 */
class WXOAuth
{
	/**
	 * 微信oauth的base scope
	 * 无需微信用户确认授权，第三方应用只能获取到用户的openid
	 *
	 * @const string
	 */
    const SCOPE_SNSAPI_BASE = 'snsapi_base';
    
    /**
     * 微信oauth的info scope
     * 需要微信用户确认授权，第三方应用能够获得用户的openid和用户的基本信息
     *
     * @const string
     */
    const SCOPE_SNSAPI_USERINFO = 'snsapi_userinfo';
    
    /**
     * oauth授权接口地址
     *
     * @const string
     */
    const AUTH_URL = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
    
    /**
     * 获取oauth access_token地址
     *
     * @const string
     */
    const ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/sns/oauth2/access_token?';
    
    /**
     * 获取oauth后的用户信息接口地址
     *
     * @const string
     */
    const USER_INFO_URL = 'https://api.weixin.qq.com/sns/userinfo?';
    
    /**
     * 第三方应用在微信授权后的二次跳转地址参数名称
     * 
     * @var string
     */
    const INTERNAL_REDIRECT_URL_PARAM_NAME = 'interr';

    /**
     * 传给微信平台的回跳地址
     */
    private $redirectUrl = '';
    
    /**
     * 授权结束后的应用内回跳地址
     */
    private $internalRedirectUrl = '';
    
    /**
     * 授权scope
     * 
     * @var string
     */
    private $scope = self::SCOPE_SNSAPI_BASE;
    
    /**
     * 授权state参数值
     * 
     * @var string
     */
    private $state = '';
    
    /**
     * #wechat_redirect
     * 必须项
     * 
     * @var string
     */
    private $wechatRedirect = true;
    
    /**
     * grant_type
     * 必须项，设置为authorization_code
     * @var string	
     */
    private $grantType = 'authorization_code';
    
    public static function factory()
    {
        return new self();
    }
    
    /**
     * 拼装oauth跳转地址
     * @return string
     */
    public function buildOAuthUrl()
    {
        $redirectUrl = $this->redirectUrl;
        if ($this->internalRedirectUrl) {
            $redirectUrl .= '?' . self::INTERNAL_REDIRECT_URL_PARAM_NAME . '=' . $this->internalRedirectUrl;
        }
        
        $params = array(
            'appid' => WXConfig::APP_ID,
            'redirect_uri' => $redirectUrl,
            'response_type' => 'code',
            'scope' => $this->scope,
            'state' => $this->state,
        );
        $wechat_redirect = $this->wechatRedirect ? '#wechat_redirect' : '';
        
        return self::AUTH_URL . http_build_query($params) . $wechat_redirect;
    }
    
    /**
     * 设置授权后的回跳地址
     * @param string $url
     */
    public function setRedirectUrl($url)
    {
        $this->redirectUrl = $url;
        
        return $this;
    }
    
    /**
     * 设置授权后应用内的回跳地址
     * @param string $internalRedirectUrl
     */
    public function setInternalRedirectUrl($internalRedirectUrl)
    {
        $this->internalRedirectUrl = $internalRedirectUrl;
        
        return $this;
    }
    
    /**
     * 设置微信授权方式
     * @param string $scope
     * @throws TMException
     */
    public function setScope($scope)
    {
    	if (!in_array($scope, array(self::SCOPE_SNSAPI_BASE, self::SCOPE_SNSAPI_USERINFO))) {
    		throw new WXOAuthException('unknown scope value');
    	}
    
    	$this->scope = $scope;
    }
    
    /**
     * 跳转到微信oauth地址
     * 
     * @var string $scope
     * @var string $internalRedirectUrl 微信授权后应用内的跳转地址
     * @var string $redirectUrl 微信授权后的回跳地址
     */
    public function goOAuth($scope, $internalRedirectUrl = '', $redirectUrl = '')
    {
        
        if ($internalRedirectUrl) {
            $this->setInternalRedirectUrl($internalRedirectUrl);
        }
        if ($redirectUrl) {
            $this->setRedirectUrl($redirectUrl);
        }
         
        $this->setScope($scope);
        
        $url = $this->buildOAuthUrl();
        
        header("Location: $url");
        
        return true;
    }
    
    /**
     * 接收微信对用户的回跳后，向微信服务器请求用户openid或者用户的个人信息
     * @throws TMException
     * @return string|unknown
     * authdeny --- 用户拒绝授权
     * authfailed --- 授权失败
     * array() --－用户信息
     */
    public function receAuth()
    {
    	$code = $_GET['code'];
    	if (!$code || $code == 'authdeny') {
    		return 'authdeny';
    	}
    	
        $accessInfo = $this->requestOpenidAndAccessToken($code);
    	if ($accessInfo['scope'] == self::SCOPE_SNSAPI_BASE) {
    		return $accessInfo;
    	} elseif ($accessInfo['scope'] == self::SCOPE_SNSAPI_USERINFO) {
    		$userInfo = $this->requestAuthUserInfo($accessInfo['access_token'], $accessInfo['openid']);
    		$userInfo['scope'] = self::SCOPE_SNSAPI_USERINFO;
    		return $userInfo;
    	} else {
    		return 'authfailed';
    	}
    }
    
    /**
     * 获取openid和access_token
     * @var string $code
     * @return array
     * 返回值：
     * {
   "access_token":"ACCESS_TOKEN",
   "expires_in":7200,
   "refresh_token":"REFRESH_TOKEN",
   "openid":"OPENID",
   "scope":"SCOPE"
}
     */
    public function requestOpenidAndAccessToken($code) {
    	$params = array(
    	    'appid' => WXConfig::APP_ID,
    		'secret' => WXConfig::APP_SECRET,
    		'code' => $code,
    		'grant_type' => $this->grantType,
    	);
    	$url = self::ACCESS_TOKEN_URL . http_build_query($params);
    	$resArray = $this->doGetRequest($url);
    	if (isset($resArray['errcode']) && $resArray['errcode'] != 0) {
    		throw new WXOAuthException('获取openid失败，请稍后再试');
    	}
    	
    	return $resArray;
    }
    
    /**
     * 获取微信用户详细信息
     * 
     * @var string $accessToken
     * @var string $openId
     * @return array $resArray
     * 
     * {
   "openid":" OPENID",
   " nickname": NICKNAME,
   "sex":"1",
   "province":"PROVINCE"
   "city":"CITY",
   "country":"COUNTRY",
    "headimgurl":    "http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46", 
	"privilege":[
	"PRIVILEGE1"
	"PRIVILEGE2"
    ]
}

     */
    public function requestAuthUserInfo($accessToken, $openId)
    {
    	$params = array(
    		'access_token' => $accessToken,
    		'openid' => $openId,
    		'lang' => 'zh_CN',
    	);
    	$url = self::USER_INFO_URL . http_build_query($params);
    	$resArray = $this->doGetRequest($url);
    	if (isset($resArray['errcode']) && $resArray['errcode'] != 0) {
    		throw new WXOAuthException('获取用户信息失败，请稍后再试');
    	}
    
    	return $resArray;
    }
    
    /**
     * 发送GET请求
     * 
     * @var $url string
     * @return array
     */
    private function doGetRequest($url)
    {
    	return WXBasic::factory()->sendGetRequest($url);
    }
}
?>
#微信OAuth类
##Part1 前提条件
微信oauth能够使得第三方网页应用在微信内直接获取到当前微信用户的身份标识信息，此过程不需要用户关注第三方应用的公众号。使用微信oauth需要公众号拥有高级接口权限。
####两种oauth方式(应用授权作用域)：
* snsapi_base
不弹出授权页面，直接跳转，只能获取用户openid
* snsapi_userinfo
弹出授权页面，可通过openid获取到用户的个人信息(昵称，头像等)

##Part2 接口封装
微信oauth接口文档：[网页授权获取用户基本信息](http://mp.weixin.qq.com/wiki/index.php?title=%E7%BD%91%E9%A1%B5%E6%8E%88%E6%9D%83%E8%8E%B7%E5%8F%96%E7%94%A8%E6%88%B7%E5%9F%BA%E6%9C%AC%E4%BF%A1%E6%81%AF)

下面介绍了5个主要方法，虽然对于本类的使用者，只需直接调用方法4和5就可以完成整个oauth，然而了解方法1,2和3后有助于理解4和5的工作原理
###1. 获取oauth跳转地址
网页应用需要将微信用户跳转到此微信oauth地址，
	
	$oauthUrl = WXOAuth::factory()->buildOAuthUrl();
###2. 获取openid和access_token
当微信将用户跳转回应用的回跳地址后，将code传递给应用，应用后台通过code去向微信请求用户的openid和access_token，

	$res = WXOAuth::factory()->requestOpenidAndAccessToken($code);

如果网页授权的作用域为snsapi_base，则本步骤中获取到网页授权access_token的同时，也获取到了openid，snsapi_base式的网页授权流程即到此为止	
###3. 获取微信用户详细信息
如果应用授权作用域是snsapi_userinfo，在获取到openid和access_token后，应用后台能够通过openid和access_token获取到用户的个人信息
	
	$res = WXOAuth::factory()->requestAuthUserInfo($accessToken, $openId)	
#####以上方法对应了微信oauth的三个基本步骤，以下方法是对1,2,3的组合，使用者可以直接调用:
###4. 微信oauth授权
在需要用户授权的地方直接调用，$scope是应用授权作用域，$internalRedirectUrl是应用内的回跳地址，$redirectUrl是应用向微信传递的回跳地址，
	
	WXOAuth::factory()->goOAuth($scope, $internalRedirectUrl = '', $redirectUrl = '');
$internalRedirectUrl和$redirectUrl的区别：
在应用内通常指定一个固定的地址来接收微信的回跳，这个对应的是$redirectUrl;
而当授权完成后，应用需要把用户在跳转到授权前用户所在的页面，这个页面地址对应$internalRedirectUrl。
###5. 处理微信的回跳
	WXOAuth::factory()->receAuth()
此方法将根据微信返回的网页授权的作用域的值调用方法2和3并返回用户的openid或者包含openid的个人信息

	
	



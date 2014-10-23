#微信支付类WXPay
##Part1 前提条件
使用微信支付必须拥有以下字段, 其中partnerId，partnerKey和paySignKey需要开通微信支付方可获得：

* appId - 公众号身份的唯一标识
* appSecret - 公众平台接口API密钥
* partnerId - 财付通商户身份的标识
* partnerKey - 财付通商户权限密钥 Key
* paySignKey - 公众号支付请求中用于加密的密钥Key,对应于支付场景中的appKey值

拿到以上字段后在WXConstant.php中进行配置。

##Part2 接口封装
微信支付接口文档：[使用公众号发起支付请求](https://mp.weixin.qq.com/paymch/readtemplate?t=mp/business/course2_tmpl&lang=zh_CN&token=1991587950#3)
###1. web页面调用微信支付
调用JS API支付接口getBrandWCPayRequest()能够调出微信支付的页面：

	// js 调用微信支付页面
    WeixinJSBridge.invoke('getBrandWCPayRequest', {
        appId: appId, // 公众号id
        timeStamp: timeStamp, // 时间戳
        nonceStr: nonceStr, // 随机字符串
        package: package, // 订单详情扩展字符串
        signType: signType, // 签名方式
        paySign: paySign // 签名
    }, function(res) {
        if (res.err_msg != 'get_brand_wcpay_request:ok') {
            alert('支付失败!');
        } else {
            alert('支付成功!');
        }
    });
以上代码中，涉及到appId，timeStamp，nonceStr，package，signType和paySign六个参数，
其中主要工作在于生成订单详情扩展字符串package和签名paySign：
####1.1 生成订单详情(package)扩展字符串
    $wxpay = WXPay::factory(); 
    $package = $wxpay->getPackage($orderData);
订单详情信息包含有发起支付请求的商户信息和交易信息，重点看其中的3个参数：

* out_trade_no - 商户订单号
此订单号由调用者即商户自己生成，在商户内全局唯一来标识该比交易, 微信后台通知商户支付成功时，会将此订单号发送给商户
* total_fee - 订单总金额 
此次交易的总金额
* notify_url - 通知 URL* 
此url由商户提供，在支付完成后微信将通过此url通知商户订单的支付* 

####1.2 生成支付签名
    $paySign = $wxpay->getPaySign();
###2. 接收支付结果通知
用户在成功完成支付后,微信后台通知( POST)商户服务器(notify_url)支付结果, 在微信发过来的数据中重点关注以下参数值：

* total_fee - 订单总金额 
* transaction_id - 订单号, 由微信方生成
* out_trade_no － 商户订单号，1.1中提到的商户订单号
* time_end － 支付完成时间
* sign － 签名

接收到微信通知后，在验证签名后，就可以根据out_trade_no对相应的订单进行状态更新了。

	WXPay::factory()->receivePayNotify();
	
###3. 发货通知
在收到支付通知后,商户要按时发货,并使用发货通知接口将相关信息同步到微信后台。若微信平台在规定时间内没有收到,将视作发货超时处理。
发货时间限制:虚拟、服务类 24 小时内,实物类 72小时内。

	WXPay::factory()->deliverNotify($postData)
###4. 查询订单
商户在预期时间内未收到支付结果通知，可以通过此接口查询订单的支付状态

	WXPay::factory()->orderQuery($postData)
###5. 接收告警通知
微信后台会向商户推送告警 通知,包括发货延迟 、调用失败、通知失败等情况

	WXPay::factory()->receiveAlert($postXML)
###6. 接收用户维权信息通知
接入微信支付的商户都必须接入用户维权系统，微信将用户投诉内容通过此api发送给商户

	WXPay::factory()->receivePayFeedback($postXML = '')

###7. 更新用户维权处理结果
商户可以通过此api调用，标记客户的投诉处理状态

	WXPay::factory()->updatePayFeedback(($openId, $feedbackId))
	



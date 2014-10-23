<?php
/**
 * @link https://github.com/octans/WXDev
 * @author octansneu@gmail.com
 */

/**
 * 微信支付类
 * 
 * 使用文档：https://github.com/octans/WXDev/blob/master/Doc/WXPay.md
 * 
 * 官方接口调试：http://mp.weixin.qq.com/debug/cgi-bin/readtmpl?t=pay/index
 * 
 */
class WXPay
{
	/**
	 * 支付回调地址
	 * 支付完成后,接收微信通知支付结果的URL
	 *
	 * @const string
	 */
	const PAY_NOTIFY_URL = '';
	
	/**
	 * 发货通知地址
	 * 第三方在收到最终支付通知之后,调用发货通知 API 告知微信后台该订单的发货状态
	 * 
	 * @const string
	 */
	const PAY_DELIVER_NOTIFY_URL = 'https://api.weixin.qq.com/pay/delivernotify';
	
	/**
	 * 订单查询地址
	 * 
	 * @const string
	 */
	const PAY_ORDER_QUERY_URL = 'https://api.weixin.qq.com/pay/orderquery';
	

	/**
	 * 投诉更新地址
	 * 标记客户的投诉处理状态URL
	 *
	 * @const string
	 */
	const PAY_FEEDBACK_UPDATE_URL = 'https://api.weixin.qq.com/payfeedback/update';
	
	/**
	 * 公众号身份的唯一标识
	 * 
	 * @var string 
	 */
	private $appId;
	
	/**
	 * 公众平台接口API密钥
	 * 
	 * @var string 
	 */
	private $appSecret;
	
	/**
	 * 财付通商户身份的标识
	 * 
	 * @var string
	 */
	private $partnerId;
	
	/**
	 * 财付通商户权限密钥Key
	 * 
	 * @var string
	 */
	private $partnerKey;
	
	/**
	 * 公众号支付请求中用于加密的密钥Key,对应于支付场景中的appKey值
	 * 
	 * @var string
	 */
	private $paySignKey;
	
	/**
	 * 时间戳,支付接口要求必传
	 * 
	 * @var int
	 */
	private $timeStamp = '';
	
	/**
	 * 随机字符串, 支付接口要求必传
	 * 
	 * @var string
	 */
	private $nonceStr = '';
	
	/**
	 * 订单详情扩展字符串
	 * 
	 * @var string
	 */
	private $package = '';
	
	/**
	 * 支付签名
	 * 
	 * @var string
	 */
	private $paySign = '';
	
	/**
	 * 签名方式
	 * 
	 * @const
	 */
	const SIGN_TYPE = 'SHA1';
	
	const INPUT_CHARSET = 'UTF-8';
	
	/**
	 * 处理支付回调通知的业务逻辑类名称
	 * 
	 * @var string
	 */
	protected $payNotifiedOrderHandleClass = 'Order';
	
	/**
	 * 处理支付回调通知的业务逻辑方法名称
	 * 
	 * @var string
	 */
	protected $payNotifiedOrderHandleMethod = 'handlePayNotifiedOrder';
	
	/**
	 * 处理用户投诉的业务逻辑类名称
	 * 
	 * @var string
	 */
	protected $payFeedbackHandleClass = 'WXPayFeedback';
	
	/**
	 * 处理用户投诉的业务逻辑方法名称
	 * 
	 * @var string
	 */
	protected $payFeedbackHandleMethod = 'handlePayFeedback';
	
	/**
	 * 处理微信告警的业务逻辑类名称
	 * 
	 * @var string
	 */
	protected $alertHandleClass = 'WXAlert';
	
	/**
	 * 处理微信告警的业务逻辑方法名称
	 * 
	 * @var string
	 */
	protected $alertHandleMethod = 'handleAlert';
	
	
	
	/**
	 * 发货通知接口参数
	 * 
	 * @var array
	 */
	protected $deliverNotifyParams = array(
			'appid' =>  array(
					'isRequired' => 1,
					'defaultValue' => WXConfig::APP_ID,
					'desc' => '公众平台账户的AppId'
			),
			'openid' =>  array(
					'isRequired' => 1,
					'desc' => '购买用户的OpenId'
			),
			'transid' =>  array(
					'isRequired' => 1,
					'desc' => '交易单号'
			),
			'out_trade_no' =>  array(
					'isRequired' => 1,
					'desc' => '第三方订单号'
			),
			'deliver_timestamp' =>  array(
					'isRequired' => 1,
					'desc' => '发货时间戳,这里指的是 Linux 时间戳'
			),
			'deliver_status' =>  array(
					'isRequired' => 1,
					'desc' => '发货状态,1 表明成功,0 表明失败,失败时需要在 deliver_msg 填上失败原因;'
			),
			'deliver_msg' =>  array(
					'isRequired' => 1,
					'desc' => '发货状态信息'
			),
			'sign_method' =>  array(
					'isRequired' => 1,
					'defaultValue' => 'sha1',
					'desc' => '发货状态信息'
			),
	);
	
	/**
	 * 支付回调通知接口参数
	 * 
	 * @var array
	 */
	protected $notifyParams = array(
			'sign_type' => array(
					'isRequired' => 0,
					'name' => '签名方式',
					'desc' => '签名类型,取值:MD5、RSA,默 认:MD5; String(8)'
			),
			'input_charset' => array(
					'isRequired' => 0,
					'name' => '签名方式',
					'desc' => '字符编码,取值:GBK、UTF-8,默 认:GBK。; String(8)'
			),
			'sign' => array(
					'isRequired' => 1,
					'name' => '签名',
					'desc' => '签名; String(32)'
			),
			'trade_mode' => array(
					'isRequired' => 1,
					'name' => '交易模式',
					'desc' => '1-即时到账 其他保留; Int'
			),
			'trade_state' => array(
					'isRequired' => 1,
					'name' => '交易状态',
					'desc' => '支付结果: 0—成功 其他保留;Int'
			),
			'partner' => array(
					'isRequired' => 1,
					'name' => '商户号',
					'desc' => '商户号,也即之前步骤的 partnerid, 由微信统一分配的 10 位正整数 (120XXXXXXX)号; String(10)'
			),
			'bank_type' => array(
					'isRequired' => 1,
					'name' => '付款银行',
					'desc' => '银行类型,在微信中使用 WX	; String(16)'
			),
			'bank_billno' => array(
					'isRequired' => 0,
					'name' => '银行订单号',
					'desc' => '银行订单号; String(32)'
			),
			'total_fee' => array(
					'isRequired' => 1,
					'name' => '总金额',
					'desc' => '支付金额,单位为分,如果 discount 有值,通知的 total_fee + discount = 请求的 total_fee; Int'
			),
			'fee_type' => array(
					'isRequired' => 1,
					'name' => '币种',
					'desc' => '现金支付币种 ,目前只支持人民币 , 默认值是 1-人民币; Int'
			),
			'notify_id' => array(
					'isRequired' => 1,
					'name' => '通知ID',
					'desc' => '支付结果通知 id,对于某些特定商 户,只返回通知 id,要求商户据此 查询交易结果; String(128)'
			),
			'transaction_id' => array(
					'isRequired' => 1,
					'name' => '订单号',
					'desc' => '交易号,28 位长的数值,其中前 10 位为商户号,之后 8 位为订单产生 的日期,如 20090415,最后 10 位 是流水号。; String(28)'
			),
			'out_trade_no' => array(
					'isRequired' => 1,
					'name' => '商户订单号',
					'desc' => '商户系统的订单号,与请求一致; String(32)'
			),
			'attach' => array(
					'isRequired' => 0,
					'name' => '商户数据包',
					'desc' => '商户数据包,原样返回,空参数不传递; String(127)'
			),
			'time_end' => array(
					'isRequired' => 1,
					'name' => '支付完成时间',
					'desc' => '支付完成时间,格式 为 yyyyMMddhhmmss,如 2009 年 12 月27日9点10分10秒表示 为 20091227091010 。时区为 GMT+8 beijing。; String(14)'
			),
			'transport_fee' => array(
					'isRequired' => 0,
					'name' => '物流费用',
					'desc' => '物流费用,单位分,默认 0。如果 有值,必须保证 transport_fee + product_fee = total_fee; Int'
			),
			'product_fee' => array(
					'isRequired' => 0,
					'name' => '物品费用',
					'desc' => '物品费用,单位分。如果有值,必 证保须 transport_fee +product_fee=total_fee; Int'
			),
			'discount' => array(
					'isRequired' => 0,
					'name' => '物品费用',
					'desc' => '折扣价格,单位分,如果有值,通 知的 total_fee + discount = 请求 的 total_fee; Int'
			),
	);
	
	/**
	 * 订单详情package参数
	 * 
	 * @var array
	 */
	protected $packageParams = array(
				'bank_type' => array(
						'isRequired' => 1,
						'defaultValue' => 'WX',
						'name' => '银行通道类型',
						'desc' => '字符串类型, 为定固"WX" ,注意 大写'
				),
				'body' => array(
						'isRequired' => 1,
						'name' => '商品描述',
						'desc' => '字符串类型, 128 字节以下'
				),
				'attach' => array(
						'isRequired' => 0,
						'name' => '附加数据',
						'desc' => '附加数据,原样返回;字符串类型, 128 字节以下'
				),
				'partner' => array(
						'isRequired' => 1,
						'defaultValue' => WXConfig::PARTNER_ID,
						'name' => '商户号',
						'desc' => '注册时分配的财付通商户号 partnerId;字符串类型'
				),
				'out_trade_no' => array(
						'isRequired' => 1,
						'name' => '商户订单号',
						'desc' => '商户系统内部的订单号,32 个字符内、可包含字 母;确保在商户系统唯一;字符串类型, 32字节以下'
				),
				'total_fee' => array(
						'isRequired' => 1,
						'name' => '订单总金额',
						'desc' => '字符串类型'
				),
				'fee_type' => array(
						'isRequired' => 1,
						'defaultValue' => 1,
						'name' => '支付币种',
						'desc' => '字符串类型,默认值是1;暂只支持1'
				),
				'notify_url' => array(
						'isRequired' => 1,
						'defaultValue' => self::PAY_NOTIFY_URL,
						'name' => '通知URL',
						'desc' => '字符串类型,在支付完成后,接收微信通知支付结果的 URL, 需给绝对路径, 255字符内'
				),
				'spbill_create_ip' => array(
						'isRequired' => 1,
						'name' => '订单生成的机器IP',
						'desc' => '字符串类型,指用户浏览器端 IP,不是商户服务器 IP,格式为 IPV4; 15字符内'
				),
				'time_start' => array(
						'isRequired' => 0,
						'name' => '交易起始时间',
						'desc' => '字符串类型,订单生成时间,格式为 yyyyMMddHHmmss,如 2009年12月25日9点10分10秒表示 为 20091225091010,时区为 GMT+8 beijing;该时间取自商户服务器; 14字符内'
				),
				'time_expire' => array(
						'isRequired' => 0,
						'name' => '交易结束时间',
						'desc' => '字符串类型,订单生成时间,格式为 yyyyMMddHHmmss,如 2009年12月25日9点10分10秒表示 为 20091225091010,时区为 GMT+8 beijing;该时间取自商户服务器; 14字符内'
				),
				'transport_fee' => array(
						'isRequired' => 0,
						'name' => '物流费用',
						'desc' => '字符串类型,物流费用,单位为分。如果有值,必须保 证 transport_fee + product_fee=total_fee;'
				),
				'product_fee' => array(
						'isRequired' => 0,
						'name' => '商品费用',
						'desc' => '字符串类型,物流费用,单位为分。如果有值,必须保 证 transport_fee + product_fee=total_fee;'
				),
				'goods_tag' => array(
						'isRequired' => 0,
						'name' => '商品标记',
						'desc' => '字符串类型,商品标记,优惠券时可能用到'
				),
				'input_charset' => array(
						'isRequired' => 1,
						'defaultValue' => self::INPUT_CHARSET,
						'name' => '传入参数字符 编码',
						'desc' => '字符串类型,取值范围:GBK、UTF-8,默认:GBK'
				),
		);
	
	public static function factory()
	{
		return new self();
	}
	
	/**
	 * Constructor
	 * 
	 * 初始化微信支付配置参数
	 * 
	 * @param array $configArray
	 */
	public function __construct($configArray = array())
	{
		if (isset($configArray) && is_array($configArray)) {
			$this->appId = isset($configArray['appId']) ? $configArray['appId'] : '';
			$this->appSecret = isset($configArray['appSecret']) ? $configArray['appSecret'] : '';
			$this->partnerId = isset($configArray['partnerId']) ? $configArray['partnerId'] : '';
			$this->partnerKey = isset($configArray['partnerKey']) ? $configArray['partnerKey'] : '';
			$this->paySignKey = isset($configArray['paySignKey']) ? $configArray['paySignKey'] : '';
		} else {
			$this->appId = WXConfig::APP_ID;
			$this->appSecret = WXConfig::APP_SECRET;
			$this->partnerId = WXConfig::PARTNER_ID;
			$this->partnerKey = WXConfig::PARTNER_KEY;
			$this->paySignKey = WXConfig::PAY_SIGN_KEY;
		}
		
		foreach (array(
				'appId' => $this->appId, 
				'appSecret' => $this->appSecret, 
				'partnerId' => $this->partnerId, 
				'partnerKey' => $this->partnerKey, 
				'paySignKey' => $this->paySignKey) as $key => $val) {
			if (!$val) {
				throw new WXPayException("$key can not be empty!");
			}
		}
		
	}
	
	/**
	 * 获取jsapi支付请求json
	 * 
	 * @param array $orderData
	 * @return array $params
	 */
	public function getBrandWCPayRequestParam($orderData)
	{
		$this->timeStamp = $this->getTimeStamp();
		$this->nonceStr = $this->getNoncestr();
		$this->package = $this->getPackage($orderData);
		$this->paySign = $this->getPaySign();
		$params = array(
				'appId' => $this->appId,
				'timeStamp' => (string)$this->timeStamp,
				'nonceStr' => $this->nonceStr,
				'package' => $this->package,
				'signType' => self::SIGN_TYPE,
				'paySign' => $this->paySign,
				'outTradeNo' => $orderData['out_trade_no'],
		);
		
		return $params;
	}
	
	/**
	 * 获取支付签名
	 * 
	 * @param array $data
	 * @return string
	 */
	public function getPaySign($data = array())
	{
		$params = array(
				'appid' => $this->appId,
				'appkey' => $this->paySignKey,
				'noncestr' => isset($data['noncestr']) ? $data['noncestr'] : $this->nonceStr,
				'package' => isset($data['package']) ? $data['package'] : $this->package,
				'timestamp' => isset($data['timestamp']) ? $data['timestamp'] : $this->timeStamp,
		);
		
		foreach ($params as $key => $val) {
			if (!$val) {
				throw new WXPayException("$key can not be empty!");
			}
		}
		
		return $this->createPaySign($params);
	}
	
	/**
	 * 支付签名算法
	 * 
	 * @param array $params
	 * @return string
	 */
	public function createPaySign($params)
	{
		ksort($params);
		$string1 = $this->httpBuildStr($params);
		$paySign = sha1($string1);
		
		return $paySign;
	}
	
	/**
	 * 获取订单详情(package)扩展字符串
	 * 
	 * @param array $orderData
	 * @return string
	 */
	public function getPackage($orderData)
	{
		// 构造参数数组
		$myPackageParams = array();
		foreach ($this->packageParams as $key => $val) {
			if ($val['isRequired']) {
				$myPackageParams[$key] = isset($orderData[$key]) ? $orderData[$key] : (isset($val['defaultValue']) ? $val['defaultValue'] : '');
				if (!$myPackageParams[$key]) {
					throw new WXPayException("Please specify value for $key", -5000);
				}
			} else {
				if (isset($orderData[$key]) && $orderData[$key]) {
					$myPackageParams[$key] = $orderData[$key];
				}
			}
		}
		
		// 构造sign
		$sign = $this->createPackageSign($myPackageParams);
		
		// 构造urlencoded params string
		ksort($myPackageParams);
		$paramsStringEncoded = http_build_query($myPackageParams);
		
		// 拼接
		$return = $paramsStringEncoded . '&sign=' . $sign;
		
		return $return;
	}
	
	/**
	 * 创建订单详情package签名
	 * 
	 * @param array $packageParams
	 * @return string
	 */
	public function createPackageSign($packageParams)
	{
		ksort($packageParams);
		$paramsString = $this->httpBuildStr($packageParams);
		$sign = strtoupper(md5($paramsString . '&key=' . $this->partnerKey));
		
		return $sign;
	}
	
	/**
	 * 拼接query字符串
	 * 相当于没有urlencode功能的http_build_query()
	 * 
	 * @param array $params
	 * @return string
	 */
	private function httpBuildStr($params)
	{
		$return = '';
		foreach ($params as $key => $val) {
			$return[] = implode('=', array($key, $val));
		}
		$return = implode('&', $return);
		
		return $return;
	}
	
	private function getTimeStamp()
	{
		return time();
	}
	
	/**
	 * 获取随机字符串
	 * 商户生成的随机字符串;取值范 围:长度为 32 个字符以下。由商户生成后传入。取值范围:32 字符以下
	 * 
	 * @param int $length
	 * @return string
	 */
	private function getNoncestr($length = 16)
	{
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
		}
		
		return $str;
	}
	
	/**
	 * 接收微信的支付回调通知
	 * 
data from wx notify:
array ( 
	'$_GET' => array ( 
		'bank_billno' => '201409028971856', 
		'bank_type' => '3006', 
		'discount' => '0', 
		'fee_type' => '1', 
		'input_charset' => 'UTF-8', 
		'notify_id' => 'qZ6Aae9b0UKIxhbQrM8tRZ1EVUJzAu14yvcTu_URSbzAnBkJD-nu218tG8sISJCSccacsSPOey1NQhopDUdzhNqb-s0Zpe2i', 
		'out_trade_no' => 'aaaaaaaaaaa', 
		'partner' => 'aaaaaa', 
		'product_fee' => '1', 
		'sign' => 'AD7342AEB7B043E7E3DEF2BF3CCFF7CD', 
		'sign_type' => 'MD5', 
		'time_end' => '20140902165147', 
		'total_fee' => '1', 
		'trade_mode' => '1', 
		'trade_state' => '0', 
		'transaction_id' => '1220730901201409023179351860', 
		'transport_fee' => '0', 
	), 
	'postData' => ' 1 1409647907 ', 
)
	 */
	public function receivePayNotify($notifyData = '')
	{
		// 读取数据
		if (!$notifyData) {
			$postData = file_get_contents('php://input');
			$notifyData = array(
					'$_GET' => $_GET,
					'postData' => $postData,
			);
		}
		
		// 校验签名
		$notifySign = $notifyData['$_GET']['sign'];
		unset($notifyData['$_GET']['sign']);
		$mySign = $this->createPackageSign($notifyData['$_GET']);
		if ($notifySign != $mySign) {
			throw new WXPayException('notify sign validate failed:' . '$notifySign=' . $notifySign . ';$mySign='. $mySign, WXPayException::NOTIFY_SIGN_VALIDATE_FAILED);
		}
		
		// 处理回调通知的订单
		$res = $this->handlePayNotifiedOrder($notifyData);
		
		
		// 应答
		if ($res) {
			return 'success';
		}
		
	}
	
	/**
	 * 处理支付回调后的业务逻辑
	 * 
	 * @param array $notifyData
	 * @return boolean
	 */
	public function handlePayNotifiedOrder($notifyData)
	{
		if (!class_exists($this->payNotifiedOrderHandleClass)) {
			return true;
		}
		$notifyHandler = new $this->payNotifiedOrderHandleClass;
		if (!method_exists($notifyHandler, $this->notifiedPayOrderHandleMethod)) {
			return true;
		}
		
		$res = call_user_method($this->payNotifiedOrderHandleMethod, $notifyHandler, $notifyData);
		
		return $res;
	}
	
	/**
	 * 发货通知
	 * 
	 * 通知微信已发货
	 * 
	 * 第三方在收到最终支付通知之后,调用发货通知 API 告知微信后台该订单的发货状态。
	 * 发货时间限制:虚拟、服务类 24 小时内,实物类 72 小时内
	 * 若微信平台在规定时间内没有收到,将视作发货超时处理。
	 * 
	 * @param array $notifyData
	 * @return array $res
	 * 
	 * 微信返回数据：
	 * {"errcode":0,"errmsg":"ok"}
	 * {"errcode":49004,"errmsg":"not match signature"}
	 * {"errcode":49001,"errmsg":"not same appid with appid of access_token"}
	 */
	public function deliverNotify(array $postData)
	{
		// 检查参数
		$myPostData = array();
		foreach ($this->deliverNotifyParams as $key => $val) {
			if ($val['isRequired']) {
				$myPostData[$key] = isset($postData[$key]) ? $postData[$key] : (isset($val['defaultValue']) ? $val['defaultValue'] : '');
				if (!$myPostData[$key]) {
					throw new WXPayException("Please specify value for $key", WXPayException::API_PARAM_ERROR);
				}
			} else {
				if (isset($postData[$key]) && $postData[$key]) {
					$myPostData[$key] = $postData[$key];
				}
			}
		}

		// 构造url
		$accessToken = WXBasic::factory()->getAccessToken();
		$url = self::PAY_DELIVER_NOTIFY_URL . "?access_token=$accessToken";
		
		// 根据支付签名( paySign)生成方法中所讲的签名方式生成,
		$signMethod = $myPostData['sign_method'];
		unset($myPostData['sign_method']); // sign_method 是签名方法(不计入签名生成)
		$myPostData['appkey'] = isset($myPostData['appkey']) ? $myPostData['appkey'] : $this->paySignKey;
		$myPostData['app_signature'] = $this->createPaySign($myPostData); 
		$myPostData['sign_method'] = $signMethod;
		unset($myPostData['appkey']); // appkey参加签名字段，但不需要传递
		$myPostData = json_encode($myPostData);
		
		// 发送请求
		$res = WXBasic::factory()->sendPostRequest($url, $myPostData);
		if ($res['errcode'] != 0) {
			throw new WXPayException($res['errmsg'], WXPayException::DELIVER_NOTIFY_ERROR);
		}
		
	    return $res;
	}
	
	/**
	 * 查询订单orderquery
	 * 
	 * 向微信查询订单信息
	 * 
	 * @param array $postData
	 * @return array $res
	 * 
	 * 微信返回数据：
{"errcode":49001,"errmsg":"not same appid with appid of access_token"}

{"errcode":49004,"errmsg":"not match signature"}

{"errcode":0,"errmsg":"ok","order_info":{
	"ret_code":0,
	"ret_msg":"",
	"input_charset":"GBK",
	"trade_state":"0",
	"trade_mode":"1",
	"partner":"1220730aaa",
	"bank_type":"CMB_FP",
	"bank_billno":"2014090281856",
	"total_fee":"1",
	"fee_type":"1",
	"transaction_id":"12207309012409023179351860",
	"out_trade_no":"354058517aaab1",
	"is_split":"false",
	"is_refund":"false",
	"attach":"",
	"time_end":"20140902165147",
	"transport_fee":"0",
	"product_fee":"1",
	"discount":"0",
	"rmb_total_fee":""}
}
	 */
	public function orderQuery(array $postData)
	{
		// 构造参数
		$myPostData['appid'] = isset($postData['appid']) ? $postData['appid'] : $this->appId;
		$myPostData['package'] = isset($postData['$package']) ? $postData['$package'] : '';
		$myPostData['timestamp'] = isset($postData['timestamp']) ? $postData['timestamp'] : time();
		$myPostData['app_signature'] = isset($postData['app_signature']) ? $postData['app_signature'] : '';
		$myPostData['sign_method'] = isset($postData['sign_method']) ? $postData['sign_method'] : 'sha1';
		if (!$myPostData['package']) {
			$outTradeNo = isset($postData['out_trade_no']) ? $postData['out_trade_no'] : '';
			$partner = isset($postData['partner']) ? $postData['partner'] : $this->partnerId;
			$sign = isset($postData['sign']) ? $postData['sign'] : '';
			if (!$outTradeNo) {
				throw new WXPayException('please specify out_trade_no', WXPayException::API_PARAM_ERROR);
			}
			// 生成签名
			if (!$sign) {
				$partnerKey = $this->partnerKey;
				$sign = strtoupper(md5("out_trade_no=$outTradeNo&partner=$partner&key=$partnerKey"));
			}
			$myPostData['package'] = "out_trade_no=$outTradeNo&partner=$partner&sign=$sign";
		}
		// 生成签名
		if (!$myPostData['app_signature']) {
			$myPostData['app_signature'] = $this->createPaySign(array(
					'appid' => $myPostData['appid'],
					'appkey' => $this->paySignKey,
					'package' => $myPostData['package'],
					'timestamp' => $myPostData['timestamp'],
			));
		}
		
		// 发送请求
		$url = self::PAY_ORDER_QUERY_URL . "?access_token=" . WXBasic::factory()->getAccessToken();
		$res = WXBasic::factory()->sendPostRequest($url, json_encode($myPostData));
		if ($res['errcode'] != 0) {
			throw new WXPayException($res['errmsg'], WXPayException::ORDER_QUERY_ERROR);
		}
		
		return $res;
	}
	
	/**
	 * 接收来自微信的用户维权信息通知
	 * @param string $postXML
	 * @return string ok
<xml>
<OpenId><![CDATA[oDF3iY9P32sK_5GgYiRkjsCo45bk]]></OpenId>
<AppId><![CDATA[wxf8b4f8594e77]]></AppId>
<TimeStamp>1393400471</TimeStamp>
<MsgType><![CDATA[request]]></MsgType>
<FeedBackId>7197417460812502768</FeedBackId>
<TransId><![CDATA[1900000109201402143240185685]]></TransId>
<Reason><![CDATA[质量问题]]></Reason>
<Solution><![CDATA[换货]]></Solution>
<ExtInfo><![CDATA[备注12435321321]]></ExtInfo>
<AppSignature><![CDATA[d60293982cc97a5a9d3383af761db763c07c86]]></AppSignature>
<SignMethod><![CDATA[sha1]]></SignMethod>
<PicInfo>
<item><PicUrl><![CDATA[http://mmbiz.qpic.cn/mmbiz/49ogibiahRNtOk37iaztwmdgFbyFS9FUrqfodiaUAmxr4hOP34C6R4nGgebMalKuY3H35riaZ5vtzJh25tp7vBUwWxw/0]]></PicUrl></item>
<item><PicUrl><![CDATA[http://mmbiz.qpic.cn/mmbiz/49ogibiahRNtOk37iaztwmdgFbyFS9FUrqfn3y72eHKRSAwVz1PyIcUSjBrDzXAibTiaAdrTGb4eBFbib9ibFaSeic3OIg/0]]></PicUrl></item>
<item><PicUrl><![CDATA[]]></PicUrl></item>
<item><PicUrl><![CDATA[]]></PicUrl></item>
<item><PicUrl><![CDATA[]]></PicUrl></item>
</PicInfo>
</xml>

或者

<xml>
<OpenId><![CDATA[111222]]></OpenId>
<AppId><![CDATA[wwwwb4f85f3a797777]]></AppId>
<TimeStamp>1369743511</TimeStamp>
<MsgType><![CDATA[confirm/reject]]></MsgType>
<FeedBackId><![CDATA[5883726847655944563]]></FeedBackId>
<Reason><![CDATA[商品质量有问题]]></Reason>
<AppSignature><![CDATA[bafe07f060f22dcda0bfdb4b5ff756f973aecffa]]></AppSignature>
<SignMethod><![CDATA[sha1]]></SignMethod>
</xml>
	 * 
	 * @throws WXPayException
	 */
	public function receivePayFeedback($postXML = '')
	{
		if (!$postXML) {
			$postXML = file_get_contents('php://input');
		}
		
		$postData = WXBasic::factory()->xml2Array($postXML);
		
		// 校验AppSignature
		$myAppSignature = $this->createPaySign(array(
				'appid' => $postData['appid'],
				'appkey' => $this->paySignKey,
				'timestamp' => $postData['timestamp'],
				'openid' => $postData['openid'],
		));
		if ($myAppSignature != $postData['AppSignature']) {
			throw new WXPayException('pay feedback sign validate failed:' . 'AppSignature=' . $postData['AppSignature'] . ';$myAppSignature='. $myAppSignature, WXPayException::PAY_FEEDBACK_SIGN_VALIDATE_FAILED);
		}
		
		if ($this->handlePayFeedback($postData)) {
			return 'ok';
		}
	}
	
	/**
	 * @param array $feedback
	 * @return boolean
	 */
	public function handlePayFeedback($feedback)
	{
		if (!class_exists($this->payFeedbackHandleClass)) {
			return true;
		}
		$handler = new $this->payFeedbackHandleClass;
		if (!method_exists($handler, $this->payFeedbackHandleMethod)) {
			return true;
		}
	
		return call_user_method($this->payFeedbackHandleMethod, $handler, $feedback);
	}
	
	/**
	 * 标记客户的投诉处理状态
	 * 
	 * 微信返回数据：
	 * {"errcode":0,"errmsg":"ok"}
	 * @param unknown $openId
	 * @param unknown $feedbackId
	 * @return array $res
	 * @throws WXPayException
	 */
	public function updatePayFeedback($openId, $feedbackId)
	{
		$url = self::PAY_FEEDBACK_UPDATE_URL . "?access_token=" . WXBasic::factory()->getAccessToken()
				. "&openid=$openId"
				. "&feedbackid=$feedbackId";
		$res = WXBasic::factory()->sendGeTRequest($url);
		if ($res['errcode'] != 0) {
			throw new WXPayException($res['errmsg'], WXPayException::PAY_FEEDBACK_UPDATE_ERROR);
		}
		
		return $res;
	}
	
	/**
	 * 接收告警信息
	 * @throws WXPayException
	 * @return string
	 */
	public function receiveAlert($postXML = '')
	{
		if (!$postXML) {
			$postXML = file_get_contents('php://input');
		}
		
		$postData = WXBasic::factory()->xml2Array($postXML);
		
		// 校验AppSignature
		$myAppSignature = $this->createPaySign(array(
				'alarmcontent' => $postData['alarmcontent'],
				'appid' => $postData['appid'],
				'appkey' => $this->paySignKey,
				'description' => $postData['description'],
				'errortype' => $postData['errortype'],
				'timestamp' => $postData['timestamp'],
		));
		if ($myAppSignature != $postData['AppSignature']) {
			throw new WXPayException('pay feedback sign validate failed:' . 'AppSignature=' . $postData['AppSignature'] . ';$myAppSignature='. $myAppSignature, WXPayException::PAY_ALERT_SIGN_VALIDATE_FAILED);
		}
		
		if ($this->handleAlert($postData)) {
			return 'success';
		}
	}
	
	/**
	 * @param array $feedback
	 * @return boolean
	 */
	public function handleAlert($alert)
	{
		if (!class_exists($this->alertHandleClass)) {
			return true;
		}
		$handler = new $this->alertHandleMethod;
		if (!method_exists($handler, $this->alertHandleMethod)) {
			return true;
		}
	
		return call_user_method($this->alertHandleMethod, $handler, $alert);
	}
	
	/**
	 * 接收前端js的支付回调结果
	 * 注意这里没有签名机制，非100%可信
	 */
	public function recieveJsCallback($openId, $outTradeNo)
	{
		return Order::factory()->update(array('FTimeJSCallback' => date('Y-m-d H:i:s')), array('FOutTradeNo' => $outTradeNo, 'FOpenId' => $openId));
	}
	
}
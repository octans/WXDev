<?php
/**
 * @link https://github.com/octans/WXDev
 * @author octansneu@gmail.com
 */

/**
 * 微信配置类
 */
class WXConfig
{
	/**
	 * 公众号身份的唯一标识
	 *
	 * @const string
	 */
	const APP_ID = '';
	
	/**
	 * 公众帐号密码
	 * 
	 * @const string
	 */
	const APP_SECRET = '';
	
	/**
	 * 接口token
	 * 
	 * Token可由开发者任意填写，用作生成签名（该Token会和接口URL中包含的Token进行比对，从而验证安全性)
	 * http://mp.weixin.qq.com/wiki/index.php?title=%E6%8E%A5%E5%85%A5%E6%8C%87%E5%8D%97
	 * 
	 * @const string
	 */
	const CONFIG_TOKEN = '';
	
	/**
	 * 获取access_token地址
	 * 
	 * @const string
	 */
	const ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token';
}
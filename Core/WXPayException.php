<?php
/**
 * @link https://github.com/octans/WXDev
 * @author octansneu@gmail.com
 */

/**
 * WXPayException
 * 
 */
class WXPayException extends Exception
{
	/**
	 * @const int API参赛错误
	 */
	const API_PARAM_ERROR = -50000;
	
	/**
	 * @const int  支付后台通知签名校验失败
	 */
	const NOTIFY_SIGN_VALIDATE_FAILED = -50001;
	
	/**
	 *  @const int 支付后台通知订单号不存在
	 */
	const NOTIFY_OUT_TRADE_NO_NOT_EXIST = -50002;
	
	/**
	 *  @const int 支付后台通知总金额不正确
	 */
	const NOTIFY_TOTAL_FEE_INCORRECT = -50003;
	
	/**
	 *  @const int 支付后台通知交易状态非0
	 */
	const NOTIFY_TRADE_STATE_NOT_EQUAL_ZERO = -50003;
	
	/**
	 * @const int 发货通知 delivernotify收到非0的errcode
	 */
	const DELIVER_NOTIFY_ERROR = -50004;
	
	/**
	 * @const int 查询订单orderquery收到非0的errcode
	 */
	const ORDER_QUERY_ERROR = -50005;
	
	/**
	 * @const int 接收来自微信的用户维权信息通知数据里的签名错误
	 */
	const PAY_FEEDBACK_SIGN_VALIDATE_FAILED = -50006;
	
	/**
	 * @const int 标记客户的投诉处理状态收到非0的errcode
	 */
	const PAY_FEEDBACK_UPDATE_ERROR = -50007;
	
	/**
	 * @const int 接收告警信息通知数据里的签名错误
	 */
	const PAY_ALERT_SIGN_VALIDATE_FAILED = -50008;
}
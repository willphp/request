<?php
if (!function_exists('input')) {
	/**
	 * 快速获取请求
	 * @param string $name 请求名称,如:get.id
	 * @param mixed	 $value 默认值
	 * @param string|array $fn 处理函数,如:intval,md5
	 * @return mixed
	 */
	function input($name, $default = null, $fn = '') {
		return \willphp\request\Request::getRequest($name, $default, $fn);
	}
}
if (!function_exists('get_header')) {
	/**
	 * 获取http请求头
	 * @param string $name 请求头名称，如:host
	 * @param mixed	 $value 默认值
	 * @return string
	 */
	function get_header($name = '', $default = '') {
		return \willphp\request\Request::getHeader($name, $default);
	}
}
if (!function_exists('get_ip')) {
	/**
	 * 获取客户端IP地址
	 * @param int $type 类型0.string;1.int
	 * @return string|int
	 */
	function get_ip($type = 0) {
		return \willphp\request\Request::ip($type);
	}
}
if (!function_exists('web_url')) {
	/**
	 * 当前url
	 * @param bool $domain 是否带域名
	 * @return string
	 */
	function web_url($domain = false) {
		if ($domain) {
			return \willphp\request\Request::url();
		}		
		return \willphp\request\Request::web();
	}
}
if (!function_exists('root_url')) {
	/**
	 * 网站请求地址
	 * @return string
	 */
	function root_url() {
		return \willphp\request\Request::domain();
	}
}
if (!function_exists('history_url')) {
	/**
	 * 来源链接地址
	 * @return string
	 */
	function history_url() {
		return \willphp\request\Request::history();
	}
}

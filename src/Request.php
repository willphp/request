<?php
/*--------------------------------------------------------------------------
 | Software: [WillPHP framework]
 | Site: www.113344.com
 |--------------------------------------------------------------------------
 | Author: 无念 <24203741@qq.com>
 | WeChat: www113344
 | Copyright (c) 2020-2022, www.113344.com. All Rights Reserved.
 |-------------------------------------------------------------------------*/
namespace willphp\request;
class Request {
	protected static $link;
	public static function single()	{
		if (!self::$link) {
			self::$link = new RequestBuilder();
		}
		return self::$link;
	}
	public function __call($method, $params) {
		return call_user_func_array([self::single(), $method], $params);
	}
	public static function __callStatic($name, $arguments) {
		return call_user_func_array([self::single(), $name], $arguments);
	}
}
class RequestBuilder {
	protected $items = []; //请求集合
	public function __construct() {
		$this->items['GET'] = $_GET;
		$this->items['POST'] = $_POST;
		$this->items['REQUEST'] = array_merge($_GET, $_POST);
		if (empty($_POST)) {
			$input = file_get_contents('php://input');
			$data = json_decode($input, true);
			if ($data) {
				$this->items['POST'] = $data;
			}
		}
		defined('IS_AJAX') or define('IS_AJAX', $this->isAjax());
		defined('IS_GET') or define('IS_GET', $this->isMethod('get'));
		defined('IS_POST') or define('IS_POST', $this->isMethod('post'));
		defined('IS_DELETE') or define('IS_DELETE', $this->isMethod('delete'));
		defined('IS_PUT') or define('IS_PUT', $this->isMethod('put'));
	}
	/**
	 * 判断请求类型
	 * @param $action
	 * @return bool
	 */
	public function isMethod($action) {
		switch (strtoupper($action)) {
			case 'GET':
				return $_SERVER['REQUEST_METHOD'] == 'GET';
			case 'POST':
				return $_SERVER['REQUEST_METHOD'] == 'POST' || !empty($this->items['POST']);
			case 'DELETE':
				return $_SERVER['REQUEST_METHOD'] == 'DELETE' || (isset($_POST['_method']) && $_POST['_method'] == 'DELETE');
			case 'PUT':
				return $_SERVER['REQUEST_METHOD'] == 'PUT' || (isset($_POST['_method']) && $_POST['_method'] == 'PUT');
			case 'AJAX':
				return $this->isAjax();
		}
	}
	/**
	 * 获取当前请求类型
	 * GET/POST/DELETE/PUT
	 * @return string
	 */
	public function getRequestType() {
		$types = ['PUT', 'DELETE', 'POST', 'GET'];
		foreach ($types as $tp) {
			if ($this->isMethod($tp)) {
				return $tp;
			}
		}
	}
	/**
	 * 获取所有请求
	 * @return array
	 */
	public function all() {
		return $this->items;
	}
	/**
	 * 获取请求头信息
	 * @return mixed
	 */
	public function getHeader($name = '', $default = '') {
		$server = $_SERVER;
		if(strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false && function_exists('apache_response_headers')) {
			$server = array_merge($server, apache_response_headers());
		}
		$headers = [];
		foreach ($server as $key => $value) {
			if (substr($key, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($key, 5))))] = $value;
			}
		}
		if (empty($name)) {
			return $headers;
		}
		$name = strtolower($name);
		return isset($headers[$name])? $headers[$name] : $default;
	}
	/**
	 * 获取请求 input('get.id', 0, 'intval')
	 * @param string $name 请求类型.请求参数
	 * @param mixed $default 默认值
	 * @param string|array $fn 处理函数intval,md5
	 * @return mixed
	 */
	public function getRequest($name, $default = null, $fn = []) {
		$exp = explode('.', $name);
		if (count($exp) == 1) {
			array_unshift($exp, 'request');
		}
		$action = array_shift($exp);
		return $this->__call($action, [implode('.', $exp), $default, $fn]);
	}
	/**
	 * 获取数据
	 * 示例: Request::get('name')
	 * @param $action    类型如get,post
	 * @param $arguments 参数结构如下
	 *                   [
	 *                   'name'=>'请求变量名',//get.id 可选
	 *                   'default'=>'默认值',//可选
	 *                   'fn'=>'处理函数',//可选
	 *                   ]
	 * @return mixed
	 */
	public function __call($action, $arguments) {
		$action = strtoupper($action);
		if (empty($arguments)) {
			return $this->items[$action];
		}
		$data = $this->arrayGet($this->items[$action], $arguments[0]);
		if (!is_null($data) && !empty($arguments[2])) {
			return $this->batchFunctions($arguments[2], $data);
		}
		return !is_null($data) ? $data : (isset($arguments[1]) ? $arguments[1] : null);
	}
	/**
	 * 数组获取值
	 * @return array
	 */
	protected function arrayGet(array $data, $key, $value = null) {
		$exp = explode('.', $key);
		foreach ((array)$exp as $d) {
			if (isset($data[$d])) {
				$data = $data[$d];
			} else {
				return $value;
			}
		}
		return $data;
	}
	/**
	 * 数组设置值
	 * @return array
	 */
	protected function arraySet(array $data, $key, $value) {
		$tmp = & $data;
		foreach (explode('.', $key) as $d) {
			if (!isset($tmp[$d])) {
				$tmp[$d] = [];
			}
			$tmp = &$tmp[$d];
		}
		$tmp = $value;
		return $data;
	}
	/**
	 * 处理函数
	 * @return mixed
	 */
	protected function batchFunctions($functions, $value) {
		$functions = is_array($functions) ? $functions : explode(',', $functions);
		foreach ($functions as $func) {
			if(function_exists($func)) {
				$value = is_array($value) ? array_map($func, $value) : $func($value);
			}
		}
		return $value;
	}
	/**
	 * 设置请求值
	 * @param string $name 如get.name,post.id
	 * @param mixed $value 值
	 * @return bool
	 */
	public function set($name, $value) {
		$info = explode('.', $name);
		$action = strtoupper(array_shift($info));
		if (isset($this->items[$action])) {
			$this->items[$action] = $this->arraySet($this->items[$action], implode('.', $info), $value);
			return true;
		}
		return false;
	}
	/**
	 * 设置绑定参数GET
	 * @param string $name 请求名称
	 * @param mixed $value 值
	 * @param string $name 请求类型
	 * @return mixed
	 */
	public function setGet($name, $value = '') {
		if (is_array($name)) {
			$this->items['GET'] = array_merge($this->items['GET'], $name);
			$_GET = array_merge($_GET, $name);
		} elseif ($value === null) {
			if (isset($this->items['GET'][$name])) {
				unset($this->items['GET'][$name]);
			}
			if (isset($_GET[$name])) unset($_GET[$name]);
		} else {
			$this->items['GET'][$name] = $value;
			$_GET[$name] = $value;
		}
	}
	/**
	 * 是否为异步(Ajax)提交
	 * @return bool
	 */
	public function isAjax() {
		return isset($_SERVER['HTTP_X_REQUESTED_WITH'])	&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	}
	/**
	 * 请求来源是否为本网站域名
	 * @return bool
	 */
	public function isDomain() {
		if (isset($_SERVER['HTTP_REFERER'])) {
			$referer = parse_url($_SERVER['HTTP_REFERER']);
			return $referer['host'] == $_SERVER['HTTP_HOST'];
		}
		return false;
	}
	/**
	 * 是否https请求
	 * @return bool
	 */
	public function isHttps() {
		if (isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) {
			return true;
		} elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
			return true;
		}
		return false;
	}
	/**
	 * 获取客户端IP
	 * @param int $type
	 * @return mixed|string
	 */
	public function ip($type = 0) {
		$type = ($type == 0)? 0 : 1;
		if (isset($_SERVER)) {
			if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
				$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
			} elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
				$ip = $_SERVER["HTTP_CLIENT_IP"];
			} elseif (isset( $_SERVER["REMOTE_ADDR"])) {
				$ip = $_SERVER["REMOTE_ADDR"];
			} else {
				return '';
			}
		} else {
			if (getenv("HTTP_X_FORWARDED_FOR")) {
				$ip = getenv("HTTP_X_FORWARDED_FOR");
			} elseif (getenv("HTTP_CLIENT_IP")) {
				$ip = getenv("HTTP_CLIENT_IP");
			} elseif (getenv("REMOTE_ADDR")) {
				$ip = getenv("REMOTE_ADDR");
			} else {
				return '';
			}
		}
		$long = ip2long($ip);
		$cip = $long ? [$ip, $long] : ['0.0.0.0', 0];
		return $cip[$type];
	}
	/**
	 * 获取url主机名
	 * @param string $url 链接地址
	 * @return string
	 */
	public function getHost($url = '') {
		if (empty($url)) {
			return $_SERVER['HTTP_HOST'];
		}
		$arr = parse_url($url);
		return isset($arr['host']) ? $arr['host'] : '';
	}
}
<?php
/*--------------------------------------------------------------------------
 | Software: [WillPHP framework]
 | Site: www.113344.com
 |--------------------------------------------------------------------------
 | Author: no-mind <24203741@qq.com>
 | WeChat: www113344
 | Copyright (c) 2020-2022, www.113344.com. All Rights Reserved.
 |-------------------------------------------------------------------------*/
namespace willphp\request\build;
use willphp\config\Config;
use willphp\cookie\Cookie;
use willphp\session\Session;
/**
 * 请求处理
 * Class Base
 * @package willphp\request\build
 */
class Base {
	protected $items = []; //请求集合	 
	/**
	 * 构造函数
	 */
	public function __construct() {		
		$_SERVER['SCRIPT_NAME'] = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
		if (!isset($_SERVER['REQUEST_METHOD'])) {
			$_SERVER['REQUEST_METHOD'] = '';
		}
		if (!isset($_SERVER['HTTP_HOST'])) {
			$_SERVER['HTTP_HOST'] = '';
		}
		if (!isset($_SERVER['REQUEST_URI'])) {
			$_SERVER['REQUEST_URI'] = '';
		}
		defined('NOW') or define('NOW', $_SERVER['REQUEST_TIME']);
		defined('MICROTIME') or define('MICROTIME', $_SERVER['REQUEST_TIME_FLOAT']);
		defined('__URL__') or define('__URL__', $this->url());
		defined('__HISTORY__') or define('__HISTORY__', $this->history());
		defined('__ROOT__') or define( '__ROOT__', $this->domain());
		defined('__WEB__') or define('__WEB__', $this->web());		
		$this->defineRequestConst();
	}
	/**
	 * 启动组件
	 * @return $this
	 */
	public function bootstrap() {
		return $this;
	}
	/**
	 * 当前基础URL
	 * @return string
	 */
	public function url() {
		$rewrite = Config::get('app.url_rewrite', false);
		return $rewrite? str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']) : $_SERVER['SCRIPT_NAME'];
	}
	/**
	 * 获取来源页
	 * @return string
	 */
	public function history() {
		return isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '';
	}
	/**
	 * 网站域名
	 * @return string
	 */
	public function domain() {
		return defined('RUN_MODE') && RUN_MODE != 'http' ? '' : trim('http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']), '/\\');
	}	
	/**
	 * 当前URL(带域名)
	 * @return string
	 */
	public function web() {
		$url = $this->url();		
		return 'http://'.$_SERVER['HTTP_HOST'].$url;
	}
	/**
	 * 定义请求常量
	 */
	protected function defineRequestConst() {
		$this->items['GET'] = $this->filter($_GET);
		$this->items['POST'] = $this->filter($_POST);
		$this->items['REQUEST'] = $this->filter($_REQUEST);
		$this->items['SERVER'] = $this->filter($_SERVER);		
		$this->items['COOKIE']  = Cookie::all();	
		$this->items['SESSION'] = Session::all();
		if (empty($_POST)) {
			$input = file_get_contents('php://input');
			$data = json_decode($input, true);
			if ($data) {
				$this->items['POST'] = $this->filter($data);
			}
		}
		defined('IS_GET') or define('IS_GET', $this->isMethod('get'));
		defined('IS_POST') or define('IS_POST', $this->isMethod('post'));
		defined('IS_DELETE') or define('IS_DELETE', $this->isMethod('delete'));
		defined('IS_PUT') or define('IS_PUT', $this->isMethod('put'));
		defined('IS_AJAX') or define('IS_AJAX', $this->isAjax());
		defined('IS_WECHAT') or define('IS_WECHAT', $this->isWeChat());
		defined('IS_MOBILE') or define('IS_MOBILE', $this->isMobile());	
	}
	/**
	 * 过滤请求
	 */
	public function filter($data) {
		if (!is_array($data)) {
			if ($data === null) $data = '';
			if (!get_magic_quotes_gpc()) $data = addslashes($data);
			$data = htmlspecialchars($data, ENT_QUOTES);
		} else {
			array_walk_recursive($data, function (&$value) {
				if ($value === null) $value = '';
				if (!get_magic_quotes_gpc()) $value = addslashes($value);
				$value = htmlspecialchars($value, ENT_QUOTES);
			});
		}
		return $data;
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
			case 'WECHAT':
				return $this->isWeChat();
			case 'MOBILE':
				return $this->isMobile();
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
			$value = $this->filter($value); //设置时过滤请求值
			$this->items[$action] = $this->arraySet($this->items[$action], implode('.', $info), $value);
			return true;
		}
		return false;
	}
	/**
	 * 设置绑定参数(GET或POST)
	 * @param string $name 请求名称
	 * @param mixed $value 值
	 * @param string $name 请求类型
	 * @return mixed
	 */
	public function setParam($name, $value = '') {
		$action = $this->isMethod('post')? 'POST' : 'GET';		
		if (is_array($name)) {
			$this->items[$action] = array_merge($this->items[$action], $name);
		} elseif ($value === null) {
			if (isset($this->items[$action][$name])) {
				unset($this->items[$action][$name]);
			}
		} else {
			$this->items[$action][$name] = $value;
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
	 * 是否微信端
	 * @return bool
	 */
	public function isWeChat() {
		return isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false;
	}
	/**
	 * 是否手机端
	 * @return bool
	 */
	public function isMobile() {
		if ($this->isWeChat()) {
			return true;
		}
		if (!empty($_GET['_mobile'])) {
			return true;
		}
		if (!isset($_SERVER['HTTP_USER_AGENT'])) {
			return false;
		}
		$_SERVER['ALL_HTTP'] = isset( $_SERVER['ALL_HTTP'] ) ? $_SERVER['ALL_HTTP'] : '';
		$mobile_browser = 0;
		if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
			$mobile_browser ++;
		}
		if ((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false)) {
			$mobile_browser ++;
		}
		if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
			$mobile_browser ++;
		}
		if (isset($_SERVER['HTTP_PROFILE'])) {
			$mobile_browser ++;
		}
		$mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
		$mobile_agents = ['w3c','acs-','alav','alca','amoi','audi','avan','benq','bird','blac','blaz','brew','cell','cldc','cmd-','dang',
				'doco','eric','hipt','inno','ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-','maui','maxo','midp','mits',
				'mmef','mobi','mot-','moto','mwbp','nec-','newt','noki','oper','palm','pana','pant','phil','play','port','prox','qwap','sage',
				'sams','sany','sch-','sec-','send','seri','sgh-','shar','sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
				'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp','wapr','webc','winw','winw','xda','xda-'];
		if (in_array( $mobile_ua, $mobile_agents)) {
			$mobile_browser ++;
		}
		if (strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false) {
			$mobile_browser ++;
		}
		if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false) {
			$mobile_browser = 0;
		}
		if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false) {
			$mobile_browser ++;
		}
		return ($mobile_browser > 0)? true : false;
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

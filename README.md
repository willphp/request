# 请求处理
request组件用于处理请求参数和对请求方式进行判断

#开始使用

####安装组件
使用 composer 命令进行安装或下载源代码使用(依赖willphp/config,willphp/session,willphp/cookie组件)。

    composer require willphp/request

> WillPHP 框架已经内置此组件，无需再安装。

####启动组件

    \willphp\request\Request::bootstrap(); 

####常量定义

组件初始化后会定义请求方式常量：
	
	IS_GET		//是否GET请求
	IS_POST		//是否POST请求
	IS_DELETE	//是否DELETE请求
	IS_PUT 		//是否PUT请求
	IS_AJAX		//是否异步请求
	IS_WECHAT	//是否微信端请求
	IS_MOBILE	//是否手机端请求
	
以及url地址定义常量：

	__ROOT__		//网站域名
	__URL__			//当前url(不带域名)
	__WEB__			//当前url(带域名)
	__HISTORY__		//来源url


####请求方式
判断请求方式的类型有:GET,POST,DELETE,PUT,AJAX,WECHAT,MOBILE

    $isPost = Request::isMethod('post'); //参数为类型(不区分大小写)
    $isAjax = Request::isAjax(); //格式:is类型(第一个字母大写)

####获取请求
获取的请求数据的类型有：GET,POST,REQUEST,SERVER,COOKIE,SESSION

    $id = Request::getRequest('get.id', 0, 'intval');  //参数:[请求变量名],[默认值],[处理函数]
    $name = Request::post('name', '', 'md5'); //同上
    $cookie = Request::cookie(); //获取所有cookie


####设置请求

	Request::set('get.cid', 1); 

####获取IP

	Request::ip(1); //0.string类型(默认), 1.int类型

####是否https

	$bool = Request::isHttps(); //当前是否是https请求

####请求来源

	$bool = Request::isDomain(); //请求来源是否是本站

####获取主机

	Request::getHost($url); //获取url主机名

#助手函数

####获取请求

	$id = input('get.id', 0, 'intval'); //参数:[请求变量名],[默认值],[处理函数]

####获取请求头

	$token = get_header('token'); //获取header('HTTP_TOKEN', 'token');

####获取IP

	$ip = get_ip(); //参数:0.string类型(默认), 1.int类型

####获取当前url

	$url = web_url(true); //true:带域名,false不带域名(默认)

####获取域名地址

	$domain = root_url(); 

####获取来源地址

	$history = history_url();



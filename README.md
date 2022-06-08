##请求处理

request组件用于处理请求参数和对请求方式进行判断

###安装组件

使用 composer 命令进行安装或下载源代码使用。

    composer require willphp/request

>WillPHP框架已经内置此组件，无需再安装。

###请求常量

组件初始化后会定义请求方式常量：
	
	IS_GET		//是否GET请求
	IS_POST		//是否POST请求
	IS_DELETE	//是否DELETE请求
	IS_PUT 		//是否PUT请求
	IS_AJAX		//是否异步请求

###请求方式

判断请求方式的类型有:GET,POST,DELETE,PUT,AJAX

    $isPost = Request::isMethod('post'); //参数为类型(不区分大小写)
    $isAjax = Request::isAjax(); //格式:is类型(第一个字母大写)

###获取请求

获取的请求数据的类型有：GET,POST,REQUEST

    $id = Request::getRequest('get.id', 0, 'intval');  //参数:[请求变量名],[默认值],[处理函数]
    $name = Request::post('name', '', 'md5'); //获取REQUEST
    $get = Request::get(); //获取所有get

###设置请求

	Request::set('get.cid', 1); 

###获取IP

	Request::ip(1); //0.string类型(默认), 1.int类型

###是否https

	$bool = Request::isHttps(); //当前是否是https请求

###请求来源

	$bool = Request::isDomain(); //请求来源是否是本站

###获取主机

	Request::getHost($url); //获取url主机名

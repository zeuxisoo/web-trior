<?php
session_start();
date_default_timezone_set("Asia/Hong_Kong");

define('IN_APPS',     true);
define('WWW_ROOT',    dirname(__FILE__));
define('APP_ROOT',    WWW_ROOT.'/hall');
define('CACHE_ROOT',  WWW_ROOT.'/cache');
define('CONFIG_ROOT', WWW_ROOT.'/config');
define('DATA_ROOT',   WWW_ROOT.'/data');
define('STATIC_ROOT', WWW_ROOT.'/static');
define('VENDOR_ROOT', WWW_ROOT.'/vendor');

require VENDOR_ROOT.'/autoload.php';
require CONFIG_ROOT.'/default.php';

use Slim\Slim;
use Slim\Extras;
use Slim\Views;
use Hall\Helper;

spl_autoload_register(function($_class) {
	$file_path = str_replace('\\', DIRECTORY_SEPARATOR, $_class);
	$path_info = pathinfo($file_path);
	$directory = strtolower($path_info['dirname']);

	$filename_underscore = preg_replace('/\B([A-Z])/', '_$1', $path_info['filename']);

	$class_file_normal     = $directory.DIRECTORY_SEPARATOR.$path_info['filename'].'.php';
	$class_file_underscore = $directory.DIRECTORY_SEPARATOR.$filename_underscore.'.php';

	if (is_file($class_file_normal) === true) {
		require_once $class_file_normal;
	}else if (is_file($class_file_underscore) === true) {
		require_once $class_file_underscore;
	}
});

// Connect to database
if (strtolower($config['database']['driver']) !== "sqlite") {
	ORM::configure(sprintf(
		'%s:host=%s;dbname=%s',
		$config['database']['driver'], $config['database']['host'], $config['database']['dbname']
	));
	ORM::configure('username', $config['database']['username']);
	ORM::configure('password', $config['database']['password']);
}else{
	ORM::configure('sqlite:'.$config['database']['host']);
}

if (strtolower($config['database']['driver']) === "mysql") {
	ORM::configure('driver_options', array(
		PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
	));
}

Model::$auto_prefix_models = '\\Hall\\Model\\';

// Slim
$app = new Slim(array(
	'debug' => $config['default']['debug'],
	'view'  => new Views\Twig(),
));

// Slim middleware
$app->add(new Extras\Middleware\CsrfGuard());

// Slim view
$view = $app->view();
$view->twigTemplateDirs = array(APP_ROOT.'/view');
$view->parserOptions = array(
	'charset'          => 'utf-8',
	'cache'            => realpath(CACHE_ROOT.'/view'),
	'auto_reload'      => true,
	'strict_variables' => false,
	'autoescape'       => true
);
$view->parserExtensions = array(
	new Views\TwigExtension(),
);

// Slim view global variable
$view->getEnvironment()->addGlobal("session", $_SESSION);
$view->getEnvironment()->addGlobal("view", new Helper\View());

// Load app directories
$auto_load_directories = array(
	APP_ROOT.'/route/*',
);

foreach($auto_load_directories as $auto_load_directory) {
	foreach(glob($auto_load_directory) as $route) {
		require_once $route;
	}
}

// Get site URL
$request   = $app->request();
$site_url  = $request->getUrl().$request->getRootUri();

// Bind view variable
$app->view()->setData('config', $config);

// Bind app variable
$app->config('app.config',   $config);
$app->config('app.site_url', $site_url);

// Start
$app->run();

<?php

	if (!defined('CASPAR_PATH')) {
		throw new RuntimeException('You must define the CASPAR_PATH constant so we can find the files we need');
	}

	error_reporting(E_ALL | E_NOTICE | E_STRICT);
	date_default_timezone_set('UTC');

	define('CASPAR_CORE_PATH', CASPAR_PATH . 'caspar' . DS . 'core' . DS);
	define('CASPAR_LIB_PATH', CASPAR_PATH . 'libs' . DS);
	define('CASPAR_APPLICATION_PATH', CASPAR_PATH . 'application' . DS);
	define('CASPAR_MODULES_PATH', CASPAR_APPLICATION_PATH . 'modules' . DS);
	define('CASPAR_CACHE_PATH', CASPAR_PATH . 'caspar' . DS . 'cache' . DS);
	defined('CASPAR_SESSION_NAME') || define('CASPAR_SESSION_NAME', 'CASPAR_SESSION');

	// Load the context class, which controls most of things
	require CASPAR_CORE_PATH . 'Caspar.class.php';

	// Set up autoloading
	spl_autoload_register(array('\\caspar\\core\\Caspar', 'autoload'));

	// Set up error and exception handling
	set_error_handler(array('\\caspar\\core\\Caspar', 'errorHandler'));
	set_exception_handler(array('\\caspar\\core\\Caspar', 'exceptionHandler'));
	error_reporting(E_ALL | E_NOTICE | E_STRICT);

	// Set core autoloader paths
	caspar\core\Caspar::autoloadNamespace('caspar\\core', CASPAR_CORE_PATH);
	caspar\core\Caspar::autoloadNamespace('application\\modules', CASPAR_MODULES_PATH);
	caspar\core\Caspar::addAutoloaderClassPath(CASPAR_LIB_PATH);

	if (!isset($argc) && !ini_get('session.auto_start')) {
		session_name(CASPAR_SESSION_NAME);
		session_start();
	}

	// Start loading Caspar
	caspar\core\Caspar::initialize();

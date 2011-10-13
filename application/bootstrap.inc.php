<?php

	$path = realpath(getcwd());
	defined('THEBUGGENIE_PATH') || define('THEBUGGENIE_PATH', CASPAR_PATH);
	defined('THEBUGGENIE_SESSION_NAME') || define('THEBUGGENIE_SESSION_NAME', 'THEBUGGENIE');
	defined('THEBUGGENIE_CORE_PATH') || define('THEBUGGENIE_CORE_PATH', CASPAR_PATH . 'application' . DS . 'classes' . DS . 'core' . DS);
	defined('THEBUGGENIE_MODULES_PATH') || define('THEBUGGENIE_MODULES_PATH', CASPAR_MODULES_PATH);
	defined('THEBUGGENIE_PUBLIC_FOLDER_NAME') || define('THEBUGGENIE_PUBLIC_FOLDER_NAME', mb_substr($path, strrpos($path, DS) + 1));

	\caspar\core\Caspar::autoloadNamespace('thebuggenie\core', THEBUGGENIE_CORE_PATH);

//	require THEBUGGENIE_CORE_PATH . 'geshi/geshi.php';

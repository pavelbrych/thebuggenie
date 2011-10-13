<?php

	$path = realpath(getcwd());
	defined('THEBUGGENIE_PATH') || define('THEBUGGENIE_PATH', CASPAR_PATH);
	defined('THEBUGGENIE_SESSION_NAME') || define('THEBUGGENIE_SESSION_NAME', 'THEBUGGENIE');
	defined('THEBUGGENIE_CORE_PATH') || define('THEBUGGENIE_CORE_PATH', THEBUGGENIE_PATH . 'core' . DS);
	defined('THEBUGGENIE_MODULES_PATH') || define('THEBUGGENIE_MODULES_PATH', THEBUGGENIE_PATH . 'modules' . DS);
	defined('THEBUGGENIE_PUBLIC_FOLDER_NAME') || define('THEBUGGENIE_PUBLIC_FOLDER_NAME', mb_substr($path, strrpos($path, DS) + 1));

	\caspar\core\Caspar::autoloadNamespace('thebuggenie\modules', THEBUGGENIE_MODULES_PATH);
//	\caspar\core\Caspar::autoloadNamespace('thebuggenie', THEBUGGENIE_CORE_PATH . 'classes' . DS);

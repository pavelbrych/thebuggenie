<?php

	namespace thebuggenie\core;

	class Context
	{
		/**
		 * Initialize the context
		 *
		 * @return null
		 */
		public static function initialize()
		{
			try
			{
				mb_internal_encoding("UTF-8");
				mb_language('uni');
				mb_http_output("UTF-8");

				self::$_request = new Request();
				self::$_response = new Response();
				self::$_factory = new Factory();

				self::checkInstallMode();
				self::loadPreModuleRoutes();
				self::setScope();

				if (!self::$_installmode)
				{
					self::loadModules();
					self::initializeUser();
				}

				else
					self::$_modules = array();

//				var_dump(self::getUser());die();
				self::setupI18n();

				if (!is_writable(THEBUGGENIE_CORE_PATH . DIRECTORY_SEPARATOR . 'cache'))
				{
					throw new \Exception(self::geti18n()->__('The cache directory is not writable. Please correct the permissions of core/cache, and try again'));
				}

				self::loadPostModuleRoutes();
				Logging::log('...done initializing');
			}
			catch (Exception $e)
			{
				if (!self::isCLI() && !self::isInstallmode())
					throw $e;
			}
		}

	}
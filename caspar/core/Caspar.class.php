<?php

	namespace caspar\core;

	/**
	 * The core class of the B2 engine
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage core
	 */

	/**
	 * The core class of the B2 engine
	 *
	 * @package thebuggenie
	 * @subpackage core
	 */
	class Caspar
	{

		const PREDEFINED_SEARCH_PROJECT_OPEN_ISSUES = 1;
		const PREDEFINED_SEARCH_PROJECT_CLOSED_ISSUES = 2;
		const PREDEFINED_SEARCH_PROJECT_MILESTONE_TODO = 6;
		const PREDEFINED_SEARCH_PROJECT_MOST_VOTED = 7;
		const PREDEFINED_SEARCH_MY_ASSIGNED_OPEN_ISSUES = 3;
		const PREDEFINED_SEARCH_TEAM_ASSIGNED_OPEN_ISSUES = 4;
		const PREDEFINED_SEARCH_MY_REPORTED_ISSUES = 5;
		
		static protected $_environment = 2;

		static protected $debug_mode = true;
		
		static protected $_partials_visited = array();

		static protected $_configuration;
		
		static protected $_ver_mj;
		static protected $_ver_mn;
		static protected $_ver_rev;
		static protected $_ver_name;

		static protected $_b2db = array();
		
		/**
		 * The current user
		 *
		 * @var User
		 */
		static protected $_user;
		
		/**
		 * The include path
		 * 
		 * @var string
		 */
		static protected $_includepath;
		
		/**
		 * The path to thebuggenie relative from url server root
		 * 
		 * @var string
		 */
		static protected $_tbgpath;
		
		/**
		 * Stripped version of the $_tbgpath
		 * 
		 * @see $_tbgpath
		 * 
		 * @var string
		 */
		static protected $_stripped_tbgpath;
		
		/**
		 * The i18n object
		 *
		 * @Class \thebuggenie\entities\I18n
		 */
		static protected $_i18n;
		
		/**
		 * The request object
		 * 
		 * @var Request
		 */
		static protected $_request;
		
		/**
		 * The response object
		 * 
		 * @var Response
		 */
		static protected $_response;
		
		/**
		 * The Factory instance
		 *
		 * @var Factory
		 */
		static protected $_factory;
		
		/**
		 * Used to determine when the b2 engine started loading
		 * 
		 * @var integer
		 */
		static protected $_loadstart;
		
		/**
		 * Used for timing purposes
		 * 
		 * @var integer
		 */
		static protected $_loadend;
		
		/**
		 * List of classpaths
		 * 
		 * @var array
		 */
		static protected $_classpaths = array();
		
		/**
		 * List of loaded libraries
		 * 
		 * @var string
		 */
		static protected $_libs = array();
		
		/**
		 * The routing object
		 * 
		 * @var Routing
		 */
		static protected $_routing;

		/**
		 * Messages passed on from the previous request
		 *
		 * @var array
		 */
		static protected $_messages;

		static protected $_redirect_login;
		
		/**
		 * Do you want to disable minifcation of javascript and css?
		 * 
		 * @var boolean
		 */
		static protected $_minifyoff = true;

		/**
		 * Returns whether or minify is disabled
		 * 
		 * @return boolean
		 */
		public static function isMinifyDisabled()
		{
			return self::$_minifyoff;
		}

		/**
		 * Add a path to the list of searched paths in the autoloader
		 * Class files must contain one class with the same name as the class
		 * in the form of Classname.class.php
		 * 
		 * @param string $path The path where the class files are
		 * 
		 * @return null
		 */
		public static function autoloadNamespace($namespace, $path)
		{
			$realpath = realpath($path);
			if (!file_exists($realpath)) throw new \Exception("Cannot autoload classes from path '{$path}', since the path doesn't exist");
			self::$_classpaths[$namespace] = $realpath;
		}
		
		public static function addAutoloaderClassPath($path)
		{
			$realpath = realpath($path);
			if (!file_exists($realpath)) throw new \Exception("Cannot autoload classes from path '{$path}', since the path doesn't exist");

//			if (file_exists($path . DS . 'actions.class.php'))
//				require_once $path . DS . 'actions.class.php';
//
//			if (file_exists($path . DS . 'actioncomponents.class.php'))
//				require_once $path . DS . 'actioncomponents.class.php';

			self::$_classpaths[0][] = $realpath;
		}
		
		/**
		 * Returns the classpaths that has been registered to the autoloader
		 *
		 * @return array
		 */
		public static function getAutoloadedNamespaces()
		{
			if (!array_key_exists(0, self::$_classpaths)) self::$_classpaths[0] = array();
			return self::$_classpaths;
		}
		
		/**
		 * Magic autoload function to make sure classes are autoloaded when used
		 * 
		 * @param $classname
		 */
		public static function autoload($classname)
		{
			$class_details = explode('\\', $classname);
			$namespaces = self::getAutoloadedNamespaces();
			if (count($class_details) > 1)
			{
				$classname_element = array_pop($class_details);
				$orig_class_details = $class_details;
				$cc = count($class_details);
				while (!empty($class_details))
				{
					$namespace = join('\\', $class_details);
					if (array_key_exists($namespace, $namespaces))
					{
						for ($ccc = 1; $ccc <= $cc; $ccc++) array_shift($orig_class_details);

						$classpath = (count($orig_class_details)) ? join(DS, $orig_class_details) . DS : '';
						$basepath = $namespaces[$namespace];
						$filename = $basepath . DS . $classpath . $classname_element . '.class.php';
						$filename_alternate = $basepath . DS . $classpath . "classes" . DS . $classname_element . ".class.php";
						break;
					}
					array_pop($class_details);
					$cc--;
				}
			}
			else
			{
				foreach ($namespaces[0] as $classpath)
				{
					if (file_exists($classpath . DS . $classname . '.class.php'))
					{
						$filename = $classpath . DS . $classname . '.class.php';
						break;
					}
				}
			}
			if (isset($filename) && file_exists($filename))
			{
				require $filename;
				return;
			}
			elseif (isset($filename_alternate) && file_exists($filename_alternate))
			{
				require $filename_alternate;
				return;
			}
			$filename = (isset($filename)) ? $filename : null;
			$filename_alternate = (isset($filename_alternate)) ? $filename_alternate : null;
			throw new \Exception("Class {$classname} not found, even though we tried both '{$filename}' and '{$filename_alternate}'");
		}
		
		/**
		 * Returns the classpaths that has been registered to the autoloader
		 *
		 * @return array
		 */
		public static function getClasspaths()
		{
			return self::$_classpaths;
		}

		/**
		 * Setup the routing object with CLI parameters
		 *
		 * @param string $module
		 * @param string $action
		 */
		public static function setCLIRouting($module, $action)
		{
			self::$_routing->setCurrentRouteModule($module);
			self::$_routing->setCurrentRouteAction($action);
			self::$_routing->setCurrentRouteName('cli');
			self::$_routing->setCurrentRouteCSRFenabled(false);
		}

		/**
		 * Returns the routing object
		 * 
		 * @return Routing
		 */
		public static function getRouting()
		{
			if (!is_object(self::$_routing))
			{
				self::$_routing = new Routing(self::$_configuration['routes']);
			}
			return self::$_routing;
		}
		
		/**
		 * Get when we last loaded the engine
		 * 
		 * @return integer
		 */
		public static function getLastLoadedAt()
		{
			return $_SESSION['b2lastreloadtime'];
		}
		
		/**
		 * Set when we last loaded the engine
		 */
		public static function setLoadedAt()
		{
			$_SESSION['b2lastreloadtime'] = NOW;
		}
		
		/**
		 * Get the subdirectory part of the url
		 * 
		 * @return string
		 */
		public static function getTBGPath()
		{
			if (self::$_tbgpath === null)
			{
				self::_setTBGPath();
			}
			return self::$_tbgpath;
		}
		
		/**
		 * Get the subdirectory part of the url, stripped
		 * 
		 * @return string
		 */
		public static function getStrippedTBGPath()
		{
			if (self::$_stripped_tbgpath === null)
			{
				self::$_stripped_tbgpath = mb_substr(self::getTBGPath(), 0, mb_strlen(self::getTBGPath()) - 1);
			}
			return self::$_stripped_tbgpath;
		}

		/**
		 * Set the subdirectory part of the url, from the url
		 */
		protected static function _setTBGPath()
		{
			self::$_tbgpath = defined('TBG_CLI') ? '.' : dirname($_SERVER['PHP_SELF']);
			if (stristr(PHP_OS, 'WIN')) { self::$_tbgpath = str_replace("\\", "/", self::$_tbgpath); /* Windows adds a \ to the URL which we don't want */ }
			if (self::$_tbgpath[strlen(self::$_tbgpath) - 1] != '/') self::$_tbgpath .= '/';
		}
		
		/**
		 * Set that we've started loading
		 * 
		 * @param integer $when
		 */
		public static function setLoadStart($when)
		{
			self::$_loadstart = $when;
		}
		
		/**
		 * Manually ping the loader
		 */
		public static function ping()
		{
			$endtime = explode(' ', microtime());
			self::$_loadend = $endtime[1] + $endtime[0];
		}

		/**
		 * Get the time from when we started loading
		 * 
		 * @param integer $precision
		 * @return integer
		 */
		public static function getLoadtime($precision = 5)
		{
			self::ping();
			return round((self::$_loadend - self::$_loadstart), $precision);
		}
		
		protected static function setupI18n()
		{
			if (self::isCLI())
				return null;

			$language = (self::$_user instanceof User) ? self::$_user->getLanguage() : Settings::getLanguage();
			
			if (self::$_user instanceof User && self::$_user->getLanguage() == 'sys')
			{
				$language = Settings::getLanguage();
			}
			
			Logging::log('Loading i18n strings');
			if (!self::$_i18n = Cache::get("i18n_{$language}"))
			{
				Logging::log("Loading strings from file ({$language})");
				self::$_i18n = new TBGI18n($language);
				self::$_i18n->initialize();
				Cache::add("i18n_{$language}", self::$_i18n);
			}
			else
			{
				Logging::log('Using cached i18n strings');
			}
			Logging::log('...done');
		}

		protected static function initializeUser()
		{
			Logging::log('Loading user');
			try
			{
				Logging::log('is this logout?');
				if (self::getRequest()->getParameter('logout'))
				{
					Logging::log('yes');
					self::logout();
				}
				else
				{
					Logging::log('no');
					Logging::log('sets up user object');
					$event = Event::createNew('core', 'pre_login');
					$event->trigger();

					if ($event->isProcessed())
						self::loadUser($event->getReturnValue());
					else
						self::loadUser();

					Event::createNew('core', 'post_login', self::getUser())->trigger();

					Logging::log('loaded');
				}
			}
			catch (Exception $e)
			{
				Logging::log("Something happened while setting up user: ". $e->getMessage(), 'main', Logging::LEVEL_WARNING);
				if (!self::isCLI() && (self::getRouting()->getCurrentRouteModule() != 'main' || self::getRouting()->getCurrentRouteAction() != 'register1' && self::getRouting()->getCurrentRouteAction() != 'register2' && self::getRouting()->getCurrentRouteAction() != 'activate' && self::getRouting()->getCurrentRouteAction() != 'reset_password' && self::getRouting()->getCurrentRouteAction() != 'captcha' && self::getRouting()->getCurrentRouteAction() != 'login' && self::getRouting()->getCurrentRouteAction() != 'getBackdropPartial' && self::getRouting()->getCurrentRouteAction() != 'serve' && self::getRouting()->getCurrentRouteAction() != 'doLogin'))
					self::$_redirect_login = true;
				else {
					$classname = self::$_configuration['core']['user_classname'];
					self::$_user = new $classname();
				}
			}
			Logging::log('...done');
		}

		protected static function loadPreModuleRoutes()
		{
			Logging::log('Loading first batch of routes', 'routing');
			if (!($routes_1 = Cache::get(Cache::KEY_PREMODULES_ROUTES_CACHE)))
			{
				if (!($routes_1 = Cache::fileGet(Cache::KEY_PREMODULES_ROUTES_CACHE)))
				{
					Logging::log('generating routes', 'routing');
					require THEBUGGENIE_CORE_PATH . 'load_routes.inc.php';
					Cache::fileAdd(Cache::KEY_PREMODULES_ROUTES_CACHE, self::getRouting()->getRoutes());
				}
				else
				{
					Logging::log('using disk cached routes', 'routing');
					self::getRouting()->setRoutes($routes_1);
				}
				Cache::add(Cache::KEY_PREMODULES_ROUTES_CACHE, self::getRouting()->getRoutes());
			}
			else
			{
				Logging::log('loading routes from cache', 'routing');
				self::getRouting()->setRoutes($routes_1);
			}
			Logging::log('...done', 'routing');
		}

		protected static function loadPostModuleRoutes()
		{
			Logging::log('Loading last batch of routes', 'routing');
			if (!($routes = Cache::get(Cache::KEY_POSTMODULES_ROUTES_CACHE)))
			{
				if (!($routes = Cache::fileGet(Cache::KEY_POSTMODULES_ROUTES_CACHE)))
				{
					Logging::log('generating postmodule routes', 'routing');
					require THEBUGGENIE_CORE_PATH . 'load_routes_postmodules.inc.php';
					Cache::fileAdd(Cache::KEY_POSTMODULES_ROUTES_CACHE, self::getRouting()->getRoutes());
				}
				else
				{
					Logging::log('using disk cached postmodule routes', 'routing');
					self::getRouting()->setRoutes($routes);
				}
				Cache::add(Cache::KEY_POSTMODULES_ROUTES_CACHE, self::getRouting()->getRoutes());
			}
			else
			{
				Logging::log('loading postmodule routes from cache', 'routing');
				self::getRouting()->setRoutes($routes);
			}
			Logging::log('...done', 'routing');
		}

		/**
		 * Returns the factory object
		 *
		 * @return Factory
		 */
		public static function factory()
		{
			if (!self::$_factory instanceof Factory) {
				self::$_factory = new Factory();
			}
			return self::$_factory;
		}

		/**
		 * Returns the request object
		 * 
		 * @return Request
		 */
		public static function getRequest()
		{
			if (!self::$_request instanceof Request) {
				self::$_request = new Request();
			}
			return self::$_request;
		}
		
		/**
		 * Returns the response object
		 * 
		 * @return Response
		 */
		public static function getResponse()
		{
			if (!is_object(self::$_response)) {
				$classname = self::$_configuration['core']['response_classname'];
				self::$_response = new $classname();
			}
			return self::$_response;
		}
		
		/**
		 * Reinitialize the i18n object, used only when changing the language in the middle of something
		 * 
		 * @param string $language The language code to change to
		 */
		public static function reinitializeI18n($language = null) 
		{
			if (!$language)
			{
				self::$_i18n = new TBGI18n(Settings::get('language'));
			}
			else
			{
				Logging::log('Changing language to '.$language);
				self::$_i18n = new TBGI18n($language);
				self::$_i18n->initialize();
			}
		}
		
		/**
		 * Get the i18n object
		 *
		 * @return TBGI18n
		 */
		public static function getI18n()
		{
			if (!self::$_i18n instanceof I18n) {
				self::$_i18n = new I18n();
//				Logging::log('Cannot access the translation object until the i18n system has been initialized!', 'i18n', Logging::LEVEL_WARNING);
//				throw new \Exception('Cannot access the translation object until the i18n system has been initialized!');
				//self::reinitializeI18n(self::getUser()->getLanguage());
			}
			return self::$_i18n;
		}
		
		/**
		 * Get available themes
		 * 
		 * @return array
		 */
		public static function getThemes()
		{
			$theme_path_handle = opendir(THEBUGGENIE_PATH . THEBUGGENIE_PUBLIC_FOLDER_NAME . DS . 'themes' . DS);
			$themes = array();
			
			while ($theme = readdir($theme_path_handle))
			{
				if ($theme != '.' && $theme != '..' && is_dir(THEBUGGENIE_PATH . THEBUGGENIE_PUBLIC_FOLDER_NAME . DS . 'themes' . DS . $theme) && file_exists(THEBUGGENIE_PATH . THEBUGGENIE_PUBLIC_FOLDER_NAME . DS . 'themes' . DS . $theme . DS . 'theme.php')) 
				{ 
					$themes[] = $theme; 
				}
			}
			
			return $themes;
		}

		public static function getIconSets()
		{
			$icon_path_handle = opendir(THEBUGGENIE_PATH . THEBUGGENIE_PUBLIC_FOLDER_NAME . DS . 'iconsets' . DS);
			$icons = array();
			
			while ($icon = readdir($icon_path_handle))
			{
				if ($icon != '.' && $icon != '..' && is_dir(THEBUGGENIE_PATH . THEBUGGENIE_PUBLIC_FOLDER_NAME . DS . 'iconsets' . DS . $icon)) 
				{ 
					$icons[] = $icon; 
				}
			}
			
			return $icons;
		}
		
		/**
		 * Load the user object into the user property
		 * 
		 * @return User
		 */
		public static function loadUser($user = null)
		{
			try
			{
				$classname = self::$_configuration['core']['user_classname'];
				self::$_user = ($user === null) ? $classname::loginCheck(self::getRequest()->getParameter('tbg3_username'), self::getRequest()->getParameter('tbg3_password')) : $user;
				if (self::$_user->isAuthenticated())
				{
					if (self::$_user->isOffline() || self::$_user->isAway())
					{
						self::$_user->setOnline();
					}
					self::$_user->updateLastSeen();
					Event::createNew('core', 'post_loaduser', self::$_user)->trigger();
				}
			}
			catch (Exception $e)
			{
				throw $e;
			}
			return self::$_user;
		}
		
		/**
		 * Returns the user object
		 *
		 * @return User
		 */
		public static function getUser()
		{
			if (!self::$_user instanceof self::$_configuration['core']['user_classname']) {
				self::initializeUser();
			}
			return self::$_user;
		}
		
		/**
		 * Set the current user
		 * 
		 * @param User $user
		 */
		public static function setUser(User $user)
		{
			self::$_user = $user;
		}

		/**
		 * Log out the current user (does not work when auth method is set to http)
		 */
		public static function logout()
		{
			if (Settings::isUsingExternalAuthenticationBackend())
			{
				$mod = self::getModule(Settings::getAuthenticationBackend());
				$mod->logout();
			}
			
			Event::createNew('core', 'pre_logout')->trigger();
			self::getResponse()->deleteCookie('tbg3_username');
			self::getResponse()->deleteCookie('tbg3_password');
			self::getResponse()->deleteCookie('THEBUGGENIE');
			session_regenerate_id(true);
			Event::createNew('core', 'post_logout')->trigger();
		}

		
		/**
		 * Set a message to be retrieved in the next request
		 * 
		 * @param string $message The message
		 */
		public static function setMessage($key, $message)
		{
			if (!array_key_exists('tbg_message', $_SESSION))
			{
				$_SESSION['tbg_message'] = array();
			}
			$_SESSION['tbg_message'][$key] = $message;
		}

		protected static function _setupMessages()
		{
			if (self::$_messages === null)
			{
				self::$_messages = array();
				if (array_key_exists('tbg_message', $_SESSION))
				{
					self::$_messages = $_SESSION['tbg_message'];
					unset($_SESSION['tbg_message']);
				}
			}
		}

		/**
		 * Whether or not there is a message in the next request
		 * 
		 * @return boolean
		 */
		public static function hasMessage($key)
		{
			self::_setupMessages();
			return array_key_exists($key, self::$_messages);
		}
		
		/**
		 * Retrieve a message passed on from the previous request
		 *
		 * @param string $key A message identifier
		 *
		 * @return string
		 */
		public static function getMessage($key)
		{
			return (self::hasMessage($key)) ? self::$_messages[$key] : null;
		}
		
		/**
		 * Clear the message
		 */
		public static function clearMessage($key)
		{
			if (self::hasMessage($key))
			{
				unset(self::$_messages[$key]);
			}
		}

		/**
		 * Retrieve the message and clear it
		 * 
		 * @return string
		 */
		public static function getMessageAndClear($key)
		{
			if ($message = self::getMessage($key))
			{
				self::clearMessage($key);
				return $message;
			}
			return null;
		}

		public static function generateCSRFtoken()
		{
			if (!array_key_exists('csrf_token', $_SESSION) || $_SESSION['csrf_token'] == '')
			{
				$_SESSION['csrf_token'] = str_replace('.', '_', uniqid(rand(), TRUE));
			}
			return $_SESSION['csrf_token'];
		}

		public static function checkCSRFtoken($handle_response = false)
		{
			$token = self::generateCSRFtoken();
			if ($token == self::getRequest()->getParameter('csrf_token')) return true;

			$message = self::getI18n()->__('An authentication error occured. Please reload your page and try again');
			/*if ($handle_response)
			{
				self::$_response->setHttpStatus(301);
				if (self::getRequest()->getRequestedFormat() == 'json')
				{
					self::$_response->setContentType('application/json');
					echo json_encode(array('message' => $message));
				}
				else
				{
					echo $message;
				}
			}
			else
			{*/
				throw new TBGCSRFFailureException($message);
			//}
			return false;
		}

		/**
		 * Loads a function library
		 * 
		 * @param string $lib_name The name of the library
		 */
		public static function loadLibrary($lib_name)
		{
			if (mb_strpos($lib_name, '/') !== false)
			{
				list ($module, $lib_name) = explode('/', $lib_name);
			}

			// Skip the library if it already exists
			if (!array_key_exists($lib_name, self::$_libs))
			{
				$lib_file_name = "{$lib_name}.inc.php";

				if (isset($module) && file_exists(CASPAR_MODULES_PATH . $module . DS . 'lib' . DS . $lib_file_name)) {
					require CASPAR_MODULES_PATH . $module . DS . 'lib' . DS . $lib_file_name;
					self::$_libs[$lib_name] = CASPAR_MODULES_PATH . $module . DS . 'lib' . DS . $lib_file_name;
				} elseif (file_exists(CASPAR_MODULES_PATH . self::getRouting()->getCurrentRouteModule() . DS . 'lib' . DS . $lib_file_name)) {
					// Include the library from the current module if it exists
					require CASPAR_MODULES_PATH . self::getRouting()->getCurrentRouteModule() . DS . 'lib' . DS . $lib_file_name;
					self::$_libs[$lib_name] = CASPAR_MODULES_PATH . self::getRouting()->getCurrentRouteModule() . DS . 'lib' . DS . $lib_file_name;
				} elseif (file_exists(CASPAR_LIB_PATH . DS . $lib_file_name)) {
					// Include the library from the global library directory if it exists
					require CASPAR_LIB_PATH . DS . $lib_file_name;
					self::$_libs[$lib_name] = CASPAR_LIB_PATH . DS . $lib_file_name;
				} else {
					// Throw an \Exception if the library can't be found in any of
					// the above directories
					Logging::log("The \"{$lib_name}\" library does not exist in either " . CASPAR_MODULES_PATH . self::getRouting()->getCurrentRouteModule() . DS . 'lib' . DS . ' or ' . CASPAR_CORE_PATH . 'lib' . DS, 'core', Logging::LEVEL_FATAL);
					throw new LibraryNotFoundException("The \"{$lib_name}\" library does not exist in either " . CASPAR_MODULES_PATH . self::getRouting()->getCurrentRouteModule() . DS . 'lib' . DS . ' or ' . CASPAR_CORE_PATH . 'lib' . DS);
				}
			}
		}
		
		public static function visitPartial($template_name, $time)
		{
			if (!self::$debug_mode) return;
			if (!array_key_exists($template_name, self::$_partials_visited))
			{
				self::$_partials_visited[$template_name] = array('time' => $time, 'count' => 1);
			}
			else
			{
				self::$_partials_visited[$template_name]['count']++;
				self::$_partials_visited[$template_name]['time'] += $time;
			}
		}
		
		public static function getVisitedPartials()
		{
			return self::$_partials_visited;
		}
		
		/**
		 * Performs an action
		 * 
		 * @param string $module Name of the action
		 * @param string $method Name of the action method to run
		 */
		public static function performAction($module, $method)
		{
			// Set content variable
			$content = null;
			
			// Set the template to be used when rendering the html (or other) output
			$template_path = CASPAR_MODULES_PATH . $module . DS . 'templates' . DS;

			// Construct the action class and method name, including any pre- action(s)
			$actionClassName = "\\application\\modules\\$module\\Actions";
			$actionToRunName = 'run' . ucfirst($method);
			$preActionToRunName = 'pre' . ucfirst($method);

			// Set up the response object, responsible for controlling any output
			self::getResponse()->setPage(self::getRouting()->getCurrentRouteName());
			self::getResponse()->setTemplate(mb_strtolower($method) . '.' . self::getRequest()->getRequestedFormat() . '.php');
			self::getResponse()->setupResponseContentType(self::getRequest()->getRequestedFormat());
			
			// Set up the action object
			$actionObject = new $actionClassName();

			// Run the specified action method set if it exists
			if (method_exists($actionObject, $actionToRunName))
			{
				// Turning on output buffering
				ob_start('mb_output_handler');
				ob_implicit_flush(0);

				if (self::getRouting()->isCurrentRouteCSRFenabled())
				{
					// If the csrf check fails, don't proceed
					if (!self::checkCSRFtoken(true))
					{
						return true;
					}
				}

				if (self::$debug_mode)
				{
					$time = explode(' ', microtime());
					$pretime = $time[1] + $time[0];
				}
				if ($content === null)
				{
					Logging::log('Running main pre-execute action');
					// Running any overridden preExecute() method defined for that module
					// or the default empty one provided by TBGAction
					if ($pre_action_retval = $actionObject->preExecute(self::getRequest(), $method))
					{
						$content = ob_get_clean();
						Logging::log('preexecute method returned something, skipping further action');
						if (self::$debug_mode) $visited_templatename = "{$actionClassName}::preExecute()";
					}
				}

				if ($content === null)
				{
					$action_retval = null;
					if (self::getResponse()->getHttpStatus() == 200)
					{
						// Checking for and running action-specific preExecute() function if
						// it exists
						if (method_exists($actionObject, $preActionToRunName))
						{
							Logging::log('Running custom pre-execute action');
							$actionObject->$preActionToRunName(self::getRequest(), $method);
						}

						// Running main route action
						Logging::log('Running route action '.$actionToRunName.'()');
						if (self::$debug_mode)
						{
							$time = explode(' ', microtime());
							$action_pretime = $time[1] + $time[0];
						}
						$action_retval = $actionObject->$actionToRunName(self::getRequest());
						if (self::$debug_mode)
						{
							$time = explode(' ', microtime());
							$action_posttime = $time[1] + $time[0];
							self::visitPartial("{$actionClassName}::{$actionToRunName}", $action_posttime - $action_pretime);
						}
					}
					if (self::getResponse()->getHttpStatus() == 200 && $action_retval) {
						// If the action returns *any* output, we're done, and collect the
						// output to a variable to be outputted in context later
						$content = ob_get_clean();
						Logging::log('...done');
					} elseif (!$action_retval) {
						// If the action doesn't return any output (which it usually doesn't)
						// we continue on to rendering the template file for that specific action
						Logging::log('...done');
						Logging::log('Displaying template');

						// Check to see if we have a translated version of the template
						if (!self::getI18n() instanceof I18n || ($templateName = self::getI18n()->hasTranslatedTemplate(self::getResponse()->getTemplate())) === false) {
							// Check to see if the template has been changed, and whether it's in a
							// different module, specified by "module/templatename"
							if (mb_strpos(self::getResponse()->getTemplate(), '/')) {
								$newPath = explode('/', self::getResponse()->getTemplate());
								$templateName = THEBUGGENIE_MODULES_PATH . $newPath[0] . DS . 'templates' . DS . $newPath[1] . '.' . self::getRequest()->getRequestedFormat() . '.php';
							} else {
								$templateName = $template_path . self::getResponse()->getTemplate();
							}
						}

						// Check to see if the template exists and throw an \Exception otherwise
						if (!file_exists($templateName))
						{
							Logging::log('The template file for the ' . $method . ' action ("'.self::getResponse()->getTemplate().'") does not exist', 'core', Logging::LEVEL_FATAL);
							throw new TemplateNotFoundException('The template file for the ' . $method . ' action ("'.self::getResponse()->getTemplate().'") does not exist');
						}

						self::loadLibrary('common');
						// Present template for current action
						ActionComponents::presentTemplate($templateName, $actionObject->getParameterHolder());
						$content = ob_get_clean();
						Logging::log('...completed');
					}
				}
				elseif (self::$debug_mode)
				{
					$time = explode(' ', microtime());
					$posttime = $time[1] + $time[0];
					self::visitPartial($visited_templatename, $posttime - $pretime);
				}

				if (!isset($csp_response))
				{
					/**
					 * @global Request The request object
					 */
					$csp_request = self::getRequest();

					/**
					 * @global User The user object
					 */
					$csp_user = self::getUser();

					/**
					 * @global Response The response object
					 */
					$csp_response = self::getResponse();
					
					/**
					 * @global Routing The routing object
					 */
					$csp_routing = self::getRouting();

					// Load the "ui" library, since this is used a lot
					self::loadLibrary('ui');
				}

				self::loadLibrary('common');
				Logging::log('rendering content');
				
				if (self::isMaintenanceModeEnabled() && !mb_strstr(self::getRouting()->getCurrentRouteName(), 'configure'))
				{
					if (!file_exists(CASPAR_APPLICATION_PATH . 'templates/offline.inc.php'))
					{
						throw new TBGTemplateNotFoundException('Can not find offline mode template');
					}
					ob_start('mb_output_handler');
					ob_implicit_flush(0);
					require CASPAR_APPLICATION_PATH . 'templates/offline.inc.php';
					$content = ob_get_clean();
				}

				// Render output in correct order
				self::getResponse()->renderHeaders();

				if (self::getResponse()->getDecoration() == Response::DECORATE_DEFAULT)
				{
					require \CASPAR_APPLICATION_PATH . 'templates/layout.php';
				}
				else
				{
					// Render header template if any, and store the output in a variable
					if (!self::getRequest()->isAjaxCall() && self::getResponse()->doDecorateHeader())
					{
						Logging::log('decorating with header');
						if (!file_exists(self::getResponse()->getHeaderDecoration()))
						{
							throw new TBGTemplateNotFoundException('Can not find header decoration: '. self::getResponse()->getHeaderDecoration());
						}
						require self::getResponse()->getHeaderDecoration();
					}

					echo $content;

					Logging::log('...done (rendering content)');

					// Render footer template if any
					if (!self::getRequest()->isAjaxCall() && self::getResponse()->doDecorateFooter())
					{
						Logging::log('decorating with footer');
						if (!file_exists(self::getResponse()->getFooterDecoration()))
						{
							throw new TBGTemplateNotFoundException('Can not find footer decoration: '. self::getResponse()->getFooterDecoration());
						}
						require self::getResponse()->getFooterDecoration();
					}

					Logging::log('...done');
				}

				if (self::isDebugMode()) self::getI18n()->addMissingStringsToStringsFile();
				
				return true;
			}
			else
			{
				Logging::log("Cannot find the method {$actionToRunName}() in class {$actionClassName}.", 'core', Logging::LEVEL_FATAL);
				throw new TBGActionNotFoundException("Cannot find the method {$actionToRunName}() in class {$actionClassName}. Make sure the method exists.");
			}
		}

		public static function calculateTimings(&$tbg_summary)
		{
			$load_time = self::getLoadtime();
			if (self::getB2DBInstance() instanceof \b2db\Connection)
			{
				$tbg_summary['db_queries'] = \b2db\Core::getSQLHits();
				$tbg_summary['db_timing'] = \b2db\Core::getSQLTiming();
			}
			$tbg_summary['load_time'] = ($load_time >= 1) ? round($load_time, 2) . ' seconds' : round($load_time * 1000, 1) . 'ms';
			$tbg_summary['scope_id'] = \thebuggenie\core\Context::getScope() instanceof \thebuggenie\core\Scope ? \thebuggenie\core\Context::getScope()->getID() : 'unknown';
			self::ping();
		}
		
		/**
		 * Launches the MVC framework
		 */
		public static function go()
		{
			Logging::log('Dispatching');
			try {
				if (($route = self::getRouting()->getRouteFromUrl(self::getRequest()->getParameter('url', null, false))) /*|| self::isInstallmode()*/) {
//					if (self::isUpgrademode()) {
//						$route = array('module' => 'installation', 'action' => 'upgrade');
//					} elseif (self::isInstallmode()) {
//						$route = array('module' => 'installation', 'action' => 'installIntro');
//					}
					if (self::$_redirect_login) {
						Logging::log('An error occurred setting up the user object, redirecting to login', 'main', Logging::LEVEL_NOTICE);
						self::setMessage('login_message_err', self::geti18n()->__('Please log in'));
						self::getResponse()->headerRedirect(self::getRouting()->generate('login_page'), 403);
					}
					if (self::performAction($route['module'], $route['action'])) {
						//\b2db\Core::closeConnections();
						return true;
					}
				} else {
					self::performAction('main', 'notFound');
				}
			} catch (TBGTemplateNotFoundException $e) {
				\b2db\Core::closeDBLink();
				self::setLoadedAt();
				header("HTTP/1.0 404 Not Found", true, 404);
				tbg_exception($e->getMessage() /*'Template file does not exist for current action'*/, $e);
			} catch (TBGActionNotFoundException $e) {
				\b2db\Core::closeDBLink();
				self::setLoadedAt();
				header("HTTP/1.0 404 Not Found", true, 404);
				tbg_exception('Module action "' . $route['action'] . '" does not exist for module "' . $route['module'] . '"', $e);
			} catch (TBGCSRFFailureException $e) {
				\b2db\Core::closeDBLink();
				self::setLoadedAt();
				self::$_response->setHttpStatus(301);
				$message = $e->getMessage();

				if (self::getRequest()->getRequestedFormat() == 'json') {
					self::$_response->setContentType('application/json');
					$message = json_encode(array('message' => $message));
				}

				self::$_response->renderHeaders();
				echo $message;
			} catch (Exception $e) {
				\b2db\Core::closeDBLink();
				self::setLoadedAt();
				header("HTTP/1.0 404 Not Found", true, 404);
				tbg_exception('An error occured', $e);
			}
		}

		public static function getURLhost()
		{
			return self::getScope()->getCurrentHostname();
		}

		public static function isCLI()
		{
			return (PHP_SAPI == 'cli');
		}

		public static function getCurrentCLIusername()
		{
			$processUser = posix_getpwuid(posix_geteuid());
			return $processUser['name'];
		}

		public static function isDebugMode()
		{
			return self::$debug_mode;
		}

		/**
		 * Displays a nicely formatted exception message
		 *
		 * @param string $title
		 * @param \Exception $exception
		 */
		public static function exceptionHandler($title, $exception = null)
		{
			if (\caspar\core\Caspar::getRequest() instanceof Request && \caspar\core\Caspar::getRequest()->isAjaxCall())
			{
				\caspar\core\Caspar::getResponse()->ajaxResponseText(404, $title);
			}
			$ob_status = ob_get_status();
			if (!empty($ob_status) && $ob_status['status'] != PHP_OUTPUT_HANDLER_END)
			{
				ob_end_clean();
			}

			if (\caspar\core\Caspar::isCLI())
			{
				$trace_elements = null;
				if ($exception instanceof \Exception)
				{
					if ($exception instanceof TBGActionNotFoundException)
					{
						TBGCliCommand::cli_echo("Could not find the specified action\n", 'white', 'bold');
					}
					elseif ($exception instanceof TBGTemplateNotFoundException)
					{
						TBGCliCommand::cli_echo("Could not find the template file for the specified action\n", 'white', 'bold');
					}
					elseif ($exception instanceof \b2db\Exception)
					{
						TBGCliCommand::cli_echo("An exception was thrown in the B2DB framework\n", 'white', 'bold');
					}
					else
					{
						TBGCliCommand::cli_echo("An unhandled exception occurred:\n", 'white', 'bold');
					}
					echo TBGCliCommand::cli_echo($exception->getMessage(), 'red', 'bold')."\n";
					echo "\n";
					TBGCliCommand::cli_echo('Stack trace').":\n";
					$trace_elements = $exception->getTrace();
				}
				else
				{
					if ($exception['code'] == 8)
					{
						TBGCliCommand::cli_echo('The following notice has stopped further execution:', 'white', 'bold');
					}
					else
					{
						TBGCliCommand::cli_echo('The following error occured:', 'white', 'bold');
					}
					echo "\n";
					echo "\n";
					TBGCliCommand::cli_echo($title, 'red', 'bold');
					echo "\n";
					TBGCliCommand::cli_echo("occured in\n");
					TBGCliCommand::cli_echo($exception['file'].', line '.$exception['line'], 'blue', 'bold');
					echo "\n";
					echo "\n";
					TBGCliCommand::cli_echo("Backtrace:\n", 'white', 'bold');
					$trace_elements = debug_backtrace();
				}
				foreach ($trace_elements as $trace_element)
				{
					if (array_key_exists('class', $trace_element))
					{
						TBGCliCommand::cli_echo($trace_element['class'].$trace_element['type'].$trace_element['function'].'()');
					}
					elseif (array_key_exists('function', $trace_element))
					{
						if (in_array($trace_element['function'], array('tbg_error_handler', 'tbg_exception'))) continue;
						TBGCliCommand::cli_echo($trace_element['function'].'()');
					}
					else
					{
						TBGCliCommand::cli_echo('unknown function');
					}
					echo "\n";
					if (array_key_exists('file', $trace_element))
					{
						TBGCliCommand::cli_echo($trace_element['file'].', line '.$trace_element['line'], 'blue', 'bold');
					}
					else
					{
						TBGCliCommand::cli_echo('unknown file', 'red', 'bold');
					}
					echo "\n";
				}
				if (class_exists('\\b2db\\Core'))
				{
					echo "\n";
					TBGCliCommand::cli_echo("SQL queries:\n", 'white', 'bold');
					try
					{
						$cc = 1;
						foreach (\b2db\Core::getSQLHits() as $details)
						{
							TBGCliCommand::cli_echo("(".$cc++.") [");
							$str = ($details['time'] >= 1) ? round($details['time'], 2) . ' seconds' : round($details['time'] * 1000, 1) . 'ms';
							TBGCliCommand::cli_echo($str);
							TBGCliCommand::cli_echo("] from ");
							TBGCliCommand::cli_echo($details['filename'], 'blue');
							TBGCliCommand::cli_echo(", line ");
							TBGCliCommand::cli_echo($details['line'], 'white', 'bold');
							TBGCliCommand::cli_echo(":\n");
							TBGCliCommand::cli_echo("{$details['sql']}\n");
						}
						echo "\n";
					}
					catch (Exception $e)
					{
						TBGCliCommand::cli_echo("Could not generate query list (there may be no database connection)", "red", "bold");
					}
				}
				echo "\n";
				die();
			}

			echo "
			<!DOCTYPE html>
			<html>
			<head>
			<style>"./*
			@import url(\"http://fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,400,700&subset=latin,cyrillic,greek\");
			@import url(\"http://fonts.googleapis.com/css?family=Droid+Sans+Mono&subset=latin,cyrillic,greek\");*/"

			body, td, th { padding: 0px; margin: 0px; background-color: #DFDFDF; font-family: 'Open Sans', sans-serif; font-style: normal; font-weight: normal; text-align: left; font-size: 13px; line-height: 1.3; color: #222;}
			h1 { margin: 5px 0 0 0; font-size: 19px; }
			h2 { margin: 0 0 15px 0; font-size: 16px; }
			h3 { margin: 15px 0 0 0; font-size: 14px; }
			input[type=\"text\"], input[type=\"password\"] { float: left; margin-right: 15px; }
			label { float: left; font-weight: bold; margin-right: 5px; display: block; width: 150px; }
			label span { font-weight: normal; color: #888; }
			.rounded_box {background: transparent; margin:0px;}
			.rounded_box h4 { margin-bottom: 0px; margin-top: 7px; font-size: 14px; }
			.xtop, .xbottom {display:block; background:transparent; font-size:1px;}
			.xb1, .xb2, .xb3, .xb4 {display:block; overflow:hidden;}
			.xb1, .xb2, .xb3 {height:1px;}
			.xb2, .xb3, .xb4 {background:#F9F9F9; border-left:1px solid #CCC; border-right:1px solid #CCC;}
			.xb1 {margin:0 5px; background:#CCC;}
			.xb2 {margin:0 3px; border-width:0 2px;}
			.xb3 {margin:0 2px;}
			.xb4 {height:2px; margin:0 1px;}
			.xboxcontent {display:block; background:#F9F9F9; border:0 solid #CCC; border-width:0 1px; padding: 0 5px 0 5px;}
			.xboxcontent table td.description { padding: 3px 3px 3px 0;}
			.white .xb2, .white .xb3, .white .xb4 { background: #FFF; border-color: #CCC; }
			.white .xb1 { background: #CCC; }
			.white .xboxcontent { background: #FFF; border-color: #CCC; }
			pre { overflow: scroll; padding: 5px; }
			</style>
			<!--[if IE]>
			<style>
			body { background-color: #DFDFDF; font-family: sans-serif; font-size: 13px; }
			</style>
			<![endif]-->
			</head>
			<body>
			<div class=\"rounded_box white\" style=\"margin: 30px auto 0 auto; width: 700px;\">
				<b class=\"xtop\"><b class=\"xb1\"></b><b class=\"xb2\"></b><b class=\"xb3\"></b><b class=\"xb4\"></b></b>
				<div class=\"xboxcontent\" style=\"vertical-align: middle; padding: 10px 10px 10px 15px;\">
				<img style=\"float: left; margin-right: 10px;\" src=\"".\caspar\core\Caspar::getTBGPath()."header.png\"><h1>An error occured in The Bug Genie</h1>";
				echo "<h2>{$title}</h2>";
				$report_description = null;
				if ($exception instanceof \Exception)
				{
					if ($exception instanceof ActionNotFoundException)
					{
						echo "<h3>Could not find the specified action</h3>";
						$report_description = "Could not find the specified action";
					}
					elseif ($exception instanceof TemplateNotFoundException)
					{
						echo "<h3>Could not find the template file for the specified action</h3>";
						$report_description = "Could not find the template file for the specified action";
					}
					elseif ($exception instanceof \b2db\Exception)
					{
						echo "<h3>An exception was thrown in the B2DB framework</h3>";
						$report_description = "An exception was thrown in the B2DB framework";
					}
					else
					{
						echo "<h3>An unhandled exception occurred:</h3>";
						$report_description = "An unhandled exception occurred";
					}
					$report_description .= "\n" . $exception->getMessage();
					echo "<i>".$exception->getMessage()."</i><br>";
					if (class_exists("\\caspar\core\Caspar") && \caspar\core\Caspar::isDebugMode())
					{
						echo "<h3>Stack trace:</h3>
						<ul>";
						//echo '<pre>';var_dump($exception->getTrace());die();
						foreach ($exception->getTrace() as $trace_element)
						{
							echo '<li>';
							if (array_key_exists('class', $trace_element))
							{
								echo '<strong>'.$trace_element['class'].$trace_element['type'].$trace_element['function'].'()</strong><br>';
							}
							elseif (array_key_exists('function', $trace_element))
							{
								if (!in_array($trace_element['function'], array('tbg_error_handler', 'tbg_exception')))
									echo '<strong>'.$trace_element['function'].'()</strong><br>';
							}
							else
							{
								echo '<strong>unknown function</strong><br>';
							}
							if (array_key_exists('file', $trace_element))
							{
								echo '<span style="color: #55F;">'.$trace_element['file'].'</span>, line '.$trace_element['line'];
							}
							else
							{
								echo '<span style="color: #C95;">unknown file</span>';
							}
							echo '</li>';
						}
						echo "</ul>";
					}
				}
				else
				{
					echo '<h3>';
					if ($exception['code'] == 8)
					{
						echo 'The following notice has stopped further execution:';
						$report_description = 'The following notice has stopped further execution: ';
					}
					else
					{
						echo 'The following error occured:';
						$report_description = 'The following error occured: ';
					}
					echo '</h3>';
					$report_description .= $title;
					echo "$title</i><br>
					<h3>Error information:</h3>
					<ul>
						<li>";
						echo '<span style="color: #55F;">'.$exception['file'].'</span>, line '.$exception['line'];
					echo "</li>
					</ul>";
					if (class_exists("\\caspar\core\Caspar") && \caspar\core\Caspar::isDebugMode())
					{
						echo "<h3>Backtrace:</h3>
						<ol>";
						foreach (debug_backtrace() as $trace_element)
						{
							echo '<li>';
							if (array_key_exists('class', $trace_element))
							{
								echo '<strong>'.$trace_element['class'].$trace_element['type'].$trace_element['function'].'()</strong><br>';
							}
							elseif (array_key_exists('function', $trace_element))
							{
								if (in_array($trace_element['function'], array('tbg_error_handler', 'tbg_exception'))) continue;
								echo '<strong>'.$trace_element['function'].'()</strong><br>';
							}
							else
							{
								echo '<strong>unknown function</strong><br>';
							}
							if (array_key_exists('file', $trace_element))
							{
								echo '<span style="color: #55F;">'.$trace_element['file'].'</span>, line '.$trace_element['line'];
							}
							else
							{
								echo '<span style="color: #C95;">unknown file</span>';
							}
							echo '</li>';
						}
						echo "</ol>";
					}
				}
				if (class_exists("\\caspar\core\Caspar") && class_exists("\\caspar\core\Logging") && \caspar\core\Caspar::isDebugMode())
				{
					echo "<h3>Log messages:</h3>";
					foreach (\caspar\core\Logging::getEntries() as $entry)
					{
						$color = \caspar\core\Logging::getCategoryColor($entry['category']);
						$lname = \caspar\core\Logging::getLevelName($entry['level']);
						echo "<div class=\"log_{$entry['category']}\"><strong>{$lname}</strong> <strong style=\"color: #{$color}\">[{$entry['category']}]</strong> <span style=\"color: #555; font-size: 10px; font-style: italic;\">{$entry['time']}</span>&nbsp;&nbsp;{$entry['message']}</div>";
					}
				}
				if (class_exists("\b2db\Core") && \caspar\core\Caspar::isDebugMode())
				{
					echo "<h3>SQL queries:</h3>";
					try
					{
						echo "<ol>";
						foreach (\b2db\Core::getSQLHits() as $details)
						{
							echo "<li>
								<b>
								<span class=\"faded_out dark small\">[";
							echo ($details['time'] >= 1) ? round($details['time'], 2) . ' seconds' : round($details['time'] * 1000, 1) . 'ms';
							echo "]</span> </b> from <b>{$details['filename']}, line {$details['line']}</b>:<br>
								<span style=\"font-size: 12px;\">{$details['sql']}</span>
							</li>";
						}
						echo "</ol>";
					}
					catch (Exception $e)
					{
						echo '<span style="color: red;">Could not generate query list (there may be no database connection)</span>';
					}
				}
				echo "</div>
				<b class=\"xbottom\"><b class=\"xb4\"></b><b class=\"xb3\"></b><b class=\"xb2\"></b><b class=\"xb1\"></b></b>
			</div>";
			if (class_exists("\\caspar\core\Caspar") && !\caspar\core\Caspar::isDebugMode())
			{
				echo "<div style=\"text-align: left; margin: 35px auto 0 auto; width: 700px; font-size: 13px;\">
					<div class=\"rounded_box white\" style=\"margin-bottom: 10px; text-align: right; color: #111;\">
						<b class=\"xtop\"><b class=\"xb1\"></b><b class=\"xb2\"></b><b class=\"xb3\"></b><b class=\"xb4\"></b></b>
						<div class=\"xboxcontent\">
							<div style=\"text-align: left;\">
								<h2 style=\"padding-top: 10px; margin-bottom: 5px;\">Reporting this issue</h2>
								Please report this error in the bug tracker by pressing the button below. This will file an automatic bug report and open it in a new window.<br><br>
								No login is required - but if you have a username and password entering it below will post the issue with your username, allowing you to follow its progress.
							</div>
							<br>
							<form action=\"http://thebuggenie.com/thebuggenie/thebuggenie/issues/new/bugreport\" target=\"_new\" method=\"post\">
								<label for=\"username\">Username <span>(optional)</span></label>
								<input type=\"text\" name=\"tbg3_username\" id=\"username\">
								<br style=\"clear: both;\">
								<label for=\"password\">Password <span>(optional)</span></label>
								<input type=\"password\" name=\"tbg3_password\" id=\"password\">
								<br>
								<input type=\"hidden\" name=\"category_id\" value=\"34\">
								<input type=\"hidden\" name=\"title\" value=\"".htmlentities($title)."\">
								<input type=\"hidden\" name=\"description\" value=\"".htmlentities($report_description)."\n\n\">";
								echo "<input type=\"hidden\" name=\"reproduction_steps\" value=\"PHP_SAPI: ".PHP_SAPI."<br>PHP_VERSION: ".PHP_VERSION."\n\n'''Backtrace''':<br>";
								if ($exception instanceof TBGException)
								{
									foreach ($exception->getTrace() as $trace_element)
									{
										if (array_key_exists('class', $trace_element))
										{
											echo "'''{$trace_element['class']}{$trace_element['type']}{$trace_element['function']}()'''\n";
										}
										elseif (array_key_exists('function', $trace_element))
										{
											if (in_array($trace_element['function'], array('tbg_error_handler', 'tbg_exception'))) continue;
											echo "'''{$trace_element['function']}()'''\n";
										}
										else
										{
											echo "'''unknown function'''\n";
										}
										if (array_key_exists('file', $trace_element))
										{
											echo 'in '.str_replace(THEBUGGENIE_PATH, '<installpath>/', $trace_element['file']).', line '.$trace_element['line'];
										}
										else
										{
											echo 'in an unknown file';
										}
										echo "<br>";
									}
								}
								else
								{
									foreach (debug_backtrace() as $trace_element)
									{
										if (array_key_exists('class', $trace_element))
										{
											echo "'''{$trace_element['class']}{$trace_element['type']}{$trace_element['function']}()'''\n";
										}
										elseif (array_key_exists('function', $trace_element))
										{
											if (in_array($trace_element['function'], array('tbg_error_handler', 'tbg_exception'))) continue;
											echo "'''{$trace_element['function']}()'''\n";
										}
										else
										{
											echo "'''unknown function'''\n";
										}
										if (array_key_exists('file', $trace_element))
										{
											echo 'in '.str_replace(THEBUGGENIE_PATH, '<installpath>/', $trace_element['file']).', line '.$trace_element['line'];
										}
										else
										{
											echo 'in an unknown file';
										}
										echo "<br>";
									}
								}
								echo "\n\n\">";
			echo "
									<input type=\"submit\" value=\"Submit details for reporting\" style=\"font-size: 16px; font-weight: normal; padding: 5px; margin: 10px 0;\">
									<div style=\"font-size: 15px; font-weight: bold; padding: 0 5px 10px 0;\">Thank you for helping us improve The Bug Genie!</div>
								</form>
							</div>
							<b class=\"xbottom\"><b class=\"xb4\"></b><b class=\"xb3\"></b><b class=\"xb2\"></b><b class=\"xb1\"></b></b>
						</div>";
						if (\caspar\core\Logging::isEnabled())
						{
							echo "<h3 style=\"margin-top: 50px;\">Log messages (may contain useful information, but will not be submitted):</h3>";
							foreach (\caspar\core\Logging::getEntries() as $entry)
							{
								$color = \caspar\core\Logging::getCategoryColor($entry['category']);
								$lname = \caspar\core\Logging::getLevelName($entry['level']);
								echo "<div class=\"log_{$entry['category']}\"><strong>{$lname}</strong> <strong style=\"color: #{$color}\">[{$entry['category']}]</strong> <span style=\"color: #555; font-size: 10px; font-style: italic;\">{$entry['time']}</span>&nbsp;&nbsp;{$entry['message']}</div>";
							}
						}
			}
	echo "
				</div>
			</body>
			</html>
			";
			die();
		}

		public static function errorHandler($code, $error, $file, $line_number)
		{
			throw new \Exception($error, $code);
			//tbg_exception($error, array('code' => $code, 'file' => $file, 'line' => $line_number));
		}

		public static function loadConfiguration()
		{
			Logging::log('Loading Caspar configuration');
			self::$_ver_mj = 1;
			self::$_ver_mn = 0;
			self::$_ver_rev = '0-dev';
			self::$_ver_name = 'Ninja';
			if (self::$_configuration = Cache::get(Cache::KEY_SETTINGS) || self::$_configuration = Cache::fileGet(Cache::KEY_SETTINGS)) {
				Logging::log('Using cached configuration');
			} else {
				Logging::log('Configuration not cached. Retrieving configuration from file');
				self::$_configuration = \Spyc::YAMLLoad(\CASPAR_PATH . 'configuration' . \DS . 'caspar.yml', true);
				Cache::add(Cache::KEY_SETTINGS, self::$_configuration);
				Cache::fileAdd(Cache::KEY_SETTINGS, self::$_configuration);
				Logging::log('Configuration loaded');
			}
			if ($b2db_config = Cache::get(Cache::KEY_B2DB_CONFIG) || $b2db_config = Cache::fileGet(Cache::KEY_B2DB_CONFIG)) {
				Logging::log('Using cached b2db settings');
			} else {
				Logging::log('B2DB settings not cached. Retrieving settings from configuration file');
				$b2db_config_file = \CASPAR_PATH . 'configuration' . \DS . 'b2db.yml';
				if (file_exists($b2db_config_file)) {
					$b2db_config = \Spyc::YAMLLoad($b2db_config_file, true);
					Cache::add(Cache::KEY_SETTINGS, $b2db_config);
					Cache::fileAdd(Cache::KEY_SETTINGS, $b2db_config);
				}
				Logging::log('Configuration loaded');
			}
			self::$_configuration['b2db'] = $b2db_config;
		}
		
		public static function loadRoutes()
		{
			if ($routes = Cache::get(Cache::KEY_ROUTES_ALL) || $routes = Cache::fileGet(Cache::KEY_ROUTES_ALL)) {
				Logging::log('Using cached routes');
			} else {
				$routes = array();
				$files = array(\CASPAR_PATH . 'configuration' . \DS . 'routes_premodules.yml' => Cache::KEY_ROUTES_PREMODULES);
				$iterator = new \DirectoryIterator(CASPAR_MODULES_PATH);
				foreach ($iterator as $fileinfo) {
					if ($fileinfo->isDir()) {
						$files[$fileinfo->getPathname() . \DS . 'configuration' . \DS . 'routes.yml'] = Cache::KEY_ROUTES_ALL . '_' . $fileinfo->getBasename();
					}
				}
				$files[\CASPAR_PATH . 'configuration' . \DS . 'routes_postmodules.yml'] = Cache::KEY_ROUTES_POSTMODULES;
				foreach ($files as $filename => $cachekey) {
					if ($route_entries = Cache::get($cachekey) || $route_entries = Cache::fileGet($cachekey)) {
						Logging::log('Using cached route entry for ' . $filename);
					} else {
						$route_entries = \Spyc::YAMLLoad($filename, true);
						foreach ($route_entries as $route => $details) {
							if (is_array($details) && array_key_exists('url', $details))
								$route_entries[$route] = Routing::generateRoute($details);
							else
								unset($route_entries[$route]);
						}
						Cache::add($cachekey, $route_entries);
						Cache::fileAdd($cachekey, $route_entries);
					}
					$routes = array_merge($routes, $route_entries);
				}
			}
			self::$_configuration['routes'] = $routes;
		}

		public static function getB2DBInstance($config = 'default')
		{
			\b2db\Core::setCachePath(\CASPAR_CACHE_PATH);
			if (!array_key_exists($config, self::$_b2db)) {
				$configuration = self::$_configuration['b2db'][$config];
				Logging::log('Initializing B2DB');
				$b2db = \b2db\Core::getInstance($configuration);
				Logging::log('...done (Initializing B2DB)');
				$b2db->connect();

				self::$_b2db[$config] = $b2db;
				Logging::log('...done');
			}
			
			return self::$_b2db[$config];
		}

		public static function initialize()
		{
			// The time the script was loaded
			$starttime = explode(' ', microtime());
			define('NOW', $starttime[1]);
			
			// Set the start time
			self::setLoadStart($starttime[1] + $starttime[0]);

			// Start loading Caspar
			self::autoloadNamespace('b2db', \CASPAR_LIB_PATH . 'b2db' . DS);

			Logging::log('Initializing Caspar framework');
			Logging::log('PHP_SAPI says "' . \PHP_SAPI . '"');
			Logging::log('PHP_VERSION_ID says "' . \PHP_VERSION_ID . '"');
			Logging::log('PHP_VERSION says "' . \PHP_VERSION . '"');

			Logging::log((Cache::isEnabled()) ? 'APC cache is enabled' : 'APC cache is not enabled');

			self::loadConfiguration();
			
			require CASPAR_APPLICATION_PATH . 'bootstrap.inc.php';

			self::loadRoutes();
			
			Logging::log('Caspar framework loaded');
			$event = Event::createNew('caspar/core', 'post_initialize')->trigger();
		}

		public static function isMaintenanceModeEnabled()
		{
			$val = false;
			
			if (array_key_exists('core', self::$_configuration) && array_key_exists('maintenance_mode', self::$_configuration['core'])) {
				$val = self::$_configuration['core']['maintenance_mode'];
			}
			
			return $val;
		}
		
	}
	

<?php

	namespace thebuggenie\core;

	class Context
	{

		/**
		 * The current scope object
		 *
		 * @var TBGScope
		 */
		static protected $_scope;

		/**
		 * The currently selected project, if any
		 * 
		 * @var TBGProject
		 */
		static protected $_selected_project;
		
		/**
		 * The currently selected client, if any
		 * 
		 * @var TBGClient
		 */
		static protected $_selected_client;
		
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

		/**
		 * Find and set the current scope
		 * 
		 * @param integer $scope Specify a scope to set for this request
		 */
		public static function setScope($scope = null)
		{
			Logging::log("Setting current scope");
			if ($scope !== null)
			{
				Logging::log("Setting scope from function parameter");
				self::$_scope = $scope;
				Settings::forceSettingsReload();
				Logging::log("...done (Setting scope from function parameter)");
				return true;
			}
	
			$row = null;
			try
			{
				$hostname = null;
				if (!self::isCLI() && !self::isInstallmode())
				{
					Logging::log("Checking if scope can be set from hostname (".$_SERVER['HTTP_HOST'].")");
					$hostname = $_SERVER['HTTP_HOST'];
				}
				
				if (!self::isUpgrademode() && !self::isInstallmode())
					$row = \thebuggenie\tables\Scopes::getTable()->getByHostnameOrDefault($hostname);
				
				if (!$row instanceof \b2db\Row)
				{
					Logging::log("It couldn't", 'main', Logging::LEVEL_WARNING);
					if (!self::isInstallmode())
						throw new \Exception("The Bug Genie isn't set up to work with this server name.");
					else
						return;
				}
				
				Logging::log("Setting scope from hostname");
				self::$_scope = self::factory()->manufacture("\\caspar\\core\\Scope", $row->get(\thebuggenie\tables\Scopes::ID), $row);
				Settings::forceSettingsReload();
				Settings::loadSettings();
				Logging::log("...done (Setting scope from hostname)");
				return true;
			}
			catch (Exception $e)
			{
				if (self::isCLI())
				{
					Logging::log("Couldn't set up default scope.", 'main', Logging::LEVEL_FATAL);
					throw new \Exception("Could not load default scope. Error message was: " . $e->getMessage());
				}
				elseif (!self::isInstallmode())
				{
					throw $e;
					Logging::log("Couldn't find a scope for hostname {$_SERVER['HTTP_HOST']}", 'main', Logging::LEVEL_FATAL);
					Logging::log($e->getMessage(), 'main', Logging::LEVEL_FATAL);
					throw new \Exception("Could not load scope. This is usually because the scopes table doesn't have a scope for this hostname");
				}
				else
				{
					Logging::log("Couldn't find a scope for hostname {$_SERVER['HTTP_HOST']}, but we're in installmode so continuing anyway");
				}
			}
		}

		/**
		 * Returns current scope
		 *
		 * @return TBGScope
		 */
		public static function getScope()
		{
			return self::$_scope;
		}
		
		/**
		 * Set the currently selected project
		 * 
		 * @param TBGProject $project The project, or null if none
		 */
		public static function setCurrentProject($project)
		{
			self::setBreadcrumb(null);
			self::$_selected_project = $project;
			
			$childbreadcrumbs = array();
			
			if ($project instanceof TBGProject)
			{
				$t = $project;
				
				$hierarchy_breadcrumbs = array();
				$projects_processed = array();
				
				while ($t instanceof TBGProject)
				{
					if (array_key_exists($t->getKey(), $projects_processed))
					{
						// We have a cyclic dependency! Oh no!
						// If this happens, throw an \Exception
						
						throw new \Exception(self::geti18n()->__('A loop has been found in the project heirarchy. Go to project configuration, and alter the subproject setting for this project so that this project is not a subproject of one which is a subproject of this one.'));
						continue;
					}
					else
					{
						$all_projects = array_merge(TBGProject::getAllRootProjects(true), TBGProject::getAllRootProjects(false));
						// If this is a root project, display a list of other root projects, then t is null
						if (!($t->hasParent()) && count($all_projects) > 1)
						{
							$itemsubmenulinks = array();
							foreach ($all_projects as $child)
							{
								$itemsubmenulinks[] = array('url' => self::getRouting()->generate('project_dashboard', array('project_key' => $child->getKey())), 'title' => $child->getName());
							}
							
							$hierarchy_breadcrumbs[] = array($t, $itemsubmenulinks);
							
							$projects_processed[$t->getKey()] = $t;
							
							$t = null;
							continue;
						}
						elseif (!($t->hasParent()))
						{
							$hierarchy_breadcrumbs[] = array($t, null);
							
							$projects_processed[$t->getKey()] = $t;
							
							$t = null;
							continue;
						}
						else
						{
							// What we want to do here is to build a list of the children of the parent unless we are the only one
							$parent = $t->getParent();
							$children = $parent->getChildren();
							
							$itemsubmenulinks = null;
							
							if ($parent->hasChildren() && count($children) > 1)
							{
								$itemsubmenulinks = array();
								foreach ($children as $child)
								{
									$itemsubmenulinks[] = array('url' => self::getRouting()->generate('project_dashboard', array('project_key' => $child->getKey())), 'title' => $child->getName());
								}
							}
							
							$hierarchy_breadcrumbs[] = array($t, $itemsubmenulinks);
							
							$projects_processed[$t->getKey()] = $t;
							
							$t = $parent;
							continue;
						}
					}
				}
				
				$clientsubmenulinks = null;
				if (self::$_selected_project->hasClient())
				{
					$clientsubmenulinks = array();
					foreach (TBGClient::getAll() as $client)
					{
						if ($client->hasAccess())
							$clientsubmenulinks[] = array('url' => self::getRouting()->generate('client_dashboard', array('client_id' => $client->getID())), 'title' => $client->getName());
					}
					self::setCurrentClient(self::$_selected_project->getClient());
				}
				if (mb_strtolower(Settings::getTBGname()) != mb_strtolower($project->getName()) || self::isClientContext())
				{
					self::getResponse()->addBreadcrumb(Settings::getTBGName(), self::getRouting()->generate('home'));
					if (self::isClientContext())
					{
						self::getResponse()->addBreadcrumb(self::getCurrentClient()->getName(), self::getRouting()->generate('client_dashboard', array('client_id' => self::getCurrentClient()->getID())), $clientsubmenulinks);
					}
				}
				
				// Add root breadcrumb first, so reverse order
				$hierarchy_breadcrumbs = array_reverse($hierarchy_breadcrumbs);
				
				foreach ($hierarchy_breadcrumbs as $breadcrumb)
				{
					$class = null;
					if ($breadcrumb[0]->getKey() == self::getCurrentProject()->getKey())
					{
						$class = 'selected_project';
					}
					self::getResponse()->addBreadcrumb($breadcrumb[0]->getName(), self::getRouting()->generate('project_dashboard', array('project_key' => $breadcrumb[0]->getKey())), $breadcrumb[1], $class);					
				}
			}
			else
			{
				self::getResponse()->addBreadcrumb(Settings::getTBGName(), self::getRouting()->generate('home'));
			}
		}
		
		/**
		 * Return the currently selected project if any, or null
		 * 
		 * @return TBGProject
		 */
		public static function getCurrentProject()
		{
			return self::$_selected_project;
		}

		/**
		 * Return whether current project is set
		 *
		 * @return boolean
		 */
		public static function isProjectContext()
		{
			return (bool) (self::getCurrentProject() instanceof TBGProject);
		}
		
		/**
		 * Set the currently selected client
		 * 
		 * @param TBGClient $client The client, or null if none
		 */
		public static function setCurrentClient($client)
		{
			self::$_selected_client = $client;
		}
		
		/**
		 * Return the currently selected client if any, or null
		 * 
		 * @return TBGClient
		 */
		public static function getCurrentClient()
		{
			return self::$_selected_client;
		}

		/**
		 * Return whether current client is set
		 *
		 * @return boolean
		 */
		public static function isClientContext()
		{
			return (bool) (self::getCurrentClient() instanceof TBGClient);
		}

		/**
		 * Set the breadcrumb trail for the current page
		 * 
		 * @param array $breadcrumb 
		 */
		public function setBreadcrumb($breadcrumb)
		{
			$this->_breadcrumb = $breadcrumb;
		}

		/**
		 * Add to the breadcrumb trail for the current page
		 * 
		 * @param string $breadcrumb 
		 * @param string $url[optional]
		 */
		public function addBreadcrumb($breadcrumb, $url = null, $subitems = null, $class = null)
		{
			if ($this->_breadcrumb === null)
				$this->_breadcrumb = array();

			$this->_breadcrumb[] = array('title' => $breadcrumb, 'url' => $url, 'subitems' => $subitems, 'class' => $class);
		}

	}
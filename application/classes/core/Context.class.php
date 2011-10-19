<?php

	namespace thebuggenie\core;
	
	use \caspar\core\Caspar,
		\caspar\core\Cache,
		\caspar\core\Event,
		\caspar\core\Logging;

	class Context
	{

		const CACHE_KEY_MODULES = '_thebuggenie_modules';
		
		/**
		 * The current scope object
		 *
		 * @var Scope
		 */
		static protected $_scope;

		/**
		 * The currently selected project, if any
		 * 
		 * @Class \thebuggenie\entities\Project
		 */
		static protected $_selected_project;
		
		/**
		 * The currently selected client, if any
		 * 
		 * @Class \thebuggenie\entities\Client
		 */
		static protected $_selected_client;
		
		/**
		 * Whether we're in installmode or not
		 * 
		 * @var boolean
		 */
		static protected $_installmode = false;
		
		/**
		 * Whether we're in upgrademode or not
		 * 
		 * @var boolean
		 */
		static protected $_upgrademode = false;
		
		/**
		 * Outdated modules
		 * 
		 * @var array
		 */
		static protected $_outdated_modules;

		/**
		 * List of modules 
		 * 
		 * @var array
		 */
		static protected $_modules;
		
		/**
		 * List of permissions
		 *  
		 * @var array
		 */
		static protected $_permissions = array();
		
		/**
		 * List of available permissions
		 * 
		 * @var array
		 */
		static protected $_available_permissions;
		
		/**
		 * Initialize the context
		 *
		 * @return null
		 */
		public static function initialize()
		{
			try
			{
				self::checkInstallMode();
				self::setScope();

				if (!self::$_installmode)
				{
					self::loadModules();
					Event::listen('core', 'post_login', array('\\thebuggenie\\core\\Context', 'listenCorePostLogin'));
					Event::listen('core', 'post_loaduser', array('\\thebuggenie\\core\\Context', 'listenCorePostLoadUser'));
					Event::listen('core', 'loadTemplateVariables', array('\\thebuggenie\\core\\Context', 'listenCoreLoadTemplateVariables'));
				}
				else
				{
					self::$_modules = array();
				}

//				var_dump(self::getUser());die();
//				self::setupI18n();
//
//				if (!is_writable(THEBUGGENIE_CORE_PATH . DIRECTORY_SEPARATOR . 'cache'))
//				{
//					throw new \Exception(self::geti18n()->__('The cache directory is not writable. Please correct the permissions of core/cache, and try again'));
//				}
//
//				self::loadPostModuleRoutes();
				Logging::log('...done initializing');
			}
			catch (Exception $e)
			{
				if (!self::isCLI() && !self::isInstallmode())
					throw $e;
			}
		}
		
		public static function listenCorePostLoadUser(Event $event)
		{
			$event->getSubject()->setTimezone(Settings::getUserTimezone());
			$event->getSubject()->setLanguage(Settings::getUserLanguage());
			$event->getSubject()->save();
			if (!($event->getSubject()->getGroup() instanceof \thebuggenie\entities\Group))
			{
				throw new \Exception('This user account belongs to a group that does not exist anymore. <br>Please contact the system administrator.');
			}
		}
		
		public static function listenCorePostLogin(Event $event)
		{
			self::cacheAllPermissions();
		}

		public static function checkInstallMode()
		{
			if (!is_readable(THEBUGGENIE_PATH . 'installed'))
				self::$_installmode = true;
			elseif (is_readable(THEBUGGENIE_PATH . 'upgrade'))
				self::$_installmode = self::$_upgrademode = true;
			elseif (!Caspar::getB2DBInstance() instanceof \b2db\Connection)
				throw new \Exception("The Bug Genie seems installed, but B2DB isn't configured. This usually indicates an error with the installation. Try removing the file ".THEBUGGENIE_PATH."installed and try again.");
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
				if (!Caspar::isCLI() && !self::isInstallmode())
				{
					Logging::log("Checking if scope can be set from hostname (".$_SERVER['HTTP_HOST'].")");
					$hostname = $_SERVER['HTTP_HOST'];
				}
				
				if (!self::isUpgrademode() && !self::isInstallmode())
					$row = Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\Scopes')->getByHostnameOrDefault($hostname);
				
				if (!$row instanceof \b2db\Row)
				{
					Logging::log("It couldn't", 'main', Logging::LEVEL_WARNING);
					if (!self::isInstallmode())
						throw new \Exception("The Bug Genie isn't set up to work with this server name.");
					else
						return;
				}
				
				Logging::log("Setting scope from hostname");
				self::$_scope = Caspar::factory()->manufacture("\\thebuggenie\\core\\Scope", $row->get(\thebuggenie\tables\Scopes::ID), $row);
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
		 * @return Scope
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
			Caspar::getResponse()->setBreadcrumb(null);
			self::$_selected_project = $project;
			
			$childbreadcrumbs = array();
			
			if ($project instanceof \thebuggenie\entities\Project)
			{
				$t = $project;
				
				$hierarchy_breadcrumbs = array();
				$projects_processed = array();
				
				while ($t instanceof \thebuggenie\entities\Project)
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
						$all_projects = array_merge(\thebuggenie\entities\Project::getAllRootProjects(true), \thebuggenie\entities\Project::getAllRootProjects(false));
						// If this is a root project, display a list of other root projects, then t is null
						if (!($t->hasParent()) && count($all_projects) > 1)
						{
							$itemsubmenulinks = array();
							foreach ($all_projects as $child)
							{
								$itemsubmenulinks[] = array('url' => Caspar::getRouting()->generate('project_dashboard', array('project_key' => $child->getKey())), 'title' => $child->getName());
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
									$itemsubmenulinks[] = array('url' => Caspar::getRouting()->generate('project_dashboard', array('project_key' => $child->getKey())), 'title' => $child->getName());
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
							$clientsubmenulinks[] = array('url' => Caspar::getRouting()->generate('client_dashboard', array('client_id' => $client->getID())), 'title' => $client->getName());
					}
					self::setCurrentClient(self::$_selected_project->getClient());
				}
				if (mb_strtolower(Settings::getTBGname()) != mb_strtolower($project->getName()) || self::isClientContext())
				{
					Caspar::getResponse()->addBreadcrumb(Settings::getTBGName(), Caspar::getRouting()->generate('home'));
					if (self::isClientContext())
					{
						Caspar::getResponse()->addBreadcrumb(self::getCurrentClient()->getName(), Caspar::getRouting()->generate('client_dashboard', array('client_id' => self::getCurrentClient()->getID())), $clientsubmenulinks);
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
					Caspar::getResponse()->addBreadcrumb($breadcrumb[0]->getName(), Caspar::getRouting()->generate('project_dashboard', array('project_key' => $breadcrumb[0]->getKey())), $breadcrumb[1], $class);					
				}
			}
			else
			{
				Caspar::getResponse()->addBreadcrumb(Settings::getTBGName(), Caspar::getRouting()->generate('home'));
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
			return (bool) (self::getCurrentProject() instanceof \thebuggenie\entities\Project);
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
		 * Returns whether or not we're in install mode
		 * 
		 * @return boolean
		 */
		public static function isInstallmode()
		{
			return self::$_installmode;
		}
		
		/**
		 * Returns whether or not we're in upgrade mode
		 * 
		 * @return boolean
		 */
		public static function isUpgrademode()
		{
			return self::$_upgrademode;
		}

		/**
		 * Loads and initializes all modules
		 */
		public static function loadModules()
		{
			Logging::log('Loading modules');
			if (self::$_modules === null)
			{
				self::$_modules = array();
				if (self::isInstallmode()) return;

				if (!Cache::has(self::CACHE_KEY_MODULES))
				{
					$modules = array();

					Logging::log('getting modules from database');

					if ($res = Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\Modules')->getAll())
					{
						while ($row = $res->getNextRow())
						{
							$module_name = $row->get(\thebuggenie\tables\Modules::MODULE_NAME);
							$classname = "\\application\\modules\\{$module_name}\\" . ucfirst($module_name);
							if (class_exists($classname))
							{
								self::$_modules[$module_name] = new $classname($row->get(\thebuggenie\tables\Modules::ID), $row);
							}
							else
							{
								Logging::log('Cannot load module "' . $module_name . '" as class "' . $classname . '", the class is not defined in the classpaths.', 'modules', Logging::LEVEL_WARNING_RISK);
								Logging::log('Removing module "' . $module_name . '" as it cannot be loaded', 'modules', Logging::LEVEL_NOTICE);
								Module::removeModule($row->get(\thebuggenie\tables\Modules::ID));
							}
						}
					}
					Cache::add(self::CACHE_KEY_MODULES, self::$_modules);
					Logging::log('done (setting up module objects)');
				}
				else
				{
					Logging::log('using cached modules');
					self::$_modules = Cache::get(self::CACHE_KEY_MODULES);
					Logging::log('done (using cached modules)');
				}

				Logging::log('initializing modules');
				if (!empty(self::$_modules))
				{
					foreach (self::$_modules as $module_name => $module)
					{
						$module->initialize();
					}
					Logging::log('done (initializing modules)');
				}
				else
				{
					Logging::log('no modules found');
				}
			}
			else
			{
				Logging::log('Modules already loaded', 'core', Logging::LEVEL_FATAL);
			}
			Logging::log('...done');
		}

		/**
		 * Return all permissions available
		 * 
		 * @param string $type
		 * @param integer $uid
		 * @param integer $tid
		 * @param integer $gid
		 * @param integer $target_id[optional]
		 * @param boolean $all[optional]
		 *
		 * @return array
		 */
		public static function getAllPermissions($type, $uid, $tid, $gid, $target_id = null, $all = false)
		{
			$crit = new \b2db\Criteria();
			$crit->addWhere(\thebuggenie\tables\Permissions::SCOPE, self::getScope()->getID());
			$crit->addWhere(\thebuggenie\tables\Permissions::PERMISSION_TYPE, $type);

			if (($uid + $tid + $gid) == 0 && !$all)
			{
				$crit->addWhere(\thebuggenie\tables\Permissions::UID, $uid);
				$crit->addWhere(\thebuggenie\tables\Permissions::TID, $tid);
				$crit->addWhere(\thebuggenie\tables\Permissions::GID, $gid);
			}
			else
			{
				switch (true)
				{
					case ($uid != 0):
						$crit->addWhere(\thebuggenie\tables\Permissions::UID, $uid);
					case ($tid != 0):
						$crit->addWhere(\thebuggenie\tables\Permissions::TID, $tid);
					case ($gid != 0):
						$crit->addWhere(\thebuggenie\tables\Permissions::GID, $gid);
				}
			}
			if ($target_id != null)
			{
				$crit->addWhere(\thebuggenie\tables\Permissions::TARGET_ID, $target_id);
			}
	
			$permissions = array();

			if ($res = Caspar::getB2DBInstance()->getTable('\thebuggenie\tables\Permissions')->doSelect($crit))
			{
				while ($row = $res->getNextRow())
				{
					$permissions[] = array('p_type' => $row->get(\thebuggenie\tables\Permissions::PERMISSION_TYPE), 'target_id' => $row->get(\thebuggenie\tables\Permissions::TARGET_ID), 'allowed' => $row->get(\thebuggenie\tables\Permissions::ALLOWED), 'uid' => $row->get(\thebuggenie\tables\Permissions::UID), 'gid' => $row->get(\thebuggenie\tables\Permissions::GID), 'tid' => $row->get(\thebuggenie\tables\Permissions::TID), 'id' => $row->get(\thebuggenie\tables\Permissions::ID));
				}
			}
	
			return $permissions;
		}
		
		/**
		 * Cache all permissions
		 */
		public static function cacheAllPermissions()
		{
			Logging::log('caches permissions');
			self::$_permissions = array();
			
			if ($permissions = Cache::get('permissions'))
			{
				self::$_permissions = $permissions;
				Logging::log('Using cached permissions');
			}
			else
			{
				if (!$permissions = Cache::fileGet(Cache::KEY_PERMISSIONS_CACHE))
				{
					Logging::log('starting to cache access permissions');
					if ($res = Caspar::getB2DBInstance()->getTable('\thebuggenie\tables\Permissions')->getAll())
					{
						while ($row = $res->getNextRow())
						{
							if (!array_key_exists($row->get(\thebuggenie\tables\Permissions::MODULE), self::$_permissions))
							{
								self::$_permissions[$row->get(\thebuggenie\tables\Permissions::MODULE)] = array();
							}
							if (!array_key_exists($row->get(\thebuggenie\tables\Permissions::PERMISSION_TYPE), self::$_permissions[$row->get(\thebuggenie\tables\Permissions::MODULE)]))
							{
								self::$_permissions[$row->get(\thebuggenie\tables\Permissions::MODULE)][$row->get(\thebuggenie\tables\Permissions::PERMISSION_TYPE)] = array();
							}
							if (!array_key_exists($row->get(\thebuggenie\tables\Permissions::TARGET_ID), self::$_permissions[$row->get(\thebuggenie\tables\Permissions::MODULE)][$row->get(\thebuggenie\tables\Permissions::PERMISSION_TYPE)]))
							{
								self::$_permissions[$row->get(\thebuggenie\tables\Permissions::MODULE)][$row->get(\thebuggenie\tables\Permissions::PERMISSION_TYPE)][$row->get(\thebuggenie\tables\Permissions::TARGET_ID)] = array();
							}
							self::$_permissions[$row->get(\thebuggenie\tables\Permissions::MODULE)][$row->get(\thebuggenie\tables\Permissions::PERMISSION_TYPE)][$row->get(\thebuggenie\tables\Permissions::TARGET_ID)][] = array('uid' => $row->get(\thebuggenie\tables\Permissions::UID), 'gid' => $row->get(\thebuggenie\tables\Permissions::GID), 'tid' => $row->get(\thebuggenie\tables\Permissions::TID), 'allowed' => (bool) $row->get(\thebuggenie\tables\Permissions::ALLOWED));
						}
					}
					Logging::log('done (starting to cache access permissions)');
					Cache::fileAdd(Cache::KEY_PERMISSIONS_CACHE, self::$_permissions);
				}
				else
				{
					self::$_permissions = $permissions;
				}
				Cache::add('permissions', self::$_permissions);
			}
			Logging::log('...cached');
		}

		public static function deleteModulePermissions($module_name, $scope)
		{
			if ($scope == self::getScope()->getID())
			{
				if (array_key_exists($module_name, self::$_permissions))
				{
					unset(self::$_permissions[$module_name]);
				}
			}
			\caspar\core\Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\Permissions')->deleteModulePermissions($module_name, $scope);
		}

		/**
		 * Cache a permission
		 * 
		 * @param array $perm_cache
		 */
		public static function cachePermission($perm_cache)
		{
			self::$_permissions[] = $perm_cache; 
		}

		/**
		 * Remove a saved permission
		 * 
		 * @param string $permission_type The permission type 
		 * @param mixed $target_id The target id
		 * @param string $module The name of the module for which the permission is valid
		 * @param integer $uid The user id for which the permission is valid, 0 for none
		 * @param integer $gid The group id for which the permission is valid, 0 for none
		 * @param integer $tid The team id for which the permission is valid, 0 for none
		 * @param boolean $recache Whether to recache after clearing this permission
		 * @param integer $scope A specified scope if not the default
		 */
		public static function removePermission($permission_type, $target_id, $module, $uid, $gid, $tid, $recache = true, $scope = null)
		{
			if ($scope === null) $scope = self::getScope()->getID();
			
			Caspar::getB2DBInstance()->getTable('\thebuggenie\tables\Permissions')->removeSavedPermission($uid, $gid, $tid, $module, $permission_type, $target_id, $scope);
			
			if ($recache) self::cacheAllPermissions();
		}

		/**
		 * Save a permission setting
		 * 
		 * @param string $permission_type The permission type 
		 * @param mixed $target_id The target id
		 * @param string $module The name of the module for which the permission is valid
		 * @param integer $uid The user id for which the permission is valid, 0 for none
		 * @param integer $gid The group id for which the permission is valid, 0 for none
		 * @param integer $tid The team id for which the permission is valid, 0 for none
		 * @param boolean $allowed Allowed or not
		 * @param integer $scope[optional] A specified scope if not the default
		 */
		public static function setPermission($permission_type, $target_id, $module, $uid, $gid, $tid, $allowed, $scope = null)
		{
			if ($scope === null) $scope = self::getScope()->getID();
			
			self::removePermission($permission_type, $target_id, $module, $uid, $gid, $tid, false, $scope);
			\caspar\core\Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\Permissions')->setPermission($uid, $gid, $tid, $allowed, $module, $permission_type, $target_id, $scope);
			
			self::cacheAllPermissions();
		}

		public static function isPermissionSet($type, $permission_key, $id, $target_id = 0, $module_name = 'core')
		{
			if (array_key_exists($module_name, self::$_permissions) &&
				array_key_exists($permission_key, self::$_permissions[$module_name]) &&
				array_key_exists($target_id, self::$_permissions[$module_name][$permission_key]))
			{
				if ($type == 'group')
				{
					foreach (self::$_permissions[$module_name][$permission_key][$target_id] as $permission)
					{
						if ($permission['gid'] == $id) return $permission['allowed'];
					}
				}
				if ($type == 'user')
				{
					foreach (self::$_permissions[$module_name][$permission_key][$target_id] as $permission)
					{
						if ($permission['uid'] == $id) return $permission['allowed'];
					}
				}
				if ($type == 'team')
				{
					foreach (self::$_permissions[$module_name][$permission_key][$target_id] as $permission)
					{
						if ($permission['tid'] == $id) return $permission['allowed'];
					}
				}
				if ($type == 'everyone')
				{
					foreach (self::$_permissions[$module_name][$permission_key][$target_id] as $permission)
					{
						if ($permission['uid'] + $permission['gid'] + $permission['tid'] == 0)
						{
							return $permission['allowed'];
						}
					}
				}
			}
			return null;
		}
		
		protected static function _permissionsCheck($permissions, $uid, $gid, $tid)
		{
			try
			{
				if ($uid != 0 || $gid != 0 || $tid != 0)
				{
					if ($uid != 0)
					{
						foreach ($permissions as $key => $permission)
						{
							if (!array_key_exists('uid', $permission))
							{
								foreach ($permission as $pkey => $pp)
								{
									if ($pp['uid'] == $uid) {
										return $pp['allowed'];
									}
								}
							}
							elseif ($permission['uid'] == $uid) return $permission['allowed'];
						}
					}
	
					if (is_array($tid) || $tid != 0)
					{
						foreach ($permissions as $key => $permission)
						{
							if (!array_key_exists('tid', $permission))
							{
								foreach ($permission as $pkey => $pp)
								{
									if ((is_array($tid) && in_array($pp['tid'], array_keys($tid))) || $pp['tid'] == $tid)
									{
										return $pp['allowed'];
									}
								}
							}
							elseif ((is_array($tid) && in_array($permission['tid'], array_keys($tid))) || $permission['tid'] == $tid)
							{
								return $permission['allowed'];
							}
						}
					}
	
					if ($gid != 0)
					{
						foreach ($permissions as $key => $permission)
						{
							if (!array_key_exists('gid', $permission))
							{
								foreach ($permission as $pkey => $pp)
								{
									if ($pp['gid'] == $gid) return $pp['allowed'];
								}
							}
							elseif ($permission['gid'] == $gid) return $permission['allowed'];
						}
					}
				}
	
				foreach ($permissions as $key => $permission)
				{
					if (!array_key_exists('uid', $permission))
					{
						foreach ($permission as $pkey => $pp)
						{
							if ($pp['uid'] + $pp['gid'] + $pp['tid'] == 0) return $pp['allowed'];
						}
					}
					elseif ($permission['uid'] + $permission['gid'] + $permission['tid'] == 0) return $permission['allowed'];
				}
			}
			catch (Exception $e) { }
			
			return null;
		}
	
		/**
		 * Check to see if a specified user/group/team has access
		 * 
		 * @param string $permission_type The permission type 
		 * @param integer $uid The user id for which the permission is valid, 0 for all
		 * @param integer $gid The group id for which the permission is valid, 0 for all
		 * @param integer $tid The team id for which the permission is valid, 0 for all
		 * @param integer $target_id[optional] The target id
		 * @param string $module_name[optional] The name of the module for which the permission is valid
		 * @param boolean $explicit[optional] whether to check for an explicit permission and return false if not set
		 * @param boolean $permissive[optional] whether to return false or true when explicit fails
		 * 
		 * @return unknown_type
		 */
		public static function checkPermission($permission_type, $uid, $gid, $tid, $target_id = 0, $module_name = 'core', $explicit = false, $permissive = false)
		{
			$uid = (int) $uid;
			$gid = (int) $gid;
			if (array_key_exists($module_name, self::$_permissions) &&
				array_key_exists($permission_type, self::$_permissions[$module_name]) &&
				(array_key_exists($target_id, self::$_permissions[$module_name][$permission_type]) || $target_id === null))
			{
				if (array_key_exists(0, self::$_permissions[$module_name][$permission_type]))
				{
					$permissions_notarget = self::$_permissions[$module_name][$permission_type][0];
				}
				
				$permissions_target = (array_key_exists($target_id, self::$_permissions[$module_name][$permission_type])) ? self::$_permissions[$module_name][$permission_type][$target_id] : array();
				
				$retval = self::_permissionsCheck($permissions_target, $uid, $gid, $tid);
				
				if (array_key_exists(0, self::$_permissions[$module_name][$permission_type]))
				{
					$retval = ($retval !== null) ? $retval : self::_permissionsCheck($permissions_notarget, $uid, $gid, $tid);
				}
				
				if ($retval !== null) return $retval;
			}

			if ($explicit) return $permissive;
			
			return Settings::isPermissive();
		}
		
		protected static function _cacheAvailablePermissions()
		{
			if (self::$_available_permissions === null)
			{
				$i18n = self::getI18n();
				self::$_available_permissions = array('user' => array(), 'general' => array(), 'project' => array());

				self::$_available_permissions['user']['canseeallissues'] = array('description' => $i18n->__('Can see issues reported by other users'), 'mode' => 'permissive');
				self::$_available_permissions['user']['canseegroupissues'] = array('description' => $i18n->__('Can see issues reported by users in the same group'), 'mode' => 'permissive');
				self::$_available_permissions['configuration']['cansaveconfig'] = array('description' => $i18n->__('Can access the configuration page and edit all configuration'), 'details' => array());
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('canviewconfig' => array('description' => $i18n->__('Read-only access: "Settings" configuration page'), 'target_id' => 12));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('cansaveconfig' => array('description' => $i18n->__('Read + write access: "Settings" configuration page'), 'target_id' => 12));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('canviewconfig' => array('description' => $i18n->__('Read-only access: "Permissions" configuration page'), 'target_id' => 5));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('cansaveconfig' => array('description' => $i18n->__('Read + write access: "Permissions" configuration page'), 'target_id' => 5));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('canviewconfig' => array('description' => $i18n->__('Read-only access: "Uploads" configuration page'), 'target_id' => 3));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('cansaveconfig' => array('description' => $i18n->__('Read + write access: "Uploads" configuration page'), 'target_id' => 3));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('canviewconfig' => array('description' => $i18n->__('Read-only access: "Scopes" configuration page'), 'target_id' => 14));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('cansaveconfig' => array('description' => $i18n->__('Read + write access: "Scopes" configuration page'), 'target_id' => 14));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('canviewconfig' => array('description' => $i18n->__('Read-only access: "Import" configuration page'), 'target_id' => 16));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('cansaveconfig' => array('description' => $i18n->__('Read + write access: "Import" configuration page'), 'target_id' => 16));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('canviewconfig' => array('description' => $i18n->__('Read-only access: "Projects" configuration page'), 'target_id' => 10));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('cansaveconfig' => array('description' => $i18n->__('Read + write access: "Projects" configuration page'), 'target_id' => 10));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('canviewconfig' => array('description' => $i18n->__('Read-only access: "Issue types" configuration page'), 'target_id' => 6));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('cansaveconfig' => array('description' => $i18n->__('Read + write access: "Issue types" configuration page'), 'target_id' => 6));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('canviewconfig' => array('description' => $i18n->__('Read-only access: "Issue fields" configuration page'), 'target_id' => 4));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('cansaveconfig' => array('description' => $i18n->__('Read + write access: "Issue fields" configuration page'), 'target_id' => 4));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('canviewconfig' => array('description' => $i18n->__('Read-only access: "Users, teams and groups" configuration page'), 'target_id' => 2));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('cansaveconfig' => array('description' => $i18n->__('Read + write access: "Users, teams and groups" configuration page'), 'target_id' => 2));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('canviewconfig' => array('description' => $i18n->__('Read-only access: "Modules" and any module configuration page'), 'target_id' => 15));
				self::$_available_permissions['configuration']['cansaveconfig']['details'][] = array('cansaveconfig' => array('description' => $i18n->__('Read + write access: "Modules" configuration page and any modules'), 'target_id' => 15));
				self::$_available_permissions['general']['canfindissuesandsavesearches'] = array('description' => $i18n->__('Can search for issues and create saved searches'), 'details' => array());
				self::$_available_permissions['general']['canfindissuesandsavesearches']['details']['canfindissues'] = array('description' => $i18n->__('Can search for issues'));
				//self::$_available_permissions['general']['canfindissuesandsavesearches']['details']['cancreatesavedsearches'] = array('description' => $i18n->__('Can create saved searches'));
				self::$_available_permissions['general']['canfindissuesandsavesearches']['details']['cancreatepublicsearches'] = array('description' => $i18n->__('Can create saved searches that are public'));
				self::$_available_permissions['general']['caneditmainmenu'] = array('description' => $i18n->__('Can edit main menu'));
				self::$_available_permissions['pages']['page_home_access'] = array('description' => $i18n->__('Can access the frontpage'));
				self::$_available_permissions['pages']['page_dashboard_access'] = array('description' => $i18n->__('Can access the user dashboard'));
				self::$_available_permissions['pages']['page_search_access'] = array('description' => $i18n->__('Can access the search page'));
				self::$_available_permissions['pages']['page_about_access'] = array('description' => $i18n->__('Can access the "About" page'));
				self::$_available_permissions['pages']['page_account_access'] = array('description' => $i18n->__('Can access the "My account" page'));
				self::$_available_permissions['pages']['page_teamlist_access'] = array('description' => $i18n->__('Can see list of teams in header menu'));
				self::$_available_permissions['pages']['page_clientlist_access'] = array('description' => $i18n->__('Can access all clients'));
				self::$_available_permissions['project_pages']['page_project_allpages_access'] = array('description' => $i18n->__('Can access all project pages'), 'details' => array());
				self::$_available_permissions['project_pages']['page_project_allpages_access']['details']['page_project_dashboard_access'] = array('description' => $i18n->__('Can access the project dashboard'));
				self::$_available_permissions['project_pages']['page_project_allpages_access']['details']['page_project_planning_access'] = array('description' => $i18n->__('Can access the project planning page'));
				self::$_available_permissions['project_pages']['page_project_allpages_access']['details']['page_project_scrum_access'] = array('description' => $i18n->__('Can access the project scrum page'));
				self::$_available_permissions['project_pages']['page_project_allpages_access']['details']['page_project_issues_access'] = array('description' => $i18n->__('Can access the project issues search page'));
				self::$_available_permissions['project_pages']['page_project_allpages_access']['details']['page_project_roadmap_access'] = array('description' => $i18n->__('Can access the project roadmap page'));
				self::$_available_permissions['project_pages']['page_project_allpages_access']['details']['page_project_team_access'] = array('description' => $i18n->__('Can access the project team page'));
				self::$_available_permissions['project_pages']['page_project_allpages_access']['details']['page_project_statistics_access'] = array('description' => $i18n->__('Can access the project statistics page'));
				self::$_available_permissions['project_pages']['page_project_allpages_access']['details']['page_project_timeline_access'] = array('description' => $i18n->__('Can access the project timeline page'));
				self::$_available_permissions['project']['canseeproject'] = array('description' => $i18n->__('Can see that project exists'));
				self::$_available_permissions['project']['canseeprojecthierarchy'] = array('description' => $i18n->__('Can see complete project hierarchy'));
				self::$_available_permissions['project']['canseeprojecthierarchy']['details']['canseeallprojecteditions'] = array('description' => $i18n->__('Can see all editions'));
				self::$_available_permissions['project']['canseeprojecthierarchy']['details']['canseeallprojectcomponents'] = array('description' => $i18n->__('Can see all components'));
				self::$_available_permissions['project']['canseeprojecthierarchy']['details']['canseeallprojectbuilds'] = array('description' => $i18n->__('Can see all releases'));
				self::$_available_permissions['project']['canseeprojecthierarchy']['details']['canseeallprojectmilestones'] = array('description' => $i18n->__('Can see all milestones'));
				self::$_available_permissions['project']['candoscrumplanning'] = array('description' => $i18n->__('Can manage stories, tasks, sprints and backlog on the sprint planning page'), 'details' => array());
				self::$_available_permissions['project']['candoscrumplanning']['details']['canaddscrumuserstories'] = array('description' => $i18n->__('Can add new user stories to the backlog on the sprint planning page'));
				self::$_available_permissions['project']['candoscrumplanning']['details']['candoscrumplanning_backlog'] = array('description' => $i18n->__('Can manage the backlog on the sprint planning page'));
				self::$_available_permissions['project']['candoscrumplanning']['details']['canaddscrumsprints'] = array('description' => $i18n->__('Can add sprints on the sprint planning page'));
				self::$_available_permissions['project']['candoscrumplanning']['details']['canassignscrumuserstoriestosprints'] = array('description' => $i18n->__('Can add stories to sprints on the sprint planning page'));
				self::$_available_permissions['project']['canmanageproject'] = array('description' => $i18n->__('Can manage project'));
				self::$_available_permissions['project']['canmanageproject']['details']['canmanageprojectreleases'] = array('description' => $i18n->__('Can manage project releases and components'));
				self::$_available_permissions['project']['canmanageproject']['details']['caneditprojectdetails'] = array('description' => $i18n->__('Can edit project details and settings'));
				self::$_available_permissions['edition']['canseeedition'] = array('description' => $i18n->__('Can see this edition'));
				self::$_available_permissions['component']['canseecomponent'] = array('description' => $i18n->__('Can see this component'));
				self::$_available_permissions['build']['canseebuild'] = array('description' => $i18n->__('Can see this release'));
				self::$_available_permissions['milestone']['canseemilestone'] = array('description' => $i18n->__('Can see this milestone'));
				self::$_available_permissions['issues']['canvoteforissues'] = array('description' => $i18n->__('Can vote for issues'));
				self::$_available_permissions['issues']['canlockandeditlockedissues'] = array('description' => $i18n->__('Can lock and edit locked issues'));
				self::$_available_permissions['issues']['cancreateandeditissues'] = array('description' => $i18n->__('Can create issues, edit basic information on issues reported by the user and close/re-open them'), 'details' => array());
				self::$_available_permissions['issues']['cancreateandeditissues']['details']['cancreateissues'] = array('description' => $i18n->__('Can create new issues'), 'details' => array());
				self::$_available_permissions['issues']['cancreateandeditissues']['details']['caneditissuebasicown'] = array('description' => $i18n->__('Can edit title and description on issues reported by the user'), 'details' => array());
				self::$_available_permissions['issues']['cancreateandeditissues']['details']['caneditissuebasicown']['details']['caneditissuetitleown'] = array('description' => $i18n->__('Can edit issue title on issues reported by the user'));
				self::$_available_permissions['issues']['cancreateandeditissues']['details']['caneditissuebasicown']['details']['caneditissuedescriptionown'] = array('description' => $i18n->__('Can edit issue description on issues reported by the user'));
				self::$_available_permissions['issues']['cancreateandeditissues']['details']['caneditissuebasicown']['details']['caneditissuereproduction_stepsown'] = array('description' => $i18n->__('Can edit steps to reproduce on issues reported by the user'));
				self::$_available_permissions['issues']['cancreateandeditissues']['details']['canclosereopenissuesown'] = array('description' => $i18n->__('Can close and reopen issues reported by the user'), 'details' => array());
				self::$_available_permissions['issues']['cancreateandeditissues']['details']['canclosereopenissuesown']['details']['cancloseissuesown'] = array('description' => $i18n->__('Can close issues reported by the user'));
				self::$_available_permissions['issues']['cancreateandeditissues']['details']['canclosereopenissuesown']['details']['canreopenissuesown'] = array('description' => $i18n->__('Can re-open issues reported by the user'));
				self::$_available_permissions['issues']['caneditissue'] = array('description' => $i18n->__('Can delete, close, reopen and update any issue details and progress'), 'details' => array());
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissuebasic'] = array('description' => $i18n->__('Can edit title and description on any issues'), 'details' => array());
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissuebasic']['details']['caneditissuetitle'] = array('description' => $i18n->__('Can edit any issue title'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissuebasic']['details']['caneditissuedescription'] = array('description' => $i18n->__('Can edit any issue description'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissuebasic']['details']['caneditissuereproduction_steps'] = array('description' => $i18n->__('Can edit any issue steps to reproduce'));
				self::$_available_permissions['issues']['caneditissue']['details']['candeleteissues'] = array('description' => $i18n->__('Can delete issues'));
				self::$_available_permissions['issues']['caneditissue']['details']['canclosereopenissues'] = array('description' => $i18n->__('Can close any issues'));
				self::$_available_permissions['issues']['caneditissue']['details']['canclosereopenissues']['details']['cancloseissues'] = array('description' => $i18n->__('Can close any issues'));
				self::$_available_permissions['issues']['caneditissue']['details']['canclosereopenissues']['details']['canreopenissues'] = array('description' => $i18n->__('Can re-open any issues'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissueposted_by'] = array('description' => $i18n->__('Can edit issue posted by'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissueowned_by'] = array('description' => $i18n->__('Can edit issue owned by'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissueassigned_to'] = array('description' => $i18n->__('Can edit issue assigned_to'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissuestatus'] = array('description' => $i18n->__('Can edit issue status'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissuecategory'] = array('description' => $i18n->__('Can edit issue category'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissuepriority'] = array('description' => $i18n->__('Can edit issue priority'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissueseverity'] = array('description' => $i18n->__('Can edit issue severity'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissuereproducability'] = array('description' => $i18n->__('Can edit issue reproducability'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissueresolution'] = array('description' => $i18n->__('Can edit issue resolution'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissueestimated_time'] = array('description' => $i18n->__('Can estimate issues'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissuespent_time'] = array('description' => $i18n->__('Can spend time working on issues'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissuepercent_complete'] = array('description' => $i18n->__('Can edit issue percent complete'));
				self::$_available_permissions['issues']['caneditissue']['details']['caneditissuemilestone'] = array('description' => $i18n->__('Can set issue milestone'));
				self::$_available_permissions['issues']['caneditissuecustomfieldsown'] = array('description' => $i18n->__('Can change custom field values for issues reported by the user'), 'details' => array());
				self::$_available_permissions['issues']['caneditissuecustomfields'] = array('description' => $i18n->__('Can change custom field values for any issues'), 'details' => array());
				foreach (TBGCustomDatatype::getAll() as $cdf)
				{
					self::$_available_permissions['issues']['caneditissuecustomfieldsown']['details']['caneditissuecustomfields'.$cdf->getKey().'own'] = array('description' => $i18n->__('Can change custom field "%field_name%" for issues reported by the user', array('%field_name%' => $cdf->getDescription())));
					self::$_available_permissions['issues']['caneditissuecustomfields']['details']['caneditissuecustomfields'.$cdf->getKey()] = array('description' => $i18n->__('Can change custom field "%field_name%" for any issues', array('%field_name%' => $cdf->getDescription())));
				}
				self::$_available_permissions['issues']['canaddextrainformationtoissues'] = array('description' => $i18n->__('Can add/remove extra information (edition, component, release, links and files) to issues'), 'details' => array());
				self::$_available_permissions['issues']['canaddextrainformationtoissues']['details']['canaddbuildsown'] = array('description' => $i18n->__('Can add releases / versions to list of affected versions for issues reported by the user'));
				self::$_available_permissions['issues']['canaddextrainformationtoissues']['details']['canaddbuilds'] = array('description' => $i18n->__('Can add releases / versions to list of affected versions for any issues'));
				self::$_available_permissions['issues']['canaddextrainformationtoissues']['details']['canaddcomponentsown'] = array('description' => $i18n->__('Can add components to list of affected components for issues reported by the user'));
				self::$_available_permissions['issues']['canaddextrainformationtoissues']['details']['canaddcomponents'] = array('description' => $i18n->__('Can add components to list of affected components for any issues'));
				self::$_available_permissions['issues']['canaddextrainformationtoissues']['details']['canaddeditionsown'] = array('description' => $i18n->__('Can add editions to list of affected editions for issues reported by the user'));
				self::$_available_permissions['issues']['canaddextrainformationtoissues']['details']['canaddeditions'] = array('description' => $i18n->__('Can add editions to list of affected editions for any issues'));
				self::$_available_permissions['issues']['canaddextrainformationtoissues']['details']['canaddlinkstoissuesown'] = array('description' => $i18n->__('Can add links to issues reported by the user'));
				self::$_available_permissions['issues']['canaddextrainformationtoissues']['details']['canaddlinkstoissues'] = array('description' => $i18n->__('Can add links to any issues'));
				self::$_available_permissions['issues']['canaddextrainformationtoissues']['details']['canaddfilestoissuesown'] = array('description' => $i18n->__('Can add files to and remove own files from issues reported by the user'));
				self::$_available_permissions['issues']['canaddextrainformationtoissues']['details']['canaddfilestoissues'] = array('description' => $i18n->__('Can add files to and remove own files from any issues'));
				self::$_available_permissions['issues']['canaddextrainformationtoissues']['details']['canremovefilesfromissuesown'] = array('description' => $i18n->__('Can remove any attachments from issues reported by the user'));
				self::$_available_permissions['issues']['canaddextrainformationtoissues']['details']['canremovefilesfromissues'] = array('description' => $i18n->__('Can remove any attachments from any issues'));
				self::$_available_permissions['issues']['canaddextrainformationtoissues']['details']['canaddrelatedissues'] = array('description' => $i18n->__('Can add related issues to other issues'));
				self::$_available_permissions['issues']['canpostandeditcomments'] = array('description' => $i18n->__('Can see public comments, post new, edit own and delete own comments'), 'details' => array());
				self::$_available_permissions['issues']['canpostandeditcomments']['details']['canviewcomments'] = array('description' => $i18n->__('Can see public comments'));
				self::$_available_permissions['issues']['canpostandeditcomments']['details']['canpostcomments'] = array('description' => $i18n->__('Can post comments'));
				self::$_available_permissions['issues']['canpostandeditcomments']['details']['caneditcommentsown'] = array('description' => $i18n->__('Can edit own comments'));
				self::$_available_permissions['issues']['canpostandeditcomments']['details']['candeletecommentsown'] = array('description' => $i18n->__('Can delete own comments'));
				self::$_available_permissions['issues']['canpostseeandeditallcomments'] = array('description' => $i18n->__('Can see all comments (including non-public), post new, edit and delete all comments'), 'details' => array());
				self::$_available_permissions['issues']['canpostseeandeditallcomments']['details']['canseenonpubliccomments'] = array('description' => $i18n->__('Can see all comments including hidden'));
				self::$_available_permissions['issues']['canpostseeandeditallcomments']['details']['caneditcomments'] = array('description' => $i18n->__('Can edit all comments'));
				self::$_available_permissions['issues']['canpostseeandeditallcomments']['details']['candeletecomments'] = array('description' => $i18n->__('Can delete any comments'));
				self::$_available_permissions['pages']['page_account_access']['details']['canchangepassword'] = array('description' => $i18n->__('Can change own password'), 'mode' => 'permissive');
				//self::trigger('core', 'cachepermissions', array('permissions' => &self::$_available_permissions));
			}
		}
		
		/**
		 * Returns all permissions available for a specific identifier
		 *  
		 * @param string $applies_to The identifier
		 * 
		 * @return array
		 */
		public static function getAvailablePermissions($applies_to = null)
		{
			self::_cacheAvailablePermissions();
			if ($applies_to === null)
			{
				$list = self::$_available_permissions;
				$retarr = array();
				foreach ($list as $key => $details)
				{
					foreach ($details as $dkey => $dd)
					{
						$retarr[$dkey] = $dd;
					}
				}
				foreach (self::getModules() as $module_key => $module)
				{
					$retarr['module_'.$module_key] = array();
					foreach ($module->getAvailablePermissions() as $mpkey => $mp)
					{
						$retarr['module_'.$module_key][$mpkey] = $mp;
					}
				}
				return $retarr;
			}
			if (array_key_exists($applies_to, self::$_available_permissions))
			{
				return self::$_available_permissions[$applies_to];
			}
			elseif (mb_substr($applies_to, 0, 7) == 'module_')
			{
				$module_name = mb_substr($applies_to, 7);
				if (self::isModuleLoaded($module_name))
				{
					return self::getModule($module_name)->getAvailablePermissions();
				}
			}
			else
			{
				return array();
			}
		}
		
		public static function getProjectAssigneeDefaultPermissionSet($ownable, $type)
		{
			$return_values = array();
			if ($ownable instanceof \thebuggenie\entities\Project)
			{
				$return_values[] = 'page_project_allpages_access';
				$return_values[] = 'canseeproject';
				$return_values[] = 'canseeprojecthierarchy';
				$return_values[] = 'cancreateandeditissues';
				$return_values[] = 'canpostandeditcomments';
			}
			elseif ($ownable instanceof TBGEdition)
			{
				$return_values[] = 'canseeedition';
			}
			elseif ($ownable instanceof TBGComponent)
			{
				$return_values[] = 'canseecomponent';
			}
			
			if(is_numeric($type))
			{
				$role = TBGProjectAssigneesTable::getTypeName($type);
				$type = $role->getItemdata();
			}

			switch ($type)
			{
				case '_leader':
					$return_values[] = 'canmanageproject';
					$return_values[] = 'candoscrumplanning';
					break;
				case '_owner':
					$return_values[] = 'canmanageproject';
					$return_values[] = 'candoscrumplanning';
					break;
				case '_qa_responsible':
					$return_values[] = 'candoscrumplanning';
					$return_values[] = 'caneditissue';
					$return_values[] = 'caneditissuecustomfields';
					$return_values[] = 'canaddextrainformationtoissues';
					break;
				case TBGProjectAssigneesTable::TYPE_DEVELOPER:
					$return_values[] = 'candoscrumplanning';
					$return_values[] = 'caneditissue';
					$return_values[] = 'caneditissuecustomfields';
					$return_values[] = 'canaddextrainformationtoissues';
					break;
				case TBGProjectAssigneesTable::TYPE_PROJECTMANAGER:
					$return_values[] = 'candoscrumplanning';
					$return_values[] = 'caneditissue';
					$return_values[] = 'caneditissuecustomfields';
					$return_values[] = 'canaddextrainformationtoissues';
					break;
				case TBGProjectAssigneesTable::TYPE_TESTER:
					$return_values[] = 'caneditissue';
					$return_values[] = 'caneditissuecustomfields';
					$return_values[] = 'canaddextrainformationtoissues';
					break;
				case TBGProjectAssigneesTable::TYPE_DOCUMENTOR:
					$return_values[] = 'caneditissue';
					$return_values[] = 'caneditissuecustomfields';
					$return_values[] = 'canaddextrainformationtoissues';
					break;
			}
			
			return $return_values;
		}

		/**
		 * Returns all the links on the frontpage
		 * 
		 * @return array
		 */
		public static function getMainLinks()
		{
			if (!$links = Cache::get('core_main_links'))
			{
				$links = Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\Links')->getMainLinks();
				Cache::add('core_main_links', $links);
			}
			return $links;
		}
		
		public static function listenCoreLoadTemplateVariables(Event $event)
		{
		}

	}
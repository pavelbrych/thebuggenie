<?php

	namespace thebuggenie\core;
	
	use caspar\core\Caspar;

	/**
	 * The scope class
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage core
	 */

	/**
	 * The scope class
	 *
	 * @package thebuggenie
	 * @subpackage core
	 */
	class Scope extends IdentifiableClass
	{
		
		static protected $_b2dbtablename = '\\thebuggenie\\tables\\Scopes';
		
		static protected $_scopes = null;

		protected $_description = '';
		
		protected $_enabled = false;
		
		protected $_shortname = '';
		
		protected $_administrator = null;
		
		protected $_hostnames = null;

		protected $_uploads_enabled = true;

		protected $_max_upload_limit = 0;

		protected $_custom_workflows_enabled = true;

		protected $_max_workflows = 0;

		protected $_max_users = 0;

		protected $_max_projects = 0;

		protected $_max_teams = 0;

		static function getAll()
		{
			if (self::$_scopes === null)
			{
				$res = TBGScopesTable::getTable()->doSelectAll();
				$scopes = array();

				while ($row = $res->getNextRow())
				{
					$scope = Caspar::factory()->manufacture('\thebuggenie\entities\Scope', $row->get(TBGScopesTable::ID), $row);
					$scopes[$scope->getID()] = $scope;
				}

				self::$_scopes = $scopes;
			}

			return self::$_scopes;
		}
		
		public function isEnabled()
		{
			return $this->_enabled;
		}

		public function isDefault()
		{
			return (bool) ($this->_id == 1);
		}
		
		public function setEnabled($enabled = true)
		{
			$this->_enabled = (bool) $enabled;
		}
		
		public function getDescription()
		{
			return $this->_description;
		}
		
		public function setDescription($description)
		{
			$this->_description = $description;
		}
		
		protected function _populateHostnames()
		{
			if ($this->_hostnames === null)
			{
				if ($this->_id)
					$this->_hostnames = Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\ScopeHostnames')->getHostnamesForScope($this->getID());
				else
					$this->_hostnames = array();
			}
		}

		public function getHostnames()
		{
			$this->_populateHostnames();
			return $this->_hostnames;
		}
		
		public function addHostname($hostname)
		{
			$hostname = trim($hostname, "/");
			$this->_populateHostnames();
			$this->_hostnames[] = $hostname;
		}
		
		/**
		 * Returns the scope administrator
		 *
		 * @return TBGUser
		 */
		public function getScopeAdmin()
		{
			if (!$this->_administrator instanceof TBGUser && $this->_administrator != 0)
			{
				try
				{
					$this->_administrator = Caspar::factory()->manufacture('\thebuggenie\entities\User', $this->_administrator);
				}
				catch (Exception $e) { }
			}
			return $this->_administrator;
		}
		
		protected function _preDelete()
		{
			$tables = array(
				'TBGIssueCustomFieldsTable', 'TBGIssueAffectsEditionTable',
				'TBGIssueAffectsBuildTable', 'TBGIssueAffectsComponentTable', 'TBGIssueFilesTable',
				'TBGIssueRelationsTable', 'TBGIssuetypeSchemeLinkTable', 'TBGIssuetypeSchemesTable',
				'TBGIssueTypesTable', 'TBGListTypesTable', 'TBGIssuesTable', 'TBGCommentsTable',
				'TBGComponentAssigneesTable', 'TBGProjectAssigneesTable', 'TBGEditionAssigneesTable',
				'TBGComponentsTable', 'TBGEditionsTable', 'TBGBuildsTable', 'TBGMilestonesTable',
				'TBGIssuesTable', 'TBGProjectsTable'
			);
			foreach($tables as $table)
			{
				$table::getTable()->deleteFromScope($this->getID());
			}
		}

		protected function _postSave($is_new)
		{
			Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\ScopeHostnames')->saveScopeHostnames($this->getHostnames(), $this->getID());
			// Load fixtures for this scope if it's a new scope
			if ($is_new)
			{
				if ($this->getID() != 1)
				{
					$prev_scope = Context::getScope();
					Caspar::setScope($this);
				}
				$this->loadFixtures();
				if ($this->getID() != 1)
				{
					TBGModule::installModule('publish', $t46his);
					Caspar::setScope($prev_scope);
				}
			}
		}
		
		public function _construct(\b2db\Row $row, $foreign_key = null)
		{
			if (Caspar::isCLI()) return;
			$hostprefix = (!array_key_exists('HTTPS', $_SERVER) || $_SERVER['HTTPS'] == '' || $_SERVER['HTTPS'] == 'off') ? 'http' : 'https';
			$this->_hostname = "{$hostprefix}://{$_SERVER['SERVER_NAME']}";
			$port = $_SERVER['SERVER_PORT'];
			if ($port != 80)
			{
				$this->_hostname .= ":{$port}";
			}
		}

		public function getCurrentHostname()
		{
			return $this->_hostname;
		}

		public function loadFixtures()
		{
			// Load initial settings
			TBGSettingsTable::getTable()->loadFixtures($this);
			TBGSettings::loadSettings();
			
			// Load group, users and permissions fixtures
			\thebuggenie\entities\Group::loadFixtures($this);

			// Load initial teams
			TBGTeam::loadFixtures($this);
			
			// Set up user states, like "available", "away", etc
			TBGUserstate::loadFixtures($this);
			
			// Set up data types
			list($b_id, $f_id, $e_id, $t_id, $u_id, $i_id) = TBGIssuetype::loadFixtures($this);
			$scheme = TBGIssuetypeScheme::loadFixtures($this);
			TBGIssueFieldsTable::getTable()->loadFixtures($this, $scheme, $b_id, $f_id, $e_id, $t_id, $u_id, $i_id);
			\thebuggenie\entities\Datatype::loadFixtures($this);
			
			// Set up workflows
			TBGWorkflow::loadFixtures($this);
			TBGWorkflowSchemesTable::getTable()->loadFixtures($this);
			TBGWorkflowIssuetypeTable::getTable()->loadFixtures($this);
			
			// Set up left menu links
			TBGLinksTable::getTable()->loadFixtures($this);
		}

		public function isUploadsEnabled()
		{
			return ($this->isDefault() || $this->_uploads_enabled);
		}

		public function setUploadsEnabled($enabled = true)
		{
			$this->_uploads_enabled = $enabled;
		}

		public function isCustomWorkflowsEnabled()
		{
			return ($this->isDefault() || $this->_custom_workflows_enabled);
		}

		public function setCustomWorkflowsEnabled($enabled = true)
		{
			$this->_custom_workflows_enabled = $enabled;
		}

		public function setMaxWorkflowsLimit($limit)
		{
			$this->_max_workflows = $limit;
		}

		public function getMaxWorkflowsLimit()
		{
			return ($this->isDefault()) ? 0 : (int) $this->_max_workflows;
		}

		public function hasCustomWorkflowsAvailable()
		{
			if ($this->isCustomWorkflowsEnabled())
				return ($this->getMaxWorkflowsLimit()) ? (TBGWorkflow::getCustomWorkflowsCount() < $this->getMaxWorkflowsLimit()) : true;
			else
				return false;
		}

		public function setMaxUploadLimit($limit)
		{
			$this->_max_upload_limit = $limit;
		}

		public function getMaxUploadLimit()
		{
			return ($this->isDefault()) ? 0 : (int) $this->_max_upload_limit;
		}

		public function getMaxUsers()
		{
			return ($this->isDefault()) ? 0 : (int) $this->_max_users;
		}

		public function setMaxUsers($limit)
		{
			$this->_max_users = $limit;
		}

		public function hasUsersAvailable()
		{
			return ($this->getMaxUsers()) ? (TBGUser::getUsersCount() < $this->getMaxUsers()) : true;
		}

		public function getMaxProjects()
		{
			return ($this->isDefault()) ? 0 : (int) $this->_max_projects;
		}

		public function setMaxProjects($limit)
		{
			$this->_max_projects = $limit;
		}

		public function hasProjectsAvailable()
		{
			return ($this->getMaxProjects()) ? (\thebuggenie\entities\Project::getProjectsCount() < $this->getMaxProjects()) : true;
		}

		public function getMaxTeams()
		{
			return ($this->isDefault()) ? 0 : (int) $this->_max_teams;
		}

		public function setMaxTeams($limit)
		{
			$this->_max_teams = $limit;
		}

		public function hasTeamsAvailable()
		{
			return ($this->getMaxTeams()) ? (TBGTeam::getTeamsCount() < $this->getMaxTeams()) : true;
		}
		
	}

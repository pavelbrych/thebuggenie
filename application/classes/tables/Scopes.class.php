<?php

	namespace thebuggenie\tables;

	use b2db\Core,
		b2db\Table,
		b2db\Criteria,
		b2db\Criterion;

	/**
	 * Scopes table
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage tables
	 */

	/**
	 * Scopes table
	 *
	 * @package thebuggenie
	 * @subpackage tables
	 */
	class Scopes extends Table
	{
		
		const B2DB_TABLE_VERSION = 2;
		const B2DBNAME = 'scopes';
		const ID = 'scopes.id';
		const ENABLED = 'scopes.enabled';
		const CUSTOM_WORKFLOWS_ENABLED = 'scopes.custom_workflows_enabled';
		const MAX_WORKFLOWS = 'scopes.max_workflows';
		const UPLOADS_ENABLED = 'scopes.uploads_enabled';
		const MAX_UPLOAD_LIMIT = 'scopes.max_upload_limit';
		const MAX_USERS = 'scopes.max_users';
		const MAX_TEAMS = 'scopes.max_teams';
		const MAX_PROJECTS = 'scopes.max_projects';
		const DESCRIPTION = 'scopes.description';
		const NAME = 'scopes.name';
		const ADMINISTRATOR = 'scopes.administrator';
		
		protected function _setup()
		{
			parent::_addBoolean(self::ENABLED, false);
			parent::_addBoolean(self::CUSTOM_WORKFLOWS_ENABLED, true);
			parent::_addBoolean(self::UPLOADS_ENABLED, true);
			parent::_addInteger(self::MAX_UPLOAD_LIMIT, 5);
			parent::_addInteger(self::MAX_WORKFLOWS, 5);
			parent::_addInteger(self::MAX_USERS, 5);
			parent::_addInteger(self::MAX_PROJECTS, 5);
			parent::_addInteger(self::MAX_TEAMS, 5);
			parent::_addText(self::DESCRIPTION, false);
			parent::_addText(self::NAME, false);
			parent::_addInteger(self::ADMINISTRATOR, 10);
		}

		protected function _migrateData(\b2db\Table $old_table)
		{
			$crit = $this->_connection->getTable('\\thebuggenie\\tables\ScopeHostnames')->getCriteria();
			$crit->addInsert(ScopeHostnames::HOSTNAME, '*');
			$crit->addInsert(ScopeHostnames::SCOPE_ID, 1);
			$this->_connection->getTable('\\thebuggenie\\tables\ScopeHostnames')->doInsert($crit);

			$crit = $this->getCriteria();
			$crit->addUpdate(self::NAME, 'Default scope');
			$this->doUpdateById($crit, 1);
		}
		
		public function getByHostname($hostname)
		{
			$crit = $this->getCriteria();
			$crit->addJoin($this->_connection->getTable('\\thebuggenie\\tables\ScopeHostnames'), ScopeHostnames::SCOPE_ID, self::ID);
			$crit->addWhere(ScopeHostnames::HOSTNAME, $hostname);
			$row = $this->doSelectOne($crit);
			return $row;
		}

		public function getDefault()
		{
			return $this->doSelectById(1);
		}

		public function getByHostnameOrDefault($hostname = null)
		{
			$crit = $this->getCriteria();
			if ($hostname !== null)
			{
				$crit->addJoin($this->_connection->getTable('\\thebuggenie\\tables\ScopeHostnames'), ScopeHostnames::SCOPE_ID, self::ID);
				$crit->addWhere(ScopeHostnames::HOSTNAME, $hostname);
				$crit->addOr(self::ID, 1);
				$crit->addOrderBy(self::ID, 'desc');
			}
			else
			{
				$crit->addWhere(self::ID, 1);
			}

			if ($res = $this->doSelect($crit))
			{
				return $res->getNextRow();
			}
		}

	}

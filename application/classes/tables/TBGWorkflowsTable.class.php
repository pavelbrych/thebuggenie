<?php

	use b2db\Core,
		b2db\Criteria,
		b2db\Criterion;

	/**
	 * Workflows table
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage tables
	 */

	/**
	 * Workflows table
	 *
	 * @package thebuggenie
	 * @subpackage tables
	 */
	class TBGWorkflowsTable extends ScopedTable
	{

		const B2DB_TABLE_VERSION = 1;
		const B2DBNAME = 'workflows';
		const ID = 'workflows.id';
		const SCOPE = 'workflows.scope';
		const NAME = 'workflows.name';
		const DESCRIPTION = 'workflows.description';
		const IS_ACTIVE = 'workflows.is_active';

		/**
		 * Return an instance of this table
		 *
		 * @return TBGWorkflowsTable
		 */
		public static function getTable()
		{
			return Core::getTable('TBGWorkflowsTable');
		}

		protected function _setup()
		{
			
			parent::_addForeignKeyColumn(self::SCOPE, $this->_connection->getTable('\\thebuggenie\\tables\\Scopes'), \thebuggenie\tables\Scopes::ID);
			parent::_addVarchar(self::NAME, 200);
			parent::_addText(self::DESCRIPTION, false);
			parent::_addBoolean(self::IS_ACTIVE);
		}

		public function getAll($scope = null)
		{
			$scope = ($scope === null) ? \thebuggenie\core\Context::getScope()->getID() : $scope;
			$crit = $this->getCriteria();
			$crit->addWhere(self::SCOPE, $scope);
			$crit->addOrderBy(self::ID, Criteria::SORT_ASC);

			$res = $this->doSelect($crit);

			return $res;
		}

		public function getByID($id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::SCOPE, \thebuggenie\core\Context::getScope()->getID());
			$row = $this->doSelectById($id, $crit, false);
			return $row;
		}

		public function countWorkflows($scope = null)
		{
			$scope = ($scope === null) ? \thebuggenie\core\Context::getScope()->getID() : $scope;
			$crit = $this->getCriteria();
			$crit->addWhere(self::SCOPE, $scope);

			return $this->doCount($crit);
		}

	}
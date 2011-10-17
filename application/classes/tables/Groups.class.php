<?php

	namespace thebuggenie\tables;

	use b2db\Core,
		b2db\Criteria,
		b2db\Criterion;

	/**
	 * Groups table
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage tables
	 */

	/**
	 * Groups table
	 *
	 * @package thebuggenie
	 * @subpackage tables
	 */
	class Groups extends ScopedTable 
	{

		const B2DB_TABLE_VERSION = 1;
		const B2DBNAME = 'groups';
		const ID = 'groups.id';
		const NAME = 'groups.name';
		const SCOPE = 'groups.scope';

		protected function _setup()
		{
			parent::_addVarchar(self::NAME, 50);
			parent::_addForeignKeyColumn(self::SCOPE, $this->_connection->getTable('\\thebuggenie\\tables\\Scopes'), \thebuggenie\tables\Scopes::ID);
		}

		public function getAll($scope = null)
		{
			$scope = ($scope === null) ? \thebuggenie\core\Context::getScope()->getID() : $scope;
			$crit = $this->getCriteria();
			$crit->addWhere(self::SCOPE, $scope);
			
			$res = $this->doSelect($crit);
			
			return $res;
		}

		public function doesGroupNameExist($group_name)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::NAME, $group_name);
			
			return (bool) $this->doCount($crit);
		}
		
	}

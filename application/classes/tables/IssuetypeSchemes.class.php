<?php

	namespace thebuggenie\tables;

	use b2db\Core,
		b2db\Criteria,
		b2db\Criterion;

	/**
	 * Issuetype schemes table
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage tables
	 */

	/**
	 * Issuetype schemes table
	 *
	 * @package thebuggenie
	 * @subpackage tables
	 */
	class IssuetypeSchemes extends ScopedTable
	{

		const B2DB_TABLE_VERSION = 1;
		const B2DBNAME = 'issuetype_schemes';
		const ID = 'issuetype_schemes.id';
		const SCOPE = 'issuetype_schemes.scope';
		const NAME = 'issuetype_schemes.name';
		const DESCRIPTION = 'issuetype_schemes.description';

		protected function _setup()
		{
			parent::_addVarchar(self::NAME, 200);
			parent::_addText(self::DESCRIPTION, false);
			parent::_addForeignKeyColumn(self::SCOPE, $this->_connection->getTable('\\thebuggenie\\tables\\Scopes'), Scopes::ID);
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
			$row = $this->doSelectById($id, $crit, 'none');
			return $row;
		}

	}
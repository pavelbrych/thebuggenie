<?php

	namespace thebuggenie\tables;

	use b2db\Core,
		b2db\Criteria,
		b2db\Criterion;

	/**
	 * Clients table
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage tables
	 */

	/**
	 * Clients table
	 *
	 * @package thebuggenie
	 * @subpackage tables
	 */
	class Clients extends ScopedTable 
	{

		const B2DB_TABLE_VERSION = 1;
		const B2DBNAME = 'clients';
		const ID = 'clients.id';
		const SCOPE = 'clients.scope';
		const NAME = 'clients.name';
		const WEBSITE = 'clients.website';
		const EMAIL = 'clients.email';
		const TELEPHONE = 'clients.telephone';
		const FAX = 'clients.fax';

		protected function _setup()
		{
			parent::_addVarchar(self::NAME, 50);
			parent::_addVarchar(self::WEBSITE, 200);
			parent::_addVarchar(self::EMAIL, 100);
			parent::_addVarchar(self::TELEPHONE, 20, null);
			parent::_addVarchar(self::FAX, 20, null);
			parent::_addForeignKeyColumn(self::SCOPE, $this->_connection->getTable('\\thebuggenie\\tables\\Scopes'), Scopes::ID);
		}

		public function getAll($scope = null)
		{
			$scope = ($scope === null) ? \thebuggenie\core\Context::getScope()->getID() : $scope;
			$crit = $this->getCriteria();
			$crit->addWhere(self::SCOPE, $scope);
			
			$res = $this->doSelect($crit, 'none');
			
			return $res;
		}

		public function doesClientNameExist($client_name)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::NAME, $client_name);

			return (bool) $this->doCount($crit);
		}

	}

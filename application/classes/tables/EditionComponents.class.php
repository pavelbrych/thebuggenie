<?php

	namespace thebuggenie\tables;

	use b2db\Core,
		b2db\Criteria,
		b2db\Criterion;

	/**
	 * Edition components table
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage tables
	 */

	/**
	 * Edition components table
	 *
	 * @package thebuggenie
	 * @subpackage tables
	 */
	class EditionComponents extends ScopedTable 
	{

		const B2DB_TABLE_VERSION = 1;
		const B2DBNAME = 'editioncomponents';
		const ID = 'editioncomponents.id';
		const SCOPE = 'editioncomponents.scope';
		const EDITION = 'editioncomponents.edition';
		const COMPONENT = 'editioncomponents.component';

		protected function _setup()
		{
			parent::_addForeignKeyColumn(self::EDITION, $this->_connection->getTable('\\thebuggenie\\tables\\Editions'), Editions::ID);
			parent::_addForeignKeyColumn(self::COMPONENT, $this->_connection->getTable('\\thebuggenie\\tables\\Components'), Components::ID);
			parent::_addForeignKeyColumn(self::SCOPE, $this->_connection->getTable('\\thebuggenie\\tables\\Scopes'), Scopes::ID);
		}

		public function getByEditionID($edition_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::EDITION, $edition_id);
			$res = $this->doSelect($crit);

			return $res;
		}

		public function getByEditionIDandComponentID($edition_id, $component_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::EDITION, $edition_id);
			$crit->addWhere(self::COMPONENT, $component_id);

			return $this->doCount($crit);
		}

		public function addEditionComponent($edition_id, $component_id)
		{
			if ($this->getByEditionIDandComponentID($edition_id, $component_id) == 0)
			{
				$crit = $this->getCriteria();
				$crit->addInsert(self::EDITION, $edition_id);
				$crit->addInsert(self::COMPONENT, $component_id);
				$crit->addInsert(self::SCOPE, \thebuggenie\core\Context::getScope()->getID());
				$res = $this->doInsert($crit);

				return true;
			}
			return false;
		}

		public function removeEditionComponent($edition_id, $component_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::EDITION, $edition_id);
			$crit->addWhere(self::COMPONENT, $component_id);
			$crit->addWhere(self::SCOPE, \thebuggenie\core\Context::getScope()->getID());
			$res = $this->doDelete($crit);
		}

	}

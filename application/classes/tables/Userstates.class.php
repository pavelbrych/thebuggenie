<?php

	namespace thebuggenie\tables;

	use b2db\Core,
		b2db\Criteria,
		b2db\Criterion;

	/**
	 * Userstate table
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage tables
	 */

	/**
	 * Userstate table
	 *
	 * @package thebuggenie
	 * @subpackage tables
	 */
	class Userstates extends ScopedTable 
	{

		const B2DB_TABLE_VERSION = 1;
		const B2DBNAME = 'userstate';
		const ID = 'userstate.id';
		const SCOPE = 'userstate.scope';
		const NAME = 'userstate.name';
		const UNAVAILABLE = 'userstate.is_unavailable';
		const BUSY = 'userstate.is_busy';
		const ONLINE = 'userstate.is_online';
		const MEETING = 'userstate.is_in_meeting';
		const COLOR = 'userstate.itemdata';
		const ABSENT = 'userstate.is_absent';

		protected function _setup()
		{
			parent::_addVarchar(self::NAME, 100);
			parent::_addBoolean(self::UNAVAILABLE);
			parent::_addBoolean(self::BUSY);
			parent::_addBoolean(self::ONLINE);
			parent::_addBoolean(self::MEETING);
			parent::_addBoolean(self::ABSENT);
			parent::_addVarchar(self::COLOR, 7, '');
			parent::_addForeignKeyColumn(self::SCOPE, $this->_connection->getTable('\\thebuggenie\\tables\\Scopes'), Scopes::ID);
		}

		public function getAll()
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::SCOPE, \thebuggenie\core\Context::getScope()->getID());

			return $this->doSelect($crit);
		}
		
	}

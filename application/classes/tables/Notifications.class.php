<?php

	namespace thebuggenie\tables;

	use b2db\Core,
		b2db\Criteria,
		b2db\Criterion;

	/**
	 * Notifications table
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage tables
	 */

	/**
	 * Notifications table
	 *
	 * @package thebuggenie
	 * @subpackage tables
	 */
	class Notifications extends ScopedTable 
	{
		
		const B2DB_TABLE_VERSION = 1;
		const B2DBNAME = 'notifications';
		const ID = 'notifications.id';
		const SCOPE = 'notifications.scope';
		const MODULE_NAME = 'notifications.module_name';
		const NOTIFY_TYPE = 'notifications.notify_type';
		const TARGET_ID = 'notifications.target_id';
		const UID = 'notifications.uid';
		const GID = 'notifications.gid';
		const TID = 'notifications.tid';
		const TITLE = 'notifications.title';
		const CONTENTS = 'notifications.contents';
		const STATUS = 'notifications.status';

		protected function _setup()
		{
			parent::_addVarchar(self::MODULE_NAME, 50);
			parent::_addInteger(self::NOTIFY_TYPE, 5);
			parent::_addInteger(self::TARGET_ID, 10);
			parent::_addVarchar(self::TITLE, 100);
			parent::_addText(self::CONTENTS, false);
			parent::_addInteger(self::STATUS, 5);
			parent::_addForeignKeyColumn(self::UID, $this->_connection->getTable('\\thebuggenie\\tables\Users'), Users::ID);
			parent::_addForeignKeyColumn(self::GID, $this->_connection->getTable('\\thebuggenie\\tables\\Groups'), Groups::ID);
			parent::_addForeignKeyColumn(self::TID, $this->_connection->getTable('\\thebuggenie\\tables\\Teams'), Teams::ID);
			parent::_addForeignKeyColumn(self::SCOPE, $this->_connection->getTable('\\thebuggenie\\tables\\Scopes'), Scopes::ID);
		}
		
		
	}

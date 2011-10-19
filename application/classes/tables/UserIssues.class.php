<?php

	namespace thebuggenie\tables;

	use b2db\Core,
		b2db\Criteria,
		b2db\Criterion;

	/**
	 * User issues table
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage tables
	 */

	/**
	 * User issues table
	 *
	 * @package thebuggenie
	 * @subpackage tables
	 */
	class UserIssues extends ScopedTable 
	{

		const B2DB_TABLE_VERSION = 1;
		const B2DBNAME = 'userissues';
		const ID = 'userissues.id';
		const SCOPE = 'userissues.scope';
		const ISSUE = 'userissues.issue';
		const UID = 'userissues.uid';

		protected function _setup()
		{
			parent::_addForeignKeyColumn(self::ISSUE, $this->_connection->getTable('\\thebuggenie\\tables\\Issues'), Issues::ID);
			parent::_addForeignKeyColumn(self::UID, $this->_connection->getTable('\\thebuggenie\\tables\Users'), Users::ID);
			parent::_addForeignKeyColumn(self::SCOPE, $this->_connection->getTable('\\thebuggenie\\tables\\Scopes'), Scopes::ID);
		}

		public function getUserIDsByIssueID($issue_id)
		{
			$uids = array();
			$crit = $this->getCriteria();
			
			$crit->addWhere(self::ISSUE, $issue_id);
			
			if ($res = $this->doSelect($crit))
			{
				while ($row = $res->getNextRow())
				{
					$uid = $row->get(TBGUserIssuesTable::UID);
					$uids[$uid] = $uid;
				}
			}
			
			return $uids;
		}
		
		public function getUserStarredIssues($user_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::UID, $user_id);
			$crit->addWhere(Issues::DELETED, 0);
			
			$res = $this->doSelect($crit);
			return $res;
		}
		
	}

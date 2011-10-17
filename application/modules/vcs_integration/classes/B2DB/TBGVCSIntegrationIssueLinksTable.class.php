<?php

	use b2db\Core,
		b2db\Criteria,
		b2db\Criterion;
	
	/**
	 * B2DB Table, vcs_integration -> VCSIntegrationIssueLinksTable
	 *
	 * @author Philip Kent <kentphilip@gmail.com>
	 * @version 3.2
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage vcs_integration
	 */

	/**
	 * B2DB Table, vcs_integration -> VCSIntegrationIssueLinksTable
	 *
	 * @package thebuggenie
	 * @subpackage vcs_integration
	 */
	class TBGVCSIntegrationIssueLinksTable extends ScopedTable 
	{

		const B2DB_TABLE_VERSION = 1;
		const B2DBNAME = 'vcsintegration_issuelinks';
		const ID = 'vcsintegration_issuelinks.id';
		const SCOPE = 'vcsintegration_issuelinks.scope';
		const ISSUE_NO = 'vcsintegration_issuelinks.issue_no';
		const COMMIT_ID = 'vcsintegration_issuelinks.commit_id';
					
		protected function _setup()
		{
			
			parent::_addForeignKeyColumn(self::SCOPE, $this->_connection->getTable('\\thebuggenie\\tables\\Scopes'), \thebuggenie\tables\Scopes::ID);
			parent::_addForeignKeyColumn(self::ISSUE_NO, TBGIssuesTable::getTable(), TBGIssuesTable::ID);
			parent::_addForeignKeyColumn(self::COMMIT_ID, TBGVCSIntegrationCommitsTable::getTable(), TBGVCSIntegrationCommitsTable::ID);
		}
		
		/**
		 * Return an instance of this table
		 *
		 * @return TBGVCSIntegrationIssueLinksTable
		 */
		public static function getTable()
		{
			return Core::getTable('TBGVCSIntegrationIssueLinksTable');
		}

		/**
		 * Get all rows by commit ID
		 * @param integer $id
		 * @return \b2db\Row
		 */
		public function getByCommitID($id, $scope = null)
		{
			$scope = ($scope === null) ? \thebuggenie\core\Context::getScope()->getID() : $scope;
			$crit = $this->getCriteria();
			$crit->addWhere(self::SCOPE, $scope);
			$crit->addWhere(self::COMMIT_ID, $id);
				
			$res = $this->doSelect($crit);
				
			return $res;
		}
		
		/**
		 * Get all rows by issue ID
		 * @param integer $id
		 * @return \b2db\Row
		 */
		public function getByIssueID($id, $scope = null)
		{
			$scope = ($scope === null) ? \thebuggenie\core\Context::getScope()->getID() : $scope;
			$crit = $this->getCriteria();
			$crit->addWhere(self::SCOPE, $scope);
			$crit->addWhere(self::ISSUE_NO, $id);
				
			$res = $this->doSelect($crit);

			return $res;
		}
	}
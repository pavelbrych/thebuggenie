<?php

	namespace thebuggenie\tables;

	use b2db\Core,
		b2db\Criteria,
		b2db\Criterion;

	/**
	 * Visible milestones table
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage tables
	 */

	/**
	 * Visible milestones table
	 *
	 * @package thebuggenie
	 * @subpackage tables
	 */
	class VisibleMilestones extends ScopedTable 
	{

		const B2DB_TABLE_VERSION = 1;
		const B2DBNAME = 'visible_milestones';
		const ID = 'visible_milestones.id';
		const SCOPE = 'visible_milestones.scope';
		const PROJECT_ID = 'visible_milestones.project_id';
		const MILESTONE_ID = 'visible_milestones.milestone_id';
		
		protected function _setup()
		{
			parent::_addForeignKeyColumn(self::MILESTONE_ID, $this->_connection->getTable('\\thebuggenie\\tables\\Milestones'), Milestones::ID);
			parent::_addForeignKeyColumn(self::PROJECT_ID, $this->_connection->getTable('\\thebuggenie\\tables\\Projects'), Projects::ID);
			parent::_addForeignKeyColumn(self::SCOPE, $this->_connection->getTable('\\thebuggenie\\tables\\Scopes'), Scopes::ID);
		}
		
		public function getAllByProjectID($project_id)
		{
			$milestones = array();
			$crit = $this->getCriteria();
			$crit->addWhere(self::PROJECT_ID, $project_id);
			$crit->addOrderBy(Milestones::SCHEDULED, Criteria::SORT_ASC);
			$res = $this->doSelect($crit);
			return $res;
		}
		
		public function clearByProjectID($project_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::PROJECT_ID, $project_id);
			$this->doDelete($crit);
			return true;
		}
		
		public function addByProjectIDAndMilestoneID($project_id, $milestone_id)
		{
			$crit = $this->getCriteria();
			$crit->addInsert(self::PROJECT_ID, $project_id);
			$crit->addInsert(self::MILESTONE_ID, $milestone_id);
			$crit->addInsert(self::SCOPE, \thebuggenie\core\Context::getScope()->getID());
			$res = $this->doInsert($crit);
			return true;
		}
		
	}

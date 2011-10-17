<?php

	use b2db\Core,
		b2db\Criteria,
		b2db\Criterion;

	class TBGMailQueueTable extends ScopedTable
	{
		
		const B2DB_TABLE_VERSION = 1;
		const B2DBNAME = 'mailing_queue';
		const ID = 'mailing_queue.id';
		const MESSAGE = 'mailing_queue.headers';
		const DATE = 'mailing_queue.date';
		const SCOPE = 'mailing_queue.scope';

		/**
		 * Return an instance of this table
		 *
		 * @return TBGMailQueueTable
		 */
		public static function getTable()
		{
			return Core::getTable('TBGMailQueueTable');
		}

		protected function _setup()
		{
			
			parent::_addText(self::MESSAGE);
			parent::_addInteger(self::DATE, 10);
			parent::_addForeignKeyColumn(self::SCOPE, $this->_connection->getTable('\\thebuggenie\\tables\\Scopes'), \thebuggenie\tables\Scopes::ID);
		}

		public function addMailToQueue(TBGMimemail $mail)
		{
			$message = serialize($mail);
			$crit = $this->getCriteria();
			$crit->addInsert(self::MESSAGE, $message);
			$crit->addInsert(self::DATE, time());
			$crit->addInsert(self::SCOPE, \thebuggenie\core\Context::getScope()->getID());

			$res = $this->doInsert($crit);

			return $res->getInsertID();
		}

		public function getQueuedMessages($limit = null)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::SCOPE, \thebuggenie\core\Context::getScope()->getID());
			if ($limit !== null)
			{
				$crit->setLimit($limit);
			}
			$crit->addOrderBy(self::DATE, Criteria::SORT_ASC);

			$messages = array();
			$res = $this->doSelect($crit);

			if ($res)
			{
				while ($row = $res->getNextRow())
				{
					$message = $row->get(self::MESSAGE);
					$messages[$row->get(self::ID)] = unserialize($message);
				}
			}

			return $messages;
		}

		public function deleteProcessedMessages($ids)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::ID, (array) $ids, Criteria::DB_IN);
			$crit->addWhere(self::SCOPE, \thebuggenie\core\Context::getScope()->getID());

			$res = $this->doDelete($crit);
		}
		
	}
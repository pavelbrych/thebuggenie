<?php

	use b2db\Core,
		b2db\Criteria,
		b2db\Criterion;

	class TBGArticleViewsTable extends ScopedTable 
	{

		const B2DB_TABLE_VERSION = 1;
		const B2DBNAME = 'articleviews';
		const ID = 'articleviews.id';
		const ARTICLE_ID = 'articleviews.article_id';
		const USER_ID = 'articleviews.user_id';
		const SCOPE = 'articleviews.scope';
		
		protected function _setup()
		{
			
			parent::_addForeignKeyColumn(self::USER_ID, Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\Users'), \thebuggenie\tables\Users::ID);
			parent::_addForeignKeyColumn(self::ARTICLE_ID, TBGArticlesTable::getTable(), TBGArticlesTable::ID);
			parent::_addForeignKeyColumn(self::SCOPE, $this->_connection->getTable('\\thebuggenie\\tables\\Scopes'), \thebuggenie\tables\Scopes::ID);
		}
	}


<?php

	namespace thebuggenie\tables;

	use b2db\Core,
		b2db\Criteria,
		b2db\Criterion;

	/**
	 * OpenID accounts table
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage tables
	 */

	/**
	 * OpenID accounts table
	 *
	 * @package thebuggenie
	 * @subpackage tables
	 */
	class OpenIdAccounts extends ScopedTable 
	{

		const B2DB_TABLE_VERSION = 1;
		const B2DBNAME = 'openid_accounts';
		const ID = 'openid_accounts.id';
		const IDENTITY = 'openid_accounts.identity';
		const IDENTITY_HASH = 'openid_accounts.identity_hash';
		const EMAIL = 'openid_accounts.email';
		const UID = 'openid_accounts.uid';

		protected function _setup()
		{
			parent::_addVarchar(self::IDENTITY, 300);
			parent::_addVarchar(self::IDENTITY_HASH, 300);
			parent::_addVarchar(self::EMAIL, 300);
			parent::_addForeignKeyColumn(self::UID, $this->_connection->getTable('\\thebuggenie\\tables\Users'), Users::ID);
		}
		
		public function addIdentity($identity, $email, $user_id)
		{
			$crit = $this->getCriteria();
			$crit->addInsert(self::IDENTITY, $identity);
			$crit->addInsert(self::IDENTITY_HASH, TBGUser::hashPassword($identity));
			$crit->addInsert(self::UID, $user_id);
			$this->doInsert($crit);
		}
		
		public function getUserIDfromIdentity($identity)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::IDENTITY, $identity);
			if ($row = $this->doSelectOne($crit))
			{
				return (integer) $row->get(self::UID);
			}
			return null;
		}
		
		public function getUserIDfromIdentityHash($identity_hash)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::IDENTITY_HASH, $identity_hash);
			if ($row = $this->doSelectOne($crit))
			{
				return (integer) $row->get(self::UID);
			}
			return null;
		}
		
		public function getIdentitiesForUserID($user_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::UID, $user_id);
			$identities = array();
			
			if ($res = $this->doSelect($crit))
			{
				while ($row = $res->getNextRow())
				{
					$identities[] = array('identity' => $row->get(self::IDENTITY), 'email' => $row->get(self::EMAIL));
				}
			}
			
			return $identities;
		}

	}

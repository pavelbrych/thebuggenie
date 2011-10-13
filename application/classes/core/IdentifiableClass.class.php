<?php

	namespace caspar\core;

	use b2db\Saveable;

	/**
	 * An identifiable class
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage core
	 */

	/**
	 * An identifiable class
	 *
	 * @package thebuggenie
	 * @subpackage core
	 */
	abstract class IdentifiableClass extends Saveable implements Identifiable
	{
		
		const TYPE_USER = 1;
		const TYPE_TEAM = 2;
		const TYPE_CLIENT = 3;
	
		/**
		 * The id for this item, usually identified by a record in the database
		 *
		 * @var integer
		 */
		protected $_id;
		
		/**
		 * The name of the object
		 *
		 * @var string
		 */
		protected $_name;
		
		/**
		 * The item type (if needed)
		 *
		 * @var string|integer
		 */
		protected $_itemtype;
		
		protected $_scope;
		
		/**
		 * Return the items id
		 * 
		 * @return integer
		 */
		public function getID()
		{
			return (int) $this->_id;
		}

		/**
		 * Set the items id
		 *
		 * @param integer $id
		 */
		public function setID($id)
		{
			$this->_id = $id;
		}

		public function setScope($scope)
		{
			if ($scope instanceof Scope)
			{
				$scope = $scope->getID();
			}
			$this->_scope = $scope;
		}
		
		public function getScope()
		{
			if (!$this->_scope instanceof Scope)
			{
				$this->_scope = Caspar::factory()->manufacture("\\caspar\\core\\Scope", $this->_scope);
			}
			return $this->_scope;
		}
		
		/**
		 * Return the items name
		 * 
		 * @return string
		 */
		public function getName()
		{
			return $this->_name;
		}

		/**
		 * Set the edition name
		 *
		 * @param string $name
		 */
		public function setName($name)
		{
			$this->_name = $name;
		}

		public function getType()
		{
			return 0;
		}

		protected function _preInitialize()
		{
			if (Caspar::getScope() instanceof Scope)
			{
				$this->_scope = Caspar::getScope();
			}
		}

		protected function toJSON()
		{
			return array('id' => $this->getID(), 'type' => $this->getType(), 'name' => $this->getName());
		}

	}

<?php

	namespace thebuggenie\entities;

	/**
	 * Issuetype scheme class
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.0
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage core
	 */

	/**
	 * Issuetype scheme class
	 *
	 * @package thebuggenie
	 * @subpackage core
	 */
	class IssuetypeScheme extends \thebuggenie\core\IdentifiableClass
	{

		static protected $_b2dbtablename = '\\thebuggenie\\tables\\IssuetypeSchemes';
		
		/**
		 * The default (core) issuetype scheme
		 * 
		 * @Class \thebuggenie\entities\IssuetypeScheme
		 */
		static protected $_core_scheme = null;
		
		protected static $_schemes = null;

		protected $_visiblefields = array();
		
		protected $_issuetypedetails = null;
		
		protected $_number_of_projects = null;
		
		/**
		 * The issuetype description
		 *
		 * @var string
		 */
		protected $_description = null;

		protected static function _populateSchemes()
		{
			if (self::$_schemes === null)
			{
				self::$_schemes = array();
				if ($res = \caspar\core\Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\IssuetypeSchemes')->getAll())
				{
					while ($row = $res->getNextRow())
					{
						$scheme = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\IssuetypeScheme', $row->get(\thebuggenie\tables\IssuetypeSchemes::ID), $row);
						
						if (self::$_core_scheme === null)
							self::$_core_scheme = $scheme;
						
						self::$_schemes[$row->get(\thebuggenie\tables\IssuetypeSchemes::ID)] = $scheme;
					}
				}
			}
		}
		
		/**
		 * Return all issuetypes in the system
		 *
		 * @return array An array of Issuetype objects
		 */
		public static function getAll()
		{
			self::_populateSchemes();
			return self::$_schemes;
		}
		
		/**
		 * Return the default (core) issuetype scheme
		 * 
		 * @return IssuetypeScheme
		 */
		public static function getCoreScheme()
		{
			self::_populateSchemes();
			return self::$_core_scheme;
		}

		public static function loadFixtures(TBGScope $scope)
		{
			$scheme = new IssuetypeScheme();
			$scheme->setScope($scope->getID());
			$scheme->setName("Default issuetype scheme");
			$scheme->setDescription("This is the default issuetype scheme. It is used by all projects with no specific issuetype scheme selected. This scheme cannot be edited or removed.");
			$scheme->save();
			
			foreach (\thebuggenie\entities\Issuetype::getAll() as $issuetype)
			{
				$scheme->setIssuetypeEnabled($issuetype);
				if ($issuetype->getIcon() == 'developer_report')
				{
					$scheme->setIssuetypeRedirectedAfterReporting($issuetype, false);
				}
				if (in_array($issuetype->getIcon(), array('task', 'developer_report', 'idea')))
				{
					$scheme->setIssuetypeReportable($issuetype, false);
				}
			}
			
			return $scheme;
		}
		
		/**
		 * Returns the issuetypes description
		 *
		 * @return string
		 */
		public function getDescription()
		{
			return $this->_description;
		}
		
		/**
		 * Set the issuetypes description
		 *
		 * @param string $description
		 */
		public function setDescription($description)
		{
			$this->_description = $description;
		}

		/**
		 * Whether this is the builtin issuetype that cannot be
		 * edited or removed
		 *
		 * @return boolean
		 */
		public function isCore()
		{
			return ($this->getID() == self::getCoreScheme()->getID());
		}

		protected function _populateAssociatedIssuetypes()
		{
			if ($this->_issuetypedetails === null)
			{
				$this->_issuetypedetails = \caspar\core\Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\IssuetypeSchemeLink')->getByIssuetypeSchemeID($this->getID());
			}
		}
		
		public function setIssuetypeEnabled(Issuetype $issuetype, $enabled = true)
		{
			if ($enabled)
			{
				if (!$this->isSchemeAssociatedWithIssuetype($issuetype))
				{
					\caspar\core\Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\IssuetypeSchemeLink')->associateIssuetypeWithScheme($issuetype->getID(), $this->getID());
				}
			}
			else
			{
				\caspar\core\Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\IssuetypeSchemeLink')->unAssociateIssuetypeWithScheme($issuetype->getID(), $this->getID());
			}
			$this->_issuetypedetails = null;
		}
		
		public function setIssuetypeDisabled(Issuetype $issuetype)
		{
			$this->setIssuetypeEnabled($issuetype, false);
		}

		public function isSchemeAssociatedWithIssuetype(Issuetype $issuetype)
		{
			$this->_populateAssociatedIssuetypes();
			return array_key_exists($issuetype->getID(), $this->_issuetypedetails);
		}
		
		public function isIssuetypeReportable(Issuetype $issuetype)
		{
			$this->_populateAssociatedIssuetypes();
			if (!$this->isSchemeAssociatedWithIssuetype($issuetype)) return false;
			return (bool) $this->_issuetypedetails[$issuetype->getID()]['reportable'];
		}

		public function isIssuetypeRedirectedAfterReporting(Issuetype $issuetype)
		{
			$this->_populateAssociatedIssuetypes();
			if (!$this->isSchemeAssociatedWithIssuetype($issuetype)) return false;
			return (bool) $this->_issuetypedetails[$issuetype->getID()]['redirect'];
		}
		
		public function setIssuetypeRedirectedAfterReporting(Issuetype $issuetype, $val = true)
		{
			\caspar\core\Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\IssuetypeSchemeLink')->setIssuetypeRedirectedAfterReportingForScheme($issuetype->getID(), $this->getID(), $val);
			if (array_key_exists($issuetype->getID(), $this->_visiblefields))
			{
				$this->_visiblefields[$issuetype->getID()]['redirect'] = $val;
			}
		}

		public function setIssuetypeReportable(Issuetype $issuetype, $val = true)
		{
			\caspar\core\Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\IssuetypeSchemeLink')->setIssuetypeReportableForScheme($issuetype->getID(), $this->getID(), $val);
			if (array_key_exists($issuetype->getID(), $this->_visiblefields))
			{
				$this->_visiblefields[$issuetype->getID()]['reportable'] = $val;
			}
		}

		/**
		 * Get all steps in this issuetype
		 *
		 * @return array An array of Issuetype objects
		 */
		public function getIssuetypes()
		{
			$this->_populateAssociatedIssuetypes();
			$retarr = array();
			foreach ($this->_issuetypedetails as $key => $details)
			{
				$retarr[$key] = $details['issuetype'];
			}
			return $retarr;
		}

		public function getReportableIssuetypes()
		{
			$issuetypes = $this->getIssuetypes();
			foreach ($issuetypes as $key => $issuetype)
			{
				if ($this->isIssuetypeReportable($issuetype)) continue;
				unset($issuetypes[$key]);
			}
			return $issuetypes;
		}
		
		protected function _preDelete()
		{
			TBGIssueFieldsTable::getTable()->deleteByIssuetypeSchemeID($this->getID());
			\caspar\core\Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\IssuetypeSchemeLink')->deleteByIssuetypeSchemeID($this->getID());
			\caspar\core\Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\Projects')->updateByIssuetypeSchemeID($this->getID());
		}

		protected function _populateVisibleFieldsForIssuetype(Issuetype $issuetype)
		{
			if (!array_key_exists($issuetype->getID(), $this->_visiblefields))
			{
				$this->_visiblefields[$issuetype->getID()] = TBGIssueFieldsTable::getTable()->getSchemeVisibleFieldsArrayByIssuetypeID($this->getID(), $issuetype->getID());
			}
		}

		public function getVisibleFieldsForIssuetype(Issuetype $issuetype)
		{
			$this->_populateVisibleFieldsForIssuetype($issuetype);
			return $this->_visiblefields[$issuetype->getID()];
		}
		
		public function clearAvailableFieldsForIssuetype(Issuetype $issuetype)
		{
			TBGIssueFieldsTable::getTable()->deleteBySchemeIDandIssuetypeID($this->getID(), $issuetype->getID());
		}

		public function setFieldAvailableForIssuetype(Issuetype $issuetype, $key, $details = array())
		{
			TBGIssueFieldsTable::getTable()->addFieldAndDetailsBySchemeIDandIssuetypeID($this->getID(), $issuetype->getID(), $key, $details);
		}
		
		public function isInUse()
		{
			if ($this->_number_of_projects === null)
			{
				$this->_number_of_projects = \caspar\core\Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\Projects')->countByIssuetypeSchemeID($this->getID());
			}
			return (bool) $this->_number_of_projects;
		}
		
		public function getNumberOfProjects()
		{
			return $this->_number_of_projects;
		}
		
	}

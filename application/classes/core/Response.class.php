<?php

	namespace thebuggenie\core;
	
	use caspar\core\Caspar,
		caspar\core\Event;
	
	class Response extends \caspar\core\Response
	{

		/**
		 * Breadcrumb trail for the current page
		 * 
		 * @var array
		 */
		protected $_breadcrumb = null;
		
		public function getPredefinedBreadcrumbLinks($type, $project = null)
		{
			$i18n = Caspar::getI18n();
			$links = array();
			switch ($type)
			{
				case 'main_links':
					$links[] = array('url' => Caspar::getRouting()->generate('home'), 'title' => $i18n->__('Frontpage'));
					$links[] = array('url' => Caspar::getRouting()->generate('dashboard'), 'title' => $i18n->__('Personal dashboard'));
					$links[] = array('title' => $i18n->__('Issues'));
					$links[] = array('title' => $i18n->__('Teams'));
					$links[] = array('title' => $i18n->__('Clients'));
					$links = Event::createNew('core', 'breadcrumb_main_links', null, array(), $links)->trigger()->getReturnList();

					if (Caspar::getUser()->canAccessConfigurationPage())
					{
						$links[] = array('url' => make_url('configure'), 'title' => $i18n->__('Configure The Bug Genie'));
					}
					$links[] = array('url' => Caspar::getRouting()->generate('about'), 'title' => $i18n->__('About %sitename%', array('%sitename%' => Settings::getTBGname())));
					$links[] = array('url' => Caspar::getRouting()->generate('account'), 'title' => $i18n->__('Account details'));

					break;
				case 'project_summary':
					$links[] = array('url' => Caspar::getRouting()->generate('project_dashboard', array('project_key' => $project->getKey())), 'title' => $i18n->__('Dashboard'));
					$links[] = array('url' => Caspar::getRouting()->generate('project_planning', array('project_key' => $project->getKey())), 'title' => $i18n->__('Planning'));
					$links[] = array('url' => Caspar::getRouting()->generate('project_roadmap', array('project_key' => $project->getKey())), 'title' => $i18n->__('Roadmap'));
					$links[] = array('url' => Caspar::getRouting()->generate('project_team', array('project_key' => $project->getKey())), 'title' => $i18n->__('Team overview'));
					$links[] = array('url' => Caspar::getRouting()->generate('project_statistics', array('project_key' => $project->getKey())), 'title' => $i18n->__('Statistics'));
					$links[] = array('url' => Caspar::getRouting()->generate('project_timeline', array('project_key' => $project->getKey())), 'title' => $i18n->__('Timeline'));
					$links[] = array('url' => Caspar::getRouting()->generate('project_reportissue', array('project_key' => $project->getKey())), 'title' => $i18n->__('Report an issue'));
					$links[] = array('url' => Caspar::getRouting()->generate('project_issues', array('project_key' => $project->getKey())), 'title' => $i18n->__('Issues'));
					$links = Event::createNew('core', 'breadcrumb_project_links', null, array(), $links)->trigger()->getReturnList();
					$links[] = array('url' => Caspar::getRouting()->generate('project_settings', array('project_key' => $project->getKey())), 'title' => $i18n->__('Settings'));
					$links[] = array('url' => Caspar::getRouting()->generate('project_release_center', array('project_key' => $project->getKey())), 'title' => $i18n->__('Release center'));
					break;
				case 'client_list':
					foreach (TBGClient::getAll() as $client)
					{
						if ($client->hasAccess())
							$links[] = array('url' => Caspar::getRouting()->generate('client_dashboard', array('client_id' => $client->getID())), 'title' => $client->getName());
					}
					break;
				case 'team_list':
					foreach (TBGTeam::getAll() as $team)
					{
						if ($team->hasAccess())
							$links[] = array('url' => Caspar::getRouting()->generate('team_dashboard', array('team_id' => $team->getID())), 'title' => $team->getName());
					}
					break;
			}

			return $links;
		}
		
		/**
		 * Set the breadcrumb trail for the current page
		 * 
		 * @param array $breadcrumb 
		 */
		public function setBreadcrumb($breadcrumb)
		{
			$this->_breadcrumb = $breadcrumb;
		}

		/**
		 * Add to the breadcrumb trail for the current page
		 * 
		 * @param string $breadcrumb 
		 * @param string $url[optional]
		 */
		public function addBreadcrumb($breadcrumb, $url = null, $subitems = null, $class = null)
		{
			if ($this->_breadcrumb === null)
				$this->_breadcrumb = array();

			$this->_breadcrumb[] = array('title' => $breadcrumb, 'url' => $url, 'subitems' => $subitems, 'class' => $class);
		}

	}
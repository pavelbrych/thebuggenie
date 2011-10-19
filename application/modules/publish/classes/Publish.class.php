<?php

	namespace application\modules\publish;

	use caspar\core\Request,
		caspar\core\Event,
		caspar\core\Caspar,
		caspar\core\ActionComponents,
		thebuggenie\core\Context;
	
	/**
	 * The wiki class
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 3.1
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage publish
	 */

	/**
	 * The wiki class
	 *
	 * @package thebuggenie
	 * @subpackage publish
	 */
	class Publish extends \thebuggenie\core\Module
	{
		
		const PERMISSION_READ_ARTICLE = 'readarticle';
		const PERMISSION_EDIT_ARTICLE = 'editarticle';
		const PERMISSION_DELETE_ARTICLE = 'deletearticle';

		protected $_longname = 'Wiki';
		
		protected $_description = 'Enables Wiki-functionality';
		
		protected $_module_config_title = 'Wiki';
		
		protected $_module_config_description = 'Set up the Wiki module from this section';
		
		protected $_has_config_settings = true;
		
		protected $_module_version = '1.0';

		protected function _initialize()
		{
			if ($this->isEnabled() && $this->getSetting('allow_camelcase_links'))
			{
				\thebuggenie\core\TextParser::addRegex('/(?<![\!|\"|\[|\>|\/\:])\b[A-Z]+[a-z]+[A-Z][A-Za-z]*\b/', array($this, 'getArticleLinkTag'));
				\thebuggenie\core\TextParser::addRegex('/(?<!")\![A-Z]+[a-z]+[A-Z][A-Za-z]*\b/', array($this, 'stripExclamationMark'));
			}
		}

		protected function _addListeners()
		{
			Event::listen('core', 'index_left', array($this, 'listen_frontpageLeftmenu'));
			Event::listen('core', 'index_right_top', array($this, 'listen_frontpageArticle'));
			if ($this->isWikiTabsEnabled())
			{
				Event::listen('core', 'project_overview_item_links', array($this, 'listen_projectLinks'));
				Event::listen('core', 'menustrip_item_links', array($this, 'listen_MenustripLinks'));
				Event::listen('core', 'breadcrumb_main_links', array($this, 'listen_BreadcrumbMainLinks'));
				Event::listen('core', 'breadcrumb_project_links', array($this, 'listen_BreadcrumbProjectLinks'));
			}
			Event::listen('core', '\thebuggenie\entities\Project::createNew', array($this, 'listen_createNewProject'));
			Event::listen('core', 'upload', array($this, 'listen_upload'));
			Event::listen('core', 'quicksearch_dropdown_firstitems', array($this, 'listen_quicksearchDropdownFirstItems'));
			Event::listen('core', 'quicksearch_dropdown_founditems', array($this, 'listen_quicksearchDropdownFoundItems'));
		}

		protected function _install($scope)
		{
			Context::setPermission('article_management', 0, 'publish', 0, 1, 0, true, $scope);
			Context::setPermission('publish_postonglobalbillboard', 0, 'publish', 0, 1, 0, true, $scope);
			Context::setPermission('publish_postonteambillboard', 0, 'publish', 0, 1, 0, true, $scope);
			Context::setPermission('manage_billboard', 0, 'publish', 0, 1, 0, true, $scope);
			$this->saveSetting('allow_camelcase_links', 1);

			Caspar::getRouting()->addRoute('publish_article', '/wiki/:article_name', 'publish', 'showArticle');
			\application\core\TextParser::addRegex('/(?<![\!|\"|\[|\>|\/\:])\b[A-Z]+[a-z]+[A-Z][A-Za-z]*\b/', array($this, 'getArticleLinkTag'));
			\application\core\TextParser::addRegex('/(?<!")\![A-Z]+[a-z]+[A-Z][A-Za-z]*\b/', array($this, 'stripExclamationMark'));
		}
		
		public function loadFixturesArticles($scope, $overwrite = true)
		{
			if (Context::isCLI()) TBGCliCommand::cli_echo("Loading default articles\n");
			$this->loadArticles('', $overwrite, $scope);
			if (Context::isCLI()) TBGCliCommand::cli_echo("... done\n");
		}
		
		public function loadArticles($namespace = '', $overwrite = true, $scope = null)
		{
			$scope = Context::getScope()->getID();
			$namespace = mb_strtolower($namespace);
			$_path_handle = opendir(THEBUGGENIE_MODULES_PATH . 'publish' . DS . 'fixtures' . DS);
			while ($original_article_name = readdir($_path_handle))
			{
				if (mb_strpos($original_article_name, '.') === false)
				{
					$article_name = mb_strtolower($original_article_name);
					$imported = false;
					$import = false;
					if ($namespace)
					{
						if (mb_strpos(urldecode($article_name), "{$namespace}:") === 0 || (mb_strpos(urldecode($article_name), "category:") === 0 && mb_strpos(urldecode($article_name), "{$namespace}:") === 9))
						{
							$import = true;
						}
					}
					else
					{
						if (mb_strpos(urldecode($article_name), "category:help:") === 0)
						{
							$name_test = mb_substr(urldecode($article_name), 14);
						}
						elseif (mb_strpos(urldecode($article_name), "category:") === 0)
						{
							$name_test = mb_substr(urldecode($article_name), 9);
						}
						else
						{
							$name_test = urldecode($article_name);
						}
						if (mb_strpos($name_test, ':') === false) 
							$import = true;
					}
					if ($import)
					{
						if (Context::isCLI())
						{
							TBGCliCommand::cli_echo('Saving '.urldecode($original_article_name)."\n");
						}
						if ($overwrite)
						{
							TBGArticlesTable::getTable()->deleteArticleByName(urldecode($original_article_name));
						}
						if (TBGArticlesTable::getTable()->getArticleByName(urldecode($original_article_name)) === null)
						{
							$content = file_get_contents(THEBUGGENIE_MODULES_PATH . 'publish' . DS . 'fixtures' . DS . $original_article_name);
							TBGWikiArticle::createNew(urldecode($original_article_name), $content, true, $scope, array('overwrite' => $overwrite, 'noauthor' => true));
							$imported = true;
						}
						Event::createNew('publish', 'fixture_article_loaded', urldecode($original_article_name), array('imported' => $imported))->trigger();
					}
				}
			}
		}

		protected function _loadFixtures($scope)
		{
			$this->loadFixturesArticles($scope);

			TBGLinksTable::getTable()->addLink('wiki', 0, 'MainPage', 'Wiki Frontpage', 1, $scope);
			TBGLinksTable::getTable()->addLink('wiki', 0, 'WikiFormatting', 'Formatting help', 2, $scope);
			TBGLinksTable::getTable()->addLink('wiki', 0, 'Category:Help', 'Help topics', 3, $scope);
			Context::setPermission(self::PERMISSION_READ_ARTICLE, 0, 'publish', 0, 1, 0, true, $scope);
			Context::setPermission(self::PERMISSION_EDIT_ARTICLE, 0, 'publish', 0, 1, 0, true, $scope);
			Context::setPermission(self::PERMISSION_DELETE_ARTICLE, 0, 'publish', 0, 1, 0, true, $scope);
		}
		
		protected function _uninstall()
		{
			if (Context::getScope()->getID() == 1)
			{
				TBGArticlesTable::getTable()->drop();
				Caspar::getB2DBInstance()->getTable('TBGBillboardPostsTable')->drop();
			}
			TBGLinksTable::getTable()->removeByTargetTypeTargetIDandLinkID('wiki', 0);
			parent::_uninstall();
		}

		public function getRoute()
		{
			return Caspar::getRouting()->generate('publish');
		}

		public function hasProjectAwareRoute()
		{
			return true;
		}

		public function getProjectAwareRoute($project_key)
		{
			return Caspar::getRouting()->generate('publish_article', array('article_name' => ucfirst($project_key).":MainPage"));
		}

		public function isWikiTabsEnabled()
		{
			return (bool) ($this->getSetting('hide_wiki_links') != 1);
		}

		public function postConfigSettings(Request $request)
		{
			if ($request->hasParameter('import_articles'))
			{
				$cc = 0;
				foreach ($request->getParameter('import_article') as $article_name => $import)
				{
					$cc++;
					TBGArticlesTable::getTable()->deleteArticleByName(urldecode($article_name));
					$content = file_get_contents(THEBUGGENIE_MODULES_PATH . 'publish' . DS . 'fixtures' . DS . $article_name);
					TBGWikiArticle::createNew(urldecode($article_name), $content, true, null, array('overwrite' => true, 'noauthor' => true));
				}
				Context::setMessage('module_message', Caspar::getI18n()->__('%number_of_articles% articles imported successfully', array('%number_of_articles%' => $cc)));
			}
			else
			{
				$settings = array('allow_camelcase_links', 'menu_title', 'hide_wiki_links', 'free_edit');
				foreach ($settings as $setting)
				{
					if ($request->hasParameter($setting))
					{
						$this->saveSetting($setting, $request->getParameter($setting));
					}
				}
			}
		}

		public function getMenuTitle($project_context = null)
		{
			$project_context = ($project_context !== null) ? $project_context : Context::isProjectContext();
			$i18n = Caspar::getI18n();
			if (($menu_title = $this->getSetting('menu_title')) !== null)
			{
				switch ($menu_title)
				{
					case 5: return ($project_context) ? $i18n->__('Project archive') : $i18n->__('Archive') ;
					case 3: return ($project_context) ? $i18n->__('Project documentation') : $i18n->__('Documentation');
					case 4: return ($project_context) ? $i18n->__('Project documents') : $i18n->__('Documents');
					case 2: return ($project_context) ? $i18n->__('Project help') : $i18n->__('Help');
				}

			}
			return ($project_context) ? $i18n->__('Project wiki') : $i18n->__('Wiki');
		}

		public function getSpacedName($camelcased)
		{
			return preg_replace('/(?<=[a-z])(?=[A-Z])/',' ', $camelcased);
		}

		public function stripExclamationMark($matches)
		{
			return mb_substr($matches[0], 1);
		}

		public function getArticleLinkTag($matches)
		{
			$article_name = $matches[0];
			if (\thebuggenie\core\TextParser::getCurrentParser() instanceof \thebuggenie\core\TextParser)
				\thebuggenie\core\TextParser::getCurrentParser()->addInternalLinkOccurrence($article_name);
			$article_name = $this->getSpacedName($matches[0]);
			if (!Context::isCLI())
			{
				Caspar::loadLibrary('ui');
				return link_tag(make_url('publish_article', array('article_name' => $matches[0])), $article_name);
			}
			else
			{
				return $matches[0];
			}
		}

		public function getLatestArticles($limit = 5)
		{
			return TBGArticlesTable::getTable()->getArticles($limit, true);
		}
	
		public function getMenuItems($target_id = 0)
		{
			return TBGLinksTable::getTable()->getLinks('wiki', $target_id);
		}

		public function getUserDrafts()
		{
			$articles = array();

			if ($res = TBGArticlesTable::getTable()->getUnpublishedArticlesByUser(Context::getUser()->getID()))
			{
				while ($row = $res->getNextRow())
				{
					try
					{
						$article = PublishFactory::article($row->get(TBGArticlesTable::ID), $row);
					}
					catch (Exception $e)
					{
						continue;
					}

					if ($article->hasAccess())
					{
						$articles[] = $article;
					}
				}
			}

			return $articles;
		}
		
		public function getFrontpageArticle($type)
		{
			$article_name = ($type == 'main') ? 'FrontpageArticle' : 'FrontpageLeftmenu';
			if ($row = TBGArticlesTable::getTable()->getArticleByName($article_name))
			{
				return PublishFactory::article($row->get(TBGArticlesTable::ID), $row);
			}
			return null;
		}
		
		public function listen_frontpageArticle(Event $event)
		{
			$article = $this->getFrontpageArticle('main');
			if ($article instanceof TBGWikiArticle)
			{
				ActionComponents::includeComponent('publish/articledisplay', array('article' => $article, 'show_title' => false, 'show_details' => false, 'show_actions' => false, 'embedded' => true));
			}
		}

		public function listen_frontpageLeftmenu(Event $event)
		{
			$article = $this->getFrontpageArticle('menu');
			if ($article instanceof TBGWikiArticle)
			{
				ActionComponents::includeComponent('publish/articledisplay', array('article' => $article, 'show_title' => false, 'show_details' => false, 'show_actions' => false, 'embedded' => true));
			}
		}

		public function listen_projectLinks(Event $event)
		{
			ActionComponents::includeTemplate('publish/projectlinks', array('project' => $event->getSubject()));
		}

		public function listen_BreadcrumbMainLinks(Event $event)
		{
			$link = array('url' => Caspar::getRouting()->generate('publish'), 'title' => $this->getMenuTitle(Context::isProjectContext()));
			$event->addToReturnList($link);
		}
		
		public function listen_BreadcrumbProjectLinks(Event $event)
		{
			$link = array('url' => Caspar::getRouting()->generate('publish_article', array('article_name' => Context::getCurrentProject()->getKey() . ':MainPage')), 'title' => $this->getMenuTitle(true));
			$event->addToReturnList($link);
		}

		public function listen_MenustripLinks(Event $event)
		{
			$project_url = (Context::isProjectContext()) ? Caspar::getRouting()->generate('publish_article', array('article_name' => ucfirst(Context::getCurrentProject()->getKey()).':MainPage')) : null;
			$url = Caspar::getRouting()->generate('publish');
			ActionComponents::includeTemplate('publish/menustriplinks', array('url' => $url, 'project_url' => $project_url, 'selected_tab' => $event->getParameter('selected_tab')));
		}

		public function listen_createNewProject(Event $event)
		{
			if (!TBGWikiArticle::getByName(ucfirst($event->getSubject()->getKey()).':MainPage') instanceof TBGWikiArticle)
			{
				$project_key = $event->getSubject()->getKey();
				$article = TBGWikiArticle::createNew("{$project_key}:MainPage", "This is the wiki frontpage for {$event->getSubject()->getName()} \n\n[[Category:{$project_key}:About]]", true);
				$this->loadArticles($project_key);
			}
		}

		public function getTabKey()
		{
			return (Context::isProjectContext()) ? parent::getTabKey() : 'wiki';
		}

		protected function _checkArticlePermissions($article_name, $permission_name)
		{
			$user = Context::getUser();
			switch ($this->getSetting('free_edit'))
			{
				case 1:
					$permissive = !$user->isGuest();
					break;
				case 2:
					$permissive = true;
					break;
				case 0:
				default:
					$permissive = false;
					break;
			}
			if ($user->hasPermission($permission_name, $article_name, 'publish', true, $permissive))
			{
				return true;
			}
			$namespaces = explode(':', $article_name);
			if (count($namespaces) > 1)
			{
				array_pop($namespaces);
				$composite_ns = '';
				foreach ($namespaces as $namespace)
				{
					$composite_ns .= ($composite_ns != '') ? ":{$namespace}" : $namespace;
					if ($user->hasPermission($permission_name, $composite_ns, 'publish', true, $permissive))
					{
						return true;
					}
				}
			}
			return $user->hasPermission($permission_name, 0, 'publish', false, $permissive);
		}
		
		public function canUserReadArticle($article_name)
		{
			return $this->_checkArticlePermissions($article_name, self::PERMISSION_READ_ARTICLE);
		}
		
		public function canUserEditArticle($article_name)
		{
			return $this->_checkArticlePermissions($article_name, self::PERMISSION_EDIT_ARTICLE);
		}
		
		public function canUserDeleteArticle($article_name)
		{
			return $this->_checkArticlePermissions($article_name, self::PERMISSION_DELETE_ARTICLE);
		}
		
		public function listen_quicksearchDropdownFirstItems(Event $event)
		{
			$searchterm = $event->getSubject();
			ActionComponents::includeTemplate('publish/quicksearch_dropdown_firstitems', array('searchterm' => $searchterm));
		}
		
		public function listen_quicksearchDropdownFoundItems(Event $event)
		{
			$searchterm = $event->getSubject();
			list ($resultcount, $articles) = TBGWikiArticle::findArticlesByContentAndProject($searchterm, Context::getCurrentProject());
			ActionComponents::includeTemplate('publish/quicksearch_dropdown_founditems', array('searchterm' => $searchterm, 'articles' => $articles, 'resultcount' => $resultcount));
		}
		
	}

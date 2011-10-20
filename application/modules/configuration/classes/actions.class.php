<?php

	class configurationActions extends TBGAction
	{
		
		/**
		 * Pre-execute function
		 * 
		 * @param TBGRequest 	$request
		 * @param string		$action
		 */
		public function preExecute(Request $request, $action)
		{
			// forward 403 if you're not allowed here
			if ($request->isAjaxCall() == false) // for avoiding empty error when an user disables himself its own permissions
			{
				$this->forward403unless(\caspar\core\Caspar::getUser()->canAccessConfigurationPage());
			}
			
			$this->access_level = $this->getAccessLevel($request->getParameter('section'), 'core');
			
			$this->getResponse()->setPage('config');
			\core\caspar\Caspar::loadLibrary('ui');
			$this->getResponse()->addBreadcrumb(TBGContext::getI18n()->__('Configure The Bug Genie'), TBGContext::getRouting()->generate('configure'), $this->getResponse()->getPredefinedBreadcrumbLinks('main_links'));
			
		}
		
		/**
		 * Configuration main page
		 * 
		 * @param TBGRequest $request
		 */
		public function runIndex(Request $request)
		{
			$i18n = TBGContext::getI18n();
			$general_config_sections = array();
			$data_config_sections = array();
			$module_config_sections = array();
			
			if (\caspar\core\Caspar::getUser()->getScope()->getID() == 1)
				$general_config_sections[TBGSettings::CONFIGURATION_SECTION_SCOPES] = array('route' => 'configure_scopes', 'description' => $i18n->__('Scopes'), 'icon' => 'scopes', 'details' => $i18n->__('Scopes are self-contained Bug Genie environments. Configure them here.'));

			$general_config_sections[TBGSettings::CONFIGURATION_SECTION_SETTINGS] = array('route' => 'configure_settings', 'description' => $i18n->__('Settings'), 'icon' => 'general', 'details' => $i18n->__('Every setting in the bug genie can be adjusted in this section.'));
			$general_config_sections[TBGSettings::CONFIGURATION_SECTION_PERMISSIONS] = array('route' => 'configure_permissions', 'description' => $i18n->__('Permissions'), 'icon' => 'permissions', 'details' => $i18n->__('Configure permissions in this section'));
			$general_config_sections[TBGSettings::CONFIGURATION_SECTION_AUTHENTICATION] = array('route' => 'configure_authentication', 'description' => $i18n->__('Authentication'), 'icon' => 'authentication', 'details' => $i18n->__('Configure the authentication method in this section'));
			
			if (\thebuggenie\core\Context::getScope()->isUploadsEnabled())
				$general_config_sections[TBGSettings::CONFIGURATION_SECTION_UPLOADS] = array('route' => 'configure_files', 'description' => $i18n->__('Uploads &amp; attachments'), 'icon' => 'files', 'details' => $i18n->__('All settings related to file uploads are controlled from this section.'));

			$data_config_sections[TBGSettings::CONFIGURATION_SECTION_IMPORT] = array('route' => 'configure_import', 'description' => $i18n->__('Import data'), 'icon' => 'import', 'details' => $i18n->__('Import data from CSV files and other sources.'));
			$data_config_sections[TBGSettings::CONFIGURATION_SECTION_PROJECTS] = array('route' => 'configure_projects', 'description' => $i18n->__('Projects'), 'icon' => 'projects', 'details' => $i18n->__('Set up all projects in this configuration section.'));
			$data_config_sections[TBGSettings::CONFIGURATION_SECTION_ISSUETYPES] = array('icon' => 'issuetypes', 'description' => $i18n->__('Issue types'), 'route' => 'configure_issuetypes', 'details' => $i18n->__('Manage issue types and configure issue fields for each issue type here'));
			$data_config_sections[TBGSettings::CONFIGURATION_SECTION_ISSUEFIELDS] = array('icon' => 'resolutiontypes', 'description' => $i18n->__('Issue fields'), 'route' => 'configure_issuefields', 'details' => $i18n->__('Status types, resolution types, categories, custom fields, etc. are configurable from this section.'));
			$data_config_sections[TBGSettings::CONFIGURATION_SECTION_WORKFLOW] = array('icon' => 'workflow', 'description' => $i18n->__('Workflow'), 'route' => 'configure_workflow', 'details' => $i18n->__('Set up and edit workflow configuration from this section'));
			$data_config_sections[TBGSettings::CONFIGURATION_SECTION_USERS] = array('route' => 'configure_users', 'description' => $i18n->__('Users, teams, clients &amp; groups'), 'icon' => 'users', 'details' => $i18n->__('Manage users, user groups, clients and user teams from this section.'));
			$module_config_sections[TBGSettings::CONFIGURATION_SECTION_MODULES][] = array('route' => 'configure_modules', 'description' => $i18n->__('Module settings'), 'icon' => 'modules', 'details' => $i18n->__('Manage Bug Genie extensions from this section. New modules are installed from here.'), 'module' => 'core');
			foreach (TBGContext::getModules() as $module)
			{
				if ($module->hasConfigSettings() && $module->isEnabled())
					$module_config_sections[TBGSettings::CONFIGURATION_SECTION_MODULES][] = array('route' => array('configure_module', array('config_module' => $module->getName())), 'description' => TBGContext::geti18n()->__($module->getConfigTitle()), 'icon' => $module->getName(), 'details' => TBGContext::geti18n()->__($module->getConfigDescription()), 'module' => $module->getName());
			}
			$this->general_config_sections = $general_config_sections; 
			$this->data_config_sections = $data_config_sections;
			$this->module_config_sections = $module_config_sections;
			$this->outdated_modules = TBGContext::getOutdatedModules();
		}
		
		/**
		 * check for updates
		 * 
		 * @param TBGRequest $request
		 */
		public function runCheckUpdates(Request $request)
		{
			$data = json_decode(file_get_contents('http://www.thebuggenie.com/updatecheck.php'));
			if (!is_object($data))
			{
				$this->getResponse()->setHttpStatus(500);
				return $this->renderJSON(array('failed' => true, 'title' => TBGContext::getI18n()->__('Failed to check for updates'), 'message' => TBGContext::getI18n()->__('The response from The Bug Genie website was invalid')));
			}
			
			$outofdate = false;
			
			// major
			if ($data->maj > TBGSettings::getMajorVer())
			{
				$outofdate = true;
			}
			elseif ($data->min > TBGSettings::getMinorVer() && ($data->maj == TBGSettings::getMajorVer()))
			{
				$outofdate = true;
			}
			elseif ($data->rev > TBGSettings::getRevision() && ($data->maj == TBGSettings::getMajorVer()) && ($data->min == TBGSettings::getMinorVer()))
			{
				$outofdate = true;
			}
			
			if (!$outofdate)
			{
				return $this->renderJSON(array('failed' => false, 'uptodate' => true, 'title' => TBGContext::getI18n()->__('The Bug Genie is up to date'), 'message' => TBGContext::getI18n()->__('The latest version is %ver%', array('%ver%' => $data->nicever))));
			}
			else
			{
				return $this->renderJSON(array('failed' => false, 'uptodate' => false, 'title' => TBGContext::getI18n()->__('The Bug Genie is out of date'), 'message' => TBGContext::getI18n()->__('The latest version is %ver%. Update now from www.thebuggenie.com.', array('%ver%' => $data->nicever))));
			}
		}
		
		/**
		 * Configuration import page
		 * 
		 * @param TBGRequest $request
		 */
		public function runImport(Request $request)
		{
			if ($request->isMethod(TBGRequest::POST))
			{
				if ($request->getParameter('import_sample_data'))
				{
					ini_set('memory_limit','64M');
					$users = array();
					
					$user1 = new TBGUser();
					$user1->setUsername('john');
					$user1->setPassword('john');
					$user1->setBuddyname('John');
					$user1->setRealname('John');
					$user1->setActivated();
					$user1->setEnabled();
					$user1->save();
					$users[] = $user1;
					
					$user2 = new TBGUser();
					$user2->setUsername('jane');
					$user2->setPassword('jane');
					$user2->setBuddyname('Jane');
					$user2->setRealname('Jane');
					$user2->setActivated();
					$user2->setEnabled();
					$user2->save();
					$users[] = $user2;
					
					$user3 = new TBGUser();
					$user3->setUsername('jackdaniels');
					$user3->setPassword('jackdaniels');
					$user3->setBuddyname('Jack');
					$user3->setRealname('Jack Daniels');
					$user3->setActivated();
					$user3->setEnabled();
					$user3->save();
					$users[] = $user3;
					
					$project1 = new TBGProject();
					$project1->setName('Sample project 1');
					$project1->setOwner($users[rand(0, 2)]);
					$project1->setLeader($users[rand(0, 2)]);
					$project1->setQaResponsible($users[rand(0, 2)]);
					$project1->setDescription('This is a sample project that is awesome. Try it out!');
					$project1->setHomepage('http://www.google.com');
					$project1->save();
					
					$project2 = new TBGProject();
					$project2->setName('Sample project 2');
					$project2->setOwner($users[rand(0, 2)]);
					$project2->setLeader($users[rand(0, 2)]);
					$project2->setQaResponsible($users[rand(0, 2)]);
					$project2->setDescription('This is the second sample project. Not as awesome as the first one, but still worth a try!');
					$project2->setHomepage('http://www.bing.com');
					$project2->save();

					foreach (array($project1, $project2) as $project)
					{
						for ($cc = 1; $cc <= 5; $cc++)
						{
							$milestone = new TBGMilestone();
							$milestone->setName("Milestone {$cc}");
							$milestone->setProject($project);
							$milestone->setType(TBGMilestone::TYPE_REGULAR);
							if ((bool) rand(0,1))
							{
								$milestone->setScheduledDate(NOW + (100000 * (20 * $cc)));
							}
							$milestone->save();
						}
					}
					
					$p1_milestones = $project1->getMilestones();
					$p2_milestones = $project2->getMilestones();
					
					$issues = array();
					$priorities = TBGPriority::getAll();
					$categories = TBGCategory::getAll();
					$severities = TBGSeverity::getAll();
					$statuses = TBGStatus::getAll();
					$reproducabilities = TBGReproducability::getAll();
					$lorem_ipsum = \caspar\core\Caspar::getB2DBInstance()->getTable('\\application\\modules\\publish\\tables\\Articles')->getArticleByName('LoremIpsum');
					$lorem_ipsum = PublishFactory::article($lorem_ipsum->get(\application\modules\publish\tables\Articles::ID), $lorem_ipsum);
					$lorem_words = explode(' ', $lorem_ipsum->getContent());
					
					foreach (array('bugreport', 'featurerequest', 'enhancement', 'idea') as $issuetype)
					{
						$issuetype = TBGIssuetype::getIssuetypeByKeyish($issuetype);
						for ($cc = 1; $cc <= 10; $cc++)
						{
							$issue1 = new TBGIssue();
							$issue1->setProject($project1);
							$issue1->setPostedBy($users[rand(0, 2)]);
							$issue1->setPosted(NOW - (86400 * rand(1, 30)));
							$title_string = '';
							$description_string = '';
							$rand_length = rand(4, 15);
							$ucnext = true;
							for ($ll = 1; $ll <= $rand_length; $ll++)
							{
								$word = str_replace(array(',', '.', "\r", "\n"), array('', '', '', ''), $lorem_words[array_rand($lorem_words)]);
								$word = ($ucnext || (rand(1, 40) == 19)) ? ucfirst($word) : mb_strtolower($word);
								$title_string .= $word;
								$ucnext = false;
								if ($ll == $rand_length || rand(1, 15) == 5) 
								{
									$title_string .= '.';
									$ucnext = true;
								}
								$title_string .= ' ';
							}
							$rand_length = rand(40, 500);
							$ucnext = true;
							for ($ll = 1; $ll <= $rand_length; $ll++)
							{
								$word = str_replace(array(',', '.', "\r", "\n"), array('', '', '', ''), $lorem_words[array_rand($lorem_words)]);
								$word = ($ucnext || (rand(1, 40) == 19)) ? ucfirst($word) : mb_strtolower($word);
								$description_string .= $word;
								$ucnext = false;
								if ($ll == $rand_length || rand(1, 15) == 5) 
								{
									$description_string .= '.';
									$ucnext = true;
									$description_string .= ($ll != $rand_length && rand(1, 15) == 8) ? "\n\n" : ' ';
								}
								else
								{
									$description_string .= ' ';
								}
							}
							$issue1->setTitle(ucfirst($title_string));
							$issue1->setDescription($description_string);
							$issue1->setIssuetype($issuetype);
							$issue1->setMilestone($p1_milestones[array_rand($p1_milestones)]);
							$issue1->setPriority($priorities[array_rand($priorities)]);
							$issue1->setCategory($categories[array_rand($categories)]);
							$issue1->setSeverity($severities[array_rand($severities)]);
							$issue1->setReproducability($reproducabilities[array_rand($reproducabilities)]);
							$issue1->setPercentCompleted(rand(0, 100));
							$issue1->save();
							$issue1->setStatus($statuses[array_rand($statuses)]);
							if (rand(0, 1)) $issue1->setAssignee($users[array_rand($users)]);
							$issue1->save();
							$issues[] = $issue1;

							$issue2 = new TBGIssue();
							$issue2->setProject($project2);
							$issue2->setPostedBy($users[rand(0, 2)]);
							$issue2->setPosted(NOW - (86400 * rand(1, 30)));
							$title_string = '';
							$description_string = '';
							$rand_length = rand(4, 15);
							$ucnext = true;
							for ($ll = 1; $ll <= $rand_length; $ll++)
							{
								$word = str_replace(array(',', '.', "\r", "\n"), array('', '', '', ''), $lorem_words[array_rand($lorem_words)]);
								$word = ($ucnext || (rand(1, 40) == 19)) ? ucfirst($word) : mb_strtolower($word);
								$title_string .= $word;
								$ucnext = false;
								if ($ll == $rand_length || rand(1, 15) == 5) 
								{
									$title_string .= '.';
									$ucnext = true;
								}
								$title_string .= ' ';
							}
							$rand_length = rand(40, 500);
							$ucnext = true;
							for ($ll = 1; $ll <= $rand_length; $ll++)
							{
								$word = str_replace(array(',', '.', "\r", "\n"), array('', '', '', ''), $lorem_words[array_rand($lorem_words)]);
								$word = ($ucnext || (rand(1, 40) == 19)) ? ucfirst($word) : mb_strtolower($word);
								$description_string .= $word;
								$ucnext = false;
								if ($ll == $rand_length || rand(1, 15) == 5) 
								{
									$description_string .= '.';
									$ucnext = true;
									$description_string .= ($ll != $rand_length && rand(1, 15) == 8) ? "\n\n" : ' ';
								}
								else
								{
									$description_string .= ' ';
								}
							}
							$issue2->setTitle(ucfirst($title_string));
							$issue2->setDescription($description_string);
							$issue2->setIssuetype($issuetype);
							$issue2->setMilestone($p2_milestones[array_rand($p2_milestones)]);
							$issue2->setPriority($priorities[array_rand($priorities)]);
							$issue2->setCategory($categories[array_rand($categories)]);
							$issue2->setSeverity($severities[array_rand($severities)]);
							$issue2->setReproducability($reproducabilities[array_rand($reproducabilities)]);
							$issue2->setPercentCompleted(rand(0, 100));
							if (rand(0, 1)) $issue1->setAssignee($users[array_rand($users)]);
							$issue2->save();
							$issue2->setStatus($statuses[array_rand($statuses)]);
							$issue2->save();
							$issues[] = $issue2;
						}
					}
					
					$rand_issues_to_close = rand(8, 40);
					$resolutions = TBGResolution::getAll();
					
					for ($cc = 1; $cc <= $rand_issues_to_close; $cc++)
					{
						$issue = array_slice($issues, array_rand($issues), 1);
						$issue = $issue[0];
						$issue->setResolution($resolutions[array_rand($resolutions)]);
						$issue->close();
						$issue->save();
					}
					
					$this->imported_data = true;

					$developer = TBGProjectAssigneesTable::getByType(TBGProjectAssigneesTable::TYPE_DEVELOPER);
					foreach (array($project1, $project2) as $project)
					{
						foreach ($users as $user)
						{
							$project->addAssignee($user, $developer->getID());
						}
					}
				}
			}

			$project1 = \thebuggenie\entities\Project::getByKey('sampleproject1');
			$project2 = \thebuggenie\entities\Project::getByKey('sampleproject2');
			$this->canimport = (!$project1 instanceof \thebuggenie\entities\Project && !$project2 instanceof \thebuggenie\entities\Project);
		}
		
		/**
		 * Configure general and server settings
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runSettings(Request $request)
		{
			if (\caspar\core\Caspar::getRequest()->isMethod(TBGRequest::POST))
			{
				$this->forward403unless($this->access_level == TBGSettings::ACCESS_FULL);
				$settings = array(TBGSettings::SETTING_THEME_NAME, TBGSettings::SETTING_ALLOW_USER_THEMES, TBGSettings::SETTING_ONLINESTATE, TBGSettings::SETTING_ENABLE_GRAVATARS,
								TBGSettings::SETTING_OFFLINESTATE, TBGSettings::SETTING_AWAYSTATE, TBGSettings::SETTING_AWAYSTATE, TBGSettings::SETTING_IS_SINGLE_PROJECT_TRACKER,
								TBGSettings::SETTING_REQUIRE_LOGIN, TBGSettings::SETTING_ALLOW_REGISTRATION, TBGSettings::SETTING_USER_GROUP,
								TBGSettings::SETTING_RETURN_FROM_LOGIN, TBGSettings::SETTING_RETURN_FROM_LOGOUT, TBGSettings::SETTING_IS_PERMISSIVE_MODE,
								TBGSettings::SETTING_REGISTRATION_DOMAIN_WHITELIST, TBGSettings::SETTING_SHOW_PROJECTS_OVERVIEW, TBGSettings::SETTING_KEEP_COMMENT_TRAIL_CLEAN,
								TBGSettings::SETTING_TBG_NAME, TBGSettings::SETTING_TBG_TAGLINE, TBGSettings::SETTING_DEFAULT_CHARSET, TBGSettings::SETTING_DEFAULT_LANGUAGE,
								TBGSettings::SETTING_SERVER_TIMEZONE, TBGSettings::SETTING_SYNTAX_HIGHLIGHT_DEFAULT_LANGUAGE, TBGSettings::SETTING_SYNTAX_HIGHLIGHT_DEFAULT_INTERVAL,
								TBGSettings::SETTING_SYNTAX_HIGHLIGHT_DEFAULT_NUMBERING, TBGSettings::SETTING_HEADER_ICON_TYPE, TBGSettings::SETTING_FAVICON_TYPE,
								TBGSettings::SETTING_HEADER_ICON_URL, TBGSettings::SETTING_FAVICON_URL, TBGSettings::SETTING_PREVIEW_COMMENT_IMAGES, TBGSettings::SETTING_HEADER_LINK,
								TBGSettings::SETTING_MAINTENANCE_MESSAGE, TBGSettings::SETTING_MAINTENANCE_MODE, TBGSettings::SETTING_ICONSET);
				
				foreach ($settings as $setting)
				{
					if (\caspar\core\Caspar::getRequest()->getParameter($setting) !== null)
					{
						$value = \caspar\core\Caspar::getRequest()->getParameter($setting);
						switch ($setting)
						{
							case TBGSettings::SETTING_TBG_NAME:
							case TBGSettings::SETTING_TBG_TAGLINE:
								$value = \caspar\core\Caspar::getRequest()->getParameter($setting, null, false);
								break;
							case  TBGSettings::SETTING_SYNTAX_HIGHLIGHT_DEFAULT_INTERVAL:
								if (!is_numeric($value) || $value < 1)
								{
									$this->getResponse()->setHttpStatus(400);
									return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please provide a valid setting for highlighting interval')));
								}
								break;
							case TBGSettings::SETTING_DEFAULT_CHARSET:
								\core\caspar\Caspar::loadLibrary('common');
								if ($value && !tbg_check_syntax($value, "CHARSET"))
								{
										$this->getResponse()->setHttpStatus(400);
										return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please provide a valid setting for charset')));
								}
								break;
						}
						TBGSettings::saveSetting($setting, $value);
					}
				}
				return $this->renderJSON(array('failed' => false, 'title' => TBGContext::getI18n()->__('All settings saved')));
			}
		}

		/**
		 * Configure projects
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runConfigureProjects(Request $request)
		{
			$this->allProjects = \thebuggenie\entities\Project::getAll();
		}
		
		/**
		 * Configure issue fields
		 *
		 * @param TBGRequest $request The request object
		 */
		public function runConfigureIssuefields(Request $request)
		{
			$i18n = TBGContext::getI18n();
			$builtin_types = array();
			$builtin_types['status'] = array('description' => $i18n->__('Status types'), 'key' => 'status');
			$builtin_types['resolution'] = array('description' => $i18n->__('Resolution types'), 'key' => 'resolution');
			$builtin_types['priority'] = array('description' => $i18n->__('Priority levels'), 'key' => 'priority');
			$builtin_types['severity'] = array('description' => $i18n->__('Severity levels'), 'key' => 'severity');
			$builtin_types['category'] = array('description' => $i18n->__('Categories'), 'key' => 'category');
			$builtin_types['reproducability'] = array('description' => $i18n->__('Reproducability'), 'key' => 'reproducability');
			// FIXME: editing of project roles should move to Users/Teams/... page?
			$builtin_types['projectrole'] = array('description' => $i18n->__('Project role'), 'key' => 'projectrole');

			$this->builtin_types = $builtin_types;
			$this->custom_types = TBGCustomDatatype::getAll();
		}

		/**
		 * Configure issue fields
		 *
		 * @param TBGRequest $request The request object
		 */
		public function runConfigureIssuetypes(Request $request)
		{
			$this->mode = $request->getParameter('mode', 'issuetypes');
			if ($this->mode == 'issuetypes' || $this->mode == 'scheme')
			{
				$this->issue_types = TBGIssuetype::getAll();
				$this->icons = TBGIssuetype::getIcons();
			}
			elseif ($this->mode == 'schemes')
			{
				$this->issue_type_schemes = TBGIssuetypeScheme::getAll();
			}
			if ($request->hasParameter('scheme_id'))
			{
				$this->scheme = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\IssuetypeScheme', (int) $request->getParameter('scheme_id'));
				if ($this->mode == 'copy_scheme')
				{
					if ($new_name = $request->getParameter('new_name'))
					{
						$new_scheme = new TBGIssuetypeScheme();
						$new_scheme->setName($new_name);
						$new_scheme->save();
						foreach ($this->scheme->getIssuetypes() as $issuetype)
						{
							$new_scheme->setIssuetypeEnabled($issuetype);
							$new_scheme->setIssuetypeRedirectedAfterReporting($issuetype, $this->scheme->isIssuetypeRedirectedAfterReporting($issuetype));
							$new_scheme->setIssuetypeReportable($issuetype, $this->scheme->isIssuetypeReportable($issuetype));
						}
						TBGIssueFieldsTable::getTable()->copyBySchemeIDs($this->scheme->getID(), $new_scheme->getID());
						return $this->renderJSON(array('content' => $this->getTemplateHTML('configuration/issuetypescheme', array('scheme' => $new_scheme))));
					}
					else
					{
						$this->error = TBGContext::getI18n()->__('Please enter a valid name');
					}
				}
				elseif ($this->mode == 'delete_scheme')
				{
					$this->scheme->delete();
					return $this->renderJSON(array('success' => true, 'message' => TBGContext::getI18n()->__('The issuetype scheme was deleted')));
				}
			}
		}

		/**
		 * Get issue type options for a specific issue type
		 *
		 * @param TBGRequest $request
		 */
		public function runConfigureIssuetypesGetOptionsForScheme(Request $request)
		{
			return $this->renderComponent('issuetypeschemeoptions', array('id' => $request->getParameter('id'), 'scheme_id' => $request->getParameter('scheme_id')));
		}

		/**
		 * Perform an action on an issue type
		 * 
		 * @param TBGRequest $request 
		 */
		public function runConfigureIssuetypesAction(Request $request)
		{
			if ($request->hasParameter('scheme_id'))
			{
				$this->scheme = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\IssuetypeScheme', (int) $request->getParameter('scheme_id'));
			}
			$this->forward403unless($this->access_level == TBGSettings::ACCESS_FULL);
			switch ($request->getParameter('mode'))
			{
				case 'add':
					if ($request->getParameter('name'))
					{
						$issuetype = new TBGIssuetype();
						$issuetype->setName($request->getParameter('name'));
						$issuetype->setIcon($request->getParameter('icon'));
						$issuetype->save();
						return $this->renderJSON(array('failed' => false, 'title' => TBGContext::getI18n()->__('Issue type created'), 'content' => $this->getComponentHTML('issuetype', array('type' => $issuetype))));
					}
					return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please provide a valid name for the issue type')));
					break;
				case 'update':
					if (($issuetype = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Issuetype', $request->getParameter('id'))) instanceof TBGIssuetype)
					{
						if ($this->scheme instanceof TBGIssuetypeScheme)
						{
							$this->scheme->setIssuetypeRedirectedAfterReporting($issuetype, $request->getParameter('redirect_after_reporting'));
							$this->scheme->setIssuetypeReportable($issuetype, $request->getParameter('reportable'));
							return $this->renderJSON(array('failed' => false, 'title' => TBGContext::getI18n()->__('The issue type details were updated'), 'description' => $issuetype->getDescription(), 'name' => $issuetype->getName()));
						}
						elseif ($request->getParameter('name'))
						{
							$issuetype->setDescription($request->getParameter('description'));
							$issuetype->setName($request->getParameter('name'));
							$issuetype->setIcon($request->getParameter('icon'));
							$issuetype->save();
							return $this->renderJSON(array('failed' => false, 'title' => TBGContext::getI18n()->__('The issue type was updated'), 'description' => $issuetype->getDescription(), 'name' => $issuetype->getName()));
						}
						else
						{
							return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please provide a valid name for the issue type')));
						}
					}
					return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please provide a valid issue type')));
					break;
				case 'updatechoices':
					if (($issuetype = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Issuetype', $request->getParameter('id'))) instanceof TBGIssuetype)
					{
						$this->scheme->clearAvailableFieldsForIssuetype($issuetype);
						foreach ($request->getParameter('field', array()) as $key => $details)
						{
							$this->scheme->setFieldAvailableForIssuetype($issuetype, $key, $details);
						}
						return $this->renderJSON(array('failed' => false, 'title' => TBGContext::getI18n()->__('Available choices updated')));
					}
					else
					{
						return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please provide a valid issue type')));
					}
					return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Not implemented yet')));
					break;
				case 'delete':
					if (($issuetype = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Issuetype', $request->getParameter('id'))) instanceof TBGIssuetype)
					{
						$issuetype->delete();
						return $this->renderJSON(array('failed' => false, 'message' => TBGContext::getI18n()->__('Issue type deleted')));
					}
					else
					{
						return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please provide a valid issue type')));
					}
					break;
				case 'toggletype':
					if (($issuetype = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Issuetype', $request->getParameter('id'))) instanceof TBGIssuetype)
					{
						if ($this->scheme instanceof TBGIssuetypeScheme)
						{
							$this->scheme->setIssuetypeEnabled($issuetype, ($request->getParameter('state') == 'enable'));
							return $this->renderJSON(array('failed' => false, 'issuetype_id' => $issuetype->getID()));
						}
					}
					return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please provide a valid action for this issue type / scheme')));
					break;
				default:
					return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please provide a valid action for this issue type')));
			}
		}

		/**
		 * Get issue fields list for a specific field type
		 *
		 * @param TBGRequest $request
		 */
		public function runConfigureIssuefieldsGetOptions(Request $request)
		{
			return $this->renderComponent('issuefields', array('type' => $request->getParameter('type'), 'access_level' => $this->access_level));
		}

		/**
		 * Add or delete an issue field option
		 *
		 * @param TBGRequest $request
		 */
		public function runConfigureIssuefieldsAction(Request $request)
		{
			$i18n = TBGContext::getI18n();
			$this->forward403unless($this->access_level == TBGSettings::ACCESS_FULL);
			$types = \thebuggenie\entities\Datatype::getTypes();

			switch ($request->getParameter('mode'))
			{
				case 'add':
					if ($request->getParameter('name'))
					{
						if (array_key_exists($request->getParameter('type'), $types))
						{
							$type_name = $types[$request->getParameter('type')];
							$item = new $type_name();
							$item->setName($request->getParameter('name'));
							$item->setItemdata($request->getParameter('itemdata'));
							$item->save();
						}
						else
						{
							$customtype = TBGCustomDatatype::getByKey($request->getParameter('type'));
							$item = $customtype->createNewOption($request->getParameter('name'), $request->getParameter('value'), $request->getParameter('itemdata'));
						}
						return $this->renderJSON(array('failed' => false, 'title' => TBGContext::getI18n()->__('The option was added'), 'content' => $this->getTemplateHTML('issuefield', array('item' => $item, 'access_level' => $this->access_level, 'type' => $request->getParameter('type')))));
					}
					return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please provide a valid name')));
				case 'edit':
					if ($request->getParameter('name'))
					{
						if (array_key_exists($request->getParameter('type'), $types))
						{
							$labname = $types[$request->getParameter('type')];
							$item = \caspar\core\Caspar::factory()->$labname($request->getParameter('id'));
						}
						else
						{
							$customtype = TBGCustomDatatype::getByKey($request->getParameter('type'));
							$item = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\CustomDatatypeOption', $request->getParameter('id'));
						}
						if ($item instanceof TBGDatatypeBase && $item->getItemtype() == $item->getType())
						{
							$item->setName($request->getParameter('name'));
							$item->setItemdata($request->getParameter('itemdata'));
							if (!$item->isBuiltin())
							{
								$item->setValue($request->getParameter('value'));
							}
							$item->save();
							return $this->renderJSON(array('failed' => false, 'title' => TBGContext::getI18n()->__('The option was updated')));
						}
						else
						{
							return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please provide a valid id')));
						}
					}
					return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please provide a valid name')));
				case 'delete':
					if ($request->hasParameter('id'))
					{
						if (array_key_exists($request->getParameter('type'), $types))
						{
							$classname = 'TBG'.ucfirst($request->getParameter('type'));
							$item = \caspar\core\Caspar::factory()->$classname($request->getParameter('id'))->delete();
							return $this->renderJSON(array('failed' => false, 'title' => $i18n->__('The option was deleted')));
						}
						else
						{
							Caspar::getB2DBInstance()->getTable('TBGCustomFieldOptionsTable')->doDeleteById($request->getParameter('id'));
							return $this->renderJSON(array('failed' => false, 'title' => $i18n->__('The option was deleted')));
						}
					}
					return $this->renderJSON(array('failed' => true, 'error' => $i18n->__('Invalid id or type')));
					break;
			}
		}

		/**
		 * Add or delete a custom type
		 *
		 * @param TBGRequest $request
		 */
		public function runConfigureIssuefieldsCustomTypeAction(Request $request)
		{
			switch ($request->getParameter('mode'))
			{
				case 'add':
					if ($request->getParameter('name') != '')
					{
						try
						{
							$customtype = new TBGCustomDatatype();
							$customtype->setName($request->getParameter('name'));
							$customtype->setItemdata($request->getParameter('label'));
							$customtype->setType($request->getParameter('field_type'));
							$customtype->save();
							return $this->renderJSON(array('failed' => false, 'title' => TBGContext::getI18n()->__('The custom field was added'), 'content' => $this->getComponentHTML('issuefields_customtype', array('type_key' => $customtype->getKey(), 'type' => $customtype))));
						}
						catch (Exception $e)
						{
							return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage() /*TBGContext::getI18n()->__('You need to provide a unique custom field name (key already exists)')*/));
						}
					}
					return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please provide a valid name')));
					break;
				case 'update':
					if ($request->getParameter('name') != '')
					{
						$customtype = TBGCustomDatatype::getByKey($request->getParameter('type'));
						if ($customtype instanceof TBGCustomDatatype)
						{
							$customtype->setDescription($request->getParameter('description'));
							$customtype->setInstructions($request->getParameter('instructions'));
							$customtype->setName($request->getParameter('name'));
							$customtype->save();
							return $this->renderJSON(array('failed' => false, 'title' => TBGContext::getI18n()->__('The custom field was updated'), 'description' => $customtype->getDescription(), 'instructions' => $customtype->getInstructions(), 'name' => $customtype->getName()));
						}
						return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('You need to provide a custom field key that already exists')));
					}
					return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please provide a valid name')));
					break;
				case 'delete':
					$customtype = TBGCustomDatatype::getByKey($request->getParameter('type'));
					if ($customtype instanceof TBGCustomDatatype)
					{
						$customtype->delete();
						return $this->renderJSON(array('failed' => false, 'title' => TBGContext::getI18n()->__('The custom field was deleted')));
					}
					return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('You need to provide a custom field key that already exists')));
					break;
			}
		}

		/**
		 * Configure modules
		 *
		 * @param TBGRequest $request The request object
		 */
		public function runConfigureModules(Request $request)
		{
			$this->module_message = TBGContext::getMessageAndClear('module_message');
			$this->module_error = TBGContext::getMessageAndClear('module_error');
			$this->modules = TBGContext::getModules();
			$this->uninstalled_modules = TBGContext::getUninstalledModules();
			$this->outdated_modules = TBGContext::getOutdatedModules();
		}

		/**
		 * Find users and show selection box
		 * 
		 * @param TBGRequest $request The request object
		 */		
		public function runFindAssignee(Request $request)
		{
			$this->forward403unless($request->isMethod(TBGRequest::POST));

			$this->message = false;
			
			if ($request->getParameter('find_by'))
			{
				$this->theProject = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
				$this->users = TBGUser::findUsers($request->getParameter('find_by'), 10);
				$this->teams = TBGTeam::findTeams($request->getParameter('find_by'));
			}
			else
			{
				$this->message = true;
			}
		}
		
		/**
		 * Adds a user or team to a project
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runAssignToProject(Request $request)
		{
			$this->forward403unless($request->isMethod(TBGRequest::POST));
									
			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				try
				{
					$this->theProject = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
				}
				catch (Exception $e) {}
				
				$this->forward403unless($this->theProject instanceof \thebuggenie\entities\Project);
				
				$assignee_type = $request->getParameter('assignee_type');
				$assignee_id = $request->getParameter('assignee_id');
				
				try
				{
					switch ($assignee_type)
					{
						case 'user':
							$assignee = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\User', $assignee_id);
							break;
						case 'team':
							$assignee = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Team', $assignee_id);
							break;
						default:
							$this->forward403();
							break;
					}
				}
				catch (Exception $e)
				{
					$this->forward403();
				}
				
				$assignee_role = $request->getParameter('role');
				$target_info = explode('_', $request->getParameter('target'));
				$this->forward403unless(count($target_info) == 2);
				
				switch ($target_info[0])
				{
					case 'project':
						$this->theProject->addAssignee($assignee, $assignee_role);
						break;
					case 'edition':
						foreach ($this->theProject->getEditions() as $e_id => $edition)
						{
							if ($e_id == $target_info[1])
							{
								$edition->addAssignee($assignee, $assignee_role);
								break;
							}
						}
						break;
					case 'component':
						foreach ($this->theProject->getComponents() as $c_id => $component)
						{
							if ($c_id == $target_info[1])
							{
								$component->addAssignee($assignee, $assignee_role);
								break;
							}
						}
						break;
						break;
					default:
						$this->forward403();
						break;
				}
				
				return $this->renderTemplate('projects_assignees', array('project' => $this->theProject));
			}
			return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__("You don't have access to save project settings")));
			
		}

		/**
		 * Configure project editions and components
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runConfigureProjectEditionsAndComponents(Request $request)
		{
			try
			{
				$this->theProject = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
			}
			catch (Exception $e) {}
			
			$this->forward403unless($this->theProject instanceof \thebuggenie\entities\Project);
		}

		/**
		 * Configure project data types
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runConfigureProjectOther(Request $request)
		{
			try
			{
				$this->theProject = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
			}
			catch (Exception $e) {}
			
			$this->forward403unless($this->theProject instanceof \thebuggenie\entities\Project);
		}

		/**
		 * Configure project data types
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runConfigureProjectMilestones(Request $request)
		{
			try
			{
				$this->theProject = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
			}
			catch (Exception $e) {}
			
			$this->forward403unless($this->theProject instanceof \thebuggenie\entities\Project);
			$this->milestones = $this->theProject->getAllMilestones();
		}
		
		/**
		 * Updates visible issue types
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runConfigureProjectUpdateOther(Request $request)
		{
			try
			{
				$this->theProject = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
			}
			catch (Exception $e)
			{
				return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('This project does not exist')));
			}
			
			$this->forward403unless($this->theProject instanceof \thebuggenie\entities\Project && $request->hasParameter('frontpage_summary'));

			try
			{
				if ($this->access_level == TBGSettings::ACCESS_FULL)
				{
					switch ($request->getParameter('frontpage_summary'))
					{
						case 'issuelist':
						case 'issuetypes':
							$this->theProject->setFrontpageSummaryType($request->getParameter('frontpage_summary'));
							$this->theProject->save();
							$this->theProject->clearVisibleIssuetypes();
							foreach ($request->getParameter('showissuetype', array()) as $issuetype_id)
							{
								$this->theProject->addVisibleIssuetype($issuetype_id);
							}
							break;
						case 'milestones':
							$this->theProject->setFrontpageSummaryType('milestones');
							$this->theProject->save();
							$this->theProject->clearVisibleMilestones();
							foreach ($request->getParameter('showmilestone', array()) as $milestone_id)
							{
								$this->theProject->addVisibleMilestone($milestone_id);
							}
							break;
						case '':
							$this->theProject->setFrontpageSummaryType('');
							$this->theProject->save();
							break;
					}
					return $this->renderJSON(array('failed' => false, 'title' => TBGContext::getI18n()->__('Your changes has been saved'), 'message' => ''));
				}
				return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__("You don't have access to save project settings")));
			}
			catch (Exception $e)
			{
				return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('An error occured'), 'message' => $e->getMessage()));
			}
		}

		/**
		 * Configure project builds
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runConfigureProjectDevelopers(Request $request)
		{
			try
			{
				$this->theProject = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
			}
			catch (Exception $e) {}
			
			$this->forward403unless($this->theProject instanceof \thebuggenie\entities\Project);
		}
		
		/**
		 * Configure project leaders
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runSetItemLead(Request $request)
		{
			try
			{
				switch ($request->getParameter('item_type'))
				{
					case 'project':
						$item = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
						break;
					case 'edition':
						$item = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Edition', $request->getParameter('edition_id'));
						break;
					case 'component':
						$item = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Component', $request->getParameter('component_id'));
						break;
				}
			}
			catch (Exception $e) {}
			
			$this->forward403unless($item instanceof TBGOwnableItem);
			
			if ($request->hasParameter('value'))
			{
				$this->forward403unless($this->access_level == TBGSettings::ACCESS_FULL);
				if ($request->hasParameter('identifiable_type'))
				{
					if (in_array($request->getParameter('identifiable_type'), array(TBGIdentifiableClass::TYPE_USER, TBGIdentifiableClass::TYPE_TEAM)))
					{
						switch ($request->getParameter('identifiable_type'))
						{
							case TBGIdentifiableClass::TYPE_USER:
								$identified = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\User', $request->getParameter('value'));
								break;
							case TBGIdentifiableClass::TYPE_TEAM:
								$identified = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Team', $request->getParameter('value'));
								break;
						}
						if ($identified instanceof TBGIdentifiableClass)
						{
							if ($request->getParameter('field') == 'owned_by') $item->setOwner($identified);
							elseif ($request->getParameter('field') == 'qa_by') $item->setQaResponsible($identified);
							elseif ($request->getParameter('field') == 'lead_by') $item->setLeader($identified);
							$item->save();
						}
					}
					else
					{
						if ($request->getParameter('field') == 'owned_by') $item->unsetOwner();
						elseif ($request->getParameter('field') == 'qa_by') $item->unsetQaResponsible();
						elseif ($request->getParameter('field') == 'lead_by') $item->unsetLeader();
						$item->save();
					}
				}
				if ($request->getParameter('field') == 'owned_by')
					return $this->renderJSON(array('field' => (($item->hasOwner()) ? array('id' => $item->getOwnerID(), 'name' => (($item->getOwnerType() == TBGIdentifiableClass::TYPE_USER) ? $this->getComponentHTML('main/userdropdown', array('user' => $item->getOwner())) : $this->getComponentHTML('main/teamdropdown', array('team' => $item->getOwner())))) : array('id' => 0))));
				elseif ($request->getParameter('field') == 'lead_by')
					return $this->renderJSON(array('field' => (($item->hasLeader()) ? array('id' => $item->getLeaderID(), 'name' => (($item->getLeaderType() == TBGIdentifiableClass::TYPE_USER) ? $this->getComponentHTML('main/userdropdown', array('user' => $item->getLeader())) : $this->getComponentHTML('main/teamdropdown', array('team' => $item->getLeader())))) : array('id' => 0))));
				elseif ($request->getParameter('field') == 'qa_by')
					return $this->renderJSON(array('field' => (($item->hasQaResponsible()) ? array('id' => $item->getQaResponsibleID(), 'name' => (($item->getQaResponsibleType() == TBGIdentifiableClass::TYPE_USER) ? $this->getComponentHTML('main/userdropdown', array('user' => $item->getQaResponsible())) : $this->getComponentHTML('main/teamdropdown', array('team' => $item->getQaResponsible())))) : array('id' => 0))));
			}
		}
		
		/**
		 * Configure project settings
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runConfigureProjectSettings(Request $request)
		{
			try
			{
				$this->project = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
			}
			catch (Exception $e) {}
			
			if (!$this->project instanceof \thebuggenie\entities\Project) return $this->return404(TBGContext::getI18n()->__("This project doesn't exist"));
			
			if ($request->isMethod(TBGRequest::POST))
			{
				$this->forward403unless($this->access_level == TBGSettings::ACCESS_FULL, TBGContext::getI18n()->__('You do not have access to update these settings'));
				
				if ($request->hasParameter('release_month') && $request->hasParameter('release_day') && $request->hasParameter('release_year'))
				{
					$release_date = mktime(0, 0, 1, $request->getParameter('release_month'), $request->getParameter('release_day'), $request->getParameter('release_year'));
					$this->project->setReleaseDate($release_date);
				}

				$old_key = $this->project->getKey();

				if ($request->hasParameter('project_name'))
				{
					if (trim($request->getParameter('project_name')) == '')
					{
						return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please specify a valid project name')));
					}
					else
					{
						$this->project->setName($request->getParameter('project_name'));
					}
				}


				$message = ($old_key != $this->project->getKey()) ? TBGContext::getI18n()->__('%IMPORTANT%: The project key has changed. Remember to replace the current url with the new project key', array('%IMPORTANT%' => '<b>'.TBGContext::getI18n()->__('IMPORTANT').'</b>')) : '';

				if ($request->hasParameter('project_key'))
					$this->project->setKey($request->getParameter('project_key'));

				if ($request->hasParameter('use_prefix'))
					$this->project->setUsePrefix((bool) $request->getParameter('use_prefix'));

				if ($request->hasParameter('use_prefix') && $this->project->doesUsePrefix())
				{
					if (!$this->project->setPrefix($request->getParameter('prefix')))
						return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__("Project prefixes may only contain letters and numbers")));
				}

				if ($request->hasParameter('client'))
				{
					if ($request->getParameter('client') == 0)
					{
						$this->project->setClient(null);
					}
					else
					{
						$this->project->setClient(\caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Client', $request->getParameter('client')));
					}
				}

				if ($request->hasParameter('workflow_scheme'))
				{
					try
					{
						$workflow_scheme = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowScheme', $request->getParameter('workflow_scheme'));
						$this->project->setWorkflowScheme($workflow_scheme);
					}
					catch (Exception $e) {}
				}

				if ($request->hasParameter('issuetype_scheme'))
				{
					try
					{
						$issuetype_scheme = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\IssuetypeScheme', $request->getParameter('issuetype_scheme'));
						$this->project->setIssuetypeScheme($issuetype_scheme);
					}
					catch (Exception $e) {}
				}

				if ($request->hasParameter('use_scrum'))
					$this->project->setUsesScrum((bool) $request->getParameter('use_scrum'));

				if ($request->hasParameter('description'))
					$this->project->setDescription($request->getParameter('description', null, false));

				if ($request->hasParameter('homepage'))
					$this->project->setHomepage($request->getParameter('homepage'));

				if ($request->hasParameter('doc_url'))
					$this->project->setDocumentationURL($request->getParameter('doc_url'));

				if ($request->hasParameter('planned_release'))
					$this->project->setPlannedReleased($request->getParameter('planned_release'));

				if ($request->hasParameter('released'))
					$this->project->setReleased((int) $request->getParameter('released'));

				if ($request->hasParameter('locked'))
					$this->project->setLocked((bool) $request->getParameter('locked'));

				if ($request->hasParameter('enable_builds'))
					$this->project->setBuildsEnabled((bool) $request->getParameter('enable_builds'));

				if ($request->hasParameter('enable_editions'))
					$this->project->setEditionsEnabled((bool) $request->getParameter('enable_editions'));

				if ($request->hasParameter('enable_components'))
					$this->project->setComponentsEnabled((bool) $request->getParameter('enable_components'));

				if ($request->hasParameter('allow_changing_without_working'))
					$this->project->setChangeIssuesWithoutWorkingOnThem((bool) $request->getParameter('allow_changing_without_working'));

				if ($request->hasParameter('allow_autoassignment'))
					$this->project->setAutoassign((bool) $request->getParameter('allow_autoassignment'));

				$this->project->save();
				TBGContext::setMessage('project_settings_saved', true);
				$this->forward(TBGContext::getRouting()->generate('project_settings', array('project_key' => $this->project->getKey())));
			}
		}
		
		/**
		 * Add a project (AJAX call)
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runAddProject(Request $request)
		{
			$i18n = TBGContext::getI18n();

			if (!\thebuggenie\core\Context::getScope()->hasProjectsAvailable())
			{
				return $this->renderJSON(array('failed' => true, "error" => $i18n->__("There are no more projects available in this instance")));
			}
			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				if (($p_name = $request->getParameter('p_name')) && trim($p_name) != '')
				{
					try
					{
						$project = new TBGProject();
						$project->setName($p_name);
						$project->save();
						return $this->renderJSON(array('title' => $i18n->__('The project has been added'), 'content' => $this->getTemplateHTML('projectbox', array('project' => $project, 'access_level' => $this->access_level)), 'total_count' => \thebuggenie\entities\Project::getProjectsCount(), 'more_available' => \thebuggenie\core\Context::getScope()->hasProjectsAvailable()));
					}
					catch (InvalidArgumentException $e)
					{
						return $this->renderJSON(array('failed' => true, "error" => $i18n->__('A project with the same key already exists')));
					}
					catch (Exception $e)
					{
						return $this->renderJSON(array('failed' => true, "error" => $i18n->__('An error occurred: '. $e->getMessage())));
					}
				}
				return $this->renderJSON(array('failed' => true, "error" => $i18n->__('Please specify a valid project name')));
			}
			return $this->renderJSON(array('failed' => true, "error" => $i18n->__("You don't have access to add projects")));
		}
		
		/**
		 * Add an edition (AJAX call)
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runAddEdition(Request $request)
		{
			$i18n = TBGContext::getI18n();

			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				try
				{
					$p_id = $request->getParameter('project_id');
					if ($project = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $p_id))
					{
						if (\caspar\core\Caspar::getUser()->canManageProjectReleases($project))
						{
							if (($e_name = $request->getParameter('e_name')) && trim($e_name) != '')
							{
								$project = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $p_id);
								if (in_array($e_name, $project->getEditions()))
								{
									throw new Exception($i18n->__('This edition already exists for this project'));
								}
								$edition = $project->addEdition($e_name);
								return $this->renderJSON(array(/*'title' => $i18n->__('The edition has been added'), */'html' => $this->getTemplateHTML('editionbox', array('edition' => $edition, 'access_level' => $this->access_level))));
							}
							else
							{
								throw new Exception($i18n->__('You need to specify a name for the new edition'));
							}
						}
						else
						{
							throw new Exception($i18n->__('You do not have access to this project'));
						}
					}
					else
					{
						throw new Exception($i18n->__('You need to specify a project id'));
					}
				}
				catch (Exception $e)
				{
					return $this->renderJSON(array('failed' => true, "error" => $i18n->__('The edition could not be added').", ".$e->getMessage()));
				}
			}
			return $this->renderJSON(array('failed' => true, "error" => $i18n->__("You don't have access to add projects")));
		}

		/**
		 * Perform actions on a build (AJAX call)
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runBuildAction(Request $request)
		{
			$i18n = TBGContext::getI18n();

			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				try
				{
					if ($b_id = $request->getParameter('build_id'))
					{
						$build = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Build', $b_id);
						if ($build->hasAccess())
						{
							switch ($request->getParameter('build_action'))
							{
								case 'delete':
									$build->delete();
									return $this->renderJSON(array('deleted' => true, 'message' => $i18n->__('The release was deleted')));
									break;
								case 'addtoopen':
									$build->addToOpenParentIssues((int) $request->getParameter('status'), (int) $request->getParameter('category'), (int) $request->getParameter('issuetype'));
									return $this->renderJSON(array('failed' => false, 'title' => $i18n->__('The selected build has been added to open issues based on your selections'), 'message' => ''));
									break;
								case 'release':
									$build->setReleased(true);
									$build->setReleaseDate();
									$build->save();
									$this->show_mode = 'one';
									break;
								case 'retract':
									$build->setReleased(false);
									$build->setReleaseDate(0);
									$build->save();
									$this->show_mode = 'one';
									break;
								case 'lock':
									$build->setLocked(true);
									$build->save();
									$this->show_mode = 'one';
									break;
								case 'unlock':
									$build->setLocked(false);
									$build->save();
									$this->show_mode = 'one';
									break;
								case 'update':
									if (($b_name = $request->getParameter('build_name')) && trim($b_name) != '')
									{
										if ($build->getProject() instanceof \thebuggenie\entities\Project && in_array($b_name, $build->getProject()->getBuilds()) && !($b_name == $build->getName()))
										{
											throw new Exception($i18n->__('This build already exists for this project'));
										}
										$build->setName($b_name);
										$build->setVersionMajor($request->getParameter('ver_mj'));
										$build->setVersionMinor($request->getParameter('ver_mn'));
										$build->setVersionRevision($request->getParameter('ver_rev'));
										if ($request->hasParameter('release_month') && $request->hasParameter('release_day') && $request->hasParameter('release_year'))
										{
											$release_date = mktime(0, 0, 1, $request->getParameter('release_month'), $request->getParameter('release_day'), $request->getParameter('release_year'));
											$build->setReleaseDate($release_date);
										}
										$build->save();
									}
									else
									{
										throw new Exception($i18n->__('The build / release needs to have a name'));
									}
									$this->show_mode = 'one';
									break;
							}
						}
						else
						{
							throw new Exception($i18n->__('You do not have access to this build / release'));
						}
					}
					else
					{
						throw new Exception($i18n->__('You need to specify a build / release'));
					}
				}
				catch (Exception $e)
				{
					return $this->renderJSON(array('failed' => true, "error" => $i18n->__('Could not update the build / release').", ".$e->getMessage()));
				}

				$this->build = $build;
			}
			else
			{
				return $this->renderJSON(array('failed' => true, "error" => $i18n->__("You don't have access to add editions")));
			}
		}
		
		/**
		 * Get edit form for user
		 */
		public function runGetUserEditForm(Request $request)
		{
			return $this->renderJSON(array('failed' => false, "content" => get_template_html('finduser_row_editable', array('user' => \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\User', $request->getParameter('user_id'))))));
		}	
			
		/**
		 * Add a build (AJAX call)
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runProjectBuild(Request $request)
		{
			$i18n = TBGContext::getI18n();

			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				try
				{
					$p_id = $request->getParameter('project_id');
					if ($project = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $p_id))
					{
						if (\caspar\core\Caspar::getUser()->canManageProjectReleases($project))
						{
							if (($b_name = $request->getParameter('build_name')) && trim($b_name) != '')
							{
								$build = new TBGBuild($request->getParameter('build_id'));
								$build->setName($b_name);
								$build->setVersion($request->getParameter('ver_mj', 0), $request->getParameter('ver_mn', 0), $request->getParameter('ver_rev', 0));
								$build->setReleased((bool) $request->getParameter('isreleased'));
								$build->setLocked((bool) $request->getParameter('locked'));
								if ($request->getParameter('milestone') && $milestone = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Milestone', $request->getParameter('milestone')))
								{
									$build->setMilestone($milestone);
								}
								else
								{
									$build->clearMilestone();
								}
								if ($request->getParameter('edition') && $edition = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Edition', $request->getParameter('edition')))
								{
									$build->setEdition($edition);
								}
								else
								{
									$build->clearEdition();
								}
								$release_date = mktime($request->getParameter('release_hour'), $request->getParameter('release_minute'), 1, $request->getParameter('release_month'), $request->getParameter('release_day'), $request->getParameter('release_year'));
								$build->setReleaseDate($release_date);
								switch ($request->getParameter('download', 'leave_file'))
								{
									case '0':
										$build->clearFile();
										$build->setFileURL('');
										break;
									case 'upload_file':
										if ($build->hasFile())
										{
											$build->getFile()->delete();
											$build->clearFile();
										}
										$file = \caspar\core\Caspar::getRequest()->handleUpload('upload_file');
										$build->setFile($file);
										$build->setFileURL('');
										break;
									case 'url':
										$build->clearFile();
										$build->setFileURL($request->getParameter('file_url'));
										break;
								}
								
								if ($request->getParameter('edition_id')) $build->setEdition($edition);
								if (!$build->getID()) $build->setProject($project);
								
								$build->save();
							}
							else
							{
								throw new Exception($i18n->__('You need to specify a name for the release'));
							}
						}
						else
						{
							throw new Exception($i18n->__('You do not have access to this project'));
						}
					}
					else
					{
						throw new Exception($i18n->__('You need to specify a project id'));
					}
				}
				catch (Exception $e)
				{
					TBGContext::setMessage('build_error', $e->getMessage());
				}
				$this->forward(TBGContext::getRouting()->generate('project_release_center', array('project_key' => $project->getKey())));
			}
			return $this->forward403($i18n->__("You don't have access to add releases"));
		}
		
		/**
		 * Add a component (AJAX call)
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runAddComponent(Request $request)
		{
			$i18n = TBGContext::getI18n();

			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				try
				{
					$p_id = $request->getParameter('project_id');
					if ($project = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $p_id))
					{
						if (\caspar\core\Caspar::getUser()->canManageProjectReleases($project))
						{
							if (($c_name = $request->getParameter('c_name')) && trim($c_name) != '')
							{
								$project = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $p_id);
								if (in_array($c_name, $project->getComponents()))
								{
									throw new Exception($i18n->__('This component already exists for this project'));
								}
								$component = $project->addComponent($c_name);
								return $this->renderJSON(array(/*'title' => $i18n->__('The component has been added'), */'html' => $this->getTemplateHTML('componentbox', array('component' => $component, 'access_level' => $this->access_level))));
							}
							else
							{
								throw new Exception($i18n->__('You need to specify a name for the new component'));
							}
						}
						else
						{
							throw new Exception($i18n->__('You do not have access to this project'));
						}
					}
					else
					{
						throw new Exception($i18n->__('You need to specify a project id'));
					}
				}
				catch (Exception $e)
				{
					return $this->renderJSON(array('failed' => true, "error" => $i18n->__('The component could not be added').", ".$e->getMessage()));
				}
			}
			return $this->renderJSON(array('failed' => true, "error" => $i18n->__("You don't have access to add components")));
		}

		/**
		 * Add a milestone (AJAX call)
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runAddMilestone(Request $request)
		{
			$i18n = TBGContext::getI18n();

			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				try
				{
					$p_id = $request->getParameter('project_id');
					if ($project = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $p_id))
					{
						if (\caspar\core\Caspar::getUser()->canManageProjectReleases($project))
						{
							if (($m_name = $request->getParameter('name')) && trim($m_name) != '')
							{
								$theProject = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $p_id);
								if (in_array($m_name, $theProject->getAllMilestones()))
								{
									throw new Exception($i18n->__('This milestone already exists for this project'));
								}
								$theMilestone = $theProject->addMilestone($m_name, $request->getParameter('milestone_type', 1));
								return $this->renderJSON(array('title' => $i18n->__('The milestone has been added'), 'content' => $this->getTemplateHTML('milestonebox', array('milestone' => $theMilestone))));
							}
							else
							{
								throw new Exception($i18n->__('You need to specify a name for the new milestone'));
							}
						}
						else
						{
							throw new Exception($i18n->__('You do not have access to this project'));
						}
					}
					else
					{
						throw new Exception($i18n->__('You need to specify a project id'));
					}
				}
				catch (Exception $e)
				{
					return $this->renderJSON(array('failed' => true, "error" => $i18n->__('The milestone could not be added').", ".$e->getMessage()));
				}
			}
			return $this->renderJSON(array('failed' => true, "error" => $i18n->__("You don't have access to add milestones")));
		}
		
		/**
		 * Perform actions on a build (AJAX call)
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runMilestoneAction(Request $request)
		{
			$i18n = TBGContext::getI18n();

			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				try
				{
					if ($m_id = $request->getParameter('milestone_id'))
					{
						$theMilestone = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Milestone', $m_id);
						if ($theMilestone->hasAccess())
						{
							switch ($request->getParameter('milestone_action'))
							{
								case 'update':
									if (($m_name = $request->getParameter('name')) && trim($m_name) != '')
									{
										if ($m_name != $theMilestone->getName())
										{
											$check_milestones = $theMilestone->getProject()->getAllMilestones();
											unset($check_milestones[$theMilestone->getID()]);
											if (in_array($m_name, $check_milestones))
											{
												throw new Exception($i18n->__('This milestone already exists for this project'));
											}
										}
										$theMilestone->setName($m_name);
										if ($request->getParameter('is_starting'))
										{
											$theMilestone->setStarting((bool) $request->getParameter('is_starting'));
										}
										
										if ($request->getParameter('is_scheduled'))
										{
											$theMilestone->setScheduled((bool) $request->getParameter('is_scheduled'));
										}
										
										$theMilestone->setDescription($request->getParameter('description', null));
										$theMilestone->setType($request->getParameter('milestone_type', 1));
										if ($theMilestone->isScheduled())
										{
											if ($request->hasParameter('sch_month') && $request->hasParameter('sch_day') && $request->hasParameter('sch_year'))
											{
												$scheduled_date = mktime(23, 59, 59, \caspar\core\Caspar::getRequest()->getParameter('sch_month'), \caspar\core\Caspar::getRequest()->getParameter('sch_day'), \caspar\core\Caspar::getRequest()->getParameter('sch_year'));
												$theMilestone->setScheduledDate($scheduled_date);
											}
										}
										else
										{
											$theMilestone->setScheduledDate(0);
										}
										
										if ($theMilestone->isStarting())
										{
											if ($request->hasParameter('starting_month') && $request->hasParameter('starting_day') && $request->hasParameter('starting_year'))
											{
												$starting_date = mktime(0, 0, 1, \caspar\core\Caspar::getRequest()->getParameter('starting_month'), \caspar\core\Caspar::getRequest()->getParameter('starting_day'), \caspar\core\Caspar::getRequest()->getParameter('starting_year'));
												$theMilestone->setStartingDate($starting_date);
											}
										}
										else
										{
											$theMilestone->setStartingDate(0);
										}
										
										$theMilestone->save();
										return $this->renderJSON(array('message' => TBGContext::getI18n()->__('Milestone updated'), 'content' => $this->getTemplateHTML('milestonebox', array('milestone' => $theMilestone))));
									}
									else
									{
										throw new Exception(TBGContext::getI18n()->__('The milestone needs to have a name'));
									}
									break;
								case 'delete':
									$theMilestone->delete();
									return $this->renderJSON(array('deleted' => true));
									break;
							}
						}
						else
						{
							throw new Exception(TBGContext::getI18n()->__('You do not have access to this milestone'));
						}
					}
					else
					{
						throw new Exception(TBGContext::getI18n()->__('You need to specify a milestone'));
					}
				}
				catch (Exception $e)
				{
					return $this->renderJSON(array('failed' => true, "error" => TBGContext::getI18n()->__('Could not update the milestone').", ".$e->getMessage()));
				}
				return $this->renderJSON(array('done' => true));
			}
			return $this->renderJSON(array('failed' => true, "error" => $i18n->__("You don't have access to modify milestones")));
		}
		
		/**
		 * Add or remove a component to/from an edition (AJAX call)
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runEditEditionComponent(Request $request)
		{
			$i18n = TBGContext::getI18n();

			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				try
				{
					$theEdition   = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Edition', $request->getParameter('edition_id'));
					if ($request->getParameter('mode') == 'add')
					{
						$theEdition->addComponent($request->getParameter('component_id'));
					}
					elseif ($request->getParameter('mode') == 'remove')
					{
						$theEdition->removeComponent($request->getParameter('component_id'));
					}
					return $this->renderJSON(array('failed' => false));
				}
				catch (Exception $e)
				{
					return $this->renderJSON(array('failed' => true, "error" => $i18n->__('The component could not be added to this edition').", ".$e->getMessage()));
				}
			}
			return $this->renderJSON(array('failed' => true, "error" => $i18n->__("You don't have access to modify components")));
			
		}

		/**
		 * Edit a component
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runEditComponent(Request $request)
		{
			$i18n = TBGContext::getI18n();

			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				try
				{
					$theComponent = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Component', $request->getParameter('component_id'));
					if ($request->getParameter('mode') == 'update')
					{
						if (($c_name = $request->getParameter('c_name')) && trim($c_name) != '')
						{
							if($c_name == $theComponent->getName())
							{
								return $this->renderJSON(array('failed' => false, 'newname' => $c_name));
							}
							if (in_array($c_name, $theComponent->getProject()->getComponents()))
							{
								throw new Exception($i18n->__('This component already exists for this project'));
							}
							$theComponent->setName($c_name);
							return $this->renderJSON(array('failed' => false, 'newname' => $theComponent->getName()));
						}
						else
						{
							throw new Exception($i18n->__('You need to specify a name for this component'));
						}
					}
					elseif ($request->getParameter('mode') == 'delete')
					{
						$project = $theComponent->getProject();
						$theComponent->delete();
						$count = count(TBGComponent::getAllByProjectID($project->getID()));
						return $this->renderJSON(array('failed' => false, 'deleted' => true, 'itemcount' => $count, 'message' => TBGContext::getI18n()->__('Component deleted')));
					}
				}
				catch (Exception $e)
				{
					return $this->renderJSON(array('failed' => true, "error" => TBGContext::getI18n()->__('Could not edit this component').", ".$e->getMessage()));
				}
			}
			return $this->renderJSON(array('failed' => true, "error" => $i18n->__("You don't have access to modify components")));
		}
		
		/**
		 * Delete a project
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runDeleteProject(Request $request)
		{
			$i18n = TBGContext::getI18n();

			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				try
				{
					$theProject = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
					$theProject->setDeleted();
					$theProject->save();
					return $this->renderJSON(array('failed' => false, 'title' => $i18n->__('The project was deleted'), 'total_count' => \thebuggenie\entities\Project::getProjectsCount(), 'more_available' => \thebuggenie\core\Context::getScope()->hasProjectsAvailable()));
				}
				catch (Exception $e)
				{
					return $this->renderJSON(array('failed' => true, 'error' => $i18n->__('An error occured') . ': ' . $e->getMessage()));
				}
			}
			return $this->renderJSON(array('failed' => true, "error" => $i18n->__("You don't have access to remove projects")));
		}
		
		/**
		 * Handle archive functiions
		 * 
		 * @param bool $archived Status
		 * @param TBGRequest $request The request object
		 */
		protected function _setArchived($archived, TBGRequest $request)
		{
			$i18n = TBGContext::getI18n();

			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				try
				{
					$theProject = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
					$theProject->setArchived($archived);
					$theProject->save();
					
					$projectbox = $this->getTemplateHtml('projectbox', array('project' => $theProject, 'access_level' => $this->access_level));
					return $this->renderJSON(array('failed' => false, 'message' => $i18n->__('Project successfully updated'), 'box' => $projectbox));
				}
				catch (Exception $e)
				{
					return $this->renderJSON(array('failed' => true, 'error' => $i18n->__('An error occured') . ': ' . $e->getMessage()));
				}
			}
			return $this->renderJSON(array('failed' => true, "error" => $i18n->__("You don't have access to archive projects")));
		}
		
		/**
		 * Archive
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runArchiveProject(Request $request)
		{
			return $this->_setArchived(true, $request);
		}
		
		/**
		 * Unarchive
		 * 
		 * @param TBGRequest $request The request object
		 */
		public function runUnarchiveProject(Request $request)
		{
			// Don't unarchive if we will have too many projects
			if (!\thebuggenie\core\Context::getScope()->hasProjectsAvailable())
			{
				return $this->renderJSON(array('failed' => true, "error" => $i18n->__("There are no more projects available in this instance")));
			}
			
			return $this->_setArchived(false, $request);
		}

		/**
		 * Perform an action on a module
		 *
		 * @param TBGRequest $request The request object
		 */
		public function runModuleAction(Request $request)
		{
			$this->forward403unless($this->access_level == TBGSettings::ACCESS_FULL);
			
			try
			{
				if ($request->getParameter('mode') == 'install' && file_exists(THEBUGGENIE_MODULES_PATH . $request->getParameter('module_key') . DS . 'module'))
				{
					if (TBGModule::installModule($request->getParameter('module_key')))
					{
						TBGContext::setMessage('module_message', TBGContext::getI18n()->__('The module "%module_name%" was installed successfully', array('%module_name%' => $request->getParameter('module_key'))));
					}
					else
					{
						TBGContext::setMessage('module_error', TBGContext::getI18n()->__('There was an error install the module %module_name%', array('%module_name%' => $request->getParameter('module_key'))));
					}
				}
				else if ($request->getParameter('mode') == 'upload')
				{
					$archive = $request->getUploadedFile('archive');	
					if ($archive == null || $archive['error'] != UPLOAD_ERR_OK || !preg_match('/application\/(x-)?zip/i', $archive['type']))
					{
						TBGContext::setMessage('module_error', TBGContext::getI18n()->__('Invalid or empty archive uploaded'));
					}
					else
					{
						$module_name = TBGModule::uploadModule($archive);
						TBGContext::setMessage('module_message', TBGContext::getI18n()->__('The module "%module_name%" was uploaded successfully', array('%module_name%' => $module_name)));
					}
				}				
				else
				{
					$module = TBGContext::getModule($request->getParameter('module_key'));
					if (!$module->isCore())
						switch ($request->getParameter('mode'))
						{
							case 'disable':
								if ($module->getType() !== TBGModule::MODULE_AUTH):
									$module->disable();
								endif;
								break;
							case 'enable':
								if ($module->getType() !== TBGModule::MODULE_AUTH):
									$module->enable();
								endif;
								break;
							case 'uninstall':
								$module->uninstall();
								TBGContext::setMessage('module_message', TBGContext::getI18n()->__('The module "%module_name%" was uninstalled successfully', array('%module_name%' => $module->getName())));
								break;
							case 'update':
								try
								{
									$module->upgrade();
									TBGContext::setMessage('module_message', TBGContext::getI18n()->__('The module "%module_name%" was successfully upgraded and can now be used again', array('%module_name%' => $module->getName())));
								}
								catch (Exception $e)
								{ throw $e;
									TBGContext::setMessage('module_error', TBGContext::getI18n()->__('The module "%module_name%" was not successfully upgraded', array('%module_name%' => $module->getName())));	
								}
								break;
						}
				}
			}
			catch (Exception $e)
			{ throw $e;
				\caspar\core\Logging::log('Trying to run action ' . $request->getParameter('mode') . ' on module ' . $request->getParameter('module_key') . ' made an exception: ' . $e->getMessage(), \caspar\core\Logging::LEVEL_FATAL);
				TBGContext::setMessage('module_error', TBGContext::getI18n()->__('This module (%module_name%) does not exist', array('%module_name%' => $request->getParameter('module_key'))));
			}
			$this->forward(TBGContext::getRouting()->generate('configure_modules'));
		}

		/**
		 * Get permissions info for a single permission key
		 *
		 * @param TBGRequest $request
		 */
		public function runGetPermissionsInfo(Request $request)
		{
			$i18n = TBGContext::getI18n();

			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				return $this->renderJSON(array('failed' => false, 'content' => $this->getComponentHTML('configuration/permissionsblock', array('base_id' => $request->getParameter('base_id'), 'permissions_list' => $request->getParameter('permissions_list'), 'mode' => $request->getParameter('mode'), 'target_id' => $request->getParameter('target_id'), 'user_id' => $request->getParameter('user_id'), 'module' => $request->getParameter('target_module'), 'access_level' => $this->access_level))));
			}
			return $this->renderJSON(array('failed' => true, "error" => $i18n->__("You don't have access to modify permissions")));
		}

		public function runSetPermission(Request $request)
		{
			$i18n = TBGContext::getI18n();

			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				$uid = 0;
				$gid = 0;
				$tid = 0;
				switch ($request->getParameter('target_type'))
				{
					case 'user':
						$uid = $request->getParameter('item_id');
						break;
					case 'group':
						$gid = $request->getParameter('item_id');
						break;
					case 'team':
						$tid = $request->getParameter('item_id');
						break;
				}

				switch ($request->getParameter('mode'))
				{
					case 'allowed':
						TBGContext::setPermission($request->getParameter('key'), $request->getParameter('target_id'), $request->getParameter('target_module'), $uid, $gid, $tid, true);
						break;
					case 'denied':
						TBGContext::setPermission($request->getParameter('key'), $request->getParameter('target_id'), $request->getParameter('target_module'), $uid, $gid, $tid, false);
						break;
					case 'unset':
						TBGContext::removePermission($request->getParameter('key'), $request->getParameter('target_id'), $request->getParameter('target_module'), $uid, $gid, $tid);
						break;
				}
				return $this->renderJSON(array('failed' => false, 'content' => $this->getComponentHTML('configuration/permissionsinfoitem', array('key' => $request->getParameter('key'), 'target_id' => $request->getParameter('target_id'), 'type' => $request->getParameter('target_type'), 'mode' => $request->getParameter('template_mode'), 'item_id' => $request->getParameter('item_id'), 'module' => $request->getParameter('target_module'), 'access_level' => $this->access_level))));
			}
			return $this->renderJSON(array('failed' => true, "error" => $i18n->__("You don't have access to modify permissions")));
		}
		
		/**
		 * Configure a module
		 *
		 * @param TBGRequest $request The request object
		 */
		public function runConfigureModule(Request $request)
		{
			$this->forward403unless($this->access_level == TBGSettings::ACCESS_FULL);
			
			try
			{
				$module = TBGContext::getModule($request->getParameter('config_module'));
				if (!$module->isEnabled())
				{
					throw new Exception('disabled');
				}
				elseif (!$module->hasConfigSettings())
				{
					throw new Exception('module not configurable');
				}
				else
				{
					if ($request->isMethod(TBGRequest::POST) && $this->access_level == TBGSettings::ACCESS_FULL)
					{
						try
						{
							$module->postConfigSettings($request);
							if (!TBGContext::hasMessage('module_message'))
							{
								TBGContext::setMessage('module_message', TBGContext::getI18n()->__('Settings saved successfully'));
							}
						}
						catch (Exception $e)
						{
							TBGContext::setMessage('module_error', $e->getMessage());
						}
						$this->forward(TBGContext::getRouting()->generate('configure_module', array('config_module' => $request->getParameter('config_module'))));
					}
					$this->module = $module;
				}
			}
			catch (Exception $e)
			{
				\caspar\core\Logging::log('Trying to configure module ' . $request->getParameter('config_module') . " which isn't configurable", 'main', \caspar\core\Logging::LEVEL_FATAL);
				TBGContext::setMessage('module_error', TBGContext::getI18n()->__('The module "%module_name%" is not configurable', array('%module_name%' => $request->getParameter('config_module'))));
				$this->forward(TBGContext::getRouting()->generate('configure_modules'));
			}
			$this->module_message = TBGContext::getMessageAndClear('module_message');
			$this->module_error = TBGContext::getMessageAndClear('module_error');
			$this->module_error_details = TBGContext::getMessageAndClear('module_error_details');
		}

		public function runConfigurePermissions(Request $request)
		{
			$this->forward403unless($this->access_level == TBGSettings::ACCESS_FULL);
		}

		public function runConfigureUploads(Request $request)
		{
			$this->uploads_enabled = \thebuggenie\core\Context::getScope()->isUploadsEnabled();
			if ($this->uploads_enabled && $request->isMethod(TBGRequest::POST))
			{
				$this->forward403unless($this->access_level == TBGSettings::ACCESS_FULL);
				if ($request->getParameter('upload_storage') == 'files' && (bool) $request->getParameter('enable_uploads'))
				{
					if (!is_writable($request->getParameter('upload_localpath')))
					{
						$this->getResponse()->setHttpStatus(400);
						return $this->renderJSON(array('error' => TBGContext::getI18n()->__("The upload path isn't writable")));
					}
				}
				
				if (!is_numeric($request->getParameter('upload_max_file_size')))
				{
					return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__("The maximum file size must be a number")));
				}
				
				$settings = array('enable_uploads', 'upload_restriction_mode', 'upload_extensions_list', 'upload_max_file_size', 'upload_storage', 'upload_localpath');

				foreach ($settings as $setting)
				{
					if (\caspar\core\Caspar::getRequest()->hasParameter($setting))
					{
						TBGSettings::saveSetting($setting, \caspar\core\Caspar::getRequest()->getParameter($setting));
					}
				}
				return $this->renderJSON(array('failed' => false, 'title' => TBGContext::getI18n()->__('All settings saved')));
			}
		}
		
		public function runConfigureAuthentication(Request $request)
		{
			$modules = array();
			$allmods = TBGContext::getModules();
			foreach ($allmods as $mod)
			{
				if ($mod->getType() == TBGModule::MODULE_AUTH)
				{
					$modules[] = $mod;
				}
			}
			$this->modules = $modules;
		}
		
		public function runSaveAuthentication(Request $request)
		{
			if (\caspar\core\Caspar::getRequest()->isMethod(TBGRequest::POST))
			{
				$this->forward403unless($this->access_level == TBGSettings::ACCESS_FULL);
				$settings = array(TBGSettings::SETTING_AUTH_BACKEND, 'register_message', 'forgot_message', 'changepw_message', 'changedetails_message');
				
				foreach ($settings as $setting)
				{
					if (\caspar\core\Caspar::getRequest()->getParameter($setting) !== null)
					{
						$value = \caspar\core\Caspar::getRequest()->getParameter($setting);
						TBGSettings::saveSetting($setting, $value);
					}
				}
			}
		}
		
		public function runConfigureUsers(Request $request)
		{
			$this->groups = \thebuggenie\entities\Group::getAll();
			$this->teams = TBGTeam::getAll();
			$this->clients = TBGClient::getall();
			$this->finduser = $request->getParameter('finduser');
		}

		public function runDeleteGroup(Request $request)
		{
			try
			{
				if (!(!in_array($request->getParameter('group_id'), TBGSettings::getDefaultGroupIDs())))
				{
					throw new Exception(TBGContext::getI18n()->__("You cannot delete the default groups"));
				}
				
				try
				{
					$group = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Group', $request->getParameter('group_id'));
				}
				catch (Exception $e) { }
				if (!$group instanceof \thebuggenie\entities\Group)
				{
					throw new Exception(TBGContext::getI18n()->__("You cannot delete this group"));
				}
				$group->delete();
				return $this->renderJSON(array('success' => true, 'message' => TBGContext::getI18n()->__('The group was deleted')));
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage()));
			}
		}

		public function runAddGroup(Request $request)
		{
			try
			{
				$mode = $request->getParameter('mode');
				if ($group_name = $request->getParameter('group_name'))
				{
					if ($mode == 'clone')
					{
						try
						{
							$old_group = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Group', $request->getParameter('group_id'));
						}
						catch (Exception $e) { }
						if (!$old_group instanceof \thebuggenie\entities\Group)
						{
							throw new Exception(TBGContext::getI18n()->__("You cannot clone this group"));
						}
					}
					if (\thebuggenie\entities\Group::doesGroupNameExist(trim($group_name)))
					{
						throw new Exception(TBGContext::getI18n()->__("Please enter a group name that doesn't already exist"));
					}
					$group = new \thebuggenie\entities\Group();
					$group->setName($group_name);
					$group->save();
					if ($mode == 'clone')
					{
						if ($request->getParameter('clone_permissions'))
						{
							\caspar\core\Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\Permissions')->cloneGroupPermissions($old_group->getID(), $group->getID());
						}
						$message = TBGContext::getI18n()->__('The group was cloned');
					}
					else
					{
						$message = TBGContext::getI18n()->__('The group was added');
					}
					return $this->renderJSON(array('failed' => false, 'message' => $message, 'content' => $this->getTemplateHTML('configuration/groupbox', array('group' => $group))));
				}
				else
				{
					throw new Exception(TBGContext::getI18n()->__('Please enter a group name'));
				}
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage()));
			}
		}

		public function runGetGroupMembers(Request $request)
		{
			try
			{
				$group = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Group', (int) $request->getParameter('group_id'));
				$users = $group->getMembers();
				return $this->renderJSON(array('failed' => false, 'content' => $this->getTemplateHTML('configuration/groupuserlist', array('users' => $users))));
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage()));
			}
		}
		
		public function runDeleteEdition(Request $request)
		{
			if ($this->access_level == TBGSettings::ACCESS_FULL)
			{
				try
				{
					$theEdition = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Edition', $request->getParameter('edition_id'));
					
					$project = $theEdition->getProject();
					$theEdition->delete();
					$count = count(TBGEdition::getAllByProjectID($project->getID()));
					return $this->renderJSON(array('failed' => false, 'deleted' => true, 'itemcount' => $count, 'message' => TBGContext::getI18n()->__('Edition deleted')));
				}
				catch (Exception $e)
				{
					return $this->renderJSON(array('failed' => true, "error" => TBGContext::getI18n()->__('Could not delete this edition').", ".$e->getMessage()));
				}
			}
			return $this->renderJSON(array('failed' => true, "error" => $i18n->__("You don't have access to modify edition")));
		
		}

		public function runDeleteUser(Request $request)
		{
			try
			{
				try
				{
					$return_options = array('success' => true);
					$user = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\User', $request->getParameter('user_id'));
					if ($user->getGroup() instanceof \thebuggenie\entities\Group)
					{
						$return_options['update_groups'] = array('ids' => array(), 'membercounts' => array());
						$group_id = $user->getGroup()->getID();
						$return_options['update_groups']['ids'][] = $group_id;
						$return_options['update_groups']['membercounts'][$group_id] = $user->getGroup()->getNumberOfMembers();
					}
					if (count($user->getTeams()))
					{
						$return_options['update_teams'] = array('ids' => array(), 'membercounts' => array());
						foreach ($user->getTeams() as $team)
						{
							$team_id = $team->getID();
							$return_options['update_teams']['ids'][] = $team_id;
							$return_options['update_teams']['membercounts'][$team_id] = $team->getNumberOfMembers();
						}
					}
					if (in_array($user->getUsername(), array('administrator', 'guest')))
					{
						throw new Exception(TBGContext::getI18n()->__("You cannot delete this system user"));
					}
				}
				catch (Exception $e) { }
				if (!$user instanceof TBGUser)
				{
					throw new Exception(TBGContext::getI18n()->__("You cannot delete this user"));
				}
				$user->markAsDeleted();
				$user->save();
				$return_options['message'] = TBGContext::getI18n()->__('The user was deleted');
				$return_options['total_count'] = TBGUser::getUsersCount();
				$return_options['more_available'] = \thebuggenie\core\Context::getScope()->hasUsersAvailable();
				
				return $this->renderJSON($return_options);
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage()));
			}
		}

		public function runDeleteTeam(Request $request)
		{
			try
			{
				try
				{
					$team = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Team', $request->getParameter('team_id'));
				}
				catch (Exception $e) { }
				if (!$team instanceof TBGTeam)
				{
					throw new Exception(TBGContext::getI18n()->__("You cannot delete this team"));
				}
				$team->delete();
				return $this->renderJSON(array('success' => true, 'message' => TBGContext::getI18n()->__('The team was deleted'), 'total_count' => TBGTeam::getTeamsCount(), 'more_available' => \thebuggenie\core\Context::getScope()->hasTeamsAvailable()));
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage()));
			}
		}

		public function runAddTeam(Request $request)
		{
			try
			{
				$mode = $request->getParameter('mode');
				if ($team_name = $request->getParameter('team_name'))
				{
					if ($mode == 'clone')
					{
						try
						{
							$old_team = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Team', $request->getParameter('team_id'));
						}
						catch (Exception $e) { }
						if (!$old_team instanceof TBGTeam)
						{
							throw new Exception(TBGContext::getI18n()->__("You cannot clone this team"));
						}
					}
					if (TBGTeam::doesTeamNameExist(trim($team_name)))
					{
						throw new Exception(TBGContext::getI18n()->__("Please enter a team name that doesn't already exist"));
					}
					$team = new TBGTeam();
					$team->setName($team_name);
					$team->save();
					if ($mode == 'clone')
					{
						if ($request->getParameter('clone_permissions'))
						{
							\caspar\core\Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\Permissions')->cloneTeamPermissions($old_team->getID(), $team->getID());
						}
						if ($request->getParameter('clone_memberships'))
						{
							\caspar\core\Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\\TeamMembers')->cloneTeamMemberships($old_team->getID(), $team->getID());
						}
						$message = TBGContext::getI18n()->__('The team was cloned');
					}
					else
					{
						$message = TBGContext::getI18n()->__('The team was added');
					}
					return $this->renderJSON(array('failed' => false, 'message' => $message, 'content' => $this->getTemplateHTML('configuration/teambox', array('team' => $team)), 'total_count' => TBGTeam::getTeamsCount(), 'more_available' => \thebuggenie\core\Context::getScope()->hasTeamsAvailable()));
				}
				else
				{
					throw new Exception(TBGContext::getI18n()->__('Please enter a team name'));
				}
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage()));
			}
		}

		public function runGetTeamMembers(Request $request)
		{
			try
			{
				$team = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Team', (int) $request->getParameter('team_id'));
				$users = $team->getMembers();
				return $this->renderJSON(array('failed' => false, 'content' => $this->getTemplateHTML('configuration/teamuserlist', array('users' => $users))));
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage()));
			}
		}

		public function runFindUsers(Request $request)
		{
			$this->too_short = false;
			$findstring = $request->getParameter('findstring');
			if (mb_strlen($findstring) >= 1)
			{
				list ($this->users, $this->total_results) = Caspar::getB2DBInstance()->getTable('\\thebuggenie\\tables\Users')->findInConfig($findstring);
			}
			else
			{
				$this->too_short = true;
			}
			switch ($findstring)
			{
				case 'unactivated':
					$this->findstring = TBGContext::getI18n()->__('Unactivated users');
					break;
				case 'newusers':
					$this->findstring = TBGContext::getI18n()->__('New users');
					break;
				case 'all':
					$this->findstring = TBGContext::getI18n()->__('All users');
					break;
				default:
					$this->findstring = $findstring;
			}
			
		}

		public function runAddUser(Request $request)
		{
			try
			{
				if (!\thebuggenie\core\Context::getScope()->hasUsersAvailable())
				{
					throw new Exception(TBGContext::getI18n()->__('This instance of The Bug Genie cannot add more users'));
				}
				
				if ($username = $request->getParameter('username'))
				{
					$user = new TBGUser();
					$user->setUsername($username);
					$user->setRealname($username);
					$user->setBuddyname($username);
					$user->setEnabled();
					$user->setActivated();
					$user->setPassword(TBGUser::hashPassword(TBGUser::createPassword()));
					$user->setJoined();
					$user->save();
				}
				else
				{
					throw new Exception(TBGContext::getI18n()->__('Please enter a username'));
				}
				$this->getResponse()->setTemplate('configuration/findusers');
				$this->too_short = false;
				$this->created_user = true;
				$this->users = array($user);
				$this->total_results = 1;
				$this->title = TBGContext::getI18n()->__('User %username% created', array('%username%' => $username));
				$this->total_count = TBGUser::getUsersCount();
				$this->more_available = \thebuggenie\core\Context::getScope()->hasUsersAvailable();
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage()));
			}
		}

		public function runUpdateUser(Request $request)
		{
			try
			{
				$user = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\User', $request->getParameter('user_id'));
				if ($user instanceof TBGUser)
				{
					$testuser = TBGUser::getByUsername($request->getParameter('username'));
					if (!$testuser instanceof TBGUser || $testuser->getID() == $user->getID())
					{
						$user->setUsername($request->getParameter('username'));
					}
					else
					{
						return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('This username is already taken')));
					}
					$password_changed = false;
					if ($request->getParameter('password_action') == 'change' && $request->getParameter('new_password_1') && $request->getParameter('new_password_2'))
					{
						if ($request->getParameter('new_password_1') == $request->getParameter('new_password_2'))
						{
							$user->setPassword($request->getParameter('new_password_1'));
							$password_changed = true;
						}
						else
						{
							return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('Please enter the new password twice')));
						}
					}
					elseif ($request->getParameter('password_action') == 'random')
					{
						$random_password = TBGUser::createPassword();
						$user->setPassword($random_password);
						$password_changed = true;
					}
					$user->setRealname($request->getParameter('realname'));
					$return_options = array();
					try
					{
						if ($group = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Group', $request->getParameter('group')))
						{
							if ($user->getGroupID() != $group->getID())
							{
								$groups = array($user->getGroupID(), $group->getID());
								$return_options['update_groups'] = array('ids' => array(), 'membercounts' => array());
							}
							$user->setGroup($group);
						}
					}
					catch (Exception $e)
					{
						throw new Exception(TBGContext::getI18n()->__('Invalid user group'));
					}
					
					$existing_teams = array_keys($user->getTeams());
					$new_teams = array();
					$user->clearTeams();
					try
					{
						foreach ($request->getParameter('teams', array()) as $team_id => $team)
						{
							if ($team = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Team', $team_id))
							{
								$new_teams[] = $team_id;
								$user->addToTeam($team);
							}
						}
					}
					catch (Exception $e)
					{
						throw new Exception(TBGContext::getI18n()->__('One or more teams were invalid'));
					}
					
					try
					{
						$user->clearClients();
						foreach ($request->getParameter('clients', array()) as $client_id => $client)
						{
							if ($client = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Client', $client_id))
							{
								$new_clients[] = $client_id;
								$user->addToClient($client);
							}
						}
					}
					catch (Exception $e)
					{
						throw new Exception(TBGContext::getI18n()->__('One or more clients were invalid'));
					}
					$user->setBuddyname($request->getParameter('nickname'));
					$user->setActivated((bool) $request->getParameter('activated'));
					$user->setEmail($request->getParameter('email'));
					$user->setEnabled((bool) $request->getParameter('enabled'));
					$user->save();
					if (isset($groups))
					{
						foreach ($groups as $group_id)
						{
							if (!$group_id) continue;
							$return_options['update_groups']['ids'][] = $group_id;
							$return_options['update_groups']['membercounts'][$group_id] = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Group', $group_id)->getNumberOfMembers();
						}
					}
					if ($new_teams != $existing_teams)
					{
						$new_team_ids = array_diff($new_teams, $existing_teams);
						$existing_team_ids = array_diff($existing_teams, $new_teams);
						$teams_to_update = array_merge($new_team_ids, $existing_team_ids);
						$return_options['update_teams'] = array('ids' => array(), 'membercounts' => array());
						foreach ($teams_to_update as $team_id)
						{
							$return_options['update_teams']['ids'][] = $team_id;
							$return_options['update_teams']['membercounts'][$team_id] = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Team', $team_id)->getNumberOfMembers();
						}
					}
					$return_options['failed'] = false;
					$template_options = array('user' => $user);
					if (isset($random_password))
					{
						$template_options['random_password'] = $random_password;
					}
					$return_options['content'] = $this->getTemplateHTML('configuration/finduser_row', $template_options);
					$return_options['title'] = TBGContext::getI18n()->__('User updated!');
					if ($password_changed)
					{
						$return_options['message'] = TBGContext::getI18n()->__('The password was changed');
					}
					return $this->renderJSON($return_options);
				}
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('This user could not be updated: %message%', array('%message%' => $e->getMessage()))));
			}
			$this->getResponse()->setHttpStatus(400);
			return $this->renderJSON(array('failed' => true, 'error' => TBGContext::getI18n()->__('This user could not be updated')));
		}

		public function runGetPermissionsConfigurator(Request $request)
		{
			return $this->renderComponent('configuration/permissionsconfigurator', array('access_level' => $this->access_level, 'user_id' => $request->getParameter('user_id', 0), 'base_id' => $request->getParameter('base_id', 0)));
		}

		public function runConfigureProjectEdition(Request $request)
		{
			try
			{
				if ($edition_id = $request->getParameter('edition_id'))
				{
					$edition = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Edition', $edition_id);
					if ($request->isMethod(TBGRequest::POST))
					{
						if ($request->hasParameter('release_month') && $request->hasParameter('release_day') && $request->hasParameter('release_year'))
						{
							$release_date = mktime(0, 0, 1, $request->getParameter('release_month'), $request->getParameter('release_day'), $request->getParameter('release_year'));
							$edition->setReleaseDate($release_date);
						}
						
						if (($e_name = $request->getParameter('edition_name')) && trim($e_name) != '')
						{
							if ($e_name != $edition->getName())
							{
								if (in_array($e_name, $edition->getProject()->getEditions()))
								{
									throw new Exception(TBGContext::getI18n()->__('This edition already exists for this project'));
								}
								$edition->setName($e_name);
							}
						}
						else
						{
							throw new Exception(TBGContext::getI18n()->__('You need to specify a name for this edition'));
						}
							
						$edition->setDescription($request->getParameter('description', null, false));
						$edition->setDocumentationURL($request->getParameter('doc_url'));
						$edition->setPlannedReleased($request->getParameter('planned_release'));
						$edition->setReleased((int) $request->getParameter('released'));
						$edition->setLocked((bool) $request->getParameter('locked'));
						$edition->save();
						return $this->renderJSON(array('edition_name' => $edition->getName(), 'message' => TBGContext::getI18n()->__('Edition details saved')));
					}
					else
					{
						switch ($request->getParameter('mode'))
						{
							case 'releases':
							case 'components':
								$this->selected_section = $request->getParameter('mode');
								break;
							default:
								$this->selected_section = 'general';
						}
						$content = $this->getComponentHTML('configuration/projectedition', array('edition' => $edition, 'access_level' => $this->access_level, 'selected_section' => $this->selected_section));
						return $this->renderJSON(array('failed' => false, 'content' => $content));
					}
				}
				else
				{
					throw new Exception(TBGContext::getI18n()->__('Invalid edition id'));
				}
			}
			catch (Exception $e)
			{
				return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage()));
			}
		}

		public function runConfigureProject(Request $request)
		{
			try
			{
				if ($project_id = $request->getParameter('project_id'))
				{
					// Build list of valid targets for the subproject dropdown
					// The following items are banned from the list: current project, children of the current project
					// Any further tests and things get silly, so we will trap it when building breadcrumbs
					$project = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $project_id);
					$valid_subproject_targets = \thebuggenie\entities\Project::getValidSubprojects($project);					
					$content = $this->getComponentHTML('configuration/projectconfig', array('valid_subproject_targets' => $valid_subproject_targets, 'project' => $project, 'access_level' => $this->access_level, 'section' => 'hierarchy'));
					return $this->renderJSON(array('failed' => false, 'content' => $content));
				}
				else
				{
					throw new Exception(TBGContext::getI18n()->__('Invalid project id'));
				}
			}
			catch (Exception $e)
			{
				return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage()));
			}
		}

		public function runConfigureWorkflowSchemes(Request $request)
		{
			$this->schemes = TBGWorkflowScheme::getAll();
		}

		public function runConfigureWorkflows(Request $request)
		{
			$this->workflows = TBGWorkflow::getAll();
			if ($request->isMethod(TBGRequest::POST))
			{
				try
				{
					$workflow_name = $request->getParameter('workflow_name');
					$workflow = new TBGWorkflow();
					$workflow->setName($workflow_name);
					$workflow->save();
					$step = new TBGWorkflowStep();
					$step->setName(TBGContext::getI18n()->__('New'));
					$step->setWorkflow($workflow);
					$step->save();
					$this->forward(TBGContext::getRouting()->generate('configure_workflow'));
				}
				catch (Exception $e)
				{
					$this->error = $e->getMessage();
				}
			}
		}

		public function runConfigureWorkflowScheme(Request $request)
		{
			$this->workflow_scheme = null;
			$this->mode = $request->getParameter('mode', 'list');
			try
			{
				$this->workflow_scheme = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowScheme', $request->getParameter('scheme_id'));
				$this->issuetypes = TBGIssuetype::getAll();
				if (\thebuggenie\core\Context::getScope()->isCustomWorkflowsEnabled() && $this->mode == 'copy_scheme')
				{
					if ($new_name = $request->getParameter('new_name'))
					{
						$new_scheme = new TBGWorkflowScheme();
						$new_scheme->setName($new_name);
						$new_scheme->save();
						foreach ($this->issuetypes as $issuetype)
						{
							if ($this->workflow_scheme->hasWorkflowAssociatedWithIssuetype($issuetype))
							{
								$new_scheme->associateIssuetypeWithWorkflow($issuetype, $this->workflow_scheme->getWorkflowForIssuetype($issuetype));
							}
						}
						return $this->renderJSON(array('content' => $this->getTemplateHTML('configuration/workflowscheme', array('scheme' => $new_scheme))));
					}
					else
					{
						$this->error = TBGContext::getI18n()->__('Please enter a valid name');
					}
				}
				elseif (\thebuggenie\core\Context::getScope()->isCustomWorkflowsEnabled() && $this->mode == 'delete_scheme')
				{
					$this->workflow_scheme->delete();
					return $this->renderJSON(array('success' => true, 'message' => TBGContext::getI18n()->__('The workflow scheme was deleted')));
				}
				elseif (\thebuggenie\core\Context::getScope()->isCustomWorkflowsEnabled() && $request->isMethod(TBGRequest::POST))
				{
					foreach ($request->getParameter('workflow_id', array()) as $issuetype_id => $workflow_id)
					{
						$issuetype = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Issuetype', $issuetype_id);
						if ($workflow_id)
						{
							$workflow = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Workflow', $workflow_id);
							$this->workflow_scheme->associateIssuetypeWithWorkflow($issuetype, $workflow);
						}
						else
						{
							$this->workflow_scheme->unassociateIssuetype($issuetype);
						}
					}
					return $this->renderJSON(array('success' => true, 'message' => TBGContext::getI18n()->__('Workflow associations were updated')));
				}
			}
			catch (Exception $e)
			{
				if ($request->getRequestedFormat() == 'json')
				{
					$this->getResponse()->setHttpStatus(400);
					return $this->renderJSON(array('success' => false, 'message' => TBGContext::getI18n()->__('An error occured'), 'error' => $e->getMessage()));
				}
				else
				{
					$this->error = TBGContext::getI18n()->__('This workflow scheme does not exist');
				}
			}
		}

		public function runConfigureWorkflowSteps(Request $request)
		{
			$this->workflow = null;
			$this->mode = $request->getParameter('mode', 'list');
			try
			{
				$this->workflow = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Workflow', $request->getParameter('workflow_id'));
				if ($this->mode == 'copy_workflow')
				{
					if ($new_name = $request->getParameter('new_name'))
					{
						$new_workflow = $this->workflow->copy($new_name);
						return $this->renderJSON(array('content' => $this->getTemplateHTML('configuration/workflow', array('workflow' => $new_workflow)), 'total_count' => TBGWorkflow::getCustomWorkflowsCount(), 'more_available' => \thebuggenie\core\Context::getScope()->hasCustomWorkflowsAvailable()));
					}
					else
					{
						$this->error = TBGContext::getI18n()->__('Please enter a valid name');
					}
				}
				elseif ($this->mode == 'delete_workflow')
				{
					$this->workflow->delete();
					return $this->renderJSON(array('success' => true, 'message' => TBGContext::getI18n()->__('The workflow was deleted'), 'total_count' => TBGWorkflow::getCustomWorkflowsCount(), 'more_available' => \thebuggenie\core\Context::getScope()->hasCustomWorkflowsAvailable()));
				}
			}
			catch (Exception $e)
			{
				if ($request->getRequestedFormat() == 'json')
				{
					$this->getResponse()->setHttpStatus(400);
					return $this->renderJSON(array('success' => false, 'message' => TBGContext::getI18n()->__('An error occured'), 'error' => $e->getMessage()));
				}
				else
				{
					$this->error = TBGContext::getI18n()->__('This workflow does not exist');
				}
			}
		}

		public function runConfigureWorkflowStep(Request $request)
		{
			$this->workflow = null;
			$this->step = null;
			try
			{
				$this->workflow = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Workflow', $request->getParameter('workflow_id'));
				if ($request->getParameter('mode') == 'edit' && !$request->hasParameter('step_id'))
				{
					$this->step = new TBGWorkflowStep();
					$this->step->setWorkflow($this->workflow);
				}
				else
				{
					$this->step = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowStep', $request->getParameter('step_id'));
				}
				if ($request->isMethod(TBGRequest::POST) && $request->getParameter('mode') == 'delete_outgoing_transitions')
				{
					$this->step->deleteOutgoingTransitions();
					$this->forward(TBGContext::getRouting()->generate('configure_workflow_steps', array('workflow_id' => $this->workflow->getID())));
				}
				if ($request->isMethod(TBGRequest::POST) && $request->getParameter('mode') == 'delete' && !$this->step->hasIncomingTransitions())
				{
					$this->step->deleteOutgoingTransitions();
					$this->step->delete();
					$this->forward(TBGContext::getRouting()->generate('configure_workflow_steps', array('workflow_id' => $this->workflow->getID())));
				}
				elseif ($request->isMethod(TBGRequest::POST) && ($request->hasParameter('edit') || $request->getParameter('mode') == 'edit'))
				{
					$this->step->setName($request->getParameter('name'));
					$this->step->setDescription($request->getParameter('description'));
					$this->step->setLinkedStatusID($request->getParameter('status_id'));
					$this->step->setIsEditable((bool) $request->getParameter('is_editable'));
					$this->step->setIsClosed((bool) ($request->getParameter('state') == TBGIssue::STATE_CLOSED));
					$this->step->save();
					$this->forward(TBGContext::getRouting()->generate('configure_workflow_step', array('workflow_id' => $this->workflow->getID(), 'step_id' => $this->step->getID())));
				}
			}
			catch (Exception $e)
			{
				$this->error = TBGContext::getI18n()->__('This workflow / step does not exist');
			}
		}

		public function runConfigureWorkflowTransition(Request $request)
		{
			$this->workflow = null;
			$this->transition = null;
			
			try
			{
				$this->workflow = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Workflow', $request->getParameter('workflow_id'));
				if ($request->hasParameter('transition_id'))
				{
					$mode = $request->getParameter('mode');
					$this->transition = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowTransition', $request->getParameter('transition_id'));
					if ($request->isMethod(TBGRequest::POST))
					{
						if ($mode == 'delete')
						{
							$this->transition->deleteTransition($request->getParameter('direction'));
							return $this->renderJSON(array('failed' => false));
						}
						elseif ($mode == 'delete_action')
						{
							$this->action = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowTransitionAction', $request->getParameter('action_id'));
							$this->action->delete();
							return $this->renderJSON(array('failed' => false, 'message' => TBGContext::getI18n()->__('The action has been deleted')));
						}
						elseif ($mode == 'new_action')
						{
							$action = new TBGWorkflowTransitionAction();
							$action->setActionType($request->getParameter('action_type'));
							$action->setTransition($this->transition);
							$action->setWorkflow($this->workflow);
							$action->setTargetValue('');
							$action->save();
							return $this->renderJSON(array('failed' => false, 'content' => $this->getComponentHTML('configuration/workflowtransitionaction', array('action' => $action))));
						}
						elseif ($mode == 'update_action')
						{
							$this->action = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowTransitionAction', $request->getParameter('action_id'));
							$this->action->setTargetValue($request->getParameter('target_value'));
							$this->action->save();
							$text = $request->getParameter('target_value');
							switch ($this->action->getActionType())
							{
								case TBGWorkflowTransitionAction::ACTION_ASSIGN_ISSUE:
									$text = ($this->action->getTargetValue()) ? \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\User', (int) $this->action->getTargetValue())->getName() : TBGContext::getI18n()->__('User specified during transition');
									break;
								case TBGWorkflowTransitionAction::ACTION_SET_RESOLUTION:
									$text = ($this->action->getTargetValue()) ? \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Resolution', (int) $this->action->getTargetValue())->getName() : TBGContext::getI18n()->__('Resolution specified by user');
									break;
								case TBGWorkflowTransitionAction::ACTION_SET_REPRODUCABILITY:
									$text = ($this->action->getTargetValue()) ? \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Reproducability', (int) $this->action->getTargetValue())->getName() : TBGContext::getI18n()->__('Reproducability specified by user');
									break;
								case TBGWorkflowTransitionAction::ACTION_SET_STATUS:
									$text = ($this->action->getTargetValue()) ? \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Status', (int) $this->action->getTargetValue())->getName() : TBGContext::getI18n()->__('Status specified by user');
									break;
								case TBGWorkflowTransitionAction::ACTION_SET_MILESTONE:
									$text = ($this->action->getTargetValue()) ? \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Milestone', (int) $this->action->getTargetValue())->getName() : TBGContext::getI18n()->__('Milestone specified by user');
									break;
								case TBGWorkflowTransitionAction::ACTION_SET_PRIORITY:
									$text = ($this->action->getTargetValue()) ? \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Priority', (int) $this->action->getTargetValue())->getName() : TBGContext::getI18n()->__('Priority specified by user');
									break;
							}
							return $this->renderJSON(array('failed' => false, 'content' => $text));
						}
						elseif ($mode == 'delete_validation_rule')
						{
							$this->rule = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowTransitionValidationRule', $request->getParameter('rule_id'));
							$this->rule->delete();
							return $this->renderJSON(array('failed' => false, 'message' => TBGContext::getI18n()->__('The validation rule has been deleted')));
						}
						elseif ($mode == 'new_validation_rule')
						{
							$rule = new TBGWorkflowTransitionValidationRule();
							if ($request->getParameter('postorpre') == 'post')
							{
								$exists = (bool) ($this->transition->hasPostValidationRule($request->getParameter('rule')));
								if (!$exists) $rule->setPost();
							}
							elseif ($request->getParameter('postorpre') == 'pre')
							{
								$exists = (bool) ($this->transition->hasPreValidationRule($request->getParameter('rule')));
								if (!$exists) $rule->setPre();
							}
							if ($exists)
							{
								$this->getResponse()->setHttpStatus(400);
								return $this->renderJSON(array('failed' => true, 'message' => TBGContext::getI18n()->__('This validation rule already exist')));
							}
							$rule->setRule($request->getParameter('rule'));
							$rule->setRuleValue('');
							$rule->setTransition($this->transition);
							$rule->setWorkflow($this->workflow);
							$rule->save();
							
							return $this->renderJSON(array('failed' => false, 'content' => $this->getTemplateHTML('configuration/workflowtransitionvalidationrule', array('rule' => $rule))));
						}
						elseif ($mode == 'update_validation_rule')
						{
							$this->rule = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowTransitionValidationRule', $request->getParameter('rule_id'));
							$text = null;
							switch ($this->rule->getRule())
							{
								case TBGWorkflowTransitionValidationRule::RULE_MAX_ASSIGNED_ISSUES:
									$this->rule->setRuleValue($request->getParameter('rule_value'));
									$text = ($this->rule->getRuleValue()) ? $this->rule->getRuleValue() : TBGContext::getI18n()->__('Unlimited');
									break;
								case TBGWorkflowTransitionValidationRule::RULE_PRIORITY_VALID:
								case TBGWorkflowTransitionValidationRule::RULE_REPRODUCABILITY_VALID:
								case TBGWorkflowTransitionValidationRule::RULE_RESOLUTION_VALID:
								case TBGWorkflowTransitionValidationRule::RULE_STATUS_VALID:
									$this->rule->setRuleValue(join(',', $request->getParameter('rule_value')));
									$text = ($this->rule->getRuleValue()) ? $this->rule->getRuleValueAsJoinedString() : TBGContext::getI18n()->__('Any valid value');
									break;
								//case TBGWorkflowTransitionValidationRule::RULE_:
								//	$text = ($this->rule->getRuleValue()) ? $this->rule->getRuleValue() : TBGContext::getI18n()->__('Unlimited');
								//	break;
							}
							$this->rule->save();
							return $this->renderJSON(array('failed' => false, 'content' => $text));
						}
						elseif ($request->getParameter('transition_name') && $request->getParameter('outgoing_step_id') && $request->hasParameter('template'))
						{
							$this->transition->setName($request->getParameter('transition_name'));
							$this->transition->setDescription($request->getParameter('transition_description'));
							if ($request->getParameter('template'))
							{
								$this->transition->setTemplate($request->getParameter('template'));
							}
							else
							{
								$this->transition->setTemplate(null);
							}
							try
							{
								$step = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowStep', $request->getParameter('outgoing_step_id'));
							}
							catch (Exception $e) {}
							$this->transition->setOutgoingStep($step);
							$this->transition->save();
							$transition = $this->transition;
							$redirect_transition = true;
						}
					}
				}
				elseif ($request->isMethod(TBGRequest::POST) && $request->hasParameter('step_id'))
				{
					$step = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowStep', $request->getParameter('step_id'));
					/*if ($step->isCore() || $workflow->isCore())
					{
						throw new InvalidArgumentException("The default workflow cannot be edited");
					}*/
					if ($request->getParameter('add_transition_type') == 'existing' && $request->hasParameter('existing_transition_id'))
					{
						$transition = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowTransition', $request->getParameter('existing_transition_id'));
						$redirect_transition = false;
					}
					else
					{
						if ($request->getParameter('transition_name') && $request->getParameter('outgoing_step_id') && $request->hasParameter('template'))
						{
							if (($outgoing_step = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowStep', (int) $request->getParameter('outgoing_step_id'))) && $step instanceof TBGWorkflowStep)
							{
								if (array_key_exists($request->getParameter('template'), TBGWorkflowTransition::getTemplates()))
								{
									$transition = new TBGWorkflowTransition();
									$transition->setWorkflow($this->workflow);
									$transition->setName($request->getParameter('transition_name'));
									$transition->setDescription($request->getParameter('transition_description'));
									$transition->setOutgoingStep($outgoing_step);
									$transition->setTemplate($request->getParameter('template'));
									$transition->save();
									$step->addOutgoingTransition($transition);
									$redirect_transition = true;
								}
								else
								{
									throw new InvalidArgumentException(TBGContext::getI18n()->__('Please select a valid template'));
								}
							}
							else
							{
								throw new InvalidArgumentException(TBGContext::getI18n()->__('Please select a valid outgoing step'));
							}
						}
						else
						{
							throw new InvalidArgumentException(TBGContext::getI18n()->__('Please fill in all required fields'));
						}
					}
					$step->addOutgoingTransition($transition);
				}
				else
				{
					throw new InvalidArgumentException('Invalid action');
				}
			}
			catch (InvalidArgumentException $e)
			{
				//throw $e;
				$this->error = $e->getMessage();
			}
			catch (Exception $e)
			{
				throw $e;
				$this->error = TBGContext::getI18n()->__('This workflow / transition does not exist');
			}
			if (isset($redirect_transition) && $redirect_transition)
			{
				$this->forward(TBGContext::getRouting()->generate('configure_workflow_transition', array('workflow_id' => $this->workflow->getID(), 'transition_id' => $transition->getID())));
			}
			elseif (isset($redirect_transition))
			{
				$this->forward(TBGContext::getRouting()->generate('configure_workflow_steps', array('workflow_id' => $this->workflow->getID())));
			}
		}

		public function getAccessLevel($section, $module)
		{
			return (\caspar\core\Caspar::getUser()->canSaveConfiguration($section, $module)) ? TBGSettings::ACCESS_FULL : TBGSettings::ACCESS_READ;
		}
		
		public function runAddClient(Request $request)
		{
			try
			{
				$mode = $request->getParameter('mode');
				if ($client_name = $request->getParameter('client_name'))
				{
					if (TBGClient::doesClientNameExist(trim($request->getParameter('client_name'))))
					{
						throw new Exception(TBGContext::getI18n()->__("Please enter a client name that doesn't already exist"));
					}
					$client = new TBGClient();
					$client->setName($request->getParameter('client_name'));
					$client->save();

					$message = TBGContext::getI18n()->__('The client was added');
					return $this->renderJSON(array('failed' => false, 'message' => $message, 'content' => $this->getTemplateHTML('configuration/clientbox', array('client' => $client))));
				}
				else
				{
					throw new Exception(TBGContext::getI18n()->__('Please enter a client name'));
				}
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage()));
			}
		}

		public function runDeleteClient(Request $request)
		{
			try
			{
				try
				{
					$client = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Client', $request->getParameter('client_id'));
				}
				catch (Exception $e) { }
				if (!$client instanceof TBGClient)
				{
					throw new Exception(TBGContext::getI18n()->__("You cannot delete this client"));
				}
				
				if (\thebuggenie\entities\Project::getAllByClientID($client->getID()) !== null)
				{
					foreach (\thebuggenie\entities\Project::getAllByClientID($client->getID()) as $project)
					{
						$project->setClient(null);
						$project->save();
					}
				}
				
				$client->delete();
				return $this->renderJSON(array('success' => true, 'message' => TBGContext::getI18n()->__('The client was deleted')));
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage()));
			}
		}
		
		public function runGetClientMembers(Request $request)
		{
			try
			{
				$client = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Client', (int) $request->getParameter('client_id'));
				$users = $client->getMembers();
				return $this->renderJSON(array('failed' => false, 'content' => $this->getTemplateHTML('configuration/clientuserlist', array('users' => $users))));
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage()));
			}
		}
		
		public function runEditClient(Request $request)
		{
			try
			{
				try
				{
					$client = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Client', $request->getParameter('client_id'));
				}
				catch (Exception $e) { }
				if (!$client instanceof TBGClient)
				{
					throw new Exception(TBGContext::getI18n()->__("You cannot edit this client"));
				}
				
				if (TBGClient::doesClientNameExist(trim($request->getParameter('client_name'))) && $request->getParameter('client_name') != $client->getName())
				{
					throw new Exception(TBGContext::getI18n()->__("Please enter a client name that doesn't already exist"));
				}
				
				$client->setName($request->getParameter('client_name'));
				$client->setEmail($request->getParameter('client_email'));
				$client->setWebsite($request->getParameter('client_website'));
				$client->setTelephone($request->getParameter('client_telephone'));
				$client->setFax($request->getParameter('client_fax'));
				$client->save();
				return $this->renderJSON(array('success' => true, 'content' => $this->getTemplateHTML('configuration/clientbox', array('client' => $client)), 'message' => TBGContext::getI18n()->__('The client was saved')));
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('failed' => true, 'error' => $e->getMessage()));
			}
		}
		
		public function runImportCSV(Request $request)
		{
			$content = $this->getTemplateHTML('configuration/importcsv', array('type' => $request->getParameter('type')));
			return $this->renderJSON(array('failed' => false, 'content' => $content));
		}
		
		public function runDoImportCSV(Request $request)
		{
			try
			{
				if ($request->getParameter('csv_data') == '')
				{
					throw new Exception(TBGContext::getI18n()->__('No data supplied to import'));
				}
				
				// Split data into individual lines
				$data = str_replace("\r\n", "\n", $request->getParameter('csv_data'));
				$data = explode("\n", $data);
				if (count($data) <= 1)
				{
					throw new Exception(TBGContext::getI18n()->__('Insufficient data to import'));
				}
				$headerrow = $data[0];
				$headerrow = html_entity_decode($headerrow, ENT_QUOTES);
				$headerrow = explode(',', $headerrow);
				$headerrow2 = array();
				for ($i = 0; $i != count($headerrow); $i++)
				{
					$headerrow2[$i] = trim($headerrow[$i], '" ');
				}
				
				$errors = array();
				
				// inspect for correct rows
				switch ($request->getParameter('type'))
				{
					case 'clients':
						$namecol = null;
						$emailcol = null;
						$telephonecol = null;
						$faxcol = null;
						$websitecol = null;
						
						for ($i = 0; $i != count($headerrow2); $i++)
						{
							if ($headerrow2[$i] == 'name'):
								$namecol = $i;
							elseif ($headerrow2[$i] == 'email'):
								$emailcol = $i;
							elseif ($headerrow2[$i] == 'telephone'):
								$telephonecol = $i;
							elseif ($headerrow2[$i] == 'fax'):
								$faxcol = $i;
							elseif ($headerrow2[$i] == 'website'):
								$websitecol = $i;
							endif;
						}
						
						$rowlength = count($headerrow2);
						
						if ($namecol === null)
						{
							$errors[] = TBGContext::getI18n()->__('Required column \'%col%\' not found in header row', array('%col%' => 'name'));
						}
						
						break;
					case 'projects':
						$namecol = null;
						$prefix = null;
						$scrum = null;
						$owner = null;
						$owner_type = null;
						$lead = null;
						$lead_type = null;
						$qa = null;
						$qa_type = null;
						$descr = null;
						$doc_url = null;
						$freelance = null;
						$en_builds = null;
						$en_comps = null;
						$en_editions = null;
						$workflow_id = null;
						$client = null;
						$show_summary = null;
						$summary_type = null;
						$issuetype_scheme = null;
						$allow_reporting = null;
						$autoassign = null;
						
						for ($i = 0; $i != count($headerrow2); $i++)
						{
							if ($headerrow2[$i] == 'name'):
								$namecol = $i;
							elseif ($headerrow2[$i] == 'prefix'):
								$prefix = $i;
							elseif ($headerrow2[$i] == 'scrum'):
								$scrum = $i;
							elseif ($headerrow2[$i] == 'owner'):
								$owner = $i;
							elseif ($headerrow2[$i] == 'owner_type'):
								$owner_type = $i;
							elseif ($headerrow2[$i] == 'lead'):
								$lead = $i;
							elseif ($headerrow2[$i] == 'lead_type'):
								$lead_type = $i;
							elseif ($headerrow2[$i] == 'qa'):
								$qa = $i;
							elseif ($headerrow2[$i] == 'qa_type'):
								$qa_type = $i;
							elseif ($headerrow2[$i] == 'descr'):
								$descr = $i;
							elseif ($headerrow2[$i] == 'doc_url'):
								$doc_url = $i;
							elseif ($headerrow2[$i] == 'freelance'):
								$freelance = $i;
							elseif ($headerrow2[$i] == 'en_builds'):
								$en_builds = $i;
							elseif ($headerrow2[$i] == 'en_comps'):
								$en_comps = $i;
							elseif ($headerrow2[$i] == 'en_editions'):
								$en_editions = $i;
							elseif ($headerrow2[$i] == 'workflow_id'):
								$workflow_id = $i;
							elseif ($headerrow2[$i] == 'client'):
								$client = $i;
							elseif ($headerrow2[$i] == 'show_summary'):
								$show_summary = $i;
							elseif ($headerrow2[$i] == 'summary_type'):
								$summary_type = $i;
							elseif ($headerrow2[$i] == 'issuetype_scheme'):
								$issuetype_scheme = $i;
							elseif ($headerrow2[$i] == 'allow_reporting'):
								$allow_reporting = $i;
							elseif ($headerrow2[$i] == 'autoassign'):
								$autoassign = $i;
							endif;
						}
						
						$rowlength = count($headerrow2);
						
						if ($namecol === null)
						{
							$errors[] = TBGContext::getI18n()->__('Required column \'%col%\' not found in header row', array('%col%' => 'name'));
						}
						
						break;
					case 'issues':
						$title = null;
						$project = null;
						$descr = null;
						$repro = null;
						$state = null;
						$status = null;
						$posted_by = null;
						$owner = null;
						$owner_type = null;
						$assigned = null;
						$assigned_type = null;
						$resolution = null;
						$issue_type = null;
						$priority = null;
						$category = null;
						$severity = null;
						$reproducability = null;
						$votes = null;
						$percentage = null;
						$blocking = null;
						$milestone = null;
						
						for ($i = 0; $i != count($headerrow2); $i++)
						{
							if ($headerrow2[$i] == 'title'):
								$title = $i;
							elseif ($headerrow2[$i] == 'project'):
								$project = $i;
							elseif ($headerrow2[$i] == 'assigned'):
								$assigned = $i;
							elseif ($headerrow2[$i] == 'repro'):
								$repro = $i;
							elseif ($headerrow2[$i] == 'state'):
								$state = $i;
							elseif ($headerrow2[$i] == 'status'):
								$status = $i;
							elseif ($headerrow2[$i] == 'posted_by'):
								$posted_by = $i;
							elseif ($headerrow2[$i] == 'owner'):
								$owner = $i;
							elseif ($headerrow2[$i] == 'owner_type'):
								$owner_type = $i;
							elseif ($headerrow2[$i] == 'assigned'):
								$assigned = $i;
							elseif ($headerrow2[$i] == 'assigned_type'):
								$assigned_type = $i;
							elseif ($headerrow2[$i] == 'resolution'):
								$resolution = $i;
							elseif ($headerrow2[$i] == 'issue_type'):
								$issue_type = $i;
							elseif ($headerrow2[$i] == 'priority'):
								$priority = $i;
							elseif ($headerrow2[$i] == 'category'):
								$category = $i;
							elseif ($headerrow2[$i] == 'severity'):
								$severity = $i;
							elseif ($headerrow2[$i] == 'reproducability'):
								$reproducability = $i;
							elseif ($headerrow2[$i] == 'votes'):
								$votes = $i;
							elseif ($headerrow2[$i] == 'percentage'):
								$percentage = $i;
							elseif ($headerrow2[$i] == 'blocking'):
								$blocking = $i;
							elseif ($headerrow2[$i] == 'type'):
								$issue_type = $i;
							elseif ($headerrow2[$i] == 'milestone'):
								$milestone = $i;
							endif;
						}
						
						$rowlength = count($headerrow2);
						
						if ($title === null)
						{
							$errors[] = TBGContext::getI18n()->__('Required column \'%col%\' not found in header row', array('%col%' => 'title'));
						}
						
						if ($project === null)
						{
							$errors[] = TBGContext::getI18n()->__('Required column \'%col%\' not found in header row', array('%col%' => 'project'));
						}
										
						if ($issue_type === null)
						{
							$errors[] = TBGContext::getI18n()->__('Required column \'%col%\' not found in header row', array('%col%' => 'issue_type'));
						}
						
						break;
					default:
						throw new Exception('Sorry, this type is unimplemented');
						break;
				}
				
				// Check if rows are long enough
				for ($i = 1; $i != count($data); $i++)
				{
					$activerow = $data[$i];
					$activerow = html_entity_decode($activerow, ENT_QUOTES);
					$activerow = explode(',', $activerow);
					
					if (count($activerow) != $rowlength)
					{
						$errors[] = TBGContext::getI18n()->__('Row %row% does not have the same number of elements as the header row', array('%row%' => $i+1));
					}
				}
				
				reset($data);
				
				// Check if fields are empty
				for ($i = 1; $i != count($data); $i++)
				{
					$activerow = $data[$i];
					$activerow = html_entity_decode($activerow, ENT_QUOTES);
					$activerow = explode(',', $activerow);
					
					for ($j = 0; $j != count($activerow); $j++)
					{
						if ($activerow[$j] == '' || $activerow[$j] == '""')
						{
							$errors[] = TBGContext::getI18n()->__('Row %row% column %col% has no value', array('%col%' => $j+1, '%row%' => $i+1));
						}
					}
				}
				
				if (count($errors) == 0)
				{
					// Check if fields are valid
					switch ($request->getParameter('type'))
					{
						case 'projects':
							for ($i = 1; $i != count($data); $i++)
							{
								$activerow = $data[$i];
								$activerow = html_entity_decode($activerow, ENT_QUOTES);
								$activerow = explode(',', $activerow);
								
								// Check if project exists
								$key = trim($activerow[$namecol], '" ');
								$key = str_replace(' ', '', $key);
								$key = mb_strtolower($key);
								
								$tmp = \thebuggenie\entities\Project::getByKey($key);
								
								if ($tmp !== null)
								{
									$errors[] = TBGContext::getI18n()->__('Row %row%: A project with this name already exists', array('%row%' => $i+1));
								}
								
								// First off are booleans
								$boolitems = array($scrum, $allow_reporting, $autoassign, $freelance, $en_builds, $en_comps, $en_editions, $show_summary);
								
								foreach ($boolitems as $boolitem)
								{
									if ($boolitem !== null && trim($activerow[$boolitem], '"') != 0 && trim($activerow[$boolitem], '"') != 1)
									{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be 1/0)', array('%col%' => $boolitem+1, '%row%' => $i+1));
									}
								}
								
								// Now identifiables
								$identifiableitems = array(array($qa, $qa_type), array($lead, $lead_type), array($owner, $owner_type));
								
								foreach ($identifiableitems as $identifiableitem)
								{
									if (($identifiableitem[0] === null || $identifiableitem[1] === null) && !($identifiableitem[0] === null && $identifiableitem[1] === null))
									{
											$errors[] = TBGContext::getI18n()->__('Row %row%: Both the type and item ID must be supplied for owner/lead/qa fields', array('%row%' => $i+1));
											continue;
									}
									
									if ($identifiableitem[1] !== null && trim($activerow[$identifiableitem[1]], '"') != 1 && trim($activerow[$identifiableitem[1]], '"') != 2)
									{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be 1 for a user or 2 for a team)', array('%col%' => $identifiableitem[1]+1, '%row%' => $i+1));
									}
									
									if ($identifiableitem[0] !== null && !(is_numeric(trim($activerow[$identifiableitem[0]], '"'))))
									{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $identifiableitem[0]+1, '%row%' => $i+1));
									}
									elseif ($identifiableitem[0] !== null && (is_numeric(trim($activerow[$identifiableitem[0]], '"'))))
									{
										// check if they exist
										switch (trim($activerow[$identifiableitem[1]], '"'))
										{
											case 1:
												try
												{
													\caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\User', trim($activerow[$identifiableitem[0]], '" '));
												}
												catch (Exception $e)
												{
													$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: user does not exist', array('%col%' => $identifiableitem[0]+1, '%row%' => $i+1));
												}
												break;
											case 2:
												try
												{
													\caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Team', trim($activerow[$identifiableitem[0]], '" '));
												}
												catch (Exception $e)
												{
													$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: team does not exist', array('%col%' => $identifiableitem[0]+1, '%row%' => $i+1));
												}
												break;
										}
									}
								}
								
								// Now check client exists
								if ($client !== null)
								{
									if (!is_numeric(trim($activerow[$client], '"')))
									{
										$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $client+1, '%row%' => $i+1));
									}
									else
									{
										try
										{
											\caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Client', trim($activerow[$client], '" '));
										}
										catch (Exception $e)
										{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: client does not exist', array('%col%' => $client+1, '%row%' => $i+1));
										}
									}
								}
								
								// Now check if workflow exists
								if ($workflow_id !== null)
								{
									if (!is_numeric(trim($activerow[$workflow_id], '"')))
									{
										$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $workflow_id+1, '%row%' => $i+1));
									}
									else
									{
										try
										{
											\caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowScheme', trim($activerow[$workflow_id], '" '));
										}
										catch (Exception $e)
										{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: workflow scheme does not exist', array('%col%' => $workflow_id+1, '%row%' => $i+1));
										}
									}
								}
								
								// Now check if issuetype scheme
								if ($issuetype_scheme !== null)
								{
									if (!is_numeric(trim($activerow[$issuetype_scheme], '"')))
									{
										$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $issuetype_scheme+1, '%row%' => $i+1));
									}
									else
									{
										try
										{
											\caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\IssuetypeScheme', trim($activerow[$issuetype_scheme], '" '));
										}
										catch (Exception $e)
										{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: issuetype scheme does not exist', array('%col%' => $issuetype_scheme+1, '%row%' => $i+1));
										}
									}
								}
								
								// Finally check if the summary type is valid. At this point, your error list has probably become so big it has eaten up all your available RAM...
								if ($summary_type !== null)
								{
									if (trim($activerow[$summary_type], '"') != 'issuetypes' && trim($activerow[$summary_type], '"') != 'milestones')
									{
										$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be \'issuetypes\' or \'milestones\')', array('%col%' => $summary_type+1, '%row%' => $i+1));
									}
								}
							}
							break;
						case 'issues':
							for ($i = 1; $i != count($data); $i++)
							{
								$activerow = $data[$i];
								$activerow = html_entity_decode($activerow, ENT_QUOTES);
								$activerow = explode(',', $activerow);
								
								// Check if project exists
								try
								{
									$prjtmp = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $activerow[$project]);
								}
								catch (Exception $e)
								{
									$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: Project does not exist', array('%col%' => $project+1, '%row%' => $i+1));
									break;
								}
								
								// First off are booleans
								$boolitems = array($state, $blocking);
								
								foreach ($boolitems as $boolitem)
								{
									if ($boolitem !== null && trim($activerow[$boolitem], '"') != 0 && trim($activerow[$boolitem], '"') != 1)
									{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be 1/0)', array('%col%' => $boolitem+1, '%row%' => $i+1));
									}
								}
								
								// Now numerics
								$numericitems = array($votes, $percentage);
								
								foreach ($numericitems as $numericitem)
								{
									if ($numericitem !== null && !(is_numeric(trim($activerow[$numericitem], '"'))))
									{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $numericitem+1, '%row%' => $i+1));
									}
								}
								
								// Percentage must be 0-100
								if ($numericitem !== null && ((trim($activerow[$percentage], '"') < 0) || (trim($activerow[$percentage], '"') > 100)))
								{
									$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: Percentage must be from 0 to 100 inclusive', array('%col%' => $percentage+1, '%row%' => $i+1));
								}
									
								// Now identifiables
								$identifiableitems = array(array($owner, $owner_type), array($assigned, $assigned_type));
								
								foreach ($identifiableitems as $identifiableitem)
								{
									if (($identifiableitem[0] === null || $identifiableitem[1] === null) && !($identifiableitem[0] === null && $identifiableitem[1] === null))
									{
											$errors[] = TBGContext::getI18n()->__('Row %row%: Both the type and item ID must be supplied for owner/lead/qa fields', array('%row%' => $i+1));
											continue;
									}
									
									if ($identifiableitem[1] !== null && trim($activerow[$identifiableitem[1]], '"') != 1 && trim($activerow[$identifiableitem[1]], '"') != 2)
									{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be 1 for a user or 2 for a team)', array('%col%' => $identifiableitem[1]+1, '%row%' => $i+1));
									}
									
									if ($identifiableitem[0] !== null && !(is_numeric(trim($activerow[$identifiableitem[0]], '"'))))
									{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $identifiableitem[0]+1, '%row%' => $i+1));
									}
									elseif ($identifiableitem[0] !== null && (is_numeric(trim($activerow[$identifiableitem[0]], '"'))))
									{
										// check if they exist
										switch (trim($activerow[$identifiableitem[1]], '"'))
										{
											case 1:
												try
												{
													\caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\User', trim($activerow[$identifiableitem[0]], '" '));
												}
												catch (Exception $e)
												{
													$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: user does not exist', array('%col%' => $identifiableitem[0]+1, '%row%' => $i+1));
												}
												break;
											case 2:
												try
												{
													\caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\Team', trim($activerow[$identifiableitem[0]], '" '));
												}
												catch (Exception $e)
												{
													$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: team does not exist', array('%col%' => $identifiableitem[0]+1, '%row%' => $i+1));
												}
												break;
										}
									}
								}
								
								// Now check user exists for postedby
								if ($posted_by !== null)
								{
									if (!is_numeric(trim($activerow[$posted_by], '"')))
									{
										$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $posted_by+1, '%row%' => $i+1));
									}
									else
									{
										try
										{
											\caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\User', trim($activerow[$posted_by], '" '));
										}
										catch (Exception $e)
										{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: user does not exist', array('%col%' => $posted_by+1, '%row%' => $i+1));
										}
									}
								}
								
								// Now check milestone exists and is valid
								if ($milestone !== null)
								{
									if (!is_numeric(trim($activerow[$milestone], '"')))
									{
										$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $milestone+1, '%row%' => $i+1));
									}
									else
									{
										try
										{
											$milestonetmp = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Milestone', trim($activerow[$milestone], '" '));
											if ($milestonetmp->getProject()->getID() != $activerow[$project])
											{
												$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: milestone does not apply to the specified project', array('%col%' => $milestone+1, '%row%' => $i+1));
											}
										}
										catch (Exception $e)
										{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: milestone does not exist', array('%col%' => $milestone+1, '%row%' => $i+1));
										}
									}
								}
								
								// status
								if ($status !== null)
								{
									if (!is_numeric(trim($activerow[$status], '"')))
									{
										$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $status+1, '%row%' => $i+1));
									}
									else
									{
										try
										{
											\caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Status', trim($activerow[$status], '" '));
										}
										catch (Exception $e)
										{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: status does not exist', array('%col%' => $status+1, '%row%' => $i+1));
										}
									}
								}
								
								// resolution
								if ($resolution !== null)
								{
									if (!is_numeric(trim($activerow[$resolution], '"')))
									{
										$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $resolution+1, '%row%' => $i+1));
									}
									else
									{
										try
										{
											\caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Resolution', trim($activerow[$resolution], '" '));
										}
										catch (Exception $e)
										{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: resolution does not exist', array('%col%' => $resolution+1, '%row%' => $i+1));
										}
									}
								}
								
								// priority
								if ($priority !== null)
								{
									if (!is_numeric(trim($activerow[$priority], '"')))
									{
										$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $priority+1, '%row%' => $i+1));
									}
									else
									{
										try
										{
											\caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Priority', trim($activerow[$priority], '" '));
										}
										catch (Exception $e)
										{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: priority does not exist', array('%col%' => $priority+1, '%row%' => $i+1));
										}
									}
								}
								
								// category
								if ($category !== null)
								{
									if (!is_numeric(trim($activerow[$category], '"')))
									{
										$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $category+1, '%row%' => $i+1));
									}
									else
									{
										try
										{
											\caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Category', trim($activerow[$category], '" '));
										}
										catch (Exception $e)
										{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: category does not exist', array('%col%' => $category+1, '%row%' => $i+1));
										}
									}
								}
								
								// severity
								if ($severity !== null)
								{
									if (!is_numeric(trim($activerow[$severity], '"')))
									{
										$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $severity+1, '%row%' => $i+1));
									}
									else
									{
										try
										{
											\caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Severity', trim($activerow[$severity], '" '));
										}
										catch (Exception $e)
										{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: severity does not exist', array('%col%' => $severity+1, '%row%' => $i+1));
										}
									}
								}
								
								// reproducability
								if ($reproducability !== null)
								{
									if (!is_numeric(trim($activerow[$reproducability], '"')))
									{
										$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $reproducability+1, '%row%' => $i+1));
									}
									else
									{
										try
										{
											\caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Reproducability', trim($activerow[$reproducability], '" '));
										}
										catch (Exception $e)
										{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: reproducability does not exist', array('%col%' => $reproducability+1, '%row%' => $i+1));
										}
									}
								}
								
								// type
								if ($issue_type !== null)
								{
									if (!is_numeric(trim($activerow[$issue_type], '"')))
									{
										$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: invalid value (must be a number)', array('%col%' => $issue_type+1, '%row%' => $i+1));
									}
									else
									{
										try
										{
											$typetmp = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\Issuetype', trim($activerow[$issue_type], '" '));
											if (!($prjtmp->getIssuetypeScheme()->isSchemeAssociatedWithIssuetype($typetmp)))
												$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: this project does not support issues of this type (%type%)', array('%type%' => $typetmp->getName(), '%col%' => $issue_type+1, '%row%' => $i+1));
										}
										catch (Exception $e)
										{
											$errors[] = TBGContext::getI18n()->__('Row %row% column %col%: issue type does not exist', array('%col%' => $issue_type+1, '%row%' => $i+1));
										}
									}
								}
							}
							break;
					}
				}
					
				// Handle errors
				if (count($errors) != 0)
				{
					$errordiv = '<ul>';
					foreach ($errors as $error)
					{
						$errordiv .= '<li>'.$error.'</li>';
					}
					$errordiv .= '</ul>';
					$this->getResponse()->setHttpStatus(400);
					return $this->renderJSON(array('failed' => true, 'errordetail' => $errordiv, 'error' => TBGContext::getI18n()->__('Errors occured while importing, see the error list in the import screen for further details')));
				}
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('failed' => true, 'errordetail' => $e->getMessage(), 'error' => $e->getMessage()));
			}
				
			if ($request->getParameter('csv_dry_run'))
			{
				return $this->renderJSON(array('failed' => false, 'message' => TBGContext::getI18n()->__('Dry-run successful, you can now uncheck the dry-run box and import your data.')));
			}
			else
			{
				switch ($request->getParameter('type'))
				{
					case 'clients':
						for ($i = 1; $i != count($data); $i++)
						{
							try
							{
								$activerow = $data[$i];
								$activerow = html_entity_decode($activerow, ENT_QUOTES);
								$activerow = explode(',', $activerow);
								$client = new TBGClient();
								$client->setName(trim($activerow[$namecol], '" '));
								
								if ($emailcol !== null)
									$client->setEmail(trim($activerow[$emailcol], '" '));
									
								if ($websitecol !== null)
									$client->setWebsite(trim($activerow[$websitecol], '" '));
									
								if ($faxcol !== null)
									$client->setFax(trim($activerow[$faxcol], '" '));
								
								if ($telephonecol !== null)
									$client->setTelephone(trim($activerow[$telephonecol], '" '));
									
								$client->save();
							}
							catch (Exception $e)
							{
									$errors[] = TBGContext::getI18n()->__('Row %row% failed: %err%', array('%row%' => $i+1, '%err%' => $e->getMessage()));
							}
						}
						break;
					case 'projects':
						for ($i = 1; $i != count($data); $i++)
						{
							try
							{
								$activerow = $data[$i];
								$activerow = html_entity_decode($activerow, ENT_QUOTES);
								$activerow = explode(',', $activerow);
								$project = new TBGProject();
								$project->setName(trim($activerow[$namecol], '" '));
								
								if ($prefix !== null)
								{
									$project->setPrefix(trim($activerow[$prefix], '" '));
									$project->setUsePrefix(true);
								}
									
								if ($scrum !== null)
								{
									if (trim($activerow[$websitecol], '"') == '1')
										$project->setUseScrum(true);
								}
								
								if ($owner !== null && $owner_type !== null)
								{
									switch (trim($activerow[$owner_type], '"'))
									{
										case TBGIdentifiableClass::TYPE_USER:
											$user = new TBGUser(trim($activerow[$owner], '" '));
											$project->setOwner($user);
											break;
										case TBGIdentifiableClass::TYPE_TEAM:
											$team = new TBGTeam(trim($activerow[$owner], '" '));
											$project->setOwner($team);
											break;
									}
								}
								
								if ($lead !== null && $lead_type !== null)
								{
									switch (trim($activerow[$lead_type], '"'))
									{
										case TBGIdentifiableClass::TYPE_USER:
											$user = new TBGUser(trim($activerow[$lead], '" '));
											$project->setLeader($user);
											break;
										case TBGIdentifiableClass::TYPE_TEAM:
											$team = new TBGTeam(trim($activerow[$lead], '" '));
											$project->setLeader($team);
											break;
									}
								}
								
								if ($qa !== null && $qa_type !== null)
								{
									switch (trim($activerow[$qa_type], '"'))
									{
										case TBGIdentifiableClass::TYPE_USER:
											$user = new TBGUser(trim($activerow[$qa], '" '));
											$project->setQaResponsible($user);
											break;
										case TBGIdentifiableClass::TYPE_TEAM:
											$team = new TBGTeam(trim($activerow[$qa], '" '));
											$project->setQaResponsible($team);
											break;
									}
								}
								
								if ($descr !== null)
									$project->setDescription(trim($activerow[$descr], '" '));
									
								if ($doc_url !== null)
									$project->setDocumentationUrl(trim($activerow[$doc_url], '" '));
									
								if ($freelance !== null)
								{
									if (trim($activerow[$freelance], '"') == '1')
										$project->setChangeIssuesWithoutWorkingOnThem(true);
								}
								
								if ($en_builds !== null)
								{
									if (trim($activerow[$en_builds], '"') == '1')
										$project->setBuildsEnabled(true);
								}
								
								if ($en_comps !== null)
								{
									if (trim($activerow[$en_comps], '"') == '1')
										$project->setComponentsEnabled(true);
								}
								
								if ($en_editions !== null)
								{
									if (trim($activerow[$en_editions], '"') == '1')
										$project->setEditionsEnabled(true);
								}
								
								if ($workflow_id !== null)
								{
									$workflow = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowScheme', trim($activerow[$workflow_id], '" '));
									$project->setWorkflowScheme($workflow);
								}
								
								if ($client !== null)
								{
									$client_object = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowScheme', trim($activerow[$client], '" '));
									$project->setClient($client_object);
								}
								
								if ($show_summary !== null)
								{
									if (trim($activerow[$show_summary], '"') == '1')
										$project->setFrontpageSummaryVisibility(true);
								}
								
								if ($summary_type !== null)
									$project->setFrontpageSummaryType(trim($activerow[$summary_type], '" '));

								if ($issuetype_scheme !== null)
									$project->setIssuetypeScheme(\caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\IssuetypeScheme', trim($activerow[$issuetype_scheme], '"')));
									
								if ($allow_reporting !== null)
									$project->setLocked(trim($activerow[$allow_reporting], '" '));
							
								if ($autoassign !== null)
									$project->setAutoassign(trim($activerow[$autoassign], '" '));
									
								$project->save();
							}
							catch (Exception $e)
							{
								$errors[] = TBGContext::getI18n()->__('Row %row% failed: %err%', array('%row%' => $i+1, '%err%' => $e->getMessage()));
							}
						}
						break;
					case 'issues':
						for ($i = 1; $i != count($data); $i++)
						{
							try
							{
								$activerow = $data[$i];
								$activerow = html_entity_decode($activerow, ENT_QUOTES);
								$activerow = explode(',', $activerow);
								$issue = new TBGIssue();
								$issue->setTitle(trim($activerow[$title], '" '));
								$issue->setProject(trim($activerow[$project], '" '));
								$issue->setIssuetype(trim($activerow[$issue_type], '" '));
								
								if ($issue_type !== null)
									$issue->setIssuetype(trim($activerow[$issue_type], '" '));
								
								if ($descr !== null)
									$issue->setDescription(trim($activerow[$descr], '" '));
									
								if ($repro !== null)
									$issue->setReproduction(trim($activerow[$repro], '" '));
								
								if ($state !== null)
									$issue->setState(trim($activerow[$state], '" '));
								
								if ($status !== null)
									$issue->setStatus(trim($activerow[$status], '" '));
									
								if ($posted_by !== null)
									$issue->setPostedBy(\caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\core\\User', trim($activerow[$posted_by], '"')));
								
								if ($owner !== null && $owner_type !== null)
								{
									switch (trim($activerow[$owner_type], '"'))
									{
										case TBGIdentifiableClass::TYPE_USER:
											$user = new TBGUser(trim($activerow[$owner], '" '));
											$issue->setOwner($user);
											break;
										case TBGIdentifiableClass::TYPE_TEAM:
											$team = new TBGTeam(trim($activerow[$owner], '" '));
											$issue->setOwner($team);
											break;
									}
								}
								
								if ($assigned !== null && $assigned_type !== null)
								{
									switch (trim($activerow[$assigned_type], '"'))
									{
										case TBGIdentifiableClass::TYPE_USER:
											$user = new TBGUser(trim($activerow[$assigned], '" '));
											$issue->setAssignee($user);
											break;
										case TBGIdentifiableClass::TYPE_TEAM:
											$team = new TBGTeam(trim($activerow[$assigned], '" '));
											$issue->setAssignee($team);
											break;
									}
								}
								
								if ($resolution !== null)
									$issue->setResolution(trim($activerow[$resolution], '" '));
									
								if ($priority !== null)
									$issue->setPriority(trim($activerow[$priority], '" '));
								
								if ($category !== null)
									$issue->setCategory(trim($activerow[$category], '" '));
								
								if ($blocking !== null)
									$issue->setBlocking(trim($activerow[$blocking], '" '));
									
								if ($severity !== null)
									$issue->setSeverity(trim($activerow[$severity], '" '));
									
								if ($reproducability !== null)
									$issue->setReproducability(trim($activerow[$reproducability], '" '));
									
								if ($votes !== null)
									$issue->setVotes(trim($activerow[$votes], '" '));
								
								if ($percentage !== null)
									$issue->setPercentage(trim($activerow[$percentage], '" '));
								
								if ($milestone !== null)
									$issue->setMilestone(trim($activerow[$milestone], '" '));
								
								$issue->save();
							}
							catch (Exception $e)
							{
								$errors[] = TBGContext::getI18n()->__('Row %row% failed: %err%', array('%row%' => $i+1, '%err%' => $e->getMessage()));
							}
						}
						break;
				}
				
				// Handle errors
				if (count($errors) != 0)
				{
					$errordiv = '<ul>';
					foreach ($errors as $error)
					{
						$errordiv .= '<li>'.$error.'</li>';
					}
					$errordiv .= '</ul>';
					$this->getResponse()->setHttpStatus(400);
					return $this->renderJSON(array('failed' => true, 'errordetail' => $errordiv, 'error' => TBGContext::getI18n()->__('Errors occured while importing, see the error list in the import screen for further details')));
				}
				else
				{
					return $this->renderJSON(array('failed' => false, 'message' => TBGContext::getI18n()->__('Successfully imported %num% rows!', array('%num%' => count($data)-1))));
				}
			}
		}
		
		public function runGetUpdatedProjectKey(Request $request)
		{
			try
			{
				$this->project = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
			}
			catch (Exception $e) {}
			
			if (!$this->project instanceof \thebuggenie\entities\Project) return $this->return404(TBGContext::getI18n()->__("This project doesn't exist"));
			$this->project->setName($request->getParameter('project_name'));
			
			return $this->renderJSON(array('content' => $this->project->getKey()));
		}

		public function runScopes(Request $request)
		{
			if ($request->isMethod(TBGRequest::POST))
			{
				$hostname = $request->getParameter('hostname');
				$scopename = $request->getParameter('name');
				if (!$hostname || TBGScopesTable::getTable()->getByHostname($hostname) instanceof \thebuggenie\core\Scope)
				{
					$this->scope_hostname_error = true;
				}
				elseif (!$scopename)
				{
					$this->scope_name_error = true;
				}
				else
				{
					$scope = new TBGScope();
					$scope->addHostname($hostname);
					$scope->setName($scopename);
					$scope->setEnabled();
					$scope->save();
					$this->forward(TBGContext::getRouting()->generate('configure_scopes'));
				}
			}
			$this->scope_deleted = TBGContext::getMessageAndClear('scope_deleted');
			$this->scopes = TBGScope::getAll();
		}

		public function runScope(Request $request)
		{
			$this->scope = new TBGScope($request->getParameter('id'));
			$modules = TBGModulesTable::getTable()->getModulesForScope($this->scope->getID());
			$this->modules = $modules;
			$this->scope_save_error = TBGContext::getMessageAndClear('scope_save_error');
			$this->scope_saved = TBGContext::getMessageAndClear('scope_saved');

			if ($request->isMethod(TBGRequest::POST))
			{
				try
				{
					if ($request->getParameter('scope_action') == 'delete')
					{
						if (!$this->scope->isDefault())
						{
							$this->scope->delete();
							TBGContext::setMessage('scope_deleted', true);
							$this->forward(make_url('configure_scopes'));
						}
						else
						{
							$this->scope_save_error = TBGContext::getI18n()->__('You cannot delete the default scope');
						}
					}
					else
					{
						if (!$request->getParameter('name'))
						{
							throw new Exception(TBGContext::getI18n()->__('Please specify a scope name'));
						}
						$this->scope->setName($request->getParameter('name'));
						$this->scope->setDescription($request->getParameter('description'));
						$this->scope->setCustomWorkflowsEnabled((bool) $request->getParameter('custom_workflows_enabled'));
						$this->scope->setMaxWorkflowsLimit((int) $request->getParameter('workflow_limit'));
						$this->scope->setUploadsEnabled((bool) $request->getParameter('file_uploads_enabled'));
						$this->scope->setMaxUploadLimit((int) $request->getParameter('upload_limit'));
						$this->scope->setMaxProjects((int) $request->getParameter('project_limit'));
						$this->scope->setMaxUsers((int) $request->getParameter('user_limit'));
						$this->scope->setMaxTeams((int) $request->getParameter('team_limit'));
						$this->scope->save();

						$enabled_modules = $request->getParameter('module_enabled');
						$prev_scope = \thebuggenie\core\Context::getScope();
						foreach ($enabled_modules as $module => $enabled)
						{
							if (!TBGContext::getModule($module)->isCore() && !$enabled && array_key_exists($module, $modules))
							{
								$module = TBGModulesTable::getTable()->getModuleForScope($module, $this->scope->getID());
								$module->uninstall($this->scope->getID());
							}
							elseif (!TBGContext::getModule($module)->isCore() && $enabled && !array_key_exists($module, $modules))
							{
								TBGContext::setScope($this->scope);
								TBGModule::installModule($module);
								TBGContext::setScope($prev_scope);
							}
						}
						TBGContext::setMessage('scope_saved', true);
						$this->forward(make_url('configure_scope', array('id' => $this->scope->getID())));
					}
				}
				catch (Exception $e)
				{
					TBGContext::setMessage('scope_save_error', $e->getMessage());
				}
			}
		}

		public function runUnassignFromProject(Request $request)
		{
			try
			{
				$project = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
				$project->removeAssignee($request->getParameter('assignee_type'), $request->getParameter('assignee_id'));
				return $this->renderJSON(array('failed' => false, 'message' => TBGContext::getI18n()->__('The assignee has been removed')));
			}
			catch (Exception $e)
			{
				return $this->renderJSON(array('failed' => true, 'message' => $e->getMessage()));
			}
		}
		
		public function runProjectIcons(Request $request)
		{
			$project = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
			if ($request->isMethod(TBGRequest::POST))
			{
				if ($request->getParameter('clear_icons'))
				{
					$project->clearSmallIcon();
					$project->clearLargeIcon();
				}
				else
				{
					switch ($request->getParameter('small_icon_action'))
					{
						case 'upload_file':
							$file = $request->handleUpload('small_icon');
							$project->setSmallIcon($file);
							break;
						case 'clear_file':
							$project->clearSmallIcon();
							break;
					}
					switch ($request->getParameter('large_icon_action'))
					{
						case 'upload_file':
							$file = $request->handleUpload('large_icon');
							$project->setLargeIcon($file);
							break;
						case 'clear_file':
							$project->clearLargeIcon();
							break;
					}
				}
				$project->save();
			}
			$route = TBGContext::getRouting()->generate('project_settings', array('project_key' => $project->getKey()));
			if ($request->isAjaxCall())
			{
				return $this->renderJSON(array('forward' => $route));
			}
			else
			{
				$this->forward($route);
			}
		}

		public function runProjectWorkflow(Request $request)
		{
			$project = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
			
			try
			{
				foreach ($project->getIssuetypeScheme()->getIssuetypes() as $type)
				{
					$data = array();
					foreach ($project->getWorkflowScheme()->getWorkflowForIssuetype($type)->getSteps() as $step)
					{
						$data[] = array((string)$step->getID(), $request->getParameter('new_step_'.$type->getID().'_'.$step->getID()));
					}
					$project->convertIssueStepPerIssuetype($type, $data);
				}
				
				$project->setWorkflowScheme(\caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowScheme', $request->getParameter('workflow_id')));
				$project->save();
				
				return $this->renderJSON(array('message' => TBGContext::geti18n()->__('Workflow scheme changed and issues updated')));
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHTTPStatus(500);
				return $this->renderJSON(array('error' => TBGContext::geti18n()->__('An internal error occured')));
			}
		}

		public function runProjectWorkflowTable(Request $request)
		{
			$project = \caspar\core\Caspar::factory()->manufacture('\\thebuggenie\\entities\\Project', $request->getParameter('project_id'));
			if ($request->isMethod(TBGRequest::POST))
			{
				try
				{
					$workflow_scheme = \caspar\core\Caspar::factory()->manufacture('\thebuggenie\entities\WorkflowScheme', $request->getParameter('new_workflow'));
					return $this->renderJSON(array('content' => $this->getTemplateHtml('projectworkflow_table', array('project' => $project, 'new_workflow' => $workflow_scheme))));
				}
				catch (Exception $e)
				{
					$this->getResponse()->setHTTPStatus(500);
					return $this->renderJSON(array('error' => TBGContext::geti18n()->__('This workflow scheme is not valid')));
				}
			}
		}
	}

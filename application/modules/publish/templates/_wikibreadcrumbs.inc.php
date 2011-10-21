<?php

	$article_name = (isset($article_name)) ? $article_name : '';
	if (!\thebuggenie\core\Context::isProjectContext() || (\thebuggenie\core\Context::isProjectContext() && mb_strtolower($article_name) != mb_strtolower(\thebuggenie\core\Context::getCurrentProject()->getKey() . ':mainpage')))
	{
		if (\thebuggenie\core\Context::isProjectContext())
		{
			$csp_response->addBreadcrumb(TBGPublish::getModule()->getMenuTitle(), make_url('publish_article', array('article_name' => \thebuggenie\core\Context::getCurrentProject()->getKey() . ':MainPage')), tbg_get_breadcrumblinks('project_summary', \thebuggenie\core\Context::getCurrentProject()));
		}
		else
		{
			$csp_response->addBreadcrumb(TBGPublish::getModule()->getMenuTitle(), make_url('publish_article', array('article_name' => 'MainPage')), tbg_get_breadcrumblinks('main_links'));
		}
		$items = explode(':', $article_name);
		$bcpath = array_shift($items);
		if (mb_strtolower($bcpath) == 'category')
		{
			$csp_response->addBreadcrumb(__('Categories'));
			if (\thebuggenie\core\Context::isProjectContext())
			{
				$bcpath .= ":".array_shift($items);
			}
		}
		elseif (!\thebuggenie\core\Context::isProjectContext() && mb_strtolower($bcpath) != 'mainpage')
		{
			$csp_response->addBreadcrumb($bcpath, make_url('publish_article', array('article_name' => $bcpath)));
		}
		foreach ($items as $bc_name)
		{
			$bcpath .= ":".$bc_name;
			$csp_response->addBreadcrumb($bc_name, make_url('publish_article', array('article_name' => $bcpath)));
		}
	}
	else
	{
		$csp_response->addBreadcrumb(TBGPublish::getModule()->getMenuTitle(), make_url('publish_article', array('article_name' => \thebuggenie\core\Context::getCurrentProject()->getKey() . ':MainPage')), tbg_get_breadcrumblinks('project_summary', \thebuggenie\core\Context::getCurrentProject()));
	}

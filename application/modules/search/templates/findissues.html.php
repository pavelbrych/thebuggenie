<?php

	if ($show_results)
	{
		$csp_response->setTitle($searchtitle);
	}
	else
	{
		$csp_response->setTitle((\thebuggenie\core\Context::isProjectContext()) ? __('Find issues for %project_name%', array('%project_name%' => \thebuggenie\core\Context::getCurrentProject()->getName())) : __('Find issues'));
	}
	if (\thebuggenie\core\Context::isProjectContext())
	{
		$csp_response->addBreadcrumb(__('Issues'), make_url('project_issues', array('project_key' => \thebuggenie\core\Context::getCurrentProject()->getKey())), tbg_get_breadcrumblinks('project_summary', \thebuggenie\core\Context::getCurrentProject()));
		$csp_response->addFeed(make_url('project_open_issues', array('project_key' => \thebuggenie\core\Context::getCurrentProject()->getKey(), 'format' => 'rss')), __('Open issues for %project_name%', array('%project_name%' => \thebuggenie\core\Context::getCurrentProject()->getName())));
		$csp_response->addFeed(make_url('project_closed_issues', array('project_key' => \thebuggenie\core\Context::getCurrentProject()->getKey(), 'format' => 'rss')), __('Closed issues for %project_name%', array('%project_name%' => \thebuggenie\core\Context::getCurrentProject()->getName())));
		$csp_response->addFeed(make_url('project_milestone_todo_list', array('project_key' => \thebuggenie\core\Context::getCurrentProject()->getKey(), 'format' => 'rss')), __('Milestone todo-list for %project_name%', array('%project_name%' => \thebuggenie\core\Context::getCurrentProject()->getName())));
		if (!TBGUser::isThisGuest())
		{
			$csp_response->addFeed(make_url('project_my_reported_issues', array('project_key' => \thebuggenie\core\Context::getCurrentProject()->getKey(), 'format' => 'rss')), __('Issues reported by me') . ' ('.\thebuggenie\core\Context::getCurrentProject()->getName().')');
			$csp_response->addFeed(make_url('project_my_assigned_issues', array('project_key' => \thebuggenie\core\Context::getCurrentProject()->getKey(), 'format' => 'rss')), __('Open issues assigned to me') . ' ('.\thebuggenie\core\Context::getCurrentProject()->getName().')');
			$csp_response->addFeed(make_url('project_my_teams_assigned_issues', array('project_key' => \thebuggenie\core\Context::getCurrentProject()->getKey(), 'format' => 'rss')), __('Open issues assigned to my teams') . ' ('.\thebuggenie\core\Context::getCurrentProject()->getName().')');
		}
	}
	else
	{
		$csp_response->addBreadcrumb(__('Issues'), make_url('search'), tbg_get_breadcrumblinks('main_links'));
		if (!TBGUser::isThisGuest())
		{
			$csp_response->addFeed(make_url('my_reported_issues', array('format' => 'rss')), __('Issues reported by me'));
			$csp_response->addFeed(make_url('my_assigned_issues', array('format' => 'rss')), __('Open issues assigned to you'));
			$csp_response->addFeed(make_url('my_teams_assigned_issues', array('format' => 'rss')), __('Open issues assigned to your teams'));
		}
	}

?>
<table style="width: 100%;" cellpadding="0" cellspacing="0">
	<tr>
		<?php include_component('search/sidebar'); ?>
		<td style="width: auto; padding: 5px; vertical-align: top;" id="find_issues">
			<?php if ($search_error !== null): ?>
				<div class="rounded_box red borderless" style="margin: 0; vertical-align: middle;" id="search_error">
					<div class="header"><?php echo $search_error; ?></div>
				</div>
			<?php endif; ?>
			<?php if ($search_message !== null): ?>
				<div class="rounded_box green borderless" style="margin: 0; vertical-align: middle;" id="search_message">
					<div class="header"><?php echo $search_message; ?></div>
				</div>
			<?php endif; ?>
			<?php include_component('search/searchbuilder', compact('appliedfilters', 'ipp', 'groupby', 'grouporder', 'issavedsearch', 'savedsearch', 'searchterm', 'show_results', 'templatename', 'template_parameter')); ?>
			<?php if ($show_results): ?>
				<div class="results_header">
					<?php echo $searchtitle; ?>
					&nbsp;&nbsp;<span class="faded_out"><?php echo __('%number_of% issue(s)', array('%number_of%' => (int) $resultcount)); ?></span>
					<?php include_component('search/extralinks', compact('show_results')); ?>
				</div>
				<?php if (count($issues) > 0): ?>
					<div id="search_results" class="search_results">
						<?php include_template('search/issues_paginated', array('issues' => $issues, 'templatename' => $templatename, 'template_parameter' => $template_parameter, 'searchterm' => $searchterm, 'filters' => $appliedfilters, 'groupby' => $groupby, 'grouporder' => $grouporder, 'resultcount' => $resultcount, 'ipp' => $ipp, 'offset' => $offset)); ?>
					</div>
				<?php else: ?>
					<div class="faded_out" id="no_issues"><?php echo __('No issues were found'); ?></div>
				<?php endif; ?>
			<?php endif; ?>
		</td>
	</tr>
</table>

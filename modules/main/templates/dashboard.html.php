<?php 

	$tbg_response->setTitle(__('Dashboard'));
	$tbg_response->addBreadcrumb(__('Personal dashboard'), make_url('dashboard'), tbg_get_breadcrumblinks('main_links'));
	$tbg_response->addFeed(make_url('my_reported_issues', array('format' => 'rss')), __('Issues reported by me'));
	$tbg_response->addFeed(make_url('my_assigned_issues', array('format' => 'rss')), __('Open issues assigned to you'));
	$tbg_response->addFeed(make_url('my_teams_assigned_issues', array('format' => 'rss')), __('Open issues assigned to your teams'));

?>
<?php include_component('main/hideableInfoBox', array('key' => 'dashboard_didyouknow', 'title' => __('This is your personal dashboard'), 'content' => __('This is your personal dashboard page - your starting point when logging in to The Bug Genie. This dashboard page will show projects and people you are associated with, as well as interesting views.') . '<br>' . __('Your dashboard can be configured and personalized. To configure what views to show on this dashboard, click the "Customize dashboard"-icon to the far right, below this box.') . '<br><br><i>' . __('Your dashboard page is accessible from anywhere - click your username in the top right header area at any time to access your dashboard.') . '</i>')); ?>
<?php /*<td id="dashboard_lefthand" class="side_bar">
			<?php TBGEvent::createNew('core', 'dashboard_left_top')->trigger(); ?>
			<div class="container_div" style="margin: 0 0 5px 5px;">
				<?php include_component('main/myfriends'); ?>
			</div>
			<?php TBGEvent::createNew('core', 'dashboard_left_bottom')->trigger();?>
</td> */ ?>
<?php TBGEvent::createNew('core', 'dashboard_main_top')->trigger(); ?>
<?php if (!count($views)): ?>
	<p class="content faded_out"><?php echo __("This dashboard doesn't contain any views. To add views in this dashboard, press the 'Customize dashboard'-icon to the far right."); ?></p>
<?php else: ?>
	<ul id="dashboard" class="column-4s" style="margin: 10px 5px;">
		<?php $clearleft = true; ?>
		<?php foreach($views as $_id => $view): ?>
		<li style="clear: none;" id="dashboard_container_<?php echo $_id; ?>">
			<?php include_component('dashboardview', array('view' => $view, 'show' => false)); ?>
			<?php // include_component('dashboardview', array('type' => $view->get(TBGDashboardViewsTable::TYPE), 'id' => $view->get(TBGDashboardViewsTable::ID), 'view' => $view->get(TBGDashboardViewsTable::VIEW), 'rss' => true)); ?>
		</li>
		<?php $clearleft = !$clearleft; ?>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
<?php TBGEvent::createNew('core', 'dashboard_main_bottom')->trigger(); ?>
<script type="text/javascript">
	document.observe('dom:loaded', function() {
		['<?php echo join(', ', array_keys($views)); ?>'].each(function(view_id) {
			TBG.Main.Dashboard.View.init('<?php echo make_url('dashboard_view'); ?>', view_id);
		});
	});
</script>
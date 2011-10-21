<li<?php if ($selected_tab == 'wiki'): ?> class="selected"<?php endif; ?>>
	<div>
		<?php echo link_tag(((isset($project_url)) ? $project_url : $url), image_tag('tab_publish.png', array(), false, 'publish') . \thebuggenie\core\Context::getModule('publish')->getMenuTitle()); ?>
		<?php if (count(\thebuggenie\entities\Project::getAll())): ?>
			<?php echo javascript_link_tag(image_tag('tabmenu_dropdown.png', array('class' => 'menu_dropdown'))); ?>
		<?php endif; ?>
	</div>
	<?php if (count(\thebuggenie\entities\Project::getAll())): ?>
		<div id="wiki_dropdown_menu" class="tab_menu_dropdown">
			<?php if (\thebuggenie\core\Context::isProjectContext()): ?>
				<div class="header"><?php echo __('Currently selected project'); ?></div>
				<?php echo link_tag($project_url, __('Project wiki frontpage')); ?>
				<?php $quicksearch_title = __('Find project article (press enter to search)'); ?>
				<div style="font-weight: normal; margin: 0 0 15px 5px;">
					<form action="<?php echo make_url('publish_find_project_articles', array('project_key' => \thebuggenie\core\Context::getCurrentProject()->getKey())); ?>" method="get" accept-charset="<?php echo \caspar\core\Caspar::getI18n()->getCharset(); ?>">
						<input type="text" name="articlename" value="<?php echo $quicksearch_title; ?>" style="width: 230px; font-size: 0.9em;" onblur="if ($(this).getValue() == '') { $(this).value = '<?php echo $quicksearch_title; ?>'; $(this).addClassName('faded_out'); }" onfocus="if ($(this).getValue() == '<?php echo $quicksearch_title; ?>') { $(this).clear(); } $(this).removeClassName('faded_out');" class="faded_out">
					</form>
				</div>
			<?php endif; ?>
			<div class="header"><?php echo __('Global content'); ?></div>
			<?php echo link_tag($url, \thebuggenie\core\Context::getModule('publish')->getMenuTitle(false)); ?>
			<?php $quicksearch_title = __('Find any article (press enter to search)'); ?>
			<div style="font-weight: normal; margin: 0 0 15px 5px;">
				<form action="<?php echo make_url('publish_find_articles'); ?>" method="get" accept-charset="<?php echo \caspar\core\Caspar::getI18n()->getCharset(); ?>">
					<input type="text" name="articlename" value="<?php echo $quicksearch_title; ?>" style="width: 230px; font-size: 0.9em;" onblur="if ($(this).getValue() == '') { $(this).value = '<?php echo $quicksearch_title; ?>'; $(this).addClassName('faded_out'); }" onfocus="if ($(this).getValue() == '<?php echo $quicksearch_title; ?>') { $(this).clear(); } $(this).removeClassName('faded_out');" class="faded_out">
				</form>
			</div>
			<?php if (count(\thebuggenie\entities\Project::getAll()) > (int) \thebuggenie\core\Context::isProjectContext()): ?>
				<div class="header"><?php echo __('Project wikis'); ?></div>
				<?php foreach (\thebuggenie\entities\Project::getAll() as $project): ?>
					<?php if (isset($project_url) && $project->getID() == \thebuggenie\core\Context::getCurrentProject()->getID()) continue; ?>
					<?php echo link_tag(make_url('publish_article', array('article_name' => ucfirst($project->getKey()).':MainPage')), $project->getName()); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</li>
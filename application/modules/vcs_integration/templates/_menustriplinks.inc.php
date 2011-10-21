<?php
	/*
	 * Generate link for browser
	 */
	 
	$link_repo = \thebuggenie\core\Context::getModule('vcs_integration')->getSetting('browser_url_' . \thebuggenie\core\Context::getCurrentProject()->getID());
	
	if (\thebuggenie\core\Context::getModule('vcs_integration')->getSetting('vcs_mode_' . \thebuggenie\core\Context::getCurrentProject()->getID()) != TBGVCSIntegration::MODE_DISABLED)
	{
			echo link_tag(make_url('vcs_commitspage', array('project_key' => \thebuggenie\core\Context::getCurrentProject()->getKey())), __('Commits'), (($csp_response->getPage() == 'vcs_commitspage') ? array('class' => 'selected') : array()));
			if (!($submenu) && $csp_response->getPage() == 'vcs_commitspage'): ?>
			<ul class="simple_list">
				<li><a href="<?php echo $link_repo; ?>" target="_blank"><?php echo __('Browse source code'); ?></a></li>
			</ul>
		<?php endif;
	}

?>
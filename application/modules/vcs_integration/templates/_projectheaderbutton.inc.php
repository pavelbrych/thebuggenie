<?php
	/*
	 * Generate link for browser
	 */
	 
	$link_repo = \thebuggenie\core\Context::getModule('vcs_integration')->getSetting('browser_url_' . \thebuggenie\core\Context::getCurrentProject()->getID());
	
	if (\thebuggenie\core\Context::getModule('vcs_integration')->getSetting('vcs_mode_' . \thebuggenie\core\Context::getCurrentProject()->getID()) != TBGVCSIntegration::MODE_DISABLED)
	{
		echo '<div class="button button-blue"><a href="'.$link_repo.'" target="_blank">'.image_tag('cfg_icon_vcs_integration.png', null, false, 'vcs_integration').__('Source code').'</a></div>';
	}

?>
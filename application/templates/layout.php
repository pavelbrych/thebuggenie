<!DOCTYPE html>
<html lang="<?php echo \caspar\core\Caspar::getI18n()->getHTMLLanguage(); ?>">
	<head>
		<meta charset="<?php echo \caspar\core\Caspar::getI18n()->getCharset(); ?>">
		<?php \caspar\core\Event::createNew('core', 'header_begins')->trigger(); ?>
		<meta name="description" content="The bug genie, friendly issue tracking">
		<meta name="keywords" content="thebuggenie friendly issue tracking">
		<meta name="author" content="thebuggenie.com">
		<title><?php echo ($tbg_response->hasTitle()) ? strip_tags($tbg_response->getBaseTitle() . ' ~ ' . $tbg_response->getTitle()) : strip_tags($tbg_response->getBaseTitle()); ?></title>
		<link rel="shortcut icon" href="<?php print $tbg_response->getFaviconURL(); ?>">
		<link title="<?php echo (\thebuggenie\core\Context::isProjectContext()) ? __('%project_name% search', array('%project_name%' => \thebuggenie\core\Context::getCurrentProject()->getName())) : __('%site_name% search', array('%site_name%' => $tbg_response->getBaseTitle())); ?>" href="<?php echo (\thebuggenie\core\Context::isProjectContext()) ? make_url('project_opensearch', array('project_key' => \thebuggenie\core\Context::getCurrentProject()->getKey())) : make_url('opensearch'); ?>" type="application/opensearchdescription+xml" rel="search">
		<?php foreach ($tbg_response->getFeeds() as $feed_url => $feed_title): ?>
			<link rel="alternate" type="application/rss+xml" title="<?php echo str_replace('"', '\'', $feed_title); ?>" href="<?php echo $feed_url; ?>">
		<?php endforeach; ?>
		<?php include THEBUGGENIE_PATH . THEBUGGENIE_PUBLIC_FOLDER_NAME . DS . 'themes' . DS . TBGSettings::getThemeName() . DS . 'theme.php'; ?>
		<?php if (count(\caspar\core\Caspar::getModules())): ?>
			<?php foreach (\caspar\core\Caspar::getModules() as $module): ?>
				<?php if (file_exists(THEBUGGENIE_PATH . THEBUGGENIE_PUBLIC_FOLDER_NAME . DS . 'themes' . DS . TBGSettings::getThemeName() . DS . "{$module->getName()}.css")): ?>
					<?php $tbg_response->addStylesheet("{$module->getName()}.css"); ?>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php list ($cssstring, $sepcss) = tbg_get_stylesheets(); ?>
		<link rel="stylesheet" href="<?php print make_url('serve'); ?>&g=css&files=<?php print base64_encode($cssstring); ?>">
		<?php foreach ($sepcss as $css): ?>
			<link rel="stylesheet" href="<?php echo $css; ?>">
		<?php endforeach; ?>

		<?php list ($jsstring, $sepjs) = tbg_get_javascripts(); ?>
		<script type="text/javascript" src="<?php print make_url('serve'); ?>&g=js&files=<?php print base64_encode($jsstring); ?>"></script>
		<?php foreach ($sepjs as $js): ?>
			<script type="text/javascript" src="<?php echo $js; ?>"></script>
		<?php endforeach; ?>
		  <!--[if lt IE 9]>
			  <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
		  <![endif]-->
		<?php \caspar\core\Event::createNew('core', 'header_ends')->trigger(); ?>
	</head>
	<body>
		<?php require THEBUGGENIE_CORE_PATH . 'templates/backdrops.inc.php'; ?>
		<table style="width: 100%; height: 100%; table-layout: fixed; min-width: 1020px;" cellpadding=0 cellspacing=0>
			<tr>
				<td style="height: auto; overflow: hidden;" valign="top" id="maintd">
					<?php require THEBUGGENIE_CORE_PATH . 'templates/headertop.inc.php'; ?>
					<?php if (!TBGSettings::isMaintenanceModeEnabled()) require THEBUGGENIE_CORE_PATH . 'templates/submenu.inc.php'; ?>
					<?php echo $content; ?>
					<?php \caspar\core\Event::createNew('core', 'footer_begin')->trigger(); ?>
				</td>
			</tr>
			<tr>
				<td class="footer_bar">
					<?php require THEBUGGENIE_CORE_PATH . 'templates/footer.inc.php'; ?>
					<?php \caspar\core\Event::createNew('core', 'footer_end')->trigger(); ?>
				</td>
			</tr>
		</table>
		<script type="text/javascript">
			document.observe('dom:loaded', TBG.initialize({ autocompleter_url: '<?php echo (\caspar\core\Caspar::isProjectContext()) ? make_url('project_quicksearch', array('project_key' => \caspar\core\Caspar::getCurrentProject()->getKey())) : make_url('quicksearch'); ?>'}));
		</script>
	</body>
</html>
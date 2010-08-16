<?php include_template('installation/header'); ?>
<?php if (isset($error)): ?>
	<div class="installation_box">
		<div class="error"><?php echo nl2br($error); ?></div>
		<h2>An error occured</h2>
		An error occured and the installation has been stopped. Please try to fix the error based on the information above, then click back, and try again.<br>
		If you think this is a bug, please report it in our <a href="http://b2.thebuggenie.com" target="_new">online bug tracker</a>.
	</div>
<?php else: ?>
	<div class="donate installation_box">
		<h3>Supporting further development</h3>
		Please remember: The Bug Genie is <a href="http://www.opensource.org/docs/definition.php" target="_new"><b>free / open source software</b></a>, provided to you free of charge and developed by a small group of people. If this software proves valuable to you, please consider giving back. More information can be found here:<br>
		<div style="margin-top: 10px; margin-bottom: 10px; font-size: 13px;"><a target="_new" href="http://www.thebuggenie.com/giving_back.php">http://www.thebuggenie.com/giving_back.php</a> <span style="font-size: 10px;">(opens in a new window)</span></div>
	</div>
	<div class="installation_box">
		<h2>Thank you for installing The Bug Genie!</h2>
		The Bug Genie builds upon the open source <b>B2 framework</b>, and a PHP database layer called <b>B2DB</b>. If you find any bugs or issues, please use our <a href="http://b2.thebuggenie.com" target="_new">issue tracker</a> or send an email to <a href="mailto:support@thebuggenie.com">support@thebuggenie.com</a>.<br>
		<br>
		<h3>Help and support</h3>
		Online documentation is available from <a href="http://www.thebuggenie.com/support.php" target="_new">www.thebuggenie.com</a>. If you need more help, you can use the <a href="http://www.thebuggenie.com/forum" target="_new">forums</a> where there are a lot of helpful people around.<br>
		<b>Commercial email support is available for anyone with a <a target="_new" href="http://www.thebuggenie.com/giving_back.php">support contract</a>.</b> For other inquiries, send an email to <a href="mailto:support@thebuggenie.com">support@thebuggenie.com</a>.
	</div>
	<div style="font-size: 15px; text-align: center; padding: 25px;">When you're done, <?php echo link_tag(make_url('about'), 'click here to finish'); ?></div>
<?php endif; ?>
<?php include_template('installation/footer'); ?>
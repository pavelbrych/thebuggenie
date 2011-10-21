<?php

	use caspar\core\Caspar;
	/**
	 * Configuration for theme
	 */

	Caspar::getResponse()->addStylesheet('oxygen.css');
	Caspar::getResponse()->addStylesheet(Caspar::getStrippedTBGPath().'/themes/oxygen/markitup.css', false);
	Caspar::getResponse()->addStylesheet(Caspar::getStrippedTBGPath().'/themes/oxygen/tablekit.css', false);
	
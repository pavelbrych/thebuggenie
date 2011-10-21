<?php

	/**
	 * UI functions
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 2.0
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 */
	
	/**
	 * Returns an <img> tag with a specified image
	 * 
	 * @param string $image image source
	 * @param array $params[optional] html parameters
	 * @param boolean $notheme[optional] whether this is a themed image or a top level path
	 * @param string $module whether this is a module image or in the core image set
	 * @param boolean $relative whether the path is relative or absolute
	 * 
	 * @return string
	 */
	function image_tag($image, $params = array(), $notheme = false, $module = 'core', $relative = true)
	{
		if ($notheme)
		{
			$params['src'] = $image;
		}
		else
		{
			if ($module != 'core' && !file_exists(THEBUGGENIE_PATH . 'iconsets/' . \thebuggenie\core\Settings::getIconsetName() . "/{$module}/" . $image))
			{
				$params['src'] = \caspar\core\Caspar::getTBGPath() . "iconsets/" . \thebuggenie\core\Settings::getIconsetName() . "/modules/{$module}/" . $image;
			}
			elseif ($module != 'core')
			{
				$params['src'] = \caspar\core\Caspar::getTBGPath() . 'iconsets/' . \thebuggenie\core\Settings::getIconsetName() . "/{$module}/" . $image;
			}
			else
			{
				$params['src'] = \caspar\core\Caspar::getTBGPath() . 'iconsets/' . \thebuggenie\core\Settings::getIconsetName() . '/' . $image;
			}
		}
		if (!$relative)
		{
			if ($notheme)
			{
				$params['src'] = \caspar\core\Caspar::getTBGPath() . $params['src'];
			}
			$params['src'] = \caspar\core\Caspar::getUrlHost() . $params['src'];
		}
		if (!isset($params['alt']))
		{
			$params['alt'] = $image;
		}
		return "<img " . parseHTMLoptions($params) . '>';
	}
	
	/**
	 * Returns the URL to a specified image
	 * 
	 * @param string $image image source
	 * @param bool $notheme[optional] whether this is a themed image or a top level path
	 * 
	 * @return string
	 */
	function image_url($image, $notheme = false, $module = 'core', $relative = true)
	{
		if ($notheme)
		{
			$params['src'] = $image;
		}
		else
		{
			if ($module != 'core' && !file_exists(THEBUGGENIE_PATH . 'themes/' . \thebuggenie\core\Settings::getThemeName() . "/{$module}/" . $image))
			{
				$params['src'] = \caspar\core\Caspar::getTBGPath() . "themes/modules/{$module}/" . \thebuggenie\core\Settings::getThemeName() . '/' . $image;
			}
			elseif ($module != 'core')
			{
				$params['src'] = \caspar\core\Caspar::getTBGPath() . 'themes/' . \thebuggenie\core\Settings::getThemeName() . "/{$module}/" . $image;
			}
			else
			{
				$params['src'] = \caspar\core\Caspar::getTBGPath() . 'themes/' . \thebuggenie\core\Settings::getThemeName() . '/' . $image;
			}
		}
		if (!$relative)
		{
			$params['src'] = \caspar\core\Caspar::getUrlHost() . $params['src'];
		}
		return $params['src'];
	}
	
	/**
	 * Returns an <a> tag linking to a specified url
	 * 
	 * @param string $url link target
	 * @param string $link_text the text displayed in the tag
	 * @param array $params[optional] html parameters
	 * 
	 * @return string
	 */
	function link_tag($url, $link_text = null, $params = array())
	{
		$params['href'] = $url;
		if ($link_text === null) $link_text = $url;
		return "<a " . parseHTMLoptions($params) . ">{$link_text}</a>";
	}

	/**
	 * Returns a csrf_token hidden input tag to use in forms
	 *
	 * @return string
	 */
	function csrf_tag()
	{
		return '<input type="hidden" name="csrf_token" value="' . \caspar\core\Caspar::generateCSRFtoken() . '">';
	}

	/**
	 * Return a javascript link tag
	 *
	 * @see link_tag()
	 * 
	 * @param string $link_text the text displayed in the tag
	 * @param array $params[optional] html parameters
	 *
	 * @return string
	 */
	function javascript_link_tag($link_text, $params = array())
	{
		return link_tag('javascript:void(0);', $link_text, $params);
	}
	
	/**
	 * Returns an <input type="image"> tag
	 * 
	 * @param string $image image source
	 * @param array $params[optional] html parameters
	 * @param bool $notheme[optional] whether this is a themed image or a top level path
	 * 
	 * @return string
	 */
	function image_submit_tag($image, $params = array(), $notheme = false)
	{
		$params['src'] = (!$notheme) ? \caspar\core\Caspar::getTBGPath() . 'themes/' . \thebuggenie\core\Settings::getThemeName() . '/' . $image : $image;
		return '<input type="image" ' . parseHTMLoptions($params) . ' />';
	}
	
	/**
	 * Includes a template with specified parameters
	 *
	 * @param string	$template	name of template to load, or module/template to load
	 * @param array 	$params  	key => value pairs of parameters for the template
	 */
	function include_template($template, $params = array())
	{
		return \caspar\core\ActionComponents::includeTemplate($template, $params);
	}

	/**
	 * Return a rendered template with specified parameters
	 *
	 * @param string	$template	name of template to load, or module/template to load
	 * @param array 	$params  	key => value pairs of parameters for the template
	 */
	function get_template_html($template, $params = array())
	{
		return TBGAction::returnTemplateHTML($template, $params);
	}

	/**
	 * Includes a component with specified parameters
	 *
	 * @param string	$component	name of component to load, or module/component to load
	 * @param array 	$params  	key => value pairs of parameters for the template
	 */
	function include_component($component, $params = array())
	{
		return \caspar\core\ActionComponents::includeComponent($component, $params);
	}

	/**
	 * Return a rendered component with specified parameters
	 *
	 * @param string	$component	name of component to load, or module/component to load
	 * @param array 	$params  	key => value pairs of parameters for the template
	 */
	function get_component_html($component, $params = array())
	{
		return TBGAction::returnComponentHTML($component, $params);
	}

	/**
	 * Generate a url based on a route
	 * 
	 * @param string	$name 	The route key
	 * @param array 	$params	key => value pairs of route parameters
	 * 
	 * @return string
	 */
	function make_url($name, $params = array(), $relative = true)
	{
		return \caspar\core\Caspar::getRouting()->generate($name, $params, $relative);
	}
	
	/**
	 * Returns a string with html options based on an array
	 * 
	 * @param array	$options an array of options
	 * 
	 * @return string
	 */
	function parseHTMLoptions($options)
	{
		$option_strings = array();
		if (!is_array($options))
		{
			throw new Exception('Invalid HTML options. Must be an array with key => value pairs corresponding to html attributes');
		}
		foreach ($options as $key => $val)
		{
			$option_strings[$key] = "{$key}=\"{$val}\"";
		}
		return implode(' ', array_values($option_strings));
	}

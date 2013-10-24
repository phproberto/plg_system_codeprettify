<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.codeprettify
 *
 * @author      Roberto Segura <roberto@phproberto.com>
 * @copyright   (c) 2012 Roberto Segura. All Rights Reserved.
 * @license     GNU/GPL 2, http://www.gnu.org/licenses/gpl-2.0.htm
 */
defined('_JEXEC') or die;

JLoader::import('joomla.plugin.plugin');

/**
 * Code prettify plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  System.codeprettify
 *
 * @since       1.0
 */
class PlgSystemCodeprettify extends JPlugin
{
	// Plugin info constants
	const TYPE = 'system';

	const NAME = 'codeprettify';

	/**
	 * Plugin parameters
	 *
	 * @var  JRegistry
	 */
	private $_params = null;

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		// Load plugin parameters
		$this->_plugin = JPluginHelper::getPlugin(self::TYPE, self::NAME);
		$this->_params = new JRegistry($this->_plugin->params);

		// Load plugin language
		$this->loadLanguage('plg_' . self::TYPE . '_' . self::NAME, JPATH_ADMINISTRATOR);
	}

	/**
	 * This event is triggered immediately before pushing the document buffers into the template placeholders,
	 * retrieving data from the document and pushing it into the into the JResponse buffer.
	 * http://docs.joomla.org/Plugin/Events/System
	 *
	 * @return boolean
	 */
	public function onBeforeRender()
	{
		$app = JFactory::getApplication();

		// Do not load in backend
		if ($app->isAdmin())
		{
			return true;
		}

		$doc  = JFactory::getDocument();
		$lang = JFactory::getLanguage();

		$params = $this->getParams();

		$doc->addScript(JURI::root(true) . '/media/codeprettify/prettify.js');

		if ($stylesheet = $this->getStyleUrl())
		{
			$doc->addStyleSheet($stylesheet);
		}

		$html = "
		window.onload = function() {
			prettyPrint();
		};";

		$doc->addScriptDeclaration($html);
	}

	/**
	 * Get the plugin parameters
	 *
	 * @return  JRegistry  Parameters JRegistry objects
	 */
	protected function getParams()
	{
		if (is_null($this->_params))
		{
			if (isset($this->params))
			{
				$this->_params = $this->params;
			}
			else
			{
				// Load plugin parameters
				$plugin = JPluginHelper::getPlugin(self::TYPE, self::NAME);
				$this->_params = new JRegistry($plugin->params);
			}
		}

		return $this->_params;
	}

	/**
	 * Get the URL of a template stylesheet
	 *
	 * @return  string  The
	 */
	protected function getStyleUrl()
	{
		$url = null;

		$params = $this->getParams();

		$tplFile = $params->get('template', 'prettify.css');
		$template = $this->getCurrentTplName();

		$paths = array(
			JPATH_THEMES . '/' . $template . '/html/plg_' . self::TYPE . '_' . self::NAME . '/styles',
			JPATH_BASE . '/media/' . self::NAME . '/styles',
			JPATH_BASE . '/media/' . self::NAME
		);

		if ($path = JPath::find($paths, $tplFile))
		{
			$url = $this->getPathUrl($path);
		}

		return $url;
	}

	/**
	 * Function to get the path to a layout checking overrides.
	 * It's exactly as it's used in the Joomla! Platform 12.2 to easily replace it when available
	 *
	 * @param   string  $type    Plugin type (system, content, etc.)
	 * @param   string  $name    Name of the plugin
	 * @param   string  $layout  The layout name
	 *
	 * @return string  Path where we have to use to call the layout
	 */
	protected function getLayoutPath($type, $name, $layout = 'default')
	{
		$template = JFactory::getApplication()->getTemplate();
		$defaultLayout = $layout;

		if (strpos($layout, ':') !== false)
		{
			// Get the template and file name from the string
			$temp = explode(':', $layout);
			$template = ($temp[0] == '_') ? $template : $temp[0];
			$layout = $temp[1];
			$defaultLayout = ($temp[1]) ? $temp[1] : 'default';
		}

		// Build the template and base path for the layout
		$tPath = JPATH_THEMES . '/' . $template . '/html/plg_' . $type . '_' . $name . '/' . $layout . '.php';
		$bPath = JPATH_BASE . '/plugins/' . $type . '/' . $name . '/tmpl/' . $defaultLayout . '.php';
		$dPath = JPATH_BASE . '/plugins/' . $type . '/' . $name . '/tmpl/' . 'default.php';

		// If the template has a layout override use it
		if (file_exists($tPath))
		{
			return $tPath;
		}
		elseif (file_exists($bPath))
		{
			return $bPath;
		}
		else
		{
			return $dPath;
		}
	}

	/**
	 * Get the name of the active template name
	 *
	 * @return  string  Name of the current template
	 */
	function getCurrentTplName()
	{
		// Required objects
		$app    = JFactory::getApplication();
		$db     = JFactory::getDbo();
		$jinput = $app->input;

		// Default values
		$menuParams = new JRegistry;
		$client_id  = $app->isSite() ? 0 : 1;
		$itemId     = $jinput->get('Itemid', 0);
		$tplName    = null;

		// Try to load custom template if assigned
		if ($itemId)
		{
			$query = $db->getQuery(true)
				->select("ts.template")
				->from("#__menu as m")
				->join("INNER", "#__template_styles as ts ON ts.id = m.template_style_id")
				->where("m.id=" . (int) $itemId);

			$db->setQuery($query);
			$tplName = $db->loadResult();
		}

		// If no itemId or no custom template assigned load default template
		if (!$itemId || empty($tplName))
		{
			$tplName = $this->getDefaultTplName($client_id);
		}

		return $tplName;
	}

	/**
	 * Get the name of the default template
	 *
	 * @param   integer  $client_id  Id of the client to use 0: frontend | 1: backend
	 *
	 * @return  string               Name of the template
	 */
	function getDefaultTplName($client_id = 0)
	{
		$result = null;
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select("template")
			->from("#__template_styles")
			->where("client_id=" . (int) $client_id)
			->where("home = 1");

		$db->setQuery($query);

		try
		{
			$result = $db->loadResult();
		}
		catch (JDatabaseException $e)
		{
			throw new Exception($e->getMessage(), 500);
		}

		return $result;
	}

	/**
	 * Convert a system path into an URL
	 *
	 * @param   string  $path  Path to convert
	 *
	 * @return  string         URL associated to the path
	 */
	function getPathUrl($path)
	{
		$url = null;

		if (file_exists($path))
		{
			$url = JUri::root() . trim(str_replace(JPATH_SITE, '', $path), '/');
		}

		return $url;
	}
}

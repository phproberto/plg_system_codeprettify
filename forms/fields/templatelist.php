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

JFormHelper::loadFieldClass('list');

JLoader::import('joomla.filesystem.folder');

/**
 * Field to load a list of code prettiy templates available including overrides
 *
 * @package     Joomla.Plugin
 * @subpackage  System.codeprettify
 *
 * @since       1.0.0
 */
class JFormFieldTemplatelist extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.0.0
	 */
	protected $type = 'Templatelist';

	/**
	 * Cached array of the category items.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected static $options = array();

	/**
	 * Method to get the options to populate to populate list
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   1.0.0
	 */
	public function getOptions()
	{
		// Accepted modifiers
		$hash = md5($this->element);

		if (!isset(static::$options[$hash]))
		{
			// Load translations
			$lang = JFactory::getLanguage();
			$lang->load('plg_system_codeprettify', JPATH_ADMINISTRATOR);

			// Initialize variables.
			static::$options[$hash] = parent::getOptions();

			$tplName = $this->getDefaultTplName(0);

			$tplAvailable = array();

			$mediaFolder = JPATH_SITE . '/media/codeprettify/styles';
			$tplOverridesFolder = JPATH_SITE . '/templates/' . $tplName . '/html/plg_system_codeprettify/styles';

			// Custom styles or overrides
			if (is_dir($tplOverridesFolder) && $tplStyles = JFolder::files($tplOverridesFolder, '\.css$'))
			{
				foreach ($tplStyles as $style)
				{
					$name = str_replace(array('.css', '.CSS'), '', $style);

					$tplAvailable[$name] = (object) array(
						'text' => $name . ' (' . JText::_('PLG_SYS_CODEPRETTIFY_TEMPLATE_TYPE_OVERRIDE') . ')',
						'value' => $style,
						'folder' => 'override'
					);
				}
			}

			// Default styles
			if (is_dir($mediaFolder) && $mediaStyles = JFolder::files($mediaFolder, '\.css$'))
			{
				foreach ($mediaStyles as $style)
				{
					$name = str_replace(array('.css', '.CSS'), '', $style);

					if (!isset($tplAvailable[$name]))
					{
						$tplAvailable[$name] = (object) array(
						'text' => $name . ' (' . JText::_('PLG_SYS_CODEPRETTIFY_TEMPLATE_TYPE_DEFAULT') . ')',
							'value' => $style,
							'folder' => 'media'
						);
					}
				}
			}

			// Default style
			if (!isset($tplAvailable['prettify']))
			{
				$name = 'prettify';

				$tplAvailable[$name] = (object) array(
						'text' => $name . ' (' . JText::_('PLG_SYS_CODEPRETTIFY_TEMPLATE_TYPE_DEFAULT') . ')',
					'value' => $name . '.css',
					'folder' => 'default'
				);
			}

			if (!empty($tplAvailable))
			{
				static::$options[$hash] = array_merge(static::$options[$hash], $tplAvailable);
			}
		}

		return static::$options[$hash];
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
}

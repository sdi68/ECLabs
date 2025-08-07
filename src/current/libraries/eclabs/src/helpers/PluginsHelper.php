<?php
/**
 * @package             Econsult Labs Library
 * @version             __DEPLOYMENT_VERSION__
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library\Helpers;

\defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Extension\DummyPlugin;
use Joomla\CMS\Factory;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Plugin\PluginHelper as PluginHelperBase;

class PluginsHelper
{
	/**
	 * Plugins loaded.
	 *
	 * @var  array|null
	 *
	 * @since  1.0.0
	 */
	protected static ?array $_plugins = null;

	/**
	 * Calls the plugin handler associated with the event.
	 *
	 * @param   string|null  $type    The plugin type, relates to the subdirectory in the plugins directory.
	 * @param   string|null  $plugin  The plugin name.
	 * @param   string|null  $event   The event name.
	 * @param   mixed|array  $args    Method arguments (optional).
	 *
	 * @return mixed Plugin event result.
	 *
	 * @throws Exception
	 *
	 * @since  1.0.0
	 */
	public static function triggerPlugin(string $type = null, string $plugin = null, string $event = null, $args = []): mixed
	{
		// Load plugin
		self::loadPlugin($type, $plugin);

		// Check plugin enable
		if (!PluginHelperBase::isEnabled($type, $plugin))
		{
			return false;
		}

		// Get plugin
		$class = Factory::getApplication()->bootPlugin($plugin, $type);
		if ($class instanceof DummyPlugin)
		{
			return false;
		}

		// Find method
		$method = $event;
		if ($class instanceof SubscriberInterface)
		{
			$subscribedEvents = $class::getSubscribedEvents();

			$method = (isset($subscribedEvents[$event])) ? $subscribedEvents[$event] : false;
		}

		if (empty($method))
		{
			return false;
		}

		return (method_exists($class, $method)) ? call_user_func_array([$class, $method], $args) : false;
	}


	/**
	 * Calls the plugins handler associated with the event.
	 *
	 * @param   string|array|null  $types  The plugin type, relates to the subdirectory in the plugins directory.
	 * @param   string|null        $event  The event name.
	 * @param   array  |null       $args   An array of arguments (optional).
	 *
	 * @return array Plugin event result.
	 *
	 * @throws Exception
	 *
	 * @since  1.0.0
	 */
	public static function triggerPlugins($types = null, ?string $event = null, ?array $args = []): array
	{
		$result = [];
		if (!is_array($types))
		{
			$types = [$types];
		}
		foreach ($types as $type)
		{
			if ($plugins = PluginHelperBase::getPlugin($type))
			{
				foreach ($plugins as $plugin)
				{
					$key = self::getPluginKey($type, $plugin->name);
					try
					{
						$result[$key] = self::triggerPlugin($type, $plugin->name, $event, $args);
					}
					catch (\Throwable $e)
					{
						if (JDEBUG || $e instanceof \RuntimeException)
						{
							throw $e;
						}

						$result[$key] = false;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Method to load plugins.
	 *
	 * @param   string|null  $type    The plugin type, relates to the subdirectory in the plugins directory.
	 * @param   string|null  $plugin  The plugin name.
	 *
	 * @throws Exception
	 *
	 * @since  1.0.0
	 */
	private static function loadPlugin(string $type = null, string $plugin = null): void
	{
		if (empty($type) || empty($plugin))
		{
			return;
		}

		if (self::$_plugins === null)
		{
			self::$_plugins = [];
		}

		$key = self::getPluginKey($type, $plugin);

		if (!isset(self::$_plugins[$key]))
		{
			// Import plugin
			PluginHelperBase::importPlugin($type, $plugin);

			// Load language
			Factory::getApplication()->getLanguage()->load($key, JPATH_ADMINISTRATOR);

			if (isset(Factory::$language))
			{
				Factory::getApplication()->getLanguage()->load($key, JPATH_ADMINISTRATOR);
			}
		}
	}

	/**
	 * Get plugin cache key.
	 *
	 * @param   string|null  $type    The plugin type, relates to the subdirectory in the plugins directory.
	 * @param   string|null  $plugin  The plugin name.
	 *
	 * @return string Plugin cache key.
	 *
	 * @since  1.0.0
	 */
	private static function getPluginKey(string $type = null, string $plugin = null): string
	{
		return 'plg_' . $type . '_' . $plugin;
	}

}
<?php
/**
 * @package              Econsult Labs Library
 * @subpackage           Econsult Labs system plugin
 * @version              __DEPLOYMENT_VERSION__
 * @author               ECL <info@econsultlab.ru>
 * @link                 https://econsultlab.ru
 * @copyright            Copyright © 2025 ECL All Rights Reserved
 * @license              http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\ECLabs\Extension\ECLabs;

return new class () implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   4.4.0
	 */
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				return new ECLabs(
					$container->get(DispatcherInterface::class),
					(array) PluginHelper::getPlugin('system', 'eclabs'),
					Factory::getApplication(),
					$container->get(DatabaseInterface::class)
				);
			}
		);
	}
};

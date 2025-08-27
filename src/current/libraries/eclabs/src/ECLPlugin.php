<?php
/**
 * @package        Econsult Labs Library
 * @version          __DEPLOYMENT_VERSION__
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2025 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

\defined('_JEXEC') or die;

\defined('DS') or define('DS', DIRECTORY_SEPARATOR);

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;


require_once JPATH_LIBRARIES . '/eclabs/src/autoload.php';


/**
 * Абстрактный класс плагина
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
abstract class ECLPlugin extends CMSPlugin implements SubscriberInterface
{
	use Traits\ECLPlugin;
    use DatabaseAwareTrait;

    /**
     * @param DispatcherInterface $dispatcher
     * @param array $config
     * @param CMSApplicationInterface $app
     * @param DatabaseInterface $db
     * @since 1.0.0
     */

    public function __construct(DispatcherInterface $dispatcher, array $config, CMSApplicationInterface $app, DatabaseInterface $db)
	{
		parent::__construct($config);
        $this->setApplication($app);
        $this->setDatabase($db);
        $this->jVersion = ECLVersion::getJoomlaVersion();
	}
}
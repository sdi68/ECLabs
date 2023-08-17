<?php
/**
 * @package        Econsult Labs Library
 * @version          1.0.15
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

defined('_JEXEC') or die;
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

use Exception;
use JLog;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;

require_once JPATH_LIBRARIES . '/eclabs/classes/autoload.php';

try
{
	ECLAutoLoader::registerJoomla3Stub('SubscriberInterface', 'Joomla\Event','interface',JPATH_LIBRARIES . '/vendor/joomla/event/src/');
}
catch (Exception $e)
{
	JLog::add($e->getMessage(),JLog::ERROR,"ECLPlugin");
}

/**
 * Абстрактный класс плагина
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
abstract class ECLPlugin extends CMSPlugin implements \Joomla\Event\SubscriberInterface
{
	use Traits\ECLPlugin;

	/**
	 * @param $subject
	 * @param $config
	 *
	 * @since 1.0.0
	 */

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

        $this->jVersion = ECLVersion::getJoomlaVersion();
	}
}
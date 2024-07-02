<?php
/**
 * @package             Econsult Labs Library
 * @version             1.0.20
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2024 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @since               1.0.20
 */

namespace ECLabs\Library\ECLLogging\Loggers;

use ECLLOG\ECLLogger;
use JConfig;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;

/**
 * Класс логгера для Joomla
 * @package     ECLabs\Library\Loggers
 *
 * @since       1.0.20
 */
class ECLabDefaultLogger extends ECLLogger
{
	/**
	 * @inheritDoc
	 * @since       1.0.20
	 */
	public function __construct(array $options)
	{
		parent::__construct($options);
		$this->entry_format    = "[{timestamp}] - {type} - {caller} - {message} - {data}";
		$this->advanced_fields = array();
	}

	/**
	 * @inheritDoc
	 * @since       1.0.20
	 */
	protected function setPathFromSource(string $source): string
	{
		$config = new JConfig();

		return $config->log_path . '/' . $source . '.php';
	}


	/**
	 * @inheritDoc
	 * @since       1.0.20
	 */
	protected function generateFileHeader(): string
	{
		$head    = array();
		$head[]  = '#';
		$head[]  = '#<?php die(\'Forbidden.\'); ?>';
		$head[]  = '#Date: ' . gmdate('Y-m-d H:i:s') . ' UTC';
		$version = new Version();
		$head[]  = '#Software: ' . $version->getLongVersion();
		$head[]  = '';

		// Prepare the fields string
		$fields       = array_merge($this->getDefaultFields(), $this->advanced_fields);
		$fields_names = array();
		foreach ($fields as $key => $val)
		{
			$fields_names[] = $key;
		}
		$head[] = '#Fields: ' . implode(" - ", $fields_names);
		$head[] = '';

		return implode("\n", $head);
	}
	/**
	 * @inheritDoc
	 * @since       1.0.20
	 */
	protected function getTimeStamp($timestamp): string
	{
		if (!empty($timestamp))
			return $timestamp;

		return Factory::getDate()->format('Y-m-d H:i:s');
	}
}